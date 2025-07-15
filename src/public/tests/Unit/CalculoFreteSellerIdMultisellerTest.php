<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../application/libraries/CalculoFrete.php';

class CalculoFreteSellerIdMultisellerTest extends TestCase
{
    private function setFeatureFlag(bool $enabled): void
    {
        \App\Libraries\FeatureFlag\FeatureManager::$client = new class($enabled) {
            private $enabled;
            public function __construct($e){$this->enabled=$e;}
            public function isEnabled($name,$ctx=null){return $this->enabled;}
        };
    }

    private function callPrivateMethod($object, string $method, array $args = [])
    {
        $ref = new ReflectionClass($object);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($object, $args);
    }

    public function test_detect_multiseller_when_same_store_but_different_sellers()
    {
        $this->setFeatureFlag(false);

        $validation = [
            'arrDataAd' => [
                'SKU1' => ['store_id'=>1,'seller_id'=>10,'prd_id'=>1],
                'SKU2' => ['store_id'=>1,'seller_id'=>20,'prd_id'=>2]
            ],
            'dataSkus' => [],
            'totalPrice' => 30,
            'cross_docking' => 0,
            'quoteResponse' => ['success'=>true],
            'zipCodeSeller' => null,
            'dataQuote' => [
                'items' => [
                    ['sku'=>'SKU1','valor'=>10],
                    ['sku'=>'SKU2','valor'=>20]
                ]
            ],
            'storeId' => 1,
            'storeIds' => [1],
            'sellerIds' => [10,20],
            'logistic' => [],
            'store_integration' => 'vtex',
            'multiseller_info' => [
                'is_multiseller' => true,
                'store_ids' => [1],
                'seller_ids' => [10,20],
                'total_stores' => 1,
                'total_sellers' => 2,
                'items_by_store' => [1=>['SKU1','SKU2']],
                'items_by_seller' => [10=>['SKU1'],20=>['SKU2']]
            ]
        ];

        $items = [ ['sku'=>'SKU1','qty'=>1], ['sku'=>'SKU2','qty'=>1] ];

        $ref = new ReflectionClass(CalculoFrete::class);
        $instance = $ref->newInstanceWithoutConstructor();

        $result = $this->callPrivateMethod(
            $instance,
            'analyzeMultisellerRequestOptimized',
            [['platform'=>'AMZ','channel'=>'AMZ'], $items, $validation, null]
        );

        $this->assertTrue($result['is_multiseller']);
        $this->assertSame(2, $result['total_sellers']);
    }
}
