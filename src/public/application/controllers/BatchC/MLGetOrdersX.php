<?php
/*
 
Baixa os pedidos que chegaram no Carrefour

*/   
require APPPATH . "controllers/Meli.php";

class MLGetOrdersX extends BatchBackground_Controller {
	
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
		$this->load->model('model_orders');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_products');
		$this->load->model('model_freights');
		
		/*
		$this->load->model('model_orders_test','model_orders');
		$this->load->model('model_stores');
		$this->load->model('model_clients_test','model_clients');
		$this->load->model('model_blingultenvio_test','model_blingultenvio');
		$this->load->model('model_promotions_test','model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_products_test', 'model_products');
		 */
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
		$this->getkeys(1,0);
		$this->getorders();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		
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
		
		UPDATE integrations
	SET auth_data='{"seller":"621913621","client_id":"191506436626890","client_secret":"LrmTu6LdGd5ZbJ6LaGhlfHetjRhmmSvI","access_token":"APP_USR-191506436626890-081112-0b6f5d7a06ab686158bdb203e362a94e-621913621","refresh_token":"TG-5f2d71254df2e300068d5bea-621913621","date_refresh":1597171585}'
	WHERE id=54;
		 * 
		 * 
		 * */
		
		$meli = new Meli($this->getClientId(),$this->getClientSecret(),$this->getAccessToken(),$this->getRefreshToken());
		echo " renovar em ".date('d/m/Y H:i:s',$this->getDateRefresh()).' hora atual = '.date('d/m/Y H:i:s'). "\n"; 
		if ($this->getDateRefresh()+1 < time()) {	
			$user = $meli->refreshAccessToken();
			var_dump($user);
			if ($user["httpCode"] == 400) {
				$user = $meli->authorize($this->getRefreshToken(), 'https://www.mercadolivre.com.br');
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
			$authdata=array(
				'client_id' =>$this->getClientId(),
				'client_secret' =>$this->getClientSecret(),
				'access_token' =>$this->getAccessToken(),
				'refresh_token' =>$this->getRefreshToken(),
				'date_refresh' =>$this->getDateRefresh(),
				'seller' => $this->getSeller(),
			);
			$integration = $this->model_integrations->updateIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id,json_encode($authdata));	
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

		$meli= $this->getkeys(1,0);
		$params = array(
			'seller' => $this->getSeller(),  // usuario de teste "621913621"
			'offset' => $offset,
			'access_token' => $this->getAccessToken()
		);
		$url = '/orders/4158295783';
		$retorno = $meli->get($url, $params);
		
		if ($retorno['httpCode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpCode'].' RESPOSTA: '.print_r($retorno,true),"E");
			return;
		}
		$pedido = json_decode(json_encode($retorno['body']),true);
		
		// var_dump($pedido);
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
			
			$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND (int_to = ? OR int_to = ?)";
			$query = $this->db->query($sql, array($sku_item,$this->getInt_to(),'MLC'));
			$prf = $query->row_array();
			if (empty($prf)) {
				if (strrpos($sku_item, "-") !=0) {
					$sku_item = substr($item['seller_sku'], 0, strrpos($item['seller_sku'], "-"));
					$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND (int_to = ? OR int_to = ?)";
					$query = $this->db->query($sql, array($sku_item,$this->getInt_to(),'MLC'));
					$prf = $query->row_array();
				}
				if (empty($prf))  {
					var_dump($pedido);
					echo 'O pedido '.$pedido['id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()." ou do MLC! Ordem não importada"."\n";
					$this->log_data('batch',$log_name,'O pedido '.$pedido['id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()." ou do MLC! Ordem não importada","E");
					$erro = true; 
					break;
				}
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
		//var_dump($prf);
		
		// Leio a Loja para pegar o service_charge_value
		$store = $this->model_stores->getStoresData($store_id);
		
		// Vejo se já existe para atualizar 
		if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['id'])) {
		    
			$status = $pedido['status'];	
			echo "Ordem Já existe :".$order_exist['id']." status marketplace= ".$status." paid_status=".$order_exist['paid_status']."\n";		
			$orders = $order_exist;
		}
		else {
			$orders=array();
		}
		// agora a ordem 
		
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
	        // Foi cancelado antes mesmo da gente pegar o pedido.
			$erro ='Pedido '.$pedido['id'].' com status '.$status.' na '.$this->getInt_to().' mas não existe na nossa base'; 
			echo $erro."\n"; 
			$this->log_data('batch',$log_name, $erro,"W");
			continue;
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
		$clients['customer_name'] = $pedido['buyer']['first_name'].' '.$pedido['buyer']['last_name'];
		$orders['customer_name'] = $clients['customer_name'];	
		$clients['phone_1'] = '';
		if (array_key_exists('phone', $pedido['buyer'])) {
			$clients['phone_1'] = $pedido['buyer']['phone']['area_code'].' '.$pedido['buyer']['phone']['number'];
		}
		if (is_null($pedido['shipping']['id'])) {
			echo ' este pedido não tem endereços de entrega. Pulando'."\n";

			continue; 
		}
		
		// Leio as informações de entrega
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
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
			
		$orders['customer_address'] = $clients['customer_address'];
		$orders['customer_address_num'] = $clients['addr_num'];
		$orders['customer_address_compl'] = $clients['addr_compl'];
		$orders['customer_address_neigh'] =$clients['addr_neigh'];
		$orders['customer_address_city'] = $clients['addr_city'];
		$orders['customer_address_uf'] = $clients['addr_uf'];
		$orders['customer_address_zip'] = $clients['zipcode'];
		$orders['customer_reference'] = $entrega['receiver_address']['comment'];
		$clients['cpf_cnpj'] = $pedido['buyer']['billing_info']['doc_number'];
		
		$clients['origin'] = $this->getInt_to();
		$clients['origin_id'] = $pedido['buyer']['id'];
		$clients['email'] =  $pedido['buyer']['email'];
		// campos que não tem no ML 
		$clients['phone_2'] = '';
		$clients['ie'] = '';
		$clients['rg'] = '';
		
		if (!$order_exist) {
			$client_id = $this->model_clients->insert($clients);
		}
		else {
			$client_id = $order_exist['customer_id'];
		}
		
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
		$orders['total_ship'] = $entrega['shipping_option']['cost'];
		$orders['gross_amount'] = $pedido['total_amount'] + $entrega['shipping_option']['cost'];
		$orders['service_charge_rate'] = $store['service_charge_value'];  
		$orders['service_charge'] = $orders['gross_amount'] * $store['service_charge_value'] / 100;  
		$orders['vat_charge_rate'] = 0; //pegar na tabela de empresa - Não está sendo usado.....
		$orders['vat_charge'] = $orders['gross_amount'] * $orders['vat_charge_rate'] / 100; //pegar na tabela de empresa - Não está sendo usado.....

		$orders['discount'] = 0; // não achei no pedido da ML 
		$orders['net_amount'] = $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'];

		$orders['paid_status'] = $statusNovo; 
		$orders['company_id'] = $cpy;   
		$orders['store_id'] = $store_id;
		$orders['origin'] = $this->getInt_to();
		$orders['user_id'] = 1;   
		$orders['data_limite_cross_docking'] = $statusNovo != 3 ? null : $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,''); // define cross_docking apenas se for pago
		
		if (!$order_exist) {
			$order_id = $this->model_orders->insertOrder($orders);
			echo "Inserido:".$order_id."\n";
		}
		else {
			$order_id = $order_exist['id'];
		}
					
		
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
		$todos_correios = true; 
		$todos_tipo_volume= true;
		$todos_por_peso = true;
		$fr = array();
		$fr['destinatario']['endereco']['cep'] = $orders['customer_address_zip'];
        $fr['expedidor']['endereco']['cep'] = $store['zipcode'];
		$origem=$this->calculofrete->lerCep($store['zipcode']);
		$destino=$this->calculofrete->lerCep($orders['customer_address_zip']);
		
		foreach($pedido['order_items'] as $item) {
			
			// mutreta apagar depois de 07/09/2020- vendeu um item que não consigo mais atualizar 
			if ($pedido['id']== '4004885508') {
				$item['item']['seller_sku'] = 'P82820S196ML-3';
				$item['item']['id'] = 'MLB1626052075';
			}
			if ($pedido['id']== '4001036416') {
				$item['item']['seller_sku'] = 'P82820S196ML-0';
				$item['item']['id'] = 'MLB1626052075';
			}
			
			
			$skumkt = $item['item']['id'];
			$skubling = $item['item']['seller_sku']; 
			$sql = "SELECT * FROM bling_ult_envio WHERE skumkt = ? AND (int_to = ? OR int_to = ?)";
			$query = $this->db->query($sql, array($skumkt, $this->getInt_to(),'MLC'));
			$prf = $query->row_array();
			if (is_null($prf)) {
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND (int_to = ? OR int_to = ?)";
				$query = $this->db->query($sql, array($skubling, $this->getInt_to(),'MLC'));
				$prf = $query->row_array();
			}
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
					$variant = substr($item['item']['seller_sku'],strrpos($item['item']['seller_sku'], "-")+1);	
					$items['sku'] = $sku.'-'.$variant;
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
				}
			}
			//verificacao do frete 
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
	            'altura' => (float) $prf['altura'] / 100,
			    'largura' => (float) $prf['largura'] /100,
			    'comprimento' => (float) $prf['profundidade'] /100,
			    'peso' => (float) $prf['peso_bruto'],  
	            'valor' => (float) $item['unit_price']* $item['quantity'],
	            'volumes_produto' => 1,
	            'consolidar' => false,
	            'sobreposto' => false,
	            'tombar' => false);
            $fr['volumes'][] = $vl;
		}


		$this->calculofrete->updateShipCompanyPreview($order_id);

		/*
		 * [PEDRO HENRIQUE - 18/06/2021] Lógica para previsão da transportadora,
		 * método e prazo de envio, foi migrada para o método updateShipCompanyPreview,
		 * dentro da biblioteca CalculoFrete
		 *
		if ($store['freight_seller'] == 1) {
			$this->model_orders->setShipCompanyPreview($order_id,'Logística Própria','Logística Própria',7);
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
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		
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
