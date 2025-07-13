<?php

namespace App\Libraries\Queue;

use App\libraries\Enum\QueueDriverEnum;
use App\Libraries\Queue\Pushers\RedisQueuePusher;
use Exception;
use App\Libraries\Queue\Pushers\SyncQueuePusher;
use App\Libraries\Queue\Pushers\DatabaseQueuePusher;
use App\Libraries\Queue\Pushers\OracleQueuePusher;

/**
 * Classe responsável por enviar um Job para a fila de processamento.
 */
class QueueDispatcher
{
    protected $driver;
    protected $pusher;

    public function __construct($driver = null)
    {
        $ci = &get_instance();
        $ci->load->config('queue');
        $this->driver = $driver ?? $ci->config->item('default_driver');

        $this->pusher = $this->resolvePusher($this->driver);
    }

    public function dispatch($job)
    {
        if (method_exists($job, 'wasSentToQueue') && $job->wasSentToQueue()) {
            return;
        }

        if ($job->getQueueName() != 'failed'){
            $job->setOriginalQueueName($job->getQueueName());
        }

        $this->pusher->push($job);

        if (method_exists($job, 'markAsQueued')) {
            $job->markAsQueued();
        }
    }

    public function dispatchSync($job)
    {
        (new SyncQueuePusher())->push($job);

        if (method_exists($job, 'markAsQueued')) {
            $job->markAsQueued();
        }
    }

    protected function resolvePusher(string $driver)
    {
        switch ($driver) {
            case QueueDriverEnum::SYNC:
                return new SyncQueuePusher();
            case QueueDriverEnum::DATABASE:
                return new DatabaseQueuePusher();
            case QueueDriverEnum::ORACLE:
                return new OracleQueuePusher();
            case QueueDriverEnum::REDIS:
                get_instance()->load->model('model_settings');
                $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');
                return new RedisQueuePusher($sellercenter);
            default:
                throw new Exception("Driver de fila '{$driver}' não suportado.");
        }
    }
}
