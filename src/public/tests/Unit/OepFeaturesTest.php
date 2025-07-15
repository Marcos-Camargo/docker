<?php
use PHPUnit\Framework\TestCase;

class BatchBackground_Controller {public function __construct(){} protected function log_data($m,$a,$v,$t='I'){} }

require_once APPPATH.'controllers/Api/V1/Stores.php';
require_once APPPATH.'controllers/Api/V1/AddOn.php';
require_once APPPATH.'controllers/Api/SellerCenter/Vtex/Simulation.php';
require_once APPPATH.'controllers/BatchC/Marketplace/Conectala/GetOrders.php';
require_once APPPATH.'libraries/PagarmeLibrary.php';
require_once APPPATH.'libraries/Integration_v2/Integration_v2.php';

class OepFeaturesTest extends TestCase
{
    private function setFeature(string $name, bool $enabled)
    {
        \App\Libraries\FeatureFlag\FeatureManager::$client = new class($name, $enabled) {
            private $name; private $enabled;
            public function __construct($n,$e){$this->name=$n;$this->enabled=$e;}
            public function isEnabled($name,$ctx=null){
                return $name === $this->name ? $this->enabled : false;
            }
        };
    }

    private function callPrivate($obj,$method,$args=[]) {
        $ref=new ReflectionClass($obj);$m=$ref->getMethod($method);$m->setAccessible(true);return $m->invokeArgs($obj,$args);
    }

    public function test_oep2002_validateCreate_switches_rules()
    {
        $this->setFeature('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas', true);
        $controller = new class extends Stores { public function __construct(){} };
        $controller->insert = ['CNPJ'=>'123','inscricao_estadual'=>'1'];
        $controller->errors = [];
        $controller->validations = ['CNPJ'=>'check_cnpj','inscricao_estadual'=>'check_ie'];
        $this->callPrivate($controller,'validateCreate',[true]);
        $this->assertEquals(['check_cpf','check_rg'],$controller->validations);
    }

    public function test_oep1957_addon_calls_handleRequest()
    {
        $this->setFeature('OEP-1957-update-delete-publica-addon-occ', true);
        $called=false;
        $controller = new class($called) extends AddOn {
            private $flag; public function __construct(& $f){$this->flag=&$f;}
            protected function handleRequest($sku,$method){$this->flag=true;}
        };
        $controller->index_post('ABC');
        $this->assertTrue($called);
    }

    public function test_oep1599_simulation_returns_merchant_name()
    {
        $simulation = new class extends Simulation { public function __construct(){parent::__construct();}}
        ;
        $this->assertTrue($this->callPrivate($simulation,'return_seller_id_on_merchant_name'));
}

    public function test_oep1789_mevo_mapped_to_vtex()
    {
        $integration = new class extends \App\Libraries\Integration_v2\Integration_v2 { public function __construct(){} };
        $integration->setIntegration('mevo');
        $this->assertSame('vtex', $this->callPrivate($integration, 'integration'));
    }

    public function test_oep2010_notifyMarketplaceShipping_respects_flag()
    {
        $this->setFeature('oep-2009-partial-invoicing', false);
        $controller = new class extends GetOrders { public function __construct(){} protected function log_data($m,$a,$v,$t='I'){} };
        $result = $this->callPrivate($controller,'notifyMarketplaceShipping',[[],[]]);
        $this->assertEquals('Feature flag desabilitada', $result['message']);
    }

    public function test_oep2012_shouldTriggerFinancialProcess()
    {
        $this->setFeature('feature-OEP-2012-financial-trigger', true);
        $controller = new class extends GetOrders { public function __construct(){} protected function log_data($m,$a,$v,$t='I'){} };
        $controller->model_orders = new class {
            public function getOrdersByMultisellerNumber($num){return [['paid_status'=>5],['paid_status'=>6]];}
        };
        $order = ['order_mkt_multiseller'=>'MS1'];
        $this->assertTrue($this->callPrivate($controller,'shouldTriggerFinancialProcess',[$order]));
    }
    public function test_oep1598_recipient_bacen_requires_external_id()
    {
        $lib = new class extends PagarmeLibrary { public function __construct(){ $this->_CI = (object)["model_stores"=>new class{public function setDateUpdateNow($id){}},"logName"=>"" ]; } };
        $store=["id"=>1,"agency"=>"1","account"=>"1","raz_social"=>"X","CNPJ"=>"1","bank_number"=>"1","phone_1"=>"(11)1111-1111"];
        $result=$lib->createUpdateRecipientBacen_v5($store);
        $this->assertSame([], $result);
    }
}
