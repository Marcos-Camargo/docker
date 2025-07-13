<?php
/*

Cria usuário no agidesk 

*/

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @property Model_users $model_users
 * @property Model_company $model_company
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Client $client
 */
class AgideskCriarContatos extends BatchBackground_Controller
{
	/**
	 * @var Client Cliente of GuzzleHttp
	 */
    private $client;

    /**
     * @var string Url base para as requisições
     */
    private $base_uri = null;

    /**
     * @var string Token de acesso para as requisições
     */
    private $access_token = null;

    /**
     * @var string Tenant da conta agidesk.
     */
    private $tenant = null;

	var $sellercenter = 'conectala'; 
	
    public function __construct()
    {
        parent::__construct();
        
        $logged_in_sess = array(
            'id' 		=> 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' 	=> 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
        // carrega os modulos necessários para o Job
        $this->load->model('model_users');
        $this->load->model('model_company');
        $this->load->model('model_settings');
		$this->load->model('model_integrations');
		$this->setClientGuzzle();
    }

	/**
	 * Define a instância Client de GuzzleHttp
	 */
	private function setClientGuzzle()
	{
		$this->client = new Client([
			'verify' => false, // no verify ssl
			'timeout' => 10000,
		]);
	}

    private function setBaseUri(string $tenant)
    {
        if ($tenant !== 'conectala') {
            $this->base_uri = "https://$tenant.agidesk.com/api/v1";
        } else {
            $this->base_uri = "https://agidesk.com/api/v1";
        }

        $this->setTenant($tenant);
    }

    private function getBaseUri(string $path): string
    {
        return $this->base_uri.$path;
    }

    private function setTenant(string $tenant)
    {
        $this->tenant = $tenant;
    }

    private function getTenant(): string
    {
        return $this->tenant;
    }
    
    private function setAccessToken(string $access_token)
    {
        $this->access_token = $access_token;
    }

    private function getAccessToken(): string
    {
        return $this->access_token;
    }

	// php index.php BatchC/AgideskCriarContatos run 
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			get_instance()->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		
		$sellerCenterSetting = $this->model_settings->getSettingDatabyName('sellercenter');
		if ($sellerCenterSetting) {
			$this->sellercenter = $sellerCenterSetting['value'];
		}

        if ($this->model_settings->getValueIfAtiveByName('use_agidesk')) { // se usa o agidesk cria os usuários lá
            if ($this->model_settings->getValueIfAtiveByName('agidesk_default_role')){
                $this->createAgents();
                $this->makeContactAgent();
            } else {
                $this->criarContatos();  // cria os contatos na minha área normal.
            }
		}
		else {
			echo " Parametro use_agidesk inativo então este sellercenter não usará o Agidesk para chamados de lojistas\n";
		}

		if ($this->sellercenter!='conectala') {  // cria os contatos no conectalá também se não for o nosso sellercenter
			$this->criarContatosConectala(); // mas, mesmo que não use, cria no agidesk da Conectala
		}
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function criarContatosConectala()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 52, ordens que já tem rastrio no bling e envia para o Bling  

		$users =$this->model_users->getUsersWithoutAgiDeskPassword(false);
		if (empty($users)) {
			$this->log_data('batch',$log_name,'Nenhum usuário pedente de criação no AgiDesk',"I");
			return ;
		}
		
		$tenantConectala = $this->model_settings->getSettingDatabyName('agidesk_conectala');
		$userAgideskConectala = $this->model_settings->getSettingDatabyName('user_agidesk_conectala');
		$passAgideskConectala = $this->model_settings->getSettingDatabyName('pass_agidesk_conectala');
		if (!$tenantConectala) {
			$this->errorMessage($log_name,"Falta cadastrar 'agidesk_conectala' nos parâmetros do sistema");
			return;
		}
		if (!$userAgideskConectala) {
			$this->errorMessage($log_name,"Falta cadastrar 'user_agidesk_conectala' nos parâmetros do sistema. ");
			return;
		}
		if (!$passAgideskConectala) {
			$this->errorMessage($log_name,"Falta cadastrar 'pass_agidesk_conectala' nos parâmetros do sistema. ");
			return;
		}

        $this->setBaseUri($tenantConectala['value']);

        $email_api = $userAgideskConectala['value'];
        $senha_api = $passAgideskConectala['value'];

        try {
            $this->getAgiDeskToken($email_api, $senha_api);
        } catch (Exception $exception) {
            $this->errorMessage($log_name, $exception->getMessage());
            return;
        }
		
		foreach ($users as $user) {
			echo 'usuário ='.$user['email']."\n"; 
			if (strpos($user['email'], "@conectala.com.br") > 0) {
				echo "Usuário do conectalá. Pulando\n";
				continue;
			}
			$empresa = $this->model_company->getCompanyData($user['company_id']);
			$password = ($user['password_agidesk_conectala'] == null || $user['password_agidesk_conectala'] == "") ? $this->random_pwd() : $user['password_agidesk_conectala'];

			$dados = array(
                "fullname" => $user['firstname']." ".$user['lastname']
            );

			if ($empresa['pj_pf'] == 'PJ') {
				if (is_null($empresa['raz_social'])) {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem Razão social. Pulando\n";
					continue;
				}
				if (trim($empresa['CNPJ']) == '') {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem CNPJ. Pulando\n";
					continue;
				}

                $dados['customertitle'] = $empresa['raz_social'];
                $dados['customercode'] = $empresa['CNPJ'];
			} else {
				if (is_null($empresa['name'])) {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem Nome. Pulando\n";
					continue;
				}
				if (trim($empresa['CPF']) == '') {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem CPF. Pulando\n";
					continue;
				}

                $dados['customertitle'] = $empresa['name'];
                $dados['customercode'] = $empresa['CPF'];
			}
			
			$dados['email'] = $user['email'];
			$dados['password'] = $password;
			$dados['status_id'] = 2;
			$dados['step'] = 'tour';
			$dados['notify'] = 0;
			$dados['api'] = 1;

            $this->model_users->update(array('password_agidesk_conectala' => $password), $user['id']);
			
			echo 'Criando usuário ='.$user['email']."\n";

            try {
                $repostaAgiDesk = $this->createuser($dados);
            } catch (Exception $exception) {
                continue;
            }

            try {
                $user_token = $this->getAgiDeskTokenUser($user['email'], $password);

                if (!empty($user_token)) {
                    $this->model_users->update(array('token_agidesk_conectala' => $user_token), $user['id']);
                    echo "Token Agidesk atualizado para o usuário $user[email]\n";
                }
            } catch (Exception $exception) {
                echo "Erro ao buscar Token Agidesk \n";
                // excluir o usuario com primeiro token token_agidesk_conectala
                try {
                    $this->deleteUserAgidesk($repostaAgiDesk['id']);
                    $repostaAgiDesk = $this->createuser($dados);
                    if (!empty($repostaAgiDesk)) {
                        //Atualizar senha no agidesk e na base de dados
                        $user_access_token = $this->updatePasswordAgidesk($repostaAgiDesk['id'], $password);
                        $this->model_users->update(array('token_agidesk_conectala' => $user_access_token), $user['id']);
                    }
                } catch (Exception $exception) {
                    continue;
                }

                echo "Usuário criado com sucesso $user[email]\n";
            }
    	} 
	}
	
	function criarContatos()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//leio os pedidos com status paid_status = 52, ordens que já tem rastrio no bling e envia para o Bling  
		
		$users =$this->model_users->getUsersWithoutAgiDeskPassword();
		if (count($users)==0) {
			$this->log_data('batch',$log_name,'Nenhum usuário pedente de criação no AgiDesk',"I");
			return ;
		}
		$tenant = $this->model_settings->getSettingDatabyName('agidesk');
		$userAgidesk = $this->model_settings->getSettingDatabyName('user_agidesk');
		$passAgidesk = $this->model_settings->getSettingDatabyName('pass_agidesk');

		if (!$tenant) {
			$this->errorMessage($log_name,"Falta cadastrar 'agidesk' nos parâmetros do sistema");
			return;
		}
		if (!$userAgidesk) {
			$this->errorMessage($log_name,"Falta cadastrar 'user_agidesk' nos parâmetros do sistema");
			return;
		}
		if (!$passAgidesk) {
			$this->errorMessage($log_name,"Falta cadastrar 'pass_agidesk' nos parâmetros do sistema");
			return;
		}

        $this->setBaseUri($tenant['value']);

        $email_api = $userAgidesk['value'];
        $senha_api = $passAgidesk['value'];

        try {
            $this->getAgiDeskToken($email_api, $senha_api);
        } catch (Exception $exception) {
            $this->errorMessage($log_name, $exception->getMessage());
            return;
        }

		foreach ($users as $user) {
			echo 'usuário ='.$user['email']."\n"; 
			
			$empresa = $this->model_company->getCompanyData($user['company_id']);
			$password = ($user['password_agidesk'] == null || $user['password_agidesk'] == "") ? $this->random_pwd() : $user['password_agidesk'];

            $dados = array(
                'fullname' => $user['firstname']." ".$user['lastname']
            );

			if ($empresa['pj_pf'] == 'PJ') {
				if (is_null($empresa['raz_social'])) {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem Razão social. Pulando\n";
					continue;
				}
				if (trim($empresa['CNPJ']) == '') {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem CNPJ. Pulando\n";
					continue;
				}

                $dados['customertitle'] = $empresa['raz_social'];
                $dados['customercode'] = $empresa['CNPJ'];
			} else {
				if (is_null($empresa['name'])) {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem Nome. Pulando\n";
					continue;
				}
				if (trim($empresa['CPF']) == '') {
					echo "Empresa ".$empresa['id']." ".$empresa['name']." sem CPF. Pulando\n";
					continue;
				}
                $dados['customertitle'] = $empresa['name'];
                $dados['customercode'] = $empresa['CPF'];
			}
			
			$dados['email'] = $user['email'];
			$dados['password'] = $password;
			$dados['status_id'] = 2;
			$dados['step'] = 'tour';
			$dados['notify'] = 0;
			$dados['api'] = 1;

            $this->model_users->update(array('password_agidesk'=>$password), $user['id']);

            try {
			    $repostaAgiDesk = $this->createuser($dados);
            } catch (Exception $exception) {
                continue;
            }

			// pego o token do fulano a primeira vez
            try {
                $user_token = $this->getAgiDeskTokenUser($user['email'], $password);

                if (!empty($user_token)) {
                    $this->model_users->update(array('token_agidesk' => $user_token), $user['id']);
                    echo "Token Agidesk atualizado para o usuário $user[email]\n";
                }
            } catch (Exception $exception) {
                echo "Erro ao buscar Token Agidesk \n";
                // excluir o usuario com primeiro token token_agidesk
                try {
                    $this->deleteUserAgidesk($repostaAgiDesk['id']);
                    $repostaAgiDesk = $this->createuser($dados);
                    if (!empty($repostaAgiDesk)) {
                        //Atualizar senha no agidesk e na base de dados
                        $user_access_token = $this->updatePasswordAgidesk($repostaAgiDesk['id'], $password);
                        $this->model_users->update(array('token_agidesk' => $user_access_token), $user['id']);
                    }
                } catch (Exception $exception) {
                    continue;
                }

                echo "Usuário criado com sucesso $user[email]\n";
            }
        }
	}
	
	public function errorMessage($log_name, $msg) {
		echo $msg."\n";
		$this->log_data('batch',$log_name,$msg,"E");
	}
		
	public function createuser($dados)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        try {
            return $this->criaContatoAgidesk($dados);
        } catch (Exception $exception) {
            $this->errorMessage($log_name,'ERRO na criação de Contatos no AgiDesk '. $this->getTenant().'. RESPOSTA AgiDesk: '.$exception->getMessage().' DADOS ENVIADOS: '.$dados);
            throw new Exception($exception->getMessage());
        }
	}

	public function getAgiDeskToken($email, $password)
	{
        try {
            $options = array(
                'headers' => array(
                    'X-Tenant-ID' => $this->getTenant()
                )
            );

            $request = $this->client->post($this->getBaseUri('/auth/token'), array_merge($options,
                array(
                    'form_params' => array(
                        'grant_type'    => 'password',
                        'password'      => $password,
                        'username'      => $email
                    )
                )
            ));

            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['access_token'])) {
                throw new Exception("Id da equipe não encontrada" . json_encode($response) . " - " . json_encode(array('grant_type' => 'password', 'password' => $password, 'username' => $email)));
            }

            $this->setAccessToken($response['token_type'] . ' ' . $response['access_token']);
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
	}

	public function criaContatoAgidesk($data)
    {
        try {
            $options = array(
                'headers' => array(
                    'X-Tenant-ID' => $this->getTenant(),
                    'Authorization' => $this->getAccessToken()
                )
            );

            $request = $this->client->post($this->getBaseUri('/contacts'), array_merge($options,
                array(
                    'json' => $data
                )
            ));

            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['id'])) {
                throw new Exception("Id do contato não encontrado");
            }

            return $response;
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
	}

    /**
     * Recuperar token do usuário.
     * 
     * @param string $email
     * @param string $password
     * @return string
     * @throws Exception
     */
  	public function getAgiDeskTokenUser(string $email, string $password): string
    {
        try {
            $options = array(
                'headers' => array(
                    'X-Tenant-ID' => $this->getTenant()
                )
            );

            $request = $this->client->post($this->getBaseUri('/auth/token'), array_merge($options,
                array(
                    'form_params' => array(
                        'grant_type'    => 'password',
                        'password'      => $password,
                        'username'      => $email
                    )
                )
            ));

            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['access_token'])) {
                throw new Exception("Acess token não encontrado para o $email");
            }

            return $response['access_token'];
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            $error_message = $exception->getMessage();
            $exp_error = explode("response:\n", $error_message);
            if (count($exp_error) == 1) {
                throw new Exception($error_message, $exception->getCode());
            }
            throw new Exception($exp_error[1], $exception->getCode());
        }
    }

	public function deleteUserAgidesk($id_usuario)
    {
        $options = array(
            'headers' => array(
                'X-Tenant-ID' 	=> $this->getTenant(),
                'Authorization' => $this->getAccessToken()
            )
        );

        try {
            $request = $this->client->patch($this->getBaseUri('/agents'), array_merge($options, array(
                'json' => array(
                    'method' => 'delete_contact',
                    'items[0][id]' => $id_usuario
                ),
                'query' => array(
                    'app_scope' => 'admin'
                )
            )));

            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['id'])) {
                throw new Exception("ID do agente não encontrado. ".json_encode($response));
            }
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

	public function updatePasswordAgidesk($id_usuario, $password)
    {
        $options = array(
            'headers' => array(
                'X-Tenant-ID' 	=> $this->getTenant(),
                'Authorization' => $this->getAccessToken()
            )
        );

        try {
            $request = $this->client->patch($this->getBaseUri("users/$id_usuario/password"), array_merge($options, array(
                'json' => array(
                    'password' => $password,
                    'confirmpassword' => $password
                ),
                'query' => array(
                    'app_scope' => 'admin'
                )
            )));

            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['access_token'])) {
                throw new Exception("access_token do contato não encontrado. ".json_encode($response));
            }

            return $response['access_token'];
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

	private function createAgents() {

		$id_group_permission = $this->model_settings->getValueIfAtiveByName('agidesk_default_role');

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$users = $this->model_users->getUsersWithoutAgiDeskPassword();

		if (count($users) == 0) {
			$this->errorMessage($log_name, 'Nenhum usuário pendente de criação no AgiDesk');
			return;
		}

		$tenant = $this->model_settings->getSettingDatabyName('agidesk');
		$userAgidesk = $this->model_settings->getSettingDatabyName('user_agidesk');
		$passAgidesk = $this->model_settings->getSettingDatabyName('pass_agidesk');

		if (!$tenant) {
			$this->errorMessage($log_name, "Falta cadastrar 'agidesk' nos parâmetros do sistema");
			return;
		}
		if (!$userAgidesk) {
			$this->errorMessage($log_name, "Falta cadastrar 'user_agidesk' nos parâmetros do sistema");
			return;
		}
		if (!$passAgidesk) {
			$this->errorMessage($log_name, "Falta cadastrar 'pass_agidesk' nos parâmetros do sistema");
			return;
		}

        $this->setBaseUri($tenant['value']);

		$email_api = $userAgidesk['value'];
		$senha_api = $passAgidesk['value'];

        try {
            $this->getAgiDeskToken($email_api, $senha_api);
        } catch (Exception $exception) {
            $this->errorMessage($log_name, $exception->getMessage());
            return;
        }

		foreach ($users as $user) {
			$company = $this->model_company->getCompanyData($user['company_id']);
			$password = empty($user['password_agidesk']) ? $this->random_pwd() : $user['password_agidesk'];

			$response_integrations = $this->model_integrations->getIntegrationsbyCompanyId($company['id']);

			$seller_id = null;
			if (!empty($response_integrations)) {
				$auth_data = $response_integrations[0]['auth_data'];
				$auth_data_array = json_decode($auth_data, true);
				if (isset($auth_data_array['seller_id'])) {
					$seller_id = $auth_data_array['seller_id'];
				} else {
					$this->errorMessage($log_name, "Não foi encontrado o SELLER_ID para essa empresa ($company[id]) e usuário ($user[id]). Nâo encontro o seller id na integrations");
					continue;
				}
			} else {
				$this->errorMessage($log_name, "Não foi encontrado o SELLER_ID para essa empresa ($company[id]) e usuário ($user[id]). Sem registro na integrations");
				continue;
			}

			if (empty($seller_id)) {
				$this->errorMessage($log_name, "Não foi encontrado o SELLER_ID para essa empresa ($company[id]) e usuário ($user[id]). Seller id em branco");
				continue;
			}

            // Criar customers
            try {
                $customer_id = $this->getCustomerId($company['raz_social']);
            } catch (Exception $exception) {
                $this->errorMessage($log_name, 'Falha ao criar empresa para ' . $seller_id);
                continue;
            }

            // Criar equipe
			try {
				$teamId = $this->getTeamId($seller_id);
			} catch (Exception $exception) {
				$this->errorMessage($log_name, 'Falha ao criar equipe para ' . $seller_id);
				continue;
			}

            $this->model_users->update(array('password_agidesk' => $password), $user['id']);

			$data = array(
				'scope' 			=> 'agent',
				'fullname' 			=> $user['firstname'] . ' ' . $user['lastname'],
				'email' 			=> $user['email'],
				'password' 			=> $password,
				'teams' 			=> $teamId,
				'roles' 			=> (int)$id_group_permission,
				'status_id' 		=> 2,
				'context_id' 		=> 2,
				'notify' 			=> 0,
				'generatepassword' 	=> 0,
				'customers' 		=> $customer_id
			);

			$agent_id = $this->createAgentUser($data);

			if (!$agent_id) {
				$this->errorMessage($log_name, 'Falha ao criar usuário no AgiDesk: ' . $user['email']);
				continue;
			}

            try {
                $user_token = $this->getAgiDeskTokenUser($user['email'], $password);
            } catch (Exception $exception) {
                $this->errorMessage($log_name, "Falha ao gerar o token da empresa ($company[id]), usuário ([$user[id]] - $user[email]) e seller_id ($seller_id) {$exception->getMessage()}");
                continue;
            }

			if (!empty($user_token)) {
				$this->model_users->update(array('token_agidesk' => $user_token), $user['id']);
				echo "Token Agidesk atualizado para o usuário: $user[email]\n";
			}
		}
	}

	private function makeContactAgent(){

		$id_group_permission = $this->model_settings->getValueIfAtiveByName('agidesk_default_role');

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$users = $this->model_users->getUsersWhereMakeUserAgentIsActive();

		if (count($users) == 0) {
			$this->errorMessage($log_name, 'Nenhum usuário pendente de criação no AgiDesk');
			return;
		}

		$tenant = $this->model_settings->getSettingDatabyName('agidesk');
		$userAgidesk = $this->model_settings->getSettingDatabyName('user_agidesk');
		$passAgidesk = $this->model_settings->getSettingDatabyName('pass_agidesk');

		if (!$tenant) {
			$this->errorMessage($log_name, "Falta cadastrar 'agidesk' nos parâmetros do sistema");
			return;
		}
		if (!$userAgidesk) {
			$this->errorMessage($log_name, "Falta cadastrar 'user_agidesk' nos parâmetros do sistema");
			return;
		}
		if (!$passAgidesk) {
			$this->errorMessage($log_name, "Falta cadastrar 'pass_agidesk' nos parâmetros do sistema");
			return;
		}

        $this->setBaseUri($tenant['value']);

        $email_api = $userAgidesk['value'];
        $senha_api = $passAgidesk['value'];

        try {
            $this->getAgiDeskToken($email_api, $senha_api);
        } catch (Exception $exception) {
            $this->errorMessage($log_name, $exception->getMessage());
            return;
        }

		foreach ($users as $user) {
			$company = $this->model_company->getCompanyData($user['company_id']);
			$token_agidesk = $user['token_agidesk'];

			if (empty($token_agidesk)) {
				echo "Usuário " . $user['id'] . " sem Token cadastrado. Pulando\n";
				continue;
			}

            $response_integrations = $this->model_integrations->getIntegrationsbyCompanyId($company['id']);

            $seller_id = null;
            if (!empty($response_integrations)) {
                $auth_data = $response_integrations[0]['auth_data'];
                $auth_data_array = json_decode($auth_data, true);
                if (isset($auth_data_array['seller_id'])) {
                    $seller_id = $auth_data_array['seller_id'];
                } else {
                    $this->errorMessage($log_name, "Não foi encontrado o SELLER_ID para essa empresa ($company[id]) e usuário ($user[id]).");
                    continue;
                }
            } else {
                $this->errorMessage($log_name, "Não foi encontrado o SELLER_ID para essa empresa ($company[id]) e usuário ($user[id]).");
                continue;
            }

            if (empty($seller_id)) {
                $this->errorMessage($log_name, "Não foi encontrado o SELLER_ID para essa empresa ($company[id]) e usuário ($user[id]).");
                continue;
            }

            try {
                $teamId = $this->getTeamId($seller_id);
            } catch (Exception $exception) {
                $this->errorMessage($log_name, 'Falha ao criar equipe para ' . $seller_id);
                continue;
            }

            $data = array(
                'scope'         => 'agent',
                'setasagent'    => 1,
                'fullname'      => $user['firstname'] . ' ' . $user['lastname'],
                'email'         => $user['email'],
                'teams'         => $teamId,
                'roles'         => $id_group_permission,
                'status_id'     => 2,
                'context_id'    => 2
            );

			$agent_id = $this->createAgentUser($data);

			if (!$agent_id) {
				$this->errorMessage($log_name, 'Falha ao criar usuário no AgiDesk: ' . $user['email'], "E");
				continue;
			}

            $this->model_users->update(array('agidesk_agent_id' => $agent_id), $user['id']);

            echo "Usuário $user[email] virou agente\n";
		}
	}

	private function createAgentUser($data)
    {
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        try {
            return $this->createAgentAgidesk($data);
        } catch (Exception $exception) {
            $this->errorMessage($log_name,"ERRO na criação de Agente no AgiDesk.\nError={$exception->getMessage()}.\nCode={$exception->getCode()}\n" .json_encode($data, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    private function getAgent(string $email): ?int
    {
        $options = array(
            'headers' => array(
                'X-Tenant-ID' 	=> $this->getTenant(),
                'Authorization' => $this->getAccessToken()
            )
        );

        try {
            $request = $this->client->get($this->getBaseUri('/agents'), array_merge($options, array('query' => array(
                'email' => $email,
                'fields' => 'id,email'
            ))));

            $response = json_decode($request->getBody()->getContents(), true);

            if (!empty($response)) {
                foreach ($response as $team) {
                    if ($team['email'] == $email) {
                        return (int)$team['id'];
                    }
                }
            }

            return null;
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $data
     * @return int
     * @throws Exception
     */
    private function createAgentAgidesk($data): int
    {
		try {
            $agent_id = $this->getAgent($data['email']);
            $options = array(
                'headers' => array(
                    'X-Tenant-ID' 	=> $this->getTenant(),
                    'Authorization' => $this->getAccessToken()
                )
            );

            if (empty($agent_id)) {
                $request = $this->client->post($this->getBaseUri('/agents'), array_merge($options, array('json' => $data)));

                $response = json_decode($request->getBody()->getContents(), true);

                if (empty($response['id'])) {
                    throw new Exception("ID do agente não encontrado. " . json_encode($response) . " - " . json_encode($data));
                }

                return (int)$response['id'];
            }

            return $agent_id;
		} catch (Exception | GuzzleException | BadResponseException $exception) {
			throw new Exception($exception->getMessage(), $exception->getCode());
		}
	}

    /**
     * Criar equipe
     *
     * @param   string $team_title
     * @return  int
     * @throws  Exception
     */
    private function createTeam(string $team_title): int
    {
        $options = array(
            'headers' => array(
                'X-Tenant-ID' 	=> $this->getTenant(),
                'Authorization' => $this->getAccessToken()
            )
        );

        try {
            $request = $this->client->post($this->getBaseUri('/teams'), array_merge($options, array('json' => array(
                'title' => $team_title
            ))));

            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['id'])) {
                throw new Exception("Id da equipe não encontrada" . json_encode($response) . " - " . json_encode(array('title' => $team_title)));
            }

            return (int)$response['id'];
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Recuperar código da equipe
     * 
     * @param   string $team_title
     * @return  int
     * @throws  Exception
     */
	private function getTeamId(string $team_title): int
    {
		$options = array(
			'headers' => array(
				'X-Tenant-ID' 	=> $this->getTenant(),
				'Authorization' => $this->getAccessToken()
			)
		);

		try {
			$request = $this->client->get($this->getBaseUri('/teams'), array_merge($options, array('query' => array(
				'title' => $team_title,
                'fields' => 'id,title'
			))));

			$response = json_decode($request->getBody()->getContents(), true);

			if (!empty($response)) {
				foreach ($response as $team) {
					if (strtolower($team['title']) == strtolower($team_title)) {
						return (int)$team['id'];
					}
				}
			}

            return $this->createTeam($team_title);
		} catch (Exception | GuzzleException | BadResponseException $exception) {
			throw new Exception($exception->getMessage(), $exception->getCode());
		}
	}

    /**
     * Criar empresa
     *
     * @param   string $customer_title
     * @return  int
     * @throws  Exception
     */
    private function createCustomer(string $customer_title): int
    {
        $options = array(
            'headers' => array(
                'X-Tenant-ID' 	=> $this->getTenant(),
                'Authorization' => $this->getAccessToken()
            )
        );

        try {
            $request = $this->client->post($this->getBaseUri('/customers'), array_merge($options, array('json' => array(
                'title' => $customer_title
            ))));

            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['id'])) {
                throw new Exception("Id da empresa não encontrada" . json_encode($response) . " - " . json_encode(array('title' => $customer_title)));
            }

            return (int)$response['id'];
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Recuperar código da empresa
     * 
     * @param   string $customer_title
     * @return  int
     * @throws  Exception
     */
	private function getCustomerId(string $customer_title): int
    {
		$options = array(
			'headers' => array(
				'X-Tenant-ID' 	=> $this->getTenant(),
				'Authorization' => $this->getAccessToken()
			)
		);

		try {
			$request = $this->client->get($this->getBaseUri('/customers'), array_merge($options, array('query' => array(
				'title' => $customer_title,
                'fields' => 'id,title'
			))));

			$response = json_decode($request->getBody()->getContents(), true);

			if (!empty($response)) {
				foreach ($response as $customer) {
					if ($customer['title'] == $customer_title) {
						return (int)$customer['id'];
					}
				}
			}

            return $this->createCustomer($customer_title);
		} catch (Exception | GuzzleException | BadResponseException $exception) {
			throw new Exception($exception->getMessage(), $exception->getCode());
		}
	}
}
