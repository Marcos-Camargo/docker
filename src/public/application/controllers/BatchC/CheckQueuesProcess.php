<?php
/*
 
Verifica se os processos que consomem as filas estão no ar e, se não estiverem, as executa 

*/   
class CheckQueuesProcess extends BatchBackground_Controller {
	
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
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_queues_control');

    }
	
	// php index.php BatchC/CheckQueuesProcess run
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
		$this->checkQueues();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	
    function checkQueues()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$queues = $this->model_queues_control->getQueuesWithProblems();
		
		$dir_log = $this->config->item('log_path');
		if ($dir_log =='') {
			$dir_log = FCPATH.'application/logs/';
		}
		
		foreach ($queues as $queue) {
			$msg= 'Processando id '.$queue['process_name'].' que não se dá sinal de vida desde '. $queue['date_update'];
			echo $msg."\n";
			$this->log_data('batch',$msg,"E");

			$logfile= $dir_log.'/queue';
			if (!file_exists($logfile)) @mkdir($logfile);
			$logfile .= '/'.$queue['process_name'].'_'.date('Hi').'.log';
			$cmd = "php ".FCPATH."index.php Queues/".$queue['process_name']." run ".$logfile;
		    $cmd .= " > ".$logfile." 2>&1 &"; 
			echo $cmd . "\n";
			exec($cmd, $output, $exit);
		}
		
	}

}

?>
