<?php

namespace Integration_v2\magalu\Services;

use Integration\Integration_v2\magalu\ToolsProduct;

/**
 * Class BaseProductImportService
 * @package Integration_v2\magalu\Services
 * @property ToolsProduct $toolsProduct
 */
abstract class BaseProductImportService
{

    protected $normalizedProduct;

    public function __construct(ToolsProduct $toolsProduct)
    {
        $this->toolsProduct = $toolsProduct;
    }

    public function handleWithRawList(array $products)
    {
        foreach ($products as $product) {
            if (!$this->enabledToImport($product)) continue;
            $this->normalizedProduct = null;
            $reference = !empty($product->sku ?? '') ? "({$product->sku})" : '';
            try {
                echo "[PROCESS][LINE:" . __LINE__ . "] Importando produto #{$product->id} - {$product->title} {$reference}\n";
                $this->importProduct($product);
            } catch (\Throwable $e) {
                $this->toolsProduct->log_integration(
                    "Ocorreu um erro ao importar/atualizar o produto #{$product->id} - {$product->title} {$reference}",
                    $e->getMessage(),
                    'E'
                );
                echo "[ERROR][LINE:" . __LINE__ . "] Ocorreu um erro ao importar/atualizar o produto #{$product->id} - {$product->title} {$reference}\n";
            }
        }
    }

    protected function importProduct(object $product)
    {
        $this->toolsProduct->setUniqueId("{$product->id}");
        $parsedProduct = $this->parserRawProduct($product);
        $this->handleParsedProduct($parsedProduct, $product);
    }

    protected function parserRawProduct(object $product): array
    {
        return $this->toolsProduct->getDataFormattedToIntegration($product);
    }

    public function handleParsedProduct($parsedProduct, $product = null)
    {
        $this->normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
        $productSku = $this->normalizedProduct['sku'];
        try {
            if (
                !empty($product) &&
                !empty($product->variant) && (
                    empty($parsedProduct['variations']['value']) ||
                    count($parsedProduct['variations']['value']) != count($product->variant)
                )
            ) {
                throw new \Exception("Foram encontrados variações, mas nem todos os SKUs do produto ($productSku) contem variação.");
            }

            $productIdErp = $this->normalizedProduct['_product_id_erp'];
            if (($this->normalizedProduct['id'] ?? 0) > 0) {
                $this->updateProduct($parsedProduct);
            } else {
                $this->sendProduct($parsedProduct);
            }
            $this->updateProductIdIntegration(
                $productSku,
                $productIdErp
            );
            $parsedVariations = $this->toolsProduct->getParsedVariations();
            $this->handleWithParsedVariations($parsedVariations, $productSku);
        } catch (\Exception $e) {
            $this->toolsProduct->log_integration(
                "Ocorreu um erro ao importar o produto {$productSku} - {$this->normalizedProduct['name']}",
                $e->getMessage(),
                'E'
            );
        }
    }

    protected abstract function enabledToImport($product): bool;

    protected function updateProduct($parsedProduct)
    {
        $isPublished = $this->normalizedProduct['_published'];
        //if (!$isPublished) {
            $this->toolsProduct->updateProduct($parsedProduct);
        //}
        $hasVariations = !empty($this->normalizedProduct['variations']);
        if (!$hasVariations) {
            $this->updateStockProduct($this->normalizedProduct['sku'], $this->normalizedProduct['stock']);
        }
        $this->updatePriceProduct($this->normalizedProduct['sku'], $this->normalizedProduct['price'], $this->normalizedProduct['list_price']);
    }

    protected function updateStockProduct($productSku, $stock, $varSku = null)
    {
        $this->toolsProduct->updateStockProduct($productSku, $stock, $varSku ?? null);
    }

    protected function updatePriceProduct($productSku, $price, $listPrice, $varSku = null)
    {
        $this->toolsProduct->updatePriceProduct($productSku, $price, $listPrice, $varSku ?? null);
    }

    protected function sendProduct($parsedProduct)
    {
        $this->toolsProduct->sendProduct($parsedProduct);
    }

    protected function handleWithParsedVariations($parsedVariations, $productSku)
    {
        foreach ($parsedVariations as $parsedVariation) {
            $this->handleWithParsedVariation($parsedVariation, $productSku);
        }
    }

    protected function handleWithParsedVariation($parsedVariation, $productSku)
    {
        echo sprintf("[PROCESS][LINE:" . __LINE__ . "] Variation: %s \n", json_encode($this->normalizedProduct));
        if ($parsedVariation['id'] > 0) {
            $this->updateVariation($parsedVariation, $productSku);
        } else {
            if (($this->normalizedProduct['id'] ?? 0) > 0) {
                $this->createVariation($parsedVariation, $productSku);
            }
        }
        $this->updateProductIdIntegration(
            $productSku,
            $parsedVariation['_variant_id_erp'],
            $parsedVariation['sku']
        );
    }

    protected function updateProductIdIntegration($productSku, $productIdErp, $variationSku = null)
    {
        $this->toolsProduct->updateProductIdIntegration(
            $productSku,
            $productIdErp,
            $variationSku
        );
    }

    protected function updateVariation($parsedVariation, $productSku)
    {
        if (!$parsedVariation['_published']) {
            $this->toolsProduct->updateVariation($parsedVariation, $productSku);
        }
        $this->updateStockProduct($productSku, $parsedVariation['stock'], $parsedVariation['sku']);
        $this->updatePriceProduct($productSku, $parsedVariation['price'], $parsedVariation['list_price'], $parsedVariation['sku']);
    }

    protected function createVariation($parsedVariation, $productSku)
    {
        $this->toolsProduct->createVariation($parsedVariation, $productSku);
    }

}