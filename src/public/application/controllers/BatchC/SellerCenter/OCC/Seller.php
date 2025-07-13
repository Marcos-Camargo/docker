<?php

require APPPATH . "controllers/BatchC/SellerCenter/OCC/Main.php";

class Seller extends Main
{

	var $int_from = 'HUB';
	var $int_to = null;
	 
    public function __construct()
    {
        parent::__construct();
		
		$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp'  => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
        $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_atributos_categorias_marketplaces');
		

    }
 
	// php index.php BatchC/SellerCenter/OCC/Seller run null Zema
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
		if (!is_null($params) && (strtolower($params) != 'null')) {
			$this->int_to = $params;
			$retorno = $this->createSeller();
        }
		else {
			echo " Informe um marketplace \n";
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
    public function createSeller()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		
		$arrSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
		$varSellerCenter = $arrSellerCenter['value'];

		$main_integration = $this->model_integrations->getIntegrationsbyCompIntType(1, $this->int_to,"CONECTALA","DIRECT",0);
		if (!$main_integration) {
			echo " Integração principal ainda não criada para ".$this->int_to."\n";
			return false;
		}
		echo 'Verificando novas lojas para criar em '.$main_integration['int_to']."\n";
		
		$main_auth_data = json_decode($main_integration['auth_data']);
		$stores = $this->model_stores->getAllActiveStore(); 

		$data_ints = array();  // array que guardará os dados da integração para nnovas lojas
		foreach ($stores as $store) {
			if ($store['type_store'] == 2) {
				echo "Pulando a loja ".$store['id']." pois a mesma é a loja CD de uma empresa Multi-CD\n";
				continue;
			}
			$integration =  $this->model_integrations->getIntegrationbyStoreIdAndInto($store['id'], $main_integration['int_to']);
			if (!$integration) { // não foi criada a integração, então tem que criar e alterar o OCC com o novo sellerid
				$data_ints[ $store['id']] = array(
					'name' 			=> $main_integration['name'],
					'active' 		=> $main_integration['active'],
					'store_id' 		=> $store['id'],
					'company_id' 	=> $store['company_id'],
					'auth_data' 	=> json_encode(array('date_integrate'=>$store['date_update'],'seller_id'=> $store['id'])),
					'int_type' 		=> 'BLING',
					'int_from' 		=> is_null($this->int_from) ? $main_integration['int_from'] : $this->int_from,
					'int_to' 		=> $main_integration['int_to'], 
					'auto_approve' 	=> $main_integration['auto_approve'] 
				); 
			}
		}
		
		if (!empty($data_ints)) {  // se tem alguma loja nova, registra na occ e cria na integrations 
			if (!$this->updateSellerIdOCC($this->int_to, false)) {
				echo "abandonando\n";
				return false; 
			}
			// se conseguiu fazer update, aí cria a integração.
			foreach ($data_ints as $data) {
				echo 'Criando integração no marketplace '.$main_integration['int_to'].' para a loja '.$data['store_id'].' empresa '.$data['company_id']."\n"; 
				$this->model_integrations->create($data); 
			}
		}
		else {
			echo "Nenhuma loja nova para criar\n";
		}
		return true; 
    }
		
}
