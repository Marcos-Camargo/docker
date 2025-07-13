<?php
/*
 * 
*/  

 class ProcessProductsQueueMKT extends BatchBackground_Controller {
		
	var $interval;
	var $integrations = array();
	const MAXESPERA = 30;
	const INCREMENTOESPERA = 2;
	const NOVO = 0;
	const PROCESSANDO = 1;
	const AGUARDANDO = 2;
	const MAXJOBS = 400;
	const SLEEPWAIT = 10;
	var $last_log;
	var $dir_log;
	var $process_url;
	var $int_to;
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' 		=> 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp'  => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
	 	$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		$this->load->model('model_integrations');
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_queues_control');
		$this->load->model('model_products');
		$this->load->model('model_settings');
    }
	
	// php index.php Queues/ProcessProductsQueueMKT  run log CO
	function run($lastlog=null, $params=null)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		
		/* faz o que o job precisa fazer */
		$this->process_url = $this->model_settings->getValueIfAtiveByName('internal_api_url');
		If (!$this->process_url) {
			/* faz o que o job precisa fazer */
			$this->process_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
			If (!$this->process_url) {
				$this->process_url = base_url() ;
			}
		}
		if (substr($this->process_url, -1) !== '/') {
			$this->process_url .= '/';
        }
		
	    $this->last_log = $lastlog;
		if (is_null($params) || $params == 'null') {
			echo "Chame com o marketplace\n";
			return; 
		}
		$this->int_to=$params;
		$retorno = $this->eternalLoop();
	}
	
	function getIntegrations($store_id=0) 
	{
		$this->integrations = $this->model_integrations->getIntegrationsbyStoreId($store_id);
	}
	
	function log($msg)
	{
		echo date("d/m/Y H:i:s")."-".$msg."\n";
	}
		
	function eternalLoop() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->dir_log = $this->config->item('log_path');
		if ($this->dir_log =='') {
			$this->dir_log = FCPATH.'application/logs/';
		}
		$this->dir_log .= 'queue';
        if (!file_exists($this->dir_log)) @mkdir($this->dir_log);

		// crio os diretorios de log se não existirem
		if (!file_exists($this->dir_log.'/'.$this->int_to)) { @mkdir($this->dir_log.'/'.$this->int_to); }
		
		$cnt = 0;
		$today= date('d-m-Y'); 
		$tomorrow = strtotime(date('Y-m-d H:i:s'). ' + 1 days');
		$stay_alive =date('H:i');
		$this->interval = self::INCREMENTOESPERA;
		$limit = 50; //quantidade de registro que puxo do banco.
		echo "update em model_queues_control\n";
		$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s'), 'last_log' =>$this->last_log), $this->router->fetch_class());
		
		$prd_id_jump = null; 
		while(true) {
			
			//$this->log("loop");
			if ((strtotime(date('Y-m-d H:i:s'))) > $tomorrow) {
				$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s', strtotime('-15 minutes')), 'last_log' =>$this->last_log), $this->router->fetch_class());
				$this->log("Mudei de dia. Encerrando\n");
				return; 
			}

			if (date('H:i') > $stay_alive) { // gravo que estou vivo de minuto em minuto
				echo "--------------- Update em model_queues_control pois passou um minuto\n";
				$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s'), 'last_log' =>$this->last_log), $this->router->fetch_class());
				$stay_alive = date('H:i', strtotime('+1 minutes'));
				// echo " ------------- Pegando produtos vencidos -------------------------------------------\n";
				
				$problems = $this->model_queue_products_marketplace->updateAllDelayedIntTo($this->int_to); // todos que já estão a muito tempo voltam a ficar com status=0

				$cnt = 0;
				$prd_id_jump = null;
			}
			
			if ($cnt>50) {
				//echo "Verificando tamanho da fila em processamento\n";
				$countRecords = $this->model_queue_products_marketplace->countQueueRunning(); // vejo se já tem muitos sendo processados e dou uma pausa para que sejam processados
				echo " Em processamento: ".$countRecords['qtd']."\n";
				if ($countRecords['qtd'] > self::MAXJOBS) {
					echo "Dormindo ".self::SLEEPWAIT." segs pois já tem ".$countRecords['qtd']." sendo processados \n";
					sleep(self::SLEEPWAIT);
					$problems = $this->model_queue_products_marketplace->updateAllDelayedIntTo($this->int_to); // todos que já estão a muito tempo voltam a ficar com status=0
				}
			}

			echo "Pegando proximo produto {$prd_id_jump} \n"; 
			$queues = $this->model_queue_products_marketplace->getDataNextNew(100, $prd_id_jump, $this->int_to);
			foreach($queues as $queue) {
				$cnt++; 
				echo date('d/m/y H:i:s').": Processando a fila {$queue['id']} {$queue['prd_id']} {$queue['int_to']}\n";
				$prd_id_jump = ($this->processQueue($queue)) ? null : $queue['id'];					
			}
			
			if (count($queues) == 0 ) {
				if ($cnt==0) { // enquanto não tem na fila, vou subindo o tempo até 2 minutos em intervalos de 5 segundos
					$this->interval = ($this->interval >= self::MAXESPERA) ? self::MAXESPERA : $this->interval+self::INCREMENTOESPERA;
				}else { // apareceu alguém na fila, volta para intervalo de 5 segundos novamente
					$this->interval = self::INCREMENTOESPERA;
					$cnt = 0;
				}
				$this->log("Dormindo ".$this->interval);
				sleep($this->interval); 
				$prd_id_jump = null;
			}			
		}
	}
	
	function processQueue($queue) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
			
		// vejo se tem alguém ja processando o mesmo produto 
		$oldprocess = $this->model_queue_products_marketplace->existProcessing($queue['prd_id'], $queue['int_to'], self::PROCESSANDO); 
		if ($oldprocess) {
			echo "Alguém processando em {$oldprocess['id']}\n";
			//var_dump($oldprocess);
			//$this->log("Já tem um processo ".$oldprocess['id']." rodando para o produto ".$oldprocess['prd_id']." para ".$oldprocess['int_to']); 
			return false;
		}
		
		//$this->log("Procesando ".$queue['id']." produto ".$queue['prd_id']." int_to ".$queue['int_to']); 
		//  atualizo para 1
		$this->model_queue_products_marketplace->update(array('status' => self::PROCESSANDO),$queue['id']);
	
		// agora, finalmente chamar o programa específico.
		$data = array (  
		    'queue_id' 	 => $queue['id'],
		 	'product_id' => $queue['prd_id'], 
		);
		
		$url = $this->process_url.'Api/queue/Products_'.$queue['int_to'];
		// $url = base_url('Api/queue/Products_'.$queue['int_to']); 
		//$url = 'http://localhost/app/Api/queue/Products_'.$queue['int_to']; 
		//$this->log($url);  
		
		$dir = $this->dir_log.'/'.$queue['int_to'];
		
		$logfile = $dir.'/products_queue_'.$queue['int_to'].'_'.$queue['prd_id'].'.log';
		shell_exec("sudo /bin/rm -f '".$logfile."'");
		//echo " $logfile \n";
		
		$this->postURL($url, $data, $logfile);

		// apago os demais registros duplicados (variações) 
		$this->model_queue_products_marketplace->deleteOthers($queue['id'],$queue['int_to'], $queue['prd_id']);
		return true;
		
	}
	
	protected function postURL( $url, $data, $logfile)
    {
    		
    	$cmd = "nohup curl -X POST '" . $url . "'";
    	$cmd.= " -H 'Content-Type: application/json' -H 'x-local-appkey: 32322rwerwefwr2343qefasfsfa312e4rfwedsdf'";
	    $cmd.= " -d '" . json_encode($data) . "' ";
				
		$cmd .= " > ".$logfile." 2>&1 &"; //just dismiss the response
		
		echo "cmd = ".$cmd."\n";
		// exec($cmd, $output, $exit);
		exec($cmd);
 
    }
	
}
?>
