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
class CanSave extends MainController
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
        $product = json_decode(file_get_contents('php://input'));
        try {
            $this->toolsProduct->setProductReceived($product);
            $tempProduct = $this->model_anymarket_temp_product->getData([
                    'integration_id' => $this->accountIntegration['id'],
                    'anymarketId' => (string)$product->sku->id,
                ]) ?? ['id' => 0];
            $parsedProduct = $this->toolsProduct->getDataFormattedToIntegration([]);
            $tempProduct['data'] = json_encode(
                $this->toolsProduct->normalizedFormattedData($parsedProduct)
            );
            $tempProduct['variants'] = json_encode(
                $this->toolsProduct->getVariationFormatted($product->sku->variations ?? [])
            );
            $tempProduct['idAccount'] = $product->idAccount;
            $tempProduct['skuInMarketplace'] = $product->skuInMarketplace ?? $product->idInMarketplace;
            if ($tempProduct['id'] > 0) {
                $response = $this->model_anymarket_temp_product->update($tempProduct['id'], $tempProduct);
                $this->response($response, 200);
                return;
            }
            $response = $this->model_anymarket_temp_product->create($tempProduct);
            $this->response($response, 200);
        } catch (Throwable $e) {
            $response = [
                "code" => "PublicationValidationException",
                "httpStatus" => 422,
                "message" => "Erro ao validar publicação",
                "details" => "{$e->getMessage()}",
            ];
            $this->response($response, 422);
        }
    }
}