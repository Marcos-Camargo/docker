<?php

namespace App\Libraries\Queue\Pushers;

use Exception;

class SyncQueuePusher
{
    public function push($job)
    {
        if (!method_exists($job, 'handle')) {
            throw new Exception('Método handle() não encontrado no Job.');
        }

        $job->handle();
    }
}
