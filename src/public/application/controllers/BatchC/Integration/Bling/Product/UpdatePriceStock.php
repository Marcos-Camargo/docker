<?php

/**
 * Class UpdateStock
 *
 * php index.php BatchC/Integration/Bling/Product/UpdatePriceStock run null 8
 *
 */

require APPPATH . "controllers/BatchC/Integration/Bling/Main.php";
require APPPATH . "controllers/BatchC/Integration/Bling/Product/Product.php";

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
        echo "Pegando produtos para Atualizar Preço e/ou Estoque  \n";

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
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "E");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        // começando a pegar os produtos para criar
        $url    = 'https://bling.com.br/Api/v2/produtos/page=1';
        $data   = "&loja={$this->multiStore}&estoque=S";
        $dataProducts = $this->sendREST($url, $data);

        if ($dataProducts['httpcode'] != 200) {
            echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
            $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
            //$this->log_integration("Erro para atualizar o estoque dos produtos", "Não foi possível consultar a listagem de produtos!!", "E");
            return false;
        }

        $contentProducts = json_decode($dataProducts['content']);

        if (isset($contentProducts->retorno->erros)) {
            // formatar mensagens de erro para log integration
            $arrErrors = array();
            $errors = $contentProducts->retorno->erros;
            if (!is_array($errors)) $errors = (array)$errors;
            foreach ($errors as $error) {
                $msgErrorIntegration = $error->erro->msg ?? "Erro desconhecido";
                array_push($arrErrors, $msgErrorIntegration);
            }
            //$this->log_integration("Erro para atualizar o estoque dos produtos", "<h4>Não foi possível consultar a listagem de produtos!</h4><ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
            return false;
        }

        $regProducts     = $contentProducts->retorno->produtos;
        $haveProductList = true;
        $page            = 1;
        $limiteRequestBlock = 3;
        $countlimiteRequestBlock = 1;
        $arrProductsStock = array();
		$arrPriceUpdate  = array();
		
		$products_bling = array();
        while ($haveProductList) {
            if ($page != 1) {
                $url    = 'https://bling.com.br/Api/v2/produtos/page='.$page;
                $data   = "&loja={$this->multiStore}&estoque=S";
                $dataProducts = $this->sendREST($url, $data);

                $contentProducts = json_decode($dataProducts['content']);

                if ($dataProducts['httpcode'] != 200) {
                    if ($dataProducts['httpcode'] == 504 || $dataProducts['httpcode'] == 401) continue;
                    if ($dataProducts['httpcode'] == 999 && $countlimiteRequestBlock <= $limiteRequestBlock) {
                        echo "aguardo 1 minuto para testar novamente. (Tentativas: {$countlimiteRequestBlock}/{$limiteRequestBlock})\n";
                        sleep(60);
                        $countlimiteRequestBlock++;
                        continue;
                    }

                    echo "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts) . "\n";
                    $this->log_data('batch', $log_name, "Erro para buscar a listagem de url={$url}, retorno=" . json_encode($dataProducts), "W");
                    //$this->log_integration("Erro para atualizar o estoque dos produtos", "Não foi possível consultar a listagem de produtos!", "E");
                    $haveProductList = false;
                    return false;
                }
                if (isset($contentProducts->retorno->erros[0]->erro->cod) && $contentProducts->retorno->erros[0]->erro->cod == 14) {
                    $haveProductList = false;
					echo "ACABOU\n";
                    continue;
                }

                $regProducts = $contentProducts->retorno->produtos;
            }

            foreach ($regProducts as $registro) {

                $registro = $registro->produto;

                // Produto não está na multiloja
                if (!isset($registro->produtoLoja)) {
                    echo "Produto {$registro->codigo} não está na multiloja\n";
                    continue;
                }
				
				$product_bling = array(
					'id'  		=> $registro->id
				);
				
                if (isset($registro->codigoPai)) { // é uma variação 
					$product_bling['skupai'] = $registro->codigoPai; 
					$product_bling['preco'] = $registro->produtoLoja->preco->precoPromocional == 0 ? $registro->produtoLoja->preco->preco : $registro->produtoLoja->preco->precoPromocional;
                	$product_bling['estoque'] = $this->product->getGeneralStock($registro->depositos ?? array(), $registro->estoqueAtual ?? 0);
                    $product_bling['tipo'] = 'V'; 
                }
				else if (isset($registro->variacoes)){  // é um produto pai 
					$product_bling['skupai'] = null;
					$product_bling['preco'] = 0;  // o preço será a maior das variações 
					$product_bling['estoque'] = 0; // o estoque será a soma de todas as variações 
					$product_bling['tipo'] = 'P';
				} 
				else { // é um produto simples 
					$product_bling['skupai'] = null; 
					$product_bling['preco'] = $registro->produtoLoja->preco->precoPromocional == 0 ? $registro->produtoLoja->preco->preco : $registro->produtoLoja->preco->precoPromocional;
					$product_bling['estoque'] = $this->product->getGeneralStock($registro->depositos ?? array(), $registro->estoqueAtual ?? 0);
					$product_bling['tipo'] = 'S';
				}
				
				$products_bling[$registro->codigo] = $product_bling;
			}
			$page++;
		}

		// agora acerto o estoque e preço do produto Pai
		foreach($products_bling as $product_bling) {
			if ($product_bling['tipo'] == 'V') {
				if (array_key_exists($product_bling['skupai'], $products_bling)) {
					$products_bling[$product_bling['skupai']]['estoque'] +=$product_bling['estoque']; 
					if ($products_bling[$product_bling['skupai']]['preco'] < $product_bling['preco']) {
						$products_bling[$product_bling['skupai']]['preco'] = $product_bling['preco'];
					}
				}
			}
		}
		
		// Agora, finalmente, eu atualizo o banco 
		foreach($products_bling as $skuProduct => $product_bling) {
			$this->setUniqueId($product_bling['id']);
			if ($product_bling['tipo'] == 'V') { // é uma variação
				$verifyProduct = $this->product->getProductForSku($product_bling['skupai']);
				$array_change = array(); 
				if (empty($verifyProduct)) { // Não achei pelo Id do Bling, então procuro pelo SKU 
					echo "Produto ".$product_bling['skupai']." não encontrado, não será possível atualizar a variação ID_BLING = ".$product_bling['id'].", SKU= ".$skuProduct." \n";
                    continue;
				}
				$variant = $this->model_products->getAllVariantByPrdIdAndIDErp($verifyProduct['id'], $product_bling['id']); 
				if (empty($variant)) {
					$variant = $this->model_products->getVariantsByProd_idAndSku($verifyProduct['id'], $skuProduct);
					if (empty($variant)) {
						echo "Variação não encontrada, não será possível atualizar a variação ID_BLING = ".$product_bling['id'].", SKU= ".$skuProduct." \n";
						continue;	
					}
					$array_change['variant_id_erp'] = $product_bling['id'];
				}
				
				$msg_stock1 = "Estoque da variação={$skuProduct} igual ao do banco, não será modificado\n";
				$msg_stock2 = null;
				
				$msg_price1 = "Variação {$skuProduct} com o mesmo preço, não será atualizado, preço={$product_bling['preco']}\n";;
				$msg_price2 = null;

				if ($variant['price'] != $product_bling['preco']) {
					if ((!$this->priceCatalogRO) || is_null($verifyProduct['product_catalog_id'])) {  // não pode alterar preço de produto de catalog se estiver com catalog_products_dont_modify_price setado
						$msg_price1 = "Preço da variação {$skuProduct} do produto {$product_bling['skupai']} atualizado";
						$msg_price2 = "<h4>O preço da variação {$skuProduct} foi atualizada com sucesso</h4><strong>Preço anterior:</strong> {$variant['price']} <br> <strong>Novo preço:</strong> {$product_bling['preco']}";
						$array_change['price'] = $product_bling['preco'];
					} 
				}
				if ($variant['qty'] != $product_bling['estoque']) {
					$msg_stock1 = "Estoque da variação {$skuProduct} do produto {$product_bling['skupai']} atualizado";
					$msg_stock2 = "<h4>O estoque da variação {$skuProduct} foi atualizada com sucesso.</h4><strong>Estoque anterior:</strong> {$variant['qty']}<br><strong>Estoque alterado:</strong> {$product_bling['estoque']}";
					$array_change['qty'] = $product_bling['estoque'];
				}
				if (!empty($array_change)) {
					$update = $this->model_products->updateProductVar($array_change, $variant['id'] , "Alterado Integração Bling" );
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
                $verifyProduct = $this->product->getProductForIdErp($product_bling['id']);
				$array_change = array(); 
				if (empty($verifyProduct)) { // Não achei pelo Id do Bling, então procuro pelo SKU 
                    // verifica se sku já existe
                    $verifyProduct = $this->product->getProductForSku($skuProduct);
                    // existe o sku na loja, mas não esá com o registro do id da bling
                    if (!empty($verifyProduct)) {
                        $array_change['product_id_erp'] = $product_bling['id'];
					}
					else {
						echo "Produto não encontrado, não será possível atualizar o produto ID_BLING = ".$product_bling['id'].", SKU= ".$skuProduct." \n";
                        continue;
					}
				}
				if ($skuProduct != $verifyProduct['sku']) {
                    echo "Produto encontrado pelo código bling, mas com o sku diferente do sku cadastrado ID_BLING={$product_bling['id']}, SKU={$skuProduct} \n";
                    $this->log_data('batch', $log_name, "Produto encontrado pelo código bling, mas com o sku diferente do sku cadastrado ID_BLING={$product_bling['id']}, SKU={$skuProduct}", "W");
                    $this->log_integration("Erro para atualizar o estoque ou preço do produto {$skuProduct}", "<h4>Produto recebido e encontrado pelo código Bling, mas com o sku diferente do sku cadastrado</h4> <strong>SKU Recebido</strong>: {$skuProduct}<br><strong>SKU Na Conecta Lá</strong>: {$verifyProduct['sku']}<br><strong>ID Bling<strong>: {$product_bling['id']}", "E");
                    continue;
                }
				
				$msg_stock1 = "Estoque do produto={$skuProduct} igual ao do banco, não será modificado\n";
				$msg_stock2 = null;
				
				$msg_price1 = "Produto {$skuProduct} com o mesmo preço, não será atualizado, preço={$product_bling['preco']}\n";;
				$msg_price2 = null;
				
				if ($verifyProduct['price'] != $product_bling['preco']) {
					if ((!$this->priceCatalogRO) || is_null($verifyProduct['product_catalog_id'])) { // não pode alterar preço de produto de catalog se estiver com catalog_products_dont_modify_price setado
						$msg_price1 = "Preço do produto {$skuProduct} atualizado";
						$msg_price2 = "<h4>O preço do produto {$skuProduct} foi atualizado com sucesso</h4><strong>Preço anterior</strong>:{$verifyProduct['price']} <br> <strong>Novo preço</strong>:{$product_bling['preco']}";
						$array_change['price'] = $product_bling['preco'];
					}
				}
				if ($verifyProduct['qty'] != $product_bling['estoque']) {
					$msg_stock1 = "Estoque do produto {$skuProduct} atualizado";
					$msg_stock2 = "<h4>O estoque do produto {$skuProduct} foi atualizado com sucesso.</h4><strong>Estoque anterior:</strong>".$verifyProduct['qty']."<br><strong>Estoque alterado:</strong> ".$product_bling['estoque'];
					$array_change['qty'] = $product_bling['estoque'];
				}
				if (!empty($array_change)) {
					$update = $this->model_products->update($array_change, $verifyProduct['id'] , "Alterado Integração Bling" );
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