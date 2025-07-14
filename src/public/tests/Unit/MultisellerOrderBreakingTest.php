<?php
use PHPUnit\Framework\TestCase;

class MultisellerOrderBreakingTest extends TestCase
{
    private function mockFeatureFlag()
    {
        \App\Libraries\FeatureFlag\FeatureManager::$client = new class {
            public function isEnabled($name, $ctx = null)
            {
                return $name === 'oep-1921-muiltiseller-freight-results';
            }
        };
    }

    private function getSampleOrder(): array
    {
        return [
            'marketplace_number' => 'ORDER123',
            'code' => 'ORDER123',
            'created_at' => '2023-01-01',
            'shipping' => [
                'seller_shipping_cost' => 0,
                'shipping_carrier' => 'Carrier',
                'service_method' => 'Normal',
                'estimated_delivery_days' => 1,
                'shipping_address' => [
                    'postcode' => '00000000',
                    'street' => '',
                    'number' => '',
                    'complement' => '',
                    'reference' => '',
                    'neighborhood' => '',
                    'city' => '',
                    'region' => ''
                ]
            ],
            'billing_address' => [
                'street' => '',
                'number' => '',
                'complement' => '',
                'neighborhood' => '',
                'city' => '',
                'region' => '',
                'country' => 'BR',
                'postcode' => '00000000'
            ],
            'customer' => [
                'name' => 'Client',
                'phones' => ['1','2'],
                'email' => 'client@example.com',
                'cpf' => '111',
                'cnpj' => '',
                'ie' => '',
                'rg' => ''
            ],
            'payments' => [
                'parcels' => [],
                'discount' => 0,
                'total_products' => 30
            ],
            'items' => [
                ['sku' => 'P1S1001NM', 'price' => 10, 'quantity' => 1],
                ['sku' => 'P2S2002NM', 'price' => 20, 'quantity' => 1]
            ]
        ];
    }

    public function test_process_breaks_order_and_calls_newOrder_per_seller()
    {
        $this->mockFeatureFlag();
        $order = $this->getSampleOrder();

        // Anonymous subclass exposing the private method
        $controller = new class extends GetOrders {
            public $calls = [];
            public function __construct() {}
            protected function newOrder(array $content): void
            {
                $this->calls[] = $content;
            }
            protected function createBrokenOrdersWithUniqueIds(array $content): array
            {
                $groups = [];
                foreach ($content['items'] as $item) {
                    if (preg_match('/S(\d+)/', $item['sku'], $m)) {
                        $groups[$m[1]][] = $item;
                    }
                }
                ksort($groups);
                $suffix = 1;
                $orders = [];
                foreach ($groups as $items) {
                    $o = $content;
                    $o['marketplace_number'] = $content['marketplace_number'] . '-' . str_pad($suffix, 2, '0', STR_PAD_LEFT);
                    $o['items'] = $items;
                    $orders[] = $o;
                    $suffix++;
                }
                return $orders;
            }
            protected function log_data($m,$a,$v,$t='I'){}
            public function trigger(array $content)
            {
                $ref = new ReflectionClass(GetOrders::class);
                $method = $ref->getMethod('processNewOrderWithMultisellerSupport');
                $method->setAccessible(true);
                $method->invoke($this, $content);
            }
        };

        $prop = new ReflectionProperty(GetOrders::class, 'enable_multiseller_operation');
        $prop->setAccessible(true);
        $prop->setValue($controller, true);

        $controller->trigger($order);

        $this->assertCount(2, $controller->calls);
        $this->assertStringEndsWith('-01', $controller->calls[0]['marketplace_number']);
        $this->assertStringEndsWith('-02', $controller->calls[1]['marketplace_number']);
    }
}
