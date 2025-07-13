<?php

namespace Logistic\Repositories\CI\v1;

/**
 * Class integrationLogisticsModel
 * @package Logistic\Repositories\CI\v1
 * @property \Model_integration_logistic $integrationLogisticsModel
 */
class IntegrationLogistics extends BaseCIRepository implements \Logistic\Repositories\IntegrationLogistics
{

    protected $fillable = [
        'id' => [],
        'name' => ['integration', 'integration_name'],
        'description' => [],
        'use_sellercenter' => [],
        'use_seller' => [],
        'active' => [],
        'fields_form' => ['form_fields'],
        'user_created' => [],
        'user_updated' => [],
        'date_updated' => ['created_at'],
        'date_created' => ['updated_at'],
    ];

    public function __construct()
    {
        $this->load->model('model_integration_logistic', 'integrationLogisticsModel');
    }

    public function getIntegrationsSellerActiveNotUse(): array
    {
        return $this->integrationLogisticsModel->getIntegrationsSellerActiveNotUse() ?? [];
    }

    public function getIntegrationsSellerCenterActiveNotUse(): array
    {
        return $this->integrationLogisticsModel->getIntegrationsSellerCenterActiveNotUse() ?? [];
    }

    public function getIntegrationsByName(string $name): array
    {
        return $this->integrationLogisticsModel->getIntegrationsByName($name) ?? [];
    }

    public function getIntegrationsInUseSellerCenter(): array
    {
        return $this->integrationLogisticsModel->getIntegrationsInUseSellerCenter() ?? [];
    }

    public function getIntegrationsInUseSeller(): array
    {
        return $this->integrationLogisticsModel->getIntegrationsInUseSeller() ?? [];
    }

    public function updateIntegrationsInUse(array $data, $id): array
    {
        return $this->integrationLogisticsModel->updateIntegrationsInUse($this->handleFillableFields($data), $id) ?? [];
    }

    public function updateAllIntegrationsInUse(array $data): bool
    {
        return $this->integrationLogisticsModel->updateAllIntegrationsInUse($this->handleFillableFields($data));
    }

    public function getAllIntegrationSellerCenter(): array
    {
        return $this->integrationLogisticsModel->getAllIntegrationSellerCenter() ?? [];
    }

    public function getAllIntegration(): array
    {
        return $this->integrationLogisticsModel->getAllIntegration() ?? [];
    }

    public function createNewIntegrationLogistic(array $data): bool
    {
        return $this->integrationLogisticsModel->createNewIntegrationLogistic($this->handleFillableFields($data));
    }

    public function isSeller(array $integration): bool
    {
        return (!!($integration['use_seller'] ?? false) === true) || (!!($integration['seller'] ?? false) === true);
    }

    public function isSellerCenter(array $integration): bool
    {
        return (!!($integration['use_sellercenter'] ?? false) === true) || (!!($integration['sellercenter'] ?? false) === true);
    }
}