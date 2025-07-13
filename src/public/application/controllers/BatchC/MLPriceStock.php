<?php
/*
 
Realiza o Leilão de Produtos e atualiza o ML 

*/   
require APPPATH . "controllers/BatchC/MercadoLivre/Meli.php";

 class MLPriceStock extends BatchBackground_Controller {
	
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
		if ($params == 'null') {
			$this->setInt_to('ML'); 
		}
		else {
			$this->setInt_to($params); 
		}
		$retorno = $this->syncProducts();
		
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
		 * ID: 2790636126327965
		 * 
		 *  
		insert into integrations values(0,'Mercado Livre',1,0,1,'{"client_id": "191506436626890", "client_secret":"LrmTu6LdGd5ZbJ6LaGhlfHetjRhmmSvI", "access_token": "APP_USR-191506436626890-080619-8fbb33d69b486596df686d37881cf29c-621913621", "refresh_token": "TG-5f2d64516cc5510006ffad98-621913621", "date_refresh": "0"}','DIRECT','CONECTALA','ML');
		
		 * teste  test_user_36704572@testuser.com
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
		 * 
		 * NOVO USUARIO DE TESTE 29/3/2021
		 {
    "id": 735662539,
    "nickname": "TETE3491905",
    "password": "qatest4248",
    "site_status": "active",
    "email": "test_user_40895670@testuser.com"
		 * client_id: 8459902962898600
}
		 * */
		
		$meli = new Meli($this->getClientId(),$this->getClientSecret(),$this->getAccessToken(),$this->getRefreshToken());
		//echo " renovar em ".date('d/m/Y H:i:s',$this->getDateRefresh()).' hora atual = '.date('d/m/Y H:i:s'). "\n"; 
		if ($this->getDateRefresh()+60 < time()) {
			sleep(100);	
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
	
    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		$estoqueIntTo=array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value']; 
			$estoqueIntTo[$key_param] = $parm['value'];
		}	
      	
		$offset = 0;
		$limit = 50;
		$tem = true; 
		while ($tem) {
			$sql = "SELECT * FROM prd_to_integration WHERE approved = 1 AND status=1 AND (status_int =0 OR status_int =1 OR status_int =2) AND int_type=13 AND int_to='".$this->getInt_to()."' LIMIT ".$limit." OFFSET ".$offset;	
	 		$query = $this->db->query($sql);
			$data = $query->result_array();
			
			If (!$data) {
				$tem = false;
			}
			$offset+= $limit; 
			foreach ($data as $key => $row) 
		    {
				$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
				$cmd = $this->db->query($sql);
				$prd = $cmd->row_array();
				
				// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
				$qty_salvo = $prd['qty'];
				$qty_atual = (int) $prd['qty'] * $estoqueIntTo[$row['int_to']] / 100; 
				$qty_atual = ceil($qty_atual); // arrendoda para cima 
				$status_int = 2;
				if ((int)$prd['qty'] < 5) { // Mando só para a B2W se a quantidade for menor que 5. 
					$qty_atual = 0;
					$status_int = 10;
				}
				$prd['qty'] = $qty_atual;
				
				if (($prd['status'] == 2) || ($prd['situacao'] == 1)) {
						// está inativo ou incompleto 
					$prd['qty'] = 0 ;
				}
				echo " enviando produto ".$row['prd_id']."\n";
							
				$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$row['prd_id'];
				$cmd = $this->db->query($sql);
				$bling_ult_envio = $cmd->row_array();
				$skumkt = '';
				if ($bling_ult_envio) {
					$skumkt = $bling_ult_envio['skumkt'];
					if (substr($row['skumkt'],0,3) != 'MLB') {
						continue ; 
					}
				}
				else {
					// só mando os que já existem no ML
					continue ; 
				}
				echo "SKUMKT =".$skumkt."\n";
				$sku = $row['skubling'];
				// pego o preço por Marketplace 
				$old_price = $prd['price'];
				$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
				if ($old_price != $prd['price']) {
					echo " Produto ".$prd['id']." tem preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
				}
				// Pego o preço a ser praticado 
				$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"ML");
				// e ai vejo se tem campanha 
				//$prd['price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to());
				$prd['price'] = round($prd['price'],2);
				
				echo 'Processando produto prd_to_integration Id= '.$row['id'].' sku '.$row['skubling']." SkuML ".$row['skumkt']."\n";
				
				$resp = $this->alteraPrecoEstoque($prd,$sku,$estoqueIntTo[$this->getInt_to()], $skumkt, $ad_link, $quality, $vendas);    
				if ($resp) {
					// TROUXE PRA DENTRO DO SUCESSO
					$sql = "UPDATE prd_to_integration SET skumkt = '".$skumkt."' , ad_link = '".$ad_link."' , quality = '".$quality."',status_int=".$status_int."  WHERE id = ".$row['id'];
					$cmd = $this->db->query($sql);
				}
		    }
		}
		
		echo "Sincronismo terminado\n";
        return ;
    } 

	function alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, &$skumkt,&$ad_link, &$quality, &$vendas) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 

		$novo_produto = false;
		if (substr($skumkt,0,3) != 'MLB') {
			return false;
		} 
		echo "Vou alterar SKUMKT =".$skumkt."\n";

		$status = $this->verificaStatusProduto($skumkt, $vendas);
		if ($status == false) {
			echo "não foi possível ler o produto no ML ".$skumkt."\n";
			return false;
		} elseif (($status != 'active')  && ($status != 'paused')) {
			echo "Oferta com status ".$status." no marketplace para ".$skumkt."\n";
			$this->errorTransformation($prd['id'],$skubling, "Oferta com status ".$status." no marketplace");
			if ($status == 'closed') { // mercado livre detonou nosso produto.... 
				// rejeitado pelo Mercado Livre 
				$sql = "UPDATE prd_to_integration SET approved = 4  WHERE int_to = ? AND prd_id = ? AND skumkt = ?";
				$cmd = $this->db->query($sql, array($this->getInt_to(),$prd['id'],$skumkt));
			}
		    return false;
		} 	
		
		$produto = array(); 
		$prazo = $prd['prazo_operacional_extra']; 
		if ($prazo < 1 ) { $prazo = 1; }
		$produto = array(
			"sale_terms" => array(
     			array (
        			"id" => "MANUFACTURING_TIME",
        			"value_name" =>  $prazo." dias",
     			)
  			),
		);
		if ($prd['has_variants']=="") {  // se não tem variação, o preço e quantidade fazem parte do produto, caso contrário, faz parte da variação. 
			$produto["available_quantity"] = ((int)$prd['qty']<0) ? 0: (int)$prd['qty'];
			$produto["price"] = (float)$prd['price'];
		}
		else {
			$prd_variacao = array();
			$variations = array();
            $prd_vars = $this->model_products->getProductVariants($prd['id'],$prd['has_variants']);
            // var_dump($prd_vars);
            $tipos = explode(";",$prd['has_variants']);
            // var_dump($tipos);
			
			foreach($prd_vars as $value) {
				// var_dump($value);
			  	if (isset($value['sku'])) {
			  		$mkt_sku = $this->model_products->getMarketplaceVariantsByFields($this->getInt_to(),0,$prd['id'],$value['variant']);  // vejo se é uma variação nova ou antiga
					if (!$mkt_sku) {
						echo " variação ainda não cadastrada \n";
						return false;
					}
					$qty_atual = (int)$value['qty'] * $estoqueIntTo / 100; 
					$variacao = array(
						"price" => (float)$prd['price'], // apesar de parecer mandar preços diferentes, o ML ignora e usa o mais alto 
						"available_quantity" => ceil($qty_atual),
					);
					$variacao['id'] = $mkt_sku['sku'];
					$variations[] = $variacao;
				}	

			}
			$produto['variations'] = $variations;	
			
		}
		
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());

		echo "Alterando o produto ".$prd['id']." ".$prd['name']." skumkt = ".$skumkt."\n";
		echo print_r(json_encode($produto),true)."\n";
		$url = '/items/'.$skumkt;
		$hc429 = true; 
		while ($hc429) {
			$retorno = $meli->put($url, $produto, $params);
			if (($retorno['httpCode'] == 429) || ($retorno['httpCode'] == 504) || ($retorno['httpCode'] == 0)) { // estourou o limite
				echo "Muitas requisições ou Timeout. Httpcode = ".$retorno['httpCode'].". Aguardando 60 segundos\n";
				sleep(60);
				$meli= $this->getkeys(1,0);
				$params = array('access_token' => $this->getAccessToken());
				$retorno = $meli->put($url, $produto, $params);
			}
			else {
				$hc429 = false; 
			} 		
		}
		if ($retorno['httpCode'] == 500) { // Algum erro interno no ML. Vejamos se dá para tratar 
			$respostaML = json_decode(json_encode($retorno['body']),true);
			if (!is_null($respostaML)) { // é um json talvez possa ser tratado.
				if ($respostaML['message'] == 'The thread pool executor cannot run the task. The upper limit of the thread pool size has probably been reached. Current pool size: 1000 Maximum pool size: 1000') { 
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
					return;
				}
				if ($respostaML['message'] == 'Timeout waiting for idle object') { 
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
					return;
				}
				// if ($respostaML['message'] == '[http-nio2-8080-exec-97] Timeout: Pool empty. Unable to fetch a connection in 0 seconds, none available[size:30; busy:30; idle:0; lastwait:100].') { 
				if (strpos($respostaML['message'], 'Timeout: Pool empty. Unable to fetch a connection in 0 seconds, none available')>0) {
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
					return;
				}
				if ($respostaML['message'] == 'Closed Connection') {
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
					return;
				}
			}
		}
		
		if ($retorno['httpCode'] == 401) { // expirou o token 
			$respostaML = json_decode(json_encode($retorno['body']),true);
			echo ' entre aqui '."\n";
			var_dump($respostaML);
			if ($respostaML['message'] == 'expired_token') {
				echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
				sleep(60);
				$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
				return;
			}
		}
		if (($retorno['httpCode'] == 400) && ($status != 'paused')){ // è um pause que não deixa acertar o produto
			echo "Oferta com status ".$status." no marketplace para ".$skumkt."\n";
			$this->errorTransformation($prd['id'],$skubling, "Oferta com status ".$status." no marketplace");
			return false;
		}
		if ($retorno['httpCode'] == 400) {
			$respostaML = json_decode(json_encode($retorno['body']),true);
			if ( $respostaML['error'] == 'validation_error')  {
				$errors = $respostaML['cause'];
				$errorTransformation = '';
				foreach ($errors as $erro) {
					if ($erro['type'] == 'error') {
						$errorTransformation =  'ERRO: '.$erro['message'].' ! ';
						$this->errorTransformation($prd['id'],$skubling, $errorTransformation,$erro['code'] );
					}
				}
				if ($errorTransformation == '') {
					$errorTransformation= json_encode($retorno['body']); 
					$this->errorTransformation($prd['id'],$skubling, $errorTransformation );
				}
				echo $errorTransformation."\n";
			}
			else {
				$this->errorTransformation($prd['id'],$skubling, json_encode($retorno['body']));
				echo json_encode($retorno['body'])."\n";
			}
			return false;
		}
		if  ($retorno['httpCode'] != 200)  {
				
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($produto,true),"E");
			die;
			return false;
		}
		$respostaML = json_decode(json_encode($retorno['body']),true);
		$skumkt =$respostaML['id'];
		echo " Codigo do Mercado Livre - ".$skumkt."\n";
		//var_dump($respostaML);
		$ad_link =$respostaML["permalink"];
		echo " link = ".$ad_link."\n"; 
		$quality = $respostaML["health"];
		echo " quality = ".$quality."\n";
		return $skumkt;
	
	} 

	function verificaStatusProduto($skumkt, &$vendas)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$vendas = 0; 
		$meli= $this->getkeys(1,0);
		$params = array(
			'access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;

		$hc429 = true; 
		while ($hc429) {
			$retorno = $meli->get($url, $params);
			if (($retorno['httpCode'] == 429) || ($retorno['httpCode'] == 504)) { // estourou o limite
				echo "Muitas requisições ou Timeout ".$retorno['httpCode'].". Aguardando 60 segundos\n";
				sleep(60);
				$meli= $this->getkeys(1,0);
				$params = array('access_token' => $this->getAccessToken());
				$retorno = $meli->get($url, $params);
			}
			else {
				$hc429 = false; 
			} 		
		}

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

	function errorTransformation($prd_id, $sku, $msg, $mkt_code = null)
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
	}
	
}
?>
