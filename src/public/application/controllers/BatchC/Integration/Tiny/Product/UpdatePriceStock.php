<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Tiny/Product/UpdateStock run
 *
 */

require APPPATH . "controllers/BatchC/Integration/Tiny/Main.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

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

        $this->load->model('model_products');
		$this->load->model('model_settings');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);

        $this->setJob('UpdatePriceStock');
    }

	//php index.php BatchC/Integration/Tiny/Product/UpdatePriceStock run null store_id
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
        echo "Pegando produtos para alterar preço estoque \n";

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
     * Recupera a lista para cadastro do produto
     *
     * @return bool
     */
    public function getListProducts()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
        $data   = $this->listPrice ? "&idListaPreco={$this->listPrice}" : '';
        $dataProducts = json_decode($this->sendREST($url, $data));

        if ($dataProducts->retorno->status != "OK") {
            echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            if ($dataProducts->retorno->codigo_erro != 99) {
                $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                $this->log_integration("Erro para atualizar o estoque do produto", "Não foi possível consultar a listagem de produtos!", "E");
            }
            return false;
        }

        $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;

        $pages = $dataProducts->retorno->numero_paginas;
		$products_erp = array();
		
		//echo "Páginas ".$pages."\n";
        for ($page = 1; $page <= $pages; $page++) {
            if ($page != 1) {
                $url    = $this->listPrice ? 'https://api.tiny.com.br/api2/listas.precos.excecoes.php' : 'https://api.tiny.com.br/api2/produtos.pesquisa.php';
                $data = $this->listPrice ? "&idListaPreco={$this->listPrice}&pagina={$page}" : "&pagina={$page}";
                $dataProducts = json_decode($this->sendREST($url, $data));

                if ($dataProducts->retorno->status != "OK") {
                    echo "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    if ($dataProducts->retorno->codigo_erro != 99) {
                        $this->log_data('batch', $log_name, "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataProducts), "W");
                        $this->log_integration("Erro para atualizar preço e estoque do produto", "Não foi possível consultar a listagem de produtos!", "E");
                    }
                    continue;
                }

                $regListProducts = $this->listPrice ? $dataProducts->retorno->registros : $dataProducts->retorno->produtos;
            }

            foreach ($regListProducts as $registro) {
                // Inicia transação
                //$this->db->trans_begin();

                $id_produto = $this->listPrice ? $registro->registro->id_produto : $registro->produto->id;
				
                $this->setUniqueId($id_produto); // define novo unique_id

                $url    = "https://api.tiny.com.br/api2/produto.obter.php";
                $data   = "&id={$id_produto}";
                $dataProduct = json_decode($this->sendREST($url, $data));

                if ($dataProduct->retorno->status != "OK") {
                    echo "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=".json_encode($registro)." retorno=" . json_encode($dataProduct) . "\n";
                    //$this->db->trans_rollback();
                    if ($dataProduct->retorno->codigo_erro != 99) {
                        $this->log_data('batch', $log_name, "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($registro) . " retorno=" . json_encode($dataProduct), "W");
                        $this->log_integration("Erro para atualizar o estoque do produto ID Tiny {$id_produto}", "Não foi possível obter informações do produto! ID={$id_produto}", "E");
                    }
                    continue;
                }

                $product = $dataProduct->retorno->produto; // Dados produto/variação

                $tipoVariacao   = $product->tipoVariacao;
				
				// consultar estoque
		        $qtyVar = $this->product->getStock(array($id_produto));
		        
		        // Erro na consulta do estoque
		        if (!$qtyVar['success']) {
		        	$msg = "Não foi possível obter informações do produto PAI de uma variação! ID={$id_produto}";
		        	echo $msg."\n";
		        	$this->log_integration("Erro para atualizar o estoque do produto ID Tiny {$id_produto}", $msg, "E");
					continue;
		        }

				// preço do produto  
				$precoProd  = $this->listPrice ? $registro->registro->preco : $registro->produto->preco;
				$product_erp = array(
					'sku'  		=> $product->codigo, 
				);
				if ($product->tipoVariacao== "V") { // é uma variação 
					$product_erp['idpai'] = $product->idProdutoPai; 
					$product_erp['preco'] = $precoProd;
                	$product_erp['estoque'] = 0;
                    $product_erp['tipo'] = 'V'; 
                }
				else if ($product->tipoVariacao== "P"){  // é um produto pai 
					$product_erp['idpai'] = null;
					$product_erp['preco'] = $precoProd;;  // o preço será a maior das variações 
					$product_erp['estoque'] = 0; // o estoque será a soma de todas as variações 
					$product_erp['tipo'] = 'P';
				} 
				else { // é um produto simples 
					$product_erp['idpai'] = null; 
					$product_erp['preco'] = $precoProd;;
					$product_erp['estoque'] = 0;
					$product_erp['tipo'] = 'S';
				}
				
				$products_erp[$id_produto] = $product_erp;
			
            }
        }
		
		$products_erp_limpo = $products_erp;
		// agora pego o estoque de quem existe e removo os que não existem.
		foreach($products_erp as $idProduct => $product_erp) {
			$this->setUniqueId($idProduct);
			$skuProduct = $product_erp['sku'];
			if ($product_erp['tipo'] == 'V') { // é uma variação
				if (!key_exists('idpai', $product_erp))	{
					echo "Produto pai da variação ID_TINY = ".$idProduct.", SKU= ".$skuProduct." não encontrada, não será possível atualizar a variação \n";
					unset($products_erp_limpo[$idProduct]);
					continue;	
				}		
				$verifyProduct = $this->product->getProductForSku($products_erp[$product_erp['idpai']]['sku']);
				$array_change = array(); 
				if (empty($verifyProduct)) { 
					echo "Produto ".$products_erp[$product_erp['idpai']]['sku']." não encontrado, não será possível atualizar a variação ID_TINY = ".$idProduct.", SKU= ".$skuProduct." \n";
                    unset($products_erp_limpo[$idProduct]);
                    continue;
				}
				$variant = $this->model_products->getAllVariantByPrdIdAndIDErp($verifyProduct['id'], $idProduct); 
				if (empty($variant)) {
					$variant = $this->model_products->getVariantsByProd_idAndSku($verifyProduct['id'], $skuProduct);
					if (empty($variant)) {
						echo "Variação não encontrada, não será possível atualizar a variação ID_TINY = ".$idProduct.", SKU= ".$skuProduct." \n";
						unset($products_erp_limpo[$idProduct]);
						continue;	
					}
				}
				
				// gravo a variant para usar depois
				$products_erp_limpo[$idProduct]['variant_id'] = $variant['id']; 
				
				// consultar estoque
		        $qtyVar = $this->product->getStock(array($idProduct));
		        
		        // Erro na consulta do estoque
		        if (!$qtyVar['success']) {
		        	$msg = "Não foi possível obter informações de uma variação! ID={$id_produto}\n".$qtyVar['message'];
		        	echo $msg."\n";
		        	$this->log_integration("Erro para atualizar o estoque do produto ID Tiny {$id_produto}", $qtyVar['message'], "E");
					unset($products_erp_limpo[$idProduct]);
					continue;
		        }
				$products_erp_limpo[$idProduct]['estoque'] =$qtyVar['totalQty'];
			}
			else { // é pai ou simples 
				// Recupera o código do produto pai
                $verifyProduct = $this->product->getProductForIdErp($idProduct);
				if (empty($verifyProduct)) { // Não achei pelo Id do Tiny , então procuro pelo SKU 
                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);
                    // existe o sku na loja, mas não esá com o registro do id da tiny 
                    if (!empty($verifyProduct)) {
						// acerto o produto com o id dele 
						$update = $this->model_products->update(array('product_id_erp' => $idProduct), $verifyProduct['id'] , "Alterado Integração Tiny" );
					}
					else {
						echo "Produto não encontrado, não será possível atualizar o produto ID_TINY = ".$idProduct.", SKU= ".$skuProduct." \n";
                        unset($products_erp_limpo[$idProduct]);
                        continue;
					}
				}
				if ($skuProduct != $verifyProduct['sku']) {
                    echo "Produto encontrado pelo código Tiny, mas com o sku diferente do sku cadastrado ID_TINY={$idProduct}, SKU={$skuProduct} \n";
                    $this->log_data('batch', $log_name, "Produto encontrado pelo código Tiny, mas com o sku diferente do sku cadastrado ID_TINY={$idProduct}, SKU={$skuProduct}", "W");
                    $this->log_integration("Erro para atualizar o estoque ou preço do produto {$skuProduct}", "<h4>Produto recebido e encontrado pelo código Tiny, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$skuProduct}<br><strong>SKU Na Conecta Lá</strong>: {$verifyProduct['sku']}<br><strong>ID Tiny<strong>: {$idProduct}", "E");
                    unset($products_erp_limpo[$idProduct]);
                    continue;
                }
				// gravo o product para usar depois
				$products_erp_limpo[$idProduct]['product_id'] = $verifyProduct['id']; 
				if ($product_erp['tipo'] == "S") { // produto simples 
					// consultar estoque
			        $qtyVar = $this->product->getStock(array($idProduct));
			        
			        // Erro na consulta do estoque
			        if (!$qtyVar['success']) {
			        	$msg = "Não foi possível obter informações de estoque do produto ID={$id_produto}\n".$qtyVar['message'];
			        	echo $msg."\n";
			        	$this->log_integration("Erro para atualizar o estoque do produto ID Tiny {$id_produto}", $qtyVar['message'], "E");
						unset($products_erp_limpo[$idProduct]);
						continue;
			        }
			        
					$products_erp_limpo[$idProduct]['estoque'] = $qtyVar['totalQty'];
					
				}
			} 

		}

		// agora acerto o estoque e preço do produto Pai
		foreach($products_erp_limpo as $product_erp) {
			if ($product_erp['tipo'] == 'V') {
				if (array_key_exists($product_erp['idpai'], $products_erp)) {
					$products_erp_limpo[$product_erp['idpai']]['estoque'] += $product_erp['estoque']; 
					if ($products_erp_limpo[$product_erp['idpai']]['preco'] < $product_erp['preco']) {
						$products_erp_limpo[$product_erp['idpai']]['preco'] = $product_erp['preco'];
					}
				}
			}
		}

		// Agora, finalmente, eu atualizo o banco 
		foreach($products_erp_limpo as $idProduct => $product_erp) {
			$this->setUniqueId($idProduct);
			$skuProduct = $product_erp['sku'];
			if ($product_erp['tipo'] == 'V') { // é uma variação
				$variant = $this->model_products->getPrdVariant($product_erp['variant_id']); 
				$array_change = array(); 
				$msg_stock1 = "Estoque da variação={$skuProduct} igual ao do banco, não será atualizado, estoque={$product_erp['estoque']}";
				$msg_stock2 = null;
				
				$msg_price1 = "Variação {$skuProduct} com o mesmo preço, não será atualizado, preço={$product_erp['preco']}";;
				$msg_price2 = null;

				if ($variant['price'] != $product_erp['preco']) {
					if ((!$this->priceCatalogRO) || is_null($verifyProduct['product_catalog_id'])) {  // não pode alterar preço de produto de catalog se estiver com catalog_products_dont_modify_price setado
						$msg_price1 = "Preço da variação {$skuProduct} do produto {$products_erp_limpo[$product_erp['idpai']]['sku']} atualizado";
						$msg_price2 = "<h4>O preço da variação {$skuProduct} foi atualizada com sucesso</h4><strong>Preço anterior:</strong> {$variant['price']} <br> <strong>Novo preço:</strong> {$product_erp['preco']}";
						$array_change['price'] = $product_erp['preco'];
					} 
				}
				if ($variant['qty'] != $product_erp['estoque']) {
					$msg_stock1 = "Estoque da variação {$skuProduct} do produto {$products_erp_limpo[$product_erp['idpai']]['sku']} atualizado";
					$msg_stock2 = "<h4>O estoque da variação {$skuProduct} foi atualizada com sucesso.</h4><strong>Estoque anterior:</strong> {$variant['qty']}<br><strong>Estoque alterado:</strong> {$product_erp['estoque']}";
					$array_change['qty'] = $product_erp['estoque'];
				}
				if (!empty($array_change)) {
					$update = $this->model_products->updateProductVar($array_change, $variant['id'] , "Alterado Integração Tiny" );
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
                $verifyProduct = $this->model_products->getProductData(0,$product_erp['product_id']);
				$array_change = array(); 

				$msg_stock1 = "Estoque do produto={$skuProduct} igual ao no sistema, não será atualizado, estoque={$product_erp['estoque']}";
				$msg_stock2 = null;
				
				$msg_price1 = "Produto {$skuProduct} com o mesmo preço, não será atualizado, preço={$product_erp['preco']}";;
				$msg_price2 = null;
				
				if ($verifyProduct['price'] != $product_erp['preco']) {
					if ((!$this->priceCatalogRO) || is_null($verifyProduct['product_catalog_id'])) { // não pode alterar preço de produto de catalog se estiver com catalog_products_dont_modify_price setado
						$msg_price1 = "Preço do produto {$skuProduct} atualizado";
						$msg_price2 = "<h4>O preço do produto {$skuProduct} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$product_erp['preco']}";
						$array_change['price'] = $product_erp['preco'];
					}
				}
				if ($verifyProduct['qty'] != $product_erp['estoque']) {
					$msg_stock1 = "Estoque do produto {$skuProduct} atualizado";
					$msg_stock2 = "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong>".$verifyProduct['qty']."<br><strong>Estoque alterado:</strong> ".$product_erp['estoque'];
					$array_change['qty'] = $product_erp['estoque'];
				}
				if (!empty($array_change)) {
					$update = $this->model_products->update($array_change, $verifyProduct['id'] , "Alterado Integração Tiny" );
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
		echo "encerrou\n";


    }

   
}