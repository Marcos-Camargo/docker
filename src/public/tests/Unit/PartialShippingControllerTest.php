<?php
use PHPUnit\Framework\TestCase;

define('BASEPATH', __DIR__);
require_once APPPATH.'controllers/Api/V1/PartialShipping.php';

class PartialShippingControllerTest extends TestCase
{
    public function test_index_post_returns_placeholder_message()
    {
        $controller = new PartialShipping();
        $result = $controller->index_post();
        $this->assertFalse($result['success']);
        $this->assertEquals('Partial shipping update not implemented', $result['message']);
    }
}
