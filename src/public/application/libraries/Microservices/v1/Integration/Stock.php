<?php

namespace Microservices\v1\Integration;

require_once 'system/libraries/Vendor/autoload.php';

use Exception;
use GuzzleHttp\Utils;
use Microservices\v1\Microservices;

require_once APPPATH . "libraries/Microservices/v1/Microservices.php";

class Stock extends Microservices
{
    /**
     * @var bool $use_ms_stock
     */
    public $use_ms_stock = false;

    public function __construct()
    {
        parent::__construct();

        if ($this->model_settings->getValueIfAtiveByName('use_ms_stock')) {
            $this->use_ms_stock = true;
            try {
                $this->setProcessUrl();
                $this->setSellerCenter();
                $this->setNameSellerCenter();
                $this->setPathUrl("/stock/$this->sellerCenter/api");
            } catch (Exception $exception) {}
        }
    }

    /**
     * @param   int         $product_id
     * @param   int|null    $variant
     * @param   int         $stock
     * @throws  Exception
     */
    public function updateProductStock(int $product_id, ?int $variant, int $stock)
    {
        $body = array(
            'stock' => $stock
        );

        try {
            $this->request(
                'POST',
                "/v1/products/insert",
                array(
                    'json' => array(
                        'product' => array_merge(
                            $body,
                            array(
                                'product_id' => $product_id,
                                'variant'    => $variant
                            )
                        )
                    )
                )
            );
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param   int         $product_id
     * @param   int|null    $variant
     * @param   string      $marketplace
     * @param   int         $stock
     * @throws  Exception
     */
    public function updateMarketplaceStock(int $product_id, ?int $variant, string $marketplace, int $stock)
    {
        $body = array(
            'stock' => $stock
        );

        try {
            $this->request(
                'POST',
                "/v1/marketplaces/insert",
                array(
                    'json' => array(
                        'product' => array_merge(
                            $body,
                            array(
                                'product_id' => $product_id,
                                'variant'    => $variant,
                                'int_to'     => $marketplace
                            )
                        )
                    )
                )
            );
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function getProductStock(int $product_id, ?int $variant): object
    {
        return $this->getStock(sprintf("/v1/products/%s/%s/%s", $product_id, $variant ?? ''));
    }

    public function getMarketplaceStock(string $marketplace, int $product_id, ?int $variant = null): object
    {
        return $this->getStock(sprintf("/v1/marketplaces/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function getStock(string $endPoint): object
    {
        try {
            $request = $this->request('GET', $endPoint);
            $stock = Utils::jsonDecode($request->getBody()->getContents());
        } catch (\Throwable $e) {
        }
        return (object)array_merge([
            'product_id' => $stock->product_id ?? null,
            'variant' => $stock->variant ?? null,
            'stock' => ($stock->stock ?? null) !== null ? (float)($stock->stock ?? null) : null,
            'int_to' => $stock->int_to ?? null
        ], !empty($stock->int_to ?? null) ? ['int_to' => $stock->int_to ?? null] : []);
    }

    public function deleteProductStock(int $product_id, ?int $variant): bool
    {
        return $this->deleteStock(sprintf("/v1/products/%s/%s", $product_id, $variant ?? ''));
    }

    public function deleteMarketplaceStock(string $marketplace, int $product_id, ?int $variant = null): bool
    {
        return $this->deleteStock(sprintf("/v1/marketplaces/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function deleteStock(string $endPoint): bool
    {
        try {
            $request = $this->request('DELETE', $endPoint);
            return $request->getStatusCode() == 204;
        } catch (\Throwable $e) {
        }
        return false;
    }

}