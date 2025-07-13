<?php

use GuzzleHttp\Utils;

class Hub2b extends Logistic
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
        $this->setEndpoint("https://freight.hub2b.com.br/api/freight/simulation/{$this->credentials['idTenant']}/v2");

        $auth = array();
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
            "marketplace"        => $this->sellerCenter,
            "destinationZipCode" => $dataQuote['zipcodeRecipient'],
            "sourceZipCode"      => $dataQuote['zipcodeSender'],
            'products'           => array()
        );

        if (ENVIRONMENT === 'development') {
            $arrQuote['marketplace'] = 'mercadolivre';
        }

        $arrSkuProductId = array();

        foreach ($dataQuote['items'] as $sku) {
            $arrSkuProductId[$sku['skuseller']] = array(
                'skumkt'    => $sku['sku'],
                'prd_id'    => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['products'][] = array(
                "sku"       => $sku['skuseller'],
                "quantity"  => $sku['quantidade']
            );
        }

        try {
            $services = $this->getQuoteUnit($arrQuote, $arrSkuProductId);
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
     * @return  array
     * @throws  InvalidArgumentException
     */
    private function getQuoteUnit(array $body, array $arrSkuProductId): array
    {
        try {
            $response = $this->request('POST', "", array('json' => $body));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!is_array($contentOrder)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Hub2b\n" . Utils::jsonEncode($contentOrder));
        }

        $servicesSla = array();
        foreach ($contentOrder as $product) {
            // Não encontrou serviços
            if (!isset($product->options) || !count($product->options)) {
                continue;
            }

            foreach ($product->options as $service){
                $servicesSla[] = array(
                    'prd_id'    => $arrSkuProductId[$product->sku]['prd_id'] ?? null,
                    'skumkt'    => $arrSkuProductId[$product->sku]['skumkt'] ?? null,
                    'quote_id'  => null,
                    'method_id' => null,
                    'value'     => $service->cost,
                    'deadline'  => $service->estimatedDelivreyDays,
                    'method'    => $service->service,
                    'provider'  => $service->provider,
                    'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                );
            }
        }

        return $servicesSla;
    }
}