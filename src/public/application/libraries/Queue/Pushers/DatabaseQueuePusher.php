<?php

namespace App\Libraries\Queue\Pushers;

use App\Jobs\GenericJob;
use App\libraries\Enum\QueueDriverEnum;

class DatabaseQueuePusher
{
    protected $jobModel;

    public function __construct($jobModel = null)
    {
        if ($jobModel) {
            $this->jobModel = $jobModel;
        } else {
            $ci = &get_instance();
            $ci->load->model('Job_model');
            $this->jobModel = $ci->Job_model;
        }
    }

    public function push(GenericJob $job)
    {
        $serialized = serialize($job);
        $classHash = method_exists($job, 'computeClassHash') ? $job->computeClassHash() : md5(get_class($job));
        $payloadHash  = $job->computeClassHash();
        $job->setDriver(QueueDriverEnum::DATABASE);

        $this->jobModel->add(
            get_class($job),
            $serialized,
            $job->getQueueName(),
            $classHash,
            $job->getAvailableAt(),
            $payloadHash,
            $job->getReservedTimeoutSeconds(),
            $job->getMaxAttempts(),
            $job->getRetryDelaySeconds(),
            $job->getAttempts(),
            $job->getFailedAt(),
            $job->getFailedReason()
        );
    }
}
