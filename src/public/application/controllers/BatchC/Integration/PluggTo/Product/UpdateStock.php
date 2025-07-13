<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/PluggTo/Product/UpdateStock run
 *
 */

require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";
require APPPATH . "controllers/BatchC/Integration/PluggTo/Product/Product.php";

class UpdateStock extends Main
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

        $this->setJob('UpdateStock');
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Buscando produtos com estoque alterado.........\n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        // Grava a última execução
        $this->saveLastRun();

        // Recupera os produtos
        $this->getProducts();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        
        $this->gravaFimJob();
    }


    public function getProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        $arrDadosJobIntegration = $this->model_integrations->getJobForJobAndStore('UpdateStock', $this->store);

        $dtLastRun = $arrDadosJobIntegration['last_run'];
        if ($dtLastRun) {
            $dtLastRun = date_format(new DateTime($dtLastRun), 'Y-m-d');
        }

        $supplier_id = $this->getIDuserSellerByStore($this->store);

        // começando a pegar os produtos para criar
        $this->getListProducts($dtLastRun, $supplier_id);
    }

      
    public function getListProducts(?string $dtLastRun, int $supplier_id)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $ult_prod = null;
        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";            
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }       

        $today = date("Y-m-d");

        $haveProductList = true;
        $access_token = $this->getToken();

        while ($haveProductList) {

            // Começando a pegar os produtos para criar
            $url = "https://api.plugg.to/products?access_token=$access_token&limit=100&supplier_id=$supplier_id";
            if ($dtLastRun) {
                $url .= "&modified={$dtLastRun}to$today";
            }
            if (!empty($ult_prod)) {
                $url .= "&next=$ult_prod";
            }

            $dataProducts = json_decode(json_encode($this->sendREST($url)));

            if ($dataProducts->httpcode != 200) {
                echo "Erro para buscar a lista de url=$url, retorno=" . json_encode($dataProducts) . "\n";
                $haveProductList = false;
                continue;
            }

            $prods = json_decode($dataProducts->content);
            if ($prods->total <= 0) {
                echo "Lista de produtos vazia url=$url\n";
                $haveProductList = false;
                continue;
            }

            if ($dataProducts->httpcode != 200) {
                echo "Erro para buscar a listagem de url=$url\n";
                $this->log_data('batch', $log_name, "Erro para buscar a listagem de url=$url, retorno=" . json_encode($dataProducts), "W");
                $haveProductList = false;
                continue;
            }

            $idProduct = "";

            //$this->db->trans_begin();

            foreach ($prods->result as $product) {
                $hasVariation = false;
                $prod = $product->Product;
                $skuProduct = $prod->sku;
                $idProduct = $prod->id; //Produto pai

                $this->setUniqueId($idProduct); // define novo unique_id
                $verifyProduct = $this->product->getProductForIdErp($idProduct);

                if (empty($verifyProduct)) {
                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);

                    $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct));
                }

                // existe o sku na loja, mas não esá com o registro do id da PluggTo
                if (!empty($verifyProduct)) {

                    //verifica se produto tem variação
                    if ($verifyProduct['has_variants'] !== '' && isset($prod->variations) && (count($prod->variations) > 0)) {
                        $hasVariation = true;
                        foreach ($prod->variations as $prodvar) {
                            $verifyProduct = $this->product->getVariationForSku($prodvar->sku);

                            if (!empty($verifyProduct)) {
                                // Adiciona preço para atualizar o preço do produto filho
                                $skuProductVar = $prodvar->sku;
                                $idProductVar = $prodvar->id;
                                $stockNewVar = $prodvar->quantity ?? 0;

                                $updateStock = $this->updateStock($idProduct, $skuProductVar, $stockNewVar, $skuProduct);

                                if ($updateStock[0] === false) {
                                    echo "Erro para atualizar o estoque da variação SKU {$skuProductVar}\n";
                                    $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProductVar} ID={$idProductVar}, dados_item_lista=" . json_encode($prodvar) . " retorno=" . json_encode($updateStock), "E");
                                    $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProductVar}", "<h4>Não foi possível atualizar o estoque do produto {$skuProductVar}</h4>", "E");
                                    continue;
                                }

                                if ($updateStock[0] === null) {
                                    echo "Estoque do produto={$skuProductVar} igual ao do banco, não será modificado\n";
                                    continue;
                                }

                                //atualizou com sucesso
                                if ($updateStock[0] === true) {
                                    $this->log_integration("Estoque da variação {$skuProductVar} atualizado", "<h4>O estoque do produto {$skuProductVar} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                                    echo "Estoque do produto={$skuProductVar} atualizado com sucesso\n";
                                    $this->log_data('batch', $log_name, "Estoque do produto {$skuProductVar} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                                    continue;
                                }

                            }
                        }
                    }

                    if ($hasVariation === true) {
                        continue;
                    }
                    $stockNew = $prod->quantity;

                    $updateStock = $this->updateStock($idProduct, $skuProduct, $stockNew);

                    if ($updateStock[0] === false) {
                        echo "Erro para atualizar o estoque do produto SKU {$skuProduct}\n";
                        $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($prod) . " retorno=" . json_encode($updateStock), "E");
                        $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>", "E");
                        continue;
                    }
                    if ($updateStock[0] === null) {
                        echo "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";
                        continue;
                    }

                    //atualizou com sucesso
                    if ($updateStock[0] === true) {
                        $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                        echo "Estoque do produto={$skuProduct} atualizado com sucesso\n";
                        $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                        continue;
                    }
                } else {
                    echo "Produto não encontrado, não será possível atualizar o estoque do produto ID_PluggTo={$idProduct}, SKU={$skuProduct} \n";
                    //$this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o estoque do produto ID_PluggTo={$idProduct}, SKU={$skuProduct}", "E");
                    //$this->log_integration("Alerta para atualizar o estoque do produto {$skuProduct}", "<h4>Não foi possível localizar o produto para atualizar seu estoque, em breve será cadastrado e poderá ser atualizado.</h4> <strong>SKU</strong>: {$skuProduct}<br><strong>ID_PluggTo</strong>: {$idProduct}", "W");
                    continue;
                }

            }

            if ($idProduct) {
                $ult_prod = $idProduct;
            }
        }
       
        /*if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
        }*/
        
        //$this->db->trans_commit();
        
        //$this->getListProducts($dtLastRun, $supplier_id, $idProduct);
    }


    /**
     * Atualiza estoque de um produto ou variação, caso esteja diferente
     * @param   string  $skuProduct     SKU do produto (Normal ou Pai)
     * @param   int     $idProduct      ID do produto PluggTo
     * @return  array                   Retorna o estado da atualização | null=Está com o mesmo estoque, true=atualizou, false=deu problema
     */
    public function updateStock($idProductPai, $skuProduct, $stockNew, $skuPai = null)
    {
        //if ($stockNew <= 0) return array(false);

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

}