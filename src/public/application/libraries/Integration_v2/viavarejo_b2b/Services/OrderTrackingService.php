<?php


namespace Integration_v2\viavarejo_b2b\Services;

use Integration\viavarejo_b2b\ToolsOrder;

/**
 * Class OrderTrackingService
 * @package Integration_v2\viavarejo_b2b\Services
 * @property ToolsOrder $toolsOrder
 */
class OrderTrackingService
{
    protected $toolsOrder;

    public function __construct(ToolsOrder $toolsOrder)
    {
        $this->toolsOrder = $toolsOrder;
    }

    public function handleWithRawObject(object $object)
    {
        foreach ($object->Trackings as $tracking) {
            try {
                $this->toolsOrder->getOrderV2()->setUniqueId($tracking->IdPedidoParceiro);
                $this->handleReceivedTracking($tracking);
            } catch (\Throwable $e) {
                $this->toolsOrder->getOrderV2()->log_integration(
                    "Ocorreu um erro ao inserir as informações de rastreamento do pedido {$tracking->IdPedidoParceiro}",
                    $e->getMessage(),
                    'E'
                );
            }
        }
    }

    protected function handleReceivedTracking(object $rawTracking)
    {
        try {
            return $this->toolsOrder->updateOrderFromIntegration($rawTracking);
        } catch (\InvalidArgumentException $e) {
            $this->toolsOrder->getOrderV2()->log_integration(
                $e->getMessage(),
                $e->getPrevious()->getMessage(),
                'E'
            );
        }
    }

}