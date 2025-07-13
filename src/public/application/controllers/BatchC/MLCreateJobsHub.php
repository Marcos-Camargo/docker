<?php
/*
 
Cria Jobs de HUB para cada uma loja do Mercado Livre

*/   

class MLCreateJobsHub extends BatchBackground_Controller {
	
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
		$this->load->model('model_integrations');
		$this->load->model('model_Calendar');
		$this->load->model('model_stores');
		
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
		$this->createJobs();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	
    function createJobs()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$jobs = array ( 
			array ('name' => 'MLGetOrdersHub', 'every' => 10), 
			array ('name' => 'MLOrdersStatusHub', 'every' => 10), 
		);
			
		$int_to='H_ML';	
		$integrations = $this->model_integrations->getIntegrationsByIntTo($int_to);
	
        $end = date('Y-m-d',time()) . " 23:59:59";
		$ini = date('Y-m-d H:i:s');	
		foreach ($integrations as $integration) {
			echo "Criando jobs para a loja ".$integration['store_id']. "\n"; 
			$store=$this->model_stores->getStoresData($integration['store_id']);
			foreach ($jobs as $job) {
				// apaga os jobs antigos  desta loja
				$this->model_calendar->deleteJobsByModuleAndParams($job['name'],$integration['store_id']);
				
				if ($store['active'] != 1) { // a loja não está mais ativa.  
					continue;
				}
				
				// cria os novos jobs 
				$count = $this->hojecount($ini,$end,$job['every'],$end);
                foreach($count as $inicio) {
                	echo " Job: ".$job['name']." ".$inicio."\n";
                    $this->model_calendar->add_job(array(
                        "module_path" => $job['name'],
                        "module_method" => 'run',
                        "params" => $integration['store_id'],
                        "status" => 0,
                        "finished" => 0,
                        "error" => NULL,
                        "error_count" => 0,
                        "error_msg" => NULL,
                        "date_start" => $inicio,
                        "date_end" => NULL,
                        "server_id" => 0
                    	)
                	);
                }
			}
		}

	}

	function hojecount($start,$end,$type,$e){
        $count = array();
        if ($end > $e) {
            $end = $e;
        }
        $minutes_to_add = $type;
        
        while ($start <= $end) {
            array_push($count,$start);
            $time = new DateTime($start);
            $time->add(new DateInterval('PT' . $minutes_to_add . 'M'));
            $start = $time->format('Y-m-d H:i');
        }
        return $count;
    }

}

?>
