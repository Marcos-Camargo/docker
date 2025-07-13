<?php

/**
 * Class UpdatePrice
 *
 * php index.php BatchC/Integration/Eccosys/Product/UpdatePriceStock run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Eccosys/Main.php";
require APPPATH . "controllers/BatchC/Integration/Eccosys/Product/Product.php";

class UpdatePriceStock extends Main
{
    private $product;
	
	var $priceCatalogRO = false;

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

        $this->load->model('model_settings');
		$this->load->model('model_products');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('UpdatePriceStock');
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
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando produtos para atualizar o preço \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

		//price RO para produtos de catalog 
		$this->priceCatalogRO = $this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');
		
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
     * Recupera a lista para atualização do produto/variação
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
        if($dataIntegrationStore){
            $credentials = json_decode($dataIntegrationStore['credentials']);
            $ECCOSYS_URL = $credentials->url_eccosys;
        }
        /* faz o que o job precisa fazer */        

        // começando a pegar os produtos para criar
        $url = $ECCOSYS_URL.'/api/produtos?$filter=opcEcommerce+eq+S';
        $data = '';
        $dataProducts = json_decode(json_encode($this->sendREST($url, $data)));

        if ($dataProducts->httpcode != 200) {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");                
          
            return false;
        }

        $regProducts = json_decode($dataProducts->content);      
       
		$products_erp = array();
        foreach ($regProducts as $registro) 
        {
        	$id_produto = $registro->id; 
    		$urlEsp = $ECCOSYS_URL."/api/produtos/$id_produto";
            $data = '';
            $dataProduct = json_decode(json_encode($this->sendREST($urlEsp, $data)));
            if ($dataProduct->httpcode != 200) {
				echo "Erro para buscar ao buscar o produto {$id_produto} em {$urlEsp}, retorno=" . json_encode($dataProducts)."\n";
	            $this->log_data('batch', $log_name, "Erro para buscar ao buscar o produto {$id_produto} em {$urlEsp}, retorno=" . json_encode($dataProducts), "W");                
                return;
            }

            $product = json_decode($dataProduct->content);
	
			if ($product->situacao == 'A'){  // está ativo
				$estoque = $product->_Estoque->estoqueDisponivel ? (int)$product->_Estoque->estoqueDisponivel : 0;	
			}
			else { // inativou 
				$estoque = 0; 
			}
			$preco = (float)number_format($product->preco, 2, ".", "");
		
			$product_erp = array(
				'sku'  		=> $product->codigo, 
			);
			if ($registro->idProdutoMaster != '0') { // é uma variação 
				$product_erp['idpai'] = $product->idProdutoMaster; 
				$product_erp['preco'] = $preco;
            	$product_erp['estoque'] = $estoque;
                $product_erp['tipo'] = 'V'; 
            }
			else{ // Pode ser um produto pai ou não 
				$product_erp['idpai'] = null; 
				$product_erp['preco'] = $preco;;
				$product_erp['estoque'] = $estoque;
				$product_erp['estoquepai'] = 0;
				$product_erp['tipo'] = 'S';  // mudará para P se for idpai de alguma variação
			}
			
			$products_erp[$id_produto] = $product_erp;	           
		}
		// agora acerto o estoque e preço do produto Pai
		foreach($products_erp as $product_erp) {
			if ($product_erp['tipo'] == 'V') {
				if (array_key_exists($product_erp['idpai'], $products_erp)) {
					$products_erp[$product_erp['idpai']]['tipo'] = 'P'; // produto que era Simples  vira pai 
					$products_erp[$product_erp['idpai']]['estoquepai'] +=$product_erp['estoque']; 
					$products_erp[$product_erp['idpai']]['estoque'] = $products_erp[$product_erp['idpai']]['estoquepai'];  
					if ($products_erp[$product_erp['idpai']]['preco'] < $product_erp['preco']) {
						$products_erp[$product_erp['idpai']]['preco'] = $product_erp['preco'];
					}
				}
			}
		}
		
		// Agora, finalmente, eu atualizo o banco 
		foreach($products_erp as $id_product => $product_erp) {
			$sku_product = $product_erp['sku'];
			$this->setUniqueId($id_product);
			if ($product_erp['tipo'] == 'V') { // é uma variação
				$skupai = $products_erp[$product_erp['idpai']]['sku'];
				$verifyProduct = $this->product->getProductForSku($skupai);
				$array_change = array(); 
				if (empty($verifyProduct)) { // Não achei pelo SKU 
					echo "Produto ".$skupai." não encontrado, não será possível atualizar a variação ID_ECCOSYS = ".$id_product.", SKU= ".$sku_product." \n";
                    continue;
				}
				$variant = $this->model_products->getAllVariantByPrdIdAndIDErp($verifyProduct['id'], $id_product); 
				if (empty($variant)) {
					$variant = $this->model_products->getVariantsByProd_idAndSku($verifyProduct['id'], $sku_product);
					if (empty($variant)) {
						echo "Variação não encontrada, não será possível atualizar a variação ID_ECCOSYS = ".$id_product.", SKU= ".$sku_product." \n";
						continue;	
					}
					$array_change['variant_id_erp'] = $id_product;
				}
				
				$msg_stock1 = "Estoque da variação={$sku_product} igual ao do banco, não será modificado\n";
				$msg_stock2 = null;
				
				$msg_price1 = "Variação {$sku_product} com o mesmo preço, não será atualizado, preço={$product_erp['preco']}\n";;
				$msg_price2 = null;

				if ($variant['price'] != $product_erp['preco']) {
					if ((!$this->priceCatalogRO) || is_null($verifyProduct['product_catalog_id'])) {  // não pode alterar preço de produto de catalog se estiver com catalog_products_dont_modify_price setado
						$msg_price1 = "Preço da variação {$sku_product} do produto {$skupai} atualizado";
						$msg_price2 = "<h4>O preço da variação {$sku_product} foi atualizada com sucesso</h4><strong>Preço anterior:</strong> {$variant['price']} <br> <strong>Novo preço:</strong> {$product_erp['preco']}";
						$array_change['price'] = $product_erp['preco'];
					} 
				}
				if ($variant['qty'] != $product_erp['estoque']) {
					$msg_stock1 = "Estoque da variação {$sku_product} do produto {$skupai} atualizado";
					$msg_stock2 = "<h4>O estoque da variação {$sku_product} foi atualizada com sucesso.</h4><strong>Estoque anterior:</strong> {$variant['qty']}<br><strong>Estoque alterado:</strong> {$product_erp['estoque']}";
					$array_change['qty'] = $product_erp['estoque'];
				}
				if (!empty($array_change)) {
					$update = $this->model_products->updateProductVar($array_change, $variant['id'] , "Alterado Integração Eccosys" );
					if ($update) {
						echo $msg_stock1."\n";
						if (!is_null($msg_stock2)) {
							$this->log_integration($msg_stock1, $msg_stock2 , "S");
							$this->log_data('batch', $log_name, $msg_stock2, "I");
						}
						echo $msg_price1."\n";
						if (!is_null($msg_price2)) {
							$this->log_integration($msg_price1, $msg_price2 , "S");
							$this->log_data('batch', $log_name, $msg_price2, "I");
						}						
					}
					else {
						$erro = "Ocorreu um problema para atualizar o estoque ou preco da variaçao ".$variant['id']." produto ".$variant['prd_id']."Dados: ".print_r($array_change,true);
						echo $erro."\n";
						$this->log_data('batch', $log_name, $erro, "E");
						die; 
					} 
				}
				else { // não precisou atualizar mostra as mensagens para ficar no log 
					echo $msg_stock1."\n";
					echo $msg_price1."\n";
				} 
			}
			else { // é o produto pai ou simples 
				// Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($id_product);
				$array_change = array(); 
				if (empty($verifyProduct)) { // Não achei pelo Id da eccosys, então procuro pelo SKU 
                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($sku_product);
                    // existe o sku na loja, mas não esá com o registro do id da eccosys
                    if (!empty($verifyProduct)) {
                        $array_change['product_id_erp'] = $id_product;
					}
					else {
						echo "Produto não encontrado, não será possível atualizar o produto ID_ECCOSYS = ".$id_product.", SKU= ".$sku_product." \n";
                        continue;
					}
				}
				if ($sku_product != $verifyProduct['sku']) {
                    echo "Produto encontrado pelo código Eccosys, mas com o sku diferente do sku cadastrado ID_ECCOSYS={$id_product}, SKU={$sku_product} \n";
                    $this->log_data('batch', $log_name, "Produto encontrado pelo código Eccosys, mas com o sku diferente do sku cadastrado ID_ECCOSYS={$id_product}, SKU={$sku_product}", "W");
                    $this->log_integration("Erro para atualizar o estoque ou preço do produto {$sku_product}", "<h4>Produto recebido e encontrado pelo código ECCOSYS, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$sku_product}<br><strong>SKU no Sistema</strong>: {$verifyProduct['sku']}<br><strong>ID Eccosys<strong>: {$id_product}", "E");
                    continue;
                }
				
				$msg_stock1 = "Estoque do produto={$sku_product} igual ao do banco, não será modificado\n";
				$msg_stock2 = null;
				
				$msg_price1 = "Produto {$sku_product} com o mesmo preço, não será atualizado, preço={$product_erp['preco']}\n";;
				$msg_price2 = null;
				
				if ($verifyProduct['price'] != $product_erp['preco']) {
					if ((!$this->priceCatalogRO) || is_null($verifyProduct['product_catalog_id'])) { // não pode alterar preço de produto de catalog se estiver com catalog_products_dont_modify_price setado
						$msg_price1 = "Preço do produto {$sku_product} atualizado";
						$msg_price2 = "<h4>O preço do produto {$sku_product} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$product_erp['preco']}";
						$array_change['price'] = $product_erp['preco'];
					}
				}
				if ($verifyProduct['qty'] != $product_erp['estoque']) {
					$msg_stock1 = "Estoque do produto {$sku_product} atualizado";
					$msg_stock2 = "<h4>O estoque do produto {$sku_product} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong>".$verifyProduct['qty']."<br><strong>Estoque alterado:</strong> ".$product_erp['estoque'];
					$array_change['qty'] = $product_erp['estoque'];
				}
				if (!empty($array_change)) {
					$update = $this->model_products->update($array_change, $verifyProduct['id'] , "Alterado Integração Eccosys" );
					if ($update) {
						echo $msg_stock1."\n";
						if (!is_null($msg_stock2)) {
							$this->log_integration($msg_stock1, $msg_stock2 , "S");
							$this->log_data('batch', $log_name, $msg_stock2, "I");
						}
						echo $msg_price1."\n";
						if (!is_null($msg_price2)) {
							$this->log_integration($msg_price1, $msg_price2 , "S");
							$this->log_data('batch', $log_name, $msg_price2, "I");
						}
					}
					else {
						$erro = "Ocorreu um problema para atualizar o estoque ou preco do produto ".$verifyProduct['id']." Dados: ".print_r($array_change,true);
						echo $erro."\n";
						$this->log_data('batch', $log_name, $erro, "E");
						die; 
					} 
				}
				else { // não precisou atualizar mostra as mensagens para ficar no log 
					echo $msg_stock1."\n";
					echo $msg_price1."\n";
				} 
			}
		}
		
    }

	
}