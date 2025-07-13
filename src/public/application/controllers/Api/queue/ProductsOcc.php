<?php
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */

use App\Libraries\FeatureFlag\FeatureManager;
use phpDocumentor\Reflection\Types\Array_;

require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

/**
 * @property Model_prd_addon $model_prd_addon
 * @property Bucket $bucket
 */
class ProductsOcc extends ProductsConectala
{

	var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal;
	var $empty_ean = false;
	var $integration;
	var $statusOcc = 'enabled';
	var $bling_ult_envio = null;
	var $adlink = null;   // http da marketplace
	var $occ_ref_sku_refid = null;
	var $variacoes_default = null;
	var $promo = false; // valida se tem campanha ou promo para enviar preço do sku ou produto
	const COLOR_DEFAULT = 'Cor';
	const SIZE_DEFAULT = 'TAMANHO';
	const VOLTAGE_DEFAULT = 'VOLTAGEM';
	const FLAVOR_DEFAULT = 'SABOR';
	const DEGREE_DEFAULT = 'GRAU';
	const SIDE_DEFAULT = 'LADO';

	public function __construct()
	{
		parent::__construct();


		$this->load->model('model_occ_last_post');
		$this->load->model('model_brands');
		$this->load->model('model_category');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_brands_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_marketplace_prd_variants');
		$this->load->model('model_collections');
		$this->load->model('model_settings');
        $this->load->model('model_prd_addon');
		$this->load->library('Bucket');

		$this->catalogId = $this->model_settings->getValueIfAtiveByName('occ_catalog');
		$this->occ_price_default = $this->model_settings->getValueIfAtiveByName('occ_price_default');
		$this->variacoes_default = $this->model_settings->getValueIfAtiveByName('variacao_valor_default');
		$this->occ_ref_sku_refid = $this->model_settings->getValueIfAtiveByName('occ_ref_sku_refid');

		$this->variant_color  = $this->model_settings->getValueIfAtiveByName('variant_color_attribute');
		if (!$this->variant_color) {  $this->variant_color = self::COLOR_DEFAULT; }

		$this->variant_size  = $this->model_settings->getValueIfAtiveByName('variant_size_attribute');
		if (!$this->variant_size) {  $this->variant_size = self::SIZE_DEFAULT; }

		$this->variant_voltage  = $this->model_settings->getValueIfAtiveByName('variant_voltage_attribute');
		if (!$this->variant_voltage) {  $this->variant_voltage = self::VOLTAGE_DEFAULT; }

		$this->variant_flavor  = $this->model_settings->getValueIfAtiveByName('variant_flavor_attribute');
		if (!$this->variant_flavor) {  $this->variant_flavor = self::FLAVOR_DEFAULT; }
		
		$this->variant_degree  = $this->model_settings->getValueIfAtiveByName('variant_degree_attribute');
		if (!$this->variant_degree) {  $this->variant_degree = self::DEGREE_DEFAULT; }
		
		$this->variant_side  = $this->model_settings->getValueIfAtiveByName('variant_side_attribute');
		if (!$this->variant_side) {  $this->variant_side = self::SIDE_DEFAULT; }

	}

	public function index_post()
	{
		$this->inicio = microtime(true);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();

		// verifico se é cadastrar, inativar ou alterar o produto
		$this->checkAndProcessProduct();

		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		$fim = microtime(true);
		echo "\nExecutou em: " . ($fim - $this->inicio) * 1000 . " ms\n";
		return;
	}

	public function checkAndProcessProduct()
	{

		$this->getkeys();
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}

	function insertProduct()
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo "Insert" . "\n";

		$sku = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		$this->prd_to_integration['skubling'] = $sku;

		$skumkt = $sku;
		// limpa os erros de transformação existentes da fase de preparação para envio

		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'], $this->int_to);

		$url = '/ccadmin/v1/products/' . $sku . '?fields=id,active';
		$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Product Insert');

		if ($this->responseCode == 200) { // O produto já existe na occ
			return $this->updateProduct();
		}

		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku) == false) {
			return false;
		};

		// Monto o Array para enviar para a OCC
		$produto = $this->montaArray($sku, true, 0);
		if ($produto == false) {
			return false;
		}
		echo 'Incluindo o produto ' . $this->prd['id'] . ' ' . $this->prd['name'] . "\n";

		// envio o produto para a OCC
		$url = '/ccadmin/v1/products';
		$retorno = $this->process($url, 'POST', json_encode($produto), $this->prd['id'], $this->int_to, 'Criando Produto');

		if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
			echo ' Erro URL: ' . $url . "\n";
			echo ' httpcode: ' . $this->responseCode . "\n";
			echo " RESPOSTA: " . print_r($this->result, true) . " \n";
			echo ' ENVIADO : ' . print_r($produto, true) . " \n";
			$this->log_data('batch', $log_name, 'ERRO no post produto site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r($produto, true), 'E');
			$this->errorTransformation($this->prd['id'], $skumkt, $this->result, 'Erro ao cadastrar produto');
			return false;
		}
		// Subo as imagens para OCC
		if($this->prd['is_on_bucket']){
			$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, '', false);
		}else{
			$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		}		
		if (is_array($productImages)) {
			// Atualizo o Produto com as imagens
			$images = array('productImages' => $productImages);
			$url = '/ccadmin/v1/products/' . $sku;
			$retorno = $this->process($url, 'PUT', json_encode($images), $this->prd['id'], $this->int_to, 'Adicionando imagem ao Produto');
		}
		// else {
		// 	$msg = 'Produto sem Imagem.';
		// 	echo 'Produto ' . $this->prd['id'] . ' ' . $msg . "\n";
		// 	$this->errorTransformation($this->prd['id'], $skumkt, $msg, 'Preparação para o envio');
		// 	return false;
		// }

		//agora envio os skus
        $variations_successfully = array();
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) == 0) {
				$erro = "As variações deste produto " . $this->prd['id'] . " sumiram.";
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, "E");
				die;
			}
			foreach ($this->variants as $variant) {
                if ($variant['status'] != 1) {
                    $this->disableProductVariant(null, $variant);
                } else {
                    if ($this->insertProductVariant($variant)) {
                        $variations_successfully[] = $variant['variant'];
                    }
                }
			}
		} else {
			if ($this->insertProductVariant(null) == false) {
				return false;
			}
		}

		if (count($this->collections) > 1) {
			$collections = array(
				"collections" => $this->collections
			);
			$url = '/ccadmin/v1/products/' . $sku . '/addToCollections';
			$retorno = $this->process($url, 'POST', json_encode($collections), $this->prd['id'], $this->int_to, 'Adicionando Collection');
		}

		$this->prd_to_integration['skubling'] = $sku;
		$this->prd_to_integration['skumkt'] = $skumkt;
		if ($this->prd['has_variants'] != '') {
			foreach ($this->variants as $variant) {
                if (in_array($variant['variant'], $variations_successfully)) {
                    $prd = $this->setDataVariantToUpdate($variant);
                    $this->updateOccLastPost($prd, $variant);
                }
			}
		} else {
			$this->updateOccLastPost($this->prd, null);
		}
		return true;
	}

	function updateProduct()
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo 'Update' . "\n";
		//verificar se a imagem existe
		$sku = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		$this->prd_to_integration['skubling'] = $sku;
		$skumkt = $sku;

		//retirando o get para diminuir o número de requisições
		$url = '/ccadmin/v1/products/' . $sku . '?fields=id,active,productImages';
		$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Product Update');

		if ($this->responseCode != 200) { // O produto já não existe na occ
			$this->insertProduct();
		}
		$product_get = json_decode($this->result);
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'], $this->int_to);


		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku) == false) {
			return false;
		};

		// atualização de imagens
		$get_product = json_decode($this->result);
		$images = $get_product->productImages;
		// if (!$images) {
		// 	if($this->prd['is_on_bucket']){
		// 		$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, '', false);
		// 	}else{
		// 		$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		// 	}	
		// 	if (is_array($productImages)) {
		// 		// Atualizo o Produto com as imagens
		// 		echo "atualizando as imagens do produto \n";
		// 		$images = array('productImages' => $productImages);
		// 		$url = '/ccadmin/v1/products/' . $sku;
		// 		$retorno = $this->process($url, 'PUT', json_encode($images), $this->prd['id'], $this->int_to, 'Adicionando imagem ao Produto');
		// 	}
		// }
		
		//atualizo as collections
		// $collections = array(
		// 	"collections" => $this->collections
		// );
		// $url = '/ccadmin/v1/products/' . $sku . '/addToCollections';
		// $retorno = $this->process($url, 'POST', json_encode($collections), $this->prd['id'], $this->int_to, 'Adicionando Collection');

		//atualizar os add-ons
		if (FeatureManager::isFeatureAvailable('OEP-1957-update-delete-publica-addon-occ')) {
			$products_add_on = $this->model_prd_addon->getAddonData($this->prd['id']);

			// https://docs.oracle.com/en/cloud/saas/cx-commerce/21d/cxocc/op-ccadmin-v1-products-id-get.html
			if (!empty($products_add_on)) {
				// Verifica se precisa enviar os novos Add-Ons
				$every_add_on_are_equals = true;
				if (!empty($product_get->addOnProducts[0]->addOnOptions)) {
					foreach ($product_get->addOnProducts[0]->addOnOptions as $addOnOption) {
						$repositoryId = $addOnOption->sku->repositoryId;
						if (empty(array_filter($products_add_on, function ($product_add_on) use ($repositoryId) {
							return $product_add_on['skumkt'] == $repositoryId;
						}))) {
							$every_add_on_are_equals = false;
							break;
						}
					}

					if ($every_add_on_are_equals) {
						foreach ($products_add_on as $product_add_on) {
							$skumkt_addon = $product_add_on['skumkt'];
							if (empty(array_filter($product_get->addOnProducts[0]->addOnOptions, function ($addOnOption) use ($skumkt_addon) {
								return $addOnOption->sku->repositoryId == $skumkt_addon;
							}))) {
								$every_add_on_are_equals = false;
								break;
							}
						}
					}
				}
				// Ainda não existe Add-On.
				else {
					$every_add_on_are_equals = false;
				}

				// Precisa enviar os Add-On
				if (!$every_add_on_are_equals) {
					$addOnOptions = array_map(function ($product_add_on) use ($sku) {
						return array(
							"addOnOptions" => array(
								array(
									"product"   => 'P' . $product_add_on['prd_id'] . 'S' . $this->prd['store_id'] . $this->int_to,
									"sku"       => $product_add_on['skumkt']
								)
							)
						);
					}, $products_add_on);

					$addOnBody = array(
						"properties" => array(
							"addOnProducts" => $addOnOptions
						)
					);
					$url_put_add_on = "/ccadminui/v1/products/$sku?includePrices=false";
					$this->process($url_put_add_on, 'PUT', json_encode($addOnBody), $this->prd['id'], $this->int_to, 'Adicionando Add-On');
					$resultAddon = json_decode($this->result);

					if ($this->responseCode != 200) {
						preg_match_all('/P(\d+)/', $resultAddon->message, $matches);
						$idsResultAddon = $matches[1];
						foreach ($idsResultAddon as $id) {
							$prdExist = null;
							foreach ($addOnOptions as $value) {
								if (preg_match('/P' . $id . '/', $value['addOnOptions'][0]['product'])) {
									$prdExist = $value['addOnOptions'][0]['sku'];
									break;
								}
							}

							if ($prdExist) {
								$this->errorTransformation($id, $prdExist, $resultAddon->message, "Erro para adicionar Add-On no sku $sku.");
							}
						}
						echo "Erro para adicionar Add-On no sku $sku. " . json_encode($addOnBody) . " - $this->result\n";
					}
				}
			} else {
				// Remover o Add-On.
				if (empty($product_get->addOnProducts[0]->addOnOptions)) {
					$addOnBody = array(
						"properties" => array(
							"addOnProducts" => array()
						)
					);
					$url_put_add_on = "/ccadminui/v1/products/$sku?includePrices=false";
					$this->process($url_put_add_on, 'PUT', json_encode($addOnBody), $this->prd['id'], $this->int_to, 'Removendo Add-On');

					if ($this->responseCode != 200) {
						echo "Erro para remover Add-On no sku $sku. $this->result\n";
					}
				}
			}
		}
        
        $prd_to_integration_var = array();
        $variations_successfully = array();
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) == 0) {
				$erro = "As variações deste produto " . $this->prd['id'] . " sumiram.";
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, "E");
				die;
			}
			foreach ($this->variants as $variant) {
                $prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
                $prd_to_integration_var[$variant['variant']] = $prd_to_integration;
                if ($variant['status'] != 1) {
                    $this->disableProductVariant($prd_to_integration, $variant);
                } else {
                    if ($this->updateProductVariant($variant, $skumkt)) {
                        $variations_successfully[] = $variant['variant'];
                    }
                }
			}
		} else {
			if ($this->updateProductVariant(null, $skumkt) == false) {
				return false;
			}
		}

		if(!$product_get->active){
			$produto = array(
				'catalogId'		=> $this->catalogId,
				'properties'    => [
					'active' 			=> true,
				]
			);
			$url = '/ccadmin/v1/products/' . 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
			$retorno = $this->process($url, 'PUT', json_encode($produto), $this->prd['id'], $this->int_to, 'Mantendo Produto Ativo');
		}

		$this->prd_to_integration['skumkt'] = $skumkt;
		$this->prd_to_integration['skubling'] = $sku;

		if ($this->prd['has_variants'] != '') {
            foreach ($this->variants as $variant) {
                if (in_array($variant['variant'], $variations_successfully)) {
                    $prd = $this->setDataVariantToUpdate($variant);
                    $this->updateOccLastPost($prd, $variant);
                }
			}
		} else {
			$this->updateOccLastPost($this->prd, null);
		}
		return true;
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo "Inativando\n";
		$sku = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'], $this->int_to);
		$url = '/ccadmin/v1/products/' . $sku . '?fields=id,active';
		$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Product Inactivate');

		if ($this->responseCode != 200) { // O produto já existe na occ
			echo "Produto não cadastrado na occ\n";
			return false;
		}

		if ($this->prepareProduct($sku) == false) {
			return false;
		};
		$disable = true;
		$this->prd['qty'] = 0; // zero a quantidade do produto

		$get_product = json_decode($this->result);
		// $images = $get_product->fullImageURLs;
		// $ImagesName = [];
		// if ($images) {
		// 	foreach ($images as $image) {
		// 		$name = explode('products/', $image);
		// 		array_push($ImagesName, $name[1]);
		// 	}
		// }

		if ($disable) {
			$this->statusOcc = 'disabled';
		}

		if ($disable) {
			// $productImages = $this->getProductImages($this->prd['image'], $this->pathImage, '', false, $ImagesName);
			// if (is_array($productImages)) {
			// 	// Atualizo o Produto com as imagens
			// 	$images = array('productImages' => $productImages);
			// }
			// $produto = array(
			// 	'catalogId'		=> $this->catalogId,
			// 	'properties'    => [
			// 		'active' 			=> false,
			// 	]
			// );
			// if(is_array($images)){
			// 	$produto = array_merge($produto,$images);
			// }
			$url = '/ccadmin/v1/products/' . 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
			$retorno = $this->process($url, 'PUT', json_encode($produto), $this->prd['id'], $this->int_to, 'Inativando Produto');
			if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
				echo ' Erro URL: ' . $url . "\n";
				echo ' httpcode: ' . $this->responseCode . "\n";
				echo " RESPOSTA: " . print_r($this->result, true) . " \n";
				echo ' ENVIADO : ' . print_r($produto, true) . " \n";
				$this->log_data('batch', $log_name, 'ERRO ao inativar produto:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r($produto, true), 'E');
				die;
				return false;
			}
		}
		
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) == 0) {
				$erro = 'As variações deste produto ' . $this->prd['id'] . ' sumiram.';
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, 'E');
				$disable = true; // melhor dar disable  
			}

            $variants = $this->variants;
            if (!is_null($variant)) {
                $variants = array($variant);
            }

            foreach($variants as $variant) {
				$variant['qty'] = 0;  // zero a quantidade da variant tb
				$this->updateProductVariant($variant, $sku);
				$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$prd_to_integration['id']);
			}
		}else{
			$this->prd['qty'] = 0;
			$this->updateProductVariant(null, $sku);
			$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
		}

		if ($this->prd['has_variants'] != '') {
            foreach ($this->variants as $variant) {
                $prd = $this->setDataVariantToUpdate($variant);
				$this->updateOccLastPost($prd, $variant);
			}
		} else {
			$this->updateOccLastPost($this->prd, null);
		}

	}

	function insertProductVariant($variant = null)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$skus = $this->montaArraySku($variant, $variant);
		if ($skus == false) {
			return false;
		};

		echo 'Incluindo os skus ' . $this->prd['id'] . ' ' . $this->prd['name'] . "\n";
		//consulta o sku
		$url = '/ccadmin/v1/skus/' . $skus['variants'][0]['id'] .'?fields=repositoryId' ;
		$this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Sku Insert');
		if ($this->responseCode == 200) {
			//sku já cadastrado
			return $this->updateProductVariant($variant, $this->prd_to_integration['skubling']);
		}

		// Envia o sku	
		$url = '/ccadmin/v1/skus';
		$this->process($url, 'PUT', json_encode($skus), $this->prd['id'], $this->int_to, 'Criando Sku');
		if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
			echo ' Erro URL: ' . $url . "\n";
			echo ' httpcode: ' . $this->responseCode . "\n";
			echo " RESPOSTA: " . print_r($this->result, true) . " \n";
			echo ' ENVIADO : ' . print_r($skus, true) . " \n";
			$msgErr = "Erro ao inserir o sku";
			$error = json_decode($this->result);
			if(isset($error->message)){
				$msgErr = $error->message;
			}
			$this->errorTransformation($this->prd['id'], $skus['variants'][0]['id'], $msgErr, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
			$this->log_data('batch', $log_name, 'ERRO no post sku site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r($skus, true), 'E');
			// die; 
			return false;
		}
		if (!is_null($variant)) {
			// Envia o Estoque
			 $skuId = $skus['variants'][0]['id'];
			$url = '/ccadmin/v1/inventories/' . $skuId;
			$this->process($url, 'PUT', json_encode(array('stockLevel' => $variant['qty'])), $this->prd['id'], $this->int_to, 'Adicionando Estoque');

			//Envia Imagem do sku
			if($this->prd['is_on_bucket']){
				$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, $variant['image'], $skus['variants'][0]['id']);
			}else{
				$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, $variant['image'], $skus['variants'][0]['id']);
			}	
			if (!empty($productImages)) {
				$images = array('images' => $productImages);
				$url = '/ccadmin/v1/skus/' . $skuId;
				$this->process($url, 'PUT', json_encode($images), $this->prd['id'], $this->int_to, 'Adicionando Imagem do Sku');
			}

			//atualiza preço
			$url = '/ccadminui/v1/prices';
			if(!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']){
				$variant['list_price'] = $variant['price'] + 0.01;
			}
			$priceCampaign = $variant['price'];
			if($this->promo){
				$priceCampaign = $this->prd['promotional_price'];
			}
			
			$price = array(
				'items' => array(
					(object)[
						'derivedListPriceFrom' => $this->occ_price_default,
						'isListPriceInherited' => false,
						'isListVolumePriceInherited' => false,
						'listPrice' => $variant['list_price'],
						'priceListId' => $this->occ_price_default . '_listPrices',
						'productId' => $this->prd_to_integration['skubling'],
						'skuId' => $skuId,

					],
					(object)[
						'derivedListPriceFrom' => $this->occ_price_default,
						'isListPriceInherited' => false,
						'isListVolumePriceInherited' => false,
						'listPrice' => $priceCampaign,
						'priceListId' => $this->occ_price_default . '_salePrices',
						'productId' => $this->prd_to_integration['skubling'],
						'skuId' => $skuId,

					]
				)
			);

			$this->process($url, 'PUT', json_encode($price), $this->prd['id'], $this->int_to, 'Adicionando preço ao Sku');
			$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
			if($prd_to_integration){
				$this->model_integrations->updatePrdToIntegration(
					array(
						'skumkt' 		=> $skuId,
						'skubling' 		=> $skuId,
						'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
						'date_last_int' => $this->dateLastInt,
						'mkt_product_id' => $skuId,
						'mkt_sku_id' => $skuId
					),
					$prd_to_integration['id']
				);
			}else{
				//adicionou novas variações
				$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, null);
				if($prd_to_integration){
					$this->model_integrations->updatePrdToIntegration(
						array(
							'skumkt' 		=> $skuId,
							'skubling' 		=> $skuId,
							'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
							'date_last_int' => $this->dateLastInt,
							'variant' => $variant['variant'],
							'mkt_product_id' => $skuId,
							'mkt_sku_id' => $skuId
						),
						$prd_to_integration['id']
					);
				}else{
					//adicionou novas variações
					$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to,$this->prd['id']);			
					$this->model_integrations->createPrdToIntegration(
					array(
							'prd_id'             => $this->prd['id'],
							'company_id'         => $this->prd['company_id'],
							'status'             => 1,
							'status_int'         => ($this->prd['qty'] == 0) ? 10 : 2,
							'int_to'             => $this->int_to,
							'skumkt'             => $skuId,
							'skubling'           => $skuId,
							'store_id'           => $this->prd['store_id'],
							'approved'           => $prd_to_integration['approved'],
							'int_id'             => $prd_to_integration['int_id'],
							'user_id'            => $prd_to_integration['user_id'],
							'int_type'           => $prd_to_integration['int_type'],
							'variant'            => $variant['variant'],
							'date_last_int' => $this->dateLastInt,
						)
					);
				}

			}

		} else {
			// Envia o Estoque
			$skuId = $skus['variants'][0]['id'];
			$url = '/ccadmin/v1/inventories/' . $skuId;
			$this->process($url, 'PUT', json_encode(array('stockLevel' => $this->prd['qty'])), $this->prd['id'], $this->int_to, 'Adicionando Estoque');

			$url = '/ccadminui/v1/prices';
			if(!isset($this->prd['list_price']) || $this->prd['list_price'] == null || $this->prd['list_price'] <= $this->prd['price']){
				$this->prd['list_price'] =  $this->prd['price'] + 0.01;
			}
			$price = array(
				'items' => array(
					(object)[
						'derivedListPriceFrom' => $this->occ_price_default,
						'isListPriceInherited' => false,
						'isListVolumePriceInherited' => false,
						'listPrice' => $this->prd['list_price'],
						'priceListId' => $this->occ_price_default . '_listPrices',
						'productId' => $this->prd_to_integration['skubling'],
						'skuId' => $skuId,

					],
					(object)[
						'derivedListPriceFrom' => $this->occ_price_default,
						'isListPriceInherited' => false,
						'isListVolumePriceInherited' => false,
						'listPrice' => $this->prd['promotional_price'],
						'priceListId' => $this->occ_price_default . '_salePrices',
						'productId' => $this->prd_to_integration['skubling'],
						'skuId' => $skuId,

					]
				)
			);

			$this->process($url, 'PUT', json_encode($price), $this->prd['id'], $this->int_to, 'Adicionando preço ao Sku');			
			$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, (isset($variant['variant']) ? $variant['variant'] : null));
			if($prd_to_integration){
				$this->model_integrations->updatePrdToIntegration(
					array(
						'skumkt' 		=> $skuId,
						'skubling' 		=> $skuId,
						'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
						'date_last_int' => $this->dateLastInt,
						'mkt_product_id' => $skuId,
						'mkt_sku_id' => $skuId
					),
					$prd_to_integration['id']
				);
			}else{
				$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to,$this->prd['id']);
				$this->model_integrations->updatePrdToIntegration(
					array(
						'skumkt' 		=> $skuId,
						'skubling' 		=> $skuId,
						'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
						'date_last_int' => $this->dateLastInt,
						'variant'       => null,
						'mkt_product_id' => $skuId,
						'mkt_sku_id' => $skuId
					),
					$prd_to_integration['id']
				);

			}

		}
		return true;
	}

	function updateProductVariant($variant = null, $skumkt)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$skus = $this->montaArraySku($variant, true);
		if ($skus == false) {
			return false;
		};
		$updateStock = true;
		$updatePrice = true;
		if (!is_null($variant)) {
			// Envia o Estoque
			$skuId = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'V' . $variant['variant'];
			$skuLastPost = $this->model_occ_last_post->getBySku($skuId);
            // SENTRY ID: 487, 486
            if ($skuLastPost) {
                if($skuLastPost['qty'] == $variant['qty']){
                    echo "não precisa fazer o update de stock. Estoque na variação:  " .$skuId. " - Variations:  ". $variant['qty']." LastPost:  " . $skuLastPost['qty']. "\n";
                    $updateStock = false;
                }
                if($skuLastPost['price'] == $variant['price']){
                    echo "não precisa fazer o update de Preço. Preço na variação:  " .$skuId. " - Variations:  ". $variant['price']." LastPost:  " . $skuLastPost['price']. "\n" ;
                    $updatePrice = false;
                }
            } else {
                // If $skuLastPost is null, force update
                $updateStock = true;
                $updatePrice = true;
            }
			if(!$updatePrice && !$updateStock){
				echo "Estoque e preço já estão atualizados não preciso fazer nada \n";
				return false;
			}

			$url = '/ccadmin/v1/skus/' . $skuId  .'?fields=repositoryId,images';
			$this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET sku Update');
			if ($this->responseCode != 200) {
				//sku não cadastrado
				return $this->insertProductVariant($variant, null);					
			}

			$get_sku = json_decode($this->result);
			$images = $get_sku->images;
			// if (!$images) {
			// 	echo "atualizando imagens do sku \n";
			// 	if($this->prd['is_on_bucket']){
			// 		$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, $variant['image'], $skus['variants'][0]['id']);
			// 	}else{				
			// 		$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, $variant['image'], $skus['variants'][0]['id']);
			// 	}	
			// 	if (is_array($productImages)) {
			// 		$images = array('images' => $productImages);
			// 		$url = '/ccadmin/v1/skus/' . $skuId;
			// 		$this->process($url, 'PUT', json_encode($images), $this->prd['id'], $this->int_to, 'Atualizando Imagem do Sku');
			// 	}
			// }
			// Envia o Estoque
			if ($updateStock) {
				echo "fazendo update stock \n";
				$url = '/ccadmin/v1//inventories/' . $skuId;
				$this->process($url, 'PUT', json_encode(array('stockLevel' => $variant['qty'])), $this->prd['id'], $this->int_to, 'Atualizando Estoque');
			}

			//atualiza preço
			if ($updatePrice) {
				echo "fazendo update preco \n";
				$url = '/ccadminui/v1/prices';
				if (!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']) {
					$variant['list_price'] = $variant['price'] + 0.01;
				}
				$priceCampaign = $variant['price'];
				if ($this->promo) {
					$priceCampaign = $this->prd['promotional_price'];
				}
				$price = array(
					'items' => array(
						(object)[
							'derivedListPriceFrom' => $this->occ_price_default,
							'isListPriceInherited' => false,
							'isListVolumePriceInherited' => false,
							'listPrice' => $variant['list_price'],
							'priceListId' => $this->occ_price_default . '_listPrices',
							'productId' => $skumkt,
							'skuId' => $skuId,

						],
						(object)[
							'derivedListPriceFrom' => $this->occ_price_default,
							'isListPriceInherited' => false,
							'isListVolumePriceInherited' => false,
							'listPrice' => $priceCampaign,
							'priceListId' => $this->occ_price_default . '_salePrices',
							'productId' => $skumkt,
							'skuId' => $skuId,

						]
					)
				);

				$this->process($url, 'PUT', json_encode($price), $this->prd['id'], $this->int_to, 'Atualizando Preço');
			}
			//atualiza os atributos arthur
			// $url = '/ccadmin/v1/skus/'.$skuId;
			// if($this->occ_ref_sku_refid){
			// 	$updateSku[$this->occ_ref_sku_refid] = $variant['sku'];
			// }

			// $occ_ean = $this->model_settings->getValueIfAtiveByName('occ_ean');
			// if($occ_ean){
			// 	if($this->empty_ean){					
			// 		$updateSku[$occ_ean] = $variant['EAN'];									
			// 	}
			// }


			// $this->process($url, 'PUT', json_encode($updateSku), $this->prd['id'], $this->int_to, 'Atualizando Atributos');

			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
			$this->model_integrations->updatePrdToIntegration(
				array(
					'skumkt' 		=> $skuId,
					'skubling' 		=> $skuId,
					'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
					'date_last_int' => $this->dateLastInt,
					'mkt_product_id' => $skuId,
					'mkt_sku_id' => $skuId
				),
				$prd_to_integration['id']
			);

		} else {
			// Envia o Estoque
			$skuId = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'S';
			$skuLastPost = $this->model_occ_last_post->getBySku($skuId);
			if ($skuLastPost['qty'] == $this->prd['qty']) {
				echo "não precisa fazer o update de stock. Estoque na variação:  " .$skuId. " - Variations:  ". $this->prd['qty']." LastPost:  " . $skuLastPost['qty']. "\n";
				$updateStock = false;
			}
			if ($skuLastPost['price'] == $this->prd['promotional_price']) {
				echo "não precisa fazer o update de Preço. Preço na variação:  " .$skuId. " - Variations:  ". $this->prd['promotional_price']." LastPost:  " . $skuLastPost['price']. "\n" ;
				$updatePrice = false;
			}
			if (!$updatePrice && !$updateStock) {
				echo "Estoque e preço já estão atualizados não preciso fazer nada \n";
				return false;
			}
			$url = '/ccadmin/v1/skus/' . $skuId.'?fields=repositoryId';
			$this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET sku Update');
			if ($this->responseCode != 200) {
				//sku não cadastrado
				return $this->insertProductVariant($variant, null);					
			}
			if ($updateStock) {
				echo "fazendo update stock \n";
				$url = '/ccadmin/v1//inventories/' . $skuId;
				$this->process($url, 'PUT', json_encode(array('stockLevel' => $this->prd['qty'])), $this->prd['id'], $this->int_to, 'Atualizando Estoque');
			}
			if ($updatePrice) {
				echo "fazendo update preço \n";
				$url = '/ccadminui/v1/prices';
				if (!isset($this->prd['list_price']) || $this->prd['list_price'] == null || $this->prd['list_price'] <= $this->prd['price']) {
					$this->prd['list_price'] =  $this->prd['price'] + 0.01;
				}
				$price = array(
					'items' => array(
						(object)[
							'derivedListPriceFrom' => $this->occ_price_default,
							'isListPriceInherited' => false,
							'isListVolumePriceInherited' => false,
							'listPrice' => $this->prd['list_price'],
							'priceListId' => $this->occ_price_default . '_listPrices',
							'productId' => $skumkt,
							'skuId' => $skuId,

						],
						(object)[
							'derivedListPriceFrom' => $this->occ_price_default,
							'isListPriceInherited' => false,
							'isListVolumePriceInherited' => false,
							'listPrice' => $this->prd['promotional_price'],
							'priceListId' => $this->occ_price_default . '_salePrices',
							'productId' => $skumkt,
							'skuId' => $skuId,

						]
					)
				);

				$this->process($url, 'PUT', json_encode($price), $this->prd['id'], $this->int_to, 'Atualizando Preço');
			}
			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, (isset($variant['variant']) ? $variant['variant'] : null));
			$this->model_integrations->updatePrdToIntegration(
				array(
					'skumkt' 		=> $skuId,
					'skubling' 		=> $skuId,
					'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
					'date_last_int' => $this->dateLastInt,
					'mkt_product_id' => $skuId,
					'mkt_sku_id' => $skuId
				),
				$prd_to_integration['id']
			);
		}
		return true; 
	}


	function getkeys()
	{
		//pega os dados da integração. 
		$this->getIntegration();
		$this->auth_data = isset($this->integration_main['auth_data']) ? json_decode($this->integration_main['auth_data']) : null;
		//$this->setApikey($api_keys['apikey']);
		//	$this->setEmail($api_keys['email']);
	}

	function getIntegration()
	{

		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'], $this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto('0', $this->int_to);
			} else {
				$this->integration_main = $this->integration_store;
			}
		}
	}

	//public function getCategoryMarketplace($skumkt,$int_to = '')
	public function getCategory($skumkt, $int_to = '', $mandatory_category = true)
	{
		if ($int_to == '') {
			$int_to = $this->int_to;
		}

		$categoryId = json_decode($this->prd['category_id']);
		if (is_array($categoryId)) {
			$categoryId = $categoryId[0];
		}
		$category   = $this->model_categorias_marketplaces->getDataByCategoryId($categoryId);
		if (!$category) {
			$msg = 'Produto sem categoria.';
			echo 'Produto ' . $this->prd['id'] . ' ' . $msg . "\n";
			$this->errorTransformation($this->prd['id'], $skumkt, $msg, 'Preparação para o envio');
			//die;
			return false;
		}

		$this->prd['categoryname'] = $category[0]['category_marketplace_id'];

		return $categoryId;
	}

	function prepareProduct($sku)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo 'Preparando produto' . "\n";

		// busco a categoria 
		$this->prd['categoria_occ'] = $this->getCategory($sku);
		if ($this->prd['categoria_occ'] == false) {
			return false;
		}

		$collections = $this->model_collections->getProductCollectionIdByProductId($this->prd['id']);
		if ($collections == false) {
			$skumkt = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
			$this->errorTransformation($this->prd['id'], $skumkt, 'produto sem navegação selecionada', 'Preparação para envio');
			return false;
		}
		$this->collections = array();
		foreach ($collections as $collection) {
			array_push($this->collections, $collection['mktp_collection_id']);
		}

		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();

		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);

		if($this->prd['promotional_price'] < $this->prd['price']){
			$this->promo = true;
		}
		if ($this->prd['promotional_price'] > $this->prd['price']) {
			$this->prd['price'] = $this->prd['promotional_price'];
		}
		// se é a conectaLá não usa EAN para o produto
		if ($this->int_to == 'OCC') {
			$this->prd['EAN'] = null;
		}
		// se tiver Variação,  acerto o estoque de cada variação
		if ($this->prd['has_variants'] != '') {
			$variações = explode(";", $this->prd['has_variants']);

			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
				$this->variants[$key]['qty_original'] = $variant['qty'];
				if ((int)$this->variants[$key]['qty'] < 0) {
					$this->variants[$key]['qty'] = 0;
				}
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
					$this->variants[$key]['price'] = $this->prd['price'];
				}

				$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
				if ($this->variants[$key]['promotional_price'] > $this->variants[$key]['price']) {
					$this->variants[$key]['price'] = $this->variants[$key]['promotional_price'];
				}

				// 	//ricardo, por enquanto, o preço da variação é igual ao do produto. REMOVER DEPOIS QUE AS INTEGRAÇÔES ESTIVEREM CONCLUIDAS
				// 	$this->variants[$key]['price'] = $this->prd['price'];
				// 	$this->variants[$key]['promotional_price'] = $this->prd['promotional_price']; 

				// 	// se é a conectaLá não usa EAN para o produto
				if ($this->int_to == 'OCC') {
					$this->variants[$key]['EAN'] = null;
				}
			}
		}

		if ($this->prd['is_kit']) {  // B2W consegue mostrar o preço original dos produtos que o componhe 
			$productsKit = $this->model_products->getProductsKit($this->prd['id']);
			$original_price = 0;
			foreach ($productsKit as $productkit) {
				$original_price += $productkit['qty'] * $productkit['original_price'];
			}
			$this->prd['price'] = $original_price;
			echo ' KIT ' . $this->prd['id'] . ' preço de ' . $this->prd['price'] . ' por ' . $this->prd['promotional_price'] . "\n";
		}

		//leio a brand
		if ($this->getBrandMarketplace($sku, false) == false) {return false;}

		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1) {
			$this->prd['prazo_operacional_extra'] = 1;
		}

		// monto atributos de produto e sku
		//verificar se é obrigatório e se ta preenchido arthur
		$atributtes = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($this->prd['id']);
		foreach ($atributtes as $atributte) {
			$atributo_prd = $this->model_atributos_categorias_marketplaces->getAtributoCategoria_MKT($this->prd['categoryname'], $atributte['id_atributo'], $this->int_to);
			if ($atributo_prd) {
				if ($atributo_prd['tipo'] == 'date') {
					$atributte['valor'] = $atributte['valor'] . 'T00:00:00.000Z';
				}
				if ($atributo_prd['prd_sku']) {
					$this->skuAtributtes[$atributte['id_atributo']] = $atributte['valor'];
				} else {
					$this->prdAtributtes[$atributte['id_atributo']] = $atributte['valor'];
				}	
			}		
		}
		
		$peso_fora_caixa = $this->model_settings->getValueIfAtiveByName('peso_fora_caixa');
		if($peso_fora_caixa){
			$pesoForaCaixa = isset($this->prd['peso_liquido']) && !empty($this->prd['peso_liquido']) ? (float)$this->prd['peso_liquido'] : (float)$this->prd['peso_bruto'];
			$this->prdAtributtes[$peso_fora_caixa] = $pesoForaCaixa;
		}

		$altura_fora_caixa = $this->model_settings->getValueIfAtiveByName('altura_fora_caixa');
		if($altura_fora_caixa){
			$alturaForaCaixa = isset($this->prd['actual_height']) && !empty($this->prd['actual_height']) ? (float)$this->prd['actual_height'] : (float)$this->prd['altura'];
			$this->prdAtributtes[$altura_fora_caixa] = $alturaForaCaixa;
		}

		$largura_fora_caixa = $this->model_settings->getValueIfAtiveByName('largura_fora_caixa');
		if($largura_fora_caixa){
			$larguraForaCaixa = isset($this->prd['actual_width']) && !empty($this->prd['actual_width']) ? (float)$this->prd['actual_width'] : (float)$this->prd['largura'];
			$this->prdAtributtes[$largura_fora_caixa] = $larguraForaCaixa;
		}

		$comprimento_fora_caixa = $this->model_settings->getValueIfAtiveByName('comprimento_fora_caixa');
		if($comprimento_fora_caixa){
			$comprimentoForaCaixa = isset($this->prd['actual_depth']) && !empty($this->prd['actual_depth']) ? (float)$this->prd['actual_depth'] : (float)$this->prd['profundidade'];
			$this->prdAtributtes[$comprimento_fora_caixa] = $comprimentoForaCaixa;
		}
		
		return true;
	}

	private function getProductImages($folder_ori, $path, $vardir = '', $variacao = false, $update = [])
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$folder = $folder_ori;
		if ($vardir !== '') {
			$folder .= '/' . $vardir;
		} elseif ($variacao) {
			return array(); // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}
		$images = scandir(FCPATH . 'assets/images/' . $path . '/' . $folder);

		if (!$images) {
			return array();
		}
		if (count($images) <= 2) { // não achei nenhuma imagem
			if ($variacao) { // Mas é uma variação, retorna o array vazio
				return  array();
			}
			return array();
		}

		if ($update) {
			$needUpdate = false;
			$notEmpty = [];
			$count = 0;
			foreach ($images as $foto) {
				if (($foto != '.') && ($foto != '..') && ($foto != '')) {
					if (!is_dir(FCPATH . 'assets/images/' . $path . '/' . $folder . '/' . $foto)) {
						//existem imagens no diretorio
						$notEmpty = true;
						$count++;
						if (!in_array($this->prd_to_integration['skubling'] . $foto, $update)) {
							$needUpdate = true;
						}
					}
				}
			}
			//se não existem imagens no diretorio retorno array vazio para atualizar na occ
			if(!$notEmpty){
				return [];
			}
			//as imagens da occ batem com as armazenadas no servidor
			if (!$needUpdate && $count == count($update)) {
				return true;
			}
		}


		$numft = 0;
		$imagesData = array();
		foreach ($images as $foto) {
			if (($foto != '.') && ($foto != '..') && ($foto != '')) {
				if (!is_dir(FCPATH . 'assets/images/' . $path . '/' . $folder . '/' . $foto)) {
					$fileFullPath = FCPATH . 'assets/images/' . $path . '/' . $folder . '/' . $foto;
					$url = '/ccadmin/v1/files';
					$exp_extens = explode(".", $foto);
					$extensao = $exp_extens[count($exp_extens) - 1];
					$name = $this->prd_to_integration['skubling'] . $foto;

					if ($vardir !== '') {
						$name = $this->prd_to_integration['skubling']  . $foto;
					}

					$image = $this->processImage($url, $fileFullPath, $name, $this->prd['id'], $this->int_to, 'Upload de Imagem');
					$data = (object)['name' => '/products/' . $name, 'path' => '/products/' . $name, 'metadata' => (object)[]];
					array_push($imagesData, $data);
					$numft++;
				}
			}
		}
		return $imagesData;
	}

		private function getProductImagesBucket($folder_ori, $path, $vardir = '', $variacao = false, $update = [])
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$folder = $folder_ori;
		if ($vardir !== '') {
			$folder .= '/' . $vardir;
		} elseif ($variacao) {
			return array(); // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}
		$images = $this->bucket->getFinalObject('assets/images/' . $path . '/' . $folder);
		if (!$images['success']) {
			return array();
		}
		$images = $images['contents'];

		if ($update) {
			$needUpdate = false;
			$notEmpty = [];
			$count = 0;
			foreach ($images as $foto) {
				$notEmpty = true;
				$count++;
				if (!in_array($this->prd_to_integration['skubling'] . $foto['key'], $update)) {
					$needUpdate = true;
				}
			}
			//se não existem imagens no diretorio retorno array vazio para atualizar na occ
			if(!$notEmpty){
				return [];
			}
			//as imagens da occ batem com as armazenadas no servidor
			if (!$needUpdate && $count == count($update)) {
				return true;
			}
		}
		$imagesData = array();
		foreach ($images as $foto) {
			$fileFullPath = $foto['url'];
			$url = '/ccadmin/v1/files';
			$exp_extens = explode(".", $foto['key']);
			$extensao = $exp_extens[count($exp_extens) - 1];
			$name = $this->prd_to_integration['skubling'] . $foto['key'];

			$image = $this->processImage($url, $fileFullPath, $name, $this->prd['id'], $this->int_to, 'Upload de Imagem');
			$data = (object)['name' => '/products/' . $name, 'path' => '/products/' . $name, 'metadata' => (object)[]];
			array_push($imagesData, $data);			
		}
		return $imagesData;
	}

	function montaArray($sku, $novo_produto = true, $vendas = 0)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$description = $this->prd['description'];
		$description = str_replace("&amp;amp;", " ", $description);
		$description = str_replace("&amp;", " ", $description);
		$description = str_replace("&nbsp;", " ", $description);
		$description = substr(htmlspecialchars(strip_tags($description), ENT_QUOTES, "utf-8"), 0, 3800);

		$produto = array(
			'catalogId'		=> $this->catalogId,
			'productType'	=> $this->prd['categoryname'],
			'categoryId'	=> $this->collections[0],
			'properties'    => [
				'id'				=> $sku,
				'active' 			=> true,
				'displayName' 		=> substr(strip_tags($this->strReplaceName($this->prd['name']), " \t\n\r\0\x0B\xC2\xA0"), 0, 100),
				'description' 		=> $description,
				'longDescription' 	=> $this->prd['description'],
				'height' 			=> (float)$this->prd['altura'],
				'length' 			=> (float)($this->prd['profundidade'] < 16) ? 16 : $this->prd['profundidade'],
				'weight' 			=> (float)$this->prd['peso_bruto'],
				'width' 			=> (float)$this->prd['largura'],
				'listPrice' 		=> (float)$this->prd['list_price'] ? (float)$this->prd['list_price'] : (float)$this->prd['price'],
				'salePrice' 		=> (float)$this->prd['price'],
				'brand'				=> $this->prd['brandname'],
				'links'				=> array(
					array(
						"rel" => "self",
						"href" => $this->adlink
					)
				),

			]
		);

		if($this->prdAtributtes){
			$produto['properties'] = array_merge($produto['properties'], $this->prdAtributtes);
		}
		

		$resp_json = json_encode($produto);
		if ($resp_json === false) {
			// a descrição está com algum problema . tento reduzir... 
			$produto['name'] = substr(strip_tags($this->strReplaceName($this->prd['name']), " \t\n\r\0\x0B\xC2\xA0"), 0, 96);
			$produto['description'] = substr($description, 0, 3000);
			$resp_json = json_encode($produto);
			if ($resp_json === false) {
				$msg = 'Erro ao fazer o json do produto ' . $this->prd['id'] . ' ' . print_r($produto, true) . ' json error = ' . json_last_error_msg();
				var_dump($resp_json);
				echo $msg . "\n";
				$this->log_data('batch', $log_name, $msg, 'E');
				return false;;
			}
		}

		echo print_r($resp_json, true) . "\n";

		return $produto;
	}

	private function montaVariacoesDefault($variant_sku, $variant_category){
		foreach($variant_category as $vc){
			if($vc['nome'] == 'sellerId'){continue;}
			if(in_array(strtoupper($vc['nome']), $variant_sku)){continue;}
			$this->skuAtributtes[$vc['id_atributo']] = $this->variacoes_default;
		}

	}

	public function montaArraySku($sku, $is_variant = null)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$this->prd['skuname'] = $this->prd['name'];
		$allAtributoCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaVariant($this->prd['categoryname'], $this->int_to);

		if (!is_null($sku)) {

			$this->prd['skuname'] = $this->prd['name'] . ' ' . str_replace(";", " / ", $sku['name']);
			$this->prd['variant_value'] = $sku['name'];
			$variants_sku = explode(';', $this->prd['has_variants']);
			$variants_value  = explode(';', $sku['name']);
			$skumkt = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'V' . $sku['variant'];
			$variants_sku_upper = array_map('strtoupper', $variants_sku);

			foreach($variants_sku_upper as $key => $vs){
				if($vs == 'TAMANHO') {
					$variants_sku_upper[$key] = $this->variant_size;
					continue; 
				} 
				if($vs == 'COR') {
					$variants_sku_upper[$key] = $this->variant_color;
					continue; 
				} 
				if($vs == 'VOLTAGEM') {
					$variants_sku_upper[$key] = $this->variant_voltage;
					continue;
				} 
				if($vs == 'SABOR') {
					$variants_sku_upper[$key] = $this->variant_flavor;
					continue;
				} 
				if($vs == 'GRAU') {
					$variants_sku_upper[$key] = $this->variant_degree;
					continue;
				} 
				if($vs == 'LADO') {
					$variants_sku_upper[$key] = $this->variant_side;
					continue;
				} 
			}

			$variants_sku_upper = array_map('strtoupper', $variants_sku_upper);

			if(!$this->variacoes_default){	
				//validação para ver se está faltando preencher alguma variação
				
				foreach($allAtributoCat as $atr){
					if(strtoupper($atr['nome']) == strtoupper($this->variant_size)){
						if(in_array(strtoupper($this->variant_size), $variants_sku_upper)){
							continue;
						}
						$notice = "Esta categoria obriga variação por " . $atr['nome'];
						$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
						//die;
						return false;

					}
					if(strtoupper($atr['nome']) == strtoupper($this->variant_color)){
						if(in_array(strtoupper($this->variant_color), $variants_sku_upper)){
							continue;
						}
						$notice = "Esta categoria obriga variação por " . $atr['nome'];
						$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
						//die;
						return false;
						
					}
					if(strtoupper($atr['nome']) == strtoupper($this->variant_voltage)){
						if(in_array(strtoupper($this->variant_voltage), $variants_sku_upper)){
							continue;
						}
						$notice = "Esta categoria obriga variação por " . $atr['nome'];
						$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
						//die;
						return false;

						
					}

					if(strtoupper($atr['nome']) == strtoupper($this->variant_flavor)){
						if(in_array(strtoupper($this->variant_flavor), $variants_sku_upper)){
							continue;
						}
						$notice = "Esta categoria obriga variação por " . $atr['nome'];
						$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
						//die;
						return false;

						
					}

					if(strtoupper($atr['nome']) == strtoupper($this->variant_degree)){
						if(in_array(strtoupper($this->variant_degree), $variants_sku_upper)){
							continue;
						}
						$notice = "Esta categoria obriga variação por " . $atr['nome'];
						$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
						//die;
						return false;

						
					}

					if(strtoupper($atr['nome']) == strtoupper($this->variant_side)){
						if(in_array(strtoupper($this->variant_side), $variants_sku_upper)){
							continue;
						}
						$notice = "Esta categoria obriga variação por " . $atr['nome'];
						$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
						//die;
						return false;

						
					}
				}
				//validação para ver se tem variações que a categoria não permite
				foreach ($variants_sku_upper as $key => $value) {

					$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryname'], ucfirst(strtolower($value)), $this->int_to, !empty($this->prd['has_variants']));
					if (!$atributoCat) {
							$notice = "Esta categoria não aceita variação por " . $value;
							$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
							//die;
							return false;					
					}

					$this->skuAtributtes[$atributoCat['id_atributo']] = $variants_value[$key];
					
				}
				if($this->occ_ref_sku_refid){
					$this->skuAtributtes[$this->occ_ref_sku_refid] = $sku['sku'];
				}
				

			}else{
				$variacoes_preenchidas = [];
				foreach ($variants_sku_upper as $key => $value) {
					$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryname'], ucfirst(strtolower($value)), $this->int_to, !empty($this->prd['has_variants']));
					if($atributoCat){
					$this->skuAtributtes[$atributoCat['id_atributo']] = $variants_value[$key];
					array_push($variacoes_preenchidas, $value);
					continue;
					}
					echo 'atributo não encontrado'. $value;					
				}
				$this->montaVariacoesDefault($variacoes_preenchidas, $allAtributoCat);
				if($this->occ_ref_sku_refid){
					$this->skuAtributtes[$this->occ_ref_sku_refid] = $sku['sku'];
				}
			}

		} else {
			if(!$this->variacoes_default){
				//validação para ver se está faltando preencher alguma variação
				foreach($allAtributoCat as $atr){
					if($atr && $atr['nome'] != "sellerId"){
						$notice = "Esta categoria obriga variação por " . $atr['nome'];
						$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
						//die;
						return false;
					}
				}
			}else{
				$this->montaVariacoesDefault([], $allAtributoCat);
			}	
			$skumkt = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'S';
		}
		$prd_id = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		$occ_ean = $this->model_settings->getValueIfAtiveByName('occ_ean');
		if($occ_ean){
			if(!isset($this->skuAtributtes[$occ_ean])){
				if($sku){
					$this->skuAtributtes[$occ_ean] = $sku['EAN'];
					$this->empty_ean = true;
				}else{
					$this->skuAtributtes[$occ_ean] = $this->prd['EAN'];
					$this->empty_ean = true;
				}			
			}
		}

		$this->skuAtributtes['x_sellerId'] = $this->prd['store_id'];
		$variants = [
			[
				'id' => $skumkt,
				'displayName' => $this->prd['skuname'],
			]
		];
		$variants[0] = array_merge($variants[0], $this->skuAtributtes);
		$sku = array(
			'productId' => $prd_id,
			'variants' => $variants
		);



		return $sku;
	}

	protected function auth($endPoint, $authToken)
	{

		$this->header = [
			'content-type: application/x-www-form-urlencoded',
			'Authorization: Bearer ' . $authToken,
		];

		$url = 'https://' . $endPoint . '/ccadmin/v1/login?grant_type=client_credentials';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, []);

		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		$result       = curl_exec($ch);
		$this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

		curl_close($ch);
		$result = json_decode($result);
		return $result->access_token;
	}

	protected function process($endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to = null, $function = null)
	{
		//gravar log

		$credentials = $this->auth($this->auth_data->site, $this->auth_data->apikey);

		$this->header = [
			'content-type: application/json; charset=UTF-8',
			'Authorization: Bearer ' . $credentials,
			'X-CCAsset-Language: pt-BR'
		];

		$url = 'https://' . $this->auth_data->site . $endPoint;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		if ($method == 'PUT') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		}

		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		$this->result       = curl_exec($ch);
		$this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$err        = curl_errno($ch);
		$errmsg     = curl_error($ch);

		curl_close($ch);

		if ($this->responseCode == 429) {
			$this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.");
			sleep(60);
			$this->process($endPoint, $method, $data,  $prd_id, $int_to, $function);
		}
		if ($this->responseCode == 504) {
			$this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
			sleep(60);
			$this->process($endPoint, $method, $data,  $prd_id, $int_to, $function);
		}

		if (!is_null($prd_id)) {
			$data_log = array(
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
		sleep(1);
		return;
	}

	protected function processImage($endPoint, $file, $fileName, $prd_id = null, $int_to = null, $function = null)
	{


		$credentials = $this->auth($this->auth_data->site, $this->auth_data->apikey);

		$this->header = [
			'Content-Type:multipart/form-data',
			'Authorization: Bearer ' . $credentials,
			'X-CCAsset-Language: pt-BR'
		];

		$url = 'https://' . $this->auth_data->site . $endPoint;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt_array($ch, array(
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => array(
				'fileUpload' => new CURLFile($file),
				'filename' => ' /products/' . $fileName

			)
		));

		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		$this->result       = curl_exec($ch);
		$this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$err        = curl_errno($ch);
		$errmsg     = curl_error($ch);

		curl_close($ch);

		if ($this->responseCode == 429) {
			$this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.");
			sleep(60);
			$this->process($endPoint, $file, $fileName, $prd_id, $int_to, $function);
		}
		if ($this->responseCode == 504) {
			$this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
			sleep(60);
			$this->process($endPoint, $file, $fileName, $prd_id, $int_to, $function);
		}

		if (!is_null($prd_id)) {
			$data_log = array(
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => 'POST',
				'sent' => 'Upload de imagem',
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}

		return;
	}

	function updateOccLastPost($prd, $variant = null)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];
		if ($prd['EAN'] == '') {
			if ($prd['is_kit'] == 1) {
				$ean = 'IS_KIT' . $prd['id'];
			} else {
				$ean = 'NO_EAN' . $prd['id'];
			}
			if (!is_null($variant_num)) {
				$ean = $ean . 'V' . $variant_num;
			}
		}
		$skulocal = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'S';
		if (!is_null($variant_num)) {			
			$skulocal = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'V' . $variant['variant'];
			if(!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']){
				if(!isset($variant['price']) || $variant['price'] == null || $variant['price'] == 0){
					$variant['list_price'] = $prd['price'] + 0.01;
				}else{
					$variant['list_price'] = $variant['price'] + 0.01;
				}				
			}
			if(!isset($variant['price']) || $variant['price'] == null || $variant['price'] == 0){
				$variant['price'] = $prd['promotional_price'];
			}
		}else{
			$variant['price'] = $prd['promotional_price'];
			$variant['list_price'] = $prd['list_price'];
			if(!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']){
				$variant['list_price'] = $prd['price'];	
			}

		}

		$data = array(
			'int_to' => $this->int_to,
			'prd_id' => $prd['id'],
			'variant' => $variant_num,
			'company_id' => $prd['company_id'],
			'store_id' => $prd['store_id'],
			'EAN' => $ean,
			'price' => $variant['price'],
			'list_price' => $variant['list_price'],
			'qty' => $prd['qty'],
			'qty_total' => $prd['qty'],
			'sku' => $prd['sku'],
			'skulocal' => $skulocal,
			'skumkt' => $this->prd_to_integration['skumkt'],
			'date_last_sent' => $this->dateLastInt,
			'tipo_volume_codigo' => $prd['tipovolumecodigo'] ?? null,
			'width' => (float)$prd['largura'],
			'height' => (float)$prd['altura'],
			'length' => (float)$prd['profundidade'],
			'gross_weight' => (float)$prd['peso_bruto'],
			'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'],
			'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']),
			'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
			'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
		);

        $data = $this->formatFieldsUltEnvio($data);

		$savedOcclastPost = $this->model_occ_last_post->createIfNotExist($this->int_to, $prd['id'], $variant_num, $data);
		if (!$savedOcclastPost) {
			$notice = 'Falha ao tentar gravar dados na tabela occ_last_post.';
			echo $notice . "\n";
			$this->log_data('batch', $log_name, $notice, 'E');
			die;
		}
	}

	function hasShipCompany()
	{
		return true;
	}
	
	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		return $this->model_occ_last_post->getDataByIntToPrdIdVariant($int_to, $prd_id, $variant);
	}

	public function strReplaceName($name) {
        return str_replace('&amp;', '&', str_replace('&#039', "'", $name));
    }

    public function errorTransformation($prd_id, $sku, $msg, $step, $prd_to_integration_id = null, $mkt_code = null, $variant = null)
    {
        // Erro de transformação zera estoque.
        if ($this->getLastPost($prd_id, $this->int_to, $variant)) {
            $prd = $this->prd;

            if (!is_null($variant)) {
                $variant_data = getArrayByValueIn($this->variants, $variant, 'variant');
                $prd = $this->setDataVariantToUpdate($variant_data);
            }
            $prd['qty'] = 0;
            $prd['qty_original'] = 0;

            if (is_null($variant) && $this->prd['has_variants'] !== '') {
                if (count($this->variants) > 0) {
                    // Zera o estoque de todas as variações.
                    foreach ($this->variants as $variant_prd) {
                        $prd = $this->setDataVariantToUpdate($variant_prd);
                        $prd['qty'] = 0;
                        $prd['qty_original'] = 0;
                        $this->updateOccLastPost($prd, $variant_prd);
                    }
                }
            } else {
                $this->updateOccLastPost($prd, $variant);
            }
        }

        parent::errorTransformation($prd_id, $sku, $msg, $step, $prd_to_integration_id, $mkt_code, $variant);
    }

    private function setDataVariantToUpdate(array $variant)
    {
        $prd = $this->prd;
        $prd['qty']             = $variant['qty'];
        $prd['sku']             = $variant['sku'];
        $prd['price']           = $variant['price'];
        $prd['qty_original']    = $variant['qty_original'];
        $prd['EAN']             = ($variant['EAN'] != '') ? $variant['EAN'] : $this->prd['EAN'];

        if ($variant['status'] != 1) {
            $prd['qty'] = 0;
            $prd['qty_original'] = 0;
        }

        return $prd;
    }
}
