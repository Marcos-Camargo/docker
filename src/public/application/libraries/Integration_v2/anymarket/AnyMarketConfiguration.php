<?php

namespace Integration\Integration_v2\anymarket;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';
use Integration_v2\Applications\Resources\IntegrationConfiguration;

class AnyMarketConfiguration extends IntegrationConfiguration
{

    private $accessToken;

    private $parameters;

    protected $integration = 'anymarket';

    public function __construct(
        ?string $accessToken = null,
        ?array  $parameters = []
    )
    {
        $this->accessToken = $accessToken;
        $this->parameters = $parameters;
    }

    public function getHost()
    {
        return $this->getIntegrationConfig('api_url') ?? $this->getSettingConfig('url_anymarket') ?? '';
    }

    public function getAppId()
    {
        return $this->getIntegrationConfig('application_id') ?? $this->getSettingConfig('app_id_anymarket') ?? '';
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getParameterValueByName($name)
    {
        return $this->parameters[$name] ?? null;
    }
}