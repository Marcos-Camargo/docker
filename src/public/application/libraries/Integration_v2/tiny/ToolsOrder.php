<?php

namespace Integration\tiny;

use DateTime;
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

        // Na conecta lá, enviamos os dados da fatura igual ao da entrega.
        if ($this->order_v2->sellerCenter === 'conectala') {
            $arrClient = array(
                // 'codigo'            => $customer->id,
                'nome'              => $shipping_address->full_name,
                'nome_fantasia'     => $shipping_address->full_name,
                'tipo_pessoa'       => $customer->person_type === 'pf' ? "F" : "J",
                'cpf_cnpj'          => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'ie'                => $customer->ie,
                'rg'                => $customer->rg,
                'endereco'          => $shipping_address->street,
                'numero'            => $shipping_address->number,
                'complemento'       => $shipping_address->complement,
                'bairro'            => $shipping_address->neighborhood,
                'cep'               => $shipping_address->postcode,
                'cidade'            => $shipping_address->city,
                'uf'                => $shipping_address->region,
                'fone'              => $shipping_address->phone,
                'celular'           => $shipping_address->phone,
                'email'             => $customer->email,
                'pais'              => "Brasil",
                'atualizar_cliente' => "S"
            );
        }
        // não é conecta lá, enviamos o endereço da fatura
        else {
            $arrClient = array(
                // 'codigo'            => $customer->id,
                'nome'              => $billing_address->full_name,
                'nome_fantasia'     => $billing_address->full_name,
                'tipo_pessoa'       => $customer->person_type === 'pf' ? "F" : "J",
                'cpf_cnpj'          => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'ie'                => $customer->ie,
                'rg'                => $customer->rg,
                'endereco'          => $billing_address->street,
                'numero'            => $billing_address->number,
                'complemento'       => $shipping_address->complement,
                'bairro'            => $billing_address->neighborhood,
                'cep'               => $billing_address->postcode,
                'cidade'            => $billing_address->city,
                'uf'                => $billing_address->region,
                'fone'              => onlyNumbers($customer->phone[0] ?? $customer->phone[1] ?? ''),
                'celular'           => onlyNumbers($customer->phone[1] ?? $customer->phone[0] ?? ''),
                'email'             => $customer->email,
                'pais'              => "Brasil",
                'atualizar_cliente' => "S"
            );
        }

        // verifica forma de pagamento
        $forma_pagamento  = "dinheiro"; // forma pagamento default
        if (isset($order->payments->parcels[0]->payment_type)) {
            $payment_type = $order->payments->parcels[0]->payment_type;
            switch ($payment_type) {
                case 'creditCard':
                    $forma_pagamento = "credito";
                    break;
                case 'debitCard':
                    $forma_pagamento = "debito";
                    break;
                case 'bankInvoice':
                    $forma_pagamento = "boleto";
                    break;
                case 'giftCard':
                    $forma_pagamento = "multiplas";
                    break;
                case 'instantPayment':
                    $forma_pagamento = "pix";
                    break;
            }
        }
        
        // Busca informações sobre a logistica da loja.
        $logistic = $this->order_v2->calculofrete->getLogisticStore([
            'freight_seller' => $this->order_v2->dataStore['freight_seller'],
            'freight_seller_type' => $this->order_v2->dataStore['freight_seller_type'],
            'store_id' => $this->order_v2->dataStore['id']
        ]);

        $newOrder = array(
            'data_pedido'               => date('d/m/Y', strtotime($order->created_at)),
            'id_lista_preco'            => $this->order_v2->credentials->id_lista_tiny,
            'valor_desconto'            => $order->payments->discount,
            'outras_despesas'           => 0,
            'obs_internas'              => "Pedido {$this->order_v2->nameSellerCenter}: $this->orderId",
            'situacao'                  => in_array($order->status->code, array(1,2,96)) ? 'aberto' : 'aprovado',
            'numero_pedido_ecommerce'   => $this->orderId,
            'ecommerce'                 => $this->order_v2->nameSellerCenter,
            'cliente'                   => $arrClient,
            'endereco_entrega'          => array(
                'tipo_pessoa'       => $customer->person_type === 'pf' ? "F" : "J",
                'cpf_cnpj'          => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'endereco'          => $billing_address->street,
                'numero'            => $billing_address->number,
                'complemento'       => $shipping_address->complement,
                'bairro'            => $billing_address->neighborhood,
                'cep'               => $billing_address->postcode,
                'cidade'            => $billing_address->city,
                'uf'                => $billing_address->region,
                'fone'              => $customer->phone[0] ?? $customer->phone[1] ?? '',
                'nome_destinatario' => $billing_address->full_name
            ),
            'itens'         => array(),
            'marcadores'    => array(
                array(
                    'marcador' => array(
                        'descricao' => $this->order_v2->nameSellerCenter
                    )
                )
            ),
            'forma_pagamento'       => (string)$forma_pagamento,
            'meio_pagamento'        => "MarketPlace",
            'parcelas'              => array(),
            'nome_transportador'    => $order->shipping->service_method ?? '',
            'frete_por_conta'       => $logistic['seller'] ? "R" : "D", // Verifica se é logistica do seller, se for, vai como CIF/por conta do remetente.
            'valor_frete'           => $order->shipping->seller_shipping_cost,
            'forma_envio'           => $order->shipping->is_correios ? "C" : "T",
            'forma_frete'           => $order->shipping->is_correios ? $order->shipping->service_method : ""
        );

        // Integração para testes webhook, na conta da Conecta Lá.
        if (ENVIRONMENT === 'development' && $this->order_v2->sellerCenter === 'conectala') {
            $newOrder['id_ecommerce'] = 8390;
        }

        foreach ($order->items as $item) {
            $newOrder['itens'][] = array(
                'item' => array(
                    'codigo'                => trim($item->sku_variation ?? $item->sku),
                    'descricao'             => trim($item->name),
                    'unidade'               => $item->unity,
                    'quantidade'            => $item->qty,
                    'valor_unitario'        => $item->original_price
                )
            );
        }

        $date_payment = !in_array($order->status->code, array(1,2,96)) ? date('d/m/Y', strtotime($order->payments->date_payment ?? date('Y-m-d'))) : '';
        $newOrder['parcelas'][] = array(
            'parcela' => array(
                'data'              => $date_payment,
                'valor'             => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
                'forma_pagamento'   => (string)$forma_pagamento,
            )
        );

        if ($this->order_v2->getStoreOwnLogistic()) {
            $newOrder['forma_envio'] = $order->shipping->shipping_carrier;
            $newOrder['forma_frete'] = $order->shipping->service_method;
        }

        $urlOrder = "pedido.incluir.php";
        $queryOrder = array(
            'query' => array(
                'pedido' => Utils::jsonEncode(array('pedido' => $newOrder))
            )
        );
        if (!empty($this->getDeveloperId())) {
            $queryOrder['query']['Developer-Id'] = $this->getDeveloperId();
        }

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());
        if ($contentOrder->retorno->registros->registro->codigo_erro == 31) {
            if (count($contentOrder->retorno->registros->registro->erros) > 0) {
                $skus = $contentOrder->retorno->registros->registro->erros;
                foreach ($skus as $erro) {
                    $mensagem_erro = $erro->erro;
                    $numberSku = preg_match('/\b\d+\b/', $mensagem_erro, $matches);
                    if ($numberSku === 1) {
                        $numberSku_encontrado = $matches[0];
                        $this->order_v2->log_integration("Erro para integrar o pedido ({$order->code})", "<h4>Não foi possível integrar o pedido {$order->code}</h4> <ul><li>SKU {$numberSku_encontrado} não localizado na Tiny. Verifique se o cadastro do produto está correto.</li></ul>", "E");
                        throw new InvalidArgumentException("SKU {$numberSku_encontrado} não localizado na Tiny. Verifique se o cadastro do produto está correto.");
                    }
                }
            }
        }

        $contentRequestOrder = $contentOrder;
        $contentOrder = $contentOrder->retorno->registros;
        if (!isset($contentOrder->registro->id)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Código de identificação do pedido não localizado no retorno.</li></ul>", "E");
            throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora.");
        }

        $idTiny     = $contentOrder->registro->id;
        $numeroTiny = $contentOrder->registro->numero;
        $this->orderIdIntegration = $idTiny;

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order->code,
            'type'           => 'create_order',
            'request'        => json_encode($newOrder, JSON_UNESCAPED_UNICODE),
            'response'       => json_encode($contentRequestOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlOrder
        ));

        $this->setOrderStock();

        return array(
            'id'        => $idTiny,
            'code'      => $numeroTiny,
            'request'   => "$urlOrder\n" . Utils::jsonEncode($newOrder, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Recupera o código de desenvolvimento da integradora. Com esse código, a integradora saberá de onde chegou o pedido para dentro da plataforma.
     *
     * @return string Código de desenvolvimento.
     */
    private function getDeveloperId(): string
    {
        $developerId = $this->order_v2->model_settings->getValueIfAtiveByName('developer_id_tiny');
        return $developerId['value'] ?? '';
    }


    /**
     * Lançar estoque do pedido inserido
     */
    private function setOrderStock()
    {
        $urlSendStock   = "pedido.lancar.estoque.php";
        $querySendStock = array(
            'query' => array(
                'id' => $this->orderIdIntegration
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlSendStock, $querySendStock);
            $response = Utils::jsonDecode($request->getBody()->getContents());

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'set_stock',
                'request'        => json_encode($querySendStock, JSON_UNESCAPED_UNICODE),
                'request_method' => 'POST',
                'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
                'response_code'  => $request->getStatusCode(),
                'request_uri'    => $urlSendStock
            ));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->order_v2->log_integration("Erro para lançar estoque do pedido $this->orderId", "<h4>Não foi possível lançar o estoque do pedido $this->orderId</h4> <ul><li>{$exception->getMessage()}</li></ul>", "E");
        }
    }

    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int|null $order  Código do pedido na integradora.
     * @return  array|object            Dados do pedido na integradora.
     */
    public function getOrderIntegration($order = null)
    {
        $order = $this->orderIdIntegration ?? $order;

        $urlOrder = "pedido.obter.php";
        $queryOrder = array(
            'query' => array(
                'id' => $order
            )
        );

        try {
            $request = $this->order_v2->request('GET', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $dataOrder = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($dataOrder->retorno->pedido)) {
            throw new InvalidArgumentException("Pedido ($order) não localizado");
        }

        return $dataOrder->retorno->pedido;
    }

    /**
     * Recupera dados da expedição na integradora.
     *
     * @param   string|int|null $order  Código do pedido na integradora.
     * @return  array|object            Dados da expedição na integradora.
     */
    public function getExpeditionIntegration($order = null)
    {
        $order = $this->orderIdIntegration ?? $order;

        $urlOrder = "expedicao.obter.php";
        $queryOrder = array(
            'query' => array(
                'idObjeto' => $order,
                'tipoObjeto' => 'venda'
            )
        );

        try {
            $request = $this->order_v2->request('GET', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $dataOrder = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($dataOrder->retorno->expedicao)) {
            throw new InvalidArgumentException("Expedição ($order) não localizado");
        }

        return $dataOrder->retorno->expedicao;
    }

    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int      $idInvoice  Código do pedido na integradora.
     * @return  array|object                Dados do pedido na integradora.
     */
    public function getInvoiceOrderIntegration($idInvoice)
    {
        $urlInvoice = "nota.fiscal.obter.php";
        $queryInvoice = array(
            'query' => array(
                'id' => $idInvoice
            )
        );

        try {
            $request = $this->order_v2->request('GET', $urlInvoice, $queryInvoice);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $dataInvoice = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($dataInvoice->retorno->nota_fiscal)) {
            throw new InvalidArgumentException("Nota fiscal ($idInvoice) não localizada");
        }

        return $dataInvoice->retorno->nota_fiscal;
    }

    /**
     * Recupera dados da nota fiscal do pedido.
     *
     * @param   string  $orderIdIntegration Dados do pedido da integradora.
     * @param int $orderid Código do pedido no Seller Center
     * @return  array                       Dados de nota fiscal do pedido [date, value, serie, number, key].
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderid): array
    {
        // Obter dados do pedido       
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
            if ($order->situacao == 'Em aberto') {
                $this->setApprovePayment($orderid, $orderIdIntegration);
            }
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // Obter dados do pedido
        try {
            $invoice = $this->getInvoiceOrderIntegration($order->id_nota_fiscal);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        /**
         * Se a situação diferir de 6 e 7, ainda não foi realmente faturado.
         * Pode existir a nota no pedido, mas ainda não foi autorizada na SEFAZ.
         *
         *  1 — Pendente
         *  2 — Emitida
         *  3 — Cancelada
         *  4 — Enviada — Aguardando recibo
         *  5 — Rejeitada
         *  6 — Autorizada
         *  7 — Emitida DANFE
         *  8 — Registrada
         *  9 — Enviada — Aguardando protocolo
         * 10 — Denegada
         */
        
        $dateEmission = DateTime::createFromFormat(DATE_BRAZIL, $invoice->data_emissao)->format(DATE_INTERNATIONAL);

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'invoice_order',
            'request'        => json_encode(array('id' => $order->id_nota_fiscal), JSON_UNESCAPED_UNICODE),
            'request_method' => 'GET',
            'response'       => json_encode($invoice, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => 'nota.fiscal.obter.php'
        ));

        return array(
            'date'      => "$dateEmission $invoice->hora_saida:00",
            'value'     => roundDecimal($invoice->valor_nota),
            'serie'     => (int)clearBlanks($invoice->serie),
            'number'    => (int)clearBlanks($invoice->numero),
            'key'       => clearBlanks($invoice->chave_acesso ?? null),
            'isDelivered' => $order->data_entrega ?? null
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
        $urlCancelOrder = "pedido.alterar.situacao";
        $queryCancelOrder = array(
            'query' => array(
                'id'        => $orderIntegration,
                'situacao'  => 'cancelado'
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlCancelOrder, $queryCancelOrder);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order,
            'type'           => 'cancel_order',
            'request'        => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : null,
            'request_method' => 'POST',
            'response'       => '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlCancelOrder
        ));

        return true;
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

        $urlApprovePayment = "pedido.alterar.situacao";
        $queryApprovePayment = array(
            'query' => array(
                'id'        => $orderIntegration,
                'situacao'  => 'aprovado'
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlApprovePayment, $queryApprovePayment);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possível aprovar o pagamento do pedido {$order}. {$exception->getMessage()}");
        }

        return $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'confirm_payment',
            'request'        => json_encode($queryApprovePayment, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlApprovePayment
        ));
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
            $order = $this->getOrderIntegration();
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $itemsTracking = array();

        try {
            $labelsLink = $this->getLabelTrackingIntegration($orderIntegration);
        } catch (InvalidArgumentException $exception) {
            $labelsLink = array();
        }

        foreach ($items as $key => $item) {
            $label = $labelsLink[$key]->link ?? $labelsLink[0]->link ?? null;
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => $order->nome_transportador,
                'trackingCode'              => $order->codigo_rastreamento,
                'trackingUrl'               => $order->url_rastreamento,
                'generatedDate'             => dateNow()->format(DATETIME_INTERNATIONAL),
                'shippingMethodName'        => $order->forma_frete,
                'shippingMethodCode'        => 0,
                'deliveryValue'             => $order->valor_frete,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => $label,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null,
                'isDelivered'               => $order->data_entrega ?? null
            );
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'get_tracking',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($order, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => null
        ));

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
        // request to update status
        $urlReadyShippingOrder = "pedido.alterar.situacao";
        $queryReadyShippingOrder = array(
            'query' => array(
                'id'        => $orderIntegration,
                'situacao'  => 'pronto_envio'
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlReadyShippingOrder, $queryReadyShippingOrder);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_tracking',
            'request'        => json_encode($queryReadyShippingOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlReadyShippingOrder
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
            $order = $this->getOrderIntegration();
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'get_shipped',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($order, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => null
        ));
        
        $shippedDate = array(
                'isDelivered' => $order->data_entrega ?? null,
                'date' => dateBrazilToDateInternational($order->data_envio) . " 00:00:00"
            );
        return $shippedDate;
    
        //return dateBrazilToDateInternational($order->data_envio) . " 00:00:00";
    }

    /**
     * Recupera ocorrências do rastreio.
     * @todo criar funcionalidade para a integradora. Tiny ainda não tem ocorrência de rastreio.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  array                       Dados de ocorrência do pedido. Um array com as chaves 'isDelivered' e 'occurrences', onde 'occurrences' será os dados de ocorrência do rastreio, composto pelos índices 'date' e 'occurrence'.
     * @throws  InvalidArgumentException
     */
    public function getOccurrenceIntegration(string $orderIntegration): array
    {
        $isDelivered = false;
        $dateDelivered = null;

        try {
            $order = $this->getOrderIntegration();
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (empty($order->data_entrega)) {
            // Pedido entregue, mas sem data de entrega.
            if ($order->situacao == 'Entregue') {
                $isDelivered = true;
                $dateDelivered = dateNow()->format(DATETIME_INTERNATIONAL);
            }
        } else {
            $isDelivered = true;
            $dateDelivered = dateBrazilToDateInternational($order->data_entrega) . " 00:00:00";
        }

        return array(
            'isDelivered'   => $isDelivered,
            'dateDelivered' => $dateDelivered,
            'occurrences'   => array()
        );
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
        // Obter dados do pedido
        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (empty($order->data_envio)) {
            // request to update status
            $urlShippedOrder = "pedido.alterar.situacao";
            $queryShippedOrder = array(
                'query' => array(
                    'id'        => $orderIntegration,
                    'situacao'  => 'enviado'
                )
            );

            try {
                $request = $this->order_v2->request('POST', $urlShippedOrder, $queryShippedOrder);
                $response = Utils::jsonDecode($request->getBody()->getContents());
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'set_in_transit',
                'request'        => json_encode($queryShippedOrder, JSON_UNESCAPED_UNICODE),
                'request_method' => 'POST',
                'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
                'response_code'  => $request->getStatusCode(),
                'request_uri'    => $urlShippedOrder
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
            $order = $this->getOrderIntegration();
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (empty($order->data_entrega)) {
            // Pedido entregue, mas sem data de entrega.
            if ($order->situacao == 'Entregue') {
                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'get_delivered',
                    'request'        => null,
                    'request_method' => 'GET',
                    'response'       => json_encode($order, JSON_UNESCAPED_UNICODE),
                    'response_code'  => 200,
                    'request_uri'    => null
                ));

                return dateNow()->format(DATETIME_INTERNATIONAL);
            }

            return '';
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'get_delivered',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($order, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => null
        ));

        return dateBrazilToDateInternational($order->data_entrega) . " 00:00:00";
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
        // request to update status
        $urlDeliveredOrder = "pedido.alterar.situacao";
        $queryDeliveredOrder = array(
            'query' => array(
                'id'        => $orderIntegration,
                'situacao'  => 'entregue'
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlDeliveredOrder, $queryDeliveredOrder);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_delivered',
            'request'        => json_encode($queryDeliveredOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlDeliveredOrder
        ));

        return true;
    }

    /**
     * Recupera a etiqueta do rastreio da integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @return  array                       URL de rastreio.
     * @throws  InvalidArgumentException
     */
    public function getLabelTrackingIntegration(string $orderIntegration): array
    {
        try {
            $expedition = $this->getExpeditionIntegration();
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $urlExpedition = "expedicao.obter.etiquetas.impressao.php";
        $queryExpedition = array(
            'query' => array(
                'idAgrupamento' => $expedition->idAgrupamento //750473983
            )
        );

        try {
            $request = $this->order_v2->request('GET', $urlExpedition, $queryExpedition);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $dataExpedition = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($dataExpedition->retorno->links)) {
            throw new InvalidArgumentException("Etiquetas para o pedido ($this->orderIdIntegration) não encontrada.");
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'get_label',
            'request'        => json_encode($queryExpedition, JSON_UNESCAPED_UNICODE),
            'request_method' => 'GET',
            'response'       => !empty($dataExpedition) ? json_encode($dataExpedition, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlExpedition
        ));

        return $dataExpedition->retorno->links;
    }
}
