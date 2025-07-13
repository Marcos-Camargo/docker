<?php

namespace Logistic\Repositories\CI\v1;

/**
 * Class IntegrationLogisticConfigurations
 * @package Logistic\Repositories\CI\v1
 * @property \Model_integration_logistic $integrationLogisticConfiguration
 */
class IntegrationLogisticConfigurations extends BaseCIRepository implements \Logistic\Repositories\IntegrationLogisticConfigurations
{
    protected $fillable = [
        'id' => [],
        'id_integration' => ['integration_id'],
        'integration' => ['name', 'integration_name'],
        'credentials' => [],
        'active' => [],
        'store_id' => [],
        'user_created' => [],
        'user_updated' => [],
        'date_updated' => ['created_at'],
        'date_created' => ['updated_at'],
        'disable' => [],
    ];

    public function __construct()
    {
        $this->load->model('Model_integration_logistic', 'integrationLogisticConfiguration');
    }

    public function getIntegrationByName(string $name, int $store_id): ?array
    {
        return $this->integrationLogisticConfiguration->getIntegrationByName($name, $store_id) ?? [];
    }

    public function createNewIntegrationByStore(array $data): bool
    {
        $data['use_sellercenter'] = $this->isSellerCenter($data) && $this->isSeller($data) || $this->isSellerCenter($data);
        $data['use_seller'] = !$this->isSellerCenter($data);
        return $this->integrationLogisticConfiguration->createNewIntegrationByStore($this->handleFillableFields($data));
    }

    public function updateIntegrationByIntegration($id, $data): bool
    {
        $data['use_sellercenter'] = $this->isSellerCenter($data) && $this->isSeller($data) || $this->isSellerCenter($data);
        $data['use_seller'] = !$this->isSellerCenter($data);
        return $this->integrationLogisticConfiguration->updateIntegrationByIntegration($id, $this->handleFillableFields($data));
    }

    public function removeIntegrationSellerCenter($integration, $store_id): array
    {
        return $this->integrationLogisticConfiguration->removeIntegrationSellerCenter($integration, $store_id) ?? [];
    }

    public function removeIntegrationSeller($integration): array
    {
        return $this->integrationLogisticConfiguration->removeIntegrationSeller($integration) ?? [];
    }

    public function removeAllIntegrationSellerCenter(): array
    {
        return $this->integrationLogisticConfiguration->removeAllIntegrationSellerCenter() ?? [];
    }

    public function removeAllIntegrationSeller(): array
    {
        return $this->integrationLogisticConfiguration->removeAllIntegrationSeller() ?? [];
    }

    public function getIntegrationSeller($storeId): ?array
    {
        $configure = $this->integrationLogisticConfiguration->getIntegrationSeller($storeId) ?? [];
        return !empty($configure['integration'] ?? '') ? array_merge($configure, [
            'integration' => $configure['integration'],
            'sellercenter' => $configure['credentials'] === null,
            'seller' => $configure['credentials'] !== null,
            'credentials' => json_decode($configure['credentials']),
        ]) : [];
    }

    public function getIntegrationLogistic(int $store, int $status = null): ?array
    {
        return $this->integrationLogisticConfiguration->getIntegrationLogistic($store, $status) ?? [];
    }

    public function removeIntegrationByStore(int $storeId): bool
    {
        return $this->integrationLogisticConfiguration->removeIntegrationByStore($storeId);
    }

    public function getLogisticAvailableBySellerCenter(string $integration): bool
    {
        return $this->integrationLogisticConfiguration->getLogisticAvailableBySellerCenter($integration);
    }

    public function getStoresIntegrationsSellerCenterByInt($integration): array
    {
        return $this->integrationLogisticConfiguration->getStoresIntegrationsSellerCenterByInt((int)$integration) ?? [];
    }

    public function getIntegrationsByStoreId($store_id): ?array
    {
        return $this->integrationLogisticConfiguration->getIntegrationsByStoreId($store_id) ?? [];
    }

    public function getIntegrationLogisticeById($id): array
    {
        return $this->integrationLogisticConfiguration->getIntegrationLogisticeById($id) ?? [];
    }

    public function isSeller(array $configure): bool
    {
        return (!!($configure['use_seller'] ?? false) === true) || (!!($configure['seller'] ?? false) === true);
    }

    public function isSellerCenter(array $configure): bool
    {
        return (!!($configure['use_sellercenter'] ?? false) === true) || (!!($configure['sellercenter'] ?? false) === true);
    }
}