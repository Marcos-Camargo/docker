<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class GrupoSoma_Seller extends Main
{

	private $vtex_seller_prefix =''; 
	var $vtex_callback_url = '';
	 
    public function __construct()
    {
        parent::__construct();
		
		$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
        $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_catalogs');
        $this->load->model('model_vtex_ult_envio');

    }
 
	// php index.php BatchC/SellerCenter/GrupoSoma/GrupoSoma_Seller run
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
		
		$this->vtex_seller_prefix = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix');
		If (!$this->vtex_seller_prefix) {
			$this->vtex_seller_prefix = '';
		};
		
		$this->vtex_callback_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
		If (!$this->vtex_callback_url) {
			$this->vtex_callback_url = base_url() ;
		};
		
		/* faz o que o job precisa fazer */
	    $retorno = $this->createSeller();
	    $retorno = $this->updateSeller();
		$retorno = $this->checkSellersCatalog();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
    public function createSeller()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	// Primeiro pego as integracoes principais dos marketplaces 
    	$main_integrations =  $this->model_integrations->getIntegrationsbyStoreId(0); 
		
		foreach ($main_integrations as $main_integration) {
			echo 'Verificando novas lojas para '.$main_integration['int_to']."\n";
			$catalog = $this->model_catalogs->getCatalogByName($main_integration['int_to']);
			if (!$catalog) {
				$erro = "Não encontrei o catálogo ".$main_integration['int_to']." Crie o catálogo e execute novamente.";
				echo $erro."\n"; 
				$this->log_data('batch',$log_name, $erro,"E");
				continue;
			}
			
			$main_auth_data = json_decode($main_integration['auth_data']);
			$stores = $this->model_stores->getAllActiveStore(); 
			foreach ($stores as $store) {
				$storeCatalog = $this->model_catalogs->verifyStoreOnCatalog($catalog['id'],$store['id']);
				if (!$storeCatalog) { // essa loja não usa este catálogo
					continue ; 
				}
				
				$integration =  $this->model_integrations->getIntegrationbyStoreIdAndInto($store['id'], $main_integration['int_to']);
				
				if (!$integration) {
					echo 'Criando integração no marketplace '.$main_integration['int_to'].' para a loja '.$store['id'].' '.$store['name']."\n"; 
					$sellerId =  $store['id']; 
					if (strlen ($sellerId ) < 3) {
						$sellerId = substr('000'.$sellerId,-3);
					}
					
					if ($store['associate_type'] == 6 ) { // Franquia, pega o parametro da Franquia 
						$prefix_seller_id = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix_'.$main_integration['int_to']);
						If (!$prefix_seller_id) {
							$erro = "Não existe o parametro ".'vtex_seller_prefix_'.$main_integration['int_to']." para definir o prefixo de franquia para a loja ".$store['id'].". Impossível criar na Vtex.";
							echo $erro."\n"; 
							$this->log_data('batch',$log_name, $erro,"E");
							continue;
						};
						$sellerId = $prefix_seller_id.$sellerId;
						$name_store = $prefix_seller_id.$store['name'];
					}
					else  {
						$sellerId = $this->vtex_seller_prefix.$sellerId; 
						$name_store = $this->vtex_seller_prefix.$store['name'];
					}
									
					$sellerId = preg_replace("/[^A-Za-z0-9 ]/", '', $sellerId);
					$data = [
		                'SellerId'                    => $sellerId, //string - id do vendedor
		                'Name'                        => $name_store, //string - nome do vendedor
		                'Email'                       => $store['responsible_email'], //string - E-mail do Administrador
		                'Description'                 => $store['description'], //string - Descrição do vendedor (É a descrição do seller que pode aparecer na loja marketplace, ou seja, é um texto comercial para o seller. Ex.: "A LojaVTEX é especializada em vender...)
		                'ExchangeReturnPolicy'        => $store['exchange_return_policy'], //string - Detalhes sobre a política de devolução de troca do vendedor (Este campo só deve ser preenchido caso seja acordado previamente entre marketplace e seller)
		                'DeliveryPolicy'              => $store['delivery_policy'], //string - Detalhes sobre a política de entrega do vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
		                'UseHybridPaymentOptions'     => false, //boolean - Se usará o pagamento híbrido entre vendedor e mercado
		                'UserName'                    => '', //string - UserName caso a integração não seja entre lojas VTEX
		                'Password'                    => '', //string - Senha caso a integração não seja entre lojas VTEX
		                'SecutityPrivacyPolicy'       => $store['security_privacy_policy'], //string - Detalhes sobre a Política de Privacidade de Segurança do Vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
		                'CNPJ'                        => preg_replace('/\D/', '', $store['CNPJ']), //string - Documento de registro da empresa vendedora
		                'CSCIdentification'           => '', //string - Identificação do vendedor
		                'ArchiveId'                   => 0, //string - Defina qual separador decimal de moeda será aplicado
		                'UrlLogo'                     => base_url() . $store['logo'], //string - Logotipo do URL do vendedor
		                'ProductCommissionPercentage' => $store['service_charge_value'], //float (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
		                'FreightCommissionPercentage' => $store['service_charge_freight_value'], //int32 (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
		                'FulfillmentEndpoint'         => $this->vtex_callback_url.'Api/SellerCenter/Vtex/'.$this->tiraAcentos($main_integration['int_to']), //string (Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/fulfillment?affiliateId={IdAfiliado}&sc={IdPoliticaComercial})
		                'CatalogSystemEndpoint'       => $this->vtex_callback_url.'Api/Integration/Vtex/ControlProduct', //string ( Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/catalog_system/)
		                'IsActive'                    => $store['active'] == 1 ? true : false, //boolean (Campo que ativa ou inativa o seller)
		                'FulfillmentSellerId'         => $sellerId, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
		                'SellerType'                  => 1, //int32 Seller type. Add 1 to a normal seller or 2 to a seller whitelabel
		                'IsBetterScope'               => false //boolean
		            ];

		            $bodyParams    = json_encode($data);
		            $endPoint      = 'api/catalog_system/pvt/seller/'.$data['SellerId'];
		            $sellerCreated = $this->processNew($main_auth_data, $endPoint, 'POST', $bodyParams);
					var_dump($this->result);
					if ($this->responseCode != 200) {
						$erro = "Erro Endpoint ".$endPoint." httpcode=".$this->responseCode." RESPOSTA ".print_r($this->result ,true)." ENVIADO ".print_r($bodyParams ,true);
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"E");
						die;
					}
					$data_int = array(
						'name' => $main_integration['name'],
						'active' => $main_integration['active'],
						'store_id' => $store['id'],
						'company_id' => $store['company_id'],
						'auth_data' => json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $sellerId)),
						'int_type' => 'BLING',
						'int_from' => $main_integration['int_from'],
						'int_to' => $main_integration['int_to'], 
						'auto_approve' => $main_integration['auto_approve'] 
					); 
					$this->model_integrations->create($data_int); 
				}
			}
		}
    }

	public function updateSeller()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	// Primeiro pego as integracoes principais dos marketplaces
    	$main_integrations =  $this->model_integrations->getIntegrationsbyStoreId(0); 
		foreach ($main_integrations as $main_integration) {
			echo 'Verificando alteração de lojas para '.$main_integration['int_to']."\n";
			$main_auth_data = json_decode($main_integration['auth_data']);
			
			$catalog = $this->model_catalogs->getCatalogByName($main_integration['int_to']);
			if (!$catalog) {
				$erro = "Não encontrei o catálogo ".$main_integration['int_to']." Crie o catálogo e execute novamente.";
				echo $erro."\n"; 
				$this->log_data('batch',$log_name, $erro,"E");
				continue;
			}
			
			// agora pego as integrações existes
			$integrations =  $this->model_integrations->getAllIntegrationsbyType('BLING'); 
			foreach ($integrations as $integration) {
				if ($integration['int_to'] !== $main_integration['int_to']) {
					continue;
				}
				$storeCatalog = $this->model_catalogs->verifyStoreOnCatalog($catalog['id'],$integration['store_id']);
				if (!$storeCatalog) { // essa loja não usa este catálogo
					continue ; 
				}
				
				// puxo a loja 
				$store = $this->model_stores->getStoresData($integration['store_id']);
				
				
				$separateIntegrationData = json_decode($integration['auth_data']);
				
				if ($store['date_update'] > $separateIntegrationData->date_integrate ) {
					echo 'Alterando no marketplace '.$main_integration['int_to'].' a loja '.$store['id'].' '.$store['name']."\n"; 
					$auth_data = json_decode($integration['auth_data']);
            		$sellerId = $auth_data->seller_id;
					
					if ($store['associate_type'] == 6 ) { // Franquia, pega o parametro da Franquia 
						$prefix_seller_id = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix_'.$main_integration['int_to']);
						If (!$prefix_seller_id) {
							$erro = "Não existe o parametro ".'vtex_seller_prefix_'.$main_integration['int_to']." para definir o prefixo de franquia para a loja ".$store['id'].". Impossível criar na Vtex.";
							echo $erro."\n"; 
							$this->log_data('batch',$log_name, $erro,"E");
							continue;
						};
						$name_store = $prefix_seller_id.$store['name'];
					}
					else  {
						$name_store = $this->vtex_seller_prefix.$store['name'];
					}
					
					$data = [
						'SellerId'                    => $sellerId, //string - id do vendedor
		                'Name'                        => $name_store, //string - nome do vendedor
		                'Email'                       => $store['responsible_email'], //string - E-mail do Administrador
		                'Description'                 => $store['description'], //string - Descrição do vendedor (É a descrição do seller que pode aparecer na loja marketplace, ou seja, é um texto comercial para o seller. Ex.: "A LojaVTEX é especializada em vender...)
		                'ExchangeReturnPolicy'        => $store['exchange_return_policy'], //string - Detalhes sobre a política de devolução de troca do vendedor (Este campo só deve ser preenchido caso seja acordado previamente entre marketplace e seller)
		                'DeliveryPolicy'              => $store['delivery_policy'], //string - Detalhes sobre a política de entrega do vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
		                'UseHybridPaymentOptions'     => false, //boolean - Se usará o pagamento híbrido entre vendedor e mercado
		                'UserName'                    => '', //string - UserName caso a integração não seja entre lojas VTEX
		                'Password'                    => '', //string - Senha caso a integração não seja entre lojas VTEX
		                'SecutityPrivacyPolicy'       => $store['security_privacy_policy'], //string - Detalhes sobre a Política de Privacidade de Segurança do Vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
		                'CNPJ'                        => preg_replace('/\D/', '', $store['CNPJ']), //string - Documento de registro da empresa vendedora
		                'CSCIdentification'           => '', //string - Identificação do vendedor
		                'ArchiveId'                   => 0, //string - Defina qual separador decimal de moeda será aplicado
		                'UrlLogo'                     => base_url() . $store['logo'], //string - Logotipo do URL do vendedor
		                'ProductCommissionPercentage' => $store['service_charge_value'], //float (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
		                'FreightCommissionPercentage' => $store['service_charge_freight_value'], //int32 (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
		                'FulfillmentEndpoint'         => $this->vtex_callback_url.'Api/SellerCenter/Vtex/'.$this->tiraAcentos($main_integration['int_to']), //string (Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/fulfillment?affiliateId={IdAfiliado}&sc={IdPoliticaComercial})
		                'CatalogSystemEndpoint'       => $this->vtex_callback_url.'Api/Integration/Vtex/ControlProduct', //string ( Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/catalog_system/)
		                'IsActive'                    => $store['active'] == 1 ? true : false, //boolean (Campo que ativa ou inativa o seller)
		                'FulfillmentSellerId'         => $sellerId, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
		                'SellerType'                  => 1, //int32 Seller type. Add 1 to a normal seller or 2 to a seller whitelabel
		                'IsBetterScope'               => false //boolean
		            ];
					
					$bodyParams    = json_encode($data);
					var_dump($data);
		            $endPoint      = 'api/catalog_system/pvt/seller/'.$sellerId;
		            $sellerUpdated = $this->processNew($main_auth_data, $endPoint, 'PUT', $bodyParams);
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
    }

	// php index.php BatchC/SellerCenter/Vtex/Seller checkSellers
	public function checkSellers() 
	{ 
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

    	// Primeiro pego as integracoes principais dos marketplaces
    	$main_integrations =  $this->model_integrations->getIntegrationsbyStoreId(0); 
		foreach ($main_integrations as $main_integration) {
			echo 'Verificando lojas na VTEX para '.$main_integration['int_to']."\n";
			$main_auth_data = json_decode($main_integration['auth_data']);
			// agora pego as integrações existes
			$integrations =  $this->model_integrations->getAllIntegrationsbyType('BLING'); 
			foreach ($integrations as $integration) {
				if ($integration['int_to'] !== $main_integration['int_to']) {
					continue;
				}
				// puxo a loja 
				$store = $this->model_stores->getStoresData($integration['store_id']);

				$separateIntegrationData = json_decode($integration['auth_data']);
				$auth_data = json_decode($integration['auth_data']);
        		$sellerId = $auth_data->seller_id;
				
				echo ' Loja '.$store['id'].' '.$store['name']." VTEX SellerId ".$sellerId."..."; 
				
	            $endPoint      = 'api/catalog_system/pvt/seller/'.$sellerId;
	            
	            $sellerData = $this->processNew($main_auth_data, $endPoint);
				
				if ($this->responseCode != 200) {
					$erro = "Erro Endpoint ".$endPoint." httpcode=".$this->responseCode." RESPOSTA ".print_r($this->result ,true);
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"E");
					die;
				}
				$return = json_decode($this->result);
				
				if ($return->IsActive) {
					echo " Ativa\n";
				}
				else {
					echo " Inativa\n";
					if ($store['active'] == 1) {
						echo "   Inativando no Sistema\n";
						//$this->model_stores->update(array('active' => 2), $store['id']);
			    		//$this->model_integrations->update(array('active' => 2), $integration['id']); 
					}
				}
				var_dump($this->result);
			}
		}
	}

	public function checkSellersCatalog() 
	{ 
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$main_integrations =  $this->model_integrations->getIntegrationsbyStoreId(0); 
		echo "Verifico lojas que sairam do catálogo\n";
		foreach ($main_integrations as $main_integration) {
			echo 'Verificando lojas a inativar para '.$main_integration['int_to']."\n";
			$main_auth_data = json_decode($main_integration['auth_data']);
			$catalog = $this->model_catalogs->getCatalogByName($main_integration['int_to']);
			if (!$catalog) {
				$erro = "Não encontrei o catálogo ".$main_integration['int_to']." Crie o catálogo e execute novamente.";
				echo $erro."\n"; 
				$this->log_data('batch',$log_name, $erro,"E");
				continue;
			}

			$stores = $this->model_stores->getStoresData(); 
			foreach ($stores as $store) {
				// verifico se a loja está no catálogo
				$storeCatalog = $this->model_catalogs->verifyStoreOnCatalog($catalog['id'],$store['id']);
				if ($storeCatalog) { // essa loja usa este catálogo, tudo ok. 
					continue; 
				}
				// A loja não usa o catálogo 
				// verifico se tem integração com este marketplace  
				$integration =  $this->model_integrations->getIntegrationbyStoreIdAndInto($store['id'], $main_integration['int_to']);
				if (!$integration) { // se está sem integração, tudo bem
					echo "Loja ".$store['id']." não tem integração". "\n"; 
					continue ; 
				}
				if ($integration['active'] != 1) { // a integração já está inativa, tudo bem
					echo "Loja ".$store['id']." tem integração inativa". "\n"; 
					continue ;
				}
				$auth_data = json_decode($integration['auth_data']);
				if (!property_exists($auth_data,"seller_id")) {
					// se não existe a propriedade, inativa a integração 
					echo "Loja ".$store['id']." nunca integrado". "\n"; 
					$int_data = array(
						'active' => 2,
					);
					// $this->model_integrations->update($int_data, $integration['id']); 
					continue;
				}
				echo "Loja ".$store['id']." sendo inativada na ".$main_integration['int_to']. "\n"; 
				// agora sim, inativo na Vtex ....
				$sellerId = $auth_data->seller_id;
				
				if ($store['associate_type'] == 6 ) { // Franquia, pega o parametro da Franquia 
					$prefix_seller_id = $this->model_settings->getValueIfAtiveByName('vtex_seller_prefix_'.$main_integration['int_to']);
					If (!$prefix_seller_id) {
						$erro = "Não existe o parametro ".'vtex_seller_prefix_'.$main_integration['int_to']." para definir o prefixo de franquia para a loja ".$store['id'].". Impossível criar na Vtex.";
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"E");
						continue;
					};
					$name_store = $prefix_seller_id.$store['name'];
				}
				else  {
					$name_store = $this->vtex_seller_prefix.$store['name'];
				}
				
				$data = [
						'SellerId'                    => $sellerId, //string - id do vendedor
		                'Name'                        => $name_store, //string - nome do vendedor
		                'Email'                       => $store['responsible_email'], //string - E-mail do Administrador
		                'Description'                 => $store['description'], //string - Descrição do vendedor (É a descrição do seller que pode aparecer na loja marketplace, ou seja, é um texto comercial para o seller. Ex.: "A LojaVTEX é especializada em vender...)
		                'ExchangeReturnPolicy'        => $store['exchange_return_policy'], //string - Detalhes sobre a política de devolução de troca do vendedor (Este campo só deve ser preenchido caso seja acordado previamente entre marketplace e seller)
		                'DeliveryPolicy'              => $store['delivery_policy'], //string - Detalhes sobre a política de entrega do vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
		                'UseHybridPaymentOptions'     => false, //boolean - Se usará o pagamento híbrido entre vendedor e mercado
		                'UserName'                    => '', //string - UserName caso a integração não seja entre lojas VTEX
		                'Password'                    => '', //string - Senha caso a integração não seja entre lojas VTEX
		                'SecutityPrivacyPolicy'       => $store['security_privacy_policy'], //string - Detalhes sobre a Política de Privacidade de Segurança do Vendedor (Este campo só deve ser preenchido caso seja acordado entre marketplace e seller para melhor abordar esse tipo de ação)
		                'CNPJ'                        => preg_replace('/\D/', '', $store['CNPJ']), //string - Documento de registro da empresa vendedora
		                'CSCIdentification'           => '', //string - Identificação do vendedor
		                'ArchiveId'                   => 0, //string - Defina qual separador decimal de moeda será aplicado
		                'UrlLogo'                     => base_url() . $store['logo'], //string - Logotipo do URL do vendedor
		                'ProductCommissionPercentage' => (float)$store['service_charge_value'], //float (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
		                'FreightCommissionPercentage' => (float)$store['service_charge_freight_value'], //int32 (É o percentual que deverá ser preenchido conforme o acordado entre marketplace e seller. Caso não haja esse comissionamento, deve-se preencher obrigatoriamente o campo com o valor: 0,00)
		                'FulfillmentEndpoint'         => $this->vtex_callback_url.'Api/SellerCenter/Vtex/'.$this->tiraAcentos($main_integration['int_to']), //string (Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/fulfillment?affiliateId={IdAfiliado}&sc={IdPoliticaComercial})
		                'CatalogSystemEndpoint'       => $this->vtex_callback_url.'Api/Integration/Vtex/ControlProduct', //string ( Deve ser preenchido com o valor: http://{NomeDoSeller}.vtexcommercestable.com.br/api/catalog_system/)
		                'IsActive'                    => false, //boolean (Campo que ativa ou inativa o seller)
		                'FulfillmentSellerId'         => $sellerId, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
		                'SellerType'                  => 1, //int32
		                'IsBetterScope'               => false //boolean
		            ];
	
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
				$int_data = array(
					'auth_data' => json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $sellerId)),
					'active' => 2,
				);

				// atualiza dados da loja na tabela vtex_ult_envio
                $toSaveUltEnvio = [
                    'zipcode'                   => preg_replace('/\D/', '', $store['zipcode']),
                    'freight_seller'            => $store['freight_seller'],
                    'freight_seller_end_point'  => $store['freight_seller_end_point'],
                    'freight_seller_type'       => $store['freight_seller_type'],
                ];
                $this->model_vtex_ult_envio->updateDatasStore($store['id'], $toSaveUltEnvio);
				
				$this->model_integrations->update($int_data, $integration['id']); 
				continue;

			}
		}
	}

	public function tiraAcentos($str) {
	 	return strtr(utf8_decode(urldecode($str)),
	                 utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
	                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
	}
}
