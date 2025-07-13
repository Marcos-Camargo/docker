<?php

namespace Integration\ideris;

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
        $shipping_address   = $order->shipping->shipping_address;
        $billing_address    = $order->billing_address;
        $customer           = $order->customer;
        $payment            = $order->payments;
        $this->orderId      = $order->code;

        if ($this->order_v2->sellerCenter === 'conectala') {
            $full_name           = $shipping_address->full_name;
        }
        else {
            $full_name           = $billing_address->full_name;
        }

        // Cliente
        $nameComplete = explode(" ", trim($full_name));

        // Separa o nome do sobrenome para enviar em campos diferente
        $lastName = $nameComplete[count($nameComplete) - 1];
        unset($nameComplete[count($nameComplete) - 1]);
        $firstName = implode(" ", $nameComplete);
        $firstName = empty($firstName) ? $lastName : $firstName;

        $cnpjIntermediary = $this->order_v2->model_settings->getValueIfAtiveByName('financial_intermediary_cnpj');
        $razaoIntermediary = $this->order_v2->model_settings->getValueIfAtiveByName('financial_intermediary_corporate_name');
        if (empty($cnpjIntermediary) || empty($razaoIntermediary)) {
            throw new InvalidArgumentException('Intermediador de pagamento não configurado!');
        }

        $paymentType = $payment->parcels[0]->payment_type ?? '';

        if (
            likeText("%ticket%", strtolower($paymentType)) ||
            likeText("%boleto%", strtolower($paymentType)) ||
            likeText("%boleto bancario%", strtolower($paymentType))
        ) {
            $paymentValid = 'Boleto';
        } elseif (
            likeText("%card%", strtolower($paymentType)) ||
            likeText("%credit%", strtolower($paymentType)) ||
            likeText("%cartao de credito%", strtolower($paymentType))
        ) {
            $paymentValid = 'Cartao de Credito';
        } elseif (
            likeText("%voucher%", strtolower($paymentType)) ||
            likeText("%conta a receber%", strtolower($paymentType)) 
        ) {
            $paymentValid = 'Outras Formas de Pagamento';
        } elseif (
            likeText("%money%", strtolower($paymentType)) ||
            likeText("%dinheiro%", strtolower($paymentType)) ||
            likeText("%cash%", strtolower($paymentType)) ||
            likeText("%pix%", strtolower($paymentType))
        ) {
            $paymentValid = 'Dinheiro';
        } elseif(
            likeText("%transferencia%", strtolower($paymentType)) ||
            likeText("%tranferencia bancaria%", strtolower($paymentType))
        ){  
            $paymentValid = 'Transferencia Bancaria';
        }
        else {
            $paymentValid = 'Dinheiro';
        }
        
        $newOrder = array(
            'document'  => array(               
                'type'  => $customer->person_type === 'pf' ? "cpf" : "cnpj",
                'value' => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj)
            ),
            'phone'  => array(               
                'areaCode' => substr(onlyNumbers($shipping_address->phone), 0, 2),
                'number'   => substr(onlyNumbers($shipping_address->phone), 2),
                'extension'=> null,
                'verified' => null
            ),
            'phoneAlternative'  => array(               
                'areaCode' => null,
                'number'   => null,
                'extension'=> null,
                'verified' => null
            ),
            'customer'  => array(               
                'firstName' => $firstName,
                'lastName'  => $lastName,
                'nickname'  => $firstName,
                'email'     => $customer->email,
                'code'      => $customer->id
            ),
            'deliveryOptions'  => array(               
                'name'                      => $order->shipping->shipping_carrier." - ".$order->shipping->service_method,
                'cost'                      => $order->shipping->seller_shipping_cost,
                'listCost'                  => 0.0,
                'estimatedDeliveryTime'     => $order->shipping->estimated_delivery,
                'estimatedScheduleTime'     => null,
                'estimatedHandlingTime'     => null,
                'estimatedDeliveryExtended' => null,
                'estimatedDeliveryLimit'    => null,
                'estimatedDeliveryFinal'    => null
            ),
            'country'  => array(               
                'name'         => 'Brasil',
                'abbreviation' => 'BR'
            ),
            'state'  => array(               
                'name'         => $shipping_address->region,
                'abbreviation' => $shipping_address->region
            ),
            'city'  => array(               
                'name'         => $shipping_address->city,
                'abbreviation' => $shipping_address->city
            ),
            'district'  => array(               
                'name'         => $shipping_address->neighborhood,
                'abbreviation' => $shipping_address->neighborhood
            ),
            'address'  => array(               
                'street'        => $shipping_address->street,
                'number'        => $shipping_address->number,
                'zipCode'       => $shipping_address->postcode,
                'receiverName'  => $shipping_address->full_name,
                'receiverPhone' => onlyNumbers($shipping_address->phone),
                'comment'       => $shipping_address->complement
            ),
            'delivery'  => array(               
                'created'              => null,
                'firstPrinted'         => null,
                'shipmentType'         => $order->shipping->shipping_carrier,
                'cost'                 => $order->shipping->seller_shipping_cost,
                'mode'                 => $order->shipping->service_method,
                'shipmentMode'         => $order->shipping->service_method, 
                'siteId'               => null,
                'code'                 => (string)$order->code,
                'statusDescription'    => null,
                'trackingCode'         => null,
                'trackingMethod'       => null,
                'subStatusDescription' => null
            ),
            'order'    => array(               
                'authenticationId'        => $this->order_v2->model_settings->getValueIfAtiveByName('ideris_authentication_id'),
                'sendCorreio'             => null,
                'expedition'              => null,
                'picking'                 => null,
                'closed'                  => null,
                'created'                 => null,
                'expiration'              => null,
                'delivery'                => null,
                'lastUpdated'             => null,
                'paidAmount'              => $payment->total_products,
                'totalAmount'             => $payment->total_products,
                'totalAmountWithShipping' => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
                'paymentType'             => $paymentValid,
                'notification'            => 0,
                'marketplaceName'         => $order->system_marketplace_code,
                'origin'                  => "Conecta La",
                'statusId'                => in_array($order->status->code, array(1)) ? '1019' : 
                                            (in_array($order->status->code, array(6, 60)) ? '1012' :
                                            (in_array($order->status->code, array(5, 45, 55)) ? '1011' :
                                            (in_array($order->status->code, array(3, 56, 57)) ? '1007' :
                                            (in_array($order->status->code, array(97, 95, 96, 98, 99)) ? '1015' : '1007')))),

                'statusDescription'       => in_array($order->status->code, array(3, 56, 57)) ? 'PEDIDO_ABERTO' : 
                                            (in_array($order->status->code, array(1)) ? 'PEDIDO_PENDENTE' : 
                                            (in_array($order->status->code, array(6, 60)) ? 'PEDIDO_ENTREGUE' :
                                            (in_array($order->status->code, array(5, 45, 55)) ? 'PEDIDO_EM_TRANSITO' :
                                            (in_array($order->status->code, array(97, 95, 96, 98, 99)) ? 'PEDIDO_CANCELADO' : 'PEDIDO_ABERTO')))),
                'statusDetail'            => $order->status->label,
                'code'                    => $order->marketplace_number."-"."$order->code",
                'packId'                  => null,
                'intermediaryName'        => $order->system_marketplace_code,
                'intermediaryCnpj'        => $cnpjIntermediary,
                'mktStatusDescription'    => in_array($order->status->code, array(1,2,96)) ? 'Aberto' : 'Approved',
            ),
            'values'  => array(               
                'transportCustomer'    => 0,
                'transportSeller'      => 0,
                'transportMarketplace' => 0,
                'discount'             => 0,
                'fee'                  => 0,
                'payment'              => 0,
                'marketplaceValue'     => 0
            ),
            'actions'   => array(
                array(               
                    'action'           => "",
                    'value'            => "",
                ),
            ),
            'payments'  => array(),
            'items'     => array(),
        );

        foreach ($order->items as $item) {
            $itemPrd = array(
                'item' => array(               
                    'unitPrice'=> $item->original_price,
                    'quantity' => $item->qty
                ),
                'itemProduct' => array(
                    'categoryId'        => null,
                    'title'             => trim($item->name),
                    'sellerCustomField' => trim($item->sku_variation ?: $item->sku),
                    'variationId'       => trim($item->sku_variation ? $item->sku_integration : ''),
                    'condition'         => null,
                    'code'              => trim($item->sku_variation ?: $item->sku),
                    'permalink'         => null,
                    'type'              => null,
                    'image'             => null,
                    'freeShipping'      => null
                ),
                'itemProductAttributes'     => array(),
            );
            $newOrder['items'][] = $itemPrd;
        }
        
        
        if (count($order->payments->parcels)) {
                foreach ($order->payments->parcels as $payment) {
                    $newOrder['payments'][] = array( 
                    'approved'           => !in_array($order->status->code, array(1,2,96)) ? date('Y-m-d', strtotime($order->payments->date_payment ?? $order->created_at)) : date('Y-m-d', strtotime($order->created_at)) ,
                    'created'            => null,
                    'lastModified'       => null,
                    'couponAmount'       => 0,
                    'installmentAmount'  => 0.0, //$payment->value,
                    'overpaidAmount'     => 0,
                    'shippingCost'       => $order->shipping->seller_shipping_cost,
                    'totalPaidAmount'    => $payment->value, 
                    'transactionAmount'  => ($payment->value)/($payment->parcel),
                    'activationUri'      => null,
                    'authorizationCode'  => null,
                    'cardId'             => null,
                    'code'               => ($payment->payment_id != null ) ? ($payment->payment_id) : ($order->code),
                    'couponId'           => null,
                    'deferredPeriod'     => null,
                    'installments'       => (string)$payment->parcel,
                    'issuerId'           => null,
                    'operationType'      => null,
                    'orderCode'          => $order->marketplace_number."-"."$order->code",
                    'payerId'            => null,
                    'methodId'           => null,
                    'type'               => tirarAcentos($payment->payment_type),
                    'siteId'             => null,
                    'status'             => "approved",
                    'statusCode'         => null,
                    'statusDetail'       => null,
                    'transactionOrderId' => null
                );

            }

            } else {
                $newOrder['payments'][] = array(
                'approved'           => !in_array($order->status->code, array(1,2,96)) ? date('Y-m-d', strtotime($order->payments->date_payment ?? $order->created_at)) : null,
                'created'            => null,
                'lastModified'       => null,
                'couponAmount'       => 0,
                'installmentAmount'  => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
                'overpaidAmount'     => 0,
                'shippingCost'       => $order->shipping->seller_shipping_cost,
                'totalPaidAmount'    => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
                'transactionAmount'  => 0,
                'activationUri'      => null,
                'authorizationCode'  => null,
                'cardId'             => null,
                'code'               => null,
                'couponId'           => null,
                'deferredPeriod'     => null,
                'installments'       => null,
                'issuerId'           => null,
                'operationType'      => null,
                'orderCode'          => "$order->code",
                'payerId'            => null,
                'methodId'           => null,
                'type'               => null,
                'siteId'             => null,
                'status'             => null,
                'statusCode'         => null,
                'statusDetail'       => null,
                'transactionOrderId' => null
            );
        }
        
        $urlOrder = "/order";
        $queryOrder = array('json' => $newOrder);

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>{$exception->getMessage()}</li></ul>".$this->order_v2->createButtonLogRequestIntegration($newOrder), "E");
            throw new InvalidArgumentException($exception->getMessage());
        }

        $contentOrderRequest = Utils::jsonDecode($request->getBody()->getContents());
        $contentOrder = $contentOrderRequest->obj;

        if (!isset($contentOrder->id)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Código de identificação do pedido não localizado no retorno.</li></ul>".$this->order_v2->createButtonLogRequestIntegration($newOrder), "E");
            throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora.");
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $order->code,
            'type'      => 'create_order',
            'request'   => json_encode($newOrder, JSON_UNESCAPED_UNICODE),
            'response'  => json_encode($contentOrderRequest, JSON_UNESCAPED_UNICODE)
        ));

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
        $urlOrder = "/order/$order";

        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $order = Utils::jsonDecode($request->getBody()->getContents());

        if (!property_exists($order, 'obj')) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        return $order->obj;
    }

    /**
     * Recupera dados da nota fiscal do pedido.
     *
     * @param string $orderIdIntegration Dados do pedido da integradora.
     * @return  array                    Dados de nota fiscal do pedido [date, value, serie, number, key].
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderid): array
    {      

        // Obter dados do pedido       
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
            if ($order->statusId == 1007) {
                $this->setApprovePayment($orderid, $orderIdIntegration);
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIdIntegration) não localizado.");
        }

        $orderId = $order->id;
        try {
            $request = $this->order_v2->request('GET', "/order/{$orderId}/invoice");
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $invoiceResponseRequest = Utils::jsonDecode($request->getBody()->getContents());
        $invoiceResponse =  $invoiceResponseRequest->obj;

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $this->orderId,
            'type'      => 'invoice_order',
            'request'   => '',
            'response'  => json_encode($invoiceResponseRequest, JSON_UNESCAPED_UNICODE)
        ));

        return [
            'date' => date('Y-m-d H:i:s'),
            'value' => (float)$order->totalAmount,
            'serie' => (int)$invoiceResponse->serie,
            'number' => (int)clearBlanks($invoiceResponse->number),
            'key' => clearBlanks($invoiceResponse->key),
            'link' => $invoiceResponse->urlDanfe ?? '',
            'isDelivered' => $order->obj->statusId == 1012 ? true : null
        ];
    }

    /**
     * Aprovar pagamento do pedido inserido.
     *
     * @param   int     $order              Código do pedido no Seller Center.
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  bool    Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setApprovePayment(int $order, string $orderIntegration): bool
    {
        $orderIdMkt = $this->order_v2->getNumeroMarketplaceByOrderId($order); //retorna num do pedido no marketplace
        $urlApproveOrder     = "/order";
        $queryApproveOrder   = array(
            'json' => array(
                'orderId'                 => $orderIntegration,
                'strId'                   => $orderIdMkt."-".$order,
                'statusId'                => 1038
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlApproveOrder, $queryApproveOrder);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $order,
            'type'      => 'confirm_payment',
            'request'   => json_encode($queryApproveOrder['json'], JSON_UNESCAPED_UNICODE),
            'response'  => json_encode($response, JSON_UNESCAPED_UNICODE)
        ));

        return true;
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
        $orderIdMkt = $this->order_v2->getNumeroMarketplaceByOrderId($order);
        $urlCancelOrder     = "/order";
        $queryCancelOrder   = array(
            'json' => array(
                'orderId'                 => $orderIntegration,
                'strId'                   => $orderIdMkt."-".$order,
                'statusId'                => 1015
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlCancelOrder, $queryCancelOrder);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $order,
            'type'      => 'cancel_order',
            'request'   => json_encode($queryCancelOrder['json'], JSON_UNESCAPED_UNICODE),
            'response'  => json_encode($response, JSON_UNESCAPED_UNICODE)
        ));

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
            $request = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $trackingResponse = Utils::jsonDecode($request->getBody()->getContents());
        $trackingResponse = $trackingResponse->obj;
        $itemsTracking = array();

        if (!$trackingResponse) {
            throw new InvalidArgumentException("Pedido {$orderIntegration} não localizado.");
        }

        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => $trackingResponse->deliveryTrackingMethod,
                'trackingCode'              => $trackingResponse->deliveryTrackingCode ?? '',
                'trackingUrl'               => $trackingResponse->deliveryTrackingMethod,
                'generatedDate'             => dateFormat($trackingResponse->delivered, DATETIME_INTERNATIONAL, null) ?? dateNow()->format(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => $trackingResponse->deliveryTrackingMethod,
                'shippingMethodCode'        => $trackingResponse->deliveryShippingMode,
                'deliveryValue'             => null,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => null,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null,
                'isDelivered'               => $request->obj->statusId == 1012 ? true : null
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
        $urlReturnTracking   = "/order";
        $queryReturnTracking = array(
            'json' => array(
                'orderId'                 => $orderIntegration,
                'strId'                   => $order->marketplace_number."-"."$order->code",
                'statusId'                => 1011,
                'trackingCode'            => $dataTracking->tracking->tracking_code[0]
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlReturnTracking, $queryReturnTracking);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $this->orderId,
            'type'      => 'set_tracking',
            'request'   => json_encode($queryReturnTracking['json'], JSON_UNESCAPED_UNICODE),
            'response'  => json_encode($response, JSON_UNESCAPED_UNICODE)
        ));

        return true;
    }

    /**
     * Recupera data de envio do pedido.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  array                      Data de envio do pedido.
     * @throws  InvalidArgumentException
     */
    public function getShippedIntegration(string $orderIntegration)
    {
        try {
            $dataOrder = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (empty($dataOrder->obj->sent)) {
            // Pedido enviado, mas sem data de envio.
            if (in_array($dataOrder->obj->statusId, array('1010', '1011'))) { 
                $date = dateFormat($dataOrder->obj->sent, DATETIME_INTERNATIONAL, null) ?? dateNow()->format(DATETIME_INTERNATIONAL);
            }
        }else{
            $date = dateFormat($dataOrder->shipping->shippingDate, DATETIME_INTERNATIONAL, null);
        }

        $returnData = array(
            'isDelivered'  => $dataOrder->obj->statusId == 1012 ? true : null,
            'date' => $date
        );
        
        return $returnData;
        //return dateFormat($dataOrder->obj->sent, DATETIME_INTERNATIONAL, null);
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
        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $orderStatus = $order->obj->statusId ?? '';
        $deliveredDate = isset($order->obj->delivered) ?
            date('Y-m-d H:i:s', strtotime($order->obj->delivered))
            : null;
        return [
            'isDelivered' => in_array($orderStatus, ['1011','1012']), //ARRUMAR STATUS
            'dateDelivered' => $deliveredDate,
            'occurrences' => []
        ];
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
        // request to send tracking to integration.
        $urlReturnOccurrence   = "/order";
        $queryReturnOccurrence = array(
            'json' => array(
                'orderId'                 => $orderIntegration,
                'strId'                   => $order->marketplace_number."-"."$order->code",
                'statusId'                => 1011 //ENVIADO
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlReturnOccurrence, $queryReturnOccurrence);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $this->orderId,
            'type'      => 'set_shipped',
            'request'   => json_encode($queryReturnOccurrence['json'], JSON_UNESCAPED_UNICODE),
            'response'  => json_encode($response, JSON_UNESCAPED_UNICODE)
        ));

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
        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if ($order->obj->statusId == 1012) { // ENTREGUE
            return dateFormat($order->obj->delivered, DATETIME_INTERNATIONAL, null) ?? dateNow()->format(DATETIME_INTERNATIONAL);
        } else{
            return '';
        }
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
        // request to send tracking to integration.
        $urlReturnDelivered   = "/order";
        $queryReturnDelivered = array(
            'json' => array(
                'orderId'                 => $orderIntegration,
                'strId'                   => $order->marketplace_number."-"."$order->code",
                'statusId'                => 1012 //ENTREGUE
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlReturnDelivered, $queryReturnDelivered);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $this->orderId,
            'type'      => 'set_delivered',
            'request'   => json_encode($queryReturnDelivered['json'], JSON_UNESCAPED_UNICODE),
            'response'  => json_encode($response, JSON_UNESCAPED_UNICODE)
        ));

        return true;
    }
}