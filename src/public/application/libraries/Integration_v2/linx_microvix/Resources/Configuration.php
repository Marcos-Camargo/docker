<?php

namespace Integration_v2\linx_microvix\Resources;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Integration_v2\Applications\Resources\IntegrationConfiguration;

/**
 * Configurações para a integração Linx-Microvix.
 * @package Integration_v2\linx_microvix\Resources
 */
class Configuration extends IntegrationConfiguration
{
    const integration = "linx_microvix";

    protected $integration = SELF::integration;

    public function __construct() {}

    // Busca a URL de saida.
    public function getUrlSaida()
    {
        return $this->getIntegrationConfig('api_url_exit') ?? $this->getSettingConfig('api_url_exit_microvix') ?? '';
    }

    // Busca a URL de entrada.
    public function getUrlEntrada()
    {
        return $this->getIntegrationConfig('api_url') ?? $this->getSettingConfig('api_url_microvix') ?? '';
    }

    public function getChaveAcesso()
    {
        return $this->getIntegrationConfig('access_key') ?? $this->getSettingConfig('access_key') ?? '';
    }
}
