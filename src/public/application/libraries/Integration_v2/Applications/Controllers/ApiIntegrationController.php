<?php

namespace Integration_v2\Applications\Controllers;

use Integration_v2\Applications\Services\ApiIntegrationService;

require_once APPPATH . "libraries/Integration_v2/Applications/Services/ApiIntegrationService.php";

/**
 * Class ApiIntegrationController
 * @package Integration_v2\Applications\Controllers
 * @property \Model_api_integrations $integrationRepo
 * @property ApiIntegrationService $apiIntegrationService
 */
class ApiIntegrationController
{

    public function __construct(\Model_api_integrations $integrationRepo, \CI_Lang $langRepo)
    {
        $this->integrationRepo = $integrationRepo;
        $this->apiIntegrationService = new ApiIntegrationService($this->integrationRepo, $langRepo);
    }

    public function validateStoreIntegrationApplication(array $storeData, array $appData)
    {
        $this->apiIntegrationService->linkApiIntegrationsStoreWithApp($storeData, $appData);
    }
}