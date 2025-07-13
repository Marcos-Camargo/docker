<?php
/*
Controller de criação de autenticação externa 
*/
require_once 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') || exit('No direct script access allowed');


class ExternalAuthentication extends Admin_Controller
{

    var $ldap_configuration = array(
        'ldap_host_name'            => '', 
        'ldap_port'                 => '389', 
        'ldap_version'              => '3', 
        'ldap_base_dn'              => '', 
        'ldap_user_type'            => 'email',
        'ldap_requires_certificate' => 0,
        'ldap_client_certificate'   => '',
        'ldap_certificate_key'      => '',
    );

    var $openid_configuration = array(
        'openid_client_id'                  => '',
        'openid_client_secret'              => '',
        'openid_url_openid_configuration'   => '',
        'openid_message_login'              => '',
        'openid_icon'                       => '',
    );

    var $valid_types = array('LDAP');

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_externalAuthentication');

        $this->load->model('model_stores');
        $this->load->model('model_users');
        $this->load->model('model_externals_authentication');
        $this->load->model('model_settings');

        if ($this->model_settings->getValueIfAtiveByName('external_authentication_openid_connect')) {
            $this->valid_types[] = 'OPENID';
        }
    }

    public function index()
    {
        if (!in_array('viewExternalAuthentication', $this->permission) &&
            !in_array('createExternalAuthentication', $this->permission) &&
            !in_array('updateExternalAuthentication', $this->permission)
        ) {
            redirect('dashboard', 'refresh');
        }
        if ($this->data['only_admin'] !=1) {
            $this->session->set_flashdata('error', $this->lang->line('messages_role_only_for_admin_groups'));
            redirect('dashboard', 'refresh');
        }

        $this->data['valid_types'] = $this->valid_types;

        $this->render_template('externalauthentication/index', $this->data);
    }

    public function fetchExternalAuthentication()
    {
        if (!in_array('viewExternalAuthentication', $this->permission) &&
            !in_array('createExternalAuthentication', $this->permission) &&
            !in_array('updateExternalAuthentication', $this->permission)
        ) {
            redirect('dashboard', 'refresh');
        }
        if ($this->data['only_admin'] !=1) {
            $this->session->set_flashdata('error', $this->lang->line('messages_role_only_for_admin_groups'));
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(null, true);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $procura = " AND ( e.name like '%" . $this->db->escape_like_str($busca['value']) . "%' OR 
                e.type like '%" . $this->db->escape_like_str($busca['value']) . "%' ) "; 
            }
        } else {
            if (trim($postdata['authName'])) {
                $procura .= " AND e.name like '%" . $this->db->escape_like_str($postdata['authName']) . "%'";
            }
            if (trim($postdata['authType'])) {
                $procura .= " AND e.type  = ".$this->db->escape($postdata['authType']);
            }
            if (trim($postdata['authStatus'])) {                
                $procura .= ((int)$postdata['authStatus'] == 1) ? " AND e.active = 1" : " AND e.active = 0";                
            }
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('e.id', 'e.name', 'e.type', 'u.email', 'e.date_updated', 'e.active', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_externals_authentication->getIndexDataView($ini, $procura, $sOrder, $length);

        $filtered = $this->model_externals_authentication->getIndexDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_externals_authentication->getIndexDataCount();
        }

        $result = array();
        foreach ($data as $key => $value) {
           
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }
            
			$buttons = '';
			if (in_array('updateExternalAuthentication', $this->permission)) {
                $buttons .= '<a href="'.base_url('externalAuthentication/edit/'.$value['id']).
                            '" class="btn btn-default"><i class="fa fa-edit"></i></a>';
			} 
            if (in_array('viewExternalAuthentication', $this->permission)) {
                $buttons .= '<a href="'.base_url('externalAuthentication/view/'.$value['id']).
                            '" class="btn btn-default"><i class="fa fa-eye"></i></a>';
			}
            
            $result[$key] = array(
                $value['id'],
                $value['name'],
                $value['type'],
                $value['email'],
                date('d/m/Y', strtotime($value['date_updated'])),
                $status,
                $buttons
            );
			
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

    public function create($type = null)
	{
		if (!in_array('createExternalAuthentication', $this->permission)) {
		    redirect('dashboard', 'refresh');
		}
        if (trim($type) == '') {
            $this->session->set_flashdata('error', $this->lang->line('application_invalid_type'));
            redirect('externalAuthentication/index/', 'refresh');
        }
        if ($this->data['only_admin'] !=1) {
            $this->session->set_flashdata('error', $this->lang->line('messages_role_only_for_admin_groups'));
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(null, true);
        $user = $this->model_users->getUserData($this->session->userdata['id']);
        if (!$user) {
            redirect('externalAuthentication/index/', 'refresh');
        }

		$this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required|min_length[3]|max_length[40]|is_unique[externals_authentication.name]');
		$this->form_validation->set_rules('active', $this->lang->line('application_status'), 'required');

        if ($type == 'LDAP') {
            $this->LDAPValidateFields($postdata);
        } elseif ($type == 'OPENID') {
            $this->OPENIDValidateFields($postdata);
        } else {
            $this->session->set_flashdata('error', $this->lang->line('application_invalid_type')." ".$type);
            redirect('externalAuthentication/index/', 'refresh');
        }

        if ($this->form_validation->run()) {
            $data = array(
                'name'          => $postdata['name'],
                'type'          => $type,
                'active'        => $postdata['active'],
                'user_created'  => $this->session->userdata['id'],
                'user_updated'  => $this->session->userdata['id'],
            );

            $auth_id = $this->model_externals_authentication->create($data);
            if ($auth_id) {
                $all_id = array ('id' => $auth_id);
                get_instance()->log_data('ExternalAuthentication', 'create', json_encode(array_merge($all_id, $data)), "I");
                $postdata['user_created'] = $data['user_created'];
                $postdata['user_updated'] = $data['user_updated'];

                $all_ok = false; 
                if ($type == 'LDAP') {
                    $all_ok = $this->LDAPSaveConfigurarion($auth_id, $postdata);
                } elseif ($type == 'OPENID') {
                    $all_ok = $this->OPENIDSaveConfigurarion($auth_id, $postdata);
                }
                if ($all_ok) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                    redirect('externalAuthentication/index', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('externalAuthentication/create/'.$type, 'refresh');
                }
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('externalAuthentication/create/'.$type, 'refresh');
            }
        }
        
		$this->data['external_auth'] = array (
			'id' 			=> 0 ,
			'name' 	        => key_exists('name', $postdata) ? $postdata['name'] : '',
            'type' 	        => $type,
            'active'        => 1, 
            'date_created'  => date('d/m/Y h:i:s'),
            'date_updated'  => date('d/m/Y h:i:s'),
            'user_created'  => $user['id'],
            'user_updated'  => $user['id'],
            'email_created' => $user['email'],
            'email_updated' => $user['email'],
		);
        if ($type == 'LDAP') {
            $this->data['ldap_configuration'] = $this->ldap_configuration;
        } elseif ($type == 'OPENID') {
            $this->data['openid_configuration'] = $this->openid_configuration;
        }

		$this->data['function'] = 'create';
		$this->render_template('externalauthentication/edit', $this->data);
    }

    public function edit($id=null)
	{
		if (!in_array('updateExternalAuthentication', $this->permission)) {
		    redirect('dashboard', 'refresh');
		}
        if (is_null($id)) {
		    redirect('externalAuthentication/index/', 'refresh');
		}
        if ($this->data['only_admin'] !=1) {
            $this->session->set_flashdata('error', $this->lang->line('messages_role_only_for_admin_groups'));
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(null, true);
        $user = $this->model_users->getUserData($this->session->userdata['id']);
        if (!$user) {
            redirect('externalAuthentication/index/', 'refresh');
        }

        $external_auth = $this->model_externals_authentication->getData($id);
        if (!$external_auth) {
            redirect('externalAuthentication/index/', 'refresh');
        }
        $confs = $this->model_externals_authentication->getAllDataConfiguration($id); 
        
        $type = $external_auth['type'];

		$this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required|min_length[3]|max_length[40]|callback_checkUniqueName['.$id.']');
		$this->form_validation->set_rules('active', $this->lang->line('application_status'), 'required');

        if ($type == 'LDAP') {
            $ldap_configuration = $this->ldap_configuration; 
            foreach ($confs as $conf) {
                $ldap_configuration[$conf['name']] = $conf['value'];
            }
            $this->LDAPValidateFields($postdata, $ldap_configuration);
        }elseif ($type == 'OPENID') {
            $openid_configuration = $this->openid_configuration; 
            foreach ($confs as $conf) {
                $openid_configuration[$conf['name']] = $conf['value'];
            }
            $this->OPENIDValidateFields($postdata, $openid_configuration);
        } else {
            $this->session->set_flashdata('error', 'Tipo Inválido: '.$type);
            redirect('externalAuthentication/index/', 'refresh');
        }

        if ($this->form_validation->run()) {
            $data = array(
                'name'          => $postdata['name'],
                'type'          => $external_auth['type'],
                'active'        => $postdata['active'],
                'user_updated'  => $this->session->userdata['id'],
            );

            $auth_id = $this->model_externals_authentication->update($data, $id);
            if ($auth_id) {
                $all_id = array ('id' => $id);
                get_instance()->log_data('ExternalAuthentication', 'edit', json_encode(array_merge($all_id, $data)), "I");
                $postdata['user_created'] = $external_auth['user_created'];
                $postdata['user_updated'] = $data['user_updated'];

                $all_ok = false; 
                if ($type == 'LDAP') {
                    $all_ok = $this->LDAPSaveConfigurarion($id, $postdata, $ldap_configuration);
                } elseif ($type == 'OPENID') {
                    $all_ok = $this->OPENIDSaveConfigurarion($id, $postdata, $openid_configuration);
                }
                if ($all_ok) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                    redirect('externalAuthentication/index', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('externalAuthentication/edit/'.$id, 'refresh');
                }
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('externalAuthentication/edit/'.$id, 'refresh');
            }
        }
        
        $usercreate = $this->model_users->getUserData($external_auth['user_created']);
        $external_auth['email_created'] = $usercreate['email'];
        $external_auth['email_updated'] = $user['email'];

		$this->data['external_auth'] = $external_auth;
        if ($type == 'LDAP') {
            $this->data['ldap_configuration'] = $ldap_configuration;
        } elseif ($type == 'OPENID') {
            $this->data['openid_configuration'] = $openid_configuration;
        }
		$this->data['function'] = 'update';
		$this->render_template('externalauthentication/edit', $this->data);
    }

    public function view($id=null)
	{
		if (!in_array('viewExternalAuthentication', $this->permission)) {
		    redirect('dashboard', 'refresh');
		}
        if (is_null($id)) {
		    redirect('externalAuthentication/index/', 'refresh');
		}
        if ($this->data['only_admin'] !=1) {
            $this->session->set_flashdata('error', $this->lang->line('messages_role_only_for_admin_groups'));
            redirect('dashboard', 'refresh');
        }

        $user = $this->model_users->getUserData($this->session->userdata['id']);

        $external_auth = $this->model_externals_authentication->getData($id);
        if (!$external_auth) {
            redirect('externalAuthentication/index/', 'refresh');
        }
        $confs = $this->model_externals_authentication->getAllDataConfiguration($id); 

        $type = $external_auth['type'];
        if ($type == 'LDAP') {
            $ldap_configuration = $this->ldap_configuration; 
            foreach ($confs as $conf) {
                $ldap_configuration[$conf['name']] = $conf['value'];
            }
            $this->data['ldap_configuration'] = $ldap_configuration;
        } elseif ($type == 'OPENID') {
            $openid_configuration = $this->openid_configuration; 
            foreach ($confs as $conf) {
                $openid_configuration[$conf['name']] = $conf['value'];
            }
            $this->data['openid_configuration'] = $openid_configuration;
        }

        $usercreate = $this->model_users->getUserData($external_auth['user_created']);
        $external_auth['email_created'] = $usercreate['email'];
        $external_auth['email_updated'] = $user['email'];

		$this->data['external_auth'] = $external_auth;

		$this->data['function'] = 'view';
		$this->render_template('externalauthentication/edit', $this->data);
    }

    private function LDAPValidateFields($postdata, $ldap_configuration = null) 
    {
        $this->form_validation->set_rules('ldap_host_name', $this->lang->line('application_ldap_host_name'), 'trim|required');
        $this->form_validation->set_rules('ldap_port', $this->lang->line('application_ldap_port'), 'trim|required|numeric');
        $this->form_validation->set_rules('ldap_version', $this->lang->line('application_ldap_version'), 'trim|required|integer|in_list[2,3]');
        $this->form_validation->set_rules('ldap_user_type', $this->lang->line('application_ldap_user_type'), 'trim|required|in_list[username,email]');
        if (array_key_exists('ldap_requires_certificate', $postdata) &&
            ($postdata['ldap_requires_certificate'])) {
                
            if (empty($_FILES['ldap_client_certificate']['name'])) {
                if (is_null($ldap_configuration)) {
                    $this->form_validation->set_rules('ldap_client_certificate', $this->lang->line('application_ldap_client_certificate'), 'required');
                }elseif (trim($ldap_configuration['ldap_client_certificate']) == '') {
                    $this->form_validation->set_rules('ldap_client_certificate', $this->lang->line('application_ldap_client_certificate'), 'required');
                }
                
            }
            if (empty($_FILES['ldap_certificate_key']['name'])) {
                if (is_null($ldap_configuration)) {
                    $this->form_validation->set_rules('ldap_certificate_key', $this->lang->line('application_ldap_certificate_key'), 'required');
                }elseif (trim($ldap_configuration['ldap_certificate_key']) == '') {
                    $this->form_validation->set_rules('ldap_certificate_key', $this->lang->line('application_ldap_certificate_key'), 'required');
                }
            }
        }
    }

    private function LDAPSaveConfigurarion($auth_id, $postdata, $ldap_configuration= null) 
    {

        foreach ($this->ldap_configuration as $key => $value) {

            if (($key == 'ldap_requires_certificate') ||
                ($key == 'ldap_client_certificate') ||
                ($key == 'ldap_certificate_key')) {
                continue;
            }
            $ok = $this->saveConfiguration($auth_id, $key, $postdata[$key], $postdata);
            if (!$ok) {
                return false;
            }
        }
        
        $requires_certificate = array_key_exists('ldap_requires_certificate', $postdata);
        $ok = $this->saveConfiguration($auth_id, 'ldap_requires_certificate', $requires_certificate, $postdata);
        if ($ok) {
            if ($requires_certificate) {
                if ((is_null($ldap_configuration)) || (!empty($_FILES['ldap_client_certificate']['name']))) {
                    $filesave = $this->uploadFile($auth_id, 'ldap_client_certificate', $postdata, 'crt');
                    if ($filesave) {
                        $ok = $this->saveConfiguration($auth_id, 'ldap_client_certificate', $filesave, $postdata);
                    } else {
                        $ok = false;
                    }
                }
                if (($ok) && ((is_null($ldap_configuration)) || (!empty($_FILES['ldap_certificate_key']['name'])))) {
                    $filesave = $this->uploadFile($auth_id, 'ldap_certificate_key', $postdata, 'key');
                    if ($filesave) {
                        $ok = $this->saveConfiguration($auth_id, 'ldap_certificate_key', $filesave, $postdata);
                    } else {
                        $ok = false;
                    }
                }
            } else {
                $ok = $this->saveConfiguration($auth_id, 'ldap_requires_certificate', '', $postdata);
                if ($ok) {
                    $ok = $this->saveConfiguration($auth_id, 'ldap_client_certificate', '', $postdata);
                    if ($ok) {
                        $ok = $this->saveConfiguration($auth_id, 'ldap_certificate_key', '', $postdata);
                    }
                }
            }
        }
     
        return $ok;
    }

    private function OPENIDValidateFields($postdata, $openid_configuration = null) 
    {
        $this->form_validation->set_rules('openid_client_id', '1'.$this->lang->line('application_openid_client_id'), 'trim|required');
        $this->form_validation->set_rules('openid_client_secret', '2'.$this->lang->line('application_openid_client_secret'), 'trim|required');
        $this->form_validation->set_rules('openid_url_openid_configuration', '3'.$this->lang->line('application_openid_url_openid_configuration'), 'trim|required|valid_url');
        $this->form_validation->set_rules('openid_message_login', '4'.$this->lang->line('application_openid_message_login'), 'trim|required');
        if (empty($_FILES['openid_icon']['name'])) {
            if (is_null($openid_configuration)) {
                $this->form_validation->set_rules('openid_icon', $this->lang->line('application_openid_icon'), 'required');
            }elseif (trim($openid_configuration['openid_icon']) == '') {
                $this->form_validation->set_rules('openid_icon', $this->lang->line('application_openid_icon'), 'required');
            }
            
        }
    }

    private function OPENIDSaveConfigurarion($auth_id, $postdata, $openid_configuration = null) 
    {

        foreach ($this->openid_configuration as $key => $value) {
            if ($key == 'openid_icon') {
                continue;
            }
            $ok = $this->saveConfiguration($auth_id, $key, $postdata[$key], $postdata);
            if (!$ok) {
                return false;
            }
        }
        if ($ok && ((is_null($openid_configuration)) || (!empty($_FILES['openid_icon']['name'])))) {
            $filesave = $this->uploadFile($auth_id, 'openid_icon', $postdata, 'gif|jpg|png|jpeg',2000,1500,1500);
            if ($filesave) {
                $ok = $this->saveConfiguration($auth_id, 'openid_icon', $filesave, $postdata);
            } else {
                $ok = false;
            }
        }

        return $ok;
    }

    private function saveConfiguration($auth_id, $field, $value, $postdata) 
    {
        $data = array(
            'external_authentication_id'   => $auth_id,
            'name'                         => $field,
            'value'                        => $value,
            'user_created'                 => $postdata['user_created'], 
            'user_updated'                 => $postdata['user_updated'],
        );

        $ok = $this->model_externals_authentication->createOrUpdateConfiguration($data);
        if (!$ok) {
            $this->session->set_flashdata('error', 'Erro ao salvar '.$field);
        }
        return $ok;
    }

    private function uploadFile($auth_id, $field, $postdata, $file_type, $max_size='3009', $max_width=null, $max_height =null) 
    {
        
       // if ($_FILES[$field]['type'] != "application/x-x509-ca-cert") {
           // $this->session->set_flashdata('error', $this->lang->line('application_invalid_certificate'));
           // die; 
           // return false;
       // }
        //CRIA PASTAS
        $path = 'assets/files/externalauthentication/';
        $targetDir = FCPATH . $path;
        if (!file_exists($targetDir)) {
            // cria o diretorio certificado
            @mkdir($targetDir);
        }

        $config['upload_path']      = $path;
        $config['file_name']        = trim($postdata['name']);
        $config['allowed_types']    = $file_type;
        $config['allowed_types']    = '*';
        $config['max_size']         = $max_size;
        if (!is_null($max_width))   { $config['max_width']  = $max_width;}
        if (!is_null($max_height))  { $config['max_height']  = $max_height;}


        $this->load->library('upload', $config);
        if (!$this->upload->do_upload($field)) {
            echo $this->upload->display_errors();
            $this->session->set_flashdata('error', $this->upload->display_errors());
            die;
            return false;
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES[$field]['name']);
            $type = $type[count($type) - 1];
            $path = $this->upload->data();
            return $config['upload_path'] . $path['file_name'];
        }
    }

    public function checkUniqueName($name, $id)
    {

        if ((is_null($name)) || (trim($name) == '')) {
            return true;
        }
        $exist = $this->model_externals_authentication->VerifyNameUnique($name, $id);
        if ($exist) {
            $this->form_validation->set_message('checkUniqueName', $this->lang->line('application_name') . ' ' . $name . ' já cadastrado, id=' . $exist['id']);
            return false;
        }
        return true;
    }

    public function LDAPTestLogin() 
    {
        $ldapid = (int)$this->postClean('ldapid', true);
        $ldaplogin = $this->postClean('ldaplogin', true);
        $ldappassword = $this->postClean('ldappassword', true, true);

        $external_auth = $this->model_externals_authentication->getData($ldapid);
        if (!$external_auth) {
            redirect('externalAuthentication/index/', 'refresh');
        }
        if ((trim($ldaplogin) =='') || (trim($ldappassword) == '')) {
            redirect('externalAuthentication/index/', 'refresh');
        }
        $output = $this->model_externals_authentication->LDAPLogin($ldapid, $ldaplogin, $ldappassword);
        ob_start();
		ob_clean();
		echo json_encode($output);
    }

    public function OPENIDTestSite() 
    {
        $openid = (int)$this->postClean('openid', true);

        $external_auth = $this->model_externals_authentication->getData($openid);
        if (!$external_auth) {
            redirect('externalAuthentication/index/', 'refresh');
        }
        $confs = $this->model_externals_authentication->getAllDataConfiguration($openid);
        foreach ($confs as $conf) {
            $openid_configuration[$conf['name']] = $conf['value'];        
        }

        $openidsite = 'https://'.$openid_configuration['openid_url_openid_configuration'].'/.well-known/openid-configuration';
      
        $ch = curl_init($openidsite);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $data = json_decode($response, true);
        curl_close($ch);

        $error = true; 
        if (($responseCode < 200) || ($responseCode > 299)) {
            $result = 'Response code '.$responseCode.' para o site '.$openidsite; 
        }elseif (!isset($data['authorization_endpoint'])) {
            $result = $openidsite.$this->lang->line('application_error_no_authorization_endpoint');             
        } else {
            $result = $this->lang->line('application_site_ok_openid').$data['authorization_endpoint'];
            $error = false;            
        }
        $output = array(
            'ok'        => !$error,
            'result'    => $result
        );
        
        ob_start();
		ob_clean();
		echo json_encode($output);
    }

    
    public function fetchUsersExternalAuthentication()
    {
        if (!in_array('viewExternalAuthentication', $this->permission) &&
            !in_array('createExternalAuthentication', $this->permission) &&
            !in_array('updateExternalAuthentication', $this->permission)
        ) {
            redirect('dashboard', 'refresh');
        }
        if ($this->data['only_admin'] !=1) {
            $this->session->set_flashdata('error', $this->lang->line('messages_role_only_for_admin_groups'));
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(null, true);

        if(!isset( $postdata['externalauthenticationid'])) {
            redirect('dashboard', 'refresh');
        }

        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $procura = " AND ( firstname like '%" . $this->db->escape_like_str($busca['value']) . "%' OR 
                lastname like '%" . $this->db->escape_like_str($busca['value']) . "%' OR
                username like '%" . $this->db->escape_like_str($busca['value']) . "%' OR
                email like '%" . $this->db->escape_like_str($busca['value']) . "%' 
                ) ";          
            }
        } 

        $sOrder ='';
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('id','username', 'firstname', 'lastname', 'email', 'active');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_externals_authentication->getUsersExternalAuthenticationView($postdata['externalauthenticationid'],$ini, $procura, $sOrder, $length);

        $filtered = $this->model_externals_authentication->getUsersExternalAuthenticationCount($postdata['externalauthenticationid'],$procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_externals_authentication->getUsersExternalAuthenticationCount($postdata['externalauthenticationid']);
        }

        $result = array();
        foreach ($data as $key => $value) {
           
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }
            
            $result[$key] = array(
                '<a href="'.base_url('users/edit/'.$value['id']).'" class="btn btn-default">'.$value['id'].'</a>',
                $value['username'],
                $value['firstname'],
                $value['lastname'],
                $value['email'],
                $status
            );
			
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

}
