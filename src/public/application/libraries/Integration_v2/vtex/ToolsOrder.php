<?php
/**
 * @todo O que fazer? "O pedido com marketplace id 201 para o afiliado CNL já existe".
 */

namespace Integration\vtex;

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
     * @var int Código do pedido no seller center
     */
    public $orderId;

    /**
     * @var string Código do pedido na integradora
     */
    public $orderIdIntegration;

    private $configuration_checkout_app_id = 'integration-marketplace-conectala-payment';

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
        $payments           = $order->payments;
        $this->orderId      = $order->code;

        // Cliente
        $nameComplete = explode(" ", trim($customer->name));

        // Separa o nome do sobrenome para enviar em campos diferente
        $lastName = $nameComplete[count($nameComplete) - 1];
        unset($nameComplete[count($nameComplete) - 1]);
        $firstName = implode(" ", $nameComplete);
        $firstName = empty($firstName) ? $lastName : $firstName;

        if($customer->person_type === 'pj'){
            $corporateName = $firstName." ".$lastName;
            $tradeName = $firstName." ".$lastName;
            $corporateDocument = $customer->cnpj;
            $isCorporate = true;
        } else{
            $corporateName = null;
            $tradeName = null;
            $corporateDocument = null;
            $isCorporate = false;
        }

        // Inicia dados do pedido
        $newOrder = array(
            'marketplaceOrderId' => $order->marketplace_number,
            'marketplaceServicesEndpoint' => $this->getCallbackUrl().'Api/Integration_v2/vtex/ServicesOrder',
            'marketplacePaymentValue' => moneyFloatToVtex($this->order_v2->sellerCenter === 'somaplace' ? $order->payments->net_amount : $order->payments->gross_amount),
            'clientProfileData' => array(
                'id'                => 'clientProfileData',
                'email'             => empty($customer->email) ? 'client@vtex.com' : $customer->email,
                'firstName'         => $firstName,
                'lastName'          => $lastName,
                'document'          => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'documentType'      => $customer->person_type === 'pf' ? "cpf" : "cnpj",
                'phone'             => onlyNumbers($customer->phones[0] != '' ? $customer->phones[0] : '47936686244'),
                'corporateName'     => $corporateName,
                'tradeName'         => $tradeName,
                'corporateDocument' => $corporateDocument,
                'stateInscription'  => $customer->ie,
                'corporatePhone'    => null,
                'isCorporate'       => $isCorporate,
                'userProfileId'     => null
            ),
            'shippingData' => array(
                'isFOB' => true, // fob=true ==> transporte por conta do marketplace
                'id'    => 'shippingData',
                'address' => array(
                    'addressType'       => 'Residencial',
                    'receiverName'      => $shipping_address->full_name,
                    'postalCode'        => onlyNumbers($shipping_address->postcode),
                    'city'              => $shipping_address->city,
                    'state'             => $shipping_address->region,
                    'country'           => 'BRA',
                    'street'            => $shipping_address->street,
                    'number'            => $shipping_address->number,
                    'neighborhood'      => $shipping_address->neighborhood,
                    'complement'        => $shipping_address->complement,
                    'reference'         => $shipping_address->reference,
                    'geoCoordinates'    => array(),
                ),
                'logisticsInfo' => array()
            ),
            'items' => array(),
            'paymentData' => null
        );
        try {
            $observation = $this->order_v2->validarCamposObrigatoriosEPopularObservation($this->orderId, $this->order_v2->store);
            
            if($observation){
                $newOrder['openTextField'] = (string) $observation;
            }
        
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Erro ao validar campos obrigatórios para o pedido {$this->orderId}: " . $e->getMessage());
        }

        $countIndex = 0;
        foreach ($order->items as $item) {
            $consult_vtex_value_to_order = $this->order_v2->model_settings->getValueIfAtiveByName('consult_vtex_value_to_order');
            if ($consult_vtex_value_to_order){
                $skuLog = $item->sku_variation ?? $item->sku;

                try {
                    $itemSimulation = $this->getProductInStock($item->sku_integration, $item->qty);
                    $getSkuCurrent = $itemSimulation->price/100;
                } catch (InvalidArgumentException $exception) {
                    $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "Não foi possível integrar o pedido $this->orderId.<br><ul><li>Não foi encontrado estoque suficiente para envio do pedido! SKU: $skuLog.</li></ul>", "E");
                    throw new InvalidArgumentException($exception->getMessage());
                }
            }else{
                $getSkuCurrent = $this->order_v2->getPriceInternalSku($order->system_marketplace_code, $item->sku, $item->sku_variation);
                if (!$getSkuCurrent) {
                    $getSkuCurrent = $item->original_price;
                }
            }


            $priceTags = array();
            if ($item->discount > 0) {
                $priceTags[] = array(
                    "name"          => "Desconto marketplace",
                    "value"         => -moneyFloatToVtex($item->discount * (int)$item->qty),
                    "isPercentual"  => false,
                    "identifier"    => null,
                    "rawValue"      => -roundDecimal($item->discount * (int)$item->qty),
                    "rate"          => null,
                    "jurisCode"     => null,
                    "jurisType"     => null,
                    "jurisName"     => null
                );
            }

            $price      = roundDecimal($item->original_price);
            $priceSku   = roundDecimal($getSkuCurrent);

            if ($price != $priceSku) {
                $discountDivergenceCampanha = $priceSku - $price;
                $priceWithQuantity = $discountDivergenceCampanha * (int)$item->qty; // pricetags vai com desconto

                $arrPriceTags = array(
                    "isPercentual"  => false,
                    "identifier"    => null,
                    "rate"          => null,
                    "jurisCode"     => null,
                    "jurisType"     => null,
                    "jurisName"     => null
                );

                if ($discountDivergenceCampanha < 0) {
                    $priceWithQuantity *= -1;
                    $price -= ($discountDivergenceCampanha*(-1));
                    $arrPriceTags["name"]       = "Acréscimo divergência preço atualizado";
                    $arrPriceTags["value"]      = moneyFloatToVtex($priceWithQuantity);
                    $arrPriceTags["rawValue"]   = roundDecimal($priceWithQuantity);
                } else {
                    $price += $discountDivergenceCampanha;
                    $arrPriceTags["name"]       = "Desconto divergência preço atualizado";
                    $arrPriceTags["value"]      = -moneyFloatToVtex($priceWithQuantity);
                    $arrPriceTags["rawValue"]   = -roundDecimal($priceWithQuantity);
                }
                array_push($priceTags, $arrPriceTags);
            }

            // Adiciona os itens no array newOrder
            $newOrder['items'][] = array(
                'id'                => $item->sku_integration,
                'quantity'          => (int)$item->qty,
                'seller'            => "1", // seller 1 é o principal VTEX
                'commission'        => 0,
                'freightCommission' => 0,
                'price'             => moneyFloatToVtex($price),
                'bundleItems'       => [],
                'attachments'       => [],
                'priceTags'         => $priceTags,
                'measurementUnit'   => null,
                'rewardValue'       => 0,
                'isGift'            => false,
                "itemAttachment"    => [
                    "name"      => null,
                    "content"   => []
                ]
            );

            /*
             Sugestão de envio de pedido sem identificação da doca. chamado https://support.vtex.com/hc/pt-br/requests/450041
             1) Enviar o 'selectedSLA' como 'null'
                Nesse caso o marketplace não possui uma doca ou estoque acordado com o seller para ser descontado.
                Ao passar o objeto dessa forma a VTEX associa a automaticamente a doca e estoque mais vantajosos para criação do pedido.
                "logisticsInfo": [
                    {
                        "itemIndex": 0,
                        "selectedSla": null,
                        "addressId": null,
                        "selectedDeliveryChannel": "delivery",
                        "deliveryIds": [],
                        "lockTTL": "10d",
                        "shippingEstimate": "9bd",
                        "bundlePrice": 1000,
                        "price": 1000,
                        "deliveryWindow": null
                    }
                ]
            $dataLogisticsInfo = array(
                'itemIndex'         => $countIndex,
                'selectedSla'       => null,
                'addressId'         => null,
                'selectedDeliveryChannel'   => "delivery",
                "deliveryIds"       => [],
                'lockTTL'           => "0bd",
                'shippingEstimate'  => "{$orderMain['ship_time']}bd",
                'price'             => 0,
                'deliveryWindow'    => null
            );
             */

            $dataLogisticsInfo = array(
                'itemIndex'         => $countIndex,
                'selectedSla'       => null,
                //'deliverycompany' => "vtex:fob_{$order->shipping->service_method}",
                'lockTTL'           => "0bd", // dias de reserva
                'shippingEstimate'  => "{$order->shipping->estimated_delivery_days}bd",
                'price'             => 0,
                'deliveryWindow'    => null
            );

            if ($this->order_v2->getStoreOwnLogistic()) {
                $dataLogisticsInfo['selectedSla']       = $order->shipping->service_method;
                $dataLogisticsInfo['deliverycompany']   = $order->shipping->service_method;

                // fob=true ==> transporte por conta do marketplace
                $newOrder['shippingData']['isFOB'] = false;
            }

            $newOrder['shippingData']['logisticsInfo'][] = $dataLogisticsInfo;
            $countIndex++;
        }

        // calcula o valor do frete para dividir entre os itens proporcionalmente
        $priceFreightTotal = (float)$order->shipping->seller_shipping_cost;
        $priceFreight = $priceFreightTotal;
        $priceFreightPeItem = $priceFreightTotal / count($newOrder['shippingData']['logisticsInfo']);
        foreach ($newOrder['shippingData']['logisticsInfo'] as $keyLogisticInfo => $logisticInfo) {
            if (($keyLogisticInfo + 1) == count($newOrder['shippingData']['logisticsInfo'])) {
                $newOrder['shippingData']['logisticsInfo'][$keyLogisticInfo]['price'] = moneyFloatToVtex($priceFreight);
                continue;
            }
            $priceFreightTemp = roundDecimal($priceFreightPeItem);
            $priceFreight = $priceFreight - $priceFreightTemp;

            $newOrder['shippingData']['logisticsInfo'][$keyLogisticInfo]['price'] = moneyFloatToVtex($priceFreightTemp);
        }

        if (!empty($payments)) {
            if (!empty($payments->parcels)) {
                $newOrder['customData'] = array(
                    "customApps" => array()
                );
            }

            foreach ($payments->parcels as $parcel_key => $parcel) {
                try {
                    $app_key = $parcel_key + 1;
                    $this->createConfigurationApp($app_key);
                } catch (InvalidArgumentException $exception) {
                    $message = $exception->getMessage();
                    $this->errorIntegrationOrder($message);
                    throw new InvalidArgumentException($message);
                }

                $newOrder["customData"]["customApps"][] = array(
                    "id" => $this->configuration_checkout_app_id . "#$app_key",
                    "major" => 1,
                    "fields" => array(
                        "paymentSystemName" => $parcel->payment_method,
                        "value"             => $parcel->value,
                        "installments"      => $parcel->parcel,
                        "groups"            => $parcel->payment_type,
                        "tid"               => $parcel->payment_transaction_id ?? null
                    )
                );
            }
        }

        // VTEX obriga a enviar a requisição em um array
        $newOrder = array($newOrder);

        $urlOrderPlace = "api/fulfillment/pvt/orders";
        $queryOrderPlace = array(
            'query' => array(
                'affiliateId'   => $this->order_v2->credentials->affiliate_id_vtex,
                'sc'            => $this->order_v2->credentials->sales_channel_vtex
            ),
            'json' => $newOrder
        );

        try {
            $request = $this->order_v2->request('POST', $urlOrderPlace, $queryOrderPlace);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = $exception->getMessage();
            try {
                $message = Utils::jsonDecode($message);
            } catch (InvalidArgumentException $exception) {}

            // Divergência de valores, deverá fazer uma nova simulação para ver se o frete mudou, para adicionar um desconto ou acréscimo
            if (
                isset($message->error->code) &&
                $message->error->code == 'FMT007' &&
                $this->order_v2->getStoreOwnLogistic()
            ) {
                $skuSimulation = array_map(function ($item){
                    return array('sku' => $item['id'], 'qty' => $item['quantity']);
                }, $newOrder[0]['items']);

                try {
                    $priceSlas = $this->getShippingIntegration($skuSimulation, $dataLogisticsInfo['selectedSla'] ?? '', $newOrder[0]['shippingData']['address']['postalCode']);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }

                foreach ($newOrder[0]['shippingData']['logisticsInfo'] as $keyItem => $item) {

                    // SLA não encontrada para o SKU
                    if (
                        !isset($priceSlas[$newOrder[0]['items'][$item['itemIndex']]['id']]) ||
                        $priceSlas[$newOrder[0]['items'][$item['itemIndex']]['id']] === null
                    ) {
                        continue;
                    }

                    $slaCurrent = (int)$item['price'];
                    $slaNew     = (int)$priceSlas[$newOrder[0]['items'][$item['itemIndex']]['id']];
                    if ($slaCurrent != $slaNew) {
                        $arrPriceTags = array(
                            "isPercentual"  => false,
                            "identifier"    => null,
                            "rate"          => null,
                            "jurisCode"     => null,
                            "jurisType"     => null,
                            "jurisName"     => null
                        );

                        if ($slaNew > $slaCurrent) {
                            $arrPriceTags['name']       = "Desconto divergência frete";
                            $arrPriceTags['value']      = -($slaNew - $slaCurrent);
                            $arrPriceTags['rawValue']   = -moneyVtexToFloat($slaNew - $slaCurrent);
                        } else {
                            $arrPriceTags['name']       = "Acréscimo divergência frete";
                            $arrPriceTags['value']      = $slaCurrent - $slaNew;
                            $arrPriceTags['rawValue']   = +moneyVtexToFloat($slaCurrent - $slaNew);
                        }

                        $newOrder[0]['items'][$keyItem]['priceTags'][] = $arrPriceTags;
                        $newOrder[0]['shippingData']['logisticsInfo'][$keyItem]['price'] = $slaNew;
                    }
                }

                $queryOrderPlace['json'] = $newOrder;

                try {
                    $request = $this->order_v2->request('POST', $urlOrderPlace, $queryOrderPlace);
                } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                    $message = $exception->getMessage();
                    $this->errorIntegrationOrder($message, $newOrder);
                    throw new InvalidArgumentException($message);
                }
            } else {
                $this->errorIntegrationOrder(Utils::jsonEncode($message), $newOrder);
                throw new InvalidArgumentException(Utils::jsonEncode($message));
            }
        }

        $contentOrderRequest = Utils::jsonDecode($request->getBody()->getContents());
        $contentOrder = $contentOrderRequest[0];

        $idVtex = $contentOrder->orderId;

        if(!in_array($order->status->code, array(1,2,96))) {
            // Autorizar despacho
            $this->setAuthorizeDispatch($idVtex);
        }

        /**
         * Iniciar manuseio (só poderá ir para manuseio quando finalizar o prazo de carência)
         * provavelmente quem fará isso é o seller... não diz nada na documentação em mover para manuseio
         * https://help.vtex.com/pt/tutorial/integracao-entre-marketplace-nao-vtex-e-seller-vtex-acoes-referentes-ao
         */
//        try {
//            $this->setStartHandling($idVtex);
//        } catch (InvalidArgumentException $exception) {
//            throw new InvalidArgumentException($exception->getMessage());
//        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order->code,
            'type'           => 'create_order',
            'request'        => json_encode($queryOrderPlace, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => json_encode($contentOrderRequest, JSON_UNESCAPED_UNICODE),
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlOrderPlace
        ));

        return array(
            'id'        => $idVtex,
            'request'   => "$urlOrderPlace\n" . Utils::jsonEncode($queryOrderPlace, JSON_UNESCAPED_UNICODE)
        );
    }

    public function createConfigurationApp(int $key)
    {
        try {
            $request = $this->order_v2->request('GET', 'api/checkout/pvt/configuration/orderForm');
            $response = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $apps = $response->apps;

        if (!getArrayByValueIn($apps, $this->configuration_checkout_app_id . "#$key", 'id')) {
            $apps[] = array(
                "fields" => [
                    "paymentSystemName",
                    "value",
                    "installments",
                    "groups",
                    "tid"
                ],
                "id" => $this->configuration_checkout_app_id . "#$key",
                "major" => 1
            );

            $response->apps = $apps;

            try {
                $uri = 'api/checkout/pvt/configuration/orderForm';
                $request = $this->order_v2->request('POST', $uri, array('json' => $response));

                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'create_order_app',
                    'request'        => json_encode($response, JSON_UNESCAPED_UNICODE),
                    'request_method' => 'POST',
                    'response'       => '',
                    'response_code'  => $request->getStatusCode(),
                    'request_uri'    => $uri
                ));
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        }
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
        // Autorizar despacho
        try {
            $this->setAuthorizeDispatch($orderIntegration);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->order_v2->log_integration("Erro para aprovar o pagamento do pedido ($order) e autorizar o despacho", "<h4>Não foi possível autorizar o pagamento do pedido $order e autorizar o despacho.</h4> <ul><li>{$exception->getMessage()}</li></ul>", "E");
            return false;
        }

        return true;
         
    }

    /**
     * Grava log. e mostra no terminal erro na integração do pedido
     *
     * @param string     $message   Response recebido pela integradora
     * @param array|null $payload   Dados enviados para a VTEX
     */
    private function errorIntegrationOrder(string $message, array $payload = null)
    {
        $message_decode = Utils::jsonDecode($message);

        // formatar mensagens de erro para log integration
        $arrErrors = array();
        if (!is_null($message_decode) && is_object($message_decode)) {
            $errors =  property_exists($message_decode, 'error') ? $message_decode->error->message : $message_decode->message;
            if (!is_array($errors)) {
                $errors = (array)$errors;
            }
            foreach ($errors as $error) {
                if (!is_string($error)) {
                    $error = Utils::jsonEncode($error);
                }
                $msgErrorIntegration = $error ?? "Erro desconhecido";
                $arrErrors[] = $msgErrorIntegration;
            }
        } else {
            $arrErrors[] = $message;
        }

        if (count($arrErrors) === 1 && $arrErrors[0] === 'Nome do merchant inválido') {
            $arrErrors[0] = 'A configuração do afiliado está configurado para utilizar o próprio meio de pagamento. O ideal seria desativar a opção "Usar meu meio de pagamento", pois o pagamento é assumido pelo afiliado.';
        }

        $description = !count($arrErrors) ?
            "<h4>Não foi possível integrar o pedido $this->orderId</h4> <p>Ocorreu um problema inesperado, em breve tentaremos integrar novamente.</p>" :
            "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>";
        if (!empty($payload)) {
            $description .= $this->order_v2->createButtonLogRequestIntegration($payload);
        }

        $this->order_v2->log_integration(
            "Erro para integrar o pedido ($this->orderId)",
            $description,
            "E"
        );
    }

    /**
     * Passar pedido para o estado de Preparando Entrega
     *
     * @param   string  $orderIdVtex    Código do pedido na VTEX
     * @return  bool                    Retornará "true" em caso de sucesso, caso contrário, retornará o erro em um objeto
     */
    private function setStartHandling(string $orderIdVtex): bool
    {
        $urlStartHandling = "api/oms/pvt/orders/$orderIdVtex/start-handling";
        $queryStartHandling = array('json' => array());

        try {
            $this->order_v2->request('POST', $urlStartHandling, $queryStartHandling);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return true;
    }

    /**
     * Checa se o produto tem estoque para integração
     *
     * @param   string  $product    ID do SKU  para consultar estoque
     * @param   int     $qty        Quantidade para validação
     * @return  bool                Retorna um array com o estoque total e estoque separado por ID
     */
    public function getProductInStock(string $product, int $qty)
    {
        $urlSimulation = "api/fulfillment/pvt/orderForms/simulation";
        $querySimulation = array(
            'query' => array(
                'affiliateId'   => $this->order_v2->credentials->affiliate_id_vtex,
                'sc'            => $this->order_v2->credentials->sales_channel_vtex
            ),
            'json' => array(
                'items' => array(
                    array(
                        'id'        => $product,
                        'quantity'  => $qty,
                        'seller'    => 1
                    )
                )
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlSimulation, $querySimulation);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $dataStock = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($dataStock->items[0]->quantity)) {
            throw new InvalidArgumentException("Produto sem estoque na integradora. [SKU:$product][Order:$this->orderId]");
        }
        if($qty > $dataStock->items[0]->quantity){
            return false;
        }
        return $dataStock->items[0];
    }

    /**
     * Autorizar despacho do pedido. Autorizamos, pois, sempre enviamos o pedido como pago.
     *
     * @param   string  $orderIdVtex    Código do pedido na VTEX
     */
    private function setAuthorizeDispatch(string $orderIdVtex): bool
    {
        // Pedido já foi confirmado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'confirm_payment'))) {
            return true;
        }

        $urlAuthorizeDispatch = "api/fulfillment/pvt/orders/$orderIdVtex/fulfill";
        $queryAuthorizeDispatch = array(
            'query' => array(
                'affiliateId'   => $this->order_v2->credentials->affiliate_id_vtex,
                'sc'            => $this->order_v2->credentials->sales_channel_vtex
            ),
            'json' => array(
                'marketplaceOrderId' => $this->orderId
            )
        );

        try {
            $request = $this->order_v2->request('POST', $urlAuthorizeDispatch, $queryAuthorizeDispatch);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = $exception->getMessage();
            if (
                likeText('%"code":"CHK0231"%', $message)
            ) {
                return $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'confirm_payment',
                    'request'        => json_encode($queryAuthorizeDispatch, JSON_UNESCAPED_UNICODE),
                    'request_method' => 'POST',
                    'response'       => $message,
                    'response_code'  => $exception->getCode(),
                    'request_uri'    => $urlAuthorizeDispatch
                ));
            } else {
                if (!empty($message)) {
                    $message = "<pre>$message</pre>";
                }
                $this->order_v2->log_integration("Erro para autorizar o despacho do pedido ($this->orderId)", "<h4>Não foi possível autorizar o despacho para o pedido $this->orderId</h4> <p>Ocorreu um problema inesperado, provavelmente alguma instabilidade na integração.</p><p>Será necessário realizar a autorização para despacho manualmente.</p>$message", "E");
                return false;
            }
        }

        return $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'confirm_payment',
            'request'        => json_encode($queryAuthorizeDispatch, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => $request->getBody()->getContents(),
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlAuthorizeDispatch
        ));
    }

    /**
     * Checa se o produto tem estoque para integração
     *
     * @param   array       $product            ID do produto para serem consultado estoque
     * @param   string      $logisticSelected   Logística selecionado
     * @param   string      $cep                CEP do destinatário
     * @return  array                           Retorna um array com o preço de entrega de cada sku
     */
    private function getShippingIntegration(array $product, string $logisticSelected, string $cep): array
    {
        $urlSimulation = "api/fulfillment/pvt/orderForms/simulation";
        $querySimulation = array(
            'query' => array(
                'affiliateId'   => $this->order_v2->credentials->affiliate_id_vtex,
                'sc'            => $this->order_v2->credentials->sales_channel_vtex
            ),
            'json' => array(
                'items'         => array(),
                'postalCode'    => $cep,
                'country'       => 'BRA'
            )
        );

        foreach ($product as $item) {
            array_push($querySimulation['json']['items'], array(
                'id'        => $item['sku'],
                'quantity'  => $item['qty'],
                'seller'    => 1
            ));
        }

        try {
            $request = $this->order_v2->request('POST', $urlSimulation, $querySimulation);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $data = Utils::jsonDecode($request->getBody()->getContents());

        $arrShipping = array();
        if (isset($data->logisticsInfo)) {
            foreach ($data->logisticsInfo as $logistic) {
                $priceSelected = null;
                if (isset($logistic->slas)) {
                    foreach ($logistic->slas as $sla) {
                        if ($sla->id == $logisticSelected) {
                            $priceSelected = $sla->price;
                        }
                    }
                }
                $arrShipping[$data->items[$logistic->itemIndex]->id] = $priceSelected;
            }
        }

        return $arrShipping;
    }

    /**
     * Recupera dados do pedido na integradora
     *
     * @param   string|int      $order  Código do pedido na integradora
     * @return  array|object            Dados do pedido na integradora
     */
    public function getOrderIntegration($order)
    {
        $urlOrder = "api/oms/pvt/orders/$order";

        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return Utils::jsonDecode($request->getBody()->getContents());
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
            $this->setApprovePayment($orderid, $orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $dataOrder = $order->packageAttachment->packages;

        foreach ($dataOrder as $invoice) {

            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'invoice_order',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($dataOrder, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));

            return array(
                'date'      => dateFormat($invoice->issuanceDate, DATETIME_INTERNATIONAL),
                'value'     => moneyVtexToFloat($invoice->invoiceValue),
                'serie'     => substr(clearBlanks($invoice->invoiceKey), 22, 3),
                'number'    => (int)clearBlanks($invoice->invoiceNumber),
                'key'       => clearBlanks($invoice->invoiceKey),
                'isDelivered' => $invoice->courierStatus->deliveredDate ?? null
            );
        }

        return [
            'date' => null,
            'value' => moneyVtexToFloat($order->value),
            'serie' => null,
            'number' => null,
            'key' => null,
            'isDelivered' => null
        ];

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

        $dataOrder = $this->order_v2->getOrder($order);

        $arrItems = array();
        foreach($dataOrder->items as $item) {
            $arrItems[] = array(
                "id"        => $item->sku_variation ?? $item->sku,
                "quantity"  => (int)$item->qty,
                "price"     => moneyFloatToVtex($item->original_price - $item->discount)
            );
        }

        /**
         * Existe NFe, não pode cancelar, deve enviar uma nota de devolução
         * @todo Criar funcionalidade para adicionar nota de devolução para ser enviada a VTEX
         */
        if ($dataOrder->invoice) {
            $invoiced = array(
                "type"              => "Input",
                "invoiceNumber"     => $dataOrder->invoice->num + 1,
                "courier"           => "",
                "trackingNumber"    => "",
                "trackingUrl"       => "",
                "items"             => $arrItems,
                "issuanceDate"      => $dataOrder->invoice->date_emission,
                "invoiceValue"      => moneyFloatToVtex($this->order_v2->sellerCenter === 'somaplace' ? $dataOrder->payments->net_amount : $dataOrder->payments->gross_amount)
            );

            // request to send nfe return input
            $urlReturnInvoice   = "api/oms/pvt/orders/$orderIntegration/invoice";
            $queryReturnInvoice = array(
                'json' => $invoiced
            );

            try {
                $request = $this->order_v2->request('POST', $urlReturnInvoice, $queryReturnInvoice);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        } else {
            // É preciso enviar duas requisições para cancelar o pedido na vtex
            // A primeira requisição o pedido vai para "Aguardando decisão do seller"
            // Na segunda requisição fica como "Cancelado".
            for ($cancelCount = 1; $cancelCount <= 2; $cancelCount++) {
                // Esperar 10s para enviar a próxima requisição.
                // Para não enviar uma requisição antes da VTEX vir aqui.
                if ($cancelCount == 2) {
                    echo "Esperar 10s para a próxima requisição\n";
                    sleep(10);
                }

                // request to cancel order
                $urlCancelOrder = "api/oms/pvt/orders/$orderIntegration/cancel";

                try {
                    $request = $this->order_v2->request('POST', $urlCancelOrder);
                } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }
            }
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order,
            'type'           => 'cancel_order',
            'request'        => !empty($invoiced) ? json_encode($invoiced, JSON_UNESCAPED_UNICODE) : null,
            'request_method' => 'POST',
            'response'       => '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlReturnInvoice ?? $urlCancelOrder ?? null
        ));

        $reason = $this->order_v2->model_orders->getPedidosCanceladosByOrderId($order);
        $reason = $reason['motivo_cancelamento'] ?? 'Motivo não informado';

        // request to reason cancel
        $urlReasonCancel = "api/oms/pvt/orders/$orderIntegration/interactions";
        $queryReasonCancel = array(
            'json' => array(
                "source" => "Cancelamento",
                "message" => $reason
            )
        );

        try {
            $this->order_v2->request('POST', $urlReasonCancel, $queryReasonCancel);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return true;
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
        try {
            $dataOrder = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $itemsTracking   = array();
        $trackingInvalid = array();

        foreach ($dataOrder->packageAttachment->packages as $keyPackage => $package) {
            $number_package = $keyPackage + 1;
            // Não é pacote de saída. Output ou Input.
            if ($package->type !== 'Output') {
                continue;
            }

            $courier = $package->courier;

            $trackingCode = $package->trackingNumber;

            $trackingUrl = $package->trackingUrl;

            $isDelivered = $package->courierStatus->deliveryDate;

            // Na primeira leitura, se encontrou rastreio, atribui a todos os itens
            if ($keyPackage === 0) {
                foreach ($items as $item) {
                    $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                        'quantity'                  => $item->qty,
                        'shippingCompany'           => $courier,
                        'trackingCode'              => $trackingCode,
                        'trackingUrl'               => $trackingUrl,
                        'generatedDate'             => date(DATETIME_INTERNATIONAL),
                        'shippingMethodName'        => $courier,
                        'shippingMethodCode'        => $courier,
                        'deliveryValue'             => 0,
                        'documentShippingCompany'   => null,
                        'estimatedDeliveryDate'     => null,
                        'labelA4Url'                => null,
                        'labelThermalUrl'           => null,
                        'labelZplUrl'               => null,
                        'labelPlpUrl'               => null,
                        'isDelivered'               => $isDelivered ?? null
                    );
                }
            }
            // caso exista mais pacotes no pedido, será lido, caso existam outros pacotes, será atribuído ao item
            else {
                foreach ($items as $item) {
                    $existItemPacked = false;
                    $skuIntegration = $item->sku_variation ?? $item->sku;

                    foreach ($package->items as $itemPack) {
                        if ($dataOrder->items[$itemPack->itemIndex]->id == $skuIntegration) {
                            $existItemPacked = true;
                            break;
                        }
                    }

                    if (!$existItemPacked) {
                        continue;
                    }

                    $itemsTracking[$skuIntegration] = array(
                        'quantity'                  => $itemsTracking[$skuIntegration]['quantity'],
                        'shippingCompany'           => $courier,
                        'trackingCode'              => $trackingCode,
                        'trackingUrl'               => $trackingUrl,
                        'generatedDate'             => $itemsTracking[$skuIntegration]['generatedDate'],
                        'deliveryValue'             => $itemsTracking[$skuIntegration]['deliveryValue']
                    );
                }
            }
        }

        if (!count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'get_tracking'))) {
            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'get_tracking',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($dataOrder->packageAttachment->packages, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));
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
        // request to send tracking to integration
        $urlReturnInvoice   = "api/oms/pvt/orders/$orderIntegration/invoice/{$order->invoice->num}";
        $queryReturnInvoice = array(
            'json' => array(
                'courier'           => $dataTracking->ship_company,
                'trackingUrl'       => $dataTracking->tracking->tracking_url,
                'trackingNumber'    => $dataTracking->tracking->tracking_code[0] ?? null,
                'dispatchedDate'    => null
            )
        );

        try {
            $request = $this->order_v2->request('PATCH', $urlReturnInvoice, $queryReturnInvoice);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_tracking',
            'request'        => json_encode($queryReturnInvoice, JSON_UNESCAPED_UNICODE),
            'request_method' => 'PATCH',
            'response'       => '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlReturnInvoice
        ));

        return true;
    }

    /**
     * Recupera data de envio do pedido
     *
     * @warning Na VTEX não existe uma data de envio do pedido, quando enviamos o rastreio é entendido que já está em rota de entrega. Como solução, após o envio do rastreio o pedido vai para Em Transporte
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @return  array                      Data de envio do pedido
     * @throws  InvalidArgumentException
     */
    public function getShippedIntegration(string $orderIntegration)
    {
        try {
            $dataOrder = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $date = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
        $contentOrder = $dataOrder->packageAttachment->packages;

        $isDelivered = false;
        $shippedDate = array(
                'isDelivered' => $isDelivered,
                'date' => $date
            );
            
        foreach ($contentOrder as $package) {
            $isDelivered = $package->courierStatus->deliveredDate ? true : false;

            if (isset($package->courierStatus->data) && count($package->courierStatus->data)) {
                $courierFirstStatus = end($package->courierStatus->data);
                $date = dateFormat(($courierFirstStatus->lastChange ?? date("Y-m-d H:i:s")), DATETIME_INTERNATIONAL);
            }
            $shippedDate = array(
                'isDelivered' => $isDelivered,
                'date' => $date
            );
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'get_shipped',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($contentOrder, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => null
        ));

        return $shippedDate;
    }

    /**
     * Recupera ocorrências do rastreio
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
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
            'isDelivered'   => false,
            'dateDelivered' => null,
            'occurrences'   => array()
        );
        $contentOrder = $dataOrder->packageAttachment->packages;

        foreach ($contentOrder as $package) {
            if (isset($package->courierStatus->finished) && $package->courierStatus->finished) {
                $occurrences['isDelivered']     = true;
                $occurrences['dateDelivered']   = $package->courierStatus->deliveredDate ?? dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }

            if (isset($package->courierStatus->data) && count($package->courierStatus->data)) {
                if (!array_key_exists($package->trackingNumber, $occurrences['occurrences'])) {
                    $occurrences['occurrences'][$package->trackingNumber] = array();
                }
                $occurrencesIntegration = array_reverse($package->courierStatus->data);

                foreach ($occurrencesIntegration as $occurrence) {
                    $occurrences['occurrences'][$package->trackingNumber][] = array(
                        'date'          => dateFormat(($occurrence->lastChange ?? date("Y-m-d H:i:s")), DATETIME_INTERNATIONAL),
                        'occurrence'    => $occurrence->description,
                        'city'          => $occurrence->city,
                        'state'         => $occurrence->state
                    );
                }
            }
        }

        if ($occurrences['isDelivered']) {
            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'get_delivered',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($contentOrder, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));
        }

        return $occurrences;
    }

    /**
     * Importar a dados de ocorrência do rastreio
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order})
     * @param   array   $dataOccurrence     Dados de ocorrência
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setOccurrenceIntegration(string $orderIntegration, object $order, array $dataOccurrence): bool
    {
        $arrOccurrence = array();
        foreach ($dataOccurrence as $occurrence) {
           $arrOccurrence[] = array(
                "city"          => $occurrence['adderCity'] ?? '',
                "state"         => $occurrence['adderState'] ?? '',
                "description"   => $occurrence['name'],
                "date"          => dateFormat($occurrence['date'], DATETIME_INTERNATIONAL_TIMEZONE)
            );
        }

        // request to send occurrence to integration
        $urlImportOccurrence   = "api/oms/pvt/orders/$orderIntegration/invoice/{$order->invoice->num}/tracking";
        $queryImportOccurrence = array(
            'json' => array(
                "isDelivered"   => false,
                "events"        => $arrOccurrence
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlImportOccurrence, $queryImportOccurrence);
            $contentRequest = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_occurrence',
            'request'        => json_encode($queryImportOccurrence['json'], JSON_UNESCAPED_UNICODE),
            'request_method' => 'PUT',
            'response'       => json_encode($contentRequest, JSON_UNESCAPED_UNICODE),
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlImportOccurrence
        ));

        return true;
    }

    /**
     * Recupera data de entrega do pedido
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @return  string                      Data de entrega do pedido
     * @throws  InvalidArgumentException
     */
    public function getDeliveredIntegration(string $orderIntegration): string
    {
        try {
            $dataOrder = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $date = null;
        $contentOrder = $dataOrder->packageAttachment->packages;

        foreach ($contentOrder as $package) {
            if (isset($package->courierStatus->deliveredDate) && !empty($package->courierStatus->deliveredDate)) {
                // pego a data mais recente
                $dateTemp = dateFormat($package->courierStatus->deliveredDate, DATETIME_INTERNATIONAL);
                if (!$date) {
                    $date = $dateTemp;
                } else {
                    if (strtotime($dateTemp) > strtotime($date)) {
                        $date = $dateTemp;
                    }
                }
            }
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'get_delivered',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($contentOrder, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => null
        ));

        return $date ?? dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
    }

    /**
     * Importar a data de entrega
     *
     * @param   string  $orderIntegration   Código do pedido na integradora
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order})
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setDeliveredIntegration(string $orderIntegration, object $order): bool
    {
        // request to send occurrence to integration
        $urlImportOccurrence   = "api/oms/pvt/orders/$orderIntegration/invoice/{$order->invoice->num}/tracking";
        $queryImportOccurrence = array(
            'json' => array(
                "isDelivered" => true
            )
        );

        try {
            $request = $this->order_v2->request('PUT', $urlImportOccurrence, $queryImportOccurrence);
            $contentRequest = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_delivered',
            'request'        => json_encode($queryImportOccurrence['json'], JSON_UNESCAPED_UNICODE),
            'request_method' => 'PUT',
            'response'       => json_encode($contentRequest, JSON_UNESCAPED_UNICODE),
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlImportOccurrence
        ));

        return true;
    }

    public function getCallbackUrl()
    {
        $url = $this->order_v2->model_settings->getValueIfAtiveByName('vtex_callback_url');
        if (!$url) {
            $url = base_url();
        }

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        return $url;
    }
}
