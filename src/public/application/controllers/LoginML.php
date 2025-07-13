<?php
/*
 
Controller de Catalogos de Produtos 

*/  
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . "controllers/BatchC/MercadoLivre/Meli.php";

class LoginML extends Admin_Controller 
{
	var $client_id; 
	var $client_secret;
	
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_mercado_livre');

		$this->load->model('model_integrations');
		$this->load->model('model_stores');
	}

	/* 
	* It only redirects to the manage category page
	*/
	
	function getMLClientId() {
		$integration = $this->model_integrations->getIntegrationsbyCompIntType('1','ML',"CONECTALA","DIRECT",'0');
		
		$api_keys = json_decode($integration['auth_data']);
		$this->client_id = $api_keys->client_id;
		$this->client_secret = $api_keys->client_secret;
		
	}
	
	public function index() 
	{
	 	if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }
       
		 if (isset($_GET['error'])) {  // vejo se veio um erro do Mercado livre
		 	$data = $this->input->get();
        	if (key_exists('error_description', $data )){
				$this->session->set_flashdata('error', "Mercado Livre retornou este erro: ". $data['error_description']);
				redirect('loginML/index', 'refresh');	
        	}
			else {
				$this->session->set_flashdata('error', "Mercado Livre retornou este erro: ". print_r($data,true));
				redirect('loginML/index', 'refresh');	
			}
		 }
		
        if(isset($_GET['code'])){  // se veio do Mercado Livre 
        	$data = $this->input->get();
        	if (!key_exists('state', $data )){
        		  redirect('dashboard', 'refresh');
        	}
			$tempexplode = explode("_",$data['state']);
			$store_id = $tempexplode[1];
		}
		else { 
        // pego qual store_id foi escolhida 
	        If (!$this->session->has_userdata('storeml')) {
	        	if ($this->data['userstore'] !=0) { // este usuário só tem uma store_id
					$store_id=$this->data['userstore'];
				}
				else {
					$stores = $this->model_stores->getActiveStore();
					if (count($stores) == 1) {
						$store_id = $stores[0]['id']; // pego o ID da primeira loja
					}
					else {  // tem mais de 1 e ainda não escolheu qual que quer. 
						redirect('loginML/chooseStore', 'refresh');
						return;
					}
				}
	        }
			else {
				$store_id = $this->session->userdata('storeml');
			}
		}
		
		//$this->session->unset_userdata('storeml');
		
		$this->data['store'] = $this->model_stores->getStoresData($store_id);
		$company_id= $this->data['store']['company_id'];
		
	    //$redirectUrl = "https://www.mercadolivre.com.br";
		// $redirectUrl = "https://teste.conectala.com.br/app/loginML";
		$redirectUrl = base_url('LoginML');
		if (ENVIRONMENT !== 'production') {
			$redirectUrl = "https://www.mercadolivre.com.br";
		}
		
		// pego o ID da plataforma
		$this->getMLClientId();
		$meli = new Meli($this->client_id, $this->client_secret);
		$this->data['loginUrl'] = $meli->getAuthUrl($redirectUrl,Meli::$AUTH_URL['MLB'],$company_id.'_'.$store_id);	
		
		// leio a integração desta loja 
		$integration = $this->model_integrations->getIntegrationByIntTo('H_ML', $store_id); 
		
		if(isset($_GET['code'])){  // se veio do Mercado Livre 
       		// já se logou..
			$access_code = $data['code'];
			$state = $data['state'];
			
			$tempexplode = explode("_",$data['state']);
			$store_id = $tempexplode[1];
			
			if ($state !== $company_id.'_'.$store_id) {
				redirect('dashboard', 'refresh');
			}
			
			$this->getMLClientId(); // pego o client Id da plataforma
			$meli = new Meli($this->client_id, $this->client_secret);
			
			// veio um code então posso pegar uma autorização para pegar o refresh token
			$auth = $meli->authorize($access_code ,$redirectUrl ); 
			if ($auth['httpCode']!="200")  {
				$this->session->set_flashdata('error', "Erro na Autorização no Mercado Livre. httpcode=".$auth['httpCode']." Resposta: ".print_r($auth['body'],true));
				redirect('loginML/index', 'refresh');	  
				return;
			}
			
			// gravo no INTEGRATIONS lido antes ou crio um novo (mais normal)
			$this->data['authorize'] = $auth['body'];
			$api_keys = array();
			if ($integration) {
				$api_keys = json_decode($integration['auth_data'], true);
			}
			$api_keys['client_id'] = $this->client_id;
			$api_keys['client_secret'] = $this->client_secret;
			$api_keys['access_token'] = $auth['body']->access_token;
			$api_keys['refresh_token'] =$auth['body']->refresh_token;
			$api_keys['date_refresh'] = $auth['body']->expires_in+time();
			$api_keys['seller'] = substr($auth['body']->refresh_token, strrpos($auth['body']->refresh_token,"-")+1);			
			if ($integration) { 
				$updInt = $this->model_integrations->update(array('auth_data' => json_encode($api_keys)),$integration['id']);
			} else {
				$data_int = array(
					'id' => 0,
					'name' => 'Hub Mercado Livre Premium',
					'active' => 1,
					'store_id' => $store_id, 
					'company_id' => $company_id, 
					'auth_data' => json_encode($api_keys), 
					'int_type' => 'DIRECT',
					'int_from' => 'HUB', 
					'int_to' => 'H_ML',
					'auto_approve' => '1',
				);
				$crtInt = $this->model_integrations->create($data_int);
				$data_int = array(
					'id' => 0,
					'name' => 'Hub Mercado Livre Clássico',
					'active' => 1,
					'store_id' => $store_id, 
					'company_id' => $company_id, 
					'auth_data' => null, 
					'int_type' => 'BLING',
					'int_from' => 'HUB', 
					'int_to' => 'H_MLC',
					'auto_approve' => '1',
				);
				$crtInt = $this->model_integrations->create($data_int);
			}	
			
			// agora vejo se está tudo ok e leio as informações do usuário para mostrar que o Login foi oK 
			$url = 'users/me';
			$params = array(); 
			$retorno = $meli->get($url, $params);
			if ($retorno['httpCode']!="200")  {  // deu algum erro lendo as informações, volto para o login 
				$this->session->set_flashdata('error', "Erro na consulta do usuário no Mercado Livre. httpcode=".$retorno['httpCode']." Resposta: ".print_r($retorno['body'],true));
				redirect('loginML/index', 'refresh');	
				return;
			}
			// tudo ok , vou mostrar o que eu li
			$this->session->unset_userdata('storeml');
			$this->data['user'] = json_decode(json_encode($retorno['body']),true);
			$this->render_template('loginml/view', $this->data);
			return;
		}

		// Não veio do marketplace
		if (!$integration) { // nunca se logou então vou fazer o login
			$this->render_template('loginml/index', $this->data);	
			return;
		}
		else { // já houve integração anterior, então vejo se ainda está valendo
			$api_keys = json_decode($integration['auth_data'], true);
			$meli = new Meli($this->client_id, $this->client_secret, $api_keys['access_token'],  $api_keys['refresh_token']);
			if ($api_keys['date_refresh'] < time()) { // está na hora de fazer refresh no token 
				$auth = $meli->refreshAccessToken();
				if ($auth["httpCode"] == 400) {
					$auth = $meli->authorize($api_keys['refresh_token'] ,$redirectUrl ); 
					if ($auth['httpCode']!="200")  {  // nao consegui fazer o refresh, então tem que fazer login novamente
						$this->session->set_flashdata('error', "Erro na Autorização no Mercado Livre. httpcode=".$auth['httpCode']." Resposta: ".print_r($auth['body'],true));
						redirect('loginML/index', 'refresh');	
						return;  
					}
				}
				// fez refresh ou authorizou novamente, atualiza a integração
				$api_keys['client_id'] = $this->client_id;
				$api_keys['client_secret'] = $this->client_secret;
				$api_keys['access_token'] = $auth['body']->access_token;
				$api_keys['refresh_token'] =$auth['body']->refresh_token;
				$api_keys['date_refresh'] = $auth['body']->expires_in+time();
				$api_keys['seller'] = substr($auth['body']->refresh_token, strrpos($auth['body']->refresh_token,"-")+1);
				$updInt = $this->model_integrations->update(array('auth_data' => json_encode($api_keys)),$integration['id']);
			}
			// consegui me authenticar, vou ler os dados do usuário
			$url = 'users/me';
			$params = ''; 
			$retorno = $meli->get($url, $params);
			if ($retorno['httpCode']!="200")  {  // deu algum erro lendo as informações, volto para o login 
				$this->session->set_flashdata('error', "Erro na consulta do usuário no Mercado Livre. httpcode=".$retorno['httpCode']." Resposta: ".print_r($retorno['body'],true));
				redirect('loginML/index', 'refresh');		  
				return;
			}
			// tudo ok , vou mostrar o que eu li
			$this->session->unset_userdata('storeml');
			$this->data['user'] = json_decode(json_encode($retorno['body']),true);
			$this->render_template('loginml/view', $this->data);
			return;			
		}
	}

	public function view() 
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }
        
		$this->load->view('LoginML/view', $this->data);

	}
	
	public function chooseStore()
	{
        redirect('dashboard', 'refresh');

		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }
        $this->session->unset_userdata('storeml');
		$this->form_validation->set_rules('store', $this->lang->line('application_store'), 'required');
		if ($this->form_validation->run() == TRUE) {
			$this->session->set_userdata('storeml',$this->postClean('store'));
			redirect('loginML/index', 'refresh');
		}
		$this->data['stores'] = $this->model_stores->getActiveStore();
		$this->render_template('loginml/choosestore', $this->data);
	}
	

}