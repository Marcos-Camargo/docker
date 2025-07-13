<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/CanActive/ValidationProductCanActive.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";

class CanActive extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_anymarket_log');
        $this->validator = new ValidationProduct($this);
    }
    public function index_post()
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
        $body = json_decode(file_get_contents('php://input'), true);
        $log_data = [
            'endpoint' => 'CanActive',
            'body_received' => json_encode($body),
            'store_id' => $integration['store_id'],
        ];
        $this->model_anymarket_log->create($log_data);
        try {
            $type = 'CanActive';
            $product = $this->model_products->getByProductIdErp($body['product']['id']);
            list($product, $vatiant) = $this->validator->validateProduct($body, $integration, $type, (Boolean) $product);
            $product['name'] = $body['title'] ?? $product['name'];
            $data = [
                'integration_id' => $integration['id'],
                'id_sku_product' => $product['sku'],
                'anymarketId' => (string)$body['id'],
                'data' => json_encode($product),
                'json_received' => json_encode($body),
                'variants' => json_encode($vatiant),
            ];
            $response = $this->model_anymarket_temp_product->create($data);
            $this->response($response, 200);
        } catch (TransformationException $transformationException) {
            $response = [
                "code" => "PublicationValidationTransformationException" . get_class($transformationException),
                "httpStatus" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $transformationException->getMessage(),
            ];
            $this->response($response, 422);
        } catch (ValidationException $validationException) {
            $response = [
                "code" => "PublicationValidationException-" . get_class($validationException),
                "httpStatus" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $validationException->getMessage(),
            ];
            $this->response($response, 422);
        } catch (Exception $notIndexException) {
            $response = [
                "code" => "PublicationValidationException-IndexDontFound-" . get_class($notIndexException),
                "httpStatus" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $notIndexException->getMessage(),
            ];
            $this->response($response, 422);
        }
    }
}
