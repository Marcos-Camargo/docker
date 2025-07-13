<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ValidationProduct.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";

class GetDefaultSkuFields extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_anymarket_log');

        $this->validator = new ValidationProduct($this);
    }
    public function index_get($id_sku)
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $credentials = json_decode($integration['credentials'], true);
        $where = [
            'anymarketId' => (string)$id_sku,
            'integration_id' => $integration['id'],
        ];
        $temp_product = $this->model_anymarket_temp_product->getData($where);
        $product_temp_data = json_decode($temp_product['data'], true);
        $product_received = json_decode($temp_product['json_received'], true);
        $response = [
            "EAN" => isset($product_received['ean']) ? $product_received['ean'] : '',
            "title" => $product_temp_data['name'],
            "DISCOUNT_VALUE" => $credentials['defaultDiscountValue'],
            "DISCOUNT_TYPE" => $credentials['defaultDiscountType'],
            "priceFactor" => $credentials['priceFactor'],
        ];
        $log_data = [
            'endpoint' => 'GetDefaultSkuFields/'.$id_sku,
            'body_received' => json_encode($response),
            'store_id' => $integration['store_id'],
        ];
        $this->model_anymarket_log->create($log_data);

        $this->response($response, 200);
    }
}
