<?php


namespace Integration_v2\Applications\Services;

/**
 * Class ApiIntegrationService
 * @package Integration_v2\Applications\Services
 * @property \Model_api_integrations $integrationRepo
 * @property \CI_Lang $langRepo
 */
class ApiIntegrationService
{
    public function __construct(\Model_api_integrations $integrationRepo, \CI_Lang $lang)
    {
        $this->integrationRepo = $integrationRepo;
        $this->langRepo = $lang;
    }

    public function linkApiIntegrationsWithApp(array $appData)
    {
        $integrations = $this->integrationRepo->getDataByIntegration($appData['aliases'] ?? $appData['name']);
        foreach (($integrations ?: []) as $integration) {
            $this->linkApiIntegrationToApp($integration['id'], $appData['id']);
        }
    }

    public function linkApiIntegrationToApp(int $apiIntegrationId, int $integrationAppId)
    {
        $this->integrationRepo->update($apiIntegrationId, ['integration_erp_id' => $integrationAppId]);
    }

    public function linkApiIntegrationsStoreWithApp(array $integrationData, array $appData)
    {
        $integration = $this->integrationRepo->getIntegrationByStoreId($integrationData['store_id']);
        if (!empty($integration)) {
            if (strcasecmp($integration['integration'], $appData['name']) !== 0) {
                throw new \Exception(sprintf($this->langRepo->line('api_app_store_already_integrated'), $integration['store_id'], $integration['store_name'], $integration['app_name']));
            }
            if (!empty($integration['hash']) && strcmp($integration['hash'], $appData['hash']) !== 0) {
                throw new \Exception(sprintf($this->langRepo->line('api_app_not_match_store_integration'), $appData['app_id'], $integration['app_name'], $integration['store_id'], $integration['store_name']));
            }
            if (empty($integration['integration_erp_id'])) {
                $this->linkApiIntegrationToApp($integration['id'], $appData['id']);
            }
            return;
        }
        $this->createApiIntegrationByApp($integrationData, $appData);
    }

    protected function createApiIntegrationByApp(array $integrationData, array $appData)
    {
        $this->integrationRepo->create([
            'integration_erp_id' => $appData['id'],
            'store_id' => $integrationData['store_id'],
            'user_id' => $integrationData['user_id'] ?? 0,
            'status' => $integrationData['status'] ?? $appData['active'] ?? \Model_api_integrations::ACTIVE_STATUS,
            'description_integration' => $appData['description'],
            'integration' => $appData['name'],
            'credentials' => json_encode((object)[]),
        ]);
    }
}