<?php
/*
SW Serviços de Informática 2019
 
Controller de Clientes

*/   

defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_clients');

		$this->load->model('model_clients');
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
	}
	/* 
	* It only redirects to the manage order page
	*/
	public function index()
	{
		if(!in_array('viewClients', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = $this->lang->line('application_manage_clients');
		$this->render_template('clients/index', $this->data);		
	}

	/*
	* Fetches the orders data from the orders table 
	* this function is called from the datatable ajax function
	*/
	public function fetchClientsData()
	{
		$result = array('data' => array());

		$data = $this->model_clients->getClientsData();

		foreach ($data as $key => $value) {

			// button
			$buttons = '';

			if(in_array('viewClients', $this->permission)) {
				$buttons .= '<a target="__blank" href="'.base_url('clients/printDiv/'.$value['id']).'" class="btn btn-default"><i class="fa fa-print"></i></a>';
			}

			if(in_array('updateClients', $this->permission)) {
				$buttons .= ' <a href="'.base_url('clients/update/'.$value['id']).'" class="btn btn-default"><i class="fa fa-edit"></i></a>';
			}
			$result['data'][$key] = array(
				'<a href="'.base_url('clients/update/'.$value['id']).'">'.$value['id'].'</a>',
				$value['customer_name'],
				$value['customer_address'].", ".$value['addr_num'].", ".$value['addr_compl'].", ".$value['addr_neigh'],
				$value['phone_1']."/".$value['phone_2'],
				$value['origin'],
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}


    /* 
    * It redirects to the company page and displays all the company information
    * It also updates the company information into the database if the 
    * validation for each input field is successfully valid
    */
	public function update($id)
	{  
        if(!in_array('updateClients', $this->permission) && !in_array('viewClients', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
		$this->form_validation->set_rules('nome', 'Name', 'trim|required');
		$this->form_validation->set_rules('address', 'Address', 'trim|required');
		$this->form_validation->set_rules('addr_num', 'Number', 'trim|required');
		$this->form_validation->set_rules('addr_compl', 'Complement', 'trim');
		$this->form_validation->set_rules('addr_neigh', 'Neighborhood', 'trim|required');
		$this->form_validation->set_rules('addr_city', 'City', 'trim|required');
		$this->form_validation->set_rules('addr_uf', 'UF', 'trim|required');
		$this->form_validation->set_rules('country', 'Country', 'trim|required');
		$this->form_validation->set_rules('zipcode', 'ZipCode', 'trim|required');
		$this->form_validation->set_rules('phone_1', 'Phone 1', 'trim|required');
		$this->form_validation->set_rules('phone_2', 'Phone 2', 'trim');
		$this->form_validation->set_rules('email', 'e-Mail', 'trim|required');
		$this->form_validation->set_rules('isPJ', 'Is Company', 'trim|required');

		if ($this->postClean('isPJ',TRUE)) {
			$this->form_validation->set_rules('gestor', 'Gestor', 'trim|required');
			$this->form_validation->set_rules('raz_soc', 'Razão Social', 'trim|required');
			$this->form_validation->set_rules('CNPJ', 'CNPJ', 'trim|required');
		} else {
			$this->form_validation->set_rules('CPF', 'CPF', 'trim|required');
		}

	
		$this->form_validation->set_rules('currency', 'Currency', 'trim|required');
		$this->form_validation->set_rules('bank', 'bank', 'trim|required');
		$this->form_validation->set_rules('agency', 'agency', 'trim|required');
		$this->form_validation->set_rules('account', 'account', 'trim|required');
		$this->form_validation->set_rules('iban', 'IBAN', 'trim');

		$fields = Array();
		if (!is_null($this->postClean('crcli'))) {
			$fields['nome'] = $this->postClean('nome',TRUE);
			$fields['isPJ'] = $this->postClean('isPJ',TRUE);
			$fields['raz_soc'] = $this->postClean('raz_soc',TRUE);
			$fields['CNPJ'] = $this->postClean('CNPJ',TRUE);
			$fields['CPF'] = $this->postClean('CPF',TRUE);
			$fields['gestor'] = $this->postClean('gestor',TRUE);
			$fields['email'] = $this->postClean('email',TRUE);
			$fields['phone_1'] = $this->postClean('phone_1',TRUE);
			$fields['phone_2'] = $this->postClean('phone_2',TRUE);
			$fields['zipcode'] = $this->postClean('zipcode',TRUE);
			$fields['address'] = $this->postClean('address',TRUE);
			$fields['addr_num'] = $this->postClean('addr_num',TRUE);
			$fields['addr_compl'] = $this->postClean('addr_compl',TRUE);
			$fields['addr_neigh'] = $this->postClean('addr_neigh',TRUE);
			$fields['addr_city'] = $this->postClean('addr_city',TRUE);
			$fields['addr_uf'] = $this->postClean('addr_uf',TRUE);
			$fields['country'] = $this->postClean('country',TRUE);
			$fields['currency'] = $this->postClean('currency',TRUE);
			$fields['bank'] = $this->postClean('bank',TRUE);
			$fields['agency'] = $this->postClean('agency',TRUE);
			$fields['account'] = $this->postClean('account',TRUE);			
		}
		$this->data['fields'] = $fields;


	
        if ($this->form_validation->run() == TRUE) {
            // true case

			if ($this->postClean('isPJ',TRUE)) {
				$cpf_cnpj = $this->postClean('CNPJ',TRUE);
			} else {
				$cpf_cnpj = $this->postClean('CPF',TRUE);
			}	
		    $startdt = new DateTime('now'); // setup a local datetime
		    $start_format = $startdt->format('Y-m-d H:i:s');

        	$data = array(
	        	'id_arq' => $this->data['usercomp'],
        		'nome' => $this->postClean('nome',TRUE),
        		'contato' => $this->postClean('gestor',TRUE),
        		'email' => $this->postClean('email',TRUE),
        		'logradouro' => $this->postClean('address',TRUE),
        		'numero' => $this->postClean('addr_num',TRUE),
        		'complemento' => $this->postClean('addr_compl',TRUE),
        		'bairro' => $this->postClean('addr_neigh',TRUE),
        		'cidade' => $this->postClean('addr_city',TRUE),
        		'UF' => $this->postClean('addr_uf',TRUE),
        		'CEP' => $this->postClean('zipcode',TRUE),
        		'telefone_1' => $this->postClean('phone_1',TRUE),
        		'telefone_2' => $this->postClean('phone_2',TRUE),
        		'pais' => $this->postClean('country',TRUE),
                'moeda' => $this->postClean('currency',TRUE),
        		'razao_social' => $this->postClean('raz_soc',TRUE),
        		'tipo' => $this->postClean('isPJ',TRUE),
        		'status' => 1,
				'data_inclusao' => $start_format,
				'data_alteracao' => '',
        		'cpf_cnpj' => $cpf_cnpj,
        		'banco' => $this->postClean('bank',TRUE),
        		'agencia' => $this->postClean('agency',TRUE),
        		'conta' => $this->postClean('account',TRUE),
        		'iban' => $this->postClean('IBAN',TRUE),
        	);

        	$update = $this->model_clients->update($data, $id);
        	if($update == true) {
        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        		redirect('clients/', 'refresh');
        	}
        	else {
        		$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        		redirect('clients/update', 'refresh');
        	}
        }
        else {
            $this->data['currency_symbols'] = $this->currency();
        	$client_data = $this->model_clients->getClientsData($id);
        	$this->data['client_data'] = $client_data;
			// SW - Log Update
			get_instance()->log_data('Clientes','edit before',json_encode($client_data),"I");
			if (!is_null($this->postClean('crcli'))) {
        		$this->session->set_flashdata('error', 'Form Errors:');
			} else {
				$fields = Array();
				$fields['nome'] = $client_data['customer_name'];
				$fields['isPJ'] = $this->client_type($client_data);
				if($fields['isPJ']==1){
					// $fields['raz_soc'] = $client_data['razao_social'];
					// $fields['CNPJ'] = $client_data['cpf_cnpj'];
				}else{
					$fields['CPF'] = $client_data['cpf_cnpj'];
				}
				
				$fields['gestor'] = $client_data['origin'];
				$fields['email'] = $client_data['email'];
				$fields['phone_1'] = $client_data['phone_1'];
				$fields['phone_2'] = $client_data['phone_2'];
				$fields['zipcode'] = $client_data['zipcode'];
				$fields['address'] = $client_data['customer_address'];
				$fields['addr_num'] = $client_data['addr_num'];
				$fields['addr_compl'] = $client_data['addr_compl'];
				$fields['addr_neigh'] = $client_data['addr_neigh'];
				$fields['addr_city'] = $client_data['addr_city'];
				$fields['addr_uf'] = $client_data['addr_uf'];
				$fields['country'] = $client_data['country'];
				$this->data['fields'] = $fields;
			}
			$this->data['page_title'] = $this->lang->line('application_edit_clients');
			$this->render_template('clients/edit', $this->data);			
        }	

		
	}
	private function client_type($client_data){
		$client_data['cpf_cnpj']=preg_replace('/[^0-9]/', '', $client_data['cpf_cnpj']);
		if(strlen($client_data['cpf_cnpj'])==11){
			return 0;
		}else{
			return 1;
		}
	}
    /* 
    * It redirects to the company page and displays all the company information
    * It also updates the company information into the database if the 
    * validation for each input field is successfully valid
    */
	public function create()
	{  
        if(!in_array('createClients', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
		$this->form_validation->set_rules('nome', $this->lang->line('application_name'), 'trim|required');
		$this->form_validation->set_rules('address', $this->lang->line('application_address'), 'trim|required');
		$this->form_validation->set_rules('addr_num', $this->lang->line('application_number'), 'trim|required');
		$this->form_validation->set_rules('addr_compl', $this->lang->line('application_complement'), 'trim');
		$this->form_validation->set_rules('addr_neigh', $this->lang->line('application_neighb'), 'trim|required');
		$this->form_validation->set_rules('addr_city', $this->lang->line('application_city'), 'trim|required');
		$this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
		$this->form_validation->set_rules('country', $this->lang->line('application_country'), 'trim|required');
		$this->form_validation->set_rules('zipcode', $this->lang->line('application_zip_code'), 'trim|required');
		$this->form_validation->set_rules('phone_1', $this->lang->line('application_phone') . " 1", 'trim|required');
		$this->form_validation->set_rules('phone_2', $this->lang->line('application_phone') . " 2", 'trim');
		$this->form_validation->set_rules('email', $this->lang->line('application_email'), 'trim|required');
		$this->form_validation->set_rules('isPJ', $this->lang->line('application_is_company'), 'trim|required');

		if ($this->postClean('isPJ',TRUE)) {
			$this->form_validation->set_rules('gestor', $this->lang->line('application_gestor'), 'trim|required');
			$this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');
			$this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required');
		} else {
			$this->form_validation->set_rules('CPF', $this->lang->line('application_cpf'), 'trim|required');
		}
	
		$this->form_validation->set_rules('currency', $this->lang->line('application_currency'), 'trim|required');
		$this->form_validation->set_rules('bank', $this->lang->line('application_code_bank'), 'trim|required');
		$this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
		$this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
		$this->form_validation->set_rules('iban', 'IBAN', 'trim');

		$fields = Array();
		if (!is_null($this->postClean('crcli'))) {
			$fields['nome'] = $this->postClean('nome',TRUE);
			$fields['isPJ'] = $this->postClean('isPJ',TRUE);
			$fields['raz_soc'] = $this->postClean('raz_soc',TRUE);
			$fields['CNPJ'] = $this->postClean('CNPJ',TRUE);
			$fields['CPF'] = $this->postClean('CPF',TRUE);
			$fields['gestor'] = $this->postClean('gestor',TRUE);
			$fields['email'] = $this->postClean('email',TRUE);
			$fields['phone_1'] = $this->postClean('phone_1',TRUE);
			$fields['phone_2'] = $this->postClean('phone_2',TRUE);
			$fields['zipcode'] = $this->postClean('zipcode',TRUE);
			$fields['address'] = $this->postClean('address',TRUE);
			$fields['addr_num'] = $this->postClean('addr_num',TRUE);
			$fields['addr_compl'] = $this->postClean('addr_compl',TRUE);
			$fields['addr_neigh'] = $this->postClean('addr_neigh',TRUE);
			$fields['addr_city'] = $this->postClean('addr_city',TRUE);
			$fields['addr_uf'] = $this->postClean('addr_uf',TRUE);
			$fields['country'] = $this->postClean('country',TRUE);
			$fields['currency'] = $this->postClean('currency',TRUE);
			$fields['bank'] = $this->postClean('bank',TRUE);
			$fields['agency'] = $this->postClean('agency',TRUE);
			$fields['account'] = $this->postClean('account',TRUE);			
		} else {
			$fields['nome'] = '';
			$fields['isPJ'] = '';
			$fields['raz_soc'] = '';
			$fields['CNPJ'] = '';
			$fields['CPF'] = '';
			$fields['gestor'] = '';
			$fields['email'] = '';
			$fields['phone_1'] = '';
			$fields['phone_2'] = '';
			$fields['zipcode'] = '';
			$fields['address'] = '';
			$fields['addr_num'] = '';
			$fields['addr_compl'] = '';
			$fields['addr_neigh'] = '';
			$fields['addr_city'] = '';
			$fields['addr_uf'] = '';
			$fields['country'] = '';
			$fields['currency'] = '';
			$fields['bank'] = '';
			$fields['agency'] = '';
			$fields['account'] = '';			
		}
		$this->data['fields'] = $fields;
		
        if ($this->form_validation->run() == TRUE) {
            // true case

			if ($this->postClean('isPJ',TRUE)) {
				$cpf_cnpj = $this->postClean('CNPJ',TRUE);
			} else {
				$cpf_cnpj = $this->postClean('CPF',TRUE);
			}	
		    $startdt = new DateTime('now'); // setup a local datetime
		    $start_format = $startdt->format('Y-m-d H:i:s');

        	$data = array(
	        	'id_arq' => $this->data['usercomp'],
        		'nome' => $this->postClean('nome',TRUE),
        		'contato' => $this->postClean('gestor',TRUE),
        		'email' => $this->postClean('email',TRUE),
        		'logradouro' => $this->postClean('address',TRUE),
        		'numero' => $this->postClean('addr_num',TRUE),
        		'complemento' => $this->postClean('addr_compl',TRUE),
        		'bairro' => $this->postClean('addr_neigh',TRUE),
        		'cidade' => $this->postClean('addr_city',TRUE),
        		'UF' => $this->postClean('addr_uf',TRUE),
        		'CEP' => $this->postClean('zipcode',TRUE),
        		'telefone_1' => $this->postClean('phone_1',TRUE),
        		'telefone_2' => $this->postClean('phone_2',TRUE),
        		'pais' => $this->postClean('country',TRUE),
                'moeda' => $this->postClean('currency',TRUE),
        		'razao_social' => $this->postClean('raz_soc',TRUE),
        		'tipo' => $this->postClean('isPJ',TRUE),
        		'status' => 1,
				'data_inclusao' => $start_format,
				'data_alteracao' => '',
        		'cpf_cnpj' => $cpf_cnpj,
        		'banco' => $this->postClean('bank',TRUE),
        		'agencia' => $this->postClean('agency',TRUE),
        		'conta' => $this->postClean('account',TRUE),
        		'iban' => $this->postClean('IBAN',TRUE),
        	);

        	$insert = $this->model_clients->create($data);
        	if($insert == true) {
        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
        		redirect('clients/', 'refresh');
        	}
        	else {
        		$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        		redirect('clients/create', 'refresh');
        	}
        }
        else {
            $this->data['currency_symbols'] = $this->currency();
			$this->data['page_title'] = $this->lang->line('application_add_clients');
			if (!is_null($this->postClean('crcli'))) {
        		$this->session->set_flashdata('error', 'Erro no fomulário:');
			}
			$this->render_template('clients/create', $this->data);			
        }	

		
	}
    /*
    * This function is invoked from another function to upload the image into the assets folder
    * and returns the image path
    */
	public function upload_image()
    {
    	// assets/images/company_image
        $config['upload_path'] = 'assets/images/company_image';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = '1000';

        // $config['max_width']  = '1024';s
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if ( ! $this->upload->do_upload('company_image'))
        {
            $error = $this->upload->display_errors();
            return $error;
        }
        else
        {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['company_image']['name']);
            $type = $type[count($type) - 1];
            
            $path = $config['upload_path'].'/'.$config['file_name'].'.'.$type;
            return ($data == true) ? $path : false;            
        }
    }


}