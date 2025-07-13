<?php

namespace Integration_v2\viavarejo_b2b\Services;

use Error;
use Exception;
use \Integration\Integration_v2\viavarejo_b2b\ToolsProduct;
use Throwable;

/**
 * Class ProductCatalogService
 * @package Integration_v2\viavarejo_b2b\Services
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

    public function handleWithRawObject(object $object): array
    {
        $productsError = array();

        if (property_exists($object, 'Produtos')) {
            $object = $object->Produtos;
        }

        foreach ($object as $product) {
            try {
                $this->toolsProduct->setUniqueId("$product->Codigo");
                $parsedProduct = $this->parserRawProduct($product);
                $this->handleParsedProduct($parsedProduct);
                echo "Processou produto: $product->Codigo\n";
            } catch (Throwable | Exception | Error $e) {
                $productsError[] = $product;
                $this->toolsProduct->log_integration(
                    "Ocorreu um erro ao importar/atualizar o produto $product->Codigo - $product->DisplayName",
                    $e->getMessage(),
                    'E'
                );
                echo "Não processou produto: $product->Codigo - {$e->getMessage()}\n";
            }
        }

        return $productsError;
    }

    protected function parserRawProduct(object $product): array
    {
        return $this->toolsProduct->getDataFormattedToIntegration($product);
    }

    /**
     * @throws Exception
     */
    public function handleParsedProduct($parsedProduct)
    {
        // Não atualiza preço e estoque por aqui, somente no ProductAvailabilityService e ProductStockService.
        if (!empty($parsedProduct['id']['value'] ?? 0)) {
            unset($parsedProduct['price']);
            unset($parsedProduct['list_price']);
            unset($parsedProduct['stock']);
        }

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
                    $this->updateVariation($parsedVariation, $productSku);
                } else {
                    if (isset($this->normalizedProduct['id']) && $this->normalizedProduct['id'] > 0) {
                        $this->createVariation($parsedVariation, $productSku);
                    }
                }
                $this->updateProductIdIntegration(
                    $productSku,
                    $parsedVariation['_variant_id_erp'],
                    $parsedVariation['sku']
                );
            }
        } catch (Exception $e) {
            $this->toolsProduct->log_integration(
                "Ocorreu um erro ao importar o produto {$productSku} - {$this->normalizedProduct['name']}",
                $e->getMessage(),
                'E'
            );

            throw new Exception($e->getMessage());
        }
    }

    protected function updateProduct($parsedProduct)
    {
        $isPublished = $this->normalizedProduct['_published'];
        //if (!$isPublished) {
            $this->toolsProduct->updateProduct($parsedProduct);
        //}
        // Não atualiza preço e estoque por aqui, somente no ProductAvailabilityService e ProductStockService.
        /*$hasVariations = !empty($this->normalizedProduct['variations']);
        if (!$hasVariations && array_key_exists('stock', $this->normalizedProduct)) {
            $this->toolsProduct->updateStockProduct($this->normalizedProduct['sku'], $this->normalizedProduct['stock']);
        }
        if (array_key_exists('price', $this->normalizedProduct) && array_key_exists('list_price', $this->normalizedProduct)) {
            $this->toolsProduct->updatePriceProduct($this->normalizedProduct['sku'], $this->normalizedProduct['price'], $this->normalizedProduct['list_price']);
        }*/
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

    protected function updateVariation($parsedVariation, $productSku)
    {
        if (!$parsedVariation['_published']) {
            $this->toolsProduct->updateVariation($parsedVariation, $productSku);
        }
        // Não atualiza preço e estoque por aqui, somente no ProductAvailabilityService e ProductStockService.
        /*$this->toolsProduct->updateStockProduct($productSku, $parsedVariation['stock'], $parsedVariation['sku']);
        $this->toolsProduct->updatePriceProduct($productSku, $parsedVariation['price'], $parsedVariation['list_price'], $parsedVariation['sku']);*/
    }

    protected function createVariation($parsedVariation, $productSku)
    {
        $this->toolsProduct->createVariation($parsedVariation, $productSku);
    }
}