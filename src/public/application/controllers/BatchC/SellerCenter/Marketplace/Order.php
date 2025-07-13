<?php

require APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 * @property Model_order_value_refund_on_gateways $model_order_value_refund_on_gateways
 * @property Model_settings $model_settings
 * @property Model_gateway $model_gateway
 * @property Model_orders $model_orders
 *
 * @property \Marketplaces\Utilities\Order $marketplace_order
 * @property Tunalibrary $tunalibrary
 */
class Order extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
        $this->load->library('Tunalibrary');
        $this->load->model('model_order_value_refund_on_gateways');
        $this->load->model('model_settings');
        $this->load->model('model_gateway');
        $this->load->model('model_orders');
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

        if (empty($params) || $params === 'null') {
            $params = 'tuna';
        }

        try {
            $this->refundValueOrder($params);
        } catch (Exception $exception) {
            $message_error = $exception->getMessage();
            echo "$message_error\n";
        }

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    /**
     * @throws Exception
     */
    private function refundValueOrder(string $gatewayBatch)
    {
        $payment_gateway_id = $this->model_settings->getValueIfAtiveByName('payment_gateway_id');
        $gatewayCode = $payment_gateway_id ? $this->model_gateway->getGatewayCodeById($payment_gateway_id) : null;

        if ($gatewayCode !== $gatewayBatch) {
            throw new Exception("Gateway $gatewayBatch not found for 'payment_gateway_id' parameter");
        }

        $order_value_refund_on_gateways = $this->model_order_value_refund_on_gateways->getAllNotSent();

        if (empty($order_value_refund_on_gateways)) {
            throw new Exception("Nenhum valor para estornar.");
        }

        foreach ($order_value_refund_on_gateways as $order_value_refund_on_gateway) {

            $order = $this->model_orders->getOrdersData(0, $order_value_refund_on_gateway['order_id']);

            if (
                $order_value_refund_on_gateway['is_product_return'] == 1 &&
                $order['paid_status'] != $this->model_orders->PAID_STATUS['refunded']
            ) {
                echo "Pedido $order[id] - Ainda não está como devolvido (status {$this->model_orders->PAID_STATUS['refunded']})\n";
                continue;
            }

            $response = $this->tunalibrary->geracancelamentotuna($order, $order_value_refund_on_gateway['value']);

            if ($response['httpcode'] < 200 || $response['httpcode'] > 299) {
                $message_error = json_encode($response['content'], JSON_UNESCAPED_UNICODE);

                if ($order_value_refund_on_gateway['response_error'] != $message_error) {
                    $this->model_order_value_refund_on_gateways->update(array(
                        'response_error' => $message_error
                    ), $order_value_refund_on_gateway['id']);
                }

                echo "Pedido $order[id] - $message_error\n";
                continue;
            }

            $this->model_order_value_refund_on_gateways->update(array(
                'refunded_at' => dateNow()->format(DATETIME_INTERNATIONAL),
                'response_error' => null
            ), $order_value_refund_on_gateway['id']);

            echo "Pedido $order[id] enviado devolução\n";
        }
    }
}