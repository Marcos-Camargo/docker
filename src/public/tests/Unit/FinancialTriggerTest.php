<?php
use PHPUnit\Framework\TestCase;

if (!class_exists('BatchBackground_Controller')) {
    class BatchBackground_Controller {public function __construct(){} protected function log_data($m,$a,$v,$t='I'){} }
}
require_once APPPATH.'controllers/BatchC/Marketplace/Conectala/GetOrders.php';

class FinancialTriggerTest extends TestCase
{
    public function test_trigger_financial_process_runs_when_flag_enabled()
    {
        \App\Libraries\FeatureFlag\FeatureManager::$client = new class {
            public function isEnabled($name,$ctx=null){return $name==='feature-OEP-2012-financial-trigger';}
        };

        $CI = &get_instance();
        $CI->model_orders = new class {
            public function getOrdersByMultisellerNumber($num){return [['total_order'=>10]];}
        };

        $controller = new class extends GetOrders {
            public function __construct() {}
            protected function log_data($m,$a,$v,$t='I'){}
            protected function isFirstDeliveryForMultisellerOrder(string $n): bool {return true;}
            public function callShould(array $o){
                $ref = new ReflectionClass(GetOrders::class);
                $m = $ref->getMethod('shouldTriggerFinancialProcess');
                $m->setAccessible(true);
                return $m->invoke($this,$o);
            }
            public function callTrigger(array $o){
                $ref = new ReflectionClass(GetOrders::class);
                $m = $ref->getMethod('triggerFinancialProcessForFirstDelivery');
                $m->setAccessible(true);
                return $m->invoke($this,$o);
            }
        };

        $order = ['order_mkt_multiseller'=>'GRP1','bill_no'=>'O1'];
        $this->assertTrue($controller->callShould($order));
        $result = $controller->callTrigger($order);
        $this->assertTrue($result['success']);
    }
}
