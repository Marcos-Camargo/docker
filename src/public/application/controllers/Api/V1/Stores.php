<?php

require APPPATH . "controllers/Api/V1/API.php";

require_once APPPATH . "libraries/Logistic/vendor/autoload.php";

/**
 * @property CI_Loader $load
 *
 * @property Model_settings $model_settings
 * @property Model_banks $model_banks
 * @property Model_integrations $model_integrations
 * @property Model_gateway $model_gateway
 * @property Model_job_schedule $model_job_schedule
 * @property \Logistic\Repositories\IntegrationLogistics $integrationLogistics
 * @property \Logistic\Repositories\IntegrationLogisticConfigurations $integrationLogisticConfigurations
 * @property \Logistic\Services\IntegrationLogisticService $integrationLogisticService
 */

class Stores extends API
{
    private const STORE_ACTIVE    = 1;
    private const FR_REGISTER     = 6;
    
    private $filters;
    private $search;
    private $insert;
    private $header;
    private $errors = [];
    private $update = [];
	
	private $bank_is_optional = false;
	private $id = null;

    private $allowRegisterStoreWithCatalog;

    private $database_x_api_fields = [
        'name'                  => 'store_name',
        'company_id'            => 'company_id',
        'address'               => 'address',
        'addr_num'              => 'number',
        'addr_compl'            => 'complement',
        'addr_neigh'            => 'neighborhood',
        'addr_city'             => 'city',
        'addr_uf'               => 'state',
        'country'               => 'country',
        'phone_1'               => 'tel1',
        'phone_2'               => 'tel2',
        'zipcode'               => 'zipcode',
        'CNPJ'                  => 'cnpj',
        '_CNPJ'                 => 'cpf',
        'catalogs'              => 'catalogs',
        'associate_type'        => 'associate_type',      
        'raz_social'            => 'corporate_name',
        'inscricao_estadual'    => 'state_reg',
        '_inscricao_estadual'   => 'rg',
        'service_charge_value'  => 'service_charge',
     	'service_charge_freight_value' => 'service_charge_freight',
        'freight_seller'               => 'freight_seller',
        'freight_seller_type'          => 'freight_seller_type',
        'freight_seller_end_point'     => 'freight_seller_end_point',
        'freight_seller_code'          => 'freight_seller_code',
        // 'fr_email_contato'      => 'contact_email',
        // 'fr_email_nfe'          => 'nfe_email',
        // 'fr_email_login'        => 'login_email',
        // 'fr_senha'              => 'password',
        'responsible_name'      => 'name',
        'responsible_cpf'       => 'cpf',
        'responsible_email'     => 'email',
        'bank'                  => 'bank',
        'agency'                => 'agency',
        'account_type'          => 'account_type',
        'account'               => 'account',
        'business_street'       => 'address',
        'business_addr_num'     => 'number',
        'business_addr_compl'   => 'complement',
        'business_neighborhood' => 'neighborhood',
        'business_town'         => 'city',
        'business_uf'           => 'state',
        'business_nation'       => 'country',
        'business_code'         => 'zipcode',
        'description'         	=> 'description',
        'exchange_return_policy'  => 'exchange_return_policy',
        'delivery_policy'         => 'delivery_policy',
        'security_privacy_policy' => 'security_privacy_policy',
        'erp_customer_supplier_code' => 'erp_customer_supplier_code',
        'invoice_cnpj'               => 'invoice_cnpj',
        'logistic'                   => 'logistic',
        'logistic_module'            => 'logistic_module',
        'onboarding'            => 'onboarding_date',
        'what_integration'      => 'integration',
        'billing_expectation'   => 'billing_expectation',
        'operation_store'       => 'operation',
        'mix_of_product'        => 'products',
        'how_up_and_fature'     => 'up_and_fature'
    ];

    private $validations = [
        'name'                    => 'required_',
        'address'                 => 'required_',
        'addr_num'                => 'required_',
        'addr_neigh'              => 'required_',
        'addr_city'               => 'required_',
        'addr_uf'                 => 'required_|check_uf|equal_2',
        'country'                 => 'required_|equal_2',
        'zipcode'                 => 'required_|numeric_|equal_8',
        'business_street'         => 'required_',
        'business_addr_num'       => 'required_',
        'business_neighborhood'   => 'required_',
        'business_town'           => 'required_',
        'business_uf'             => 'required_|check_uf|equal_2',
        'business_nation'         => 'required_|equal_2',
        'business_code'           => 'required_|numeric_|equal_8',
        'raz_social'              => 'required_',
        'CNPJ'                    => 'required_|check_cnpj|unique_CNPJ',
        'inscricao_estadual'      => 'alphanumericoptional_',
		'associate_type'		  => 'check_associatetype',
        'responsible_name'        => 'required_',
        'responsible_email'       => 'required_',
        'responsible_cpf'         => 'required_|numeric_|check_cpf',
        'bank'                    => 'required_|check_bank',
        'agency'                  => 'required_',
        'account_type'            => 'required_|check_account',
        'account'                 => 'required_',
        'service_charge_value'    => 'required_|numeric_',
        'service_charge_freight_value'    => 'numericoptional_',
        // 'fr_email_contato'        => 'required_',
        // 'fr_email_nfe'            => 'required_',
        'company_id'              => 'required_|numeric_|check_company',
        'phone_1'                 => 'required_|numeric_|min_10|max_11'
    ];

    private $dontUpdate = [
        'CNPJ', 'company_id'
    ];

	public function __construct()
	{
		parent::__construct();
		
		$this->load->model('model_settings');
		$this->load->model('model_banks');
		$this->load->model('model_integrations');
        $this->load->model('model_gateway');
        $this->load->model('model_job_schedule');

		$this->bank_is_optional = $this->model_settings->getStatusbyName('store_optional_bank_details');
        $this->allowRegisterStoreWithCatalog = $this->model_settings->getStatusbyName('store_with_catalog');
        $allowCorsOcc = $this->model_settings->getStatusbyName('allow_cors_occ');
        if($allowCorsOcc){
            if ( "OPTIONS" === $_SERVER['REQUEST_METHOD'] ) {
                die();
            }
        }

        $useMsShipping = (new \Microservices\v1\Logistic\ShippingCarrier())->use_ms_shipping;
        $this->integrationLogistics = $useMsShipping ?
            new \Logistic\Repositories\MS\v1\IntegrationLogistics() : new \Logistic\Repositories\CI\v1\IntegrationLogistics();
        $this->integrationLogisticConfigurations = $useMsShipping ?
            new \Logistic\Repositories\MS\v1\IntegrationLogisticConfigurations() : new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations();
        $this->integrationLogisticService = new \Logistic\Services\IntegrationLogisticService($this->integrationLogistics, $this->integrationLogisticConfigurations);
	}

    public function index_get()
    {
//        if (!$this->app_authorized)
//            return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);
        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->search = $this->cleanGet();

        if (!$this->mountTheFilters()) {
            return $this->response(array('success' => false, 'result' => $this->lang->line('api_store_id_numeric')), REST_Controller::HTTP_BAD_REQUEST);
        }
        $stores = $this->responseFormat($this->getStores());

        if (!$stores) {
            return $this->response(array('success' => true, 'result' => $this->lang->line('api_no_content_found')), REST_Controller::HTTP_OK);
        }
        
        return $this->response(array('success' => true, 'result' => $stores), REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
//        if (!$this->app_authorized)
//            return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        // $data   = json_decode(file_get_contents('php://input'));
        $data   = $this->inputClean();
		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => $this->lang->line('api_invalid_json_format')), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result = $this->insert($data);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => ['id' => $result, 'message' => $this->lang->line('api_store_created')]), REST_Controller::HTTP_CREATED);
    }

    public function index_put($id = null)
    {
//        if (!$this->app_authorized)
//            return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
		if (is_null($id)) {
			return $this->response(array('success' => false, 'errors' => $this->lang->line('api_id_supplied')), REST_Controller::HTTP_BAD_REQUEST);
		}
		
        // $data   = json_decode(file_get_contents('php://input'));
        $data   = $this->inputClean();
		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => $this->lang->line('api_invalid_json_format')), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result = $this->update($data, $id);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => $this->lang->line('api_updated_data')), REST_Controller::HTTP_OK);
    }

    private function mountTheFilters()
    {
        if ($this->tokenMaster) {
            $this->filters = " WHERE stores.id IS NOT NULL ";
        } else {
            $this->filters = " WHERE stores.provider_id = " . $this->db->escape($this->header['x-provider-key']);
        }

        if (isset($this->search['store_id'])) {
            if (!is_numeric($this->search['store_id'])) {
                return false;
            } else {
                $this->filters .= " AND stores.id = " . $this->db->escape($this->search['store_id']);
            }
        }
        if (isset($this->search['store_name'])) $this->filters   .= " AND stores.name LIKE '%" . $this->db->escape_like_str($this->search['store_name']) . "%' ";
        if (isset($this->search['company_name'])) $this->filters .= " AND company.name LIKE '%" . $this->db->escape_like_str($this->search['company_name']) . "%' ";

        if (isset($this->search['cnpj'])) {
			$cnpj = preg_replace( '/[^0-9]/is', '', $this->search['cnpj']);
			$cnpj_format = $this->formatCnpjCpf($cnpj);
			$this->filters .= " AND (company.CNPJ = " . $this->db->escape($cnpj) . " OR company.CNPJ = " . $this->db->escape($cnpj_format). ")";
		}
		
        return true;
    }

    private function getStores()
    {
        $sql = "SELECT 
                    stores.*, 
                    stores.name as name, 
                    company.name as company,
                    company.IMUN as municipal_reg,
                    si.seller_index as seller_index
                FROM stores 
                LEFT JOIN company ON company.id = stores.company_id 
                INNER JOIN seller_index si ON si.store_id = stores.id " . $this->filters;

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    private function responseFormat($stores)
    {
        $response = [];
		
		foreach ($stores as $store) {
            if ($store['active'] == 1) { $status = 'active'; }
            elseif ($store['active'] == 2) { $status = 'inactive'; }
            elseif ($store['active'] == 3) { $status = 'in negociation'; }
            elseif ($store['active'] == 4) { $status = 'billet'; }
            elseif ($store['active'] == 5) { $status = 'churn'; }
            if ($this->allowRegisterStoreWithCatalog === "1") {
                $catalogs = $this->getCatalogs($store['id']);
            } else {
                $catalogs = null;
            }
            $seller_ids=[];
            $integralizations=$this->model_integrations->getVtexData($store['id'],$store['company_id']);

            if(count($integralizations)!=0){
                foreach($integralizations as $integralization){
                    if($integralization['auth_data']!=null){
                        $auth_data=json_decode($integralization['auth_data']);
                        if(isset($auth_data->seller_id))
                            if($auth_data->seller_id){
                                array_push($seller_ids,['seller_id'=>$auth_data->seller_id,'marketplace_id'=>$integralization['int_to']]);
                            }
                    }
                }
            }
            $store_logo = null;
            if ((!is_null($store['logo'])) && ($store['logo'] !='')){
                $store_logo = base_url() . $store['logo'];
            }

            $gatewaySubAaccount = $this->model_gateway->getSubAccountByStoreId($store['id']);
            $gateway_subaccount_name = !$gatewaySubAaccount ? '' : ucfirst($gatewaySubAaccount['name']);
            $gateway_subaccount_id   = !$gatewaySubAaccount ? '' : $gatewaySubAaccount['gateway_account_id'];

            $freight_seller_type = null;
            switch ($store['freight_seller_type']) {
                case '1':
                    $freight_seller_type = 'Precode';
                    break;
                case '2':
                    $freight_seller_type = 'Tabela Conecta Lá';
                    break;
                case '3':
                    $freight_seller_type = 'Intelipost (Seller)';
                    break;
                case '4':
                    $freight_seller_type = 'Intelipost (Conecta Lá)';
                    break;
                case '5':
                    $freight_seller_type = 'Frete Rápido (Seller)';
                    break;
                case '8':
                    $freight_seller_type = 'Sequoia';
                    break;
                case '10':
                    $freight_seller_type = 'Dress & Go';
                    break;
                case '9':
                    $freight_seller_type = 'Integração (ERP)';
                    break;
                default:
                    $type_view_tag = $store['freight_seller_type'];
            }

            $type_view_tag = '';
            switch ($store['type_view_tag']) {
                case 'all':
                    $type_view_tag = 'Correios, Transportadora e/ou Gateway Logístico';
                    break;
                case 'correios':
                    $type_view_tag = 'Correios';
                    break;                
                case 'shipping_company_gateway':
                    $type_view_tag = 'Transportadora e/ou Gateway Logístico';
                    break;
                default:
                    $type_view_tag = $store['type_view_tag'];
            }
            
            $format = [
                'id'                            => $store['id'],
                'name'                          => $store['name'],
                'sellers_id'                    => $seller_ids,
                'company'                       => $store['company'],
                'active'            	        => $store['active'],
                'active_description'   	        => $status,
                'cnpj'               	        => $store['CNPJ'],
                'state_reg'               	    => $store['inscricao_estadual'],
                'municipal_reg'               	=> $store['municipal_reg'],
                'description'                   => $store['description'],
                'catalogs'                      => $catalogs,
                'associate_type'			    => $store['associate_type'],
                'exchange_return_policy'        => $store['exchange_return_policy'],
                'delivery_policy' 			    => $store['delivery_policy'],
                'security_privacy_policy' 	    => $store['security_privacy_policy'],
                'erp_customer_supplier_code'    => $store['erp_customer_supplier_code'],
                'service_charge_value'          => (float)$store['service_charge_value'],
                'service_charge_freight_value'  => (float)$store['service_charge_freight_value'],
                'logo'                          => $store_logo,
                'invoice_cnpj'                  => $store['invoice_cnpj'],
                'onboarding_date'               => $store['onboarding'],
                'collection_address' => [
                    'address'      => $store['address'],
                    'number'       => $store['addr_num'],
                    'complement'   => $store['addr_compl'],
                    'neighborhood' => $store['addr_neigh'],
                    'city'         => $store['addr_city'],
                    'state'        => $store['addr_uf'],
                    'country'      => $store['country'],
                    'zipcode'      => $store['zipcode']
                ],
                'business_address'   => [
                    'address'      => $store['business_street'],
                    'number'       => $store['business_addr_num'],
                    'complement'   => $store['business_addr_compl'],
                    'neighborhood' => $store['business_neighborhood'],
                    'city'         => $store['business_town'],
                    'state'        => $store['business_uf'],
                    'country'      => $store['business_nation'],
                    'zipcode'      => $store['business_code']
                ],
                'responsible'        => [
                    'name'                      => $store['responsible_name'],
                    'cpf'                       => $store['responsible_cpf'],
                    'email'                     => $store['responsible_email'],
                    'bank'                      => $store['bank'],
                    'agency'                    => $store['agency'],
                    'account_type'              => $store['account_type'],
                    'account'                   => $store['account'],
                    'gateway_subaccount_name'   => $gateway_subaccount_name,
                    'gateway_subaccount_id'     => $gateway_subaccount_id
                ],
                'logistic'        => [
                    'freight_seller'                    => $store['freight_seller'],
                    'freight_seller_type'               => $store['freight_seller'] ? $freight_seller_type : null,
                    'freight_seller_endpoint_or_token'  => $store['freight_seller'] ? $store['freight_seller_end_point'] : null,
                    'freight_seller_code'               => $store['freight_seller'] ? $store['freight_seller_code'] : null,
                    'type_tag'                          => $type_view_tag
                ],
                'handover'        => [
                    'integration'         => $store['what_integration'],
                    'billing_expectation' => $store['billing_expectation'],
                    'operation'           => $store['operation_store'],
                    'products'            => $store['mix_of_product'],
                    'up_and_fature'       => $store['how_up_and_fature']
                ]
            ];

            if ($store['pj_pf'] == 'pf' && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
                $format['cpf'] = $format['cnpj'];
                $format['rg'] = $format['state_reg'];
                unset($format['cnpj']);
                unset($format['state_reg']);
            }

            array_push($response, $format);
        }
        
        return $response;
    }

    private function insert($data)
    {
    	if (!isset($data->store)) {
			array_push($this->errors, $this->lang->line('api_missing_key_store'));
			return false;
		}
        $store              = $data->store;
        $collection_address = $data->store->collection_address;
        $business_address   = $data->store->business_address;
        // $logistics          = $data->store->logistics;
        $responsible        = $data->store->responsible;
        $handover           = $data->store->handover;
		
		$nivel = array('collection_address','business_address','responsible','handover');
		foreach ($data->store as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && (!in_array($key, $nivel))) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
		foreach ($collection_address as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
		foreach ($business_address as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
		foreach ($responsible as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
        foreach ($handover as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}

        if ($this->allowRegisterStoreWithCatalog === "1") {
            $catalogsId = $this->validateCatalogs($store);
			if ($catalogsId === false) {
				return false;
			}
        }
		
		$this->id = null;

        if (isset($responsible->bank)) {
            $banks = $this->model_banks->getNamesBanks();
            foreach ($banks as $bank)
                if (mb_strtoupper($responsible->bank) == mb_strtoupper($bank))
                    $responsible->bank = $bank;
        }

        if (isset($responsible->account_type)) {
            $accounts = ['Conta Corrente', 'Conta Poupança'];
            foreach ($accounts as $account)
                if (mb_strtoupper($responsible->account_type) == mb_strtoupper($account))
                    $responsible->account_type = $account;
        }

        $store_array_data = [
            'name'                  => (isset($store->store_name) ? $store->store_name : ''),
            'company_id'            => (isset($store->company_id) ? $store->company_id : ''),
            'address'               => (isset($collection_address->address) ? $collection_address->address : ''),
            'addr_num'              => (isset($collection_address->number) ? $collection_address->number : ''),
            'addr_compl'            => (isset($collection_address->complement) ? $collection_address->complement : ''),
            'addr_neigh'            => (isset($collection_address->neighborhood) ? $collection_address->neighborhood : ''),
            'addr_city'             => (isset($collection_address->city) ? $collection_address->city : ''),
            'addr_uf'               => (isset($collection_address->state) ? $collection_address->state : ''),
            'country'               => (isset($collection_address->country) ? $collection_address->country : ''),
            'phone_1'               => (isset($store->tel1) ? $store->tel1 : ''),
            'phone_2'               => (isset($store->tel2) ? $store->tel2 : ''),
            'zipcode'               => (isset($collection_address->zipcode) ? $collection_address->zipcode : ''),
            'CNPJ'                  => (isset($store->cnpj) ? $store->cnpj : ''),
            'raz_social'            => (isset($store->corporate_name) ? $store->corporate_name : ''),
            'inscricao_estadual'    => (isset($store->state_reg) ? $store->state_reg : ''),
            'associate_type'        => (isset($store->associate_type) ? (int)$store->associate_type : ''),
            'service_charge_value'  => (isset($store->service_charge) ? $store->service_charge : ''),
            'service_charge_freight_value'  => (isset($store->service_charge_freight) ? $store->service_charge_freight : $store->service_charge),
            'active'                => self::STORE_ACTIVE,
            'prefix'                => strtoupper(substr(md5(uniqid(mt_rand(99999,99999999), true)), 0, 5)),
            'fr_cadastro'           => self::FR_REGISTER,
            'responsible_name'      => (isset($responsible->name) ? $responsible->name : ''),
            'responsible_cpf'       => (isset($responsible->cpf) ? $responsible->cpf : ''),
            'responsible_email'     => (isset($responsible->email) ? $responsible->email : ''),
            'bank'                  => (isset($responsible->bank) ? $responsible->bank : ''),
            'agency'                => (isset($responsible->agency) ? $responsible->agency : ''),
            'account_type'          => (isset($responsible->account_type) ? $responsible->account_type : ''),
            'account'               => (isset($responsible->account) ? $responsible->account : ''),
            'business_street'       => (isset($business_address->address) ? $business_address->address : ''),
            'business_addr_num'     => (isset($business_address->number) ? $business_address->number : ''),
            'business_addr_compl'   => (isset($business_address->complement) ? $business_address->complement : ''),
            'business_neighborhood' => (isset($business_address->neighborhood) ? $business_address->neighborhood : ''),
            'business_town'         => (isset($business_address->city) ? $business_address->city : ''),
            'business_uf'           => (isset($business_address->state) ? $business_address->state : ''),
            'business_nation'       => (isset($business_address->country) ? $business_address->country : ''),
            'business_code'         => (isset($business_address->zipcode) ? $business_address->zipcode : ''),

            'provider_id'           => $this->header['x-provider-key'],

            'description'        		 => (isset($store->description) ? $store->description : ''),
            'exchange_return_policy'     => (isset($store->exchange_return_policy) ? $store->exchange_return_policy : ''),
            'delivery_policy'            => (isset($store->delivery_policy) ? $store->delivery_policy : ''),
            'security_privacy_policy'    => (isset($store->security_privacy_policy) ? $store->security_privacy_policy : ''),
            'erp_customer_supplier_code' => (isset($store->erp_customer_supplier_code) ? $store->erp_customer_supplier_code : ''),
            'invoice_cnpj'               => (isset($store->invoice_cnpj) ? $store->invoice_cnpj : ''),
            'onboarding'                 => (isset($store->onboarding_date) ? $store->onboarding_date : ''),
            'what_integration'           => (isset($handover->integration) ? $handover->integration : ''),
            'billing_expectation'        => (isset($handover->billing_expectation) ? $handover->billing_expectation : ''),
            'operation_store'            => (isset($handover->operation) ? $handover->operation : ''),
            'mix_of_product'             => (isset($handover->products) ? $handover->products : ''),
            'how_up_and_fature'          => (isset($handover->up_and_fature) ? $handover->up_and_fature : '')
        ];

        $is_pf = false;
        if (property_exists($store, 'cpf') && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
            $is_pf = true;
            $store_array_data['CNPJ'] = $store->cpf;
            $store_array_data['pj_pf'] = 'pf';

            if (property_exists($store, 'state_reg')) {
                $store_array_data['rg'] = $store->state_reg;
            }
        }

        if ($this->model_settings->getValueIfAtiveByName('allow_automatic_antecipation')) {
            $store_array_data['use_automatic_antecipation'] = 0;
            $store_array_data['antecipation_type'] = $this->model_settings->getValueIfAtiveByName('antecipacao_dx_default');
            $store_array_data['percentage_amount_to_be_antecipated'] = $this->model_settings->getValueIfAtiveByName('porcentagem_antecipacao_default');
            $store_array_data['number_days_advance'] = $this->model_settings->getValueIfAtiveByName('numero_dias_dx_default');
            $store_array_data['automatic_anticipation_days'] = $this->model_settings->getValueIfAtiveByName('automatic_anticipation_days_default');
        }

        // Apenas adiciona as verificações caso não seja isento.
        if (strtolower($store_array_data['inscricao_estadual']) != strtolower("ISENTO")) {
            $check_ie = 'required_|check_ie';
            if ($is_pf && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
                $check_ie = 'check_rg';
            }

            $this->validations['inscricao_estadual'] = "$check_ie|unique_inscricao_estadual";
        } else {
            // Se for igual a isento, então seta o valor como 0.
            $store_array_data['inscricao_estadual'] = "0";
        }

        $this->insert = $store_array_data;

        try {
            $this->setLogisticForCreate($store);
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();
            return false;
        }

        if (!$this->validateCreate($is_pf)) {
            return false;
        }

        $result = $this->db->insert('stores', $this->insert);
        $id     = $this->db->insert_id();

        if (!empty($id)) {
            $this->model_job_schedule->create([
                'module_path' => "Automation/CreateApplicationAttributes",
                'module_method' => 'run',
                'params' => "{$id}",
                'status' => 0,
                'finished' => 0,
                'date_start' => date('Y-m-d H:i:s', strtotime("+2 minutes")),
                'date_end' => null,
                'server_id' => 0
            ]);
        }

        if ($this->allowRegisterStoreWithCatalog === "1") {
            $this->db->delete('catalogs_stores', array('store_id' => $id));
            foreach ($catalogsId as $catalogId) {
                $this->db->insert('catalogs_stores', ['catalog_id' => $catalogId, 'store_id' => $id]);
            }
        }

        if (!empty($store->logistic)) {
            try {
                if ($this->integrationLogisticConfigurations->getLogisticAvailableBySellerCenter(strtolower($store->logistic ?? ''))) {
                    $res = $this->integrationLogisticService->saveIntegrationLogisticConfigure([
                        'integration_name' => $store->logistic,
                        'use_seller' => false,
                        'use_sellercenter' => true,
                        'credentials' => null,
                        'store_id' => $id,
                        'user_created' => 0,
                        'active' => true
                    ]);
                }
            } catch (Throwable $e) {
            }
        }
        
        $store_token = $this->createTokenAPI($id, $store->company_id);

        $this->db->where('id', $id);
        $this->db->update('stores', ['token_api' => $store_token]);

        return $id;
    }

    private function update($data, $id)
    {
        $filter = " AND provider_id = ".$this->header['x-provider-key'];
        if ($this->tokenMaster) {
         	$filter = '';
        }
        $sql    = "SELECT * FROM stores WHERE id = ? ".$filter; 
        $query  = $this->db->query($sql, array($id));
        $result = $query->result_array();
        
        if (!$result) {
            array_push($this->errors, $this->lang->line('api_store_not_found'));
            return;
        };
		
		$nivel = array('collection_address','business_address','responsible','handover');
		foreach ($data->store as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && (!in_array($key, $nivel))) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_update') . $key);
		}

        if ($this->allowRegisterStoreWithCatalog === "1") {
            $catalogsId = $this->validateCatalogs($data->store);
			if ($catalogsId === false) {
				return false;
			}
        }
		
		$this->id = $id;
		
        foreach ($data->store as $store_key => $store_value) {
            switch ($store_key) {
                case 'collection_address':
                    foreach ($data->store->collection_address as $collection_address_key => $collection_address_value) {
                        $key = array_search($collection_address_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $collection_address_value) : '';
                    }
                    break;
                case 'business_address':
                    foreach ($data->store->business_address as $business_address_key => $business_address_value) {
                        $array_business_address = array_slice($this->database_x_api_fields, 20, 10, true);
                        $key = array_search($business_address_key, $array_business_address);
                        $key ? ($this->update[$key] = $business_address_value) : '';
                    }
                    break;
                // case 'logistics':
                //     foreach ($data->store->logistics as $logistics_key => $logistics_value) {
                //         $key = array_search($logistics_key, $this->database_x_api_fields);
                //         $key ? ($this->update[$key] = $logistics_value) : '';
                //     }
                //     break;
                case 'responsible':
                    foreach ($data->store->responsible as $responsible_key => $responsible_value) {
                        $key = array_search($responsible_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $responsible_value) : '';
                    }
                    break;
                case 'handover':
                    foreach ($data->store->handover as $handover_key => $handover_value) {
                        $key = array_search($handover_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $handover_value) : '';
                    }
                    break;
                default:
                    $key = array_search($store_key, $this->database_x_api_fields);
                    $this->update[$key] = $store_value;
                    break;
            }
        }
        
        if (!$this->validateUpdate()) {
            return false;
        }

        if (isset($this->update['catalogs'])) {
            unset($this->update['catalogs']);
        }

        $this->db->where('id', $id);
        $this->db->update('stores', $this->update);

        if ($this->allowRegisterStoreWithCatalog === "1") {
            $this->db->delete('catalogs_stores', array('store_id' => $id));
            foreach ($catalogsId as $catalogId) {
                $this->db->insert('catalogs_stores', ['catalog_id' => $catalogId, 'store_id' => $id]);
            }
        }

        return true;
    }

    private function validateCreate($is_pf)
    {
		if ($this->bank_is_optional==1) {
			$this->validations['bank'] = 'check_bank';
       		$this->validations['agency'] = 'alphanumericoptional_';
        	$this->validations['account_type'] = 'check_account';
        	$this->validations['account'] = 'alphanumericoptional_';
		}

        if (!empty($this->insert['onboarding'])) $this->validations['onboarding'] = 'date_';
		 
        foreach ($this->validations as $field => $validation) {
            $rules = explode('|', $validation);
            foreach ($rules as $rule) {
                if ($is_pf && $field == 'CNPJ' && $rule == 'check_cnpj' && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
                    $rule = 'check_cpf';
                }
                if ($is_pf && $field == 'inscricao_estadual' && $rule == 'check_ie' && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
                    $rule = 'check_rg';
                }

                $method = strstr($rule, '_', true);
                $this->$method($field, $rule, 'insert');
            }
        }

        if ($this->errors) {
            return false;
        }

        return true;
    }

    private function validateUpdate()
    {
    	
		if ($this->bank_is_optional==1) {
			$this->validations['bank'] = 'check_bank';
       		$this->validations['agency'] = 'alphanumericoptional_';
        	$this->validations['account_type'] = 'check_account';
        	$this->validations['account'] = 'alphanumericoptional_';
		}
        
        if (!empty($this->update['onboarding'])) $this->validations['onboarding'] = 'date_';

        foreach ($this->validations as $field => $validation) {
            foreach ($this->update as $keyUpdate => $valueUpdate) {
                if ($field == $keyUpdate) {
                    if (in_array($field, $this->dontUpdate)) {
                        array_push($this->errors, $this->lang->line('api_updating_field') . $this->database_x_api_fields[$field] . $this->lang->line('api_updating_field_end'));
                        continue;
                    }
                    $rules = explode('|', $validation);
                    foreach ($rules as $rule) {
                        $method = strstr($rule, '_', true);
                        $this->$method($field, $rule, 'update');
                    }
                }
            }
        }

        if ($this->errors) {
            return false;
        }

        return true;
    }

	private function numericoptional($field, $numeric, $type)
    {
    	if (is_null($this->$type[$field]) || $this->$type[$field]=='') { // é opcional
    		return ;
    	} 
	
        $is_numeric = is_numeric($this->$type[$field]);
        if (!$is_numeric) {
            array_push($this->errors, $this->lang->line('api_field') . $this->database_x_api_fields[$field] . $this->lang->line('api_field_numeric'));
        }
        return;
    }
	
    private function alphanumericoptional($field, $numeric, $type)
    {
    	if (is_null($this->$type[$field]) || $this->$type[$field]=='') { // é opcional
    		return ;
    	} 
        return;
    }

    private function numeric($field, $numeric, $type)
    {
        $is_numeric = is_numeric($this->$type[$field]);
        if (!$is_numeric) {
            array_push($this->errors, $this->lang->line('api_field') . $this->database_x_api_fields[$field] . $this->lang->line('api_field_numeric'));
        }
        return;
    }

    private function required($field, $required, $type)
    {
        $empty = empty(trim($this->$type[$field]));
        if ($empty) {
            array_push($this->errors, $this->lang->line('api_field') . $this->database_x_api_fields[$field] . $this->lang->line('api_field_required'));
        }
        return;
    }

    private function min($field, $min, $type)
    {
        $lenght = strlen(trim($this->$type[$field]));
        $least  = explode('_', $min);
        if ($lenght < $least[1]) {
            array_push($this->errors, $this->lang->line('api_field') . $this->database_x_api_fields[$field] . $this->lang->line('api_field_must_least') . $least[1] . $this->lang->line('api_field_characters'));
        }
        return;
    }

    private function max($field, $max, $type)
    {
        $lenght  = strlen(trim($this->$type[$field]));
        $maximum = explode('_', $max);
        if ($lenght > $maximum[1]) {
            array_push($this->errors, $this->lang->line('api_field') . $this->database_x_api_fields[$field] . $this->lang->line('api_field_must_maximum') . $maximum[1] . $this->lang->line('api_field_characters'));
        }
        return;
    }

    private function unique($field, $unique, $type)
    {
        $sql    = "SELECT * FROM stores WHERE $field = ? ";
		if (!is_null($this->id)) {
			$sql .= " AND id != ".$this->id;
		}
        $query  = $this->db->query($sql,array($this->$type[$field]));
        $result = $query->result_array();

        if ($result) {
            array_push($this->errors, $this->lang->line('api_existing') . $this->database_x_api_fields[$field] . $this->lang->line('api_choose_new') . $this->database_x_api_fields[$field]);
        }
        return;
    }

    private function check($field, $check, $type)
    {
        $verifications = explode('_', $check);
        $verify = $verifications[1];
        $this->$verify($field, $this->$type[$field], $type);
        $pause = 2;
    }

	private function associatetype($field, $associate, $type)
    {
    	if (is_null($this->$type[$field]) || $this->$type[$field]=='') { // é opcional
    		return ;
    	} 
        $valids = [1,2,3,4,5,6];
        if (!in_array($associate, $valids)) {
            array_push($this->errors, $this->lang->line('api_valid_associate_type') . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function uf($field, $uf)
    {
        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 
                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 
                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
        if (!in_array($uf, $ufs)) {
            array_push($this->errors, $this->lang->line('api_valid_uf') . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function cnpj($field, $cnpj)
    {
        //Etapa 1: Cria um array com apenas os digitos numéricos, isso permite receber o cnpj em diferentes formatos como "00.000.000/0000-00", "00000000000000", "00 000 000 0000 00" etc...
		$j=0;
		$num = array();
		for($i=0; $i<(strlen($cnpj)); $i++)
			{
				if(is_numeric($cnpj[$i]))
					{
						$num[$j]=$cnpj[$i];
						$j++;
					}
			}
		//Etapa 2: Conta os dígitos, um Cnpj válido possui 14 dígitos numéricos.
		if(count($num)!=14)
			{
				$isCnpjValid=false;
			}
		//Etapa 3: O número 00000000000 embora não seja um cnpj real resultaria um cnpj válido após o calculo dos dígitos verificares e por isso precisa ser filtradas nesta etapa.
		elseif ($num[0]==0 && $num[1]==0 && $num[2]==0 && $num[3]==0 && $num[4]==0 && $num[5]==0 && $num[6]==0 && $num[7]==0 && $num[8]==0 && $num[9]==0 && $num[10]==0 && $num[11]==0)
			{
				$isCnpjValid=false;
			}
		//Etapa 4: Calcula e compara o primeiro dígito verificador.
		else
			{
				$j=5;
				for($i=0; $i<4; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);
				$j=9;
				for($i=4; $i<12; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);	
				$resto = $soma%11;			
				if($resto<2)
					{
						$dg=0;
					}
				else
					{
						$dg=11-$resto;
					}
				if($dg!=$num[12])
					{
						$isCnpjValid=false;
					} 
			}
		//Etapa 5: Calcula e compara o segundo dígito verificador.
		if(!isset($isCnpjValid))
			{
				$j=6;
				for($i=0; $i<5; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);
				$j=9;
				for($i=5; $i<13; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);	
				$resto = $soma%11;			
				if($resto<2)
					{
						$dg=0;
					}
				else
					{
						$dg=11-$resto;
					}
				if($dg!=$num[13])
					{
						$isCnpjValid=false;
					}
				else
					{
						$isCnpjValid=true;
					}
			}
		//Trecho usado para depurar erros.
		/*
		if($isCnpjValid==true)
			{
				echo "<p><font color="GREEN">Cnpj é Válido</font></p>";
			}
		if($isCnpjValid==false)
			{
				echo "<p><font color="RED">Cnpj Inválido</font></p>";
			}
		*/
		//Etapa 6: Retorna o Resultado em um valor booleano.
        $isCnpjValid;

        if (!$isCnpjValid) {
            array_push($this->errors, $this->lang->line('api_valid_cnpj') . $this->database_x_api_fields[$field] ."");
        }
		return;
    }

    private function ie($field, $ie, $type)
    {
        $valid = ValidatesIE::check($ie, $this->$type['addr_uf']);
        if (!$valid) {
            array_push($this->errors, $this->lang->line('api_valid_ie') . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function rg($field, $ie, $type)
    {
        return;
    }

    private function company($field, $company)
    {
        if (!$company) {
            return;
        }
        
        $sql    = "SELECT * FROM company WHERE id = ? AND provider_id = ?";
        $query  = $this->db->query($sql, array($company,$this->header['x-provider-key']));
        $result = $query->result_array();

        if (!$result) {
            array_push($this->errors, $this->lang->line('api_company_not_exist'));
        }
        return;
    }

    private function cpf($field, $cpf)
    {
        $valid = true;
        // Extrai somente os números
	    $cpf = preg_replace( '/[^0-9]/is', '', $cpf);
	     
	    // Verifica se foi informado todos os digitos corretamente
	    if (strlen($cpf) != 11) {
	        $valid = false;
	    }
	
	    // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
	    if (preg_match('/(\d)\1{10}/', $cpf)) {
	        $valid = false;
	    }
	
	    // Faz o calculo para validar o CPF
	    for ($t = 9; $t < 11; $t++) {
	        for ($d = 0, $c = 0; $c < $t; $c++) {
	            $d += $cpf[$c] * (($t + 1) - $c);
	        }
	        $d = ((10 * $d) % 11) % 10;
	        if ($cpf[$c] != $d) {
	            $valid = false;
	        }
	    }
        
        if (!$valid) {
            array_push($this->errors, $this->lang->line('api_valid_cpf') . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function bank($field, $bank)
    {
    	if (($this->bank_is_optional==1) && (is_null($bank) || $bank=='')) {
    		return; 
    	}
        $banks = $this->model_banks->getBankNames();
        $banks = array_map('mb_strtoupper', $banks);
        if (!in_array(mb_strtoupper($bank), $banks)) {
            array_push($this->errors, $this->lang->line('api_valid_value_field') . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function account($field, $account)
    {
    	if (($this->bank_is_optional==1) && (is_null($account) || $account=='')) {
    		return;
    	} 
        $accounts = ['CONTA CORRENTE', 'CONTA POUPANÇA'];
        if (!in_array(mb_strtoupper($account), $accounts)) {
            array_push($this->errors, $this->lang->line('api_valid_value_field') . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function equal($field, $equals, $type)
    {
        $lenght = strlen(trim($this->$type[$field]));
        $equal  = explode('_', $equals);
        if ($lenght != $equal[1]) {
            array_push($this->errors, $this->lang->line('api_field') . $this->database_x_api_fields[$field] . $this->lang->line('api_field_must_char') . $equal[1] . $this->lang->line('api_field_characters'));
        }
        return;
    }

    private function date($field, $date, $type)
    {
        $array_date = explode('-', $this->$type[$field]);
        $is_date    = false;

        if (count($array_date) == 3) {
            $is_date = checkdate($array_date[1], $array_date[2], $array_date[0]);
        }

        if (!$is_date) {
            array_push($this->errors, $this->lang->line('api_valid_date_field') . $this->database_x_api_fields[$field] . "");
        }

        return;
    }

    public function createTokenAPI($cod_store, $cod_company)
    {
        $key = get_instance()->config->config['encryption_key'];

        $payload = array(
            "cod_store" => $cod_store,
            "cod_company" => $cod_company
        );

        return $this->jwt->encode($payload, $key);
    }

    private function getCatalogs($storeId)
    {
        $response = [];

        $sql = "SELECT * FROM catalogs_stores cs 
                INNER JOIN catalogs c ON c.id = cs.catalog_id 
                WHERE cs.store_id = ? ";
        $query = $this->db->query($sql, array($storeId));
        $catalogs = $query->result_array();

        foreach ($catalogs as $catalog) {
            $data = [
                'catalog_id' => $catalog['catalog_id'],
                'name' => $catalog['name'],
                'description' => $catalog['description']
            ];
            array_push($response, $data);
        }

        return $response;
    }

    private function validateCatalogs($store)
    {
        if (!isset($store->catalogs)) {
            array_push($this->errors, $this->lang->line('api_provide_catalog'));
            return false;
        }
        
        if (!$store->catalogs) {
            array_push($this->errors, $this->lang->line('api_provide_catalog'));
            return false;
        }

		if (!is_array($store->catalogs)) {
			array_push($this->errors, $this->lang->line('api_catalog_array'));
            return false;
		}
        $catalogsId = [];

        foreach ($store->catalogs as $catalog) {
            $sql = "SELECT id FROM catalogs WHERE id = ? OR name = ? ";
            $query = $this->db->query($sql, array($catalog, $catalog));
            $catalogId = $query->result_array();

            if (!$catalogId) {
                array_push($this->errors, $this->lang->line('api_provide_catalog'));
                return false;
            }

            array_push($catalogsId, $catalogId[0]['id']);
        }

        $notDuplicatedCatalogs = array_unique($catalogsId);

        return $notDuplicatedCatalogs;
    }
	
	function formatCnpjCpf($value)
	{
		$CPF_LENGTH = 11;
		$cnpj_cpf = preg_replace("/\D/", '', $value);
		  
		if (strlen($cnpj_cpf) === $CPF_LENGTH) {
		    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
		} 
		  
		return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
	}

    /**
     * @param   object      $store  Dados de criação da loja.
     * @return  void
     * @throws  Exception
     */
    private function setLogisticForCreate(object $store)
    {
        if (!property_exists($store, 'logistic')) {
            return;
        }

        if (empty($store->logistic)) {
            throw new Exception($this->lang->line('api_logistics_not_informed'));
        }

        if (property_exists($store, 'logistic_module')) {
            if (!in_array($store->logistic_module, array(true, false, "true", "false"))) {
                throw new Exception($this->lang->line('api_misinformed_logistics'));
            }

            if ($store->logistic_module == true) {
                $codeIntegration = null;

                try{
                    foreach ($this->integrationLogistics->getAllIntegration() ?? [] as $logistic) {
                        if (strtolower($logistic['name']) == strtolower($store->logistic)) {
                            $codeIntegration = $logistic['id'] ?? $logistic['name'];
                            break;
                        }
                    }
                }catch (Throwable $e) {

                }

                if ($codeIntegration === null) {
                    throw new Exception($this->lang->line('api_logistics') . $store->logistic . $this->lang->line('api_not_found'));
                }

                return;
            }
        }

        switch ($store->logistic) {
            case 'sgpweb':
                // SGP quando não é módulo de frete, não precisa definir nada.
                return;
            case 'sequoia':
                $this->insert['freight_seller_type'] = 8;
                break;
            default:
                throw new Exception($this->lang->line('api_logistics'). $store->logistic . $this->lang->line('api_unmapped'));
        }

        $this->insert['freight_seller'] = 1;
    }
}

/*

EXEMPLO DE CONTEÚDO DO HEADER

accept         = application/json;charset=UTF-8
content-type   = application/json
x-provider-key = 10
x-api-key      = eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwcm92aWRlcl9pZCI6IjEwIiwiZW1haWwiOiJyZXNwb25zYXZlbEB0ZXN0ZS5jb20ifQ.HiGuFtheMBD_6MR6d2cpawwF24OeY-5zIxDeI-WHmzU
x-email        = responsavel@teste.com

EXEMPLO DE COMO DEVE SER O PAYLOAD DE CRIAÇÃO

{
	"store": {
		"store_name": "Loja Teste api 100820200959",
		"corporate_name": "Razão Social da Loja",
		"company_id": 10,
		"service_charge": 12,
		"cnpj": "10459818000120",
		"state_reg": "942908783",
		"tel1": 48912345678,
		"tel2": null,
        "onboarding_date": "1985-12-01",
		"collection_address": {
			"address": "Rua Santos Saraiva",
			"number": "23",
			"complement": null,
			"neighborhood": "Coqueiros",
			"city": "Florianópolis",
			"state": "SC",
			"country": "BR",
			"zipcode": 20751190
		}, 
		"business_address": {
			"b_address": "Rua Santos Saraiva",
			"b_number": "23",
			"b_complement": null,
			"b_neighborhood": "Coqueiros",
			"b_city": "Florianópolis",
			"b_state": "SC",
			"b_country": "BR",
			"b_zipcode": 20751190
		}, 
		"responsible": {
			"name": "Nome do Responsável",
			"cpf": "35412631053",
			"email": "teste@teste.com",
			"bank": "Itaú",
			"agency": 1234,
			"account_type": "Conta Corrente",
			"account": 12345
		},
        "handover": {
            "integration": "Qual integração",
            "billing_expectation": "Expectativa de faturamento no Conecta Lá",
            "operation": "Fale um pouco sobre a operação",
            "products": "Qtde de produtos, como será cadastrado e principais categorias",
            "up_and_fature": "Como irá faturar e subir os produtos"
        }
	}
}

*/
