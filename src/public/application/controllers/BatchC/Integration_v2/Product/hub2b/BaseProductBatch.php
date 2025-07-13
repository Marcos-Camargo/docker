<?php

use Integration\Integration_v2\hub2b\ToolsProduct;
use Integration_v2\hub2b\Services\ProductCatalogService;

require_once APPPATH . 'libraries/Integration_v2/hub2b/ToolsProduct.php';
require_once APPPATH . 'libraries/Integration_v2/hub2b/Services/ProductCatalogService.php';

/**
 * Class BaseProductBatch
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property ToolsProduct $toolsProduct
 * @property ProductCatalogService $productCatalogService
 * @property Model_stores $model_stores
 * @property Model_api_integrations $model_api_integrations
 */
abstract class BaseProductBatch extends BatchBackground_Controller
{

    protected $toolsProduct;

    protected $productCatalogService;

    protected $storeId;
    protected $productSku;

    protected $integrationData;

    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '4096M');
        $this->toolsProduct = new ToolsProduct();

        $this->session->set_userdata([
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        ]);

        $this->load->model('model_api_integrations');
        $this->load->model('model_stores');

        $this->toolsProduct->setJob(get_class($this));
        $this->productCatalogService = new ProductCatalogService($this->toolsProduct);
    }

    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $storeId Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @param string $productSku Importar produto específico pelo SKU
     * @return bool                     Estado da execução
     */
    public function run($id = null, int $storeId = null, string $productSku = null): bool
    {
        $this->storeId = $storeId;
        $this->productSku = $productSku;
        $log_name = __DIR__ . '/' . get_class($this) . '/' . __FUNCTION__;
        try {
            try {
                if (!$this->checkStartRun(
                    $log_name,
                    $this->router->directory,
                    get_class($this),
                    $id,
                    $this->storeId
                )) {
                    return false;
                }
                $this->toolsProduct->startRun($this->storeId);
                $this->toolsProduct->setDateStartJob();
                $this->toolsProduct->setLastRun();
            } catch (InvalidArgumentException $exception) {
                $this->toolsProduct->log_integration(
                    "Erro para executar a integração",
                    "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                    "E"
                );
                throw new InvalidArgumentException($exception->getMessage());
            }
            $this->import();
            $this->toolsProduct->saveLastRun(true);

        } catch (Throwable|Exception|ErrorException|InvalidArgumentException|GuzzleException $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    protected function getProductPaginationFilters(): array
    {
        return [
            'getAdditionalInfo' => true
        ];
    }

    protected function import(): bool
    {
        $page = 1;
        $perPage = 10;
        $countException = 1;
        $processed = 0;
        $filter = !empty($this->productSku) ? ['destinationSKU' => $this->productSku] : $this->getProductPaginationFilters();
        try {
            while (true) {
                echo "PAGE: {$page}\n";
                try {
                    $products = $this->toolsProduct->getProductsPagination($filter, $page, $perPage);
                    if (empty($products)) break;
                    $this->productCatalogService->handleWithRawList($products);
                    $processed += $perPage;
                    echo "PROCESSED: {$processed}\n";
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