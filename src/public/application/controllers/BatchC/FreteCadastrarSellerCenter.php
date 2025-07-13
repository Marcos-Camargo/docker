<?php
/*
Verifica quais lojas precisam ser criadas no frete rapido como expedidor e as cria 
*/

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Router $router
 * 
 * @property Model_stores $model_stores
 * @property Model_integrations $model_integrations
 * @property Model_settings $model_settings
 * @property Model_products $model_products
 * @property Model_integration_logistic $model_integration_logistic
 *
 * @property CalculoFrete $calculofrete
 */

class FreteCadastrarSellerCenter extends BatchBackground_Controller {
		
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
		
		// carrega os modulos necessários para o Job
	    $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_products');
		$this->load->model('model_integration_logistic');
		$this->load->library('calculoFrete');
    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->setWarehouseLogistic($params);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	public function setWarehouseLogistic()
	{
		$integrationsSellerCenter = $this->model_integration_logistic->getAllIntegrationSellerCenter();
		foreach ($integrationsSellerCenter as $integrationSellerCenter) {
			$integrationsStore = $this->model_integration_logistic->getStoresIntegrationsSellerCenterByInt($integrationSellerCenter['id']);
			foreach ($integrationsStore as $integrationStore) {
				$store 		 = $integrationStore['store_id'];
				$integration = $integrationStore['integration'];

				try {
					$this->calculofrete->instanceLogistic($integration, $store, array(), false);
					$this->calculofrete->logistic->setWarehouse();
					$message = "Centro de distribuição criado/atualizado.[STORE=$store] [INTEGRATION=$integration]";
					echo $message . "\n";
					$this->log_data('batch',__CLASS__.'/'.__FUNCTION__, $message);
				} catch (InvalidArgumentException $exception) {
					$message =  "Não foi possível criar/atualizar o centro de distribuição.[STORE=$store] [INTEGRATION=$integration] {$exception->getMessage()}";
					echo $message . "\n";
					$this->log_data('batch',__CLASS__.'/'.__FUNCTION__, $message,"E");
				}
			}
		}
	}
}
?>
