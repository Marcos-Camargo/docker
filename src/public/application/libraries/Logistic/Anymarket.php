<?php

use GuzzleHttp\Utils;

class Anymarket extends Logistic
{
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

        $queryAppId = $this->dbReadonly->where('name', 'app_id_anymarket')->get('settings')->row_object();
        $queryUrl = $this->dbReadonly->where('name', 'url_anymarket')->get('settings')->row_object();

        $appId = $queryAppId->value;
        $urlRequest = $queryUrl->value;

        $auth['headers']['token'] = $this->credentials['token_anymarket'];
        $auth['headers']['appId'] = $appId;

        if (substr($urlRequest, -1) !== '/') {
            $urlRequest .= '/';
        }

        $this->setEndpoint($urlRequest);

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
        $arrQuote = array(
            'products'              => array(),
            "zipCode"               => $dataQuote['zipcodeRecipient'],
            "additionalPercentual"  => 0
        );

        $services        = array();
        $arrSkuProductId = array();
        $promises        = array();

        foreach ($dataQuote['items'] as $sku) {
            $dataProduct = array(
                "skuId"      => $sku['skuseller'],
                "amount"     => $sku['quantidade'],
                "dimensions" => [
                    'length'    => $sku['comprimento'] * 100,
                    'width'     => $sku['largura'] * 100,
                    'height'    => $sku['altura'] * 100,
                    'weight'    => $sku['peso']
                ]
            );

            $arrSkuProductId[] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['products'][] = $dataProduct;
        }

        try {
            $services = $this->getQuoteUnit($arrQuote, $arrSkuProductId, $dataQuote['crossDocking']);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }

    /**
     * @param   array   $body               Corpo da requisição.
     * @param   array   $arrSkuProductId    Código de SKU e PRD_ID para gerar o retorno.
     * @param   int     $crossDocking       Dias de cross docking.
     * @return  array
     * @throws  InvalidArgumentException
     */
    private function getQuoteUnit(array $body, array $arrSkuProductId, int $crossDocking): array
    {
        try {
            $response = $this->request('POST', "/freight", array('json' => $body));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $services = array();

        if (isset($contentOrder->defaultFreight)) {
            $service = $contentOrder->defaultFreight;
            $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                'quote_id'  => NULL,
                'method_id' => NULL,
                'value'     => $service->price,
                'deadline'  => $service->deliveryTime + $crossDocking,
                'method'    => $service->serviceName,
                'provider'  => $service->carrierName
            )));
        } else {
            if (!empty($contentOrder->quotes)) {
                foreach ($contentOrder->quotes as $service) {
                    $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                        'quote_id'  => NULL,
                        'method_id' => NULL,
                        'value'     => $service->price,
                        'deadline'  => $service->deliveryTime + $crossDocking,
                        'method'    => $service->serviceName,
                        'provider'  => $service->carrierName
                    )));
                }
            }
        }

        return $services;
    }

    /**
     * @param   object   $content_response  Dados da requisição.
     * @param   array    $skumkt_product    Dados skumkt e product_id.
     * @param   int|null $crossDocking      Tempo de crossdocking do produto.
     * @return  array
     */
    private function getQuoteUnitAsync(object $content_response, array $skumkt_product, ?int $crossDocking): array
    {
        $services = array();

        if (isset($content_response->defaultFreight)) {
            $service = $content_response->defaultFreight;
            $services[] = array(
                'prd_id'    => $skumkt_product[1],
                'skumkt'    => $skumkt_product[0],
                'quote_id'  => NULL,
                'method_id' => NULL,
                'value'     => $service->price,
                'deadline'  => $service->deliveryTime + $crossDocking,
                'method'    => $service->serviceName,
                'provider'  => $service->carrierName,
                'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
            );
        } else {
            if (!empty($content_response->quotes) && count($content_response->quotes)) {
                foreach ($content_response->quotes as $service) {
                    $services[] = array(
                        'prd_id'    => $skumkt_product[1],
                        'skumkt'    => $skumkt_product[0],
                        'quote_id'  => NULL,
                        'method_id' => NULL,
                        'value'     => $service->price,
                        'deadline'  => $service->deliveryTime + $crossDocking,
                        'method'    => $service->serviceName,
                        'provider'  => $service->carrierName,
                        'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                    );
                }
            }
        }

        return $services;
    }
}