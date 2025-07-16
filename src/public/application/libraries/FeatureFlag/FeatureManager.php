<?php

namespace App\Libraries\FeatureFlag;

use App\Libraries\Cache\RedisCacheHandler;
use GuzzleHttp\Client;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\UnleashBuilder;

class FeatureManager
{

    /** @var \Unleash\Client\Unleash */
    public static $client = null;
    public static $redisHost = null;
    public static $redisPort = null;
    private static $timeout = 0.700;

    /**
     * Creates and configures an HTTP client with timeout settings
     *
     * @param  int  $timeout  Timeout in seconds
     * @return Client
     */
    private static function createHttpClient($timeout = null): Client
    {
        if ($timeout === null) {
            $timeout = self::$timeout;
        }

        return new Client([
            'verify' => false, // no verify ssl
            'timeout' => $timeout,
            'connect_timeout' => $timeout
        ]);
    }

    /**
     * Set the timeout value for HTTP requests
     *
     * @param  int  $timeout  Timeout in seconds
     */
    public static function setTimeout(int $timeout): void
    {
        self::$timeout = $timeout;
    }

    private static function initUnleash($cacheInMinutes = 10): void
    {
        // Configuração do cache Redis
        $cacheHandler = new RedisCacheHandler($cacheInMinutes * 60);

        $appUrl = getenv('UNLEASH_API_URL') ?: get_instance()->model_settings->getValueIfAtiveByName('unleash_api_url');
        $token = getenv('UNLEASH_API_TOKEN') ?: get_instance()->model_settings->getValueIfAtiveByName('unleash_api_token');
        if (!$appUrl) {
            $appUrl = 'https://unleash.conectala.com.br/api/';
        }
        if (!$token) {
            $token = 'default:development.cfd771fa8449f0ab5fd18240e4ec95788eab71c2b01b6e6150673cbf';
            if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
                $token = 'default:production.98d3c5647a9d0c573180de1d57d427eaa9ac9858cd22213c1713b107';
            }
        }

        // Create HTTP client with timeout
        $httpClient = self::createHttpClient();

        self::$client = UnleashBuilder::create()
            ->withAppName(get_instance()->model_settings->getValueIfAtiveByName('sellercenter'))
            ->withAppUrl($appUrl)
            ->withHeader('Authorization', $token)
            ->withInstanceId('1')
            ->withMetricsInterval($cacheInMinutes * 60)
            ->withCacheHandler($cacheHandler)
            ->withCacheTimeToLive($cacheInMinutes * 60)
            ->withHttpClient($httpClient)
            ->build();
    }

    private static function loadConfigurations(): void
    {

        $endpoint_redis_quote = get_instance()->model_settings->getValueIfAtiveByName('endpoint_redis_quote');
        if ($endpoint_redis_quote) {
            self::$redisHost = $endpoint_redis_quote;
        }
        $port_redis_quote = get_instance()->model_settings->getValueIfAtiveByName('port_redis_quote');
        if ($port_redis_quote) {
            self::$redisPort = $port_redis_quote;
        }

    }

    private static function buildContext(): UnleashContext
    {
        $instance = get_instance();
        $userId = null;
        if ($instance && isset($instance->session)){
            $userId = $instance->session->userdata('user_id');
        }
        return (new UnleashContext())
            ->setCustomProperty('sellercenter', get_instance()->model_settings->getValueIfAtiveByName('sellercenter'))
            ->setCurrentUserId($userId);
    }

    /**
     * Check if a feature is available
     *
     * @param  string  $featureName  Name of the feature to check
     * @param  int  $cacheInMinutes  Cache time in minutes
     * @param  int|null  $timeout  Timeout in seconds for the HTTP request
     * @return bool
     */
    public static function isFeatureAvailable(string $featureName, $cacheInMinutes = 10, $timeout = null): bool
    {
        // Set custom timeout if provided
        if ($timeout !== null) {
            self::setTimeout($timeout);
        }

        if (is_null(self::$client) || $cacheInMinutes != 10) {
            self::initUnleash($cacheInMinutes);
        }

        try {
            return self::$client->isEnabled($featureName, self::buildContext());
        }catch (\Throwable $e){
            return false;
        }
    }


}
