<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/CalculoFrete.php";

class StatusReceipt extends REST_Controller
{
    private $calculoFrete;
    private $apiKey;
    private $trackingCode;
    private $orderNumber;
    private $volumeNumber;
    private $orderId;
    private $freightId;
    private $paidStatus;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_stores');
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_settings');

        $this->calculoFrete = new CalculoFrete();
    }

    /**
     * Atualização de estoque, deve ser recebido via POST
     */
    public function index_put()
    {
        $status = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou PUT, não deveria - GET='.json_encode($_GET).' - PAYLOAD='.json_encode($status), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de estoque, deve ser recebido via POST
     */
    public function index_get()
    {
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou GET, não deveria - GET='.json_encode($_GET), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de estoque
     */
    public function index_post()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $status = json_decode(file_get_contents('php://input'));

        $this->log_data('batch', $log_name, "Chegou request intelipost.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "I");

        // GET header api-key
        $this->apiKey = getallheaders()['api-key'] ?? null;
        if (!$this->apiKey) {
            $this->log_data('batch', $log_name, "Api-key não encontrada.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response('api-key not found',REST_Controller::HTTP_UNAUTHORIZED);
        }

        // valida api-key seller/seller center
        if (!$this->model_stores->validApikeyIntelipost($this->apiKey)) {
            $this->log_data('batch', $log_name, "Api-key não está relacionado a algum cliente.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response('api-key not match',REST_Controller::HTTP_UNAUTHORIZED);
        }

        $storeApiKey = $this->model_stores->getStoreByApikeyIntelipost($this->apiKey);

        $history = $status->history;

        $this->setTrackingCode($status->tracking_code);
        $this->setOrderNumber($status->order_number);
        $this->setVolumeNumber($status->volume_number);

        if ($history->shipment_order_volume_state == 'NEW') {
            $this->log_data('batch', $log_name, "chegou NEW ignorou.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()));
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        $freightByTrackingCode = $this->model_freights->getOrderIdForCodeTracking($this->trackingCode, true);

        if (empty($freightByTrackingCode)) {
            $freights = $this->model_freights->getFreightsByShippingOrder($this->orderNumber);

            // não tem tracking code, definir para um pedido
            if (!$freights) {
                $this->log_data('batch', $log_name, "Não foram contrados rastreios para o pedido.\n\n body=" . json_encode($status) . "\n\n header=" . json_encode(getallheaders()), "E");
                return $this->response('order number not found', REST_Controller::HTTP_BAD_REQUEST);
            }

            $freight = $freights[$this->volumeNumber - 1];
        } else {
            $freight = $freightByTrackingCode;
        }

        $this->setOrderId($freight['order_id']);
        $this->setFreightId($freight['id']);

        $order = $this->model_orders->getOrdersData(0, $this->orderId);

        $this->setPaidStatus($order['paid_status']);

        if (is_numeric($storeApiKey) && $storeApiKey !== 0 && $storeApiKey !== $this->orderId) {
            $this->log_data('batch', $log_name, "Loja não pertence a api-key.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(null, REST_Controller::HTTP_UNAUTHORIZED);
        }

        // criar etiqueta
        if ($history->shipment_order_volume_state == 'LABEL_CREATED') {
            // quando chamar o endpoint de etiqueta, já cria esse evento
            // $this->getLabel($order);
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        if (empty($this->trackingCode)) {
            $this->log_data('batch', $log_name, "Volume do pedido sem código de rastreio.\n\n body=".json_encode($status)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response('tracking code not found', REST_Controller::HTTP_BAD_REQUEST);
        }

        $arrFreight = array();
        $arrOrder = array();

        if ($freight['codigo_rastreio'] == null) {
            $this->getLabel($order);
        }

        $stateName          = $history->shipment_order_volume_state;
        $stateHistoryName   = $history->shipment_volume_micro_state->name;
        $dataStatus         = date('Y-m-d H:i:s', strtotime($history->created_iso));

        $ocorrencia = $this->model_frete_ocorrencias->getOcorrenciasByFreightIdName($this->freightId, $stateHistoryName);
        if (!isset($ocorrencia)) {

            if ($stateName == 'CANCELLED') return $this->response(null,REST_Controller::HTTP_OK);
            if ($stateName == 'NEW') return $this->response(null,REST_Controller::HTTP_OK);

            // atualizo o status da Order
            if (in_array($order['paid_status'], [4,53]) && in_array($stateName, ['IN_TRANSIT', 'SHIPPED'])) {
                $arrOrder['paid_status'] = $order['in_resend_active'] ? 5 : 55;
                $arrOrder['data_envio'] = $dataStatus;
                $arrFreight['status_ship'] = $order['in_resend_active'] ? 5 : 55;
            } elseif ($order['paid_status'] == 5 && $stateName == 'DELIVERED') {
                $arrFreight['date_delivered'] = $dataStatus;
                $arrFreight['status_ship'] = 60;
                $arrOrder['paid_status'] = 60; // Entregue. Precisa acertar no marketplace.
                $arrOrder['data_entrega'] = $dataStatus;
            }

            if (in_array($stateName, ['IN_TRANSIT', 'SHIPPED']) && !in_array($order['paid_status'], [4,53,5,55,6,60])) {
                $this->log_data('batch', $log_name, 'Objeto com status de entregue, mas ainda não foi atualizado no marketplace para receber essa situação. Está no status=' . $order['paid_status'] . '. Rastreio=' . $freight['codigo_rastreio'] . ' do pedido=' . $this->orderId . ' frete=' . $this->freightId . '. Retorno=' . json_encode($history, true), "W");
                return $this->response(null,REST_Controller::HTTP_BAD_REQUEST);
            }
            if ($stateName == 'DELIVERED' && !in_array($order['paid_status'], [5,6,60])) {
                $this->log_data('batch', $log_name, 'Objeto com status de entregue, mas ainda não foi atualizado no marketplace para receber essa situação. Está no status=' . $order['paid_status'] . '. Rastreio=' . $freight['codigo_rastreio'] . ' do pedido=' . $this->orderId . ' frete=' . $this->freightId . '. Retorno=' . json_encode($history, true), "W");
                return $this->response(null,REST_Controller::HTTP_BAD_REQUEST);
            }

            if (count($arrFreight) > 0) $this->model_freights->update($arrFreight, $this->freightId);
            $arrOrder['last_occurrence'] = $stateHistoryName;
            if (count($arrOrder) > 0) $this->model_orders->updateByOrigin($this->orderId, $arrOrder);

            $this->saveFreteOcorrencias($history);
            $this->log_data('batch', $log_name, "Pedido: {$this->orderId}, frete: {$freight['codigo_rastreio']}, gravou ocorrencia \n\n". json_encode($history));
        }

        return $this->response(null,REST_Controller::HTTP_OK);
        
    }

    private function saveFreteOcorrencias(object $history): bool
    {
        $stateDescrition    = $history->shipment_volume_micro_state->description;
        $stateHistoryName   = $history->shipment_volume_micro_state->name;
        $stateCode          = $history->shipment_volume_micro_state->code;
        $dataStatus         = date('Y-m-d H:i:s', strtotime($history->created_iso));

        $frete_ocorrencia = array(
            'freights_id'           => $this->freightId,
            'codigo'                => $stateCode,
            'nome'                  => $stateHistoryName,
            'data_ocorrencia'       => $dataStatus,
            'data_atualizacao'      => $dataStatus,
            'data_reentrega'        => NULL,
            'prazo_devolucao'       => NULL,
            'mensagem'              => $stateDescrition,
            'avisado_marketplace'   => 0,
            'addr_place'            => $history->location->local ?? null,
            'addr_name'             => $history->location->address ?? null,
            'addr_num'              => $history->location->number ?? null,
            'addr_cep'              => $history->location->zip_code ?? null,
            'addr_neigh'            => $history->location->quarter ?? null,
            'addr_city'             => $history->location->city ?? null,
            'addr_state'            => $history->location->state_code ?? null
        );

        return $this->model_frete_ocorrencias->create($frete_ocorrencia) ? true : false;
    }

    private function getPathServer(string $folder): string
    {
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath,'assets');
        $serverpath = substr($serverpath,0,$pos);
        $targetDir = $serverpath . 'assets/images/'.$folder.'/';
        if (!file_exists($targetDir)) {
            // cria o diretorio para receber as etiquetas
            @mkdir($targetDir);
        }
        return $targetDir;
    }

    private function setTrackingCode(string $trackingCode)
    {
        $this->trackingCode = $trackingCode;
    }

    private function setOrderNumber(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    private function setVolumeNumber(int $volumeNumber)
    {
        $this->volumeNumber = $volumeNumber;
    }

    private function setOrderId(int $orderId)
    {
        $this->orderId = $orderId;
    }

    private function setFreightId(int $freightId)
    {
        $this->freightId = $freightId;
    }

    private function setPaidStatus(int $paidStatus)
    {
        $this->paidStatus = $paidStatus;
    }

    private function getLabel($order)
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $pathEtiquetas = $this->getPathServer("etiquetas");

        $etiquetaA4 = base_url() . $pathEtiquetas . "P_{$this->orderId}_{$this->volumeNumber}_{$order['in_resend_active']}_A4.pdf";
        $etiquetaTermica = base_url() . $pathEtiquetas . "P_{$this->orderId}_{$this->volumeNumber}_{$order['in_resend_active']}_Termica.pdf";

        $salesChanel = $this->model_settings->getSettingDatabyName('sellercenter')['value'];

        $url = "https://api.intelipost.com.br/api/v1/shipment_order/get_label/{$this->orderNumber}/{$this->volumeNumber}";
        $getLabelTracking = $this->calculoFrete->sendRest($url, array("api-key: {$this->apiKey}", "platform: {$salesChanel}"), '', 'GET');
        $httpCode = (int)$getLabelTracking['httpcode'];
        $response = json_decode($getLabelTracking['content']);

        if ($httpCode != 200) {
            $this->log_data('batch', $log_name, "ERRO para obter dados da etiqueta intelipost do pedido ( {$this->orderId} ). \n\nhttpcode={$httpCode}\ncontent={$getLabelTracking['content']}", "E");
            return $this->response(null,REST_Controller::HTTP_BAD_REQUEST);
        }

        $getEtiqueta = $response->content->label_url;
        copy($getEtiqueta, FCPATH . $pathEtiquetas . "P_{$this->orderId}_{$this->volumeNumber}_{$order['in_resend_active']}_A4.pdf");
        copy($getEtiqueta, FCPATH . $pathEtiquetas . "P_{$this->orderId}_{$this->volumeNumber}_{$order['in_resend_active']}_Termica.pdf");

        $this->model_freights->update(array(
            'link_etiqueta_a4'      => $etiquetaA4,
            'link_etiqueta_termica' => $etiquetaTermica,
            'codigo_rastreio'       => $this->trackingCode
        ), $this->freightId);

        if ($this->paidStatus == 50) $this->model_orders->updatePaidStatus($this->orderId, $order['in_resend_active'] ? 53 : 51);
    }
}