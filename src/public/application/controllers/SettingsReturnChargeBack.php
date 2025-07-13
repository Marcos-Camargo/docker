<?php

defined('BASEPATH') or exit('No direct script access allowed');

class SettingsReturnChargeBack extends Admin_Controller
{

    public $viewFolder = 'settings_return_chargeback_rules';
    public $currentBaseRoute = 'settingsReturnChargeBack';
    public $listIndexOrder = [
        'id' => 'application_id',
        'marketplace_int_to' => 'application_marketplace',
        'created_at' => 'application_date_create',
        'user_id' => 'application_responsible',
        'active' => 'application_status',
        'action' => 'application_action'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_setting_up_return_chargeback_rules');

        $this->load->model('model_settingsreturnchargebackrules');
        $this->load->model('model_integrations');

    }


    public function index()
    {

        if (!in_array('viewSettingChargebackRule', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['route_ajax_data'] = base_url($this->currentBaseRoute . '/index_data');
        $this->data['route_save'] = base_url($this->currentBaseRoute . '/create');
        $this->data['route_update'] = base_url($this->currentBaseRoute . '/update');
        $this->data['route_get_item'] = base_url($this->currentBaseRoute . '/get_item');
        $this->data['route_delete_item'] = base_url($this->currentBaseRoute . '/delete');
        $this->data['list_index_order'] = $this->listIndexOrder;
        $this->data['marketplaces'] = $this->model_integrations->getAllDistinctIntTo();

        $this->render_template($this->viewFolder . '/index', $this->data);

    }

    public function index_data()
    {

        $postdata = $this->postClean(NULL, TRUE);

        $rows = $this->model_settingsreturnchargebackrules->listIndex($postdata);
        $recordsTotal = $this->model_settingsreturnchargebackrules->listIndex($postdata, true);

        $data = [];
        if ($rows) foreach ($rows as $row) {

            $item = [];
            $item['id'] = $row['id'];
            $item['marketplace_int_to'] = $row['marketplace_int_to'];
            $item['created_at'] = datetimeBrazil($row['created_at']);
            $item['user_id'] = $row['username'];
            $item['active'] = $row['active'] == 1 ? lang('application_active') : lang('application_inactive');
            $item['action'] = $this->generateButtonsForSetting($row);

            $data[] = $item;

        }

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $data,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    public function get_item(int $id)
    {

        $row = $this->model_settingsreturnchargebackrules->getById($id);

        $data = [];
        $data['marketplace_int_to'] = $row['marketplace_int_to'];
        $data['rule_full_refund_inside_cicle'] = $row['rule_full_refund_inside_cicle'];
        $data['rule_full_refund_outside_cicle'] = $row['rule_full_refund_outside_cicle'];
        $data['rule_partial_refund_inside_cicle'] = $row['rule_partial_refund_inside_cicle'];
        $data['rule_partial_refund_outside_cicle'] = $row['rule_partial_refund_outside_cicle'];

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($data);

    }

    protected function generateButtonsForSetting(array $row): string
    {

        $actions = '';

        if (in_array('updateSettingChargebackRule', $this->permission)) {

            $actions .= '<button type="button" class="btn btn-sm btn-default" onclick="edit(' . $row['id'] . ')" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></button>';

        } elseif (in_array('viewSettingChargebackRule', $this->permission)) {

            $actions .= '<button type="button" class="btn btn-sm btn-default" onclick="view(' . $row['id'] . ')" data-toggle="modal" data-target="#viewModal"><i class="fa fa-eye"></i></button>';

        }

        if (in_array('deleteSettingChargebackRule', $this->permission) && $row['active']) {
            $actions .= '<button type="button" class="btn btn-sm btn-default" onclick="removeFunc(' . $row['id'] . ')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
        }

        return $actions;

    }

    public function marketplace_already_exists(string $marketplaceIntTo = null)
    {
        if (!$marketplaceIntTo){
            $this->form_validation->set_message('marketplace_already_exists', lang('application_select_the_marketplace_corresponding_to_the_configuration'));
            return false;
        }
        if (!$this->model_settingsreturnchargebackrules->marketplaceAlreadyHasRule($marketplaceIntTo)) {
            return true;
        }
        $this->form_validation->set_message('marketplace_already_exists', lang('application_message_there_already_rule_registered_for_this_marketplace'));
        return false;
    }

    public function create()
    {

        if (!in_array('createSettingChargebackRule', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $inputNames = [];
        $inputNames[] = 'marketplace_int_to';
        $inputNames[] = 'rule_full_refund_inside_cicle';
        $inputNames[] = 'rule_full_refund_outside_cicle';
        $inputNames[] = 'rule_partial_refund_inside_cicle';
        $inputNames[] = 'rule_partial_refund_outside_cicle';

        $response = array();

        $this->form_validation->set_rules('marketplace_int_to', lang('application_marketplace'), 'trim|required|callback_marketplace_already_exists');
        $this->form_validation->set_rules('rule_full_refund_inside_cicle', lang('application_chargeback_rule_orders_returned_within_payment_cycle'), 'trim|required');
        $this->form_validation->set_rules('rule_full_refund_outside_cicle', lang('application_chargeback_rule__orders_returned_after_payment_cycle'), 'trim|required');
        $this->form_validation->set_rules('rule_partial_refund_inside_cicle', lang('application_chargeback_rule_orders_returned_within_payment_cycle'), 'trim|required');
        $this->form_validation->set_rules('rule_partial_refund_outside_cicle', lang('application_chargeback_rule_orders_returned_after_payment_cycle'), 'trim|required');

        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {

            $data = [];
            foreach ($inputNames as $inputName) {
                $data[$inputName] = $this->postClean($inputName, TRUE);
            }
            $data['user_id'] = $this->session->userdata['id'];

            $create = $this->model_settingsreturnchargebackrules->create($data);

            if ($create == true) {
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_created');
            } else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_create_setting');
            }

        } else {
            $response['success'] = false;
            foreach ($inputNames as $key) {
                $response['messages'][$key] = form_error($key);
            }
        }

        ob_clean();
        echo json_encode($response);

    }

    public function update(int $id)
    {

        if (!in_array('updateSettingChargebackRule', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->model_settingsreturnchargebackrules->disableItemById($id);

        $inputNames = [];
        $inputNames[] = 'marketplace_int_to';
        $inputNames[] = 'rule_full_refund_inside_cicle';
        $inputNames[] = 'rule_full_refund_outside_cicle';
        $inputNames[] = 'rule_partial_refund_inside_cicle';
        $inputNames[] = 'rule_partial_refund_outside_cicle';

        $response = array();

        $this->form_validation->set_rules('marketplace_int_to', lang('application_marketplace'), 'trim|required|callback_marketplace_already_exists');
        $this->form_validation->set_rules('rule_full_refund_inside_cicle', lang('application_chargeback_rule_orders_returned_within_payment_cycle'), 'trim|required');
        $this->form_validation->set_rules('rule_full_refund_outside_cicle', lang('application_chargeback_rule__orders_returned_after_payment_cycle'), 'trim|required');
        $this->form_validation->set_rules('rule_partial_refund_inside_cicle', lang('application_chargeback_rule_orders_returned_within_payment_cycle'), 'trim|required');
        $this->form_validation->set_rules('rule_partial_refund_outside_cicle', lang('application_chargeback_rule_orders_returned_after_payment_cycle'), 'trim|required');

        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {

            $data = [];
            foreach ($inputNames as $inputName) {
                $data[$inputName] = $this->postClean($inputName, TRUE);
            }
            $data['user_id'] = $this->session->userdata['id'];

            $create = $this->model_settingsreturnchargebackrules->create($data);

            if ($create == true) {
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_created');
            } else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_create_setting');
            }

        } else {
            $response['success'] = false;
            foreach ($inputNames as $key) {
                $response['messages'][$key] = form_error($key);
            }
        }

        ob_clean();
        echo json_encode($response);

    }

    public function delete()
    {
        $id = $this->postClean('id');
        $retorno['success'] = false;

        if ($id) {

            $response = $this->model_settingsreturnchargebackrules->disableItemById($id);
            $retorno['success'] = true;
            if ($response) {
                $retorno['messages'] = lang('application_successfull_deleted');
            } else {
                $retorno['messages'] = lang('application_error_on_delete');
            }
        }

        echo json_encode($retorno);

    }

}
