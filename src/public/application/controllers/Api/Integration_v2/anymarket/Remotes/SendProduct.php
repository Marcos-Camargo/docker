<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

require_once APPPATH . "libraries/Integration_v2/anymarket/ToolsProduct.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/ApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/AnyMarketApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/TransformationException.php";

/**
 * Class CanActive
 * @property \Integration\Integration_v2\anymarket\ToolsProduct $toolsProduct
 * @property Model_products $model_products
 */
class SendProduct extends MainController
{

    private $toolsProduct;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_products');
        $this->toolsProduct = new \Integration\Integration_v2\anymarket\ToolsProduct();
        // $this->toolsProduct->setAuth($this->accountIntegration['store_id']);
        try {
            $this->toolsProduct->setAuth($this->accountIntegration['store_id']);
        } catch (Throwable $e) {            
            log_message('debug', get_instance()->router->fetch_class().'/'.__FUNCTION__.' exception '.$e->getMessage());            
            $this->response($e->getMessage(), self::HTTP_UNAUTHORIZED);
            die;
        }
        $this->toolsProduct->setJob(get_class($this));
    }

    private function saveLog(string $uuid, string $body, string $type = 'I')
    {
        return;
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif(!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = "NONE";
        }

        $data = array(
            'user_id' => 1,
            'company_id' => 0,
            'store_id' => 0,
            'module' => __CLASS__.'/'.__FUNCTION__,
            'action' => $uuid,
            'ip' => $ip,
            'value' => $body,
            'tipo' => $type
        );
        $this->db->insert('log_history_api', $data);
    }

    public function index_post()
    {
        $uuid = Admin_Controller::getGUID(false);
        $body = file_get_contents('php://input');

        $this->saveLog($uuid, $body);

        $body = json_decode($body, true);
        $product_data = null;

        try {
            $parsedProduct = $this->toolsProduct->getDataFormattedToIntegration([
                'idSkuMarketplace' => $body['idSkuMarketplace'] ?? $body['id']
            ]);
            $this->toolsProduct->setUniqueId($body['id'] ?? $body['idSkuMarketplace']);
            $normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
            $parsedVariations = $this->toolsProduct->getParsedVariations();
            $getUpdateOnlyPriceStock = $this->toolsProduct->getUpdateOnlyPriceStock();
            $product_data = $this->toolsProduct->productIntegrationValidation->getProduct();
            $is_variation_grouped = $product_data && $product_data['is_variation_grouped'];
            if ($this->toolsProduct->store_uses_catalog || $is_variation_grouped) {
                $getUpdateOnlyPriceStock = true;
            }
            if (($normalizedProduct['id'] ?? 0) > 0 && $getUpdateOnlyPriceStock) {
                $productSku = $normalizedProduct['sku'];
                $productId = $normalizedProduct['id'];
                $varProductId = $productId;

                if ($is_variation_grouped)  {
                    $parsedProductToUpdate = $parsedProduct;
                    $parsedProductToUpdate['sku']['value'] = $normalizedProduct['_sku_variation_agrupped'] ?? $normalizedProduct['sku']['value'];
                    $parsedProductToUpdate = $this->toolsProduct->normalizedFormattedData($parsedProductToUpdate);
                    $this->toolsProduct->updateVariation($parsedProductToUpdate, $normalizedProduct['sku']);
                    $productSku = $parsedProductToUpdate['sku'];
                } else {
                    $this->toolsProduct->updateProduct($parsedProduct);
                }

                if (empty($normalizedProduct['variations'])) {
                    $this->toolsProduct->updateStockProduct($normalizedProduct['sku'], $normalizedProduct['stock'], $normalizedProduct['_sku_variation_agrupped'] ?? null);
                }

                $this->toolsProduct->updatePriceProduct($normalizedProduct['sku'], $normalizedProduct['price'], $normalizedProduct['list_price'], $normalizedProduct['_sku_variation_agrupped'] ?? null);
                $hasNewVariation = false;
                foreach ($parsedVariations as $parsedVariation) {
                    if (empty($parsedVariation['id'] ?? 0)) {
                        if (!$this->toolsProduct->store_uses_catalog) {
                            $hasNewVariation = true;
                            continue;
                        }
                    }

                    $this->toolsProduct->updateVariation($parsedVariation, $productSku);
                    $this->toolsProduct->updatePriceProduct(
                        $normalizedProduct['sku'],
                        $parsedVariation['price'],
                        $parsedVariation['list_price'] ?? null,
                        $parsedVariation['sku']
                    );
                    $this->toolsProduct->updateStockProduct(
                        $normalizedProduct['sku'],
                        $parsedVariation['stock'],
                        $parsedVariation['sku']
                    );
                    $varProductId = $parsedVariation['id'];
                    $productSku = $parsedVariation['sku'];
                }

                $response = $this->toolsProduct->sendSuccessTransmission(array_merge($body, [
                        'id' => $varProductId,
                        'productId' => $productId,
                        'sku' => $productSku,
                        'status' => $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT ? 'ACTIVE' : 'PAUSED',
                        'marketplaceStatus' => $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT ? 'ATIVO' : 'INATIVO',
                    ]
                ));
                if (!$hasNewVariation) {
                    $body['transmission'] = $response;
                }
                $this->insertQueue($body);
                $this->response($response, self::HTTP_OK);
                return;
            } elseif ($this->toolsProduct->store_uses_catalog) {
                if (empty($normalizedProduct['_product_id_erp'])) {
                    $this->toolsProduct->updateProductIdIntegration($normalizedProduct['sku'], $body['idProduct'] ?? $body['id'] ?? $body['idSkuMarketplace']);
                    foreach ($parsedVariations as $parsedVariation) {
                        if (!empty($parsedVariation['_variant_id_erp'])) {
                            $this->toolsProduct->updateProductIdIntegration($normalizedProduct['sku'], $parsedVariation['_variant_id_erp'], $parsedVariation['sku']);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $error_message = $e->getMessage();
            $this->saveLog($uuid, $error_message, 'E');
            if (($normalizedProduct['id'] ?? 0) > 0) {
                if (empty($product_data)) {
                    $product_data = $this->model_products->getProductData(0, $normalizedProduct['id'] ?? 0);
                }
                $currentStatus = $product_data['status'] ?? null;
            }
            $status = $currentStatus ?? null;
            $body['status'] = $status === null ? 'UNPUBLISHED' : ($status == Model_products::ACTIVE_PRODUCT ? 'ACTIVE' : 'PAUSED');
            $response = $this->toolsProduct->sendErrorTransmission($body, $error_message);
            if (((int)$e->getCode()) === 404) {
                $this->response($response, self::HTTP_OK);
                return;
            }
            $body['transmission'] = $response;
            $this->insertQueue($body);
            $this->response($response, self::HTTP_OK);
            return;
        }

        $body['marketplaceStatus'] = "O Marketplace está processando o produto.";
        $body['transmissionStatus'] = "OK";
        $response = $this->toolsProduct->sendSuccessTransmission($body);
        $this->insertQueue($body);
        $this->response(array_merge($response, [
            'status' => 'UNPUBLISHED'
        ]), self::HTTP_OK);
    }

    protected function insertQueue($body)
    {
        $onlySyncPrice = false;
        $onlySyncStock = false;
        $onlySyncStatus = false;
        $updateStatus = false;
        $updatePrice = false;
        $updateStock = false;

        if (array_key_exists('onlySyncPrice', $body) && $body['onlySyncPrice']) {
            $onlySyncPrice = true;
        }
        if (array_key_exists('onlySyncStock', $body) && $body['onlySyncStock']) {
            $onlySyncStock = true;
        }
        if (array_key_exists('onlySyncStatus', $body) && $body['onlySyncStatus']) {
            $onlySyncStatus = true;
        }
        if (array_key_exists('updateStatus', $body) && $body['updateStatus']) {
            $updateStatus = true;
        }
        if (array_key_exists('updatePrice', $body) && $body['updatePrice']) {
            $updatePrice = true;
        }
        if (array_key_exists('updateStock', $body) && $body['updateStock']) {
            $updateStock = true;
        }

        if  (!$onlySyncPrice && !$onlySyncStock && !$onlySyncStatus && !$updateStatus && !$updatePrice && !$updateStock) {
            if (array_key_exists('updateStatus', $body) && !$body['updateStatus']) {
                $body['updateStatus'] = true;
            }
            if (array_key_exists('updatePrice', $body) && !$body['updatePrice']) {
                $body['updatePrice'] = true;
            }
            if (array_key_exists('updateStock', $body) && !$body['updateStock']) {
                $body['updateStock'] = true;
            }
        }

        $integration = $this->accountIntegration;
        $anymarketQueue = $this->db->select()
            ->from('anymarket_queue')
            ->where([
                'idSku' => (string)$body['idSku'],
                'integration_id' => $integration['id'],
                'checked' => 0
            ])->get()->row_array();
        if (!empty($anymarketQueue)) return;

        $data = [
            'received_body' => json_encode($body),
            'integration_id' => $integration['id'],
            'idSku' => $body['idSku'] ?? 0,
            'idProduct' => $body['idProduct'] ?? 0,
            'idSkuMarketplace' => $body['idSkuMarketplace'],
        ];
        $this->db->insert('anymarket_queue', $data);
    }
}
