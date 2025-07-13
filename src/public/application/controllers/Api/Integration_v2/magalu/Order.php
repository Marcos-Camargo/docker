<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

class Order extends REST_Controller
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    /**
     * Instantiate a new UpdateNFe instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->order_v2 = new Order_v2();
        header('Integration: v2');
    }

    /**
     * Atualização de pedido, deve ser recebido via POST
     */
    public function index_put()
    {
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook', 'WebHookOrder', "Chegou PUT, não deveria\n_GET=".json_encode($_GET)."\n".json_encode($product), "E");
        return $this->response("method not accepted", REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de pedido, deve ser recebido via POST
     */
    public function index_get()
    {
        $this->log_data('WebHook', 'WebHookOrder', "Chegou GET, não deveria\n_GET=".json_encode($_GET), "E");
        return $this->response("method not accepted", REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de estoque
     */
    public function index_post()
    {
        ob_start();
        $headers = getallheaders();
        foreach ($headers as $header => $value) {
            $headers[strtolower($header)] = $value;
        }
        if (!isset($headers['token'])) {
            return $this->response(array("message" => "apiKey não encontrado"), REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($headers['token'], FILTER_SANITIZE_STRING);
        $store  = $this->order_v2->getStoreForApiKey($apiKey);

        if (!$store) {
            return $this->response(array("message" => 'apiKey não corresponde a nenhuma loja'), REST_Controller::HTTP_UNAUTHORIZED);
        }

        try {
            $this->order_v2->startRun($store);
            $this->order_v2->setToolsOrder();

            // Recupera dados enviado pelo body
            $data = $this->cleanGet(json_decode($this->input->raw_input_stream));

            $order_integration_id = $data->order_id;
            $event_id = $data->event_id;

            $data_order     = $this->order_v2->getOrderByOrderIntegration($order_integration_id);
            if (empty($data_order)) {
                throw new Exception("Pedido $order_integration_id não encontrado");
            }
            $order_id       = $data_order['id'];
            $this->order_v2->toolsOrder->orderIdIntegration = $order_integration_id;
            $this->order_v2->toolsOrder->orderId = $order_id;
            $this->order_v2->setUniqueId($this->order_v2->toolsOrder->orderId);

            try {
                $order = $this->order_v2->getOrder($this->order_v2->toolsOrder->orderId);
            } catch (InvalidArgumentException $exception) {
                echo "[PROCESS][LINE:" . __LINE__ . "]\n";
                return $this->response(array("message" => "Não encontrou dados para o pedido ({$this->order_v2->toolsOrder->orderId}). " . $exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
            }

            switch ($event_id) {
                case '3': // [cancelado] - O Pedido foi Cancelado
                    $actionStatus = $this->order_v2->setCancelIntegration();
                    break;
                case '7': // [faturado] - Sua Nota Fiscal foi emitida
                    $actionStatus = $this->order_v2->setInvoice($order);
                    break;
                case '192': // [rastreio] - Transportador foi notificado para realizar a entrega
                case '317': // [rastreio] - Entregador chegou na loja para coletar o pedido, para as modalidades de SFS loja ou Seller
                case '318': // [rastreio] - Entregador saiu na loja para coletada o pedido, para as modalidades de SFS loja ou Seller
                    $actionStatus = $this->order_v2->setTracking();
                    break;
                case '18': // [enviado] - Seu produto já esta com a transportadora responsável pela entrega : )
                case '9':  // [enviado] - Seu produto está em rota de entrega.
                    $actionStatus = $this->order_v2->setShipped();
                    break;
                case '10': // [entregue] - Seu produto foi entregue
                    $actionStatus = $this->order_v2->setOccurrence();
                    break;
                default:
                    $actionStatus = false;
                    break;
            }

            if ($actionStatus && $event_id != '3' && $this->order_v2->nameStatusUpdated) {
                $this->order_v2->log_integration("Pedido ({$this->order_v2->toolsOrder->orderId}) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido {$this->order_v2->toolsOrder->orderId}, foi atualizado para <strong>{$this->order_v2->nameStatusUpdated}</strong></li></ul>", "S");
            }
        } catch (InvalidArgumentException | Exception $exception) {
            ob_clean();
            return $this->response(array("message" => $exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
    }
}