<?php

require APPPATH . "controllers/Api/FreteConectala.php";

/**
 * http://localhost:88/app/Api/SellerCenter/Vtex/{sellercenter}/pvt/orderForms/simulation
 *
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property Model_settings $model_settings
 * @property CalculoFrete $calculofrete
 * @property Slack $slack
 */

class Simulation extends FreteConectala {

    private $mkt;
    private $items_skus = array();
    private $logistic_skus = array();
    private $pickup_points = array();
    private $zipcode = null;
    private $enable_log_slack = false;
    private $enable_log_quotes = false;
    private $enable_log_count_simulation_daily = false;
    private $return_seller_id_on_merchant_name = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->library('calculoFrete');

        if ($this->model_settings->getValueIfAtiveByName('enable_log_slack')) {
            $this->enable_log_slack = true;
        }

        if ($this->model_settings->getValueIfAtiveByName('enable_log_quotes')) {
            $this->enable_log_quotes = true;
        }

        if ($this->model_settings->getValueIfAtiveByName('enable_log_count_simulation_daily')) {
            $this->enable_log_count_simulation_daily = true;
        }

        if ($this->model_settings->getValueIfAtiveByName('return_merchant_name_on_simulation_vtex')) {
            $this->return_seller_id_on_merchant_name = true;
        }

        if ($this->enable_log_slack) {
            $this->load->library('Logging/Slack', array(), 'slack');
            $this->slack->endpoint = 'https://hooks.slack.com/services/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';
            $this->slack->channel = ['log_simulation_vtex'];
        }

        $this->mkt = array('platform' => 'VTEX', 'channel' => NULL);
    }

    /**
     * Get All Data from this method.
     *
     * @return void|null
     */
    public function index_get($mkt)
    {
        return $this->response(array(
            'items' => [],
            'logisticsInfo' => []
        ), REST_Controller::HTTP_OK);
    }

    /**
     * Post All Data from this method.
     *
     * @return void|null
     */
    public function index_post($mkt)
    {
        $_time_start_decode_json = 0;
        $_time_end_decode_json = 0;
        $_time_start_get_sellercenter_setting = 0;
        $_time_end_get_sellercenter_setting = 0;
        $_time_start_check_availability_stock = 0;
        $_time_end_check_availability_stock = 0;
        $_time_start_save_quotes_correios = 0;
        $_time_end_save_quotes_correios = 0;

        try {
            set_error_handler('customErrorHandler', E_ALL ^ E_NOTICE);

            $this->calculofrete->setSellerCenter();
            // hash para identificar um grupo de cotação.
            $quote_id = Admin_Controller::getGUID(false);

            $this->mkt['channel'] = $this->tiraAcentos($mkt);
            $timeStart = microtime(true) * 1000;


            $_time_start_decode_json = microtime(true) * 1000;
            //função CleanGEt comentada para testes do tempo de simulação
            //$data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));
            $data = json_decode(file_get_contents('php://input'), true);
            // $this->log_data('api', 'ConsultaFreteVTEX - POST', 'Chegou consulta frete='.json_encode($data), "I");
            if (is_null($data)) {
                return $this->response(array(
                    'items' => [],
                    'logisticsInfo' => []
                ), REST_Controller::HTTP_OK);
            }
            $_time_end_decode_json = microtime(true) * 1000;

            $zipcode  = isset($data['postalCode']) ? preg_replace('/[^0-9]/', '', $data['postalCode']) : null;
            //$an       = isset($_GET['an']) ? filter_var($_GET['an'], FILTER_SANITIZE_STRING) : '';
            //$sc       = isset($_GET['sc']) ? filter_var($_GET['sc'], FILTER_SANITIZE_STRING) : '';

            $this->zipcode = $zipcode;

            $_time_start_get_sellercenter_setting = microtime(true) * 1000;
            $sellerCenter = $this->calculofrete->getSellerCenter();
            $_time_end_get_sellercenter_setting = microtime(true) * 1000;

            $store_ids          = array();
            $itemsResponse      = array();
            $logisticResponse   = array();
            $skusKey            = array();
            $providerQuote      = array();
            $items_holder       = array();
            $totalDeadline      = null;
            $taxTotal           = 0;
            $totalPriceQuote    = 0;
            $_time_start_check_availability_stock = microtime(true) * 1000;
            foreach($data['items'] as $item) {

               $stockNow =  $this->getCurrentStockQuantity($item);

               $skusKey[] = $item['id'];

                if (!in_array($item['seller'], $store_ids)) {
                    $store_ids[] = $item['seller'];
                }

                $items = array(
                    array(
                        'sku' => $item['id'],
                        'qty' =>  $stockNow,
                        'seller' => $item['seller']
                    )
                );

                if (empty($items_holder)) {
                    $items_holder[$item['seller']] = $items;
                } else {
                    $seller_found = false;
                    foreach ($items_holder as $seller_key => $seller_value_) {
                        if ($seller_key == $item['seller']) {
                            $seller_found = true;

                            $aux_items = $seller_value_;
                            $aux_items[] = array(
                                'sku' => $item['id'],
                                'qty' => $stockNow,
                                'seller' => $item['seller']
                            );
                            $items_holder[$item['seller']] = $aux_items;

                            break;
                        }
                    }

                    if ($seller_found === false) {
                        $items_holder[$item['seller']] = $items;
                    }
                }
            }
            $_time_end_check_availability_stock = microtime(true) * 1000;
            $counter = -1;

            $data_to_product_id = array();

            foreach ($items_holder as $seller_value) {
                $all_skus = array();
                foreach ($seller_value as $s) {
                    $all_skus[] = $s['sku'];
                }

                $this->calculofrete->_time_start = microtime(true) * 1000;
                $quote = $this->calculofrete->formatQuote($this->mkt, $seller_value, $zipcode, true, false);
                $this->calculofrete->_time_end = microtime(true) * 1000;

                $response_details_time = array(
                    'total'                     => $this->calculofrete->_time_end - $this->calculofrete->_time_start,
                    'query_sku'                 => $this->calculofrete->_time_end_query_sku - $this->calculofrete->_time_start_query_sku,
                    'integration'               => $this->calculofrete->_time_end_integration - $this->calculofrete->_time_start_integration,
                    'integration_instance'      => $this->calculofrete->_time_end_integration_instance - $this->calculofrete->_time_start_integration_instance,
                    'internal_table'            => $this->calculofrete->_time_end_internal_table - $this->calculofrete->_time_start_internal_table,
                    'contingency'               => $this->calculofrete->_time_end_contingency - $this->calculofrete->_time_start_contingency,
                    'promotion'                 => $this->calculofrete->_time_end_promotion - $this->calculofrete->_time_start_promotion,
                    'auction'                   => $this->calculofrete->_time_end_auction - $this->calculofrete->_time_start_auction,
                    'price_rules'               => $this->calculofrete->_time_end_price_rules - $this->calculofrete->_time_start_price_rules,
                    'redis'                     => $this->calculofrete->_time_end_redis - $this->calculofrete->_time_start_redis,
                    'decode_json'               => $_time_end_decode_json - $_time_start_decode_json,
                    'get_sellercenter_setting'  => $_time_end_get_sellercenter_setting - $_time_start_get_sellercenter_setting,
                    'check_availability_stock'  => $_time_end_check_availability_stock - $_time_start_check_availability_stock
                );

                if ($this->enable_log_quotes && isset($quote['data']['skus'])) {
                    foreach ($quote['data']['skus'] as $skumkt => $sku) {
                        $data_to_product_id[$sku['prd_id']] = array(
                            'seller_id'             => $sku['seller_id'],
                            'store_id'              => $sku['store_id'],
                            'skumkt'                => $skumkt,
                            'success'               => $quote['success'],
                            'response_total_time'   => $this->calculofrete->_time_end - $this->calculofrete->_time_start,
                            'response_details_time' => $response_details_time,
                            'shipping_company'      => isset($quote['data']['services'][0]['shipping_id']),
                            'error_message'         => $quote['data']['message'] ?? null,
                        );
                    }
                }

                $counter += 1;
                $this->skusLookup(
                    $quote,
                    $data,
                    $seller_value,
                    $itemsResponse,
                    $sellerCenter,
                    $totalDeadline,
                    $providerQuote,
                    $logisticResponse,
                    -1,
                    $totalPriceQuote,
                    $taxTotal,
                    $all_skus
                );
            }

            $response = array(
                'items' => $this->items_skus, // $itemsResponse,
                'logisticsInfo' => $this->logistic_skus, // $logisticResponse,
                "country" => "BRA"
            );

            if (!empty($this->pickup_points)) {
                $response['pickupPoints'] = $this->pickup_points;
            }

            // se mandou o CEP, retorno na resposta e grava registro de cotação
            if ($zipcode) {
                $response['postalCode'] = $zipcode;

                $timeFinish = microtime(true) * 1000;
                $_time_start_save_quotes_correios = microtime(true) * 1000;
                $this->db->insert('quotes_correios', array(
                    'marketplace'       => $this->mkt['channel'],
                    'zip'               => $zipcode,
                    'sku'               => json_encode($skusKey),
                    'price'             => 0,
                    'time'              => '',
                    'service'           => '',
                    'frete_taxa'        => 0,
                    'response'          => json_encode($response, true),
                    'response_time'     => $timeFinish - $timeStart,
                    'error'             => isset($quote) ? ($quote["success"] ? 0 : 1) : 1,
                    'integration_name'  => '',
                    'status'            => '',
                    'store_id'          => $current_store_id[0]["store_id"] ?? 0
                ));
                $_time_end_save_quotes_correios = microtime(true) * 1000;

                if ($this->enable_log_quotes && isset($quote['data']['skus'])) {
                    $timeFinish = microtime(true) * 1000;
                    $stores = array_column($data_to_product_id, 'store_id');
                    $isMultiseller = count(array_unique($stores)) > 1 ? 1 : 0;
                    foreach ($data_to_product_id as $product_id => $products) {
                        $products['response_details_time']['save_quotes_correios'] = $_time_end_save_quotes_correios - $_time_start_save_quotes_correios;

                        $this->db->insert('log_quotes', array(
                            'quote_id'                  => $quote_id,
                            'marketplace'               => $this->mkt['channel'],
                            'zipcode'                   => $zipcode,
                            'product_id'                => $product_id,
                            'skumkt'                    => $products['skumkt'],
                            'store_id'                  => $products['store_id'],
                            'seller_id'                 => $products['seller_id'],
                            'is_multiseller'            => $isMultiseller,
                            'integration'               => isset($quote['data']['logistic']['type']) ? ($quote['data']['logistic']['type'] === false ? 'internal_table' : $quote['data']['logistic']['type']) : null,
                            'success'                   => $products['success'],
                            'contingency'               => $products['shipping_company'],
                            'response_total_time'       => $products['response_total_time'],
                            'response_details_time'     => json_encode($products['response_details_time']),
                            'error_message'             => $products['error_message'],
                            'response_total_time_quote' => $timeFinish - $timeStart,
                            'response_slas'             => json_encode($response, JSON_UNESCAPED_UNICODE),
                        ));
                    }
                }
            } else {
                if (isset($quote) && $this->enable_log_count_simulation_daily) {
                    foreach ($data['items'] as $item) {
                        $skumkt             = $item['id'];
                        $store_id           = $quote['data']['skus'][$skumkt]['store_id'] ?? null;
                        $integration        = isset($quote['data']['logistic']['type']) ? ($quote['data']['logistic']['type'] === false ? 'internal_table' : $quote['data']['logistic']['type']) : 'no_fright_module';
                        $store_integration  = $quote['data']['store_integration'];
                        $seller             = $quote['data']['logistic']['seller'];
                        $marketplace        = $this->mkt['channel'];
                        $sellercenter       = $this->calculofrete->getSellerCenter();
                        $date               = dateNow()->format('Y_m_d');

                        $key_redis = "$sellercenter:count_request_simulation:$marketplace:$date:$store_id:$skumkt";
                        $data_to_save = array(
                            'sellercenter'          => $sellercenter,
                            'marketplace'           => $marketplace,
                            'skumkt'                => $skumkt,
                            'store_id'              => $store_id,
                            'logistic_integration'  => $integration,
                            'store_integration'     => $store_integration,
                            'seller'                => $seller,
                            'date'                  => dateNow()->format(DATE_INTERNATIONAL),
                            'count'                 => 1
                        );

                        if ($this->calculofrete->instance->redis->is_connected) {
                            $data_redis = $this->calculofrete->instance->redis->get($key_redis);
                            if ($data_redis !== null) {
                                $data_redis = json_decode($data_redis, true);
                                $data_to_save['count'] = $data_redis['count'] + 1;
                            }

                            $this->calculofrete->instance->redis->set($key_redis, json_encode($data_to_save));
                        }
                    }
                }
            }

            $this->log_data('api', 'ConsultaFreteVTEX - POST - return', 'url=' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ' - received=' . json_encode($data) . ' - return=' . json_encode($response). "\npid=" . getmypid());
        } catch (Throwable $exception) {
            if ($this->enable_log_slack) {
                $message = "Error={$exception->getMessage()}\nTrace={$exception->getTraceAsString()}\nBody=".file_get_contents('php://input')."\nUrl={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                $this->slack->send($message);
            }

            return $this->response(array(
                "items" => [],
                "postalCode" => null,
                "geoCoordinates" => [],
                "country" => "BRA",
                "logisticsInfo" => [],
                "pickupPoints" => [],
                "messages" => [
                    [
                        "code" => "ERROR",
                        "text" => $exception->getMessage(),
                        "status" => "error",
                        "fields" => []
                    ]
                ],
                "totals" => [],
                "itemMetadata" => null
            ), REST_Controller::HTTP_OK);
        }

        return $this->response($response, REST_Controller::HTTP_OK);
    }

    private function skusLookup(
        $quote,
        $data,
        $seller_value,
        $itemsResponse,
        $sellerCenter,
        $totalDeadline,
        $providerQuote,
        $logisticResponse,
        $counter,
        $totalPriceQuote,
        $taxTotal,
        $current_sku
    ): void
    {
        if (!$quote['success']) {
            $this->saveLogError($quote['data']['message'] . "\n" . json_encode($data));
            
            if (!isset($quote['data']['skus'])) {
                return;
            }
        }

        $index = -1;
        foreach ($seller_value as $current_product) {
            foreach ($current_sku as $current) {
                $skumkt = $current_product['sku'];

                if ($current != $skumkt) {
                    continue;
                }

                if ($counter == -1) {
                    $index += 1;
                } else {
                    $index = $counter;
                }

                $itemsResponse[] = array(
                    // obrigatório, string - identificador do SKU
                    'id'                => $skumkt, // $item['id'],
                    // obrigatório, int - representa a posição desse item no array original (request)
                    "requestIndex"      => $index, // $key,
                    // obrigatório, int - preço por, os dois dígitos menos significativos são os centavos
                    "price"             => (int) str_replace('.', '', number_format($quote['data']['skus'][$skumkt]['sale_price'], 2, '.', '')),
                    // obrigatório, int - preço de, os dois dígitos menos significativos são os centavos
                    "listPrice"         => (int) str_replace('.', '', number_format($quote['data']['skus'][$skumkt]['list_price'], 2, '.', '')),
                    // obrigatório, int - retornar a quantidade solicitada ou a quantidade que consegue atender
                    "quantity"          => (int) $current_product['qty'],
                    // obrigatório, string - retonar o que foi passado no request
                    "seller"            => $current_product['seller'],
                    // se o pagamento for no seller, retornar o que recebeu no parametro an.
                    // Dilnei OEP-1599
                    "merchantName"      => $this->return_seller_id_on_merchant_name ? $current_product['seller'] : null,
                    // pode ser nulo, string - data de validade do preço.
                    "priceValidUntil"   => null
                );

                $slas_delivery = array();
                if (isset($quote['data']['services'])) {
                    foreach ($quote['data']['services'] as $sla) {
                        if (isset($sla['skumkt']) && ($sla['skumkt'] != $skumkt)) {
                            continue;
                        }

                        if (
                            $sellerCenter == 'somaplace'
                        ) {
                            $sla['method'] = 'Normal';
                            $sla['provider'] = 'Normal';
                        }

                        // Para Frete grátis, não adicionamos taxa
                        if ($sla['value'] != 0 && $quote['data']['logistic']['sellercenter']) {
                            // $tax = $this->calculofrete->calculaTaxa($quote['data']['totalPrice']);
                            $tax = 0;
                            $sla['value'] += $tax;
                            $taxTotal += $tax;
                        }

                        // Monta as SLAs do resultado.
                        $slas_delivery[] = array(
                            "id"                        => $sla['method'],
                            "name"                      => $sla['method'], //mb_convert_case(strtoupper($sla['provider']), MB_CASE_TITLE, 'UTF-8'),
                            "shippingEstimate"          => "{$sla['deadline']}bd",
                            "price"                     => (int) str_replace('.', '', number_format($sla['value'], 2, '.', '')),
                            "availableDeliveryWindows"  => array(),
                            'deliveryChannel'           => 'delivery',
                            'pickupStoreInfo'           => null
                        );

                        $totalPriceQuote += $sla['value'];

                        if ($totalDeadline === null || $totalDeadline < $sla['deadline']) {
                            $totalDeadline = $sla['deadline'];
                        }

                        if (!in_array($sla['provider'], $providerQuote)) {
                            $providerQuote[] = $sla['provider'];
                        }
                    }
                }

                $slas_pickup_point = array();
                if (!empty($quote['data']['pickup_points'])) {
                    // Ponto de retirada para referenciar à sla.
                    $this->pickup_points = array_map(function($item) {
                        $businessHours = array_map(
                            function ($withdrawal_time) {
                                return [
                                    "DayOfWeek"     => (int)$withdrawal_time['day_of_week'],
                                    "OpeningTime"   => $withdrawal_time['start_hour'],
                                    "ClosingTime"   => $withdrawal_time['end_hour']
                                ];
                            }, array_filter($item['withdrawal_times'],
                                function($withdrawal_time) {
                                    return !$withdrawal_time['closed_store'];
                                }
                            )
                        );
                        sort($businessHours);
                        return [
                            "id"                => "PickUpPoint_$item[id]",
                            "friendlyName"      => $item['name'],
                            "additionalInfo"    => '',
                            "address"           => [
                                "addressType"   => "pickup",
                                "receiverName"  => null,
                                "addressId"     => "PickUpPoint_$item[id]",
                                "postalCode"    => $item['cep'],
                                "city"          => $item['city'],
                                "state"         => $item['state'],
                                "country"       => $item['country'],
                                "street"        => $item['street'],
                                "number"        => $item['number'],
                                "neighborhood"  => $item['district'],
                                "complement"    => $item['complement'],
                                "reference"     => null,
                                "geoCoordinates"=> []
                            ],
                            "businessHours"     => $businessHours
                        ];
                    }, $quote['data']['pickup_points']);

                    // Slas do ponto de retirada.
                    $slas_pickup_point = array_map(function($item) {
                        return [
                            "id"                        => "PickUpPoint_$item[id]",
                            "deliveryChannel"           => "pickup-in-point",
                            "name"                      => $item['name'],
                            "shippingEstimate"          => "0bd",
                            "price"                     => 0,
                            "availableDeliveryWindows"  => [],
                            "pickupPointId"             => "PickUpPoint_$item[id]",
                            "pickupStoreInfo"           => [
                                "isPickupStore"     => true,
                                "friendlyName"      => $item['name'],
                                "address"           => [
                                    "addressType"   => "pickup",
                                    "receiverName"  => null,
                                    "addressId"     => "PickUpPoint_$item[id]",
                                    "postalCode"    => $item['cep'],
                                    "city"          => $item['city'],
                                    "state"         => $item['state'],
                                    "country"       => $item['country'],
                                    "street"        => $item['street'],
                                    "number"        => $item['number'],
                                    "neighborhood"  => $item['district'],
                                    "complement"    => $item['complement'],
                                    "reference"     => null,
                                    "geoCoordinates"=> []
                                ],
                                "additionalInfo"    => ''
                            ]
                        ];
                    }, $quote['data']['pickup_points']);
                }

                $slas = array_merge($slas_delivery, $slas_pickup_point);

                $quotes = array(
                    // obrigatório, int - index do array de items
                    "itemIndex"         => $index, // $key,
                    // obrigatório, int - estoque que atende
                    "stockBalance"      => (int) $quote['data']['skus'][$skumkt]['current_qty'],
                    // obrigatório, int - index do array de items
                    "quantity"          => (int) $current_product['qty'],
                    // obrigatório, int - index do array de items
                    "shipsTo"           => ['BRA'],
                    // obrigatório, pode ser um array vazio na ausência de CEP.
                    "slas"              => $slas
                );


                $quotes['deliveryChannels'] = array(
                    array(
                        "id"            => "delivery",
                        "stockBalance"  => (int) $quote['data']['skus'][$skumkt]['current_qty']
                    )
                );

                // Existe estoque para ponto de retirada.
                if (!empty($slas_pickup_point)) {
                    $quotes['deliveryChannels'][] = [
                        "id" => "pickup-in-point",
                        "stockBalance" => (int) $quote['data']['skus'][$skumkt]['current_qty']
                    ];
                }

                $logisticResponse[] = $quotes;
            }
        }

        foreach ($itemsResponse as $i) {
            $this->items_skus[] = $i;
        }
            
        foreach ($logisticResponse as $i) {
            $this->logistic_skus[] = $i;
        }

    }

    /**
     * Salvar log de erro
     *
     * @param string $message Mensagem de error
     * @return void
     */
    private function saveLogError(string $message): void
    {
        $this->log_data('api', 'FreteVTEX Consulta Frete', $message . "\npid=".getmypid(), 'E');
    }

    /*
    private function saveLogFile($data)
    {
        $pathFile = APPPATH.'logs/api/'.getmypid().'.php';
        $logFile = '';

        if (!is_dir(APPPATH.'logs/api')) {
            mkdir(APPPATH . 'logs/api', 0775);
        }

        if (file_exists($pathFile)) {
            $fileRead = fopen($pathFile, 'r');
            $logFile .= fread($fileRead,  filesize($pathFile)) . "\n\n";
            fclose($fileRead);
        }

        $file = fopen($pathFile, 'w');

        $logFile .= "URL={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}\nbody=" . json_encode($data) . "\ndate=".date(DATETIME_INTERNATIONAL);

        fwrite($file, $logFile);

        fclose($file);
    }
    */

    /**
     * Busca o estoque do produto e retorna a quantidade menor ou igual a quantidade no banco.
     * @param  array $received [seller_id,quantidade,sku]
     * @return int   valor do estoque do banco
     * */
    public function getCurrentStockQuantity(array $received) :int
    {
        $stock = $this->calculofrete->readonlydb->select('qty_atual as quantity')
            ->where([
                'skumkt'    => $received['id'],
                'seller_id' => $received['seller'],
            ])
            ->get('vtex_ult_envio')
            ->row_array();

        if (!$stock) {
            return $received['quantity'];
        }
        if (intval($stock['quantity']) <= $received['quantity']) {
            return intval($stock['quantity']);
        }
        return $received['quantity'];
    }
}