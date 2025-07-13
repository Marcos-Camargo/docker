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

    public function index_post()
    {
        $body = file_get_contents('php://input');

        $body = json_decode($body, true);

        try {
            $this->toolsProduct->setUniqueId($body['idSkuMarketplace'] ?? $body['id']);
            $parsedProduct = $this->toolsProduct->getDataFormattedToIntegration([
                'idSkuMarketplace' => $body['idSkuMarketplace'] ?? $body['id']
            ]);
            $normalizedProduct = $this->toolsProduct->normalizedFormattedData($parsedProduct);
            $parsedVariation = $this->toolsProduct->getParsedVariations();

            $varProductId = $parsedVariation[0]['id'] ?? $normalizedProduct['id'] ?? null;
            $productId = $normalizedProduct['id'] ?? null;
            $productSku = $parsedVariation[0]['sku'] ?? $normalizedProduct['sku'];

            if (empty($normalizedProduct['id'])) {
                $response = $this->toolsProduct->sendTransmissionIntegration(array_merge([
                        'id'                => $varProductId,
                        'productId'         => $productId,
                        'sku'               => $productSku,
                        'status'            => 'UNPUBLISHED'
                    ], $body
                ), true);

                if (!$this->toolsProduct->store_uses_catalog) {
                    $this->insertQueue($body);
                }
                $this->response($response, self::HTTP_OK);
                return;
            }

            $product_data = $this->toolsProduct->productIntegrationValidation->getProduct();
            $is_variation_grouped = !empty($product_data) && $product_data['is_variation_grouped'];
            $has_variants_in_sellercenter = !empty($product_data['has_variants']);

            // Caso o produto é simples na integradora, mas foi transformado em produto com variação, deve fazer a atualização.
            if ($is_variation_grouped) {
                $parsedProductToUpdate = $parsedProduct;
                $parsedProductToUpdate['sku']['value'] = $normalizedProduct['_sku_variation_agrupped'] ?? $normalizedProduct['sku']['value'];
                $parsedProductToUpdate = $this->toolsProduct->normalizedFormattedData($parsedProductToUpdate);
                // Não deve atualizar, preço, estoque de um produto agrupado por aqui.
                unset($parsedProductToUpdate['qty']);
                unset($parsedProductToUpdate['stock']);
                unset($parsedProductToUpdate['price']);
                unset($parsedProductToUpdate['list_price']);
                $this->toolsProduct->updateVariation($parsedProductToUpdate, $normalizedProduct['sku']);
                $productSku = $parsedProductToUpdate['sku'];
            } else {
                // Atualizar dados do produto.
                $this->toolsProduct->updateProduct($parsedProduct);
            }

            // É um produto simples, deve atualizar preço e estoque do produto
            if (empty($normalizedProduct['variations']) || $this->toolsProduct->store_uses_catalog) {
                if (!$has_variants_in_sellercenter && $this->toolsProduct->store_uses_catalog && !empty($parsedVariation)) {
                    $normalizedProduct['price'] = $parsedVariation[0]['price'];
                    $normalizedProduct['list_price'] = $parsedVariation[0]['list_price'];
                    $normalizedProduct['stock'] = $parsedVariation[0]['stock'];
                }
                $this->toolsProduct->updatePriceProduct($normalizedProduct['sku'], $normalizedProduct['price'], $normalizedProduct['list_price'], $normalizedProduct['_sku_variation_agrupped'] ?? null);
                $this->toolsProduct->updateStockProduct($normalizedProduct['sku'], $normalizedProduct['stock'], $normalizedProduct['_sku_variation_agrupped'] ?? null);
            }

            $sku_status = $normalizedProduct['status'] == Model_products::ACTIVE_PRODUCT;
            // Quantidade em estoque
            $stock_sku = $this->toolsProduct->getRealValueNormalized($normalizedProduct, 'stock') ?: 0;

            // Existe variação
            if (!empty($parsedVariation)) {
                $parsedVariation = $parsedVariation[0];
                $hasNewVariation = empty($parsedVariation['id']) && !$this->toolsProduct->store_uses_catalog;
                $sku_status = $parsedVariation['status'] == Model_products::ACTIVE_PRODUCT;
                $stock_sku = $this->toolsProduct->getRealValueNormalized($parsedVariation, 'stock') ?: 0;

                $varProductId = $parsedVariation['id'];
                $productSku = $parsedVariation['sku'];

                // Variação ainda não existe
                if (empty($parsedVariation['id'])) {
                    $transmission_data = [
                        'id'                => $varProductId,
                        'productId'         => $productId,
                        'sku'               => $productSku,
                        'status'            => 'UNPUBLISHED'
                    ];
                    
                    if ($this->toolsProduct->store_uses_catalog) {
                        $transmission_data = [
                            'id'                => $varProductId,
                            'productId'         => $productId,
                            'sku'               => $productSku,
                            'status'            => $sku_status ? 'ACTIVE' : 'PAUSED',
                            'marketplaceStatus' => $sku_status ? 'ATIVO' : 'INATIVO',
                        ];
                    }

                    $response = $this->toolsProduct->sendTransmissionIntegration(array_merge($transmission_data, $body), true, $stock_sku);

                    if (!$hasNewVariation) {
                        $body['transmission'] = $response;
                    }
                    if (!$this->toolsProduct->store_uses_catalog) {
                        $this->insertQueue($body);
                    } else {
                        $this->toolsProduct->checkIdSkuIntegration($normalizedProduct, $parsedVariation);
                    }
                    $this->response($response, self::HTTP_OK);
                    return;
                }

                // Variação existe, então deve atualizar o sku, preço e estoque.
                // Atualizar sku.
                $this->toolsProduct->updateVariation($parsedVariation, $productSku);
                // Atualizar preço.
                $this->toolsProduct->updatePriceProduct(
                    $normalizedProduct['sku'],
                    $parsedVariation['price'],
                    $parsedVariation['list_price'] ?? null,
                    $parsedVariation['sku']
                );
                // Atualizar estoque.
                $this->toolsProduct->updateStockProduct(
                    $normalizedProduct['sku'],
                    $parsedVariation['stock'],
                    $parsedVariation['sku']
                );
            }

            $response = $this->toolsProduct->sendTransmissionIntegration(array_merge([
                    'id'                => $varProductId,
                    'productId'         => $productId,
                    'sku'               => $productSku,
                    'status'            => $sku_status ? 'ACTIVE' : 'PAUSED',
                    'marketplaceStatus' => $sku_status ? 'ATIVO' : 'INATIVO',
                ], $body
            ), true, $stock_sku);

            if (!empty($body['sent_from_trash'])) {
                $current_product_id_erp = is_array($normalizedProduct['_current_product_id_erp']) && array_key_exists('value', $normalizedProduct['_current_product_id_erp']) ?
                    $normalizedProduct['_current_product_id_erp']['value'] : (array_key_exists('_current_variant_id_erp', $normalizedProduct) ? $normalizedProduct['_current_product_id_erp'] : 'CANNOT_UPDATE');
                $product_id_erp = is_array($normalizedProduct['_product_id_erp']) && array_key_exists('value', $normalizedProduct['_product_id_erp']) ?
                    $normalizedProduct['_product_id_erp']['value'] : ($normalizedProduct['_product_id_erp'] ?? NULL);

                if (is_null($current_product_id_erp) && !is_null($product_id_erp)) {
                    $this->toolsProduct->updateProductIdIntegration($normalizedProduct['sku'], $product_id_erp);
                }


                if (!empty($parsedVariation)) {
                    $current_variant_id_erp = is_array($parsedVariation['_current_variant_id_erp']) && array_key_exists('value', $parsedVariation['_current_variant_id_erp']) ?
                        $parsedVariation['_current_variant_id_erp']['value'] : (array_key_exists('_current_variant_id_erp', $parsedVariation) ? $parsedVariation['_current_variant_id_erp'] : 'CANNOT_UPDATE');
                    $variant_id_erp = is_array($parsedVariation['_variant_id_erp']) && array_key_exists('value', $parsedVariation['_variant_id_erp']) ?
                        $parsedVariation['_variant_id_erp']['value'] : ($parsedVariation['_variant_id_erp'] ?? NULL);

                    if (is_null($current_variant_id_erp) && !is_null($variant_id_erp)) {
                        $this->toolsProduct->updateProductIdIntegration($normalizedProduct['sku'], $variant_id_erp, $parsedVariation['sku']);
                    }
                }
            }

            $this->toolsProduct->checkIdSkuIntegration($normalizedProduct, $parsedVariation);
            $this->response($response, self::HTTP_OK);
            return;
        } catch (Throwable $e) {
            $error_message = $e->getMessage();
            $currentStatus = null;
            if (!empty($normalizedProduct['id'])) {
                if (empty($product_data)) {
                    $product_data = $this->model_products->getProductData(0, $normalizedProduct['id']);
                }
                $currentStatus = $product_data['status'] ?? null;
            }
            $status = $currentStatus;
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
