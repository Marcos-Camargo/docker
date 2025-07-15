<?php
use PHPUnit\Framework\TestCase;

class BatchBackground_Controller {public function __construct(){} protected function log_data($m,$a,$v,$t='I'){} }

class GetOrders extends BatchBackground_Controller {
    public static $lastInstance;
    public $received;
    public function __construct(){self::$lastInstance=$this;}
    public function processPartialShipping($billNo, array $data){
        $this->received = [$billNo,$data];
        return ['success'=>true,'tracking_code'=>$data['tracking_code']];
    }
}

require_once APPPATH.'controllers/Api/V1/PartialShipping.php';

class PartialShippingControllerTest extends TestCase
{
    public function test_index_post_validates_fields()
    {
        $controller = new PartialShipping();
        $result = $controller->index_post([]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('bill_no', $result['message']);
    }

    public function test_index_post_calls_batch_controller()
    {
        $controller = new PartialShipping();
        $payload = [
            'bill_no' => 'ORDER1',
            'tracking_code' => 'TRK',
            'carrier' => 'CARR',
            'shipping_date' => '2020-01-01'
        ];
        $result = $controller->index_post($payload);
        $this->assertTrue($result['success']);
        $this->assertSame('TRK', $result['tracking_code']);
        $this->assertSame(['ORDER1', [
            'tracking_code' => 'TRK',
            'carrier' => 'CARR',
            'shipping_date' => '2020-01-01'
        ]], GetOrders::$lastInstance->received);
    }
}
