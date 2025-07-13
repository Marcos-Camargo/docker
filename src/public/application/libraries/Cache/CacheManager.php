<?php

namespace App\Libraries\Cache;

class CacheManager
{
    public static $host = '';
    public static $port = '';
    public static $timeout = 0.5;
    public static $password = '';

    public static $redisConnection;
    public static $shouldBeConnected = true;

    public static function getAllByPrefix(string $prefix): array
    {
        $return = [];
        $keys = self::getAllKeysByPrefix($prefix);
        if ($keys) {
            foreach ($keys as $key) {
                $item = self::get($key);
                if ($item) {
                    $return[$key] = $item;
                }
            }
        }
        return $return;
    }

    public static function getAllKeysByPrefix(string $prefix)
    {
        self::checkConnection();
        if (self::$shouldBeConnected) {
            return self::$redisConnection->keys($prefix . '*');
        }
        return null;
    }

    private static function checkConnection(): void
    {
        if (is_null(self::$redisConnection)) {
            try {
                get_instance()->load->library("Cache/RedisCodeigniter", [], 'redis');
                self::$redisConnection = get_instance()->redis;
                self::loadConfigurations();
                if (!self::$host || !self::$port) {
                    self::$shouldBeConnected = false;
                    return;
                }
                self::$redisConnection->configure([
                    'timeout' => self::$timeout,
                    'host' => self::$host,
                    'port' => self::$port,
                    'password' => self::$password
                ]);

            } catch (\Throwable $exception) {
            }
        }

        if (self::$shouldBeConnected && !(self::$redisConnection && ((isset(self::$redisConnection->is_connected) && !self::$redisConnection->is_connected) || !self::$redisConnection->isConnected()) )) {
            throw new \Exception("Redis not connected on: " . self::$host . ':' . self::$port);
        }
    }

    private static function loadConfigurations(): void
    {
        get_instance()->load->model('model_settings');
        $endpoint = get_instance()->model_settings->getValueIfAtiveByName('endpoint_redis_quote');
        if ($endpoint) self::$host = $endpoint;
        $port = get_instance()->model_settings->getValueIfAtiveByName('port_redis_quote');
        if ($port) self::$port = $port;
    }

    public static function get(string $key): ?string
    {
        self::checkConnection();
        return self::$shouldBeConnected ? self::$redisConnection->get($key) : null;
    }

    public static function deleteAllByPrefix(string $prefix): void
    {
        $keys = self::getAllKeysByPrefix($prefix);
        if ($keys) self::delete($keys);
    }

    public static function delete(array $keys): void
    {
        self::checkConnection();
        if (self::$shouldBeConnected) self::$redisConnection->del(...$keys);
    }

    public static function setex(string $key, string $data, int $ttl = null): void
    {
        self::checkConnection();
        if (self::$shouldBeConnected) self::$redisConnection->setex($key, $ttl, $data);
    }

    public static function set(string $key, string $data): void
    {
        self::checkConnection();
        if (self::$shouldBeConnected) self::$redisConnection->set($key, $data);
    }

    // Atalhos para comandos Redis relacionados a filas

    public static function zadd(string $key, int $score, string $member): void
    {
        self::checkConnection();
        if (self::$shouldBeConnected) self::$redisConnection->zAdd($key, $score, $member);
    }

    public static function zrange(string $key, int $start, int $end): array
    {
        self::checkConnection();
        return self::$shouldBeConnected ? self::$redisConnection->zRange($key, $start, $end) : [];
    }

    public static function zrangebyscore(string $key, int $min, int $max): array
    {
        self::checkConnection();
        return self::$shouldBeConnected ? self::$redisConnection->zRangeByScore($key, $min, $max) : [];
    }

    public static function zscore(string $key, string $member)
    {
        self::checkConnection();
        return self::$shouldBeConnected ? self::$redisConnection->zScore($key, $member) : null;
    }

    public static function zrem(string $key, string $member): void
    {
        self::checkConnection();
        if (self::$shouldBeConnected) self::$redisConnection->zRem($key, $member);
    }

    /**
     * Set if Not eXists
     * @param $lockKey
     * @param $time
     * @param $ttl
     * @return void
     * @throws \Exception
     */
    public static function setnx($lockKey, $time)
    {
        self::checkConnection();
        if (self::$shouldBeConnected) return self::$redisConnection->setnx($lockKey, $time);
    }
    public static function getset($key, $value)
    {
        self::checkConnection();
        if (self::$shouldBeConnected) return self::$redisConnection->getSet($key, $value);
    }
    public static function closeConnection()
    {
        self::$shouldBeConnected = false;
        self::$redisConnection = null;
    }
}
