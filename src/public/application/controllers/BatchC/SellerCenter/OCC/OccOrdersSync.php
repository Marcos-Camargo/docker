<?php
/*
 * Atualiza os status dos pedidos nos sellerscenter do conecta lá
 * */
require APPPATH . "controllers/BatchC/Marketplace/Conectala/Integration.php";

 class OccOrdersSync extends BatchBackground_Controller 
{

    public $int_to 	= '';

	public function __construct()
	{
		parent::__construct();

		$this->integration = new Integration();

		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_orders_occ');
		
    }
	
	//php index.php BatchC/SellerCenter/OCC/OccOrdersSync run null Zema
	function run($id = null, $params = null)
	{
		/* inicia o job */
        $this->int_to = $params;
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return ;
		}

		$this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");
		
		/* faz o que o job precisa fazer */
			$this->syncOrder();

		
		   
		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}
	
	
	function syncOrder()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos da tabela orders_occ e atualizo o orders_payment;
			
		$orders_to_processing = $this->model_orders_occ->getDataToProcess();
		
		
		if (count($orders_to_processing) == 0)
		{
			$this->log_data('batch',$log_name,'Nenhuma ordem pendente de envio de processamento ',"I");
			return ;
		}

        foreach ($orders_to_processing as $orderp)
        {
            $data = json_decode($orderp['json']);
            $orders = $this->model_orders->getAllOrdersDatabyBill($this->int_to, $orderp['order_id']);
        
            if (!$orders) {
                $this->log_data('batch',$log_name,'PaymentOCC', 'Pedido não encontrado - '.$orderp['json'],"I");
                continue; // não encontrou pedido
            }
            $response = [];
            foreach($orders as $order){
                $marketplaceOrderId = $order['numero_marketplace'];
                if ($order['paid_status'] != 1) { // pedido já está pago ou com pagamento em processamento
                    $this->log_data('batch',$log_name,'PaymentOCC', 'Pedido já foi atualizado - '.json_encode($order['json']),"I");
                    $orders_to_processing = $this->model_orders_occ->update(['status' => 1, 'date_updated' => (new DateTime())->format("Y-m-d H:i:s")], $orderp['id']);               
                }else{
                    
                    if($data->status == 'PROCESSING'){  
                        $forma_desc = null;
                        $autorization_id = null;
                        $payment_transaction_id = null;
                        $first_digits = null;
                        $last_digits = null;
                        if($data->payment_type == 'creditCard' && $data->authorizationPayload){
                            $forma_desc = $data->authorizationPayload->Payment->CreditCard->Brand; 
                            $autorization_id = $data->authorizationPayload->Payment->AuthorizationCode;
                            $payment_transaction_id = $data->authorizationPayload->Payment->AuthorizationCode;
                            $first_digits = str_split($data->authorizationPayload->Payment->CreditCard->CardNumber, 4);
                            $first_digits = $first_digits[0];
                            $last_digits =  str_split($data->authorizationPayload->Payment->CreditCard->CardNumber, strlen($data->authorizationPayload->Payment->CreditCard->CardNumber) - 4);
                            $last_digits = $last_digits[1];
                        }
                        
                        $payment = $this->model_orders->getOrdersParcels($order['id']);
                        if($payment){
                            $arrPayment = array(
                                'order_id'               => $order['id'],
                                'parcela'                => $data->number_installments,
                                'bill_no'                => $orderp['order_id'],
                                'data_vencto'            => date('Y-m-d'),
                                'valor'                  => $data->amount,
                                'forma_id'               => $data->payment_type,
                                'payment_id'             => $data->paymentId,
                                'transaction_id'         => $data->paymentId,
                                'forma_desc'             => $forma_desc,
                                'autorization_id'        => $autorization_id,
                                'payment_transaction_id' => $payment_transaction_id,
                                'first_digits'           => $first_digits,
                                'last_digits'            => $last_digits
                            );
            
                            $paymentSuccess = $this->model_orders->updateOrdersPayment($arrPayment, $payment[0]['id']);

                        }else{
                            $arrPayment = array(
                                'order_id'               => $order['id'],
                                'parcela'                => $data->number_installments,
                                'bill_no'                => $orderp['order_id'],
                                'data_vencto'            => date('Y-m-d'),
                                'valor'                  => $data->amount,
                                'forma_id'               => $data->payment_type,
                                'payment_id'             => $data->paymentId,
                                'transaction_id'         => $data->paymentId,
                                'forma_desc'             => $forma_desc,
                                'autorization_id'        => $autorization_id,
                                'payment_transaction_id' => $payment_transaction_id,
                                'first_digits'           => $first_digits,
                                'last_digits'            => $last_digits
                            );
            
                            $paymentSuccess = $this->model_orders->insertParcels($arrPayment);

                        }

                        if($paymentSuccess){
                            $this->model_orders->updatePaidStatus($order['id'],2);
                        }else{
                            $this->log_data('batch',$log_name,'PaymentOCC', 'Erro ao salvar pagamento - '.json_encode($data),"I");
                            continue;
                        }
                        
                    }else{
                        $this->log_data('api', 'PaymentOCC', 'Erro ao salvar pagamento - '.json_encode($data));
                        continue;
                    }
                    $orders_to_processing = $this->model_orders_occ->update(['status' => 1, 'date_updated' => (new DateTime())->format("Y-m-d H:i:s") ], $orderp['id']);
                }
                
            }   

        }
			
	}   

}
?>