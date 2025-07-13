<?php

// Esta API foi adaptada para uso exclusivo da Shopify
// por não fornecer todas as informações necessárias 
// para criação de uma empresa. 
// Dessa forma, as validações de determinados campos
// foram retiradas e o cadastro será feito de forma 
// incompleta. 

require APPPATH . "controllers/Api/V1/API.php";

class Stores extends API
{
    private const NO_CONTENT        = 'No content was found for the parameters entered.';
    private const NON_NUMERIC       = 'store_id must have a numeric value.';
    private const STORE_CREATED     = 'Store created';
    private const STORE_UPDATED     = 'Updated data';
    private const STORE_ACTIVE      = 1;
    private const STORE_INCOMPLETE  = 6;
    private const FR_REGISTER       = 6;
    private const STORE_NOT_FOUND   = 'Store not found';
	private const COMPANY_NOT_FOUND = 'Company not found';

    private $filters;
    private $search;
    private $insert;
    private $header;
    private $errors = [];
    private $update = [];
	
	private $bank_is_optional = false;
	private $id = null;

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
        'raz_social'            => 'corporate_name',
        'inscricao_estadual'    => 'state_reg',
        'service_charge_value'  => 'service_charge',
     	'service_charge_freight_value'  => 'service_charge_freight',
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
        // 'CNPJ'                    => 'required_|check_cnpj|unique_CNPJ',
        // 'inscricao_estadual'      => 'required_|check_ie|unique_inscricao_estadual',
        'responsible_name'        => 'required_',
        'responsible_email'       => 'required_|unique_responsible_email',
        // 'responsible_cpf'         => 'required_|numeric_|check_cpf',
        // 'bank'                    => 'required_|check_bank',
        // 'agency'                  => 'required_|numeric_',
        // 'account_type'            => 'required_|check_account',
        // 'account'                 => 'required_|numeric_',
        'service_charge_value'    => 'required_|numeric_',
        // 'service_charge_freight_value'    => 'numericoptional_',
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
		
		$this->bank_is_optional = $this->model_settings->getStatusbyName('store_optional_bank_details');
		
	}

    public function index_get()
    {
//        if (!$this->app_authorized)
//            return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->header = getallheaders();
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->search = $this->input->get();

        if (!$this->mountTheFilters()) {
            return $this->response(array('success' => false, 'result' => self::NON_NUMERIC), REST_Controller::HTTP_BAD_REQUEST);
        }

        $stores = $this->responseFormat($this->getStores());

        if (!$stores) {
            return $this->response(array('success' => true, 'result' => self::NO_CONTENT), REST_Controller::HTTP_OK);
        }
        
        return $this->response(array('success' => true, 'result' => $stores), REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
//        if (!$this->app_authorized)
//            return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->header = getallheaders();
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $data   = json_decode(file_get_contents('php://input'));
		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => 'Invalid json format'), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result = $this->insert($data);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => ['id' => $result, 'message' => self::STORE_CREATED]), REST_Controller::HTTP_CREATED);
    }

    public function index_put($id = null)
    {
//        if (!$this->app_authorized)
//            return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->header = getallheaders();
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
		if (is_null($id)) {
			return $this->response(array('success' => false, 'errors' => 'id must be supplied'), REST_Controller::HTTP_BAD_REQUEST);
		}
		
        $data   = json_decode(file_get_contents('php://input'));
		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => 'Invalid json format'), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result = $this->update($data, $id);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => self::STORE_UPDATED), REST_Controller::HTTP_OK);
    }

    private function mountTheFilters()
    {
        $this->filters = " WHERE stores.provider_id = " . $this->header['x-provider-key'];
        if (isset($this->search['store_id'])) {
            if (!is_numeric($this->search['store_id'])) {
                return false;
            } else {
                $this->filters .= " AND stores.id = '" . $this->search['store_id'] . "' ";
            }
        }
        if (isset($this->search['store_name'])) $this->filters   .= " AND stores.name LIKE '%" . $this->search['store_name'] . "%' ";
        if (isset($this->search['company_name'])) $this->filters .= " AND company.name LIKE '%" . $this->search['company_name'] . "%' ";

        // if ($this->filters) {
        //     $this->filters = substr_replace($this->filters, 'WHERE', 0, 4);
        // } else {
        //     $this->filters = '';
        // }

        return true;
    }

    private function getStores()
    {
        $sql = "SELECT 
                    stores.*, 
                    stores.name as name, 
                    company.name as company 
                FROM stores 
                LEFT JOIN company ON company.id = stores.company_id " . $this->filters;

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    private function responseFormat($stores)
    {
        $response = [];

        foreach ($stores as $store) {
            if ($store['active'] == 1) { $status = $this->lang->line('application_active'); }
            elseif ($store['active'] == 2) { $status = $this->lang->line('application_inactive'); }
            elseif ($store['active'] == 3) { $status = $this->lang->line('application_in_negociation'); }
            elseif ($store['active'] == 4) { $status = $this->lang->line('application_billet'); }
            elseif ($store['active'] == 5) { $status = $this->lang->line('application_churn'); }
        
            $format = [
                'id'                         => $store['id'],
                'name'                       => $store['name'],
                'company'                    => $store['company'],
                'active'            	     => $store['active'],
                'active_description'   	     => $status,
                'description'                => $store['description'],
                'exchange_return_policy'     => $store['exchange_return_policy'],
                'delivery_policy' 			 => $store['delivery_policy'],
                'security_privacy_policy' 	 => $store['security_privacy_policy'],
                'erp_customer_supplier_code' => $store['erp_customer_supplier_code'],
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
                    'name'         => $store['responsible_name'],
                    'cpf'          => $store['responsible_cpf'],
                    'email'        => $store['responsible_email'],
                    'bank'         => $store['bank'],
                    'agency'       => $store['agency'],
                    'account_type' => $store['account_type'],
                    'account'      => $store['account'],
                ]
            ];
            array_push($response, $format);
        }
        
        return $response;
    }

    private function insert($data)
    {
    	if (!isset($data->store)) {
			array_push($this->errors, "Missing key 'store'" );
			return false;
		}
        $store              = $data->store;
        // $logistics          = $data->store->logistics;
        $responsible        = $data->store->responsible;
		
		$nivel = array('collection_address','business_address','responsible');
		foreach ($data->store as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && (!in_array($key, $nivel))) 
            	array_push($this->errors, "Parameter entered does not match in an insert field: " . $key);
        }

        if (isset($data->store->collection_address)) {
            $collection_address = $data->store->collection_address;
            foreach ($collection_address as $key => $value) {
                if (!array_search($key, $this->database_x_api_fields)) 
                    array_push($this->errors, "Parameter entered does not match in an insert field: " . $key);
            }
        }

        if (isset($data->store->business_address)) {
            $business_address = $data->store->business_address;
            foreach ($business_address as $key => $value) {
                if (!array_search($key, $this->database_x_api_fields)) 
                    array_push($this->errors, "Parameter entered does not match in an insert field: " . $key);
            }
        }
        
		foreach ($responsible as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, "Parameter entered does not match in an insert field: " . $key);
		}
		
		$this->id = null;
		
        $this->insert = [
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
            'service_charge_value'  => (isset($store->service_charge) ? $store->service_charge : ''),
            'service_charge_freight_value'  => (isset($store->service_charge_freight) ? $store->service_charge_freight : $store->service_charge),
            'active'                => self::STORE_INCOMPLETE,
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
			
        ];

        if (!$this->validateCreate()) {
            return false;
        }

        $result = $this->db->insert('stores', $this->insert);
        $id     = $this->db->insert_id();
        
        $store_token = $this->createTokenAPI($id, $store->company_id);

        $this->db->where('id', $id);
        $this->db->update('stores', ['token_api' => $store_token]);

        return $id;
    }

    private function update($data, $id)
    {
        $sql    = "SELECT * FROM stores WHERE provider_id = " . $this->header['x-provider-key'] . " AND id = ? ";
        $query  = $this->db->query($sql, array($id));
        $result = $query->result_array();
        
        if (!$result) {
            array_push($this->errors, self::STORE_NOT_FOUND);
            return;
        };
		
		$nivel = array('collection_address','business_address','responsible');
		foreach ($data->store as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && (!in_array($key, $nivel))) 
            	array_push($this->errors, "Parameter entered does not match in an update field: " . $key);
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
                default:
                    $key = array_search($store_key, $this->database_x_api_fields);
                    $this->update[$key] = $store_value;
                    break;
            }
        }

        if (!$this->validateUpdate()) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update('stores', $this->update);
        return true;
    }

    private function validateCreate()
    {
		if ($this->bank_is_optional==1) {
			$this->validations['bank'] = 'check_bank';
       		$this->validations['agency'] = 'numericoptional_';
        	$this->validations['account_type'] = 'check_account';
        	$this->validations['account'] = 'numericoptional_';
		}
		 
        foreach ($this->validations as $field => $validation) {
            $rules = explode('|', $validation);
            foreach ($rules as $rule) {
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
       		$this->validations['agency'] = 'numericoptional_';
        	$this->validations['account_type'] = 'check_account';
        	$this->validations['account'] = 'numericoptional_';
		}
		
        foreach ($this->validations as $field => $validation) {
            foreach ($this->update as $keyUpdate => $valueUpdate) {
                if ($field == $keyUpdate) {
                    if (in_array($field, $this->dontUpdate)) {
                        array_push($this->errors, "Updating field " . $this->database_x_api_fields[$field] ." is not allowed");
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
            array_push($this->errors, "Field " . $this->database_x_api_fields[$field] ." must be numeric");
        }
        return;
    }
	
    private function numeric($field, $numeric, $type)
    {
        $is_numeric = is_numeric($this->$type[$field]);
        if (!$is_numeric) {
            array_push($this->errors, "Field " . $this->database_x_api_fields[$field] ." must be numeric");
        }
        return;
    }

    private function required($field, $required, $type)
    {
        $empty = empty(trim($this->$type[$field]));
        if ($empty) {
            array_push($this->errors, "Field " . $this->database_x_api_fields[$field] ." is required");
        }
        return;
    }

    private function min($field, $min, $type)
    {
        $lenght = strlen(trim($this->$type[$field]));
        $least  = explode('_', $min);
        if ($lenght < $least[1]) {
            array_push($this->errors, "Field " . $this->database_x_api_fields[$field] ." must be at least $least[1] characters");
        }
        return;
    }

    private function max($field, $max, $type)
    {
        $lenght  = strlen(trim($this->$type[$field]));
        $maximum = explode('_', $max);
        if ($lenght > $maximum[1]) {
            array_push($this->errors, "Field " . $this->database_x_api_fields[$field] ." must be a maximum of $maximum[1] characters");
        }
        return;
    }

    private function unique($field, $unique, $type)
    {
        $sql    = "SELECT * FROM stores WHERE $field = '". $this->$type[$field] . "' ";
		if (!is_null($this->id)) {
			$sql .= " AND id != ".$this->id;
		}
        $query  = $this->db->query($sql);
        $result = $query->result_array();

        if ($result) {
            array_push($this->errors, "Existing " . $this->database_x_api_fields[$field] .", choose new " . $this->database_x_api_fields[$field]);
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

    private function uf($field, $uf)
    {
        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 
                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 
                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
        if (!in_array($uf, $ufs)) {
            array_push($this->errors, "Enter a valid UF for field " . $this->database_x_api_fields[$field] ."");
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
            array_push($this->errors, "Enter a valid CNPJ for field " . $this->database_x_api_fields[$field] ."");
        }
		return;
    }

    private function ie($field, $ie, $type)
    {
        $valid = ValidatesIE::check($ie, $this->$type['addr_uf']);
        if (!$valid) {
            array_push($this->errors, "Enter a valid IE for field " . $this->database_x_api_fields[$field] ."");
        }
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
            array_push($this->errors, "Informed company does not exist");
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
	            $d += $cpf{$c} * (($t + 1) - $c);
	        }
	        $d = ((10 * $d) % 11) % 10;
	        if ($cpf{$c} != $d) {
	            $valid = false;
	        }
	    }
        
        if (!$valid) {
            array_push($this->errors, "Enter a valid CPF for field " . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function bank($field, $bank)
    {
    	if (($this->bank_is_optional==1) && (is_null($bank) || $bank=='')) {
    		return; 
    	} 
        $banks = $this->model_banks->getBankNames();
        if (!in_array($bank, $banks)) {
            array_push($this->errors, "Enter a valid value for field " . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function account($field, $account)
    {
    	if (($this->bank_is_optional==1) && (is_null($account) || $account=='')) {
    		return;
    	} 
        $accounts = ['Conta Corrente', 'Conta Poupança'];
        if (!in_array($account, $accounts)) {
            array_push($this->errors, "Enter a valid value for field " . $this->database_x_api_fields[$field] ."");
        }
        return;
    }

    private function equal($field, $equals, $type)
    {
        $lenght = strlen(trim($this->$type[$field]));
        $equal  = explode('_', $equals);
        if ($lenght != $equal[1]) {
            array_push($this->errors, "Field " . $this->database_x_api_fields[$field] ." must be $equal[1] characters");
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
		"company_id": 46,
		"service_charge": 12,
		"tel1": 48912345678,
		"tel2": null,
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
			"address": "Rua Santos Saraiva",
			"number": "23",
			"complement": null,
			"neighborhood": "Coqueiros",
			"city": "Florianópolis",
			"state": "RJ",
			"country": "BR",
			"zipcode": 20751190
		}, 
		"responsible": {
			"name": "Nome do Responsável",
			"email": "teste1@teste.com"
		}
	}
}

*/