<?php

namespace Logistic\Services;

use Logistic\Repositories\IntegrationLogisticConfigurations;
use Logistic\Repositories\IntegrationLogistics;

/**
 * Class IntegrationLogisticService
 * @package Logistic\Services
 * @property IntegrationLogistics $integrationLogistics
 * @property IntegrationLogisticConfigurations $integrationLogisticConfigurations
 */
class IntegrationLogisticService
{

    public function __construct(IntegrationLogistics $integrationLogistics, IntegrationLogisticConfigurations $integrationLogisticConfigurations)
    {
        $this->integrationLogistics = $integrationLogistics;
        $this->integrationLogisticConfigurations = $integrationLogisticConfigurations;
    }

    public function saveIntegrationLogisticConfigure(array $data)
    {
        $integrationName = $data['integration_name'] ?? $data['integration'] ?? $data['name'] ?? null;
        $integration = $this->integrationLogistics->getIntegrationsByName($integrationName);
        if (empty($integration)) throw new \Exception("Integração com logística '{$integrationName}' não encontrada! Por favor, entre em contato com o suporte!");

        $data = array_merge($data, [
            'integration_id' => $integration['id'] ?? null,
            'store_id' => ($data['store_id'] ?? 0) == 0 ? null : $data['store_id'],
            'use_seller' => $data['use_seller'] ?? $this->integrationLogistics->isSeller($integration),
            'use_sellercenter' => $data['use_sellercenter'] ?? $this->integrationLogistics->isSellerCenter($integration)
        ]);
        //Quando for uma integração nativa sellercenter (sgep, intelipost, freterapido, etc) utilizando as credenciais do seller
        if ($this->integrationLogisticConfigurations->isSeller($data)) {
            if ($this->integrationLogistics->isSellerCenter($integration)) {
                $data = array_merge($data, [
                    'use_seller' => true, 'use_sellercenter' => true
                ]);
            }
        }
        $configure = $this->integrationLogisticConfigurations->getIntegrationSeller($data['store_id'] ?? null);
        if (!empty($configure)
            && (
                $configure['integration'] !== $integrationName
                || $this->integrationLogisticConfigurations->isSeller($data) !== $this->integrationLogisticConfigurations->isSeller($configure)
                || $this->integrationLogisticConfigurations->isSellerCenter($data) !== $this->integrationLogisticConfigurations->isSellerCenter($configure)
            )
        ) {
            $result = $this->integrationLogisticConfigurations->removeIntegrationSellerCenter($configure['integration'], $data['store_id'] ?? null);
            if (!($result['_deleted'] ?? false)) throw new \Exception('Não foi possível excluir a configuração com logística existente.');
        }
        if (!empty($configure) && (
                $configure['integration'] === $integrationName
                && $this->integrationLogisticConfigurations->isSeller($data) === $this->integrationLogisticConfigurations->isSeller($configure)
                && $this->integrationLogisticConfigurations->isSellerCenter($data) === $this->integrationLogisticConfigurations->isSellerCenter($configure)
            )) {
            return $this->integrationLogisticConfigurations->updateIntegrationByIntegration($data['id'] ?? null, $data);
        }
        return $this->integrationLogisticConfigurations->createNewIntegrationByStore($data);
    }
}