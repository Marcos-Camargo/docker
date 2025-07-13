<?php

/*
 SW Serviços de Informática 2019
 
 Controller de Usuários
 
 */
defined('BASEPATH') || exit('No direct script access allowed');

class Jobs extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_jobs');

        $this->load->model('model_job_schedule');

    }


    public function index()
    {
        if (!in_array('viewCalendar', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('jobs/index', $this->data);

        if ($this->model_settings->getStatusbyName('batch_new_version') == 1) {
            redirect('jobsHistory', 'refresh');
        }
    }

    public function fetchJobsData()
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
                $procura = " AND ( module_path like '%" . $busca['value'] . "%'";
                $procura.= " OR module_params like '%" . $busca['value'] . "%'";  
                $procura.= " OR module_method like '%" . $busca['value'] . "%' ) ";
            }
        } else {
            if (trim($postdata['module_path'])) {
                $procura .= " AND module_path like '%" . $postdata['module_path'] . "%'";
            }
            if (trim($postdata['module_params'])) {
                $procura .= " AND params like '%" . $postdata['module_params'] . "%'";
            }
            if (trim($postdata['module_method'])) {
                $procura .= " AND module_method like '%" . $postdata['module_method'] . "%'";
            }
            if (trim($postdata['status'])) {
                $procura .= " AND status = " . $postdata['status'];
            }
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('id','module_path', 'module_method', 'params', 'status', 'date_start', 'start_alert');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_job_schedule->getJobsDataView($ini, $procura, $sOrder, $length);
        $filtered = $this->model_job_schedule->getJobsDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_job_schedule->getJobsDataCount();
        }

        $result = array();
        foreach ($data as $key => $value) {
            switch ($value['status']) {
                case 0:
                    $label_status = 'success';
                    break;
                case 7:
                case 1:
                    $label_status = 'danger';
                    break;
                case 3:
                    $label_status = 'info';
                    break;
                case 5:
                case 8:
                case 4:
                    $label_status = 'warning';
                    break;
                case 6:
                    $label_status = 'default';
                    break;
                default:
                    $label_status = 'primary';
                    break;
            }

            $status = "<span class='label label-$label_status'>{$this->lang->line("application_status_job_history_$value[status]")}</span>";
            
            $buttons = '<a href="'.base_url('jobs/runNow/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="'.$this->lang->line('application_run_now').'" ><i class="fas fa-running"></i></a>'; 
            $alert =  $this->lang->line('application_no_alert');
            if (!is_null( $value['start_alert'])) {
                $label_tmp = '<span class="label label-success">';
                if (strtotime($value['start_alert']) <= strtotime("now")) {
                    $label_tmp = '<span class="label label-danger">';
                }
                $alert = $label_tmp.date('d/m/Y H:i:s', strtotime($value['start_alert'])).'</span>';
            }
            $result[$key] = array(
                $value['id'],
                $value['module_path'] ,
                $value['module_method'],
                (is_null($value['params'])) ?'null': $value['params'],
                $status,
                (is_null( $value['date_start']) ? '' : date('d/m/Y H:i:s', strtotime($value['date_start']))),
                $alert,
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

    public function runNow($id)
    {
        if ((!in_array('updateCalendar', $this->permission)) && (!in_array('createCalendar', $this->permission))) {
            redirect('dashboard', 'refresh');
        }

        if ($id) {
        	$job = $this->model_job_schedule->getData($id);
			if (!$job) {
				$this->session->set_flashdata('error', $this->lang->line('messages_job_not_found'));
                redirect('jobs/', 'refresh');
			}
			 $this->data['job'] = $job;
            if ($this->postClean('confirm')) {
                $run_now = $this->model_job_schedule->runNow($id);
                if ($run_now == true) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_scheduled'));
                    redirect('jobs/', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('jobs/run_now/' . $id, 'refresh');
                }

            } else {
                $this->data['id'] = $id;
                $this->render_template('jobs/run_now', $this->data);
            }
        }
    }
	
 }