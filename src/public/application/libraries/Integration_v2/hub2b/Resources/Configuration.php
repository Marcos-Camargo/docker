<?php

namespace Integration_v2\hub2b\Resources;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Integration_v2\Applications\Resources\IntegrationConfiguration;

/**
 * Class Configuration
 * @package Integration_v2\hub2b\Resources
 */
class Configuration extends IntegrationConfiguration
{
    const INTEGRATION = 'hub2b';

    const API_V1 = "https://webservice.hub2b.com.br/RestServiceImpl.svc";

    const API_V2 = "https://rest.hub2b.com.br";

    protected $integration = 'hub2b';

    public function __construct()
    {
    }

    public static function getApiV1URL(): string
    {
        return Configuration::API_V1;
    }

    public static function getApiV2URL(): string
    {
        return Configuration::API_V2;
    }

    public function getClientId(): string
    {
        return $this->getIntegrationConfig('client_id') ?? $this->getSettingConfig('client_id') ?? '';
    }

    public function getClientSecret(): string
    {
        return $this->getIntegrationConfig('client_secret') ?? $this->getSettingConfig('client_secret') ?? '';
    }

    public function getAuthScope(): string
    {
        return $this->getIntegrationConfig('auth_scope') ?? $this->getSettingConfig('auth_scope') ?? '';
    }

    public function getMarketPlaceName(): string
    {
        return $this->getIntegrationConfig('marketplace_name') ?? $this->getSettingConfig('marketplace_name') ?? '';
    }

    public function getMarketPlaceId(): string
    {
        return $this->getIntegrationConfig('marketplace_id') ?? $this->getSettingConfig('marketplace_id') ?? '';
    }

    public function getSalesChannelId(): string
    {
        return $this->getIntegrationConfig('sales_channel_id') ?? $this->getSettingConfig('sales_channel_id') ?? '';
    }

    protected function getSettingConfig(string $field)
    {
        if (!empty($this->settingConfig[$field] ?? '')) return $this->settingConfig[$field];
        $settingConfig = parent::getSettingConfig('hub2b_app_config') ?? '{}';
        $settingConfig = json_decode($settingConfig, true);
        if (!empty($settingConfig)) {
            $this->settingConfig = $settingConfig;
        }
        return parent::getSettingConfig($field);
    }
}