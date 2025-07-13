<?php

namespace Integration_v2\hub2b\Services;

use \Integration\Integration_v2\hub2b\ToolsProduct;

/**
 * Class ProductCatalogService
 * @package Integration_v2\hub2b\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductCatalogService
{

    protected $toolsProduct;

    protected $normalizedProduct;

    protected $parentProducts = [];

    public function __construct(ToolsProduct $toolsProduct)
    {
        $this->toolsProduct = $toolsProduct;
    }

    public function handleWithRawList(array $products)
    {

        foreach ($products as $product) {
            $this->normalizedProduct = null;
            $id = $product->id ?? $product->sourceId ?? $product->skus->source ?? $product->skus->destination ?? null;
            $sku = $product->skus->source ?? $product->skus->destination ?? null;
            try {
                if (!$this->enabledToImport($product)) continue;
                $this->importProduct($product);
                echo sprintf("Produto importado #%s - %s %s\n", $id, $product->name, $sku);
            } catch (\Throwable $e) {
                $this->toolsProduct->setStatusProductIntegration($sku, 4);
                $this->toolsProduct->log_integration(
                    "Ocorreu um erro ao importar/atualizar o produto #{$id} - {$product->name} {$sku}",
                    $e->getMessage(),
                    'E'
                );
                echo sprintf("Ocorreu um erro ao importar/atualizar o produto #%s - %s %s\n", $id, $product->name, $sku);
            }
        }
    }

    protected function enabledToImport($product): bool
    {
        $parentSku = $product->groupers->parentSku ?? null;
        if (!empty($parentSku)) {
            if (isset($this->parentProducts[$parentSku])) return false;
            $this->parentProducts[$parentSku] = $parentSku;
        }
        //Tipo do Produto: 1 é Simples, 2 é Produto Pai, 3 é Variação/Produto Filho.
        return (($product->idProductType ?? 0) === 1) || (($product->idProductType ?? 0) === 3);
    }

    protected function getProductFromIntegrationById($productId)
    {
        $product = $this->toolsProduct->getDataProductIntegration((string)$productId);
        return !isset($product->id) ? (object)[] : $product;
    }

    protected function importProduct(object $product)
    {
        $id = $product->id ?? $product->sourceId ?? $product->skus->source ?? $product->skus->destination ?? null;
        $this->toolsProduct->setUniqueId("{$id}");
        $parsedProduct = $this->parserRawProduct($product);
        $this->handleParsedProduct($parsedProduct);
    }

    protected function parserRawProduct(object $product): array
    {
        return $this->toolsProduct->getDataFormattedToIntegration($product);
    }

    public function handleParsedProduct($parsedProduct)
    {
        $this->normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
        $productSku = $this->normalizedProduct['sku'];
        echo sprintf("Produto %s:\n%s\n", $productSku, json_encode($this->normalizedProduct));
        try {
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
            $this->toolsProduct->setStatusProductIntegration($productSku, 4);
            $this->toolsProduct->log_integration(
                "Ocorreu um erro ao importar o produto {$productSku} - {$this->normalizedProduct['name']}",
                $e->getMessage(),
                'E'
            );
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
        $this->toolsProduct->confirmImportProduct([
            'externalSku' => $this->normalizedProduct['_external_sku'],
            'sku' => $this->normalizedProduct['sku']
        ]);
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
            $this->toolsProduct->confirmImportProduct([
                'externalSku' => $parsedVariation['_external_sku'],
                'sku' => $parsedVariation['sku']
            ]);
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