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
        header('Integration: v2');
    }

    /**
     * Atualização de estoque, deve ser recebido via POST
     */
    public function index_put()
    {
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook', 'WebHookUpdateNfe', "Chegou PUT, não deveria\n_GET=".json_encode($_GET)."\n".json_encode($product), "E");
        return $this->response("method not accepted", REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de estoque
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

        $this->order_v2->startRun($store);
        $this->order_v2->setToolsOrder();

        // Recupera dados enviado pelo body
        $data = json_decode(str_replace('data=', '', file_get_contents('php://input')));

        if (!isset($data->retorno->notasfiscais) || !isset($data->retorno->notasfiscais[0]->notafiscal)) {
            return $this->response('Nota fiscal não localizada', REST_Controller::HTTP_BAD_REQUEST);
        }

        $invoices = (array)($data->retorno->notasfiscais ?? array());

        foreach ($invoices as $invoice) {
            $invoice = $invoice->notafiscal;
            $orderId = (int)($invoice->numeroPedidoLoja ?? $invoice->numeroLoja);

            // Somente pedido enviados pela loja configurada.
            if (!empty($this->order_v2->credentials->loja_bling) && $this->order_v2->credentials->loja_bling != $invoice->loja) {
                continue;
            }

            // Pedido não pertence à loja da Apikey
            $dataOrder = $this->order_v2->getOrderByOrderId($orderId);
            if (!$dataOrder) {
                continue;
            }

            // Pedido não pode mais receber NF-e
            if ($dataOrder['paid_status'] != 3) {
                continue;
            }

            $this->order_v2->toolsOrder->orderId            = $orderId;
            $this->order_v2->toolsOrder->orderIdIntegration = $dataOrder['order_id_integration'];
            $this->order_v2->setUniqueId($orderId);

            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (
                !isset($invoice->chaveAcesso) ||
                $invoice->chaveAcesso === null ||
                !isset($invoice->numero) ||
                $invoice->numero === null
            ) {
                continue;
            }

            $dataInvoice = array(
                'date'      => $invoice->dataEmissao,
                'value'     => roundDecimal($invoice->valorNota),
                'serie'     => (int)$invoice->serie,
                'number'    => (int)clearBlanks($invoice->numero),
                'key'       => clearBlanks($invoice->chaveAcesso)
            );

            try {
                $this->order_v2->setInvoiceOrder($dataInvoice);
            } catch (InvalidArgumentException $exception) {
                continue;
            }
        }

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
    }
}