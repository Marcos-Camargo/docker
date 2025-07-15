<?php
/*
 Controller de Pedidos
 */
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') or exit('No direct script access allowed');

include "./system/libraries/Vendor/dompdf/autoload.inc.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/PickupPoints.php";

use Dompdf\Dompdf;
use Firebase\JWT\JWT;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;
use Microservices\v1\Logistic\PickupPoints;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Microservices\v1\Logistic\FreightTables;

/**
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Output $output
 * @property CI_Router $router
 *
 * @property Model_orders $model_orders
 * @property Model_products $model_products
 * @property Model_company $model_company
 * @property Model_reports $model_reports
 * @property Model_nfes $model_nfes
 * @property Model_stores $model_stores
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_clients $model_clients
 * @property Model_freights $model_freights
 * @property Model_integration_logistic $model_integration_logistic
 * @property Model_settings $model_settings
 * @property Model_users $model_users
 * @property Model_attributes $model_attributes
 * @property Model_integrations $model_integrations
 * @property Model_orders_with_problem $model_orders_with_problem
 * @property Model_products_catalog $model_products_catalog
 * @property Model_requests_cancel_order $model_requests_cancel_order
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_orders_item $model_orders_item
 * @property Model_notification_popup $model_notification_popup
 * @property Model_phases $model_phases
 * @property Model_orders_mediation $model_orders_mediation
 * @property Model_shipping_company $model_shipping_company
 * @property Model_order_payment_transactions $model_order_payment_transactions
 * @property Model_product_return $model_product_return
 * @property Model_log_integration $model_log_integration
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_stores_multi_channel_fulfillment $model_stores_multi_channel_fulfillment
 * @property Model_change_seller_histories $model_change_seller_histories
 * @property Model_order_items_cancel $model_order_items_cancel
 * @property Model_commissioning_orders_items $model_commissioning_orders_items
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_external_integration_history $model_external_integration_history
 * @property Model_order_value_refund_on_gateways $model_order_value_refund_on_gateways
 * @property Model_pickup_point $model_pickup_point
 *
 * @property JWT $jwt
 * @property OrdersMarketplace $ordersmarketplace
 * @property CalculoFrete $calculofrete
 * @property Bucket $bucket
 *
 * @property FreightTables $ms_freight_tables
 * @property PickupPoints $ms_pickup_points
 */

class Orders extends Admin_Controller
{
    /**
     * @var LogisticTypesWithAutoFreightAcceptedGeneration
     */
    private $logisticAutoApproval;
    private $is_sellercenter;

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_orders');

        $this->load->model('model_orders');
        $this->load->model('model_products');
        $this->load->model('model_company');
        $this->load->model('model_reports');
        $this->load->model('model_nfes');
        $this->load->model('model_stores');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_clients');
        $this->load->model('model_company');
        $this->load->model('model_freights');
        $this->load->model('model_integration_logistic');
        $this->load->model('model_settings');
        $this->load->model('model_users');
        $this->load->model('model_attributes');
        $this->load->model('model_integrations');
        $this->load->model('model_orders_with_problem');
        $this->load->model('model_products_catalog');
        $this->load->model('model_requests_cancel_order');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_orders_item');
        $this->load->model('model_notification_popup');
        $this->load->model('model_phases');
        $this->load->model('model_orders_mediation');
        $this->load->model('model_shipping_company');
        $this->load->model('model_order_payment_transactions');
        $this->load->model('model_product_return');
        $this->load->model('model_log_integration');
        $this->load->model('model_promotions');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_stores_multi_channel_fulfillment');
        $this->load->model('model_change_seller_histories');
        $this->load->model('model_order_items_cancel');
        $this->load->model('model_external_integration_history');
        $this->load->model('model_commissionings');
        $this->load->model('model_commissioning_orders_items');
        $this->load->model('model_billet');
        $this->load->model('model_legal_panel');
        $this->load->model('model_legal_panel_fiscal');
        $this->load->model('model_order_value_refund_on_gateways');
        $this->load->model('model_pickup_point');

        $this->load->library('JWT');
        $this->load->library('ordersMarketplace');
        $this->load->library('calculoFrete');
        $this->load->library("Microservices\\v1\\Logistic\\FreightTables", array(), 'ms_freight_tables');
        $this->load->library("Microservices\\v1\\Logistic\\PickupPoints", array(), 'ms_pickup_points');
        $this->load->library('Bucket');


        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $more = " company_id = " . $usercomp;

        if ($this->session->userdata('ordersfilter') !== Null) {
            $ordersfilter = $this->session->userdata('ordersfilter');
        } else {
            $ordersfilter = "";
        }
        $this->data['ordersfilter'] = $ordersfilter;

        $this->data['mycontroller'] = $this;

        //paid status liberados para alteração do endereçõ de entrega de um pedido
        $this->data['paid_status_authorized_change_address'] = [3, 4, 40, 43, 50, 51, 52, 53, 54, 56, 57, 59, 80, 101];

        $this->logisticAutoApproval = (new LogisticTypesWithAutoFreightAcceptedGeneration(
            $this->db
        ))->setEnvironment(
            $this->model_settings->getValueIfAtiveByName('sellercenter')
        );

		$this->is_sellercenter = $this->model_settings->getStatusbyName('sellercenter');
    }

    /*
     * It only redirects to the manage order page
     */
    public function index()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['phases'] = [];
        if ($this->data['only_admin'] == 1) {
            $this->data['phases'] = $this->model_phases->getAll();
        }

        $this->data['filters'] = $this->model_reports->getFilters('orders');
        $this->data['page_title'] = $this->lang->line('application_manage_orders');
        $this->session->unset_userdata('ordersfilter');
        unset($this->data['ordersfilter']);
        $this->data['stores_filter'] = $this->model_orders->getStoresForFilter();

        $this->data['show_marketplace_order_id'] = $this->model_settings->getStatusbyName('show_marketplace_order_id');
        $this->render_template('orders/index', $this->data);
    }

    public function internal()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['filters'] = $this->model_reports->getFilters('orders');
        $this->data['page_title'] = $this->lang->line('application_manage_orders');
        $this->session->unset_userdata('ordersfilter');
        unset($this->data['ordersfilter']);
        $this->data['stores_filter'] = $this->model_orders->getStoresForFilter();
        $this->data['freights_filter'] = $this->model_orders->getFreightsForFilter();
        $this->data['marketplaces'] = $this->model_integrations->getIntegrationsbyStoreId(0);

        $this->data['phases'] = [];
        if ($this->data['only_admin'] == 1) {
            $this->data['phases'] = $this->model_phases->getAll();
        }

        $this->render_template('orders/internal', $this->data);
    }

    public function invoice()
    {
        if (!in_array('createInvoice', $this->permission) && !in_array('cancelInvoice', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $arrStoresInvoicing = array();
        foreach ($this->model_stores->getStoresData() as $store) {
            if ($this->model_stores->getStoreActiveInvoicing($store['id']) == 1) array_push($arrStoresInvoicing, $store['id']);
        }

        $this->data['page_title'] = $this->lang->line('application_manage_orders_invoice');

        if (count($arrStoresInvoicing) == 0) {
            $this->data['haveStoreForInvoice'] = false;
            $this->render_template('orders/invoice', $this->data);
        } else {

            $this->data['haveStoreForInvoice'] = true;


            $stores = $this->model_stores->getActiveStore();
            $storesView = array();

            foreach ($stores as $store) {
                array_push($storesView, array(
                    'id' => $store['id'],
                    'name' => $store['name']
                ));
            }
            $this->data['storesView'] = $storesView;

            $this->render_template('orders/invoice', $this->data);
        }
    }


    public function filter()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['filters'] = $this->model_reports->getFilters('orders');
        $this->data['page_title'] = $this->lang->line('application_manage_orders_filtered');
        $ordersfilter = "";
        if (!is_null($this->postClean('do_filter'))) {
            if ((!is_null($this->postClean('id'))) && ($this->postClean('id_op') != "0")) {
                $ordersfilter .= " AND o.id " . $this->postClean('id_op') . " " . $this->postClean('id');
            }
            if ((!is_null($this->postClean('bill_no')))  && ($this->postClean('bill_no_op') != "0")) {
                $ordersfilter .= " AND o.bill_no " . $this->postClean('bill_no_op') . " '" . $this->postClean('bill_no') . "'";
            }
            if ((!is_null($this->postClean('numero_marketplace')))  && ($this->postClean('numero_marketplace_op') != "0")) {
                $ordersfilter .= " AND o.numero_marketplace " . $this->postClean('numero_marketplace_op') . " '" . $this->postClean('numero_marketplace') . "'";
            }
            if ((!is_null($this->postClean('origin')))  && ($this->postClean('origin_op') != "0")) {
                $ordersfilter .= " AND o.origin " . $this->postClean('origin_op') . " '" . $this->postClean('origin') . "'";
            }
            if ((!is_null($this->postClean('paid_status')))  && ($this->postClean('paid_status_op') != "0")) {
                $ordersfilter .= " AND o.paid_status " . $this->postClean('paid_status_op') . " " . $this->postClean('paid_status');
            }
            if (
                !is_null($this->postClean('orders_pending_action')) || !is_null($this->postClean('orders_waiting_invoice')) || !is_null($this->postClean('orders_in_transport'))
                || !is_null($this->postClean('orders_delivered')) || !is_null($this->postClean('orders_canceled')) || !is_null($this->postClean('orders_last_post_day'))
                || !is_null($this->postClean('orders_delayed_post')) || !is_null($this->postClean('order_awaiting_collection')) || !is_null($this->postClean('exchange_orders'))
            ) {

                $ordersfilter .= " AND (";
                $operator_or = false; // Adiciona operador OR na consulta entre os tipos de filtro

                if (!is_null($this->postClean("orders_pending_action"))) {
                    $ordersfilter .= " o.paid_status in (3, 99)";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("orders_waiting_invoice"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    $ordersfilter .= " paid_status = 3";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("orders_in_transport"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    $ordersfilter .= " paid_status in (5, 45,  55)";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("orders_delivered"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    $ordersfilter .= " paid_status in (6, 60)";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("orders_canceled"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    $ordersfilter .= " paid_status in (95,96,97,98,99)";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("orders_last_post_day"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    $ordersfilter .= " DATE_FORMAT(data_limite_cross_docking, '%Y-%m-%d') = CURDATE()";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("orders_delayed_post"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    //$ordersfilter .= " (data_limite_cross_docking < NOW() AND paid_status <> 5 AND paid_status <> 6 AND paid_status <> 51 AND paid_status <> 52 AND paid_status <> 53 AND paid_status <> 60 AND paid_status <> 99)";
                    $ordersfilter .= " (data_limite_cross_docking < CURDATE() AND paid_status in (3, 4, 40, 41, 43, 50, 51, 52, 53, 54, 56, 57, 101))";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("order_awaiting_collection"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    $ordersfilter .= " paid_status in (4,43,51,53) ";
                }
                if (!is_null($this->postClean("exchange_orders"))) {
                    if ($operator_or) $ordersfilter .= " OR";
                    $ordersfilter .= " exchange_request is not null ";
                }

                $ordersfilter .= ")";
            }
        }
        $this->session->set_userdata(array('ordersfilter' => $ordersfilter, 'orderExportFilters' => $ordersfilter));
        $this->data['show_marketplace_order_id'] = $this->model_settings->getStatusbyName('show_marketplace_order_id');
        $this->data['stores_filter'] = $this->model_orders->getStoresForFilter();

        $this->render_template('orders/index', $this->data);
    }

    /**
     * Tipos de erro:
     * Erro 1: Problema na decodificação da order_id
     * Erro 2: Pedido com status diferente de 3 - Para Faturar
     * Erro 3: Pedido incompleto
     */
    public function sendOrdersForInvoice()
    {
        ob_start();
        if (!in_array('createInvoice', $this->permission)) {
            ob_clean();
            echo json_encode(array('success' => false, 'message' => "Ocorreu um problema para processar, você não tem permissão!"));
            exit();
        }

        $error      = 0;
        $retorno    = array();
        $orders_id  = array();
        $orders     = $this->postClean('order_id');

        foreach ($orders as $id) {

            // valida status
            if ($this->model_orders->checkStatusOrder($id, 3) === 0) {
                $error = 2;
                break;
            }

            $verifyOrder = $this->verifyOrderForSendInvoice($id);
            if (!$verifyOrder) {
                $error = 3;
                break;
            }

            // adiciona o order_id decodificado em um novo array
            array_push($orders_id, $id);
        }

        // Erro 1 encontrado
        if ($error == 1)
            $retorno = array('success' => false, 'message' => "Ocorreu um problema para processar, recarregue a página e tente novamente!");
        // Erro 2 encontrado
        elseif ($error == 2)
            $retorno = array('success' => false, 'message' => "Ocorreu um problema para processar, seu pedido não pode ser faturado!");
        // Erro 2 encontrado
        elseif ($error == 3)
            $retorno = array('success' => false, 'message' => "Ocorreu um problema para processar, seu pedido não pode ser faturado, ele está incompleto!");

        // Checa status do pedido para emissão
        else {
            $this->db->trans_begin();

            foreach ($orders_id as $order_id) {
                $dataOrder = $this->model_orders->getOrdersData(0, $order_id);
                $dataCreate = array(
                    'order_id'      => (int)$order_id,
                    'company_id'    => (int)$dataOrder['company_id'],
                    'store_id'      => (int)$dataOrder['store_id'],
                    'operation'     => 'E',
                    'request_date'  => date("Y-m-d H:i:s")
                );

                $this->model_nfes->createForIntegration($dataCreate);
                $this->model_orders->updatePaidStatus($order_id, 56);
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $retorno = array('success' => false, 'message' => "Ocorreu um problema para faturar algum pedido!");
            }

            $this->db->trans_commit();
            $retorno = array('success' => true, 'message' => "Pedido(s) faturado(s) com sucesso!");
        }

        ob_clean();
        echo json_encode($retorno);
    }

    private function verifyOrderForSendInvoice($order_id)
    {
        return true;
        // Pedido completo
        // Itens de pedido incorreto
        // Faturas do pedido
    }

    public function fetchOrdersInvoiceData()
    {
        ob_start();
        // Filtro inicial - mostrar apenas as nfes para faturar
        $orders_for_invoice = true;
        $orders_invoiced    = false;
        $orders_processing  = false;
        $result             = array();
        $resultIdsEncryp    = array();
        $empresas           = array();

        // Gets de atualização de filtragem
        if (count($_GET) > 0) {
            $orders_for_invoice = filter_var($_GET['orders_for_invoice'], FILTER_VALIDATE_BOOLEAN);
            $orders_invoiced    = filter_var($_GET['orders_invoiced'], FILTER_VALIDATE_BOOLEAN);
            $orders_processing  = filter_var($_GET['orders_processing'], FILTER_VALIDATE_BOOLEAN);
        }

        $empbd      = $this->model_company->getAllCompanyData();
        $limiteFor  = $orders_invoiced ? 2 : 1;

        foreach ($empbd as $emp) $empresas[$emp['id']] = $emp['name'];

        $arrStoresInvoicing = array();
        foreach ($this->model_stores->getStoresData() as $store) {
            if ($this->model_stores->getStoreActiveInvoicing($store['id']) == 1) array_push($arrStoresInvoicing, $store['id']);
        }

        if (count($arrStoresInvoicing) == 0) {
            ob_clean();
            echo json_encode(array());
            exit();
        }


        for ($i = 0; $i < $limiteFor; $i++) {

            if ($i == 0) {
                if ($orders_for_invoice || $orders_processing) {
                    $arrStatusFilter = array();
                    if ($orders_for_invoice) array_push($arrStatusFilter, "paid_status = 3");
                    if ($orders_processing) array_push($arrStatusFilter, "paid_status = 56");
                    if ($orders_processing) array_push($arrStatusFilter, "paid_status = 57");

                    $this->data['ordersfilter'] = " AND (";
                    $this->data['ordersfilter'] .= implode($arrStatusFilter, " OR ");
                    $this->data['ordersfilter'] .= ")";
                } else continue;
            }

            if ($i == 1) {
                $ordersId = $this->model_nfes->getAllOrderIdHaveNfe("AND id_nota_tiny <> 0");
                if (count($ordersId) == 0) $ordersId = array(0);
                $this->data['ordersfilter'] = " AND o.id IN(" . implode(",", $ordersId) . ")";
            }
            $this->data['ordersfilter'] .= " AND o.store_id IN(" . implode(",", $arrStoresInvoicing) . ")";

            $data = $this->model_orders->getOrdersData(0, null, false);

            foreach ($data as $key => $value) {
                $checkbox       = "<i class='fa fa-ban'></i>";
                $buttons        = "";
                $date           = date('d/m/Y', strtotime($value['date_time']));
                $date_time      = $date;
                $verifyNfeExist = $orders_invoiced ? $this->model_nfes->getNfesDataByOrderId($value['id']) : array();
                $verifyItems    = count($verifyNfeExist) == 0 ? $this->model_orders->getErrorsProductsForInvoice($value['id']) : array();

                if (count($verifyNfeExist) > 0) {
                    if ($verifyNfeExist[0]['request_cancel'] == 0) {
                        $dataExp = explode("/", $verifyNfeExist[0]['date_emission']);
                        $dataExp = explode(" ", "{$dataExp[1]}-{$dataExp[2]}");
                        $urlXml = base_url('assets/images/xml/' . $verifyNfeExist[0]['store_id'] . '/' . $dataExp[0] . '/' . $verifyNfeExist[0]['chave'] . '.xml');
                        $buttons .= '<div class="btn-group">
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" data-togg="tooltip" title="' . $this->lang->line('application_action') . '" aria-expanded="false">
                                            <span class="fa fa-cog"></span>
                                        </button>
                                        <ul class="dropdown-menu position-right">
                                            <li><a href="#" class="viewInvoice" order-id="' . $value['id'] . '">' . $this->lang->line('application_view') . '</a></li>
                                            <li><a download href="' . $urlXml . '">' . $this->lang->line('application_download') . ' XML' . '</a></li>';
                        if (in_array('cancelInvoice', $this->permission)) :
                            $buttons .= '<li><a href="#" class="btnRequestCancel" order-id="' . $value['id'] . '">' . $this->lang->line('application_request_cancel') . ' ' . $this->lang->line('application_order') . ': ' . $value['id'] . '</a></li>';
                        endif;
                        $buttons .= '</ul>
                                                  </div>';
                    } elseif ($verifyNfeExist[0]['request_cancel'] == 1) {
                        $buttons .= '<button class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_in_cancellation') . '"><i class="fa fa-warning"></i></button>';
                    } elseif ($verifyNfeExist[0]['request_cancel'] == 2) {
                        $buttons .= '<button class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_ticket_status_canceled') . '"><i class="fa fa-times"></i></button>';
                    } else {
                        $buttons .= '<button class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_ticket_status_canceled') . '"><i class="fa fa-times"></i></button>';
                    }
                } elseif (count($verifyItems) > 0) {
                    $buttons .= '<button class="btn btn-default btnErrorOrder" order-id="' . $value['id'] . '" data-toggle="tooltip" title="' . $this->lang->line('application_problem_order') . '"><i class="fa fa-warning"></i></button>';
                } elseif ($value['paid_status'] == 3) {
                    if (in_array('createInvoice', $this->permission)) {
                        $checkbox = "<input type='checkbox' class='minimal sendInvoice' value='true'>";
                        // Permissão para emitir
                        $buttons .= '<button class="btn btn-default btnSendInvoice" order-id="' . $value['id'] . '" data-toggle="tooltip" title="' . $this->lang->line('application_send') . ' ' . $this->lang->line('application_order') . ': ' . $value['id'] . ' "><i class="fa fa-check"></i></button>
                                     <button class="btn btn-default btnAddAdditionalData" order-id="' . $value['id'] . '" data-toggle="tooltip" title="' . $this->lang->line('application_add_additional_data') . ' ' . $this->lang->line('application_order') . ': ' . $value['id'] . ' "><i class="fas fa-receipt"></i></button>';
                    } else {
                        $buttons .= '<button class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_no_permission') . '"><i class="fas fa-dot-circle"></i></button>';
                    }
                } elseif ($value['paid_status'] == 56)
                    $buttons .= '<button class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_order_56') . '"><i class="fa fa-cog fa-spin"></i></button>';
                else
                    $buttons .= '<button class="btn btn-default btnInvoiceError" order-id="' . $value['id'] . '" data-toggle="tooltip" title="' . $this->lang->line('application_order_57') . '"><i class="fa fa-warning"></i></button>';

                if (count($verifyNfeExist) > 0) {
                    if ($verifyNfeExist[0]['request_cancel'] == 0)
                        $paid_status = '<span class="label label-success">' . $this->lang->line('application_Invoiced') . '</span>';
                    elseif ($verifyNfeExist[0]['request_cancel'] == 1)
                        $paid_status = '<span class="label label-warning">' . $this->lang->line('application_in_cancellation') . '</span>';
                    elseif ($verifyNfeExist[0]['request_cancel'] == 2)
                        $paid_status = '<span class="label label-danger">' . $this->lang->line('application_ticket_status_canceled') . '</span>';
                } elseif ($value['paid_status'] == 3)
                    $paid_status = '<span class="label label-primary">' . $this->lang->line('application_order_3') . '</span>';
                elseif ($value['paid_status'] == 56)
                    $paid_status = '<span class="label label-warning">' . $this->lang->line('application_order_56') . '</span>';
                else
                    $paid_status = '<span class="label label-danger">' . $this->lang->line('application_order_57') . '</span>';

                $result[$value['id']] = array(
                    $checkbox,
                    $value['id'],
                    $empresas[$value['company_id']],
                    $value['customer_name'],
                    $date_time,
                    get_instance()->formatprice($value['gross_amount']),
                    $paid_status,
                    $buttons
                );

                if (count($_GET) > 0) $result[$value['id']] = (object)$result[$value['id']];
            } // /foreach
        }

        ob_clean();
        if (count($_GET) > 0) echo json_encode(array('count' => count($result), 'data' => $result));
        else return $result;
    }

    /*
	* Fetches the orders data from the orders table
	* this function is called from the datatable ajax function
	*/
    public function fetchOrdersData($postdata = null)
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $show_marketplace_order_id =  $this->model_settings->getStatusbyName('show_marketplace_order_id');
        $pickupStore = $this->model_settings->getValueIfAtiveByName('occ_pickupstore');

        $postdata   = $this->postClean(NULL,TRUE);
        $ini        = $postdata['start'];
        $draw       = $postdata['draw'];
        $busca      = $postdata['search'];
        $date_start = $postdata['date_start'] ?? null;
        $date_end   = $postdata['date_end'] ?? null;
        $this->data['ordersfilter'] = '';

        if ($this->session->has_userdata('ordersfilter')) {
            $this->data['ordersfilter'] .= $this->session->userdata('ordersfilter');
            $this->session->unset_userdata('ordersfilter');
        }

        if ($date_start && $date_end) {
            $date_start .= " 00:00:00";
            $date_end .= " 23:59:59";
            $this->data['ordersfilter'] .= " AND o.date_time BETWEEN '$date_start' AND '$date_end'";
        }

        if ($postdata['internal'] == 'false') {
            if ($busca['value']) {
                $busca['value'] = str_replace('\'', '', $busca['value']);
                if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                    $this->data['ordersfilter'] = " AND ( o.numero_marketplace like '%" . $busca['value'] . "%' 
                    OR o.id like '%" . $busca['value'] . "%' 
                    OR o.customer_name like '%" . $busca['value'] . "%' 
                    OR o.date_time like '%" . $busca['value'] . "%' 
                    OR o.data_limite_cross_docking like '%" . $busca['value'] . "%' 
                    OR f.prazoprevisto like '%" . $busca['value'] . "%' 
                    OR o.gross_amount like '%" . $busca['value'] . "%' 
                    OR f.ship_company like '%" . $busca['value'] . "%' 
                    OR o.data_pago like '%" . $busca['value'] . "%' ) ";
                } else {
                    //return true;
                }
            } else {
                if (trim($postdata['pedido'])) {
                    $this->data['ordersfilter'] .= " AND o.id like '%" . $postdata['pedido'] . "%' ";
                }
                if ($show_marketplace_order_id == 1) {
                    if (trim($postdata['nummkt'])) {
                        $this->data['ordersfilter'] .= " AND o.numero_marketplace like '%" . $postdata['nummkt'] . "%'";
                    }
                }
                if (is_array($postdata['lojas'])) {
                    $lojas = $postdata['lojas'];
                    $this->data['ordersfilter'] .= " AND (";
                    foreach ($lojas as $loja) {
                        $this->data['ordersfilter'] .= "s.id = " . (int)$loja . " OR ";
                    }
                    $this->data['ordersfilter'] = substr($this->data['ordersfilter'], 0, (strlen($this->data['ordersfilter']) - 3));
                    $this->data['ordersfilter'] .= ") ";
                }

                if (trim($postdata['status'])) {
                    $postdata_status = $postdata['status'];
                    if (is_numeric($postdata['status'])) {
                        $postdata_status = (int) $postdata['status'];
                    }

                    // switch ((int)$postdata['status']) {
                    switch ($postdata_status) {
                        case 1:
                            $this->data['ordersfilter'] .= " AND o.paid_status = 1 ";
                            break;
                        case 3:
                            $this->data['ordersfilter'] .= " AND o.paid_status = 3 ";
                            break;
                        case 4:
                            $this->data['ordersfilter'] .= " AND o.paid_status in (4, 43, 51, 53) ";
                            break;
                        case 5:
                            $this->data['ordersfilter'] .= " AND o.paid_status in (5, 45, 55) ";
                            break;
                        case 6:
                            $this->data['ordersfilter'] .= " AND o.paid_status in (6, 60) AND o.product_return_status = 0 ";
                            break;
                        case 50:
                            $this->data['ordersfilter'] .= " AND o.paid_status in (50, 52, 54, 40, 101) ";
                            break;
                        case 59:
                            $this->data['ordersfilter'] .= " AND o.paid_status in (59) ";
                            break;
                        case 97:
                            $this->data['ordersfilter'] .= " AND o.paid_status in (97, 98, 99) AND o.product_return_status = 0 ";
                            break;
                        case "returned_product":
                            $this->data['ordersfilter'] .= " AND o.paid_status IN (6, 8, 81, 97, 112) AND o.product_return_status IN (2, 3) AND EXISTS (SELECT id FROM product_return pr WHERE pr.order_id = o.id AND pr.status IN ('devolvido'))";
                            break;
                        case "return_product":
                            $this->data['ordersfilter'] .= " AND o.paid_status IN (6, 81, 97) AND o.product_return_status IN (20, 21, 22, 30, 31, 32) AND EXISTS (SELECT id FROM product_return pr WHERE pr.order_id = o.id AND pr.status IN ('coletado', 'a_contratar'))";
                            break;
                        default:
                            $this->data['ordersfilter'] .= " AND (o.paid_status = " . (int)$postdata['status'] . ") ";
                            break;
                    }
                }
                if (trim($postdata['entrega'])) {
                    if ($postdata['entrega'] == 'CORREIOS') {
                        $this->data['ordersfilter'] .= " AND f.ship_company = 'CORREIOS' AND o.is_pickup_in_point <> 1";
                    }else if($postdata['entrega'] == 'RETIRADA'){
                        $this->data['ordersfilter'] .= " AND o.is_pickup_in_point = 1";
                    }
                     else {
                        $this->data['ordersfilter'] .= " AND f.ship_company <> 'CORREIOS' AND o.is_pickup_in_point <> 1";
                    }
                }
            }
        } elseif ($postdata['internal'] == 'true') {
            if ($busca['value']) {
                $busca['value'] = str_replace('\'', '', $busca['value']);
                if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                    $this->data['ordersfilter'] = " AND (  
                    o.id like '%" . $busca['value'] . "%' 
                    OR o.numero_marketplace like '%" . $busca['value'] . "%' 
                    OR s.name like '%" . $busca['value'] . "%' 
                    OR o.customer_name like '%" . $busca['value'] . "%' 
                    OR o.date_time like '%" . $busca['value'] . "%' 
                    OR o.data_limite_cross_docking like '%" . $busca['value'] . "%' 
                    OR f.prazoprevisto like '%" . $busca['value'] . "%' 
                    OR o.gross_amount like '%" . $busca['value'] . "%' 
                    OR f.ship_company like '%" . $busca['value'] . "%' 
                    OR o.data_pago like '%" . $busca['value'] . "%' )";
                }
            }
            if (trim($postdata['pedido'])) {
                $this->data['ordersfilter'] .= " AND o.id like '%" . $postdata['pedido'] . "%' ";
            }
            if (trim($postdata['nummkt'])) {
                $this->data['ordersfilter'] .= " AND o.numero_marketplace like '%" . $postdata['nummkt'] . "%' ";
            }
            if (trim($postdata['lojas'])) {
                $this->data['ordersfilter'] .= " AND s.name like '%" . $postdata['lojas'] . "%' ";
            }
            if (trim($postdata['status'])) {
                switch ((int)$postdata['status']) {
                    case 1:
                        $this->data['ordersfilter'] .= " AND o.paid_status = 1 ";
                        break;
                    case 3:
                        $this->data['ordersfilter'] .= " AND o.paid_status = 3 ";
                        break;
                    case 4:
                        $this->data['ordersfilter'] .= " AND o.paid_status in (4, 43, 51, 53) ";
                        break;
                    case 5:
                        $this->data['ordersfilter'] .= " AND o.paid_status in (5, 45, 55) ";
                        break;
                    case 6:
                        $this->data['ordersfilter'] .= " AND o.paid_status in (6, 60) ";
                        break;
                    case 50:
                        $this->data['ordersfilter'] .= " AND o.paid_status in (50, 52, 54, 40, 101) ";
                        break;
                    case 59:
                        $this->data['ordersfilter'] .= " AND o.paid_status in (59) ";
                        break;
                    case 97:
                        $this->data['ordersfilter'] .= " AND o.paid_status in (97, 98, 99) ";
                        break;
                    default:
                        $this->data['ordersfilter'] .= " AND (o.paid_status = " . (int)$postdata['status'] . ") ";
                        break;
                }
            }
            if (trim($postdata['entrega'])) {
                if($postdata['entrega'] == 'RETIRADA'){
                    #$this->data['ordersfilter'] .= "AND f.ship_company = 'PickUpPoint_1' ";
                    $this->data['ordersfilter'] .= " AND o.is_pickup_in_point = 1";
                }
                else{
                    $this->data['ordersfilter'] .= " AND f.ship_company = '" . $postdata['entrega'] . "' ";
                }
                
            }
            if (trim($postdata['incidence'])) {
                switch ((int)$postdata['incidence']) {
                    case 1:
                        $this->data['ordersfilter'] .= " AND o.incidence_user is not null ";
                        break;
                    case 2:
                        $this->data['ordersfilter'] .= " AND o.incidence_user is null ";
                        break;
                    default:
                        $this->data['ordersfilter'] .= '';
                        break;
                }
            }
            if (!empty($postdata['marketplace'])) {
                $this->data['ordersfilter'] .= " AND o.origin = '" . $postdata['marketplace'] . "' ";
            }
        }
        if ($postdata['buscacpf_cnpj']) {
            $postdata['buscacpf_cnpj']=preg_replace("/[.|-]/","",$postdata['buscacpf_cnpj']);
            $this->data['ordersfilter'] .= " AND REPLACE(REPLACE(c.cpf_cnpj,'.',''),'-','') like \"%" . $postdata['buscacpf_cnpj'] . "%\" ";
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            if ($postdata['internal'] == 'false') {
                if ($show_marketplace_order_id == 1) {
                    $campos = array('o.numero_marketplace','id','customer_name', 'date_time', 'data_pago', 'data_envio', 'data_entrega', 'f.prazoprevisto', 'CAST(gross_amount AS UNSIGNED)', 'f.ship_company', 's.name', 'paid_status', '');
                } else {
                    $campos = array('id', 'customer_name', 'date_time', 'data_pago', 'data_envio', 'data_entrega', 'f.prazoprevisto', 'CAST(gross_amount AS UNSIGNED)', 'f.ship_company', 's.name', 'paid_status', '');
                }
            } else {
                $campos = array('id', 'o.numero_marketplace', 's.name', 'customer_name', 'date_time', 'data_pago', 'data_envio', 'data_entrega', 'f.prazoprevisto', 'CAST(gross_amount AS UNSIGNED)', 'f.ship_company', 'paid_status', '');
            }
            $campo =  $campos[$postdata['order'][0]['column']];
            if (($campo == 'id') || ($campo == "o.numero_marketplace")) { // inverto no caso do ID ou número do marketplace
                if ($postdata['order'][0]['dir'] == "asc") {
                    $direcao = "desc";
                } else {
                    $direcao = "asc";
                }
            }
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        if (isset($postdata['phases']) && is_array($postdata['phases'])) {
            if ($this->data['only_admin'] == 1) {
                $phases = implode(',', $postdata['phases']);
                $this->data['ordersfilter'] .= " AND phase.id IN ({$phases}) ";
            }
        }

        if (isset($this->data['ordersfilter'])) {
            $this->session->set_userdata('orderExportFilters', $this->data['ordersfilter']);
            $filtered = $this->model_orders->getOrdersDataViewCount($this->data['ordersfilter']);
        } else {
            $filtered = 0;
            if ($this->session->has_userdata('orderExportFilters')) {
                $this->session->unset_userdata('orderExportFilters');
            }
        }
        $data = $this->model_orders->getOrdersDataView($ini, '', $sOrder,$postdata['length']);

        $i = 0;

        // set seller center
        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter)
            $sellerCenter = $settingSellerCenter['value'];

        foreach ($data as $key => $value) {
            $i++;
            $count_total_item = $this->model_orders->countOrderItem($value['id']);
            $date = date('d/m/y', strtotime($value['date_time']));
            //$time = date('h:i a', strtotime($value['date_time']));

            //$date_time = $date . ' ' . $time;
            $date_time = $date;

            // button
            $buttons = '';

            // if(in_array('viewOrder', $this->permission)) {
            // 	$buttons .= '<a target="__blank" href="'.base_url('orders/printDiv/'.$value['id']).'" class="btn btn-default"><i class="fa fa-print"></i></a>';
            // }

            if (in_array('updateOrder', $this->permission)) {
                if ($postdata['internal'] == 'true') {
                    $buttons .= ' <a href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
                }
            }

            if (in_array('deleteOrder', $this->permission)) {
                // $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['bill_no'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }
            // $buttons .= ' <a class="btn btn-default" href="export/createxls"><i class="fa fa-file-excel-o"></i></a>';
            $nd = $this->datedif($value['date_time']);
            if ($postdata['internal'] == 'false') {
                $ld = $lp = $lb = $lw = "label-primary";
                $np = "label-primary";
                $cc = "label-danger";
            } elseif ($postdata['internal'] == 'true') {
                $lb = "label-success";
                $lw = "label-warning";
                $np = $lp = "label-primary";
                $ld = "label-danger";
                if ($nd>2) $lb = "label-warning";
                if ($nd>5) $lb = "label-danger";
            }
			$tooltip = '';
			if ($this->data['usercomp'] == 1) {
				$tooltip = ' data-toggle="tooltip"  data-placement="top" title="'.($value['paid_status']).'" ';
			}

            $order_status = "";
			if($value['paid_status'] == 1) {  // Não Pago
				$paid_status = '<span class="label label-primary"'.$tooltip.'>'.$this->lang->line('application_order_1').'</span>';
			}
			elseif($value['paid_status'] == 2) {  // NOVO e Pago  - NÂO DEVE OCORRER
				$paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_2').'</span>';
			}
			elseif($value['paid_status'] == 3) {  // Em Andamento - Aguardando faturamento (ACABOU DE CHEGAR DO BING)
				$paid_status = '<span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$this->lang->line('application_order_3').'</span>';
			}
			elseif($value['paid_status'] == 4) {  // Aguardando Coleta
				$paid_status = '<span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$this->lang->line('application_order_4').'</span>';
			}
			elseif($value['paid_status'] == 5) {  // Enviado
				$paid_status = '<span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$this->lang->line('application_order_5').'</span>';
			}
			elseif($value['paid_status'] == 6) {  // Entregue
                $paid_status = '<span class="label label-success"'.$tooltip.'>'.$this->lang->line('application_order_6').'</span>';

                // Se o produto foi devolvido depois de entregue.
                $returned_order =  $this->model_product_return->statusReturnedOrder($value['id']);
                foreach ($returned_order as $rorder) {
                    $order_status = $rorder['status'];
                }

                if (($order_status != "") && (strtolower($order_status) != "devolvido")) {
                    $paid_status = '<span class="label label-success"'.$tooltip.'>Em devolução</span>';
                } else if (($order_status != "") && (strtolower($order_status) == "devolvido")) {
                    $paid_status = '<span class="label label-success"'.$tooltip.'>Devolvido</span>';
                }
			}
			elseif($value['paid_status'] == 50) {  // Nota Fiscal Registrada - Contratar o Frete.
				$paid_status = '<span class="label label-warning"'.$tooltip.'>'.$this->lang->line('application_order_50').'</span>';
			}
            elseif($value['paid_status'] == 40) {
                $paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_40').'</span>';
            }
		    elseif($value['paid_status'] == 51) {  // Frete Contratado - Mandar para o marketplace
		    	//$hasLabel = $this->model_freights->getFreightsHasLabel($value['id']);
		    	//if (empty($hasLabel)) {
		    	//	$paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_51').'</span>';
		    	//}
				//else {
					$paid_status = '<span  class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_4').'</span>';
				//}
			}
  			elseif($value['paid_status'] == 52) {  //  Mandar NF para o marketplace
  				//$hasLabel = $this->model_freights->getFreightsHasLabel($value['id']);
		    	//if (empty($hasLabel)) {
		    		$paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_52').'</span>';
		    	//}
				//else {
				//	$paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_4').'</span>';
				//
			}
			elseif($value['paid_status'] == 53) {  // Tudo ok. Agora é com o Rastreio do frete
				//$hasLabel = $this->model_freights->getFreightsHasLabel($value['id']);
		    	//if (empty($hasLabel)) {
		    	//	$paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_53').'</span>';
		    	//}
				//else {
					$paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_4').'</span>';
				//}
			}
            elseif($value['paid_status'] == 43) {
                $paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_43').'</span>';
            }
			elseif($value['paid_status'] == 54) {  // Igual a 50 só que veio sem cotacao e fez cotacao manual
				$paid_status = '<span class="label '.$ld.'"'.$tooltip.'>'.$this->lang->line('application_order_50').'</span>';
			}
	      	elseif($value['paid_status'] == 55) {  // Pedido foi enviado mas precisa de intervenção manual no Marketplace para ser informado que foi enviado
	          	$paid_status = '<span class="label '.$lw.'">'.$this->lang->line('application_order_55').'</span>';
	      	}
            elseif($value['paid_status'] == 45) {
                $paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_45').'</span>';
            }
	      	elseif($value['paid_status'] == 56) { // Processando nfe aguardando envio para tiny
	          	$paid_status = '<span class="label '.$lw.'">'.$this->lang->line('application_order_56').'</span>';
	      	}
	      	elseif($value['paid_status'] == 57) { // Problema para faturar o pedido
	          	$paid_status = '<span class="label '.$ld.'">'.$this->lang->line('application_order_57').'</span>';
	      	}
            elseif($value['paid_status'] == 58) { // Cliente deve retirar o objeto no local
                $paid_status = '<span class="label label-danger">'.$this->lang->line('application_order_58').'</span>';
            }
            elseif($value['paid_status'] == 59) { // Devolução de produto
                $paid_status = '<span class="label label-danger">'.$this->lang->line('application_order_59').'</span>';
            }
			elseif($value['paid_status'] == 60) {  // Pedido foi Entregue mas precisa de intervenção manual no Marketplace para ser informado que foi entregue
				$paid_status = '<span class="label '.$ld.'"'.$tooltip.'>'.$this->lang->line('application_order_60').'</span>';
			}
            elseif($value['paid_status'] == 70) {  // Pedido foi enviado para trocar de seller
                $paid_status = '<span class="label '.$ld.'"'.$tooltip.'>'.$this->lang->line('application_order_70').'</span>';
            }
            elseif($value['paid_status'] == 80) {  // Pedido com problema na contratação do frete
                $paid_status = '<span class="label label-danger"'.$tooltip.'>'.$this->lang->line('application_order_80').'</span>';
            }
            elseif($value['paid_status'] == 90) {  // Solicitação de cancelamento
                $paid_status = '<span class="label label-danger"'.$tooltip.'>'.$this->lang->line('application_order_90').'</span>';
            }
            elseif($value['paid_status'] == 95) {  // cancelado pelo seller
                $paid_status = '<span class="label label-danger"'.$tooltip.'>'.$this->lang->line('application_order_95').'</span>';
            }
			elseif($value['paid_status'] == 96) {  // cancelado pré-pagamento
				$paid_status = '<span class="label label-default"'.$tooltip.'>'.$this->lang->line('application_order_96').'</span>';
			}
			elseif($value['paid_status'] == 97) {  // Cancelado em definitivo
				$paid_status = '<span class="label label-danger"'.$tooltip.'>'.$this->lang->line('application_order_97').'</span>';

                // Se o produto foi devolvido depois de entregue.
                $returned_order =  $this->model_product_return->statusReturnedOrder($value['id']);
                foreach ($returned_order as $rorder) {
                    $order_status = $rorder['status'];
                }

                if (($order_status != "") && (strtolower($order_status) != "devolvido")) {
                    $paid_status = '<span class="label label-success"'.$tooltip.'>Em devolução</span>';
                } else if (($order_status != "") && (strtolower($order_status) == "devolvido")) {
                    $paid_status = '<span class="label label-success"'.$tooltip.'>Devolvido</span>';
                }
			}
			elseif($value['paid_status'] == 98) {  // Cancelar no Marketplace
				$paid_status = '<span class="label label-danger"'.$tooltip.'>'.($postdata['internal'] == 'true' ? $this->lang->line('application_order_98') : $this->lang->line('application_order_97')) .'</span>';
			}
			elseif($value['paid_status'] == 99) {  // Em Cancelamento - status para cancelar no Bling (BlingCancelar)
				$paid_status = '<span class="label label-danger"'.$tooltip.'>'.($postdata['internal'] == 'true' ? $this->lang->line('application_order_99') : $this->lang->line('application_order_97')).'</span>';
			}
			elseif($value['paid_status'] == 101) {  // Sem cotação de frete - deve ter falhado a consulta frete e precisa ser feita manualmente
				$paid_status = '<span class="label label-danger"'.$tooltip.'>'.($postdata['internal'] == 'true' ? $this->lang->line('application_order_101') : $this->lang->line('application_order_101')).'</span>';
			} else {
                $paid_status = '<span class="label label-primary">'.$this->lang->line("application_order_{$value['paid_status']}").'</span>';
            }
			if (($value['has_incident']) && (in_array('admDashboard', $this->permission))) {
				$paid_status .= '<br><span class="label '.$ld.'">'.$this->lang->line('application_has_incident_mini').'</span>';
			}
			if (($value['exchange_request']) && (in_array('admDashboard', $this->permission))) {
				$value['numero_marketplace'] .= '<br><span class="label '.$lw.'">'.$this->lang->line('application_exchange_order').'</span>';
            }

            if ($value['finished_monitoring'] == false) {
                if (!is_null($value['status_mkt'])) {
                    if ($value['origin'] == 'VIA') {
                        if ($value['status_mkt'] == 'PAY') {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$this->lang->line('application_via_pay').'</span>';
                        }
                        else if ($value['status_mkt'] == 'PEN') {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$this->lang->line('application_via_pen').'</span>';
                        }
                        else if ($value['status_mkt'] == 'DVC') {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$this->lang->line('application_via_dvc').'</span>';
                        }
                        else if ($value['status_mkt'] == 'DLV') {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$this->lang->line('application_via_dlv').'</span>';
                        }
                        else if ($value['status_mkt'] == 'SHP') {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$this->lang->line('application_via_shp').'</span>';
                        }
                        else if ($value['status_mkt'] == 'PDL') {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$this->lang->line('application_via_pdl').'</span>';
                        }
                        else if ($value['status_mkt'] == 'PSH') {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$this->lang->line('application_via_psh').'</span>';
                        }
                        else {
                            $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$value['status_mkt'].'</span>';
                        }
                    }
					else {
                        $paid_status .= '<br><span class="label '.($postdata['internal'] == 'false' ? 'label-warning' : $lb).'"'.$tooltip.'>'.$value['origin'].": ".$value['status_mkt'].'</span>';
                    }
                }
            }

            $incidence = '';
            if ($value['incidence_user'])
                $incidence = '<br><span class="label label-warning">' . $this->lang->line('application_incidence') . '</span>';

            $last_occurrence = "";
            if ($order_status !== "") {
                if ($order_status == 'a_contratar') {
                    $last_occurrence = 'A contratar';
                } else if ($order_status == 'coletado') {
                    $last_occurrence = 'Coletado';
                } else if ($order_status == 'cancelado') {
                    $last_occurrence = 'Cancelado';
                } else if ($order_status == 'devolvido') {
                    $last_occurrence = 'Devolvido';
                }
            } else {
                if ($value['last_occurrence'] !== '') {
                    $last_occurrence = $value['last_occurrence'];
                }
            }

            if ($postdata['internal'] == 'false') {
                $link_id = "<a href='" . base_url('orders/update/' . $value['id']) . "'>" . $value['id'] . "</a>";
                if ($show_marketplace_order_id == 1) {
                    $result[$key] = array(
                        $value['numero_marketplace'],
                        $link_id,
                        $value['customer_name'],
                        // $this->formatCnpjCpf($value['cpf_cnpj']),
                        $date_time, // coluna "incluído"
                        (is_null($value['data_pago'])) ? '' : date('d/m/Y', strtotime($value['data_pago'])),
                        (is_null($value['data_limite_cross_docking'])) ? '' : date('d/m/Y', strtotime($value['data_limite_cross_docking'])),
                        //(trim($value['prazoprevisto'])) == '' ? '' : date('d/m/Y', strtotime($value['prazoprevisto'])),
                        (is_null($value['data_pago'])) || (is_null($value['ship_time_preview'])) ? '' : date('d/m/Y', strtotime($this->somar_dias_uteis(date('Y-m-d', strtotime($value['data_pago'])),$value['ship_time_preview'],'', TRUE))),
                        (trim($value['prazoprevisto'])) == '' ? '' : date('d/m/Y', strtotime($value['prazoprevisto'])),
                        $sellerCenter == 'somaplace' ? $this->formatprice($value['net_amount']) : $this->formatprice($value['gross_amount']),
	                    ($value['ship_company'] == '' ? ($value['ship_company_preview'] == 'CORREIOS' ? 'CORREIOS' : ($value['is_pickup_in_point'] == 1 ? 'RETIRADA' : 'TRANSPORTADORA')) : ($value['ship_company'] == 'CORREIOS' ? 'CORREIOS' : ($value['is_pickup_in_point'] == 1 ? 'RETIRADA' : 'TRANSPORTADORA'))),
	                    $value['store'],
                        $paid_status,
                        $last_occurrence // $value['last_occurrence'] ?? ''
	                );
				} else {
					$result[$key] = array(
	                    $link_id,
	                    $value['customer_name'],
	                    // $this->formatCnpjCpf($value['cpf_cnpj']),
	                    $date_time, // coluna "incluído"
	                    (is_null($value['data_pago'])) ?'' :date('d/m/Y', strtotime($value['data_pago'])),
	                    (is_null($value['data_limite_cross_docking'])) ?'' :date('d/m/Y', strtotime($value['data_limite_cross_docking'])),
	                    //(trim($value['prazoprevisto'])) == '' ? '' : date('d/m/Y', strtotime($value['prazoprevisto'])),
                        (is_null($value['data_pago'])) || (is_null($value['ship_time_preview'])) ? '' : date('d/m/Y', strtotime($this->somar_dias_uteis(date('Y-m-d', strtotime($value['data_pago'])),$value['ship_time_preview'],'', TRUE))),
                        (trim($value['prazoprevisto'])) == '' ? '' : date('d/m/Y', strtotime($value['prazoprevisto'])),
                        $sellerCenter == 'somaplace' ? $this->formatprice($value['net_amount']) : $this->formatprice($value['gross_amount']),
	                    ($value['ship_company'] == '' ? ($value['ship_company_preview'] == 'CORREIOS' ? 'CORREIOS' : ($value['is_pickup_in_point'] == 1 ? 'RETIRADA' : 'TRANSPORTADORA')) : ($value['ship_company'] == 'CORREIOS' ? 'CORREIOS' : ($value['is_pickup_in_point'] == 1 ? 'RETIRADA' : 'TRANSPORTADORA'))),
	                    $value['store'],
	                    $paid_status,
                        $last_occurrence // $value['last_occurrence'] ?? ''
	                );
				}
            } elseif ($postdata['internal'] == 'true') {
                $result[$key] = array(
                    $value['id'],
                    $value['numero_marketplace'] . $incidence,
                    $value['store'],
                    $value['customer_name'],
                    // $this->formatCnpjCpf($value['cpf_cnpj']),
                    $date_time, // coluna "incluído"
                    (is_null($value['data_pago'])) ? '' : date('d/m/Y', strtotime($value['data_pago'])),
                    (is_null($value['data_limite_cross_docking'])) ? '' : date('d/m/Y', strtotime($value['data_limite_cross_docking'])),
                    //(trim($value['prazoprevisto'])) == '' ? '' : date('d/m/Y', strtotime($value['prazoprevisto'])),
                    (is_null($value['data_pago'])) || (is_null($value['ship_time_preview'])) ? '' : date('d/m/Y', strtotime($this->somar_dias_uteis(date('Y-m-d', strtotime($value['data_pago'])),$value['ship_time_preview'],'', TRUE))),
                    (trim($value['prazoprevisto'])) == '' ? '' : date('d/m/Y', strtotime($value['prazoprevisto'])),
                    $sellerCenter == 'somaplace' ? $this->formatprice($value['net_amount']) : $this->formatprice($value['gross_amount']),
                    $value['is_pickup_in_point'] == 1 ? 'RETIRADA' : $value['ship_company'],
                    $paid_status,
                    $last_occurrence, // $value['last_occurrence'] ?? '',
                    $buttons
                );
            }
            if($pickupStore){
                if($pickupStore == $value['ship_company_preview']){
                    $result[$key][9] = $value['ship_company_preview'];
                }
            }
        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_orders->getOrdersDataViewCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        //$this->log_data('Orders','result',print_r($output,true));
        //ob_clean();
        echo json_encode($output);
    }

    public function getErrorOrderInvoice()
    {
        ob_start();
        $orders  = (int)$this->postClean('order_id');

        $result = $this->model_nfes->getErrorInvoiceIntegration($orders);
        ob_clean();
        echo json_encode($result);
    }

    public function sendInvoiceWithError()
    {
        ob_start();
        $orders  = (int)$this->postClean('order_id');

        $result = $this->model_nfes->sendInvoiceWithError($orders);
        ob_clean();
        echo json_encode($result);
    }

    public function getDataInvoice()
    {
        ob_start();
        $orders  = $this->postClean('order_id');

        $result = $this->model_nfes->getDataInvoice($orders);

        if ($result['link'] != "") {
            ob_clean();
            echo json_encode(array('success' => true, 'data' => $result));
            exit();
        }

        $linkTiny = $this->getLinkInvoiceTiny($result['id_nota_tiny'], $result['store_id']);
        if ($linkTiny['success'])
            $result['link'] = $linkTiny['data'][0];
        ob_clean();
        echo json_encode(array('success' => true, 'data' => $result));
    }

    function getLinkInvoiceTiny($id_tiny, $store_id)
    {

        $arrReturn['data'] = array();
        $arrReturn['success'] = false;
        // RECUPERAR KEY DA LOJA
        $getToken = $this->model_stores->getTokenInvoice($store_id);
        if (!$getToken) {
            $arrReturn['success'] = false;
            $arrReturn['data'] = array("Nao foi possivel obter o token da loja.");
            return $arrReturn;
        }
        $apikey     = $getToken;
        $urlObter = 'https://api.tiny.com.br/api2/nota.fiscal.obter.link.php';
        $data = "token={$apikey}&id={$id_tiny}&formato=json";

        try {

            $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
            ));

            $ctx = stream_context_create($params);
            $fp = @fopen($urlObter, 'rb', false, $ctx);
            if (!$fp) {
                $arrReturn['success'] = false;
                $arrReturn['data'] = array("Nao foi possivel acessar a URL(fopen): {$urlObter}");
                return $arrReturn;
            }
            $response = @stream_get_contents($fp);
            if ($response === false) {
                $arrReturn['success'] = false;
                $arrReturn['data'] = array("Nao foi possivel acessar a URL(stream_get_contents): {$urlObter}");
                return $arrReturn;
            }

            $retornoObter = $response;
            $retornoObter = json_decode($retornoObter);

            if ($retornoObter->retorno->status_processamento == 3 && $retornoObter->retorno->status == "OK") {
                $arrReturn['data'] = array($retornoObter->retorno->link_nfe);
                $arrReturn['success'] = true;
            } else {
                foreach ($retornoObter->retorno->erros as $erro) {
                    array_push($arrReturn['data'], $erro->erro);
                }
                $arrReturn['success'] = false;
            }
        } catch (Exception $e) {
            $arrReturn['success'] = false;
            $arrReturn['data'] = array($e->getMessage());
        }

        return $arrReturn;
    }

    /*
	* If the validation is not valid, then it redirects to the create page.
	* If the validation for each input field is valid then it inserts the data into the database
	* and it stores the operation message into the session flashdata and display on the manage group page
	*/
    public function create()
    {
        $sellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if (!in_array('createOrder', $this->permission) || (!in_array(ENVIRONMENT, array('development', 'local')) && $sellerCenter['value'] != 'democonectala')) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_add_order');
        $this->form_validation->set_rules('status', $this->lang->line('application_paid_status'), 'trim|required');

        if ($this->form_validation->run() == TRUE) {

            $this->db->trans_begin();

            // create client
            $cpf_cnpj = preg_replace('/[^\d\+]/', '', trim($this->postClean('customer_cpf_cnpj')));
            $rg_ie = preg_replace('/[^\d\+]/', '', trim($this->postClean('customer_rg_ie')));
            $arrClient = array(
                'customer_name'     => $this->postClean('customer_name'),
                'customer_address'  => $this->postClean('address'),
                'addr_num'          => $this->postClean('addr_num'),
                'addr_compl'        => $this->postClean('addr_compl'),
                'addr_neigh'        => $this->postClean('addr_neigh'),
                'addr_city'         => $this->postClean('addr_city'),
                'addr_uf'           => $this->postClean('addr_uf'),
                'country'           => 'BR',
                'phone_1'           => preg_replace('/[^\d\+]/', '', trim($this->postClean('customer_phone1'))),
                'phone_2'           => preg_replace('/[^\d\+]/', '', trim($this->postClean('customer_phone2'))),
                'zipcode'           => preg_replace('/[^\d\+]/', '', trim($this->postClean('zipcode'))),
                'email'             => $this->postClean('customer_email'),
                'origin'            => $this->postClean('origin'),
                'cpf_cnpj'          => $cpf_cnpj,
                'ie'                => strlen($cpf_cnpj) == 11 ? '' : $rg_ie,
                'rg'                => strlen($cpf_cnpj) == 11 ? $rg_ie : ''
            );
            $client_id = $this->model_clients->create($arrClient);
            if (!$client_id) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Erro para inserir o cliente!');
                redirect('orders/create', 'refresh');
            }

            // create items
            $orderItem = array();
            $totalProducts = 0;
            $totalDiscount = 0;
            for ($iten = 0; $iten < count($this->postClean('product_id')); $iten++) {

                $productId = $this->postClean('product_id')[$iten];
                $variant = '';
                $dataVariant = null;
                $expProductId = explode('-', $productId);
                if (count($expProductId) > 1) {
                    $variant = $expProductId[1];
                    $productId = $expProductId[0];
                }

                $dataProduct = $this->model_products->getProductData(0, $productId);
                if (!$dataProduct) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', 'Erro para encontrar o produto!');
                    redirect('orders/create', 'refresh');
                }

                $sku = $dataProduct['sku'];
                if (count($expProductId) > 1) {
                    $sku .= "-$variant";
                }

                $orderItem[] = array(
                    'order_id'      => null,
                    'product_id'    => $productId,
                    'sku'           => $sku,
                    'name'          => $dataProduct['name'],
                    'qty'           => (int)$this->postClean('product_stock')[$iten],
                    'rate'          => (float)$this->postClean('product_price')[$iten] - (float)$this->postClean('product_discount')[$iten],
                    'amount'        => (float)$this->postClean('product_price_total')[$iten],
                    'discount'      => (float)$this->postClean('product_discount')[$iten],
                    'un'            => 'Un',
                    'pesobruto'     => $dataProduct['peso_bruto'],
                    'largura'       => $dataProduct['largura'],
                    'altura'        => $dataProduct['altura'],
                    'profundidade'  => $dataProduct['profundidade'],
                    'unmedida'      => 'cm',
                    'company_id'    => $this->postClean('company'),
                    'store_id'      => $this->postClean('store'),
                    'variant'       => $variant
                );
                $totalProducts += (float)$this->postClean('product_price_total')[$iten];
                $totalDiscount += (float)number_format(((float)$this->postClean('product_discount')[$iten] * (int)$this->postClean('product_stock')[$iten]), 2, '.', '');
            }
            if ($sellerCenter['value'] == 'somaplace') {
                $totalProducts += $totalDiscount;
            }
            $grossAmount = $totalProducts + $this->fmtNum($this->postClean('ship_value'));
            $netAmount = $grossAmount;
            if ($sellerCenter['value'] == 'somaplace') {
                $netAmount -= $totalDiscount;
            }

            // valores do pedido
            $netAmount = $grossAmount * ((100 - $this->postClean('tax_service')) / 100);
            $taxService = $grossAmount - $netAmount;

            // get store
            $dataStore = $this->model_stores->getStoresData($this->postClean('store'));

            // create order
            $orderMkt = 'TEST-' . time();
            $arrOrder = array(
                'bill_no'                       => $orderMkt,
                'numero_marketplace'            => $orderMkt,
                'customer_id'                   => $client_id,
                'customer_name'                 => $this->postClean('customer_name'),
                'customer_address'              => $this->postClean('address'),
                'customer_phone'                => preg_replace('/[^\d\+]/', '', trim($this->postClean('customer_phone1'))),
                'date_time'                     => date('Y-m-d H:i:s'),
                'total_order'                   => $totalProducts,
                'discount'                      => $totalDiscount,
                'net_amount'                    => $netAmount,
                'total_ship'                    => $this->fmtNum($this->postClean('ship_value')),
                'gross_amount'                  => $grossAmount,
                'service_charge_rate'           => $this->postClean('tax_service'),
                'service_charge'                => $taxService,
                'vat_charge_rate'               => 0,
                'vat_charge'                    => 0,
                'paid_status'                   => $this->postClean('status'),
                'user_id'                       => $this->session->userdata('id'),
                'company_id'                    => $this->postClean('company'),
                'origin'                        => $this->postClean('origin'),
                'store_id'                      => $this->postClean('store'),
                'customer_address_num'          => $this->postClean('addr_num'),
                'customer_address_compl'        => $this->postClean('addr_compl'),
                'customer_address_neigh'        => $this->postClean('addr_neigh'),
                'customer_address_city'         => $this->postClean('addr_city'),
                'customer_address_uf'           => $this->postClean('addr_uf'),
                'customer_address_zip'          => preg_replace('/[^\d\+]/', '', trim($this->postClean('zipcode'))),
                'data_limite_cross_docking'     => $this->postClean('data_limite_cross_docking') ?? null,
                'data_entrega'                  => $this->postClean('data_entrega') ?? null,
                'data_envio'                    => $this->postClean('data_envio') ?? null,
                'customer_reference'            => $this->postClean('customer_reference'),
                'data_pago'                     => $this->postClean('data_pago') ?? null,
                'ship_company_preview'          => $this->postClean('ship_company_preview'),
                'ship_service_preview'          => $this->postClean('ship_service_preview'),
                'ship_time_preview'             => $this->postClean('ship_time_preview'),
                'freight_seller'                => $dataStore['freight_seller'] ?? 0,
                'service_charge_freight_value'  => $dataStore['service_charge_freight_value'] ?? 0,
                'order_manually'                => 1
            );

            $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
            $sellerCenter = $settingSellerCenter['value'];

            if ($sellerCenter != 'conectala') {
                $arrOrder['total_order']    = $totalProducts + $totalDiscount;
                $arrOrder['gross_amount']   = $totalProducts + $totalDiscount + $arrOrder['total_ship'];
                $arrOrder['net_amount']     = $arrOrder['gross_amount'] - $totalDiscount;
            }


            $order_id = $this->model_orders->insertOrder($arrOrder);
            if (!$order_id) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Erro para criar o pedido!');
                redirect('orders/create', 'refresh');
            }

            //add order id nos itens e cria
            foreach ($orderItem as $key => $iten) {
                $orderItem[$key]['order_id'] = $order_id;
                $order_iten = $this->model_orders->insertItem($orderItem[$key]);
                if (!$order_iten) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', 'Erro para criar o item do pedido!');
                    redirect('orders/create', 'refresh');
                }
            }

            // create payments
            if ($this->postClean('payment_parcel') && count($this->postClean('payment_parcel'))) {
                for ($payment_count = 0; $payment_count < count($this->postClean('payment_parcel')); $payment_count++) {
                    $payment_parcel      = $this->postClean("payment_parcel")[$payment_count];
                    $payment_date        = dateTimeBrazilToDateInternational($this->postClean("payment_date")[$payment_count]);
                    $payment_value       = $this->postClean("payment_value")[$payment_count];
                    $payment_description = $this->postClean("payment_description")[$payment_count];
                    $payment_type        = $this->postClean("payment_type")[$payment_count];

                    $order_payment = $this->model_orders->insertParcels(array(
                        'order_id'      => $order_id,
                        'parcela'       => $payment_parcel,
                        'bill_no'       => $orderMkt,
                        'data_vencto'   => $payment_date,
                        'valor'         => forceNumberToFloat($payment_value),
                        'forma_id'      => $payment_type,
                        'forma_desc'    => $payment_description
                    ));
                    if (!$order_payment) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Erro para criar o pagamento do pedido!');
                        redirect('orders/create', 'refresh');
                    }
                }
            }

            if ($this->postClean('status') != 1 && $this->postClean('status') != 3 && $this->postClean('set_nfe_order')) {
                $nfeNumber  = $this->postClean("nfe_number");
                $nfeSerie   = $this->postClean("nfe_serie");
                $nfeDate    = $this->postClean("nfe_date");
                $nfeKey     = $this->postClean("nfe_key");

                $order_invoice = $this->model_nfes->create(array(
                    'order_id'      => $order_id,
                    'company_id'    => $this->postClean('company'),
                    'date_emission' => $nfeDate,
                    'nfe_value'     => $grossAmount,
                    'nfe_serie'     => $nfeSerie,
                    'nfe_num'       => $nfeNumber,
                    'chave'         => $nfeKey,
                    'store_id'      => $this->postClean('store'),
                ));
                if (!$order_invoice) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', 'Erro para criar a nota fiscal!');
                    redirect('orders/create', 'refresh');
                }

                $this->model_promotions->updatePromotionByStock($iten['product_id'], $iten['qty'], $iten['rate'] + $iten['discount']);
                $this->model_products->reduzEstoque($iten['product_id'], $iten['qty'], $iten['variant'], $order_id);
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('orders/create', 'refresh');
            }

            $this->db->trans_commit();

            $this->calculofrete->createQuoteShipRegister($order_id);


            //Verificando se existe webhook cadastrado para essa loja no momento da criação
            $this->load->model('model_integrations_webhook');

            if ($this->model_integrations_webhook->storeExists($arrOrder['store_id'])) {
                $this->load->library('ordersmarketplace');

                $store_id_wh = $arrOrder['store_id'];
                $typeIntegration = "pedido_criado";
                $this->ordersmarketplace->formatsendDataWebhook($store_id_wh, $typeIntegration, $order_id, $arrOrder);
            }


            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            redirect('orders/update/' . $order_id, 'refresh');
        } else {
            // false case
            $this->data['company_data'] = $this->model_company->getMyCompanyData();
            $this->data['origin_data'] = $this->model_integrations->get_integrations_list();

            $this->render_template('orders/create', $this->data);
        }
    }

    /*
     * It gets the product id passed from the ajax method.
     * It checks retrieves the particular product data from the product id
     * and return the data into the json format.
     */
    public function getProductValueById()
    {
        ob_start();
        $product_id = $this->postClean('product_id');
        if ($product_id) {
            $product_data = $this->model_products->getProductData($product_id);
            ob_clean();
            echo json_encode($product_data);
        }
    }

    /*
     * It gets the all the active product inforamtion from the product table
     * This function is used in the order page, for the product selection in the table
     * The response is return on the json format.
     */
    public function getTableProductRow()
    {
        ob_start();
        $products = $this->model_products->getActiveProductData();
        ob_clean();
        echo json_encode($products);
    }

    public function updateAddress()
    {

        if (!in_array('chageDeliveryAddress', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($this->uri->segment('3') != null) {
            $id = $this->uri->segment('3');
        } else {
            $id = $this->postClean('id');
        }

        $order_data = $this->model_orders->verifyOrderOfStore($id);

        $this->form_validation->set_rules('customer_address', $this->lang->line('application_address'), 'trim|required');
        $this->form_validation->set_rules('customer_address_num', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('customer_address_neigh', $this->lang->line('application_neighb'), 'trim|required');
        $this->form_validation->set_rules('customer_address_city', $this->lang->line('application_uf'), 'trim|required');
        $this->form_validation->set_rules('customer_address_zip', $this->lang->line('application_zip_code'), 'trim|required');

        if ($this->form_validation->run() == TRUE) {


            $data = array(
                'customer_address' => $this->postClean('customer_address'),
                'customer_address_num' => $this->postClean('customer_address_num'),
                'customer_address_neigh' => $this->postClean('customer_address_neigh'),
                'customer_address_city' => $this->postClean('customer_address_city'),
                'customer_address_uf' => $this->postClean('customer_address_uf'),
                'customer_address_zip' => $this->postClean('customer_address_zip'),
                'customer_reference' => $this->postClean('customer_reference'),
                'customer_address_compl' => $this->postClean('customer_address_compl'),
            );

            if ($this->model_orders->updateAddress($data, $id)) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                get_instance()->log_data('Orders', 'edit address', json_encode($data), "I");
                redirect('orders/update/' . $id, 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('orders/update/' . $id, 'refresh');
            }
        }

        if (in_array($order_data['paid_status'], $this->data['paid_status_authorized_change_address'])) {
            $this->data['order_data'] = $order_data;
            $this->render_template('orders/updateaddress', $this->data);
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            redirect('orders/update/' . $id, 'refresh');
        }
    }


    private function getNotificationPopup($order, $items) {
        $notification = null;
        foreach ($items as $item) {
            $category_id = str_replace('"]', '', str_replace('["', '', $item['category_id']));
            $records = $this->model_notification_popup->getNotificationByCategory('orders/edit', $category_id);
            if (!is_null($records)) {
                foreach($records as $record) {
                    $body = json_decode($record['body'], true);
                    if ($body['paid_status'] == $order['paid_status']) {
                        if ($this->checkIfNotificationPopupCanShow($record, $order['store_id'])) {
                            if (!is_null($notification)) {
                                if ($notification['priority'] > $record['priority']) {
                                    $notification = $record;
                                }
                            }
                            else {
                                $notification = $record;
                            }
                            $notification['paid_status'] = $body['paid_status'];
                        }
                    }
                }
            }
        }

        if (!is_null($notification)) {
            $this->markNotificationPopupShowed($notification, $order['store_id']);
        }

        return $notification;
    }

    private function checkIfNotificationPopupCanShow($notification, $store_id) {
        $canShow = false;

        $control = $this->model_notification_popup->getNotificationAmountShowedByStore($notification['id'], $store_id);

        if (is_null($control)) {
            $canShow = true;
        }
        else {
            if ($control['notification_showed'] % $notification['presentation_interval'] == 0) {
                $canShow = true;
            }
            $this->markNotificationPopupShowed($notification, $store_id);
        }

        return $canShow;
    }

    private function markNotificationPopupShowed($notification, $store_id) {
        $control = $this->model_notification_popup->getNotificationAmountShowedByStore($notification['id'], $store_id);
        if (is_null($control)) {
            $this->model_notification_popup->insertControlNotificationPopup($notification['id'], $store_id);
        }
        else {
            $this->model_notification_popup->updateControlNotificationPopup($notification['id'], $store_id);
        }
    }

    /*
     * If the validation is not valid, then it redirects to the edit orders page
     * If the validation is successfully then it updates the data into the database
     * and it stores the operation message into the session flashdata and display on the manage group page
     */
    public function update($id=null)
    {

        if (!in_array('updateOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!$id) {
            redirect('dashboard', 'refresh');
        }

        $orders_data = $this->model_orders->verifyOrderOfStore($id);

        if (!$orders_data) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_update_order');
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');                

        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
            $this->data['sellercenter'] = $settingSellerCenter['value'];
        } else {
            $this->data['sellercenter'] = 'conectala';
        }

        $this->form_validation->set_rules('paid_status', $this->lang->line('application_paid_status'), 'trim|required');

        $this->data['order_value_refund_on_gateways'] = $this->model_order_value_refund_on_gateways->getByOrderId($id);
        $this->data['product_return'] = $this->model_product_return->getByOrderId($id);
        $this->data['has_order_value_refund'] = !empty($this->data['order_value_refund_on_gateways']);
        $this->data['days_to_refund_value_tuna'] = $this->model_settings->getValueIfAtiveByName('days_to_refund_value_tuna');

        if ($this->form_validation->run() == TRUE) {

            /*
             $update = $this->model_orders->update($id);

             if($update == true) {
             $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
             redirect('orders/update/'.$id, 'refresh');
             }
             else {
             $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
             redirect('orders/update/'.$id, 'refresh');
             }
             */
            redirect('orders/update/' . $id, 'refresh'); // não tem update de ordem
        } else {
            // false case
            $company = $this->model_company->getCompanyData(1);
            $this->data['company_data'] = $company;
            $this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
            $this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;

            $result = array();

            $intervalo_orientacao_moveis = $this->model_settings->getValueIfAtiveByName('intervalo_orientacao_moveis');
            $setting_categoria_moveis = $this->model_settings->getValueIfAtiveByName('categoria_moveis');
            $categorias_moveis = false;


            $result['order'] = $orders_data;

            $orders_item = $this->model_orders->getOrdersItemData($orders_data['id']);
            $order_items_cancel = $this->model_order_items_cancel->getByOrderId($orders_data['id']);
            $this->data['has_canceled_item'] = false;

            $i = 0;
            $qtds = 0;
            $result['order_item'] = [];
            foreach ($orders_item as $k => $v) {
                $i++;
                $result['order_item'][$k] = $v;
                // $result['order_item'][$k]['picture'] = $v['principal_image'];
                $product_data = $this->model_products->getProductData(0, $v['product_id']);
                $result['order_item'][$k]['status'] = isset($product_data['id']) ? $product_data['status'] : Model_products::DELETED_PRODUCT;
                $is_on_bucket = $product_data['is_on_bucket'];
                if ($v['variant'] != '') {
                    $result['order_item'][$k]['name'] .= ' (';
                    $variants_prd = $this->model_products->getVariantSkuOrder($v['product_id'], $v['variant']);

                    $result['order_item'][$k]['picture'] = '';
                    $diretorios = [];
                    if(!$is_on_bucket){
                        if (file_exists(FCPATH . 'assets/images/product_image/' . $product_data['image'])){
                            $diretorios = scandir(FCPATH . 'assets/images/product_image/' . $product_data['image']);
                        }

                        if($variants_prd['image'] != ''){
                            foreach($diretorios as $diretorio) {
                                if ($diretorio == $variants_prd['image']) {
                                    if ($result['order_item'][$k]['picture'] == '') {
                                        $path = scandir(FCPATH.'assets/images/product_image/'.$product_data['image'].'/'.$variants_prd['image']);
        
                                        foreach($path as $foto) {
                                            // var_dump($foto);
                                            if(($foto != ".") && ($foto != "..") && ($foto != "")){
                                                if ((pathinfo($foto)['extension'] == 'jpg') || (pathinfo($foto)['extension'] == 'png')) {
                                                    $result['order_item'][$k]['picture'] = base_url('assets/images/product_image/').$product_data['image'].'/'.$variants_prd['image'] . '/' . $foto;
                                                }
                                            }else{
                                                $result['order_item'][$k]['picture'] = $v['principal_image'];
                                            }
                                            
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                        else{
                            $result['order_item'][$k]['picture'] = $v['principal_image'];
                        }
                    }else{
                        if($variants_prd['image']){
                            $imagens = $this->bucket->getFinalObject('assets/images/product_image/' . $product_data['image']. '/' . $variants_prd['image']);
                            foreach ($imagens['contents'] as $imagem) {
                                    $result['order_item'][$k]['picture'] = $imagem['url'];
                                    break;
                            }

                        }else{
                            $result['order_item'][$k]['picture'] = $v['principal_image'];
                        }
                    }
                    $result['order_item'][$k]['sku'] = $variants_prd["sku"];
                    $result['order_item'][$k]['status'] = $variants_prd['status'] ?? $product_data['status'];
                    if (strpos($product_data['has_variants'], ";") > 0) {
                        $variants_names = explode(';', $product_data['has_variants']);
                        $variants_value = explode(';', $variants_prd['name']);
                        $i_var = 0;
                        $result['order_item'][$k]['variant_text'] =  '';
                        foreach ($variants_names as $variants_name) {
                            if (isset($variants_value[$i_var]) && $variants_value[$i_var] != '') {
                                $result['order_item'][$k]['name'] .= $variants_name . ":" . $variants_value[$i_var] . "; ";
                            }
                            $i_var++;
                        }
                    } else {
                        $result['order_item'][$k]['name'] .= $product_data['has_variants'] . ":" . $variants_prd['name'];
                    }
                    $result['order_item'][$k]['name'] .= ' )';
                    if ($variants_prd) {
                        $result['order_item'][$k]['stock'] = $variants_prd['qty'];
                    }
                }
                else {
                    $result['order_item'][$k]['picture'] = $v['principal_image'];
                }

                $itenCatalog = '';
                if ($product_data['product_catalog_id']) {
                    $getCatalogId = $this->model_products_catalog->getCatalogByProduct($product_data['id']);
                    $itenCatalog  = $getCatalogId['name'];
                }

                $item_cancel = getArrayByValueIn($order_items_cancel, $v['id'], 'item_id');
                if ($item_cancel) {
                    $this->data['has_canceled_item'] = true;
                }

                $result['order_item'][$k]['catalog'] = $itenCatalog;
                $result['order_item'][$k]['qty_canceled'] = $item_cancel['qty'] ?? 0;
                $result['order_item'][$k]['total_amount_canceled_mkt'] = $item_cancel ? $item_cancel['total_amount_canceled_mkt'] : null;
                $result['order_item'][$k]['created_at_cancelled'] = $item_cancel ? $item_cancel['created_at'] : null;
                $result['order_item'][$k]['email_user_cancelled'] = $item_cancel ? ($this->model_users->getUserById($item_cancel['user_id'])['email'] ?? '') : null;

                $qtds = $qtds + $v['qty'];
            }

            $this->data['has_notification_popup'] = false;
            $notification_popup = $this->getNotificationPopup($orders_data, $orders_item);
            if (!is_null($notification_popup)) {
                $this->data['has_notification_popup'] = true;
                $this->data['url_notification_popup'] = $notification_popup['url'];
            }

            if (empty($result['order_item'])) {
                $result['order_item'] = [];
            }
            $result['order']['num_items'] = $i;
            $result['order']['sum_qty'] = $qtds;

            $result['order']['loja'] = $this->model_stores->getStoresData($orders_data['store_id']);
            $result['order']['empresa'] = $this->model_company->getCompanyData($orders_data['company_id']);
            $cliente = $this->model_clients->getClientsData($orders_data['customer_id']);
            $cliente['cpf_cnpj'] = $this->formatCnpjCpf($cliente['cpf_cnpj']);
            $result['order']['cliente'] = $cliente;

            $freights = $this->model_orders->getItemsFreightsGroupCodeTracking($id);
            $i = 0;
            $val = 0;
            foreach ($freights as $k => $v) {
                $i++;
                $result['freights'][] = $v;
                $val = $val + $v['ship_value'];
            }
            $result['order']['freight'] = $val;

            if (count($freights)>0) {
                foreach ($freights as $key => $freight) {
                    $ocorrencias = $this->model_frete_ocorrencias->getFreteOcorrenciasDataByFreightsId($freight['id']);
                    foreach ($ocorrencias as $v) {
                        $codeTraking = $freight['in_resend_active'] ? $freight['codigo_rastreio'] . '(REENVIO)' : $freight['codigo_rastreio'];
                        $result['frete_ocorrencias'][$codeTraking][] = $v;
                    }
                }
            }

            $nfes = $this->model_orders->getOrdersNfes($id);
            $i = 0;
            foreach ($nfes as $k => $v) {
                $i++;
                $result['nfes'][] = $v;
            }
            $parcs = $this->model_orders->getOrdersParcels($id);
            $i = 0;
            foreach ($parcs as $k => $v) {
                $i++;

                switch ($v['forma_id']) {
                    case 'creditCard':
                        $v['forma_id'] = $this->lang->line('application_credit_card');
                        break;
                    case 'debitCard':
                        $v['forma_id'] = $this->lang->line('application_debit_card');
                        break;
                    case 'bankInvoice':
                        $v['forma_id'] = $this->lang->line('application_bank_invoice');
                        break;
                    case 'giftCard':
                        $v['forma_id'] = $this->lang->line('application_gift_card');
                        break;
                    case 'instantPayment':
                        $v['forma_id'] = $this->lang->line('application_instant_payment');
                        break;
                }

                $result['pagtos'][] = $v;
            }

            $interactionsPayment = $this->model_order_payment_transactions->getLastTransactionsByOrder($id);
            $result['interactionsPayment'] = $interactionsPayment;

            $this->data['order_data'] = $result;

            // $this->data['products'] = $this->model_products->getActiveProductData();
            $this->log_data('Orders', 'edit before', json_encode($orders_data), "I");

            $notCancelStatus    = array(6, 60, 95, 96, 97, 98, 99);
            $notCancelStatusADM = array(60, 95, 96, 97, 98, 99);
            $this->data['cancelavel'] = !in_array((int)$result['order']['paid_status'], $notCancelStatus) || (!in_array((int)$result['order']['paid_status'], $notCancelStatusADM) && in_array('admDashboard', $this->permission));

            $status_str = $this->lang->line('application_order_' . $orders_data['paid_status']);
            if (($orders_data['paid_status'] == 51) || ($orders_data['paid_status'] == 53)) {
                $hasLabel = $this->model_freights->getFreightsHasLabel($orders_data['id']);
                if (!empty($hasLabel)) {
                    $status_str = $this->lang->line('application_order_4');
                }
            }
            $this->data['pedido_cancelado'] = false;

            $this->data['commision_charges'] = null;

            $CommisionCharges = $this->model_legal_panel->getDataOrdersCommisionChargesByOrderId($orders_data['id']);

            if($CommisionCharges){
                $this->data['commision_charges'] = $CommisionCharges;
            }

            if (in_array((int)$orders_data['paid_status'],  array(95, 96, 97, 98, 99))) {
                $this->data['pedido_cancelado'] = $this->model_orders->getPedidosCanceladosByOrderId($orders_data['id']);
            }

            // $this->data['cancel_reasons'] = $this->model_attributes->getAttributeValuesByName('cancel_reasons');
            $this->data['cancel_reasons'] = $this->model_attributes->getAttributeValuesAndIdByNameCancelOrders('cancel_reasons');

            $arrPenalties = array();
            //$cancelPenaltiesTo = $this->model_attributes->getAttributeValuesByName('cancel_penalty_to');
            $cancelPenaltiesTo = $this->model_attributes->getAttributeValuesAndIdByNameCancelOrders('cancel_penalty_to');

            foreach ($cancelPenaltiesTo as $cancelPenaltyTo) {
                if (!in_array('admDashboard', $this->permission) && !in_array('deleteOrder', $this->permission) && $cancelPenaltyTo['value'] != '1-Seller') continue;
                // Alterado para descobrirmos o ID da razão e salvarmos ela no banco
                $arrPenalties[$cancelPenaltyTo['id']] =  $cancelPenaltyTo['value'];
            }

            $this->data['cancel_penalty_to'] = $arrPenalties;

            $this->data['status_str'] = $status_str;

            $change_seller_set = $this->model_settings->getSettingDatabyName('change_seller');
            $this->data['use_change_seller'] = $change_seller_set && $change_seller_set['status'] == 1;

            // set seller center
            $sellerCenter = 'conectala';


            //$orderMediation = $this->model_orders_mediation->getOrderMediationData($id);
            //$this->data['orderMediation']  = (isset($orderMediation)) ? 1 : 0;
            //$this->data['orderMediationResolved']  = $orderMediation['resolved'];

            $this->data['orderMediation']  = 0;
            $this->data['orderMediationResolved']  = 1;

            $this->data['occurrence'] = '';
            if (($orders_data['paid_status'] == 58) && (!empty($result['frete_ocorrencias']))) {
                foreach ($result['frete_ocorrencias'] as $frete_ocorrencias) {
                    if (empty($this->data['occurrence'])) {
                        $this->data['occurrence'] = getArrayByValueIn($frete_ocorrencias, "Objeto encaminhado para retirada no endereço indicado", 'nome');
                    }
                    if (empty($this->data['occurrence'])) {
                        $this->data['occurrence'] = getArrayByValueIn($frete_ocorrencias, "Objeto aguardando retirada no endereço indicado", 'nome');
                    }
                }
            }

            $this->data['problem_order'] = null;
            if ($orders_data['paid_status'] == 80) {
                $this->data['problem_order'] = $this->model_orders_with_problem->getProblemsByOrder($orders_data['id']);
            }

            $this->data['reasonRequestCancel'] = null;
            if ($orders_data['paid_status'] == 90)
                $this->data['reasonRequestCancel'] = $this->model_requests_cancel_order->getLastReasonByOrder($id);

            // motivos de solicitação de cancelamento. orders.paid_status=94
            $arrReasonRequestCancel = array();
            $requestCancelReason = $this->model_attributes->getAttributeValuesByName('request_cancel_reason');
            foreach ($requestCancelReason as $cancelReason)
                array_push($arrReasonRequestCancel, $cancelReason['value']);

            $this->data['cancel_reason'] = $arrReasonRequestCancel;

            $this->data['hide_taxes'] = $sellerCenter == 'somaplace' ? true : false;
            $this->data['gsoma_painel_financeiro'] = $this->model_settings->getStatusbyName('gsoma_painel_financeiro');
			$this->data['tipo_frete'] = $this->model_orders->getMandanteFretePedido($orders_data['id']);

			//fin-685
			/*if (ENVIRONMENT == 'development')
			{*/
				$somaPLace = $this->model_settings->getValueIfAtiveByName('sellercenter');

				$tipo_frete = $this->model_orders->getMandanteFretePedido($orders_data['id']);

				$campaign_data = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($orders_data['id']);

				// $tax_pricetags     = $campaign_data['total_pricetags'] * ($orders_data['service_charge_rate'] / 100);
                $tax_pricetags     = 0;
				$comissao_frete    = $orders_data['total_ship'] * ($orders_data['service_charge_freight_value'] / 100);
				$comissao_campanha = $campaign_data['total_channel'] * ($orders_data['service_charge_rate'] / 100);
				$total_rebate      = (empty($campaign_data['total_rebate'])) ? 0 : $campaign_data['total_rebate'] * ($orders_data['service_charge_rate'] / 100);
				// $reembolso_mkt     = ($campaign_data['total_channel'] - ($campaign_data['total_channel'] * ($orders_data['service_charge_rate'] / 100))) + ($campaign_data['total_pricetags'] - $tax_pricetags);
                $reembolso_mkt     = $campaign_data['total_channel'];

				if ($somaPLace && $somaPLace == 'somaplace')
				{
					$comissao_produto = ($orders_data['net_amount'] - $orders_data['total_ship'] ) * ($orders_data['service_charge_rate'] / 100);
				}
				else
				{
					$comissao_produto = ($orders_data['gross_amount'] - $orders_data['total_ship'] ) * ($orders_data['service_charge_rate'] / 100);
				}

				$valor_total = ( $comissao_produto + $comissao_frete + $comissao_campanha + $tax_pricetags) - ($campaign_data['comission_reduction'] + $total_rebate + $reembolso_mkt);

				$this->data['tipo_frete']['expectativaReceb'] = $orders_data['gross_amount'] - $valor_total;
				$this->data['tipo_frete']['taxa_descontada']  = $valor_total;
			// }

            $this->data['queue_order_integration'] = $this->model_orders_to_integration->getOrdersQeueuByOrder($orders_data['id']);
            $this->data['status_now'] = $this->model_orders->getOrderStatusNow($this->data['order_data']['order']['id']);
            foreach($this->data['status_now'] as $key => $status){
                $status = $this->lang->line('application_order_' . $status['status']);
                $this->data['status_now'][$key]['status'] = $status;
            }

            $this->data['order_error_integration'] = null;
            if (ENVIRONMENT === 'development') {
                // Se o pedido está em algum dos status abaixo, não será verificado se existe erro.
                if (!in_array($orders_data['paid_status'], array(1, 2, 96, 52, 51, 55, 60, 99, 97, 6, 95, 98))) {
                    $orderErrorIntegration = $this->model_log_integration->getLastLog($orders_data['store_id'], $orders_data['id']);
                    // O último status é de erro.
                    if ($orderErrorIntegration && $orderErrorIntegration->type === 'E') {
                        $this->data['order_error_integration'] = $orderErrorIntegration;
                    }
                }
            }

            $totalTaxs = 0;            
            $hasCampaigns = $this->model_orders->getDetalheTaxasModalOrders($id);
            if(count($hasCampaigns['campaigns']) > 0) {
                foreach ($hasCampaigns['campaigns'] as $campaign){
                    $totalTaxs += $campaign['total_desconto'];
                }
            }

            $this->data['pickup_point_order'] = null;
            if ($orders_data['is_pickup_in_point']) {
                try {
                    $pickup_point_id = str_replace('PickUpPoint_', '', $orders_data['ship_service_preview']);
                    if ($this->ms_pickup_points->use_ms_shipping) {
                        $pickup_point = $this->ms_pickup_points->getPickupPoint($pickup_point_id);
                        $pickup_point = $pickup_point->data;
                    } else {
                        $pickup_point = $this->model_pickup_point->getById($pickup_point_id);
                    }

                    $this->data['pickup_point_order'] = $pickup_point;
                } catch (Exception $exception) {}
            }
            
            $this->data['totalTax'] = $totalTaxs;

            // Teve troca de pedido e o usuário é da empresa 1.
            $this->data['stores_multi_channel_fulfillment'] = array();
            if (!empty($orders_data['multi_channel_fulfillment_store_id']) && $this->session->userdata('usercomp') == 1) {
                $this->data['stores_multi_channel_fulfillment'] = $this->model_change_seller_histories->getByOrderIdWithOldStoreNameAndIgnoreOldStoreId($id, $orders_data['multi_channel_fulfillment_store_id']);
            }

            //Verifica o Parâmetro de edição de cancelamento
            $allowChanceCancelReason = false;
            $SettingsAllowChanceCancelReason = $this->model_settings->getSettingDatabyName('allow_change_cancel_reason_penalty_to');
            if($SettingsAllowChanceCancelReason['status'] == 1){
                $allowChanceCancelReason = true;
            }

            $this->data['allowChanceCancelReason'] = $allowChanceCancelReason;
            $this->data['forced_to_delivery'] = $this->model_orders->wasUpdatedByOrderToDelivered($id);
            //Verifica se o pedido tem data de cancelamento
            //Caso tenha ele faz a conta se está dentro do período de estorno e volta true para tela
            $maximumDaysToRefundComission = $this->model_settings->getSettingDatabyName('maximum_days_to_refund_comission');
            $checkMaximumDaysToRefundComission = false;
            if($maximumDaysToRefundComission['status'] == 1 && is_numeric($maximumDaysToRefundComission['value'])){
                if($orders_data['date_cancel']){

                    $dateCancelCHeck = substr($orders_data['date_cancel'],0,10);
                    $dataCancelSomada = date('Y-m-d', strtotime($dateCancelCHeck. ' + '.$maximumDaysToRefundComission['value'].' days'));
                    $dataAtual = date("Y-m-d");

                    if($dataAtual <= $dataCancelSomada){
                        $checkMaximumDaysToRefundComission = true;
                    }
                }
            }

            $this->data['checkMaximumDaysToRefundComission']  = $checkMaximumDaysToRefundComission;
$this->data['invoice_error_reason'] = null;
            if ($orders_data['paid_status'] == $this->model_orders->PAID_STATUS['invoice_with_error']) {
                $external_integration_history = $this->model_external_integration_history->getByRegisterIdAndTypeAndMethod($orders_data['id'], 'nfe', 'validation');
                if (!empty($external_integration_history)) {
                    $this->data['invoice_error_reason'] = $external_integration_history[0]['response_webhook'];
                }
            }
            $this->render_template('orders/edit', $this->data);
        }
    }

    public function getModalTaxDetail($orderId){

        $result = $this->model_orders->getDetalheTaxasModalOrders($orderId);
        $result['sellercenter'] = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $result['user_permission'] = $this->permission;

        echo $this->load->view('orders/modal_campaign_desconto_comissao_details', $result, TRUE);
    
    }
    
    public function getModalDiscountDetail($orderId){

        $result = $this->model_orders->getDetalheDescontosModalOrders($orderId);

        echo $this->load->view('orders/modal_campaign_desconto_produto_details', $result, TRUE);

    }

    public function view($id)
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!$id) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_view_order');

        $company = $this->model_company->getCompanyData(1);
        $this->data['company_data'] = $company;
        $this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
        $this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;

        $result = array();
        $orders_data = $this->model_orders->getOrdersData(0, $id);

        $result['order'] = $orders_data;

        $orders_item = $this->model_orders->getOrdersItemData($orders_data['id']);
        $i = 0;
        $qtds = 0;
        foreach ($orders_item as $k => $v) {
            $i++;
            $product_data = $this->model_products->getProductData(0, $v['product_id']);
            $result['order_item'][$k] = $v;
            $result['order_item'][$k]['status'] = isset($product_data['id']) ? $product_data['status'] : Model_products::DELETED_PRODUCT;
            $qtds = $qtds + $v['qty'];
            if ($v['variant'] != '') {
                $result['order_item'][$k]['name'] .= ' (';
                $product_data = $this->model_products->getProductData(0, $v['product_id']);
                $variants_prd = $this->model_products->getVariants($v['product_id'], $v['variant']);
                $result['order_item'][$k]['status'] = $variants_prd['status'] ?? $product_data['status'];
                if (strpos($product_data['has_variants'], ";") > 0) {
                    $variants_names = explode(';', $product_data['has_variants']);
                    $variants_value = explode(';', $variants_prd['name']);
                    $i = 0;
                    $result['order_item'][$k]['variant_text'] =  '';
                    foreach ($variants_names as $variants_name) {
                        $result['order_item'][$k]['name'] .= $variants_name . ":" . $variants_value[$i++] . "; ";
                    }
                } else {
                    $result['order_item'][$k]['name'] .= $product_data['has_variants'] . ":" . $variants_prd['name'];
                }
                $result['order_item'][$k]['name'] .= ' )';
            }
        }
        $result['order']['num_items'] = $i;
        $result['order']['sum_qty'] = $qtds;

        $result['order']['loja'] = $this->model_stores->getStoresData($orders_data['store_id']);
        $result['order']['empresa'] = $this->model_company->getCompanyData($orders_data['company_id']);
        $cliente = $this->model_clients->getClientsData($orders_data['customer_id']);
        $cliente['cpf_cnpj'] = $this->formatCnpjCpf($cliente['cpf_cnpj']);
        $result['order']['cliente'] = $cliente;

        $freights = $this->model_orders->getItemsFreights($id);
        $i = 0;
        $val = 0;
        foreach ($freights as $k => $v) {
            $i++;
            $result['freights'][] = $v;
            $val = $val + $v['ship_value'];
        }
        $result['order']['freight'] = $val;

        if (count($freights) > 0) {
            $ocorrencias = $this->model_frete_ocorrencias->getFreteOcorrenciasDataByFreightsId($freights[0]['id']);
            foreach ($ocorrencias as $k => $v) {
                $result['frete_ocorrencias'][] = $v;
            }
        }

        $nfes = $this->model_orders->getOrdersNfes($id);
        $i = 0;
        foreach ($nfes as $k => $v) {
            $i++;
            $result['nfes'][] = $v;
        }

        $parcs = $this->model_orders->getOrdersParcels($id);
        $i = 0;
        foreach ($parcs as $k => $v) {
            $i++;
            $result['pagtos'][] = $v;
        }

        $this->data['order_data'] = $result;

        $status_str = $this->lang->line('application_order_' . $orders_data['paid_status']);
        if (($orders_data['paid_status'] == 51) || ($orders_data['paid_status'] == 53)) {
            $hasLabel = $this->model_freights->getFreightsHasLabel($orders_data['id']);
            if (!empty($hasLabel)) {
                $status_str = $this->lang->line('application_order_4');
            }
        }
        $this->data['status_str'] = $status_str;

        $this->data['pedido_cancelado'] = false;
        if (in_array((int)$result['order']['paid_status'],  array(95, 96, 97, 98, 99))) {
            $this->data['pedido_cancelado'] = $this->model_orders->getPedidosCanceladosByOrderId($result['order']['id']);
        }

        $this->render_template('orders/view', $this->data);
    }

    function formatCnpjCpf($value)
    {
        $cnpj_cpf = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }
    /*
     * It removes the data from the database
     * and it returns the response into the json format
     */
    public function remove()
    {
        ob_start();
        if (!in_array('deleteOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $order_id = $this->postClean('order_id');

        $response = array();
        if ($order_id) {
            $delete = $this->model_orders->remove($order_id);
            if ($delete == true) {
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_removed');
            } else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_remove_product');
            }
        } else {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_refresh_page_again');
        }
        ob_clean();
        echo json_encode($response);
    }

    /*
     * It gets the product id and fetch the order data.
     * The order print logic is done here
     */
    public function printDiv($id)
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        if ($id) {
            $order_data = $this->model_orders->getOrdersData(0, $id);
            $orders_items = $this->model_orders->getOrdersItemData($id);
            $usercomp = $this->session->userdata('usercomp');
            $company_info = $this->model_company->getCompanyData($usercomp);
            $currency = $company_info['currency'];
            $order_date = date('d/m/Y', strtotime($order_data['date_time']));
            $paid_status = $this->lang->line('application_order_' . $order_data['paid_status']);

            $cliente = $this->model_clients->getClientsData($order_data['customer_id']);
            $cliente['cpf_cnpj'] = $this->formatCnpjCpf($cliente['cpf_cnpj']);

            $nfes = $this->model_orders->getOrdersNfes($id);
            $parcs = $this->model_orders->getOrdersParcels($id);

            $html = '<!-- Main content -->
			<!DOCTYPE html>
			<html>
			<head>
			  <meta charset="utf-8">
			  <meta http-equiv="X-UA-Compatible" content="IE=edge">
			  <title> ' . $company_info['name'] . '| Invoice</title>
			  <!-- Tell the browser to be responsive to screen width -->
			  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
			  <!-- Bootstrap 3.3.7 -->
			  <link rel="stylesheet" href="' . base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css') . '">
			  <!-- Font Awesome -->
			  <link rel="stylesheet" href="' . base_url('assets/bower_components/font-awesome/css/font-awesome.min.css') . '">
			  <link rel="stylesheet" href="' . base_url('assets/dist/css/AdminLTE.min.css') . '">
			</head>
			<body onload="window.print();">
			      
			<div class="wrapper">
			  <section class="invoice">
			    <!-- title row -->
			    <div class="row">
			      <div class="col-xs-12">
    		        <span class="logo-lg"><img src="' . base_url() . $company_info['logo'] . '"  width="200"></span>
			        <h2 class="page-header">
			          ' . $company_info['name'] . '
			          <small class="pull-right">Date: ' . $order_date . '</small>
			        </h2>
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- info row -->
			    <div class="row invoice-info">
			              
			      <div class="col-sm-4 invoice-col">
			        <b>' . $this->lang->line('application_order') . ':</b> ' . $order_data['id'] . '<br>
			        <b>' . $this->lang->line('application_origin') . ':</b> ' . $order_data['origin'] . '<br>
			        <b>' . $this->lang->line('application_order_marketplace_full') . ':</b> ' . $order_data['numero_marketplace'] . '<br>
			        <b>' . $this->lang->line('application_order_bling') . ':</b> ' . $order_data['bill_no'] . '<br>
			        <b>' . $this->lang->line('application_name') . ':</b> ' . $order_data['customer_name'] . '<br>
			        <b>' . $this->lang->line('application_phone') . ':</b> ' . $order_data['customer_phone'] . '<br>
			      </div>
			      <!-- /.col -->
			    </div>';

            $html .= ' <!-- /.row -->
			            
			    <!-- Table row -->
			    <div class="row">
			      <div class="col-xs-12 table-responsive">
			        <table class="table table-striped">
			          <thead>
			          <tr>
			            <th>' . $this->lang->line('application_product') . '</th>
			            <th>' . $this->lang->line('application_price') . '</th>
			            <th>' . $this->lang->line('application_qty') . '</th>
			            <th>' . $this->lang->line('application_amount') . '</th>
			          </tr>
			          </thead>
			          <tbody>';
            setlocale(LC_MONETARY, "pt_BR");
            foreach ($orders_items as $k => $v) {

                $product_data = $this->model_products->getProductData(0, $v['product_id']);
                $html .= '<tr>
				            <td>' . $product_data['name'] . '</td>
				            <td>' . money_format("%i", $v['rate']) . '</td>
				            <td>' . $v['qty'] . '</td>
				            <td>' . money_format("%i", $v['amount']) . '</td>
			          	</tr>';
            }

            $html .= '</tbody>
			        </table>
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->
                
			    <div class="row">
                
			      <div class="col-xs-6 pull pull-right">
                
			        <div class="table-responsive">
			          <table class="table">
			            <tr>
			              <th style="width:50%">' . $this->lang->line('application_gross_amount') . ':</th>
			              <td>' . $order_data['gross_amount'] . '</td>
			            </tr>';

            if ($order_data['service_charge'] > 0) {
                $html .= '<tr>
				              <th>Service Charge (' . $order_data['service_charge_rate'] . '%)</th>
				              <td>' . $order_data['service_charge'] . '</td>
				            </tr>';
            }

            if ($order_data['vat_charge'] > 0) {
                $html .= '<tr>
				              <th>Vat Charge (' . $order_data['vat_charge_rate'] . '%)</th>
				              <td>' . $order_data['vat_charge'] . '</td>
				            </tr>';
            }


            $html .= ' <tr>
			              <th>' . $this->lang->line('application_discount') . ':</th>
			              <td>' . $order_data['discount'] . '</td>
			            </tr>
			            <tr>
			              <th>' . $this->lang->line('application_net_amount') . ':</th>
			              <td>' . $order_data['net_amount'] . '</td>
			            </tr>
			            <tr>
			              <th>' . $this->lang->line('application_status') . ':</th>
			              <td>' . $paid_status . '</td>
			            </tr>
			          </table>
			        </div>
			      </div>
			                  
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->';
            $html .= '
	    		<div class="row invoice-info">
				    <div class="col-sm-4 invoice-col">
	                <h4><strong>' . $this->lang->line('application_delivery_address') . '</strong></h4> 
	                	<b>' . $this->lang->line('application_name') . ':</b> ' . $order_data['customer_name'] . '<br>
	                	<b>' . $this->lang->line('application_phone') . ':</b> ' . $order_data['customer_phone'] . '<br>
	                	<b>' . $this->lang->line('application_address') . ':</b><br>
	                	&nbsp;&nbsp;' . $order_data['customer_address'] . ', ' . $order_data['customer_address_num'] . '&nbsp;&nbsp;' . $order_data['customer_address_compl'] . '<br>
	                    &nbsp;&nbsp;' . $order_data['customer_address_neigh'] . '<br>
	                    &nbsp;&nbsp;' . $order_data['customer_address_city'] . '&nbsp;&nbsp;' . $order_data['customer_address_uf'] . '<br>
	                    &nbsp;&nbsp;' . $order_data['customer_address_zip'] . '<br>
	                    &nbsp;&nbsp;' . $order_data['customer_reference'] . '<br>
	               </div> 
	               
					<div class="col-sm-4 invoice-col">
	               <h4><strong>' . $this->lang->line('application_invoice_data') . '</strong></h4>
	                	<b>' . $this->lang->line('application_client_name') . ':</b> ' . $cliente['customer_name'] . '<br>
	                	<b>' . $this->lang->line('application_phone') . ' 1:</b> ' . $cliente['phone_1'] . '<br>
	                	<b>' . $this->lang->line('application_phone') . ' 2:</b> ' . $cliente['phone_2'] . '<br>
	                	<b>' . $this->lang->line('application_cpf') . '/' . $this->lang->line('application_cnpj') . ':</b> ' . $cliente['cpf_cnpj'] . '<br>
	                	<b>' . $this->lang->line('application_address') . ':</b><br>
	                	&nbsp;&nbsp;' . $cliente['customer_address'] . ', ' . $cliente['addr_num'] . '&nbsp;&nbsp;' . $cliente['addr_compl'] . '<br>
	                    &nbsp;&nbsp;' . $cliente['addr_neigh'] . '<br>
	                    &nbsp;&nbsp;' . $cliente['addr_city'] . '&nbsp;&nbsp;' . $cliente['addr_uf'] . '<br>
	                    &nbsp;&nbsp;' . $cliente['zipcode'] . '<br>
	               </div> 
				</div>';
            $html .= '
			    <div class="row moreinfo"><div>
			  </section>
			  <!-- /.content -->
			</div>
		</body>
	</html>';

            echo $html;
        }
    }

    public function updatenfe()
    {
        ob_start();
        if (!in_array('updateOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $response = array();

        $this->form_validation->set_rules('num_nfe', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('serie_nfe', $this->lang->line('application_serie'), 'trim|required');
        // $this->form_validation->set_rules('date_emission', 'Data da Emissão', 'trim|required');
        $this->form_validation->set_rules('date_emission_nfe', $this->lang->line('application_issuance_date'), 'callback_checkDateFormat[' . $this->postClean('nfe_data_pago') . ']');

        $this->form_validation->set_rules('chave_nfe', $this->lang->line('application_access_key'), 'trim|required');
        //	$this->form_validation->set_rules('data_coleta_nfe',$this->lang->line('application_gather_date'),'callback_checkOnlyDateFormatToday', array('checkOnlyDateFormatToday' => 'Data inválida'));

        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {

            $dataOrder = $this->model_orders->verifyOrderOfStore($this->postClean('id_pedido'));
            if (!$dataOrder) {
                $response['success'] = false;
                $response['messages']['id_pedido'] = "<p class='text-danger'>Não pertence a loja.</p>";
                $semerro = false;
            }

            $id_nfe = $this->postClean('id_nfe');
            $data = array(
                'order_id' => $this->postClean('id_pedido'),
                'nfe_num' => $this->postClean('num_nfe'),
                'nfe_serie' => $this->postClean('serie_nfe'),
                'chave' => trim(onlyNumbers($this->postClean('chave_nfe'))),
                'date_emission' => $this->postClean('date_emission_nfe'),
                'company_id' => $this->postClean('company_id'),
                'nfe_value' => $dataOrder["net_amount"],
                'link_nfe' => $this->postClean("consultation_link_nfe")
            );
            if (!(trim($this->postClean('id_nfe')) == '')) {
                $data['id'] = $this->postClean('id_nfe');
            }
            //33200230120829000199012222211222221364117781
            //	$chave = '3320023012082900019901'.$data['nfe_serie'].$data['nfe_num'].'1'.'36411778';
            //		$data['chave'] = $chave.$this->getDigitosChaveAcessoNFe($chave);

            $semerro = true;
            if (trim($this->postClean('data_coleta_nfe')) != '') {
                if (!$this->checkOnlyDateFormatToday($this->postClean('data_coleta_nfe'))) {
                    $response['success'] = false;
                    $response['messages']['data_coleta_nfe'] = '<p class="text-danger">Data inválida ou menor do que hoje!</p>';
                    $semerro = false;
                }
            }
            $chave_acesso = $this->ordersmarketplace->checkKeyNFe($data['chave'], $data['nfe_serie'], $data['nfe_num'], $data['date_emission'], $dataOrder['store_id'], $this->postClean('id_pedido'));
            if (!$chave_acesso[0]) {
                $response['success'] = false;
                $response['messages']['chave_nfe'] = "<p class='text-danger'>" . $chave_acesso[1] . "</p>";
                $semerro = false;
            }

            if ($semerro) {
                $update = $this->model_orders->replaceNfe($data);

                if ($update == true) {
                    try {
                        $resp = $this->model_orders->updateDataColeta($data['order_id'], $this->postClean('data_coleta_nfe'));
                        // $resp = $this->model_orders->updatePaidStatus($data['order_id'],'50'); // Era 50 antes mas agota é 52 para contratar a nota fiscal antes
                        if ($dataOrder['paid_status'] == 3) {
                            $resp = $this->model_orders->updatePaidStatus($data['order_id'], '52');
                        }
                        $response['success'] = true;
                        $response['messages'] = $this->lang->line('messages_successfully_updated');
                    } catch (Exception $e) {
                        $response['success'] = false;
                        $response['messages'] = $e->getMessage();
                    }
                } else {
                    $response['success'] = false;
                    $response['messages'] = $this->lang->line('messages_error_updating_invoice_data');
                }
            }
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        $this->log_data(__CLASS__, __FUNCTION__, 'form_data='.json_encode($this->postClean(NULL,TRUE)), "I");
        ob_clean();
        echo json_encode($response);
    }

    // Check date format, if input date is valid return TRUE else returned FALSE.
    public function checkDateFormat($date, $startDate = null)
    {
        if ($this->isValidDateTimeString($date, 'd/m/Y H:i:s')) {
            if (is_null($startDate)) {  // se não tem data de início, a data está correta
                return true;
            } else { // transforma a data no formato americano
                $dateNfe = DateTime::createFromFormat('d/m/Y H:i:s', $date);
                if ($dateNfe == false) {
                    $dateNfe = DateTime::createFromFormat('d/m/Y H:i:s', $date . ' 00:00:00');
                }
                if ($dateNfe->format('Y-m-d H:i:s') < $startDate) {  // verifica se é menor que
                    $startDate_f = DateTime::createFromFormat('Y-m-d H:i:s', $startDate);
                    $this->form_validation->set_message('checkDateFormat', 'Data da Nota fiscal não pode ser menor que a data de pagamento do pedido :' . $startDate_f->format('d/m/Y H:i:s'));
                    return false;
                } else {
                    return true;
                }
            }
        } else { // Data inválida
            $this->form_validation->set_message('checkDateFormat', 'Data inválida');
            return false;
        }
    }

    public function checkOnlyDateFormat($date)
    {

        return  $this->isValidDateTimeString($date, 'd/m/Y');
    }

    public function YYYYMMDD($data)
    {
        return substr($data, 6, 4) . substr($data, 3, 2) . substr($data, 0, 2);
    }

    public function checkOnlyDateFormatToday($date)
    {
        if ($this->isValidDateTimeString($date, 'd/m/Y')) {
            $today = date("d/m/Y");
            if ($this->YYYYMMDD($date) >= $this->YYYYMMDD($today)) {
                return true;
            }
        }
        return false;
    }

    public function getDigitosChaveAcessoNFe($chave)
    {

        $chave = substr($chave, 0, 43);
        $peso = array(2, 3, 4, 5, 6, 7, 8, 9);
        $contaPeso = 0;
        $somaPonderacao = 0;
        for ($i = strlen($chave) - 1; $i >= 0; $i--) {
            $numero = substr($chave, $i, 1);
            $ponderacao = (int) $numero * (int) $peso[$contaPeso];
            $somaPonderacao = $somaPonderacao + $ponderacao;
            $contaPeso++;
            if ($contaPeso > 7) {
                $contaPeso = 0;
            }
        }
        $resto = ($somaPonderacao % 11);
        $digito = 0;
        if (($resto == 0) || ($resto == 1)) {
            $digito = 0;
        } else {
            $digito = 11 - $resto;
        }
        return $digito;
    }

    public function verificaChave($data)
    {
        if (!array_key_exists('chave', $data)) {
            return array('ok' => false, 'mensagem' => 'Sem chave');
        }

        $chave = $data['chave'];
        $uf = array(11, 12, 13, 14, 15, 16, 17, 21, 22, 23, 24, 25, 26, 27, 28, 29, 31, 32, 33, 35, 41, 42, 43, 50, 51, 52, 53);
        $tipo_emissao = array(1, 2, 3, 4, 5, 6, 7);
        if (!in_array((int) substr($chave, 0, 2), $uf)) {
            return array('ok' => false, 'mensagem' => 'Chave com código do estado emitente inválido');
        }
        if (substr($data['date_emission'], 8, 2) != substr($chave, 2, 2)) {
            return array('ok' => false, 'mensagem' => 'Ano da chave está diferente do ano na data de emissão');
        }
        if (substr($data['date_emission'], 3, 2) != substr($chave, 4, 2)) {
            return array('ok' => false, 'mensagem' => 'Mês da chave está diferente do mês na data de emissão');
        }
        /*
         $store = $this->model_stores->getStoresData($data['company_id']);

         if ($store['CNPJ'] !=substr($chave,6,14) ) {
         return array('ok' => false, 'mensagem' => 'CNPJ da chave está diferente do CNPJ da loja');
         }
         */
        if (substr($chave, 20, 2) != '55') {
            return array('ok' => false, 'mensagem' => 'Modelo da NF-e inválido.');
        }

        if (((int) $data['nfe_serie']) != ((int) substr($chave, 22, 3))) {
            return array('ok' => false, 'mensagem' => 'Número de série da chave está diferente do qual foi informado.');
        }
        if (((int) $data['nfe_num']) != ((int) substr($chave, 25, 9))) {
            return array('ok' => false, 'mensagem' => 'Número da nota fiscal da chave está diferente do qual foi informado.');
        }
        if (!in_array((int) substr($chave, 34, 1), $tipo_emissao)) {
            return array('ok' => false, 'mensagem' => 'Chave com tipo de emissão inválido');
        }
        $digitoChave = substr($chave, 43, 1);
        if ($digitoChave == $this->getDigitosChaveAcessoNFe($chave)) {
            return array('ok' => true, 'mensagem' => 'Chave correta');
        } else {
            return array('ok' => false, 'mensagem' => 'Chave com dígito verificador errado');
        }
    }

    public function loadnfe()
    {
        if (!in_array('updateOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_upload_nfes');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_null($this->postClean("validate"))) {

                $dirPathTemp = "assets/files/nfes_upload/";
                if (!is_dir($dirPathTemp)) {
                    $oldmask = umask(0);
                    mkdir($dirPathTemp, 0775);
                    umask($oldmask);
                }

                $upload_file = $this->upload_file();
                if (!$upload_file) {
                    $this->data['upload_point'] = 1;
                    $upload_file = $this->data['upload_msg'];
                } else {
                    $this->data['upload_point'] = 2;
                    $this->log_data('NFEs', 'upload', $upload_file, "I");
                }
            }
            if (!is_null($this->postClean("noerrors"))) {
                $upload_file = $this->postClean('upload_file');
                $this->data['upload_point'] = 3;
            }
            if (!is_null($this->postClean("witherrors"))) {
                $upload_file = $this->postClean('upload_file');
                $this->data['upload_point'] = 4;
            }
        } else {
            $this->data['upload_point'] = 1;
            $upload_file = $this->lang->line('messages_nofile');
        }
        $this->data['upload_file'] = trim($upload_file);
        $this->render_template('orders/loadnfe', $this->data);
    }

    /*
     * This function is invoked from another function to upload the image into the assets folder
     * and returns the image path
     */
    public function upload_file()
    {
        // assets/files/product_upload
        $config['upload_path'] = 'assets/files/nfes_upload';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'csv|txt';
        $config['max_size'] = '1000';

        // $config['max_width']  = '1024';s
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('nfe_upload')) {
            $error = $this->upload->display_errors();
            $this->data['upload_msg'] = $this->lang->line('messages_invalid_file');
            return false;
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['nfe_upload']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return ($data == true) ? $path : false;
        }
    }

    function CheckNfeLoadData($linha, &$msg, &$data)
    {

        $colunas = array("versao", "numero_pedido", "numero_nota_fiscal", "serie", "chave_acesso", "data_emissao", "hora_emissao", "data_coleta");
        $check =   array("NB",   "NBX",          "NBX",                "NBX",  "NBX",         "DBX",         "HBX",        "DBX");

        $attribs = array();
        $ok = true;
        $col = 1;

        $valAc = '';
        foreach ($linha as $key => $val) {
            $valAc .= trim($val);
        }
        if ($valAc == '') {
            $data = '';
            return $ok;  // ignoro linha em branco
        }


        if (isset($linha['numero_pedido'])) {
            if (trim($linha['numero_pedido']) != "") {
                $pedido = $this->model_orders->getOrdersData(0, trim($linha['numero_pedido']));
                if (isset($pedido)) {
                    if (($pedido['company_id']) == $this->session->userdata('usercomp')) {
                        if (($pedido['paid_status'] == '3')) {

                            $existe = $this->model_orders->getOrdersNfes(trim($linha['numero_pedido']));
                            if ($existe) {
                                $data = $existe[0];
                            }
                        } else {
                            $msg .= '(Pedido ' . trim($linha['numero_pedido']) . ' não pode mais ser atualizado com notas fiscais)';
                            $ok = false;
                        }
                    } else {
                        $msg .= '(Pedido ' . trim($linha['numero_pedido']) . ' não existe esta empresa)';
                        $ok = false;
                    }
                } else {
                    $msg .= '(Pedido ' . trim($linha['numero_pedido']) . ' inexistente)';
                    $ok = false;
                }
            } else {
                $msg .= '(Número do Pedido inexistente)';
                $ok = false;
            }
        } else {
            $msg .= '(sem a coluna obrigatória "numero_pedido")';
            $ok = false;
        }

        $i = 0;
        foreach ($colunas as $coluna) {
            if (!(array_key_exists($coluna, $linha))) {
                if (substr($check[$i], 2, 1) == 'X') {
                    $msg .= "(Falta coluna " . $coluna . " OBRIGATÓRIA)";
                    $ok = false;
                }
                $i++;
            }
        }
        foreach ($linha as $key => $val) {
            if ($col++ > 0) {
                $attrib_ok = "";
                if (in_array(trim($key), $colunas)) {
                    $nc = array_search(trim($key), $colunas);
                    if (substr($check[$nc], 0, 1) == "D") {
                        if ((substr($check[$nc], 0, 2) == "DB") && (trim($linha[$key]) == "")) {
                            if ((!$existe) || (($existe) && ($check[$nc] == "DBX"))) {
                                $msg .= "(Falta valor OBRIGATÓRIO em: " . $key . ")";
                                $ok = false;
                            }
                        }
                        if ($ok && (!$this->isValidDateTimeString($linha[$key], 'd/m/Y'))) {
                            $msg .= "(Data inválida em: " . $key . ")";
                            $ok = false;
                        }
                    }
                    if (substr($check[$nc], 0, 1) == "H") {
                        if ((substr($check[$nc], 0, 2) == "HB") && (trim($linha[$key]) == "")) {
                            if ((!$existe) || (($existe) && ($check[$nc] == "HBX"))) {
                                $msg .= "(Falta valor OBRIGATÓRIO em: " . $key . ")";
                                $ok = false;
                            }
                        }
                        if ($ok && (!$this->isValidDateTimeString($linha[$key], 'G:i:s'))) {
                            $msg .= "(Hora inválida em: " . $key . ")";
                            $ok = false;
                        }
                    }
                    if (substr($check[$nc], 0, 1) == "N") {
                        if ((substr($check[$nc], 0, 2) == "NB") && (trim($linha[$key]) == "")) {
                            if ((!$existe) || (($existe) && ($check[$nc] == "NBX"))) {
                                $msg .= "(Falta valor OBRIGATÓRIO em: " . $key . ")";
                                $ok = false;
                            }
                        }
                        if ($ok && (!$this->fmtNum($linha[$key]))) {
                            $msg .= "(Valor NÃO NUMÉRICO em: " . $key . ")";
                            $ok = false;
                        }
                    }
                    if (substr($check[$nc], 0, 1) == "A") {
                        if ((substr($check[$nc], 0, 2) == "AB") && (trim($linha[$key]) == "")) {
                            if ((!$existe) || (($existe) && ($check[$nc] == "ABX"))) {
                                $msg .= "(Falta valor OBRIGATÓRIO em: " . $key . ")";
                                $ok = false;
                            }
                        }
                    }
                } else {
                    $msg .= "(Coluna " . $key . " Invalida. Valor:" . $val . ")";
                    $ok = false;
                }
                if ($ok) {
                    if ($key == 'numero_pedido') {
                        $data['order_id'] = $linha['numero_pedido'];
                    } elseif ($key == 'numero_nota_fiscal') {
                        $data['nfe_num'] = $linha['numero_nota_fiscal'];
                    } elseif ($key == 'preco_venda') {
                        $data['price'] = $this->fmtNum($linha['preco_venda']);
                    } elseif ($key == 'serie') {
                        $data['nfe_serie'] = $linha['serie'];
                    } elseif ($key == 'chave_acesso') {
                        $data['chave'] = trim(onlyNumbers($linha['chave_acesso']));
                    } elseif ($key == 'data_emissao') {
                        $data['date_emission'] = $linha['data_emissao'] . ' ' . $linha['hora_emissao'];
                    } elseif ($key == 'hora_emissao') {
                        $data['date_emission'] = $linha['data_emissao'] . ' ' . $linha['hora_emissao'];
                    } elseif ($key == 'data_coleta') {
                        $data['data_coleta'] = $linha['data_coleta'];
                    }
                }
            }
        }
        //$this->log_data('Products','Check',"OK:".$ok.")".json_encode($linha));

        if ($ok) {
            $chave_acesso = $this->ordersmarketplace->checkKeyNFe($data['chave'], $data['nfe_serie'], $data['nfe_num'], $data['date_emission'], $pedido['store_id'], $data['order_id']);
            if ($chave_acesso[0]) {
                return $ok;
            } else {
                $msg .= "(" . $chave_acesso[1] . ")";
                return false;
            }
        }
        // return $ok;
    }

    /**
     * Check if a string is a valid date(time)
     *
     * DateTime::createFromFormat requires PHP >= 5.3
     *
     * @param string $str_dt
     * @param string $str_dateformat
     * @param string $str_timezone (If timezone is invalid, php will throw an exception)
     * @return bool
     */
    function isValidDateTimeString($str_dt, $str_dateformat, $str_timezone = 'America/Sao_Paulo')
    {
        $date = DateTime::createFromFormat($str_dateformat, $str_dt, new DateTimeZone($str_timezone));

        return $date && DateTime::getLastErrors()["warning_count"] == 0 && DateTime::getLastErrors()["error_count"] == 0;
    }

    function fmtNum($num, $padrao = "US")
    {    // Ou BR
        $temp = str_replace(",", "", $num);
        $temp = str_replace(".", "", $temp);
        if (is_numeric($temp)) {
            $num = str_replace(",", ".", $num);
            $ct = false;
            while (!$ct) {
                $temp = str_replace(".", "", $num, $cnt);
                if ($cnt < 2) {
                    $ct = true;
                } else {
                    $pos = strpos($num, ".");
                    $num = substr($num, 0, $pos) . substr($num, $pos + 1);
                    $ct = false;
                }
            }
            return $num;
        } else {
            return false;
        }
    }

    public function semFrete()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['filters'] = $this->model_reports->getFilters('orders');
        $this->data['page_title'] = $this->lang->line('application_novofrete');
        $this->session->unset_userdata('ordersfilter');
        unset($this->data['ordersfilter']);

        $this->data['stores_filter'] = $this->model_stores->getActiveStore();
        $this->render_template('orders/semfrete', $this->data);
    }

    public function fetchOrdersDataSemFrete($postdata = null)
    {
        ob_start();
        if (!in_array('viewOrder', $this->permission)) {

            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];
        //$this->log_data('Orders','postdata',print_r($ini+1,true));
        $busca = $postdata['search'];
        $whereLike = [];
        unset($this->data['ordersfilter']);
        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            $whereLike = array('o.customer_name' => $busca['value'], 'o.id' => $busca['value'], 'o.ship_company_preview' => $busca['value'], 'o.ship_service_preview' => $busca['value']);
            $this->data['ordersfilter'] = true;
        }

        if (isset($postdata['lojas']) && is_array($postdata['lojas'])) {
            $this->data['where_in']['s.id'] = $postdata['lojas'];
            $this->data['ordersfilter'] = true;
        }

        $result = array();

        if (isset($postdata['order'])) {
            $order_dir = $postdata['order'][0]['dir'];
            $order_by = 'id';
            switch ($postdata['order'][0]['column']) {
                case 0:
                    $order_by = 'id';
                    break;
                case 1:
                    $order_by = 'store';
                    break;
                case 2:
                    $order_by = 'customer_name';
                    break;
                case 3:
                    $order_by = 'customer_phone';
                    break;
                case 4:
                    $order_by = 'date_time';
                    break;
                case 5:
                    $order_by = 'ship_company_preview';
                    break;
                case 6:
                    $order_by = 'ship_company_preview';
                    break;
                case 7:
                    $order_by = 'gross_amount';
                    break;
                case 8:
                    $order_by = 'total_ship';
                    break;
            }
        }
        $data = $this->model_orders->getOrdersSemFreteByLike($whereLike, [], $order_by, $order_dir, $length, $ini);
        // dd( $data,$this->db->last_query());

        foreach ($data as $key => $value) {

            $date = date('d-m-Y', strtotime($value['date_time']));
            $time = date('h:i a', strtotime($value['date_time']));

            //$date_time = $date . ' ' . $time;
            $date_time = $date;

            // button
            $buttons = '';
            // if (in_array('updateOrder', $this->permission)) {
            if (in_array('createTrackingOrder', $this->permission)) {
                $buttons .= ' <a href="' . base_url('orders/novofrete/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-truck"></i></a>';
            }
            if (in_array('viewOrder', $this->permission)) {
                $buttons .= '<a target="__blank" href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';
            }

            // $buttons .= ' <a class="btn btn-default" href="export/createxls"><i class="fa fa-file-excel-o"></i></a>';
            $nd = $this->datedif($value['date_time']);

            $linkid = '<a href="' . base_url() . 'orders/update/' . $value['id'] . '" target="_blank">' . $value['id'] . '</a>';
            $result[$key] = array(
                $linkid,
                $value['store'],
                $value['customer_name'],
                $value['customer_phone'],
                $date_time,
                $value['ship_company_preview'],
                $value['ship_service_preview'],
                $this->formatprice($value['gross_amount']),
                $this->formatprice($value['total_ship']),
                $buttons
            );
        } // /foreach

        unset($this->data['ordersfilter']);
        unset($this->data['where_in']);
        $output = array(
            "draw" => $draw,
            "recordsTotal" => count($this->model_orders->getOrdersSemFreteByLike()),
            "recordsFiltered" => count($this->model_orders->getOrdersSemFreteByLike($whereLike)),
            "data" => $result,
        );
        ob_clean();
        echo json_encode($output);
    }

    public function filterSemFrete()
    {
        if (!in_array('viewOrder', $this->permission)) {

            redirect('dashboard', 'refresh');
        }

        $this->data['filters'] = $this->model_reports->getFilters('orders');
        $this->data['page_title'] = $this->lang->line('application_manage_orders_filtered');
        $ordersfilter = "";
        if (!is_null($this->postClean('do_filter'))) {
            if ((!is_null($this->postClean('id'))) && ($this->postClean('id_op') != "0")) {
                $ordersfilter .= " AND id " . $this->postClean('id_op') . " " . $this->postClean('id');
            }
            if ((!is_null($this->postClean('bill_no')))  && ($this->postClean('bill_no_op') != "0")) {
                $ordersfilter .= " AND bill_no " . $this->postClean('bill_no_op') . " '" . $this->postClean('bill_no') . "'";
            }
            if ((!is_null($this->postClean('origin')))  && ($this->postClean('origin_op') != "0")) {
                $ordersfilter .= " AND origin " . $this->postClean('origin_op') . " '" . $this->postClean('origin') . "'";
            }
        }
        $this->session->set_userdata(array('ordersfilter' => $ordersfilter));

        $this->data['stores_filter'] = $this->model_stores->getActiveStore();
        $this->render_template('orders/semfrete', $this->data);
    }

    public function novofrete($id)
    {
        // if (!in_array('updateOrder', $this->permission)) {
        if (!in_array('createTrackingOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!$id) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_new_freight');

        //$this->form_validation->set_rules('paid_status', $this->lang->line('application_paid_status'), 'trim|required');


        if ($this->form_validation->run() == TRUE) {
            // Não tem update no momento
            //$update = $this->model_orders->update($id);

            //if($update == true) {
            //	$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            //	redirect('orders/novofrete/'.$id, 'refresh');
            //}
            //else {
            //$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
            //	redirect('orders/novofrete/'.$id, 'refresh');
            //}
            redirect('orders/novofrete/' . $id, 'refresh');
        } else {
            // false case
            $company = $this->model_company->getCompanyData(1);
            $this->data['company_data'] = $company;
            $this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
            $this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;

            $result = array();
            $orders_data = $this->model_orders->getOrdersData(0, $id);

            $result['order'] = $orders_data;
            $result['order']['loja'] = $this->model_stores->getStoresData($orders_data['store_id']);
            $result['order']['empresa'] = $this->model_company->getCompanyData($orders_data['company_id']);

            $orders_item = $this->model_orders->getOrdersItemData($orders_data['id']);
            $i = 0;
            $qtds = 0;
            foreach ($orders_item as $k => $v) {
                $i++;
                $result['order_item'][] = $v;
                $qtds = $qtds + $v['qty'];
            }
            $result['order']['num_items'] = $i;
            $result['order']['sum_qty'] = $qtds;

            $freights = $this->model_orders->getItemsFreights($id);
            $i = 0;
            $val = 0;
            foreach ($freights as $k => $v) {
                $i++;
                $result['freights'][] = $v;
                $val = $val + $v['ship_value'];
            }
            $result['order']['freight'] = $val;

            if (count($freights) > 0) {
                $ocorrencias = $this->model_frete_ocorrencias->getFreteOcorrenciasDataByFreightsId($freights[0]['id']);
                foreach ($ocorrencias as $k => $v) {
                    $result['frete_ocorrencias'][] = $v;
                }
            }

            $nfes = $this->model_orders->getOrdersNfes($id);
            $i = 0;
            foreach ($nfes as $k => $v) {
                $i++;
                $result['nfes'][] = $v;
            }

            $parcs = $this->model_orders->getOrdersParcels($id);
            $i = 0;
            foreach ($parcs as $k => $v) {
                $i++;
                $result['pagtos'][] = $v;
            }

            $cliente = $this->model_clients->getClientsData($orders_data['customer_id']);
            $cliente['cpf_cnpj'] = $this->formatCnpjCpf($cliente['cpf_cnpj']);
            $result['order']['cliente'] = $cliente;

            $this->data['order_data'] = $result;

            //$this->data['products'] = $this->model_products->getActiveProductData();
            $this->log_data('Orders', 'edit before', json_encode($orders_data), "I");

            $status_str = $this->lang->line('application_order_' . $orders_data['paid_status']);
            if (($orders_data['paid_status'] == 51) || ($orders_data['paid_status'] == 53)) {
                $hasLabel = $this->model_freights->getFreightsHasLabel($orders_data['id']);
                if (!empty($hasLabel)) {
                    $status_str = $this->lang->line('application_order_4');
                }
            }
            $this->data['status_str'] = $status_str;

            if ($this->ms_freight_tables->use_ms_shipping) {
                try {
                    $this->ms_freight_tables->setStore($orders_data['store_id']);
                    $shipping_companies = $this->ms_freight_tables->getShippingCompanies();
                } catch (Exception $exception) {
                    $shipping_companies = array();
                }
            } else {
                $shipping_companies = $this->model_shipping_company->getShippingCompanyActiveByStoreAndSellerCenter($orders_data['store_id']);
            }

            $this->data['ship_companies'] = array();
            foreach ($shipping_companies as $shipping_company) {
                $shipping_company_id    = $this->ms_freight_tables->use_ms_shipping ? $shipping_company->id : $shipping_company['id'];
                $shipping_company_name  = $this->ms_freight_tables->use_ms_shipping ? $shipping_company->name : $shipping_company['name'];

                $this->data['ship_companies'][] = array(
                    'id'    => $shipping_company_id,
                    'name'  => $shipping_company_name
                );
            }

            $this->data['pedido_cancelado'] = false;
            if (in_array((int)$result['order']['paid_status'],  array(95, 96, 97, 98, 99))) {
                $this->data['pedido_cancelado'] = $this->model_orders->getPedidosCanceladosByOrderId($result['order']['id']);
            }

            $this->data['data_prometida'] = (is_null($orders_data['data_pago'])) || (is_null($orders_data['ship_time_preview'])) ? '' : date('d/m/Y', strtotime($this->somar_dias_uteis(date('Y-m-d', strtotime($orders_data['data_pago'])),$orders_data['ship_time_preview'],'', TRUE)));

            $this->data['cancel_reasons'] = $this->model_attributes->getAttributeValuesByName('cancel_reasons');
            $this->data['cancel_penalty_to'] = $this->model_attributes->getAttributeValuesByName('cancel_penalty_to');

            $this->render_template('orders/novofrete', $this->data);
        }
    }

    public function consultafrete()
    {
        $orderId = (int)$this->input->get('id');
        if ($orderId == '') {
            return false;
        }

        $CNPJ = '30120829000199'; // CNPJ fixo do ConectaLa
        // Pego o Token pro frete Rápido
        $sql = "SELECT * FROM settings WHERE name = ?";
        $query = $this->db->query($sql, array('token_frete_rapido_master'));
        $row = $query->row_array();
        if ($row) {
            $token_fr = $row['value'];
        } else {
            $retorno = array();
            $retorno['erro'] = true;
            $this->session->set_flashdata('error', 'Falta o cadastro do parametro token_frete_rapido_master');
            $json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
            $json_data = stripslashes($json_data);

            echo $json_data;
            return;
        }

        $orders_data = $this->model_orders->getOrdersData(0, $orderId);

        if (is_null($orders_data['store_id'])) {
            $stores = $this->model_stores->getCompanyStores($orders_data['company_id']);
            $store = $stores[0];
        } else {
            $store = $this->model_stores->getStoresData($orders_data['store_id']);
        }
        // var_dump($store);

        $order_client = $this->model_clients->getClientsData($orders_data['customer_id']);

        $orders_item = $this->model_orders->getOrdersItemData($orders_data['id']);

        foreach ($orders_item as $item) {

            // $sql = "SELECT * FROM bling_ult_envio WHERE int_to = ? and prd_id= ?";
            $sql = "SELECT * FROM prd_to_integration WHERE int_to = ? and prd_id= ?";
            $query = $this->db->query($sql, array($orders_data['origin'], $item['product_id']));
            $row_ult = $query->row_array();
            if (empty($row_ult)) {
                $retorno = array();
                $retorno['erro'] = true;
                $this->session->set_flashdata('error', 'O produto ' . $item['product_id'] . ' não foi enviado para o marketplace ' . $orders_data['origin']);
                $json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
                $json_data = stripslashes($json_data);

                echo $json_data;
                return;
            }
            $sku = $row_ult['skumkt'];
            if ($sku == "") {
                $sku = $row_ult['skubling'];
            }

            $sql = "SELECT * FROM products WHERE id= ?";
            $query = $this->db->query($sql, $item['product_id']);
            $prd = $query->row_array();

            $cat_id = json_decode($prd['category_id']);
            $sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
					 WHERE id =" . intval($cat_id[0]) . ")";
            $cmd = $this->db->query($sql);
            $lido = $cmd->row_array();
            if (empty($lido)) {
                $tipo_volume_codigo = 999;
            } else {
                $tipo_volume_codigo = intval($lido['codigo']);
            }

            $skus_key[] = $sku;
            $vl = array(
                "tipo" => $tipo_volume_codigo,
                "sku" => $sku,
                "quantidade" => (int) $item['qty'],
                "altura" => (float) $prd['altura'] / 100,
                "largura" => (float) $prd['largura'] / 100,
                "comprimento" => (float) $prd['profundidade'] / 100,
                "peso" => (float) $prd['peso_bruto'],
                "valor" => (float) $item['amount'],
                "volumes_produto" => 1,
                "consolidar" => false,
                "sobreposto" => false,
                "tombar" => false
            );
            $fr['volumes'][] = $vl;
        }

        $fr["destinatario"] = array(
            "tipo_pessoa" => 1,
            "endereco" => array(
                "cep" => preg_replace('/\D/', '', $orders_data['customer_address_zip'])
            )
        );

        $fr["remetente"] = array(
            "cnpj" => $CNPJ
        );

        $fr["expedidor"] = array(
            "cnpj" => preg_replace('/\D/', '', $store['CNPJ']),
            "endereco" => array('cep' => $store['zipcode'])
        );
        $fr["codigo_plataforma"] = "nyHUB56ml";
        // $fr["token"] = "5d1c7889ff8789959cb39eb151a3698e";  // Rick pegar o Token do Parceiro., talvez colcoar na bling_ult_envio
        $fr["token"] = $token_fr;
        $fr["retornar_consolidacao"] = true;
        //var_dump($fr);
        $json_data = json_encode($fr, JSON_UNESCAPED_UNICODE);
        $json_data = stripslashes($json_data);

        $url = "https://freterapido.com/api/external/embarcador/v1/quote-simulator";

        $data = $this->get_web_page($url, $json_data);

        if (!($data['httpcode'] == "200")) {
            //echo 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data;
            $retorno = array();
            $retorno['erro'] = true;
            $this->session->set_flashdata('error', 'ERRO - httpcode: ' . $data['httpcode'] . ' RESPOSTA FR: ' . $data['content'] . ' DADOS ENVIADOS:' . $json_data);

            $json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
            $json_data = stripslashes($json_data);

            echo $json_data;
            return;
        }

        $retorno_fr = $data['content'];

        $data = json_decode($data['content'], true);
        $transp = $data['transportadoras'];
        if (count($transp) == 0) {
            // Não voltou transportadora.
            //echo 'SEM TRANSPORTADORA: DADOS ENVIADOS:'.print_r($json_data,true).' RECEBIDOS '.print_r($retorno_fr,true);
            $retorno = array();
            $retorno['erro'] = true;
            $this->session->set_flashdata('error', 'SEM TRANSPORTADORA: DADOS ENVIADOS:' . print_r($json_data, true) . ' RECEBIDOS ' . print_r($retorno_fr, true));
            $json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
            $json_data = stripslashes($json_data);

            echo $json_data;
            return;
        }
        // Adiciono a taxa de frete ao valor retornado
        $sql = 'SELECT av.value FROM attribute_value av, attributes a WHERE a.name ="frete_taxa" and a.id = av.attribute_parent_id';
        $query = $this->db->query($sql);
        $row_taxa = $query->row_array();
        // Não faz sentido aumentar com a taxa $transp[0]['preco_frete'] += (float) $row_taxa['value'];

        $retorno = array();
        $retorno['erro'] = false;
        $retorno['preco_frete'] = $transp[0]['preco_frete'];
        $retorno['prazo_entrega'] = $transp[0]['prazo_entrega'];
        $retorno['transportadora'] = $transp[0]['nome'];

        $json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
        $json_data = stripslashes($json_data);

        echo $json_data;
        //var_dump ($row_ult);

        sort($skus_key);
        $quotes = array();
        $quotes['marketplace'] = $orders_data['origin'];
        $quotes['zip'] = preg_replace('/\D/', '', $orders_data['customer_address_zip']);
        $quotes['sku'] =  json_encode($skus_key);
        $quotes['cost'] = $transp[0]['preco_frete'];
        $quotes['id'] = $data['token_oferta'];
        $quotes['oferta'] = $transp[0]['oferta'];
        $quotes['validade'] = $transp[0]['validade'];
        $quotes['retorno'] = $retorno_fr;
        $quotes['frete_taxa'] = 0;  // Por enquanto, não tem taxa. Será calculado quando contratar o frete $row_taxa['value'];
        $this->db->replace('quotes_ship', $quotes);
    }

    public function mudastatusfrete()
    {
        if (!in_array('updateOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->form_validation->set_rules('id_pedido', 'Id do Pedido', 'trim|required');
        if ($this->form_validation->run() == TRUE) {

            $order_id = $this->postClean('id_pedido');
            $novopreco = $this->postClean('newpriceFR');
            $precovelho = $this->postClean('oldpriceFR');

            $this->model_orders->updateNovoFrete($order_id, 54, $novopreco); // volta para entrar no ciclo normal de contratação de frete
            $this->session->set_flashdata('success', 'Frete marcado para contratação.');
            redirect('orders/semfrete', 'refresh');
        } else {
            $order_id = $this->postClean('id_pedido') ?? 0;
            $this->session->set_flashdata('error', 'Não era para acontecer isto');
            redirect('orders/novofrete/' . $order_id, 'refresh');
        }
        // $this->render_template('orders/semfrete', 'refresh');
    }

    function get_web_page($url, $post_data)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "conectala", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_POST        => true,
            CURLOPT_POSTFIELDS    => $post_data,
            CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
        );
        $ch      = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err     = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $header  = curl_getinfo($ch);
        curl_close($ch);
        $header['httpcode']   = $httpcode;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }

    public function deliverySentToMarketplace()
    {
        if (!in_array('doIntegration', $this->permission) || ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            redirect('dashboard', 'refresh');
        }
        //$this->log_data('Products','index','-');

        $this->render_template('orders/freteentregue', $this->data);
    }

    public function freteEntregueSelect()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!is_null($this->postClean('id'))) {
            $this->log_data('Products', 'SendMKT', json_encode($_POST), "I");
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    $this->model_orders->updatePaidStatus($id, 6); // Ordem entregue e marcada no marketplace
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $id) {
                    $this->model_orders->updatePaidStatus->updatePaidStatus($id, 60);
                    //this->model_integrations->unsetProductToMkt($mkt,$id,$cpy,$prd);
                }
            }
        }

        redirect('orders/deliverySentToMarketplace', 'refresh');
        //$this->render_template('orders/freteentregue', $this->data);
    }

    public function freteEntregueData()
    {
        ob_start();
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        // $this->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 2 letras
                $procura = " AND ( o.origin like '%" . $busca['value'] . "%' OR o.bill_no like '%" . $busca['value'] . "%'";
                $procura .= "  OR s.name  like '%" . $busca['value'] . "%' OR o.customer_name like '%" . $busca['value'] . "%'";
                $procura .= "  OR o.id like '%" . $busca['value'] . "%' ) ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'origin', 'bill_no', 'loja', 'customer_name', 'id', '', 'gross_amount');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $procura .= " AND o.order_manually = 1 ";

        $result = array();

        // Pego as ordens com status = 60, isto é marcadas como entregues mas que precisa avisar ao marketplace
        $data = $this->model_orders->getOrdensFreteEntregueData($ini, $procura, $sOrder);

        $i = 0;
        $filtered = $this->model_orders->getOrdensFreteEntregueCount($procura);

        foreach ($data as $key => $value) {
            $i++;

            $linkid = '<a href="' . base_url() . 'orders/update/' . $value['id'] . '" target="_blank">' . $value['id'] . '</a>';
            $buttons = '<a target="__blank" href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';

            $result[$key] = array(
                $value['id'],
                $value['origin'],
                $value['numero_marketplace'],
                $value['bill_no'],
                $value['loja'],
                $value['customer_name'],
                $linkid,
                $this->model_orders->countOrderItem($value['id']),
                $this->formatprice($value['gross_amount']),
                $buttons
            );
        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_orders->getOrdensFreteEntregueCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

    public function cancelarPedido()
    {
        ob_start();
        if (!in_array('deleteOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $response = array();

        $this->form_validation->set_rules('motivo_cancelamento', $this->lang->line('application_cancel_reason'), 'trim|required');
        $this->form_validation->set_rules('penalty_to', $this->lang->line('application_penalty_to'), 'trim|required');

        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {

            // Descobre se veio da tela de cancelar pedidos e busca o ID na string do cancelamento para salvar o ID na tabela caso venha
            $penaltyPostClean = $this->postClean('penalty_to', TRUE);
            $resultPenaltyPostClean = explode("|sep|", $penaltyPostClean);
            $penaltyPostClean = $resultPenaltyPostClean[0];

            $timeCancel = null;
            $settingTimeCancel = $this->model_settings->getSettingDatabyName('time_not_return_stock_cancel_order');
            if ($settingTimeCancel && $settingTimeCancel['status'] == 1)
                $timeCancel = (int)$settingTimeCancel['value'];

            $order = $this->model_orders->getOrdersData(0, $this->postClean('id_cancelamento'));

            $penaltyExist = false;
            $cancelPenaltiesTo = $this->model_attributes->getAttributeValuesByName('cancel_penalty_to');
            foreach ($cancelPenaltiesTo as $cancelPenaltyTo) {
                if ($penaltyPostClean == $cancelPenaltyTo['value']) {
                    $penaltyExist = true;
                    break;
                }
            }
            if (!$penaltyExist || (!in_array('admDashboard', $this->permission) && !in_array('deleteOrder', $this->permission) && $penaltyPostClean != '1-Seller')) {
                $response['success'] = false;
                $response['messages'] = 'Penalidade incorreta. Informe uma penalidade válida.';
                ob_clean();
                echo json_encode($response);
                die;
            }

            // devolvo o estoque do pedido
            if ($timeCancel === null || ($order['paid_status'] == 1 && time() < strtotime("+{$timeCancel} minutes", strtotime($order['date_time'])))) {
                $itens = $this->model_orders->getOrdersItemData($this->postClean('id_cancelamento'));
                foreach ($itens as $item)
                    $this->model_products->adicionaEstoque($item['product_id'], $item['qty'], $item['variant']);
            }

            // Seta os valores para null antes de verificar se existe ou não o ID do atributo a ser tratado
            $idAttributeValuePenaltyTo = null;
            $AttributeValuePenaltyToCommissionChargesAttributeValue = null;

            if(array_key_exists("1",$resultPenaltyPostClean)){
                $idAttributeValuePenaltyTo = $resultPenaltyPostClean[1];

                // Busca se o atributo da penalidade cobra ou não comissão
                $CommissionChargesAttribute = $this->model_attributes->getAttributeValueCommissionChargesById($idAttributeValuePenaltyTo);
                if($CommissionChargesAttribute){
                    $AttributeValuePenaltyToCommissionChargesAttributeValue = $CommissionChargesAttribute['commission_charges'];
                }

            }

            $data = array(
                'order_id' => $this->postClean('id_cancelamento', TRUE),
                'reason' => $this->postClean('motivo_cancelamento', TRUE),
                'date_update' => date("Y-m-d H:i:s"),
                'status' => '1',
                'penalty_to' => $resultPenaltyPostClean[0],
                'observation' => $this->postClean('observation', TRUE),
                'user_id' => $this->session->userdata('id'),
                'manual_cancel' => 1,
                'attribute_value_id' => $idAttributeValuePenaltyTo,
                'commission_charges_attribute_value' => $AttributeValuePenaltyToCommissionChargesAttributeValue
            );
            $this->model_orders->insertPedidosCancelados($data);

			$data_order = array (
//                'paid_status'	=> $order['paid_status'] == 6 ? ($this->postClean('penalty_to',TRUE) == '1-Seller' ? 95 : 97) : 99 ,
                'paid_status'	=> 99,
				'date_cancel'	=> date("Y-m-d H:i:s")
			);
			$this->model_orders->updateByOrigin($this->postClean('id_cancelamento',TRUE), $data_order);
            // $resp = $this->model_orders->updatePaidStatus($this->postClean('id_cancelamento',TRUE), $order['paid_status'] == 6 ? ($this->postClean('penalty_to') == '1-Seller' ? 95 : 97) : 99);
            $response['success'] = true;
            $response['messages'] = $this->lang->line('messages_successfully_canceled');
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_canceled'));
            // redirect('orders/semfrete', 'refresh');
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        //header('Content-type:application/json;charset=utf-8');
        ob_clean();
        echo json_encode($response);
    }

    public function postItem()
    {
        ob_start();
        if (!in_array('updateTrackingOrder', $this->permission)) {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_error_occurred');
            ob_clean();
            echo json_encode([]);
            die;
        }
        $response = array();
        $this->form_validation->set_rules('post_date', $this->lang->line('application_post_date'), 'trim|required');
        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {
            // devolvo o estoque do pedido
            $date = $this->postClean('post_date');
            $data_ocorrencia = DateTime::createFromFormat('d/m/Y H:i:s', $date)->format('Y-m-d H:i:s');
            $id = $this->postClean('id_order_post');
            $order = $this->model_orders->getOrdersData(0, $id);
            $nameStatus = 'Em transporte (Manual)';
        
            $order['paid_status'] = $order['in_resend_active'] ? 5 : 55;  // Enviado. Precisa acertar no marketplace
            $order['data_envio'] = $data_ocorrencia;
            $order['last_occurrence'] = $nameStatus;

            if(empty($order['data_pago'])){
                $date_compara = strtotime($order['data_time']);
            }else{
                $date_compara = strtotime($order['data_pago']);
            }

            if(strtotime($data_ocorrencia) < $date_compara){
                $response['success'] = false;
                $response['messages'] = $this->lang->line('application_shipping_date');
                echo json_encode($response);
                die;
            }
            
            $freights = $this->model_orders->getItemsFreights($id);
            foreach ($freights as $freight) {
                $frete_ocorrencia = array(
                    'freights_id' => $freight['id'],
                    'codigo' => 200,
                    'nome' => $nameStatus,
                    'data_ocorrencia' => $data_ocorrencia,
                    'data_atualizacao' => $data_ocorrencia,
                    'avisado_marketplace' => 0
                );
                $this->model_frete_ocorrencias->create($frete_ocorrencia);
            }

            $this->model_orders->updateByOrigin($id, $order);

            $response['success'] = true;
            $response['messages'] = $this->lang->line('messages_successfully_updated');
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            // redirect('orders/semfrete', 'refresh');
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        ob_clean();
        echo json_encode($response);
    }

    public function deliveryItem()
    {
        ob_start();
        if (!in_array('updateTrackingOrder', $this->permission)) {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_error_occurred');
            ob_clean();
            echo json_encode([]);
            die;
        }

        $response = array();
        $this->form_validation->set_rules('delivery_date', $this->lang->line('application_delivered_date'), 'trim|required');
        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {
            // devolvo o estoque do pedido
            $date = $this->postClean('delivery_date');
            $data_ocorrencia = DateTime::createFromFormat('d/m/Y H:i:s', $date)->format('Y-m-d H:i:s');
            $id = $this->postClean('id_order_delivery');
            $order = $this->model_orders->getOrdersData(0, $id);
            $nameStatus = 'Entregue (Manual)';
            

            $order['paid_status'] = 60;  // Enviado. Precisa acertar no marketplace
            $order['data_entrega'] = $data_ocorrencia;
            $order['last_occurrence'] = $nameStatus;

            if(empty($order['data_envio'])){
                $date_compara = strtotime($order['data_time']);
            }else{
                $date_compara = strtotime($order['data_envio']);
            }

            if(strtotime($data_ocorrencia) < $date_compara){
                $response['success'] = false;
                $response['messages'] = $this->lang->line('application_delivery_date');
                echo json_encode($response);
                die;   
            }

            $freights = $this->model_orders->getItemsFreights($id);
            foreach ($freights as $freight) {
                $frete_ocorrencia = array(
                    'freights_id' => $freight['id'],
                    'codigo' => 201,
                    'nome' => $nameStatus,
                    'data_ocorrencia' => $data_ocorrencia,
                    'data_atualizacao' => $data_ocorrencia,
                    'avisado_marketplace' => 0
                );
                $this->model_frete_ocorrencias->create($frete_ocorrencia);
                $freight['date_delivered'] = $data_ocorrencia;
                $this->model_freights->replace($freight);
            }

            $this->model_orders->updateByOrigin($id, $order);

            $response['success'] = true;
            $response['messages'] = $this->lang->line('messages_successfully_updated');
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            // redirect('orders/semfrete', 'refresh');
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        ob_clean();
        echo json_encode($response);
    }

    public function cancelSentoToMarketplace()
    {
        if (!in_array('doIntegration', $this->permission) || ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            redirect('dashboard', 'refresh');
        }
        //$this->log_data('Products','index','-');

        $this->render_template('orders/cancelamkt', $this->data);
    }

    public function cancelaMktSelect()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!is_null($this->postClean('id'))) {
            $this->log_data('Orders', 'SendMKT', json_encode($_POST), "I");
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    //$this->model_orders->updatePaidStatus($id, 97); // Ordem cancelada e marcada no marketplace/frete rápido
                    $this->ordersmarketplace->cancelOrder($id, false, false);
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $id) {
                    // $this->model_orders->updatePaidStatus->updatePaidStatus($id,60);
                }
            }
        }

        redirect('orders/cancelSentoToMarketplace', 'refresh');
        //$this->render_template('orders/freteentregue', $this->data);
    }

    public function cancelaMktData()
    {
        ob_start();
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        // $this->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 2 letras
                $procura = " AND ( o.origin like '%" . $busca['value'] . "%' OR o.bill_no like '%" . $busca['value'] . "%'";
                $procura .= "  OR s.name  like '%" . $busca['value'] . "%' OR o.customer_name like '%" . $busca['value'] . "%'";
                $procura .= "  OR o.id like '%" . $busca['value'] . "%' ) ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'origin', 'bill_no', 'loja', 'customer_name', 'id', '', 'gross_amount');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $procura .= " AND o.order_manually = 1 ";

        $result = array();

        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_orders->getOrdensCancelaMktData($ini, $procura, $sOrder);

        $i = 0;
        $filtered = $this->model_orders->getOrdensCancelaMktCount($procura);

        foreach ($data as $key => $value) {
            $i++;

            $linkid = '<a href="' . base_url() . 'orders/update/' . $value['id'] . '" target="_blank">' . $value['id'] . '</a>';
            $buttons = '<a target="__blank" href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';

            $result[$key] = array(
                $value['id'],
                $value['origin'],
                $value['numero_marketplace'],
                $this->lang->line('application_order_' . $value['paid_status']),
                $value['reason'],
                $value['data_cancelamento'],
                $value['loja'],
                $linkid,
                $this->formatprice($value['gross_amount']),
                $buttons
            );
        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_orders->getOrdensCancelaMktCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

    public function etiquetas()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        //$this->log_data('Products','index','-');

        $this->render_template('orders/etiquetas', $this->data);
    }

    public function etiquetasData()
    {
        ob_start();
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        // $this->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 2 letras
                $procura = " AND ( o.id like '%" . $busca['value'] . "%' OR o.numero_marketplace like '%" . $busca['value'] . "%'";
                $procura .= "  OR s.name  like '%" . $busca['value'] . "%' OR oi.name like '%" . $busca['value'] . "%' ) ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'id', 'bill_no', 'loja', '', 'item', 'CAST(gross_amount AS DECIMAL(12,2))', 'date_time', '');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_orders->getOrdersEtiquetas($ini, $procura, $sOrder);

        $i = 0;
        $filtered = $this->model_orders->getOrdersEtiquetasCount($procura);

        foreach ($data as $key => $value) {
            $i++;

            $linkid = '<a href="' . base_url() . 'orders/update/' . $value['id'] . '" target="_blank" >' . $value['id'] . '</a>';
            $buttons = '';
            if ($value['link_plp'] != '') {
                $buttons .= '<a href="' . $value['link_plp'] . '" target="_blank"  class="btn btn-primary active">' .
                    '<i class="glyphicon glyphicon-print" aria-hidden="true"></i>&nbsp PLP' .
                    '</a>';
            }
            if ($value['link_etiqueta_a4'] != '') {
                $buttons .= '<a href="' . $value['link_etiqueta_a4'] . '" target="_blank"  class="btn btn-primary active">' .
                    '<i class="glyphicon glyphicon-print" aria-hidden="true"></i>&nbsp A4' .
                    '</a>';
            }
            if ($value['link_etiqueta_termica'] != '') {
                $buttons .= '<a href="' . $value['link_etiqueta_termica'] . '" target="_blank"  class="btn btn-primary active">' .
                    '<i class="glyphicon glyphicon-print" aria-hidden="true"></i>&nbsp ' . $this->lang->line('application_thermal') .
                    '</a>';
            }

            $buttons .= '<a target="__blank" href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';
            $linkprd = '<a href="' . base_url() . 'products/update/' . $value['product_id'] . '" target="_blank">' . $value['item'] . '</a>';
            $result[$key] = array(
                $value['id'],
                $linkid,
                $value['numero_marketplace'],
                $value['loja'],
                $this->model_orders->countOrderItem($value['id']),
                $linkprd,
                $this->formatprice($value['gross_amount']),
                date('d/m/Y', strtotime($value['date_time'])),
                $buttons
            );
        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_orders->getOrdersEtiquetasCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

    public function etiquetasSelect()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('orders/etiquetas', $this->data);
    }


    public function inTransitSentToMarketplace()
    {
        if (!in_array('doIntegration', $this->permission) || ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            redirect('dashboard', 'refresh');
        }
        //$this->log_data('Products','index','-');

        $this->render_template('orders/enviomkt', $this->data);
    }

    public function envioMktSelect()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!is_null($this->postClean('id'))) {
            $this->log_data('Products', 'enviadoMkt', json_encode($_POST), "I");
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    $this->model_orders->updatePaidStatus($id, 5); // Ordem enviada e marcada no marketplace
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $id) {
                    $this->model_orders->updatePaidStatus->updatePaidStatus($id, xx);
                    //this->model_integrations->unsetProductToMkt($mkt,$id,$cpy,$prd);
                }
            }
        }

        redirect('orders/inTransitSentToMarketplace', 'refresh');
    }

    public function invoiceMktSelect()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $order = array(
            'envia_nf_mkt'  => date('Y-m-d H:i:s'),
            'paid_status'   => 50
        );

        if (!is_null($this->postClean('id'))) {
            $this->log_data('Products', 'invoiceMkt', json_encode($_POST), "I");
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    $this->model_orders->updateByOrigin($id, $order); // Ordem enviada e marcada no marketplace
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $id) {
                    //$this->model_orders->updatePaidStatus->updatePaidStatus($id, xx);
                    //this->model_integrations->unsetProductToMkt($mkt,$id,$cpy,$prd);
                }
            }
        }

        redirect('orders/invoiceSentToMarketplace', 'refresh');
    }

    public function trackingMktSelect()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $order = array(
            'paid_status' => 53
        );

        if (!is_null($this->postClean('id'))) {
            $this->log_data('Products', 'trackingMkt', json_encode($_POST), "I");
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    $this->model_orders->updateByOrigin($id, $order); // Ordem enviada e marcada no marketplace
                }
            }
        }

        redirect('orders/trackingSentToMarketplace', 'refresh');
    }

    public function envioMktData()
    {
        ob_start();
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        // $this->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 2 letras
                $procura = " AND ( o.origin like '%" . $busca['value'] . "%' OR o.bill_no like '%" . $busca['value'] . "%'";
                $procura .= "  OR s.name  like '%" . $busca['value'] . "%' OR o.customer_name like '%" . $busca['value'] . "%'";
                $procura .= "  OR o.id like '%" . $busca['value'] . "%' OR f.codigo_rastreio like '%" . $busca['value'] . "%') ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'origin', 'numero_marketplace', 'bill_no', 'loja', 'customer_name', 'id', '', 'gross_amount', 'data_envio', 'f.codigo_rastreio', '');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $procura .= " AND o.order_manually = 1 ";

        $result = array();

        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_orders->getOrdensEnvioMktData($ini, $procura, $sOrder);

        $i = 0;
        $filtered = $this->model_orders->getOrdensEnvioMktCount($procura);

        foreach ($data as $key => $value) {
            $i++;

            $linkid = '<a href="' . base_url() . 'orders/update/' . $value['id'] . '" target="_blank">' . $value['id'] . '</a>';
            $buttons = '<a target="__blank" href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';

            $result[$key] = array(
                $value['id'],
                $value['origin'],
                $value['numero_marketplace'],
                $value['bill_no'],
                $value['loja'],
                $value['customer_name'],
                $linkid,
                $this->model_orders->countOrderItem($value['id']),
                $this->formatprice($value['gross_amount']),
                date("d/m/Y H:i:s", strtotime($value['data_envio'])),
                $value['codigo_rastreio'],
                $buttons
            );
        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_orders->getOrdensEnvioMktCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

    public function invoiceMktData()
    {
        ob_start();
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        // $this->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 2 letras
                $procura = " AND ( o.origin like '%" . $busca['value'] . "%' OR o.bill_no like '%" . $busca['value'] . "%'";
                $procura .= "  OR s.name  like '%" . $busca['value'] . "%' OR o.customer_name like '%" . $busca['value'] . "%'";
                $procura .= "  OR o.id like '%" . $busca['value'] . "%' OR f.codigo_rastreio like '%" . $busca['value'] . "%') ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'origin', 'numero_marketplace', 'bill_no', 'loja', 'customer_name', 'id', '', 'gross_amount', 'data_envio', 'f.codigo_rastreio', '');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $procura .= " AND o.order_manually = 1 ";

        $result = array();

        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_orders->getOrdensWithNfeMktData($ini, $procura, $sOrder);

        $i = 0;
        $filtered = $this->model_orders->getOrdensWithNfeMktCount($procura);

        foreach ($data as $key => $value) {
            $i++;

            $linkid = '<a href="' . base_url() . 'orders/update/' . $value['id'] . '" target="_blank">' . $value['id'] . '</a>';
            $buttons = '<a target="__blank" href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';

            $result[$key] = array(
                $value['id'],
                $value['origin'],
                $value['numero_marketplace'],
                $value['loja'],
                $value['customer_name'],
                $linkid,
                $this->model_orders->countOrderItem($value['id']),
                $this->formatprice($value['gross_amount']),
                date("d/m/Y H:i:s", strtotime($value['data_limite_cross_docking'])),
                $buttons
            );
        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_orders->getOrdensWithNfeMktCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

    public function trackingMktData(): CI_Output
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $filter_default[]['where']['o.paid_status'] = $this->model_orders->PAID_STATUS['tracking'];
            $filter_default[]['where']['o.order_manually'] = 1;

            $fields_order = array('', 'o.origin', 'o.numero_marketplace', 'o.bill_no', 's.name', 'o.customer_name', 'o.id', 'o.gross_amount', 'o.data_envio', '');

            $query = array();
            $query['select'][] = "o.origin, o.numero_marketplace, o.bill_no, s.name, o.customer_name, o.id, o.gross_amount, o.data_envio, o.data_limite_cross_docking";
            $query['from'][] = 'orders o';
            $query['join'][] = ["stores s", "o.store_id = s.id"];

            $data = fetchDataTable(
                $query,
                array('o.id', 'DESC'),
                null,
                null,
                ['doIntegration'],
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

        foreach ($data['data'] as $value) {
            $linkid = '<a href="' . base_url() . 'orders/update/' . $value['id'] . '" target="_blank">' . $value['id'] . '</a>';
            $buttons = '<a target="__blank" href="' . base_url('orders/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';

            $result[] = array(
                $value['id'],
                $linkid,
                $value['numero_marketplace'],
                $value['origin'],
                $value['customer_name'],
                $value['name'],
                $this->model_orders->countOrderItem($value['id']),
                $this->formatprice($value['gross_amount']),
                dateFormat($value['data_limite_cross_docking'], DATE_BRAZIL),
                $buttons
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function invoiceSentToMarketplace()
    {
        if (!in_array('doIntegration', $this->permission) || ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('orders/enviadoNfeMkt', $this->data);
    }

    public function trackingSentToMarketplace()
    {
        if (!in_array('doIntegration', $this->permission) || ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('orders/trackingSentToMarketplace', $this->data);
    }

	public function errorOrderAjax()
    {
        ob_start();
        $orders  = $this->postClean('order_id');
        ob_clean();
        echo json_encode($this->model_orders->getErrorsProductsForInvoice($orders));
    }

    public function downloadXmlLote()
    {
        ob_start();
        $postdata = $this->postClean(NULL,TRUE);
        $month = (int)$postdata['month'];
        $year = (int)$postdata['year'];
        $store = (int)$postdata['store'];

        if ($month < 0 || $month > 12 || $year < 2020 || $year > 2100 || $store == 0) {
            ob_clean();
            echo json_encode(false);
            exit();
        }
        if ($month < 10) $month = "0{$month}";

        $file = 'assets/images/xml/' . $store . '/' . $month . '-' . $year;
        if (!is_dir($file)) {
            ob_clean();
            echo json_encode(false);
            exit();
        }

        // Normaliza o caminho do diretório a ser compactado
        $source_path = realpath($file);

        // Caminho com nome completo do arquivo compactado
        // Nesse exemplo, será criado no mesmo diretório de onde está executando o script
        $zip_file = $file . '/' . $month . '-' . $year . '.zip';

        // Inicializa o objeto ZipArchive
        $zip = new ZipArchive();
        $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Iterador de diretório recursivo
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            $extFile = explode(".", $file);
            if (isset($extFile[1]) && $extFile[1] == 'zip') continue;
            // Pula os diretórios. O motivo é que serão inclusos automaticamente
            if (!$file->isDir()) {
                // Obtém o caminho normalizado da iteração corrente
                $file_path = $file->getRealPath();

                // Obtém o caminho relativo do mesmo.
                $relative_path = substr($file_path, strlen($source_path) + 1);

                // Adiciona-o ao objeto para compressão
                $zip->addFile($file_path, $relative_path);
            }
        }

        // Fecha o objeto. Necessário para gerar o arquivo zip final.
        $zip->close();

        // Retorna o caminho completo do arquivo gerado
        ob_clean();
        echo json_encode(array(base_url($zip_file), $month . '-' . $year . '.zip'));
    }

    public function RequestCancelInvoice()
    {
        ob_start();
        $orders  = $this->postClean('order_id');

        $dataNfe = $this->model_nfes->getDataInvoice($orders, "AND request_cancel = 0");

        if (count($dataNfe) == 0) {
            ob_clean();
            echo json_encode(false);
        }

        $invoice_responsible_email = $this->model_settings->getValueIfAtiveByName('invoice_responsible_email');
        if ($invoice_responsible_email) { // só manda se tiver um responsável por analizar isso.
            $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
            if (!$sellercenter) {
                $sellercenter = 'conectala';
            }
            $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
            if (!$from) {
                $from = 'marketing@conectala.com.br';
            }

            $data['dataNfe'] = $dataNfe;
            $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
            if (!$sellercenter_name) {
                $sellercenter_name = 'Conecta Lá';
            }
            $data['sellercentername'] = $sellercenter_name;
            if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/requestcancelinvoice.php')) {
                $body= $this->load->view('mailtemplate/'.$sellercenter.'/requestcancelinvoice',$data,TRUE);
            }
            else {
                $body= $this->load->view('mailtemplate/default/requestcancelinvoice',$data,TRUE);
            }
   
            $this->sendEmailMarketing($invoice_responsible_email, "Solicitação Cancelamento", $body, $from);
            
        }
        $this->model_nfes->requestCancel($orders);
        ob_clean();
        echo json_encode(true);
    }

    public function manage_tags()
    {
        if (!in_array('viewLogistics', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['page_title'] = $this->lang->line('application_manage tags');
        $this->data['page_now'] = 'manage tags';

        $typeViewTags = '';

        if ($this->data['usercomp'] == 1) { // admin
            $typeViewTags = 'all';
        } elseif($this->data['userstore'] == 0) {
            $tempTypeViewTags = array();
            $stores = $this->model_stores->getStoresByCompany($this->data['usercomp']);
            foreach ($stores as $store)
                if (!in_array($store['type_view_tag'], $tempTypeViewTags)) array_push($tempTypeViewTags, $store['type_view_tag']);

            if (in_array('all', $tempTypeViewTags)) $typeViewTags = 'all';
            elseif (in_array('correios', $tempTypeViewTags) && in_array('shipping_company_gateway', $tempTypeViewTags)) $typeViewTags = 'all';
            elseif (in_array('correios', $tempTypeViewTags)) $typeViewTags = 'correios';
            elseif (in_array('shipping_company_gateway', $tempTypeViewTags)) $typeViewTags = 'shipping_company_gateway';
        } else {
            $store = $this->model_stores->getStoresData($this->data['userstore']);
            $typeViewTags = $store['type_view_tag'];
        }

        $this->data['typeViewTags'] = $typeViewTags;
        $this->data['stores_filter'] = $this->model_orders->getStoresForFilter();
        $this->data['ship_company_filter'] = $this->model_freights->getAllShippingCompany();
        $this->data['users_filter'] = $this->model_users->getMyUsersData();
        $this->data['user_id'] = $this->session->userdata('id');

        $this->render_template('orders/manage_tags', $this->data);
    }

    public function groupsLabelsCorreios(): CI_Output
    {
        if (!in_array('viewLogistics', $this->permission)) {
            return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_no_access_error'))));
        }

        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $store_verify = null;
        $count_objects = 0;
        $objects_expired = [];
        $arrObjetosZpl = array();
        $logistic = null;
        $integration_logistic = null;
        $dataStore = array();
        $dataOrders = array();
        $trackingPlp = array();

        $orders = json_decode($this->postClean('orders'));

        if (count($orders) == 0) {
            return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_no_order_selected'))));
        }

        $data = array(
            'order_id'      => 0,
            'company_id'    => 0,
            'store_id'      => 0,
            'status'        => 2
        );

        $this->db->trans_begin();

        foreach ($orders as $order) {
            $objetosOrder = array();
            $orderDb = $this->model_orders->verifyOrderOfStore($order);
            if (!$orderDb) {
                $this->db->trans_rollback();
                return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_store_not_found'))));
            }

            $dataOrders[$order] = array(
                'order_id'  => $orderDb['id'],
                'in_resend' => $orderDb['in_resend_active']
            );

            $integration_logistic = $orderDb['integration_logistic'];

            if (!$store_verify) {
                $store_verify = $orderDb['store_id'];

                $dataStore = $this->model_stores->getStoresData($store_verify);

                $logistic = $this->calculofrete->getLogisticStore(array(
                    'freight_seller' 		=> $dataStore['freight_seller'],
                    'freight_seller_type' 	=> $dataStore['freight_seller_type'],
                    'store_id'				=> $dataStore['id']
                ));
            } else {
                if ($store_verify != $orderDb['store_id']) {
                    $this->db->trans_rollback();
                    return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_store_different'))));
                }
            }

            // A logística ainda é a mesma, não precisa trocar.
            if ($logistic['type'] != 'correios' && $integration_logistic != 'correios') {
                $this->model_freights->removeForOrderId($order, false, $orderDb['in_resend_active'] == 1);
                $this->model_orders->updatePaidStatus($order, 50);
                continue;
            }

            $rastreios = $this->model_freights->getCountObjetosOrder($order, $orderDb['in_resend_active'] == 1);
            $label_date = date("Y-m-d", strtotime("+15 days", strtotime(date('Y-m-d', strtotime($rastreios[0]['data_etiqueta'] ?? date('Y-m-d'))))));

            // Etiqueta vencida.
            if (strtotime($label_date) < strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {
                $objects_expired[] = $order;
                continue;
            }

            foreach ($rastreios as $rastreio) {
                $objetosOrder[] = $rastreio['codigo_rastreio'];
                $trackingPlp[] = $rastreio['codigo_rastreio'];
                $count_objects++;
            }

            $data['order_id']           = (int)$order;
            $data['company_id']         = $orderDb['company_id'];
            $data['store_id']           = $orderDb['store_id'];
            $data['in_resend_active']   = $orderDb['in_resend_active'];
            $data['date_expiration']    = $label_date;

            $this->model_freights->insertRequestPLP($data);

            // cria array de zpls
            $zpls = $this->getStrZplByTrackinAndOrder($objetosOrder, $order);
            $arrObjetosZpl[$order] = $zpls;

            $this->model_orders->updatePaidStatus($order, $orderDb['in_resend_active'] ? 53 : 51);
        }

        // A logística ainda é a mesma, não precisa trocar.
        if ($integration_logistic == 'correios') {
            $this->calculofrete->instanceLogistic($integration_logistic, $dataStore['id'], $dataStore, false);
            $this->calculofrete->logistic->setCredentialsSellerCenter();
        }

        if ($count_objects > 100) {
            $this->db->trans_rollback();
            return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_more_100'))));
        }

        if ($count_objects == 0) {
            $this->db->trans_rollback();
            if (count($objects_expired)) {

                foreach ($objects_expired as $order) {
                    $this->model_freights->removeForOrderId($order, false, $dataOrders[$order]['in_resend'] == 1);
                    $this->model_orders->updatePaidStatus($order, 50);
                }

                return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_label_expired_try_again'))));
            }
            return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_no_order_selected'))));
        }

        try {
            $this->calculofrete->instanceLogistic($logistic['type'], $store_verify, $dataStore, $logistic['seller']);
            $plp = $this->calculofrete->logistic->generatePlp();
        } catch (InvalidArgumentException $exception) {
            $this->db->trans_rollback();
            return $this->output->set_output(json_encode(array('success' => false, 'message' => "Não foi possível gerar etiquetas para a solicitação, tente novamente em alguns minutos", "error" => $exception->getMessage())));
        }

        try {
            $file_plp = base_url("assets/images/etiquetas/P_{$plp}_PLP.pdf");

            foreach ($dataOrders as $order) {
                $order_id = $order['order_id'];

                // atualiza file da plp
                $this->model_freights->updateFreightsOrderId($order_id, array('link_plp' => $file_plp), $order['in_resend']);

                // define número da plp, status e data de expiração.
                $this->model_freights->updateNumberPlpForOrderId($order_id, array('number_plp' => $plp));
            }

            //Cria arquivo txt pra zpl de pedido
            $this->createFileZpl($arrObjetosZpl);

            // Gerar grupo de etiquetas
            $data = $this->calculofrete->logistic->saveLabelGroup($trackingPlp, $plp);

            $this->model_freights->updatePlpAfterUpload($plp, $data);
        } catch (InvalidArgumentException $exception) {
            $this->db->trans_rollback();

            // Remove a etiqueta pois foi usada na plp e deu erro.
            foreach ($dataOrders as $order) {
                $this->model_freights->removeForOrderId($order['order_id'], false, $order['in_resend'] == 1);
                $this->model_orders->updatePaidStatus($order['order_id'], 50);
            }

            return $this->output->set_output(json_encode(array('success' => false, 'message' => "Não foi possível gerar etiquetas para a solicitação, tente novamente em alguns minutos", "error" => $exception->getMessage())));
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_try_again'))));
        }

        $this->log_data('batch', $log_name, 'PLP criado, orders=' . json_encode($orders) . ', user_id=' . $this->session->userdata('id') . ', objects_expired='.json_encode($objects_expired));
        $this->db->trans_commit();

        $additional_message = '';
        if (count($objects_expired)) {
            foreach ($objects_expired as $order) {
                $this->model_freights->removeForOrderId($order, false, $dataOrders[$order]['in_resend'] == 1);
                $this->model_orders->updatePaidStatus($order, 50);
            }

            $additional_message = " Alguns pedidos estavam com a postagem vencida, serão geradas novas postagens e será possível imprimir em alguns instante. Pedidos: " . json_encode($objects_expired);
        }
        return $this->output->set_output(json_encode(array('success' => true, 'message' => 'Etiquetas geradas com sucesso!'.$additional_message)));
    }

    public function manage_tags_post()
    {
        ob_start();
        if (!in_array('viewLogistics', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $settingPlpAutomatic = $this->model_settings->getSettingDatabyName('plp_automatic');

        $plpAutomatic = true;
        $storesException = array();

        if (!$settingPlpAutomatic || $settingPlpAutomatic['status'] == 2)
            $plpAutomatic = false;

        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $setting_sgp = $this->model_settings->getSettingDatabyName('token_sgp_correios');
        if ($setting_sgp == false) {
            $this->log_data('batch', 'generatePdfEtiquetas', 'Falta o cadastro do parametro token_sgp_correios', "E");
            ob_clean();
            echo json_encode(array('success' => false, 'message' => 'Falta o cadastro do parametro token_sgp_correios!'));
            exit();
        }

        $store_verify = null;
        $count_objects = 0;
        $arrObjetosPlp = array();
        $arrObjetosZpl = array();
        $_plp_temp = time();
        $logistic = null;
        $integration_logistic = null;
        $dataStore = array();
        $dataOrders = array();
        $trackingPlp = array();

        $orders = $this->postClean('orders');
        $orders = json_decode($orders);

        if (count($orders) == 0) {
            ob_clean();
            echo json_encode(array('success' => false, 'message' => $this->lang->line('messages_no_order_selected')));
            exit();
        }

        $data = array(
            'order_id' => 0,
            'company_id' => 0,
            'store_id' => 0,
            'status' => 0,
            'date_expiration' => date("Y-m-d",strtotime("+8 days", strtotime(date('Y-m-d'))))
        );
        $setting_quote_default_sellercenter = $this->model_settings->getValueIfAtiveByName('quote_default_sellercenter');

        $this->db->trans_begin();

        foreach ($orders as $order) {
            $objetosOrder = array();
            $orderDb = $this->model_orders->verifyOrderOfStore($order);
            if (!$orderDb) {
                $this->db->trans_rollback();
                ob_clean();
                echo json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_store_not_found')));
                exit();
            }

            $dataOrders[$order] = array(
                'order_id'      => $orderDb['id'],
                'in_resend'     => $orderDb['in_resend_active']
            );

            $integration_logistic = $orderDb['integration_logistic'];

            if (!$store_verify) {
                $store_verify = $orderDb['store_id'];

                $dataStore = $this->model_stores->getStoresData($store_verify);

                $logistic = $this->calculofrete->getLogisticStore(array(
                    'freight_seller' 		=> $dataStore['freight_seller'],
                    'freight_seller_type' 	=> $dataStore['freight_seller_type'],
                    'store_id'				=> $dataStore['id']
                ));
            } else {
                if ($store_verify != $orderDb['store_id']) {
                    $this->db->trans_rollback();
                    ob_clean();
                    echo json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_store_different')));
                    exit();
                }
            }

            // A logística ainda é a mesma, não precisa trocar.
            if ($logistic['type'] != 'sgpweb' && $integration_logistic != 'sgpweb') {
                // Padrão Grupo Soma, pois não usam o módulo de frete 100% ainda
                if ($logistic['type'] != 'sellercenter' || $setting_quote_default_sellercenter != 'sgpweb') {
                    $this->model_freights->removeForOrderId($order, false, $orderDb['in_resend_active'] == 1);
                    $this->model_orders->updatePaidStatus($order, 50);
                    continue;
                }
            }

            $data['order_id']           = (int)$order;
            $data['company_id']         = $orderDb['company_id'];
            $data['store_id']           = $orderDb['store_id'];
            $data['in_resend_active']   = $orderDb['in_resend_active'];

            $this->model_freights->insertRequestPLP($data);

            $rastreios = $this->model_freights->getCountObjetosOrder($order, $orderDb['in_resend_active'] == 1);

            foreach ($rastreios as $rastreio) {
                $arrObjetosPlp[] = array('objeto' => $rastreio['codigo_rastreio']);
                $objetosOrder[] = $rastreio['codigo_rastreio'];
                $trackingPlp[] = $rastreio['codigo_rastreio'];
                $count_objects++;
            }

            // cria array de zpls
            $zpls = $this->getStrZplByTrackinAndOrder($objetosOrder, $order);
            $arrObjetosZpl[$order] = $zpls;

            $this->model_orders->updatePaidStatus($order, $orderDb['in_resend_active'] ? 53 : 51);
        }

        // A logística ainda é a mesma, não precisa trocar.
        if ($integration_logistic == 'sgpweb' || (
                // Padrão Grupo Soma, pois não usam o módulo de frete 100% ainda
                $logistic['type'] == 'sellercenter' && $setting_quote_default_sellercenter == 'sgpweb')
        ) {
            $this->calculofrete->instanceLogistic($integration_logistic, $dataStore['id'], $dataStore, false);
            $this->calculofrete->logistic->setCredentialsSellerCenter();
            $credentials = $this->calculofrete->logistic->getCredentials();
        } else {
            $credentials = $this->calculofrete->getCredentialsRequest($dataStore['id']);
        }

        if ($count_objects > 100) {
            $this->db->trans_rollback();
            ob_clean();
            echo json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_more_100')));
            exit();
        }

        if ($count_objects == 0) {
            $this->db->trans_rollback();
            ob_clean();
            echo json_encode(array('success' => false, 'message' => $this->lang->line('messages_no_order_selected')));
            exit();
        }

        $arrPlps = array();
        $ordersError = array();
        $objetosError = array();
        $messagesError = array();

        if ($plpAutomatic || in_array($store_verify, $storesException)) {
            // Gera PLP
            // $url = "http://177.70.27.232/sgp_login/v/2.1/api/index.php/gerar-plp?chave_integracao={$credentials['token']}"; // REMOVIDO SGP TROCOU O ENDPOINT 11/09/20
            $url = "https://gestaodeenvios.com.br/sgp_login/v/2.2/api/index.php/gerar-plp?chave_integracao={$credentials['token']}";
            if (array_key_exists('type_integration', $credentials)) {
                if ($credentials['type_integration'] === 'sgpweb') {
                    $url = "https://www.sgpweb.com.br/novo/api/index.php/gerar-plp?chave_integracao={$credentials['token']}";
                }
            }
            $json_post = json_encode(array('objetos' => $arrObjetosPlp));
            $json_post = stripslashes($json_post);
            $retornoPlp = $this->executaGetWeb($url, $json_post);

            // Caso de problema
            if ($retornoPlp['httpcode'] != "201") {
                // erro
                echo json_encode(array($retornoPlp['httpcode'], $retornoPlp['content']));
                exit();
            }

            $decodeRetorno = json_decode($retornoPlp['content']);
            $plps = $decodeRetorno->objetos ?? array();

            if (count($plps) == 0) {
                $this->db->trans_rollback();
                $this->log_data('batch', 'generatePlp', 'Não encontrou resultados na criação de PLP retorno=' . json_encode($retornoPlp), "E");
                ob_clean();
                echo json_encode(array('success' => false, 'message' => "Não foi possível gerar etiquetas para a solicitação, tente novamente em alguns minutos"));
                exit();
            }
        } else {
            foreach ($arrObjetosPlp as $objeto) {

                $orders_plp = $this->model_freights->getOrderIdForCodeTracking($objeto['objeto']);

                if (!array_key_exists($_plp_temp, $arrPlps)) {
                    $arrPlps[$_plp_temp] = array();
                }

                $arrPlps[$_plp_temp][] = array(
                    'code_tracking' => $objeto['objeto'],
                    'order_id' => $orders_plp['order_id']
                );
            }
        }

        if ($plpAutomatic || in_array($store_verify, $storesException)) {
            foreach ($plps as $plp) {
                $orders_plp = $this->model_freights->getOrderIdForCodeTracking($plp->objeto);
                if ($plp->erro) {
                    // encontrou erro, criar log e mostrar pedido(etiqueta) com erro que gerar necessário gerar novamente
                    $objetosError[] = $plp->objeto;

                    //cancelar etiqueta dos pedidos para gerar novamente
                    $ordersError[] = $orders_plp['order_id'];
                    $messagesError[] = $plp->erro;
                    continue;
                }

                if (!array_key_exists($plp->plp, $arrPlps)) {
                    $arrPlps[$plp->plp] = array();
                }

                $arrPlps[$plp->plp][] = array(
                    'code_tracking' => $plp->objeto,
                    'order_id'      => $orders_plp['order_id'],
                    'in_resend'     => $orders_plp['in_resend_active']
                );
            }

            if (count($arrPlps) == 0 || count($ordersError) > 0) {
                $this->db->trans_rollback();

                foreach ($ordersError as $keyError => $order) {

                    $orderDb = $this->model_orders->verifyOrderOfStore($order);

                    $this->model_freights->removeForOrderId($order, false, $orderDb['in_resend_active'] == 1);
                    $this->model_orders->updatePaidStatus($order, 50);

                    // cria comentário no pedido de quem excluiu
                    $dataOrder = $this->model_orders->getOrdersData(0, $order);

                    $arrComment = array();
                    if ($dataOrder['comments_adm']) {
                        $arrComment = json_decode($dataOrder['comments_adm']);
                    }

                    $user_id = $this->session->userdata('id');
                    $user = $this->model_users->getUserData($user_id);
                    $userName = $user['username'];

                    $arrComment[] = array(
                        'order_id'  => $order,
                        'comment'   => $messagesError[$keyError],
                        'user_id'   => $user_id,
                        'user_name' => $userName,
                        'date'      => date('Y-m-d H:i:s')
                    );

                    $sendComment  = json_encode($arrComment);

                    $this->model_orders->createCommentOrderInProgress($sendComment, $order);
                }

                $this->log_data('batch', 'generatePlp', 'Encontrou erros para gerar PLP nos seguintes pedidos=' . json_encode($ordersError) . ' e nos seguintes objetos=' . json_encode($objetosError) . 'as etiquetas .Retorno sgp=' . json_encode($decodeRetorno), "E");

                ob_clean();
                echo json_encode(array('success' => false, 'message' => "Não foi possível gerar PLP para a solicitação, tente novamente em alguns minutos."));
                exit();
            }
        } else {
            foreach ($orders as $order) {
                $data = array(
                    'number_plp' => $_plp_temp,
                    'status' => 0
                );
                // define número da plp
                $this->model_freights->updateNumberPlpForOrderId($order, $data);
                $this->model_freights->updateFreightsOrderId($order, array('link_plp' => ''));
            }

            // Gerar grupo de etiquetas
            $this->generatePdfEtiquetas($arrPlps, $dataStore, $logistic);
        }

        if ($plpAutomatic || in_array($store_verify, $storesException)) {
            foreach ($arrPlps as $plp => $itens) {

                $file_plp = base_url("assets/images/etiquetas/P_{$plp}_PLP.pdf");

                foreach ($itens as $iten) {
                    $order_id = $iten['order_id'];

                    // atualiza file da plp
                    $this->model_freights->updateFreightsOrderId($order_id, array('link_plp' => $file_plp), $iten['in_resend']);

                    $data = array(
                        'number_plp' => $plp,
                        'status' => 1
                    );
                    // define número da plp
                    $this->model_freights->updateNumberPlpForOrderId($order_id, $data);
                }

                // Gerar PLP
                $this->generatePlp($plp, $credentials);
            }

            //Cria arquivo txt pra zpl de pedido
            $this->createFileZpl($arrObjetosZpl);

            // Gerar grupo de etiquetas
            $this->generatePdfEtiquetas($arrPlps, $dataStore, $credentials);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            foreach ($ordersError as $order) {
                $this->model_freights->removeForOrderId($order);
                $this->model_orders->updatePaidStatus($order, 50);
            }

            ob_clean();
            echo json_encode(array('success' => false, 'message' => $this->lang->line('messages_error_generate_plp_try_again')));
            exit();
        }

        $this->log_data('batch', $log_name, 'PLP criado, PLP=' . json_encode($arrPlps) . ', orders=' . json_encode($orders) . ', user_id=' . $this->session->userdata('id'), "I");
        $this->db->trans_commit();
        ob_clean();
        echo json_encode(array('success' => true, 'message' => 'Etiquetas geradas com sucesso!'));
    }

    public function fetchEtiquetas(string $integration = 'sgpweb')
    {
        $dataEtiqueta = $this->model_orders->getEtiquetasGeneratePLP($integration === 'sgpweb' ? 1 : 7);
        $etiquetasTemp = array();
        $etiquetas = array();

        // set seller center
        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }

        foreach ($dataEtiqueta as $item) {
            if (!key_exists($item['id'], $etiquetasTemp)) {
                $etiquetasTemp[$item['id']] = array(
                    'id'            => $item['id'],
                    'name_item'     => "- <a href='" . base_url('products/update/' . $item['product_id']) . "' target='_blank'>{$item['sku']}</a>",
                    'product_id'    => $item['product_id'],
                    'date_time'     => $item['date_time'],
                    'customer_name' => $item['customer_name'],
                    'gross_amount'  => $sellerCenter == 'somaplace' ? $item['net_amount'] : $item['gross_amount'],
                    'store'         => $item['store']
                );
            } else {
                $etiquetasTemp[$item['id']]['name_item'] .= "<br>- <a href='" . base_url('products/update/' . $item['product_id']) . "' target='_blank'>{$item['sku']}</a>";
            }
        }

        foreach ($etiquetasTemp as $item) {
            $etiquetas[] = $item;
        }

        return $this->output->set_output(json_encode($etiquetas));
    }

    public function manage_tags_adm()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('number_plp', 'Erro Interno - Tente novamente', 'trim|required');
        $this->form_validation->set_rules('number_plp_edit', 'Sem Número de PLP', 'trim|required');

        if ($this->form_validation->run() == TRUE) {

            $number_plp  = $this->postClean('number_plp');
            $number_plp_edit = $this->postClean('number_plp_edit');
            $dataPLP    = $this->model_freights->getDataPLP($number_plp, true);
            $arrObjetosZpl = array();

            if (empty($number_plp_edit)) {
                $this->session->set_flashdata('error', 'Informe um número de PLP válido!');
                redirect('orders/manage_tags_adm', 'refresh');
            }

            $this->db->trans_begin();

            foreach ($dataPLP as $order) {

                $objetosOrder = array();
                // remove as plp caso exista
                $file_plp = "assets/images/etiquetas/P_{$number_plp_edit}_PLP.pdf";
                if (file_exists($file_plp)) unlink($file_plp);

                // cria url do diretório da plp
                $urlPLP = base_url($file_plp);

                // array para atualizar no banco
                $data = array(
                    'link_plp' => $urlPLP
                );

                // atualiza plp
                $this->model_freights->updateFreightsOrderId($order['order_id'], $data);

                $rastreios = $this->model_freights->getCountObjetosOrder($order['order_id'], true);
                foreach ($rastreios as $rastreio)
                    array_push($objetosOrder, $rastreio['codigo_rastreio']);

                // cria array de zpls
                $zpls = $this->getStrZplByTrackinAndOrder($objetosOrder, $order['order_id']);
                $arrObjetosZpl[$order['order_id']] = $zpls;

                // cria comentario no pedido de quem excluiu
                $dataOrder = $this->model_orders->getOrdersData(0, $order['order_id']);

                $arrComment = array();
                if ($dataOrder['comments_adm'])
                    $arrComment = json_decode($dataOrder['comments_adm']);

                $user_id    = $this->session->userdata('id');
                $user       = $this->model_users->getUserData($user_id);
                $userName   = $user['username'];

                array_push($arrComment, array(
                    'order_id'  => $order['order_id'],
                    'comment'   => "PLP alterada de {$number_plp} para $number_plp_edit",
                    'user_id'   => $user_id,
                    'user_name' => $userName,
                    'date'      => date('Y-m-d H:i:s')
                ));

                $sendComment  = json_encode($arrComment);

                $create = $this->model_orders->createCommentOrderInProgress($sendComment, $order['order_id']);
            }

            $data = array(
                'number_plp' => $number_plp_edit,
                'status'    => 1
            );
            $this->model_freights->updatePlpAfterUpload($number_plp, $data);

            // Gerar nova PLP
            $this->generatePlp($number_plp_edit);

            //Cria arquivo txt pra zpl de pedido
            $this->createFileZpl($arrObjetosZpl);

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Ocorreu um problema para salvar a PLP, tente novamente!');
                redirect('orders/manage_tags_adm', 'refresh');
            }

            $this->db->trans_commit();
            $this->session->set_flashdata('success', 'PLP importada com sucesso!');
            redirect('orders/manage_tags_adm', 'refresh');
        }

        $settingPlpAutomatic = $this->model_settings->getSettingDatabyName('plp_automatic');
        $plpAutomatic = true;
        if (!$settingPlpAutomatic || $settingPlpAutomatic['status'] == 2)
            $plpAutomatic = false;

        $this->data['plpAutomatic'] = $plpAutomatic;

        $this->render_template('orders/manage_tags_adm', $this->data);
    }

    public function fetchPLPRequestData()
    {
        $settingPlpAutomatic = $this->model_settings->getSettingDatabyName('plp_automatic');

        $plpAutomatic = true;
        if (!$settingPlpAutomatic || $settingPlpAutomatic['status'] == 2)
            $plpAutomatic = false;

        if (!in_array('doIntegration', $this->permission)) {
            exit();
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        //$this->log_data('Orders','postdata',print_r($ini+1,true));
        $busca = $postdata['search'];

        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $this->data['ordersfilter'] = " 
                AND (correios_plps.number_plp like '%" . $busca['value'] . "%' 
                OR stores.name like '%" . $busca['value'] . "%' 
				OR company.name like '%" . $busca['value'] . "%'
				OR correios_plps.date_created like '%" . $busca['value'] . "%')";
            } else {
                //return true;
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('correios_plps.number_plp', 'correios_plps.status', 'stores.name', 'company.name', 'correios_plps.date_created', 'correios_plps.id');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo == 'id') { // inverto no caso do ID
                if ($postdata['order'][0]['dir'] == "asc") {
                    $direcao = "desc";
                } else {
                    $direcao = "asc";
                }
            }
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        if (isset($this->data['ordersfilter'])) {
            $filtered = $this->model_freights->getCountRequestPLP($this->data['ordersfilter']);
        } else {
            $filtered = 0;
        }

        $data = $this->model_freights->getRequestPLP($ini, '', $sOrder);
        $i = 0;
        foreach ($data as $key => $value) {
            $i++;

            $date_time = date('d/m/Y H:i', strtotime($value['date_created']));

            // button
            $buttons = '<button type="button" class="btn btn-default view-request" number-plp="' . $value['number_plp'] . '"><i class="fa fa-edit"></i></button>';
            $status = $value['status'] == 0 ? '<span class="label label-warning">Pendente</span>' : '<span class="label label-success">Concluído</span>';

            if ($plpAutomatic)
                $result[$key] = array(
                    $value['number_plp'],
                    $value['store_name'],
                    $value['company_name'],
                    $date_time,
                    $buttons
                );
            else
                $result[$key] = array(
                    $value['status'] == 0 ? 'Em Andamento' : $value['number_plp'],
                    $status,
                    $value['store_name'],
                    $value['company_name'],
                    $date_time,
                    $buttons
                );
        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_freights->getCountRequestPLP(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        //$this->log_data('Orders','result',print_r($output,true));
        //ob_clean();
        echo json_encode($output);
    }

    public function viewDataPLP()
    {
        ob_start();
        if (!in_array('doIntegration', $this->permission)) {
            exit();
        }

        $number_plp = $this->postClean('number_plp');

        $dataPLP = $this->model_freights->getDataPLP($number_plp);

        ob_clean();
        echo json_encode($dataPLP);
    }

    public function upload_plp($order_id)
    {
        // assets/images/product_image
        $config['upload_path'] = 'assets/images/etiquetas';
        $config['file_name'] =  "P_{$order_id}_PLP.pdf";
        $config['allowed_types'] = 'pdf';

        // $config['max_width']  = '1024';
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('file_plp')) {
            $error = $this->upload->display_errors();
            return array(false, $error);
        } else {
            $path = $this->upload->data();
            $namePath = $config['upload_path'] . '/' . $path['file_name'];

            return array(true, $namePath);
        }
    }

    public function getTagsTransmit(string $integration = 'sgpweb')
    {
        $dataPlp = $this->model_freights->getDataPlpTransmit($integration === 'sgpweb' ? [0,1] : [2]);
        $plps = array();

        foreach ($dataPlp as $item) {
            if(!key_exists($item['number_plp'], $plps)) {
                $button = $item['status'] == 1 ? '<a href="' . $item['link_plp'] . '" class="btn btn-success btn-sm-tag" download><i class="fas fa-file-powerpoint"></i> PLP </a>
                    <button type="button" class="btn btn-success btn-sm-tag viewTags" number-plp="' . $item['number_plp'] . '"><i class="fa fa-tag"></i> ' . $this->lang->line('application_labels') . '</button>' :
                    '<button type="button" class="btn btn-success btn-sm-tag viewTags" number-plp="' . $item['number_plp'] . '"><i class="fa fa-tag"></i> ' . $this->lang->line('application_labels') . '</button>';

                if (in_array('doIntegration', $this->permission)) {
                    $button .= '<button type="button" class="btn btn-danger btn-sm-tag del-plp ml-1" number-plp="' . $item['number_plp'] . '"><i class="fa fa-trash"></i> ' . $this->lang->line('application_delete') . '</button>';
                }

                $result = array(
                    "<a class='btn btn-primary btn-sm-tag' href='" . base_url('orders/update/' . $item['order_id']) . "' target='_blank'>{$item['order_id']}</a>&nbsp;",
                    $button
                );

                if ($integration === 'sgpweb') {
                    $result = array(
                        $item['number_plp'] == 0 || $item['status'] == 0 ? $this->lang->line('application_in_progress') : ($item['status'] == 2 ? '' : $item['number_plp']),
                        "<a class='btn btn-primary btn-sm-tag' href='" . base_url('orders/update/' . $item['order_id']) . "' target='_blank'>{$item['order_id']}</a>&nbsp;",
                        date('d/m/Y', strtotime($item['date_expiration'])),
                        $button
                    );
                }

                $plps[$item['number_plp']] = $result;
            } else {
                $plps[$item['number_plp']][$integration === 'sgpweb' ? 1 : 0] .= "<a class='btn btn-primary btn-sm-tag' href='" . base_url('orders/update/' . $item['order_id']) . "' target='_blank'>{$item['order_id']}</a>";
            }
        }

        return $this->output->set_output(json_encode($plps));
    }

    public function updateShippingMethod(int $order_id)
    {
        $this->output->set_content_type('application/json');
        $order = $this->model_orders->verifyOrderOfStore($order_id);
        if (!$order) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => $this->lang->line('messages_error_occurred')
            )));
        }

        $shipping_name = $this->postClean('shipping_name');
        $shipping_method = $this->postClean('shipping_method');

        if (empty($shipping_name) || empty($shipping_method)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => $this->lang->line('messages_all_fields_must_be_filled')
            )));
        }

        $update_order = array(
            'ship_company_preview' => $shipping_name,
            'ship_service_preview' => $shipping_method
        );

        $this->model_orders->updateByOrigin($order_id, $update_order);

        $this->log_data(__CLASS__, __FUNCTION__, "old_data=".json_encode(array('ship_company_preview' => $order['ship_company_preview'], 'ship_service_preview' => $order['ship_service_preview']))."\nnew_data=".json_encode($update_order));

        return $this->output->set_output(json_encode(array(
            'success' => true,
            'message' => $this->lang->line('messages_successfully_updated')
        )));
    }

    public function manage_tags_del()
    {
        ob_start();
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$this->postClean('number_plp')) {
            redirect('orders/manage_tags', 'refresh');
        }

        $number_plp  = $this->postClean('number_plp');
        $dataPLP    = $this->model_freights->getDataPLP($number_plp, true, false);

        $this->db->trans_begin();

        if(!$this->model_freights->removePlpOrder($number_plp)) {
            $this->db->trans_rollback();
            ob_clean();
            echo json_encode(array("success" => false, "message" => "Não foi encontrado a PLP: {$number_plp}"));
            exit();
        }

        foreach ($dataPLP as $order) {
            $files = "assets/images/etiquetas/P_{$order['order_id']}";

            if (file_exists($files . "_PLP.pdf")) unlink($files . "_PLP.pdf");
            if (file_exists($files . "_A4.pdf")) unlink($files . "_A4.pdf");
            if (file_exists($files . "_Termica.pdf")) unlink($files . "_Termica.pdf");

            if(!$this->model_orders->updatePaidStatus($order['order_id'], 50)) {
                $this->db->trans_rollback();
                ob_clean();
                echo json_encode(array("success" => false, "message" => "Não foi encontrado o pedido: {$order['order_id']} !"));
                exit();
            }

            // cria comentario no pedido de quem excluiu
            $dataOrder = $this->model_orders->getOrdersData(0, $order['order_id']);

            $arrComment = array();
            if ($dataOrder['comments_adm'])
                $arrComment = json_decode($dataOrder['comments_adm']);

            $user_id = $this->session->userdata('id');
            $user = $this->model_users->getUserData($user_id);
            $userName = $user['username'];

            array_push($arrComment, array(
                'order_id'  => $order['order_id'],
                'comment'   => "PLP {$number_plp} excluida",
                'user_id'   => $user_id,
                'user_name' => $userName,
                'date'      => date('Y-m-d H:i:s')
            ));

            $sendComment  = json_encode($arrComment);

            $create = $this->model_orders->createCommentOrderInProgress($sendComment, $order['order_id']);
        }

        if (!$this->model_freights->removePlp($number_plp)) {
            $this->db->trans_rollback();
            ob_clean();
            echo json_encode(array("success" => false, "message" => "Não foi encontrado a plp: {$number_plp} para a loja ou empresa!"));
            exit();
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            ob_clean();
            echo json_encode(array("success" => false, "message" => $this->lang->line('messages_error_remove_plp_try_again')));
            exit();
        }

        $this->log_data('batch', $log_name, 'PLP excluída, PLP=' . $number_plp . ', user_id=' . $this->session->userdata('id'), "I");
        $this->db->trans_commit();
        ob_clean();
        echo json_encode(array("success" => true, "message" => $this->lang->line('messages_successfully_remove_plp')));
    }

    public function generatePlp($plp, $data_credential = null)
    {
        $credentialCorreios = $this->model_settings->getSettingDatabyName('credentials_correios');

        $store_id = $this->model_freights->getStore_idCorreios_plps($plp);
        if (is_null($data_credential)) {
            $credential = $this->model_integration_logistic->getIntegrationsByStoreId($store_id);

            if (empty($credential)) {
                $credential = $this->model_integration_logistic->getIntegrationsByStoreId(0);
            }
            $data_credential = json_decode($credential['credentials'], true);
        }

        $cartao     = $data_credential['cart'] ?? $data_credential['post_card'];
        $contrato   = $data_credential['contract'];

        $dataCorreios = false;
        if ($credentialCorreios && $credentialCorreios['status'] == 1) {
            $dataCorreios = json_decode($credentialCorreios['value']);
        }

        $plpBarcode = str_pad($plp, 10, 0, STR_PAD_LEFT);
        $barcode = $this->generateBarCode($plpBarcode);

        $dataPlp = $this->model_freights->getDataPLPForplp($plp);
        $servicos = array();

        foreach ($dataPlp as $item) {
            if (!key_exists($item['idservico'], $servicos))
                $servicos[$item['idservico']] = array(
                    'idServico'     => $item['idservico'],
                    'quantidade'    => 1
                );
            else
                $servicos[$item['idservico']]['quantidade'] += 1;
        }

        if ($dataCorreios) {

            $htmlServicos = "";
            foreach ($servicos as $servico) {
                $htmlServicos .= "
                <tr>
                <td style='width: 20%; padding-left: 10px; font-size: 13px;padding-top: 15px'>{$cartao}</td>
                <td style='width: 20%; font-size: 13px;padding-top: 15px'>{$servico['idServico']}</td>
                <td style='width: 20%; font-size: 13px;padding-top: 15px'>{$servico['quantidade']}</td>
                <td style='width: 40%;padding-top: 15px'>&nbsp;</td>
               </tr>
            ";
            }

            $html = "
            <html>
                <head>
                    <title>PLP_{$plp}</title>
                    <meta http-equiv='content-type' content='text/html; charset=utf-8'>
                    <style>
                        body{
                            font-family: 'Microsoft YaHei','Source Sans Pro', sans-serif;
                            font-size:13px;
                        }
                        .table {
                            border-collapse:collapse;
                            font-size: 11px;
                            border: 0;
                            width: 100%;
                            margin: 0px;
                            cellspacing: 0;
                        }
                        .table.border {
                            border: 1px solid #000;
                        }
                        .table.padding td{
                            padding-left:10px
                        }
                        .table.padding td{
                            padding-top:5px
                        }
                        .table.padding td{
                            padding-bottom:10px
                        }
                        .table.header span{
                            font-size: 15px;
                        }
                        .mt-05 {
                            margin-top: 5px
                        }
                        .mt-1 {
                            margin-top: 10px
                        }
                        thead:before, thead:after { display: none; }
                        tbody:before, tbody:after { display: none; }
                    </style>
                </head>
                <body>
                    <table class='table'>
                        <tbody>
                            <tr>
                                <td style='width: 20%'><img src='./assets/images/logo-correios.png' width='100px'></td>
                                <td><h2 style='margin: 0px;'>SIGEP WEB - Gerenciador de Postagens dos Correios</h2></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class='table border padding header'>
                        <tbody>
                            <tr>
                            <td style='width: 75%; padding-top: 20px'>
                            <span>Contrato: {$contrato}</span><br>
                            <span>Cliente: {$dataCorreios->nome_contrato}</span><br>
                            <span>Telefone de Contato: {$dataCorreios->telefone_contrato}</span><br>
                            <span>Email de Contato: {$dataCorreios->email_contrato}</span>
                            </td>
                                <td style='width: 25%;text-align: center; padding-top: 15px'>
                                    <span>Lista: {$plp}</span>
                                    <br/>
                                    <div class='mt-1'>
                                    {$barcode}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class='table border mt-1'>
                        <tbody>
                            <tr>
                                <td style='width: 20%; padding-left: 10px; padding-top: 15px;font-size: 13px; font-weight: bold'>Cartão:</td>
                                <td style='width: 20%; padding-top: 15px;font-size: 13px; font-weight: bold'>Serviço:</td>
                                <td style='width: 20%; padding-top: 15px;font-size: 13px; font-weight: bold'>Quantidade:</td>
                                <td style='width: 40%; padding-right: 10px; text-align: right; padding-top: 15px;font-size: 13px; font-weight: bold'>Data da entrega: ____/____/______</td>
                            </tr>
                            {$htmlServicos}
                            <tr>
                                <td style='width: 60%; padding-top: 15px' colspan='3'>&nbsp;</td>
                                <td style='width: 40%; text-align: center; padding-top: 15px'>_________________________________________</td>
                            </tr>
                            <tr>
                                <td style='width: 60%; padding-top: 15px' colspan='3'>&nbsp;</td>
                                <td style='width: 40%; padding-bottom: 5px;font-size: 13px; padding-top: 15px;padding-left: 25px'>Assinatura / Matrícula dos Correios</td>
                            </tr>
                            <tr>
                                <td style='width: 60%; padding-left: 10px; padding-bottom: 5px;font-size: 13px; padding-top: 15px' colspan='4'>Obs.:</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class='table mt-05'>
                        <tbody>
                            <tr>
                                <td style='width: 5px'><img src='./assets/images/cut.png' width='15px'></td>
                                <td style='font-size: 13px; font-weight: bold;padding-bottom: 4px; padding-top: 10px'>-------------------------------------------------------------------------------------------------------------------------------------------------------------</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class='table border mt-05'>
                        <tbody>
                            <tr>
                                <td colspan='4' style='padding-top: 5px'><img src='./assets/images/logo-correios.png' width='100px'></td>
                            </tr>
                            <tr>
                                <td style='width: 20%; padding-left: 10px;font-size: 13px; font-weight: bold; padding-top: 15px'>Cartão:</td>
                                <td style='width: 20%;font-size: 13px; font-weight: bold; padding-top: 15px'>Serviço:</td>
                                <td style='width: 20%;font-size: 13px; font-weight: bold; padding-top: 15px'>Quantidade:</td>
                                <td style='width: 40%; padding-right: 10px; text-align: right;font-size: 13px; font-weight: bold; padding-top: 15px'>Data da entrega: ____/____/______</td>
                            </tr>
                            {$htmlServicos}
                            <tr>
                                <td style='width: 60%; padding-top: 15px' colspan='3'>&nbsp;</td>
                                <td style='width: 40%; text-align: center; padding-top: 15px'>_________________________________________</td>
                            </tr>
                            <tr>
                                <td style='width: 60%; padding-top: 15px' colspan='3'>&nbsp;</td>
                                <td style='width: 40%; padding-bottom: 5px;font-size: 13px;padding-top: 15px;padding-left: 25px'>Assinatura / Matrícula dos Correios</td>
                            </tr>
                            <tr>
                                <td style='width: 60%; padding-left: 10px; padding-bottom: 5px;font-size: 13px; padding-top: 15px' colspan='4'>Obs.:</td>
                            </tr>
                        </tbody>
                    </table>
                </body>
            </html>";
        }

        $dompdf = new Dompdf();

        if (count($dataPlp) == 0 || !$dataCorreios) {
            $html = "<div style='width: 100%; text-align: center;margin-top: 10%'><h2 style='font-family: 'Microsoft YaHei','Source Sans Pro', sans-serif;'>Não encontramos informações para essa PLP</h4></div>";
            $dompdf->loadHtml($html);
            $dompdf->render();
            $dompdf->stream(
                "PLP_{$plp}.pdf", /* Nome do arquivo de saída */
                array(
                    "Attachment" => false /* Para download, altere para true */
                )
            );
            return false;
        }

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        $output = $dompdf->output();
        file_put_contents("assets/images/etiquetas/P_{$plp}_PLP.pdf", $output);

        // Visualizar
        //    $dompdf->stream(
        //        "PLP_{$plp}.pdf", /* Nome do arquivo de saída */
        //        array(
        //            "Attachment" => false /* Para download, altere para true */
        //        )
        //    );
    }

    private function generateBarCode($numero, $altura = 50)
    {
        $code = "";

        $fino = 1.5;
        $largo = 4.5;

        $barcodes[0] = '00110';
        $barcodes[1] = '10001';
        $barcodes[2] = '01001';
        $barcodes[3] = '11000';
        $barcodes[4] = '00101';
        $barcodes[5] = '10100';
        $barcodes[6] = '01100';
        $barcodes[7] = '00011';
        $barcodes[8] = '10010';
        $barcodes[9] = '01010';

        for ($f1 = 9; $f1 >= 0; $f1--) {
            for ($f2 = 9; $f2 >= 0; $f2--) {
                $f = ($f1 * 10) + $f2;
                $texto = '';
                for ($i = 1; $i < 6; $i++)
                    $texto .= substr($barcodes[$f1], ($i - 1), 1) . substr($barcodes[$f2], ($i - 1), 1);

                $barcodes[$f] = $texto;
            }
        }

        $code .= '<img src="./assets/images/barcode/p.gif" width="' . $fino . '" height="' . $altura . '" border="0" />';
        $code .= '<img src="./assets/images/barcode/b.gif" width="' . $fino . '" height="' . $altura . '" border="0" />';
        $code .= '<img src="./assets/images/barcode/p.gif" width="' . $fino . '" height="' . $altura . '" border="0" />';
        $code .= '<img src="./assets/images/barcode/b.gif" width="' . $fino . '" height="' . $altura . '" border="0" />';

        $code .= '<img ';

        $texto = $numero;

        if ((strlen($texto) % 2) <> 0) $texto = '0' . $texto;

        while (strlen($texto) > 0) {
            $i = round(substr($texto, 0, 2));
            $texto = substr($texto, strlen($texto) - (strlen($texto) - 2), (strlen($texto) - 2));

            if (isset($barcodes[$i])) $f = $barcodes[$i];

            for ($i = 1; $i < 11; $i += 2) {
                if (substr($f, ($i - 1), 1) == '0') $f1 = $fino;
                else $f1 = $largo;

                $code .= 'src="./assets/images/barcode/p.gif" width="' . $f1 . '" height="' . $altura . '" border="0">';
                $code .= '<img ';

                if (substr($f, $i, 1) == '0') $f2 = $fino;
                else $f2 = $largo;

                $code .= 'src="./assets/images/barcode/b.gif" width="' . $f2 . '" height="' . $altura . '" border="0">';
                $code .= '<img ';
            }
        }
        $code .= 'src="./assets/images/barcode/p.gif" width="' . $largo . '" height="' . $altura . '" border="0" />';
        $code .= '<img src="./assets/images/barcode/b.gif" width="' . $fino . '" height="' . $altura . '" border="0" />';
        $code .= '<img src="./assets/images/barcode/p.gif" width="1" height="' . $altura . '" border="0" />';

        return $code;
    }

    public function getTagsPlp()
    {
        ob_start();
        $number_plp  = $this->postClean('number_plp');
        $integration = $this->postClean('integration');

        $dataPlp = $this->model_freights->getTagsPlpActive($number_plp);
        $plp = array();
        $store = null;
        $pathFileGroup = null;
        $labels = array();
        $logistics_loaded = null;
        $logistics_loaded_current = false;

        foreach ($dataPlp as $item) {
            $integration_logistic = $item['integration_logistic'];

            if ($store === null) {
                $store = $this->model_stores->getStoresData($item['store_id']);
                $logisticStore = $this->calculofrete->getLogisticStore(array(
                    'freight_seller' 		=> $store['freight_seller'],
                    'freight_seller_type' 	=> $store['freight_seller_type'],
                    'store_id'				=> $store['id']
                ));
            }

            if ($logistics_loaded != $logisticStore['type'] || !$logistics_loaded_current) {
                $logistics_loaded = $logisticStore['type'];
                $logistics_loaded_current = true;
                try {
                    $this->calculofrete->instanceLogistic($logisticStore['type'], $store['id'], $store, $logisticStore['seller']);
                } catch (InvalidArgumentException $exception) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode([
                            'message' => $exception->getMessage()
                        ]));
                }
            }

            if ($integration == $integration_logistic && $logisticStore['type'] != $integration_logistic) {
                try {
                    $logistics_loaded_current = false;
                    $this->calculofrete->instanceLogistic($integration_logistic, $store['id'], $store, false);
                    // Usar as credenciais do marketplace.
                    $this->calculofrete->logistic->setCredentialsSellerCenter();
                } catch (InvalidArgumentException | Exception $exception) {
                    // Se falhou para definir as credenciais do marketplace, tenta pelo seller.
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(array(
                            'message'   => $exception->getMessage()
                        )));
                }
            }

            // Deixar por uma semana, até ajustar as etiquetas dos pedidos com problemas.
            // Implementado 03/05/22.
            $pathFile = str_replace(base_url(), FCPATH, $item['link_etiqueta_a4']);
            if (!file_exists($pathFile)) {
                $this->calculofrete->logistic->saveLabel($item['codigo_rastreio'], $item['order_id']);
            } else {
                if (filesize($pathFile) < 2000) {
                    unlink($pathFile);
                    $this->calculofrete->logistic->saveLabel($item['codigo_rastreio'], $item['order_id']);
                }
            }

            if ($pathFileGroup === null) {
                $pathFileGroup = str_replace(base_url(), FCPATH, $item['link_etiquetas_a4']);
            }

            $labels[] = $item['codigo_rastreio'];

            if (!key_exists($item['order_id'], $plp)) {
                $plp[$item['order_id']] = array(
                    'codigo_rastreio'       => "<pre>{$item['codigo_rastreio']}</pre>",
                    'order_id'              => $item['order_id'],
                    'link_etiqueta_a4'      => $item['link_etiqueta_a4'],
                    'link_etiqueta_termica' => $item['link_etiqueta_termica'],
                    'link_etiquetas_a4'     => $item['link_etiquetas_a4'],
                    'link_etiquetas_termica'=> $item['link_etiquetas_termica'],
                    'codigo_rastreio_zpl'   => $item['codigo_rastreio'],
                    'date_expiration'       => dateFormat($item['date_expiration'], DATE_BRAZIL)
                );
            } else {
                $plp[$item['order_id']]['codigo_rastreio'] .= "<pre>{$item['codigo_rastreio']}</pre>";
                $plp[$item['order_id']]['codigo_rastreio_zpl'] .= ",{$item['codigo_rastreio']}";
            }
        }

        $expLabels = implode('|', $labels);
        if (!file_exists($pathFileGroup)) {
            $this->calculofrete->logistic->saveLabelGroup($expLabels, $number_plp);
        } else {
            // menor que 2kb
            if (filesize($pathFileGroup) < 2000) {
                unlink($pathFileGroup);
                $this->calculofrete->logistic->saveLabelGroup($expLabels, $number_plp);
            }
        }

        ob_clean();
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($plp));
    }

	public function registerFreight()
	{
        ob_start();
	    // if(!in_array('updateOrder', $this->permission)) {
        if (!in_array('createTrackingOrder', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    $response = array();
		$this->form_validation->set_rules('ship_company', $this->lang->line('application_ship_company'), 'trim|required');
	    $this->form_validation->set_rules('price_ship', $this->lang->line('application_price'), 'trim|required');
	    $this->form_validation->set_rules('tracking_code', $this->lang->line('application_tracking code'), 'trim|required');
		$this->form_validation->set_rules('method', $this->lang->line('application_service'), 'trim|required');
		$this->form_validation->set_rules('expected_date', $this->lang->line('application_expected_date'), 'trim|required');
        $this->form_validation->set_rules('url_tracking', $this->lang->line('application_url_tracking'), 'trim');
	    $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

	    if ($this->form_validation->run() == TRUE) {
	        // devolvo o estoque do pedido
	        $order_id= $this->postClean('id_register_freight');
			ob_clean();
	        $itens = $this->model_orders->getOrdersItemData($order_id);

            $ship_company = $this->model_shipping_company->getShippingCompany($this->postClean('ship_company'));

			$order = $this->model_orders->getOrdersData(0, $order_id);

			$sgp = 3; // default para transportadoras
			if (array_key_exists('CNPJ', $ship_company)) {
                if  ($ship_company['CNPJ']== '34028316000103' ) { // CNPJ dos Correios
                    $sgp = 1;
                }
            }
			$datetmp = DateTime::createFromFormat('d/m/Y', $this->postClean('expected_date'));
	        $data = array(
	            'order_id' => $order_id,
	            'item_id' => $itens[0]['id'],
	            'company_id' => $itens[0]['company_id'],
	            'ship_company' => $ship_company['name'],
	            'status_ship' => 1,
	            'date_delivered' => '',
	            'ship_value' => $this->postClean('price_ship'),
	            'prazoprevisto' => $datetmp->format('Y-m-d'),
	            'idservico' => '62405',
	            'codigo_rastreio' => $this->postClean('tracking_code'),
	            'link_etiqueta_a4' => '',
	            'link_etiqueta_termica' => '',
	            'link_plp' => '',
	            'data_etiqueta' => date("Y-m-d H:i:s"),
	            'CNPJ' => $ship_company['cnpj'],
	            'method' => $this->postClean('method'),
	            'cte' => null,
	            'sgp' => $sgp,
	            'solicitou_plp' => 0,
                'history_update' => null,
                'in_resend_active' => $order['in_resend_active'],
                'url_tracking' => (empty($this->postClean('url_tracking', true)) ? null : $this->postClean('url_tracking', true))
	        );

	        $this->model_freights->create($data);

	        $resp = $this->model_orders->updatePaidStatus($order_id, $order['in_resend_active'] ? 53 : 51);  // vai para o status de enviar para os marketplaces

            $data_to_order = [
                'ship_company_preview' => ($ship_company['name'] == 'CORREIOS' ? 'CORREIOS' : 'TRANSPORTADORA'),
            ];

            $this->model_orders->updateByOrigin($order_id, $data_to_order);  // atualiza a tabela orders para Correios ou Transportadora


	        $response['success'] = true;
	        $response['messages'] = $this->lang->line('messages_successfully_created');
	        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
	        // redirect('orders/semfrete', 'refresh');
	    }
	    else {
	        $response['success'] = false;
	        foreach ($_POST as $key => $value) {
	            $response['messages'][$key] = form_error($key);
	        }
	    }
		//header('Content-type:application/json;charset=utf-8');
		ob_clean();
		echo json_encode($response);
    }

    public function postxml()
    {
        $order = $this->postClean('order');

        if ($_FILES['fileNfXml']['type'] != 'text/xml') {
            $this->session->set_flashdata('error', $this->lang->line('messages_invalid_file_format'));
            redirect("orders/update/$order", 'refresh');
        }

        $xml   = new SimpleXMLElement(file_get_contents($_FILES['fileNfXml']['tmp_name']));

        $infNFe = $xml->NFe->infNFe ?? $xml->infNFe ?? $xml->Emitente->Nota->nfeProc->NFe->infNFe;

        $ide   = (array)$infNFe->ide;
        $tot   = (array)$infNFe->total->ICMSTot;
        $attr  = (array)$infNFe->attributes();

        $data = array(
            'sub_id'         => 0,
            'order_id'       => $order,
            'item_id'        => 0,
            'company_id'     => $this->postClean('company_id'),
            'date_emission'  => date('d/m/Y H:i:s', strtotime($ide['dhEmi'])),
            'nfe_value'      => $tot['vNF'],
            'nfe_serie'      => $ide['serie'],
            'nfe_num'        => $ide['nNF'],
            'chave'          => substr($attr['@attributes']['Id'], 3),
            'request_cancel' => 0,
        );

        $save = $this->model_orders->saveNfXml($data);

        if ($save) {
            // $save == 'inserted' ? $this->model_orders->updatePaidStatus($order, '50') : ''; // era 50 antes mas agora é 52 e manda a nota fiscal antes de contratar o frete
            $save == 'inserted' ? $this->model_orders->updatePaidStatus($order, '52') : '';
            $this->session->set_flashdata('success', $this->lang->line('messages_invoice_successfully_inserted'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_when_importing_invoice'));
        }

        $this->log_data(__CLASS__, __FUNCTION__, 'form_data_xml='.json_encode($infNFe), "I");

        redirect("orders/update/$order", 'refresh');
    }

    public function generatePdfEtiquetas($arrObjetosAll, $dataStore, $credentials = null): bool
    {
        if (is_null($credentials)) {
            $logistic = $this->calculofrete->getLogisticStore(array(
                'freight_seller' => $dataStore['freight_seller'],
                'freight_seller_type' => $dataStore['freight_seller_type'],
                'store_id' => $dataStore['id']
            ));

            $credentials = $this->calculofrete->getCredentialsRequest($dataStore['id']);
        }

        $pathEtiquetas = $this->pathServer("etiquetas");

        if (count($arrObjetosAll) == 0) {
            return false;
        }

        $arrObjetos = array();
        foreach ($arrObjetosAll as $plp => $itens) {
            if (!array_key_exists($plp, $arrObjetos)) {
                $arrObjetos[$plp] = array();
            }

            foreach ($itens as $item) {
                $arrObjetos[$plp][] = $item['code_tracking'];
            }
        }

        foreach ($arrObjetos as $plp => $objeto) {

            // Transforma o array em string dividos por pipe
            $expCorreios = implode("|", $objeto);

            // Salvar pdf de etiqueta A4 e etiqueta térmica
            // pdfUnitario | true = términa , false = A4
            //            $getEtiquetaA4 = "http://177.70.27.232/sgp_login/v/2.1/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$expCorreios}&ordem=id&pdfUnitario=false";
            //            $getEtiquetaTermica = "http://177.70.27.232/sgp_login/v/2.1/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$expCorreios}&ordem=id&pdfUnitario=true";
            $getEtiquetaA4 = "https://gestaodeenvios.com.br/sgp_login/v/2.2/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$expCorreios}&ordem=id&pdfUnitario=false";
            $getEtiquetaTermica = "https://gestaodeenvios.com.br/sgp_login/v/2.2/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$expCorreios}&ordem=id&pdfUnitario=true";

            if (array_key_exists('type_integration', $credentials)) {
                if ($credentials['type_integration'] === 'sgpweb') {
                    $getEtiquetaA4 = "https://www.sgpweb.com.br/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$expCorreios}&ordem=id&pdfUnitario=false";
                    $getEtiquetaTermica = "https://www.sgpweb.com.br/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$expCorreios}&ordem=id&pdfUnitario=true";        
                }
            }

            copy($getEtiquetaA4, FCPATH . $pathEtiquetas . "P_T_{$plp}_A4.pdf");
            copy($getEtiquetaTermica, FCPATH . $pathEtiquetas . "P_T_{$plp}_Termica.pdf");

            $etiquetaA4 = base_url() . $pathEtiquetas . "P_T_{$plp}_A4.pdf";
            $etiquetaTermica = base_url() . $pathEtiquetas . "P_T_{$plp}_Termica.pdf";

            $data = [
                'link_etiquetas_a4' => $etiquetaA4,
                'link_etiquetas_termica' => $etiquetaTermica
            ];

            $this->model_freights->updatePlpAfterUpload($plp, $data);
        }

        return true;
    }

    public function pathServer($folder)
    {
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath, 'assets');
        $serverpath = substr($serverpath, 0, $pos);
        $targetDir = $serverpath . 'assets/images/' . $folder . '/';
        if (!file_exists($targetDir)) {
            // cria o diretorio para receber as etiquetas
            @mkdir($targetDir);
        }
        return $targetDir;
    }

    public function order_in_progress()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['sellercenter_name']=$this->model_settings->getSettingDatabyName('sellercenter_name')["value"];
        //$ocorrencias = $this->model_frete_ocorrencias->getAllNomeOcorrencia();

        //$this->data['ocorrencias'] = $ocorrencias;

        $this->render_template('orders/order_in_progress', $this->data);
    }

    public function fetchOrdersInProgress()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        //$this->log_data('Orders','postdata',print_r($ini+1,true));
        $busca = $postdata['search'];
        $this->data['sellercenter_name']=$this->model_settings->getSettingDatabyName('sellercenter_name')["value"];
        $procura = "";
        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '', $busca['value']);
            if (strlen($busca['value']) >= 2)  // Garantir no minimo 3 letras
                $procura .= " 
                AND (orders.id like '%" . $busca['value'] . "%' 
				OR orders.id like '%" . $busca['value'] . "%' 
                OR orders.customer_name like '%" . $busca['value'] . "%'  
                OR orders_item.name like '%" . $busca['value'] . "%' 
                OR freights.codigo_rastreio like '%" . $busca['value'] . "%' 
                OR freights.ship_company like '%" . $busca['value'] . "%' 
                OR freights.updated_date like '%" . $busca['value'] . "%' 
                OR orders.origin like '%" . $busca['value'] . "%' 
                OR orders.numero_marketplace like '%" . $busca['value'] . "%' 
                OR freights.prazoprevisto like '%" . $busca['value'] . "%' 
				)";
        }
        // Filtro por marketplace
        if ($this->data['sellercenter_name'] != 'RaiaDrogasil') {
            if ($postdata['marketplace'] != "") $procura .= " AND orders.origin = '{$postdata['marketplace']}' ";
        }
        //        if ($postdata['ocorrencia'] != "") $procura .= " AND frete_ocorrencias.nome = '{$postdata['ocorrencia']}' ";

        $this->data['ordersfilter'] = $procura;

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") $direcao = "asc";
            else $direcao = "desc";

            $campos = array(
                'orders.id',
                'orders.customer_name',
                'orders_item.name',
                'freights.codigo_rastreio',
                'freights.ship_company',
                //'frete_ocorrencias.nome',
                'freights.updated_date',
                '',
                //'frete_ocorrencias.data_ocorrencia',
                'orders.origin',
                'orders.numero_marketplace',
                'freights.prazoprevisto',
                'orders.id'
            );
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo == 'id') { // inverto no caso do ID
                if ($postdata['order'][0]['dir'] == "asc") $direcao = "desc";
                else $direcao = "asc";
            }
            if ($campo != "") $sOrder = "{$campo} {$direcao}";
        }

        $data = $this->model_orders->getOrdersInProgress($ini, $sOrder);
        $result = array();
        $resultTemp = array();
        foreach ($data as $value) {
            $codOrder       = $value['id'];
            $nomeCliente    = $value['customer_name'];
            $nomeProduto    = $value['prod_name'];
            $codRastreio    = $value['codigo_rastreio'];
            $empresaEnvio   = $value['ship_company'];
            //$codOcorrencia  = (int)$value['ocorrencia'];
            //$dataOcorrencia = $value['data_ocorrencia'] ? date('d/m/Y H:i', strtotime($value['data_ocorrencia'])) : "";
            $foiAlterado    = $value['comments_adm'] == null ? "Não" : "Sim";
            $mktPlace       = $value['origin'];
            $numMktPlace    = $value['numero_marketplace'];
            $prazoPrevisto  = $value['prazoprevisto'] ? date('d/m/Y', strtotime($value['prazoprevisto'])) : "";
            $botao          = in_array('doIntegration', $this->permission) ? '<button type="button" class="btn btn-default edit-tracking" order-id="' . $codOrder . '"><i class="fa fa-edit"></i></button>' : '';

            if (array_key_exists($codOrder, $resultTemp)) {

                if (!in_array($nomeProduto, $resultTemp[$codOrder][2])) array_push($resultTemp[$codOrder][2], $nomeProduto);
                if (!in_array($codRastreio, $resultTemp[$codOrder][3])) array_push($resultTemp[$codOrder][3], $codRastreio);

                //$resultTemp[$codOrder][5] = $codOcorrencia;
            } else
                $resultTemp[$codOrder] = array(
                    $codOrder,
                    $nomeCliente,
                    [$nomeProduto],
                    [$codRastreio],
                    $empresaEnvio,
                    //$codOcorrencia,
                    //$dataOcorrencia,
                    $foiAlterado,
                    $mktPlace,
                    $numMktPlace,
                    $prazoPrevisto,
                    $botao
                );
        }

        // alinhando os pedidos
        $i = 0;
        foreach ($resultTemp as $order) {

            //            $resultOcorrencia = $this->model_frete_ocorrencias->getFreteOcorrenciasData($order[5]);
            //            $ultimaOcorrencia = isset($resultOcorrencia['nome']) ? $resultOcorrencia['nome'] : "";
            //
            //            if ($postdata['ocorrencia'] != "") {
            //                if($postdata['ocorrencia'] != $ultimaOcorrencia) continue;
            //            }

            $result[$i] = array(
                $order[0],
                $order[1],
                ' -' . implode('<br> -', $order[2]),
                implode('<br>', $order[3]),
                $order[4],
                //                $ultimaOcorrencia,
                //                $order[6],
                $order[5],
                $order[6],
                $order[7],
                $order[8],
                $order[9]
            );

            $i++;
        }

        $filtered = $this->model_orders->getOrdersInProgressCount($this->data['ordersfilter']);
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_orders->getOrdersInProgressCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        //$this->log_data('Orders','result',print_r($output,true));
        //ob_clean();
        echo json_encode($output);
    }

    public function getDataTrackingOrderInProgress()
    {
        ob_start();
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $order  = (int)$this->postClean('order');

        $dataEtiqueta = $this->model_freights->getDataFreightsOrderId($order, false);
        $etiqueta = array();
        $dataOrder = $this->model_orders->getOrdersData(0, $order);

        foreach ($dataEtiqueta as $key => $item) {

            $emMovimentacao = false;
            $ocorrencias = $this->model_frete_ocorrencias->getFreteOcorrenciasDataByFreightsId($item['id']);
            if ($ocorrencias) $emMovimentacao = true;

            $etiqueta[$key] = array(
                'codigo_rastreio'   => $item['codigo_rastreio'],
                'order_id'          => $item['order_id'],
                'url_post'          => base_url('orders/updateCodeTrackingOrder'),
                'comments_adm'      => $item['comments_adm'] == null ? array() : array_reverse(json_decode($item['comments_adm'])),
                'history_update'    => $item['history_update'] == null ? array() : array_reverse(json_decode($item['history_update'])),
                'em_movimentacao'   => $emMovimentacao
            );
        }
        if (!count($dataEtiqueta)) {
            $etiqueta[0] = array(
                'codigo_rastreio'   => false,
                'comments_adm'      => $dataOrder['comments_adm'] == null ? array() : array_reverse(json_decode($dataOrder['comments_adm'])),
                'order_id'          => $order
            );
        }

        ob_clean();
        echo json_encode($etiqueta);
    }

    public function updateCodeTrackingOrder()
    {
        $pathEtiquetas      = $this->pathServer("etiquetas");
        $codeTrackingNew    = trim($this->postClean('code-tracking-new'));
        $codeTrackingReal   = trim($this->postClean('code-tracking-real'));
        $orderId            = (int)$this->postClean('order-id');

        $orderDb = $this->model_orders->verifyOrderOfStore($orderId);
        if (!$orderDb) {
            $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
            redirect('orders/order_in_progress', 'refresh');
        }

        $dataStore = $this->model_stores->getStoresData($orderDb['store_id']);
        $logistic = $this->calculofrete->getLogisticStore(array(
            'freight_seller' 		=> $dataStore['freight_seller'],
            'freight_seller_type' 	=> $dataStore['freight_seller_type'],
            'store_id'				=> $dataStore['id']
        ));

        $arrHistory = array();
        $sendHistory = "";
        $user_id    = $this->session->userdata('id');

        $user       = $this->model_users->getUserData($user_id);
        $userName   = $user['username'];
        $email      = $user['email'];

        $dataFreight = $this->model_freights->getFreightForCodeTracking($orderId, $codeTrackingReal);

        if ($dataFreight['history_update']) {
            $arrHistory = json_decode($dataFreight['history_update']);
        }

        array_push($arrHistory, array(
            'order_id'          => $orderId,
            'codigo_anterior'   => $codeTrackingReal,
            'codigo_novo'       => $codeTrackingNew,
            'user_id'           => $user_id,
            'user_name'         => $userName,
            'email'             => $email,
            'date'              => date('Y-m-d H:i:s')
        ));

        $sendHistory  = json_encode($arrHistory);

        $update = $this->model_freights->updateCodeTracking($orderId, $codeTrackingReal, $codeTrackingNew, $sendHistory);

        if ($logistic['type'] === 'sgpweb') {

            $credentials = $this->calculofrete->getCredentialsRequest($dataStore['id']);

            // Salvar pdf de etiqueta A4 e etiqueta térmica
            // pdfUnitario | true = términa , false = A4
            //        $getEtiquetaA4 = "http://177.70.27.232/sgp_login/v/2.1/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$codeTrackingNew}&ordem=id&pdfUnitario=false";
            //        $getEtiquetaTermica = "http://177.70.27.232/sgp_login/v/2.1/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$codeTrackingNew}&ordem=id&pdfUnitario=true";
            $getEtiquetaA4 = "https://gestaodeenvios.com.br/sgp_login/v/2.2/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$codeTrackingNew}&ordem=id&pdfUnitario=false";
            $getEtiquetaTermica = "https://gestaodeenvios.com.br/sgp_login/v/2.2/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$codeTrackingNew}&ordem=id&pdfUnitario=true";

            if (array_key_exists('type_integration', $credentials)) {
                if ($credentials['type_integration'] === 'sgpweb') {
                    $getEtiquetaA4 = "https://sgpweb.com.br/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$codeTrackingNew}&ordem=id&pdfUnitario=false";
                    $getEtiquetaTermica = "https://sgpweb.com.br/webservice.php?opcao=pdf&key={$credentials['token']}&postObjeto={$codeTrackingNew}&ordem=id&pdfUnitario=true";
                }
            }
            copy($getEtiquetaA4, FCPATH . $pathEtiquetas . "P_{$orderId}_A4.pdf");
            copy($getEtiquetaTermica, FCPATH . $pathEtiquetas . "P_{$orderId}_Termica.pdf");
        }

        if ($update) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            redirect('orders/order_in_progress', 'refresh');
        } else {
            $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
            redirect('orders/order_in_progress', 'refresh');
        }
    }

    public function createCommentOrderInProgress()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $arrComment = array();
        $sendComment = "";
        $order_id   = $this->postClean('order_id_comment');
        $comment    = $this->postClean('comment');
        $user_id    = $this->session->userdata('id');

        $user = $this->model_users->getUserData($user_id);
        $userName = $user['username'];

        $dataOrder = $this->model_orders->getOrdersData(0, $order_id);

        if ($dataOrder['comments_adm']) {
            $arrComment = json_decode($dataOrder['comments_adm']);
        }

        array_push($arrComment, array(
            'order_id'  => $order_id,
            'comment'   => $comment,
            'user_id'   => $user_id,
            'user_name' => $userName,
            'date'      => date('Y-m-d H:i:s')
        ));

        $sendComment  = json_encode($arrComment);

        $create = $this->model_orders->createCommentOrderInProgress($sendComment, $order_id);

        if ($create) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            redirect('orders/order_in_progress', 'refresh');
        } else {
            $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
            redirect('orders/order_in_progress', 'refresh');
        }
    }

    public function getZplEtiqueta()
    {
        ob_start();
        $zpl         = array();
        $order_id    = (int)$this->postClean('order');
        $trackins    = $this->postClean('trackins');
        $arrTrackins = explode(",", $trackins);

        $zpls = $this->getStrZplByTrackinAndOrder($arrTrackins, $order_id);

        foreach ($arrTrackins as $trackin) {
            array_push($zpl, array("{$this->lang->line('application_tracking code')}: {$trackin}", $zpls[$trackin]));
        }

        if (count($zpl) == 0) {
            ob_clean();
            echo json_encode(array(false, "Não foi encontrada nenhuma ZPL"));
            exit();
        }

        ob_clean();
        echo json_encode(array(true, $zpl));
    }

    public function executaGetWeb($url, $post_data)
    {

        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "conectala", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120000,      // timeout on connect
            CURLOPT_TIMEOUT        => 120000,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_POST        => true,
            CURLOPT_POSTFIELDS    => $post_data,
            CURLOPT_HTTPHEADER =>  array('Content-Type:application/json'),
            CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
        );
        $ch      = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err     = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $header  = curl_getinfo($ch);
        curl_close($ch);
        $header['httpcode']   = $httpcode;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }

    public function registerExchange()
    {
        ob_start();
        if (!in_array('admDashboard', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $response = array();
        $this->form_validation->set_rules('original_order', $this->lang->line('application_original_order'), 'trim|required');
        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {
            // devolvo o estoque do pedido
            $id = $this->postClean('id_register_exchange');
            $order = $this->model_orders->getOrdersData(0, $id);
            $order_origin = $this->model_orders->getOrdersData(0, $this->postClean('original_order'));
            if ($order_origin) {
                $order['original_order_marketplace'] = $this->postClean('original_order');
                $this->model_orders->updateByOrigin($id, $order);
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_updated');
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            }
            // else{
            //     $response['success'] = false;
            //     $response['messages'] = $this->lang->line('messages_error_occurred');
            //     $this->session->set_flashdata('success', $this->lang->line('messages_error_occurred')." ". $this->lang->line('messages_invalid_order'));
            // }


            // redirect('orders/semfrete', 'refresh');
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        ob_clean();
        echo json_encode($response);
    }

    public function checkTypeExhange($field)
    {
        /*if ($field != $this->lang->line('application_exchange_capital_letters')) {
            $this->form_validation->set_message('checkTypeExhange',  $this->lang->line('application_type_exchange'));
            return FALSE;
        }*/
        return true;
    }

    public function newExchange()
    {
        ob_start();
        if (!in_array('admDashboard', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $response = array();
        $this->form_validation->set_rules('confirm_new_exchange', $this->lang->line('application_type_exchange'), 'trim|required|callback_checkTypeExhange');
        if ($this->form_validation->run() == TRUE) {
            $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');
            $order_id = $this->postClean('id');
            $itens = $this->postClean('itens');
            if (count($itens) == 0) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            } else {
                if ($this->postClean('all') == 'true') {
                    $new_order_id = $this->model_orders->createExchangeOrder($order_id, $itens);
                } else {
                    $this->db->trans_begin();
                    $new_order_id = $this->createNewExchange($order_id, $itens, $this);
                    $this->db->trans_commit();
                }
            }
            // $new_order_id = $this->model_orders->createExchangeOrder($order_id,$itens);

            $response['success'] = true;
            $response['order_id'] = $new_order_id;
            $response['messages'] = $this->lang->line('messages_successfully_updated');
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            // redirect('orders/update/'.$new_order_id, 'refresh');
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        ob_clean();
        echo json_encode($response);
    }
    public function createNewExchange($order_id, $order_itens)
    {
        $array_itens_to_exchenge = [];
        $array_itens_not_exchenge = [];
        foreach ($order_itens as $key => $order_item) {
            $orders_its = $this->model_orders_item->getItensByOrderId($order_id);
            foreach ($orders_its as $order_it) {
                if ($order_it['sku'] == $order_item['sku'] && $order_it['id'] == $order_item['id']) {
                    if (intval($order_it['qty']) != intval($order_item['qty'])) {
                        unset($order_it['id']);
                        $order_it_to_exchenge = $order_it;
                        $order_it_to_exchenge['qty'] = intval($order_item['qty']);
                        array_push($array_itens_to_exchenge, $order_it_to_exchenge);
                        $array_it_not_exchenge = $order_it;
                        if (intval($order_it['qty']) - intval($order_item['qty']) <= 0) {
                            throw new Exception("Quantidade do pedido incompativel com a solicitação de troca.");
                        }
                        $array_it_not_exchenge['qty'] = intval($order_it['qty']) - $order_item['qty'];
                        array_push($array_itens_not_exchenge, $array_it_not_exchenge);
                    } else {
                        unset($order_it['id']);
                        array_push($array_itens_to_exchenge, $order_it);
                    }
                }
            }
        }

        $order = $this->model_orders->getOrdersData(0, $order_id);
        $new_order_exchenge = $order;
        unset($new_order_exchenge['id']);
        $frete_exchenge = 0;
        $net_amount_exchenge = 0;
        $gross_amount_exchenge = 0;
        foreach ($array_itens_to_exchenge as $itens_to_exchenge) {
            $gross_amount_l_exchenge = floatval($itens_to_exchenge['amount']) * floatval($itens_to_exchenge['qty']);
            $net_amount_exchenge += $gross_amount_l_exchenge - floatval($itens_to_exchenge['discount']);
            $gross_amount_exchenge += $gross_amount_l_exchenge;
            $total_order = $gross_amount_exchenge;
            $gross_amount_exchenge += $frete_exchenge;
        }

        $service_charge = $gross_amount_exchenge * floatval($order['service_charge_rate']) / 100;
        $new_order_exchenge['bill_no'] = "TROCA-" . $new_order_exchenge['bill_no'];
        $new_order_exchenge['numero_marketplace'] = "TROCA-" . $new_order_exchenge['numero_marketplace'];
        $new_order_exchenge['date_time'] = date('Y-m-d');
        $new_order_exchenge['net_amount'] = $net_amount_exchenge;
        $new_order_exchenge['gross_amount'] = $gross_amount_exchenge;
        $new_order_exchenge['service_charge'] = $service_charge;
        $new_order_exchenge['total_order'] = $gross_amount_exchenge;
        $new_order_exchenge['order_delayed_post'] = 0;
        $new_order_exchenge['exchange_request'] = 2;
        $new_order_exchenge['data_pago'] = date('Y-m-d H:i:s');
        $new_order_exchenge['data_limite_cross_docking'] = get_instance()->somar_dias_uteis(date("Y-m-d"), 5, '');
        $new_order_exchenge['paid_status'] = 3;
        $new_order_exchenge['original_order_marketplace'] = $order_id;

        $new_order_not_exchenge = $order;
        unset($new_order_not_exchenge['id']);
        $frete_not_exchange = 0;
        $net_amount_not_exchange = 0;
        $gross_amount_not_exchange = 0;
        foreach ($array_itens_not_exchenge as $itens_to_exchenge) {
            $gross_amount_l_not_exchange = floatval($itens_to_exchenge['amount']) * floatval($itens_to_exchenge['qty']);
            $net_amount_not_exchange += $gross_amount_l_not_exchange - floatval($itens_to_exchenge['discount']);
            $gross_amount_not_exchange += $gross_amount_l_not_exchange;
            $total_order = $gross_amount_not_exchange;
            $gross_amount_not_exchange += $frete_not_exchange;
        }
        // $service_charge = $gross_amount_not_exchange * floatval($order['service_charge_rate']) / 100;
        // $new_order_not_exchenge['bill_no']="".$new_order_not_exchenge['bill_no'];
        // $new_order_not_exchenge['numero_marketplace']="".$new_order_not_exchenge['numero_marketplace'];
        // $new_order_not_exchenge['date_time']= date('Y-m-d');
        // $new_order_not_exchenge['net_amount']= $net_amount_not_exchange;
        // $new_order_not_exchenge['gross_amount']= $gross_amount_not_exchange;
        // $new_order_not_exchenge['service_charge']= $service_charge;
        // $new_order_not_exchenge['total_order']= $gross_amount_not_exchange;
        // $new_order_not_exchenge['order_delayed_post']= 0;
        // $new_order_not_exchenge['data_pago']= date('Y-m-d H:i:s');
        // $new_order_not_exchenge['data_limite_cross_docking']= get_instance()->somar_dias_uteis(date("Y-m-d"),5,'');

        $new_order_exchenge_id = $this->model_orders->insertOrder($new_order_exchenge);
        foreach ($array_itens_to_exchenge as $itens_to_exchenge) {
            $itens_to_exchenge['order_id'] = $new_order_exchenge_id;
            $this->model_orders->insertItem($itens_to_exchenge);
        }
        get_instance()->log_data('Orders', 'update', json_encode($new_order_exchenge), "I");
        // $this->model_orders->updateOrderData($order_id, $new_order_not_exchenge);
        // $this->model_orders->deleteItemByOrderId($order_id);
        // $new_order_not_exchenge_id = $order_id;
        // foreach($array_itens_not_exchenge as $itens_to_not_exchenge){
        //     $itens_to_not_exchenge['order_id'] = $new_order_not_exchenge_id;
        //     $this->model_orders->insertItem($itens_to_not_exchenge);
        // }
        // get_instance()->log_data('Orders','create',json_encode($new_order_not_exchenge),"I");
        $this->db->trans_commit();
        return $new_order_exchenge_id;
    }

    public function removeFreightOrder()
    {
        $order_id = (int)$this->postClean('order_id');

        $this->db->trans_begin();

        $dataFreight = $this->model_freights->getFreightsDataByOrderId($order_id);
        $codeTracking = array();
        foreach ($dataFreight as $freight)
            array_push($codeTracking, $freight['codigo_rastreio']);

        // remove pedido do correios_plps
        $delOrderPlp = $this->model_freights->removeOrderPlp($order_id);
        if (!$delOrderPlp) {
            $this->db->trans_rollback();
            echo json_encode(array('success' => false, 'message' => "Ocorreu um problema para excluir o pedido da PLP!"));
            exit();
        }
        // remove pedido do freights
        $delFreight = $this->model_freights->removeForOrderId($order_id);
        if (!$delFreight) {
            $this->db->trans_rollback();
            echo json_encode(array('success' => false, 'message' => "Ocorreu um problema para excluir o rastreio do pedido!"));
            exit();
        }
        // atualizar pedido para status 50
        $updateStatus = $this->model_orders->updatePaidStatus($order_id, 50);
        if (!$updateStatus) {
            $this->db->trans_rollback();
            echo json_encode(array('success' => false, 'message' => "Ocorreu um problema para atualizar o status do pedido!"));
            exit();
        }

        $arrComment = array();
        $sendComment = "";
        $comment    = 'Etiqueta removida ( ' . implode('-', $codeTracking) . ' )';
        $user_id    = $this->session->userdata('id');

        $user = $this->model_users->getUserData($user_id);
        $userName = $user['username'];

        $dataOrder = $this->model_orders->getOrdersData(0, $order_id);

        if ($dataOrder['comments_adm'])
            $arrComment = json_decode($dataOrder['comments_adm']);

        array_push($arrComment, array(
            'order_id'  => $order_id,
            'comment'   => $comment,
            'user_id'   => $user_id,
            'user_name' => $userName,
            'date'      => date('Y-m-d H:i:s')
        ));

        $sendComment  = json_encode($arrComment);

        $createComment = $this->model_orders->createCommentOrderInProgress($sendComment, $order_id);

        if (!$createComment) {
            $this->db->trans_rollback();
            echo json_encode(array('success' => false, 'message' => "Ocorreu um problema para criar comentário no pedido!"));
            exit();
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            echo json_encode(array('success' => false, 'message' => "Ocorreu um problema para remover a etiqueta pedido!"));
            exit();
        }

        $this->db->trans_commit();
        $this->log_data('NFEs', 'Orders/removeFreightOrder', 'etiqueta do pedido ' . $order_id . ' exlcuida. user_id=' . $user_id, "I");
        echo json_encode(array('success' => true, 'message' => "Etiqueta removida com sucesso, dentro de 5 minutos será gerado uma nova!"));
        exit();
    }

    public function getStrZplByTrackinAndOrder($trackins, $order_id)
    {
        $zpls        = array();
        $dataOrder  = $this->model_orders->getOrdersData(0, $order_id);
        $dataStore  = $this->model_stores->getStoresData($dataOrder['store_id']);
        $dataNfe    = $this->model_nfes->getNfesDataByOrderId($order_id,true);

        if (!$dataOrder || !$dataStore || !$dataNfe || count($trackins) == 0 || $order_id == 0)
            return false;

        $dataNfe = $dataNfe[0];

        $credentialCorreios = $this->model_settings->getSettingDatabyName('credentials_correios');
        if (!$credentialCorreios || $credentialCorreios['status'] != 1)
            return false;

        $dataCorreios = json_decode($credentialCorreios['value']);

        foreach ($trackins as $trackin) {

            $dataFreight = $this->model_freights->getOrderIdForCodeTracking($trackin);
            $chancela = '';
            $method = '';

            if ($dataFreight['method'] == 'PAC') {
                $chancela = "^FO600,50^GFA,2337,2337,19,,::W01FF,V0KFC,T01MFE,T0OFC,S07PF8,R01QFE,R07RF8,Q01SFE,Q07TF8,Q0UFC,P03VF,P07VF8,O01WFE,O03XF,O07XF8,O0YFC,N01YFE,N03gF,N07gF8,N0gGFC,M01gGFE,M03gHF,M07gHF8,:M0gIFC,L01gIFE,L03gJF,:L07gJF8,:L0gKFC,K01gKFE,:K01gLF,K03gLF,K03gLF8,K07gLF8,:K0gMFC,::J01gMFE,::J01gNF,J03gNF,:::J03gNF8,J07gNF8,:::::::::::::::::J03gNF8,J03gNF,::J01gMFE,:::K0gMFC,::K07gLF8,:K03gLF8,K03gLF,:K01gKFE,:L0gKFC,L07gJF8,:L03gJF,:L01gIFE,M0gIFC,:M07gHF8,M03gHF,M01gGFE,N0gGFC,N07gF8,N03gF,N01YFE,O0YFC,O07XF8,O03XF,O01WFE,P07VF8,P03VF,P01UFC,Q07TF8,Q01SFE,R07RF8,R01QFE,S07PF8,T0OFC,T01MFE,V0KFE,W03FF,,::^FS";
            } elseif ($dataFreight['method'] == 'SEDEX') {
                $chancela = "^FO600,50^GFA,2565,2565,19,,::::W0JFC,V0LFE,U0NFE,T07OFC,S01QF,S0RFC,R03SF,R0TFC,Q01UF,Q07UFC,Q0VFE,P03WF,P07WFC,P0XFE,O01YF,O03YF8,O07YFC,O0gFE,N01gGF,N03gGF8,N07gGFC,N0gHFC,N0gHFE,M01gIF,M03gIF8,M07gIF8,M07gIFC,M0gJFE,:L01gKF,L03gKF,L03gKF8,:L07gKFC,:L0gLFE,:K01gMF,::K03gMF,K03gMF8,::K07gMF8,K07gMFC,::K0gNFC,:K0gNFE,::::::K0RFJ03QFE,K0PFEM07OFE,K0OFEO0OFE,K0NFEP01NFE,K0NF8Q03MFE,K0MFCS0MFE,K0MFT01LFE,K0LFCU07KFE,K0LFV01KFE,K0KFCW0KFE,K0KF8W03JFE,K0JFEY0JFE,K0JFCY07IFE,K0JFg01IFE,K0IFEgG0IFE,K0IFCgG07FFE,K0IFgH03FFE,K0FFEgI0FFE,K0FFCgI07FE,K0FF8gI03FE,K0FFgJ01FE,K0FEgJ01FE,K0FCgK07E,K0F8gK07E,K0FgL03E,K0FgL01E,K0EgM0E,K0CgM06,,::::::W07IFC,V03KF8,U01LFE,U07MF8,U0NFE,T03OF8,T07OFC,S01PFE,S03QF8,S07QF8,S0RFE,R01SF,R03SF,R03SF8,R07SFC,R0TFC,R0TFE,Q01UF,:Q03UF8,:Q07UFC,::Q0VFC,Q0VFE,::P01VFE,P01WF,:::Q0VFE,,:::^FS";
            }

            //--dados para gerar o DATAMATRIX
            $qrCode['CEP_destino'] = str_replace(array('-', '.'), '', $dataOrder['customer_address_zip']);       //--8 caracteres
            $qrCode['complemento_do_CEP'] = '00000'; //--5 caracteres
            $qrCode['CEP_Origem'] = str_replace(array('-', '.'), '', $dataStore['zipcode']);        //--8 caracteres
            $qrCode['complemento_do_CEP_origem'] = '00000';       //--5 caracteres
            $validador = 0;

            for ($i = 0; $i < strlen($qrCode['CEP_Origem']); $i++) {
                $qrCode['CEP_Origem'][$i] . " \n ";
                $validador = $validador + $qrCode['CEP_Origem'][$i];
            }
            $arr[] = 10;
            $arr[] = 20;
            $arr[] = 30;
            $arr[] = 40;
            $arr[] = 50;
            $arr[] = 60;
            $arr[] = 70;
            $arr[] = 80;
            $arr[] = 90;
            foreach ($arr as $key => $val) {
                if ($val >= $validador) {
                    $validadorCep = $val - $validador;
                    break;
                }
            }

            $qrCode['Validador_do_CEP_Destino'] = $validadorCep;       //--1 caracteres
            $qrCode['IDV'] = '51';       //--2 caracteres
            //--inicio dados variaveis
            $qrCodeDadosVariaveis['Etiqueta'] = $trackin;       //--13 caracteres

            $qrCodeDadosVariaveis['Servicos_Adicionais'] = '00000000'; //--8 caracteres   (AR, MP, DD, VD) Quando não possui o serviço adicional deverá serpreenchido com 00

            if ($dataFreight['method'] == 'SEDEX') $qrCodeDadosVariaveis['Servicos_Adicionais'] = '00019000';
            if ($dataFreight['method'] == 'PAC') $qrCodeDadosVariaveis['Servicos_Adicionais'] = '00064000';

            $qrCodeDadosVariaveis['Cartao_de_Postagem'] = $dataCorreios->cartao;       //--10 caracteres
            $qrCodeDadosVariaveis['Codigo_do_Servico'] = '00000';       //--5 caracteres
            $qrCodeDadosVariaveis['Informacao_de_Agrupamento'] = '00';       //--2 caracteres
            $qrCodeDadosVariaveis['Numero_do_Logradouro']      = str_pad(trim(substr($this->tirarAcentos($dataOrder['customer_address_num']), 0, 5)), 5, ' ', STR_PAD_LEFT);       //--5 caracteres
            $qrCodeDadosVariaveis['complemento_do_Logradouro'] = trim(substr(strtolower($dataOrder['customer_address_compl']), 0, 20));       //--20 caracteres
            $qrCodeDadosVariaveis['complemento_do_Logradouro'] = ltrim($qrCodeDadosVariaveis['complemento_do_Logradouro']);
            $qrCodeDadosVariaveis['complemento_do_Logradouro'] = str_replace('-', '', $qrCodeDadosVariaveis['complemento_do_Logradouro']);
            $qrCodeDadosVariaveis['complemento_do_Logradouro'] = str_pad($qrCodeDadosVariaveis['complemento_do_Logradouro'], 20, ' ', STR_PAD_LEFT);

            $qrCodeDadosVariaveis['Valor_Declarado'] = str_pad(str_replace(array(',', '.'), '', $dataOrder['total_order']), 5, '0', STR_PAD_LEFT);       //--5 caracteres
            // $qrCodeDadosVariaveis['Valor_Declarado']='00000'; //--5 caracteres
            $qrCodeDadosVariaveis['DDD_TelefoneDestinatário'] = '000000000000'; //--12 caracteres
            $qrCodeDadosVariaveis['latitude'] = '-00.000000'; //--10 caracteres
            $qrCodeDadosVariaveis['longitude'] = '-00.000000'; //--10 caracteres
            $qrCodeDadosVariaveis['pipe'] = '|'; //--1 caracteres
            $qrCodeDadosVariaveis['Reserva_para_cliente'] = str_pad('', 30, ' ', STR_PAD_LEFT); //--30 caracteres

            $qrCode['dados_variaveis '] = '';
            foreach ($qrCodeDadosVariaveis as $key => $val) $qrCode['dados_variaveis '] .= $val; //--131 caracteres

            $qrCodeString = '';
            foreach ($qrCode as $key => $val) $qrCodeString .= $val;

            $customerAddressFormattedStr = $this->tirarAcentos($dataOrder['customer_address']);
            // Limita o endereço destinatario para não cortar o número no fim do campo
            if (strlen($customerAddressFormattedStr) > 42) {
                $customerAddressFormattedStr = trim(substr($customerAddressFormattedStr, 0, 40)) . "...";
            }
            $customerAddressStr = $customerAddressFormattedStr . ", " . $dataOrder['customer_address_num'];
            $customerAddressComplStr = $this->tirarAcentos($dataOrder['customer_address_compl']);
            // Limita os campos para um máximo de chars para não sobrepor o código de barras
            $customerAddressNeighStr = substr($this->tirarAcentos($dataOrder['customer_address_neigh']), 0, 24);
            $customerAddressCityStr = substr($this->tirarAcentos($dataOrder['customer_address_city']), 0, 31);
            // Define configurações de fonte menores caso os campos ultrapassem certa quantidade de caracteres
            $customerAddressConfig = strlen($customerAddressStr) < 39 ? "^CF0,33 ^FO200,560" : "^CF0,28 ^FO200,563";
            $customerAddressComplConfig = strlen($customerAddressComplStr) < 34 ? "^CF0,33 ^FO160,600" : "^CF0,28 ^FO160,603";
            $customerAddressNeighConfig = strlen($customerAddressNeighStr) < 18 ? "^CF0,33 ^FO155,640" : "^CF0,28 ^FO155,643";
            $customerAddressCityConfig = strlen($customerAddressCityStr) < 26 ? "^CF0,33 ^FO60,680" : "^CF0,28 ^FO60,683";

            $addressFormattedStr = $this->tirarAcentos($dataStore['address']);
            // Limita o endereço do remetente para não cortar o número no fim do campo
            if (strlen($addressFormattedStr) > 32) {
                $addressFormattedStr = trim(substr($addressFormattedStr, 0, 30)) . "...";
            }
            $addressStr = $addressFormattedStr . ", " . $dataStore['addr_num'];

            $zpls[$trackin] =
                "^XA
^FX LINHA DE CIMA
^FO50,30^GB700,1,3^FS

{$chancela}

^FX LINHA DE CIMA DADOS CORREIO
^CFA,30
^FO40,100^FD {$dataFreight['method']} CONTRATO ^FS
^FO40,140^FD AGENCIA^FS
^CFA,20
^FO45,180^FD {$dataCorreios->contrato}/2016-DR/SPM ^FS

^FX LOGO - TOPO
^CF0,40 ^A0N^FO40,50^FD CONECTA LA ^FS

^FO360,40^BXN,5,200,0,0,1,_
^FD{$qrCodeString}^FS

^FX CODIGO DE BARRAS - RASTREIO
^BY3,3,200
^FO72,255^BC^FD{$trackin}^FS

^FX DADOS DESTINATÁRIO
^CF0,40 ^A0N^FO60,510^FD{$this->tirarAcentos($dataOrder['customer_name'])}^FS
^CF0,33 ^FO60,560^FDEndereco:^FS {$customerAddressConfig}^FD{$customerAddressStr}^FS
^CF0,33 ^FO60,600^FDCompl:^FS {$customerAddressComplConfig}^FD{$customerAddressComplStr}^FS
^CF0,33 ^FO60,640^FDBairro:^FS {$customerAddressNeighConfig}^FD{$customerAddressNeighStr}^FS
{$customerAddressCityConfig}^FD{$customerAddressCityStr}^FS
^CF0,33 ^FO60,720^FDCEP: {$dataOrder['customer_address_zip']} - UF: {$this->tirarAcentos($dataOrder['customer_address_uf'])}^FS

^FX CODIGO DE BARRAS - CEP
^BY1.5,2,90
^FO455,650^BC^FD {$dataOrder['customer_address_zip']} ^FS

^FX LINHA DO MEIO
^FO50,780^GB700,1,3^FS

^FX REMENTENTE
^CF0,40 ^FO60,795^FDRemetente:^FS
^CF0,40 ^FO280,800^FD{$this->tirarAcentos($dataStore['raz_social'])}
^CF0,33 ^FO60,840^FDEndereco: {$addressStr}^FS
^CF0,33 ^FO60,880^FDBairro: {$this->tirarAcentos($dataStore['addr_neigh'])}^FS
^CF0,33 ^FO60,920^FD{$this->tirarAcentos($dataStore['addr_city'])}^FS
^CF0,33 ^FO60,960^FDCEP: {$dataStore['zipcode']} - UF: {$this->tirarAcentos($dataStore['addr_uf'])}^FS

^FX LINHA DO FINAL
^FO50,1000^GB700,1,3^FS

^FX DADOS NF
^CF0,35 ^A0N^FO60,1040^FDNF^FS
^CF0,35 ^A0N^FO180,1040^FD{$dataNfe['nfe_num']}^FS

^CF0,35 ^A0N^FO480,1040^FDPED^FS
^CF0,35 ^A0N^FO570,1040^FD{$order_id}^FS

^FX CODIGO DE BARRAS - FINAL
^BY2,1,70
^FO60,1080^B2^FD{$dataNfe['chave']}^FS

^XZ";
        }

        return $zpls;
    }

    public function createFileZpl($zpls)
    {
        $datadir = FCPATH . 'assets/images/etiquetas/';
        $this->log_data('batch', 'Orders/CreateZPL', 'zpls=' . json_encode($zpls) . ' - datadir=' . print_r($datadir, true), "I");
        foreach ($zpls as $order => $zpl) {

            if ($zpl == false) continue;

            $strZpl = implode("\n\n", $zpl);

            $file = fopen($datadir . "P_{$order}_ZPL.txt", 'w');

            $this->log_data('batch', 'Orders/CreateZPL', 'pedido ' . $order . ' - zpl=' . json_encode($zpl) . ' - status=' . print_r($file, true), "I");

            if ($file == false) continue;
            fwrite($file, $strZpl);
            fclose($file);

            $filezpl = base_url("assets/images/etiquetas/P_{$order}_ZPL.txt");
            $this->model_freights->updateFreightsOrderId($order, array('link_etiquetas_zpl' => $filezpl));
        }

        return true;
    }

    public function cancelReason()
    {
        ob_start();
        if (!in_array('deleteOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $response = array();
        $this->form_validation->set_rules('cancelReason', $this->lang->line('application_cancel_reason'), 'trim|required');
        $this->form_validation->set_rules('penalty_to_reason', $this->lang->line('application_penalty_to'), 'trim|required');
        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {
            // devolvo o estoque do pedido
            $data = array(
                'order_id' => $this->postClean('id_order_cancelReason'),
                'reason' => $this->postClean('cancelReason'),
                'date_update' => date("Y-m-d H:i:s"),
                'status' => '1',
                'penalty_to' => $this->postClean('penalty_to_reason'),
                'user_id' => $this->session->userdata('id')
            );
            $id_cancel = $this->postClean('id_cancel');
            if ($id_cancel == '') {
                $this->model_orders->insertPedidosCancelados($data);
                $response['messages'] = $this->lang->line('messages_successfully_created');
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            } else {
                $this->model_orders->updatePedidosCancelados($data, $id_cancel);
                $response['messages'] = $this->lang->line('messages_successfully_updated');
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            }
            $response['success'] = true;
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        //header('Content-type:application/json;charset=utf-8');
        ob_clean();
        echo json_encode($response);
    }

    public function returnCorreios()
    {
        ob_start();
        if (!in_array('deleteOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $response = array();
        $this->form_validation->set_rules('id_returnCorreios', $this->lang->line('application_ship_company'), 'trim|required');

        if ($this->form_validation->run() == TRUE) {
            // devolvo o estoque do pedido
            $order_id = $this->postClean('id_returnCorreios');
            $data = array(
                'paid_status' => 50,
                'ship_company_preview' => 'CORREIOS',
                'ship_service_preview' => 'PAC',
            );

            $this->model_orders->updateByOrigin($order_id, $data);
            $response['success'] = true;
            $response['messages'] = $this->lang->line('messages_successfully_updated');
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        ob_clean();
        echo json_encode($response);
    }

    public function getAdditionData()
    {
        ob_start();
        $order  = $this->postClean('order_id');

        $result = $this->model_orders->getOrdersCount(" AND o.id = {$order}");

        if (!$result) {
            ob_clean();
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_order_not_found')));
            exit();
        }

        $result = $this->model_orders->getOrdersData(0, $order);

        ob_clean();
        echo json_encode(array('success' => true, 'data' => $result['additional_data_nfe']));
    }

    public function updateAdditionData()
    {
        ob_start();
        $order  = $this->postClean('order_id');
        $message = $this->postClean('message');

        if (empty($message)) {
            ob_clean();
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_message_required')));
            exit();
        }

        $result = $this->model_orders->getOrdersCount(" AND o.id = {$order}");

        if (!$result) {
            ob_clean();
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_order_not_found')));
            exit();
        }

        $update = $this->model_orders->updateByOrigin($order, ['additional_data_nfe' => $message]);

        ob_clean();

        if (!$update)
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_occurred') . ' ' . $this->lang->line('messages_refresh_page')));
        else
            echo json_encode(array('success' => true, 'data' => $this->lang->line('messages_changes_saved')));
    }

    public function sendFreightHire()
    {
        $this->db->trans_begin();

        $order_id = $this->postClean('order_id');

        $arrComment = array();
        $comment    = "Pedido enviado para frete a contratar";
        $user_id    = $this->session->userdata('id');

        $user = $this->model_users->getUserData($user_id);
        $userName = $user['username'];

        $dataOrder = $this->model_orders->getOrdersData(0, $order_id);

        if (!in_array($dataOrder['paid_status'], array(40, 50, 80))) {
            $this->session->set_flashdata('error', "Pedido não pode ser mais enviado para Frete Manual. Já foi gerado rastreio para esse pedido.");
            redirect('orders/update/' . $order_id, 'refresh');
        }

        if ($dataOrder['comments_adm'])
            $arrComment = json_decode($dataOrder['comments_adm']);

        $arrComment[] = array(
            'order_id'  => $order_id,
            'comment'   => $comment,
            'user_id'   => $user_id,
            'user_name' => $userName,
            'date'      => date('Y-m-d H:i:s')
        );

        $sendComment  = json_encode($arrComment);

        $createComment  = $this->model_orders->createCommentOrderInProgress($sendComment, $order_id);
        $updateStatus   = $this->model_orders->updatePaidStatus($order_id, 101);
        // remove pedido do freights
        $delFreight = $this->model_freights->removeForOrderId($order_id, false, $dataOrder['in_resend_active'] == 1);

        if (!$delFreight || !$updateStatus || !$createComment) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', "Ocorreu um problema para atualizar o pedido. Tente novamente!");
            redirect('orders/update/' . $order_id, 'refresh');
        }


        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', "Ocorreu um problema para atualizar o pedido. Tente novamente!");
            redirect('orders/update/' . $order_id, 'refresh');
        }

        $this->log_data(__CLASS__, __CLASS__.'/'.__FUNCTION__, "Pedido $order_id enviado para Frete Manual");

        $this->db->trans_commit();
        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        redirect('orders/update/' . $order_id, 'refresh');
    }

    public function changeSellerOrder()
    {
        ob_start();
        $order_id = $this->postClean('order_id');
        $message  = $this->postClean('message');

        $result = $this->model_orders->getOrdersByFilter("id = {$order_id} AND paid_status in (1,3)");

        if (!count($result)) {
            ob_clean();
            echo json_encode(array('success' => false, 'messages' => $this->lang->line('messages_order_not_found')));
            die;
        }

        $result = $result[0];

        $this->model_orders->updatePaidStatus($order_id, 70);
        $this->model_orders->saveHistoryChangeSeller($order_id, $result['store_id'], $message);

        ob_clean();
        echo json_encode(array('success' => true, 'messages' => $this->lang->line('messages_changes_saved')));
    }

    public function charge_update_status()
    {
        if (!in_array('updateTrackingOrder', $this->permission) && !in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_charge_status_order');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!ini_get("auto_detect_line_endings")) // arquivo lido em um computador Macintosh
                ini_set("auto_detect_line_endings", '1');

            if (!is_null($this->postClean("import"))) { // selecionou o arquivo e será validado

                if (!$this->postClean('validate_file')) {
                    $dirPathTemp = "assets/files/product_upload/";
                    if (!is_dir($dirPathTemp)) mkdir($dirPathTemp);
                    $upload_file = $this->upload_file_update_status();
                } else
                    $upload_file = $this->postClean('validate_file');

                if (!$upload_file) {
                    $this->session->set_flashdata('error', $this->data['upload_msg']);
                    redirect('orders/charge_update_status', 'refresh');
                }


                $csv = Reader::createFromPath($upload_file); // lê o arquivo csv
                $csv->setDelimiter(';'); // separados de colunas
                $csv->setHeaderOffset(0); // linha do header

                $stmt   = new Statement();
                $dados  = $stmt->process($csv);

                $arrRetorno         = array();
                $arrRetornoOrder      = array();
                $newFileWithError   = array();
                $qtdErros           = 0;
                $arrChaves          = array(
                    'ID do Pedido',
                    'Data Evento',
                    'Tipo Evento'
                );

                // 0 = atualizar status
                // 1 = baixar nova planilha de pedidos com erro
                $tipoImportacao = $this->postClean('typeImport');

                $this->db->trans_begin();

                foreach ($dados as $linha => $dado) {
                    $linha++; // sempre pular o header, então adicionar 1(uma) linha
                    $arrRetorno[$linha] = array();
                    $arrPedidoExistente[$linha] = false;

                    $orderId    = !isset($dado['ID do Pedido']) ? null : filter_var($this->detectUTF8(trim($dado['ID do Pedido'])), FILTER_SANITIZE_STRING);
                    $dateEvent  = !isset($dado['Data Evento']) ? null : filter_var($this->detectUTF8(trim($dado['Data Evento'])), FILTER_SANITIZE_STRING);
                    $typeEvent  = !isset($dado['Tipo Evento']) ? null : filter_var($this->detectUTF8(trim($dado['Tipo Evento'])), FILTER_SANITIZE_STRING);

                   
                    $arrRetornoOrder[$linha] = $orderId;

                    // se todas as colunas da linhas estiverem em branco, vou ignorar
                    $linhaEmBranco = true;
                    foreach ($dado as $line)
                        if (trim($line) != '') $linhaEmBranco = false;

                    if ($linhaEmBranco) {
                        unset($arrRetorno[$linha]);
                        continue; // ignoro linha em branco
                    }

                    if (!$orderId || !$dateEvent || !$typeEvent) {
                        $messageErrorInit = '';

                        if (!$orderId) $messageErrorInit = 'Não foi possível localizar o código do pedido.';
                        elseif (!$dateEvent) $messageErrorInit = 'Não foi possível localizar a data do evento.';
                        elseif (!$typeEvent) $messageErrorInit = 'Não foi possível localizar o tipo do evento.';

                        array_push($arrRetorno[$linha], $messageErrorInit);
                        array_push($newFileWithError, $dado);
                        $qtdErros++;
                        continue;
                    }

                    if ($typeEvent != 'Em Transporte' && $typeEvent != 'Entregue') {
                        array_push($arrRetorno[$linha], 'Tipo do evento não aceito, deve ser informado "Em Transporte" ou "Entregue".');
                        $qtdErros++;
                    }

                    // valida se o usuário realmente pode atualizar o pedido
                    $verifyOrder = $this->model_orders->verifyOrderOfStore($orderId);
                    if (!$verifyOrder) {
                        array_push($arrRetorno[$linha], 'Pedido não localizado.');
                        $qtdErros++;
                    }
                    $orderDados = $this->model_orders->getOrdersData(0,$orderId); 
                    // verficar se pode receber atualização checkStatusOrder()
                    $updateStatus = false;
                    if ($typeEvent == 'Em Transporte' && $orderId) {
                        $verifyStatus_waitSend = $this->model_orders->checkStatusOrder($orderId, array(4,43,53));
                        if ($verifyStatus_waitSend) $updateStatus = true;
                    } elseif ($typeEvent == 'Entregue' && $orderId) {
                        $verifyStatus_waitDelivery = $this->model_orders->checkStatusOrder($orderId, array(5,45));
                        if ($verifyStatus_waitDelivery) $updateStatus = true;
                    }

                    if (!$updateStatus && ($typeEvent == 'Em Transporte' || $typeEvent == 'Entregue') && $verifyOrder) {
                        array_push($arrRetorno[$linha], 'Pedido não está no status para receber esse tipo de atualização.');
                        $qtdErros++;
                    }
                    $formatDate = strlen($dateEvent) == 10 ? 'Y-m-d' : (strlen($dateEvent) == 16 ? 'Y-m-d H:i' : (strlen($dateEvent) == 19 ? 'Y-m-d H:i:s' : false));
                    $formatDateInput = strlen($dateEvent) == 10 ? 'd/m/Y' : (strlen($dateEvent) == 16 ? 'd/m/Y H:i' : (strlen($dateEvent) == 19 ? 'd/m/Y H:i:s' : false));
                    if (!$formatDate) {
                        array_push($arrRetorno[$linha], 'Data de evento em um formato inválido. (DD/MM/AAAA) (DD/MM/AAAA HH:mm) (DD/MM/AAAA HH:mm:ss).');
                        $qtdErros++;
                    } else {
                        $dateEventFormat = DateTime::createFromFormat($formatDateInput, $dateEvent)->format($formatDate);
                        if (!strtotime($dateEventFormat)) {
                            array_push($arrRetorno[$linha], 'Data de evento em um formato inválido. (DD/MM/AAAA) (DD/MM/AAAA HH:mm) (DD/MM/AAAA HH:mm:ss).');
                            $qtdErros++;
                        }
                    }

                    

                    if($typeEvent == 'Em Transporte'){
                        
                        if(empty($orderDados['data_pago'])){
                            $date_compara = strtotime($orderDados['data_time']);
                        }else{
                            $date_compara = strtotime($orderDados['data_pago']);
                        }

                        if(strtotime($dateEventFormat) < $date_compara){
                            array_push($arrRetorno[$linha], 'Data de envio deve ser maior que a data do pagamento do pedido.');
                            $qtdErros++;
                        }
                    }

                    if ($typeEvent == 'Entregue') { 

                        if(empty($orderDados['data_envio'])){
                            $date_compara = strtotime($orderDados['data_time']);
                        }else{
                            $date_compara = strtotime($orderDados['data_envio']);
                        }

                        if(strtotime($dateEventFormat) < $date_compara){
                            array_push($arrRetorno[$linha], 'Data de entrega deve ser maior que a data de envio do pedido.');
                            $qtdErros++;
                        }
                    }

                    if ($tipoImportacao == 0 && !count($arrRetorno[$linha])) {

                        $user_id = $this->session->userdata('id');

                        if ($typeEvent == 'Em Transporte') {
                            $nome = 'Em transporte (Manual)';
                            $codigo = 200;
                            $dataUpdate = array(
                                'paid_status' => $verifyOrder['in_resend_active'] ? 5 : 55,
                                'data_envio' => $dateEventFormat,
                                'last_occurrence' => $nome
                            );
                        } elseif ($typeEvent == 'Entregue') {
                            $nome = 'Entregue (Manual)';
                            $codigo = 201;
                            $dataUpdate = array(
                                'paid_status' => 60,
                                'data_entrega' => $dateEventFormat,
                                'last_occurrence' => $nome
                            );
                        }

                        $freights = $this->model_orders->getItemsFreights($orderId, $verifyOrder['in_resend_active'] == 1);
                        foreach($freights as $freight) {
                            $frete_ocorrencia = array(
                                'freights_id'           => $freight['id'],
                                'codigo'                => $codigo,
                                'nome'                  => $nome,
                                'data_ocorrencia'       => $dateEventFormat,
                                'data_atualizacao'      => $dateEventFormat,
                                'avisado_marketplace'   => 0
                            );
                            $this->model_frete_ocorrencias->create($frete_ocorrencia);

                            $this->log_data('frete_ocorrencias', 'new_register', "order_id={$orderId} - user_id={$user_id}" . json_encode($frete_ocorrencia), "I");

                            if ($typeEvent == 'Entregue') {
                                $freight['date_delivered'] = $dateEventFormat;
                                $this->model_freights->replace($freight);
                                $this->log_data('freight', 'update_freight', "order_id={$orderId} - user_id={$user_id}" . json_encode($freight), "I");
                            }
                        }

                        $this->model_orders->updateByOrigin($orderId, $dataUpdate); // atualiza o pedido
                        $this->log_data('order', 'update_order', "order_id={$orderId} - user_id={$user_id}" . json_encode($dataUpdate), "I");
                    } elseif ($tipoImportacao == 1) {
                        if (count($arrRetorno[$linha])) array_push($newFileWithError, $dado);
                    }
                }

                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    array_push($arrRetorno[$linha], 'Ocorreu um problema para aplicar as atualizações');
                    $qtdErros++;
                }

                $this->db->trans_commit();

                if ($tipoImportacao == 0) {
                    $this->data['validate_finish']       = $arrRetorno;
                    $this->data['validate_finish_ext']   = $arrPedidoExistente;
                    $this->data['validate_finish_order'] = $arrRetornoOrder;
                    $this->data['qty_errors']            = $qtdErros;
                } elseif ($tipoImportacao == 1) {
                    $newCsv = Writer::createFromString("");
                    //                    $newCsv->setOutputBOM(Reader::BOM_UTF8); // converte para UTF8
                    //                    $newCsv->addStreamFilter('convert.iconv.ISO-8859-15/UTF-8'); // converte de ISO-8859-15 para UTF8
                    $encoder = (new CharsetConverter())->outputEncoding('utf-8');
                    $newCsv->addFormatter($encoder);
                    $newCsv->setDelimiter(';'); // demiliter de cada coluna
                    $newCsv->insertOne($arrChaves); // cabeçalho
                    $newCsv->insertAll($newFileWithError); // linhas
                    $newCsv->output('Erros-ConectaLa_Pedidos_' . date('Y-m-d-H-i-s') . '.csv'); // arquivo de saida
                    die;
                }
                $this->data['tipo_importacao'] = $tipoImportacao;
                $this->data['orders_status_upload'] = $upload_file;
            }
        } else {
            $this->data['upload_point'] = 1;
            $upload_file = $this->lang->line('messages_nofile');
        }
        $this->data['upload_file'] = trim($upload_file);
        $this->render_template('orders/charge_update_status', $this->data);
    }

    public function upload_file_update_status()
    {
        // assets/files/product_upload
        $config['upload_path'] = 'assets/files/product_upload';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'csv|txt';
        $config['max_size'] = '100000';

        // $config['max_width']  = '1024';s
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('orders_status_upload')) {
            $error = $this->upload->display_errors();
            //Var_dump($error);
            $this->data['upload_msg'] = $this->lang->line('messages_invalid_file');
            $this->data['upload_msg'] = $error;
            return false;
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['orders_status_upload']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return ($data == true) ? $path : false;
        }
    }

    public function updateOrderIncidence()
    {
        ob_start();
        $order_id   = $this->postClean('order_id');
        $incidence  = $this->postClean('incidence');
        $cancel     = $this->postClean('cancelIncidence');
        $user_id    = $this->session->userdata('id');

        if ($cancel) {
            $incidence  = null;
            $user_id    = null;
        }

        $result     = $this->model_orders->verifyOrderOfStore($order_id);

        if (!$result) {
            ob_clean();
            echo json_encode(array('success' => false, 'messages' => $this->lang->line('messages_order_not_found')));
            die;
        }

        $this->model_orders->updateOrderIncidence($order_id, $user_id, $incidence);
        $this->addLogOrder($order_id, $cancel ? 'Removeu incidencia' : "ADD incidencia: {$incidence}");

        ob_clean();
        echo json_encode(array('success' => true, 'messages' => $this->lang->line('messages_changes_saved')));
    }

    public function addLogOrder($order_id, $comment)
    {
        $arrComment = array();
        $user_id = $this->session->userdata('id') ?? 0;

        $user = $this->model_users->getUserData($user_id);
        $userName = $user['username'] ?? 'admin';

        $dataOrder = $this->model_orders->getOrdersData(0, $order_id);

        if ($dataOrder['comments_adm'])
            $arrComment = json_decode($dataOrder['comments_adm']);

        array_push($arrComment, array(
            'order_id'  => $order_id,
            'comment'   => $comment,
            'user_id'   => $user_id,
            'user_name' => $userName,
            'date'      => date('Y-m-d H:i:s')
        ));

        $sendComment = json_encode($arrComment);

        $createComment = $this->model_orders->createCommentOrderInProgress($sendComment, $order_id);
    }

    public function createTagTransp($orders): CI_Output
    {
        $orders = explode('-', $orders);

        if (!count($orders)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'message'   => 'Não encontramos informações para a geração da etiqueta.'
                )));
        }

        $dataStoreTemp  = array();
        $nfe_label      = array();
        $freights_label = array();
        $data_orders    = $this->model_orders->getOrdersDataByIds($orders);
        $data_nfe       = $this->model_nfes->getNfesDataByOrderIds($orders);
        $data_freights  = $this->model_freights->getFreightsDataByOrderIds($orders);

        foreach ($data_nfe as $nfe_order) {
            $nfe_label[$nfe_order['order_id']][] = $nfe_order;
        }
        foreach ($data_freights as $freight_order) {
            $freights_label[$freight_order['order_id']][] = $freight_order;
        }

        foreach ($data_orders as $dataOrder) {
            if (!array_key_exists($dataOrder['store_id'], $dataStoreTemp)) {
                $store = $this->model_stores->getStoresData($dataOrder['store_id']);
                $dataStoreTemp[$dataOrder['store_id']] = $store;
            }

            $store      = $dataStoreTemp[$dataOrder['store_id']];
            $nfe        = $nfe_label[$dataOrder['id']];
            $freights   = $freights_label[$dataOrder['id']];

            if (!$dataOrder || !$store || !$nfe || !count($freights)) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'message'   => 'Não encontramos informações para a geração da etiqueta.'
                    )));
            }
        }


        $this->model_csv_to_verifications->create(array(
            'upload_file'   => "",
            'user_id'       => $this->session->userdata('id'),
            'username'      => $this->session->userdata('username'),
            'user_email'    => $this->session->userdata('email'),
            'usercomp'      => $this->session->userdata('usercomp'),
            'allow_delete'  => true,
            'module'        => 'ExportLabelTracking',
            'form_data'     => json_encode($orders)
        ));

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => true,
                'message'   => '<h3 style="margin: 0;font-weight: bold">Pedido adicionado para exportação.</h3><br><h4 style="margin: 0">Em alguns minutos as etiquetas estarão disponíveis em <b>Etiquetas Geradas</b>.</h4>'
            )));
    }

    public function requestGenerateTagShippingCompany($orders = ''): CI_Output
    {
        $orders = explode('-', $orders);

        if (!count($orders) || (count($orders) == 1 && empty($orders[0]))) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Pedido(s) não encontrado'
                )));
        }

        $this->db->trans_begin();

        foreach ($orders as $order) {
            $data_order = $this->model_orders->verifyOrderOfStore($order);
            if (!$data_order) {

                $this->db->trans_rollback();

                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'message' => "Pedido $order não encontrado para seu usuário"
                    )));
            }

            $this->model_orders->updateOrderById($order, array('freight_accepted_generation' => true));

            if ($data_order['paid_status'] == $this->model_orders->PAID_STATUS['waiting_issue_label']) {
                $this->model_orders->updatePaidStatus($data_order['id'], $this->model_orders->PAID_STATUS['tracking']);
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Não foi possível autorizar os pedidos!'
                )));
        }

        $this->db->trans_commit();

        $this->log_data(__CLASS__, __METHOD__, json_encode($orders));

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Pedidos autorizados com sucesso!<br><br>O próximo passo será enviar o pedido para a integradora. Quando disponível pela integradora, será possível fazer a impressão da(s) etiqueta(s). <br>Caso ocorra qualquer problema na integração, será possível acompanhar no pedido.'
            )));
    }

    public function manage_tags_transp()
    {
        redirect('dashboard', 'refresh');
        if (!in_array('viewLogistics', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('orders/manage_tags_transp', $this->data);
    }

    public function fetchOrdersWithLabelShippingCompany(): CI_Output
    {
        $storesFilter       = $this->postClean('stores') ?? array();
        $ship_companyFilter = $this->postClean('ship_company') ?? array();
        $result             = array();
        $etiquetasTemp      = array();
        $logistic           = array();

        try {
            $draw   = $this->postClean('draw');

            $filters = array();

            if (count($storesFilter) && !empty($storesFilter[0])) {
                $filters['where_in']['o.store_id'] = $storesFilter;
            }
            if (count($ship_companyFilter) && !empty($ship_companyFilter[0])) {
                $filters['where_in']['f.ship_company'] = $ship_companyFilter;
            }

            $fields_order = array('o.id', 'o.customer_name', 'o.date_time', 'o.data_limite_cross_docking', 's.name', 'f.ship_company', 'f.codigo_rastreio', 'n.nfe_num');

            $data = $this->fetchDataTable('model_orders', 'getFetchOrdersWithLabelShippingCompanyData', ['createCarrierRegistration'], $filters, $fields_order);
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
        }

        foreach ($data['data'] as $key => $value) {
            if (!array_key_exists($value['store_id'], $logistic)) {
                $store = $this->model_stores->getStoresData($value['store_id']);
                $logistic[$value['store_id']] = $this->calculofrete->getLogisticStore(array(
                    'freight_seller' => $store['freight_seller'],
                    'freight_seller_type' => $store['freight_seller_type'],
                    'store_id' => $store['id']
                ));

                $logistic[$value['store_id']]['isLogisticTypeWithAutoFreightAcceptedGeneration'] = $this->logisticAutoApproval
                    ->isLogisticTypeWithAutoFreightAcceptedGeneration(
                        $logistic[$value['store_id']]['type']
                    );
            }

            if (!Model_orders::isFreightAcceptedGeneration($value) &&
                !$logistic[$value['store_id']]['isLogisticTypeWithAutoFreightAcceptedGeneration']
            ) {
                continue;
            }

            if (
                (
                    $logistic[$value['store_id']]['type'] !== 'sgpweb' ||
                    (
                        $logistic[$value['store_id']]['type'] !== 'sellercenter' &&
                        $logistic[$value['store_id']]['sellercenter']
                    )
                )
            ) { // É por transportadora ou getaway logístico e ainda não foi autorizado a emissão
                if (!array_key_exists($value['order_id'], $etiquetasTemp)) {
                    $delete_button = '';
                    if (in_array($value['paid_status'], $this->model_orders->PAID_STATUS['can_delete_tracking'])) {
                        $delete_button = '<button type="button" class="btn btn-danger btn-sm-tag del-transp-tag ml-1" tag-number="' . $value['order_id'] . '"><i class="fa fa-trash" aria-hidden="true"></i> Excluir</button>';
                    }

                    $etiquetasTemp[$value['order_id']] = array(
                        "<label class='col-md-12 no-padding cursor-pointer'><input type='checkbox' value='{$value['order_id']}'> {$value['order_id']}</label>",
                        $value['customer_name'],
                        date('d/m/Y H:i', strtotime($value['date_time'])),
                        date('d/m/Y', strtotime($value['data_limite_cross_docking'])),
                        $value['store'],
                        $value['ship_company'],
                        $value['codigo_rastreio'],
                        $value['nfe_num'],
                        $delete_button
                    );
                } else {
                    $etiquetasTemp[$value['order_id']][6] = "{$etiquetasTemp[$value['order_id']][6]}<br>{$value['codigo_rastreio']}";
                }
            }
        }

        foreach ($etiquetasTemp as $etiqueta) {
            $result[] = $etiqueta;
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

    public function fetchOrdersWithoutLabelShippingCompany(): CI_Output
    {
        $storesFilter   = $this->postClean('stores') ?? array();
        $result         = array();
        $logistic       = array();

        try {
            $draw    = $this->postClean('draw');
            $filters = array();

            if (count($storesFilter) && !empty($storesFilter[0])) {
                $filters['where_in']['o.store_id'] = $storesFilter;
            }

            $fields_order   = array('o.id', 'o.customer_name', 'o.date_time', 'o.data_limite_cross_docking', 's.name', 'n.nfe_num');
            $data           = $this->fetchDataTable('model_orders', 'getFetchOrdersWithoutLabelShippingCompanyData', ['createCarrierRegistration'], $filters, $fields_order);
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
        }

        $hire_automatic_freight = $this->model_settings->getValueIfAtiveByName('hire_automatic_freight');

        foreach ($data['data'] as $item) {
            if (!array_key_exists($item['store_id'], $logistic)) {
                $store = $this->model_stores->getStoresData($item['store_id']);
                $logistic[$item['store_id']] = $this->calculofrete->getLogisticStore(array(
                    'freight_seller'        => $store['freight_seller'],
                    'freight_seller_type'   => $store['freight_seller_type'],
                    'store_id'              => $store['id']
                ));
            }

            if ($hire_automatic_freight !== false && in_array($logistic[$item['store_id']]['type'], array('intelipost', 'freterapido'))) {
                continue;
            }

            if (!in_array($item['integration_logistic'], array('sellercenter', 'sgpweb'))) { // É por transportadora ou getaway logístico e ainda não foi autorizado a emissão
                $result[] = array(
                    "<label class='col-md-12 no-padding cursor-pointer'><input type='checkbox' value='{$item['order_id']}'> {$item['order_id']}</label>",
                    $item['customer_name'],
                    date('d/m/Y', strtotime($item['date_time'])),
                    date('d/m/Y', strtotime($item['data_limite_cross_docking'])),
                    $item['store'],
                    $item['nfe_num']
                );
            }
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

    public function transfer($id)
    {
        if (!in_array('changeOrderStore', $this->permission) || $this->data['usercomp'] != 1)
            redirect('dashboard', 'refresh');

        if (!$id)
            redirect('dashboard', 'refresh');

        $orders_data = $this->model_orders->verifyOrderOfStore($id);

        if (!$orders_data)
            redirect('dashboard', 'refresh');

        $this->data['page_title'] = $this->lang->line('application_update_order');
        $this->form_validation->set_rules('store', $this->lang->line('application_store'), 'trim|required');
        $this->form_validation->set_rules('company', $this->lang->line('application_company'), 'trim|required');

        if ($this->form_validation->run() == TRUE) {

//            dd($this->postClean(NULL,TRUE), $this->postClean('sendOrderToIntegration'), $this->postClean('updateStockNewsProducts'));

            $newStoreId     = $this->postClean('store');
            $newCompanyId   = $this->postClean('company');
            $ordersItem     = $this->model_orders->getOrdersItemData($id);

            if (count($ordersItem) != count($this->postClean('product_id'))) {
                $this->session->set_flashdata('errors', 'Não foi possível identificar todos os produtos do pedido');
                redirect("orders/transfer/{$id}", 'refresh');
            }

            $this->db->trans_begin();

            // trocar store_id e company_id da orders
            $this->model_orders->updateByOrigin($id, array('store_id' => $newStoreId, 'company_id' => $newCompanyId));

            // trocar store_id, company_id, name, sku, product_id, variant, skumkt da orders_item
            foreach ($ordersItem as $key => $iten) {
                $product = $this->model_products->getProductData(0, $this->postClean('product_id')[$key]);
                $productMkt = $this->model_products->getDataProductIntegrationMkt($this->postClean('product_id')[$key], $orders_data['origin']);

                $dataupdateIten = array(
                    'store_id'      => $newStoreId,
                    'company_id'    => $newCompanyId,
                    'name'          => $product['name'],
                    'sku'           => $product['sku'],
                    'product_id'    => $product['id'],
                    'variant'       => $this->postClean('product_var')[$key],
                    'skumkt'        => $productMkt['skumkt'] ?? null
                );

                $this->model_orders_item->updateById($iten['id'], $dataupdateIten);

                if ($this->postClean('updateStockNewsProducts'))
                    $this->model_products->updateStockProduct($product['id'], ($product['qty'] - $iten['qty']));

                if ($this->postClean('updateStockOldsProducts')) {
                    $productReal = $this->model_products->getProductData(0, $iten['product_id']);
                    $this->model_products->updateStockProduct($iten['product_id'], ($productReal['qty'] + $iten['qty']));
                }
            }

            // add registro na tabela orders_to_integration
            if ($this->postClean('sendOrderToIntegration')) {
                $data = array(
                    'order_id'      => $id,
                    'company_id'    => $newCompanyId,
                    'store_id'      => $newStoreId,
                    'paid_status'   => in_array($orders_data['paid_status'], array(1, 2)) ? 1 : 3,
                    'new_order'     => 1,
                    'updated_at'    => date('Y-m-d H:i:s')
                );
                $this->model_orders_to_integration->removeAllOrderIntegration($id, $orders_data['store_id']);
                $this->model_orders_to_integration->create($data);
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
                redirect("orders/transfer/{$id}", 'refresh');
            }

            $this->log_data(__CLASS__, __METHOD__, "user=".json_encode($this->session->userdata)."\norder_old=".json_encode($orders_data)."\norder_item_old=".json_encode($ordersItem)."\npost=".json_encode($this->input->post), "I");

            $this->db->trans_commit();
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            redirect("orders/update/{$id}", 'refresh');
        } else {
            // false case
            $this->data['company_data'] = $this->model_company->getMyCompanyData();
            $this->data['order'] = $orders_data;
            $items = $this->model_orders->getOrdersItemData($orders_data['id']);
            $this->data['items'] = [];
            foreach ($items as $iten) {
                $variantProd = $this->model_products->getVariants($iten['product_id'], $iten['variant']);
                $nameVarProd = $iten['name'];
                if ($iten['variant'] != '') $nameVarProd .= ' (' . str_replace(';', ' - ', $variantProd['name']) . ')';
                array_push($this->data['items'], [
                    'id_item' => $iten['id'],
                    'id' => $iten['product_id'],
                    'name' => $nameVarProd,
                    'sku' => $iten['variant'] == '' ? $iten['sku'] : $variantProd['sku'],
                    'variation' => $iten['variant'] == '' ? 'Não' : 'Sim'
                ]);
            }

            $this->data['client'] = $this->model_clients->getByOrigin($orders_data['origin'], $orders_data['id']);

            $this->render_template('orders/transfer', $this->data);
        }
    }

    public function updateOrderResend()
    {
        $orderId = (int)$this->postClean('order_id');
        if(!in_array('updateTrackingOrder', $this->permission)) { // sem permissão
            $this->session->set_flashdata('error', $this->lang->line('application_dont_permission'));
            redirect("orders/update/{$orderId}", 'refresh');
        }

        $dataOrder = $this->model_orders->getOrdersData(0, $orderId);
        if (!$dataOrder) { // pedido não encontrado
            $this->session->set_flashdata('success', $this->lang->line('application_dont_permission'));
            redirect("orders/update/{$orderId}", 'refresh');
        }

        if ($dataOrder['paid_status'] == 59) { // reenvio

            // update order in_resend_active=true e paid_status=50 para gerar nova etiqueta
            $this->model_orders->updateByOrigin(
                $orderId,
                array(
                    'in_resend_active' => true,
                    'freight_accepted_generation' => false,
                    'paid_status' => 50
                )
            );

            // alterar registro para in_resend_active=false
            $this->model_freights->updateFreightsForResendFalse($orderId);

        } else { // solicitação de reenvio

            $orderUpdate = array('paid_status' => 59, 'in_resend_active' => false);
            $freights = $this->model_orders->getItemsFreights($orderId);
            $descStatus = 'Extravio/Devolução (Manual)';
            foreach($freights as $freight) {
                $frete_ocorrencia = array(
                    'freights_id'           => $freight['id'],
                    'codigo'                => 200,
                    'nome'                  => $descStatus,
                    'data_ocorrencia'       => date('Y-m-d H:i:s'),
                    'data_atualizacao'      => date('Y-m-d H:i:s'),
                    'avisado_marketplace'   => 0
                );
                $this->model_frete_ocorrencias->create($frete_ocorrencia);
            }
            $orderUpdate['last_occurrence'] = $descStatus;

            $this->model_orders->updateByOrigin($orderId,$orderUpdate);
        }

        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        redirect("orders/update/{$orderId}", 'refresh');
    }

    public function updateOrderRequestCancel()
    {
        $order           = (int)$this->postClean('order_id');
        $dataOrder       = $this->model_orders->verifyOrderOfStore($order);
        $user_id         = $this->session->userdata('id');
        $reason          = $this->postClean('reason_cancel_request');
        $existReason     = false;
        $stores_multi_cd = $this->model_settings->getValueIfAtiveByName('stores_multi_cd');

        if(
            (!in_array('createRequestCancelOrder', $this->permission)) // sem permissão para solicitar cancelamento
            || (!$dataOrder) // usuários não tem acesso ao pedido
            // Thiago(ATD) informou ao Pedro Henrique que pode cancelar em qualquer status 22/07/21 || (in_array($dataOrder['paid_status'], array(1,2,6,60,90,95,96,97,98,99))) // status não permite solicitar cancelamento
        ) {
            $this->session->set_flashdata('error', $this->lang->line('application_dont_permission'));
            redirect("orders/update/{$order}", 'refresh');
        }

        if ($stores_multi_cd && in_array($dataOrder['paid_status'], array(1,2,3))) {
            $store = $this->model_stores->getStoresData($dataOrder['store_id']);

            if (!$store) {
                $this->session->set_flashdata('error', $this->lang->line('messages_store_dont_exist_or_dont_permission_or_not_active'));
                redirect("orders/update/$order", 'refresh');
            }

            // Loja que está configurada como um CD, não pode solicitar cancelamento, o pedido deve ir direto para a loja principal.
            if ($store['type_store'] == 2) {
                // Encontrar qual a loja principal do CD.
                $search_for_principal_id = $this->model_stores_multi_channel_fulfillment->getMainStoreByCDStore($dataOrder['store_id']);
                if (!$search_for_principal_id) {
                    $this->session->set_flashdata('error', "Não encontrado informação sobre o CD.");
                    redirect("orders/update/$order", 'refresh');
                }
                // Consulta os dados da loja principal.
                $data_principal_store = $this->model_stores->getStoresData($search_for_principal_id['store_id_principal']);
                if (!$data_principal_store) {
                    $this->session->set_flashdata('error', "Loja principal não encontrada.");
                    redirect("orders/update/$order", 'refresh');
                }

                try {
                    $this->ordersmarketplace->changeSeller($order, $data_principal_store, $dataOrder, __CLASS__ . '/' . __FUNCTION__);
                } catch (Exception $exception) {
                    $this->session->set_flashdata('error', $exception->getMessage());
                    redirect("orders/update/$order", 'refresh');
                }

                // sucesso na atualização
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect("orders", 'refresh');
            }
        }

        // verifico se o motivo enviado realmente existe
        $requestCancelReason = $this->model_attributes->getAttributeValuesByName('request_cancel_reason');
        foreach ($requestCancelReason as $cancelReason)
            if ($reason == $cancelReason['value']) {
                $existReason = true;
                break;
            }

        // não encontrou o motivo
        if (!$existReason) {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_reason_not_found_request_cancel_order'));
            return redirect("orders/update/{$order}", 'refresh');
        }

        // inicio da transaction
        $this->db->trans_begin();

        // crio array com os dados da solicitação de cancelamento
        $arrRequestCancel = array(
            'order_id'      => $order,
            'reason'        => $reason,
            'store_id'      => $dataOrder['store_id'],
            'company_id'    => $dataOrder['company_id'],
            'user_id'       => $user_id,
            'old_status'    => $dataOrder['paid_status'],
        );

        // atualizo pedido para status 90, caso dê erro executo rollback e retorno ao usuário
        if (!$this->model_orders->updatePaidStatus($order, 90)) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            return redirect("orders/update/{$order}", 'refresh');
        }

        // cria registro de solicitação de cancelamento, caso dê erro executo rollback e retorno ao usuário
        if (!$this->model_requests_cancel_order->create($arrRequestCancel)) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            return redirect("orders/update/{$order}", 'refresh');
        }

        // validação para saber se houve algum problema nas queries
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $retorno = array('success' => false, 'message' => "Ocorreu um problema para faturar algum pedido!");
        }

        $this->log_data('Orders', __METHOD__, 'order='.json_encode($dataOrder)."\nuser=".json_encode($this->session->userdata)."\ndataRequestCancel=".json_encode($arrRequestCancel), "I");

        $this->db->trans_commit(); // confirma as queries aplicadas

        // sucesso na atualização
        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        redirect("orders/update/{$order}", 'refresh');
    }

    public function updateOrderCancelRequestCancel()
    {
        $order       = (int)$this->postClean('order_id');
        $dataOrder   = $this->model_orders->verifyOrderOfStore($order);
        $user_id     = $this->session->userdata('id');
        $reason      = $this->postClean('reason_cancel_request');
        $lastRequest = $this->model_requests_cancel_order->getFirstReasonCancel($order);

        if(
            !in_array('deleteRequestCancelOrder', $this->permission) || // sem permissão para cancelar a  solicitação cancelamento
            !$dataOrder || // usuários não tem acesso ao pedido
            !$lastRequest || // não existe solicições anteriores, não deveria acontecer
            $dataOrder['paid_status'] != 90 // status não permite cancelar solicitação de cancelamento
        ) {
            $this->session->set_flashdata('error', $this->lang->line('application_dont_permission'));
            redirect("orders/update/{$order}", 'refresh');
        }

        // inicio da transaction
        $this->db->trans_begin();

        // crio array com os dados da solicitação de cancelamento
        $arrRequestCancel = array(
            'order_id'      => $order,
            'reason'        => $reason,
            'store_id'      => $dataOrder['store_id'],
            'company_id'    => $dataOrder['company_id'],
            'user_id'       => $user_id,
            'old_status'    => $dataOrder['paid_status'],
            'cancel_request'=> true
        );

        // atualizo pedido para status 90, caso dê erro executo rollback e retorno ao usuário
        if (!$this->model_orders->updatePaidStatus($order, $lastRequest['old_status'])) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            return redirect("orders/update/{$order}", 'refresh');
        }

        // cria registro de solicitação de cancelamento, caso dê erro executo rollback e retorno ao usuário
        if (!$this->model_requests_cancel_order->create($arrRequestCancel)) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            return redirect("orders/update/{$order}", 'refresh');
        }

        // validação para saber se houve algum problema nas queries
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $retorno = array('success' => false, 'message' => "Ocorreu um problema para faturar algum pedido!");
        }

        $this->log_data('Orders', __METHOD__, 'order='.json_encode($dataOrder)."\nuser=".json_encode($this->session->userdata)."\ndataRequestCancel=".json_encode($arrRequestCancel), "I");

        $this->db->trans_commit(); // confirma as queries aplicadas

        // sucesso na atualização
        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        redirect("orders/update/{$order}", 'refresh');
    }

    public function addOrderQueueIntegration()
    {
        $order       = (int)$this->postClean('order');
        $dataOrder   = $this->model_orders->verifyOrderOfStore($order);
        $user_id     = $this->session->userdata('id');

        // usuários não tem acesso ao pedido
        if(!in_array('createQueueOrderIntegration', $this->permission) || !$dataOrder) {
            echo json_encode(['success' => false, 'message' => $this->lang->line('application_dont_permission')]);
            die;
        }

        // conferir se statud do pedido já está na fila
        foreach ($this->model_orders_to_integration->getOrdersQeueuByOrder($order) as $statusQueue) {
            if ($statusQueue['paid_status'] == $dataOrder['paid_status']) {
                echo json_encode(['success' => false, 'message' => $this->lang->line('messages_order_is_already_in_queue')]);
                die;
            }
        }

        // inicio da transaction
        $this->db->trans_begin();

        // cria registro
        $queue = [
            'order_id'      => $dataOrder['id'],
            'company_id'    => $dataOrder['company_id'],
            'store_id'      => $dataOrder['store_id'],
            'paid_status'   => $dataOrder['paid_status'],
            'updated_at'    => date('Y-m-d H:i:s')

        ];
        $this->model_orders_to_integration->create($queue);

        $this->log_data(
            'Orders',
            __METHOD__,
            'order='.json_encode($dataOrder)."\nuser=".json_encode($this->session->userdata)."\ndataAddNewQueue=".json_encode($queue),
            "I"
        );

        // validação para saber se houve algum problema nas queries
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $retorno = array('success' => false, 'message' => "Ocorreu um problema para faturar algum pedido!");
        }

        $this->db->trans_commit(); // confirma as queries aplicadas

        // sucesso na criação
        echo json_encode(['success' => true, 'message' => $this->lang->line('messages_successfully_created')]);
    }

    public function getLabelsByOrder(): CI_Output
    {
        $orders = $this->postClean('orders');

        if (!count($orders)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }

        $arrDataLinks = array();

        foreach ($this->model_freights->getFreightsWithLabelByOrder($orders) as $link) {
            $arrDataLinks[] = array(
                'order' => $link['order_id'],
                'link'  => $link['link_a4']
            );
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($arrDataLinks));
    }

    public function request_return()
    {
        // Tem a permissão necessária?
        // if (in_array('admDashboard', $user_permission)) {
        if (!in_array('createReturnOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        // Título da página na aba do navegador.
        $this->data['page_title'] = $this->lang->line('application_request_order_return');

        // Mostra a página de cadastro manual de devolução de pedidos.
        $this->render_template('orders/request_return', $this->data);
    }

    public function addMediation($order_id)
    {
        $orderStatus = $this->postClean('orderStatus');
        $arrOrderMediation = array(
            'order_id' => $order_id,
            'paid_status' => $orderStatus
        );
        $createOrderMediation = $this->model_orders_mediation->create($arrOrderMediation);

        if ($createOrderMediation) {
            $this->session->set_flashdata('success', $this->lang->line('application_order_mediation_message_create_success'));
            return redirect("orders/update/{$order_id}", 'refresh');
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            return redirect("orders/update/{$order_id}", 'refresh');
        }

        // Título da página na aba do navegador.
        $this->data['page_title'] = $this->lang->line('application_request_order_return');

        // Mostra a página de cadastro manual de devolução de pedidos.
        $this->render_template('orders/request_return', $this->data);
    }

    public function resolveMediation($order_id)
    {
        $orderStatus = $this->postClean('orderStatus');
        $arrOrderMediation = array(
            'resolved' => 1,
            'paid_status' => $orderStatus
        );
        $resolveOrderMediation = $this->model_orders_mediation->update($arrOrderMediation, $order_id);

        if ($resolveOrderMediation) {
            $this->session->set_flashdata('success', $this->lang->line('application_order_mediation_message_resolved_success'));
            return redirect("orders/update/{$order_id}", 'refresh');
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            return redirect("orders/update/{$order_id}", 'refresh');
        }
    }

    public function manage_tags_transp_del()
    {
        ob_start();
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$this->postClean('tag_number')) {
            redirect('orders/manage_tags', 'refresh');
        }

        $number_plp  = $this->postClean('tag_number');
        $dataPLP     = $this->model_freights->getDataTranspPLP($number_plp, true, false);

        $this->db->trans_begin();

        $removed = $this->removeTranspPlpOrder($dataPLP[0]['id']); // $number_plp
        if ($removed !== true) {
            $this->db->trans_rollback();
            ob_clean();
            echo json_encode(array("success" => false, "message" => $removed));
            exit();
        }

        foreach ($dataPLP as $order) {
            $updated = $this->model_orders->updatePaidStatus($order['order_id'], 50);
            if (!$updated) {
                $this->db->trans_rollback();
                ob_clean();
                echo json_encode(array("success" => false, "message" => "Não foi encontrado o pedido: {$order['order_id']}."));
                exit();
            }

            // Cria comentário no pedido informando que as etiquetas foram excluídas e as informações 
            // delas foram apagadas para permitir que novas etiquetas possam ser geradas.
            $dataOrder = $this->model_orders->getOrdersData(0, $order['order_id']);

            $arrComment = array();
            if ($dataOrder['comments_adm']) {
                $arrComment = json_decode($dataOrder['comments_adm']);
            }

            $user_id = $this->session->userdata('id');
            $user = $this->model_users->getUserData($user_id);
            $userName = $user['username'];

            array_push($arrComment, array(
                'order_id'  => $order['order_id'],
                'comment'   => "Etiquetas excluídas e informações das etiquetas apagadas.",
                'user_id'   => $user_id,
                'user_name' => $userName,
                'date'      => date('Y-m-d H:i:s')
            ));

            $comment = json_encode($arrComment);
            $create = $this->model_orders->createCommentOrderInProgress($comment, $order['order_id']);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            ob_clean();
            echo json_encode(array("success" => false, "message" => $this->lang->line('messages_error_remove_transp_plp_try_again')));
            exit();
        }

        // $this->log_data('batch', $log_name, 'Etiquetas excluídas, PLP=' . $number_plp . ', user_id=' . $this->session->userdata('id'), "I");
        $this->db->trans_commit();
        ob_clean();
        echo json_encode(array("success" => true, "message" => $this->lang->line('messages_successfully_remove_transp_plp')));
    }

    public function removeTranspPlpOrder($freights_id = null)
    {
        if (empty($freights_id)) {
            return "Número do frete não informado.";
        }

        $result = "Registro não existente na tabela de fretes.";
        $this->load->model('model_freights');

        $sql = "SELECT * FROM freights WHERE id = ?";
        foreach ($this->db->query($sql, array($freights_id))->result_array() as $data) {
            $cloned = $this->cloneTranspPlpOrder($data['id'], 'delete');
            if ($cloned === true) {
                $this->db->query("DELETE FROM freights WHERE id = ?", array($data['id']));
                $deleted = $this->db->affected_rows();
                if (!$deleted) {
                    return "Erro ao tentar apagar o registro da ocorrência.";
                }

                $result = true;
            } else {
                return $cloned;
            }
        }

        return $result;
    }

    public function cloneTranspPlpOrder($id = null, $action = 'delete')
	{
		$result = "Registro não existe na tabela de ocorrências.";

        if ($id) {
            $sql = "SELECT * FROM freights WHERE id = ?";
            foreach($this->db->query($sql, array($id))->result_array() as $data) {
				$data['action'] = $action;
                $this->db->insert('freights_history', $data);
				$inserted = $this->db->affected_rows();
				if ($inserted) {
					$result = true;
				} else {
					$result = "Erro ao tentar duplicar o registro da ocorrência.";
				}
			}
		}

        return $result;
	}

    public function freightDetails($order_id, $freight_price)
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (empty($freight_price)) {
            $freight_price = "Não definido";
        }

        $freight_return = array(
            'rule_applied'              => "Não se aplica",
            'mkt_cost'                  => "Não definido",
            'rma_cost'                  => "Não definido",
            'freight_margin'            => "Não definida",
            'pricing_original_value'    => $freight_price, // "Não definido",
            'toll'                      => "Não definido",
            'ad_valorem'                => "Não definido",
            'revenue'                   => "Não definida",
            'gris'                      => "Não definido",
            'freight_final_value'       => $freight_price, // "Não definido",
            'freight_original_value'    => $freight_price  // "Não definido"
        );

        $details = $this->model_orders->getFreightDetails($order_id);
        if ($details) {
            $freight_details = json_decode($details);
            $retorno_details = json_decode($freight_details->qs_retorno);

            $freight_final_value = "Não definido";
            if (!empty($freight_details->qs_cost)) {
                $freight_final_value = $freight_details->qs_cost;
                $freight_return['freight_final_value'] = "R$ " . number_format((float) $freight_final_value, 2, ',', '');
            }

            $freight_original_value = $freight_final_value;
            $freight_return['freight_original_value'] = "R$ " . number_format((float) $freight_original_value, 2, ',', '');

            $pricing_original_value = (float) $freight_final_value;
            $freight_return['pricing_original_value'] = "R$ " . number_format((float) $pricing_original_value, 2, ',', '');

            if (property_exists($retorno_details->shipping->apply, 'success')) {
                $freight_return['rule_applied'] = "{$retorno_details->shipping->apply->rule->rule_id} - {$freight_details->qs_provider}";

                $mkt_cost = $retorno_details->shipping->apply->rule->mkt_cost;
                $freight_return['mkt_cost'] = "$mkt_cost%";

                $rma_cost = $retorno_details->shipping->apply->rule->rma_cost;
                $freight_return['rma_cost'] = "$rma_cost%";

                $freight_margin = $retorno_details->shipping->apply->rule->freight_margin;
                $freight_return['freight_margin'] = "$freight_margin%";

                $pricing_original_value = (float) $freight_final_value * (1 - ($freight_margin + $mkt_cost + $rma_cost) / 100);
                $freight_return['pricing_original_value'] = "R$ " . number_format((float) $pricing_original_value, 2, ',', '');
            }

            $ad_valorem = $freight_details->sc_valorem;
            $ad_valorem_value = 0;
            if (!empty($ad_valorem)) {
                $freight_return['ad_valorem'] = "$ad_valorem%";
                $ad_valorem_value = (float) $pricing_original_value - ($pricing_original_value * (1 - ($ad_valorem) / 100));
            }

            $revenue = $freight_details->sc_revenue;
            $revenue_value = 0;
            if (!empty($revenue)) {
                $freight_return['revenue'] = "$revenue%";
                $revenue_value = (float) $pricing_original_value - ($pricing_original_value * (1 - ($revenue) / 100));
            }

            $gris = $freight_details->sc_gris;
            $gris_value = 0;
            if (!empty($gris)) {
                $freight_return['gris'] = "$gris%";
                $gris_value = (float) $pricing_original_value - ($pricing_original_value * (1 - ($gris) / 100));
            }

            $toll = (float) $freight_details->sc_toll;
            if (!empty($toll)) {
                $freight_return['toll'] = "R$ " . number_format((float) $toll, 2, ',', '');
            }

            $freight_original_value = $pricing_original_value - $ad_valorem_value - $revenue_value - $gris_value - $toll;
            $freight_return['freight_original_value'] = "R$ " . number_format((float) $freight_original_value, 2, ',', '');
        }

        $url =
            '<div class="col-md-12 col-xs-12 pull pull-left"><h3>Detalhamento de frete</h3></div>
              <div class="col-md-8">
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="freight_original_value">Valor original do frete</label>
                    <div>
                      <input type="text" class="form-control" id="freight_original_value" name="freight_original_value" value="' . $freight_return["freight_original_value"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="freight_toll">Pedágio</label>
                    <div>
                      <input type="text" class="form-control" id="freight_toll" name="freight_toll" value="' . $freight_return["toll"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="freight_ad_valorem">Ad valorem</label>
                    <div>
                      <input type="text" class="form-control" id="freight_ad_valorem" name="freight_ad_valorem" value="' . $freight_return["ad_valorem"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="freight_revenue">Receita de frete</label>
                    <div>
                      <input type="text" class="form-control" id="freight_revenue" name="freight_revenue" value="' . $freight_return["revenue"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="freight_gris">Gerenciamento de risco (GRIS)</label>
                    <div>
                      <input type="text" class="form-control" id="freight_gris" name="freight_gris" value="' . $freight_return["gris"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group col-md-4">
                <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width: 100%; font-size: 22px; font-weight: normal;"><center></center-->VALOR DO FRETE + GENERALIDADES</th>
                    </tr>
                  </thead>
                  <tbody>
                    <td style="width: 100%; font-size: 40px; font-weight: bold;"><center><span id="final_freight" name="final_freight">' . $freight_return["pricing_original_value"] . '</span></center></td>
                  </tbody>
                </table>
              </div>';

        $store_id = $this->model_stores->getActiveStore();
        if (gettype($store_id) == 'array') {
            if (isset($store_id[0]['id'])) {
                $store_id = $store_id[0]['id'];
            }
        }

        $company_id = $this->data['usercomp'];
        $order = $this->model_orders->getOrdersDataByIds(array('id' => $order_id));
        $order_company_id = $order[0]['company_id'];
        $order_store_id = $order[0]['store_id'];

        $is_allowed = false;
        if ($company_id == 1) {
            $is_allowed = true;
        } else if ($this->data['only_admin'] && ($company_id == $order_company_id)) {
            $is_allowed = true;
        }

        if ($is_allowed) {
            $url .= '
              <div class="col-md-12 col-xs-12 pull pull-left"><h3>Detalhamento da precificação de frete</h3></div>
              <div class="col-md-8">
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="pricing_rule_applied">Regra aplicada</label>
                    <div>
                      <input type="text" class="form-control" id="pricing_rule_applied" name="pricing_rule_applied" value="' . $freight_return["rule_applied"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="pricing_original_value">Valor original do frete</label>
                    <div>
                      <input type="text" class="form-control" id="pricing_original_value" name="pricing_original_value" value="' . $freight_return["pricing_original_value"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="pricing_rma_cost">Custo RMA</label>
                    <div>
                      <input type="text" class="form-control" id="pricing_rma_cost" name="pricing_rma_cost" value="' . $freight_return["rma_cost"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="pricing_mkt_cost">Custo marketplace</label>
                    <div>
                      <input type="text" class="form-control" id="pricing_mkt_cost" name="pricing_mkt_cost" value="' . $freight_return["mkt_cost"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="pricing_freight_revenue">Margem de frete</label>
                    <div>
                      <input type="text" class="form-control" id="pricing_freight_revenue" name="pricing_freight_revenue" value="' . $freight_return["freight_margin"] . '" autocomplete="off" readonly/>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="client_address">&nbsp;</label>
                    <div>&nbsp;</div>
                  </div>
                  <div class="form-group col-md-12">
                    <table class="table table-bordered" id="product_info_table">
                      <thead>
                        <tr>
                          <th style="width: 100%; font-size: 22px; font-weight: normal;"><center></center-->VALOR DO FRETE + GENERALIDADES + PRECIFICAÇÃO</th>
                        </tr>
                      </thead>
                      <tbody>
                        <td style="width: 100%; font-size: 40px; font-weight: bold;"><center><span id="final_pricing" name="final_pricing">' . $freight_return["freight_final_value"] . '</span></center></td>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>';
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($url));
    }
    
    public function checkNFeValid(): CI_Output
    {
        $data = $this->postClean();

        $key            = $data['key'];
        $serie          = $data['serie'];
        $number         = $data['number'];
        $dateEmission   = $data['dateEmission'];
        $store_id       = $data['store_id'];

        $check = $this->ordersmarketplace->checkKeyNFe($key, $serie, $number, $dateEmission, $store_id);

        if (!$check[0]) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $check[1]
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => null
            )));
    }

    public function fetchLabelsGeneratedByShippingCompany(): CI_Output
    {
        $usersFilter    = $this->postClean('users') ?? array();
        $result         = array();
        $filter_default = array();
        $draw           = $this->postClean('draw');

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => 0,
            "recordsFiltered"   => 0,
            "data"              => $result,
        );

        $my_users = $this->model_users->getMyUsersData();
        // Se não informar usuário, recupero todos os meus.
        if (empty($usersFilter)) {
            $usersFilter = array_map(function ($item) {
                return $item['id'];
            }, $my_users);
        }

        // Verifica se o usuário existe.
        $exist_user = true;
        foreach ($usersFilter as $user) {
            if (!in_array(true, array_map(function ($item) use ($user) {
                return $user == $item['id'];
            }, $my_users))) {
                $exist_user = false;
                break;
            }
        }

        // Usuário não encontrado.
        if (!$exist_user) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($output));
        }

        try {

            $filters = array();

            if (count($usersFilter) && !empty($usersFilter[0])) {
                $filters['where_in']['user_id'] = $usersFilter;
            }

            $filter_default['where']['module'] = 'ExportLabelTracking';

            $users_inactivates = array_map(function ($item) { return $item['id']; }, $this->model_users->getMyUsersData(false));
            if (!empty($users_inactivates)) {
                $filter_default['where_not_in']['user_id'] = $users_inactivates;
            }

            $fields_order = array('user_email', 'final_situation', 'form_data', 'created_at', '');

            $data = $this->fetchDataTable('model_csv_to_verifications', 'getFetchFileProcessProductsLoadData', [], $filters, $fields_order, $filter_default);
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($output));
        }

        foreach ($data['data'] as $key => $value) {
            switch ($value['final_situation']) {
                case 'wait':
                    $colorStatus = 'warning';
                    $nameStatus = '<i class="fa fa-spinner fa-spin"></i>';
                    break;
                case 'success':
                    $colorStatus = 'success';
                    $nameStatus = $this->lang->line('application_success');
                    break;
                case 'err':
                case 'error':
                    $colorStatus = 'danger';
                    $nameStatus = $this->lang->line('application_error');
                    break;
                default:
                    $colorStatus = '';
                    $nameStatus = '';
            }
            $status = "<span class='label label-$colorStatus'>$nameStatus</span>";

            $result[$key] = array(
                $value['username'],
                $status,
                implode(', ', json_decode($value['form_data'], true)),
                date('d/m/Y H:i', strtotime($value['created_at'])),
                $value['final_situation'] === 'success' ? "<a href='".base_url($value['upload_file'])."' download>{$this->lang->line('application_print_labels')} <i class='fa fa-download'></i></a>" : '',
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function cancelcommissioncharges(){

        $dataPost = $this->postClean();
        $dataArquivoBanco = null;

        //Realiza o Upload do arquivo caso exista
        if ($_FILES['fl_commision_charges_input_file']['error'] == 0)
        {

	        $exp_extens = explode( ".", $_FILES['fl_commision_charges_input_file']['name']) ;
	        $extensao = $exp_extens[count($exp_extens)-1];

	        $tempFile = $_FILES['fl_commision_charges_input_file']['tmp_name'];

	        if ($_SERVER['SERVER_NAME'] == "localhost")
			{
				$root = getcwd();
				$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/commision_charges/'.$dataPost['id_pedido_cancelamento_comissao'].'/');

				if (!is_dir($caminhoMapeado))
					mkdir($caminhoMapeado, 0777, true);
			}
			else
			{

				$root = getcwd();
				$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/commision_charges/'.$dataPost['id_pedido_cancelamento_comissao'].'/');

				if (!is_dir($caminhoMapeado))
					mkdir($caminhoMapeado, 0777, true);
			}

	        $targetPath = $caminhoMapeado;
	        $targetFile =  str_replace('//','/',$targetPath) . $dataPost['id_pedido_cancelamento_comissao'] .'-'. $_FILES['fl_commision_charges_input_file']['name'];

            $dataArquivoBanco = str_replace('\\', '/', 'assets/docs/commision_charges/'.$dataPost['id_pedido_cancelamento_comissao'].'/'. $dataPost['id_pedido_cancelamento_comissao'] .'-'. $_FILES['fl_commision_charges_input_file']['name']);

	        move_uploaded_file($tempFile,$targetFile);

	    }

        //Descobre o valor da comissão a ser cobrada pelo pedido
        $arrayFiltroPedido['txt_id_pedido'] = $dataPost['id_pedido_cancelamento_comissao'];

        // $retornoCalculo = $this->model_orders->getMandanteFretePedido( $dataPost['id_pedido_cancelamento_comissao'] );
        // $valorComissaoCobrada = round($retornoCalculo['taxa_descontada'] * -1,2);
        
        $calculateCampaign = (bool)$this->model_settings->getValueIfAtiveByName('cancellation_commission_calculate_campaign');
        $valorComissaoCobrada = $this->model_orders->calculateRefundCommission($dataPost["id_pedido_cancelamento_comissao"], $calculateCampaign);
        $valorComissaoCobradaFiscal = $this->model_orders->calculateRefundCommission($dataPost["id_pedido_cancelamento_comissao"], false);


        //Realiza a criação do painel jurídico com o valor da comissão a ser cobrada
        $dataJuridico = [
            'notification_type' => 'order',
            'orders_id' => $dataPost['id_pedido_cancelamento_comissao'],
            'notification_id' => "Estorno de comissão Cobrada",
            'notification_title' => "Estorno de comissão Cobrada",
            'status' => "Chamado Aberto",
            'description' => "Estorno de comissão Cobrada",
            'balance_paid' => $valorComissaoCobrada,
            'balance_debit' => $valorComissaoCobrada,
            'attachment' => null,
            'creation_date' => date_create()->format(DATE_INTERNATIONAL),
            'update_date' => date_create()->format(DATE_INTERNATIONAL),
            'accountable_opening' => $this->session->userdata['username'],
            'accountable_update' => $this->session->userdata['username'],
        ];

        $createLegalPanel = $this->model_legal_panel->create($dataJuridico);

         //Realiza a criação do painel jurídico com o valor da comissão a ser cobrada
         $dataJuridicoFiscal = [
            'notification_type' => 'order',
            'orders_id' => $dataPost['id_pedido_cancelamento_comissao'],
            'notification_id' => "Estorno de comissão Cobrada",
            'notification_title' => "Estorno de comissão Cobrada",
            'status' => "Chamado Aberto",
            'description' => "Estorno de comissão Cobrada",
            'balance_paid' => $valorComissaoCobradaFiscal,
            'balance_debit' => $valorComissaoCobradaFiscal,
            'attachment' => null,
            'creation_date' => date_create()->format(DATE_INTERNATIONAL),
            'update_date' => date_create()->format(DATE_INTERNATIONAL),
            'accountable_opening' => $this->session->userdata['username'],
            'accountable_update' => $this->session->userdata['username'],
        ];

        $this->model_legal_panel_fiscal->create($dataJuridicoFiscal);
        $idLegalPanelCreated = $this->model_legal_panel->createLegalPanelLastId();

        $dataOrdersCommisionCharges = [
            'order_id' => $dataPost['id_pedido_cancelamento_comissao'],
            'observation' => $dataPost['txt_commission_charges_descricao'],
            'file' => $dataArquivoBanco,
            'users_id' => $this->session->userdata['id'],
            'legal_panel_id' => $idLegalPanelCreated,
        ];

        //Realiza o insert na OrdersCommisionCharges
        $retFinal = $this->model_legal_panel->insertOrdersCommisionCharges($dataOrdersCommisionCharges);

        if($retFinal){
            $retorno['ret'] = "sucesso";
            $retorno['msg'] = "Sucesso ao estornar a comissão do pedido ".$dataPost['id_pedido_cancelamento_comissao'];
        }else{
            $retorno['ret'] = "sucesso";
            $retorno['msg'] = "Sucesso ao estornar a comissão do pedido ".$dataPost['id_pedido_cancelamento_comissao'];
        }

        echo json_encode($retorno);


    }

    public function getItemsToPartialCancellation(int $order_id): CI_Output
    {
        $output = array();

        if (!in_array('partialCancellationOrder', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($output));
        }

        $order_items        = $this->model_orders->getOrdersItemData($order_id);
        $order_items_cancel = $this->model_order_items_cancel->getByOrderId($order_id);

        foreach ($order_items as $order_item) {
            $item_cancel = getArrayByValueIn($order_items_cancel, $order_item['id'], 'item_id');

            $output[] = array(
                'id'            => $order_item['id'],
                'sku'           => $order_item['sku'],
                'name'          => $order_item['name'],
                'qty'           => (int)$order_item['qty'],
                'qty_canceled'  => (int)($item_cancel['qty'] ?? 0)
            );
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }


    public function saveItemsToPartialCancellation(int $order_id): CI_Output
    {
        if (!in_array('partialCancellationOrder', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('messages_not_permission')
                )));
        }

        $data_order = $this->model_orders->verifyOrderOfStore($order_id);
        // Pedido não encontrado ou não pertence a loja.
        if (!$data_order) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('messages_order_not_found')
                )));
        }

        // Pedido está no status incorreto.
        if (!in_array($data_order['paid_status'], array(1,2,3))) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('messages_not_permission')
                )));
        }

        $order_value_refund_on_gateway = $this->model_order_value_refund_on_gateways->getByOrderId($order_id);
        if (!empty($order_value_refund_on_gateway)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('messages_not_permission')
                )));
        }

        $data_post = $this->postClean('data_post');
        $order_items = $this->model_orders->getOrdersItemData($order_id);
        $all_canceled_items = true;
        foreach ($order_items as $order_item) {
            $cancel_item = getArrayByValueIn($data_post, $order_item['id'], 'item_id');
            if (!$cancel_item) {
                $all_canceled_items = false;
                break;
            }

            if ($cancel_item['qty'] != $order_item['qty']) {
                $all_canceled_items = false;
                break;
            }
        }

        // Todos os produtos foram cancelados.
        if ($all_canceled_items) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => "Não é permitido selecionar todos os produtos para cancelamento parcial."
                )));
        }

        $this->db->trans_begin();

        $this->model_order_items_cancel->removeByOrderid($order_id);
        foreach ($data_post as $item) {
            $cancel_item = getArrayByValueIn($order_items, $item['item_id'], 'id');
            if (!$cancel_item) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'message' => "Produto $item[item_id] não encontrado como um produto do pedido."
                    )));
            }

            $item['qty'] = (int)$item['qty'];

            if ($item['qty'] > 0) {
                $this->model_order_items_cancel->create(array(
                    'order_id'  => $order_id,
                    'item_id'   => $item['item_id'],
                    'qty'       => $item['qty'],
                    'user_id'   => $this->session->userdata('id')
                ));
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('messages_error_occurred')
                )));
        }

        $this->db->trans_commit();

        $this->log_data(__CLASS__, __FUNCTION__, "order_id=$order_id\n".json_encode($data_post));

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->lang->line('messages_successfully_updated')
            )));
    }

    public function refundOrderValueToGateway(int $order_id)
    {
        if (!in_array('refundOrderValue', $this->permission)) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect("orders/update/$order_id", 'refresh');
        }

        $days_to_refund_value_tuna = $this->model_settings->getValueIfAtiveByName('days_to_refund_value_tuna');
        if (!$days_to_refund_value_tuna) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect("orders/update/$order_id", 'refresh');
        }

        $data_order = $this->model_orders->verifyOrderOfStore($order_id);
        // Pedido não encontrado ou não pertence a loja.
        if (!$data_order) {
            $this->session->set_flashdata('error', $this->lang->line('messages_order_not_found'));
            redirect("orders/update/$order_id", 'refresh');
        }

        // Pedido está no status incorreto.
        if (in_array($data_order['paid_status'], array(1,2,7,8,30,31,81,95,96,97,98,99))) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect("orders/update/$order_id", 'refresh');
        }

        if (strtotime(addDaysToDate($data_order['date_time'], $days_to_refund_value_tuna)) < strtotime(dateNow()->format(DATETIME_INTERNATIONAL))) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect("orders/update/$order_id", 'refresh');
        }

        // Pedido tem cancelamento parcial.
        if ($data_order['paid_status'] == 3 && !empty($this->model_order_items_cancel->getByOrderId($order_id))) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect("orders/update/$order_id", 'refresh');
        }

        $refund_on_gateway_value = $this->postClean('refund_on_gateway_value');
        $refund_on_gateway_description = $this->postClean('refund_on_gateway_description');

        // Valor deve ser maior que zero.
        if (empty($refund_on_gateway_value)) {
            $this->session->set_flashdata('error', "Valor deve ser maior que zero.");
            redirect("orders/update/$order_id", 'refresh');
        }

        $order_value_refund_on_gateways = $this->model_order_value_refund_on_gateways->getByOrderId($order_id);
        $total_refund = array_sum(
            array_map(function ($item) {
                return (float)$item['value'];
            }, $order_value_refund_on_gateways)
        );

        $product_return = $this->model_product_return->getByOrderId($order_id);
        $total_return = array_sum(
            array_map(function ($item) {
                return (float)($item['shipping_value_returned'] ?? 0) + (float)($item['product_value_returned'] ?? 0);
            }, $product_return)
        );

        $orders_item = $this->model_orders->getOrdersItemData($order_id);
        $order_items_cancel = $this->model_order_items_cancel->getByOrderId($order_id);

        $total_return_value = 0;
        foreach ($orders_item as $order_item) {
            $item_cancel = getArrayByValueIn($order_items_cancel, $order_item['id'], 'item_id');
            if ($item_cancel) {
                $this->data['has_canceled_item'] = true;
            }

            $qty_canceled = $item_cancel['qty'] ?? 0;
            $total_amount_canceled_mkt = $item_cancel['total_amount_canceled_mkt'];

            if ($qty_canceled != 0 && !empty($total_amount_canceled_mkt)) {
                $total_return_value += $total_amount_canceled_mkt;
            }
        }

        $max_to_refund = ($data_order['total_order'] + $data_order['total_ship']) - ($total_refund + $total_return + $total_return_value);

        if ($refund_on_gateway_value > $max_to_refund) {
            $this->session->set_flashdata('error', $this->lang->line('messages_mx_value_not_allowed'));
            redirect("orders/update/$order_id", 'refresh');
        }

        $this->model_order_value_refund_on_gateways->create(array(
            'order_id'      => $order_id,
            'store_id'      => $data_order['store_id'],
            'company_id'    => $data_order['company_id'],
            'user_id'       => $this->session->userdata('id'),
            'value'         => $refund_on_gateway_value,
            'description'   => $refund_on_gateway_description
        ));

        $this->log_data(__CLASS__, __FUNCTION__, "order_id=$order_id\n".json_encode($this->postClean()));

        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        redirect("orders/update/$order_id", 'refresh');
    }
}
