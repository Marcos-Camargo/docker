<?php

namespace Microservices\v1\Logistic;

require_once 'system/libraries/Vendor/autoload.php';

use Exception;
use GuzzleHttp\Utils;
use Microservices\v1\Microservices;

require_once APPPATH . "libraries/Microservices/v1/Microservices.php";

class ShippingIntegrator extends Microservices
{
    const LOGISTIC_TYPE = 'integrator';

    /**
     * @var bool $use_ms_shipping
     */
    public $use_ms_shipping = false;

    public $use_ms_shipping_replica = false;

    public function __construct()
    {
        parent::__construct();

        if ($this->model_settings->getValueIfAtiveByName('use_ms_shipping')) {
            $this->use_ms_shipping = true;
            if ($this->model_settings->getValueIfAtiveByName('use_ms_shipping_replica')) {
                $this->use_ms_shipping_replica = true;
            }
            try {
                $this->setProcessUrl();
                $this->setSellerCenter();
                $this->setNameSellerCenter();
                $this->setPathUrl("/shipping_integrator/$this->sellerCenter/api");
            } catch (Exception $exception) {}
        }
    }

    /**
     * @param   string $errors
     * @return  array
     */
    public function getErrorFormatted(string $errors): array
    {
        $errorsFormatted = array();

        $errors = json_decode($errors, true);

        if (!is_array($errors)) {
            if (is_string($errors)) {
                return array($errors);
            }

            return (array)json_encode($errors, JSON_UNESCAPED_UNICODE);
        }

        foreach ($errors as $errors_field) {
            foreach ($errors_field as $error) {
                $errorsFormatted[] = $error;
            }
        }

        return $errorsFormatted;
    }

    /**
     * @throws Exception
     */
    public function getConfigures()
    {
        try {
            $request = $this->request('GET', "/$this->store/v1/configure");

            $contentResponse = $request->getBody()->getContents();
            $response = Utils::jsonDecode($contentResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return $response;
    }

    /**
     * @param string $integration
     * @return array|bool|float|int|object|string|null
     */
    public function getConfigure(string $integration)
    {
        try {
            $request = $this->request('GET', "/$this->store/v1/configure/$integration");
            $contentResponse = $request->getBody()->getContents();
            $response = Utils::jsonDecode($contentResponse);
        } catch (Exception $e) {
            if ($this->store === 0) {
                $this->setStore(null);
                return $this->getConfigure($integration);
            }
            return [];
        }
        return $response;
    }

    public function saveConfigure(array $data): bool
    {
        try {
            $data['integration'] = $data['integration'] ?? $data['integration_name'] ?? $data['name'] ?? '';
            $data['credentials'] = is_string($data['credentials']) ? json_decode($data['credentials']) : $data['credentials'];
            $storeId = ($data['store_id'] ?? $this->store) == 'null' ? null : (int)($data['store_id'] ?? $this->store);
            $this->setStore($storeId);
            if (!empty($this->getConfigure($data['integration']))) {
                $this->updateConfigure($data['integration'], array_merge(['credentials' => $data['credentials']], ($data['active'] ?? null) !== null ? ['active' => $data['active']] : []));
                return true;
            }
            $this->createConfigure($data);
            return true;
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function updateConfigure(string $integration, array $data)
    {
        try {
            $this->request('PUT', "/$this->store/v1/configure/$integration", array('json' => $data));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function createConfigure(array $data)
    {
        try {
            $this->request('POST', "/$this->store/v1/configure", array('json' => $data));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param string $integration
     * @return bool
     * @throws Exception
     */
    public function removeConfigureIfExists(string $integration): bool
    {
        if (!empty($this->getConfigure($integration))) {
            try {
                $this->removeConfigure($integration);
                return true;
            } catch (\Throwable $e) {
                throw new Exception($e->getMessage(), 0, $e);
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function removeConfigure(string $integration)
    {
        try {
            $this->request('DELETE', "/$this->store/v1/configure/$integration");
            return true;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function getAllIntegrations()
    {
        try {
            $request = $this->request('GET', "/integration");

            $contentResponse = $request->getBody()->getContents();
            $response = Utils::jsonDecode($contentResponse);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    public function getIntegration(string $integration)
    {
        try {
            $request = $this->request('GET', "/integration/$integration");

            $contentResponse = $request->getBody()->getContents();
            $response = Utils::jsonDecode($contentResponse);
        } catch (Exception $exception) {
            return [];
        }

        return $response;
    }

    public function saveIntegration(array $data): bool
    {
        try {
            $integration = $data['integration'] ?? $data['name'] ?? $data['integration_name'] ?? '';
            //$data['form_fields'] = is_string($data['form_fields']) ? json_decode($data['form_fields']) : $data['form_fields'];
            //if (!empty($this->getIntegration($data['integration']))) {
                $this->updateIntegration($integration, $data);
                return true;
            //}
            //$this->createIntegration($data);
            //return true;
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * @deprecated
     *
     * @throws Exception
     */
    public function createIntegration(array $data)
    {
        /*try {
            $this->request('POST', "/integration/create", ['json' => $data]);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }*/
    }

    /**
     * @throws Exception
     */
    public function updateIntegration(string $integration, array $data)
    {
        try {
            $this->request('PUT', "/integration/$integration", array('json' => $data));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

}