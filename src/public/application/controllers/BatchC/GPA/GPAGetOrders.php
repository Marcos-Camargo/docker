<?php
/*
 * 
Envia os produtos par ao GPA
 * 
*/   
require APPPATH . "controllers/BatchC/Marketplace/Mirakl/GetOrders.php";

class GPAGetOrders extends GetOrders {

	public function __construct()
	{
		parent::__construct();

		$logged_in_sess = array(
			'id' => 1,
	        'username'  => 'batch',
	        'email'     => 'batch@conectala.com.br',
	        'usercomp' 	=> 1,
	        'userstore' => 0,
	        'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
		$this->load->model( 'model_gpa_last_post');
    }
		
	// php index.php BatchC/GPA/GPAGetOrders run null
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
		$this->int_to='GPA';
		$this->model_last_post = $this->lastPostModel();
		$this->getkeys(1,0);
		$this->getorders();
		$this->load->model( 'model_gpa_last_post');
 		
		
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function lastPostModel() {
		return $this->model_gpa_last_post; 
	}

}
?>
