<?php

namespace Integration\pluggto;

use Exception;
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
        $this->orderId      = $order->code;

        // Cliente
        $nameCompleteReceiver = explode(" ", trim($shipping_address->full_name));
        $nameCompletePayer = explode(" ", trim($this->order_v2->sellerCenter === 'conectala' ? $shipping_address->full_name : $billing_address->full_name));

        // Separa o nome do sobrenome para enviar em campos diferente
        $lastNameReceiver   = $nameCompleteReceiver[count($nameCompleteReceiver) - 1];
        $lastNamePayer      = $nameCompletePayer[count($nameCompletePayer) - 1];
        
        unset($nameCompleteReceiver[count($nameCompleteReceiver) - 1]);
        unset($nameCompletePayer[count($nameCompletePayer) - 1]);
        
        $firstNameReceiver  = implode(" ", $nameCompleteReceiver);
        $firstNamePayer     = implode(" ", $nameCompletePayer);

        $saleIntermediary       = (string)$this->order_v2->model_settings->getValueIfAtiveByName('sale_intermediary_pluggto');
        $paymentIntermediary    = (string)$this->order_v2->model_settings->getValueIfAtiveByName('payment_intermediary_pluggto');
      
        // Na conecta lá, enviamos os dados da fatura igual ao da entrega.
        if ($this->order_v2->sellerCenter === 'conectala') {
            $arrClient = array(
                'payer_name'                => $firstNamePayer,
                'payer_lastname'            => $lastNamePayer,
                'payer_address'             => $shipping_address->street,
                'payer_address_number'      => $shipping_address->number,
                'payer_additional_info'     => $shipping_address->reference,
                'payer_address_complement'  => $shipping_address->complement,
                'payer_zipcode'             => $shipping_address->postcode,
                'payer_neighborhood'        => $shipping_address->neighborhood,
                'payer_city'                => $shipping_address->city,
                'payer_state'               => $shipping_address->region,
                'payer_country'             => $shipping_address->country,
                'payer_phone_area'          => substr(onlyNumbers($shipping_address->phone), 0, 2),
                'payer_phone'               => substr(onlyNumbers($shipping_address->phone), 2),
                'payer_phone2_area'         => substr(onlyNumbers($shipping_address->phone), 0, 2),
                'payer_phone2'              => substr(onlyNumbers($shipping_address->phone), 2),
                'payer_email'               => $customer->email,
                'payer_tax_id'              => $customer->person_type === 'pf' ? onlyNumbers($customer->cpf) : '',
                'payer_document'            => $customer->person_type === 'pj' ? onlyNumbers($customer->cnpj) : '',
                'payer_razao_social'        => $customer->person_type === 'pj' ? $shipping_address->full_name : '',
                'payer_company_name'        => $customer->person_type === 'pj' ? $shipping_address->full_name : '',
                'payer_gender'              => "n/a",
                $customer->person_type === 'pf' ? 'payer_cpf' : 'payer_cnpj' => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj)
            );
        }
        // não é conecta lá, enviamos o endereço da fatura
        else {
            $arrClient = array(
                'payer_name'                => $firstNamePayer,
                'payer_lastname'            => $lastNamePayer,
                'payer_address'             => $billing_address->street,
                'payer_address_number'      => $billing_address->number,
                'payer_additional_info'     => '',
                'payer_address_complement'  => $billing_address->complement,
                'payer_zipcode'             => $billing_address->postcode,
                'payer_neighborhood'        => $billing_address->neighborhood,
                'payer_city'                => $billing_address->city,
                'payer_state'               => $billing_address->region,
                'payer_country'             => $billing_address->country,
                'payer_phone_area'          => substr(onlyNumbers($customer->phone[0] ?? $customer->phone[1] ?? ''), 0, 2),
                'payer_phone'               => substr(onlyNumbers($customer->phone[0] ?? $customer->phone[1] ?? ''), 2),
                'payer_phone2_area'         => substr(onlyNumbers($customer->phone[1] ?? $customer->phone[0] ?? ''), 0, 2),
                'payer_phone2'              => substr(onlyNumbers($customer->phone[1] ?? $customer->phone[0] ?? ''), 2),
                'payer_email'               => $customer->email,
                'payer_tax_id'              => $customer->person_type === 'pf' ? onlyNumbers($customer->cpf) : '',
                'payer_document'            => $customer->person_type === 'pj' ? onlyNumbers($customer->cnpj) : '',
                'payer_razao_social'        => $customer->person_type === 'pj' ? $billing_address->full_name : '',
                'payer_company_name'        => $customer->person_type === 'pj' ? $billing_address->full_name : '',
                'payer_gender'              => "n/a",
                $customer->person_type === 'pf' ? 'payer_cpf' : 'payer_cnpj' => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj)
            );
        }

        $newOrder = array(
            'receiver_name'                  => $firstNameReceiver,
            'receiver_lastname'              => $lastNameReceiver,
            'receiver_address'               => $shipping_address->street,
            'receiver_address_number'        => $shipping_address->number,
            'receiver_address_complement'    => $shipping_address->complement,
            'receiver_address_reference'     => $shipping_address->reference,
            'receiver_zipcode'               => $shipping_address->postcode,
            'receiver_neighborhood'          => $shipping_address->neighborhood,
            'receiver_city'                  => $shipping_address->city,
            'receiver_state'                 => $shipping_address->region,
            'receiver_country'               => $shipping_address->country,
            'receiver_email'                 => $customer->email,
            'receiver_phone_area'            => substr(onlyNumbers($shipping_address->phone), 0, 2),
            'receiver_phone'                 => substr(onlyNumbers($shipping_address->phone), 2),
            'receiver_phone2_area'           => substr(onlyNumbers($shipping_address->phone), 0, 2),
            'receiver_phone2'                => substr(onlyNumbers($shipping_address->phone), 2),
            'receiver_schedule_date'         => "",
            'receiver_schedule_period'       => "afternoon",
            'sale_intermediary'              => $saleIntermediary,
            'payment_intermediary'           => $paymentIntermediary,
            'intermediary_seller_id'         => $this->order_v2->store,
            'email_nfe'                      => $customer->email,
            'total_paid'                     => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
            'shipping'                       => $order->shipping->seller_shipping_cost,
            'subtotal'                       => $order->payments->discount + $order->payments->total_products, //valor original
            'total'                          => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
            'discount'                       => $order->payments->discount,
            'payment_installments'           => 0,
            'user_client_id'                 => $this->order_v2->credentials->client_id,
            'status'                         => in_array($order->status->code, array(1,2,96)) ? 'pending' : 'approved',
            'original_id'                    => $order->marketplace_number,
            'expected_send_date'             => $order->shipping->cross_docking_deadline ?? '',
            'expected_delivery_date'         => $order->shipping->estimated_delivery ?? '',
            'auto_reserve'                   => true,
            'external'                       => $this->orderId,
            'channel'                        => $this->order_v2->nameSellerCenter,
            'items'                         => array(),
            'payments'                      => array(),
            'shipments'                     => array(
                array(
                    "shipping_company"          => $order->shipping->shipping_carrier,
                    "shipping_method"           => $order->shipping->service_method,
                    "track_code"                => "",
                    "track_url"                 => "",
                    "status"                    => 'issue', //shipped|delivered|issue
                    "estimate_delivery_date"    => $order->shipping->estimated_delivery,
                    "date_shipped"              => "",
                    "date_delivered"            => "",
                    "date_cancelled"            => "",
                    "nfe_key"                   => "",
                    "nfe_link"                  => "",
                    "nfe_number"                => "",
                    "nfe_serie"                 => "",
                    "nfe_date"                  => "",
                    "cfops"                     => "",
                    "documents"                 => array(
                        array(
                            "url"       => "",
                            "external"  => "",
                            "type"      => "",
                        )
                    ),
                    "shipping_items"            => array(),
                    "issues"                    => array(
                        array(
                            "description"   => "",
                            "date"          => "",
                        )
                    ),
                )
            ),
            //'stock_code'                     => "warehouse_1",
            //'price_code'                     => "price_reseller"
        );

        $newOrder = array_merge($newOrder, $arrClient);

        foreach ($order->payments->parcels as $parcel) {
            $paymentType = $parcel->payment_type ?? '';

            switch ($paymentType) {
                case 'bankInvoice':
                    $paymentValid = "ticket";
                    break;
                case 'creditCard':
                    $paymentValid = "credit";
                    break;
                case 'debitCard':
                    $paymentValid = "debit";
                    break;
                case 'giftCard':
                    $paymentValid = "voucher";
                    break;
                case 'instantPayment':
                    $paymentValid = "transfer";
                    break;
            }

            $newOrder['payments'][] = array(
                "payment_type"          => $paymentValid,
                "payment_method"        => $parcel->payment_method,
                "payment_installments"  => (int)$parcel->parcel,
                // "payment_total"      => $parcel->value,
                "payment_total"         =>  $this->order_v2->sellerCenter === 'somaplace' ? number_format($order->payments->net_amount, 2, '.', '') : number_format($order->payments->gross_amount, 2, '.', ''),
                "payment_quota"         => 0,
                "payment_interest"      => 0
            );
        }

        foreach ($order->items as $item) {
            $itemPrd = array(
                'sku'       => trim($item->sku_variation ?? $item->sku),
                'price'     => number_format($item->original_price, 2, '.', ''),
                'total'     => number_format($item->original_price * $item->qty, 2, '.', ''),
                'name'      => trim($item->name),
                'quantity'  => $item->qty,
            );

            $itemsPrdShipping = array(
                'sku'       => trim($item->sku_variation ?? $item->sku),
                'quantity'  => $item->qty
            );

            $newOrder['shipments'][0]['shipping_items'][] = $itemsPrdShipping;
            $newOrder['items'][] = $itemPrd;
        }

        // desativado para não ocorrer problemas, até conseguir credenciais de homologação

        $urlOrder = "orders";
        $queryOrder = array('json' => $newOrder);

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $error = $exception->getMessage();
            try {
                $errorDecode = Utils::jsonDecode($exception->getMessage());
            } catch (InvalidArgumentException $exception) {
                $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "Não foi possível integrar o pedido $this->orderId.<br><ul><li>$error</li></ul>", "E");
                throw new InvalidArgumentException($error);
            }

            $error = $errorDecode->details->errmsg ?? $error;
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "Não foi possível integrar o pedido $this->orderId.<br><ul><li>$error</li></ul>", "E");
            throw new InvalidArgumentException($error);
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());
        $contentOrder = $contentOrder->Order;

        if (!isset($contentOrder->id)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Código de identificação do pedido não localizado no retorno.</li></ul>", "E");
            throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora.");
        }

        $idPluggTo = $contentOrder->id;
        $this->orderIdIntegration = $idPluggTo;

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order->code,
            'type'           => 'create_order',
            'request'        => json_encode($newOrder, JSON_UNESCAPED_UNICODE),
            'response'       => json_encode($contentOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlOrder
        ));

        return array(
            'id'        => $idPluggTo,
            'request'   => "$urlOrder\n" . Utils::jsonEncode($newOrder, JSON_UNESCAPED_UNICODE)
        );
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
        $urlApprovePayment      = "orders/$orderIntegration";
        $queryApprovePayment    = array(
            'json' => array(
                'status' => 'approved'
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlApprovePayment, $queryApprovePayment);
            $response = Utils::jsonDecode($request->getBody()->getContents());

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'confirm_payment',
                'request'        => json_encode($queryApprovePayment, JSON_UNESCAPED_UNICODE),
                'request_method' => 'PUT',
                'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
                'response_code'  => $request->getStatusCode(),
                'request_uri'    => $urlApprovePayment
            ));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $error = $exception->getMessage();
            try {
                $errorDecode = Utils::jsonDecode($error);
                $error = $errorDecode->details->errmsg ?? $error;
                $this->order_v2->log_integration("Erro para aprovar o pagamento do pedido ($order)", "<h4>Não foi possível aprovar o pagamento do pedido $order</h4> <ul><li>$error</li></ul>", "E");
            } catch (InvalidArgumentException $exception) {
                $this->order_v2->log_integration("Erro para aprovar o pagamento do pedido ($order)", "<h4>Não foi possível aprovar o pagamento do pedido $order</h4> <ul><li>{$exception->getMessage()}</li></ul>", "E");
            }
        }

        return true;   
    }

    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int      $order  Código do pedido na integradora.
     * @return  array|object            Dados do pedido na integradora.
     */
    public function getOrderIntegration($order)
    {
        $urlOrder = "orders/$order";

        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $order = Utils::jsonDecode($request->getBody()->getContents());

        if (!property_exists($order, 'Order')) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        return $order->Order;
    }

    /**
     * Recupera dados da nota fiscal do pedido.
     *
     * @param string $orderIdIntegration Dados do pedido da integradora.
     * @param int $orderid Código do pedido no Seller Center
     * @return  array                    Dados de nota fiscal do pedido [date, value, serie, number, key].
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderid): array
    {
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
            if ($order->status == 'pending') {
                $this->setApprovePayment($orderid, $orderIdIntegration);
            }
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIdIntegration) não localizado.");
        }
        
        $shipment = $order->shipments[0];
        $dateEmission = null;
        if($order->shipments[0]->nfe_date){
            $dateEmission = dateFormat($order->shipments[0]->nfe_date, DATETIME_INTERNATIONAL, null);
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'invoice_order',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($order, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => null
        ));

        return array(
            'date'      => $dateEmission, 
            'value'     => roundDecimal($order->total),
            'serie'     => (int)clearBlanks($order->shipments[0]->nfe_serie),
            'number'    => (int)clearBlanks($order->shipments[0]->nfe_number),
            'key'       => clearBlanks($order->shipments[0]->nfe_key),
            'isDelivered' => $shipment->status === 'delivered' ? true : null
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
        // Pedido já foi cancelado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        // request to cancel order
        $urlCancelOrder     = "orders/$orderIntegration";
        $queryCancelOrder   = array(
            'json' => array(
                'status' => 'canceled'
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlCancelOrder, $queryCancelOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'cancel_order',
            'request'        => json_encode($queryCancelOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'PUT',
            'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlCancelOrder
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
        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $itemsTracking = array();

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        // Leio apenas a primeira entrega. Não sei qual produto vai por entrega.
        $shipment = $order->shipments[0];        
        
        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => $shipment->shipping_company,
                'trackingCode'              => $shipment->track_code,
                'trackingUrl'               => $shipment->track_url,
                'generatedDate'             => date(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => $shipment->shipping_method,
                'shippingMethodCode'        => $shipment->shipping_method,
                'deliveryValue'             => 0,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => null,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null,
                'isDelivered'               => $shipment->status === 'delivered' ? true : null
            );
        }

        return $itemsTracking;
    }

    /**
     * Importar a dados de rastreio
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order})
     * @param   object  $dataTracking       Dados do rastreio do pedido (Api/V1/Tracking/{order})
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setTrackingIntegration(string $orderIntegration, object $order, object $dataTracking): bool
    {
        try {
            $dataOrderIntegration = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$dataOrderIntegration) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        if (!property_exists($dataOrderIntegration, 'shipments') || empty($dataOrderIntegration->shipments)) {
            throw new InvalidArgumentException("Dados de entrega do pedido ($orderIntegration) não localizado.");
        }

        // request to send tracking to integration
        $urlReturnInvoice   = "orders/$orderIntegration";
        $queryReturnInvoice = array(
            'json' => array(
                'status'    => 'shipping_informed',
                'shipments' => array(
                    array(
                        'id'                        => $dataOrderIntegration->shipments[0]->id,
                        'shipping_company'          => $dataTracking->ship_company,
                        'shipping_method'           => $dataTracking->ship_service,
                        'estimate_delivery_date'    => $dataTracking->expected_delivery_date,
                        'track_code'                => $dataTracking->tracking->tracking_code[0],
                        'track_url'                 => $dataTracking->tracking->tracking_url,
                        'date_shipped'              => "",
                    )
                )
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlReturnInvoice, $queryReturnInvoice);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_tracking',
            'request'        => json_encode($queryReturnInvoice, JSON_UNESCAPED_UNICODE),
            'request_method' => 'PUT',
            'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlReturnInvoice
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

        $dateShipped = '';
        foreach ($dataOrder->shipments as $shipment) {
            if($shipment->date_shipped){
                $dateShipped = dateFormat($shipment->date_shipped, DATETIME_INTERNATIONAL, null);
            }
        }

        $firstShipment = $dataOrder->shipments[0] ?? null;
        $isDelivered = $firstShipment && $firstShipment->status === 'delivered' ? true : null;

        
        $shippedDate = array(
            'isDelivered' => $isDelivered,
            'date' => $dateShipped ?: null
        );
        return $shippedDate;
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
            $dataOrder = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $occurrences = array(
            'occurrences'   => array()
        );

        $occurrences['isDelivered']     = !empty($dataOrder->shipments[0]->date_delivered) || $dataOrder->status === 'delivered';
        $occurrences['dateDelivered']   = $dataOrder->shipments[0]->date_delivered ?? dateFormat($dataOrder->modified, DATETIME_INTERNATIONAL, null);;
        $dataOccurrences = $dataOrder->shipments[0]->issues;
        //$dataOccurrences[] = (object)array('description' => 'Coletado', 'date' => '2022-01-27 09:00:00');

        foreach ($dataOccurrences as $issue) {
            if (empty($issue->description) || empty($issue->date)) {
                continue;
            }

            $occurrences['occurrences'][$dataOrder->shipments[0]->track_code][] = array(
                'date'          => dateFormat($issue->date, DATETIME_INTERNATIONAL, null),
                'occurrence'    => $issue->description
            );
        }

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
        try {
            $dataOrderIntegration = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$dataOrderIntegration) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        if (!property_exists($dataOrderIntegration, 'shipments') || empty($dataOrderIntegration->shipments)) {
            throw new InvalidArgumentException("Dados de entrega do pedido ($orderIntegration) não localizado.");
        }

        if (empty($order->data_envio)) {
            // request to send tracking to integration
            $urlShipped   = "orders/$orderIntegration";
            $queryShipped = array(
                'json' => array(
                    'status'    => 'shipped',
                    'shipments' => array(
                        array(
                            'id'            => $dataOrderIntegration->shipments[0]->id,
                            'date_shipped'  => $order->data_envio,
                            'status'        => 'shipped'
                        )
                    )
                )
            );

            if (isset($dataOrderIntegration->shipments[0]->documents[0]->id)) {
                $queryShipped['json']['shipments'][0]['documents'][0]['id'] = $dataOrderIntegration->shipments[0]->documents[0]->id;
            }

            try {
                $request = $this->order_v2->request('PUT', $urlShipped, $queryShipped);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }

            $response = Utils::jsonDecode($request->getBody()->getContents());

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'set_in_transit',
                'request'        => json_encode($queryShipped, JSON_UNESCAPED_UNICODE),
                'request_method' => 'PUT',
                'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
                'response_code'  => $request->getStatusCode(),
                'request_uri'    => $urlShipped
            ));
        }

        if (count($dataOccurrence)) {
            // Transformo os dados em objeto para vetor.
            $arrOccurrence = $dataOrderIntegration->shipments[0]->issues;
            $arrOccurrence = Utils::jsonDecode(Utils::jsonEncode($arrOccurrence, JSON_UNESCAPED_UNICODE), true);

            foreach ($dataOccurrence as $occurrence) {
                $arrOccurrence[] = array(
                    "id"            => null,
                    "description"   => $occurrence['name'],
                    "date"          => $occurrence['date']
                );
            }

            // request to send occurrence to integration
            $urlImportOccurrence   = "orders/$orderIntegration";
            $queryImportOccurrence = array(
                'json' => array(
                    'shipments' => array(
                        array(
                            'id'        => $dataOrderIntegration->shipments[0]->id,
                            'issues'    => $arrOccurrence
                        )
                    )
                )
            );

            try {
                $request = $this->order_v2->request('PUT', $urlImportOccurrence, $queryImportOccurrence);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }

            $response = Utils::jsonDecode($request->getBody()->getContents());

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'set_occurrence',
                'request'        => json_encode($queryImportOccurrence, JSON_UNESCAPED_UNICODE),
                'request_method' => 'PUT',
                'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
                'response_code'  => $request->getStatusCode(),
                'request_uri'    => $urlImportOccurrence
            ));
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
            $dataOrder = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // Pedido está entregue, mas não tem data, pego a data atual para atualizar o pedido e não ficar travado.
        if ($dataOrder->status === 'delivered' && empty($dataOrder->shipments[0]->date_delivered)) {
            $date = dateFormat($dataOrder->modified, DATETIME_INTERNATIONAL, null);
        } else {
            if (empty($dataOrder->shipments[0]->date_delivered)) {
                throw new InvalidArgumentException("Pedido ($orderIntegration) sem data de entrega.");
            }
            $date = dateFormat($dataOrder->shipments[0]->date_delivered, DATETIME_INTERNATIONAL, null);
        }

        return $date;
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
        try {
            $dataOrderIntegration = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$dataOrderIntegration) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        if (!property_exists($dataOrderIntegration, 'shipments') || empty($dataOrderIntegration->shipments)) {
            throw new InvalidArgumentException("Dados de entrega do pedido ($orderIntegration) não localizado.");
        }

        // request to send tracking to integration
        $urlDelivered   = "orders/$orderIntegration";
        $queryDelivered = array(
            'json' => array(
                'status'    => 'delivered',
                'shipments' => array(
                    array(
                        'id'            => $dataOrderIntegration->shipments[0]->id,
                        'date_delivered'  => $order->shipping->delivered_date,
                        'status'        => 'delivered'
                    )
                )
            )
        );

        if (isset($dataOrderIntegration->shipments[0]->documents[0]->id)) {
            $queryDelivered['json']['shipments'][0]['documents'][0]['id'] = $dataOrderIntegration->shipments[0]->documents[0]->id;
        }

        try {
            $request = $this->order_v2->request('PUT', $urlDelivered, $queryDelivered);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_delivered',
            'request'        => json_encode($queryDelivered, JSON_UNESCAPED_UNICODE),
            'request_method' => 'PUT',
            'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlDelivered
        ));

        return true;
    }

    /**
     * Envia o pedido para shipping_error e um log de do motivo do erro.
     *
     * @param   string|array    $messages   Mensagem de log.
     * @throws  InvalidArgumentException
     */
    public function setShippingError($messages)
    {
        $urlShippingErrorLog = "orders/$this->orderIdIntegration";
        $queryShippingError  = array(
            'json' => array('status' => 'shipping_error')
        );
        $queryErrorIntegration = array(
            'json' => array('log_history' => array())
        );
        if (!is_array($messages)) {
            $messages = array($messages);
        }
        foreach ($messages as $message) {
            $queryErrorIntegration['json']['log_history'][] = array(
                'message'   => $message,
                'date'      => dateFormat(dateNow()->format(DATETIME_INTERNATIONAL), DATETIME_INTERNATIONAL_TIMEZONE, null)
            );
        }

        try {
            $this->order_v2->request('PUT', $urlShippingErrorLog, $queryShippingError);
            $this->order_v2->request('PUT', $urlShippingErrorLog, $queryErrorIntegration);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }
    }
}