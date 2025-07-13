<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Eccosys/Product/UpdateProducts run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";
require APPPATH . "controllers/BatchC/Integration/Eccosys/Product/Product.php";

class UpdateProduct extends Main
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
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('UpdateProduct');
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
        echo "Atualizando lista de produtos \n";

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

        // começando a pegar os produtos para criar
        $this->getListProducts();
    }

    /**
     * Recupera a lista para cadastro do produto
     *
     * @return bool
     */
    public function getListProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }
        
        $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
        $ECCOSYS_URL = '';
        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $ECCOSYS_URL = $credentials->url_eccosys;
        }
        /* faz o que o job precisa fazer */

        // começando a pegar os produtos para criar
        $url = $ECCOSYS_URL.'/api/produtos/produtosAlterados';
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));
               
        $arrayProductErroCheck  = array();
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;

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


        if ($dataProducts->httpcode != 200) {
            if ($dataProducts->httpcode == 999 && $countlimiteRequestBlock <= $limiteRequestBlock) {
                echo "aguardo 1 minuto para testar novamente. (Tentativas: {$countlimiteRequestBlock}/{$limiteRequestBlock})\n";
                sleep(60);
                $countlimiteRequestBlock++;
            }

            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
        }
        
        // Inicia transação
        
        foreach ($regProducts as $registro) {
				          
			$this->db->trans_begin();
            $id_produto = $registro->id;
            $codigo = $registro->codigo;
            $this->setUniqueId($id_produto); // define novo unique_id
           
            //busca dados completos do produto
            $urlEsp = $ECCOSYS_URL."/api/produtos/$registro->id";
            $dataEsp = '';
            $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $dataEsp)));

            if ($dataProduct->httpcode != 200) {                
                $this->db->trans_rollback();
                if ($dataProduct->httpcode != 99) {
                    $this->log_data('batch', $log_name, "Produto com ID={$id_produto}, codigo: {$codigo} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                    $this->log_integration("Erro para atualizar o produto ID {$id_produto}, codigo: {$codigo}", "Não foi possível obter informações do produto! ID={$id_produto}", "E");
                }
                continue;
            }

            $product        = json_decode($dataProduct->content);
            $idProduct      = $product->id;
            $skuProduct     = $product->codigo;
                
            if (in_array($idProduct, $arrayProductErroCheck)) {
                echo "Já tentou atualizar o ID={$idProduct} e deu erro\n";
                $this->db->trans_rollback();
                continue;
            }
			
			if ($product->idProdutoMaster == '0') { // é um produto pai   
	            $verifyProduct = $this->product->getProductForSku($skuProduct);
	                    
	            // existe o sku na loja
	            if (!empty($verifyProduct)) { 
	                if ($verifyProduct['status'] != 1) {
	                    echo "Produto não está ativo\n";
	                    $this->db->trans_rollback();
	                    continue;
	                }
	
	                $dirImage = $verifyProduct['image'];
	
	                //atualiza imagens
	                if ($this->uploadproducts->countImagesDir($dirImage) == 0){
	                    //busca endpoint de imagens
	                    $urlImg = $ECCOSYS_URL."/api/produtos/$idProduct/imagens";
	                    $dataImg = '';
	                    echo "Buscando as imagens do produto -> {$idProduct}............\n";
	                    $dataProductImgs = json_decode(json_encode($this->sendREST($urlImg, $dataImg)));
	
	                    $product->anexos = null;
	
	                    if ($dataProductImgs->httpcode == 200) {
	                        $imgsResult = json_decode($dataProductImgs->content);
	                        if (count($imgsResult)>0 && !empty($imgsResult)) {
	                            echo "Total de imagens encontradas ".count($imgsResult)."\n";
	                            $product->anexos = $imgsResult;
	                            $product->path_images = $dirImage;
	                        }else{
	                            echo "Não foram encontradas imagens para este produto \n";
	                        }
	                    } else {
	                        $this->log_data('batch', $log_name, "Imagem não cadastrada: url={$urlImg}, codigo: {$codigo} encontrou um erro, dados_item_lista=" . json_encode($dataProductImgs) . " retorno=" . json_encode($dataProductImgs), "W");
	                        echo "Imagens não cadastradas ou com erro.\n";
	                    }
	
	                    if (count($imgsResult)>6) {                        
	                        $productIdentifier = "SKU {$product->codigo}";
	                        $this->log_integration("Erro para integrar produto - {$productIdentifier}", 'Produto chegou com mais imagens que o permitido <br><strong>ID Eccosys</strong>:'. $idProduct. '<br>', "E");
	                    }
	                }
	
	                $productUpdate = $this->product->updateProduct($product);
					if (is_null($productUpdate['success'])) { 
						echo "produto={$idProduct} não precisa de atualização\n";
						$this->db->trans_rollback();
						if (!in_array($idProduct, $arrayProductErroCheck)) {
	                        array_push($arrayProductErroCheck, $idProduct);
	                    }
						continue;
					}
	
	                if ($productUpdate['success'] === false) {
	                    echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($productUpdate) . "\n";
	                    $this->db->trans_rollback();
	                    $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID eccosys
	                        
	                    // adiciono no array para não consultar mais esse produto pai
	                    if (!in_array($idProduct, $arrayProductErroCheck)) {
	                        array_push($arrayProductErroCheck, $idProduct);
	                    }
	                }
	                
	                $urlUpdate = $ECCOSYS_URL.'/api/produtos/produtosAlterados/N';
	                $dataUpdate ="[".$idProduct."]";
	                $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));
	
	                if ($dataProductUpdateResult->httpcode != 200) {
	                    echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($dataProductUpdateResult) . "\n";
	                    $this->db->trans_rollback();
	                    $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID eccosys
	                    continue;
	                }
	
	                $this->log_data('batch', 
	                                $log_name, 
	                                "Produto atualizado!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), 
	                                "I");
	                $this->log_integration("Produto atualizado com sucesso!!! sku= {$skuProduct}",
	                                        "<ul> <li> Id Produto= {$idProduct} </li> <li> sku= {$skuProduct} </li>
	                                            <li> Nome = $product->nome </li> </ul>", 
	                                        "S");
	                echo "Produto ID_Eccosys={$idProduct}, SKU={$skuProduct} atualizado com sucesso\n";
	                $this->db->trans_commit();
					continue;
	            } else {
					// esse produto não está cadastrado. ignoro....
					echo "Produto não encontrado, não será possível atualizar o produto ID_eccosys={$idProduct}, SKU={$skuProduct}. Ignorando \n";                    
                    /*
                    $this->log_data('batch', $log_name, "Produto não encontrado, não será possível atualizar o produto ID_eccosys={$idProduct}, SKU={$skuProduct}", "E");
                    $this->log_integration("Erro para atualizar o produto SKU {$skuProduct}", "Não foi possível localizar o produto para atualizar: <ul><li>SKU: {$skuProduct}</li><li>ID eccosys: {$idProduct}</li></ul>", "E");
                    */
					$this->db->trans_rollback();
                    //tenta limpar a lista de produtos alterados retirando o produto que não esta no conecta
                    /* $urlUpdate = $ECCOSYS_URL.'/api/produtos/produtosAlterados/N';
                    $dataUpdate ="[".$idProduct."]";
                    $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT'))); 
					 * 
					 */                   
                    continue;
				}
			} else { // é uma variação
            	$idPaiEcosys = $product->idProdutoMaster;
				 // busca por variação
                $verifyProduct = $this->product->getVariationForSku($skuProduct);             
                
                if (empty($verifyProduct)) {
                	$verifyProduct = $this->product->getProductForIdErp($idPaiEcosys);
					if ($verifyProduct) { // Variação nova de um produto que já existe 
						if ($verifyProduct['status'] != 1) {
	                    	$this->db->trans_rollback();
	                        echo "Produto não está ativo\n";
	                        continue;
	                    } 
						$createVariation = $this->product->createVariation($product, $verifyProduct['sku']);
						if ($createVariation['success'] === false) {
							$msg = empty($createVariation['message'][0]) ? '' : $createVariation['message'][0];
	                        echo "Não foi possível criar a variação ID_eccosys={$idProduct} o produto {$idProdutoMaster} devido a um erro, retorno = " . $msg . "\n";
	                        $this->db->trans_rollback();
	                        // adiciono no array para não consultar mais esse produto
	                        if (!in_array($idProduct, $arrayProductErroCheck)) {
	                            array_push($arrayProductErroCheck, $idProduct);
	                        }
	                        continue;
	                    }
						
						$urlUpdate = $ECCOSYS_URL.'/api/produtos/produtosAlterados/N';
	                    $dataUpdate ="[".$idProduct."]";
	                    $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));
	
	                    if ($dataProductUpdateResult->httpcode != 200) {
	                        echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($dataProductUpdateResult) . "\n";
	                        $this->db->trans_rollback();
	                        continue;
	                    }
	                  
                        $this->db->trans_commit();
                        $this->log_data('batch', $log_name, "Variacao cadastrada para prd_id= ".$verifyProduct['id']."!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), "I");                        
                        echo "Variação ID_Eccosys={$idProduct}, SKU={$skuProduct} para produto ID_Eccosys={$idPaiEcosys} criado com sucesso\n";                        
						continue; 
					}
					else { // variação de um produto q não foi cadastrado, ignoro
						echo "Produto não encontrado, não será possível atualizar a variação ID_eccosys={$idProduct}, SKU={$skuProduct} pois não encontrei o produto pai {$idPaiEcosys}. Ignorando \n";                    
						$this->db->trans_rollback();
						continue;
					} 
				} else { // variação antiga 
                    if ($verifyProduct['status'] != 1) {
                    	$this->db->trans_rollback();
                        echo "Produto não está ativo\n";
                        continue;
                    }                  

                    $prdId = $verifyProduct['prd_id'];

                    $productUpdate = $this->product->updateVariation($product, $prdId);

                    if ($productUpdate['success'] === false) {
                    	$msg = empty($createVariation['message'][0]) ? '' : $createVariation['message'][0];
                        echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . $msg . "\n";
                        $this->db->trans_rollback();
                        // adiciono no array para não consultar mais esse produto pai
                        if (!in_array($idProduct, $arrayProductErroCheck)) {
                            array_push($arrayProductErroCheck, $idProduct);
                        }
                        continue;
                    }
                    if (is_null($productUpdate['success'])) {
                        echo "Produto {$idProduct} não precisa ser atualizado\n";  
						$this->db->trans_rollback();      
						continue;               
                    }

                    $urlUpdate = $ECCOSYS_URL.'/api/produtos/produtosAlterados/N';
                    $dataUpdate ="[".$idProduct."]";
                    $dataProductUpdateResult = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));

                    if ($dataProductUpdateResult->httpcode != 200) {
                        echo "Não foi possível atualizar o produto={$idProduct} encontrou um erro, retorno = " . json_encode($dataProductUpdateResult) . "\n";
                        $this->db->trans_rollback();
                        continue;
                    }
                    
                    $this->db->trans_commit();
                    $this->log_data('batch', $log_name, "Produto atualizado!!! payload=" . json_encode($product) . 'backup_payload_prod' . json_encode($verifyProduct), "I");                        
                    echo "Produto ID_Eccosys={$idProduct}, SKU={$skuProduct} atualizado com sucesso\n";                        
                   
                    continue;	
				}	
            }           

        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo "ocorreu um erro\n";
        }         

        $this->db->trans_commit();
    }
}
    
