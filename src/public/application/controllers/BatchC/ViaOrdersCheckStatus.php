<?php

require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

class ViaOrdersCheckStatus extends BatchBackground_Controller {
		
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
		$this->syncPartialStatus($authorization);
		$this->syncSent($authorization);

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function syncPartialStatus($authorization) 
	{
		echo '[syncPartialStatus][INICIO]'. PHP_EOL;
		$this->syncPartialSent($authorization);
		$this->syncPartialDelivered($authorization);
		echo '[syncPartialStatus][FIM]'. PHP_EOL;
	}

	private function syncPartialSent($authorization)
	{
		echo '[syncPartialSent][INICIO]'. PHP_EOL;

		$orders = $this->model_orders->getOrdersStatusMkt($this->getInt_to(), 'PSH');

		foreach ($orders as $order) 
		{
			$this->resendOrderSent($authorization, $order);
		}

		echo '[syncPartialSent][FIM]'. PHP_EOL;
	}

	private function resendOrderSent($authorization, $order) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'ordem ='.$order['id']."\n"; 

		$nfes = $this->model_orders->getOrdersNfes($order['id']);
		if (count($nfes) == 0) {
			echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
			$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
			// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
			return ;
		}
		$nfe = $nfes[0]; 
		
		$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
		if (count($frete)==0) {
			echo "Sem frete/rastreio \n"; 
			// Não tem frete, não deveria aconter
			$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
			return ;
		}
		$frete = $frete[0];
		
		$items = $this->getSkusOrderItems($authorization, $order['id'], $order['numero_marketplace']);

		$response = $this->integration->sentOrder($authorization, $order, $items, $nfe, $frete);

		if (!($response['httpcode']=="201") )  {  // created
			$int_to = $this->getInt_to();

			$req_order = $this->integration->castSentOrder($order, $items, $nfe, $frete);
			$json_data = json_encode($req_order);
			echo "Erro na respota do ". $int_to ." - Order: ". $order['id'] . " httpcode=".$response['httpcode']." RESPOSTA VIA VAREJO: ".print_r($response['content'],true)." \n"; 
			echo "Dados enviados=".print_r($json_data,true)."\n";
			$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido enviado no '.$int_to. " - Order: ". $order['id'] . ' - httpcode: '.$response['httpcode'].' RESPOSTA '.$int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return;
		}
	}

	private function syncSent($authorization)
	{
		echo '[syncSent][INICIO]'. PHP_EOL;

		$orders = $this->model_orders->getOrdersStatusMktAndPaidStatus($this->getInt_to(), 'PAY', 5);

		foreach ($orders as $order) 
		{
			$this->resendOrderSent($authorization, $order);
		}

		$orders = $this->model_orders->getOrdersStatusMktAndPaidStatus($this->getInt_to(), 'PAY', 60);

		foreach ($orders as $order) 
		{
			$this->resendOrderSent($authorization, $order);
		}

		$orders = $this->model_orders->getOrdersStatusMktAndPaidStatus($this->getInt_to(), 'PAY', 6);

		foreach ($orders as $order) 
		{
			$this->resendOrderSent($authorization, $order);
		}

		$orders = $this->model_orders->getOrdersStatusMktAndPaidStatus($this->getInt_to(), 'SHP', 6);

		foreach ($orders as $order) 
		{
			$this->resendOrderDelivered($authorization, $order);
		}

		echo '[syncSent][FIM]'. PHP_EOL;
	}

	private function syncPartialDelivered($authorization)
	{
		echo '[syncPartialDelivered][INICIO]'. PHP_EOL;

		$orders = $this->model_orders->getOrdersStatusMkt($this->getInt_to(), 'PDL');

		foreach ($orders as $order) 
		{
			$this->resendOrderSent($authorization, $order);
			$this->resendOrderDelivered($authorization, $order);
		}

		echo '[syncPartialDelivered][FIM]'. PHP_EOL;
	}

	private function resendOrderDelivered($authorization, $order) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'ordem ='.$order['id']."\n"; 

		$nfes = $this->model_orders->getOrdersNfes($order['id']);
		if (count($nfes) == 0) {
			echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
			$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
			// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
			return ;
		}
		$nfe = $nfes[0]; 
		
		$frete=$this->model_freights->getFreightsDataByOrderId($order['id']);
		if (count($frete)==0) {
			echo "Sem frete/rastreio \n"; 
			// Não tem frete, não deveria aconter
			$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
			return ;
		}
		$frete = $frete[0];

		$items = $this->getSkusOrderItems($authorization, $order['id'], $order['numero_marketplace']);

		$response = $this->integration->deliveredOrder($authorization, $order, $items, $nfe, $frete);

		if (!($response['httpcode']=="201") )  {  // created
			$int_to = $this->getInt_to();
			$req_order = $this->integration->castDeliveredOrder($order, $items, $nfe, $frete);
			$json_data = json_encode($req_order);
			echo "Erro na respota do ". $int_to ." - Order: ". $order['id'] . " httpcode=".$response['httpcode']." RESPOSTA VIA VAREJO: ".print_r($response['content'],true)." \n"; 
			echo "Dados enviados=".print_r($json_data,true)."\n";
			$this->log_data('batch',$log_name, 'ERRO na marcacao de pedido entregue no '.$int_to. " - Order: ". $order['id'] . ' - httpcode: '.$response['httpcode'].' RESPOSTA '.$int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return;
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

			foreach ($items_order as $item_order) {
				$skuvia = explode('-', $item_order['skuSellerId']);
				if ($skuvia[0] == $skumkt) {
					if (count($skuvia) > 1) {
						$has_variant_in_mkt = true;
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
