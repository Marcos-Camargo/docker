<?php

namespace Microservices\v1\Logistic;

require_once 'system/libraries/Vendor/autoload.php';

use Exception;
use GuzzleHttp\Utils;
use Microservices\v1\Microservices;

require_once APPPATH . "libraries/Microservices/v1/Microservices.php";

class PickupPoints extends Microservices
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
                $this->setPathUrl("/pickup_point/api");
            } catch (Exception $exception) {}
        }
    }

    /**
     * @throws Exception
     */
    public function getPickupPoints($store_id = null, $page, $per_page, $search = null, $order_by = null, $tableFields)
    {
        $options = [
            'query' => ['page' => $page, 'per_page' => $per_page]
        ];

        if ($store_id != null) {
            $options['query']['store_id'] = $store_id;
        }

        if ($search != null) {
            foreach ($tableFields as $field){
                $options['query'][$field] = $search['value'];
            }
        }

        if ($order_by != null) {
            $options['query']['order_by'] = $order_by;
        }

        try {
            $request = $this->request('GET', "/$this->sellerCenter/v1/pickup-points", $options);

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
    public function getPickupPoint(int $pickup_point_id)
    {
        try {
            $request = $this->request('GET', "/$this->sellerCenter/v1/pickup-points/$pickup_point_id");

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
    public function createPickupPoint(array $data)
    {
        try {

            $request = $this->request('POST', "/$this->sellerCenter/v1/pickup-points", array('json' => $data));
            $contentResponse = $request->getBody()->getContents();
            return Utils::jsonDecode($contentResponse);

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    /**
     * @throws Exception
     */
    public function updatePickupPoint(int $id, array $data)
    {
        try {

            $request = $this->request('PUT', "/$this->sellerCenter/v1/pickup-points/$id", array('json' => $data));
            $contentResponse = $request->getBody()->getContents();
            return Utils::jsonDecode($contentResponse);

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

}