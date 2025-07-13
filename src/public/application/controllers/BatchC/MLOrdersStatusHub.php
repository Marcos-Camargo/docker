<?php
/*
 
Verifica quais ordens mudaram de status e envia para o Mercado Livre 

*/   
// require APPPATH . "controllers/Meli.php";
require APPPATH . "controllers/BatchC/MercadoLivre/Meli.php";

class MLOrdersStatusHub extends BatchBackground_Controller {
		
	var $int_to='ML';
	var $int_from = 'CONECTALA';
	var $client_id='';
	var $client_secret='';
	var $refresh_token='';
	var $access_token='';
	var $date_refresh='';  
	var $seller='';
	var $store_id = 0;
	var $company_id = 1;
	var $shipping_modes = array();
	var $store ; 
	var $franquia = false;
	var $equipe = 'Conecta Lá';
	var $email_atendimento = "atendimento@conectala.com.br";
	
	
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
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_frete_ocorrencias');
		$this->load->model('model_integrations');
		$this->load->model('model_stores');
		$this->load->model('model_company');
		
		$this->load->library('ordersMarketplace');
    }
	
	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setClientId($client_id) {
		$this->client_id = $client_id;
	}
	function getClientId() {
		return $this->client_id;
	}
	function setClientSecret($client_secret) {
		$this->client_secret = $client_secret;
	}
	function getClientSecret() {
		return $this->client_secret;
	}
	function setRefreshToken($refresh_token) {
		$this->refresh_token = $refresh_token;
	}
	function getRefreshToken() {
		return $this->refresh_token;
	}
	function setAccessToken($access_token) {
		$this->access_token = $access_token;
	}
	function getAccessToken() {
		return $this->access_token;
	}
	function setDateRefresh($date_refresh) {
		$this->date_refresh = $date_refresh;
	}
	function getDateRefresh() {
		return $this->date_refresh;
	}
	function setSeller($seller) {
		$this->seller = $seller;
	}
	function getSeller() {
		return $this->seller;
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
		if (!is_null($params)) {// se passou parametros, é do HUB e não da ConectaLa
			$this->store = $this->model_stores->getStoresData($params);
			if (!$this->store) {
				$msg = 'Loja '.$params.' passada como parametro não encontrada!'; 
				echo $msg."\n";
				$this->log_data('batch',$log_name,$msg,"E");
				return ;
			}
			$this->int_from = 'HUB';
			$this->int_to='H_ML';
			$this->store_id = $this->store['id'];
			$this->company_id = $this->store['company_id'];
			$company = $this->model_company->getCompanyData($this->company_id);
			if ($company['name'] == 'SIKA') {  // se for sika, pega todas os orders da empresa menos a loja da sika. 
				$this->franquia = true; 
				$this->equipe = 'Sika Brasil';
				$this->email_atendimento = $company['email'];
			}
			else {
				$equipe = $this->store['name'];
				$this->email_atendimento = $this->store['responsible_email'];
			}
		}

		$this->getkeys($this->company_id,$this->store_id);
		
		$this->getMe();
		$this->mandaNfe();
		$this->mandaTracking();
		$this->mandaEnviado();
		$this->mandaEntregue();
		$this->mandaCancelados();
		// $this->mandaExcecaoEntrega();
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}
	
	function getkeys($company_id,$store_id) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pega os dados da integração.
		//$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),$this->int_from,"DIRECT",$store_id);
		
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setClientId($api_keys['client_id']);
		$this->setClientSecret($api_keys['client_secret']);
		$this->setAccessToken($api_keys['access_token']);
		$this->setRefreshToken($api_keys['refresh_token']);
		$this->setDateRefresh($api_keys['date_refresh']);
		$this->setSeller($api_keys['seller']);
		
		$meli = new Meli($this->getClientId(),$this->getClientSecret(),$this->getAccessToken(),$this->getRefreshToken());
		echo " renovar em ".date('d/m/Y H:i:s',$this->getDateRefresh()).' hora atual = '.date('d/m/Y H:i:s'). "\n"; 
		if ($this->getDateRefresh()+1 < time()) {	
			$user = $meli->refreshAccessToken();
			//var_dump($user);
			if ($user["httpCode"] == 400) {
				$redirectUrl = base_url('LoginML');
				if (strpos($redirectUrl,"teste.conectala.com" ) > 0)  {
					$redirectUrl = "https://www.mercadolivre.com.br";
				}
				$redirectUrl = $meli->getAuthUrl($redirectUrl,Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
				var_dump($redirectUrl);
				//$retorno = $this->getPage($redirectUrl);
				
				//var_dump($retorno);
				die;
			}
			$this->setAccessToken($user['body']->access_token);
			$this->setDateRefresh($user['body']->expires_in+time());
			$this->setRefreshToken($user['body']->refresh_token);
			$api_keys['client_id'] = $this->getClientId();
			$api_keys['client_secret']= $this->getClientSecret();
			$api_keys['access_token'] = $this->getAccessToken();
			$api_keys['refresh_token'] = $this->getRefreshToken();
			$api_keys['date_refresh'] = $this->getDateRefresh();
			$api_keys['seller'] = $this->getSeller();
			
			$integration = $this->model_integrations->update(array('auth_data'=>json_encode($api_keys)),$integration['id']);
			
		}
		echo 'access token ='.$this->getAccessToken()."\n";
		return $meli; 
	}	

	function mandaNfe()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '52';  
		
		if ($this->franquia) {  // se for franquia , pega todas os orders da empresa . 
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatusCompanyId($this->getInt_to(),$paid_status, $this->company_id);
		}
		else {
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status, $this->store_id);
		}
		if (count($ordens_andamento)==0) {
			$msg = 'Nenhuma ordem pendente de envio de nfe da '.$this->getInt_to();
			echo $msg."\n";
			//$this->log_data('batch',$log_name,$msg,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			//pego o frete se existir 
			//$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			
			if ($order['exchange_request']) { // pedido de troca não é atualizado no Mercado Livre 
				echo 'pedido de troca'."\n";

				// [PEDRO] - precisa sempre ir para 50, se for para 53 o seller não irá ocnseguir "gerar" a etiqueta
				//if ($frete) { // se já tem frete, jeito antigo de contratar frete e enviar
				//	$order['paid_status'] = 53; // agora tudo certo para ficar rasteando o pedido
				//}
				//else { // Jeito novo, envia nota fiscal e depois contrata o frete
					$order['paid_status'] = 50; // agora tudo certo para contratar frete 
					$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
				//}
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			$nfes = $this->model_orders->getOrdersNfes($order['id']);
			if (count($nfes) == 0) {
				$msg = 'ERRO: pedido '.$order['id'].' não tem nota fiscal';
				echo $msg."\n";
				$this->log_data('batch',$log_name, $msg,"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}
			$nfe = $nfes[0]; 
	
			$msg ='Seu pedido teve a nota fiscal emitida em '.$this->dataBr($nfe['date_emission']).' de número: '.$nfe['nfe_num'].' série: '.$nfe['nfe_serie'].' chave: '.$nfe['chave'];
			if ($this->sendMessage($order,$msg)) {
				// [PEDRO] - precisa sempre ir para 50, se for para 53 o seller não irá ocnseguir "gerar" a etiqueta
				//if ($frete) { // se já tem frete, jeito antigo de contratar frete e enviar
				//	$order['paid_status'] = 53; // agora tudo certo para ficar rasteando o pedido
				//}
				//else { // Jeito novo, envia nota fiscal e depois contrata o frete
					$order['paid_status'] = 50; // agora tudo certo para contratar frete 
					$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
				//}
				$this->model_orders->updateByOrigin($order['id'],$order);
				echo 'Nota Fiscal enviada para '.$this->getInt_to()."\n";
			}
		} 

	}

	function dataBr($data) {
		$newData = DateTime::createFromFormat('d/m/Y H:i:s',$data);
		if ($newData == false) {
			$newData = DateTime::createFromFormat('d/m/Y H:i:s',$data.' 00:00:00');
			if ($newData == false) {
				$newData = DateTime::createFromFormat('Y-m-d H:i:s',$data);
			}
		}
		return $newData->format('d/m/Y H:i:s');
	}

	function mandaTracking()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';  
		
		if ($this->franquia) {  // se for franquia , pega todas os orders da empresa . 
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatusCompanyId($this->getInt_to(),$paid_status, $this->company_id);
		}
		else {
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status, $this->store_id);
		}
		
		if (count($ordens_andamento)==0) {
			$msg = 'Nenhuma ordem pendente de envio de tracking da '.$this->getInt_to();
			echo $msg."\n";
			//$this->log_data('batch',$log_name,$msg,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			//pego a cotação de frete
			
			if ($order['exchange_request']) { // pedido de troca não é atualizado no Mercado Livre 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			if (!in_array('me1',$this->shipping_modes)) {  // se o cliente não tem me1 como modo de envio não pode atualizr tracking
				echo 'Loja sem me1'."\n";
				$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			
			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				// Não tem frete, não deveria acontecer
				$msg = 'ERRO: Sem frete para a ordem '.$order['id'];
				echo $msg."\n";
				$this->log_data('batch',$log_name,$msg,"E" );
			    $order['paid_status'] = 101; // Precisa contratar o frete manualmente
				$this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}
			$frete = $frete[0];
			
			$nfes = $this->model_orders->getOrdersNfes($order['id']);
			if (count($nfes) == 0) {
				$msg = 'ERRO: pedido '.$order['id'].' não tem nota fiscal';
				echo $msg."\n";
				$this->log_data('batch',$log_name, $msg,"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}
			$nfe = $nfes[0]; 
			
			$pedido_ml = $this->getOrderML($order);
			if ($pedido_ml === false) {
				continue; // nao consegui ler o pedido no ML
			}
			/*
			if (array_key_exists('receiver_id', $pedido_ml)) {
				$receiver = $pedido_ml['receiver_id'];
			}
			else {
				$receiver = $pedido_ml['buyer']['id'];
			} 

			$tracking = array(
				'tracking_number' => $frete['codigo_rastreio'], 
				'receiver_id' => $receiver,
				);
				
			$meli= $this->getkeys($this->company_id,$this->store_id);
			$params = array('access_token' => $this->getAccessToken());
			$url = '/shipments/'. $pedido_ml['shipping']['id'];
			$retornoShip = $meli->put($url,$tracking,$params);
			
			if ($retornoShip['httpCode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retornoShip['httpCode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retornoShip,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no put site:'.$url.' - httpcode: '.$retornoShip['httpCode'].' RESPOSTA: '.print_r($retornoShip,true),"E");
				continue; // abandona esta ordem e pega a próxima. 
			}
			*/

			$shipment = $this->getOrderMLShipments($order); 
			
			if (array_key_exists('id', $shipment)) {
				
				if (($shipment['mode'] !== 'me1') || ($shipment['status'] == 'shipped')){
					echo 'Pedido sem me1 ou já enviado'."\n";
					$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
				    $this->model_orders->updateByOrigin($order['id'],$order);
					continue;
				}
				 
				$tracking = array(
					'tracking_number' => $frete['codigo_rastreio'], 
					'service_id' => 11,
				);
				$params = array('access_token' => $this->getAccessToken());
				$params = array();
				$meli= $this->getkeys($this->company_id,$this->store_id);
				$url = 'shipments/'.$shipment['id'];
				$retornoShip = $meli->put($url,$tracking,$params);
				if ($retornoShip['httpCode'] != 200) {
					echo " Erro URL: ". $url. " httpcode=".$retornoShip['httpCode']."\n"; 
					echo " RESPOSTA ".$this->getInt_to().": ".print_r($retornoShip,true)." \n"; 
					echo " Enviado  ".$this->getInt_to().": ".print_r($tracking,true)." \n"; 
					echo "=============================== pedido \n";
					var_dump($pedido_ml);
					echo "=============================== shipement \n";
					var_dump($shipment);
					$this->log_data('batch',$log_name, 'ERRO no put tracking ordem ='.$order['id'].' site:'.$url.' - httpcode: '.$retornoShip['httpCode'].' RESPOSTA: '.print_r($retornoShip,true),"E");
					continue; // abandona esta ordem e pega a próxima. 
				}				
			}
			
			$msg ='Seu pedido teve a nota fiscal emitida em '.$this->dataBr($nfe['date_emission']).' de número: '.$nfe['nfe_num'].' série: '.$nfe['nfe_serie'].' chave: '.$nfe['chave'].' e agora está esperando a coleta do frete contratado com a empresa '.$frete['ship_company'].' que tem o código de rastreio: '.$frete['codigo_rastreio'].'.';
			if ($this->sendMessage($order,$msg,$pedido_ml)) {
				// Tracking enviado
				$order['paid_status'] = 53; // fluxo novo, manda para enviado
				$this->model_orders->updateByOrigin($order['id'],$order);
				echo 'Tracking enviado para '.$this->getInt_to()."\n";
			}
		} 
	}

	function mandaEnviado()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		
		if ($this->franquia) {  // se for franquia , pega todas os orders da empresa . 
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatusCompanyId($this->getInt_to(),$paid_status, $this->company_id);
		}
		else {
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status, $this->store_id);
		}
		if (count($ordens_andamento)==0) {
			$msg = 'Nenhuma ordem pendente de mudar status para Enviado da '.$this->getInt_to();
			echo $msg."\n";
			//$this->log_data('batch',$log_name,$msg,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			
			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				// Não tem frete, não deveria aconter
				$msg = 'ERRO: Sem frete para a ordem '.$order['id'];
				echo $msg."\n";
				$this->log_data('batch',$log_name,$msg,"E");
				continue;

			}
			$frete = $frete[0];
			
			if (in_array('me1',$this->shipping_modes)) {  // se o cliente não tem me1 como modo de envio não pode atualizar envio
				$shipment = $this->getOrderMLShipments($order); 
				//var_dump($shipment);
				
				if (array_key_exists('id', $shipment)) {
					$data_envio =  str_replace(" ","T",$order['data_envio'])."-03:00";
					$enviado = array(
						'payload' => array(
							"comment" => "despachado",
							"date" => $data_envio,
						), 
						"status" => "shipped",
	   					"substatus" => "null"
					);
					// $params = array('access_token' => $this->getAccessToken());
					$params = array();
					$meli= $this->getkeys($this->company_id,$this->store_id);
					$url = 'shipments/'.$shipment['id'].'/seller_notifications';
					$retornoShip = $meli->post($url,$enviado,$params);
					//var_dump($retornoShip);
					if ($retornoShip['httpCode'] != 200) {
						echo " Erro URL: ". $url. " httpcode=".$retornoShip['httpCode']."\n"; 
						echo " RESPOSTA ".$this->getInt_to().": ".print_r($retornoShip,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no put enviado ordem ='.$order['id'].' site:'.$url.' - httpcode: '.$retornoShip['httpCode'].' RESPOSTA: '.print_r($retornoShip,true),"E");
						continue; // abandona esta ordem e pega a próxima. 
					}				
				}
			} 
			else {
				echo 'Loja sem me1'."\n";		
			}

			$msg ='Seu pedido foi enviado em '.date("d/m/Y",strtotime($order['data_envio'])).' pela empresa '.$frete['ship_company'].' com o código de rastreio '.$frete['codigo_rastreio'].'.';
			if ($this->sendMessage($order,$msg)) {
				// Avisado que foi entregue na transportadora 
				$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
				$this->model_orders->updateByOrigin($order['id'],$order);
				echo 'Aviso de Envio enviado para '.$this->getInt_to()."\n";
			}
		} 

	}

	function mandaEntregue()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 60, ordens que já tem mudaram o status para entregue no FreteRastrear
		$paid_status = '60';  
		
		if ($this->franquia) {  // se for franquia , pega todas os orders da empresa . 
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatusCompanyId($this->getInt_to(),$paid_status, $this->company_id);
		}
		else {
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status, $this->store_id);
		}
		
		if (count($ordens_andamento)==0) {
			$msg = 'Nenhuma ordem pendente de mudar status para Entregue da '.$this->getInt_to();
			echo $msg."\n";
			//$this->log_data('batch',$log_name,$msg,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			
			if (in_array('me1',$this->shipping_modes)) {  // se o cliente não tem me1 como modo de envio não pode atualizar envio
			
				$shipment = $this->getOrderMLShipments($order); 
				//var_dump($shipment);
				if (array_key_exists('id', $shipment)) {
					$data_envio =  date("Y-m-d\TH:i:s.000", strtotime($order['data_entrega']))."-03:00"; 
					$enviado = array(
						'payload' => array(
							"comment" => "Pedido entregue",
							"date" => $data_envio,
						), 
						"status" => "delivered",
						"substatus" => "null"
					);
					// $params = array('access_token' => $this->getAccessToken());
					$params = array();
					$meli= $this->getkeys($this->company_id,$this->store_id);
					$url = 'shipments/'.$shipment['id'].'/seller_notifications';
					$retornoShip = $meli->post($url,$enviado,$params);
					if ($retornoShip['httpCode'] != 200) {
						echo " Erro URL: ". $url. " httpcode=".$retornoShip['httpCode']."\n"; 
						echo " RESPOSTA ".$this->getInt_to().": ".print_r($retornoShip,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no put enviado ordem ='.$order['id'].' site:'.$url.' - httpcode: '.$retornoShip['httpCode'].' RESPOSTA: '.print_r($retornoShip,true),"E");
						continue; // abandona esta ordem e pega a próxima. 
					}				
				}
			} 
			else {
				echo 'Loja sem me1'."\n";		
			}
						
			$msg = 'Seu pedido foi entregue em '.date("d/m/Y",strtotime($order['data_entrega'])).'.';
			if ($this->sendMessage($order,$msg)) {
				// Avisado que foi entregue na transportadora 
				$order['paid_status'] = 6; // O pedido está entregue 
				$this->model_orders->updateByOrigin($order['id'],$order);
				echo 'Aviso de Entregue enviado para '.$this->getInt_to()."\n";
			}
		} 

	}

	function mandaCancelados()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 99, ordens que canceladas que tem que ser avisadas no Marketplace
		$paid_status = '99';  
		
		if ($this->franquia) {  // se for franquia , pega todas os orders da empresa . 
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatusCompanyId($this->getInt_to(),$paid_status, $this->company_id);
		}
		else {
			$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status, $this->store_id);
		}
		
		if (count($ordens_andamento)==0) {
			$msg = 'Nenhuma ordem a cancelar '.$this->getInt_to();
			echo $msg."\n";
			//$this->log_data('batch',$log_name,$msg,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
		   	echo 'Cancelando pedido '.$order['id'].' '.$order['numero_marketplace']."\n"; 
			$cancel = Array (
					  "fulfilled" => false,
					  "rating" => "neutral",
					  "message" => "Não consigo atender no momento",
					  "reason" => "SELLER_REGRETS",
					  "restock_item" => true,
				);
			$meli= $this->getkeys($this->company_id,$this->store_id);
			//$params = array('access_token' => $this->getAccessToken());
			$params = array();
			$url = 'orders/'.$order['numero_marketplace'].'/feedback';
			
			$retorno = $meli->post($url, $cancel, $params);
			if ($retorno['httpCode']=="200")  {  
				if ($retorno['body'][0] == 'Feedback already exists') {
					echo ' alguém cancelou direto no marketplace'."\n";
					$retorno['httpCode']="201";
				}
			}
			if (!($retorno['httpCode']=="201") )  {  // created
				echo 'Erro na respota do '.$this->getInt_to().' httpcode='.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true)."\n"; 
				$this->log_data('batch',$log_name, 'ERRO ao cancelar pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true).' DADOS ENVIADOS:'.print_r($cancel,true),"E");
				return false;
			}

			$this->ordersmarketplace->cancelOrder($order['id'], true);
			echo 'Cancelado em '.$this->getInt_to()."\n";
		} 

	}

	function mandaExcecaoEntrega()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio as ocorrencias de frete com código 5 que não ainda não avisaram ao mktplace o que ocorreu. 
		
		$ocorrencias = $this->model_frete_ocorrencias->getFreightsOcorrenciasByCodigoSemAviso('5');		
		if (count($ocorrencias)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ocorrencia para avisar mktplace '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ocorrencias as $ocorrencia) {
			
			$order=$this->model_orders->getOrdersData(0,$ocorrencia['order_id']);
			if ($order['origin'] != $this->getInt_to()) {
			  //  echo 'ignorando pedido '.$order['id'].' do mktplace '.$order['origin']."\n";
				continue ; 
			}

			$msg_ocorrencia = $ocorrencia['nome'];
			if (!is_null($ocorrencia['mensagem'])) {
				$msg_ocorrencia .= ' '.$ocorrencia['mensagem'];
			}
			echo 'Enviando ocorrencia pedido ='.$order['id'].' '.$msg_ocorrencia."\n"; 

			$msg = 'Em '.date("d/m/Y",strtotime($ocorrencia['data_ocorrencia'])).' a seguinte ocorrência aconteceu no seu pedido:'.$msg.'.';
			if ($this->sendMessage($order,$msg)) {
				$this->model_frete_ocorrencias->updateFreightsOcorrenciaAviso($ocorrencia['id'],'1');
				echo 'Ocorrencia enviada para '.$this->getInt_to()."\n";
			}
		} 

	}
	
	function getOrderMLShipments($order)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$numero_marketplace = $order['numero_marketplace'];
		$meli= $this->getkeys($this->company_id,$this->store_id);
		
		// $params = array('access_token' => $this->getAccessToken());
		$params = array();
		$url = 'orders/'.$numero_marketplace.'/shipments';
		$retorno = $meli->get($url, $params);
			
		if ($retorno['httpCode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpCode'].' RESPOSTA: '.print_r($retorno,true),"E");
			return false;
		}
		$body = json_decode(json_encode($retorno['body']),true);
		return $body; 
	}
	
	function getMe() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$meli = $this->getkeys($this->company_id,$this->store_id);
		$url = 'users/me';
		$params = array();
		$retorno = $meli->get($url, $params);
		if ($retorno['httpCode']!="200")  {  // deu algum erro lendo as informações, volto para o login 
			$msg = 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpCode'].' RESPOSTA: '.print_r($retorno,true). ' ENVIADO: '.print_r($params,true);
			$this->log_data('batch',$log_name,$msg ,"E");
			return false;
		}
		$resp = json_decode(json_encode($retorno['body']),true);
		$this->shipping_modes = $resp['shipping_modes'];
	}
	
	function getOrderML($order)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$numero_marketplace = $order['numero_marketplace'];
		$meli= $this->getkeys($this->company_id,$this->store_id);
		$params = array(
				'seller' => $this->getSeller(),  // usuario de teste "621913621"
				'access_token' => $this->getAccessToken()
			);
		$params = array('access_token' => $this->getAccessToken());
		
		$url = 'orders/'.$numero_marketplace;
		$retorno = $meli->get($url, $params);
			
		if ($retorno['httpCode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpCode'].' RESPOSTA: '.print_r($retorno,true),"E");
			return false;
		}
		$body = json_decode(json_encode($retorno['body']),true);
		return $body; 
	}

	function sendMessage($order, $msg, $pedido_ml = null) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (is_null($pedido_ml)) {
			$pedido_ml =  $this->getOrderML($order);
		}
		if ($pedido_ml === false) {
			return false;
		}
		if (is_null($pedido_ml['pack_id'])) {
			$pack_id= $order['numero_marketplace']; 
		}
		else { 
			$pack_id = $pedido_ml['pack_id'];
		}
	 
		$msg = 'Oi '.$order['customer_name'].', tudo bom?<br>'.$msg.'<br>Estamos à disposição para eventuais dúvidas.<br>Obrigado por comprar conosco.<br>Att,.<br>Equipe '.$this->equipe;
		//var_dump($msg);
		$mensagem = array (
			"from" => array ( 
				"user_id" => $this->getSeller(),
				"email" => $this->email_atendimento,
			),
			"to" => array (
				"user_id" => $pedido_ml['buyer']['id'],
			),
			"text" => $msg,
		);
		$meli= $this->getkeys($this->company_id,$this->store_id);
		$params = array('access_token' => $this->getAccessToken());
		$url = 'messages/packs/'.$pack_id.'/sellers/'.$this->getSeller();
		$retorno = $meli->post($url, $mensagem, $params);
		// var_dump($retorno);
		if ($retorno['httpCode'] == 429) { // estourou o limite
			sleep(60);
			$retorno = $meli->post($url, $mensagem, $params);
		}
		if ($retorno['httpCode'] == 403) { 
			$body = json_decode(json_encode($retorno['body']),true);
			if ($body['error']== 'conversation_blocked') { // a conversa está bloqueada e não ná para enviar mensagens
				echo " Infelizmente, não é possível mandar mensagem para o pedido ".$order['id']."-".$order['numero_marketplace']."\n";
				return true;
			}
		}
		if ($retorno['httpCode'] != 201)  {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($mensagem,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no envio de mensagem pedido '.$order['id'].' '.$order['numero_marketplace'].' site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($mensagem,true),"E");
			return false;
		}
		return true;
	}
	
}
?>
