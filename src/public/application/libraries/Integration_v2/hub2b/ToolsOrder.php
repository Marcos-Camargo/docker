<?php

namespace Integration\hub2b;

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
            $nome           = $shipping_address->full_name;
        }
        else {
            $nome           = $billing_address->full_name;
        }

        $paymentType = $this->payments->parcels[0]->payment_type ?? '';

        if (
            likeText("%ticket%", strtolower($paymentType)) ||
            likeText("%boleto%", strtolower($paymentType))
        ) {
            $paymentValid = 'ticket';
        } elseif (
            likeText("%card%", strtolower($paymentType)) ||
            likeText("%credit%", strtolower($paymentType))
        ) {
            $paymentValid = 'credit';
        } elseif (
            likeText("%voucher%", strtolower($paymentType)) ||
            likeText("%conta a receber%", strtolower($paymentType))
        ) {
            $paymentValid = 'voucher';
        } elseif (
            likeText("%money%", strtolower($paymentType)) ||
            likeText("%dinheiro%", strtolower($paymentType)) ||
            likeText("%cash%", strtolower($paymentType)) ||
            likeText("%pix%", strtolower($paymentType))
        ) {
            $paymentValid = 'transfer';
        } else {
            $paymentValid = 'credit';
        }

        $newOrder = array(
            'reference'        => array(
                'idTenant'     => $this->order_v2->credentials->idTenant ?? '', 
                'store'        => $this->order_v2->sellerCenter,
                'virtual'      => $order->code,
                'source'       => $order->code,
                'system' => array(
                    'source' => $this->order_v2->model_settings->getValueIfAtiveByName('hub2b_source_name') ?: $this->order_v2->sellerCenter,
                )
            ),
            'shipping'      => array(
                'shippingDate'          => '', 
                'estimatedDeliveryDate' => $order->shipping->estimated_delivery,
                'responsible'           => $this->order_v2->getStoreOwnLogistic() ? 'seller' : 'Marketplace',
                'provider'              => $order->shipping->shipping_carrier,
                'service'               => $order->shipping->service_method,
                'price'                 => $order->shipping->seller_shipping_cost,
                'receiverName'          => $shipping_address->full_name,
                'address' => array(
                    'address'         => $shipping_address->street,
                    'neighborhood'    => $shipping_address->neighborhood,
                    'city'            => $shipping_address->city,
                    'state'           => $shipping_address->region,
                    'country'         => 'Brasil',  
                    'zipCode'         => $shipping_address->postcode,
                    'additionalInfo'  => $shipping_address->complement,                    
                    'reference'       => $shipping_address->reference,
                    'number'          => $shipping_address->number
                )
            ),
            'payment'      => array(
                'method'                   => $paymentValid, 
                'paymentDate'              => !in_array($order->status->code, array(1,2,96)) ? $order->payments->date_payment ?? dateNow()->format(DATETIME_INTERNATIONAL) : '',
                'purchaseDate'             => dateNow()->format(DATETIME_INTERNATIONAL) ,
                'approvedDate'             => !in_array($order->status->code, array(1,2,96)) ? $order->payments->date_payment ?? dateNow()->format(DATETIME_INTERNATIONAL) : '',
                'totalAmount'              => '',
                'totalAmountPlusShipping'  => '',
                'totalDiscount'            => 0,
                'installments'             => count($order->payments->parcels),
                'address' => array(
                    'address'         => $billing_address->street,
                    'neighborhood'    => $billing_address->neighborhood,
                    'city'            => $billing_address->city,
                    'state'           => $billing_address->region,
                    'country'         => 'Brasil',  
                    'zipCode'         => $billing_address->postcode,
                    'additionalInfo'  => $billing_address->complement,                    
                    'reference'       => $billing_address->region,
                    'number'          => $billing_address->number
                )
            ),
            'status'          => array(
                'status'      => in_array($order->status->code, array(1,2,96)) ? "Pending" : "Approved", 
                'updatedDate' => dateNow()->format(DATETIME_INTERNATIONAL) ,
                'active'      => true,
                'message'     => ''
            ),
            'customer'           => array(
                'name'           => $nome, 
                'documentNumber' => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'telephone'      => onlyNumbers($customer->phones[0] ?? $customer->phones[1] ?? ''),
                'mobileNumber'   => onlyNumbers($customer->phones[0] ?? $customer->phones[1] ?? ''),
                'email'          => $customer->email
            ),
            'createdDate'          => dateNow()->format(DATETIME_INTERNATIONAL) ,
            'products'             => array(),
            'orderNotes'           => array(),
            'orderAdditionalInfos' => array()
        );
        
        // calcula o valor do frete para dividir entre os itens proporcionalmente
        $priceFreightTotal = (float)$order->shipping->seller_shipping_cost;
        $priceFreight = $priceFreightTotal;
        $priceFreightPeItem = $priceFreightTotal / count($order->items);
        $totalProducts = 0;
        foreach ($order->items as $itensCount => $item) {
            $shippingCost = 0;

            if (($itensCount + 1) == count($order->items)) {
                $shippingCost = (float)$priceFreight;
            } else{
                $priceFreightTemp = roundDecimal($priceFreightPeItem);
                $priceFreight = $priceFreight - $priceFreightTemp;
                $shippingCost = (float)$priceFreightTemp;
            }

            $price = ((float)$item->total_price) / ($item->qty); // pega o valor do produto
            $totalProducts += ((float)$price) * ((int)$item->qty); // pega o valor total/montante total

            $itemPrd = array(
                'sku'          => trim($item->sku_variation ?? $item->sku),
                'name'         => trim($item->name),
                'quantity'     => (int)$item->qty,
                'price'        => $price,
                'shippingCost' => $shippingCost,
                'discount'     => 0,
                'type'         => "None"
            );
            $newOrder['products'][] = $itemPrd;
        }

        $newOrder['payment']['totalAmount'] = $totalProducts;
        $newOrder['payment']['totalAmountPlusShipping'] = $totalProducts + $order->shipping->seller_shipping_cost;

        $urlOrder = "/Orders";
        $queryOrder = array('json' => $newOrder);

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $msg = json_decode($exception->getMessage())->errors[0] ?? '';
            if (strpos(strtolower($msg), 'already exists') !== false) {
                $orderNumber = (int)filter_var($msg, FILTER_SANITIZE_NUMBER_INT);
                $systemName = $newOrder['reference']['system']['source'] ?? '';
                try {
                    $requestOrder = $this->order_v2->request('GET', "{$urlOrder}/{$systemName}/{$orderNumber}");
                    $order = Utils::jsonDecode($requestOrder->getBody()->getContents());
                    if (!property_exists($order, 'reference')) {
                        throw new InvalidArgumentException('Pedido não localizado');
                    }
                    return [
                        'id' => $order->reference->id,
                        'request' => "$urlOrder\n" . Utils::jsonEncode($queryOrder, JSON_UNESCAPED_UNICODE)
                    ];
                } catch (\Throwable $e) {

                }
            }
            throw new InvalidArgumentException($exception->getMessage());
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($contentOrder->reference->id)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Código de identificação do pedido não localizado no retorno.</li></ul>", "E");
            throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora.");
        }

        return array(
            'id'        => $contentOrder->reference->id,
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
        $urlOrder = "/Orders/$order";

        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $order = Utils::jsonDecode($request->getBody()->getContents());

        if (!property_exists($order, 'reference')) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        return $order;
    }

    /**
     * Recupera dados da nota fiscal do pedido.
     *
     * @param   string  $orderIdIntegration Dados do pedido da integradora.
     * @param   int     $orderid Código do pedido no Seller Center
     * @return  array                    Dados de nota fiscal do pedido [date, value, serie, number, key].
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderid): array
    {
        // Obter dados do pedido       
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
            if ($order->status->status == 'Pending') {
                $this->setApprovePayment($orderid, $orderIdIntegration);
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }


        try {
            $request = $this->order_v2->request('GET', "/Orders/{$orderIdIntegration}/Invoice");
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Ainda não faturado - " . $exception->getMessage());
        }

        $invoiceResponse = Utils::jsonDecode($request->getBody()->getContents());
           
        return [
            'date' => dateFormat($invoiceResponse->issueDate, DATETIME_INTERNATIONAL, null),
            'value' => roundDecimal($invoiceResponse->totalAmount),
            'serie' => (int)$invoiceResponse->series,
            'number' => (int)clearBlanks($invoiceResponse->number),
            'key' => clearBlanks($invoiceResponse->key),
            'link' => $invoiceResponse->xmlReference ?? '',
            'isDelivered' => $order->status->status == 'delivered' ? true : null
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

        $urlApproveOrder     = "/Orders/$orderIntegration/Status";
        $queryApproveOrder   = array(
            'json' => array(
                'status' => 'Approved',
                'updatedDate' =>  dateNow()->format(DATETIME_INTERNATIONAL),
                'active' => 'true',
                'message' => 'Pagamento do Pedido {'.$order.'} Aprovado - Status atualizado para approved.'
            )
        );

        try {
            $this->order_v2->request('PUT', $urlApproveOrder, $queryApproveOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

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
        $urlCancelOrder     = "/Orders/$orderIntegration/Status";
        $queryCancelOrder   = array(
            'json' => array(
                'status' => 'Canceled',
                'updatedDate' =>  dateNow()->format(DATETIME_INTERNATIONAL),
                'active' => 'false',
                'message' => 'Pedido cancelado - Status atualizado para canceled.'
            )
        );

        try {
            $this->order_v2->request('PUT', $urlCancelOrder, $queryCancelOrder);
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
        try {
            $request = $this->order_v2->request('GET', "/Orders/{$orderIntegration}/Tracking");
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $trackingResponse = Utils::jsonDecode($request->getBody()->getContents());
        $itemsTracking = array();

        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => $trackingResponse->shippingProvider,
                'trackingCode'              => $trackingResponse->code,
                'trackingUrl'               => $trackingResponse->url ?? '',
                'generatedDate'             => dateFormat($trackingResponse->shippingDate, DATETIME_INTERNATIONAL, null) ?? dateNow()->format(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => $trackingResponse->shippingService,
                'shippingMethodCode'        => $trackingResponse->code, // Se não exitir informar o mesmo que o shippingMethodName
                'deliveryValue'             => null,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => null,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null,
                'isDelivered'               => $order->status->status == 'delivered' ? true : null
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

        // Atualiza o Status
        $urlUpdateStatus     = "/Orders/$orderIntegration/Status";
        $queryUpdateStatus   = array(
            'json' => array(
                'situacao' => 'shipped',
                'updatedDate' =>  date('Y-m-d H:i:s'),
                'active' => 'true',
                'message' => 'Pedido {$order->code} Enviado - Status atualizado para shipped.'
            )
        );

        try {
            $this->order_v2->request('PUT', $urlUpdateStatus, $queryUpdateStatus);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possivel atualizar o status do pedido (".$order->code.") para enviado.");
        }

        //Informa dados de rastreio
        $urlTrackingCodeOrder     = "/Orders/$orderIntegration/Tracking";
        $queryTrackingCodeOrder   = array(
            'json' => array(
                "code"             => $dataTracking->tracking->tracking_code[0],
                "url"              => $dataTracking->tracking->tracking_url,
                "shippingDate"     => $order->shipping->shipped_date ?? date('Y-m-d H:i:s'),
                "shippingProvider" => $dataTracking->ship_company,
                "shippingService"  => $order->shipping->service_method
            )
        );

        try {
            $this->order_v2->request('POST', $urlTrackingCodeOrder, $queryTrackingCodeOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Ocorreu um erro ao enviar o rastreamento do pedido (".$order->code."). - ".$exception->getMessage());
        }
 
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
        $date = null;

        if (empty($dataOrder->shipping->shippingDate)) {
            // Pedido enviado, mas sem data de envio.
            if (in_array(strtolower($dataOrder->status->status), array('shipped', 'delivered'))) {
                $date = dateFormat($dataOrder->status->updatedDate, DATETIME_INTERNATIONAL, null) ?? dateNow()->format(DATETIME_INTERNATIONAL);
            }
        }
        else{
            $date = dateFormat($dataOrder->shipping->shippingDate, DATETIME_INTERNATIONAL, null);
        }
        
        $returnData = array(
            'isDelivered'               => $dataOrder->status->status == 'delivered' ? true : null,
            'date'  => $date ?: null
        );

        return $returnData;
        //return dateFormat($dataOrder->shipping->shippingDate, DATETIME_INTERNATIONAL, null);
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

        $orderStatus = $order->status->status ?? '';
        $deliveredDate = isset($order->status->updatedDate) ?
            date('Y-m-d H:i:s', strtotime($order->status->updatedDate))
            : null;
        return [
            'isDelivered' => in_array(strtolower($orderStatus), ['delivered','completed']),
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
        $urlUpdateStatus     = "/Orders/$orderIntegration/Status";
        $queryUpdateStatus   = array(
            'json' => array(
                'situacao' => 'shipped',
                'updatedDate' =>  date('Y-m-d H:i:s'),
                'active' => 'true',
                'message' => 'Pedido {$order->code} Enviado - Status atualizado para shipped.'
            )
        );

        try {
            $this->order_v2->request('PUT', $urlUpdateStatus, $queryUpdateStatus);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possivel atualizar o status do pedido (".$order->code.") para enviado.");
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
        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if ($order->status->status == 'delivered') {
            return dateFormat($order->status->updatedDate, DATETIME_INTERNATIONAL, null) ?? dateNow()->format(DATETIME_INTERNATIONAL);
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
        // request to send delivered date to integration.
        $urlUpdateStatus     = "/Orders/$orderIntegration/Status";
        $queryUpdateStatus   = array(
            'json' => array(
                'situacao' => 'completed',
                'updatedDate' =>  date('Y-m-d H:i:s'),
                'active' => 'true',
                'message' => 'Pedido {$order->code} Entregue - Status alterado para completed.'
            )
        );

        //ATUALIZAR A DATA DE ENVIO SE ESTIVER VAZIA

        try {
            $this->order_v2->request('PUT', $urlUpdateStatus, $queryUpdateStatus);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possivel atualizar o status do pedido (".$order->code.") para Entregue/Finalizado.");
        }

        return true;
    }
}
