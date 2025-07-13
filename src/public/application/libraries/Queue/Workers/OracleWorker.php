<?php

namespace App\Libraries\Queue\Workers;

use App\libraries\Enum\QueueDriverEnum;
use App\libraries\Enum\QueueEnum;
use App\Libraries\Queue\QueueDispatcher;
use Carbon\Carbon;
use Exception;
use QueueService;
use Throwable;

/**
 * Class OracleWorker
 *
 * Handles tasks from an Oracle-based message queue system. The class incorporates
 * job execution, failure handling, and manages the interaction with the underlying
 * queue service. Each job processed is expected to define a `handle` method for execution.
 */
class OracleWorker extends BaseWorker
{

    public static $ci;
    public static $queueName = null;
    /**
     * @var QueueService
     */
    protected $queueService;
    protected $channels = [];

    public function __construct()
    {
        if (!self::$ci) {
            self::$ci = &get_instance();
            self::$ci->load->library('QueueService');
            $this->queueService = self::$ci->queueservice;
            $this->queueService->initService('OCI');

            self::$ci->load->model('model_settings');
            $settingSellerCenter = self::$ci->model_settings->getSettingDatabyName('sellercenter');
            self::$queueName = 'queue_'.ENVIRONMENT.'_'.$settingSellerCenter['value'];
        }

    }

    public function run()
    {
        if (empty($this->channels)) {
            $this->channels = [null];
        }

        while (true) {

            $job = $this->fetchNextJob();

            if (!$job || !isset($job['message']) || !$job['message']) {
                $this->sleepAndFlush(2);
                continue;
            }

            $data = $job['message'];

            try {

                $instance = unserialize($data);

                if (!method_exists($instance, 'handle')) {
                    throw new Exception('Método handle() não encontrado no job.');
                }

                if (getenv('MODE_DEBUG')) {
                    echo "[".date('Y-m-d H:i:s')."] Executando Oracle job: ".get_class($instance).PHP_EOL;
                }

                if ($this->jobHasChanged($instance)) {
                    //Não tem unlock aqui, a própria oracle controla isso
                    exit(100);
                }

                $instance->handle();
                $instance->markAsHandled();

                $this->queueService->deleteQueueMessageQueue(self::$queueName, $job['id']);

                if (getenv('MODE_DEBUG')) {
                    echo "[".date('Y-m-d H:i:s')."] FIM do Job.".PHP_EOL;
                }

            } catch (Throwable $e) {
                if (getenv('MODE_DEBUG')) {
                    echo "[".date('Y-m-d H:i:s')."] ERRO Oracle: ".$e->getMessage().PHP_EOL;
                }
                $this->sleepAndFlush();
                $this->handleJobFailure($instance, $job['id'], json_encode($e));
            }
        }
    }

    protected function fetchNextJob()
    {
        foreach ($this->channels as $channel) {
            $job = $this->queueService->receiveQueueMessageQueue(self::$queueName, $channel);
            if ($job) {
                return $job;
            }
        }
        return null;
    }

    protected function handleJobFailure($job, $id, $failedReason): void
    {

        $job->attempts = (property_exists($job, 'attempts') ? $job->attempts + 1 : 1);
        $maxAttempts = method_exists($job, 'getMaxAttempts') ? $job->getMaxAttempts() : 5;

        if ($job->attempts >= $maxAttempts) {
            if (getenv('MODE_DEBUG')) {
                echo "[".date('Y-m-d H:i:s')."] Job excedeu o máximo de tentativas.".PHP_EOL;
            }
            $job->markAsHandled(false);
            $dispatcher = new QueueDispatcher(QueueDriverEnum::DATABASE);
            $job->setQueueName(QueueEnum::FAILED);
            $job->setFailedAt(Carbon::now());
            $job->setFailedReason($failedReason);
            $dispatcher->dispatch($job);
            //Removendo da fila para tratar internamente
            $this->queueService->deleteQueueMessageQueue(self::$queueName, $id);
        }

        //A própria oracle recoloca na fila se não deletar

    }

}
