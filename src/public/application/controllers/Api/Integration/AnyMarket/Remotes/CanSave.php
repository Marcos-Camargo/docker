<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/CanSave/ValidationProductCanSave.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";

class CanSave extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_anymarket_log');

        $this->validator = new ValidationProductCanSave($this);
    }
    public function index_post()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $this->validator = new ValidationProductCanSave($this);
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $credentiais = json_decode($integration["credentials"], true);

        $body = json_decode(file_get_contents('php://input'), true);
        $log_data = [
            'endpoint' => 'CanSave',
            'body_received' => json_encode($body),
            'store_id' => $integration['store_id'],
        ];
        $this->model_anymarket_log->create($log_data);
        try {
            $type = 'CanSave';
            list($product, $vatiant) = $this->validator->validateProduct($body, $integration, $type, $body['fields']);
            $product['name'] = $body['title'] ?? $product['name'];
            $where = [
                'integration_id' => $integration['id'],
                'anymarketId' => (string)$body['sku']['id'],
            ];
            $temp_product = $this->model_anymarket_temp_product->getData($where);
            if (!$temp_product) {
                $response = [
                    "code" => "PublicationValidationException",
                    "httpStatus" => 422,
                    "message" => "Falha no fluxo do processo.",
                    "details" => "Produto não foi enviado para as rotas CanActive.",
                ];
                $this->response($response, 422);
                return;
            }
            $product['price'] = $body['discountPrice'];
            $product['sku'] = $body['skuInMarketplace'] ?? $body['idInMarketplace'];
            $vatiant[0]['price'] = $body['discountPrice'];
            $temp_product['data'] = json_encode($product);
            $temp_product['variants'] = json_encode($vatiant);
            $temp_product['idAccount'] = $body['idAccount'];
            $temp_product['skuInMarketplace'] = $body['skuInMarketplace'] ?? $body['idInMarketplace'];
            $credentiais['idAccount'] = $body['idAccount'];
            $update_integration = [];
            $update_integration["credentials"] = json_encode($credentiais, JSON_UNESCAPED_UNICODE);
            $this->model_api_integrations->update($integration['id'], $update_integration);
            $this->model_anymarket_temp_product->update($temp_product['id'], $temp_product);
            $this->response(true, 200);
        } catch (Exception $e) {
            $response = [
                "code" => "PublicationValidationException",
                "httpStatus" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $e->getMessage(),
            ];
            $this->response($response, 422);
        }
    }
}
// application\controllers\Api\Integration\AnyMarket\canSave.php
