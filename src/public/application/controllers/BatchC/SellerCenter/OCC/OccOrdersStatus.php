<?php
/*
 * Atualiza os status dos pedidos nos sellerscenter do conecta lá
 * */
require APPPATH . "controllers/BatchC/Marketplace/Conectala/ConectalaIntegration.php";

 class OccOrdersStatus extends BatchBackground_Controller 
{
    public $int_to 					  = '';
	protected $integration            = null;
	protected $integration_data       = null;	
	protected $api_keys               = '';


	public function __construct()
	{
		parent::__construct();

                $this->integration = new ConectalaIntegration();

		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_nfes');
		$this->load->model('model_freights');
		$this->load->model('model_integrations');
		$this->load->model('model_shipping_company');
		$this->load->library('ordersMarketplace');
		
    }
	
	//php index.php BatchC/SellerCenter/OCC/OccOrdersStatus run null ZEMA
	function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return ;
		}

		$this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");
		
		/* faz o que o job precisa fazer */
		$this->int_to = $params;
		if($this->getkeys(1, 0)) {
			$this->mandaNfe($this->api_keys);
			$this->mandaTracking($this->api_keys);
			$this->mandaEnviado($this->api_keys);
			$this->mandaEntregue($this->api_keys);
	        $this->mandaCancelados($this->api_keys);
		}
		   
		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id, $store_id)
	{
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType($company_id, $this->int_to, "CONECTALA", "DIRECT", $store_id);

		if (!is_array($this->integration_data) || empty($this->integration_data))
        {
            echo $msg = "Não foi possível recuperar os dados de integração\n";
            $this->log_data('batch', $log_name, $msg, "E");
            return false;
        }

        $this->api_keys = @json_decode($this->integration_data['auth_data'], true);
		
        if (is_array($this->api_keys)) {
			$this->integration->setUrlApi($this->api_keys['api_url']);
			return true;
		}
        else
            return false;
	}
	
	function mandaNfe($api_keys)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '52';
		$pickupStore = $this->model_settings->getValueIfAtiveByName('occ_pickupstore');
			
		$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($this->int_to, $paid_status);
		
		
		if (count($ordens_andamento) == 0)
		{
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$this->int_to,"I");
			return ;
		}

        foreach ($ordens_andamento as $order)
        {
			echo 'ordem ='.$order['id']."\n"; 
			
			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

			$nfes = $this->model_orders->getOrdersNfes($order['id']);

			if (count($nfes) == 0) 
            {
				echo $msg = 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch', $log_name, $msg, "E");
				continue;
			}

			$nfe = $nfes[0]; 
			
			$frete = $this->model_freights->getFreightsDataByOrderId($order['id']); // leio se tem frete para utilizar mais abaixo	
			$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission']);

			if ($issue_date_o == false)
            {
				$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission'].' 23:00:00');

				if ($issue_date_o == false)
					$issue_date_o = DateTime::createFromFormat('Y-m-d H:i:s',$nfe['date_emission']);
			}

			if ($issue_date_o > (new DateTime))  // ve se a data passou da hora de agora.
				$issue_date_o = new DateTime;

			$data_pago = DateTime::createFromFormat('Y-m-d H:i:s',$order['data_pago']);

			if ($data_pago > $issue_date_o) 
            {
				$data_pago->add(new DateInterval('PT15M')); // somo 15 Minutos
				$issue_date_o = $data_pago;  // evitar problema de enviar nota fiscal com data de nota fiscal menor que a data de pagamento
			}

			$issue_date = $issue_date_o->format('Y-m-d H:i:s');
			$issue_date = str_replace(" ","T",$issue_date)."-03:00";

			$split_order = explode("-", $order['numero_marketplace']);
			$shippingGroup = $split_order[1];
			$order_pai = $split_order[0];
			$AllOrders = $this->model_orders->getAllOrdersDatabyBill($this->int_to, $order_pai);

			$occ_order_invoice = Array(
				'id' => $order['bill_no'],
				'shippingGroups' => Array(
					Array(
						"description"=> $order['bill_no'],
						"state" => "PROCESSING",
						"id" => $shippingGroup,
						"specialInstructions" => Array (
							'order_number' => $order['bill_no'],
                            'invoce_number' => $nfe['nfe_num'],
                            'price' => (empty(trim($nfe['nfe_value']))) ? $order['gross_amount'] : $nfe['nfe_value'],
                            'serie' => $nfe['nfe_serie'],
                            'access_key' => $nfe['chave'],
                            'emission_datetime' => date('d/m/Y H:i:s', strtotime(str_replace("/", "-", $nfe['date_emission'])))
						)

					)
				)
			);

			//preciso enviar todos os shippingGroups
			foreach($AllOrders as $singleOrder){
				if($singleOrder['id'] == $order['id']){
					continue;
				}
				$splited = explode("-", $singleOrder['numero_marketplace']);
				$sg = $splited[1];
				$occ_order_invoice['shippingGroups'] = array_merge($occ_order_invoice['shippingGroups'], 
				[
					Array(
						"description"=> $order['bill_no'],
						"id" => $sg
						)
				]
			);
			}
			

			$url = "/ccadmin/v1/orders/".$order['bill_no'];

			if(ENVIRONMENT == 'development' || ENVIRONMENT == 'testing'){
				$url = "/ccadmin/v1/orders/".$order['bill_no']."?preview=true";
			}
			
			//envia para occ arthur
			$resp = $this->process($this->int_to, $url, 'PUT', json_encode($occ_order_invoice));		

			var_dump($this->result);
			
			if (($this->responseCode != "200") && ($this->responseCode != "201"))
            {  
                // created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$this->responseCode." RESPOSTA: ".print_r($this->result,true)." \n"; 
				echo "Dados enviados=".print_r($occ_order_invoice,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$this->responseCode.' RESPOSTA '.$this->int_to.': '.print_r($this->result,true).' DADOS ENVIADOS:'.print_r($occ_order_invoice,true),"E");
				continue;
			}
			
			if($pickupStore == $order['ship_company_preview']){
				$order['paid_status'] = 43; //quando for retire em loja paid status vai para 43 
				$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			}else{
				$order['paid_status'] = 50; // agora tudo certo para contratar frete 
				$order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			}
			
			$this->model_orders->updateByOrigin($order['id'],$order);
			echo 'NFE enviado para '.$this->int_to."\n";
		} 
	}
	
	
	function mandaTracking($api_keys)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 51 Ordens que já tem contrato de frete
		$paid_status = '51';		
		
		$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($this->int_to, $paid_status);

		if (count($ordens_andamento)==0)
		{
			// $this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Tracking da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order)
         {
			echo 'ordem ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}
			
			$nfes = $this->model_orders->getOrdersNfes($order['id']);
	
			if (count($nfes) == 0) 
            {
				echo 'ERRO: pedido '.$order['id'].' não tem nota fiscal'."\n";
				$this->log_data('batch',$log_name, 'ERRO: pedido '.$order['id'].' não tem nota fiscal',"E");
				// ainda não cadastraram nfe para este pedido nao deveria estar no Status = 50 
				continue;
			}

			$nfe = $nfes[0]; 
			
			$frete = $this->model_freights->getFreightsDataByOrderId($order['id']);

			if (count($frete) == 0)
            {
				echo "Sem frete/rastreio \n"; 
				// Não tem frete, não deveria aconter
				$this->log_data('batch',$log_name,'ERRO: Sem frete para a ordem '.$order['id'],"E");
				continue;

			}

			$frete = $frete[0];
			
			$carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/';
			if (!empty($frete['url_tracking'])) {
				// $transportadora = $this->model_shipping_company->getShippingCompanyByCnpjAndStore($frete['CNPJ'], $order['store_id']);
				// if ($transportadora) {
				// 	if (!empty($transportadora['tracking_web_site'])) {
				// 		$carrier_url = $transportadora['tracking_web_site'];
				// 	}
				// }
				$carrier_url = $frete['url_tracking'];
			}

			$send_track_code = $this->model_settings->getValueIfAtiveByName('send_tracking_code_to_mkt');
			if ($send_track_code === false) {
				$send_track_code = 0;
			}

			$sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');			


			$split_order = explode("-", $order['numero_marketplace']);
			$shippingGroup = $split_order[1];
			$order_pai = $split_order[0];
			$AllOrders = $this->model_orders->getAllOrdersDatabyBill($this->int_to, $order_pai);

			$occ_order_update = Array(
				'id' => $order['bill_no'],
				'shippingGroups' => Array(
					Array(
						"description"=> $order['bill_no'],
						"state" => "PENDING_SHIPMENT",
						"id" => $shippingGroup,
						"specialInstructions" => Array(		
							//invoice data
							'order_number' => $order['bill_no'],
                            'invoce_number' => $nfe['nfe_num'],
                            'price' => (empty(trim($nfe['nfe_value']))) ? $order['gross_amount'] : $nfe['nfe_value'],
                            'serie' => $nfe['nfe_serie'],
                            'access_key' => $nfe['chave'],
                            'emission_datetime' => date('d/m/Y H:i:s', strtotime(str_replace("/", "-", $nfe['date_emission']))),

							//tracking data
							
							'tracking_carrier' 	    => $frete['ship_company'],
							'tracking_url'			=> $carrier_url,
							'tracking_code' 			=> $frete['codigo_rastreio'],
							'tracking_method' 			=> $frete['method'],
						)
					)
				)
			);

			//preciso enviar todos os shippingGroups
			foreach($AllOrders as $singleOrder){
				if($singleOrder['id'] == $order['id']){
					continue;
				}
				$splited = explode("-", $singleOrder['numero_marketplace']);
				$sg = $splited[1];
				$occ_order_update['shippingGroups'] = array_merge($occ_order_update['shippingGroups'], 
					[
					Array(
							"description"=> $order['bill_no'],
							"id" => $sg
						)
					]
				);
			}

			$url = "/ccadmin/v1/orders/".$order['bill_no'];

			if(ENVIRONMENT == 'development' || ENVIRONMENT == 'testing'){
				$url = "/ccadmin/v1/orders/".$order['bill_no']."?preview=true";
			}
						
			//envia tracking occ
			$resp = $this->process($this->int_to, $url, 'PUT', json_encode($occ_order_update));		

			var_dump($this->result);

			if (($this->responseCode != "200") && ($this->responseCode != "201"))  
            {  
				// created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$this->responseCode." RESPOSTA: ".print_r($this->result,true)." \n"; 
				echo "Dados enviados=".print_r($occ_order_update,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação de tracking pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$this->responseCode.' RESPOSTA '.$this->int_to.': '.print_r($this->result,true).' DADOS ENVIADOS:'.print_r($tracking,true),"E");
				continue;
			}else{
				$msgerror = json_decode($response['content'],true);
				if ($msgerror['message'] == "Erro ao enviar tracking") { // alguém avançou direto no marketplace. 
					$order['paid_status'] = 53; // tracking enviado
					$this->model_orders->updateByOrigin($order['id'], $order);
					echo 'Tracking enviado para '.$this->int_to."\n";
					continue;
				}
			}
			// Pedido entregue
			$order['paid_status'] = 53; // tracking enviado
			$this->model_orders->updateByOrigin($order['id'], $order);
			echo 'Tracking enviado para '.$this->int_to."\n";
		} 
	}

	
	function mandaEnviado($api_keys)
	{
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;

		$pickupStore = $this->model_settings->getValueIfAtiveByName('occ_pickupstore');
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '55';  
		
		$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($this->int_to, $paid_status);

		if (count($ordens_andamento) == 0) 
		{
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Enviado da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order)
		{
			echo 'ordem ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

			$split_order = explode("-", $order['numero_marketplace']);
			$shippingGroup = $split_order[1];
			$order_pai = $split_order[0];
			$AllOrders = $this->model_orders->getAllOrdersDatabyBill($this->int_to, $order_pai);

			$occ_order_update = Array(
				'id' => $order['bill_no'],
				'shippingGroups' => Array(
					Array(
						"description"=> $order['bill_no'],
						"id" => $shippingGroup,
						"submittedDate" => dateFormat($order['data_envio'], 'Y-m-d\TH:i:s\Z', null)
					)
				)
			);

			//preciso enviar todos os shippingGroups
			foreach($AllOrders as $singleOrder){
				if($singleOrder['id'] == $order['id']){
					continue;
				}
				$splited = explode("-", $singleOrder['numero_marketplace']);
				$sg = $splited[1];
				$occ_order_update['shippingGroups'] = array_merge($occ_order_update['shippingGroups'], 
					[
					Array(
							"description"=> $order['bill_no'],
							"id" => $sg
						)
					]
				);
			}		

			$url = "/ccadmin/v1/orders/".$order['bill_no'];

			if(ENVIRONMENT == 'development' || ENVIRONMENT == 'testing'){
				$url = "/ccadmin/v1/orders/".$order['bill_no']."?preview=true";
			}
			
			//envia tracking occ
			$resp = $this->process($this->int_to, $url, 'PUT', json_encode($occ_order_update));		

			var_dump($this->result);
			//$this->responseCode
		
			if (($this->responseCode != 200) && ($this->responseCode != "201"))  
            {  
				// created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$this->responseCode." RESPOSTA: ".print_r($this->result,true)." \n"; 
				echo "Dados enviados=".print_r($occ_order_update,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO no envio para Enviado pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$this->responseCode.' RESPOSTA '.$this->int_to.': '.print_r($this->result,true).' DADOS ENVIADOS:'.print_r($occ_order_update,true),"E");
				continue;
			}
			 
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 

			if($pickupStore == $order['ship_company_preview']){
				$order['paid_status'] = 58;
			}
			$this->model_orders->updateByOrigin($order['id'], $order);
			echo 'Aviso de Envio enviado para '.$this->int_to."\n";
		} 

	}


	function mandaEntregue($api_keys)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 55, ordens que já tem mudaram o status para enviado no FreteRastrear
		$paid_status = '60';
		
		$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($this->int_to, $paid_status);

		if (count($ordens_andamento) == 0)
        {
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de mudar status para Entregue da '.$this->int_to,"I");
			return ;
		}
		
		foreach ($ordens_andamento as $order)
         {
			echo 'ordem ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

			$split_order = explode("-", $order['numero_marketplace']);
			$shippingGroup = $split_order[1];
			$order_pai = $split_order[0];
			$AllOrders = $this->model_orders->getAllOrdersDatabyBill($this->int_to, $order_pai);

			$occ_order_update = Array(
				'id' => $order['bill_no'],
				'shippingGroups' => Array(
					Array(
						"description"=> $order['bill_no'],
						"id" => $shippingGroup,
						"state" => "NO_PENDING_ACTION"
					)
				)
			);

			//preciso enviar todos os shippingGroups
			foreach($AllOrders as $singleOrder){
				if($singleOrder['id'] == $order['id']){
					continue;
				}
				$splited = explode("-", $singleOrder['numero_marketplace']);
				$sg = $splited[1];
				$occ_order_update['shippingGroups'] = array_merge($occ_order_update['shippingGroups'], 
					[
					Array(
							"description"=> $order['bill_no'],
							"id" => $sg
						)
					]
				);
			}

			$url = "/ccadmin/v1/orders/".$order['bill_no'];

			if(ENVIRONMENT == 'development' || ENVIRONMENT == 'testing'){
				$url = "/ccadmin/v1/orders/".$order['bill_no']."?preview=true";
			}
			
			//envia tracking occ
			$resp = $this->process($this->int_to, $url, 'PUT', json_encode($occ_order_update));		

			var_dump($this->result);
			//$this->responseCode
		
			if (($this->responseCode != 200) && ($this->responseCode != "201"))  
            {  
				
				echo "Erro na respota do ".$this->int_to.". httpcode=".$this->responseCode." RESPOSTA: ".print_r($this->result,true)." \n"; 
				echo "Dados enviados=".print_r($occ_order_update,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na Entrega para Enviado pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$this->responseCode.' RESPOSTA '.$this->int_to.': '.print_r($this->result,true).' DADOS ENVIADOS:'.print_r($data,true),"E");
				continue;
			}
			 
			// Pedido entregue
			$order['paid_status'] = 6; // Entregue
			$this->model_orders->updateByOrigin($order['id'], $order);
			echo 'Aviso de Pedido Entregue para '.$this->int_to."\n";
		} 
	}

    function mandaCancelados($api_keys)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 99, ordens que canceladas que tem que ser avisadas no Marketplace
		$paid_status = '99';  
		
		$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($this->int_to, $paid_status);

		if (count($ordens_andamento) == 0)
        {
			$this->log_data('batch', $log_name, 'Nenhuma ordem a cancelar '.$this->int_to, "I");
			return ;
		}
		
		foreach ($ordens_andamento as $order)
        {
			echo 'Cancelando pedido ='.$order['id']."\n"; 

			$orderTroca = $this->ordersmarketplace->updateOrderTroca($order['id'], $order['numero_marketplace'],$order['paid_status']);
				
			if($orderTroca){
				echo "Pedido de Troca atualizado, seguindo para o proximo item\n";
			    continue;
			}

			$split_order = explode("-", $order['numero_marketplace']);
			$shippingGroup = $split_order[1];
			$order_pai = $split_order[0];
			$AllOrders = $this->model_orders->getAllOrdersDatabyBill($this->int_to, $order_pai);

			$occ_order_update = Array(
				'id' => $order['bill_no'],
				'shippingGroups' => Array(
					Array(
						"description"=> $order['bill_no'],
						"id" => $shippingGroup,
						"state" => "FAILED"
					)
				)
			);

			//preciso enviar todos os shippingGroups
			foreach($AllOrders as $singleOrder){
				if($singleOrder['id'] == $order['id']){
					continue;
				}
				$splited = explode("-", $singleOrder['numero_marketplace']);
				$sg = $splited[1];
				$occ_order_update['shippingGroups'] = array_merge($occ_order_update['shippingGroups'], 
					[
					Array(
							"description"=> $order['bill_no'],
							"id" => $sg
						)
					]
				);
			}			

			$url = "/ccadmin/v1/orders/".$order['bill_no'];

			if(ENVIRONMENT == 'development' || ENVIRONMENT == 'testing'){
				$url = "/ccadmin/v1/orders/".$order['bill_no']."?preview=true";
			}
			
			//envia tracking occ
			$resp = $this->process($this->int_to, $url, 'PUT', json_encode($occ_order_update));		

			var_dump($this->result);

			if (($this->responseCode != 200) && ($this->responseCode != "201"))  
            {  
				// created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$this->responseCode." RESPOSTA: ".print_r($response['content'],true)." \n"; 
				echo "Dados enviados=".print_r($invoiced,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO no envio para Cacnelado pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$this->responseCode.' RESPOSTA '.$this->int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($data,true),"E");
				continue;
			}

			$this->ordersmarketplace->cancelOrder($order['id'], true);
			
			echo 'Cancelado em '.$this->int_to."\n";
			$callback_cancel = $this->model_settings->getValueIfAtiveByName('callback_cancel_occ');
			if($callback_cancel){

				$url = $callback_cancel.$order['bill_no'];
			
				//envia tracking occ
				$resp = $this->process($this->int_to, $url, 'GET');		
	
				var_dump($this->result);

			}

		} 

	}



	protected function auth( $endPoint, $authToken )
    {
  	
	    $this->header = [
	        'content-type: application/x-www-form-urlencoded',
	        'Authorization: Bearer '.$authToken,
	    ];

        $url = 'https://'.$endPoint.'/ccadmin/v1/login?grant_type=client_credentials';
		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//testar local
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);

		//curl_setopt($ch, CURLOPT_VERBOSE, true);
        $result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		
        curl_close($ch);
        $result = json_decode($result);
        return $result->access_token;
    }

    protected function process($integrationThe, $endPoint, $method = 'GET', $data = null, $integration_id = null )
    {
    	if (is_null($integration_id))  {
    		$integrationData         = $this->model_integrations->getIntegrationsbyName($integrationThe);
			$separateIntegrationData = json_decode($integrationData[0]['auth_data']);
    	}
		else {
			$integrationData         = $this->model_integrations->getIntegrationsData($integration_id);
			$separateIntegrationData = json_decode($integrationData['auth_data']);
		}
       
	   	
        //$this->accountName = $separateIntegrationData->accountName;
        $credentials = $this->auth( $separateIntegrationData->site, $separateIntegrationData->apikey );

        $this->header = [
            'content-type: application/json; charset=UTF-8',
            'Authorization: Bearer '.$credentials,
            'X-CCAsset-Language: pt-BR'
        ];

        $url = 'https://'.$separateIntegrationData->site.$endPoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

		//curl_setopt($ch, CURLOPT_VERBOSE, true);
        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		
        curl_close($ch);
        if (($this->responseCode == 429) || ($this->responseCode == 504)) {
            echo "site ".$url." deu ".$this->responseCode." dormindo 50 segundos\n";
            sleep(60);
            return $this->process($integrationThe, $endPoint, $method , $data , $integration_id);
        }
        return;
    }

}
?>