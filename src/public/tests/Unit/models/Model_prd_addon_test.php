<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Fakes/FakeDbHandler.php';

class Model_prd_addon_test extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $CI->load->model('Model_prd_addon');

        $this->model = $CI->Model_prd_addon;
        $this->model->db = new FakeDbHandler();
    }

    public function test_getAddonData_with_valid_id_returns_array()
    {
        $result = $this->model->getAddonData(123);
        $this->assertEquals([['mocked' => 'result']], $result);
    }

    public function test_getAddonData_with_null_returns_false()
    {
        $result = $this->model->getAddonData(null);
        $this->assertFalse($result);
    }

    public function test_getAddonDataByPrdIdAddOnAndPrdId_returns_array()
    {
        $result = $this->model->getAddonDataByPrdIdAddOnAndPrdId(1, 2);
        $this->assertEquals(['mocked' => 'data'], $result);
    }

    public function test_removeByPrdIdAddOnAndPrdId_returns_true()
    {
        $result = $this->model->removeByPrdIdAddOnAndPrdId(1, 2);
        $this->assertTrue($result);
    }

    public function test_create_with_data_returns_true()
    {
        $result = $this->model->create(['prd_id' => 1]);
        $this->assertTrue($result);
    }

    public function test_create_with_empty_data_returns_false()
    {
        $result = $this->model->create([]);
        $this->assertFalse($result);
    }

    public function test_remove_returns_true()
    {
        $result = $this->model->remove(5);
        $this->assertTrue($result);
    }

    public function test_removeByPrdId_returns_true()
    {
        $result = $this->model->removeByPrdId(5);
        $this->assertTrue($result);
    }
}
