<?php

use GuzzleHttp\Utils;

class Tms_infracommerce extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
        if (empty($this->credentials['endpoint'])) {
            throw new InvalidArgumentException("Endpoint não configurado");
        }
        $this->setEndpoint($this->credentials['endpoint']);
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        if (empty($this->credentials['api_key'])) {
            throw new InvalidArgumentException("Token não configurado");
        }
        if (empty($this->credentials['platform'])) {
            throw new InvalidArgumentException("Plataforma não configurado");
        }
        $auth = array();
        $auth['headers']['api-key'] = $this->credentials['api_key'];
        $auth['headers']['platform'] = $this->credentials['platform'];

        $this->authRequest = $auth;
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse(){}

    /**
     * Cotação.
     *
     * @param array $dataQuote Dados para realizar a cotação.
     * @param bool $moduloFrete Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        $arrSkuProductId = array();
        $arrQuote = array(
            "origin_zip_code" => $dataQuote['zipcodeSender'],
            "destination_zip_code" => $dataQuote['zipcodeRecipient'],
            "location" => [
                "document" => $dataQuote['dataInternal'][$dataQuote['items'][0]['sku']]['CNPJ']
            ],
            "products" => array()
        );

        foreach ($dataQuote['items'] as $sku) {
            $dataProduct = array(
                "weight"        => $sku['peso'],
                "cost_of_goods" => $sku['valor'] / $sku['quantidade'],
                "width"         => $sku['largura'] * 100,
                "height"        => $sku['altura'] * 100,
                "length"        => $sku['comprimento'] * 100,
                "quantity"      => $sku['quantidade'],
                "sku_id"        => $sku['skuseller'],
            );

            $arrSkuProductId[] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['products'][] = $dataProduct;
        }

        try {
            $services = $this->getQuoteUnit($arrQuote, $arrSkuProductId);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'success' => true,
            'data' => array(
                'services' => $services
            )
        );
    }

    /**
     * @param array $body Corpo da requisição.
     * @param array $arrSkuProductId Código de SKU e PRD_ID para gerar o retorno.
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

        // não encontrou transportadora
        if (empty($contentOrder->content->delivery_options)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Intelipost\n" . Utils::jsonEncode($contentOrder));
        }

        $services = array();

        foreach ($contentOrder->content->delivery_options as $service) {
            $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                'quote_id' => sprintf('%.0f', $contentOrder->content->id),
                'method_id' => $service->delivery_method_id,
                'value' => $service->final_shipping_cost,
                'deadline' => $service->delivery_estimate_business_days,
                'method' => $service->delivery_method_type,
                'provider' => $service->logistic_provider_name,
                'token_oferta' => sprintf('%.0f', $contentOrder->content->id),
                'provider_cnpj' => null,
                'custo_frete' => $service->provider_shipping_cost ?? null,
                'quote_json' => Utils::jsonEncode($contentOrder)
            )));
        }

        return $services;
    }

    /**
     * Contrata o frete pela Intelipost
     *
     * @param array $order Dados do pedido
     */
    public function hireFreight(array $order)
    {
        $this->load->model('model_orders');
        $this->model_orders->updatePaidStatus($order['id'], 40);
        echo "pedido {$order['id']} usa logística TMS Infracommerce, vai pro status 40\n";
    }

    /**
     * Consultar as ocorrências do rastreio para Intelipost
     *
     * @param array $order Dados do pedido.
     * @return  void            Retorna o status do rastreio.
     */
    public function tracking(array $order): void
    {
        echo "pedido {$order['id']} usa logística da integradora: TMS Infracommerce, deve mudar para status de aguardando dado externo\n";

        if ($order['paid_status'] == 53 || $order['paid_status'] == 4) {
            $this->model_orders->updatePaidStatus($order['id'], 43);
        } elseif ($order['paid_status'] == 5) {
            $this->model_orders->updatePaidStatus($order['id'], 45);
        }
    }
}