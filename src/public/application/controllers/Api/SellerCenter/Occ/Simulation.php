<?php

require APPPATH . "controllers/Api/FreteConectala.php";

/**
 * http://localhost:88/app/Api/SellerCenter/Occ/{marketplace}
 *
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property Model_settings $model_settings
 * @property Model_occ_last_post $model_occ_last_post
 * @property CalculoFrete $calculofrete
 * @property Slack $slack
 */

class Simulation extends FreteConectala {

    private $mkt;
    private $items_skus = array();
    private $logistic_skus = array();
    private $zipcode = null;
    private $idQuote = null;
    private $enable_log_slack = false;
    private $enable_log_quotes = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_occ_last_post');
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'OCC', 'channel' => NULL);
        header("Access-Control-Allow-Origin: *");
        if ( "OPTIONS" === $_SERVER['REQUEST_METHOD'] ) {
            die();
        }

        if ($this->model_settings->getValueIfAtiveByName('enable_log_slack')) {
            $this->enable_log_slack = true;
            $this->load->library('Logging/Slack', array(), 'slack');
            $this->slack->endpoint = 'https://hooks.slack.com/services/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';
            $this->slack->channel = ['log_simulation_occ'];
        }

        if ($this->model_settings->getValueIfAtiveByName('enable_log_quotes')) {
            $this->enable_log_quotes = true;
        }
    }

    /**
     * Get All Data from this method.
     *
     * @return void
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
     * @return void
     */
    public function index_post($mkt)
    {
        try {
            set_error_handler('customErrorHandler', E_ALL ^ E_NOTICE);
            $this->mkt['channel'] = $this->tiraAcentos($mkt);
            $timeStart = microtime(true) * 1000;
            $this->calculofrete->setSellerCenter();
            // hash para identificar um grupo de cotação.
            $quote_id = Admin_Controller::getGUID(false);

            $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));
            // $this->log_data('api', 'ConsultaFreteVTEX - POST', 'Chegou consulta frete='.json_encode($data), "I");
            if (is_null($data)) {
                return $this->response(array(
                    'items' => [],
                    'logisticsInfo' => []
                ), REST_Controller::HTTP_OK);
            }

            $zipcode  = isset($data['shippingAddress']['postalCode']) ? preg_replace('/[^0-9]/', '', $data['shippingAddress']['postalCode']) : null;
            $an       = isset($_GET['an']) ? filter_var($_GET['an'], FILTER_SANITIZE_STRING) : '';
            $sc       = isset($_GET['sc']) ? filter_var($_GET['sc'], FILTER_SANITIZE_STRING) : '';

            $this->zipcode = $zipcode;

            $rowSettingsSellerCenter = $this->calculofrete->getSellerCenter();

            $store_ids = array();
            $itemsResponse = array();
            $logisticResponse = array();
            $skusKey = array();
            $taxTotal = 0;
            $totalPriceQuote = 0;
            $totalDeadline = null;
            $providerQuote = array();
            $items = array();
            $items_holder = array();
            $logisticResponse = array();
            foreach($data['items'] as $key => $item) {

                $skusKey[] = $item['catRefId'];

                $occLastPost = $this->model_occ_last_post->getBySku($item['catRefId']);

                $items[] = array(
                        'sku' => $item['catRefId'],
                        'qty' => (int) $item['quantity'],
                    );
            }

            $data_to_product_id = array();
            $quotes = array();
            $quote_id_integration = null;

            foreach ($items as $current_product) {
                $this->calculofrete->_time_start = microtime(true) * 1000;
                $quote = $this->calculofrete->formatQuote($this->mkt, array($current_product), $zipcode);
                $this->calculofrete->_time_end = microtime(true) * 1000;

                $response_details_time = array(
                    'total'                 => $this->calculofrete->_time_end - $this->calculofrete->_time_start,
                    'query_sku'             => $this->calculofrete->_time_end_query_sku - $this->calculofrete->_time_start_query_sku,
                    'integration'           => $this->calculofrete->_time_end_integration - $this->calculofrete->_time_start_integration,
                    'integration_instance'  => $this->calculofrete->_time_end_integration_instance - $this->calculofrete->_time_start_integration_instance,
                    'internal_table'        => $this->calculofrete->_time_end_internal_table - $this->calculofrete->_time_start_internal_table,
                    'contingency'           => $this->calculofrete->_time_end_contingency - $this->calculofrete->_time_start_contingency,
                    'promotion'             => $this->calculofrete->_time_end_promotion - $this->calculofrete->_time_start_promotion,
                    'auction'               => $this->calculofrete->_time_end_auction - $this->calculofrete->_time_start_auction,
                    'price_rules'           => $this->calculofrete->_time_end_price_rules - $this->calculofrete->_time_start_price_rules,
                    'redis'                 => $this->calculofrete->_time_end_redis - $this->calculofrete->_time_start_redis,
                );

                if ($this->enable_log_quotes && isset($quote['data']['skus'])) {
                    foreach ($quote['data']['skus'] as $skumkt => $sku) {
                        $data_to_product_id[$sku['prd_id']] = array(
                            'seller_id'             => '',
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

                if ($quote["success"] == false){
                    $response['erros'] = [
                        array(
                        "errorCode" =>  1,
                        "description"=> $quote['data']['message']
                    )];
                    break;
                }
                $slas = array();

                if (isset($quote['data']['services'])) {
                    foreach ($quote['data']['services'] as $sla) {
                        $quote_id_integration = $sla['quote_id'];
                        // Para Frete grátis, não adicionamos taxa
                        if ($sla['value'] != 0 && $quote['data']['logistic']['sellercenter']) {
                            $tax = $this->calculofrete->calculaTaxa($quote['data']['totalPrice']);
                            $sla['value'] += $tax;
                            $taxTotal += $tax;
                        }

                        $slas[] = array(
                            "estimatedDeliveryDateGuaranteed"   => false,
                            "shippingCost"                      => (float) number_format($sla['value'], 2,'.',''),
                            "internationalDutiesTaxesFees"      => 0,
                            "displayName"                       => mb_convert_case(strtoupper($sla['method']), MB_CASE_TITLE, 'UTF-8'),
                            "estimatedDeliveryDate"             => get_instance()->somar_dias_uteis(date("Y-m-d"), $sla['deadline']).' 23:59:00 -0300',
                            "shippingTotal"                     => (float) number_format($sla['value'], 2,'.',''),
                            "currency"                          => "BRL",
                            "taxcode"                           => "icms",
                            "carrierId"                         => $sla['method'],
                            "deliveryDays"                      => $sla['deadline'],
                            "catRefId"                          => $current_product['sku'],
                            "shippingTax"                       => 0,
                            "sellerId"                          => $quote['store_id']

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

                $quotes = array_merge($quotes,$slas);


            }

            $logisticResponse = $quotes;
            if (isset($quote) && $quote["success"] == true){
                $response["shippingMethods"] = $logisticResponse;
            }


            // se mandou o CEP, retorno na resposta e grava registro de cotação
            if ($zipcode) {
                if(!isset($quote)){

                    $response['erros'] = [
                        array(
                        "errorCode" =>  1,
                        "description"=>'Cotação não encontrada'
                    )];

                }
                $timeFinish = microtime(true) * 1000;
                $this->db->insert('quotes_correios', array(
                    'marketplace'   => $this->mkt['channel'],
                    'zip'           => $zipcode,
                    'sku'           => json_encode($skusKey),
                    'price'         => $totalPriceQuote,
                    'time'          => $totalDeadline ?? '',
                    'service'       => count($providerQuote) === 1 ? $providerQuote[0] : json_encode($providerQuote),
                    'frete_taxa'    => $taxTotal,
                    'response'      => json_encode($response, true),
                    'response_time' => $timeFinish - $timeStart,
                    'error'         => $quote["success"] == false,
                    'integration_name' => '',
                    'status'        => '',
                    'store_id'      => ''
                ));

                $response['id'] = $quote_id_integration ?? $this->db->insert_id();

                if ($this->enable_log_quotes && isset($quote['data']['skus'])) {
                    foreach ($data_to_product_id as $product_id => $products) {
                        $this->db->insert('log_quotes', array(
                            'quote_id'                  => $quote_id ?? '',
                            'marketplace'               => $this->mkt['channel'],
                            'zipcode'                   => $zipcode,
                            'product_id'                => $product_id,
                            'skumkt'                    => $products['skumkt'],
                            'store_id'                  => $products['store_id'],
                            'seller_id'                 => $products['seller_id'],
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
            }

            $this->log_data('api', 'ConsultaFreteOCC - POST - return', 'url=' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ' - received=' . json_encode($data) . ' - return=' . json_encode($response));
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

        $this->response($response, REST_Controller::HTTP_OK);
    }
}