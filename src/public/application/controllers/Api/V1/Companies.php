<?php

require APPPATH . "controllers/Api/V1/API.php";

class Companies extends API
{
    private const PARENT_ID            = 1;

    private $filters;
    private $search;
    private $insert;
    private $header;
    private $errors = [];
    private $update = [];
	private $id = null;
	private $legal_personality = 'PJ';

    private $database_x_api_fields = [
        'name'                 => 'company_name',
        'raz_social'           => 'corporate_name',
        'gestor'               => 'manager',
        'pj_pf'                => 'legal_personality',
        'CNPJ'                 => 'cnpj',
        'IEST'                 => 'state_reg',
        'IMUN'                 => 'munic_reg',
        'email'                => 'email',
        'phone_1'              => 'phone1',
        'phone_2'              => 'phone2',
        'address'              => 'address',
        'addr_num'             => 'number',
        'addr_compl'           => 'complement',
        'addr_neigh'           => 'neighborhood',
        'addr_city'            => 'city',
        'addr_uf'              => 'state',
        'country'              => 'country',
        'zipcode'              => 'zipcode',
        'service_charge_value' => 'service_charge',
        'associate_type'       => 'associate_type',
        'CPF'                  => 'cpf',
        'RG'                   => 'rg',
        'rg_expedition_agency' => 'expedition_agency',
        'rg_expedition_date'   => 'expedition_date',
        'affiliation'          => 'affiliation',
        'birth_date'           => 'birth_date',
        'bank'                 => 'bank',
        'agency'               => 'agency',
        'account_type'         => 'account_type',
        'account'              => 'account',
        'responsible_finan_name'  => 'name_financial',
        'responsible_finan_email' => 'email_financial',
        'responsible_finan_tell'  => 'phone_financial',
        'responsible_ti_name'     => 'name_ti',
        'responsible_ti_email'    => 'email_ti',
        'responsible_ti_tell'     => 'phone_ti',
        'responsible_sac_name'    => 'name_sac',
        'responsible_sac_email'   => 'email_sac',
        'responsible_sac_tell'    => 'phone_sac'
    ];

    private $common_validations = [
        'name'                 => 'required_',
        'associate_type'       => 'required_|numeric_|associate_',
        'bank'                 => 'required_|check_bank',
        'agency'               => 'required_|numeric_',
        'account_type'         => 'required_|check_account',
        'account'              => 'required_|numeric_',
        'email'                => 'required_',
        'address'              => 'required_',
        'addr_num'             => 'required_',
        'addr_neigh'           => 'required_',
        'addr_city'            => 'required_',
        'addr_uf'              => 'required_|check_uf|equal_2',
        'country'              => 'required_|equal_2',
        'zipcode'              => 'required_|numeric_|equal_8',
        'service_charge_value' => 'required_|numeric_',
        'phone_1'              => 'required_|numeric_|min_10|max_11',
        'phone_2'              => 'required_|numeric_|min_10|max_11'
    ];

    private $pj_validations = [
        'CNPJ'                 => 'required_|check_cnpj|unique_CNPJ',
        'IEST'                 => 'required_',
        'IMUN'                 => 'required_',
        'raz_social'           => 'required_',
        'gestor'               => 'required_'
    ];

    private $pf_validations = [
        'CPF'                  => 'required_|check_cpf|unique_CPF',
        'RG'                   => 'required_',
        'rg_expedition_agency' => 'required_',
        'rg_expedition_date'   => 'required_|date_',
        'affiliation'          => 'required_',
        'birth_date'           => 'required_|date_'
    ];

    private $dontUpdate = [
        'CNPJ',
        'IEST'
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

        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->search = $this->input->get();

        if (!$this->mountTheFilters()) {
            return $this->response(array('success' => false, 'result' => $this->lang->line('api_company_id_numeric')), REST_Controller::HTTP_BAD_REQUEST);
        }

        $companies = $this->responseFormat($this->getCompanies());

        if (!$companies) {
            return $this->response(array('success' => true, 'result' => $this->lang->line('api_no_content_found')), REST_Controller::HTTP_OK);
        }
        
        return $this->response(array('success' => true, 'result' => $companies), REST_Controller::HTTP_OK);
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

        //$data   = json_decode(file_get_contents('php://input'));
        $data   = $this->inputClean();

		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => $this->lang->line('api_invalid_json_format')), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result = $this->insert($data);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => ['id' => $result, 'message' => $this->lang->line('api_company_created')]), REST_Controller::HTTP_CREATED);
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

        if (!$id) {
            array_push($this->errors, $this->lang->line('api_company_not_informed'));
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        $data   = json_decode(file_get_contents('php://input'));
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
            $this->filters = " WHERE company.id IS NOT NULL ";
        } else {
            $this->filters = " WHERE company.provider_id = " . $this->db->escape($this->header['x-provider-key']);
        }
        
        if (isset($this->search['company_id'])) {
            if (!is_numeric($this->search['company_id'])) {
                return false;
            } else {
                $this->filters .= " AND company.id = " . $this->db->escape($this->search['company_id']) . " ";
            }
        }
        if (isset($this->search['company_name'])) $this->filters .= " AND company.name LIKE '%" . $this->db->escape_like_str($this->search['company_name']) . "%' ";
        
		if (isset($this->search['cnpj'])) {
			$cnpj = preg_replace( '/[^0-9]/is', '', $this->search['cnpj']);
			$cnpj_format = $this->formatCnpjCpf($cnpj);
			$this->filters .= " AND (company.CNPJ = " . $this->db->escape($cnpj) . " OR company.CNPJ = " .  $this->db->escape($cnpj_format) . ")";
		}

        return true;
    }

    private function getCompanies()
    {
        $sql = "SELECT *
                    -- id, 
                    -- name, 
                    -- address, 
                    -- addr_num, 
                    -- addr_compl, 
                    -- addr_neigh, 
                    -- addr_city, 
                    -- addr_uf, 
                    -- country, 
                    -- zipcode 
                FROM company " . $this->filters;

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    private function responseFormat($companies)
    {
        $response = [];
        
        foreach ($companies as $company) {
            $format = [
                'id'                => $company['id'],
                'name'              => $company['name'],
                'legal_personality' => $company['pj_pf'],
                'service_charge'    => $company['service_charge_value'],
                'associate_type'    => $company['associate_type'],
                'email'             => $company['email'],
                'phone1'            => $company['phone_1'],
                'phone2'            => $company['phone_2'],
                'pj'                => [
                    'corporate_name'    => ($company['pj_pf'] == "PJ" ? $company['raz_social'] : null),
                    'cnpj'              => ($company['pj_pf'] == "PJ" ? $company['CNPJ'] : null),
                    'state_reg'         => ($company['pj_pf'] == "PJ" ? $company['IEST'] : null),
                    'munic_reg'         => ($company['pj_pf'] == "PJ" ? $company['IMUN'] : null),
                    'manager'           => ($company['pj_pf'] == "PJ" ? $company['gestor'] : null)
                ],
                'pf'                => [
                    'cpf'               => ($company['pj_pf'] == "PF" ? $company['CPF'] : null),
                    'rg'                => ($company['pj_pf'] == "PF" ? $company['RG'] : null),
                    'expedition_agency' => ($company['pj_pf'] == "PF" ? $company['rg_expedition_agency'] : null),
                    'expedition_date'   => ($company['pj_pf'] == "PF" ? date('Y-m-d', strtotime($company['rg_expedition_date'])) : null),
                    'birth_date'        => ($company['pj_pf'] == "PF" ? date('Y-m-d', strtotime($company['birth_date'])) : null),
                    'affiliation'       => ($company['pj_pf'] == "PF" ? $company['affiliation'] : null)
                ],
                'address'           => [
                    'address'           => $company['address'],
                    'number'            => $company['addr_num'],
                    'complement'        => $company['addr_compl'],
                    'neighborhood'      => $company['addr_neigh'],
                    'city'              => $company['addr_city'],
                    'state'             => $company['addr_uf'],
                    'country'           => $company['country'],
                    'zipcode'           => $company['zipcode'],
                ],
                'bank_data'         => [
                    'bank'              => $company['bank'],
                    'agency'            => $company['agency'],
                    'account_type'      => $company['account_type'],
                    'account'           => $company['account']
                ],
                'contacts'         => [
                    'name_financial'    => $company['responsible_finan_name'],
                    'email_financial'   => $company['responsible_finan_email'],
                    'phone_financial'   => $company['responsible_finan_tell'],
                    'name_ti'           => $company['responsible_ti_name'],
                    'email_ti'          => $company['responsible_ti_email'],
                    'phone_ti'          => $company['responsible_ti_tell'],
                    'name_sac'          => $company['responsible_sac_name'],
                    'email_sac'         => $company['responsible_sac_email'],
                    'phone_sac'         => $company['responsible_sac_tell']
                ]
            ];
			if ($company['associate_type'] ==0) {
				unset($format['bank_data']);
				unset($format['service_charge']);
			}
			if ( $company['pj_pf'] == 'PJ') {
				unset($format['pf']);
			}
			else {
				unset($format['pj']);
			}
            array_push($response, $format);
        }
        
        return $response;
    }

    private function insert($data)
    {
        
		if (!isset($data->company)) {
			array_push($this->errors, $this->lang->line('api_missing_key_company'));
			return false;
		}
		$company   = $data->company;
		$address = array();
		if (isset($data->company->address)) {
       		$address   = $this->db->escape($data->company->address);
		} 
		$pj = array();
		if (isset($data->company->pj)) {
        	$pj        = $this->db->escape($data->company->pj);
		}
		$pf = array();
		if (isset($data->company->pf)) {
			$pf        = $this->db->escape($data->company->pf);
		}
		$bank_data=array();
        if (isset($data->company->bank_data)) {
       		$bank_data = $this->db->escape($data->company->bank_data);
		}
        $contacts=array();
        if (isset($data->company->contacts)) {
       		$contacts = $this->db->escape($data->company->contacts);
		}
		
		if (isset($company->associate_type)) {
			if ($company->associate_type == 0) { // MATRIZ 
				unset($this->database_x_api_fields['service_charge_value']);
				unset($this->database_x_api_fields['bank']);
				unset($this->database_x_api_fields['agency']);
				unset($this->database_x_api_fields['account_type']);
				unset($this->database_x_api_fields['account']);
				unset($this->common_validations['service_charge_value']);
				unset($this->common_validations['bank']);
				unset($this->common_validations['agency']);
				unset($this->common_validations['account_type']);
				unset($this->common_validations['account']);
			}
		} 
		if (isset($company->legal_personality)) {
			$this->legal_personality = $this->db->escape($company->legal_personality);
			if ($company->legal_personality == 'PF') {
				foreach ($pf as $key => $value) {
					if (!array_search($key, $this->database_x_api_fields)) 
		            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
				}
			}
			else {
				foreach ($pj as $key => $value) {
					if (!array_search($key, $this->database_x_api_fields)) 
		            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
				}
			}
		} 
		
		$nivel = array('address','pj','pf','bank_data','contacts');
		foreach ($data->company as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && (!in_array($key, $nivel))) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
		foreach ($address as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}		
		foreach ($bank_data as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
        foreach ($contacts as $key => $value) {
			if (!array_search($key, $this->database_x_api_fields)) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
		
		$this->id = null;

        if (isset($bank_data->bank)) {
            $banks = $this->model_banks->getBankNames();
            foreach ($banks as $bank)
                if (mb_strtoupper($bank_data->bank) == mb_strtoupper($bank))
                    $bank_data->bank = $bank;
        }

        if (isset($bank_data->account_type)) {
            $accounts = ['Conta Corrente', 'Conta Poupança'];
            foreach ($accounts as $account)
                if (mb_strtoupper($bank_data->account_type) == mb_strtoupper($account))
                    $bank_data->account_type = $account;
        }
	
        $this->insert = [
            'name'                 => (isset($company->company_name) ? $company->company_name : ''),
            'raz_social'           => (isset($pj->corporate_name) ? $pj->corporate_name : ''),
            'service_charge_value' => (isset($company->service_charge) ? $company->service_charge : ''),
            'currency'             => 'BRL',
            'associate_type'       => (isset($company->associate_type) ? (int)$company->associate_type : ''),
            'gestor'               => (isset($pj->manager) ? $pj->manager : ''),
            'pj_pf'                => (isset($company->legal_personality) ? $company->legal_personality : ''),
            'CNPJ'                 => (isset($pj->cnpj) ? $pj->cnpj : ''),
            'CPF'                  => (isset($pf->cpf) ? $pf->cpf : ''),
            'RG'                   => (isset($pf->rg) ? $pf->rg : ''),
            'rg_expedition_agency' => (isset($pf->expedition_agency) ? $pf->expedition_agency : ''),
            'rg_expedition_date'   => (isset($pf->expedition_date) ? $pf->expedition_date : ''),
            'affiliation'          => (isset($pf->affiliation) ? $pf->affiliation : ''),
            'birth_date'           => (isset($pf->birth_date) ? $pf->birth_date : ''),
            'IEST'                 => (isset($pj->state_reg) ? $pj->state_reg : ''),
            'IMUN'                 => (isset($pj->munic_reg) ? $pj->munic_reg : ''),
            'email'                => (isset($company->email) ? $company->email : ''),
            'phone_1'              => (isset($company->phone1) ? $company->phone1 : ''),
            'phone_2'              => (isset($company->phone2) ? $company->phone2 : ''),
            'prefix'               => strtoupper(substr(md5(uniqid(mt_rand(99999,99999999), true)), 0, 5)),
            'address'              => (isset($address->address) ? $address->address : ''),
            'addr_num'             => (isset($address->number) ? $address->number : ''),
            'addr_compl'           => (isset($address->complement) ? $address->complement : ''),
            'addr_neigh'           => (isset($address->neighborhood) ? $address->neighborhood : ''),
            'addr_city'            => (isset($address->city) ? $address->city : ''),
            'addr_uf'              => (isset($address->state) ? $address->state : ''),
            'country'              => (isset($address->country) ? $address->country : ''),
            'zipcode'              => (isset($address->zipcode) ? $address->zipcode : ''),
            'parent_id'            => self::PARENT_ID,
            'bank'                 => (isset($bank_data->bank) ? $bank_data->bank : ''),
            'agency'               => (isset($bank_data->agency) ? $bank_data->agency : ''),
            'account_type'         => (isset($bank_data->account_type) ? $bank_data->account_type : ''),
            'account'              => (isset($bank_data->account) ? $bank_data->account : ''),
            'responsible_finan_name'  => (isset($contacts->name_financial) ? $contacts->name_financial : ''),
            'responsible_finan_email' => (isset($contacts->email_financial) ? $contacts->email_financial : ''),
            'responsible_finan_tell'  => (isset($contacts->phone_financial) ? $contacts->phone_financial : ''),
            'responsible_ti_name'     => (isset($contacts->name_ti) ? $contacts->name_ti : ''),
            'responsible_ti_email'    => (isset($contacts->email_ti) ? $contacts->email_ti : ''),
            'responsible_ti_tell'     => (isset($contacts->phone_ti) ? $contacts->phone_ti : ''),
            'responsible_sac_name'    => (isset($contacts->name_sac) ? $contacts->name_sac : ''),
            'responsible_sac_email'   => (isset($contacts->email_sac) ? $contacts->email_sac : ''),
            'responsible_sac_tell'    => (isset($contacts->phone_sac) ? $contacts->phone_sac : ''),
            'provider_id'             => $this->header['x-provider-key']
        ];

        if (!$this->validateCreate()) {
            return false;
        }

        $result = $this->db->insert('company', $this->insert);
        $id     = $this->db->insert_id();

        return $id;
    }

    private function update($data, $id)
    {
        $filter = " AND provider_id = ".$this->db->escape($this->header['x-provider-key']);
        if ($this->tokenMaster) {
         	$filter = '';
        }
        $sql    = "SELECT * FROM company WHERE id = ? ".$filter;
       // $sql    = "SELECT * FROM company WHERE provider_id = " . $this->header['x-provider-key'] . " AND id = ? ";
        $query  = $this->db->query($sql, array($id));
        $result = $query->result_array();
        
        if (!$result) {
            array_push($this->errors, $this->lang->line('api_company_not_found'));
            return;
        };
		
        if (!isset($data->company)) {
			array_push($this->errors, "Missing key 'company'" );
			return false;
		}

		$this->id = $id;
		if (isset($company->legal_personality)) {
			$this->legal_personality = $company->legal_personality;
		}
		$nivel = array('address','pj','pf','bank_data','contacts');
		foreach ($data->company as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && (!in_array($key, $nivel))) 
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_update') . $key);
		}
		
        foreach ($data->company as $company_key => $company_value) {
            switch ($company_key) {
                case 'pj':

                    foreach ($data->company->pj as $pj_key => $pj_value) {
                        $key = array_search($pj_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $pj_value) : '';
                    }
                    break;
                case 'pf':
                    foreach ($data->company->pf as $pf_key => $pf_value) {
                        $key = array_search($pf_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $pf_value) : '';
                    }
                    break;
                case 'address':
                    foreach ($data->company->address as $address_key => $address_value) {
                        $key = array_search($address_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $address_value) : '';
                    }
                    break;
                case 'bank_data':
                    foreach ($data->company->bank_data as $bank_data_key => $bank_data_value) {
                        $key = array_search($bank_data_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $bank_data_value) : '';
                    }
                    break;
                case 'contacts':
                    foreach ($data->company->contacts as $contacts_key => $contacts_value) {
                        $key = array_search($contacts_key, $this->database_x_api_fields);
                        $key ? ($this->update[$key] = $contacts_value) : '';
                    }
                    break;
                default:
                    $key = array_search($company_key, $this->database_x_api_fields);
                    $this->update[$key] = $company_value;
                    break;
            }
        }

        if (!$this->validateUpdate($result[0]['pj_pf'])) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update('company', $this->update);
        return true;
    }

    private function validateCreate()
    {       

        $validations = $this->common_validations;
        if ($this->insert['pj_pf'] == 'PJ') {
            $validations = array_merge($this->common_validations, $this->pj_validations);
        } elseif ($this->insert['pj_pf'] == 'PF') {
            $validations = array_merge($this->common_validations, $this->pf_validations);
        }

        if (strtolower($this->insert['IEST']) != 'isento') {
            $validations['IEST'] .= '|check_ie';
        }else{
            $this->insert['IEST'] = 0;
        }
        
        if (!empty($this->insert['responsible_finan_tell'])) $validations['responsible_finan_tell'] = 'numeric_|min_10|max_11';
        if (!empty($this->insert['responsible_ti_tell']))    $validations['responsible_ti_tell']    = 'numeric_|min_10|max_11';
        if (!empty($this->insert['responsible_sac_tell']))   $validations['responsible_sac_tell']   = 'numeric_|min_10|max_11';
        
        foreach ($validations as $field => $validation) {
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

    private function validateUpdate($pj_pf = 'PJ')
    {
        $validations = $this->common_validations;
        if (isset($this->update['pj_pf'])) {
            if ($this->update['pj_pf'] == 'PJ') {
                $validations = array_merge($this->common_validations, $this->pj_validations);
            } elseif ($this->update['pj_pf'] == 'PF') {
                $validations = array_merge($this->common_validations, $this->pf_validations);
            }
        } else {
            if ($pj_pf == 'PJ') {
                $validations = array_merge($this->common_validations, $this->pj_validations);
            } elseif ($pj_pf == 'PF') {
                $validations = array_merge($this->common_validations, $this->pf_validations);
            }
        }
        
        if (!empty($this->update['responsible_finan_tell'])) $validations['responsible_finan_tell'] = 'numeric_|min_10|max_11';
        if (!empty($this->update['responsible_ti_tell']))    $validations['responsible_ti_tell']    = 'numeric_|min_10|max_11';
        if (!empty($this->update['responsible_sac_tell']))   $validations['responsible_sac_tell']   = 'numeric_|min_10|max_11';

        foreach ($validations as $field => $validation) {
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
        if (empty(trim($this->$type[$field])) && $this->$type[$field] !== 0) {
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

    private function unique($field, $unique, $type)
    {
        $sql    = "SELECT * FROM company WHERE $field = ? ";
        if (!is_null($this->id)) {
			$sql .= " AND id != ".$this->id;
		}
        $query  = $this->db->query($sql, array($this->$type[$field]));
        $result = $query->result_array();

        if ($result) {
            array_push($this->errors, $this->lang->line('api_existing') . $this->database_x_api_fields[$field] . $this->lang->line('api_choose_new') . $field);
        }
        return;
    }

    private function check($field, $check, $type)
    {
        $verifications = explode('_', $check);
        $verify = $verifications[1];
        $this->$verify($field, $this->$type[$field]);
        $pause = 2;
    }

    private function uf($field, $uf)
    {
        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 
                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 
                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
        if (!in_array($uf, $ufs)) {
            array_push($this->errors, $this->lang->line('api_valid_uf') . $this->database_x_api_fields[$field] . "");
        }
        return;
    }

    private function associate($field, $assoc, $type)
    {
        $allowed = [0, 1, 2, 3, 4, 5];
		if ($this->legal_personality == "PF") {
			$allowed = [2, 3, 4];
		}
        if (!in_array(trim($this->$type[$field]), $allowed)) {
            array_push($this->errors, $this->lang->line('api_invalid_value_field') . $this->database_x_api_fields[$field] . "");
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
            array_push($this->errors, $this->lang->line('api_valid_cnpj') . $this->database_x_api_fields[$field] . "");
        }
		return;
    }

    private function ie($field, $ie)
    {
        $valid = ValidatesIE::check($ie, $this->insert['addr_uf']);
        if (!$valid) {
            array_push($this->errors, $this->lang->line('api_valid_ie') . $this->database_x_api_fields[$field] . "");
        }
        return;
    }

    private function cpf($field, $cpf)
    {
        $valid = true;

        if (!$cpf) {
            array_push($this->errors, $this->lang->line('api_valid_cpf') . $this->database_x_api_fields[$field] . "");
            return;
        }

        // Extrai somente os números
	    $cpf = preg_replace( '/[^0-9]/is', '', $cpf);
	     
	    // Verifica se foi informado todos os digitos corretamente
	    if (strlen($cpf) != 11) {
	        array_push($this->errors, $this->lang->line('api_valid_cpf') . $this->database_x_api_fields[$field] . "");
            return;
	    }
	
	    // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
	    if (preg_match('/(\d)\1{10}/', $cpf)) {
	         array_push($this->errors, $this->lang->line('api_valid_cpf') . $this->database_x_api_fields[$field] . "");
            return;
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
            array_push($this->errors, $this->lang->line('api_valid_cpf') . $this->database_x_api_fields[$field] . "");
        }
        return;
    }

    private function bank($field, $bank)
    {
        $banks = $this->model_banks->getBankNames();
        $banks = array_map('mb_strtoupper', $banks);
        if (!in_array(mb_strtoupper($bank), $banks)) {
            array_push($this->errors, $this->lang->line('api_valid_value_field') . $this->database_x_api_fields[$field] . "");
        }
        return;
    }

    private function account($field, $account)
    {
        $accounts = ['CONTA CORRENTE', 'CONTA POUPANÇA'];
        if (!in_array(mb_strtoupper($account), $accounts)) {
            array_push($this->errors, $this->lang->line('api_valid_value_field') . $this->database_x_api_fields[$field] . "");
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
	
	function formatCnpjCpf($value)
	{
		$CPF_LENGTH = 11;
		$cnpj_cpf = preg_replace("/\D/", '', $value);
		  
		if (strlen($cnpj_cpf) === $CPF_LENGTH) {
		    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
		} 
		  
		return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
	}
}

/*

EXEMPLO DE CONTEÚDO DO HEADER

accept         = application/json;charset=UTF-8
content-type   = application/json
x-provider-key = 10
x-api-key      = eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwcm92aWRlcl9pZCI6IjEwIiwiZW1haWwiOiJyZXNwb25zYXZlbEB0ZXN0ZS5jb20ifQ.HiGuFtheMBD_6MR6d2cpawwF24OeY-5zIxDeI-WHmzU
x-email        = responsavel@teste.com

EXEMPLO DE COMO DEVE SER O PAYLOAD DE CRIAÇÃO DE PJ

{
	"company":{
		"legal_personality": "PJ",
		"associate_type": 2,
		"service_charge": 12,
		"company_name": "Empresa PJ 090820202311",
		"email": "empresa@teste.com",
		"phone1": "48987654321",
		"phone2": "48912345678",
		"pj": {
			"corporate_name": "Razão Social da Empresa",
			"cnpj": "63990124000181",
			"state_reg": "334333520",
			"munic_reg": "3453453",
			"manager": "Nome do Gestor"
		},
		"pf": {
			"cpf": null,
			"rg": null,
			"expedition_agency": null,
			"expedition_date": null,
			"birth_date": null,
			"affiliation": null
			},
		"address": {
			"address": "Rua do teste",
			"number": "200",
			"complement": "",
			"neighborhood": "Coqueiros",
			"city": "Florianópolis",
			"state": "SC",
			"country": "BR",
			"zipcode": 20755190
		},
		"bank_data": {
			"bank": "Banco do Brasil",
		  "agency": 2342,
		  "account_type": "Conta Corrente",
		  "account": 234223
		},
		"contacts": {
            "name_financial": "Nome do Responsável Financeiro",
            "email_financial": "financeiro@teste.com",
            "phone_financial": "1165656565",
			"name_ti": "Nome do Responsável do departamento de TI",
            "email_ti": "ti@teste.com",
            "phone_ti": "1165656565",
			"name_sac": "Nome do Responsável do SAC",
            "email_sac": "sac@teste.com",
            "phone_sac": "1165656565"
        }
	}
}

EXEMPLO DE COMO DEVE SER O PAYLOAD DE CRIAÇÃO DE PF

{
	"company":{
		"legal_personality": "PF",
		"associate_type": 1,
		"service_charge": 13,
		"company_name": "Empresa PF 090820202349",
		"email": "empresa@teste.com",
		"phone1": "48987654321",
		"phone2": "48912345678",
		"pj": {
			"corporate_name": null,
			"cnpj": null,
			"state_reg": null,
			"munic_reg": null,
			"manager": null
		},
		"pf": {
			"cpf": "40446520071",
			"rg": 1346897,
			"expedition_agency": "IFP",
			"expedition_date": "2020-02-20",
			"birth_date": "1984-11-04",
			"affiliation": "Nome dos pais"
			},
		"address": {
			"address": "Rua do teste",
			"number": "200",
			"complement": "",
			"neighborhood": "Coqueiros",
			"city": "Florianópolis",
			"state": "SC",
			"country": "BR",
			"zipcode": 20755190
		},
		"bank_data": {
			"bank": "Banco do Brasil",
		  "agency": 2342,
		  "account_type": "Conta Corrente",
		  "account": 234223
		},
		"contacts": {
            "name_financial": "Nome do Responsável Financeiro",
            "email_financial": "financeiro@teste.com",
            "phone_financial": "1165656565",
			"name_ti": "Nome do Responsável do departamento de TI",
            "email_ti": "ti@teste.com",
            "phone_ti": "1165656565",
			"name_sac": "Nome do Responsável do SAC",
            "email_sac": "sac@teste.com",
            "phone_sac": "1165656565",   
        }
	}
}

*/