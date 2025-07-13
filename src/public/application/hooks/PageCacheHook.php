<?php

use App\Libraries\Cache\CacheManager;
use Predis\Client as RedisClient;

class PageCacheHook
{
    protected static $cacheKey = null;
    protected static $enabled = true;
    protected static $prefix = 'route_cache';
    protected static $redis = null;
    protected static $cacheableRoutes = [
        'dashboard' => 3, //cache de 3 minutos
    ];

    /**
     * Set a cacheable route with its TTL in minutes
     * 
     * @param string $route The route URL
     * @param int $ttlMinutes Time to live in minutes
     */
    public static function setCacheableRoute(string $route, int $ttlMinutes)
    {
        self::$cacheableRoutes[$route] = $ttlMinutes;
    }

    public static function setPrefix(string $prefix)
    {
        self::$prefix = $prefix;
    }

    protected static function connectRedis(): RedisClient
    {
        if (!CacheManager::$redisConnection) {
            $host = getenv('REDIS_HOST');
            $port = getenv('REDIS_PORT');

            CacheManager::$redisConnection = new RedisClient([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
            ]);
            CacheManager::$shouldBeConnected = true;
        }

        return CacheManager::$redisConnection;
    }

    protected static function generateCacheKey(): string
    {

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = trim(str_replace('/', ':', $uri), ':');

        $key = self::$prefix.':'.$host.':'.$uri.':'.strtolower($method);

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $payload = file_get_contents('php://input');
            if (empty($payload) && !empty($_POST)) {
                $payload = json_encode($_POST);
            }
            $key .= ':body_'.md5($payload);
        }

        //Utilizar cabeçalhos que começam com x- para utilizalos como parte da chave
        $xHeaders = [];
        foreach ($_SERVER as $k => $v) {
            if (stripos($k, 'HTTP_X_') === 0) {
                $headerName = str_ireplace('HTTP_X_', '', $k);
                $xHeaders[$headerName] = $v;
            }
        }

        if (!empty($xHeaders)) {
            $key .= ':xheaders_'.md5(json_encode($xHeaders));
        }

        //Utilizar o id da sessão do usuário logado como parte da chave
        $sessionId = $_COOKIE['ci_session'] ?? null;

        if ($sessionId) {
            $key .= ':sess_'.md5($sessionId);
        }

        return $key;
    }

    protected static function currentRouteIsCacheable(): bool
    {
        if (empty(self::$cacheableRoutes)) {
            return false;
        } // default: nada é cacheável

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = trim(parse_url($uri, PHP_URL_PATH), '/');

        foreach (array_keys(self::$cacheableRoutes) as $rota) {
            if (stripos($uriPath, $rota) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the TTL in seconds for the current route
     * 
     * @return int TTL in seconds
     */
    protected static function getCurrentRouteTTL(): int
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = trim(parse_url($uri, PHP_URL_PATH), '/');

        foreach (self::$cacheableRoutes as $rota => $ttlMinutes) {
            if (stripos($uriPath, $rota) === 0) {
                // Convert minutes to seconds
                return (int)$ttlMinutes * 60;
            }
        }

        // Default TTL: 10 minutes
        return 600;
    }

    public function checkCache()
    {
        if (!self::$enabled || !self::currentRouteIsCacheable()) {
            return;
        }

        self::$cacheKey = self::generateCacheKey();
        CacheManager::$redisConnection = self::connectRedis();

        $cached = CacheManager::$redisConnection->get(self::$cacheKey);

        if ($cached) {
            exit($cached);
        }
    }

    public static function saveCache($output = null, $ttl = null)
    {
        if (!self::$enabled || !self::$cacheKey || !self::currentRouteIsCacheable()) {
            return;
        }

        if (!$output) {
            $CI =& get_instance();
            $output = $CI->output->get_output();
        }

        if (!$output) {
            return;
        }

        // Use the provided TTL or get the TTL for the current route
        if ($ttl === null) {
            $ttl = self::getCurrentRouteTTL();
        }

        CacheManager::$redisConnection = self::connectRedis();
        CacheManager::$redisConnection->setex(self::$cacheKey, $ttl, $output);
    }

    public static function deleteCache(string $prefix)
    {
        CacheManager::$redisConnection = self::connectRedis();

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $pattern = self::$prefix.':'.$host.':'.$prefix.'*';

        $keys = CacheManager::$redisConnection->keys($pattern);

        if (!empty($keys)) {
            CacheManager::$redisConnection->del($keys);
        }

        return count($keys);

    }

}
