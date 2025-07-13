<?php
/*
 
Realiza o Leilão de Produtos e atualiza o ML 

*/   
require APPPATH . "controllers/BatchC/MercadoLivre/Meli.php";

 class MLInativarAnuncios extends BatchBackground_Controller {
	
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
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_stores');
		$this->load->model('model_orders');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_products_catalog');
		
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
	
	function run($id=null,$params='null')
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}  
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		$this->getkeys(1,0);
		$retorno = $this->inativarAnuncios();
		
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
	
    function inativarAnuncios()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		$arquivo = fopen ('/var/www/html/app/importacao/inativar_ml.txt', 'r');
		$cnt = 0;
	    while(!feof($arquivo)){
	    	$ml = str_replace("\r\n","",fgets($arquivo));
			
			if (trim($ml) == '') {
				continue;
			}
	      	echo " ML = ".$ml."\n";
			$sql = 'SELECT * FROM prd_to_integration WHERE skumkt = ?';
			$query = $this->db->query($sql, array($ml));
			$lido = $query->row_array();
			$cnt++;
			if ($lido) {
				echo " ". $lido['id']." prd ".$lido['prd_id']."\n";
		 		$this->model_integrations->updatePrdToIntegration(array('status'=>0, 'status_int'=>90, 'approved' => 2, 'ad_link' => null, 'quality' => null), $lido['id']);		 	
			}
			$this->pauseML($ml);
	    }
	    fclose($arquivo);
		echo 'lido = '.$cnt.'\n'; 
		
    } 
 
	function pauseML($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$pause = Array (
						'status' => 'paused'
				);
									
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->put($url, $pause, $params);
		if  ($retorno['httpCode'] == 400 ) { // verifico se já está em closed e ai tem que remover SKUmkt de volta para 00 no Prd_to_integration e Bling_ult_envio
			$body = json_decode(json_encode($retorno['body']),true);
			if ($body['cause'][0]['message'] == 'Item in status closed is not possible to change to status paused. Valid transitions are [closed]') {
				echo 'Colocando 00 para skumkt onde era  '.$skumkt." pois o anúncio ficou Closed no ML \n";
				$sql = 'UPDATE prd_to_integration SET skumkt = "00" WHERE skumkt = ?';
				$cmd = $this->db->query($sql,array($skumkt));
				$sql = 'UPDATE bling_ult_envio SET skumkt = "00" WHERE skumkt = ?';
				$cmd = $this->db->query($sql,array($skumkt));
				return true;
			}
			if ($body['message']== 'Cannot update item '.$skumkt.' [status:under_review, has_bids:false]') {
				echo 'Produdo '.$skumkt.' com status:under_review, has_bids:false - já pausado pelo ML'."\n";
				return true;
			}
			if ($body['message']== 'Cannot update item '.$skumkt.' [status:under_review, has_bids:true]') {
				echo 'Produdo '.$skumkt.' com status:under_review, has_bids:true - já pausado pelo ML'."\n";
				return true;
			}
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		if  ($retorno['httpCode'] != 200 ) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		return true;
	}
	
	function errorTransformation($prd_id, $sku, $msg, $prd_to_integration_id = null, $mkt_code = null)
	{
		$this->model_errors_transformation->setStatusResolvedByProductId($prd_id,$this->getInt_to());
		$trans_err = array(
			'prd_id' => $prd_id,
			'skumkt' => $sku,
			'int_to' => $this->getInt_to(),
			'step' => "Preparação para envio",
			'message' => $msg,
			'status' => 0,
			'date_create' => date('Y-m-d H:i:s'), 
			'reset_jason' => '', 
			'mkt_code' => $mkt_code,
		);
		echo "Produto ".$prd_id." skubling ".$sku." int_to ".$this->getInt_to()." ERRO: ".$msg."\n"; 
		$insert = $this->model_errors_transformation->create($trans_err);
		
		if (!is_null($prd_to_integration_id)) {
			$sql = "UPDATE prd_to_integration SET date_last_int = now() WHERE id = ?";
			$cmd = $this->db->query($sql,array($prd_to_integration_id));
		}
	}
	
	function verificaStatusProduto($skumkt, &$vendas)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$vendas = 0; 
		$meli= $this->getkeys(1,0);
		$params = array(
			'access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->get($url, $params);
		if (!($retorno['httpCode']=="200") )  {  
			echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
			return false;
		}
		$product = json_decode(json_encode($retorno['body']),true);
		//var_dump($product);
		$vendas = $product['sold_quantity'];
		return $product['status'];
	}
	
	
	function closeML($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$pause = Array (
						'status' => 'closed'
				);
									
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->put($url, $pause, $params);
		if  ($retorno['httpCode'] != 200 ) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		return true;
	}
	
	function deleteML($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$pause = Array (
						'deleted' => 'true'
				);
									
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->put($url, $pause, $params);
		if  ($retorno['httpCode'] != 200 ) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		return true;
	}
	
}
?>
