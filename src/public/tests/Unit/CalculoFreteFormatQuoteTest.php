<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../application/libraries/CalculoFrete.php';

class CalculoFreteFormatQuoteTest extends TestCase
{

    private function setFeatureFlag(bool $enabled): void
    {
        \App\Libraries\FeatureFlag\FeatureManager::$client = new class($enabled) {
            private $enabled;
            public function __construct($e){$this->enabled=$e;}
            public function isEnabled($name, $ctx=null){return $this->enabled;}
        };
    }

    public function test_formatQuote_runs_multiseller_when_enabled()
    {
        $this->setFeatureFlag(true);

        $ci = &get_instance();
        $ci->redis = (object)[
            'is_connected' => false,
            'configure' => function(array $c) {},
            'get' => function($k) { return null; },
            'setex' => function($k,$t,$v) {}
        ];

        $db = new class {
            public function get_where($table, $where){
                $name = $where['name'] ?? '';
                if ($name === 'enable_multiseller_operation') {
                    return new class { public function row_array(){ return ['status'=>1,'value'=>'1']; } };
                }
                if ($name === 'marketplace_multiseller_operation') {
                    return new class { public function row_array(){ return ['status'=>1,'value'=>'AMZ']; } };
                }
                return new class { public function row_array(){ return null; } };
            }
            public function select($f){ return $this; }
            public function where($f,$v=null){ return $this; }
            public function get($t){ return new class { public function row_array(){ return ['integration'=>'vtex']; } }; }
        };

        $cf = new class($ci,$db) extends CalculoFrete {
            public function __construct($ci,$db){
                $this->instance = $ci;
                $this->readonlydb = $db;
                $this->ms_shipping = (object)['use_ms_shipping'=>false];
            }
            public function setSellerCenter() { $this->sellercenter = 'test'; }
            public function getColumnsMarketplace(string $p): array { return ['table'=>'dummy','qty'=>'qty']; }
            public function validItemsQuote(array $items, array $mkt, string $table, string $colQty, bool $checkStock = true, string $zipcode = null, array $dataRecipient = []): array {
                $arrDataAd = [
                    'P1S1' => ['store_id'=>1,'zipcode'=>'00000000','crossdocking'=>0,'prd_id'=>1,'freight_seller'=>0],
                    'P2S2' => ['store_id'=>2,'zipcode'=>'00000000','crossdocking'=>0,'prd_id'=>2,'freight_seller'=>0]
                ];
                $dataSkus = ['P1S1'=>[],'P2S2'=>[]];
                $dataQuote = ['zipcodeRecipient'=>$zipcode,'items'=>[
                    ['sku'=>'P1S1','valor'=>10],
                    ['sku'=>'P2S2','valor'=>20]
                ]];
                return [
                    'arrDataAd'=>$arrDataAd,
                    'dataSkus'=>$dataSkus,
                    'totalPrice'=>30,
                    'cross_docking'=>0,
                    'quoteResponse'=>['success'=>true],
                    'zipCodeSeller'=>null,
                    'dataQuote'=>$dataQuote,
                    'storeId'=>1,
                    'storeIds'=>[1,2],
                    'logistic'=>[],
                    'store_integration'=>'vtex',
                    'multiseller_info'=>[
                        'is_multiseller'=>true,
                        'store_ids'=>[1,2],
                        'total_stores'=>2,
                        'items_by_store'=>[1=>['P1S1'],2=>['P2S2']]
                    ]
                ];
            }
            public function getLogisticIntegration(int $store_id, bool $returnException = false): array { return ['seller'=>false,'sellercenter'=>true,'type'=>'test']; }
            public function instanceLogistic(string $logistic, int $store, array $dataQuote, bool $freightSeller) {
                $this->logistic = new class {
                    public $asyncCalled=false;
                    public $has_multiseller=true;
                    public function getQuoteAsync($d,$f=false){ $this->asyncCalled=true; return ['success'=>true,'data'=>['shipping_methods'=>[['name'=>'X','price'=>1,'deadline'=>1]]]]; }
                    public function getQuote($d,$f=false,$m=false){ return ['success'=>true,'data'=>['shipping_methods'=>[['name'=>'Y','price'=>1,'deadline'=>1]]]]; }
                    public function applyShippingPricingRules($d,$r){ return []; }
                };
            }
        };

        $items = [ ['sku'=>'P1S1','qty'=>1], ['sku'=>'P2S2','qty'=>1] ];
        $result = $cf->formatQuote(['platform'=>'AMZ','channel'=>'AMZ'],$items,null,true,true);

        $this->assertTrue($result['success']);
        $this->assertTrue($cf->logistic->asyncCalled, 'Parallel quote should be executed');
    }

    public function test_formatQuote_ignores_invalid_cache_json()
    {
        $this->setFeatureFlag(true);

        $ci = &get_instance();
        $ci->redis = (object)[
            'is_connected' => true,
            'configure' => function(array $c) {},
            'get' => function($k) { return '{invalid'; },
            'setex' => function($k,$t,$v) {}
        ];

        $db = new class {
            public function get_where($table, $where){
                $name = $where['name'] ?? '';
                if ($name === 'enable_multiseller_operation') {
                    return new class { public function row_array(){ return ['status'=>1,'value'=>'1']; } };
                }
                if ($name === 'marketplace_multiseller_operation') {
                    return new class { public function row_array(){ return ['status'=>1,'value'=>'AMZ']; } };
                }
                return new class { public function row_array(){ return null; } };
            }
            public function select($f){ return $this; }
            public function where($f,$v=null){ return $this; }
            public function get($t){ return new class { public function row_array(){ return ['integration'=>'vtex']; } }; }
        };

        $cf = new class($ci,$db) extends CalculoFrete {
            public function __construct($ci,$db){
                $this->instance = $ci;
                $this->readonlydb = $db;
                $this->ms_shipping = (object)['use_ms_shipping'=>false];
            }
            public function setSellerCenter() { $this->sellercenter = 'test'; }
            public function getColumnsMarketplace(string $p): array { return ['table'=>'dummy','qty'=>'qty']; }
            public function validItemsQuote(array $items, array $mkt, string $table, string $colQty, bool $checkStock = true, string $zipcode = null, array $dataRecipient = []): array {
                $arrDataAd = [
                    'P1S1' => ['store_id'=>1,'zipcode'=>'00000000','crossdocking'=>0,'prd_id'=>1,'freight_seller'=>0],
                ];
                $dataSkus = ['P1S1'=>[]];
                $dataQuote = ['zipcodeRecipient'=>$zipcode,'items'=>[
                    ['sku'=>'P1S1','valor'=>10]
                ]];
                return [
                    'arrDataAd'=>$arrDataAd,
                    'dataSkus'=>$dataSkus,
                    'totalPrice'=>10,
                    'cross_docking'=>0,
                    'quoteResponse'=>['success'=>true],
                    'zipCodeSeller'=>null,
                    'dataQuote'=>$dataQuote,
                    'storeId'=>1,
                    'storeIds'=>[1],
                    'logistic'=>[],
                    'store_integration'=>'vtex',
                    'multiseller_info'=>[
                        'is_multiseller'=>true,
                        'store_ids'=>[1],
                        'total_stores'=>1,
                        'items_by_store'=>[1=>['P1S1']]
                    ]
                ];
            }
            public function getLogisticIntegration(int $store_id, bool $returnException = false): array { return ['seller'=>false,'sellercenter'=>true,'type'=>'test']; }
            public function instanceLogistic(string $logistic, int $store, array $dataQuote, bool $freightSeller) {
                $this->logistic = new class {
                    public $asyncCalled=false;
                    public $has_multiseller=true;
                    public function getQuoteAsync($d,$f=false){ $this->asyncCalled=true; return ['success'=>true,'data'=>['shipping_methods'=>[['name'=>'X','price'=>1,'deadline'=>1]]]]; }
                    public function getQuote($d,$f=false,$m=false){ return ['success'=>true,'data'=>['shipping_methods'=>[['name'=>'Y','price'=>1,'deadline'=>1]]]]; }
                    public function applyShippingPricingRules($d,$r){ return []; }
                };
            }
        };

        $items = [ ['sku'=>'P1S1','qty'=>1] ];
        $result = $cf->formatQuote(['platform'=>'AMZ','channel'=>'AMZ'],$items,null,true,true);

        $this->assertTrue($result['success']);
        $this->assertTrue($cf->logistic->asyncCalled, 'Parallel quote should be executed');
    }

    public function test_extractSellerFromSku_parses_ids()
    {
        $ref = new ReflectionClass(CalculoFrete::class);
        $obj = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('extractSellerFromSku');
        $method->setAccessible(true);

        $this->assertSame('1001', $method->invoke($obj, 'P123S1001NM'));
        $this->assertSame('987', $method->invoke($obj, 'ABCS987'));
        $this->assertSame('', $method->invoke($obj, 'NOSKU'));
    }
}
