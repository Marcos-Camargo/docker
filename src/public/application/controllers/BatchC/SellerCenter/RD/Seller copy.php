<?php

require APPPATH . "controllers/BatchC/SellerCenter/RD/Main.php";

defined('BASEPATH') OR exit('No direct script access allowed');

class Seller extends Main
{
	
	var $int_from = null;
	 
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
		$this->load->model('model_sc_last_post');
		$this->model_sc_last_post->setIntTo('rd');

    }
	
	// php index.php BatchC/SellerCenter/RD/Seller run null RaiaDrogasil
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
				$this->int_from = 'HUB';
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

			// o parametro está ativo e tem lojas que somente essas devem ser publicadas
			if ($store['id'] != $onlyStorePublished && $store['company_id'] == $onlyCompanyPublished) {
				echo "loja {$store['id']} - {$store['nane']} da empresa {$store['company_id']} não está configurada para ser publicar\n";
				continue;
			}
			if (is_null($store['erp_customer_supplier_code']) || (trim($store['erp_customer_supplier_code']==''))) {
				echo "loja {$store['id']} - {$store['nane']} não está configurada com o Clifor\n";
				continue;
			}
			
			$integration =  $this->model_integrations->getIntegrationbyStoreIdAndInto($store['id'], $main_integration['int_to']);
			
			if (!$integration) {
				echo 'Criando integração no marketplace '.$main_integration['int_to'].' para a loja '.$store['id'].' '.$store['name']."\n"; 
				$sellerId =  $store['erp_customer_supplier_code'];
				
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
			
			$separateIntegrationData = json_decode($integration['auth_data']);
			
			if ($store['date_update'] > $separateIntegrationData->date_integrate ) {
				echo 'Alterando no marketplace '.$main_integration['int_to'].' a loja '.$store['id'].' '.$store['name']."\n"; 

				$sellerId = $store['erp_customer_supplier_code'];
				
				// atualiza dados da loja na tabela vtex_ult_envio
				$toSaveUltEnvio = [
					'zipcode'                   => preg_replace('/\D/', '', $store['zipcode']),
					'freight_seller'            => $store['freight_seller'],
					'freight_seller_end_point'  => $store['freight_seller_end_point'],
					'freight_seller_type'       => $store['freight_seller_type'],
					'CNPJ'                      => $store['CNPJ']
				];
				$this->model_sc_last_post->updateDatasStore($store['id'], $toSaveUltEnvio);
				
				$integration['auth_data'] = json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $sellerId));
				$integration['active'] = $store['active'] == 1 ? 1 : 2;
				$this->model_integrations->update($integration, $integration['id']); 
			
			}
		}
    }
}
