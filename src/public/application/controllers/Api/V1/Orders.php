<?php

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Security $security
 * @property CI_Output $output
 *
 * @property Model_settings $model_settings
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_products $model_products
 * @property Model_requests_cancel_order $model_requests_cancel_order
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_groups $model_groups
 * @property Model_billet $model_billet
 * @property Model_orders_item $model_orders_item
 * @property Model_clients $model_clients
 * @property Model_stores $model_stores
 * @property Model_providers $model_providers
 * @property Model_integrations $model_integrations
 * @property Model_orders_pickup_store $model_orders_pickup_store
 * @property Model_commissionings $model_commissionings
 * @property Model_commissioning_orders_items $model_commissioning_orders_items
 * @property Model_legal_panel $model_legal_panel
 * @property Model_campaign_v2_orders_items $model_campaign_v2_orders_items
 *
 * @property OrdersMarketplace $ordersmarketplace
 * @property CalculoFrete $calculofrete
 */

class Orders extends API
{
    private $cod_order;
    private $filters;
    private $statusDelete = 0;
    private $fieldsNfe = array(
        'order_number'      =>  array('columnDatabase' => 'order_id','type' => 'I', 'required' => true),
        'invoce_number'     =>  array('columnDatabase' => 'nfe_num','type' => 'I', 'required' => true),
        'price'             =>  array('columnDatabase' => 'nfe_value','type' => 'F', 'required' => true),
        'serie'             =>  array('columnDatabase' => 'nfe_serie','type' => 'I', 'required' => true),
        'access_key'        =>  array('columnDatabase' => 'chave','type' => 'S', 'required' => true),
        'emission_datetime' =>  array('columnDatabase' => 'date_emission','type' => 'S', 'required' => true),
        'collection_date'   =>  array('columnDatabase' => 'data_coleta','type' => 'S', 'required' => false),
        'link_nfe'          =>  array('columnDatabase' => 'link_nfe','type' => 'S', 'required' => false),
        'isDelivered'       =>  array('columnDatabase' => 'isDelivered','type' => 'S', 'required' => false),
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_products');
        $this->load->model('model_requests_cancel_order');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_groups');
        $this->load->model('model_billet');
        $this->load->model('model_orders_item');
        $this->load->model('model_clients');
        $this->load->model('model_stores');
        $this->load->model('model_providers');
        $this->load->model('model_integrations');
        $this->load->library('ordersMarketplace');
        $this->load->library('calculoFrete');
        $this->load->model('model_orders_pickup_store');
        $this->load->model('model_integrations_webhook');
        $this->load->model('model_commissionings');
        $this->load->model('model_commissioning_orders_items');
        $this->load->model('model_legal_panel');
        $this->load->model('model_campaign_v2_orders_items');

    }

    public function index_get($cod_order = null)
    {
        $this->endPointFunction = __FUNCTION__;
        $this->cod_order = $cod_order;

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        // filtros
        $this->filters = $this->cleanGet($_GET) ?? null;
      
        // não informou código do pedido, retorna a fila de pedidos
        if ($cod_order === null) {
            $result = $this->createQueueOrder();
        }
       
        // encontrou código do pedido
        if ($cod_order !== null) {
            if (!is_numeric($cod_order)) {
                $this->response(array('success' => false, 'message' => "O código do pedido deve ser numérico"), REST_Controller::HTTP_BAD_REQUEST);                
                return;                
            }
            $this->cod_order = (string)$cod_order;
            $result = $this->createArrayOrder();
        }

        // Verifica se foram encontrados resultados
        if (isset($result['error']) && $result['error']) {
            $this->response($this->returnError($result['data']), $result['data'] == $this->lang->line('api_no_results_where') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        $response = array('success' => true, 'result' => $result);

        if ($cod_order === null) {
            $response = array(
                'success'           => true,
                "registers_count"   => $result['registers_count'],
                "pages_count"       => $result['pages_count'],
                "page"              => $result['page'],
                "total_registers"   => $result['total_registers'],
                'result'            => $result['data']
            );
        }

        $this->response($response, REST_Controller::HTTP_OK);
    }

    /**
     * Consulta lista de pedidos.
     */
    public function list_get()
    {
        $this->endPointFunction = __FUNCTION__;
        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        // filtros
        $this->filters = $this->cleanGet($_GET) ?? null;

        // consulta a lista de pedidos
        $result = $this->createListOrder();

        // Verifica se foram encontrados resultados
        if (isset($result['error']) && $result['error']){
            $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    /**
     * Consulta lista de pedidos.
     */
    public function filled_orders_get()
    {
        $this->endPointFunction = __FUNCTION__;

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }
        // filtros
        $this->filters = $this->cleanGet($_GET) ?? null;

        // Informo que preciso que a taxa do cartão de crédito esteja preenchida.
        $this->filters['check_credit_card_fee'] = true;

        $result = $this->createQueueOrder();

        // Verifica se foram encontrados resultados
        if (isset($result['error']) && $result['error']) {
            $this->response($this->returnError($result['data']), $result['data'] == $this->lang->line('api_no_results_where') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $response = array(
            'success'           => true,
            "registers_count"   => $result['registers_count'],
            "pages_count"       => $result['pages_count'],
            "page"              => $result['page'],
            "total_registers"   => $result['total_registers'],
            'result'            => $result['data']
        );

        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function nfe_post()
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
        // Recupera dados enviado pelo body
        // $data   = $this->inputClean();
        // Recupera dados enviado pelo body
        $data = json_decode(file_get_contents('php://input'));

        // valida a criação de nfe
        $result = $this->insert_nfe($data);

        // Verifica se foram encontrado algum erro
        if (isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($data) . "\nSTR_PAYLOAD=".file_get_contents('php://input'),"W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST);
        }
        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($data));

        $this->response(array('success' => true, "message" => $this->lang->line('api_invoice_inserted')), REST_Controller::HTTP_CREATED);
    }

    public function partial_invoice_post($billNo = null)
    {
        $this->endPointFunction = __FUNCTION__;

        if ($billNo === null) {
            return $this->response(
                ['success' => false, 'message' => 'Código do pedido não informado'],
                REST_Controller::HTTP_BAD_REQUEST
            );
        }

        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $billNo = xssClean($billNo);

        $payload = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->response(
                ['success' => false, 'message' => 'JSON inválido'],
                REST_Controller::HTTP_BAD_REQUEST
            );
        }

        $items = [];
        if (isset($payload['items']) && is_array($payload['items'])) {
            $items = $payload['items'];
        } elseif (isset($payload['skus']) && is_array($payload['skus'])) {
            foreach ($payload['skus'] as $sku) {
                $items[] = ['sku' => $sku];
            }
        }

        require_once APPPATH . 'controllers/BatchC/Marketplace/Conectala/GetOrders.php';
        $processor = new GetOrders();
        $result = $processor->processPartialInvoicing($billNo, $items);

        $httpCode = $result['success'] ? REST_Controller::HTTP_OK : REST_Controller::HTTP_BAD_REQUEST;
        return $this->response($result, $httpCode);
    }

    public function index_post(string $platform)
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit(false, false);
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
    
        $body = $this->cleanGet(json_decode($this->security->xss_clean($this->input->raw_input_stream), true));

        if (!array_key_exists('order', $body)) {
            return $this->response(array('success' => false, 'message' => "Corpo da requisição em um formato inválido, informe a chave 'order' para adicionar os dados do pedido. ({\"order\": { ... }}) "), REST_Controller::HTTP_BAD_REQUEST);
        }

        $order  = $body['order'];
      
        try {
            $marketplace = $order['system_marketplace_code'];
            $items       = $order['items'];
            $this->validateMarketplaceCreateOrder($order['system_marketplace_code']);
            $responseValidItems = $this->ordersmarketplace->validItemsOrder($items, $platform, $marketplace);
            $this->ordersmarketplace->validCodeMarketpalceOrder($order['marketplace_number'], $marketplace);
            $this->ordersmarketplace->validAmountOrderByApi($items, $order['payments'], $order['shipping']['seller_shipping_cost']);
            $this->ordersmarketplace->validIfMarketplaceExist($marketplace);

            $store = $this->model_stores->getStoreById($responseValidItems['arrDataAd'][$items[0]['sku']]['store_id']);
        } catch (Exception | Error $exception) {
            return $this->response(array('success' => false, 'message' => $exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
        
        // Inicia uma transação.
        $this->db->trans_begin();
        try {
            // create clients
            $client = $this->model_clients->create($this->ordersmarketplace->formatClientToCreateApi($order));
            // create orders
            $orderId = $this->model_orders->insertOrder($this->ordersmarketplace->formatOrderToCreateApi($order, $store, $client));
            // create orders_items
            foreach ($this->ordersmarketplace->formatOrderItemToCreateApi($order, $orderId, $platform) as $item) {
                $this->model_orders->insertItem($item);
            }
        } catch (Exception | Error $exception) {
            $this->db->trans_rollback();
            return $this->response(array('success' => false, 'message' => $exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
       
        // Falhou alguma query.
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return $this->response(array('success' => false, 'message' => 'Ocorreu um problema interno'), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->db->trans_commit();
        
       
         //Verificando se existe webhook cadastrado para essa loja no momento da criação
        if ($this->model_integrations_webhook->storeExists($store)) {
        
            $store_id_wh = $store;
            $typeIntegration = "pedido_criado";
            $this->ordersmarketplace->formatsendDataWebhook($store_id_wh, $typeIntegration, $orderId, $order);
        }
       

        return $this->response(
            array(
                'success' => true,
                'message' => 'Pedido criado com sucesso',
                'id'      => $orderId
            ),
            REST_Controller::HTTP_CREATED
        );
    }

    public function index_put($cod_order = null, $type = null)
    {
        $type =  $this->cleanGet($type);
        $cod_order = xssClean($cod_order); 
        $this->cod_order = (string)xssClean($cod_order);
        
        if (!in_array($type, array("shipped", "delivered", "canceled"))) {
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_operation_not_accepted'),"W");
            $this->response($this->returnError($this->lang->line('api_operation_not_accepted')), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if ($cod_order === null) {
            $this->response($this->returnError($this->lang->line('api_order_not_found')), REST_Controller::HTTP_NOT_FOUND);
            return;
        }
        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        // Recupera dados enviado pelo body
        $data = $this->inputClean();

        // Verifica o tipo de atualização
        switch ($type) {
            case "shipped":
                $result = $this->update_shipped($data);
                break;
            case "delivered":
                $result = $this->update_delivered($data);
                break;
            case "canceled":
                $result = $this->update_canceled($data);
                break;
            default:
                $this->response($this->returnError($this->lang->line('api_operation_not_accepted')), REST_Controller::HTTP_BAD_REQUEST);
                return;
        }

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, "type=$type - payload: " . json_encode($data));

        // Verifica se foram encontrados algum erro
        if (isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($data),"W");
            $this->response($this->returnError($result['data']), $result['http_code'] ?? REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($data));
        $this->response(array('success' => true, "message" => $this->lang->line('api_order_updated')), REST_Controller::HTTP_OK);
    }

    public function index_delete($cod_order = null, $removeAll = null)
    {
        $this->endPointFunction = __FUNCTION__;
        if (!$cod_order){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_order_code_not'),"W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_order_code_not'))), REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // set cod_order
        $this->cod_order = (string)xssclean($cod_order);
        $removeAll =  xssclean($removeAll);

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }
        // Remove registro do pedido na fila
        $result = $this->removeStatusIntegration($removeAll);

        // Verifica se foram encontrados resultados
        if (isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - order: " . json_encode($cod_order),"W");
            $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,"[ORDER={$cod_order}]\n[STATUS={$this->statusDelete}]");

        $this->response(array('success' => true, "message" => $this->lang->line('api_order_removed_queue')), REST_Controller::HTTP_OK);
    }

    public function change_status_new_order_patch($cod_order = null)
    {
        $this->endPointFunction = __FUNCTION__;
        if (!$cod_order){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_order_code_not'),"W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_order_code_not'))), REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // set cod_order
        $this->cod_order = (string)xssclean($cod_order);

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }
        // Remove registro do pedido na fila
        $result = $this->changeStatusNewOrder();

        // Verifica se foram encontrados resultados
        if (isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - order: " . json_encode($cod_order),"W");
            $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,"[ORDER={$cod_order}]");

        $this->response(array('success' => true, "message" => $this->lang->line('api_order_change_status_new_order')), REST_Controller::HTTP_OK);
    }

    private function removeStatusIntegration($removeAll)
    {
        if ($this->model_orders_to_integration->getDataOrdersInteg($this->getStoreToLists(), $this->tokenMaster, $this->cod_order)->num_rows() === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_records_found'));
        }

        if ($removeAll === 'all') {
            $this->model_orders_to_integration->removeAllOrderIntegration($this->cod_order, $this->getStoreToLists(), $this->tokenMaster);
            return array('error' => false);
        }

        $idDelOrder = $this->model_orders_to_integration->getDataOrdersInteg($this->getStoreToLists(), $this->tokenMaster, $this->cod_order)->first_row();

        $idQueue = $idDelOrder->id;
        $statusQueue = $idDelOrder->paid_status;
        $newOrderQueue = $idDelOrder->new_order;

        $this->statusDelete = $statusQueue;

        $this->db->trans_begin();

        if ($statusQueue == 3 && $newOrderQueue == 1) {
            $this->model_orders_to_integration->update($idQueue, array('new_order' => 0), $this->tokenMaster);
        }else {
            $this->model_orders_to_integration->delete($idQueue, $this->tokenMaster);
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            if ($this->db->trans_status() === FALSE) {
                return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
            }
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function insert_nfe($data)
    {
        $this->load->model('model_orders');
        $this->load->model('model_nfes');

        $erroColumn = "";
        $dataSql = array();
        $store_id = null;

        if (!isset($data->nfe)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_found_nfe_key'));
        }
        if (count((array)$data->nfe) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_found_no_data'));
        }
        
        $verifyConfigOrderSequencial = [ ];
        foreach ($data->nfe as $key_nfe => $value_nfe) {
            if (isset($value_nfe->order_number)){
                $verifyConfigOrderSequencial[$value_nfe->order_number] = false;
                $data_order = $this->model_orders->getOrdersData(0, $value_nfe->order_number);
                if (!isset($data_order)) {
                    $erroColumn = "NFE: #" . ($key_nfe + 1) . " - " . $this->lang->line('api_order') . $value_nfe->order_number . $this->lang->line('api_order_no_existent');
                    return array('error' => true, 'data' => $erroColumn);
                }

                // Pedido precisa está no status 3.
                if ($data_order['paid_status'] != 3) {
                    $erroColumn = "NFE: #" . ($key_nfe + 1) . " - " . $this->lang->line('api_order') . $value_nfe->order_number . $this->lang->line('api_order_no_updated_invoices');
                    return array('error' => true, 'data' => $erroColumn);
                }

                // Verifica as lojas, só pode enviar notas da mesma loja por vez.
                if (is_null($store_id)) {
                    $store_id = $data_order['store_id'];
                } else if ($store_id != $data_order['store_id']) {
                    $erroColumn = "NFE: #" . ($key_nfe + 1) . " - " . $this->lang->line('api_invoices_from_different_stores');
                    return array('error' => true, 'data' => $erroColumn);
                }

                // Loja não tem acesso ao pedido.
                if (!$this->checkStoreByProvider($store_id, $data_order)) {
                    $erroColumn = "NFE: #" . ($key_nfe + 1) . " - " . $this->lang->line('api_order') . $value_nfe->order_number . $this->lang->line('api_order_no_existent');
                    return array('error' => true, 'data' => $erroColumn);
                }

                // Se já tem nota fiscal, atualiza.
                $queryNfe = $this->model_orders->getOrdersNfes((int)$value_nfe->order_number);
                if (count($queryNfe) > 0) {
                    $dataSql[$key_nfe] = $queryNfe[0];
                }
            }

            $isDelivered = !empty($value_nfe->isDelivered);

            $dataAtualizacaoTimestamp = $this->getDataAtualizacaoForcada($data_order['store_id'], $value_nfe->order_number, $data_order['origin']); 

            if ($dataAtualizacaoTimestamp && $isDelivered){
                $tracking = $this->model_order_to_delivered->getTrackingByStoreAndMarketplace($data_order['store_id'], $data_order['origin']);
                if (!$tracking) {
                    return null; 
                }

                $orderConfig = $this->model_order_to_delivered->getConfigById($tracking['order_to_delivered_config_id']);

                if (!$orderConfig) {
                    return null;
                }

                $hasSerialConfig = !empty($orderConfig['sequencial_nfe']) ? $orderConfig['sequencial_nfe'] : 0;

                if($hasSerialConfig == 1){
                    $this->model_order_to_delivered->updateFlagOrder($value_nfe->order_number);
                    
                    $verifyConfigOrderSequencial[$value_nfe->order_number] = true;

                    $chaveSequencial = str_pad($value_nfe->order_number, 14, '0', STR_PAD_LEFT);
                    $chaveSequencial = str_repeat('0', 30) . $chaveSequencial;

                    $value_nfe->emission_datetime = $value_nfe->emission_datetime ?: dateNow()->format(DATETIME_BRAZIL); //formata para data brasileira

                    $value_nfe->access_key = $value_nfe->access_key ?: $chaveSequencial;
                    if (!isset($value_nfe->access_key) || $value_nfe->access_key === "") {
                        return array('error' => true, 'data' => $this->lang->line('api_access_key_invalid'));
                    }

                    $value_nfe->invoce_number = $value_nfe->invoce_number ?: $chaveSequencial;
                    if (!isset($value_nfe->invoce_number) || $value_nfe->invoce_number === "") {
                        return array('error' => true, 'data' => $this->lang->line('api_invoce_number_invalid'));
                    }

                    $value_nfe->serie = $value_nfe->serie ?: $chaveSequencial;
                    if (!isset($value_nfe->serie) || $value_nfe->serie === "") {
                        return array('error' => true, 'data' => $this->lang->line('api_serie_invalid'));
                    }
                }
            }

            foreach ($value_nfe as $key => $value) {
                if($key === 'invoice_number') {
                    $key = 'invoce_number';
                }
                //se tiver faltando algum dado obrigatorio faltando
                if (!array_key_exists($key, $this->fieldsNfe) && $erroColumn === "") {
                    $erroColumn = "NFE: #" . ($key_nfe + 1) . " - " . $this->lang->line('api_parameter_not_match_field_insert') . $key;
                    break;
                }

                $value = $this->setValueVariableCorrect($key, $value);

                if ((
                    $value === "" ||
                    (
                        $value === 0 &&
                        $key != 'serie'
                    )) &&
                    $erroColumn === "" &&
                    $this->fieldsNfe[$key]['required']
                ) {
                    $erroColumn = "NFE: #" . ($key_nfe + 1) . " - " . $this->lang->line('api_all_fields_informed') . $key;
                    break;
                }

                $verify = $this->verifyFieldsNFe($key, $value);

                if (!$verify[0] && $erroColumn === "") {
                    $erroColumn = "NFE: #" . ($key_nfe + 1) . " - " .$verify[1];
                    break;
                }

                $value = $verify[1];

                if (!isset($this->fieldsNfe[$key])) {
                    continue;
                }

                $dataSql[$key_nfe][$this->fieldsNfe[$key]['columnDatabase']] = $value;
            }
            if (!isset($dataSql[$key_nfe]['company_id'])) {
                $dataSql[$key_nfe]['company_id'] = $this->company_id;
            }
        }

        // Erros gerado no laço dos itens
        if ($erroColumn !== "") {
            return array('error' => true, 'data' => $erroColumn);
        }

        // Não existem campos para o insert
        if (count($dataSql) == 0) {
            return array('error' => true, 'data' => $this->lang->line('api_found_no_data'));
        }

        foreach ($dataSql as $key_nfe => $data) {
            // valida chave de acesso
            if (!$verifyConfigOrderSequencial[$value_nfe->order_number]){

                $chave_acesso = $this->ordersmarketplace->checkKeyNFe($data['chave'], $data['nfe_serie'], $data['nfe_num'], $data['date_emission'], $store_id);
                if (!$chave_acesso[0]) {
                    return array('error' => true, 'data' => "NFE: #" . ($key_nfe + 1) . " - {$chave_acesso[1]}");
                }

            }
            
            // campos faltantes
            $checkRequiredFields = $this->checkRequiredFields($data);
            if ($checkRequiredFields != "") {
                return array('error' => true, 'data' => "NFE: #" . ($key_nfe + 1) . " - " . $this->lang->line('api_mandatory_fields_not_informed') . $checkRequiredFields);
            }
        }

        // Inicia transação
        $this->db->trans_begin();

        foreach ($dataSql as $key_nfe => $data) {
            $datacoleta = $data['data_coleta'] ?? "";
            unset($data['data_coleta']);

            // formata data da coleta para DD/MM/YYYY
            $data['date_emission'] = \DateTime::createFromFormat('Y-m-d H:i:s', $data['date_emission'])->format('d/m/Y H:i:s');
            unset($data['isDelivered']);
            
            // cria nfe
            $create = $this->model_nfes->replace($data);

            if ($create) {
                if ($datacoleta != "") {
                    $this->model_orders->updateDataColeta($data['order_id'], \DateTime::createFromFormat('Y-m-d', $datacoleta)->format('d/m/Y'));
                }
                $this->model_orders->updatePaidStatus($data['order_id'], '52');
            }
            else {
                return array('error' => true, 'data' => "NFE: #" . ($key_nfe + 1) . " - Import error. Try again.");
            }

        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function checkRequiredFields($dataSql)
    {
        foreach ($this->fieldsNfe as $key => $prop){
            $unverified_fields = array("data_coleta");
            if (!array_key_exists($prop['columnDatabase'], $dataSql) && !in_array($prop['columnDatabase'], $unverified_fields) && $this->fieldsNfe[$key]['required']){
                return $key;
            }
        }
        return "";
    }

    private function verifyFieldsNFe($key, $value)
    {
        $value_ok = array(true, $value);

        if ($key === "access_key"){
            if (strlen(trim(onlyNumbers($value))) != 44){
                return array(false, $this->lang->line('api_key_must_char'));
            }
            $value_ok = array(true, trim(onlyNumbers($value)));
        }
        if ($key === "emission_datetime"){
            $value = trim($value);
            $validDateTime = $this->ValidDateAndTime($value);
            if (!$validDateTime[0] || strlen($value) != 19) {
                return array(false, $validDateTime[1] . $this->lang->line('api_order_date_invalid_format_valid'));
            }

            $value = \DateTime::createFromFormat('d/m/Y H:i:s', $value)->format('Y-m-d H:i:s');

            $value_ok = array(true, $value);
        }
        if ($key === "collection_date" && $value != ""){
            if (strlen($value) != 10) {return array(false, $this->lang->line('api_order_date_invalid_format'));}

            $validDateTime = $this->ValidDateAndTime($value);
            if (!$validDateTime[0]) {return array(false, $validDateTime[1] . $this->lang->line('api_invalid_format'));}

            $value = \DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d');

            $value_ok = array(true, $value);
        }

        return $value_ok;
    }

    private function setValueVariableCorrect($key, $value)
    {
        if (!isset($this->fieldsNfe[$key])) {return $value;}
        switch ($this->fieldsNfe[$key]['type']) {
            case 'S': return (string)$value;
            case 'I': return (int)$value;
            case 'F': return (float)$value;
            default:  return $value;
        }
    }

    private function createQueueOrder(): array
    {
        $arrOrdersList = array();
        $check_credit_card_fee = false;
        $filters = "";

        if ($this->filters) {
            if (isset($this->filters['status']) && trim($this->db->escape($this->filters['status'])) != ""){
                $this->filters['status'] = str_replace(",","','",trim($this->db->escape($this->filters['status'])));
                $filters .= ' AND oti.paid_status in(' . filter_var($this->filters['status']) . ')';
            }
            if (isset($this->filters['new_order']) && trim($this->filters['new_order']) != "") {
                $status = trim($this->filters['new_order']) == 'true' ? 1 : 0;
                $filters .= " AND oti.new_order = {$status}";
            }

            $createdAfter = isset($this->filters['order_created_after']) ? $this->filters['order_created_after'] : null;

            if ($createdAfter) {
                $dateAfter = DateTime::createFromFormat('d-m-Y', $createdAfter);

                if (!$dateAfter || $createdAfter != $dateAfter->format('d-m-Y')) {
                    return array('error' => true, 'data' => "Formato aceito: 'dd-mm-YYYY'.");
                }
                $filters .= ' AND oti.updated_at >= "' .  $dateAfter->format('Y-m-d') . ' 00:00:00"';
            }

            $createdBefore = isset($this->filters['order_created_before']) ? $this->filters['order_created_before'] : null;

            if ($createdBefore) {
                $dateBefore = DateTime::createFromFormat('d-m-Y', $createdBefore);

                if (!$dateBefore || $createdBefore != $dateBefore->format('d-m-Y')) {
                    return array('error' => true, 'data' => "Formato aceito: 'dd-mm-YYYY'.");
                }

                $filters .= ' AND oti.updated_at <= "' .  $dateBefore->format('Y-m-d') . ' 23:59:59"';
            }

            $check_credit_card_fee = array_key_exists('check_credit_card_fee', $this->filters);
        }

        //filtros page e qtd por pagina
        $page       = $this->filters['page'] ?? 1;
        $per_page   = $this->filters['per_page'] ?? 100;
        $start_queue_id = $this->filters['queue_id'] ?? 0;

        $page       = filter_var($page, FILTER_VALIDATE_INT);
        $per_page   = filter_var($per_page, FILTER_VALIDATE_INT);

        // Validação de valor mínimo e/ou máximo
        if ($page <= 0) {
            $page = 1;
        }
        if ($per_page <= 0) {
            $per_page = 1;
        }
        if ($per_page > 100) {
            $per_page = 100;
        }

        $page--; // decremento para realizar a consulta
        $page_per_page = $page*$per_page;
        $limit = "LIMIT $per_page OFFSET $page_per_page";

        // existe filtro de 'only_new_order', deve ignorar todos os filtro(exceto de paginação) e trazer apenas novo pedidos
        $filterOnlyNewAndOrder = isset($this->filters['only_new_order']) && trim($this->filters['only_new_order']) == true;
        if ($filterOnlyNewAndOrder) {
            $queryOrders = $this->model_orders_to_integration->getDataOrdersOnlyNewOrder($this->getStoreToLists(), $this->tokenMaster, $limit, $check_credit_card_fee, $start_queue_id);
            $allResult = $this->model_orders_to_integration->getDataOrdersOnlyNewOrder($this->getStoreToLists(), $this->tokenMaster, '', $check_credit_card_fee, $start_queue_id);
        } else { // filtro normal
            $queryOrders = $this->model_orders_to_integration->getDataOrdersInteg($this->getStoreToLists(), $this->tokenMaster, null, $filters, $limit, $check_credit_card_fee, $start_queue_id);
            $allResult = $this->model_orders_to_integration->getDataOrdersInteg($this->getStoreToLists(), $this->tokenMaster, null, $filters, '', $check_credit_card_fee, $start_queue_id);
        }

        // Verifica se foi encontrado resultados
        if ($queryOrders->num_rows() === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));
        }

        $resultOrder = $queryOrders->result_array();

        foreach ($resultOrder as $order) {
            $new_order = $order['new_order'] == 1;
            // Verificar se existe registro desse pedido com new_order=1
            // Para deixar o new_order desse registro igual a 1
            if ($new_order === false && $filterOnlyNewAndOrder) {
                $new_order = true;
            }

            $arrOrdersList[] = array(
                'queue_id' => $this->changeType($order['id'], "int"),
                'order_code' => $this->changeType($order['order_id'], "int"),
                'status' => array(
                    'code' => $this->changeType($order['paid_status'], "int"),
                    'label' => $this->ordersmarketplace->getStatusOrder($order['paid_status'])
                ),
                'updated_at' => $order['updated_at'],
                'new_order' => $new_order
            );
        }

        $page++; // incremento novamente para retornar na requisição
        $totalReg = $allResult->num_rows();
        $totalPages = $totalReg / $per_page;

        return array(
            "registers_count"   => count($resultOrder),
            "pages_count"       => $totalPages === (int)$totalPages ? $totalPages : (int)$totalPages + 1,
            "page"              => $page,
            "total_registers"   => $totalReg,
            "data"              => $arrOrdersList
        );
    }

    private function createArrayOrder(): array
    {
        $arrPayment  = array();
        $arrItems    = array();
        $cpf         = null;
        $cnpj        = null;
        $person_type = null;

        // consulta o seller center
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $sellercenter = $settingSellerCenter['value'];

        // Consulta
        $queryOrder = $this->getDataOrder();
        
        $queryPayment = $this->getDataPaymentOrder();
        $queryIntegration = $this->model_orders_to_integration->getDataOrdersInteg($this->getStoreToLists(), $this->tokenMaster, (string)$this->cod_order);
        $queryHistoric = $this->getDataHistoric($this->cod_order);

        // Verifica se foi encontrado resultados
        if ($queryOrder->num_rows() === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));
        }

        $resultOrder = $queryOrder->result_array();
        $resultPayment = $queryPayment->result_array();
        $resultHistoric = $queryHistoric->result_array();

        $itemIds = array_column($resultOrder, 'iten_id');
        $invoices = $this->model_nfes->getNfesDataByOrderItemIds($itemIds);
        $invoiceByItem = [];
        foreach ($invoices as $inv) {
            $invoiceByItem[$inv['order_item_id']] = $inv;
        }

        // Dados do pedido.
        $order = $resultOrder[0];

        // Loja não tem acesso ao pedido.
        if (!$this->checkStoreByProvider($order['store_id'], $order)) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }

        // Cria array com o pagamento
        $arrPayment["gross_amount"] = $this->changeType($order['gross_amount_order'], "float");

        // mesma lógica da tela do pedido
        $tipoFrete = $this->model_orders->getMandanteFretePedido($this->cod_order);
        $store_id = $this->store_id > 0 ? $this->store_id : $order['store_id'];
        $tipoLoja = $this->model_stores->getStoresData($store_id);

        $arrPayment["net_amount"] = $tipoFrete['expectativaReceb'] == "0" || $sellercenter=='somaplace' || $sellercenter=='novomundo' ?
            $this->changeType($order['net_amount_order'], "float") :
            $this->changeType($tipoFrete['expectativaReceb'], "float");

        $this->data['usercomp'] = 1;
        $data = $this->model_billet->getPedidosExtratoConciliado(['txt_id_pedido' => $this->db->escape($this->cod_order)]);
        
        if ($data) {
            $arrPayment['payment_seller_amount'] = ($data && isset($data['data'][0]['expectativaReceb'])) ? (float)$data['data'][0]['expectativaReceb'] : 0;            
        } else {
            $arrPayment['payment_seller_amount'] = 0;
        }

        $arrPayment["service_charge"] = $tipoFrete['expectativaReceb'] == "0" || $sellercenter == 'somaplace' || $sellercenter == 'novomundo' ?
            $this->changeType(($order['service_charge_order'] + $order['vat_charge_order'] - $order['campaign_comission_reduction']), "float") :
            $this->changeType($tipoFrete['taxa_descontada'] - $order['campaign_comission_reduction'], "float");

        $arrPayment["discount"] = $this->changeType($order['discount_total'], "float");
        $arrPayment["total_products"] = $this->changeType($order['total_products_order'], "float");
        $arrPayment["qty_parcels"] = count($resultPayment) == 0 ? 1 : count($resultPayment);
        $arrPayment["date_payment"] = $order['data_pago_order'];
        // $valueMdr = !count($resultPayment) ? 0 : (($resultPayment[0]['taxa_cartao_credito'] / 100) * $resultPayment[0]['valor']);
        $valueMdr = !count($resultPayment) ? 0 : $resultPayment[0]['taxa_cartao_credito'];

        $arrayTaxas =  $this->model_orders->getDetalheTaxas($this->cod_order);
           
        $campanhaComissao = $arrayTaxas[0]['comissao_campanha'] ?? 0;
        $reembolso_mkt = $arrayTaxas[0]['reembolso_mkt'] ?? 0;

        $arrPayment["campaing_comission"] = $this->changeType($campanhaComissao, "float");
        $arrPayment["marketplace_refund"] = $this->changeType($reembolso_mkt, "float");

        $settingSensitivePayment = $this->model_settings->getSettingDatabyName('get_sensitive_payment_data');

        $arrPayment["parcels"] = array();
        if (count($resultPayment)) {
            foreach ($resultPayment as $key => $payment) {
                $arrPayment['parcels'][] = array(
                    "order_payment_id"  => $this->changeType($payment['id'], "int"),
                    "parcel"            => $this->changeType($payment['parcela'], "int"),
                    "value"             => $this->changeType($payment['valor'], "float"),
                    "due_date"          => empty($payment['data_vencto']) ? null : $payment['data_vencto'],
                    "payment_method"    => $payment['forma_desc'],
                    "payment_id"        => $this->getPaymentId(),
                    "payment_type"      => $payment['forma_id'],
                    "mdr"               => $this->changeType($valueMdr, "float")
                );

                if ($settingSensitivePayment && $settingSensitivePayment['status'] == 1) {
                    $arrPayment['parcels'][$key] = array_merge($arrPayment['parcels'][$key], array(
                        "status_payment"         => $payment['status_payment'], // Transaction Status
                        "transaction_id"         => $payment['transaction_id'], // Transaction Id
                        "first_digits"           => $payment['first_digits'],
                        "last_digits"            => $payment['last_digits'],
                        "payment_transaction_id" => $payment['payment_transaction_id'], // Payment Transaction Id
                        "autorization_id"        => $payment['autorization_id'] // Connector Authorization Id
                    ));
                }
            }
        }

        $arrHistoric = array();
        if (count($resultHistoric)) {
            foreach ($resultHistoric as $key => $historic) {
                
                $date_notification = null;

                switch ($historic['status']) {
                    case 3: 
                    case 50:
                        $date_notification = $order['data_mkt_invoice'] ?? ''; 
                        break;
                    case 5:
                    case 45:
                    case 55: 
                        $date_notification = $order['data_mkt_sent'] ?? '' ; 
                        break;
                    case 6: 
                    case 60:
                        $date_notification = $order['data_mkt_delivered'] ?? '' ; 
                        break;
                }

                $arrHistoric[] = array(
                    "status_id"         => $historic['status'],
                    "status"            => $this->ordersmarketplace->getStatusOrder($historic['status']),
                    "date"              => $historic['date_status_update'],
                    "date_notification" => $date_notification
                );
            }
        }

        // Criar array com os itens
        $product_exist = array();
        $return_net_product_fee_api = $this->model_settings->getValueIfAtiveByName('return_net_product_fee_api');
        foreach ($resultOrder as $key => $iten) {

            if (in_array($this->changeType($iten['iten_id'], "int"), $product_exist)) {
                continue;
            }

            $arrComissionReduction = $this->getCommissionReduction($iten['product_id_iten']);

            $sku_variation = null;
            $sku = $iten['sku_iten'];
            $sku_integration = $iten['product_id_erp'] ?? $sku;
            $valueComission = (($arrComissionReduction / 100) * $iten['amount_iten_total']);
            $valueFreighServiceFee = (($order['total_ship'] * $order['service_charge_freight_value_order']) / 100);
            $commission_item = $order['service_charge_rate'];
            $commision = $this->model_commissioning_orders_items->getCommissionByOrderAndItem($this->cod_order, $iten['iten_id']);
            if ($commision) {
                $commission_item = $commision['comission'];
            }

            $total_product = $iten['amount_iten_total'];
            $valor_comissao_produto = $total_product * ($commission_item / 100);
            $valor_comissao_campanha = 0;
            $reembolso_marketplace = 0;
            if ($return_net_product_fee_api) {
                $campaign_v2_orders_item = $this->model_campaign_v2_orders_items->getAllItemCampaignsByOrderItemId($iten['iten_id']);
                if ($campaign_v2_orders_item) {
                    $valor_comissao_campanha = $campaign_v2_orders_item['channel_discount'] * ($commission_item / 100);
                    $reembolso_marketplace = $campaign_v2_orders_item['channel_discount'];
                }
            }

            $valueComissionStore = $valor_comissao_produto + $valor_comissao_campanha - $reembolso_marketplace;

            if ($iten['variant'] != "" && $iten['variant'] != null) {
                $getVar = $this->getSkuVariant($iten['product_id_iten'], $iten['variant']);
                $sku_variation = $getVar->sku;
                $sku_integration = $getVar->variant_id_erp ?? $sku_variation;
                $sku = $this->getSkuPai($iten['product_id_iten'], $iten['variant']);
            }

			$skumkt = null;
			$campaignArray = [];
			$returnArray = [];

            if ($this->is_provider) {
				$skumkt = $this->getSkuMkt($iten['product_id_iten'], $iten['variant'], $iten['origin']);
				$campaignData = $this->getCampaignDataByProductId($iten['product_id_iten'], $iten['iten_id'], $iten['origin']);
				$returnData = $this->getReturn($this->cod_order, $iten['variant']);

				if (!empty($campaignData))
				{
                    $campaignArray['current_price'] = $campaignData['current_price'];
                    $campaignArray['campaign_price'] = $campaignData['campaign_price'];
                    $campaignArray['campaign_discount'] = $campaignData['campaign_discount'];
					$campaignArray['discount_type'] = $campaignData['discount_type'];
					$campaignArray['seller_discount'] = $campaignData['seller_discount'];
					$campaignArray['marketplace_discount'] = $campaignData['marketplace_discount'];
					$campaignArray['total_discount'] = $campaignData['total_discount'];
                    $campaignArray['coupon_discount'] = $campaignData['coupon_discount'];
				}

				if (!empty($returnData) && $returnData['devolution_request_date'] != null && $returnData['returned_items'] != null)
				{
					$returnArray['items_return_date'] = $returnData['devolution_request_date'];
					$returnArray['items_returned'] = $returnData['returned_items'];
				}
			}

            $arrItems[$key] = array(
                "remote_store_id" => $this->changeType($iten['store_id'], "int"),
                "qty" => $this->changeType($iten['qty_iten'], "float"),
                "product_id" => $this->changeType($iten['product_id_iten'], "int"),
                "original_price" => ($this->changeType($iten['amount_iten'], "float") + $this->changeType($iten['discount_iten'], "float")),
                "total_price" => $this->changeType($iten['amount_iten_total'], "float"),
                "name" => $iten['name_iten'],
                "sku" => $sku,
                "discount" => $this->changeType($iten['discount_iten'], "float"),
                "unity" => $iten['unity_iten'],
                "gross_weight" => $this->changeType($iten['gross_weight_iten'], "float"),
                "width" => $this->changeType($iten['width_iten'], "float"),
                "height" => $this->changeType($iten['height_iten'], "float"),
                "depth" => $this->changeType($iten['depth_iten'], "float"),
                "measured_unit" => $iten['measured_unit_iten'],
                "variant_order" => trim($iten['variant']) == "" ? null : $this->changeType($iten['variant'], "int"),
                "sku_variation" => $sku_variation,
                "freight_service_fee" => $this->changeType($valueFreighServiceFee, "float"),
                "product_fee" => $arrComissionReduction <> "" ? $this->changeType($valueComission, "float") : $this->changeType($valueComissionStore, "float"),
                "sku_integration" => $sku_integration,
				"campaigns" => $campaignArray,
				"sku_marketplace" => $skumkt,
                "return" => $returnArray,
                "order_item_id" => $this->changeType($iten['iten_id'], "int"),
            );

            $inv = $invoiceByItem[$iten['iten_id']] ?? null;
            $arrItems[$key]['invoice'] = $inv ? [
                'date_emission' => $inv['date_emission'],
                'value' => $this->changeType($inv['nfe_value'], 'float'),
                'serie' => $this->changeType($inv['nfe_serie'], 'int'),
                'num' => $this->changeType($inv['nfe_num'], 'int'),
                'key' => $inv['chave'],
                'link' => $inv['link_nfe']
            ] : null;

            $commission_hierarchy_value = null;
            $commission_hierarchy_level = null;
            $commission_hierarchy_id    = null;

            if ($this->is_provider) {
                $commission_hierarchy_value = number_format($order['service_charge_rate'], 2, '.', '').'%';
                $commissioning_orders_item = $this->model_commissioning_orders_items->getCommissionByOrderAndItem($this->cod_order, $iten['iten_id']);
                if ($commissioning_orders_item && $commissioning_orders_item['commissioning_id']) {
                    $commissioning = $this->model_commissionings->getById($commissioning_orders_item['commissioning_id']);
                    if ($commissioning) {
                        $commission_hierarchy_value = $commissioning_orders_item['comission'] . '%'; //valor da comissão do produto dentro da hierarquia
                        $commission_hierarchy_level = $commissioning['type']; //verificar quais nomes foram usados para definir hierarquias por loja, marca, categoria, politica comercial
                        $commission_hierarchy_id = $this->changeType($commissioning_orders_item['commissioning_id'], "int"); //id da comissão por hierarquia aplicada no item
                    }
                }
            }

            $arrItems[$key]["commission_hierarchy_value"] = $commission_hierarchy_value; //valor da comissão do produto dentro da hierarquia
            $arrItems[$key]["commission_hierarchy_level"] = $commission_hierarchy_level; //verificar quais nomes foram usados para definir hierarquias por loja, marca, categoria, politica comercial
            $arrItems[$key]["commission_hierarchy_id"] = $commission_hierarchy_id; //id da comissão por hierarquia aplicada no item

            $product_exist[] = $this->changeType($iten['iten_id'], "int");
        }
        // ordenar para perder o índice.
        sort($arrItems);

        $nfValue = $this->changeType($order['nfe_value'], "float");
        if(!$nfValue){
            $nfValue = $arrPayment["gross_amount"];
        }
        // Cria array com dados da nfe
        $arrInvoice = $order['nfe_chave'] == null ? NULL : array(
            "date_emission" => $this->format_date($order['nfe_date_emission']),
            "value" => $nfValue,
            "serie" => $this->changeType($order['nfe_serie'], "int"),
            "num" => $this->changeType($order['nfe_num'], "int"),
            "key" => $order['nfe_chave'],
            "link" => $this->changeType($order['link_nfe'])
        );

        // Define variáveis cnpj e cpf e tipo de pessoa
        if ($order['cpf_cnpj']){
            $doc = onlyNumbers($order['cpf_cnpj']);
            if (strlen($doc) === 11 || strlen($doc) === 14) {
                strlen($doc) === 11 ? $cpf = $doc : $cnpj = $doc;
            }

            if (strlen($doc) === 11 || strlen($doc) === 14) {
                $person_type = strlen($doc) === 11 ? 'pf' : 'pj';
            }
        }

        // Array com dados de envio
        $arrShip = $this->getDataShipping($resultOrder);
        
        $pickupStore = $this->model_settings->getValueIfAtiveByName('occ_pickupstore');
        if ($pickupStore) {
            $isPickup = $this->model_orders_pickup_store->getDataByOrderId($this->cod_order);
            if ($isPickup) {
                $arrShip = $isPickup;
            }        
        }
        $couponActive = $this->model_settings->getValueIfAtiveByName('api_show_coupon');
        if($couponActive && $this->store_id == 0){
            $arrPayment['coupon'] = json_decode($order['coupon']);
        }

        $commision_charges = $this->is_provider ? $this->model_legal_panel->getDataOrdersCommisionChargesByOrderId($this->cod_order) : null;

        return array(
            'order' => array(
                "code" => $this->changeType($this->cod_order, "int"),
                "system_marketplace_code" => $order['origin'],
                "marketplace_number" => $order['marketplace_code'],
				"sales_channel" => $order['sales_channel'],
                "created_at" => $order['date_time_order'],
                "store_code" => $this->changeType($order['store_id'], "int"),
                "company_code" => $this->changeType($order['company_id'], "int"),
                "status_sync" => $queryIntegration->num_rows() === 0 ? "SYNCED" : "NOT_SYNCED",
                'sales_model' => $order['sales_model'],
                "is_incomplete" => $this->changeType($order['is_incomplete'], 'boolean'),
                "status" => array(
                    "label" => $this->ordersmarketplace->getStatusOrder($order['status_order']),
                    "code" => $this->changeType($order['status_order'], "int")
                ),
                "commission_returned" => !empty($commision_charges), // true ou false para devolvido ou não respectivamente
                "commission_returned_date" => $commision_charges['date_create'] ?? null, //data em que a ação ocorreu
                "shipping" => $arrShip,
                "payments" => $arrPayment,
                "items" => $arrItems,
                "invoice" => $arrInvoice,
                "customer" => array(
                    "id" => $order['id_client'],
                    "name" => $order['customer_name_order'],
                    "person_type" => $person_type,
                    "cpf" => $cpf,
                    "cnpj" => $cnpj,
                    'ie' => $order['ie_client'],
                    'rg' => $order['rg_client'],
                    "email" => $order['email_client'],
                    "birth_date" => $order['birth_date_client'],
                    "phones" => array(
                        $order['phone1_client'],
                        $order['phone2_client'],
                    ),
                ),
                "billing_address" => array(
                    "street" => $order['address_client'],
                    "secondary_phone" => $order['phone2_client'],
                    "region" => $order['address_uf_client'],
                    "postcode" => $order['address_zip_client'],
                    "phone" => $order['phone1_client'],
                    "number" => $order['address_num_client'],
                    "neighborhood" => $order['address_neigh_client'],
                    "full_name" => $order['customer_name_client'],
                    "country" => "BR",
                    "complement" => $order['address_compl_client'] == null ? "" : $order['address_compl_client'],
                    "city" => $order['address_city_client']
                ),
                "historic" => $arrHistoric
            )
        );
    }

    private function getSkuPai($prd_id, $order_var)
    {
        if ($prd_id == 0 || $order_var == "" || $order_var == null) {return null;}


        $sql = "SELECT products.sku FROM prd_variants JOIN products ON prd_variants.prd_id = products.id WHERE prd_variants.prd_id = {$prd_id} AND prd_variants.variant = {$order_var}";
        $query = $this->db->query($sql);
        if ($query->num_rows() === 0) {return null;}

        return $query->first_row()->sku;

    }

	private function getSkuMkt($prd_id, $variant, $int_to)
	{
		if ($prd_id == 0 || $int_to == "" || $int_to == null) {
            return null;
        }

        $this->db->select('skumkt')
            ->where(array(
                'prd_id' => $prd_id,
                'int_to' => $int_to
            ));

        if (!is_null($variant) && $variant !== '') {
            $this->db->where('variant', $variant);
        }

        $result = $this->db->get('prd_to_integration')->row_array();

        if (!$result) {
            return null;
        }

        return $result['skumkt'];
	}
	private function getReturn($order_id, $variant)
	{
        $this->db->select('devolution_request_date, SUM(quantity_requested) as returned_items')
        ->where(array(
            'order_id' => $order_id,
            'STATUS' => 'devolvido'
        ));

        if (!is_null($variant) && $variant !== '') {
            $this->db->where('variant', $variant);
        }

		return $this->db->get('product_return')
            ->row_array();
	}

    private function getSkuVariant($prd_id, $order_var)
    {
        if ($prd_id == 0 || $order_var == "" || $order_var == null) {return null;}

        $sql = "SELECT sku, variant_id_erp FROM prd_variants WHERE prd_id = {$prd_id} and variant = {$order_var}";
        $query = $this->db->query($sql);
        if ($query->num_rows() === 0) {return null;}

        return $query->first_row();
    }

    private function getDataOrder()
    {
        $sql = "SELECT 
                orders_item.id as iten_id,
                orders_item.qty as qty_iten,
                orders_item.product_id as product_id_iten,
                orders_item.amount as amount_iten_total,
                orders_item.rate as amount_iten,
                orders_item.variant as variant,
                orders_item.name as name_iten,
                orders_item.sku as sku_iten,
                orders_item.discount as discount_iten,
                orders_item.un as unity_iten,
                orders_item.pesobruto as gross_weight_iten,
                orders_item.largura as width_iten,
                orders_item.altura as height_iten,
                orders_item.profundidade as depth_iten,
                orders_item.unmedida as measured_unit_iten,
                freights.item_id,
                freights.ship_company,
                freights.ship_value,
                freights.date_delivered,
                freights.idservico, 
                freights.codigo_rastreio, 
                freights.method as method_freights, 
                freights.prazoprevisto, 
                orders.customer_address as address_order,
                orders.store_id as store_id,
                orders.company_id as company_id,
                orders.customer_address_uf as address_uf_order,
                orders.customer_address_zip as address_zip_order,
                orders.customer_phone as phone_order,
                orders.customer_address_num as address_num_order,
                orders.customer_address_neigh as address_neigh_order,
                orders.customer_name as customer_name_order,
                orders.customer_address_compl as address_compl_order,
                orders.customer_reference as address_reference_order,
                orders.customer_address_city as address_city_order,
                orders.total_ship,
                orders.frete_real,
                orders.data_limite_cross_docking,
                orders.discount as discount_total,
                orders.origin,
                DATE_FORMAT(orders.date_time,'%Y-%m-%d %H:%i:%s') as date_time_order,
                orders.gross_amount as gross_amount_order,
                orders.net_amount as net_amount_order,
                orders.numero_marketplace as marketplace_code,
                orders.data_pago as data_pago_order,
                orders.ship_company_preview as ship_company_order,
                orders.ship_companyName_preview as ship_companyName_order,
                orders.ship_service_preview as ship_service_preview,
                orders.ship_time_preview as ship_time_preview,
                orders.service_charge as service_charge_order,
                orders.service_charge_freight_value as service_charge_freight_value_order,
                (SELECT COALESCE(SUM(comission_reduction), 0) FROM `campaign_v2_orders` WHERE order_id = ?) as campaign_comission_reduction,
                orders.vat_charge as vat_charge_order,
                orders.total_order as total_products_order,
                orders.data_entrega,
                orders.data_envio,
                orders.freight_seller,
                orders.quote_id,
                orders.sales_model,
                orders.is_incomplete,
                orders.service_charge_rate,
                clients.id as id_client,
                clients.customer_address as address_client,
                clients.addr_uf as address_uf_client,
                clients.zipcode as address_zip_client,
                clients.phone_1 as phone1_client,
                clients.phone_2 as phone2_client,
                clients.addr_num as address_num_client,
                clients.addr_neigh as address_neigh_client,
                clients.customer_name as customer_name_client,
                clients.addr_compl as address_compl_client,
                clients.addr_city as address_city_client,
                clients.email as email_client, 
                clients.cpf_cnpj as cpf_cnpj,
                clients.ie as ie_client,
                clients.rg as rg_client,
                clients.birth_date as birth_date_client,
                nfes.date_emission as nfe_date_emission, 
                nfes.nfe_value, 
                nfes.nfe_serie, 
                nfes.nfe_num,
                nfes.link_nfe,
                nfes.chave as nfe_chave, 
                orders.paid_status as status_order,
                orders.data_mkt_delivered,
                orders.data_mkt_invoice,
                orders.data_mkt_sent,
                orders.coupon,
                products.product_id_erp,
                orders.sales_channel
                FROM orders 
                JOIN orders_item ON orders.id = orders_item.order_id 
                JOIN products ON orders_item.product_id = products.id 
                LEFT JOIN freights ON orders.id = freights.order_id 
                LEFT JOIN clients ON orders.customer_id = clients.id 
                LEFT JOIN nfes ON orders.id = nfes.order_id 
                WHERE orders.id = ?";

        if ($this->store_id > 0) {
            $sql .= " AND orders.store_id = ".(int)$this->store_id. " AND orders_item.store_id = ".(int)$this->store_id;
        }
        return $this->db->query($sql,array($this->cod_order,$this->cod_order));
    }

    private function getShipping()
    {
        $arrRs = array();

        $sql = "SELECT frete_ocorrencias.codigo, frete_ocorrencias.data_ocorrencia, frete_ocorrencias.nome, freights.item_id
                FROM freights 
                JOIN frete_ocorrencias ON freights.id = frete_ocorrencias.freights_id 
                WHERE freights.order_id = ?
                ORDER BY frete_ocorrencias.id ASC";
        $query = $this->db->query($sql,array($this->cod_order));
        
        if ($query->num_rows() === 0) {return $arrRs;}
        $result = $query->result_array();

        foreach ($result as $item) {
            array_push($arrRs, array(
                'code' => $item['codigo'],
                'date' => $item['data_ocorrencia'],
                'label' => $item['nome']
            ));
        }

        return $arrRs;
    }

    private function getDataPaymentOrder()
    {
        $sql = "SELECT * FROM orders_payment WHERE order_id = ?";
        return $this->db->query($sql, array($this->cod_order));
    }
    private function getPaymentId()
    {
        $sql = "SELECT id FROM orders_payment WHERE order_id = ?";
        $result = $this->db->query($sql, array($this->cod_order))->row();
        return $result ? $result->id : null;
    }
    private function getDataShipping($resultOrder)
    {
        $trackingCode = array();
        foreach ($resultOrder as $tracking) {
            if (in_array($tracking['codigo_rastreio'], $trackingCode)) {continue;}
            array_push($trackingCode, $tracking['codigo_rastreio']);
        }
        $order = $resultOrder[0];

        // Array com status de envio
        $arrStatusShip = $this->getShipping();
        
        // Data das ocorrências
        return array(
            "logistic_by" => $order['freight_seller'] == 1 ? "seller" : "marketplace",
            "shipping_cost" => $this->changeType($order['frete_real'], "float"),
            "shipping_carrier" => $order['ship_company_order'],
            "shipping_carrierName" => $order['ship_companyName_order'],
            "real_shipping_carrier" => $order['ship_company'],
            "status" => $arrStatusShip,
            "itemId" => $order['item_id'],
            "tracking_code" => count($trackingCode) > 1 ? $trackingCode : $trackingCode[0],
            "service_code" => $order['idservico'],
            "service_method" => $order['ship_service_preview'],
            "real_service_method" => $order['method_freights'],
            "shipped_date" => $order['data_envio'],
            "shipping_reported_date" => $order['data_mkt_delivered'] ?? '',
            "delivered_date" => $order['data_entrega'],
            "estimated_delivery" => !empty($order['ship_time_preview']) ? $this->somar_dias_uteis(($order['data_pago_order'] ?? $order['date_time_order']), $order['ship_time_preview']) : null,
            "estimated_delivery_days" => $order['ship_time_preview'],

            "freight_on_account" => "sender",
            "seller_shipping_cost" => $this->changeType($order['total_ship'], "float"),

            "cross_docking_deadline" => empty($order['data_limite_cross_docking']) ? null : date('Y-m-d', strtotime($order['data_limite_cross_docking'])),
            "quote_id" => $order['quote_id'],
            "shipping_address" => array(
                "full_name" => $order['customer_name_order'],
                "phone" => $order['phone_order'],
                "postcode" => $order['address_zip_order'],
                "street" => $order['address_order'],
                "number" => $order['address_num_order'],
                "complement" => $order['address_compl_order'],
                "reference" => $order['address_reference_order'],
                "neighborhood" => $order['address_neigh_order'],
                "city" => $order['address_city_order'],
                "region" => $order['address_uf_order'],
                "country" => "BR"
            ),
        );
    }

    private function update_shipped($data): array
    {
        $order = $this->model_orders->getOrdersData(0, $this->cod_order);

        $dataAtualizacaoTimestamp = $this->getDataAtualizacaoForcada($order['store_id'], $order['id'], $order['origin']);
            $isDelivered = !empty($data->shipment->isDelivered);

        if ($dataAtualizacaoTimestamp && $isDelivered){
            $config = $this->model_order_to_delivered->getTrackingByStoreAndMarketplace($order['store_id'], $order['origin']);

            if ($config) {
            $this->model_order_to_delivered->updateFlagOrder($order['id']);

            //date
            $data->shipment->shipped_date = $data->shipment->shipped_date ?: dateNow()->format(DATETIME_INTERNATIONAL);

            }
        }

        if (!isset($data->shipment)) {
        return array('error' => true, 'data' => $this->lang->line('api_not_shipment_key'));
        }
        if (count((array)$data->shipment) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }
        if (!isset($data->shipment->shipped_date)) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }
        if (isset($data->shipment->shipped_date) && !strtotime($data->shipment->shipped_date) && !empty($data->shipment->shipped_date)) {
            return array('error' => true, 'data' => $this->lang->line('api_shipped_date_correctly'));
        }

        // Loja não tem acesso ao pedido.
        if (!$this->checkStoreByProvider($order['store_id'], $order)) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }

        $this->store_id = $order['store_id'];
        $this->company_id = $order['company_id'];

        // se for cancelamento não precisa validar se a logística é do seller
        if (!$this->getStoreUseFreight() && !$this->getStoreUseFreightByProvider()) {
            return array('error' => true, 'data' => $this->lang->line('api_unauthorized_request'), 'http_code' => REST_Controller::HTTP_UNAUTHORIZED);
        }

        if (!$order || $order['data_envio'] != null){
            return array('error' => true, 'data' => $this->lang->line('api_no_accept_shipping_date'));
        }
        if ($order['paid_status'] != 43) {
            return array('error' => true, 'data' => $this->lang->line('api_order_cannot_shipped'));
        }
        if (empty(trim($data->shipment->shipped_date))) {
            return array('error' => true, 'data' => $this->lang->line('api_shipped_date_correctly'));
        }

        
        $shipped_date = date('Y-m-d H:i:s', strtotime($data->shipment->shipped_date));

        if(empty($orderDados['data_pago'])){
            $date_compara = strtotime($order['date_time']);
        }else{
            $date_compara = strtotime($order['data_pago']);
        }

        if(strtotime($shipped_date) < $date_compara){
            return array('error' => true, 'data' => $this->lang->line('api_shipped_date_incorrectly'), 'http_code' => REST_Controller::HTTP_OK);
        }
      
        $update = $this->model_orders->updateByOrigin($this->cod_order, array(
            'paid_status' => $order['in_resend_active'] ? 5 : 55,
            'data_envio' => $shipped_date
        ));

        if (!$update) {
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }
        return array('error' => false);
    }

    private function update_delivered($data): array
    {
        if (!isset($data->shipment)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_shipment_key'));
        }
        if (count((array)$data->shipment) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }
        if (!isset($data->shipment->delivered_date)) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }
        if (isset($data->shipment->delivered_date) && !strtotime($data->shipment->delivered_date) && !empty($data->shipment->delivered_date)) {
            return array('error' => true, 'data' => $this->lang->line('api_delivered_date_correctly'));
        }

        $order = $this->model_orders->getOrdersData(0, $this->cod_order);

        // Loja não tem acesso ao pedido.
        if (!$this->checkStoreByProvider($order['store_id'], $order)) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }

        $this->store_id = $order['store_id'];
        $this->company_id = $order['company_id'];

        // se for cancelamento não precisa validar se a logística é do seller
        if (!$this->getStoreUseFreight() && !$this->getStoreUseFreightByProvider()) {
            return array('error' => true, 'data' => $this->lang->line('api_unauthorized_request'), 'http_code' => REST_Controller::HTTP_UNAUTHORIZED);
        }

        if (!$order || $order['data_entrega'] != null){
            return array('error' => true, 'data' => $this->lang->line('api_no_accept_delivery_date'));
        }
        if ($order['paid_status'] != 45) {
            return array('error' => true, 'data' => $this->lang->line('api_order_cannot_delivered'));
        }
        if (empty(trim($data->shipment->delivered_date))) {
            return array('error' => true, 'data' => $this->lang->line('api_delivered_date_correctly'));
        }

        $delivered_date = date('Y-m-d H:i:s', strtotime($data->shipment->delivered_date));

        if(empty($orderDados['data_pago'])){
            $date_compara = strtotime($order['date_time']);
        }else{
            $date_compara = strtotime($order['data_envio']);
        }

        if(strtotime($delivered_date) < $date_compara){
            return array('error' => true, 'data' => $this->lang->line('api_delivered_date_incorrectly'), 'http_code' => REST_Controller::HTTP_OK);
        }

        $updateOrder = $this->model_orders->updateByOrigin($this->cod_order, array(
            'paid_status' => 60,
            'data_entrega' => $delivered_date
        ));

        $updateFreight = $this->model_freights->updateFreightsOrderId($this->cod_order, array(
            'date_delivered' => $delivered_date
        ));

        if (!$updateOrder || !$updateFreight) {
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }
        return array('error' => false);
    }

    private function update_canceled($data): array
    {
        $order = $this->model_orders->getOrdersData(0, $this->cod_order);
        $canCancel = true; // inicia como verdadeiro, pois caso seja fornecedor, sempre poderá cancelar.
        $canRequestCancel = false; // inicia como falso, pois caso seja fornecedor, sempre poderá cancelar, não solicitar.

        // Loja não tem acesso ao pedido.
        if (!$this->checkStoreByProvider($order['store_id'], $order)) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }

        $this->store_id = $order['store_id'];
        $this->company_id = $order['company_id'];

        // Se as lojas não são de fornecedor, deve validar as permissões.
        if (empty($this->stores_by_provider)) {
            // recuperar permissão no grupo do usuário, se pode solicitar um cancelamento
            $groupUser = $this->model_groups->getUserGroupByUserId($this->user_id);
            if (!$groupUser || !isset($groupUser['permission'])) {
                return array('error' => true, 'data' => $this->lang->line('api_user_group_not_found'));
            }

            // usuário sem permissão de cancelamento
            $canRequestCancel   = in_array('createRequestCancelOrder', unserialize($groupUser['permission']));
            $canCancel          = in_array('deleteOrder', unserialize($groupUser['permission']));
            if (!$canRequestCancel && !$canCancel) {
                return array('error' => true, 'data' => $this->lang->line('api_user_not_allowed_cancel'));
            }
        }

        $timeCancel = null;
        $settingTimeCancel = $this->model_settings->getSettingDatabyName('time_not_return_stock_cancel_order');
        if ($settingTimeCancel && $settingTimeCancel['status'] == 1) {
            $timeCancel = (int)$settingTimeCancel['value'];
        }

        if (!isset($data->order)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_order_key'));
        }
        if (count((array)$data->order) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }
        if (!isset($data->order->date)) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }
        if (isset($data->order->date) && !strtotime($data->order->date) && !empty($data->order->date)) {
            return array('error' => true, 'data' => $this->lang->line('api_date_needs_correctly'));
        }
        if (!isset($data->order->reason) || $data->order->reason == "") {
            return array('error' => true, 'data' => $this->lang->line('api_not_cancellation_reason'));
        }

        if (!$order || in_array($order['paid_status'], array(51,52,55,60,90,95,96,97,98,99))) {
            return array('error' => true, 'data' => $this->lang->line('api_cannot_canceled_order'));
        }
        // Inicia transação
        $this->db->trans_begin();

        if (empty(trim($data->order->date))) {
            return array('error' => true, 'data' => $this->lang->line('api_date_needs_correctly'));
        }

        $date   = date('Y-m-d H:i:s', strtotime($data->order->date));
        $reason = filter_var(trim($data->order->reason), FILTER_SANITIZE_STRING);

        if (empty($reason)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_cancellation_reason'));
        }

        $itens = $this->model_orders->getOrdersItemData($this->cod_order);

        if ($timeCancel === null || ($order['paid_status'] == 1 && time() < strtotime("+{$timeCancel} minutes", strtotime($order['date_time'])))) {
            foreach ($itens as $item) {
                $this->model_products->adicionaEstoque($item['product_id'], $item['qty'], $item['variant']);
            }
        }

        $data = array(
            'order_id'      => $this->cod_order,
            'reason'        => $reason,
            'store_id'      => $this->store_id,
            'company_id'    => $this->company_id,
            'user_id'       => empty($this->user_id) ? NULL : $this->user_id,
            'old_status'    => $order['paid_status']
        );

        $this->model_requests_cancel_order->create($data);

        $this->model_orders->updatePaidStatus($this->cod_order, $canCancel ? 99 : 90);

        if ($canCancel) {
            $this->model_orders->insertPedidosCancelados(array(
                'order_id'      => $this->cod_order,
                'reason'        => $reason,
                'date_update'   => $date,
                'status'        => 1,
                'penalty_to'    => $this->ordersmarketplace->getCancelReasonDefault(),
                'user_id'       => empty($this->user_id) ? 0 : $this->user_id,
                'store_id'      => empty($this->store_id) ? null : $this->store_id,
                'observation'   => 'Cancelado via API'
            ));
        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function createListOrder(): array
    {
        $filters                = $this->filters;
        $page                   = $filters['page'] ?? 1;
        $per_page               = $filters['per_page'] ?? 50;
        $start_date             = $filters['start_date'] ?? null;
        $end_date               = $filters['end_date'] ?? null;
        $status                 = $filters['status'] ?? null;
        $check_credit_card_fee  = $filters['check_credit_card_fee'] ?? false;
        $marketplace_number     = filter_var($filters['marketplace_number'] ?? null, FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
        $dataOrders             = array();
        $order_created_before   = $filters['order_created_before'] ?? null;
        $order_created_after  = $filters['order_created_after'] ?? null;

        $page       = filter_var($page, FILTER_VALIDATE_INT);
        $per_page   = filter_var($per_page, FILTER_VALIDATE_INT);

        if ($start_date) {
            $start_date = filter_var($start_date, FILTER_SANITIZE_STRING);
            // Data com quantidade de caracteres incorreta.
            if (strlen($start_date) != 10) {
                return array('error' => true, 'data' => $this->lang->line('api_incorrect_start_date'));
            }
            // data inexistente
            if (!strtotime($start_date)) {
                return array('error' => true, 'data' => $this->lang->line('api_incorrect_start_date'));
            }
        }

        if ($end_date) {
            $end_date = filter_var($end_date, FILTER_SANITIZE_STRING);
            // data com quatidade de caracteres incorreta
            if (strlen($end_date) != 10) {
                return array('error' => true, 'data' => $this->lang->line('api_incorrect_final_date'));
            }
            // data inexistente
            if (!strtotime($end_date)) {
                return array('error' => true, 'data' => $this->lang->line('api_incorrect_final_date'));
            }
        }

        // data inicial maior que a data final
        if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)){
            return array('error' => true, 'data' => $this->lang->line('api_final_date_greater'));
        }
        if ($page < 0) {
            $page = 1;
        }
        if ($per_page < 0) {
            $per_page = 1;
        }
        if ($per_page > 50) {
            $per_page = 50;
        }

        $filter_search = [];
        if (!is_null($page)) {
            $filter_search['page'] = $page;
        }
        if (!is_null($per_page)) {
            $filter_search['per_page'] = $per_page;
        }
        if (!is_null($start_date)) {
            $filter_search['start_date'] = $start_date;
        }
        if (!is_null($end_date)) {
            $filter_search['end_date'] = $end_date;
        }
        if (!is_null($status)) {
            $filter_search['status'] = $status;
        }
        if (!is_null($marketplace_number)) {
            $filter_search['marketplace_number'] = $marketplace_number;
        }
        $filter_search['check_credit_card_fee'] = $check_credit_card_fee;

        if (!empty($order_created_before)) {
            $dateBefore = DateTime::createFromFormat('d-m-Y', $order_created_before);

            if (!$dateBefore || $order_created_before != $dateBefore->format('d-m-Y')) {
                return array('error' => true, 'data' => "Formato aceito: 'dd-mm-YYYY'.");
            }

            $filter_search['order_created_before'] = $dateBefore->format('Y-m-d');
        }

        if (!empty($order_created_after)) {
            $dateAfter = DateTime::createFromFormat('d-m-Y', $order_created_after);

            if (!$dateAfter || $order_created_after != $dateAfter->format('d-m-Y')) {
                return array('error' => true, 'data' => "Formato aceito: 'dd-mm-YYYY'.");
            }

            $filter_search['order_created_after'] = $dateAfter->format('Y-m-d');
        }

        $orders = $this->model_orders_to_integration->getListOrders($this->getStoreToLists(), $this->company_id, $filter_search);
        foreach ($orders as $order) {
            $this->cod_order = (string)$order['id'];
            // se for fornecedor que gerencia lojas, preciso limpar o campo store_id
            if (!is_null($this->stores_by_provider)) {
                $this->store_id = 0;
            }
            $dataOrders[] = $this->createArrayOrder();
        }

        return array('error' => false, 'registers_count' => count($orders), 'pages_count' => $this->getCountListOrders($filter_search), 'page' => $page, 'data' => $dataOrders);
    }

    private function getCountListOrders($filter_search)
    {
        $per_page               = $filter_search['per_page'] ?? null;
        $start_date             = $filter_search['start_date'] ?? null;
        $end_date               = $filter_search['end_date'] ?? null;
        $status                 = $filter_search['status'] ?? null;
        $marketplace_number     = $filter_search['marketplace_number'] ?? null;
        $check_credit_card_fee  = $filter_search['check_credit_card_fee'] ?? false;

        $this->db
            ->select('count(o.id) as count')
            ->from('orders AS o USE INDEX (ix_orders_01)');
       
        if ($this->store_id > 0) {
            if ($this->stores_by_provider) {
                $this->db->where_in('o.store_id', $this->stores_by_provider);
            } else {
                $this->db->where(
                    array(
                        'o.store_id' => $this->store_id,
                        'o.company_id' => $this->company_id
                    )
                );
            }
        }        

        if ($start_date) {// existe data inicial, fazer o filtro
            $this->db->where("DATE_FORMAT(o.date_time,'%Y-%m-%d %H:%i:%s') >= ",$start_date.' 00:00:00');
        }
        if ($end_date) {// existe data final, fazer o filtro
            $this->db->where("DATE_FORMAT(o.date_time,'%Y-%m-%d %H:%i:%s') <= ",$end_date.' 23:59:59');
        }
        if ($status) {// existe status, fazer o filtro
            $status = explode(',', $status);
            $this->db->where_in('o.paid_status', $status);
        }
        if ($marketplace_number) {// existe filtro pelo código do pedido no marketplace
            $this->db->where('o.numero_marketplace', $marketplace_number);
        }
        if ($check_credit_card_fee) {
            $this->db->join('orders_payment AS op', 'op.order_id = o.id');
            $this->db->where('op.taxa_cartao_credito IS NOT NULL', NULL, FALSE);
        }

        $count = $this->db->get()->row_array()['count'] / $per_page;

        return $count === (int)$count ? $count : (int)$count + 1;
    }

    private function format_date($date_string){
        $data_config = DateTime::createFromFormat('d/m/Y H:i:s', $date_string);
        if (!$data_config) {
            return date('Y-m-d H:i:s', strtotime($date_string));
        } else {
            return date_format($data_config, 'Y-m-d H:i:s');
        }
    }

    private function getCommissionReduction($productId)
    {

        $query = "SELECT new_comission 
                    FROM campaign_v2_products 
                    JOIN campaign_v2_orders_campaigns ON (campaign_v2_orders_campaigns.campaign_id = campaign_v2_products.campaign_v2_id)
                    WHERE (campaign_v2_orders_campaigns.total_reduced <> '' OR campaign_v2_orders_campaigns.total_rebate <> '')
                    AND campaign_v2_orders_campaigns.order_id = ? AND product_id = ?";
        $result = $this->db->query($query,array($this->cod_order, (string)$productId));

        return $result->num_rows() ? $result->row_array()['new_comission'] : 0;

    }

	private function getCampaignDataByProductId($product_id, $order_id, $int_to)
	{
        // Verifica tipo de desconto e tipo de campanha
		$sql = "SELECT
                    (oi.amount / oi.qty) + oi.discount AS current_price,
                    (oi.amount / oi.qty) + cvo.total_pricetags AS campaign_price,
                    (coi.total_discount / oi.qty) AS campaign_discount,
                    CASE
                        WHEN cp.discount_type='discount_percentage' THEN 'percent'
                        ELSE 'value'
                    END AS discount_type,
                    CASE
                        WHEN cp.discount_type='discount_percentage' THEN 
                            CASE
                                WHEN cv.campaign_type='merchant_discount' THEN cp.discount_percentage
                                ELSE cp.seller_discount_percentual
                            END
                        ELSE cp.seller_discount_fixed
                    END AS seller_discount,
                    CASE
                        WHEN cp.discount_type='discount_percentage' THEN
                            CASE
                                WHEN cv.campaign_type='channel_funded_discount' THEN cp.discount_percentage
                                ELSE cp.marketplace_discount_percentual
                            END
                        ELSE cp.marketplace_discount_fixed
                    END AS marketplace_discount,
                    CASE
                        WHEN cp.discount_type='discount_percentage' 
                            THEN cp.discount_percentage
                        ELSE cp.fixed_discount
                    END AS total_discount,
                    cvo.total_pricetags AS coupon_discount
                FROM campaign_v2_products cp
                JOIN campaign_v2 cv ON cv.id = cp.campaign_v2_id
                LEFT JOIN campaign_v2_orders_items coi ON cp.campaign_v2_id = coi.campaign_v2_id
                JOIN orders_item oi ON oi.id = coi.item_id
                JOIN campaign_v2_orders cvo ON cvo.order_id = oi.order_id
                WHERE cp.product_id = " . $product_id . "
                AND coi.item_id = " . $order_id . "
                AND cp.int_to = '" . $int_to . "'
                ";

		$result = $this->db->query($sql);
		return $result->row_array();
	}

    /**
     * Valida se o marketplace realmente existe, se fornecedor existe e se o forncedor por operar com esse marketplace.
     *
     * @param   string      $marketplace
     * @throws  Exception
     */
    private function validateMarketplaceCreateOrder(string $marketplace)
    {
        // marketplace não encontrado.
        if (!$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $marketplace)) {
            throw new Exception("Makretplace '$marketplace' não localizado.");
        }

        // fornecedor não encontrado.
        $providerId = array_change_key_case(getallheaders())['x-provider-key'] ?? 0;
        if (!$provider = $this->model_providers->getProviderData($providerId)) {
            throw new Exception("Fornecedor '$providerId' não localizado.");
        }

        // fornecedor não pode publicar no marketplace.
        if ($provider['marketplace'] != $integration['id']) {
            throw new Exception("Fornecedor '$providerId' sem permissão para gerenciar o marketplace '$marketplace'.");
        }
    }

    /**
     * Busca os históricos de atualização dos pedidos.
     *
     * @param   string      $marketplace
     * @throws  Exception
     */
    private function getDataHistoric($cod_order)
    {
        $sql = "SELECT * FROM order_status WHERE order_id = ? ORDER BY id";
        return $this->db->query($sql,array($cod_order));
    }

    /**
     * Alterar status do campo new_order para 0.
     *
     * @return false[]
     */
    private function changeStatusNewOrder(): array
    {
        $orders = $this->model_orders_to_integration->getDataOrdersInteg($this->getStoreToLists(), $this->tokenMaster, $this->cod_order)->result_array();

        $order_to_change_status_new_order = array_filter($orders, function($order) {
            return $order['new_order'];
        });

        if (empty($order_to_change_status_new_order)) {
            return array('error' => false);
        }

        $order_to_change_status_new_order = $order_to_change_status_new_order[0];

        $this->model_orders_to_integration->update($order_to_change_status_new_order['id'], array('new_order' => 0), $this->tokenMaster);

        return array('error' => false);
    }
}
