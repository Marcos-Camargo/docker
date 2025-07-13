<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Controle de Acesso
 
 */

require_once 'system/libraries/Vendor/autoload.php';

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Input $input
 * @property CI_Loader $load
 * @property CI_Security $security
 * @property CI_Session $session
 * @property CI_Lang $lang
 * @property CI_Form_validation $form_validation
 *
 * @property Model_auth $model_auth
 * @property Model_users $model_users
 * @property Model_groups $model_groups
 * @property Model_settings $model_settings
 * @property Model_company $model_company
 * @property Model_reset_tokens $model_reset_tokens
 * @property Model_stores $model_stores
 * @property Model_contract_signatures $model_contract_signatures
 * @property Model_externals_authentication $model_externals_authentication
 * @property AuthUser $authuser
 */
class Auth extends Admin_Controller
{
    
    private $viewLoginBanner;
    
    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_auth');
        $this->load->model('model_users');
        $this->load->model('model_groups');
        $this->load->model('model_settings');
        $this->load->model('model_company');
        $this->load->model('model_reset_tokens');
        $this->load->model('model_stores');
		$this->load->model('model_contract_signatures');
        $this->load->model('model_externals_authentication');
        $this->load->library('AuthUser');
    }

    /*
     Check if the login form is submitted, and validates the user credential
     If not submitted it redirects to the login page
     */
    public function login($icn='',$pcn='')
    {
        $this->logged_in();
        $count_error_login = 0;

        $this->form_validation->set_rules('email', 'Email', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
        /**
         * Verifica se o parametro da view de login com banner esta ativo e caso sim carrega a view correspondente
         */
        
        $this->viewLoginBanner = $this->model_settings->getSettingDatabyName('tela_login_banner');
        
        if($this->viewLoginBanner && $this->viewLoginBanner['status'] == 1){
            $view = 'login_banner';
        }else{
            $view = 'login';
        }
	    $sellercenter = 'conectala';
		$sellerCenterSetting = $this->model_settings->getSettingDatabyName('sellercenter');
		if ($sellerCenterSetting) {
			$sellercenter = $sellerCenterSetting['value'];
		}
		$this->data['sellerCenter'] = $sellercenter;
		
		$this->data['csrf'] = array(
			'name' => $this->security->get_csrf_token_name(),
			'hash' => $this->security->get_csrf_hash()
		);

		if (file_exists(FCPATH.'assets/skins/'.$sellercenter.'/politica_privacidade.pdf')) {
			$policy =base_url('assets/skins/'.$sellercenter.'/politica_privacidade.pdf');
			$this->data['policy'] = $policy; 
		}

        if ($this->form_validation->run() == TRUE || ($icn && $pcn)) {

            $key_redis_count_error_login = "$sellercenter:count_error_login:{$this->postClean('email')}";
            try {
                $count_error_login = \App\Libraries\Cache\CacheManager::get($key_redis_count_error_login) ?? 0;
            } catch (Exception $exception) {}

            $this->data['messages_external_login'] = [];
            $this->data['messages_external_login_icon'] = [];
            if ($count_error_login > 10) {
                $this->data['errors'] = $this->lang->line('messages_login_too_many_login');
                $this->data['messages_external_login'] = $this->model_externals_authentication->getLoginMessages();
                $this->data['messages_external_login_icon'] = $this->model_externals_authentication->getLoginIcons();
                $this->data['redirect_url'] = $this->postClean('redirect_url', true) ?? null;
                //$this->log_data('Auth', 'login', $this->lang->line('messages_login_email_not_exists') . $this->postClean('email', TRUE), "W");

                if (!$icn && !$pcn) {
                    $this->load->view($view, $this->data);
                }

                session_write_close();
                return ;
            }

            // true case
            $email_exists = $this->model_auth->check_email($this->postClean('email', TRUE));
            $need_change_password=false;
            $warning_need_change_password=false;
            if ($email_exists == TRUE || ($icn && $pcn)) {
                if ($icn && $pcn){
                    $email = $this->model_users->getUserData($icn)['email'];
                    $login = $this->model_auth->login($email,$pcn);
                }  else {
                    $login = $this->model_auth->login($this->postClean('email', TRUE), $this->postClean('password', TRUE, TRUE));
                    $days_for_change_password=$this->model_settings->getValueIfAtiveByName('days_for_change_password');
                    $days_for_warning_change_password=$this->model_settings->getValueIfAtiveByName('days_for_warning_change_password');
                    if(($days_for_change_password && is_null($login['result']['external_authentication_id']))){
                        $days_for_change_password=intval($days_for_change_password);
                        $days_for_warning_change_password=intval($days_for_warning_change_password);
                        $datetime1 = new DateTime($login['result']['last_change_password']);
                        $datetime2 = new DateTime();
                        $interval = $datetime1->diff($datetime2);
                        $day_for_expiration=$days_for_change_password-$interval->days;
                        if($day_for_expiration<0){
                            $need_change_password=true;
                            $this->session->set_flashdata('error',sprintf( $this->lang->line('messages_need_change_password')));
                        }else if($days_for_warning_change_password && $day_for_expiration<$days_for_warning_change_password){
                            $warning_need_change_password=true;
                            if($day_for_expiration==0){
                                $this->session->set_flashdata('error',sprintf($this->lang->line('messages_warning_to_change_password_today')).' <a href="users/changepassword">'.$this->lang->line('application_change_password').'</a>');
                            }else{
                                $this->session->set_flashdata('error',sprintf($this->lang->line('messages_warning_to_change_password').' <a href="users/changepassword">'.$this->lang->line('application_change_password').'</a>',$day_for_expiration));
                            }
                        }
                    }
                }
                if ($login['auth']) {
                    // Salva a quantidade de error do usuário de acesso.
                    $this->setCountErrorLoginRedis($key_redis_count_error_login, null);

                    // SENTRY ID: 591, 581
                    $this->setSessionLogin($login, $sellercenter, $login['login_type'], $need_change_password, $warning_need_change_password, $icn, $pcn);
                    return;                    

                } else {

                    $logged_in_sess = array(
                        'id'        => $login['result']['id'],
                        'username'  => $login['result']['username'],
                        'email'     => $login['result']['email'],
                        'usercomp'  => $login['result']['company_id'],
                        'group_id'  => $this->model_groups->getUserGroupByUserId($login['result']['id']),
                        'logged_in' => FALSE
                    );
                    $this->session->set_userdata($logged_in_sess);
                    if (($login['result']['active']) == 2) {
                        //$this->data['errors'] = $this->lang->line('messages_login_user_inactive');
                        $this->data['errors'] = $this->lang->line('messages_login_login_incorrect');
                        $this->log_data('Auth', 'login', $this->lang->line('messages_login_user_inactive') . $this->postClean('email', TRUE), "W");
                    } else {
                        $this->data['errors'] = $this->lang->line('messages_login_login_incorrect');
                        $this->log_data('Auth', 'login', $this->lang->line('messages_login_login_incorrect') . $this->postClean('email', TRUE), "W");
                    }
                    $this->session->sess_destroy();
                    session_write_close();
                    $this->data['messages_external_login'] = $this->model_externals_authentication->getLoginMessages();
                    $this->data['messages_external_login_icon'] = $this->model_externals_authentication->getLoginIcons();
                    $this->data['redirect_url'] = $this->postClean('redirect_url', true) ?? null;
                    if (!$icn && !$pcn) {
                        $this->load->view($view, $this->data);
                    }

                    // Salva a quantidade de error do usuário de acesso.
                    $this->setCountErrorLoginRedis($key_redis_count_error_login, $count_error_login);
                }
            } else {
                $this->data['errors'] = $this->lang->line('messages_login_login_incorrect');
                $this->data['messages_external_login'] = $this->model_externals_authentication->getLoginMessages();
                $this->data['messages_external_login_icon'] = $this->model_externals_authentication->getLoginIcons();
                $this->data['redirect_url'] = $this->postClean('redirect_url', true) ?? null;
                $this->log_data('Auth', 'login', $this->lang->line('messages_login_email_not_exists') . $this->postClean('email', TRUE), "W");

                if (!$icn && !$pcn) {
                    $this->load->view($view, $this->data);
                }

                // Salva a quantidade de error do usuário de acesso.
                $this->setCountErrorLoginRedis($key_redis_count_error_login, $count_error_login);
            }
        } else {
            // false case

            if (!$icn && !$pcn) {
            	$data_login = array ('sellerCenter' => $sellercenter ); 

				if (isset($policy)) {
					$data_login['policy'] = $policy; 
				}
				$data_login['csrf'] = array(
					'name' => $this->security->get_csrf_token_name(),
					'hash' => $this->security->get_csrf_hash()
				);

				$data_login['redirect_url'] = $this->postClean('redirect_url', true) ?? null;                
                $data_login['messages_external_login'] = $this->model_externals_authentication->getLoginMessages();
                $data_login['messages_external_login_icon'] = $this->model_externals_authentication->getLoginIcons();
                
            	$this->load->view($view, $data_login);
            }
                
        }
        session_write_close();
    }

    protected function setSessionLogin($login, $sellercenter, $login_type = 'login', $need_change_password = false, $warning_need_change_password=false, $icn='', $pcn=''){

        $contractSign = false;
        $block = false;
        if($login['result']['company_id'] != 1){
            if(!$login['result']['store_id']){
                $arrIds = array();
                $stores = $this->model_stores->getCompanyStores($login['result']['company_id']);
                foreach ($stores as $store) array_push($arrIds, $store['id']);
                if($stores){							
                    $contract = $this->model_contract_signatures->getAllCompanyContracts($arrIds);
                    if(isset($contract['id'])){
                        $contractSign = $contract['id'];
                        $block = $contract['block'];
                    } 
                }

            }else{
                $stores = array($login['result']['store_id']);
                if($stores){
                    $contract = $this->model_contract_signatures->getAllCompanyContracts($stores);
                    if(isset($contract['id'])){
                        $contractSign = $contract['id'];
                        $block = $contract['block'];
                    } 
                }
            }
        }

        $logged_in_sess = array(
            'id'                            => $login['result']['id'],
            'username'                      => $login['result']['username'],
            'email'                         => $login['result']['email'],
            'usercomp'                      => $login['result']['company_id'],
            'userstore'                     => $login['result']['store_id'],
            'group_id'                      => $this->model_groups->getUserGroupByUserId($login['result']['id']),
            'logged_in'                     => TRUE,
            'need_change_password'          => $need_change_password,
            'warning_need_change_password'  => $warning_need_change_password,
            'contract_sign'                 => $contractSign,
            'block'                         => $block,
            'legal_administrator'           => $login['result']['legal_administrator'],
            'external_authentication_id'    => $login['result']['external_authentication_id'] 
        );
        $this->session->set_userdata($logged_in_sess);
        
        // pego o token do  do tenant deste sellercenter 
        $token_agidesk = null;
        $token_agidesk_conectala= null;
        if ($this->model_settings->getValueIfAtiveByName('use_agidesk')) {// Usa Agidesk para atendimento
            $tenantsetting = $this->model_settings->getSettingDatabyName('agidesk');
            $tenant = ($tenantsetting) ? $tenantsetting['value'] : '';
            $token_agidesk = $login['result']['token_agidesk'];
            if (!is_null($login['result']['password_agidesk'])) {
                $result = $this->getAgiDeskToken($login['result']['email'], $login['result']['password_agidesk'], $tenant);
                if ($result['httpcode'] != '200') {
                    $this->log_data('Auth', 'login', "Não foi possivel pegar o token AgiDesk. Retorno=" . print_r($result['content'], true), "E");
                } else {
                    $repostaAgiDesk = json_decode($result['content'], true);
                    $token_agidesk = $repostaAgiDesk["access_token"];
                }

            }
        }
        
        // pego o token do agidesk do conectala 
        
        if ($sellercenter == 'conectala') { // se for o conectala, uso o mesmo token e não preciso ir buscar no agidesk 
            $token_agidesk_conectala = $token_agidesk; 
        }
        else {
            $tenantsetting = $this->model_settings->getSettingDatabyName('agidesk_conectala');
            $tenant = ($tenantsetting) ? $tenantsetting['value'] : 'conectala';
            $token_agidesk_conectala = $login['result']['token_agidesk_conectala'];
            if (!is_null($login['result']['password_agidesk_conectala'])) {
                $result = $this->getAgiDeskToken($login['result']['email'], $login['result']['password_agidesk_conectala'], $tenant);
                if ($result['httpcode'] != '200') {
                    $this->log_data('Auth', 'login', "Não foi possivel pegar o token AgiDesk Conectala. Retorno=" . print_r($result['content'], true), "E");
                } else {
                    $repostaAgiDesk = json_decode($result['content'], true);
                    $token_agidesk_conectala = $repostaAgiDesk["access_token"];
                }
            }
        }                   

        // regrava agora com o Token do Agidesk
        $logged_in_sess = array(
            'id'                            => $login['result']['id'],
            'username'                      => $login['result']['username'],
            'email'                         => $login['result']['email'],
            'usercomp'                      => $login['result']['company_id'],
            'userstore'                     => $login['result']['store_id'],
            'group_id'                      => $this->model_groups->getUserGroupByUserId($login['result']['id']),
            'token_agidesk'                 => $token_agidesk,
            'token_agidesk_conectala'       => $token_agidesk_conectala,
            'logged_in'                     => TRUE,
            'need_change_password'          => $need_change_password,
            'contract_sign'                 => $contractSign,
            'block'                         => $block,
            'legal_administrator'           => $login['result']['legal_administrator'],
            'external_authentication_id'    => $login['result']['external_authentication_id'] 
        );
        $this->session->set_userdata($logged_in_sess);
        session_write_close();
        // grava o token e marcar o last_login_date
        $this->model_users->login_update($login['result']['id'], $token_agidesk);

        $get_browser = '';
        if (ini_get('browscap')) {
            $get_browser = json_encode(get_browser());
        }
        get_instance()->log_data('Auth', $login_type, 'Login Autorizado:'.$login['result']['email'].' Dados de ambiente:'.$get_browser, "I");

        if($need_change_password){
            redirect('users/changepassword', 'refresh');
        }
        // if($this->session->set_userdata('requestroute',$_SERVER['REQUEST_URI']);)
        if (!$icn && !$pcn){

            $userData=$this->session->get_userdata();
            if(isset($userData['requestroute'])){
                redirect($userData['requestroute'], 'refresh');
            }else{
                if (strlen($this->postClean('redirect_url', true) ?? '') > 0) {
                    redirect($this->redirectURLHandler($this->postClean('redirect_url', true)), 'location', 302);
                }
                redirect('/dashboard', 'refresh');
            }
        }

    }

    public function loginExternal() {
        $this->viewLoginBanner = $this->model_settings->getSettingDatabyName('tela_login_banner');
        
        if($this->viewLoginBanner && $this->viewLoginBanner['status'] == 1){
            $view = 'login_banner';
        }else{
            $view = 'login';
        }
        $sellercenter = 'conectala';
        $sellerCenterSetting = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($sellerCenterSetting) {
            $sellercenter = $sellerCenterSetting['value'];
        }
        $this->data['sellerCenter'] = $sellercenter;
        
        $this->data['csrf'] = array(
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash()
        );
        if (!isset($this->session->oidc) || !isset($this->session->loginemail)) {            
            $this->data['errors'] = $this->lang->line('messages_login_login_incorrect');
            $this->data['messages_external_login'] = $this->model_externals_authentication->getLoginMessages();
            $this->data['messages_external_login_icon'] = $this->model_externals_authentication->getLoginIcons();
            $this->data['redirect_url'] = $this->postClean('redirect_url', true) ?? null;
            $this->log_data('Auth', 'login_openid', 'Authenticação externa não funcionou', "W");
            $this->load->view($view, $this->data);
            return; 
        }

        $login = $this->model_auth->loginExternal($this->session->loginemail);
        if ($login['auth']) {
            $this->setSessionLogin($login, $sellercenter, 'login_openid');
            return ;                     
        }
        $this->data['errors'] = $this->lang->line('messages_login_login_incorrect');
        if (isset($login['result'])) {
            if (($login['result']['active']) == 2) {
                $this->log_data('Auth', 'login_openid', $this->lang->line('messages_login_user_inactive') . $this->session->loginemail, "W");
            } else {
                $this->log_data('Auth', 'login_openid', $this->lang->line('messages_login_login_incorrect') . $this->session->loginemail, "W");
            }
        } else {
            $this->log_data('Auth', 'login_openid', $this->lang->line('messages_login_email_not_exists') . $this->session->loginemail, "W");
        }
        $this->session->sess_destroy();
        $this->data['messages_external_login'] = $this->model_externals_authentication->getLoginMessages();
        $this->data['messages_external_login_icon'] = $this->model_externals_authentication->getLoginIcons();
        $this->data['redirect_url'] = $this->postClean('redirect_url', true) ?? null;
        $this->load->view($view, $this->data);
    }

    public function passwordReset($mt = '')
    {
        $domain = ($_SERVER['HTTP_HOST']);
        $body = $this->lang->line('application_reset_email_body');
        $this->form_validation->set_rules('email', 'Email', 'required');
        $key = get_instance()->config->config['encryption_key'];
        $email = $this->postClean('email', TRUE);

		$this->viewLoginBanner = $this->model_settings->getSettingDatabyName('tela_login_banner');
		
		if($this->viewLoginBanner && $this->viewLoginBanner['status'] == 1){
            $view = 'login_banner';
        }else{
            $view = 'login';
        }
		$sellercenter = 'conectala';
		$sellerCenterSetting = $this->model_settings->getSettingDatabyName('sellercenter');
		if ($sellerCenterSetting) {
			$sellercenter = $sellerCenterSetting['value'];
		}

		if (file_exists(FCPATH.'assets/skins/'.$sellercenter.'/politica_privacidade.pdf')) { 
			$policy = base_url('assets/skins/'.$sellercenter.'/politica_privacidade.pdf');
			$this->data['policy'] = $policy; 
		}

        if ($this->form_validation->run()) {
            $this->logged_in();
            // true case
            if (trim($email) == '') {
            	sleep(3);
                echo "ok";
                //echo "wrongmail";
				return; 
            }
            $key_redis_count_send_password_reset = "$sellercenter:count_send_email_password:{$email}";
            try {
                $count_send_password_reset = \App\Libraries\Cache\CacheManager::get($key_redis_count_send_password_reset) ?? 0;
            } catch (Exception $exception) {}
            //$count_send_password_reset = 6;
            if ($count_send_password_reset  > 5) {    
                //session_write_close();
                echo "toomanyrequests";
                return ;
            }
            $this->setCountErrorLoginRedis($key_redis_count_send_password_reset, $count_send_password_reset);

            $email_exists = $this->model_auth->check_email($email);
            if ($email_exists) {
                try {
                    $this->authuser->resetPassword($email);
                } catch (Exception $exception) {}
                echo "ok";
            } else {  // email inexistente
            	sleep(3);
//                echo "notfoundemail";
                echo "ok";
            }
        } else {
            $validToken = $this->model_reset_tokens->checkValid($mt);
            if ($validToken) {
                // GERAR TOKEN PELA STRING
                $tokenParsed = (new Parser())->parse($mt); // Parses from a string

                //VERIFICAR
                $data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
                $data->setIssuer($domain);
                $data->setAudience($domain);
                $data->setId($tokenParsed->getClaim('uid')->id ?? null);
                $validated = ($tokenParsed->validate($data));

                $signer = new Sha256();
                $valideSigner = $tokenParsed->verify($signer, $key);

                if ($validated && $valideSigner) {

					$data_login = array (
						'sellerCenter' => $sellercenter,
                        'validated' => $validated,
                        'token' => $mt,
                        'messages_external_login' => []
					); 
					if (isset($policy)) {
						$data_login['policy'] = $policy; 
					}
            		$this->load->view($view, $data_login);

					return;
                }
            }
			
			$this->data['sellerCenter'] = $sellercenter;
			$this->data['errors'] = $this->lang->line('application_invalid_or_expired_token');

            $this->data['messages_external_login'] = [];

			if (isset($policy)) {
				$this->data['policy'] = $policy; 
			}

			$this->data['csrf'] = array(
				'name' => $this->security->get_csrf_token_name(),
				'hash' => $this->security->get_csrf_hash()
			);

			$this->load->view($view, $this->data);
			// redirect('auth/login/'.$this->lang->line('application_invalid_or_expired_token'), $this->data);
			
        }
    }

    public function newPassword()
    {
        $token = $this->postClean('token', TRUE);
        $validToken = $this->model_reset_tokens->checkValid($token);
        if ($validToken) {
            $newpassword = $this->postClean('newPassword', TRUE);
            $domain = ($_SERVER['SERVER_NAME']);
            $key = get_instance()->config->config['encryption_key'];

            // GERAR TOKEN PELA STRING
            $tokenParsed = (new Parser())->parse($token); // Parses from a string

            //VERIFICAR
            $data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
            $data->setIssuer($domain);
            $data->setAudience($domain);
            $data->setId($tokenParsed->getClaim('uid')->id);
            $validated = ($tokenParsed->validate($data));
			$error = $this->lang->line('application_invalid_or_expired_token');
            $signer = new Sha256();
            $valideSigner = $tokenParsed->verify($signer, $key);

            if ($validated && $valideSigner) {
                $id = $tokenParsed->getClaim('uid')->id ?? null;
				$error = '';
				if (!$this->passwordStrenght($newpassword)){
					$error .= $this->lang->line('messages_password_strenght_profile').'<br>';
				}

				$password = password_hash($newpassword, PASSWORD_DEFAULT);
	            $prev_passwords = $this->model_users->getUserData($id);

	   			// verifica se é a senha atual
				if (password_verify($newpassword, $prev_passwords['password'])) {
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
						if ( password_verify($newpassword, $prv_password['password'])) {
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
				
					$pass = $this->model_users->deleteUserPass($id);
					$update = $this->model_users->edit($data, $id);
				
                	get_instance()->log_data('Users', 'reset password by forgot my password', json_encode($data), "W");
                	$this->model_reset_tokens->markAsUsed($token);

                	// Não dá mais login automático $this->login($id,$newpassword);

               		echo 1;
                	exit();
                }
				echo $error;
				exit();
            }
            echo $error;
            exit();
        } else {
            echo $this->lang->line('application_invalid_or_expired_token');
            exit();
        }
    }

    /*
     clears the session and redirects to login page
     */
    public function logout()
    {
        $get_browser = '';
        if (ini_get('browscap')) {
            $get_browser = json_encode(get_browser());
        }
        $value = 'Logout email: '.$this->session->userdata['email'].' '.$get_browser;
        get_instance()->log_data('Auth', 'logout '.$this->session->userdata['email'], $value, "I");

        if (isset($this->session->oidc)) {   
            // aqui tiraria a permissão do single sing-on
//            $oidc = $this->session->oidc;
//            $oidc->revokeToken($oidc->getAccessToken());            
        }

        $this->session->sess_destroy();

        redirect('auth/login', 'refresh');
    }

	public function passwordStrenght($password)
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8 || strlen($password) > 16) {
            return false;
        } 
        return true;
    }

    public function getAgiDeskToken($email, $password, $tenant)
    {
    	
		if ($tenant !== 'conectala') {
			$url = "https://".$tenant.".agidesk.com/api/v1/auth/token";
		}
		else {
			$url = "https://agidesk.com/api/v1/auth/token";
		}
		
        //$url = "https://agidesk.com/api/v1/auth/token";
        $dados = "username=" . $email . "&password=" . $password . "&grant_type=password";

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'X-Tenant-ID: '.$tenant));
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT_MS, 300);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $dados);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        //	curl_setopt($curl_handle,CURLOPT_HTTPHEADER,array('Content-Type:application/json'));
        $content = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        $err = curl_errno($curl_handle);
        $errmsg = curl_error($curl_handle);
        $header = curl_getinfo($curl_handle);
        curl_close($curl_handle);
        $header['httpcode'] = $httpcode;
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;

    }

    protected function redirectURLHandler(string $redirectUrl, array $options = []): string
    {
        try {
            // SENTRY ID: 580
            require_once APPPATH . 'libraries/Helpers/URL.php';
            return (new \Libraries\Helpers\URL($redirectUrl))->addQuery(
                $options['query'] ?? []
            )->getURL();
        } catch (Throwable $e) {

        }
        return $redirectUrl;
    }

    public function setCountErrorLoginRedis(string $key, ?int $count = 0)
    {
        // Se for nulo, precisa remover.
        if (is_null($count)) {
            try {
                \App\Libraries\Cache\CacheManager::delete(array($key));
            } catch (Exception $exception) {}
        } else {
            $count++;

            try {
                \App\Libraries\Cache\CacheManager::setex($key, $count);
            } catch (Exception $exception) {}
        }
    }
}
