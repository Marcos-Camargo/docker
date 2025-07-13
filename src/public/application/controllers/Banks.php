<?php
/*
SW Serviços de Informática 2019

Controller de Atributos

 */
defined('BASEPATH') or exit('No direct script access allowed');

class Banks extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        $this->load->model('model_attributes');
    }
    public function index()
    {
        if (
            !in_array('createBank', $this->permission) &&
            !in_array('updateBank', $this->permission) &&
            !in_array('viewBank', $this->permission)
        ) {
            redirect('dashboard', 'refresh');
        }
        $this->data['page_title'] = $this->lang->line('application_Banks');
        $this->data['updateBank'] = in_array('updateBank', $this->permission);

        // application_bank
        $this->render_template('banks/index', $this->data);
    }
    //http://conectala.local/banks/fetchData
    public function fetchData()
    {
        $postdata = $this->postClean(NULL, TRUE);
        // dd($postdata, 123);
        $draw = $postdata['draw'];
        $order = $postdata['order'][0];
        try {
            $search = $postdata['search']['value'];
            if (strlen($search) < 3) {
                $search = '';
            }
        } catch (Exception $e) {
            $search = '';
        }
        switch ($order['column']) {
            case 0:
                $order['column'] = 'number';
                break;
            case 1:
                $order['column'] = 'name';
                break;
            case 2:
                $order['column'] = 'active';
                break;
        }
        $start = $postdata['start'];
        if (!$start){
            $start = 0;
        }
        $length = $postdata['length'];
        $filtered = 1;
        $result = [
            'data' => array(), "draw" => $draw,
            "recordsTotal" => $this->model_banks->getCount(),
        ];
        $where = [];
        $banks = $this->model_banks->getAllBanksData($length, $start, $order, $where, $search);
        $result["recordsFiltered"] = $this->model_banks->getAllBanksDataCount($length, $start, $order, $where, $search);;
        foreach ($banks as $key => $bank) {
            $data = [];
            $data[] = '<button class="btn btn-link" data-toggle="modal" data-id="ISBN-001122" data-target="#addModal" onclick="openModal(' . $bank['id'] . ')">' . $bank['number'] . '</button>';
            $data[] = htmlspecialchars ($bank['name']);
            $data[] = $bank['active'] == 1 ? '<span class="label label-success">' . $this->lang->line('application_active') . '</span>' : '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            $result['data'][] = $data;
        }
        // dd($this->db->select('*')->from(Model_banks::TABLE)->where($where)->count_all_results());
        ob_clean();
        echo json_encode($result);
    }
    public function bank_info($id)
    {
        $bank = $this->model_banks->getBankById($id);
        echo json_encode($bank);
        // return '{"id":"5","number":"001","name":"Banco do Brasil","mask":null,"conta":null,"active":"1"}';
    }
    public function create()
    {
        if (!in_array('createBank', $this->permission)) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect('banks', 'refresh');
        } else {
            $data = $this->postClean(NULL, TRUE);
            $data['mask_agency'] = $this->bankMaskDefine($data['mask_agency']);
            if (!$this->model_banks->existBankWithThisNumber($data['number'])) {
                $bank = [
                    'number' => $data['number'],
                    'name' => $data['name'],
                    'active' => $data['active'],
                    'mask_agency'=> $data['mask_agency'],
                    'mask_account'=> $data['mask_account']
                ];
                $this->model_banks->create($bank);
                redirect('banks', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_bank_exist_with_this_number'));
                redirect('banks', 'refresh');
            }
        }
    }
    public function upload()
    {
        if (!in_array('updateBank', $this->permission)) {
            $this->session->set_flashdata('error', $this->lang->line('messages_not_permission'));
            redirect('banks', 'refresh');
        } else {
            $data = $this->postClean(NULL, TRUE);
            $bank = $this->model_banks->getBankById($data['id']);
            if (!$bank) {
                $this->session->set_flashdata('error', $this->lang->line('messages_bank_dont_exist'));
                redirect('dashboard', 'refresh');
            }
            if (!$this->model_banks->existBankWithThisNumber($data['number'], $data['id'])) {
                $bank_new_data = [
                    'number' => $data['number'],
                    'name' => $data['name'],
                    'active' => $data['active'],
                    'mask_agency'=> $data['mask_agency'],
                    'mask_account'=> $data['mask_account']
                ];
                $upload = $this->model_banks->update($data['id'], $bank_new_data);
                if ($upload) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_bank_updated'));
                }
                redirect('banks', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_bank_exist_with_this_number'));
                redirect('banks', 'refresh');
            }
        }
    }
    public function bankMaskDefine($mask)
    {
        $patterns = '/[0-9]/';
        $mask = preg_replace($patterns, '9', $mask);
        return $mask;
    }
}
