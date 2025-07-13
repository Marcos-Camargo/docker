<?php
/*
 
Realiza o Leilão de Produtos e atualiza o ML 

*/   
require APPPATH . "controllers/Meli.php";

 class MLSyncTickets extends BatchBackground_Controller {
	
	var $int_to_principal='ML'; // esse não varia. Importante para pegar os dados de integração e categoria
	var $int_to='ML';
	var $client_id='';
	var $client_secret='';
	var $refresh_token='';
	var $access_token='';
	var $date_refresh='';
	var $seller='';
	
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
		$this->load->model('model_ticket_ml');
		
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
		//$retorno = $this->syncEstoquePreco();

		$this->getkeys(1,0);
		$this->lerDisputas();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id,$store_id) {
		
		
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->int_to_principal,"CONECTALA","DIRECT",$store_id);
		
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setClientId($api_keys['client_id']);
		$this->setClientSecret($api_keys['client_secret']);
		//$this->setCode($api_keys['code']);
		$this->setAccessToken($api_keys['access_token']);
		$this->setRefreshToken($api_keys['refresh_token']);
		$this->setDateRefresh($api_keys['date_refresh']);
		$this->setSeller($api_keys['seller']);
		
		/*sql 
		insert into integrations values(0,'Mercado Livre',1,0,1,'{"client_id": "191506436626890", "client_secret":"LrmTu6LdGd5ZbJ6LaGhlfHetjRhmmSvI", "access_token": "APP_USR-191506436626890-080619-8fbb33d69b486596df686d37881cf29c-621913621", "refresh_token": "TG-5f2d64516cc5510006ffad98-621913621", "date_refresh": "0"}','DIRECT','CONECTALA','ML');
		
		 * teste
		UPDATE integrations
	SET auth_data='{"seller":"621913621","client_id": "191506436626890", "client_secret":"LrmTu6LdGd5ZbJ6LaGhlfHetjRhmmSvI", "access_token": "APP_USR-191506436626890-080714-eddfbe20be37f652341d229188a8b919-621913621", "refresh_token": "TG-5f2d70e93476b40007062968-621913621", "date_refresh": "0"}'
	WHERE id=54;
		 * 
		 * producao
		 insert into integrations values(0,'Mercado Livre',1,0,1,'{"client_id": "3148997777473390", "client_secret":"VCpjTA97lhrydu4i9EWBU2Fs4F5HNlKL", "access_token": "APP_USR-191506436626890-080714-eddfbe20be37f652341d229188a8b919-621913621", "refresh_token": "TG-5f2c3ed9e929bc00061b3437-553996630", "date_refresh": "0"}','DIRECT','CONECTALA','ML');
		
		 UPDATE integrations
	SET auth_data='{"seller":"553996630","client_id": "4731355931828241", "client_secret":"zrPa3pp8saTYkAauynbrMYlSCSEd9jFu", "access_token": "APP_USR-4731355931828241-081419-39df5819840113f03f84d0e74f868de4-553996630", "refresh_token": "TG-5f36ea0509b33200065f6d80-553996630", "date_refresh": "0"}'
	WHERE id=736;
		 * 
		 * 
		 * */
		
		$meli = new Meli($this->getClientId(),$this->getClientSecret(),$this->getAccessToken(),$this->getRefreshToken());
		//echo " renovar em ".date('d/m/Y H:i:s',$this->getDateRefresh()).' hora atual = '.date('d/m/Y H:i:s'). "\n"; 
		if ($this->getDateRefresh()+1 < time()) {	
			$user = $meli->refreshAccessToken();
			var_dump($user);
			if ($user["httpCode"] == 400) {
				$user = $meli->authorize($this->getRefreshToken(), 'https://www.mercadolivre.com.br');
				var_dump($user);
				if ($user["httpCode"] == 400) {
					$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
					var_dump($redirectUrl);
					//$retorno = $this->getPage($redirectUrl);
					
					//var_dump($retorno);
					die;
				}
			}
			$this->setAccessToken($user['body']->access_token);
			$this->setDateRefresh($user['body']->expires_in+time());
			$this->setRefreshToken($user['body']->refresh_token);
			$authdata=array(
				'client_id' =>$this->getClientId(),
				'client_secret' =>$this->getClientSecret(),
				'access_token' =>$this->getAccessToken(),
				'refresh_token' =>$this->getRefreshToken(),
				'date_refresh' =>$this->getDateRefresh(),
				'seller' => $this->getSeller(),
			);
			$integration = $this->model_integrations->updateIntegrationsbyCompIntType($company_id,$this->int_to_principal,"CONECTALA","DIRECT",$store_id,json_encode($authdata));	
		}
		// echo 'access token ='.$this->getAccessToken()."\n";
		return $meli; 

		/*
		$user = $meli->authorize($this->getRefreshToken(), 'https://www.mercadolivre.com.br');
		if ($user["httpCode"] == 400) {

			$user = $meli->refreshAccessToken();
			var_dump($user);
					
			$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
			var_dump($redirectUrl);
			//$retorno = $this->getPage($redirectUrl);
			
			//var_dump($retorno);
			die;
		}
		$this->setAccessToken($user['body']->access_token);
		$this->setDateRefresh($user['body']->expires_in+time());
		$this->setRefreshToken($user['body']->refresh_token);
		var_dump($user);
		$authdata=array(
				'client_id' =>$this->getClientId(),
				'client_secret' =>$this->getClientSecret(),
				'access_token' =>$this->getAccessToken(),
				'refresh_token' =>$this->getRefreshToken(),
				'date_refresh' =>$this->getDateRefresh(),
			);
			$integration = $this->model_integrations->updateIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id,json_encode($authdata));
		
		
		die; 
		*/
		$user = $meli->refreshAccessToken();
		var_dump($user);
			
		echo " Authorizando \n";
		$user = $meli->authorize($this->getRefreshToken(), 'https://www.mercadolivre.com.br');
		var_dump($user);
		if ($user["httpCode"] == 400) {
			$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
			var_dump($redirectUrl);
			die;
			
			echo " Não autorizou. Tentando o Refresh \n";
			$user = $meli->refreshAccessToken();
			var_dump($user);
		}
		$this->setAccessToken($user['body']->access_token);
		$this->setDateRefresh($user['body']->expires_in);
		$this->setRefreshToken($user['body']->refresh_token);
		
		if ($this->getDateRefresh()+time()+1 < time()) {	
			$refresh = $meli->refreshAccessToken();
			var_dump($refresh);
			
			$this->setAccessToken($refresh['body']->access_token);
			$this->setDateRefresh($refresh['body']->expires_in);
			$this->setRefreshToken($refresh['body']->refresh_token);
			$authdata=array(
				'client_id' =>$this->getClientId(),
				'client_secret' =>$this->getClientSecret(),
				'access_token' =>$this->getAccessToken(),
				'refresh_token' =>$this->getRefreshToken(),
				'date_refresh' =>$this->getDateRefresh(),
			);
			$integration = $this->model_integrations->updateIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id,json_encode($authdata));
		
		}
	
		die;
		
	}
		
	function lerDisputas()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$scroll_id = '';
		$cnt =0;
		$offset = 0 ;
		while (true){
			$meli= $this->getkeys(1,0);
			$params = array(
				'stage' => 'dispute',
				'offset' => $offset,
				'access_token' => $this->getAccessToken(),
					//	'search_type' => 'scan',
				);
			//if ($scroll_id != '') {
			//	$params['scroll_id'] = $scroll_id;
			//}
			$url = '/v1/claims/search';
			$retorno = $meli->get($url, $params);
			if (!($retorno['httpCode']=="200") )  {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO ao buscar claims no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
				return;
			}
			$body = json_decode(json_encode($retorno['body']),true);
			// $scroll_id = $body['paging'];
			$total = $body['paging']['total'];
			$limit = $body['paging']['limit'];
			$results = $body['data'];
			
			if (count($results)==0) {
				echo "acabou\n";
				break;
			}
			echo ' total '.$total."\n";
			echo ' limit '.$limit."\n";
			foreach ($results as $claim) {
				echo "claim ".$claim['id'].' '.$claim['resource'].' '.$claim['resource_id']."\n"; 
				if ($claim['resource'] == 'shipment') {
					$meli= $this->getkeys(1,0);
					$params = array(
						'access_token' => $this->getAccessToken());
					$url = '/shipments/'.$claim['resource_id'];
					$retorno = $meli->get($url, $params);
					if (!($retorno['httpCode']=="200") )  {  
						echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
						return;
					}
					$body = json_decode(json_encode($retorno['body']),true);
					$order_id = $body['order_id'];
					echo " -----> achei ".$order_id."\n";
				}
				elseif ($claim['resource'] == 'order') {
					$order_id = $claim['resource_id']; 
				}
				else {
					continue; // outros tipos eu não puxo. 
				}

				$data = array (
					'id' 			=> $claim['id'],
					'order_id' 		=> $order_id,
					'type' 			=> $claim['type'],
					'stage' 		=> $claim['stage'],
					'status' 		=> $claim['status'],
					'parent_id' 	=> $claim['parent_id'],
					'resource_id' 	=> $claim['resource_id'],
					'resource' 		=> $claim['resource'],
					'reason_id' 	=> $claim['reason_id'],
					'fulfilled'	 	=> $claim['fulfilled'],
					'players' 		=> json_encode($claim['players']),
					'resolution' 	=> json_encode($claim['resolution']),
					'site_id' 		=> $claim['site_id'],
					'date_created' 	=> $claim['date_created'],
					'last_updated' 	=> $claim['last_updated'],
				);
				$this->model_ticket_ml->replace($data);
			}
			$offset += $limit; 
			if ($offset > $total) {
				echo "acabou 2\n";
				break;
			}		
		}

    }
	
	
   
}
?>
