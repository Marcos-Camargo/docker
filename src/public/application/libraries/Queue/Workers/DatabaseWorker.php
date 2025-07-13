<?php

namespace App\Libraries\Queue\Workers;

use App\Libraries\Queue\JobFileWatcher;
use Exception;
use Throwable;

/**
 * DatabaseWorker is responsible for managing and processing queued jobs
 * retrieved from a job model. It processes each job by invoking its handle
 * method and manages retries, exceptions, and failures.
 *
 * This class runs continuously, fetching jobs from specified queues, and
 * ensures that jobs are properly locked, unlocked, or moved to a failed
 * queue depending on their state and processing outcome.
 *
 * Properties:
 * - $jobModel: The job model instance responsible for job data handling.
 * - $queues: An array of queue names to process jobs from.
 */
class DatabaseWorker extends BaseWorker
{
    public $jobModel;
    public $queues;

    public function __construct($jobModel, array $queues = [])
    {
        $this->jobModel = $jobModel;
        $this->queues = $queues;
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        if (empty($this->queues)) {
            $this->queues = [null];
        }

        while (true) {
            $job = $this->fetchNextJob();

            if (!$job) {
                $this->sleepAndFlush(2);
                continue;
            }

            try {
                $instance = unserialize($job->payload);

                if (!method_exists($instance, 'handle')) {
                    throw new Exception('Método handle() não encontrado.');
                }

                if ($this->jobHasChanged($instance)){
                    $this->jobModel->unlock($job->id, $job->attempts);
                    exit(100);
                }

                $instance->handle();
                $instance->markAsHandled();

                $this->jobModel->delete($job->id);
            } catch (Throwable $e) {
                if (getenv('MODE_DEBUG')) {
                    echo "[" . date('Y-m-d H:i:s') . "] ERRO Banco: " . $e->getMessage() . PHP_EOL;
                }

                if ($job->attempts >= ($job->max_attempts ?? 5)) {
                    $this->jobModel->moveToFailedQueue($job->id, json_encode($e));
                } else {
                    $retry = $job->retry_delay_seconds ?? 600;
                    $this->jobModel->unlock($job->id, $job->attempts + 1, $retry);
                }
            }
        }
    }

    protected function fetchNextJob()
    {
        foreach ($this->queues as $queue) {
            $job = $this->jobModel->getNextJob($queue);
            if ($job) return $job;
        }
        return null;
    }
}
