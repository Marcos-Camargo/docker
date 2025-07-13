<?php

/*
 SW Serviços de Informática 2019
 
 Controller de Usuários
 
 */

/**
 * @property Model_users $model_users
 * @property Model_groups $model_groups
 * @property Model_company $model_company
 * @property Model_stores $model_stores
 * @property Model_settings $model_settings
 * @property Model_plans $model_plans
 * @property Model_notification_config $model_notification_config
 * @property Model_user_link_training $model_user_link_training
 * @property Model_externals_authentication $model_externals_authentication
 * @property AuthUser $authuser
 */
class Users extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_users');

        $this->load->model('model_users');
        $this->load->model('model_groups');
        $this->load->model('model_company');
        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_plans');
        $this->load->model('model_notification_config');
        $this->load->model('model_user_link_training');
        $this->load->model('model_externals_authentication');
        $this->load->library('AuthUser');

    }

    public function index()
    {
        if (!in_array('viewUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['sellercenter']=$this->model_settings->getValueIfAtiveByName('sellercenter');
        $this->render_template('users/index', $this->data);
    }

    public function fetchUsersData()
    {
        if (!in_array('viewUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $procura = " AND ( u.username like '%" . $busca['value'] . "%' OR u.email like '%" . $busca['value'] . "%'  
                OR u.id like '%" . $busca['value'] . "%' OR c.name like '%" . $busca['value'] . "%' OR group_name like '%" . $busca['value'] . "%'
                OR u.firstname like '%" . $busca['value'] . "%' OR u.lastname like '%" . $busca['value'] . "%') ";
            }
        } else {
            if (trim($postdata['nome'])) {
                $procura .= " AND u.username like '%" . $postdata['nome'] . "%'";
            }
            if (trim($postdata['empresa'])) {
                $procura .= " AND c.name like '%" . $postdata['empresa'] . "%'";
            }
            if (trim($postdata['status'])) {
                $procura .= " AND u.active = " . $postdata['status'];
            }
            if (trim($postdata['grupo'])) {
                $procura .= " AND group_name like '%" . $postdata['grupo'] . "%'";
            }
            if (trim($postdata['email'])) {
                $procura .= " AND u.email like '%" . $postdata['email'] . "%'";
            }
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('u.username', 'u.email', 'u.firstname', 'u.phone', 'g.group_name', 'c.name', 'u.last_login_date', 'u.active', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_users->getUsersDataView($ini, $procura, $sOrder, $length);
        $filtered = $this->model_users->getUsersDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_users->getUsersDataCount();
        }

        $result = array();
        $last_login = null;
        $no_login_record = "Sem registro";
        foreach ($data as $key => $value) {
           
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }
            
			$buttons = '';
			if(in_array('updateUser', $this->permission) || in_array('viewUser', $this->permission)) {
                $buttons .= '<a href="'.base_url('users/edit/'.$value['id']).'" class="btn btn-default"><i class="fa fa-edit"></i></a>'; 
			}
			if(in_array('deleteUser', $this->permission)) {
                $buttons .= '<a href="'.base_url('users/delete/'.$value['id']).'" class="btn btn-default"><i class="fa fa-trash"></i></a>'; 
			}
            $sellercenter=$this->model_settings->getValueIfAtiveByName('sellercenter');
            if($sellercenter=='novomundo'){ 
               if($value['last_login_date'] == null || $value['last_login_date'] == ''){
                    $last_login = null;
                } else {
                    $last_login = date('d/m/Y', strtotime($value['last_login_date']));
                }

                $limite_date = strtotime('-90 days');
                $last_login = strtotime(str_replace('/', '-', $last_login));


                if (!empty($last_login) && $last_login <= $limite_date){                   
                    $last_login =  '<span class="label label-danger">' . date('d/m/Y', strtotime($value['last_login_date'])) . '</span>'; 
                   
                }else if (!$last_login && empty($last_login)) {
                    $last_login =  '<span class="label label-warning">' . $no_login_record . '</span>';
                } else {
                    $last_login = date('d/m/Y', strtotime($value['last_login_date']));
                }
                $result[$key] = array(
                    $value['email'],
                    $value['firstname'] . ' ' . $value['lastname'],
                    $value['phone'],
                    $value['group_name'],
                    $value['company'],
                    $last_login,
                    $status,
                    $buttons
                );
            }else{
                if($value['last_login_date'] == null || $value['last_login_date'] == ''){
                    $last_login = null;
                } else {
                    $last_login = date('d/m/Y', strtotime($value['last_login_date']));
                }

                $limite_date = strtotime('-90 days');
                $last_login = strtotime(str_replace('/', '-', $last_login));


                if (!empty($last_login) && $last_login <= $limite_date){                   
                    $last_login =  '<span class="label label-danger">' . date('d/m/Y', strtotime($value['last_login_date'])) . '</span>'; 
                   
                }else if (!$last_login && empty($last_login)) {
                    $last_login =  '<span class="label label-warning">' . $no_login_record . '</span>';
                } else {
                    $last_login = date('d/m/Y', strtotime($value['last_login_date']));
                }
                $result[$key] = array(
                    $value['username'],
                    $value['email'],
                    $value['firstname'] . ' ' . $value['lastname'],
                    $value['phone'],
                    $value['group_name'],
                    $value['company'],
                    $last_login,
                    $status,
                    $buttons
                );
            }
			
			
		}
		$output = array(
			"draw" => $draw,
		    "recordsTotal" => $total_rec,
		    "recordsFiltered" => $filtered,
		    "data" => $result
		);
        ob_start();
		ob_clean();
		echo json_encode($output);
		
	}
	
    public function inactive($id)
    {
        if (!in_array('updateUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
        $this->data['store_id'] = $id;
        $this->render_template('users/confirmInactive', $this->data);
    }

    public function active($id)
    {
        if (!in_array('updateUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
        $this->data['store_id'] = $id;
        $this->render_template('users/confirmActive', $this->data);
    }

    public function activeConfirmed($id)
    {
        if (!in_array('updateUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->log_data(__CLASS__, __FUNCTION__,'activate_user_with_id', json_encode($id), 'I');
        $this->model_users->active($id);
        redirect('users', 'refresh');
    }

    public function inactiveConfirmed($id)
    {
        if (!in_array('updateUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->log_data(__CLASS__, __FUNCTION__,'inactivate_user_with_id', json_encode($id), 'I');
        $this->model_users->inactive($id);
        redirect('users', 'refresh');
    }

    public function create()
    {
        if (!in_array('createUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();

        $this->form_validation->set_rules('groups', $this->lang->line('application_group'), 'required');
        $this->form_validation->set_rules('username', $this->lang->line('application_username'), 'trim|required|min_length[5]|max_length[40]|is_unique[users.username]');
        $this->form_validation->set_rules('email', $this->lang->line('application_email'), 'trim|valid_email|required|is_unique[users.email]');
       
        $this->form_validation->set_rules('fname', $this->lang->line('application_firstname'), 'trim|required');
        $this->form_validation->set_rules('company', $this->lang->line('application_company'), 'trim|required');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->postClean('external_authentication_id',TRUE) == 0) {
                $this->form_validation->set_rules('password', $this->lang->line('application_password'), 'trim|required|min_length[8]');
                $this->form_validation->set_rules('cpassword', $this->lang->line('application_confirm_password'), 'trim|required|matches[password]');
            }
            $associate_type_pj = $this->postClean('associate_type_pj',TRUE); 
            if (!is_null($associate_type_pj)) {
                if ($associate_type_pj > 0) {                    
                    $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
                    $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
                    $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
                    $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');

                    foreach ($this->data['banks'] as $local_bank) {
                        $found = false;
                        if ($local_bank['name'] == $this->postClean('bank', TRUE)) {
                            $found = true;
                            if (!is_null($local_bank['mask_account'])) {
                                $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required|exact_length['.strlen($local_bank['mask_account']).']', array('exact_length' => $this->lang->line('application_bank_validation_account') . $local_bank['mask_account'] . $this->lang->line('application_bank_validation_complement')));                                                                
                            }
                            if (!is_null($local_bank['mask_agency'])) {
                                $this->form_validation->set_rules('agency', $this->lang->line('application_bank_validation_agency'), 'trim|required|exact_length['.strlen($local_bank['mask_agency']).']', array('exact_length' => $this->lang->line('application_bank_validation_agency') . $local_bank['mask_agency'] . $this->lang->line('application_bank_validation_complement')));
                            }
                            break;                
                        }
                    }
                    if (!$found) {
                        $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required|in_list[naoachou]', array('in_list' => $this->lang->line('messages_bank_dont_exist')));                    
                    }
                }
            }
        }

        if (!empty($this->postClean('legal_administrator', TRUE))) {
            $this->form_validation->set_rules('cpf', $this->lang->line('application_cpf'), 'trim|callback_checkCPF');
        }
        
        $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
        $this->data['lojas'] = $this->model_stores->getActiveStore();
        $group_data = $this->model_groups->getGroupData();
        $this->data['group_data'] = $group_data;
        $company_data = $this->model_company->getCompanyData();
        $this->data['company_data'] = $company_data;
        
        $this->data['externals_authentication'] = $this->model_externals_authentication->getDataActive();

        if ($this->form_validation->run()) {
            // true case
            $password = $this->password_hash($this->postClean('password', TRUE)) ?? $this->random_pwd();

            $data = array(
                'username'                      => $this->postClean('username', TRUE),
                'password'                      => $password,
                'email'                         => $this->postClean('email', TRUE),
                'firstname'                     => $this->postClean('fname', TRUE),
                'lastname'                      => $this->postClean('lname', TRUE),
                'phone'                         => $this->postClean('phone', TRUE),
                'gender'                        => 0,
                'company_id'                    => $this->postClean('company', TRUE),
                'parent_id'                     => $this->data['usercomp'],
                'active'                        => $this->postClean('active',TRUE),
                'store_id'                      => $this->postClean('store_id',TRUE),
                'associate_type'                => $this->postClean('associate_type_pj',TRUE),
                'cpf'                           => empty($this->postClean('legal_administrator', TRUE)) ? '' : preg_replace("/[^0-9]/", "",$this->postClean('cpf',TRUE)),
                'legal_administrator'           => empty($this->postClean('legal_administrator', TRUE)) ? 0 : 1,                
                'external_authentication_id'    => $this->postClean('external_authentication_id',TRUE) == 0 ? null : $this->postClean('external_authentication_id',TRUE),
                'bank'                          => '',
                'agency'                        => '', 
                'account_type'                  => '',
                'account'                       => '',
            );

            if ($data['associate_type']> 0) {        
                $data['bank']           = $this->postClean('bank', TRUE);
                $data['agency']         = $this->postClean('agency', TRUE);
                $data['account_type']   = $this->postClean('account_type', TRUE);
                $data['account']        = $this->postClean('account', TRUE);                
            }

            $create = $this->model_users->create($data, $this->postClean('groups', TRUE));

            if ($create) {
                $userPass = array(
                    'user_id' => $create,
                    'temp_pass' => $this->postClean('password'),
                );
                $this->model_users->createUserPass($userPass); // grava a senha para poder mandar o email de senha
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect('users/', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('users/create', 'refresh');
            }
        } else {
            // false case
            
                
            $this->render_template('users/create', $this->data);
        }
    }

    public function password_hash($pass = '')
    {
        if ($pass) {
            return password_hash($pass, PASSWORD_DEFAULT);
        }
    }

    public function edit($id = null)
    {
        if (!in_array('updateUser', $this->permission) && !in_array('viewUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        if (is_null($id)) {
            redirect('users');
        }
        $user_data = $this->model_users->getUserData($id);
        if (!$user_data) {
            redirect('users');
        }

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();
        $usar_mascara_banco = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
        $groups = $this->model_users->getUserGroup($id);
        $this->data['sendpass'] = $this->model_users->getUserPass($id);
        $this->data['externals_authentication'] = $this->model_externals_authentication->getDataActive();
        $this->data['user_data'] = $user_data;
        $this->data['user_group'] = $groups;
        $this->data['lojas'] = $this->model_stores->getActiveStore();
        $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
        $group_data = $this->model_groups->getGroupData();
        $this->data['group_data'] = $group_data;
        $company_data = $this->model_company->getCompanyData();
        $this->data['company_data'] = $company_data;

        $this->form_validation->set_rules('groups', $this->lang->line('application_group'), 'required');
        $this->form_validation->set_rules('username', $this->lang->line('application_username'), 'trim|required|min_length[5]|max_length[40]');
        $this->form_validation->set_rules('email', $this->lang->line('application_email'), 'trim|required|valid_email|edit_unique[users.email.'.$id.']');
        $this->form_validation->set_rules('fname', $this->lang->line('application_firstname'), 'trim|required');
        $this->form_validation->set_rules('lname', $this->lang->line('application_lastname'), 'trim|required');
        $this->form_validation->set_rules('company', $this->lang->line('application_company'), 'trim|required');
        $this->form_validation->set_rules('phone', $this->lang->line('application_phone'), 'trim|required');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($this->postClean('legal_administrator', TRUE))) {
                $this->form_validation->set_rules('cpf', $this->lang->line('application_cpf'), 'trim|callback_checkCPF');
            }
            $associate_type_pj = $this->postClean('associate_type_pj',TRUE); 
            if (!is_null($associate_type_pj)) {
                if ($associate_type_pj > 0) {
                    $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
                    $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
                    $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
                    $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
                    foreach ($this->data['banks'] as $local_bank) {
                        $found = false;
                        if ($local_bank['name'] == $this->postClean('bank', TRUE)) {
                            $found = true;
                            if (!is_null($local_bank['mask_account'])) {
                                $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required|exact_length['.strlen($local_bank['mask_account']).']', array('exact_length' => $this->lang->line('application_bank_validation_account') . $local_bank['mask_account'] . $this->lang->line('application_bank_validation_complement')));                                                                
                            }
                            if (!is_null($local_bank['mask_agency'])) {
                                $this->form_validation->set_rules('agency', $this->lang->line('application_bank_validation_agency'), 'trim|required|exact_length['.strlen($local_bank['mask_agency']).']', array('exact_length' => $this->lang->line('application_bank_validation_agency') . $local_bank['mask_agency'] . $this->lang->line('application_bank_validation_complement')));
                            }
                            break;                
                        }
                    }
                    if (!$found) {
                        $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required|in_list[naoachou]', array('in_list' => $this->lang->line('messages_bank_dont_exist')));                    
                    }
                }                
            }           

            $change_password = false;
            if (($this->postClean('external_authentication_id',TRUE) == 0) &&
                 ($this->postClean('password', TRUE) || $this->postClean('cpassword', TRUE))) {
                $this->form_validation->set_rules('password', $this->lang->line('application_password'), 'trim|required|min_length[8]');
                $this->form_validation->set_rules('cpassword', $this->lang->line('application_confirm_password'), 'trim|required|matches[password]');
                $change_password = true;
            }
        }

        if ($this->form_validation->run()) {
            // true case
            if (!in_array('updateUser', $this->permission)) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('users/edit/' . $id, 'refresh');
            }

            $data = array(
                'username'                      => $this->postClean('username',TRUE),
                'email'                         => $this->postClean('email',TRUE),
                'firstname'                     => $this->postClean('fname',TRUE),
                'lastname'                      => $this->postClean('lname',TRUE),
                'phone'                         => $this->postClean('phone',TRUE),
                'gender'                        => 0,
                'company_id'                    => $this->postClean('company',TRUE),
                'active'                        => $this->postClean('active',TRUE),
                'store_id'                      => $this->postClean('store_id',TRUE),
                'associate_type'                => $this->postClean('associate_type_pj',TRUE),
                'cpf'                           => empty($this->postClean('legal_administrator', TRUE)) ? '' : preg_replace("/[^0-9]/", "",$this->postClean('cpf',TRUE)),
                'legal_administrator'           => empty($this->postClean('legal_administrator', TRUE)) ? 0 : 1,
                'external_authentication_id'    => $this->postClean('external_authentication_id', TRUE) == 0 ? null : $this->postClean('external_authentication_id',TRUE),
                'bank'                          => '',
                'agency'                        => '', 
                'account_type'                  => '',
                'account'                       => '',
                'make_user_agent'               => $this->postClean('make_user_agent',TRUE),
            );
            if ($data['associate_type']>0) {
                $data['bank']           = $this->postClean('bank', TRUE);
                $data['agency']         = $this->postClean('agency', TRUE);
                $data['account_type']   = $this->postClean('account_type', TRUE);
                $data['account']        = $this->postClean('account', TRUE);                   
            }

            if ($change_password) {
                $data['password'] = $this->password_hash($this->postClean('password', TRUE));
            }

            $update = $this->model_users->edit($data, $id, $this->postClean('groups', TRUE));
            if ($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('users/', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('users/edit/' . $id, 'refresh');
            }

        } else {
            // SW - Log Update
            get_instance()->log_data('Users', 'edit before', json_encode($user_data), "I");

            $this->render_template('users/edit', $this->data);
        }
        
    }

    // deletar usuário não é mais permitido 
    private function delete($id)
    {
        if (!in_array('deleteUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        redirect('dashboard', 'refresh');

        if ($id) {
            if ($this->postClean('confirm')) {
                // deletar não mais permitido $delete = $this->model_users->delete($id);
                $delete = false;
                if ($delete) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed'));
                    redirect('users/', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('users/delete/' . $id, 'refresh');
                }

            } else {
                $this->data['id'] = $id;
                $this->render_template('users/delete', $this->data);
            }
        }
    }

    public function profile()
    {
        if (!in_array('viewProfile', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $plans = $this->model_plans->getPlans();
        $this->data['plans'] = $plans;

        $user_id = $this->session->userdata('id');

        $user_data = $this->model_users->getUserData($user_id);
        $this->data['user_data'] = $user_data;

        $user_group = $this->model_users->getUserGroup($user_id);
        $this->data['user_group'] = $user_group;

        $company = $this->model_company->getCompanyData($user_data['company_id']);
        $this->data['user_company'] = $company;

        $stores = $this->model_stores->getActiveStore();
        $this->data['user_stores'] = $stores;

        $bank = $this->model_users->getBankStoreFromUser($user_id);
        
        $this->data['bank'] = $bank;

        $this->render_template('users/profile', $this->data);
        
    }

	public function changepassword()
	
    {
		$user_id = $this->session->userdata('id');

        $user_data = $this->model_users->getUserData($user_id);
        if (!$user_data) {
            redirect('dashboard', 'refresh');
        }
        if (!is_null($user_data['external_authentication_id'])) { 
            redirect('dashboard', 'refresh');
        }

		if ($user_id) {
	        $this->form_validation->set_rules('current_password', $this->lang->line('application_current_password'), 'trim|required');
	        $this->form_validation->set_rules('new_password', $this->lang->line('application_new_password'), 'callback_passwordStrenght');
	        $this->form_validation->set_rules('confirm_password', $this->lang->line('application_confirm_password'), 'trim|matches[new_password]');
	
	        if ($this->form_validation->run()) {
	            	
	            $password = $this->password_hash($this->postClean('new_password', TRUE));
	            $prev_passwords = $this->model_users->getUserData($user_id);
	
				$error = '';
	            // Verifica se senha informada é correta
	            $check_password = password_verify($this->postClean('current_password', TRUE), $prev_passwords['password']);
	
	            if (!$check_password) {
	                $error = $this->lang->line('messages_error_current_password_is_invalid').'<br>';
	            }
				
				if (password_verify($this->postClean('new_password', TRUE), $prev_passwords['password'])) {
					$error .= $this->lang->line('messages_error_password_already_used').'<br>';
				}

	            // Decodifica json para ler como array
	            $previous_passwords_db = json_decode($prev_passwords['previous_passwords'], true);
	
	            // Verifica se existem mais que 10 senhas salvas para remover a mais antiga
	            if ($previous_passwords_db !== null && count($previous_passwords_db) === 10) {
	                krsort($previous_passwords_db);
	                array_pop($previous_passwords_db);
	            }
				if ($previous_passwords_db !== null) {
					foreach($previous_passwords_db as $prv_password) {
						if ( password_verify($this->postClean('new_password', TRUE), $prv_password['password'])) {
							$error .= $this->lang->line('messages_error_password_already_used').'<br>';
							break;
						}
					}
				}
				
				if ($error == '') {
		            // Adiciona dados da nova senha no array
		            $previous_passwords_db[time()] = array('datetime' => date('Y-m-d H:i:s'), 'password' => $prev_passwords['password']);
		            krsort($previous_passwords_db); // Ordena array para as senhas ficarem em order decrescente pela data da alteração
		
		            $previous_passwords_json = json_encode($previous_passwords_db); // Codifica o array para json
		
		            $data = array(
		                'password' => $password,
		                'previous_passwords' => $previous_passwords_json,
		                'last_change_password' => date('Y-m-d H:i:s'), 
		            );
					
					$this->model_users->deleteUserPass($user_id);
		            $update = $this->model_users->edit($data, $user_id);
		            if ($update) {
		                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
						$new_user_data=$this->session->userdata();
                        $new_user_data['need_change_password']=false;
                        $this->session->set_userdata($new_user_data);
		                redirect('users/changepassword', 'refresh');
		            } else {
		                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
		                redirect('users/changepassword', 'refresh');
		            }
                    get_instance()->log_data('Users', 'Password Changed','Password changed successfully ' , "I");
	            }	
				else {
					$this->session->set_flashdata('error',$error);
                    get_instance()->log_data('Users', 'Password Changed Eror',$error , "E");
				}

	        } 


			$this->render_template('users/changepassword', $this->data);

		}
    }

    public function notification_config()
    {
        $user_id = $this->session->userdata('id');
        $user_config_for_notification = $this->model_notification_config->get_by_user($user_id);
        $this->data['user_config_for_notification'] = $user_config_for_notification;
        $this->render_template('users/notification', $this->data);
    }

    public function set_notification_config()
    {        
        $user_config_for_notification = $this->model_notification_config->get_by_user($this->session->userdata('id'));
        $user_config_for_notification['order_notification'] = $this->postClean('order_notification', TRUE);
        $this->model_notification_config->update_by_user($this->session->userdata('id'), $user_config_for_notification);
        redirect('users/profile', 'refresh');
    }

    public function setting()
    {
        if (!in_array('updateSetting', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $id = $this->session->userdata('id');

        if ($id) {
            $this->form_validation->set_rules('username', $this->lang->line('application_username'), 'trim|required|min_length[5]|max_length[40]');
            $this->form_validation->set_rules('email', $this->lang->line('application_email'), 'trim|required');
            $this->form_validation->set_rules('fname', $this->lang->line('application_firstname'), 'trim|required');

            if ($this->form_validation->run()) {
                // true case
                $data = array(
                    'username' => $this->postClean('username',TRUE),
                    'email' => $this->postClean('email',TRUE),
                    'firstname' => $this->postClean('fname',TRUE),
                    'lastname' => $this->postClean('lname',TRUE),
                    'phone' => $this->postClean('phone',TRUE),
                    'gender' => $this->postClean('gender',TRUE),
                );
                $update = $this->model_users->edit($data, $id);
                if ($update) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                    redirect('users/setting/', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('users/setting/', 'refresh');
                }
            } else {
                // false case
                $user_data = $this->model_users->getUserData($id);
                $groups = $this->model_users->getUserGroup($id);

                $this->data['user_data'] = $user_data;
                $this->data['user_group'] = $groups;

                $group_data = $this->model_groups->getGroupData();
                $this->data['group_data'] = $group_data;

                $company_data = $this->model_company->getCompanyData();
                $this->data['company_data'] = $company_data;

                $this->render_template('users/setting', $this->data);
            }
        }
    }

    function language($lang = false, $code = false)
    {
        $folder = 'application/language/';
        $languagefiles = scandir($folder);

        if (in_array($lang, $languagefiles))
        {
            $cookie = array(
                'name' => 'swlanguage',
                'value' => $lang,
                'expire' => '31536000',
            );            

            $this->input->set_cookie($cookie);

            //braun -> lista de codigos de idiomas baseado nas pastas existentes
            switch ($lang)
            {
                case 'portugues_br': $code = 'pt_BR'; break;
                default: $code = 'en_US';
            }

            $_SESSION['language_code'] = $code;
        }
        redirect('');
    }

    public function passwordStrenght($password)
    {

        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8 || strlen($password) > 16) {
            $this->form_validation->set_message('passwordStrenght', $this->lang->line('messages_password_strenght_profile'));
            return false;
        } else {
            return true;
        }
    }

    public function welcomeEmail()
    {
        if (!in_array('updateUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
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

        $id = $this->postClean('id_user_boas', TRUE);

        $user = $this->model_users->getUserData($id);
        $pass = $this->model_users->getUserPass($id);
        $to[] = $user['email'];

        if($sellercenter=='novomundo'){
            $subject = 'Bem-Vindo ao seller center ' . $sellercenter_name;
        }else{
            $subject = 'Bem-Vindo ao ' . $sellercenter_name;
        }
        
		$company = $this->model_company->getCompanyData(1);
		$data['logo'] = base_url().$company['logo'];
		$data['user'] = $user;
        if (!is_null($user['external_authentication_id'])) {
            $pass['temp_pass'] = 'Utilize a senha do seu provedor ';            
        }
        $data['pass'] = $pass;   
        
        $data['sellercentername'] = $sellercenter_name;
		$data['url'] = base_url();

        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/welcome.php')) {
		    $body= $this->load->view('mailtemplate/'.$sellercenter.'/welcome',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/welcome',$data,TRUE);
        }

        $resp = $this->sendEmailMarketing($to,$subject,$body,$from,$sellercenter == 'conectala' ? base_url('assets/images/system/videos_treinamentoconecta_la.pdf') : null);

		if ($resp['ok']) {
            get_instance()->log_data('Users', 'WelcomeEmail','Welcome email to '.$user['email'].' successfully ', "I");
            $this->model_users->deleteUserPass($id); // apaga a senha temporária 
			$this->session->set_flashdata('success', $resp['msg']);
		}
		else {
            get_instance()->log_data('Users', 'WelcomeEmail','Welcome email to '.$user['email'].' with error '.$resp['msg'], "E");            
			$this->session->set_flashdata('error', $resp['msg']);
		}
		
		redirect('users/edit/'.$id, 'refresh');
	}
	
	public function resetPassword()
	{
		if (!in_array('updateUser', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		$id = $this->postClean('id_user_reset', TRUE);
		
		$user = $this->model_users->getUserData($id);
        if ($user) {
            try {
                $this->authuser->resetPassword($user['email']);
                get_instance()->log_data('Users','Reset Password',json_encode($user),"W");
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_sent'));
            } catch (Exception $exception) {
                $error_message = $exception->getMessage();
                get_instance()->log_data('Users','Reset Password error','Data: '.json_encode($user).' Error: '.$error_message,"E");
                $this->session->set_flashdata('error', $error_message);
            }
        }

		redirect('users/edit/'.$id, 'refresh');
	}
	
	private function random_str($length = 12) 
	{
		$ucase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
		$lcase = "abcdefghijklmnopqrstuvwxyz";
		$num = "0123456789";
		$schar = '=!@#$%^*()<>?;:[]{}';
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

    /*
    * This function is invoked from another function to upload the image into the assets folder
    * and returns the image path
    */
    public function upload_pfx()
    {
        // assets/images/product_image
        $config['upload_path'] = 'assets/files/certificados/' . $this->postClean('stores', TRUE);
        $config['file_name'] = "certificado";
        $config['allowed_types'] = 'pfx';
        $config['max_size'] = '1500';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('file_certificado')) {
            $error = $this->upload->display_errors();
            return array(false, $error);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['file_certificado']['name']);
            $type = $type[count($type) - 1];

            $path = $this->upload->data();
            $namePath = $config['upload_path'] . '/' . $path['file_name'];
            return array(true, $path['full_path'], $namePath);
        }
    }

    public function request_biller()
    {

        $this->form_validation->set_rules('stores', $this->lang->line('application_stores'), 'required');
        $this->form_validation->set_rules('password_certificado', $this->lang->line('application_password'), 'trim|required');

        $storesInvoicing = $this->model_stores->getStoreIdInvoicing();
        $stores = $this->model_stores->getActiveStore();
        $storesView = array();
        $allStoreIntegrate = true;

        foreach ($stores as $store) {
            if (in_array($store['id'], $storesInvoicing)) {
                if ($this->model_stores->getStoreActiveInvoicing($store['id']) == 0) {$allStoreIntegrate = false;}
                continue;
            }

            array_push($storesView, array(
                'id' => $store['id'],
                'name' => $store['name']
            ));
            $allStoreIntegrate = false;
        }
        $this->data['storesView'] = $storesView;
        $this->data['allStoreIntegrate'] = $allStoreIntegrate;

        if ($this->form_validation->run() == TRUE) {

            $existRequest = false;
            $erp = $this->postClean('erp', TRUE);
            $password = $this->postClean('password_certificado', TRUE);
            $store = (int)$this->postClean('stores', TRUE);

            foreach ($stores as $_store) {
                if (in_array($_store['id'], $storesInvoicing)) {
                    $existRequest = true;
                    break;
                }
            }

            if ($store == 0) {
                $this->session->set_flashdata('error', "Erro: " . $this->lang->line('application_stores'));
                redirect('users/request_biller/', 'refresh');
            }
            if ($existRequest) {
                $this->session->set_flashdata('error', "Erro: " . $this->lang->line('application_stores'));
                redirect('users/request_biller/', 'refresh');
            }

            if ($_FILES['file_certificado']['type'] != "application/x-pkcs12") {
                $this->session->set_flashdata('error', $this->lang->line('application_invalid_certificate'));
                redirect('users/request_biller/', 'refresh');
            }
            //CRIA PASTAS
            $serverpath = $_SERVER['SCRIPT_FILENAME'];
            $pos = strpos($serverpath, 'assets');
            $serverpath = substr($serverpath, 0, $pos);
            $targetDir = $serverpath . 'assets/files/certificados/';
            $dirPfx = $this->postClean('stores', TRUE); // gero um novo diretorio para o certificado
            if (!file_exists($targetDir)) {
                // cria o diretorio certificado
                @mkdir($targetDir);
            }
            $targetDir .= $dirPfx;
            if (!file_exists($targetDir)) {
                // cria o diretorio para o certificado
                @mkdir($targetDir);
            }
            $upload = $this->upload_pfx();
            if (!$upload[0]) {
                $this->session->set_flashdata('error', $this->lang->line('application_invalid_certificate'));
                redirect('users/request_biller/', 'refresh');
            }
            $filePath = $upload[1];
            $namePath = $upload[2];

            $data = array(
                'store_id' => $store,
                'certificado_path' => $namePath,
                'certificado_pass' => $password,
                'erp' => $erp
            );


            if ($this->model_stores->createStoresInvoicing($data)) {
                $invoice_responsible_email = $this->model_settings->getValueIfAtiveByName('invoice_responsible_email');
                if ($invoice_responsible_email) { // só manda se tiver um responsável por analizar isso.
                    $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
                    if (!$sellercenter) {
                        $sellercenter = 'conectala';
                    }
                    $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
                    if (!$from) {
                        $from = 'marketing@conectala.com.br';
                    }
                    $data['password'] = $password;
                    $data['erp'] = $erp;
                    $data['store'] = $store;
                    $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
                    if (!$sellercenter_name) {
                        $sellercenter_name = 'Conecta Lá';
                    }
                    $data['sellercentername'] = $sellercenter_name;
                    if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/invoiceservicerequest.php')) {
                        $body= $this->load->view('mailtemplate/'.$sellercenter.'/invoiceservicerequest',$data,TRUE);
                    }
                    else {
                        $body= $this->load->view('mailtemplate/default/invoiceservicerequest',$data,TRUE);
                    }

                    $this->sendEmailMarketing($invoice_responsible_email, "Solicitação Faturamento", $body, $from, $filePath);
                }
                $this->session->set_flashdata('success', $this->lang->line('messages_request_sent_successfully'));

                redirect('users/request_biller/', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('users/request_biller/', 'refresh');
            }
        } else {
            $this->render_template('users/request_biller', $this->data);
        }

    }

    public function sendMailCredentialApi()
    {
        if (!in_array('updateUser', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        
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

        $id = $this->postClean('id_user', TRUE);
        $store_id = $this->postClean('store_id_api', TRUE);

        $user = $this->model_users->getUserData($id);
        $store = $this->model_stores->getStoresData($store_id);

        $to[] = $user['email'];
        $subject = 'Aqui estão seus acessos para integração com o sellercenter ' . $sellercenter_name;

        $company = $this->model_company->getCompanyData(1);
        $data['logo'] = base_url() . $company['logo'];
        $data['user'] = $user;
        $data['store'] = $store;
        $data['sellercentername'] = $sellercenter_name;
        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/credentialapi.php')) {
		    $body= $this->load->view('mailtemplate/'.$sellercenter.'/credentialapi',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/credentialapi',$data,TRUE);
        }
        $resp = $this->sendEmailMarketing($to, $subject, $body, $from);

        if ($resp['ok']) {
            $this->session->set_flashdata('success', $resp['msg']);
        } else {
            $this->session->set_flashdata('error', $resp['msg']);
        }

        redirect('users/edit/' . $id, 'refresh');
    }

    public function updateLink()
    {
        ob_start();
        $user_id = $this->session->userdata('id');
        
        if($this->postClean('edit_link', TRUE) != null){

            $url_youtube = explode("youtu.be", $this->postClean('edit_link', TRUE));

            if(count($url_youtube) == 2)
            {
                $url_youtube = explode("/", $url_youtube[1]); 
                $id_youtube = $url_youtube[1];
            }else
            {
                $url_youtube = explode("list=", $this->postClean('edit_link', TRUE));  
                
                if(count($url_youtube) == 1)
                {
                    $url_youtube = explode("v=", $this->postClean('edit_link', TRUE));
                    if(count($url_youtube) > 1)
                    {
                        $id_youtube = $url_youtube[1];  
                    }
                }else
                {
                    $id_youtube = $url_youtube[1];                      
                }
            }

            if(!isset($id_youtube))
            {
                ob_clean();
                echo json_encode('');
            }else
            {

                $user_group = $this->model_users->getUserGroup($user_id);
                
                $data = array(
                    'update_user_id' => $user_id,
                    'link' => $id_youtube,
                    'module' => $this->postClean('module', TRUE),
                    'class' => $this->postClean('class', TRUE),
                    'id_group' => $user_group['id'],
                );  

                $retorno = $this->model_user_link_training->editByModuleClass($data);
                
                ob_clean();
                echo json_encode($retorno);
            }
        }else
        {
            ob_clean();
            echo json_encode("");
        }
    }   

    public function createLink()
    {
        ob_start();
        $user_id = $this->session->userdata('id');
        
        if($this->postClean('create_link', TRUE) != null){

            $url_youtube = explode("youtu.be", $this->postClean('create_link', TRUE));

            if(count($url_youtube) == 2)
            {
                $url_youtube = explode("/", $url_youtube[1]); 
                $id_youtube = $url_youtube[1];
            }else
            {
                $url_youtube = explode("list=", $this->postClean('create_link', TRUE));

                if(count($url_youtube) == 1)
                {
                    $url_youtube = explode("v=", $this->postClean('create_link', TRUE));
                    if(count($url_youtube) > 1)
                    {
                        $id_youtube = $url_youtube[1];  
                    }
                }else
                {
                    $id_youtube = $url_youtube[1];                      
                }
            }

            if(!isset($id_youtube))
            {
                ob_clean();
                echo json_encode('');
            }else
            {
                $user_group = $this->model_users->getUserGroup($user_id);

                $data = array(
                    'create_user_id' => $user_id,
                    'link' => $id_youtube,
                    'module' => $this->postClean('module', TRUE),
                    'class' => $this->postClean('class', TRUE),
                    'id_group' => $user_group['id'],
                );

                $update = $this->model_user_link_training->create($data);
                
                ob_clean();
                echo json_encode($id_youtube);
            }    
        }else
        {
            ob_clean();
            echo json_encode('');
        }
    }

    function checkCPF($cpf)
    {
        $ok = $this->isCPFValid($cpf);
        if (!$ok) {
            $this->form_validation->set_message('checkCPF', '{field} inválido.');
        }
        return $ok;
    }

    function isCPFValid($cpf)
    {
        // Extrai somente os números
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }
    
    public function get_name_id_active_users(){
        $users = $this->model_users->getNameAndIdActiveUsers();
        ob_clean();
        echo json_encode($users);
    }
 }