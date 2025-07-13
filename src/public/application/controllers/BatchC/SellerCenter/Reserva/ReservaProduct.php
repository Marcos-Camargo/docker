<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class ReservaProduct extends Main
{
	var $int_to;
	var $auth_data;
	var $score_min = 80;
	var $caixaSomaL = 30;
	var $caixaSomaA = 7;
	var $caixaSomaP = 35;
	var $MinCorreiosL = 11;
	var $MinCorreiosA = 2;
	var $MinCorreiosP = 16;
	var $catalog_id ;
	
    public function __construct()
    {
        parent::__construct();
        	
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
    	$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		$this->load->model('model_products');
        $this->load->model('model_brands');
        $this->load->model('model_category');
		$this->load->model('model_products_catalog');
		$this->load->model('model_integrations');
		$this->load->model('model_vtex_ult_envio');
		$this->load->model('model_stores');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_promotions');
		$this->load->model('model_brands_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_catalogs');
	}

    // php index.php BatchC/SellerCenter/Reserva/ReservaProduct run null Reserva
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id); 
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->clearQueueProductsMarketplace();
		if (!is_null($params)) {
			$this->int_to = $params;
			
			$catalog = $this->model_catalogs->getCatalogByName($this->int_to);
			if (!$catalog) {
				echo "Este catálogo ".$this->int_to." não existe\n";
				die;
			}
			$this->catalog_id = $catalog['id'];
			
			$integrationData = $this->model_integrations->getIntegrationbyStoreIdAndInto('0',$this->int_to);
			$this->auth_data = json_decode($integrationData['auth_data']);
			$this->accountName = $this->auth_data->accountName;
			$retorno = $this->getScore();
		    $retorno = $this->notifyPriceAndStockChange();
		    $retorno = $this->skuSuggestionInsertion();
		}
		else {
			echo "Informe o int_to do marketplace para enviar produtos\n";
		}
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	public function getScore() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// GET https://api.vtex.com/lojafarm/suggestions/configuration 
 		$url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/configuration';
        $this->processURL($this->auth_data, $url);
        $result = json_decode($this->result);
		//var_dump($result);
		if ($this->responseCode !== 200) {
			$erro = "Não foi possivel pegar o score mínimo para match de ".$this->int_to." http: ".$url." httpcode:".$this->responseCode." resposta: ".print_r($this->result,true);
            echo $erro."\n";
            $this->log_data('batch', $log_name, $erro,"E");
			return;
		}
		$this->score_min = $result->Score->Approve;
		
	}
	
	function clearQueueProductsMarketplace() {
		$sql = 'DELETE FROM queue_products_marketplace';
		$query = $this->db->query($sql);
		return ;
	}
	
    // php index.php BatchC/SellerCenter/Vtex/Product notifyPriceAndStockChange
    public function notifyPriceAndStockChange()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Buscando produtos com estoque alterado para notificar ".$this->int_to."\n";
		
		$offset = 0;
		$limit = 50; 
		$exists = true; 
		while ($exists) {
			$dateLastInt = date('Y-m-d H:i:s');
			$offsetX = 0;  // volto sempre para zero pois estou alterando os produtos e ai o offset não funcioana....
			$products = $this->model_integrations->getProductsChangedToIntegrate($this->int_to, $offsetX, $limit);
			if (count($products)==0) {
				echo "Encerrou \n";
				$exists = false;
				break;
			}
			
			foreach ($products as $key => $product) {
				echo $offset + $key + 1 . " - ";
				
				// var_dump($product);
				
				// Leio os dados da integração desta loja deste produto
				$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto($product['store_id'],$this->int_to);
				$auth_data = json_decode($integration['auth_data']);
            	$sellerId = $auth_data->seller_id;
				
				if (($product['status'] != 1) || ($product['situacao'] != 2)) {
	                echo "Produto ".$product['id']." não está ativo. Estoque na Vtex será alterado para zero.\n";
	                $product['qty'] = 0;
	            }
	            // pego o preço da promotion se houver
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['variant'])) {
                    $product['price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to, $product['variant']);
                }
                else
                {
                    $product['price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to);
                }

				$product['price'] = round($product['price'],2);
				
				$this->model_vtex_ult_envio->updateQty($product['id'], $this->int_to, $product['qty']);
				
				$data = [];
				$bodyParams = json_encode($data);
	            $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$product['pi_skumkt'];
	            
	            echo "Verificando se o produto ".$product['id']." sku ".$product['pi_skumkt']." existe no marketplace ".$this->int_to." para o seller ".$sellerId.".\n";
	            $skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $product['id'], $this->int_to, 'Notificação de Mudança');

				if ($this->responseCode == 404) {
					$erro = "O produto ".$product['id']." não está cadastrado no marketplace ".$this->int_to." para o seller ".$sellerId.".";
		            echo $erro."\n";
		            $this->log_data('batch', $log_name, $erro,"E");
					$this->model_integrations->updatePrdToIntegration(array('status_int' => 90, 'date_last_int' => $dateLastInt), $product['pi_id']);
					
					// deveria retirar o registro do prd_to_integration e do vtex_ult_envio se isso acontecer 
					continue;
				}
				if ($this->responseCode !== 204) {
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				
	            $notice = "Notificação de alteração concluída para o produto ".$product['id']." sku: ".$product['pi_skumkt'];
                echo $notice."\n";
                $this->model_integrations->updatePrdToIntegration(array(
	                	'int_id'=> $integration['id'], 
	                	'seller_id'=> $sellerId, 
	                	'status_int' => 2, 
	                	'date_last_int' => $dateLastInt, 
	                	'skumkt' => $product['pi_skumkt'], 
	                	'skubling' => $product['pi_skumkt']
					), $product['pi_id']);
				
				// se for produto de catálogo, puxo as informações do catálogo. 
				if (!is_null($product['product_catalog_id'])) {
					$prd_catalog = $this->model_products_catalog->getProductProductData($product['product_catalog_id']); 
					$product['EAN'] = $prd_catalog['EAN'];
					$product['largura'] = $prd_catalog['width'];
					$product['altura'] = $prd_catalog['height'];
					$product['profundidade'] = $prd_catalog['length'];
					$product['peso_bruto'] = $prd_catalog['gross_weight'];
					$product['ref_id'] = $prd_catalog['ref_id']; 
				}
				else {
					$product['ref_id'] = null;
				}
				
				$loja  = $this->model_stores->getStoresData($product['store_id']);
				$toSaveUltEnvio = [
                    'int_to' => $this->int_to,
                    'company_id' => $product['company_id'],
                    'EAN' => $product['EAN'],
                    'prd_id' => $product['id'],
                    'price' => $product['price'],
                    'list_price' => $product['price'],
                    'sku' => $product['sku'],
                    'data_ult_envio' => $dateLastInt, 
                    'qty_atual' => $product['qty'],
                    'skumkt' => $product['pi_skumkt'],
                    'largura' => ((int)$product['largura'] < $this->caixaSomaL) ? $this->caixaSomaL : $product['largura'],
                    'altura' => ((int)$product['altura'] < $this->caixaSomaA) ? $this->caixaSomaA : $product['altura'],
                    'profundidade' => ((int)$product['profundidade'] < $this->caixaSomaP) ? $this->caixaSomaP : $product['profundidade'],
                    'peso_bruto' => $product['peso_bruto'],
                    'store_id' => $product['store_id'],
                    'seller_id' => $sellerId,
                    'crossdocking' => $product['prazo_operacional_extra'],
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']), 
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
                ];

                $savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($product['id'], $this->int_to, null, $toSaveUltEnvio);
				
			}	
			$offset += $limit;

		} 

    }

    // php index.php BatchC/SellerCenter/Vtex/Product skuSuggestionInsertion
    public function skuSuggestionInsertion()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        // Como funciona a pontuação do VTEX Matcher
        // https://help.vtex.com/pt/tutorial/entendendo-a-pontuacao-do-vtex-matcher?locale=pt

        echo "Buscando novos produtos para integrar.\n";
		$offset = 0;
		$limit = 500; 
		$exists = true; 
		while ($exists) {
			$dateLastInt = date('Y-m-d H:i:s');
			
			//trago de 50 em 50. Como altero o integration, sempre volto para 0 para buscar os restantes 
			$products = $this->model_integrations->getNewProductsToIntegrate($this->int_to, $this->catalog_id, $offset, $limit );
			if (count($products)==0) {
				echo "Encerrou \n";
				$exists = false;
				break;
			}

			foreach ($products as $key => $product) {
				echo $offset + $key + 1 . " - ";
				
				// Leio os dados da integração desta loja deste produto
				$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto($product['store_id'],$this->int_to);
				$auth_data = json_decode($integration['auth_data']);
            	$sellerId = $auth_data->seller_id;
				
				// pego o preço da promotion se houver
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['variant'])) {
                    $product['price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to, $product['variant']);
                }
                else
                {
                    $product['price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to);
                }

				$product['price'] = round($product['price'],2); 
						
				// pego os dados do catálogo do produto se houver 
				if (!is_null($product['product_catalog_id'])) {
					$prd_catalog = $this->model_products_catalog->getProductProductData($product['product_catalog_id']); 
					$product['name'] = $prd_catalog['name'];
					$product['description'] = $prd_catalog['description'];
					$product['EAN'] = $prd_catalog['EAN'];
					$product['largura'] = $prd_catalog['width'];
					$product['altura'] = $prd_catalog['height'];
					$product['profundidade'] = $prd_catalog['length'];
					$product['peso_bruto'] = $prd_catalog['gross_weight'];
					$product['ref_id'] = $prd_catalog['ref_id']; 
					$product['brand_code'] = $prd_catalog['brand_code'];
					$product['brand_id'] = '["'.$prd_catalog['brand_id'].'"]'; 
					$product['category_id'] = '["'.$prd_catalog['category_id'].'"]';
					$product['mkt_sku_id'] = $prd_catalog['mkt_sku_id'];
					$product['image'] = $prd_catalog['image'];
					$product['product_name'] = $prd_catalog['product_name'];
					$product['sku_name'] = $prd_catalog['sku_name'];
					$product['mkt_product_id'] = $prd_catalog['mkt_product_id'];
					$pathImage = 'catalog_product_image';

				}
				else {
					$pathImage = 'product_image';
				}
				
				$skumkt = $product['sku'];
				if (strlen($product['sku']) > 20) {
					$skumkt = $product['id'].'-'.$this->int_to;
				}
				
				// Verifico de o produto existe na Vtext
				// https://help.vtex.com/en/tutorial/integration-guide-for-marketplaces-seller-non-vtex-with-payment--bNY99qbQ7mKsSMMuq2m4g
				$bodyParams = json_encode(array());
	            $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$skumkt;
	            
	            echo "Verificando se o produto ".$product['id']." sku ".$skumkt." existe no marketplace ".$this->int_to." para o seller ".$sellerId.".\n";

	            $skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $product['id'], $this->int_to, 'Notificação de mudança'); 
				
				if (($this->responseCode == 200) || ($this->responseCode == 204)) {
					$erro = 'Produto '.$product['id'].' já cadastrado no marketplace '.$this->int_to.' com sku '.$skumkt. ' Criando tabelas prd_to_integration e vtex_ult_envio';
					echo $erro."\n";
					
					// tenho q fazer get do sku do seller primeiro 
					$endPoint   = 'api/catalog_system/pvt/skuseller/'.$sellerId.'/'.$skumkt;
					$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $product['id'], $this->int_to, 'Ler Sku do Seller');
					if ($this->responseCode != 200) {
						$notice = "Falha ao ler SKU ".$product['id']." sku: ".$skumkt." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true);
		                echo $notice."\n";
		                $this->log_data('batch', $log_name, $notice,"E");
		                continue;
					}
					$sku_vtex  = json_decode($this->result);
					echo 'Refid = '.$sku_vtex->StockKeepingUnitId."\n";
					
					// Agora posso pegar o SKU de verdade  
					$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$sku_vtex->StockKeepingUnitId;
					$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $product['id'], $this->int_to, 'Ler Sku Completo');
		
					if ($this->responseCode != 200) {
						$notice = "Falha ao ler SKU do produto ".$product['id']." id vtex:".$sku_vtex->StockKeepingUnitId." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true);
		                echo $notice."\n";
		                $this->log_data('batch', $log_name, $notice,"E");
		                continue;
					} 
					$sku_vtex  = json_decode($this->result);
					
					$toSavePrd = [
		                'prd_id' => $product['id'],
		                'company_id' => $product['company_id'],
		                'status' => 1,
		                'status_int' => 2,
		                'date_last_int' => $dateLastInt, 
		                'skumkt' => $skumkt,
		                'skubling' => $skumkt,
		                'int_to' => $this->int_to,
		                'store_id' => $product['store_id'],
		                'seller_id' => $sellerId,
		                'approved' => 1,
		                'int_id' => $integration['id'],
		                'user_id' => 0,
		                'int_type' => 0, 
		                'variant' => 0,
            			'mkt_product_id' => $sku_vtex->ProductId,
            			'mkt_sku_id' => $sku_vtex->Id,
		            ];
		            $savedPrd = $this->model_integrations->createPrdToIntegration($toSavePrd);
				
					$loja  = $this->model_stores->getStoresData($product['store_id']);
					$toSaveUltEnvio = [
	                    'int_to' => $this->int_to,
	                    'company_id' => $product['company_id'],
	                    'EAN' => $product['EAN'],
	                    'prd_id' => $product['id'],
	                    'price' => $product['price'],
	                    'sku' => $product['sku'],
	                    'data_ult_envio' => $dateLastInt, 
	                    'qty_atual' => $product['qty'],
                   		'skumkt' => $skumkt,      
						'largura' => ((int)$product['largura'] < $this->caixaSomaL) ? $this->caixaSomaL : $product['largura'],
                    	'altura' => ((int)$product['altura'] < $this->caixaSomaA) ? $this->caixaSomaA : $product['altura'],
                    	'profundidade' => ((int)$product['profundidade'] < $this->caixaSomaP) ? $this->caixaSomaP : $product['profundidade'],
	                    'peso_bruto' => $product['peso_bruto'],
	                    'store_id' => $product['store_id'],
	                    'seller_id' => $sellerId,
	                    'crossdocking' => $product['prazo_operacional_extra'],
		        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
		        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']), 
		        		'freight_seller' =>  $loja['freight_seller'],
						'freight_seller_end_point' => $loja['freight_seller_end_point'],
						'freight_seller_type' => $loja['freight_seller_type'],
						'variant' => null
	                ];
	                $savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($product['id'], $this->int_to, null, $toSaveUltEnvio);
					continue; 
				}
				if ($this->responseCode !== 404) { // O normal é dar 404, então podemos cadastrar o produto
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				
				// Monto array com as imagens do produto
				$images = $this->getProductImages($product['image'], $pathImage);
	            if ($images === null) {
	                $notice = "Pasta ".$pathImage.'/'.$product['image']." não encontrada!";
	                echo $notice."\n";
	                $this->log_data('batch', $log_name, $notice,"E");
	                continue;
	            } elseif ($images === false) {
	                $notice = "Não foram encontradas imagens na pasta ".$pathImage.'/'.$product['image'].".";
	                echo $notice."\n";
	                $this->log_data('batch', $log_name, $notice,"E");
	                continue;
	            }

				// pego a marca
				$brandId    = json_decode($product['brand_id']);
            	$brand      = $this->model_brands->getBrandData($brandId);
				// pego o brand do Marketplace 
				$brandvtex = $this->model_brands_marketplaces->getBrandMktplaceByName($this->int_to,$brand['name']);
				if (!$brandvtex) {
					$msg= 'Marca '.$brand['name'].' não vinculada ao marketplace '.$this->int_to;
					echo 'Produto '.$product['id']." ".$msg."\n";
					$this->errorTransformation($product['id'],$skumkt,$msg);
					return ;
				}
				if (!$brandvtex['isActive']) {
					$msg= 'Marca '.$brand['name'].' inativada no marketplace '.$this->int_to;
					echo 'Produto '.$product['id']." ".$msg."\n";
					$this->errorTransformation($product['id'],$skumkt,$msg);
					return ;
				}
				$product['brandvtex'] = $brandvtex['id_marketplace'];
				
				
				// pego a categoria 
				$categoryId = json_decode($product['category_id']);
           		$category   = $this->model_category->getCategoryData($categoryId);
           		// pego a categoria do marketplace
				$result= $this->model_categorias_marketplaces->getCategoryMktplace($this->int_to,$categoryId);
				if (!$result) {
					$msg= 'Categoria '.$categoryId.' não vinculada ao marketplace '.$this->int_to;
					$this->errorTransformation($product['id'],$skumkt,$msg);
					continue;
				}
				$idCat= $result['category_marketplace_id'];
				$product['categoryidvtex'] = $result['category_marketplace_id']; 
				// mando a suggestion				
	            $data = [
	                'BrandId'                    => (int)$product['brandvtex'],
	                'BrandName'                  => $brand['name'],
	                'CategoryFullPath'           => str_replace(' > ','/',$category['name']),
	                'CategoryId'                 => (int)$product['categoryidvtex'],
	                'EAN'                        => array(
	                    ((trim($product['EAN']) == '') ? null : $product['EAN'])
	                ),
	                'Height'                     => (float)$product['altura'],
	                'Id'                         => null,
	                'Images'                     => $images,
	                'IsAssociation'              => false,
	                'IsKit'                      => false,
	                'IsProductSuggestion'        => false,
	                'Length'                     => (float)$product['profundidade'],
	                'ListPrice'                  => (int)$product['price']*100,
	                'ModalId'                    => null,
	                'Price'                      => (int)$product['price']*100,
	                'ProductDescription'         => $product['description'],
	                'ProductId'                  => (!is_null($product['mkt_product_id'])) ? $product['mkt_product_id'] : ((trim($product['ref_id']) ? $product['ref_id'] : null)),
	                'ProductName'                => (is_null($product['product_name'])) ? $product['name']: $product['product_name'],
	                'ProductSpecifications'      => [],
	                'ProductSupplementaryFields' => null,
	                'RefId'                      => ((trim($product['ref_id']) ? $product['ref_id'] : null)), // obrigatório quando o EAN não for enviado
	                'SellerId'                   => $sellerId,
	                'SellerModifiedDate'         => null,
	                'SellerStockKeepingUnitId'   => $skumkt,
	                'SkuId'                      => $product['mkt_sku_id'],
	                'SkuName'                    => (is_null($product['sku_name'])) ? $product['name']: $product['sku_name'],
	                'SkuSpecifications'          => [],
	                'SkuSupplementaryFields'     => null,
	                'SynonymousPropertyNames'    => null,
	                'WeightKg'                   => (float)$product['peso_bruto'],
	                'Width'                      => (float)$product['largura'],
	            ];
   
	            $bodyParams  = json_encode($data);
	            $endPoint    = "api/catalog_system/pvt/sku/SuggestionInsertUpdatev2";
            
            	echo "Enviando sugestão de SKU.\n";
				$skuInserted = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $product['id'], $this->int_to, 'Sugestão');

	            if ($this->responseCode != 200) {
	                $notice = "Falha no envio de sugestão de SKU do produto ".$product['id']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true).' enviado: '.print_r($bodyParams,true);
	                echo $notice."\n";
	                $this->log_data('batch', $log_name, $notice,"E");
	                continue;
	            }

				echo "Iniciando match do produto.\n";
	            $matchOk = $this->skuToMatch($product, $sellerId, $idCat, $skumkt);
	
				if ($matchOk['success'] == false) {
	                $notice = "Falha ao tentar match no produto: ".$matchOk['error'];
	                echo $notice."\n";
	                $this->log_data('batch', $log_name, $notice,"E");
	                continue;
	            }
				
				var_dump($matchOk);
				
	            echo "Match realizado com sucesso! Id do produto na Conecta: ".$product['id']."; Id do SKU do produto na Vtex: ".$product['mkt_sku_id']."\n";

				$loja  = $this->model_stores->getStoresData($product['store_id']);
	            $toSaveUltEnvio = [
	                'int_to' => $this->int_to,
	                'company_id' => $product['company_id'],
	                'EAN' => $product['EAN'],
	                'prd_id' => $product['id'],
	                'price' => $product['price'],
	                'sku' => $product['sku'],
	                'data_ult_envio' => $dateLastInt, 
	                'qty_atual' => $product['qty'],
	                'largura' => $product['largura'],
	                'skumkt' => $skumkt,
	                'altura' => $product['altura'],
	                'profundidade' => $product['profundidade'],
	                'peso_bruto' => $product['peso_bruto'],
	                'store_id' => $product['store_id'],
	                'seller_id' => $sellerId,
	                'crossdocking' => $product['prazo_operacional_extra'],
                	'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']), 
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
					'variant' => null,
	            ];
            	$savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($product['id'], $this->int_to, null, $toSaveUltEnvio);

	            if (!$savedUltEnvio) {
	                $notice = "Falha ao tentar gravar dados na tabela vtex_ult_envio.";
	                echo $notice."\n";
	                $this->log_data('batch', $log_name, $notice,"E");
					die;
	            } 
				
				$mkt_product_id = $matchOk['ProductId']; 
				if (is_null($mkt_product_id)) {
					// Agora posso pegar o SKU de verdade  
					$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$product['mkt_sku_id'];
					$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $product['id'], $this->int_to, 'Ler Sku Completo');
					if ($this->responseCode != 200) {
						$notice = "Falha ao ler SKU do produto ".$product['id']." id vtex:".$product['mkt_sku_id']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true);
		                echo $notice."\n";
		                $this->log_data('batch', $log_name, $notice,"E");
		                continue;
					}
					$sku_vtex  = json_decode($this->result);
					$mkt_product_id = $sku_vtex->ProductId;					
				}
				
				if (($product['mkt_product_id']) != $mkt_product_id) {
					echo " Não deu Match no produto certo. Novo product Id = ".$mkt_product_id." product ID para fazer match= ".$product['mkt_product_id']."\n";
				
				}
				
				 $toSavePrd = [
	                'prd_id' => $product['id'],
	                'company_id' => $product['company_id'],
	                'status' => 1,
	                'status_int' => 2,
	                'date_last_int' => $dateLastInt, 
	                'skumkt' => $skumkt,
	                'skubling' => $skumkt,
	                'int_to' => $this->int_to,
	                'store_id' => $product['store_id'],
	                'seller_id' => $sellerId,
	                'approved' => 1,
	                'int_id' => $integration['id'],
	                'user_id' => 0,
	                'int_type' => 0, 
	                'variant' => null,
	                'mkt_product_id' => $mkt_product_id,
	                'mkt_sku_id' => $product['mkt_sku_id']
	            ];

	            // $savedPrd = $this->model_integrations->createPrdToIntegration($toSavePrd);
				$savedPrd = $this->model_integrations->createIfNotExist($product['id'], $this->int_to, null, $toSavePrd);
	            if (!$savedPrd) {
	                $notice = "Falha ao tentar gravar produto na tabela prd_to_integration.";
	                echo $notice."\n";
	                $this->log_data('batch', $log_name, $notice,"E");
					die;
	            }	
				
				// enviando Notificacao para a Vtex pegar o estoque e preço do produto 
				$cnt = 0;
				while ($cnt < 1) {
					$cnt++;
					$data= array(); 
					$bodyParams = json_encode($data);
					sleep(10); // durmo por 10 segundos...
		            $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$skumkt;	            
		            $skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $product['id'], $this->int_to, 'Notificação de Mudança');
					if ($this->responseCode == 204) {
						$cnt = 5;	
						break;
					}
					if ($this->responseCode == 404) {
						
						continue;
					}
					if ($this->responseCode !== 204) {
						$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
						echo $erro."\n";
						$this->log_data('batch',$log_name, $erro ,"E");
						// die;
					}
				}
				
			}
			$offset += $limit;
        }
    }

    private function skuToMatch($product, $sellerId, $idCat, $skumkt)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        echo "Pegando version do SKU.\n";
        $version = $this->getVersion($sellerId, $skumkt, $product['id']);

        if ($version == false) {
            return false;
        }

		$data = [
            'matcherId' => 1,
            'matchType' => 'itemMatch',
            'score' => $this->score_min,
            'skuRef' => $product['mkt_sku_id'],
            'productRef' => $product['mkt_product_id'],
            'Product' => [
            	'name' => (!is_null($product['product_name'])) ? $product['product_name'] : $product['name'],
            	'description' => $product['description'],
            	'brandId' => (int)$product['brandvtex'], 
            	'categoryId' => (int)$product['categoryidvtex'],
            ], 
            'SKU' => [
                'name' => (!is_null($product['sku_name'])) ? $product['sku_name'] : $product['name'],
                'eans' => [
                    $product['EAN']
                ],
                'refId' => $product['ref_id'],
                'height' => $product['altura'],
                'width' => $product['largura'],
                'length' => $product['profundidade'],
                'weight' => $product['peso_bruto'],
                'unitMultiplier' => '1.0000',
                'measurementUnit' => 'un'
            ]
        ];

        $bodyParams = json_encode($data);

        echo "Concluindo match.\n";
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$sellerId.'/'.$skumkt.'/versions/'.$version.'/matches/1';
		
        $this->processURL($this->auth_data, $url,'PUT', $bodyParams, $product['id'], $this->int_to, 'Match de produto');
        $result = json_decode($this->result);
		// var_dump($result);
		if ($this->responseCode !== 200) {
			$msg = $result->Error->Message ?? $result->message; 
			if ($msg == 'Association can be redone') {
				echo "Mandar remover a associação na Vtex \n";		
			};
			return (array('success' => false, 'error' => $result->Error->Message ?? $result->message)) ;
		}

        return (array('success' => true, 'ProductId' => $result->ProductId, 'SkuId' =>$result->SkuId)) ;
    }

    private function getVersion($sellerId,$sku, $product_id)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$sellerId.'/'.$sku.'/versions';
        
        $this->processURL($this->auth_data, $url,'GET',null, $product_id, $this->int_to, 'Última versão de sugestão');

		if ($this->responseCode !== 200) {
			$notice = "Falha ao tentar obter version do SKU. httpcode = ".$this->responseCode;
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			return false;
		}

		$result = json_decode($this->result);
		
		foreach($result as $version) {
			if ($version->IsLatest) {
				return $version->VersionId;
			}
		}
        return $result[0]->VersionId;
    }

    private function getProductImages($folder, $path)
    {
        $images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
        
        if (!$images) {
            return null;
        }
        if (count($images) <= 2) {
            return false;
        }
		$numft= 0;
		$imagesData = [];
		foreach($images as $foto) {
			if (($foto!=".") && ($foto!="..") && ($foto!="")) {
				$data = [
	                'ImageUrl'  => base_url('assets/images/'.$path.'/' . $folder.'/'. $foto),
	                'ImageName' => 'Imagem'.$numft,
	                'FileId'    => null
	            ];
	            array_push($imagesData, $data);
				$numft++;
			}
		}

        return $imagesData;
    }
    
    function errorTransformation($prd_id, $sku, $msg, $prd_to_integration_id = null, $mkt_code = null)
	{
		$this->model_errors_transformation->setStatusResolvedByProductId($prd_id,$this->int_to);
		$trans_err = array(
			'prd_id' => $prd_id,
			'skumkt' => $sku,
			'int_to' => $this->int_to,
			'step' => "Preparação para envio",
			'message' => $msg,
			'status' => 0,
			'date_create' => date('Y-m-d H:i:s'), 
			'reset_jason' => '', 
			'mkt_code' => $mkt_code,
		);
		echo "Produto ".$prd_id." skubling ".$sku." int_to ".$this->int_to." ERRO: ".$msg."\n"; 
		$insert = $this->model_errors_transformation->create($trans_err);
		
		if (!is_null($prd_to_integration_id)) {
			$sql = "UPDATE prd_to_integration SET date_last_int = now() WHERE id = ?";
			$cmd = $this->db->query($sql,array($prd_to_integration_id));
		}
	}
}
