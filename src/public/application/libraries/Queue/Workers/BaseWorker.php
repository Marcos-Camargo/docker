<?php

namespace App\libraries\Queue\Workers;

use App\Jobs\GenericJob;
use App\Libraries\Queue\JobFileWatcher;
use ReflectionClass;

abstract class BaseWorker implements WorkerInterface
{

    /**
     * @param  GenericJob  $instance
     * @return bool
     */
    protected function jobHasChanged($instance): bool
    {
        $reflection = new ReflectionClass($instance);
        $filePath = $reflection->getFileName();
        $watcher = JobFileWatcher::getInstance();

        if (!$watcher->check($filePath)) {
            if (getenv('MODE_DEBUG')){
                echo "[".date('Y-m-d H:i:s')."] Nova versão detectada. Encerrando.".PHP_EOL;
            }
            return true;
        }
        return false;
    }

    protected function sleepAndFlush(int $seconds = 0): void
    {
        if ($seconds > 0){
            sleep($seconds);
        }
        if (getenv('MODE_DEBUG')){
            if (function_exists('ob_flush')) @ob_flush();
            if (function_exists('flush')) @flush();
        }
    }

}
