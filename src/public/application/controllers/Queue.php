<?php

use App\Libraries\Queue\Pushers\OracleQueuePusher;
use App\Libraries\Queue\Pushers\RedisQueuePusher;
use App\Libraries\Queue\Tools\RedisQueueMonitor;
use App\Libraries\Queue\Workers\DatabaseWorker;
use App\Libraries\Queue\Workers\JobProcessor;
use App\Libraries\Queue\Workers\OracleWorker;
use App\Libraries\Queue\Workers\RedisWorker;

class Queue extends CI_Controller
{

    public function work(...$queues)
    {
        $this->load->config('queue');
        $driver = $this->config->item('default_driver');

        if ($driver === 'sync') {
            echo "Fila 'sync' não exige worker.".PHP_EOL;
            return;
        }

        if ($driver === 'oracle') {
            $worker = new OracleWorker();
        }

        if ($driver === 'redis') {
            get_instance()->load->model('model_settings');
            $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');
            $worker = new RedisWorker($sellercenter, $queues);
        }

        if ($driver === 'database') {
            $this->load->model('Job_model');
            $worker = new DatabaseWorker($this->Job_model, $queues);
        }

        if (!isset($worker)) {
            echo "Driver '{$driver}' não suportado.".PHP_EOL;
            return;
        }

        $processor = new JobProcessor($worker);
        $processor->start();
    }

    public function failed()
    {
        $this->load->model('Job_model');

        $jobs = $this->Job_model->getFailedJobs();

        if (empty($jobs)) {
            echo "Nenhum job falhou até agora.".PHP_EOL;
            return;
        }

        echo "Jobs falhados:".PHP_EOL;
        foreach ($jobs as $job) {
            echo "- ID: {$job->id}, Tentativas: {$job->attempts}";
            if (!empty($job->failed_at)) {
                $data = Carbon\Carbon::parse($job->failed_at);
                echo ", Data da Falha: {$data->format(DATETIME_BRAZIL)}";
            }
            echo PHP_EOL;
        }
    }

    public function retry($id)
    {
        $this->load->model('Job_model');

        $job = $this->Job_model->getFailedJobById($id);

        if (!$job) {
            echo "Job ID {$id} não encontrado na fila 'failed'.".PHP_EOL;
            return;
        }

        $payload = unserialize($job->payload);
        $queueName = $payload->getOriginalQueueName() ?? 'default';

        if ($payload->getDriver() == 'database'){
            $this->Job_model->reenqueueFailedJob($job);
        }elseif ($payload->getDriver() == 'redis'){

            $payload->markAsHandled(false);

            get_instance()->load->model('model_settings');
            $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');

            $pusher = new RedisQueuePusher($sellercenter);
            $pusher->push($payload);

            $this->Job_model->delete($id);

        }elseif($payload->getDriver() == 'oracle'){
            $payload->markAsHandled(false);

            $pusher = new OracleQueuePusher();
            $pusher->sendToQueue($queueName);

            $this->Job_model->delete($id);
        }

        echo "Job ID {$id} reenfileirado para processamento!".PHP_EOL;
    }

    public function flush()
    {
        $this->load->model('Job_model');

        $deleted = $this->Job_model->flushFailedJobs();

        echo "Fila 'failed' limpa. {$deleted} job(s) removido(s).".PHP_EOL;
    }

    public function retryAll()
    {
        $this->load->model('Job_model');

        $jobs = $this->Job_model->getFailedJobs();

        if (empty($jobs)) {
            echo "Nenhum job falhado para reenfileirar.".PHP_EOL;
            return;
        }

        foreach ($jobs as $job) {
            $this->retry($job->id);
        }

        echo count($jobs)." job(s) reenfileirado(s) para processamento.".PHP_EOL;
    }

    /**
     * @todo precisamos implementar para oracle e redis tambem
     * @param $format
     * @param $warning
     * @param $critical
     * @return void
     */
    public function stats($format = null, $warning = null, $critical = null)
    {
        $this->load->model('Job_model');
        $stats = $this->Job_model->getQueueStats();

        if ($format === 'json') {
            header('Content-Type: application/json');
            echo json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'stats' => $stats,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return;
        }

        $exitCode = 0;
        $message = "Fila OK";

        if (!is_null($critical) && $stats['stuck'] >= $critical) {
            $exitCode = 2;
            $message = "CRITICAL: {$stats['stuck']} jobs stuck!";
        } elseif (!is_null($warning) && $stats['stuck'] >= $warning) {
            $exitCode = 1;
            $message = "WARNING: {$stats['stuck']} jobs stuck!";
        }

        echo PHP_EOL."📊 STATUS DAS FILAS:".PHP_EOL;
        echo "--------------------------".PHP_EOL;
        echo "Total geral:         {$stats['total']}".PHP_EOL;
        echo "Jobs pendentes:      {$stats['pending']}".PHP_EOL;
        echo "Jobs reservados:     {$stats['reserved']}".PHP_EOL;
        echo "Jobs stuck/expirados:{$stats['stuck']}".PHP_EOL;
        echo "Jobs falhados:       {$stats['failed']}".PHP_EOL;
        echo "--------------------------".PHP_EOL;
        echo "Status: {$message}".PHP_EOL;
        echo "--------------------------".PHP_EOL.PHP_EOL;

        exit($exitCode);
    }

    /**
     * Redis apenas
     * @return void
     */
    public function monitor()
    {
        (new RedisQueueMonitor())->live();
    }

    private function sleepAndFlush($seconds)
    {
        sleep($seconds);
        $this->flushOutput();
    }

    private function flushOutput()
    {
        if (ob_get_length()) {
            @ob_flush();
            @flush();
        }
    }

}
