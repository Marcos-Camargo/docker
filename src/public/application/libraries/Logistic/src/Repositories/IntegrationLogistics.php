<?php

namespace Logistic\Repositories;

interface IntegrationLogistics
{

    public function getIntegrationsSellerActiveNotUse(): array;

    public function getIntegrationsSellerCenterActiveNotUse(): array;

    public function getIntegrationsByName(string $name): array;

    public function getIntegrationsInUseSellerCenter(): array;

    public function getIntegrationsInUseSeller(): array;

    public function createNewIntegrationLogistic(array $data): bool;

    public function updateIntegrationsInUse(array $data, $id): array;

    public function updateAllIntegrationsInUse(array $data): bool;

    public function getAllIntegrationSellerCenter(): array;

    public function getAllIntegration(): array;

    public function isSeller(array $integration): bool;

    public function isSellerCenter(array $integration): bool;
}