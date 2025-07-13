<?php

namespace Marketplaces\External;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use stdClass;
use function foo\func;

require_once 'system/libraries/Vendor/autoload.php';
require_once 'BaseExternal.php';

class Magalu extends BaseExternal
{
    /**
     * Instantiate a new Vtex instance.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $base_uri = 'https://backoffice-gate-sandbox.luizalabs.com/backoffice/v1/gateway';
        if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            $base_uri = 'https://backoffice-gate.magazineluiza.com.br/backoffice/v1/gateway';
        }
        // Ambiente local
        if (ENVIRONMENT === 'local') {
            $base_uri = 'https://webhook.site/ad508266-1d35-4395-bbd9-2538ba6f7d3c';
        }
        $this->setBaseUri($base_uri);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function setAuthTokenNfe()
    {
        // Token ainda é válido.
        if ($this->getCredentials('expire_at') && $this->getCredentials('expire_at') < time() && $this->getCredentials('type_token') == 'nfe') {
            return;
        }

        $client_id = $this->model_settings->getValueIfAtiveByName('client_id_magalu_nfe');
        $client_secret = $this->model_settings->getValueIfAtiveByName('client_secret_magalu_nfe');

        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("Parâmetros client_id_magalu e client_secret_magalu não configurados");
        }

        $uri = 'https://docs-conformidade-fiscal-gateway-staging.magazineluiza.com.br/seller-checkup/token';
        if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            $uri = 'https://id-b2b.magazineluiza.com.br/auth/realms/B2B/protocol/openid-connect/token'; // @todo Jerry irá enviar a nova url de produção.
        }
        $options = array(
            'form_params' => array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'grant_type'    => 'client_credentials'
            ),
            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'charset'       => 'UTF-8'
            )
        );

        try {
            $request    = $this->client->request('POST', $uri, $options);
            $response   = json_decode($request->getBody()->getContents());

            // Define a credencial.
            $credentials = new StdClass;
            $credentials->Authorization = "Bearer $response->access_token";
            $credentials->access_token = $response->access_token;
            $credentials->expires_in = $response->expires_in;
            $credentials->refresh_expires_in = $response->refresh_expires_in;
            $credentials->refresh_token = $response->refresh_token;
            $credentials->token_type = $response->token_type;
            $credentials->expire_at = $response->expires_in + time();
            $credentials->type_token = 'nfe';
        } catch (GuzzleException | BadResponseException $exception) {
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "{$exception->getMessage()}\ntype=auth\naction=access_token\nuri=$uri", "E");
            throw new Exception("Ocorreu um erro para auntenticar. {$exception->getMessage()}");
        }

        $this->setMapAuthRequest(array(
            'Authorization' => [
                'field' => 'Authorization',
                'type'  => 'headers'
            ]
        ));

        // Define os campos para ser utilizado nas requisições com o marketplace.
        $this->setExternalCredentials($credentials);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function setAuthToken()
    {
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $backtrace = $backtrace[1] ?? null;
        if ($backtrace['function'] == 'notifyNfeValidation') {
            $this->setAuthTokenNfe();
            return;
        }
        // Token ainda é válido.
        if ($this->getCredentials('expire_at') && $this->getCredentials('expire_at') < time() && $this->getCredentials('type_token') == 'gateway') {
            return;
        }

        $client_id = $this->model_settings->getValueIfAtiveByName('client_id_magalu');
        $client_secret = $this->model_settings->getValueIfAtiveByName('client_secret_magalu');

        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("Parâmetros client_id_magalu e client_secret_magalu não configurados");
        }

        $uri = 'https://idmagalu-staging-external.luizalabs.com/auth/realms/CORP/protocol/openid-connect/token';
        if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            $uri = 'https://idmagalu.luizalabs.com/auth/realms/CORP/protocol/openid-connect/token';
        }
        $options = array(
            'form_params' => array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'grant_type'    => 'client_credentials'
            ),
            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'charset'       => 'UTF-8'
            )
        );

        try {
            $request    = $this->client->request('POST', $uri, $options);
            $response   = json_decode($request->getBody()->getContents());

            // Define a credencial.
            $credentials = new StdClass;
            $credentials->Authorization = "Bearer $response->access_token";
            $credentials->access_token = $response->access_token;
            $credentials->expires_in = $response->expires_in;
            $credentials->refresh_expires_in = $response->refresh_expires_in;
            $credentials->refresh_token = $response->refresh_token;
            $credentials->token_type = $response->token_type;
            $credentials->expire_at = $response->expires_in + time();
            $credentials->type_token = 'gateway';
        } catch (GuzzleException | BadResponseException $exception) {
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "{$exception->getMessage()}\ntype=auth\naction=access_token\nuri=$uri", "E");
            throw new Exception("Ocorreu um erro para auntenticar. {$exception->getMessage()}");
        }

        $this->setMapAuthRequest(array(
            'Authorization' => [
                'field' => 'Authorization',
                'type'  => 'headers'
            ]
        ));

        // Define os campos para ser utilizado nas requisições com o marketplace.
        $this->setExternalCredentials($credentials);
    }

    /**
     * @param int $store_id
     * @return void
     * @throws Exception
     */
    public function notifyStore(int $store_id)
    {
        $store              = $this->model_stores->getStoresData($store_id);
        $store_phone        = separatePhoneAndDdd($store['phone_1']);
        $store_phone_ddd    = $store_phone['ddd'];
        $store_phone_number = $store_phone['phone'];
        $bank               = $this->model_banks->getBankNumber($store['bank']);
        $addressType        = strtok($store['business_street'], " ");
        $ibgeCode           = $this->model_cities->getCodeByCityAndUf($store['business_town'], $store['business_uf']);

        $array_store = [
            "company" => "EPOCA",
            "source" => "CONECTA_TOMADOR",
            "category" => "TOMADOR",
            "transactionId" => null,
            "uuidProtocol" => null,
            "referenceId" => $store['id'],
            "record" => [
                "value" => [
                    "taker" => [
                        "organization"          => onlyNumbers('01239313000160'), // Fixo 01239313000160
                        "documentNumber"        => onlyNumbers($store['CNPJ']),
                        "name"                  => $store['raz_social'],
                        "tradeName"             => $store['name'],
                        "municipalRegistration" => null,
                        "stateRegistration"     => onlyNumbers($store['inscricao_estadual']),
                        "email"                 => $store['responsible_email'],
                        "taxRegime"             => "1", // 1- Simples Nacional | 2- Lucro Presumido | 3 - Lucro Real
                        "address" => [
                            "country"               => "Brasil",
                            "countryCode"           => 1058,
                            "zipCode"               => onlyNumbers($store['business_code']),
                            "addressDescription"    => $store['business_street'],
                            "addressType"           => $addressType,
                            "number"                => $store['business_addr_num'],
                            "complement"            => $store['business_addr_compl'],
                            "neighborhood"          => $store['business_neighborhood'],
                            "cityDescription"       => $store['business_town'],
                            "state"                 => $store['business_uf'],
                            "ibgeCode"              => $ibgeCode['code_city'] ?? null
                        ],
                        "phone" => [
                            "areaCode"      => $store_phone_ddd,
                            "phoneNumber"   => $store_phone_number
                        ]
                    ],
                    "bankDetails" => [
                        [
                            "codBank"           => $bank,
                            "branchBank"        => $store['agency'],
                            "accountBank"       => $store['account'],
                            //"respAccountBank"   => "string"
                        ]
                    ]
                ]
            ]
        ];

        $array_store['record']['value'] = json_encode($array_store['record']['value'], JSON_UNESCAPED_UNICODE);

        $uri = '';
        $options = array(
            'json' => $array_store
        );

        try {
            $request = $this->request('POST', $uri, $options);
            $response = json_decode($request->getBody()->getContents());
        } catch (Exception $exception) {
            $message_error = $exception->getMessage();
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\ncode={$exception->getCode()}\nstore_id=$store_id\ntype=order\naction=create\nuri={$this->getBaseUri($uri)}\nrequest=".json_encode($array_store, JSON_UNESCAPED_UNICODE), "E");
            $this->model_external_integration_history->create(array(
                'register_id'       => $store_id,
                'type'              => 'store',
                'method'            => 'create',
                'uri'               => $this->getBaseUri($uri),
                'request'           => json_encode($array_store, JSON_UNESCAPED_UNICODE),
                'response_webhook'  => '{}',
                'status_webhook'    => 0,
                'response'          => $message_error,
            ));
            throw new Exception($message_error, $exception->getCode());
        }

        $this->model_external_integration_history->create(array(
            'register_id'   => $store_id,
            'external_id'   => $response->records[0]->protocol,
            'type'          => 'store',
            'method'        => 'create',
            'uri'           => $this->getBaseUri($uri),
            'request'       => json_encode($array_store, JSON_UNESCAPED_UNICODE),
            'response'      => json_encode($response, JSON_UNESCAPED_UNICODE),
        ));
    }

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    public function receiveStore(object $data)
    {
        if (!property_exists($data, 'uuidProtocol')) {
            throw new Exception("Protocolo não encontrado.");
        }

        $protocol_id = $data->uuidProtocol;
        $status = $data->status === 'SUCCESS';

        $external_integration = $this->model_external_integration_history->getByExternalId($protocol_id);
        if (!$external_integration) {
            return;
        }

        $this->model_external_integration_history->updateByExternalId($protocol_id, array(
            'response_webhook'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            'status_webhook'    => $status
        ));
    }

    /**
     * @param   int     $order_id
     * @param   string  $action paid | cancel | refund
     * @return  void
     * @throws  Exception
     */
    public function notifyOrder(int $order_id, string $action)
    {
        $order           = $this->model_orders->getOrdersData(0, $order_id);
        $payments        = $this->model_payment->getByOrderId($order_id);
        $store           = $this->model_stores->getStoresData($order['store_id']);
        $order_key_sufix = in_array($action, array('cancel', 'refund')) ? 'C' : 'V';
        $orderKey        = "$order[id]-$order_key_sufix";

        if (empty($payments)) {
            $message_error = "Pagamento não localizado para o pedido $order_id.";
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        // Recupera o pagamento com os dados preenchidos.
        $filter_payments = array_filter($payments, function ($payment) {
            return
                !empty($payment['payment_id']) &&
                !empty($payment['data_vencto']) &&
                !empty($payment['forma_id']) &&
                !empty($payment['parcela']) &&
                !empty($payment['transaction_id']) &&
                !empty($payment['autorization_id']);
        });

        // Não pagamento com os dados completos.
        if (count($filter_payments) != count($payments)) {
            $message_error = "Existem pagamentos incompletos para o pedido $order_id.";
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        if (strlen($payments[0]['autorization_id']) < 10) {
            $message_error = "Autorization id do pagamentos incorreto para o pedido $order_id.";
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        if ($action == 'cancel' && empty($order['data_pago'])) {
            $message_error = "Pedido $order_id não deve ser cancelado, não foi pago.";
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        // Parte de comissão, cálculo feito dentro da tela do pedido.
        $totalTax = 0;
        $hasCampaigns = $this->model_orders->getDetalheTaxas($order_id);
        if (count($hasCampaigns) > 0) {
            for ($i = 0; $i < count($hasCampaigns); $i++) {
                $totalTax += $hasCampaigns[$i]['total_desconto'];
            }
        }

        $tipo_frete = $this->model_orders->getMandanteFretePedido($order_id);
        $taxas = $order['service_charge'] + $order['vat_charge'];
        $comission_amount = $tipo_frete['taxa_descontada'];

        if ($totalTax > 0) {
            $comission_amount = $totalTax;
        } else if ($tipo_frete['expectativaReceb'] == "0") {
            $comission_amount = $taxas;
        }

        $payment_id = $payments[0]['payment_id'];
        $partialOrTotal = 'T';

        // Se está cancelado, ver se existem mais pedidos para o pagamento, assim será cancelamento parcial.
        if ($action == 'cancel') {
            $orders_payment = $this->model_orders_payment->getOrdersByPaymentId($payment_id);
            if (count($orders_payment) > 1) {
                $partialOrTotal = 'P';
            }
        } elseif ($action == 'refund') {
            $data_order_refund = $this->model_product_return->getByOrderId($order_id);
            if (empty($data_order_refund)) {
                $message_error = "Pedido $order_id não tem devolução cadastrada.";
                get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
                $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
                throw new Exception($message_error);
            }

            if (empty($data_order_refund[0]['returned_at'])) {
                $message_error = "Pedido $order_id não tem data de devolução concluída.";
                get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
                $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
                throw new Exception($message_error);
            }

            $order_items = $this->model_orders->getOrdersItemData($order_id);
            $comission_returned_amount = 0;
            $all_items_returned = true;
            foreach ($order_items as $item) {
                if (is_null($item['variant'])) {
                    $data_return_item = getArrayByValueIn($data_order_refund, $item['product_id'], 'product_id');
                } else {
                    $data_return_item = getArrayByValueIn($data_order_refund, [$item['product_id'], $item['variant']], ['product_id','variant']);
                }

                // Produto não foi devolvido.
                if (empty($data_return_item)) {
                    $all_items_returned = false;
                    continue;
                }

                if ($data_return_item['quantity_requested'] != $item['qty']) {
                    $all_items_returned = false;
                }

                $service_charge_rate = $order['service_charge_rate'];
                $commissioning_orders_item = $this->model_commissioning_orders_items->getCommissionByOrderAndItem($order_id, $item['id']);
                if ($commissioning_orders_item) {
                    $service_charge_rate = $commissioning_orders_item['comission'];
                }

                $total_item_returned = $item['rate'] * $data_return_item['quantity_requested'];
                $total_comission_item_returned = $total_item_returned * ($service_charge_rate / 100);
                $comission_returned_amount += $total_comission_item_returned;
            }

            // Nem todos os itens foram devolvidos, então será uma comissão parcial.
            if (!$all_items_returned) {
                $comission_amount = $comission_returned_amount;
                $partialOrTotal = 'P';
            } else {
                $orders_payment = $this->model_orders_payment->getOrdersByPaymentId($payment_id);
                if (count($orders_payment) > 1) {
                    $partialOrTotal = 'P';
                }
            }
        }

        $comission_amount = roundDecimal($comission_amount);

        if (empty($comission_amount)) {
            $message_error = "Pedido $order_id não valor de comissão para ser enviada.";
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        $package_payments = array_map(function($payment) use ($comission_amount) {
            // @todo fazer o cálculo da comissão por forma de pagamento. Higor deu a sugetão de receber o valor do pagamento e fazer a comissão proporcional pela forma paga.
            return [
                "acquirerId" => "MP", // Por enquanto mp, magalu pay
                "acquirerName" => "Magalu Pagamentos",
                "amount" => $comission_amount,
                "autorizationCode" => $payment['autorization_id'],//"códido de autorização",
                //"cardNumberMask" => "número do cartão mascarado, caso existir",
                "nsu" => $payment['transaction_id'],
                "paidDate" => $payment['data_vencto'],//"2024-07-02T22:55:43.000000Z",
                "paymentMethod" => $payment['forma_id'],//"CARTAO_CREDITO",
                "installments" => (int)($payment['parcela'] ?? 1)
            ];
        }, $payments);

        // Por enquanto somente 1 pagamento.
        $package_payments = array($package_payments[0]);

        $package = [
            "amount" => $comission_amount,
            "event" => "COMISSAOMKT_EPOCA",
            "orderKey" => $payment_id,
            "typeOrigin" => "3P", // 1P ou 3P
            "tid" => $payment_id, // orderKey
            "payments" => $package_payments
        ];

        if (in_array($action, array('cancel', 'refund'))) {
            $package['partialOrTotal'] = $partialOrTotal; // P para parcial ou T para total, somente no caso de cancelamento
            $package['refundID'] = "T"; // id do cancelamento
        }

        $transaction_date = dateNow()->format(DATE_INTERNATIONAL);
        if ($action == 'paid') {
            $transaction_date = dateFormat($order['date_time'], DATE_INTERNATIONAL);
        } else if ($action == 'cancel') {
            $transaction_date = dateFormat($order['date_cancel'] ?: dateNow()->format(DATETIME_INTERNATIONAL), DATE_INTERNATIONAL);
        } else if ($action == 'refund') {
            if (!empty($data_order_refund)) {
                $data_order_refund = $data_order_refund[0];
                $transaction_date = dateFormat($data_order_refund['returned_at'], DATE_INTERNATIONAL);
            }
        }

        $array_order = [
            "company" => "EPOCA",
            "source" => "CONECTA",
            "category" => "NFSE_CONCILIACAO",
            "transactionId" => null,
            "uuidProtocol" => null,
            "referenceId" => $order['id'],
            "record" => [
                "value" => [
                    "conciliacao" => [
                        "operationType" => in_array($action, array('cancel', 'refund')) ? 'C' : 'V', //Operação V para venda C para cancelamento)
                        "orderKey" => $payment_id,
                        "storeId" => 200, // Por enquanto fixo 200.
                        "tid" => $payment_id, // orderKey
                        "transactionDate" => $transaction_date,
                        "package" => array($package),
                        "channel" => "Epoca Marketplace",
                        "company" => "EPOCA",
                        "project" => "Epoca Marketplace",
                        "origin" => "CONECTALA",
                        "transactionID" => $orderKey
                    ],
                    "nfse" => [
                        "transactionDate" => $transaction_date,//"2024-01-24",
                        "glDate" => $transaction_date, //dateFormat($order['date_time'], DATE_INTERNATIONAL),
                        "orderSite" => $order['id'],
                        "sourceId" => $orderKey,
                        "invoiceAmount" => in_array($action, array('cancel', 'refund')) ? (-$comission_amount) : $comission_amount,
                        //"paymentMethod" => "AR030",
                        //"paymentDescription" => "BOLETO",
                        "typeDescription" => "SERV_INTERM_EP",
                        "operation" => in_array($action, array('cancel', 'refund')) ? "Estorno" : "Venda",
                        "organization" => onlyNumbers('01239313000593'), // Fixo 01239313000593 - CNPJ Janus
                        "provider" => [
                            "documentNumber" => onlyNumbers('01239313000593') // Fixo 01239313000593 - CNPJ Janus
                        ],
                        "taker" => [
                            "name" => $store['raz_social'],
                            "documentNumber" => onlyNumbers($store['CNPJ'])
                        ]
                    ]
                ]
            ]
        ];

        $array_order['record']['value'] = json_encode($array_order['record']['value'], JSON_UNESCAPED_UNICODE);

        $uri = '';
        $options = array(
            'json' => $array_order
        );

        try {
            $request = $this->request('POST', $uri, $options);
            $response = json_decode($request->getBody()->getContents());
        } catch (Exception $exception) {
            $message_error = $exception->getMessage();
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\ncode={$exception->getCode()}\norder_id=$order_id\ntype=order\naction=$action\nuri={$this->getBaseUri($uri)}\nrequest=".json_encode($array_order, JSON_UNESCAPED_UNICODE), "E");
            $this->model_external_integration_history->create(array(
                'register_id'       => $order_id,
                'type'              => 'order',
                'method'            => $action,
                'uri'               => $this->getBaseUri($uri),
                'request'           => json_encode($array_order, JSON_UNESCAPED_UNICODE),
                'response_webhook'  => '{}',
                'status_webhook'    => 0,
                'response'          => $message_error,
            ));
            throw new Exception($message_error, $exception->getCode());
        }

        $this->model_external_integration_history->create(array(
            'register_id'   => $order_id,
            'external_id'   => $response->records[0]->protocol,
            'type'          => 'order',
            'method'        => $action,
            'uri'           => $this->getBaseUri($uri),
            'request'       => json_encode($array_order, JSON_UNESCAPED_UNICODE),
            'response'      => json_encode($response, JSON_UNESCAPED_UNICODE),
        ));
    }

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    public function receiveOrder(object $data)
    {
        if (!property_exists($data, 'uuidProtocol')) {
            throw new Exception("Protocolo não encontrado.");
        }

        $protocol_id = $data->uuidProtocol;
        $status = $data->status === 'SUCCESS';

        $external_integration = $this->model_external_integration_history->getByExternalId($protocol_id);
        if (!$external_integration) {
            return;
        }

        $this->model_external_integration_history->updateByExternalId($protocol_id, array(
            'response_webhook'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            'status_webhook'    => $status
        ));
    }

    /**
     * @param int $order_id
     * @return void
     * @throws Exception
     */
    public function notifyNfeValidation(int $order_id)
    {
        $url = 'https://docs-conformidade-fiscal-gateway-staging.magazineluiza.com.br/sellers-chk/ws/nfe/v4/validate';
        if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            $url = 'https://docs-conformidade-fiscal-gateway.magazineluiza.com.br/sellers-chk/ws/nfe/v4/validate';
        }

        $this->setBaseUri($url);

        $order      = $this->model_orders->getOrdersData(0, $order_id);
        $invoice    = $this->model_nfes->getNfesDataByOrderId($order_id, true);
        $clients    = $this->model_clients->getClientsData($order['customer_id']);
        $store      = $this->model_stores->getStoresData($order['store_id']);
        $invoice    = $invoice[0] ?? [];

        // Loja que utiliza integração com magalu, não deve validar a nfe.
        $api_integration = $this->model_api_integrations->getDataByStore($order['store_id'], true);
        if ($api_integration && $api_integration['integration'] == 'magalu') {
            return;
        }

        try {
            if (empty($order) || empty($invoice) || empty($clients) || empty($store)) {
                throw new Exception("Os dados de pedido, NFe, cliente e loja devem existir.");
            }

            $last_external_integration = $this->model_external_integration_history->getLastRowByTypeAndMethodAndRegisterId('nfe', 'validation', $order_id);

            if ($last_external_integration && !empty($last_external_integration['response_webhook'])) {
                $response_webhook = json_decode($last_external_integration['response_webhook']);
                if (!empty((array)$response_webhook)) {
                    if ($response_webhook->status->id == 3) {
                        // Pedido já foi validado e bem-sucedido.
                        return;
                    }
                }
            }

            if (strlen(onlyNumbers($clients['cpf_cnpj'])) != 11 && strlen(onlyNumbers($clients['cpf_cnpj'])) != 14) {
                $error_message = "Cliente com a informção de cpf ou cnpj, inválido. CPF_CNPJ=$clients[cpf_cnpj]";
                get_instance()->log_data('external_validation', __CLASS__ . '\\' . __FUNCTION__, "$error_message\norder_id=$order_id\ntype=nfe\naction=validation\nrequest=$clients[cpf_cnpj]", "E");
                throw new Exception($error_message);
            }

            $order_items_cancel = $this->model_order_items_cancel->getItemsCanceledProductsByOrder($order['id']);

            $value_cancel_total_items = 0;
            $discount_cancel_total_items = 0;
            if (!empty($order_items_cancel)) {
                $value_cancel_total_items = array_sum(array_map(function($item) {
                    return $item['rate'] * $item['qty_cancel'];
                }, $order_items_cancel));

                $discount_cancel_total_items = array_sum(array_map(function($item) {
                    return $item['discount'] * $item['qty_cancel'];
                }, $order_items_cancel));
            }

            $array_nfe = [
                "subOrder" => [
                    "uuid" => $order['id'],
                    "amount" => roundDecimal($order['gross_amount'] - $value_cancel_total_items),
                    "discount" => roundDecimal($order['discount'] - $discount_cancel_total_items),
                    "interest" => 0, // Juros
                    "invoice" => [
                        "uuid" => $order['id'],
                        "key" => onlyNumbers($invoice['chave']),
                        "emitter" => [
                            "cnpj" => onlyNumbers($store['CNPJ'])
                        ]
                    ],
                    "shipping" => [
                        "cost" => roundDecimal($order['total_ship']),
                        "logisticNetwork" => [
                            "type" => "epoca"
                        ]
                    ],
                    "isInterestWithheld" => false // Indica se o valor dos juros será embutido no valor do pedido.
                ],
                "application" => [
                    "id" => "epoca",
                    "name" => "Epoca"
                ]
            ];

            if (strlen(onlyNumbers($clients['cpf_cnpj'])) == 11) {
                $array_nfe['subOrder']['customer']['cpf'] = onlyNumbers($clients['cpf_cnpj']);
            } else {
                $array_nfe['subOrder']['customer']['cnpj'] = onlyNumbers($clients['cpf_cnpj']);
            }

            $options = array(
                'json' => $array_nfe
            );

            try {
                $request = $this->request('POST', '', $options);
                $response = json_decode($request->getBody()->getContents());
            } catch (Exception $exception) {
                get_instance()->log_data('external_validation', __CLASS__ . '\\' . __FUNCTION__, "{$exception->getMessage()}\ncode={$exception->getCode()}\norder_id=$order_id\ntype=nfe\naction=validation\nuri={$this->getBaseUri()}\nrequest=" . json_encode($array_nfe, JSON_UNESCAPED_UNICODE), "E");
                throw new Exception($exception->getMessage(), $exception->getCode());
            }
        } catch (Exception $exception) {
            $this->saveErrorBeforToSend($order_id, 'nfe', 'validation', $exception->getMessage());
            $this->model_orders->updatePaidStatus($order_id, $this->model_orders->PAID_STATUS['invoice_with_error']);
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $this->model_external_integration_history->create(array(
            'register_id'   => $order_id,
            'external_id'   => $response->protocol_id ?? $response->records[0]->protocol,
            'type'          => 'nfe',
            'method'        => 'validation',
            'uri'           => $this->getBaseUri(),
            'request'       => json_encode($array_nfe, JSON_UNESCAPED_UNICODE),
            'response'      => json_encode($response, JSON_UNESCAPED_UNICODE),
        ));

        if (in_array($order['paid_status'], array(
            $this->model_orders->PAID_STATUS['awaiting_billing'],
            $this->model_orders->PAID_STATUS['invoice_with_error'],
            $this->model_orders->PAID_STATUS['nfe_sent_to_marketplace'],
            $this->model_orders->PAID_STATUS['waiting_issue_label'],
            $this->model_orders->PAID_STATUS['manual_tracking_hire'],
            $this->model_orders->PAID_STATUS['sented_nfe_to_marketplace']
        ))) {
            $this->model_orders->updatePaidStatus($order_id, $this->model_orders->PAID_STATUS['checking_invoice']);
        }
    }

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    public function receiveNfe(object $data)
    {
        if (!property_exists($data, 'protocol_id')) {
            throw new Exception("Protocolo não encontrado.");
        }

        $protocol_id = $data->protocol_id;
        // 3 = sucesso | 4 = erro
        $status = $data->status->id == 3;

        $external_integration = $this->model_external_integration_history->getByExternalId($protocol_id);
        if (!$external_integration) {
            return;
        }

        $order = $this->model_orders->getOrdersData(0, $external_integration['register_id']);
        if (!$order) {
            throw new Exception("Pedido não encontrado.");
        }

        $this->model_external_integration_history->updateByExternalId($protocol_id, array(
            'response_webhook'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            'status_webhook'    => $status
        ));

        if ($external_integration['type'] != 'nfe' || $external_integration['method'] != 'validation') {
            throw new Exception("Validação chegou com tipo e método inválidos.");
        }

        if ($order['paid_status'] == $this->model_orders->PAID_STATUS['checking_invoice']) {
            if ($status) {
                $this->model_orders->updatePaidStatus($order['id'], $this->model_orders->PAID_STATUS['sented_nfe_to_marketplace']);
            } else {
                $this->model_orders->updatePaidStatus($order['id'], $this->model_orders->PAID_STATUS['invoice_with_error']);
            }
        }
    }

    /**
     * @param int $store_id
     * @param string $reference_date
     * @param string|null $number_nfse_to_export
     * @param string|null $type_to_export
     * @return array
     * @throws Exception
     *
     * Usar para baixar o arquivo. $this->marketplace_store->external_integration->getNfseStore(1,'2024-07', 33, 'zip');
     */
    public function getNfseStore(int $store_id, string $reference_date, string $number_nfse_to_export = null, string $type_to_export = null): array
    {
        $check_date = date('Y-m', strtotime($reference_date));
        $next_month = dateFormat(addMonthToDate(dateNow()->format(DATE_INTERNATIONAL), 1), 'Y-m');

        if (!$check_date || $check_date == "1970-01" || strtotime($check_date) >= strtotime($next_month)) {
            throw new Exception("Formato da data deve ser válida e até o mês atual.");
        }

        $store = $this->model_stores->getStoresData($store_id);
        $store_document = onlyNumbers($store['CNPJ']);
        $dir_save = "assets/files/temp/nfse_magalu/$store_id/$reference_date/".get_instance()->getGUID(false);

        $this->setBaseUri("https://janus-api.magazineluiza.com.br/janus/v2/invoice/mktplace/$store_document/$reference_date/MAGALU/simplified"); // @todo ver com magalu se será enviado MAGALU mesmo

        try {
            $request = $this->request('GET');
            $response = json_decode($request->getBody()->getContents());
            /*$response = json_decode('{
                "records": [
                {
                "typeDescription": "OUTROS SERVIÇOS",
                "invoiceDate": "2024-04-02",
                "invoiceNum": "33",
                "series": "A",
                "totalAmount": "46.02",
                "protocol": "660c063b973c2fe8c2f362a2",
                "xml": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.xml",
                "pdf": "https://casaevideoteste.conectala.com.br/app/assets/images/etiquetas/P_139_1_A4.pdf",
                "extract": "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/extract",
                "zipExtract": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.zip"
                },
                {
                "typeDescription": "CUSTO DE COPARTICIPAÇÃO",
                "invoiceDate": "2024-04-05",
                "invoiceNum": "2628314",
                "series": "AR",
                "totalAmount": "23307.51",
                "protocol": "",
                "xml": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.xml",
                "pdf": "https://casaevideoteste.conectala.com.br/app/assets/images/etiquetas/P_139_1_A4.pdf",
                "extract": "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/extract",
                "zipExtract": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.zip"
                },
                {
                "typeDescription": "CUSTO DE SERVIÇO DE ENTREGA",
                "invoiceDate": "2024-04-05",
                "invoiceNum": "",
                "series": "AR",
                "totalAmount": "527.64",
                "protocol": "",
                "xml": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.xml",
                "pdf": "https://casaevideoteste.conectala.com.br/app/assets/images/etiquetas/P_139_1_A4.pdf",
                "extract": "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/extract",
                "zipExtract": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.zip"
                },
                {
                "typeDescription": "CUSTO DE SERVIÇO DE ENTREGA",
                "invoiceDate": "2024-04-05",
                "invoiceNum": "3966128",
                "series": "AR",
                "totalAmount": "527.64",
                "protocol": "",
                "xml": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.xml",
                "pdf": "",
                "extract": "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/extract",
                "zipExtract": "https://casaevideoteste.conectala.com.br/app/assets/images/xml/teste.zip"
                }
                ]
                }'
            );*/
            if (!is_object($response) || empty($response->records)) {
                throw new Exception("Não foram retornados registros.");
            }
        } catch (Exception $exception) {
            get_instance()->log_data('external_search', __CLASS__.'\\'.__FUNCTION__, "{$exception->getMessage()}\ncode={$exception->getCode()}\nstore_id=$store_id\ntype=nfse\naction=get\nuri={$this->getBaseUri()}", "E");
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $result_array = array_filter(array_map(function($record) use ($store_id, $reference_date, $dir_save, $number_nfse_to_export, $type_to_export) {
            if (empty($record->invoiceNum)) {
                return null;
            }

            $array_files = array(
                'xml' => null,
                'pdf' => null,
                'zip' => null,
            );

            $result = array(
                "type"          => $record->typeDescription,// "OUTROS SERVIÇOS", // Nome da transação que originou a nota (tipo de faturamento)
                "invoice_date"  => $record->invoiceDate,// "2024-04-02", // Data de Emissão/Autorização da NFSe
                "invoice_num"   => $record->invoiceNum,// "33", // Número da nota fiscal de serviço
                "serie"         => $record->series,// "A", // Série da nota fiscal
                "total_amount"  => $record->totalAmount,// "46.02", // Valor total faturado
                //"protocol"      => $record->protocol,// "660c063b973c2fe8c2f362a2", // Protocolo de autorização da Nota Fiscal
                //"xml"           => $array_files['xml'],// "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/xml", // Url com o arquivo xml da nota fiscal de serviço (Retorna um arquivo XML Content-Type: application/xml;charset=UTF-8)
                //"pdf"           => $array_files['pdf'],// "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/pdf", // Url com o arquivo pdf da nota fiscal de serviço ou nota de débito (Retorna um arquivo PDF Content-Type: application/pdf)
                //"extract"       => $record->extract,// "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/extract", // Url com os dados do extrato do seller (detalhado)
                //"zip_extract"   => $array_files['zip'],// "https://api.apiluiza.com.br/janus/v1/invoice/doc/13502227/zipextract", // Url com os dados do extrato do seller compactado (.zip contendo um arquivo csv) (Retorna um arquivo ZIP Content-Type: application/zip)
            );

            foreach (
                array(
                    ['prop' => 'xml','type' => 'xml'],
                    ['prop' => 'pdf','type' => 'pdf'],
                    ['prop' => 'zipExtract','type' => 'zip']
                ) as $file_type
            ) {
                try {
                    $prop       = $file_type['prop'];
                    $uri_file   = $record->$prop;
                    $type       = $file_type['type'];
                    $invoice_num= $record->invoiceNum;

                    // Exportar somente o tipo específico.
                    if (!is_null($type_to_export) && $type != $type_to_export) {
                        continue;
                    }

                    // Exportar somente da nota específica.
                    if (!is_null($number_nfse_to_export) && $invoice_num != $number_nfse_to_export) {
                        continue;
                    }

                    if (empty($uri_file)) {
                        throw new Exception("Link para o arquivo em $prop inválido.");
                    }

                    $array_files[$type] = $this->getFileToExport($dir_save, $type, $uri_file, $record->invoiceNum, !is_null($number_nfse_to_export) && !is_null($type_to_export));

                    if (!is_null($number_nfse_to_export) && !is_null($type_to_export)) {
                        return [];
                    }

                } catch (Exception $exception) {
                    $result['error'][] = $exception->getMessage();
                    get_instance()->log_data('external_search', __CLASS__.'\\'.__FUNCTION__, "{$exception->getMessage()}\ncode={$exception->getCode()}\nstore_id=$store_id\nreference_date=$reference_date\ntype=nfse\naction=get\nuri={$this->getBaseUri($uri_file)}", "E");
                }
            }

            $result["xml"]  = $array_files['xml'];
            $result["pdf"]  = $array_files['pdf'];
            $result["zip"]  = $array_files['zip'];

            return $result;
        }, $response->records), function($record) {
            return !is_null($record);
        });

        rsort($result_array);

        /*$this->model_external_integration_history->create(array(
            'register_id'   => $store_id,
            'type'          => 'nfse',
            'method'        => 'get',
            'uri'           => $this->getBaseUri(),
            'request'       => '',
            'response'      => json_encode($response, JSON_UNESCAPED_UNICODE),
        ));*/

        return $result_array;
    }

    /**
     * @param string $dir_save
     * @param string $type
     * @param string $uri
     * @param string $invoice_num
     * @param bool $return_link
     * @return false|int|string|void
     * @throws Exception
     */
    public function getFileToExport(string $dir_save, string $type, string $uri, string $invoice_num, bool $return_link = true)
    {
        $options = array(
            'headers' => array(
                'Content-Type' => "application/$type;charset=UTF-8",
                'Accept' => "application/$type;charset=UTF-8",
            )
        );
        $request = $this->request('GET', $uri, $options);
        $response = $request->getBody()->getContents();

        if ($return_link) {
            $this->exportFile($response, $type, $invoice_num);
            return;
        }

        return copyFileByContentFile($dir_save."/nfse_$invoice_num.$type", $response, true);
    }

    /**
     * @param string $response
     * @param string $type
     * @param string $invoice_num
     * @return void
     */
    public function exportFile(string $response, string $type, string $invoice_num)
    {
        header("Content-Type: application/$type;charset=UTF-8");
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'."nfse_$invoice_num.$type".'"');
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');

        echo $response;
    }

    /**
     * @param array $payment
     * @throws Exception
     */
    public function checkDataPayment(array $payment)
    {
        if (!array_key_exists('data_vencto', $payment) || empty($payment['data_vencto'])) {
            throw new Exception("data_vencto not found");
        }
        if (!array_key_exists('valor', $payment) || empty($payment['valor'])) {
            throw new Exception("valor not found");
        }
        if (!array_key_exists('forma_id', $payment) || empty($payment['forma_id'])) {
            throw new Exception("forma_id not found");
        }
        if (!array_key_exists('forma_desc', $payment) || empty($payment['forma_desc'])) {
            throw new Exception("forma_desc not found");
        }
        if (!array_key_exists('payment_id', $payment) || empty($payment['payment_id'])) {
            throw new Exception("payment_id not found");
        }
        if (!array_key_exists('transaction_id', $payment) || empty($payment['transaction_id'])) {
            throw new Exception("transaction_id not found");
        }
        if (!array_key_exists('payment_transaction_id', $payment) || empty($payment['payment_transaction_id'])) {
            throw new Exception("payment_transaction_id not found");
        }
        if (!array_key_exists('autorization_id', $payment) || empty($payment['autorization_id'])) {
            throw new Exception("autorization_id not found");
        }
    }
}