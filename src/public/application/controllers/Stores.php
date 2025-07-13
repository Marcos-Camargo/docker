<?php
/*
SW Serviços de Informática 2019

Controller de Lojas/Depósitos

*/

use App\Libraries\Enum\StatusFinancialManagementSystemEnum;
use App\Libraries\Enum\StoreSubaccountStatusFilterEnum;
use phpDocumentor\Reflection\Types\Boolean;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;
use App\Libraries\FeatureFlag\FeatureManager;

require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php';
require_once APPPATH . "libraries/Marketplaces/Utilities/Store.php";
require_once APPPATH . "libraries/Logistic/vendor/autoload.php";

use Microservices\v1\Logistic\ShippingIntegrator;
use Microservices\v1\Logistic\ShippingCarrier;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Form_validation $form_validation
 * @property CI_Lang $lang
 *
 * @property Model_stores $model_stores
 * @property Model_company $model_company
 * @property Model_category $model_category
 * @property Model_users $model_users
 * @property Model_integrations $model_integrations
 * @property Model_calendar $model_calendar
 * @property Model_catalogs $model_catalogs
 * @property Model_settings $model_settings
 * @property Model_banks $model_banks
 * @property Model_products $model_products
 * @property Model_plans $model_plans
 * @property Model_shopify_new_stores $model_shopify_new_stores
 * @property Model_gateway $model_gateway
 * @property Model_payment_gateway_store_logs $model_payment_gateway_store_logs
 * @property Model_iugu $model_iugu
 * @property Model_api_integrations $model_api_integrations
 * @property Model_integration_erps $model_integration_erps
 * @property Model_job_schedule $model_job_schedule
 * @property Model_orders $model_orders
 * @property Model_attributes $Model_attributes
 * @property Model_mosaico_aggregate_merchant $model_mosaico_aggregate_merchant
 * @property Model_mosaico_sales_channel $model_mosaico_sales_channel
 * @property Model_stores_multi_channel_fulfillment $model_stores_multi_channel_fulfillment
 * @property Model_cycles $model_cycles
 * @property Model_shopify_new_stores $Model_shopify_new_stores
 * 
 * @property ShippingIntegrator $ms_shipping_integrator
 * @property ShippingCarrier $ms_shipping_carrier
 * @property \Logistic\Repositories\IntegrationLogistics $integrationLogistics
 * @property \Logistic\Repositories\IntegrationLogisticConfigurations $integrationLogisticConfigurations
 * @property \Logistic\Services\IntegrationLogisticService $integrationLogisticService
 * @property Marketplaces\Utilities\Store $marketplace_store
 *
 * @property CI_Loader $load
 */

class Stores extends Admin_Controller
{
     /**
     * @var DeleteProduct
     */
    public $deleteProduct;
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_stores');

        $this->load->model('model_stores');
        $this->load->model('model_company');
        $this->load->model('model_category');
        $this->load->model('model_users');
        $this->load->model('model_integrations');
        $this->load->model('model_calendar');
        $this->load->model('model_catalogs');
        $this->load->model('model_settings');
        $this->load->model('model_banks');
        $this->load->model('model_products');
        $this->load->model('model_plans');
        $this->load->model('Model_shopify_new_stores');
        $this->load->model('model_gateway');
        $this->load->model('model_payment_gateway_store_logs');
        $this->load->model('model_iugu');
        $this->load->model('model_orders');
        $this->load->model('model_api_integrations');
        $this->load->model('model_financial_management_system_stores');
        $this->load->model('model_financial_management_system_store_histories');
        $this->load->model('Model_attributes');
        $this->load->model('model_integration_erps');
        $this->load->model('model_job_schedule');
        $this->load->model('model_cycles');
        $this->load->model('model_stores_multi_channel_fulfillment');
        $this->load->model('model_mosaico_aggregate_merchant');
        $this->load->model('model_mosaico_sales_channel');

        $this->load->library('JWT');
        $this->load->library('DeleteProduct', [
            'productModel' => $this->model_products,
            'ordersModel' => $this->model_orders,
            'lang' => $this->lang
        ], 'deleteProduct');
        $this->load->library("Marketplaces\\Utilities\\Store", [], 'marketplace_store');

        $this->ms_shipping_integrator = new ShippingIntegrator();
        $this->ms_shipping_carrier = new ShippingCarrier();

        $useMsShipping = $this->ms_shipping_carrier->use_ms_shipping && $this->ms_shipping_integrator->use_ms_shipping;
        $this->integrationLogistics = $useMsShipping ?
            new \Logistic\Repositories\MS\v1\IntegrationLogistics() : new \Logistic\Repositories\CI\v1\IntegrationLogistics();
        $this->integrationLogisticConfigurations = $useMsShipping ?
            new \Logistic\Repositories\MS\v1\IntegrationLogisticConfigurations() : new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations();
        $this->integrationLogisticService = new \Logistic\Services\IntegrationLogisticService($this->integrationLogistics, $this->integrationLogisticConfigurations);
    }

    /*
    * It only redirects to the manage stores page
    */

    public function index()
    {
        if (!in_array('viewStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['sellers_filter'] = $this->model_users->getUsersByCompanyId(1);
        $this->data['sellers_filter'] = array_map(function ($user) {
            $user['desc'] = "{$user['firstname']} {$user['lastname']} ({$user['email']})";
            return $user;
        }, $this->data['sellers_filter']);

        $this->render_template('stores/index', $this->data);
    }

    /*
    * It retrieve the specific store information via a store id
    * and returns the data in json format.
    */
    public function fetchStoresDataById($id)
    {
        if ($id) {
            $data = $this->model_stores->getStoresData($id);
            echo json_encode($data);
        }
    }

    public function fetchCompanyDataById($id)
    {
        log_message('info', 'fetch START');
        if ($id) {
            log_message('info', 'fetch ID:' . $id);

            $data = $this->model_company->getCompanyData($id);
            echo json_encode($data);
        }
    }

    /*
	* It retrieves all the store data from the database 
	* This function is called from the datatable ajax function
	* The data is return based on the json format.
	*/
    public function fetchStoresData()
    {
        ob_start();
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $procura = " AND ( s.name like '%" . $busca['value'] . "%' OR c.name like '%" . $busca['value'] . "%'  OR s.id like '%" . $busca['value'] . "%') ";
            }
        } else {
            if (trim($postdata['nome'])) {
                $procura .= " AND s.name like '%" . $postdata['nome'] . "%'";
            }
            if (trim($postdata['empresa'])) {
                $procura .= " AND c.name like '%" . $postdata['empresa'] . "%'";
            }
            if (trim($postdata['status'])) {
                if ($postdata['status'] == '7') {
                    $procura .= " AND s.is_vacation = 1";
                } else {
                    $procura .= " AND s.is_vacation = 0 AND s.active = " . $postdata['status'];
                }
            }
            if (trim($postdata['razaosocial'])) {
                $procura .= " AND s.raz_social like '%" . $postdata['razaosocial'] . "%'";
            }
        }

        if (isset($postdata['cnpj']) && !empty($postdata['cnpj'])) {
            $postdata['cnpj'] = preg_replace('/[^0-9]/', '', $postdata['cnpj']);
            $procura .= " AND REPLACE(REPLACE(REPLACE(s.cnpj, '.', ''), '-', ''), '/', '') = '{$postdata['cnpj']}'";
        }

        if (isset($postdata['vendedores']) && is_array($postdata['vendedores'])) {
            $idsVendedores = implode(', ', $postdata['vendedores']);
            $procura .= " AND s.seller IN ({$idsVendedores})";
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('s.id', 's.name', 'c.name', 's.date_create', 'date_product', 'date_order', 'CAST(s.service_charge_value AS DECIMAL(12,2))', 's.active', '');

            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $end = '';
        $filtrocnpj = '';
        if ($postdata['filtrocnpj']) {
            switch ($postdata['filtrocnpj']) {
                case 1:
                    $end = ' AND invoice_cnpj = ';
                    $filtrocnpj = '1';
                    break;
                case 2:
                    $end = ' AND invoice_cnpj = ';
                    $filtrocnpj = '0';
                    break;
                default:
                    $end = '';
                    $filtrocnpj = '';
                    break;
            }
        }

        if ($postdata['filtrostatussubconta']){
            if (StoreSubaccountStatusFilterEnum::WITHOUT_PENDENCIES == $postdata['filtrostatussubconta']){
                $procura.= $filtrocnpj.= " AND s.id IN (SELECT store_id FROM gateway_subaccounts WHERE gateway_subaccounts.store_id = s.id AND gateway_subaccounts.with_pendencies = 0 ) ";
            }
            if (StoreSubaccountStatusFilterEnum::WITH_PENDENCIES == $postdata['filtrostatussubconta']){
                $procura.= $filtrocnpj.= " AND s.id IN (SELECT store_id FROM gateway_subaccounts WHERE gateway_subaccounts.store_id = s.id AND gateway_subaccounts.with_pendencies = 1 ) ";
            }
            if (StoreSubaccountStatusFilterEnum::WITH_ERROR == $postdata['filtrostatussubconta']){
                $procura.= $filtrocnpj.= " AND s.id NOT IN (SELECT store_id FROM gateway_subaccounts WHERE gateway_subaccounts.store_id = s.id )
                                            AND s.id IN (SELECT store_id FROM payment_gateway_store_logs WHERE payment_gateway_store_logs.store_id = s.id AND payment_gateway_store_logs.status = 'error') ";
            }
            if (StoreSubaccountStatusFilterEnum::PENDING == $postdata['filtrostatussubconta']){
                $procura.= $filtrocnpj.= " AND s.id NOT IN (SELECT store_id FROM gateway_subaccounts WHERE gateway_subaccounts.store_id = s.id )
                                            AND s.id NOT IN (SELECT store_id FROM payment_gateway_store_logs WHERE payment_gateway_store_logs.store_id = s.id) ";
            }
        }

		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND s.id = " . $this->data['userstore']);
        $procura .= $more;
        $data = $this->model_stores->getStoresDataView($end, $filtrocnpj, $ini, $procura, $sOrder, $length);
        $filtered = $this->model_stores->getStoresDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_stores->getStoresDataCount();
        }

        $paymentGatewayId = $this->model_settings->getSettingDatabyName('payment_gateway_id')['value'];

        $result = array();

        foreach ($data as $key => $value) {
            $buttons = '';
            if (in_array('updateStore', $this->permission)) {
                $buttons .= ' <a href="' . base_url('stores/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }
            // if (in_array('deleteStore', $this->permission)) {
            //     $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc(' . $value['id'] . ',\'' . $value['name'] . '\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            // }
            if (isset($value['is_vacation']) && $value['is_vacation'] == 1) {
                $store_status = '<span class="label label-info">' . $this->lang->line('application_vacation_status') . '</span>';

            } else {
                // Define o status com base no campo 'active'
                switch ($value['active']) {
                    case 1:
                        $store_status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
                        break;
                    case 2:
                        $store_status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
                        break;
                    case 3:
                        $store_status = '<span class="label label-warning">' . $this->lang->line('application_in_negociation') . '</span>';
                        break;
                    case 4:
                        $store_status = '<span class="label label-warning">' . $this->lang->line('application_billet') . '</span>';
                        break;
                    case 5:
                        $store_status = '<span class="label label-danger">' . $this->lang->line('application_churn') . '</span>';
                        break;
                    case 6:
                        $store_status = '<span class="label label-danger">' . $this->lang->line('application_incomplete') . '</span>';
                        break;
                    case 7:
                        $store_status = '<span class="label label-info">' . $this->lang->line('application_vacation_status') . '</span>';
                    default:
                        $store_status = null;
                        break;
                }
            }

            /**
             * Subaccount Status
             */
            $subaccountStatus = $paymentGatewayId ? $this->getSubaccountStatus($paymentGatewayId, $value['id']) : null;
            $alertColorSubstatus = 'default';
            $labelSubaccountStatus = 'pending';
            if (!is_null($subaccountStatus)) {
                $labelSubaccountStatus = StoreSubaccountStatusFilterEnum::getName($subaccountStatus);
                $alertColorSubstatus = $this->getAlertColorSubaccountStatus($subaccountStatus);
            }
            

            $labelSubaccountStatus = '<span class="label label-'.$alertColorSubstatus.'">'.$labelSubaccountStatus."</span>";

            $expiration = null;
            if (!is_null($value['date_product'])) {
                $deadline = new DateTime($value['date_product']);
                $deadline->add(new DateInterval('P6M'));
                $expiration = $deadline->format('d/m/Y');
            }
            $result[$key] = array(
                '<a href="' . base_url('stores/update/' . $value['id']) . '">' . $value['id'] . '</a>',
                $value['name'],
                $value['company'],
                date('d/m/Y', strtotime($value['date_create'])),
                (is_null($value['date_product'])) ? null : date('d/m/Y', strtotime($value['date_product'])),
                (is_null($value['date_order'])) ? null : date('d/m/Y', strtotime($value['date_order'])),
                empty($value['service_charge_value']) ? '0%' : $value['service_charge_value'] . '%',
                $store_status,
                $labelSubaccountStatus,
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

    public function inactive($id)
    {
        if (!in_array('updateStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $store = $this->model_stores->getStoresData($id);
        $this->data['count_users'] = count($this->model_users->getUsersByStore($id));
        $this->data['count_products'] = $this->model_products->getProductCount(' AND store_id='.$id);
        $qtd_stores_in_company = count($this->model_stores->getCompanyStores($store['company_id']));
        if ($qtd_stores_in_company == 1) {
            $this->data['count_users'] = count($this->model_users->getUsersByCompanyId($store['company_id']));
            $this->data['adtional_mensage'] = $this->lang->line('messages_company_also_disable');
        }
        $company = $this->model_company->getMyCompanyData($store['company_id']);
        $this->data['additional_message'] = $qtd_stores_in_company == 1 ? sprintf($this->lang->line('application_additional_company_inativation'), $company['name']) : '';
        $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
        $this->data['store_id'] = $id;
        $this->render_template('stores/confirmInactive', $this->data);
    }

    public function active($id)
    {
        if (!in_array('updateStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['count_users'] = count($this->model_users->getUsersByStore($id));
        $store = $this->model_stores->getStoresData($id);
        $qtd_stores_in_company = count($this->model_stores->getCompanyStores($store['company_id']));
        if ($qtd_stores_in_company == 1) {
            $this->data['count_users'] = count($this->model_users->getUsersByCompanyId($store['company_id']));
            $this->data['adtional_mensage'] = $this->lang->line('messages_company_also_disable');
        }
        $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
        $this->data['store_id'] = $id;
        $this->render_template('stores/confirmActive', $this->data);
    }

    public function activeConfirmed($id)
    {
        if (!in_array('updateStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->log_data(__CLASS__, __FUNCTION__, 'activate_store_with_id ' . json_encode($id).' for user with id:'.$this->session->userdata('id'), 'I');
        $this->model_stores->active($id);
        redirect('stores', 'refresh');
    }
    public function inactiveConfirmed($id)
    {
        if (!in_array('updateStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $store = $this->model_stores->getStoresData($id);
        $this->log_data(__CLASS__, __FUNCTION__, 'inactivate_store_with_id ' . json_encode($id).' for user with id:'.$this->session->userdata('id'), 'I');
        $this->model_stores->inactive($id);
        redirect('stores', 'refresh');
    }

    public function vacationOn($id)
    {
        if (!in_array('enableVacation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $store = $this->model_stores->getStoresData($id);
        $this->data['count_products'] = $this->model_products->getProductCount(' AND store_id='.$id);
        $this->data['adtional_mensage'] = $this->lang->line('messages_products_also_disable');
        $this->data['page_title'] = $this->lang->line('application_manage_vacation_stores');
        $this->data['store_id'] = $id;

        $this->render_template('stores/confirmVacationOn', $this->data);
    }
    public function vacationOff($id)
    {
        if (!in_array('enableVacation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $store = $this->model_stores->getStoresData($id);
        $this->data['count_products'] = $this->model_products->getProductCount(' AND store_id='.$id);
        $this->data['adtional_mensage'] = $this->lang->line('messages_products_also_able');
        $this->data['page_title'] = $this->lang->line('application_manage_vacation_stores');
        $this->data['store_id'] = $id;
        
        $this->render_template('stores/confirmVacationOff', $this->data);
    }
    
    public function vacationOffConfirmed($id)
    {
        if (!in_array('enableVacation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->vacationLog('OFF Vacation',$id);
        $this->model_stores->vacationOff($id);
        $this->createVacationJob($id);

        redirect('stores', 'refresh');
    }
    public function vacationOnConfirmed($id)
    {
        if (!in_array('enableVacation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->vacationLog('ON Vacation',$id);
        $this->model_stores->vacationOn($id);
        $this->createVacationJob($id);

        redirect('stores', 'refresh');
    }
    private function createVacationJob($store_id)
    {
        if ($this->db->where('module_path', 'Vacation')
                     ->where('params', $store_id)
                     ->get('calendar_events')
                     ->num_rows() === 0 ){
            
            $dateNow = dateNow(TIMEZONE_DEFAULT)->format(DATE_INTERNATIONAL);

            $this->db->insert('calendar_events', array(
                'title' => "Gerencia produtos pertencentes a lojas em Férias",
                'event_type' => '5',  
                'start' => '2024-01-09 02:00:00',
                'end' => $dateNow.' 23:59:59',
                'module_path' => 'Vacation',
                'module_method' => 'run', 
                'params' => (string) $store_id));

                
        }

        $job = $this->db->where('module_path', 'Vacation')
                        ->where('params', $store_id) 
                        ->get('calendar_events')
                        ->row();
        
        if ($job) {
            $this->model_job_schedule->create([
                'module_path' => 'Vacation',
                'module_method' => 'run',
                'params' => (string) $store_id,
                'status' => 0,
                'finished' => 0,
                'date_start' => date('Y-m-d H:i:s', strtotime("+2 minutes")),
                'date_end' => null,
                'server_id' => $job->id
            ]);
        }
        
    }
    public function vacationLog($action,$id)
    {
        $module = 'Stores';
        $value = json_encode($this->model_stores->getStoresData($id));
        $this->log_data($module, $action, $value);
        
    }

    public function buscavacation(){
        
        $inputs = $this->postClean(NULL,TRUE);
        $storeId = $inputs['storeId'];
        //$limit = isset($inputs['limit']) ? intval($inputs['limit']) : 6;
        return $this->model_stores->getVacationLogs($storeId);

    }

    public function customFields($id)
    {
        if (!in_array('updateStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $store = $this->model_stores->getStoresData($id);

        $fieldsAdd = $this->model_stores->getFieldsAddFromStoreId($id);
        $fieldsMandatory = $this->model_stores->getFieldsMandatoryFromStoreId($id);

        $map = [
            "TID"                         => 'tid',
            "NSU"                         => 'nsu',
            "Código de Autorização Cartão" => 'authorization_id',
            "6 primeiros díg. do Cartão" => 'first_digits',
            "4 últimos díg. do Cartão"   => 'last_digits'
        ];

        $camposAdicionais = [];
        $camposObrigatorios = [];

        foreach ($map as $label => $field) {
            $camposAdicionais[$label] = isset($fieldsAdd[$field]) && $fieldsAdd[$field] == 1;
            $camposObrigatorios[$label] = isset($fieldsMandatory[$field]) && $fieldsMandatory[$field] == 1;
        }
        return [
            'camposAdicionais' => $camposAdicionais,
            'camposObrigatorios' => $camposObrigatorios
        ];
        
    }
    
    public function fetchStoresDataOld()
    {
        $result = array('data' => array());

        $data = $this->model_stores->getStoresData();
        $dia = $this->diminuir_dias_uteis(date("Y-m-d"), 2) . " 00:00:00";
        foreach ($data as $key => $value) {

            // button
            $buttons = '';

            if (in_array('updateStore', $this->permission)) {
                $buttons .= ' <a href="' . base_url('stores/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }

            if (in_array('deleteStore', $this->permission)) {
                $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc(' . $value['id'] . ',\'' . $value['name'] . '\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }
            
            $company = $this->model_company->getCompanyData($value['company_id']);

            switch ($value['active']) {
                case 1:
                    $store_status = '<span class="label label-success">Ativo</span>';
                    break;
                case 2:
                    $store_status = '<span class="label label-danger">Inativo</span>';
                    break;
                case 3:
                    $store_status = '<span class="label label-warning">Em Negociação</span>';
                    break;
                case 4:
                    $store_status = '<span class="label label-warning">Boleto</span>';
                    break;
                case 5:
                    $store_status = '<span class="label label-danger">Churn</span>';
                    break;
                default:
                    $store_status = null;
                    break;
            }

            $first_product = $this->model_stores->getDataForTheProgressBar($value['id']);

            if ($first_product['date_product']) {
                $date_first_product = date('d/m/Y', strtotime($first_product['date_product']));
                $deadline = new DateTime($first_product['date_product']);
                $deadline->add(new DateInterval('P6M'));
                $expiration = $deadline->format('d/m/Y');
            } else {
                $date_first_product = null;
                $expiration = null;
            }

            $onboarding = $value['onboarding'] ? date('d/m/Y', strtotime($value['onboarding'])) : null;

            $result['data'][$key] = array(
                $value['id'],
                $value['name'],
                $company['name'],
                date('d/m/Y', strtotime($value['date_create'])),
                $date_first_product,
                $expiration,
                $onboarding,
                $store_status,
                $buttons
            );
        }

        echo json_encode($result);
    }

    /*
    * If the validation is not valid, then it provides the validation error on the json format
    * If the validation for each input is valid then it inserts the data into the database and 
    returns the appropriate message in the json format.
    */

    public function multiple_select($array)
    {

        $this->form_validation->set_message('multiple_select', 'Selecione ao menos 1 %s');
        if (empty($array)) {
            return false;
        } else {
            return true;
        }
    }

    public function create(int $company_id = NULL)
    {
        if (!in_array('createStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $required_clifor = $this->model_settings->getStatusbyName('required_clifor');
        $bank_is_optional = $this->model_settings->getStatusbyName('store_optional_bank_details');
        $usar_mascara_banco = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
        $stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;

        $create_seller_vtex = $this->model_settings->getStatusbyName('create_seller_vtex');
        $create_seller_mosaico = $this->model_settings->getStatusbyName('create_seller_mosaico')==1;;
        $store_cpf_optional = $this->model_settings->getStatusbyName('store_cpf_optional');

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->model_banks->getBanks();
        
        $this->data['CSs'] = $this->model_users->getUsersByCompanyId(1);
        $this->data['sellercenter_name']=$this->model_settings->getSettingDatabyName('sellercenter_name')["value"];

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration") && $create_seller_mosaico) {
            $this->form_validation->set_rules('aggregate_select', "Aggregate Merchant", 'trim|required');
            $this->form_validation->set_rules('website_url', 'Website Url', 'trim|required');
            $this->form_validation->set_rules('responsible_sac_name', $this->lang->line('application_responsible_sac_name'), 'trim|required');
            $this->form_validation->set_rules('responsible_sac_email', $this->lang->line('application_responsible_sac_email'), 'trim|required');
            $this->form_validation->set_rules('responsible_sac_tell', $this->lang->line('application_responsible_sac_tell'), 'trim|required');
        }

        $this->form_validation->set_rules('edit_active', $this->lang->line('application_status'), 'trim|required');
        $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('address', $this->lang->line('application_address'), 'trim|required');
        $this->form_validation->set_rules('addr_num', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('addr_compl', $this->lang->line('application_complement'), 'trim');
        $this->form_validation->set_rules('addr_neigh', $this->lang->line('application_neighb'), 'trim|required');
        $this->form_validation->set_rules('addr_city', $this->lang->line('application_city'), 'trim|required');
        $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        $this->form_validation->set_rules('country', $this->lang->line('application_country'), 'trim|required');
        $this->form_validation->set_rules('zipcode', $this->lang->line('application_zip_code'), 'trim|required');
        $this->form_validation->set_rules('type_view_tag', $this->lang->line('application_type_tag'), 'trim|required');
        $this->form_validation->set_rules('invoice_cnpj', $this->lang->line('application_cnpj_fatured'), 'trim');
        $this->form_validation->set_rules('utm_source', $this->lang->line('application_name'), 'regex_match[/[a-zA-Z\u00C0-\u00FF ]+/i]');
        $this->form_validation->set_rules('phone_1', $this->lang->line('application_phone'), 'required|min_length[10]');
        $this->form_validation->set_rules('phone_2', $this->lang->line('application_phone'), 'required|min_length[10]');
        $this->form_validation->set_rules('additional_operational_deadline', $this->lang->line('application_store_additional_operational_deadline'), 'trim|numeric');

        if ($this->postClean('same',TRUE) != "1") {
            $this->form_validation->set_rules('business_street', $this->lang->line('application_address'), 'trim|required');
            $this->form_validation->set_rules('business_addr_num', $this->lang->line('application_number'), 'trim|required');
            $this->form_validation->set_rules('business_addr_compl', $this->lang->line('application_complement'), 'trim');
            $this->form_validation->set_rules('business_neighborhood', $this->lang->line('application_neighb'), 'trim|required');
            $this->form_validation->set_rules('business_town', $this->lang->line('application_city'), 'trim|required');
            $this->form_validation->set_rules('business_uf', $this->lang->line('application_uf'), 'trim|required');
            $this->form_validation->set_rules('business_nation', $this->lang->line('application_country'), 'trim|required');
            $this->form_validation->set_rules('business_code', $this->lang->line('application_zip_code'), 'trim|required');
        }

        $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
            // Permite a inclusão de CNPJ duplicado.
            $cnpjValidation = 'trim|required|callback_checkCNPJ';
            $allowDuplicatedCnpj = $this->model_settings->getStatusbyName("allow_duplicate_cnpj") == 1;
            if ( !$allowDuplicatedCnpj) {
                $cnpjValidation .= "|callback_checkUniqueCNPJ";
            }
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
            $count_stores = 0;
            $data_company = null;
            $stores_multicd_company = null;
            if ($company_id) {
                $data_company = $this->model_company->getCompanyData($company_id);
                if ($stores_multi_cd && $data_company && $data_company['multi_channel_fulfillment']) {
                    $stores_multicd_company = $this->model_stores->getStoresMultiCdByCompany($company_id);
                    $count_stores = count($stores_multicd_company);
                }
            }

            if ($this->postClean('pj_pf') == "PF") {
                $company_id = $this->postClean('company_id');
                $data_company = $this->model_company->getCompanyData($company_id);

                $this->form_validation->set_rules('CPF', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCPF|callback_checkUniqueCNPJ');
                $this->form_validation->set_rules('RG', $this->lang->line('application_rg'), 'trim');
            } else if ($this->postClean('pj_pf') == "PJ") {
                $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');
                $this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|callback_checkUniqueCNPJ');

                $company_id = $this->postClean('company_id');
                $data_company = $this->model_company->getCompanyData($company_id);

                if ($this->postClean('exempted') != "1" && $data_company["addr_uf"] != $this->postClean("business_uf")) {
                    $this->form_validation->set_rules('inscricao_estadual', $this->lang->line('application_iest_erro'), 'trim|required|callback_checkInscricaoEstadual[' . $this->postClean("business_uf") . ']');
                }
            }
        } else {
            $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');
            
            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), $cnpjValidation);
            } else {
                $this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|callback_checkUniqueCNPJ');
            }
        }

        $birth_date_requered = $this->model_settings->getValueIfAtiveByName('birth_date_requered_to_responsable_store')==="true";
        if($birth_date_requered){
            $this->form_validation->set_rules('responsable_birth_date', $this->lang->line('application_responsible_birth_date'), 'trim|required');
        }
        $this->form_validation->set_rules('responsible_name', $this->lang->line('application_responsible_name'), 'trim|required');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_responsible_email'), 'trim|required|valid_email');
        $this->form_validation->set_rules('responsible_cpf', $this->lang->line('application_responsible_cpf'), 'trim|callback_checkCPF');

        if ($bank_is_optional != 1) {
            $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
            $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
            $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
            $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
        }

        $this->form_validation->set_rules('service_charge_value', $this->lang->line('application_charge_amount'), 'trim|required');
        if ($this->postClean('service_charge_freight_option',TRUE) != "1") {
            $this->form_validation->set_rules('service_charge_freight_value', $this->lang->line('application_charge_amount_freight'), 'trim|required');
        }

        if ($this->postClean('freight_seller',TRUE) == 1 && $this->postClean('freight_seller_type', TRUE) == 1) {
            $this->form_validation->set_rules('freight_seller_end_point', $this->lang->line('freight_seller_end_point'), 'trim|required|valid_url');
        }
        if ($this->postClean('freight_seller',TRUE) == 1 && $this->postClean('freight_seller_type', TRUE) == 3) {
            $this->form_validation->set_rules('freight_seller_end_point', $this->lang->line('freight_seller_end_point'), 'trim|required');
            $this->form_validation->set_rules('freight_seller_code', $this->lang->line('application_own_logistic_code'), 'trim|required');
        }

        $this->data['empresas'] = $this->model_company->getCompanyData(null,true);
        $this->data['tipos_volumes'] = $this->model_category->getTiposVolumes();
        $this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs();

        if(empty($this->postClean('erp_customer_supplier_code',TRUE)) && $required_clifor == "1"){
            $this->form_validation->set_rules('erp_customer_supplier_code', $this->lang->line('application_store_erp_customer_supplier_code'), 'trim|required');
        }

        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
            $count_stores = 0;
            $data_company = null;
            $stores_multicd_company = null;
            if ($company_id) {
                $data_company = $this->model_company->getCompanyData($company_id);
                if ($stores_multi_cd && $data_company && $data_company['multi_channel_fulfillment']) {
                    $stores_multicd_company = $this->model_stores->getStoresMultiCdByCompany($company_id);
                    $count_stores = count($stores_multicd_company);
                }
            }
        }

        $this->data['count_stores'] = $count_stores;
        $this->data['data_company'] = $data_company;

        if ($this->form_validation->run() == TRUE) {
            if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
                $company_id = $this->postClean('company_id');
                $data_company = $this->model_company->getCompanyData($company_id);
                if ($this->postClean('exempted',TRUE) != "1" && $data_company["addr_uf"] != $this->postClean("business_uf",TRUE)) {
                    $this->form_validation->set_rules('inscricao_estadual', $this->lang->line('application_iest_erro'), 'trim|required|callback_checkInscricaoEstadual[' . $this->postClean("business_uf",TRUE) . ']');
                }
            }
            if ($stores_multi_cd && $data_company && $data_company['multi_channel_fulfillment']) {
                $stores_multicd_company = $this->model_stores->getStoresMultiCdByCompany($company_id);
                $count_stores = count($stores_multicd_company);
            }

            if ($this->postClean('exempted',TRUE) != "1" && $data_company["addr_uf"] != $this->postClean("business_uf",TRUE)) {
                $this->form_validation->set_rules('inscricao_estadual', $this->lang->line('application_iest_erro'), 'trim|required|callback_checkInscricaoEstadual[' . $this->postClean("business_uf",TRUE) . ']');
            }

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration") && $this->postClean('exempted_mun', TRUE) != "1" && $data_company["addr_city"] != $this->postClean("business_town", TRUE)) {
                $this->form_validation->set_rules('inscricao_municipal', $this->lang->line('application_imun'), 'trim|required');
            }

            if ($this->postClean('freight_seller',TRUE) == "1") {
                $freight_seller_end_point = $this->postClean('freight_seller_end_point',TRUE);
                $freight_seller_type = $this->postClean('freight_seller_type',TRUE);
                $freight_seller = 1;
                $freight_seller_code = null;
                if ($freight_seller_type == 3) {
                    $freight_seller_code = $this->postClean('freight_seller_code',TRUE);
                }
            } else {
                $freight_seller_code = null;
                $freight_seller_end_point = null;
                $freight_seller_type = null;
                $freight_seller = 0;
            }
            if ($this->postClean('service_charge_freight_option',TRUE) == "1") {
                $service_charge_freight_value = $this->postClean('service_charge_value',TRUE);
            } else {
                $service_charge_freight_value = $this->postClean('service_charge_freight_value',TRUE);
            }


            if ($this->postClean('buybox',TRUE) == "1") {
                $buybox = 1;
            }else{
                $buybox = 0;
            }

            if ($this->postClean('ativacaoAutomaticaProdutos',TRUE) == "1") {
                $ativacaoAutomaticaProdutos = 1;
            }else{
                $ativacaoAutomaticaProdutos = 0;
            }

            $bank = (is_null($this->postClean('bank',TRUE))) ? "" : $this->postClean('bank',TRUE);
            $agency = (is_null($this->postClean('agency',TRUE))) ? "" : $this->postClean('agency',TRUE);
            $account_type = (is_null($this->postClean('account_type',TRUE))) ? "" : $this->postClean('account_type',TRUE);
            $account = (is_null($this->postClean('account',TRUE))) ? "" : $this->postClean('account',TRUE);
            foreach ($this->data['banks'] as $local_bank) {
                if ( $usar_mascara_banco == true) {
                    if ($local_bank['name'] == $bank) {
    
                        if(strlen($account) != strlen($local_bank['mask_account'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_account') . $local_bank['mask_account']);
                            redirect('stores/create', 'refresh');
                        }
                        if( strlen($agency) != strlen($local_bank['mask_agency'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_agency') . $local_bank['mask_agency']);
                            redirect('stores/create', 'refresh');
                        }
                        continue;
                    }
                }else{
                    continue;
                }

            }

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $aggregateId = null;
                if ($create_seller_mosaico) {
                    // Busca o aggregate da request.
                    $aggr = $this->postClean('aggregate_select', TRUE);
                    $aggrName = $this->postClean('aggregate_select_name');

                    // Busca no banco, se não existir ou existir com nome diferente, cria novo.
                    $aggregateDb = $this->model_mosaico_aggregate_merchant->getById($aggr);
                    if (!$aggregateDb || $aggregateDb['name'] != $aggrName) {
                        $aggr = $this->model_mosaico_aggregate_merchant->create(['name' => $aggrName]);
                        if (!$aggr) {
                            $this->session->set_flashdata('error', $this->lang->line("application_aggregate_not_created"));
                            return redirect('stores/create', 'refresh');
                        }
                    }
                    $aggregateId = $aggr;
                }

                $logo = "";

                // Verifica se recebeu a imagem da logo corretamente.
                $hasFile = isset($_FILES['store_image'])  && $_FILES['store_image']['size'] > 0;

                // Caso não tenha as imagens e seja Mosaico, não cria, é obrigatório a inserção.
                if (!$hasFile && $create_seller_mosaico) {
                    $this->session->set_flashdata('error', $this->lang->line("application_required_logo"));
                    return redirect('stores/create', 'refresh');
                }

                // Caso tenha o arquivo, realiza o envio da imagem.
                if ($hasFile) {
                    // Upload da imagem gerando um GUID para o nome do arquivo.
                    $resp = $this->upload_image($this->getGUID(false));
                    if ($resp['success']) {
                        $logo = $resp['msg'];
                    } else {
                        $this->session->set_flashdata('error', $resp['msg']);
                        return redirect('stores/create', 'refresh');
                    }
                }
            }

            $data = array(
                'name' => $this->strReplaceName($this->postClean('name',TRUE)),
                'address' => $this->postClean('address',TRUE),
                'addr_num' => $this->postClean('addr_num',TRUE),
                'addr_compl' => $this->postClean('addr_compl',TRUE),
                'addr_neigh' => $this->postClean('addr_neigh',TRUE),
                'addr_city' => $this->postClean('addr_city',TRUE),
                'addr_uf' => $this->postClean('addr_uf',TRUE),
                'zipcode' => preg_replace('/[^\d\+]/', '', $this->postClean('zipcode',TRUE)),
                'phone_1' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_1',TRUE)),
                'phone_2' => preg_replace('/[^\d\+]/', '', $this->postClean('phone_2',TRUE)),
                'country' => $this->postClean('country',TRUE),
                'raz_social' => $this->postClean('pj_pf') == "PF" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? $this->strReplaceName($this->postClean('name')) : $this->strReplaceName($this->postClean('raz_soc')),
                'inscricao_estadual' => $this->postClean('pj_pf') == "PF" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? onlyNumbers($this->postClean('RG') ?: 0) : ($this->postClean('exempted') == "1" ? "0" : $this->postClean('inscricao_estadual')),
                'company_id' => $this->postClean('company_id',TRUE),
                'responsible_cs' => $this->postClean('responsible_cs',TRUE),
                'active' => $this->postClean('edit_active',TRUE),
                'prefix' => strtoupper(substr(md5(uniqid(mt_rand(99999, 99999999), true)), 0, 5)),
                'CNPJ' => $this->numberString($this->postClean('pj_pf') == "PF" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? $this->postClean('CPF') : $this->postClean('CNPJ'), false),
                'invoice_cnpj' => $this->postClean('invoice_cnpj',TRUE) == "1" ? "0" : $this->postClean('invoice_cnpj',TRUE),
                'responsible_name' => $this->postClean('responsible_name',TRUE),
                'responsible_email' => $this->postClean('responsible_email',TRUE),
                'responsible_cpf' => $this->postClean('responsible_cpf',TRUE),
                'responsible_mother_name' => $this->postClean('responsible_mother_name',TRUE),
                'responsible_position' => $this->postClean('responsible_position',TRUE),
                'responsible_monthly_income' => $this->postClean('responsible_monthly_income',TRUE),
                'company_annual_revenue' => $this->postClean('company_annual_revenue',TRUE),
                'bank' => $bank,
                'agency' => $agency,
                'account_type' => $account_type,
                'account' => $account,
                'onboarding' => $this->postClean('onboarding',TRUE) == "" ? null : $this->postClean('onboarding',TRUE),
                'service_charge_value' => $this->postClean('service_charge_value',TRUE),
                'lista_preco_integracao' => $this->postClean('lista_preco_integracao',TRUE),
                'url_callback_integracao' => $this->postClean('url_callback_integracao',TRUE),
                'associate_type' => $this->postClean('associate_type_pj',TRUE),

                'business_street' => $this->postClean('same',TRUE) == "1" ? $this->postClean('address',TRUE) : $this->postClean('business_street',TRUE),
                'business_addr_num' => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_num',TRUE) : $this->postClean('business_addr_num',TRUE),
                'business_addr_compl' => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_compl',TRUE) : $this->postClean('business_addr_compl',TRUE),
                'business_neighborhood' => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_neigh',TRUE) : $this->postClean('business_neighborhood',TRUE),
                'business_town' => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_city',TRUE) : $this->postClean('business_town',TRUE),
                'business_uf' => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_uf',TRUE) : $this->postClean('business_uf',TRUE),
                'business_nation' => $this->postClean('same',TRUE) == "1" ? $this->postClean('country',TRUE) : $this->postClean('business_nation',TRUE),
                'business_code' => $this->postClean('same',TRUE) == "1" ? $this->postClean('zipcode',TRUE) : $this->postClean('business_code',TRUE),

                'user_create' => $this->session->userdata('id'),

                'freight_seller' => $freight_seller,
                'freight_seller_end_point' => $freight_seller_end_point,
                'freight_seller_type' => $freight_seller_type,
                'freight_seller_code' => $freight_seller_code,

                'service_charge_freight_value ' => $service_charge_freight_value,

                'description' => $this->postClean('description', TRUE),
                'exchange_return_policy' => $this->postClean('exchange_return_policy', TRUE),
                'delivery_policy' => $this->postClean('delivery_policy', TRUE),
                'security_privacy_policy' => $this->postClean('security_privacy_policy', TRUE),
                'erp_customer_supplier_code' => $this->postClean('erp_customer_supplier_code', TRUE),

                'seller' => $this->postClean('seller', TRUE),
                'what_integration' => $this->postClean('what_integration', TRUE),
                'how_up_and_fature' => $this->postClean('how_up_and_fature', TRUE),
                'mix_of_product' => $this->postClean('mix_of_product', TRUE),
                'operation_store' => $this->postClean('operation_store', TRUE),
                'billing_expectation' => $this->postClean('billing_expectation', TRUE),
                'type_view_tag' => $this->postClean('type_view_tag', TRUE),
                'flag_bloqueio_repasse' => $this->postClean('flag_bloqueio_repasse', TRUE),
                'allow_payment_reconciliation_installments' => $this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments') ? $this->postClean('allow_payment_reconciliation_installments', TRUE) : '0',
                'utm_source' => $this->postClean('utm_source', TRUE),
                'flag_store_migration' => $this->postClean('flag_store_migration', TRUE),
                'max_time_to_invoice_order' => $stores_multi_cd && $data_company['multi_channel_fulfillment'] && $count_stores == 0 ? $this->postClean('max_time_to_invoice_order') : null,
                'type_store' => $this->postClean('type_store') ?? 0,
                'additional_operational_deadline' => $this->postClean('additional_operational_deadline'),
                'inventory_utilization' => $stores_multi_cd && $data_company['multi_channel_fulfillment'] && $count_stores == 0 ? $this->postClean('inventory_utilization') : null,
                'buybox'                        =>   $buybox,
                'ativacaoAutomaticaProdutos'    =>   $ativacaoAutomaticaProdutos,
            );

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $data['aggregate_id'] = $aggregateId;
                $data['inscricao_municipal'] = $this->postClean('exempted_mun', TRUE) == "1" ? "0" : $this->postClean('inscricao_municipal', TRUE);
                $data['logo'] = $logo;
                $data['website_url'] = $this->postClean('website_url', TRUE);
                $data['responsible_sac_name'] = $this->postClean('responsible_sac_name', TRUE);
                $data['responsible_sac_email'] = $this->postClean('responsible_sac_email', TRUE);
                $data['responsible_sac_tell'] = $this->postClean('responsible_sac_tell', TRUE);
            }  
          
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
                $data['pj_pf'] = strtolower($this->postClean('pj_pf'));
            }

            if(in_array('transferAnticipationRelease', $this->permission)){
                // Trecho que salva o campo Antecipação de Repasse (remover o comentario quando precisar).
                /*$flag_antecipacao_repasse = $this->postClean('flag_antecipacao_repasse', TRUE);
                $this->log_data(
                    'Stores',
                    __FUNCTION__,
                    "Setou o campo 'Antecipação de Repasse' para '".($flag_antecipacao_repasse=="S"?"SIM":"NÃO")."'",
                    "I"
                );
                $data['flag_antecipacao_repasse'] = $flag_antecipacao_repasse;*/                
            }

            if ($this->model_settings->getValueIfAtiveByName('allow_automatic_antecipation')) {
                if ($this->postClean('use_automatic_antecipation', TRUE)) {
                    $data['use_automatic_antecipation'] = $this->postClean('use_automatic_antecipation', TRUE);
                    $data['antecipation_type'] = $this->postClean('antecipation_type', TRUE);
                    $data['percentage_amount_to_be_antecipated'] = $this->postClean('percentage_amount_to_be_antecipated', TRUE);
                    $data['number_days_advance'] = $this->postClean('number_days_advance', TRUE);
                    $data['automatic_anticipation_days'] = $this->postClean('automatic_anticipation_days', TRUE);
                } else {
                    $data['use_automatic_antecipation'] = false;
                }
            } else {
                $data['use_automatic_antecipation'] = false;
            }

            if ($this->postClean('id_indicator',TRUE) != "0" || $this->postClean('percentage_indication',TRUE) != "") {
                if ($this->postClean('id_indicator',TRUE) == "0" || $this->postClean('percentage_indication',TRUE) == "") {
                    $this->session->set_flashdata('error', 'É preciso completar o cadastro do usuário indicador');
                    return redirect('stores/create', 'refresh');
                }

                $indicator = explode('-', $this->postClean('id_indicator',TRUE));
                $data['id_indicator'] = $indicator[1];
                $data['type_indicator'] = $indicator[0];
                $data['percentage_indication'] = $this->postClean('percentage_indication',TRUE);
                $data['date_indication'] = date('Y-m-d H:i:s');
                $data['user_create_indication'] = $this->session->userdata('id');
            }
            if (in_array('createUserFreteRapido', $this->permission)) {
                $data['fr_cadastro'] = 6; // Era 2 mas agora só cadastra no frete rápido quando tiver o primeiro produto de transportadora.
            }

            //Update na tabela de lojas shopify, lojas solicitadas via email.
            $data_for_shopify_store = array( 
                "company_id" => $data['company_id'],
                "creation_status" => 1
            );
            
            $this->Model_shopify_new_stores->update_creation_status($data_for_shopify_store);
            $data['responsable_birth_date'] = $this->postClean('responsable_birth_date',TRUE);

            // Empresa usa o Multi CD e não é a principal.
            if ($stores_multi_cd && $data['type_store'] != 0 && $data_company['multi_channel_fulfillment'] && $count_stores !== 0) {
                if ($this->postClean('zipcode_start') && $this->postClean('zipcode_end')) {
                    $store_id_principal = $stores_multicd_company[0]['id'];

                    for ($count = 0; $count < count($this->postClean('zipcode_start')); $count++) {
                        $zipcode_start = onlyNumbers($this->postClean('zipcode_start')[$count]);
                        $zipcode_end = onlyNumbers($this->postClean('zipcode_end')[$count]);

                        if (!$this->model_stores_multi_channel_fulfillment->checkAvailabilityRangeZipcode(0, $store_id_principal, $zipcode_start, $zipcode_end)) {
                            $this->session->set_flashdata('error', "O range de CEP entre $zipcode_start e $zipcode_end já está em uso em outro CD.");
                            redirect("stores/create/$company_id", 'refresh');
                        }
                    }
                } else {
                    $this->session->set_flashdata('error', "Informe a área de atendimento do CD.");
                    redirect("stores/create/$company_id", 'refresh');
                }
            }

            $create = $this->model_stores->create($data);

            if ($create) {
                $this->model_job_schedule->create([
                    'module_path' => "Automation/CreateApplicationAttributes",
                    'module_method' => 'run',
                    'params' => "{$create}",
                    'status' => 0,
                    'finished' => 0,
                    'date_start' => date('Y-m-d H:i:s', strtotime("+2 minutes")),
                    'date_end' => null,
                    'server_id' => 0
                ]);

                // Se é criação de seller mosaico, realiza a 
                if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration") && $create_seller_mosaico) {
                    $selectedSc = $this->postClean("msc_sales_channel",TRUE);
                    $this->model_mosaico_sales_channel->deletePivotAllByStoreId($create);

                    foreach ($selectedSc as $sc) {
                        $pivotEntry = ["store_id" => $create, "sc_id" => $sc];
                        $this->model_mosaico_sales_channel->createStorePivot($pivotEntry);
                    }
                }

                if ($this->model_settings->getValueIfAtiveByName('sgp_integrated_default')) {
                    // se existe name = sgpweb e active = 1 e user_sellercenter = 1 em integrations_logistic.
                    // se store_id = 0 e id_integration = {{id_integration}},= e credentials != {}.
                    try {
                        if ($this->integrationLogisticConfigurations->getLogisticAvailableBySellerCenter('sgpweb')) {
                            $dataIntegration = $this->integrationLogistics->getIntegrationsByName('sgpweb');
                            $this->integrationLogisticService->saveIntegrationLogisticConfigure([
                                'id_integration' => $dataIntegration['id'] ?? null,
                                'integration' => $dataIntegration['name'],
                                'use_seller' => $this->integrationLogistics->isSeller($dataIntegration),
                                'use_sellercenter' => $this->integrationLogistics->isSellerCenter($dataIntegration),
                                'credentials' => null,
                                'store_id' => $create,
                                'user_created' => $this->session->userdata('id'),
                                'active' => true
                            ]);
                        }
                    } catch (Throwable $e) {

                    }
                }

                $tipos_volumes = $this->postClean('tipos_volumes[]',TRUE);
                if (!is_null($tipos_volumes)) {
                    foreach ($tipos_volumes as $tipo_volume) {
                        $datatipo = array(
                            "store_id" => $create,
                            "tipos_volumes_id" => $tipo_volume,
                            "status" => 1
                        );
                        $this->model_stores->createStoresTiposVolumes($datatipo);
                    }
                }

                if ($stores_multi_cd && $count_stores !== 0 && $this->postClean('zipcode_start') && $this->postClean('zipcode_end')) {
                    $zipcodes_multi_cd  = array();
                    $store_id_principal = $stores_multicd_company[0]['id'];

                    for ($count = 0; $count < count($this->postClean('zipcode_start')); $count++) {
                        $zipcode_start = onlyNumbers($this->postClean('zipcode_start')[$count]);
                        $zipcode_end   = onlyNumbers($this->postClean('zipcode_end')[$count]);

                        $zipcodes_multi_cd[] = array(
                            'store_id_principal' => $store_id_principal,
                            'store_id_cd'        => $create,
                            'company_id'         => $this->postClean('company_id'),
                            'zipcode_start'      => $zipcode_start,
                            'zipcode_end'        => $zipcode_end
                        );
                    }

                    $this->model_stores_multi_channel_fulfillment->create_batch($zipcodes_multi_cd);
                }

                $token = $this->createTokenAPI($create, (int)$this->postClean('company_id',TRUE));
                $this->model_stores->update(array("token_api" => $token), $create);

                $this->model_catalogs->setCatalogsStoresDataByStoreId($create, $this->postClean('catalogs',TRUE));
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created') . " Agora crie o usuário que irá gerenciar esta empresa se não existe ainda");
                
                $this->session->set_flashdata('store_id', $create);
			    $this->session->set_flashdata('company_id', $this->postClean('company_id',TRUE));
				
                redirect('/users/create', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('stores/create', 'refresh');
            }
        } else {
            $this->data['birth_date_requered'] = $birth_date_requered !== false;
            $this->session->set_flashdata('company_id', $company_id);

            $this->data['required_clifor'] = $this->model_settings->getStatusbyName('required_clifor');
			$this->data['franchise_on_store'] = $this->model_settings->getStatusbyName('franchise_on_store');
			$this->data['big_sellers_on_store'] = $this->model_settings->getStatusbyName('big_sellers_on_store');
            $this->data['users_indicator'] = $this->model_users->getUsersIndicator();
            $this->data['stores_indicator'] = $this->model_stores->getStoresIndicator();
            $this->data['companies_indicator'] = $this->model_company->getCompaniesIndicator();
            $this->data['bank_is_optional'] = $bank_is_optional;
            $this->data['create_seller_vtex'] = $create_seller_vtex;
            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $this->data['create_seller_mosaico'] = $create_seller_mosaico;
                if ($create_seller_mosaico) {
                    $this->data['available_sc'] = $this->model_mosaico_sales_channel->getAll();
                }
            }
            $this->data['store_cpf_optional'] = $store_cpf_optional;
            $this->data['allow_automatic_antecipation'] = $this->model_settings->getValueIfAtiveByName('allow_automatic_antecipation');
            $this->data['antecipacao_dx_default'] = $this->model_settings->getValueIfAtiveByName('antecipacao_dx_default');
            $this->data['porcentagem_antecipacao_default'] = $this->model_settings->getValueIfAtiveByName('porcentagem_antecipacao_default');
            $this->data['numero_dias_dx_default'] = $this->model_settings->getValueIfAtiveByName('numero_dias_dx_default');
            $this->data['automatic_anticipation_days_default'] = $this->model_settings->getValueIfAtiveByName('automatic_anticipation_days_default');
            $this->data['allow_payment_reconciliation_installments'] = $this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments');
            $this->data['use_buybox'] = $this->model_settings->getValueIfAtiveByName('buy_box');
            $this->data['use_ativacaoAutomaticaProdutos'] = $this->model_settings->getValueIfAtiveByName('ativacao_automatica_produtos');
            $this->data['default_payment_reconciliation_installments_enabled'] = $this->model_settings->getValueIfAtiveByName('default_payment_reconciliation_installments_enabled');
            $this->data['get_attribute_value_utm_param'] = $this->Model_attributes->getAttributeValueUtmParam();
            $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
            $this->data['stores_multi_cd'] = $stores_multi_cd;
            $this->render_template('stores/create', $this->data);
        }
    }

    public function start_migration($id)
    {
        $this->model_stores->startMigration($id);
        redirect('stores/update/' . $id, 'refresh');
    }
    /*
    * If the validation is not valid, then it provides the validation error on the json format
    * If the validation for each input is valid then it updates the data into the database and
    returns a n appropriate message in the json format.
    */
    public function update($id)
    {
        if (!in_array('updateStore', $this->permission) && !in_array('viewStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        // Verifica se o fornecedor existe
        if ($this->model_stores->getStoresData($id) === null) {
            redirect('stores/', 'refresh');
        }

        $required_clifor = $this->model_settings->getStatusbyName('required_clifor');
        $bank_is_optional = $this->model_settings->getStatusbyName('store_optional_bank_details');
        $usar_mascara_banco = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
        $create_seller_vtex = $this->model_settings->getStatusbyName('create_seller_vtex');
        $create_seller_mosaico = $this->model_settings->getStatusbyName('create_seller_mosaico');
        $this->data['migration_store'] = $this->model_settings->getStatusbyName('migration_store');
        $store_cpf_optional = $this->model_settings->getStatusbyName('store_cpf_optional');
        $stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;

        $this->data['external_marketplace_integration'] = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->model_banks->getBanks();
        $this->data['CSs'] = $this->model_users->getUsersByCompanyId(1);
        $this->data['sellercenter_name']=$this->model_settings->getSettingDatabyName('sellercenter_name')["value"];

        $paymentGatewayId = $this->model_settings->getSettingDatabyName('payment_gateway_id')['value'];
        $gatewayName = $paymentGatewayId ? $this->model_gateway->getGatewayNameById($paymentGatewayId) : '';
        $this->data['gateway_name'] = ucfirst($gatewayName);

        /**
         * Subaccount Status
         */
        $subaccountStatus = $paymentGatewayId ? $this->getSubaccountStatus($paymentGatewayId, $id) : null;
        $this->data['labelSubaccountStatus'] = $subaccountStatus ? StoreSubaccountStatusFilterEnum::getName($subaccountStatus) : '';
        $this->data['alertColorlabelSubaccountStatus'] = $this->getAlertColorSubaccountStatus($subaccountStatus);

        /**
         * Financial Management System Customer
         */
        $this->data['is_using_financial_management_system'] = false;
        if ($this->model_settings->getValueIfAtiveByName('nibo_api_key')) {

            $this->data['is_using_financial_management_system'] = true;

            $financialManagementSystemStatus = $this->getFinancialManagementSystemCustomerStatus($id);
            $this->data['financialManagementSystemName'] = 'Nibo';
            $this->data['labelFinancialManagementSystemStatus'] = StatusFinancialManagementSystemEnum::getName($financialManagementSystemStatus);
            $this->data['alertColorlabelFinancialManagementSystemStatus'] = $this->getAlertColorFinancialManagementSystemStatus($financialManagementSystemStatus);

        }

        $plans = $this->model_plans->getPlans();
        $this->data['plans'] = $plans;

        $this->data['empresas'] = $this->model_company->getCompanyData(null,true);
        $this->data['store_data'] = $this->model_stores->getStoresData($id);

        $plano_emmpresa = null;
        foreach ($this->data['empresas'] as $empresa) {
            if ($this->data['store_data']['company_id'] == $empresa['id']) {
                $plano_emmpresa = $empresa['plan_id'];
            }
        }
        $company = $this->model_company->getCompanyData($this->data['store_data']['company_id'],true);
        $this->data['plano_empresa'] = $plano_emmpresa;

        $this->data['tipos_volumes_loja'] = $this->model_stores->getStoresTiposVolumesData($id);

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration") && $create_seller_mosaico) {
            $this->form_validation->set_rules('aggregate_select', "Aggregate Merchant", 'trim|required');
            $this->form_validation->set_rules('website_url', 'Website Url', 'trim|required');
            $this->form_validation->set_rules('responsible_sac_name', $this->lang->line('application_responsible_sac_name'), 'trim|required');
            $this->form_validation->set_rules('responsible_sac_email', $this->lang->line('application_responsible_sac_email'), 'trim|required');
            $this->form_validation->set_rules('responsible_sac_tell', $this->lang->line('application_responsible_sac_tell'), 'trim|required');
        }

        $this->form_validation->set_rules('edit_active', $this->lang->line('application_status'), 'trim|required');
        $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('address', $this->lang->line('application_address'), 'trim|required');
        $this->form_validation->set_rules('addr_num', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('addr_compl', $this->lang->line('application_complement'), 'trim');
        $this->form_validation->set_rules('addr_neigh', $this->lang->line('application_neighb'), 'trim|required');
        $this->form_validation->set_rules('addr_city', $this->lang->line('application_city'), 'trim|required');
        $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        $this->form_validation->set_rules('country', $this->lang->line('application_country'), 'trim|required');
        $this->form_validation->set_rules('zipcode', $this->lang->line('application_zip_code'), 'trim|required');
        $this->form_validation->set_rules('type_view_tag', $this->lang->line('application_type_tag'), 'trim|required');
        $this->form_validation->set_rules('invoice_cnpj', $this->lang->line('application_cnpj_fatured'));
        $this->form_validation->set_rules('phone_1', $this->lang->line('application_phone'), 'required|min_length[10]');
        $this->form_validation->set_rules('phone_2', $this->lang->line('application_phone'), 'required|min_length[10]');
        $this->form_validation->set_rules('additional_operational_deadline', $this->lang->line('application_store_additional_operational_deadline'), 'trim|numeric');
        $this->form_validation->set_rules('buybox', $this->lang->line('application_buy_box'));
        $this->form_validation->set_rules('ativacaoAutomaticaProdutos', $this->lang->line('application_automate_products'));



        $store_multi_channel_fulfillment = 0;
        $this->data['data_company'] = $company;
        $this->data['range_zipcode_multi_channel_fulfillment'] = array();
        $this->data['store_id_principal_multi_cd'] = false;
        $this->data['stores_by_company'] = array();
        if ($stores_multi_cd && $company['multi_channel_fulfillment']) {
            $stores_by_company = $this->model_stores->getStoresMultiCdByCompany($company['id']);
            if (count($stores_by_company)) {
                $store_multi_channel_fulfillment = $stores_by_company[0]['id'];
                $this->data['range_zipcode_multi_channel_fulfillment'] = $this->model_stores_multi_channel_fulfillment->getRangeZipcode($id, $company['id']);
                $this->data['store_id_principal_multi_cd'] = $store_multi_channel_fulfillment == $id;
                $this->data['stores_by_company'] = $stores_by_company;
            }
        }

        if ($this->postClean('same',TRUE) != "1") {
            $this->form_validation->set_rules('business_street', $this->lang->line('application_address'), 'trim|required');
            $this->form_validation->set_rules('business_addr_num', $this->lang->line('application_number'), 'trim|required');
            $this->form_validation->set_rules('business_addr_compl', $this->lang->line('application_complement'), 'trim');
            $this->form_validation->set_rules('business_neighborhood', $this->lang->line('application_neighb'), 'trim|required');
            $this->form_validation->set_rules('business_town', $this->lang->line('application_city'), 'trim|required');
            $this->form_validation->set_rules('business_uf', $this->lang->line('application_uf'), 'trim|required');
            $this->form_validation->set_rules('business_nation', $this->lang->line('application_country'), 'trim|required');
            $this->form_validation->set_rules('business_code', $this->lang->line('application_zip_code'), 'trim|required');
        }

        if ($this->postClean('pj_pf') == "PF" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
            $this->form_validation->set_rules('RG', $this->lang->line('application_rg'), 'trim');
        } elseif ($this->postClean('pj_pf') == "PJ" && !\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')) {
            $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');

            if ($this->postClean('exempted') != "1" && $company["addr_uf"] != $this->postClean("business_uf")) {
                $this->form_validation->set_rules('inscricao_estadual', $this->lang->line('application_iest_erro'), 'trim|required|callback_checkInscricaoEstadual[' . $this->postClean("business_uf") . ']');
            }
        }

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration") && $this->postClean('exempted_mun',TRUE) != "1" && $company["addr_city"] != $this->postClean("business_town",TRUE)) {
            $this->form_validation->set_rules('inscricao_municipal', $this->lang->line('application_imun'), 'trim|required');
        }

        $this->form_validation->set_rules('responsible_name', $this->lang->line('application_responsible_name'), 'trim|required');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_responsible_email'), 'trim|required');
        $this->form_validation->set_rules('responsible_cpf', $this->lang->line('application_responsible_cpf'), 'trim|callback_checkCPF');

        if ($bank_is_optional != 1) {
            $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
            $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
            $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
            $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
        }
        $birth_date_requered = $this->model_settings->getValueIfAtiveByName('birth_date_requered_to_responsable_store')==="true";
        if($birth_date_requered){
            $this->form_validation->set_rules('responsable_birth_date', $this->lang->line('application_responsible_birth_date'), 'trim|required');
        }
        $this->form_validation->set_rules('service_charge_value', $this->lang->line('application_charge_amount'), 'trim|required');
        if ($this->postClean('service_charge_freight_option',TRUE) != "1") {
            $this->form_validation->set_rules('service_charge_freight_value', $this->lang->line('application_charge_amount_freight'), 'trim|required');
        }

        if ($this->postClean('freight_seller',TRUE) == 1 && $this->postClean('freight_seller_type', TRUE) == 1) {
            $this->form_validation->set_rules('freight_seller_end_point', $this->lang->line('freight_seller_end_point'), 'trim|required|valid_url');
        }
        if ($this->postClean('freight_seller',TRUE) == 1 && $this->postClean('freight_seller_type', TRUE) == 3) {
            $this->form_validation->set_rules('freight_seller_end_point', $this->lang->line('freight_seller_end_point'), 'trim|required');
            $this->form_validation->set_rules('freight_seller_code', $this->lang->line('application_own_logistic_code'), 'trim|required');
        }


        if (!is_null($this->postClean('erp_customer_supplier_code',TRUE))) {
            if(empty($this->postClean('erp_customer_supplier_code',TRUE)) && $required_clifor == "1"){
                $this->form_validation->set_rules('erp_customer_supplier_code', $this->lang->line('application_store_erp_customer_supplier_code'), 'trim|required|callback_checkCliFor[' . $id . ']');
            }else {
                $this->form_validation->set_rules('erp_customer_supplier_code', $this->lang->line('application_store_erp_customer_supplier_code'), 'callback_checkCliFor[' . $id . ']');
            }
        }

        $store_data = $this->model_stores->getStoresData($id);

        $allow_cnpj_store_update = $this->model_settings->getStatusbyName('allow_cnpj_store_update');
        $paymentGatewayId = $this->model_settings->getSettingDatabyName('payment_gateway_id')['value'];
        $subaccountStatus = $paymentGatewayId ? $this->getSubaccountStatus($paymentGatewayId, $store_data['id']) : null;
        $cnpj_disabled = "";
        if($allow_cnpj_store_update != "1"){
            $cnpj_disabled = "disabled";
        }
        if($allow_cnpj_store_update != "1" && ($subaccountStatus == StoreSubaccountStatusFilterEnum::WITH_ERROR || $subaccountStatus == StoreSubaccountStatusFilterEnum::PENDING)){
            $cnpj_disabled = "";
        }

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
            $logo = $store_data['logo'];
            $errologo = false;

            $hasFile = isset($_FILES['store_image']) && $_FILES['store_image']['size'] > 0;
            if ($hasFile) {
                $resp = $this->upload_image($id);
                if ($resp['success']) {
                    $logo = $resp['msg'];
                } else {
                    $errologo = true;
                    $this->session->set_flashdata('error', $resp['msg']);
                }
            } else if ($create_seller_mosaico && !$store_data['logo']) {
                $errologo = true;
                $this->session->set_flashdata('error', $this->lang->line("application_required_logo"));
            }
        } else {
            $logo = '';
            $errologo = false;
            if (array_key_exists('store_image', $_FILES)) {
                if ($_FILES['store_image']['size'] > 0) {
                    $resp = $this->upload_image($id);
                    if ($resp['success']) {
                        $logo = $resp['msg'];
                    } else {
                        $errologo = true;
                        $this->session->set_flashdata('error', $resp['msg']);
                    }
                }
            }
        }

        $this->data['mostrar_campos_envio_erp'] = $this->model_settings->getStatusbyName('send_new_fields_erp');

        if($this->data['mostrar_campos_envio_erp'] == 1){
            $campos = $this->customFields($id);
            $this->data['camposAdicionais'] = $campos['camposAdicionais'];
            $this->data['camposObrigatorios'] = $campos['camposObrigatorios'];

        }

        if (($this->form_validation->run() == TRUE) && ($errologo == false)) {
            // true case
            if($this->data['mostrar_campos_envio_erp'] == 1){
                $map = [
                    "TID"                         => 'tid',
                    "NSU"                         => 'nsu',
                    "Código de Autorização Cartão" => 'authorization_id',
                    "6 primeiros díg. do Cartão" => 'first_digits',
                    "4 últimos díg. do Cartão"   => 'last_digits'
                ];
                
                $adicionaisSelecionados = $this->postClean('campos_adicionais', true) ?? [];
                $obrigatoriosSelecionados = $this->postClean('campos_obrigatorios', true) ?? [];
                
                $dataAdd = ['store_id' => $id];
                $dataMandatory = ['store_id' => $id];
                
                foreach ($map as $label => $dbField) {
                    $dataAdd[$dbField] = in_array($label, $adicionaisSelecionados) ? 1 : 0;
                    $dataMandatory[$dbField] = in_array($label, $obrigatoriosSelecionados) ? 1 : 0;
                }
                
                $this->model_stores->saveFieldsOrdersAdd($dataAdd);
                $this->model_stores->saveFieldsOrdersMandatory($dataMandatory);
            }
            

            if ($this->postClean('freight_seller',TRUE) == "1") {
                $freight_seller_end_point = $this->postClean('freight_seller_end_point',TRUE);
                $freight_seller_type = $this->postClean('freight_seller_type',TRUE);
                $freight_seller = 1;
                $freight_seller_code = null;
                if ($freight_seller_type == 3) {
                    $freight_seller_code = $this->postClean('freight_seller_code',TRUE);
                }
            } else {
                $freight_seller_code = null;
                $freight_seller_end_point = null;
                $freight_seller_type = null;
                $freight_seller = 0;
            }

            if ($this->postClean('service_charge_freight_option',TRUE) == "1") {
                $service_charge_freight_value = $this->postClean('service_charge_value',TRUE);
            } else {
                $service_charge_freight_value = $this->postClean('service_charge_freight_value',TRUE);
            }

            $bank = (is_null($this->postClean('bank',TRUE))) ? "" : $this->postClean('bank',TRUE);
            $agency = (is_null($this->postClean('agency',TRUE))) ? "" : $this->postClean('agency',TRUE);
            $account_type = (is_null($this->postClean('account_type',TRUE))) ? "" : $this->postClean('account_type',TRUE);
            $account = (is_null($this->postClean('account',TRUE))) ? "" : $this->postClean('account',TRUE);

            foreach ($this->data['banks'] as $local_bank) {
                if ( $usar_mascara_banco == true) {
                    if ($local_bank['name'] == $bank) {
    
                        if(strlen($account) != strlen($local_bank['mask_account'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_account') . $local_bank['mask_account']);
                            redirect('stores/update/'.$id, 'refresh');
                        }
                        if( strlen($agency) != strlen($local_bank['mask_agency'])) {
                            $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_agency') . $local_bank['mask_agency']);
                            redirect('stores/update/'.$id, 'refresh');
                        }
                        continue;
                    }
                }else{
                    continue;
                }

            }

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $aggregateId = null;
                if ($create_seller_mosaico) {
                    // Busca o aggregate da request.
                    $aggr = $this->postClean('aggregate_select', TRUE);
                    $aggrName = $this->postClean('aggregate_select_name');

                    // Busca no banco, se não existir ou existir com nome diferente, cria novo.
                    $aggregateDb = $this->model_mosaico_aggregate_merchant->getById($aggr);
                    if (!$aggregateDb || $aggregateDb['name'] != $aggrName && !empty($aggrName)) {
                        $aggr = $this->model_mosaico_aggregate_merchant->create(['name' => $aggrName]);
                    }
                    $aggregateId = $aggr;
                }
            }

            $data = array(
                'name'                  => $this->strReplaceName($this->postClean('name',TRUE)),
                'address'               => $this->postClean('address',TRUE),
                'addr_num'              => $this->postClean('addr_num',TRUE),
                'addr_compl'            => $this->postClean('addr_compl',TRUE),
                'addr_neigh'            => $this->postClean('addr_neigh',TRUE),
                'addr_city'             => $this->postClean('addr_city',TRUE),
                'addr_uf'               => $this->postClean('addr_uf',TRUE),
                'zipcode'               => preg_replace('/[^\d\+]/', '', $this->postClean('zipcode',TRUE)),
                'phone_1'               => preg_replace('/[^\d\+]/', '', $this->postClean('phone_1',TRUE)),
                'phone_2'               => preg_replace('/[^\d\+]/', '', $this->postClean('phone_2',TRUE)),
                'country'               => $this->postClean('country',TRUE),
                'raz_social'                => $this->postClean('pj_pf') == "PF" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? $this->strReplaceName($this->postClean('name')) : $this->strReplaceName($this->postClean('raz_soc')),
                'inscricao_estadual'        => $this->postClean('pj_pf') == "PF" && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ? onlyNumbers($this->postClean('RG') ?: 0) : ($this->postClean('exempted') == "1" ? "0" : $this->postClean('inscricao_estadual')),
                'invoice_cnpj'              => $this->postClean('invoice_cnpj',TRUE) == "1" ? "1" : "0",
                'active'                    => $this->postClean('edit_active',TRUE),
                'company_id'                => $this->postClean('company_id',TRUE),
                'responsible_cs'            => $this->postClean('responsible_cs',TRUE),
                //'CNPJ'                      => $this->numberString($this->postClean('CNPJ',TRUE), false),
                'responsible_name'          => $this->postClean('responsible_name',TRUE),
                'responsible_email'         => $this->postClean('responsible_email',TRUE),
                'responsible_cpf'           => $this->postClean('responsible_cpf',TRUE),

                'responsible_mother_name'         => $this->postClean('responsible_mother_name',TRUE),
                'responsible_position'         => $this->postClean('responsible_position',TRUE),
                'responsible_monthly_income'         => $this->postClean('responsible_monthly_income',TRUE),
                'company_annual_revenue'         => $this->postClean('company_annual_revenue',TRUE),

                'bank'                      => $bank,
                'agency'                    => $agency,
                'account_type'              => $account_type,
                'account'                   => $account,
                'onboarding'                => $this->postClean('onboarding',TRUE) == "" ? null : $this->postClean('onboarding',TRUE),
                'service_charge_value'      => $stores_multi_cd && $store_multi_channel_fulfillment == $id ? 100 : $this->postClean('service_charge_value',TRUE),
                'lista_preco_integracao'    => $this->postClean('lista_preco_integracao',TRUE),
                'url_callback_integracao'   => $this->postClean('url_callback_integracao',TRUE),
                'associate_type'            => $this->postClean('associate_type_pj',TRUE),

                'business_street'           => $this->postClean('same',TRUE) == "1" ? $this->postClean('address',TRUE) : $this->postClean('business_street',TRUE),
                'business_addr_num'         => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_num',TRUE) : $this->postClean('business_addr_num',TRUE),
                'business_addr_compl'       => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_compl',TRUE) : $this->postClean('business_addr_compl',TRUE),
                'business_neighborhood'     => $this->postClean('same',TRUE) == "1" ? $this->postClean('addr_neigh',TRUE) : $this->postClean('business_neighborhood',TRUE),
                'business_town'             => $this->postClean('same') == "1" ? $this->postClean('addr_city',TRUE) : $this->postClean('business_town',TRUE),
                'business_uf'               => $this->postClean('same') == "1" ? $this->postClean('addr_uf',TRUE) : $this->postClean('business_uf',TRUE),
                'business_nation'           => $this->postClean('same') == "1" ? $this->postClean('country',TRUE) : $this->postClean('business_nation',TRUE),
                'business_code'             => $this->postClean('same') == "1" ? $this->postClean('zipcode',TRUE) : $this->postClean('business_code',TRUE),

                'freight_seller'            => $freight_seller,
                'freight_seller_end_point'  => $freight_seller_end_point,
                'freight_seller_type'       => $freight_seller_type,
                'freight_seller_code'       => $freight_seller_code,

                'service_charge_freight_value' => $stores_multi_cd && $store_multi_channel_fulfillment == $id ? 100 : $service_charge_freight_value,

                'description'               => $this->postClean('description',TRUE),
                'exchange_return_policy'    => $this->postClean('exchange_return_policy',TRUE),
                'delivery_policy'           => $this->postClean('delivery_policy',TRUE),
                'security_privacy_policy'   => $this->postClean('security_privacy_policy',TRUE),
                'erp_customer_supplier_code' => $this->postClean('erp_customer_supplier_code',TRUE),
                'logo'                      => $logo,
                'what_integration'          => $this->postClean('what_integration', TRUE),
                'how_up_and_fature'         => $this->postClean('how_up_and_fature', TRUE),
                'mix_of_product'            => $this->postClean('mix_of_product', TRUE),
                'operation_store'           => $this->postClean('operation_store', TRUE),
                'billing_expectation'       => $this->postClean('billing_expectation', TRUE),
                'type_view_tag'             => $this->postClean('type_view_tag', TRUE),
                'flag_bloqueio_repasse'     => $this->postClean('flag_bloqueio_repasse', TRUE),
                'allow_payment_reconciliation_installments' => $this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments') ? $this->postClean('allow_payment_reconciliation_installments', TRUE) : '0',
                'max_time_to_invoice_order' => $stores_multi_cd && $store_multi_channel_fulfillment == $id ? $this->postClean('max_time_to_invoice_order') : null,
                'additional_operational_deadline' => $this->postClean('additional_operational_deadline'),
                'inventory_utilization'     => $stores_multi_cd && $store_multi_channel_fulfillment == $id ? $this->postClean('inventory_utilization') : null,
                'buybox'                    => $this->postClean('buybox',TRUE) == "1" ? "1" : "0",
                'ativacaoAutomaticaProdutos'    => $this->postClean('ativacaoAutomaticaProdutos',TRUE) == "1" ? "1" : "0",
            );

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $data['inscricao_municipal']       = $this->postClean('exempted_mun', TRUE) == "1" ? "0" : $this->postClean('inscricao_municipal', TRUE);
                $data['aggregate_id']              = $aggregateId;
                $data['website_url']               = $this->postClean('website_url', TRUE);
                $data['responsible_sac_name'] = $this->postClean('responsible_sac_name', TRUE);
                $data['responsible_sac_email'] = $this->postClean('responsible_sac_email', TRUE);
                $data['responsible_sac_tell'] = $this->postClean('responsible_sac_tell', TRUE);
            }



            if(in_array('transferAnticipationRelease', $this->permission)){
                $flag_antecipacao_repasse = $this->postClean('flag_antecipacao_repasse', TRUE);
                if($store_data['flag_antecipacao_repasse'] == "S" && $store_data['flag_antecipacao_repasse'] != $flag_antecipacao_repasse){
                    $this->log_data(
                        'Stores',
                        __FUNCTION__,
                        "Setou o campo 'Antecipação de Repasse' para '".($flag_antecipacao_repasse=="S"?"SIM":"NÃO")."'",
                        "I"
                    );
                    $data['flag_antecipacao_repasse'] = $flag_antecipacao_repasse;
                }
                // Inativa a assinatura do contratod e antecipação, caso assinado e ativo
                if($store_data['flag_antecipacao_repasse'] == "S" && $flag_antecipacao_repasse == "N"){
                    $this->model_stores->inactiveContractFromStore($store_data['id']);
                }
            }

            if(empty($cnpj_disabled)){                
                $data['CNPJ'] = $this->numberString($this->postClean('CNPJ',TRUE), false);                
            }

            if ($this->postClean('utm_source', TRUE)) {
                $data['utm_source'] = $this->postClean('utm_source', TRUE);
            }

            if ($this->model_settings->getValueIfAtiveByName('allow_automatic_antecipation')) {
                if ($this->postClean('use_automatic_antecipation', TRUE)) {
                    $data['use_automatic_antecipation'] = $this->postClean('use_automatic_antecipation', TRUE);
                    $data['antecipation_type'] = $this->postClean('antecipation_type', TRUE);
                    $data['percentage_amount_to_be_antecipated'] = $this->postClean('percentage_amount_to_be_antecipated', TRUE);
                    $data['number_days_advance'] = $this->postClean('number_days_advance', TRUE);
                    $data['automatic_anticipation_days'] = $this->postClean('automatic_anticipation_days', TRUE);
                } else {
                    $data['use_automatic_antecipation'] = false;
                }
            } else {
                $data['use_automatic_antecipation'] = false;
            }

            if (in_array('createUserFreteRapido', $this->permission)) {

                $tipos_volumes_post = $this->postClean('tipos_volumes[]',TRUE);
                if (!is_null($tipos_volumes_post)) {
                    foreach ($tipos_volumes_post as $tipo_volume) {
                        $novo = true;
                        foreach ($this->data['tipos_volumes_loja'] as $antigo) {
                            if ($tipo_volume == $antigo['tipos_volumes_id']) {
                                $novo = false;
                                break;
                            }
                        }
                        if ($novo) {
                            $datatipo = array(
                                "store_id" => $id,
                                "tipos_volumes_id" => $tipo_volume,
                                "status" => 1
                            );
                            $this->model_stores->createStoresTiposVolumes($datatipo);
                        }
                    }
                    // incluo os novos tipos se não existirem

                    // removo os que foram desmarcados
                    foreach ($this->data['tipos_volumes_loja'] as $antigo) {
                        $sumiu = true;
                        foreach ($tipos_volumes_post as $tipo_volume) {
                            if ($tipo_volume == $antigo['tipos_volumes_id']) {
                                $sumiu = false;
                                break;
                            }
                        }
                        if ($sumiu) {
                            $this->model_stores->deleteStoresTiposVolumes($id, $antigo['tipos_volumes_id']);
                        }
                    }
                }

            }

            if (($this->postClean('id_indicator',TRUE) != "0" || ($this->postClean('percentage_indication',TRUE) != "" && $this->postClean('percentage_indication',TRUE) != 0)) && $store_data['id_indicator'] === null) {
                if ($this->postClean('id_indicator',TRUE) == "0" || $this->postClean('percentage_indication',TRUE) == "" || $this->postClean('percentage_indication',TRUE) == 0) {
                    $this->session->set_flashdata('error', 'É preciso completar o cadastro do usuário indicador');
                    return redirect('stores/update/' . $id, 'refresh');
                }

                $indicator = explode('-', $this->postClean('id_indicator',TRUE));
                $data['id_indicator'] = $indicator[1];
                $data['type_indicator'] = $indicator[0];
                $data['percentage_indication'] = $this->postClean('percentage_indication',TRUE);
                $data['date_indication'] = date('Y-m-d H:i:s');
                $data['user_create_indication'] = $this->session->userdata('id');
            }
            $data['responsable_birth_date'] = $this->postClean('responsable_birth_date',TRUE);
            $data['use_exclusive_cycle'] = $this->postClean('use_exclusive_cycle') ? 1 : 0;

            //Se está atualizando a loja e inativando ela, vamos verificar se já possui uma subconta e alterar a id dela para _old_{id}
            // if ($id){

            //     $paymentGatewayId = $this->model_settings->getSettingDatabyName('payment_gateway_id')['value'];

            //     if ($paymentGatewayId){

            //         $subaccount = $this->model_gateway->getSubAccountByStoreId($id, $paymentGatewayId);

            //         if ($subaccount){

            //             if ($data['active'] == 1){

            //                 if ($subaccount['gateway_account_id']){
            //                     $subaccount['gateway_account_id'] = str_replace('_old_'.$id, '', $subaccount['gateway_account_id']);
            //                 }
            //                 if ($subaccount['secondary_gateway_account_id']){
            //                     $subaccount['secondary_gateway_account_id'] = str_replace('_old_'.$id, '', $subaccount['secondary_gateway_account_id']);
            //                 }

            //             }elseif($data['active'] == 2){

            //                 if ($subaccount['gateway_account_id']){
            //                     $subaccount['gateway_account_id'].= '_old_'.$id;
            //                 }
            //                 if ($subaccount['secondary_gateway_account_id']){
            //                     $subaccount['secondary_gateway_account_id'].= '_old_'.$id;
            //                 }

            //             }

            //             $this->model_gateway->updateSubAccounts($subaccount['id'], $subaccount);

            //         }

            //     }
            // }

            // Empresa usa o Multi CD e não é a principal.
            if ($stores_multi_cd && $company['multi_channel_fulfillment'] && $store_multi_channel_fulfillment != $id) {
                if ($this->postClean('zipcode_start') && $this->postClean('zipcode_end')) {
                    $zipcodes_multi_cd = array();

                    for ($count = 0; $count < count($this->postClean('zipcode_start')); $count++) {
                        $zipcode_start = onlyNumbers($this->postClean('zipcode_start')[$count]);
                        $zipcode_end   = onlyNumbers($this->postClean('zipcode_end')[$count]);

                        $zipcodes_multi_cd[] = array(
                            'store_id_principal' => $store_multi_channel_fulfillment,
                            'store_id_cd'        => $id,
                            'company_id'         => $company['id'],
                            'zipcode_start'      => $zipcode_start,
                            'zipcode_end'        => $zipcode_end
                        );

                        if (!$this->model_stores_multi_channel_fulfillment->checkAvailabilityRangeZipcode($id, $store_multi_channel_fulfillment, $zipcode_start, $zipcode_end)) {
                            $this->session->set_flashdata('error', "O range de CEP entre $zipcode_start e $zipcode_end já está em uso em outro CD.");
                            redirect("stores/update/$id", 'refresh');
                        }
                    }

                    // Remove os CEPs antigos.
                    $this->model_stores_multi_channel_fulfillment->remove($id);
                    // Salva os novos CEPs antigos.
                    $this->model_stores_multi_channel_fulfillment->create_batch($zipcodes_multi_cd);
                } else {
                    $this->session->set_flashdata('error', "Informe a área de atendimento do CD.");
                    redirect("stores/update/$id", 'refresh');
                }
            }

            $update = $this->model_stores->update($data, $id);

            if ($update == true) {
            	
				$store_data = $this->model_stores->getStoresData($id);
				if ($store_data['token_api']  == '') {
					$token = $this->createTokenAPI($id, (int)$this->postClean('company_id', TRUE));
                	$this->model_stores->update(array("token_api" => $token), $id);
				}

                if (!$data['use_exclusive_cycle']){
                    $this->model_cycles->removeAllCyclesByStoreId($id);
                }

                if(FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration") && $create_seller_mosaico){
                    $selectedSc = $this->postClean("msc_sales_channel",TRUE);
                    $this->model_mosaico_sales_channel->deletePivotAllByStoreId($store_data['id']);

                    foreach ($selectedSc as $sc) {
                        $pivotEntry = ["store_id" => $store_data['id'], "sc_id" => $sc];
                        $this->model_mosaico_sales_channel->createStorePivot($pivotEntry);
                    }
                }

                // altero os catálogos que a loja pode utilizar
                $this->model_catalogs->setCatalogsStoresDataByStoreId($id, $this->postClean('catalogs', TRUE));

                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
				
                redirect('stores/', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('stores/update', 'refresh');
            }
        } else {
        	$this->data['franchise_on_store'] = $this->model_settings->getStatusbyName('franchise_on_store');
			$this->data['big_sellers_on_store'] = $this->model_settings->getStatusbyName('big_sellers_on_store');
			
            $store_data = $this->model_stores->getStoresData($id);

            $this->data['store_data'] = $store_data;
            $this->data['store_data']['phone_1'] = $this->formatPhone($this->data['store_data']['phone_1']);
            $this->data['store_data']['phone_2'] = $this->formatPhone($this->data['store_data']['phone_2']);
            $this->data['store_data']['zipcode'] = $this->formatCep($this->data['store_data']['zipcode']);
            $this->data['tipos_volumes'] = $this->model_category->getTiposVolumes();
            $this->data['tipos_volumes_loja'] = $this->model_stores->getStoresTiposVolumesData($id);
            $this->data['tipos_volumes_novos'] = $this->model_stores->getStoresTiposVolumesNovosCount(' AND store_id = ' . $id);

            $this->data['dataProgressBar'] = $this->model_stores->getDataForTheProgressBar($id);
            $this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs();
            $this->data['linkcatalogs'] = $this->model_catalogs->getCatalogsStoresDataByStoreId($id);

            if ($store_data['id_indicator']) {
                $this->data['users_indicator'] = $store_data['type_indicator'] != 'u' ? [] : $this->model_users->getUsersIndicator($store_data['id_indicator']);
                $this->data['stores_indicator'] = $store_data['type_indicator'] != 's' ? [] : $this->model_stores->getStoresIndicator($store_data['id_indicator']);
                $this->data['companies_indicator'] = $store_data['type_indicator'] != 'c' ? [] : $this->model_company->getCompaniesIndicator($store_data['id_indicator']);
            } else {
                $this->data['users_indicator'] = $this->model_users->getUsersIndicator();
                $this->data['stores_indicator'] = $this->model_stores->getStoresIndicator();
                $this->data['companies_indicator'] = $this->model_company->getCompaniesIndicator();
            }

            $this->data['required_clifor'] = $required_clifor;
            $this->data['cnpj_disabled'] = $cnpj_disabled;
            $this->data['bank_is_optional'] = $bank_is_optional;
            $this->data['store_cpf_optional'] = $store_cpf_optional;
            $this->data['create_seller_vtex'] = $create_seller_vtex;
            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $this->data['create_seller_mosaico'] = $create_seller_mosaico;
                if ($create_seller_mosaico) {
                    $this->data['available_sc'] = $this->model_mosaico_sales_channel->getAll();
                    $this->data['selected_sc'] = $this->model_mosaico_sales_channel->getStoreSalesChannelsConectaId($id);
                }
                $this->data['selected_aggregate']= $this->model_mosaico_aggregate_merchant->getById($store_data['aggregate_id']);
            }
            $this->data['birth_date_requered'] = $birth_date_requered !== false;
            $this->data['allow_automatic_antecipation'] = $this->model_settings->getValueIfAtiveByName('allow_automatic_antecipation');
            $this->data['antecipacao_dx_default'] = $this->model_settings->getValueIfAtiveByName('antecipacao_dx_default');
            $this->data['porcentagem_antecipacao_default'] = $this->model_settings->getValueIfAtiveByName('porcentagem_antecipacao_default');
            $this->data['numero_dias_dx_default'] = $this->model_settings->getValueIfAtiveByName('numero_dias_dx_default');
            $this->data['automatic_anticipation_days_default'] = $this->model_settings->getValueIfAtiveByName('automatic_anticipation_days_default');
            $this->data['allow_payment_reconciliation_installments'] = $this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments');
            $this->data['use_buybox'] = $this->model_settings->getValueIfAtiveByName('buy_box');
            $this->data['use_ativacaoAutomaticaProdutos'] = $this->model_settings->getValueIfAtiveByName('ativacao_automatica_produtos');
            $this->data['default_payment_reconciliation_installments_enabled'] = $this->model_settings->getValueIfAtiveByName('default_payment_reconciliation_installments_enabled');
            $this->data['get_attribute_value_utm_param'] = $this->Model_attributes->getAttributeValueUtmParam();
            $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
            $this->data['marketplaces'] = $this->model_cycles->getAllMarketplaces();
            $this->data['cycle_cut_dates'] = $this->model_cycles->getCutDates();
            $this->data['stores_multi_cd'] = $stores_multi_cd;
            $this->data['seller_id'] = $this->getExternalIdVtex($id);
 
            $this->render_template('stores/edit', $this->data);
        }
    }

    private function getExternalIdVtex(int $storeId): string
    {

        $seller_id_json = $this->model_gateway->getVtexSellerIdIntegration($storeId);
        $seller_id_array = json_decode($seller_id_json, true);

        if (!isset($seller_id_array['seller_id'])) {
            return "";
        }

        return $seller_id_array['seller_id'];
    }

    /*
    * If checks if the store id is provided on the function, if not then an appropriate message
    is return on the json format
    * If the validation is valid then it removes the data into the database and returns an appropriate
    message in the json format.
    */

    public function remove()
    {
        if (!in_array('deleteStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $store_id = $this->postClean('store_id', TRUE);

        $response = array();
        if ($store_id) {
            $delete = $this->model_stores->remove($store_id);
            if ($delete == true) {
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_removed');
            } else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_occurred');
            }
        } else {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_refresh_page');
        }

        echo json_encode($response);
    }

    public function passwordStrenght($password)
    {

        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
            $this->form_validation->set_message('passwordStrenght', 'A {field} deve ter pelo menos 8 letras incluindo uma letra maíuscula, uma minúscula, um número e um símbolo');            
            return false;
        } else {
            return true;
        }
    }

    public function avisoFreteRapido()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('stores/avisofreterapido', $this->data);
    }

    public function avisoFreteRapidoSelect()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!is_null($this->postClean('id'))) {
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $v) {
                    list($store_id, $tipos_volumes_id) = explode("|", $v);
                    $this->model_stores->updateStoresTiposVolumesStatus($store_id, $tipos_volumes_id, '2'); // tudo ok com esta loja
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $v) {
                    list($store_id, $tipos_volumes_id) = explode("|", $v);
                    $this->model_stores->updateStoresTiposVolumesStatus($store_id, $tipos_volumes_id, '1');
                }
            }
        }

        redirect('stores/avisoFreteRapido', 'refresh');
    }

    public function avisoFreteRapidoData()
    {

        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 2 letras
                $newdate = DateTime::createFromFormat('d/m/Y', $busca['value']);
                if ($newdate !== FALSE) {

                    $procura = " AND ( stv.date_update like '%" . $newdate->format('Y-m-d') . "%' OR s.name like '%" . $busca['value'] . "%' OR tv.produto like '%" . $busca['value'] . "%' ) ";
                } else {
                    $procura = " AND ( stv.date_update like '%" . $busca['value'] . "%' OR s.name like '%" . $busca['value'] . "%' OR tv.produto like '%" . $busca['value'] . "%' ) ";
                }
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('s.name', 'tv.produto', 'stv.status', 'stv.date_update', '');
            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_stores->getStoresTiposVolumesNovosData($ini, $procura, $sOrder);

        $i = 0;
        $filtered = $this->model_stores->getStoresTiposVolumesNovosCount($procura);
        $dia = $this->diminuir_dias_uteis(date("Y-m-d"), 2) . " 00:00:00";
        foreach ($data as $key => $value) {
            $i++;

            $linkloja = '<a href="' . base_url() . 'stores/update/' . $value['store_id'] . '" target="_blank">' . $value['loja'] . '</a>';
            $buttons = '<a target="__blank" href="' . base_url('stores/update/' . $value['store_id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';

            if ($value['date_update'] < $dia) {
                $date_update = '<span class="label label-danger">' . date("d/m/Y H:i:s", strtotime($value['date_update'])) . '</span>';
            } else {
                $date_update = '<span class="label label-warning">' . date("d/m/Y H:i:s", strtotime($value['date_update'])) . '</span>';
            }

            if ($value['status'] == 1) {
                $newCat = '<span class="label label-danger">' . $this->lang->line('application_New') . '</span>';
            } elseif ($value['status'] == 2) {
                $newCat = '<span class="label label-default">' . $this->lang->line('application_correio_ok') . '</span>';
            } elseif ($value['status'] == 3) {
                $newCat = '<span class="label label-warning">' . $this->lang->line('application_in_registration') . '</span>';
            } else {
                $newCat = '<span class="label label-success">' . $this->lang->line('application_ok') . '</span>';
            };

            $result[$key] = array(
                $linkloja,
                $value['tipo_volume'],
                $newCat,
                $date_update,
                $buttons
            );
        } // /foreach
        if ($filtered == 0) { $filtered = $i;}
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_stores->getStoresTiposVolumesNovosCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        echo json_encode($output);
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

    function checkCNPJ($cnpj)
    {
        $ok = $this->isCnpjValid($cnpj);
        if (!$ok) {
            $this->form_validation->set_message('checkCNPJ', '{field} inválido.');
        }
        return $ok;
    }

    function checkUniqueCNPJ($cnpj)
    {
        $ok = $this->isCnpjUnique($cnpj);
        if (!$ok) {
            $this->form_validation->set_message('checkUniqueCNPJ', '{field} está em uso por outra Loja.');
        }
        return $ok;
    }

    function checkCPF($cpf)
    {
        $store_cpf_optional = $this->model_settings->getStatusbyName('store_cpf_optional');

        if (($store_cpf_optional == 1) && (trim($cpf) == '' || is_null($cpf))) {
            return true;
        }
        $ok = $this->isCPFValid($cpf);
        if (!$ok) {
            $this->form_validation->set_message('checkCPF', '{field} inválido.');
        }
        return $ok;
    }

    function checkInscricaoEstadual($ie, $uf)
    {
        $ok = ValidatesIE::check($ie, $uf);
        if (!$ok) {
            $this->form_validation->set_message('checkInscricaoEstadual', '{field} inválida.');
        }
        return $ok;
    }

    function checkCliFor($clifor, $id)
    {
        $unique_clifor_store = $this->model_settings->getValueIfAtiveByName('unique_clifor_store');
        if ($clifor == '') {
            return true;
        }
        if (!ctype_alnum($clifor)) {
            $this->form_validation->set_message('checkCliFor', $this->lang->line('application_store_erp_customer_supplier_code_alphanumeric'));
            return false;
        }

        $unique_clifor_store = $this->model_settings->getValueIfAtiveByName('unique_clifor_store');

        if (!$unique_clifor_store) {
            return true;
        }

        $ok = $this->model_stores->uniqueErpCustomerSupplierCode($id, $clifor);

        if (!$ok) {
            $this->form_validation->set_message('checkCliFor', $this->lang->line('application_store_erp_customer_supplier_code_unique'));
        }
        return $ok;
    }

    public function numberString($string = null, $array = true)
    {
        if (!$string) {
            return false;
        }

        $num = array();
        for ($i = 0; $i < (strlen($string)); $i++) {
            if (is_numeric($string[$i])) {
                $num[] = $string[$i];
            }
        }

        if ($array) {
            return $num;
        }

        return implode('', $num);
    }


    function isCnpjValid($cnpj)
    {
        //Etapa 1: Cria um array com apenas os digitos numéricos, isso permite receber o cnpj em diferentes formatos como "00.000.000/0000-00", "00000000000000", "00 000 000 0000 00" etc...
        $num = $this->numberString($cnpj);

        //Etapa 2: Conta os dígitos, um Cnpj válido possui 14 dígitos numéricos.
        if (count($num) != 14) {
            $isCnpjValid = false;
        } //Etapa 3: O número 00000000000 embora não seja um cnpj real resultaria um cnpj válido após o calculo dos dígitos verificares e por isso precisa ser filtradas nesta etapa.
        elseif ($num[0] == 0 && $num[1] == 0 && $num[2] == 0 && $num[3] == 0 && $num[4] == 0 && $num[5] == 0 && $num[6] == 0 && $num[7] == 0 && $num[8] == 0 && $num[9] == 0 && $num[10] == 0 && $num[11] == 0) {
            $isCnpjValid = false;
        } //Etapa 4: Calcula e compara o primeiro dígito verificador.
        else {
            $j = 5;
            for ($i = 0; $i < 4; $i++) {
                $multiplica[$i] = $num[$i] * $j;
                $j--;
            }
            $soma = array_sum($multiplica);
            $j = 9;
            for ($i = 4; $i < 12; $i++) {
                $multiplica[$i] = $num[$i] * $j;
                $j--;
            }
            $soma = array_sum($multiplica);
            $resto = $soma % 11;
            if ($resto < 2) {
                $dg = 0;
            } else {
                $dg = 11 - $resto;
            }
            if ($dg != $num[12]) {
                $isCnpjValid = false;
            }
        }
        //Etapa 5: Calcula e compara o segundo dígito verificador.
        if (!isset($isCnpjValid)) {
            $j = 6;
            for ($i = 0; $i < 5; $i++) {
                $multiplica[$i] = $num[$i] * $j;
                $j--;
            }
            $soma = array_sum($multiplica);
            $j = 9;
            for ($i = 5; $i < 13; $i++) {
                $multiplica[$i] = $num[$i] * $j;
                $j--;
            }
            $soma = array_sum($multiplica);
            $resto = $soma % 11;
            if ($resto < 2) {
                $dg = 0;
            } else {
                $dg = 11 - $resto;
            }
            if ($dg != $num[13]) {
                $isCnpjValid = false;
            } else {
                $isCnpjValid = true;
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


    function isCnpjUnique($cnpj)
    {
        $num = $this->numberString($cnpj);

        $first_validation = \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas') ?
            count($num) != 14 && count($num) != 11 :
            count($num) != 14;

        if ($first_validation) {
            $isCnpjUnique = false;
        } elseif (
            $this->postClean('pj_pf') == "PF" &&
            $num == str_pad(0 , 11, 0 , STR_PAD_LEFT) &&
            \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')
        ) {
            $isCnpjUnique = false;
        } elseif (
            $this->postClean('pj_pf') == "PJ" &&
            $num == str_pad(0 , 14, 0 , STR_PAD_LEFT) &&
            !\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')
        ) {
            $isCnpjUnique = false;
        } else {
            $isCnpjUnique = $this->model_stores->uniqueCnpj($num);
        }

        return $isCnpjUnique;
    }


    function isCPFValid($cpf)
    {
        // Extrai somente os números
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

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

    public function setting()
    {
        if ($this->data['usercomp'] != 1 && ENVIRONMENT == "production") {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('store_id', 'Loja', 'integer|required');

        if ($this->form_validation->run() == TRUE) {
            $store = $this->postClean('store_id',TRUE);

            if ($this->model_stores->removeRequestStoreInvoice($store)) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed'));
                redirect('stores/setting', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('stores/setting', 'refresh');
            }
        }

        $this->data['page_title'] = $this->lang->line('application_request_biller_module');
        $this->render_template('stores/setting_invoice', $this->data);
    }

    public function fetchStoresInvoice()
    {
        $result = array();

        $data = $this->model_stores->getRequestStoreInvoice();

        foreach ($data as $key => $value) {

            $buttons = '<button class="btn btn-default btnConfig" data-toggle="tooltip" store-id="' . $value['store_id'] . '" title="' . $this->lang->line('application_settings') . '"><i class="fa fa-cog"></i></button>
                        <button class="btn btn-danger btnDelete" data-toggle="tooltip" store-id="' . $value['store_id'] . '" title="' . $this->lang->line('application_delete') . '"><i class="fa fa-trash"></i></button>';
            $situation = $value['active'] == 1 ? '<span class="label label-success">' . $this->lang->line('application_active') . '</span>' : '<span class="label label-warning">' . $this->lang->line('application_inactive') . '</span>';

            $result[$value['id']] = array(
                $value['store_name'],
                $value['company_name'],
                $value['erp'],
                $value['token_tiny'],
                date('d/m/Y H:i', strtotime($value['created_at'])),
                $situation,
                $buttons
            );

            if (count($_GET) > 0) {$result[$value['id']] = (object)$result[$value['id']];}
        }

        echo json_encode(array('count' => count($result), 'data' => $result));
    }

    public function getDataStoreRequestInvoice()
    {
        $store_id = $this->postClean('store_id',TRUE);
        if (!isset($store_id)) {
            echo json_encode(array());
            exit();
        }
        $data = $this->model_stores->getRequestStoreInvoice($store_id);
        $data = $data[0];


        $key = get_instance()->config->config['encryption_key'];
        $store_id = array("store_id" => $data['store_id']);
        $store_id_encode = $this->jwt->encode($store_id, $key);

        echo json_encode(array(
            'store_id'          => $store_id_encode,
            'store_id_decode'   => base_url('stores/update/' . $data['store_id']),
            'company_id_decode' => base_url('company/update/' . $data['company_id']),
            'company_name'      => $data['company_name'],
            'store_name'        => $data['store_name'],
            'certificado_path'  => base_url($data['certificado_path']),
            'certificado_pass'  => $data['certificado_pass'],
            'erp'               => $data['erp'],
            'token_tiny'        => $data['token_tiny'],
            'active'            => $data['active'] == 1 ? $this->lang->line('application_active') : $this->lang->line('application_inactive'),
            'status'            => $data['active'],
            'created_at'        => date('d/m/Y H:i', strtotime($data['created_at']))
        ));
    }

    public function updateRequestStoreInvoice()
    {
        $store_decode = $this->postClean('store_id',TRUE);
        $token = $this->postClean('token',TRUE);
        $key = get_instance()->config->config['encryption_key']; // Key para decodificação
        $decodeJWT = $this->jwt->decode($store_decode, $key, array('HS256'));

        // erros na decodificação
        if (is_string($decodeJWT)) {
            echo json_encode(array(false, "erro interno: {$decodeJWT}"));
            exit();
        }

        // order_id
        $store_id = (int)$decodeJWT->store_id;

        $update = $this->model_stores->updateRequestStoreInvoice($store_id, $token);

        if ($update) {
            echo json_encode(array(true, $this->lang->line('messages_successfully_updated')));
        } else {
            echo json_encode(array(false, "Erro para atualizar!"));
        }
    }

    function getDataTokenTiny()
    {
        $token = $this->postClean('token',TRUE);

        $url = 'https://api.tiny.com.br/api2/info.php';
        $data = "token=$token&formato=json";

        try {
            $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
            ));

            $ctx = stream_context_create($params);
            $fp = @fopen($url, 'rb', false, $ctx);
            if (!$fp) {
                echo json_encode(array('success' => false, 'data' => $php_errormsg));
                exit();
            }
            $response = @stream_get_contents($fp);
            if ($response === false) {
                echo json_encode(array('success' => false, 'data' => $php_errormsg));
                exit();
            }
            $response = json_decode($response);

            if ($response->retorno->status_processamento == 3 && $response->retorno->status == "OK") {
                echo json_encode(array(
                    'success' => true,
                    'razao_social' => $response->retorno->conta->razao_social,
                ));
            } else {
                $arrErros = array();

                if ($response->retorno->status_processamento == 1 || $response->retorno->status_processamento == 2) {
                    foreach ($response->retorno->erros as $erro) {
                        array_push($arrErros, $erro->erro);
                    }
                }

                $strErros = implode(' | ', $arrErros);
                echo json_encode(array('success' => false, 'data' => $strErros));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'data' => $e->getMessage()));
        }
    }

    protected function loadFormCredentials()
    {
        $arrIntegration = [];
        foreach ($this->postClean() as $keyCredential => $valueCredential) {
            if (in_array($keyCredential, [
                'store',
                'integration',
                'interface',
                'use_logistics_equals_integration',
                'update_order_to_in_transit_and_delivery',
                'quote_via_integration',
                'url_quote_via_integration',
                "price_not_update"
            ])) {
                continue;
            }

            $arrIntegration[$keyCredential] = $this->postClean($keyCredential, true);
        }
        return $arrIntegration;
    }

    public function integration(int $storeId = 0)
    {
        $storeId = empty($storeId) ? $this->input->get('store') : $storeId;

        $stores = $this->model_stores->getActiveStore();
        $system = $this->postClean('integration',TRUE);

        $useCatalogIntegrationERP = false;
        $settingUseCatalogERP = $this->model_settings->getSettingDatabyName('use_catalog_integration_erp');
        if ($settingUseCatalogERP && $settingUseCatalogERP['status'] == 1) {
            $useCatalogIntegrationERP = true;
        }

        $this->data['useCatalogIntegrationERP'] = $useCatalogIntegrationERP;

        $this->form_validation->set_rules('integration', $this->lang->line('application_integration'), 'trim|required');
        $this->form_validation->set_rules('store', $this->lang->line('application_store'), 'trim|required');

        $this->form_validation->set_rules('use_logistics_equals_integration', '', 'trim');
        $this->form_validation->set_rules('update_order_to_in_transit_and_delivery', '', 'trim');
        $this->form_validation->set_rules('quote_via_integration', '', 'trim');
        $this->form_validation->set_rules('url_quote_via_integration', '', 'trim');

        switch ($system) {
            case 'bling':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('store_code', $this->lang->line('application_store_code'), 'trim');
                break;
            case 'pluggto':
                $this->form_validation->set_rules('user_id', 'user_id', 'trim|required');
                break;
            case 'bseller':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('url_bseller', 'url_bseller', 'trim|required');
                $this->form_validation->set_rules('interface', 'Interface', 'trim|required');
                break;
            case 'eccosys':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('url_eccosys', 'url_eccosys', 'trim|required');
                break;
            case 'tiny':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                break;
            case 'vtex':
            case 'mevo':
                $this->form_validation->set_rules('token_vtex', 'X-VTEX-API-AppToken', 'trim|required');
                $this->form_validation->set_rules('appkey_vtex', 'X-VTEX-API-AppKey', 'trim|required');
                $this->form_validation->set_rules('account_name_vtex', 'accountName', 'trim|required');                
                $this->form_validation->set_rules('sales_channel_vtex', 'salesChannel', 'trim|required');
                $this->form_validation->set_rules('affiliate_id_vtex', 'affiliateId', 'trim|required');
                if ($system == 'mevo') {
                    $this->form_validation->set_rules('base_url_external', 'UrlBase', 'trim|required');
                }
                break;
            case 'jn2':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('url_jn2', 'url_jn2', 'trim|required');
                break;
            case 'lojaintegrada':
                $this->form_validation->set_rules('chave_api', 'chave_api', 'trim|required');
                break;
            case 'viavarejo_b2b':
                $this->form_validation->set_rules('partnerId', 'ID Parceiro', 'trim|required');
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('campaign_pontofrio', $this->lang->line('application_campaigns') . ' Ponto Frio', 'trim|required');
                $this->form_validation->set_rules('campaign_extra', $this->lang->line('application_campaigns') . ' Entra', 'trim|required');
                $this->form_validation->set_rules('campaign_casasbahia', $this->lang->line('application_campaigns') . ' Casas Bahia', 'trim|required');
                $this->form_validation->set_rules('cnpj_pontofrio', $this->lang->line('application_cnpj') . ' Ponto Frio', 'trim|required');
                $this->form_validation->set_rules('cnpj_extra', $this->lang->line('application_cnpj') . ' Entra', 'trim|required');
                $this->form_validation->set_rules('cnpj_casasbahia', $this->lang->line('application_cnpj') . ' Casas Bahia', 'trim|required');
                $this->form_validation->set_rules('related_store_pontofrio', $this->lang->line('application_store') . ' Ponto Frio', 'trim|required');
                $this->form_validation->set_rules('related_store_extra', $this->lang->line('application_store') . ' Extra', 'trim|required');
                $this->form_validation->set_rules('related_store_casasbahia', $this->lang->line('application_store') . ' Casas Bahia', 'trim|required');
                break;
            case 'hub2b':
                $this->form_validation->set_rules('idTenant', 'ID Loja', 'trim|required');
                $this->form_validation->set_rules('authToken', 'Auth Token (API V1)', 'trim|required');
                $this->form_validation->set_rules('username', 'Username', 'trim|required');
                $this->form_validation->set_rules('password', 'Password', 'trim|required');
                break;
            case 'magalu':
                $this->form_validation->set_rules('magalu_username', 'Username', 'trim|required');
                $this->form_validation->set_rules('magalu_password', 'Password', 'trim|required');
                $this->form_validation->set_rules('magalu_default_stock', 'Quantidade em Estoque para Produtos', 'trim|required');
                break;
            case 'microvix':
                $this->form_validation->set_rules('microvix_usuario', 'Username', 'trim|required');
                $this->form_validation->set_rules('microvix_senha', 'Password', 'trim|required');
                $this->form_validation->set_rules('microvix_cnpj','CNPJ','trim|required');
                $this->form_validation->set_rules('microvix_id_portal','Id Portal','trim|required');
                break;    
        }


        $integrations_logistic = $this->integrationLogistics->getAllIntegration();
        $this->data['integration_logistic_by_name'] = array();
        foreach ($integrations_logistic as $integration_logistic) {
            $this->data['integration_logistic_by_name'][$integration_logistic['name']] = $integration_logistic['description'];
        }
        $this->data['integration_logistic_by_name'] = json_encode($this->data['integration_logistic_by_name'], JSON_UNESCAPED_UNICODE);

        if ($this->form_validation->run() == TRUE) {
            $store = $this->postClean('store');

            $existStore = false;

            foreach ($stores as $_store) {
                // verifica se o código da loja realmente pertence a empresa
                if ($_store['id'] == $store) {
                    $existStore = true;
                }
            }

            if (!$existStore) {
                $this->session->set_flashdata('error', "Erro: " . $this->lang->line('application_stores'));
                redirect('stores/integration/', 'refresh');
            }

            $use_logistics_equals_integration           = $this->postClean('use_logistics_equals_integration');
            $update_order_to_in_transit_and_delivery    = $this->postClean('update_order_to_in_transit_and_delivery');
            $quote_via_integration                      = $this->postClean('quote_via_integration');
            $url_quote_via_integration                  = $this->postClean('url_quote_via_integration');
            $price_not_update                           = $this->postClean('price_not_update');

            if ($use_logistics_equals_integration || $update_order_to_in_transit_and_delivery || $quote_via_integration) {
                try {
                    $integration_actual = $this->integrationLogisticConfigurations->getIntegrationSeller($store);
                    $is_update = (bool)$integration_actual;

                    // loja já tem integração, então não vamos trocar.
                    if (
                        $integration_actual &&
                        $integration_actual['integration'] != 'precode' && // se for precode, a loja tem autonomia.
                        $integration_actual['integration'] != $system // se for o erp integrador, a loja tem autonomia
                    ) {
                        $data_logistics = $this->integrationLogistics->getIntegrationsByName($integration_actual['integration']);
                        $this->session->set_flashdata('error', "Você possui a integração com {$data_logistics['description']} ativa, solicite a exclusão da integração para o administrador para que você possa configurar a integração com a sua integração atual.");
                        redirect("stores/integration/$store", 'refresh');
                    }

                    // Usará a integração 'precode', mas sem endpoint. Quando '$update_order_to_in_transit_and_delivery', for verdadeiro.
                    $id_logistic_precode = $this->integrationLogistics->getIntegrationsByName('precode');
                    $arrCreateIntegrationLogistic = array(
                        'id_integration' => $id_logistic_precode['id'] ?? null,
                        'integration' => $id_logistic_precode['name'],
                        'credentials' => '{}',
                        'store_id' => $store,
                        $is_update ? 'user_created' : 'user_updated' => $this->session->userdata('id')
                    );

                    // Usará a logística da integradora.
                    if ($use_logistics_equals_integration) {
                        if (!array_key_exists($system, json_decode($this->data['integration_logistic_by_name'], true))) {
                            $this->session->set_flashdata('error', "A integradora selecionada, $system, não tem integração logística.");
                            redirect("stores/integration/$store", 'refresh');
                        }
                        $id_logistic_integration = $this->integrationLogistics->getIntegrationsByName($system);

                        $arrCreateIntegrationLogistic['id_integration'] = $id_logistic_integration['id'] ?? null;
                        $arrCreateIntegrationLogistic['integration'] = $id_logistic_integration['name'];

                    } // Usará a integração 'precode', com endpoint.
                    elseif ($quote_via_integration) {
                        $arrCreateIntegrationLogistic['credentials'] = json_encode(array('endpoint' => $url_quote_via_integration), JSON_UNESCAPED_UNICODE);
                    }

                    $this->integrationLogisticService->saveIntegrationLogisticConfigure(array_merge($arrCreateIntegrationLogistic, [
                            'id' => $integration_actual['id'] ?? null,
                            'active' => true
                        ])
                    );
                    $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__.'/saveIntegrationLogisticConfigure', json_encode($arrCreateIntegrationLogistic));
                } catch (Throwable $e) {
                    $this->session->set_flashdata('error', implode('<br>', $e->getMessage()));
                    redirect("stores/integration/$store", 'refresh');
                }
            }

            $arrIntegration = array();

            $dataIntegrationStore = $this->model_stores->getDataApiIntegration($store);
            $arrInsert['status'] = 1;
            if (isset($dataIntegrationStore['store_id']) && $dataIntegrationStore && $dataIntegrationStore['store_id']) {
                $arrInsert = $dataIntegrationStore;
                unset($arrInsert['token_callback']);
                unset($arrInsert['id']);
            } else {
                $this->model_integrations->removeRowsOrderToIntegration($store);
            }

            if($price_not_update){
                $arrIntegration['price_not_update'] = trim($this->postClean('price_not_update'));
            }

            $integrationDescription = '';
            switch ($system) {
                case 'bling':
                    $arrIntegration['apikey_bling'] = trim($this->postClean('token'));
                    $arrIntegration['loja_bling'] = trim($this->postClean('store_code'));
                    $arrIntegration['stock_bling'] = trim($this->postClean('stock_bling'));
                    break;
                case 'pluggto':
                    $arrIntegration['client_id_pluggto'] = $this->postClean('client_id_pluggto');
                    $arrIntegration['client_secret_pluggto'] = $this->postClean('client_secret_pluggto');
                    $arrIntegration['username_pluggto'] = $this->postClean('username_pluggto');
                    $arrIntegration['password_pluggto'] = $this->postClean('password_pluggto');
                    $arrIntegration['user_id'] = $this->postClean('user_id');
                    break;
                case 'eccosys':
                    $arrIntegration['token_eccosys'] = $this->postClean('token');
                    $arrIntegration['url_eccosys']   = $this->postClean('url_eccosys');
                    break;
                case 'bseller':
                    $arrIntegration['token_bseller'] = trim($this->postClean('token'));
                    $arrIntegration['url_bseller']   = trim($this->postClean('url_bseller'));
                    $arrIntegration['interface']     = trim($this->postClean('interface'));
                    break;
                case 'tiny':
                    $arrIntegration['token_tiny'] = $this->postClean('token');
                    $arrIntegration['endpoint_quote'] = $this->postClean('endpoint_quote');
                    $arrIntegration['lista_tiny'] = $this->postClean('price_list');
                    $arrIntegration['id_lista_tiny'] = "";
                    $arrIntegration['stock_tiny'] = trim($this->postClean('stock_tiny'));
                    break;
                case 'vtex':
                case 'mevo':
                    $arrIntegration['token_vtex'] = $this->postClean('token_vtex');
                    $arrIntegration['appkey_vtex'] = $this->postClean('appkey_vtex');
                    $arrIntegration['account_name_vtex'] = $this->postClean('account_name_vtex');
                    $arrIntegration['environment_vtex'] = $this->postClean('environment_vtex') ?? 'vtexcommercestable';
                    $arrIntegration['sales_channel_vtex'] = $this->postClean('sales_channel_vtex');
                    $arrIntegration['affiliate_id_vtex'] = $this->postClean('affiliate_id_vtex');
                    if ($system == 'mevo') {
                        $parse_base_url = parse_url($this->postClean('base_url_external'));
                        $arrIntegration['base_url_external'] = str_replace('www.', '', $parse_base_url['host'] ?? $parse_base_url['path']);
                    }
                    break;
                case 'jn2':
                    $arrIntegration['token_jn2'] = $this->postClean('token');
                    $arrIntegration['url_jn2']   = $this->postClean('url_jn2');
                case 'lojaintegrada':
                    $arrIntegration['chave_api'] = $this->postClean('chave_api');
                    break;
                case 'magalu':
                    $arrIntegration['magalu_username'] = $this->postClean('magalu_username');
                    $arrIntegration['magalu_password'] = $this->postClean('magalu_password');
                    $arrIntegration['magalu_default_stock'] = $this->postClean('magalu_default_stock');
                    $arrIntegration['save_images_in_father_product'] = (bool)$this->postClean('save_images_in_father_product');
                    break;
                case 'microvix':
                    $arrIntegration['microvix_usuario'] = $this->postClean('microvix_usuario');
                    $arrIntegration['microvix_senha'] = $this->postClean('microvix_senha');
                    $arrIntegration['microvix_cnpj'] = $this->postClean('microvix_cnpj');
                    $arrIntegration['microvix_chave'] = $this->postClean('microvix_chave');
                    $arrIntegration['microvix_id_portal'] = $this->postClean('microvix_id_portal');
                    break;    
                case 'viavarejo_b2b':
                    $arrIntegration['token_b2b_via'] = $this->postClean('token');
                    $integrationDescription = 'Via Varejo B2B';
                    break;
                case 'tray':
                case 'hub2b':
                case 'ideris':
                    $arrIntegration = $this->loadFormCredentials();
                    if($price_not_update){
                        $arrIntegration['price_not_update'] = trim($this->postClean('price_not_update'));
                    }
                    $integrationTrayStore = $this->model_api_integrations->getIntegrationByStoreId($store);
                    if (isset($integrationTrayStore['credentials'])) {
                        $credentialsDecode = json_decode($integrationTrayStore['credentials'], true);
                        if ($system == 'tray') {
                            $arrIntegration = array_merge($arrIntegration, $credentialsDecode);
                            $arrIntegration = array_merge($arrIntegration, ['storeUrl' => $arrIntegration['storeUrl']]);
                            $integrationDescription = 'Tray';
                            if(!$price_not_update && isset($arrIntegration['price_not_update'])){
                                unset($arrIntegration['price_not_update']);
                            }
                        } elseif ($system == 'hub2b') {
                            $arrIntegration = array_merge($arrIntegration, $credentialsDecode, [
                                'idTenant' => $arrIntegration['idTenant'],
                                'authToken' => $arrIntegration['authToken'],
                                'username' => $arrIntegration['username'],
                                'password' => $arrIntegration['password'],
                            ]);
                            $integrationDescription = 'Hub2b';
                        } elseif ($system == 'ideris') {
                            $arrIntegration = array_merge($arrIntegration, $credentialsDecode, [
                                'authToken' => $arrIntegration['authToken'],
                            ]);
                            $integrationDescription = 'Ideris';
                        }
                    }
                    break;
                default:
                    $arrIntegration = $this->loadFormCredentials();
            }

            // limpo o array para não pegar o ID
            if ($system === 'viavarejo_b2b') {
                $arrInsert = array();
            }

            $arrInsert['store_id'] = $store;
            $arrInsert['user_id'] = $this->session->userdata('id');
            $arrInsert['credentials'] = json_encode($arrIntegration);
            $arrInsert['integration'] = $system;
            if (!empty($integrationDescription)) {
                $arrInsert['description_integration'] = $integrationDescription;
            }

            if (!empty($arrInsert['integration'] ?? '')) {
                $idIntegrationErp = $this->model_integration_erps->find(['name' => $arrInsert['integration']])['id'] ?? null;
                $arrInsert['integration_erp_id'] = $idIntegrationErp;
            }
            if ($system === 'viavarejo_b2b') {
                $this->db->trans_begin();
                $integrationsVia = [
                    FlagMapper::FLAG_PONTOFRIO,
                    FlagMapper::FLAG_EXTRA,
                    FlagMapper::FLAG_CASASBAHIA,
                ];
                $checkStoreVia = array();
                $checkCampaignVia = array();
                foreach ($integrationsVia as $integrationVia) {
                    $storeVia = $this->postClean("related_store_$integrationVia");
                    $campaignVia = $this->postClean("campaign_$integrationVia", true);
                    $cnpjVia = $this->postClean("cnpj_$integrationVia", true);
                    $arrIntegration['partnerId'] = $this->postClean("partnerId", true);
                    $arrIntegration['campaign'] = $campaignVia;
                    $arrIntegration['cnpj'] = $cnpjVia;
                    $arrIntegration['flag'] = $integrationVia;
                    $arrIntegration['flagId'] = FlagMapper::getFlagIdByName($integrationVia);
                    $arrIntegration['name'] = FlagMapper::ENABLED_FLAGS[$integrationVia];
                    $arrIntegration['bandeira'] = $integrationVia;
                    $arrIntegration['idLojista'] = $arrIntegration['flagId'];
                    if (isset($dataIntegrationStore['credentials'])) {
                        $credentialsDecode = json_decode($dataIntegrationStore['credentials'], true);
                        $arrIntegration['webhookAuthToken'] = $credentialsDecode['webhookAuthToken'] ?? null;
                    }
                    $integrationViaStore = $this->model_api_integrations->getIntegrationByStoreId($storeVia);
                    if (isset($integrationViaStore['credentials'])) {
                        $credentialsDecode = json_decode($integrationViaStore['credentials'], true);
                        $arrIntegration = array_merge($credentialsDecode, $arrIntegration);
                    }

                    $arrIntegration['webhookAuthToken'] = $arrIntegration['webhookAuthToken'] ?? md5(uniqid(rand(), true) . date('sYimsdHs'));
                    $arrIntegration['idLojista'] =
                        $integrationVia === FlagMapper::FLAG_CASASBAHIA ?
                            FlagMapper::FLAG_CASASBAHIA_ID :
                            (
                                $integrationVia === FlagMapper::FLAG_PONTOFRIO ?
                                    FlagMapper::FLAG_PONTOFRIO_ID :
                                    (
                                        $integrationVia === FlagMapper::FLAG_EXTRA ?
                                            FlagMapper::FLAG_EXTRA_ID :
                                            null
                                    )
                            );

                    if ($arrIntegration['idLojista'] === null) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', "Não localizada o código da bandeira ($integrationVia).");
                        redirect("stores/integration?store=$store", 'refresh');
                    }

                    if (in_array($storeVia, $checkStoreVia)) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Não é permitido escolher a mesma loja, para mais de um bandeira');
                        redirect("stores/integration?store=$store", 'refresh');
                    }

                    if (in_array($campaignVia, $checkCampaignVia)) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Não é permitido escolher o mesmo código de campanha, para mais de um bandeira');
                        redirect("stores/integration?store=$store", 'refresh');
                    }

                    $checkCampaignVia[] = $campaignVia;
                    $checkStoreVia[] = $storeVia;

                    $arrInsert['credentials'] = json_encode($arrIntegration);
                    $arrInsert['integration'] = "viavarejo_b2b_$integrationVia";
                    $arrInsert['store_id'] = $storeVia;
                    $arrInsert['status'] = Model_api_integrations::ACTIVE_STATUS;

                    if ($dataIntegrationStore['integration']) {
                        $updateInsert = $this->model_api_integrations->updateByStore($storeVia, $arrInsert);
                    } else {
                        $updateInsert = $this->model_api_integrations->create($arrInsert);
                    }

                    if (!$updateInsert) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . $this->lang->line('messages_refresh_page_again'));
                        redirect("stores/integration?store=$store", 'refresh');
                    }
                }

                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . $this->lang->line('messages_refresh_page_again'));
                    redirect("stores/integration?store=$store", 'refresh');
                }

                $this->db->trans_commit();
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_integration_api'));
                redirect('integrations/job_integration', 'refresh');
            }

            if (isset($dataIntegrationStore['integration']) && $dataIntegrationStore && $dataIntegrationStore['integration']) {
                $updateInsert = $this->model_api_integrations->updateByStore($store, $arrInsert);
            } else {
                $updateInsert = $this->model_api_integrations->create($arrInsert);
            }

            if ($updateInsert) {
                if (in_array($system, ['tray', 'hub2b', 'ideris'])) {
                    $integration = $this->model_api_integrations->getIntegrationByStoreId($store);
                    $integration = array_merge($integration, ['credentials' => json_decode($integration['credentials'])]);

                    require_once APPPATH . "libraries/Integration_v2/{$system}/Controllers/AuthApp.php";
                    $className = "\Integration_v2\\{$system}\\Controllers\AuthApp";
                    $intAppAuth = new $className(
                        $integration,
                        $this->model_api_integrations
                    );
                    if (!$intAppAuth->testIntegrationConnection($store)) {
                        $this->session->set_flashdata(
                            'error',
                            sprintf("%s %s", $this->lang->line('messages_error_occurred'), $this->lang->line('messages_integration_config_error'))
                        );
                        redirect("stores/integration?store=$store", 'refresh');
                        return;
                    }
                }
                $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__, json_encode($arrInsert));
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_integration_api'));
                redirect('integrations/job_integration', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . $this->lang->line('messages_refresh_page_again'));
                redirect("stores/integration?store=$store", 'refresh');
            }
        }

        // recupera o código da loja por get ou recuperar o primeiro da listagem
        foreach ($stores as $store) {
            $storeId = $storeId == 0 ? $store['id'] : $storeId;
            break;
        }
        $existStore = false;

        foreach ($stores as $store) {
            // verifica se o código da loja realmente pertence a empresa
            if ($store['id'] == $storeId) {
                $existStore = true;
            }
        }

        if (!$existStore) {
            $this->session->set_flashdata('error', "Erro: " . $this->lang->line('application_user_without_store'));
            redirect('dashboard', 'refresh');
        }


        $getStore = $this->model_stores->getStoresData($storeId);
        if (!$getStore['token_callback']) {
            $tokenCallback = bin2hex(random_bytes(8)) . time() . bin2hex(random_bytes(8));

            if ($this->model_stores->getStoreTokenCallback($tokenCallback)) {                
                redirect('stores/integration', 'refresh');
            }

            $this->model_stores->update(array('token_callback' => $tokenCallback), $storeId);
        }
        $this->chave_aplicacao = $this->model_settings->getValueIfAtiveByName('chave_aplicacao_loja_integrada');
        $this->data['chave_application_setted'] = (Boolean)$this->chave_aplicacao;
        $dataIntegrationStore = $this->model_stores->getDataApiIntegrationByStore($storeId);
        $integration = $dataIntegrationStore['integration'] ?? null;
        $storeEmail = null;
        if(isset($_GET['store']) || $storeId > 0){
            $storeId = $_GET['store'] ?? $storeId;
            $store = $this->model_stores->getStoreById($storeId);
            $storeEmail = !empty($store['responsible_email'] ?? '') ? $store['responsible_email'] : null;
            $this->data['store'] = $store;
        }
        $userEmail = $this->model_users->fetchStoreManagerUser($storeId, $store['company_id'] ?? $dataIntegrationStore['company_id'])['email'] ?? null;
        $this->data['user_email'] = $userEmail ?? $storeEmail ?? $this->session->userdata('email');
        $this->data['storesView'] = $stores;
        $this->data['storeId'] = $storeId;

        $credentials = $dataIntegrationStore && !empty($dataIntegrationStore['credentials']) ? json_decode($dataIntegrationStore['credentials']) : null;
        if (strpos($dataIntegrationStore['integration'], 'viavarejo_b2b') !== false) {
            $baseUrl = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
            $baseUrl = empty($baseUrl) ? (get_instance()->config->config['base_url'] ?? base_url()) : base_url();
            $baseUrl = strlen($baseUrl) >= strripos($baseUrl, '/') ? substr($baseUrl, 0, strripos($baseUrl, '/')) : $baseUrl;
            $credentials = (object)array_merge((array)$credentials, [
                'trackingUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Tracking",
                'partialProductUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/PartialProduct",
                'stockProductUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Stock",
                'availabilityProductUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Availability",
                'updateCategoryUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Category",
            ]);
            $credentials->webhookAuthToken = $credentials->webhookAuthToken ?? 'Disponível após salvar as configurações...';
        }

        $this->data['credentials'] = $credentials;
        $this->data['dataIntegration'] = $dataIntegrationStore;
        if(isset($credentials->price_not_update)){
            $this->data['price_not_update'] = $credentials->price_not_update;
        }


        //interface para o bseller
        $this->data['interface'] = (isset($dataIntegrationStore['interface'])) ? $dataIntegrationStore['interface'] : '';

        $settingNameSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
        $nameSellerCenter = $settingNameSellerCenter['value'];
        $this->data['nameSellerCenter'] = $nameSellerCenter;

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $settingSellerCtr = $settingSellerCenter['value'];
        $this->data['sellercenter'] = $settingSellerCtr;

        $this->data['erpDisabled'][$integration] = '';
        $this->data['integration'] = $integration;
        $this->data['disabledIntegration'] = (bool)$integration;

        $this->data['stores_company'] = $this->model_stores->getCompanyStores($store['company_id']);

        $this->data['integration_b2b_viavarejo'] = null;
        if (in_array($integration, array('viavarejo_b2b_pontofrio', 'viavarejo_b2b_extra', 'viavarejo_b2b_casasbahia'))) {
            foreach ($this->data['stores_company'] as $storeInt) {
                $integrationVia = $this->model_stores->getDataApiIntegrationByStore($storeInt['id']);
                if ($integrationVia && !empty($integrationVia['integration'])) {
                    $this->data['integration_b2b_viavarejo'][$integrationVia['integration']] = array('store' => $storeInt['id'], 'credentials' => json_decode($integrationVia['credentials']));
                }
            }
        }

        $this->data['integrationsErp'] = $this->model_integration_erps->getIntegrationActive();
        $this->data['integrations_backoffice'] = array();
        foreach ($this->data['integrationsErp'] as $integrationErp) {
            if ($integrationErp->type != 2) {
                $this->data['integrations_backoffice'][] = $integrationErp->name;
            }
        }
        $this->data['integrations_backoffice'] = json_encode($this->data['integrations_backoffice']);

        $this->data['integration_configuration'] = array();
        $integration_bling = $this->model_integration_erps->getByName('bling_v3');
        if ($integration_bling && !empty($integration_bling['configuration'])) {
            $this->data['integration_configuration'][$integration_bling['name']] = json_decode($integration_bling['configuration'], true);
        }

        $conecta_la_api_url = $this->model_settings->getValueIfAtiveByName("conecta_la_api_url");
        $this->data["conecta_la_api_url"] = $conecta_la_api_url . (substr($conecta_la_api_url,-1)=='/'?'':'/');
        
        $this->render_template('stores/integration', $this->data);
    }

    public function manage_integrations()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $status = $this->postClean('status',TRUE);

        if ($status == 2) {
            $this->form_validation->set_rules('description_integration', 'Descrição é obrigatória', 'trim|required');
        }

        $this->form_validation->set_rules('status', 'Seleciona um status', 'trim');
        $this->form_validation->set_rules('update_token_callback', 'Seleciona um status', 'trim');

        if ($this->form_validation->run() == TRUE) {

            if ($this->postClean('update_token_callback',TRUE) == "true") {

                $storeToken = $this->postClean('store_id',TRUE);
                $getStore = $this->model_stores->getStoresData($storeToken);

                if (!$getStore['token_callback']) {
                    $tokenCallback = bin2hex(random_bytes(8)) . time() . bin2hex(random_bytes(8));

                    if ($this->model_stores->getStoreTokenCallback($tokenCallback)) {
                        $this->session->set_flashdata('error', "Ocorreu um erro inesperado, tente novamente!");
                        redirect('stores/manage_integrations', 'refresh');
                    }

                    $updateToken = $this->model_stores->update(array('token_callback' => $tokenCallback), $storeToken);

                    if ($updateToken) {
                        $this->session->set_flashdata('success', "Chave criada com sucesso!");
                        redirect('stores/manage_integrations', 'refresh');
                    }
                }

                $this->session->set_flashdata('error', "Não foi possível atualizar a chave do callback!");
                redirect('stores/manage_integrations', 'refresh');
            }

            if ($this->postClean('remove_integration',TRUE) == "true") {
                $integration_id = $this->postClean('integration_id',TRUE);
                $action_performed = $this->postClean('action_performed',TRUE);
                
                $getStoreId = $this->model_integrations->getStoreApiIntegration($integration_id);
                $store_id = $getStoreId['store_id'];
                if (!$store_id) {
                    $this->session->set_flashdata('error', "Não foi possível remover a integração! Erro: Integração não encontrado!");
                    redirect('stores/manage_integrations', 'refresh');
                }
                $this->db->trans_begin();                
                $menssage_return = " ";
                switch ($action_performed) {
                    case 1:
                        $this->model_products->setStockStore($store_id);
                        $menssage_return = " | Estoque zerado com sucesso.";
                        break;
                    case 2:
                        $this->model_products->setStatusStore($store_id,'2');
                        $menssage_return = " | Produtos da base do seller inativados com sucesso.";
                        break;
                    case 3:
                        $this->model_products->setStockStore($store_id);
                        $this->model_products->setStatusStore($store_id,'2');
                        $menssage_return = " | Estoque zerado com sucesso | Produtos da base do seller inativados com sucesso.";
                        break;
                    case 4:
                        $this->model_products->setStockStore($store_id);
                        $products = $this->model_products->getProductsByStore($store_id);
                        $response = $this->deleteProduct->moveToTrash($products);
                        if (isset($response['errors'])) {
                            $menssage_return = " | Estoque zerado com sucesso | Erros ao enviar os produtos para a lixeira.";
                        } else {
                            $menssage_return = " | Estoque zerado com sucesso | Produtos enviados para a lixeira.";
                        }     
                        break;
                }
                $this->model_integrations->removeIntegrationStoreId($store_id);
                $this->model_integrations->removeJobByStore($store_id);
                $this->model_calendar->deleteEventByParams($store_id);
                $this->model_calendar->deleteJobByParams($store_id);

                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', "Não foi possível remover a integração!");
                    redirect('stores/manage_integrations', 'refresh');
                }

                $this->db->trans_commit();
                $this->session->set_flashdata('success', "Integração excluída com sucesso".$menssage_return);
                
                redirect('stores/manage_integrations', 'refresh');
            }

            $description_integration = $this->postClean('description_integration',TRUE);
            $id_integration = $this->postClean('id_integration',TRUE);
            $resolvido = $this->postClean('resolvido',TRUE);

            if ($resolvido && $status == 2) {
                $status = 3;
            }

            if ($status != 2 && $status != 3) {
                $description_integration = "";
            }

            $existIntegration = $this->model_stores->getDataApiIntegrationForId($id_integration);

            // se status anterior for diferente de 1
            // status para alteração for 1
            // nunca teve integração(não existe log_integration)
            // remove as orders_to_integration para não ir pedidos antigos
            $getStoreId = $this->model_integrations->getStoreApiIntegration($id_integration);
            $store_id = $getStoreId['store_id'];
            $existLog = $this->model_integrations->getLogsByStore($store_id);
            if ($existIntegration['status'] != 1 && $status == 1 && !$existLog) {
                $this->model_integrations->removeRowsOrderToIntegration($store_id);
            }

            if (!$existIntegration) {
                $this->session->set_flashdata('error', "Erro: Integração não encontrado");
                redirect('stores/manage_integrations', 'refresh');
            }

            $arrUpdate = array(
                'status' => $status,
                'description_integration' => $description_integration
            );

            if ($this->model_stores->updateStatusIntegration($id_integration, $arrUpdate)) {
                $this->session->set_flashdata('success', "Status alterado com sucesso!");
                redirect('stores/manage_integrations', 'refresh');
            } else {
                $this->session->set_flashdata('error', "Não foi possível realizar a alteração do status!");
                redirect('stores/manage_integrations', 'refresh');
            }
        } else {
            $this->render_template('stores/manage_integrations', $this->data);
        }
    }

    public function fetchStoresIntegratios(): CI_Output
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $fields_order = array('api_integrations.store_id', 'stores.name', 'stores.company_id', 'api_integrations.date_updated', 'api_integrations.status', 'api_integrations.integration', '');

            $query = array();
            $query['select'][] = 'stores.name, stores.company_id, api_integrations.id, api_integrations.status, api_integrations.date_updated, api_integrations.integration, api_integrations.store_id, api_integrations.integration_erp_id';
            $query['from'][] = 'api_integrations';
            $query['join'][] = ['stores', 'api_integrations.store_id = stores.id'];

            $data = fetchDataTable(
                $query,
                array('id', 'DESC'),
                null,
                null,
                ['admin_group'],
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

        $erps_type = array();
        foreach ($data['data'] as $key => $value) {
            $integration_erp_id = $value['integration_erp_id'];
            $is_backoffice = $erps_type[$integration_erp_id] ?? false;
            if ($this->data['only_admin'] && !is_null($integration_erp_id)) {
                if (!array_key_exists($integration_erp_id, $erps_type)) {
                    $integration_erps = $this->model_integration_erps->getById($integration_erp_id);
                    $erps_type[$integration_erp_id] = $integration_erps && $integration_erps->type == 1;
                    if ($erps_type[$integration_erp_id]) {
                        $is_backoffice = true;
                    }
                }
            }

            $company = $this->model_company->getCompanyData($value['company_id']);
            $buttons = '<button type="button" class="btn btn-default viewIntegration" data-toggle="tooltip" title="Visualizar" id-integration="' . $value['id'] . '"><i class="fa fa-eye"></i></button>
                        <button type="button" class="btn btn-default updateIntegration" data-toggle="tooltip" title="Configurar" id-integration="' . $value['id'] . '"><i class="fa fa-cog"></i></button>
                        <button type="button" class="btn btn-default updateKeyCallback" data-toggle="tooltip" title="Chave de Callback" id-integration="' . $value['id'] . '"><i class="fa fa-key"></i></button>
                        <button type="button" class="btn btn-default removeIntegration" data-toggle="tooltip" title="Excluir Solicitação" id-integration="' . $value['id'] . '"><i class="fa fa-trash"></i></button>';
            if ($is_backoffice) {
                $buttons .= '<a href="'.base_url("Integration_v2/General/search/$value[store_id]").'" class="btn btn-default" data-toggle="tooltip" title="Consultar na integração"><i class="fa fa-plug"></i></a>';
            }

            switch ($value['status']) {
                case 0:
                    $status = "<span class='label label-warning'>Pendente</span>";
                    break;
                case 1:
                    $status = "<span class='label label-success'>Concluído</span>";
                    break;
                case 2:
                    $status = "<span class='label label-danger'>Problema</span>";
                    break;
                case 3:
                    $status = "<span class='label label-warning'>Problema - Resolvido</span>";
                    break;
                case 4:
                    $status = "<span class='label label-warning'>Pendente - Modificado</span>";
                    break;
                default:
                    $status = "<span class='label label-warning'>Pendente</span>";
            }

            $result[$key] = array(
                $value['store_id'],
                $value['name'],
                $company['name'],
                date('d/m/Y H:i', strtotime($value['date_updated'])),
                $status,
                ucfirst($value['integration']),
                $buttons
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $data['recordsTotal'],
            "recordsFiltered" => $data['recordsFiltered'],
            "data" => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function fetchAggregate()
    {
        $term = $this->input->get('q');
        $items = $this->model_mosaico_aggregate_merchant->searchAggregateMerchant($term);

        $result = [];
        foreach ($items as $item) {
            $result[] = ['id' => $item['id'], 'text' => $item['name']];
        }

        echo json_encode($result);
    }


    public function getDataIntegrations(int $store_id = null)
    {
        $this->output->set_content_type('application/json');

        if (!is_null($store_id)) {
            $integration = $this->model_api_integrations->getDataByStore($store_id, true);
            if (!$integration) {
                return $this->output
                    ->set_output(json_encode(array(
                        'credentials'               => [],
                        'integration'               => null,
                        'store_id'                  => null,
                        'token_callback'            => null,
                        'status'                    => null,
                        'name'                      => null,
                        'description_integration'   => null
                    )));
            }
            $id_integration = $integration['id'];
        } else {
            $id_integration = $this->postClean('id_integration', TRUE);
        }

        $dataIntegration = $this->model_stores->getDataApiIntegrationForId($id_integration);

        $credentials = array();
        if (!empty($dataIntegration['credentials'])) {
            $credentials_decode = json_decode($dataIntegration['credentials'], true);
            if ($credentials_decode) {
                unset($credentials_decode['refresh_token']);
                $credentials = $credentials_decode;
            }
        }

        return $this->output
            ->set_output(json_encode(array(
                'credentials'               => $credentials,
                'integration'               => $dataIntegration['integration'],
                'store_id'                  => $dataIntegration['store_id'],
                'token_callback'            => $dataIntegration['token_callback'],
                'status'                    => $dataIntegration['status'],
                'name'                      => $dataIntegration['name'],
                'description_integration'   => $dataIntegration['description_integration']
            )));
    }

    public function checkCredentialsIntegartions()
    {
        $id_integration = $this->postClean('id_integration',TRUE);
        if (!$id_integration) {
            echo json_encode(['return' => ['Não foi possível localizar a integração'], 'status' => false]);
            exit();
        }

        $getStoreId = $this->model_integrations->getStoreApiIntegration($id_integration);
        $getIntegration = $this->model_integrations->getApiIntegrationStore($getStoreId['store_id']);

        if (!$getIntegration) {
            echo json_encode(['return' => ['Não foi possível localizar a integração'], 'status' => false]);
            exit();
        }

        $dataCredential = json_decode($getIntegration['credentials']);

        $return = [];
        $status = true;

        if ($getIntegration['integration'] == 'bling') {
            $url = "https://bling.com.br/Api/v2/situacao/Vendas/json/?apikey={$dataCredential->apikey_bling}";

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $responseDecode = json_decode($response);

            if ($httpcode == 429) {
                array_push($return, ['API bloqueada, aguarde um minuto e tente novamente!']);
                $status = false;
            } elseif ($httpcode != 200) {
                array_push($return, $responseDecode->retorno->erros);
                $status = false;
            } elseif ($httpcode == 200) {

            } else {
                array_push($return, ['Ocorreu um erro inesperado']);
                $status = false;
            }
        } elseif ($getIntegration['integration'] == 'tiny') {
            for ($x = 0; $x <= 1; $x++) {

                if ($x == 1 && $dataCredential->lista_tiny == "") {continue;}

                $url = $x == 0 ? 'https://api.tiny.com.br/api2/info.php' : 'https://api.tiny.com.br/api2/listas.precos.pesquisa.php';

                $params = array('http' => array(
                    'method' => 'POST',
                    'content' => $x == 0 ? "token={$dataCredential->token_tiny}&formato=json" : "token={$dataCredential->token_tiny}&formato=json&pesquisa=".urlencode($dataCredential->lista_tiny)
                ));

                $ctx = stream_context_create($params);
                $fp = @fopen($url, 'rb', false, $ctx);
                if (!$fp) {
                    $response = '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(fopen): ' . $url . ' "}]}}';
                } else {
                    $response = @stream_get_contents($fp);
                }
                if ($response === false) {
                    $response = '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(stream_get_contents): ' . $url . ' "}]}}';
                }
                $responseDecode = json_decode($response);
                if ($responseDecode->retorno->status == "Erro" && isset($responseDecode->retorno->codigo_erro) && $responseDecode->retorno->codigo_erro == 6) {
                    array_push($return, ['API bloqueada, aguarde um minuto e tente novamente!']);
                    $status = false;
                } elseif ($responseDecode->retorno->status == "Erro") {
                    if ($responseDecode->retorno->codigo_erro == 20 && $x == 1) {
                        array_push($return, ['Lista de preço não encontrada']);
                    }
                    else {
                        array_push($return, $responseDecode->retorno->erros);
                    }
                    $status = false;
                } elseif ($responseDecode->retorno->status == "OK") {

                } else {
                    array_push($return, ['Ocorreu um erro inesperado']);
                    $status = false;
                }
            }
        } elseif ($getIntegration['integration'] == 'vtex') {

            $url = "https://{$dataCredential->account_name_vtex}.{$dataCredential->environment_vtex}.com.br/api/catalog_system/pub/products/search?_from=0&_to=0";

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "accept: application/vnd.vtex.ds.v10+json",
                "content-type: application/json",
                "x-vtex-api-apptoken: {$dataCredential->token_vtex}",
                "x-vtex-api-appkey: {$dataCredential->appkey_vtex}"
            ));
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);

            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            if ($header['httpcode'] != 200 && $header['httpcode'] != 206) {
                array_push($return, ['Credenciais inválidas!']);
                $status = false;
            }
        } elseif ($getIntegration['integration'] == 'lojaintegrada') {
            $chave_aplicacao = $this->model_settings->getValueIfAtiveByName('chave_aplicacao_loja_integrada');
            if(!$chave_aplicacao){
                array_push($return, ["parametro 'chave_aplicacao_loja_integrada' não configurado a chave da aplicação conectala para a loja integrada."]);
                $status = false;
            }
            $params = "format=json&chave_api={$dataCredential->chave_api}&chave_aplicacao={$chave_aplicacao}";
            $loja_integrada_api = $this->model_settings->getValueIfAtiveByName('loja_integrada_api_url');
            if(!$chave_aplicacao){
                array_push($return, ["parametro 'loja_integrada_api_url' não configurado com a URL da Loja Integrada."]);
                $status = false;
            }
            $url = "{$loja_integrada_api}/v1/produto?{$params}";

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
            ));
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);

            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);
            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            if ($header['httpcode'] != 200 && $header['httpcode'] != 206) {
                array_push($return, ["Credenciais inválidas! - {$header['content']}"]);
                $status = false;
            }
        } elseif ($getIntegration['integration'] == 'magalu') {

            $url = "https://b2b.magazineluiza.com.br/api/v1/oauth/token";

            $data = [
                'username' => $dataCredential->magalu_username,
                'password' => $dataCredential->magalu_password,
            ];

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "accept: application/json",
                "content-type: application/json",
            ));
            curl_setopt($curl_handle, CURLOPT_POST, count($data));
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);

            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            if ($header['httpcode'] != 200) {
                array_push($return, ['Credenciais inválidas!']);
                $status = false;
            }
        } else {
            array_push($return, ['Função ainda não implementada para a integração de: ' . strtoupper($getIntegration['integration'])]);
            $status = false;
        }

        echo json_encode(['return' => $return, 'status' => $status]);
    }

    public function updateStatusStoreInvoice()
    {
        $store_decode = $this->postClean('store_id',TRUE);
        $status = $this->postClean('status',TRUE);
        $key = get_instance()->config->config['encryption_key']; // Key para decodificação
        $decodeJWT = $this->jwt->decode($store_decode, $key, array('HS256'));

        // erros na decodificação
        if (is_string($decodeJWT)) {
            echo json_encode(array(false, "erro interno: {$decodeJWT}"));
            exit();
        }

        // order_id
        $store_id = (int)$decodeJWT->store_id;

        $update = $this->model_stores->updateStoreInvoice(array('active' => $status), $store_id);

        if ($update) {
            echo json_encode(array(true, $this->lang->line('messages_successfully_updated')));
        } else {
            echo json_encode(array(false, "Erro para atualizar!"));
        }
    } 

    public function webhookintegration(int $storeId = 0)
    {
        $storeId = empty($storeId) ? $this->input->get('store') : $storeId;

        $stores = $this->model_stores->getActiveStore();
        $system = $this->postClean('integration',TRUE);

        $useCatalogIntegrationERP = false;
        $settingUseCatalogERP = $this->model_settings->getSettingDatabyName('use_catalog_integration_erp');
        if ($settingUseCatalogERP && $settingUseCatalogERP['status'] == 1) {
            $useCatalogIntegrationERP = true;
        }

        $this->data['useCatalogIntegrationERP'] = $useCatalogIntegrationERP;

        $this->form_validation->set_rules('integration', $this->lang->line('application_integration'), 'trim|required');
        $this->form_validation->set_rules('store', $this->lang->line('application_store'), 'trim|required');

        $this->form_validation->set_rules('use_logistics_equals_integration', '', 'trim');
        $this->form_validation->set_rules('update_order_to_in_transit_and_delivery', '', 'trim');
        $this->form_validation->set_rules('quote_via_integration', '', 'trim');
        $this->form_validation->set_rules('url_quote_via_integration', '', 'trim');

        switch ($system) {
            case 'bling':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('store_code', $this->lang->line('application_store_code'), 'trim');
                break;
            case 'pluggto':
                $this->form_validation->set_rules('user_id', 'user_id', 'trim|required');
                break;
            case 'bseller':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('url_bseller', 'url_bseller', 'trim|required');
                $this->form_validation->set_rules('interface', 'Interface', 'trim|required');
                break;
            case 'eccosys':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('url_eccosys', 'url_eccosys', 'trim|required');
                break;
            case 'tiny':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                break;
            case 'vtex':
                $this->form_validation->set_rules('token_vtex', 'X-VTEX-API-AppToken', 'trim|required');
                $this->form_validation->set_rules('appkey_vtex', 'X-VTEX-API-AppKey', 'trim|required');
                $this->form_validation->set_rules('account_name_vtex', 'accountName', 'trim|required');
                $this->form_validation->set_rules('sales_channel_vtex', 'salesChannel', 'trim|required');
                $this->form_validation->set_rules('affiliate_id_vtex', 'affiliateId', 'trim|required');
                break;
            case 'jn2':
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('url_jn2', 'url_jn2', 'trim|required');
                break;
            case 'lojaintegrada':
                $this->form_validation->set_rules('chave_api', 'chave_api', 'trim|required');
                break;
            case 'viavarejo_b2b':
                $this->form_validation->set_rules('partnerId', 'ID Parceiro', 'trim|required');
                $this->form_validation->set_rules('token', 'Token', 'trim|required');
                $this->form_validation->set_rules('campaign_pontofrio', $this->lang->line('application_campaigns') . ' Ponto Frio', 'trim|required');
                $this->form_validation->set_rules('campaign_extra', $this->lang->line('application_campaigns') . ' Entra', 'trim|required');
                $this->form_validation->set_rules('campaign_casasbahia', $this->lang->line('application_campaigns') . ' Casas Bahia', 'trim|required');
                $this->form_validation->set_rules('cnpj_pontofrio', $this->lang->line('application_cnpj') . ' Ponto Frio', 'trim|required');
                $this->form_validation->set_rules('cnpj_extra', $this->lang->line('application_cnpj') . ' Entra', 'trim|required');
                $this->form_validation->set_rules('cnpj_casasbahia', $this->lang->line('application_cnpj') . ' Casas Bahia', 'trim|required');
                $this->form_validation->set_rules('related_store_pontofrio', $this->lang->line('application_store') . ' Ponto Frio', 'trim|required');
                $this->form_validation->set_rules('related_store_extra', $this->lang->line('application_store') . ' Extra', 'trim|required');
                $this->form_validation->set_rules('related_store_casasbahia', $this->lang->line('application_store') . ' Casas Bahia', 'trim|required');
                break;
            case 'hub2b':
                $this->form_validation->set_rules('idTenant', 'ID Loja', 'trim|required');
                $this->form_validation->set_rules('authToken', 'Auth Token (API V1)', 'trim|required');
                $this->form_validation->set_rules('username', 'Username', 'trim|required');
                $this->form_validation->set_rules('password', 'Password', 'trim|required');
                break;
        }

        $integrations_logistic = $this->integrationLogistics->getAllIntegration();
        $this->data['integration_logistic_by_name'] = array();
        foreach ($integrations_logistic as $integration_logistic) {
            $this->data['integration_logistic_by_name'][$integration_logistic['name']] = $integration_logistic['description'];
        }
        $this->data['integration_logistic_by_name'] = json_encode($this->data['integration_logistic_by_name'], JSON_UNESCAPED_UNICODE);

        if ($this->form_validation->run() == TRUE) {
            $store = $this->postClean('store');

            $existStore = false;

            foreach ($stores as $_store) {
                // verifica se o código da loja realmente pertence a empresa
                if ($_store['id'] == $store) {
                    $existStore = true;
                }
            }

            if (!$existStore) {
                $this->session->set_flashdata('error', "Erro: " . $this->lang->line('application_stores'));
                redirect('stores/webhookintegration/', 'refresh');
            }

            $use_logistics_equals_integration           = $this->postClean('use_logistics_equals_integration');
            $update_order_to_in_transit_and_delivery    = $this->postClean('update_order_to_in_transit_and_delivery');
            $quote_via_integration                      = $this->postClean('quote_via_integration');
            $url_quote_via_integration                  = $this->postClean('url_quote_via_integration');

            if ($use_logistics_equals_integration || $update_order_to_in_transit_and_delivery || $quote_via_integration) {
                try {
                    $integration_actual = $this->integrationLogisticConfigurations->getIntegrationSeller($store);
                    $is_update = (bool)$integration_actual;

                    // loja já tem integração, então não vamos trocar.
                    if (
                        $integration_actual &&
                        $integration_actual['integration'] != 'precode' && // se for precode, a loja tem autonomia.
                        $integration_actual['integration'] != $system // se for o erp integrador, a loja tem autonomia
                    ) {
                        $data_logistics = $this->integrationLogistics->getIntegrationsByName($integration_actual['integration']);
                        $this->session->set_flashdata('error', "Você possui a integração com {$data_logistics['description']} ativa, solicite a exclusão da integração para o administrador para que você possa configurar a integração com a sua integração atual.");
                        redirect("stores/webhookintegration/$store", 'refresh');
                    }

                    // Usará a integração 'precode', mas sem endpoint. Quando '$update_order_to_in_transit_and_delivery', for verdadeiro.
                    $id_logistic_precode = $this->integrationLogistics->getIntegrationsByName('precode');
                    $arrCreateIntegrationLogistic = array(
                        'id_integration' => $id_logistic_precode['id'] ?? null,
                        'integration' => $id_logistic_precode['name'],
                        'credentials' => '{}',
                        'store_id' => $store,
                        $is_update ? 'user_created' : 'user_updated' => $this->session->userdata('id')
                    );

                    // Usará a logística da integradora.
                    if ($use_logistics_equals_integration) {
                        if (!array_key_exists($system, json_decode($this->data['integration_logistic_by_name'], true))) {
                            $this->session->set_flashdata('error', "A integradora selecionada, $system, não tem integração logística.");
                            redirect("stores/webhookintegration/$store", 'refresh');
                        }
                        $id_logistic_integration = $this->integrationLogistics->getIntegrationsByName($system);

                        $arrCreateIntegrationLogistic['id_integration'] = $id_logistic_integration['id'] ?? null;
                        $arrCreateIntegrationLogistic['integration'] = $id_logistic_integration['name'];

                    } // Usará a integração 'precode', com endpoint.
                    elseif ($quote_via_integration) {
                        $arrCreateIntegrationLogistic['credentials'] = json_encode(array('endpoint' => $url_quote_via_integration), JSON_UNESCAPED_UNICODE);
                    }

                    $this->integrationLogisticService->saveIntegrationLogisticConfigure(array_merge($arrCreateIntegrationLogistic, [
                            'id' => $integration_actual['id'] ?? null,
                            'active' => true
                        ])
                    );
                } catch (Throwable $e) {
                    $this->session->set_flashdata('error', implode('<br>', $e->getMessage()));
                    redirect("stores/webhookintegration/$store", 'refresh');
                }
            }

            $arrIntegration = array();

            $dataIntegrationStore = $this->model_stores->getDataApiIntegration($store);
            $arrInsert['status'] = 1;
            if ($dataIntegrationStore['store_id']) {
                $arrInsert = $dataIntegrationStore;
                unset($arrInsert['token_callback']);
                unset($arrInsert['id']);
            } else {
                $this->model_integrations->removeRowsOrderToIntegration($store);
            }

            $integrationDescription = '';
            switch ($system) {
                case 'bling':
                    $arrIntegration['apikey_bling'] = trim($this->postClean('token'));
                    $arrIntegration['loja_bling'] = trim($this->postClean('store_code'));
                    $arrIntegration['stock_bling'] = trim($this->postClean('stock_bling'));
                    break;
                case 'pluggto':
                    $arrIntegration['client_id_pluggto'] = $this->postClean('client_id_pluggto');
                    $arrIntegration['client_secret_pluggto'] = $this->postClean('client_secret_pluggto');
                    $arrIntegration['username_pluggto'] = $this->postClean('username_pluggto');
                    $arrIntegration['password_pluggto'] = $this->postClean('password_pluggto');
                    $arrIntegration['user_id'] = $this->postClean('user_id');
                    break;
                case 'eccosys':
                    $arrIntegration['token_eccosys'] = $this->postClean('token');
                    $arrIntegration['url_eccosys']   = $this->postClean('url_eccosys');
                    break;
                case 'bseller':
                    $arrIntegration['token_bseller'] = trim($this->postClean('token'));
                    $arrIntegration['url_bseller']   = trim($this->postClean('url_bseller'));
                    $arrIntegration['interface']     = trim($this->postClean('interface'));
                    break;
                case 'tiny':
                    $arrIntegration['token_tiny'] = $this->postClean('token');
                    $arrIntegration['endpoint_quote'] = $this->postClean('endpoint_quote');
                    $arrIntegration['lista_tiny'] = $this->postClean('price_list');
                    $arrIntegration['id_lista_tiny'] = "";
                    $arrIntegration['stock_tiny'] = trim($this->postClean('stock_tiny'));
                    break;
                case 'vtex':
                    $arrIntegration['token_vtex'] = $this->postClean('token_vtex');
                    $arrIntegration['appkey_vtex'] = $this->postClean('appkey_vtex');
                    $arrIntegration['account_name_vtex'] = $this->postClean('account_name_vtex');
                    $arrIntegration['environment_vtex'] = $this->postClean('environment_vtex') ?? 'vtexcommercestable';
                    $arrIntegration['sales_channel_vtex'] = $this->postClean('sales_channel_vtex');
                    $arrIntegration['affiliate_id_vtex'] = $this->postClean('affiliate_id_vtex');
                    break;
                case 'jn2':
                    $arrIntegration['token_jn2'] = $this->postClean('token');
                    $arrIntegration['url_jn2']   = $this->postClean('url_jn2');
                case 'lojaintegrada':
                    $arrIntegration['chave_api'] = $this->postClean('chave_api');
                    break;
                case 'viavarejo_b2b':
                    $arrIntegration['token_b2b_via'] = $this->postClean('token');
                    $integrationDescription = 'Via Varejo B2B';
                    break;
                case 'tray':
                case 'hub2b':
                case 'ideris':
                    $arrIntegration = $this->loadFormCredentials();
                    $integrationTrayStore = $this->model_api_integrations->getIntegrationByStoreId($store);
                    if (isset($integrationTrayStore['credentials'])) {
                        $credentialsDecode = json_decode($integrationTrayStore['credentials'], true);
                        if ($system == 'tray') {
                            $arrIntegration = array_merge($arrIntegration, $credentialsDecode);
                            $arrIntegration = array_merge($arrIntegration, ['storeUrl' => $arrIntegration['storeUrl']]);
                            $integrationDescription = 'Tray';
                        } elseif ($system == 'hub2b') {
                            $arrIntegration = array_merge($arrIntegration, $credentialsDecode, [
                                'idTenant' => $arrIntegration['idTenant'],
                                'authToken' => $arrIntegration['authToken'],
                                'username' => $arrIntegration['username'],
                                'password' => $arrIntegration['password'],
                            ]);
                            $integrationDescription = 'Hub2b';
                        } elseif ($system == 'ideris') {
                            $arrIntegration = array_merge($arrIntegration, $credentialsDecode, [
                                'authToken' => $arrIntegration['authToken'],
                            ]);
                            $integrationDescription = 'Ideris';
                        }
                    }
                    break;
                default:
                    $arrIntegration = $this->loadFormCredentials();
            }

            // limpo o array para não pegar o ID
            if ($system === 'viavarejo_b2b') {
                $arrInsert = array();
            }

            $arrInsert['store_id'] = $store;
            $arrInsert['user_id'] = $this->session->userdata('id');
            $arrInsert['credentials'] = json_encode($arrIntegration);
            $arrInsert['integration'] = $system;
            if (!empty($integrationDescription)) {
                $arrInsert['description_integration'] = $integrationDescription;
            }

            if (!empty($arrInsert['integration'] ?? '')) {
                $idIntegrationErp = $this->model_integration_erps->find(['name' => $arrInsert['integration']])['id'] ?? null;
                $arrInsert['integration_erp_id'] = $idIntegrationErp;
            }
            if ($system === 'viavarejo_b2b') {
                $this->db->trans_begin();
                $integrationsVia = [
                    FlagMapper::FLAG_PONTOFRIO,
                    FlagMapper::FLAG_EXTRA,
                    FlagMapper::FLAG_CASASBAHIA,
                ];
                $checkStoreVia = array();
                $checkCampaignVia = array();
                foreach ($integrationsVia as $integrationVia) {
                    $storeVia = $this->postClean("related_store_$integrationVia");
                    $campaignVia = $this->postClean("campaign_$integrationVia", true);
                    $cnpjVia = $this->postClean("cnpj_$integrationVia", true);
                    $arrIntegration['partnerId'] = $this->postClean("partnerId", true);
                    $arrIntegration['campaign'] = $campaignVia;
                    $arrIntegration['cnpj'] = $cnpjVia;
                    $arrIntegration['flag'] = $integrationVia;
                    $arrIntegration['flagId'] = FlagMapper::getFlagIdByName($integrationVia);
                    $arrIntegration['name'] = FlagMapper::ENABLED_FLAGS[$integrationVia];
                    $arrIntegration['bandeira'] = $integrationVia;
                    $arrIntegration['idLojista'] = $arrIntegration['flagId'];
                    if (isset($dataIntegrationStore['credentials'])) {
                        $credentialsDecode = json_decode($dataIntegrationStore['credentials'], true);
                        $arrIntegration['webhookAuthToken'] = $credentialsDecode['webhookAuthToken'] ?? null;
                    }
                    $integrationViaStore = $this->model_api_integrations->getIntegrationByStoreId($storeVia);
                    if (isset($integrationViaStore['credentials'])) {
                        $credentialsDecode = json_decode($integrationViaStore['credentials'], true);
                        $arrIntegration = array_merge($credentialsDecode, $arrIntegration);
                    }

                    $arrIntegration['webhookAuthToken'] = $arrIntegration['webhookAuthToken'] ?? md5(uniqid(rand(), true) . date('sYimsdHs'));
                    $arrIntegration['idLojista'] =
                        $integrationVia === FlagMapper::FLAG_CASASBAHIA ?
                            FlagMapper::FLAG_CASASBAHIA_ID :
                            (
                                $integrationVia === FlagMapper::FLAG_PONTOFRIO ?
                                    FlagMapper::FLAG_PONTOFRIO_ID :
                                    (
                                        $integrationVia === FlagMapper::FLAG_EXTRA ?
                                            FlagMapper::FLAG_EXTRA_ID :
                                            null
                                    )
                            );

                    if ($arrIntegration['idLojista'] === null) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', "Não localizada o código da bandeira ($integrationVia).");
                        redirect("stores/webhookintegration?store=$store", 'refresh');
                    }

                    if (in_array($storeVia, $checkStoreVia)) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Não é permitido escolher a mesma loja, para mais de um bandeira');
                        redirect("stores/webhookintegration?store=$store", 'refresh');
                    }

                    if (in_array($campaignVia, $checkCampaignVia)) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', 'Não é permitido escolher o mesmo código de campanha, para mais de um bandeira');
                        redirect("stores/webhookintegration?store=$store", 'refresh');
                    }

                    $checkCampaignVia[] = $campaignVia;
                    $checkStoreVia[] = $storeVia;

                    $arrInsert['credentials'] = json_encode($arrIntegration);
                    $arrInsert['integration'] = "viavarejo_b2b_$integrationVia";
                    $arrInsert['store_id'] = $storeVia;
                    $arrInsert['status'] = Model_api_integrations::ACTIVE_STATUS;

                    if ($dataIntegrationStore['integration']) {
                        $updateInsert = $this->model_api_integrations->updateByStore($storeVia, $arrInsert);
                    } else {
                        $updateInsert = $this->model_api_integrations->create($arrInsert);
                    }

                    if (!$updateInsert) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . $this->lang->line('messages_refresh_page_again'));
                        redirect("stores/webhookintegration?store=$store", 'refresh');
                    }
                }

                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . $this->lang->line('messages_refresh_page_again'));
                    redirect("stores/webhookintegration?store=$store", 'refresh');
                }

                $this->db->trans_commit();
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_integration_api'));
                redirect('integrations/job_integration', 'refresh');
            }

            if ($dataIntegrationStore['integration']) {
                $updateInsert = $this->model_api_integrations->updateByStore($store, $arrInsert);
            } else {
                $updateInsert = $this->model_api_integrations->create($arrInsert);
            }

            if ($updateInsert) {
                if (in_array($system, ['tray', 'hub2b', 'ideris'])) {
                    $integration = $this->model_api_integrations->getIntegrationByStoreId($store);
                    $integration = array_merge($integration, ['credentials' => json_decode($integration['credentials'])]);

                    require_once APPPATH . "libraries/Integration_v2/{$system}/Controllers/AuthApp.php";
                    $className = "\Integration_v2\\{$system}\\Controllers\AuthApp";
                    $intAppAuth = new $className(
                        $integration,
                        $this->model_api_integrations
                    );
                    if (!$intAppAuth->testIntegrationConnection($store)) {
                        $this->session->set_flashdata(
                            'error',
                            sprintf("%s %s", $this->lang->line('messages_error_occurred'), $this->lang->line('messages_integration_config_error'))
                        );
                        redirect("stores/integration?store=$store", 'refresh');
                        return;
                    }
                }
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_integration_api'));
                redirect('integrations/job_integration', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . $this->lang->line('messages_refresh_page_again'));
                redirect("stores/webhookintegration?store=$store", 'refresh');
            }
        }

        // recupera o código da loja por get ou recuperar o primeiro da listagem
        foreach ($stores as $store) {
            $storeId = $storeId == 0 ? $store['id'] : $storeId;
            break;
        }
        $existStore = false;

        foreach ($stores as $store) {
            // verifica se o código da loja realmente pertence a empresa
            if ($store['id'] == $storeId) {
                $existStore = true;
            }
        }

        if (!$existStore) {
            $this->session->set_flashdata('error', "Erro: " . $this->lang->line('application_user_without_store'));
            redirect('dashboard', 'refresh');
        }


        $getStore = $this->model_stores->getStoresData($storeId);
        if (!$getStore['token_callback']) {
            $tokenCallback = bin2hex(random_bytes(8)) . time() . bin2hex(random_bytes(8));

            if ($this->model_stores->getStoreTokenCallback($tokenCallback)) {
                redirect('stores/webhookintegration', 'refresh');
            }

            $this->model_stores->update(array('token_callback' => $tokenCallback), $storeId);
        }
        $this->chave_aplicacao = $this->model_settings->getValueIfAtiveByName('chave_aplicacao_loja_integrada');
        $this->data['chave_application_setted'] = (Boolean)$this->chave_aplicacao;
        $dataIntegrationStore = $this->model_stores->getDataApiIntegrationByStore($storeId);
        $integration = $dataIntegrationStore['integration'] ?? null;
        $storeEmail = null;
        if(isset($_GET['store']) || $storeId > 0){
            $storeId = $_GET['store'] ?? $storeId;
            $store = $this->model_stores->getStoreById($storeId);
            $storeEmail = !empty($store['responsible_email'] ?? '') ? $store['responsible_email'] : null;
            $this->data['store'] = $store;
        }
        $userEmail = $this->model_users->fetchStoreManagerUser($storeId, $store['company_id'] ?? $dataIntegrationStore['company_id'])['email'] ?? null;
        $this->data['user_email'] = $userEmail ?? $storeEmail ?? $this->session->userdata('email');
        $this->data['storesView'] = $stores;
        $this->data['storeId'] = $storeId;

        $credentials = $dataIntegrationStore && !empty($dataIntegrationStore['credentials']) ? json_decode($dataIntegrationStore['credentials']) : null;
        if (strpos($dataIntegrationStore['integration'], 'viavarejo_b2b') !== false) {
            $baseUrl = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
            $baseUrl = empty($baseUrl) ? (get_instance()->config->config['base_url'] ?? base_url()) : base_url();
            $baseUrl = strlen($baseUrl) >= strripos($baseUrl, '/') ? substr($baseUrl, 0, strripos($baseUrl, '/')) : $baseUrl;
            $credentials = (object)array_merge((array)$credentials, [
                'trackingUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Tracking",
                'partialProductUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/PartialProduct",
                'stockProductUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Stock",
                'availabilityProductUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Availability",
                'updateCategoryUrl' => "{$baseUrl}/Api/Integration_v2/viavarejo_b2b/Category",
            ]);
            $credentials->webhookAuthToken = $credentials->webhookAuthToken ?? 'Disponível após salvar as configurações...';
        }
        $this->data['credentials'] = $credentials;
        $this->data['dataIntegration'] = $dataIntegrationStore;

        //interface para o bseller
        $this->data['interface'] = (isset($dataIntegrationStore['interface'])) ? $dataIntegrationStore['interface'] : '';

        $settingNameSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
        $nameSellerCenter = $settingNameSellerCenter['value'];
        $this->data['nameSellerCenter'] = $nameSellerCenter;

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $settingSellerCtr = $settingSellerCenter['value'];
        $this->data['sellercenter'] = $settingSellerCtr;

        $this->data['erpDisabled'][$integration] = '';
        $this->data['integration'] = $integration;
        $this->data['disabledIntegration'] = (bool)$integration;

        $this->data['stores_company'] = $this->model_stores->getCompanyStores($store['company_id']);

        $this->data['integration_b2b_viavarejo'] = null;
        if (in_array($integration, array('viavarejo_b2b_pontofrio', 'viavarejo_b2b_extra', 'viavarejo_b2b_casasbahia'))) {
            foreach ($this->data['stores_company'] as $storeInt) {
                $integrationVia = $this->model_stores->getDataApiIntegrationByStore($storeInt['id']);
                if ($integrationVia && !empty($integrationVia['integration'])) {
                    $this->data['integration_b2b_viavarejo'][$integrationVia['integration']] = array('store' => $storeInt['id'], 'credentials' => json_decode($integrationVia['credentials']));
                }
            }
        }

        $this->data['integrationsErp'] = $this->model_integration_erps->getIntegrationActive();
        $this->data['integrations_backoffice'] = array();
        foreach ($this->data['integrationsErp'] as $integrationErp) {
            if ($integrationErp->type != 2) {
                $this->data['integrations_backoffice'][] = $integrationErp->name;
            }
        }
        $this->data['integrations_backoffice'] = json_encode($this->data['integrations_backoffice']);
        $this->render_template('stores/webhookintegration', $this->data);
    }

    /*
     * This function is invoked from another function to upload the image into the assets folder
     * and returns the image path
     */
    public function upload_image($id = null)
    {
        // assets/images/company_image
        $config['upload_path'] = 'assets/images/store_image';
        $config['file_name'] = (is_null($id)) ? uniqid() : $id;
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = '1500';
        $config['overwrite'] = TRUE;

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('store_image')) {
            $error = $this->upload->display_errors();
            return array('success' => false, 'msg' => $error);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['store_image']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            if ($data) {
                return array('success' => true, 'msg' => $path);
            } else {
                return array('success' => false, 'msg' => 'Erro no upload');
            }
        }
    }

    public function getStoresAjaxByCompany()
    {
        $company = $this->postClean('company',TRUE);

        $dataUser       = $this->model_users->getUserData($this->session->userdata('id'));
        $companyCurrent = $dataUser['company_id'];
        $storeCurrent   = $dataUser['store_id'];

        if ($companyCurrent != 1 && $company != $companyCurrent) {
            echo json_encode([]);
            die;
        }

        if ($companyCurrent != 1 && $storeCurrent == 0) {
            $response = $this->model_stores->getCompanyStores($company);
        } elseif ($companyCurrent != 1 && $storeCurrent != 0) {
            $response = array($this->model_stores->getStoresData($storeCurrent));
        } else {
            $response = $this->model_stores->getCompanyStores($company);
        }
        echo json_encode($response);
    }

    public function getLabelFinancialManagementSystemStatus($status): string
    {
        switch ($status) {
            case StatusFinancialManagementSystemEnum::CREATED:
                return $this->lang->line('application_store_subaccount_status_account_created');
            case StatusFinancialManagementSystemEnum::ERROR:
                return $this->lang->line('application_store_subaccount_status_account_error');
            default:
                return $this->lang->line('application_store_subaccount_status_account_not_created');
        }

    }

    public function getAlertColorSubaccountStatus($subaccountStatus): string
    {
        switch ($subaccountStatus) {
            case StoreSubaccountStatusFilterEnum::WITHOUT_PENDENCIES:
                return 'success';
            case StoreSubaccountStatusFilterEnum::WITH_PENDENCIES:
                return 'warning';
            case StoreSubaccountStatusFilterEnum::WITH_ERROR:
                return 'danger';
            default:
                return 'default';
        }

    }

    public function getAlertColorFinancialManagementSystemStatus($status): string
    {
        switch ($status) {
            case StatusFinancialManagementSystemEnum::CREATED:
                return 'success';
            case StatusFinancialManagementSystemEnum::ERROR:
                return 'danger';
            default:
                return 'default';
        }

    }

    public function getSubaccountStatus(int $paymentGatewayId, int $storeId): string
    {

        $gatewayCode = $paymentGatewayId ? $this->model_gateway->getGatewayCodeById($paymentGatewayId) : '';

        if (in_array($gatewayCode, [Model_gateway::PAGARME, Model_gateway::GETNET, Model_gateway::MOIP, Model_gateway::MAGALUPAY, Model_gateway::TUNA, Model_gateway::EXTERNO])){
            $subaccount = $this->model_gateway->getSubAccountByStoreId($storeId, $paymentGatewayId);
            if ($subaccount){
                if ($subaccount['with_pendencies']){
                    return StoreSubaccountStatusFilterEnum::WITH_PENDENCIES;
                }
                return StoreSubaccountStatusFilterEnum::WITHOUT_PENDENCIES;
            }
            if ($this->model_payment_gateway_store_logs->hasLog($storeId, $paymentGatewayId)){
                return StoreSubaccountStatusFilterEnum::WITH_ERROR;
            }
        }

        if (in_array($gatewayCode, [Model_gateway::IUGU])){
            $iuguSubAccount = $this->model_iugu->getSubAccountByStoreId($storeId);
            if ($iuguSubAccount && $iuguSubAccount['ativo'] == 12){
                return StoreSubaccountStatusFilterEnum::WITHOUT_PENDENCIES;
            }
            if ($iuguSubAccount && in_array($iuguSubAccount['ativo'], [10, 11])){
                return StoreSubaccountStatusFilterEnum::WITH_ERROR;
            }
        }

        //Por padrão se não se encontrou em nenhuma das opções acima, a subconta ainda está pendente de criação
        return StoreSubaccountStatusFilterEnum::PENDING;

    }

    public function getFinancialManagementSystemCustomerStatus(int $storeId): string
    {

        $customer = $this->model_financial_management_system_stores->getByStoreId($storeId);

        if (!$customer){
            return StatusFinancialManagementSystemEnum::PENDING;
        }

        return $customer['status'];

    }

    public function buscamensagenssubconta(): void
    {

        $logs = [];

        $inputs = $this->postClean(NULL,TRUE);
        $storeId = $inputs['storeId'];

        $paymentGatewayId = $this->model_settings->getSettingDatabyName('payment_gateway_id')['value'];

        $gatewayCode = $paymentGatewayId ? $this->model_gateway->getGatewayCodeById($paymentGatewayId) : '';

        if (in_array($gatewayCode, [Model_gateway::PAGARME, Model_gateway::GETNET, Model_gateway::MOIP, Model_gateway::MAGALUPAY, Model_gateway::TUNA, Model_gateway::EXTERNO])){

            $logs = $this->model_payment_gateway_store_logs->getLogs($storeId, $paymentGatewayId);

            if ($logs){
                foreach ($logs as &$log){
                    $log['date_insert'] = datetimeBrazil($log['date_insert']);
                    $log['status'] = $log['status'] == 'error' ? 'Erro' : 'Cadastrado';
                }
            }

        }elseif (in_array($gatewayCode, [Model_gateway::IUGU])){

            $iuguSubAccount = $this->model_iugu->getSubAccountByStoreId($storeId);

            if ($iuguSubAccount){
                $inputs = ['iugu_subconta_id' => $iuguSubAccount['id']];

                $logs = $this->model_iugu->subcontastatuslog($inputs);

                if ($logs){
                    foreach ($logs as &$log){
                        $log['date_insert'] = datetimeBrazil($log['data_log']);
                        $log['status'] = $log['retorno'] >= 200 && $log['retorno'] < 300 ? 'Cadastrado' : 'Erro';
                        $log['description'] = $log['log'];
                    }
                }
            }

        }

        echo json_encode($logs);

    }

    public function findlogsfinancialmanagementsystem(): void
    {

        $logs = [];

        $inputs = $this->postClean(NULL,TRUE);
        $storeId = $inputs['storeId'];

        $customer = $this->model_financial_management_system_stores->getByStoreId($storeId);

        if ($customer){

            $logs = $this->model_financial_management_system_store_histories->getByFinancialManagementSystemStoreId($customer['id']);

            if ($logs){

                foreach ($logs as &$history){

                    $history['created_at'] = datetimeBrazil($history['created_at']);
                    $history['job_name'] = StatusFinancialManagementSystemEnum::getName($history['job_name']);

                }

            }

        }

        echo json_encode($logs);

    }

    public function getDataCompanyToMultiCdStore(int $company_id): CI_Output
    {
        if ($this->model_settings->getStatusbyName('stores_multi_cd') != 1) {
            return $this->output->set_output(json_encode([
                'stores_company'    => array(),
                'count_stores'      => 0,
                'data_company'      => array(
                    'multi_channel_fulfillment' => 0
                ),
            ]));
        }

        $count_stores   = 0;
        $data_company   = null;
        $stores_multicd_company = null;
        if ($company_id) {
            $data_company = $this->model_company->getCompanyData($company_id);
            if ($data_company && $data_company['multi_channel_fulfillment']) {
                $stores_multicd_company = $this->model_stores->getStoresMultiCdByCompany($company_id);
                $count_stores = count($stores_multicd_company);
            }
        }
        $this->data['count_stores'] = $count_stores;
        $this->data['data_company'] = $data_company;

        return $this->output->set_output(json_encode([
            'stores_company'    => $stores_multicd_company,
            'count_stores'      => $count_stores,
            'data_company'      => $data_company,
        ]));
    }

    public function strReplaceName($name) {
        return str_replace('&amp;', '&', str_replace('&#039', "'", $name));
    }

    public function importComissionsCategory($storeId)
    {
        $this->render_template('stores/import_category_comission_rules', $this->data);
    }

    public function downloadComissionsCategory()
    {

        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-type: application/x-msexcel;");
        header("Content-Disposition: attachment; filename=comission_rules.xls");

        $result = array('data' => array());

        echo utf8_decode( "<table border=\"1\">
                <tr>
                    <th>Clifor</th>
                    <th>abc</th>
               </tr>");

        echo utf8_decode( "<tr>");
        echo utf8_decode("<td>abc</td>");
        echo utf8_decode("<td>123</td>");
        echo utf8_decode( "</tr>");


        echo "</table>";


    }

    public function getExternalNfse(int $store_id, string $reference): CI_Output
    {
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');

        if (!$external_marketplace_integration) {
            return $this->output->set_output(json_encode([
                'success'   => false,
                'message'   => $this->lang->line('application_unavailable')
            ]));
        }

        if (!$this->model_stores->checkIfTheStoreIsMine($store_id)) {
            return $this->output->set_output(json_encode([
                'success'   => false,
                'message'   => $this->lang->line('application_store_not_found')
            ]));
        }

        try {
            $this->marketplace_store->setExternalIntegration($external_marketplace_integration);
            $data = $this->marketplace_store->external_integration->getNfseStore($store_id, $reference);
        } catch (Exception | Error $exception) {
            return $this->output->set_output(json_encode([
                'success'   => false,
                'message'   => $exception->getMessage()
            ]));
        }

        return $this->output->set_output(json_encode([
            'success'   => true,
            'message'   => array_map(function($nfse){
                $nfse['total_amount'] = money($nfse['total_amount']);
                $nfse['invoice_date'] = dateFormat($nfse['invoice_date'], DATE_BRAZIL);

                return $nfse;
            }, $data)
        ]));
    }

}
