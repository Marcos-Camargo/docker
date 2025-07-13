<?php

namespace Logistic\Repositories\MS\v1;

use Logistic\Repositories\MSRepositoryException;
use Microservices\v1\Logistic\ShippingCarrier;
use Microservices\v1\Logistic\ShippingIntegrator;

/**
 * Class IntegrationLogisticConfigurations
 * @package Logistic\Repositories\MS\v1
 * @property ShippingCarrier $shippingCarrier
 * @property ShippingIntegrator $shippingIntegrator
 */
class IntegrationLogisticConfigurations implements \Logistic\Repositories\IntegrationLogisticConfigurations
{

    public function __construct()
    {
        $this->shippingCarrier = new ShippingCarrier();
        $this->shippingIntegrator = new ShippingIntegrator();
        if (!$this->shippingCarrier->use_ms_shipping && !$this->shippingIntegrator->use_ms_shipping) {
            throw new MSRepositoryException("Parâmetro 'use_ms_shipping' invativo!");
        }
    }

    public function getIntegrationByName(string $name, int $store_id): ?array
    {
        if ($this->shippingCarrier->use_ms_shipping) {
            try {
                $this->shippingCarrier->setStore($store_id);
                $configure = $this->shippingCarrier->getConfigure($name);
                if ($configure->active ?? false) {
                    return array_merge((array)$configure, ['type' => ShippingCarrier::LOGISTIC_TYPE]);
                }
            } catch (\Throwable $e) {

            }
        }
        if ($this->shippingIntegrator->use_ms_shipping) {
            try {
                $this->shippingIntegrator->setStore($store_id);
                $configure = $this->shippingIntegrator->getConfigure($name);
                if ($configure->active ?? false) {
                    return array_merge((array)$configure, ['type' => ShippingIntegrator::LOGISTIC_TYPE]);
                }
            } catch (\Throwable $e) {

            }
        }
        return [];
    }

    public function createNewIntegrationByStore(array $data): bool
    {
        $saved = $this->saveIntegrationConfigure($data);
        if ($saved && ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica)) {
            $searchIntegration = array_merge($data, []);
            $searchIntegration['use_sellercenter'] = $this->isSellerCenter($searchIntegration) && $this->isSeller($searchIntegration) || $this->isSellerCenter($searchIntegration);
            $searchIntegration['use_seller'] = !$this->isSellerCenter($searchIntegration);
            $integrations = (new \Logistic\Repositories\CI\v1\IntegrationLogistics())->getIntegrationsInUseSeller();
            foreach ($integrations ?? [] as $integration) {
                if (($integration['name'] == ($searchIntegration['integration'] ?? $searchIntegration['integration_name'] ?? $searchIntegration['name'] ?? ''))
                    && ($this->isSeller($integration) === $this->isSeller($searchIntegration))
                    && ($this->isSellerCenter($integration) === $this->isSellerCenter($searchIntegration))) {
                    $data['id_integration'] = $integration['id'] ?? null;
                    break;
                }
            }
            if (!empty($data['id_integration'])) (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->createNewIntegrationByStore($data);
        }
        return $saved;
    }

    public function updateIntegrationByIntegration($id, array $data): bool
    {
        $saved = $this->saveIntegrationConfigure($data);
        if ($saved && ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica)) {
            if (empty($id)) {
                $configure = (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->getIntegrationByName($data['integration'] ?? $data['integration_name'] ?? $data['name'] ?? '', $data['store_id'] ?? null);
                if (empty($id = ($configure['id'] ?? null))) return true;
                $data['id_integration'] = $configure['id_integration'];
            }
            (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->updateIntegrationByIntegration($id, $data);
        }
        return $saved;
    }

    protected function saveIntegrationConfigure(array $data): bool
    {
        try {
            if ($this->shippingIntegrator->use_ms_shipping && ($this->isSeller($data) && !$this->isSellerCenter($data))) {
                return $this->shippingIntegrator->saveConfigure($data);
            } elseif ($this->shippingCarrier->use_ms_shipping && $this->isSellerCenter($data)) {
                $data['use_sellercenter'] = $this->isSellerCenter($data) && $this->isSeller($data) || $this->isSellerCenter($data);
                $data['use_seller'] = !$this->isSellerCenter($data);
                return $this->shippingCarrier->saveConfigure($data);
            }
        } catch (\Throwable $e) {
        }
        return false;
    }

    public function removeIntegrationConfigureByStoreIfExists(int $storeId): bool
    {
        $configure = $this->getIntegrationSeller($storeId);
        if (empty($configure)) return true;
        return $this->removeIntegrationConfigure($configure['type'], $configure['integration'], $storeId);
    }

    public function removeIntegrationSellerCenter($integration, $store_id): array
    {
        $configure = $this->getIntegrationByName($integration, $store_id);
        if (empty($configure)) return [];
        $removed = $this->removeIntegrationConfigure($configure['type'], $integration, $store_id);
        return array_merge($configure, ['_deleted' => $removed]);
    }

    protected function removeIntegrationConfigure(string $type, string $integration, int $storeId): bool
    {
        $removed = false;
        if ($type == ShippingCarrier::LOGISTIC_TYPE) {
            try {
                $removed = $this->shippingCarrier
                    ->setStore($storeId)
                    ->removeConfigure($integration);
            } catch (\Throwable $e) {

            }
        } elseif ($type == ShippingIntegrator::LOGISTIC_TYPE) {
            try {
                $removed = $this->shippingIntegrator
                    ->setStore($storeId)
                    ->removeConfigure($integration);
            } catch (\Throwable $e) {

            }
        }
        if ($removed && ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica)) {
            (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->removeIntegrationSellerCenter($integration, $storeId);
        }
        return $removed;
    }

    public function removeIntegrationSeller($integration): array
    {
        if ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica) {
            (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->removeIntegrationSeller($integration);
        }
    }

    public function removeAllIntegrationSellerCenter(): array
    {
        if ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica) {
            (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->removeAllIntegrationSellerCenter();
        }
    }

    public function removeAllIntegrationSeller(): array
    {
        if ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica) {
            (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->removeAllIntegrationSellerCenter();
        }
    }

    public function getIntegrationSeller($storeId): ?array
    {
        $configure = [];
        if (!empty($storeId) && $this->shippingIntegrator->use_ms_shipping) { //storeId '0' ou 'null' é admin e não tem configuração no integrator
            try {
                $this->shippingIntegrator->setStore($storeId);
                $configure = array_merge((array)$this->shippingIntegrator->getConfigures(), ['type' => ShippingIntegrator::LOGISTIC_TYPE]);
            } catch (\Throwable $e) {
            }
        }

        if (empty($configure->integration_name ?? '') && $this->shippingCarrier->use_ms_shipping) {
            try {
                $this->shippingCarrier->setStore($storeId);
                $configure = array_merge((array)$this->shippingCarrier->getConfigures(), ['type' => ShippingCarrier::LOGISTIC_TYPE]);
            } catch (\Throwable $e) {
            }
        }

        return !empty($configure['integration_name'] ?? '') ? array_merge($configure, [
            'integration' => $configure['integration_name'],
            'sellercenter' => $configure['type_contract'] === 'sellercenter',
            'seller' => $configure['type_contract'] === 'seller',
            'credentials' => $configure['credentials']
        ]) : [];
    }

    public function getIntegrationLogistic(int $store, int $status = null): ?array
    {
        $configure = $this->getIntegrationSeller($store);
        if (empty($configure) || ($status !== null && $configure['active'] !== $status)) return [];

        $integration = [];
        if ($this->isSellerCenter($configure) && $this->shippingCarrier->use_ms_shipping) {
            try {
                $integration = (array)$this->shippingCarrier->getIntegration($configure['integration']);
            } catch (\Throwable $e) {

            }
        }
        if (empty($integration) && ($this->isSeller($configure) && $this->shippingIntegrator->use_ms_shipping)) {
            try {
                $integration = (array)$this->shippingIntegrator->getIntegration($configure['integration']);
            } catch (\Throwable $e) {
            }
        }

        return !empty($integration) ? array_merge($configure, [
            'description' => $integration->name,
            'id_ils' => $integration->name,
            'use_seller' => $integration->use_seller,
            'store_id' => $store,
            'name' => null
        ]) : [];
    }

    public function removeIntegrationByStore(int $storeId): bool
    {
        $removed = $this->removeIntegrationConfigureByStoreIfExists($storeId);
        if ($removed && ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica)) {
            (new \Logistic\Repositories\CI\v1\IntegrationLogisticConfigurations())->removeIntegrationByStore($storeId);
        }
    }

    public function getLogisticAvailableBySellerCenter(string $integration): bool
    {
        try {
            $integration = $this->shippingCarrier->getIntegration($integration);
            if (!($integration->use_seller ?? true) && ($integration->active ?? false)) {
                $this->shippingCarrier->setStore(null);
                $configuration = $this->shippingCarrier->getConfigure($integration->name ?? '');
                return $configuration->active ?? false;
            }
        } catch (\Throwable $e) {

        }
        return false;
    }

    public function getStoresIntegrationsSellerCenterByInt($integration): array
    {
        // Não há implementação no MS para obter todas as configurações de uma integração
        return [];
    }

    public function getIntegrationsByStoreId(int $store_id): ?array
    {
        $configure = $this->getIntegrationSeller($store_id);
        if (empty($configure)) return [];
        $configure['credentials'] = json_encode($configure['credentials']);
        return $configure;
    }

    public function getIntegrationLogisticeById($id): array
    {
        return [];
    }

    public function isSeller(array $configure): bool
    {
        return (($configure['type'] ?? '') === ShippingIntegrator::LOGISTIC_TYPE)
            || !!($configure['seller'] ?? false) === true
            || !!($configure['use_seller'] ?? false) === true;
    }

    public function isSellerCenter(array $configure): bool
    {
        return (($configure['type'] ?? '') === ShippingCarrier::LOGISTIC_TYPE)
            || !!($configure['sellercenter'] ?? false) === true
            || !!($configure['use_sellercenter'] ?? false) === true;
    }
}