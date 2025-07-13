<?php
/*
 
Verifica quais ordens receberam Nota Fiscal e Envia para o Bling 

*/   

require APPPATH . "controllers/BatchC/SellerCenter/RD/Main.php";
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

/**
 * @property CI_Loader $load
 * @property CI_Router $router
 * @property CI_Session $session
 *
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_clients $model_clients
 * @property Model_integrations $model_integrations
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_shipping_company $model_shipping_company
 * @property Model_stores $model_stores
 * @property Model_sc_last_post $model_sc_last_post
 * @property Model_settings $model_settings
 *
 * @property OrdersMarketplace $ordersmarketplace
 */
class SyncOrderStatus extends Main {
		
	var $int_to='RD';
	var $apikey='';
	var $email='';
	var $auth_data;
    var $order_db = null;
    var $credential = null;
    var $auth = null;
	
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
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_frete_ocorrencias');
		$this->load->model('model_shipping_company');
		$this->load->model('model_stores');
		$this->load->model('model_sc_last_post');
        $this->load->model('model_settings');

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

    function syncIntTo($credential, $auth, $int_to){
		// $this->getkeys(1,0, $int_to);
		$this->mandaNfe($credential,$auth, $int_to);
		$this->mandaTracking($int_to);
		$this->mandaEnviado($credential,$auth, $int_to);
		$this->mandaEntregue($credential,$auth, $int_to);
		$this->mandaCancelados($credential,$auth, $int_to);
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
		
        $this->model_sc_last_post->setIntTo("rd");
        $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
        if($integration){
            $this->credential = json_decode($integration['auth_data']);
            $this->auth = $this->auth($this->credential->api_url, $this->credential->grant_type, $this->credential->client_id, $this->credential->client_secret);
            
            echo 'Sync: '. $integration['int_to']."\n";
            $this->syncIntTo($this->credential, $this->auth, $integration['int_to']);
        }

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}

	function getkeys($company_id,$store_id, $int_to) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$int_to,"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setEmail($api_keys['email']);
	}
	
	function mandaTracking($int_to)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';  
		
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

	function mandaNfe($credential, $auth, $int_to)
	{		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '52';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$int_to,"I");
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

			$date_emission = date_create_from_format('d/m/Y H:i:s', $nfe['date_emission']);
			$date_emission = date_format($date_emission,'Y-m-d H:i:s');

			$invoiced = [
				"invoiceNumber" => $nfe['order_id'],
				"invoiceUrl" => $nfe['link_tiny'],
				"invoiceKey" => $nfe['chave'],
				"serie" => $nfe['nfe_serie'],
				"value" => $nfe['nfe_value'],
				"issueDate" => $date_emission
			];
			
			$json_data = json_encode($invoiced);

			$url = '/marketplace/orders/' . $order['numero_marketplace'] .'/invoice';

			$this->process($credential, $auth, $url, 'POST', $json_data);
			$resp['httpcode'] = $this->responseCode;

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
						echo "Erro na resposta do ".$int_to.". httpcode=".$resp['httpcode']." RESPOSTA: "." \n"; 
						echo "Dados enviados=".print_r($json_data,true)."\n";
						$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$int_to.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$int_to.' DADOS ENVIADOS:'.print_r($json_data,true),"E");
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
						echo "Erro na resposta do ".$int_to.". httpcode=".$resp['httpcode']." RESPOSTA: "." \n"; 		
						echo "Dados enviados=".print_r($json_data,true)."\n";
						$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$int_to.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$int_to.' DADOS ENVIADOS:'.print_r($json_data,true),"E");
						continue;
					}
					
				} 
				
			}

			if (($resp['httpcode']!="200") && ($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				echo "Erro na resposta do ".$int_to.". httpcode=".$resp['httpcode']." RESPOSTA: "." \n"; 
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$int_to.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$int_to.' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}

			$order['paid_status'] = 50; // agora tudo certo para contratar frete 
			$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'NFE enviado para '.$int_to."\n";
		} 

	}

	function mandaEnviado($credential,$auth, $int_to)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$int_to,"I");
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
			

			$sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
			if (!empty($sellercenter) && ($sellercenter == 'conectala')) {
				$carrier_url = base_url('rastreio');
			}

			$item = $this->model_orders->getOrdersDate($order['id']);
	
			$payloadMandarEnvioRD = [
				"code" => $item['id'],
                "carrier" => $item['ship_company'],
				"trackingCode" => $item['codigo_rastreio'],
				"trackingUrl" =>  publicUrl('rastreio'),
				"shippedDate" => $item['data_envio']
			];

			$json_data = json_encode($payloadMandarEnvioRD,JSON_UNESCAPED_SLASHES);

			// $url = 'https://api.skyhub.com.br/orders/'.$order['numero_marketplace'].'/shipments';
			$url = '/marketplace/orders/' . $order['numero_marketplace'] .'/shipment';

			$this->process($credential, $auth, $url, 'POST', $json_data);
			$resp['httpcode'] = $this->responseCode;
	
			// $resp = $this->postSkyHub($url.'', $json_data,$this->getApikey(), $this->getEmail());
			
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
				
				// $pedido = $this->readOrder($order['numero_marketplace']);
				// if ($pedido != false) {
				// 	if ($pedido['status']['type'] == "SHIPPED") {
				// 		echo 'Já foi avisado no marketplace'."\n";
				// 		$resp['httpcode']="201";
				// 	}					
				// }			
			}

			if (($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				echo "Erro na resposta do ".$int_to.". httpcode=".$resp['httpcode']." RESPOSTA: "."\n"; 
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido '.$order['id'].' '.$order['numero_marketplace'].' enviado no '.$int_to.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$int_to.' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				// continue;
			}
			 
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Envio enviado para '.$int_to."\n";
		} 

	}

	function mandaEntregue($credential,$auth, $int_to)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 60, ordens que já tem mudaram o status para entregue no FreteRastrear
		$paid_status = '60';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Entregue da '.$int_to,"I");
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

			$delivered = [
				'deliveredDate' => $order['data_entrega']
			];
									
			$json_data = json_encode($delivered);
		

			// https://api-qa.raiadrogasil.io/v1/api/marketplace/orders/$order['numero_marketplace']/delivered
			$url = '/marketplace/orders/' . $order['numero_marketplace'] .'/delivered';

			$this->process($credential, $auth, $url, 'PUT', $json_data);
			$resp['httpcode'] = $this->responseCode;
			
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
				echo "Erro na resposta do ".$int_to.". httpcode=".$resp['httpcode']." RESPOSTA ".$int_to." \n"; /*.": "." \n"*/ 
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido '.$order['id'].' '.$order['numero_marketplace'].' entregue no '.$int_to.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$int_to.' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			}
		
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 6; // O pedido está entregue 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Entregue enviado para '.$int_to."\n";
		} 

	}

	function mandaCancelados($credential,$auth, $int_to)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 99, ordens que canceladas que tem que ser avisadas no Marketplace
		$paid_status = '99';  
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem a cancelar '.$int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order) {
			echo 'Cancelando pedido ='.$order['id']."\n"; 
			
			$cancel = [
				"date" => date('Y-m-d H:i:s'),
				"reason"=> "Ruptura de estoque"
			];
			
			$json_data = json_encode($cancel);

			$url = '/marketplace/orders/' . $order['numero_marketplace'] .'/cancel';

			$this->process($credential, $auth, $url, 'PUT', $json_data);
			$resp['httpcode'] = $this->responseCode;

			if (($resp['httpcode']!="201") && ($resp['httpcode']!="204")) {  
				// $erro = json_decode($resp['content'],true);
				$msg_erro = true;
				if (isset($erro['error'])) {
					if ($erro['error'] == "Transição inválida: CANCELED -> CANCELED") { // já estava cancelado no marketplace
						$msg_erro = false;
					}
				}
				if ($msg_erro) {
					echo "Erro na resposta do ".$int_to." httpcode=".$resp['httpcode']." RESPOSTA ".$int_to.": "." \n";
					$this->log_data('batch',$log_name, 'ERRO ao cancelar pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$int_to.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$int_to.' DADOS ENVIADOS:'.print_r($json_data,true),"E");
					// continue;
				}
			}
			$this->ordersmarketplace->cancelOrder($order['id'], true);
			echo 'Cancelado em '.$int_to."\n";
		} 

	}

	// function readOrder($numero_marketplace) {
	// 	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
	// 	$url = 'https://api.skyhub.com.br/orders/'.$numero_marketplace;
	// 	$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
	// 	if ($retorno['httpcode'] != 200) {
	// 		echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
	// 		echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
	// 		$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA : '.print_r($retorno,true),"E");
	// 		return false;
	// 	}	
	// 	return json_decode($retorno['content'],true);
		
	// }

	/*function postSkyHub($url, $post_data, $api_key, $login){
		
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
	}*/

	/*function getSkyHub($url, $api_key, $login){
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
	}*/

}