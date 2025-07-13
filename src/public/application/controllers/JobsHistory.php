<?php

/*
 SW Serviços de Informática 2019
 
 Controller de Usuários
 
 */
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * @property Model_job_schedule $model_job_schedule
 * @property Model_jobs_logs $model_jobs_logs
 * @property Model_settings $model_settings
 */
class JobsHistory extends Admin_Controller
{

    var $sellercenter = null;

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_jobs');

        $this->load->model('model_job_schedule');
        $this->load->model('model_jobs_logs');
        $this->load->model('model_settings');
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

		if ($settingSellerCenter) {
            $this->sellercenter = $settingSellerCenter['value'];
        }
        

    }


    public function index()
    {
        if (!in_array('viewCalendar', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($this->model_settings->getStatusbyName('batch_new_version') != 1) {
            redirect('jobs', 'refresh');
        }
        $this->render_template('jobshistory/index', $this->data);
        
    }

    public function fetchJobsData()
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $postdata = $this->postClean(NULL,TRUE);

            $filters        = array();
            $filter_default = array();

            if (trim($postdata['module_path'])) {
                $filters[]['like']['module_path'] = $postdata['module_path'];
            }
            if (trim($postdata['module_params'])) {
                $filters[]['like']['params'] = $postdata['module_params'];
            }
            if (trim($postdata['module_method'])) {
                $filters[]['like']['module_method'] = $postdata['module_method'];
            }
            if ($postdata['status'] === '0') {
                $filters[]['where']['status'] = 0;
            } else {
                if (trim($postdata['status'])) {
                    $filters[]['where']['status'] = $postdata['status'];
                }
            }

            $fields_order = array('id','module_path', 'module_method', 'params', 'status', 'date_start', 'start_alert','','','server_batch_ip');

            $query = array();
            $query['select'][] = "
                id,
                status,
                start_alert,
                module_path,
                module_method,
                params,
                date_start,
                server_batch_ip
            ";
            $query['from'][] = 'job_schedule';

            $data = fetchDataTable(
                $query,
                array('id', 'DESC'),
                null,
                null,
                ['viewCalendar'],
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
            $label_status = getColorLabelJobStatus($value['status']);
            $status = "<span class='label label-$label_status'>{$this->lang->line("application_status_job_history_$value[status]")}</span>";

            $buttons = '<a href="'.base_url('jobsHistory/runNow/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="'.$this->lang->line('application_run_now').'" ><i class="fas fa-running"></i></a>'; 
            $alert =  $this->lang->line('application_no_alert');
            if (!is_null( $value['start_alert'])) {
                $label_tmp = '<span class="label label-success">';
                if (strtotime($value['start_alert']) <= strtotime("now")) {
                    $label_tmp = '<span class="label label-danger">';
                }
                $alert = $label_tmp.date('d/m/Y H:i:s', strtotime($value['start_alert'])).'</span>';
            }

            // Condição, pois atualmente não temos a conexão com o 'monitor' em ambiente local
            $jobhistory = ENVIRONMENT === 'local' ? null : $this->model_jobs_logs->getJob($this->sellercenter, $value['id']);
            $error_job  = ''; 
            $end_date = null; 
            if ($jobhistory) {
                $error_job = $jobhistory['success']==1 ? $this->lang->line('application_no') : '<span class="label label-danger">' . $this->lang->line('application_yes') . '</span>';                                
                $end_date = $jobhistory['date_create'];
                $buttons .= '<a href="'.base_url('jobsHistory/getLog/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" data-placement="right" title="Ver Log de Execução" ><i class="fas fa-book"></i></a>'; 
            }
            if (in_array($value['status'], [0,1,4,6])) {
                $buttons .= '<button type="button" class="btn btn-default cancelJob" data-id="'.$value['id'].'"><i class="fas fa-times"></i></button>';
            }
            $result[] = array(
                $value['id'],
                $value['module_path'] ,
                $value['module_method'],
                (is_null($value['params'])) ?'null': $value['params'],
                $status,
                (is_null( $value['date_start']) ? '' : date('d/m/Y H:i:s', strtotime($value['date_start']))),
                $alert,
                (is_null( $end_date) ? '' : date('d/m/Y H:i:s', strtotime($end_date))),
                $error_job,
                $value['server_batch_ip'],
                $buttons
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

    public function runNow($id)
    {
        if ((!in_array('updateCalendar', $this->permission)) && (!in_array('createCalendar', $this->permission))) {
            redirect('dashboard', 'refresh');
        }

        if ($id) {
        	$job = $this->model_job_schedule->getData($id);
			if (!$job) {
				$this->session->set_flashdata('error', $this->lang->line('messages_job_not_found'));
                redirect('jobsHistory/', 'refresh');
			}
			$this->data['job'] = $job;
            if ($this->postClean('confirm')) {
                $run_now = $this->model_job_schedule->runNow($id);
                if ($run_now == true) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_scheduled'));
                    redirect('jobsHistory/', 'refresh');
                } else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('jobsHistory/run_now/' . $id, 'refresh');
                }

            } else {
                $this->data['id'] = $id;
                $this->render_template('jobshistory/run_now', $this->data);
            }
        }
    }

    public function getLog($id)
    {
        if ((!in_array('updateCalendar', $this->permission)) && (!in_array('createCalendar', $this->permission))) {
            redirect('dashboard', 'refresh');
        }

        if ($id) {
        	$job = $this->model_job_schedule->getData($id);
			if (!$job) {
				$this->session->set_flashdata('error', $this->lang->line('messages_job_not_found'));
                redirect('jobsHistory/', 'refresh');
			}
            $jobhistory = $this->model_jobs_logs->getJob($this->sellercenter, $id);

            $log = file_get_contents( $jobhistory['log_url']);

			$this->data['job'] = $job;
            $this->data['id'] = $id;
            $this->data['log'] =  $log;
            $this->data['jobhistory'] = $jobhistory;
            
            $this->render_template('jobshistory/listlog', $this->data);

        }
    }

    public function UpdateStatus(): CI_Output
    {
        if (!in_array('viewCalendar', $this->permission)) {
            return $this->output->set_output(json_encode(array('success' => false, 'message' => $this->lang->line('messages_no_access_error')),JSON_UNESCAPED_UNICODE));
        }

        $this->output->set_content_type('application/json');

        $id = $this->postClean('id');
        $status = $this->postClean('status');

        $this->model_job_schedule->update(array(
            'status' => $status,
            'date_end' => dateNow()->format(DATETIME_INTERNATIONAL)
        ), $id);

        return $this->output->set_output(json_encode(array('success' => true, 'message' => $this->lang->line('messages_successfully_updated')),JSON_UNESCAPED_UNICODE));
    }
	
 }