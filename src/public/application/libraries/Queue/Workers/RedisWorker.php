<?php

namespace App\Libraries\Queue\Workers;

use App\Jobs\GenericJob;
use App\Libraries\Cache\CacheManager;
use App\Libraries\Cache\RedisCacheHandler;
use App\libraries\Enum\QueueDriverEnum;
use App\libraries\Enum\QueueEnum;
use App\Libraries\Queue\QueueDispatcher;
use Carbon\Carbon;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

/**
 * Classe responsável por processar jobs armazenados no Redis.
 * Suporta múltiplas filas simultaneamente e reprocesso com backoff.
 */
class RedisWorker extends BaseWorker
{

    protected $sellercenter;

    /** @var RedisCacheHandler */
    protected $redis;

    /** @var array Lista de filas que o worker irá monitorar */
    protected $queues = [];

    /** @var string Fila atual sendo processada */
    protected $currentQueue;

    public function __construct($sellercenter, array $queues = [])
    {
        $this->sellercenter = $sellercenter;
        $this->redis = new RedisCacheHandler();
        $this->queues = $queues;
    }

    public function run(): void
    {
        while (true) {
            $this->discoverQueues();
            foreach ($this->queues as $queue) {
                $this->currentQueue = $queue;
                $this->processQueue($queue);
            }
            $this->sleepAndFlush(2);
        }
    }

    public function discoverQueues()
    {
        $allKeys = $this->redis->getAllKeysByPrefix("{$this->sellercenter}:queue:");
        $discovered = [];

        if ($allKeys) {
            foreach ($allKeys as $key) {
                if (preg_match("/^{$this->sellercenter}:queue:([^:]+):/", $key, $matches)) {
                    $discovered[] = $matches[1];
                }
            }
            $this->queues = array_values(array_unique($discovered));
            sort($this->queues);
        }

    }

    protected function processQueue(string $queue): void
    {
        $keys = $this->getAllReadyKeysForQueue($queue);

        foreach ($keys as $key) {
            $payload = $this->redis->get($key);

            if (!$payload) {
                $this->redis->zrem("{$this->sellercenter}:queue:{$queue}:schedule", $key);
                continue;
            }

            $job = unserialize($payload);

            if ($this->jobHasChanged($job)) {
                $this->cleanupBeforeExit();
                exit(100);
            }

            if ($this->acquireLock($key, $job)) {
                $this->processJobKey($queue, $key, $job);
                $this->releaseLock($key);
            }
        }
    }

    protected function getAllReadyKeysForQueue(string $queue): array
    {
        $now = time();
        $keys = $this->redis->zrangebyscore("{$this->sellercenter}:queue:{$queue}:schedule", 0, $now);

        $allKeys = $this->redis->getAllKeysByPrefix("{$this->sellercenter}:queue:{$queue}:");
        $immediateKeys = array_filter($allKeys, function ($k) {
            return strpos($k, ':schedule') === false;
        });

        return array_merge($keys, $immediateKeys);
    }

    protected function acquireLock(string $key, $job = null): bool
    {
        $ttl = 60 * 10;

        if ($job && method_exists($job, 'getReservedTimeoutSeconds')) {
            $ttl = (int) $job->getReservedTimeoutSeconds();
        }

        if (getenv('MODE_DEBUG')) {
            echo "lock de $ttl s".PHP_EOL;
        }

        $lockKey = "{$this->sellercenter}:lock:{$key}";
        return $this->redis->setnx($lockKey, $ttl);
    }

    protected function processJobKey(string $queue, string $key, $job): void
    {

        if (method_exists($job, 'getAvailableAt')) {
            $availableAt = strtotime($job->getAvailableAt());
            if ($availableAt > time()) {
                $this->redis->zadd("{$this->sellercenter}:queue:{$queue}:schedule", $availableAt, $key);
                return;
            }
        }

        $this->redis->delete($key);
        $this->redis->zrem("{$this->sellercenter}:queue:{$queue}:schedule", $key);

        //Marcando como processado para evitar ser recolocado na fila
        if (method_exists($job, 'markAsHandled')) {
            $job->markAsHandled();
        }

        $this->executeJob($job, $queue);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function executeJob($job, string $queue): void
    {
        try {
            if (getenv('MODE_DEBUG')) {
                echo "[".date('Y-m-d H:i:s')."] Executando job ({$queue}): ".get_class($job).PHP_EOL;
                $this->sleepAndFlush(0);
            }

            $job->handle();

            if (getenv('MODE_DEBUG')) {
                echo "[".date('Y-m-d H:i:s')."] Job finalizado com sucesso.".PHP_EOL;
                $this->sleepAndFlush();
            }
        } catch (Throwable $e) {
            if (getenv('MODE_DEBUG')) {
                echo "[".date('Y-m-d H:i:s')."] Erro ao executar job Redis: {$e->getMessage()}".PHP_EOL;
                $this->sleepAndFlush();
            }
            $this->handleJobFailure($job, $queue, json_encode($e));
        }
    }

    /**
     * @param  GenericJob  $job
     * @param  string  $queue
     * @param  string  $failedReason
     * @return void
     * @throws InvalidArgumentException [
     */
    protected function handleJobFailure($job, string $queue, string $failedReason): void
    {

        $job->attempts = (property_exists($job, 'attempts') ? $job->attempts + 1 : 1);
        $maxAttempts = method_exists($job, 'getMaxAttempts') ? $job->getMaxAttempts() : 5;

        if ($job->attempts >= $maxAttempts) {
            if (getenv('MODE_DEBUG')) {
                echo "[".date('Y-m-d H:i:s')."] Job excedeu o máximo de tentativas.".PHP_EOL;
            }
            $job->markAsHandled(false);
            $job->setFailedAt(Carbon::now());
            $job->setFailedReason($failedReason);
            $dispatcher = new QueueDispatcher(QueueDriverEnum::DATABASE);
            $job->setQueueName(QueueEnum::FAILED);
            $dispatcher->dispatch($job);
            return;
        }

        $delay = method_exists($job, 'getRetryDelaySeconds') ? $job->getRetryDelaySeconds() : 600;
        $payloadRetry = serialize($job);
        $retryKey = "{$this->sellercenter}:queue:{$queue}:".$job->computeClassHash();

        if (!$this->redis->has($retryKey)) {
            $this->redis->set($retryKey, $payloadRetry);
            $this->redis->zadd("{$this->sellercenter}:queue:{$queue}:schedule", time() + $delay, $retryKey);
        }
    }

    protected function releaseLock(string $key): void
    {
        $lockKey = "{$this->sellercenter}:lock:{$key}";
        $this->redis->delete($lockKey);
    }

    /**
     * Clean up resources before exiting
     * This method ensures Redis connections are properly closed
     * to prevent "supplied resource is not a valid stream resource" errors
     */
    protected function cleanupBeforeExit(): void
    {
        // Close the instance Redis connection
        if ($this->redis) {
            // Force Redis connection to close by calling the destructor
            unset($this->redis);
        }

        CacheManager::closeConnection();

    }

}
