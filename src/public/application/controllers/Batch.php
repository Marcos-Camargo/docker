<?php

/**
 * @property CI_Config $config
 *
 * @property Model_calendar $model_calendar
 * @property Model_stores $model_stores
 * @property Model_job_schedule $model_job_schedule
 * @property Model_settings $model_settings
 * @property Model_jobs_logs $model_jobs_logs
 *
 * @property BackgroundProcess $backgroundProcess
 * @property QueueService $queueService
 */

class Batch extends Admin_Controller {
    var $hm;
    var $sellercenter;
    var $queue;

    public function __construct()
    {
        parent::__construct();
        
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model("model_calendar");
		$this->load->model("model_stores");
        $this->load->model('model_job_schedule');
        $this->load->model('model_settings');
        $this->load->library("backgroundProcess");
        $this->load->library("queueService");

        $this->queue = new queueService();
        $this->queue->initService('OCI'); 

        $this->hm = dateNow()->format('Hi');

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

		if ($settingSellerCenter) {
            $this->sellercenter = $settingSellerCenter['value'];
        }
		else {
			echo "não achei o sellercenter\n";
			die;			
		}
    }
    
    public function run($cmd = NULL)
    {
        $this->log_data('batch','run',$cmd,"I");
        $cmds = explode(" ",$cmd);
        $module = $cmds[0];
        $method = null;
        $result = '';
        if (count($cmds)==2) {
            $method = $cmds[1];
        }
        if (count($cmds)==3) {
            $method = $cmds[1];
            $params = $cmds[2];
        } else {
            $params = NULL;
        }
        require_once dirname(__FILE__)."/" . $module . ".php";
        if (!is_null($method)) {
            $result = $method($this, $params);
        }
        
        // Log THE END;
        $this->log_data('batch','finished',$result,"I");
    }
    
    public function runBackground($cmd = NULL)
    {
        $this->log_data('batch','run',$cmd,"I");
        $cmds = explode(" ",$cmd);
        $module = $cmds[0];
        
        $executa = "php ".FCPATH."index.php BatchC/".trim($cmd);

        $moduleArr = explode("/", $module);
        
		$dir = $this->config->item('log_path');
		if ($dir =='') {
			$dir = FCPATH.'application/logs/';
		}
		
        foreach ($moduleArr as $key => $moduleStr) {
            if ($key == (count($moduleArr)-1)) { $module = $moduleStr; continue; }
            $dir .= $moduleStr.'/';
            if (!file_exists($dir)) {@mkdir($dir);}
        }
        $logfile = $dir.'batch_'.$module.'_'.date('Hi').'.log';

        $proc= new backgroundProcess();
        $proc->setLogFile($logfile);
        $proc->setCmd($executa);
        $proc->start();
        
    }
    
    public function runjobs($cmd = NULL)
    {
        $this->log_data('batch','runjobs','START',"I");

        $batch_new_version = $this->model_settings->getStatusbyName('batch_new_version') == 1;

        $days_of_week_window_maintenance_setting = $this->model_settings->getValueIfAtiveByName('days_of_week_window_maintenance_batch');
        $start_datetime_window_maintenance  = null;
        $end_datetime_window_maintenance    = null;
        $days_of_week_window_maintenance    = array();
        $start_time_window_maintenance      = null;
        $end_time_window_maintenance        = null;

        if ($days_of_week_window_maintenance_setting !== false) {
            $days_of_week_window_maintenance = explode(',', $days_of_week_window_maintenance_setting); // 0 = domingo
            $start_time_window_maintenance   = $this->model_settings->getValueIfAtiveByName('start_time_window_maintenance_batch'); // HH:ii:ss
            $end_time_window_maintenance     = $this->model_settings->getValueIfAtiveByName('end_time_window_maintenance_batch'); // HH:ii:ss

            // Janela de manutenção
            $start_datetime_window_maintenance  = dateNow()->format(DATE_INTERNATIONAL) . " $start_time_window_maintenance";
            $end_datetime_window_maintenance    = dateNow()->format(DATE_INTERNATIONAL) . " $end_time_window_maintenance";

            // Irá agendar o job, caso falhou em algum momento, irá tentar agendar até conseguir, durante 4 horas.
            if (
                strtotime($start_datetime_window_maintenance) &&
                strtotime($end_datetime_window_maintenance) &&
                strtotime(dateNow()->format(DATETIME_INTERNATIONAL)) <= strtotime('+4 hours', strtotime($end_datetime_window_maintenance)) &&
                strtotime(dateNow()->format(DATETIME_INTERNATIONAL)) >= strtotime($end_datetime_window_maintenance)
            ) {
                $jobs_schedules = $this->model_job_schedule->getByModulePath('Script/FixJobsKilled');
                if (empty($jobs_schedules)) {
                    $this->model_job_schedule->create([
                        'module_path'   => "Script/FixJobsKilled",
                        'module_method' => 'run',
                        'params'        => dateFormat($end_datetime_window_maintenance, 'Y-m-d\TH:i:s', NULL),
                        'status'        => 0,
                        'finished'      => 0,
                        'date_start'    => addMinutesToDatetime(dateNow()->format(DATETIME_INTERNATIONAL), 1),
                        'date_end'      => null,
                        'server_id'     => 0,
                        'alert_after'   => 30
                    ]);
                    echo "Agendou o job Script/FixJobsKilled\n";
                }
            }
        }

        $week_day = getdate(strtotime(dateNow()->format(DATETIME_INTERNATIONAL)));
        $week_day = $week_day["wday"]; // 0 = domingo

        $jobs = $this->get_readyjobs();
        $jobs = $jobs['data'];
        if (!empty($jobs)) {
            foreach($jobs as $job) {
                $id         = $job['id'];
                $module     = $job['module_path'];
                $method     = $job['module_method'];
                $params     = $job['params'];
                $start      = $job['date_start'];
                $event_type = $job['event_type'];

                // Janela de manutenção
                if (in_array($week_day, $days_of_week_window_maintenance)) {
                    if (
                        !strtotime($start_datetime_window_maintenance) ||
                        !strtotime($end_datetime_window_maintenance) ||
                        strlen($start_time_window_maintenance) !== 8 ||
                        strlen($end_time_window_maintenance) !== 8
                    ) {
                        echo "Hora de início ou Hora de fim da manutenção devem ser informados corretamente (HH:ii:ss)\n";
                        $this->model_job_schedule->update(array('status' => $this->model_job_schedule::JOB_ERROR), $id);
                        continue;
                    }

                    if (strtotime($start_datetime_window_maintenance) > strtotime($end_datetime_window_maintenance)) {
                        echo "Hora de início da manutenção não pode ser maior que a hora final\n";
                        $this->model_job_schedule->update(array('status' => $this->model_job_schedule::JOB_ERROR), $id);
                        continue;
                    }

                    if (
                        strtotime($start) < strtotime($end_datetime_window_maintenance) &&
                        strtotime($start) >= strtotime($start_datetime_window_maintenance)
                    ) {
                        echo "Job $id, em janela de manutenção\n";

                        $event_type_check = (int)$event_type;
                        $end_datetime_window_maintenance_update_job = date(DATETIME_INTERNATIONAL, strtotime('+10 minutes', strtotime($end_datetime_window_maintenance)));

                        // Job tem um intervalo muito longo para ser cancelado.
                        if (in_array($event_type_check, array(71,72,73,74,240,480))) {
                            echo "Job $id, reagendado para $end_datetime_window_maintenance_update_job\n";
                            $this->model_job_schedule->update(array(
                                'date_start' => $end_datetime_window_maintenance_update_job,
                                'status' => $this->model_job_schedule::WAITING_FOR_APPROVAL,
                                'error_msg' => null
                            ), $id);
                        } else {
                            $this->model_job_schedule->update(array('status' => $this->model_job_schedule::WINDOW_MAINTENANCE), $id);
                        }
                        continue;
                    }
                }

                $this->model_job_schedule->update(array('status' => '6'), $id);
                //$this->log_data('batch','job run',$module." (".$method.") at ". $start ,"I");
                
                $executa = "php ".FCPATH."index.php BatchC/".$module.' '.$method.' '.$id.' '.$params;
                $executaQueue = "php ".FCPATH."index.php BatchC/".$module.'/'.$method.'/'.$id.'/'.$params;

                $moduleArr = explode("/", $module);
				
				$dir = $this->config->item('log_path');
				if ($dir =='') {
					$dir = APPPATH.'logs/';
				}
	                
                foreach ($moduleArr as $key => $moduleStr) {
                    if ($key == (count($moduleArr)-1)) { $module = $moduleStr; continue; }
                    $dir .= $moduleStr.'/';
                    if (!file_exists($dir)) {@mkdir($dir);}
                }
				if ($params !='null') {
                    $params = str_replace(['/', ' '], '_', $params);
					$logfile = $dir.'batch_'.$module.'_'.$method.'_'.$params.'_'.date('Hi').'.log';
				} else {
					$logfile = $dir.'batch_'.$module.'_'.$method.'_'.date('Hi').'.log';
				}

                if ($batch_new_version) {
                    if (!$this->putOnQueue($executaQueue, $job, $logfile)) {
                        echo "não consegui gravar na fila \n";
                        $this->model_job_schedule->update(array('status' => 7), $id);
                    }
                    continue;
                }
				
				if (file_exists($logfile)) {
					 shell_exec("sudo /bin/rm ".$logfile);
				}
               
                $proc= new backgroundProcess();
                $proc->setLogFile($logfile);
                $proc->setCmd($executa);
                
                $proc->start();                
                // echo " Process ID = ".$proc->pid."\n"; 
                // $this->model_job_schedule->update(array('server_id' => $proc->pid ), $id); 
                
            }
            $result = "Called: ".count($jobs)." Jobs";            
        } else {
            $result = "NONE";
        }
        sleep(2);
        $this->model_calendar->resetJobs($this->hm);
        echo "\n";
        echo "Encerrado :".date('d/m/Y H:i:s')."\n";
        //$this->log_data('batch','finished',$result,"I");
    }
       
    public function initnewday($cmd = NULL)
    {

        $result = $this->db->truncate("job_schedule");
        $batch_new_version = $this->model_settings->getStatusbyName('batch_new_version') == 1;
        if ($batch_new_version) {
            $this->load->model('model_jobs_logs');
            $this->model_jobs_logs->removeAllSellercenter($this->sellercenter, ENVIRONMENT);
        }

    }
    
    public function get_readyjobs($parm = null)
    {
        
        $events = $this->model_calendar->readyToRun($this->hm);
        $all_events =$events->result();

        $data_events = array('data' => array());
        foreach($all_events as $r) {
            if (is_numeric($r->params) && likeTextNew('%Integration_v2%', $r->module_path)) { // verifico se a loja está ativa, quando for integração.
            	$store = $this->model_stores->getStoresData($r->params);
				if ($store['active'] != 1) {
					echo " *********************** LOJA ".$store['id']." INATIVA *************************\n";
                    $this->model_calendar->update_job($r->id,  array('status' => 5));
					continue ;
				}            	
            }
            if ($this->model_calendar->getEventOpen($r->module_path,$r->module_method,$r->params)>0) {
                echo "id: ".$r->id." path: ".$r->module_path." params: ".$r->params." já rodando\n";
				$this->model_calendar->update_job($r->id, array("status" => 3));
                continue;
			}
            if ($r->server_id != 0 && $this->model_calendar->getEventStatus($r->server_id,'4',$this->hm)>0) {
                echo "id: ".$r->id." path: ".$r->module_path." params: ".$r->params." aguardando para rodar\n";
				$this->model_calendar->update_job($r->id, array("status" => 3));
                continue;
			}
            // $this->model_calendar->update_job($r->id,  array('status' => 4));
            if ($r->server_id != 0 && array_key_exists($r->server_id,$data_events['data'])) {
                echo "id: ".$r->id." path: ".$r->module_path." params: ".$r->params." duplicado no select \n";
                $this->model_calendar->update_job($r->id, array("status" => 3));
                continue; 
            }

            $server_id = $r->server_id;

            if ($server_id == 0) {
                $server_id = get_instance()->getGUID(false);
            }

            $data_events['data'][$server_id] = array(
                'id' => $r->id,
                'module_path' => $r->module_path,
                'module_method' => $r->module_method,
                'params' => $r->params,
                'status' => $r->status,
                'date_start' => $r->date_start,
                'date_end' => $r->date_end,
                'alert_after' => $r->alert_after,
                'event_type' => $r->event_type
            );
        }
        return $data_events;
    }

    private function putOnQueue($executa, $job, $log_file) 
    {

        $message = array(
            "sellercenter"  => $this->sellercenter,
            "id"            => $job['id'],
            "module_path"   => $job['module_path'],
            "module_method" => $job['module_method'],
            "params"        => $job['params'],
            "date_start"    => $job['date_start'],
            "alert_after"   => is_null($job['alert_after']) ? 0 : $job['alert_after'],
            "program"       => $executa,
            "log_file"      => $log_file,
        );
        $result = $this->queue->sendQueueMessageQueue('job_batch_'.ENVIRONMENT.'_'.$this->sellercenter, $message);
        if (!$result['success'] && $result['message'] === 'Fila ainda não cadastrada na OCI') {

            $chunkCommand = ['php '.FCPATH.'index.php BatchC/CreateOciQueues run'];
            $shellCommands = implode(' && ', array_merge([sprintf("cd %s", FCPATH)], $chunkCommand));
            $shellCommands = sprintf("%s %s", $shellCommands, '&');
            //echo sprintf("Executando Comandos:%s\n", $shellCommands);
            exec($shellCommands);

            $result = $this->queue->sendQueueMessageQueue('job_batch_'.ENVIRONMENT.'_'.$this->sellercenter, $message);

        }
        if (!$result['success']) {
            $this->log_data('batch',__CLASS__.'/'.__FUNCTION__,json_encode(['queue_name' => 'job_batch_'.ENVIRONMENT.'_'.$this->sellercenter, 'in' => $message, 'out' => $result], JSON_UNESCAPED_UNICODE),"W");
            error_log($result['message']);
            return false;
        }
        return true;
    }
    
    public function get_newevents($parm = null)
    {
        // Our Start and End Dates
        if ($parm == null) {
            $start = $this->input->get("start");
        } else {
            $start = $parm;
        }
        
        $startdt = new DateTime('now'); // setup a local datetime
        $startdt->setTimestamp($start); // Set the date based on timestamp
        $start_format = $startdt->format('Y-m-d H:i:s');
        
        $events = $this->model_calendar->get_newevents($start_format);
        
        $data_events = array('data' => array());
        $i = 0;
        foreach($events->result() as $r) {
            
            $data_events['data'][$i] = array(
                $r->ID,
                $r->title,
                $r->module_path,
                $r->module_method,
                $r->params,
                $r->event_type,
                $r->start,
                $r->end,
                $r->alert_after, 
            );
            $i++;
        }
        if ($parm == null) {
            echo json_encode($data_events);
        } else {
            return $data_events;
        }
    }
    public function createtodayevents()
    {
        
        $this->log_data('batch','createtodayevents started','-',"I");
        
        // Our Start and End Dates
        $this->initnewday();
        if (date('H') == 23) {
            $datetime = new DateTime('tomorrow');
            $d = $datetime->format('Y-m-d H:i:s');
            $x = $datetime->getTimestamp ();
        }
        else {
            $x = time();
            $d = date("Y-m-d H:i:s");
        }
        echo 'Criando eventos para o dia '.$d."\n";
		echo ' Hora:'.$d."\n";
        $e = date('Y-m-d',$x) . " 23:59:59";
        $jobs = $this->get_newevents(strtotime($d));
        foreach ($jobs['data'] as $job) {
        	
			if (is_numeric($job[4])) { // verifico se a loja está ativa.
            	$store = $this->model_stores->getStoresData($job[4]);
                if (is_null($store)) {
                    echo " *********************** LOJA ".$job[4]." NÃO ENCONTRADA - PULANDO A CRIAÇÃO DE JOBS *************************\n";
                    continue;
                }
                if ($store['active'] != 1) {
                    echo " *********************** LOJA ".$job[4]." INATIVA - PULANDO A CRIAÇÂO DE JOBS *************************\n";
                    continue ;
                }            	
            }
			
            $id     = $job[0];
            $title  = $job[1];
            $module = $job[2];
            $method = $job[3];
            $params = $job[4];
            $type   = $job[5];
            $start  = $job[6];
            $end    = $job[7];
            $hora   = substr($start, -8);
            $ini    = date('Y-m-d',$x) . " " . $hora;
            $alert  = $job[8];
            
            // daily
            if ($type == 71) {
                $this->model_calendar->add_job(array(
                    "module_path"       => $module,
                    "module_method"     => $method,
                    "params"            => $params,
                    "status"            => 0,
                    "finished"          => 0,
                    "error"             => NULL,
                    "error_count"       => 0,
                    "error_msg"         => NULL,
                    "date_start"        => $ini,
                    "date_end"          => NULL,
                    "server_id"         => $id,
                    "alert_after"       => $alert,
                    )
                );
            }
            
            //weekly
            if (($type == 72) && (date('w') == date('w',strtotime($start)))) {
                $this->model_calendar->add_job(array(
                    "module_path"   => $module,
                    "module_method" => $method,
                    "params"        => $params,
                    "status"        => 0,
                    "finished"      => 0,
                    "error"         => NULL,
                    "error_count"   => 0,
                    "error_msg"     => NULL,
                    "date_start"    => $ini,
                    "date_end"      => NULL,
                    "server_id"     => $id, 
                    "alert_after"   => $alert,
                    )
                );
            }
            
            //Monthly
            if (($type == 73) && (date('d') == date('d',strtotime($start)))) {
                $this->model_calendar->add_job(array(
                    "module_path"   => $module,
                    "module_method" => $method,
                    "params"        => $params,
                    "status"        => 0,
                    "finished"      => 0,
                    "error"         => NULL,
                    "error_count"   => 0,
                    "error_msg"     => NULL,
                    "date_start"    => $ini,
                    "date_end"      => NULL,
                    "server_id"     => $id,
                    "alert_after"   => $alert,
                    )
                );
            }
            
            //Annually
            if (($type == 74) && (date('d-m') == date('d-m',strtotime($start)))) {
                $this->model_calendar->add_job(array(
                    "module_path"   => $module,
                    "module_method" => $method,
                    "params"        => $params,
                    "status"        => 0,
                    "finished"      => 0,
                    "error"         => NULL,
                    "error_count"   => 0,
                    "error_msg"     => NULL,
                    "date_start"    => $ini,
                    "date_end"      => NULL,
                    "server_id"     => $id,
                    "alert_after"   => $alert,
                    )
                );
            }
            
            if (($type < 70) || ($type > 79)) {
                $count = $this->hojecount($ini,$end,$type,$e);
                foreach($count as $inicio) {
                    $this->model_calendar->add_job(array(
                        "module_path"   => $module,
                        "module_method" => $method,
                        "params"        => $params,
                        "status"        => 0,
                        "finished"      => 0,
                        "error"         => NULL,
                        "error_count"   => 0,
                        "error_msg"     => NULL,
                        "date_start"    => $inicio,
                        "date_end"      => NULL,
                        "server_id"     => $id,
                        "alert_after"   => $alert,
                        )
                    );
                }
            }
        }
		echo 'Encerrado criação de eventos para o dia '.$d."\n";
		echo ' Hora:'.date("Y-m-d H:i:s")."\n";
        $this->log_data('batch','createtodayevents finished',json_encode($jobs),"I");
        return 0;
    }
    
    function hojecount($start,$end,$type,$e){
        $count = array();
        if ($end > $e) {
            $end = $e;
        }
        $minutes_to_add = $type;
        
        while ($start <= $end) {
            array_push($count,$start);
            $time = new DateTime($start);
            $time->add(new DateInterval('PT' . $minutes_to_add . 'M'));
            $start = $time->format('Y-m-d H:i');
        }
        return $count;
    }   
    
}