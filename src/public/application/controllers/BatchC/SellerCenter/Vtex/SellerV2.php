<?php

require_once APPPATH . "libraries/Helpers/StringHandler.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";
require_once APPPATH . "libraries/Marketplaces/Utilities/Store.php";

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

/**
 * Class SellerV2
 * @property CI_Loader $load
 * @property Microservices\v1\Logistic\Shipping $ms_shipping
 * @property Marketplaces\Utilities\Store $marketplace_store
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Model_stores $model_stores
 */
class SellerV2 extends Main
{
	var $vtex_callback_url = '';
	
	private $vtex_seller_prefix =''; 

	private $vtex_seller_name_no_prefix = false;
	
	var $useHybridPaymentOption = false; 
	var $int_from = null;

    private $mainIntegration;

    private $fulfillmentEndpoint = [];

    public function __construct()
    {
        parent::__construct();
		
		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		
        $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_catalogs');
        $this->load->model('model_vtex_ult_envio');

        $this->load->library("Microservices\\v1\\Logistic\\Shipping", [], 'ms_shipping');
        $this->load->library("Marketplaces\\Utilities\\Store", [], 'marketplace_store');
    }
	
	// php index.php BatchC/SellerCenter/Vtex/SellerV2 run null CasaeVideo
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
		
		 /* faz o que o job precisa fazer */
		 if(is_null($params)  || ($params == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
			if($integration){
				$this->int_to=$integration['int_to'];
				echo 'Sync: '. $integration['int_to']."\n";
				$this->useHybridPaymentOption = False;
				$this->int_from = 'HUB';
                $this->mainIntegration = $integration;
				$this->createSeller($integration);
				$this->updateSeller($integration);
            }
			else {
				echo PHP_EOL .$params." não tem integração definida". PHP_EOL;
			}
		}

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}
	
    public function createSeller($main_integration)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->vtex_seller_prefix = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix');
		If (!$this->vtex_seller_prefix) {
			$this->vtex_seller_prefix = '';
		}
		
		$this->vtex_callback_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
		If (!$this->vtex_callback_url) {
			$this->vtex_callback_url = base_url() ;
		}

		$this->vtex_seller_name_no_prefix = $this->model_settings->getValueIfAtiveByName('vtex_seller_name_no_prefix');
		If ($this->vtex_seller_name_no_prefix) {
			$this->vtex_seller_name_no_prefix = true;
		}

		// verifica se será publicada apenas lojas do parametro no marketplace
        $onlyStorePublished     = null;
        $onlyCompanyPublished   = null;
        $onlyStorePublishedSetting = $this->model_settings->getSettingDatabyName('publish_only_one_store_company');
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');

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

			if ($store['flag_store_migration'] == 1) continue;

			if ($store['type_store'] == 2) {
				echo "Pulando a loja ".$store['id']." pois a mesma é a loja CD de uma empresa Multi-CD\n";
				continue;
			}
			
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

				if($this->vtex_seller_name_no_prefix){
					$nomeLojaVtex = $store['name'];
				}else{
					$nomeLojaVtex = $this->vtex_seller_prefix.$store['name'];
				}

				$data = [
					'SellerId'                    => $sellerId, //string - id do vendedor
					'Name'                        => $nomeLojaVtex, //string - nome do vendedor
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
					'FulfillmentEndpoint'         => $this->retrieveFulfillmentEndpoint(), //string (Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/fulfillment?affiliateId={IdAfiliado}&sc={IdPoliticaComercial})
					'CatalogSystemEndpoint'       => $this->vtex_callback_url.'Api/Integration/Vtex/ControlProduct', //string ( Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/catalog_system/)
					'IsActive'                    => $store['active'] == 1 ? true : false, //boolean (Campo que ativa ou inativa o seller)
					'FulfillmentSellerId'         => $sellerId, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
					'SellerType'                  => 1, //int32 Seller type. Add 1 to a normal seller or 2 to a seller whitelabel
					'IsBetterScope'               => false //boolean
				];

				$bodyParams    = json_encode($data);
				$endPoint      = 'api/catalog_system/pvt/seller';
				$this->processNew($main_auth_data, $endPoint, 'POST', $bodyParams);
                var_dump($this->result);

                if ($this->responseCode == 400) {
                    $result_decode = json_decode($this->result);
                    if (
                        $result_decode &&
                        property_exists($result_decode, 'error') &&
                        $result_decode->error == 'existing seller'
                    ) {
                        echo "Seller $sellerId já existe. Atualizar para criar o registro no banco\n";
                        $this->processNew($main_auth_data, $endPoint, 'PUT', $bodyParams);
                        var_dump($this->result);
                    }
                }

				if ($this->responseCode != 200) {
					$erro = "Erro Endpoint ".$endPoint." httpcode=".$this->responseCode." RESPOSTA ".print_r($this->result ,true)." ENVIADO ".print_r($bodyParams ,true);
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"E");
					continue;
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

                try {
                    if ($external_marketplace_integration) {
                        $this->marketplace_store->setExternalIntegration($external_marketplace_integration);
                        $this->marketplace_store->external_integration->notifyStore($store['id']);
                        echo "Notificação de criação da loja enviada com sucesso para a loja $store[id]\n";
                    }
                } catch (Exception | Error $exception) {
                    echo "Não foi possível notificar o integrador externo sobre a criação da loja $store[id]. {$exception->getMessage()}\n";
                    $this->log_data('batch', $log_name, "Não foi possível notificar o integrador externo sobre a criação da loja $store[id]. {$exception->getMessage()}", "E");
                }
			}
		}
		
    }

	public function updateSeller($main_integration)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->vtex_seller_prefix = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix');
		If (!$this->vtex_seller_prefix) {
			$this->vtex_seller_prefix = '';
		}

		$this->vtex_callback_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
		If (!$this->vtex_callback_url) {
			$this->vtex_callback_url = base_url() ;
		}
		
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
			if (!$store) {
				echo " A Loja ".$integration['store_id']." Foi removida ou está inativa\n"; 
				continue; 
			}
			
			if ($store['active'] == 6) {  // lojas com active == 6 não foram migradas 
				continue;
			}

			if (($store['active'] == 1) && ($integration['active']==0)) {  // integration inativa indica que está no processo de migracao de loja
				continue;
			}

			$separateIntegrationData = json_decode($integration['auth_data']);
			
			if ($store['date_update'] > $separateIntegrationData->date_integrate ) {
				echo 'Alterando no marketplace '.$main_integration['int_to'].' a loja '.$store['id'].' '.$store['name']."\n"; 
				$auth_data = json_decode($integration['auth_data']);
				$sellerId = $auth_data->seller_id;

				if($this->vtex_seller_name_no_prefix){
					$nomeLojaVtex = $store['name'];
				}else{
					$nomeLojaVtex = $this->vtex_seller_prefix.$store['name'];
				}

				$data = [
					'SellerId'                    => $sellerId, //string - id do vendedor
					'Name'                        => $nomeLojaVtex, //string - nome do vendedor
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
					'FulfillmentEndpoint'         => $this->retrieveFulfillmentEndpoint(), //string (Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/fulfillment?affiliateId={IdAfiliado}&sc={IdPoliticaComercial})
					'CatalogSystemEndpoint'       => $this->vtex_callback_url.'Api/Integration/Vtex/ControlProduct', //string ( Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/catalog_system/)
					'IsActive'                    => $store['active'] == 1 ? true : false, //boolean (Campo que ativa ou inativa o seller)
					'FulfillmentSellerId'         => $sellerId, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
					'SellerType'                  => 1, //int32 Seller type. Add 1 to a normal seller or 2 to a seller whitelabel
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
				$endPoint      = 'api/catalog_system/pvt/seller';
				$this->processNew($main_auth_data, $endPoint, 'PUT', $bodyParams);
				var_dump($this->result);
				if ($this->responseCode != 200) {
					$erro = "Erro Endpoint ".$endPoint." httpcode=".$this->responseCode." RESPOSTA ".print_r($this->result ,true)." ENVIADO ".print_r($bodyParams ,true);
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"E");
					continue;
				}
				$integration['auth_data'] = json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $sellerId));
				$integration['active'] = $store['active'] == 1 ? 1 : 2;
				$this->model_integrations->update($integration, $integration['id']); 
			
			}
		}
    }

    protected function retrieveFulfillmentEndpoint(): string
    {
        $intTo = \libraries\Helpers\StringHandler::slugify($this->mainIntegration['int_to'], '_');
        if (!empty($this->fulfillmentEndpoint[$intTo] ?? '')) return $this->fulfillmentEndpoint[$intTo];

        $this->fulfillmentEndpoint[$intTo] = $this->ms_shipping->buildQuoteSimulationEndPoint('vtex', $this->mainIntegration['int_to'])
            ?? "{$this->vtex_callback_url}Api/SellerCenter/Vtex/{$this->mainIntegration['int_to']}";

        // salvar como parâmetro para consulta
        $settingName = sprintf("%s:%s", 'current_fulfillment_endpoint', $intTo);
        if ($settingId = $this->model_settings->getSettingbyName($settingName)) {
            $this->model_settings->update([
                'value' => $this->fulfillmentEndpoint[$intTo]
            ], $settingId);
        } else {
            $this->model_settings->create([
                'name' => $settingName,
                'value' => $this->fulfillmentEndpoint[$intTo],
                'status' => 2,
                'setting_category_id' => 1,
                'friendly_name' => 'Endpoint de fulfillment gerado automaticamente',
                'description' => 'Endpoint de fulfillment utilizado para consulta de frete e criação de pedidos (MS e Monolito).',
            ]);
        }

        return $this->fulfillmentEndpoint[$intTo];
    }
}