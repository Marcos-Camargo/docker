<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class ReservaRefreshProducts extends Main
{

    public function __construct()
    {
        parent::__construct();
        	
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
    	$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		$this->load->model('model_products');
        $this->load->model('model_integrations');
		$this->load->model('model_catalogs');

	}

    // php index.php BatchC/SellerCenter/Reserva/ReservaRefreshProducts run null Farm
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
		
		if (!is_null($params)) {
			$this->int_to = $params;
			
			$catalog = $this->model_catalogs->getCatalogByName($this->int_to);
			if (!$catalog) {
				echo "Este catálogo ".$this->int_to." não existe\n";
				die;
			}
			$this->resync();
		}
		else {
			echo "Informe o int_to do marketplace para enviar produtos\n";
		}
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	public function resync() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		
		echo "Buscando produtos da ".$this->int_to."\n";
		
		$products = $this->model_products->getActiveProductData();
		foreach ($products as $prd) {
			if ($prd['qty'] <=0) {
				continue;
			}
			$prd_to_int = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to, $prd['id']);
			
			if (!$prd_to_int) {
				continue;
			}
			
			if (strtotime($prd['date_update']) <= strtotime($prd_to_int['date_last_int'])) {
				echo " Atualizando produto ".$prd['id']."\n";
				$date_update = strtotime($prd['date_update']) -1; 
				// echo 'data antiga '.$prd['date_update'].' data nova '.date('Y-m-d H:i:s', $date_update)."\n"; 
				$this->model_integrations->updatePrdToIntegration( array('date_last_int' => date('Y-m-d H:i:s', $date_update)) ,$prd_to_int['id'] );
			}
			
		}
		
    }

}
