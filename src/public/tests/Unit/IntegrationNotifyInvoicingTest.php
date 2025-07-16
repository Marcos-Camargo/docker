<?php
use PHPUnit\Framework\TestCase;
if (!class_exists('BatchBackground_Controller')) {
    class BatchBackground_Controller {public function __construct(){} protected function log_data($m,$a,$v,$t="I"){} }
}
use Tests\Fakes\FunctionMockTrait;

require_once APPPATH.'controllers/BatchC/Integration/Integration.php';

class IntegrationNotifyInvoicingTest extends TestCase
{
    use FunctionMockTrait;

    public function test_notifyInvoicing_sends_request()
    {
        $this->getFunctionMock('', 'curl_init')
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn('ch');
        $this->getFunctionMock('', 'curl_setopt')
            ->expects($this->any())
            ->method('__invoke');
        $this->getFunctionMock('', 'curl_exec')
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn('ok');
        $this->getFunctionMock('', 'curl_getinfo')
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(200);
        $this->getFunctionMock('', 'curl_error')
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn('');
        $this->getFunctionMock('', 'curl_close')
            ->expects($this->once())
            ->method('__invoke');

        $integration = new class extends Integration {
            public function __construct() {}
            protected function log_data($m,$a,$v,$t='I'){}
        };

        $api = ['api_url'=>'http://localhost','access_token'=>'tk'];
        $data = ['order_code'=>'123','invoice_number'=>'INV1','total_value'=>9];
        $result = $integration->notifyInvoicing($api,$data);

        $this->assertTrue($result['success']);
        $this->assertEquals(200,$result['http_code']);
    }
}
