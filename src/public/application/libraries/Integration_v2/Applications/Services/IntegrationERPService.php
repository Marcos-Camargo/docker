<?php

namespace Integration_v2\Applications\Services;

require_once APPPATH . "libraries/Integration_v2/Applications/Services/ApiIntegrationService.php";

/**
 * Class IntegrationERPService
 * @package Integration_v2\Applications\Services
 * @property \Model_integration_erps $integrationErpsRepo
 * @property \Model_api_integrations $integrationRepo
 * @property ApiIntegrationService $apiIntegrationService
 */
class IntegrationERPService
{

    public function __construct(\Model_integration_erps $integrationErpsRepo, \Model_api_integrations $integrationRepo, \CI_Lang $langRepo)
    {
        $this->integrationErpsRepo = $integrationErpsRepo;
        $this->integrationRepo = $integrationRepo;
        $this->apiIntegrationService = new ApiIntegrationService($this->integrationRepo, $langRepo);
    }

    public function saveIntegrationApp(int $id = 0, array $data = [])
    {
        $id = $this->integrationErpsRepo->find(['name' => $data['name']])['id'] ?? $id;
        $id = empty($id) ? $this->integrationErpsRepo->find(['description LIKE' => "%{$data['name']}%"])['id'] ?? $id : $id;
        $aliases = $data['aliases'] ?? null;
        unset($data['aliases']);
        if ($this->integrationErpsRepo->save($id, $data)) {
            $appId = empty($id) ? $this->integrationErpsRepo->getInsertId() : $id;
            $this->apiIntegrationService->linkApiIntegrationsWithApp(array_merge($data, ['id' => $appId, 'aliases' => $aliases]));
        }
    }
}