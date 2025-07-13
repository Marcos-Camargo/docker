<?php

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/BaseProductBatch.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductCatalogCreateService.php";

use Integration_v2\viavarejo_b2b\Services\ProductCatalogCreateService;

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/CreateProduct run {ID} {STORE} {QUEUEID}
 * @property ProductCatalogCreateService $productCatalogService
 */
class CreateProduct extends BaseProductBatch
{

    protected $productCatalogService;

    public function __construct()
    {
        parent::__construct();
        $this->toolsProduct->setJob(__CLASS__);
    }

    protected function initializeServiceProvider()
    {
        $this->productCatalogService = new ProductCatalogCreateService($this->toolsProduct);
    }

    protected function handleDeserializedXML(object $object)
    {
        return $this->productCatalogService->handleWithRawObject($object);
    }
}