<?php
/*
 
Atualiza pedidos que chegaram na SkyHub

*/   
class SkyHubGetOrdersFaltando extends BatchBackground_Controller {
	
	var $int_to='B2W';
	var $apikey='';
	var $email='';
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_promotions');
		$this->load->model('model_category');
		$this->load->model('model_freights');

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
	function setEmail($email) {
		$this->email = $email;
	}
	function getEmail() {
		return $this->email;
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
		$this->setApikey($api_keys['apikey']);
		$this->setEmail($api_keys['email']);
	}
	
	function getOrdersAll()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// ["code"]=>  string(29) "Lojas Americanas-276673381801"
     
		$page = 1;
		$cnt = 0;
		while (true) {
			$url = 'https://api.skyhub.com.br/orders?page='.$page++;
			$retorno = $this->getSkyHub($url,$this->getApikey(),$this->getEmail());
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA B2W: ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA B2W: '.print_r($retorno,true),"E");
				return;
			}
		
			$resp_skyhub= json_decode($retorno['content'],true);
			if (count($resp_skyhub['orders']) == 0 ) {
				echo "acabou $cnt\n";
				break;
			}
			
			foreach ($resp_skyhub['orders'] as $pedido) {
				
				$cnt++;
				if ($order_exist = $this->model_orders->getOrdersDatabyBill($this->getInt_to(),$pedido['code'])) {
				
				}
				else {
					echo $pedido['code']." faltando status: ".$pedido['status']['type']."\n";
				}
			}
			
			
		}
		
	}

    function getorders()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		// filtro por data https://api.skyhub.com.br/orders?filters[start_date]=15/08/2019&filters[end_date]=15/08/2019
		
		// filtro pedidos novos https://api.skyhub.com.br/orders?filters[statuses][]=book_product   
		// filtro pedidos cancelados https://api.skyhub.com.br/orders?filters[statuses][]=order_canceled  
		// filtro pedidos aprovados https://api.skyhub.com.br/orders?filters[statuses][]=payment_received  
		// ver os demais status https://api.skyhub.com.br/statuses
		// se eu quiser ver todos os produtos
		// neste momento usaremos as filas 
		
		$this->load->library('calculoFrete');
		
		$falta = 'Lojas Americanas-276639039201cccc';   
	//	$falta = 'Lojas Americanas-276653014901cccc'; 
			$url = 'https://api.skyhub.com.br/orders/'.$falta;
			$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
			//var_dump($retorno);
			echo $falta."\n";
			If ($retorno['httpcode'] == 429) {
				echo ' estourei o limite. dormindo por 60 sec'."\n";
				sleep(60); // estourou o limite, aguardo 60 seg e tento de novo. 
				$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
				If ($retorno['httpcode'] == 204) {
					echo "Nenhum pedido na fila SkyHub \n";
					$temOrdem = false;
					die;
				}
			}
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA : '.print_r($retorno,true),"E");
				return;
			}
		
			$pedido = json_decode($retorno['content'],true);
			//var_dump($pedido);

			// Verifico se todos os skus estão certos e são das mesmas empresas 
			$cpy ='';
			$store_id = '';
			$erro = false;
			$cross_docking_default = 2;// tempo default de cross_docking  - depois colocar no prefixes
			$cross_docking = $cross_docking_default;
			$new_cross_docking = null;
			foreach($pedido['items'] as $item) {
				$sku_item = $item['product_id'];
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
				$query = $this->db->query($sql, array($sku_item,$this->getInt_to()));
				$prf = $query->row_array();
				if (empty($prf)) {
					if (strrpos($sku_item, "-") !=0) {
						$sku_item = substr($item['codigo'], 0, strrpos($item['codigo'], "-"));
						$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
						$query = $this->db->query($sql, array($sku_item,$this->getInt_to()));
						$prf = $query->row_array();
					}
					if (empty($prf))  {
						echo 'O pedido '.$pedido['numero'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()."! Ordem não importada"."\n";
						$this->log_data('batch',$log_name,'O pedido '.$pedido['code'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()."! Ordem não importada","E");
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
						echo 'O pedido '.$pedido['code'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada'."\n";
						$this->log_data('batch',$log_name,'O pedido '.$pedido['code'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada',"E");
						$erro = true; 
						if ($this->cancelaPedido($pedido['code'])) { // cancelo o pedido
						   // removo da fila 
							$url = 'https://api.skyhub.com.br/queues/orders/'.$pedido['code'];
							$resp = $this->deleteSkyHub($url, $this->getApikey(), $this->getEmail());
							if ($retorno['httpcode'] != 200) {
								echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
								echo " RESPOSTA: ".print_r($retorno,true)." \n"; 
								$this->log_data('batch',$log_name, 'ERRO ao remover da fila de pedidos site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
							}
						}
						break; // abandono os itens. 	
					}
				}

				$sku = $prf['sku'];
				//$prd = $this->model_products->getProductBySku($sku,$cpy);
				$prd = $this->model_products->getProductData(0,$prf['prd_id']);
				// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
				$new_cross_docking = $this->getCrossDocking($prd['category_id'], $new_cross_docking);

				// Tempo de crossdocking 
				if (isset($prf['crossdocking'])) {  // pega o pior tempo de crossdocking dos produtos
					if (((int) $prf['crossdocking'] + $cross_docking_default) > $cross_docking) {
						$cross_docking = $cross_docking_default + (int) $prf['crossdocking']; 
					};
				}

			}
			if ($erro) {
				return; // teve erro, encerro esta ordem 
			}
			echo 'cross_docking='.$cross_docking.' - Exceção='.json_encode($new_cross_docking)."\n";
			
			// Leio a Loja para pegar o service_charge_value
			$store = $this->model_stores->getStoresData($store_id);

			// Vejo se já existe para atualizar 
			if ($order_exist = $this->model_orders->getOrdersDatabyBill($this->getInt_to(),$pedido['code'])) {
				
				
				$status = $pedido['status']['type'];	
				echo "Ordem Já existe :".$order_exist['id']." status marketplace= ".$pedido['status']['type']." paid_status=".$order_exist['paid_status']."\n";		
				if ($status=='NEW') { 
					if ($order_exist['paid_status'] == '1') {
						echo "Já está recebido, só vou remover da fila\n";
						//pedido já recebido. Basta remover da fila da SKY HUB
					}
					else {
						// Não deveria acontecer. Mensagem de erro.
						$erro ="Pedido ".$pedido['code']." com status NEW na SkyHub já existe na base no pedido ".$order_exist['id']." e paid_status=".$order_exist['paid_status']; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"E");
						die;	
					}
				}elseif ($status=='WAITING_PAYMENT') { 
					if ($order_exist['paid_status'] == '1') {
						echo "Já está recebido, só vou remover da fila\n";
						//pedido já recebido. Basta remover da fila da SKY HUB
					}
					else {
						// Não deveria acontecer. Mensagem de erro.
						$erro ="Pedido ".$pedido['code']." com status WAITING_PAYMENT na SkyHub já existe na base no pedido ".$order_exist['id']." e paid_status=".$order_exist['paid_status']; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"E");
						die;	
					}

				}elseif ($status=='CANCELED') {
					if (($order_exist['paid_status'] == '97') || ($order_exist['paid_status'] == '98') || ($order_exist['paid_status'] == '99')) {
						//pedido já recebido. basta remover da fila da SKY HUB
						echo "Já está cancelado, só vou remover da fila\n";
					}
					else {
						// Pedido foi cancelado. Cancelo no sistema 
						$frete=$this->model_freights->getFreightsDataByOrderId($order_exist['id']);
						if (count($frete)==0) {
							$order_exist['paid_status'] = 97;   // Não tem frete, cancela definitivo
						}
						else {
							$frete = $frete[0];
							if ($frete['ship_company'] == 'CORREIOS') {
								$order_exist['paid_status'] = 97; // Se é correios também cancela definitivo
							}
							else {
								$order_exist['paid_status'] = 98; // Cancelar na transportadora 
							}
						}
						
						// devolvo o estoque
						$items = $this->model_orders->getOrdersItemData($order_exist['id']);
						foreach($items as $item) {
							$this->model_products->adicionaEstoque($item['product_id'],$item['qty'],$item['variant']);
						}
						$this->model_orders->updateByOrigin($order_exist['id'],$order_exist);
						echo "Marcado para cancelamento\n";
						// removo da fila 
					}
				}elseif (($status=='APPROVED')){
					if ($order_exist['paid_status'] != '1') {
						echo "Já está recebido, só vou remover da fila\n";
						//pedido já recebido. basta remover da fila da SKY HUB
					}
					else {
						// Pedido foi aprovado, mudo o status para faturar . Não precisa alterar o estoque
						// Pego o pagamento 
						// PARCELAS
						$i = 0;
						if (isset($pedido['parcelas'])) {
							$parcelas = $pedido['payments'];
							foreach($parcelas as $parc) {
								$i++;
								$parcs['parcela'] 			= $i;
								$parcs['order_id'] 			= $order_exist['id']; 
								$parcs['bill_no'] 			= $bill_no;
								$parcs['data_vencto'] 		= $parc['transaction_date'];
								$parcs['valor'] 			= $parc['value'];
								$parcs['forma_id']	 		= $parc['sefaz']['id_payment'];
								$parcs['forma_desc'] 		= $parc['method'];
								$parcs['forma_cf'] 			= ''; // nao tem na skyhub 
								//campos novoas abaixo
								$parcs['method'] 			= $parc['method'];
								$parcs['autorization_id'] 	= $parc['autorization_id'];
								$parcs['card_issuer'] 		= $parc['card_issuer'];
								$parcs['description'] 		= $parc['description'];
								$parcs['parcels'] 			= $parc['parcels'];
								$parcs['name_card_issuer'] 	= $parc['sefaz']['name_card_issuer'];
								$parcs['name_payment'] 		= $parc['sefaz']['name_payment'];
								//var_dump($parcs);
								$parcs_id = $this->model_orders->insertParcels($parcs);
								if (!$parcs_id) {
									$this->log_data('batch',$log_name,'Erro ao incluir pagamento ',"E");
									return; 
								}
							}
						}
						$this->model_orders->updatePaidStatus($order_exist['id'],3);
						// Se for pago, definir o cross docking a partir da data de pagamento
						if ($new_cross_docking) $cross_docking = $new_cross_docking; // irá usar o cross docking da categoria
						$this->model_orders->updateByOrigin($order_exist['id'], array('data_pago'=>date("Y-m-d H:i:s"),'data_limite_cross_docking' => $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,'')));

						echo 'Pedido '.$order_exist['id']." marcado para faturamento\n";
						// removo da fila 
					}
				}elseif ($status=='INVOICED') {
					if (($order_exist['paid_status'] == '4')) {
						//pedido já recebido. basta remover da fila da SKY HUB
						echo "Já está faturado, só vou remover da fila\n";
					}
					else {
						// Não sei o motivo de receber com outro status.  
						$erro ="Pedido ".$pedido['code']." com status ".$status." na SkyHub já existe na base no pedido ".$order_exist['id']." e paid_status=".$order_exist['paid_status']; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"W");
						die;	
					}
				}elseif ($status=='SHIPPED') {
					if (($order_exist['paid_status'] == '5')) {
						//pedido já recebido. basta remover da fila da SKY HUB
						echo "Já está entviado, só vou remover da fila\n";
					}
					elseif (($order_exist['paid_status'] == '55')) {
						echo "Já está enviado, Acerto o status e removo da fila\n";
						$order_exist['paid_status'] = 5; 
						$this->model_orders->updateByOrigin($order_exist['id'],$order_exist);
					}
					else {
						// Não sei o motivo de receber com outro status.  
						$erro ="Pedido ".$pedido['code']." com status ".$status." na SkyHub já existe na base no pedido ".$order_exist['id']." e paid_status=".$order_exist['paid_status']; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"W");
						die;	
					}
				}elseif ($status=='DELIVERED') {
					if (($order_exist['paid_status'] == '6')) {
						//pedido já recebido. basta remover da fila da SKY HUB
						echo "Já está entregue, só vou remover da fila\n";
					}
					elseif (($order_exist['paid_status'] == '60')) {
						echo "Já está entregue, acerto o status e removo da fila\n";
						$order_exist['paid_status'] = 6; 
						$this->model_orders->updateByOrigin($order_exist['id'],$order_exist);
					}
					else {
						// Não sei o motivo de receber com outro status.  
						$erro ="Pedido ".$pedido['code']." com status ".$status." na SkyHub já existe na base no pedido ".$order_exist['id']." e paid_status=".$order_exist['paid_status']; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"W");
						die;	
					}
				}else {
					// Não sei o motivo de receber outros pedidos.  
					$erro ="Pedido ".$pedido['code']." com status ".$status." na SkyHub já existe na base no pedido ".$order_exist['id']." e paid_status=".$order_exist['paid_status']; 
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"W");
					die;	
				}
				// removo da fila 
				die; //resolvi e morro 
				$url = 'https://api.skyhub.com.br/queues/orders/'.$pedido['code'];
				$resp = $this->deleteSkyHub($url, $this->getApikey(), $this->getEmail());
				if ($retorno['httpcode'] != 200) {
					echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
					echo " RESPOSTA: ".print_r($retorno,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO ao remover da fila de pedidos site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
					return;
				}
				die; // vou ler a próxima ordem		
			} 

			// gravo o novo pedido
			// PRIMEIRO INSERE O CLIENTE
			$clients = array();
			$clients['customer_name'] = $pedido['customer']['name'];	
			$clients['customer_address'] = $pedido['billing_address']['street'];
			$clients['addr_num'] = $pedido['billing_address']['number'];
			$clients['addr_compl'] = $pedido['billing_address']['complement'];
			if ((is_null($clients['addr_compl'])) || $clients['addr_compl'] ='NULL'){
				$clients['addr_compl'] ='';
			}
			$clients['addr_neigh'] = $pedido['billing_address']['neighborhood'];
			$clients['addr_city'] = $pedido['billing_address']['city'];
			$clients['addr_uf'] = $pedido['billing_address']['region'];
			$clients['country'] = $pedido['billing_address']['country'];
			$clients['zipcode'] = $pedido['billing_address']['postcode'];
			$clients['phone_1'] = $pedido['billing_address']['phone'];
			$clients['origin'] = $this->getInt_to();
			$clients['origin_id'] = $pedido['customer']['vat_number'];
			if (!isset($pedido['customer']['phones'][1])) {
				$pedido['customer']['phones'][1]= '';
			}
			$clients['phone_2'] = $pedido['customer']['phones'][1];
			
			if (is_null($pedido['customer']['email'])) {
				$pedido['customer']['email'] = '';
			}
			$clients['email'] =  $pedido['customer']['email'];
			
			$clients['cpf_cnpj'] = $pedido['customer']['vat_number'];  // nao tem esta informação no skyhub
			$clients['ie'] = '';
			$clients['rg'] = '';
			
			// var_dump($clients);
			/*
			if ($client_id = $this->model_clients->getByOrigin($this->getInt_to(),$pedido['customer']['vat_number'])) {
				$clients['id'] = $client_id['id'];
				$client_id = $this->model_clients->replace($clients);
				echo "Cliente Atualizado:".$client_id."\n";
			} else {
				$client_id = $this->model_clients->replace($clients);
				echo "Cliente Inserido:".$client_id."\n";
			}	
			 * 
			 */
			
			$client_id = $this->model_clients->insert($clients);
			if ($client_id == false) {
				echo 'Não consegui incluir o cliente'."\n";
				$this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
				return;
			}
				
			$zip = $pedido['shipping_address']['postcode'];
			
			// agora a ordem 
			$orders = Array();

			$orders['bill_no'] = $pedido['code'];
			$bill_no = $pedido['code'];
			$orders['numero_marketplace'] = $pedido['code']; // numero do pedido no marketplace 
			$orders['date_time'] = $pedido['placed_at'];
			$orders['customer_id'] = $client_id;
			
			$orders['total_order'] = (float)$pedido['total_ordered'] - (float)$pedido['shipping_cost'];
			
			$orders['service_charge_rate'] = $store['service_charge_value'];  
			$orders['service_charge'] = $pedido['total_ordered'] * $store['service_charge_value'] / 100;  
			$orders['vat_charge_rate'] = 0; //pegar na tabela de empresa - Não está sendo usado.....
			$orders['vat_charge'] = $pedido['total_ordered'] * $orders['vat_charge_rate'] / 100; //pegar na tabela de empresa - Não está sendo usado.....
			$orders['gross_amount'] = $pedido['total_ordered'];
			$orders['total_ship'] = $pedido['shipping_cost'];
			$frete = $pedido['shipping_cost'];
			$orders['discount'] = $pedido['discount'];
			if (is_null($pedido['discount'])){
				echo ' Pedido de troca. Pulando'."\n";
				die;				
			}
			$orders['net_amount'] = $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'];
			
			$skystatus = $pedido['status']['type'];
			switch ($skystatus) {
			    case "NEW":
			        $status = 1;
					$orders['data_pago'] = null;
			        break;
				case "WAITING_PAYMENT":
			        $status = 1;
					$orders['data_pago'] = null;
			        break;
			    case "APPROVED":
			        $status = 3;
					$orders['data_pago'] = date("Y-m-d H:i:s");
			        break;
			    case "CANCELED":
					$erro  ="Pedido ".$pedido['code']." com status ".$skystatus."na SkyHub mas não existe na nossa base. Retirando da fila"; 
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"I");
			        // Nem deu tempo de baixar este pedido.  So removo da fila. 
					$url = 'https://api.skyhub.com.br/queues/orders/'.$pedido['code'];
					$resp = $this->deleteSkyHub($url, $this->getApikey(), $this->getEmail());
					if ($retorno['httpcode'] != 200) {
						echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
						echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO ao remover da fila de pedidos site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA : '.print_r($retorno,true),"E");
						return;
					}
					die; // vou ler a próxima ordem		

			    default:
					// Não deveria cair aqui.
					$erro ="Pedido ".$pedido['code']." com status ".$skystatus."na SkyHub mas não existe na nossa base"; 
					echo $erro."\n"; 
					$this->log_data('batch',$log_name, $erro,"W");
					return;
			}
			
			$orders['paid_status'] = $status; // $pedido['status'];  CONVERTIDO
			$orders['company_id'] = $cpy;   
			$orders['store_id'] = $store_id;
			$orders['origin'] = $this->getInt_to();
			$orders['user_id'] = 1;   // ID DO SYSTEM USER

			if($new_cross_docking) $cross_docking = $new_cross_docking;
			$orders['data_limite_cross_docking'] = $status != 3 ? null : $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,''); // define cross_docking apenas se for pago

			if (isset($pedido['shipping_address'])) {
				if (is_null($pedido['shipping_address']['complement']) || ($pedido['shipping_address']['complement']=='null')) {
					$pedido['shipping_address']['complement'] = '';
				}
				$orders['customer_address'] 		= $pedido['shipping_address']['street'];
				$orders['customer_name'] 			= $pedido['shipping_address']['full_name'];
				$orders['customer_address_num'] 	= $pedido['shipping_address']['number'];
				$orders['customer_address_compl'] 	= $pedido['shipping_address']['complement'];
				$orders['customer_address_neigh'] 	= $pedido['shipping_address']['neighborhood'];
				$orders['customer_address_city'] 	= $pedido['shipping_address']['city'];
				$orders['customer_address_uf'] 	    = $pedido['shipping_address']['region'];
				$orders['customer_address_zip'] 	= preg_replace("/[^0-9]/", "",$pedido['shipping_address']['postcode']);
				$orders['customer_reference'] 		= $pedido['shipping_address']['reference'];
			} else {
				$orders['customer_address'] 		= $clients['customer_address'];
				$orders['customer_name'] 			= $clients['customer_name'] ;
				$orders['customer_address_num']   	= $clients['addr_num'];
				$orders['customer_address_compl'] 	= $clients['addr_compl'];
				$orders['customer_address_neigh']	= $clients['addr_neigh']; 
				$orders['customer_address_city'] 	= $clients['addr_city'];
				$orders['customer_address_zip'] 	= preg_replace("/[^0-9]/", "",$clients['zipcode']);
				$orders['customer_address_uf'] 	    = $clients['addr_uf'];
			}

			// se tem o número do pedido com 9 caracteres, é troca 
			$orders['exchange_request'] = null;
			if (strlen(substr($pedido['code'],strpos($pedido['code'],'-')+1))== 9) {
				$orders['exchange_request'] = true;
			}
			
			$order_id = $this->model_orders->insertOrder($orders);
			echo "Inserido:".$order_id."\n";
			if (!$order_id) {
				$this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
				return ;
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
			
			foreach($pedido['items'] as $item) {
				$skubling = $item['product_id'];
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
				$query = $this->db->query($sql, array($skubling, $this->getInt_to()));
				$prf = $query->row_array();
			    $cpy = $prf['company_id']; 
				$sku = 	$prf['sku'];			
				echo  $item['product_id']."=".$cpy."=".$sku."\n";
				$prd = $this->model_products->getProductData(0,$prf['prd_id']);

				if ($prd['is_kit'] ==0) {
					$items = array();
					$items['order_id'] = $order_id; // ID da order incluida
					$items['skumkt'] = $item['id'];
					$items['product_id'] = $prd['id'];
					$items['sku'] = $sku;
					$variant='';
					if ($prd['has_variants'] != '') {
						$variant = substr($item['id'],strrpos($item['id'], "-")+1);	
						$items['sku'] = $sku.'-'.$variant;
					}
					$items['variant'] = $variant;
					$items['name'] = $prd['name'];
					$items['qty'] = $item['qty'];
					$items['rate'] = $item['special_price'];
					$items['amount'] = (float)$item['special_price'] * (float)$item['qty'];
					$items['discount'] = (float)$item['original_price'] - (float)$item['special_price']; 
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
						echo 'Erro ao incluir item. removendo pedido '."\n";
						$this->model_orders->remove($order_id);
						$this->model_clients->remove($client_id);
						$this->log_data('batch',$log_name,'Erro ao incluir item. pedido mkt = '.$pedido['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
						return; 
					}
					$itensIds[]= $item_id; 
					// Acerto o estoque do produto 
					
					$this->model_products->reduzEstoque($prd['id'],$item['qty'],$variant);
					$this->model_blingultenvio->reduzEstoque($this->getInt_to(),$prd['id'],$item['qty']);
					
					// vejo se o produto estava com promoção de estoque e vejo se devo terminar 
					$this->model_promotions->updatePromotionByStock($prd['id'],$item['qty'],$item['special_price']); 
					
				}
				else { // é um kit,  
					echo "O item é um KIT id=". $prd['id']."\n";
					$productsKit = $this->model_products->getProductsKit($prd['id']);
					foreach ($productsKit as $productKit){
						$prd = $this->model_products->getProductData(0,$productKit['product_id_item']);
						echo "Produto item =".$prd['id']."\n";
						$items = array();
						$items['order_id'] = $order_id; // ID da order incluida
						$items['skumkt'] = $item['id'];
						$items['kit_id'] = $productKit['product_id'];
						$items['product_id'] = $prd['id'];
						$items['sku'] = $prd['sku'];
						$variant = '';
						$items['variant'] = $variant;  // Kit não pega produtos com variantes
						$items['name'] = $prd['name'];
						$items['qty'] = $item['qty'] * $productKit['qty'];
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
						
						$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant);
						
					}
					
					$this->model_blingultenvio->reduzEstoque($this->getInt_to(),$prd['id'],$item['qty']);  // reduzo o estoque do produto KIT no Bling_utl_envio
					
				}
				//verificacao do frete 
				$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
				$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 
				$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
				$vl = Array ( 
					'tipo' => $prf['tipo_volume_codigo'],     
		            'sku' => $skubling,
		            'quantidade' => $item['qty'],	           
		            'altura' => (float) $prf['altura'] / 100,
				    'largura' => (float) $prf['largura'] /100,
				    'comprimento' => (float) $prf['profundidade'] /100,
				    'peso' => (float) $prf['peso_bruto'],  
		            'valor' => (float) $item['special_price'],
		            'volumes_produto' => 1,
		            'consolidar' => false,
		            'sobreposto' => false,
		            'tombar' => false);
	            $fr['volumes'][] = $vl;
			}

			// verificação do frete
			$this->calculofrete->updateShipCompanyPreview($order_id);

			/*
			 * [PEDRO HENRIQUE - 18/06/2021] Lógica para previsão da transportadora,
			 * método e prazo de envio, foi migrada para o método updateShipCompanyPreview,
			 * dentro da biblioteca CalculoFrete
			 *
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
				die;	
			}
			if (!array_key_exists('servicos',$resposta )) {
				$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				die;	
			}
			if (empty($resposta['servicos'] )) {
				$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				die;	
			}	
			$key = key($resposta['servicos']); 
			$transportadora = $resposta['servicos'][$key]['empresa']; 
			$servico =  $resposta['servicos'][$key]['servico'];
			$prazo = $resposta['servicos'][$key]['prazo']; 
			$this->model_orders->setShipCompanyPreview($order_id,$transportadora,$servico,$prazo);
			*/
			
			// PARCELAS
			$i = 0;
			if (isset($pedido['parcelas'])) {
				$parcelas = $pedido['payments'];
				foreach($parcelas as $parc) {
					$i++;
					$parcs['parcela'] 			= $i;
					$parcs['order_id'] 			= $order_id;
					$parcs['bill_no'] 			= $bill_no;
					$parcs['data_vencto'] 		= $parc['transaction_date'];
					$parcs['valor'] 			= $parc['value'];
					$parcs['forma_id']	 		= $parc['sefaz']['id_payment'];
					$parcs['forma_desc'] 		= $parc['method'];
					$parcs['forma_cf'] 			= ''; // nao tem na skyhub 
					//campos novoas abaixo
					$parcs['method'] 			= $parc['method'];
					$parcs['autorization_id'] 	= $parc['autorization_id'];
					$parcs['card_issuer'] 		= $parc['card_issuer'];
					$parcs['description'] 		= $parc['description'];
					$parcs['parcels'] 			= $parc['parcels'];
					$parcs['name_card_issuer'] 	= $parc['sefaz']['name_card_issuer'];
					$parcs['name_payment'] 		= $parc['sefaz']['name_payment'];
					//var_dump($parcs);
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

		$cancel = Array (
					'status' => 'order_canceled'
				);

		$json_data = json_encode($cancel);
		
		$url = 'https://api.skyhub.com.br/orders/'.$pedido.'/cancel';

		$resp = $this->postSkyHub($url.'', $json_data, $this->getApikey(), $this->getEmail());
		if (!($resp['httpcode']=="201") )  {  // created
			echo "Erro na respota do '.$this->getInt_to().' url:'.$url.' httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO ao cancelar no '.$this->getInt_to().' url:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return false;
		}
		return true;
		
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

	function postSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function putSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "PUT",
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function deleteSkyHub($url, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "DELETE",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function getCrossDocking($category_id, $new_cross_docking)
	{
		// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
		$category = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);
		$dataCategory = $this->model_category->getCategoryData($category);
		if ($dataCategory && $dataCategory['days_cross_docking']) {

			$limit_cross_docking_category = (int)$dataCategory['days_cross_docking'];

			if ($new_cross_docking && $limit_cross_docking_category < $new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;

			if (!$new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;
		}
		return $new_cross_docking;
	}

}

?>
