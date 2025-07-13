<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/lojaintegrada/UpdateProduct run {ID} {STORE}
 *
 */

require APPPATH . "libraries/Integration_v2/lojaintegrada/ToolsProduct.php";
require APPPATH . "libraries/Integration_v2/lojaintegrada/Services/ProductCatalogService.php";

use GuzzleHttp\Exception\GuzzleException;
use Integration\Integration_v2\lojaintegrada\ToolsProduct;
use Integration_v2\lojaintegrada\Services\ProductCatalogService;

/**
 * Class UpdateProduct
 * @property ToolsProduct $toolsProduct
 * @property ProductCatalogService $productCatalogService
 */
class UpdateProduct extends BatchBackground_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolsProduct;

    private $productCatalogService;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolsProduct = new ToolsProduct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->toolsProduct->setJob(__CLASS__);

        $this->productCatalogService = new ProductCatalogService($this->toolsProduct);
    }

    /**
     * Método responsável pelo "start" da aplicação.
     *
     * @param  string|int|null  $id     Código do job (job_schedule.id).
     * @param  int|null         $store  Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params).
     * @param  string|null      $prdUnit    SKU do produto.
     * @return bool                     Estado da execução.
     */
    public function run($id = null, int $store = null, string $prdUnit = null): bool
    {
        $log_name = $this->toolsProduct->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        if (!$this->checkStartRun(
            $log_name,
            $this->router->directory,
            __CLASS__,
            $id,
            $store
        )) {
            return false;
        }

        // realiza algumas validações iniciais antes de iniciar a rotina
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

        $success = false;
        // Recupera os produtos
        try {
            // Recupera os produtos
            $success = $this->getProductToUpdate($prdUnit);
        } catch (Exception | GuzzleException $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        // Grava a última execução
        $this->toolsProduct->saveLastRun($success);

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    /**
     * Recupera os produtos e variações para atualização de preço e estoque.
     *
     * @param   string|null  $prdUnit   SKU do produto
     * @return  bool                    Retorna se o get para atualização de produtos ocorreu como esperado e poderá atualizar a data da última vez que buscou.
     * @throws  GuzzleException
     */
    public function getProductToUpdate(string $prdUnit = null): bool
    {
        $page = 1;
        $countException = 1;
        $filter['updatedAt'] = $this->toolsProduct->dateLastJob ?? date('Y-m-d H:i:s', strtotime('-1 hours'));
        try {
            while (true) {
                echo "PAGE: {$page}\n";
                try {
                    $productList = $this->toolsProduct->getProductsPagination($filter, $page);
                    echo "TOTAL PRODUCTS: {$productList->meta->totalCount}\n";
                    if (!isset($productList->products) || empty($productList->products)) break;
                    $this->productCatalogService->handleWithRawList($productList->products);
                    $countException = 0;
                    $page++;
                } catch (Throwable $e) {
                    echo "EXCEPTION: {$countException}\n";
                    if ($countException > 4) {
                        echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
                        throw new Exception($e->getMessage());
                    }
                    $countException++;
                }
            }
        } catch (Throwable $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            $this->toolsProduct->log_integration('Ocorreu um erro ao importar a listagem de produtos para criação', $e->getMessage(), 'E');
        }
        return true;
    }
}