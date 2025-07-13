<?php

require APPPATH . "libraries/Integration_v2/tray/Resources/Auth.php";
//require APPPATH . "libraries/Integration_v2/tray/Resources/Configuration.php";

use GuzzleHttp\Utils;
use Integration_v2\tray\Resources\Auth;

class Tray extends Logistic
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

        $this->setEndpoint($this->credentials['apiAddress']);

        $auth['query']['access_token'] = Auth::getInstance()->fetchAccessToken($this->store);

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
        $arrSkuProductId = array();
        $arrQuote = array(
            "zipcode" => $dataQuote['zipcodeRecipient']
        );
        $arrQuoteUnit['query'] = $arrQuote;
        $products_id_to_sku = array();

        foreach ($dataQuote['items'] as $keyProduct => $sku) {
            $sku_seller = $sku['skuseller'];
            $sku_seller_variation = null;
            if (!is_null($sku['variant'])) {
                $product_id = $dataQuote['dataInternal'][$sku['sku']]['prd_id'];
                if (!array_key_exists($product_id, $products_id_to_sku)) {
                    $dataProduct = $this->db->select('sku')->get_where('products', array('id' => $product_id))->row_array();
                    if (!$dataProduct) {
                        continue;
                    }
                    $sku_seller = str_replace('P_', '', $dataProduct['sku']);
                    $sku_seller_variation =  $sku['skuseller'];
                    $products_id_to_sku[$product_id] = $sku_seller;
                } else {
                    $sku_seller = $products_id_to_sku[$product_id];
                }

            }

            $dataProduct = array(
                "products[$keyProduct][product_id]" => $sku_seller,
                "products[$keyProduct][quantity]"   => $sku['quantidade'],
                "products[$keyProduct][price]"      => $sku['valor']
            );

            if ($sku_seller_variation !== null) {
                $dataProduct["products[$keyProduct][sku]"] = $sku_seller_variation;
            }

            $arrSkuProductId[] = array(
                'skumkt' => $sku['sku'],
                'prd_id' => $dataQuote['dataInternal'][$sku['sku']]['prd_id']
            );

            $arrQuoteUnit['query'] = array_merge($arrQuoteUnit['query'], $dataProduct);
        }

        try {
            $services = $this->getQuoteUnit($arrQuoteUnit, $arrSkuProductId);
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
            $response = $this->request('GET', '/shippings/cotation', $body);
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // não encontrou transportadora
        if (!isset($contentOrder->Shipping->cotation)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Tray\n" . Utils::jsonEncode($contentOrder));
        }

        $services = array();
        foreach ($contentOrder->Shipping->cotation as $service) {
            if($service->pickup != 0){
                continue;
            }

            $services = array_merge_recursive($services, $this->formatShippingMethod($arrSkuProductId, array(
                'quote_id'  => null,
                'method_id' => null,
                'value'     => $service->value,
                'deadline'  => $service->max_period,
                'method'    => $service->name,
                'provider'  => $service->id,
                'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
            )));
        }

        $new_services = [];
        foreach ($services as $service) {
            foreach ($new_services as $key_new_service => $new_service) {
                if ($new_service['skumkt'] == $service['skumkt'] && $new_service['method'] == $service['method']) {
                    $new_services[$key_new_service]['value'] += $service['value'];
                    if ($new_services[$key_new_service]['deadline'] < $service['deadline']) {
                        $new_services[$key_new_service]['deadline'] = $service['deadline'];
                    }
                    continue 2;
                }
            }

            $new_services[] = $service;
        }

        return $new_services;
    }

    /**
     * @param   object   $content_response  Dados da requisição.
     * @param   array    $skumkt_product    Dados skumkt e product_id.
     * @return  array
     */
    private function getQuoteUnitAsync(object $content_response, array $skumkt_product): array
    {
        // não encontrou transportadora
        if (!isset($content_response->Shipping->cotation)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Tray\n" . Utils::jsonEncode($content_response));
        }

        $services = array();
        foreach ($content_response->Shipping->cotation as $service) {
            $services[] = array(
                'prd_id'    => $skumkt_product[1],
                'skumkt'    => $skumkt_product[0],
                'quote_id'  => null,
                'method_id' => null,
                'value'     => $service->value,
                'deadline'  => $service->max_period,
                'method'    => $service->name,
                'provider'  => $service->id
            );
        }

        $new_services = [];
        foreach ($services as $service) {
            foreach ($new_services as $key_new_service => $new_service) {
                if ($new_service['skumkt'] == $service['skumkt'] && $new_service['method'] == $service['method']) {
                    $new_services[$key_new_service]['value'] += $service['value'];
                    if ($new_services[$key_new_service]['deadline'] < $service['deadline']) {
                        $new_services[$key_new_service]['deadline'] = $service['deadline'];
                    }
                    continue 2;
                }
            }

            $new_services[] = $service;
        }

        return $new_services;
    }
}