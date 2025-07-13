<?php
defined('BASEPATH') or exit('No direct script access allowed');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

/**
 * @property Model_integrations $model_integrations
 * @property Model_integrations_settings $model_integrations_settings
 * @property Model_settings $model_settings
 * @property Model_control_sequential_skumkts $model_control_sequential_skumkts
 *
 * @property VtexCampaigns $vtexcampaigns
 */

class IntegrationsSettings extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_integrations');
        $this->load->model('model_integrations_settings');
        $this->load->model('model_settings');
        $this->load->model('model_control_sequential_skumkts');
    }

    public function index()
    {
        $this->data['page_title'] = 'Integração Seller Center';
        if(!in_array('viewIntegrationsSettings', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('IntegrationsSettings/index', $this->data);
    }

    public function fetchData()
    {
        ob_start();
        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if (($busca['value']) && (strlen($busca['value']) >= 1)) {  // Garantir no minimo 3 letras
            $procura = " WHERE ( name like '%" . $busca['value'] . "%' OR id like '%" . $busca['value'] . "%') AND int_type = 'DIRECT' ";
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }

            $campos = array('id', 'name', 'active', '');

            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_integrations->getIntegrationsDataView($ini, $procura, $sOrder, $length);
        $filtered = $this->model_integrations->getIntegrationsDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_integrations->getIntegrationsDataCount();
        }
        $result = array();
        foreach ($data as $key => $value) {

            $buttons = '';

            $auto_approve = ($value['auto_approve'] == 0) ? $this->lang->line("application_migration_new_23_v2") : $this->lang->line("application_migration_new_23_v1");

            $status = ($value['active'] == 1) ? '<span class="label label-success">' . $this->lang->line('application_active') . '</span>' : '<span class="label label-warning">' . $this->lang->line('application_inactive') . '</span>';

            if(in_array('updateIntegrationsSettings', $this->permission)) {
                $buttons .= '<a type="button" class="btn btn-default" href="/IntegrationsSettings/update/?id='.$value['id'].'"><i class="fa fa-pencil"></i></a>';
            }
            if(in_array('deleteIntegrationsSettings', $this->permission)) {
                $buttons .= ' <a href="#" class="btn btn-default" onclick="executeExample('.$value['id'].')"><i class="fa fa-trash"></i></a>';
            }

            $result[$key] = array(
                $value['id'],
                $value['name'],
                $value['int_to'],
                $auto_approve,
                $status,
                $buttons
            );
        } // /foreach
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total_rec,
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

    public function create()
    {
        $this->data['page_title'] = 'Integração Seller Center Criar';

        if(!in_array('createIntegrationsSettings', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('IntegrationsSettings/create', $this->data);
    }

    public function update()
    {
        $tradespolicies = '';
        $this->data['page_title'] = 'Integração Seller Center Editar';

        if(!in_array('updateIntegrationsSettings', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $id = intval($this->input->get('id'));
        $this->data['integration'] = $this->model_integrations->getRegisterIntegration($id);
        $this->data['auth_data'] = (array) json_decode($this->data['integration']['auth_data']);

        $this->data['notIntegration'] = (isset($this->data['integration']['integration_id']) ?: '0' );
        if($this->data['notIntegration'] == '0'){
            $this->data['integration']['integration_id'] = $this->data['integration']['id'];
        }
        $remove         = ['[',']','"'];
        @$tradespolicies = explode(",",$this->data['integration']['tradesPolicies']);
        $this->data['integration']['tradesPolicies'] = str_replace($remove,'',$tradespolicies);
        if($this->data['integration']['mkt_type'] == 'vtex'){
            $this->data['trade_policies'] = $this->model_integrations->getTradePolicies($this->data['integration']['int_to']);
                if(empty($this->data['trade_policies'])) {
                    $this->load->library('VtexCampaigns', array('intTo' => $this->data['integration']['int_to']));
                    $this->vtexcampaigns->updateTradePolicies(false);
                }
        }
        $urlApi = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
        $this->data['integration']['logistic'] = $urlApi.'Apisccl/freight';
        $this->data['trade_policies'] = $this->model_integrations->getTradePolicies($this->data['integration']['int_to']);

        $exist_product_published = $this->model_integrations->checkIfExistProductPublished($this->data['integration']['int_to']);
        $exist_product_published_sequential = $this->model_control_sequential_skumkts->checkIfExistProductPublished($this->data['integration']['int_to']);

        $this->data['exist_product_published'] = $exist_product_published || $exist_product_published_sequential;

        $this->render_template('IntegrationsSettings/update', $this->data);
    }

    public function remove()
    {
        if(!in_array('deleteIntegrationsSettings', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $id = intval($this->input->get('id'));

        $this->data['integration'] = $this->model_integrations->RemoveRegisterIntegration($id);
        $this->session->set_flashdata('success', 'Integração removida com sucesso!');
        redirect('IntegrationsSettings/index', 'refresh');
    }

    public function verify()
    {
        if ($_SERVER['SERVER_NAME'] !== parse_url($_SERVER['HTTP_REFERER'])['host']) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output("Unauthorized");
        }

        $request = $this->input->post();

        $url = "https://" . $request['accountName'] . "." . $request['environment'] . $request['suffixdns'] . '/api/catalog_system/pvt/saleschannel/list?x=' . time();
        $queryParams  = array(
//            'form_params' => array(
//                'name'             => $request['name'],
//                'int_to'           => $request['int_to'],
//                'adlink'           => $request['adlink'],
//                'active'           => $request['active'],
//                'store_id'         => 0,
//                'company_id'       => 1,
//                'int_type'         => 'DIRECT',
//                'int_from'         => 'CONECTALA',
//            ),
            'headers' => array(
                "content-type" => "application/json",
                "x-vtex-api-appkey" => $request['api_key'],
                "x-vtex-api-apptoken" => $request['api_token']
            )
        );
        try {
            $client = new Client([
                'verify' => false
            ]);
            $response = $client->request('GET', $url, $queryParams);
            $statusCode =  $response->getStatusCode();
            $response = Utils::jsonDecode($response->getBody());

            $data = ['statusCode' => $statusCode];

            if ($statusCode == 200) {
                $data['data'] = $response;
            }

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($data));

        } catch (InvalidArgumentException | GuzzleException $exception) {
            echo $exception->getCode();
        }
    }

    public function verifyConectala()
    {

        $request = $this->input->post();

        $url = $request['api_url'] . 'Brands';
        $queryParams  = array(
            'headers' => array(
                "content-type" => "application/json",
                "x-user-email" => $request['x_user_email'],
                "x-api-key" => $request['x_api_key'],
                "x-store-key" => $request['x_store_key'],
                "accept" => "application/json;charset=UTF-8"
            )
        );
        try {
            $client = new Client([
                'verify' => false
            ]);
            $response = $client->request('GET', $url, $queryParams);
            $statusCode =  $response->getStatusCode();
            $response = Utils::jsonDecode($response->getBody());

            $data = ['statusCode' => $statusCode];

            if ($statusCode == 200) {
                $data['data'] = true;
            }
            echo $statusCode;
            return true;


        } catch (InvalidArgumentException | GuzzleException $exception) {
            //echo 200;
            echo $exception->getCode();
            return false;
            // return $this->output
            // ->set_content_type('application/json')
            // ->set_output(false);
        }
    }

    public function save()
    {
        $id = '';
        $arr  = [];
        $arr2 = [];
        $arr3 = [];
        $data = $this->input->post();

        if(isset($data['id'])){
            $id = $data['id'];
        }            

        if(empty(trim($data['int_to']))){
            $this->session->set_flashdata('error', 'o campo integração não pode ser vazio.');
            redirect('IntegrationsSettings/index', 'refresh');
        }

        if(is_numeric($data['int_to'])){
            $this->session->set_flashdata('error', 'o campo integração não pode ser apenas números.');
            redirect('IntegrationsSettings/index', 'refresh');
        }

        $int_to = trim(str_replace(' ','',ucfirst(trim($data['int_to']))));
        if (preg_match('/[^a-zA-Z0-9]/u', $int_to)) {
            $this->session->set_flashdata('error', 'o campo int_to não pode conter espaço ou caracters especiais.');
            redirect('IntegrationsSettings/index', 'refresh');
        }

        if (!empty($data['skumkt_default']) && $data['skumkt_default'] == 'sequential_id') {
            if (((int)$data['skumkt_sequential_initial_value']) <= 0) {
                $this->session->set_flashdata('error', 'O campo de Valor Inicial deve ser maior que zero.');
                redirect(empty($id) ? 'IntegrationsSettings/create' : "IntegrationsSettings/update/?id=$id", 'refresh');
            }
        }

        if(isset($data['tradesPolicies'])) {
            foreach ($data['tradesPolicies'] as $t) {

                array_push($arr, '"' . $t . '"');
                $arr2 = implode(",", $arr);
                $arr3 = [
                    "tradesPolicies" => "[" . $arr2 . "]"
                ];

            }
        }

        $auth = [];

        if ($data['mkt_type'] == "vtex") {
            $auth = [
                "accountName"         => trim($data['accountName']),
                "environment"         => trim($data['environment']),
                "X_VTEX_API_AppKey"   => trim($data['api_key']),
                "X_VTEX_API_AppToken" => trim($data['api_token']),
                "suffixDns"           => trim($data['suffixdns'])
            ];
        } elseif ($data['mkt_type'] == "conectala") {
            $auth = [
                "api_url"              => trim($data['api_url']),
                "x-user-email"         => trim($data['x-user-email']),
                "x-api-key"            => trim($data['x-api-key']),
                "x-store-key"          => trim($data['x-store-key']),
                "x-application-id"     => trim($data['x-application-id']),
                "Content-Type"         => "application/json",
                "accept"               => "application/json;charset=UTF-8"
            ];
            
            if (!str_contains($int_to, "CNL") && !$id) {
                $int_to = "CNL".$int_to;
            }
        } else {
            $this->session->set_flashdata('error', "O tipo de marketplace informado, $data[mkt_type], não parametrizado.");
            redirect(empty($id) ? 'IntegrationsSettings/create' : "IntegrationsSettings/update/?id=$id", 'refresh');
        }

        $request = [
            "name"         => trim($data['name']),
            "active"       => trim($data['active']),
            'store_id'     => 0,
            'company_id'   => 1,
            "auth_data"    => json_encode($auth),
            'int_type'     => 'DIRECT',
            'int_from'     => 'CONECTALA',
            "int_to"       => $int_to,
            "auto_approve" => isset($data['auto_approve']) ? 0 : 1,
            "mkt_type"     => $data['mkt_type'],
        ];

        $return = $this->model_integrations->createIntegration($id, $request);
        $this->log_data(__CLASS__, __FUNCTION__, "request#1\nid=$id\nrequest_received=".json_encode($data, JSON_UNESCAPED_UNICODE)."\nrequest_to_save=".json_encode($request, JSON_UNESCAPED_UNICODE));
        if ($return && $data['mkt_type'] == "vtex") {

            $integration_id = $this->db->insert_id();

            $request2 = [
                "adlink"                        => $data['adlink'],
                "auto_approve"                  => isset($data['auto_approve_two']) ? 1 : 0,
                "update_product_specifications" => isset($data['update_product_specifications']) ? 1: 0,
                "update_sku_specifications"     => isset($data['update_sku_specifications']) ? 1 : 0,
                "update_sku_vtex"               => isset($data['update_sku_vtex']) ? 1 : 0,
                "update_product_vtex"           => isset($data['update_product_vtex']) ? 1 : 0,
                "minimum_stock"                 => $data['minimum_stock'],
                "ref_id"                        => filter_var($data['ref_id'], FILTER_SANITIZE_STRING),
                // "reserve_stock"                 => $data['reserve_stock'],
                // "hasAuction"                    => isset($data['hasAuction']) ? 1 : 0,
                "integration_id"                => $integration_id,
                "update_images_specifications"  => isset($data['update_images_specifications']) ? 1 : 0
            ];

            $exist_product_published = $this->model_integrations->checkIfExistProductPublished($int_to);
            $exist_product_published_sequential = $this->model_control_sequential_skumkts->checkIfExistProductPublished($int_to);

            if (!empty($data['skumkt_default']) && !$exist_product_published && !$exist_product_published_sequential) {
                $request2['skumkt_default'] = $data['skumkt_default'];
                $request2['skumkt_sequential_initial_value'] = $data['skumkt_default'] == 'sequential_id' ? $data['skumkt_sequential_initial_value'] : null;

                if ($data['skumkt_default'] == 'sequential_id') {
                    $this->model_control_sequential_skumkts->removeEmptyRow($int_to);
                    $this->model_control_sequential_skumkts->create(array(
                        'id' => ($data['skumkt_sequential_initial_value'] - 1),
                        'int_to' => $int_to
                    ));
                }
            }

            if(isset($data['tradesPolicies'])) {
                $request2['tradesPolicies'] = $arr3['tradesPolicies'];
            }

            $return = $this->model_integrations_settings->createIntegration($id, $request2);
            $this->log_data(__CLASS__, __FUNCTION__, "request#2\nid=$id\nrequest_received=".json_encode($data, JSON_UNESCAPED_UNICODE)."\nrequest_to_save=".json_encode($request2, JSON_UNESCAPED_UNICODE));
            // EXECUTA O BACTH DE ATUALIZACAO DE TRADE POLICIES APOS SALVAR A NOVA INTEGRAÇÃO
            $this->load->library('VtexCampaigns', array('intTo' => $int_to));
            $this->vtexcampaigns->updateTradePolicies(false);
        }



        if($id){
            $this->session->set_flashdata('success', 'Integração atualizada com sucesso!');
            redirect('IntegrationsSettings/index', 'refresh');
        }else{
            if($data['mkt_type'] == "vtex"){
                // ADICIONAR JOBS
                $this->model_integrations_settings->createJob($data['int_to']);

                $this->session->set_flashdata('success', 'Integração criada com sucesso.');
                redirect('IntegrationsSettings/index', 'refresh');       
            }
            if($data['mkt_type'] == "conectala"){
                // ADICIONAR JOBS
                $this->model_integrations_settings->createJobConectala($int_to);

                $this->session->set_flashdata('success', 'Integração criada com sucesso.');
                redirect('IntegrationsSettings/index', 'refresh');       
            }
        }    
    }
    public function getVerifyIntTo(){
        $data = $this->input->post('name');
        $request = str_replace(' ', '', $data);
        $request = preg_replace('/[^a-zA-Z0-9]/u', '', $request);
        $request = strtoupper($request);
        echo json_encode($this->model_integrations->verifyIntegration($request));
    }
}
