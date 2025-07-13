<?php

/**
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/Queue/PartialProductNotification run {ID} {STORE_ID} {QUEUE_ID} {TOPIC}
 */

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/Queue/BaseProductNotification.php";

class PartialProductNotification extends BaseProductNotification
{
    protected $topic = 'partialproduct';

    protected function queueDataHandler(array $queueNotification)
    {
        $data = $queueNotification['data'];
        $params = (array)$data->origin;
        $this->toolsProduct = $this->buildServiceProvider($params);
        if (strcasecmp($params['topic'], 'PartialProduct') === 0) {
            require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductCatalogUpdateService.php";
            $productCatalogService = new \Integration_v2\viavarejo_b2b\Services\ProductCatalogUpdateService($this->toolsProduct);
            $productCatalogService->handleWithRawObject($data->content ?? (object)[]);
        }
    }
}