<?php

namespace App\Libraries\Queue\Pushers;

use App\libraries\Enum\QueueDriverEnum;

class OracleQueuePusher
{
    public static $ci;
    protected $queueService;
    public static $queueName = null;

    public function __construct($queueService = null, $modelSettings = null)
    {
        if (!self::$ci) {
            self::$ci = &get_instance();
        }

        $this->queueService = $queueService ?? self::$ci->queueservice;
        $this->queueService->initService('OCI');

        $modelSettings = $modelSettings ?? self::$ci->model_settings;
        $settingSellerCenter = $modelSettings->getSettingDatabyName('sellercenter');
        self::$queueName = 'queue_' . ENVIRONMENT . '_' . $settingSellerCenter['value'];
    }

    public function push($job)
    {
        $classHash = method_exists($job, 'computeClassHash') ? $job->computeClassHash() : md5(get_class($job));
        $job->setDriver(QueueDriverEnum::ORACLE);

        $this->sendToQueue($job);
    }

    public function sendToQueue($job): void
    {
        $this->queueService->sendMessageToQueue(self::$queueName, $job);
    }
}
