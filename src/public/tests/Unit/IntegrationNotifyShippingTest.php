<?php
use PHPUnit\Framework\TestCase;
class BatchBackground_Controller {public function __construct(){} protected function log_data($m,$a,$v,$t="I"){} }
use Tests\Fakes\FunctionMockTrait;

require_once APPPATH.'controllers/BatchC/Marketplace/Conectala/GetOrders.php';

class IntegrationNotifyShippingTest extends TestCase
{
    use FunctionMockTrait;

    public function test_notifyMarketplaceShipping_sends_request_when_flag_enabled()
    {
        \App\Libraries\FeatureFlag\FeatureManager::$client = new class {
            public function isEnabled($name,$ctx=null){return $name==='oep-2010-partial-shipping';}
        };

        $integration = new class {
            public $called = false;
            public $data;
            public function notifyShipping($api, $data){$this->called=true;$this->data=[$api,$data];return ['success'=>true];}
        };

        $controller = new class($integration) extends GetOrders {
            public function __construct($integration){$this->integration=$integration;}
            protected function log_data($m,$a,$v,$t='I'){}
        };
        $controller->api_keys = ['api_url'=>'http://localhost','access_token'=>'tk'];

        $order = ['bill_no'=>'123','order_mkt_multiseller'=>'123','shipping_carrier'=>'C','service_method'=>'S'];
        $ship = ['tracking_code'=>'TRK','shipping_carrier'=>'C','service_method'=>'S','shipping_date'=>'2020-01-01 00:00:00'];

        $ref = new ReflectionClass(GetOrders::class);
        $method = $ref->getMethod('notifyMarketplaceShipping');
        $method->setAccessible(true);
        $result = $method->invokeArgs($controller, [$order,$ship]);

        $this->assertTrue($result['success']);
        $this->assertTrue($integration->called, 'notifyShipping should be called');
    }
}
