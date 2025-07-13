<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

/**
 * @property CI_Input $input
 */

class UpdateStatus extends REST_Controller
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    /**
     * @var string Nome do estado para geração de logs.
     */
    private $nameStatusUpdated = null;

    /**
     * Instantiate a new UpdateStatus instance.
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
        $this->log_data('api', 'Api/UpdateStatus/Tiny', json_encode($body));
        $status = $body->dados ?? null;
        $apiKey = filter_var($this->input->get('apiKey'), FILTER_SANITIZE_STRING);

        if (empty($this->input->get('apiKey'))) {
            ob_clean();
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }

        $store = $this->order_v2->getStoreForApiKey($apiKey);

        if (!$store) {
            ob_clean();
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
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
            return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->order_v2->setToolsOrder();

        if (!$status || !property_exists($body, 'tipo') || $body->tipo != 'situacao_pedido') {
            ob_clean();
            return $this->response("Tipo de requisição não localizado", REST_Controller::HTTP_BAD_REQUEST);
        }

        $dataOrder = $this->order_v2->getOrderByOrderId($status->idPedidoEcommerce);
        if (!$dataOrder) {
            ob_clean();
            return $this->response("Pedido ($status->idPedidoEcommerce) não localizado", REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->order_v2->toolsOrder->orderId            = $dataOrder['id'];
        $this->order_v2->toolsOrder->orderIdIntegration = $dataOrder['order_id_integration'];
        $this->order_v2->setUniqueId($this->order_v2->toolsOrder->orderId);

        // Se não for envio de nota fiscal e loja não tem logística da Tiny, retorna 200.
        if ($status->situacao !== 'faturado' && !$this->order_v2->getStoreOwnLogistic()) {
            ob_clean();
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        try {
            switch ($status->situacao) {
                case 'faturado':
                    $this->updateInvoice($dataOrder);
                    break;
                case 'pronto_envio':
                    $this->updateTracking($dataOrder);
                    break;
                case 'enviado':
                    $this->updateShipped($dataOrder);
                    break;
                case 'entregue':
                    $this->updateDelivered($dataOrder);
                    break;
                default:
                    ob_clean();
                    return $this->response(null, REST_Controller::HTTP_OK);
            }
        } catch (Exception $exception) {
            ob_clean();
            return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
        }

        if ($status->situacao !== 'faturado' && $this->nameStatusUpdated) {
            $this->order_v2->log_integration("Pedido ({$this->order_v2->toolsOrder->orderId}) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido {$this->order_v2->toolsOrder->orderId}, foi atualizado para <strong>$this->nameStatusUpdated</strong></li></ul>", "S");
        }

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
    }

    /**
     * @param   array   $dataOrder
     * @return  void
     * @throws  Exception
     */
    private function updateInvoice(array $dataOrder): void
    {
        // Pedido já tem nota.
        if ($dataOrder['paid_status'] != 3) {
            return;
        }

        try {
            $dataInvoice = $this->order_v2->toolsOrder->getInvoiceIntegration($this->order_v2->toolsOrder->orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            throw new Exception($exception->getMessage());
        }

        try {
            $this->order_v2->setInvoiceOrder($dataInvoice);
        } catch (InvalidArgumentException $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param   array   $dataOrder
     * @return  void
     * @throws  Exception
     */
    private function updateTracking(array $dataOrder): void
    {
        try {
            $order = $this->order_v2->getOrder($this->order_v2->toolsOrder->orderId);
        } catch (InvalidArgumentException $exception) {
            throw new Exception($exception->getMessage());
        }

        if ($dataOrder['paid_status'] != 40) {
            if (in_array($dataOrder['paid_status'], [3, 52, 50])) {
                throw new Exception('Pedido ainda não pode receber o rastreio.');
            }
            return;
        }

        try {
            $tracking = $this->order_v2->toolsOrder->getTrackingIntegration($this->order_v2->toolsOrder->orderIdIntegration, $order->items);
        } catch (InvalidArgumentException $exception) {
            $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
            throw new Exception($exception->getMessage());
        }

        // Não encontrou rastreio para os itens.
        if (!count($tracking)) {
            throw new Exception("Pedido ({$dataOrder['id']}). Sem informações de rastreio");
        }

        try {
            $this->order_v2->setTrackingOrder($tracking, $this->order_v2->toolsOrder->orderId);
        } catch (InvalidArgumentException $exception) {
            $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
            throw new Exception($exception->getMessage());
        }

        $this->nameStatusUpdated = 'Aguardando Coleta/Envio';
    }

    /**
     * @param   array   $dataOrder
     * @return  void
     * @throws  Exception
     */
    private function updateShipped(array $dataOrder): void
    {
        if ($dataOrder['paid_status'] != 43) {
            if (in_array($dataOrder['paid_status'], [3, 52, 50, 51, 53])) {
                throw new Exception('Pedido ainda não pode receber a data de envio.');
            }
            return;
        }

        try {
            $dateShipped = $this->order_v2->toolsOrder->getShippedIntegration($this->order_v2->toolsOrder->orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
            throw new Exception($exception->getMessage());
        }

        if (empty($dateShipped)) {
            throw new Exception('Pedido sem data de envio.');
        }

        try {
            $this->order_v2->setShippedOrder($dateShipped, $this->order_v2->toolsOrder->orderId);
        } catch (InvalidArgumentException $exception) {
            $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
            throw new Exception($exception->getMessage());
        }

        $this->nameStatusUpdated = 'Em Transporte em: ' . datetimeBrazil($dateShipped, null);
    }

    /**
     * @param   array   $dataOrder
     * @return  void
     * @throws  Exception
     */
    private function updateDelivered(array $dataOrder): void
    {
        if ($dataOrder['paid_status'] != 45) {
            if (in_array($dataOrder['paid_status'], [3, 52, 50, 51, 53, 55, 5])) {
                throw new Exception('Pedido ainda não pode receber a data de entrega.');
            }
            return;
        }

        try {
            $dataOccurrence = $this->order_v2->toolsOrder->getOccurrenceIntegration($this->order_v2->toolsOrder->orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar ocorrências do pedido {$this->order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
            throw new Exception($exception->getMessage());
        }

        // Pedido já foi entregue, deve marcar o pedido como entregue.
        if ($dataOccurrence['isDelivered']) {

            $dateDelivered = $dataOccurrence['dateDelivered'];
            if (!$dateDelivered) {
                try {
                    $dateDelivered = $this->order_v2->toolsOrder->getDeliveredIntegration($this->order_v2->toolsOrder->orderIdIntegration);
                } catch (InvalidArgumentException $exception) {
                    $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                    throw new Exception($exception->getMessage());
                }
            }

            if (empty($dateDelivered)) {
                throw new Exception('Não foi possível obter a data de entrega.');
            }

            try {
                $this->order_v2->setDeliveredOrder($dateDelivered, $this->order_v2->toolsOrder->orderId);
            } catch (InvalidArgumentException $exception) {
                $this->order_v2->log_integration("Erro na atualização do pedido ({$this->order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar o pedido ({$this->order_v2->toolsOrder->orderId}) para Entregue</h4><p>{$exception->getMessage()}</p>", "E");
                throw new Exception($exception->getMessage());
            }
        } else {
            throw new Exception('Pedido ainda não entregue ao cliente');
        }

        $this->nameStatusUpdated = 'Entregue em: ' . datetimeBrazil($dateDelivered, null);
    }
}