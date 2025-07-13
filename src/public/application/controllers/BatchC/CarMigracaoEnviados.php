<?php
/*
 
Migra as ofertas para o modelo novo alterando a prd_to_integration e bling_ult_envio para ter variação. 

*/   
class CarMigracaoEnviados extends BatchBackground_Controller {
	
	var $int_to='CAR';
	var $apikey='';
	var $site='';
	
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
		$this->load->model('model_promotions');
		$this->load->model('model_products');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_errors_transformation'); 	
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_car_ult_envio');
		
    }
	
	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setApikey($apikey) {
		$this->apikey = $apikey;
	}
	function getApikey() {
		return $this->apikey;
	}
	function setSite($site) {
		$this->site = $site;
	}
	function getSite() {
		return $this->site;
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
		// usado uma vez para limpar as ofertas perdidas do BLING 
		// faça um backup do bling_ult_envio antes pois irá remover os produtos errados dela. 
		// poderá ser usado novamente para sincronizar com as ofertas validas do carrefour 
		// para isso deve ser baixado o arquivo de ofertas atuais e colocado no diretorio de importação 
		// com o nome offers.csv e descomentar a linha abaixo 
		$this->migra();
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function migra()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		//$prds_to = $this->model_integrations->getProductsToCheckMarketplace($this->int_to,0, 3000);
		 $sql = "SELECT * FROM prd_to_integration WHERE status=0 AND status_int = 22 AND int_to=? ORDER BY store_id";

        $query = $this->db->query($sql, array($this->int_to));
        $prds_to =  $query->result_array();
		
		
		foreach($prds_to as $prd_to) {
			$prd = $this->model_products->getProductData(0,$prd_to['prd_id']);
			
			if ($prd['has_variants'] == '') {
				continue; 
			}
			echo "Acertando ". $prd['id']. "\n";
			$variants = $this->model_products->getVariants($prd['id']);
			foreach($variants as $variant) {
				$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($prd['id'],$this->int_to, $variant['variant']);
				if (!$prd_int) {// jeito velho, então acerto a prd_to_integration 
					$prd_upd = array (
						'int_id'		=> $prd_to['int_id'],
						'prd_id'		=> $prd['id'],
						'company_id'	=> $prd_to['company_id'],
						'date_last_int' => $prd_to['date_last_int'],
						'status'	 	=> $prd_to['status'],
						'status_int' 	=> $prd_to['status_int'],
						'user_id' 		=> $prd_to['user_id'],
						'int_type' 		=> $prd_to['int_type'],
						'int_to' 		=> $this->int_to,
						'skumkt' 		=> $prd_to['skumkt'],
						'skubling' 		=> $prd_to['skubling'].'-'.$variant['variant'],
						'store_id'		=> $prd_to['store_id'],
						'quality' 		=> $prd_to['quality'],
						'ad_link' 		=> $prd_to['ad_link'],
						'approved' 		=> $prd_to['approved'],
						'variant' 		=> $variant['variant'],
						'rule' 			=> $prd_to['rule'],
					);
					
					$this->model_integrations->createPrdToIntegration($prd_upd);
				}
			}
			$todelete =  $this->model_integrations->getIntegrationsProductWithVariant($prd['id'],$this->int_to,null);
			if ($todelete) {
				$this->model_integrations->removePrdToIntegration($todelete['id']); 
			}
		}
		
	}

}
?>
