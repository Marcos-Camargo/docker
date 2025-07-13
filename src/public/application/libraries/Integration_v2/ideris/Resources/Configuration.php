<?php

namespace Integration_v2\ideris\Resources;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Integration_v2\Applications\Resources\IntegrationConfiguration;

/**
 * Class Configuration
 * @package Integration_v2\ideris\Resources
 */
class Configuration extends IntegrationConfiguration
{
    const API_URL = "https://apiv3.ideris.com.br";

    const INTEGRATION = 'ideris';

    protected $integration = self::INTEGRATION;

    public function __construct()
    {

    }

}