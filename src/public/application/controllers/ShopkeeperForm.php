<?php


defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Model_shopkeeper_field_value $Model_shopkeeper_field_value
 * @property Model_shopkeeper_field_form $Model_shopkeeper_field_form
 * @property Model_shopkeeper_form $Model_shopkeeper_form
 * @property model_stores $model_stores
 * @property Model_company $Model_company
 * @property model_banks $model_banks
 * @property Model_attributes $Model_attributes
 * @property model_attributes $model_attributes
 * @property model_settings $model_settings
 * @property model_users $model_users
 */

class ShopkeeperForm extends Admin_Controller 
{
    /**
     * @var string
     */
    private $sellercenter;

	public function __construct()
	{
		parent::__construct();

		$this->data['page_title'] = $this->lang->line('application_shopkeeper_form');

		$this->load->model('Model_shopkeeper_field_value');
        $this->load->model('Model_shopkeeper_field_form');
        $this->load->model('Model_shopkeeper_form');
        $this->load->model('model_stores');
        $this->load->model('Model_company');
        $this->load->model('model_banks');
        $this->load->model('Model_attributes');
        $this->load->model('model_attributes');
        $this->load->model('model_settings');
        $this->load->model('model_users');

        $this->load->library('JWT');

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->sellercenter = $settingSellerCenter['value'];
	}

	/* 
	* It only redirects to the manage product page and
	*/
	public function index()
	{
		if(!in_array('updateShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

        //$result = $this->model_settings->getSettingData();
        $result = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter, 1);

		$this->data['results'] = $result;
        
        $settingLinkRedirect = $this->model_settings->getSettingDatabyName('success_description_shopkeeperform');
        
        if(isset($settingLinkRedirect)){
            $this->data['results']['success_description'] = $settingLinkRedirect['value'];
        }

		$this->render_template('shopkeeperform/index', $this->data);
	}

    public function success()
	{

        $settingSuccess = $this->model_settings->getSettingDatabyName('success_description_shopkeeperform');
        $this->data['shopkeeperform']['success_description'] = $settingSuccess ? $settingSuccess['value'] : "";
    
        $settinglogo = $this->model_settings->getSettingDatabyName('logotipo_shopkeeperform');
        $this->data['shopkeeperform']['file_logotipo'] = $settinglogo ? $settinglogo['value'] : ""; 
        
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->data['shopkeeperform']['sellerCenter'] = $settingSellerCenter['value'];

		$this->load->view('shopkeeperform/success', $this->data);
	}

    public function list() 
	{
		if(!in_array('updateShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

        //$result = $this->model_settings->getSettingData();
        $result = $this->Model_shopkeeper_form->getFormDatabySellerCenter($this->sellercenter);

        $usergroup = $this->session->userdata('group_id');
        $user_id = $this->session->userdata('id');

        $setting_seller_group_user = $this->model_settings->getSettingDatabyName('group_id_user_seller');
        $seller_group_user = $setting_seller_group_user['value'];

        if($seller_group_user){
            $seller_group_user = explode(",", $seller_group_user);
            $this->data['shopkeeperform']['user_id'] = in_array($usergroup, $seller_group_user) ? $user_id : false;
        }else{
            $this->data['shopkeeperform']['user_id'] = false;
        }

        

		$this->data['results'] = $result;

        $this->data['get_attribute_value_utm_param'] = $this->Model_attributes->getAttributeValueUtmParam();

		$this->render_template('shopkeeperform/list', $this->data);
	}

    	/*
	* Fetches the Setting data from the Setting table 
	* this function is called from the datatable ajax function
	*/
	public function fetchShopkeepersData()
	{
		$result = array('data' => array());

		$data = $this->Model_shopkeeper_form->getFormDatabySellerCenter($this->sellercenter);

		foreach ($data as $key => $value) {
			// button
			$buttons = '';
            $user_create = '';
			if(in_array('updateShopkeeperForm', $this->permission)) {
				$buttons .= "<a href='".base_url('ShopkeeperForm/edit/'.$value['id'])."' class='btn btn-default'><i class='fa fa-edit'></i></a>";	
			}


            $status = '';
            if($value['status'] == '1')
            {   
                $status = "Aguardando Analise";    
            }
            else if($value['status'] == '2')
            {
                $status = "Aprovado";
            }
            else if($value['status'] == '3')
            {
                $attribute = $this->model_attributes->getAttributeValueDataById($value['attribute_value_id']);
                
                $status = $attribute['value'];
            }else if($value['status'] == '4')
            {   
                $status = "Cadastro incompleto";
            }
            if($value['user_create'] && $value['user_create'] !== '0' && $value['user_create'] !== 0) {
                $user = $this->model_users->getUserData($value['user_create']);
                $user_create = $user['firstname'] . " " . $user['lastname'];
            }
			
			$result['data'][$key] = array(
				'<span style="word-break:break-all;">'.$value['name'].'</span>',
                '<span style="word-break:break-all;">'.$value['responsible_name'].'</span>',
                '<span style="word-break:break-all;">'.$status.'</span>',
                '<span style="word-break:break-all;">'.$user_create.'</span>',
				$buttons
			);
		} // /foreach
		echo json_encode($result);
	}

	/*
	* Fetches the Setting data from the Setting table 
	* this function is called from the datatable ajax function
	*/
	public function fetchShopkeeperformData()
	{
		$result = array('data' => array());

		$data = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter);
		foreach ($data as $key => $value) {

			// button
			$buttons = '';

			if(in_array('updateShopkeeperForm', $this->permission)) {
				$buttons .= '<button type="button" class="btn btn-default" onclick="editField('.$value['id'].')" data-toggle="modal" data-target="#editFieldModal"><i class="fa fa-pencil"></i></button>';	
			}
			
			if(in_array('updateShopkeeperForm', $this->permission)) {
				//$buttons .= ' <button type="button" class="btn btn-default" onclick="removeField('.$value['id'].',\''.$value['label'].'\')" data-toggle="modal" data-target="#removeFieldModal"><i class="fa fa-trash"></i></button>';
			}				

			$visible = ($value['visible'] == 1) ? '<span class="label label-success">'.$this->lang->line('application_active').'</span>' : '<span class="label label-warning">'.$this->lang->line('application_inactive').'</span>';

            $required = ($value['required'] == 1) ? '<span class="label label-success">'.$this->lang->line('application_active').'</span>' : '<span class="label label-warning">'.$this->lang->line('application_inactive').'</span>';

            if($value['type'] == 1){
                $type = '<span class="label label-success">'.$this->lang->line('application_text').'</span>';
            }else if($value['type'] == 2){
                $type = '<span class="label label-success">'.$this->lang->line('application_yes_no').'</span>';
            }else if($value['type'] == 3){
                $type = '<span class="label label-success">'.$this->lang->line('application_attachment').'</span>';
            }

			$result['data'][$key] = array(
				'<span style="word-break:break-all;">'.$value['label'].'</span>',
                $type,
				$visible,
				$required,
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}

	/*
	* It checks if it gets the Setting id and retreives
	* the Setting information from the Setting model and 
	* returns the data into json format. 
	* This function is invoked from the view page.
	*/
	public function fetchShopkeeperFormDataById($id)
	{
		if($id) {
            $data = $this->Model_shopkeeper_field_form->getFieldDatabyId($id);
			echo json_encode($data);
		}

		return false;
	}

    public function complete($id, $user_id = "")
    {
        $this->data['banks'] = $this->model_banks->getBanks();
        
        $this->form_validation->set_rules('address', $this->lang->line('application_address'), 'trim|required');
        $this->form_validation->set_rules('addr_num', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('addr_compl', $this->lang->line('application_complement'), 'trim');
        $this->form_validation->set_rules('addr_neigh', $this->lang->line('application_neighb'), 'trim|required');
        $this->form_validation->set_rules('addr_city', $this->lang->line('application_city'), 'trim|required');
        $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        $this->form_validation->set_rules('country', $this->lang->line('application_country'), 'trim|required');
        $this->form_validation->set_rules('zipcode', $this->lang->line('application_zip_code'), 'trim|required');
        $usar_mascara_banco = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;

        if ($this->postClean('same') != "1") {
            $this->form_validation->set_rules('business_street', $this->lang->line('application_address'), 'trim|required');
            $this->form_validation->set_rules('business_addr_num', $this->lang->line('application_number'), 'trim|required');
            $this->form_validation->set_rules('business_addr_compl', $this->lang->line('application_complement'), 'trim');
            $this->form_validation->set_rules('business_neighborhood', $this->lang->line('application_neighb'), 'trim|required');
            $this->form_validation->set_rules('business_town', $this->lang->line('application_city'), 'trim|required');
            $this->form_validation->set_rules('business_uf', $this->lang->line('application_uf'), 'trim|required');
            $this->form_validation->set_rules('business_nation', $this->lang->line('application_country'), 'trim|required');
            $this->form_validation->set_rules('business_code', $this->lang->line('application_zip_code'), 'trim|required');
        }

        /*
        $this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|is_unique[stores.CNPJ]');
        if ($this->postClean('exempted') != "1") {
            $this->form_validation->set_rules('insc_estadual', $this->lang->line('application_iest'), 'trim|required|callback_checkInscricaoEstadual[' . $this->postClean("addr_uf") . ']');
        }
     */
    

        $this->form_validation->set_rules('responsible_name', $this->lang->line('application_responsible_name'), 'trim|required');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_responsible_email'), 'trim|required|valid_email');
        $this->form_validation->set_rules('responsible_cpf', $this->lang->line('application_responsible_cpf'), 'trim|callback_checkCPF');

        $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
        $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
        $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
        $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');

        $this->form_validation->set_rules('form-3_0', 'Arquivo', 'callback_validate_file');
        
        if ($this->form_validation->run() == TRUE) {    

            $data = array();
            
            $data['status'] = 2;

            $bank = (is_null($this->postClean('bank'))) ? "" : $this->postClean('bank');
            $agency = (is_null($this->postClean('agency'))) ? "" : $this->postClean('agency');
            $account_type = (is_null($this->postClean('account_type'))) ? "" : $this->postClean('account_type');
            $account = (is_null($this->postClean('account'))) ? "" : $this->postClean('account');

            foreach ($this->data['banks'] as $local_bank) {
                if ( $usar_mascara_banco == true) {
                    if ($local_bank['name'] == $bank) {
     
                        if(strlen($account) != strlen($local_bank['mask_account'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_account') . $local_bank['mask_account']);
                            redirect('ShopkeeperForm/complete/'.$id, 'refresh');
                        }
                        if( strlen($agency) != strlen($local_bank['mask_agency'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_agency') . $local_bank['mask_agency']);
                            redirect('ShopkeeperForm/complete/'.$id, 'refresh');

                        }
                        continue;
                    }
                 }else{
                    continue;
                 }
            }

            $shopkeeper_form = $this->Model_shopkeeper_form->getFormDatabyId($id);

            $data = array(
                'name' => $this->postClean('name'),
                'address' => $this->postClean('address'),
                'addr_num' => $this->postClean('addr_num'),
                'addr_compl' => $this->postClean('addr_compl'),
                'addr_neigh' => $this->postClean('addr_neigh'),
                'addr_city' => $this->postClean('addr_city'),
                'addr_uf' => $this->postClean('addr_uf'),
                'zipcode' => preg_replace('/[^\d\+]/', '', $this->postClean('zipcode')),
                'phone_1' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_1')),
                'phone_2' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_2')),
                'country' => $this->postClean('country'),
                'raz_social' => $this->postClean('pj_pf') == "pf" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? $this->postClean('name') : $this->postClean('raz_soc'),
                'insc_estadual' => $this->postClean('exempted') == "1" ? "0" : $this->postClean('insc_estadual'),
                'responsible_cs' => $this->postClean('responsible_cs'),
                'CNPJ' => $this->numberString($this->postClean('CNPJ'), false),
                'responsible_name' => $this->postClean('responsible_name'),
                //'responsible_email' => $this->postClean('responsible_email'),
                'responsible_cpf' => $this->postClean('responsible_cpf'),

                'responsible_mother_name' => $this->postClean('responsible_mother_name'),
                'responsible_position' => $this->postClean('responsible_position'),

                'bank' => $bank,
                'agency' => $agency,
                'account_type' => $account_type,
                'account' => $account,
                'business_street' => $this->postClean('same') == "1" ? $this->postClean('address') : $this->postClean('business_street'),
                'business_addr_num' => $this->postClean('same') == "1" ? $this->postClean('addr_num') : $this->postClean('business_addr_num'),
                'business_addr_compl' => $this->postClean('same') == "1" ? $this->postClean('addr_compl') : $this->postClean('business_addr_compl'),
                'business_neighborhood' => $this->postClean('same') == "1" ? $this->postClean('addr_neigh') : $this->postClean('business_neighborhood'),
                'business_town' => $this->postClean('same') == "1" ? $this->postClean('addr_city') : $this->postClean('business_town'),
                'business_uf' => $this->postClean('same') == "1" ? $this->postClean('addr_uf') : $this->postClean('business_uf'),
                'business_nation' => $this->postClean('same') == "1" ? $this->postClean('country') : $this->postClean('business_nation'),
                'business_code' => $this->postClean('same') == "1" ? $this->postClean('zipcode') : $this->postClean('business_code'),
                //'user_create' => $this->session->userdata('id'),
                'status' => '1',
                'user_create' => $user_id ? $user_id : "",
                
            );

            if ($shopkeeper_form['status'] == 2) {
                unset($data['status']);
            }
        
            $this->Model_shopkeeper_form->update($data, $id);

            $attachments = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter, 3);
            $fields2 = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter, 2);
            $fields1 = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter, 1);

            foreach ($attachments as $file=>$key) {
                if (!empty($_FILES['form-3_'.$file]["name"])) {
                    $upload_image = $this->upload_image('form-3_'.$file);
                    
                    $field = array();
                    $field['field_value'] = $upload_image;
                    $field['field_form_id'] = $key['id'];
                    $field['form_id'] = $id;

                    $this->Model_shopkeeper_field_value->removeByFieldValueAndFieldFormIdAndFormId($field['field_value'], $field['field_form_id'], $field['form_id']);
                    $this->Model_shopkeeper_field_value->create($field);
                }
            }

            foreach ($fields2 as $field2) {
                if(!empty($this->postClean('form-2_'.$field2['id'])))
                {
                    $field['field_value'] = $this->postClean('form-2_'.$field2['id']);
                    $field['field_form_id'] = $field2['id'];
                    $field['form_id'] = $id;

                    $this->Model_shopkeeper_field_value->removeByFieldValueAndFieldFormIdAndFormId($field['field_value'], $field['field_form_id'], $field['form_id']);
                    $this->Model_shopkeeper_field_value->create($field);
                }
            }

            foreach ($fields1 as $field1) {
                if(!empty($this->postClean('form-1_'.$field1['id'])))
                {
                    $field['field_value'] = $this->postClean('form-1_'.$field1['id']);
                    $field['field_form_id'] = $field1['id'];
                    $field['form_id'] = $id;

                    $this->Model_shopkeeper_field_value->removeByFieldValueAndFieldFormIdAndFormId($field['field_value'], $field['field_form_id'], $field['form_id']);
                    $this->Model_shopkeeper_field_value->create($field);
                }
            }
            //$settingLinkRedirect = $this->model_settings->getSettingDatabyName('link_redirect_shopkeeperform');

            $settingSuccess = $this->model_settings->getSettingDatabyName('success_description_shopkeeperform');
            $this->data['shopkeeperform']['success_description'] = $settingSuccess ? $settingSuccess['value'] : "";

            $settinglogo = $this->model_settings->getSettingDatabyName('logotipo_shopkeeperform');
            $this->data['shopkeeperform']['file_logotipo'] = $settinglogo ? $settinglogo['value'] : "";

            $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
            $this->data['shopkeeperform']['sellerCenter'] = $settingSellerCenter['value'];

            $this->load->view('shopkeeperform/success', $this->data);
        } 
        else {
            $this->data["shopkeeperform"] = $this->Model_shopkeeper_form->getFormDatabyId($id); 
            $this->data['shopkeeperform']['phone_1'] = $this->formatPhone($this->data['shopkeeperform']['phone_1']);
            $this->data['shopkeeperform']['phone_2'] = $this->formatPhone($this->data['shopkeeperform']['phone_2']);
            $this->data['shopkeeperform']['zipcode'] = $this->formatCep($this->data['shopkeeperform']['zipcode']);
            
            $result = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter, 1);
            $attachment = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter, 3);
            $fields = $this->Model_shopkeeper_field_form->getFieldsbySellerCenter($this->sellercenter, 2);

            $this->data['fields-1'] = $result;
            $this->data['fields-2'] = $fields;
            $this->data['attachments'] = $attachment;

            //$this->data['reproved_reasons'] = $this->model_attributes->getAttributeValuesAndIdByName('reproved_reasons');
            $user_id = $this->session->userdata('id');
            $is_admin = ($user_id == 1) ? true :false;
            
            $usercomp = $this->session->userdata('usercomp');
            $usergroup = $this->session->userdata('group_id');
            $this->data['usercomp'] = $usercomp;
            $this->data['usergroup'] = $usergroup;
            $this->data['only_admin'] = "1";

            $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
            $this->data['shopkeeperform']['sellerCenter'] = $settingSellerCenter['value'];
            $this->data['shopkeeperform']['page_title'] = 'Mar Aberto';
            $this->data['page_now'] = 'Mar Aberto';

            $settingheader = $this->model_settings->getSettingDatabyName('header_description_shopkeeperform');
            $this->data['shopkeeperform']['header_description'] = $settingheader ? $settingheader['value'] : "";

            $settinglogo = $this->model_settings->getSettingDatabyName('logotipo_shopkeeperform');
            $this->data['shopkeeperform']['file_logotipo'] = $settinglogo ? $settinglogo['value'] : "";
            
            $this->data['banks'] = $this->model_banks->getBanks();
            $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
            $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;

            $this->load->view('shopkeeperform/complete', $this->data);
        }
    }

    public function create($id_user = null)
    {
        $result = $this->Model_shopkeeper_form->getFormDatabySellerCenter($this->sellercenter);
        $settingheader = $this->model_settings->getSettingDatabyName('header_description_shopkeeperform');
        $this->data['shopkeeperform']['header_description'] = $settingheader ? $settingheader['value'] : "";
        
        $settinglogo = $this->model_settings->getSettingDatabyName('logotipo_shopkeeperform');
        $this->data['shopkeeperform']['file_logotipo'] = $settinglogo ? $settinglogo['value'] : false; 
        
        $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        //$this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|is_unique[stores.CNPJ]');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_email'), 'trim|required|valid_email');
        $this->form_validation->set_rules('phone_1', $this->lang->line('application_phone'), 'trim|required');
        $this->form_validation->set_rules('phone_2', $this->lang->line('application_phone'), 'trim|required');
        //->form_validation->set_rules('raz_soc', $this->lang->line('application_name'), 'trim|required');
        
        //$this->form_validation->set_rules('form-1[]', $this->lang->line('application_setting_name'), 'trim|required');

        if ($this->form_validation->run() == TRUE) {    
            $data = array();

            $data = array(
                'name' => $this->postClean('name'),
                'responsible_email' => $this->postClean('responsible_email'),
                'responsible_name' => $this->postClean('responsible_name'),
                'phone_1' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_1')),
                'phone_2' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_2')),
                'sellercenter' => $this->sellercenter,
                'status' => 4,
                'user_create' => $id_user
            );

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
                $data['pj_pf'] = $this->postClean('pj_pf');
            }

            if ($this->postClean('utm_source')) {
                $data['utm_source'] = str_replace(' ', '', $this->postClean('utm_source'));
            }

            $result = $this->Model_shopkeeper_form->create($data);
            if($id_user){
                redirect('ShopkeeperForm/complete/'.$result.'/'.$id_user, 'refresh');
            }else{
                redirect('ShopkeeperForm/complete/'.$result, 'refresh');
            }
            
        }
        
        //$result = $this->model_settings->getSettingData();

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->data['shopkeeperform']['sellerCenter'] = $settingSellerCenter['value'];
        $this->data['shopkeeperform']['page_title'] = 'Mar Aberto';
        $this->data['page_now'] = 'Mar Aberto';

        $this->load->view('shopkeeperform/create', $this->data);
        
    }

    public function edit($id)
    {

        if(!in_array('updateShopkeeperForm', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $usar_mascara_banco = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
        
        $this->data['banks'] = $this->model_banks->getBanks();
        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        
        
        //$this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('address', $this->lang->line('application_address'), 'trim|required');
        $this->form_validation->set_rules('addr_num', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('addr_compl', $this->lang->line('application_complement'), 'trim');
        $this->form_validation->set_rules('addr_neigh', $this->lang->line('application_neighb'), 'trim|required');
        $this->form_validation->set_rules('addr_city', $this->lang->line('application_city'), 'trim|required');
        $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        $this->form_validation->set_rules('country', $this->lang->line('application_country'), 'trim|required');
        $this->form_validation->set_rules('zipcode', $this->lang->line('application_zip_code'), 'trim|required');

        if ($this->postClean('same') != "1") {
            $this->form_validation->set_rules('business_street', $this->lang->line('application_address'), 'trim|required');
            $this->form_validation->set_rules('business_addr_num', $this->lang->line('application_number'), 'trim|required');
            $this->form_validation->set_rules('business_addr_compl', $this->lang->line('application_complement'), 'trim');
            $this->form_validation->set_rules('business_neighborhood', $this->lang->line('application_neighb'), 'trim|required');
            $this->form_validation->set_rules('business_town', $this->lang->line('application_city'), 'trim|required');
            $this->form_validation->set_rules('business_uf', $this->lang->line('application_uf'), 'trim|required');
            $this->form_validation->set_rules('business_nation', $this->lang->line('application_country'), 'trim|required');
            $this->form_validation->set_rules('business_code', $this->lang->line('application_zip_code'), 'trim|required');
        }

        if ($this->postClean('pj_pf') == "pf" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
            $this->form_validation->set_rules('CPF', $this->lang->line('application_cpf'), 'trim|required|callback_checkCPF|is_unique[stores.CNPJ]');
        } else {
            $this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|is_unique[stores.CNPJ]');
        }
        //$this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|callback_checkUniqueCNPJ');

        $this->form_validation->set_rules('responsible_name', $this->lang->line('application_responsible_name'), 'trim|required');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_responsible_email'), 'trim|required|valid_email');
        $this->form_validation->set_rules('responsible_cpf', $this->lang->line('application_responsible_cpf'), 'trim|callback_checkCPF');

        $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
        $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
        $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
        $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');

        //$this->form_validation->set_rules('form-1[]', $this->lang->line('application_setting_name'), 'trim|required');
       
        if ($this->form_validation->run() == TRUE) {
            $data = array();
            $data['status'] = 2;

            $bank = (is_null($this->postClean('bank'))) ? "" : $this->postClean('bank');
            $agency = (is_null($this->postClean('agency'))) ? "" : $this->postClean('agency');
            $account_type = (is_null($this->postClean('account_type'))) ? "" : $this->postClean('account_type');
            $account = (is_null($this->postClean('account'))) ? "" : $this->postClean('account');

            $valorProduto = (is_null($this->postClean('service_charge_value'))) ? "" : $this->postClean('service_charge_value'); 

            if(array_key_exists('service_charge_freight_option',$this->postClean())){
               $valorFrete = $valorProduto;   
            }else{ 
               $valorFrete = (is_null($this->postClean('service_charge_freight_value'))) ? "" : $this->postClean('service_charge_freight_value'); 
            }

            foreach ($this->data['banks'] as $local_bank) {
                if ( $usar_mascara_banco == true) {
                    if ($local_bank['name'] == $bank) {
     
                        if(strlen($account) != strlen($local_bank['mask_account'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_account') . $local_bank['mask_account']);
                            redirect('ShopkeeperForm/edit/'.$id, 'refresh');
                        }
                        if( strlen($agency) != strlen($local_bank['mask_agency'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_agency') . $local_bank['mask_agency']);
                            redirect('ShopkeeperForm/edit/'.$id, 'refresh');

                        }
                        continue;
                    }
                 }else{
                    continue;
                 }
            }
            $data = array(
                'name' => $this->postClean('name'),
                'address' => $this->postClean('address'),
                'addr_num' => $this->postClean('addr_num'),
                'addr_compl' => $this->postClean('addr_compl'),
                'addr_neigh' => $this->postClean('addr_neigh'),
                'addr_city' => $this->postClean('addr_city'),
                'addr_uf' => $this->postClean('addr_uf'),
                'zipcode' => preg_replace('/[^\d\+]/', '', $this->postClean('zipcode')),
                'phone_1' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_1')),
                'phone_2' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_2')),
                'country' => $this->postClean('country'),
                'raz_social' => $this->postClean('pj_pf') == "pf" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? $this->postClean('name') : $this->postClean('raz_soc'),
                //'company_id' => 18,
                'responsible_cs' => $this->postClean('responsible_cs'),
                'prefix' => strtoupper(substr(md5(uniqid(mt_rand(99999, 99999999), true)), 0, 5)),
                'CNPJ' => $this->numberString($this->postClean('pj_pf') == "pf" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? $this->postClean('CPF') : $this->postClean('CNPJ'), false),
                'responsible_name' => $this->postClean('responsible_name'),
                'responsible_email' => $this->postClean('responsible_email'),

                'responsible_mother_name' => $this->postClean('responsible_mother_name'),
                'responsible_position' => $this->postClean('responsible_position'),

                'responsible_cpf' => $this->postClean('responsible_cpf'),
                'bank' => $bank,
                'agency' => $agency,
                'account_type' => $account_type,
                'account' => $account,
                'business_street' => $this->postClean('same') == "1" ? $this->postClean('address') : $this->postClean('business_street'),
                'business_addr_num' => $this->postClean('same') == "1" ? $this->postClean('addr_num') : $this->postClean('business_addr_num'),
                'business_addr_compl' => $this->postClean('same') == "1" ? $this->postClean('addr_compl') : $this->postClean('business_addr_compl'),
                'business_neighborhood' => $this->postClean('same') == "1" ? $this->postClean('addr_neigh') : $this->postClean('business_neighborhood'),
                'business_town' => $this->postClean('same') == "1" ? $this->postClean('addr_city') : $this->postClean('business_town'),
                'business_uf' => $this->postClean('same') == "1" ? $this->postClean('addr_uf') : $this->postClean('business_uf'),
                'business_nation' => $this->postClean('same') == "1" ? $this->postClean('country') : $this->postClean('business_nation'),
                'business_code' => $this->postClean('same') == "1" ? $this->postClean('zipcode') : $this->postClean('business_code'),
                'service_charge_value' => $valorProduto,
                'service_charge_freight_value' => $valorFrete,
                'utm_source' => $this->postClean('utm_source')
                //'user_create' => $this->session->userdata('id')
            );

            $update = $this->Model_shopkeeper_form->update($data, $id);
    
            if($update == true) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('ShopkeeperForm/list', 'refresh');
                
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('ShopkeeperForm/edit/'.$id, 'refresh');
            }
        }
		else {
            $this->data["shopkeeperform"] = $this->Model_shopkeeper_form->getFormDatabyId($id);
            
            if(!isset($this->data['shopkeeperform']['service_charge_value'])) {
                $this->data['shopkeeperform']["service_charge_value"] = $this->model_settings->getValueIfAtiveByName('service_charge_value_shopkeeperform');
            }
            if(!isset($this->data['shopkeeperform']['service_charge_freight_value'])) {
                $this->data['shopkeeperform']["service_charge_freight_value"] = $this->model_settings->getValueIfAtiveByName('service_charge_freight_shopkeeperform');
            }
            
            if(isset($this->data['shopkeeperform']['phone_1']))
                $this->data['shopkeeperform']['phone_1'] = $this->formatPhone($this->data['shopkeeperform']['phone_1']);

            if(isset($this->data['shopkeeperform']['phone_2']))
                $this->data['shopkeeperform']['phone_2'] = $this->formatPhone($this->data['shopkeeperform']['phone_2']);

            if(isset($this->data['shopkeeperform']['zipcode']))
                $this->data['shopkeeperform']['zipcode'] = $this->formatCep($this->data['shopkeeperform']['zipcode']);
            
            $result = [];

            $result[1] = $this->Model_shopkeeper_field_value->getFieldValueByFormIdandType($id, 1);
            $result[2] = $this->Model_shopkeeper_field_value->getFieldValueByFormIdandType($id, 2);

            $attachment = $this->Model_shopkeeper_field_value->getFieldValueByFormIdandType($id, 3);
                 
            $result = array_merge(...$result);

            $this->data['fields'] = $result;
            $this->data['attachments'] = $attachment;   
            $this->data['reproved_reasons'] = $this->model_attributes->getAttributeValuesAndIdByName('reproved_reasons');
            $user_id = $this->session->userdata('id');
            $is_admin = ($user_id == 1) ? true :false;

            $usercomp = $this->session->userdata('usercomp');
            $usergroup = $this->session->userdata('group_id');

            $setting_seller_group_user = $this->model_settings->getSettingDatabyName('group_id_user_seller');
            $seller_group_user = $setting_seller_group_user['value'];

            if($seller_group_user){
                $seller_group_user = explode(",", $seller_group_user);
                $this->data['shopkeeperform']['user_id'] = in_array($usergroup, $seller_group_user) ? $user_id : false;
            }else{
                $this->data['shopkeeperform']['user_id'] = false;
            }

            $this->data['usercomp'] = $usercomp;
            $this->data['usergroup'] = $usergroup;
            $this->data['only_admin'] = "1";

            $this->data['get_attribute_value_utm_param'] = $this->Model_attributes->getAttributeValueUtmParam();
            $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;

            $this->render_template('shopkeeperform/edit', $this->data);
		}

            /** 
            if ($_FILES['form-3']['size'] > 0) {
                $upload_image = $this->upload_image();
                $upload_image = array('attachment' => $upload_image);
                $this->Model_shopkeeper_form->update($upload_image, $result);
            }

            $field = array();
            
            if($this->postClean('form-1[]'))
            {
                foreach($this->postClean('form-1[]') as $value)
                {
                    $field['field_value'] = $value;
                    $field['field_form_id'] = $result;
                    $this->Model_shopkeeper_field_value->create($field);
                }
            }
        }
        */
        //$result = $this->model_settings->getSettingData();
        
    }

    

	public function insertLogoForm()
	{

		if(!in_array('createFieldShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();
        
        if (!empty($_FILES['logotipo_header']["name"])) {
            $upload_image = $this->upload_image('logotipo_header');
            
        	$data = array(
        		'value' => $upload_image,
        	); 
        	
            $settingId = $this->model_settings->getSettingbyName('logotipo_shopkeeperform');
            
            if($settingId){
                $create = $this->model_settings->update($data, $settingId);
            }else{ 
                $data['name'] = "logotipo_shopkeeperform";
                $create = $this->model_settings->create($data);
            }

            if($create == true) {
        		$response['success'] = true;
        		$response['messages'] = $this->lang->line('messages_successfully_created');
        	}
        	else {
        		$response['success'] = false;
        		$response['messages'] = $this->lang->line('messages_error_database_create_setting');
        	}
        }
        else {
        	$response['success'] = false;
        	foreach ($_POST as $key => $value) {
        		$response['messages'][$key] = form_error($key);
        	}   
        }
        ob_clean();
        echo json_encode($response);

	} 

    public function insertSuccessDescription()
	{

		if(!in_array('createFieldShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$this->form_validation->set_rules('success_description', $this->lang->line('application_link_redirect'), 'trim|required');
		
        if ($this->form_validation->run() == TRUE) {
        	$data = array(
        		'value' => $this->postClean('success_description'),
        	);

            $settingId = $this->model_settings->getSettingbyName('success_description_shopkeeperform');
            if($settingId){
                $create = $this->model_settings->update($data, $settingId);
            }else{ 
                $data['name'] = "success_description_shopkeeperform";
                $create = $this->model_settings->create($data);
            }

            if($create == true) {
        		$response['success'] = true;
        		$response['messages'] = $this->lang->line('messages_successfully_created');
        	}
        	else {
        		$response['success'] = false;
        		$response['messages'] = $this->lang->line('messages_error_database_create_setting');
        	}
        }
        else {
        	$response['success'] = false;
        	foreach ($_POST as $key => $value) {
        		$response['messages'][$key] = form_error($key);
        	}   
        }
        ob_clean();
        echo json_encode($response);

	}


    public function insert()
	{

		if(!in_array('createFieldShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();

		$this->form_validation->set_rules('field_name', $this->lang->line('application_setting_name'), 'trim|required');
		//$this->form_validation->set_rules('setting_value', $this->lang->line('application_setting_value'), 'trim|required');
		//$this->form_validation->set_rules('active', $this->lang->line('application_active'), 'trim|required');

		//$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

        if ($this->form_validation->run() == TRUE) {
        	$data = array(
        		'label' => $this->postClean('field_name'),
        		'required' => $this->postClean('field_required'),
        		'visible' => $this->postClean('field_visible'),
                'type' => $this->postClean('field_type'),
                'sellercenter' => $this->sellercenter,
        	);

        	$create = $this->Model_shopkeeper_field_form->createField($data);
        	if($create == true) {
        		$response['success'] = true;
        		$response['messages'] = $this->lang->line('messages_successfully_created');
        	}
        	else {
        		$response['success'] = false;
        		$response['messages'] = $this->lang->line('messages_error_database_create_setting');
        	}
        }
        else {
        	$response['success'] = false;
        	foreach ($_POST as $key => $value) {
        		$response['messages'][$key] = form_error($key);
        	}   
        }
        ob_clean();
        echo json_encode($response);

	}

	public function insertTitleHeader()
	{

		if(!in_array('createFieldShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();

		$this->form_validation->set_rules('header_description', $this->lang->line('application_header_title'), 'trim|required');
		
        if ($this->form_validation->run() == TRUE) {
        	$data = array(
        		'value' => $this->postClean('header_description'),
        	);

            $settingId = $this->model_settings->getSettingbyName('header_description_shopkeeperform');
            if($settingId)
                $create = $this->model_settings->update($data, $settingId);
            else
                $create  = false;

        	if($create == true) {
        		$response['success'] = true;
        		$response['messages'] = $this->lang->line('messages_successfully_created');
        	}
        	else {
        		$response['success'] = false;
        		$response['messages'] = $this->lang->line('messages_error_database_create_setting');
        	}
        }
        else {
        	$response['success'] = false;
        	foreach ($_POST as $key => $value) {
        		$response['messages'][$key] = form_error($key);
        	}   
        }
        ob_clean();
        echo json_encode($response);

	}

	/*
	* Its checks the Setting form validation 
	* and if the validation is successfully then it updates the data into the database 
	* and returns the json format operation messages
	*/
	public function update($id)
	{
		if(!in_array('updateShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();

		if($id) {
            $this->form_validation->set_rules('edit_label', $this->lang->line('application_setting_name'), 'trim|required');
            //$this->form_validation->set_rules('edit_setting_value', $this->lang->line('application_setting_value'), 'trim|required');
            //$this->form_validation->set_rules('edit_active', $this->lang->line('application_active'), 'trim|required');

			//$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

            $this->data['get_attribute_value_utm_param'] = $this->Model_attributes->getAttributeValueUtmParam($id);

	        if ($this->form_validation->run() == TRUE) {
	        	$data = array(
	        		'label' => $this->postClean('edit_label'),
        		    'required' => $this->postClean('edit_required'),
        		    'visible' => $this->postClean('edit_visible'),
                    'type' => $this->postClean('edit_type'),
                    'sellercenter' => $this->sellercenter,
	        	);

	        	$update = $this->Model_shopkeeper_field_form->updateField($data, $id);
                
	        	if($update == true) {
	        		$response['success'] = true;
	        		$response['messages'] = $this->lang->line('messages_successfully_updated');
	        	}
	        	else {
	        		$response['success'] = false;
	        		$response['messages'] = $this->lang->line('messages_error_database_update_setting');
	        	}
	        }
	        else {
	        	$response['success'] = false;
	        	foreach ($_POST as $key => $value) {
	        		$response['messages'][$key] = form_error($key);
	        	}
	        }

            
		}
		else {
			$response['success'] = false;
    		$response['messages'] = $this->lang->line('messages_refresh_page_again');
		}
        ob_clean();
		echo json_encode($response);
	}

    public function reproved()
	{
		if(!in_array('updateShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		
		$id = $this->postClean('id');
		$id_reason = $this->postClean('reason');
        $response = array();
		if($id) {
			$reproved = $this->Model_shopkeeper_form->reproved($id, $id_reason);
            
			if($reproved == true) {
				$response['success'] = true;
				$response['messages'] = $this->lang->line('messages_successfully_removed');
			}
			else {
				$response['success'] = false;
				$response['messages'] = $this->lang->line('messages_error_database_remove_setting');
			}
		}
		else {
			$response['success'] = false;
			$response['messages'] = $this->lang->line('messages_refresh_page_again');
		}
        ob_clean();
		echo json_encode($response);
	}

	/*
	* It removes the Setting information from the database 
	* and returns the json format operation messages
	*/
	public function aproved()
	{
		if(!in_array('updateShopkeeperForm', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		
		$id = $this->postClean('id');
		$response = array();

		if($id) {

            if($this->postClean('service_charge_value') !== '' &&  $this->postClean('service_charge_freight_value') == ''){

                $service_charge = $this->postClean('service_charge_value');
                $service_charge_freight = $this->postClean('service_charge_value');

            }else if($this->postClean('service_charge_value') !== '' && $this->postClean('service_charge_freight_value') !== ''){

                $service_charge = $this->postClean('service_charge_value');
                $service_charge_freight = $this->postClean('service_charge_freight_value');

            }else{

                $service_charge = $this->model_settings->getSettingDatabyName('service_charge_value_shopkeeperform');
                $service_charge_freight = $this->model_settings->getSettingDatabyName('service_charge_freight_shopkeeperform');

            }

            $data = array(
                'service_charge_value' => $service_charge,
                'service_charge_freight_value' => $service_charge_freight,
            );

			$shopkeeperForm = $this->Model_shopkeeper_form->getFormDatabyId($id ,$data);


            if($this->model_stores->uniqueCnpj($shopkeeperForm['CNPJ'])){
                $aproved = $this->Model_shopkeeper_form->aproved($id);
                $usercomp = $this->session->userdata('usercomp');

                $data = array(
                    'name' => $shopkeeperForm['name'],
                    'raz_social' => $shopkeeperForm['raz_social'],
                    'IEST' => $shopkeeperForm['insc_estadual'],
                    'address' => $shopkeeperForm['address'],
                    'addr_num' => $shopkeeperForm['addr_num'],
                    'addr_compl' => $shopkeeperForm['addr_compl'],
                    'addr_neigh' => $shopkeeperForm['addr_neigh'],
                    'addr_compl' => $shopkeeperForm['addr_compl'],
                    'addr_city' => $shopkeeperForm['addr_city'],
                    'addr_uf' => $shopkeeperForm['addr_uf'],
                    'zipcode' => $shopkeeperForm['zipcode'],
                    'phone_1' => $shopkeeperForm['phone_1'],
                    'phone_2' => $shopkeeperForm['phone_2'],
                    'country' => $shopkeeperForm['country'],
                    'logo' => "logo",
                    'reputacao' => "reputacao",
                    'parent_id' => $usercomp ? $usercomp : 1,
                    'pj_pf' => 'PJ',
                    'prefix' => strtoupper(substr(md5(uniqid(mt_rand(99999, 99999999), true)), 0, 5)),
                    'CNPJ' => $shopkeeperForm['CNPJ'],
                    'email' => $shopkeeperForm['responsible_email'],
                    'bank' => $shopkeeperForm['bank'],
                    'agency' => $shopkeeperForm['agency'],
                    'account_type' => $shopkeeperForm['account_type'],
                    'account' => $shopkeeperForm['account'],
                    'responsible_sac_name' => $shopkeeperForm['responsible_name'],
                    'responsible_sac_email' => $shopkeeperForm['responsible_email'],
                    'currency' => 'BRL',
                    'message' => "message",
                );

                $company = $this->Model_company->create($data);

                $datastore = array(
                    'name' => $shopkeeperForm['name'],
                    'company_id' => $company,
                    'active' => 1,
                    'address' => $shopkeeperForm['address'],
                    'addr_num' => $shopkeeperForm['addr_num'],
                    'addr_compl' => $shopkeeperForm['addr_compl'],
                    'addr_neigh' => $shopkeeperForm['addr_neigh'],
                    'addr_city' => $shopkeeperForm['addr_city'],
                    'addr_uf' => $shopkeeperForm['addr_uf'],
                    'zipcode' => $shopkeeperForm['zipcode'],
                    'phone_1' => $shopkeeperForm['phone_1'],
                    'phone_2' => $shopkeeperForm['phone_2'],
                    'country' => $shopkeeperForm['country'],
                    'raz_social' => $shopkeeperForm['raz_social'],
                    'prefix' => strtoupper(substr(md5(uniqid(mt_rand(99999, 99999999), true)), 0, 5)),
                    'CNPJ' => $shopkeeperForm['CNPJ'],
                    //'email' => $shopkeeperForm['responsible_email'],
                    'bank' => $shopkeeperForm['bank'],
                    'agency' => $shopkeeperForm['agency'],
                    'account_type' => $shopkeeperForm['account_type'],
                    'account' => $shopkeeperForm['account'],
                    'inscricao_estadual' => $shopkeeperForm['insc_estadual'],
                    'responsible_name' => $shopkeeperForm['responsible_name'],
                    'responsible_email' => $shopkeeperForm['responsible_email'],

                    'responsible_mother_name' => $shopkeeperForm['responsible_mother_name'],
                    'responsible_position' => $shopkeeperForm['responsible_position'],

                    'responsible_cpf' => $shopkeeperForm['responsible_cpf'],
                    'business_street' => $shopkeeperForm['business_street'],
                    'business_addr_num' => $shopkeeperForm['business_addr_num'],
                    'business_addr_compl' => $shopkeeperForm['business_addr_compl'],
                    'business_neighborhood' => $shopkeeperForm['business_neighborhood'],
                    'business_town' => $shopkeeperForm['business_town'],
                    'business_uf' => $shopkeeperForm['business_uf'],
                    'business_nation' => $shopkeeperForm['country'],
                    'business_code' => $shopkeeperForm['zipcode'],
                    'token_api' => "",
                    'user_create' => $this->session->userdata('id'),
                    'service_charge_value' => $service_charge,
                    'service_charge_freight_value' => $service_charge_freight,
                    'seller' => $shopkeeperForm['user_create'],
                    'utm_source' => $shopkeeperform['utm_source'],

                );  

                $store = $this->model_stores->create($datastore);

                $token = $this->createTokenAPI($store, $company);
                $this->model_stores->update(array("token_api" => $token), $store);

                $name = explode( " ", $shopkeeperForm['responsible_name']);

                $len = strlen($name[0]);
                $last_name = substr($shopkeeperForm['responsible_name'], $len + 1);

                $password = $this->random_pwd();
                $passwordHash = $this->password_hash($password);

                $dataUser = array(
                    'username' => $shopkeeperForm['responsible_email'],
                    'password' => $passwordHash,
                    'email' => $shopkeeperForm['responsible_email'],
                    'firstname' => $name[0],
                    'lastname' => $last_name ? $last_name : "",
                    'phone' => $shopkeeperForm['phone_1'],
                    'gender' => 0,
                    'company_id' => $company,
                    'parent_id' => $usercomp ? $usercomp : 1,
                    'previous_passwords' => "",
                    'active' => 1,
                    'store_id' => $store,
                    'bank' => $shopkeeperForm['bank'],
                    'agency' => $shopkeeperForm['agency'],
                    'account_type' => $shopkeeperForm['account_type'],
                    'account' => $shopkeeperForm['account'],
                    'associate_type' => 0,
                    'last_change_password' => date('Y-m-d H:i:s'),
                );
                
                $id_group = $this->model_settings->getSettingDatabyName('id_group_shopkeeperform');
                $aproved = $this->model_users->create($dataUser, $id_group['value']);

                $userPass = array(
                    'user_id' => $aproved,
                    'temp_pass' => $password,
                );

                $passcreate = $this->model_users->createUserPass($userPass); 

                if($passcreate == true) {
                    $response['sucess'] = true;
                    $response['messages'] = $this->lang->line('application_approved');
                }
                else {
                    $response['sucess'] = false;
                    $response['messages'] = $this->lang->line('application_error_has_ocurred');
                }
            }
            else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('application_cnpj_duplicate');
            }
        }
        ob_clean();
		echo json_encode($response);
	}

    public function createTokenAPI($cod_store, $cod_company)
    {
        $key = get_instance()->config->config['encryption_key'];

        $payload = array(
            "cod_store" => $cod_store,
            "cod_company" => $cod_company
        );

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        return $this->jwt->encode($payload, $key);
    }

    public function password_hash($pass = '')
    {
        if ($pass) {
            $password = password_hash($pass, PASSWORD_DEFAULT);
            return $password;
        }
    }
    
    public function numberString($string = null, $array = true)
    {
        if (!$string)
            return false;

        $num = array();
        for ($i = 0; $i < (strlen($string)); $i++) {
            if (is_numeric($string[$i]))
                $num[] = $string[$i];
        }

        if ($array)
            return $num;

        return implode('', $num);
    }

    
    /*
     * This function is invoked from another function to upload the image into the assets folder
     * and returns the image path
     */
    public function upload_image($file)
    {
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath, 'assets');
        $serverpath = substr($serverpath, 0, $pos);

        $targetDir = $serverpath . 'assets/images/shopkeeperform_attachment';
        

        if (!file_exists($targetDir)) {
            // cria o diretorio para o produto receber as imagens
            // força mod 775
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        } 

        // assets/images/company_image
        $config['upload_path'] = $targetDir;
        $config['file_name'] = uniqid();
        $config['allowed_types'] = 'gif|jpg|png|pdf';
        $config['max_size'] = '2000';

        // $config['max_width']  = '1024';s
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload($file)) {
            $error = $this->upload->display_errors();
            return $error;
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES[$file]['name']);
            $type = $type[count($type) - 1];
            $path = $this->upload->data();

            $pathFull = $config['upload_path'] . '/' . $path['file_name'];
            return ($data == true) ? $pathFull : false;
        }
        
    }
	
	function checkCNPJ($cnpj) {
		$ok = $this->isCnpjValid($cnpj);
		if (!$ok) {
			 $this->form_validation->set_message('checkCNPJ', '{field} inválido.');
		}
		return $ok;
		
	}
	
	function checkCPF($cpf) {
		$ok = $this->isCPFValid($cpf);
		if (!$ok) {
			 $this->form_validation->set_message('checkCPF', '{field} inválido.');
		}
		return $ok;
		
	}

	function isCnpjValid($cnpj){
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
		return $isCnpjValid;			
	}

	function isCPFValid($cpf) {
	    // Extrai somente os números
	    $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
	     
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

    function checkInscricaoEstadual($ie, $uf)
    {
        $ok = ValidatesIE::check($ie, $uf);
        if (!$ok) {
            $this->form_validation->set_message('checkInscricaoEstadual', '{field} inválida.');
        }
        return $ok;
    }

    public function validate_file($file)
    {
   
        if (!empty($_FILES['form-3_0']['name'])) {
            $allowedExtensions = array('gif', 'jpg', 'png', 'pdf');
            
            $fileExtension = pathinfo($_FILES['form-3_0']['name'], PATHINFO_EXTENSION);    
            if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
              
                $this->form_validation->set_message('validate_file', 'O arquivo deve ter extensão gif, jpg, png ou pdf');
                return false; 
            }
        }

        return true;
    }
    
}