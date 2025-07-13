<?php

namespace Integration\NEW_INTEGRATION;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Integration\Integration_v2\Order_v2;

class ToolsOrder
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    /**
     * @var int Código do pedido no seller center.
     */
    public $orderId;

    /**
     * @var string Código do pedido na integradora.
     */
    public $orderIdIntegration;

    /**
     * Instantiate a new Tools instance.
     *
     * @param Order_v2 $order_v2
     */
    public function __construct(Order_v2 $order_v2)
    {
        $this->order_v2 = $order_v2;
    }

    /**
     * Envia o pedido para a integradora.
     *
     * @param   object  $order  Dados do pedido para formatação.
     * @return  array           Código do pedido gerado pela integradora e dados da requisição para log.
     */
    public function sendOrderIntegration(object $order): array
    {
        $urlOrder = "";
        $queryOrder = array();

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());

        return array(
            'id'        => $contentOrder->id,
            'request'   => "$urlOrder\n" . Utils::jsonEncode($queryOrder, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int      $order  Código do pedido na integradora.
     * @return  array|object            Dados do pedido na integradora.
     */
    public function getOrderIntegration($order)
    {
        $urlOrder = "pedido/$order";

        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return Utils::jsonDecode($request->getBody()->getContents());
    }

    /**
     * Recupera dados da nota fiscal do pedido.
     *
     * @param string $orderIdIntegration Dados do pedido da integradora.
     * @return  array                    Dados de nota fiscal do pedido [date, value, serie, number, key].
     */
    public function getInvoiceIntegration(string $orderIdIntegration): array
    {
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'date'      => '2022-01-01 00:00:00',
            'value'     => 100.00,
            'serie'     => 1,
            'number'    => 15,
            'key'       => '12345678901234567890123456789012345678901234'
        );
    }

    /**
     * Cancelar pedido na integradora.
     *
     * @param   int     $order              Código do pedido no Seller Center.
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function cancelIntegration(int $order, string $orderIntegration): bool
    {
        // request to cancel order
        $urlCancelOrder     = "pedido/$orderIntegration";
        $queryCancelOrder   = array(
            'json' => array(
                'situacao' => 'cancelado'
            )
        );

        try {
            $this->order_v2->request('POST', $urlCancelOrder, $queryCancelOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return true;
    }

    /**
     * Recupera dados de tracking.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   array   $items              Itens do pedido.
     * @return  array                       Array onde, a chave de cada item do array será o código SKU e o valor do item um array com os dados: quantity, shippingCompany, trackingCode, trackingUrl, generatedDate, shippingMethodName, shippingMethodCode, deliveryValue, documentShippingCompany, estimatedDeliveryDate, labelA4Url, labelThermalUrl, labelZplUrl, labelPlpUrl.
     * @throws  InvalidArgumentException
     */
    public function getTrackingIntegration(string $orderIntegration, array $items): array
    {
        // recuperar tracking na integradora.
        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $itemsTracking = array();

        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => 'Shipping Company',
                'trackingCode'              => 'AA_123456',
                'trackingUrl'               => 'https://rastreio.com/AA_123456',
                'generatedDate'             => date(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => 'Method Name',
                'shippingMethodCode'        => 'Method Code', // Se não exitir informar o mesmo que o shippingMethodName
                'deliveryValue'             => 0,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => null,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null
            );
        }

        return $itemsTracking;
    }

    /**
     * Importar a dados de rastreio.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order}).
     * @param   object  $dataTracking       Dados do rastreio do pedido (Api/V1/Tracking/{order}).
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setTrackingIntegration(string $orderIntegration, object $order, object $dataTracking): bool
    {
        // request to send tracking to integration.
        $urlReturnInvoice   = "pedido/$orderIntegration/rastreio";
        $queryReturnInvoice = array(
            'json' => array()
        );

        try {
            $this->order_v2->request('PUT', $urlReturnInvoice, $queryReturnInvoice);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return true;
    }

    /**
     * Recupera data de envio do pedido.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  string                      Data de envio do pedido.
     * @throws  InvalidArgumentException
     */
    public function getShippedIntegration(string $orderIntegration): string
    {
        return '2021-01-01 00:00:00';
    }

    /**
     * Recupera ocorrências do rastreio.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  array                       Dados de ocorrência do pedido. Um array com as chaves 'isDelivered' e 'occurrences', onde 'occurrences' será os dados de ocorrência do rastreio, composto pelos índices 'date' e 'occurrence'
     * @throws  InvalidArgumentException
     */
    public function getOccurrenceIntegration(string $orderIntegration): array
    {
        $occurrences = array(
            'isDelivered'   => false,
            'dateDelivered' => null,
            'occurrences'   => array()
        );

        $occurrences['occurrences']['AA_123456'][] = array(
            'date'          => '2021-01-01 00:00:00',
            'occurrence'    => 'Coletado'
        );

        $occurrences['occurrences']['AA_123456'][] = array(
            'date'          => '2021-01-02 00:00:00',
            'occurrence'    => 'Em trânsito'
        );

        return $occurrences;
    }

    /**
     * Importar a dados de ocorrência do rastreio.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order}).
     * @param   array   $dataOccurrence     Dados de ocorrência.
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setOccurrenceIntegration(string $orderIntegration, object $order, array $dataOccurrence): bool
    {
        // Se o pedido ainda não tem data de envio, será feito do envio da data.
        if (empty($order->data_envio)) {
            // request to send shipped date to integration.
            $urlShipped   = "pedido/$orderIntegration/enviado";
            $queryShipped = array(
                'json' => array(
                    'data_envio' => $order->data_envio
                )
            );

            try {
                $this->order_v2->request('PUT', $urlShipped, $queryShipped);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        }


        // Existe ocorrencias vou enviar para a integradora.
        if (count($dataOccurrence)) {
            $arrOccurrence = array();

            foreach ($dataOccurrence as $occurrence) {
                $arrOccurrence[] = array(
                    "description"   => $occurrence['name'],
                    "date"          => $occurrence['date']
                );
            }

            // request to send occurrence to integration.
            $urlImportOccurrence   = "pedido/$orderIntegration/rastreio";
            $queryImportOccurrence = array(
                'json' => array(
                    'ocorrencias' => $arrOccurrence
                )
            );

            try {
                $this->order_v2->request('POST', $urlImportOccurrence, $queryImportOccurrence);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        }

        return true;
    }

    /**
     * Recupera data de entrega do pedido.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  string                      Data de entrega do pedido.
     * @throws  InvalidArgumentException
     */
    public function getDeliveredIntegration(string $orderIntegration): string
    {
        return '2022-01-01';
    }

    /**
     * Importar a data de entrega.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order}).
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setDeliveredIntegration(string $orderIntegration, object $order): bool
    {
        // request to send delivered date to integration.
        $urlShipped   = "pedido/$orderIntegration/entregue";
        $queryShipped = array(
            'json' => array(
                'data_entrega' => $order->data_envio
            )
        );

        try {
            $this->order_v2->request('PUT', $urlShipped, $queryShipped);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return true;
    }
}