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
        $this->order_v2->setJob('UpdateStatus');
        header('Integration: v2');
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
            "versao": "1.0.0",
            "cnpj": "30120829000199",
            "idEcommerce": 8390,
            "tipo": "nota_fiscal",
            "dados": {
                "chaveAcesso": "32220129922847000296550010000620671208513540",
                "numero": "62067",
                "serie": "1",
                "urlDanfe": "https://erp.tiny.com.br/pre-release/doc.view?id=525cd327da9718cef2bfa53616e27ef2",
                "idPedidoEcommerce": "1",
                "dataEmissao": "18/01/2022",
                "valorNota": 368.87,
                "idNotaFiscalTiny" : 17985647
            }
         }
         */

        if (!isset($_GET['apiKey'])) {
            ob_clean();
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store  = $this->order_v2->getStoreForApiKey($apiKey);

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

        // Recupera dados enviado pelo body
        $body = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'Api/UpdateNFe/Tiny', json_encode($body));
        $invoice = $body->dados ?? null;

        if (!$invoice || !property_exists($body, 'tipo') || $body->tipo != 'nota_fiscal') {
            ob_clean();
            return $this->response('Chegou um tipo diferente de nota fiscal ou os dados estão mal informados', REST_Controller::HTTP_BAD_REQUEST);
        }

        $dataOrder = $this->order_v2->getOrderByOrderId($invoice->idPedidoEcommerce ?? null);
        if (!$dataOrder) {
            ob_clean();
            return $this->response("Pedido ($invoice->idPedidoEcommerce) não localizado", REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->order_v2->toolsOrder->orderId            = $dataOrder['id'];
        $this->order_v2->toolsOrder->orderIdIntegration = $dataOrder['order_id_integration'];
        $this->order_v2->setUniqueId($this->order_v2->toolsOrder->orderId);

        $dateEmission = DateTime::createFromFormat(DATE_BRAZIL, $invoice->dataEmissao)->format(DATE_INTERNATIONAL);

        // Dados para inserir a nota fiscal
        $dataInvoice = array(
            'date'      => "$dateEmission 00:00:00",
            'value'     => roundDecimal($invoice->valorNota),
            'serie'     => (int)clearBlanks($invoice->serie),
            'number'    => (int)clearBlanks($invoice->numero),
            'key'       => clearBlanks($invoice->chaveAcesso)
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