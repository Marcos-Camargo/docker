<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

class UpdateNFe extends REST_Controller
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
    }

    /**
     * Atualização de NFe.
     */
    public function index_post()
    {
        ob_start();

        if (!isset($_GET['apiKey'])) {
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store  = $this->order_v2->getStoreForApiKey($apiKey);

        if (!$store) {
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
        }

        try {
            $this->order_v2->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $this->order_v2->log_integration(
                "Erro para receber notificação",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->order_v2->setToolsOrder();

        // Recupera dados enviado pelo body.
        $body = json_decode(file_get_contents('php://input'));

        // Recuperar o pedido pelo código do pedido.
        $dataOrder = $this->order_v2->getOrderByOrderId($body->orderId);
        if (!$dataOrder) {
            // Se não encontrou pelo pedido, ver se é pelo código da integradora.
            $dataOrder = $this->order_v2->getDataOrderByNumMkt($body->orderId);
            if (!$dataOrder) {
                return $this->response("Pedido ($body->orderId) não localizado", REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        $this->order_v2->toolsOrder->orderId            = $dataOrder['id'];
        $this->order_v2->toolsOrder->orderIdIntegration = $dataOrder['order_id_integration'];
        $this->order_v2->setUniqueId($this->order_v2->toolsOrder->orderId);

        // Dados para inserir a nota fiscal.
        $dataInvoice = array(
            'date'      => $body->date,
            'value'     => roundDecimal($body->value),
            'serie'     => (int)clearBlanks($body->serie),
            'number'    => (int)clearBlanks($body->number),
            'key'       => clearBlanks($body->key_access)
        );

        try {
            $this->order_v2->setInvoiceOrder($dataInvoice);
        } catch (InvalidArgumentException $exception) {
            $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
        }

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
    }
}