<?php
/*
 
Baixa os pedidos que chegaram no Carrefour

*/   
// require APPPATH . "controllers/Meli.php";
require APPPATH . "controllers/BatchC/MercadoLivre/Meli.php";

class MLGetOrdersHub extends BatchBackground_Controller {
	
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

	public $argParams = [];

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
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_products');
		$this->load->model('model_freights');
		$this->load->model('model_settings');
		$this->load->model('model_ml_ult_envio');
		$this->load->model('model_log_integration_order_marketplace');
		
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

	public function buildArgsData($argsData, $fields, $value, $i)
	{
		if (isset($fields[$i]) && !isset($argsData[$fields[$i]])) {
			$argsData = array_merge($argsData, [$fields[$i] => []]);
		}
		if (count($fields) > $i) {
			$child = $this->buildArgsData($argsData[$fields[$i]], $fields, $value, ($i + 1));
			if (is_array($child)) {
				$argsData[$fields[$i]] = array_merge($argsData[$fields[$i]], $child);
			} else {
				$argsData = array_merge($argsData, [$fields[$i] => $child]);
			}
		} else {
			return $value ?? '';
		}
		return $argsData;
	}

	public function parseArgs($args)
	{
		foreach ($args as $k => $arg) {
			if (!strpos($arg, ':')) {
				continue;
			}
			$valuePos = substr($arg, strpos($arg, ':') + 1);
			$keyPos = substr($arg, 0, strpos($arg, ':'));
			if (strlen($valuePos) > 1) {
				$fields = explode('.', $keyPos);
				$this->argParams = $this->buildArgsData($this->argParams, $fields, $valuePos, 0);
			}
		}
	}

    /**
     * @param null $id
     * @param null $params
     * [@param] optional ex: search.[MLSearchQueryParam]:value config.[keyConditions]:value
     */
    // php index.php BatchC/MLGetOrdersHubArgs run null store_id ...args
    // ex: php index.php BatchC/MLGetOrdersHubArgs run null 504 search.q:4868076254 config.allowImportOrderWithStatus:cancelled
	function run($id=null,$params=null)
	{
		$this->parseArgs(func_get_args());
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
			$store = $this->model_stores->getStoresData($params);
			if (!$store) {
				$msg = 'Loja '.$params.' passada como parametro não encontrada!'; 
				echo $msg."\n";
				$this->log_data('batch',$log_name,$msg,"E");
				return ;
			}
			$this->int_from = 'HUB';
			$this->int_to='H_ML';
			$this->store_id = $store['id'];
			$this->company_id = $store['company_id'];
		}
		$this->getkeys($this->company_id,$this->store_id);
		$this->getorders();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id,$store_id) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		//pega os dados da integração.
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),$this->int_from,"DIRECT",$store_id);
		
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setClientId($api_keys['client_id']);
		$this->setClientSecret($api_keys['client_secret']);
		$this->setAccessToken($api_keys['access_token']);
		$this->setRefreshToken($api_keys['refresh_token']);
		$this->setDateRefresh($api_keys['date_refresh']);
		$this->setSeller($api_keys['seller']);
		
		$meli = new Meli($this->getClientId(),$this->getClientSecret(),$this->getAccessToken(),$this->getRefreshToken());
		echo " Renovar em ".date('d/m/Y H:i:s',$this->getDateRefresh()).' hora atual = '.date('d/m/Y H:i:s'). "\n"; 
		if ($this->getDateRefresh()+1 < time()) {	
			$user = $meli->refreshAccessToken();
			if ($user["httpCode"] == 400) {
				var_dump($user);
				$redirectUrl = base_url('LoginML');
				if (strpos($redirectUrl,"teste.conectala.com" ) > 0)  {
					$redirectUrl = "https://www.mercadolivre.com.br";
				}
				$user = $meli->authorize($this->getRefreshToken(), $redirectUrl);
				var_dump($user);
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

    function getorders()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->load->library('calculoFrete');
		
		$order_state_codes="confirmed,payment_required,payment_in_process,paid,cancelled,invalid"; 
		$start_update_date= date("Y-m-d",time() - 60 * 60 * 24*7).'T00:00:00.000-00:00';
		$end_update_date= date("Y-m-d",time()).'T23:59:59.000-00:00';
		
		$offset = 0;
		while (true) {
			$meli= $this->getkeys($this->company_id,$this->store_id);
			$params = array(
				'offset' => $offset,
				'order.date_created.from' => $start_update_date,
				'order.date_created.to' => $end_update_date,
				// 'access_token' => $this->getAccessToken()
			);

			if (isset($this->argParams['search'])) {
				$params = $this->argParams['search'];
			}

			$params['seller'] = $this->getSeller(); // usuario de teste "621913621"

			$url = '/orders/search';
			$retorno = $meli->get($url, $params);
			
			if ($retorno['httpCode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpCode'].' RESPOSTA: '.print_r($retorno,true),"E");
				return;
			}
			$body = json_decode(json_encode($retorno['body']),true);
			
			$paging = $body['paging'];
			$results = $body['results'];
			// var_dump($body);
			if (count($results)==0) {
				echo "acabou\n";
				break;
			}
			
			echo "Total de pedidos = ".$paging['total']."\n"; 

			//$pedidos = json_decode($retorno['body'],true);
			foreach ($results as $pedido) {
				
				echo "------------------------------------------------------------------------\n";	
				echo "Pedido = ".$pedido['id']."\n";
				//var_dump($pedido);
	
				// Verifico se todos os skus estão certos e são das mesmas empresas 
				$cpy ='';
				$store_id = '';
				$erro = false;
				$cross_docking_default = 0;
				$cross_docking = $cross_docking_default; 
				$cancelar_pedido = false; 
				foreach($pedido['order_items'] as $item) {
					$item = $item['item'];

					$sku_item = $item['seller_sku']; 
					$prf= $this->model_ml_ult_envio->getBySku($sku_item);
					
					if (empty($prf)) {
						var_dump($pedido);
						echo 'O pedido '.$pedido['id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()." ou do MLC! Ordem não importada"."\n";
						$this->log_data('batch',$log_name,'O pedido '.$pedido['id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()."! Ordem não importada","E");
						$erro = true; 
						break;
					}
					
					if($cpy == '') { // primeir item 
						$cpy = $prf['company_id']; 
						$store_id = $prf['store_id'];
						echo "Peguei Empresa:".$cpy." e loja:".$store_id."\n";
			    	} 
			    	else 
			    	{ // proximos itens
						if (($cpy != $prf['company_id']) || ($store_id != $prf['store_id'] )) { //empresas diferentes ou lojas diferentes 
						    $msg_cancela = 'O pedido '.$pedido['id'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')!';
							echo 'O pedido '.$pedido['id'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada'."\n";
							$this->log_data('batch',$log_name,'O pedido '.$pedido['id'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada',"E");
							//$erro = true; 
							$cancelar_pedido = true;
						}
					}
					
					// Tempo de crossdocking
					if (isset($prf['crossdocking'])) {  // pega o pior tempo de crossdocking dos produtos
						if (((int) $prf['crossdocking'] + $cross_docking_default) > $cross_docking) {
							$cross_docking = $cross_docking_default + (int) $prf['crossdocking']; 
						};
					}
					
				}
				if ($erro) {
					continue; // teve erro, encerro esta ordem 
				}
				echo 'cross_docking='.$cross_docking."\n";
				
				// Leio a Loja para pegar o service_charge_value
				$store = $this->model_stores->getStoresData($store_id);
				
				// Vejo se já existe para atualizar 
				if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['id'])) {
				    
					$status = $pedido['status'];	
					echo "Ordem Já existe :".$order_exist['id']." status marketplace= ".$status." paid_status=".$order_exist['paid_status']."\n";		
					if (($status=='confirmed') || ($status=='payment_required') || ($status=='payment_in_process') || ($status=='partially_paid') ) { 
						if ($order_exist['paid_status'] == '1') {
							echo "Já está recebido, ignorando\n";
							continue;
						}
						else {
							if (!in_array($order_exist['paid_status'], [95,97])) {
								// Não deveria acontecer. Mensagem de erro.
								$erro ='Pedido '.$pedido['id'].' com status '.$status.' em '.$this->getInt_to().' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
								echo $erro."\n"; 
								$this->log_data('batch',$log_name, $erro,"E");
								return;
							}
							else {
								echo "Já está recebido E PEDI PARA CANCELAR, ignorando\n";
								continue;
							}
						}
					}elseif (($status=='cancelled') || ($status=='invalid')) {
						echo 'staus='.$status."\n";
						$this->ordersmarketplace->cancelOrder($order_exist['id'], false);
						echo "Marcado para cancelamento no Frete rápido\n";
						continue; 
					}elseif ($status=='paid') {
						if ($order_exist['paid_status'] == '1') {
							// Pedido foi aprovado, mudo o status para faturar . Não precisa alterar o estoque
							$this->model_orders->updatePaidStatus($order_exist['id'],3);
							// Se for pago, definir o cross docking a partir da data de pagamento
							$data_pago = date("Y-m-d H:i:s");
							foreach($pedido['payments'] as $parc) {
								if ($parc['status'] == "approved" ) {
									$data_pago =$parc['date_approved'];
								}
							}
							$this->model_orders->updateByOrigin($order_exist['id'], 
								array(
									'data_pago' => $data_pago,
									'data_limite_cross_docking' => $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,'')));
							echo 'Pedido '.$order_exist['id']." marcado para faturamento\n";
							continue ;
						}
						elseif (in_array($order_exist['paid_status'], [95,97])) {
							echo "Já está recebido E PEDI PARA CANCELAR em uma rodada anterior, ignorando\n";
							continue;
						}
						elseif ($order_exist['paid_status'] == 3) {
							echo "Já está recebido,ignorando\n";
							continue; 
							//pedido já recebido. 
						} 
						else {
							//pode acontecer se demorar a rodar o processo que atualiza os status dos pedidos. 
							$erro ='Pedido '.$pedido['id'].' com status '.$status.' na '.$this->getInt_to().' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
							echo $erro."\n"; 
							$this->log_data('batch',$log_name, $erro,"W");
							continue;
						}	
					}else {
						// Não o motivo de receber outros pedidos. vou registrar  
						$erro ='Pedido '.$pedido['id'].' com status '.$status.' na '.$this->getInt_to().' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"W");
						continue;	
					}
				}
				// agora a ordem 
				$orders = Array();
				
				//$orders['freight_seller'] = $store['freight_seller'];
				
				$statusNovo ='';
				$status = $pedido['status'];
				if (($status=='confirmed') || ($status=='payment_required') || ($status=='payment_in_process') || ($status=='partially_paid')) {
					$statusNovo = 1;
				}elseif ($status== "paid") {
				    $statusNovo = 3;
					$orders['data_pago'] = date("Y-m-d H:i:s");
					foreach($pedido['payments'] as $parc) {
						if ($parc['status'] == "approved" ) {
							$orders['data_pago'] =$parc['date_approved'];
						}
					}
					
				}elseif (($status== "cancelled") || ($status== "invalid")) {
					$allowImport = false;
					if (isset($this->argParams['config'])) {
						$allowImport = $this->argParams['config']['allowImportOrderWithStatus'] == 'cancelled';
					}
					if (!$allowImport) {
						// Foi cancelado antes mesmo da gente pegar o pedido.
						$erro = 'Pedido ' . $pedido['id'] . ' com status ' . $status . ' na ' . $this->getInt_to() . ' mas não existe na nossa base';
						echo $erro . "\n";
						$this->log_data('batch', $log_name, $erro, "W");
						continue;
					}
					echo 'Importando pedido ' . $pedido['id'] . ' com status cancelled na ' . $this->getInt_to() . "\n";
				}
				else {
					// Não deveria cair aqui.
					$erro ='Pedido '.$pedido['id'].' com status '.$status.' na '.$this->getInt_to().' mas não existe na nossa base'; 
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"W");
					continue;
				}
				if ($cancelar_pedido) {
					$statusNovo = 97; // já chega cancelado
				}
				// gravo o novo pedido
				// PRIMEIRO INSERE O CLIENTE
				$clients = array();
				//$clients['customer_name'] = $pedido['buyer']['first_name'].' '.$pedido['buyer']['last_name'];
				//$orders['customer_name'] = $clients['customer_name'];	
				//$clients['phone_1'] = '';
				//if (array_key_exists('phone', $pedido['buyer'])) {
				//	$clients['phone_1'] = $pedido['buyer']['phone']['area_code'].' '.$pedido['buyer']['phone']['number'];
				//
				if (is_null($pedido['shipping']['id'])) {
					echo ' ** Este pedido ainda não tem endereços de entrega. ';
					if ($this->company_id == 1) { // Conectala sempre tem endereco de entrega
						echo "Pulando\n";
						continue; 
					}
					var_dump($pedido);
					$clients['customer_address'] = 'COMBINAR ENTREGA COM O COMPRADOR';
					$clients['addr_num'] =  '';
					$clients['addr_compl']  ='';
					$clients['addr_neigh'] = '';
					$clients['addr_city'] = '';
					$clients['addr_uf'] =  '';
					$clients['country'] = '';
					$clients['zipcode'] = '';
					
					$orders['customer_reference'] = '';
					$shipping_cost= 0;
				}
				else {
					// Leio as informações de entrega
					$meli= $this->getkeys($this->company_id,$this->store_id);
					//$params = array('access_token' => $this->getAccessToken());
					$params = array();
					$url = '/shipments/'. $pedido['shipping']['id'];
					$retornoShip = $meli->get($url, $params);
					
					if ($retornoShip['httpCode'] != 200) {
						echo " Erro URL: ". $url. " httpcode=".$retornoShip['httpCode']."\n"; 
						echo " RESPOSTA ".$this->getInt_to().": ".print_r($retornoShip,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retornoShip['httpCode'].' RESPOSTA: '.print_r($retornoShip,true),"E");
						continue; // abandona esta ordem e pega a próxima. 
					}
					$entrega = json_decode(json_encode($retornoShip['body']),true);
					
					$clients['customer_address'] = $entrega['receiver_address']['street_name'];
					$clients['addr_num'] =  $entrega['receiver_address']['street_number'];
					$clients['addr_compl'] =  $entrega['receiver_address']['comment'];
					if (is_null($clients['addr_compl'])) { $clients['addr_compl']  ='';} 
					$clients['addr_neigh'] =  $entrega['receiver_address']['neighborhood']['name'];
					if (is_null($clients['addr_neigh'])) { $clients['addr_neigh'] = '';}
					$clients['addr_city'] = $entrega['receiver_address']['city']['name'];
					$clients['addr_uf'] =  substr($entrega['receiver_address']['state']['id'],-2);
					$clients['country'] = $entrega['receiver_address']['country']['id'];
					$clients['zipcode'] = preg_replace("/[^0-9]/", "", $entrega['receiver_address']['zip_code']);
				
					$orders['customer_reference'] = $entrega['receiver_address']['comment'];
					$shipping_cost = $entrega['shipping_option']['cost'];
				}


				$clients['phone_1'] = '';
				
				if (isset($pedido['buyer']['first_name'])) { // Nem sempre o pedido da lista tem os dados do usuário. 
					$clients['customer_name'] = $pedido['buyer']['first_name'].' '.$pedido['buyer']['last_name'];
					if (array_key_exists('phone', $pedido['buyer'])) {
						$clients['phone_1'] = $pedido['buyer']['phone']['area_code'].' '.$pedido['buyer']['phone']['number'];
					}
					$clients['origin_id'] = $pedido['buyer']['id'];
					$clients['email'] =  $pedido['buyer']['email'];
				}
				else {
					// E ai tenho que ler o pedido novamente 
					$meli= $this->getkeys($this->company_id,$this->store_id);
					//$params = array('access_token' => $this->getAccessToken());
					$params = array();
					$url = '/orders/'. $pedido['id'];
					$order_lido = $meli->get($url, $params);
					if ($order_lido['httpCode'] != 200) {
						echo " Erro URL: ". $url. " httpcode=".$order_lido['httpCode']."\n"; 
						echo " RESPOSTA ".$this->getInt_to().": ".print_r($order_lido,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$order_lido['httpCode'].' RESPOSTA: '.print_r($order_lido,true),"E");
						echo "morri\n";
						die; // abandona esta ordem e pega a próxima. 
					}
					$orderlido = json_decode(json_encode($order_lido['body']),true);
					$clients['customer_name'] = $orderlido['buyer']['first_name'].' '.$orderlido['buyer']['last_name'];
					if (array_key_exists('phone', $orderlido['buyer'])) {
						$clients['phone_1'] = $orderlido['buyer']['phone']['area_code'].' '.$orderlido['buyer']['phone']['number'];
					}
					$clients['origin_id'] = $orderlido['buyer']['id'];
					$clients['email'] =  $orderlido['buyer']['email'];
				}
				
				$orders['customer_name'] = $clients['customer_name'];	
				$orders['customer_address'] = $clients['customer_address'];
				$orders['customer_address_num'] = $clients['addr_num'];
				$orders['customer_address_compl'] = $clients['addr_compl'];
				$orders['customer_address_neigh'] =$clients['addr_neigh'];
				$orders['customer_address_city'] = $clients['addr_city'];
				$orders['customer_address_uf'] = $clients['addr_uf'];
				$orders['customer_address_zip'] = $clients['zipcode'];
				
				//$clients['cpf_cnpj'] = $pedido['buyer']['billing_info']['doc_number'];
				//if (is_null($pedido['buyer']['billing_info']['doc_number'])) {
				//	$clients['cpf_cnpj'] = $pedido['buyer']['id']; 
				//}
				// Leio as informações de faturamento (CPF)
				$meli= $this->getkeys($this->company_id,$this->store_id);
				//$params = array('access_token' => $this->getAccessToken());
				$params = array();
				$url = '/orders/'. $pedido['id'].'/billing_info';
				$retornobilling = $meli->get($url, $params);
				if ($retornobilling['httpCode'] != 200) {
					echo " Erro URL: ". $url. " httpcode=".$retornobilling['httpCode']."\n"; 
					echo " RESPOSTA ".$this->getInt_to().": ".print_r($retornobilling,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retornobilling['httpCode'].' RESPOSTA: '.print_r($retornobilling,true),"E");
					echo "morri\n";
					die; // abandona esta ordem e pega a próxima. 
				}
				$billingInfo = json_decode(json_encode($retornobilling['body']),true);
				$clients['cpf_cnpj'] = $billingInfo['billing_info']['doc_number'];
				
				
				$clients['origin'] = $this->getInt_to();
				//$clients['origin_id'] = $pedido['buyer']['id'];
			///	$clients['email'] =  $pedido['buyer']['email'];
				// campos que não tem no ML 
				$clients['phone_2'] = '';
				if(isset($entrega['receiver_address']['receiver_phone'])) {
					$clients['phone_2'] = $entrega['receiver_address']['receiver_phone'];
				}
				$clients['ie'] = '';
				$clients['rg'] = '';
				
				$client_id = $this->model_clients->insert($clients);
				if ($client_id==false) {
					echo 'Não consegui incluir o cliente'."\n";
					$this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
					return;
				}	
				
				$orders['bill_no'] = $pedido['id'];
				$bill_no = $pedido['id'];
				$orders['numero_marketplace'] = $pedido['id']; // numero do pedido no marketplace 
				$orders['date_time'] = $pedido['date_created'];
				$orders['customer_id'] = $client_id;
				$orders['customer_phone'] = $clients['phone_1'];
				
				$orders['total_order'] = $pedido['total_amount'];   
				$orders['total_ship'] = $shipping_cost;
				$orders['gross_amount'] = $pedido['total_amount'] + $shipping_cost;
				$orders['service_charge_rate'] = $store['service_charge_value'];  
				$orders['service_charge_freight_value'] = $store['service_charge_freight_value'];  
				$orders['service_charge'] = $orders['gross_amount'] * $store['service_charge_value'] / 100;  
				$orders['vat_charge_rate'] = 0; //pegar na tabela de empresa - Não está sendo usado.....
				$orders['vat_charge'] = $orders['gross_amount'] * $orders['vat_charge_rate'] / 100; 

				$orders['discount'] = 0; // não achei no pedido da ML 
				$orders['net_amount'] = $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'];
		
				$orders['paid_status'] = $statusNovo; 
				$orders['company_id'] = $cpy;   
				$orders['store_id'] = $store_id;
				$orders['origin'] = $this->getInt_to();
				$orders['user_id'] = 1;   
				$orders['data_limite_cross_docking'] = $statusNovo != 3 ? null : $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,''); // define cross_docking apenas se for pago

				$order_id = $this->model_orders->insertOrder($orders);
				echo "Inserido:".$order_id."\n";
				if (!$order_id) {
					$this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
					return ;
				}
				
				if ($cancelar_pedido) {
					if (!$this->cancelaPedido($pedido['id'])) {
						die; 
					}
					$data = array(
			            'order_id' => $order_id,
			            'motivo_cancelamento' => $msg_cancela,
			            'data' => date("Y-m-d H:i:s"),
			            'status' => '1',
			            'user_id' => '1'
			        );
			        $this->model_orders->insertPedidosCancelados($data);
					
				}	
				// Itens 
				$quoteid = "";
				$this->model_orders->deleteItem($order_id);  // Nao deve deletar nada pois só pego ordem nova
				$itensIds = array();
				
				// para o verificação do frete
				if ($orders['customer_address_zip'] == '') {  // entrega a combinar entre o seller e o vendedor
					$todos_correios = false; 
					$todos_tipo_volume= false;
					$todos_por_peso = false;
				}
				else {
					$todos_correios = true; 
					$todos_tipo_volume= true;
					$todos_por_peso = true;
					$fr = array();
					$fr['destinatario']['endereco']['cep'] = $orders['customer_address_zip'];
			        $fr['expedidor']['endereco']['cep'] = $store['zipcode'];
					$origem=$this->calculofrete->lerCep($store['zipcode']);
					$destino=$this->calculofrete->lerCep($orders['customer_address_zip']);
				}
				
				foreach($pedido['order_items'] as $item) {
					
					$skumkt = $item['item']['id'];
					$skubling = $item['item']['seller_sku']; 
					$prf= $this->model_ml_ult_envio->getBySku($skubling);
					if (is_null($prf)) {
						var_dump($pedido);
						$msg = 'O pedido '.$pedido['id'].' possui produto '.$skubling.' não encontrado na ml_ult_envio. Ordem não importada';
						echo $msg."\n";
						$this->log_data('batch',$log_name,$msg,"E");
						$this->model_orders->remove($order_id);
						$this->model_clients->remove($client_id);
						return;
					}
					
					$prf['altura'] = $prf['height'];
					$prf['largura'] = $prf['width'];
					$prf['profundidade'] = $prf['length'];
					$prf['peso_bruto'] = $prf['gross_weight'];
					
				    $cpy = $prf['company_id']; 
					$sku = 	$prf['sku'];			
					echo  $skumkt."=".$cpy."=".$sku."\n";
					//$prd = $this->model_products->getProductBySku($sku,$cpy);
				    $prd = $this->model_products->getProductData(0,$prf['prd_id']);
					
					if ($prd['is_kit'] ==0) {
						$items = array();
						$items['skumkt'] = $skumkt;
						$items['order_id'] = $order_id; // ID da order incluida
						$items['product_id'] = $prd['id'];
						$items['sku'] = $sku;
						$variant='';
						if ($prd['has_variants'] != '') {
							$variant = $prf['variant'];	
							$items['sku'] = $prf['sku'];	
						}
						$items['variant'] = $variant;
						$items['name'] = $prd['name'];
						$items['qty'] = $item['quantity'];
						$items['rate'] = $item['unit_price'];
						$items['amount'] = (float)$item['unit_price'] * (float)$item['quantity'];
						$items['discount'] = ((float)$item['full_unit_price'] - (float)$item['unit_price'])* $item['quantity']; 
						$items['company_id'] = $prd['company_id']; 
						$items['store_id'] = $prd['store_id']; 
						$items['un'] = 'Un' ; // Não tem na SkyHub
						$items['pesobruto'] = $prd['peso_bruto'];  // Não tem na SkyHub
						$items['largura'] = $prd['largura']; // Não tem na SkyHub
						$items['altura'] = $prd['altura']; // Não tem na SkyHub
						$items['profundidade'] = $prd['profundidade']; // Não tem na SkyHub
						$items['unmedida'] = 'cm'; // não tem na skyhub
						$items['kit_id'] = null;
						//var_dump($items);
						$item_id = $this->model_orders->insertItem($items);
						if (!$item_id) {
							echo 'Erro ao incluir item. removendo pedido '.$order_id."\n";
							$this->model_orders->remove($order_id);
							$this->model_clients->remove($client_id);
							$this->log_data('batch',$log_name,'Erro ao incluir item. pedido mkt = '.$pedido['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
							return; 
						}
						$itensIds[]= $item_id; 
						if (!$cancelar_pedido) {
							$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant, $order_id);

							$this->model_blingultenvio->reduzEstoque($prf['int_to'],$prd['id'],$items['qty']);
							$this->model_ml_ult_envio->reduzEstoque($prf['id'],$items['qty']);
							
							// vejo se o produto estava com promoção de estoque e vejo se devo terminar 
							$this->model_promotions->updatePromotionByStock($prd['id'],$items['qty'],$item['unit_price']); 
						}
					}
					else { // é um kit,  
						echo "O item é um KIT id=". $prd['id']."\n";
						$productsKit = $this->model_products->getProductsKit($prd['id']);
						foreach ($productsKit as $productKit){
							$prd = $this->model_products->getProductData(0,$productKit['product_id_item']);
							echo "Produto item =".$prd['id']."\n";
							$items = array();
							$items['skumkt'] = $skumkt;
							$items['order_id'] = $order_id; // ID da order incluida
							$items['kit_id'] = $productKit['product_id'];
							$items['product_id'] = $prd['id'];
							$items['sku'] = $prd['sku'];
							$variant = '';
							$items['variant'] = $variant;  // Kit não pega produtos com variantes
							$items['name'] = $prd['name'];
							$items['qty'] = $item['quantity'] * $productKit['qty'];
							$items['rate'] = $productKit['price'] ;  // pego o preço do KIT em vez do item
							$items['amount'] = (float)$items['rate'] * (float)$items['qty'];
							$items['discount'] = 0; // Não sei de quem tirar se houver desconto. 
							$items['company_id'] = $prd['company_id']; 
							$items['store_id'] = $prd['store_id']; 
							$items['un'] = 'Un' ; // Não tem na SkyHub
							$items['pesobruto'] = $prd['peso_bruto'];  // Não tem na SkyHub
							$items['largura'] = $prd['largura']; // Não tem na SkyHub
							$items['altura'] = $prd['altura']; // Não tem na SkyHub
							$items['profundidade'] = $prd['profundidade']; // Não tem na SkyHub
							$items['unmedida'] = 'cm'; // não tem na skyhub
							//var_dump($items);
							$item_id = $this->model_orders->insertItem($items);
							if (!$item_id) {
								echo 'Erro ao incluir item. removendo pedido '.$order_id."\n";
								$this->model_orders->remove($order_id);
								$this->model_clients->remove($client_id);
								$this->log_data('batch',$log_name,'Erro ao incluir item. pedido mkt = '.$pedido['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
								return; 
							}
							$itensIds[]= $item_id; 
							// Acerto o estoque do produto filho
							if (!$cancelar_pedido) {
								$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant, $order_id);
							}
						}
						if (!$cancelar_pedido) {
							$this->model_blingultenvio->reduzEstoque($prf['int_to'],$prd['id'],$item['quantity']);  // reduzo o estoque do produto KIT no Bling_utl_envio
							$this->model_ml_ult_envio->reduzEstoque($prf['id'],$item['quantity']);
						}
					}
					//verificacao do frete
					if ($store['freight_seller'] == 1 && in_array($store['freight_seller_type'], [3, 4])) { // intelipost
						$todos_tipo_volume 	= false;
						$todos_correios 	= false;
						$todos_por_peso 	= false;
					}
					$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 
					if ($todos_tipo_volume) { // se é tipo_volume não pode ser correios e não procisa consultar os correios	
						$todos_correios = false; 
					}
					else { // se não é tipo volumes, não precisa consultar o tipo_volumes pois já não achou antes 
						$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
					}
					$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
					$vl = Array ( 
						'tipo' => $prf['tipo_volume_codigo'],     
			            'sku' => $skumkt,
			            'quantidade' => $item['quantity'],	           
			            'altura' => (float) $prf['width'] / 100,
					    'largura' => (float) $prf['height'] /100,
					    'comprimento' => (float) $prf['length'] /100,
					    'peso' => (float) $prf['gross_weight'],  
			            'valor' => (float) $item['unit_price']* $item['quantity'],
			            'volumes_produto' => 1,
			            'consolidar' => false,
			            'sobreposto' => false,
			            'tombar' => false);
		            $fr['volumes'][] = $vl;
				}

				$this->calculofrete->updateShipCompanyPreview($order_id);

				// Gravando o log do pedido
				$data_log = array(
					'int_to' 	=>$this->getInt_to(),
					'order_id'	=> $order_id,
					'received'	=> json_encode($pedido)
				);
				$this->model_log_integration_order_marketplace->create($data_log);

				/*
				 * [PEDRO HENRIQUE - 18/06/2021] Lógica para previsão da transportadora,
				 * método e prazo de envio, foi migrada para o método updateShipCompanyPreview,
				 * dentro da biblioteca CalculoFrete
				 *
				if ($store['freight_seller'] == 1 && in_array($store['freight_seller_type'], [3, 4])) { // intelipost

					if ($store['freight_seller_type'] == 3) $tokenIntelipost = $store['freight_seller_end_point'];
					else {
						$querySettings   = $this->db->query('SELECT * FROM settings WHERE name = ?', array('token_intelipost_sellercenter'));
						$rowSettings     = $querySettings->row_array();
						$tokenIntelipost = $rowSettings['value'];
					}

					$responseIntelipost = $this->calculofrete->getPriceAndDeadlineIntelipost($fr, $cross_docking, $orders['customer_address_zip'], $tokenIntelipost);
					if ($responseIntelipost !== false)
						$this->model_orders->setShipCompanyPreview($order_id, 'Intelipost', 'Intelipost', $responseIntelipost['deadline']);

				} elseif ($store['freight_seller'] == 1 && $store['freight_seller_type'] == 1) {
					$endpointFreight = $store['freight_seller_end_point'];
					$precode = $this->calculofrete->getQuotePreCodeOrder($endpointFreight, $fr, $cross_docking, $orders['customer_address_zip']);
					if ($precode === false)
						$this->model_orders->setShipCompanyPreview($order_id, 'Precode', 'Precode', 10);
					else
						$this->model_orders->setShipCompanyPreview($order_id, $precode['provider'], 'Precode', $precode['deadline']);
				}
				else {
					if ($todos_correios) {
						$resposta = $this->calculofrete->calculaCorreiosNovo($fr,$origem,$destino);
					}elseif ($todos_tipo_volume) {
						$resposta = $this->calculofrete->calculaTipoVolume($fr,$origem,$destino);
					}elseif ($todos_por_peso) {
						$resposta = $this->calculofrete->calculaPorPeso($fr,$origem,$destino);
					}	
					else {
						$resposta = array(
							'servicos' => array(
								'FR' => array ('empresa'=>'FreteRápido','servico'=>'A contratar', 'preco'=>0,'prazo'=>0,),
							),
						);
					}
					if (array_key_exists('erro',$resposta )) {
						echo $resposta['erro']."\n"; 
						$this->log_data('batch',$log_name, $resposta['erro'],"W");
						continue;	
					}
					if (!array_key_exists('servicos',$resposta )) {
						$erro = $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
						echo $resposta['erro']."\n"; 
						$this->log_data('batch',$log_name, $resposta['erro'],"W");
						continue;	
					}
					if (empty($resposta['servicos'] )) {
						$erro = $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
						echo $resposta['erro']."\n"; 
						$this->log_data('batch',$log_name, $resposta['erro'],"W");
						continue;	
					}	
					$key = key($resposta['servicos']); 
					$transportadora = $resposta['servicos'][$key]['empresa']; 
					$servico =  $resposta['servicos'][$key]['servico'];
					$prazo = $resposta['servicos'][$key]['prazo']; 
					$this->model_orders->setShipCompanyPreview($order_id,$transportadora,$servico,$prazo);
				}
				*/
				
				$parcelas = $pedido['payments'];
				$i = 0;
				if (is_null($parc['date_approved'])) {
					$parc['date_approved'] = ''; 
				}
				foreach($parcelas as $parc) {
					$i++;
					$parcs['parcela'] 			= $i;
					$parcs['order_id'] 			= $order_id; 
					$parcs['bill_no'] 			= $bill_no;
					$parcs['data_vencto'] 		= $parc['date_approved'];
					$parcs['valor'] 			= $parc['total_paid_amount'];
					$parcs['forma_id']	 		= $parc['payment_method_id'];
					$parcs['forma_desc'] 		= $parc['payment_type'];
					$parcs['forma_cf'] 			= ''; // nao tem na skyhub 
					if (is_null($parcs['data_vencto'])) {
						$parcs['data_vencto'] = '';
					}
					//campos novoas abaixo
					//var_dump($parcs);
					if ($parc['status'] != 'cancelled') {
						$parcs_id = $this->model_orders->insertParcels($parcs);
						if (!$parcs_id) {
							$this->log_data('batch',$log_name,'Erro ao incluir pagamento ',"E");
							return; 
						}
					}
					
				}
				
			}	
			
			if ($paging['total'] < $paging['limit']) {
				echo "acabou \n";
				break;
			}
			$offset = $paging['offset'] + $paging['limit'];
			
		}
		
	}
	
	function cancelaPedido($pedido)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo 'Cancelando pedido ='.$pedido."\n"; 
		
		$motivo = 'Falta de produto';

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
		$url = 'orders/'.$pedido.'/feedback';
		
		$retorno = $meli->post($url, $cancel, $params);
		
	    //var_dump($resp); 

		if (!($retorno['httpCode']=="201") )  {  // created
			echo 'Erro na respota do '.$this->getInt_to().' httpcode='.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true)."\n"; 
			$this->log_data('batch',$log_name, 'ERRO ao cancelar no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true).' DADOS ENVIADOS:'.print_r($cancel,true),"E");
			return false;
		}
		return true;
	}

}

?>
