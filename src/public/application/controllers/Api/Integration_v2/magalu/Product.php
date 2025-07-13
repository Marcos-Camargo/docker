<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/magalu/ToolsProduct.php";

use Integration\Integration_v2\magalu\ToolsProduct;

class Product extends REST_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

    /**
     * Instantiate a new Product instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolProduct = new ToolsProduct();
        header('Integration: v2');
    }

    /**
     * Atualização de produto, deve ser recebido via POST
     */
    public function index_get()
    {
        $this->log_data('WebHook', 'WebHookProduct', "Chegou GET, não deveria\n_GET=".json_encode($_GET), "E");
        return $this->response("method not accepted", REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de produto, deve ser recebido via POST
     */
    public function index_put()
    {
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook', 'WebHookProduct', "Chegou PUT, não deveria\n_GET=".json_encode($_GET)."\n".json_encode($product), "E");
        return $this->response("method not accepted", REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de produto
     */
    public function index_post()
    {
        $headers = getallheaders();
        foreach ($headers as $header => $value) {
            $headers[strtolower($header)] = $value;
        }
        if (!isset($headers['token'])) {
            return $this->response(array("message" => "apiKey não encontrado"), REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($headers['token'], FILTER_SANITIZE_STRING);

        $store = $this->toolProduct->getStoreForApiKey($apiKey);
        if (!$store) {
            return $this->response(array("message" => 'apiKey não corresponde a nenhuma loja'), REST_Controller::HTTP_UNAUTHORIZED);
        }

        try {
            $this->toolProduct->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $this->toolProduct->log_integration(
                "Erro para receber notificação",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            return $this->response($exception->getMessage(), REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Recupera dados enviado pelo body
        $product = $this->cleanGet(json_decode($this->input->raw_input_stream));
        $this->log_data('WebHook', 'WebHookProduct', json_encode($product));

        // não conseguiu descodificar o JSON
        if ($product == null) {
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        $id = $product->id;
        $sku = $product->parent_sku;

        try {
            $pricing_and_availability = $this->toolProduct->getPriceStockErp($id);

            $price = $pricing_and_availability['price_product'];
            $list_price = $pricing_and_availability['listPrice_product'];
            $stock = $pricing_and_availability['stock_product'];

            // verificar se o SKU é uma variação ou produto simples
            $has_variants = false;
            $data_product = $this->toolProduct->getProductForSku($sku);
            if (!$data_product) {
                $data_product = $this->toolProduct->getVariationBySkuFather($sku);
                if (!$data_product) {
                    throw new InvalidArgumentException("SKU $sku não encontrado");
                }
                $has_variants = true;
            }

            if ($has_variants) {
                $this->toolProduct->updatePriceProduct($data_product['sku'], $price, $list_price, $sku);
                $this->toolProduct->updateStockProduct($data_product['sku'], $stock, $sku);
            } else {
                $this->toolProduct->updatePriceProduct($sku, $price, $list_price);
                $this->toolProduct->updateStockProduct($sku, $stock);
            }

        } catch (InvalidArgumentException $exception) {
            return $this->response(array("message" => $exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(null, REST_Controller::HTTP_OK);
    }
}