<?php

namespace Microservices\v1\Logistic;

require_once 'system/libraries/Vendor/autoload.php';

use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Utils;
use Microservices\v1\Microservices;
use Model_products;

require_once APPPATH . "libraries/Microservices/v1/Microservices.php";

/**
 * Class Shipping
 * @package Microservices\v1\Logistic
 * @property \Model_integrations $model_integrations
 * @property \Model_auction $model_auction
 * @property Model_products $model_products
 */
class Shipping extends Microservices
{
    /**
     * @var bool $use_ms_shipping
     */
    public $use_ms_shipping = false;
    public $use_ms_shipping_replica = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_integrations');
        $this->load->model('model_auction');

        if ($this->model_settings->getValueIfAtiveByName('use_ms_shipping')) {
            $this->use_ms_shipping = true;
            if ($this->model_settings->getValueIfAtiveByName('use_ms_shipping_replica')) {
                $this->use_ms_shipping_replica = true;
            }

            try {
                $this->setProcessUrl();
                $this->setSellerCenter();
                $this->setNameSellerCenter();
                $this->setPathUrl("/shipping/$this->sellerCenter/api");
            } catch (Exception $exception) {}
        }
    }

    /**
     * @param string $errors
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
    public function formatPivot(array $data): array
    {
        $this->load->model('model_products');
        if (array_key_exists('prd_id', $data)) {
            $product = $this->model_products->getProductData(0, $data['prd_id']);
        }
        if (array_key_exists('store_id', $data) && (!isset($data['additional_operational_deadline']) || !isset($data['type_store']))) {
            $store = $this->model_stores->getStoresData($data['store_id']);
            if ($store) {
                $data['additional_operational_deadline'] = $store['additional_operational_deadline'];
                $data['type_store'] = $store['type_store'];
            }
        }
        $return = array();

        if (isset($data['int_to'])) {
            $return["marketplace"] = $data['int_to'];
        }
        if (isset($data['company_id'])) {
            $return["company_id"] = $data['company_id'];
        }
        if (isset($data['store_id'])) {
            $return["store_id"] = $data['store_id'];
        }
        if (isset($data['seller_id'])) {
            $return["seller_id"] = $data['seller_id'];
        }
        if (isset($data['CNPJ']) || isset($data['cnpj'])) {
            $return["cnpj"] = $data['CNPJ'] ?? $data['cnpj'];
        }
        if (isset($data['zipcode'])) {
            $return["zip_code"] = $data['zipcode'];
        }
        if (isset($data['EAN']) || isset($data['ean'])) {
            $return["ean"] = $data['EAN'] ?? $data['ean'];
        }
        if (isset($data['sku'])) {
            $return["sku_seller"] = $data['sku'];
        }
        if (isset($data['skumkt'])) {
            $return["sku_mkt"] = $data['skumkt'];
        }
        if (isset($data['skulocal'])) {
            $return["sku_local"] = $data['skulocal'];
        }
        if (isset($data['prd_id'])) {
            $return["product_id"] = $data['prd_id'];
        }
        if (isset($data['variant'])) {
            $return["product_variant"] = $data['variant'] === '' ? null : $data['variant'];
        }
        if (isset($data['price'])) {
            $return["price"] = $data['price'];
        }
        if (isset($data['list_price'])) {
            $return["list_price"] = $data['list_price'];
        }
        if (isset($product['category_id'])) {
            $return["category_id"] = onlyNumbers($product['category_id']);
        }
        if (isset($data['qty_atual'])) {
            $return["quantity"] = $data['qty_atual'];
        }
        if (isset($data['qty_atual'])) {
            $return["quantity_total"] = $data['qty_atual'];
        }
        if (isset($data['altura'])) {
            $return["height"] = $data['altura'];
        }
        if (isset($data['largura'])) {
            $return["width"] = $data['largura'];
        }
        if (isset($data['profundidade'])) {
            $return["length"] = $data['profundidade'];
        }
        if (isset($data['peso_bruto'])) {
            $return["gross_weight"] = $data['peso_bruto'];
        }
        if (isset($product['products_package'])) {
            $return["quantity_per_package"] = $product['products_package'];
        }
        if (isset($data['crossdocking'])) {
            $return["crossdocking"] = $data['crossdocking'];
        }
        if (isset($data['additional_operational_deadline'])) {
            $return["additional_operational_deadline"] = $data['additional_operational_deadline'];
        }
        if (isset($data['type_store'])) {
            $return["type_store"] = $data['type_store'];
        }
        if (!empty($product['has_variants'])) {
            $return["sku_product_seller"] = $product['sku'];
        }

        return $return;
    }

    /**
     * @throws Exception
     */
    public function createPivot(array $data)
    {
        try {
            $this->request('POST', "/v1/pivot/create", array('json' => $this->formatPivot($data)));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function updatePivot(array $data, string $marketplace = null, int $product_id = null, string $variant = null)
    {
        if (!array_key_exists('int_to', $data) || !array_key_exists('prd_id', $data) || !array_key_exists('variant', $data)) {
            if (is_null($marketplace) || is_null($product_id) || is_null($variant)) {
                return;
            }
        }

        $marketplace = $marketplace === null ? $data['int_to'] : $marketplace;
        $product_id = $product_id === null ? $data['prd_id'] : $product_id;
        $variant = $variant === null ? $data['variant'] : $variant;
        if ($variant === '') {
            $variant = null;
        }

        $uri = "/v1/pivot/$marketplace/$product_id";

        if (!is_null($variant)) {
            $uri .= "/$variant";
        }

        try {
            $this->request('PUT', $uri, array('json' => $this->formatPivot($data)));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function updatePivotByStore(int $store_id, array $data)
    {
        try {
            $this->request('PUT', "/$store_id/v1/store", array('json' => $this->formatPivot($data)));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function removePivot(array $data, string $marketplace = null, int $product_id = null, string $variant = null)
    {
        if (!array_key_exists('int_to', $data) || !array_key_exists('prd_id', $data) || !array_key_exists('variant', $data)) {
            if (is_null($marketplace) || is_null($product_id) || is_null($variant)) {
                return;
            }
        }

        $marketplace = $marketplace === null ? $data['int_to'] : $marketplace;
        $product_id = $product_id === null ? $data['prd_id'] : $product_id;
        $variant = $variant === null ? $data['variant'] : $variant;
        if ($variant === '') {
            $variant = null;
        }

        $uri = "/v1/pivot/$marketplace/$product_id";

        if (!is_null($variant)) {
            $uri .= "/$variant";
        }

        try {
            $this->request('DELETE', $uri);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function getAuctionTypes(array $criteria = []): array
    {
        try {
            $response = $this->request('GET', "/v1/auction/all_rules", ['query' => $criteria]);
            $rules = Utils::jsonDecode($response->getBody()->getContents(), true);
            return array_map(function ($rule) {
                return [
                    'id' => $rule['id'],
                    'nome' => $rule['name'],
                    'descricao' => $rule['description']
                ];
            }, $rules ?? []);
        } catch (Exception $exception) {

        }
        return [];
    }

    public function getRuleAuction(string $marketplace, ?int $storeId = 0): array
    {
        try {
            $storeId = is_null($storeId) ? $storeId : 0;
            $response = $this->request('GET', "/v1/auction/{$marketplace}/{$storeId}");
            $rule = Utils::jsonDecode($response->getBody()->getContents(), true);
            return !$rule ? [] : array_merge($rule, [
                'rules_seller_conditions_status_id' => $rule['rule'],
            ]);
        } catch (Exception $exception) {

        }
        return [];
    }

    public function saveRuleAuction(array $data): int
    {
        unset($data['id']);

        $data['rule'] = $data['rules_seller_conditions_status_id'];

        $integration = $this->model_integrations->getIntegrationsData($data['marketplace']);
        if ($integration) {
            $data['marketplace'] = $integration['int_to'];
        }

        $data['store_id'] = $data['store_id'] ?? 0;

        try {
            $this->request('POST', "/v1/auction/update", ['json' => $data]);
        } catch (Exception $exception) {
        }
        return 0;
    }

    public function buildQuoteSimulationEndPoint(string $plataform, string $marketplace): ?string
    {
        if (!$this->use_ms_shipping) return null;
        $token = $this->model_settings->getValueIfAtiveByName('jwt_token_ms_shipping') ?: '';
        if (empty($token)) return null;
        $payloadToken = explode('.', $token)[1] ?? '';
        $payloadToken = JWT::jsonDecode(JWT::urlsafeB64Decode($payloadToken));
        $tenant = $payloadToken->client_id ?? $payloadToken->clientId ?? null;
        if (empty($tenant)) return null;
        $base64Token = base64_encode(sprintf("%s:%s", md5($token), $tenant));
        return "{$this->process_url}/fulfillment/{$base64Token}/{$plataform}/{$marketplace}/api/v1";
    }

    public function getFormatQuoteAuction(string $marketplace, array $items, string $destinationZip): array
    {
        try {
            $data['destinationZip'] = $destinationZip;
            $data['volumes']        = $items;
            $response = $this->request('POST', "/v1/quote/internal/{$marketplace}", ['json' => $data]);
            $quoteResponse = Utils::jsonDecode($response->getBody()->getContents(), true);
            return !$quoteResponse ? [] : $quoteResponse;
        } catch (Exception $exception) {

        }
        return [];
    }

    /**
     * @throws Exception
     */
    public function updateDataStoresInPivot(array $data)
    {
        try {
            $this->request('PUT', "/v1/pivot/update_data_store", array('json' => $this->formatPivot($data)));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}