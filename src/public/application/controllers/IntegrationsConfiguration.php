<?php
/*
 
Controller de Catalogos de Produtos 

*/  
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * @property Model_integrations $model_integrations
 * @property Model_stores $model_stores
 * @property Model_settings $model_settings
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 */

class IntegrationsConfiguration extends Admin_Controller 
{
    private $int_from = 'HUB';
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_integrationsconfiguration');

		$this->load->model('model_integrations');
		$this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_queue_products_marketplace');
        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }
        if ( $sellerCenter == 'conectala') {
            $this->int_from = 'CONECTALA';
        }
		
	}

	 public function index()
    {
        if (!in_array('marketplaces_integrations', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['marketplaces'] = $this->model_integrations->getIntegrationsbyStoreId(0);
        $this->render_template('integrationsconfiguration/index', $this->data);
    }

    public function fetchIntegrationData()
    {
		ob_start();
		if (!in_array('marketplaces_integrations', $this->permission)) {
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
                OR  i.int_to like '%" . $busca['value'] . "%'";
            }
        } else {
            if (trim($postdata['loja'])) {
                $procura .= " AND s.name like '%" . $postdata['loja'] . "%'";
            }
            if (trim($postdata['empresa'])) {
                $procura .= " AND c.name like '%" . $postdata['empresa'] . "%'";
            }
            if (trim($postdata['status'])) {
                $procura .= " AND i.active = " . (($postdata['status'] == 1) ? 1 : 0);
            }
            if (trim($postdata['seller_index'])) {
                $procura .= " AND sih.seller_index like '%" . $postdata['seller_index'] . "%'";
            }
            if (trim($postdata['autoapprove'])) {
                $procura .= " AND i.auto_approve = " . (($postdata['autoapprove'] == 1) ? 1 : 0);
            }
            
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('i.id', 'i.int_to', 'c.name', 's.name', 'sih.seller_index', 's.service_charge_value', 's.service_charge_freight_value',  'i.active', 'i.auto_approve', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }
        
        $data = $this->model_integrations->getFecthIntegrationsIndex($postdata['marketplace'], $ini, $length, $sOrder, $procura);
        $filtered = $this->model_integrations->getCountFecthIntegrationsCountIndex($postdata['marketplace'], $procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_integrations->getCountFecthIntegrationsCountIndex($postdata['marketplace']);
        }

        $result = array();
        foreach ($data as $key => $value) {
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }

			if ($value['auto_approve'] == 1) {
                $auto_approve = '<span class="label label-success">' . $this->lang->line('application_approve') . '</span>';
            } else {
                $auto_approve = '<span class="label label-danger">' . $this->lang->line('application_curatorship') . '</span>';
            }

            if ($value['seller_index'] >= 4 ) {
                $seller_index = '<span class="label label-success">' .$value['seller_index'] . '</span>';
            } elseif ($value['seller_index'] <= 2) {
                $seller_index = '<span class="label label-danger">' .$value['seller_index'] . '</span>';
            } else {
                $seller_index = '<span class="label label-warning">' . $value['seller_index'] . '</span>';
            }
            
            $buttons = '<button onclick="editStoreIntegration(event,\''.$value['id'].'\',\''.$postdata['marketplace'].'\',\''.str_replace("'"," ",$value['store']).'\',\''.str_replace("'"," ",$value['company']).'\',\''.str_replace("'"," ",$value['auto_approve']).'\',\''.str_replace("'"," ",$value['active']).'\')" class="btn btn-default" >'.$this->lang->line('application_edit').' <i class="fas fa-edit"></i></button>';

            $result[$key] = array(
                $value['id'],
				$value['int_to'],
				$value['company'],
                $value['store'],
                $seller_index, 
                $value['service_charge_value'],
                $value['service_charge_freight_value'],
                $status,
				$auto_approve, 
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

	public function fetchWithoutIntegrationData()
    {
		ob_start();
		if (!in_array('marketplaces_integrations', $this->permission)) {
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
                $procura = " AND ( s.name like '%" . $busca['value'] . "%' OR c.name like '%" . $busca['value'] . "%' ";
            }
        } else {
            if (trim($postdata['loja'])) {
                $procura .= " AND s.name like '%" . $postdata['loja'] . "%'";
            }
            if (trim($postdata['empresa'])) {
                $procura .= " AND c.name like '%" . $postdata['empresa'] . "%'";
            }
            if (trim($postdata['seller_index'])) {
                $procura .= " AND sih.seller_index like '%" . $postdata['seller_index'] . "%'";
            }
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array( 's.id', 'c.name', 's.name', 'sih.seller_index', 's.service_charge_value', 's.service_charge_freight_value', '', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_integrations->getFecthIntegrationsWithoutIntTo($postdata['marketplace'], $ini, $length, $sOrder, $procura);
        $filtered = $this->model_integrations->getCountFecthIntegrationsWithoutIntTo($postdata['marketplace'], $procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_integrations->getCountFecthIntegrationsWithoutIntTo($postdata['marketplace']);
        }

        $result = array();
        foreach ($data as $key => $value) {
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }

            $buttons = '<button onclick="createStoreIntegration(event,\''.$value['id'].'\',\''.$postdata['marketplace'].'\',\''.str_replace("'"," ",$value['store']).'\',\''.str_replace("'"," ",$value['company']).'\')" class="btn btn-default" >'.$this->lang->line('application_new').' <i class="fas fa-puzzle-piece"></i></button>';

            if ($value['seller_index'] >= 4 ) {
                $seller_index = '<span class="label label-success">' .$value['seller_index'] . '</span>';
            } elseif ($value['seller_index'] <= 2) {
                $seller_index = '<span class="label label-danger">' .$value['seller_index'] . '</span>';
            } else {
                $seller_index = '<span class="label label-warning">' . $value['seller_index'] . '</span>';
            }

            $result[$key] = array(
                $value['id'],
				$value['company'],
                $value['store'],
                $seller_index, 
                $value['service_charge_value'],
                $value['service_charge_freight_value'],
                $postdata['marketplace'],                
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

    public function tocreateSeveral(){
		$postdata = $this->postClean(NULL,TRUE);

        if ($postdata['id_integrate_several'] =='FILTER') {
            $procura = '';
            if (trim($postdata['filter_stores'])) {
                $procura .= " AND s.name like '%" . $postdata['filter_stores'] . "%'";
            }
            if (trim($postdata['filter_company'])) {
                $procura .= " AND c.name like '%" . $postdata['filter_company'] . "%'";
            }
            if (trim($postdata['filter_seller_index'])) {
                $procura .= " AND sih.seller_index like '%" . $postdata['filter_seller_index'] . "%'";
            }
            $filtered = $this->model_integrations->getCountFecthIntegrationsWithoutIntTo($postdata['filter_marketplace'], $procura);
            $datas = $this->model_integrations->getFecthIntegrationsWithoutIntTo($postdata['filter_marketplace'], 0, $filtered, '', $procura);  
        }
        else {
            $ids = explode(';',$postdata['id_integrate_several']);
            
            $datas = array();
            foreach($ids as $id) {
                if ($id != '') {
                    $datas[] = array('id' =>  $id);
                }   
            }
        }

        $auto_approve = false; 
        if (array_key_exists('auto_approve_several', $postdata)) {
            $auto_approve = $postdata['auto_approve_several'];
        }
      
		Foreach($datas as $store) {
			if (!$this->createIntegration($postdata['filter_marketplace'], $store['id'], $auto_approve)) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('IntegrationsConfiguration/index');
            }
		}
        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
		redirect('IntegrationsConfiguration/index');
		
	}

    private function createIntegration($int_to, $store_id, $auto_approve)
    {

        $integration = $this->model_integrations->getIntegrationByIntTo($int_to, 0);
        $store = $this->model_stores->getStoresData($store_id);
        $data_int = array(
            'name'          => $integration['name'],
            'active'        => ($store['active'] == 1),
            'store_id'      => $store_id, 
            'company_id'    => $store['company_id'], 
            'auth_data'     => json_encode(array('date_created'=>date('Y-m-d H:i:s'))), 
            'int_type'      => 'BLING',
            'int_from'      => $this->int_from, 
            'int_to'        => $int_to,
            'auto_approve'  => ($auto_approve =='on') ,
        );

        $exists = $this->model_integrations->getIntegrationsbyCompIntType($store['company_id'], $int_to, 'HUB', 'BLING', $store_id);
        if (!$exists) {
            $this->log_data('IntegrationsConfiguration', 'createIntegrationMarketplace', json_encode($data_int), "I");
            return $this->model_integrations->create($data_int);
        }
        return false ;
      
    }	

	public function toCreate()
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }
		
		$this->form_validation->set_rules('create_store_id_integration', $this->lang->line('application_id'), 'trim|required');
		$this->form_validation->set_rules('create_int_to_integration', $this->lang->line('application_marketplace'), 'trim|required');
        
		if ($this->form_validation->run()) {
            $postdata = $this->postClean(NULL,TRUE);
			$store_id =  $postdata['create_store_id_integration'];
			$int_to = $postdata['create_int_to_integration'];
            $auto_approve = false; 
            if (array_key_exists('create_auto_approve_integration', $postdata)) {
                $auto_approve = $postdata['create_auto_approve_integration'];
            }           
            if ($this->createIntegration($int_to, $store_id, $auto_approve)) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            }
		}
		redirect('IntegrationsConfiguration/index');
	}

    public function toEditSeveral(){
		$postdata = $this->postClean(NULL,TRUE);

        if ($postdata['int_id_integrate_several'] =='FILTER') {
            $procura = '';
            if (trim($postdata['int_filter_stores'])) {
                $procura .= " AND s.name like '%" . $postdata['int_filter_stores'] . "%'";
            }
            if (trim($postdata['int_filter_company'])) {
                $procura .= " AND c.name like '%" . $postdata['int_filter_company'] . "%'";
            }
            if (trim($postdata['int_filter_status'])) {
                $procura .= " AND i.active = ".(($postdata['int_filter_status'] == 1) ? 1 : 0 );
            }
            if (trim($postdata['int_filter_seller_index'])) {
                $procura .= " AND sih.seller_index like '%" . $postdata['int_filter_seller_index'] . "%'";
            }
            $filtered = $this->model_integrations->getCountFecthIntegrationsCountIndex($postdata['int_filter_marketplace'], $procura);
            $datas = $this->model_integrations->getFecthIntegrationsIndex($postdata['int_filter_marketplace'], 0, $filtered, '', $procura);  
        }
        else {
            $ids = explode(';',$postdata['int_id_integrate_several']);
            
            $datas = array();
            foreach($ids as $id) {
                if ($id != '') {
                    $datas[] = array('id' =>  $id);
                }   
            }
        }
        $auto_approve = false; 
        if (array_key_exists('int_auto_approve_several', $postdata)) {
            $auto_approve = $postdata['int_auto_approve_several'];
        } 
        $status = false; 
        if (array_key_exists('int_active_several', $postdata)) {
            $status = $postdata['int_active_several'];
        } 

		Foreach($datas as $integration) {
			if (!$this->editIntegration($integration['id'],$auto_approve, $status)) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('IntegrationsConfiguration/index');
            }
		}
        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
		redirect('IntegrationsConfiguration/index');
		
	}

    private function editIntegration($id, $auto_approve, $active)
    {
        $int_edit = array (
            'active'        =>($active =='on'),
            'auto_approve'  =>($auto_approve =='on'), 
        );

        $integration = $this->model_integrations->getIntegrationsData($id);

        // Existia curadoria e agora não.
        if ($integration['auto_approve'] == 0 && $int_edit['auto_approve']) {
            $store_id = $integration['store_id'];
            $products_to_send_to_queue = array_map(function ($product) {
                return array(
                    'status' => 0,
                    'prd_id' => $product['prd_id']
                );
            }, $this->model_integrations->getProductsToDisapprovedByStoreId($store_id));
            // Atualizar todos os produtos para aprovados.
            $this->model_integrations->updateProductsToApprovedByStoreId($store_id);

            // Adicionar os produtos na fila.
            $this->model_queue_products_marketplace->create($products_to_send_to_queue, true);
        }

        $ok = $this->model_integrations->update($int_edit, $id);
        $int_edit['id'] = $id; 
        $this->log_data('IntegrationsConfiguration', 'editIntegrationMarketplace', json_encode($int_edit), "I");
        return $ok;
    }

    public function toEdit()
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }
		
		$this->form_validation->set_rules('edit_id_integration', $this->lang->line('application_id'), 'trim|required');
		$this->form_validation->set_rules('edit_int_to_integration', $this->lang->line('application_marketplace'), 'trim|required');
        
		if ($this->form_validation->run()) {
            $postdata = $this->postClean(NULL,TRUE);
            $auto_approve = false; 
            if (array_key_exists('edit_auto_approve_integration', $postdata)) {
                $auto_approve = $postdata['edit_auto_approve_integration'];
            }
            $status = false; 
            if (array_key_exists('edit_active_integration', $postdata)) {
                $status = $postdata['edit_active_integration'];
            } 
            if ($this->editIntegration($postdata['edit_id_integration'], $auto_approve, $status)) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            }
		}
		redirect('IntegrationsConfiguration/index');
	}

}