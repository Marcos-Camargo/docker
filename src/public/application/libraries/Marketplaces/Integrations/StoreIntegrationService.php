<?php

namespace libraries\Marketplaces\Integrations;

use libraries\Marketplaces\Integrations\Providers\BaseIntegrationProvider;

/**
 * Class StoreIntegrationService
 * @package libraries\Marketplaces\Integrations
 * @property BaseIntegrationProvider $integrationProvider
 * @property \Model_integrations $integrationsModel
 */
class StoreIntegrationService
{
    protected $storeIntegrations = [];

    public function __construct(BaseIntegrationProvider $integrationProvider, \Model_integrations $integrationsModel)
    {
        $this->integrationsModel = $integrationsModel;
        $this->integrationProvider = $integrationProvider;
    }

    public function getStoreIntegrations(): array
    {
        return $this->storeIntegrations ?? [];
    }

    public function fetchStoreIntegrations(int $storeId): array
    {
        $criteria = array_merge(
            $this->integrationProvider->getIntegrationCriteria(['int.active' => 1]),
            ['int.store_id' => $storeId]
        );
        $integrations = $this->integrationsModel->getIntegrationsByCriteria($criteria);
        if (empty($integrations)) {
            $integrations = $this->integrationsModel->getIntegrationsByCriteria(array_merge($criteria, [
                'int.store_id' => 0,
                'int.company_id' => 1
            ]));
            if (empty($integrations)) {
                return [];
            }
        }
        $this->storeIntegrations = [];
        foreach ($integrations as $integration) {
            $storeInts = $this->integrationsModel->getIntegrationsByCriteria([
                'int.store_id' => $storeId,
                'int.active' => 1,
                'int.int_to' => $integration['int_to']
            ]);
            if (empty($storeInts)) continue;
            $this->storeIntegrations = array_merge($this->storeIntegrations, array_map(function ($i) use ($integration) {
                return $i + ['main_integration_id' => $integration['id']];
            }, $storeInts));
        }
        return $this->storeIntegrations;
    }

}