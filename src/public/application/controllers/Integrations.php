<?php
/*
SW Serviços de Informática 2019

Controller de Integrações

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Integration_v2\Applications\Resources\IntegrationERPResource as IntegrationERP;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;
use libraries\Helpers\StringHandler;

require_once APPPATH . 'libraries/Helpers/StringHandler.php';
require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationERPResource.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php';
require_once APPPATH . 'libraries/Helpers/StringHandler.php';
require_once APPPATH . 'libraries/Integration_v2/linx_microvix/Controllers/AuthApp.php';

/**
 * @property CI_Lang $lang
 * @property CI_Output $output
 * @property CI_Loader $load
 * @property CI_Upload $upload
 * @property CI_Form_validation $form_validation
 * @property CI_Session $session
 *
 *
 * @property Model_company $model_company
 * @property Model_stores $model_stores
 * @property Model_attributes $model_attributes
 * @property Model_integrations $model_integrations
 * @property Model_integrations_webhook $model_integrations_webhook
 * @property Model_calendar $model_calendar
 * @property Model_catalogs $model_catalogs
 * @property Model_settings $model_settings
 * @property Model_api_integrations $model_api_integrations
 * @property Model_integration_erps $model_integration_erps
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_providers $model_providers
 * @property Model_integration_logistic $model_integration_logistic
 */
class Integrations extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_integrations');

		$this->load->model('model_company');
		$this->load->model('model_stores');
		$this->load->model('model_attributes');
        $this->load->model('model_integrations');
        $this->load->model('model_integrations_webhook');
        $this->load->model('model_calendar');
        $this->load->model('model_catalogs');
        $this->load->model('model_settings');
        $this->load->model('model_api_integrations');
        $this->load->model('model_integration_erps');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_providers');
        $this->load->model('model_integration_logistic');
        $this->load->helper('text');
        $this->load->library('JWT');

		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$more = " company_id = ".$usercomp;

        	// attributes 
        	$attribute_data = $this->model_attributes->getActiveAttributeData('integrations');

        	$attributes_final_data = array();
        	foreach ($attribute_data as $k => $v) {
        		$attributes_final_data[$k]['attribute_data'] = $v;

        		$value = $this->model_attributes->getAttributeValueData($v['id']);

        		$attributes_final_data[$k]['attribute_value'] = $value;
        	}

        	$this->data['integrations_info'] = $attributes_final_data;
		
	}

    /*
    * It only redirects to the manage  page
    */
	public function index()
	{
        if(!in_array('viewIntegrations', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->render_template('integrations/index', $this->data);	
	}

    /*
    * It Fetches the products data from the product table
    * this function is called from the datatable ajax function
    */
	public function fetchIntegrationsData()
	{
		$result = array('data' => array());

		$data = $this->model_integrations->getIntegrationsData();
		foreach ($data as $key => $value) {

            $store_data = $this->model_stores->getStoresData($value['store_id']);
			// button
            $buttons = '';
            if(in_array('updateIntegrations', $this->permission)) {
    			$buttons .= '<a href="'.base_url('integrations/update/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }

            if(in_array('deleteIntegrations', $this->permission)) { 
    			$buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['name'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }

			if ($this->data['usergroup'] < 5) {
				$company = $this->model_company->getMyCompanyData($value['company_id']);
				$result['data'][$key] = array(
					$company['name'],
					$value['name'],
					$value['int_from'],
	                $value['int_to'],
	                $value['int_type'],
	                $value['active'],
					$buttons
				);
			} else {
				$result['data'][$key] = array(
					$value['name'],
					$value['int_from'],
	                $value['int_to'],
	                $value['int_type'],
	                $value['active'],
					$buttons
				);
			}
			
			
		} // /foreach

		echo json_encode($result);
	}	

    /*
    * If the validation is not valid, then it redirects to the create page.
    * If the validation for each input field is valid then it inserts the data into the database
    * and it stores the operation message into the session flashdata and display on the manage product page
    */
	public function create()
	{
		if(!in_array('createIntegrations', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->form_validation->set_rules('int_name', $this->lang->line('application_name'), 'trim|required');
		$this->form_validation->set_rules('int_from', $this->lang->line('application_from'), 'trim|required');
		$this->form_validation->set_rules('int_to', $this->lang->line('application_to'), 'trim|required');
		$this->form_validation->set_rules('int_auth_data', $this->lang->line('application_auth_data'), 'trim|required');
        $this->form_validation->set_rules('int_type', $this->lang->line('application_type'), 'trim|required');
//        $this->form_validation->set_rules('int_type', 'Type', 'trim|required');

        if ($this->form_validation->run() == TRUE) {
            // true case
        	$data = array(
        		'name' => $this->postClean('int_name', TRUE),
        		'int_from' => $this->postClean('int_from', TRUE),
        		'int_to' => $this->postClean('int_to', TRUE),
        		'auth_data' => $this->postClean('int_auth_data', TRUE),
        		'int_type' => $this->postClean('int_type', TRUE),
        		'active' => $this->postClean('int_active', TRUE),
        		'company_id' => $this->data['usercomp'],
        	);

        	$create = $this->model_integrations->create($data);
        	if($create == true) {
        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
        		redirect('integrations/', 'refresh');
        	}
        	else {
        		$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
        		redirect('integrations/create', 'refresh');
        	}
        }
        else {
            // false case

            $this->render_template('integrations/create', $this->data);
        }	
	}


    /*
    * If the validation is not valid, then it redirects to the edit product page
    * If the validation is successfully then it updates the data into the database
    * and it stores the operation message into the session flashdata and display on the manage product page
    */
	public function update($id)
	{      
        if(!in_array('updateIntegrations', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if(!$id) {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('int_name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('int_from', $this->lang->line('application_from'), 'trim|required');
        $this->form_validation->set_rules('int_to', $this->lang->line('application_to'), 'trim|required');
        $this->form_validation->set_rules('int_auth_data', $this->lang->line('application_auth_data'), 'trim|required');
        $this->form_validation->set_rules('int_type', $this->lang->line('application_type'), 'trim|required');
        //        $this->form_validation->set_rules('int_type', 'Type', 'trim|required');

        if ($this->form_validation->run() == TRUE) {
            // true case
        	$data = array(
        		'name' => $this->postClean('int_name', TRUE),
        		'int_from' => $this->postClean('int_from', TRUE),
        		'int_to' => $this->postClean('int_to', TRUE),
        		'auth_data' => $this->postClean('int_auth_data', TRUE),
        		'int_type' => $this->postClean('int_type', TRUE),
        		'active' => $this->postClean('int_active', TRUE),
        	);

        	$create = $this->model_integrations->update($data,$id);
        	if($create == true) {
        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        		redirect('integrations/', 'refresh');
        	}
        	else {
        		$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
        		redirect('integrations/update', 'refresh');
        	}
        }
        else {
            // false case
            $int_data = $this->model_integrations->getIntegrationsData($id);
            $this->data['int_data'] = $int_data;

            $this->render_template('integrations/edit', $this->data);
        }	
	}


    /*
    * It removes the data from the database
    * and it returns the response into the json format
    */
	public function remove()
	{
        if(!in_array('deleteIntegrations', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $product_id = $this->postClean('int_id', TRUE);

        $response = array();
        if($int_id) {
            $delete = $this->model_integrations->remove($product_id);
            if($delete == true) {
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_removed');
            }
            else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_remove_product');
            }
        }
        else {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_refresh_page_again');
        }

        echo json_encode($response);
	}

    public function log_integration()
    {
        $stores = $this->model_stores->getStoresWithLogIntegration();
        $this->data['storesView'] = $stores;

        $this->render_template('integrations/log_integration', $this->data);
    }

    public function fetchLogIntegrationData()
    {
        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        $busca = $postdata['search'];
        $store = $postdata['store_id'];// buscar jobs de loja específica
        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value'])>1) {  // Garantir no minimo 1 letras
                $procura = " AND (l.title like '%".$busca['value']."%' 
                             OR l.type like '%".$busca['value']."%' 
                             OR s.name like '%".$busca['value']."%' 
                             OR DATE_FORMAT(l.date_updated,'%d/%m/%Y %H:%i:%s') like '%".$busca['value']."%')";
            }
        }

        if (empty($store)) {
            $store = 0;
        }

        if (!empty($store) && !$this->model_stores->checkIfTheStoreIsMine($store)) {
            $store = 0;
        }

        $procura .= " AND l.store_id = $store ";

        $arrTypes = $postdata['filter_type'];
        $procuraFilterTypes = "AND (";
        foreach ($arrTypes as $type => $status) {
            if($status == 1){
                if ($procuraFilterTypes != "AND (") $procuraFilterTypes .= " OR ";
                $procuraFilterTypes .= "l.type = '{$type}'";
            }
        }
        $procuraFilterTypes .= ")";

        if ($procuraFilterTypes == "AND ()") $procuraFilterTypes = "";

        $procura .= $procuraFilterTypes;


        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('l.type','l.title','s.name','l.date_updated');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }

        $result = array();

        $data = empty($store) ? array() : $this->model_integrations->getLogsIntegration($ini, $sOrder, $procura,$store);

        $i = 0;
        foreach ($data as $key => $value) {
            $i++;

            switch ($value['type']) {
                case "S":
                    $type = '<span class="label label-success">'.$this->lang->line('application_success').'</span>';
                    break;
                case "W":
                    $type = '<span class="label label-warning">'.$this->lang->line('application_alert').'</span>';
                    break;
                case "E":
                    $type = '<span class="label label-danger">'.$this->lang->line('application_error').'</span>';
                    break;
                default:
                    $type = '<span class="label label-info">'.$this->lang->line('application_error').'</span>';
            }
            if($value['unique_id']) {
                $buttonLog = '<button class="btn btn-default btnhistoryLogUniqueId btn-sm"  data-toggle="modal" data-target="#historyLogUniqueId" log-id="'.$value['unique_id'].'" store="'.$value['store_id'].'" ><i class="fa fa-eye"></i></a>';
            } else {
                $buttonLog = '<button class="btn btn-default btn-view btn-sm" log-id="'.$value['id'].'"><i class="fa fa-eye"></i></a>';
            }
            
            $result[$key] = array(
                $type,
                $value['title'],
                $value['store'],
                date('d/m/Y H:i:s', strtotime($value['date_updated'])),
                $buttonLog,
            );

        } // /foreach
        $total_count = empty($store) ? 0 : $this->model_integrations->getCountLogsIntegration($procura,$store);
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total_count,
            "recordsFiltered" => $total_count,
            "data" => $result
        );

        echo json_encode($output);
    }

    public function viewLogIntegration()
    {
        $log_id = (int)$this->postClean('log_id', TRUE);

        if (!$log_id) {
            echo json_encode(array('success' => false, 'data' => "Não foi possível recuperar os dados desse log."));
            exit();
        }

        echo json_encode(array('success' => true, 'data' => $this->model_integrations->viewLogsIntegration($log_id)));
    }

    public function job_integration()
    {
        // lojas com integração com ERP ativas
        $stores = $this->model_stores->getActiveStore();
        $erpIntegration = array();
        foreach ($stores as $_store) {
            // verifica se o código da loja realmente pertence a empresa
            $dataIntegration = $this->model_stores->getDataApiIntegration($_store['id']);
            if ($dataIntegration) {
                $erpIntegration[] = $dataIntegration['integration'];
            }
        }
        $this->data['erp_integration'] = $erpIntegration;

        $useCatalogIntegrationERP = false;
        $settingUseCatalogERP = $this->model_settings->getSettingDatabyName('use_catalog_integration_erp');
        if ($settingUseCatalogERP && $settingUseCatalogERP['status'] == 1)
            $useCatalogIntegrationERP = true;

        $this->data['useCatalogIntegrationERP'] = $useCatalogIntegrationERP;
        $this->data['storesView'] = $this->model_stores->getStoreIntegration();
        $this->render_template('integrations/job_integration', $this->data);
    }

    public function fetchJobIntegrationData()
    {
        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        $busca = $postdata['search'];
        $store = $postdata['store_id'];// buscar jobs de loja específica
        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value'])>1) {  // Garantir no minimo 1 letras
                $procura = " AND (j.integration like '%".$busca['value']."%' 
                             OR j.name like '%".$busca['value']."%' 
                             OR DATE_FORMAT(j.last_run,'%d/%m/%Y %H:%i:%s') like '%".$busca['value']."%'
                             OR s.name like '%".$busca['value']."%') ";
            }
        }

        if ($store == "") {
            if (!in_array('doIntegration', $this->permission)){
                $store = "0";
            }
        }
        if ($store != "")
            $procura .= " AND j.store_id = {$store} ";

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('j.integration','j.name','s.name','j.last_run','j.status');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }

        $result = array();

        $data = $this->model_integrations->getJobsIntegration($ini, $sOrder, $procura);
        $filtered = $this->model_integrations->getCountJobsIntegration($procura);

        $i = 0;
        foreach ($data as $key => $value) {
            $i++;

            $checked = $value['status'] == 1 ? 'checked' : '';

            $btns = '<input type="checkbox" class="person-switch" id="job-'.$value["id"].'" job-id="'.$value["id"].'" '.$checked.'><label for="job-'.$value["id"].'"></label>';
            $btns .= in_array('doIntegration', $this->permission) ?
                '<button class="btn btn-danger btn-sm removeJob" job-id="'.$value["id"].'" data-toggle="tootip" title="Excluir Job"><i class="fa fa-trash"></i></button>
                <button class="btn btn-warning btn-sm emptyDateRun" job-id="'.$value["id"].'" data-toggle="tootip" title="Limpar data da última execução"><i class="fas fa-eraser"></i></button>'
                : '';

            $result[$key] = array(
                $value['integration'],
                $value['name'],
                $value['store'],
                $value['last_run'] ? date('d/m/Y H:i:s', strtotime($value['last_run'])) : 'Nunca executado',
                $btns
            );

        } // /foreach

        $output = array(
            "draw" => $draw,
            "recordsTotal" =>$this->model_integrations->getCountJobsIntegration(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        echo json_encode($output);
    }

    public function updateStatusJobIntegrations()
    {
        $job_id     = (int)$this->postClean('job', TRUE);
        $status     = (int)$this->postClean('status', TRUE);
        $existStore = false;
        $job        = $this->model_integrations->getJobForId($job_id);

        if (!$job_id || !$job) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_data_this_job')));
            exit();
        }

        //echo '<br>L_457_job[status]: '.$job['status'].' - status: '.$status;
        if ($job['status']  == $status) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_save_job_try_again')));
            exit();
        }

        $store = $job['store_id'];

        // Verifica loja
        $stores = $this->model_stores->getActiveStore();
        foreach ($stores as $_store) {
            
            // verifica se o código da loja realmente pertence a empresa
          //  echo '<br>L_470_store_id: '.$_store['id'].' - status: '.$store;
            if($_store['id'] == $store)
                $existStore = true;
        }

        if (!$existStore){
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_log_for_store')));
            exit();
        }

        $update = $this->model_integrations->updateStatusJobForId($status, $job_id);

        if (!$update) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_save_job')));
            exit();
        }

        $this->setStatusJobIntegration($store, $status, $job['job'], $job['integration'], $job['name'], $job['job_path']);

        $sellercenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($sellercenter && $sellercenter['value'] == 'somaplace' && $job['job'] == 'UpdatePriceStock') {
            $this->model_orders_to_integration->cleanOrderQueueByStore($store); // Limpando a fila de pedidos
        }

        echo json_encode(array('success' => true));
        exit();
    }

    /**
     * @param  int      $store          Código da loja
     * @param  int      $status         Situação que a integração ficará. 0=off e 1=on.
     * @param  string   $job            Rotina. (job_integration.job)
     * @param  string   $integration    Nome da integração. (job_integration.integration)
     * @param  string   $name           Nome da rotina. (job_integration.name)
     * @param  string   $job_path       Pasta da rotina. (job_integration.job_path)
     * @throws Exception
     */
    private function setStatusJobIntegration(int $store, int $status, string $job, string $integration, string $name, string $job_path)
    {
        // Define minuto de início das rotinas.
        switch (substr($store, -1)) {
            case 1:
                $minuteStart = '05';
                break;
            case 2:
                $minuteStart = '10';
                break;
            case 3:
                $minuteStart = '15';
                break;
            case 4:
                $minuteStart = '20';
                break;
            case 5:
                $minuteStart = '25';
                break;
            case 6:
                $minuteStart = '30';
                break;
            case 7:
                $minuteStart = '35';
                break;
            case 8:
                $minuteStart = '40';
                break;
            case 9:
                $minuteStart = '45';
                break;
            default:
                $minuteStart = '00';
                break;
        }

        $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');

        $time_product_job = '00:00';
        if ($integration == 'vtex') {
            switch ($sellercenter) {
                case 'somaplace':
                case 'naterra':
                case 'dormed':
                    $time_product_job = '00:00';
                    break;
                case 'privalia':
                case 'aramis':
                case 'oscarcalcados':
                    $time_product_job = '10:00';
                    break;
                case 'epoca':
                case 'polishop':
                case 'lojasmm':
                case 'angeloni':
                case 'Angeloni':
                    $time_product_job = '20:00';
                    break;
                case 'rihappy':
                case 'sicoob':
                    $time_product_job = '30:00';
                    break;
                case 'fastshop':
                    $time_product_job = '40:00';
                    break;
                case 'sicredi':
                case 'decathlon':
                case 'ventureshop':
                case 'lojabelgo':
                case 'ramarim':
                case 'comfortflex':
                case 'mateusmais':
                case 'pitstop':
                    $time_product_job = '50:00';
                    break;
            }
        }

        // adiciona 1 minuto para ser executado assim que for ativo
        $start          = date('Y-m-d H:i:s', strtotime("+1 minutes", strtotime(date('Y-m-d H:i') . ':00')));
        $end_date_job   = '2200-12-31 23:59:00';
        $start_date_job = date('Y-m-d', strtotime($start)) . ' 01:'.$minuteStart.':00';

        switch ($job) {
            case "CreateProduct":
                $time = 60;
                if (strtolower($integration) == 'viavarejo_b2b') {
                    $time = 10;
                }
                if (strtolower($integration) == 'bling') {
                    $time = in_array($sellercenter, ['oscarcalcados', 'youplay']) ? $time : 71;
                }
                $start_date_job = date('Y-m-d', strtotime($start)) . " 06:$time_product_job";
                $end_date_job   = "2200-12-31 20:$time_product_job";
                break;
            case "SyncProduct":
                $time = 71;
                if (strtolower($integration) == 'tiny') {
                    $time = 480;
                }
                $start_date_job = date('Y-m-d', strtotime($start)) . " 06:$time_product_job";
                $end_date_job   = "2200-12-31 20:$time_product_job";
                break;
            case "UpdateStock":
                $time = 10;
                if (strtolower($integration) == 'bling') {
                    $time = 60;
                } elseif (strtolower($integration) == 'tiny') {
                    $time = 20;
                } elseif (strtolower($integration) == 'eccosys') {
                    $time = 30;
                } elseif (strtolower($integration) == 'bSeller') {
                    $time = 30;
                } elseif (strtolower($integration) == 'pluggTo') {
                    $time = 60;
                } elseif (strtolower($integration) == 'jn2') {
                    $time = 30;
                }
                break;
            case "UpdateProduct":
                $time = 60;
                if (strtolower($integration) == 'anymarket') {
                    $time = 5;
                } else if (strtolower($integration) == 'viavarejo_b2b') {
                    $time = 10;
                } else if (strtolower($integration) == 'bling') {
                    $time = in_array($sellercenter, ['oscarcalcados', 'youplay']) ? $time : 71;
                } else if (strtolower($integration) == 'tiny') {
                    $time = 480;
                }
                $start_date_job = date('Y-m-d', strtotime($start)) . " 06:$time_product_job";
                $end_date_job   = "2200-12-31 20:$time_product_job";
                break;
            case "UpdateAllProducts":
                $time = 72;
                break;
            case "UpdateStatus":
                $time = 10;
                if (strtolower($integration) == 'viavarejo_b2b') {
                    $time = 5;
                } else if (strtolower($integration) == 'tiny') {
                    $time = 30;
                }
                $start_date_job = date('Y-m-d', strtotime($start)) . ' 06:00:00';
                $end_date_job   = '2200-12-31 20:00:00';
                break;
            case "UpdateAvailability":
            case "UpdatePrice":
                $time = 10;
                if (strtolower($integration) == 'viavarejo_b2b') {
                    $time = 5;
                    $start_date_job = date('Y-m-d', strtotime($start)) . ' 00:00:00';
                }
                if (strtolower($integration) == 'tiny') {
                    $time = 20;
                }
                break;
            case "CreateOrder":
                $time = 10;
                if (strtolower($integration) == 'viavarejo_b2b') {
                    $time = 5;
                    $start_date_job = date('Y-m-d', strtotime($start)) . ' 00:00:00';
                }
                break;
            case "UpdatePriceStock":
                $time = 10;
                if (strtolower($integration) == 'tiny') {
                    $time = 20;
                }
                /*elseif (strtolower($integration) == 'anymarket') {
                    $time = 360;
                }*/
                break;
            case "TrackingNotification":
            case "PartialProductNotification":
                $time = 5;
                $start_date_job = date('Y-m-d', strtotime($start)) . ' 06:00:00';
                $end_date_job   = '2200-12-31 20:00:00';
                break;
            case "AvailabilityNotification":
            case "StockNotification":
                $time = 5;
                break;
            default:
                echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_save_job')));
                die;
        }

        // É módulo v2 sendo ambiente de desenvolvimento. Então as rotinas rodarão a cada 5 minutos
        if (
            ENVIRONMENT === 'development' &&
            likeText('%Integration_v2%', $job_path) &&
            !in_array($time, array(71,72,73))
        ) {
            $time = 5;
        }

        if ($status == 1) {
            $this->model_calendar->add_event(
                array(
                    "title"         => "$integration: $name - Loja: $store",
                    "module_path"   => $job_path,
                    "module_method" => 'run',
                    "params"        => $store,
                    "event_type"    => $time,
                    "start"         => $start_date_job,
                    "end"           => $end_date_job
                )
            );
            $id_calendar = $this->db->insert_id();

            // cria os job_schedule
            if (date('H') == 23) {
                $datetime = new DateTime('tomorrow');
                $x = $datetime->getTimestamp();
            } else {
                $x = time();
            }

            $e = date('Y-m-d',$x) . " 23:59:59";

            $count = array();
            if ($end_date_job > $e) {
                $end_date_job = $e;
            }

            while ($start <= $end_date_job) {
                $count[] = $start;
                $_time = new DateTime($start);
                $_time->add(new DateInterval('PT' . $time . 'M'));
                $start = $_time->format('Y-m-d H:i');
            }

            foreach($count as $inicio) {
                if (in_array($time, array(71,72,73))) {
                    continue;
                }
                $this->model_calendar->add_job(
                    array(
                        "module_path"   => $job_path,
                        "module_method" => 'run',
                        "params"        => $store,
                        "status"        => 0,
                        "finished"      => 0,
                        "error"         => NULL,
                        "error_count"   => 0,
                        "error_msg"     => NULL,
                        "date_start"    => $inicio,
                        "date_end"      => NULL,
                        "server_id"     => $id_calendar
                    )
                );
            }
        } elseif ($status == 0) {
            $calendar = $this->model_calendar->getEventModuleParam($job_path, $store);
            $this->model_calendar->delete_job($calendar['ID']);
            $this->model_calendar->delete_event($calendar['ID']);
        }
    }

    public function new_job_integration()
    {
        $stores = $this->model_stores->getActiveStore();

        $jobs               = json_decode($this->postClean('job', TRUE)) ?? array($this->postClean('job', TRUE));
        $store              = $this->postClean('store', TRUE);
        $integration        = strtolower($this->postClean('integration', TRUE));
        $integration_name   = $integration;
        $create             = true;
        $company            = $this->model_stores->getStoresData($store);
        $company            = $company['company_id'];

        if ($integration == 'viavarejo_b2b') {
            $integration_name = FlagMapper::INTEGRATION_ERP;
        }

        // [OEP-1789] Atualmente, a integração com a mevo, terá o mesmo formato da vtex.
        if ($integration == 'mevo') {
            $integration = 'vtex';
        }

        $integrationStore = $this->model_integration_erps->find(array('name' => $integration_name));
        if (!$integrationStore || $integrationStore['type'] != $this->model_integration_erps->type['backoffice']) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'data' => $this->lang->line('messages_integration_unavailable_to_configuration'))));
        }

        $module_v2 = true;
        // Lista de integrações que não estão no novo módulo.
        if (in_array($integration, array('bseller', 'eccosys', 'jn2'))) {
            $module_v2 = false;
        }

        $warning     = array();
        $existStore  = false;

        foreach ($stores as $_store) {
            // verifica se o código da loja realmente pertence a empresa
            if($_store['id'] == $store)
                $existStore = true;
        }

        if(!$existStore){
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_log_for_store')));
            exit();
        }

        $storeUseCatalog = $this->model_catalogs->getCatalogsStoresDataByStoreId($store);
        if ($storeUseCatalog) {
            $storeUseCatalog = count($storeUseCatalog);
        }

        // Novo módulo não é necessário criar um caso.
        $integrationName = $integration;
        switch ($integration) {
            case 'eccosys':
                $integrationName = 'Eccosys';
                break;
            case 'bseller':
                $integrationName = 'BSeller';
                break;
            case 'jn2':
                $integrationName = 'jn2';
                break;
            case 'anymarket':
                $integrationName = 'AnyMarket';
                break;
            case 'lojaintegrada':
                $integrationName = 'LojaIntegrada';
                break;
            case 'tray':
                $integrationName = 'Tray';
                break;
            case 'hub2b':
                $integrationName = 'Hub2b';
                break;
            case 'ideris':
                $integrationName = 'Ideris';
                break;
            case 'magalu':
                $integrationName = 'Magalu';
                break;
        }

        $intReady = false;
        foreach ($this->model_api_integrations->getNameIntegrationsActive() as $intAvailable) {
            if (
                $intAvailable['integration'] == $integration ||
                ($integration === FlagMapper::INTEGRATION && in_array($intAvailable['integration'], array(
                    FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_CASASBAHIA), 
                    FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_PONTOFRIO),
                    FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_EXTRA)
                )))
            ) {
                $intReady = true;
                break;
            }
        }

        if (!$intReady) {
            echo json_encode(array('success' => false, 'data' => 'Integração mal informado. Foi informado: ' . $integration));
            exit();
        }

        // loja é integrada com esse erp
        $apiIntegration = $this->model_integrations->getApiIntegrationStore($store);
        if (!$apiIntegration){
            echo json_encode(array('success' => false, 'data' => 'Loja ainda não integrada.'));
            exit();
        }

        if (
            $apiIntegration['integration'] != $integration &&
            ($integration === 'viavarejo_b2b' && !in_array($apiIntegration['integration'], array(
                FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_CASASBAHIA),
                FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_PONTOFRIO),
                FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_EXTRA)
            )))
        ) {
            echo json_encode(array('success' => false, 'data' => 'Loja ainda não está integrada com '.$integration));
            exit();
        }

        $namesJobs = array();

        $this->db->trans_begin();

        $usePriceOrStock = false;
        foreach ($jobs as $job) {

            $pathQueueNotification = '';
            if (in_array($integration, [
                FlagMapper::INTEGRATION
            ])) {
                $pathQueueNotification = "Integration_v2/Product/$integration/Queue/";
            }
            if ($module_v2) {
                if (
                    ($job == 'UpdatePrice' || $job == 'UpdateStock') &&
                    $integration != 'viavarejo_b2b'
                ) {
                    if ($usePriceOrStock) {
                        continue;
                    }
                    $usePriceOrStock = true;
                    $job = 'UpdatePriceStock';
                }

                $pathProduct    = "Integration_v2/Product/$integration/";
                $pathOrder      = "Integration_v2/Order/";
            } else {
                $pathProduct    = "Integration/$integrationName/Product/";
                $pathOrder      = "Integration/$integrationName/Order/";
            }

            $useSettingMatchEAN = false;
            $settingMatchEan = $this->model_settings->getSettingDatabyName('match_produto_por_EAN');
            if ($settingMatchEan && $settingMatchEan['status'] == 1) {
                $useSettingMatchEAN = true;
            }

            $name = null;
            switch ($job) {
                case "CreateProduct":
                    $name = "Criação de Produto";
                    $path = $pathProduct;
                    if ($storeUseCatalog && $settingMatchEan === false) {
                        echo json_encode(array('success' => false, 'data' => "Loja que utiliza Catálogos não pode utilizar job ".$name));
                        die;
                    }
                    break;
                case "SyncProduct":
                    $name = "Criação/Atualização de Produto, preço e estoque";
                    $path = $pathProduct;
                    if ($storeUseCatalog && $settingMatchEan === false) {
                        echo json_encode(array('success' => false, 'data' => "Loja que utiliza Catálogos não pode utilizar job ".$name));
                        die;
                    }
                    break;
                case "UpdateProduct":
                    $name = $this->lang->line('application_job_update_product');
                    if (in_array($integration, ['anymarket'])) {
                        $name = $this->lang->line('application_job_create_update_product');
                    }
                    $path = $pathProduct;
                    if ($storeUseCatalog && $settingMatchEan === false) {
                        echo json_encode(array('success' => false, 'data' => "Loja que utiliza Catálogos não pode utilizar job ".$name));
                        die;
                    }
                    break;
                case "UpdateAllProducts":
                    $name = $this->lang->line('application_job_update_all_products');
                    $path = $pathProduct;
                    break;
                case "UpdatePrice": // Bling, Tiny e Eccosys  não usam mais isso e
                case "UpdateAvailability":
                    $name = "Atualização de Preço";
                    $path = $pathProduct;
                    if ($storeUseCatalog && $settingMatchEan === false) {
                        echo json_encode(array('success' => false, 'data' => "Loja que utiliza Catálogos não pode utilizar job ".$name));
                        die;
                    }
                    break;
                case "UpdateStock": // Bling, Tiny e Eccosys não usam mais isso
                    $name = "Atualização de Estoque";
                    $path = $pathProduct;
                    break;
				case "UpdatePriceStock":  // por enquanto só para o Bling, Tiny e Eccosys
                    $name = "Atualização de Preço e Estoque";
                    $path = $pathProduct;
                    break;
                case "CreateOrder":
                    $name = "Criação de Pedido";
                    $path = $pathOrder;
                    break;
                case "UpdateStatus":
                    $name = "Atualização de Status/NF-e";
                    $path = $pathOrder;
                    break;
                case "AvailabilityNotification":
                case "StockNotification":
                case "TrackingNotification":
                case "PartialProductNotification":
                    switch ($job) {
                        case 'AvailabilityNotification':
                            $name = $this->lang->line('application_job_availability_notification');
                            break;
                        case 'StockNotification':
                            $name = $this->lang->line('application_job_stock_notification');
                            break;
                        case 'TrackingNotification':
                            $name = $this->lang->line('application_job_tracking_notification');
                            break;
                        case 'PartialProductNotification':
                            $name = $this->lang->line('application_job_partial_product_notification');
                    }
                    $path = $pathQueueNotification;
                    break;
                default:
                    echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_occurred')));
                    exit();
            }

            $namesJobs[] = $name;

            if ($this->model_integrations->getJobForJobAndStore($job, $store)) {
                $warning[] = "Job <strong>{$name}</strong> {$this->lang->line('messages_exist_for_store')}";
                continue;
            }

            $data = array(
                'name'          => $name,
                'integration'   => $integration,
                'job'           => $job,
                'job_path'      => $path.$job,
                'status'        => 1, // inicia ligado
                'store_id'      => $store,
                'company_id'    => $company
            );

            if (!$this->model_integrations->createJob($data)) {
                $create = false;
            }

            $sellercenter = $this->model_settings->getSettingDatabyName('sellercenter');
            if ($sellercenter && $sellercenter['value'] == 'somaplace' && $job == 'UpdatePriceStock') {
                $this->model_orders_to_integration->cleanOrderQueueByStore($store); // Limpando a fila de pedidos
            }

            // Cria as rotinas em calendar e job_schedule.
            $this->setStatusJobIntegration($store, $data['status'], $data['job'], $data['integration'], $data['name'], $data['job_path']);
        }

        if (!$this->db->trans_status() || !$create){
            $this->db->trans_rollback();
            echo json_encode(array('success' => false, 'data' => "AA aa".$this->lang->line('messages_error_occurred')));
            exit();
        }

        $this->db->trans_commit();

        if (count($warning) == 0) {
            echo json_encode(array('success' => true, 'data' => $this->lang->line('messages_successfully_created'), 'name' => $namesJobs));
            exit();
        } elseif (count($warning) != count($jobs)) {
            echo json_encode(array('warning' => true, 'data' => "Job cadastrado com sucesso! <br><br>" . implode("<br>", $warning), 'name' => $namesJobs));
            exit();
        } elseif (count($warning) == count($jobs)) {
            echo json_encode(array('success' => false, 'data' => implode("<br>", $warning), 'name' => $namesJobs));
            exit();
        }
    }

    public function remove_job_integration()
    {
        if (!in_array('doIntegration', $this->permission)) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_access_error')));
            exit();
        }

        $job_id     = (int)$this->postClean('job_id', TRUE);
        $job        = $this->model_integrations->getJobForId($job_id);
        $stores     = $this->model_stores->getActiveStore();
        $existStore = false;

        if (!$job_id || !$job) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_data_this_log')));
            exit();
        }

        $store = $job['store_id'];

        // Verifica loja
        foreach ($stores as $_store) {
            // verifica se o código da loja realmente pertence a empresa
            if($_store['id'] == $store)
                $existStore = true;
        }
        if (!$existStore){
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_log_for_store')));
            exit();
        }

        $calendar = $this->model_calendar->getEventModuleParam($job['job_path'], $store);
        $this->model_calendar->delete_job($calendar['ID']);
        $this->model_calendar->delete_event($calendar['ID']);
        $this->model_integrations->removeJob($job_id);

        echo json_encode(array('success' => true, 'data' => $this->lang->line('messages_successfully_removed')));
        exit();
    }

    public function clear_job_integration()
    {
        if (!in_array('doIntegration', $this->permission)) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_access_error')));
            exit();
        }

        $job_id     = (int)$this->postClean('job_id', TRUE);
        $job        = $this->model_integrations->getJobForId($job_id);
        $stores     = $this->model_stores->getActiveStore();
        $existStore = false;

        if (!$job_id || !$job) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_data_this_log')));
            exit();
        }

        $store = $job['store_id'];

        // Verifica loja
        foreach ($stores as $_store) {
            // verifica se o código da loja realmente pertence a empresa
            if($_store['id'] == $store)
                $existStore = true;
        }
        if (!$existStore){
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_log_for_store')));
            exit();
        }

        $this->model_integrations->updateJobIntegrationById(array('last_run' => null), $job_id);

        echo json_encode(array('success' => true, 'data' => $this->lang->line('messages_successfully_updated')));
        exit();
    }

    public function test_product_integration()
    {
        if (!in_array('doIntegration', $this->permission)) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_access_error')));
            exit();
        }
        
        $integration= $this->postClean('integration', TRUE);
        $search     = $this->postClean('search', TRUE);
        $store      = (int)$this->postClean('store', TRUE);

        $stores     = $this->model_stores->getActiveStore();
        $existStore = false;
        if (!$store || !$search || !$integration) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_data_this_log')));
            exit();
        }

        // Verifica loja
        foreach ($stores as $_store) {
            // verifica se o código da loja realmente pertence a empresa
            if($_store['id'] == $store)
                $existStore = true;
        }
        if (!$existStore){
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_log_for_store')));
            exit();
        }

        if ($integration != 'tiny' && $integration != 'bling') {
            echo json_encode(array('success' => false, 'data' => 'Integração mal informado. Foi informado: ' . $integration));
            exit();
        }

        $dataIntegrationStore = $this->db->get_where('api_integrations', array('store_id' => $store))->row_array();
        if (!$dataIntegrationStore) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_integration_for_store')));
            exit();
        }

        $credentials = json_decode($dataIntegrationStore['credentials']);

        if ($integration == 'tiny') {
            $token = $credentials->token_tiny;
        } elseif ($integration == 'bling') {
            $token = $credentials->apikey_bling;
            $multiloja = $credentials->loja_bling;
        }

        // consulta o produto para saber se foi informado sku, codigo conecta ou codigo integrador
        $dataIntegration = $this->db->get_where('products', array('store_id' => $store, 'product_id_erp' => $search))->row_array();
        if (!$dataIntegration) {
            $dataIntegration = $this->db->get_where('products', array('store_id' => $store, 'sku' => $search))->row_array();
            if (!$dataIntegration) {
                $dataIntegration = $this->db->get_where('products', array('store_id' => $store, 'id' => $search))->row_array();
                if (!$dataIntegration) {
        //                    echo json_encode(array('success' => false, 'data' => 'Não foi encontrado nenhum produto com essa pesquisa.'));
        //                    exit();
                }
            }
        }

        //        if (!$dataIntegration['product_id_erp']) {
        //            echo json_encode(array('success' => false, 'data' => 'Não foi encontrado o vínculo entre o produto na Conecta Lá e a plataforma integrada.'));
        //            exit();
        //        }

        if ($integration == 'tiny') {
            $idProduct = $dataIntegration['product_id_erp'] ?? $search;
        } elseif ($integration == 'bling') {
            $idProduct = $dataIntegration['sku'] ?? $search;
        }


        if ($integration == 'tiny') {
            $params = array('http' => array(
                'method' => 'POST',
                'content' => "token={$token}&formato=json&id={$idProduct}"
            ));

            $ctx = stream_context_create($params);
            $fp = @fopen('https://api.tiny.com.br/api2/produto.obter.php', 'rb', false, $ctx);
            if (!$fp) {
                echo json_encode(array('success' => false, 'data' => "Problema com https://api.tiny.com.br/api2/produto.obter.php"));
                exit();
            }
            $response = @stream_get_contents($fp);
            if ($response === false) {
                echo json_encode(array('success' => false, 'data' => "Problema obtendo retorno de https://api.tiny.com.br/api2/produto.obter.php"));
                exit();
            }
            $response = json_decode($response);

            $produto = array();
            if ($response->retorno->status != "OK") {
                if (isset($response->retorno->codigo_erro) && $response->retorno->codigo_erro == 6) {
                    echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_api_blocked_try_again')));
                    exit();
                } else {

                    $arrErrors = array();
                    $errors = $response->retorno->erros;
                    if (!is_array($errors)) $errors = (array)$errors;
                    foreach ($errors as $error) {
                        $msgErrorIntegration = $error->erro ?? "Erro desconhecido";
                        array_push($arrErrors, $msgErrorIntegration);
                    }
                    echo json_encode(array('success' => false, 'data' => "Não foi possível obter o produto. <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>"));
                    exit();
                }
            }
            $produto = $response->retorno->produto;
        }
        elseif ($integration == 'bling') {

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, "https://bling.com.br/Api/v2/produto/{$idProduct}/json/?apikey={$token}&loja={$multiloja}&estoque=S&imagem=S");

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
            $response = curl_exec($curl_handle);
            curl_close($curl_handle);

            $response = json_decode($response);

            if (isset($response->retorno->erros)) {
                $arrErrors = array();
                $errors = $response->retorno->erros;
                if (!is_array($errors)) $errors = (array)$errors;
                foreach ($errors as $error) {
                    $msgErrorIntegration = $error->erro->msg ?? "Erro desconhecido";
                    array_push($arrErrors, $msgErrorIntegration);
                }
                echo json_encode(array('success' => false, 'data' => "Não foi possível obter o produto. <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>"));
                exit();
            }

            $produto = $response->retorno->produtos[0]->produto ?? $response;
        }

        echo json_encode(array('success' => true, 'data' => $produto));
    }

    public function search_logs_integration()
    {
        $search = $this->postClean('search', TRUE);
        $store  = (int)$this->postClean('store', TRUE);

        $stores     = $this->model_stores->getActiveStore();
        $existStore = false;
        if (!$store || !$search) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_data_this_log')));
            exit();
        }

        // Verifica loja
        foreach ($stores as $_store) {
            // verifica se o código da loja realmente pertence a empresa
            if($_store['id'] == $store)
                $existStore = true;
        }
        if (!$existStore){
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_integration_for_store')));
            exit();
        }

        $dataLog = $this->model_integrations->getLogsForStoreAndUniqueId($store, $search);
        if (!count($dataLog)) {
            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_no_found_result_unique_id')));
            exit();
        }

        $strLog = '';
        $dateLastUpdated = $LastJob  = '';
        foreach ($dataLog as $log) {
            if($dateLastUpdated == $log['date_updated'] && (str_contains($log['job'], "Api") || $log['job'] == $LastJob )) {continue;}
            $type = $log['type'] == 'W' ? '<span class="label label-warning">'.$this->lang->line('application_alert').'</span>' : ($log['type'] == 'E' ? '<span class="label label-danger">'.$this->lang->line('application_error').'</span>' : '<span class="label label-success">'.$this->lang->line('application_success').'</span>');
            $strLog .= "<div class='row'><div class='col-md-12 form-group'><p class='d-flex justify-content-between flex-wrap'><span>{$type}</span><span><h3 class='text-right'>".date('d/m/Y H:i:s', strtotime($log['date_updated']))."</h3></span></p><h3 class='text-center'>{$log['title']}</h3> {$log['description']} <br><br> <b>Increment Id:</b> {$log['id']} </div></div><hr style='border: 0.1px dashed #bbb'>";
            $dateLastUpdated = $log['date_updated'];
            $LastJob = $log['job'];
        }

        echo json_encode(array('success' => true, 'data' => $strLog));
    }
    
    public function getJobsByStore()
    {
        $store = $this->postClean('store', TRUE);

        $integrationStore = $this->model_api_integrations->getDataByStore($store);
        $storeUseCatalog = $this->model_catalogs->getCatalogsStoresDataByStoreId($store);
        if ($storeUseCatalog) {
            $storeUseCatalog = count($storeUseCatalog);
        }

        if ($integrationStore === false || !count($integrationStore)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'order'     => array(),
                    'product'   => array(),
                    'queueNotifications' => array()
                )));
        }

        $integration_name = $integrationStore[0]['integration'];
        $integration = strtolower($integration_name);

        if (in_array(
            $integration,
            array(
                FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_CASASBAHIA),
                FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_EXTRA),
                FlagMapper::getIntegrationNameFromFlag(FlagMapper::FLAG_PONTOFRIO)
            )
        )) {
            $integration = FlagMapper::INTEGRATION;
            $integration_name = FlagMapper::INTEGRATION_ERP;
        }

        $integrationStore = $this->model_integration_erps->find(array('name' => $integration_name));
        if (!$integrationStore || $integrationStore['type'] != $this->model_integration_erps->type['backoffice']) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'order'     => array(),
                    'product'   => array(),
                    'queueNotifications' => array()
                )));
        }

        $product = array(
            'CreateProduct' => $this->lang->line('application_job_create_product'),
            'UpdateProduct' => $this->lang->line('application_job_update_product'),
            'UpdatePrice'   => $this->lang->line('application_job_update_price'),
            'UpdateStock'   => $this->lang->line('application_job_update_stock')
        );
        $order  = array(
            'CreateOrder'   => $this->lang->line('application_job_create_order'),
            'UpdateStatus'  => $this->lang->line('application_job_update_status')
        );

        $queueNotifications = [];

        $useCatalogIntegrationERP = false;
        $settingUseCatalogERP = $this->model_settings->getSettingDatabyName('use_catalog_integration_erp');
        if ($settingUseCatalogERP && $settingUseCatalogERP['status'] == 1) {
            $useCatalogIntegrationERP = true;
        }

        switch ($integration) {
            case 'anymarket':
                $product = [
                    'UpdateProduct' => $this->lang->line('application_job_create_update_product'),
                    'UpdatePriceStock'  => $this->lang->line('application_job_update_price_stock'),
                    'UpdateAllProducts' => $this->lang->line('application_job_update_all_products'),
                ];
                break;
            case 'bling':
            case 'bling_v3':
            case 'tiny':
            case 'eccosys':
            case 'lojaintegrada':
            case 'tray':
            case 'hub2b':
            case 'ideris':
            case 'microvix':
            case 'vtex':
            case 'mevo':
                $product = [
                    'CreateProduct' => $this->lang->line('application_job_create_product'),
                    'UpdateProduct' => $this->lang->line('application_job_update_product'),
                    'UpdatePriceStock' => $this->lang->line('application_job_update_price_stock')
                ];
                break;
            case 'magalu':
                $product = [
                    'SyncProduct' => $this->lang->line('application_job_sync_product'),
                ];
                break;
            case FlagMapper::INTEGRATION:
                $product = [
                    'CreateProduct' => $this->lang->line('application_job_create_product'),
                    'UpdateProduct' => $this->lang->line('application_job_update_product'),
                    'UpdateAvailability' => $this->lang->line('application_job_update_price'),
                    'UpdateStock' => $this->lang->line('application_job_update_stock')
                ];
                $queueNotifications = [
                    'AvailabilityNotification' => $this->lang->line('application_job_availability_notification'),
                    'StockNotification' => $this->lang->line('application_job_stock_notification'),
                    'TrackingNotification' => $this->lang->line('application_job_tracking_notification'),
                    'PartialProductNotification' => $this->lang->line('application_job_partial_product_notification'),
                ];
                break;
            default:
                break;
        }

        $useSettingMatchEAN = false;
        $settingMatchEan = $this->model_settings->getSettingDatabyName('match_produto_por_EAN');
        if ($settingMatchEan && $settingMatchEan['status'] == 1) {
            $useSettingMatchEAN = true;
        }

        if ($storeUseCatalog && $useSettingMatchEAN === false) {
            unset($product['CreateProduct']);
            unset($product['UpdateProduct']);
            unset($product['UpdatePrice']);
        }

        if (!$useCatalogIntegrationERP) {
            $product = array();
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'order' => $order,
                'product' => $product,
                'queueNotifications' => $queueNotifications,
                'integration' => $integration
            )));
    }

    public function manageIntegration()
    {
        if(!in_array('viewManageIntegrationErp', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('integration_erps/index', $this->data);
    }

    public function fetchmanageIntegration(): CI_Output
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $fields_order = array('description', 'type', 'active', '');

            $query = array();
            $query['select'][] = "id,description,type,active";
            $query['from'][] = 'integration_erps';

            $data = fetchDataTable(
                $query,
                array('id', 'DESC'),
                null,
                null,
                ['viewManageIntegrationErp'],
                $filters,
                $fields_order,
                $filter_default
            );
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(
                    json_encode(array(
                        "draw"              => $draw,
                        "recordsTotal"      => 0,
                        "recordsFiltered"   => 0,
                        "data"              => $result,
                        "message"           => $exception->getMessage()
                    ))
                );
        }

        foreach ($data['data'] as $value) {
            $btn = "<a class='btn btn-default btn-sm' href='" . base_url("integrations/updateIntegration/$value[id]") . "'><i class='fa fa-pencil'></i></a>";

            $result[] = array(
                $value['description'],
                $this->lang->line("application_type_integration_erp_$value[type]"),
                $value['active'] == 1 ? "<span class='label label-success'>{$this->lang->line('application_yes')}</span>" : "<span class='label label-warning'>{$this->lang->line('application_no')}</span>",
                $btn
            );

        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function updateIntegration(int $id)
    {
        // Não tem permissão para ver a integração.
        if(!in_array('viewManageIntegrationErp', $this->permission) && !in_array('updateManageIntegrationErp', $this->permission)) {
            redirect('integrations/manageIntegration', 'refresh');
        }

        $integration = $this->model_integration_erps->getById($id);

        $integrationFields = [];
        $formFields = json_decode($integration->configuration_form ?? '{}');
        foreach ($formFields ?? [] as $field => $definitions) {
            $label = $this->lang->line("application_{$field}");
            $integrationFields[$field] = (object)[
                'name' => $definitions->name,
                'type' => $definitions->type ?? 'text',
                'label' => !empty($label) ? $label : ($definitions->label ?? $definitions->name ?? ''),
                ($definitions->values ?? null) ? 'values' : null => $definitions->values ?? null
            ];
        }

        $integration->configuration_form = $integrationFields;
        $integration->configuration = json_decode($integration->configuration ?? '{}');
        $this->data['integration'] = $integration;
        $this->data['canUpdate'] = in_array('updateManageIntegrationErp', $this->permission) ? 1 : 0;

        // Não encontrou a integração.
        if (!$integration) {
            redirect('integrations/manageIntegration', 'refresh');
        }

        $this->form_validation->set_rules('description', $this->lang->line('application_name'), "trim|required|edit_unique[integration_erps.description.$id]");
        $this->form_validation->set_rules('active', $this->lang->line('application_visible_in_the_system'), 'trim|required');

        if ($this->form_validation->run()) {
            if (!$this->data['canUpdate']) {
                $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
                redirect("integrations/updateIntegration/$id", 'refresh');
            }

            try {
                $this->saveDataIntegrationErp($id);
            } catch (Exception $exception) {
                $this->session->set_flashdata('error', "{$this->lang->line('messages_error_occurred')}<br/>{$exception->getMessage()}");
                redirect("integrations/updateIntegration/$id", 'refresh');
            }

            $this->log_data(__CLASS__,__FUNCTION__, "data_old=" . json_encode($integration) . "\ndata_new=" . json_encode($this->model_integration_erps->getById($id)));

            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            redirect("integrations/updateIntegration/$id", 'refresh');
        }

        $this->data['providers'] = $this->model_providers->getDataProviderActive('id,name');
        $this->data['logsitc'] = $this->model_integration_logistic->getByExternalIntegrationId($id);
        $this->render_template('integration_erps/update', $this->data);
    }

    public function createIntegration()
    {
        // Não tem permissão para criar a integração.
        if(!in_array('createManageIntegrationErp', $this->permission)) {
            redirect('integrations/manageIntegration', 'refresh');
        }

        $this->form_validation->set_rules('description', $this->lang->line('application_name'), 'trim|required|is_unique[integration_erps.description]');
        //$this->form_validation->set_rules('type', $this->lang->line('application_type'), 'trim|numeric|required');
        $this->form_validation->set_rules('active', $this->lang->line('application_visible_in_the_system'), 'trim|required');
        if (empty($_FILES['image']['name'])) {
            $this->form_validation->set_rules('image', $this->lang->line('application_integration_erp_banner'), 'trim|required');
        }

        if ($this->form_validation->run()) {
            try {
                $this->saveDataIntegrationErp();
            } catch (Exception $exception) {
                $this->session->set_flashdata('error', "{$this->lang->line('messages_error_occurred')}<br/>{$exception->getMessage()}");
                redirect("integrations/createIntegration", 'refresh');
            }

            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            redirect("integrations/manageIntegration", 'refresh');
        }

        $this->data['providers'] = $this->model_providers->getDataProviderActive('id, name');
        $this->render_template('integration_erps/create', $this->data);
    }

    /**
     * Salvar dados da integração.
     *
     * @param   int|null    $id Código da integração.
     * @throws  Exception
     */
    public function saveDataIntegrationErp(int $id = null)
    {
        try {
            $type_integration   = $this->postClean('type');

            if (is_null($type_integration) && !empty($id)) {
                $integration_erp = $this->model_integration_erps->getById($id);
                if ($integration_erp) {
                    $type_integration = $integration_erp->type;
                }
            }

            $is_logistic        = $type_integration == $this->model_integration_erps->type['external_logistic'];
            $arrayLinkSupport   = array();

            if ($this->postClean('title')) {
                for ($countImg = 0; $countImg < count($this->postClean('title')); $countImg++) {
                    $arrayLinkSupport[] = array('title' => $this->postClean('title')[$countImg], 'link' => $this->postClean('link')[$countImg]);
                }
            }

            if (is_null($id) && !in_array($type_integration, array($this->model_integration_erps->type['external'], $this->model_integration_erps->type['external_logistic']))) {
                $this->session->set_flashdata('error', $this->lang->line('messages_type_bad_informed_integration_erp'));
                redirect($id ? "integrations/updateIntegration/$id" : "integrations/createIntegration", 'refresh');
            }

            $arrayUpdate = array(
                'description' => $this->postClean('description'),
                'active' => $this->postClean('active'),
                'support_link' => json_encode($arrayLinkSupport),
                'provider_id'   => $is_logistic && !empty($this->postClean('provider')) ? $this->postClean('provider') : null,
                'configuration' => json_encode($this->postClean('configurations') ?? (object)[]),
                'label_required' => $this->postClean('label_required') ?: 0
            );

            $arrayUpdate['name'] = $this->postClean('name') ?? \libraries\Helpers\StringHandler::slugify($arrayUpdate['description'], '_');
            if (is_null($id)) {
                $arrayUpdate['type'] = $type_integration;
                $arrayUpdate['name'] = $arrayUpdate['name'] ?? StringHandler::slugify($arrayUpdate['description'], '_');
                $arrayUpdate['hash'] = IntegrationERP::generateKeyApp($arrayUpdate['name'], IntegrationERP::GLOBAL_ENCRYPT_KEY);
                $arrayUpdate['user_created'] = $this->session->userdata('id');
                $idCreate = $this->model_integration_erps->create($arrayUpdate);
            } else {
                $arrayUpdate['user_updated'] = $this->session->userdata('id');
                $this->model_integration_erps->updateById($arrayUpdate, $id);
            }

            if (!empty($_FILES['image']['name'])) {
                $nameFile = $this->uploadImageIntegrationErp();
                $this->model_integration_erps->updateById(array('image' => $nameFile), is_null($id) ? $idCreate : $id);
            }
        } catch (Exception $exception) {
            throw new Exception($exception);
        }
    }

    /**
     * Upload do banner da integração.
     *
     * @return  string
     * @throws  Exception
     */
    public function uploadImageIntegrationErp(): string
    {
        // assets/images/product_image
        $config['upload_path'] = 'assets/images/integration_erps';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['max_size'] = '1500';

        if (!is_dir($config['upload_path'])) {
            $oldmask = umask(0);
            @mkdir($config['upload_path'], 0775);
            umask($oldmask);
        }

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('image')) {
            throw new Exception($this->upload->display_errors());
        }

        $extension = pathinfo($_FILES['image']['name'])['extension'];

        return "{$config['file_name']}.$extension";
    }

    public function getIntegrationsByStore(int $store_id): CI_Output
    {
        if (empty($store_id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }
        $integrations = $this->model_integrations->getIntegrationsbyStoreId($store_id);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($integrations));
    }


     /**
     * Salvar dados da url callback da integração.
     *
     */
    public function saveUrlCallbackintegration()
    {


        $integration = $this->postClean();


        if(empty($integration['url-webhook'])){
            echo json_encode(array('success' => 1, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }

        // Verificar se foi enviado pelo menos um tipo de pedido do front
        if (!isset($integration['eventos-webhook']) || empty($integration['eventos-webhook'])) {
            echo json_encode(array('success' => 2, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }

        // Verificar se os arrays têm o mesmo número de elementos
        if (count($integration['url-webhook']) !== count($integration['eventos-webhook'])) {
            echo json_encode(array('success' => 3, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }
       // Arrays equalizados finalizados
        $equalizedData = [];
        foreach ($integration['url-webhook'] as $index => $urlArray) {
                    // Verifica se existe um array de eventos para o índice atual
            $eventosArray = $integration['eventos-webhook'][$index] ?? [];

            // Transforma o array de eventos em uma string separada por ponto e vírgula
            $eventosStr = implode(';', $eventosArray);

            $equalizedData[] = [
                'store_id' => $integration['storeId'],
                'id_supplier' => null,
                'url' => $urlArray[0],
                'type_webhook' => $eventosStr,
                'is_supplier' => 0
            ];
        }

        $operationModel =  $this->model_integrations_webhook->updateOrInsert($equalizedData);

        if($operationModel){
            echo json_encode(array('success' => 4, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }
    }


    public function getModalDataWebhook()
    {
        $storeId = $this->input->get('storeId');

        if(!$storeId){
            echo json_encode(array('success' => 1, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }
        $is_supplier = false;
        $dataFrom = $this->model_integrations_webhook->getData($storeId,$is_supplier);

        echo json_encode($dataFrom);
    }



    public function deleteGroupFormDataWebhook()
    {

        $integration = $this->input->post();

        $storeId = $integration['storeId'];
        $urlCallback = $integration['nameUrl'];
        $is_supplier = isset($integration['is_supplier']) && $integration['is_supplier'] === 'true';


        $dataFrom = $this->model_integrations_webhook->getDataUnique($storeId,$is_supplier,$urlCallback);

        if($dataFrom){
            $operationModel =  $this->model_integrations_webhook->deleteUrlCallback($storeId,$is_supplier,$urlCallback);
        }

        if($operationModel){
            echo json_encode(array('success' => 1, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }
    }

    public function deleteGroupFormDataWebhookProvider()
    {

        $integration = $this->input->post();


        $storeId = $integration['storeId'];
        $urlCallback = $integration['nameUrl'];
        $idSupplier = $integration['id_supplier'];
        $is_supplier = isset($integration['is_supplier']) && $integration['is_supplier'] === 'true';


        $dataFrom = $this->model_integrations_webhook->getDataUniqueProvider($idSupplier,$is_supplier,$urlCallback);

        if($dataFrom){
            $operationModel =  $this->model_integrations_webhook->deleteUrlCallback($idSupplier,$is_supplier,$urlCallback);
        }

        if($operationModel){
            echo json_encode(array('success' => 1, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }
    }


      /**
     * Salvar dados da url callback da integração.
     *
     */
    public function saveUrlCallbackintegrationSupplier()
    {

        $integration = $this->postClean();


        if(empty($integration['url-webhook'])){
            echo json_encode(array('success' => 1, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }

        // Verificar se foi enviado pelo menos um tipo de pedido do front
        if (!isset($integration['eventos-webhook']) || empty($integration['eventos-webhook'])) {
            echo json_encode(array('success' => 2, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }

        // Verificar se os arrays têm o mesmo número de elementos
        if (count($integration['url-webhook']) !== count($integration['eventos-webhook'])) {
            echo json_encode(array('success' => 3, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }


       // Arrays equalizados finalizados
        $equalizedData = [];
        foreach ($integration['url-webhook'] as $index => $urlArray) {
                    // Verifica se existe um array de eventos para o índice atual
            $eventosArray = $integration['eventos-webhook'][$index] ?? [];

            // Transforma o array de eventos em uma string separada por ponto e vírgula
            $eventosStr = implode(';', $eventosArray);


            $storeIds = explode(',', $integration['storeId']);

            foreach ($storeIds as $storeId) {
                $equalizedData[] = [
                    'store_id' => $storeId,
                    'id_supplier' => $integration['id_supplier'],
                    'url' => $urlArray[0],
                    'type_webhook' => $eventosStr,
                    'is_supplier' => 1
                ];
            }
        }

        $operationModel =  $this->model_integrations_webhook->updateOrInsert($equalizedData);

        if($operationModel){
            echo json_encode(array('success' => 4, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }
    }

    public function getModalDataSupplierWebhook()
    {
        $providerId = $this->input->get('providerId');

        if(!$providerId){
            echo json_encode(array('success' => 1, 'data' => $this->lang->line('api_legalpanel_post_error_saving')));
            exit();
        }
        $is_supplier = true;
        $dataFrom = $this->model_integrations_webhook->getData($providerId, $is_supplier);

        echo json_encode($dataFrom);
    }


    public function saveIntegrationOauth()
    {
        $store_id = $this->postClean('store');

        $integration = $this->model_api_integrations->getIntegrationByStore($store_id);
        $has_revoke = false;
        if ($integration) {
            $credentials = json_decode($integration['credentials']);
            if ($credentials && !empty($credentials->revoke)) {
                $has_revoke = true;
            }
        }

        if (!$integration || $has_revoke) {
            $data_store = $this->model_stores->getStoresData($store_id);
            $key = $this->config->config['encryption_key'];

            $payload = array(
                "cod_store" => $data_store['id'],
                "cod_company" => $data_store['company_id'],
                'loja_bling' => $this->postClean('store_code'),
                'stock_bling' => $this->postClean('stock_bling'),
                'exp' => time() + 60 * 60 * 6 // 24h
            );

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('token' => $this->jwt->encode($payload, $key))));
        }

        $credentials = json_decode($integration['credentials'], true);
        $credentials['loja_bling'] = $this->postClean('store_code');
        $credentials['stock_bling'] = $this->postClean('stock_bling');
        $credentials['stock_id_bling'] = null; // limpar o id do estoque.
        $credentials['price_not_update'] = $this->postClean('price_not_update');
        if (isset($credentials['price_not_update']) && !$credentials['price_not_update']){
            unset($credentials['price_not_update']);
        }
        
        $this->model_api_integrations->update($integration['id'], array(
            'credentials' => json_encode($credentials, JSON_UNESCAPED_UNICODE)
        ));

        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_integration_api'));
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('success' => true, 'redirect' => base_url('integrations/job_integration'))));
    }

    public function homologation(string $integration_name)
    {
        // Não tem permissão para ver a integração.
        if(!in_array('viewManageIntegrationErp', $this->permission) && !in_array('updateManageIntegrationErp', $this->permission)) {
            redirect('integrations/manageIntegration', 'refresh');
        }

        $integration = $this->model_integration_erps->getByName($integration_name);

        $integration['configuration'] = json_decode($integration['configuration'] ?? '{}');
        $this->data['integration'] = $integration;

        // Não encontrou a integração.
        if (!$integration) {
            $this->session->set_flashdata('error', $this->lang->line('application_register_not_found'));
            redirect('integrations/manageIntegration', 'refresh');
        }

        if (!property_exists($integration['configuration'], 'client_id')) {
            $this->session->set_flashdata('error', $this->lang->line('messages_client_id_client_secret_not_found'));
            redirect("Integrations/updateIntegration/$integration[id]", 'refresh');
        }


        $client_id = $integration['configuration']->client_id;

        $key = $this->config->config['encryption_key'];

        $payload = array(
            'redirect_uri' => base_url("Integrations/startHomologation/$integration[name]"),
            'exp' => time() + 60 * 60 * 6 // 24h
        );

        $state = $this->jwt->encode($payload, $key);
        $this->data['url_validation_token'] = "https://www.bling.com.br/Api/v3/oauth/authorize?response_type=code&client_id=$client_id&state=$state";

        $this->render_template('integration_erps/homologation', $this->data);
    }

    public function startHomologation(string $integration_name)
    {
        // Não tem permissão para ver a integração.
        if(!in_array('viewManageIntegrationErp', $this->permission) && !in_array('updateManageIntegrationErp', $this->permission)) {
            redirect('integrations/manageIntegration', 'refresh');
        }

        $integration = $this->model_integration_erps->getByName($integration_name);

        $configuration = json_decode($integration['configuration'] ?? '{}');

        // Não encontrou a integração.
        if (!$integration || !$configuration) {
            $this->session->set_flashdata('error', "configuração da integração não encontrada");
            redirect('integrations/manageIntegration', 'refresh');
            return;
        }

        $data = $this->input->get();

        $key = $this->config->config['encryption_key']; // Key para decodificação
        $decodeJWT = $this->jwt->decode($data['state'], $key, array('HS256'));

        // Verifica se ocorreu algum problema para decodificar a key
        if (is_string($decodeJWT)) {
            redirect("Integrations/homologation/$integration_name");
            return;
        }

        if (empty($decodeJWT->redirect_uri)) {
            $this->session->set_flashdata('error', "redirect_uri não encontrado");
            redirect("Integrations/homologation/$integration_name");
            return;
        }

        try {
            $log = [];
            // Gera os dados de acesso.
            $authorization = base64_encode("$configuration->client_id:$configuration->client_secret");

            $token_options = array(
                'form_params' => array(
                    'grant_type'    => 'authorization_code',
                    'code'          => $data['code']
                ),
                'headers' => array(
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Accept'        => '1.0',
                    'Authorization' => "Basic $authorization"
                )
            );

            try {
                $client = new Client([
                    'verify' => false,
                    'allow_redirects' => true
                ]);
                $request  = $client->request('POST', 'https://www.bling.com.br/Api/v3/oauth/token', $token_options);
                $response = json_decode($request->getBody()->getContents(), true);
                $access_token = $response['token_type'] . ' ' . $response['access_token'];
                $refresh_token = $response['refresh_token'];
                $log[] = ['POST', 'https://www.bling.com.br/Api/v3/oauth/token', date(DATETIME_INTERNATIONAL), $request->getStatusCode(), json_encode(array_merge($token_options, ['headers' => ['Authorization' => '*******']]), JSON_UNESCAPED_UNICODE), json_encode($response, JSON_UNESCAPED_UNICODE)];

                $product_id = null;
                $body = null;
                $x_bling_homologacao = null;
                $arrTests = array(
                    'GET'    => 'https://bling.com.br/Api/v3/homologacao/produtos',
                    'POST'   => 'https://bling.com.br/Api/v3/homologacao/produtos',
                    'PUT'    => 'https://bling.com.br/Api/v3/homologacao/produtos/[PRODUCT_ID]',
                    'PATCH'  => 'https://bling.com.br/Api/v3/homologacao/produtos/[PRODUCT_ID]/situacoes',
                    'DELETE' => 'https://bling.com.br/Api/v3/homologacao/produtos/[PRODUCT_ID]',
                );

                foreach ($arrTests as $method => $uri) {
                    while (true) {
                        if (!is_null($product_id)) {
                            $uri = str_replace('[PRODUCT_ID]', $product_id, $uri);
                        }

                        $test_options = array(
                            'headers' => array(
                                'Content-Type' => 'application/json',
                                'Accept' => '1.0',
                                'Authorization' => $access_token,
                                'x-bling-homologacao' => $x_bling_homologacao
                            )
                        );

                        switch ($method) {
                            case 'POST':
                                $test_options['json'] = $body['data'];
                                break;
                            case 'PUT':
                                $body['data']['nome'] = 'Copo';
                                $test_options['json'] = $body['data'];
                                break;
                            case 'PATCH':
                                $test_options['json'] = array('situacao' => "I");
                                break;
                        }

                        try {
                            $request = $client->request($method, $uri, $test_options);
                            $x_bling_homologacao = $request->getHeaderLine('x-bling-homologacao');
                            $response = json_decode($request->getBody()->getContents(), true);
                            $log[] = [$method, $uri, date(DATETIME_INTERNATIONAL), $request->getStatusCode(), json_encode(array_merge($test_options, ['headers' => ['Authorization' => '*******']]), JSON_UNESCAPED_UNICODE), json_encode($response, JSON_UNESCAPED_UNICODE)];
                            break;
                        } catch (GuzzleException | BadResponseException $exception) {
                            $log[] = [$method, $uri, date(DATETIME_INTERNATIONAL), $exception->getCode(), json_encode(array_merge($test_options, ['headers' => ['Authorization' => '*******']]), JSON_UNESCAPED_UNICODE), $exception->getMessage()];
                            if ($exception->getCode() == 401) {
                                try {
                                    $token_options['form_params']['grant_type'] = 'refresh_token';
                                    $token_options['form_params']['refresh_token'] = $refresh_token;

                                    unset($token_options['form_params']['code']);

                                    $request_token = $client->request('POST', 'https://www.bling.com.br/Api/v3/oauth/token', $token_options);
                                    $response_token = json_decode($request_token->getBody()->getContents(), true);
                                    $access_token = $response_token['token_type'] . ' ' . $response_token['access_token'];
                                    $refresh_token = $response_token['refresh_token'];
                                    $x_bling_homologacao = $request->getHeaderLine('x-bling-homologacao');
                                    $log[] = ['POST', 'https://www.bling.com.br/Api/v3/oauth/token', date(DATETIME_INTERNATIONAL), $request_token->getStatusCode(), json_encode(array_merge($token_options, ['headers' => ['Authorization' => '*******']]), JSON_UNESCAPED_UNICODE), json_encode($response_token, JSON_UNESCAPED_UNICODE)];
                                } catch (GuzzleException | BadResponseException $exception) {
                                    $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
                                    $error = json_decode($message, true);
                                    throw new Exception($error['error']['description'] ?? json_encode($error, JSON_UNESCAPED_UNICODE));
                                }
                            } else {
                                $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
                                $error = json_decode($message, true);
                                throw new Exception($error['error']['description'] ?? json_encode($error, JSON_UNESCAPED_UNICODE));
                            }
                        }
                    }

                    switch ($method) {
                        case 'GET':
                            $body = $response;
                            break;
                        case 'POST':
                            $product_id = $response['data']['id'];
                            break;
                    }
                }

                $this->data['integration'] = $integration;
                $this->data['success_homologation'] = $log;
                $this->session->set_flashdata('success', "Homologação bem sucedida, volte na conta Bling e clique em <b>Solicitar revisão</b>.");
                $this->render_template('integration_erps/homologation', $this->data);
            } catch (GuzzleException | BadResponseException $exception) {
                $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
                $error = json_decode($message, true);
                throw new Exception($error['error']['description'] ?? json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (Exception $exception) {
            $this->session->set_flashdata('error', $exception->getMessage() . ' ' . $this->lang->line('messages_reload_page_try_again'));
            redirect("Integrations/homologation/$integration_name");
            return;
        }
    }

}