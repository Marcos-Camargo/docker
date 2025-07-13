<?php

require APPPATH . "libraries/REST_Controller.php";

/**
 * WebHook de confirmação de produto da Mosaico.
 * Geram um receipt na hora do envio, retornam a confirmação de processamento do produto.
 * Um produto pode ser atualizado mais de uma vez na Mosaico, neste caso, gerando mais de um receipt.
 * 
 * @property     CI_Output                          $output
 * @property     Model_errors_transformation        $model_errors_transformation
 * @property     Model_integration_ticket           $model_integration_ticket
 * @property     Model_products                     $model_products
 * @property     Model_queue_products_marketplace   $model_queue_products_marketplace
 */
class Receipt extends REST_Controller
{

    private const ALLOWED_METHODS = ["POST"];

    /**
     * @var  array Array contendo os erros.
     */
    private $errors = [];

    /**
     * @var string Nome da integração.
     */
    private $int_to = "Mosaico";

    /**
     * @var  array Array contendo como chave os queueIDs para validar se podem ser removidos.
     */
    private $queuesToVerify = [];

    /**
     * @var  array Array contendo os campos obrigatórios do Receipt.
     */
    const REQUIRED_RECEIPT_FIELDS = ["ticket_id", "message", "product_id", "status", "warning_messages"];

    public function __construct()
    {
        parent::__construct();

        // Verifica se o método da request é permitido para esta rota, se não, retorna o erro.
        // Caso mais de um método seja permitido, mas não para todos endpoint, é necessário criação de handlers próprios.
        if (!in_array($_SERVER["REQUEST_METHOD"], SELF::ALLOWED_METHODS)) {
            $this->returnMethodNotAllowed($_SERVER["REQUEST_METHOD"]);
            $this->output->_display();
            exit;
        }

        $this->load->model("model_errors_transformation");
        $this->load->model("model_integration_ticket");
        $this->load->model("model_products");
        $this->load->model("model_queue_products_marketplace");
    }

    /**
     * Realiza o tratamento dos tickets recebidos.
     */
    public function index_post()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $data = json_decode(file_get_contents('php://input'), true);

        $message = json_encode([
            "type" => "Receipt Produto Mosaico",
            "data" => $data
        ]);

        if ($message) {
            $this->log_data('api', $log_name, json_encode($message));
        }

        if (!$data || !$data["receipts"]) {
            return $this->sendErrorResponse("Dados inválidos.");
        }

        $receipts = $data["receipts"];

        foreach ($receipts as $receipt) {
            if (!$this->validateReceipt($receipt)) {
                $this->errors[] = [
                    "receipt" => $receipt,
                    "message" => "Estrutura do receipt inválida"
                ];
                continue;
            }

            $this->handleReceipt($receipt);
        }

        $this->verifyQueues();

        if (count($this->errors) > 0) {
            $this->log_data("api", $log_name, json_encode($this->errors));
        }

        return $this->response(
            [
                "message" => "Receipts processados com sucesso",
                "errors" => $this->errors
            ],
            REST_Controller::HTTP_OK
        );
    }

    /**
     * Cria uma entrada de erro de transformação para determinado produto.
     * 
     * @param    array{
     *               ticket_id: string,
     *               message: string,
     *               product_id: string,
     *               status: int,
     *               warning_message: array
     *           } $receipt 
     */
    private function handleErrorReceipt($receipt, $latestProductTicket)
    {
        $data = [
            "prd_id" => $latestProductTicket["prd_id"],
            "skumkt" => $latestProductTicket["sku_mkt"],
            "int_to" => $this->int_to,
            "step" => "Webhook Mosaico",
            "message" => $receipt["message"],
            "status" => 0
        ];
        $this->model_errors_transformation->create($data);
    }

    /**
     * Realiza o tratamento de uma das receipts recebidas.
     * 
     * @param    array{
     *               ticket_id: string,
     *               message: string,
     *               product_id: string,
     *               status: int,
     *               warning_message: array
     *           } $receipt
     * 
     * @return   array{
     *               ticket_id: int,
     *               prd_id: int,
     *               sku_mkt: string,
     *               status: string,
     *               finished: bool,
     *               id: int,
     *               ticket: string,
     *               created_at: string
     *           } 
     */
    private function handleReceipt($receipt)
    {
        $ticket = $this->model_integration_ticket->getTicket(["ticket" => $receipt["ticket_id"]]);
        if (!$ticket) {
            $this->errors[] = [
                "ticket" => $receipt["ticket_id"],
                "message" => "Ticket não encontrado"
            ];
            return;
        }

        $latestProductTicket = $this->model_integration_ticket->getSkuLatestTicketHistory($receipt["product_id"]);
        if (!$latestProductTicket) {
            $this->errors[] = [
                "ticket" => $receipt["ticket_id"],
                "message" => "Produto {$receipt['product_id']} não apresenta envio válido na fila"
            ];
            return;
        }

        if ($latestProductTicket["ticket"] != $receipt["ticket_id"]) {
            $this->errors[] = [
                "ticket" => $receipt["ticket_id"],
                "message" => "Não é o Ticket mais atual para o produto {$receipt['product_id']}"
            ];
            return;
        }

        if ($latestProductTicket["finished"]) {
            $this->errors[] = [
                "ticket" => $receipt["ticket_id"],
                "message" => "Ticket mais atual para o produto {$receipt['product_id']} já foi finalizado"
            ];
            return;
        }

        $this->setFinishedLastSkuTicket($latestProductTicket, $receipt["message"]);

        if ($receipt["status"] >= 300) {
            $this->handleErrorReceipt($receipt, $latestProductTicket);
        } else {
            $this->handleSuccessReceipt($receipt);
        }
    }

    /**
     * Seta qualquer status de erro de transformação do produto como resolvido.
     * 
     * @param    array{
     *               ticket_id: string,
     *               message: string,
     *               product_id: string,
     *               status: int,
     *               warning_message: array
     *           } $receipt 
     */
    private function handleSuccessReceipt($receipt)
    {
        $this->model_errors_transformation->setStatusResolvedBySkuMkt($receipt["product_id"], $this->int_to);
    }

    /**
     * Retorna a response de erro para o caso do método não ser permitido.
     * @param    string         $method                 Método HTTP utilizado.
     * 
     * @return   mixed          Seta a response que será enviada.
     */
    private function returnMethodNotAllowed(string $method)
    {
        $msg = "O verbo '$method' não é compatível com essa rota.";
        return $this->sendErrorResponse($msg, REST_Controller::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Retorna a response de erro genérica.
     * @param    string     $message                Mensagem de retorno do erro.
     * @param    int        $status                 Status HTTP que será enviado.
     * 
     * @return   mixed      Seta a response.
     */
    private function sendErrorResponse($message = "Um erro ocorreu", $status = REST_Controller::HTTP_BAD_REQUEST)
    {
        return $this->response([
            "error" => [
                "message" => $message,
                "exception" => null
            ]
        ], $status);
    }

    /**
     * Altera o status do ticket do produto para finalizado.
     */
    private function setFinishedLastSkuTicket($latestProductTicket, $newMessage)
    {
        $updated = $this->model_integration_ticket->setFinishedHistoryEntry(
            [
                "sku_mkt" => $latestProductTicket["sku_mkt"],
                "ticket_id" => $latestProductTicket["ticket_id"]
            ],
            $newMessage
        );
        if (!$updated) {
            $this->errors[] = [
                "ticket" => $latestProductTicket["ticket_id"],
                "message" => "Não foi possivel finalizar o ticket {$latestProductTicket['ticket']}"
            ];
        }

        $this->queuesToVerify[$latestProductTicket["queue_id"]] = true;
    }

    /**
     * Valida se os dados da Receipt são validos ou se há campos faltantes.
     */
    private function validateReceipt($receipt)
    {
        if (!is_array($receipt)) {
            return false;
        }

        foreach (self::REQUIRED_RECEIPT_FIELDS as $field) {
            if (!isset($receipt[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica cada entrada da fila cujo o produto foi confirmado.
     * Se não tiver nenhuma entrada não finalizada após os receipts, pode remover da fila.
     */
    private function verifyQueues()
    {
        foreach (array_keys($this->queuesToVerify) as $queueId) {
            $stillProcessing = $this->model_integration_ticket->getTicketHistoryEntries(
                [
                    "finished" => 0,
                    "queue_id" => $queueId
                ]
            );

            if (empty($stillProcessing)) {
                $this->model_queue_products_marketplace->remove($queueId);
            }
        }
    }
}
