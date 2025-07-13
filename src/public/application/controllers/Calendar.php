<?php 

defined('BASEPATH') || exit('No direct script access allowed');
/*
 SW Serviços de Informática 2019
 
 Controller de Calendário de execução dos Jobs
 
 */
class Calendar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_calendar');
        
        $this->load->model("model_calendar");
        $this->load->model("model_job_schedule");

        
    }
    
    public function index()
    {
        if ((!in_array('createCalendar', $this->permission)) &&
            (!in_array('updateCalendar', $this->permission)) &&
            (!in_array('viewCalendar', $this->permission)) &&
            (!in_array('deleteCalendar', $this->permission))) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('calendar/index', $this->data);
    }

    public function fetchCalendarData()
    {
        ob_start();
        if ((!in_array('createCalendar', $this->permission)) &&
            (!in_array('updateCalendar', $this->permission)) &&
            (!in_array('viewCalendar', $this->permission)) &&
            (!in_array('deleteCalendar', $this->permission))) {
            redirect('dashboard', 'refresh');
        }

        if ((int) $_SERVER['CONTENT_LENGTH'] > 8192) {
            $message_403 = "Too much data.";
            show_error($message_403 , 403 );
            return; 
        }        
        
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];
        if ((!in_array('createCalendar', $this->permission)) &&
            (!in_array('updateCalendar', $this->permission)) &&
            (!in_array('viewCalendar', $this->permission)) &&
            (!in_array('deleteCalendar', $this->permission))) {
            $output = array(
                "draw" =>$draw,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => array()
            );
            ob_clean();
            echo json_encode($output);
            return; 
        }
        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $procura = " AND ( module_path like '%" . $busca['value'] . "%'";
                $procura.= " OR title like '%" . $busca['value'] . "%'";  
                $procura.= " OR params like '%" . $busca['value'] . "%'";
                $procura.= " OR module_method like '%" . $busca['value'] . "%' ) ";
            }
        } else {
            if (trim($postdata['title'])) {
                $procura .= " AND title like '%" . $postdata['title'] . "%'";
            }
            if (trim($postdata['module_path'])) {
                $procura .= " AND module_path like '%" . $postdata['module_path'] . "%'";
            }
            if (trim($postdata['module_params'])) {
                $procura .= " AND params like '%" . $postdata['module_params'] . "%'";
            }
            if (trim($postdata['module_method'])) {
                $procura .= " AND module_method like '%" . $postdata['module_method'] . "%'";
            }
            if (trim($postdata['event_type'])) {
                $procura .= " AND event_type = " . $postdata['event_type'];
            }
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('id','title','module_path', 'module_method', 'params', 'event_type', 'starttime','start', 'end','alert_after');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_calendar->getCalendarDataView($ini, $procura, $sOrder, $length);
        $filtered = $this->model_calendar->getCalendarDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_calendar->getCalendarDataCount();
        }

        $result = array();
        foreach ($data as $key => $value) {

            if(in_array('updateCalendar', $this->permission)) { // ou ele ve ou ele altera 
                $buttons = '<a href="'.base_url('calendar/edit/'.$value['ID']).'" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="'.$this->lang->line('application_edit').'" ><i class="fa fa-pencil"></i></a>'; 
            }elseif(in_array('viewCalendar', $this->permission)) {
                $buttons = '<a href="'.base_url('calendar/view/'.$value['ID']).'" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="'.$this->lang->line('application_view').'" ><i class="fa fa-eye"></i></a>'; 
            }else {
                $buttons = ''; 
            }
            if(in_array('deleteCalendar', $this->permission)) {
                $buttons .= '<a href="'.base_url('calendar/delete/'.$value['ID']).'" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="'.$this->lang->line('application_delete').'" ><i class="fa fa-trash"></i></a>'; 
            }
            if(in_array('updateCalendar', $this->permission) || in_array('createCalendar', $this->permission)) { // ou ele ve ou ele altera 
                 $buttons .= '<a href="'.base_url('calendar/runNow/'.$value['ID']).'" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="'.$this->lang->line('application_run_now').'" ><i class="fas fa-running"></i></a>'; 
            }
            if ($value['event_type']==71) {$type = $this->lang->line('application_daily');}
            elseif ($value['event_type']==72) {$type = $this->lang->line('application_weekly');}
            elseif ($value['event_type']==73) {$type = $this->lang->line('application_monthly');}
            elseif ($value['event_type']==74) {$type = $this->lang->line('application_annually');}
            else {$type = 'a cada '.$value['event_type'].' '.$this->lang->line('application_mintes'); }

            $result[$key] = array(
                $value['ID'],
                $value['title'],
                $value['module_path'],
                $value['module_method'],
                (is_null($value['params'])) ?'null': $value['params'],
                $type,
                $value['starttime'],
                (is_null( $value['start']) ? '' : date('d/m/Y H:i:s', strtotime($value['start']))),
                (is_null( $value['end']) ? '' : date('d/m/Y H:i:s', strtotime($value['end']))),
                is_null($value['alert_after']) ? $this->lang->line('application_no_alert') : $value['alert_after'].' '.$this->lang->line('application_mintes'),
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

    public function edit($id){

		if(!in_array('updateCalendar', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
        
        $dataCalendar = $this->model_calendar->getCalendarById($id);
        If(!$dataCalendar) {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            redirect('calendar/', 'refresh');
        }

        $this->form_validation->set_rules('edit_title', $this->lang->line('application_event_name'), 'trim|required');
        $this->form_validation->set_rules('edit_event_type', $this->lang->line('application_type'), 'trim|required');
        $this->form_validation->set_rules('edit_module_path', $this->lang->line('application_module'), 'trim|required');
        $this->form_validation->set_rules('edit_module_method', $this->lang->line('application_method'), 'trim|required');
        $this->form_validation->set_rules('edit_params', $this->lang->line('application_params'), 'trim|required');
        $this->form_validation->set_rules('edit_start', $this->lang->line('application_start_date'), 'trim|required');
        $this->form_validation->set_rules('edit_end', $this->lang->line('application_end_date'), 'trim|required');
		$this->form_validation->set_rules('edit_alert_after', $this->lang->line('application_alert_after'), 'trim|integer|is_natural');
        
        if ($this->form_validation->run()) {
            
            $data = array(
                'title'         => $this->postClean('edit_title',TRUE),
                'event_type'    => $this->postClean('edit_event_type',TRUE),
                'module_path'   => $this->postClean('edit_module_path',TRUE),
                'module_method' => $this->postClean('edit_module_method',TRUE),
                'params'        => $this->postClean('edit_params',TRUE),
                'start'         => $this->postClean('edit_start',TRUE),
                'end'           => $this->postClean('edit_end',TRUE),
                'alert_after'   => $this->postClean('edit_alert_after',TRUE) == 0 ? null : $this->postClean('edit_alert_after',TRUE),

            );
            get_instance()->log_data('Calendar','edit before',json_encode($dataCalendar),"I");
            $update = $this->model_calendar->update_event($id,$data);
            if($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
				$data['id'] = $id;
				get_instance()->log_data('Calendar','edit after',json_encode($data),"I");
                redirect('calendar', 'refresh');
            }
            else {
                get_instance()->log_data('Calendar','Erro ao gravar',json_encode($data),"E");
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('calendar/edit/'.$id, 'refresh');
            }
        }
		else {
			$this->data['data'] = $dataCalendar;
            $this->data['function'] = 'edit';
		    $this->render_template('calendar/edit', $this->data);
		}
	
	}

    public function create(){
        
        if(!in_array('createCalendar', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('edit_title', $this->lang->line('application_event_name'), 'trim|required');
        $this->form_validation->set_rules('edit_event_type', $this->lang->line('application_type'), 'trim|required');
        $this->form_validation->set_rules('edit_module_path', $this->lang->line('application_module'), 'trim|required');
        $this->form_validation->set_rules('edit_module_method', $this->lang->line('application_method'), 'trim|required');
        $this->form_validation->set_rules('edit_params', $this->lang->line('application_params'), 'trim|required');
        $this->form_validation->set_rules('edit_start', $this->lang->line('application_start_date'), 'trim|required');
        $this->form_validation->set_rules('edit_end', $this->lang->line('application_end_date'), 'trim|required');
        $this->form_validation->set_rules('edit_alert_after', $this->lang->line('application_alert_after'), 'trim|integer|is_natural|required');
       
        if ($this->form_validation->run()) {
            $data = array(
                'title'         => $this->postClean('edit_title',TRUE),
                'event_type'    => $this->postClean('edit_event_type',TRUE),
                'module_path'   => $this->postClean('edit_module_path',TRUE),
                'module_method' => $this->postClean('edit_module_method',TRUE),
                'params'        => $this->postClean('edit_params',TRUE),
                'start'         => $this->postClean('edit_start',TRUE),
                'end'           => $this->postClean('edit_end',TRUE),
                'alert_after'   => $this->postClean('edit_alert_after',TRUE) == 0 ? null : $this->postClean('edit_alert_after',TRUE),
            );
            $id = $this->model_calendar->add_event($data);
            if($id) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
				$data['id'] = $id;
				get_instance()->log_data('Calendar','create',json_encode($data),"I");
                redirect('calendar', 'refresh');
            }
            else {
                get_instance()->log_data('Calendar','Erro ao gravar',json_encode($data),"E");
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('calendar/create', 'refresh');
            }

        }
        $this->data['data'] = array(
            'ID'            => 0, 
            'title'         => '',
            'event_type'    => '',
            'module_path'   => '',
            'module_method' => '',
            'params'        => '',
            'start'         => '',
            'end'           => '2200-12-31 23:59:59',
            'alert_after'   => '',

        );
        $this->data['function'] = 'create';
        $this->render_template('calendar/edit', $this->data);
    }

    public function delete($id)
    {
        if (!in_array('deleteCalendar', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        if ($id) {
            $dataCalendar = $this->model_calendar->getCalendarById($id);
            If(!$dataCalendar) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('calendar/', 'refresh');
            }
            if ($this->postClean('confirm')) {
                $delete = $this->model_calendar->delete_event($id);
                if ($delete) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed'));
                    redirect('calendar/', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('calendar/delete/' . $id, 'refresh');
                }
            } else {
                $this->data['data'] = $dataCalendar;
                $this->data['function'] = 'delete';
                $this->render_template('calendar/edit', $this->data);
            }
        }
       
    }

    public function view($id)
    {
        if (!in_array('viewCalendar', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        if ($id) {
            $dataCalendar = $this->model_calendar->getCalendarById($id);

            $this->data['data'] = $dataCalendar;
            $this->data['function'] = 'view';
            $this->render_template('calendar/edit', $this->data);
            
        }
    }

    public function runNow($id)
    {
        if ((!in_array('updateCalendar', $this->permission)) && (!in_array('createCalendar', $this->permission))) {
            redirect('dashboard', 'refresh');
        }

        if ($id) {
        	$event = (array)$this->model_calendar->get_event_array($id);            
			if (!$event) {
				$this->session->set_flashdata('error', $this->lang->line('messages_job_not_found'));
                redirect('calendar/', 'refresh');
			}
			$this->data['event'] = $event;
            if ($this->postClean('confirm')) {
                $date_job = array (
                    'module_path'   => $event['module_path'],
                    'module_method' => $event['module_method'],
                    'params'        => $event['params'],
                    'finished'      => 0,
                    'status'        => 0,
                    'error_msg'     => 0, 
                    'date_start'    => date('Y-m-d H:i:s'),
                    'alert_after'   => $event['alert_after'],
                    'server_id'     => $id
                );
                $run_now = $this->model_job_schedule->create($date_job);
                if ($run_now) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_scheduled'));
                    redirect('calendar/', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('calendar/run_now/' . $id, 'refresh');
                }

            } else {
                $this->data['id'] = $id;
                $this->render_template('calendar/run_now', $this->data);
            }
        }
    }
    
}
?>