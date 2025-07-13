<?php

/**
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/Queue/StockNotification run {ID} {STORE_ID} {QUEUE_ID} {TOPIC}
 */

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/Queue/BaseProductNotification.php";

class StockNotification extends BaseProductNotification
{
    protected $topic = 'stock';

    protected function queueDataHandler(array $queueNotification)
    {
        $data = $queueNotification['data'];
        $params = (array)$data->origin;
        $this->toolsProduct = $this->buildServiceProvider($params);
        if (strcasecmp($params['topic'], 'Stock') === 0) {
            require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductStockService.php";
            $productStockService = new \Integration_v2\viavarejo_b2b\Services\ProductStockService($this->toolsProduct);
            $productStockService->handleWithRawObject($data->content ?? (object)[]);
        }
    }
}