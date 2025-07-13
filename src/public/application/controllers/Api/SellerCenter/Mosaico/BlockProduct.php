<?php

require APPPATH . "libraries/REST_Controller.php";

/**
 * WebHook de bloqueio de produto da Mosaico.
 * Caso um produto da Mosaico tenha alterações bruscas, como preço ou muitas mudanças de nome, bloqueiam o produto.
 * Recebe qual produto foi bloqueado, a fim de mostrar em tela.
 * 
 * @property     CI_Output          $output
 * @property     Model_sku_locks    $model_sku_locks
 * @property     Model_products     $model_products
 */
class BlockProduct extends REST_Controller
{

    private const ALLOWED_METHODS = ["POST"];

    /**
     * @var array Array contendo os erros.
     */
    private $errors = [];

    /**
     * @var string Nome da integração.
     */
    private $int_to = "Mosaico";

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

        $this->load->model("model_products");
        $this->load->model("model_sku_locks");
    }

    /**
     * Realiza o tratamento dos locks recebidos.
     */
    public function index_post()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $data = json_decode(file_get_contents('php://input'), true);

        $message = json_encode([
            "type" => "Bloqueio Produto Mosaico",
            "data" => $data
        ]);

        if ($message) {
            $this->log_data('api', $log_name, json_encode($message));
        }

        if (!$data) {
            return $this->sendErrorResponse("Não foi possível realizar o parsing da request.");
        }

        $newLocks = $data["added_locks"];
        $removedLocks = $data["removed_locks"];

        $this->handleLocks($newLocks, "ADD");
        $this->handleLocks($removedLocks, "REMOVE");

        if(count($this->errors)>0){
            $this->log_data("api",$log_name,json_encode($this->errors));
        }

        return $this->response(
            [
                "message" => "Locks processados com sucesso",
                "errors" => $this->errors
            ],
            REST_Controller::HTTP_OK
        );
    }

    /**
     * Busca dados para cada tipo de lock.
     * @property     array          $locks Array com os locks a serem criados ou removidos.
     * @property     string         $type Tipo da operação com lock (Criação/Remoção).
     */
    private function handleLocks($locks, $type)
    {
        foreach ($locks as $lock) {
            $skuMkt = $lock["offer_id"];
            $lockId = $lock["lock_id"];
            $prdInt = $this->model_products->getProductIntegrationSkumkt($skuMkt);
            if (!$prdInt) {
                $this->errors[] = [
                    "lock" => $lockId,
                    "message" => "Produto com offer_id $skuMkt não encontrado."
                ];
                continue;
            }

            $prdInt = $prdInt[0];

            $existingLock = $this->model_sku_locks->getFirst([
                "marketplace" => $this->int_to,
                "external_id" => $lock["lock_id"]
            ]);

            $parsedLock = [
                "id" => $lock["lock_id"],
                "note" => $lock["lock_note"],
                "prd_id" => $prdInt["prd_id"],
                "sku_mkt" => $skuMkt
            ];

            switch ($type) {
                case "ADD":
                    $this->handleNewLock($parsedLock, $existingLock);
                    break;
                case "REMOVE":
                    $this->handleRemoveLock($parsedLock, $existingLock);
                    break;
            }
        }
    }

    /**
     * Adiciona os locks especificados de determinado produto.
     * @param    array          $newLocks Locks que devem ser adicionados.
     * @param    array          $existingLock Lock já existente.
     */
    private function handleNewLock($lock, $existingLock)
    {
        if ($existingLock) {
            $this->errors[] = [
                "lock" => $lock["id"],
                "message" => "Lock com ID {$lock['id']} já existe."
            ];
            return;
        }

        $lock = $this->model_sku_locks->create([
            "prd_id" => $lock["prd_id"],
            "sku_mkt" => $lock["sku_mkt"],
            "marketplace" => $this->int_to,
            "external_id" => $lock["id"],
            "note" => $lock["note"]
        ]);

        if (!$lock) {
            $this->errors[] = [
                "lock" => $lock["id"],
                "message" => "Não foi possível salvar o lock com ID {$lock['id']}."
            ];
        }
    }

    /**
     * Remove os locks especificados de determinado produto.
     * @param    array          $toRemove Locks que devem ser removidos.
     * @param    array          $existingLock Lock já existente para esse id.
     */
    private function handleRemoveLock($lock, $existingLock)
    {
        if (!$existingLock) {
            $this->errors[] = [
                "lock" => $lock["id"],
                "message" => "Lock com ID {$lock['id']} não existe."
            ];
            return;
        }

        if ($lock["sku_mkt"] != $existingLock["sku_mkt"]) {
            $this->errors[] = [
                "lock" => $lock["id"],
                "message" => "O offer ID não corresponde ao Lock com ID {$lock['id']} ."
            ];
            return;
        }

        $removed = $this->model_sku_locks->remove($existingLock["id"]);
        if (!$removed) {
            $this->errors[] = [
                "lock" => $lock["id"],
                "message" => "Não foi possível remover o Lock com ID {$lock['id']}."
            ];
        }
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
}
