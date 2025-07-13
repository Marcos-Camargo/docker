<?php

require_once APPPATH . "libraries/Integration_v2/anymarket/ToolsProduct.php";
require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

use \Integration\Integration_v2\anymarket\ToolsProduct;

/**
 * Class UpdatePublicationStatus
 * @property ToolsProduct $toolsProduct
 */
class UpdatePublicationStatus extends MainController
{
    private $toolsProduct;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_products');
        $this->load->model('model_anymarket_temp_product');
        $this->toolsProduct = new ToolsProduct();
        // $this->toolsProduct->setAuth($this->accountIntegration['store_id']);
        try {
            $this->toolsProduct->setAuth($this->accountIntegration['store_id']);
        } catch (Throwable $e) {            
            log_message('debug', get_instance()->router->fetch_class().'/'.__FUNCTION__.' exception '.$e->getMessage());            
            $this->response($e->getMessage(), self::HTTP_UNAUTHORIZED);
            die;
        }
    }

    public function index_put()
    {
        $bodyString = file_get_contents('php://input');
        $body = json_decode($bodyString, true);
        $integration = $this->accountIntegration;
        $tempProduct = $this->model_anymarket_temp_product->getData([
            'anymarketId' => (string)$body['idSku'],
            'integration_id' => $integration['id'],
        ]);
        $tempProductAttr = [];
        $tempProductAttr['need_update'] = 2;
        $tempProductAttr['json_received'] = json_encode($body);
        if (isset($tempProduct['id'])) {
            $this->model_anymarket_temp_product->update($tempProduct['id'], $tempProductAttr);
        }

        $idSkuMarketplace = $body["idSkuMarketplace"];
        try {
            $bodyRequest = $this->toolsProduct->getDataProductIntegration($idSkuMarketplace);
            $bodyRequest->{'status'} = $body["status"] ?? $bodyRequest->status;
            $bodyRequest->{'idSku'} = $body["idSku"] ?? $bodyRequest->sku->id;
            $bodyRequest->{'availableAmount'} = $body["availableAmount"] ?? $bodyRequest->stock->availableAmount;
            $parsedProduct = $this->toolsProduct->getFormattedProductFieldsToUpdate($bodyRequest);
            $normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
            $parsedVariations = $normalizedProduct['variations'] ?? [];
            unset($parsedProduct['variations']);
            $productSku = $normalizedProduct['sku'];
            if (($normalizedProduct['id'] ?? 0) > 0) {
                $this->toolsProduct->updateProduct($parsedProduct);
                $productId = $normalizedProduct['id'] ?? $this->toolsProduct->getProductIdBySku($productSku);
                $varProductId = $productId;
                foreach ($parsedVariations as $parsedVariation) {
                    if (($parsedVariation['id'] ?? 0) > 0) {
                        $this->toolsProduct->updateVariation($parsedVariation, $productSku);
                    }
                    $varProductId = $this->toolsProduct->getVariationIdBySku($productSku, $parsedVariation['sku']);
                    $normalizedProduct['status'] = $parsedVariation['status'] ?? $normalizedProduct['status'];
                }
                $response = $this->toolsProduct->sendSuccessTransmission(array_merge($body, [
                        'id' => $varProductId,
                        'productId' => $productId,
                        'sku' => $productSku,
                        'status' => $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT ? 'ACTIVE' : ($bodyRequest->status ?? $body['status'] ?? 'PAUSED'),
                        'marketplaceStatus' => $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT ? 'ATIVO' : 'INATIVO'
                    ])
                );
                $this->response($response, self::HTTP_OK);
                return;
            } elseif ($this->toolsProduct->store_uses_catalog && empty($normalizedProduct['_product_id_erp'])) {
                $this->toolsProduct->updateProductIdIntegration($normalizedProduct['sku'], $body['id'] ?? $body['idSkuMarketplace']);
                foreach ($parsedVariations as $parsedVariation) {
                    if (!empty($parsedVariation['_variant_id_erp'])) {
                        $this->toolsProduct->updateProductIdIntegration($normalizedProduct['sku'], $parsedVariation['_variant_id_erp'], $parsedVariation['sku']);
                    }
                }
            }
        } catch (Throwable $e) {
            $response = $this->toolsProduct->sendErrorTransmission($body, $e->getMessage());
            $this->response($response, self::HTTP_BAD_REQUEST);
            return;
        }
        $this->response(array_merge($body, [
            'marketplaceStatus' => "NÃO PUBLICADO",
            'idSku' => $body['idSku'] ?? null,
            'transmissionStatus' => "ERROR",
            'errorMsg' => 'Produto não encontrado no Marketplace'
        ]), self::HTTP_OK);
    }
}
