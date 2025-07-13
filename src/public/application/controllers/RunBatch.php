<?php

/**
 * @property Model_calendar $model_calendar
 * @property Model_stores $model_stores
 * @property Model_job_schedule $model_job_schedule
 * @property Model_settings $model_settings
 */
class RunBatch extends Admin_Controller {
    var $sellercenter;
    var $queue = null;
    var $queue_name = null;
    var $maxcpu = 80;
    var $maxmem = 80;
    
    public const SLEEPWAITINGRESOURCES = 1;

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
        

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

		if ($settingSellerCenter) {
            $this->sellercenter = $settingSellerCenter['value'];
        } else {
			echo "não achei o sellercenter\n";
			die;			
		}
        $this->queue->initService('OCI'); 
    }
    
    public function run($folder=null)
    {
        $this->log_data('batch','run',$folder,"I");
        
        // recebe o sellercenter como parametro e verifica se o mesmo bate com
        if (is_null($folder)) {
            echo "Faltou passar o folder \n";
            die; 
        }

        // só funciona se tiver o parametro batch_new_version ativo 
        if ($this->model_settings->getStatusbyName('batch_new_version')!=1) {
            echo "o parametro batch_new_version não está ativo ou não existe na ".$this->sellercenter."\n";
            die;
        }

        // essa é a fila que usa para rodar jobs 
        $this->queue_name = 'job_batch_'.ENVIRONMENT.'_'.$this->sellercenter;

        $this->dailyLoop();
        
        // Log THE END;
        $this->log_data('batch','finished','',"I");
    }

    private function dailyLoop()
    {
        $today = date('Y-m-d');
        echo "Iniciando \n";
        $hhmm = '';
        while (true) {
            
            // Verifico se tem CPU & Memória disponíveis 
            while (!$this->availableResources()) {
                sleep(self::SLEEPWAITINGRESOURCES);
            }
            
            $nextJob = $this->getNextJob(); 
            if (!$nextJob || !is_array($nextJob) || is_null($nextJob['message'])) {
                echo "dormindo ".self::SLEEPWAITINGRESOURCES." seg por falta de jobs\n";
                sleep(self::SLEEPWAITINGRESOURCES); // 
                if ($hhmm != date('Hi')) { // se já passou 1 minuto e não tem job, verifico se o parametro mudou 
                    $batch_new_version = $this->model_settings->getSettingDatabyName('batch_new_version');
                    if ((!$batch_new_version) || ($batch_new_version['status']!=1)) {
                        echo "Terminando pois parametro batch_new_version não está mais ativo ou não existe na ".$this->sellercenter."\n";
                        break;
                    }
                    $hhmm = date('Hi');
                }
            } else {
                $this->runJob($nextJob);     
            }

            if ($today != date('Y-m-d')) { // verifica encerramento do dia. Um processo na crontab chamado /usr/local/bin/runjobs.sh verifica se está rodano e reinicia se precisar
                echo "Encerrando pois mudei de dia \n";
                break;
            }            
        }

    }

    private function runJob($job) {

        $message_id = $job['id']; 
        $to_run = json_decode($job['message'], true);

        if (file_exists($to_run['log_file'])) {
            shell_exec("sudo /bin/rm ".$to_run['log_file']);
        }

        $this->model_job_schedule->update(array('server_batch_ip' => exec('hostname -I')), $to_run['id']);

        //$to_run['params'] = str_replace(['/', ' '], '_', $to_run['params']);

        $program = '/usr/local/bin/executa_batch_PHP.sh '. date('Ymd').' '.$to_run['id'].' '.
            '"'.FCPATH.'index.php" '.
            '"BatchC/'.$to_run['module_path'].'/'.$to_run['module_method'].'/'.$to_run['id'].'/'.$to_run['params'].'" '.
            '"'.$to_run['log_file'].'"';
      
        $proc = new backgroundProcess();
        // $proc->setLogFile($to_run['log_file']);
        $proc->setCmd($program);
        $proc->start();
        echo "\n";

        $result = $this->queue->deleteQueueMessageQueue($this->queue_name, $message_id);
    }

    private function getNextJob() {
        
        echo date('Y-m-d H:i:s')." Procurando jobs para rodar na fila ".$this->queue_name."  \n";
        $result = $this->queue->receiveQueueMessageQueue($this->queue_name);
        if (!$result['success']) {
            error_log($result['message']);
            return false;
        }
        return $result; // retornará a mensagem ou null se não tiver nenhuma mensagem nova     		
    }

    private function availableResources(){
        
        $cpu = $this->get_server_cpu_usage() ;
        $mem = $this->get_server_memory_usage();
        echo date('Y-m-d H:i:s').": CPU: ".$cpu."%  MEM: ".(int)$mem."%"; 
        if ($cpu >  $this->maxcpu) {
            echo " - Sem CPU\n";
            return false; 
        }
        if ($mem > $this->maxmem) {
            echo " - Sem memória\n";
            return false; 
        }
        echo " - posso pegar job para executar\n";
        return true;
    }

    private function get_server_memory_usage(){
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        return $mem[2]/$mem[1]*100;
    }   

    private function get_server_cpu_usage(){

        $statData1 = $this->getServerLoadLinuxData();
        sleep(1);
        $statData2 = $this->getServerLoadLinuxData();

        if (!is_null($statData1) && !is_null($statData2)) {
            // Get difference
            $statData2[0] -= $statData1[0];
            $statData2[1] -= $statData1[1];
            $statData2[2] -= $statData1[2];
            $statData2[3] -= $statData1[3];

            // Sum up the 4 values for User, Nice, System and Idle and calculate
            // the percentage of idle time (which is part of the 4 values!)
            $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];

            // Invert percentage to get CPU time, not idle time
            return round(100 - ($statData2[3] * 100 / $cpuTime),2);
        }
        else {
            return false; 
        }

        //$load = sys_getloadavg();
        //$core_nums=trim(shell_exec("grep -P '^physical id' /proc/cpuinfo|wc -l"));
        //return $load[0]/$core_nums*100;
    }
    
    private function getServerLoadLinuxData()
    {
        if (is_readable("/proc/stat"))
        {
            $stats = @file_get_contents("/proc/stat");

            if ($stats !== false)
            {
                // Remove double spaces to make it easier to extract values with explode()
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);

                // Separate lines
                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                $stats = explode("\n", $stats);

                // Separate values and find line for main CPU load
                foreach ($stats as $statLine)
                {
                    $statLineData = explode(" ", trim($statLine));

                    // Found!
                    if
                    (
                        (count($statLineData) >= 5) &&
                        ($statLineData[0] == "cpu")
                    )
                    {
                        return array(
                            $statLineData[1],
                            $statLineData[2],
                            $statLineData[3],
                            $statLineData[4],
                        );
                    }
                }
            }
        }

        return null;
    }

}