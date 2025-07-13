<?php

namespace Integration_v2\tray\Resources;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Integration_v2\Applications\Resources\IntegrationConfiguration;

/**
 * Class Configuration
 * @package Integration_v2\tray\Resources
 */
class Configuration extends IntegrationConfiguration
{
    const API_URL = "https://api.commerce.tray.com.br";

    protected $integration = 'tray';

    public function __construct()
    {

    }

    public static function getAuthConfirmationURL(): string
    {
        return str_replace('http://', 'https://', str_replace('conectala.tec.br', 'conectala.com.br',
            base_url("Api/Integration_v2/tray/OAuth/authConfirm")));
    }

    public static function getOAuthCallbackURL(): string
    {
        return str_replace('http://', 'https://', str_replace('conectala.tec.br', 'conectala.com.br',
            base_url("Api/Integration_v2/tray/OAuth/auth")));
    }

    public function getConsumerKey(): string
    {
        return $this->getIntegrationConfig('app_consumer_key') ?? $this->getSettingConfig('consumer_key_tray_app') ?? '';
    }

    public function getConsumerSecret(): string
    {
        return $this->getIntegrationConfig('app_consumer_secret') ?? $this->getSettingConfig('consumer_secret_tray_app') ?? '';
    }

}