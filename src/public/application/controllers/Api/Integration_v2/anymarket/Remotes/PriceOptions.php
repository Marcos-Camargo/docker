<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

class PriceOptions extends MainController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index_get()
    {
        $integration = $this->accountIntegration;
        $credentials = json_decode($integration['credentials'], true);
        try {
            $response = [
                "priceFactor" => $credentials['priceFactor'],
                "defaultDiscountValue" => $credentials['defaultDiscountValue'],
                "defaultDiscountType" => $credentials['defaultDiscountType']
            ];
            $this->response($response, 200);
            return;
        } catch (Exception $e) {
            $response = [
                "code" => "PublicationValidationException",
                "httpStatus" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $e->getMessage()
            ];
            $this->response($response, 422);
        }
    }
}
