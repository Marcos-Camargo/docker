<?php
use PHPUnit\Framework\TestCase;
define('BASEPATH', __DIR__);
require_once __DIR__ . '/../../application/libraries/CalculoFrete.php';

class CalculoFreteAggregationTest extends TestCase
{
    private function callPrivateMethod($object, $method, array $args = [])
    {
        $ref = new ReflectionClass($object);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($object, $args);
    }

    public function testAggregateReturnsSuccessWhenAllSellersSucceed()
    {
        $ref = new ReflectionClass(CalculoFrete::class);
        $instance = $ref->newInstanceWithoutConstructor();

        $results = [
            'seller1' => [
                'success' => true,
                'data' => [
                    'shipping_methods' => [
                        ['name' => 'A', 'price' => 10, 'deadline' => 1]
                    ],
                    'seller_execution_time' => 0.1,
                    'seller_execution_mode' => 'test'
                ]
            ],
            'seller2' => [
                'success' => true,
                'data' => [
                    'shipping_methods' => [
                        ['name' => 'B', 'price' => 15, 'deadline' => 2]
                    ],
                    'seller_execution_time' => 0.2,
                    'seller_execution_mode' => 'test'
                ]
            ]
        ];

        $result = $this->callPrivateMethod($instance, 'aggregateMultisellerResults', [$results, 0.5]);
        $this->assertTrue($result['success']);
        $this->assertTrue($result['data']['success']);
        $this->assertArrayNotHasKey('message', $result['data']);
    }

    public function testAggregateFailsWhenAnySellerFails()
    {
        $ref = new ReflectionClass(CalculoFrete::class);
        $instance = $ref->newInstanceWithoutConstructor();

        $results = [
            'seller1' => [
                'success' => true,
                'data' => [
                    'shipping_methods' => [
                        ['name' => 'A', 'price' => 10, 'deadline' => 1]
                    ],
                    'seller_execution_time' => 0.1,
                    'seller_execution_mode' => 'test'
                ]
            ],
            'seller2' => [
                'success' => false,
                'data' => [
                    'message' => 'error'
                ]
            ]
        ];

        $result = $this->callPrivateMethod($instance, 'aggregateMultisellerResults', [$results, 0.5]);
        $this->assertFalse($result['success']);
        $this->assertFalse($result['data']['success']);
        $this->assertSame('Não há cotação de frete disponível para este carrinho', $result['data']['message']);
    }

    public function testChosenShippingByLowestPrice()
    {
        $ref = new ReflectionClass(CalculoFrete::class);
        $instance = $ref->newInstanceWithoutConstructor();
        $instance->setMultisellerParams(['rule' => 'menor_preco']);

        $results = [
            'seller1' => [
                'success' => true,
                'data' => [
                    'shipping_methods' => [
                        ['name' => 'A', 'price' => 10, 'deadline' => 5],
                        ['name' => 'B', 'price' => 8, 'deadline' => 7],
                    ],
                    'seller_execution_time' => 0.1,
                    'seller_execution_mode' => 'test'
                ]
            ],
            'seller2' => [
                'success' => true,
                'data' => [
                    'shipping_methods' => [
                        ['name' => 'A', 'price' => 12, 'deadline' => 4],
                        ['name' => 'B', 'price' => 6, 'deadline' => 6],
                    ],
                    'seller_execution_time' => 0.2,
                    'seller_execution_mode' => 'test'
                ]
            ]
        ];

        $result = $this->callPrivateMethod($instance, 'aggregateMultisellerResults', [$results, 0.5]);
        $this->assertSame('B', $result['data']['chosen_shipping']['name']);
        $this->assertSame(14.0, $result['data']['chosen_shipping']['total_price']);
    }

    public function testChosenShippingByShortestDeadline()
    {
        $ref = new ReflectionClass(CalculoFrete::class);
        $instance = $ref->newInstanceWithoutConstructor();
        $instance->setMultisellerParams(['rule' => 'menor_prazo']);

        $results = [
            'seller1' => [
                'success' => true,
                'data' => [
                    'shipping_methods' => [
                        ['name' => 'A', 'price' => 10, 'deadline' => 5],
                        ['name' => 'B', 'price' => 8, 'deadline' => 7],
                    ],
                    'seller_execution_time' => 0.1,
                    'seller_execution_mode' => 'test'
                ]
            ],
            'seller2' => [
                'success' => true,
                'data' => [
                    'shipping_methods' => [
                        ['name' => 'A', 'price' => 12, 'deadline' => 4],
                        ['name' => 'B', 'price' => 6, 'deadline' => 6],
                    ],
                    'seller_execution_time' => 0.2,
                    'seller_execution_mode' => 'test'
                ]
            ]
        ];

        $result = $this->callPrivateMethod($instance, 'aggregateMultisellerResults', [$results, 0.5]);
        $this->assertSame('A', $result['data']['chosen_shipping']['name']);
        $this->assertSame(5, $result['data']['chosen_shipping']['max_deadline']);
    }
}

