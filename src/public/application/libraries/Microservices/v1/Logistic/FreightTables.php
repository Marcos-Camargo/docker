<?php

namespace Microservices\v1\Logistic;

require_once 'system/libraries/Vendor/autoload.php';

use Exception;
use GuzzleHttp\Utils;
use Microservices\v1\Microservices;

require_once APPPATH . "libraries/Microservices/v1/Microservices.php";

class FreightTables extends Microservices
{
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
                $this->setPathUrl("/freight_tables/$this->sellerCenter/api");
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
            if (is_string($errors_field)) {
                $errorsFormatted[] = $errors_field;
                continue;
            }

            foreach ($errors_field as $error) {
                $errorsFormatted[] = $error;
            }
        }

        return $errorsFormatted;
    }

    /**
     * @throws Exception
     */
    public function getShippingCompanies()
    {
        try {
            $request = $this->request('GET', "/$this->store/v1/shipping_company/list");

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
    public function getShippingCompany(int $shipping_company_id)
    {
        try {
            $request = $this->request('GET', "/$this->store/v1/shipping_company/$shipping_company_id");

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
    public function createShippingCompany(array $data)
    {
        try {
            $request = $this->request('POST', "/$this->store/v1/shipping_company/create", array('json' => $data));

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
    public function updateShippingCompany(int $shipping_company_id, array $data)
    {
        try {
            $this->request('PUT', "/$this->store/v1/shipping_company/$shipping_company_id", array('json' => $data));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function createSimplifiedTable(array $data, int $shipping_company_id)
    {
        try {
            $request = $this->request('POST', "/$this->store/v1/simplified_table/create/$shipping_company_id", array('json' => array('simplified' => $data)));
            $request->getBody()->getContents();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getSimplifiedTable(int $shipping_company_id)
    {
        try {
            $request = $this->request('GET', "/$this->store/v1/simplified_table/$shipping_company_id");

            $contentResponse = $request->getBody()->getContents();
            $response = Utils::jsonDecode($contentResponse);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        return $response;
    }

    public function getProcessingStatus(int $csvFileId)
    {
        try {
            $request = $this->request('GET', "/$this->store/v1/shipping_table/processing_status/{$csvFileId}");
            $contentResponse = $request->getBody()->getContents();
            $response = Utils::jsonDecode($contentResponse);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
        return $response;
    }
}