<?php

namespace App\Libraries\Queue\Workers;

class JobProcessor {

    public $worker;

    public function __construct(WorkerInterface $worker) {
        $this->worker = $worker;
    }

    public function start() {
        $this->worker->run();
    }
}
