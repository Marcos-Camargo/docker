<?php

require APPPATH . "controllers/Api/V1/API.php";

class Users extends API
{
     const PARTNER_GROUP  = 11;
     const USER_ACTIVE    = 1;
     

    private $filters;
    private $search;
    private $insert;
    private $header;
    private $update = [];
    private $errors = [];
	private $id = null;
	private $group_id = null;

    private $database_x_api_fields = [
        'username'   => 'username',
        'email'      => 'email',
        'firstname'  => 'firstname',
        'lastname'   => 'lastname',
        'phone'      => 'phone',
        'company_id' => 'company_id',
        'store_id'   => 'store_id',
        'group_name' => 'group_name',
        'group_id'   => 'group_id',
        'legal_administrator'   => 'legal_administrator',
        'cpf'                   => 'cpf',
    ];

    private $validations = [
        'username'   => 'required_|min_5|max_40|unique_username',
        'email'      => 'required_|unique_email',
        'firstname'  => 'required_',
        'lastname'   => 'required_',
        'phone'      => 'required_|numeric_|min_10|max_11',
        'company_id' => 'required_|numeric_|check_company',
        'store_id'   => 'numeric_|check_store',
        'group_name' => 'check_group',
        'group_id'   => 'check_groupid',
    ];

    private $dontUpdate = [
        'store_id', 'company_id'
    ];
	
	public function __construct()
    {
        parent::__construct();

        $this->load->model('model_settings');
		$this->load->model('model_company');
		$this->load->model('model_users');
		$this->load->model('model_groups');
    }

    public function index_get()
    {
        // Obs.: O "if" abaixo foi retirado da aplicação (foi comentado) conforme também foi feito nas APIs de "stores" e "companies", 
        // de acordo com os commits 8e493efd4c8ed82732ab3e4cacb2c142d85064c3 e 8427d5bdfc9bfd5f845b4566ed46a2dba8382a14, dos dias
        // 18 e 19 de Janeiro, respectivamente.
        // https://github.com/ConectaLa/Fase1/commit/8e493efd4c8ed82732ab3e4cacb2c142d85064c3
        // https://github.com/ConectaLa/Fase1/commit/8427d5bdfc9bfd5f845b4566ed46a2dba8382a14

        // if (!$this->app_authorized)
        //     return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->search = $this->input->get();

        if (!$this->mountTheFilters()) {
            return $this->response(array('success' => false, 'result' => $this->lang->line('api_user_id_numeric')), REST_Controller::HTTP_BAD_REQUEST);
        }

        $users = $this->responseFormat($this->getUsers());

        if (!$users) {
            return $this->response(array('success' => true, 'result' => $this->lang->line('api_no_content_found')), REST_Controller::HTTP_OK);
        }
        
        return $this->response(array('success' => true, 'result' => $users), REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
        // Obs.: O "if" abaixo foi retirado da aplicação (foi comentado) conforme também foi feito nas APIs de "stores" e "companies", 
        // de acordo com os commits 8e493efd4c8ed82732ab3e4cacb2c142d85064c3 e 8427d5bdfc9bfd5f845b4566ed46a2dba8382a14, dos dias
        // 18 e 19 de Janeiro, respectivamente.
        // https://github.com/ConectaLa/Fase1/commit/8e493efd4c8ed82732ab3e4cacb2c142d85064c3
        // https://github.com/ConectaLa/Fase1/commit/8427d5bdfc9bfd5f845b4566ed46a2dba8382a14

        // if (!$this->app_authorized)
        //     return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $data      = json_decode(file_get_contents('php://input'));
		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => $this->lang->line('api_invalid_json_format')), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result    = $this->insert($data);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => ['data' => $result, 'message' => $this->lang->line('api_user_created')]), REST_Controller::HTTP_CREATED);
    }

    public function index_put($id = null)
    {
        // Obs.: O "if" abaixo foi retirado da aplicação (foi comentado) conforme também foi feito nas APIs de "stores" e "companies", 
        // de acordo com os commits 8e493efd4c8ed82732ab3e4cacb2c142d85064c3 e 8427d5bdfc9bfd5f845b4566ed46a2dba8382a14, dos dias
        // 18 e 19 de Janeiro, respectivamente.
        // https://github.com/ConectaLa/Fase1/commit/8e493efd4c8ed82732ab3e4cacb2c142d85064c3
        // https://github.com/ConectaLa/Fase1/commit/8427d5bdfc9bfd5f845b4566ed46a2dba8382a14
        
        // if (!$this->app_authorized)
        //     return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);
        $id  = xssClean($id);
        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
		if (is_null($id)) {
			return $this->response(array('success' => false, 'errors' => $this->lang->line('api_id_supplied')), REST_Controller::HTTP_BAD_REQUEST);
		}
        $data   = json_decode(file_get_contents('php://input'));
		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => $this->lang->line('api_invalid_json_format')), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result = $this->update($data, (int)$id);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => $this->lang->line('api_updated_data')), REST_Controller::HTTP_OK);
    }

    private function mountTheFilters()
    {
        if ($this->tokenMaster) {
            $this->filters = " WHERE users.id IS NOT NULL ";
        } else {
            $this->filters = " WHERE users.provider_id = " . (int) $this->header['x-provider-key'];
        }

        if (isset($this->search['user_id'])) {
            if (!is_numeric($this->search['user_id'])) {
                return false;
            } else {
                $this->filters .= " AND users.id = " . $this->db->escape($this->search['user_id']) . " ";
            }
        }
        if (isset($this->search['user_name'])) $this->filters    .= " AND users.username LIKE '%" . $this->db->escape_like_str($this->search['user_name']) . "%' ";
        if (isset($this->search['user_email'])) $this->filters   .= " AND users.email LIKE '%" . $this->db->escape_like_str($this->search['user_email']) . "%' ";
        if (isset($this->search['company_name'])) $this->filters .= " AND company.name LIKE '%" . $this->db->escape_like_str($this->search['company_name']) . "%' ";
        if (isset($this->search['store_name'])) $this->filters   .= " AND stores.name LIKE '%" . $this->db->escape_like_str($this->search['store_name']) . "%' ";
		if (isset($this->search['group_name'])) $this->filters   .= " AND groups.group_name LIKE '%" . $this->db->escape_like_str($this->search['group_name']) . "%' ";
 
        return true;
    }

    private function getUsers()
    {
        $sql = "SELECT 
                    users.id, 
                    users.username, 
                    users.email, 
                    users.company_id, 
                    users.firstname, 
                    users.lastname, 
                    users.phone,
                    users.legal_administrator,
                    users.cpf,  
                    users.active,
                    user_group.group_id, 
                    company.name AS company_name, 
                    users.store_id, 
                    stores.name AS store_name, 
                    groups.group_name AS group_name, 
                    groups.id AS group_id
                FROM users 
                LEFT JOIN company ON company.id = users.company_id 
                LEFT JOIN user_group ON user_group.user_id = users.id 
                LEFT JOIN `groups` ON user_group.group_id = groups.id
                LEFT JOIN stores ON stores.id = users.store_id " . $this->filters. " ORDER BY users.id";

        $query = $this->db->query($sql);
		
        return $query->result_array();
       
    }

    private function responseFormat($users)
    {
        $response = [];

        foreach ($users as $user) {

            $format = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'group_id'  => $user['group_id'],
                'group'     => $user['group_name'],
                'firstname' => $user['firstname'],
                'lastname'  => $user['lastname'],
                'phone'   	=> $user['phone'],
                'active'    => $user['active'],
                'legal_administrator' => $user['legal_administrator'],
                'cpf'       => ($user['cpf'] == null ? '' : $user['cpf']),
                'active'    => $user['active'],
                
                'company'  => [
                    'company_id'   => $user['company_id'],
                    'company_name' => $user['company_name'],
                ],
                'store'    => [
                    'store_id'     => $user['store_id'],
                    'store_name'   => ($user['store_id'] == '0' ? 'Todas as Lojas' : $user['store_name'])
                ]
            ];
            array_push($response, $format);
        }
        
        return $response;
    }

    private function insert($data)
    {
    	if (!isset($data->user)) {
			array_push($this->errors, "Missing key 'user'" );
			return false;
		}
        $user = $data->user;
        $time = time();
        $password = $this->random_pwd();
		
		foreach ($data->user as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && $key != 'send_welcome_email')  
            	array_push($this->errors, $this->lang->line('api_parameter_not_match_field_insert') . $key);
		}
		$legal_administrator = (isset($user->legal_administrator) ? $user->legal_administrator == "1" : 0);
        $cpf = null;
        if ($legal_administrator)  {
            $this->validations['cpf'] = 'required_|check_cpf';
            $cpf = (isset($user->cpf) ? preg_replace('/[^0-9]/is', '', $user->cpf) : null);
        }

		$this->id = null;
        $this->insert = [
            'username'              => (isset($user->username) ? $user->username : ''),
            'password'              => $this->password_hash($password),
            'email'                 => (isset($user->email) ? $user->email : ''),
            'firstname'             => (isset($user->firstname) ? $user->firstname : ''),
            'lastname'              => (isset($user->lastname) ? $user->lastname : ''),
            'phone'                 => (isset($user->phone) ? $user->phone : ''),
            'company_id'            => (isset($user->company_id) ? $user->company_id : ''),
            'active'                => self::USER_ACTIVE,
            'store_id'              => (isset($user->store_id) ? $user->store_id : 0),
            'provider_id'           => $this->header['x-provider-key'], 
            'previous_passwords'    => '',
            'gender' 	            => 0,
            'group_name'            => (isset($user->group_name) ? $user->group_name : null),
        	'group_id'              => (isset($user->group_id) ? $user->group_id : null),
            'legal_administrator'   => $legal_administrator,
            'last_change_password'  => date("Y-m-d H:i:s", strtotime("-1 year", time())),
            'cpf'                   => $cpf,
        ];

        if (!$this->validateCreate()) {
            return false;
        }
		
        // ($this->insert['store_id'] == 999 ? $this->insert['store_id'] = 0 : '');
		unset($this->insert['group_name']);
		unset($this->insert['group_id']);
       
	   	if (is_null($this->group_id)) {
			$this->group_id = $this->model_settings->getValueIfAtiveByName('default_group_id_api_users');
			if (!$this->group_id) {
				$this->group_id = self::PARTNER_GROUP;
			}
		}
		/*
		$result  = $this->db->insert('users', $this->insert);
        $user_id = $this->db->insert_id();
        */
		
		$user_id = $this->model_users->create($this->insert, $this->group_id);
		/*
		$group_data = [
            'user_id'  => $user_id,
            'group_id' => $this->group_id,
 		];
		
        $group  = $this->db->insert('user_group', $group_data);
		*/
		
        $response = [
            'id'        => $user_id, 
            'email'     => $this->insert['email'],
            'password'  => $password
        ];

		if (isset($user->send_welcome_email)) {
			if ( $user->send_welcome_email == 1) {
				$this->welcomeEmail($user_id, $time);
			}
		}

        return $response;
    }

	private function welcomeEmail($id, $pass )
	{

		$sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
		if (!$sellercenter) {
			$sellercenter = 'conectala';
		}
		$sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
		if (!$sellercenter_name) {
			$sellercenter_name = 'Conecta Lá';
		}
		$from = $this->model_settings->getValueIfAtiveByName('email_marketing');
		if (!$from) {
			$from = 'marketing@conectala.com.br';
		}
		
		$user = $this->model_users->getUserData($id);
		$to[] = $user['email']; 
		$subject = 'Bem-Vindo ao '.$sellercenter_name;
		
		$company = $this->model_company->getCompanyData(1);
		$data['logo'] = base_url().$company['logo'];
		$data['user'] = $user;
		$data['url'] = base_url();
		$data['pass'] = array('temp_pass' => $pass);
        $data['sellercentername'] = $sellercenter_name;
        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/welcome.php')) {
            $body= $this->load->view('mailtemplate/'.$sellercenter.'/welcome',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/welcome',$data,TRUE);
        }
		
        $resp = $this->sendEmailMarketing($to,$subject,$body,$from);
		
	}

    private function update($data, $id)
    {
    	
		$filter = " AND provider_id = ".(int)$this->header['x-provider-key'];
        if ($this->tokenMaster) {
         	$filter = '';
        }
        $sql    = "SELECT * FROM users WHERE id = ? ".$filter;
        $query  = $this->db->query($sql, array($id));
        $result = $query->result_array();
        
        if (!$result) {
            array_push($this->errors, $this->lang->line('api_user_not_found'));
            return;
        };

        if (!isset($data->user)) {
			array_push($this->errors, $this->lang->line('api_user_missing_key'));
			return false;
		}
		
		$this->id = $id;
        
		$this->insert = [
            'company_id'  => (isset($data->user->company_id) ? $data->user->company_id : ''),
        ];
        if (!isset($data->user->legal_administrator)) {  // não tem legal_administrator no json 
            if (isset($data->user->cpf)) { // mas tem o cpf no json 
                if ($result['legal_administrator'] == 0) { 
                    array_push($this->errors, $this->lang->line('api_change_cpf_adm'));
			        return false;
                }
                $data->user->cpf = preg_replace('/[^0-9]/is', '', $data->user->cpf);
                $this->validations['cpf'] = 'required_|check_cpf';
            }
        }
        else {
            if ($data->user->legal_administrator == "1") {
                $this->validations['cpf'] = 'required_|check_cpf';
                if (isset($data->user->cpf)) { // mas tem o cpf no json 
                    $data->user->cpf = preg_replace('/[^0-9]/is', '', $data->user->cpf);
                }
                else {
                    array_push($this->errors, $this->lang->line('api_user_cpf_required'));
                }
            }
            else {
                if (isset($data->user->cpf)) {
                    array_push($this->errors, $this->lang->line('api_change_cpf_adm') );
                }
                $data->user->legal_administrator = false; 
                $data->user->cpf = null; 
            }
        }

        foreach ($data->user as $user_key => $user_value) {
            $key = array_search($user_key, $this->database_x_api_fields);
            if (!$key){
                array_push($this->errors, $this->lang->line('api_field') . $user_key . $this->lang->line('api_field_invalid'));
                continue;
            }
			$this->update[$key] = $user_value; 
        }

        if (!$this->validateUpdate()) {
            return false;
        }
		
		if (array_key_exists('group_name',$this->update)) {
			unset($this->update['group_name']);
		}
		if (array_key_exists('group_id',$this->update)) {
			unset($this->update['group_id']);
		}
		if (is_null($this->group_id)) {
			$this->model_users->update($this->update, $id);
		}
		else {
			$this->model_users->edit($this->update, $id,$this->group_id );
		}
		
		/*		
        $this->db->where('id', $id);
        $result = $this->db->update('users', $this->update);
		
		if (!is_null()) {
			$group_data = [
	            'user_id'  => $id,
	            'group_id' => $this->group_id,
	        ];
			$this->db->where('user_id', $id);
			$group  = $this->db->update('user_group', $group_data);
		}
        */
		return true; 
		
    }

    private function validateCreate()
    {
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
        $sql    = "SELECT * FROM users WHERE $field = ? ";
		if (!is_null($this->id)) {
			$sql .= " AND id != ".(int)$this->id;
		}
        $query  = $this->db->query($sql, array($this->$type[$field]));
        $result = $query->result_array();

        if ($result) {
            array_push($this->errors, $this->lang->line('api_existing') . $this->database_x_api_fields[$field] . $this->lang->line('api_choose_new'));
        }
        return;
    }

    private function check($field, $check, $type)
    {
        $verifications = explode('_', $check);
        $verify = $verifications[1];
        $this->$verify($field, $this->$type[$field]);
    }

    private function company($field, $company)
    {
        if (!$company) {
            return;
        }

		$filter = " AND provider_id = ".(int)$this->header['x-provider-key'];
        if ($this->tokenMaster) {
         	$filter = '';
        }
		
        $sql    = "SELECT * FROM company WHERE id = ? ".$filter;
        $query  = $this->db->query($sql, array($company));
		//$sql    = "SELECT * FROM company WHERE id = ? ";
        //$query  = $this->db->query($sql, array($company));
        $result = $query->result_array();

        if (!$result) {
            array_push($this->errors, $this->lang->line('api_company_not_exist'));
        }
        return;
    }

    private function store($field, $store)
    {
        if ((!$store) || ($store == '0'))  {
            return;
        }
        $filter = " AND provider_id = ".(int)$this->header['x-provider-key'];
        if ($this->tokenMaster) {
         	$filter = '';
        }
        $sql    = "SELECT * FROM stores WHERE id = ? AND company_id = ? ".$filter;
        $query  = $this->db->query($sql, array($store, $this->insert['company_id'] ));
        $result = $query->result_array();

        if (!$result) {
            array_push($this->errors, $this->lang->line('api_store_not_exist'));
        }
        return;
    }

    private function password_hash($time)
    {
        $password = password_hash($time, PASSWORD_DEFAULT);
        return $password;
    }
	
	private function group($field, $group_name)
    {
        if ((!$group_name) || (is_null($group_name)))  {
            return;
        }
        $result = $this->model_groups->getGroupDataByName($group_name); 

        if (!$result) {
            array_push($this->errors, $this->lang->line('api_group_name_not_exist'));
			return;
        }
        $this->group_id = $result['id'];
        return;
    }
	
	private function groupid($field, $group_id)
    {
        if ((!$group_id) ||(is_null($group_id)))  {
            return;
        }
        $result = $this->model_groups->getGroupData($group_id); 

        if (!$result) {
            array_push($this->errors, $this->lang->line('api_group_id_not_exist'));
			return;
        }
		$this->group_id = $group_id;
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

    private function random_pwd($length = 12) 
	{
		$ucase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
		$lcase = "abcdefghijklmnopqrstuvwxyz";
		$num = "0123456789";
		$schar = '=!@$^*()<>[]{}';
		$all = $ucase.$lcase.$num.$schar;
		
	    $str = $ucase[random_int(0,mb_strlen($ucase, '8bit') - 1 )];
		$str .= $lcase[random_int(0,mb_strlen($lcase, '8bit') - 1 )];
		$str .= $num[random_int(0,mb_strlen($num, '8bit') - 1 )];
		$str .= $schar[random_int(0,mb_strlen($schar, '8bit') - 1 )];
		
	    $max = mb_strlen($all, '8bit') - 1;
	    for ($i = 0; $i < $length -4; ++$i) {
	        $str .= $all[random_int(0, $max)];
	    }
  	  	return $str;
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
	"user": {
		"username": "Usuário 100820201204",
		"email": "teste1204@teste.com",
		"firstname": "Fábio",
		"lastname": "Monteiro",
		"phone": 48912345678,
		"company_id": 17,
		"store_id": 99
	}
}

*/