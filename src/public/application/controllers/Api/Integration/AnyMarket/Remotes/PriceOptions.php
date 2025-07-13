<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ValidationProduct.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";

class PriceOptions  extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->validator = new ValidationProduct($this);
    }
    public function index_get()
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
        $body = json_decode(file_get_contents('php://input'), true);
        try {

            $response = [
                "priceFactor" => $credentials['priceFactor'],
                "defaultDiscountValue" => $credentials['defaultDiscountValue'],
                "defaultDiscountType" => $credentials['defaultDiscountType']
            ];
            $this->response($response, 200);
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
