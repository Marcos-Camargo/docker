<?php

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . 'libraries/htmlpurifier/HTMLPurifier.standalone.php';
require_once APPPATH . "libraries/Microservices/v1/Microservices.php";

use GuzzleHttp\Psr7\Request;

/**
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Output $output
 * @property CI_Router $router
 * @property CI_DB_query_builder $db
 * @property CI_Form_validation $form_validation
 * @property CI_Upload $upload
 * @property CI_Encrypt $encrypt
 * @property array $data
 */
class MY_Controller extends CI_Controller
{
	public function __construct()
	{
//        header('X-Frame-Options: deny');
		parent::__construct();
        if (!isset($this->db) && isset($GLOBALS['CI_DB'])) {
            $this->db = $GLOBALS['CI_DB'];
        }
		$this->load->library('session');
		
		$config =& get_config();
		
		if($this->input->cookie('swlanguage') != ""){ 
			$language = $this->input->cookie('swlanguage'); 
			if ($language=="portuguese"){
				$language="portuguese_br";
				$cookie = array(
					'name'   => 'swlanguage',
					'value'  => $language,
					'expire' => '31536000',
				);

                if (array_key_exists('SERVER_ADDR', $_SERVER)) {
                    $this->input->set_cookie($cookie);
                }
			}
		}else{ 
			if(isset($config['language'])){
				$language = $config['language'];
			}else{ 
				$language = "english"; 
			}
			$cookie = array(
				'name'   => 'swlanguage',
				'value'  => $language,
				'expire' => '31536000',
			);

            if (array_key_exists('SERVER_ADDR', $_SERVER)) {
                $this->input->set_cookie($cookie);
            }

		}
		log_message('info', 'mycontroller lang:'.$language);
		
		$this->lang->load('application', $language);
		$this->lang->load('messages', $language);
		$this->lang->load('event', $language);
		
		$this->load->model('model_settings');
		$this->load->model('model_banks');
        $this->load->helper('text');

		if ($this->model_settings->getStatusbyName('security_block_post_more_than_8Kb') && (array_key_exists('REQUEST_METHOD',$_SERVER))) {
			if (($_SERVER['REQUEST_METHOD'] === 'POST') || ($_SERVER['REQUEST_METHOD'] === 'PUT')) {
				$max_size=8*1024;
				$post_size = (int) $_SERVER['CONTENT_LENGTH'];
				if ($post_size > $max_size) {
					$uri_exceptions = array (  // Request_uri, tamanho em bytes			
						'/app/productsloadbycsv/onlyVerify' 			=> 8*1024*1204,
						'/app/billet/uploadArquivo' 					=> 8*1024*1204,
						'/app/billet/uploadarquivoconciliasellercenter'	=> 8*1024*1204,
						'/app/company/upload_image' 					=> 1024*1024,
						'/app/shippingcompany/uploadconfig'				=> 8*1024*1204,
						'/app/products/update/' 						=> 8*1024*1204,
						'/app/products/create'	 						=> 8*1024*1204,
						'/app/company/update/'							=> 2*1024*1024,
						'/app/company/create'							=> 2*1024*1024,
						'/app/stores/update/'							=> 2*1024*1024,
						'/app/catalogProducts/create'					=> 8*1024*1024,
						'/app/catalogProducts/update/'					=> 8*1024*1024,
						'/app/products/upload_attributes_file'			=> 8*1024*1024,
						'/app/orders/loadnfe'							=> 8*1024*1024,
						'/app/templateemail/create'						=> 8*1024*1024,
						'/app/templateemail/update'						=> 8*1024*1024,
						'/app/api/v1/products'							=> 8*1024*1024,
						'/app/api/v1/catalogs/skumanufacturer/'			=> 8*1024*1024,
                        '/app/shopkeeperform/complete/'					=> 8*1024*1024,
                        '/app/integrations/createIntegration'		    => 8*1024*1024,
                        '/app/integrations/updateIntegration/'		    => 8*1024*1024,
						'/financeiro/productsloadbycsv/onlyVerify' 			=> 8*1024*1204,
						'/financeiro/billet/uploadArquivo' 					=> 8*1024*1204,
						'/financeiro/billet/uploadarquivoconciliasellercenter'	=> 8*1024*1204,
						'/financeiro/company/upload_image' 					=> 1024*1024,
						'/financeiro/shippingcompany/uploadconfig'				=> 8*1024*1204,
						'/financeiro/products/update/' 						=> 8*1024*1204,
						'/financeiro/products/create'	 						=> 8*1024*1204,
						'/financeiro/company/update/'							=> 2*1024*1024,
						'/financeiro/company/create'							=> 2*1024*1024,
						'/financeiro/stores/update/'							=> 2*1024*1024,
						'/financeiro/catalogProducts/create'					=> 8*1024*1024,
						'/financeiro/catalogProducts/update/'					=> 8*1024*1024,
						'/financeiro/products/upload_attributes_file'			=> 8*1024*1024,
						'/financeiro/orders/loadnfe'							=> 8*1024*1024,
						'/financeiro/templateemail/create'						=> 8*1024*1024,
						'/financeiro/templateemail/update'						=> 8*1024*1024,
						'/financeiro/api/v1/products'							=> 8*1024*1024,
						'/financeiro/api/v1/catalogs/skumanufacturer/'			=> 8*1024*1024,
                        '/financeiro/shopkeeperform/complete/'					=> 8*1024*1024,
                        '/financeiro/integrations/createIntegration'		    => 8*1024*1024,
                        '/financeiro/integrations/updateIntegration/'		    => 8*1024*1024,
						'/financeiro/contracts/fileUpload' => 8*1024*1024,
						'/app/contracts/fileUpload' => 8*1024*1024,
						'/app/products/attributes/edit/' => 2*1024*1024,
						'/financeiro/products/attributes/edit/' => 2*1024*1024,
                        'campaigns_v2' => 2*1024*1024
												
					);
					foreach ($uri_exceptions as $key => $exception) {
						if (str_contains(strtolower($_SERVER['REQUEST_URI']), $key)) {
							$max_size= $exception;
							if ($post_size > $max_size) {
								$message_400 = "Too much data. ". $_SERVER['REQUEST_URI']." ".$post_size;
								show_error($message_400 , 400 );
								return;
							}
							break;
						}
					}
					// if (array_key_exists(strtolower($_SERVER['REQUEST_URI']), $uri_exceptions)) {
					// 	$max_size= $uri_exceptions[strtolower($_SERVER['REQUEST_URI'])];
					// 	if ($post_size > $max_size) {
					// 		$message_400 = "Too much data. ". $_SERVER['REQUEST_URI']." ".$post_size;
					// 		show_error($message_400 , 400 );
					// 		return;
					// 	}
					// } else {
					// 	$ok = false;
					// 	foreach($uri_exceptions as $a_key => $a_value) {
					// 		if (substr($_SERVER['REQUEST_URI'],0,strlen($a_key)) == $a_key) {
					// 			$ok = $uri_exceptions[$a_key] >=$post_size;
					// 			break;
					// 		}
					// 	}
					// 	if (!$ok) {
					// 		$message_400 = "Too much data. ".$_SERVER['REQUEST_URI']." ".$post_size;
					// 		show_error($message_400 , 400 );
					// 		return;
					// 	}
					// }
				}
			}
		}
	}
	
	function postClean($key = null,$clean = true, $no_replace_quotes = false, $remove_html_tags = true)
	{
		if (is_null($key)) {			
			if (!$clean) {
				return $this->input->post(); 
			}
			return cleanArray($this->input->post(), $no_replace_quotes, $remove_html_tags);
		}	
		else{
			$post = $this->input->post($key); 
			if (is_array($post)) {
				return ($clean) ? cleanArray($post, $no_replace_quotes, $remove_html_tags) : $post;
			}
			else {
				return ($clean) ? xssClean($post, $no_replace_quotes, $remove_html_tags) : $post;
			}			
		}
	}

    static function formatDateBr_En($date)
    {
        if (strlen($date) === 10) {
            return \DateTime::createFromFormat('d/m/Y', $date)->format('Y-m-d');
		}elseif (strlen($date) === 16) {
            return \DateTime::createFromFormat('d/m/Y H:i', $date)->format('Y-m-d\TH:i');
		}elseif (strlen($date) === 19) {
            return \DateTime::createFromFormat('d/m/Y H:i:s', $date)->format('Y-m-d\TH:i:s');
		}
        return false;
    }

    static function getStateNameByUF($uf)
    {
        $states = array(
            'AC'=>'Acre',
            'AL'=>'Alagoas',
            'AP'=>'Amapá',
            'AM'=>'Amazonas',
            'BA'=>'Bahia',
            'CE'=>'Ceará',
            'DF'=>'Distrito Federal',
            'ES'=>'Espírito Santo',
            'GO'=>'Goiás',
            'MA'=>'Maranhão',
            'MT'=>'Mato Grosso',
            'MS'=>'Mato Grosso do Sul',
            'MG'=>'Minas Gerais',
            'PA'=>'Pará',
            'PB'=>'Paraíba',
            'PR'=>'Paraná',
            'PE'=>'Pernambuco',
            'PI'=>'Piauí',
            'RJ'=>'Rio de Janeiro',
            'RN'=>'Rio Grande do Norte',
            'RS'=>'Rio Grande do Sul',
            'RO'=>'Rondônia',
            'RR'=>'Roraima',
            'SC'=>'Santa Catarina',
            'SP'=>'São Paulo',
            'SE'=>'Sergipe',
            'TO'=>'Tocantins'
        );

        return $states[$uf];
    }
}

class Admin_Controller extends MY_Controller 
{
	var $permission = array();

	public function __construct() 
	{
		parent::__construct();

		$group_data = array();
		if(empty($this->session->userdata('logged_in'))) {
			$session_data = array('logged_in' => FALSE);
			$this->session->set_userdata($session_data);
     		$this->load->model('model_company');
			$company = $this->model_company->getCompanyData(1);
			$this->session->set_userdata(array('logo' => $company['logo']));
      
            $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
            if($settingSellerCenter && $settingSellerCenter['name'] == NULL){
                $skin = 'default';
            } else{
                $skin = $settingSellerCenter['value'];
            }

            /*
            * Verificação se existe a pasta correspondente a skin da company(empresa)
            */
            if(is_dir("assets/skins/".$skin)){
                $this->session->set_userdata(array('skin' => $skin));
            }else{
                $this->session->set_userdata(array('skin' => 'default'));
            }

            $layout = $this->model_settings->getSettingDatabyName('layout_seller_center');

            if($layout['status'] == '1')
            {
              $this->session->set_userdata('layout', $settingSellerCenter);
            }
		} else {
            $this->load->model('model_company');
            $this->load->model('model_groups');
            $this->load->model('model_settings');
            $this->load->model('model_gateway_settings');
			$this->load->model('model_user_link_training');

			$user_id = $this->session->userdata('id');
			$usercomp = $this->session->userdata('usercomp');
			$this->data['usercomp'] = $usercomp;
			$userstore = $this->session->userdata('userstore');
			$this->data['userstore'] = $userstore;

			$company = $this->model_company->getCompanyData($usercomp);
			$this->session->set_userdata(array('company_logo' => $company['logo']));
			$this->session->set_userdata(array('currency' => $this->company_currency($usercomp)));

			$settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

            $link_video_trainning = $this->model_settings->getSettingDatabyName('link_video_trainning');

			$this->data['data']['permissionLink'] = "";
			
            if(isset($link_video_trainning) && $link_video_trainning['status'] == '1')
            {
                if($this->uri->segment(3) !== null)
                {
                    $this->data['data']['permissionLink'] = $this->model_user_link_training->getLinkTrainingVideo($this->router->fetch_method(), $this->uri->segment(3));	
                }else{
                    $this->data['data']['permissionLink'] = $this->model_user_link_training->getLinkTrainingVideo($this->router->fetch_class(), $this->router->fetch_method());	
                }
            }
            $this->data['data']['settingSellerCenter'] = $settingSellerCenter['value'];
        
			
			$group_data = $this->model_groups->getUserGroupByUserId($user_id);
	
			$this->data['user_permission'] = isset($group_data['permission']) ? unserialize($group_data['permission']) : "";
			$this->permission = isset($group_data['permission']) ? unserialize($group_data['permission']) : "";
			$this->session->set_userdata(Array('group_id' => $group_data['group_id']));
			$usergroup = $this->session->userdata('group_id');
			$this->data['usergroup'] = $usergroup;
			$this->data['only_admin'] = isset($group_data['only_admin']) ? $group_data['only_admin'] : "";
			
			$this->data['mycompanies'] = $this->model_company->getMyCompanyData();
			$filters = Array(); 
		    foreach ($this->data['mycompanies'] as $k => $v) {
		    	array_push($filters,$v['id']);
			}
			$this->data['filters'] = $filters;

            $layout = $this->model_settings->getSettingDatabyName('layout_seller_center');
		
            if($layout['status'] == '1')
            {
                $this->session->set_userdata('layout', $settingSellerCenter);
            }

			$siteAgidesk=$this->model_settings->getSettingDatabyName('agidesk');
			$this->data['site_agidesk'] = ($siteAgidesk) ? $siteAgidesk['value'] : null;
			$this->data['token_agidesk'] = $this->session->userdata('token_agidesk');
			$this->data['user_group_id'] = $this->session->userdata('group_id');
			$this->data['token_agidesk_conectala'] = $this->session->userdata('token_agidesk_conectala');
            
            $this->data['gsoma_painel_financeiro'] = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
            $this->data['novomundo_painel_financeiro'] = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
			$this->data['ortobom_painel_financeiro'] = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
			$this->data['casaevideo_painel_financeiro'] = $this->model_settings->getSettingDatabyName('casaevideo_painel_financeiro');
           
        }

		if ($this->model_settings->getValueIfAtiveByName('use_ms_shipping')) {
		    $this->load->library("Microservices\\v1\\Microservices", array(), 'microservices');
		}

		if ($this->model_settings->getValueIfAtiveByName('use_version_git_tag')) {
            $this->data['version'] = $this->getVersion();
        }

        $this->data['show_version_git_tag'] = $this->model_settings->getValueIfAtiveByName('show_version_git_tag');
    }

	protected function requestMs($method, $url) {
        $keycloak_token = $this->microservices->authenticatorKeycloak();

        $headers = [ 'Authorization' => $keycloak_token->token_type . ' ' . $keycloak_token->access_token];
        return new Request($method, $url, $headers);
    }

    public function onlyNumbers($string)
    {
        return preg_replace("/[^0-9]/", "", $string);
    }


	public function logged_in()
	{
		$session_data = $this->session->userdata();
		if($session_data['logged_in'] == TRUE) {
			redirect('dashboard', 'refresh');
		}
	}

	public function not_logged_in()
	{
		$session_data = $this->session->userdata();
		if($_SERVER['REQUEST_URI']=='/anymarket/config'){
			$this->session->set_userdata('requestroute',$_SERVER['REQUEST_URI']);
		}

		if (array_key_exists('id', $session_data)) {
			$this->load->model('model_users');
			$user = $this->model_users->getUserById($session_data['id']);
			if ($user['active'] != 1) {  // o usuário foi inativado, então derrubo ele  
				$session_data['logged_in'] = FALSE;				
			}
		}
		
        if ($session_data['logged_in'] == FALSE) {
            $url = 'auth/login';
            try {
                require_once APPPATH . 'libraries/Helpers/URL.php';
                $url = (new \libraries\Helpers\URL(base_url('auth/login')))->addQuery([
                    'redirect_url' => \libraries\Helpers\URL::retrieveServerCurrentURL()
                ])->getURL();
            } catch (Throwable $e) {
				// nothing to do 
            }
            redirect($url, 'refresh');
        }
	}

	public function render_template($page = null, $data = array())
	{
		
		$data['Preco_Quantidade_Por_Marketplace']= $this->model_settings->getStatusbyName('price_qty_by_marketplace');
		$data['tranning_url'] = $this->model_settings->getValueIfAtiveByName('tranning_url');
		$data['use_agidesk'] = $this->model_settings->getStatusbyName('use_agidesk');
		$data['link_atendimento_externo_status'] = $this->model_settings->getStatusbyName('link_atendimento_externo');
		$data['link_atendimento_externo'] = $this->model_settings->getValueIfAtiveByName('link_atendimento_externo');
		$data['fac_url'] = $this->model_settings->getValueIfAtiveByName('fac_url');
		$data['report_problem_url'] = $this->model_settings->getValueIfAtiveByName('report_problem_url');
 		$data['gsoma_painel_financeiro'] = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $data['novomundo_painel_financeiro'] = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $data['collection_occ'] = $this->model_settings->getStatusbyName('collection_occ');
		
        // Paramêtro para controlar a inserção de script hotjar
        $data['hotjar_id'] = $this->model_settings->getSettingDatabyName('enable_and_show_hotjar_script');

        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
		}
        $menuMetabse_seller = array();
        $menuMetabse_adm = array();
        $language = $this->input->cookie('swlanguage') == 'portuguese_br' ? 'title_br' : 'title_en' ;
		
		$data['hasReportGroupsAdmin'] = false;
		$data['hasReportGroups'] = false;

        $groupIdUser    = $this->session->userdata('group_id');
		
        $datasMetabase = $this->db->query("SELECT * FROM `reports_metabase` where active = 1 order by {$language}")->result_array();
        foreach ($datasMetabase as $metabase) {
        	$groups         = json_decode($metabase['groups'],true);
            $groupsReport   = $metabase['groups'] === null || $metabase['groups'] === 'null' ? array($groupIdUser) : json_decode($metabase['groups'], true);

        	// grupo do usuário não pode ver relatório
            if (!in_array($groupIdUser, $groupsReport)) {
                continue;
            }

			if (is_null($groups)) {$groups = array();}
            if (in_array($this->data['usergroup'],$groups)) {
				if ($metabase['admin']) {
					$data['hasReportGroupsAdmin'] =true;
	                array_push($menuMetabse_adm, array(
	                    'selector_menu' => $metabase['selector_menu'].'_adm',
	                    'title' => $metabase[$language],
	                    'name_href' => $metabase['name']
	                ));
				}
	            else {
	            	$data['hasReportGroups'] =true;
	                array_push($menuMetabse_seller, array(
	                    'selector_menu' => $metabase['selector_menu'].'_seller',
	                    'title' => $metabase[$language],
	                    'name_href' => $metabase['name']
	                ));
				}
            }
			else {
				if ($metabase['admin']) {
                     array_push($menuMetabse_adm, array(
                        'selector_menu' => $metabase['selector_menu'].'_adm',
                        'title' => $metabase[$language],
                        'name_href' => $metabase['name']
                    ));
				}     
	            else {
	                array_push($menuMetabse_seller, array(
	                    'selector_menu' => $metabase['selector_menu'].'_seller',
	                    'title' => $metabase[$language],
	                    'name_href' => $metabase['name']
	                ));
				}
			}	
            
        }
        $data['menuMetabse_seller'] = $menuMetabse_seller;
        $data['menuMetabse_adm'] = $menuMetabse_adm;
		$data['sellerCenter'] = $sellerCenter;

        if (!isset($data['page_now'])) {
            $data['page_now'] = strtolower(strtok($page, '/'));
        }

		if ($this->isMobile()) {
			$this->load->view('templates/header_mobile',$data);
			$this->load->view('templates/header_menu',$data);
			if (is_file(APPPATH.'views/templates/side_menubar/'.$sellerCenter . '.php'))
			{
				$this->load->view('templates/side_menubar/'.$sellerCenter,$data);
			} else {
				$this->load->view('templates/side_menubar/default',$data);
			}
			if ($this->session->get_userdata()['need_change_password'] && $page != 'users/changepassword') {
				$this->session->set_flashdata(
					'error',
					sprintf($this->lang->line('messages_need_change_password'))
				);
				redirect('users/changepassword', 'refresh');
			} else {
				$this->load->view($page, $data);
			}
			$this->load->view('templates/footer_mobile',$data);
	
		} else {
			$this->load->view('templates/header',$data);
			$this->load->view('templates/header_menu',$data);
			if (is_file(APPPATH.'views/templates/side_menubar/'.$sellerCenter . '.php'))
			{
				$this->load->view('templates/side_menubar/'.$sellerCenter,$data);
			} else {
				$this->load->view('templates/side_menubar/default',$data);
			}
		
			if ($this->session->get_userdata()['need_change_password'] && $page != 'users/changepassword') {
				$this->session->set_flashdata(
					'error',
					sprintf($this->lang->line('messages_need_change_password'))
				);
				redirect('users/changepassword', 'refresh');
			} 

			if (!isset($this->session->get_userdata()['block'])) {
				redirect('auth/logout', 'refresh');
			}
			
			if (!$this->session->get_userdata()['need_change_password']) { // Trocar a senha tem precedencia sobre outras coisas obrigatórias
				if ($this->session->get_userdata()['block'] && $page != 'contractSignatures/edit' && $this->session->get_userdata()['legal_administrator']) {
					$this->session->set_flashdata(
						'error',
						sprintf($this->lang->line('application_sign_contract_to_release'))
					);

						redirect('contractSignatures/edit/'.$this->session->get_userdata()['contract_sign'], 'refresh');				
				}
				if ($this->session->get_userdata()['block'] && $page != 'contractSignatures/read' && !$this->session->get_userdata()['legal_administrator']) {
					$this->session->set_flashdata(
						'error',
						sprintf($this->lang->line('application_admin_needs_to_sign'))
					);

						redirect('contractSignatures/read', 'refresh');				
				}
				if ($this->session->get_userdata()['contract_sign'] && $page != 'contractSignatures/edit') {
					$this->session->set_flashdata(
						'error',
						sprintf($this->lang->line('application_contracts_to_signed'))
					);				
				}
			} 
			$this->load->view($page, $data);
			$this->load->view('templates/footer',$data);
		}
	}

	public function company_currency($id = null)
	{
		if (!$id) { $id = $this->data['usercomp']; }
		$this->load->model('model_company');
		$company_currency = $this->model_company->getCompanyData($id);
		$currencies = $this->currency();
			
		$currency = '';
		foreach ($currencies as $key => $value) {
			if($key == $company_currency['currency']) {
				$currency = $value;
			}
		}

		return $currency;

	}
	
	public function datedif($data1,$data2 = 0)
	{
		if ($data2==0) {
			$data2 = date('Y-m-d');
		}
		// converte as datas para o formato timestamp
		$d1 = strtotime($data1); 
		$d2 = strtotime($data2);
		// verifica a diferença em segundos entre as duas datas e divide pelo número de segundos que um dia possui
		$dataFinal = ($d2 - $d1) / 86400;
		// caso a data 2 seja menor que a data 1
		if($dataFinal < 0) { $dataFinal = $dataFinal * -1; }
		return $dataFinal;
	}	
	
	public function formatprice($price = 0)
	{
		$price = trim($price);
        if ($price == "" || $price == '-') {$price = 0;}
		return $this->session->userdata('currency') . number_format($price, 2, ',', '.');
	}	
	function fmtNum($num, $padrao = "US") {    // Ou BR
		$num = preg_replace("/[^0-9^.^,]/", "", $num);
        $num = trim($num);
		$temp = str_replace(",", "", $num);
		$temp = str_replace(".", "", $temp);
		if (is_numeric($temp)) {
			$num = str_replace(",", ".", $num);
			$ct = false;
			while (!$ct) {
				$temp = str_replace(".", "", $num,$cnt);
				if ($cnt < 2) {
					$ct = true;
				} else {
					$pos = strpos($num,".");
					$num = substr($num,0,$pos).substr($num,$pos+1);
					$ct = false;
				}
			}
			return $num;
		} else {
			return false;
		}	
	}
	/* 
		This function checks if the email exists in the database
	*/
	public function log_data($mod,$action,$value,$tipo = 'I') 
	{
	    if(!empty($_SERVER['HTTP_CLIENT_IP'])){ 
	        //ip from share internet
	        $ip = $_SERVER['HTTP_CLIENT_IP'];
	    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
	        //ip pass from proxy
	        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    }elseif(!empty($_SERVER['REMOTE_ADDR'])) {
	        $ip = $_SERVER['REMOTE_ADDR'];
	    } else {
		    $ip = "NONE";
	    }		
		if($value) {

			if (($mod!="batch") && ($mod!="api")) {
				$id = (isset($this->session->userdata['id'])) ? $this->session->userdata['id'] : "none" ;
				$company_id = (isset($this->session->userdata['usercomp'])) ? $this->session->userdata['usercomp'] : "none" ;
				$datalog = array(
	 			 'user_id' => $id,
		         'company_id' => $company_id,
				 'store_id' => 1,
				 'module' => $mod,
				 'action' => $action,
				 'ip' => $ip,
				 'value' => $value,
				 'tipo' => $tipo
				 );
			} else {
				$datalog = array(
	 			 'user_id' => 1,
		         'company_id' => 1,
				 'store_id' => 1,
				 'module' => $mod,
				 'action' => $action,
				 'ip' => $ip,
				 'value' => $value,
				 'tipo' => $tipo
				 );
			}
			if (strtolower($mod)=='batch') {
				$insert = $this->db->insert('log_history_batch', $datalog);
			}elseif (strtolower($mod)=='api') {
				$insert = $this->db->insert('log_history_api', $datalog);
			}else {
				$insert = $this->db->insert('log_history', $datalog);
			}
			return ($insert == true) ? true : false;
		}		

		return false;
	}

	function MyLog ($a,$b) {
		log_message($a,$b);
	}

	function random_strings($length_of_string) 
	{ 
	  
	    // String of all alphanumeric character 
	    $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'; 
	  
	    // Shufle the $str_result and returns substring 
	    // of specified length 
	    return substr(str_shuffle($str_result),  
	                       0, $length_of_string); 
	}
	
	function random_pwd($length = 12) 
	{
		$ucase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
		$lcase = "abcdefghijklmnopqrstuvwxyz";
		$num = "0123456789";
		$schar = '=!@$^*()<>;:[]{}';
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

	static function getGUID($brackets = true){
	    if (function_exists('com_create_guid')){
	        return com_create_guid();
	    }else{
	        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45);// "-"
	        $uuid = ($brackets ? chr(123) : "") // "{"
	            .substr($charid, 0, 8).$hyphen
	            .substr($charid, 8, 4).$hyphen
	            .substr($charid,12, 4).$hyphen
	            .substr($charid,16, 4).$hyphen
	            .substr($charid,20,12)
	            .($brackets ? chr(125) : "");// "}"
	        return $uuid;
	    }
	}

	public function isMobile() {
		$useragent=$_SERVER['HTTP_USER_AGENT'];

		if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g | nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
			return true;
		} else {
			return false;
		}	
	}
	
	public function currency()
	{
		return $currency_symbols = array(
		  'AED' => '&#1583;.&#1573;', // ?
		  'AFN' => '&#65;&#102;',
		  'ALL' => '&#76;&#101;&#107;',
		  'ANG' => '&#402;',
		  'AOA' => '&#75;&#122;', // ?
		  'ARS' => '&#36;',
		  'AUD' => '&#36;',
		  'AWG' => '&#402;',
		  'AZN' => '&#1084;&#1072;&#1085;',
		  'BAM' => '&#75;&#77;',
		  'BBD' => '&#36;',
		  'BDT' => '&#2547;', // ?
		  'BGN' => '&#1083;&#1074;',
		  'BHD' => '.&#1583;.&#1576;', // ?
		  'BIF' => '&#70;&#66;&#117;', // ?
		  'BMD' => '&#36;',
		  'BND' => '&#36;',
		  'BOB' => '&#36;&#98;',
		  'BRL' => '&#82;&#36;',
		  'BSD' => '&#36;',
		  'BTN' => '&#78;&#117;&#46;', // ?
		  'BWP' => '&#80;',
		  'BYR' => '&#112;&#46;',
		  'BZD' => '&#66;&#90;&#36;',
		  'CAD' => '&#36;',
		  'CDF' => '&#70;&#67;',
		  'CHF' => '&#67;&#72;&#70;',
		  'CLP' => '&#36;',
		  'CNY' => '&#165;',
		  'COP' => '&#36;',
		  'CRC' => '&#8353;',
		  'CUP' => '&#8396;',
		  'CVE' => '&#36;', // ?
		  'CZK' => '&#75;&#269;',
		  'DJF' => '&#70;&#100;&#106;', // ?
		  'DKK' => '&#107;&#114;',
		  'DOP' => '&#82;&#68;&#36;',
		  'DZD' => '&#1583;&#1580;', // ?
		  'EGP' => '&#163;',
		  'ETB' => '&#66;&#114;',
		  'EUR' => '&#8364;',
		  'FJD' => '&#36;',
		  'FKP' => '&#163;',
		  'GBP' => '&#163;',
		  'GEL' => '&#4314;', // ?
		  'GHS' => '&#162;',
		  'GIP' => '&#163;',
		  'GMD' => '&#68;', // ?
		  'GNF' => '&#70;&#71;', // ?
		  'GTQ' => '&#81;',
		  'GYD' => '&#36;',
		  'HKD' => '&#36;',
		  'HNL' => '&#76;',
		  'HRK' => '&#107;&#110;',
		  'HTG' => '&#71;', // ?
		  'HUF' => '&#70;&#116;',
		  'IDR' => '&#82;&#112;',
		  'ILS' => '&#8362;',
		  'INR' => '&#8377;',
		  'IQD' => '&#1593;.&#1583;', // ?
		  'IRR' => '&#65020;',
		  'ISK' => '&#107;&#114;',
		  'JEP' => '&#163;',
		  'JMD' => '&#74;&#36;',
		  'JOD' => '&#74;&#68;', // ?
		  'JPY' => '&#165;',
		  'KES' => '&#75;&#83;&#104;', // ?
		  'KGS' => '&#1083;&#1074;',
		  'KHR' => '&#6107;',
		  'KMF' => '&#67;&#70;', // ?
		  'KPW' => '&#8361;',
		  'KRW' => '&#8361;',
		  'KWD' => '&#1583;.&#1603;', // ?
		  'KYD' => '&#36;',
		  'KZT' => '&#1083;&#1074;',
		  'LAK' => '&#8365;',
		  'LBP' => '&#163;',
		  'LKR' => '&#8360;',
		  'LRD' => '&#36;',
		  'LSL' => '&#76;', // ?
		  'LTL' => '&#76;&#116;',
		  'LVL' => '&#76;&#115;',
		  'LYD' => '&#1604;.&#1583;', // ?
		  'MAD' => '&#1583;.&#1605;.', //?
		  'MDL' => '&#76;',
		  'MGA' => '&#65;&#114;', // ?
		  'MKD' => '&#1076;&#1077;&#1085;',
		  'MMK' => '&#75;',
		  'MNT' => '&#8366;',
		  'MOP' => '&#77;&#79;&#80;&#36;', // ?
		  'MRO' => '&#85;&#77;', // ?
		  'MUR' => '&#8360;', // ?
		  'MVR' => '.&#1923;', // ?
		  'MWK' => '&#77;&#75;',
		  'MXN' => '&#36;',
		  'MYR' => '&#82;&#77;',
		  'MZN' => '&#77;&#84;',
		  'NAD' => '&#36;',
		  'NGN' => '&#8358;',
		  'NIO' => '&#67;&#36;',
		  'NOK' => '&#107;&#114;',
		  'NPR' => '&#8360;',
		  'NZD' => '&#36;',
		  'OMR' => '&#65020;',
		  'PAB' => '&#66;&#47;&#46;',
		  'PEN' => '&#83;&#47;&#46;',
		  'PGK' => '&#75;', // ?
		  'PHP' => '&#8369;',
		  'PKR' => '&#8360;',
		  'PLN' => '&#122;&#322;',
		  'PYG' => '&#71;&#115;',
		  'QAR' => '&#65020;',
		  'RON' => '&#108;&#101;&#105;',
		  'RSD' => '&#1044;&#1080;&#1085;&#46;',
		  'RUB' => '&#1088;&#1091;&#1073;',
		  'RWF' => '&#1585;.&#1587;',
		  'SAR' => '&#65020;',
		  'SBD' => '&#36;',
		  'SCR' => '&#8360;',
		  'SDG' => '&#163;', // ?
		  'SEK' => '&#107;&#114;',
		  'SGD' => '&#36;',
		  'SHP' => '&#163;',
		  'SLL' => '&#76;&#101;', // ?
		  'SOS' => '&#83;',
		  'SRD' => '&#36;',
		  'STD' => '&#68;&#98;', // ?
		  'SVC' => '&#36;',
		  'SYP' => '&#163;',
		  'SZL' => '&#76;', // ?
		  'THB' => '&#3647;',
		  'TJS' => '&#84;&#74;&#83;', // ? TJS (guess)
		  'TMT' => '&#109;',
		  'TND' => '&#1583;.&#1578;',
		  'TOP' => '&#84;&#36;',
		  'TRY' => '&#8356;', // New Turkey Lira (old symbol used)
		  'TTD' => '&#36;',
		  'TWD' => '&#78;&#84;&#36;',
		  'UAH' => '&#8372;',
		  'UGX' => '&#85;&#83;&#104;',
		  'USD' => '&#36;',
		  'UYU' => '&#36;&#85;',
		  'UZS' => '&#1083;&#1074;',
		  'VEF' => '&#66;&#115;',
		  'VND' => '&#8363;',
		  'VUV' => '&#86;&#84;',
		  'WST' => '&#87;&#83;&#36;',
		  'XAF' => '&#70;&#67;&#70;&#65;',
		  'XCD' => '&#36;',
		  'XPF' => '&#70;',
		  'YER' => '&#65020;',
		  'ZAR' => '&#82;',
		  'ZMK' => '&#90;&#75;', // ?
		  'ZWL' => '&#90;&#36;',
		);
	}

    /**
     * Format document, CNPJ and CPF - 000.000.000-00 / 00.000.000-0000/00
     *
     * @param string $value
     * @return string
     */
    public function formatDoc($value = '')
    {
        if(strlen($value) != 11 && strlen($value) != 14) {return $value;} // Se for diferente de 11 e 14 caracteres devolve o valor informado
        // Se for 11 caracteres formata no formato 000.000.000-00
        if(strlen($value) == 11) {return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{2})/", "$1.$2.$3-$4", $value);}
        // Se for 14 caracteres formata no formato 00.000.000-0000/00
        if(strlen($value) == 14) {return preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})/", "$1.$2.$3/$4-$5", $value);}
    }

    /**
     * Format CEP - 00.000-000
     *
     * @param string $value
     * @return string
     */
    public function formatCep($value = '')
    {
        if(strlen($value) != 8) {return $value;} // Se for diferente de 8 caracteres devolve o valor informado
        // Se for 8 caracteres formata no formato 00.000-000
        if(strlen($value) == 8) {return preg_replace("/([0-9]{5})([0-9]{3})/", "$1-$2", $value);}
    }

    /**
     * Format telephone number - (00) 0000-0000 / (00) 00000-0000
     *
     * @param $value
     * @return tring
     */
    function formatPhone($value = '')
    {
        if(strlen($value) != 10 && strlen($value) != 11) {return $value;} // Se for diferente de 10 e 11 caracteres devolve o valor informado
        // Se for 10 caracteres formata no formato (00) 0000-0000
        if(strlen($value) == 10) {return preg_replace("/([0-9]{2})([0-9]{4})([0-9]{4})/", "($1) $2-$3", $value);}
        // Se for 11 caracteres formata no formato (00) 00000-0000
        if(strlen($value) == 11) {return preg_replace("/([0-9]{2})([0-9]{5})([0-9]{4})/", "($1) $2-$3", $value);}
    }

    /**
     * Get banks
     *
     * @return array
     */
    public function getBanks()
    {
        return $this->model_banks->getBanks();
    }

	public function somar_dias_uteis( $str_data, $int_qtd_dias_somar, $feriados = '', $removegmdate = FALSE)
	{
        return somar_dias_uteis($str_data, $int_qtd_dias_somar, $feriados, $removegmdate);
	}

	public function diminuir_dias_uteis( $str_data, $int_qtd_dias_remover, $feriados = '' )
	{
        return diminuir_dias_uteis($str_data, $int_qtd_dias_remover, $feriados);
	}

	function dataPascoa( $ano = false, $form = "d/m/Y" ) {
        return dataPascoa($ano, $form);
	}

	// dataCarnaval(ano, formato);
	// Autor: Yuri Vecchi
	//
	// Funcao para o calculo do Carnaval
	// Retorna o dia do Carnaval no formato desejado ou false.
	//
	// ######################ATENCAO###########################
	// Esta funcao sofre das limitacoes de data de mktime()!!!
	// ########################################################
	//
	// Possui dois parametros, ambos opcionais
	// ano = ano com quatro digitos
	//	 Padrao: ano atual
	// formato = formatacao da funcao date() http://br.php.net/date
	//	 Padrao: d/m/Y
	
	function dataCarnaval( $ano = false, $form = "d/m/Y" ) {
        return dataCarnaval($ano, $form);
	}
	
	// dataCorpusChristi(ano, formato);
	// Autor: Yuri Vecchi
	//
	// Funcao para o calculo do Corpus Christi
	// Retorna o dia do Corpus Christi no formato desejado ou false.
	//
	// ######################ATENCAO###########################
	// Esta funcao sofre das limitacoes de data de mktime()!!!
	// ########################################################
	//
	// Possui dois parametros, ambos opcionais
	// ano = ano com quatro digitos
	//	 Padrao: ano atual
	// formato = formatacao da funcao date() http://br.php.net/date
	//	 Padrao: d/m/Y
	
	function dataCorpusChristi( $ano = false, $form = "d/m/Y" ) {
        return dataCorpusChristi($ano, $form);
	}
	
	// dataSextaSanta(ano, formato);
	// Autor: Yuri Vecchi
	//
	// Funcao para o calculo da Sexta-feira santa ou da Paixao.
	// Retorna o dia da Sexta-feira santa ou da Paixao no formato desejado ou false.
	//
	// ######################ATENCAO###########################
	// Esta funcao sofre das limitacoes de data de mktime()!!!
	// ########################################################
	//
	// Possui dois parametros, ambos opcionais
	// ano = ano com quatro digitos
	// Padrao: ano atual
	// formato = formatacao da funcao date() http://br.php.net/date
	// Padrao: d/m/Y
	
	function dataSextaSanta( $ano = false, $form = "d/m/Y" ) {
	    return dataSextaSanta($ano, $form);
	}

    /**
     * Consulta dados gráfico Metabase
     *
     * @return string   tag iframe
     */
    public function getMetabase($type, $id, $params = array())
    {
        if(($type !== "dashboard" && $type !== "question") || $id == 0) {
            return "<div class='alert alert-warning'>Tipo de parâmetro mal informado!</div>";
		}

        $metabase = new Metabase\Embed();

        if($type === "dashboard") {
            return $metabase->dashboardIframe($id, $params);
		}
        if($type === "question") {
            return $metabase->questionIFrame($id, $params);
		}
        return "<div class='alert alert-warning'>Ocorreu um erro desconhecido!</div>";
    }
    /**
	 * @param $attach accept string with path to file to send.
	 */
    public function sendEmailMarketing($to, $subject, $body, $from = null, $attach = null)
    {

        if (is_null($from)) {
            $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
            if (!$from) {
                $from = 'marketing@conectala.com.br';
            }
        }
        // com gmail mas tem que alterar configurações de segurança
        //		$config['protocol'] = "smtp";
        //		$config['smtp_host'] = "ssl://smtp.gmail.com";
        //		$config['smtp_port'] = "465";
        //		$config['smtp_user'] = "admin@conectala.com.br";
        //	 	$config['smtp_pass'] = "Gsuite1*";
        //		$config['smtp_pass'] = "tdtcramlzhsqxwpg";
                
        $config['smtp_host']= $this->model_settings->getValueIfAtiveByName('smtp_host');
		if ($config['smtp_host'] === false) {
			 $config['smtp_host'] = "email-smtp.us-east-1.amazonaws.com";
		}
        //Se não está usando oracle para enviar, vamos enviar usando a biblioteca tradicional mesmo
        if (!strstr($config['smtp_host'], 'oraclecloud.com')){
            return $this->sendEmailCodeIgniter($to, $subject, $body, $from, $attach);
        }
        
	    $config['smtp_port']= $this->model_settings->getValueIfAtiveByName('smtp_port');
		if ($config['smtp_port'] === false) {
			 $config['smtp_port'] = "587";
		}
		$config['smtp_crypto']= $this->model_settings->getValueIfAtiveByName('smtp_crypto');
		if ($config['smtp_crypto'] === false) {
			 $config['smtp_crypto'] = 'tls';
		}
		$config['smtp_user']= $this->model_settings->getValueIfAtiveByName('smtp_user');
		if ($config['smtp_user'] === false) {
			 $config['smtp_user'] = "";
		}
		$config['smtp_pass']= $this->model_settings->getValueIfAtiveByName('smtp_pass');
		if ($config['smtp_pass'] === false) {
			 $config['smtp_pass'] = "";
		}

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->SMTPDebug = 0;

        try {
            // Configurações do servidor SMTP
            $mail->isSMTP();                                             // Enviar usando SMTP
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->Host       = $config['smtp_host'];  // Endereço do servidor SMTP
            $mail->SMTPAuth   = true;
            $mail->AuthType = 'PLAIN';// Habilitar autenticação SMTP
            $mail->Username   = $config['smtp_user']; // Seu nome de usuário
            $mail->Password   = $config['smtp_pass'];                           // Sua senha SMTP
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;          // Habilitar criptografia TLS
            $mail->Port       = $config['smtp_port'];                                     // Porta TCP para conexão

            // Remetente e destinatários
            $mail->setFrom($from, $from);
            if (is_array($to)) {
                foreach ($to as $toEmail){
                    $mail->addAddress($toEmail, $toEmail);
                }
            }else {
                $mail->addAddress($to, $to);
            }

            // Conteúdo do e-mail
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            if($attach) {
                $mail->addAttachment($attach);
            }

            // Enviar o e-mail
            $mail->send();

            return (array('ok'=>true,'msg'=>'Email enviado com sucesso'));

        } catch (Exception $e) {
            $this->log_data("email","send", $mail->ErrorInfo,"E");
            return (array('ok'=>false,'msg'=>'Houve erro no email. Debug: '.$mail->ErrorInfo));
        }

	}

    /**
     * Envia e-mails da forma tradicional usando a própria biblioteca do Codeigniter (não compatível com oracle)
     * @param  array  $config
     * @return void
     */
    public function sendEmailCodeIgniter($to, $subject, $body, $from = null, $attach = null)
    {

        if (is_null($from)) {
            $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
            if (!$from) {
                $from = 'marketing@conectala.com.br';
            }
        }
        // com gmail mas tem que alterar configurações de segurança
        //		$config['protocol'] = "smtp";
        //		$config['smtp_host'] = "ssl://smtp.gmail.com";
        //		$config['smtp_port'] = "465";
        //		$config['smtp_user'] = "admin@conectala.com.br";
        //	 	$config['smtp_pass'] = "Gsuite1*";
        //		$config['smtp_pass'] = "tdtcramlzhsqxwpg";

        $config['smtp_host']= $this->model_settings->getValueIfAtiveByName('smtp_host');
		if ($config['smtp_host'] === false) {
			 $config['smtp_host'] = "email-smtp.us-east-1.amazonaws.com";
		}
	    $config['smtp_port']= $this->model_settings->getValueIfAtiveByName('smtp_port');
		if ($config['smtp_port'] === false) {
			 $config['smtp_port'] = "587";
		}
		$config['smtp_crypto']= $this->model_settings->getValueIfAtiveByName('smtp_crypto');
		if ($config['smtp_crypto'] === false) {
			 $config['smtp_crypto'] = 'tls';
		}
		$config['smtp_user']= $this->model_settings->getValueIfAtiveByName('smtp_user');
		if ($config['smtp_user'] === false) {
			 $config['smtp_user'] = "";
		}
		$config['smtp_pass']= $this->model_settings->getValueIfAtiveByName('smtp_pass');
		if ($config['smtp_pass'] === false) {
			 $config['smtp_pass'] = "";
		}
		$config['protocol']= $this->model_settings->getValueIfAtiveByName('smtp_protocol');
		if ($config['protocol'] === false) {
			 $config['protocol'] = "smtp";
		}

        $config['smtp_timeout']='10';
        $config['charset'] = "utf-8";
        $config['mailtype'] = "html";
        $config['newline'] = "\r\n";
        $config['crlf']     = "\r\n";
        $config['wordwrap'] = TRUE;
        $this->load->library('email',$config);
        $this->email->initialize($config);

        if (!is_array($to)) {
            $sendto[] = $to;
        }else {
            $sendto = $to;
        }
        $this->email->from($from,$from);
        $this->email->to($sendto);
        $this->email->subject($subject);
        $this->email->message($body);
        // Anexo
        if($attach) {
            $this->email->attach($attach);
		}
		if (!$this->email->send()) {
			$this->log_data("email","send", $this->email->print_debugger(),"E");
			return (array('ok'=>false,'msg'=>'Houve erro no email. Debug: '.$this->email->print_debugger()));
		} else {
			return (array('ok'=>true,'msg'=>'Email enviado com sucesso'));
		}

	}

	public function tirarAcentos($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }

    public function detectUTF8($string)
    {

        $detect = preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);

        return $detect == 1 ? $string : utf8_encode($string);
    }

    /**
     * https://stackoverflow.com/a/15423899/10781591
     *
     * @param   string $text
     * @return  array|string|string[]|null
     */
    public function removeUtf8Bom(string $text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    /**
     * @param   string  $file   Caminho do arquivo. Ex.: 'assets/images'
     * @return  bool
     */
    public function checkCreatePath(string $file): bool
    {
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath,'assets');
        $serverpath = substr($serverpath,0,$pos);
        $targetDir = $serverpath . $file;
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        return true;
    }


    /**
     * Consulta as informações para retornar ao datatable.
     *
     * @param   string  $model          Model a ser realizar a consulta. Ex.: model_csv_to_verifications
     * @param   string  $method         Método dentro da model para realizar a consulta. Ex.: getFetchFileProcessData
     * @param   array   $permissions    Permissões a serem validadas pelo usuário. Ex.: ['createCarrierRegistration', 'updateCarrierRegistration]
     * @param   array   $filters        Filtros adicionais para a consulta. Ex.: ['where_in' => ['csv_to_verification.store_id' => [10,20,30]], 'where' => ['csv_to_verification.module' => 'Shippingcompany', 'csv_to_verification.final_stuation' => 'success']]
     * @param   array   $fields_order   Campos pertencente a tabela no front para realizar a ordenagem dos resultados. Ex.: ['csv_to_verification.id', 'csv_to_verification.upload_file', 'csv_to_verification.created_at', ...]
     * @return  array                   Será retornado
     * @throws  Exception
     */
    public function fetchDataTable(string $model, string $method, array $permissions = array(), array $filters = array(), array $fields_order = array(), $filter_default = array()): array
    {
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if (!in_array($permission, $this->permission)) {
                    throw new Exception("Sem autorização para fazer essa ação.", 403);
                }
            }
        }

        $start  = $this->postClean('start');
        $length = $this->postClean('length');
        $search = $this->postClean('search');

        if (empty($start)) {
            $start = 0;
        }

        if (empty($length)) {
            $length = 200;
        }

        if (!empty($search) && isset($search['value'])) {
            $search_text = $search['value'];
        } else {
            $search_text = null;
        }

        $order_by = [];
        if (!empty($this->postClean('order'))) {
            if ($this->postClean('order')[0]['dir'] == "asc") {
                $direction = "asc";
            } else {
                $direction = "desc";
            }
            $field = $fields_order[$this->postClean('order')[0]['column']] ?? '';
            if ($field != "") {
                $order_by = array($field, $direction);
            }
        }

        try {
            $this->load->model($model);

            $filters = array_merge_recursive($filter_default, $filters);

            $data           = $this->$model->$method($start, $length, $order_by, $search_text, $filters,        false, $fields_order);
            $count_filtered = $this->$model->$method(null,   null,    $order_by, $search_text, $filters,        true,  $fields_order);
            $count_total    = $this->$model->$method(null,   null,    $order_by, null,         $filter_default, true,  $fields_order);
        } catch (Exception $exception) {
            throw new Exception("Não foi possível realizar a consulta.", 400);
        }

        return array(
            'data'              => $data,
            'recordsFiltered'   => $count_filtered,
            'recordsTotal'      => $count_total
        );
    }
	public function getVersion() 
	{
		try {
			// $version = trim(exec('git describe --tags $(git log --pretty="%h" -n1 HEAD)'));
			$version = file_get_contents(APPPATH.'../version.txt', true);
			return trim($version);
		}
		catch (Exception $e){
			return 'NO_VERSION';
		}
	}
}

class BatchBackground_Controller extends Admin_Controller 
{

    protected const JOBS_RUNNING_PARALLEL = 1;

	var $idJob = "";
	public function __construct() 
	{
		parent::__construct();
		$this->load->model("model_calendar");
		$this->load->model('model_job_schedule');
		
		// Bloqueia a execução se não for chamado do servidor 
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$ip = $this->input->ip_address();
		if ($ip!='0.0.0.0') {
			$this->log_data('batch',$log_name,'Tentativa de rodar o job do IP='.$ip,"E");
			show_error( 'Unauthorized', 401,"An Error Was Encountered");
			die;
		}
		ini_set('display_errors', 1);
	}
	
	public function setIdJob($id) 
	{
		$this->idJob = $id;
	}
	public function getIdJob()
	{
		return($this->idJob);
	}
	
	public function gravaInicioJob($module, $method, $params = null)
	{
        $date = dateNow();
		$idJob = $this->idJob;
		echo "Iniciado em ".$date->format('Y/m/d H:i:s')." com id: $idJob\n";
		if (!is_null($this->idJob)) {
			// verifica se já existe um job igual rodando.
            if ($this->checkJobRunning($module, $method, $params)) {
                $this->model_calendar->update_job($this->idJob, array(
                        "status" => 3,
                    )
                );
                return false;
            }
			$job_event = $this->model_job_schedule->getData($this->idJob);
			$job_data = array(
	            "status" => 1,
			);
			if (($job_event['alert_after'] ?? null) >= 5) {
				$job_data['start_alert'] = date("Y-m-d H:i:s", strtotime("+".$job_event['alert_after']." minutes"));
			}
			$this->model_calendar->update_job($this->idJob, $job_data);
		}
		return true;
	}

    protected function checkJobRunning($module, $method, $params = null) : bool
    {
        return ($this->model_calendar->getEventOpen($module, $method, $params) >= get_class($this)::JOBS_RUNNING_PARALLEL);
    }

	public function gravaFimJob()
	{
		$idJob = $this->idJob;
		if (!is_null($idJob)) {
            $enddt = dateNow();
		    $end_format = $enddt->format('Y-m-d H:i:s');
			
			$this->model_calendar->update_job($this->idJob, array(
			    "status" => 2,
			    "date_end" => $end_format
			    )
			);
		}
		echo "Encerrado em ".date('Y/m/d H:i:s')." com id: $idJob\n";
	}

    public function checkStartRun(string $log_name, string $directory, string $class, string $id, string $params): bool
    {
        if (empty($params)) {
            $this->log_data('batch', $log_name, "Parâmetros informados incorretamente. ID=$id - STORE=$params", "E");
            return false;
        }

        $this->setIdJob($id);

        $modulePath = (str_replace("BatchC/", '', $directory)) . $class;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, "Já tem um job rodando ou que foi cancelado, job_id=$id store_id=$params", "E");
            return false;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        return true;
    }

}
