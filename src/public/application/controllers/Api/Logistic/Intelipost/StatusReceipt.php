<?php

require APPPATH . "libraries/REST_Controller.php";

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Router $router
 * @property CI_Security $security
 * @property CI_Input $input
 *  
 * @property Model_stores $model_stores
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property logistic $logistic
 * @property CalculoFrete $calculofrete
 */
class StatusReceipt extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_stores');
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_nfes');

        $this->load->library('calculoFrete');
    }

    public $codeTracking;
    public $urlTracking;
    public $freightId;

    /**
     * Atualização rastreio, deve ser recebido via POST.
     */
    public function index_put()
    {
        $status = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou PUT, não deveria - GET='.json_encode($_GET).' - PAYLOAD='.json_encode($status), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização rastreio, deve ser recebido via POST.
     */
    public function index_get()
    {
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou GET, não deveria - GET='.json_encode($_GET), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização rastreio.
     */
    public function index_post()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;    
        $status = json_decode($this->security->xss_clean($this->input->raw_input_stream));
        $this->log_data('batch', $log_name, "Chegou request intelipost.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()));

        // GET header api-key
        $headers = getallheaders();
        $apiKey = $headers['api-key'] ?? $headers['Api-Key'] ?? null;
        $this->apiKey = $apiKey;
        if (!$apiKey) {
            $this->log_data('batch', $log_name, "Api-key não encontrada.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response('api-key not found',REST_Controller::HTTP_UNAUTHORIZED);
        }

        // valida api-key seller/seller center
        $storeApiKey = $this->model_stores->validApikeyIntelipost($apiKey, $status->order_number);
        if ($storeApiKey === false) {
            $this->log_data('batch', $log_name, "Api-key não está relacionado a algum cliente.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response('api-key not match',REST_Controller::HTTP_UNAUTHORIZED);
        }

        $history = $status->history;

        try {
            $store = $this->model_stores->getStoreById($storeApiKey);
            $logistic = $this->calculofrete->getLogisticStore(array(
                'freight_seller' 		=> $store['freight_seller'],
                'freight_seller_type' 	=> $store['freight_seller_type'],
                'store_id'				=> $store['id']
            ));
            $this->calculofrete->instanceLogistic($logistic['type'], $store['id'], $store, $logistic['seller']);
        } catch (InvalidArgumentException $exception) {
            return $this->response($exception->getMessage(),REST_Controller::HTTP_BAD_REQUEST);
        }

        $trackingCode   = $status->tracking_code;
        $orderNumber    = $status->order_number;
        $volumeNumber   = $status->volume_number;

        if ($history->shipment_order_volume_state == 'NEW') {
            $this->log_data('batch', $log_name, "chegou NEW ignorou.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()));
            return $this->response(null, REST_Controller::HTTP_OK);
        }
  
        $freightByTrackingCode = $this->model_freights->getOrderIdForCodeTracking($trackingCode);

        if (empty($freightByTrackingCode)) {
            $freights = $this->model_freights->getFreightsByShippingOrder($orderNumber);

            // Rastreio não encontrado.
            if (!count($freights)) {
                $this->log_data('batch', $log_name, "Não foram contrados rastreios para o pedido.\n\n body=" . json_encode($status) . "\n\n header=" . json_encode(getallheaders()), "E");
                return $this->response('order number not found', REST_Controller::HTTP_BAD_REQUEST);
            }

            $freight = $freights[$volumeNumber - 1];
        } else {
            $freight = $freightByTrackingCode;
        }

        $this->freightId  = $freight['id'];
        $this->codeTracking = ($trackingCode != $freight['codigo_rastreio']) ? $trackingCode : $freight['codigo_rastreio'];
        $this->urlTracking  =  ($status->tracking_url != $freight['url_tracking']) ? $status->tracking_url : $freight['url_tracking'];
        
        $order = $this->model_orders->getOrdersData(0, $freight['order_id']);

        if (is_numeric($storeApiKey) && $storeApiKey !== 0 && $storeApiKey !== $order['store_id']) {
            $this->log_data('batch', $log_name, "Loja não pertence a api-key.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response('Loja não pertence a api-key', REST_Controller::HTTP_UNAUTHORIZED);
        }
            //SHIPPED STATUS QUE VIRA
        if (in_array($history->shipment_order_volume_state, array('NEW', 'LABEL_CREATED', 'CANCELLED'))) {
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        if (in_array($history->shipment_order_volume_state, array('SHIPPED'))) {

            $nfe = $this->model_nfes->getNfesDataByOrderId($order['id'])[0];
            $this->logistic->getAllLabelIntelipost($order, $nfe);
            
            $history->shipment_volume_micro_state->name = 'Aguardando Seller Emitir Etiqueta';
        }

        ob_start();
        $this->calculofrete->logistic->setNewRegisterOccurrence(
            array(
                'name'              => $history->shipment_volume_micro_state->name,
                'description'       => $history->shipment_volume_micro_state->description,
                'code'              => $history->shipment_volume_micro_state->code,
                'code_name'         => $history->shipment_order_volume_state,
                'type'              => '',
                'date'              => date('Y-m-d H:i:s', strtotime($history->created_iso)),
                'statusOrder'       => $order['paid_status'],
                'freightId'         => $freight['id'],
                'orderId'           => $order['id'],
                'trackingCode'      => $this->codeTracking,
                'address_place'     => $history->location->local        ?? null,
                'address_name'      => $history->location->address      ?? null,
                'address_number'    => $history->location->number       ?? null,
                'address_zipcode'   => $history->location->zip_code     ?? null,
                'address_neigh'     => $history->location->quarter      ?? null,
                'address_city'      => $history->location->city         ?? null,
                'address_state'     => $history->location->state_code   ?? null
            )
        );

        ob_clean();
     
        return $this->response(null, REST_Controller::HTTP_OK);
        
    }    
}
