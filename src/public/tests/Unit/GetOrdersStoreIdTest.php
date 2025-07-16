<?php
use PHPUnit\Framework\TestCase;
require_once APPPATH.'controllers/BatchC/Marketplace/Conectala/GetOrders.php';

class GetOrdersStoreIdTest extends TestCase
{
    private function callPrivate($obj,$method,$args=[]) {
        $ref = new ReflectionClass($obj); $m=$ref->getMethod($method); $m->setAccessible(true); return $m->invokeArgs($obj,$args);
    }

    public function test_validarItensPedido_allows_different_stores()
    {
        $controller = new class extends GetOrders {
            public function __construct(){}
            protected function log_data($m,$a,$v,$t='I'){}
            protected function validarItemPedido(array $i, string $o): array { return ['store_id'=>$i['store_id']]; }
        };
        $items = [['store_id'=>1], ['store_id'=>2]];
        $store = $this->callPrivate($controller,'validarItensPedido',[$items,'O1']);
        $this->assertEquals(1,$store);
    }
}
