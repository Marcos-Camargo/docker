<?php

/**
 * @property Model_job_schedule $model_job_schedule
 */
class FixJobsKilled extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_job_schedule');
    }

    public function run($id = null, string $end_date = null)
    {
        if (empty($end_date)) {
            echo "Deve ser informado ID e end_date\n";
            return;
        }

        if (!strtotime($end_date) || !strpos($end_date, 'T') !== false || strlen($end_date) !== 19) {
            echo "Deve ser informado corretamente o parâmetro start_date no formato Y-m-dTH:i:s (2025-01-01T12:30:00)\n";
            return;
        }

        $this->setIdJob($id);
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            return;
        }

        $this->fixJobs($end_date);

        $this->gravaFimJob();
    }

    public function fixJobs(string $end_date): bool
    {
        /**
         * Type:        Error
         * Message:     Call to a member function row_object() on bool
         * Filename:    /var/www/html/decathlon/app/application/libraries/CalculoFrete.php
         *
         * Severity:    Warning
         * Message:     mysqli::real_connect(): (HY000/2002): Connection refused
         * Filename:    /var/www/html/decathlon/app/system/database/drivers/mysqli/mysqli_driver.php
         */

        if (file_exists('application/logs/batch')) {
            $batch_path = 'application/logs/batch';
        } else if (file_exists('application/logs')) {
            $batch_path = 'application/logs';
        } else {
            echo "[ ERROR ] Não foi encontrado pasta de log.\n";
            return false;
        }

        $end_date = str_replace('T', ' ', $end_date);
        $jobs = $this->model_job_schedule->getByStartDateAndEndDate($end_date);

        if (empty($jobs)) {
            echo "[  INFO ] Não foi encontrado jobs com status 1 e com a data menor que $end_date\n";
            return false;
        }

        foreach ($jobs as $job) {
            // Adiciona o prefixo "batch_" no arquivo.
            $module_path_exp    = explode('/', $job['module_path']);
            $last_module_path   = end($module_path_exp);
            $module_path        = str_replace($last_module_path, "batch_$last_module_path", $job['module_path']);

            $file = "$batch_path/{$module_path}_$job[module_method]";

            if ($job['params'] !== 'null') {
                $job['params'] = str_replace(' ', '_', $job['params']);
                $file .= "_$job[params]";
            }

            $final_file = null;
            for ($x = 0; $x < 4; $x++) {

                $date_start = $job['date_start'];
                $date_start = strtotime("+$x minutes", strtotime($date_start));
                $date_start = date('Hi', $date_start);

                $final_file_validate = "{$file}_$date_start.log";

                if (file_exists($final_file_validate)) {
                    $final_file = "{$file}_$date_start.log";
                    break;
                }
            }

            if (empty($final_file)) {
                echo "[ ERROR ] Não encontrado o arquivo do job $job[id]\n";
                continue;
            }

            $file_content = file_get_contents($final_file);
            $date_now = dateNow()->format('Y/m/d');

            // Iniciado em 2025/02/28 04:20:07
            if (!strpos($file_content, "Iniciado em $date_now") !== false) {
                echo "[ ERROR ] O arquivo não é do dia atual do job $job[id]\n";
                continue;
            }

            // Ver se encontra o tipo "Error" e se existe um erro com a conexão com o banco de dados.
            if (
                !strpos($file_content, 'Type:        Error') !== false ||
                !strpos($file_content, 'Message:     Call to a member function row_object() on bool') !== false ||
                !strpos($file_content, 'Message:     mysqli::real_connect(): (HY000/2002): Connection refused') !== false
            ) {
                $file_content = str_replace(' ','', $file_content);
                if (
                    !strpos($file_content, str_replace(' ','','Type:        Error')) !== false ||
                    !strpos($file_content, str_replace(' ','','Message:     Call to a member function row_object() on bool')) !== false ||
                    !strpos($file_content, str_replace(' ','','Message:     mysqli::real_connect(): (HY000/2002): Connection refused')) !== false
                ) {
                    echo "[ ERROR ] Não foi encontrado um erro de banco de dados mapeado do job $job[id]\n";
                    continue;
                }
            }

            // Foi encontrado um erro de banco de dados e deve mover o job para "7-erro"
            $update_status = $this->model_job_schedule::JOB_ERROR;
            $this->model_job_schedule->update(array('status' => $update_status), $job['id']);

            echo "[SUCCESS] Job $job[id] enviado para status $update_status\n";
        }

        return true;
    }
}
