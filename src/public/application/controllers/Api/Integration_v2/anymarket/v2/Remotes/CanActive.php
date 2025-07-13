<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

require_once APPPATH . "libraries/Integration_v2/anymarket/ToolsProduct.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/ApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/AnyMarketApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/TransformationException.php";

/**
 * Class CanActive
 * @property \Integration\Integration_v2\anymarket\ToolsProduct $toolsProduct
 */
class CanActive extends MainController
{
    private $toolsProduct;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_anymarket_temp_product');
        $this->toolsProduct = new \Integration\Integration_v2\anymarket\ToolsProduct();
        // $this->toolsProduct->setAuth($this->accountIntegration['store_id']);
        try {
            $this->toolsProduct->setAuth($this->accountIntegration['store_id']);
        } catch (Throwable $e) {            
            log_message('debug', get_instance()->router->fetch_class().'/'.__FUNCTION__.' exception '.$e->getMessage());            
            $this->response($e->getMessage(), self::HTTP_UNAUTHORIZED);
            die;
        }
    }

    public function index_post()
    {
        $integration = $this->accountIntegration;
        $product = json_decode(file_get_contents('php://input'));

        try {
            $this->toolsProduct->getLocalManufacturerByExternalId($product->product->brand);
            $this->toolsProduct->getLocalCategoryByExternalId($product->product->category);
            $data = [
                'integration_id' => $integration['id'],
                'id_sku_product' => $product->partnerId ?? '',
                'anymarketId' => (string)$product->id,
                'data' => json_encode($product),
                'json_received' => json_encode($product),
                'variants' => json_encode(
                    $this->toolsProduct->getVariationFormatted($product->variations ?? [])
                ),
            ];
            $response = $this->model_anymarket_temp_product->create($data);
            $this->response($response, 200);
        } catch (TransformationException $transformationException) {
            $response = [
                "code" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $transformationException->getMessage(),
            ];
            $this->response($response, 422);
        } catch (\Integration\Integration_v2\anymarket\AnyMarketApiException $validationException) {
            $response = [
                "code" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $validationException->getMessage(),
            ];
            $this->response($response, 422);
        } catch (Throwable $notIndexException) {
            $response = [
                "code" => 422,
                "message" => "Erro ao validar publicação",
                "details" => $notIndexException->getMessage(),
            ];
            $this->response($response, 422);
        }
    }
}
