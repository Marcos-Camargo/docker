<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/tiny/ToolsProduct.php";

use Integration\Integration_v2\tiny\ToolsProduct;

class UpdatePrice extends REST_Controller
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
        $this->toolProduct->setJob('UpdatePriceStock');
        header('Integration: v2');
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
            return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
        }

        // Recupera dados enviado pelo body
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'Api/UpdatePriceStock/Tiny', json_encode($product));

        // chegou um tipo diferente de estoque (não implementado)
        if (!in_array($product->tipo, array('estoque', 'precos'))) {
            return $this->response('Tipo diferente de estoque ou precos', REST_Controller::HTTP_BAD_REQUEST);
        }

        $typeRequest = $product->tipo;
        $product     = $product->dados;

        $existVariation = !empty($product->skuMapeamentoPai);

        // Na configuração da integração (onde são informadas as URLs dos webhooks) são disponibilizadas as mesmas opções
        // existentes nas integrações implementadas pela Tiny, ou seja, se deve ser enviado o saldo disponível,
        // o físico ou o saldo de um depósito.

        $precoPromocional = property_exists($product, 'precoPromocional') ? (float)$product->precoPromocional : (float)$product->preco_promocional;
        $precoPromocional = $precoPromocional > 0 ? (float)$precoPromocional : null;
        $price = empty($precoPromocional ?? null) ? ((float)$product->preco ?? 0) : $precoPromocional;
        $listPrice = (float)($product->preco > 0 ? $product->preco : $precoPromocional);

        $stock = $product->saldo ?? 0;
        $stockReserved = $product->saldoReservado ?? 0;

        $stock -= $stockReserved;

        if (isset($this->credentials->stock_tiny) && !empty($this->credentials->stock_tiny)) {
            $stock = 0;
            foreach ($product->depositos as $deposit) {
                if (strtolower($deposit->deposito->nome) === strtolower($this->credentials->stock_tiny)) {
                    $stock = $deposit->deposito->saldo - $stockReserved;
                    break;
                }
            }
        }
        
        if ($existVariation) {
            $this->toolProduct->setUniqueId($product->skuMapeamentoPai);
            if ($typeRequest === 'estoque') {
                $this->toolProduct->updateStockVariation($product->skuMapeamento, $product->skuMapeamentoPai, $stock);
            } elseif ($typeRequest === 'precos') {
                $this->toolProduct->updatePriceVariation($product->skuMapeamento, $product->skuMapeamentoPai, $price, $listPrice);
            }
        } else {
            $this->toolProduct->setUniqueId($product->skuMapeamento);
            if ($typeRequest === 'estoque') {
                $this->toolProduct->updateStockProduct($product->skuMapeamento, $stock);
            } elseif ($typeRequest === 'precos') {
                $this->toolProduct->updatePriceProduct($product->skuMapeamento, $price,$listPrice);
            }
        }

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
    }
}