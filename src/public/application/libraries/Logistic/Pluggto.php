<?php

use GuzzleHttp\Utils;

class Pluggto extends Logistic
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
        $query_marketplace_pluggto = $this->dbReadonly->where('name', 'marketplace_pluggto')->get('settings')->row_array();
        $marketplace_pluggto = $query_marketplace_pluggto && $query_marketplace_pluggto['status'] == 1 ? $query_marketplace_pluggto['value'] : $this->sellerCenter;

        if (!$marketplace_pluggto) {
            if (ENVIRONMENT === 'development' && $this->sellerCenter === 'conectala') {
                $marketplace_pluggto = 'conectaladev';
            }

            if ($this->sellerCenter === 'vertem') {
                $marketplace_pluggto = 'shophub';
            }

            if ($this->sellerCenter === 'casavideo') {
                $marketplace_pluggto = 'casaevideo';
            }

            if ($this->sellerCenter === 'raiadrogasil') {
                $marketplace_pluggto = 'drogaraia';
            }
        }

        $user_id = 0;

        $arrQuote = array(
            "destination_postalcode" => $dataQuote['zipcodeRecipient'],
            "products" => array()
        );
        $arrSkuProductId = array();

        foreach ($dataQuote['items'] as $sku) {
            $split_sku = explode("-" , $sku['skuseller']);

            // Não é um produto PluggTo
            if (count($split_sku) < 2) {
                continue;
            }

            $user_id = $split_sku[0];

            if ($this->endpoint === null) {
                $this->setEndpoint("https://marketplace.plugg.to/freight/$marketplace_pluggto/$user_id");
            }

            $original_sku = substr($sku['skuseller'], (strLen($user_id) + 1));

            $dataProduct = array(
                "unit_price"    => $sku['valor'] / $sku['quantidade'],
                "sku"           => $sku['skuseller'],
                "original_sku"  => $original_sku,
                "quantity"      => $sku['quantidade'],
                "weight"        => $sku['peso'],
                "height"        => $sku['altura'] * 100,
                "length"        => $sku['comprimento'] * 100,
                "width"         => $sku['largura'] * 100,
            );

            $arrSkuProductId[] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['products'][] = $dataProduct;
        }

        $arrQuote['user_id'] = $user_id;

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
            $response = $this->request('POST', '', array('json' => $body));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // não encontrou transportadora
        if (property_exists($contentOrder, 'error') || !property_exists($contentOrder, 'results')) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Plugg.To\n" . Utils::jsonEncode($contentOrder));
        }

        $services = array();
        foreach ($contentOrder->results as $arrService) {
            if (is_array($arrService)) {
                foreach($arrService as $service) {
                    if (!isset($service->price)) {
                        continue;
                    }
                    $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                        'quote_id'  => null,
                        'method_id' => null,
                        'value'     => $service->price,
                        'deadline'  => $service->estimate,
                        'method'    => $service->method,
                        'provider'  => $service->company,
                        'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                    )));
                }
            } elseif (is_string($arrService)) {
                throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Plugg.To\n$arrService");
            }
        }

        return $this->setServicesDuplicated($services);
    }

    /**
     * @param   object   $content_response  Dados da requisição.
     * @param   array    $skumkt_product    Dados skumkt e product_id.
     * @return  array
     */
    private function getQuoteUnitAsync(object $content_response, array $skumkt_product): array
    {
        // não encontrou transportadora
        if (property_exists($content_response, 'error') || !property_exists($content_response, 'results')) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Plugg.To\n" . Utils::jsonEncode($content_response));
        }

        $services = array();
        foreach ($content_response->results as $arrService) {
            if (is_array($arrService)) {
                foreach($arrService as $service) {
                    if (!isset($service->price)) {
                        continue;
                    }

                    $services[] = array(
                        'prd_id'    => $skumkt_product[1],
                        'skumkt'    => $skumkt_product[0],
                        'quote_id'  => null,
                        'method_id' => null,
                        'value'     => $service->price,
                        'deadline'  => $service->estimate,
                        'method'    => $service->method,
                        'provider'  => $service->company
                    );
                }
            }
            /*elseif (is_string($arrService)) {
                throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Plugg.To\n$arrService");
            }*/
        }

        return $this->setServicesDuplicated($services);
    }
}