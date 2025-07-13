<?php
/*
Verifica quais lojas precisam ser criadas as integraçoes  
*/  

 class CreateIntegrations extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' 		=> 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' 	=> 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
	    $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_products');

    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		if(is_null($params)  || ($params == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
			if($integration){
				echo 'Sync: '. $integration['int_to']."\n";
				$retorno = $this->criaIntegracoes($params,$integration );
			}
			else {
				echo PHP_EOL .$params." não tem integração definida". PHP_EOL;
			}
		}
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function criaIntegracoes($nt_to = NULL, $integration) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo "Verificando integrações de lojas\n";
		
		$lojas= $this->model_stores->getAllActiveStore(); 
		foreach ($lojas as $loja) {
			$inte = array (
				'name' 			=> $integration['name'],
				'active'		=> $integration['active'],
				'store_id' 		=> $loja['id'],
				'company_id' 	=> $loja['company_id'],
				'auth_data'		=> '',
				'int_type'		=> 'BLING',
				'int_from'		=> 'HUB',
				'int_to'		=> $integration['int_to'],
				'auto_approve' 	=> $integration['auto_approve']
			);
				
			$id = $this->model_integrations->getIntegrationsbyCompIntType($inte['company_id'], $inte['int_to'],$inte['int_from'],$inte['int_type'],$inte['store_id']);
			if (!($id)) {
				echo " Cadastrando ".$loja['id']." - ".$loja['name']." para ".$inte['int_to']."\n";
				$this->model_integrations->create($inte);
			}
		}
		$this->log_data('batch',$log_name,'finish',"I");
		 
	}
	
}
?>
