<?php

namespace Integration\bling;

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

        if(in_array($order->status->code, array(1,2,96))) {
            throw new InvalidArgumentException('Pedido Aguardando confirmação de pagamento, só será integrado após Pagamento Confirmado!');
        }

        $paymentType  = $order->payments->parcels[0]->payment_type ?? '';
        $paymentValid = $this->getNormalizePaymentType($paymentType);
        try {
            $formPayment  = $this->getFormPayment($paymentValid);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>{$exception->getMessage()}</li></ul>", "E");
            throw new InvalidArgumentException($exception->getMessage());
        }

        // Na conecta lá, enviamos os dados da fatura igual ao da entrega.
        if ($this->order_v2->sellerCenter === 'conectala') {
            $arrClient = array(
                'nome'          => $shipping_address->full_name,
                'tipoPessoa'    => $customer->person_type === 'pf' ? "F" : "J",
                'cpf_cnpj'      => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'ie'            => $customer->ie,
                'rg'            => $customer->rg,
                'endereco'      => $shipping_address->street,
                'numero'        => $shipping_address->number,
                'complemento'   => $shipping_address->complement,
                'bairro'        => $shipping_address->neighborhood,
                'cep'           => $shipping_address->postcode,
                'cidade'        => $shipping_address->city,
                'uf'            => $shipping_address->region,
                'fone'          => $shipping_address->phone,
                'celular'       => $shipping_address->phone,
                'email'         => $customer->email,
            );
        }
        // não é conecta lá, enviamos o endereço da fatura
        else {
            $arrClient = array(
                'nome'          => $billing_address->full_name,
                'tipoPessoa'    => $customer->person_type === 'pf' ? "F" : "J",
                'cpf_cnpj'      => onlyNumbers($customer->cpf),
                'ie'            => $customer->ie,
                'rg'            => $customer->rg,
                'endereco'      => $billing_address->street,
                'numero'        => $billing_address->number,
                'complemento'   => $billing_address->complement,
                'bairro'        => $billing_address->neighborhood,
                'cep'           => $billing_address->postcode,
                'cidade'        => $billing_address->city,
                'uf'            => $billing_address->region,
                'fone'          => onlyNumbers($customer->phone[0] ?? $customer->phone[1] ?? ''),
                'celular'       => onlyNumbers($customer->phone[1] ?? $customer->phone[0] ?? ''),
                'email'         => $customer->email,
            );
        }

        $newOrder = array(
            'data'              => date('d/m/Y', strtotime($order->created_at)),
            'numero_loja'       => $order->code,
            //'loja'              => $this->order_v2->credentials->loja_bling,
            'vlr_frete'         => $order->shipping->seller_shipping_cost,
            'obs_internas'      => "Pedido {$this->order_v2->nameSellerCenter}: $order->code",
            'vlr_desconto'      => 0, // o desconto vai nos itens
            'idFormaPagamento'  => $formPayment,
            'cliente'           => $arrClient,
            'itens'             => array(
                'item' => array()
            ),
            'parcelas' => array(
                'parcela' => array()
            )
        );

        if (!empty($this->order_v2->credentials->loja_bling)) {
            $newOrder['loja'] = $this->order_v2->credentials->loja_bling;
        }

        // Produtos
        $pesoTransporte = 0;
        $qtyVolumes = 0;
        foreach ($order->items as $item) {
            $percentDiscountItem = 0;
            if ($item->discount > 0) {
                $percentDiscountItem = floatval($item->discount) / floatval($item->original_price);
                $percentDiscountItem = roundDecimal($percentDiscountItem * 100);
            }

            $product = $this->order_v2->getProductForSku($item->sku);
            $productsPackage = $product['products_package'] ?? 1;
            if ((int)$productsPackage <= 0) {
                $productsPackage = 1;
            }
            $qtyVolume = ceil($item->qty / $productsPackage);

            if ($item->sku_variation != null) {
                $sku_variacao = $this->order_v2->getSkuProductVariationOrder($item);
                if ($item->variant_order == $sku_variacao['variant']) {
                    $sku = $item->sku_variation;
                }else{
                    $msgError = "Não foi encontrado o SKU do produto/variação para integrar! SKU={$item->sku}. VARIANT={$item->sku_variation}. PEDIDO={$order->code}";
                    echo "{$msgError}\n";
                    continue;
                }
            }else{
                $sku = $item->sku;
            }

            $newOrder['itens']['item'][] = array(
                'codigo'        => trim($sku),
                'descricao'     => trim($item->name),
                'un'            => $item->unity,
                'qtde'          => $item->qty,
                'vlr_unit'      => roundDecimal($item->original_price),
                'vlr_desconto'  => $percentDiscountItem,
            );
            $qtyVolumes += $qtyVolume;
            $pesoTransporte += (float)$item->gross_weight;
        }

        // Transporte
        $provider['transportadora'] = $order->shipping->shipping_carrier;
        
        // identifica logistica do seller para enviar o tipo de frete
        $logistic = $this->order_v2->calculofrete->getLogisticStore([
            'freight_seller' => $this->order_v2->dataStore['freight_seller'],
            'freight_seller_type' => $this->order_v2->dataStore['freight_seller_type'],
            'store_id' => $this->order_v2->dataStore['id']
        ]);
        if ($logistic['seller']) {
            $provider['tipo_frete'] = 'R';
        }else{
            $provider['tipo_frete'] = 'D';
        }
        
        // Verifica flag de correios.
        if(likeText("%correios%", strtolower($order->shipping->shipping_carrier))||$order->shipping->is_correios){
            $provider['servico_correios'] = $order->shipping->service_method;
        }
        $provider['peso_bruto'] = roundDecimal($pesoTransporte, 3);
        $newOrder['transporte'] = $provider;
        $newOrder['qtde_volumes'] = $qtyVolumes;

        // Endereço de entrega
        $newOrder['transporte']['dados_etiqueta'] = array(
            'nome'          => $shipping_address->full_name,
            'cep'           => $shipping_address->postcode,
            'endereco'      => $shipping_address->street,
            'numero'        => $shipping_address->number,
            'complemento'   => $shipping_address->complement,
            'bairro'        => $shipping_address->neighborhood,
            'municipio'     => $shipping_address->city,
            'uf'            => $shipping_address->region,
        );

        if (count($order->payments->parcels)) {
            foreach ($order->payments->parcels as $payment) {
                $newOrder['parcelas']['parcela'][] = array(
                    'data'  => date('d/m/Y', strtotime($order->payments->date_payment ?? $order->created_at)),
                    'vlr'   => $payment->value,
                    'forma_pagamento' => array(
                        'id' => $formPayment
                    )
                );
            }
        } else {
            $newOrder['parcelas']['parcela'] = array(
                'data'  => date('d/m/Y', strtotime($order->created_at)),
                'vlr'   => $this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount,
                'forma_pagamento' => array(
                    'id' => $formPayment
                )
            );
        }

        try {
            $orderXml = $this->order_v2->arrayToXml($newOrder);
        } catch (Exception $exception) {
            throw new InvalidArgumentException("Não foi possível converter os dados para XML. {$exception->getMessage()}");
        }

        // ocorreu um problema na conversão do array para XML
        if ($orderXml === false) {
            throw new InvalidArgumentException("Não foi possível converter os dados para XML\n" . Utils::jsonEncode($newOrder));
        }

        //$orderXml = str_replace('&amp;', '', $orderXml);

        $urlOrder = "pedido";
        $queryOrder = array(
            'query' => array(
                'gerarnfe'  => false,
                'xml'       => $orderXml
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = Utils::jsonDecode(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $this->getErrorRequest($message);

            throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());

        $errors = $this->getErrorRequest($contentOrder);
        if ($errors !== null) {
            throw new InvalidArgumentException(Utils::jsonEncode($contentOrder));
        }

        $idBling     = $contentOrder->retorno->pedidos[0]->pedido->idPedido ?? null;
        $numeroBling = $contentOrder->retorno->pedidos[0]->pedido->numero ?? null;

        if (empty($idBling) || empty($numeroBling)) {
            $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>Houve um conflito de pedidos no bling.</li></ul>", "E");
            throw new InvalidArgumentException("Não foi possível identificar o código gerado pela integradora.");
        }

        // atualiza situação do pedido para Em aberto
        $this->updateStatusOrder($numeroBling);

        return array(
            'id' => $numeroBling,
            'request' => "$urlOrder\n" .  htmlentities(
                str_replace('\/', '/',
                    str_replace('> <', '><',
                        str_replace('> ', '>',
                            str_replace('   ', '',
                                str_replace('\n', '',
                                    Utils::jsonEncode($queryOrder, JSON_UNESCAPED_UNICODE)
                                )
                            )
                        )
                    )
                )
            )
        );
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
        
        $this->orderId = $order;
        try {
            $request = $this->updateStatusOrder($orderIntegration);            
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException('Erro para atualizar o status do pedido');
        }

        $this->order_v2->log_integration("Pedido ({$this->orderId }) Atualizado com sucesso. ", "<h4>Pagamento do pedido  {$this->orderId} informado com sucesso na integradora.</h4> <ul><li>Status do pedido  {$this->orderId} atualizado para Em Andamento na integradora.</li></ul>", "S");
        return true;
    }   

    /**
     * Normaliza os tipos de pagamentos
     *
     * @return string|int Tipo de pagamento
     */
    private function getNormalizePaymentType(string $payment)
    {
        $paymentValid ='';
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
    private function getFormPayment(string $paymentValid)
    {
        
        try {
            $request = $this->order_v2->request('GET', 'formaspagamento');
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
        }

        $contentPayment  = Utils::jsonDecode($request->getBody()->getContents());
        $paymentStandard = $contentPayment->retorno->formaspagamento[0]->formapagamento->id;
        $contentPayments = $contentPayment->retorno->formaspagamento;
        
        foreach ($contentPayments as $formPayment) {
            if (likeText("%".strtolower($paymentValid)."%", strtolower($formPayment->formapagamento->descricao))) {
                return $formPayment->formapagamento->id;
            }
        }

        switch ($paymentValid) {
            case "Boleto Bancário":
                $taxCode = 15;
                break;
            case "Cartão de Débito":
                $taxCode = 4;
                break;
            case "Cartão de Crédito":
                $taxCode = 3;
                break;
            default:
                $taxCode = 1;
                break;
        } 

        $newPaymentForm = array(
            'descricao'       => $paymentValid,
            'codigofiscal'       => $taxCode
        );

        try {
            $newPaymentFormXml = $this->order_v2->arrayToXml($newPaymentForm,"formapagamento");
        } catch (Exception $exception) {
            return $paymentStandard ?? '';
        }

        if ($newPaymentFormXml === false) {
            return $paymentStandard ?? '';
        }

        $urlPaymentForm = "formapagamento";
        $queryPaymentForm = array(
            'query' => array(
                'xml'       => $newPaymentFormXml
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlPaymentForm, $queryPaymentForm);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {            
            return $contentPayment->retorno->formaspagamento[0]->formapagamento->id ?? '';
        }     

        $newContentPaymentForm = Utils::jsonDecode($request->getBody()->getContents());

        $errors = $this->getErrorRequest($newContentPaymentForm);
        if ($errors !== null) {
            return $paymentStandard ?? '';
        }

        return $newContentPaymentForm->retorno->formaspagamento[0]->id ?? $paymentStandard ?? '';
    }

    /**
     * Atualiza a situação do pedido para "6-Em aberto" após a criação
     *
     * @param   int $idBling    Código do pedido no Bling
     */
    public function updateStatusOrder(int $idBling)
    {
        $convertXml = $this->order_v2->arrayToXml(array('idSituacao' => 6));

        // ocorreu um problema na conversão do array para XML
        if ($convertXml === false) {
            throw new InvalidArgumentException('Não foi possível converter os dados para XML');
        }

        $queryUpdateOrder = array(
            'query' => array(
                'xml' => $convertXml
            )
        );

        try {
            $this->order_v2->request('PUT', "pedido/$idBling", $queryUpdateOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->order_v2->log_integration("Erro para atualizar situação do pedido ($this->orderId)", "<h4>Não foi possível atualizar a situação do pedido $this->orderId</h4> <p>Ocorreu um problema para atualizar a situação do pedido para (6-Em Aberto). O processo deve ser feito manualmente.</p>", "E");
            throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
        }
    }

    /**
     * @param   object      $object Objeto de retorno para validar se existe algum erro
     * @return  array|null          Caso encontre algum erro retornará um array, caso contrário, nulo.
     */
    private function getErrorRequest(object $object): ?array
    {
        $errorRequest = null;
        if (property_exists($object, 'retorno') && property_exists($object->retorno, 'erros')) {

            $errorRequest = array();

            if (is_string($object->retorno->erros)) {
                $errorRequest = array($object->retorno->erros);
            }
            else {
                if (is_object($object->retorno->erros)) {
                    $errorRequest = $object->retorno->erros->erro->msg ?? array();
                }
                elseif (is_array($object->retorno->erros)) {
                    foreach ($object->retorno->erros as $error) {
                        if (!isset($error->erro->msg)) {
                            continue;
                        }
                        array_push($errorRequest, $error->erro->msg);
                    }
                }
            }

        }


        if ($errorRequest !== null) {
            $this->order_v2->log_integration(
                "Erro para integrar o pedido ($this->orderId)",
                !count($errorRequest) ?
                    "<h4>Não foi possível integrar o pedido $this->orderId</h4> <p>Ocorreu um problema inesperado, em breve tentaremos integrar novamente.</p>" :
                    "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>" . implode('</li><li>', $errorRequest) . "</li></ul>",
                "E"
            );
        }

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
        $urlOrder = "pedido/$order";

        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
        }

        $order = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($order->retorno->pedidos[0]->pedido)) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        return $order->retorno->pedidos[0]->pedido;
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
        // Obter dados do pedido
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
            /*if ($order->situacao == 'Em aberto') {
                $this->setApprovePayment($orderid, $orderIdIntegration);
            }*/
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
        if (!property_exists($order, 'nota') || empty($order->nota)) {
            throw new InvalidArgumentException('Ainda não faturado');
        }

        $invoice = $order->nota;

        if (ENVIRONMENT === 'development') {
            $invoice->chaveAcesso = '32220129922847000296550010000620671208513540';
        }

        // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
        if (
            !isset($invoice->chaveAcesso) ||
            $invoice->chaveAcesso === null ||
            !isset($invoice->numero) ||
            $invoice->numero === null
        ) {
            throw new InvalidArgumentException('Ainda não faturado');
        }

        return array(
            'date'      => $invoice->dataEmissao,
            'value'     => roundDecimal($invoice->valorNota),
            'serie'     => (int)$invoice->serie,
            'number'    => (int)clearBlanks($invoice->numero),
            'key'       => clearBlanks($invoice->chaveAcesso)
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
        // request to cancel order
        $urlCancelOrder = "pedido/$orderIntegration";
        $cancelOrder = array('idSituacao' => 12);
        $orderCancelXml = $this->order_v2->arrayToXml($cancelOrder);
        
        // ocorreu um problema na conversão do array para XML
        if ($orderCancelXml === false) {
            throw new InvalidArgumentException("Não foi possível converter os dados para XML\n" . Utils::jsonEncode($cancelOrder));
        }

        $queryCancelOrder = array(
            'query' => array(
                'xml' => $orderCancelXml
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlCancelOrder, $queryCancelOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $contentOrder = Utils::jsonDecode($request->getBody()->getContents());
        $errors = $this->getErrorRequest($contentOrder);
        if ($errors !== null) {
            foreach ($errors as $error) {
                if (likeText("%Não há transições definidas para esta entidade%", $request->getBody()->getContents())) {
                    return true;
                }
            }

            throw new InvalidArgumentException(Utils::jsonEncode($contentOrder));
        }

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
        return true;
    }

    /**
     * Recupera o SKU do produto/variação vendido
     *
     * @param   array       $item   Array com dados do produto vendido
     * @return  false|string        Retorna o sku do produto ou variação, em caso de erro retorna false
     */
    public function getSkuProductVariationOrder($Item)
    {
        if ($Item->sku_variation) return $Item->product_id;

        $var = $this->db
            ->get_where('prd_variants',
                array(
                    'prd_id'    => $Item->product_id,
                    'variant'   => $Item->sku_variation
                )
            )->row_array();

        if (!$var) return false;

        return $var['sku'];


    }
}