<?php

namespace Integration_v2\lojaintegrada\Services;

use \Integration\Integration_v2\lojaintegrada\ToolsProduct;

/**
 * Class ProductCatalogService
 * @package Integration_v2\lojaintegrada\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductCatalogService
{

    protected $toolsProduct;

    protected $normalizedProduct = [];

    public function __construct(ToolsProduct $toolsProduct)
    {
        $this->toolsProduct = $toolsProduct;
    }

    public function handleWithRawList(array $products)
    {
        foreach ($products as $product) {
            try {
                if (strcasecmp($product->nome, 'none') === 0) continue;
                $this->toolsProduct->setUniqueId("{$product->sku}");
                $product = $this->toolsProduct->getDataProductIntegration((string)$product->id);
                if (!$this->enabledToImport($product)) continue;
                $parsedProduct = $this->parserRawProduct($product);
                $this->handleParsedProduct($parsedProduct);
            } catch (\Throwable $e) {
               $this->toolsProduct->log_integration(
                    "Ocorreu um erro ao importar/atualizar o produto {$product->sku} - {$product->nome}",
                    "{$e->getMessage()}",
                    'E'
                );
            }
        }
    }

    protected function enabledToImport($product): bool
    {
        $product->nome = trim($product->nome ?? '');
        return (
            (strcasecmp($product->tipo, ToolsProduct::TYPE_VARIABLE_PRODUCT) === 0
                || strcasecmp($product->tipo, ToolsProduct::TYPE_NORMAL_PRODUCT) === 0)
            && !($product->removido ?? false)
            && !empty($product->nome)
            && strcasecmp($product->nome, 'none') !== 0
            && strpos($product->nome, 'DUPLICADO -') !== 0
        );
    }

    protected function parserRawProduct(object $product): array
    {
        return $this->toolsProduct->getDataFormattedToIntegration($product);
    }

    public function handleParsedProduct($parsedProduct)
    {
        $this->normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
        $productSku = $this->normalizedProduct['sku'];
        try {
            $productIdErp = $this->normalizedProduct['_product_id_erp'];
            if (isset($this->normalizedProduct['id']) && $this->normalizedProduct['id'] > 0) {
                $this->updateProduct($parsedProduct);
            } else {
                $this->sendProduct($parsedProduct);
            }
            $this->updateProductIdIntegration(
                $productSku,
                $productIdErp
            );
            $parsedVariations = $this->toolsProduct->getParsedVariations();
            foreach ($parsedVariations as $parsedVariation) {
                if ($parsedVariation['id'] > 0) {
                    $this->updateVariation($parsedVariation);
                } else {
                    if (isset($this->normalizedProduct['id']) && $this->normalizedProduct['id'] > 0) {
                        $this->createVariation($parsedVariation);
                    }
                }
                $this->updateProductIdIntegration(
                    $productSku,
                    $parsedVariation['_variant_id_erp'],
                    $parsedVariation['sku']
                );
            }
        } catch (\Exception $e) {
           /* $this->toolsProduct->log_integration(
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
            $this->toolsProduct->updateStockProduct($this->normalizedProduct['sku'], $this->normalizedProduct['stock']);
        }
        $this->toolsProduct->updatePriceProduct($this->normalizedProduct['sku'], $this->normalizedProduct['price'], $this->normalizedProduct['list_price']);
    }

    protected function sendProduct($parsedProduct)
    {
        $this->toolsProduct->sendProduct($parsedProduct);
    }

    protected function updateProductIdIntegration($productSku, $productIdErp, $variationSku = null)
    {
        $this->toolsProduct->updateProductIdIntegration(
            $productSku,
            $productIdErp,
            $variationSku
        );
    }

    protected function updateVariation($parsedVariation)
    {
        if (!$parsedVariation['_published']) {
            $this->toolsProduct->updateVariation($parsedVariation, $this->normalizedProduct['sku']);
        }
        $this->toolsProduct->updateStockProduct($this->normalizedProduct['sku'], $parsedVariation['stock'], $parsedVariation['sku']);
        $this->toolsProduct->updatePriceProduct($this->normalizedProduct['sku'], $parsedVariation['price'], $parsedVariation['list_price'], $parsedVariation['sku']);
    }

    protected function createVariation($parsedVariation)
    {
        $this->toolsProduct->createVariation($parsedVariation, $this->normalizedProduct['sku']);
    }
}