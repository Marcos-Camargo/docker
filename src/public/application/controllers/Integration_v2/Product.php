<?php

use Integration\Integration_v2\anymarket\ApiException;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_api_integrations $model_api_integrations
 * @property Model_products $model_products
 */
class Product extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        if ($this->data['only_admin'] != 1) {
            redirect('dashboard', 'refresh');
        }

        $this->load->model('model_api_integrations');
    }

    public function search(string $type = null, int $store_id = null, string $sku = null, bool $debug = false): CI_Output
    {
        if (!$debug) {
            $this->output->set_content_type('application/json');
        }

        if (empty($type)) {
            return $this->output->set_output(json_encode(array('error' => "Informe um tipo de solicitação válida."),JSON_UNESCAPED_UNICODE));
        }
        if (empty($store_id)) {
            return $this->output->set_output(json_encode(array('error' => "Informe uma loja válida."),JSON_UNESCAPED_UNICODE));
        }
        if (empty($sku)) {
            return $this->output->set_output(json_encode(array('error' => "Informe um sku válido."),JSON_UNESCAPED_UNICODE));
        }

        $data_integration = $this->model_api_integrations->getIntegrationByStore($store_id);

        if (!$data_integration) {
            return $this->output->set_output(json_encode(array('error' => "Integração não encontrada para a loja $store_id. (api_integrations)"),JSON_UNESCAPED_UNICODE));
        }

        if (likeText('viavarejo_b2b%', $data_integration['integration'])) {
            $data_integration['integration'] = 'viavarejo_b2b';
        }

        require APPPATH . "libraries/Integration_v2/$data_integration[integration]/ToolsProduct.php";
        $instance = "Integration\Integration_v2\\$data_integration[integration]\ToolsProduct";
        $toolsProduct = new $instance($this);

        if (!$debug) {
            ob_start();
        }
        try {
            $toolsProduct->startRun($store_id);

            if ($debug) {
                $toolsProduct->setDebug(true);
            }

            $product = $this->model_products->getProductBySkuAndStore($sku, $store_id);
            if (!$product) {
                $variation = $this->model_products->getVariantsBySkuAndStore($sku, $store_id);
                if (!$variation) {
                    throw new Exception("SKU não localizado para a loja.");
                } else {
                    $product = $this->model_products->getProductData(0, $variation['prd_id']);
                    $sku_integration = $variation['variant_id_erp'];
                    $product_id_erp = $product['product_id_erp'];
                    $product_id = $variation['prd_id'];
                }
            } else {
                $product_id_erp = $product['product_id_erp'];
                $sku_integration = $product_id_erp;
                $product_id = $product['id'];
            }

            if (!$sku_integration) {
                throw new Exception("SKU sem vínculo de integração");
            }

            switch ($type) {
                case 'sku':
                    try {
                        $response = $toolsProduct->getDataProductIntegration($sku_integration);
                    } catch (Throwable|ApiException|InvalidArgumentException $e) {
                        try {
                            $response = $toolsProduct->getDataProductIntegration($sku);
                        } catch (Throwable|ApiException|InvalidArgumentException $e) {
                            try {
                                $response = $toolsProduct->getProductsBySku($sku);
                            } catch (Throwable|ApiException|InvalidArgumentException $e) {
                                $response = array('error' => 'Sku não localizado');
                            }
                        }
                    }
                    break;
                case 'price':
                    $response = $toolsProduct->getPriceErp($sku_integration) ?? $toolsProduct->getPriceErp($sku) ?? array('error' => 'Preço do sku não localizado');
                    break;
                case 'stock':
                    $response = $toolsProduct->getStockErp($sku_integration) ?? $toolsProduct->getStockErp($sku) ?? array('error' => 'Estoque do sku não localizado');
                    break;
                case 'attribute':
                    $response = $toolsProduct->getAttributeProduct($product_id, $product_id_erp) ?? array('error' => 'Atributos do sku não localizado');
                    break;
                default:
                    throw new Exception("Tipo de parâmetro não encontrado");
            }

            if (!$debug) {
                ob_clean();
                return $this->output->set_output(json_encode($response,JSON_UNESCAPED_UNICODE));
            }

            echo "\n";
            echo "response:\n";
            return $this->output->set_output(json_encode($response,JSON_UNESCAPED_UNICODE));
        } catch (Throwable|ApiException|InvalidArgumentException|Exception $e) {
            if (!$debug) {
                ob_clean();
                return $this->output->set_output(json_encode(array('error' => array('Message' => $e->getMessage(), 'Code' => $e->getCode())),JSON_UNESCAPED_UNICODE));
            }

            echo "\n";
            echo "response:\n";
            return $this->output->set_output(json_encode($e->getMessage(),JSON_UNESCAPED_UNICODE));
        }
    }
}
