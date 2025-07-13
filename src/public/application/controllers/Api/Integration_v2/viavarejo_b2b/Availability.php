<?php

require_once APPPATH . "controllers/Api/Integration_v2/viavarejo_b2b/ViaProductController.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductAvailabilityService.php";

use Integration_v2\viavarejo_b2b\Services\ProductAvailabilityService;

/**
 * Class Availability
 * @property ProductAvailabilityService $productAvailabilityService
 */
class Availability extends ViaProductController
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        $this->toolsProduct->setJob(self::class);
        $this->productAvailabilityService = new ProductAvailabilityService($this->toolsProduct);
    }

    protected function handlePostRequest()
    {
        $this->productAvailabilityService->handleWithRawObject($this->rawRequestContent);
    }
}