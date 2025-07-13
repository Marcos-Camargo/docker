<?php

namespace Integration_v2\Applications\Resources;

class IntegrationERPResource
{
    const HASH_ALGO = 'sha1';

    const GLOBAL_ENCRYPT_KEY = 'WnZu7x!A%D*G-KaPdSgVkXp2s5v8y/B?';


    public static function generateKeyApp(string $baseStr, string $encryptKey): string
    {
        return hash_hmac(self::HASH_ALGO, $baseStr, $encryptKey);
    }

    public static function getExistsIntegrationERPs(array $config = []): array
    {
        if (file_exists(dirname(__FILE__) . "/data/integration_erps.php"))
            return include dirname(__FILE__) . "/data/integration_erps.php";
        return [];
    }
}