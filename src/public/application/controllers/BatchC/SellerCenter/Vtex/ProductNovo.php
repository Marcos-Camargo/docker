<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class ProductNovo extends Main
{
	var $int_to;
	var $auth_data;
	
    public function __construct()
    {
        parent::__construct();
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
		
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
    }

    // php index.php BatchC/SellerCenter/Vtex/ProductNovo run null Farm
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
		if (!is_null($params)) {
			$this->int_to = $params;
			$integrationData = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$this->int_to);
			$this->auth_data = json_decode($integrationData['auth_data']);
			
		   // $retorno = $this->notifyPriceAndStockChange();
		    $retorno = $this->skuSuggestionInsertion();
		}
		else {
			echo "Informe o int_to do marketplace para enviar produtos\n";
		}
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
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
			$products = $this->model_integrations->getProductsChangedToIntegrate($this->int_to, $offset, $limit);
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
                    $product['promotional_price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to, $product['variant']);
                }
                else
                {
                    $product['promotional_price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to);
                }

				$product['promotional_price'] = round($product['promotional_price'],2);
				
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
					die;;
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
                    'price' => $product['promotional_price'],
                    'sku' => $product['sku'],
                    'data_ult_envio' => $dateLastInt, 
                    'qty_atual' => $product['qty'],
                    'largura' => $product['largura'],
                    'skumkt' => $product['pi_skumkt'],
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
                ];

                $savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($product['id'], $this->int_to, $toSaveUltEnvio);
				
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

        $busca = array('simples','variacao');
		foreach ($busca as $tipo) {
			echo "Buscando novos produtos $tipo para integrar.\n";
			$offset = 0;
			$limit = 50; 
			$exists = true; 
			while ($exists) {
				$dateLastInt = date('Y-m-d H:i:s');
				
				if ($tipo == 'simples') {
					$products = $this->model_integrations->getNewProductsToIntegrate($this->int_to, $offset, $limit);
				}
				else {
					$products = $this->model_integrations->getNewProductsVariantsToIntegrate($this->int_to, $offset, $limit);
				} 
				if (count($products)==0) {
					echo "Encerrou produtos $tipo\n";
					$exists = false;
					break;
				}
	
				foreach ($products as $key => $product) {
					echo $offset + $key + 1 . " - ";
					
					// Leio os dados da integração desta loja deste produto
					$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto($product['store_id'],$this->int_to);

					$this->createProduct($product,  $integration, $dateLastInt);
				}
				$offset += $limit;
	        }
		}
   
    }

	private function createProduct($product, $integration, $dateLastInt) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		 
		$auth_data = json_decode($integration['auth_data']);
		$sellerId = $auth_data->seller_id;
		// pego o preço da promotion se houver
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['variant'])) {
            $product['promotional_price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to, $product['variant']);
        }
        else
        {
            $product['promotional_price'] = $this->model_promotions->getPriceProduct($product['id'],$product['price'],$this->int_to);
        }

		$product['promotional_price'] = round($product['promotional_price'],2); 
		if ($prd['promotional_price'] > $prd['price'] ) {
			$prd['price'] = $prd['promotional_price']; 
		}
		
		if ($product['has_variants']!= '') {
			$product_variant = $this->model_products->getDataPrdVariant($product['id'],$product['variant']);
			$product['sku'] = $product_variant['sku'];
			$product['qty'] = $product_variant['qty'];
			$product['EAN'] = $product_variant['EAN'];
		}
		else {
			$product['variant'] = null;
		}
		
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
			$product['ref_id'] = $prd_catalog['ref_id'];
			$product['sku_id'] = $prd_catalog['sku_id'];
			$product['image'] = $prd_catalog['image'];
			$pathImage = 'catalog_product_image';
		}
		else {
			$pathImage = 'product_image';
		}
		
		$product['sku']= str_replace('.','',$product['sku']);
		
		// Verifico de o produto existe na Vtext
		// https://help.vtex.com/en/tutorial/integration-guide-for-marketplaces-seller-non-vtex-with-payment--bNY99qbQ7mKsSMMuq2m4g
		$bodyParams = json_encode(array());
        $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$product['sku'];
        
        echo "Verificando se o produto ".$product['id']." sku ".$product['sku']." existe no marketplace ".$this->int_to." para o seller ".$sellerId.".\n";
        $skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $product['id'], $this->int_to, 'Notificação de Mudança');
		
		if (($this->responseCode == 200) || ($this->responseCode == 204)) {
			$erro = 'Produto '.$product['id'].' já cadastrado no marketplace '.$this->int_to.' com sku '.$product['sku']. ' Criando tabelas prd_to_integration e vtex_ult_envio';
			echo $erro."\n";
			
			// tenho q fazer get do sku do seller primeiro 
			$endPoint   = 'api/catalog_system/pvt/skuseller/'.$sellerId.'/'.$product['sku'];
			$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $product['id'], $this->int_to, 'Ler Sku do Seller');

			$sku_vtex  = json_decode($this->result);
			echo 'Refid = '.$sku_vtex->StockKeepingUnitId."\n";
			
			// Agora posso pegar o SKU de verdade  
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$sku_vtex->StockKeepingUnitId;
			$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $product['id'], $this->int_to, 'Ler Sku Completo');

			echo $this->responseCode; 
			$sku_vtex  = json_decode($this->result);
			var_dump($sku_vtex);
			
			// Agora posso pegar o SKU de verdade  
			/*
			$endPoint   = 'api/catalog/pvt/product/'.$sku_vtex->ProductId;
			$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $product['id'], $this->int_to, 'Ler Produto');
			echo $this->responseCode; 
			$product_vtex  = json_decode($this->result);
			var_dump($product_vtex);
			
			die;
			 * */
			$toSavePrd = [
                'prd_id' => $product['id'],
                'company_id' => $product['company_id'],
                'status' => 1,
                'status_int' => 2,
                'date_last_int' => $dateLastInt, 
                'skumkt' => $product['sku'],
                'skubling' => $product['sku'],
                'int_to' => $this->int_to,
                'store_id' => $product['store_id'],
                'seller_id' => $sellerId,
                'approved' => 1,
                'int_id' => $integration['id'],
                'user_id' => 0,
                'int_type' => 0, 
                'variant' => $product['variant'],
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
                'price' => $product['promotional_price'],
                'sku' => $product['sku'],
                'data_ult_envio' => $dateLastInt, 
                'qty_atual' => $product['qty'],
                'largura' => $product['largura'],
                'skumkt' => $product['sku'],
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
				'variant' => $product['variant'],
            ];
            $savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($product['id'], $this->int_to, $toSaveUltEnvio);
			return; 
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
            return;
        } elseif ($images === false) {
            $notice = "Não foram encontradas imagens na pasta ".$pathImage.'/'.$product['image'].".";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            return;
        }

		// pego a marca
		$brandId    = json_decode($product['brand_id']);
    	$brand      = $this->model_brands->getBrandData($brandId);
		
		// pego a categoria 
		$categoryId = json_decode($product['category_id']);
   		$category   = $this->model_category->getCategoryData($categoryId);
   		// pego a categoria do marketplace
		$result= $this->model_categorias_marketplaces->getCategoryMktplace($this->int_to,$categoryId);
		if (!$result) {
			$msg= 'Categoria '.$categoryId.' não vinculada ao marketplace '.$this->int_to;
			echo 'Produto '.$product['id']." ".$msg."\n";
			$this->errorTransformation($product['id'],$product['sku'],$msg);
			return;
		}
		$idCat= $result['category_marketplace_id'];
		
		if (!(array_key_exists('ref_id', $product))) {
			$product['ref_id'] = null;
		}
		if (!(array_key_exists('sku_id', $product))) {
			$product['sku_id'] = null;
		}
		$product['categoryvtex'] = (int)$idCat;
	
		// pego o brand do Marketplace 
		$brandvtex = $this->model_brands_marketplaces->getBrandMktplaceByName($this->int_to,$brand['name']);
		if (!$brandvtex) {
			$msg= 'Marca '.$brand['name'].' não vinculada ao marketplace '.$this->int_to;
			echo 'Produto '.$product['id']." ".$msg."\n";
			$this->errorTransformation($product['id'],$product['sku'],$msg);
			return ;
		}
		if (!$brandvtex['isActive']) {
			$msg= 'Marca '.$brand['name'].' inativada no marketplace '.$this->int_to;
			echo 'Produto '.$product['id']." ".$msg."\n";
			$this->errorTransformation($product['id'],$product['sku'],$msg);
			return ;
		}
		$product['brandvtex'] = $brandvtex['id_marketplace'];
		
		$spec = array();
		$product['skuname'] = $product['name'];
		if ($product['has_variants']!= '') {
			$product['skuname'] = str_replace(";", " / ", $product_variant['name']);
			$product['variant_value'] = $product_variant['name'];
			$variants = explode(';', $product['has_variants']);
			$variants_value  = explode(';', $product_variant['name']);
			
			foreach( $variants as $key => $value) {
				$spec[] = array(
					'FieldId' => $key,
					'FieldName' => $value, 
					'FieldValueIds' => null,
					'FieldValues' => array($variants_value[$key]),
				);	
			}
			$data['SkuSpecifications'] = $spec; 
			
			$existVtex = $this->model_integrations->getDifferentVariant($this->int_to,$product['id'],$product['variant']);
			if ($existVtex) {
				$product['ref_id'] = $existVtex['mkt_product_id'];
				var_dump($existVtex);
				echo "aqui\n";
			}
			
			
		}
		$product['vteximages'] = array();
		foreach ($images as $image) {
			// alterar para colocar as imagens da variacao 
			$product['vteximages'][$image['ImageName']] = $image['ImageUrl'];
		}

		// Busco os atributos específicos do produto para este marketplace 
		$prodspec = array();
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCat,$this->int_to);
		foreach($atributosCat as $atributoCat) {
			$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($product['id'],$atributoCat['id_atributo'],$this->int_to);
			if ($atributo_prd) {
				$prodspec[] = array(
					'FieldId' => 0, 
					'FieldName' => $atributo_prd['nome'], 
					'FieldValueIds' => null, 
					'FieldValues' => array($atributo_prd['valor']), 
					
				);
			}
		}

		// mando a suggestion				
        $data = [
            'BrandId'                    => $product['brandvtex'],
            'BrandName'                  => $brand['name'],
            'CategoryFullPath'           => str_replace(' > ','/',$category['name']),
            'CategoryId'                 => (int)$idCat,
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
            'ListPrice'                  => (int)ceil($product['price']*100),
            'ModalId'                    => null,
            'Price'                      => (int)ceil($product['promotional_price']*100),   
            'ProductDescription'         => $product['description'],
            'ProductId'                  => ((trim($product['ref_id']) ? $product['ref_id'] : null)),
            'ProductName'                => $product['name'],
            'ProductSpecifications'      => $prodspec,
            'ProductSupplementaryFields' => null,
            'RefId'                      => ((trim($product['ref_id']) ? $product['ref_id'] : null)), // obrigatório quando o EAN não for enviado
            'SellerId'                   => $sellerId,
            'SellerModifiedDate'         => null,
            'SellerStockKeepingUnitId'   => $product['sku'],
            'SkuId'                      => null,
            'SkuName'                    => $product['skuname'],
            'SkuSpecifications'          => $spec,
            'SkuSupplementaryFields'     => null,
            'SynonymousPropertyNames'    => null,
            'WeightKg'                   => (float)$product['peso_bruto'],
            'Width'                      => (float)$product['largura'],
        ];
		
		
		var_dump($data);
		
        $bodyParams  = json_encode($data);
        $endPoint    = "api/catalog_system/pvt/sku/SuggestionInsertUpdatev2";
    
    	echo "Enviando sugestão de SKU.\n";
	    $skuInserted = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $product['id'], $this->int_to, 'Sugestão');

        if ($this->responseCode != 200) {
            $notice = "Falha no envio de sugestão de SKU do produto ".$product['id']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true).' enviado: '.print_r($bodyParams,true);
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            return;
        }
		
		echo "Iniciando match do produto.\n";
        $matchOk = $this->skuToMatch($product, $sellerId);

        if ($matchOk['success'] == false) {
            $this->errorTransformation($product['id'],$product['sku'], $matchOk['error']);
            return;
        }
		$vtexprodid = $matchOk['ProductId']; 
		if (is_null($mkt_product_id)) {
			// Agora posso pegar o SKU de verdade  
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$matchOk['SkuId'];
			$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $product['id'], $this->int_to, 'Ler Sku Completo');
			if ($this->responseCode != 200) {
				$notice = "Falha ao ler SKU do produto ".$product['id']." id vtex:".$matchOk['SkuId']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true);
                echo $notice."\n";
                $this->log_data('batch', $log_name, $notice,"E");
                return;
			}
			$sku_vtex  = json_decode($this->result);
			$mkt_product_id = $sku_vtex->ProductId;					
		}
				
		// fez match então ficou ok e pode colocar o status_int = 2
        echo "Match realizado com sucesso! Id do produto na local: ".$product['id']."; Id do produto na Vtex: ".$vtexprodid." sku na vtex ".$matchOk['SkuId']."\n";
		
		$loja  = $this->model_stores->getStoresData($product['store_id']);
		
        $toSaveUltEnvio = [
            'int_to' => $this->int_to,
            'company_id' => $product['company_id'],
            'EAN' => $product['EAN'],
            'prd_id' => $product['id'],
            'price' => $product['promotional_price'],
            'sku' => $product['sku'],
            'data_ult_envio' => $dateLastInt, 
            'qty_atual' => $product['qty'],
            'largura' => $product['largura'],
            'skumkt' => $product['sku'],
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
			'variant' => $product['variant'],
        ];
    	$savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($product['id'], $this->int_to, $product['variant'], $toSaveUltEnvio);

        if (!$savedUltEnvio) {
            $notice = "Falha ao tentar gravar dados na tabela vtex_ult_envio.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        } 
		
        $toSavePrd = [
            'prd_id' => $product['id'],
            'company_id' => $product['company_id'],
            'status' => 1,
            'status_int' => 2,
            'date_last_int' => $dateLastInt, 
            'skumkt' => $product['sku'],
            'skubling' => $product['sku'],
            'int_to' => $this->int_to,
            'store_id' => $product['store_id'],
            'seller_id' => $sellerId,
            'approved' => 1,
            'int_id' => $integration['id'],
            'user_id' => 0,
            'int_type' => 0, 
            'variant' => $product['variant'],
            'mkt_product_id' => $vtexprodid, 
            'mkt_sku_id' => $matchOk['SkuId']
        ];
		
		// $PrdIntId = $this->model_integrations->createPrdToIntegration($toSavePrd);
		$PrdIntId = $this->model_integrations->createIfNotExist($product['id'], $this->int_to, $product['variant'], $toSavePrd);
		
        if (!$PrdIntId) {
            $notice = "Falha ao tentar gravar produto na tabela prd_to_integration.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        }
	
	}

    private function skuToMatch($product, $sellerId)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		/*if (!(array_key_exists('ref_id', $product)) || !(array_key_exists('sku_id', $product))) {
			return false;
		}
		if (is_null($product['ref_id']) || ($product['ref_id'] == '')) {
			return false;
		}
		if (is_null($product['sku_id']) || ($product['sku_id'] == '')) {
			return false;
		}*/
		
        echo "Pegando version do SKU.\n";
        $version = $this->getVersion($sellerId, $product['sku'],  $product['id']);
		echo "version ". $version."\n";
        if ($version == false) {
            return array('success' => false, 'error' => 'Não achou uma versão da suggestion');
        }

		$data = [
            'matcherId' => 2,
            'matchType' => 'productMatch',
            'score' => 90,
            'productRef' => $product['ref_id'],
            'product' => [
            	'name' => $product['name'],
            	'description' => $product['description'],
            	'categoryId' => $product['categoryvtex'], 
            	'brandId' => $product['brandvtex'], 
            ],
            'SKU' => [
                'name' => $product['skuname'],
                'eans' => [
                    $product['EAN']
                ],
                'refId' => $product['ref_id'],
                'height' => $product['altura'],
                'width' => $product['largura'],
                'length' => $product['profundidade'],
                'weight' => $product['peso_bruto'],
                'Images' => $product['vteximages'], 
                'unitMultiplier' => '1.0000',   
                'measurementUnit' => 'un'
            ]
        ];

        $bodyParams = json_encode($data);

        echo "Concluindo match.\n";
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$sellerId.'/'.$product['sku'].'/versions/'.$version.'/matches/1';
		
        $this->processURL($this->auth_data, $url,'PUT', $bodyParams, $product['id'], $this->int_to, 'Match');

		$result = json_decode($this->result);
		var_dump($result);
		echo "httpcode = ".$this->responseCode."\n";
		if ($this->responseCode !== 200) {
			return (array('success' => false, 'error' => $result->Error->Message)) ;
			/*Resposta com Erro 
				object(stdClass)#40 (1) {
				  ["Error"]=>
				  object(stdClass)#42 (2) {
				    ["Code"]=>
				    int(2)
				    ["Message"]=>
				    string(32) "The property BrandId is required"
				  }
				} */
		}
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
		*/
        return (array('success' => true, 'ProductId' => $result->ProductId, 'SkuId' =>$result->SkuId)) ;
    }

    private function getVersion($sellerId,$sku, $product_id)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$sellerId.'/'.$sku.'/versions';

        $this->processURL($this->auth_data, $url, 'GET',null, $product_id, $this->int_to, 'Última versão de sugestão');

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
    { // ALTERAR PARA COLOCAR AS IMAGENS DE VARIACAO TB
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
