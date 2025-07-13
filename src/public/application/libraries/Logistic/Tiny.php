<?php

use GuzzleHttp\Utils;

class Tiny extends Logistic
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

        $this->setEndpoint($this->credentials['endpoint_quote'] ?? '');
        $queryDeveloperId = $this->dbReadonly->where('name', 'developer_id_tiny')->get('settings')->row_array();

        if ($queryDeveloperId && $queryDeveloperId['status'] == 1) {
            $auth['headers']['Developer-Id'] = $queryDeveloperId['value'];
        }

        $auth['headers']['Token'] = $this->credentials['token_tiny'] ?? '';

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
            "cep_destino"   => $dataQuote['zipcodeRecipient'],
            "itens"         => array(),
            "opcoes"        => array(
                "cotar_agrupado"             => false,
                "considerar_dias_preparacao" => true
            )
        );

        $arrSkuProductId = array();

        foreach ($dataQuote['items'] as $sku) {
            $dataProduct = array(
                "sku"           => $sku['skuseller'],
                "quantidade"    => $sku['quantidade']
            );

            $arrSkuProductId[$sku['skuseller']] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuote['itens'][] = $dataProduct;
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
            $response = $this->request('POST', "", array('json' => $body));
            $response_text = $response->getBody()->getContents();

            // API bloqueada por limite de requisição.
            if (likeText('%api bloqueada%', strtolower($response_text))) {
                throw new InvalidArgumentException($response_text);
            }

            $contentOrder = Utils::jsonDecode($response_text);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // não encontrou transportadora
        if (
            !isset($contentOrder->cotacoes) ||
            !is_array($contentOrder->cotacoes) ||
            !count($contentOrder->cotacoes)
        ) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Tiny\n" . Utils::jsonEncode($contentOrder));
        }

        $servicesSla = array();
        foreach ($contentOrder->cotacoes as $services) {
            $prd_id = $arrSkuProductId[$services->sku]['prd_id'] ?? null;
            $sku_id = $arrSkuProductId[$services->sku]['skumkt'] ?? null;

            foreach ($services->opcoes as $service) {
                $servicesSla[] = array(
                    'prd_id'    => $prd_id,
                    'skumkt'    => $sku_id,
                    'quote_id'  => null,
                    'method_id' => null,
                    'value'     => $service->preco,
                    'deadline'  => $service->prazo,
                    'method'    => $service->nome_forma_frete,
                    'provider'  => $service->nome_forma_envio,
                    'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                );
            }
        }

        return $servicesSla;
    }
}