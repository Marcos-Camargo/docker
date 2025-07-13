<?php
/*
	FUNÇÃO DEFAULT DE TESTES
	*** NÃO RETIRAR OU ALTERAR ***
*/			
	function isAlive($me, $param = NULL, $param2 = NULL, $param3 = NULL)
	{	
		// Para teste retornar "funcional"
		// Para teste retornar "com problema"	
		// $result = "MONIT_NOK " . $msgerr;	
		return "MONIT_OK " . $param;
	}
	
	function isDBAlive($me, $param = NULL, $param2 = NULL, $param3 = NULL)
	{
        $me->load->model("model_settings");
        $value = $me->model_settings->getSettingbyName($param);
		if ($value) {
			// Para teste retornar "funcional"
			$result = "MONIT_OK " . $value;	
		} else {	
			// Para teste retornar "com problema"	
			$result = "MONIT_NOK Erro na busca de " . $param;	
		}
		return $result;
	}
	
	
	function ordersReceived($me, $param = NULL, $param2 = NULL, $param3 = NULL) {
		$subject = 'Pedidos';
		$event_name = 'Recebimento de pedidos';

		// quantas horas que não chegou pedidos.
		$hours_by_int_to = array (
			'B2W' => 2,
			'CAR' => 8,
			'VIA' => 8,
			'ML'  => 48,
		);
		// verifico se vou monitorar o que passou de parâmetro
		if (!key_exists($param, $hours_by_int_to)) {
			return "MONIT_NOK marketplace " . $param . " não definido com horas para checagem.";	
		}
		
		
		// verifico se este sellercenter tem este marketplace
		$ok=FALSE;
		$me->load->model("model_integrations");
		$integrations = $me->model_integrations->getIntegrationsbyStoreId(0);
		foreach($integrations as $integration) {
			if ($integration['int_to'] == $param) {
				$ok = true;
				break;
			}
		}
		if (!$ok) {
			return "MONIT_NOK marketplace " . $param . " não configurado neste sellercenter.";	
		}
		
		// vejo se estou dentro do horário de monitoração
		if (!alertHour()) {
			return "MONIT_OK " . $param;
		}
		
		$me->load->model("model_orders");
		
		$orders = $me->model_orders->getLastOrdersInTime($param, $hours_by_int_to[$param]);
		if (count($orders) == 0) {
			$error = "Não está baixando pedidos do marketplace " . $param ;	
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;	
		}
		saveMonitor($me, $subject, $event_name,true);
		return "MONIT_OK " . $param;
	}

	function queueProblems($me, $param = NULL, $param2 = NULL, $param3 = NULL) {
		$subject = 'Publicação';
		$event_name = 'Fila de Produtos';

		if (!alertHour()) {
			saveMonitor($me, $subject, $event_name,true);
			return "MONIT_OK " . $param;
		}
		
		$me->load->model("model_queue_products_marketplace");
		$queue_products = $me->model_queue_products_marketplace->countQueue();
		if ($queue_products['qtd'] > 1000) {
			$error = "Fila de produtos para enviar para marketplaces acumulada: " . $queue_products['qtd'];
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;	
		}
		
		$queue_products = $me->model_queue_products_marketplace->countOldRecords(date('Y-m-d H:i:s',strtotime('-1 hour')));
		if ($queue_products['qtd'] > 1 ) {			
			$error = "Fila de produtos com produtos com mais de uma hora: " . $queue_products['qtd'] ;	
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;	
		}
		saveMonitor($me, $subject, $event_name,true);
		return "MONIT_OK " . $param;
		
	}

	function ordersMissing($me, $int_to = NULL, $hours = NULL, $hoursfterhours = NULL) {
		
		if (is_null($hours) || is_null($int_to) || is_null($hoursfterhours)  ) {
			return "MONIT_NOK Indique o int_to / horas / horasforadohorario para verificar";	
		}

		if (!alertHour(true)) { // se não está no horário normal, multiplico as horas por 3 
			$hours = $hoursfterhours;  
		}
		$subject = 'Pedidos';
		$event_name = 'Recebimento de Pedidos '.$int_to ;

		// verifico se este sellercenter tem este marketplace
		$ok=FALSE;
		$me->load->model("model_integrations");
		$integrations = $me->model_integrations->getIntegrationsbyStoreId(0);
		foreach($integrations as $integration) {
			if ($integration['int_to'] == $int_to) {
				$ok = true;
				break;
			}
		}
		if (!$ok) {
			$error = "Marketplace " . $int_to . " não configurado neste sellercenter.";				
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;	
		}
		
		$me->load->model("model_orders");
		
		$orders = $me->model_orders->getLastOrdersInTime($int_to, $hours);
		if (count($orders) == 0) {
			$error = "Não está baixando pedidos do marketplace " . $int_to ;				
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;	
		}
		saveMonitor($me, $subject, $event_name,true);
		return "MONIT_OK " . $int_to;
		
	}

	function quotesMissing($me, $int_to = NULL, $minutes = NULL, $minutesafterhours = NULL) {
		
		if (is_null($minutes) || is_null($int_to) || is_null($minutesafterhours) ) {
			return "MONIT_NOK Indique o int_to / minutos / minutosdepoisdahora para verificar";	
		}

		if (!alertHour(true)) { // se não está no horário normal, multiplico as horas por 3 
			$minutes = $minutesafterhours ;  
		}

		$subject = 'Logística';
		$event_name = 'Cotação de Frete '. $int_to ;

		// verifico se este sellercenter tem este marketplace
		$ok=FALSE;
		$me->load->model("model_integrations");
		$integrations = $me->model_integrations->getIntegrationsbyStoreId(0);
		foreach($integrations as $integration) {
			if ($integration['int_to'] == $int_to) {
				$ok = true;
				break;
			}
		}
		if (!$ok) {
			$error = "Marketplace " . $int_to . " não configurado neste sellercenter.";	
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;
		}
		
		$me->load->model("model_quotes_correios");
		
		$quotes = $me->model_quotes_correios->getLastQuotesInTime($int_to, $minutes);
		if (count($quotes) == 0) {
			$error = "Marketplace " . $int_to . " não fez nenhuma cotação de frete nos últimos ".$minutes." minutos";			
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;	
		}
		saveMonitor($me, $subject, $event_name,true);
		return "MONIT_OK " . $int_to;
		
	}

	function jobAlert($me, $param = NULL, $param2 = NULL, $param3 = NULL) {

		$subject = 'Automação';
		$event_name = 'Rotinas Batch';

		$me->load->model("model_job_schedule");
		$error = '';
		$jobs_alert = $me->model_job_schedule->jobsAlert();		
		if ($jobs_alert) {
			foreach($jobs_alert as $job_alert) {
				$error .= 'Job '.$job_alert['module_path'].'/'.$job_alert['module_method'].'/'.
				$job_alert['id'].'/'.$job_alert['params'].' iniciado em '.$job_alert['date_start'].' não acabou até '.
				$job_alert['start_alert'].' <br>';
			}
			
		}
		$jobs_alert = $me->model_job_schedule->jobsNotRunning();		
		if ($jobs_alert) {
			foreach($jobs_alert as $job_alert) {
				$error .= 'Job '.$job_alert['module_path'].'/'.$job_alert['module_method'].'/'.
				$job_alert['id'].'/'.$job_alert['params'].' deveria ter iniciado em '.$job_alert['date_start'].' mas ficou com status=4 <br>';
			}			
		}

		if ($error == '') {
			saveMonitor($me, $subject, $event_name,true);
			return "MONIT_OK " . $param;	
		}
		else {
			saveMonitor($me, $subject, $event_name, false, $error);
			return "MONIT_NOK ".$error;
		}

	}

	function alertHour($extended = false) {
		if ($extended) {
			$alert_during = [
				'Sun' => ['07:30 AM' => '11:00 PM'],
				'Mon' => ['07:30 AM' => '11:00 PM'],
				'Tue' => ['07:30 AM' => '11:00 PM'],
				'Wed' => ['07:30 AM' => '11:00 PM'],
				'Thu' => ['07:30 AM' => '11:00 PM'],
				'Fri' => ['07:30 AM' => '11:00 PM'],
				'Sat' => ['07:30 AM' => '11:00 PM']
			];
		}
		else {
			$alert_during = [
				'Sun' => ['08:30 AM' => '06:48 PM'],
				'Mon' => ['08:30 AM' => '06:48 PM'],
				'Tue' => ['08:30 AM' => '06:48 PM'],
				'Wed' => ['08:30 AM' => '06:48 PM'],
				'Thu' => ['08:30 AM' => '06:48 PM'],
				'Fri' => ['08:30 AM' => '06:48 PM'],
				'Sat' => ['08:30 AM' => '06:48 PM']
			];
		}
		
		$timestamp = time(); 
		
		// get current time object
		$currentTime = (new DateTime())->setTimestamp($timestamp);
		
		// loop through time ranges for current day
		foreach ($alert_during[date('D', $timestamp)] as $startTime => $endTime) {
		
		    // create time objects from start/end times
		    $startTime = DateTime::createFromFormat('h:i A', $startTime);
		    $endTime   = DateTime::createFromFormat('h:i A', $endTime);

		    // check if current time is within a range
		    if (($currentTime > $startTime) && ($currentTime < $endTime)) {
		        return true;
		    }
		}
		
		return false;
		
	}

	function saveMonitor($me, $subject, $event_name, $status, $error = null)
	{
		if ((ENVIRONMENT == 'development') || (ENVIRONMENT == 'testing')) {
			// return ;
		}
		$me->load->model("model_settings");

		if (!$me->model_settings->getStatusbyName('shared_monitor')) {
			return ;
		}

        $sellercenter = $me->model_settings->getValueIfAtiveByName('sellercenter');
		$validity = date('mY');
		$total_up = 0 ;
		$total_down = 0;

		$me->load->model("model_monitor_events");		
		$event = $me->model_monitor_events->getEvent($sellercenter, ENVIRONMENT, $subject, $event_name, $validity);
		if ($event) {
			$total_up = $event['total_up'];
			$total_down = $event['total_down'];
		}
		if ($status) {
			$total_up++;
		}
		else {
			$total_down++;
		}

		$data = array( 
			'sellercenter' 	=> $sellercenter, 
			'environment'	=> ENVIRONMENT,
			'subject'		=> $subject,
			'event_name'	=> $event_name,
			'validity'		=> $validity,
			'status'		=> $status,
			'message'		=> $error,
			'total_up'		=> $total_up,
			'total_down'	=> $total_down,
		);

		if ($event) {
			$me->model_monitor_events->update($data, $event['id']);
		}	
		else {
			$me->model_monitor_events->create($data);
		}
	}
?>