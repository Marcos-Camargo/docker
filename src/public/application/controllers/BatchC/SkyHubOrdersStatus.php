<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   
class SkyHubOrdersStatus extends BatchBackground_Controller {
		
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
		$this->load->model('model_shipping_company');
		$this->load->model('model_stores');
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
		$this->mandaNfe();
		$this->mandaTracking();
		$this->mandaEnviado();
		$this->mandaEntregue();
		$this->mandaCancelados();
		//$this->mandaExcecaoEntrega();
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
	

	function mandaNfe()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '52';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			//pego a cotação de frete

			$nfes = $this->model_orders->getOrdersNfes($order['id']);
			if (count($nfes) == 0) {
				echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}
			$nfe = $nfes[0]; 
			
			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']); // leio se tem frete para utilizar mais abaixo
			
			// $issue_date =  str_replace(" ","T",$nfe['date_emission'])."-03:00";
			
			$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission']);
			if ($issue_date_o == false) {
				$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission'].' 23:00:00');
				if ($issue_date_o == false) {
					$issue_date_o = DateTime::createFromFormat('Y-m-d H:i:s',$nfe['date_emission']);
				}
			}
			if ($issue_date_o > (new DateTime)) { // ve se a data passou da hora de agora.
				$issue_date_o = new DateTime;
			} 

			$data_pago = DateTime::createFromFormat('Y-m-d H:i:s',$order['data_pago']);
			if ($data_pago > $issue_date_o) {
				$data_pago->add(new DateInterval('PT15M')); // somo 15 Minutos
				$issue_date_o = $data_pago;  // evitar problema de enviar nota fiscal com data de nota fiscal menor que a data de pagamento
			}

			$issue_date = $issue_date_o->format('Y-m-d H:i:s');
			$issue_date = str_replace(" ","T",$issue_date)."-03:00";
			$invoiced = Array (
						'status' => 'order_invoiced',
						'invoice' => array(
						    "key" => $nfe['chave'],
						    "issue_date" => $issue_date,
						    'volume_qty' => $this->model_orders->countOrderItem($order['id'])
						) 
					);
			
			$json_data = json_encode($invoiced);
			
			$url = 'https://api.skyhub.com.br/orders/'.$order['numero_marketplace'].'/invoice';
	
			$resp = $this->postSkyHub($url.'', $json_data, $this->getApikey(), $this->getEmail());

			if ($resp['httpcode']=="422") {
				$error = json_decode($resp['content'], true);
				if ($frete) { // Jeito antigo, já contratei o frete e vejo os status que deram problema 
					/*
					 * 04/05/2021 - Pedro Henrique
					 * Removido EP-228 (Não devemos atualizar o pedido com o status que está na B2W)
					 *
					if (($error['error']) == "Transição inválida: SHIPPED -> INVOICED") {
						echo 'Alguem avançou o pedido '.$order['id'].' '.$order['numero_marketplace'].' para ENVIADO direto no marketplace'."\n";
						$this->log_data('batch',$log_name, 'Alguem avançou o pedido '.$order['id'].' para ENVIADO direto no marketplace',"W");
						$order['paid_status'] = 5; // Mudo para Enviado para parar de tentar enviar a toa.
						$this->model_orders->updateByOrigin($order['id'],$order);
						continue;
					}elseif (($error['error']) == "Transição inválida: DELIVERED -> INVOICED") {
						echo 'Alguem avançou o pedido '.$order['id'].' '.$order['numero_marketplace'].' para ENTREGUE direto no marketplace'."\n";
						$this->log_data('batch',$log_name, 'Alguem avançou o pedido '.$order['id'].' para ENTREGUE direto no marketplace',"W");
						$order['paid_status'] = 6; // Mudo para Entregue para parar de tentar enviar a toa.
						$this->model_orders->updateByOrigin($order['id'],$order);
						continue;
					}
					else {*/
						echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA: ".print_r($resp['content'],true)." \n"; 
						echo "Dados enviados=".print_r($json_data,true)."\n";
						$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
						continue;
					//}
				}
				else { // Jeito novo. vou ter que descobrir o que há de errado. 
					if (($error['error']) == "Transição inválida: SHIPPED -> INVOICED") {
						echo 'Alguem avançou o pedido '.$order['id'].' '.$order['numero_marketplace'].' para ENVIADO direto no marketplace'."\n";
						$this->log_data('batch',$log_name, 'Alguem avançou o pedido '.$order['id'].' para ENVIADO direto no marketplace',"W");
						$order['paid_status'] = 50; // Mudo para etiqueta .
						$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
						$this->model_orders->updateByOrigin($order['id'],$order);
						continue;
					}elseif (($error['error']) == "Transição inválida: CANCELED -> INVOICED") {
						echo 'O pedido '.$order['id'].' '.$order['numero_marketplace'].' foi cancelado no marketplace'."\n";
						$this->ordersmarketplace->cancelOrder($order['id'], true);
						continue;
					}else{  
						echo 'Alguem avançou o pedido '.$order['id'].' '.$order['numero_marketplace'].': '.$error['error']."\n";
						echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA: ".print_r($resp['content'],true)." \n"; 		
						echo "Dados enviados=".print_r($json_data,true)."\n";
						$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
						continue;
					}
					
				} 
				
			}

			if (($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA: ".print_r($resp['content'],true)." \n"; 
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}


			// [PEDRO] - precisa sempre ir para 50, se for para 53 o seller não irá ocnseguir "gerar" a etiqueta
			//if ($frete) { // se já tem frete, jeito antigo de contratar frete e enviar
			//	$order['paid_status'] = 53; // agora tudo certo para ficar rasteando o pedido
			//}
			//else { // Jeito novo, envia nota fiscal e depois contrata o frete
				$order['paid_status'] = 50; // agora tudo certo para contratar frete 
				$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			//}
			// Nota fiscal enviada 
			
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'NFE enviado para '.$this->getInt_to()."\n";
		} 

	}
	
	function mandaTracking()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';  
		$int_to = $this->getInt_to();
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Tracking da '.$int_to,"I");
			return ;
		}
		foreach ($ordens_andamento as $order) {
			if (is_null($order['envia_nf_mkt'])) { 
				$order['paid_status'] = 52; // fluxo antigo, manda para a envio da Nota Fiscal 
			}
			else {
				$order['paid_status'] = 53; // fluxo novo, manda para a rastreio
			}
			
			$this->model_orders->updateByOrigin($order['id'],$order);
		} 
	}
	
	function mandaEnviado()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
			
			$nfes = $this->model_orders->getOrdersNfes($order['id']);
			if (count($nfes) == 0) {
				echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}
			$nfe = $nfes[0]; 

			$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission']);
			if ($issue_date_o == false) {
				$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission'].' 23:00:00');
			}
			if ($issue_date_o > (new DateTime)) { // ve se a data passou da hora de agora.
				$issue_date_o = new DateTime;
			} 

			$data_pago = DateTime::createFromFormat('Y-m-d H:i:s',$order['data_pago']);
			if ($data_pago > $issue_date_o) {
				$data_pago->add(new DateInterval('PT15M')); // somo 15 Minutos
				$issue_date_o = $data_pago;  // evitar problema de enviar nota fiscal com data de nota fiscal menor que a data de pagamento
			}

			$issue_date = $issue_date_o->format('Y-m-d H:i:s');
			$issue_date = str_replace(" ","T",$issue_date)."-03:00";
			
			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
				continue;

			}
			$frete = $frete[0];
			
			$envio_date_o = DateTime::createFromFormat('Y-m-d H:i:s', $order['data_envio']);
			if ($envio_date_o == false) {
				$envio_date_o = DateTime::createFromFormat('d/m/Y H:i:s', $order['data_envio']);
				if ($envio_date_o == false) {
					$envio_date_o = DateTime::createFromFormat('d/m/Y H:i:s', $order['data_envio'].' 23:00:00');
				}
			}
			if ($envio_date_o > (new DateTime)) { // ve se a data passou da hora de agora.
				echo "Pulando o pedido ".$order['id']." pois a data de envio ".$order['data_envio']." maior do que agora \n";
				continue;
			}
			
			$data_envio =  str_replace(" ","T",$order['data_envio'])."-03:00";
			if ($frete['prazoprevisto'].' 00:00:00' <  $order['data_envio']) {
				$prazo_previsto = $this->somar_dias_uteis($order['data_envio'],5)."T15:30:00-03:00";
			}
			else {
				$prazo_previsto = $frete['prazoprevisto']."T15:30:00-03:00";
			}
			
			$carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/';
			if (!empty($frete['CNPJ'])) {
				$transportadora = $this->model_shipping_company->getShippingCompanyByCnpjAndStore($frete['CNPJ'], $order['store_id']);
				if ($transportadora) {
					if (!is_null($transportadora['tracking_web_site'])) {
						$carrier_url = $transportadora['tracking_web_site'];
					}
				}
			}

			$sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
			if (!empty($sellercenter) && ($sellercenter == 'conectala')) {
				$carrier_url = base_url('rastreio');
			}

			$shipped = Array (
						'status' => 'order_shipped',
						'shipment' => array(
						    "code" => $order['numero_marketplace'],
						    "delivered_carrier_date" => $data_envio, 
							"track" => array (
								"code" =>  $frete['codigo_rastreio'], 
								"carrier"=>  $frete['ship_company'],
								"method"=> $frete['method'],
								"url"=> $carrier_url
							)
						),
						'invoice' => array(
						    "key" => $nfe['chave'],
						    "issue_date" => $issue_date,
						    'volume_qty' => $this->model_orders->countOrderItem($order['id'])
						), 
						'estimated_delivery' => $prazo_previsto
						
					);
									
			$shipped["shipment"]["items"] = array();
			$itens = $this->model_orders->getOrdersItemData($order['id']);
			foreach($itens as $item) {
				// pego o SKU do Bling do produto. 
				$prd_id = $item['product_id']; 
				if (!is_null($item['kit_id'])) {
					$prd_id = $item['kit_id']; 
				}
				if (!is_null($item['skumkt'])) {
					$skumkt =$item['skumkt'];
				}
				else {
					$sql = 'SELECT * FROM prd_to_integration WHERE prd_id = ? and int_to = ?'; 
					$query = $this->db->query($sql, array($prd_id, $order['origin']));
					$prd_integration = $query->row_array(); 
							
					// pego o produto do  prd_to_integration
					$variant = ''; // se tiver variant, o SKU muda
					if ($item['variant'] != '') {
						$variant = '-'.$item['variant'];
					} 
					$skumkt = $prd_integration['skubling'].$variant;
				}
				$shipped["shipment"]["items"][] = array(
					'sku' => $skumkt,
					'qty' => $item['qty'],
				);
			}
			$json_data = json_encode($shipped);
			
			$url = 'https://api.skyhub.com.br/orders/'.$order['numero_marketplace'].'/shipments';
	
			$resp = $this->postSkyHub($url.'', $json_data,$this->getApikey(), $this->getEmail());
			
		    //var_dump($resp); 
			if ($resp['httpcode']=="422") {
				$error = json_decode($resp['content'], true);
				if (($error['error']) == "Transição inválida: DELIVERED -> SHIPPED") {
					echo 'Alguem avançou o pedido '.$order['id'].' '.$order['numero_marketplace'].' para ENTREGUE direto no marketplace'."\n";
					$this->log_data('batch',$log_name, 'Alguem avançou o pedido '.$order['id'].' para ENTREGUE direto no marketplace',"W");
					/*
					 * 04/05/2021 - Pedro Henrique
					 * Removido EP-228 (Não devemos atualizar o pedido com o status que está na B2W)
					 *
					$order['paid_status'] = 5; // Mudo para Enviado para parar de tentar enviar a toa.
					$this->model_orders->updateByOrigin($order['id'],$order);
					*/
					continue;
				}
				
				$pedido = $this->readOrder($order['numero_marketplace']);
				if ($pedido != false) {
					if ($pedido['status']['type'] == "SHIPPED") {
						echo 'Já foi avisado no marketplace'."\n";
						$resp['httpcode']="201";
					}					
				}			
			}
				
			if (($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA: ".print_r($resp['content'],true)." \n"; 
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido '.$order['id'].' '.$order['numero_marketplace'].' enviado no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}
			 
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Envio enviado para '.$this->getInt_to()."\n";
		} 

	}

	function mandaEntregue()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 60, ordens que já tem mudaram o status para entregue no FreteRastrear
		$paid_status = '60';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Entregue da '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 

			$entrega_date_o = DateTime::createFromFormat('Y-m-d H:i:s', $order['data_entrega']);
			if ($entrega_date_o == false) {
				$entrega_date_o = DateTime::createFromFormat('d/m/Y H:i:s', $order['data_entrega']);
				if ($entrega_date_o == false) {
					$entrega_date_o = DateTime::createFromFormat('d/m/Y H:i:s', $order['data_entrega'].' 23:00:00');
				}
			}
			if ($entrega_date_o > (new DateTime)) { // ve se a data passou da hora de agora.
				echo "Pulando o pedido ".$order['id']." pois a data de entrega ".$order['data_entrega']." maior do que agora \n";
				continue;
			}

			$delivered = Array (
						'status' => 'complete',
						'delivered_date' => date("d/m/Y",strtotime($order['data_entrega']))
					);
									
			$json_data = json_encode($delivered);
			
			$url = 'https://api.skyhub.com.br/orders/'.$order['numero_marketplace'].'/delivery';
	
			$resp = $this->postSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
			
		   // var_dump($resp); 
		   if ($resp['httpcode']=="422") {
				$error = json_decode($resp['content'], true);
				//var_dump($error);
				echo $error['errors']['detail']."\n";
				if (($error['errors']['detail']) == "Transição inválida: complete -> complete.") {
					echo 'Alguem avançou o pedido '.$order['id'].' '.$order['numero_marketplace'].' para ENTREGUE direto no marketplace'."\n";
					$this->log_data('batch',$log_name, 'Alguem avançou o pedido '.$order['id'].' para ENTREGUE direto no marketplace',"W");
					$resp['httpcode']="201";								
				}
			
			}

			if (($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido '.$order['id'].' '.$order['numero_marketplace'].' entregue no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}
		
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 6; // O pedido está entregue 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Entregue enviado para '.$this->getInt_to()."\n";
		} 

	}

	function mandaCancelados()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 99, ordens que canceladas que tem que ser avisadas no Marketplace
		$paid_status = '99';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($this->getInt_to(),$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem a cancelar '.$this->getInt_to(),"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'Cancelando pedido ='.$order['id']."\n"; 
			
			$cancel = Array (
						'status' => 'order_canceled'
					);

			$json_data = json_encode($cancel);
			
			$url = 'https://api.skyhub.com.br/orders/'.$order['numero_marketplace'].'/cancel';
	
			$resp = $this->postSkyHub($url.'', $json_data,$this->getApikey(), $this->getEmail());

		    //var_dump($resp);

			if (($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				$erro = json_decode($resp['content'],true);
				$msg_erro = true;
				if (isset($erro['error'])) {
					if ($erro['error'] == "Transição inválida: CANCELED -> CANCELED") { // já estava cancelado no marketplace
						$msg_erro = false;
					}
				}
				if ($msg_erro) {
					echo "Erro na respota do ".$this->getInt_to()." httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n";
					$this->log_data('batch',$log_name, 'ERRO ao cancelar pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
					continue;
				}
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
			    echo 'ignorando pedido '.$order['id'].' do mktplace '.$order['origin']."\n";
				continue ; 
			}
			
			$date_ocorrencia = str_replace(" ","T",$ocorrencia['data_ocorrencia'])."-03:00";
			$msg = $ocorrencia['nome'];
			if (!is_null($ocorrencia['mensagem'])) {
				$msg .= ' '.$ocorrencia['mensagem'];
			}
			echo 'Enviando ocorrencia pedido ='.$order['id'].' '.$msg."\n"; 
			
			$shipment_exception = Array (
						'shipment_exception' => array(
							'occurrence_date' => $date_ocorrencia,
							"observation" => $msg
						)
					);

			$json_data = json_encode($shipment_exception);
			//var_dump($json_data);
			
			$url = 'https://api.skyhub.com.br/orders/'.$order['numero_marketplace'].'/shipment_exception';
			//var_dump($url);
			$resp = $this->postSkyHub($url.'', $json_data,$this->getApikey(), $this->getEmail());
			
		    //var_dump($resp); 

			if (($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				$error = json_decode($resp['content'],true);
				if (array_key_exists('error', $error)) {
					if ($error['error'] =="Transição inválida: SHIPMENT_EXCEPTION -> SHIPMENT_EXCEPTION") {
						echo 'Já está em exceção '."\n";
						$this->model_frete_ocorrencias->updateFreightsOcorrenciaAviso($ocorrencia['id'],'1');
						continue; 
					}
				}
				echo "Erro na respota do ".$this->getInt_to()." httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO ao mandar exceções de entrega pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}

			$this->model_frete_ocorrencias->updateFreightsOcorrenciaAviso($ocorrencia['id'],'1');
			echo 'Ocorrencia enviada para '.$this->getInt_to()."\n";
		} 

	}
	
	function readOrder($numero_marketplace) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$url = 'https://api.skyhub.com.br/orders/'.$numero_marketplace;
		$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
		if ($retorno['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA : '.print_r($retorno,true),"E");
			return false;
		}	
		return json_decode($retorno['content'],true);
		
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

		if ($httpcode == 429) {
			echo "Estourei o limite \n";
			sleep(15);
			return $this->postSkyHub($url, $post_data, $api_key, $login);
		}
	    return $header;
	}

	function getSkyHub($url, $api_key, $login){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
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

		if ($httpcode == 429) {
			echo "Estourei o limite \n";
			sleep(15);
			return $this->getSkyHub($url, $api_key, $login);
		}
	    return $header;
	}

}
?>
