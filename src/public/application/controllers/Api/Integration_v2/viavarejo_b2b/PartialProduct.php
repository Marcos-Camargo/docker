<?php

require_once APPPATH . "controllers/Api/Integration_v2/viavarejo_b2b/ViaProductController.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductCatalogUpdateService.php";

use Integration_v2\viavarejo_b2b\Services\ProductCatalogUpdateService;

/**
 * Class PartialProduct
 * @property ProductCatalogUpdateService $productCatalogService
 */
class PartialProduct extends ViaProductController
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        $this->toolsProduct->setJob(self::class);
        $this->productCatalogService = new ProductCatalogUpdateService($this->toolsProduct);
    }

    protected function handlePostRequest()
    {
        $this->productCatalogService->handleWithRawObject($this->rawRequestContent);
    }
}