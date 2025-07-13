<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/bling/ToolsProduct.php";

use Integration\Integration_v2\bling\ToolsProduct;

class UpdateStock extends REST_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

    /**
     * Instantiate a new UpdateStock instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolProduct = new ToolsProduct();
        header('Integration: v2');
    }

    /**
     * Atualização de estoque, deve ser recebido via POST
     */
    public function index_put()
    {
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook', 'WebHookUpdateProd', "Chegou PUT, não deveria\n_GET=".json_encode($_GET)."\n".json_encode($product), "E");
        return $this->response("method not accepted", REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Atualização de estoque
     */
    public function index_post()
    {
        ob_start();
        if (!isset($_GET['apiKey'])) {
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);

        $store = $this->toolProduct->getStoreForApiKey($apiKey);
        if (!$store) {
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
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
        $product = json_decode(str_replace('data=', '', file_get_contents('php://input')));
        $this->log_data('WebHook', 'WebHookUpdateStock', json_encode($product));

        // não conseguiu descodificar o JSON
        if ($product == null) {
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        $products = (array)($product->retorno->estoques ?? array());

        foreach ($products as $product) {
            $product = $product->estoque;

            $sku = $product->codigo ?? '';

            $existVariation = true;
            $dataProduct = null;

            $this->toolProduct->setUniqueId($sku);

            // verificar se o SKU é uma variação ou produto simples
            if ($this->toolProduct->getProductForSku($sku)) {
                $existVariation = false;
            } else {
                $dataProduct = $this->toolProduct->getVariationBySkuFather($sku);
                if (!$dataProduct) {
                    continue;
                }
            }

            // consulta o estoque, caso a loja tenha configurado apenas um estoque
            $stock = $product->estoqueAtual;
            if (!empty($this->toolProduct->credentials->stock_bling)) {
                foreach ($product->depositos as $deposito) {
                    if ($this->toolProduct->credentials->stock_bling == $deposito->nome) {
                        $stock = $deposito->saldo;
                        break;
                    }
                }
            }

            // atualiza o estoque da variação
            if ($existVariation) {
                $this->toolProduct->updateStockVariation($sku, $dataProduct['sku'], $stock);
            }
            // atualiza o estoque do produto
            else {
                $this->toolProduct->updateStockProduct($sku, $stock);
            }
        }

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
    }
}