<?php

namespace Integration_v2\tray\Services;

require_once APPPATH . 'libraries/Integration_v2/tray/Services/ProductCatalogService.php';

use \Integration\Integration_v2\tray\ToolsProduct;

/**
 * Class ProductCatalogService
 * @package Integration_v2\tray\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductNotificationService extends ProductCatalogService
{

    protected $processedScopeIds = [];

    public function __construct(ToolsProduct $toolsProduct)
    {
        parent::__construct($toolsProduct);
        $this->processedScopeIds = [];
    }

    public function handleWithProductNotification(array $queueNotification = [])
    {
        $productId = $this->fetchQueueScopeId($queueNotification);
        $this->processedScopeIds[$productId] = $productId;
        try {
            $product = $this->getProductFromIntegrationById($productId);
            if (!$this->enabledToImport($product)) return;
            $this->importProduct($product);
        } catch (\Throwable $e) {
            $this->toolsProduct->log_integration(
                "Ocorreu um erro ao processar notificação do produto {$productId}",
                $e->getMessage(),
                'E'
            );
        }
    }

    protected function handleWithParsedVariation($parsedVariation, $productSku)
    {
        $variantId = $parsedVariation['_variant_id_erp'] ?? 0;
        $this->processedScopeIds[$variantId] = $variantId;
        parent::handleWithParsedVariation($parsedVariation, $productSku);
    }

    public function updateStockProductByNotification($queueNotification = [])
    {
        $productId = $this->fetchQueueScopeId($queueNotification);
        $isVariation = $queueNotification['is_variation'] ?? false;
        list($stock, $price) = $this->toolsProduct->fetchProductPriceStockProductVariation($productId, $isVariation);
        if (empty($stock)) return;
        try {
            $this->updateStockProduct($stock['sku'], $stock['stock'], $stock['varSku'] ?? null);
        } catch (\Throwable $e) {

        }
    }

    public function updatePriceProductByNotification($queueNotification = [])
    {
        $productId = $this->fetchQueueScopeId($queueNotification);
        $isVariation = $queueNotification['is_variation'] ?? false;
        list($stock, $price) = $this->toolsProduct->fetchProductPriceStockProductVariation($productId, $isVariation);
        if (empty($price)) return;
        try {
            $this->updatePriceProduct($price['sku'], $price['price'], $price['listPrice'], $price['varSku'] ?? null);
        } catch (\Throwable $e) {

        }
    }

    public function handleWithVariationNotification(array $queueNotification = [])
    {
        $variationId = $this->fetchQueueScopeId($queueNotification);
        $variationData = $this->toolsProduct->getDataVariationIntegration($variationId);
        if (!isset($variationData->id)) return;
        $this->toolsProduct->productValidationHandler($variationData->product_id);
        $parsedVariation = $this->toolsProduct->getVariationParsed($variationData);
        if(empty($parsedVariation)) return [];
        if (($parsedVariation['id'] ?? 0) > 0) {
            return $this->updateVariation($parsedVariation, $parsedVariation['_parent_sku']);
        }
        return $this->createVariation($parsedVariation, $parsedVariation['_parent_sku']);
    }

    public function getProcessedScopeIds(): array
    {
        return $this->processedScopeIds;
    }

    public function fetchQueueScopeId($queueNotification = [])
    {
        return $queueNotification['data']->scope_id ?? $queueNotification['scope_id'] ?? 0;
    }
}