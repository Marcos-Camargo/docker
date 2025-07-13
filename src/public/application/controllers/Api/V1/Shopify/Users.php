<?php

// Esta API foi adaptada para uso exclusivo da Shopify
// por não fornecer todas as informações necessárias 
// para criação de uma empresa. 
// Dessa forma, as validações de determinados campos
// foram retiradas e o cadastro será feito de forma 
// incompleta. 

require APPPATH . "controllers/Api/V1/API.php";

class Users extends API
{
    private const NO_CONTENT     = 'No content was found for the parameters entered.';
    private const NON_NUMERIC    = 'user_id must have a numeric value.';
    private const USER_CREATED   = 'User created';
    private const USER_UPDATED   = 'Updated data';
    private const PARTNER_GROUP  = 11;
    private const USER_ACTIVE    = 1;
    private const USER_NOT_FOUND = 'User not found';

    private $filters;
    private $search;
    private $insert;
    private $header;
    private $update = [];
    private $errors = [];
	private $id = null;

    private $database_x_api_fields = [
        'username'   => 'username',
        'email'      => 'email',
        'firstname'  => 'firstname',
        'lastname'   => 'lastname',
        'phone'      => 'phone',
        'company_id' => 'company_id',
        'store_id'   => 'store_id'
    ];

    private $validations = [
        'username'   => 'required_|min_5|max_40|unique_username',
        'email'      => 'required_|unique_email',
        'firstname'  => 'required_',
        'lastname'   => 'required_',
        'phone'      => 'required_|numeric_|min_10|max_11',
        'company_id' => 'required_|numeric_|check_company',
        'store_id'   => 'required_|numeric_|check_store'
    ];

    private $dontUpdate = [
        'store_id', 'company_id'
    ];

    private $groups = [
        '1'  => 'Administrator',
        '5'  => 'Agência',
        '11' => 'Parceiro',
        '12' => 'Vendedor'
    ];
	
	public function __construct()
    {
        parent::__construct();

        $this->load->model('model_settings');
		$this->load->model('model_company');
		$this->load->model('model_users');
    }

    public function index_get()
    {

        $this->header = getallheaders();
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->search = $this->input->get();

        if (!$this->mountTheFilters()) {
            return $this->response(array('success' => false, 'result' => self::NON_NUMERIC), REST_Controller::HTTP_BAD_REQUEST);
        }

        $users = $this->responseFormat($this->getUsers());

        if (!$users) {
            return $this->response(array('success' => true, 'result' => self::NO_CONTENT), REST_Controller::HTTP_OK);
        }
        
        return $this->response(array('success' => true, 'result' => $users), REST_Controller::HTTP_OK);
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

        $data      = json_decode(file_get_contents('php://input'));
		if (is_null($data)) {
			return $this->response(array('success' => false, 'errors' => 'Invalid json format'), REST_Controller::HTTP_BAD_REQUEST);
		}
        $result    = $this->insert($data);

        if (!$result) {
            return $this->response(array('success' => false, 'errors' => $this->errors), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'result' => ['data' => $result, 'message' => self::USER_CREATED]), REST_Controller::HTTP_CREATED);
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

        return $this->response(array('success' => true, 'result' => self::USER_UPDATED), REST_Controller::HTTP_OK);
    }

    private function mountTheFilters()
    {
        $this->filters = " WHERE users.provider_id = " . $this->header['x-provider-key'];
        if (isset($this->search['user_id'])) {
            if (!is_numeric($this->search['user_id'])) {
                return false;
            } else {
                $this->filters .= " AND users.id = " . $this->search['user_id'] . " ";
            }
        }
        if (isset($this->search['user_name'])) $this->filters    .= " AND users.username LIKE '%" . $this->search['user_name'] . "%' ";
        if (isset($this->search['user_email'])) $this->filters   .= " AND users.email LIKE '%" . $this->search['user_email'] . "%' ";
        if (isset($this->search['company_name'])) $this->filters .= " AND company.name LIKE '%" . $this->search['company_name'] . "%' ";
        if (isset($this->search['store_name'])) $this->filters   .= " AND stores.name LIKE '%" . $this->search['store_name'] . "%' ";

        return true;
    }

    private function getUsers()
    {
        $sql = "SELECT 
                    users.id, 
                    users.username, 
                    users.email, 
                    users.company_id, 
                    user_group.group_id, 
                    company.name AS company_name, 
                    users.store_id, 
                    stores.name AS store_name 
                FROM users 
                LEFT JOIN company ON company.id = users.company_id 
                LEFT JOIN user_group ON user_group.user_id = users.id 
                LEFT JOIN stores ON stores.id = users.store_id " . $this->filters;

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    private function responseFormat($users)
    {
        $response = [];
        
        foreach ($users as $user) {
            $format = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'group'    => $this->groups[$user['group_id']],
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
		
		foreach ($data->user as $key => $value) {
            if (!array_search($key, $this->database_x_api_fields) && $key != 'send_welcome_email')  
            	array_push($this->errors, "Parameter entered does not match in an insert field: " . $key);
		}
		
		$this->id = null;
        $this->insert = [
            'username'    => (isset($user->username) ? $user->username : ''),
            'password'    => $this->password_hash($time),
            'email'       => (isset($user->email) ? $user->email : ''),
            'firstname'   => (isset($user->firstname) ? $user->firstname : ''),
            'lastname'    => (isset($user->lastname) ? $user->lastname : ''),
            'phone'       => (isset($user->phone) ? $user->phone : ''),
            'company_id'  => (isset($user->company_id) ? $user->company_id : ''),
            'active'      => self::USER_ACTIVE,
            'store_id'    => (isset($user->store_id) ? $user->store_id : ''),
            'provider_id' => $this->header['x-provider-key']
        ];

        if (!$this->validateCreate()) {
            return false;
        }

        ($this->insert['store_id'] == 999 ? $this->insert['store_id'] = 0 : '');

        $result  = $this->db->insert('users', $this->insert);
        $user_id = $this->db->insert_id();

        $group_data = [
            'user_id'  => $user_id,
            'group_id' => self::PARTNER_GROUP,
        ];

        $group  = $this->db->insert('user_group', $group_data);

        $response = [
            'email'    => $this->insert['email'],
            'password' => $time
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
        $sql    = "SELECT * FROM users WHERE provider_id = " . $this->header['x-provider-key'] . " AND id = ? ";
        $query  = $this->db->query($sql, array($id));
        $result = $query->result_array();
        
        if (!$result) {
            array_push($this->errors, self::USER_NOT_FOUND);
            return;
        };
		
		$this->id = $id;
        
		$this->insert = [
            'company_id'  => (isset($data->user->company_id) ? $data->user->company_id : ''),
        ];
		
        foreach ($data->user as $user_key => $user_value) {
            $key = array_search($user_key, $this->database_x_api_fields);
            if (!$key){
                array_push($this->errors, "Field $user_key invalid");
                continue;
            }
            $this->update[$key] = $user_value; 
        }

        if (!$this->validateUpdate()) {
            return false;
        }

        $this->db->where('id', $id);
        $result = $this->db->update('users', $this->update);
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
        $sql    = "SELECT * FROM users WHERE $field = '". $this->$type[$field] . "'";
		if (!is_null($this->id)) {
			$sql .= " AND id != ".$this->id;
		}
        $query  = $this->db->query($sql);
        $result = $query->result_array();

        if ($result) {
            array_push($this->errors, "Existing " . $this->database_x_api_fields[$field] .", choose new $field");
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
        
        $sql    = "SELECT * FROM company WHERE id = ? AND provider_id = ?";
        $query  = $this->db->query($sql, array($company, $this->header['x-provider-key']));

        $result = $query->result_array();

        if (!$result) {
            array_push($this->errors, "Informed company does not exist");
        }
        return;
    }

    private function store($field, $store)
    {
        if ((!$store) || ($store == '0') || ($store == 999))  {
            return;
        }
        
        $sql    = "SELECT * FROM stores WHERE id = ? AND company_id = ? AND provider_id = ?";
        $query  = $this->db->query($sql, array($store, $this->insert['company_id'], $this->header['x-provider-key']));
        $result = $query->result_array();

        if (!$result) {
            array_push($this->errors, "Informed store does not exist");
        }
        return;
    }

    private function password_hash($time)
    {
        $password = password_hash($time, PASSWORD_DEFAULT);
        return $password;
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
		"username": "Usuário 1008202012073",
		"email": "teste12073@teste.com",
		"firstname": "Fábio",
		"lastname": "Monteiro",
		"phone": 48912345678,
		"company_id": 46,
		"store_id": 999
	}
}

*/