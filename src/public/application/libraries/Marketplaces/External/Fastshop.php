<?php

namespace Marketplaces\External;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use stdClass;

require_once 'system/libraries/Vendor/autoload.php';
require_once 'BaseExternal.php';

class Fastshop extends BaseExternal
{
    /**
     * Instantiate a new Vtex instance.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $base_uri = 'https://apiqa.fastshop.com.br/gan/v0/seller/v0/fms-events';
        if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            $base_uri = 'https://api.fastshop.com.br/gan/v0/seller/v0/fms-events';
        }
        if (ENVIRONMENT === 'local') {
            $base_uri = 'https://webhook.site/e2e86a7c-75a4-40c6-8fac-12dad4f18fdd';
        }
        $this->setBaseUri($base_uri);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function setAuthToken()
    {
        $app_token_fastshop = $this->model_settings->getValueIfAtiveByName('app_token_fastshop');

        if (empty($app_token_fastshop)) {
            throw new Exception("Parâmetros app_token_fastshop não configurados");
        }

        // Define a credencial.
        $credentials = new StdClass;
        $credentials->app_token = $app_token_fastshop;

        $this->setMapAuthRequest(array(
            'app_token' => [
                'field' => 'X-App-Token',
                'type'  => 'headers'
            ]
        ));

        // Define os campos para ser utilizado nas requisições com o marketplace.
        $this->setExternalCredentials($credentials);
    }

    /**
     * Fastshop não possuí essa funcionalidade
     *
     * @param int $store_id
     * @return void
     * @throws Exception
     */
    public function notifyStore(int $store_id) {}

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    public function receiveStore(object $data) {}

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    public function receiveOrder(object $data) {}

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    public function receiveNfe(object $data) {}

    /**
     * @param int $order_id
     * @param string $action paid | cancel | refund
     * @return void
     * @throws Exception
     */
    public function notifyOrder(int $order_id, string $action)
    {
        $order          = $this->model_orders->getOrdersData(0, $order_id);
        $payments       = $this->model_payment->getByOrderId($order_id);
        $store          = $this->model_stores->getStoresData($order['store_id']);
        $company        = $this->model_company->getCompanyData($order['company_id']);
        $client         = $this->model_clients->getClientsData($order['customer_id']);
        $order_items    = $this->model_orders->getOrdersItemData($order_id);
        $cancel_reason  = in_array($action, array('cancel', 'refund')) ? $this->model_orders->getReasonsCancelOrder($order_id) : [];
        $cancel_reason  = $cancel_reason[0] ?? [];

        $order_to_process_commission = $this->model_orders_to_process_commission->getByOrder($order_id);
        // Se o pedido ainda estiver na tabela, não foi processado.
        if ($order_to_process_commission) {
            return;
        }

        if ($action === 'refund') {
            $order_itens = $this->model_orders->getOrdersItemData($order['id']);
            foreach ($order_itens as $item) {
                $refund_item = $this->model_product_return->getByOrderAndProductAndVariant($order['id'], $item['product_id'], $item['variant'] === '' ? null : $item['variant']);
                // Só continua com a notificação de todos os itens foram devolvidos.
                if (!$refund_item || $refund_item['quantity_requested'] != $item['qty']) {
                    throw new Exception("Nem todos os produtos foram devolvidos.");
                }
            }
        }

        if (empty($store['erp_customer_supplier_code'])) {
            $message_error = "O campo clifor deve estar preenchido.";
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        $shipping_value = $order['total_ship'];
        $shipping_per_item = array();
        foreach ($order_items as $key_order_item => $order_item) {
            if (($key_order_item + 1) == count($order_items)) {
                $shipping_per_item[$order_item['id']] = $shipping_value - array_sum($shipping_per_item);
                break;
            }

            $shipping_per_item[$order_item['id']] = roundDecimal($shipping_value / count($order_items));
        }

        if (empty($payments)) {
            $message_error = "Pagamento não localizado para o pedido $order_id.";
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        // Recupera o pagamento com os dados preenchidos.
        $filter_payments = array_filter($payments, function ($payment) {
            return
                !empty($payment['payment_method_id']) &&
                !empty($payment['forma_desc']) &&
                !empty($payment['autorization_id']) &&
                !empty($payment['forma_id']) &&
                !empty($payment['parcela']);
        });

        // Não pagamento com os dados completos.
        if (empty($filter_payments)) {
            $message_error = "Pagamento incompleto para o pedido $order_id.";
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\norder_id=$order_id\ntype=order\naction=$action", "E");
            $this->saveErrorBeforToSend($order['id'], 'order', $action, $message_error);
            throw new Exception($message_error);
        }

        // Recupera o pagamento completo.
        rsort($filter_payments);
        $billingsData = array_map(function($payment) use ($client, $action, $cancel_reason) {
            $AuthorizationNumber = $payment['autorization_id'];
            $TNSUNumber = $payment['nsu'];
            if ($payment['forma_desc'] == 'pix') {
                $AuthorizationNumber = $payment['gateway_tid'];
                $TNSUNumber = str_replace('-', '', $payment['gateway_tid']);
            }

            return [
                "SequencePaymentID"         => "1", // Número sequêncial, inicial em 1
                "BillingMethodID"           => $payment['payment_method_id'],
                "BillingMethodDescription"  => $payment['forma_desc'],
                "BillingMethodGroup"        => $payment['forma_id'],
                "BillingMethodPayValue"     => (string)(roundDecimal($payment['valor'])),
                "FlagCardID"                => $this->getCardFlag($payment['forma_desc']),
                "CardHolderName"            => $client['customer_name'],
                "CardExpirationMonth"       => "",
                "CardExpirationYear"        => "",
                "OtherDocument"             => (string)(onlyNumbers($client['rg'] ?: $client['ie'])),
                "InstallmentQuantity"       => (string)($payment['parcela'] ?? 1),
                "InstallmentValue"          => (string)(roundDecimal($payment['valor'] / ($payment['parcela'] ?? 1))),
                "BillingStatus"             => $action == 'paid' ? "F" : 'C', // Valor "F" = venda ou "C" para cancelamento
                "TNSUNumber"                => $TNSUNumber,
                "AuthorizationNumber"       => $AuthorizationNumber,
                "OperationCardCode"         => "",
                "PaymentMethod"             => $payment['forma_id'],
                "ReasonDescription"         => $cancel_reason['reason'] ?? '',
                "Index"                     => "1", // Posição da lista. Repetir o valor informado no campo "SequencePaymentID".
                "BankId"                    => (int)$this->getCardFlag($payment['valor']),
                "DocumentNumber"            => [
                    "CPF"   => strlen(onlyNumbers($client['cpf_cnpj'])) == 11 ? (string)(onlyNumbers($client['cpf_cnpj'])) : null,
                    "CNPJ"  => strlen(onlyNumbers($client['cpf_cnpj'])) == 11 ? null : (string)(onlyNumbers($client['cpf_cnpj']))
                ]
            ];
        }, $filter_payments);

        // Parte de comissão, cálculo feito dentro da tela do pedido.
        $valueComission = 0;
        $hasCampaigns = $this->model_orders->getDetalheTaxas($order_id);
        $campaigns = $this->model_orders->getDetalheDescontos($order_id);

        $fastCommissionDiscountValue = 0;
        if (count($campaigns) > 0) {
            for ($i = 0; $i < count($campaigns); $i++) {
                $fastCommissionDiscountValue += $campaigns[$i]['desconto_campanha_marketplace']; // Desconto campanha marketplace
            }
        }

        if (count($hasCampaigns) > 0) {
            for ($i = 0; $i < count($hasCampaigns); $i++) {
                $valueComission += $hasCampaigns[$i]['comissao_campanha'] + $hasCampaigns[$i]['comissao_produto']; // Valor Comissão Campanha + Valor Comissão Produto
            }
        }

        $street_type = strtok($company['address'], " ");

        $array_order =  [
            "submitOrderShortRequest" => [
                "SubmitOrders" => [
                    "Customer" => [
                        "CustomerInfo"      => [
                            "CustomerName"  => $client['customer_name'],
                            "OtherDoc"      => (string)(strlen(onlyNumbers($client['cpf_cnpj'])) == 11 ? onlyNumbers($client['rg']) : onlyNumbers($client['ie'])),
                            "DocumentType"  => strlen(onlyNumbers($client['cpf_cnpj'])) == 11 ? 'RG' : 'IE',
                            "Gender"        => "E",
                            "Email"         => $client['email'],
                            "SendEmail"     => "false",
                            "Birthday"      => $client['birth_date'],
                            "DocumentNumber" => [
                                "CPFNumber" => (string)(onlyNumbers($client['cpf_cnpj']))
                            ],
                            "HomePhone" => [
                                "PhoneNumber" => (string)(onlyNumbers($client['phone_1']))
                            ],
                            "MobilePhone" => [
                                "PhoneNumber" => (string)(onlyNumbers($client['phone_1']))
                            ],
                            "Address" => [
                                "AddressName"   => $order['customer_name'],
                                "StreetName"    => $order['customer_address'],
                                "Number"        => $order['customer_address_num'],
                                "Complement"    => $order['customer_address_compl'],
                                "StreetType"    => $street_type,
                                "District"      => $order['customer_address_neigh'],
                                "City"          => $order['customer_address_city'],
                                "State"         => $order['customer_address_uf'],
                                "Country"       => "Brasil",
                                "ZIPCode"       => $order['customer_address_zip']
                            ],
                            "SellerInfos" => [
                                "SellerId"      => $store['erp_customer_supplier_code'],
                                "SellerName"    => $company['name'],
                                "CNPJ"          => (string)(onlyNumbers($company['CNPJ']))
                            ]
                        ]
                    ],
                    "Order" => [
                        "SystemSource" => "CL", // Fixo CL - Conecta Lá
                        "OrderDateTime" => $order['date_time'],
                        "CancelDateTime" => in_array($action, array('cancel', 'refund')) ? (!empty($order['date_cancel']) ? dateFormat($order['date_cancel'], DATETIME_INTERNATIONAL) : dateNow()->format(DATETIME_INTERNATIONAL)) : null,
                        "PricingTableID" => "MP", // Fixo "MP" para o Canal
                        "ClientOrder" => [
                            "ID" => $order['numero_marketplace'],//'CL' . $order['id'],
                        ],
                        "Event" => [
                            "TypeID" => $action == 'paid' ? "sale" : "cancel", // "Valores permitidos: "sale" = venda | "cancel" = cancelamento | "transfer" = Pagamento ao seller"
                            "Description" => $action == 'paid' ? "venda" : "cancelamento"
                        ],
                        "OrderItems" => [
                            [
                                "ValueShipping"                 => (string)(roundDecimal($order['total_ship'])),
                                "ValueComission"                => (string)(roundDecimal($valueComission)), // Valor que o seller irá pagar de comissão.
                                "ValueSeller"                   => (string)(roundDecimal(($order['gross_amount'] - $valueComission) + $fastCommissionDiscountValue)), // Valor que o seller irá receber.
                                "ValueIr"                       => "0",
                                "ValueProduct"                  => (string)(roundDecimal($order['total_order'])),
                                "TotalOrder"                    => (string)(roundDecimal($order['gross_amount'])),
                                "fastCommissionDiscountValue"   => (string)(roundDecimal($fastCommissionDiscountValue))
                            ]
                        ]
                    ],
                    "BillingData" => [
                        "BillingsData" => $billingsData
                    ]
                ]
            ]
        ];

        $options = array(
            'json' => $array_order
        );

        try {
            $request = $this->request('POST', '', $options);
            $response = json_decode($request->getBody()->getContents());

            if (!is_object($response)) {
                throw new Exception(json_encode($response, JSON_UNESCAPED_UNICODE));
            }

            if (property_exists($response, 'error') && !empty($response->error)) {
                throw new Exception($response->error->message);
            }
            if (!property_exists($response, 'code') || $response->code >= 300) {
                throw new Exception(property_exists($response, 'message') ? $response->message : (
                    property_exists($response, 'insertOrderStatusResponse') ? (
                        property_exists($response->insertOrderStatusResponse, 'Status') ? (
                            property_exists($response->insertOrderStatusResponse->Status, 'Message') ? (
                                $response->insertOrderStatusResponse->Status->Message
                            ) : json_encode($response, JSON_UNESCAPED_UNICODE)
                        ) : json_encode($response, JSON_UNESCAPED_UNICODE)
                    ) : json_encode($response, JSON_UNESCAPED_UNICODE)
                ));
            }
        } catch (Exception $exception) {
            $message_error = $exception->getMessage();
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\ncode={$exception->getCode()}\norder_id=$order_id\ntype=order\naction=$action\nuri={$this->getBaseUri()}\nrequest=".json_encode($array_order, JSON_UNESCAPED_UNICODE), "E");
            $this->model_external_integration_history->create(array(
                'register_id'       => $order_id,
                'type'              => 'order',
                'method'            => $action,
                'uri'               => $this->getBaseUri(),
                'request'           => json_encode($array_order, JSON_UNESCAPED_UNICODE),
                'response_webhook'  => '{}',
                'status_webhook'    => 0,
                'response'          => $message_error,
            ));
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $this->model_external_integration_history->create(array(
            'register_id'       => $order_id,
            'type'              => 'order',
            'method'            => $action,
            'uri'               => $this->getBaseUri(),
            'request'           => json_encode($array_order, JSON_UNESCAPED_UNICODE),
            'response'          => json_encode($response, JSON_UNESCAPED_UNICODE),
            'response_webhook'  => null,
            'status_webhook'    => 1
        ));
    }

    /**
     * @param int $order_id
     * @return void
     * @throws Exception
     */
    public function notifyNfeValidation(int $order_id) {}

    /**
     * @param int $company_id
     * @param string $lote
     * @throws Exception
     */
    public function getNfseStore(int $company_id, string $lote)
    {
        $conciliation_fiscal = $this->db->distinct('pmcf.id, pmcf.data_ciclo_fiscal, pmcf.data_fim, pmcf.data_inicio, cf.ano_mes')
            ->select('pmcf.id, pmcf.data_ciclo_fiscal, pmcf.data_fim, pmcf.data_inicio, cf.ano_mes')
            ->join('param_mkt_ciclo_fiscal pmcf', 'cf.param_mkt_ciclo_id = pmcf.id')
            ->where('cf.lote', $lote)
            ->get('conciliacao_fiscal cf')
            ->row_array();

        if (!$conciliation_fiscal) {
            throw new Exception("Conciliação fiscal não encontrada. $lote");
        }

        // Novas datas para ATD-634171
        $conciliation_fiscal['data_inicio'] = 26;
        $conciliation_fiscal['data_fim'] = 25;

        $param_mkt_ciclo_id = $conciliation_fiscal['id'];
        $date = $this->getStartAndEndDateToConciliation(
            $conciliation_fiscal['data_ciclo_fiscal'],
            $conciliation_fiscal['data_fim'],
            $conciliation_fiscal['data_inicio'],
            $conciliation_fiscal['ano_mes']
        );

        // Nova lógica para ATD-634171
        if ($conciliation_fiscal['data_ciclo_fiscal'] < $conciliation_fiscal['data_inicio']) {
            $date['start_date'] = addMonthToDate($date['start_date'], 1);
            $date['end_date'] = addMonthToDate($date['end_date'], 1);
        }

        $start_date = $date['start_date'];
        $end_date   = $date['end_date'];
        $cycle_date = $date['cycle_date'];

        echo "start_date=$start_date | end_date=$end_date | cycle_date=$cycle_date\n";

        $company = $this->model_company->getCompanyData($company_id);
        $company_document = onlyNumbers($company['CNPJ']);

        $this->setBaseUri("https://apiqa.fastshop.com.br/gan/v0/seller/v0/servicenfs");
        if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
            $this->setBaseUri("https://api.fastshop.com.br/gan/v0/seller/v0/servicenfs");
        }

        try {
            $options = array(
                'json' => array(
                    "startDate"     => $start_date,
                    "endDate"       => $end_date,
                    "customerCNPJ"  => $company_document,
                    "status"        => "E",
                    "customerID"    => "",
                    "typeService"   => "M"
                )
            );
            $request = $this->request('POST', '', $options);
            $response = json_decode($request->getBody()->getContents());
            //$response = json_decode('{"invoices":[{"createdDateNFSe":"2024-01-24","numberNFSe":265823,"status":"E","total":1513.86,"totalIRRF":22.71,"typeService":"M"},{"createdDateNFSe":"2024-03-22","numberNFSe":279248,"status":"E","total":76.33,"totalIRRF":0.00,"typeService":"M"},{"createdDateNFSe":"2024-02-23","numberNFSe":272303,"status":"E","total":4224.06,"totalIRRF":63.36,"typeService":"M"},{"createdDateNFSe":"2024-04-23","numberNFSe":285051,"status":"E","total":1646.76,"totalIRRF":24.70,"typeService":"M"}]}');
            if (!is_object($response) || empty($response->invoices)) {
                throw new Exception("Não foram retornados notas fiscais.");
            }
        } catch (Exception $exception) {
            get_instance()->log_data('external_search', __CLASS__.'\\'.__FUNCTION__, "{$exception->getMessage()}\ncode={$exception->getCode()}\ncompany_id=$company_id\ntype=nfse\naction=get\nuri={$this->getBaseUri()}", "E");
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $new_nfse = false;
        $stores = $this->model_stores->getStoresByCompany($company_id);
        foreach ($stores as $store) {
            $new_nfse_store = false;
            foreach ($response->invoices as $invoice) {
                if (strtotime(dateFormat($invoice->createdDateNFSe, DATE_INTERNATIONAL)) < strtotime('2024-12-20')) {
                    continue;
                }

                // Se nfe não existe, cria o registro.
                $create = array(
                    'lote'                      => $lote,
                    'store_id'                  => $store['id'],
                    'data_ciclo'                => $cycle_date,
                    'data_criacao'              => dateNow()->format(DATETIME_INTERNATIONAL),
                    'invoice_emission_date'     => $invoice->createdDateNFSe,
                    'invoice_number'            => $invoice->numberNFSe,
                    'invoice_amount_total'      => $invoice->total,
                    'invoice_amount_irrf'       => $invoice->totalIRRF,
                    'param_mkt_ciclo_fiscal_id' => $param_mkt_ciclo_id
                );

                if (!$this->model_payment->getNfsByInvoiceNumberAndStoreIdAndLote($create['invoice_number'], $create['store_id'], $create['lote'])) {
                    $new_nfse = true;
                    $new_nfse_store = true;
                    $this->model_payment->insertNFSUrl($create);
                }
            }

            if ($new_nfse_store) {
                if (!$this->model_payment->checkIfExistNfsGroupByLoteAndStoreIdAndCycle($lote, $store['id'], $cycle_date)) {
                    $this->model_payment->insertNFSGroupData(array(
                        'lote'          => $lote,
                        'store_id'      => $store['id'],
                        'data_ciclo'    => $cycle_date
                    ));
                }
            }
        }

        if ($new_nfse) {
            $this->model_external_integration_history->create(array(
                'register_id'       => $company_id,
                'type'              => 'nfse',
                'method'            => 'get',
                'uri'               => $this->getBaseUri(),
                'request'           => json_encode($options, JSON_UNESCAPED_UNICODE),
                'response'          => json_encode($response, JSON_UNESCAPED_UNICODE),
                'response_webhook'  => null,
                'status_webhook'    => 1
            ));
        }
    }

    /**
     * @param int $company_id
     * @param string|null $register_date
     * @return void
     * @throws Exception
     */
    public function notifyConciliation(int $company_id, string $register_date = null)
    {
        $register_id        = "$company_id-".date('m/Y');
        $conciliation_date  = date('m-Y');

        if (!is_null($register_date)) {
            $conciliation_date  = date('m-Y', strtotime($register_date));
            $register_id        = "$company_id-".date('m/Y', strtotime($register_date));
        }

        $company        = $this->model_company->getCompanyData($company_id);
        $conciliations  = $this->model_conciliation->getByYearMonth($conciliation_date);

        if (empty($conciliations)) {
            $message_error = "Não foram encontrados repasse para a loja ou ainda não foi finalizada.";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }

        $stores = $this->model_stores->getStoresByCompany($company_id);

        if (empty($stores)) {
            $message_error = "Não foram encontrados lojas para empresa.";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }

        $active_stores = array_map(function ($store) {
            return $store['active'] != 0 ? $store : null;
        }, $stores);

        $active_stores = array_filter($active_stores, function($store){
            return $store;
        });
        sort($active_stores);

        if (empty($active_stores)) {
            $message_error = "Não foi encontrado loja ativa para empresa.";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }

        $data_store     = $this->model_stores->getStoresData($active_stores[0]['id']);
        $number_bank    = $this->model_banks->getBankNumber($data_store['bank']);

        if (empty($number_bank)) {
            $message_error = "O campo banco deve estar preenchido.";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }
        if (empty($data_store['erp_customer_supplier_code'])) {
            $message_error = "O campo clifor deve estar preenchido.";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }
        if (empty($data_store['agency'])) {
            $message_error = "O campo agência bancário deve estar preenchido.";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }
        if (empty($data_store['account'])) {
            $message_error = "O campo conta bancária deve estar preenchido.";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }

        $conciliation_id = null;
        $conciliation_date_insert = null;
        $sum_valor_seller_total = 0;
        $sum_valor_comissao_frete_total = 0;
        $fast_commission_discount_value = 0;
        $got_nfse_conciliation = [];
        $value_ir_total = 0;
        $value_canceled_total = 0;
        $value_refunded_total = 0;
        $value_commission_refund_total = 0;
        foreach ($stores as $store) {
            $store_id = $store['id'];
            foreach ($conciliations as $conciliation) {
                // status_repasse
                // 21 aguardando
                // 50 robo já robou
                // 51 negativo, abriu jurídico
                // 52 loja ficou zerado
                $repasse_seller = $this->model_repasse->sumValorSellerByStoreIdAndConciliacaoId($store_id, $conciliation['conciliacao_id']);

                if (is_null($conciliation_id)) {
                    $conciliation_id = $conciliation['conciliacao_id'];
                }

                if (is_null($conciliation_date_insert)) {
                    $conciliation_date_insert = $repasse_seller['date_insert'];
                }

                $orders_seller = $this->db->distinct('orders_id, campaign_v2_orders_total_channel, orders_service_charge_rate, orders_total_orders, orders_total_ship')
                    ->select('orders_id, campaign_v2_orders_total_channel, orders_service_charge_rate, orders_total_orders, orders_total_ship')
                    ->where('financial_release_payment_id', $conciliation['conciliacao_id'])
                    ->where('store_id', $store_id)
                    ->where('status_conciliacao', 'Repasse Ciclo')
                    ->get('financial_release_payment_data')
                    ->result_array();

                foreach ($orders_seller as $order_seller) {
                    $sum_valor_comissao_frete_total += $order_seller['orders_total_ship'];
                    $service_charge_rate = $order_seller['orders_service_charge_rate'];

                    $fast_commission_discount_value += $order_seller['orders_total_orders'] * ($service_charge_rate / 100);
                    // Desconto campanha marketplace
                    if (!empty($order_seller['campaign_v2_orders_total_channel'])) {
                        $fast_commission_discount_value += $order_seller['campaign_v2_orders_total_channel'] * ($service_charge_rate / 100);
                    }
                }

                $sum_valor_seller_total += $repasse_seller['sum_valor_seller'] ?: 0;
                if (!in_array($conciliation['lote'], $got_nfse_conciliation)) {
                    $got_nfse_conciliation[] = $conciliation['lote'];

                    $irrf_conciliation = $this->db->select('sum(nfsu.invoice_amount_irrf) as sum_invoice_amount_irrf')
                        ->where(
                            array(
                                'nfcff.lote' => $conciliation['lote'],
                                'nfcff.company_id' => $company_id,
                            )
                        )
                        ->join('nota_fiscal_servico_url nfsu', 'nfsu.id = nfcff.nota_fiscal_servico_url_id')
                        ->get('nota_fiscal_ciclo_financeiro_fiscal nfcff')
                        ->row_array();

                    $value_ir_total += $irrf_conciliation['sum_invoice_amount_irrf'] ?? 0;
                }

                // Cancelamento
                $orders_canceled_seller = $this->db->distinct('orders_id, valor_repasse_ajustado')
                    ->select('orders_id, valor_repasse_ajustado')
                    ->where('financial_release_payment_id', $conciliation['conciliacao_id'])
                    ->where('store_id', $store_id)
                    ->where('status_conciliacao', 'Repasse Cancelamento')
                    ->get('financial_release_payment_data')
                    ->result_array();
                $value_canceled_total += array_sum(array_column($orders_canceled_seller, 'valor_repasse_ajustado'));

                // Devolução
                $orders_refunded_seller = $this->db->distinct('orders_id, valor_repasse_ajustado')
                    ->select('orders_id, valor_repasse_ajustado')
                    ->where('financial_release_payment_id', $conciliation['conciliacao_id'])
                    ->where('store_id', $store_id)
                    ->where('financial_adjustment_adjustment_number', 'Devolução de produto.')
                    ->get('financial_release_payment_data')
                    ->result_array();
                $value_refunded_total += array_sum(array_column($orders_refunded_seller, 'valor_repasse_ajustado'));

                // Estorno de comissão
                $orders_commission_refun_seller = $this->db->distinct('orders_id, valor_repasse_ajustado')
                    ->select('orders_id, valor_repasse_ajustado')
                    ->where('financial_release_payment_id', $conciliation['conciliacao_id'])
                    ->where('store_id', $store_id)
                    ->where('financial_adjustment_adjustment_number', 'Estorno de comissão Cobrada')
                    ->get('financial_release_payment_data')
                    ->result_array();
                $value_commission_refund_total += array_sum(array_column($orders_commission_refun_seller, 'valor_repasse_ajustado'));
            }
        }

        if (empty($sum_valor_seller_total)) {
            $message_error = "não existem valores enviados a empresa $company_id";
            $this->saveErrorBeforToSend($register_id, 'conciliation', 'payment', $message_error);
            throw new Exception($message_error);
        }

        $value_discount_of_canceled = $value_canceled_total + $value_refunded_total + $value_commission_refund_total;
        $value_discount_of_canceled *= (-1);

        $order_items = array(
            [
                "valueReversePunishment"    => "0", // Fixo zero.
                "valueTotalSellerOrigin"    => (string)(roundDecimal($sum_valor_seller_total + $value_ir_total)),
                "valuePunishment"           => "0", // Fixo zero.
                "FastOrderId"               => "REPASSE",
                "OrderId"                   => $conciliation_id,
                "ValueComission"            => (string)(roundDecimal($fast_commission_discount_value)),
                "valueTotalSeller"          => (string)(roundDecimal($sum_valor_seller_total + $value_ir_total)),
                "ValueDiscountOfCanceled"   => (string)(roundDecimal($value_discount_of_canceled)),
                "ValueTranfer"              => (string)(roundDecimal($sum_valor_seller_total + $value_ir_total)),
                "ValueIr"                   => (string)(roundDecimal($value_ir_total)),
                "Parcel"                    => [], // Sempre passar vazio.
                "ValueShipping"             => (string)(roundDecimal($sum_valor_comissao_frete_total)),
                "DateOrder"                 => dateNow()->format(DATE_INTERNATIONAL),
                "CloseDate"                 => dateFormat($conciliation_date_insert, DATETIME_INTERNATIONAL)
            ]
        );

        $array_conciliation =  [
            "submitOrderShortRequest" => [
                "SubmitOrders" => [
                    "Order" => [
                        "SystemSource"      => "CL", // Pode colocar fixo "CL" = Conecta-lá
                        "OrderDateTime"     => dateNow()->format(DATE_INTERNATIONAL),
                        "PricingTableID"    => "MP", // Fixo "MP" para o Canal
                        "ClientOrder" => [
                            "ID"            => "REPASSE", // Fixo = "REPASSE"
                            "WalletID"      => $company['id'],
                            "Name"          => $company['name'],
                            "SellerName"    => $company['name'],
                            "SellerId"      => $data_store['erp_customer_supplier_code'],
                            "CNPJ"          => (string)(onlyNumbers($company['CNPJ'])),
                            "Bank" => [
                                "cnpj"      => "",
                                "bank"      => $number_bank,
                                "ag"        => $data_store['agency'],
                                "account"   => $data_store['account']
                            ]
                        ],
                        "Event" => [
                            "TypeID"        => "transfer", // "Valores permitidos: "sale" = venda | "cancel" = cancelamento | "transfer" = Pagamento ao seller"
                            "Description"   => "Pagamento ao seller"
                        ],
                        "OrderItems" => $order_items
                    ]
                ]
            ]
        ];

        $options = array(
            'json' => $array_conciliation
        );

        try {
            $request = $this->request('POST', '', $options);
            $response = json_decode($request->getBody()->getContents());

            if (!is_object($response)) {
                throw new Exception(json_encode($response, JSON_UNESCAPED_UNICODE));
            }

            if (property_exists($response, 'error') && !empty($response->error)) {
                throw new Exception($response->error->message);
            }
            if (!property_exists($response, 'code') || $response->code >= 300) {
                throw new Exception(property_exists($response, 'message') ? $response->message : (
                    property_exists($response, 'insertOrderStatusResponse') ? (
                        property_exists($response->insertOrderStatusResponse, 'Status') ? (
                            property_exists($response->insertOrderStatusResponse->Status, 'Message') ? (
                                $response->insertOrderStatusResponse->Status->Message
                            ) : json_encode($response, JSON_UNESCAPED_UNICODE)
                        ) : json_encode($response, JSON_UNESCAPED_UNICODE)
                    ) : json_encode($response, JSON_UNESCAPED_UNICODE)
                ));
            }

            foreach ($got_nfse_conciliation as $lote) {
                $this->db->update(
                    'nota_fiscal_ciclo_financeiro_fiscal',
                    array('processed_at' => dateNow()->format(DATETIME_INTERNATIONAL)),
                    array(
                        'lote' => $lote,
                        'company_id' => $company_id
                    )
                );
            }
        } catch (Exception $exception) {
            $message_error = $exception->getMessage();
            get_instance()->log_data('external_notification', __CLASS__.'\\'.__FUNCTION__, "$message_error\ncode={$exception->getCode()}\nstore_id=$company_id\ntype=conciliation\naction=paymentnuri={$this->getBaseUri()}\nrequest=".json_encode($array_conciliation, JSON_UNESCAPED_UNICODE), "E");
            $this->model_external_integration_history->create(array(
                'register_id'       => $register_id,
                'type'              => 'conciliation',
                'method'            => 'payment',
                'uri'               => $this->getBaseUri(),
                'request'           => json_encode($array_conciliation, JSON_UNESCAPED_UNICODE),
                'response_webhook'  => '{}',
                'status_webhook'    => 0,
                'response'          => $message_error,
            ));
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $this->model_external_integration_history->create(array(
            'register_id'       => $register_id,
            'type'              => 'conciliation',
            'method'            => 'payment',
            'uri'               => $this->getBaseUri(),
            'request'           => json_encode($array_conciliation, JSON_UNESCAPED_UNICODE),
            'response'          => json_encode($response, JSON_UNESCAPED_UNICODE),
            'response_webhook'  => null,
            'status_webhook'    => 1
        ));
    }

    protected function getCardFlag(string $flag): string
    {
        $flag = strtolower($flag);
        switch ($flag) {
            case 'visa':
                return '01';
            case 'mastercard':
                return '02';
            case 'american express':
                return '03';
            case 'sorocred':
                return '04';
            case 'siners club':
                return '05';
            case 'elo':
                return '06';
            case 'hipercard':
                return '07';
            case 'aura':
                return '08';
            case 'cabal':
                return '09';
            case 'alelo':
                return '10';
            case 'banes card':
                return '11';
            case 'calcard':
                return '12';
            case 'credz':
                return '13';
            case 'discover':
                return '14';
            case 'goodCard':
                return '15';
            case 'greencard':
                return '16';
            case 'hiper':
                return '17';
            case 'jcb':
                return '18';
            case 'mais':
                return '19';
            case 'maxVan':
                return '20';
            case 'policard':
                return '21';
            case 'redecompras':
                return '22';
            case 'sodexo':
                return '23';
            case 'valeCard':
                return '24';
            case 'verocheque':
                return '25';
            case 'vr':
                return '26';
            case 'ticket':
                return '27';
            default:
                return '99';
        }
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
        if (!array_key_exists('payment_method_id', $payment) || empty($payment['payment_method_id'])) {
            throw new Exception("payment_method_id not found");
        }
        if (!array_key_exists('autorization_id', $payment) || empty($payment['autorization_id'])) {
            throw new Exception("autorization_id not found");
        }
        if (!array_key_exists('parcela', $payment) || empty($payment['parcela'])) {
            throw new Exception("parcela not found");
        }
    }

    private function getStartAndEndDateToConciliation($dia_ciclo_fiscal, $dia_fim, $dia_inicio, $ano_mes): array
    {
        $formatted_cycle_date   = parseDateFromFormatToDateFormat('01-'.$ano_mes, 'd-m-Y', DATE_INTERNATIONAL);

        $cycle_date = date('Y-m', strtotime($formatted_cycle_date)) . "-$dia_ciclo_fiscal";

        // Data inicial é do mês do pagamento.
        if ($dia_ciclo_fiscal > $dia_fim) {
            $end_date = date('Y-m', strtotime($formatted_cycle_date)) . "-$dia_fim";
        }
        // Data inicial é do mês anterior do pagamento.
        else {
            $end_date = date('Y-m', strtotime('-1 month', strtotime($formatted_cycle_date))) . "-$dia_fim";
        }

        // Data final é do mês da data inicial.
        if ($dia_fim > $dia_inicio) {
            $start_date = date('Y-m', strtotime($end_date)) . "-$dia_inicio";
        }
        // Data final é do mês anterior da data inicial.
        else {
            // o dia do pagamento é o mês subsequente ao dia final do ciclo.
            if ($dia_ciclo_fiscal < $dia_inicio) {
                $start_date = date('Y-m', strtotime('-2 month', strtotime($formatted_cycle_date))) . "-$dia_inicio";
            } else {
                $start_date = date('Y-m', strtotime('-1 month', strtotime($formatted_cycle_date))) . "-$dia_inicio";
            }
        }

        return [
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'cycle_date' => $cycle_date
        ];
    }
}