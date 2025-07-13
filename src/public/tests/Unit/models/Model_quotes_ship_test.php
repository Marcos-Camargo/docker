<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Fakes/FakeDbHandler.php';
require_once __DIR__ . '/../../Fakes/FakeQuotesDbHandler.php';

class Model_quotes_ship_test extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $CI->load->model('Model_quotes_ship');
        $this->model = $CI->Model_quotes_ship;
    }

    public function test_getQuoteShipByKey_returns_row_when_found()
    {
        $row = [
            'id' => 1,
            'marketplace' => 'AMZ',
            'zip' => '12345678',
            'sku' => '["sku1","sku2"]',
            'cost' => '10.00'
        ];
        $fakeDb = new FakeQuotesDbHandler($row);
        $this->model->db = $fakeDb;

        $result = $this->model->getQuoteShipByKey('AMZ', '12345-678', ['sku2', 'sku1'], '10.00');

        $this->assertEquals($row, $result);
        $this->assertSame(['AMZ', '12345678', '["sku1","sku2"]', '10.00'], $fakeDb->queries[0]['bindings']);
    }

    public function test_getQuoteShipByKey_returns_false_when_not_found()
    {
        $fakeDb = new FakeQuotesDbHandler([]); // no row returned
        $this->model->db = $fakeDb;

        $result = $this->model->getQuoteShipByKey('AMZ', '12345-678', ['sku1'], '5');

        $this->assertFalse($result);
    }

    public function test_getQuoteShipByKey_returns_false_with_empty_params()
    {
        $fakeDb = new FakeQuotesDbHandler([]);
        $this->model->db = $fakeDb;

        $result = $this->model->getQuoteShipByKey('', '', '', '');

        $this->assertFalse($result);
    }
}
