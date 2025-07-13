<?php

/**
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/Queue/TrackingNotification run {ID} {STORE_ID} {QUEUE_ID} {TOPIC}
 */

require_once APPPATH . "libraries/Integration_v2/Order_v2.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/ToolsOrder.php";
require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/Queue/QueueNotifications.php";

/**
 * Class TrackingNotification
 * @property \Integration\viavarejo_b2b\ToolsOrder $toolsOrder
 * @property \Integration\Integration_v2\Order_v2 $order_v2
 */
class TrackingNotification extends QueueNotifications
{
    protected $topic = 'tracking';

    protected function queueDataHandler(array $queueNotification)
    {
        $data = $queueNotification['data'];
        $params = (array)$data->origin;

        if (strcasecmp($params['topic'], 'Tracking') === 0) {
            $this->toolsOrder = $this->buildServiceProvider($params);
            require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/OrderTrackingService.php";
            $orderTrackingService = new \Integration_v2\viavarejo_b2b\Services\OrderTrackingService($this->toolsOrder);
            $orderTrackingService->handleWithRawObject($data->content ?? (object)[]);
        }
    }

    protected function buildServiceProvider($params)
    {
        $this->order_v2 = new \Integration\Integration_v2\Order_v2();
        $this->order_v2->setJob($params['topic']);
        $this->order_v2->startRun($params['storeId']);

        $this->toolsOrder = new \Integration\viavarejo_b2b\ToolsOrder($this->order_v2);
        return $this->toolsOrder;
    }
}