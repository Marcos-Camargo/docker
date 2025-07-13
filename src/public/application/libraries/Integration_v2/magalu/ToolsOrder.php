<?php

namespace Integration\magalu;

use App\Libraries\Cache\CacheManager;
use CI_Loader;
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
        $this->order_v2->can_integrate_incomplete_order = true;
    }

    /**
     * Busca cliente, se não encontrar cria um novo.
     *
     * @param   object  $order           Dados do Cliente.
     * @return  array   id do cliente e id do endereço
     * @throws  InvalidArgumentException
     */
    public function getClient(object $order): array
    {   
        $shipping_address   = $order->shipping->shipping_address;
        $customer           = $order->customer;

        $document = onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj);
        $urlGetClient = "/customers?document=".$document;

        //Consulta se existe um cliente cadastrado
        try {
            $request = $this->order_v2->request('GET',  $urlGetClient);
            $contentClient = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            if ($exception->getCode() != 404) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        }

        /*$customer->name                 = removeAccents($customer->name);
        $shipping_address->street       = removeAccents($shipping_address->street);
        $shipping_address->neighborhood = removeAccents($shipping_address->neighborhood);
        $shipping_address->number       = removeAccents($shipping_address->number);
        $shipping_address->complement   = removeAccents($shipping_address->complement);
        $shipping_address->reference    = removeAccents($shipping_address->reference);
        $shipping_address->city         = removeAccents($shipping_address->city);
        $shipping_address->region       = removeAccents($shipping_address->region);
        $shipping_address->full_name    = removeAccents($shipping_address->full_name);*/

        $shipping_address->number       = $shipping_address->number ?: 'S/N';
        $shipping_address->complement   = $shipping_address->complement ?: '-';
        $shipping_address->reference    = $shipping_address->reference ?: '-';

        //Caso não encontre o cliente cria o cadastro dele
        if (!isset($contentClient->id)) {
            $newClient = array(
                'name'         => substr($customer->name, 0, 50),
                'email'        => substr($customer->email, 0, 100),
                'document'     => onlyNumbers($customer->person_type === 'pf' ? $customer->cpf : $customer->cnpj),
                'birth_date'    => $customer->person_type === 'pf' ? ($customer->birth_date ?: '1980-01-01') : '',
                'address' => array(
                    'zip_code'      => onlyNumbers($shipping_address->postcode),
                    'street'        => substr($shipping_address->street, 0, 60),
                    'district'      => substr($shipping_address->neighborhood, 0, 50),
                    'number'        => substr($shipping_address->number, 0, 5),
                    'complement'    => substr($shipping_address->complement, 0, 20),
                    'reference'     => substr($shipping_address->reference, 0, 60),
                    'city'          => substr($shipping_address->city, 0, 30),
                    'state'         => substr($shipping_address->region, 0, 2),
                ),
                'phone'             => array(
                    'area_code'     => substr(onlyNumbers($shipping_address->phone), 0, 2),
                    'number'        => substr(onlyNumbers($shipping_address->phone), 2)
                ),
                'responsible_name'  => $shipping_address->full_name,
                //'state_inscription' => $customer->person_type === 'pf' ? "CPF" : "CNPJ" // Número da inscrição estadual da empresa. Caso não possua, deve ser informado o texto ISENTO. Este campo não se aplica para pessoas físicas e é obrigatório para pessoas jurídicas.
            );

            $urlCreateClient     = "/customers";
            $queryCreateClient = array('json' => $newClient);
    
            try {
                $request = $this->order_v2->request('POST', $urlCreateClient, $queryCreateClient);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException("Falha ao criar cliente do pedido ($order->code). Pedido não foi integrado. {$exception->getMessage()}");
            }

            $contentClient = Utils::jsonDecode($request->getBody()->getContents());

            //Encontrou o cliente retorna as informações
            if (isset($contentClient->id) && isset($contentClient->address_id)) {
                return array(
                    'clientId'   => $contentClient->id,
                    'addressId'  => $contentClient->address_id
                );
            } elseif (!isset($contentClient->id)) {
                throw new InvalidArgumentException('Falha ao criar o cliente do pedido ('.$order->code.'). Pedido não foi integrado ');
            }
        }

        $clientId = $contentClient->id;
        $urlGetAddress = "/customers/$clientId/address";
        $optionsGetAddress = array(
            'query' => array(
                'zip_code'   => onlyNumbers($shipping_address->postcode),
                'street'     => substr($shipping_address->street,0 , 60),
                'district'   => substr($shipping_address->neighborhood,0 , 50),
                'number'     => substr($shipping_address->number,0 , 5),
                'complement' => substr($shipping_address->complement,0 , 20),
                'reference'  => substr($shipping_address->reference,0 , 60),
                'city'       => substr($shipping_address->city,0 , 30),
                'state'      => substr($shipping_address->region,0 , 2)
            )
        );

        //Consulta se existe um endereço cadastrado para o cliente
        try {
            $request = $this->order_v2->request('GET',  $urlGetAddress, $optionsGetAddress);
            $contentAddress = Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            if ($exception->getCode() != 404) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        }

        //Caso não encontre o endereço do cliente cria o cadastro dele
        if (!isset($contentAddress->id)) {
            $newAddress = array(
                'alias'      => 'Residencial',
                'name'       => substr($shipping_address->full_name, 0, 50),
                'zip_code'   => onlyNumbers($shipping_address->postcode),
                'street'     => substr($shipping_address->street, 0, 60),
                'district'   => substr($shipping_address->neighborhood, 0, 50),
                'number'     => substr($shipping_address->number, 0, 5),
                'complement' => substr($shipping_address->complement, 0, 20),
                'reference'  => substr($shipping_address->reference, 0, 60),
                'city'       => substr($shipping_address->city, 0, 30),
                'state'      => substr($shipping_address->region, 0, 2),
                'area_code'  => substr(onlyNumbers($shipping_address->phone), 0, 2),
                'phone'      => substr(onlyNumbers($shipping_address->phone), 2)
            );

            $urlCreateAddress     = "/customers/$clientId/address";
            $queryCreateAddress = array('json' => $newAddress);

            try {
                $request = $this->order_v2->request('POST', $urlCreateAddress, $queryCreateAddress);
            } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException("Falha ao criar Endereço para o cliente cliente do pedido ($order->code). Pedido não foi integrado. {$exception->getMessage()}");
            }

            $contentAddress = Utils::jsonDecode($request->getBody()->getContents());

            //Encontrou o endereço e retorna as informações
            if (!isset($contentAddress->id) ) {
                throw new InvalidArgumentException('Falha ao criar o endereço do pedido ('.$order->code.'). Pedido não foi integrado ');
            }
        }

        return array(
            'clientId'   => $clientId,
            'addressId'  => $contentAddress->id
        );
    }   

    private function createCart($items): ?string
    {
        $body = [];
        $body['json'] = [];
        $body['json']['items'] = [];
        foreach ($items as $item) {
            $body['json']['items'][] = [
                'sku'       => $item['id_erp'],
                'seller'    => $item['seller'],
                'quantity'  => $item['quantity'],
            ];
        }

        $urlCart = '/carts/';
        $response = $this->order_v2->request('POST', $urlCart, $body);
        $contentOrder = Utils::jsonDecode($response->getBody()->getContents(), true);

        //Garantindo que foi inserido todos os itens selecionados no carrinho
        if (empty($contentOrder['id']) || empty($contentOrder['items']) || count($contentOrder['items']) != count($items)) {
            return null;
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'create_cart',
            'request'        => json_encode($body, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => json_encode($contentOrder, JSON_UNESCAPED_UNICODE),
            'response_code'  => $response->getStatusCode(),
            'request_uri'    => $urlCart
        ));

        return $contentOrder['id'];
    }

    private function getDeliveries(string $zipcode, string $cartId, array $items): array
    {
        $cartMd5 = md5(json_encode($items));
        $key_redis = "{$this->order_v2->sellerCenter}:integration_logistic:magalu:cart:$cartMd5:zipcode:$zipcode";
        $data_redis = CacheManager::get($key_redis);
        if ($data_redis !== null) {
            $cartId = $data_redis;
        }

        $response = $this->order_v2->request('GET', "/shipping/$zipcode/cart/$cartId");
        $contentOrder = Utils::jsonDecode($response->getBody()->getContents(), true);

        $delivery_id    = $contentOrder['deliveries'][0]['id'] ?? null;
        $modality_id    = $contentOrder['deliveries'][0]['modalities'][0]['id'] ?? null;
        $modality_type  = $contentOrder['deliveries'][0]['modalities'][0]['type'] ?? null;

        //Garantindo que foi inserido todos os itens selecionados no carrinho
        if (empty($delivery_id) || empty($modality_id) || empty($modality_type)) {
            throw new InvalidArgumentException("Não foi possível obter o id do carrinho. " . json_encode($contentOrder, JSON_UNESCAPED_UNICODE));
        }

        return array(
            array(
                'id'       => $delivery_id,
                'modality' => array(
                    'id'     => $modality_id,
                    'type'   => $modality_type
                )
            )
        );
    }

    /**
     * Chega Ip
     *
     * @return  string  Ip
     */
    private function getUserIP(): string
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (strpos($ip, ',') !== false) {
            $ipArray = explode(',', $ip);
            $ip = trim($ipArray[0]);
        }

        return $ip;
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
        $this->orderId      = $order->code;

        //Cria o carrinho
        $items = [];
        foreach ($order->items as $item) {
            $idErpExplode = explode('_', $item->sku_integration);

            $items[] = [
                'prd_id'    => trim($item->product_id),
                'sku'       => trim($item->sku_variation ?? $item->sku),
                'id_erp'    => $idErpExplode[0],
                'seller'    => $idErpExplode[1],
                'quantity'  => $item->qty,
                'price'     => roundDecimal($item->original_price)
            ];
        }

        try {
            $cartId = $this->createCart($items);
        } catch (ClientException | InvalidArgumentException $exception) {
            $this->saveLogIntegrationInOrder($exception->getMessage());
        }

        try {
            $client = $this->getClient($order);
            $deliveries = $this->getDeliveries($order->shipping->shipping_address->postcode, $cartId, $items);
        } catch (ClientException | InvalidArgumentException $exception) {
            $this->saveLogIntegrationInOrder($exception->getMessage());
        }
      
        $updateCart = array(
            'billing_address_id'    => $client['addressId'],
            'customer_id'           => $client['clientId'],
            'shipping_address_id'   => $client['addressId'],
            'deliveries'            => $deliveries,
        );

        $urlUpdateCart     = "/carts/".$cartId;
        $queryUpdateCart = array('json' => $updateCart);

        try {
            $request = $this->order_v2->request('PATCH', $urlUpdateCart, $queryUpdateCart);
        } catch (ClientException | InvalidArgumentException $exception) {
            $this->saveLogIntegrationInOrder($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'update_cart',
            'request'        => json_encode($queryUpdateCart, JSON_UNESCAPED_UNICODE),
            'request_method' => 'PATCH',
            'response'       => $request->getBody()->getContents(),
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlUpdateCart
        ));

        $createOrder = array(
            'cart_id'             => $cartId,
            //'customer_ip_address' => $this->getUserIP(),
            'payment_method_id'   => 'external',
            'installments'        => 1,
            'partner_order_id'    => $order->marketplace_number,
            'shipping_reference_contact' => array(
                'phone_ddd'        => substr(onlyNumbers($shipping_address->phone), 0, 2),
                'phone_number'     => substr(onlyNumbers($shipping_address->phone), 2),
                'payment_info'     => array(),
            ),
        );

        $urlCreateOrder     = "/orders";
        $queryCreateOrder = array('json' => $createOrder);

        try {
            $request = $this->order_v2->request('POST', $urlCreateOrder, $queryCreateOrder);

            $contentOrder = Utils::jsonDecode($request->getBody()->getContents());

            if (!isset($contentOrder->display_order_id)) {
                $this->saveLogIntegrationInOrder("Código de identificação do pedido não localizado no retorno." . json_encode($contentOrder, JSON_UNESCAPED_UNICODE));
            }

            $this->orderIdIntegration = $contentOrder->display_order_id;
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->saveLogIntegrationInOrder($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order->code,
            'type'           => 'create_order',
            'request'        => json_encode($queryCreateOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'POST',
            'response'       => json_encode($contentOrder, JSON_UNESCAPED_UNICODE),
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlCreateOrder
        ));

        try {
            if (!in_array($order->status->code, array(1,2,96))) {
                $this->setApprovePayment($this->orderId, $this->orderIdIntegration);
            }
        } catch (InvalidArgumentException $exception) {
            $error_message = $exception->getMessage();
            echo "Não foi possível aprovar o pagamento do pedido $this->orderId. $error_message";
            $this->order_v2->log_integration("Erro para aprovar o pagamento o pedido ($this->orderId)", "<h4>Não foi possível aprovar o pagamento do pedido $this->orderId</h4> <ul><li>$error_message</li></ul>", "E");
        }

        return array(
            'id'        => $this->orderIdIntegration,
            'request'   => "$urlCreateOrder\n" . Utils::jsonEncode($queryCreateOrder, JSON_UNESCAPED_UNICODE)
        );
    }

   
    /**
     * Recupera dados do pedido na integradora.
     *
     * @param   string|int      $order  Código do pedido na integradora.
     * @return  array|object            Dados do pedido na integradora.
     */
    public function getOrderIntegration($order)
    {
        $urlOrder = "/orders/$order";
        
        try {
            $request = $this->order_v2->request('GET', $urlOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $order = Utils::jsonDecode($request->getBody()->getContents());

        if (!property_exists($order, 'id')) {
            throw new InvalidArgumentException("Pedido $order não localizado");
        }

        return $order->sub_orders[0] ?? [];
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
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado.');
        }

        try {
            $order = $this->getOrderIntegration($this->orderIdIntegration);
            $this->setApprovePayment($orderid, $orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIdIntegration) não localizado.");
        }

        $deliveredDate = null;

        foreach ($order->events as $event) {
            if ($event->status->id == 10) {
                $deliveredDate = !empty($event->date) ? $event->date : null;
                break;
            }
        }

        $invoiceResponse = $order->invoices;
        foreach ($invoiceResponse as $invoice) {
            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'invoice_order',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($invoice, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));

            return [
                'date'          => dateFormat($invoice->createdAt, DATETIME_INTERNATIONAL),
                'value'         => roundDecimal($invoice->amount),
                'serie'         => (int)$invoice->serie,
                'number'        => (int)clearBlanks($invoice->number),
                'key'           => clearBlanks($invoice->key),
                'link'          => '',
                'isDelivered'   => $deliveredDate
            ];
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
        // Pedido já foi confirmado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'confirm_payment'))) {
            return true;
        }

        $urlupdateOrder     = "/orders/".$this->orderIdIntegration;
        //$queryUpdateOrder   = array('json' => array('status' => '2'));
        $queryUpdateOrder   = array('json' => array('status' => 'approved'));

        try {
            $request = $this->order_v2->request('PATCH', $urlupdateOrder, $queryUpdateOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException('Falha ao atualizar o status do pedido ('.$order.'). Pedido não foi integrado '.$exception->getMessage());
        }

        return $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $this->orderId,
            'type'           => 'confirm_payment',
            'request'        => json_encode($queryUpdateOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'PATCH',
            'response'       => $request->getBody()->getContents(),
            'response_code'  => $request->getStatusCode(),
            'request_uri'    => $urlupdateOrder
        ));
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
        $cancelOrder = array(
            //'status' => '3',
            'status' => 'cancelled'
        );
    
        $urlCancelOrder     = "/orders/".$this->orderIdIntegration;
        $queryCancelOrder = array('json' => $cancelOrder);
    
        try {
            $request = $this->order_v2->request('PATCH', $urlCancelOrder, $queryCancelOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $this->order_v2->model_orders_integration_history->create(array(
            'order_id'       => $order,
            'type'           => 'cancel_order',
            'request'        => json_encode($queryCancelOrder, JSON_UNESCAPED_UNICODE),
            'request_method' => 'PATCH',
            'response'       => $request->getBody()->getContents(),
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
        if ($this->checkOrderCanceled()) {
            throw new InvalidArgumentException('Pedido deve ser cancelado.');
        }

        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        $itemsTracking = array();

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        $traking = $order->shipping;
        $generatedDate = date(DATETIME_INTERNATIONAL);
        if (!empty($traking->carriers[0]->created_at)) {
            $generatedDate = dateFormat($traking->carriers[0]->created_at, DATETIME_INTERNATIONAL);
        }

        $generatedDate = date(DATETIME_INTERNATIONAL);
        if (!empty($traking->carriers[0]->created_at)) {
            $generatedDate = dateFormat($traking->carriers[0]->created_at, DATETIME_INTERNATIONAL);
        }
        
        $deliveredDate = null;

        foreach ($order->events as $event) {
            if ($event->status->id == 10) {
                $deliveredDate = !empty($event->date) ? $event->date : null;
                break;
            }
        }

        foreach ($items as $item) {
            $itemsTracking[$item->sku_variation ?? $item->sku] = array(
                'quantity'                  => $item->qty,
                'shippingCompany'           => $traking->service->type,
                'trackingCode'              => $traking->package,
                'trackingUrl'               => $traking->tracking->url ?? null,
                'generatedDate'             => $generatedDate,
                'shippingMethodName'        => $traking->service->type,
                'shippingMethodCode'        => $traking->service->type, // Se não exitir informar o mesmo que o shippingMethodName
                'deliveryValue'             => 0,
                'documentShippingCompany'   => null,
                'estimatedDeliveryDate'     => null,
                'labelA4Url'                => null,
                'labelThermalUrl'           => null,
                'labelZplUrl'               => null,
                'labelPlpUrl'               => null,
                'isDelivered'               => $deliveredDate
            );
        }

        if (!count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'get_tracking'))) {
            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'get_tracking',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($traking, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));
        }

        return $itemsTracking;
    }

    /**
     * Importar a dados de rastreio.
     * @warning Magalu não recebe rastreio.
     *
     * @param   string  $orderIntegration   Código do pedido na integradora.
     * @param   object  $order              Dado completo do pedido (Api/V1/Order/{order}).
     * @param   object  $dataTracking       Dados do rastreio do pedido (Api/V1/Tracking/{order}).
     * @return  bool                        Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setTrackingIntegration(string $orderIntegration, object $order, object $dataTracking): bool
    {
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
        if ($this->checkOrderCanceled()) {
            return [];
        }

        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        $delivery_date = '';
        
        foreach ($order->events as $event) {
            if($event->date){
                $delivery_date = dateFormat($event->date, DATETIME_INTERNATIONAL, null);
            }
            $delivery_date = dateNow()->format(DATETIME_INTERNATIONAL);
        }

        if (!empty($delivery_date)) {
            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'get_shipped',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($order->events, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));
        }
        $returnData = array(
            'isDelivered'               => $order->status === 'delivered' ?? null,
            'date'                      => $delivery_date
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
        if ($this->checkOrderCanceled()) {
            return [
                'isDelivered'   => false,
                'dateDelivered' => null,
                'occurrences'   => []
            ];
        }

        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        $is_delivered = false;
        foreach ($order->events as $event) {
            if ($event->status->id == 10) {
                $is_delivered = true;
            }
        }
        
        $deliveredDate = $is_delivered ? (empty($order->events->date ) ? dateNow()->format(DATETIME_INTERNATIONAL) : dateFormat($order->events->date, DATETIME_INTERNATIONAL, null)) : null;

        if ($is_delivered) {
            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'get_delivered',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($order->events, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));
        }

        return [
            'isDelivered'   => $is_delivered,
            'dateDelivered' => $deliveredDate,
            'occurrences'   => []
        ];
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
    public function setOccurrenceIntegration(string $orderIntegration, object $order, array $dataOccurrence): bool
    {
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
        if ($this->checkOrderCanceled()) {
            return '';
        }

        try {
            $order = $this->getOrderIntegration($orderIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($orderIntegration) não localizado.");
        }

        $delivered_date = '';
        
        foreach ($order->events as $event) {
            if ($event->status->id == 10) {
                if (empty($event->date)) {
                    $delivered_date = dateNow()->format(DATETIME_INTERNATIONAL);
                    break;
                }
                $delivered_date = dateFormat($event->date, DATETIME_INTERNATIONAL, null);
                break;
            }
        }

        if ($delivered_date) {
            $this->order_v2->model_orders_integration_history->create(array(
                'order_id'       => $this->orderId,
                'type'           => 'get_delivered',
                'request'        => null,
                'request_method' => 'GET',
                'response'       => json_encode($order->events, JSON_UNESCAPED_UNICODE),
                'response_code'  => 200,
                'request_uri'    => null
            ));
        }

        return $delivered_date;
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
        return true;   
    }

    public function checkOrderCanceled(): bool
    {
        // Pedido já foi cancelado
        if (count($this->order_v2->model_orders_integration_history->getByOrderIdAndType($this->orderId, 'cancel_order'))) {
            return true;
        }

        try {
            $order = $this->getOrderIntegration($this->orderIdIntegration);
        } catch (ClientException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$order) {
            throw new InvalidArgumentException("Pedido ($this->orderIdIntegration) não localizado.");
        }

        foreach ($order->events as $event) {
            if ($event->status->id == 3) {
                try {
                    $date_cancelled = dateFormat($event->date, DATETIME_INTERNATIONAL);
                    $this->order_v2->setCancelOrder($this->orderId, $date_cancelled, 'Cancelado pelo seller via integradora.');
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException("Não possível realizar o cancelamento do pedido $this->orderId. {$exception->getMessage()}");
                }

                $this->order_v2->model_orders_integration_history->create(array(
                    'order_id'       => $this->orderId,
                    'type'           => 'cancel_order',
                    'request'        => $date_cancelled,
                    'response'       => json_encode($order, JSON_UNESCAPED_UNICODE),
                    'request_method' => 'PUT',
                    'response_code'  => 200
                ));

                return true;
            }
        }

        return false;
    }

    private function saveLogIntegrationInOrder(string $error_message)
    {
        $this->order_v2->log_integration("Erro para integrar o pedido ($this->orderId)", "<h4>Não foi possível integrar o pedido $this->orderId</h4> <ul><li>$error_message</li></ul>", "E");
        throw new InvalidArgumentException("Falha ao criar o pedido ($this->orderId). Pedido não foi integrado $error_message");
    }
}