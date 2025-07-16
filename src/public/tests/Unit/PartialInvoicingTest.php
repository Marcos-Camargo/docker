<?php
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FunctionMockTrait;

class BatchBackground_Controller {public function __construct(){} protected function log_data($m,$a,$v,$t='I'){} }
require_once APPPATH.'controllers/BatchC/Marketplace/Conectala/GetOrders.php';

class PartialInvoicingTest extends TestCase
{
    use FunctionMockTrait;

    public function test_processPartialInvoicing_creates_invoice_and_updates_status()
    {
        $CI = &get_instance();
        $orderModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getOrdersDatabyBill','createInvoice','updateOrderStatus','getInvoicedQuantitiesByOrder'])
            ->getMock();
        $itemModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getItemsByOrderId'])
            ->getMock();
        $invoiceItemModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(['createItems','getInvoicedQuantities'])
            ->getMock();
        $CI->model_orders = $orderModel;
        $CI->model_orders_item = $itemModel;
        $CI->model_orders_invoice_items = $invoiceItemModel;

        $order = ['id'=>1,'bill_no'=>'ORDER1','order_mkt_multiseller'=>'ORDER1','total_order'=>100];
        $items = [
            ['id'=>11,'sku'=>'SKU1','price'=>10,'quantity'=>2],
            ['id'=>12,'sku'=>'SKU2','price'=>5,'quantity'=>1]
        ];

        $orderModel->expects($this->once())
            ->method('getOrdersDatabyBill')
            ->willReturn($order);
        $orderModel->expects($this->once())
            ->method('getInvoicedQuantitiesByOrder')
            ->with(1)
            ->willReturn([]);
        $itemModel->expects($this->once())
            ->method('getItemsByOrderId')
            ->with(1)
            ->willReturn($items);
        $orderModel->expects($this->once())
            ->method('createInvoice')
            ->with(
                $this->callback(function($data){return $data['invoice_value']==5;}),
                [['order_item_id'=>12,'qty_invoiced'=>1,'price'=>5]]
            )
            ->willReturn(10);
        $orderModel->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1,62);
        $invoiceItemModel->expects($this->once())
            ->method('getInvoicedQuantities')
            ->with(1)
            ->willReturn([]);

        $controller = new class extends GetOrders {
            public function __construct() {}
            protected function log_data($m,$a,$v,$t='I'){}
            protected function notifyMarketplaceInvoicing(array $order, array $invoiceData): array {return ['success'=>true];}
        };
        $controller->int_to = 'ANY';

        $response = $controller->processPartialInvoicing('ORDER1', [['sku'=>'SKU2','quantity'=>1]]);

        $this->assertTrue($response['success']);
        $this->assertEquals(10, $response['invoice_id']);
    }

    public function test_processFullInvoicing_creates_items_for_all()
    {
        $CI = &get_instance();
        $orderModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getOrdersDatabyBill','createInvoice','updateOrderStatus'])

            ->getMock();
        $itemModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getItemsByOrderId'])
            ->getMock();
        $invoiceItemModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(['createItems','getInvoicedQuantities'])
            ->getMock();
        $CI->model_orders = $orderModel;
        $CI->model_orders_item = $itemModel;
        $CI->model_orders_invoice_items = $invoiceItemModel;

        $order = ['id'=>1,'bill_no'=>'ORDER1','order_mkt_multiseller'=>'ORDER1','total_order'=>100];
        $items = [
            ['id'=>11,'sku'=>'SKU1','price'=>10,'quantity'=>2],
            ['id'=>12,'sku'=>'SKU2','price'=>5,'quantity'=>1]
        ];

        $orderModel->expects($this->once())
            ->method('getOrdersDatabyBill')
            ->willReturn($order);
        $itemModel->expects($this->once())
            ->method('getItemsByOrderId')
            ->with(1)
            ->willReturn($items);
        $orderModel->expects($this->once())
            ->method('createInvoice')
            ->with(
                $this->callback(function($data){return $data['invoice_value']==100;}),
                [
                    ['order_item_id'=>11,'qty_invoiced'=>2,'price'=>10],
                    ['order_item_id'=>12,'qty_invoiced'=>1,'price'=>5]
                ]
            )
            ->willReturn(10);
        $orderModel->expects($this->once())
            ->method('updateOrderStatus')
            ->with(1,62);
        $invoiceItemModel->expects($this->once())
            ->method('getInvoicedQuantities')
            ->with(1)
            ->willReturn([]);


        $controller = new class extends GetOrders {
            public function __construct() {}
            protected function log_data($m,$a,$v,$t='I'){}
            protected function notifyMarketplaceInvoicing(array $order, array $invoiceData): array {return ['success'=>true];}
        };
        $controller->int_to = 'ANY';

        $response = $controller->processPartialInvoicing('ORDER1', []);

        $this->assertTrue($response['success']);
        $this->assertEquals(10, $response['invoice_id']);

    }
}
