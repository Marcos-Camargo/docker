<?php

namespace Tests\Fakes;

class FakeQueueService
{
    public function initService($name) {}
    public function sendMessageToQueue($queue, $job) {}
}
