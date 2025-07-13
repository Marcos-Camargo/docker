<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/tiny/ToolsProduct.php";

use Integration\Integration_v2\tiny\ToolsProduct;

class CreateProduct extends REST_Controller
{
    /**
     * @var ToolsProduct
     */
    private $toolProduct;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->toolProduct = new ToolsProduct();
        $this->toolProduct->setJob('CreateProduct');
        header('Integration: v2');
    }

    /**
     * Atualização de estoque, receber via POST(eu acho, confirmar com Tiny)
     */
    public function index_post()
    {
        ob_start();
        $product = json_decode(file_get_contents('php://input'));
        //$this->log_data('api', 'Api/CreateProduct/Tiny', json_encode($product));

        if (empty($product) || !property_exists($product, 'dados')) {
            return $this->response([
                "error" => "Dados enviados não reconhecido."
            ], REST_Controller::HTTP_OK);
        }

        if (!isset($_GET['apiKey'])) {
            $mapeamento = $this->getMapeamento($product->dados, 'apiKey não encontrado');
            ob_clean();
            return $this->response($mapeamento, REST_Controller::HTTP_OK);
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);

        $store = $this->toolProduct->getStoreForApiKey($apiKey);
        if (!$store) {
            $mapeamento = $this->getMapeamento($product->dados, 'apiKey não corresponde a nenhuma loja');
            ob_clean();
            return $this->response($mapeamento, REST_Controller::HTTP_OK);
        }

        try {
            $this->toolProduct->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $this->toolProduct->log_integration(
                "Erro para receber notificação",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );

            $mapeamento = $this->getMapeamento($product->dados, $exception->getMessage());
            ob_clean();
            return $this->response($mapeamento, REST_Controller::HTTP_OK);
        }

        // chegou um tipo diferente de estoque (não implementado)
        if ($product->tipo != "produto") {
            $mapeamento = $this->getMapeamento($product->dados, 'Tipo diferente de produto');
            ob_clean();
            return $this->response($mapeamento, REST_Controller::HTTP_OK);
        }
        $product = $product->dados;

        $existVariation = property_exists($product, 'variacoes') && count($product->variacoes);
        $skuProduct     = trim($product->codigo);
        $idProduct      = $product->id;

        $this->toolProduct->setUniqueId($skuProduct);
        $verifyProduct = $this->toolProduct->getProductForSku($skuProduct);

        $errorIntegration = null;

        if (!$existVariation) {
            if ($this->toolProduct->store_uses_catalog) {
                if (!$verifyProduct) {
                    $errorIntegration = "SKU não localizado para realizar o mapeamento.";
                } else {
                    $this->toolProduct->updateProductIdIntegration($skuProduct, $idProduct);
                }
            } else {
                $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product, 'webhook');
                // SKU não localizado na loja. Deve tentar cadastrar
                if (!$verifyProduct) {
                    try {
                        $this->toolProduct->sendProduct($dataProductFormatted, true);
                        // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                        // muitas vezes o produto chegará não categorizado então esse cenário não acontecerá
                        $verifyProduct = $this->toolProduct->getProductForSku($skuProduct);
                        $attributes = $this->toolProduct->getAttributeProduct($verifyProduct["id"], $skuProduct);
                        if (!empty($attribute)) {
                            $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                        }

                        $this->toolProduct->updateProductIdIntegration($skuProduct, $idProduct);
                    } catch (InvalidArgumentException $exception) {
                        $errorIntegration = $exception->getMessage();
                    }
                }
                // SKU localizado na loja
                else {
                    try {
                        $this->toolProduct->updateProduct($dataProductFormatted);
                    } catch (InvalidArgumentException $exception) {
                        $errorIntegration = $exception->getMessage();
                    }
                    // produto atualizado com código da integradora
                    if ($verifyProduct['product_id_erp'] != $idProduct) {
                        $this->toolProduct->updateProductIdIntegration($skuProduct, $idProduct);
                    }
                }
            }
        }
        // É variação, então precisa ler os dados do produto e em seguida ler os skus para cadastrar na variação
        else {
            if ($this->toolProduct->store_uses_catalog) {
                if (!$verifyProduct) {
                    $errorIntegration = "SKU não localizado para realizar o mapeamento.";
                } else {
                    foreach ($product->variacoes as $variation) {
                        $this->toolProduct->updateProductIdIntegration($skuProduct, $variation->id, $variation->codigo);
                    }
                }
            } else {
                // Produto pai não localizado na loja. Deve tentar cadastrar
                if (!$verifyProduct) {
                    try {
                        $this->toolProduct->sendProduct($this->toolProduct->getDataFormattedToIntegration($product, 'webhook'), true);

                        // produto criado, recuperei o ID do sku e definirei os atributos para a categoria do produto
                        // muitas vezes o produto chegará não categorizado então esse cenário não acontecerá
                        $verifyProduct = $this->toolProduct->getProductForSku($skuProduct);
                        $attributes = $this->toolProduct->getAttributeProduct($verifyProduct["id"], $skuProduct);
                        if (!empty($attribute)) {
                            $this->toolProduct->setAttributeProduct($verifyProduct['id'], $attributes);
                        }

                        $this->toolProduct->updateProductIdIntegration($skuProduct, $idProduct);
                        foreach ($product->variacoes as $variation) {
                            $this->toolProduct->updateProductIdIntegration($skuProduct, $variation->id, $variation->codigo);
                        }
                    } catch (InvalidArgumentException $exception) {
                        $errorIntegration = $exception->getMessage();
                    }
                }
                // sku do produto pai encontrado na loja, precisa ver se todos os skus estão cadastrados nas variações
                else {
                    $dataProductFormatted = $this->toolProduct->getDataFormattedToIntegration($product, 'webhook');

                    try {
                        $this->toolProduct->updateProduct($dataProductFormatted);
                    } catch (InvalidArgumentException $exception) {
                        $errorIntegration = $exception->getMessage();
                    }

                    if ($errorIntegration === null) {
                        // ler todos os skus, para saber se todas as variações estão cadastradas
                        foreach ($dataProductFormatted['variations']['value'] as $variation) {
                            $verifyVariation = $this->toolProduct->getVariationForSkuAndSkuVar($skuProduct, $variation['sku']);
                            // variação não localizada cadastrada no produto pai
                            if (!$verifyVariation) {
                                try {
                                    $this->toolProduct->sendVariation($dataProductFormatted, $variation['sku'], $skuProduct, true);
                                    $this->toolProduct->updateProductIdIntegration($skuProduct, $variation['id'], $variation['sku']);
                                } catch (InvalidArgumentException $exception) {
                                    $errorIntegration = $exception->getMessage();
                                }
                            } // sku localizada, cadastrada como variação no produto
                            else {
                                // Variação atualizada com código da integradora
                                if ($verifyVariation['variant_id_erp'] != $variation['id']) {
                                    $this->toolProduct->updateProductIdIntegration($skuProduct, $variation['id'], $variation['sku']);
                                }
                            }
                        }
                    }
                }
            }
        }

        $mapeamento = $this->getMapeamento($product, $errorIntegration);

        ob_clean();
        return $this->response($mapeamento, REST_Controller::HTTP_OK);
    }

    /**
     * Resposta de mapeamento da integradora
     *
     * https://www.tiny.com.br/ajuda/api/api2-webhooks-envio-produtos
     * https://www.tiny.com.br/help-content/files/webhook-produto-retorno.json
     *
     * @param   object      $payload    Dados do pedido recebido pela integradora
     * @param   string|null $erroAll
     * @return  array
     */
    public function getMapeamento(object $payload, string $erroAll = null): array
    {
        if (!property_exists($payload, 'idMapeamento') || !property_exists($payload, 'codigo')) {
            return [
                "error" => "Dados enviados não reconhecido."
            ];
        }

        $arrMapeamentoTiny = array();
        $arrMap = array(
            "idMapeamento"  => $payload->idMapeamento,
            "skuMapeamento" => $payload->codigo
        );

        if ($erroAll) {
            $arrMap["error"] = $erroAll;
        }
        array_push($arrMapeamentoTiny, $arrMap);

        foreach ($payload->variacoes as $mapeamento) {
            $arrMap = array(
                "idMapeamento"  => $mapeamento->idMapeamento,
                "skuMapeamento" => $mapeamento->codigo
            );
            if ($erroAll) {
                $arrMap["error"] = $erroAll;
            }

            array_push($arrMapeamentoTiny, $arrMap);
        }

        return $arrMapeamentoTiny;
    }
}