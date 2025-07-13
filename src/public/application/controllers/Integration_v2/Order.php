<?php

use Integration\Integration_v2\anymarket\ApiException;
use Integration\Integration_v2\Order_v2;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_api_integrations $model_api_integrations
 * @property Model_orders $model_orders
 */
class Order extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        if ($this->data['only_admin'] != 1) {
            redirect('dashboard', 'refresh');
        }

        $this->load->model('model_api_integrations');
        $this->load->model('model_orders');
    }

    public function search(string $type = null, int $store_id = null, int $order_id = null, bool $debug = false): CI_Output
    {
        if (!$debug) {
            $this->output->set_content_type('application/json');
        }

        if (empty($type)) {
            return $this->output->set_output(json_encode(array('error' => "Informe um tipo de solicitação válida."),JSON_UNESCAPED_UNICODE));
        }
        if (empty($store_id)) {
            return $this->output->set_output(json_encode(array('error' => "Informe uma loja válida."),JSON_UNESCAPED_UNICODE));
        }
        if (empty($order_id)) {
            return $this->output->set_output(json_encode(array('error' => "Informe um sku válido."),JSON_UNESCAPED_UNICODE));
        }

        $order = $this->model_orders->getOrdersData(0, $order_id);
        if (empty($order['order_id_integration'])) {
            return $this->output->set_output(json_encode(array('error' => "Pedido ainda não integrado."),JSON_UNESCAPED_UNICODE));
        }

        $data_integration = $this->model_api_integrations->getIntegrationByStore($store_id);

        if (!$data_integration) {
            return $this->output->set_output(json_encode(array('error' => "Integração não encontrada para a loja $store_id. (api_integrations)"),JSON_UNESCAPED_UNICODE));
        }

        if (likeText('viavarejo_b2b%', $data_integration['integration'])) {
            $data_integration['integration'] = 'viavarejo_b2b';
        }

        if (!$debug) {
            ob_start();
        }
        try {
            require APPPATH . "libraries/Integration_v2/Order_v2.php";
            $order_v2 = new Order_v2();
            $order_v2->startRun($store_id);
            $order_v2->setToolsOrder();
            $order_v2->toolsOrder->orderId = $order_id;
            $order_v2->toolsOrder->orderIdIntegration = $order['order_id_integration'];

            if ($debug) {
                $order_v2->setDebug(true);
            }

            switch ($type) {
                case 'order':
                    $response = $order_v2->toolsOrder->getOrderIntegration($order['order_id_integration']) ?? array('error' => 'Pedido não localizado');
                    break;
                case 'invoice':
                    $response = $order_v2->toolsOrder->getInvoiceIntegration($order['order_id_integration'], $order_id) ?? array('error' => 'Nota fiscal não localizado');
                    break;
                case 'tracking':
                    $order_integration = $order_v2->getOrder($order_id);
                    $order_integration->items = array_map(function ($item) use ($order_integration) {
                        $item->shipping_carrier = $order_integration->shipping->shipping_carrier ?? null;
                        $item->service_method = $order_integration->shipping->service_method ?? null;
                        return $item;
                    }, $order_integration->items);
                    $response = $order_v2->toolsOrder->getTrackingIntegration($order['order_id_integration'], $order_integration->items) ?? array('error' => 'Rastreio não localizado');
                    break;
                default:
                    throw new Exception("Tipo de parâmetro não encontrado");
            }

            if (!$debug) {
                ob_clean();
                return $this->output->set_output(json_encode($response,JSON_UNESCAPED_UNICODE));
            }

            echo "\n";
            echo "response:\n";
            return $this->output->set_output(json_encode($response,JSON_UNESCAPED_UNICODE));
        } catch (Throwable|ApiException|InvalidArgumentException $e) {
            if (!$debug) {
                ob_clean();
                return $this->output->set_output(json_encode(array('error' => array('Message' => $e->getMessage(), 'Code' => $e->getCode())),JSON_UNESCAPED_UNICODE));
            }

            echo "\n";
            echo "response:\n";
            return $this->output->set_output(json_encode($e->getMessage(),JSON_UNESCAPED_UNICODE));
        }
    }
}
