<?php

namespace Integration\anymarket;

require_once APPPATH . "libraries/Integration_v2/Order_v2.php";

require_once APPPATH . "libraries/Integration_v2/anymarket/ApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/AnyMarketApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/TransformationException.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Integration\Integration_v2\Order_v2;

/**
 * Class ToolsOrder
 * @package Integration\Integration_v2\anymarket
 * @property \CI_Loader $load
 * @property \Model_anymarket_order_to_update $model_anymarket_order_to_update
 * @property \Model_orders $model_orders
 * @property \Model_products $model_products
 */
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

    protected $host;

    protected $parsedOrder = [];

    protected $ordersIntegration = [];

    protected $sellerCenterName = '';

    /**
     * Instantiate a new Tools instance.
     *
     * @param Order_v2 $order_v2
     */
    public function __construct(Order_v2 $order_v2)
    {
        $this->load->model('model_anymarket_order_to_update');
        $this->load->model('model_orders');
        $this->load->model('model_products');
        $this->order_v2 = $order_v2;
        $this->ordersIntegration = [];
        $this->host = $this->order_v2->model_settings->getValueIfAtiveByName('url_anymarket');
        $this->sellerCenterName = $this->order_v2->model_settings->getValueIfAtiveByName('sellercenter_name');
    }

    public function parseOrderToIntegration(object $order): array
    {
        $shippingAddress = $order->shipping->shipping_address;
        $billingAddress = $order->billing_address;
        $payment = $order->payments;
        $customer = $order->customer;
        $items = $order->items;
        $this->orderId = $order->code;

        $dataformat = "Y-m-d\TH:i:sP";

        $this->parsedOrder = [];

        $this->parsedOrder['marketPlaceId'] = $this->orderId;
        $this->parsedOrder['marketPlaceNumber'] = $order->marketplace_number;
        $createAt = new \DateTime($order->created_at, new \DateTimeZone("America/Sao_Paulo"));
        $this->parsedOrder['createdAt'] = $createAt->format($dataformat);

        $this->parsedOrder['cancelDate'] = null;
        $this->parsedOrder['cancellationCode'] = null;
        $this->parsedOrder['transmissionStatus'] = null;

        $this->parsedOrder['observation'] = [];
        
        try {
            $observation = $this->order_v2->validarCamposObrigatoriosEPopularObservation($this->orderId, $this->order_v2->dataStore['id']);
            if($observation){
                $this->parsedOrder['observation'] = $observation;
            }

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Erro ao validar campos obrigatórios para nota fiscal do pedido {$this->orderId}: " . $e->getMessage());
        }

        $this->parsedOrder['status'] = in_array($order->status->code, array(1,2,96)) ? 'PENDING' : 'PAID';

        $this->parsedOrder['marketPlaceStatusComplement'] = null;
        $this->parsedOrder['marketPlaceUrl'] = null;
        $this->parsedOrder['marketPlaceShipmentStatus'] = null;
        $this->parsedOrder['invoice'] = null; 

        $this->parsedOrder['marketPlaceStatus'] = in_array($order->status->code, array(1,2,96)) ? 'PENDING' : 'PAID';

        $this->parsedOrder['shipping'] = [
            'city' => $shippingAddress->city,
            'state' => $shippingAddress->region,
            'stateNameNormalized' => $shippingAddress->region,
            'country' => 'Brazil',
            'countryAcronymNormalized' => null,
            'countryNameNormalized' => 'Brazil',
            'address' => "$shippingAddress->street, $shippingAddress->number, $shippingAddress->complement",
            'number' => $shippingAddress->number,
            'neighborhood' => $shippingAddress->neighborhood,
            'street' => $shippingAddress->street,
            'comment' => $shippingAddress->complement,
            'reference' => $shippingAddress->reference ?? '',
            'zipCode' => $shippingAddress->postcode,
            'receiverName' => $shippingAddress->full_name,
            'promisedShippingTime' => $order->shipping->estimated_delivery,
        ];
        $this->parsedOrder['billingAddress'] = [
            'city' => $billingAddress->city,
            'state' => $billingAddress->region,
            'stateNameNormalized' => $billingAddress->region,
            'country' => 'Brazil',
            'countryAcronymNormalized' => null,
            'countryNameNormalized' => 'Brazil',
            'number' => $billingAddress->number,
            'neighborhood' => $billingAddress->neighborhood,
            'street' => $billingAddress->street,
            'comment' => $billingAddress->complement,
            'reference' => $billingAddress->reference ?? '',
            'zipCode' => $billingAddress->postcode
        ];
        $this->parsedOrder['anymarketAddress'] = null;
        $this->parsedOrder['buyer'] = [
            'id' => $customer->id,
            'marketPlaceId' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'document' => $customer->cnpj ?? $customer->cpf,
            'documentType' => $customer->cnpj ? 'CNPJ' : 'CPF',
            'phone' => $customer->phones[1] ?? '',
            'cellPhone' => $customer->phones[0] ?? '',
            'documentNumberNormalized' => $customer->cnpj ?? $customer->cpf,
        ];
        $this->parsedOrder['tracking'] = null;

        $this->parsedOrder['items'] = [];

        $totalNetItems = 0;
        $totalGrossItems = 0;
        foreach ($items as $item) {
            $partnerId = $item->sku_variation ?? $item->sku;

            $product_id = $item->product_id;

            if (
                likeTextNew('DEL_%', $partnerId) &&
                count(explode('_', $partnerId)) >= 4
            ) {
                $data_product = $this->order_v2->model_products->getProductData(0, $product_id);

                if (!empty($data_product) && $data_product['status'] == $this->order_v2->model_products::DELETED_PRODUCT) {
                    $exp_partner_id = explode('_', $partnerId);
                    array_pop($exp_partner_id);
                    array_pop($exp_partner_id);
                    array_shift($exp_partner_id);
                    $partnerId = implode('_', $exp_partner_id);
                }
            }

            $data = [
                "sku" => [
                    'partnerId' => $partnerId
                ],
                "amount" => $item->qty,
                "unit" => round(floatval($item->original_price), 2),
                "gross" => round((floatval($item->original_price)) * floatval($item->qty), 2),
                "total" => round((floatval($item->original_price) - $item->discount) * floatval($item->qty), 2),
                "discount" => round($item->discount * floatval($item->qty), 2),
                "marketPlaceId" => $item->product_id,
                "orderItemId" => null,
                "shippings" => [],
            ];
            $totalNetItems += $data['total'];
            $totalGrossItems += $data['gross'];
            $data["shippings"][] = [
                "id" => null,
                "shippingtype" => "{$order->shipping->shipping_carrier} - {$order->shipping->service_method}",
                "shippingCarrierNormalized" => $order->shipping->shipping_carrier,
                "shippingCarrierTypeNormalized" => $order->shipping->service_method,
            ];
            $this->parsedOrder['items'][] = $data;
        }

        $this->parsedOrder['interestValue'] = 0;
        $this->parsedOrder['freight'] = round(
            floatval($order->shipping->shipping_cost ?? $order->shipping->seller_shipping_cost), 2
        );

        $diffTotalNetProd = ($payment->total_products ?? $totalNetItems) - $totalNetItems;
        if ($diffTotalNetProd > 0) {
            $this->parsedOrder['interestValue'] = (float)number_format($diffTotalNetProd, 2, '.', '');
        }

        $this->parsedOrder['discount'] = round(floatval($payment->discount ?? 0), 2);
        $totalDiscountItems = $totalGrossItems - $totalNetItems;

        $this->parsedOrder['discount'] = (float)number_format($this->parsedOrder['discount'], 2, '.', '');
        $totalDiscountItems = (float)number_format($totalDiscountItems, 2, '.', '');

        if ($this->parsedOrder['discount'] >= $totalDiscountItems) {
            $this->parsedOrder['discount'] -= $totalDiscountItems;
        }

        $totalCalcGrossOrder = ($totalNetItems + $this->parsedOrder['freight'] + $this->parsedOrder['interestValue']);
        $totalCalcGrossOrder = (float)number_format($totalCalcGrossOrder, 2, '.', '');
        $totalDbNetOrder = (float)number_format((($payment->total_products ?? 0) + $this->parsedOrder['freight'] + $this->parsedOrder['interestValue']), 2, '.', '');

        $this->parsedOrder['discount'] = ($totalCalcGrossOrder - $this->parsedOrder['discount']) >= $totalDbNetOrder ? $this->parsedOrder['discount'] : 0;

        $discount = round(floatval($this->parsedOrder['discount']), 2);
        $this->parsedOrder['discount'] = (float)number_format("{$discount}", 2, '.', '');
        $this->parsedOrder['productNet'] = round(floatval($totalNetItems), 2);
        $totalOrder = $totalCalcGrossOrder - $this->parsedOrder['discount'];
        $this->parsedOrder['total'] = round($totalOrder, 2);

        if ($payment) {
            $this->parsedOrder['payments'] = [];
            $paymentDate = new \DateTime($payment->date_payment, new \DateTimeZone("America/Sao_Paulo"));
            $this->parsedOrder['paymentDate'] = $paymentDate->format($dataformat);
            $countPaymentMethod = count($payment->parcels);
            $totalValue = $this->parsedOrder['total']/$countPaymentMethod;
            $totalValue = round($totalValue, 2);
            $fixRoud = 0;
            if(($totalValue*$countPaymentMethod) != $this->parsedOrder['total']){
                $fixRoud = $this->parsedOrder['total'] - ($totalValue*$countPaymentMethod);
                $fixRoud = round($fixRoud, 2);
            }
            foreach ($payment->parcels as $parcel) {
                if ($parcel === end($payment->parcels) && $fixRoud) {
                    $totalValue = $totalValue+$fixRoud;
                }
                $data = [
                    "method" => $parcel->payment_method,
                    "status" => in_array($order->status->code, array(1,2,96)) ? 'PENDING' : 'PAGO',
                    "value" => $totalValue,
                    "installments" => ((int)$parcel->parcel <= 0) ? 1 : (int)$parcel->parcel,
                    "marketplaceId" => null,
                    "paymentMethodNormalized" => null,
                    "paymentDetailNormalized" => null,
                    "dueDate" => $paymentDate->format($dataformat),
                ];
                $this->parsedOrder['payments'][] = $data;
            }
        } else {
            $this->parsedOrder['paymentDate'] = null;
        }

        $this->parsedOrder['deliverStatus'] = null;

        $this->parsedOrder['errorMsg'] = null;
        $this->parsedOrder['observation'] = null;
        $this->parsedOrder['accountName'] = null;

        $this->parsedOrder['idAccount'] = $this->order_v2->credentials->idAccount
            ?? $this->order_v2->integrationData['user_id'];
        $this->parsedOrder['marketPlace'] = strtoupper($order->system_marketplace_code);

        $this->parsedOrder['metadata'] = null;
        $this->parsedOrder['cancelDetails'] = null;

        return $this->parsedOrder;
    }

    /**
     * Envia o pedido para a integradora.
     *
     * @param object $order Dados do pedido para formatação.
     * @return  array           Código do pedido gerado pela integradora e dados da requisição para log.
     */
    public function sendOrderIntegration(object $order): array
    {
        $requestIntegration = array();
        $orderResponse = (object) null;

        $this->parsedOrder = $this->parseOrderToIntegration($order);
        $uri = "/orders";
        try {
            $request = $this->order_v2->request('POST', $uri, ['json' => $this->parsedOrder]);
            $orderResponse = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = $exception->getMessage();
            $errorIntegration = json_decode($message, True);
            if ($errorIntegration["code"] === 400) {
                if (strpos($errorIntegration["message"], 'Erro ao criar o pedido. Já existe um pedido com id') !== false) {
                    $requestIntegration = $this->getOrderIntegration($order->code);
                    $requestIntegration = json_decode(json_encode($requestIntegration),true);
                } else if (strpos($errorIntegration["message"], 'Parâmetro configurado no ANYMARKET para não importar pedidos pendentes') !== false) {
                    $this->setOrderPendingInIntegration($order);
                    $this->order_v2->log_integration("Erro para integrar o pedido ({$order->code})", "<h4>Não foi possível integrar o pedido {$order->code}</h4> <ul><li>Anymarket não importar pedido pendentes. Pedido será enviado após confirmação de pagamento.</li></ul>", "E");
                    throw new InvalidArgumentException($errorIntegration["details"]);
                } else {
                    $errorMsg = $errorIntegration["message"] ?? $message;
                    $this->order_v2->log_integration("Erro para integrar o pedido ({$order->code})", "<h4>Não foi possível integrar o pedido {$order->code}</h4> <ul><li>$errorMsg</li></ul>", "E");
                    throw new InvalidArgumentException($errorIntegration["message"] ?? $message);
                }
            } else {
                $errorMsg = $errorIntegration["message"] ?? $message;
                $this->order_v2->log_integration("Erro para integrar o pedido ({$order->code})", "<h4>Não foi possível integrar o pedido {$order->code}</h4> <ul><li>$errorMsg</li></ul>", "E");
                throw new InvalidArgumentException($errorIntegration["message"] ?? $message);
            }
        }

        $orderResponse = json_decode(json_encode($orderResponse),true);
        $orderIntegration = empty($orderResponse) ? $requestIntegration['id'] : $orderResponse['id'];

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order->code,
            'type'           => 'create_order',
            'request'        => json_encode($this->parsedOrder, JSON_UNESCAPED_UNICODE),
            'response'       => json_encode($orderResponse ?? $requestIntegration ?? [], JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response_code'  => 200,
            'request_uri'    => $uri
        ));

        return [
            'id' => $orderIntegration ?: null,
            'request' => Utils::jsonEncode($this->parsedOrder, JSON_UNESCAPED_UNICODE)
        ];
    }

    private function setOrderPendingInIntegration(object $order)
    {
        // Pedido já foi notificado na monitoria.
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'notify_pending_order'))) {
            return;
        }

        // Pedido pendente, deve informar na monitoria na Anymarket.
        if (in_array($order->status->code, array(1,2,96))) {
            try {
                $orderOrigin = $this->order_v2->model_settings->getValueIfAtiveByName('sellercenter_name') ?? "CONECTALA";
                $body_monitory = array(
                    "createdAt"         => dateNow(TIMEZONE_DEFAULT)->format('Y-m-d\TH:i:s\Z'), //"2020-05-13T20:01:33Z",
                    "origin"            => $orderOrigin, //"NomeDoMarketplace",
                    "details"           => "PEDIDO NÂO IMPORTADO",
                    "id"                => $this->orderId, //"001111",
                    "partnerId"         => $this->orderId, //001111
                    "message"           => "PEDIDO NÂO IMPORTADO, POR FAVOR ATUALIZE",
                    "type"              => "CRITICAL_ERROR",
                    "retryCallbackURL"  => str_replace('http://','https://',str_replace('conectala.tec.br','conectala.com.br', base_url("Api/Integration/AnyMarket/Remotes/orderRequest/$this->orderId?token={$this->order_v2->credentials->token2}"))),//"WWW.MINHACALLBACK.COM.BR/RETRY/45787ADSA",
                    "status"            => "PENDING"
                );
                $options = ['json' => $body_monitory];
                $uri = "monitorings";
                $request = $this->order_v2->request('POST', $uri, $options);
                $response = $request->getBody()->getContents();

                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'notify_pending_order',
                    'request'        => json_encode($options, JSON_UNESCAPED_UNICODE),
                    'response'       => $response,
                    'request_method' => 'POST',
                    'response_code'  => $request->getStatusCode(),
                    'request_uri'    => $uri
                ));
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                // Por enquanto não faz nada, caso gera algum erro, pois o pedido já foi integrado e pode quebrar o fluxo.
            }
        }
    }

    public function updateOrderIntegration($orderId, object $parsedOrder): array
    {
        try {
            $request = $this->order_v2->request('PUT', "/orders/{$orderId}", ['json' => $parsedOrder]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = $exception instanceof ClientException ?
                json_decode(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage())
                : (object)['message' => $exception->getMessage()];
            $this->handleErrorRequest($message);
            throw new InvalidArgumentException($exception->getMessage());
        }

        if ($request->getStatusCode() != 200) {
            $message = Utils::jsonDecode($request->getBody()->getContents());
            $this->handleErrorRequest($message);
            throw new InvalidArgumentException($request->getBody()->getContents());
        }

        $orderResponse = Utils::jsonDecode($request->getBody()->getContents());

        return [
            'id' => $orderResponse->id ?? null
        ];
    }

    /**
     * @param object $object Objeto de retorno para validar se existe algum erro
     * @return  array|null          Caso encontre algum erro retornará um array, caso contrário, nulo.
     */
    private function handleErrorRequest($object)
    {
        if (isset($object->message)) {
            $this->order_v2->log_integration(
                "Erro para integrar o pedido ($this->orderId)",
                "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>{$object->message}</li></ul>",
                "E"
            );
        }
        $now = new \DateTime('now', new \DateTimeZone("America/Sao_Paulo"));
        $orderOrigin = $this->order_v2->model_settings->getValueIfAtiveByName('sellercenter_name') ?? "CONECTALA";
        $dataMonitorings = [
            "message" => $object->message,
            'details' => "PEDIDO NÃO IMPORTADO",
            'createdAt' => $now->format("Y-m-d\TH:i:sP"),
            "origin" => strtoupper($orderOrigin),
            'id' => $this->orderId,
            "partnerId" => $this->orderId,
            "type" => "CRITICAL_ERROR",            
            "retryCallbackURL" => str_replace('http://','https://',str_replace('conectala.tec.br','conectala.com.br',
                base_url("Api/Integration/AnyMarket/Remotes/orderRequest/{$this->orderId}?token={$this->order_v2->credentials->token2}"))),
            "status" => "PENDING",
        ];
        $response = $this->order_v2->request('POST', "/monitorings", ['json' => $dataMonitorings]);
    }

    public static function skuCodeNormalize($code, $delimiter = '')
    {
        return trim(
            preg_replace('/[\s]+/', $delimiter,
                preg_replace('/[^A-Za-z0-9-_@]+/', $delimiter,
                    preg_replace('/[&]/', '',
                        preg_replace('/[\']/', '',
                            iconv(
                                'UTF-8',
                                'ASCII//TRANSLIT//IGNORE',
                                $code
                            )
                        )
                    )
                )
            ), $delimiter
        );
    }

    /**
     * Recupera dados do pedido na integradora
     *
     * @param string|int $order Código do pedido na integradora
     * @return  array|object            Dados do pedido na integradora
     */
    public function getOrderIntegration($order)
    {
        if (isset($this->ordersIntegration[$order])) {
            return $this->ordersIntegration[$order];
        } else {
            if (empty($this->orderId)) {
                $this->orderId = $this->model_orders->getOrderByOrderIdIntegration($order, $this->order_v2->store ?? null)['id'] ?? null;
            }
            $order = $this->orderId ?? 0;
        }

        try {
            $request = $this->order_v2->request('GET', "/orders/{$order}");
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $orderResponse = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($orderResponse->id)) {
            throw new InvalidArgumentException('Pedido não localizado');
        }

        $this->ordersIntegration[$order] = $orderResponse;

        return $orderResponse;
    }

    /**
     * Recupera dados da nota fiscal do pedido
     *
     * @param string $orderIdIntegration Dados do pedido da integradora
     * @param int $orderid Código do pedido no Seller Center
     * @return  array                       Dados de nota fiscal do pedido [date, value, serie, number, key]
     */
    public function getInvoiceIntegration(string $orderIdIntegration, int $orderid): array
    {
        
        try {
            $order = $this->getOrderIntegration($orderIdIntegration);
            if ($order->status == 'PENDING') {
                $this->setApprovePayment($orderid, $orderIdIntegration);
            }
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }
        
        if (!isset($order->invoice) || !$order->invoice) {
            throw new InvalidArgumentException('Nota não encontrada para o pedido.');
        }
        $invoice = $order->invoice;

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'invoice_order',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($invoice, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => 'nota.fiscal.obter.php'
        ));

        return [
            'date' => $invoice->date,
            'value' => roundDecimal($order->total),
            'serie' => (int)$invoice->series,
            'number' => (int)clearBlanks($invoice->number),
            'key' => clearBlanks($invoice->accessKey),
            'link' => $invoice->linkNfe ?? '',
            'isDelivered' => $order->tracking->deliveredDate ?? null
        ];
    }

    /**
     * Cancelar pedido na integradora
     *
     * @param int $order Código do pedido no Seller Center
     * @param string $orderIntegration Código do pedido na integradora
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     */
    public function cancelIntegration(int $order, string $orderIntegration): bool
    {
        // Pedido já foi cancelado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        $this->orderId = $this->orderId ?? $order;
        $cancelOrder = [
            'date' => date("Y-m-d\TH:i:sP", strtotime(date('Y-m-d H:i:s'))),
            "code" => 'BUYER_CANCELED',
        ];
        $uri = "/orders/{$this->orderId}/markAsCanceled";

        try {
            try {
                $request = $this->order_v2->request('PUT', $uri, ['json' => $cancelOrder]);
                $response = Utils::jsonDecode($request->getBody()->getContents());
                $this->sendOrderTransmissionStatus($this->orderId, ['marketPlaceStatus' => 'CANCELADO']);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }

            if ($request->getStatusCode() != 200) {
                $contentOrder = Utils::jsonDecode($request->getBody()->getContents());
                throw new InvalidArgumentException(Utils::jsonEncode($contentOrder));
            }
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($e->getMessage(), 'já está no status cancelado') !== false) {
                $msg .= " ({order already canceled})";
            }
            throw new InvalidArgumentException($msg);
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order,
            'type'           => 'cancel_order',
            'request'        => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : null,
            'request_method' => 'POST',
            'response'       => '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $uri
        ));

        return true;
    }

    /**
     * Recupera dados de tracking
     * @param string $orderIntegration Código do pedido na integradora
     * @param array $items Itens do pedido
     * @return  array                       Array onde, a chave de cada item do array será o código SKU e o valor do item um array com os dados: quantity, shippingCompany, trackingCode, trackingUrl, generatedDate, shippingMethodName, shippingMethodCode, deliveryValue, documentShippingCompany, estimatedDeliveryDate, labelA4Url, labelThermalUrl, labelZplUrl, labelPlpUrl
     * @throws  InvalidArgumentException
     * @todo criar funcionalidade para a integradora.
     *
     */
    public function getTrackingIntegration(string $orderIntegration, array $items): array
    {
        $order = $this->getOrderIntegration($orderIntegration);

        $itemsTracking = [];

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }
        $tracking = $order->tracking;

        foreach ($items as $item) {
            $orderItem = array_filter($order->items, function ($it) use ($item) {
                return $item->sku_variation == $it->sku->partnerId || $item->sku == $it->sku->partnerId;
            });
            $orderItem = !empty($orderItem) ? current($orderItem) : null;
            if (isset($orderItem->shippings)) {
                $itemShipping = !empty($orderItem->shippings) ? current($orderItem->shippings) : null;
                $item->{'shipping_carrier'} = $item->shipping_carrier ?? $itemShipping->shippingCarrierNormalized ?? null;
                $item->{'service_method'} = $item->service_method ?? $itemShipping->shippingCarrierTypeNormalized ?? null;
            }
            $itemsTracking[$item->sku_variation ?? $item->sku] = [
                'quantity' => $item->qty,
                'shippingCompany' => $tracking->carrier ?? $item->shipping_carrier ?? null,
                'trackingCode' => $tracking->number,
                'trackingUrl' => $tracking->url,
                'generatedDate' => date(DATETIME_INTERNATIONAL),
                'shippingMethodName' => $item->service_method ?? null,
                'shippingMethodCode' => $item->service_method ?? null,
                'deliveryValue' => 0,
                'documentShippingCompany' => null,
                'estimatedDeliveryDate' => null,
                'labelA4Url' => null,
                'labelThermalUrl' => null,
                'labelZplUrl' => null,
                'labelPlpUrl' => null,
                'isDelivered' => $tracking->deliveredDate ?? null
            ];
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'get_tracking',
            'request'        => null,
            'request_method' => 'GET',
            'response'       => json_encode($tracking, JSON_UNESCAPED_UNICODE),
            'response_code'  => 200,
            'request_uri'    => null
        ));

        return $itemsTracking;
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
        $paid = [
            "Date" => date('Y-m-d\TH:i:sP'),
            "payments" => array(
                ["status" => "PAGO",
                "marketplaceId" => 1]
            )
        ];
        $uri = "/orders/{$order}/markAsPaid";

        try {
            $request = $this->order_v2->request('PUT', $uri, ['json' => $paid]);
            $response = Utils::jsonDecode($request->getBody()->getContents());
            $this->sendOrderTransmissionStatus($order, ['marketPlaceStatus' => 'PAID']);
            $this->sendOrderTransmissionStatus($order, ['status' => 'PAID']);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if ($request->getStatusCode() != 200) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao enviar a data de pagamento. {$request->getStatusCode()} - " . json_encode($response, JSON_UNESCAPED_UNICODE)
            );
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'confirm_payment',
            'request'        => json_encode($paid, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $uri
        ));

        return true;
    }

    /**
     * Importar a dados de rastreio
     * @param string $orderIntegration Código do pedido na integradora
     * @param object $order Dado completo do pedido (Api/V1/Order/{order})
     * @param object $dataTracking Dados do rastreio do pedido (Api/V1/Tracking/{order})
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     * @todo criar funcionalidade para a integradora.
     *
     */
    public function setTrackingIntegration(string $orderIntegration, object $order, object $dataTracking): bool
    {
        $this->orderId = $this->orderId ?? $order->code;
        $estimateDate = !empty($dataTracking->expected_delivery_date)
            ? $dataTracking->expected_delivery_date
            : $order->shipping->estimated_delivery;
        $tracking = [
            'marketPlaceStatus' => 'AGUARDANDO COLETA/ENVIO',
            'tracking' => [
                "url" => $dataTracking->tracking->tracking_url,
                "number" => $dataTracking->tracking->tracking_code[0],
                "carrier" => $dataTracking->ship_company,
                "estimateDate" => date('Y-m-d\TH:i:sP', strtotime($estimateDate)),
            ]
        ];
        $uri = "/orders/{$this->orderId}";

        try {
            $request = $this->order_v2->request('PUT', $uri, ['json' => $tracking]);
            $response = Utils::jsonDecode($request->getBody()->getContents());
            $this->sendOrderTransmissionStatus($this->orderId, ['marketPlaceStatus' => \OrderStatusConst::statusDescription($order->status->code)]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if ($request->getStatusCode() != 200) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao enviar o rastreamento. {$request->getStatusCode()} - " . json_encode($response, JSON_UNESCAPED_UNICODE)
            );
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_tracking',
            'request'        => json_encode($tracking, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $uri
        ));

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
        $shippedDate = $order->tracking->shippedDate ?? '';

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
            'isDelivered' => $order->tracking->deliveredDate ?? null,
            'date' => $shippedDate ? date('Y-m-d H:i:s', strtotime($shippedDate)) : null
        );
        return $shippedDate;

        //return date('Y-m-d H:i:s', strtotime($shippedDate));
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
        $this->orderId = $this->orderId ?? $order->code;
        $shipped = [
            "shippedDate" => date('Y-m-d\TH:i:sP', strtotime($order->shipping->shipped_date ?? date('Y-m-d H:i:s')))
        ];
        $uri = "/orders/{$this->orderId}/markAsShipped";

        try {
            $request = $this->order_v2->request('PUT', $uri, ['json' => $shipped]);
            $response = Utils::jsonDecode($request->getBody()->getContents());
            $this->sendOrderTransmissionStatus($this->orderId, ['marketPlaceStatus' => \OrderStatusConst::statusDescription($order->status->code)]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if ($request->getStatusCode() != 200) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao enviar a data de envio. {$request->getStatusCode()} - " . json_encode($response, JSON_UNESCAPED_UNICODE)
            );
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_in_transit',
            'request'        => json_encode($shipped, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $uri
        ));

        return true;
    }

    /**
     * Recupera ocorrências do rastreio
     * @param string $orderIntegration Código do pedido na integradora
     * @return  array                       Dados de ocorrência do pedido. Um array com as chaves 'isDelivered' e 'occurrences', onde 'occurrences' será os dados de ocorrência do rastreio, composto pelos índices 'date' e 'occurrence'
     * @throws  InvalidArgumentException
     * @todo criar funcionalidade para a integradora.
     *
     */
    public function getOccurrenceIntegration(string $orderIntegration): array
    {
        $order = $this->getOrderIntegration($orderIntegration);
        $orderStatus = $order->status ?? '';
        $deliveredDate = isset($order->tracking->deliveredDate) ?
            date('Y-m-d H:i:s', strtotime($order->tracking->deliveredDate))
            : null;
        return [
            'isDelivered' => in_array($orderStatus, ['DELIVERED']),
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
     * Recupera data de entrega do pedido
     * @param string $orderIntegration Código do pedido na integradora
     * @return  string                      Data de entrega do pedido
     * @throws  InvalidArgumentException
     *
     */
    public function getDeliveredIntegration(string $orderIntegration): string
    {
        $order = $this->getOrderIntegration($orderIntegration);
        $deliveredDate = $order->tracking->deliveredDate ?? '';
        if (empty($deliveredDate)) {
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

        return date('Y-m-d H:i:s', strtotime($deliveredDate));
    }

    /**
     * Importar a data de entrega
     * @param string $orderIntegration Código do pedido na integradora
     * @param object $order Dado completo do pedido (Api/V1/Order/{order})
     * @return  bool                        Estado da importação
     * @throws  InvalidArgumentException
     *
     */
    public function setDeliveredIntegration(string $orderIntegration, object $order): bool
    {
        $this->orderId = $this->orderId ?? $order->code;
        $delivered = [
            "marketPlaceStatus" => "CONCLUIDO",
            "status" => "DELIVERED",
            "deliveredDate" => date('Y-m-d\TH:i:sP', strtotime($order->shipping->delivered_date ?? date('Y-m-d H:i:s')))
        ];
        $uri = "/orders/{$this->orderId}/markAsDelivered";
        try {
            $request = $this->order_v2->request('PUT', $uri, ['json' => $delivered]);
            $response = Utils::jsonDecode($request->getBody()->getContents());
            $this->sendOrderTransmissionStatus($this->orderId, ['marketPlaceStatus' => \OrderStatusConst::statusDescription($order->status->code)]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if ($request->getStatusCode() != 200) {
            throw new InvalidArgumentException(
                "Ocorreu um erro ao enviar a confirmação de entrega. {$request->getStatusCode()} - " . json_encode($response, JSON_UNESCAPED_UNICODE)
            );
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'set_delivered',
            'request'        => json_encode($delivered, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : '',
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $uri
        ));

        return true;
    }

    public function sendOrderTransmissionStatus($orderId, $transmissionStatus)
    {
        try {
            if (!isset($transmissionStatus['marketPlaceStatus']) || empty($transmissionStatus['marketPlaceStatus'])) {
                $orderDb = $this->order_v2->model_orders->getOrdersData(0, $this->orderId ?? 0);
                $transmissionStatus['marketPlaceStatus'] = \OrderStatusConst::statusDescription($orderDb['paid_status'] ?? 0);
            }
            $transmissionStatus = [
                "marketPlaceStatus" => $transmissionStatus['marketPlaceStatus'],
                "success" => $transmissionStatus['success'] ?? 'true',
                "errorMessage" => $transmissionStatus['errorMessage'] ?? '',
            ];
            $request = $this->order_v2->request(
                'PUT',
                "/orders/{$orderId}/transmissionStatus",
                [
                    'json' => $transmissionStatus
                ]);
        } catch (\Throwable $e) {

        }
    }

    public function __get($var)
    {
        return get_instance()->{$var};
    }

    public function getOrdersFromIntegrationNotifications($params = []): array
    {
        return $this->model_anymarket_order_to_update->getNewOrders($params['company_id'], $params['store_id'], $params['order_id'] ?? null);
    }

    protected function getFlowByStatus($status):int
    {
        switch ($status) {
            case 'PENDING':
                return 1;
            case 'PAID':
                return 2;
            case 'INVOICED':
                return 3;
            case 'SHIPPED':
                return 4;
            case 'DELIVERED':
                return 5;
            default:
                return 99;
        }
    }

    public function updateOrderFromIntegrationNotifications($order): array
    {
        $this->updateOrderFromIntegration($order);
        try {
            $orderIntegration = $this->getOrderIntegration($this->orderIdIntegration ?? $order["order_anymarket_id"]);
            if ($this->getFlowByStatus($orderIntegration->status ?? '') >= $order['status_flow']) {
                $this->model_anymarket_order_to_update->setIntegrated($order['id']);
                return $order + ['updated' => true];
            }
        } catch (\Throwable $exception) {}
        return $order + ['updated' => false];
    }

    public function updateOrderFromIntegration(array $order): bool
    {
        $this->orderId = $order["order_id"];
        $this->orderIdIntegration = $order["order_anymarket_id"];
        $realizedOperation = false;
        $statusToRemove = null;
        $orderDb = $this->order_v2->model_orders->getOrdersData(0, $order["order_id"]);
        try {
            $orderIntegration = $this->getOrderIntegration($this->orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $logistic = $this->order_v2->calculofrete->getLogisticStore([
            'freight_seller' => $this->order_v2->dataStore['freight_seller'],
            'freight_seller_type' => $this->order_v2->dataStore['freight_seller_type'],
            'store_id' => $this->order_v2->dataStore['id']
        ]);

        $withoutAnymarket = !$logistic['seller'] || ($logistic['type'] !== 'anymarket' && $logistic['type'] !== 'erp');
        if ($order['new_status'] == 'CANCELED') {
            $this->order_v2->log_integration("Não é possível cancelar um pedido via Anymarket",
                "O pedido {$order["order_id"]} não pode ser cancelado via ANYMARKET, é necessário que entre em contato com o marketplace",
                "E"
            );
            $transmissionStatus = [
                "marketPlaceStatus" => \OrderStatusConst::statusDescription($orderDb['paid_status']),
                "success" => "false",
                "errorMessage" => "O pedido não pode ser cancelado via ANYMARKET, é necessário que entre em contato com o marketplace",
            ];
            $response = $this->order_v2->request(
                'PUT',
                "/orders/{$this->orderId}/transmissionStatus",
                ['json' => $transmissionStatus]
            );
            return $response->getStatusCode() == 200;
        }

        $orderCancel = $this->order_v2->model_orders_to_integration->getOrderCancel($this->orderId, $this->order_v2->store);
        if ($orderCancel || in_array($orderDb['paid_status'], [
                \OrderStatusConst::CANCELED_BY_SELLER,
                \OrderStatusConst::CANCELED_BEFORE_PAYMENT,
                \OrderStatusConst::CANCELED_AFTER_PAYMENT
            ])) {
            $canceledData = [
                'date' => date("Y-m-d\TH:i:sP", strtotime($orderDb['date_cancel'] ?? date('Y-m-d H:i:s'))),
                "code" => 'BUYER_CANCELED',
            ];

            try {
                $response = $this->order_v2->request(
                    'PUT',
                    "/orders/{$this->orderId}/markAsCanceled",
                    ['json' => $canceledData]
                );
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                $this->order_v2->log_integration(
                    "Ocorreu um erro ao cancelar o pedido {$this->orderId} atualizado para Cancelado.",
                    "<h4>O pedido {$this->orderId} não pode ser cancelado.</h4><p>{$exception->getMessage()}</p>",
                    "E"
                );
                if (strpos($exception->getMessage(), 'concluído') !== false) {
                    return true;
                }
                throw new InvalidArgumentException($exception->getMessage());
            }
            if ($response->getStatusCode() == 200) {
                $this->order_v2->log_integration(
                    "Pedido {$this->orderId} atualizado para Cancelado.",
                    "<h4>Pedido {$this->orderId} cancelado com sucesso.</h4>",
                    "S"
                );
                return true;
            }
            return false;
        }

        if (isset($orderIntegration->invoice) && in_array($orderDb['paid_status'], [\OrderStatusConst::WAITING_INVOICE])) {
            try{
                $isInvoiced = $this->order_v2->setInvoiceOrder($this->getInvoiceIntegration($this->orderIdIntegration,$this->orderId));
                $transmissionStatus = [
                    "marketPlaceStatus" => "FATURADO",
                    "success" => "true",
                    "errorMessage" => "",
                ];
                $realizedOperation = true;
            }catch (\Throwable $e) {
                $transmissionStatus = [
                    "marketPlaceStatus" => "AGUARDANDO FATURAMENTO",
                    "success" => "false",
                    "errorMessage" => $e->getMessage(),
                ];
            }
            $response = $this->order_v2->request(
                'PUT',
                "/orders/{$this->orderId}/transmissionStatus",
                [
                    'json' => $transmissionStatus
                ]);
            $statusToRemove[] = \OrderStatusConst::WAITING_INVOICE;
        }

        if (isset($orderIntegration->tracking) && in_array($orderDb['paid_status'], [\OrderStatusConst::WAITING_TRACKING])) {
            try {
                if ($withoutAnymarket) {
                    $realizedOperation = true;
                    throw new \Exception(
                        "Este Pedido {$order['order_id']} possui a regra de logistica no Seller Center '{$this->sellerCenterName}', portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status."
                    );
                }
                $orderAPI = $this->order_v2->getOrder($this->orderId);

                $orderAPI->items = array_map(function ($item) use ($orderAPI) {
                    $item->{'shipping_carrier'} = $orderAPI->shipping->shipping_carrier ?? null;
                    $item->{'service_method'} = $orderAPI->shipping->service_method ?? null;
                    return $item;
                }, $orderAPI->items);

                $responseTracking = $this->order_v2->setTrackingOrder(
                    $this->getTrackingIntegration($this->orderId, $orderAPI->items),
                    $this->orderId
                );
                $transmissionStatus = [
                    "marketPlaceStatus" => "AGUARDANDO COLETA/ENVIO",
                    "success" => "true",
                    "errorMessage" => '',
                ];
                $realizedOperation = true;
                if (isset($orderIntegration->tracking->shippedDate) && !empty(isset($orderIntegration->tracking->shippedDate))) {
                    try {
                        $this->model_anymarket_order_to_update->save(0, [
                            'company_id' => $this->order_v2->company,
                            'store_id' => $this->order_v2->store,
                            'order_anymarket_id' => $this->orderIdIntegration,
                            'order_id' => $this->orderId,
                            'old_status' => 'SHIPPED',
                            'new_status' => 'SHIPPED',
                            'is_new' => 1
                        ]);
                    } catch (\Throwable $e) {

                    }
                }
            } catch (\Throwable $e) {
                $transmissionStatus = [
                    "marketPlaceStatus" => "AGUARDANDO ETIQUETA/RASTRAMENTO",
                    "success" => "false",
                    "errorMessage" => $e->getMessage(),
                ];
            }
            $response = $this->order_v2->request(
                'PUT',
                "/orders/{$this->orderId}/transmissionStatus",
                [
                    'json' => $transmissionStatus
                ]);
            $statusToRemove[] = \OrderStatusConst::WAITING_TRACKING;
        }

        if (isset($orderIntegration->tracking->shippedDate) && in_array($orderDb['paid_status'], [
                \OrderStatusConst::WITH_TRACKING_WAITING_SHIPPING
            ])
        ) {
            try {
                if ($withoutAnymarket) {
                    $realizedOperation = true;
                    throw new \Exception(
                        "Este Pedido {$order['order_id']} possui a regra de logistica no Seller Center '{$this->sellerCenterName}', portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status."
                    );
                }
                $this->order_v2->setShippedOrder($orderIntegration->tracking->shippedDate, $this->orderId);
                $transmissionStatus = [
                    "marketPlaceStatus" => "EM TRANSPORTE",
                    "success" => "true",
                    "errorMessage" => '',
                ];
                $realizedOperation = true;
            } catch (\Throwable $e) {
                $transmissionStatus = [
                    "marketPlaceStatus" => "AGUARDANDO COLETA/ENVIO",
                    "success" => "false",
                    "errorMessage" => $e->getMessage(),
                ];
            }
            $response = $this->order_v2->request(
                'PUT',
                "/orders/{$this->orderId}/transmissionStatus",
                [
                    'json' => $transmissionStatus
                ]);
            $statusToRemove[] = \OrderStatusConst::WITH_TRACKING_WAITING_SHIPPING;
        }

        if (isset($orderIntegration->tracking->deliveredDate) && in_array($orderDb['paid_status'], [
                \OrderStatusConst::SHIPPED_IN_TRANSPORT_45
            ])
        ) {
            try {
                if ($withoutAnymarket) {
                    $realizedOperation = true;
                    throw new \Exception(
                        "Este Pedido {$order['order_id']} possui a regra de logistica no Seller Center '{$this->sellerCenterName}', portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status."
                    );
                }
                $this->order_v2->setDeliveredOrder($orderIntegration->tracking->deliveredDate, $this->orderId);
                $transmissionStatus = [
                    "marketPlaceStatus" => "ENTREGUE",
                    "success" => "true",
                    "errorMessage" => "",
                ];
                $realizedOperation = true;
            } catch (\Throwable $e) {
                $transmissionStatus = [
                    "marketPlaceStatus" => "EM TRANSPORTE",
                    "success" => "false",
                    "errorMessage" => $e->getMessage(),
                ];
            }
            $response = $this->order_v2->request(
                'PUT',
                "/orders/{$this->orderId}/transmissionStatus",
                [
                    'json' => $transmissionStatus
                ]);
            $statusToRemove[] = \OrderStatusConst::SHIPPED_IN_TRANSPORT_45;
        }

        if ($statusToRemove !== null) {
            $this->order_v2->model_orders_to_integration->removeOrderToIntegrationByStatus(
                $this->orderId,
                $this->order_v2->store,
                $statusToRemove
            );
        }

        if (in_array($orderDb['paid_status'], [\OrderStatusConst::DELIVERED, \OrderStatusConst::DELIVERED_NOTIFY_MKTPLACE])) {
            $transmissionStatus = [
                "marketPlaceStatus" => "ENTREGUE",
                "success" => "true",
                "errorMessage" => "",
            ];
            $response = $this->order_v2->request(
                'PUT',
                "/orders/{$this->orderId}/transmissionStatus",
                [
                    'json' => $transmissionStatus
                ]);
            $realizedOperation = true;
        }

        if (!$realizedOperation) {
            $orderDb = $this->order_v2->model_orders->getOrdersData(0, $order["order_id"]);
            $response = $this->order_v2->request(
                'PUT',
                "/orders/{$this->orderId}/transmissionStatus",
                [
                    'json' => $transmissionStatus ?? [
                            "marketPlaceStatus" => \OrderStatusConst::statusDescription((int)$orderDb['paid_status']),
                            "success" => "true",
                            "errorMessage" => "",
                        ]
                ]);
        }

        return $realizedOperation;
    }
}