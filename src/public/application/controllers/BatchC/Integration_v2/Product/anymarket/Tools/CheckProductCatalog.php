<?php
/**
 * php index.php BatchC/Integration_v2/Product/anymarket/Tools/CheckProductCatalog run {ID} {STORE_ID}
 */

require_once APPPATH . "libraries/Helpers/StringHandler.php";
require_once APPPATH . "libraries/FileDir.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/ToolsProduct.php";

use Integration\Integration_v2\anymarket\ToolsProduct;
use libraries\Helpers\StringHandler;

/**
 * Class CheckProductCatalog
 * @property CI_Loader $load
 * @property CI_DB_query_builder $db
 * @property Model_attributes $model_attributes
 * @property Model_api_integrations $model_api_integrations
 * @property Model_integrations $model_integrations
 * @property Model_settings $model_settings
 * @property ToolsProduct $toolsProduct
 */
class CheckProductCatalog extends BatchBackground_Controller
{

    protected $storeId;
    protected $page;
    protected $size;

    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '4096M');
        $this->load->model('model_attributes');
        $this->load->model('model_api_integrations');
        $this->load->model('model_integrations');

    }

    public function run($id = null, $storeId = null, $page = null, $size = null, $ignoreStores = '')
    {
        $this->storeId = $storeId;
        $this->page = $page;
        $this->size = $size;
        $ignoreStores = explode(':', $ignoreStores);
        echo "Iniciando consulta de produtos...\n";
        $integrations = $this->model_api_integrations->getByIntegrationsName(['anymarket']);
        foreach ($integrations as $integration) {
            if (in_array($integration['store_id'], $ignoreStores)) continue;
            if (!empty($storeId) && $integration['store_id'] != $storeId) continue;
            if (empty($this->storeId)) {
                $this->generateCommands($integration);
                continue;
            }
            $products = $this->loadIntegrationProducts($integration);
        }
        echo "Finalizado consulta de produtos...\n";
    }

    protected function generateCommands($integration)
    {
        $this->initTools($integration);
        $productList = $this->toolsProduct->getProductsPagination([], 1, 1);
        $total = ceil(($productList->page->totalElements ?? 0) / 500);
        $commands = [];
        echo "Criando Comandos de execução loja {$integration['store_id']}...\n";
        for ($page = 0; $page <= $total; $page++) {
            array_push($commands,
                sprintf("php index.php BatchC/Integration_v2/Product/anymarket/Tools/%s run null %s %s %s >/dev/null",
                    get_class($this),
                    $integration['store_id'],
                    $page,
                    500
                )
            );
        }

        $chunkCommands = array_chunk($commands, 100);
        foreach ($chunkCommands as $k => $chunkCommand) {
            $shellCommands = implode(' && ', array_merge([sprintf("cd %s", FCPATH)], $chunkCommand));
            $shellCommands = sprintf("%s %s", $shellCommands, '&');
            echo "Executando Comandos de execução loja {$integration['store_id']} [{$k}]...\n";
            exec($shellCommands);
        }
    }

    protected function saveProductsReport($products, $info)
    {
        $startDate = $info['startDate'] ?? date('dmY');
        $part = $info['processId'] ?? time();
        $storeData = $info['integration'];
        $slugStoreName = StringHandler::slugify($storeData['store_name'], '_');
        $fileDir = "%s/assets/files/report_products/anymarket/{$slugStoreName}_{$storeData['store_id']}";
        $baseDir = sprintf($fileDir, FCPATH);
        if (!FileDir::createDir($baseDir))
            return;
        $fileName = sprintf("%s_%s_%s_%s.csv", $slugStoreName, $storeData['store_id'], $startDate, $part);
        $fullFilePath = "{$baseDir}/$fileName";

        $csvData = "id Loja,Nome Loja,Sku,Anuncio Anymarket,Sku Anymarket,Produto Anymarket,Produto {$this->toolsProduct->sellerCenter},id Produto,Publicado\n";
        foreach ($products as $product) {
            $csvData .= sprintf("\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $product['storeId'],
                $product['storeName'],
                $product['sku'],
                str_replace('"', '""', $product['announcementTitle']),
                str_replace('"', '""', $product['skuTitle']),
                str_replace('"', '""', $product['productTitle']),
                str_replace('"', '""', $product['importedTitle']),
                $product['importedId'],
                $product['mktplaces']
            );
        }
        if (file_exists($fullFilePath)) unlink($fullFilePath);
        $fp = fopen($fullFilePath, 'w+');
        fwrite($fp, $csvData);
        fclose($fp);
        echo sprintf($fileDir . "/%s\n", base_url(), $fileName);
    }

    protected function initTools(array $integration)
    {
        echo "Loja {$integration['store_name']} ({$integration['store_id']})...\n";
        $this->toolsProduct = new ToolsProduct();
        $this->toolsProduct->setJob(get_class($this));
        $this->toolsProduct->startRun($integration['store_id']);
    }

    protected function loadIntegrationProducts(array $integration): array
    {
        $this->initTools($integration);
        return $this->loadProductsPagination($integration);
    }

    protected function loadProductsPagination($integration, $page = null, $size = null)
    {
        $page = $this->page ?? $page ?? 1;
        $size = $this->size ?? $size ?? 500;
        $total = 0;
        $rows = [];

        $info = [
            'startDate' => date('dmY'),
            'processId' => $page,
        ];
        try {
            while (true) {
                echo "PAGE: {$page}\n";
                try {
                    $productList = $this->toolsProduct->getProductsPagination([], $page, $size);
                    $total = $total == 0 ? $productList->page->totalElements : $total;
                    echo "TOTAL PRODUCTS: {$productList->page->totalElements}\n";
                    echo "REMAINS: {$total}\n";
                    $total -= $size;
                    if (!isset($productList->content) || empty($productList->content)) break;
                    foreach ($productList->content as $product) {
                        $row = $this->checkProductData($product);
                        if (!empty($row)) {
                            $row['storeId'] = $this->toolsProduct->integrationData['store_id'];
                            $row['storeName'] = $this->toolsProduct->dataStore['name'];
                            array_push($rows, $row);
                        }
                    }
                    $count = count($rows);
                    echo "FOUND PRODUCTS: {$count}\n";
                } catch (Throwable $e) {
                    echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
                }

                if (count($rows) >= 1000) {
                    $info['processId'] = $page;
                    $this->saveProductsReport($rows, array_merge($info, ['integration' => $integration]));
                    $rows = [];
                }
                if (!empty($this->page)) break;
                $page++;
            }
        } catch (Throwable $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
        }
        if (!empty($rows)) {
            $info['processId'] = $page;
            $this->saveProductsReport($rows, array_merge($info, ['integration' => $integration]));
        }
        return $rows;
    }

    protected function checkProductData($announcementData): array
    {
        $idIntegration = $this->toolsProduct->integrationData['id'] ?? 0;
        $idSku = $announcementData->sku->id ?? 0;
        $idProduct = $announcementData->sku->product->id ?? 0;
        if (empty($idIntegration) || empty($idSku) || empty($idProduct)) return [];

        try {
            $importedProduct = $this->toolsProduct->model_products->getByProductIdErpAndStore($idProduct, $this->toolsProduct->store);
            $importedProduct = $importedProduct ?: null;
            if ($importedProduct) {
                $importedProduct = $this->toolsProduct->model_products->getProductsToDisplayByCriteria([
                        'product_id' => $importedProduct['id'],
                        'store_id' => $this->toolsProduct->store,
                    ])[0] ?? [];
            }

            $importedProductTitle = trim($importedProduct['name'] ?? '');
            $announcementTitle = trim($announcementData->title ?? '');
            $skuTitle = trim($announcementData->sku->title ?? '');
            $productTitle = trim($announcementData->sku->product->title ?? '');

            $productData = [
                'importedProductTitle' => $importedProductTitle,
                'announcementTitle' => $announcementTitle,
                'skuTitle' => $skuTitle,
                'productTitle' => $productTitle,
                'productIntegration' => $announcementData,
                'importedProduct' => $importedProduct
            ];

            echo sprintf("[%s] %s | %s | %s | %s\n", $announcementData->skuInMarketplace, $announcementTitle, $skuTitle, $productTitle, $importedProductTitle);
            if (!empty($importedProductTitle)) {
                if (!self::strToSlugContainsStrToSlug($importedProductTitle, $announcementTitle)) {
                    if (!self::strToSlugContainsStrToSlug($importedProductTitle, $skuTitle)) {
                        if (!self::strToSlugContainsStrToSlug($importedProductTitle, $productTitle)) {
                            return $this->handleValidationResult($productData);
                        }
                        return $this->handleValidationResult($productData);
                    } elseif (!self::strToSlugContainsStrToSlug($importedProductTitle, $productTitle)) {
                        return $this->handleValidationResult($productData);
                    }
                    return $this->handleValidationResult($productData);
                } elseif (!self::strToSlugContainsStrToSlug($importedProductTitle, $skuTitle)) {
                    if (!self::strToSlugContainsStrToSlug($importedProductTitle, $productTitle)) {
                        return $this->handleValidationResult($productData);
                    }
                } elseif (!self::strToSlugContainsStrToSlug($importedProductTitle, $productTitle)) {
                    return $this->handleValidationResult($productData);
                }
            }

            if (!self::strToSlugContainsStrToSlug($announcementTitle, $skuTitle)) {
                if (!self::strToSlugContainsStrToSlug($announcementTitle, $productTitle)) {
                    if (!self::strToSlugContainsStrToSlug($announcementTitle, $productTitle)) {
                        return $this->handleValidationResult($productData);
                    }
                    return $this->handleValidationResult($productData);
                }
                return $this->handleValidationResult($productData);
            } elseif (!self::strToSlugContainsStrToSlug($announcementTitle, $productTitle)) {
                return $this->handleValidationResult($productData);
            }
            if (!self::strToSlugContainsStrToSlug($skuTitle, $productTitle)) {
                return $this->handleValidationResult($productData);
            }

        } catch (Throwable $e) {
            echo "Error: {$e->getMessage()}\n";
        }
        return [];
    }

    protected function handleValidationResult(array $productData): array
    {
        return [
            'sku' => $productData['importedProduct']['sku'] ?? $productData['productIntegration']->skuInMarketplace ?? '',
            'announcementTitle' => $productData['announcementTitle'],
            'skuTitle' => $productData['skuTitle'],
            'productTitle' => $productData['productTitle'],
            'importedTitle' => $productData['importedProductTitle'] ?? sprintf("Produto não encontrado no sellercenter %s", $this->toolsProduct->sellerCenter),
            'importedId' => $productData['importedProduct']['id'] ?? '',
            'mktplaces' => $productData['importedProduct']['marketplaces'] ?? ''
        ];
    }

    protected static function strToSlugContainsStrToSlug(string $firstStr, string $secondStr): string
    {
        $search = [' - ', '--', ','];
        $replaceSearch = [' ', '-', ''];
        $firstSlug = str_replace(
            $search,
            $replaceSearch,
            StringHandler::slugify(str_replace($search, $replaceSearch, $firstStr), '-')
        );
        $secondSlug = str_replace(
            $search,
            $replaceSearch,
            StringHandler::slugify(str_replace($search, $replaceSearch, $secondStr), '-')
        );
        echo sprintf("%s | %s\n", $firstSlug, $secondSlug);
        return StringHandler::strContainsStr(str_replace($search, $replaceSearch, $firstSlug), str_replace($search, $replaceSearch, $secondSlug));
    }
}