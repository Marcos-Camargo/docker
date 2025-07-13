<?php

namespace Microservices\v1\Integration;

require_once 'system/libraries/Vendor/autoload.php';

use Exception;
use GuzzleHttp\Utils;
use Microservices\v1\Microservices;

require_once APPPATH . "libraries/Microservices/v1/Microservices.php";

class Price extends Microservices
{
    /**
     * @var bool $use_ms_price
     */
    public $use_ms_price = false;

    public function __construct()
    {
        parent::__construct();

        if ($this->model_settings->getValueIfAtiveByName('use_ms_price')) {
            $this->use_ms_price = true;
            try {
                $this->setProcessUrl();
                $this->setSellerCenter();
                $this->setNameSellerCenter();
                $this->setPathUrl("/price/$this->sellerCenter/api");
            } catch (Exception $exception) {}
        }
    }

    /**
     * @param int $product_id
     * @param int|null $variant
     * @param float|null $price
     * @param float|null $list_price
     * @throws  Exception
     */
    public function updateProductPrice(int $product_id, ?int $variant, ?float $price, float $list_price = null)
    {
        $body = array();

        if (!empty($price)) {
            $body['price'] = $price;
        }
        if (!empty($list_price)) {
            $body['list_price'] = $list_price;
        }

        if (empty($body)) {
            throw new Exception("Não foi possível atualizar o preço do produto.");
        }

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
                                'variant' => $variant
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
     * @param int $product_id
     * @param int|null $variant
     * @param string $marketplace
     * @param float|null $price
     * @param float|null $list_price
     * @throws  Exception
     */
    public function updateMarketplacePrice(int $product_id, ?int $variant, string $marketplace, ?float $price, float $list_price = null)
    {
        $body = array();

        if (!empty($price)) {
            $body['price'] = $price;
        }
        if (!empty($list_price)) {
            $body['list_price'] = $list_price;
        }

        if (empty($body)) {
            throw new Exception("Não foi possível atualizar o preço do marketplace.");
        }

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
                                'variant' => $variant,
                                'int_to' => $marketplace
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
     * @param int $product_id
     * @param int|null $variant
     * @param string $marketplace
     * @param float|null $price
     * @param float|null $list_price
     * @throws  Exception
     */
    public function updatePromotionPrice(int $product_id, ?int $variant, string $marketplace, ?float $price, float $list_price = null)
    {
        $body = array();

        if (!empty($price)) {
            $body['price'] = $price;
        }
        if (!empty($list_price)) {
            $body['list_price'] = $list_price;
        }

        if (empty($body)) {
            throw new Exception("Não foi possível atualizar o preço do produto.");
        }

        try {
            $this->request(
                'POST',
                "/v1/promotions/insert",
                array(
                    'json' => array(
                        'product' => array_merge(
                            $body,
                            array(
                                'product_id' => $product_id,
                                'variant' => $variant,
                                'int_to' => $marketplace
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
     * @param int $product_id
     * @param int|null $variant
     * @param string $marketplace
     * @param float|null $price
     * @param float|null $list_price
     * @throws  Exception
     */
    public function updateCampaignPrice(int $product_id, ?int $variant, string $marketplace, ?float $price, float $list_price = null)
    {
        $body = array();

        if (!empty($price)) {
            $body['price'] = $price;
        }
        if (!empty($list_price)) {
            $body['list_price'] = $list_price;
        }

        if (empty($body)) {
            throw new Exception("Não foi possível atualizar o preço do produto.");
        }

        try {
            $this->request(
                'POST',
                "/v1/campaigns/insert",
                array(
                    'json' => array(
                        'product' => array_merge(
                            $body,
                            array(
                                'product_id' => $product_id,
                                'variant' => $variant,
                                'int_to' => $marketplace
                            )
                        )
                    )
                )
            );
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function getProductPrice(int $product_id, ?int $variant = null): object
    {
        return $this->getPrice(sprintf("/v1/products/%s/%s", $product_id, $variant ?? ''));
    }

    public function getCatalogsPrice(string $marketplace, int $product_id, ?int $variant = null): object
    {
        return $this->getPrice(sprintf("/v1/catalogs/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function getMarketplacePrice(string $marketplace, int $product_id, ?int $variant = null): object
    {
        return $this->getPrice(sprintf("/v1/marketplaces/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function getPromotionPrice(string $marketplace, int $product_id, ?int $variant = null): object
    {
        return $this->getPrice(sprintf("/v1/promotions/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function getCampaignsPrice(string $marketplace, int $product_id, ?int $variant = null): object
    {
        return $this->getPrice(sprintf("/v1/campaigns/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    protected function getPrice(string $endPoint): object
    {
        try {
            $request = $this->request('GET', $endPoint);
            $price = Utils::jsonDecode($request->getBody()->getContents());
        } catch (\Throwable $e) {
        }
        return (object)array_merge([
            'product_id' => $price->product_id ?? null,
            'variant' => $price->variant ?? null,
            'price' => ($price->price ?? null) !== null ? (float)($price->price ?? null) : null,
            'list_price' => ($price->list_price ?? null) !== null ? (float)($price->list_price ?? null) : null
        ], ($price->int_to ?? null) !== null ? [
            'int_to' => $price->int_to ?? null
        ] : [], ($price->type ?? null) !== null ? [
            'type' => $price->type ?? null
        ] : []
        );
    }

    public function deleteProductPrice(int $product_id, ?int $variant = null): bool
    {
        return $this->deletePrice(sprintf("/v1/products/%s/%s", $product_id, $variant ?? ''));
    }

    public function deleteCatalogsPrice(string $marketplace, int $product_id, ?int $variant = null): bool
    {
        return $this->deletePrice(sprintf("/v1/catalogs/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function deleteMarketplacePrice(string $marketplace, int $product_id, ?int $variant = null): bool
    {
        return $this->deletePrice(sprintf("/v1/marketplaces/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function deletePromotionPrice(string $marketplace, int $product_id, ?int $variant = null): bool
    {
        return $this->deletePrice(sprintf("/v1/promotions/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    public function deleteCampaignPrice(string $marketplace, int $product_id, ?int $variant = null): bool
    {
        return $this->deletePrice(sprintf("/v1/campaigns/%s/%s/%s", $marketplace, $product_id, $variant ?? ''));
    }

    protected function deletePrice(string $endPoint): bool
    {
        try {
            $request = $this->request('DELETE', $endPoint);
            return $request->getStatusCode() == 204;
        } catch (\Throwable $e) {

        }
        return false;
    }
}