<?php

namespace Integration_v2\magalu\Resources;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Integration_v2\Applications\Resources\IntegrationConfiguration;

/**
 * Class Configuration
 * @package Integration_v2\magalu\Resources
 */
class Configuration extends IntegrationConfiguration
{
    const API_URL = "https://b2b.magazineluiza.com.br/api/v1";

    const INTEGRATION = 'magalu';

    protected $integration = self::INTEGRATION;

    public function __construct()
    {

    }

}