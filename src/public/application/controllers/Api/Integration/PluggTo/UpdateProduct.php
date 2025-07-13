<?php

require APPPATH . "controllers/BatchC/Integration/PluggTo/Product/Product.php";

class UpdateProduct
{
    public $_this;
    private $product;    

    public function __construct($_this)
    {        
        $this->_this = $_this;
        $this->product = new Product($_this);
        header('Integration: v1');
    } 

    public function create($idProduct, $user, $changes, $access_token)
    {
        $this->_this->setJob('WeebHook-createProduct-PluggTo');        
        
        $url    = "https://api.plugg.to/products/$idProduct?access_token={$access_token}";
        $data   = "";
        $this->typeIntegration = 'pluggto';
        
        $dataProducts = json_decode(json_encode($this->_this->sendREST($url, $data)));        

        if ($dataProducts->httpcode != 200) {        
            $this->_this->log_integration("Erro para integrar produto {$idProduct}", "<h4>Não foi possível localizar dados do produto {$idProduct}</h4>", "E");
            return false;
        }

        $prod = json_decode($dataProducts->content);
        $prod = $prod->Product;
        $id_produto = $prod->id;
        $skuProduct = $prod->sku;
        $nomeProd   = $prod->name;

        // Recupera o código do produto pai
        $verifyProduct = $this->product->getProductForIdErp($id_produto, $skuProduct);
        
        if (empty($verifyProduct)) {
            $this->_this->setUniqueId($id_produto); // define novo unique_id
            $this->_this->db->trans_begin();
            $productCreate = $this->product->createProduct($prod);

            if (!$productCreate['success']) {
                $this->_this->db->trans_rollback();
                $erroMessage = '';
                $productCreate['message'] = $productCreate['message'] ?? [];
                foreach (((array)$productCreate['message']) as $message) {
                    $erroMessage .= "<li>{$message}</li>";
                }
                
                $this->_this->log_integration("Erro para atualizar o produto {$skuProduct}", "Produto não cadastrado. <ul>{$erroMessage}</ul>", "E");
                return null;
            }

            $this->_this->log_integration("Produto {$skuProduct} cadastrado com sucesso", "<h4>Produto {$nomeProd} foi cadastrado com sucesso.</h4>", "S");
            $this->_this->log_data('api', 'pluggto/createProduct', "Produto {$idProduct} cadastrado com sucesso", 'I');

            if ($this->_this->db->trans_status() === false) {
                $this->_this->db->trans_rollback();
                echo "ocorreu um erro com a transação\n";
                $this->_this->log_integration("Alerta para integrar produto - {$prod->sku}", 'Ocorreu uma instabilidade para cadastro o produto. Em instantes será feito uma nova tentativa!', "W");
                return null;
            }

            $this->_this->db->trans_commit();

            return true;
        }

        $this->_this->log_integration("Erro ao cadastrar o produto {$skuProduct}", "<h4>Produto {$idProduct} já cadastrado no sistema</h4>", "E");
        return null;
    }

    public function update($idProduct, $user, $changes, $access_token)
    {
        $this->_this->setJob('WeebHook-updateProduct-PluggTo');        
        
        $url    = "https://api.plugg.to/products/$idProduct?access_token={$access_token}";
        $data   = "";
        $this->typeIntegration = 'pluggto';
        
        $dataProducts = json_decode(json_encode($this->_this->sendREST($url, $data)));        

        if ($dataProducts->httpcode != 200) {        
            $this->_this->log_integration("Erro para atualizar o produto {$idProduct}", "<h4>Não foi possível localizar dados do produto {$idProduct}</h4>", "E");
            return null;
        }

        $change = $changes;        
        $this->_this->setUniqueId($idProduct); // define novo unique_id        
        $prod = json_decode($dataProducts->content);        

        $result = null;
        if($change->price == true){
            $price = $prod->Product->special_price ? $prod->Product->special_price : $prod->Product->price;
            $price = number_format($price,2,".","");
            $verifyProduct = $this->product->getProductForIdErp($idProduct);
            
            if (empty($verifyProduct)) {
                return null;
            }
            $sku = $verifyProduct['sku'];
            $old_price = $verifyProduct['price'];
            $nameProd  = $verifyProduct['name'];
            $update = $this->product->updatePrice($sku, $price);

            if(count($prod->Product->variations) > 0){
                //atualiza preço das variações 
                $variations = $prod->Product->variations;
                foreach($variations as $variation){
                    $price = $variation->special_price ? $variation->special_price : $variation->price;
                    $price = number_format($price,2,".","");
                    $sku   = $variation->sku;
                    $update = $this->product->updatePrice($sku, $price);                    
                }
            }

            if ($update !== false) {
                $this->_this->log_integration("Preço do produto {$sku} atualizado", "<h4>O preço do produto {$nameProd} foi atualizado com sucesso.</h4><ul><li>Preço anterior : {$old_price}</li><li>Preço atualizado : {$price}</li></ul>", "S");
                $this->_this->log_data('api', 'pluggto/updatePrice', "preço do produto {$sku} da loja {$this->_this->store} alterado para {$price}", 'I');
                $result = true;
            }else{
                $result = false;
            }
        }
        
        if($change->stock == true){
            $stockNew = $prod->Product->quantity;
            $verifyProduct = $this->product->getProductForIdErp($idProduct);
            
            if (empty($verifyProduct)) {
                return null;
            }
            $skuPai = $verifyProduct['sku'];            
            $id_prod = $verifyProduct['id'];
            $old_stock = $verifyProduct['qty'];
            $nameProd  = $verifyProduct['name'];
            if(count($prod->Product->variations) > 0){
                //atualiza preço das variações
                $variations = $prod->Product->variations;
                foreach($variations as $variation){
                    $verifyProductVar = $this->product->getVariationForSku($variation->sku);
                    if (!empty($verifyProductVar)) {
                        // Adiciona preço para atualizar o preço do produto filho
                        $skuProductVar     = $variation->sku;                                
                        $idProductVar      = $variation->id;
                        $stockNewVar       = $variation->quantity ?? 0;
                                                        
                        $updateStock = $this->updateStock($idProductVar, $skuProductVar, $stockNewVar, $skuPai);
                    }
                }
            }
            else
            {
                $updateStock = $this->updateStock($id_prod, $skuPai, $stockNew);
            }

            if ($updateStock[0] != false && $updateStock[0] != null) {
                $verifyProduct = $this->product->getProductForIdErp($idProduct);
                $sku = $verifyProduct['sku'];
                $stockNew = $verifyProduct['qty'];
                $this->_this->log_integration("Estoque do produto {$sku} atualizado", "<h4>O estoque do produto {$sku} foi atualizado com sucesso.</h4><ul><li>Estoque anterior : {$old_stock}</li><li>Estoque atualizado : {$stockNew}</li></ul>", "S");
                $this->_this->log_data('api', 'pluggto/updateStock', "Estoque do produto {$idProduct} alterado de {$old_stock} para {$stockNew}", 'I');
                $result = true;
            }else{
                $result = false;
            }
        }

        return $result;
    }


    /**
     * Atualiza estoque de um produto ou variação, caso esteja diferente
     * @param   string  $skuProduct     SKU do produto (Normal ou Pai)
     * @param   int     $idProduct      ID do produto PluggTo
     * @return  array                   Retorna o estado da atualização | null=Está com o mesmo estoque, true=atualizou, false=deu problema
     */
    public function updateStock($idProductPai, $skuProduct, $stockNew, $skuPai = null)
    {
        if ($stockNew < 0) return array(false);

        

        if(!empty($skuPai))
        {
            $stock = $stockNew;
            $stockReal = $this->product->getStockVariationForSku($skuProduct, $skuPai) ?? 0;
            if($stock == (int)$stockReal) return array(null);            
            return array($this->product->updateStockVariation($skuProduct, $skuPai, $stock),$stockReal,$stock);
        }

        $stock = $stockNew;     
        $stockReal = $this->product->getStockForSku($skuProduct) ?? 0;
        if($stock == (int)$stockReal) return array(null);
        return array($this->product->updateStockProduct($skuProduct, $stock, $idProductPai),$stockReal,$stock);              
    }   

    public function getStoreBySKUProduct($skuProduct)
    { 
        
        $ID_contaSeller = explode("-", $skuProduct);
        $ID_contaSeller = $ID_contaSeller[0];
        
        $credentials = $this->CI->model_api_integrations->getDataByIntegration('pluggto');
        foreach($credentials as $credential)
        {
            if (!$credential || $credential['status'] != 1)
            return false;

            $data = json_decode($credential['credentials']);
            if(isset($data->user_id) && ($data->user_id == $ID_contaSeller))
            return $credential['store_id'];
        }
        
    }
 

    public function getVariationForSku($sku)
    {
        $this->store = $this->getStoreBySKUProduct($sku);
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(                    
                    'prd_variants.sku' => $sku,
                    'products.store_id'  => $this->store,
                )
            )
            ->get()
            ->row_array();
    }    
}