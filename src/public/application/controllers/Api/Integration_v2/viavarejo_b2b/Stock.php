<?php

require_once APPPATH . "controllers/Api/Integration_v2/viavarejo_b2b/ViaProductController.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductStockService.php";

use Integration_v2\viavarejo_b2b\Services\ProductStockService;

/**
 * Class Availability
 * @property ProductStockService $productStockService
 */
class Stock extends ViaProductController
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        $this->toolsProduct->setJob(self::class);
        $this->productStockService = new ProductStockService($this->toolsProduct);
    }

    protected function handlePostRequest()
    {
        $this->productStockService->handleWithRawObject($this->rawRequestContent);
    }
}