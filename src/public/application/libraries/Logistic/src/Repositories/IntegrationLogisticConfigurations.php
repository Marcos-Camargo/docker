<?php

namespace Logistic\Repositories;

interface IntegrationLogisticConfigurations
{

    public function getIntegrationByName(string $name, int $store_id): ?array;

    public function createNewIntegrationByStore(array $data): bool;

    public function updateIntegrationByIntegration($id, array $data): bool;

    public function removeIntegrationSellerCenter($integration, $store_id): array;

    public function removeIntegrationSeller($integration): array;

    public function removeAllIntegrationSellerCenter(): array;

    public function removeAllIntegrationSeller(): array;

    public function getIntegrationSeller($storeId): ?array;

    public function getIntegrationLogistic(int $store, int $status = null): ?array;

    public function removeIntegrationByStore(int $storeId): bool;

    public function getLogisticAvailableBySellerCenter(string $integration): bool;

    public function getStoresIntegrationsSellerCenterByInt($integration): array;

    public function getIntegrationsByStoreId(int $store_id): ?array;

    public function getIntegrationLogisticeById($id): array;

    public function isSeller(array $configure): bool;

    public function isSellerCenter(array $configure): bool;
}