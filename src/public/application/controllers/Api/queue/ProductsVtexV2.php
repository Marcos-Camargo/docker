<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsConectala.php";

/**
 * @property CI_Loader $load
 * @property CI_Router $router
 *
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 * @property Model_brands $model_brands
 * @property Model_stores $model_stores
 * @property Model_category $model_category
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_brands_marketplaces $model_brands_marketplaces
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_integrations $model_integrations
 * @property Model_log_integration_product_marketplace $model_log_integration_product_marketplace
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_promotions $model_promotions
 * @property Model_products $model_products
 * @property Model_products_catalog $model_products_catalog
 * @property Model_settings $model_settings
 * @property Model_whitelist $model_whitelist
 * @property Model_blacklist_words $model_blacklist_words
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_control_sequential_skumkts $model_control_sequential_skumkts
 * @property Model_control_sync_skuseller_skumkt $model_control_sync_skuseller_skumkt
 * @property Bucket $bucket
 */
class ProductsVtexV2 extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $score_min = 100;  // score da Vtex 
	var $auto_approve = true;
	var $auth_data;
	var $fotos = array();
	var $sellerId; 
	var $tradesPolicies = array();  // array com as trade policies do cadastradas no produto
	var $adlink = null;   // http da marketplace 
	var $prd_vtex = null; 
	var $ref_id = 'SKUMKT';
	var $update_sku_specifications = false; // inidica se é para forçar a alteração das especificações de SKU
	var $update_product_specifications = false; // inidica se é para forçar a alteração das especificações de Products
	var $update_images_specifications = true; // inidica se é para forçar a alteração das imagens 
	var $skumkt_seller = false;  // indica se é para usar o sku do seller como skumkt na vtex.
	var $accountName ='';
	var $informacoesTecnicas = false;
	var $minimum_stock = 0;
	var $vtex_conectala = false;
	var $vtex_ref_id = null;
	var $update_sku_vtex = false;  // permite que altere informações do sku com altura, peso etc 
	var $update_product_vtex = false;  // permite que altere informações do product da vtex
	var $promo = false; // valida se tem campanha ou promo para enviar preço do sku ou produto

	var $variant_color;
	var $variant_size;
	var $variant_voltage;
	const COLOR_DEFAULT = 'Cor';
	const SIZE_DEFAULT = 'TAMANHO';
	const VOLTAGE_DEFAULT = 'VOLTAGEM';
    var $skumkt_default = 'conectala';
	
    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_vtex_ult_envio');
	    
	    $this->load->model('model_brands');
	    $this->load->model('model_stores');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_control_sequential_skumkts');
        $this->load->model('model_control_sync_skuseller_skumkt');
		$this->load->library('Bucket');

		$this->informacoesTecnicas = $this->model_settings->getValueIfAtiveByName('informacoes_tecnicas_vtex');
		
		$this->variant_color  = $this->model_settings->getValueIfAtiveByName('variant_color_attribute');
		if (!$this->variant_color) {  $this->variant_color = self::COLOR_DEFAULT; }

		$this->variant_size  = $this->model_settings->getValueIfAtiveByName('variant_size_attribute');
		if (!$this->variant_size) {  $this->variant_size = self::SIZE_DEFAULT; }

		$this->variant_voltage  = $this->model_settings->getValueIfAtiveByName('variant_voltage_attribute');
		if (!$this->variant_voltage) {  $this->variant_voltage = self::VOLTAGE_DEFAULT; }

	}
	
	public function index_post() 
    {
        // CHECK INTEGRATIONS SETTINGS :: START
        $integrationSettings = $this->getIntegrationSettings();
        if($integrationSettings){
            $this->tradesPolicies = json_decode($integrationSettings->tradesPolicies, true);
            $this->adlink = $integrationSettings->adlink;
            $this->auto_approve = $integrationSettings->auto_approve;
            $this->update_product_specifications = $integrationSettings->update_product_specifications;
            $this->update_sku_specifications = $integrationSettings->update_sku_specifications;
            $this->update_sku_vtex = $integrationSettings->update_sku_vtex;
            $this->update_product_vtex = $integrationSettings->update_product_vtex;
            $this->ref_id = $integrationSettings->ref_id;
            $this->minimum_stock = $integrationSettings->minimum_stock;
			$this->update_images_specifications = $integrationSettings->update_images_specifications;
            $this->skumkt_default = $integrationSettings->skumkt_default;
        }
        // CHECK INTEGRATIONS SETTINGS :: END

    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();

		// verifico se é cadastrar, inativar ou alterar o produto
		$this->checkAndProcessProduct();
			
		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		$fim= microtime(true);
		echo "\nExecutou em: ". ($fim-$this->inicio)*1000 ." ms\n";
		return;
    } 
	
	protected function beforeGetProductData($prd_id) {
		parent::beforeGetProductData($prd_id);
		$this->loadAttributesDefault($prd_id);
	}

	protected function loadAttributesDefault($prd_id) {
		$permiteVariacaoNosAtributos = $this->model_settings->getValueIfAtiveByName('permite_variacao_nos_atributos');
		if ($permiteVariacaoNosAtributos) {
			$prd = $this->model_products->getProductData(0,$prd_id);
				
			$categoryId = json_decode($prd['category_id']);
			if (is_array($categoryId)) {
				$categoryId = $categoryId[0];
			}
			$category_mkt = $this->model_categorias_marketplaces->getCategoryMktplace($this->int_to, $categoryId);
			$tipos_variacao = explode(";", strtoupper($prd['has_variants']));

			$variacaoValorDefault = $this->model_settings->getValueIfAtiveByName('variacao_valor_default');
			if ($variacaoValorDefault) {
				if (isset($category_mkt['category_marketplace_id'])) {
					$attributes = $this->model_atributos_categorias_marketplaces->getAllAtributesVariantByCategory($this->int_to, $category_mkt['category_marketplace_id']);
					foreach($attributes as $attribute) {
						$variacaoValorDefault_var = null;
						if (!in_array(strtoupper($attribute['nome']), $tipos_variacao)) {
							$prodAttrCat = $this->model_atributos_categorias_marketplaces->getProductAttributeById($prd_id, $attribute['id_atributo']);
							if (is_null($prodAttrCat)) {

								if ($attribute['tipo'] == 'list') {
									$valores = json_decode($attribute['valor'], true);
									foreach($valores as $valor) {
										if ($valor['Value'] == $variacaoValorDefault) {
											$variacaoValorDefault_var = $valor['FieldValueId'];
											break;
										}
									}
								}

								if (!is_null($variacaoValorDefault_var)) {
									$data_attribute_product = array(
										'id_product' => $prd_id,
										'id_atributo' => $attribute['id_atributo'],
										'valor' => $variacaoValorDefault_var,
										'int_to' => $this->int_to
									);
									$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
								}
							}
						}
					}
				}
			}
			else {
				$variacaoCorDefault = $this->model_settings->getValueIfAtiveByName('variacao_cor_default');
				$variacaoTamanhoDefault = $this->model_settings->getValueIfAtiveByName('variacao_tamanho_default');
				
				if (!in_array(strtoupper('cor'), $tipos_variacao)) {
					if ($variacaoCorDefault) {
                        $variacaoValorDefault_var = null;
						$atributoCat = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($this->int_to, $category_mkt['category_marketplace_id'], 'Cor');
						if (!is_null($atributoCat)) {
							$prodAttrCat = $this->model_atributos_categorias_marketplaces->getProductAttributeById($prd_id, $atributoCat['id_atributo']);
							if (is_null($prodAttrCat)) {
								if ($atributoCat['tipo'] == 'list') {
									$valores = json_decode($atributoCat['valor'], true);
									foreach($valores as $valor) {
										if ($valor['Value'] == $variacaoCorDefault) {
                                            $variacaoValorDefault_var = $valor['FieldValueId'];
											break;
										}
									}
								}
                                if (!is_null($variacaoValorDefault_var)) {
                                    $data_attribute_product = array(
                                        'id_product' => $prd_id,
                                        'id_atributo' => $atributoCat['id_atributo'],
                                        'valor' => $variacaoValorDefault_var,
                                        'int_to' => $this->int_to
                                    );
                                    $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
                                }
							}
						}
					}
				}

				if (!in_array(strtoupper('tamanho'), $tipos_variacao)) {
					if ($variacaoTamanhoDefault) {
                        $variacaoValorDefault_var = null;
						$atributoCat = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($this->int_to, $category_mkt['category_marketplace_id'], 'Tamanho');
						if (!is_null($atributoCat)) {
							$prodAttrCat = $this->model_atributos_categorias_marketplaces->getProductAttributeById($prd_id, $atributoCat['id_atributo']);
							if (is_null($prodAttrCat)) {
								if ($atributoCat['tipo'] == 'list') {
									$valores = json_decode($atributoCat['valor'], true);
									foreach($valores as $valor) {
										if ($valor['Value'] == $variacaoTamanhoDefault) {
                                            $variacaoValorDefault_var = $valor['FieldValueId'];
											break;
										}
									}
								}
                                if (!is_null($variacaoValorDefault_var)) {
                                    $data_attribute_product = array(
                                        'id_product' => $prd_id,
                                        'id_atributo' => $atributoCat['id_atributo'],
                                        'valor' => $variacaoValorDefault_var,
                                        'int_to' => $this->int_to
                                    );
                                    $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
                                }
							}
						}
					}
				}
			}
		}

		$sendSellernameAttributes = $this->model_settings->getValueIfAtiveByName('send_sellername_attribute');
		if ($sendSellernameAttributes) {
			$attribute = null;
			if (isset($category_mkt['category_id'])) {
				$attribute = $this->model_atributos_categorias_marketplaces->getAttributesByName($this->int_to, $category_mkt['category_id'], $sendSellernameAttributes);
			}
			if (!is_null($attribute)) {
				$prodAttrCat = $this->model_atributos_categorias_marketplaces->getProductAttributeById($prd_id, $attribute['id_atributo']);
				if (is_null($prodAttrCat)) {
					$this->prd=$this->model_products->getProductData(0,$prd_id);
					$this->store = $this->model_stores->getStoresById($this->prd['store_id']);
					$sellerName = $this->store['name'];
					if ($attribute['tipo'] == 'list') {
						$valores = json_decode($attribute['valor'], true);
						foreach($valores as $valor) {
							if ($valor['Value'] == $sellerName) {
								$sellerName = $valor['FieldValueId'];
								break;
							}
						}
					}
					$data_attribute_product = array(
						'id_product' => $prd_id,
						'id_atributo' => $attribute['id_atributo'],
						'valor' => $sellerName,
						'int_to' => $this->int_to
					);
					$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
				}
			}
		}

		return ;
	}

	public function checkAndProcessProduct()
	{
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
	public function getScore() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
 		$url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/configuration';
        $this->vtexHttpUrl($this->auth_data, $url);
        $result = json_decode($this->result);

		if ($this->responseCode == 404) {
			echo "Esse marketplace não configurou score_min \n";
			return ; 
		}
		if ($this->responseCode !== 200) {
			$erro = "Não foi possivel pegar o score mínimo para match de ".$this->int_to." http: ".$url." httpcode:".$this->responseCode." resposta: ".print_r($this->result,true);
            echo $erro."\n";
            $this->log_data('batch', $log_name, $erro,"E");
			die;
		}
		if (isset($result->Score->Approve)) {
			$this->score_min = $result->Score->Approve;
		}elseif (isset($result->score->approve)) {
			$this->score_min = $result->score->approve;
		}elseif (isset($result->Score->approve)) {
			$this->score_min = $result->Score->approve;
		}elseif (isset($result->score->Approve)) {
			$this->score_min = $result->score->Approve;
		}


	}

 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		// Pego o seller Id da Vtex
		$auth_data_local = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = ($auth_data_local && isset($auth_data_local->seller_id)) ? $auth_data_local->seller_id : '';
		
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		$this->accountName = ($this->auth_data && isset($this->auth_data->accountName)) ? $this->auth_data->accountName : '';
		// pego o preço do produto deste marqketplace ou da promotion se houver
		
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}

            // preparo o produto para ser feito update
            if (!$this->prepareProduct($this->newSkuMkt($this->prd_to_integration, $this->variants[0]))) {
                return false;
            }

			foreach($this->variants as $variant) {
                if ($variant['status'] != 1) {
                    $this->disableProductVariant(null, $variant);
                } else {
                    $this->insertProductVariant($variant);
                }
			}
		}
		else {
            // preparo o produto para ser feito update
            if (!$this->prepareProduct($this->newSkuMkt($this->prd_to_integration))) {
                return false;
            }
			$this->insertProductVariant();
		}

	}

	function newSkuMkt($prd_to_integration, $variant = null) {
		$skumkt = null; 
		if (!is_null($prd_to_integration)) {
			$skumkt = $prd_to_integration['skumkt'];
		}

		if (is_null($skumkt)) {
			if (is_null($prd_to_integration)) {
				$prd_to_integration = $this->prd_to_integration;
			}
			$getFlag = $this->model_stores->getStoresData($prd_to_integration['store_id']);

			if($getFlag['flag_store_migration'] == 1){
				if( (isset($prd_to_integration['variant']) && !is_null($variant)) || (is_null($prd_to_integration['variant'])) ){

					$filter = !is_null($variant) ? $variant['sku'] : $this->prd['sku'];

					$bodyParams = json_encode(array());
					$endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$this->sellerId .'/'.$filter;

					echo "Verificando se o produto de migração de seller ".$this->prd['id']." sku ".$filter." existe no marketplace ".$this->int_to." para o seller ".$this->sellerId ."\n";
					$this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Notificação de Mudança');

					if (($this->responseCode == 200) || ($this->responseCode == 204)) {

                        $this->notifyMarketplaceOfPriceUpdate($filter);
                        echo "Enviando notificação de mudança de preço do produto {$this->prd['id']} sku $filter para o marketplace $this->int_to do seller $this->sellerId\n";

						if( (isset($prd_to_integration['variant']) && !is_null($variant)) ){
							$this->model_products->updateProdutoSkutMktWithVariations($filter, $prd_to_integration['id']);
							return $filter;
						}
						if(is_null($prd_to_integration['variant'])){
							$this->model_products->updateProdutoSkutMkt($filter, $prd_to_integration['id']);
							return $filter;
						}
					}
				}
			}

            if ($this->skumkt_default === 'sequential_id') {
                if (empty($this->prd['has_variants']) || (!is_null($variant) && array_key_exists('variant', $variant))) {
                    $sku_sync = !is_null($variant) ? $variant['sku'] : $this->prd['sku'];
                    $sync_skuseller_skumkt = $this->model_control_sync_skuseller_skumkt->getByStoreSkuIntTo($this->prd['store_id'], $sku_sync, $this->int_to);

                    if (!empty($sync_skuseller_skumkt)) {
                        $skumkt = $sync_skuseller_skumkt['skumkt'];
                    } else {
                        $sequential_skumkts = $this->model_control_sequential_skumkts->getByPrdVariantIntTo($this->prd['id'], empty($variant) || $variant['variant'] === '' ? null : $variant['variant'], $this->int_to);
                        if (!$sequential_skumkts) {
                            $this->model_control_sequential_skumkts->create(array(
                                'prd_id'    => $this->prd['id'],
                                'variant'   => $variant['variant'] === '' ? null : $variant['variant'],
                                'int_to'    => $this->int_to
                            ));
                            $sequential_skumkts = $this->model_control_sequential_skumkts->getByPrdVariantIntTo($this->prd['id'], empty($variant) || $variant['variant'] === '' ? null : $variant['variant'], $this->int_to);
                        }
                        $skumkt = $sequential_skumkts['id'];
                    }
                }
            } else if  ($this->skumkt_seller) {  // o SKU na Vtex será igual ao SKU do lojista, inclusive nas variações
				$skumkt = $this->prd['sku'];
			} else {
				if ($this->vtex_conectala){  // Padrão Conectalá
					$skumkt = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
					if (!is_null($variant)) {
						$skumkt = $skumkt.'-'.$variant['variant'];
					}	
				}
				else { // Padrão Sellercenter
					if (!is_null($variant)) {
						$skumkt = $this->prd['id'].'_'.$variant['variant'].'_'.$this->int_to;
					}	
					else {
						$skumkt = $this->prd['id'].'_'.$this->int_to;
					}
					
				}
			}
		}
		return $skumkt; 
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Update"."\n";

		// Pego o seller Id da Vtex
		$auth_data_local = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = ($auth_data_local && isset($auth_data_local->seller_id)) ? $auth_data_local->seller_id : '';
		
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		$this->accountName = ($this->auth_data && isset($this->auth_data->accountName)) ? $this->auth_data->accountName : '';
        
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
        // preparo o produto para ser feito update
		$prepare_ok = $this->prepareProduct($this->prd_to_integration['skumkt']);

		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}			
			foreach($this->variants as $variant) {
                $prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
                if ($variant['status'] != 1) {
                    $this->disableProductVariant($prd_to_integration, $variant);
                } else {
                    $this->updateProductVariant($prd_to_integration, $variant, false, $prepare_ok);
                }
			}
			$todelete =  $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to,null);
			if ($todelete) {
				$this->model_integrations->removePrdToIntegration($todelete['id']); 
				if ($this->prd_to_integration['id'] == $todelete['id']) {
					$this->prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);;
				}
			}
			
		}
		else {
			$this->updateProductVariant($this->prd_to_integration, null, false, $prepare_ok);
		}
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		// Caso esteja inativando o produto e não a variante, impede o envio de qualquer outra variante.
		if ($variant == null) {
			$this->setInactivate();
		}

		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando"."\n";

		// Pego o seller Id da Vtex
		$auth_data_local = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = $auth_data_local->seller_id;
		
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		  // pego o preço do produto deste marqketplace ou da promotion se houver 
		$this->prd['qty'] = 0; // zero a quantidade do produto
		$this->update_images_specifications = false;
		$this->update_sku_specifications = false;
        $this->update_product_specifications = false;
		// preparo o produto para ser feito update
		$prepare_ok = $this->prepareProduct($this->newSkuMkt($this->prd_to_integration));

		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}

            $variants = $this->variants;
            if (!is_null($variant)) {
                $variants = array($variant);
            }

			foreach($variants as $variant) {
				$variant['qty'] = 0;  // zero a quantidade da variant tb
				$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
				$this->updateProductVariant($prd_to_integration, $variant, $disable, $prepare_ok );
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$prd_to_integration['id']);
			}
		}
		else {
			$this->updateProductVariant($this->prd_to_integration, null, $disable, $prepare_ok );
			$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
		}
	}
	
	function prepareProduct($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Preparando produto'."\n";
		
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arredondo para cima 
		if ((int)$this->prd['qty'] < $this->minimum_stock) { 
			$this->prd['qty']  = 0;
		}
		
		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);
		//validar se tem promoção

        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku'))
        {
            if($this->prd['promotional_price'] < $this->prd['price']){
                $this->promo = true;
            }
        }

		if ($this->prd['promotional_price'] > $this->prd['price'] ) {
			$this->prd['price'] = $this->prd['promotional_price']; 
		}

		if (!isset($this->prd['list_price']) || ($this->prd['list_price'] == null) || ($this->prd['list_price'] == 0)) {
			$this->prd['list_price'] = $this->prd['price']; 
		}

		// vejo se devo remover os tags HTML
		$description_without_html = $this->model_settings->getValueIfAtiveByName('description_without_html');
		if ($description_without_html) {
			$this->prd['description'] = nl2br($this->prd['description']);
			$this->prd['description'] = str_replace("<br>",$description_without_html,$this->prd['description']);
			$this->prd['description'] = str_replace("</p>",$description_without_html,$this->prd['description']);
			$this->prd['description'] = str_replace("&nbsp;",' ',$this->prd['description']);
			$this->prd['description'] = strip_tags($this->prd['description'],"<br>");  // se o $description_without_html for <br> eu tenho que permiti aqui
		}
	
		// se tiver Variação, acerto o estoque de cada variação
    	if ($this->prd['has_variants']!='') {
    		$variações = explode(";",$this->prd['has_variants']);

			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
				$this->variants[$key]['qty_original'] =$variant['qty'];				
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
				if  ((int)$this->variants[$key]['qty'] < $this->minimum_stock) { 
					$this->variants[$key]['qty'] = 0;
				}
				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
					$this->variants[$key]['price'] = $this->prd['price'];
				}

				$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
				if ($this->variants[$key]['promotional_price'] > $this->variants[$key]['price'] ) {
					$this->variants[$key]['price'] = $this->variants[$key]['promotional_price'];
				}
				
				//ricardo, por enquanto, o preço da variação é igual ao do produto. REMOVER DEPOIS QUE AS INTEGRAÇÔES ESTIVEREM CONCLUIDAS
				if ($this->vtex_conectala){  // Padrão Conectalá
					// $this->variants[$key]['price'] = $this->prd['price'];
					// $this->variants[$key]['promotional_price'] = $this->prd['promotional_price']; 
					$this->variants[$key]['EAN'] = ''; 
				}
			}
		}
		
		if ($this->prd['is_kit']) {  // vtex consegue mostrar o preço original dos produtos que o componhe 
			$productsKit = $this->model_products->getProductsKit($this->prd['id']);
			$original_price = 0; 
			foreach($productsKit as $productkit) {
				$original_price += $productkit['qty'] * $productkit['original_price'];
			}
			$this->prd['price'] = $original_price;
			echo ' KIT '.$this->prd['id'].' preço de '.$this->prd['price'].' por '.$this->prd['promotional_price']."\n";  
		}
	
		//verifico se o nome está ok
		if (mb_strlen($this->prd['name']) > 150) {
			$notice = "Nome do produto acima de 150 caracteres. Limite máximo do Marketplace é de 150. Favor, acertar o nome do produto";
            echo $notice."\n";
			$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio');
			return false; 
		}
		
		// pego a categoria 
		$this->prd['categoryvtex'] = $this->getCategoryMarketplace($skumkt);
		if (!$this->prd['categoryvtex']) {
			return false; 
		}
		
		if ($this->vtex_conectala){  // Padrão Conectalá
			$this->prd['EAN'] = ''; // sem EAN
		
			// pego a marca
			$brandId  = json_decode($this->prd['brand_id']);
			if ($brandId=='') {
				$msg= 'Produto sem Marca e é obrigatório para '.$this->int_to;
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
				$this->prd['brandname'] = "";
				return false;
			}				
				
	    	$brand = $this->model_brands->getBrandData($brandId);
			$this->prd['brandname'] = $brand['name'];
			$vtexBrand = $this->model_brands_vtex->getBrandMktplaceByName($this->int_to, mb_strtolower($this->prd['brandname']));
			if(empty($vtexBrand)){ // Verifica associação das marcas pelo nome somente
				$this->errorTransformation($this->prd['id'],$skumkt, 'A marca do produto não existe na vtex', 'Preparação para o envio');
	            return false;
			}
			$this->prd['brandvtex'] = $vtexBrand['external_id'];
		}
		else {
			// pego a Marca
			$this->prd['brandvtex'] = $this->getBrandMarketplace($skumkt, true);
			if ($this->prd['brandvtex']===false) {
				return false; 
			}
		}
		
		return true; 
		
	}
	
	function updateProductVariant($prd_to_integration, $variant=null, $disable = false, $prepare_ok = true) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		if (is_null($prd_to_integration)) {
			// houve algum problema com alguma variação e a mesma não foi enviada, então insiro. 
			$this->insertProductVariant($variant);
			return;
		}
		if (!isset($prd_to_integration['skumkt'])) {
			// houve algum problema com alguma variação e a mesma não foi enviada, então insiro. 
			$this->insertProductVariant($variant);
			return;
		}
		if (is_null($prd_to_integration['skumkt'])) {
			// houve algum problema com alguma variação e a mesma não foi enviada, então insiro. 
			$this->insertProductVariant($variant);
			return;
		}
		$variant_num = null;
		if (!is_null($variant)) {
            $this->setDataVariantToUpdate($variant);
			$variant_num = $variant['variant'];
		}
				
		$this->saveVtexUltEnvio($prd_to_integration['skumkt'],$variant_num );
			
		$data = [];
		$bodyParams = json_encode($data);
        $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$this->sellerId .'/'.$prd_to_integration['skumkt'];
	            
        echo "Verificando se o produto ".$this->prd['id']." sku ".$prd_to_integration['skumkt']." existe no marketplace ".$this->int_to." para o seller ".$this->sellerId ."\n";
        $skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Notificação de Mudança');
        
        if ($this->responseCode == 404) {
			sleep(60); // pode não ter dado tempo para a Vtex atualizar as multiplas replicas do banco de dados
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Notificação de Mudança');
        }
		
		if ($this->responseCode == 404) {
			// var_dump($prd_to_integration);
			if ($prd_to_integration['status_int'] != 22) { //está em cadastramento
				$erro = "O produto ".$this->prd['id']." não está cadastrado no marketplace ".$this->int_to." para o seller ".$this->sellerId .".";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				$this->model_integrations->updatePrdToIntegration(array('status_int' => 90, 'date_last_int' => $this->dateLastInt), $prd_to_integration['id']);
				
				echo " **** deveria retirar o registro do prd_to_integration e do vtex_ult_envio se isso acontecer"."\n";
			}
			else {
				echo "Ainda em cadastramento! Mando a suggestion de novo\n";	
			}
			$this->insertProductVariant($variant);;
			return;
		}
		if (($this->responseCode !== 204) && ($this->responseCode !== 200)) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}

		if (!$prepare_ok) {
			echo "Houve um erro na preparação do produto, então encerro aqui\n";
			return;
		}
		
		// pego os ids da Vtex pois podem ter sido re-associados a outro produto
		$ids =$this->getVtexSkuProductId($variant_num, $prd_to_integration['skumkt']);

        $this->notifyMarketplaceOfPriceUpdate($prd_to_integration['skumkt']);
        echo "Enviando notificação de mudança de preço do produto {$this->prd['id']} sku {$prd_to_integration['skumkt']} para o marketplace $this->int_to do seller $this->sellerId\n";

        $notice = "Notificação de alteração concluída para o produto ".$this->prd['id']." sku: ".$prd_to_integration['skumkt'];
        echo $notice."\n";
        $this->model_integrations->updatePrdToIntegration(array(
            	'int_id'			=> $this->integration_store['id'], 
            	'seller_id'			=> $this->sellerId , 
            	'status_int' 		=> 2, 
            	'date_last_int' 	=> $this->dateLastInt, 
            	'skumkt' 			=> $prd_to_integration['skumkt'], 
            	'skubling' 			=> $prd_to_integration['skumkt'],
            	'mkt_product_id' 	=> $ids['mkt_product_id'],
            	'mkt_sku_id' 		=> $ids['mkt_sku_id'],   
            	'ad_link' 			=>  (is_null($this->adlink)) ? '' : $this->adlink . $this->prd_vtex->LinkId."/p"
			), $prd_to_integration['id']);
			
		$prd_to_integration['mkt_product_id'] = $ids['mkt_product_id'];
		$prd_to_integration['mkt_sku_id'] = $ids['mkt_sku_id'];


//		if ((!$this->vtex_conectala) && (is_null($this->prd['mkt_product_id']))) { // se for sellercenter mas sem produto de catalog ( gsoma e reserva) pode fazer update no produto, imagens e etc.
        if (!$this->vtex_conectala) { // se for sellercenter mas sem produto de catalog ( gsoma e reserva) pode fazer update no produto, imagens e etc.

			$match_exists = $this->verifySellers($prd_to_integration['mkt_sku_id']);
			
			if ($match_exists) { // se tem match então não pode alterar nada no produto ou sku 
				$this->update_product_vtex = false;
				$this->update_sku_vtex = false;
				$this->update_images_specifications = false;
			}
			$ok = true;
			if ((is_null($variant_num) || ($variant_num ==0))  && $this->update_product_vtex){
				// update o product  somente na primeira variação
				$ok = $this->updateProductVtex($prd_to_integration, $variant);
			}
			
			// update das imagens
			if (($this->update_images_specifications) && ($ok)) {
				$ok = $this->changeSkuImage($prd_to_integration, $variant);
			}
			
			if (($this->update_sku_vtex) && ($ok)) {
				// update do sku - varição
				$ok = $this->updateSkuVtex($prd_to_integration, $variant, $disable);
			}
		}

	}

	function insertProductVariant($variant=null)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$variant_num = null;
		$prd_to_integration = $this->prd_to_integration;
		if (!is_null($variant)) {
			$variant_num = $variant['variant'];
            $this->setDataVariantToUpdate($variant);
			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant_num);
		}
		
		$this->prd['sku']= str_replace('.','',$this->prd['sku']);
		
		$skumkt = $this->newSkuMkt($prd_to_integration, $variant); 

		// Verifico de o produto existe na Vtext
		// https://help.vtex.com/en/tutorial/integration-guide-for-marketplaces-seller-non-vtex-with-payment--bNY99qbQ7mKsSMMuq2m4g
		$bodyParams = json_encode(array());
        $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$this->sellerId .'/'.$skumkt;
        
        echo "Verificando se o produto ".$this->prd['id']." sku ".$skumkt." existe no marketplace ".$this->int_to." para o seller ".$this->sellerId ."\n";
        $skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Notificação de Mudança');
		
		if (($this->responseCode == 200) || ($this->responseCode == 204)) {
			// O Produto já está na VTEX então insert na prd-to_integration e faço update dele.  
			$this->productAtVtex($variant_num,$skumkt);
			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant_num);		
			$this->updateProductVariant($prd_to_integration, $variant); 
			return; 
		}
		// Caso o produto tenha sido inativado, retorna.
		if ($this->from_inactivate) {
			echo "Produto não existe no marketplace, contudo está inativo, logo não será inserido.\n";
			return;
		}
		if ($this->responseCode !== 404) { // O normal é dar 404, então podemos cadastrar o produto
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}

        $this->notifyMarketplaceOfPriceUpdate($skumkt);
        echo "Enviando notificação de mudança de preço do produto {$this->prd['id']} sku $skumkt para o marketplace $this->int_to do seller $this->sellerId\n";

		// Monto array com as imagens do produto
		$vardir = '';
		$images = $this->getProductImages($this->prd['image'], $this->pathImage, $vardir);
		if ($images === null) {
            $notice = "Pasta ".$this->pathImage .'/'.$this->prd['image']." não encontrada!";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
            return;
        } elseif ($images === false) {
            $notice = "Não foram encontradas imagens na pasta ".$this->pathImage.'/'.$this->prd['image'].".";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
            return;
        }
		if  (!is_null($variant)){
			$images_var = null;
			if ($this->pathImage == 'product_image') {
				if (!is_null($variant['image']) && trim($variant['image'])!='')	{
					$vardir = '/'.$variant['image'];
					$images_var = $this->getProductImages($this->prd['image'], $this->pathImage,  $vardir);
				}
			}
			else { // produto de catálogo
				$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$variant['variant'] ); 
				if ($var_cat) {
					$images_var = $this->getProductImages($var_cat['image'], $this->pathImage,  '');
				}
			}
			if (is_array($images_var)) {
                $images = array_merge($images_var, $images); // junto as imagens da variação premeiro e depois a do pai
                if ($this->only_send_images_from_sku) {
                    $images = $images_var;
                }
			}
		} 	
		
		if (!(array_key_exists('ref_id', $this->prd))) {
			$this->prd['ref_id'] = null;
		}
		if (!(array_key_exists('sku_id', $this->prd))) {
			$this->prd['sku_id'] = null;
		}

		// Procuro as variações 
		$skuspec_match = array();
		$spec = array();
		$spec_old = array();
		$this->prd['skuname']= $this->prd['name'];
		if ($this->prd['has_variants']!= '') {
			$this->prd['skuname'] = $this->prd['name'].' '.str_replace(";", " / ", $variant['name']);
			$this->prd['variant_value'] = $variant['name'];
			$variants = explode(';', $this->prd['has_variants']);
			$variants_value  = explode(';', $variant['name']);
			foreach( $variants as $key => $value) {
				if ($value == self::SIZE_DEFAULT) { $value = $this->variant_size; }
				if ($value == self::COLOR_DEFAULT) { $value = $this->variant_color; }
				if ($value == self::VOLTAGE_DEFAULT) { $value = $this->variant_voltage; }

				$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryvtex'],ucfirst(strtolower($value)),$this->int_to, !empty($this->prd['has_variants']));
				$valor_id = null;
				$field_id=0;
				
				if ($atributoCat) {
					if ($atributoCat['variacao'] == 0) {
						$notice = "Esta categoria não aceita variação por ".$value;
						$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
			            return;
					} 
					$field_id = $atributoCat['id_atributo'];
					$valores = json_decode($atributoCat['valor'],true);
					$val_accepts = '  ';
					$cnt_val = 0; 
					foreach($valores as $valor) {
						if ($valor['IsActive']) {
							if (array_key_exists($key,$variants_value)) {
								if ($cnt_val++ < 10) {
									$val_accepts .= "'".$valor['Value']."', ";
								}								 
								if (trim(strtoupper($valor['Value'])) ==  trim(strtoupper($variants_value[$key]))) {
									$valor_id = array((int)$valor['FieldValueId']);
									break ;
								}
							}
						}
					}
					if ($field_id!=0) {
						if (is_null($valor_id)) { 							
							$notice = "Esta categoria não aceita o valor ".$variants_value[$key]." para a variação ".$value;
							$notice .= ". Alguns Valores aceitos: ".trim(substr($val_accepts,0,-2))."\n"; 
							echo $notice."\n";
							$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
							return;
						}
						$spec[] = array(
							'FieldId' 		=> (int)$field_id,
							'FieldName' 	=> ucfirst(strtolower($value)), 
							'FieldValueIds' => $valor_id,
							'FieldValues' 	=> array(trim($variants_value[$key])),
						);	
						$spec_old[] = array(
							'FieldName'		=> ucfirst(strtolower($value)), 
							'FieldValues' 	=> array(trim($variants_value[$key])),
						);
						$skuspec_match[ucfirst(strtolower($value))] = trim($variants_value[$key]);
					}
					else {
                        if (((int)$atributoCat['obrigatorio']) == 1
                            && ((int)$atributoCat['variacao']) == 1) { // mas é obrigatório e de variação
							$msg= 'Atributo obrigatório não preenchido ou com valor incorreto: '.$atributoCat['nome'].' : '.$variants_value[$key];
							echo 'Produto '.$this->prd['id']." ".$msg."\n";
							$this->errorTransformation($this->prd['id'],$skumkt, $msg, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
							return false;
						}
					}
					
				}
				else { // No futuro adicionar considerar adicionar o valor da variaçao no nome. 
					$notice = "Esta categoria não tem variação por ".$value;
					$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
					return;
				}				
			}
			$data['SkuSpecifications'] = $spec; 
			
			// verifica se já cadastrou alguma variant antes deste produto. 
			$existVtex = $this->model_integrations->getDifferentVariant($this->int_to,$this->prd['id'],$variant_num);
			if ($existVtex) {
				$this->prd['ref_id'] = $existVtex['mkt_product_id'];
			}
			
		}

		$this->prd['vteximages'] = array();
		foreach ($images as $image) {
			// alterar para colocar as imagens da variacao 
			$this->prd['vteximages'][$image['ImageName']] = $image['ImageUrl'];
		}

		// Busco os atributos específicos do produto para este marketplace 
		$prdspec_match = array();
		$prodspec = array();
		$prodspec_old= array();
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoryvtex'],$this->int_to);
		foreach($atributosCat as $atributoCat) {

			if ($this->informacoesTecnicas) {
				if ($this->informacoesTecnicas == $atributoCat['nome']) {
					$attributesCustomProduct = $this->model_products->getAttributesCustomProduct($this->prd['id']);
					$valueInformacoesTecnicas = $this->getInformacoesTecnicas($attributesCustomProduct);
					
					if ($valueInformacoesTecnicas !== false) {
						$dataProdutosAtributos = array(
							'id_product' => $this->prd['id'],
							'id_atributo' => $atributoCat['id_atributo'],
							'valor' => $valueInformacoesTecnicas,
							'int_to' => $this->int_to
						);

						$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($dataProdutosAtributos);
					}
				}
			}
			
			if (is_null($this->prd['product_catalog_id'])) {  // pego os atributos que vem do produto.
				$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to);
			} else { // as atributos vem de outra tabela se usa produto de catálogo.
				$atributo_prd = $this->model_products_catalog->getProductAttributeByIdIntto($this->prd['product_catalog_id'],$atributoCat['id_atributo'],$this->int_to);
			}
			if (!$atributo_prd) {  // o atributo não está preenchido 
                if (((int)$atributoCat['obrigatorio']) == 1
                    && ((int)$atributoCat['variacao']) == 0
                ) { // mas é obrigatório e não é de variação
                	$found = false;  // se já está na variação um semelhante, puxa tb pro produto - BUGS-691
                	foreach ($spec_old as $spec_var) {
                		if ($spec_var['FieldName'] == $atributoCat['nome']) {
                			$found = true; 
							// $prodspec[] = $spec_var;
							$prodspec_old[] = $spec_var;  // - BUGS-691
							$prdspec_match[$atributoCat['nome']] = $spec_var['FieldValues'][0];
							continue;
                		}
                	}
                	if (!$found) {
                		$msg= 'Atributo obrigatório não preenchido : '.$atributoCat['nome'];
						echo 'Produto '.$this->prd['id']." ".$msg."\n";
						$this->errorTransformation($this->prd['id'],$skumkt, $msg, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
						return false;
                	}
					
				} 
			} else {  // o atributo está preenchido, vou pegar seus valores
				$valor_id = null;
				$valores = json_decode($atributoCat['valor'],true);
				foreach($valores as $valor) {
					if ($valor['IsActive']) { 
						if (trim(strtoupper($valor['FieldValueId'])) ==  trim(strtoupper($atributo_prd['valor']))) {
							$valor_id = array((int)$atributo_prd['valor']);
							$atributo_prd['valor'] = $valor['Value'];
							break ;
						}
					}
				}
				$spec_novo = array(
						'FieldId' 		=> (int)$atributo_prd['id_atributo'], 
						'FieldName' 	=> $atributoCat['nome'], 
						'FieldValueIds' => $valor_id, 
						'FieldValues' 	=> array(trim($atributo_prd['valor'])), 
					);
				$spec_velho = array(
						'FieldName' 	=> $atributoCat['nome'], 
						'FieldValues' 	=> array(trim($atributo_prd['valor'])),
					);
				if ($atributoCat['variacao'] == 0) {  // não é uma variação, fica no produto 
					$prodspec[] = $spec_novo;
					$prodspec_old[] = $spec_velho;
					$prdspec_match[$atributoCat['nome']] = trim($atributo_prd['valor']);
				}
				else { // é uma variação, fica no sku
					$spec[] = $spec_novo;	
					$spec_old[] = $spec_velho;
					$skuspec_match[$atributoCat['nome']] = trim($atributo_prd['valor']);
				}
			}
		}
		if (count($prodspec) == 0) {
			$prodspec = null;
		}
		if (count($spec) == 0) {
			$spec = null;
		}

		// mando a suggestion	
		// Suggestion V1
		$images_old = array();
		foreach ($images as $image) {
			$images_old[] = array(
				'imageName' => $image['ImageName'], 
				'imageUrl' => $image['ImageUrl'],
			);
		}
		
		if (empty($images_old)) {
			$this->errorTransformation($this->prd['id'],$skumkt, 'Produto sem Imagem', 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
            return false;
		}
		
		$vtex_prod_id= (trim($this->prd['ref_id']) ? $this->prd['ref_id'] : null);
		
		$refid= $skumkt; 
		if ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto - solicitação Ortobom
			$this->prd['EAN'] = '';
			$refid = $this->prd['id'];
		}

		if (is_null($this->prd['mkt_product_id'])) { // processo normal de produto novo que precisa fazer suggestion 
			$ok = $this->suggestionv1($skumkt, $vtex_prod_id, $spec_old, $prodspec_old, $images_old, $refid);  
			if (!$ok) {
				return false;
			}
		}
		else { // Produto vindo de catálogo da Vtex então muda o jeito de fazer match 
			$ok = $this->suggestionv2($skumkt,  $spec, $prodspec, $images );
			if (!$ok) {
				return false;
			}
		}

		echo "Iniciando match do produto."."\n";
		$matchOk = $this->skuToMatch($skumkt, $prdspec_match, $skuspec_match, $variant_num);

        if ($matchOk['success'] === false) {
			$errors_map = array (
				'Catalog response: {"Errors":[{"Message":"Violation of UNIQUE KEY constraint \'IX_Produto_TextoLink\'', 
				'Association can be redone'
			);
			$maps_message = array (
				'O título do produto não foi aceito na VTEX por já existir um outro produto com mesmo nome ou muito semelhante. Por favor, altere alguma informação do nome do produto e integre novamente!',
				'Favor abrir chamado para que serja removida a associação antiga deste sku na Vtex'
			);
			foreach ($errors_map as $key => $error_map) {
				if (substr($matchOk['error'],0,strlen($error_map)) ==  $error_map) {
					$matchOk['error'] = $maps_message[$key];
					break;
				}
			}
            $this->errorTransformation($this->prd['id'],$skumkt, $matchOk['error'], 'Match de produto', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
            return;
        }
		if ($matchOk['success'] == 'Pending') {
			$this->saveVtexUltEnvio($skumkt,$variant_num );
       
	        $toSavePrd = [
	            'prd_id' 			=> $this->prd['id'],
	            'company_id' 		=> $this->prd['company_id'],
	            'status' 			=> 1,
	            'status_int' 		=> 22, // está aguardando aprovação na Vtex
	            'date_last_int' 	=> $this->dateLastInt, 
	            'skumkt' 			=> $skumkt,
	            'skubling' 			=> $skumkt,
	            'int_to' 			=> $this->int_to,
	            'store_id' 			=> $this->prd['store_id'],
	            'seller_id' 		=> $this->sellerId,
	            'approved' 			=> 1,
	            'int_id' 			=> $this->integration_store['id'],
	            'user_id' 			=> 0,
	            'int_type' 			=> 0, 
	            'variant' 			=> $variant_num,
	            'mkt_product_id' 	=> null, 
	            'mkt_sku_id' 		=> null
	        ];
			if(isset($this->prd['approved_curatorship_at']) && !empty($this->prd['approved_curatorship_at'])){
                $toSavePrd['approved_curatorship_at'] = $this->prd['approved_curatorship_at'];
            }

            if(isset($this->prd_to_integration['approved_curatorship_at']) && !empty($this->prd_to_integration['approved_curatorship_at'])){
                $toSavePrd['approved_curatorship_at'] = $this->prd_to_integration['approved_curatorship_at'];
            }

			$PrdIntId = $this->model_integrations->createIfNotExist($this->prd['id'], $this->int_to, $variant_num, $toSavePrd);

	        if (!$PrdIntId) {
	            $notice = "Falha ao tentar gravar produto na tabela prd_to_integration.";
	            echo $notice."\n";
	            $this->log_data('batch', $log_name, $notice,"E");
				die;
	        }
			$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
			if (!is_null($variant)) {
				// apaga o registro inicial criado na BlingMarcaTodosEnvio sem variação
				$todelete =  $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to,null);
				if ($todelete) {
					$this->model_integrations->removePrdToIntegration($todelete['id']); 
					if ($this->prd_to_integration['id'] == $todelete['id']) {
						$this->prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);;
					}
				}
			}
			return;
		}
		if ($matchOk['success'] == 'Denied') {
			$this->errorTransformation($this->prd['id'],$skumkt, 'Match da VTEX reprovou o produto ou variação '.$skumkt, 'Match de produto', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
            return;
		}
		
		// aprovou...
		$vtexprodid = $matchOk['ProductId']; 
		echo "ID do Produto -> ".$matchOk['ProductId']."\n"; 
		echo "ID do sku  -> ".$matchOk['SkuId']."\n"; 
		$skuvtex = null;

		if (is_null($vtexprodid)) {
			// Agora posso pegar o SKU de verdade  
			if (is_null($matchOk['SkuId'])) {
				if (!is_null($this->prd['mkt_product_id'])) {
					$matchOk['SkuId'] = $this->prd['mkt_sku_id'] ; 
				}
			}
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$matchOk['SkuId'];
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
			if ($this->responseCode != 200) {
				$notice = "Falha ao ler SKU do produto ".$this->prd['id']." id vtex:".$matchOk['SkuId']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true);
                echo $notice."\n";
                $this->log_data('batch', $log_name, $notice,"E");
                die;
			}
			$skuvtex  = json_decode($this->result, true);
			$vtexprodid = $skuvtex['ProductId'];
			$this->vtex_ref_id = $skuvtex['RefId'];		
		}
				
		// fez match então ficou ok e pode colocar o status_int = 2
        echo "Match realizado com sucesso! Id do produto local: ".$this->prd['id']."; Id do produto na Vtex: ".$vtexprodid." sku na vtex ".$matchOk['SkuId']."\n";

		$prd_to_integration['mkt_sku_id'] = $matchOk['SkuId']; 
		$prd_to_integration['mkt_product_id'] = $vtexprodid;

		$this->saveVtexUltEnvio($skumkt,$variant_num );
		if (!is_null($variant)) {
			// apaga o registro inicial criado na BlingMarcaTodosEnvio sem variação
			$todelete =  $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to,null);
			if ($todelete) {
				$this->model_integrations->removePrdToIntegration($todelete['id']); 
				if ($this->prd_to_integration['id'] == $todelete['id']) {
					$this->prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);;
				}
			}
		}
       
        $toSavePrd = [
            'prd_id' 			=> $this->prd['id'],
            'company_id' 		=> $this->prd['company_id'],
            'status'			=> 1,
            'status_int' 		=> 2,
            'date_last_int' 	=> $this->dateLastInt, 
            'skumkt' 			=> $skumkt,
            'skubling' 			=> $skumkt,
            'int_to' 			=> $this->int_to,
            'store_id' 			=> $this->prd['store_id'],
            'seller_id' 		=> $this->sellerId ,
            'approved' 			=> 1,
            'int_id' 			=> $this->integration_store['id'],
            'user_id' 			=> 0,
            'int_type' 			=> 0, 
            'variant' 			=> $variant_num,
            'mkt_product_id' 	=> $vtexprodid, 
            'mkt_sku_id' 		=> $matchOk['SkuId']
        ];
		if(isset($this->prd['approved_curatorship_at']) && !empty($this->prd['approved_curatorship_at'])){
            $toSavePrd['approved_curatorship_at'] = $this->prd['approved_curatorship_at'];
        }

        if(isset($this->prd_to_integration['approved_curatorship_at']) && !empty($this->prd_to_integration['approved_curatorship_at'])){
            $toSavePrd['approved_curatorship_at'] = $this->prd_to_integration['approved_curatorship_at'];
        }

		$PrdIntId = $this->model_integrations->createIfNotExist($this->prd['id'], $this->int_to, $variant_num, $toSavePrd);

        if (!$PrdIntId) {
            $notice = "Falha ao tentar gravar produto na tabela prd_to_integration.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        }
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
		
		$vtex_commercial_condition_id = $this->model_settings->getValueIfAtiveByName('vtex_commercial_condition_id');

		if ($vtex_commercial_condition_id) {
			if (is_null($skuvtex)) {
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$matchOk['SkuId'];
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
				if ($this->responseCode != 200) {
					$notice = "Falha ao ler SKU do produto ".$this->prd['id']." id vtex:".$matchOk['SkuId']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true);
					echo $notice."\n";
					$this->log_data('batch', $log_name, $notice,"E");
					die;
				}
				$skuvtex  = json_decode($this->result, true);
			}
			if ($skuvtex['CommercialConditionId'] != $vtex_commercial_condition_id) {				
				$skuvtex['CommercialConditionId'] = $vtex_commercial_condition_id;
				$this->updateOnlySkuVtex($prd_to_integration, $matchOk['SkuId'], $skuvtex, $variant);
			}
		}

		if (!$this->vtex_conectala) {
			if (!array_key_exists('skumkt', $prd_to_integration)) {
				$prd_to_integration['skumkt'] = $skumkt;
			}
			if (is_null($prd_to_integration['skumkt'])) {
				$prd_to_integration['skumkt'] = $skumkt;
			}
			if ($this->changeSkuSpecifications($prd_to_integration, $variant)) {
				if ($this->changeProductSpecifications($prd_to_integration)) {
					if ($this->update_sku_vtex) {
						// update do sku - varição
						$this->updateSkuVtex($prd_to_integration, $variant, false);					
					}
				}
			}
		}
	}

	function suggestionV1($skumkt, $vtex_prod_id, $spec, $prodspec, $images, $refid)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// Pega o refid.
		$ref_id = (trim($this->prd['ref_id']) ? $this->prd['ref_id'] : $refid);

		// Caso esteja forçando o ref_id, seta como sku_mkt.
		if($this->ref_id == 'FORCEREFID'){
			$ref_id = $skumkt;
		}

        $data = [
            'ProductName'                => $this->prd['name'],
			'ProductId'                  => $this->prd['id'],
			//'ProductId'                  => $vtex_prod_id,
			'ProductDescription'         => $this->prd['description'],
			'BrandName'                  => $this->prd['brandname'],
			'SkuName'                    => $this->prd['skuname'],
			'SellerId'                   => $this->sellerId ,
			'Height'                     => (float)$this->prd['altura'],
			'Width'                      => (float)$this->prd['largura'],
			'Length'                     => (float)$this->prd['profundidade'],
			'WeightKg'                   => (float)$this->prd['peso_bruto'],
			'RefId'                      => $ref_id, // obrigatório quando o EAN não for enviado
            'EAN'                        => ((trim($this->prd['EAN']) == '') ? null : $this->prd['EAN']),
			'SellerStockKeepingUnitId'   => $skumkt,
			'CategoryFullPath'           => str_replace(' > ','/',$this->prd['categoryname']),
			'SkuSpecifications'          => $spec,
			'ProductSpecifications'      => $prodspec,
			'Images'                     => $images,
			'MeasurementUnit' 			 => 'un',
			'UnitMultiplier'             => 1, 
			'AvailableQuantity'          => (int)$this->prd['qty'],
			'Pricing'					 => array(
				'Currency'				 => 'BRL',
				'SalePrice'   			 => (float)$this->prd['promotional_price'], 
				'CurrencySymbol' 	     => 'R$'
			), 
        ];
		
        $bodyParams  = json_encode($data);
		
		echo "Enviando sugestão de SKU."."\n";
		$url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$this->sellerId .'/'.$skumkt;
        $this->vtexHttpUrl($this->auth_data, $url,'PUT', $bodyParams, $this->prd['id'], $this->int_to, 'Suggestion');
	
		if ($this->responseCode == 400) {
			$result = json_decode($this->result);  
			if (isset($result->Message)) {
				if ($result->Message == 'Seller is inactive') {
					echo " A LOJA ESTÁ INATIVA NA VTEX \n";
					$this->errorTransformation($this->prd['id'],$skumkt, 'A Loja está inativa na Vtex. impossível enviar Suggestions.', 'Match de produto');
					return false;
				}
			} 			
		}

        if ($this->responseCode != 200) {
            $notice = "Falha no envio de sugestão de SKU do produto ".$this->prd['id']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true).' enviado: '.print_r($bodyParams,true);
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            die;
			return false;
        }
		return true; 
	}

	function suggestionV2($skumkt,  $spec, $prodspec, $images ) 
	{
        $data = [
            'BrandId'                    => (int)$this->prd['brandvtex'],
            'BrandName'                  => $this->prd['brandname'],
            'CategoryFullPath'           => str_replace(' > ','/',$this->prd['categoryname']),
            'CategoryId'                 => (int)$this->prd['categoryvtex'],
            'EAN'                        => array(
                ((trim($this->prd['EAN']) == '') ? null : $this->prd['EAN'])
            ),
            'Height'                     => (float)$this->prd['altura'],
            'Id'                         => null,
            'Images'                     => $images,
            'IsAssociation'              => false,
            'IsKit'                      => false,
            'IsProductSuggestion'        => false,
            'Length'                     => (float)$this->prd['profundidade'],
            'ListPrice'                  => (int)$this->prd['list_price']*100,
            'ModalId'                    => null,
            'Price'                      => (int)$this->prd['promotional_price']*100,   
            'ProductDescription'         => $this->prd['description'],
            'ProductId'                  => (!is_null($this->prd['mkt_product_id'])) ? $this->prd['mkt_product_id'] : null,
            'ProductName'                => (is_null($this->prd['product_name'])) ? $this->prd['name']: $this->prd['product_name'],
            'ProductSpecifications'      => $prodspec,
            'ProductSupplementaryFields' => null,
            'RefId'                      => (trim($this->prd['ref_id']) ? $this->prd['ref_id'] : $skumkt), // obrigatório quando o EAN não for enviado
            'SellerId'                   => $this->sellerId ,
            'SellerModifiedDate'         => null,
            'SellerStockKeepingUnitId'   => $skumkt,
            'SkuId'                      => null,
            'SkuName'                    => $this->prd['skuname'],
            'SkuSpecifications'          => $spec,
            'SkuSupplementaryFields'     => null,
            'SynonymousPropertyNames'    => null,
            'WeightKg'                   => (float)$this->prd['peso_bruto'],
            'Width'                      => (float)$this->prd['largura'],
        ];
		
        $bodyParams  = json_encode($data);
        $endPoint    = "api/catalog_system/pvt/sku/SuggestionInsertUpdatev2";
    	
		echo "Enviando sugestão de SKU."."\n";
	    $skuInserted = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'SuggestionV2');
		
		if ($this->responseCode == 400) {
			$result = json_decode($this->result);  
			if (isset($result->Message)) {
				if ($result->Message == 'Seller is inactive') {
					echo " A LOJA ESTÁ INATIVA NA VTEX \n";
					$this->errorTransformation($this->prd['id'],$skumkt, 'A Loja está inativa na Vtex. impossível enviar Suggestions.', 'Match de produto');
					return false;
				}
			} 			
		}

		if ($this->responseCode != 200) {
            $notice = "Falha no envio de sugestão de SKU do produto ".$this->prd['id']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true).' enviado: '.print_r($bodyParams,true);
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            die;
			return false;
        }
		return true; 
	}

	protected function getInformacoesTecnicas($attributesCustomProduct) {
		$html = '';
		foreach($attributesCustomProduct as $attributeCustomProduct) {
			$html .= '<div class="technical-item">
				<h3 class="technical-title">'.$attributeCustomProduct['name_attr'].'</h3>
				<p class="technical-description">'.$attributeCustomProduct['value_attr'].'</p>
			</div>
			<hr>';
		}
		return $html;
	}

	private function getProductImages($folder_ori, $path, $vardir = '')
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// Limpar para deixar somente as imagens da variação.
		if ($this->only_send_images_from_sku) {
			$this->fotos = [];
		}
		$folder = $folder_ori;
		if ($vardir != '') {
			$folder .= $vardir;
		}
		if ($folder == '') {
			return null;
		}
		$imagesData = [];
		$onBucket = $this->prd["is_on_bucket"];
		if ($onBucket) {

			$images = $this->bucket->getFinalObject('assets/images/' . $path . '/' . $folder);
			if (!$images['success']) {
				return null;
			}
			$images = $images['contents'];
			foreach ($images as $foto) {
				$path_parts = pathinfo($foto['url']);
				$this->fotos[] = $path_parts['filename'];	
				$data = [
					'ImageUrl'  => $foto['url'],
					'ImageName' => $path_parts['filename'],
					'FileId'    => null
				];

				array_push($imagesData, $data);
			}
			
		}else{

			echo 'Lendo imagens em assets/images/'.$path.'/'.$folder."\n";
			if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder)) {
				return null;
			}
		
			$images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
			
			if (!$images) {
				return null;
			}
			if (count($images) <= 2) {
				return false;
			}
			$imagesData = array();
			
			foreach($images as $foto) {
				if (($foto!=".") && ($foto!="..") && ($foto!="")) {
					if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder.'/'.$foto)) {
						$path_parts = pathinfo($foto);
						$this->fotos[] = $path_parts['filename'];	
						$image_url = base_url('assets/images/'.$path.'/' . $folder.'/'. $foto);
						$image_url = str_replace('http://','https://',$image_url); // vtex só aceita https
						$data = [
							'ImageUrl'  => str_replace('conectala.tec.br','conectala.com.br',$image_url), 
							'ImageName' => $path_parts['filename'],
							'FileId'    => null
						];
						
						array_push($imagesData, $data);
					}
					
				}
			}

		}
				
        return $imagesData;
    }
	
	private function skuToMatch($skumkt, $prdSpec, $skuSpec, $variant_num =null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "pegando o score do marketplace"."\n";
		$this->getScore();
		echo " Score minimo ".$this->score_min."\n";
		
        echo "Pegando version do SKU."."\n";
        $version = $this->getVersion($skumkt);
		echo "version ". $version."\n";
        if ($version == false) {
            return array('success' => false, 'error' => 'Não achou uma versão da suggestion');
        }
		
		if (is_null($this->prd['mkt_product_id'])) { // processo normal de produto novo ou tentando macth pela primeira vez
			$macthType = 'newproduct';
			$ref_id = $this->prd['ref_id'];
			$ean = $this->prd['EAN'];
			
			if ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto
				if ((is_null($this->prd['ref_id'])) || (trim($this->prd['ref_id']) == '')) {
					$ref_id = $this->prd['id'];
					if (!is_null($variant_num)) {
						$ref_id .= '-'.$variant_num;
					}
					$macthType = 'productMatch';
					$ean = '';
				}
			}
			elseif (trim($this->prd['EAN']) == '') {
				if ((is_null($this->prd['ref_id'])) || (trim($this->prd['ref_id']) == '')) {
					$ref_id = $skumkt;
					$macthType = 'productMatch';
				}
			}
			if ($this->ref_id == 'FORCEREFID') {
				$ref_id = $skumkt;
			}
			
			if(!$this->auto_approve){
				$this->score_min=70;
			}

			if(empty($prdSpec)){
				$prdSpec = null;
			}
			if(empty($skuSpec)){
				$skuSpec = null;
			}

			$data = [
	            'matcherId' 			=> 'vtex-matcher',
	            'matchType' 			=> 'productMatch',
	            'score' 				=> $this->score_min,
	            'productRef' 			=> $this->prd['ref_id'],
	            'product' => [
	            	'name' 				=> $this->prd['name'],
	            	'description' 		=> $this->prd['description'],
	            	'categoryId'  		=> (int)$this->prd['categoryvtex'], 
	            	'brandId'    		=> $this->prd['brandvtex'], 
	            	'specifications'	=> $prdSpec
	            ],
	            'SKU' => [
	                'name' 				=> $this->prd['skuname'],
	                'eans' 				=> [
	                    $this->prd['EAN']
	                ],
	                'refId'  			=> $ref_id,
	                'height' 			=> $this->prd['altura'],
	                'width'  			=> $this->prd['largura'],
	                'length' 			=> $this->prd['profundidade'],
	                'weight' 			=> $this->prd['peso_bruto'],
	                'Images' 			=> $this->prd['vteximages'], 
	                'unitMultiplier'  	=> '1.0000',   
	                'measurementUnit' 	=> 'un', 
	                'specifications' 	=> $skuSpec
	            ]
	        ];
	    }
		else { // Já tem um catago da Vtex e vou forçar a associação 
			$data = [
	            'matcherId' 			=> '1',
	            'matchType' 			=> 'itemMatch',
	            'score' 				=> $this->score_min,
	            'skuRef' 				=> $this->prd['mkt_sku_id'],
	            'productRef' 			=> $this->prd['mkt_product_id'],
	            'Product' => [
	            	'name' 				=> (!is_null($this->prd['product_name'])) ? $this->prd['product_name'] : $this->prd['name'],
	            	'description' 		=> $this->prd['description'],
	            	'brandId' 			=> (int)$this->prd['brandvtex'], 
	            	'categoryId' 		=> (int)$this->prd['categoryvtex'],
					'specifications'    => null,
	            ], 
	            'SKU' => [
	                'name' 				=> (!is_null($this->prd['sku_name'])) ? $this->prd['sku_name'] : $this->prd['name'],
	                'eans' 				=> [
	                    $this->prd['EAN']
	                ],
	                'refId' 			=> $this->prd['ref_id'],
	                'height' 			=> $this->prd['altura'],
	                'width' 			=> $this->prd['largura'],
	                'length' 			=> $this->prd['profundidade'],
	                'weight' 			=> $this->prd['peso_bruto'],
	                'unitMultiplier' 	=> '1.0000',
	                'measurementUnit' 	=> 'un',
					'specifications'    => null,
	            ]
	        ];
		}
		echo print_r($data,true)."\b";
        $bodyParams = json_encode($data);

        echo "Concluindo match."."\n";
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$this->sellerId .'/'.$skumkt.'/versions/'.$version.'/matches/1';
		
        $this->vtexHttpUrl($this->auth_data, $url,'PUT', $bodyParams, $this->prd['id'], $this->int_to, 'Match');

		$result = json_decode($this->result);
		if ($this->responseCode == 400) { 
			if ((is_null($result->error->message)) && ($result->error->code == 0)) {  /// nem sempre vem o Pending 
				return (array('success' => 'Pending', 'ProductId' => null, 'SkuId' =>null)) ;
			}								
			if (($result->error->message == 'Only suggestion with status accepted or denied can be to approve') || ($result->error->message == 'Only suggestion with status pending or denied can be approved')) {
				echo "Deu erro .".$result->Error->Message."\n";
       			$url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$this->sellerId .'/'.$skumkt;
		
				$this->vtexHttpUrl($this->auth_data, $url,'DELETE', '', $this->prd['id'], $this->int_to, 'Match');
				if (($this->responseCode >= 200) && ($this->responseCode <=204)) { 
					echo "vou morrer para que fique na fila e recomece com uma nova sugestão\n";
					die;
				}
				else {
					return (array('success' => false, 'error' => 'Error ao deletar a Suggestion')) ;
				}
			}
		}
		if ($this->responseCode !== 200) {
			if (is_null($result)) {
				$error_msg = 'Erro não previsto no Match da Vtex';
			}
			else { 			
				$error_msg = $result->error->message ? $result->error->message : (isset($result->message) ? $result->message : null);  
				if ($error_msg == 'Association can be redone') {
					echo "Mandar remover a associação na Vtex \n";		
				};
				if (is_null($error_msg)) {
					$error_msg = 'Erro não previsto no Match da Vtex';
				}
			}
			return (array('success' => false, 'error' => $error_msg)) ;					
		}
		/* Resposta com Erro 
				object(stdClass)#40 (1) {
				  ["Error"]=>
				  object(stdClass)#42 (2) {
				    ["Code"]=>
				    int(2)
				    ["Message"]=>
				    string(32) "The property BrandId is required"
				  }
				} */
		/* Resposta com produto e SKU Novo : 
			object(stdClass)#42 (7) {
			  ["Operation"]=>
			  string(10) "NewProduct"
			  ["Message"]=>
			  string(85) "Product insertion successfully executed. Seller: MKTP462;\n Seller Item id: VarPAzul;"
			  ["Details"]=>
			  string(26) "Product id: 27; Sku id: 30"
			  ["ProductId"]=>
			  string(2) "27"
			  ["SkuId"]=>
			  string(2) "30"
			  ["SuggestionStatus"]=>
			  string(8) "Accepted"
			  ["Suggestion"]=>
			  NULL
			}
		 * 
		 * Resposta com match de produto
		 * "
			  ["Details"]=>
			  string(0) ""
			  ["ProductId"]=>
			  NULL
			  ["SkuId"]=>
			  string(2) "30"
			  ["SuggestionStatus"]=>
			  string(8) "Accepted"
			  ["Suggestion"]=>
			  NULL
			}
		 * 
		 * 
		 *Se não dá match.... 
		 * 
		 object(stdClass)#46 (7) {
			  ["Operation"]=>
			  string(17) "InsufficientScore"
			  ["Message"]=>
			  string(62) "Insufficient score for approval. Suggestion status: 'Pending'."
			  ["Details"]=>
			  string(46) "Suggestion: 2690/1756_cama01. Matches Updated."
			  ["ProductId"]=>
			  NULL
			  ["SkuId"]=>
			  NULL
			  ["SuggestionStatus"]=>
			  string(7) "Pending"
			  ["Suggestion"]=>
			  NULL
			}
		 * 
		
		** se foi rejeitado 
		object(stdClass)#100 (7) {
		  ["Operation"]=>
		  string(17) "InsufficientScore"
		  ["Message"]=>
		  string(62) "Insufficient score for approval. Suggestion status: 'Pending'."
		  ["Details"]=>
		  string(51) "Suggestion: 2690/3066_0_NovoMundo. Matches Updated."
		  ["ProductId"]=>
		  NULL
		  ["SkuId"]=>
		  NULL
		  ["SuggestionStatus"]=>
		  string(6) "Denied"
		  ["Suggestion"]=>
		  NULL
		}
		 * */

		 return (array(
			'success' => isset($result->suggestionStatus) ? $result->suggestionStatus : $result->SuggestionStatus, 
		   	'ProductId' => isset($result->ProductId) ? $result->ProductId : $result->productId,
		   	'SkuId' => isset($result->SkuId) ? $result->SkuId : $result->skuId )) ;
    }

    private function getVersion($sku)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$this->sellerId .'/'.$sku.'/versions';

        $this->vtexHttpUrl($this->auth_data, $url, 'GET',null, $this->prd['id'], $this->int_to, 'Última versão de sugestão');

		if ($this->responseCode !== 200) {
			$notice = "Falha ao tentar obter version do SKU. httpcode = ".$this->responseCode;
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			return false;
		}

        $result = json_decode($this->result);
		
		foreach($result as $version) {
			if (isset($version->IsLatest)) {
				if  ($version->IsLatest) {
					return $version->VersionId;
				}
			}
			if (isset($version->isLatest)){
				if ($version->isLatest) {
					return $version->versionId;
				}
			}				
		}
		if($result[0]->VersionId){
			return $result[0]->VersionId;
		}
		return $result[0]->versionId;
        
    }
	
	private function changeProductSpecifications($prd_to_integration)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if (!$this->update_product_specifications) {
			echo "Alterações de atributos de produtos desligada \n";
			return true; 
		}

		// Pego as specificações do produto
		$endPoint   = 'api/catalog_system/pvt/products/'.$prd_to_integration['mkt_product_id'].'/specification';
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Specification');
		$specifications  = json_decode($this->result, true);
		
		echo "Especificações atuais\n";
		echo print_r($specifications,true)."\n";
		if ((is_null($specifications)) || (!is_array($specifications))) {
			$specifications = array();
		}
		// Busco os atributos específicos do produto para este marketplace 
		$prodspecs = array();
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoryvtex'],$this->int_to);
		foreach($atributosCat as $atributoCat) {
			if ($atributoCat['variacao'] == 1 ) {
				continue;
			}
			if (is_null($this->prd['product_catalog_id'])) {  // pego os atributos que vem do produto.
				$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to);
			} else { // as atributos vem de outra tabela se usa produto de catálogo.
				$atributo_prd = $this->model_products_catalog->getProductAttributeByIdIntto($this->prd['product_catalog_id'],$atributoCat['id_atributo'],$this->int_to);
			}
			if ($atributo_prd) {
				$valor_id = null;
				$valores = json_decode($atributoCat['valor'],true);
				if (is_array($valores)) {
					foreach($valores as $valor) {
						if ($valor['IsActive']) { 
							if (trim(strtoupper($valor['FieldValueId'])) ==  trim(strtoupper($atributo_prd['valor']))) {
								$valor_id = $atributo_prd['valor'];
								$atributo_prd['valor'] = $valor['Value'];
								break;
							}
						}
					}
				}
				if ((is_null($valor_id)) && (trim($atributo_prd['valor']) == '')) {
					echo "Não achei nenhum valor ou texto para ".$atributoCat['nome']."\n";
				}
				else {
					echo " Atributo: ". $atributoCat['nome']. " valor_id: ".$valor_id." text: ".trim($atributo_prd['valor'])."\n"; 
					$prodspecs[] = array(
						'Id' =>  0, 
						'Name' => $atributoCat['nome'], 
						'Value' => array(trim($atributo_prd['valor'])), 			
						'FieldId' => $atributo_prd['id_atributo'], 
						'FieldValueId' => $valor_id,
						'Text' => trim($atributo_prd['valor']),
						'Novo' => true, // Flag para verificar se todos já estão criados na vtex
					);
				}
			}
			else {
				if (((int)$atributoCat['obrigatorio'] == 1) && ((int)$atributoCat['variacao'] == 0)) {  // não está preenchido mas é obrigatório 
					$msg= 'Atributo obrigatório não preenchido : '.$atributoCat['nome'];
					echo 'Produto '.$this->prd['id']." ".$msg."\n";
					$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $msg, 'Atualização de Atributos');
					return false;
				}
			}
		}
		var_dump($prodspecs);
		$spec_changed = false; 
		foreach ($specifications as $skey => $specification) { // verifica se alterou alguma especificação
			echo "ESPECIFICACAO ". $specification['Id']. "\n";
			$exist = false;
			foreach($prodspecs as $key => $prodspec) {
				if ($specification['Name'] == $prodspec['Name']) {
					$prodspecs[$key]['Novo'] = false; // Já está criado na vtex
					$exist = true;
					if ($specification['Value'][0] !== $prodspec['Value'][0]) { // mudou o valor do atributo
						$spec_changed = true;
						break 2;
					}
				}
			}
			// * incluir aqui os atributos que não são de categoria e verificar se esta especificação está lá 
			if (!$exist) {  // Não achou esta categoria, então removeram do sistema
				$spec_changed = true; 
				break;
			}
		}
		foreach($prodspecs as $prodspec) { //verifico se todos os atributos foram encontrados na Vtex
			if ($prodspec['Novo']) {
				$spec_changed = true; 
				break;
			}
		}
		echo "Analise das especificações\n";
		var_dump($prodspecs);
		if ($spec_changed) { // mudou algum atributo do produto na vtex 
		    if (count($specifications) > 0) { // apago tudo antigo
				$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'].'/specification';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'DELETE', null, $this->prd['id'], $this->int_to, 'Deletar Specification');
				if ($this->responseCode !== 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no DELETE '.$endPoint.' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
			}
			sleep(10);
			echo "Criando Novas Especificações\n";
			foreach($prodspecs as $key => $prodspec) { // cria novas especificações 
				$data=array(
					'FieldId' => $prodspec['FieldId'],
					'FieldValueId' =>  $prodspec['FieldValueId'],
					'Text' => $prodspec['Text']
				);
				var_dump($data);
				$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'].'/specification';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($data), $this->prd['id'], $this->int_to, 'Alterar Specification');
				if ($this->responseCode == 400) { 
					echo "A especificação ".$prodspec['Text']." não existe mais neste tipo de produto\n";
					continue;
				}
				if ($this->responseCode == 409) { 
					echo "A especificação ".$prodspec['Text']." já existe no produto\n";
					continue;
				}
				if ($this->responseCode !== 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no POST '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					continue;
				}
			}
			
		}
		return true;
	}

	private function updateProductVtex($prd_to_integration, $variant) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// ler o produto atual
		$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'];
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Produto');
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$this->prd_vtex  = json_decode($this->result);
		
		$ref_id = $this->prd_vtex->RefId; 
		if (is_null($ref_id)) {
			if ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto - solicitação Ortobom
				$ref_id = $this->prd['id'];	
			}  
			else {
				$ref_id  = $this->prd['id'].'_'.$this->int_to;
			}
		}
		$prodvtex = array(
			'name' 						=> $this->prd['name'], 
			'CategoryId' 				=> (int)$this->prd['categoryvtex'], 
			'BrandId' 					=> (int)$this->prd['brandvtex'], 
			'LinkId' 					=> $this->prd_vtex->LinkId,  
			'RefId' 					=> $this->prd_vtex->RefId,
			'IsVisible' 				=> true,
			'Description' 				=> $this->prd['description'],
			'DescriptionShort' 			=> $this->prd_vtex->DescriptionShort,
			'ReleaseDate' 				=> $this->prd_vtex->ReleaseDate,
			'KeyWords' 					=> $this->prd_vtex->KeyWords,
			'Title' 					=> $this->prd['name'],
			'IsActive'					=> Model_products::isActive($this->prd),
			'TaxCode' 					=> $this->prd_vtex->TaxCode,
			'MetaTagDescription' 		=> $this->prd_vtex->MetaTagDescription,
			'SupplierId'				=> $this->prd_vtex->SupplierId,
			'ShowWithoutStock' 			=> $this->prd_vtex->ShowWithoutStock,
			'AdWordsRemarketingCode' 	=> $this->prd_vtex->AdWordsRemarketingCode,
			'LomadeeCampaignCode' 		=> $this->prd_vtex->LomadeeCampaignCode,
			'Score' 					=> $this->prd_vtex->Score,
		);

		if ((is_null($this->prd_vtex->RefId ) || ($this->prd_vtex->RefId =='')) && ($this->ref_id == 'FORCEREFID') ) {  // solicitado pela Polishop
			$prodvtex['RefId']  = $this->prd['id'].'_'.$this->int_to;
		}

		// AQUI ALTERA O PRODUTO e NÂO O SKU 
		$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'];
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($prodvtex), $this->prd['id'], $this->int_to, 'Alterar Produto');
		$this->prd_vtex  = json_decode($this->result);
		var_dump($this->prd_vtex);
		$msg_ref_dup = 'There is already a product created with the same RefId for the Product id';

		if ($this->responseCode == 409) {
			$msg_error = print_r($this->result,true);
			if (substr($msg_error,1,strlen($msg_ref_dup)) == $msg_ref_dup) {	
				$variant_num = 0;
				if (!is_null($variant)) {
					$variant_num = $variant['variant'];
				}
				$prodvtex['RefId']  = $this->prd['id'].'_'.$variant_num.'_'.$this->int_to;
				$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'];
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($prodvtex), $this->prd['id'], $this->int_to, 'Alterar Produto Mudando REF_ID');
				$this->prd_vtex  = json_decode($this->result);
				var_dump($this->prd_vtex);
				if ($this->responseCode == 409) {
					$msg_error = print_r($this->result,true);
					if (substr($msg_error,1,strlen($msg_ref_dup)) == $msg_ref_dup) {	
						$prodvtex['RefId']  = $this->prd['id'].'_'.$variant_num.'_'.$this->int_to."_".date("s"); 
						$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'];
						$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($prodvtex), $this->prd['id'], $this->int_to, 'Alterar Produto Mudando REF_ID Novamente');
						$this->prd_vtex  = json_decode($this->result);
						var_dump($this->prd_vtex);
					}
				}
			}
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		
		// Acerta as políticas de preço / site 
		foreach($this->tradesPolicies as $tradePolicy) {
			$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'].'/salespolicy/'.$tradePolicy;
			$tradepol = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($prodvtex), $this->prd['id'], $this->int_to, 'Alocando na Trade Policy '.$tradePolicy);

			if ($this->responseCode !== 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
		}

		if ($this->informacoesTecnicas) {
            $atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoryvtex'], $this->int_to);
            foreach ($atributosCat as $atributoCat) {
                if ($this->informacoesTecnicas == $atributoCat['nome']) {
                    $attributesCustomProduct = $this->model_products->getAttributesCustomProduct($this->prd['id']);
                    $valueInformacoesTecnicas = $this->getInformacoesTecnicas($attributesCustomProduct);

                    if ($valueInformacoesTecnicas !== false) {
                        $dataProdutosAtributos = array(
                            'id_product' => $this->prd['id'],
                            'id_atributo' => $atributoCat['id_atributo'],
                            'valor' => $valueInformacoesTecnicas,
                            'int_to' => $this->int_to
                        );

                        $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($dataProdutosAtributos);
                    }
                }
            }
        }

		// agora troco as especificações do produto 
		return $this->changeProductSpecifications($prd_to_integration);
	}

	private function updateSkuVtex($prd_to_integration, $variant, $disable)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// Agora posso pegar o SKU de verdade  
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'];
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
		if ($this->responseCode != 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$sku_vtex  = json_decode($this->result, true);

		// alteramos agora o sku 
		$skuvtex= array( 
			'Id' 					=> $prd_to_integration['mkt_sku_id'], 
			'ProductId' 			=> $prd_to_integration['mkt_product_id'],
			'IsActive' 				=> !$disable, 
			'ActivateIfPossible' 	=> !$disable,
            'Name' 					=> ($this->prd['has_variants']!= '') ? $this->prd['name']." ".str_replace(";", " / ", $variant['name']) : $this->prd['name'],
			'PackagedHeight' 		=> (float)$this->prd['altura'],
			'PackagedLength' 		=> (float)$this->prd['profundidade'],
			'PackagedWidth' 		=> (float)$this->prd['largura'],
			'PackagedWeightKg' 		=> (float)$this->prd['peso_bruto'],
			'Height' 				=> (float)$this->prd['actual_height'],
			'Length' 				=> (float)$this->prd['actual_depth'],
			'Width' 				=> (float)$this->prd['actual_width'],
			'WeightKg' 				=> (float)$this->prd['peso_liquido'],
			'CubicWeight'   		=> $sku_vtex['CubicWeight'],
			'IsKit'					=> $sku_vtex['IsKit'],
			'RewardValue'			=> $sku_vtex['RewardValue'],
			'EstimatedDateArrival'	=> $sku_vtex['EstimatedDateArrival'],
			'ManufacturerCode'		=> $sku_vtex['ManufacturerCode'],
			'CommercialConditionId' => $sku_vtex['CommercialConditionId'], 
			'MeasurementUnit'		=> $sku_vtex['MeasurementUnit'],
			'UnitMultiplier' 		=> $sku_vtex['UnitMultiplier'], 
			'ModalType'				=> $sku_vtex['ModalType'],
			'KitItensSellApart'		=> $sku_vtex['KitItensSellApart'],
			'Videos'				=> $sku_vtex['Videos'],
			'RefId'					=> $sku_vtex['RefId'], 
		);
		
		$commercialConditionId = $this->model_settings->getValueIfAtiveByName('vtex_commercial_condition_id');
		if ($commercialConditionId) {
			$skuvtex['CommercialConditionId'] = (int)$commercialConditionId;
		}
		
		if (is_null($sku_vtex['RefId']) || ($sku_vtex['RefId'] == '') ){
			if ($this->ref_id == 'FORCEREFID')  { // rick - solicitação polishop 
				$skuvtex['RefId'] = $prd_to_integration['skumkt']; 
			}elseif ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto - solicitação Ortobom
				$ref_id = $this->prd['id'];
				if (!is_null($variant)) {
					$ref_id .= '-'.$variant['variant'];
				}
				$skuvtex['RefId'] = $ref_id; // Força o Id do produto para a Ortobom....
			}
			elseif (trim($this->prd['EAN']) == '') {
				if ((is_null($this->prd['ref_id'])) || (trim($this->prd['ref_id']) == '')) {
					$skuvtex['RefId'] = $prd_to_integration['skumkt'];  // acerto para mandar o refid quando não tem EAN; 
				}
			}
		}

		// AQUI ALTERA O SKU e NÂO O PRODUTO 
		$this->updateOnlySkuVtex($prd_to_integration, $prd_to_integration['mkt_sku_id'], $skuvtex, $variant);

		$this->changeSkuSpecifications($prd_to_integration, $variant);
		
	}

	private function updateOnlySkuVtex($prd_to_integration, $mkt_sku_id, $skuvtex, $variant)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo "UPDATE ONLY \n";
	
		// AQUI ALTERA O SKU e NÂO O PRODUTO 
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$mkt_sku_id;
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($skuvtex), $this->prd['id'], $this->int_to, 'Alterar SKU');
		$sku_vtex  = json_decode($this->result);
		if ($this->responseCode == 409) { 
			if (strpos($this->result, 'Sku can not be created because the RefId is registered in Sku id') >0 ){
				echo "Já tem um SKU com o mesmo REFID :".$skuvtex['RefId']." ".print_r($this->result,true);
				echo $erro."\n";
				$skuvtex['RefId'] = $skuvtex['RefId'] .'-1';
				echo "Tentando novamente com o REF ".$skuvtex['RefId']."\n";
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'];
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($skuvtex), $this->prd['id'], $this->int_to, 'Alterar SKU');
				$sku_vtex  = json_decode($this->result);
			}
		}
		if ($this->responseCode == 400) { 
			if (
                strpos($sku_vtex->Message, 'could not be set as active because it does not have any files associated to it yet.') > 0 ||
                (
                    !isset($sku_vtex->Message) &&
                    is_string($this->result) &&
                    strpos($this->result, 'could not be set as active because it does not have any files associated to it yet.') > 0
                )
            ) {
				$erro =  "Produto ".$this->prd['id']." SkuVtex: ".$prd_to_integration['mkt_sku_id']." sem Imagem na VTEX";
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				$this->changeSkuImage($prd_to_integration,$variant); 
				return; 

			}
		}
		if ($this->responseCode == 409) { 
			if (strpos($this->result, 'Sku can not be created because the RefId is registered in Sku id') >0 ){
				echo "Já tem um SKU com o mesmo REFID :".print_r($this->result,true)." \n Deu de novo, melhor morrer";
				echo $erro."\n";			
				$this->log_data('batch',$log_name, $erro ,"E");				
				die;
			}
		}

		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' enviado '.print_r(json_encode($skuvtex),true).' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}

		
	}
	
	private function changeSkuSpecifications($prd_to_integration, $variant)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if (!$this->update_sku_specifications) {
			echo "Alterações de atributos de variações (SKU) desligada \n";
			return true; 
		}
		
		// Pego as specificações do produto
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler SKU Specification');
		$specifications  = json_decode($this->result, true);
		
		echo "Especificações atuais do SKU\n";
		if (is_null($specifications)) {
			$specifications = array();
		}
		else {
			foreach($specifications as $key => $value) {
				var_dump($specifications[$key]);
				if (is_array($specifications[$key])) {
					$specifications[$key]['deleteme'] = true; 
					if (!isset($specifications[$key]['deleteme'])) {
						echo " *************************** \n";
						echo "deu ruim\n";
						die;
					}
				}
			}
		}
		echo print_r($specifications,true)."\n";
		
		// Busco os atributos específicos do produto para este marketplace 
		
		$prodspecs = array();
		$variants = explode(';', $this->prd['has_variants']);
		if (isset($variant['name'])) {
			$variants_value  = explode(';', $variant['name']);
		}
		$found_variant = array();
		foreach( $variants as $key => $value) {

			if ($value == self::SIZE_DEFAULT) { $value = $this->variant_size; }
			if ($value == self::COLOR_DEFAULT) { $value = $this->variant_color; }
			if ($value == self::VOLTAGE_DEFAULT) { $value = $this->variant_voltage; }

			$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryvtex'],ucfirst(strtolower($value)),$this->int_to, !empty($this->prd['has_variants']));
			$valor_id = null;
			$field_id=0;
			if ($atributoCat) {
				if ($atributoCat['variacao'] == 0) {
					$notice = "Esta categoria não aceita variação por ".$value;
					$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
		            return;
				}
				$field_id = $atributoCat['id_atributo'];
				$valores = json_decode($atributoCat['valor'],true);
				$val_accepts = '  ';
				$cnt_val = 0; 
				foreach($valores as $valor) {
					if ($valor['IsActive']) { 
						if ($cnt_val++ < 10) {
							$val_accepts .= "'".$valor['Value']."', ";
						}		
						if (trim(strtoupper($valor['Value'])) ==  trim(strtoupper($variants_value[$key]))) {
							$valor_id = (int)$valor['FieldValueId'];
							break;
						}
					}
				}
				if ($field_id!=0) {
					if (is_null($valor_id)) { 
						$notice = "Esta categoria não aceita o valor ".$variants_value[$key]." para a variação ".$value;
						$notice .= ". Alguns Valores aceitos: ".trim(substr($val_accepts,0,-2))."\n"; 
						echo $notice."\n";
						$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
						return;
					}
					$prodspecs[] = array(
						'FieldId' 		=> (int)$atributoCat['id_atributo'],
						'FieldValueId' 	=> $valor_id,
						'Name'  		=> $atributoCat['nome'],
					);
					$found_variant[] = $atributoCat['nome']; 
				}
				
			}
		}

		// Agora vejo se algum atributo deveria ser variação mas não foi. 
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoryvtex'],$this->int_to);
		foreach($atributosCat as $atributoCat) {
			if ($atributoCat['variacao'] == 1) {
				if (in_array($atributoCat['nome'],$found_variant)) { // essa variante apareceu antes. 
					continue; 
				}
			    if (is_null($this->prd['product_catalog_id'])) {  // pego os atributos que vem do produto.
					$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to);
				} else { // as atributos vem de outra tabela se usa produto de catálogo.
					$atributo_prd = $this->model_products_catalog->getProductAttributeByIdIntto($this->prd['product_catalog_id'],$atributoCat['id_atributo'],$this->int_to);
				}
				var_dump($atributo_prd);
				if ($atributo_prd) {   // o atributo está preenchido, vou pegar seus valores
					$valor_id = null;
					$valores = json_decode($atributoCat['valor'],true);
					foreach($valores as $valor) {
						if ($valor['IsActive']) { 
							if (trim(strtoupper($valor['FieldValueId'])) ==  trim(strtoupper($atributo_prd['valor']))) {
								$valor_id = array((int)$atributo_prd['valor']);
								$atributo_prd['valor'] = $valor['Value'];
								$prodspecs[] = array(
									'FieldId' 		=> (int)$atributoCat['id_atributo'],
									'FieldValueId'  => (int)$valor['FieldValueId'],
								);
								break ;
							}
						}
					}
				}
				else {  // não achou nenhum atributo para variação
					if ((int)$atributoCat['obrigatorio'] == 1) { // a variação é obrigatória.
						$notice = "Variação ou Atributo obrigatório não preenchido: ".$atributoCat['nome'];
						echo $notice."\n";
						$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
						return;
					}
				}
			}
		}

		echo "Especificações que deveria ter\n";
		echo print_r($prodspecs,true)."\n";
		
		foreach($prodspecs as $prodspec) {
			$achou = false;
			foreach($specifications as $specification) {
				$specifications[$key]['deleteme'] = true; 
				if (!is_array($specification)) {					
					continue;
				}
				if (!array_key_exists('FieldId',$specification)) {
					continue;
				}
				if (!array_key_exists('FieldId',$prodspec)) {
					continue;
				}
				if ($prodspec['FieldId'] == $specification['FieldId']) {  // já existe na Vtex 
					$specifications[$key]['deleteme'] = false; 
					$achou = true; 
					if ($prodspec['FieldValueId'] != $specification['FieldValueId']) { // valor está diferente, então tem que trocar
						$data=array(
							'Id'			=> $specification['Id'],
							'SkuId'			=> $specification['SkuId'],
							'FieldId' 		=> $prodspec['FieldId'],
							'FieldValueId' 	=> $prodspec['FieldValueId']
						);
						$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
						$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($data), $this->prd['id'], $this->int_to, 'Alterar SKU Specification');
						if ($this->responseCode == 400) {
							$sku_vtex  = json_decode($this->result);
							if ($sku_vtex->Message == 'You can only enter Sku Specification in Combo (type 5) and Radio (type 6), Field  is of the type 7'){
								$notice = "Variação ou Atributo: ".$prodspec['Name']." com o tipo (Type 7) errado na Vtex nesta categoria e deve ser Combo (type 5) ou Radio (type 6). Favor, abrir chamado com o administrador do Marketplace para corrigir a Vtex";
								echo $notice."\n";
								$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
								return;
							}
						}
						if ($this->responseCode !== 200) {
							$erro = 'Erro httpcode: '.$this->responseCode.' no PUT '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
							echo $erro."\n";
							$this->log_data('batch',$log_name, $erro ,"E");
							die;
						}
					}
				}
			}
			if (!$achou) { // Não achou, tem que criar
				$data=array(
					'FieldId' 		=> $prodspec['FieldId'],
					'FieldValueId' 	=> $prodspec['FieldValueId'],
				);
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($data), $this->prd['id'], $this->int_to, 'Criar SKU Specification');
				if ($this->responseCode == 400) {
					$sku_vtex  = json_decode($this->result);
					if ($sku_vtex->Message == 'You can only enter Sku Specification in Combo (type 5) and Radio (type 6), Field  is of the type 7'){
						$notice = "Variação ou Atributo: ".$prodspec['Name']." com o tipo (Type 7) errado na Vtex nesta categoria e deve ser Combo (type 5) ou Radio (type 6). Favor, abrir chamado com o administrador do Marketplace para corrigir a Vtex";
						echo $notice."\n";
						$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
						return;
					}
				}
				if ($this->responseCode !== 200) {
					$erro = 'Erro httpcode: '.$this->responseCode.' no POST '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
			}
		}
		foreach($specifications as $specification) {
			continue ; // rick - Não consegui testar abaixo e não sei se deve ocorrer... 
			if ($specification['deleteme']) { // sumiu aqui, então remove da Vtex
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification/'.$specification['Id'];
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'DELETE', null, $this->prd['id'], $this->int_to, 'Removendo SKU Specification');
				if ($this->responseCode !== 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no DELETE '.$endPoint.' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				
			}
		}
	}
	
	private function changeSkuImage($prd_to_integration,$variant)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// pego as fotos atuais
		// Monto array com as imagens do produto
		$vardir = '';
		// pego as fotos do pai 
		$this->fotos = array(); 
		
		$images_var = null;
		$images = $this->getProductImages($this->prd['image'], $this->pathImage,  $vardir);
		if  (!is_null($variant)){
			$imagesVar = array();
			if ($this->pathImage == 'product_image') {
				if (!is_null($variant['image']) && trim($variant['image'])!='')	{
					$vardir = '/'.$variant['image'];
					$images_var = $this->getProductImages($this->prd['image'], $this->pathImage,  $vardir);
				}
			}
			else { // produto de catálogo
				$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$variant['variant'] ); 
				if ($var_cat) {
					$images_var = $this->getProductImages($var_cat['image'], $this->pathImage,  '');
				}
			}
			if (is_array($images_var)) {
                $images = array_merge($images_var, $images);  // junto as imagens da variação primeiro e depois a do pai
                if ($this->only_send_images_from_sku) {
                    $images = $images_var;
                }
			}
		} 
		
		if (empty($images)){
			//$sql = "UPDATE queue_products_marketplace SET status=3 WHERE id=?";
			//$query = $this->db->query($sql, array($this->queue_id));
			$erro = 'Sumiram as fotos do produto';
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return; 
		}

		echo "Verificando imagens da Vtex\n";
		// vou pegar as imagens
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/file';
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Listar Imagens');
		$sku_files  = json_decode($this->result);
		if ($this->responseCode == 404) { 
			$erro = 'Local de imagens não encontrado na Vtex httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			$sku_files= array();
		} 
		elseif ($this->responseCode !== 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$alterou_fotos = (count($sku_files) !== count($this->fotos));
		if (!$alterou_fotos) {
			foreach($sku_files as $file) {
				if (!in_array($file->Name, $this->fotos)) {
					$alterou_fotos = true;
					echo "Não achei a  foto ".$file->Name." em ".print_r($this->fotos,true)."\n"; 
					break;
				}
			}
		}
		echo "Fotos na vtex=".count($sku_files)." Fotos aqui=".count($this->fotos)."\n";
		if ($alterou_fotos) {
			echo "Removendo fotos\n";
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/file';
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'DELETE', null, $this->prd['id'], $this->int_to, 'Deletar Imagens');
			if (($this->responseCode !== 200) && ($this->responseCode !== 202) && ($this->responseCode !== 204)) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' no DELETE chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}

			$principal = true; 
			$cnt = 0;
			foreach($images as $image){
				echo "Enviando imagem ".$image['ImageName']."\n";
				$path_parts = pathinfo($image['ImageName']);
				$data = array(
					'IsMain' => $principal,
					"Label" => "",
					'Name' => $path_parts['filename'],
					"Text" => null,
					'Url' => $image['ImageUrl'],
				);
				$principal = false;
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/file';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($data), $this->prd['id'], $this->int_to, 'Criar Imagem');

				$cnt++;
				if ($this->responseCode == 400) {
					$retVtex  = json_decode($this->result);
					$retVtexMessage = $retVtex;
					if(isset($retVtex->Message)){
				        $retVtexMessage = $retVtex->Message;
					}
					
					if (str_contains($retVtexMessage, 'bytes exceeds 4194304 bytes (4 MB)') || str_contains($retVtexMessage, 'maximum buffer size: 4194304') ){					
						$notice = 'A imagem <a href="'.$image['ImageUrl'].'">'.$path_parts['filename'].'</a> com tamanho maior que 4 MB. Reduza o tamanho ou remova a imagem';
						echo $notice."\n";
						$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Envio de Imagem', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
						return;
					}
					else {
						$notice = 'A imagem <a href="'.$image['ImageUrl'].'">'.$path_parts['filename'].'</a> com erro na Vtex: '. $retVtex->Message;
						echo $notice."\n";
						$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Envio de Imagem', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
						die;
					}
				}

				if (($this->responseCode !== 200) && ($this->responseCode !== 202) && ($this->responseCode !== 204)) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no POST chamar '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				
				if ($cnt >= 50) {  // Vtex só aceita 50 imagens
					break;
				}
			}
		}
		return true;
	}

	function getVtexSkuProductId($variant_num, $skumkt)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// tenho q fazer get do sku do seller primeiro 
		$endPoint   = 'api/catalog_system/pvt/skuseller/'.$this->sellerId .'/'.$skumkt;
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku do Seller');
		if ($this->responseCode != 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$sku_vtex  = json_decode($this->result);
		
		// Agora posso pegar o SKU de verdade  
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$sku_vtex->StockKeepingUnitId;
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
		if ($this->responseCode != 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$sku_vtex  = json_decode($this->result);
		$this->vtex_ref_id = $sku_vtex->RefId;
		
		if (!is_null($this->adlink)) {
			// Agora posso pegar o products  de verdade  
			$endPoint   = 'api/catalog/pvt/product/'.$sku_vtex->ProductId;
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Produto');
			if ($this->responseCode != 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
			$this->prd_vtex  = json_decode($this->result);
		}
		
		return array (
			'mkt_product_id' => $sku_vtex->ProductId,
        	'mkt_sku_id' => $sku_vtex->Id 
		);
			
	}

	function productAtVtex($variant_num, $skumkt) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$erro = 'Produto '.$this->prd['id'].' já cadastrado no marketplace '.$this->int_to.' com sku '.$skumkt. ' Criando tabelas prd_to_integration e vtex_ult_envio';
		echo $erro."\n";
		
		$ids= $this->getVtexSkuProductId($variant_num, $skumkt);
		
		$toSavePrd = [
            'prd_id' 			=> $this->prd['id'],
            'company_id' 		=> $this->prd['company_id'],
            'status' 			=> 1,
            'status_int' 		=> 2,
            'date_last_int' 	=> $this->dateLastInt, 
            'skumkt' 			=> $skumkt,
            'skubling' 			=> $skumkt,
            'int_to' 			=> $this->int_to,
            'store_id' 			=> $this->prd['store_id'],
            'seller_id' 		=> $this->sellerId ,
            'approved' 			=> 1,
            'int_id' 			=> $this->integration_store['id'],
            'user_id' 			=> 0,
            'int_type' 			=> 0, 
            'variant' 			=> $variant_num,
            'mkt_product_id' 	=> $ids['mkt_product_id'],
            'mkt_sku_id' 		=> $ids['mkt_sku_id'],  
            'ad_link' 			=> (is_null($this->adlink)) ? '' : $this->adlink . $this->prd_vtex->LinkId."/p"
        ];
		if(isset($this->prd['approved_curatorship_at']) && !empty($this->prd['approved_curatorship_at'])){
            $toSavePrd['approved_curatorship_at'] = $this->prd['approved_curatorship_at'];
        }

        if(isset($this->prd_to_integration['approved_curatorship_at']) && !empty($this->prd_to_integration['approved_curatorship_at'])){
            $toSavePrd['approved_curatorship_at'] = $this->prd_to_integration['approved_curatorship_at'];
        }

		$PrdIntId = $this->model_integrations->createIfNotExist($this->prd['id'], $this->int_to, $variant_num, $toSavePrd);

        if (!$PrdIntId) {
            $notice = "Falha ao tentar gravar produto na tabela prd_to_integration.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        }
			
	} 

	protected function getCrossDocking($prd) {
		return $prd['prazo_operacional_extra'];
	}

 	function saveVtexUltEnvio($skumkt,$variant_num )
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$crossdocking = $this->getCrossDocking($this->prd);

        $toSaveUltEnvio = [
            'int_to' 					=> $this->int_to,
            'company_id' 				=> $this->prd['company_id'],
            'EAN' 						=> $this->prd['EAN'],
            'prd_id' 					=> $this->prd['id'],
            'price' 					=> $this->prd['promotional_price'],
            'list_price' 				=> $this->prd['list_price'],
            'sku' 						=> $this->prd['sku'],
            'data_ult_envio' 			=> $this->dateLastInt, 
            'qty_atual' 				=> (int)$this->prd['qty'],
            'largura' 					=> $this->prd['largura'],
            'skumkt' 					=> $skumkt,
            'altura' 					=> $this->prd['altura'],
            'profundidade' 				=> $this->prd['profundidade'],
            'peso_bruto' 				=> $this->prd['peso_bruto'],
            'store_id' 					=> $this->prd['store_id'],
            'seller_id' 				=> $this->sellerId ,
            'crossdocking' 				=> $crossdocking,
        	'CNPJ' 						=> preg_replace('/\D/', '', $this->store['CNPJ']),
    		'zipcode' 					=> preg_replace('/\D/', '', $this->store['zipcode']), 
    		'freight_seller' 			=> $this->store['freight_seller'],
			'freight_seller_end_point' 	=> $this->store['freight_seller_end_point'],
			'freight_seller_type' 		=> $this->store['freight_seller_type'],
			'variant' 					=> $variant_num,
            'tipo_volume_codigo'		=> $this->model_category->getTipoVolumeCategory(json_decode($this->prd['category_id'])[0] ?? 0)
        ];

        $toSaveUltEnvio = $this->formatFieldsUltEnvio($toSaveUltEnvio);

    	$savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($this->prd['id'], $this->int_to, $variant_num, $toSaveUltEnvio);
        if (!$savedUltEnvio) {
            $notice = "Falha ao tentar gravar dados na tabela vtex_ult_envio.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        } 
	
	}

	function hasShipCompany()
    {
    	return true;
    }
	
	function checkAutoApproveSeller() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/configuration/autoapproval/toggle?sellerid='.$this->sellerId ;

        $this->vtexHttpUrl($this->auth_data, $url, 'GET',null);

		if ($this->responseCode !== 200) {
			$notice = "Falha ao tentar obter configuração de autoa aapproval. Ull:".$url."httpcode = ".$this->responseCode;
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
		}

        $result = json_decode($this->result);
        return $result->Enabled;
	}

	function verifySellers($vtex_sku_id)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$data = [];
		$bodyParams = json_encode($data);
        $endPoint   = 'api/catalog_system/pvt/sku/stockkeepingunitbyid/'.$vtex_sku_id;
	            
        echo "Verificando se o produto ".$this->prd['id']." sku_id ".$vtex_sku_id." tem mais de 1 vendedor além do seller ".$this->sellerId ."\n";
        $skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', $bodyParams, $this->prd['id'], $this->int_to, 'Listando todos os sellers do sku');
		
		if ($this->responseCode != 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$result = json_decode($this->result,true);

		foreach($result['SkuSellers'] as $sku_seller) {
			if ($sku_seller['SellerId'] == '1') {
				echo "Seller 1 \n";
				// Para o seller 1, tenho que verificar o preço se existe

				$url = 'https://api.vtex.com/'.$this->accountName.'/pricing/prices/'.$sku_seller['SellerStockKeepingUnitId'] ;
				$this->vtexHttpUrl($this->auth_data, $url,'GET', null, $this->prd['id'], $this->int_to, 'Ler Preço do SellerId 1');
				if ($this->responseCode == 404) {  // se não existe preço, não é vendido pelo dono do sellercenter
					echo "O produto não é vendido pela seller 1  \n"; 
					continue;
				}
				if ($this->responseCode != 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				echo "O produto também é vendido pela seller 1 e não pode mudar \n"; 
				return true;

			}
			elseif ($sku_seller['SellerId'] == $this->sellerId) {
				echo "Eu achei eu mesmo \n"; 
			}
			else {
				echo "Achou mais um seller_id ".$sku_seller['SellerId']." para este produto, então não pode mudar \n";
				// tenho q fazer get do sku do seller primeiro 
				$endPoint   = 'api/catalog_system/pvt/skuseller/'.$sku_seller['SellerId'] .'/'.$sku_seller['SellerStockKeepingUnitId'];
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku do Seller De outro Seller');
				if ($this->responseCode != 404) { 
					if ($this->responseCode != 200) { 
						$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
						echo $erro."\n";
						$this->log_data('batch',$log_name, $erro ,"E");
						die;
					}
				}
				return true; 
			}

		}
		// Não achei mais ninguém vendendo este sku
		return false; 
	}
	
	protected function vtexHttp($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null, $cnt429=0 )
    {
        $this->accountName = $separateIntegrationData->accountName;
		if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
	        $this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
	            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
	        ];
	        if (isset($separateIntegrationData->suffixDns)) {
	            if (!is_null($separateIntegrationData->suffixDns)) {
		            $this->setSuffixDns($separateIntegrationData->suffixDns);
		        }
	        } 
			
	        $url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;
		} else {   // LinkApi
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	           // "apiKey: $separateIntegrationData->apiKey",
	        ];
	        $url = $separateIntegrationData->site .'/'.$endPoint;
			$url .= "?apiKey=".$separateIntegrationData->apiKey;
		}
		echo "------------------- Chamando: ".$url."\n";
		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if($errno = curl_errno($ch)) {
			$error_message = curl_strerror($errno);
			echo "cURL error ({$errno}):\n {$error_message}\n";
		}
        curl_close($ch);
		
		var_dump( $this->result );
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => $method,
				'sent' => json_encode($data),
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}

		if ($this->responseCode == 429) {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 10 segundos.");
            sleep(10);
			if ($cnt429 >= 2) {
				$this->log("3 requisições já enviadas httpcode=429.Desistindo e mantendo na fila.");
				die;
			}
			$cnt429++;
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $prd_id, $int_to, $function, $cnt429 );
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $prd_id, $int_to, $function, 0);
		}
        if ($this->responseCode == 503) {
		    $this->log("Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $prd_id, $int_to, $function, 0);
		}

        return;
    }

	protected function vtexHttpUrl($separateIntegrationData, $url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null, $cnt429=0 )
    {
        $this->accountName = $separateIntegrationData->accountName;
		if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
	        $this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
	            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
	        ];
		} else {   // LinkApi
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            // "apiKey: $separateIntegrationData->apiKey",
	        ];
			// troca o site da Vtex pelo site da LinkApi 
			$url = str_replace('https://api.vtex.com/'.$this->accountName,$separateIntegrationData->site.'/api/'.$this->accountName, $url);
			$url .= "?apiKey=".$separateIntegrationData->apiKey;
		}
		echo "****************    Chamando: ".$url."\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if($errno = curl_errno($ch)) {
			$error_message = curl_strerror($errno);
			echo "cURL error ({$errno}):\n {$error_message}\n";
		}
        curl_close($ch);
		
		var_dump( $this->result );
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'function' => $function,
				'url' => $url,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
		
		if ($this->responseCode == 429) {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 10 segundos.");
            sleep(10);
			if ($cnt429 >= 2) {
				$this->log("3 requisições já enviadas httpcode=429.Desistindo e mantendo na fila.");
				die;
			}
			$cnt429++;
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data, $prd_id, $int_to, $function, $cnt429);
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data, $prd_id, $int_to, $function, 0);
		}
        if ($this->responseCode == 503) {
		    $this->log("Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data, $prd_id, $int_to, $function, 0);
		}


        return;
    }

    public function notifyMarketplaceOfPriceUpdate($skumkt)
    {
        $endPoint = "api/notificator/$this->sellerId/changenotification/$skumkt/price";
        $this->vtexHttp(
            $this->auth_data,
            $endPoint,
            'POST',
            array(),
            $this->prd['id'],
            $this->int_to,
            'Notificação de atualização de preços no marketplace'
        );

        if (!in_array($this->responseCode, array(200,202,204,206))) {
            echo "Erro para notificar a atualização de preço httpcode: $this->responseCode ao chamar $endPoint result ".print_r($this->result,true);
        }
    }

	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }

		return $this->model_vtex_ult_envio->getData(null, $procura);
	}

    public function errorTransformation($prd_id, $sku, $msg, $step, $prd_to_integration_id = null, $mkt_code = null, $variant = null)
    {
        // Erro de transformação zera estoque.
        if ($this->getLastPost($prd_id, $this->int_to, $variant)) {
            if (!is_null($variant)) {
                $variant_data = getArrayByValueIn($this->variants, $variant, 'variant');
                $this->setDataVariantToUpdate($variant_data);
            }
            $this->prd['qty'] = 0;

            if (is_null($variant) && $this->prd['has_variants'] !== '') {
                if (count($this->variants) > 0) {
                    // Zera o estoque de todas as variações.
                    foreach ($this->variants as $variant_prd) {
                        $this->setDataVariantToUpdate($variant_prd);
                        $this->prd['qty'] = 0;
                        $this->saveVtexUltEnvio($sku, $variant_prd['variant']);
                    }
                }
            } else {
                $this->saveVtexUltEnvio($sku, $variant);
            }
        }

        parent::errorTransformation($prd_id, $sku, $msg, $step, $prd_to_integration_id, $mkt_code, $variant);
    }

    private function setDataVariantToUpdate(array $variant)
    {
        $this->prd['sku'] = $variant['sku'];
        $this->prd['qty'] = $variant['qty'];
        $this->prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'];
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku'))
        {
            $this->prd['price'] = $variant['price'];
            $this->prd['list_price'] = $variant['list_price'];
            $this->prd['promotional_price'] = $variant['promotional_price'];
        }
        else
        {
            // ao remover a featureflag remover estes itens sem medo
            if((isset($variant['list_price'])) && ($variant['list_price'] != null) && !$this->promo){
                $this->prd['list_price'] = $variant['list_price'];
            }
            if((isset($variant['price'])) && ($variant['price'] != 0) && !$this->promo ){
                $this->prd['promotional_price'] = $variant['price'];
            }
            if ($this->prd['promotional_price'] > $this->prd['list_price'] && !$this->promo) {
                $this->prd['list_price'] = $this->prd['promotional_price'];
            }
        }
    }
}
