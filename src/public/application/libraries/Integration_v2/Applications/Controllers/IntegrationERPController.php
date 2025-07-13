<?php

namespace Integration_v2\Applications\Controllers;

use Integration_v2\Applications\Resources\IntegrationERPResource;
use Integration_v2\Applications\Services\IntegrationERPService;

require_once APPPATH . "libraries/Integration_v2/Applications/Resources/IntegrationERPResource.php";
require_once APPPATH . "libraries/Integration_v2/Applications/Services/IntegrationERPService.php";

/**
 * Class IntegrationERPController
 * @package Integration_v2\Applications\Controllers
 * @property \Model_integration_erps $integrationAppRepo
 * @property \Model_api_integrations $integrationRepo
 * @property IntegrationERPService $IntegrationERPService
 */
class IntegrationERPController
{

    public function __construct(\Model_integration_erps $integrationErpsRepo, \Model_api_integrations $integrationRepo, \CI_Lang $langRepo)
    {
        $this->integrationAppRepo = $integrationErpsRepo;
        $this->integrationRepo = $integrationRepo;
        $this->IntegrationERPService = new IntegrationERPService($this->integrationAppRepo, $this->integrationRepo, $langRepo);
    }

    public function createIntegrationApps(array $config)
    {
        $integrationApps = IntegrationERPResource::getExistsIntegrationERPs($config);
        foreach ($integrationApps ?? [] as $integrationApp) {
            $this->IntegrationERPService->saveIntegrationApp(0, $integrationApp);
        }
    }
}