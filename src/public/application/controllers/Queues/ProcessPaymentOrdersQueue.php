<?php
require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 * Fila para baixar pagamento de pedidos Vtex após cadastro no Seller Center
 *
 * @property Model_integrations $model_integrations
 * @property Model_queue_payments_orders_marketplace $model_queue_payments_orders_marketplace
 * @property Model_queues_control $model_queues_control
 * @property Model_settings $model_settings
 * @property Model_orders_payment $model_orders_payment
 * @property Model_orders $model_orders
 *
 * @property CI_Config $config
 * @property \Marketplaces\Utilities\Order $marketplace_order
 */

class ProcessPaymentOrdersQueue extends Main {
		
	var $interval;
	var $integrations = array();
	const MAXESPERA = 30;
	const INCREMENTOESPERA = 2;
	const NOVO = 0;
	const PROCESSANDO = 1;
	const AGUARDANDO = 2;
	const MAXJOBS = 400;
	const SLEEPWAIT = 10;
	var $last_log;
	var $dir_log;
	var $process_url;
    private $limit_attempts = 10;
	
	public function __construct()
	{
		parent::__construct();

        $logged_in_sess = array(
            'id' 		=> 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
		$this->session->set_userdata($logged_in_sess);
	 	$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		$this->load->model('model_integrations');
		$this->load->model('model_queue_payments_orders_marketplace');
		$this->load->model('model_queues_control');
		$this->load->model('model_settings');
		$this->load->model('model_orders_payment');
		$this->load->model('model_orders');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
    }
	
	// php index.php Queues/ProcessPaymentOrdersQueue run 
	public function run($lastlog=null)
	{
		/* faz o que o job precisa fazer */
		$this->process_url = $this->model_settings->getValueIfAtiveByName('internal_api_url');
		if (!$this->process_url) {
			/* faz o que o job precisa fazer */
			$this->process_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
			If (!$this->process_url) {
				$this->process_url = base_url() ;
			}
		}
		if (substr($this->process_url, -1) !== '/') {
			$this->process_url .= '/';
        }
		
	    $this->last_log = $lastlog;
		$this->eternalLoop();
	}
	
	private function getIntegrations()
	{
		$this->integrations = $this->model_integrations->getIntegrationsbyStoreIdActive(0);
	}
	
	private function log($msg)
	{
		echo date("d/m/Y H:i:s")."-".$msg."\n";
	}
		
	private function eternalLoop()
    {
		$this->dir_log = $this->config->item('log_path');
		if ($this->dir_log =='') {
			$this->dir_log = FCPATH.'application/logs/';
		}
		$this->dir_log .= 'queue';
        if (!file_exists($this->dir_log)) {
			@mkdir($this->dir_log);
		}
		$this->getIntegrations();
		// crio os diretorios de ‘log’ se não existirem
		foreach($this->integrations as $integration) {
			 if (!file_exists($this->dir_log.'/'.$integration['int_to'])) {
				 @mkdir($this->dir_log.'/'.$integration['int_to']);
			 }
		} 
		
		$cnt             = 0;
		$tomorrow        = strtotime(date('Y-m-d H:i:s'). ' + 1 days');
        $stay_alive      = date('H:i');
		$this->interval  = self::INCREMENTOESPERA;
        $payment_id_jump = null;
        $queues_control  = $this->model_queues_control->getData(__CLASS__);

		if ($queues_control) {
			$this->model_queues_control->update(array('date_update'=>date('Y-m-d H:i:s'), 'last_log'=>$this->last_log, 'server_batch_ip' => exec('hostname -I')), __CLASS__);
		} else {
			$this->model_queues_control->create(array('date_update'=>date('Y-m-d H:i:s'), 'last_log'=>$this->last_log, 'process_name'=>__CLASS__, 'server_batch_ip' => exec('hostname -I')));
		}

		while (true) {
			if (strtotime(date('Y-m-d H:i:s')) > $tomorrow) {
				$this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s', strtotime('-15 minutes')), 'last_log' =>$this->last_log, 'server_batch_ip' => exec('hostname -I')), __CLASS__);
				$this->log("Mudei de dia. Encerrando\n");
				return; 
			}

            if (date('H:i') > $stay_alive) { // gravo que estou vivo de minuto em minuto
                echo "--------------- Update em model_queues_control pois passou um minuto\n";
                $this->model_queues_control->update(array('date_update' => date('Y-m-d H:i:s'), 'last_log' =>$this->last_log, 'server_batch_ip' => exec('hostname -I')), $this->router->fetch_class());
                $stay_alive = date('H:i', strtotime('+1 minutes'));

                // Pegando produtos vencidos
                $this->model_queue_payments_orders_marketplace->updateAllDelayed(); // todos que já estão há muito tempo voltam a ficar com status=0.

                $cnt = 0;
                $prd_id_jump = null;
            }
			
			if ($cnt > 50) {
				//echo "Verificando tamanho da fila em processamento\n";
				$countRecords = $this->model_queue_payments_orders_marketplace->countQueueRunning(); // vejo se já tem muitos sendo processados e dou uma pausa para serem processados
				echo " Em processamento: ".$countRecords['qtd']."\n";
				if ($countRecords['qtd'] > self::MAXJOBS) {
					echo "Dormindo ".self::SLEEPWAIT." segs pois já tem ".$countRecords['qtd']." sendo processados \n";
					sleep(self::SLEEPWAIT);
					$this->model_queue_payments_orders_marketplace->updateAllDelayed(); // todos que já estão há muito tempo voltam a ficar com status=0
				}
			}

			echo "Pegando proximo pagamento $payment_id_jump \n";
			$queues = $this->model_queue_payments_orders_marketplace->getDataNextNew(1, $payment_id_jump);

			foreach($queues as $queue) {
				$cnt++;
				echo date('d/m/Y H:i:s').": Processando registro da fila. id=$queue[id] e order_id=$queue[order_id]\n";
				$payment_id_jump = ($this->processQueue($queue)) ? null : $queue['id'];
			}
			if (count($queues) == 0 ) {
				// enquanto não tem na fila, vou subindo o tempo até 2 minutos em intervalos de 5 segundos
				if ($cnt == 0) {
					$this->interval = ($this->interval >= self::MAXESPERA) ? self::MAXESPERA : $this->interval+self::INCREMENTOESPERA;
				}
				// apareceu alguém na fila, volta para intervalo de 5 segundos novamente
				else {
					$this->interval = self::INCREMENTOESPERA;
					$cnt = 0; 
				}
				$this->log("Dormindo ".$this->interval );
				sleep($this->interval);
			}
		}
	}
	
	private function processQueue($queue): bool
	{
		$log_name = __CLASS__.'/'.__FUNCTION__;

        if ($queue['attempts'] > $this->limit_attempts) {
            echo "Pedido $queue[order_id] excedeu $this->limit_attempts tenativas.\n";
            $this->model_queue_payments_orders_marketplace->remove($queue['id']);
            return true;
        }
			
		// vejo se tem alguém ja processando o mesmo pedido 
		$oldprocess = $this->model_queue_payments_orders_marketplace->existProcessing($queue['order_id'], self::PROCESSANDO);
		if (!is_null($oldprocess)) {
			echo "Alguém processando em {$oldprocess['id']}\n";
			return false;
		}

        // apago os demais registros duplicados
        $this->model_queue_payments_orders_marketplace->deleteOthers($queue['id'],$queue['order_id']);

		//  atualizo para 1
		$this->model_queue_payments_orders_marketplace->update(array('status' => self::PROCESSANDO), $queue['id']);

		// verifica se pedido já possui pagamento registrado
        if (empty($this->model_orders_payment->getByOrderId($queue["order_id"]))) {
			$order = $this->model_orders->getOrdersData(0, $queue['order_id']);

            // Pedido existe.
            if ($order) {
                // busca pedido na Vtex
                $orderVtex = $this->getOrderVtex($order);

                $ordersPayment = $orderVtex['payments'] ?? array();

                try {
                    $arrPayment = array();
                    if ($orderVtex && isset($orderVtex['paymentData']['transactions'])) {
                        foreach ($orderVtex['paymentData']['transactions'] as $transaction) {
                            foreach ($transaction['payments'] as $payment) {
                                $orderVtexAux = array('status' => $orderVtex['status'], 'transactionId' => $transaction['transactionId']);
                                $arrPayment[] = $this->formatDataToPayment($order, $payment, $orderVtexAux);
                            }
                        }
                    }

                    foreach ($ordersPayment as $payment) {
                        $arrPayment[] = $this->formatDataToPayment($order, $payment, $orderVtex);
                    }

                    if (!empty($arrPayment)) {
                        $this->insertQueuePaymentParcel($order['id'], $arrPayment);
                    }
                } catch (Exception $exception) {
                    // É adicionado novamente na fila, pois ainda o pagamento não está completo.
                    $orderQueue = array(
                        'order_id' => $queue['order_id'],
                        'numero_marketplace' => $queue['numero_marketplace'],
                        'status' => 0,
                        'attempts' => $queue['attempts'] + 1
                    );

                    echo $exception->getMessage() . json_encode($orderQueue)."\n";

                    $this->model_queue_payments_orders_marketplace->create($orderQueue);
                }
            }
		}

        // apago o meu registro
		$this->model_queue_payments_orders_marketplace->remove($queue['id']);

		return true;
	}

	private function getOrderVtex($order)
	{
		$integration = getArrayByValueIn($this->integrations, $order['origin'], 'int_to');

		if (!$integration) {
			return false;
		}

		$auth_data = json_decode($integration['auth_data']);

        $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'] . '/payment-transaction';

		$this->processNew($auth_data, $endPoint);

		if ($this->responseCode != 200) {
            // Não está disponível o pagamento no endpoint payment-transaction.
            $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'];
            $this->processNew($auth_data, $endPoint);

            if ($this->responseCode != 200) {
                $erro = 'Erro httpcode: ' . $this->responseCode . ' ao chamar ' . $endPoint;
                echo $erro . "\n";
                return false;
            }
		}

		return json_decode($this->result, true);
	}

    /**
     * @param int $order_id
     * @param array $arrPayment
     * @return void
     * @throws Exception
     */
	private function insertQueuePaymentParcel(int $order_id, array $arrPayment)
    {
        $this->marketplace_order->createPayment($order_id, $arrPayment);
    }

    /**
     * @param array $order
     * @param array $payment
     * @param array $orderVtex
     * @return array
     * @throws Exception
     */
	private function formatDataToPayment(array $order, array $payment, array $orderVtex): array
    {
        if (empty($payment['group']) || empty($payment['paymentSystemName'])) {
            throw new Exception("Pagamento do pedido $order[id] ainda não está completo no marketplace.");
        }

        $authId = $payment['connectorResponses']['authId'] ?? '';
        return array(
            'order_id'              => $order["id"],
            'parcela'               => $payment['installments'],
            'bill_no'               => $order["numero_marketplace"],
            'data_vencto'           => $payment['dueDate'] ?? date('Y-m-d H:i:s'),
            'valor'                 => substr_replace($payment['value'], '.', -2, 0),
            'forma_id'              => $payment['group'],
            'forma_desc'            => $payment['paymentSystemName'],
            'payment_method_id'     => $payment['paymentSystem'],
            'gift_card_provider'    => $payment['giftCardProvider'] ?? null,
            'gift_card_id'          => $payment['giftCardId'] ?? null,
            "payment_id"            => $payment['id'],
            "status_payment"        => $orderVtex['status'],
            "transaction_id"        => $orderVtex['transactionId'],
            "first_digits"          => $payment['firstDigits'],
            "last_digits"           => $payment['lastDigits'],
            "payment_transaction_id"=> $payment['tid'],
            "autorization_id"       => $authId
        );
    }
}
