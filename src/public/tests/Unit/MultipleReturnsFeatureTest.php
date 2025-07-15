<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH.'models/Model_product_return.php';

class MultipleReturnsFeatureTest extends TestCase
{
    public function test_allowMultipleReturnsPerOrder_respects_setting()
    {
        $model = new Model_product_return();
        $model->model_settings = new class {
            public function getStatusbyName($name) { return 1; }
        };
        $this->assertTrue($model->allowMultipleReturnsPerOrder());

        $model->model_settings = new class {
            public function getStatusbyName($name) { return 0; }
        };
        $this->assertFalse($model->allowMultipleReturnsPerOrder());
    }
}
