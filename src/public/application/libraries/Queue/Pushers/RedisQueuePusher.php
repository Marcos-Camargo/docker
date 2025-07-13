<?php

namespace App\Libraries\Queue\Pushers;

use App\Libraries\Cache\RedisCacheHandler;
use App\libraries\Enum\QueueDriverEnum;
use App\libraries\Enum\QueueEnum;

class RedisQueuePusher
{
    protected $redis;
    protected $sellercenter;

    public function __construct($sellercenter, $redis = null)
    {
        $this->redis = $redis ?? new RedisCacheHandler();
        $this->sellercenter = $sellercenter;
    }

    public function push($job)
    {
        $queue = $job->getOriginalQueueName() ?? QueueEnum::DEFAULT;
        $classHash = method_exists($job, 'computeClassHash') ? $job->computeClassHash() : md5(get_class($job));
        $job->setDriver(QueueDriverEnum::REDIS);
        $serialized = serialize($job);
        $payloadHash  = $job->computeClassHash();
        $key = "{$this->sellercenter}:queue:{$queue}:{$payloadHash}";

        $delay = method_exists($job, 'getDelayInSeconds')
            ? $job->getDelayInSeconds()
            : 0;

        if ($this->redis->has($key)) {
            return false;
        }

        if ($delay > 0) {
            // com delay: salva em ZSET e conteúdo separado
            $this->redis->zadd("queue:{$queue}:schedule", time() + $delay, $key);
            $this->redis->set($key, $serialized);
            return true;
        }

        $this->redis->set($key, $serialized); // persistente, sem TTL
        return true;
    }

}
