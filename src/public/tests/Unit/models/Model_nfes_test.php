<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../Fakes/FakeDbHandler.php';

class Model_nfes_test extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $CI->load->model('Model_nfes');
        $this->model = $CI->Model_nfes;
        $this->model->db = new FakeDbHandler();
    }

    public function test_getNfesDataByOrderItemIds_returns_array()
    {
        $result = $this->model->getNfesDataByOrderItemIds([1, 2]);
        $this->assertEquals([['mocked' => 'result']], $result);
        $this->assertStringContainsString('orders_invoice_items', $this->model->db->queries[0]['sql']);
    }

    public function test_getNfesDataByOrderItemIds_with_empty_returns_empty()
    {
        $result = $this->model->getNfesDataByOrderItemIds([]);
        $this->assertEquals([], $result);
    }
}
