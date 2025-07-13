<?php


defined('BASEPATH') or exit('No direct script access allowed');

class Phases extends Admin_Controller
{
    const PHASE_IMPORT_ROUTE = 'phases/import';
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        $this->load->model('model_stores');
        $this->load->library('Validations/ValidationPhase', null, 'validation_phase');
        $this->load->model('model_blacklist_words');
        $this->load->model('model_whitelist');
        $this->load->model('model_phases');
        $this->load->model('model_csv_to_verifications_phases');
        $this->load->model('model_users');
    }
    public function _remap($method, $params = array())
    {
        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }
       
        
        if ($params == ['create'] && $method === 'managePhases') {
            $this->create();
            return true;
        }
        if ($params == ['update'] && $method === 'managePhases') {
            $this->updatePhase();
            return true;
        }
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $params);
        }
        show_404();
    }
    public function index()
    {
        if (!in_array('viewPhases', $this->permission)) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect('dashboard', 'refresh');
        }
        $this->data['phases'] = $this->model_phases->getAll();
        $this->data['users'] = $this->model_users->getNameAndIdActiveUsersForPhases();
        $this->data['page_title'] = $this->lang->line('application_stages');
        $this->data['stores_filter'] = $this->model_stores->getActiveStore();
        $this->data['page_now'] = 'phases';
        $this->render_template('phases/index', $this->data);
    }
    public function update()
    {
        if (!in_array('updatePhases', $this->permission)) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect('phases', 'refresh');
        }
        $data = $this->postClean(NULL,TRUE);
        $data['goal_month'] = floatval(str_replace(',', '.', str_replace('.', '', $data['goal_month'])));
        $store = $this->model_stores->getStoresData($data['store_id']);

        $this->model_blacklist_words->updateByPhase($store['phase_id'],['new_or_update'=>$this->model_blacklist_words->getNewOrUpdatedBlockingRulesConst()]);
        $this->model_whitelist->updateByPhase($store['phase_id'],['new_or_update'=>$this->model_whitelist->getNewOrUpdatedBlockingRulesConst()]);

        $this->log_data("Phase_Store", "Update store_" . $data['store_id'], json_encode($data, JSON_UNESCAPED_UNICODE), "I");
        $this->model_stores->update(['phase_id' => $data['phase_id'], 'goal_month' => $data['goal_month']], $store['id']);
        
        $this->model_blacklist_words->updateByPhase($data['phase_id'],['new_or_update'=>$this->model_blacklist_words->getNewOrUpdatedBlockingRulesConst()]);
        $this->model_whitelist->updateByPhase($data['phase_id'],['new_or_update'=>$this->model_whitelist->getNewOrUpdatedBlockingRulesConst()]);

        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        
        redirect('phases', 'refresh');
    }
    public function getphases()
    {
        $phases = $this->model_phases->getAll();
        ob_clean();
        echo json_encode($phases);
    }
    public function fetchPhasesStoresData()
    {
        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];
        $store_in = $this->postClean('store');
        $phase_like = $this->postClean('phases');
        $responsable_like =
            $this->postClean('responsable');
        $where = ['s.active' => 1];
        $order_stage = $postdata['order'][0];
        switch ($order_stage['column']) {
            case 0:
                $orderby = 's.id';
                break;
            case 1:
                $orderby = 's.name';
                break;
            case 2:
                $orderby = 'phases.name';
                break;
            case 3:
                $orderby = 'user_name';
                break;
            case 4:
                $orderby = 'goal_month';
                break;
            default:
                $orderby = 's.id';
                break;
        }
        $direction = $order_stage['dir'];
        $stores = $this->model_stores->getAllStoresFromStage($where, $length, $orderby, $direction, $store_in, $phase_like, $responsable_like, $busca['value']);
        $recordsTotal = $this->model_stores->countTotalStoresActive();
        $recordsFiltered = $this->model_stores->countAllStoresFromStage($where, $length, $orderby, $direction, $store_in, $phase_like, $responsable_like, $busca['value']);
        $results = [];
        foreach ($stores as $store) {
            $result = [];
            $result[] = $store['id'];
            $result[] = $store['name'];
            $result[] = $store['stage'];
            $result[] = $store['user_name'];
            $result[] = "R$: " . number_format($store['goal_month'], 2, ',', '.');
            $button = '';
            if (in_array('updateStore', $this->permission)) {
                $button .= '<button type="button" class="btn btn-default" onclick="editPhase(' . $store['id'] . ',' . $store['phase_id'] . ',' .  $store['goal_month']  . ')" data-toggle="modal" data-target="#editGoalAndPhaseModal"><i class="fa fa-pencil"></i></button>';
            }
            if (in_array('updateStore', $this->permission) || in_array('viewStore', $this->permission)) {
                $button .= '<a target="__blank" href="' . base_url('stores/update/' . $store['id']) . '" class="btn btn-default"><i class="fa fa-eye" aria-hidden="true"></i></a>';
            }
            $result[] = $button;
            $results[] = $result;
        }
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $results,
        );
        ob_clean();
        echo json_encode($output);
    }
    public function fetchPhasesData()
    {
        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];
        $phase_like = $this->postClean('phases');
        $status_in = $this->postClean('status');
        if (empty($status_in)) {
            $status_in = '';
        }
        $responsable_like = $this->postClean('responsable');
        $where = [];
        $order_stage = $postdata['order'][0];
        switch ($order_stage['column']) {
            case 0:
                $orderby = 'p.id';
                break;
            case 1:
                $orderby = 'p.name';
                break;
            case 2:
                $orderby = 'user_name';
                break;
            case 3:
                $orderby = 'p.status';
                break;
            default:
                $orderby = 'p.id';
                break;
        }
        $direction = $order_stage['dir'];
        $phases = $this->model_phases->getAllWithReposable($where, $length, $orderby, $direction,  $phase_like, $responsable_like, $status_in, $busca['value'], $ini);
        $recordsTotal = $this->model_phases->countAll();
        $recordsFiltered = $this->model_phases->countFromWhere($where, $phase_like, $responsable_like, $status_in, $busca['value']);
        $results = [];
        foreach ($phases as $phase) {
            $result = [];
            $result[] = $phase['id'];
            $result[] = $phase['name'];
            $result[] = $phase['user_name'];
            if ($phase['status'] == "1") {
                $status =  '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $status =  '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }
            $result[] = $status;
            $button = '';
            if (in_array('updatePhases', $this->permission)) {
                $button .= '<button type="button" class="btn btn-default" onclick="editPhase(' . $phase['id'] . ",'" . $phase['name'] . "'," . $phase['responsable_id'] . "," . $phase['status'] . ')" data-toggle="modal" data-target="#editPhaseModal"><i class="fa fa-pencil"></i></button>';
            }
            $result[] = $button;
            $results[] = $result;
        }
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $results,
        );
        ob_clean();
        echo json_encode($output);
    }
    public function managePhases()
    {
        if (!in_array('viewPhases', $this->permission)) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect('dashboard', 'refresh');
        }
        $this->data['phases'] = $this->model_phases->getAll();
        $this->data['users'] = $this->model_users->getNameAndIdActiveUsersForPhases();
        $this->data['users_all'] = $this->model_users->getNameAndIdActiveUsers();
        $this->data['page_title'] = $this->lang->line('application_stages');
        $this->data['page_now'] = 'phases';
        $this->render_template('phases/manage', $this->data);
    }
    public function create()
    {
        if (!in_array('createPhases', $this->permission)) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect('dashboard', 'refresh');
        }
        $data = $this->postClean(NULL,TRUE);
        $phase_data = [
            'name' => $data['phase_name'],
            'responsable_id' => $data['phase_responsable_id']
        ];
        $config = $this->validation_phase->getConfig();
        $this->form_validation->set_rules($config);
        $this->form_validation->set_data($phase_data);
        if ($this->form_validation->run()) {
            $this->log_data("Phase", "CreatePhase", json_encode($phase_data, JSON_UNESCAPED_UNICODE), "I");
            $insert = $this->model_phases->create($phase_data);
            if (!$insert) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            }
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            redirect("phases/managePhases");
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . '<br><li>' . implode("</li><li>", $this->form_validation->error_array()) . '</li>');
            redirect("phases/managePhases");
        }
    }
    public function updatePhase()
    {
        if (!in_array('updatePhases', $this->permission)) {
            redirect('phases/managePhases', 'refresh');
        }
        $data = $this->postClean(NULL,TRUE);
        $phase_data = [
            'status' => $data['status'],
            'responsable_id' => $data['phase_responsable_id']
        ];
        $config = $this->validation_phase->getConfig(false);
        $this->form_validation->set_rules($config);
        $this->form_validation->set_data($phase_data);
        if ($phase_data['status'] === '2') {
            $existsStore = $this->model_stores->existsStoreToThisPhaseId($data['phase_id']);
            if ($existsStore) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred_phase_with_store'));
                redirect("phases/managePhases");
                return;
            }
        }
        if ($this->form_validation->run()) {
            $insert = $this->model_phases->update($data['phase_id'], $phase_data);
            $this->log_data("Phase", "UpdatePhas_" . $data['phase_id'], json_encode($phase_data, JSON_UNESCAPED_UNICODE), "I");
            if (!$insert) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            }
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            redirect("phases/managePhases");
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . '<br><li>' . implode("</li><li>", $this->form_validation->error_array()) . '</li>');
            redirect("phases/managePhases");
        }
    }
    public function import()
    {
        if (!in_array('viewPhases', $this->permission)) {
            redirect('dashboard', 'refresh');
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
        }
        $this->data['phases'] = $this->model_phases->getAll();
        $this->data['users'] = $this->model_users->getNameAndIdActiveUsers();
        $this->data['page_title'] = $this->lang->line('application_stages');;
        $this->data['page_now'] = 'phases';
        $this->render_template('phases/new_load', $this->data);
    }
    function get_file_on_upload()
    {
        if (!$this->postClean('validate_file', TRUE)) {
            $dirPathTemp = "assets/images/phases_upload/";
            if (!is_dir($dirPathTemp)) {
                mkdir($dirPathTemp);
            }
            $upload_file = $this->upload_file();
        } else {
            $upload_file = $this->postClean('validate_file', TRUE);
        }
        return $upload_file;
    }
    function check_for_err_on_upload_file($upload_file)
    {
        if (!$upload_file) {
            $this->session->set_flashdata('error', $this->data['upload_msg']);
            redirect(self::PHASE_IMPORT_ROUTE, 'refresh');
        }
    }
    public function only_verify()
    {
        $upload_file = $this->get_file_on_upload();
        if (!$upload_file) {
            $this->session->set_flashdata('error', $this->lang->line('messages_imported_queue_error'));
            redirect(self::PHASE_IMPORT_ROUTE, 'refresh');
        }
        $user = $this->model_users->getUserById($this->session->userdata['id']);
        $new_csv_data = [
            'upload_file' => $upload_file,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'user_email' => $user['email'],
            'usercomp' => $user['company_id'],
        ];
        $is_create = $this->model_csv_to_verifications_phases->create($new_csv_data);
        if ($is_create) {
            $this->session->set_flashdata('success', $this->lang->line('messages_imported_queue_sucess'));
            redirect(self::PHASE_IMPORT_ROUTE, 'refresh');
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_imported_queue_error'));
            redirect(self::PHASE_IMPORT_ROUTE, 'refresh');
        }
    }

    public function upload_file()
    {
        $config['upload_path'] = 'assets/images/phases_upload';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'csv|txt';
        $config['max_size'] = '100000';
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('phase_upload')) {
            $error = $this->upload->display_errors();
            $this->data['upload_msg'] = $this->lang->line('messages_invalid_file');
            $this->data['upload_msg'] = $error;
            return false;
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['phase_upload']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return ($data) ? $path : false;
        }
    }
}
