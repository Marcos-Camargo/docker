<?php

namespace Integration\microvix;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Integration\Integration_v2\Order_v2;
use Integration\Integration_v2\microvix\Services\LinxB2InputService;
use Integration\Integration_v2\microvix\Services\LinxB2OutputService;

require_once(APPPATH . 'libraries/Integration_v2/microvix/Services/LinxB2InputService.php');
require_once(APPPATH . 'libraries/Integration_v2/microvix/Services/LinxB2OutputService.php');

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
     * @var LinxB2InputService Classe de serviço que envia requests para endpoint de entrada da Microvix
     */
    public $linxB2InputService;

    /**
     * @var LinxB2OutputService Classe de serviço que envia requests para endpoint de Saida da Microvix
     */
    public $linxB2OutputService;

    protected $initialized = false;

    private $statusMicrovix = [
        3 => [
            'codigo_microvix' => 1,
            'name_microvix' => 'PENDENTE'
        ],
        60 => [
            'codigo_microvix' => 4,
            'name_microvix' => 'ENTREGUE'
        ],
        52 => [
            'codigo_microvix' => 5,
            'name_microvix' => 'FATURADO'
        ],
        [
            'codigo_microvix' => 9,
            'name_microvix' => 'SEPARAÇÃO'
        ],
        55 => [
            'codigo_microvix' => 10,
            'name_microvix' => 'ENVIADO'
        ],
    ];

    /**
     * Instantiate a new Tools instance.
     *
     * @param Order_v2 $order_v2
     */
    public function __construct(Order_v2 $order_v2)
    {
        $this->order_v2 = $order_v2;
        $this->order_v2->load->library('Cache/cacheManager');
        $this->order_v2->can_integrate_incomplete_order = true;
        $this->order_v2->load->model('model_orders_item');
        $this->order_v2->load->model('model_orders');
        $this->order_v2->load->model('model_orders_integration_history');
    }

    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->linxB2InputService = new LinxB2InputService($this->order_v2->store);
        $this->linxB2OutputService = new LinxB2OutputService($this->order_v2->store);
        $this->initialized = true;
    }

    /**
     * Envia o pedido para a integradora.
     *
     * @param   object  $order  Dados do pedido para formatação.
     * @return  array           Código do pedido gerado pela integradora e dados da requisição para log.
     */
    public function sendOrderIntegration(object $order): array
    {
        if(in_array($order->status->code, array(1,2,96))) {
            throw new InvalidArgumentException('Pedido Aguardando confirmação de pagamento, só será integrado após Pagamento Confirmado!');
        }

        $this->initialize();
        try {
            $shipping_address = $order->shipping->shipping_address;
            $shipping_address->number = !empty($shipping_address->number) ? $shipping_address->number : 'S/N';
            $shipping_address->complement = !empty($shipping_address->complement) ? $shipping_address->complement : '-';
            $shipping_address->reference = !empty($shipping_address->reference) ? $shipping_address->reference : '-';

            $customer = $order->customer;
            $this->orderId = $order->code;
            $this->orderIdIntegration =  $order->code;

            try {
                $client_id = $this->createClient($customer, $shipping_address);

                 $this->createOrder($order, $client_id);
                 $this->createOrderItems($order);
            } catch (ClientException | InvalidArgumentException $exception) {
                $this->saveLogIntegrationInOrder($exception->getMessage());
            }

            $commands = [
                'B2CCadastraClientes',
                'B2CCadastraPedido',
                'B2CCadastraPedidosItens'
            ];

            $enpointInput = $this->linxB2InputService->getEndpoint();

            return array(
                'id'        => $this->orderIdIntegration,
                'request'   => "$enpointInput\n" . Utils::jsonEncode($commands, JSON_UNESCAPED_UNICODE)
            );

        } catch (ClientException | InvalidArgumentException $exception) {
            $this->saveLogIntegrationInOrder($exception->getMessage());
        }
    }

    protected function createClient(object $customer, object $shipping_address): int
    {
        $document = onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj);

        $client = $this->getCustomerERP($document);

        if (empty($client['response'])) {
            $data = [
                'cod_cliente' => $customer->id,
                'doc_cliente' => $document,
                'nm_cliente' => $customer->name,
                'ativo' => 1,
                'receber_email' => 0,
                'cidade_cliente' => $shipping_address->city,
                'cep_cliente' => $shipping_address->postcode,
                'bairro_cliente' => $shipping_address->neighborhood,
                'end_cliente' => $shipping_address->street,
                'complemento_end_cliente' => $shipping_address->complement,
                'uf_cliente' => $shipping_address->region,
                'nr_rua_cliente' => $shipping_address->number,
                'fone_cliente' => $shipping_address->phone,
                'email_cliente' => $customer->email,
                'atualizar_por_cpf' => 1
            ];

            if($customer->person_type === 'pf') {
                $data['rg_cliente'] = $customer->rg;
            } else {
                $data['inscricao_cliente'] = $customer->ie;
            }

            $response = $this->linxB2InputService->registerCustomer($data);
            if ($response['status'] != 200) {

                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'create_client_error',
                    'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'request_method' => 'POST',
                    'response'       => $response['body'],
                    'response_code'  => $response['status'],
                    'request_uri'    => "B2CCadastraClientes"
                ));

                throw new InvalidArgumentException('Falha ao cadastrar cliente. Pedido não integrado');
            }

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'create_client',
                'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                'request_method' => 'POST',
                'response'       => $response['body'],
                'response_code'  => $response['status'],
                'request_uri'    => "B2CCadastraClientes"
            ));

            return $customer->id;
        }

        return $client['response'][0]['cod_cliente_b2c'];
    }

    protected function createOrder(object $order, int $client_id): bool
    {
        sleep(1);

        $data = [
            'id_pedido' => $order->code,
            'dt_pedido' => $order->created_at,
            'cod_cliente' => $client_id,
            'tipo_frete' => $this->getStatusTypesDelivery($order->shipping->shipping_carrierName),
            'id_status' => 1, //status Pendente
            'ativo' => 1,
            'valor_frete' => $order->shipping->seller_shipping_cost,
            'desconto' => $order->payments->discount
        ];

        $methodPayment = count($order->payments->parcels) <= 1;
        if ($methodPayment) {
            $data['plano_pagamento'] = $this->getPaymentMethod($order->payments->parcels[0]);
        }

        $response = $this->linxB2InputService->registerOrder($data);
        if ($response['status'] != 200) {

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'create_order_error',
                'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                'request_method' => 'POST',
                'response'       => $response['body'],
                'response_code'  => $response['status'],
                'request_uri'    => "B2CCadastraPedido"
            ));

            throw new InvalidArgumentException("Erro ao criar pedido na microvix.");
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'create_order',
            'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => $response['body'],
            'response_code'  => $response['status'],
            'request_uri'    => "B2CCadastraPedido"
        ));

        if (!$methodPayment) {
            foreach ($order->payments->parcels as $key => $parcel) {
                $codePayment = $this->getPaymentMethod($parcel);

                //verificar o que é plano de pagamento e valor do plano
                $data = [
                    'id_pedido' => $order->code,
                    'id_pedido_planos' => $parcel->order_payment_id,
                    'plano_pagamento' => $codePayment,
                    'valor_plano' => $parcel->value,
                ];

                $response = $this->linxB2InputService->registerOrderPlans($data);

                if ($response['status'] != 200) {
                    $this->order_v2->model_orders_integration_history->create(array(
                        'order_id'       => $this->orderId,
                        'type'           => 'create_plan_payment_error',
                        'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'request_method' => 'POST',
                        'response'       => $response['body'],
                        'response_code'  => $response['status'],
                        'request_uri'    => "B2CCadastraPedidoPlanos"
                    ));

                    throw new InvalidArgumentException("Pedido não integrado. Erro ao criar plano de pagamento do pedido na microvix.");
                }

                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'create_plan_payment',
                    'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'request_method' => 'POST',
                    'response'       => $response['body'],
                    'response_code'  => $response['status'],
                    'request_uri'    => "B2CCadastraPedidoPlanos"
                ));
            }
        }

        $this->order_v2->model_orders->saveOrderIdIntegrationByOrderIDAndStoreId($order->code, $this->order_v2->store, $order->code);
        return true;
    }

    /**
     * Enviar items do pedido para microvix
     *
     * @return bool
     */
    protected function createOrderItems(object $order):bool
    {
        foreach ($order->items as $key => $item) {
            $data = [
                'id_pedido_item' => $item->order_item_id,
                'id_pedido' => $order->code,
                'codigoproduto' => $item->sku_integration,
                'quantidade' => $item->qty,
                'vl_unitario' => $item->original_price
            ];

            $response = $this->linxB2InputService->registerOrderItems($data);

            if ($response['status'] != 200) {
                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'create_item_order_error',
                    'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'request_method' => 'POST',
                    'response'       => $response['body'],
                    'response_code'  => $response['status'],
                    'request_uri'    => "B2CCadastraPedidosItens"
                ));

                throw new InvalidArgumentException("Pedido não integrado. Erro ao criar items do pedido na microvix.");
            }

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'create_item_order',
                'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
                'request_method' => 'POST',
                'response'       => $response['body'],
                'response_code'  => $response['status'],
                'request_uri'    => "B2CCadastraPedidosItens"
            ));
        }
        return true;
    }

    /**
     * Função para buscar os metodos de pagamento e devolver o id do pagamento do lado da microvix
     * @param object $paymentData
     * @return int
     */
    protected  function getPaymentMethod(object $paymentData): int
    {
        $data = [
            'timestamp' => 0
        ];

        $command = 'B2CConsultaPlanos';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        if (count($response['body'][0]) == 0 || empty($response['body'][0])) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        $method = strtoupper($paymentData->payment_method);
        $parcel = $paymentData->parcel . 'X';
        $debit = $paymentData->payment_type === 'debitCard';

        switch ($paymentData->payment_method) {
            case 'Mastercard':
                $flag = 'MASTER ' . $parcel;
                break;

            case 'Hipercard':
                $flag = 'HIPER ' . $parcel;
                break;

            case 'American Express':
                $flag = 'AMEX ' . $parcel;
                break;

            case 'Vale':
                $flag = $paymentData->parcel === 1
                    ? 'VALE FUNCIONÁRIO'
                    : 'VALE FUNCIONÁRIO ' . $paymentData->parcels->parcel . 'X';
                break;

            default:
                $flag = $method . ' ' . $parcel;
                break;
        }

        if ($debit && $paymentData->payment_method !== 'Vale') {
            $flag .= ' DÉBITO';
        }

        $paymentCode = null;
        foreach ($response['body'] as $key => $payment) {
            if ($payment['nome_plano'] === $flag) {
                if ($payment['desativado'] === 'S') {
                    continue;
                }
                $paymentCode = $payment['plano'];
            }
        }

        return $paymentCode;
    }

    public function getCustomerERP(int $customerId): array
    {
        $this->initialize();
        $data = [
            'timestamp' => 0,
            'doc_cliente' => $customerId,
        ];

        $command = 'B2CConsultaClientes';

        $response = $this->linxB2OutputService->request($command, $data);
        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        return [
            'status' => true,
            'response' => $response['body'],
        ];
    }

    protected function getStatusTypes():array
    {
        $data = [
            'timestamp' => 0
        ];

        $command = 'B2CConsultaStatus';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        return $response['body'];
    }

    protected function getStatusTypesDelivery(string $carrierName): int
    {
        $data = [
            'timestamp' => 0
        ];

        $command = 'B2CConsultaTipoEncomenda';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        if (count($response['body'][0]) == 0 || empty($response['body'][0])) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        $sedex = null;
        $pac = null;
        $transportadora = null;
        foreach ($response['body'] as $item) {
            if ($item['nm_tipo_encomenda'] == 'Sedex') {
                $sedex = (int) $item['id_tipo_encomenda'];
            }

            if ($item['nm_tipo_encomenda'] == 'PAC') {
                $pac = (int) $item['id_tipo_encomenda'];
            }

            if ($item['nm_tipo_encomenda'] === 'Transportadora') {
                $transportadora = (int) $item['id_tipo_encomenda'];
            }
        }

        if ($carrierName == 'Pac') {
            return $pac;
        }

        if ($carrierName == 'Sedex') {
            return $sedex;
        }

        return $transportadora;
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
        $this->initialize();
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        $data = [
            'id_pedido' => $orderIntegration,
            'ativo' =>  1
        ];

        $command = 'B2CcancelaPedido';

        $response = $this->linxB2InputService->cancelOrder($data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order,
            'type'           => 'cancel_order',
            'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => $response['body'],
            'response_code'  => $response['status'],
            'request_uri'    => $command
        ));

        return true;
    }

    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int      $orderIntegration  Código do pedido na integradora.
     * @return  array|object            Dados do pedido na integradora.
     */
    public function getOrderIntegration($orderIntegration): array
    {
        $this->initialize();
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        $order = $this->order_v2->model_orders->getOrderByOrderIdIntegration($orderIntegration);

        if (empty($order)) {
            $this->saveLogIntegrationInOrder('Pedido não encontrato, id integração: ' . $orderIntegration);
        }

        $data = [
            'id_pedido' => $orderIntegration,
            'timestamp' => 0
        ];

        $command = 'B2CConsultaPedidos';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        return $response['body'];
    }

    /**
     * Recupera dados de tracking.
     *
     * @param   string  $orderIntegration   Código do pedido na microvix.
     * @param   array   $items              Itens do pedido.
     * @return  array                       Array onde, a chave de cada item do array será o código SKU e o valor do item um array com os dados: quantity, shippingCompany, trackingCode, trackingUrl, generatedDate, shippingMethodName, shippingMethodCode, deliveryValue, documentShippingCompany, estimatedDeliveryDate, labelA4Url, labelThermalUrl, labelZplUrl, labelPlpUrl.
     * @throws  InvalidArgumentException
     */
    public function getTrackingIntegration(string $orderIntegration, array $items): array
    {
        return [];
    }

    /**
     * Atualiza codigo de rastreio na microvix.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order}).
     * @param   object  $dataTracking       Dados do rastreio do pedido (Api/V1/Tracking/{order}).
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setTrackingIntegration(string $orderIntegration, object $order, object $dataTracking): array
    {
        $this->initialize();
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        $data = [
            'id_pedido' => $order->code,
            'codigo_rastreio' => $dataTracking->tracking->tracking_code[0]
        ];

        $response = $this->linxB2InputService->updateTrackingCode($data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        $dataUpdateStatus = [
            'id_pedido' => $order->code,
            'id_status' => 9 // em separação
        ];

        $response = $this->linxB2InputService->updateStatusOrder($dataUpdateStatus);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_tracking',
            'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => $response['body'],
            'response_code'  => $response['status'],
            'request_uri'    => "B2CAtualizaPedidoStatus"
        ));

        return true;
    }

    /**
     * Recupera dados da nota fiscal do pedido
     *
     * @param   string  $orderIdIntegration Dados do pedido da integradora
     * @param int $orderid Código do pedido no Seller Center
     * @return  array                       Dados de nota fiscal do pedido [date, value, serie, number, key]
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderId)
    {
        $this->initialize();
        if ($this->checkOrderCanceled()) {
           throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        try {

            $data = [
                'id_pedido' => $orderIdIntegration,
            ];

            $response = $this->linxB2OutputService->request('B2CConsultaNFe', $data);

            if ($response['status'] != 200) {
                $this->saveLogIntegrationInOrder($response['body']);
            }

            if (empty($response['body'])) {
                throw new InvalidArgumentException('Ainda não faturado');
            }

        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (
            empty($response['body']['chave_nfe']) ||
            is_null($response['body']['serie']) || $response['body']['serie'] === '' ||
            empty($response['body']['data_emissao']) ||
            empty($response['body']['valor_nota']) ||
            empty($response['body']['documento'])
        ) {
            $this->order_v2->log_integration("Erro para atualizar o pedido ($this->orderId)", 'Os dados de nota fiscal estão incompletos, reveja: Chave, Número, Série, Data de emissão emissão ou Valor da nota.', "E");
            throw new InvalidArgumentException("Erro para atualizar o pedido ($this->orderId). Os dados de nota fiscal estão incompletos, reveja: Chave, Número, Série, Data de emissão ou Valor da nota.");
        }

        return [
            'date' => dateFormat($response['body']['data_emissao'], DATETIME_INTERNATIONAL),
            'value' => $response['body']['valor_nota'],
            'serie' => $response['body']['serie'],
            'number' => $response['body']['documento'],
            'key' => $response['body']['chave_nfe']
        ];
    }

    protected function checkOrderCanceled(): bool
    {
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        try {
            $order = $this->getOrderIntegration($this->orderIdIntegration);

            if (!$order[0]['ativo']) {
                $this->order_v2->setCancelOrder(
                    $this->orderId,
                    dateNow()->format(DATETIME_INTERNATIONAL),
                    'Cancelado pelo seller via integradora.'
                );

                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'cancel_order',
                    'request'        => null,
                    'request_method' => 'POST',
                    'response'       => json_encode($order),
                    'response_code'  => 200,
                    'request_uri'    => null
                ));

                return true;
            }
        } catch(InvalidArgumentException $exception)
        {
            $this->saveLogIntegrationInOrder($exception->getMessage());
        }

        return false;
    }

    /**
     * Recupera ocorrências do rastreio.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  array                       Dados de ocorrência do pedido. Um array com as chaves 'isDelivered' e 'occurrences', onde 'occurrences' será os dados de ocorrência do rastreio, composto pelos índices 'date' e 'occurrence'
     * @throws  InvalidArgumentException
     */
    public function getOccurrenceIntegration()
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        return [
            'isDelivered'   => false,
            'dateDelivered' => null,
            'occurrences'   => array()
        ];
    }

    /**
     * Recupera data de envio do pedido.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  string                      Data de envio do pedido.
     * @throws  InvalidArgumentException
     */
    public function getShippedIntegration()
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        return '';
    }

    /**
     * @param string $orderIntegration
     * @param object $order
     * @return bool
     */
    public function setShippedIntegration(string $orderIntegration, object $order): bool
    {
        $this->initialize();
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        $data =  [
            'id_pedido' => $order->code,
            'id_status' => 10, //status enviado
        ];

        $response = $this->linxB2InputService->updateStatusOrder($data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_shipped',
            'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => $response['body'],
            'response_code'  => $response['status'],
            'request_uri'    => "B2CAtualizaPedidoStatus"
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
    public function getDeliveredIntegration(string $orderIntegration)
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        return '';
    }

    /**
     * Importar a data de entrega.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order}).
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setDeliveredIntegration(string $orderIntegration, object $order)
    {
        $this->initialize();
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        $data =  [
            'id_pedido' => $order->code,
            'id_status' => 4, //status entregue
        ];

        $response = $this->linxB2InputService->updateStatusOrder($data);

        if ($response['status'] != 200) {
            $this->saveLogIntegrationInOrder($response['body']);
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_shipped',
            'request'        => json_encode($data, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => $response['body'],
            'response_code'  => $response['status'],
            'request_uri'    => "B2CAtualizaPedidoStatus"
        ));

        return true;
    }

    /**
     * Importar a dados de ocorrência do rastreio.
     * @warning Magalu não recebe rastreio.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order}).
     * @param   array   $dataOccurrence     Dados de ocorrência.
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setOccurrenceIntegration(string $orderIntegration, object $order, array $dataOccurrence)
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado');
        }

        return true;
    }

    private function saveLogIntegrationInOrder(string $error_message)
    {
        $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>$error_message</li></ul>", "E");
        throw new InvalidArgumentException("Falha ao criar o pedido ($this->orderId). Pedido não foi integrado $error_message");
    }
}