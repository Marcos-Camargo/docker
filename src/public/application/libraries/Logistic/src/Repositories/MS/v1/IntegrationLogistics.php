<?php

namespace Logistic\Repositories\MS\v1;


use Logistic\Repositories\MSRepositoryException;
use Microservices\v1\Logistic\ShippingCarrier;
use Microservices\v1\Logistic\ShippingIntegrator;

/**
 * Class IntegrationLogistics
 * @package Logistic\Repositories\MS\v1
 * @property ShippingCarrier $shippingCarrier
 * @property ShippingIntegrator $shippingIntegrator
 * @property \Logistic\Repositories\IntegrationLogistics $integrationLogisticsReplica
 * @property \Logistic\Repositories\IntegrationLogisticConfigurations $integrationLogisticConfigurationsReplica
 */
class IntegrationLogistics implements \Logistic\Repositories\IntegrationLogistics
{

    public function __construct()
    {
        $this->shippingCarrier = new ShippingCarrier();
        $this->shippingIntegrator = new ShippingIntegrator();
        if (!$this->shippingCarrier->use_ms_shipping && !$this->shippingIntegrator->use_ms_shipping) {
            throw new MSRepositoryException("Parâmetro 'use_ms_shipping' invativo!");
        }
    }

    public function getIntegrationsSellerActiveNotUse(): array
    {
        $integrations = [];
        if ($this->shippingCarrier->use_ms_shipping) {
            try {
                foreach ($this->shippingCarrier->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSellerCenter((array)$integration)) {
                        $integrations[] = [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingCarrier::LOGISTIC_TYPE
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if ($this->shippingIntegrator->use_ms_shipping) {
            try {
                foreach ($this->shippingIntegrator->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSeller((array)$integration)) {
                        $integrations[] = [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingIntegrator::LOGISTIC_TYPE
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return $integrations;
    }

    public function getIntegrationsSellerCenterActiveNotUse(): array
    {
        $integrations = [];
        if ($this->shippingCarrier->use_ms_shipping) {
            try {
                foreach ($this->shippingCarrier->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSellerCenter((array)$integration)) {
                        $integrations[] = [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingCarrier::LOGISTIC_TYPE
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return $integrations;
    }

    public function getIntegrationsByName(string $name): array
    {
        $integration = [];
        if ($this->shippingIntegrator->use_ms_shipping) {
            try {
                $integration = (array)($this->shippingIntegrator->getIntegration($name) ?? []);
                $integration = empty($integration) ? [] : array_merge($integration, [
                    'name' => $integration['name'] ?? $name,
                    'description' => $integration['description'],
                    'type' => ShippingIntegrator::LOGISTIC_TYPE
                ]);
            } catch (\Throwable $e) {

            }
        }
        if (empty($integration) && $this->shippingCarrier->use_ms_shipping) {
            try {
                $integration = (array)($this->shippingCarrier->getIntegration($name) ?? []);
                $integration = empty($integration) ? [] : array_merge($integration, [
                    'name' => $integration['name'] ?? $name,
                    'description' => $integration['description'],
                    'type' => ShippingCarrier::LOGISTIC_TYPE
                ]);
            } catch (\Throwable $e) {

            }
        }
        return $integration;
    }

    public function getIntegrationsInUseSellerCenter(): array
    {
        $integration = [];
        if ($this->shippingCarrier->use_ms_shipping) {
            try {
                foreach ($this->shippingCarrier->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSellerCenter((array)$integration)) {
                        $integration[] = array_merge((array)$integration, [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingCarrier::LOGISTIC_TYPE
                        ]);
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return $integration;
    }

    public function getIntegrationsInUseSeller(): array
    {
        $integrations = [];
        if ($this->shippingIntegrator->use_ms_shipping) {
            try {
                foreach ($this->shippingIntegrator->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSeller((array)$integration)) {
                        $integrations[] = array_merge((array)$integration, [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingIntegrator::LOGISTIC_TYPE
                        ]);
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        if ($this->shippingCarrier->use_ms_shipping) {
            try {
                foreach ($this->shippingCarrier->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSellerCenter((array)$integration)) {
                        $integrations[] = array_merge((array)$integration, [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingCarrier::LOGISTIC_TYPE
                        ]);
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return $integrations;
    }

    public function createNewIntegrationLogistic(array $data): bool
    {
        try {
            $saved = $this->saveIntegrationLogistic($data);
        } catch (\Throwable $e) {
            return false;
        }
        if ($saved && ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica)) {
            (new \Logistic\Repositories\CI\v1\IntegrationLogistics())->createNewIntegrationLogistic($data);
        }
        return $saved;
    }

    public function updateIntegrationsInUse(array $data, $id): array
    {
        try {
            $saved = $this->saveIntegrationLogistic($data);
        } catch (\Throwable $e) {
            return false;
        }
        if ($saved && ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica)) {
            $data['form_fields'] = is_string($data['form_fields']) ? $data['form_fields'] : json_encode($data['form_fields']);
            (new \Logistic\Repositories\CI\v1\IntegrationLogistics())->updateIntegrationsInUse($data, $id);
        }
        return $saved;
    }

    protected function saveIntegrationLogistic($data): bool
    {
        try {
            if ($this->isSellerCenter($data)) {
                $saved = $this->shippingCarrier->saveIntegration($data);
                //$saved = $saved && $this->shippingIntegrator->saveIntegration(array_merge($data, ['use_sellercenter' => false, 'use_seller' => true]));
            } else if ($this->isSeller($data)) {
                $saved = $this->shippingIntegrator->saveIntegration($data);
            }
        } catch (\Throwable $e) {
        }
        return $saved ?? false;
    }

    public function updateAllIntegrationsInUse(array $data): bool
    {
        $saved = false;
        $integrations = $this->getIntegrationsInUseSeller();
        foreach ($integrations as $integration) {
            try {
                if ($this->isSellerCenter($integration)) {
                    $saved = $this->shippingCarrier->saveIntegration(array_merge($integration, $data));
                } else if ($this->isSeller($integration['type'])) {
                    $saved = $this->shippingIntegrator->saveIntegration(array_merge($integration, $data));
                }
            } catch (\Throwable $e) {

            }
        }
        if ($saved && ($this->shippingCarrier->use_ms_shipping_replica || $this->shippingIntegrator->use_ms_shipping_replica)) {
            (new \Logistic\Repositories\CI\v1\IntegrationLogistics())->updateAllIntegrationsInUse($data);
        }
        return $saved;
    }

    public function getAllIntegrationSellerCenter(): array
    {
        $integrations = [];
        if ($this->shippingCarrier->use_ms_shipping) {
            try {
                foreach ($this->shippingCarrier->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSellerCenter((array)$integration) && $integration->active) {
                        $integrations[] = [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingCarrier::LOGISTIC_TYPE
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return $integrations;
    }

    public function getAllIntegration(): array
    {
        $integrations = [];
        if ($this->shippingCarrier->use_ms_shipping) {
            try {
                foreach ($this->shippingCarrier->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSellerCenter((array)$integration) && $integration->active) {
                        $integrations[] = [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingCarrier::LOGISTIC_TYPE
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if ($this->shippingIntegrator->use_ms_shipping) {
            try {
                foreach ($this->shippingIntegrator->getAllIntegrations() ?? [] as $integration) {
                    if ($this->isSeller((array)$integration) && $integration->active) {
                        $integrations[] = [
                            'name' => $integration->name,
                            'description' => $integration->description,
                            'type' => ShippingIntegrator::LOGISTIC_TYPE
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return $integrations;
    }

    public function isSeller(array $integration): bool
    {
        return (($integration['type'] ?? '') === ShippingIntegrator::LOGISTIC_TYPE)
            || (($integration['seller'] ?? false) === true)
            || (($integration['use_seller'] ?? false) === true);
    }

    public function isSellerCenter(array $integration): bool
    {
        return (($integration['type'] ?? '') === ShippingCarrier::LOGISTIC_TYPE)
            || (($integration['sellercenter'] ?? false) === true)
            || (($integration['use_sellercenter'] ?? false) === true);
    }
}