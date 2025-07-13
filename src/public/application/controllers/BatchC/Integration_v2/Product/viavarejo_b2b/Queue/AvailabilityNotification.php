<?php

/**
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/Queue/AvailabilityNotification run {ID} {STORE_ID} {QUEUE_ID} {TOPIC}
 */

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/Queue/BaseProductNotification.php";

class AvailabilityNotification extends BaseProductNotification
{
    protected $topic = 'availability';

    protected function queueDataHandler(array $queueNotification)
    {
        $data = $queueNotification['data'];
        $params = (array)$data->origin;
        $this->toolsProduct = $this->buildServiceProvider($params);
        if (strcasecmp($params['topic'], 'Availability') === 0) {
            require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductAvailabilityService.php";
            $productAvailabilityService = new \Integration_v2\viavarejo_b2b\Services\ProductAvailabilityService($this->toolsProduct);
            $productAvailabilityService->handleWithRawObject($data->content ?? (object)[]);
        }
    }
}