<?php

require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

class ViaOrdersStatus extends BatchBackground_Controller {
		
    private $oAuth2 = null;
    private $integration = null;

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

		$this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();

		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_nfes');
		$this->load->model('model_freights');
		$this->load->model('model_integrations');
    }
	
	private function getInt_to() {
		return ViaUtils::getInt_to();
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
		
		$int_to = $this->getInt_to();

		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);

		/* faz o que o job precisa fazer */
		$this->mandaNfe($authorization);
		$this->mandaTracking($authorization);
		$this->mandaEnviado($authorization);
		// $this->mandaEnviadoExterno($authorization);
		$this->mandaEntregue($authorization);

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function mandaNfe($authorization)
	{
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '52';  
		$int_to = $this->getInt_to();
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$int_to,"I");
			return ;
		}
		echo "Enviando Nota Fiscal\n";
		foreach ($ordens_andamento as $order) {

			if ($order['exchange_request']) { // pedido de troca não é atualizado no Carrefour
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 50; // agora tudo certo para contratar frete 
				$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}

			$nfes = $this->model_orders->getOrdersNfes($order['id']);
			if (count($nfes) == 0) {
				echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}
			$nfe = $nfes[0]; 

			$items = $this->getSkusOrderItems($authorization, $order['id'], $order['numero_marketplace']);

			$response = $this->integration->invoiceOrder($authorization, $order, $items, $nfe);

			if (($response['httpcode'] < 200 ) || ($response['httpcode'] > 299))  { 
				if (!$this->isSent($authorization, $order['numero_marketplace']))
				{
					$req_order = $this->integration->castSentOrder($order, $items, $nfe, $frete);
					$json_data = json_encode($req_order);
					echo "Erro na respota do ". $int_to ." - Order: ". $order['id'] . " httpcode=".$response['httpcode']." RESPOSTA VIA VAREJO: ".print_r($response['content'],true)." \n"; 
					echo "Dados enviados=".print_r($json_data,true)."\n";
					$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido enviado no '.$int_to. " - Order: ". $order['id'] . ' - httpcode: '.$response['httpcode'].' RESPOSTA '.$int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
					continue;
				}
			}
			else {
				$order['paid_status'] = 50; // agora tudo certo para contratar frete 
				$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
				$this->model_orders->updateByOrigin($order['id'],$order);
			}
		} 
	}
	
	function mandaTracking($authorization)
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
	
	function mandaEnviado($authorization)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		$int_to = $this->getInt_to();
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$int_to,"I");
			return ;
		}
		echo "Enviando Postagem\n";
		
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 

			if ($order['exchange_request']) { // pedido de troca não é atualizado no Carrefour
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio
			    $this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}

			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
				continue;

			}
			$frete = $frete[0];
			
			$items = $this->getSkusOrderItems($authorization, $order['id'], $order['numero_marketplace']);

			$response = $this->integration->sentOrder($authorization, $order, $items, $frete);

			if (($response['httpcode'] < 200 ) || ($response['httpcode'] > 299))  { 
				if (!$this->isSent($authorization, $order['numero_marketplace']))
				{
					$req_order = $this->integration->castSentOrder($order, $items, $frete);
					$json_data = json_encode($req_order);
					echo "Erro na respota do ". $int_to ." - Order: ". $order['id'] . " httpcode=".$response['httpcode']." RESPOSTA VIA VAREJO: ".print_r($response['content'],true)." \n"; 
					echo "Dados enviados=".print_r($json_data,true)."\n";
					$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido enviado no '.$int_to. " - Order: ". $order['id'] . ' - httpcode: '.$response['httpcode'].' RESPOSTA '.$int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
					continue;
				}
			}
			 
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Envio enviado para '.$int_to."\n";
		} 

	}

	function mandaEnviadoExterno($authorization)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '45';  
		$int_to = $this->getInt_to();
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$int_to,"I");
			return ;
		}

		echo "Enviando Enviado Externo\n";

		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 

			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
				continue;

			}
			$frete = $frete[0];
			
			$items = $this->getSkusOrderItems($authorization, $order['id'], $order['numero_marketplace']);

			$response = $this->integration->sentOrder($authorization, $order, $items,$frete);

			if (($response['httpcode'] < 200 ) || ($response['httpcode'] > 299))  { 
				if (!$this->isSent($authorization, $order['numero_marketplace']))
				{
					$req_order = $this->integration->castSentOrder($order, $items, $frete);
					$json_data = json_encode($req_order);
					echo "Erro na respota do ". $int_to ." - Order: ". $order['id'] . " httpcode=".$response['httpcode']." RESPOSTA VIA VAREJO: ".print_r($response['content'],true)." \n"; 
					echo "Dados enviados=".print_r($json_data,true)."\n";
					$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido enviado no '.$int_to. " - Order: ". $order['id'] . ' - httpcode: '.$response['httpcode'].' RESPOSTA '.$int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
					continue;
				}
			}
			 
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Envio enviado para '.$int_to."\n";
		} 

	}

	function mandaEntregue($authorization)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '60';  
		$int_to = $this->getInt_to();
		
		$ordens_andamento =$this->model_orders->getOrdensByOriginPaidStatus($int_to,$paid_status);
		if (count($ordens_andamento)==0) {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Entregue da '.$int_to,"I");
			return ;
		}
		echo "Enviando Entregue\n";
		foreach ($ordens_andamento as $order) {
			echo 'ordem ='.$order['id']."\n"; 
						
			if ($order['exchange_request']) { 
				echo 'pedido de troca'."\n";
				$order['paid_status'] = 6; // Entregue
				$this->model_orders->updateByOrigin($order['id'],$order);
				continue;
			}

			$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
			if (count($frete)==0) {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
				continue;

			}
			$frete = $frete[0];

			$items = $this->getSkusOrderItems($authorization, $order['id'], $order['numero_marketplace']);

			$response = $this->integration->deliveredOrder($authorization, $order, $items, $frete);

			if (($response['httpcode'] < 200 ) || ($response['httpcode'] > 299))  { 
				if ($this->isSent($authorization, $order['numero_marketplace'])) {
					if (!$this->isDelivered($authorization, $order['numero_marketplace']))
					{
						if (!$this->isReturned($authorization, $order['numero_marketplace'])) {
							$req_order = $this->integration->castSentOrder($order, $items, $frete);
							$json_data = json_encode($req_order);
							echo "[Entregue] Erro na respota do ".$int_to.". httpcode=".$response['httpcode']." RESPOSTA VIA VAREJO: ".print_r($response['content'],true)." \n"; 
							echo "Dados enviados=".print_r($json_data,true)."\n";
							$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido enviado no '.$int_to.  " - Order: ". $order['id'] . ' - httpcode: '.$response['httpcode'].' RESPOSTA '.$int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
							continue;
						}
					}
				}
				else {
					$this->integration->sentOrder($authorization, $order, $items, $frete);
					continue;
				}
			}
			 
			// Pedido entregue
			$order['paid_status'] = 6; // Entregue
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'Aviso de Pedido Entregue para '.$int_to."\n";
		} 
	}

	private function getSkusOrderItems($authorization, $order_id, $numero_marketplace) 
	{
		$item_arr = array();

		$response_order_mkt = $this->integration->getOrder($authorization, $numero_marketplace);

		$items_order = json_decode($response_order_mkt['content'], true)['items'];

		$items = $this->model_orders->getOrdersItemData($order_id);
		foreach ($items as $item) {
			$sql = "select skumkt from prd_to_integration where prd_id = ? and int_to = ?";
			$query = $this->db->query($sql, array($item['product_id'], $this->getInt_to()));
			$record = $query->row_array();

			$skumkt = is_null($item['skumkt']) ? $record['skumkt'] : $item['skumkt'];
			
			$has_variant_in_mkt = false;
			
			if (is_array($items_order)) {
				foreach ($items_order as $item_order) {
					$skuvia = explode('-', $item_order['skuSellerId']);
					if ($skuvia[0] == $skumkt) {
						if (count($skuvia) > 1) {
							$has_variant_in_mkt = true;
						}
					}
				}
			}
			if ($has_variant_in_mkt) {
				if (!(is_null($item['variant'])))
				{
					if ($item['variant'] != '') 
					{
						$skumkt .= "-". $item['variant'];
					}
				}
			}
			
			$skumkt .=  '-' . (int)$item['qty'];

			array_push($item_arr, $skumkt);
		}	

		return $item_arr;
	}

	private function getOrderStatus($authorization, $order_id)
	{
		$response = $this->integration->getOrder($authorization, $order_id);
		if ($response['httpcode']=="200") 
		{
			$order = json_decode($response['content'], true);
			return $order['status'];
		}

		return 'ERR';
	}

	private function isSent($authorization, $order_id)
	{
		return $this->getOrderStatus($authorization, $order_id) == 'SHP';
	}

	private function isDelivered($authorization, $order_id)
	{
		return $this->getOrderStatus($authorization, $order_id) == 'DLV';
	}

	private function isReturned($authorization, $order_id)
	{
		return $this->getOrderStatus($authorization, $order_id) == 'DVC';
	}
}
?>