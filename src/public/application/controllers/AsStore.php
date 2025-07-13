<?php
/*
SW Serviços de Informática 2019


*/   
defined('BASEPATH') OR exit('No direct script access allowed');

class AsStore extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_logs');

		$this->load->model('model_stores');
		$this->load->model('model_company');
        $this->load->model('model_users');
	}

	public function change() 
	{
		$msgCompanyStory='';
		if(!in_array('changeStore', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		$this->form_validation->set_rules('company', $this->lang->line('application_store'), 'trim|required');
		$this->form_validation->set_rules('store_id', $this->lang->line('application_store'), 'trim|required');
		
		if ($this->form_validation->run() == TRUE) {
			$store_id = $this->postClean('store_id');
			$company_id = $this->postClean('company');

			if ($store_id ==0) {
				$store_id = 0;
				if ($company_id == 1) {
					$msg = $this->lang->line('messages_back_to_all_stores');
				}
				else {
					$company = $this->model_company->getCompanyData($company_id);
					$msg = $this->lang->line('messages_managing_company').$company['name'];
					$msgCompanyStory.=$this->lang->line('application_company').": <b>".$company['name']."</b>";
				}	
			}
			else {
				$company = $this->model_company->getCompanyData($company_id);
				$msgCompanyStory.=$this->lang->line('application_company').": <b>".$company['name']."</b>&nbsp;&nbsp;&nbsp;";
				$store = $this->model_stores->getStoresData($store_id);
				$msgCompanyStory.=$this->lang->line('application_store').": <b>".$store['name']."</b>";
				$company_id = $store['company_id'];
				$msg = $this->lang->line('messages_managing_store').$store['name'];
			}

			$logged_in_sess = array(
                        'id' 		=> $this->session->userdata('id'),
                        'username'  => $this->session->userdata('username'), 
                        'email'     => $this->session->userdata('email'), 
                        'usercomp'  => $company_id,
                        'userstore' => $store_id,
                        'group_id'  => $this->session->userdata('group_id'),
                        'logged_in' => TRUE
                    );

			$this->data['usercomp'] = $company_id;
			$this->data['userstore'] = $store_id;
			$this->session->set_userdata($logged_in_sess);
			$this->session->set_userdata('company_and_store',$msgCompanyStory);
			$this->session->set_flashdata('success', $msg);
			redirect('dashboard', 'refresh');
		}

        $dataUser       = $this->model_users->getUserData($this->session->userdata('id'));
        $companyCurrent = $dataUser['company_id'];
        $storeCurrent   = $dataUser['store_id'];

        $this->data['company_data']     = $this->model_company->getCompanyDataById($companyCurrent);
        $this->data['all_stores'] = [];
        foreach ($this->data['company_data'] as $company_data) {
            $stores = $this->model_stores->getStoresByCompany($company_data['id']);
            if ($stores){
                $this->data['all_stores'] = array_merge($this->data['all_stores'], $stores);
            }
        }

        $this->data['companyCurrent']   = $companyCurrent;
        $this->data['storeCurrent']     = $storeCurrent;

		$this->render_template('asstore/change', $this->data);
	}
	
}