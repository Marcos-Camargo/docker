<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

class UpdateTracking extends REST_Controller
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    /**
     * Instantiate a new UpdateTracking instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->order_v2 = new Order_v2();
        $this->order_v2->setJob('UpdateStatus');
    }

    /**
     * Atualização de estoque, receber via POST(eu acho, confirmar com Tiny)
     */
    public function index_post()
    {
        ob_start();

        /**
         * example payload
         *
        {
            "versao":"1.0.1",
            "cnpj":"00000000000099",
            "idEcommerce":1234,
            "tipo":"rastreio",
            "dados":{
                "idEntregaEcommerce": "int",
                "idVendaTiny": "int",
                "idNotaFiscalTiny": "int",
                "idPedidoEcommerce": "string",
                "codigoRastreio": "string",
                "urlRastreio": "string",
                "transportadora": "string",
                "formaEnvio": "string",
                "formaFrete": "string"
            }
        }
         */
        // Recupera dados enviado pelo body
        $body = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'Api/UpdateTracking/Tiny', json_encode($body));
        $tracking = $body->dados ?? null;

        if (!isset($_GET['apiKey'])) {
            ob_clean();
            return $this->response(array('idEntregaEcommerce' => $tracking->idEntregaEcommerce ?? null, 'error' => "apiKey não encontrado"), REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store  = $this->order_v2->getStoreForApiKey($apiKey);

        if (!$store) {
            ob_clean();
            return $this->response(array('idEntregaEcommerce' => $tracking->idEntregaEcommerce ?? null, 'error' => 'apiKey não corresponde a nenhuma loja'), REST_Controller::HTTP_UNAUTHORIZED);
        }

        try {
            $this->order_v2->startRun($store);
        } catch (InvalidArgumentException $exception) {
            if ($this->order_v2->company) {
                $this->order_v2->log_integration(
                    "Erro para receber notificação",
                    "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                    "E"
                );
            }
            ob_clean();
            return $this->response(array('idEntregaEcommerce' => $tracking->idEntregaEcommerce ?? null, 'error' => $exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->order_v2->setToolsOrder();

        if (!$tracking || !property_exists($body, 'tipo') || $body->tipo != 'rastreio') {
            ob_clean();
            return $this->response(array('idEntregaEcommerce' => $tracking->idEntregaEcommerce ?? null, 'error' => "Tipo de requisição não localizado"), REST_Controller::HTTP_BAD_REQUEST);
        }

        $responseTiny = array(
            'idEntregaEcommerce' => $tracking->idEntregaEcommerce
        );

        $dataOrder = $this->order_v2->getOrderByOrderId($tracking->idPedidoEcommerce ?? null);
        if (!$dataOrder) {
            ob_clean();
            return $this->response(array_merge($responseTiny, array('error' => "Pedido ($tracking->idPedidoEcommerce) não localizado")), REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            $order = $this->order_v2->getOrder($tracking->idPedidoEcommerce);

            $this->order_v2->toolsOrder->orderId            = $dataOrder['id'];
            $this->order_v2->toolsOrder->orderIdIntegration = $dataOrder['order_id_integration'];
            $this->order_v2->setUniqueId($this->order_v2->toolsOrder->orderId);
        } catch (InvalidArgumentException $exception) {
            return $this->response(array_merge($responseTiny, array('error' => $exception->getMessage())), REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            $labelsLink = $this->order_v2->toolsOrder->getLabelTrackingIntegration($tracking->idVendaTiny);
        } catch (InvalidArgumentException $exception) {
            $labelsLink = array();
        }

        $itemsTracking  = array();
        foreach ($order->items as $key => $item) {
            $label = $labelsLink[$key]->link ?? $labelsLink[0]->link ?? null;
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => $tracking->transportadora,
                'trackingCode'              => $tracking->codigoRastreio,
                'trackingUrl'               => $tracking->urlRastreio,
                'generatedDate'             => date(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => $tracking->formaFrete,
                'shippingMethodCode'        => 0,
                'deliveryValue'             => $order->shipping->seller_shipping_cost,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => $label,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null
            );
        }

        // Não encontrou rastreio para os itens
        if (!count($itemsTracking)) {
            ob_clean();
            return $this->response(array_merge($responseTiny, array('error' => "Rastreios não encontrados")), REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            $this->order_v2->setTrackingOrder($itemsTracking, $this->order_v2->toolsOrder->orderId);
        } catch (InvalidArgumentException | TypeError | Error $exception) {
            $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
            ob_clean();
            return $this->response(array_merge($responseTiny, array('error' => $exception->getMessage())), REST_Controller::HTTP_BAD_REQUEST);
        }

        ob_clean();
        return $this->response($responseTiny, REST_Controller::HTTP_OK);
    }
}