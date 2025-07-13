<?php

use GuzzleHttp\Utils;

/**
 * DESATIVADA
 */
class Dressandgo extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
        $this->setEndpoint($this->credentials['endpoint'] ?? $this->credentials['token']);
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $this->authRequest = array();
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse() {}

    /**
     * Cotação.
     *
     * @warning Não usa no novo módulo de frete.
     *
     * @param   array   $dataQuote      Dados para realizar a cotação.
     * @param   bool    $moduloFrete    Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        return array(
            'success'   => true,
            'data'      => array(
                'services'  => array()
            )
        );

        $items = array();

        foreach ($dataQuote['items'] as $sku) {
            $items[] = array(
                'id'        => $sku['skuseller'],
                'quantity'  => $sku['quantidade'],
                'seller'    => '1'
            );
        }

        $option = array(
            'query' => array(
                'purchaseContext' => Utils::jsonEncode(
                    array(
                        'items'             => $items,
                        'marketingData'     => null,
                        'postalCode'        => $dataQuote['zipcodeRecipient'],
                        'country'           => 'BRA',
                        'selectedSla'       => null,
                        'clientProfileData' => null,
                        'geoCoordinates'    => array()
                    )
                ),
                'sc' => 1
            )
        );

        try {
            $response = $this->request('GET', '', $option);
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
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Dress And Go\n" . Utils::jsonEncode($contentOrder));
        }

        $arrServices = array();
        foreach ($contentOrder->logisticsInfo as $services) {
            foreach ($services->slas as $service) {

                $valueDelivery      = (float)substr_replace($service->price, '.', -2, 0);
                $deadlineDelivery   = (int)filter_var($service->shippingEstimate, FILTER_SANITIZE_NUMBER_INT);

                if (array_key_exists($service->id, $arrServices)) {
                    $arrServices[$service->id]['value'] += $valueDelivery;
                    if ($deadlineDelivery > $arrServices[$service->id]['deadline']) {
                        $arrServices[$service->id]['deadline'] = $deadlineDelivery;
                    }

                    continue;
                }

                $arrServices[$service->id] = array(
                    'quote_id'  => NULL,
                    'method_id' => NULL,
                    'value'     => $valueDelivery,
                    'deadline'  => $deadlineDelivery,
                    'method'    => $service->id,
                    'provider'  => $service->name
                );
            }
        }

        $services = array();
        foreach ($arrServices as $service) {
            $services[] = array(
                'quote_id'  => $service['quote_id'],
                'method_id' => $service['method_id'],
                'value'     => (float)$service['value'],
                'deadline'  => (int)$service['deadline'],
                'method'    => $service['method'],
                'provider'  => $service['provider']
            );
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }
}