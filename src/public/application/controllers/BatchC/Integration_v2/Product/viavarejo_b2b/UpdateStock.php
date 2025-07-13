<?php

use Integration_v2\viavarejo_b2b\Services\ProductStockService;
use Integration\Integration_v2;

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/BaseProductBatch.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductStockService.php";

/**
 * Class UpdatePriceStock
 *
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/UpdatePriceStock run {ID} {STORE} {QUEUEID}
 * @property ProductStockService $productStockService
 *
 */
class UpdateStock extends BaseProductBatch
{

    protected $productStockService;

    public function __construct()
    {
        parent::__construct();
        $this->toolsProduct->setJob(__CLASS__);
    }

    protected function initializeServiceProvider()
    {
        $this->toolsProduct->setIgnoreIntegrationLogTypes([
            Integration_v2::LOG_TYPE_ERROR
        ]);
        $this->productStockService = new ProductStockService($this->toolsProduct);
    }

    protected function handleDeserializedXML(object $object): array
    {
        return $this->productStockService->handleWithRawObject($object);
    }

}