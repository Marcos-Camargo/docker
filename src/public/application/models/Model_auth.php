<?php 
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Controle de Acesso

*/  

class Model_auth extends CI_Model
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('model_users');
		$this->load->model('model_externals_authentication');
	}

	/* 
		This function checks if the email exists in the database
	*/
	public function check_email($email) 
	{
		if($email) {
			$result = $this->model_users->getUserByEmail($email); 
			return ($result) ? true : false;
		}

		return false;
	}

	/* 
		This function checks if the email and password matches with the database
	*/
	public function login($email, $password) {
		if($email && $password) {
			$result = $this->model_users->getUserByEmail($email); 
			if($result) {
				$user = $result[0];
				if ($user['active'] == 2) {  // Usuário Inativo 
					$hash_password = password_verify('lsjlkajsdkljalk', '233339302323092323');
					return array('auth' => false, 'result' => $user, 'login_type' => 'login' );
				}
				if (is_null($user['external_authentication_id'])){ // autenticação interna 
					$hash_password = password_verify($password, $user['password']);
					if($hash_password === true) {
						return array('auth' => true, 'result' => $user, 'login_type' => 'login' );
					}
					else {
						return array('auth' => false, 'result' => $user, 'login_type' => 'login' );
					}
				}
				// autenticação externa 
				$login_ok = $this->model_externals_authentication->externalLogin($user['external_authentication_id'], $user, $password);				
				return array('auth' => $login_ok['auth'], 'result' => $user, 'login_type' => 'login_external' );
				
			}
			$hash_password = password_verify('lsjlkajsdkljalk', '233339302323092323');
            return array('auth' => false , 'login_type' => 'login' );

		}
	}

	public function loginExternal($email) {
		if($email) {
			$result = $this->model_users->getUserByEmail($email); 
			if($result) {
				$user = $result[0];
				if ($user['active'] == 2) {  // Usuário Inativo 
					$hash_password = password_verify('lsjlkajsdkljalk', '233339302323092323');
					return array('auth' => false, 'result' => $user);
				}
				return array('auth' => true, 'result' => $user);			
			}
			$hash_password = password_verify('lsjlkajsdkljalk', '233339302323092323');
            return array('auth' => false);
		}
	}
	
}