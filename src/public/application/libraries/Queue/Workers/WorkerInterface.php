<?php

namespace App\Libraries\Queue\Workers;

interface WorkerInterface {
    public function run();
}