<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Eccosys/Product/UpdateStock run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";
require APPPATH . "controllers/BatchC/Integration/Eccosys/Product/Product.php";

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

		echo "Essa rotina foi inativada\n";
		die; 
		
        /* faz o que o job precisa fazer */
        echo "Buscando produtos com estoque alterado.........\n";

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


    public function getProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        // começando a pegar os produtos para criar
        $this->getListProducts();
    }

      
    public function getListProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";            
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }
        
        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $ECCOSYS_URL = '';
        if($dataIntegrationStore){
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $ECCOSYS_URL = $credentials->url_eccosys;
        }
        /* faz o que o job precisa fazer */
        
        $url = $ECCOSYS_URL.'/api/produtos/produtosComEstoqueAlterado';
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataProducts->httpcode == 404) {
            echo "Sistema atualizado";
            return false;
        }

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->httpcode != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");                
            }
            return false;
        }

        $regProducts = json_decode($dataProducts->content);
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;

    
        if ($dataProducts->httpcode != 200) {            
            if ($dataProducts->httpcode == 999 && $countlimiteRequestBlock <= $limiteRequestBlock) {
                echo "aguardo 1 minuto para testar novamente. (Tentativas: {$countlimiteRequestBlock}/{$limiteRequestBlock})\n";
                sleep(60);
                $countlimiteRequestBlock++;                
            }

            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");                
            $haveProductList = false;            
        }        

        // Inicia transação
        
        foreach ($regProducts as $registro) {
            $this->db->trans_begin();
            $skuProduct     = $registro->codigo;         
            $idProduct      = $registro->id;

            $this->setUniqueId($idProduct); // define novo unique_id            
            $verifyProduct = $this->product->getProductForIdErp($idProduct);
            
            if (!empty($verifyProduct)) 
            {
                // verifica se sku existe
                $verifyProduct = $this->product->getProductForSku($skuProduct);
                // existe o sku na loja, mas não está com o registro do id da Eccosys
                if (!empty($verifyProduct)) {
                    $urlEsp = $ECCOSYS_URL."/api/produtos/$idProduct";
                    $data = '';
                    $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $data)));
                    if ($dataProduct->httpcode != 200) {
                        $this->db->trans_rollback();
                        continue;
                    }

                    $product = json_decode($dataProduct->content);
                   
                    $stockNew = $product->_Estoque->estoqueDisponivel ?? 0;
                    $updateStock = $this->updateStock($idProduct, $skuProduct, $stockNew);

                    //atualiza lista de produtos com estoque alterado
                    $urlUpdate = $ECCOSYS_URL.'/api/produtos/produtosComEstoqueAlterado';
                    $dataUpdate =
                    '{
                        "codigo": "'.$skuProduct.'",
                        "opcEstoqueEcommerce": "N"
                    }';
                    $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));

                    if ($updateStock[0] === false) {
                        $this->db->trans_rollback();
                        echo "Erro para atualizar o estoque do produto SKU {$skuProduct}\n";                        
                        $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($product) . " retorno=" . json_encode($updateStock), "E");
                        $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>", "E");
                        continue;
                    }
                    if ($updateStock[0] === null) {
                        $this->db->trans_rollback();
                        //$this->log_data('batch', $log_name, "Alerta para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($product) . " retorno=" . json_encode($updateStock), "W");
                        continue;
                    }

                    //atualizou com sucesso
                    if ($updateStock[0] === true) {
                        $this->db->trans_commit();
                        $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                        echo "Estoque do produto={$skuProduct} atualizado com sucesso\n";
                        $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");
                        continue;
                    }
                }                
            }
            else // Não encontrou o produto, tenta procurar na tabela de variação(prd_variants)
            {
                $verifyProduct = $this->product->getVariationForSku($skuProduct);
                if (!empty($verifyProduct)) {
                    // Adiciona preço para atualizar o preço do produto filho

                    $skuProductVar     = $skuProduct;                                
                    $idProductVar      = $idProduct;                    

                    $urlEsp = $ECCOSYS_URL."/api/produtos/$idProductVar";
                    $data = '';
                    $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $data)));
                    if ($dataProduct->httpcode != 200) {
                        $this->db->trans_rollback();
                        continue;
                    }

                    $product = json_decode($dataProduct->content);                                      
                    $stockNewVar = $product->_Estoque->estoqueDisponivel ?? 0;
                    $productPai = $this->product->getProductForIdErp($product->idProdutoMaster);

                    if (!empty($productPai)) {
                        $updateStock = $this->updateStock($idProduct, $skuProductVar, $stockNewVar, $productPai['sku']);
                        
                        //atualiza lista de produtos com estoque alterado
                        $urlUpdate = $ECCOSYS_URL.'/api/produtos/produtosComEstoqueAlterado';
                        $dataUpdate =
                        '{
                            "codigo": "'.$skuProduct.'",
                            "opcEstoqueEcommerce": "N"
                        }';
                        $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));

                        if ($updateStock[0] === false) {
                            $this->db->trans_rollback();
                            echo "Erro para atualizar o estoque do produto SKU {$skuProduct}\n";                        
                            $this->log_data('batch', $log_name, "Erro para atualizar o estoque do produto SKU {$skuProduct} ID={$idProduct}, dados_item_lista=" . json_encode($productVar) . " retorno=" . json_encode($updateStock), "E");
                            $this->log_integration("Erro para atualizar o estoque do produto SKU {$skuProduct}", "<h4>Não foi possível atualizar o estoque do produto {$skuProduct}</h4>", "E");
                            continue;
                        }
                        if ($updateStock[0] === null) {
                            $this->db->trans_rollback();                 
                            //echo "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";                        
                            continue;
                        }

                        //atualizou com sucesso
                        if ($updateStock[0] === true) {
                            $this->db->trans_commit();
                            $this->log_integration("Estoque do produto {$skuProduct} atualizado", "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong> {$updateStock[1]}<br><strong>Estoque alterado:</strong> {$updateStock[2]}", "S");
                            echo "Estoque do produto={$skuProduct} atualizado com sucesso\n";
                            $this->log_data('batch', $log_name, "Estoque do produto {$skuProduct} atualizado. estoque_anterior={$updateStock[1]} estoque_atualizado={$updateStock[2]}", "I");                                            
                            continue;
                        }
                    }
                }
            }

            //produto não encontrado, tenta limpar a lista de produtos alterados
            $urlUpdate = $ECCOSYS_URL.'/api/produtos/produtosComEstoqueAlterado';
            $dataUpdate =
            '{
                "codigo": "'.$skuProduct.'",
                "opcEstoqueEcommerce": "N"
            }';
            $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));

        }        
    }

    

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