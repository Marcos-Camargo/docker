<?php

namespace Integration\lojaintegrada;

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

        $urlOrder = "/v1/integration/sales";
        
        try {
            $status_code = in_array($order->status->code, array(1,2,96)) ? '2' : '3';
            $orderAssemble = $this->assembleOrderIntegration($order,$status_code);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não conseguiu montar o Array do pedido (".$order->code.").");
        }

        $queryOrder = array('json' => $orderAssemble);

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($contentOrder->number)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Código de identificação do pedido não localizado no retorno.</li></ul>", "E");
            throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora.");
        }

        $orderLojaIntegrada = $contentOrder->number."|".$contentOrder->id;
        $this->orderIdIntegration = $orderLojaIntegrada;

        return array(
            'id'        => $orderLojaIntegrada,
            'request'   => "$urlOrder\n" . Utils::jsonEncode($queryOrder, JSON_UNESCAPED_UNICODE)
        );
    }

    public function assembleOrderIntegration(object $order,int $status)
    {
        if (!isset($status)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Status não definido.</li></ul>", "E");
            throw new InvalidArgumentException("Não foi possível identificar o Status.");
        }

        $shipping_address   = $order->shipping->shipping_address;
        $billing_address    = $order->billing_address;
        $customer           = $order->customer;
        $this->orderId      = $order->code;

        if ($this->order_v2->sellerCenter === 'conectala') {
            $nome           = $shipping_address->full_name;
            $nome_fantasia  = $shipping_address->full_name;
        }
        else {
            $nome           = $billing_address->full_name;
            $nome_fantasia  = $billing_address->full_name;        
        }

        $newOrder = array(
            'buyer'            => array(
                'name'         => $nome,
                'email'        => $customer->email,
                'document'     => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'external_id'  => $customer->id,
                'phone'        => $shipping_address->phone,
                'type'         => $customer->person_type === 'pf' ? "CPF" : "CNPJ",
                'cellPhone'    => $shipping_address->phone
            ),
            'shipping'    => array(
                'address' => array(
                    'name'       => 'Residencial',
                    'address'    => $shipping_address->street." - ".$shipping_address->number,
                    'country'    => 'BR',
                    'complement' => $shipping_address->complement,
                    'street'     => $shipping_address->street,
                    'district'   => $shipping_address->neighborhood,
                    'city'       => $shipping_address->city,
                    'state'      => $shipping_address->region,
                    'zipcode'    => $shipping_address->postcode,
                    'number'     => $shipping_address->number
                ),
                'option'         => $order->shipping->shipping_carrier." - ".$order->shipping->service_method,
            ),
            'amount'         => array(
                'discount'   => 0,
                'freight'    => $order->shipping->seller_shipping_cost,
                'fees'       => 0,
                'total'      => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
                'gross'      => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount
            ),
            'items'          => array(),
            'info'           => array(
                'status'        => $status, 
                'marketPlaceId' => strtoupper($order->marketplace_number), 
                'reference'     => $this->order_v2->sellerCenter.'/'.strtoupper($order->system_marketplace_code),
                'comment'       => null 
            ),
            'integration_data'  => array(
                'integrator'    => $this->order_v2->sellerCenter, 
                'marketplace'   => strtoupper($order->system_marketplace_code),
                'external_id'   => $order->code
            )
        );

        foreach ($order->items as $item) {
            $itemPrd = array(
                'product_id'    => trim($item->sku_integration ?? $item->sku_variation ?? $item->sku),
                'quantity'      => $item->qty,
                'unit_value'    => number_format($item->original_price - $item->discount, 2, '.', ''),
                'line_value'    => number_format($item->total_price, 2, '.', '')
            );
            $newOrder['items'][] = $itemPrd;
        }

        return $newOrder;
    }

    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int      $order  Código do pedido na integradora.
     * @return  array|object            Dados do pedido na integradora.
     */
    public function getOrderIntegration($order)
    {
        $urlOrder = "/v1/pedido/$order";
        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $order = Utils::jsonDecode($request->getBody()->getContents());

        if (!property_exists($order, 'numero')) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        return $order;
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
        // Obter dados do pedido
        $orderIntegration = explode("|", $orderIdIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora, do pedido ({$orderid}), não esta no formato esperado (number|id).");
        }
        try {
            $order = $this->getOrderIntegration($orderIntegration[0]);
            if (in_array($order->situacao->codigo, ["aguardando_pagamento", "pagamento_em_analise"])) {  
                $this->setApprovePayment($orderid, $orderIdIntegration);
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        throw new InvalidArgumentException('Integradora sem módulo de Nota Fiscal');
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora, do pedido ({$order}), não esta no formato esperado (number|id).");
        }

        try {
            $orderObj = $this->order_v2->getOrder($order);
            $orderAssemble = $this->assembleOrderIntegration($orderObj,4);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não encontrou dados para o pedido (".$order.").");
        }
        
        $urlApprovePayment     = "/v1/integration/sales/$orderIntegration[1]";
        $queryApprovePayment = array('json' => $orderAssemble);

        try {
            $this->order_v2->request('PUT', $urlApprovePayment, $queryApprovePayment);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possível aprovar o pagamento do pedido {$order}. {$exception->getMessage()}");
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora, do pedido ({$order}), não esta no formato esperado (number|id).");
        }

        try {
            $order = $this->order_v2->getOrder($order);
            $orderAssemble = $this->assembleOrderIntegration($order,8);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não encontrou dados para o pedido (".$order.").");
        }
        
         
        $urlCancelOrder     = "/v1/integration/sales/$orderIntegration[1]";
        $queryCancelOrder = array('json' => $orderAssemble);

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
        $itemsTracking = array();
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora (".$orderIntegration.") não esta no formato esperado (number|id).");
        }
        
        try {
            $order = $this->getOrderIntegration($orderIntegration[0]);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido (".$orderIntegration[0].") não localizado.");
        }
        $traking = $order->envios[0];

        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => $traking->forma_envio[0]->nome,
                'trackingCode'              => $traking->objeto,
                'trackingUrl'               => '',
                'generatedDate'             => date(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => $traking->forma_envio[0]->nome,
                'shippingMethodCode'        => $traking->forma_envio[0]->code, // Se não exitir informar o mesmo que o shippingMethodName
                'deliveryValue'             => $traking->valor,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => null,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null,
                'isDelivered'               => $order->situacao->codigo === 'pedido_entregue' ? true : null
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora, do pedido (".$order->code."), não esta no formato esperado (number|id).");
        }

        try {
            $orderInt = $this->getOrderIntegration($orderIntegration[0]);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $idTracking = $orderInt->envios[0]->id;
        if (!$idTracking) {
            throw new InvalidArgumentException("Id do Envio do pedido (".$order->code.") não localizado, não foi possível atualizar o código de rastreio.");
        }
        
        $urlTrackingCodeOrder     = "/v1/pedido_envio/$idTracking";
        $queryTrackingCodeOrder   = array(
            'json' => array(
                'objeto' => $dataTracking->tracking->tracking_code[0] ?? null,
            )
        );

        try {
            $this->order_v2->request('PUT', $urlTrackingCodeOrder, $queryTrackingCodeOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $urlOrder     = "/v1/integration/sales/$orderIntegration[1]";
        try {
            $orderAssemble = $this->assembleOrderIntegration($order,15);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não conseguiu montar o Array do pedido para atualizar situação para Em Separação (".$order->code.").");
        }
        $queryOrder = array('json' => $orderAssemble);       

        try {
            $this->order_v2->request('PUT', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora (".$orderIntegration.") não esta no formato esperado (number|id).");
        }
        
        $order = $this->getOrderIntegration($orderIntegration[0]);
        
        $returnData = array(
            'isDelivered'               => $order->situacao->codigo === 'pedido_entregue' ? true : null,
            'date' => $order->envios[0]->data_modificacao ? dateFormat($order->envios[0]->data_modificacao, DATETIME_INTERNATIONAL, null) : null
        );
        return $returnData;       
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora (".$orderIntegration.") não esta no formato esperado (number|id).");
        }

        $order = $this->getOrderIntegration($orderIntegration[0]);
        $orderStatus = $order->situacao->codigo ?? '';
        $deliveredDate = dateFormat($order->envios[0]->data_modificacao, DATETIME_INTERNATIONAL, null) ?? dateNow()->format(DATETIME_INTERNATIONAL);
        return [
            'isDelivered' => in_array($orderStatus, ["pedido_entregue"]),
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora, do pedido (".$order->code."), não esta no formato esperado (number|id).");
        }

        $urlOrder     = "/v1/integration/sales/$orderIntegration[1]";
        try {
            $orderAssemble = $this->assembleOrderIntegration($order,11);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não conseguiu montar o Array do pedido (".$order->code.").");
        }
        $queryOrder = array('json' => $orderAssemble);       

        try {
            $this->order_v2->request('PUT', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora (".$orderIntegration.") não esta no formato esperado (number|id).");
        }
        $order = $this->getOrderIntegration($orderIntegration[0]);

        if (in_array($order->situacao->codigo, ["pedido_entregue"])) {
            if (empty($order->envios[0]->data_modificacao)) {
                return dateNow()->format(DATETIME_INTERNATIONAL);
            }
            return dateFormat($order->envios[0]->data_modificacao, DATETIME_INTERNATIONAL, null);
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
        $orderIntegration = explode("|", $orderIntegration);
        if (count($orderIntegration)<>2) {
            throw new InvalidArgumentException("O Id do Pedido na Integradora, do pedido (".$order->code."), não esta no formato esperado (number|id).");
        }
        $urlOrder     = "/v1/integration/sales/$orderIntegration[1]";

        try {
            $orderAssemble = $this->assembleOrderIntegration($order,14);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não conseguiu montar o Array do pedido (".$order->code.").");
        }

        $queryOrder = array('json' => $orderAssemble);  

        try {
            $this->order_v2->request('PUT', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return true;
    }
}