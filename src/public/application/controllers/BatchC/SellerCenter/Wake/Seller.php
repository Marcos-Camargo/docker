<?php

require_once APPPATH . "libraries/Helpers/StringHandler.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

require APPPATH . "controllers/BatchC/SellerCenter/Wake/Main.php";

/**
 * Class SellerV2
 * @property CI_Loader $load
 * @property Microservices\v1\Logistic\Shipping $ms_shipping
 * @property Model_settings $model_settings
 */
class Seller extends Main
{
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
        $this->load->model('model_sellercenter_last_post');

        $this->load->library("Microservices\\v1\\Logistic\\Shipping", [], 'ms_shipping');

    }
	
	// php index.php BatchC/SellerCenter/Wake/Seller run null int_to
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

				$data = [
					'razaoSocial'                 => $store['raz_social'], 
					'email'                       => $store['responsible_email'], 
					'telefone'                    => $store['phone_1'], 
					'cnpj'                        => preg_replace('/\D/', '', $store['CNPJ']), 
					'ativo'                       => $store['active'] == 1 ? true : false, 
					'split'                       => true, 
					'isento'                      => true,// verificar se é necessário
                    'inscricaoEstadual'           => "0000000000",// verificar se é necessário
                    'ativacaoAutomaticaProdutos'  => $store['ativacaoAutomaticaProdutos'] == 1 ? true : false,// validar se precisa desse parametro
                    'cep'                         => $store['zipcode'],
					'buyBox'                      => $store['buybox'] == 1 ? true : false // validar se precisa desse parametro
				];

				$bodyParams    = json_encode($data);
				$endPoint      = 'resellers';
				$this->processNew($main_auth_data, $endPoint, 'POST', $bodyParams);
                var_dump($this->result);

                if ($this->responseCode == 201) {
                    $result_decode = json_decode($this->result);
                    if ($result_decode && !$result_decode->resellerId) {
                        echo "Erro ao criar o Seller: $sellerId \n";
                        var_dump($this->result);
                        continue;
                    }
                }

                if ($this->responseCode == 401) {
                    echo "Erro ao criar o Seller: $sellerId \n";
                }

				if ($this->responseCode != 201) {
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
					'auth_data' 	=> json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $result_decode->resellerId, 'token' => $result_decode->token)),
					'int_type' 		=> 'BLING',
					'int_from' 		=> is_null($this->int_from) ? $main_integration['int_from'] : $this->int_from,
					'int_to' 		=> $main_integration['int_to'], 
					'auto_approve' 	=> $main_integration['auto_approve'] 
				); 
				$this->model_integrations->create($data_int); 
			}
		}
		
    }

	public function updateSeller($main_integration)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
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
				$tokenSeller = $auth_data->token;

				$data = [
					'razaoSocial'                 => $store['raz_social'], //string - nome do vendedor
					'email'                       => $store['responsible_email'], //string - E-mail do Administrador
					'telefone'                    => $store['phone_1'], //string - Detalhes sobre a política de devolução de troca do vendedor (Este campo só deve ser preenchido caso seja acordado previamente entre marketplace e seller)
					'cnpj'                        => preg_replace('/\D/', '', $store['CNPJ']), //string - Documento de registro da empresa vendedora
					'ativo'                       => $store['active'] == 1 ? true : false, //boolean (Campo que ativa ou inativa o seller)
					'split'                       => true, //string (Identificador do seller que irá fazer o fulfillment do pedido Usado quando um seller vende skus de outro seller. Porém quando o seller está vendendo um SKU dele mesmo, este campo deve ficar em branco)
					'isento'                      => true,// verificar se é necessário
                    'inscricaoEstadual'           => "0000000000",// verificar se é necessário
                    'ativacaoAutomaticaProdutos'  => $store['ativacaoAutomaticaProdutos'] == 1 ? true : false,// validar se precisa desse parametro
                    'cep'                         => $store['zipcode'], //int32 Seller type. Add 1 to a normal seller or 2 to a seller whitelabel
					'buyBox'                      => $store['buybox'] == 1 ? true : false// validar se precisa desse parametro
				];

				// atualiza dados da loja na tabela sellercenter_last_post
				$toSaveUltEnvio = [
					'zipcode'                   => preg_replace('/\D/', '', $store['zipcode']),
					'freight_seller'            => $store['freight_seller'],
					'freight_seller_end_point'  => $store['freight_seller_end_point'],
					'freight_seller_type'       => $store['freight_seller_type'],
					'CNPJ'                      => $store['CNPJ']
				];
				$this->model_sellercenter_last_post->updateDatasStore($store['id'], $toSaveUltEnvio);
				
				$bodyParams    = json_encode($data);
				$endPoint      = 'resellers?resellerId='.$auth_data->seller_id;
				$this->processNew($main_auth_data, $endPoint, 'PUT', $bodyParams);
				var_dump($this->result);
				if ($this->responseCode != 200) {
					$erro = "Erro Endpoint ".$endPoint." httpcode=".$this->responseCode." RESPOSTA ".print_r($this->result ,true)." ENVIADO ".print_r($bodyParams ,true);
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"E");
					continue;
				}
				$integration['auth_data'] = json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $sellerId, 'token' => $tokenSeller));
				$integration['active'] = $store['active'] == 1 ? 1 : 2;
				$this->model_integrations->update($integration, $integration['id']); 
			
			}
		}
    }

}