<?php
/*
 
Baixa os pedidos que chegaram na Mirakl

*/   
abstract class GetOrders extends BatchBackground_Controller {
	
	var $int_to='';
	var $apikey='';
	var $site='';
	var $model_last_post;

	abstract protected function lastPostModel(); 
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' 	=> 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_products');
		$this->load->model('model_freights');
		$this->load->model('model_settings');
		$this->load->model('model_log_integration_order_marketplace');
		$this->load->library('ordersMarketplace');

    }
	
	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setApikey($apikey) {
		$this->apikey = $apikey;
	}
	function getApikey() {
		return $this->apikey;
	}
	function setSite($site) {
		$this->site = $site;
	}
	function getSite() {
		return $this->site;
	}
	
	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setSite($api_keys['site']);
	}

    function getorders()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->load->library('calculoFrete');
		
		$order_state_codes="WAITING_ACCEPTANCE,WAITING_DEBIT_PAYMENT,SHIPPING,CANCELED,SHIPPED"; 
		$start_update_date= date("Y-m-d\TH:i:s",time() - 60 * 60 * 24*7);
		$end_update_date= date("Y-m-d\TH:i:s",time() );
		
		$offset = 0;
		while (true) {
			$url = 'https://'.$this->getSite().'/api/orders?max=100&offset='.$offset.'&start_update_date='.$start_update_date.'&end_update_date='.$end_update_date.'&order_state_codes='.$order_state_codes;
			echo 'url='.$url."\n";
			$offset += 10;
			$retorno = $this->getMirakl($url, $this->getApikey());
			
			if ($retorno['httpcode'] == 429) {
				sleep(60);
				$retorno = $this->getMirakl($url, $this->getApikey());
			}
			
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
				return;
			}
			
			$pedidos = json_decode($retorno['content'],true);
			echo "Total de pedidos = ".count($pedidos['orders'])."\n"; 
			if (count($pedidos['orders']) == 0 ) {
				// acabou a paginação
				break;
			}
			foreach ($pedidos['orders'] as $pedido) {
				
				echo "------------------------------------------------------------------------\n";	
				echo "Pedido = ".$pedido['order_id']."\n";
				//var_dump($pedido);

				// Verifico se todos os skus estão certos e são das mesmas empresas 
				$cpy ='';
				$store_id = '';
				$erro = false;
				$cross_docking_default = 2;
				$cross_docking = $cross_docking_default; 
				$cancelar_pedido = false; 
				foreach($pedido['order_lines'] as $item) {
					$sku_item = $item['offer_sku'];

					$prf = $this->model_last_post->getDataBySkuLocalIntto($sku_item, $this->int_to);
					if (empty($prf)) {
						echo 'O pedido '.$pedido['order_id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()."! Ordem não importada"."\n";
						$this->log_data('batch',$log_name,'O pedido '.$pedido['order_id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()."! Ordem não importada","E");
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
						    $msg_cancela = 'O pedido '.$pedido['order_id'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')!';
							echo 'O pedido '.$pedido['order_id'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada'."\n";
							$this->log_data('batch',$log_name,'O pedido '.$pedido['order_id'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada',"E");
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
				if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['order_id'])) {
				    
					$status = $pedido['order_state'];	
					echo "Ordem Já existe :".$order_exist['id']." status marketplace= ".$pedido['order_state']." paid_status=".$order_exist['paid_status']."\n";		
					
					// gravo o status do pedido no marketplace
					$this->model_orders->updateByOrigin($order_exist['id'],array('status_mkt'=> $status));
										
					if (($pedido['has_incident']==1) && (!$order_exist['has_incident'])) { // apareceu um incidente novo
						echo " NOVO INCIDENTE \n";
						$this->model_orders->setIncidentOnOrder($order_exist['id'],true); 
					} 
					if (($pedido['has_incident']!=1) && ($order_exist['has_incident'])) { // O incidente foi resolvido
						echo " incidente resolvido \n";
						$this->model_orders->setIncidentOnOrder($pedido['order_id'],false); 
					}
	
					if (($status=='WAITING_ACCEPTANCE')  || ($status=='WAITING_DEBIT_PAYMENT') ) { 
						if ($order_exist['paid_status'] == '1') {
							echo "Já está recebido, ignorando\n";
							continue;
						}
						else {
							if (!in_array($order_exist['paid_status'], [95,97])) {
								// Não deveria acontecer. Mensagem de erro.
								$erro ='Pedido '.$pedido['order_id'].' com status '.$status.' em '.$this->getInt_to().' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
								echo $erro."\n"; 
								$this->log_data('batch',$log_name, $erro,"E");
								return;
							}
							else {
								echo "Já está recebido E PEDI PARA CANCELAR, ignorando\n";
								continue;
							}
						}
					}elseif ($status=='CANCELED' || $status=='REFUSED') {
						echo 'staus='.$status."\n";
						$this->ordersmarketplace->cancelOrder($order_exist['id'], false);
						echo "Marcado para cancelamento\n";
						continue; 
					}elseif ($status=='SHIPPING') {
						if ($order_exist['paid_status'] == '1') {
							// Pedido foi aprovado, mudo o status para faturar . Não precisa alterar o estoque
							$this->model_orders->updatePaidStatus($order_exist['id'],3);
							// Se for pago, definir o cross docking a partir da data de pagamento
							$this->model_orders->updateByOrigin($order_exist['id'], array('data_pago'=>date("Y-m-d H:i:s"),'data_limite_cross_docking' => $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,'')));
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
							$erro ='Pedido '.$pedido['order_id'].' com status '.$status.' na '.$this->getInt_to().' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
							echo $erro."\n"; 
							$this->log_data('batch',$log_name, $erro,"W");
							continue;
						}
					}elseif ($status=='SHIPPED') {
						if ($order_exist['paid_status'] == 5) { 
							echo "Já está recebido,ignorando\n";
							continue; 
							//pedido já recebido. 
						} 
						else {
							//pode acontecer se demorar a rodar o processo que atualiza os status dos pedidos. 
							$erro ='Pedido '.$pedido['order_id'].' com status '.$status.' na '.$this->getInt_to().' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
							echo $erro."\n"; 
							$this->log_data('batch',$log_name, $erro,"W");
							continue;
						}	
					}else {
						// Não o motivo de receber outros pedidos. vou registrar  
						$erro ='Pedido '.$pedido['order_id'].' com status '.$status.' na '.$this->getInt_to().' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"W");
						continue;	
					}	
				} 
				var_dump($pedido);
				// agora a ordem 
				$orders = Array();
				//$orders['freight_seller'] = $store['freight_seller'];
				
				$statusNovo ='';
				$status = $pedido['order_state'];
				if ($status== "WAITING_ACCEPTANCE") {
					$statusNovo = 1;
				}elseif ($status== "WAITING_DEBIT_PAYMENT") { 
				    $statusNovo = 1;
				}elseif ($status== "SHIPPING") {
				    $statusNovo = 3;
					$orders['data_pago'] = date("Y-m-d H:i:s");
				}elseif ($status== "CANCELED") {
			        // Foi cancelado antes mesmo da gente pegar o pedido.
					$erro ='Pedido '.$pedido['order_id'].' com status '.$status.' na '.$this->getInt_to().' mas não existe na nossa base'; 
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"W");
					continue;
				}
				else {
					// Não deveria cair aqui.
					$erro ='Pedido '.$pedido['order_id'].' com status '.$status.' na '.$this->getInt_to().' mas não existe na nossa base'; 
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
				$clients['customer_name'] = $pedido['customer']['firstname'].' '.$pedido['customer']['lastname'];
				$orders['customer_name'] = $clients['customer_name'];	
				$clients['phone_1'] = '';
				/*** Segundo a carredour usar sempre o Order_Additional_fields
				
				*/
				if ($this->int_to == 'CAR') { // Segundo a carredour usar sempre o Order_Additional_fields
					$orders['customer_address_compl'] = '';
					foreach ($pedido['order_additional_fields'] as $campo) {
						if ($campo['code'] == 'customer-cpf') {
							$clients['cpf_cnpj'] = $campo['value'];
						}elseif ($campo['code'] == 'delivery-address') {
							$orders['customer_address']  = $campo['value'];
						}elseif ($campo['code'] == 'delivery-complement-address') {
							$orders['customer_address_compl'] = $campo['value'];
						}elseif ($campo['code'] == 'delivery-district-address') {
							$orders['customer_address_neigh'] = $campo['value'];
						}elseif ($campo['code'] == 'delivery-number-address') {
							$orders['customer_address_num'] = $campo['value'];
						}elseif ($campo['code'] == 'delivery-postal-code') {
							$orders['customer_address_zip'] = preg_replace("/[^0-9]/", "",$campo['value']);
						}elseif ($campo['code'] == 'delivery-state') {
							$orders['customer_address_uf'] = $campo['value'];
						}elseif ($campo['code'] == 'delivery-town') {
							$orders['customer_address_city']  = $campo['value'];
						}elseif ($campo['code'] == 'delivery-reference') {
							$orders['customer_reference']  = $campo['value'];
						}elseif ($campo['code'] == 'tel-number') {
							$clients['phone_1'] = $campo['value'];
						}
					}
					if ((!array_key_exists('customer_address', $orders)) || (!array_key_exists('customer_address_num', $orders)) || (!array_key_exists('customer_address_zip', $orders))) {
						$erro ='Pedido '.$pedido['order_id'].' não possui endereço do cliente. Ignorando'; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"E");
						continue;
					}
					$clients['customer_address'] 	= $orders['customer_address'];
					$clients['addr_num'] 			= $orders['customer_address_num'];
					$clients['addr_compl'] 			= $orders['customer_address_compl'];
					$clients['addr_neigh'] 			= $orders['customer_address_neigh'];
					$clients['addr_city'] 			= $orders['customer_address_city']; 
					$clients['addr_uf'] 			= $orders['customer_address_uf'];
					$clients['country'] 			= 'BR';
					$clients['zipcode'] 			= preg_replace("/[^0-9]/", "",$orders['customer_address_zip']);
				}
				elseif ($this->int_to == 'GPA') {
					foreach ($pedido['order_additional_fields'] as $campo) {
						if ($campo['code'] == 'cpf') {
							$clients['cpf_cnpj'] = $campo['value'];
						}
					}
					if (!is_null($pedido['customer']['shipping_address'])) {
						$rua = $pedido['customer']['shipping_address']['street_1'];
						$num = '';
						if (strpos($rua,',')) {
							$num = substr($rua,strpos($rua,',')+1);
							$rua = substr($rua,0,strpos($rua,','));
						}
						$orders['customer_address'] 		= $rua;
						$orders['customer_address_num'] 	= $num;
						$orders['customer_address_compl'] 	= $pedido['customer']['shipping_address']['lastname'];
						$orders['customer_address_neigh'] 	= $pedido['customer']['shipping_address']['street_2'];
						$orders['customer_address_city'] 	= $pedido['customer']['shipping_address']['city'];
						$orders['customer_address_uf'] 		= $pedido['customer']['shipping_address']['state'];
						$orders['customer_address_city'] 	= $pedido['customer']['shipping_address']['country'];
						$orders['customer_address_zip'] 	= preg_replace("/[^0-9]/", "",$pedido['customer']['shipping_address']['zip_code']);
						$orders['customer_name'] 			= $pedido['customer']['firstname'].' '.$pedido['customer']['lastname'];
						$orders['customer_phone'] 			= $pedido['customer']['shipping_address']['phone'];
						
					}
					else {
						$erro ='Pedido '.$pedido['order_id'].' não possui endereço de entrega. Ignorando'; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"E");
						continue;
					}
					if (!is_null($pedido['customer']['billing_address'])) {
						$rua = $pedido['customer']['billing_address']['street_1'];
						$num = '';
						if (strpos($rua,',')) {
							$num = substr($rua,strpos($rua,',')+1);
							$rua = substr($rua,0,strpos($rua,','));
						}
						$clients['customer_address'] 	= $rua;
						$clients['addr_num'] 			= $num;
						$clients['addr_compl'] 			= $pedido['customer']['billing_address']['lastname'];
						$clients['addr_neigh'] 			= $pedido['customer']['billing_address']['street_2'];
						$clients['addr_city'] 			= $pedido['customer']['billing_address']['city'];
						$clients['addr_uf'] 			= $pedido['customer']['billing_address']['state'];
						$clients['country'] 			= 'BR';
						$clients['zipcode'] 			= preg_replace("/[^0-9]/", "",$pedido['customer']['billing_address']['zip_code']);
						$clients['phone_1'] 			= $pedido['customer']['billing_address']['phone'];
					}
					else {
						$clients['customer_address'] 	= $orders['customer_address'];
						$clients['addr_num'] 			= $orders['customer_address_num'];
						$clients['addr_compl'] 			= $orders['customer_address_compl'];
						$clients['addr_neigh'] 			= $orders['customer_address_neigh'];
						$clients['addr_city'] 			= $orders['customer_address_city']; 
						$clients['addr_uf'] 			= $orders['customer_address_uf'];
						$clients['country'] 			= 'BR';
						$clients['zipcode'] 			= preg_replace("/[^0-9]/", "",$orders['customer_address_zip']);
					}

				}
				else {
					echo "Não sei o que fazer aqui para este ".$this->int_to ."\n";
					die;
				}
				
				$clients['origin'] 		= $this->getInt_to();
				$clients['origin_id'] 	= $pedido['customer']['customer_id'];
				$clients['email'] 		=  $pedido['customer_notification_email'];

				// campos que não tem  
				$clients['phone_2'] 	= '';
				$clients['ie'] 			= '';
				$clients['rg']			= '';
				
				// var_dump($clients);

				$client_id = $this->model_clients->insert($clients);
				if ($client_id==false) {
					echo 'Não consegui incluir o cliente'."\n";
					$this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
					return;
				}
				
				$orders['bill_no'] = $pedido['order_id'];
				$bill_no = $pedido['order_id'];
				$orders['numero_marketplace'] = $pedido['order_id']; // numero do pedido no marketplace 

				$orders['date_time'] = gmdate('Y-m-d H:i:s', strtotime( $pedido['created_date'])+date("Z"));  // Acerta o fuso horário
				
				$orders['customer_id'] = $client_id;
				$orders['total_order'] = $pedido['price'] ;
				
				$orders['service_charge_rate'] = $store['service_charge_value'];
				$orders['service_charge_freight_value'] = $store['service_charge_freight_value'];  
				$orders['service_charge'] = $pedido['total_price'] * $store['service_charge_value'] / 100;  
				$orders['vat_charge_rate'] = 0; //pegar na tabela de empresa - Não está sendo usado.....
				$orders['vat_charge'] = $pedido['total_price'] * $orders['vat_charge_rate'] / 100; //pegar na tabela de empresa - Não está sendo usado.....
				$orders['gross_amount'] = $pedido['total_price'];
				$orders['total_ship'] = $pedido['shipping_price'];
				
				$orders['discount'] = $pedido['promotions']['total_deduced_amount']; 
				$orders['net_amount'] = $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'];
		
				$orders['paid_status'] = $statusNovo; 
				$orders['company_id'] = $cpy;   
				$orders['store_id'] = $store_id;
				$orders['origin'] = $this->getInt_to();
				$orders['user_id'] = 1;
				if ((isset($pedido['shipping_deadline'])) && (!is_null($pedido['shipping_deadline']))) {
					$orders['data_limite_cross_docking'] = gmdate('Y-m-d H:i:s', strtotime( $pedido['shipping_deadline'])+date("Z"));  // Acerta o fuso horário
				}
				else {
					$orders['data_limite_cross_docking'] = $statusNovo != 3 ? null : $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,''); // define cross_docking apenas se for pago
				}
				$order_id = $this->model_orders->insertOrder($orders);
				echo "Inserido:".$order_id."\n";
				if (!$order_id) {
					$this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
					return ;
				}
				
				if ($cancelar_pedido) {
					$this->cancelaPedido($pedido['order_id']);
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
				
				foreach($pedido['order_lines'] as $item) {
					$skulocal = $item['offer_sku'];
					$prf = $this->model_last_post->getDataBySkuLocalIntto($skulocal, $this->int_to);
	
				    $cpy = $prf['company_id']; 
					$sku = 	$prf['sku'];
					$skuseller = $prf['sku'];
					echo  $item['offer_sku']."=".$cpy."=".$sku."\n";
					$prd = $this->model_products->getProductData(0,$prf['prd_id']);
					
					if ($prd['is_kit'] ==0) {
						$variant='';
						$sku_item = $sku;
						if ($prd['has_variants'] != '') {
							$variant = substr($item['offer_sku'],strrpos($item['offer_sku'], "-")+1);	
							$sku_item = $this->getSkuVariant($prd['id'], $variant, $sku.'-'.$variant); 
						}
						$items = array(
							'skumkt' 		=> $item['offer_sku'],
							'order_id' 		=> $order_id,  // ID da order incluida
							'product_id' 	=> $prd['id'],
							'sku' 			=> $sku_item,
							'variant' 		=> $variant,
							'name' 			=> $prd['name'],
							'qty' 			=> $item['quantity'],
							'rate' 			=> $item['price_unit'],
							'amount' 		=> (float)$item['price_unit'] * (float)$item['quantity'],
							'discount' 		=> (float)$item['price'] - ((float)$item['price_unit'] * (float)$item['quantity']), 
							'company_id' 	=> $prd['company_id'], 
							'store_id' 		=> $prd['store_id'],
							'un' 			=> 'Un', // Não tem na Mirakl
							'pesobruto' 	=> $prd['peso_bruto'],  // Não tem na SkyHub
							'largura' 		=> $prd['largura'], // Não tem na SkyHub
							'altura' 		=> $prd['altura'], // Não tem na SkyHub
							'profundidade' 	=> $prd['profundidade'], // Não tem na SkyHub
							'unmedida' 		=> 'cm', // não tem na skyhub
							'kit_id' 		=> null
						);
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
							$this->model_last_post->reduzEstoque($prf['id'],$items['qty']);
							
							// vejo se o produto estava com promoção de estoque e vejo se devo terminar 
							$this->model_promotions->updatePromotionByStock($prd['id'],$items['qty'],$item['price_unit']); 
						}
					}
					else { // é um kit,  
						echo "O item é um KIT id=". $prd['id']."\n";
						$productsKit = $this->model_products->getProductsKit($prd['id']);
						foreach ($productsKit as $productKit){
							$prd = $this->model_products->getProductData(0,$productKit['product_id_item']);
							echo "Produto item =".$prd['id']."\n";
							$variant = '';
							$items = array(
								'order_id' 		=> $order_id, // ID da order incluida
								'skumkt' 		=> $item['offer_sku'],
								'kit_id' 		=> $productKit['product_id'],
								'product_id' 	=> $prd['id'],
								'sku' 			=> $prd['sku'],
								'variant' 		=> $variant,  // Kit não pega produtos com variantes
								'name' 			=> $prd['name'],
								'qty' 			=> $item['quantity'] * $productKit['qty'],
								'rate' 			=> $productKit['price'],  // pego o preço do KIT em vez do item
								'amount' 		=> (float)$productKit['price'] * (float)($item['quantity'] * $productKit['qty']),
								'discount' 		=> 0, // Não sei de quem tirar se houver desconto. 
								'company_id' 	=> $prd['company_id'], 
								'store_id' 		=> $prd['store_id'], 
								'un' 			=> 'Un', // Não tem na Mirakl
								'pesobruto' 	=> $prd['peso_bruto'],  // Não tem na SkyHub
								'largura' 		=> $prd['largura'], // Não tem na SkyHub
								'altura' 		=> $prd['altura'], // Não tem na SkyHub
								'profundidade' 	=> $prd['profundidade'], // Não tem na SkyHub
								'unmedida' 		=>'cm'  // não tem na skyhub
							);
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
								$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant,$order_id);
							}
						}
						if (!$cancelar_pedido) {
							$this->model_last_post->reduzEstoque($prf['id'],$item['quantity']);  // reduzo o estoque do produto KIT no Bling_utl_envio
						}
					}
					//verificacao do frete
					if ($store['freight_seller'] == 1 && in_array($store['freight_seller_type'], [3,4,5,6])) { // frete externo
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
						'tipo' 				=> $prf['tipo_volume_codigo'],     
			            'sku' 				=> $skulocal,
			            'quantidade' 		=> $item['quantity'],	           
			            'altura' 			=> (float) $prf['altura'] / 100,
					    'largura' 			=> (float) $prf['largura'] /100,
					    'comprimento' 		=> (float) $prf['profundidade'] /100,
					    'peso' 				=> (float) $prf['peso_bruto'],  
			            'valor' 			=> (float) $item['price_unit'] * $item['quantity'],
			            'volumes_produto' 	=> 1,
			            'consolidar' 		=> false,
			            'sobreposto' 		=> false,
			            'tombar' 			=> false,
						'skuseller' 		=> $skuseller
					);
		            $fr['volumes'][] = $vl;
				}

				$typeMessageLog = $order_exist ? 'incluído' : 'atualizado';
				$this->log_data('batch',$log_name,"Pedido {$pedido['order_id']} {$typeMessageLog}\n\n".json_encode($pedido));

				$this->calculofrete->updateShipCompanyPreview($order_id);
				
				// Gravando o log do pedido
				$data_log = array(
					'int_to' 	=> $this->getInt_to(),
					'order_id'	=> $order_id,
					'received'	=> json_encode($pedido)
				);
				$this->model_log_integration_order_marketplace->create($data_log);
				
			}	
		}
		
	}

	function cancelaPedido($pedido)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo 'Cancelando pedido ='.$pedido."\n"; 
		
		$motivo = 'Falta de produto';

		$cancel = Array (
					'body' => 'Por favor, cancelem o pedido '.$pedido.'. Motivo: '.$motivo.'. Obrigado', 
					'subject' => 'Favor cancelar pedido '.$pedido, 
					'to_customer' => false,
					'to_operator' => true,
					'to_shop' => false,
				);

		$json_data = json_encode($cancel);
		$url = 'https://'.$this->getSite().'/api/orders/'.$pedido.'/messages';

		$resp = $this->postMirakl($url, $this->getApikey(), $json_data);
		
	    //var_dump($resp); 

		if (!($resp['httpcode']=="201") )  {  // created
			echo 'Erro na respota do '.$this->getInt_to().' httpcode='.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true)."\n"; 
			$this->log_data('batch',$log_name, 'ERRO ao cancelar no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return false;
		}
		return true;
	}


	function getMirakl($url, $api_key){
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

	function putCarrefour($url, $api_key,$data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "PUT",
			CURLOPT_HTTPHEADER 		=>  array(
				'Accept: application/json',
				'Authorization: '.$api_key,
				'Content-Type: application/json'
				)
	    );
		if ($data != '') {
			$options[CURLOPT_POSTFIELDS] = $data;
		}
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
	
	function postMirakl($url, $api_key,$data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "POST",
	        CURLOPT_POSTFIELDS     => $data,
			CURLOPT_HTTPHEADER 		=>  array(
				'Accept: application/json',
				'Authorization: '.$api_key,
				'Content-Type: application/json'
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

	function getSkuVariant($prd_id, $variant, $sku) {
		$var = $this->model_products->getVariants($prd_id,$variant);
		if ($var) {
			if ($var['sku'] != '')	{
				return $var['sku'];
			}
		}
		return $sku;
	}
}

?>
