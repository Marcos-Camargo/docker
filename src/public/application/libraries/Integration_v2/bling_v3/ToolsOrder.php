<?php

namespace Integration\bling_v3;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Integration\Integration_v2\Order_v2;

class ToolsOrder
{
    /**
     * @var Order_v2
     */
    public $order_v2;

    /**
     * @var int Código do pedido no seller center
     */
    public $orderId;

    /**
     * @var string Código do pedido na integradora
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
        $payments           = $order->payments;

        if (in_array($order->status->code, array(1,2,96))) {
            throw new InvalidArgumentException('Pedido Aguardando confirmação de pagamento, só será integrado após Pagamento Confirmado!');
        }

        try {
            $client_id = $this->saveClient($customer, $billing_address);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Não foi possível criar o contato. {$exception->getMessage()}");
        }

        $newOrder = array(
            'data'                  => dateFormat($order->created_at, DATE_INTERNATIONAL),
            'numeroLoja'            => $order->code,
            'observacoesInternas'   => "Pedido {$this->order_v2->nameSellerCenter}: $order->code",
            "dataPrevista"          => $order->shipping->estimated_delivery,
            'contato' => array(
                'id'                => $client_id,
                "nome"              => $shipping_address->full_name,
                "tipoPessoa"        => $customer->person_type === 'pf' ? "F" : "J",
                "numeroDocumento"   => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj)
            ),
            "situacao" => array(
                "id" => $this->getIdSituationOrder('Em aberto'),
            ),
            'itens' => array(),
            'parcelas' => array(),
            "transporte" => array(
                "frete" => $order->shipping->seller_shipping_cost,
                "prazoEntrega" => $order->shipping->estimated_delivery_days,
                "contato" => array(
                    "id" => 0,
                    "nome" => "{$order->shipping->shipping_carrier} - {$order->shipping->service_method}"
                ),
                "etiqueta" => array(
                    "nome"          => $shipping_address->full_name,
                    "endereco"      => $shipping_address->street,
                    "numero"        => $shipping_address->number,
                    "complemento"   => $shipping_address->complement,
                    "municipio"     => $shipping_address->city,
                    "uf"            => $shipping_address->region,
                    "cep"           => $shipping_address->postcode,
                    "bairro"        => $shipping_address->neighborhood,
                    "nomePais"      => "BRASIL"
                ),
                "volumes" => array()
            )
        );

        /*
         * Desconto vai nos itens
         *
        if (!empty($payments->discount)) {
            $newOrder['desconto'] = array(
                "valor"     => $payments->discount,
                "unidade"   => "REAL"
            );
        }*/

        if (!empty($this->order_v2->credentials->loja_bling)) {
            $newOrder['loja']['id'] = $this->order_v2->credentials->loja_bling;
        }

        // Produtos
        $pesoTransporte = 0;
        $qtyVolumes = 0;
        foreach ($order->items as $item) {
            $percentDiscountItem = 0;
            if ($item->discount > 0) {
                $percentDiscountItem = floatval($item->discount) / floatval($item->original_price);
                $percentDiscountItem = $percentDiscountItem * 100;
            }

            $product = $this->order_v2->getProductForSku($item->sku);
            $productsPackage = $product['products_package'] ?? 1;
            if ((int)$productsPackage <= 0) {
                $productsPackage = 1;
            }
            $qtyVolume = ceil($item->qty / $productsPackage);

            if ($item->sku_integration == null) {
                $msgError = "Não foi encontrado o código na integradora do item para integrar! SKU=$item->sku. VARIANT=$item->sku_variation";
                echo "$msgError\n";
                continue;
            }

            $original_price = $item->original_price;
            if ($percentDiscountItem > 0) {
                $original_price -= $original_price * ($percentDiscountItem/100);
            }

            $newOrder['itens'][] = array(
                'codigo'        => trim($item->sku_variation ?? $item->sku),
                'descricao'     => trim($item->name),
                'unidade'       => $item->unity,
                'quantidade'    => $item->qty,
                'valor'         => roundDecimal($item->original_price),
                'desconto'      => roundDecimal($percentDiscountItem, 3),
                'produto'       => array(
                    'id' => $item->sku_integration
                ),
                "comissao" => array(
                    "base" => roundDecimal($original_price) * $item->qty
                )
            );
            $qtyVolumes += $qtyVolume;
            $pesoTransporte += (float)$item->gross_weight;
        }

        // identifica logistica do seller para enviar o tipo de frete
        $logistic = $this->order_v2->calculofrete->getLogisticStore([
            'freight_seller' => $this->order_v2->dataStore['freight_seller'],
            'freight_seller_type' => $this->order_v2->dataStore['freight_seller_type'],
            'store_id' => $this->order_v2->dataStore['id']
        ]);

        /**
         * fretePorConta
         * 0 - Contratação do Frete por conta do Remetente (CIF)
         * 1 - Contratação do Frete por conta do Destinatário (FOB)
         * 2 - Contratação do Frete por conta de Terceiros
         * 3 - Transporte Próprio por conta do Remetente
         * 4 - Transporte Próprio por conta do Destinatário
         * 9 - Sem Ocorrência de Transporte
         */
        $newOrder['transporte']["fretePorConta"] = $logistic['seller'] ? 0 : 1;
        $newOrder['transporte']["quantidadeVolumes"] = $qtyVolumes;
        $newOrder['transporte']["pesoBruto"] = roundDecimal($pesoTransporte, 3);

        $paymentValid = $this->getNormalizePaymentType($payments->parcels[0]->payment_type ?? '');
        try {
            $formPayment = $this->getFormPayment($paymentValid);
        } catch (ClientException | InvalidArgumentException $exception) {
            try {
                $formPayment = $this->getFormPayment();
            } catch (ClientException | InvalidArgumentException $_exception) {
                $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>{$exception->getMessage()}</li></ul>", "E");
                throw new InvalidArgumentException($exception->getMessage());
            }
        }

        $newOrder['parcelas'][] = array(
            'dataVencimento'    => dateFormat($payments->parcels[0]->date_payment ?? $order->created_at, DATE_INTERNATIONAL),
            'valor'             => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
            'formaPagamento'    => array(
                'id' => $formPayment
            )
        );

        $cnpjIntermediary = $this->order_v2->model_settings->getValueIfAtiveByName('financial_intermediary_cnpj');
        $razaoIntermediary = $this->order_v2->model_settings->getValueIfAtiveByName('financial_intermediary_corporate_name');
        if ($cnpjIntermediary && $razaoIntermediary) {
            $newOrder['intermediador']['cnpj'] = $cnpjIntermediary;
            $newOrder['intermediador']['nomeUsuario'] = $razaoIntermediary;
        }

        $urlOrder = "pedidos/vendas";
        $queryOrder = array(
            'json' => $newOrder
        );

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException $exception) {
            $message = Utils::jsonDecode(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $message = $this->getErrorRequest($message);
            throw new InvalidArgumentException($message);
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());

        $idBling = $contentOrder->data->id ?? null;

        if (empty($idBling)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Houve um conflito de pedidos no bling.</li></ul>", "E");
            throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora.");
        }

        $this->setStockOrder($idBling);
        //$this->setBillOrder($idBling);

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
            'id' => $idBling,
            'request' => "$urlOrder\n" . Utils::jsonEncode($queryOrder, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Normaliza os tipos de pagamentos
     *
     * @return string Tipo de pagamento
     */
    private function getNormalizePaymentType(string $payment): string
    {
        if (
            likeText("%ticket%", strtolower($payment)) ||
            likeText("%boleto%", strtolower($payment))
        ) {
            $paymentValid = 'Boleto Bancário';
        } elseif (
            likeText("%debit%", strtolower($payment))||
            likeText("%débit%", strtolower($payment))
        ) {
            $paymentValid = 'Cartão de Débito';
        } elseif (
            likeText("%card%", strtolower($payment)) ||
            likeText("%visa%", strtolower($payment)) ||
            likeText("%elo%", strtolower($payment)) ||
            likeText("%credit%", strtolower($payment))
        ) {
            $paymentValid = 'Cartão de Crédito';
        } elseif (
            likeText("%voucher%", strtolower($payment)) ||
            likeText("%conta a receber%", strtolower($payment))
        ) {
            $paymentValid = 'Voucher';
        } else {
            $paymentValid = 'Dinheiro';
        }

        return $paymentValid;
    }

    /**
     * Recupera o ID da forma de pagamento
     * Se não encontrar cria o tipo de pagamento
     *
     * @return string|int Código da forma de pagamento
     */
    private function getFormPayment(string $paymentValid = null)
    {
        try {
            $options = array(
                'query' => array(
                    'situacao' => 1
                )
            );

            if ($paymentValid) {
                $options['query']['descricao'] = $paymentValid;
            }

            $request = $this->order_v2->request('GET', 'formas-pagamentos', $options);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
        }

        $contentPayment  = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($contentPayment->data[0])) {
            throw new InvalidArgumentException("Pagamento $paymentValid não localizado");
        }

        return $contentPayment->data[0]->id;
    }

    /**
     * @param   object      $object Objeto de retorno para validar se existe algum erro
     * @return  string              Caso encontre algum erro retornará um array, caso contrário, nulo.
     */
    private function getErrorRequest(object $object): string
    {
        $errorRequest = '';
        if (property_exists($object, 'error') && property_exists($object->error, 'description')) {
            $errorRequest = $object->error->description;
        }
        if (property_exists($object, 'error') && property_exists($object->error, 'fields')) {
            $errorRequest .= ' ' . implode(', ', array_map(function ($error){
                    return $error->msg;
                },
                $object->error->fields));
        }

        $this->order_v2->log_integration(
            "Erro para integrar o pedido ($this->orderId)",
            is_null($errorRequest) ?
                "<h4>Não foi possível integrar o pedido $this->orderId</h4> <p>Ocorreu um problema inesperado, em breve tentaremos integrar novamente.</p>" :
                "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>$errorRequest</li></ul>",
            "E"
        );

        return $errorRequest;
    }

    /**
     * Recupera dados do pedido na integradora
     *
     * @param   string|int      $order  Código do pedido na integradora
     * @return  array|object            Dados do pedido na integradora
     */
    public function getOrderIntegration($order)
    {
        $urlOrder = "pedidos/vendas/$order";

        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
        }

        $order = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($order->data)) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        return $order->data;
    }

    /**
     * Recupera dados da nota fiscal do pedido
     *
     * @param   string  $orderIdIntegration Dados do pedido da integradora
     * @param int $orderid Código do pedido no Seller Center
     * @return  array                       Dados de nota fiscal do pedido [date, value, serie, number, key]
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderid): array
    {
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado.');
        }

        // Obter dados do pedido
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (ENVIRONMENT === 'development') {
            $data_nfe = $this->order_v2->generateNfe();
            $invoice = Utils::jsonDecode('{"data":{"dataEmissao":"'.$data_nfe['date'].'","valorNota":"'.$order->total.'","serie":"'.$data_nfe['serie'].'","numero":"'.$data_nfe['number'].'","chaveAcesso":"'.$data_nfe['key'].'"}}');
            $invoice = $invoice->data;
        } else {
            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (empty($order->notaFiscal->id)) {
                throw new InvalidArgumentException('Ainda não faturado');
            }

            $invoice_id = $order->notaFiscal->id;

            $urlOrder = "nfe/$invoice_id";

            try {
                $request = $this->order_v2->request('GET', $urlOrder);
            } catch (ClientException|InvalidArgumentException $exception) {
                throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            }

            $invoice = Utils::jsonDecode($request->getBody()->getContents());

            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (empty($invoice->data)) {
                throw new InvalidArgumentException("Nota fiscal $invoice_id não faturado");
            }

            $invoice = $invoice->data;

            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (empty($invoice->chaveAcesso) || empty($invoice->numero)) {
                throw new InvalidArgumentException('Ainda não faturado');
            }

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'invoice_order',
                'request'        => null,
                'response'       => json_encode($invoice, JSON_UNESCAPED_UNICODE),
                'request_method' => 'POST',
                'response_code'  => $request->getStatusCode(),
                'request_uri'    => $urlOrder
            ));
        }

        return array(
            'date'          => $invoice->dataEmissao,
            'value'         => roundDecimal($invoice->valorNota),
            'serie'         => (int)$invoice->serie,
            'number'        => (int)clearBlanks($invoice->numero),
            'key'           => clearBlanks($invoice->chaveAcesso)
        );
    }

    /**
     * Cancelar pedido na integradora
     *
     * @param   int     $order              Código do pedido no Seller Center
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function cancelIntegration(int $order, string $orderIntegration): bool
    {
        // Pedido já foi cancelado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        $urlStatus = "pedidos/vendas/$orderIntegration/situacoes/{$this->getIdSituationOrder('Cancelado')}";

        try {
            $request = $this->order_v2->request('PATCH', $urlStatus);
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException  $exception) {
            $this->order_v2->log_integration("Erro para atualizar situação do pedido ($this->orderId)", "<h4>Não foi possível atualizar a situação do pedido $this->orderId</h4> <p>Ocorreu um problema para atualizar a situação do pedido para Cancelado. O processo deve ser feito manualmente.</p>", "E");
            throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order,
            'type'           => 'cancel_order',
            'request'        => null,
            'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'request_method' => 'PATCH',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlStatus
        ));

        return true;
    }

    /**
     * Recupera dados de tracking
     * @todo criar funcionalidade para a integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   array   $items              Itens do pedido
     * @return  array                       Array onde, a chave de cada item do array será o código SKU e o valor do item um array com os dados: quantity, shippingCompany, trackingCode, trackingUrl, generatedDate, shippingMethodName, shippingMethodCode, deliveryValue, documentShippingCompany, estimatedDeliveryDate, labelA4Url, labelThermalUrl, labelZplUrl, labelPlpUrl
     * @throws  InvalidArgumentException
     */
    public function getTrackingIntegration(string $orderIntegration, array $items): array
    {
        $this->checkOrderCanceled();
        return array();
    }

    /**
     * Importar a dados de rastreio
     * @todo criar funcionalidade para a integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order})
     * @param   object  $dataTracking       Dados do rastreio do pedido (Api/V1/Tracking/{order})
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setTrackingIntegration(string $orderIntegration, object $order, object $dataTracking): bool
    {
        $this->checkOrderCanceled();
        return true;
    }

    /**
     * Recupera data de envio do pedido
     * @todo criar funcionalidade para a integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @return  array                      Data de envio do pedido
     * @throws  InvalidArgumentException
     */
    public function getShippedIntegration(string $orderIntegration)
    {
        $this->checkOrderCanceled();
        return '';
    }

    /**
     * Recupera ocorrências do rastreio
     * @todo criar funcionalidade para a integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @return  array                       Dados de ocorrência do pedido. Um array com as chaves 'isDelivered' e 'occurrences', onde 'occurrences' será os dados de ocorrência do rastreio, composto pelos índices 'date' e 'occurrence'
     * @throws  InvalidArgumentException
     */
    public function getOccurrenceIntegration(string $orderIntegration): array
    {
        $this->checkOrderCanceled();
        return array(
            'isDelivered'   => false,
            'dateDelivered' => null,
            'occurrences'   => array()
        );
    }

    /**
     * Importar a dados de ocorrência do rastreio
     * @todo criar funcionalidade para a integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order})
     * @param   array   $dataOccurrence     Dados de ocorrência
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setOccurrenceIntegration(string $orderIntegration, object $order, array $dataOccurrence): bool
    {
        $this->checkOrderCanceled();
        return true;
    }

    /**
     * Recupera data de entrega do pedido
     * @todo criar funcionalidade para a integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @return  string                      Data de entrega do pedido
     * @throws  InvalidArgumentException
     */
    public function getDeliveredIntegration(string $orderIntegration): string
    {
        $this->checkOrderCanceled();
        return '';
    }

    /**
     * Importar a data de entrega
     * @todo criar funcionalidade para a integradora.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order})
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setDeliveredIntegration(string $orderIntegration, object $order): bool
    {
        $this->checkOrderCanceled();
        return true;
    }

    /**
     * Recupera dados do pedido na integradora
     *
     * @param   string  $situation_name Nome da situação
     * @return  int                     Código da situação
     */
    public function getIdSituationOrder(string $situation_name): int
    {
        $urlSituation = "situacoes/modulos";

        try {
            $request = $this->order_v2->request('GET', $urlSituation);
        } catch (ClientException | InvalidArgumentException $exception) {
            return $this->defaultSituationOrder($situation_name);
        }

        $situation = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($situation->data)) {
            return $this->defaultSituationOrder($situation_name);
        }

        $order_situation = getArrayByValueIn($situation->data, 'Vendas', 'nome');

        if ($order_situation) {
            $urlOrderSituation = "situacoes/modulos/$order_situation->id";

            try {
                $request = $this->order_v2->request('GET', $urlOrderSituation);
            } catch (ClientException | InvalidArgumentException $exception) {
                return $this->defaultSituationOrder($situation_name);
            }

            $rder_situation = Utils::jsonDecode($request->getBody()->getContents());

            if (empty($rder_situation->data)) {
                return $this->defaultSituationOrder($situation_name);
            }

            $order_situation = getArrayByValueIn($rder_situation->data, $situation_name, 'nome');

            if ($order_situation) {
                return $order_situation->id;
            }
        }

        return $this->defaultSituationOrder($situation_name);
    }

    /**
     * Valores padrões de situação de pedidos.
     *
     * @param   string $situation_name  Nome da situação
     * @return  int                     Código da situação
     */
    private function defaultSituationOrder(string $situation_name): int
    {
        switch ($situation_name) {
            case "Em aberto":
                return 6;
            case "Atendido":
                return 9;
            case "Cancelado":
                return 12;
            case "Em andamento":
                return 15;
            case "Venda Agenciada":
                return 18;
            case "Em digitação":
                return 21;
            case "Verificado":
                return 24;
            default:
                return 0;
        }
    }

    private function saveClient($customer, $billing_address)
    {
        $urlContact = "contatos";
        $queryContact = array(
            'query' => array(
                'numeroDocumento'   => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'criterio'          => '3'
            )
        );

        try {
            $requestContact = $this->order_v2->request('GET', $urlContact, $queryContact);
            $responseContact = Utils::jsonDecode($requestContact->getBody()->getContents());
            if (empty($responseContact->data[0])) {
                throw new InvalidArgumentException("Cliente {$queryContact['query']['numeroDocumento']} não encontrado");
            }
            return $responseContact->data[0]->id;
        } catch (ClientException | InvalidArgumentException $exception) {}

        $contact = [
            'nome'              => $billing_address->full_name,
            "codigo"            => $customer->id,
            "situacao"          => "A",
            "numeroDocumento"   => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
            "telefone"          => onlyNumbers($customer->phone[0] ?? $customer->phone[1] ?? ''),
            "celular"           => onlyNumbers($customer->phone[1] ?? $customer->phone[0] ?? ''),
            "fantasia"          => empty($customer->ie) ? '' : $billing_address->full_name,
            "tipo"              => $customer->person_type === 'pf' ? "F" : "J",
            "indicadorIe"       => empty($customer->ie) ? 0 : 1,
            "ie"                => $customer->ie,
            "rg"                => $customer->rg,
//            "orgaoEmissor"    => "1234567890",
            "email"             => $customer->email,
            "endereco" => [
                "geral" => [
                    "endereco" => $billing_address->street,
                    "cep" => $billing_address->postcode,
                    "bairro" => $billing_address->neighborhood,
                    "municipio" => $billing_address->city,
                    "uf" => $billing_address->region,
                    "numero" => $billing_address->number,
                    "complemento" => $billing_address->complement,
                ],
                "cobranca" => [
                    "endereco" => $billing_address->street,
                    "cep" => $billing_address->postcode,
                    "bairro" => $billing_address->neighborhood,
                    "municipio" => $billing_address->city,
                    "uf" => $billing_address->region,
                    "numero" => $billing_address->number,
                    "complemento" => $billing_address->complement,
                ]
            ]
        ];


        $urlOrder = "contatos";
        $queryOrder = array(
            'json' => $contact
        );

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException $exception) {
            $message = Utils::jsonDecode(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $message = $this->getErrorRequest($message);

            throw new InvalidArgumentException($message);
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'create_client',
            'request'        => json_encode($queryOrder, JSON_UNESCAPED_UNICODE),
            'response'       => json_encode($response, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlOrder
        ));

        return $response->data->id;
    }

    private function setStockOrder(string $order)
    {
        try {
            if (empty($this->order_v2->credentials->stock_id_bling)) {
                $url = "pedidos/vendas/$order/lancar-estoque";
            } else {
                $url = "pedidos/vendas/$order/lancar-estoque/{$this->order_v2->credentials->stock_id_bling}";
            }

            $request = $this->order_v2->request('POST', $url);
            $response = Utils::jsonDecode($request->getBody()->getContents());

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'set_stock',
                'request'        => null,
                'response'       => $response ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
                'request_method' => 'POST',
                'request_uri'    => $url,
                'response_code'  => $request->getStatusCode()
            ));
        } catch (ClientException | InvalidArgumentException $exception) {
            $message = Utils::jsonDecode(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $this->getErrorRequest($message);
        }
    }

    private function setBillOrder(string $order)
    {
        try {
            $url = "pedidos/vendas/$order/lancar-contas";
            $this->order_v2->request('POST', $url);
        } catch (ClientException | InvalidArgumentException $exception) {
            $message = Utils::jsonDecode(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $this->getErrorRequest($message);
        }
    }

    public function checkOrderCanceled(): bool
    {
        // Pedido já foi cancelado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        $order          = $this->getOrderIntegration($this->orderIdIntegration);
        $situation_id   = $this->getIdSituationOrder('Cancelado');

        // Cancelar o pedido.
        if ($order->situacao->id == $situation_id) {
            try {
                $this->order_v2->setCancelOrder($this->orderId, dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL), 'Cancelado pelo seller via integradora.');
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException("Não possível realizar o cancelamento do pedido $this->orderId. {$exception->getMessage()}");
            }

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'cancel_order',
                'request'        => null,
                'response'       => '',
                'request_method' => 'POST',
                'request_uri'    => null,
                'response_code'  => 200
            ));

            return true;
        }

        return false;
    }
}