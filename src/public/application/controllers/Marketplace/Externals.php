<?php

defined('BASEPATH') or exit('No direct script access allowed');

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Marketplaces/Utilities/Order.php";
require_once APPPATH . "libraries/Marketplaces/Utilities/Store.php";

/**
 * @property CI_Output $output
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Session $session
 *
 * @property Model_external_integration_history $model_external_integration_history
 * @property Model_settings $model_settings
 *
 * @property Marketplaces\Utilities\Order $marketplace_order
 * @property Marketplaces\Utilities\Store $marketplace_store
 */
class Externals extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_external_integration');

        $this->load->model('model_external_integration_history');
        $this->load->model('model_settings');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
        $this->load->library("Marketplaces\\Utilities\\Store", [], 'marketplace_store');
    }

    public function list()
    {
        $this->data['page_title'] = $this->lang->line('application_external_integration');
        $this->render_template('marketplace/external/list', $this->data);
    }

    /**
     * Busca todos os arquivos para serem mostrados em uma listagem na view.
     *
     * @return CI_Output
     */
    public function fetchFileProcessShippingCompanyData(): CI_Output
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $fields_order = array('id', 'type', 'method', 'status_webhook', 'register_id', 'external_id', 'created_at', '');

            $query = array();
            $query['select'][] = 'id, type, method, status_webhook, register_id, external_id, created_at, response_webhook, status_webhook';
            $query['from'][] = 'external_integration_history';

            $data = fetchDataTable(
                $query,
                array('id', 'DESC'),
                null,
                null,
                ['admin_group'],
                $filters,
                $fields_order,
                $filter_default
            );
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(
                    json_encode(array(
                        "draw"              => $draw,
                        "recordsTotal"      => 0,
                        "recordsFiltered"   => 0,
                        "data"              => $result,
                        "message"           => $exception->getMessage()
                    ))
                );
        }

        foreach ($data['data'] as $key => $value) {
            $colorStatus = '';
            $nameStatus = '';
            if (is_null($value['response_webhook']) && is_null($value['status_webhook'])) {
                $colorStatus = 'warning';
                $nameStatus = "<i class='fa fa-spinner fa-spin'></i> &nbsp; {$this->lang->line('application_waiting_notification')}";
            } else if ($value['status_webhook'] == 0) {
                $colorStatus = 'danger';
                $nameStatus = $this->lang->line('application_error');
            } else if ($value['status_webhook'] == 1) {
                $colorStatus = 'success';
                $nameStatus = $this->lang->line('application_success');
            }

            $status = "<span class='label label-$colorStatus'>$nameStatus</span>";

            $result[$key] = array(
                $value['id'],
                $this->lang->line("application_external_$value[type]"),
                $this->lang->line("application_external_$value[method]"),
                $status,
                $value['register_id'],
                $value['external_id'],
                date('d/m/Y H:i', strtotime($value['created_at'])),
                "<button type='button' class='btn btn-primary view-status-file' data-external-integration-id='{$value['id']}'><i class='fa fa-eye'></i></button>"
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $data['recordsTotal'],
            "recordsFiltered" => $data['recordsFiltered'],
            "data" => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    /**
     * Recupera a resposta do processamento do arquivo
     *
     * @return CI_Output
     */
    public function getResponseFile(): CI_Output
    {
        $id = $this->postClean('id');
        $external_integration = $this->model_external_integration_history->getById($id);

        // Não encontrou o arquivo
        if (empty($external_integration)) {
            return $this->output->set_output(json_encode(array(
                'errors' => 'Não foi possível identificar a notificação',
                'messages' => 'Não foi possível identificar a notificação'
            )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'uri' => $external_integration['uri'],
                'request' => $external_integration['request'],
                'response' => $external_integration['response'],
                'response_webhook' => $external_integration['response_webhook'] ?: (is_null($external_integration['status_webhook']) ? 'Aguardando...' : ''),
                'status_webhook' => $external_integration['status_webhook']
            )));
    }

    public function resendNotification(): CI_Output
    {
        $id = $this->postClean('id');
        $external_integration = $this->model_external_integration_history->getById($id);

        if (empty($external_integration)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                'errors' => 'Não foi possível identificar a notificação ou já foi enviada para processamento.'
            )));
        }

        /*if (is_null($external_integration['response_webhook']) || $external_integration['status_webhook'] != 0) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                'errors' => 'Notificação já em processamento.'
            )));
        }*/

        try {
            $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
            if ($external_marketplace_integration) {

                if ($external_integration['type'] === 'store') {
                    $this->marketplace_store->setExternalIntegration($external_marketplace_integration);
                    if ($external_integration['method'] == 'create') {
                        $this->marketplace_store->external_integration->notifyStore($external_integration['register_id']);
                    } else {
                        throw new Exception("Não encontrado o método ($external_integration[method])");
                    }
                } else if ($external_integration['type'] === 'conciliation') {
                    $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                    if ($external_integration['method'] == 'payment') {
                        $exp_register_id = explode('-', $external_integration['register_id']);

                        $exp_date = new DateTime("02/$exp_register_id[1]");
                        $exp_date = $exp_date->format(DATE_INTERNATIONAL);

                        $this->marketplace_order->external_integration->notifyConciliation($exp_register_id[0], $exp_date);
                    } else {
                        throw new Exception("Não encontrado o método ($external_integration[method])");
                    }
                } else if (in_array($external_integration['type'], array('nfe', 'order'))) {
                    $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                    if ($external_integration['type'] == 'nfe' && $external_integration['method'] == 'validation') {
                        $this->marketplace_order->external_integration->notifyNfeValidation($external_integration['register_id']);
                    } elseif ($external_integration['type'] == 'order') {
                        $this->marketplace_order->external_integration->notifyOrder($external_integration['register_id'], $external_integration['method']);
                    } else {
                        throw new Exception("Não encontrado o método ($external_integration[type] - $external_integration[method])");
                    }
                } else {
                    throw new Exception("Não encontrado o tipo ($external_integration[type])");
                }
            }
        } catch (Exception | Error $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                'errors' => "Não foi possível enviar a notificação. {$exception->getMessage()}"
            )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array()));
    }

    public function resendAllNotification(): CI_Output
    {
        $errors = array();
        $all_notification_with_error = true;
        foreach ($this->model_external_integration_history->getErroNotifications() as $external_integration) {
            try {
                $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
                if ($external_marketplace_integration) {

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
                            $this->marketplace_order->external_integration->notifyNfeValidation($external_integration['register_id']);
                        } elseif ($external_integration['type'] == 'order') {
                            $this->marketplace_order->external_integration->notifyOrder($external_integration['register_id'], $external_integration['method']);
                        } else {
                            throw new Exception("Não encontrado o método ($external_integration[type] - $external_integration[method])");
                        }
                    } else {
                        throw new Exception("Não encontrado o tipo ($external_integration[type])");
                    }
                    $all_notification_with_error = false;
                }
            } catch (Exception|Error $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'all_notification_with_error' => $all_notification_with_error,
                'errors' => $errors
            )));
    }

    public function resendLostOrdersNotifications(): CI_Output
    {
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        if (!$external_marketplace_integration) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'errors' => 'Parâmetro inativo.'
                )));
        }

        try {
            $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
        } catch (Exception|Error $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'errors' => $exception->getMessage()
                )));
        }

        $orders = $this->model_external_integration_history->getOrdersToIntegrationByDate('2024-07-23');
        $errors = array();
        foreach ($orders as $order) {
            try {
                // Ignorar.
                if (in_array($order['paid_status'], array(1,96)) || empty($order['data_pago'])) {
                    continue;
                }

                // Enviar pagamento e cancelar.
                if (in_array($order['paid_status'], array(95,97))) {
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
                $errors[] = $exception->getMessage();
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'errors' => $errors
            )));
    }

    public function resendManualNotification(string $type, string $method, string $register_id)
    {
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        if (!$external_marketplace_integration) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'errors' => 'Parâmetro inativo.'
                )));
        }

        try {
            $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
            $this->marketplace_store->setExternalIntegration($external_marketplace_integration);
        } catch (Exception|Error $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'errors' => $exception->getMessage()
                )));
        }

        try {
            switch ($type) {
                case 'order':
                    if (!in_array($method, array('paid', 'cancel', 'refund'))) {
                        throw new Exception("Método $method para $type, não mapeado");
                    }
                    $this->marketplace_order->external_integration->notifyOrder($register_id, $method);
                    break;
                case 'nfe':
                    if ($method != 'validation') {
                        throw new Exception("Método $method para $type, não mapeado");
                    }
                    $this->marketplace_order->external_integration->notifyNfeValidation($register_id);
                    break;
                case 'store':
                    if ($method != 'create') {
                        throw new Exception("Método $method para $type, não mapeado");
                    }
                    $this->marketplace_store->external_integration->notifyStore($register_id);
                    break;
                case 'conciliation':
                    if ($method != 'payment') {
                        throw new Exception("Método $method para $type, não mapeado");
                    }
                    $exp_register_id = explode('-', $register_id);
                    $this->marketplace_order->external_integration->notifyConciliation($exp_register_id[0]);
                    break;
            }
        } catch (Exception|Error $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'errors' => $exception->getMessage()
                )))->set_status_header(400);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true
            )));
    }
}