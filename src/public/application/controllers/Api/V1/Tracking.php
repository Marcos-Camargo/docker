<?php

require APPPATH . "controllers/Api/V1/API.php";
require_once "application/libraries/CalculoFrete.php";

/**
 * @property Model_settings $model_settings
 * @property Model_order_to_delivered $model_order_to_delivered
 */

class Tracking extends API
{
    private $order;
    private $tracking_url_default;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_settings');
        $this->load->model('model_order_to_delivered');

        $this->setTrackingUrlDefault();
    }

    public function setTrackingUrlDefault()
    {
        $this->tracking_url_default = $this->model_settings->getValueIfAtiveByName('tracking_url_default');
    }

    public function index_get($order = null)
    { 
        $order = xssClean($order);
        if (!$order) {
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_order_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $this->order = (int) $order;

        $result = $this->createArrayTracking();

        $this->response($result, $result['success'] ? REST_Controller::HTTP_OK : REST_Controller::HTTP_BAD_REQUEST);
    }

    public function index_post($order = null)
    {
        $order = xssClean($order);
        if (!$order) {
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_order_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $this->order = (int)$order;

        // Recupera dados enviado pelo body
        $data = $this->inputClean();
        $body_api = json_decode($this->security->xss_clean($this->input->raw_input_stream));
        if (
            !empty($body_api) &&
            is_object($body_api) &&
            property_exists($body_api, 'tracking') &&
            is_object($body_api->tracking) &&
            property_exists($body_api->tracking, 'track') &&
            is_object($body_api->tracking->track) &&
            property_exists($body_api->tracking->track, 'url') &&
            is_string($body_api->tracking->track->url)
        ) {
            $data->tracking->track->url = filter_var($body_api->tracking->track->url, FILTER_SANITIZE_URL);
        }

        $create = $this->insertTracking($data);

        $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, "STR_PAYLOAD=".file_get_contents('php://input')."\nRETURN=".json_encode($create)."\nPAYLOAD=".json_encode($data)."\n");

        $this->response(array('success' => !$create['error'], "message" => $create['data'] ?? $this->lang->line('api_tracking_successfully')), $create['error'] ? (isset($create['unauthorized']) && $create['unauthorized'] == true ? REST_Controller::HTTP_UNAUTHORIZED : REST_Controller::HTTP_NOT_FOUND) : REST_Controller::HTTP_CREATED);
    }

    public function occurrence_post($order = null, $trackingCode = null)
    {
        if (!$trackingCode) {
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_tracking_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        if (!$order) {
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_order_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $this->order = $order;

        // Recupera dados enviado pelo body
        $data = $this->inputClean();

        $create = $this->insertOccurrence($data, $trackingCode);

        $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, "STR_PAYLOAD=".file_get_contents('php://input')."\nRETURN=".json_encode($create)."\nPAYLOAD=".json_encode($data)."\n");

        $this->response(array('success' => !$create['error'], "message" => $create['data'] ?? $this->lang->line('api_occurrence_successfully')), $create['error'] ? (isset($create['unauthorized']) && $create['unauthorized'] == true ? REST_Controller::HTTP_UNAUTHORIZED : REST_Controller::HTTP_NOT_FOUND) : REST_Controller::HTTP_CREATED);
    }

    private function createArrayTracking(): array
    {
        $order = $this->model_orders->getOrdersData(0, $this->order);

        // Loja não tem acesso ao pedido.
        if (!$this->checkStoreByProvider($order['store_id'], $order)) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }

        $tracking = $this->getDataTracking();

        if (count($tracking) == 0) {
            return array('success' => false, 'result' => $this->lang->line('api_no_results_where'));
        }

        $order = $tracking[0];

        $codesTracking = array();
        $labels = array();

        $key = get_instance()->config->config['encryption_key'];

        foreach ($tracking as $codeTRacking) {
            if (in_array($codeTRacking['codigo_rastreio'], $codesTracking) || empty($codeTRacking['codigo_rastreio'])) {
                continue;
            }

            if (empty($codeTRacking['link_etiqueta_a4'])) {

                $tokenLabel = $this->jwt->encode(array(
                    'orders' => [ $order['order_id'] ],
                    'iat' =>  time(),
                    'exp' => time() + 60 * 60 * 24 // 24h
                ), $key);

                $codeTRacking['link_etiqueta_a4'] = base_url("Tracking/printLabel/$tokenLabel");
            }

            $labels[] = array(
                "file_a4"           => $codeTRacking['link_etiqueta_a4'],
                "file_thermal"      => $codeTRacking['link_etiqueta_termica'],
                "file_zpl"          => $codeTRacking['link_etiquetas_zpl'],
                "file_plp"          => $codeTRacking['link_plp'],
                "tracking_code"     => $codeTRacking['codigo_rastreio'],
                "number_plp"        => $codeTRacking['number_plp'] ?? null,
                "tracking_url"      => $codeTRacking['url_tracking'] ?? null
            );

            $codesTracking[] = $codeTRacking['codigo_rastreio'];
        }

        return array('success' => true, 'result' => array(
            'order_code'            => (int)$order['order_id'],
            'date_cross_docking'    => $order['data_limite_cross_docking'],
            'ship_company'          => $order['ship_company'],
            'ship_company_cnpj'     => $order['CNPJ'],
            'ship_service'          => $order['method'],
            'ship_value'            => (float)$order['frete_real'],
            'expected_delivery_date'=> $order['prazoprevisto'],
            "ship_address" => array(
                "full_name"         => $order['customer_name'],
                "phone"             => $order['customer_phone'],
                "street"            => $order['customer_address'],
                "number"            => $order['customer_address_num'],
                "postcode"          => $order['customer_address_zip'],
                "complement"        => $order['customer_address_compl'] ?? "",
                "neighborhood"      => $order['customer_address_neigh'],
                "city"              => $order['customer_address_city'],
                "region"            => $order['customer_address_uf'],
                "country"           => "BR"
            ),
            "tracking" => array(
                "date_label"        => $order['data_etiqueta'],
                "file_a4"           => $order['link_etiqueta_a4'],
                "file_thermal"      => $order['link_etiqueta_termica'],
                "file_zpl"          => $order['link_etiquetas_zpl'],
                "file_plp"          => $order['link_plp'],
                "tracking_code"     => $codesTracking,
                "number_plp"        => $order['number_plp'] ?? null,
                "tracking_url"      => $order['url_tracking'] ?? null
            ),
            "label" => $labels,
            "tracking_history"  => $this->getStatusTracking()
        ));
    }

    private function getDataTracking()
    {
        $sql = "SELECT orders.*, freights.*, correios_plps.number_plp  
                FROM orders 
                JOIN freights ON orders.id = freights.order_id 
                LEFT JOIN correios_plps ON orders.id = correios_plps.order_id 
                WHERE orders.id = ? AND orders.store_id = ?";

        if ($this->getStoreUseFreight()) {
            $sql = "SELECT * FROM orders 
            JOIN freights ON orders.id = freights.order_id 
            WHERE orders.id = ? AND orders.store_id = ?";
        }

        $query = $this->db->query($sql, array($this->order, $this->store_id));
        return $query->result_array();
    }

    private function getStatusTracking()
    {
        $arrRs = array();

        $sql = "SELECT frete_ocorrencias.codigo, frete_ocorrencias.data_ocorrencia, frete_ocorrencias.nome 
                FROM freights 
                JOIN frete_ocorrencias ON freights.id = frete_ocorrencias.freights_id 
                WHERE freights.order_id = ?
                ORDER BY frete_ocorrencias.id ASC";
        $query = $this->db->query($sql, array($this->order));

        if ($query->num_rows() === 0) return $arrRs;
        $result = $query->result_array();

        foreach ($result as $item) {
            array_push($arrRs, array(
//                'code' => $item['codigo'],
                'date' => $item['data_ocorrencia'],
                'label' => $item['nome']
            ));
        }

        return $arrRs;
    }

    private function insertTracking($data): array
    {
        $order = $this->model_orders->getOrdersData(0, $this->order);

        // Loja não tem acesso ao pedido.
        if (!$this->checkStoreByProvider($order['store_id'], $order)) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }

        $this->store_id = $order['store_id'];
        $this->company_id = $order['company_id'];

        if (!$this->getStoreUseFreight() && !$this->getStoreUseFreightByProvider()) {
            return array('error' => true, 'data' => $this->lang->line('api_unauthorized_request'), 'unauthorized' => true);
        }

        if (!$order) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }
        
        if ($this->getCodeInfo('freights', 'order_id', $this->order, "AND company_id=$this->company_id")) {
            return array('error' => true, 'data' => $this->lang->line('api_order_already_tracking'));
        }

        if ($order['paid_status'] != 40) {
            return array('error' => true, 'data' => $this->lang->line('api_order_cannot_tracking_40'));
        }

        if (!isset($data->tracking)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_tracking_key'));
        }
        if (count((array)$data->tracking) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_create'));
        }
        if (count((array)$data->tracking->items) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_create'));
        }

        $track = $data->tracking->track;
        $items = $data->tracking->items;

        // Inicia transação
        $this->db->trans_begin();

        foreach ($items as $item) {
            
            
            if (!isset($item->sku) || $item->sku === "") {
                return array('error' => true, 'data' => $this->lang->line('api_item_sku_not_informed'));
            }
            if (!isset($item->qty) || $item->qty <= 0) {
                return array('error' => true, 'data' => $this->lang->line('api_item_qty_zero'));
            }

            $dataAtualizacaoTimestamp = $this->getDataAtualizacaoForcada($this->store_id, $this->order, $order['origin']);
            $isDelivered = !empty($data->tracking->isDelivered);

            if ($dataAtualizacaoTimestamp && $isDelivered){
                $config = $this->model_order_to_delivered->getTrackingByStoreAndMarketplace($this->store_id, $order['origin']);

                if ($config) {
                $this->model_order_to_delivered->updateFlagOrder($this->order);
                $orderConfig = $this->model_order_to_delivered->getConfigById($config['order_to_delivered_config_id']);

                //date
                $data->tracking->date_tracking = $data->tracking->date_tracking ?: dateNow()->format(DATETIME_INTERNATIONAL);

                //value
                $item->value = $item->value ?: 0;

                //serverId
                $item->service_id = $item->service_id ?: 0;

                //url de Rastreio
                $track->url = $track->url ?: $orderConfig['url_rastreio'];

                //cod de Rastreio
                $item->code = $item->code ?: $orderConfig['codigo_rastreio'];
                
                //metodo de envio
                $item->method = $item->method ?: $orderConfig['metodo_envio'];
        
                //transportadora
                $track->carrier = $track->carrier ?: $orderConfig['transportadora'];
                }
            }

            if(empty($item->code) || !isset($item->code) || $item->code === ""){

                if ($this->model_settings->getStatusbyName('shipping_code_default') == 1){
                    $item->code = $this->model_settings->getValueIfAtiveByName('shipping_code_default');
                }
                else{
                        return array('error' => true, 'data' => $this->lang->line('api_item_tracking_not_informed'));
                }
            }
            if (!isset($item->value) || (float)$item->value < 0) {
                return array('error' => true, 'data' => $this->lang->line('api_item_value_zero'));
            }
            if (!isset($item->service_id) || $item->service_id === "") {
                return array('error' => true, 'data' => $this->lang->line('api_item_service_id'));
            }

            if (empty($track->url)) {
                $track->url = $this->tracking_url_default;
                if (empty($track->url)) {
                    return array('error' => true, 'data' => $this->lang->line('api_url_not_informed'));
                }
            }

            $date_tracking = date('Y-m-d H:i:s', strtotime($data->tracking->date_tracking));
            $url_tracking = filter_var($track->url, FILTER_SANITIZE_STRING);

            if (!isset($data->tracking->date_tracking) || empty($data->tracking->date_tracking) || !strtotime($data->tracking->date_tracking)) {
                return array('error' => true, 'data' => $this->lang->line('api_date_tracking_misinformed'));
            }

            if (isset($item->delivery_date) && !strtotime($item->delivery_date) && !empty($item->delivery_date)) {
                return array('error' => true, 'data' => $this->lang->line('api_delivery_date_correctly'));
            }

            if (empty($track->carrier)) {
                return array('error' => true, 'data' => $this->lang->line('api_carrier_not_informed'));
            }

            if (!$url_tracking) return array('error' => true, 'data' => $this->lang->line('api_url_valid'));

            if (!isset($item->method) || $item->method === "") {
                return array('error' => true, 'data' => $this->lang->line('api_item_method'));
            }
            $delivery_date = '';
            if (!empty($item->delivery_date)) {
                $delivery_date = date('Y-m-d', strtotime($item->delivery_date));
            }

            $product_id = $this->getCodeInfo('products', 'sku', $item->sku, "AND store_id={$this->store_id}");

            // não encontro sku no produto
            if (!$product_id) {
                $product_id = $this->getCodeInfo('orders_item', 'sku', $item->sku, "AND order_id={$this->order}", 'product_id');

                // não encontro sku no orders_item
                if (!$product_id) {
                    $product_id = $this->getCodeInfo('prd_variants JOIN products ON products.id = prd_variants.prd_id', 'prd_variants.sku', $item->sku, "AND products.store_id=$this->store_id", 'prd_variants.prd_id');

                    // não encontro sku no prd_variants
                    if (!$product_id) {
                        return array('error' => true, 'data' => "sku ({$item->sku}) was not found");
                    }
                }
            }

            $this->model_freights->create(
                array(
                    'order_id'              => $this->order,
                    'item_id'               => $product_id,
                    'company_id'            => $this->company_id,
                    'ship_company'          => $track->carrier,
                    'status_ship'           => 0,
                    'date_delivered'        => '',
                    'ship_value'            => (float)$item->value,
                    'prazoprevisto'         => $delivery_date,
                    'idservico'             => $item->service_id,
                    'codigo_rastreio'       => $item->code,
                    'link_etiqueta_a4'      => empty($item->url_label_a4) ? null : $item->url_label_a4,
                    'link_etiqueta_termica' => empty($item->url_label_thermic) ? null : $item->url_label_thermic,
                    'link_etiquetas_zpl'    => empty($item->url_label_zpl) ? null : $item->url_label_zpl,
                    'link_plp'              => empty($item->url_plp) ? null : $item->url_plp,
                    'data_etiqueta'         => $date_tracking,
                    'CNPJ'                  => empty($item->carrier_cnpj) ? null : $track->carrier_cnpj,
                    'method'                => $item->method,
                    'solicitou_plp'         => 0,
                    'sgp'                   => 0,
                    'url_tracking'          => $url_tracking,
                    'in_resend_active'      => $order['in_resend_active']
                )
            );
        }

        $hire_automatic_freight = $this->model_settings->getValueIfAtiveByName('hire_automatic_freight');

        $label_required = false;
        if (!empty($this->logistic['type'])) {
            $integration_logistic_id = $this->logistic['shipping_id'];
            if ($integration_logistic_id) {
                $integration_logistic = $this->model_integration_logistic->getIntegrationsById($integration_logistic_id);
                $external_integration_id = $integration_logistic['external_integration_id'];
                if ($external_integration_id) {
                    $integration_erp = $this->model_integration_erps->getById($external_integration_id);
                    $label_required = (bool)$integration_erp->label_required;
                }
            }
        }

        // Se é fornecedor deve enviar o pedido para aguardando emissão de etiqueta, pois a ação de mudança de status acontecerá quando o usuário fizer a impressão da etiqueta.
        $this->model_orders->updatePaidStatus($this->order,
            ($this->is_provider && $label_required) || ($this->is_provider && !$hire_automatic_freight && !$order['freight_accepted_generation']) ?
            $this->model_orders->PAID_STATUS['waiting_issue_label'] :
            (
                $order['in_resend_active'] ? $this->model_orders->PAID_STATUS['awaiting_pickup_or_shipping'] : $this->model_orders->PAID_STATUS['sent_trace_to_marketplace']
            )
        );

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    /**
     * Consulta string em uma parte de outra string
     *
     * @param   string  $needle     Valor a ser procurado
     * @param   string  $haystack   Valor real para comparação
     * @return  bool                Retorna o status da consulta
     */
    public function likeText($needle, $haystack)
    {
        $regex = '/' . str_replace('%', '.*?', $needle) . '/';

        return preg_match($regex, $haystack) > 0;
    }

    /**
     * Adiciona ocorrência no rastreio do pedido
     *
     * @param   object          $data           Dados da ocorrência
     * @param   string          $trackingCode   Código de rastreio
     * @return  array|false[]
     */
    private function insertOccurrence(object $data, string $trackingCode): array
    {
        $order = $this->model_orders->getOrdersData(0, $this->order);

        // Loja não tem acesso ao pedido.
        if (!$this->checkStoreByProvider($order['store_id'], $order)) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }

        if (!$this->getStoreUseFreight()) {
            return array('error' => true, 'data' => $this->lang->line('api_unauthorized_request'), 'unauthorized' => true);
        }

        if (!$order) {
            return array('error' => true, 'data' => $this->lang->line('api_order_not_found'));
        }
        if (!isset($data->occurrence)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_tracking_key_occurrence'));
        }
        if (!in_array($order['paid_status'], array(5,45,55))) {
            return array('error' => true, 'data' => $this->lang->line('api_order_5_55'));
        }
        if (!isset($data->occurrence->occurrence) || empty($data->occurrence->occurrence)) {
            return array('error' => true, 'data' => $this->lang->line('api_name_occurrence'));
        }
        if (!isset($data->occurrence->date) || empty($data->occurrence->date) || !strtotime($data->occurrence->date)) {
            return array('error' => true, 'data' => $this->lang->line('api_date_occurrence_not_informed'));
        }

        $date = date('Y-m-d H:i:s', strtotime($data->occurrence->date));
        $message = filter_var($data->occurrence->occurrence, FILTER_SANITIZE_STRING);

        $frete = $this->model_freights->getFreightForCodeTracking($this->order, $trackingCode);

        if (!$frete) {
            return array('error' => true, 'data' => $this->lang->line('api_Tracking_not_found'));
        }

        $occurrence = $this->model_frete_ocorrencias->getOcorrenciasByFreightIdName($frete['id'], $message);

        if ($occurrence) {
            return array('error' => true, 'data' => $this->lang->line('api_existing_occurrence'));
        }

        $data->occurrence->place        = isset($data->occurrence->place) && !empty($data->occurrence->place) ? filter_var($data->occurrence->place, FILTER_SANITIZE_STRING) : null;
        $data->occurrence->street       = isset($data->occurrence->street) && !empty($data->occurrence->street) ? filter_var($data->occurrence->street, FILTER_SANITIZE_STRING) : null;
        $data->occurrence->number       = isset($data->occurrence->number) && !empty($data->occurrence->number) ? filter_var($data->occurrence->number, FILTER_SANITIZE_STRING) : null;
        $data->occurrence->zipcode      = isset($data->occurrence->zipcode) && !empty($data->occurrence->zipcode) ? filter_var(preg_replace('/\D/', '', $data->occurrence->zipcode), FILTER_SANITIZE_NUMBER_INT) : null;
        $data->occurrence->neighborhood = isset($data->occurrence->neighborhood) && !empty($data->occurrence->neighborhood) ? filter_var($data->occurrence->neighborhood, FILTER_SANITIZE_STRING) : null;
        $data->occurrence->city         = isset($data->occurrence->city) && !empty($data->occurrence->city) ? filter_var($data->occurrence->city, FILTER_SANITIZE_STRING) : null;
        $data->occurrence->state        = isset($data->occurrence->state) && !empty($data->occurrence->state) ? filter_var($data->occurrence->state, FILTER_SANITIZE_STRING) : null;

        // array para gravar a ocorrência
        $freightOccurrence = array(
            'freights_id'       => $frete['id'],
            'codigo'            => 0,
            'nome'              => $message,
            'data_ocorrencia'   => $date,
            'data_atualizacao'  => $date,
            'mensagem'          => $message,
            'addr_place'        => $data->occurrence->place,
            'addr_name'         => $data->occurrence->street,
            'addr_num'          => $data->occurrence->number,
            'addr_cep'          => $data->occurrence->zipcode,
            'addr_neigh'        => $data->occurrence->neighborhood,
            'addr_city'         => $data->occurrence->city,
            'addr_state'        => $data->occurrence->state
        );

        $this->model_frete_ocorrencias->create($freightOccurrence);
        $this->model_orders->updateByOrigin($this->order, array('last_occurrence' => $message));

        return array('error' => false);
    }
}