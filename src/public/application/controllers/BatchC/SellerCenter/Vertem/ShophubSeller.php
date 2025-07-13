<?php


require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Seller.php";

class ShophubSeller extends Seller
{

	var $auth_data;
	var $int_to; 
	
	public function __construct()
	{
		parent::__construct();

	}

	// php index.php BatchC/SellerCenter/Vertem/ShophubSeller run 
	function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return;
		}
		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
		
		$this->useHybridPaymentOption = True;
		$this->int_from = 'HUB';
		$this->int_to = 'SH';
		$retorno = $this->createSeller();
	    $retorno = $this->updateSeller();

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}
	
	public function createSeller()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	// Primeiro pego as integracoes principais dos marketplaces 
    	
        $main_integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$this->int_to,"CONECTALA","DIRECT",0);
		
		if (!$main_integration) {
			echo "Integration não encontrada para ".$this->int_to."\n";
			return;
		}
		$this->vtex_seller_prefix = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix');
		If (!$this->vtex_seller_prefix) {
			$this->vtex_seller_prefix = '';
		};
		
		$this->vtex_callback_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
		If (!$this->vtex_callback_url) {
			$this->vtex_callback_url = base_url() ;
		};
		
		$catalog_system_endpoint = $this->model_settings->getValueIfAtiveByName('vtex_callback_url_CatalogSystemEndpoint');
		if (!$catalog_system_endpoint) {
			$catalog_system_endpoint = $this->vtex_callback_url.'Api/Integration/Vtex/ControlProduct';
		}
		$fulfillment_endpoint = $this->model_settings->getValueIfAtiveByName('vtex_callback_url_FulfillmentEndpoint');
		If (!$fulfillment_endpoint) {
			$fulfillment_endpoint =$this->vtex_callback_url.'Api/SellerCenter/Vtex/'.$main_integration['int_to'];
		}

		// verifica se será publicada apenas lojas do parametro no marketplace
        $onlyStorePublished     = null;
        $onlyCompanyPublished   = null;
        $onlyStorePublishedSetting = $this->model_settings->getSettingDatabyName('publish_only_one_store_company');
        if ($onlyStorePublishedSetting && $onlyStorePublishedSetting['status'] == 1) {
            $onlyStorePublished     = $onlyStorePublishedSetting['value'];
            $storePublished         = $this->model_stores->getStoresData($onlyStorePublished);
            $onlyCompanyPublished   = $storePublished['company_id'];
            echo "parametro 'publish_only_one_store_company' ativo. somente a loja {$onlyStorePublished} será publicada\n";
        }

		echo 'Verificando novas lojas para '.$main_integration['int_to']."\n";
		
		$main_auth_data = json_decode($main_integration['auth_data']);
		$stores = $this->model_stores->getAllActiveStore(); 
		foreach ($stores as $store) {

		    // o parametro está ativo e tem lojas que somente essas devem ser publicadas
		    if ($store['id'] != $onlyStorePublished && $store['company_id'] == $onlyCompanyPublished) {
		        echo "loja {$store['id']} da empresa {$store['company_id']} não está configurada para ser publicar\n";
		        continue;
            }
			
			$integration =  $this->model_integrations->getIntegrationbyStoreIdAndInto($store['id'], $main_integration['int_to']);
			
			if (!$integration) {
				echo 'Criando integração no marketplace '.$main_integration['int_to'].' para a loja '.$store['id'].' '.$store['name']."\n"; 
				$sellerId =  $store['id']; 
				if (strlen ($sellerId ) < 3) {
					$sellerId = substr('000'.$sellerId,-3);
				}
				$sellerId = $this->vtex_seller_prefix.$sellerId; 				
				$sellerId = preg_replace("/[^A-Za-z0-9 ]/", '', $sellerId);
				$data = [
	                'SellerId'                    => $sellerId, //string - id do vendedor
	                'Name'                        => $store['name'], //string - nome do vendedor
	                'Email'                       => $store['responsible_email'], //string - E-mail do Administrador
	                'Description'                 => $store['description'], //string - Descrição do vendedor (É a descrição do seller que pode aparecer na loja marketplace, ou seja, é um texto comercial para o seller. Ex.: "A LojaVTEX é especializada em vender...)
	                'ExchangeReturnPolicy'        => $store['exchange_return_policy'], //string - Detalhes sobre a política de devolução de troca do vendedor (Este campo só deve ser preenchido caso seja acordado previamente entre marketplace e seller)
	                'DeliveryPolicy'              => $store['delivery_policy'], //string - Detalhes sobre a política de entrega do vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
	                'UseHybridPaymentOptions'     => $this->useHybridPaymentOption, //boolean - Se usará o pagamento híbrido entre vendedor e mercado
	                'UserName'                    => '', //string - UserName caso a integração não seja entre lojas VTEX
	                'Password'                    => '', //string - Senha caso a integração não seja entre lojas VTEX
	                'SecutityPrivacyPolicy'       => $store['security_privacy_policy'], //string - Detalhes sobre a Política de Privacidade de Segurança do Vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
	                'CNPJ'                        => preg_replace('/\D/', '', $store['CNPJ']), //string - Documento de registro da empresa vendedora
	                'CSCIdentification'           => '', //string - Identificação do vendedor
	                'ArchiveId'                   => 0, //string - Defina qual separador decimal de moeda será aplicado
	                'UrlLogo'                     => base_url() . $store['logo'], //string - Logotipo do URL do vendedor
	                'ProductCommissionPercentage' => $store['service_charge_value'], //float (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
	                'FreightCommissionPercentage' => $store['service_charge_freight_value'], //int32 (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
	                'FulfillmentEndpoint'         => $fulfillment_endpoint, //string (Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/fulfillment?affiliateId={IdAfiliado}&sc={IdPoliticaComercial})
	                'CatalogSystemEndpoint'       => $catalog_system_endpoint, //string ( Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/catalog_system/)
	                'IsActive'                    => $store['active'] == 1 ? true : false, //boolean (Campo que ativa ou inativa o seller)
	                'FulfillmentSellerId'         => $sellerId, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
	                'SellerType'                  => 1, //int32
	                'IsBetterScope'               => false //boolean
	            ];

	            $bodyParams    = json_encode($data);
	            $endPoint      = 'api/catalog_system/pvt/seller/'.$data['SellerId'];

				$sellerCreated = $this->processNew($main_auth_data, $endPoint, 'POST', $bodyParams);
				var_dump($this->result);
				if ($this->responseCode != 200) {
					$erro = "Erro Endpoint ".$endPoint." httpcode=".$this->responseCode." RESPOSTA ".print_r($this->result ,true);
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"E");
					die;
				}
				$data_int = array(
					'name' 			=> $main_integration['name'],
					'active' 		=> $main_integration['active'],
					'store_id' 		=> $store['id'],
					'company_id' 	=> $store['company_id'],
					'auth_data' 	=> json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $sellerId)),
					'int_type' 		=> 'BLING',
					'int_from' 		=> is_null($this->int_from) ? $main_integration['int_from'] : $this->int_from,
					'int_to' 		=> $main_integration['int_to'], 
					'auto_approve' 	=> $main_integration['auto_approve'] 
				); 
				$this->model_integrations->create($data_int); 
			}
		}
		
    }

	public function updateSeller()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$main_integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$this->int_to,"CONECTALA","DIRECT",0);
		
		if (!$main_integration) {
			echo "Integration não encontrada para ".$this->int_to."\n";
			return;
		}
		
		$this->vtex_seller_prefix = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix');
		If (!$this->vtex_seller_prefix) {
			$this->vtex_seller_prefix = '';
		};

		$this->vtex_callback_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
		If (!$this->vtex_callback_url) {
			$this->vtex_callback_url = base_url() ;
		};
		
		$catalog_system_endpoint = $this->model_settings->getValueIfAtiveByName('vtex_callback_url_CatalogSystemEndpoint');
		if (!$catalog_system_endpoint) {
			$catalog_system_endpoint = $this->vtex_callback_url.'Api/Integration/Vtex/ControlProduct';
		}
		
		$fulfillment_endpoint = $this->model_settings->getValueIfAtiveByName('vtex_callback_url_FulfillmentEndpoint');
		If (!$fulfillment_endpoint) {
			$fulfillment_endpoint =$this->vtex_callback_url.'Api/SellerCenter/Vtex/'.$main_integration['int_to'];
		}
		
    	// Primeiro pego as integracoes principais dos marketplaces
    	$main_integrations =  $this->model_integrations->getIntegrationsbyStoreId(0); 
		
		echo 'Verificando alteração de lojas para '.$main_integration['int_to']."\n";
		$main_auth_data = json_decode($main_integration['auth_data']);
		
		// agora pego as integrações existes
		$integrations =  $this->model_integrations->getAllIntegrationsbyType('BLING'); 
		foreach ($integrations as $integration) {
			if ($integration['int_to'] !== $main_integration['int_to']) {
				continue;
			}
			
			// puxo a loja 
			$store = $this->model_stores->getStoresData($integration['store_id']);		
			
			if ($store['active'] == 6) {  // lojas com active == 6 não foram migradas 
				continue;
			}

			$separateIntegrationData = json_decode($integration['auth_data']);
			
			if ($store['date_update'] > $separateIntegrationData->date_integrate ) {
				echo 'Alterando no marketplace '.$main_integration['int_to'].' a loja '.$store['id'].' '.$store['name']."\n"; 
				$auth_data = json_decode($integration['auth_data']);
        		$sellerId = $auth_data->seller_id;

				$data = [
					'SellerId'                    => $sellerId, //string - id do vendedor
	                'Name'                        => $store['name'], //string - nome do vendedor
	                'Email'                       => $store['responsible_email'], //string - E-mail do Administrador
	                'Description'                 => $store['description'], //string - Descrição do vendedor (É a descrição do seller que pode aparecer na loja marketplace, ou seja, é um texto comercial para o seller. Ex.: "A LojaVTEX é especializada em vender...)
	                'ExchangeReturnPolicy'        => $store['exchange_return_policy'], //string - Detalhes sobre a política de devolução de troca do vendedor (Este campo só deve ser preenchido caso seja acordado previamente entre marketplace e seller)
	                'DeliveryPolicy'              => $store['delivery_policy'], //string - Detalhes sobre a política de entrega do vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
	                'UseHybridPaymentOptions'     => $this->useHybridPaymentOption, //boolean - Se usará o pagamento híbrido entre vendedor e mercado
	                'UserName'                    => '', //string - UserName caso a integração não seja entre lojas VTEX
	                'Password'                    => '', //string - Senha caso a integração não seja entre lojas VTEX
	                'SecutityPrivacyPolicy'       => $store['security_privacy_policy'], //string - Detalhes sobre a Política de Privacidade de Segurança do Vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
	                'CNPJ'                        => preg_replace('/\D/', '', $store['CNPJ']), //string - Documento de registro da empresa vendedora
	                'CSCIdentification'           => '', //string - Identificação do vendedor
	                'ArchiveId'                   => 0, //string - Defina qual separador decimal de moeda será aplicado
	                'UrlLogo'                     => base_url() . $store['logo'], //string - Logotipo do URL do vendedor
	                'ProductCommissionPercentage' => $store['service_charge_value'], //float (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
	                'FreightCommissionPercentage' => $store['service_charge_freight_value'], //int32 (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
	                'FulfillmentEndpoint'         => $fulfillment_endpoint, //string (Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/fulfillment?affiliateId={IdAfiliado}&sc={IdPoliticaComercial})
	                'CatalogSystemEndpoint'       => $catalog_system_endpoint, //string ( Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/catalog_system/)
	                'IsActive'                    => $store['active'] == 1 ? true : false, //boolean (Campo que ativa ou inativa o seller)
	                'FulfillmentSellerId'         => $sellerId, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
	                'SellerType'                  => 1, //int32
	                'IsBetterScope'               => false //boolean
	            ];

                // atualiza dados da loja na tabela vtex_ult_envio
                $toSaveUltEnvio = [
                    'zipcode'                   => preg_replace('/\D/', '', $store['zipcode']),
                    'freight_seller'            => $store['freight_seller'],
                    'freight_seller_end_point'  => $store['freight_seller_end_point'],
                    'freight_seller_type'       => $store['freight_seller_type'],
                    'CNPJ'                      => $data['CNPJ']
                ];
                $this->model_vtex_ult_envio->updateDatasStore($store['id'], $toSaveUltEnvio);
				
				$bodyParams    = json_encode($data);
	            $endPoint      = 'api/catalog_system/pvt/seller/'.$sellerId;
	         	$sellerUpdated = $this->processNew($main_auth_data, $endPoint, 'PUT', $bodyParams);
				var_dump($this->result);
				if ($this->responseCode != 200) {
					$erro = "Erro Endpoint ".$endPoint." httpcode=".$this->responseCode." RESPOSTA ".print_r($this->result ,true);
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"E");
					die;
				}
				$integration['auth_data'] = json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $sellerId));
				$integration['active'] = $store['active'] == 1 ? 1 : 2;
				$this->model_integrations->update($integration, $integration['id']); 
			}
		}
		
    }

}
