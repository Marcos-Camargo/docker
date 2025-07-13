<?php

require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property BlacklistOfWords        $BlacklistOfWords
 * @property Model_whitelist         $model_whitelist
 * @property Model_blacklist_words   $model_blacklist_words
 * @property Model_category          $model_category
 * @property Model_products          $model_products
 * @property Model_stores            $model_stores
 * @property Model_integrations      $model_integrations
 * @property Model_brands            $model_brands
 * @property Model_phases            $model_phases
 */
class Whitelist extends Admin_Controller
{
    private $word;
    private $productId;
    private $sku;
    private $store;
    private $category;
    private $marketplace;
    private $operatorCommission;
    private $commission;
    private $operatorSellerIndex;
    private $sellerIndex;
    private $brand;
    private $hasContent = false;
    private $dataToSave = [];

    private const ACTIVE = 1;
    private const OPERATORS = [
        '='  => 'igual a',
        '>'  => 'maior que',
        '>=' => 'maior ou igual a',
        '<'  => 'menor que',
        '<=' => 'menor ou igual a',
        '!=' => 'diferente de'
    ];

    // Evitar perca dos operadores devido a limpeza de input. Ex: <= é removido no inputClean.
    const OPERATOR_CONVERSION = [
        'e' => "=",
        'gt' => ">",
        'gte' => ">=",
        'lt' => "<",
        'lte' => "<=",
        "ne" => "!="
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('BlacklistOfWords');
        $this->load->model('model_whitelist');
        $this->load->model('model_blacklist_words');
        $this->load->model('model_category');
        $this->load->model('model_products');
        $this->load->model('model_stores');
        $this->load->model('model_integrations');
        $this->load->model('model_brands');
        $this->load->model('model_phases');
    }

    public function index()
    {
        if(!in_array('viewCuration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_manage_whitelist');
     	/*
	    $this->data['categories'] = $this->model_category->getActiveCategroy();
        $this->data['stores']     = $this->model_stores->getActiveStore();
        $this->data['brands']     = $this->model_brands->getBrandData();

        $this->data['nameOfIntegrations'] = [
            'B2W'  => 'B2W',
            'CAR'  => 'Carrefour',
            'ML'   => 'Mercado Livre Premium',
            'MLC'  => 'Mercado Livre Clássico',
            'VIA'  => 'Via Varejo',
            'Farm' => 'Farm'
        ];

        $activeIntegrations = array_column($this->model_integrations->getIntegrations(), 'int_to');

        foreach ($this->data['nameOfIntegrations'] as $keyNameOfIntegration => $nameOfIntegration) {
            if (!in_array($keyNameOfIntegration, $activeIntegrations)) {
                unset($this->data['nameOfIntegrations'][$keyNameOfIntegration]);
            }
        }
		*/
        $this->render_template('whitelist/index', $this->data);
    }

	/*
    public function create()
    {
        if(!in_array('createCuration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_add_word');

        if ($this->postClean(NULL,TRUE)) {

            $this->validateFields();

            if (!$this->hasContent) {
                $this->session->set_flashdata('error', $this->lang->line('messages_add_item_error'));

                redirect("Whitelist/", 'refresh');
            }

            $this->mountDataToSave();

            foreach ($this->dataToSave as $data) {
                $this->model_whitelist->create($data);
            }

            $this->session->set_flashdata('success', $this->lang->line('application_word_registered_successfully'));

            redirect("Whitelist/", 'refresh');
        }

        redirect("Whitelist/", 'refresh');
    }
	*/
	public function createnew()
    {
        if(!in_array('createCuration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['page_title'] = $this->lang->line('application_add_word');
        $this->data['stores']     = $this->model_stores->getActiveStore();
        $this->data['categories'] = $this->model_category->getActiveCategroy();
        $this->data['brands']     = $this->model_brands->getActiveBrands();
        $this->data['phases']     = $this->model_phases->getActivePhases();

        foreach ($this->model_integrations->getMyNamesIntegrations() as $integration) {
            $this->data['nameOfIntegrations'][$integration['int_to']] = $integration['name'];
        }

        if ($this->postClean(NULL,TRUE)) {
			$word        = trim($this->postClean('word')) ? 'da palavra "'.$this->postClean('word').'" ' : '';
	        $productId   = trim($this->postClean('product_id')) ? 'do produto de ID '.$this->postClean('product_id').' ' : '';
	        $productSku  = trim($this->postClean('sku')) ? 'que tenha o SKU "'.$this->postClean('sku').'" ' : '';
	        $marketplace = trim($this->postClean('marketplace')) ? 'para o marketplace "'.$this->postClean('marketplace').'" ' : '';
	        $commission  = trim($this->postClean('commission')) ? 'com comissão '.self::OPERATORS[self::OPERATOR_CONVERSION[$this->postClean('operator_commission')]].' '.$this->postClean('commission').'% ' : '';
	        $sellerIndex = trim($this->postClean('seller_index')) ? 'de Seller Index '.self::OPERATORS[self::OPERATOR_CONVERSION[$this->postClean('operator_seller_index')]].' '.$this->postClean('seller_index').' ' : '';
		
           
            if (trim($this->postClean('store'))) {
                $store = $this->model_stores->getStoreById($this->postClean('store'));
                $storeName = 'da loja "'.$store['name'].'" ';
            } else {
                $storeName = '';
            }

            if (trim($this->postClean('brand'))) {
                $brand = $this->model_brands->getBrandData($this->postClean('brand'));
                $brandName = 'da marca "'.$brand['name'].'" ';
            } else {
                $brandName = '';
            }

            if (trim($this->postClean('category'))) {
                $category = $this->model_category->getCategoryData($this->postClean('category'));
                $categoryName = 'da categoria "'.$category['name'].'" ';
            } else {
                $categoryName = '';
            }
            $phase='';
            $phase_id=$this->postClean('phase',TRUE);
            if($this->postClean('phase',TRUE)){
                $phase=$this->model_phases->getPhaseByNameOrId($phase_id,$phase_id);
                $phase='da fase "'.$phase['name'].'" ';
            }
            $sentence = $word.$productId.$productSku.$storeName.$brandName.$categoryName.$marketplace.$commission.$sellerIndex.$phase;
            if ($sentence != '') {
	            $teste = json_decode($this->postClean('created_by'));
	
	            $createdBy = json_encode([
	                'user_id' => $this->session->userdata('id'),
	                'username' => $this->session->userdata('username'),
	                'email' => $this->session->userdata('email'),
	                'start' => date('Y-m-d H:i:s'),
	                'end' => ($this->postClean('status') == 1 ? '' : date('Y-m-d H:i:s')),
	            ]);
	
	            $data = [
	                'words'                 => trim($this->postClean('word')) ? $this->postClean('word') : null,
	                'product_id'            => trim($this->postClean('product_id')) ? $this->postClean('product_id') : null,
	                'product_sku'           => trim($this->postClean('sku')) ? $this->postClean('sku') : null,
	                'store_id'              => trim($this->postClean('store')) ? $this->postClean('store') : null,
	                'category_id'           => trim($this->postClean('category')) ? $this->postClean('category') : null,
	                'marketplace'           => trim($this->postClean('marketplace')) ? $this->postClean('marketplace') : null,
	                'brand_id'              => trim($this->postClean('brand')) ? $this->postClean('brand') : null,
	                'phase_id'              => trim($phase_id) ? $phase_id : null,
	                'commission'            => trim($this->postClean('commission')) ? $this->postClean('commission') : null,
	                'operator_commission'   => trim($this->postClean('commission')) ? self::OPERATOR_CONVERSION[$this->postClean('operator_commission')] : null,
	                'seller_index'          => trim($this->postClean('seller_index')) ? $this->postClean('seller_index') : null,
	                'operator_seller_index' => trim($this->postClean('seller_index')) ? self::OPERATOR_CONVERSION[$this->postClean('operator_seller_index')] : null,
	                'sentence'              => $sentence,
	                'created_by'            => $createdBy,
	                'status'                => $this->postClean('status'),
	                'new_or_update'         => Model_blacklist_words::NEW_OR_UPDATE_RULE, 
	                'apply_to'				=> trim($this->postClean('apply_to')) ? $this->postClean('apply_to') : 2,
	            ];
				
	            if ($this->model_whitelist->create($data)) {
	            	$this->session->set_flashdata('success', $this->lang->line('application_word_registered_successfully'));
					redirect("whitelist/index", 'refresh');
	            }
				else {
					$this->session->set_flashdata('error', $this->lang->line('messages_add_item_error'));
				};
	            
	        }
			else {
				$this->session->set_flashdata('error', $this->lang->line('messages_choose_at_least_one_option'));
			}
        }

		$this->data['wordData'] = [
				'id'					=> null, 
                'words'                 => '',
                'product_id'            => '',
                'product_sku'           => '',
                'store_id'              => '',
                'phase_id'              => '',
                'category_id'           => '',
                'marketplace'           => '',
                'brand_id'              => '',
                'commission'            => '',
                'operator_commission'   => '',
                'seller_index'          => '',
                'operator_seller_index' => '',
                'sentence'              => '',
                'created_by'            => '',
                'status'                => '',
                'new_or_update'         => Model_blacklist_words::NEW_OR_UPDATE_RULE, 
                'apply_to'				=> 2,
            ];
			
        $this->render_template('whitelist/edit', $this->data);
    }

    public function update($id)
    {
        if(!in_array('updateCuration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['page_title'] = $this->lang->line('application_whitelist');
        $this->data['stores']     = $this->model_stores->getActiveStore();
        $this->data['categories'] = $this->model_category->getActiveCategroy();
        $this->data['brands']     = $this->model_brands->getActiveBrands();
        $this->data['phases']     = $this->model_phases->getActivePhases();
        $wordData = $this->model_whitelist->getWordById($id);

        foreach ($this->model_integrations->getMyNamesIntegrations() as $integration) {
            $this->data['nameOfIntegrations'][$integration['int_to']] = $integration['name'];
		}

        if ($this->postClean(NULL,TRUE)) {

            $word        = trim($this->postClean('word')) ? 'da palavra "'.$this->postClean('word').'" ' : '';
            $productId   = trim($this->postClean('product_id')) ? 'do produto de ID '.$this->postClean('product_id').' ' : '';
            $productSku  = trim($this->postClean('sku')) ? 'que tenha o SKU "'.$this->postClean('sku').'" ' : '';
            $marketplace = trim($this->postClean('marketplace')) ? 'para o marketplace "'.$this->postClean('marketplace').'" ' : '';
            $commission  = trim($this->postClean('commission')) ? 'com comissão '.self::OPERATORS[self::OPERATOR_CONVERSION[$this->postClean('operator_commission')]].' '.$this->postClean('commission').'% ' : '';
            $sellerIndex = trim($this->postClean('seller_index')) ? 'de Seller Index '.self::OPERATORS[self::OPERATOR_CONVERSION[$this->postClean('operator_seller_index')]].' '.$this->postClean('seller_index').' ' : '';

            if (trim($this->postClean('store'))) {
                $store = $this->model_stores->getStoreById($this->postClean('store'));
                $storeName = 'da loja "'.$store['name'].'" ';
            } else {
                $storeName = '';
            }

            if (trim($this->postClean('brand'))) {
                $brand = $this->model_brands->getBrandData($this->postClean('brand'));
                $brandName = 'da marca "'.$brand['name'].'" ';
            } else {
                $brandName = '';
            }

            if (trim($this->postClean('category'))) {
                $category = $this->model_category->getCategoryData($this->postClean('category'));
                $categoryName = 'da categoria "'.$category['name'].'" ';
            } else {
                $categoryName = '';
            }
            $phase='';
            if($this->postClean('phase',TRUE)){
                $phase_id=$this->postClean('phase',TRUE);
                $phase=$this->model_phases->getPhaseByNameOrId($phase_id,$phase_id);
                $phase='da fase "'.$phase['name'].'" ';
            }
            $sentence = $word.$productId.$productSku.$storeName.$brandName.$categoryName.$marketplace.$commission.$sellerIndex.$phase;
			if ($sentence != '') {
	            $teste = json_decode($this->postClean('created_by'));
	
	            $createdBy = json_encode([
	                'user_id' => $this->session->userdata('id'),
	                'username' => $this->session->userdata('username'),
	                'email' => $this->session->userdata('email'),
	                'start' => $this->postClean('status') == $wordData['status'] ? $teste->start : ($this->postClean('status') == 1 ? date('Y-m-d H:i:s') : $teste->start),
	                'end' => $this->postClean('status') == $wordData['status'] ? $teste->end : ($this->postClean('status') == 1 ? '' : date('Y-m-d H:i:s')),
	            ]);
	            
	            $data = [
	                'words'                 => trim($this->postClean('word')) ? $this->postClean('word') : null,
	                'product_id'            => trim($this->postClean('product_id')) ? $this->postClean('product_id') : null,
	                'product_sku'           => trim($this->postClean('sku')) ? $this->postClean('sku') : null,
	                'store_id'              => trim($this->postClean('store')) ? $this->postClean('store') : null,
	                'phase_id'              => trim($this->postClean('phase')) ? $this->postClean('phase') : null,
	                'category_id'           => trim($this->postClean('category')) ? $this->postClean('category') : null,
	                'marketplace'           => trim($this->postClean('marketplace')) ? $this->postClean('marketplace') : null,
	                'brand_id'              => trim($this->postClean('brand')) ? $this->postClean('brand') : null,
	                'commission'            => trim($this->postClean('commission')) ? $this->postClean('commission') : null,
	                'operator_commission'   => trim($this->postClean('commission')) ? self::OPERATOR_CONVERSION[$this->postClean('operator_commission')] : null,
	                'seller_index'          => trim($this->postClean('seller_index')) ? $this->postClean('seller_index') : null,
	                'operator_seller_index' => trim($this->postClean('seller_index')) ? self::OPERATOR_CONVERSION[$this->postClean('operator_seller_index')] : null,
	                'sentence'              => $sentence,
	                'created_by'            => $createdBy,
	                'status'                => $this->postClean('status'),
	                'new_or_update'         => Model_whitelist::NEW_OR_UPDATE_RULE, 
	                'apply_to'				=> trim($this->postClean('apply_to')) ? $this->postClean('apply_to') : 2,
	            ];
	
	            if ($this->model_whitelist->update($id, $data)) {
	            	$this->session->set_flashdata('success', $this->lang->line('application_word_successfully_changed'));
	
	            	redirect("Whitelist/index", 'refresh');
	            }
				else {
					$this->session->set_flashdata('success', $this->lang->line('messages_save_item_error'));
				}
	            
	        }
			else {
				$this->session->set_flashdata('error', $this->lang->line('messages_choose_at_least_one_option'));
			}
        }

        $this->data['wordData'] = $wordData;

        $this->render_template('whitelist/edit', $this->data);
    }
	
	
	
    public function fetchWhitelistData($postdata = null)
    {
        $postdata = $this->postClean(NULL,TRUE);
		$ini      = $postdata['start'];
		$draw     = $postdata['draw'];
        $length   = $postdata['length'];
        $busca    = $postdata['search'];

        if ($busca['value']) {
            $busca['value'] = str_replace('\'', '',$busca['value']);
            if (strlen($busca['value'])>=2) {
                $this->data['wordsfilter'] = " AND (
                    words LIKE '%".$busca['value']."%' OR 
                    created_by LIKE '%".$busca['value']."%' OR 
                    sentence LIKE '%".$busca['value']."%' OR 
                    id LIKE '%".$busca['value']."%'
                ) ";
            }
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direct = "asc";
            } else {
                $direct = "desc";
            }
            $campos = array('id','sentence','created_by','created_by','created_by','status');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direct;
            }
            $this->data['orderby'] = $sOrder;
        }

        $result = array();

        $data = $this->model_whitelist->getWordsDataView($ini, $length);

        foreach ($data as $key => $value) {

            $id = '<a href="'.base_url('Whitelist/update/'.$value['id']).'">'.$value['id'].'</a>';
            
            if ($value['status'] == 1) {
                $status = '<span class="label label-success">Ativo</span>';
            } else {
                $status = '<span class="label label-danger">Inativo</span>';
            }

            $teste = json_decode($value['created_by']);

            $result[$key] = [
                $id,
                ucfirst($value['sentence']),
                $teste->email,
                $teste->start,
                $teste->end,
                $status
            ];
        }

        $filtered = $this->model_whitelist->getWordsDataViewCount($this->data['wordsfilter'] ?? '');

        $output = array(
			"draw"            => $draw,
		    "recordsTotal"    => $this->model_whitelist->getWordsDataViewCount(),
		    "recordsFiltered" => $filtered,
		    "data"            => $result
		);
        
        echo json_encode($output);
    }

    public function unlockProduct($id)
    {
        if(!in_array('createCuration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($this->postClean(NULL,TRUE)) {
            $product_data = $this->model_products->verifyProductsOfStore($id);
            if ($product_data['status'] == Model_products::DELETED_PRODUCT) {
                $this->session->set_flashdata('error', $this->lang->line('messages_unlock_product_removed'));
                redirect("products/update/" . $id, 'refresh');
                return;
            }

            $sentence = 'do produto de ID '.$id;

            $createdBy = json_encode([
                'user_id' => $this->session->userdata('id'),
                'username' => $this->session->userdata('username'),
                'email' => $this->session->userdata('email'),
                'start' => date('Y-m-d H:i:s'),
                'end' => ''
            ]);
            
            $data = [
                'product_id'            => $id,
                'sentence'              => $sentence,
                'created_by'            => $createdBy,
                'status'                => self::ACTIVE
            ];

            $this->model_whitelist->create($data);
            $this->model_blacklist_words->deleteProductWithLock($id);
            $this->model_products->update(['status' => 1], $id);

            $this->session->set_flashdata('success', $this->lang->line('application_word_successfully_changed'));

            redirect("products/update/".$id, 'refresh');
        }
    }

	/*
    private function validateFields()
    {
        if ($this->postClean('word')) {
            $this->word = $this->postClean('word');
            $this->hasContent = true;
        }

        if ($this->postClean('product_id')) {
            $this->productId = $this->postClean('product_id');
            $this->hasContent = true;
        }

        if ($this->postClean('sku')) {
            $this->sku = $this->postClean('sku');
            $this->hasContent = true;
        }

        if ($this->postClean('store')) {
            $this->store = $this->postClean('store');
            $this->hasContent = true;
        }

        if ($this->postClean('category')) {
            $this->category = $this->postClean('category');
            $this->hasContent = true;
        }

        if ($this->postClean('marketplace')) {
            $this->marketplace = $this->postClean('marketplace');
            $this->hasContent = true;
        }

        if ($this->postClean('brand')) {
            $this->brand = $this->postClean('brand');
            $this->hasContent = true;
        }

        if ($this->postClean('commission')) {
            $this->commission         = $this->postClean('commission');
            $this->operatorCommission = $this->postClean('operator_commission');
            $this->hasContent = true;
        }

        if ($this->postClean('seller_index')) {
            $this->sellerIndex         = $this->postClean('seller_index');
            $this->operatorSellerIndex = $this->postClean('operator_seller_index');
            $this->hasContent = true;
        }
    }
	
    private function mountDataToSave()
    {
        $storeQtd       = isset($this->store) ? count($this->store) : 1;
        $categoryQtd    = isset($this->category) ? count($this->category) : 1;
        $marketplaceQtd = isset($this->marketplace) ? count($this->marketplace) : 1;
        $brandQtd       = isset($this->brand) ? count($this->brand) : 1;

        $createdBy = json_encode([
            'user_id' => $this->session->userdata('id'),
            'username' => $this->session->userdata('username'),
            'email' => $this->session->userdata('email'),
            'start' => date('Y-m-d H:i:s'),
            'end' => ''
        ]);

        for ($sQ = 0; $sQ < $storeQtd; $sQ++) {
            for ($cQ = 0; $cQ < $categoryQtd; $cQ++) {
                for ($mQ = 0; $mQ < $marketplaceQtd; $mQ++) {
                    for ($bQ = 0; $bQ < $brandQtd; $bQ++) {

                        $word        = $this->word ? 'da palavra "'.$this->word.'" ' : '';
                        $productId   = $this->productId ? 'do produto de ID '.$this->productId.' ' : '';
                        $productSku  = $this->sku ? 'que tenha o SKU "'.$this->sku.'" ' : '';
                        $marketplace = $this->marketplace[$mQ] ? 'para o marketplace "'.$this->marketplace[$mQ].'" ' : '';
                        $commission  = $this->commission ? 'com comissão '.self::OPERATORS[$this->operatorCommission].' '.$this->commission.'% ' : '';
                        $sellerIndex = $this->sellerIndex ? 'de Seller Index '.self::OPERATORS[$this->operatorSellerIndex].''.$this->sellerIndex.' ' : '';

                        if ($this->store[$sQ]) {
                            $store = $this->model_stores->getStoreById($this->store[$sQ]);
                            $storeName = 'da loja "'.$store['name'].'" ';
                        } else {
                            $storeName = '';
                        }

                        if ($this->brand[$bQ]) {
                            $brand = $this->model_brands->getBrandData($this->brand[$bQ]);
                            $brandName = 'da marca "'.$brand['name'].'" ';
                        } else {
                            $brandName = '';
                        }

                        if ($this->category[$cQ]) {
                            $category = $this->model_category->getCategoryData($this->category[$cQ]);
                            $categoryName = 'da categoria "'.$category['name'].'" ';
                        } else {
                            $categoryName = '';
                        }

                        $sentence = $word.$productId.$productSku.$storeName.$brandName.$categoryName.$marketplace.$commission.$sellerIndex;

                        array_push($this->dataToSave, [
                            'words'                 => $this->word,
                            'product_id'            => $this->productId,
                            'product_sku'           => $this->sku,
                            'store_id'              => $this->store[$sQ],
                            'category_id'           => $this->category[$cQ],
                            'marketplace'           => $this->marketplace[$mQ],
                            'brand_id'              => $this->brand[$bQ],
                            'commission'            => $this->commission,
                            'operator_commission'   => $this->operatorCommission,
                            'seller_index'          => $this->sellerIndex,
                            'operator_seller_index' => $this->operatorSellerIndex,
                            'sentence'              => $sentence,
                            'created_by'            => $createdBy,
                            'status'                => self::ACTIVE,
                            'new_or_update'         => Model_whitelist::NEW_OR_UPDATE_RULE
                        ]);
                    }
                }
            }
        }
    }
	 * 
	 */
}
