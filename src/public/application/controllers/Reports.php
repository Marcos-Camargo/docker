<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Relatórios
 
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->data['page_title'] = $this->lang->line('application_reports');
        $this->load->model('model_stores');
        $this->load->model('model_reports');
		$this->load->model('model_groups');
        $this->load->model('model_settings');
    }
    
    /*
     * It redirects to the report page
     * and based on the year, all the orders data are fetch from the database.
     * DESATIVADO - no momento não será usado
     */
    public function index()
    {
    	
        redirect('dashboard', 'refresh');
    }

    public function report($report = null)
    {
        // não passou o nome do relatorio
        if (!$report) {
            redirect('dashboard', 'refresh');
        }

        $dataReport = $this->model_reports->getReportByName($report);

        // não encontrou o relatorio
        if (!$dataReport) {
            redirect('dashboard', 'refresh');
        }

        // relatorio não está ativo
        if (!$dataReport['active']) {
            redirect('dashboard', 'refresh');
        }

        // não pode ver relatorios
        if(!in_array('viewReports', $this->permission) && !$dataReport['admin']) {
            redirect('dashboard', 'refresh');
        }

        // relatorio apenas admin pode ver
        if(!in_array('viewManagementReport', $this->permission) && $dataReport['admin']) {
            redirect('dashboard', 'refresh');
        }

        $groupIdUser    = $this->session->userdata('group_id');
        $groupsReport   = $dataReport['groups'] === null || $dataReport['groups'] === 'null' ? array($groupIdUser) : json_decode($dataReport['groups'], true);

        if (!in_array($groupIdUser, $groupsReport)) {
            redirect('dashboard', 'refresh');
        }
		
        $usercomp = $this->session->userdata('usercomp');
        $isAdmin = $usercomp == 1;
        $language = $this->input->cookie('swlanguage') == 'portuguese_br' ? 'title_br' : 'title_en' ;

        $stores = $dataReport['admin'] ? array() : array('store_id' => $this->model_stores->getStoresId());

        // Sufixo da coluna com o código do dashboard.
        switch ($this->config->item('Metabase_Url')) {
            case 'metabaseprd.conectala.com.br':
                $sufix = '';
                break;
            case 'metabase-production-rtttdnrswa-uk.a.run.app':
            case 'metabase-homologation-rtttdnrswa-uk.a.run.app':
                $sufix = '_gcp';
                break;
            case 'metabaseociprd.conectala.com.br':
            case 'metabaseocihmlg.conectala.com.br':
                $sufix = '_oci';
                break;
            default:
                $sufix = '';
        }

        $cod_dashboard = $dataReport["cod_type_prod$sufix"];

        if ($isAdmin) {
            $stores = array();
        } else {
            $cod_dashboard = $dataReport["cod_type_prod_adm_seller$sufix"];
            $stores = array("store_id" => $this->model_stores->getStoresId());
        }

        $compl_selector = $dataReport['admin'] ? '_adm' : '_seller';
        $dataReport['selector_menu'] .= $compl_selector;

        $this->data['title'] = $dataReport[$language];
        $this->data['metabase_graph'] = $this->getMetabase($dataReport['type'], (int)$cod_dashboard, $stores);
        $this->data['menuActive'] = $dataReport['selector_menu'];
        $this->data['menuMainActive'] = $dataReport['selector_main_nav_custom'] ?? ($dataReport['admin'] ? '#reportNavAdm' : '#reportNav');
        $this->data['title_admin'] = $dataReport['admin'];

        $this->render_template('reports/report_template', $this->data);
    }

    public function manageReports()
    {

        if(!in_array('admDashboard', $this->permission))
            redirect('dashboard', 'refresh');

        $this->data['page_title'] = $this->lang->line('application_manage_reports');
		$this->data['groups'] = $this->model_groups->getGroupData();
        $this->render_template('reports/index', $this->data);
    }

    public function bfSpecialReports()
    {
        if (!in_array('admDashboard', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $getSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($getSellercenter['value'] != 'conectala') {
            redirect('dashboard', 'refresh');
        }

        $this->data['title'] = $this->lang->line('application_report_bf_special'); // 'Especial BF';
        $this->data['metabase_graph'] = $this->getMetabase('dashboard', 295);
        $this->data['menuActive'] = 'navSpecialBlackFriday';
        $this->data['menuMainActive'] = '#navMainBlackFriday';
        $this->data['title_admin'] = true;

        $this->render_template('reports/report_template', $this->data);
    }

    public function blackFridayReports()
    {
        if (!in_array('admDashboard', $this->permission))
            redirect('dashboard', 'refresh');

        $sellercenter_id = 0;
        $getSellercenter= $this->model_settings->getSettingDatabyName('sellercenter');
        $sellercenter = $getSellercenter['value'];
        switch ($sellercenter) {
            case 'conectala':
                $sellercenter_id = 44;
                break;
            case 'decathlon':
                $sellercenter_id = 293;
                break;
            case 'somaplace':
                $sellercenter_id = 281;
                break;
            case 'novomundo':
                $sellercenter_id = 287;
                break;
            case 'ortobom':
                $sellercenter_id = 291;
                break;
            case 'casavideo':
                $sellercenter_id = 288;
                break;
        }

        if ($sellercenter_id == 0) {
            redirect('dashboard', 'refresh');
        }

        $this->data['title'] = $this->lang->line('application_report_black_friday'); // 'Cotação x Venda';
        $this->data['metabase_graph'] = $this->getMetabase('dashboard', $sellercenter_id);
        $this->data['menuActive'] = 'navQuoteBlackFriday';
        $this->data['menuMainActive'] = '#navMainBlackFriday';
        $this->data['title_admin'] = true;

        $this->render_template('reports/report_template', $this->data);
    }

    public function fetchReportData()
    {
        if(!in_array('admDashboard', $this->permission)) {
            echo json_encode([]);
            die;
        }
        $language = $this->input->cookie('swlanguage') == 'portuguese_br' ? 'title_br' : 'title_en' ;

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        if ($busca['value']) {
            $this->data['ordersfilter'] = " ( id like '%" . $busca['value'] . "%' 
            OR name like '%" . $busca['value'] . "%' 
            OR {$language} like '%" . $busca['value'] . "%' 
            OR type like '%" . $busca['value'] . "%' 
            OR cod_type_prod like '%" . $busca['value'] . "%' 
            OR cod_type_prod_adm_seller like '%" . $busca['value'] . "%' 
            OR cod_type_prod_gcp like '%" . $busca['value'] . "%' 
            OR cod_type_prod_adm_seller_gcp like '%" . $busca['value'] . "%' 
            OR cod_type_prod_oci like '%" . $busca['value'] . "%' 
            OR cod_type_prod_adm_seller_oci like '%" . $busca['value'] . "%' 
            OR admin like '%" . $busca['value'] . "%' 
            OR active like '%" . $busca['value'] . "%' ) ";
        }

        $this->data['orderby'] = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('id','name', $language,'type','cod_type_prod','admin','active','');

            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo == 'id') { // inverto no caso do ID
                if ($postdata['order'][0]['dir'] == "asc") {
                    $direcao = "desc";
                } else {
                    $direcao = "asc";
                }
            }
            if ($campo != "") {
                $this->data['orderby'] = " ORDER BY ".$campo." ".$direcao;
            }
        }

        $result = array();

        if (isset($this->data['ordersfilter']))
            $filtered = $this->model_reports->getReportsCount($this->data['ordersfilter']);
        else
            $filtered = $this->model_reports->getReportsCount();

        $data = $this->model_reports->getReports($ini, $length);

        foreach ($data as $key => $value) {


            $codes = array();

            if (!empty($value['cod_type_prod'])) {
                $codes[] = "<b>AWS ADMIN:<b/> $value[cod_type_prod]";
            }
            if (!empty($value['cod_type_prod_adm_seller'])) {
                $codes[] = "<b>AWS SELLER:<b/> $value[cod_type_prod_adm_seller]";
            }
            if (!empty($value['cod_type_prod_gcp'])) {
                $codes[] = "<b>GCP ADMIN:<b/> $value[cod_type_prod_gcp]";
            }
            if (!empty($value['cod_type_prod_adm_seller_gcp'])) {
                $codes[] = "<b>GCP SELLER:<b/> $value[cod_type_prod_adm_seller_gcp]";
            }
            if (!empty($value['cod_type_prod_oci'])) {
                $codes[] = "<b>OCI ADMIN:<b/> $value[cod_type_prod_oci]";
            }
            if (!empty($value['cod_type_prod_adm_seller_oci'])) {
                $codes[] = "<b>OCI SELLER:<b/> $value[cod_type_prod_adm_seller_oci]";
            }

            $result[$key] = array(
                $value['name'],
                $value[$language],
                $value['type'],
                '<ul><li>' . implode('</li><li>', $codes) . '</li></ul>',
                $value['admin'] ? "<span class='label label-info'>{$this->lang->line('application_yes')}</span>" : "<span class='label label-warning'>{$this->lang->line('application_no')}</span>",
                $value['active'] ? "<span class='label label-success'>{$this->lang->line('application_yes')}</span>" : "<span class='label label-danger'>{$this->lang->line('application_no')}</span>",
                '<button type="button" class="btn btn-primary editReport" id-report="'.$value['id'].'"><i class="fa fa-edit"></i></button>
                 <button type="button" class="btn btn-danger delReport" id-report="'.$value['id'].'" name-report="'.$value['name'].'"><i class="fa fa-trash"></i></button>'
            );
        } // /foreach
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_reports->getReportsCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        echo json_encode($output);
    }

    public function newReport()
    {
        if(!in_array('admDashboard', $this->permission)) {
            echo json_encode([]);
            die;
        }
        ob_start();

        $this->form_validation->set_rules('name_report', $this->lang->line('application_name'), "trim|required|callback_checkUniqueName[{$this->postClean('admin')}]");
        $this->form_validation->set_rules('type_report', $this->lang->line('application_type'), 'trim|required');
        $this->form_validation->set_rules('title_pt', $this->lang->line('application_title') . ' PT', 'trim|required');
        $this->form_validation->set_rules('title_en', $this->lang->line('application_title') . ' EN', 'trim|required');


        if ($this->form_validation->run() == TRUE) {

            $dataSql = array(
                'name'          => $this->postClean('name_report'),
                'type'          => $this->postClean('type_report'),
                'title_br'      => $this->postClean('title_pt'),
                'title_en'      => $this->postClean('title_en'),
                'selector_menu' => $this->postClean('name_report'),

                'cod_type_prod' => empty($this->postClean('code_prod_aws')) ? null : $this->postClean('code_prod_aws'),
                'cod_type_prod_adm_seller' => empty($this->postClean('code_prod_aws_seller')) ? null : $this->postClean('code_prod_aws_seller'),

                'cod_type_prod_gcp' => empty($this->postClean('code_prod_gcp')) ? null : $this->postClean('code_prod_gcp'),
                'cod_type_prod_adm_seller_gcp' => empty($this->postClean('code_prod_gcp_seller')) ? null : $this->postClean('code_prod_gcp_seller'),

                'cod_type_prod_oci' => empty($this->postClean('code_prod_oci')) ? null : $this->postClean('code_prod_oci'),
                'cod_type_prod_adm_seller_oci' => empty($this->postClean('code_prod_oci_seller')) ? null : $this->postClean('code_prod_oci_seller'),

//                'cod_type_test_adm_seller' => empty($this->postClean('code_test_seller')) ? null : $this->postClean('code_test_seller'),
//                'cod_type_test' => $this->postClean('code_test'),
                'admin'         => $this->postClean('admin') ? true : false,
                'active'        => $this->postClean('active') ? true : false,
                'groups' 		=> empty($this->postClean('groups')) ? null : json_encode($this->postClean('groups')),
            );


            $create = $this->model_reports->create($dataSql);

            ob_clean();
            if ($create) {
                echo json_encode(array('success' => true, 'data' => $this->lang->line('messages_successfully_created')));
                die;
            }

            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_occurred')));

        } else {

            ob_clean();
            echo json_encode(array('success' => false, 'data' => validation_errors('<li>', '</li>')));
        }
    }

    public function editReport()
    {
        if(!in_array('admDashboard', $this->permission)) {
            echo json_encode([]);
            die;
        }
        ob_start();

        $admin = $this->postClean('admin') ? 1 : 0;

        $this->form_validation->set_rules('name_report', $this->lang->line('application_name'), "trim|required|callback_checkUniqueName[{$admin}||{$this->postClean('report_id')}]");
        $this->form_validation->set_rules('type_report', $this->lang->line('application_type'), 'trim|required');
        $this->form_validation->set_rules('title_pt', $this->lang->line('application_title') . ' PT', 'trim|required');
        $this->form_validation->set_rules('title_en', $this->lang->line('application_title') . ' EN', 'trim|required');
        $this->form_validation->set_rules('report_id', $this->lang->line('application_report'), 'trim|required');


        if ($this->form_validation->run() == TRUE) {

            $dataSql = array(
                'name'          => $this->postClean('name_report'),
                'type'          => $this->postClean('type_report'),
                'title_br'      => $this->postClean('title_pt'),
                'title_en'      => $this->postClean('title_en'),
                'selector_menu' => $this->postClean('name_report'),

                'cod_type_prod' => empty($this->postClean('code_prod_aws')) ? null : $this->postClean('code_prod_aws'),
                'cod_type_prod_adm_seller' => empty($this->postClean('code_prod_aws_seller')) ? null : $this->postClean('code_prod_aws_seller'),

                'cod_type_prod_gcp' => empty($this->postClean('code_prod_gcp')) ? null : $this->postClean('code_prod_gcp'),
                'cod_type_prod_adm_seller_gcp' => empty($this->postClean('code_prod_gcp_seller')) ? null : $this->postClean('code_prod_gcp_seller'),

                'cod_type_prod_oci' => empty($this->postClean('code_prod_oci')) ? null : $this->postClean('code_prod_oci'),
                'cod_type_prod_adm_seller_oci' => empty($this->postClean('code_prod_oci_seller')) ? null : $this->postClean('code_prod_oci_seller'),

//                'cod_type_test_adm_seller' => empty($this->postClean('code_test_seller')) ? null : $this->postClean('code_test_seller'),
//                'cod_type_test' => $this->postClean('code_test'),
                'admin'         => $this->postClean('admin') ? true : false,
                'active'        => $this->postClean('active') ? true : false, 
                'groups' 		=> empty($this->postClean('groups')) ? null : json_encode($this->postClean('groups')),
            );


            $update = $this->model_reports->update($dataSql, $this->postClean('report_id'));

            ob_clean();
            if ($update) {
                echo json_encode(array('success' => true, 'data' => $this->lang->line('messages_successfully_updated')));
                die;
            }

            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_occurred')));

        } else {

            ob_clean();
            echo json_encode(array('success' => false, 'data' => validation_errors('<li>', '</li>')));
        }
    }

    public function removeReport()

    {
        if(!in_array('admDashboard', $this->permission)) {
            echo json_encode([]);
            die;
        }
        ob_start();

        $this->form_validation->set_rules('report_id', $this->lang->line('application_report'), 'trim|required');

        if ($this->form_validation->run() == TRUE) {

            $remove = $this->model_reports->remove($this->postClean('report_id'));

            ob_clean();
            if ($remove) {
                echo json_encode(array('success' => true, 'data' => $this->lang->line('messages_successfully_removed')));
                die;
            }

            echo json_encode(array('success' => false, 'data' => $this->lang->line('messages_error_occurred')));

        } else {

            ob_clean();
            echo json_encode(array('success' => false, 'data' => validation_errors('<li>', '</li>')));
        }
    }

    public function getReport($report_id)
    {
        ob_start();
        if(!in_array('admDashboard', $this->permission)) {
            echo json_encode(['success' => false,  'data' => $this->lang->line('messages_error_occurred')]);
            die;
        }

        ob_clean();
        $report = $this->model_reports->getReport($report_id);
        echo json_encode(array('success' => $report ? true : false, 'data' => $report ?? $this->lang->line('messages_error_occurred')));
    }

    public function checkUniqueName($name, $param): bool
    {
        if(!in_array('admDashboard', $this->permission))
            return false;

        $params = explode('||', $param);

        if (count($params) > 1) {
            $admin = $params[0];
            $report_id = $params[1];
        } elseif (count($params) === 1) {
            $admin = $params[0];
            $report_id = 0;
        }

        $report = $this->model_reports->getNameReportByAdmin($name, $admin, $report_id);
        if ($report) {
            $this->form_validation->set_message('checkUniqueName', '{field} já está em uso.');
            return false;
        }

        return true;
    }
}	