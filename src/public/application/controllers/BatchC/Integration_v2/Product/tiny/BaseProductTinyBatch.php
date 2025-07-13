<?php

require APPPATH . "libraries/Integration_v2/tiny/ToolsProduct.php";

use Integration\Integration_v2\tiny\ToolsProduct;

/**
 * Class BaseProductTinyBatch
 * @property CI_Loader $load
 * @property Model_job_schedule $model_job_schedule
 */
abstract class BaseProductTinyBatch extends BatchBackground_Controller
{

    /**
     * @var ToolsProduct
     */
    protected $toolProduct;

    protected $jobId;
    protected $store;
    protected $pagination;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolProduct = new ToolsProduct();
        $this->load->model('model_job_schedule');

        $logged_in_sess = [
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => true
        ];
        $this->session->set_userdata($logged_in_sess);
        $this->toolProduct->setJob(get_class($this));
    }

    public function run(): bool
    {
        $args = func_get_args();
        $this->jobId = $args[0] ?? null;
        $this->store = $args[1] ?? null;
        $this->pagination = $args[3] ?? null;

        $log_name = $this->toolProduct->integration . '/' . get_class($this) . '/' . __FUNCTION__;
        if (!$this->checkStartRun(
            $log_name,
            $this->router->directory,
            get_class($this),
            $this->jobId,
            $this->store
        )) {
            return false;
        }
        
        // realiza algumas validações iniciais antes de iniciar a rotina
        try {
            $this->toolProduct->startRun($this->store);
        } catch (InvalidArgumentException $exception) {
            $this->toolProduct->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            $this->gravaFimJob();
            return true;
        }

        try {
            $this->handler($args);
        } catch (Exception $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }

        // Grava a última execução
        $this->toolProduct->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    protected abstract function handler(array $args = []):bool;

    /**
     * @param array $jobData
     * @param int $nroJobs
     * @param int $mInterval
     */
    protected function updateJobSchedules(array $jobData = [], int $nroJobs = 0, int $mInterval = 10)
    {
        $data = array_merge($jobData, [
            'module_method' => 'run',
            'status' => 0,
            'finished' => 0,
            'date_end' => null,
        ]);
        $jobs = $this->model_job_schedule->findAll([
            'module_path' => $data['module_path'],
            'params' => "{$this->store}",
            'finished' => 0,
        ]);
        $serverId = 0;
        foreach ($jobs as $job) {
            $serverId = $job['server_id'];
            $this->model_job_schedule->delete($job['id']);
        }
        $data['server_id'] = $serverId;
        $interval = 1;
        for ($i = 0; $i < $nroJobs; $i++) {
            $data = array_merge($data, [
                'params' => "{$this->store} null {$i}",
                'date_start' => date('Y-m-d H:i:s', strtotime("+{$interval} minutes")),
            ]);
            $interval += $mInterval;
            $this->model_job_schedule->create($data);
            echo "[PROCESS][LINE:" . __LINE__ . "] Recriando JOB:[{$data['module_path']}] params:[{$data['params']}] data_start:[{$data['date_start']}]\n";
        }
        $interval += ($mInterval * 3);
        $this->model_job_schedule->create(array_merge($data, [
            'params' => "{$this->store}",
            'date_start' => date('Y-m-d H:i:s', strtotime("+{$interval} minutes"))
        ]));
    }
}