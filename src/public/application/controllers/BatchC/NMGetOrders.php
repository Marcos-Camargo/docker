<?php
/*
 
Atualiza pedidos que chegaram na Novo Mundo SC

*/   

require 'NovoMundo/NMIntegration.php';


class NMGetOrders extends BatchBackground_Controller 
{
	
	public $int_to 					= 'NM';
	public $int_to_id				= '1';
	public $url_api 				= null;
	public $api_keys 				= '';
	protected $integration 			= null;
	protected $integration_data 	= null;
	protected $cant_cancel_status 	= array(5,6,45,55,60,96,97,98,99);
	

	public function __construct()
	{
		parent::__construct();
			
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_integration_last_post');
		$this->load->model('model_promotions');
		$this->load->model('model_category');
		$this->load->model('model_freights');
		$this->load->library('ordersMarketplace');

		$this->integration = new NMIntegration();
	}


	function getInt_to()
	{
		return $this->int_to;
	}


	function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) 
        {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return ;
		}

		$this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");
		
		/* faz o que o job precisa fazer */
		if($this->getkeys(1, 0)) {
			$this->getorders();
			echo "Verificando os cancelados\n";
			$this->syncCancelled();
		}
		   
		
		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}


    function getDays($date_start = false, $date_end = false)
    {
        if(!$date_start || !$date_end)
            return false;

        $date_today         = new DateTime($date_start);
        $date_estimated     = new DateTime($date_end);
        $date_interval      = $date_today->diff($date_estimated);
        return $date_interval->days;
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

    private function validarItensPedido($items) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;		
        $isValid = true;
        $store_id = null;
        $msg_return = null;
        foreach ($items as $k => $item)
        {
            if ($isValid === false) continue;

            $arr = $this->validarItemPedido($item, $store_id);
            $isValid = $arr[0];
            $integration_last_post = $arr[1];
            
            if ($isValid) {
                if ($store_id == null) {
                    $store_id = $integration_last_post['store_id'];
                }

                if ($store_id != $integration_last_post['store_id']) {
                    $isValid = false;
                    echo $msg = "Pedido com produtos de lojas diferente. Cancelar Pedido \n";
                    $this->log_data('batch ',$log_name, $msg, "E");
                    $msg_return = $msg;
                }
            }
            else  {
                $msg_return = $arr[2];
            }
        }   
        return [$isValid, $msg_return, $store_id];
    }

    private function validarItemPedido($item) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;		
        $sku = $item['sku'];

        $integration_last_post = $this->model_integration_last_post->getDataBySkulocalAndIntto($sku, $this->int_to);

        if (is_null($integration_last_post))
        {
            echo $msg = "Pedido: ".$content['code']." | marketplace_number: ".$sku." | na loja: ".$store_id." 
            | da empresa: ".$content['company_code']." | nao consta como INT_TO: ".$this->int_to." | na tabela integration_last_post \n";
            $this->log_data('batch ',$log_name, $msg, "E");

            $msg_return = "Produto (".$sku.") não encontrado. ";

            return [false, null, $msg_return];
        }

        return [true, $integration_last_post, null];
    }

    private function cancelarPedido($bill_no, $msg_cancela) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;		

        $cancel = $this->integration->cancelSCOrder($this->api_keys, $bill_no, $msg_cancela);

        if ($cancel['http_code'] != 200)
        {
            echo $msg = "Pedido: ".$bill_no." não conseguiu ser cancelado no Seller Center \n";
            $this->log_data('batch ',$log_name, $msg, "E");
            return false;
        }
        else {
            return true;
        }
    }
	
	function syncCancelled()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$response = $this->integration->getOrdersCanceled($this->api_keys);

		if ($response['httpcode'] == 404) {
			echo " Lista acabou \n";
			return;
		} 
		
		if ($response['httpcode'] != 200) 
		{
			echo " Erro URL: ". $url. " httpcode=".$response['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($response,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$response['httpcode'].' RESPOSTA: '.print_r($response,true),"E");
			return;
		}
		
		$response = json_decode($response['content'], true);
		
		echo PHP_EOL . "syncCancelled ". $_offset. "-" .count($response["orders"]) . PHP_EOL;
		
		if ($response['success']) {
			foreach ($response["result"] as $order) 
			{
				$order_exist = $this->model_orders->getOrdersDatabyBill($this->int_to, $order['order_code']);
				if ($order_exist) {
					echo 'Cancelando pedido '.$order_exist['id'].' bill no. '.$order['order_code'].' status '. $order['status']['code']."\n";
					$this->ordersmarketplace->cancelOrder($order_exist['id'], false);
					
					$remove_from_line = $this->integration->removeFromLine($order['order_code'], $this->api_keys);

                    if ($remove_from_line['http_code'] != 200)
                    {                            
                        echo $msg = " Erro REMOÇÃO DA FILA ".$remove_from_line['httpcode']."\n
                                        RESPOSTA: ".print_r($remove_from_line, true)." \n"; 
                        $this->log_data('batch', $log_name, $msg, "E");
                    }
                    else
                    {
                        echo "Pedido ".$order['order_code']." removido com sucesso. \n";
                    }
				}
				else 
				{
					echo "Order não localizada... \n";
				}
				//$this->markCancelled($authorization, $order);
			}
		
		}

	}

    function getorders()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;		
		$this->load->library('calculoFrete');

        $orders_line = [];
		
        //confere a fila         
		$order_line = $this->integration->getNewOrders($this->api_keys);

        if ($order_line['http_code'] == 200)
		{
			$line_array = json_decode($order_line['content'], true);

			if ($line_array['success'] == true && is_array($line_array['result']) && !empty($line_array['result']))
			{					
				foreach ($line_array['result'] as $k => $v)
				{
                    $orders_line[$k]['order_code'] 	= $v['order_code'];
                    $orders_line[$k]['paid_status'] = $v['status']['code'];
                    $orders_line[$k]['updated_at'] 	= $v['updated_at'];
                    $orders_line[$k]['new_order'] 	= $v['new_order'];
				}
			}
            else
            {
                echo $msg = "Fila de Pedidos retornou: quantidade: ".count($line_array['result'])." | resposta API: ".$line_array['success']." \n";
                $this->log_data('batch', $log_name, $msg, "E");
                return false;
            }
		}
		else
		{
			if ($order_line['http_code'] == 404) {
				echo $msg = "Lista de pedidos vazia \n";
            	$this->log_data('batch', $log_name, $msg, "I");
				return false;
			}
            echo $msg = "Erro ao conectar com a API de pedidos, array serializado: ".serialize($order_line)." \n";
            $this->log_data('batch', $log_name, $msg, "E");
			return false;
		}

		if (is_array($orders_line) && !empty($orders_line))
		{
			//inicio do fluxo completo
			foreach($orders_line as $k => $line_item)
			{
                $can_remove_queue = false;
				try {
                    $can_remove_queue = $this->processOrder($line_item);
                }
                finally {
                    if ($can_remove_queue) {
                        $remove_from_line = $this->integration->removeFromLine($line_item['order_code'], $this->api_keys);

                        if ($remove_from_line['http_code'] != 200)
                        {                            
                            echo $msg = " Erro REMOÇÃO DA FILA ".$remove_from_line['httpcode']."\n
                                            RESPOSTA: ".print_r($remove_from_line, true)." \n"; 
                            $this->log_data('batch', $log_name, $msg, "E");
                        }
                        else
                        {
                            echo "Pedido ".$line_item['order_code']." removido com sucesso. \n";
                        }
                    }
                }
			}
		}
        else
        {
            echo $msg = "Lista de pedidos vazia \n";
            $this->log_data('batch', $log_name, $msg, "I");
			return false;
        }

	}

    private function processOrder($line_item) {
        if ((int)$line_item['paid_status'] > 3) return true;

        //pego os dados do produto para adição nas tabelas
        $order = $this->integration->getOrderItem($this->api_keys, $line_item['order_code']);

        if($order['http_code'] == 200)
        {
            $content = json_decode($order['content'], true);

            if(!$content['success'])
                return false;

            $content        = $content['result']['order'];
        
            $status         = $line_item['paid_status'];
            $store_id       = intVal($this->api_keys['x-store-key']);
            $company_id     = $content['company_code'];
            $paid_status    = $line_item['paid_status'];
            $new_order      = $line_item['new_order'];

            $order_data     = [];
            
            $store_id = null;
            //loop nos itens para ver se houve split ou nao existe na tabela
            if ($status == 1) {
                if ($content['items'] && is_array($content['items']))
                {
                    $arr = $this->validarItensPedido($content['items']);
                    $isValid = $arr[0];
                    
                    if ($isValid === false) {
                        $msg_return = $arr[1];
                        if ((int)$content['status']['code'] >= 90) return true;

                        return $this->cancelarPedido($line_item['order_code'], $msg_return);
                    }
                    else {
                        $store_id = $arr[2];
                        $store = $this->model_stores->getStoresData($store_id);

                        if (empty($existing_order = $this->model_orders->getOrdersDatabyBill($this->int_to, $line_item['order_code'])))
                        {
                            return $this->newOrder($content, $store);
                        }
                    }
                }

            }
            else if ($status == 3)
            {
                $parcels = $content['payments']['parcels'];
                $parcel_number = 1;
                $existing_order = $this->model_orders->getOrdersDatabyBill($this->int_to, $line_item['order_code']);

                if (is_array($parcels) && !empty($parcels))
                {
                    //ja que temos parcelas, vou remover o que ja foi registrado previamente no 1o cadastro
                    $sql = "DELETE FROM orders_payment WHERE order_id = ?";
                    $query = $this->db->query($sql, array($existing_order['id']));

                    foreach ($parcels as $k => $parcel)
                    {        
                        $parcel_data = [];                            
                        $parcel_data['order_id'] 			= $existing_order['id']; 
                        $parcel_data['parcela'] 			= $parcel_number++;
                        $parcel_data['bill_no'] 			= $existing_order['bill_no'];
                        $parcel_data['data_vencto'] 		= date('Y-m-d', strtotime($parcel['due_date']));
                        $parcel_data['valor'] 			    = $parcel['value'];
                        $parcel_data['forma_id']	 		= $parcel['payment_method']; // braun <- estou sem esta referência
                        $parcel_data['forma_desc'] 		    = $parcel['payment_method'];

                        $parcel_id = $this->model_orders->insertParcels($parcel_data);

                        if (!$parcel_id) 
                        {
                            $this->log_data('batch', $log_name, 'Erro ao incluir parcelas ',"E");
                            return false;
                        }                                    
                    }
                }                            
                
                if (!$this->model_orders->updatePaidStatus($existing_order['id'], $status)) {
                    echo "Pedido ".$existing_order['id']." NÃO sofreu atualização para faturamento \n";
                    return false;
                }
                else {
                    echo "Pedido ".$existing_order['id']." marcado para faturamento \n";  
                    return true;
                }

            } 

            return false;




            if (!is_null($store_id)) {
                $store = $this->model_stores->getStoresData($store_id);
            }



            $store = $this->model_stores->getStoresData($content['store_code']);

            if (empty($store))
            {
                //TODO - FAZER O Q?
                //se nao foi possivel recuperar dados da loja e sem a loja nao pode prosseguir
                // continue;
            }


            //define se o pedido ja existe no banco ou nao, para incluir ou atualizar
            if (empty($existing_order = $this->model_orders->getOrdersDatabyBill($this->int_to, $line_item['order_code'])))
            {
                //TODO - ADICIONAR NOVA ORDEM
                $this->newOrder($content);
            }
            else
            {
                //se o pedido ja existe, vou atualizar.
                $order_id = $line_item['order_code'];

                if ($status == 1)
                { 
                    // continue;                       
                }
                elseif ($status == 3)
                {
                    $parcels = $content['payments']['parcels'];
                    $parcel_number = 1;

                    if (is_array($parcels) && !empty($parcels))
                    {
                        //ja que temos parcelas, vou remover o que ja foi registrado previamente no 1o cadastro
                        $sql = "DELETE FROM orders_payment WHERE order_id = ?";
                        $query = $this->db->query($sql, array($existing_order['id']));

                        foreach ($parcels as $k => $parcel)
                        {        
                            $parcel_data = [];                            
                            $parcel_data['order_id'] 			= $existing_order['id']; 
                            $parcel_data['parcela'] 			= $parcel_number++;
                            $parcel_data['bill_no'] 			= $existing_order['bill_no'];
                            $parcel_data['data_vencto'] 		= date('Y-m-d', strtotime($parcel['due_date']));
                            $parcel_data['valor'] 			    = $parcel['value'];
                            $parcel_data['forma_id']	 		= $parcel['payment_method']; // braun <- estou sem esta referência
                            $parcel_data['forma_desc'] 		    = $parcel['payment_method'];

                            $parcel_id = $this->model_orders->insertParcels($parcel_data);

                            if (!$parcel_id) 
                            {
                                $this->log_data('batch', $log_name, 'Erro ao incluir parcelas ',"E");
                                continue;
                            }                                    
                        }
                    }                            
                    
                    if (!$this->model_orders->updatePaidStatus($existing_order['id'], $status))
                        echo "Pedido ".$existing_order['id']." NÃO sofreu atualização para faturamento \n";
                    else
                        echo "Pedido ".$existing_order['id']." marcado para faturamento \n";  
                }        
                elseif ($status == 96) 
                {
                    // $cancel = $this->integration->cancelSCOrder($this->api_keys, $existing_order['id'], 'Pedido cancelado. RAZAO: Prazo de pagamento expirado');
                    $cancel = $this->model_orders->updatePaidStatus($existing_order['id'], $status);	
                    echo "Marcado para cancelamento\n";                            
                }
                elseif (in_array($status, array(5, 50)))
                {
                    //segundo a tabela de status, deveria haver intervencao no mktplae
                    echo "Já está enviado, Acerto o status e removo da fila\n";
                    $existing_order['paid_status'] = 5; 
                    $this->model_orders->updateByOrigin($existing_order['id'], $existing_order);   
                }
                elseif (in_array($status, array(6, 60)))
                {
                    //segundo a tabela de status, deveria haver intervencao no mktplae
                    echo "Já está entregue, acerto o status e removo da fila\n";
                    $existing_order['paid_status'] = 6; 
                    $this->model_orders->updateByOrigin($existing_order['id'], $existing_order);  
                }
                else 
                {
                    // alcança esta linha quando o status e de numero inesperado
                    echo $msg ="Pedido ".$existing_order['id']." chegou com status ".$status." e nao possui uma atitude definida para este status \n"; 
                    $this->log_data('batch', $log_name, $msg, "W");
                    // continue;	
                }
            }

            //atualizo na tabela de integrations
            if ($integration = $this->model_orders->updateOrderToIntegrationByOrderAndStatus(
                $existing_order['id'], 
                $store_id, 
                $paid_status, 
                array('new_order' => $new_order)
            ))
                echo "Tabela de orders_to_integration atualizada com sucesso \n";  

                
            //atualizacao do frete
            $estimated_delivery = $this->getDays(date("Y-m-d"), $content['shipping']['estimated_delivery']);

            $valid_days         = $this->somar_dias_uteis(date("Y-m-d"), $estimated_delivery, '');
            $valid_days         = $this->getDays(date("Y-m-d"), $valid_days);

            $delivery           = $this->model_orders->setShipCompanyPreview(
                                        $order_id, 
                                        $content['shipping']['shipping_carrier'], 
                                        $content['shipping']['service_method'], 
                                        $valid_days
                                    );

            if (!$delivery)
            {
                echo $msg = " Erro ao atualizar os dados de frete \n";                        
                $this->log_data('batch', $log_name, $msg, "E");
                // continue;
            }

            //se chegou ate esta linha é pq o processamento ocorreu 100%
    
        }
    }

    private function newOrder($content, $store) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        //registrando o cliente para resgatar o ID
        $customer = [];
        $customer['customer_name'] 		= $content['customer']['name'];	
        $customer['customer_address'] 	= $content['billing_address']['street'];
        $customer['addr_num'] 			= $content['billing_address']['number'];
        $customer['addr_compl'] 		= $content['billing_address']['complement'];	
        $customer['addr_neigh']			= $content['billing_address']['neighborhood'];
        $customer['addr_city'] 			= $content['billing_address']['city'];
        $customer['addr_uf'] 			= $content['billing_address']['region'];
        $customer['country'] 			= $content['billing_address']['country'];
        $customer['zipcode'] 			= $content['billing_address']['postcode'];
        $customer['phone_1'] 			= $content['billing_address']['phone'];
        $customer['origin'] 			= $this->int_to;
        $customer['origin_id'] 			= $this->int_to_id;
        $customer['phone_1'] 			= $content['customer']['phones'][0];
        $customer['phone_2'] 			= $content['customer']['phones'][1];
        $customer['email'] 				= $content['customer']['email'];
        $customer['cpf_cnpj'] 			= $content['customer']['cpf'];
        $customer['ie'] 				= $content['customer']['cpf'];
        $customer['rg'] 				= $content['customer']['rg'];
                        
        if (!$customer_id = $this->model_clients->insert($customer))
        {
            //nao foi possivel cadastrar o cliente, entao nao adianta continuar com o pedido
            echo $msg = "Pedido:  ".$line_item['order_code']." | SC: ".$this->int_to." nao foi 
                        possível cadastrar o cliente. Abortando o registro \n";
            $this->log_data('batch ',$log_name, $msg, "E");
            return false;
        }

        $order_data['bill_no'] 				        = $content['code'];
        $order_data['numero_marketplace']       	= $content['marketplace_number'];
        $order_data['customer_id'] 			        = $customer_id;
        $order_data['customer_name'] 		        = $customer['customer_name'];
        $order_data['customer_address'] 	        = $customer['customer_address'];
        $order_data['customer_phone'] 		        = empty($customer['phone_1']) ? $customer['phone_1'] : $customer['phone_2'];
        $order_data['date_time'] 			        = $content['created_at'];
        $order_data['total_order'] 		        	= floatval($content['payments']['total_products']) - floatval($content['shipping']['shipping_cost']);
        $order_data['discount'] 		        	= $content['payments']['discount'];
        $order_data['net_amount'] 		        	= $content['payments']['gross_amount'] - $content['payments']['discount'] - $content['payments']['service_charge'];
        $order_data['total_ship'] 		        	= ($content['shipping']['shipping_cost'] == null) ? '' : $content['shipping']['shipping_cost'];;
        $order_data['gross_amount'] 		        = $content['payments']['gross_amount'];
        $order_data['service_charge_rate']          = $store['service_charge_value'];  
        $order_data['service_charge'] 		        = $content['payments']['total_products'] * $store['service_charge_value'] / 100;
        $order_data['vat_charge_rate'] 		        = 0;
        $order_data['vat_charge'] 		        	= 0;
        $order_data['paid_status'] 		        	= 1;
        $order_data['user_id'] 			        	= 1;
        $order_data['company_id'] 		        	= $store['company_id'];
        $order_data['origin'] 			        	= $this->int_to;
        $order_data['store_id'] 		        	= $store['id'];
        
        $order_data['customer_address_num']     	= $content['billing_address']['number'];
        $order_data['customer_address_compl']   	= $content['billing_address']['complement'];
        $order_data['customer_address_neigh']	    = $content['billing_address']['neighborhood'];
        $order_data['customer_address_city']	    = $content['billing_address']['city'];
        $order_data['customer_address_uf']	        = $content['billing_address']['region'];
        $order_data['customer_address_zip']	        = $content['billing_address']['postcode'];

        // $order_data['ship_company_preview']	        = $content['shipping']['shipping_carrier'];	//<- correto?
        // $order_data['ship_service_preview']	        = $content['shipping']['service_method'];	//<- correto?
        // $order_data['ship_time_preview']		    = $content['shipping']['service_method'];	//<- correto?
        //$order_data['freight_seller']			    = $store['freight_seller'];
        $order_data['service_charge_freight_value'] = $store['service_charge_freight_value'];

        //finalmente gravo o pedido na tabela order
        $order_id = $this->model_orders->insertOrder($order_data);
        echo "Inserido:".$order_id."\n";

        if (!$order_id)
        {
            $this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
            $this->model_clients->remove($customer_id);
            return false;
        }

        //cadastro as parcelas
        $parcels = $content['payments']['parcels'];
        $counter = 1; //primeira parcela

        if (is_array($parcels) && !empty($parcels))
        {
            foreach ($parcels as $k => $parcel)
            {        
                $parcel_data = [];                            
                $parcel_data['order_id'] 			= $order_id; 
                $parcel_data['parcela'] 			= $counter++;
                $parcel_data['bill_no'] 			= $order_data['bill_no'];
                $parcel_data['data_vencto'] 		= date('Y-m-d', $parcel['due_date']);
                $parcel_data['valor'] 			    = $parcel['value'];
                $parcel_data['forma_id']	 		= $parcel['payment_method']; // braun <- estou sem esta referência
                $parcel_data['forma_desc'] 		    = $parcel['payment_method'];

                $parcs_id = $this->model_orders->insertParcels($parcel_data);

                if (!$parcs_id) 
                {
                    echo $msg = "Erro ao incluir parcelas ".var_dump($parcs_id)." \n";
                    $this->log_data('batch', $log_name, $msg, "E");
                }                                    
            }
        }

        //agora passo ao cadastro dos itens
        if ($content['items'] && is_array($content['items']))
        {
            foreach ($content['items'] as $k => $item)
            {                                
                $variant = '';
                $integration_last_post = $this->model_integration_last_post->getDataBySkulocalAndIntto($item['sku'], $this->int_to);
                if ($integration_last_post['prd_id'] > 0) {
                    $product = $this->model_products->getProductData(0, $integration_last_post['prd_id']);
                }
                $data = []; //dados para include

                if(empty($product))
                {
                    $this->model_clients->remove($customer_id);
                    $this->model_orders->remove($order_id);
                    echo $msg = "Erro ao incluir o item ".$item['product_id']." - Pedido mkt = ".$content['code']." order_id = ".$order_id." removendo para receber novamente \n";
                    $this->log_data('batch', $log_name, $msg, "E");
                    // continue; 
                }

                if ($product['is_kit'] == 0) 
                {
                    $data['order_id']       = $order_id;
                    $data['skumkt']         = $item['sku'];
                    $data['product_id']     = $product['id'] ?? $integration_last_post['prd_id'];
                    $data['sku']            = $product['sku'];
                    $data['variant']        = $variant = $product['has_variants'] != '' ? $item['sku_variation'] : '';
                    $data['name']           = $product['name'];
                    $data['qty']            = $item['qty'];
                    $data['rate']           = $item['original_price'];
                    $data['amount']         = floatVal($item['original_price']) * floatVal($item['qty']);
                    $data['discount']       = floatVal($item['discount']);
                    $data['company_id']     = intVal($product['company_id']); 
                    $data['store_id']       = intVal($product['store_id']); 
                    $data['un']             = $item['unity'];
                    $data['pesobruto']      = $item['gross_weight'];
                    $data['largura']        = $item['width'];
                    $data['altura']         = $item['height'];
                    $data['profundidade']   = $item['depth'];
                    $data['unmedida']       = $item['measured_unit'];
                    $data['kit_id']         = null;
                    
                    $item_id = $this->model_orders->insertItem($data);

                    if (!$item_id)
                    {
                        echo "Erro ao incluir item. removendo pedido \n";
                        $this->model_clients->remove($customer_id);
                        $this->model_orders->remove($order_id);                                        
                        $this->log_data('batch',$log_name,'Erro ao incluir item. pedido mkt = '.$content['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
                        // continue; 
                    }
            
                    $this->model_products->reduzEstoque($product['id'], $item['qty'], $variant, $order_id);
                    $this->model_integration_last_post->reduzEstoque($this->int_to, $product['id'], $item['qty']);
                    
                }
                else
                {
                    echo "O item é um KIT id=". $product['id']."\n";

                    $productsKit = $this->model_products->getProductsKit($product['id']);

                    foreach ($productsKit as $productKit)
                    {
                        $kit_item = $this->model_products->getProductData(0,$productKit['product_id_item']);
                        echo "Produto item =".$kit_item['id']."\n";

                        $data['order_id']       = $order_id;
                        $data['skumkt']         = $item['sku'];
                        $data['product_id']     = $kit_item['id'];
                        $data['sku']            = $kit_item['sku'];
                        $data['variant']        = '';
                        $data['name']           = $kit_item['name'];
                        $data['qty']            = $item['qty'] * $productKit['qty'];
                        $data['rate']           = $productKit['price'];
                        $data['amount']         = floatVal($data['rate']) * floatVal($data['qty']);
                        $data['discount']       = 0;
                        $data['company_id']     = intVal($kit_item['company_id']); 
                        $data['store_id']       = intVal($kit_item['store_id']); 
                        $data['un']             = $item['unity'];
                        $data['pesobruto']      = $kit_item['peso_bruto'];
                        $data['largura']        = $kit_item['largura'];
                        $data['altura']         = $kit_item['altura'];
                        $data['profundidade']   = $kit_item['profundidade'];
                        $data['unmedida']       = $item['measured_unit'];
                        $data['kit_id']         = $productKit['product_id'];

                        $item_id = $this->model_orders->insertItem($data);

                        if (!$item_id)
                        {
                            echo "Erro ao incluir item de kit. removendo pedido ".$order_id."\n";
                            $this->model_orders->remove($order_id);
                            $this->model_clients->remove($customer_id);
                            $this->log_data('batch',$log_name,'Erro ao incluir item. pedido mkt = '.$content['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
                            // continue; 
                        }
                                            
                        $this->model_products->reduzEstoque($kit_item['id'], $data['qty'], $variant, $order_id);                                    
                    }

                    $this->model_integration_last_post->reduzEstoque($this->int_to, $product['id'], $item['qty']);                                
                }
            }

            //cadastro na tablea de integrations
            // [PEDRO] removido, já existe uma trigger no banco que faz isso.
            //$this->model_orders->createOrderToIntegration($order_id, $company_id, $store_id, $paid_status, $new_order);
        }
        else
        {
            //se nao existe itens no pedido nao posso registrar
            $this->model_clients->remove($customer_id);
            $this->model_orders->remove($order_id);
            echo $msg = "Erro ao incluir item(s). Pedido mkt = ".$content['code']." order_id = ".$order_id." removendo para receber novamente \n";
            $this->log_data('batch', $log_name, $msg, "E");
            return false;
        }

        $this->log_data('batch',$log_name,"Pedido {$content['marketplace_number']} 'incluído\n\n".json_encode($content));

        $this->calculofrete->updateShipCompanyPreview($order_id);
        return true;
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
