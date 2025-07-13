<?php

require APPPATH . "libraries/CalculoFrete.php";

/**
 * @property Model_orders $model_orders
 * @property Model_stores_multi_channel_fulfillment $model_stores_multi_channel_fulfillment
 * @property Model_stores $model_stores
 * @property Model_products $model_products
 * @property Model_settings $model_settings
 * @property OrdersMarketplace $ordersmarketplace
 *
 * */
class ChangeSeller extends BatchBackground_Controller
{
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

        // Carrega os módulos necessários para o Job.
        $this->load->model('model_orders');
        $this->load->model('model_stores_multi_channel_fulfillment');
        $this->load->model('model_stores');
        $this->load->model('model_products');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_settings');
        $this->load->library('ordersMarketplace');
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        $this->changeSeller();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

    }

    private function changeSeller()
    {
        $log_name = $this->router->class . '/' . __FUNCTION__;
        $stores_multi_cd = $this->model_settings->getValueIfAtiveByName('stores_multi_cd');

        if (!$stores_multi_cd) {
            echo "Parâmetro 'stores_multi_cd' desligado.\n";
            return;
        }

        $max_invoice_time_to_stores             = array();
        $max_invoice_time_to_principal_stores   = array();
        $store_cd_to_store_principal            = array();
        $data_principal_stores                  = array();

        // Ler pedidos que não foram pagos de multi CD.
        $orders = $this->model_orders->getOrdersMultiChannelFulfillmentByPaidStatus($this->model_orders->PAID_STATUS['awaiting_billing']);

        foreach ($orders as $order) {
            $order_id = $order['id'];
            $date_now = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);

            echo "Pedido $order_id\n";
            // Pedido ainda não foi pago.
            if (empty($order['data_pago'])) {
                echo "Pedido $order_id aguardando faturamento mas não tem data de pago.\n";
                continue;
            }

            // De para de loja CD com o tempo para faturar ainda não existe.
            if (!array_key_exists($order['store_id'], $max_invoice_time_to_stores)) {
                // Não encontrada referência do CD.
                $data_principal_store = $this->model_stores_multi_channel_fulfillment->getMainStoreByCDStore($order['store_id']);
                if (!$data_principal_store) {
                    echo "Não encontrado informação sobre o CD $order[store_id] do pedido $order_id\n";
                    continue;
                }

                // De para de loja principal com o tempo para faturar ainda não existe.
                if (!array_key_exists($data_principal_store['store_id_principal'], $max_invoice_time_to_principal_stores)) {
                    // Loja principal não encontrada.
                    $data_store = $this->model_stores->getStoresData($data_principal_store['store_id_principal']);
                    if (!$data_store) {
                        echo "Loja principal não encontrada $data_principal_store[store_id_principal] do pedido $order_id\n";
                        continue;
                    }

                    $max_time_to_invoice_order = $data_store['max_time_to_invoice_order'];
                    $data_principal_stores[$data_principal_store['store_id_principal']] = $data_store;
                    $max_invoice_time_to_principal_stores[$data_principal_store['store_id_principal']] = $max_time_to_invoice_order;
                }

                $store_cd_to_store_principal[$order['store_id']] = $data_principal_store['store_id_principal'];

                $max_invoice_time_to_stores[$order['store_id']] = $max_invoice_time_to_principal_stores[$data_principal_store['store_id_principal']];
            }

            $paid_date              = $order['data_pago'];
            $invoice_end_date       = date(DATETIME_INTERNATIONAL, strtotime("+ {$max_invoice_time_to_stores[$order['store_id']]} hours", strtotime($paid_date)));
            $invoice_end_time_date  = strtotime($invoice_end_date);
            $date_now_time          = strtotime($date_now);
            $principal_store_id     = $store_cd_to_store_principal[$order['store_id']];

            // Pedido ainda está no prazo para faturar.
            if ($date_now_time < $invoice_end_time_date) {
                echo "Pedido $order_id ainda no prazo para faturar. Pago em: $paid_date, deve faturar até $invoice_end_date.\n";
                continue;
            }

            echo "Enviar pedido $order_id para a loja principal.\n";

            try {
                $this->ordersmarketplace->changeSeller($order_id, $data_principal_stores[$principal_store_id], $order, $log_name);
            } catch (Exception $exception) {
                echo "{$exception->getMessage()}\n";
                continue;
            }

            echo "Pedido $order_id enviado para a loja $principal_store_id\n";
        }
    }
}