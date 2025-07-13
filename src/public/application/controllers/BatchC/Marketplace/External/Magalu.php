<?php

require APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 * @property Model_orders $model_orders
 * @property Model_settings $model_settings
 * @property Model_external_integration_history $model_external_integration_history
 * @property \Marketplaces\Utilities\Order $marketplace_order
 */
class Magalu extends BatchBackground_Controller {

    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_external_integration_history');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
    }

    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        // Desativado, será enviado on-line.
        //$this->proccessDailyOrderCanceled();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    private function proccessDailyOrderCanceled()
    {
        $date_now = dateNow()->format(DATE_INTERNATIONAL);
//        $date_yesterday = date(DATE_INTERNATIONAL, strtotime($date_now. ' - 1 day'));
        $date_yesterday = $date_now;

        $start_date = "$date_yesterday 00:00:00";
        $end_date   = "$date_yesterday 23:59:59";
        $limit      = 200;
        $offset     = 0;
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');

        if (!$external_marketplace_integration || $external_marketplace_integration != 'magalu') {
            echo "Parâmetro external_marketplace_integration, deve existir com o valor de 'magalu'\n";
            return;
        }

        while (true) {
            $orders = $this->model_orders->getOrdersPaidAndCancelBetweenDates($start_date, $end_date, $limit, $offset);

            if (empty($orders)) {
                break;
            }

            $offset += $limit;

            foreach ($orders as $order) {
                if (!empty($this->model_external_integration_history->getByRegisterIdAndTypeAndMethod($order['id'], 'order', 'cancel'))) {
                    echo "Pedido $order[id] já notificado\n";
                    continue;
                }

                try {
                    $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                    $this->marketplace_order->external_integration->setProvider('daily_job');
                    $this->marketplace_order->external_integration->notifyOrder($order['id'], 'cancel');
                    echo "Pedido $order[id] cancelado\n";
                } catch (Exception | Error $exception) {
                    echo "{$exception->getMessage()}\n";
                }
            }
        }

    }
}