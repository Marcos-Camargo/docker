<?php

namespace App\Libraries\Cache;

use Psr\SimpleCache\CacheInterface;

class RedisCacheHandler implements CacheInterface
{
    private $defaultTtl;

    public function __construct($defaultTtl = 3600)
    {
        $this->defaultTtl = $defaultTtl;
    }

    public function get($key, $default = null)
    {
        $value = CacheManager::get($key);
        if ($value) {
            try {
                return unserialize($value);
            } catch (\Throwable $e) {
                log_message('error', 'Failed to unserialize Redis key ' . $key . ': ' . $e->getMessage());
                return $default;
            }
        }
        return $default;
    }

    public function getLock($key)
    {
        return CacheManager::get($key);
    }

    public function set($key, $value, $ttl = null): void
    {
        CacheManager::set($key, serialize($value));
        if ($ttl !== null) {
            CacheManager::setex($key, serialize($value), $ttl);
        } else {
            CacheManager::setex($key, serialize($value), $this->defaultTtl);
        }
    }

    public function setex($key, $value, $ttl = null): void
    {
        CacheManager::setex($key, serialize($value), $ttl ?? $this->defaultTtl);
    }

    /**
     * @param $key
     * @return bool
     */
    public function delete($key): bool
    {
        CacheManager::delete([$key]);
        return true;
    }

    public function clear(): bool
    {
        return false;
    }

    public function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }
        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key): bool
    {
        return CacheManager::get($key) !== null;
    }

    public function zadd(string $key, int $score, string $member): void
    {
        CacheManager::zadd($key, $score, $member);
    }

    public function zrange(string $key, int $start, int $end): array
    {
        return CacheManager::zrange($key, $start, $end);
    }

    public function zrangebyscore(string $key, int $min, int $max): array
    {
        return CacheManager::zrangebyscore($key, $min, $max);
    }

    public function zscore(string $key, string $member)
    {
        return CacheManager::zscore($key, $member);
    }

    public function zrem(string $key, string $member): void
    {
        CacheManager::zrem($key, $member);
    }

    public function getAllKeysByPrefix(string $prefix)
    {
        return CacheManager::getAllKeysByPrefix($prefix);
    }

    public function setnx($lockKey, $time)
    {
        return CacheManager::setnx($lockKey, $time);
    }

    public function getset($key, $value)
    {
        return CacheManager::getset($key, $value);
    }
}
