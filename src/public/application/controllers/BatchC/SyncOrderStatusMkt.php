<?php

require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';
require APPPATH . "controllers/Meli.php";

class SyncOrderStatusMkt extends BatchBackground_Controller {
		
	private $via_authorization = null;
	private $via_oAuth2 = null;
	private $via_integration = null;
	
	private $car_site = null;
	private $car_apikey = null;

	private $b2w_email = null;
	private $b2w_apikey = null;

	private $ml_client_id = null;
	private $ml_client_secret = null;
	private $ml_access_token = null;
	private $ml_refresh_token = null;
	private $ml_date_refresh = null;
	private $ml_seller = null;

	private $mad_site = null;
	private	$mad_token = null;
	private	$mad_id_seller = null;
		
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

		$this->via_oAuth2 = new ViaOAuth2();
        $this->via_integration = new ViaIntegration();

		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_integrations');
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
		
		$this->loadAuthorizationsMarketplaces($params);

		$this->syncOrdersStatusMkt($params, 0, 100);

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function loadAuthorizationsMarketplaces($int_to) {
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, $int_to, "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'],true);
		if ($int_to == 'VIA') {
			$client_id = $api_keys['client_id'];
			$client_secret = $api_keys['client_secret']; 
			$grant_code = $api_keys['grant_code']; 
			
			$this->via_authorization = $this->via_oAuth2->authorize($client_id, $client_secret, $grant_code);
		}
		else if ($int_to == 'CAR') {
			$this->car_apikey = $api_keys['apikey'];
			$this->car_site = $api_keys['site'];
		}
		else if ($int_to == 'B2W') {
			$this->b2w_apikey = $api_keys['apikey'];
			$this->b2w_email = $api_keys['email'];
		}
		else if (($int_to == 'ML') || ($int_to == 'MLC')) {
			$this->ml_client_id = $api_keys['client_id'];
			$this->ml_client_secret = $api_keys['client_secret'];
			$this->ml_access_token = $api_keys['access_token'];
			$this->ml_refresh_token = $api_keys['refresh_token'];
			$this->ml_date_refresh = $api_keys['date_refresh'];
			$this->ml_seller = $api_keys['seller'];
		}
		else if ($int_to == 'MAD') {
			$this->mad_site = $api_keys['site'];
			$this->mad_token = $api_keys['token'];
			$this->mad_id_seller = $api_keys['id_seller'];
		}
	}

	function syncOrdersStatusMkt($int_to, $offset, $limit) {
		$orders = $this->model_orders->getOrdersOpenToSyncStatusMkt($int_to, $offset, $limit);

		foreach ($orders as $order) {
			echo $order['bill_no'] . ' - Origin: '. $order['origin'] . PHP_EOL;
			switch ($order['origin']) {
				case 'VIA':
					$this->syncOrderStatusVia($order);
					break;
				
				case 'CAR':
					$this->syncOrderStatusCar($order);
					break;
				
				case 'B2W':
					$this->syncOrderStatusB2w($order);
					break;

				case 'MLC':
				case 'ML':
					// $this->syncOrderStatusML($order);
					break;
				
				case 'MAD':
					$this->syncOrderStatusMad($order);
					break;
				
				default:
					break;
			}
		}

		if (count($orders) > 0) {
			$this->syncOrdersStatusMkt($int_to, $offset + $limit, $limit);
		}
	}

	function syncOrderStatusVia($order) {
		$response = $this->via_integration->getOrder($this->via_authorization, $order['numero_marketplace']);
		if ($response['http_code'] == 200) {
			$order_mkt = json_decode($response['content']);
			 
			$update = true;
			if (!is_null($order['status_mkt'])) {
				$update = !($order['status_mkt'] == $order_mkt->status);
			}

			$finished_monitoring = false;
			if ((intval($order['paid_status']) >= 90) and (intval($order['paid_status']) <= 99) and ($order_mkt->status == 'CAN')) {
				$finished_monitoring = true;
				$update = true;
			}
			
			if ((intval($order['paid_status']) == 6) and ($order_mkt->status == 'DLV')){
				$finished_monitoring = true;
				$update = true;
			}

			if ($update) {
				$this->model_orders->updateStatusMkt($order['id'], $order_mkt->status, $finished_monitoring);
			}
		}
		else if ($response['http_code'] == 429) {
			echo 'WAITING...'. PHP_EOL;
			sleep(60);
			$this->syncOrderStatusVia($order);
		}
	}

	function syncOrderStatusCar($order) {
		$url = 'https://'.$this->car_site.'/api/orders/'.$order['numero_marketplace'];
		$response = $this->getCarrefour($url, $this->car_apikey);

		if ($response['http_code'] == 200) {
			$order_mkt = json_decode($response['content']);
			$update = true;
			if (!is_null($order['status_mkt'])) {
				$update = !($order['status_mkt'] == $order_mkt->order_state);
			}

			$finished_monitoring = false;
			if ((intval($order['paid_status']) >= 90) and (intval($order['paid_status']) <= 99) and ($order_mkt->order_state == 'CANCELED')) {
				$finished_monitoring = true;
				$update = true;
			}
			
			if ((intval($order['paid_status']) == 6) and ($order_mkt->order_state == 'RECEIVED')){
				$finished_monitoring = true;
				$update = true;
			}

			if ($update) {
				$this->model_orders->updateStatusMkt($order['id'], $order_mkt->order_state, $finished_monitoring);
			}
		}
		else if ($response['http_code'] == 429) {
			echo 'WAITING...'. PHP_EOL;
			sleep(60);
			$this->syncOrderStatusCar($order);
		}
	}

	function syncOrderStatusB2w($order) {
		$url = 'https://api.skyhub.com.br/orders/'.$order['numero_marketplace'];

		$response = $this->getSkyHub($url, $this->b2w_apikey, $this->b2w_email);

		if ($response['http_code'] == 200) {
			$order_mkt = json_decode($response['content']);
			$update = true;
			if (!is_null($order['status_mkt'])) {
				$update = !($order['status_mkt'] == $order_mkt->status->type);
			}

			$finished_monitoring = false;
			if ((intval($order['paid_status']) >= 90) and (intval($order['paid_status']) <= 99) and ($order_mkt->status->type == 'CANCELED')) {
				$finished_monitoring = true;
				$update = true;
			}
			
			if ((intval($order['paid_status']) == 6) and ($order_mkt->status->type == 'DELIVERED')){
				$finished_monitoring = true;
				$update = true;
			}

			if ($update) {
				$this->model_orders->updateStatusMkt($order['id'], $order_mkt->status->type, $finished_monitoring);
			}
		}
		else if ($response['http_code'] == 429) {
			echo 'WAITING...'. PHP_EOL;
			sleep(60);
			$this->syncOrderStatusCar($order);
		}
	}

	function syncOrderStatusMl($order) {
		$numero_marketplace = $order['numero_marketplace'];
		$meli = $this->getAccessTokenMl();
		$params = array(
			'seller' => $this->ml_seller,
			'access_token' => $this->ml_access_token
		);
		$params = array('access_token' => $this->ml_access_token);
		
		$url = 'orders/'.$numero_marketplace;
		$response = $meli->get($url, $params);

		if ($response['httpCode'] == 200) {
			$order_mkt = json_decode($response['body']);
			$update = true;
			if (!is_null($order['status_mkt'])) {
				$update = !($order['status_mkt'] == $order_mkt->status->type);
			}

			$finished_monitoring = false;
			if ((intval($order['paid_status']) >= 90) and (intval($order['paid_status']) <= 99) and ($order_mkt->status->type == 'CANCELED')) {
				$finished_monitoring = true;
				$update = true;
			}
			
			if ((intval($order['paid_status']) == 6) and ($order_mkt->status->type == 'DELIVERED')){
				$finished_monitoring = true;
				$update = true;
			}

			if ($update) {
				$this->model_orders->updateStatusMkt($order['id'], $order_mkt->status->type, $finished_monitoring);
			}
		}
		else if ($response['httpCode'] == 429) {
			echo 'WAITING...'. PHP_EOL;
			sleep(60);
			$this->syncOrderStatusCar($order);
		}
	}

	function syncOrderStatusMad($order) {
		$url = $this->mad_site.'/v1/pedido/id/'.$order['numero_marketplace'];

		// https://documenter.getpostman.com/view/3341659/RztmqU19#bdbf24d3-8c16-444b-be9b-36f635f49343
		$orders_desc = array (
			1 => 'NOVO', 
			2 => 'RECEBIDO',
			3 => 'APROVADO',
			4 => 'CANCELADO',
			5 => 'CANCELADO',
			6 => 'NF EMITIDA', 
			7 => 'ENVIADO',
			8 => 'ENTREGUE'
		); 
		
		$response = $this->getMadeira($url);

		if ($response['http_code'] == 200) {
			$order_mkt = json_decode($response['content']);
			$order_mkt = $order_mkt->data;
			$update = true;
			if (!is_null($order['status_mkt'])) {
				$update = !($order['status_mkt'] == $orders_desc[(int)$order_mkt->status]);
			}

			$finished_monitoring = false;
			if ((intval($order['paid_status']) >= 90) and (intval($order['paid_status']) <= 99) and ((int)$order_mkt->status== 4 || (int)$order_mkt->status== 5)) {
				$finished_monitoring = true;
				$update = true;
			}
			
			if ((intval($order['paid_status']) == 6) and ((int)$order_mkt->status== 8)){
				$finished_monitoring = true;
				$update = true;
			}

			if ($update) {
				$this->model_orders->updateStatusMkt($order['id'], $orders_desc[(int)$order_mkt->status], $finished_monitoring);
			}
		}
		else if ($response['http_code'] == 429) {
			echo 'WAITING...'. PHP_EOL;
			sleep(60);
			$this->syncOrderStatusMad($order);
		}
	}

	function getAccessTokenMl() {
		return ;
		$meli = new Meli($this->ml_client_id, $this->ml_client_secret, $this->ml_access_token, $this->ml_refresh_token);

		if ($this->ml_date_refresh+1 < time()) {	
			$user = $meli->refreshAccessToken();
			//var_dump($user);
			if ($user["httpCode"] == 400) {
				$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
				var_dump($redirectUrl);
				//$retorno = $this->getPage($redirectUrl);
				
				//var_dump($retorno);
				die;
			}
			$this->ml_access_token = $user['body']->access_token;
			$this->ml_date_refresh = $user['body']->expires_in+time();
			$this->ml_refresh_token = $user['body']->refresh_token;
			$authdata=array(
				'client_id' =>$this->ml_client_id,
				'client_secret' =>$this->ml_client_secret,
				'access_token' =>$this->ml_access_token,
				'refresh_token' =>$this->ml_refresh_token,
				'date_refresh' =>$this->ml_date_refresh,
				'seller' => $this->ml_seller,
			);
			echo json_encode($authdata);
			//$integration = $this->model_integrations->updateIntegrationsbyCompIntType(1, 'ML',"CONECTALA","DIRECT",0,json_encode($authdata));	
		}

		return $meli; 
	}

	function getSkyHub($url, $api_key, $login){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	    	CURLOPT_CONNECTTIMEOUT => 360,
	    	CURLOPT_TIMEOUT 	   => 900,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}

	function getCarrefour($url, $api_key){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "GET",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json',
				'Authorization: '.$api_key,
				)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}

    function getMadeira($url){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "GET",
			CURLOPT_HTTPHEADER =>  array(
				'content-type: application/json',
				'accept: application/json',
				'TOKENMM: '.$this->mad_token,
				)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}
}
?>
