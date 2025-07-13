<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

class GetDefaultSkuFields extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_anymarket_temp_product');
    }

    public function index_get($id_sku)
    {
        $integration = $this->accountIntegration;
        $credentials = json_decode($integration['credentials'], true);
        $where = [
            'anymarketId' => (string)$id_sku,
            'integration_id' => $integration['id'],
        ];
        $tempProduct = $this->model_anymarket_temp_product->getData($where);
        $productTempData = json_decode($tempProduct['data'], true);
        $productReceived = json_decode($tempProduct['json_received'], true);
        $response = [
            "EAN" => $productReceived['ean'] ?? '',
            "title" => $productTempData['name'] ?? $productTempData['title'],
            "DISCOUNT_VALUE" => $credentials['defaultDiscountValue'],
            "DISCOUNT_TYPE" => $credentials['defaultDiscountType'],
            "priceFactor" => $credentials['priceFactor'],
        ];
        $this->response($response, 200);
    }
}
