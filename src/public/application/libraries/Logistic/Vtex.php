<?php

use GuzzleHttp\Utils;

class Vtex extends Logistic
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

        $auth['headers']['accept'] = "application/vnd.vtex.ds.v10+json";
        $auth['headers']['X-VTEX-API-AppToken'] = $this->credentials['token_vtex'];
        $auth['headers']['X-VTEX-API-AppKey'] = $this->credentials['appkey_vtex'];

        $auth['query']['affiliateId'] = $this->credentials['affiliate_id_vtex'];
        $auth['query']['sc'] = $this->credentials['sales_channel_vtex'];

        $base_url = "{$this->credentials['environment_vtex']}.com.br";
        if (!empty($this->credentials['base_url_external'])) {
            $base_url = $this->credentials['base_url_external'];
        }

        $this->setEndpoint("https://{$this->credentials['account_name_vtex']}.$base_url");

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
            'items'      => array(),
            "postalCode" => $dataQuote['zipcodeRecipient'],
            "country"    => "BRA"
        );

        $arrSkuProductId = array();

        foreach ($dataQuote['items'] as $sku) {
            $dataProduct = array(
                "id"        => $sku['skuseller'],
                "quantity"  => $sku['quantidade'],
                "seller"    => 1
            );

            $arrSkuProductId[$sku['skuseller']] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['items'][] = $dataProduct;
        }

        try {
            $servicesSla = $this->getQuoteUnit($arrQuote, $arrSkuProductId);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'success'   => true,
            'origin'    => 'VTEX',
            'data'      => array(
                'services'  => $servicesSla
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
            $response = $this->request('POST', "/api/fulfillment/pvt/orderforms/simulation", array('json' => $body));
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // não encontrou transportadora
        if (
            !isset($contentOrder->logisticsInfo) ||
            !is_array($contentOrder->logisticsInfo) ||
            !count($contentOrder->logisticsInfo)
        ) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na VTEX\n" . Utils::jsonEncode($contentOrder));
        }

        if (property_exists($contentOrder, 'messages') && is_array($contentOrder->messages)) {
            foreach ($contentOrder->messages as $message) {
                if ($message->code === 'itemMaxQuantityLimitReached') {
                    throw new InvalidArgumentException("$message->text\n" . json_encode($contentOrder, JSON_UNESCAPED_UNICODE));
                }
            }
        }

        $servicesSla = array();
        foreach ($contentOrder->logisticsInfo as $services) {
            $item_id = $contentOrder->items[$services->itemIndex]->id;

            $prd_id = $arrSkuProductId[$item_id]['prd_id'] ?? null;
            $sku_id = $arrSkuProductId[$item_id]['skumkt'] ?? null;

            foreach ($services->slas as $service) {
                // Só retorna SLA se for entrega.
                if ($service->deliveryChannel !== "delivery") {
                    continue;
                }

                $valueDelivery      = moneyVtexToFloat($service->price);
                $deadlineDelivery   = (int)filter_var($service->shippingEstimate, FILTER_SANITIZE_NUMBER_INT);

                $servicesSla[] = array(
                    'prd_id'    => $prd_id,
                    'skumkt'    => $sku_id,
                    'quote_id'  => null,
                    'method_id' => null,
                    'value'     => $valueDelivery,
                    'deadline'  => $deadlineDelivery,
                    'method'    => $service->id,
                    'provider'  => $service->name,
                    'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                );
            }
        }

        return $servicesSla;
    }
}