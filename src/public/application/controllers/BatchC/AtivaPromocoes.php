<?php
/*
 
Realiza o Leilão de Produtos e atualiza o ML 

*/   
require APPPATH . "controllers/Meli.php";

 class AtivaPromocoes extends BatchBackground_Controller {
	
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

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
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_campaigns_v2');

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
		//$retorno = $this->syncEstoquePreco();

		$this->promotions();
		$this->campaigns();
		$this->campaignsV2();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
		
	function promotions() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$this->model_promotions->activateAndDeactivate();
	}
	
	function campaigns() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$this->model_campaigns->activateAndDeactivate();
	}

	function campaignsV2() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");
		$this->model_campaigns_v2->updateProductsPriceInScheduledCampaigns();
		$this->model_campaigns_v2->deactivateProductsCampaignExpired();
		$this->model_campaigns_v2->addAllProductsSegmentByStoreInCampaignTypeMarketplaceTrading();
	}

}
?>
