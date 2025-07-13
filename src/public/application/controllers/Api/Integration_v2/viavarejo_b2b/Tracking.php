<?php

require_once APPPATH . "libraries/Integration_v2/Order_v2.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/ToolsOrder.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/OrderTrackingService.php";
require_once APPPATH . "controllers/Api/Integration_v2/viavarejo_b2b/ViaHttpController.php";

use Integration\viavarejo_b2b\ToolsOrder;
use Integration\Integration_v2\Order_v2;
use Integration_v2\viavarejo_b2b\Services\OrderTrackingService;

/**
 * Class Tracking
 * @property Order_v2 $order_v2
 * @property ToolsOrder $toolsOrder
 * @property OrderTrackingService $orderTrackingService
 */
class Tracking extends ViaHttpController
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        $this->order_v2->setJob(self::class);
        $this->orderTrackingService = new OrderTrackingService($this->toolsOrder);
    }


    protected function buildToolsClass(): ToolsOrder
    {

        $this->order_v2 = new Order_v2();
        $this->order_v2->setJob(__CLASS__);
        $this->order_v2->startRun($this->storeId);

        $this->toolsOrder = new ToolsOrder($this->order_v2);
        return $this->toolsOrder;
    }

    protected function handlePostRequest()
    {
        $this->orderTrackingService->handleWithRawObject($this->rawRequestContent);
    }
}

