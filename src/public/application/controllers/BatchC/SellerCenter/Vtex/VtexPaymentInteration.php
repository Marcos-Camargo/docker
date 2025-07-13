<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

/**
 * @property Model_orders $model_orders
 * @property Model_integrations $model_integrations
 * @property Model_settings $model_settings
 * @property Model_order_payment_transactions $model_order_payment_transactions
 * @property Model_orders_payment $model_orders_payment
 *
 * @property OrdersMarketplace $ordersmarketplace
 *
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_Router $router
 */

class VtexPaymentInteration extends Main {

    private $int_to;
    private $account_name;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders');
        $this->load->model('model_integrations');
        $this->load->model('model_settings');
        $this->load->model('model_order_payment_transactions');
        $this->load->model('model_orders_payment');
    }

    private function setInt_to($int_to) {
        $this->int_to = $int_to;
    }

    private function getInt_to(): string
    {
        return $this->int_to;
    }

    private function setAccoutName($account_name) {
        $this->account_name = $account_name;
    }

    public function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        // verificar se tem integration com store_id=0 e $params se não for null. 
        if (!is_null($params) && ($params != 'null')) {
        	$integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$params,"CONECTALA","DIRECT",0);
			if (!$integration) {
                $this->log_data('batch',$log_name,"Marketplace $params não encontrado!","E");
                $this->gravaFimJob();
				return;
			}
			echo " Buscando pedidos da $params\n";
        }
		else {
			$params = null; 
		}

        $this->syncPaymentInteractions($params);

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    private function setkeys($int_to) {
        $this->setInt_to($int_to);

        //Pega os dados da integração. Por enquanto só a conectala faz a integração direta
        $integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$this->getInt_to(),"CONECTALA","DIRECT");
        $api_keys = json_decode($integration['auth_data'],true);
        $this->setAccoutName($api_keys['accountName'] ?? null);
    }

    private function syncPaymentInteractions(string $int_to = null)
    {
        $payments = $this->model_orders->getOrdersInProgressInteraction($int_to);

        if (count($payments)==0) {
            echo "Nenhum pedido em andamento para baixar as transações\n";
            return;
        }
        echo count($payments)." pedido em andamento para baixar as transações\n";

        foreach ($payments as $payment) {
            if ($this->int_to != $payment['origin']) {
                $this->setkeys($payment['origin']);
            }

            $endPoint = "https://$this->account_name.vtexpayments.com.br/api/pvt/transactions/{$payment['transaction_id']}/interactions";
            $this->process($payment['origin'], $endPoint);

            $interactionsPayment = json_decode($this->result);

            // Transação não encontrada.
            if ($this->responseCode != 200) {
                echo '[interactions]' . $this->result . "\n";
                continue;
            }

            if (empty($interactionsPayment)) {
                continue;
            }

            $interactionsPayment = array_reverse($interactionsPayment);

            foreach ($interactionsPayment as $interaction) {
                $interactionDate    = dateFormat(dateFormat($interaction->Date, DATETIME_INTERNATIONAL, null).'-00:00', DATETIME_INTERNATIONAL);
                $interactionId      = $interaction->Id;
                $status             = $interaction->Status;
                $description        = $interaction->Message;

                // Código do pagamento não é desse registro.
                // Isso pode acontecer quando um pedido tem mais que um pagamento.
                if ($interaction->PaymentId != $payment['payment_id']) {
                    //echo "[PROCESS] $interactionId com o PaymentId diferente do pagamento {$payment['payment_id']}.\n";
                    continue;
                }

                // Verificar se esse registro já existe no banco.
                if (
                    $this->model_order_payment_transactions->getTransaction(
                        array(
                            'order_id'       => $payment['order_id'],
                            'payment_id'     => $payment['id'],
                            'interaction_id' => $interactionId
                        )
                    )
                ) {
                    //echo "[PROCESS] $interactionId já existe no pedido {$payment['order_id']} e pagamento {$payment['id']}.\n";
                    continue;
                }

                // Registro não existe, cria-lo no banco.
                $this->model_order_payment_transactions->create(
                    array(
                        'order_id'          => $payment['order_id'],
                        'payment_id'        => $payment['id'],
                        'status'            => $status,
                        'description'       => $description,
                        'interaction_id'    => $interactionId,
                        'interaction_date'  => $interactionDate
                    )
                );

                echo "[SUCCESS] $interactionId criado para o pedido {$payment['order_id']} e pagamento {$payment['id']}.\n";
            }

            // https://help.vtex.com/pt/tutorial/fluxo-da-transacao-no-pagamentos--Er2oWmqPIWWyeIy4IoEoQ
            $endPoint = "https://$this->account_name.vtexpayments.com.br/api/pvt/transactions/{$payment['transaction_id']}/payments/{$payment['payment_id']}";
            $this->process($payment['origin'], $endPoint);

            $paymentTransaction = json_decode($this->result);

            if ($this->responseCode != 200) {
                echo '[payments]' . $this->result . "\n";
                continue;
            }

            if (!$this->model_orders_payment->getOrderPaymentByIdAndTransactionStatus($payment['id'], $paymentTransaction->status)) {
                $this->model_orders_payment->update(array('transaction_status' => $paymentTransaction->status), $payment['id']);
            }
        }
    }
}