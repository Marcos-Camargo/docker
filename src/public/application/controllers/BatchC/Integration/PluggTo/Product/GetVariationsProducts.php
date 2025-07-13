<?php

/**
 * Class GetVariationsProducts
 *
 * php index.php BatchC/Integration/PluggTo/Product/GetVariationsProducts run null 20
 *
 */

require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";
require APPPATH . "controllers/BatchC/Integration/PluggTo/Product/Product.php";

class GetVariationsProducts extends Main
{
    private $product;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('GetVariationsProducts');
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            echo "Parametros informados incorretamente. ID={$id} - STORE={$store}\n";
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando produtos... \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        // Recupera os produtos
        $this->getProducts();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Recupera os produtos
     *
     * @return bool
     */
    public function getProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }
        
        $access_token = $this->getToken();       
        // Começando a pegar os produtos para criar
        $url    = "https://api.plugg.to/products?access_token={$access_token}";
        $data   = "";
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
        }

        $prods = json_decode($dataProducts->content);
        if($prods->total <= 0){
            echo "Lista de produtos vazia\n";
            $this->log_data('batch', $log_name, "Lista de produtos vazia, retorno = " . json_encode($dataProducts), "W");
            return false;
        }

        // Inicia transação
        $this->db->trans_begin();
        foreach ($prods->result as $product) {
            $prod = $product->Product;
            $id_produto = $prod->id;
            $skuProduct = $prod->sku;            
            $this->setUniqueId($id_produto); // define novo unique_id
            $verifyProduct = $this->product->getProductForIdErp($id_produto, $skuProduct);            
            if (!empty($verifyProduct)) {
                //produto cadastrados
                //verifica se alguma variação não esta cadastradas
                $prd_id = $verifyProduct['id'];
                if (isset($prod->variations) && (count($prod->variations) > 0) && isset($prd_id)) {
                    $hasVariations = explode(';',$verifyProduct['has_variants']);      
                    $produtoPaiNovoEstoque = 0;             
                    foreach ($prod->variations as $prodvar) 
                    {
                        $verifyProductVariation = $this->product->getVariationForSku($prodvar->sku);
                        if (empty($verifyProductVariation)) {
                            $variation_name = '';                   
                            foreach ($prodvar->attributes as $attr){                                
                                if (!array_key_exists($attr->code, $this->product->tipoVariacoes)){
                                    echo "Variação produto sku = $skuProduct -- $attr->code, Não compatível com o sistema...\n";                                   
                                }else{
                                    $variationType = $this->product->tipoVariacoes[$attr->code];
                                    $variation_name .= $attr->value->label.";";
                                    if (!in_array($variationType, $hasVariations)) {
                                        $hasVariations[] = $variationType;
                                    }
                                }                                
                            }
                            $variant_count = 0;
                            $variant_count = $this->getMaxVariationNumber($prd_id);
                            $this->model_products->createvar(
                                array(
                                'prd_id'                => $prd_id ?? 0,
                                'variant'               => ++$variant_count,
                                'name'                  => $variation_name ?? '',
                                'sku'                   => $prodvar->sku ?? '',
                                'qty'                   => $prodvar->quantity ?? 0,
                                'variant_id_erp'        => $prodvar->id ?? 0,
                                'price'                 => number_format($prodvar->price, 2,".","") ?? 0,
                                'image'                 => '',
                                'status'                => 1,
                                'ean'                   => $prodvar->ean ?? '',
                                'codigo_do_fabricante'  => '', )
                                );

                            $produtoPaiNovoEstoque += $prodvar->quantity;
                            echo "Variação {$prodvar->sku} cadastrada com sucesso!\n";
                            $this->log_integration("Variação do produto {$skuProduct} cadastrada", "<h4>Variação cadastrada com sucesso</h4> <ul><li>Nome: {$prodvar->name}</li><li>Sku: {$prodvar->sku}</li><li>Preço: {$prodvar->price}</li></ul>", "S");              
                        }else{
                            echo "Variação {$prodvar->sku} já cadastrado..\n";                    
                        }
                    } 
                    if(!empty($hasVariations)){
                        $this->product->updateHasVariations($prd_id, $hasVariations);
                        $this->updateStockProduct($payload->sku, $produtoPaiNovoEstoque);
                    }                      
                }
            }
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
        }
        $this->db->trans_commit();
    }

    /**
     * Atualiza o estoque do produto
     *
     * @param   string  $sku        SKU do produto
     * @param   float   $qty        Novo saldo do estoque do produto
     * @return  bool                Retorna o status da atualização
     */
    public function updateStockProduct($sku, $qty)
    {
        $product = $this->getProductForSku($sku);
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );

        return $this->_this->db->update('products', array('qty' => $qty)) ? true : false;
    }

    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku($sku)
    {

        return $this->db->get_where(
            'products use index (store_sku)',
            array(
                'store_id'  => $this->_this->store,
                'sku'       => $sku
            )
        )->row_array();
    }

    //retorna o maior numero da coluna variation de um prd_id 
    public function getMaxVariationNumber($prd_id){

        $variation = $this->db
        ->select('max(variant)')
        ->from('prd_variants')
        ->where(array(
                'prd_id'  => $prd_id,                
        ))
        ->get()
        ->result_array();

    if (!$variation) return 0;
    return $variation[0]['max(variant)'];
    }
}