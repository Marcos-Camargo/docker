<?php

use GuzzleHttp\Utils;

class Precode extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
        $this->setEndpoint($this->credentials['endpoint'] ?? $this->credentials['token'] ?? '');
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $this->load->model('model_integration_logistic_api_parameters');
        $auth = array();
        foreach ($this->model_integration_logistic_api_parameters->getDataByIntegrationId($this->integration_logistic_id) as $parameters) {
            $auth[$parameters['type']][$parameters['key']] = $parameters['value'];
        }

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
    public function getQuote(array $dataQuote, bool $moduloFrete = false, $enable_multiseller_operation = false): array
    {
        if (empty($this->endpoint)) {
            throw new InvalidArgumentException("Endpoint not found");
        }

        $is_sellercenter = likeTextNew("%conectala.com.br%", $this->endpoint);
        if (!$is_sellercenter) {
            $enable_multiseller_operation = false;
        }

        $arrSkuProductId = array();
        $arrQuote = array(
            'originZip' => $dataQuote['zipcodeSender'],
            'destinationZip' => $dataQuote['zipcodeRecipient'],
            'volumes'        => array()
        );

        $promises = [];
        $seller_volumes = [];
        foreach ($dataQuote['items'] as $item) {
            $dataProduct = array(
                'sku'       => $item['skuseller'],
                'quantity'  => $item['quantidade'],
                "weight" => $item['peso'],
                "height" => $item['altura'] * 100,
                "length" => $item['comprimento'] * 100,
                "width" => $item['largura'] * 100,
            );

            $arrSkuProductId[] = array(
                'skumkt'    => $item['sku'],
                'prd_id'    => $dataQuote['dataInternal'][$item['sku']]['prd_id'],
                'skuseller' => $item['skuseller']
            );

            if (!$enable_multiseller_operation) {
                $arrQuote['volumes'][] = $dataProduct;
            } else {
                $exp_sku = explode('S', $item['skuseller']);
                $seller = str_replace($exp_sku[0], '', $item['skuseller']);

                $seller_exp = explode('-', $seller);
                if (count($seller_exp) > 1) {
                    $seller = str_replace("-$seller_exp[1]", '', $seller);
                }

                if (!array_key_exists($seller, $seller_volumes)) {
                    $seller_volumes[$seller] = array();
                }

                $seller_volumes[$seller][] = $dataProduct;
            }
        }

        if (!$enable_multiseller_operation) {
            try {
                $new_services = $this->getQuoteUnit($arrQuote, $arrSkuProductId, $dataQuote['crossDocking']);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        } else {
            if (count($seller_volumes) > 1) {
                $this->has_multiseller = true;
            }

            foreach ($seller_volumes as $seller_key => $volumes) {
                $promises[$seller_key] = array('json' => array_merge($arrQuote, ['volumes' => $volumes]));
            }

            $responses = $this->requestAsync('POST', "", $promises);

            $crossDocking = $dataQuote['crossDocking'];
            $is_sellercenter = strpos($this->endpoint, "conectala.com.br") != false;
            if ($is_sellercenter) {
                $crossDocking = 0;
            }

            $services = array();
            foreach ($responses as $key => $response) {
                $services[$key] = $this->formatResponseApi($response, $arrSkuProductId, $crossDocking);
            }

            $new_services = array();
            foreach ($services as $service) {
                $new_services = array_merge($new_services, $service);
            }
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $new_services
            )
        );
    }

    private function formatResponseApi($contentOrder, $arrSkuProductId, $crossDocking)
    {
        $services = array();
        if (
            empty($contentOrder) ||
            (
                (!property_exists($contentOrder, 'shippingQuotes') || count($contentOrder->shippingQuotes) == 0) &&
                (!property_exists($contentOrder, 'ShippingQuotes') || count($contentOrder->ShippingQuotes) == 0)
            )
        ) {
            // Respondeu, mas não tem entrega. Não é erro.
            if (
                is_object($contentOrder) &&
                (
                    (property_exists($contentOrder, 'shippingQuotes') && count($contentOrder->shippingQuotes) == 0) ||
                    (property_exists($contentOrder, 'ShippingQuotes') && count($contentOrder->ShippingQuotes) == 0)
                )
            ) {
                return [];
            }

            return [];
//            throw new InvalidArgumentException('Ocorreu um problema para realizar a cotação - Sem transportadora disponível.');
        }

        $servicesQuote      = property_exists($contentOrder, 'shippingQuotes') ? $contentOrder->shippingQuotes : $contentOrder->ShippingQuotes;
        $existSkuInService  = null;

        foreach ($servicesQuote as $service) {

            $shippingMethodName = property_exists($service, 'shippingMethodName') ? $service->shippingMethodName : $service->ShippingMethodName;
            $shippingCost = property_exists($service, 'shippingCost') ? $service->shippingCost : $service->ShippingCost;
            $shippingMethodDisplayName = isset($service->shippingMethodDisplayName) ? $service->shippingMethodDisplayName :
                (isset($service->ShippingMethodDisplayName) ? $service->ShippingMethodDisplayName : $shippingMethodName);
            // Se tem "|" e ":" é precode. "100|1|1|Rea:150.19|2089|Cob:157.7|ID:2089|BD|V2|P:16"
            /*if (strpos($shippingMethodDisplayName, '|') === FALSE && strpos($shippingMethodDisplayName, ':') === FALSE) {
                $shippingMethodDisplayName = "$shippingMethodName - $shippingMethodDisplayName";
            } else {
                $shippingMethodDisplayName = $shippingMethodName;
            }*/

            if ($shippingMethodName == "sku nao encontrado") {
                return [];
//                throw new InvalidArgumentException('Ocorreu um problema para realizar a cotação - SKU não encontrado.');
            }

            if ($shippingMethodName == "Não TEM") {
                return [];
//                throw new InvalidArgumentException('Ocorreu um problema para realizar a cotação - SKU sem estoque.');
            }

            if ($shippingMethodName == "Verificar") {
                return [];
//                throw new InvalidArgumentException('Não tem previsão do tempo e preço para realizar a entrega.');
            }

            if ($shippingMethodName == "Indisponivel") {
                return [];
//                throw new InvalidArgumentException('Não foram encontradas entregas disponíveis.');
            }

            $deadline = property_exists($service, 'deliveryTime') ? ($service->deliveryTime->total ?? $service->deliveryTime) : ($service->DeliveryTime->Total ?? $service->DeliveryTime);

            if (property_exists($service, 'sku')) {
                if (is_null($existSkuInService)) {
                    $existSkuInService = true;
                }

                if ($existSkuInService === false) {
                    throw new InvalidArgumentException("Os serviços retornados, vieram com estruturas diferentes (service->sku).\n".json_encode($servicesQuote, JSON_UNESCAPED_UNICODE));
                }

                $skuProductId = getArrayByValueIn($arrSkuProductId, $service->sku, 'skuseller');

                $services[] = array(
                    'prd_id'    => $skuProductId['prd_id'] ?? null,
                    'skumkt'    => $skuProductId['skumkt'] ?? null,
                    'quote_id'  => null,
                    'method_id' => null,
                    'value'     => $shippingCost,
                    'deadline'  => $deadline + $crossDocking,
                    'method'    => $shippingMethodDisplayName,
                    'provider'  => $shippingMethodName,
                    'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                );
            } else {
                if (is_null($existSkuInService)) {
                    $existSkuInService = false;
                }

                if ($existSkuInService === true) {
                    throw new InvalidArgumentException("Os serviços retornados, vieram com estruturas diferentes (service->sku).\n".json_encode($servicesQuote, JSON_UNESCAPED_UNICODE));
                }

                $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                    'quote_id'  => null,
                    'method_id' => null,
                    'value'     => $shippingCost,
                    'deadline'  => $deadline + $crossDocking,
                    'method'    => $shippingMethodDisplayName,
                    'provider'  => $shippingMethodName,
                    'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                )));
            }
        }

        return $services;
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
        $is_sellercenter = strpos($this->endpoint, "conectala.com.br") != false;
        if($is_sellercenter) {
            $crossDocking = 0;
        }

        $services = array();
        try {
            $response = $this->request('POST', "", array('json' => $body));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        //get_instance()->log_data('api', __CLASS__.'/'.__FUNCTION__, json_encode([$body, $arrSkuProductId, $this->credentials, $contentOrder]));

        return $this->formatResponseApi($contentOrder, $arrSkuProductId, $crossDocking, $services);
    }

    /**
     * @param   object   $content_response  Dados da requisição.
     * @param   array    $skumkt_product    Dados skumkt e product_id.
     * @param   int|null $crossDocking      Tempo de crossdocking do produto.
     * @return  array
     */
    private function getQuoteUnitAsync(object $content_response, array $skumkt_product, ?int $crossDocking): array
    {
        if (
            empty($content_response) ||
            (
                (!property_exists($content_response, 'shippingQuotes') || count($content_response->shippingQuotes) == 0) &&
                (!property_exists($content_response, 'ShippingQuotes') || count($content_response->ShippingQuotes) == 0)
            )
        ) {
            // Respondeu, mas não tem entrega. Não é erro.
            if (
                is_object($content_response) &&
                (
                    (property_exists($content_response, 'shippingQuotes') && count($content_response->shippingQuotes) == 0) ||
                    (property_exists($content_response, 'ShippingQuotes') && count($content_response->ShippingQuotes) == 0)
                )
            ) {
                return [];
            }

            throw new InvalidArgumentException('Ocorreu um problema para realizar a cotação - Sem transportadora disponível.');
        }

        $services = array();

        $servicesQuote = property_exists($content_response, 'shippingQuotes') ? $content_response->shippingQuotes : $content_response->ShippingQuotes;

        foreach ($servicesQuote as $service) {
            $shippingMethodName = property_exists($service, 'shippingMethodName') ? $service->shippingMethodName : $service->ShippingMethodName;
            $shippingCost = property_exists($service, 'shippingCost') ? $service->shippingCost : $service->ShippingCost;
            $shippingMethodDisplayName = $service->shippingMethodDisplayName ?? ($service->ShippingMethodDisplayName ?? $shippingMethodName);

            if ($shippingMethodName == "sku nao encontrado") {
                throw new InvalidArgumentException('Ocorreu um problema para realizar a cotação - SKU não encontrado.');
            }

            if ($shippingMethodName == "Não TEM") {
                throw new InvalidArgumentException('Ocorreu um problema para realizar a cotação - SKU sem estoque.');
            }

            if ($shippingMethodName == "Verificar") {
                throw new InvalidArgumentException('Não tem previsão do tempo e preço para realizar a entrega.');
            }

            if ($shippingMethodName == "Indisponivel") {
                throw new InvalidArgumentException('Não foram encontradas entregas disponíveis.');
            }

            $deadline = property_exists($service, 'deliveryTime') ? ($service->deliveryTime->total ?? $service->deliveryTime) : ($service->DeliveryTime->Total ?? $service->DeliveryTime);

            $services[] = array(
                'prd_id'    => $skumkt_product[1],
                'skumkt'    => $skumkt_product[0],
                'quote_id'  => null,
                'method_id' => null,
                'value'     => $shippingCost,
                'deadline'  => $deadline + $crossDocking,
                'method'    => $shippingMethodDisplayName,
                'provider'  => $shippingMethodName
            );
        }

        return $services;
    }
}