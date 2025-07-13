<?php

namespace Integration_v2\tray\Services;

use \Integration\Integration_v2\tray\ToolsProduct;

/**
 * Class ProductCatalogService
 * @package Integration_v2\tray\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductCatalogService
{

    protected $toolsProduct;

    protected $normalizedProduct;

    public function __construct(ToolsProduct $toolsProduct)
    {
        $this->toolsProduct = $toolsProduct;
    }

    public function handleWithRawList(array $products)
    {
        foreach ($products as $product) {
            $this->normalizedProduct = null;
            try {
                $product = $product->Product;
                if (!$this->enabledToImport($product)) continue;
                $product = $this->getProductFromIntegrationById($product->id);
                $this->importProduct($product);
            } catch (\Throwable $e) {
                $reference = !empty($product->reference ?? '') ? "({$product->reference})" : '';
                $this->toolsProduct->log_integration(
                    "Ocorreu um erro ao importar/atualizar o produto #{$product->id} - {$product->name} {$reference}",
                    $e->getMessage(),
                    'E'
                );
            }
        }
    }

    protected function getProductFromIntegrationById($productId)
    {
        $product = $this->toolsProduct->getDataProductIntegration((string)$productId);
        return !isset($product->id) ? (object)[] : $product;
    }

    protected function importProduct(object $product)
    {
        $parsedProduct = $this->parserRawProduct($product);
        $this->handleParsedProduct($parsedProduct);
    }

    protected function enabledToImport($product): bool
    {
        $integrationSkuField = $this->toolsProduct->getIntegrationSkuField();
        return (((int)$product->is_kit ?? 0) === 0) && !empty($product->{$integrationSkuField});
    }

    protected function parserRawProduct(object $product): array
    {
        return $this->toolsProduct->getDataFormattedToIntegration($product);
    }

    public function handleParsedProduct($parsedProduct)
    {
        $this->normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
        $productSku = $this->normalizedProduct['sku'];
        $this->toolsProduct->setUniqueId("{$productSku}");
        try {
            $productIdErp = $this->normalizedProduct['_product_id_erp'];
            if (($this->normalizedProduct['id'] ?? 0) > 0) {
                $this->updateProduct($parsedProduct, $this->normalizedProduct['sku']);
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
            /*$this->toolsProduct->log_integration(
                "Ocorreu um erro ao importar o produto {$productSku} - {$this->normalizedProduct['name']}",
                $e->getMessage(),
                'E'
            );*/
        }
    }

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