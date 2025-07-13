<?php

use GuzzleHttp\Utils;

/**
 * @property Model_products $model_products
 */

class Viavarejo_b2b extends Logistic
{
    protected $finalEndpoint = '';
    protected $tokenEndpoint = '';

    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $auth = array();

        $auth['json']['idCampanha'] = $this->credentials['campaign'];
        $auth['json']['cnpj'] = cnpj(onlyNumbers($this->credentials['cnpj']));

        $auth['headers']['Accept'] = 'text/plain';
        $auth['headers']['Authorization'] = $this->credentials['token_b2b_via'];

        $auth['query']['cnpj'] = $auth['json']['cnpj'];
        $auth['query']['idLojista'] = $this->credentials['idLojista'];

        if (ENVIRONMENT === 'development') {
            $this->finalEndpoint = "https://b2b-integracao.{$this->credentials['flag']}.viavarejo-hlg.com.br";
        } else {
            $this->finalEndpoint = "https://api-integracao-b2b.{$this->credentials['flag']}.com.br";
        }

        $getEndpoint = $this->dbReadonly->where(['name' => 'fixed_ip_api_url', 'status' => 1])->get('settings')->row_object();
        $getTokenEndpoint = $this->dbReadonly->where(['name' => 'fixed_ip_token_url', 'status' => 1])->get('settings')->row_object();
        $endpoint = $this->finalEndpoint;
        if ($getEndpoint) {
            $endpoint = $getEndpoint->value;
        }
        if ($getTokenEndpoint) {
            $this->tokenEndpoint = $getTokenEndpoint->value;
        }
        $this->setEndpoint($endpoint);

        $this->authRequest = $auth;
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse() {}

    /**
     * Cotação.
     *
     * @param   array   $dataQuote      Dados para realizar a cotação.
     * @param   bool    $moduloFrete    Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        $this->load->model('model_products');
        $arrQuote = array(
            "cep"                   => $dataQuote['zipcodeRecipient'],
            "produtos"              => array(),
//            "idUnidadeNegocio"      => 1,
//            "idEnderecoLojaFisica"  => 1,
//            "idEntregaTipo"         => 1
        );

        $arrSkuProductId = array();

        foreach ($dataQuote['items'] as $sku) {
            $dataProduct = array(
                "codigo"        => $sku['skuseller'],
                "quantidade"    => $sku['quantidade'],
                "idLojista"     => $this->credentials['idLojista'],
            );

            $arrSkuProductId[$sku['skuseller']] = array(
                'skumkt'    => $sku['sku'],
                'prd_id'    => $dataQuote['dataInternal'][$sku['sku']]['prd_id'],
                'price'     => $dataQuote['dataInternal'][$sku['sku']]['price'],
                'variant'   => $dataQuote['dataInternal'][$sku['sku']]['variant']
            );

            $arrQuote['produtos'][] = $dataProduct;
        }

        try {
            $servicesSla = $this->getQuoteUnit($arrQuote, $arrSkuProductId, $dataQuote['crossDocking']);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $servicesSla
            )
        );
    }

    /**
     * @param array $body Corpo da requisição.
     * @param array $arrSkuProductId Código de SKU e PRD_ID para gerar o retorno.
     * @return  array
     * @throws  InvalidArgumentException
     */
    private function getQuoteUnit(array $body, array $arrSkuProductId, int $crossDocking): array
    {
        $servicesSla    = array();
        $promises       = array();
        $productsToSku  = array();

        // Monta as requisições para serem feitas assíncronas.
        foreach ($body['produtos'] as $product) {
            $options = $this->getBodyRequestInternal('GET', "/campanhas/{$this->credentials['campaign']}/produtos/{$product['codigo']}", array());

            // Remover o body porque é GET.
            unset($options['options']['json']);

            $productsToSku[$product['codigo']] = $product;
            $promises["$product[codigo]"] = array('json' => array('json' => $options, 'headers' => array('api-key' => $this->tokenEndpoint)));
        }

        try {
            $responses = $this->requestAsync('POST', "/request", $promises);

            foreach ($responses as $skumkt_product => $content_response) {
                if (!property_exists($content_response, 'data')) {
                    throw new InvalidArgumentException("Resposta fora do padrão." . json_encode($content_response));
                }
                $content_response = $content_response->data;// Produto indisponível.
                if (!$content_response->disponibilidade) {
                    $this->setUnavailableProduct($arrSkuProductId, $productsToSku[$skumkt_product]);
                    $messageError = $content_response->error->message ?? json_encode($content_response);
                    throw new InvalidArgumentException("Produto ({$productsToSku[$skumkt_product]['codigo']}) não disponível - RESPONSE=$messageError");
                }

                // Produto sem preço.
                if (!isset($content_response->valor)) {
                    $this->cleanStock($arrSkuProductId, $productsToSku[$skumkt_product]);
                    $messageError = $content_response->error->message ?? json_encode($content_response);
                    throw new InvalidArgumentException("Produto ({$productsToSku[$skumkt_product]['codigo']}) não disponível - RESPONSE=$messageError");
                }

                // Produto com preço diferente.
                if (roundDecimal($content_response->valor) != roundDecimal($arrSkuProductId[$productsToSku[$skumkt_product]['codigo']]['price'])) {
                    $this->updatePrice($arrSkuProductId, $productsToSku[$skumkt_product], $content_response);
                    throw new InvalidArgumentException("Produto ({$productsToSku[$skumkt_product]['codigo']}) está com o preço diferente do pretendido. SellerCenter={$arrSkuProductId[$productsToSku[$skumkt_product]['codigo']]['price']} | Via={$content_response->valor}");
                }
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        try {
            $options = $this->getBodyRequestInternal('POST', '/pedidos/carrinho', array('json' => $body));
            $response = $this->request('POST', "/request", array('json' => $options, 'headers' => array('api-key' => $this->tokenEndpoint)));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $services = $contentOrder->data;

        if (!isset($services->produtos) || !count($services->produtos)) {
            $messageError = $contentOrder->error->message ?? json_encode($contentOrder);
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Via Varejo - Sem transportadora disponível.\nRESPONSE=$messageError");
        }

        foreach ($services->produtos as $service) {
            if ($service->previsaoEntrega === 'Imediato') {
                $service->previsaoEntrega = 1;
            } else {
                if (likeText("%dia%", strtolower($service->previsaoEntrega))) {
                    $service->previsaoEntrega = onlyNumbers($service->previsaoEntrega);

                    if ($service->previsaoEntrega > 100) {
                        throw new InvalidArgumentException("Não foi possível ler a previsão de entrega.\n" . json_encode($service));
                    }
                } else {
                    try {
                        $datetime1 = new DateTime($service->previsaoEntrega);
                        $datetime2 = dateNow(TIMEZONE_DEFAULT);
                        $interval = $datetime1->diff($datetime2);
                        $service->previsaoEntrega = $interval->days;
                    } catch (Throwable $exception) {
                        throw new InvalidArgumentException("Não foi possível ler a previsão de entrega.\n" . json_encode($service));
                    }
                }
            }

            $servicesSla[] = array(
                'prd_id'    => $arrSkuProductId[$service->idSku]['prd_id'],
                'skumkt'    => $arrSkuProductId[$service->idSku]['skumkt'],
                'quote_id'  => NULL,
                'method_id' => NULL,
                'value'     => $service->valorTotalFrete,
                'deadline'  => (int)$service->previsaoEntrega + $crossDocking,
                'method'    => 'Via Varejo',
                'provider'  => 'Via Varejo',
                'quote_json'=> Utils::jsonEncode($contentOrder)
            );
        }

        return $servicesSla;
    }

    private function getBodyRequestInternal($method, $uri, $options): array
    {
        $options['headers']['Content-Type'] = "application/json";

        if (array_key_exists('headers', $this->authRequest)) {
            $options['headers'] = array_merge_recursive($options['headers'], $this->authRequest['headers']);
        }

        if (array_key_exists('json', $this->authRequest)) {
            if (!array_key_exists('json', $options)) {
                $options['json'] = array();
            }
            $options['json'] = array_merge_recursive($options['json'], $this->authRequest['json']);
        }

        if (array_key_exists('query', $this->authRequest)) {
            if (!array_key_exists('query', $options)) {
                $options['query'] = array();
            }
            $options['query'] = array_merge_recursive($options['query'], $this->authRequest['query']);
        }

        return array(
            'uri'       => $this->finalEndpoint.$uri,
            'method'    => $method,
            'options'   => $options
        );
    }

    private function updatePrice(array $arrSkuProductId, array $product, object $contentAvailable)
    {
        if (!empty($arrSkuProductId[$product['codigo']]['prd_id'])) {
            $dataQuote = $arrSkuProductId[$product['codigo']];
            $price      = $contentAvailable->valor;
            $list_price = $contentAvailable->valorDe;

            // é variação
            if ($dataQuote['variant'] !== null) {
                $VariationData = $this->model_products->getVariants(
                    $arrSkuProductId[$product['codigo']]['prd_id'],
                    $dataQuote['variant']
                );
                if ($VariationData['price'] != roundDecimal($price)) {
                    $this->model_products->updateVar(
                        array(
                            'price'      => roundDecimal($price),
                            'list_price' => roundDecimal($list_price)
                        ),
                        $arrSkuProductId[$product['codigo']]['prd_id'],
                        $dataQuote['variant']
                    );
                }
            } else {
                $productData = $this->model_products->getProductData(0, $arrSkuProductId[$product['codigo']]['prd_id']);
                if ($productData['price'] != roundDecimal($price)) {
                    $this->model_products->update(
                        array(
                            'price'      => roundDecimal($price),
                            'list_price' => roundDecimal($list_price)
                        ),
                        $arrSkuProductId[$product['codigo']]['prd_id']
                    );
                }
            }
        }
    }

    private function cleanStock(array $arrSkuProductId, array $product)
    {
        if (!empty($arrSkuProductId[$product['codigo']]['prd_id'])) {
            $dataQuote = $arrSkuProductId[$product['codigo']];

            // é variação
            if ($dataQuote['variant'] !== null) {
                $VariationData = $this->model_products->getVariants(
                    $arrSkuProductId[$product['codigo']]['prd_id'],
                    $dataQuote['variant']
                );
                if ($VariationData['qty'] != 0) {
                    $this->model_products->updateVar(
                        array('qty' => 0),
                        $arrSkuProductId[$product['codigo']]['prd_id'],
                        $dataQuote['variant']
                    );
                }
            } else {
                $productData = $this->model_products->getProductData(0, $arrSkuProductId[$product['codigo']]['prd_id']);
                if ($productData['qty'] != 0) {
                    $this->model_products->update(
                        array('qty' => 0),
                        $arrSkuProductId[$product['codigo']]['prd_id']
                    );
                }
            }
        }
    }

    private function setUnavailableProduct(array $arrSkuProductId, array $product)
    {
        if (!empty($arrSkuProductId[$product['codigo']]['prd_id'])) {
            $dataQuote = $arrSkuProductId[$product['codigo']];

            // é variação
            if ($dataQuote['variant'] !== null) {
                $this->model_products->updateVar(
                    array('status' => $this->model_products::INACTIVE_PRODUCT),
                    $arrSkuProductId[$product['codigo']]['prd_id'],
                    $dataQuote['variant']
                );
            }

            $this->model_products->update(
                array('status' => $this->model_products::INACTIVE_PRODUCT),
                $arrSkuProductId[$product['codigo']]['prd_id']
            );
        }
    }

}