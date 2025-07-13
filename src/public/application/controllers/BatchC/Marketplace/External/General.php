<?php

require APPPATH . "libraries/Marketplaces/Utilities/Order.php";
require APPPATH . "libraries/Marketplaces/Utilities/Store.php";

/**
 * @property Model_settings $model_settings
 * @property Model_external_integration_history $model_external_integration_history
 * @property Model_orders $model_orders
 *
 * @property \Marketplaces\Utilities\Order $marketplace_order
 * @property \Marketplaces\Utilities\Store $marketplace_store
 */
class General extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_settings');
        $this->load->model('model_external_integration_history');
        $this->load->model('model_orders');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
        $this->load->library("Marketplaces\\Utilities\\Store", [], 'marketplace_store');
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

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    public function runResendOrders($id = null, $params = null)
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

        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        if ($external_marketplace_integration) {
            try {
                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                $date_to_filter = subtractDateFromDays(30);
                $orders = $this->model_external_integration_history->getOrdersToIntegrationByDate($date_to_filter);
                foreach ($orders as $order) {
                    try {
                        // Não foi pago ou é cancelado pré.
                        if (in_array($order['paid_status'], array(1, 96)) || empty($order['data_pago'])) {
                            continue;
                        }

                        // Enviar pagamento e cancelar.
                        if (in_array($order['paid_status'], array(95, 97))) {
                            $this->marketplace_order->external_integration->notifyOrder($order['id'], 'paid');
                            $this->marketplace_order->external_integration->notifyOrder($order['id'], 'cancel');
                            continue;
                        }

                        // Enviar devolução.
                        if ($order['paid_status'] == 8) {
                            $this->marketplace_order->external_integration->notifyOrder($order['id'], 'paid');
                            $this->marketplace_order->external_integration->notifyOrder($order['id'], 'refund');
                            continue;
                        }
                        // Enviar pagamento
                        $this->marketplace_order->external_integration->notifyOrder($order['id'], 'paid');
                    } catch (Exception|Error $exception) {
                        echo "[ERROR] pedido ($order[id]) {$exception->getMessage()}\n";
                    }
                }
            } catch (Exception|Error $exception) {
                echo "[ERROR] {$exception->getMessage()}\n";
            }
        } else {
            echo "[ERROR] parâmetro 'external_marketplace_integration' deve estar ativo.\n";
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    public function runResendWithError($id = null, $params = null)
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

        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        if ($external_marketplace_integration) {
            try {
                foreach ($this->model_external_integration_history->getErroNotifications() as $external_integration) {
                    try {
                        if ($external_integration['type'] === 'store') {
                            $this->marketplace_store->setExternalIntegration($external_marketplace_integration);
                            if ($external_integration['method'] == 'create') {
                                $this->marketplace_store->external_integration->notifyStore($external_integration['register_id']);
                            } else {
                                throw new Exception("Não encontrado o método ($external_integration[method])");
                            }
                        } else if (in_array($external_integration['type'], array('nfe', 'order'))) {
                            $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                            if ($external_integration['type'] == 'nfe' && $external_integration['method'] == 'validation') {
                                // Se estiver cancelado não envia mais.
                                $order = $this->model_orders->getOrdersData(0, $external_integration['register_id']);
                                if (!empty($order) && in_array($order['paid_status'], $this->model_orders->PAID_STATUS['orders_cancel'])) {
                                    continue;
                                }
                                $this->marketplace_order->external_integration->notifyNfeValidation($external_integration['register_id']);
                            } elseif ($external_integration['type'] == 'order') {
                                $this->marketplace_order->external_integration->notifyOrder($external_integration['register_id'], $external_integration['method']);
                            } else {
                                throw new Exception("Não encontrado o método ($external_integration[type] - $external_integration[method])");
                            }
                        } else {
                            throw new Exception("Não encontrado o tipo ($external_integration[type])");
                        }
                    } catch (Exception|Error $exception) {
                        echo "[ERROR] {$exception->getMessage()}\n";
                    }
                }
            } catch (Exception|Error $exception) {
                echo "[ERROR] {$exception->getMessage()}\n";
            }
        } else {
            echo "[ERROR] parâmetro 'external_marketplace_integration' deve estar ativo.\n";
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }
}
