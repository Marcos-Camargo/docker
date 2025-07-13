<?php

require APPPATH . "controllers/Api/FreteConectala.php";

/**
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Security $security
 * @property CI_Output $output
 *
 * @property Model_settings $model_settings
 *
 * @property CalculoFrete $calculofrete
 * @property Slack $slack
 */

class QuoteDefault extends FreteConectala
{
    private $enable_log_quotes = false;
    private $enable_log_slack = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->library('calculoFrete');

        if ($this->model_settings->getValueIfAtiveByName('enable_log_quotes')) {
            $this->enable_log_quotes = true;
        }

        if ($this->model_settings->getValueIfAtiveByName('enable_log_slack')) {
            $this->enable_log_slack = true;
        }

        if ($this->enable_log_slack) {
            $this->load->library('Logging/Slack', array(), 'slack');
            $this->slack->endpoint = 'https://hooks.slack.com/services/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';
            $this->slack->channel = ['log_simulation_vtex'];
        }
    }


    /**
     * @return CI_Output|null
     */
    public function index_post(string $mkt, string $int_to = null): ?CI_Output
    {
        try {
            set_error_handler('customErrorHandler', E_ALL ^ E_NOTICE);
            // hash para identificar um grupo de cotação.
            $quote_id = Admin_Controller::getGUID(false);
            $timeStart = microtime(true) * 1000;
            $body = json_decode($this->security->xss_clean($this->input->raw_input_stream));

            $mkt = strtolower($this->tiraAcentos($mkt));
            $dataMarketplace['channel']   = $int_to ?? $mkt;
            $dataMarketplace['platform']  = $mkt;

            // Não conseguiu descodificar o JSON.
            if (is_null($body)) {
                return $this->response(array(
                    "items"          => [],
                    "postalCode"     => null,
                    "logisticsInfo"  => [],
                    "messages"       => [
                        $this->getError("Itens não encontrados ou indisponíveis")
                    ]
                ), REST_Controller::HTTP_BAD_REQUEST);
            }

            $zipcode  = isset($body->postalCode) ? onlyNumbers($body->postalCode) : null;

            // agrupar itens por loja.
            $skusKey = array();
            $productsToStore = array();
            $skuToIndex = array();
            $qtyToIndex = array();
            $response = array(
                'items'         => array(),
                'logisticsInfo' => array(),
                'postalCode'    => $zipcode
            );
            foreach ($body->items as $key => $item) {

                if (
                    !property_exists($item, 'id') ||
                    !property_exists($item, 'quantity') ||
                    !property_exists($item, 'seller')
                ) {
                    return $this->response(array(
                        "items"          => [],
                        "postalCode"     => $zipcode,
                        "logisticsInfo"  => [],
                        "messages"       => [
                            $this->getError("Preencha os itens com as propriedades: id, quantity e seller")
                        ]
                    ), REST_Controller::HTTP_BAD_REQUEST);
                }

                if (!array_key_exists($item->seller, $productsToStore)) {
                    $productsToStore[$item->seller] = array();
                }

                $skusKey[] = $item->id;

                $productsToStore[$item->seller][] = array(
                    'sku'    => $item->id,
                    'qty'    => (int)$item->quantity,
                    'seller' => $item->seller
                );

                $response['items'][$key] = array(
                    'id'            => $item->id,
                    'requestIndex'  => $key,
                    'quantity'      => $item->quantity,
                    'seller'        => $item->seller
                );

                $skuToIndex[$item->id] = $key;
                $qtyToIndex[$item->id] = $item->quantity;
            }

            $data_to_product_id = array();

            foreach ($productsToStore as $products) {
                $this->calculofrete->_time_start = microtime(true) * 1000;
                $quote = $this->calculofrete->formatQuote($dataMarketplace, $products, $zipcode, true, false);
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

                // Não encontrou os produtos.
                if (!isset($quote['data']['skus'])) {
                    continue;
                }

                // Define os itens da cotação.
                foreach ($quote['data']['skus'] as $sku => $dataSku) {
                    if (!array_key_exists($sku, $skuToIndex)) {
                        continue;
                    }

                    $itemIndex = $skuToIndex[$sku];

                    if (!array_key_exists($itemIndex, $response['logisticsInfo'])) {
                        $response['logisticsInfo'][$itemIndex] = array(
                            "itemIndex"     => $itemIndex,
                            "stockBalance"  => (int)$quote['data']['skus'][$sku]['current_qty'],
                            "quantity"      => $qtyToIndex[$sku],
                            'slas'          => array()
                        );
                    }

                    $response['items'][$itemIndex]['price'] = moneyFloatToVtex($quote['data']['skus'][$sku]['sale_price']);
                    $response['items'][$itemIndex]['listPrice'] = moneyFloatToVtex($quote['data']['skus'][$sku]['list_price']);
                }

                // Define as mensagens de erro.
                if (!$quote['success']) {
                    $response['messages'][] = $this->getError(str_replace("\n", '', str_replace('  ', '', $quote['data']['message'])));
                    continue;
                }

                // Define as slas.
                foreach ($quote['data']['services'] as $service) {
                    if (!array_key_exists($service['skumkt'], $skuToIndex)) {
                        continue;
                    }

                    $itemIndex = $skuToIndex[$service['skumkt']];

                    $response['logisticsInfo'][$itemIndex]['slas'][] = array(
                        "id"                => $service['method'],
                        "deliveryChannel"   => "delivery",
                        "name"              => "{$service['provider']} {$service['method']}",
                        "shippingEstimate"  => "{$service['deadline']}d",
                        "price"             => moneyFloatToVtex($service['value'])
                    );
                }
            }
            
            sort($response['logisticsInfo']);
            
            if ($zipcode) {
                $response['postalCode'] = $zipcode;

                $timeFinish = microtime(true) * 1000;

                if ($this->enable_log_quotes && isset($quote['data']['skus'])) {
                    $stores = array_column($data_to_product_id, 'store_id');
                    $isMultiseller = count(array_unique($stores)) > 1 ? 1 : 0;
                    foreach ($data_to_product_id as $product_id => $products) {
                        $this->db->insert('log_quotes', array(
                            'quote_id'                  => $quote_id,
                            'marketplace'               => $dataMarketplace['channel'],
                            'zipcode'                   => $zipcode,
                            'product_id'                => $product_id,
                            'skumkt'                    => $products['skumkt'],
                            'store_id'                  => $products['store_id'],
                            'seller_id'                 => $products['seller_id'] ?? '',
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
            }

            // Não encontrou produtos.
            if (count($response['logisticsInfo']) === 0) {
                $response = array(
                    "items"          => [],
                    "postalCode"     => $zipcode,
                    "logisticsInfo"  => [],
                    "messages"       => [
                        $this->getError("Itens não encontrados ou indisponíveis")
                    ]
                );
            }
        } catch (Throwable $exception) {
            if ($this->enable_log_slack) {
                $message = "Error={$exception->getMessage()}\nTrace={$exception->getTraceAsString()}\nBody=".file_get_contents('php://input')."\nUrl={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                $this->slack->send($message);
            }

            return $this->response(array(
                "items"          => [],
                "postalCode"     => $zipcode ?? null,
                "logisticsInfo"  => [],
                "messages"       => [
                    $this->getError($exception->getMessage())
                ]
            ), REST_Controller::HTTP_OK);
        }

        return $this->response($response, count($response['logisticsInfo']) === 0 ? REST_Controller::HTTP_BAD_REQUEST : REST_Controller::HTTP_OK);
    }

    /**
     * Recupera a mensagem de erro para retorno.
     *
     * @param   string $message
     * @return  array
     */
    private function getError(string $message = ''): array
    {
        return array(
            "code"      => null,
            "text"      => $message,
            "status"    => "error"
        );
    }
}