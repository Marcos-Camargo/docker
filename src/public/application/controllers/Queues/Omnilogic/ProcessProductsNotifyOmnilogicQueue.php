<?php
/*
 * 
*/  

require APPPATH . "libraries/Omnilogic.php";

class ProcessProductsNotifyOmnilogicQueue extends BatchBackground_Controller {
		
	var $interval;
	const MAXESPERA = 120;
	const INCREMENTOESPERA = 5;
	const NOVO = 0;
	const PROCESSANDO = 1;
	const AGUARDANDO = 2;
	var $last_log;
	private $url = '';
	private $prefix;
	
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
	 	$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		$this->load->model('model_queue_products_notify_omnilogic');
		$this->load->model('model_queues_control');
		$this->load->model('model_omnilogic');
		$this->load->model('model_settings');
		$this->load->library('rest_request');

		$this->url = Omnilogic::url_notify();

		$this->prefix = $this->model_settings->getValueIfAtiveByName('omnilogic_prefix');
    }
	
	protected function getPrefix() {
		return $this->prefix;
	}

	protected function getFetchClass(){ return $this->router->fetch_class(); }
	protected function getPath() { return $this->router->directory; }

	private function getProcessName() {
		return str_replace('Queues/', '', $this->getPath()) . $this->getFetchClass();
	}

	// php index.php Queues/ProcessProductsQueue run 
	function run($lastlog=null)
	{
		$log_name =$this->getFetchClass().'/'.__FUNCTION__;
		
		/* faz o que o job precisa fazer */
		$this->last_log = $lastlog;
		
		if (!$this->prefix) {
			echo 'Prefixo não encontrado. \n';
			return ;
		}

		$retorno = $this->eternalLoop();
	}
	
	function log($msg)
	{
		echo date("d/m/Y H:i:s")."-".$msg."\n";
	}
		
	function eternalLoop() {
		$log_name =$this->getFetchClass().'/'.__FUNCTION__;
		
		$cnt = 0;
		$this->interval = self::INCREMENTOESPERA;
		while(true) {
			// gravar q to vivo aqui .....
			$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s'), 'last_log' =>$this->last_log), $this->getProcessName());
			
			$this->log("loop");
			$queues = $this->model_queue_products_notify_omnilogic->getDataNext();
			foreach($queues as $queue) 
			{
				$cnt++;
				$this->processQueue($queue);
			}
			
			// Acabou a fila normal vejo se tem registros antigos que tem que ser reprocessados
			$problems = $this->model_queue_products_notify_omnilogic->getDataAllDelayed();
			foreach( $problems as $problem) {
				$this->log("problema ".$problem['id']);
				$cnt++;
				$aguardando = $this->model_queue_products_notify_omnilogic->existProcessing($problem['prd_id'], $problem['int_to'], self::NOVO);
				if ($aguardando) { // ja criaram um novo então posso apagar este  
					$this->model_queue_products_notify_omnilogic->remove($problem['id']); // apago o com problema
				}
				else { // devolvo para a fila 
					$this->model_queue_products_notify_omnilogic->update(array('status' => self::NOVO),$problem['id']);
				}
			}
			
			if ($cnt==0) { // enquanto não tem na fila, vou subindo o tempo até 2 minutos em intervalos de 5 segundos
				$this->interval = ($this->interval >= self::MAXESPERA) ? self::MAXESPERA : $this->interval+self::INCREMENTOESPERA;
			}else { // apareceu alguém na fila, volta para intervalo de 5 segundos novamente
				$this->interval = self::INCREMENTOESPERA;
			}
			// gravar q to vivo aqui .....
			$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s'), 'last_log' =>$this->last_log), $this->getProcessName());
			$cnt = 0;
			$this->log("dormindo ".$this->interval);
			sleep($this->interval); 			
		}
	}
	
	function processQueue($queue) 
	{
		$log_name =$this->getFetchClass().'/'.__FUNCTION__;
		
		// gravar q to vivo aqui .....
		$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s'), 'last_log' =>$this->last_log), $this->getProcessName());
				
		// vejo se tem alguém ja processando o mesmo produto 
		$oldprocess = $this->model_queue_products_notify_omnilogic->existProcessing($queue['prd_id'], $queue['int_to'], self::PROCESSANDO); 
		if ($oldprocess) {
			//var_dump($oldprocess);
			$this->log("Já tem um processo ".$oldprocess['id']." rodando para o produto ".$oldprocess['prd_id']." para ".$oldprocess['int_to']); 
			return ;
		}
		
		$this->log("Procesando ".$queue['id']." produto ".$queue['prd_id']." int_to ".$queue['int_to']); 
		$this->model_queue_products_notify_omnilogic->update(array('status' => self::PROCESSANDO),$queue['id']);
		
		$this->model_queue_products_notify_omnilogic->remove($queue['id']);
		$this->afterProcessQueue($queue);
		return ;

		// agora, finalmente chamar o programa específico.
		$data = array (  
			'store' => Omnilogic::store(),
            'id' => $this->getProductId($queue['prd_id'])
		);
		
		$dir = FCPATH.'application/logs/queue';
        if (!file_exists($dir)) @mkdir($dir);
        $logfile = $dir.'/products_omnilogic_queue_'.$queue['int_to'].'_'.$queue['prd_id'].'_'.date('YmdHis').'.log';
	
		$logfile = $dir.'/products_omnilogic_queue_'.$queue['int_to'].'_'.$queue['prd_id'].'.log';
		echo " $logfile \n";
		$response = $this->postRequest($this->url, $data);

		if ($response['httpcode'] < 300) {
			$this->model_queue_products_notify_omnilogic->remove($queue['id']);
			$this->model_omnilogic->sent($queue['prd_id']);
		}
		$this->model_queue_products_notify_omnilogic->remove($queue['id']);
		$this->afterProcessQueue($queue);
	}

	protected function afterProcessQueue($queue)
	{
		return;
	}
	
	private function getProductId($prd_id) {
		return $this->getPrefix() . "_" . $prd_id;
	}

	private function postRequest($url, $payload){
        $post_data = json_encode($payload);

		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER  => array(
                    'content-type: application/json', 
                    'Authorization: '. Omnilogic::token()
				)
	    );
        
        $ch         = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );
        
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
        
        return $header;
	}
	
}
?>
