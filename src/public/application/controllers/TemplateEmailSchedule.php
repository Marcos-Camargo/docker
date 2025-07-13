<?php
/*
 
Controller de Catalogos de Produtos 

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

class TemplateEmailSchedule extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->data['page_title'] = $this->lang->line('application_marketplace_trading');
        $this->load->library('form_validation');
		$this->load->model('model_template_email_schedule');
		$this->load->model('model_notification_trigger');
        $this->load->model('model_template_email');
        $this->load->model('model_queue_send_notification');
	}

	public function index()
    {
        if (!in_array('marketplaces_integrations', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['page_title'] = $this->lang->line('application_template_email_schedule');
        $this->render_template('template_email_schedule/index', $this->data);
    }
	
	public function create($data  = null)
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }

        $notification_trigger = $this->model_template_email_schedule->getNotificationTriggerSchedule();

		$this->data['page_title'] = 'Regras';
		$this->data['template_email_id'] = $this->postClean('template_email_id');
		$this->data['notification_trigger_id'] = $this->postClean('template_email_rule_id');
        $this->data['status'] = $this->postClean('template_email_rule_status');
		$this->form_validation->set_rules('template_email_id', 'Template de e-mail', 'trim|required');
		$this->form_validation->set_rules('template_email_rule_id', 'Regra de disparo', 'trim|required');
		$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

  
        if ($this->form_validation->run() == TRUE) {
		
            if ($this->data['status'] == 'on') {
                $this->data['status'] = '1';
            } else {
                $this->data['status']  = '0';
            }
            
			$data = array(
				'template_email_id' => $this->data['template_email_id'],
				'notification_trigger_id' => $this->postClean('template_email_rule_id'),
                'status' => $this->data['status']
			);
            foreach ($notification_trigger as $key => $value) {

                if($this->postClean('template_email_rule_id') == $value['notification_trigger_id'] && $value['status'] == 1 ){
                    $this->session->set_flashdata('error', 'Já existe uma regra de disparo ativa para o gatilho selecionada');
                        redirect('templateEmailSchedule/create', 'refresh');
                }
            }
			$insert = $this->model_template_email_schedule->create($data);
            if ($insert) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created') . " O template de email foi criado.");
                redirect('templateEmailSchedule/index', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('templateEmailSchedule/create', 'refresh');
            }
		} else {
			$this->render_template('template_email_schedule/create', $this->data);
		}
	}

    /*
     * Fetches the orders data from the orders table
     * this function is called from the datatable ajax function
     */
    public function fetchTemplatesScheduleData()
    {
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
				$procura = " AND ( notification_trigger.name like '%" . $busca['value'] . "%' OR notification_trigger.name like '%" . $busca['value'] . "%') ";
            }
        } else {
            if (trim($postdata['title'])) {
                $procura .= " AND template_email.title like '%" . $postdata['title'] . "%'";
            }
            if (trim($postdata['subject'])) {
                $procura .= " AND template_email.subject like '%" . $postdata['subject'] . "%'";
            }
            if (trim($postdata['status']) != "") {
                $procura .= " AND template_email.status = " . $postdata['status'];
            }
        }

		$sOrder = '';
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('', 'template_email.id', 'template_email.title', 'template_email.subject', 'template_email.status', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }
        return [$ini, $procura, $sOrder, $length];
        $data = $this->model_template_email_schedule->getTemplatesScheduleView($ini, $procura, $sOrder, $length);
        $filtered = $this->model_template_email_schedule->getTemplatesScheduleDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_template_email_schedule->getTemplatesDataCount();
        }
        $result = array();

        foreach ($data as $key => $value) {
            // button
            $buttons = '';
            if (in_array('updateCompany', $this->permission) || in_array('viewCompany', $this->permission)) {
                $buttons .= ' <a href="' . base_url('templateEmail/update/' . $value['id']) . '" class="btn btn-default"title="' . $this->lang->line('application_copy') . '"><i class="fa fa-pencil"></i></a>';
            }
            if (in_array('viewCompany', $this->permission)) {
                $buttons .= '<a href="' . base_url('templateEmail/delete/' . $value['id']) . '" class="btn btn-default" title="' . $this->lang->line('application_delete') . '"><i class="fa fa-trash"></i></a>';
            }

            $dont_use_url=(!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission));
            $result[$key] = array(
                $value['title'],
                $value['subject'],
                $value['id'],
                $value['status'],
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

    public function fetchTemplatesScheduleRulesData()
    {
        $data = $this->model_notification_trigger->getNotificationTrigger();
        echo json_encode($data);
    }

    public function emailstosend() 
    {
        $data = $this->model_queue_send_notification->list();
        echo json_encode($data);
    }

    public function fetchTemplatesData()
    {
        
        $data = $this->model_template_email->getTemplates();
        

        echo json_encode($data);
    }
    public function checkHasTemplateRuleActive($template_email_rule_id, $rule_status)
    {
        $notification_trigger = $this->model_template_email_schedule->getNotificationTriggerSchedule();
        
        foreach ($notification_trigger as $key => $value) {

            if($template_email_rule_id == $value['notification_trigger_id'] && $rule_status == $value['status']){
                if($rule_status == 1){

                    $this->session->set_flashdata('error', 'Já existe uma regra de disparo ativa para o gatilho selecionada');
                    redirect('templateEmailSchedule/index', 'refresh');
                    }
                }
                else {
                    return $rule_status;
                }
        }
    }
	public function update($id)
    {

        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $notification_trigger = $this->model_template_email_schedule->getNotificationTriggerSchedule();

        $this->data['template_email_id'] = $this->postClean('template_email_id');
		$this->data['template_email_rule_id'] = $this->postClean('template_email_rule_id');
        $this->data['status'] = $this->postClean('template_email_rule_status');
        $this->form_validation->set_rules('template_email_id', 'Template de e-mail', 'trim|required');
		$this->form_validation->set_rules('template_email_rule_id', 'Regra de disparo', 'trim|required');
		$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');
        $rule_status = null;
        if ($this->form_validation->run() == TRUE) {
            if ($this->data['status'] == 'on' || $this->data['status'] === '1') {
                $this->data['status'] = 1;
                $rule_status = $this->checkHasTemplateRuleActive($this->data['template_email_rule_id'], $this->data['status']);
            } else {
                $this->data['status']  = 0;
            }
            //$rule_status = $this->checkHasTemplateRuleActive($this->data['template_email_rule_id'], $this->data['status']);
            $data = array(
				'template_email_id' => $this->data['template_email_id'],
				'notification_trigger_id' => $this->postClean('template_email_rule_id'),
                'status' => $rule_status
			);
            foreach ($notification_trigger as $key => $value) {
                if($this->postClean('template_email_rule_id') == $value['notification_trigger_id'] && $value['status'] == 1 && $rule_status == 1){
                    $this->session->set_flashdata('error', 'Já existe uma regra de disparo ativa para gatilho selecionada');
                        redirect('templateEmailSchedule/update/'. $id);
                }else{
                    continue;
                }
            }
			$update = $this->model_template_email_schedule->update($data, $id);
            if ($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('templateEmailSchedule/index', 'refresh');
            } else {
                $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
                redirect('templateEmailSchedule/update', 'refresh');
            }
        } else {
            $templateEmailSchedule = $this->model_template_email_schedule->getTemplateNotificationData($id);
            $templateEmailScheduleRules = $this->model_notification_trigger->getNotificationTriggerId($id);
			$this->data['template_email_schedule_data'] = $templateEmailSchedule;
			$this->data['template_notification_trigger_rules_data'] = $templateEmailScheduleRules;
            $this->render_template('template_email_schedule/edit', $this->data);
        }
    }
	public function updateStatus($id)
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		
        $this->data['status'] = $this->postClean('template_email_rule_status');
		$this->form_validation->set_rules('status', 'Status', 'trim|required');

        if ($this->form_validation->run() == TRUE) {
            $data = array(
				'status' => $this->postClean('sttemplate_email_rule_statusatus'),
			);
			$update = $this->model_template_email_schedule->update($data, $id);
            if ($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('templateEmailSchedule/index', 'refresh');
            } else {
                $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
                redirect('templateEmailSchedule/update', 'refresh');
            }
        } else {
            $templateEmailSchedule = $this->model_template_email_schedule->getTemplateNotificationData($id);
            $templateEmailScheduleRules = $this->model_notification_trigger->getNotificationTriggerId($id);
			$this->data['template_email_schedule_data'] = $templateEmailSchedule;
            $this->session->set_flashdata('success', "Status atualizado");
			
			$this->data['template_notification_trigger_rules_data'] = $templateEmailScheduleRules;
            $this->render_template('template_email_schedule/edit', $this->data);
        }
    }
	public function copy($id)
    {
		// $templateEmailSchedule = $this->model_template_email_schedule->getTemplateNotificationData($id);

		// $data = array(
		// 	'template_email_id' => $templateEmailSchedule['template_email_id'],
		// 	'notification_trigger_id' => $templateEmailSchedule['notification_trigger_id'],
		// 	'status' => $templateEmailSchedule['status'],
		// );

		// $insert = $this->model_template_email_schedule->create($data);
		// if ($insert) {
		// 	$this->session->set_flashdata('success', "A regra de disparo de email foi copiada.");
		// 	redirect('templateEmailSchedule/index', 'refresh');
		// } else {
		// 	$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
		// 	redirect('templateEmailSchedule/index', 'refresh');
		// }
        return false;
	}

	public function delete($id)
    {
		$templateEmailSchedule = $this->db->where('id', $id);
    	$templateEmailSchesuleDelete = $this->db->delete('template_email_notification_trigger');

		if ($templateEmailSchesuleDelete) {
			$this->session->set_flashdata('success', "A regra de disparo de email foi excluído.");
			redirect('templateEmailSchedule/index', 'refresh');
		} else {
			$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
			redirect('templateEmailSchedule/index', 'refresh');
		}
	}

    public function getTemplatesScheduleData()
    {
        $postdata = $this->postClean(NULL,TRUE);
        
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras nt.name,te.title,  te.subject, tnt.status
                $procura = " AND (nt.name like '%" . $busca['value'] . "%' OR te.title like '%" . $busca['value'] . "%'  
                OR te.subject '%" . $busca['value'] . "%' OR tnt.status like '%" . $busca['value'] . "%') ";
            }
        } else {
            if (trim($postdata['subject'])) {
                $procura .= " AND te.subject like '%" . $postdata['subject'] . "%'";
            }
            if (trim($postdata['title'])) {
                $procura .= " AND te.title like '%" . $postdata['title'] . "%'";
            }
            if (trim($postdata['rule']) != "") {
                if (trim($postdata['rule']) != "0") {
                    $procura .= " AND nt.id = " . $postdata['rule'];
                }
            }
            if (trim($postdata['status']) != "") {
                $procura .= " AND tnt.status = " . $postdata['status'];
            }
        }
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('tnt.id', 'nt.name', 'te.title', 'te.subject', 'tnt.status', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_template_email_schedule->getTemplatesScheduleView($ini, $procura, $sOrder, $length);
        $filtered = $data;

        //TODO - REVER
        // if ($procura == '') {
        //     $total_rec = count($filtered);
        // } else {
        //     $total_rec = $this->model_template_email_schedule->getTemplatesScheduleDataCount();
        // }
        $total_rec = count($filtered);
        $result = array();

        foreach ($data as $key => $value) {
            $value['buttons'] = '';
            $buttons = '';
            $status = '';
            if ($value['status']  == '1') {
                $value['status'] = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $value['status'] = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }
            if (in_array('updateCompany', $this->permission) || in_array('viewCompany', $this->permission)) {
                $buttons .= ' <a href="' . base_url('templateEmailSchedule/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }
            if (in_array('viewCompany', $this->permission)) {
                $buttons .= '<a href="' . base_url('templateEmailSchedule/delete/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-trash"></i></a>';
            }
            $value['buttons'] = $buttons;
            $result[$key] = array(
                $value['name'],
                $value['title'],
                $value['subject'],
                $value['status'],
                $value['buttons']
            );
        }
        $output = array(
			"draw" => $draw,
		    "recordsTotal" => $total_rec,
		    "recordsFiltered" => $total_rec,
		    "data" => $result
		);
		ob_clean();
		echo json_encode($output);
    }
}