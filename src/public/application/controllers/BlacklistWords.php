<?php

require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Model_blacklist_words   $model_blacklist_words
 * @property Model_whitelist         $model_whitelist
 * @property Model_category          $model_category
 * @property Model_products          $model_products 
 * @property Model_stores            $model_stores
 * @property Model_integrations      $model_integrations
 * @property Model_brands            $model_brands
 * @property Model_phases            $model_phases
 */
class BlacklistWords extends Admin_Controller
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

    const ACTIVE = 1;
    const INACTIVE = 2;
    const DISCONTINUED = 3;
    const LOCKED = 4;
    const INCOMPLETED = 1;
    const COMPLETED = 2;

    const OPERATORS = [
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
        $this->load->model('model_blacklist_words');
        $this->load->model('model_whitelist');
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

        $this->data['page_title'] = $this->lang->line('application_manage_blacklist');
       /* 
	    $this->data['categories'] = $this->model_category->getActiveCategroy();
        $this->data['stores']     = $this->model_stores->getActiveStore();
        $this->data['brands']     = $this->model_brands->getBrandData();

        foreach ($this->model_integrations->getMyNamesIntegrations() as $integration) {
           $this->data['nameOfIntegrations'][$integration['int_to']] = $integration['name'];
        }
		*/
        $this->render_template('blacklistWords/index', $this->data);
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

                redirect("BlacklistWords/", 'refresh');
            }

            $this->mountDataToSave();

            foreach ($this->dataToSave as $data) {
                $this->model_blacklist_words->create($data);
            }

            $this->session->set_flashdata('success', $this->lang->line('application_word_registered_successfully'));

            redirect("BlacklistWords/", 'refresh');
        }

        redirect("BlacklistWords/", 'refresh');
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
			$word        = trim($this->postClean('word',TRUE)) ? 'da palavra "'.$this->postClean('word',TRUE).'" ' : '';
	        $productId   = trim($this->postClean('product_id',TRUE)) ? 'do produto de ID '.$this->postClean('product_id',TRUE).' ' : '';
	        $productSku  = trim($this->postClean('sku',TRUE)) ? 'que tenha o SKU "'.$this->postClean('sku',TRUE).'" ' : '';
	        $marketplace = trim($this->postClean('marketplace',TRUE)) ? 'para o marketplace "'.$this->postClean('marketplace',TRUE).'" ' : '';
	        $commission  = trim($this->postClean('commission',TRUE)) ? 'com comissão '.self::OPERATORS[self::OPERATOR_CONVERSION[$this->postClean('operator_commission',TRUE)]].' '.$this->postClean('commission',TRUE).'% ' : '';
	        $sellerIndex = trim($this->postClean('seller_index',TRUE)) ? 'de Seller Index '.self::OPERATORS[SELF::OPERATOR_CONVERSION[$this->postClean('operator_seller_index',TRUE)]].' '.$this->postClean('seller_index',TRUE).' ' : '';
		
           
            if (trim($this->postClean('store',TRUE))) {
                $store = $this->model_stores->getStoreById($this->postClean('store',TRUE));
                $storeName = 'da loja "'.$store['name'].'" ';
            } else {
                $storeName = '';
            }

            if (trim($this->postClean('brand',TRUE))) {
                $brand = $this->model_brands->getBrandData($this->postClean('brand',TRUE));
                $brandName = 'da marca "'.$brand['name'].'" ';
            } else {
                $brandName = '';
            }

            if (trim($this->postClean('category',TRUE))) {
                $category = $this->model_category->getCategoryData($this->postClean('category',TRUE));
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
	            $teste = json_decode($this->postClean('created_by',TRUE));
	
	            $createdBy = json_encode([
	                'user_id' => $this->session->userdata('id'),
	                'username' => $this->session->userdata('username'),
	                'email' => $this->session->userdata('email'),
	                'start' => date('Y-m-d H:i:s'),
	                'end' => ($this->postClean('status',TRUE) == 1 ? '' : date('Y-m-d H:i:s')),
	            ]);
	
	            $data = [
	                'words'                 => trim($this->postClean('word',TRUE)) ? $this->postClean('word',TRUE) : null,
	                'product_id'            => trim($this->postClean('product_id',TRUE)) ? $this->postClean('product_id',TRUE) : null,
	                'product_sku'           => trim($this->postClean('sku',TRUE)) ? $this->postClean('sku',TRUE) : null,
	                'store_id'              => trim($this->postClean('store',TRUE)) ? $this->postClean('store',TRUE) : null,
	                'phase_id'              => trim($phase_id) ? $phase_id : null,
	                'category_id'           => trim($this->postClean('category',TRUE)) ? $this->postClean('category',TRUE) : null,
	                'marketplace'           => trim($this->postClean('marketplace',TRUE)) ? $this->postClean('marketplace',TRUE) : null,
	                'brand_id'              => trim($this->postClean('brand',TRUE)) ? $this->postClean('brand',TRUE) : null,
	                'commission'            => trim($this->postClean('commission',TRUE)) ? $this->postClean('commission',TRUE) : null,
	                'operator_commission'   => trim($this->postClean('commission',TRUE)) ? SELF::OPERATOR_CONVERSION[$this->postClean('operator_commission',TRUE)] : null,
	                'seller_index'          => trim($this->postClean('seller_index',TRUE)) ? $this->postClean('seller_index',TRUE) : null,
	                'operator_seller_index' => trim($this->postClean('seller_index',TRUE)) ? SELF::OPERATOR_CONVERSION[$this->postClean('operator_seller_index',TRUE)] : null,
	                'sentence'              => $sentence,
	                'created_by'            => $createdBy,
	                'status'                => $this->postClean('status',TRUE),
	                'new_or_update'         => Model_blacklist_words::NEW_OR_UPDATE_RULE, 
	                'apply_to'				=> trim($this->postClean('apply_to',TRUE)) ? $this->postClean('apply_to',TRUE) : 2,
	            ];
	
	            if ($this->model_blacklist_words->create($data)) {
		            $this->session->set_flashdata('success', $this->lang->line('application_word_registered_successfully'));
					redirect("BlacklistWords/index", 'refresh');
				}
				else {
					$this->session->set_flashdata('error', $this->lang->line('messages_add_item_error'));
				}
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
			
        $this->render_template('blacklistWords/edit', $this->data);
    }

    public function update($id)
    {
        if(!in_array('updateCuration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['page_title'] = $this->lang->line('application_edit_word');
        $this->data['stores']     = $this->model_stores->getActiveStore();
        $this->data['categories'] = $this->model_category->getActiveCategroy();
        $this->data['brands']     = $this->model_brands->getActiveBrands();
        $this->data['phases']     = $this->model_phases->getActivePhases();
        $wordData = $this->model_blacklist_words->getWordById($id);

        foreach ($this->model_integrations->getMyNamesIntegrations() as $integration) {
            $this->data['nameOfIntegrations'][$integration['int_to']] = $integration['name'];
        }

        if ($this->postClean(NULL,TRUE)) {

            $word        = trim($this->postClean('word',TRUE)) ? 'da palavra "'.$this->postClean('word',TRUE).'" ' : '';
            $productId   = trim($this->postClean('product_id',TRUE)) ? 'do produto de ID '.$this->postClean('product_id',TRUE).' ' : '';
            $productSku  = trim($this->postClean('sku',TRUE)) ? 'que tenha o SKU "'.$this->postClean('sku',TRUE).'" ' : '';
            $marketplace = trim($this->postClean('marketplace',TRUE)) ? 'para o marketplace "'.$this->postClean('marketplace',TRUE).'" ' : '';
            $commission  = trim($this->postClean('commission',TRUE)) ? 'com comissão '.self::OPERATORS[SELF::OPERATOR_CONVERSION[$this->postClean('operator_commission',TRUE)]].' '.$this->postClean('commission',TRUE).'% ' : '';
            $sellerIndex = trim($this->postClean('seller_index',TRUE)) ? 'de Seller Index '.self::OPERATORS[SELF::OPERATOR_CONVERSION[$this->postClean('operator_seller_index',TRUE)]].' '.$this->postClean('seller_index',TRUE).' ' : '';

            if (trim($this->postClean('store',TRUE))) {
                $store = $this->model_stores->getStoreById($this->postClean('store',TRUE));
                $storeName = 'da loja "'.$store['name'].'" ';
            } else {
                $storeName = '';
            }

            if (trim($this->postClean('brand',TRUE))) {
                $brand = $this->model_brands->getBrandData($this->postClean('brand',TRUE));
                $brandName = 'da marca "'.$brand['name'].'" ';
            } else {
                $brandName = '';
            }

            if (trim($this->postClean('category',TRUE))) {
                $category = $this->model_category->getCategoryData($this->postClean('category',TRUE));
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
	            $teste = json_decode($this->postClean('created_by',TRUE));
	
	            $createdBy = json_encode([
	                'user_id' => $this->session->userdata('id'),
	                'username' => $this->session->userdata('username'),
	                'email' => $this->session->userdata('email'),
	                'start' => $this->postClean('status',TRUE) == $wordData['status'] ? $teste->start : ($this->postClean('status',TRUE) == 1 ? date('Y-m-d H:i:s') : $teste->start),
	                'end' => $this->postClean('status',TRUE) == $wordData['status'] ? $teste->end : ($this->postClean('status',TRUE) == 1 ? '' : date('Y-m-d H:i:s')),
	            ]);
	            
	            $data = [
	                'words'                 => trim($this->postClean('word',TRUE)) ? $this->postClean('word',TRUE) : null,
	                'product_id'            => trim($this->postClean('product_id',TRUE)) ? $this->postClean('product_id',TRUE) : null,
	                'product_sku'           => trim($this->postClean('sku',TRUE)) ? $this->postClean('sku',TRUE) : null,
	                'store_id'              => trim($this->postClean('store',TRUE)) ? $this->postClean('store',TRUE) : null,
                    'phase_id'              => trim($this->postClean('phase')) ? $this->postClean('phase') : null,
	                'category_id'           => trim($this->postClean('category',TRUE)) ? $this->postClean('category',TRUE) : null,
	                'marketplace'           => trim($this->postClean('marketplace',TRUE)) ? $this->postClean('marketplace',TRUE) : null,
	                'brand_id'              => trim($this->postClean('brand',TRUE)) ? $this->postClean('brand',TRUE) : null,
	                'commission'            => trim($this->postClean('commission',TRUE)) ? $this->postClean('commission',TRUE) : null,
	                'operator_commission'   => trim($this->postClean('commission',TRUE)) ? SELF::OPERATOR_CONVERSION[$this->postClean('operator_commission',TRUE)] : null,
	                'seller_index'          => trim($this->postClean('seller_index',TRUE)) ? $this->postClean('seller_index',TRUE) : null,
	                'operator_seller_index' => trim($this->postClean('seller_index',TRUE)) ? SELF::OPERATOR_CONVERSION[$this->postClean('operator_seller_index',TRUE)] : null,
	                'sentence'              => $sentence,
	                'created_by'            => $createdBy,
	                'status'                => $this->postClean('status',TRUE),
	                'new_or_update'         => Model_blacklist_words::NEW_OR_UPDATE_RULE, 
	                'apply_to'				=> trim($this->postClean('apply_to',TRUE)) ? $this->postClean('apply_to',TRUE) : 2,
	            ];
	
	            if ($this->model_blacklist_words->update($id, $data)) {
		            $this->session->set_flashdata('success', $this->lang->line('application_word_successfully_changed'));
		
		            redirect("BlacklistWords/index", 'refresh');
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

        $this->render_template('blacklistWords/edit', $this->data);
    }

    public function fetchBlacklistData($postdata = null)
    {
        $postdata = $this->postClean(NULL,TRUE);
		$ini      = $postdata['start'];
		$draw     = $postdata['draw'];
        $length = $postdata['length'];
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

        $data = $this->model_blacklist_words->getWordsDataView($ini, $length);

        foreach ($data as $key => $value) {

            $id = '<a href="'.base_url('BlacklistWords/update/'.$value['id']).'">'.$value['id'].'</a>';
            
            if ($value['status'] == 1) {
                $status = '<span class="label label-success">Ativo</span>';
            } else {
                $status = '<span class="label label-danger">Inativo</span>';
            }

            $teste = json_decode($value['created_by']);

            $result[$key] = [
                $id,
                ucfirst($value['sentence']),
                isset($teste->email) ? $teste->email : '', 
                isset($teste->start) ? $teste->start : '', 
                isset($teste->end) ? $teste->end : '', 
                $status
            ];
        }

        $filtered = $this->model_blacklist_words->getWordsDataViewCount($this->data['wordsfilter'] ?? '');

        $output = array(
			"draw"            => $draw,
		    "recordsTotal"    => $this->model_blacklist_words->getWordsDataViewCount(),
		    "recordsFiltered" => $filtered,
		    "data"            => $result
		);
        
        echo json_encode($output);
    }

    public function lockProduct($id)
    {
        if(!in_array('createCuration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($this->postClean(NULL,TRUE)) {
            $product_data = $this->model_products->verifyProductsOfStore($id);
            if ($product_data['status'] == Model_products::DELETED_PRODUCT) {
                $this->session->set_flashdata('error', $this->lang->line('messages_lock_product_removed'));
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
                'words'                 => null,
                'product_id'            => $id,
                'product_sku'           => null,
                'store_id'              => null,
                'category_id'           => null,
                'marketplace'           => null,
                'brand_id'              => null,
                'commission'            => null,
                'operator_commission'   => null,
                'seller_index'          => null,
                'operator_seller_index' => null,
                'sentence'              => $sentence,
                'created_by'            => $createdBy,
                'status'                => self::ACTIVE,
                'new_or_update'         => Model_blacklist_words::NEW_OR_UPDATE_RULE, 
                'apply_to'				=> 1,
            ];

            $create = $this->model_blacklist_words->create($data);

            $insert = [];

            $locks = [
                'product_id' => $id,
                'blacklist_id' => $create,
                'sentence' => $sentence
            ];

            array_push($insert, $locks);

            $this->model_blacklist_words->createProductWithLock($insert);

            $this->model_products->update(['status' => 4], $id);

            $this->model_whitelist->updateStatusIfExists($data);

            $this->session->set_flashdata('success', $this->lang->line('application_word_successfully_changed'));

            redirect("products/update/".$id, 'refresh');
        }
    }

	/*
    private function validateFields()
    {
        if ($this->postClean('word',TRUE)) {
            $this->word = $this->postClean('word',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('product_id',TRUE)) {
            $this->productId = $this->postClean('product_id',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('sku',TRUE)) {
            $this->sku = $this->postClean('sku',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('store',TRUE)) {
            $this->store = $this->postClean('store',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('category',TRUE)) {
            $this->category = $this->postClean('category',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('marketplace',TRUE)) {
            $this->marketplace = $this->postClean('marketplace',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('brand',TRUE)) {
            $this->brand = $this->postClean('brand',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('commission',TRUE)) {
            $this->commission         = $this->postClean('commission',TRUE);
            $this->operatorCommission = $this->postClean('operator_commission',TRUE);
            $this->hasContent = true;
        }

        if ($this->postClean('seller_index',TRUE)) {
            $this->sellerIndex         = $this->postClean('seller_index',TRUE);
            $this->operatorSellerIndex = $this->postClean('operator_seller_index',TRUE);
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
                            'new_or_update'         => Model_blacklist_words::NEW_OR_UPDATE_RULE
                        ]);
                    }
                }
            }
        }
    }
	*/
	
    public function dashboard()
    {
        $this->data['page_title'] = $this->lang->line('application_manage_blacklist');

        // Obs.: Não gostei da forma que fiz essa controller de dashboard!! 
        // São feitas diversas consultas à mesma tabela para trazer counts diferentes.
        // E, como a tabela de products tem mais de 60 mil produtos, o carregamento está pesado. 
        // VOU REFATORAR!
        
        $this->data['quantityOfRegisteredProducts'] = $this->model_products->getProductCount();
        $this->data['quantityOfActivedProducts'] = $this->model_products->getProductCount(' AND (p.status = 1)');
        $this->data['quantityOfInactivedProducts'] = $this->model_products->getProductCount(' AND (p.status = 2)');
        $this->data['quantityOfDiscontinuedProducts'] = $this->model_products->getProductCount(' AND (p.status = 3)');
        $this->data['quantityOfLockedProducts'] = $this->model_products->getProductCount(' AND (p.status = 4)');
        $this->data['quantityOfCompletedProducts'] = $this->model_products->getProductCount(' AND (p.situacao = 2)');
        $this->data['quantityOfErrorsTransformationProducts'] = $this->model_products->getProductCount(' AND (et.prd_id IS NOT NULL)');
        $this->data['quantityOfIncompletedProducts'] = $this->model_products->getProductCount(' AND (p.situacao = 1)');

        $this->data['quantityOfIntegratedProducts'] = $this->model_products->getProductCount(' AND (i.status_int = 2)');
        $this->data['quantityOfPublichedProducts'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.status = 0)');

        $this->data['quantityOfIntegratedProductsB2B'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.int_to = "B2W")');
        $this->data['quantityOfIntegratedProductsVia'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.int_to = "VIA")');
        $this->data['quantityOfIntegratedProductsCar'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.int_to = "CAR")');
        $this->data['quantityOfIntegratedProductsMl'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND (i.int_to = "ML" OR i.int_to = "MLC"))');

        $this->data['quantityOfErrorsTransformationProductsB2W'] = $this->model_products->getProductCount(' AND (et.prd_id IS NOT NULL AND i.int_to = "B2W")');
        $this->data['quantityOfErrorsTransformationProductsVia'] = $this->model_products->getProductCount(' AND (et.prd_id IS NOT NULL AND i.int_to = "VIA")');
        $this->data['quantityOfErrorsTransformationProductsCar'] = $this->model_products->getProductCount(' AND (et.prd_id IS NOT NULL AND i.int_to = "CAR")');
        $this->data['quantityOfErrorsTransformationProductsMl'] = $this->model_products->getProductCount(' AND (et.prd_id IS NOT NULL AND (i.int_to = "ML" OR i.int_to = "MLC"))');

        $this->data['quantityOfPublichedProductsB2B'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.status = 0 AND i.int_to = "B2W")');
        $this->data['quantityOfPublichedProductsVia'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.status = 0 AND i.int_to = "VIA")');
        $this->data['quantityOfPublichedProductsCar'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.status = 0 AND i.int_to = "CAR")');
        $this->data['quantityOfPublichedProductsMl'] = $this->model_products->getProductCount(' AND (i.status_int = 2 AND i.status = 0 AND (i.int_to = "ML" OR i.int_to = "MLC"))');

        $this->render_template('blacklistWords/dashboard', $this->data);
    }
}
