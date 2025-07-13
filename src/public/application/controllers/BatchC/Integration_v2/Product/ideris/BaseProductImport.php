<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/ideris/CreateProduct run {ID} {STORE}
 *
 */

require_once APPPATH . "libraries/Integration_v2/ideris/ToolsProduct.php";
require_once APPPATH . "libraries/Integration_v2/ideris/Services/BaseProductImportService.php";

use Integration\Integration_v2\ideris\ToolsProduct;
use Integration_v2\ideris\Services\BaseProductImportService;

/**
 * Class BaseProductImport
 * @property ToolsProduct $toolsProduct
 * @property BaseProductImportService $productImportService
 */
abstract class BaseProductImport extends BatchBackground_Controller
{
    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolsProduct = new ToolsProduct();

        $logged_in_sess = [
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        ];
        $this->session->set_userdata($logged_in_sess);
        $this->toolsProduct->setJob(get_class($this));
    }

    /**
     * Método responsável pelo "start" da aplicação.
     *
     * @param string|int|null $id Código do job (job_schedule.id).
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params).
     * @return bool                     Estado da execução.
     */
    public function run($id = null, int $store = null): bool
    {
        $log_name = $this->toolsProduct->integration . '/' . get_class($this) . '/' . __FUNCTION__;

        if (!$this->checkStartRun(
            $log_name,
            $this->router->directory,
            get_class($this),
            $id,
            $store
        )) {
            return false;
        }
        
        // realiza algumas validações iniciais antes de iniciar a rotina.
        try {
            $this->toolsProduct->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $this->toolsProduct->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            $this->gravaFimJob();
            return true;
        }

        $this->toolsProduct->setLastRun();

        $success = false;
        // Recupera os produtos.
        try {
            $success = $this->import();
        } catch (Throwable $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        // Grava a última execução.
        $this->toolsProduct->saveLastRun($success);

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos para cadastro.
     *
     * @return bool
     * @throws Exception|GuzzleException
     */
    public function import(): bool
    {
        $this->productImportService = $this->buildServiceProvider();
        $page = 1;
        $size = 50;
        while (true) {
            try {
                echo "[PROCESS][LINE:" . __LINE__ . "] OBTENDO LISTAGEM DE PRODUTOS. PAGINA: {$page}\n";
                $result = $this->toolsProduct->getProductsPagination([], $page, $size);
                if (empty($result->obj ?? [])) break;
                try {
                    $this->productImportService->handleWithRawList($result->obj ?? []);
                } catch (Throwable $e) {
                    echo "[ERROR][LINE:" . __LINE__ . "] PROCESSAMENTO LISTAGEM DE PRODUTOS. PAGINA: {$page} - {$e->getMessage()}\n";
                }
            } catch (Throwable $e) {
                /**
                 * Ideris retorna uma mensagem com erro 404 ao não achar nenhum resultado.
                 * Verificando se é este o caso, caso seja, apenas finalizamos sem erro, visto que chegou ao fim.
                 */
                $message = json_decode($e->getMessage());
                if($message && $message->obj == null && $message->httpStatusCode == 404){
                    echo "[PROCESS][LINE:" . __LINE__ . "] Não foram encontrados outros produtos, finalizou na pagina: {$page}...\n";
                    break;
                }
                
                echo "[ERROR][LINE:" . __LINE__ . "] CONSULTA LISTAGEM DE PRODUTOS. PAGINA: {$page} - {$e->getMessage()}\n";
                $this->toolsProduct->log_integration(
                    "Erro ao obter listagem de produtos para importação",
                    "<h4>Não foi possível obter a lista de produtos para importação</h4> <p>{$e->getMessage()}</p>",
                    "E"
                );
                break;
            }
            $page++;
        }
        return true;
    }

    protected abstract function buildServiceProvider();
}