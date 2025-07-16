<?php
use PHPUnit\Framework\TestCase;

if (!class_exists('BatchBackground_Controller')) {
    class BatchBackground_Controller {public function __construct(){} protected function log_data($m,$a,$v,$t='I'){} }
}

require_once APPPATH.'controllers/BatchC/Marketplace/Conectala/GetOrders.php';

class GetOrdersInvalidJsonTest extends TestCase
{
    public function test_processOrder_throws_exception_on_invalid_json()
    {
        $integration = new class {
            public function getOrderItem($api, $code)
            {
                return ['http_code' => 200, 'content' => '{invalid'];
            }
        };

        $controller = new class($integration) extends GetOrders {
            public function __construct($integration) { $this->integration = $integration; }
            protected function log_data($m,$a,$v,$t='I'){}
        };

        $controller->api_keys = [];
        $line = ['order_code' => '123', 'paid_status' => 1, 'new_order' => false];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Resposta inválida ao consultar o pedido 123');

        $ref = new ReflectionClass(GetOrders::class);
        $method = $ref->getMethod('processOrder');
        $method->setAccessible(true);
        $method->invoke($controller, $line);
    }
}
