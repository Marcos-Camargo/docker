<?php

namespace App\Libraries\Queue\Traits;

use App\libraries\Enum\QueueEnum;
use App\Libraries\Queue\QueueDispatcher;

trait Dispatchable
{
    protected $sentToQueue = false;
    protected $autoDispatch = false;

    public static function dispatch(...$params)
    {
        $instance = new static(...$params);
        $instance->autoDispatch = true;
        return $instance;
    }

    public static function dispatchSync(...$params)
    {
        $instance = new static(...$params);
        $dispatcher = new QueueDispatcher();
        $dispatcher->dispatchSync($instance);
        return $instance;
    }

    public function queue(string $queueName = QueueEnum::DEFAULT)
    {
        if (method_exists($this, 'setQueueName')) {
            $this->setQueueName($queueName);
        }

        $this->autoDispatch = false;
        $dispatcher = new QueueDispatcher();
        $dispatcher->dispatch($this);
        return $this;
    }

    public function __destruct()
    {
        if ($this->autoDispatch) {
            $dispatcher = new QueueDispatcher();
            $dispatcher->dispatch($this);
        }
    }

    public function delay(int $seconds)
    {
        if (method_exists($this, 'setAvailableAt')) {
            $this->setAvailableAt(date('Y-m-d H:i:s', strtotime('+' . $seconds . ' seconds')));
        }
        return $this;
    }

    public function delayRetry(int $seconds)
    {
        if (method_exists($this, 'setRetryDelaySeconds')) {
            $this->setRetryDelaySeconds($seconds);
        }
        return $this;
    }

    public function reservedTimeout(int $seconds)
    {
        if (method_exists($this, 'setReservedTimeoutSeconds')) {
            $this->setReservedTimeoutSeconds($seconds);
        }
        return $this;
    }

    public function maxAttempts(int $attempts)
    {
        if (method_exists($this, 'setMaxAttempts')) {
            $this->setMaxAttempts($attempts);
        }
        return $this;
    }

    public function wasSentToQueue()
    {
        return $this->sentToQueue;
    }

    public function markAsQueued()
    {
        $this->sentToQueue = true;
    }

    protected function sendToQueue()
    {
        $dispatcher = new QueueDispatcher();
        $dispatcher->dispatch($this);
    }
}
