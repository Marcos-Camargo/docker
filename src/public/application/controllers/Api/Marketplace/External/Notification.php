<?php

require APPPATH . "libraries/Marketplaces/Utilities/Order.php";
require APPPATH . 'libraries/REST_Controller.php';

/**
 * @property CI_Loader $load
 *
 * @property Model_settings $model_settings
 * @property Model_external_integration_history $model_external_integration_history
 *
 * @property \Marketplaces\Utilities\Order $marketplace_order
 */

class Notification extends REST_Controller
{
    /**
     * Instantiate a new Notification instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_external_integration_history');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
    }

    /**
     * Callback lojas
     */
    public function store_post()
    {
        try {
            $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
            if ($external_marketplace_integration) {
                $data = $this->inputClean();
                $this->log_data(__CLASS__, __CLASS__.'/'.__FUNCTION__, json_encode($data, JSON_UNESCAPED_UNICODE));

                if (!$data || !is_object($data)) {
                    throw new Exception("Corpo da requisição enviada está inválida.");
                }

                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                $this->marketplace_order->external_integration->receiveStore($data);
            }
        } catch (Exception | Error $exception) {
            $this->log_data('batch', __CLASS__.'/'.__FUNCTION__, "Não foi possível salvar a notificação do pedido. {$exception->getMessage()}", "E");
            $this->response(array('success' => false, "error" => $exception->getMessage()), self::HTTP_BAD_REQUEST);
            return;
        }

        $this->response(array('success' => true), self::HTTP_OK);
    }

    /**
     * Callback pedido
     */
    public function order_post()
    {
        try {
            $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
            if ($external_marketplace_integration) {
                $data = $this->inputClean();
                $this->log_data(__CLASS__, __CLASS__.'/'.__FUNCTION__, json_encode($data, JSON_UNESCAPED_UNICODE));

                if (!$data || !is_object($data)) {
                    throw new Exception("Corpo da requisição enviada está inválida.");
                }

                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                $this->marketplace_order->external_integration->receiveOrder($data);
            }
        } catch (Exception | Error $exception) {
            $this->log_data('batch', __CLASS__.'/'.__FUNCTION__, "Não foi possível salvar a notificação do pedido. {$exception->getMessage()}", "E");
            $this->response(array('success' => false, "error" => $exception->getMessage()), self::HTTP_BAD_REQUEST);
            return;
        }

        $this->response(array('success' => true), self::HTTP_OK);
    }

    /**
     * Callback pedido
     */
    public function invoice_post()
    {
        try {
            $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
            if ($external_marketplace_integration) {
                $data = $this->inputClean();
                $this->log_data(__CLASS__, __CLASS__.'/'.__FUNCTION__, json_encode($data, JSON_UNESCAPED_UNICODE));

                if (!$data || !is_object($data)) {
                    throw new Exception("Corpo da requisição enviada está inválida.");
                }

                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                $this->marketplace_order->external_integration->receiveNfe($data);
            }
        } catch (Exception | Error $exception) {
            $this->log_data('batch', __CLASS__.'/'.__FUNCTION__, "Não foi possível salvar a notificação do pedido. {$exception->getMessage()}", "E");
            $this->response(array('success' => false, "error" => $exception->getMessage()), self::HTTP_BAD_REQUEST);
            return;
        }

        $this->response(array('success' => true), self::HTTP_OK);
    }
}