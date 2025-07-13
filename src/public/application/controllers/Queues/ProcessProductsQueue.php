<?php
/**
 * @property Model_integrations $model_integrations
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_queues_control $model_queues_control
 * @property Model_products $model_products
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_stores_multi_channel_fulfillment $model_stores_multi_channel_fulfillment
 */

class ProcessProductsQueue extends BatchBackground_Controller {
		
	var $interval;
	var $integrations = array();
	private $max_espera = 30;
	const INCREMENTOESPERA = 2;
	const NOVO = 0;
	const PROCESSANDO = 1;
	const AGUARDANDO = 2;
	private $max_jobs = 400;
	const SLEEPWAIT = 10;
	var $last_log;
	var $dir_log;
	var $process_url;

	private $types_integration = array();
	
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
		$this->load->model('model_stores');
		$this->load->model('model_stores_multi_channel_fulfillment');

		if ($this->model_settings->getValueIfAtiveByName('sellercenter') == 'privalia') {
			$this->max_espera = 60;
			$this->max_jobs = 50;
		}
    }
	
	// php index.php Queues/ProcessProductsQueue run 
	function run($lastlog=null)
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

		$stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;
		
		$this->dir_log = $this->config->item('log_path');
		if ($this->dir_log =='') {
			$this->dir_log = FCPATH.'application/logs/';
		}
		$this->dir_log .= 'queue';
        if (!file_exists($this->dir_log)) @mkdir($this->dir_log);
		$this->getIntegrations(0);
		// crio os diretorios de log se não existirem
		foreach($this->integrations as $integration) {
			 if (!file_exists($this->dir_log.'/'.$integration['int_to'])) @mkdir($this->dir_log.'/'.$integration['int_to']);
		} 
		
		$cnt = 0;
		$today= date('d-m-Y'); 
		$tomorrow = strtotime(date('Y-m-d H:i:s'). ' + 1 days');
		$stay_alive =date('H:i');
		$this->interval = self::INCREMENTOESPERA;
		$limit = 50; //quantidade de registro que puxo do banco.
		echo "update em model_queues_control\n";
		$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s'), 'last_log' =>$this->last_log, 'server_batch_ip' => exec('hostname -I')), $this->router->fetch_class());
		
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
				
				$problems = $this->model_queue_products_marketplace->updateAllDelayed(); // todos que já estão a muito tempo voltam a ficar com status=0
				
				/* rotina antiga 
				$problems = $this->model_queue_products_marketplace->getDataAllDelayed();
				foreach( $problems as $problem) {
					//$this->log("problema ".$problem['id']);
					$cnt++;
					$aguardando = $this->model_queue_products_marketplace->existProcessing($problem['prd_id'], $problem['int_to'], self::NOVO);
					if ($aguardando) { // ja criaram um novo então posso apagar este  
						$this->model_queue_products_marketplace->remove($problem['id']); // apago o com problema
					}
					else { // devolvo para a fila 
						$this->model_queue_products_marketplace->update(array('status' => self::NOVO),$problem['id']);
					}
				}
				*/
				$cnt = 0;
				$prd_id_jump = null;
			}
			
			if ($cnt > 50) {
                $cnt = 0;
				//echo "Verificando tamanho da fila em processamento\n";
				$countRecords = $this->model_queue_products_marketplace->countQueueRunning(); // vejo se já tem muitos sendo processados e dou uma pausa para que sejam processados
				echo " Em processamento: ".$countRecords['qtd']."\n";
				if ($countRecords['qtd'] > $this->max_jobs) {
					echo "Dormindo ".self::SLEEPWAIT." segs pois já tem ".$countRecords['qtd']." sendo processados \n";
					sleep(self::SLEEPWAIT);
					$problems = $this->model_queue_products_marketplace->updateAllDelayed(); // todos que já estão a muito tempo voltam a ficar com status=0
				}
			}

			echo "Pegando proximo produto {$prd_id_jump} \n";
			$queues = $this->model_queue_products_marketplace->getDataNextNew(1, $prd_id_jump);
			foreach($queues as $queue) 
			{
				
				$cnt++;
				if (is_null($queue['int_to'])) { // se não passou a fila, tenho q criar registros para cada integração e remover este registro
					//leio o produto para pegar a loja
					// echo "Lendo o produto\n";
					$prd=$this->model_products->getProductData(0,$queue['prd_id']);
					// leio quais integrações a loja tem
					// echo "Lendo integracores da loja\n";
					$this->getIntegrations($prd['store_id']);

					if ((!$this->integrations) && ($stores_multi_cd))  {
						$this->checkMultifullfillment($prd, $this->model_stores->getStoresData($prd['store_id']));
					}

					$prd_id_jump = null;
					foreach($this->integrations as $integration) {
						$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($integration['int_to'], $queue['prd_id']);
						if  (!$prd_to_integration) {							
							echo 'Produto '. $queue['prd_id'].' sem integração com '.$integration['int_to']."\n";
							continue;
						}
						$data = array (
							'status' => 0,
							'prd_id' => $queue['prd_id'],
							'int_to' => $integration['int_to'],
							'date_create' => $queue['date_create'],
						);
						// echo "Recriando a fila\n";
						$data['id'] = $this->model_queue_products_marketplace->create($data);
						 
						//$data_new = $this->model_queue_products_marketplace->getData($data['id']);
						echo ": Processando a fila CRIADA {$data['id']} {$data['prd_id']} {$data['int_to']}\n";
						$prd_id_jump = ($this->processQueue($data)) ? null : $data['id'];
					}
					//echo "Removendo da fila int_to Null do {$queue['prd_id']}\n";
					//$this->model_queue_products_marketplace->remove($queue['id']);  // retiro da fila 

					// echo "Removendo da fila duplicados\n";
					$this->model_queue_products_marketplace->removePrdIdNull($queue['prd_id']); // removo os duplicados 
				}
				else {
					echo ": Processando a fila {$queue['id']} {$queue['prd_id']} {$queue['int_to']}\n";
					$prd_id_jump = ($this->processQueue($queue)) ? null : $queue['id'];
					
				}
			}
			if (($cnt==0) || ($cnt > $limit)) {
				// Acabou a fila normal vejo se tem registros antigos que tem que ser reprocessados
				/*
				echo " ------------- Pegando produtos vencidos -------------------------------------------\n";
				$problems = $this->model_queue_products_marketplace->getDataAllDelayed();
				foreach( $problems as $problem) {
					//$this->log("problema ".$problem['id']);
					$cnt++;
					$aguardando = $this->model_queue_products_marketplace->existProcessing($problem['prd_id'], $problem['int_to'], self::NOVO);
					if ($aguardando) { // ja criaram um novo então posso apagar este  
						$this->model_queue_products_marketplace->remove($problem['id']); // apago o com problema
					}
					else { // devolvo para a fila 
						$this->model_queue_products_marketplace->update(array('status' => self::NOVO),$problem['id']);
					}
				}
				$cnt = 0;
				$prd_id_jump = null;
				 * 
				 */
			}
			
			if (count($queues) == 0 ) {
				if ($cnt==0) { // enquanto não tem na fila, vou subindo o tempo até 2 minutos em intervalos de 5 segundos
					$this->interval = ($this->interval >= $this->max_espera) ? $this->max_espera : $this->interval+self::INCREMENTOESPERA;
				}else { // apareceu alguém na fila, volta para intervalo de 5 segundos novamente
					$this->interval = self::INCREMENTOESPERA;
					$cnt = 0; 
				}
				$this->log("Dormindo ".$this->interval );
				sleep($this->interval); 
				$prd_id_jump = null;
				$stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;
			}
			
		}
	}
	
	function processQueue($queue) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
			
		// vejo se tem alguém ja processando o mesmo produto 
		$oldprocess = $this->model_queue_products_marketplace->existProcessing($queue['prd_id'], $queue['int_to'], self::PROCESSANDO); 
		if ($oldprocess) {
			if ($oldprocess['id'] != $queue['id']) {
				$this->model_queue_products_marketplace->remove($queue['id']);
			}
			echo "Alguém processando em {$oldprocess['id']}\n";
			//var_dump($oldprocess);
			//$this->log("Já tem um processo ".$oldprocess['id']." rodando para o produto ".$oldprocess['prd_id']." para ".$oldprocess['int_to']); 
			return true;
		}
		
		//$this->log("Procesando ".$queue['id']." produto ".$queue['prd_id']." int_to ".$queue['int_to']); 
		//  atualizo para 1
		$this->model_queue_products_marketplace->update(array('status' => self::PROCESSANDO),$queue['id']);
	
		// agora, finalmente chamar o programa específico.

		$data = array (
			'queue_id' 	 => $queue['id'],
			'product_id' => $queue['prd_id'],
			'int_to'	 => $queue['int_to']
		);

		if (file_exists(APPPATH.'controllers/Api/queue/Products_'.$queue['int_to'].'.php')) {
			$url = $this->process_url.'Api/queue/Products_'.$queue['int_to'];
		} else {
			if (!array_key_exists($queue['int_to'], $this->types_integration)) {
				$this->types_integration[$queue['int_to']] = $this->model_integrations->getIntegrationByIntTo($queue['int_to'], 0);
			}

			$intType = $this->types_integration[$queue['int_to']];
			if(!$intType){
				$this->model_queue_products_marketplace->deleteByIntTo($queue['int_to']);
				$this->model_queue_products_marketplace->remove($queue['id']);
				echo "integração inativada tirei da fila";
				return true;	
			}
			if($intType['mkt_type'] == "conectala") {
				$url = $this->process_url.'Api/queue/Products_Default_Conectala';
			} else if($intType['mkt_type'] == "wake") {
				$url = $this->process_url.'Api/queue/Products_Default_Wake';
			} else {
				$url = $this->process_url.'Api/queue/Products_Default';
			}
		}

		// $url = $this->process_url.'Api/queue/Products_'.$queue['int_to'];

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

	private function checkMultifullfillment($prd, $store)
	{		
	    if (($store['type_store'] !=2) || ($store['active'] !=1)) {
			return false; 
		}
		
		$stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;
		if (!$stores_multi_cd) {
			return false; 
		}

		echo "Produto de uma loja CD de Multi-CD \n";
		$multi_channel = $this->model_stores_multi_channel_fulfillment->getRangeZipcode($store['id'], $store['company_id'], 1);

		if (!$multi_channel){
			echo "Não consegui encontrar a loja Original para esse produto \n";
			return true; 
		}
		$original_store_id = $multi_channel[0]['store_id_principal']; 
		if ($prd['has_variants'] == '') {
			$prd_original = $this->model_products->getProductComplete($prd['sku'], $store['company_id'], $original_store_id);
			if ($prd_original) {
				echo "Colocando o produto ". $prd_original['id']. " da loja Principal na fila \n";
				$queue = array(
					'status' => 0,
					'prd_id' => $prd_original['id'],
					'int_to' => null
				);
				$this->model_queue_products_marketplace->create($queue);
				return true;
			}
			echo "ATENÇÂO: Não existe produto original com SKU ".$prd['sku']." na loja ".$original_store_id."\n";
		}
		else{
			$variants  = (isset ($this->variants)) ? $this->variants : $this->model_products->getVariants($prd['id']);
			
			foreach ($variants as $variant) {
				$variant_original = $this->model_products->getVariantsBySkuAndStore($variant['sku'], $original_store_id);
				if ($variant_original) {
					echo "Colocando o produto ". $variant_original['prd_id'] . " da loja Principal na fila \n";
					$queue = array(
						'status' => 0,
						'prd_id' => $variant_original['prd_id'],
						'int_to' => null
					);
					$this->model_queue_products_marketplace->create($queue);
					return false;
				}
			}
			echo "ATENÇÂO: Não existe produto original, nem variações para os SKUs ".$prd['sku']." na loja ".$original_store_id."\n";
		}
		return true;
	}
	
}
