<?php

namespace Integration\viavarejo_b2b;

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

    protected $ordersIntegration = [];

    /**
     * Instantiate a new Tools instance.
     *
     * @param Order_v2 $order_v2
     */
    public function __construct(Order_v2 $order_v2)
    {
        $this->order_v2 = $order_v2;
    }

    public function getOrderV2(): Order_v2
    {
        return $this->order_v2;
    }

    /**
     * Envia o pedido para a integradora.
     *
     * @param object $order Dados do pedido para formatação.
     * @return  array           Código do pedido gerado pela integradora e dados da requisição para log.
     */
    public function sendOrderIntegration(object $order): array
    {
        $shipping_address   = $order->shipping->shipping_address;
        $billing_address    = $order->billing_address;
        $customer           = $order->customer;
        $this->orderId      = $order->code;
        $created_at         = $order->created_at;
        $days               = 3;

        if (strtotime(addTimesToDate($created_at, 2, null, 50)) < strtotime(dateNow()->format(DATETIME_INTERNATIONAL))) {
            $messageCancel = "Cancelado por falha na integração de até $days dias.";

            try {
                $this->order_v2->setCancelOrder($this->orderId, dateNow()->format(DATETIME_INTERNATIONAL), $messageCancel);
                $this->order_v2->removeAllOrderIntegration($this->orderId);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException("Não possível realizar o cancelamento do pedido $this->orderId. {$exception->getMessage()}");
            }

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'  => $this->orderId,
                'type'      => 'cancel_order',
                'request'   => null,
                'response'  => json_encode($order, JSON_UNESCAPED_UNICODE)
            ));

            throw new InvalidArgumentException($messageCancel);
        }

        $cnpjIntermediary = $this->order_v2->model_settings->getValueIfAtiveByName('financial_intermediary_cnpj');
        $razaoIntermediary = $this->order_v2->model_settings->getValueIfAtiveByName('financial_intermediary_corporate_name');
        if (empty($cnpjIntermediary) || empty($razaoIntermediary)) {
            throw new InvalidArgumentException('Intermediador de pagamento não configurado!');
        }
        
        $urlOrder = "pedidos";
        $queryOrder = array(
            "produtos" => [],
            "enderecoEntrega" => [
                "cep"           => $shipping_address->postcode,
                "estado"        => $shipping_address->region,
                "logradouro"    => $shipping_address->street,
                "cidade"        => $shipping_address->city,
                "numero"        => !is_numeric($shipping_address->number) ? 0 : (int)$shipping_address->number,
                "referencia"    => strlen($shipping_address->reference) < 4 ? '.....' : substr($shipping_address->reference, 0, 100),
                "bairro"        => $shipping_address->neighborhood,
                "complemento"   => strlen($shipping_address->complement) < 4 ? '.....' : substr($shipping_address->complement, 0, 100),
                "telefone"      => empty(onlyNumbers($customer->phones[0] ?? $customer->phones[1] ?? '')) ? '0000000000' : onlyNumbers($customer->phones[0] ?? $customer->phones[1] ?? ''),
                "telefone2"     => onlyNumbers($customer->phones[1] ?? $customer->phones[0] ?? ''),
                "telefone3"     => ""
            ],
            "destinatario" => [
                "nome"              => $shipping_address->full_name,
                "cpfCnpj"           => $customer->person_type === 'pf' ? cpf(onlyNumbers($customer->cpf)) : cnpj(onlyNumbers($customer->cnpj)),
                "inscricaoEstadual" => empty($customer->ie) ? "ISENTO" : $customer->ie,
                "email" => $customer->email
            ],
            "campanha"                  => $this->order_v2->credentials->campaign,
            "cnpj"                      => cnpj(onlyNumbers($this->order_v2->credentials->cnpj)),
            "pedidoParceiro"            => $order->code,
            "idPedidoMktplc"            => $order->marketplace_number,
            //"senhaAtendimento"          => "",
            //"apolice"                   => "",
            //"administrador"             => 0,
            //"parametrosExtras"          => "",
            "valorFrete"                => $order->shipping->seller_shipping_cost,
            "aguardarConfirmacao"       => true,
            "optantePeloSimples"        => false,
            "possuiPagtoComplementar"   => false,
            /*"pagtosComplementares"      => [
                [
                    "idFormaPagamento" => 0,
                    "dadosCartaoCredito" => [
                        "nome" => "string",
                        "numero" => "string",
                        "codigoVerificador" => "string",
                        "validadeAno" => "string",
                        "validadeMes" => "string",
                        "validadeAnoMes" => "string",
                        "quantidadeParcelas" => 0
                    ],
                    "dadosCartaoCreditoValidacao" => [
                        "nome" => "string",
                        "numeroMascarado" => "string",
                        "qtCaracteresCodigoVerificador" => "string",
                        "validadeAno" => "string",
                        "validadeMes" => "string"
                    ],
                    "valorComplementarComJuros" => 0,
                    "valorComplementar" => 0
                ]
            ],*/
            "dadosEntrega" => [
                //"previsaoDeEntrega"     => $order->shipping->estimated_delivery,
                "valorFrete"            => $order->shipping->seller_shipping_cost,
                //"idEntregaTipo"         => 0,
                //"idEnderecoLojaFisica"  => 0,
                //"idUnidadeNegocio"      => 0
            ],
            /*"enderecoCobranca" => [
                "cep"           => $billing_address->postcode,
                "estado"        => $billing_address->region,
                "logradouro"    => $billing_address->street,
                "cidade"        => $billing_address->city,
                "numero"        => $billing_address->number,
                "referencia"    => "",
                "bairro"        => $billing_address->neighborhood,
                "complemento"   => $billing_address->complement,
                "telefone"      => onlyNumbers($customer->phones[0] ?? $customer->phones[1] ?? ''),
                "telefone2"     => onlyNumbers($customer->phones[1] ?? $customer->phones[0] ?? ''),
                "telefone3"     => ""
            ],*/
            //"valorTotalPedido"               => $order->payments->gross_amount,
            //"valorTotalComplementar"         => 0,
            //"valorTotalComplementarComJuros" => 0,
            "intermediadoresFinanceiros" => [
                [
                    "tipoIntegracaoPagamento"   => 2, // Pagamento não integrado com o sistema de automação da empresa (Ex.: equipamento POS)
                    "valorPagamento"            => $order->payments->gross_amount,
                    "formaPagamento"            => ($order->payments->parcels[0]->parcel ?? 1) > 1 ? 1 : 0, // à vista = 0; a prazo = 1
                    "meioPagamento"             => $this->getMeanOfPayment($order->payments->parcels[0]->payment_type ?? ''),
                    "bandeiraOperadoraCartao"   => $this->getFlagOfPayment($order->payments->parcels[0]->payment_method ?? ''),
                    "cnpjIntermediador"         => cnpj($cnpjIntermediary),
                    "razaoSocialIntermediador"  => $razaoIntermediary,
                    "numAutorizacaoCartao"      => null //"0000000001" // nsu
                ]
            ]
        );

        foreach ($order->items as $item) {
            $queryOrder['produtos'][] = array(
                'codigo'        => trim($item->sku_variation ?? $item->sku),
                "idLojista"     => $this->order_v2->credentials->idLojista,
                "quantidade"    => $item->qty,
                //"premio"        => 0,
                "precoVenda"    => roundDecimal($item->original_price)
            );
        }

        try {
            $request = $this->order_v2->request('POST', $urlOrder, array('json' => $queryOrder));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $messageException = $exception->getMessage();

            // Estado inválido.
            if (likeText('%EstadoInvalido%', $messageException)) {
                $dataZipcodeClient = $this->order_v2->calculofrete->lerCep($queryOrder['enderecoEntrega']['cep']);
                $queryOrder['enderecoEntrega']['estado'] = $dataZipcodeClient['state'] ?? $queryOrder['enderecoEntrega']['estado'];
                try {
                    $request = $this->order_v2->request('POST', $urlOrder, array('json' => $queryOrder));
                    $messageException = null;
                } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                    $messageException = $exception->getMessage();
                }
            }

            // Inscriação estadual inválida.
            if (likeText('%InscricaoEstadualInvalida%', $messageException)) {
                $queryOrder['destinatario']['inscricaoEstadual'] = 'ISENTO';
                try {
                    $request = $this->order_v2->request('POST', $urlOrder, array('json' => $queryOrder));
                    $messageException = null;
                } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                    $messageException = $exception->getMessage();
                }
            }

            if ($messageException !== null) {
                try {
                    $dataMessage = Utils::jsonDecode($messageException);
                } catch (Exception $exception) {
                }

                $messageError = $dataMessage->error->message ?? $messageException;

                $this->order_v2->log_integration(
                    "Erro para integrar o pedido ($this->orderId)",
                    "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>$messageError</li></ul>",
                    "E"
                );

                if (likeText('%PedidoDuplicado%', $messageError)) {
                    $orderIdIntegration = (int)preg_replace('/[^0-9]/', '', $messageError);
                    if (($orderIdIntegration ?? 0) > 0) {
                        return [
                            'id' => $orderIdIntegration
                        ];
                    }
                }

                throw new InvalidArgumentException($messageError);
            }
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());

        $this->orderIdIntegration = $contentOrder->data->codigoPedido;

        return array(
            'id' => $this->orderIdIntegration,
            'request' => "$urlOrder\n" . Utils::jsonEncode($queryOrder, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Informa a confirmação do pedido.
     *
     * @param   object  $order  Dados do pedido.
     * @param   string  $orderIdIntegration  Dados do pedido da integradora.
     */
    public function confirmOrder(object $order, string $orderIdIntegration): bool
    {
        if ($this->checkOrderCanceled()) {
            return true;
        }
        // Pedido já foi confirmado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'confirm_payment'))) {
            return true;
        }

        $urlOrder = "pedidos/$orderIdIntegration";
        $queryOrder = array(
            "idCampanha"            => $this->order_v2->credentials->campaign,
            "idPedidoParceiro"      => $order->code,
            "confirmado"            => true,
            "idPedidoMktplc"        => $order->marketplace_number,
            //"cancelado"             => false,
            //"motivoCancelamento"    => "",
            //"parceiro"              => ""
        );

        try {
            $request = $this->order_v2->request('PATCH', $urlOrder, array('json' => $queryOrder));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = $exception->getMessage();
            if (
                likeText('%PedidoComStatusInvalido%', $message)
            ) {
                return $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'  => $this->orderId,
                    'type'      => 'confirm_payment',
                    'request'   => json_encode($queryOrder, JSON_UNESCAPED_UNICODE),
                    'response'  => $message
                ));
            } else if (
                likeText('%PedidoComStatusInvalido%', $message)
            ) {
                return $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'  => $this->orderId,
                    'type'      => 'confirm_payment',
                    'request'   => json_encode($queryOrder, JSON_UNESCAPED_UNICODE),
                    'response'  => $message
                ));
            } else {
                throw new InvalidArgumentException($message);
            }
        }

        return $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $this->orderId,
            'type'      => 'confirm_payment',
            'request'   => json_encode($queryOrder, JSON_UNESCAPED_UNICODE),
            'response'  => $request->getBody()->getContents()
        ));
    }

     /**
     * Recupera dados do pedido na integradora
     *
     * @return  array|object            Dados do pedido na integradora
     */
    public function getOrderIntegration()
    {
        $order = $this->order_v2->getNumeroMarketplaceByOrderId($this->order_v2->unique_id);
        if (isset($this->ordersIntegration[$this->order_v2->unique_id])) {
            return $this->ordersIntegration[$this->order_v2->unique_id];
        }

        try {
            $options = array(
                "query" =>[
                    "request.cnpj"           => cnpj(onlyNumbers($this->order_v2->credentials->cnpj)),
                    "request.idCampanha"     => $this->order_v2->credentials->campaign,
                    "request.idPedidoMktplc" => $order
                ]
            );

            $request = $this->order_v2->request('GET', "pedidos/$this->orderIdIntegration", $options);
            $orderResponse = Utils::jsonDecode($request->getBody()->getContents());

        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!isset($orderResponse->data)) {
            throw new InvalidArgumentException('Pedido não localizado');
        }


        $orderResponse = $orderResponse->data;
        if (!isset($orderResponse->pedido)) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        $orderDb = $this->order_v2->model_orders->getOrdersData(0, $this->orderId);

        $deliveries = $orderResponse->entregas;
        if ($deliveries){
            $lastDelivery = end($deliveries);
            if ($lastDelivery->trackingEntrega){
                $lastTracking = end($lastDelivery->trackingEntrega);

                $updates = [];

                if (in_array($lastTracking->codDescricao, ['AXD', 'ARE'])){

                    if ($lastTracking->codDescricao != $orderDb['status_integration']){
                        $updates['status_integration'] = $lastTracking->codDescricao;
                        $updates['status_integration_description'] = $lastTracking->descricao;
                    }

                }elseif ($orderDb['status_integration']){
                    $updates['status_integration'] = null;
                    $updates['status_integration_description'] = null;
                }

                if ($updates){
                    $this->order_v2->model_orders->updateOrderById($this->orderId, $updates);
                }

            }
        }

        $this->ordersIntegration[$this->order_v2->unique_id] = $orderResponse;

        return $orderResponse;
    }

    /**
     * Recupera dados da nota fiscal do pedido
     *
     * @param string $orderIdIntegration Dados do pedido da integradora
     * @return  array                       Dados de nota fiscal do pedido [date, value, serie, number, key]
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderid): array
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado.');
        }

        $order = $this->getOrderIntegration();

        // Obter dados do pedido
        try {
            $orderObj = $this->order_v2->getOrder($orderid);
            $this->confirmOrder($orderObj,$orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $shipments = $order->entregas ?? null;
        $shipment = !empty($shipments) ? current($shipments) : (object)[];

        if (ENVIRONMENT === 'development') {
            //$shipment->chaveAcesso = '35220237189889000379550110011602681202204378';
        }

        return [
            'date' => $shipment->dataEmissaoNotaFiscal,
            'value' => roundDecimal($order->pedido->valorTotalPedido),
            'serie' => (int)$shipment->serieNotaFiscal,
            'number' => (int)clearBlanks($shipment->idNotaFiscal),
            'key' => clearBlanks($shipment->chaveAcesso),
            'link' => $shipment->linkNotaFiscalPDF ?? '',
            'isDelivered' => $shipment->dataEntrega ?? null
        ];
    }

    /**
     * Recupera dados de tracking
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   array   $items              Itens do pedido
     * @return  array                       Array onde, a chave de cada item do array será o código SKU e o valor do item um array com os dados: quantity, shippingCompany, trackingCode, trackingUrl, generatedDate, shippingMethodName, shippingMethodCode, deliveryValue, documentShippingCompany, estimatedDeliveryDate, labelA4Url, labelThermalUrl, labelZplUrl, labelPlpUrl
     * @throws  InvalidArgumentException
     */
    public function getTrackingIntegration(string $orderIntegration, array $items): array
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado.');
        }

        $order = $this->getOrderIntegration();

        $itemsTracking = [];

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        $shipments = $order->entregas ?? null;
        $shipment = !empty($shipments) ? current($shipments) : (object)[];

        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = [
                'quantity' => $item->qty,
                'shippingCompany' => $shipment->nomeTransportadora,
                'trackingCode' => $shipment->idNotaFiscal,
                'trackingUrl' => null,
                'generatedDate' => date(DATETIME_INTERNATIONAL),
                'shippingMethodName' => 'Normal',
                'shippingMethodCode' => 'normal',
                'deliveryValue' => 0,
                'documentShippingCompany' => null,
                'estimatedDeliveryDate' => null,
                'labelA4Url' => null,
                'labelThermalUrl' => null,
                'labelZplUrl' => null,
                'labelPlpUrl' => null,
                'isDelivered' => $shipment->dataEntrega ?? null
            ];
        }

        return $itemsTracking;
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
        if ($this->checkOrderCanceled()) {
            return [];
        }

        $order = $this->getOrderIntegration();
        $shipments = $order->entregas ?? null;
        $shipment = !empty($shipments) ? current($shipments) : (object)[];
        $tracking = $shipment->trackingEntrega ?? [];
        $tracking = !empty($tracking) ? current($tracking) : (object)[];
        $shippedDate = $tracking->data ?? '';
        
        $shippedDate = array(
            'isDelivered' => $order->entregas[0]->dataEntrega ?? null,
            'date'        => $shippedDate ? date('Y-m-d H:i:s', strtotime($shippedDate)) : null
        );
        return $shippedDate;
        //return date('Y-m-d H:i:s', strtotime($shippedDate));
    }

    /**
     * Recupera ocorrências do rastreio
     *
     * @param   string $orderIntegration    Código do pedido na integradora
     * @return  array                       Dados de ocorrência do pedido. Um array com as chaves 'isDelivered' e 'occurrences', onde 'occurrences' será os dados de ocorrência do rastreio, composto pelos índices 'date' e 'occurrence'
     * @throws  InvalidArgumentException
     */
    public function getOccurrenceIntegration(string $orderIntegration): array
    {
        if ($this->checkOrderCanceled()) {
            return [
                'isDelivered'   => false,
                'dateDelivered' => null,
                'occurrences'   => []
            ];
        }

        $order = $this->getOrderIntegration();
        $shipments = $order->entregas ?? null;
        $shipment = !empty($shipments) ? current($shipments) : (object)[];
        $deliveredDate = isset($shipment->dataEntrega) && !empty($shipment->dataEntrega) ?
            date('Y-m-d H:i:s', strtotime(dateTimeBrazilToDateInternational(strlen($shipment->dataEntrega) === 16 ? $shipment->dataEntrega . ':00' : $shipment->dataEntrega)))
            : null;
        return [
            'isDelivered' => $deliveredDate ?: false,
            'dateDelivered' => $deliveredDate,
            'occurrences' => []
        ];
    }

    /**
     * Importar a dados de ocorrência do rastreio
     * @param string $orderIntegration Código do pedido na integradora
     * @param object $order Dado completo do pedido (Api/V1/Order/{order})
     * @param array $dataOccurrence Dados de ocorrência
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     * @todo criar funcionalidade para a integradora.
     *
     */
    public function setOccurrenceIntegration(string $orderIntegration, object $order, array $dataOccurrence): bool
    {
        return true;
    }

    /**
     * Recupera data de entrega do pedido.
     *
     * @param   string $orderIntegration    Código do pedido na integradora
     * @return  string                      Data de entrega do pedido
     * @throws  InvalidArgumentException
     */
    public function getDeliveredIntegration(string $orderIntegration): string
    {
        if ($this->checkOrderCanceled()) {
            return '';
        }

        $order = $this->getOrderIntegration();
        $shipments = $order->entregas ?? null;
        $shipment = !empty($shipments) ? current($shipments) : (object)[];
        $deliveredDate = isset($shipment->dataEntrega) && !empty($shipment->dataEntrega) ?
            date('Y-m-d H:i:s', strtotime($shipment->dataEntrega))
            : null;
        if (empty($deliveredDate)) {
            return '';
        }
        return date('Y-m-d H:i:s', strtotime($deliveredDate));
    }

    /**
     * Importar a data de entrega.
     *
     * @param string $orderIntegration Código do pedido na integradora.
     * @param object $order Dado completo do pedido (Api/V1/Order/{order}).
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setDeliveredIntegration(string $orderIntegration, object $order): bool
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException("Pedido deve ser cancelado.");
        }

        // request to send delivered date to integration.
        $urlShipped = "pedido/$orderIntegration/entregue";
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

    public function mapInvoiceData($receivedData): array
    {
        return [
            'key' => $receivedData->ChaveAcesso,
            'number' => $receivedData->IdNotaFiscal,
            'serie' => $receivedData->SerieNotaFiscal,
            'value' => $receivedData->ValorTotal,
            'date' => date('Y-m-d H:i:s', strtotime($receivedData->DataEmissaoNotaFiscal ?? date('Y-m-d H:i:s'))),
        ];
    }

    public function mapTrackingData($receivedData, $items, $nfe): array
    {
        $itemsTracking = [];
        foreach ($items as $item) {

            $shipping_company = 'VIA';
            if (!empty($receivedData->Transportadora)) {
                $shipping_company = $receivedData->Transportadora;
            } elseif(!empty($item->shipping_carrier)) {
                $shipping_company = $item->shipping_carrier;
            }

            $tracking_code = $nfe;
            if (!empty($receivedData->CodigoRastreio)) {
                $tracking_code = $receivedData->CodigoRastreio;
            }

            $itemsTracking[$item->sku_variation ?? $item->sku] = [
                'quantity' => $item->qty,
                'shippingCompany' => $shipping_company,
                'trackingCode' => $tracking_code,
                'trackingUrl' => $receivedData->url ?? null,
                'generatedDate' => date(DATETIME_INTERNATIONAL),
                'shippingMethodName' => !empty($item->service_method) ? $item->service_method : $shipping_company,
                'shippingMethodCode' => !empty($item->service_method) ? $item->service_method : 1,
                'deliveryValue' => 0,
                'documentShippingCompany' => null,
                'estimatedDeliveryDate' => $receivedData->DataPrevisao ? date(DATETIME_INTERNATIONAL, strtotime($receivedData->DataPrevisao)) : null,
                'labelA4Url' => null,
                'labelThermalUrl' => null,
                'labelZplUrl' => null,
                'labelPlpUrl' => null
            ];
        }

        return $itemsTracking;
    }

    public function updateOrderFromIntegration($receivedData)
    {
        $this->orderId = $receivedData->IdPedidoParceiro;
        $this->orderIdIntegration = $receivedData->IdCompra;

        $orderDb = $this->order_v2->model_orders->getOrdersData(0, $this->orderId);

        try {
            if (isset($receivedData->ChaveAcesso) && in_array($orderDb['paid_status'], [\OrderStatusConst::WAITING_INVOICE])) {
                $receivedData->{'ValorTotal'} = $orderDb['net_amount'] ?? $orderDb['total_order'];
                $isInvoiced = $this->order_v2->setInvoiceOrder($this->mapInvoiceData($receivedData), $this->orderId);
            }
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao inserir os <b>Dados Fiscais</b> do pedido <b>{$this->orderId}</b>",
                0,
                $e
            );
        }

        try {
            if (
                in_array($orderDb['paid_status'], [\OrderStatusConst::WAITING_TRACKING])
            ) {
                $orderAPI = $this->order_v2->getOrder($this->orderId);
                $orderAPI->items = array_map(function ($item) use ($orderAPI, $receivedData) {
                    $item->{'shipping_carrier'} = $receivedData->Transportadora ?? $orderAPI->shipping->shipping_carrier ?? null;
                    $item->{'service_method'} = $orderAPI->shipping->service_method ?? null;
                    return $item;
                }, $orderAPI->items);

                $responseTracking = $this->order_v2->setTrackingOrder(
                    $this->mapTrackingData($receivedData, $orderAPI->items, $receivedData->IdNotaFiscal),
                    $this->orderId
                );

                $this->order_v2->log_integration("Pedido ($this->orderId) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido $this->orderId, foi atualizado para <strong>Aguardando Coleta/Envio</strong></li></ul>", "S");
            }
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao inserir os <b>Dados de Rastreamento</b> do pedido <b>$this->orderId</b>",
                0,
                $e
            );
        }

        try {
            if (isset($receivedData->DataSaida) && in_array($orderDb['paid_status'], [
                    \OrderStatusConst::WITH_TRACKING_WAITING_SHIPPING
                ])
            ) {
                $receivedData->DataSaida = date(DATETIME_INTERNATIONAL, strtotime($receivedData->DataSaida));
                $this->order_v2->setShippedOrder($receivedData->DataSaida, $this->orderId);
                $this->order_v2->log_integration("Pedido ($this->orderId) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido $this->orderId, foi atualizado para <strong>Em Transporte em: " . datetimeBrazil($receivedData->DataSaida, null)."</strong></li></ul>", "S");
            }
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao inserir a <b>Data de Envio</b> do pedido <b>$this->orderId</b>",
                0,
                $e
            );
        }

        try {
            if (isset($receivedData->DataEntrega) && in_array($orderDb['paid_status'], [
                    \OrderStatusConst::SHIPPED_IN_TRANSPORT_45
                ])
            ) {
                $receivedData->DataEntrega = date(DATETIME_INTERNATIONAL, strtotime($receivedData->DataEntrega));
                $this->order_v2->setDeliveredOrder($receivedData->DataEntrega, $this->orderId);
                $this->order_v2->log_integration("Pedido ($this->orderId) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido $this->orderId, foi atualizado para <strong>Entregue em: " . datetimeBrazil($receivedData->DataSaida, null)."</strong></li></ul>", "S");
            }

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao inserir a <b>Data de Entrega</b> do pedido <b>$this->orderId</b>",
                0,
                $e
            );
        }

        return true;
    }

    /**
     * Recupera código do meio de pagamento.
     *
     * @param   string  $mean   Nome do meio de pagamento.
     * @return  string
     */
    private function getMeanOfPayment(string $mean): string
    {
        $mean = strtolower($mean);
        // 01=Dinheiro
        // 02=Cheque
        // 03=Cartão de Crédito
        // 04=Cartão de Débito
        // 05=Crédito Loja
        // 10=Vale Alimentação
        // 11=Vale Refeição
        // 12=Vale Presente
        // 13=Vale Combustível
        // 15=Boleto Bancário
        // 16=Depósito Bancário
        // 17=Pagamento Instantâneo (PIX)
        // 18=Transferência bancária, Carteira Digital
        // 19=Programa de fidelidade, Cashback, Crédito Virtual
        // 90=Sem pagamento

        if (likeText('%credit%',$mean)) {
            return "03";
        }
        if (likeText('%debit%',$mean)) {
            return "04";
        }
        if (likeText('%instant%',$mean) || likeText('%pix%',$mean)) {
            return "17";
        }

        return "15";
    }

    /**
     * Recupera o código da bandeira de pagamento.
     *
     * @param   string  $brand  Nome da bandeira.
     * @return  string
     */
    private function getFlagOfPayment(string $brand): string
    {
        // 01 Visa
        // 02 Mastercard
        // 03 American Express
        // 04 Sorocred
        // 05 Diners Club
        // 06 Elo
        // 07 Hipercard
        // 08 Aura
        // 09 Cabal
        // 10 Alelo
        // 11 Banes Card
        // 12 CalCard
        // 13 Credz
        // 14 Discover
        // 15 GoodCard
        // 16 GreenCard
        // 17 Hiper
        // 18 JcB
        // 19 Mais
        // 20 MaxVan
        // 21 Policard
        // 22 RedeCompras
        // 23 Sodexo
        // 24 ValeCard
        // 25 Verocheque
        // 26 VR
        // 27 Ticket
        // 99 Outros

        if ($brand == 'Visa') {
            return "01";
        }
        if ($brand == 'Mastercard') {
            return "02";
        }
        if ($brand == 'American Express') {
            return "03";
        }
        if ($brand == 'Sorocred') {
            return "04";
        }
        if ($brand == 'Diners Club') {
            return "05";
        }
        if ($brand == 'Elo') {
            return "06";
        }
        if ($brand == 'Hipercard') {
            return "07";
        }
        if ($brand == 'Aura') {
            return "08";
        }
        if ($brand == 'Cabal') {
            return "09";
        }
        if ($brand == 'Alelo') {
            return "10";
        }
        if ($brand == 'Banes Card') {
            return "11";
        }
        if ($brand == 'CalCard') {
            return "12";
        }
        if ($brand == 'Credz') {
            return "13";
        }
        if ($brand == 'Discover') {
            return "14";
        }
        if ($brand == 'GoodCard') {
            return "15";
        }
        if ($brand == 'GreenCard') {
            return "16";
        }
        if ($brand == 'Hiper') {
            return "17";
        }
        if ($brand == 'JcB') {
            return "18";
        }
        if ($brand == 'Mais') {
            return "19";
        }
        if ($brand == 'MaxVan') {
            return "20";
        }
        if ($brand == 'Policard') {
            return "21";
        }
        if ($brand == 'RedeCompras') {
            return "22";
        }
        if ($brand == 'Sodexo') {
            return "23";
        }
        if ($brand == 'ValeCard') {
            return "24";
        }
        if ($brand == 'Verocheque') {
            return "25";
        }
        if ($brand == 'VR') {
            return "26";
        }
        if ($brand == 'Ticket') {
            return "27";
        }

        return "99";
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

        $urlOrder = "pedidos/$this->orderIdIntegration";
        $orderMarketplace = $this->order_v2->getNumeroMarketplaceByOrderId($this->orderId);
        $queryOrder = array(
            "idCampanha"            => $this->order_v2->credentials->campaign,
            "idPedidoParceiro"      => $this->orderId,
            "idPedidoMktplc"        => $orderMarketplace,
            "confirmado"            => false
        );

        try {
            $message_error = null;
            $request = $this->order_v2->request('PATCH', $urlOrder, array('json' => $queryOrder));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message_error = $exception->getMessage();
            // É um erro e não é de status inválido.
            if (
                !likeText('%PedidoComStatusInvalido%', $message_error) &&
                !likeText('%"PedidoCancAutFaltConf%', $message_error)
            ) {
                throw new InvalidArgumentException($message_error);
            }
        }

        return $this->order_v2->model_orders_integration_history->create(array(
            'order_id'  => $this->orderId,
            'type'      => 'cancel_order',
            'request'   => json_encode($queryOrder, JSON_UNESCAPED_UNICODE),
            'response'  => $message_error ?? (isset($request) ? $request->getBody()->getContents() : '')
        ));
    }

    public function checkOrderCanceled(): bool
    {
        // Pedido já foi cancelado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        $order      = $this->getOrderIntegration();
        $shipments  = property_exists($order, 'entregas') ? $order->entregas : null;
        $shipment   = !empty($shipments) ? current($shipments) : (object)[];

        if (!property_exists($shipment, 'trackingEntrega') || empty($shipment->trackingEntrega)) {
            throw new InvalidArgumentException('Pedido não contém a propriedade trackingEntrega.');
        }

        $trackingEntrega = $shipment->trackingEntrega;

        foreach ($trackingEntrega as $tracking) {
            // Cancelar o pedido.
            if (in_array($tracking->codDescricao, array('_CANM', 'CAN'))) {
                try {
                    $this->order_v2->setCancelOrder($this->orderId, dateFormat($tracking->data, 'Y-m-d H:i:s', null), 'Cancelado pelo seller via integradora.');
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException("Não possível realizar o cancelamento do pedido $this->orderId. {$exception->getMessage()}");
                }

                $options = array(
                    "query" => [
                        "request.cnpj"           => cnpj(onlyNumbers($this->order_v2->credentials->cnpj)),
                        "request.idCampanha"     => $this->order_v2->credentials->campaign,
                        "request.idPedidoMktplc" => $this->order_v2->getNumeroMarketplaceByOrderId($this->order_v2->unique_id)
                    ],
                    "uri" => "pedidos/$this->orderIdIntegration"
                );

                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'  => $this->orderId,
                    'type'      => 'cancel_order',
                    'request'   => json_encode($options, JSON_UNESCAPED_UNICODE),
                    'response'  => json_encode($order, JSON_UNESCAPED_UNICODE)
                ));

                return true;
            }
        }

        return false;
    }
}