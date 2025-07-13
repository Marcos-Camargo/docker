<?php
/*
 
Controller de Catalogos de Produtos 

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

class VertemStoreConfig extends Admin_Controller 
{

	var $int_to = 'VS';
	var $integration_master = null;
	
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_vertem_store');

		$this->load->model('model_integrations');
		$this->load->model('model_stores');
		
		$this->integration_master = $this->model_integrations->getIntegrationsbyCompIntType('1','VS',"CONECTALA","DIRECT",'0');
	}

	 public function index()
    {
        if (!in_array('marketplaces_integrations', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		if (!$this->integration_master) {
			$this->session->set_flashdata('error', $this->lang->line('application_integration_vertem_store_not_enable'));
			redirect('dashboard', 'refresh');
		}
		$this->data['tocreate'] = $this->model_integrations->getActivetStoresWithOutIntegration($this->int_to, 7 );
        $this->render_template('vertemstoreconfig/index', $this->data);
    }

    public function fetchIntegrationData()
    {
		
		if (!$this->integration_master) {
			$this->session->set_flashdata('error', $this->lang->line('application_integration_vertem_store_not_enable'));
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
                $procura = " AND ( s.name like '%" . $busca['value'] . "%' OR c.name like '%" . $busca['value'] . "%'  
                OR i.auth_data like '%" . $busca['value'] . "%' ) ";
            }
        } else {
            if (trim($postdata['loja'])) {
                $procura .= " AND s.name like '%" . $postdata['loja'] . "%'";
            }
            if (trim($postdata['empresa'])) {
                $procura .= " AND c.name like '%" . $postdata['empresa'] . "%'";
            }
			 if (trim($postdata['sellerid'])) {
                $procura .= " AND i.auth_data like '%" . $postdata['sellerid'] . "%'";
            }
            if (trim($postdata['status'])) {
                $procura .= " AND i.active = " . $postdata['status'];
            }
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('s.name',  'c.name', 'i.auth_data', 'p.status', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_integrations->getFecthIntegrations($this->int_to, $ini, $length, $sOrder, $procura);
        $filtered = $this->model_integrations->getCountFecthIntegrations($this->int_to, $procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_integrations->getCountFecthIntegrations($this->int_to);
        }

        $result = array();
        foreach ($data as $key => $value) {
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }
            
			$buttons = '';
			if(in_array('updateUser', $this->permission) || in_array('viewUser', $this->permission)) {
                $buttons .= '<a href="'.base_url('vertemStoreConfig/edit/'.$value['id']).'" class="btn btn-default" ><i class="fa fa-edit" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.$this->lang->line('application_edit').'"></i></a>'; 
			}

            $sellercenter=$this->model_settings->getValueIfAtiveByName('sellercenter');
			$seller_id = '';
			if (!is_null($value['auth_data'])) {
				$auth_data = json_decode($value['auth_data']);
				$seller_id=$auth_data->seller_id;
			}
			
            $result[$key] = array(
                $value['store'],
                $value['company'],
                $seller_id,
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
		ob_clean();
		echo json_encode($output);
		
	}

	public function validateSellerId($seller_id, $store_id = null)
    {

        $exist = $this->model_integrations->verifySellerId($this->int_to, json_encode(array('seller_id' => $seller_id)), $store_id);
        if ($exist) {
            $this->form_validation->set_message('validateSellerId', $this->lang->line('application_supplier_id') . ' ' . $seller_id . ' já cadastrado');
            return FALSE;
        }
        return true;
    }

	public function create()
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }

		if (!$this->integration_master) {
			$this->session->set_flashdata('error', $this->lang->line('application_integration_vertem_store_not_enable'));
			redirect('dashboard', 'refresh');
		}
		
		$this->form_validation->set_rules('store', $this->lang->line('application_store'), 'trim|required');
		$this->form_validation->set_rules('seller_id', $this->lang->line('application_supplier_id'), 'trim|required|callback_validateSellerId['.$this->postClean('store',true).']');
        
		if ($this->form_validation->run() == TRUE) {
			$store_id = $this->postClean('store',true);
			
			$store = $this->model_stores->getStoresData($store_id);
			
			$data_int = array(
					'id' => 0,
					'name' => 'Vertem Store',
					'active' => 1,
					'store_id' => $store['id'], 
					'company_id' => $store['company_id'], 
					'auth_data' => json_encode(array('seller_id' => $this->postClean('seller_id',true))), 
					'int_type' => 'BLING',
					'int_from' => 'HUB', 
					'int_to' => $this->int_to,
					'auto_approve' => '1',
				);
			$create = $this->model_integrations->create($data_int);
			if ($create) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect('vertemStoreConfig/index', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('vertemStoreConfig/create', 'refresh');
            }
		}
		$this->data['stores'] = $this->model_integrations->getActivetStoresWithOutIntegration($this->int_to, 7 );
		$this->data['integration_id'] = "";
		$this->data['seller_id'] = "";
		$this->data['form'] = "create";
		$this->data['token_api'] = "token_api";
		
		$this->render_template('vertemstoreconfig/create', $this->data);
	}

	public function edit($integration_id = null)
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }
		
		if (!$this->integration_master) {
			$this->session->set_flashdata('error', $this->lang->line('application_integration_vertem_store_not_enable'));
			redirect('dashboard', 'refresh');
		}
		
		$integration = $this->model_integrations->getIntegrationsData($integration_id); 
		if (!$integration) {
			$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            redirect('vertemStoreConfig/index/', 'refresh');
		}
		$store = $this->model_stores->getStoresData($integration['store_id']);
		
		$this->form_validation->set_rules('seller_id', $this->lang->line('application_supplier_id'), 'trim|required|callback_validateSellerId['.$store['id'].']');
        
		if ($this->form_validation->run() == TRUE) {

			$data_int = array(
					'id' => $integration_id,
					'name' => 'Vertem Store',
					'active' => 1,
					'store_id' => $store['id'], 
					'company_id' => $store['company_id'], 
					'auth_data' => json_encode(array('seller_id' => $this->postClean('seller_id',true))), 
					'int_type' => 'BLING',
					'int_from' => 'HUB', 
					'int_to' => $this->int_to,
					'auto_approve' => '1',
				);
			$create = $this->model_integrations->update($data_int, $integration_id);
			if ($create) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('vertemStoreConfig/index', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('vertemStoreConfig/edit/'.$integration_id , 'refresh');
            }
		}

		$seller_id=''; 
		if (!is_null($integration['auth_data'])) {
			$auth_data = json_decode($integration['auth_data']);
			$seller_id=$auth_data->seller_id;
		}
				
		$this->data['store'] = $store['name'];
		$this->data['token_api'] =  base64_encode('loja'.$store['id'] . ":" . substr($store['token_api'],0,12)) ;
		$this->data['integration_id'] = $integration_id;
		$this->data['seller_id'] = $seller_id;
		$this->data['form'] = "edit";
		$this->render_template('vertemstoreconfig/create', $this->data);
	}

	function getTokenApi() 
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }

		$store = $this->model_stores->getStoresData($this->postClean('loja',TRUE));
		$response = array(
			'success' => false,
			'error' 	=> $this->lang->line('messages_store_dont_exist_or_dont_permission_or_not_active'), 
			'token'		=> ''
		);
		if ($store) {
			
			$user ="vertemStore";

			$response = array(
				"success" 	=> true,
				"error" 	=> "",
				"token"		=> base64_encode('loja'.$store['id'] . ":" . substr($store['token_api'],0,12))  
			);
			
		}
		echo json_encode($response);
	}

}