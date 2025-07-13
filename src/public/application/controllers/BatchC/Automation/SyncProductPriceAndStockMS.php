<?php

/**
 * php index.php BatchC/Automation/SyncProductPriceAndStockMS run {ID} {STORE_ID}
 */

require_once APPPATH . "libraries/Logistic/vendor/autoload.php";

use Microservices\v1\Integration\Price;
use Microservices\v1\Integration\Stock;

/**
 * Class SyncProductPriceAndStockMS
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_DB_query_builder $db
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_products $model_products
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_promotions $model_promotions
 *
 * @property Price $ms_price
 * @property Stock $ms_stock
 */
class SyncProductPriceAndStockMS extends BatchBackground_Controller
{

    protected $storeId;

    protected $syncStock = true;
    protected $syncPrice = true;

    public function __construct()
    {
        parent::__construct();
        $this->session->set_userdata([
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true
        ]);
        $this->load->model([
            'model_settings',
            'model_stores',
            'model_products',
            'model_products_marketplace',
            'model_campaigns_v2',
            'model_promotions'
        ]);
        $this->load->library("Microservices\\v1\\Integration\\Price", array(), 'ms_price');
        $this->load->library("Microservices\\v1\\Integration\\Stock", array(), 'ms_stock');
    }

    public function run($id = null, $storeId = null, $syncPrice = true, $syncStock = true): bool
    {
        $this->storeId = $storeId == 'null' ? null : $storeId;
        $this->syncPrice = in_array($syncPrice, [1, '1', 'true']);
        $this->syncStock = in_array($syncStock, [1, '1', 'true']);
        $params = implode(' ', array_slice(func_get_args(), 1));
        try {
            $logName = __DIR__ . '/' . get_class($this) . '/' . __FUNCTION__;
            if (!$this->checkStartRun(
                $logName,
                $this->router->directory,
                get_class($this),
                $id ?? '',
                !empty($params) ? "{$params}" : date('YmdHi')
            )) {
                return false;
            }
            /*if (!$this->ms_stock->use_ms_stock && !$this->ms_price->use_ms_price) {
                throw new Exception('Feature flag de "ms_stock" e "ms_price" desabilitadas.');
            }*/
            $this->process();
        } catch (Throwable $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
        }
        $this->gravaFimJob();
        return true;
    }

    protected function process()
    {
        echo "[PROCESS][LINE:" . __LINE__ . "] - Carregando lojas ativas...\n";
        $stores = $this->loadEnabledStores();
        foreach ($stores ?? [] as $store) {
            $this->loadProductsByStore($store);
        }
    }

    protected function loadEnabledStores(): array
    {
        $this->storeId = explode('_', $this->storeId) ?? [];
        $this->db->select(['s.*'])->from('stores s')
            ->where('s.active', 1);
        if (!empty($this->storeId[0] ?? 0)) {
            $this->db->where_in('s.id', $this->storeId);
            return $this->db->get()->result_array() ?? [];
        }
        $stores = $this->db->order_by(null, 'RANDOM')->get()->result_array() ?? [];
        $chunckCount = count($stores) / 5;
        $chunckCount = $chunckCount > 20 ? 5 : ($chunckCount > 10 ? 3 : 1);
        $chunckStores = array_chunk($stores, $chunckCount);
        $commands = [];
        foreach ($chunckStores ?? [] as $chuncked) {
            $storesId = implode('_', array_column($chuncked, 'id'));
            echo "Criando Comandos de execução lojas " . str_replace('_', ',', $storesId) . "...\n";
            $commands[] = sprintf("php index.php BatchC/Automation/%s run null %s %s %s >/dev/null",
                get_class($this),
                $storesId,
                $this->syncPrice ? 'true' : 'false',
                $this->syncStock ? 'true' : 'false'
            );
        }
        $chunkCommands = array_chunk($commands, 1);
        foreach ($chunkCommands as $k => $chunkCommand) {
            $shellCommands = implode(' && ', array_merge([sprintf("cd %s", FCPATH)], $chunkCommand));
            $shellCommands = sprintf("%s %s", $shellCommands, '&');
            echo "Executando Comando: " . $shellCommands . "...\n";
            exec($shellCommands);
        }
        return [];
    }

    protected function loadProductsByStore(array $store)
    {
        $limit = 100;
        $offset = 0;
        $criteria = [
            'store_id' => $store['id'],
            'status' => [
                Model_products::ACTIVE_PRODUCT, Model_products::INACTIVE_PRODUCT, Model_products::BLOCKED_PRODUCT
            ]
        ];
        while (true) {
            try {
                echo "[PROCESS][LINE:" . __LINE__ . "] - Carregando produtos da loja {$store['name']} #{$store['id']} - {$offset} {$limit}\n";
                $products = $this->model_products->getProductsToDisplayByCriteria($criteria, $offset, $limit);
                if (empty($products)) {
                    break;
                }
                $this->syncProducts($products);
            } catch (Throwable $e) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            }
            $offset += $limit;
        }
    }

    protected function syncProducts(array $products)
    {
        foreach ($products as $product) {
            echo "[PROCESS][LINE:" . __LINE__ . "] - Sincronizando produto {$product['name']} ({$product['sku']})\n";
            $this->syncProduct($product);
        }
    }

    protected function syncProduct(array $product)
    {
        $this->syncPriceStock($product);
        $this->syncPriceStockCatalog($product);
        $this->syncPriceStockWithMarketPlaces($product);

        $product['var_ids'] = explode(',', $product['var_ids'] ?? '') ?? [];
        if (!empty($product['var_ids'])) {
            $variations = $this->model_products->getProductVariants($product['id'], $product['has_variants'] ?? '') ?: [];
            foreach ($variations ?? [] as $k => $variation) {
                if (!is_numeric($k)) continue;
                $variation['productId'] = $product['id'];
                $variation['name'] = "{$product['name']}: {$variation['variant']}";
                $variation['marketplaces'] = $product['marketplaces'] ?? '';
                $this->syncVariation($variation);
            }
        }
    }

    protected function syncPriceStockWithMarketPlaces(array $data)
    {
        $marketplaces = explode(',', $data['marketplaces'] ?? '');
        foreach ($marketplaces ?? [] as $marketplace) {
            if (empty($marketplace)) continue;
            $data['marketplace'] = $marketplace;
            $this->syncPriceStockMarketPlace($data);
            $this->syncPriceCampaigns($data);
            $this->syncPricePromotions($data);
        }
    }

    protected function syncVariation($variation)
    {
        $this->syncPriceStock($variation);
        $this->syncPriceStockCatalog($variation);
        $this->syncPriceStockWithMarketPlaces($variation);
    }

    protected function syncPriceStock(array $data)
    {
        if ($this->syncPrice) {
            try {
                echo "[PROCESS][LINE:" . __LINE__ . "] - Sincronizando preço do produto {$data['id']} - {$data['name']}\n";
                $this->ms_price->updateProductPrice($data['productId'], $data['variant'] ?? null, $data['price'], $data['list_price']);
            } catch (Throwable $e) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            }
        }
        if ($this->syncStock) {
            try {
                echo "[PROCESS][LINE:" . __LINE__ . "] - Sincronizando estoque do produto {$data['id']} - {$data['name']}\n";
                $this->ms_stock->updateProductStock($data['productId'], $data['variant'] ?? null, $data['var_qty_total'] ?? $data['qty']);
            } catch (Throwable $e) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            }
        }
    }

    protected function syncPriceStockCatalog(array $data)
    {

    }

    protected function syncPriceStockMarketPlace(array $data)
    {
        if ($this->syncPrice) {
            try {
                $price_marketplace = $this->model_products_marketplace->getDataByUniqueKey($data['marketplace'], $data['productId'], $data['variant'] ?? '');

                if (!empty($price_marketplace) && !$price_marketplace['same_price'] && !empty($price_marketplace['price'])) {
                    echo "[PROCESS][LINE:" . __LINE__ . "] - Sincronizando preço do produto {$data['id']} - {$data['name']} no marketplace {$data['marketplace']}\n";
                    $data['price'] = $price_marketplace['price'];
                    $this->ms_price->updateMarketplacePrice($data['productId'], $data['variant'] ?? null, $data['marketplace'], $data['price'], $data['list_price']);
                }
            } catch (Throwable $e) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            }
        }
        if ($this->syncStock) {
            try {
                $stock_marketplace = $this->model_products_marketplace->getDataByUniqueKey($data['marketplace'], $data['productId'], $data['qty']);

                if (!empty($stock_marketplace) && !$stock_marketplace['same_qty'] && !empty($stock_marketplace['qty'])) {
                    echo "[PROCESS][LINE:" . __LINE__ . "] - Sincronizando estoque do produto {$data['id']} - {$data['name']} no marketplace {$data['marketplace']}\n";
                    $data['qty'] = $stock_marketplace['qty'];
                    $this->ms_stock->updateMarketplaceStock($data['productId'], $data['variant'] ?? null, $data['marketplace'], $data['qty']);
                }
            } catch (Throwable $e) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            }
        }
    }

    protected function syncPriceCampaigns(array $data)
    {
        if ($this->syncPrice) {
            try {
                $price = $this->model_campaigns_v2->getProductPriceInCampaigns($data['productId'], $data['marketplace']);

                if (!empty($price)) {
                    echo "[PROCESS][LINE:" . __LINE__ . "] - Sincronizando preço do produto na campanha {$data['id']} - {$data['name']} no marketplace {$data['marketplace']}\n";
                    $data['price'] = $price;
                    $this->ms_price->updateCampaignPrice($data['productId'], $data['variant'] ?? null, $data['marketplace'], $data['price'], $data['list_price']);
                }
            } catch (Throwable $e) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            }
        }
    }

    protected function syncPricePromotions(array $data)
    {
        if ($this->syncPrice) {
            try {
                $price = $this->model_promotions->getPromotionByProductAndMarketplace($data['productId'], $data['marketplace']);

                if (!empty($price)) {
                    echo "[PROCESS][LINE:" . __LINE__ . "] - Sincronizando preço do produto na promoção {$data['id']} - {$data['name']} no marketplace {$data['marketplace']}\n";
                    $data['price'] = $price;
                    $this->ms_price->updatePromotionPrice($data['productId'], $data['variant'] ?? null, $data['marketplace'], $data['price'], $data['list_price']);
                }
            } catch (Throwable $e) {
                echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            }
        }
    }
}