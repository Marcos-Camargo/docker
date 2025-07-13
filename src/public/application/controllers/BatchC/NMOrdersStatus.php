<?php

require 'NovoMundo/NMIntegration.php';


class NMOrdersStatus extends BatchBackground_Controller 
{
    public $int_to 					  = 'NM';
	protected $integration            = null;
	protected $integration_data       = null;	
	protected $api_keys               = '';

	public function __construct()
	{
		parent::__construct();

        $this->integration = new NMIntegration();

		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_nfes');
		$this->load->model('model_freights');
		$this->load->model('model_integrations');
		$this->load->model('model_shipping_company');
		$this->load->library('ordersMarketplace');
		
    }
	

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__))
		{
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		if($this->getkeys(1, 0))

		/* faz o que o job precisa fazer */
		$this->mandaNfe($this->api_keys);
		$this->mandaTracking($this->api_keys);
		$this->mandaEnviado($this->api_keys);
		$this->mandaEntregue($this->api_keys);
        $this->mandaCancelados($this->api_keys);

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
			
		$ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($this->int_to, $paid_status);
		
		if (count($ordens_andamento) == 0)
		{
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de Nfe da '.$this->int_to,"I");
			return ;
		}

        foreach ($ordens_andamento as $order)
        {
			echo 'ordem ='.$order['id']."\n"; 			

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

			$invoiced = Array (
                    'nfe' => array(
                         array(
                            'order_number' => $order['bill_no'],
                            'invoce_number' => $nfe['nfe_num'],
                            'price' => (empty(trim($nfe['nfe_value']))) ? $order['gross_amount'] : $nfe['nfe_value'],
                            'serie' => $nfe['nfe_serie'],
                            'access_key' => $nfe['chave'],
                            // 'emission_datetime' => date('Y-m-d H:i:s', strtotime(str_replace("/", "-", $nfe['date_emission']))),
                            'emission_datetime' => date('d/m/Y H:i:s', strtotime(str_replace("/", "-", $nfe['date_emission']))),
                            'collection_date' => ''
                        )
                    )
                );
			
			/* BUGS-657
			$json_data = json_encode($invoiced);
			$url = $this->api_url.'Orders/nfe';
			$resp = $this->postNovoMundo($url, $json_data, $this->api_keys);
			*/
			
			$resp = $this->integration->nfesOrder($this->api_keys,  $invoiced);		

			// var_dump($resp);
			
			if ($resp['httpcode'] != "200")
            {  
                // created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$resp['httpcode']." RESPOSTA: ".print_r($resp['content'],true)." \n"; 
				echo "Dados enviados=".print_r($invoiced,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->int_to.': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}

            $order['paid_status'] = 50; // agora tudo certo para contratar frete 
            $order['envia_nf_mkt'] = date('Y-m-d H:i:s');
			
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
			
			$nfes = $this->model_orders->getOrdersNfes($order['id']);
			die;
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

			$items = $this->getSkusOrderItems($this->api_keys, $order['id'], $order['numero_marketplace']);

			$item_track = array();
			foreach ($items as $item) {
				$item_track[] = array(
					'sku'				=> $item['sku'],
					'qty'				=> $item['qty'],
					'code' 				=> $frete['codigo_rastreio'],
					'method' 			=> $frete['method'],
					'service_id' 		=> $frete['idservico'],
					'value' 			=> $frete['ship_value'],
					'delivery_date' 	=> $frete['prazoprevisto'],
					'url_label_a4'		=> $frete['link_etiqueta_a4'],
					'url_label_thermic' => $frete['link_etiqueta_termica'],
					'url_label_zpl' 	=> $frete['link_etiquetas_zpl'],
					'url_plp' 			=> $frete['link_plp'],
				);
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
			
			$tracking = array (
				'tracking' => array (
					'date_tracking' 	=> date('Y-m-d H:i:s'),
					'track' => array(
						'carrier' 	    => $frete['ship_company'],
						'carrier_cnpj'	=> $frete['CNPJ'],
						'url'			=> $carrier_url
					) 
				)
			);

			$response = $this->integration->nfesOrder($this->api_keys,  $tracking);

			if (!($response['httpcode'] == "200"))  
            {  
				// created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$response['httpcode']." RESPOSTA: ".print_r($response['content'],true)." \n"; 
				echo "Dados enviados=".print_r($invoiced,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na gravação da NFE pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$response['httpcode'].' RESPOSTA '.$this->int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($tracking,true),"E");
				continue;
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
		
			$data = array('shipped_date' => $order['data_envio']);

			$response = $this->integration->sentOrderNew($api_keys, $order, $data);

			if ($response['httpcode'] != 200)  
            {  
				// created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$response['httpcode']." RESPOSTA: ".print_r($response['content'],true)." \n"; 
				echo "Dados enviados=".print_r($invoiced,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO no envio para Enviado pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$response['httpcode'].' RESPOSTA '.$this->int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($data,true),"E");
				continue;
			}
			 
			// Avisado que foi entregue na transportadora 
			$order['paid_status'] = 5; // agora tudo certo para com enviado normal e ficar no rastreio. 
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
			
			$data = array('delivered_date' => $order['data_entrega']);

			$response = $this->integration->sentOrderNew($api_keys, $order, $data);

			if ($response['httpcode'] != 200)  
            {  
				// created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$response['httpcode']." RESPOSTA: ".print_r($response['content'],true)." \n"; 
				echo "Dados enviados=".print_r($invoiced,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO na Entrega para Enviado pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$response['httpcode'].' RESPOSTA '.$this->int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($data,true),"E");
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
					
            $sql = "SELECT reason FROM canceled_orders WHERE order_id = ".$order['id'];
			$query = $this->db->query($sql);
            $status = $query->result_array();

            $cancel = array ('status' => (!empty($status[0]['reason'])) ? $status[0]['reason'] : 'order_canceled');

            $data = array(
             	'order' => array(
                	'date' => date('Y-m-d H:i:s'),
                	'reason' => (!empty($status[0]['reason'])) ? $status[0]['reason'] : 'order_canceled'
				)
            );

            $response = $this->integration->cancelOrder($api_keys, $order, $data);

			if (($response['httpcode'] != 200) && ($response['httpcode'] != 400)) 
            {  
				// created
				echo "Erro na respota do ".$this->int_to.". httpcode=".$response['httpcode']." RESPOSTA: ".print_r($response['content'],true)." \n"; 
				echo "Dados enviados=".print_r($invoiced,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO no envio para Cacnelado pedido '.$order['id'].' '.$order['numero_marketplace'].' no '.$this->int_to.' 
                                - httpcode: '.$response['httpcode'].' RESPOSTA '.$this->int_to.': '.print_r($response['content'],true).' DADOS ENVIADOS:'.print_r($data,true),"E");
				continue;
			}
			$this->ordersmarketplace->cancelOrder($order['id'], true);
			
			echo 'Cancelado em '.$this->int_to."\n";
		} 

	}


	private function getSkusOrderItems($api_keys, $order_id, $numero_marketplace) 
	{
		$item_arr = array();

		$response_order_mkt = $this->integration->getOrder($api_keys, $order_id);

		$items_order = json_decode($response_order_mkt['content'], true);

		$items = $this->model_orders->getOrdersItemData($order_id);

		foreach ($items as $item) 
        {
			$sql = "select skumkt from prd_to_integration where prd_id = ? and int_to = ?";
			$query = $this->db->query($sql, array($item['product_id'], $this->int_to));
			$record = $query->row_array();

			$skumkt = is_null($item['skumkt']) ? $record['skumkt'] : $item['skumkt'];
			
			$has_variant_in_mkt = false;

			foreach ($items_order as $item_order)
             {
				$skunm = explode('-', $item_order['skuSellerId']);
				if ($skunm[0] == $skumkt) 
                {
					if (count($skunm) > 1)
                    {
						$has_variant_in_mkt = true;
					}
				}
			}

			if ($has_variant_in_mkt) 
            {
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


	private function getOrderStatus($api_keys, $order_id)
	{
		$response = $this->integration->getOrder($api_keys, $order_id);
		if ($response['httpcode']=="200") 
		{
			$order = json_decode($response['content'], true);
			return $order['status'];
		}

		return 'ERR';
	}


	private function isSent($api_keys, $order_id)
	{
		return $this->getOrderStatus($api_keys, $order_id) == 'SHP';
	}


	private function isDelivered($api_keys, $order_id)
	{
		return $this->getOrderStatus($api_keys, $order_id) == 'DLV';
	}


	private function isReturned($api_keys, $order_id)
	{
		return $this->getOrderStatus($api_keys, $order_id) == 'DVC';
	}

    
    function postNovoMundo($url, $post_data, $api_keys, $type = 'POST')
    {	
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => $this->getHttpHeader($api_keys)
        ));

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


    private function getHttpHeader($api_keys) 
    {
        if (empty($api_keys))
            return false;
            
        $keys = array();

        foreach ($api_keys as $k => $v)
        {
            if ($k != 'api_url' && $k != 'int_to')
                $keys[] = $k.':'.$v;
        }

        return $keys;        
    }

}
?>
