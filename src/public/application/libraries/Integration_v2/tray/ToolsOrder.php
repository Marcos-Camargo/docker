<?php

namespace Integration\tray;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Integration\Integration_v2\Order_v2;
use League\Csv\InvalidArgument;


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

        try {
            
            if ($this->order_v2->sellerCenter === 'conectala') {
                $nome           = $shipping_address->full_name;
            }
            else {
                $nome           = $billing_address->full_name;
            }

            $paymentType = $payment->parcels[0]->payment_type ?? '';

            $quantidadeParcelas = $payment->parcels[0]->parcel ?? '';

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
                likeText("%cash%", strtolower($paymentType))
            ) {
                $paymentValid = 'transfer';
            }else if (
                     likeText("%pix%", strtolower($paymentType))  ||
                     likeText("%instantPayment%", strtolower($paymentType))){
                $paymentValid = 'pix';
            } else {
                $paymentValid = 'credit';
            }

            $statusEs = $this->getStatus('AGUARDANDO PAGAMENTO');

            $newOrder = array(
                'discount'      => $order->payments->discount,
                'point_sale'    => strtoupper($order->system_marketplace_code),
                'session_id'    => null,
                "status_id"     => $statusEs,
                'shipment'      => $order->shipping->shipping_carrier." - ".$order->shipping->service_method,
                'shipment_value'=> $order->shipping->seller_shipping_cost,
                'payment_form'  => $paymentValid,
                'installment'   => $quantidadeParcelas, //enviando qtd parcelas
                'store_note'    => "Pedido no Marketplace ".$this->order_v2->nameSellerCenter.": ".strtoupper($order->marketplace_number)." (".$order->code.")",
                'Customer'      => array(
                    'type'      => $customer->person_type === 'pf' ? "0" : "1", 
                    'name'      => $nome,
                    // 'cpf'       => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                    'email'     => $customer->email,
                    'rg'        => $customer->rg,
                    'gender'    => null,
                    'phone'     => onlyNumbers($customer->phones[0] ?? $customer->phones[1] ?? ''),
                    'birth_date'=> '1980-01-01',
                    'CustomerAddress' => array(
                        'address'     => $shipping_address->street,
                        'zip_code'    => $shipping_address->postcode,
                        'number'      => $shipping_address->number,
                        'complement'  => $shipping_address->complement,
                        'neighborhood'=> $shipping_address->neighborhood,
                        'city'        => $shipping_address->city,
                        'state'       => $shipping_address->region,
                        'country'     => 'BRA',                    
                        'type'        => '1'
                    )
                ),
                'ProductsSold'    => array(),
                'MarketplaceOrder'=> array( 
                    'MarketplaceOrder' => array(               
                        'marketplace_name'            => $this->order_v2->sellerCenter ?? strtoupper($order->system_marketplace_code), 
                        'marketplace_seller_name'     => strtoupper($order->system_marketplace_code),
                        'marketplace_seller_id'       => strtoupper($order->marketplace_number),
                        'marketplace_document'        => cnpj(onlyNumbers($this->order_v2->dataStore['CNPJ'])), //CNPJ do marketplace
                        'payment_responsible_document'=> null,
                        'marketplace_order_id'        => $order->code,
                        'marketplace_shipping_id'     => null, 
                        'marketplace_shipping_type'   => null,
                        'marketplace_internal_status' => in_array($order->status->code, array(1,2,96)) ? 'PENDING' : 'PAID'
                    )
                )
            );
            if ($customer->person_type === 'pf') {
                $newOrder['Customer']['cpf'] = onlyNumbers($customer->cpf);
            }else {
                $newOrder['Customer']['cnpj'] = onlyNumbers($customer->cnpj);
            }

            $customer_email = $this->getCustomers($newOrder);
            if(!empty($customer_email)){
                
                $customer_email = $customer_email[0]['Customer']['email'];
                
                $newOrder['Customer']['email'] = $customer_email;
            }

            foreach ($order->items as $item) {
                $variation_id_erp = null;
                $product_id_erp = null;
                if($item->sku_variation){
                    $variation = $this->order_v2->model_products->getVariationIdErpForSkuAndSkuVarAndStore($item->sku, $item->sku_variation,$this->order_v2->store);
                    $variation_id_erp = $variation['variant_id_erp'];
                    $product_id_erp = $variation['product_id_erp'];               
                }else{
                    $variation_id_erp = '';
                    $product_id_erp = $item->sku_integration;
                }
                $itemPrd = array(
                    'product_id'    => trim($product_id_erp),
                    'variant_id'    => trim($variation_id_erp),
                    'price'         => number_format($item->original_price, 2, '.', ''),
                    'original_price'=> number_format($item->original_price, 2, '.', ''),
                    'quantity'      => $item->qty
                );
                $newOrder['ProductsSold'][] = $itemPrd;
            }

            $urlOrder = "/orders";
            $queryOrder = array('json' => $newOrder);
            $errorIntegration = null;
            $order_id_integration = null;
            $contentOrder = (object) null;
            try {
                $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
                $contentOrder = Utils::jsonDecode($request->getBody()->getContents());
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                $error_message = $exception->getMessage();
                $errorIntegration = json_decode($error_message, True);
                if ($errorIntegration["code"] == 400) {
                    if (isset($errorIntegration["causes"]["MarketplaceOrder"]["marketplace_order_id"][0])) {
                        $marketplace_order_id = $errorIntegration["causes"]["MarketplaceOrder"]["marketplace_order_id"][0];
                        if (strpos($marketplace_order_id, 'MarketplaceOrder duplicate entry for order')!== false) {
                            $order_id_integration = (int)filter_var($marketplace_order_id, FILTER_SANITIZE_NUMBER_INT);
                            $contentOrder->order_id = $order_id_integration;
                        }
                    }
                    elseif (isset($errorIntegration["causes"]["Customer"]["cpf"][0])) {
                        $this->order_v2->log_integration("Erro para integrar o pedido ($order->code)", "<h4>Não foi possível integrar o pedido $order->code</h4> <ul><li>CPF do Cliente já consta na loja, mas está vinculado com email diferente. Seller precisa acionar Suporte Tray para liberar cadastro de pedido para cliente já existente no ERP.</li></ul>", "E");
                        throw new InvalidArgumentException("CPF do Cliente já consta na loja, mas está vinculado com email diferente. Seller precisa acionar Suporte Tray para liberar cadastro de pedido para cliente já existente no ERP.");
                    }
                    elseif (isset($errorIntegration["causes"]["Customer"]["cnpj"][0])) {
                        $this->order_v2->log_integration("Erro para integrar o pedido ($order->code)", "<h4>Não foi possível integrar o pedido $order->code</h4> <ul><li>CNPJ do Cliente já consta na loja, mas está vinculado com email diferente. Seller precisa acionar Suporte Tray para liberar cadastro de pedido para cliente PJ já existente no ERP.</li></ul>", "E");
                        throw new InvalidArgumentException("CNPJ do Cliente já consta na loja, mas está vinculado com email diferente. Seller precisa acionar Suporte Tray para liberar cadastro de pedido para cliente PJ já existente no ERP.");
                    }
                    else if (!empty($errorIntegration["causes"])) {
                        $error_message =  json_encode($errorIntegration["causes"], JSON_UNESCAPED_UNICODE);
                        $this->order_v2->log_integration("Pedido ($order->code) não integrado. Erro para integrar o pedido ", "<h4>Não foi possível integrar o pedido $order->code</h4> <ul><li>$error_message</li></ul>", "E");
                        throw new InvalidArgumentException("Não foi possível integrar o pedido $order->code. $error_message");
                    }
                } else {
                    $this->order_v2->log_integration("Pedido ($order->code) não integrado. Erro para integrar o pedido ", "<h4>Não foi possível integrar o pedido $order->code</h4> <ul><li>$error_message</li></ul>", "E");
                    throw new InvalidArgumentException("Não foi possível integrar o pedido $order->code. $error_message");
                }
            }
                
            if (!isset($contentOrder->order_id)) {
                $this->order_v2->log_integration("Erro para integrar o pedido ($order->code)", "<h4>Não foi possível integrar o pedido $order->code</h4> <ul><li>Código de identificação do pedido não localizado no retorno.</li></ul>", "E");
                throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora no pedido $order->code.");
            }

            $this->orderIdIntegration = $contentOrder->order_id;

            if(!in_array($order->status->code, array(1,2,96))){
                try {
                    $this->setPayment($order);
                } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                    throw new InvalidArgumentException("Não foi possível cadastrar os dados de pagamento do Pedido ".$this->orderId."  na integradora. {$exception->getMessage()}");
                }

                try {
                    $this->setApprovePayment($this->orderId,$this->orderIdIntegration);
                } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }
            }
        }catch (ClientException | InvalidArgumentException | GuzzleException $exception){
            throw new InvalidArgumentException("Pedido não integrado."."{$exception->getMessage()}");
        }

        return array(
            'id'        => $contentOrder->order_id,
            'request'   => "$urlOrder\n" . Utils::jsonEncode($queryOrder, JSON_UNESCAPED_UNICODE)
        );
    }

     /**
     * Envia dados pagamento do pedido inserido.
     */
    private function setPayment(object $order)
    {
        $this->orderId      = $order->code;
        
        $urlApprovePayment      = "/payments";
        $queryApprovePayment    = array(
            'json' => array(
                    'order_id'=> $this->orderIdIntegration, 
                    'method'  => $order->payments->parcel[0]->payment_method ?? "Pagamento com cartão", 
                    'value'   => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
                    'date'    => date('Y-m-d', strtotime($order->payments->date_payment ?? $order->created_at)),
                    'note'    => "pagamento realizado com sucesso"
            )
        );

        try {
            $this->order_v2->request('POST', $urlApprovePayment, $queryApprovePayment);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possível informar os dados de pagamento do pedido {$order->code}. {$exception->getMessage()}");
        }
    }

    /**
     * Aprovar pagamento do pedido inserido.
     *
     * @param   int     $order              Código do pedido no Seller Center.
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  bool Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setApprovePayment(int $order, string $orderIntegration): bool
    {

        try {
            $orderObj = $this->order_v2->getOrder($order);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não encontrou dados para o pedido (".$order.").");
        }

        $statusEs = $this->getStatus('A ENVIAR');

        if (!isset($statusEs)) {
            throw new InvalidArgumentException("Não foi possível recuperar o ID do Status 'PAGO'.");
        }

        $urlApprovePayment      = "orders/{$orderIntegration}";
        $queryApprovePayment    = array(
            'json' => array(
                    "status_id"     => $statusEs, //Pago | 124111 - A ENVIAR | 2
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlApprovePayment, $queryApprovePayment);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possível aprovar o pagamento do pedido {$order}. {$exception->getMessage()}");
        }

        $this->order_v2->log_integration("Pedido ({$this->orderId }) Atualizado com sucesso. ", "<h4>Pagamento do pedido  {$this->orderId} informado com sucesso na integradora.</h4> <ul><li>Dados de pagamento do pedido  {$this->orderId} informado com sucesso na integradora.</li></ul>", "S");
        return true;
    }

    public function getStatus(string $status, int $page = 1, int $size = 100)
    {
        $query['page'] = $page;
        $query['limit'] = $size;
        $query['default'] = '1';
        $query['status'] = $status;

        try {
            $request = $this->order_v2->request('GET', "orders/statuses", ['query' => $query]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $statusResponse = Utils::jsonDecode($request->getBody()->getContents());
        $statusResponse = $statusResponse->OrderStatuses[0]->OrderStatus;

        if (!isset($statusResponse->id)) {
            throw new InvalidArgumentException('Status do pedido não localizado.');
        }

        if($statusResponse->status == $status){
            return $statusResponse->id;
        } else{
            throw new InvalidArgumentException('Não foi possível localizar o id do Status.');
        }
    }

    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int      $order  Código do pedido na integradora.
     * @return  array|object            Dados do pedido na integradora.
     */
    public function getOrderIntegration($order)
    {
        if (isset($this->ordersIntegration[$order])) {
            return $this->ordersIntegration[$order];
        }

        try {
            $request = $this->order_v2->request('GET', "orders/{$order}/complete");
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $orderResponse = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($orderResponse->Order)) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        $orderResponse = $orderResponse->Order;
        if (!isset($orderResponse->id)) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        $this->ordersIntegration[$order] = $orderResponse;

        return $orderResponse;
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

        if (isset($this->ordersIntegration[$orderIdIntegration])) {
            return $this->ordersIntegration[$orderIdIntegration];
        }

        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
            $internalOrder = $this->order_v2->getOrder($orderid);
            if ($order->status == 'AGUARDANDO PAGAMENTO') {
                $this->setApprovePayment($orderid, $orderIdIntegration);
                $this->setPayment($internalOrder);
            }
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIdIntegration) não localizado.");
        }
        $isDelivered = $order->delivery_date;
        $order = $order->id;
        
        $request = $this->order_v2->request('GET', "orders/{$order}/invoices");
        
        $invoiceResponse = Utils::jsonDecode($request->getBody()->getContents());
        $invoiceResponse = $invoiceResponse->OrderInvoices[0]->OrderInvoice;

        return [
            'date' => $invoiceResponse->issue_date,
            'value' => roundDecimal($invoiceResponse->value),
            'serie' => (int)$invoiceResponse->serie,
            'number' => (int)clearBlanks($invoiceResponse->number),
            'key' => clearBlanks($invoiceResponse->key),
            'link' => $invoiceResponse->link ?? '',
            'isDelivered'  => $isDelivered ?? null
        ];
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
        try {
            $request = $this->order_v2->request('PUT', "orders/cancel/{$orderIntegration}");
            $request = Utils::jsonDecode($request->getBody()->getContents(), true);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // throw new InvalidArgumentException($exception->getMessage());
            $errorIntegration = json_decode($exception->getMessage(), True);
            if($errorIntegration["code"] == 400){
                if (isset($errorIntegration["causes"])) {
                    $order_cancel = $errorIntegration["causes"];
                    if (strpos($order_cancel[0], "Unable to cancel the order because its status does not allow changes.") !== false) {
                        $this->order_v2->log_integration("Pedido ({$order}) atualizado", "<h4>Pedido {$order} atualizado para cancelado no integrador com sucesso.</h4>", "S");
                        $this->order_v2->removeOrderQueue($this->order_v2->toolsOrder->orderId);
                    }
                    elseif (strpos($order_cancel[0], "Order already canceled") !== false) {
                        $this->order_v2->log_integration("Pedido ({$order}) atualizado", "<h4>Pedido  {$order} atualizado para cancelado no integrador com sucesso.</h4>", "S");
                        $this->order_v2->removeOrderQueue($this->order_v2->toolsOrder->orderId);
                    }
                }
            }
        }

        if ($request['code'] != 200 && $request['code'] != 201) {
            $contentOrder = Utils::jsonDecode($request->getBody()->getContents());
            throw new InvalidArgumentException(Utils::jsonEncode($contentOrder));
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

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        $itemsTracking = [];

        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = array( 
                'quantity'                  => $item->qty,
                'shippingCompany'           => $order->shipment,
                'trackingCode'              => $order->sending_code,
                'trackingUrl'               => $order->tracking_url,
                'generatedDate'             => date(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => $order->shipment,
                'shippingMethodCode'        => $order->shipment, // Se não exitir informar o mesmo que o shippingMethodName
                'deliveryValue'             => 0,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => null,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null,
                'isDelivered'               => $order->delivery_date ?? null

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
        $statusEs = $this->getStatus('A ENVIAR');

        if (!isset($statusEs)) {
            throw new InvalidArgumentException("Não foi possível recuperar o ID do Status 'A ENVIAR'.");
        }

        $urlTrackingCodeOrder     = "/orders/$orderIntegration";
        $queryTrackingCodeOrder   = array(
            'json' => array(
                'Order' => array(
                    "status_id"     => $statusEs, //Em Separação 124345 | A enviar 2
                    "shipment"      => $dataTracking->ship_company,
                    "shipment_value"=> $order->shipping->seller_shipping_cost,
                    "sending_code"  => $dataTracking->tracking->tracking_code[0],
                    "sending_date"  => date('Y-m-d', strtotime($order->shipping->shipped_date)) ?? null,
                    "tracking_url"  => $dataTracking->tracking->tracking_url
                ) 
            )
        );

        try {
            $this->order_v2->request('PUT', $urlTrackingCodeOrder, $queryTrackingCodeOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Ocorreu um erro ao enviar o rastreamento. - ".$exception->getMessage());
        }

        return true; 
    }

     /**
     * Recupera data de envio do pedido
     * @param string $orderIntegration Código do pedido na integradora
     * @return  array                      Data de envio do pedido
     * @throws  InvalidArgumentException
     *
     */
    public function getShippedIntegration(string $orderIntegration)
    {
        $order = $this->getOrderIntegration($orderIntegration);
        $shippedDate = $order->sending_date ?? '';

        $shippedDate = array(
            'isDelivered' => $order->delivery_date ?? null,
            'date' => $shippedDate ? dateFormat($shippedDate, DATETIME_INTERNATIONAL, null) : null
        );
        return $shippedDate;
        
        //return dateFormat($shippedDate, DATETIME_INTERNATIONAL, null);
    }

    /**
     * Importar a data de envio
     * @param string $orderIntegration Código do pedido na integradora
     * @param object $order Dado completo do pedido (Api/V1/Order/{order})
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     *
     */
    public function setShippedIntegration(string $orderIntegration, object $order): bool
    {
        $statusEs = $this->getStatus('ENVIADO');

        if (!isset($statusEs)) {
            throw new InvalidArgumentException("Não foi possível recuperar o ID do Status 'A ENVIAR'.");
        }

        $urlTrackingCodeOrder     = "/orders/$orderIntegration";
        $queryTrackingCodeOrder   = array(
            'json' => array(
                'Order' => array(
                    "status_id"     => $statusEs, //Pedido Enviado 124241 | Enviado 3
                    "sending_date"  => date('Y-m-d', strtotime($order->shipping->shipped_date ?? date('Y-m-d'))),
                ) 
            )
        );

        try {
            $this->order_v2->request('PUT', $urlTrackingCodeOrder, $queryTrackingCodeOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Ocorreu um erro ao enviar a data de envio. - ".$exception->getMessage());
        }

        return true; 
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
        $order = $this->getOrderIntegration($orderIntegration);
        $orderStatus = $order->status ?? '';
        $deliveredDate = isset($order->shipment_date) ?
            date('Y-m-d H:i:s', strtotime($order->shipment_date))
            : null;
        return [
            'isDelivered' => in_array($orderStatus, ['ENTREGUE','FINALIZADO']),
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
        $statusEs = $this->getStatus('ENTREGUE');

        if (!isset($statusEs)) {
            throw new InvalidArgumentException("Não foi possível recuperar o ID do Status 'ENTREGUE'.");
        }

        $urlTrackingCodeOrder     = "/orders/$orderIntegration";
        $queryTrackingCodeOrder   = array(
            'json' => array(
                'Order' => array(
                    "status_id"     => $statusEs, //Entregue 124117 | Finalizado 4
                ) 
            )
        );

        try {
            $this->order_v2->request('PUT', $urlTrackingCodeOrder, $queryTrackingCodeOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Ocorreu um erro ao informar que o pedido foi finalizado. - ".$exception->getMessage());
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
        $order = $this->getOrderIntegration($orderIntegration);
        if ($order->delivered != 1) {
            return '';
        }
        $shippedDate = $order->sending_date ?? '';
        if (empty($shippedDate)) {
            return '';
        }
        
        return dateFormat($shippedDate, DATETIME_INTERNATIONAL, null);
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
        $statusEs = $this->getStatus('ENTREGUE');

        if (!isset($statusEs)) {
            throw new InvalidArgumentException("Não foi possível recuperar o ID do Status 'ENTREGUE'.");
        }

        $urlTrackingCodeOrder     = "/orders/$orderIntegration";
        $queryTrackingCodeOrder   = array(
            'json' => array(
                'Order' => array(
                    "status_id"     => $statusEs, //Entregue 124117 | Finalizado 4
                    "sending_date"  => date('Y-m-d', strtotime($order->shipping->shipped_date ?? date('Y-m-d'))),
                ) 
            )
        );

        try {
            $this->order_v2->request('PUT', $urlTrackingCodeOrder, $queryTrackingCodeOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Ocorreu um erro ao enviar a data de entrega. - ".$exception->getMessage());
        }

        return true; 
    }

    public function getCustomers($newOrder){
        if($newOrder['Customer']['type'] == 0){
            $query['cpf'] = $newOrder['Customer']['cpf'];
        }
        else{
            $query['cnpj'] = $newOrder['Customer']['cnpj'];
        }

        try{
            $request = $this->order_v2->request('GET', "customers", ['query' => $query]);
        }
        catch(ClientException | InvalidArgumentException | GuzzleException $exception){
            throw new InvalidArgumentException($exception->getMessage());
        }

        $request_response = Utils::jsonDecode($request->getBody()->getContents(), true);


        $request_response = $request_response['Customers'];

        return $request_response;
    }


}