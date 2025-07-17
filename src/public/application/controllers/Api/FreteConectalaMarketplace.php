<?php 

require APPPATH . "controllers/Api/FreteConectala.php";

/**
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Input $input
 *
 * @property CalculoFrete $calculofrete
 * @property Slack $slack
 * @property Model_integration_last_post $model_integration_last_post
 * @property Model_sellercenter_last_post $model_sellercenter_last_post
 * @property Model_settings $model_settings
 */

class FreteConectalaMarketplace extends FreteConectala 
{
    private $mkt;
    private $enable_log_slack = false;
    private $enable_log_quotes = false;

    public function __construct() 
    {
        parent::__construct();
        $this->load->library('calculoFrete');
		$this->load->model('model_integration_last_post');
		$this->load->model('model_sellercenter_last_post');
        $this->load->model('model_settings');

        if ($this->model_settings->getValueIfAtiveByName('enable_log_slack')) {
            $this->enable_log_slack = true;
        }

        if ($this->model_settings->getValueIfAtiveByName('enable_log_quotes')) {
            $this->enable_log_quotes = true;
        }

        if ($this->enable_log_slack) {
            $this->load->library('Logging/Slack', array(), 'slack');
            $this->slack->endpoint = 'https://hooks.slack.com/services/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';
            $this->slack->channel = ['log_quote_vs'];
        }
    }

    public function index_post() 
    {
        try {
            set_error_handler('customErrorHandler', E_ALL ^ E_NOTICE);
            $timeStart = microtime(true) * 1000;
            $data = json_decode(file_get_contents('php://input'), true);
            $quote_id = Admin_Controller::getGUID(false);

            if (!array_key_exists('destinationZip', $data)) {
                return $this->returnError('Sem o parâmetro shipping_zip_code. Recebido: ' . print_r($data, true));
            }

            if (!array_key_exists('volumes', $data)) {
                return $this->returnError('Sem o parâmetro volumes. Recebido: ' . print_r($data, true));
            }

            $zip = str_pad(onlyNumbers($data['destinationZip']), 8, 0, STR_PAD_LEFT);

            $items      = array();
            $skusKey    = array();
            foreach ($data['volumes'] as $vol) {
                $items[] = array(
                    'sku' => $vol['sku'],
                    'qty' => (int)$vol['quantity']
                );
                $skusKey[] = $vol['sku'];
            }

            if (count($skusKey) == 0) {
                return $this->returnError('Nenhum SKU enviado: '.print_r($data,true));
            }
            // pego int_to baseado no primeiro SKU envido.
            $int_tos=$this->model_sellercenter_last_post->getInttoBySkulocal($skusKey[0]);
            if (!$int_tos) {
                $int_tos=$this->model_integration_last_post->getInttoBySkulocal($skusKey[0]);
                if (!$int_tos) {
                    return $this->returnError('Sku não encontrado '.$skusKey[0].'. Recebido: '.print_r($data,true));
                }
            }
            $this->mkt = array('platform' => $int_tos['int_to'], 'channel' => $int_tos['int_to']);

            //vejo a cotação do frete agora
            $data_to_product_id = array();
            $this->calculofrete->_time_start = microtime(true) * 1000;
            $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip, true, false);
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

            if (!$quote['success']) {
                if ($zip) {
                    $timeFinish = microtime(true) * 1000;
                    $this->saveLogQuotes($quote, $data_to_product_id, $quote_id, $zip, $timeFinish, $timeStart, $this->getMessageError());
                }
                return $this->returnError($quote['data']['message']);
            }

            $freights = array();
            // Por padrão retornaremos o melhor preço até o módulo frete ficar pronto.
            foreach ($quote['data']['services'] as $service) {
                $freights[] = array(
                    'sku'                => $service['skumkt'],
                    'shippingCost'       => $service['value'],
                    'deliveryTime'       => $service['deadline'],
                    'shippingEstimateId' => $service['provider'],
                    'shippingMethodName' => $service['method']
                );
            }

            $response = array();
            $response['shippingQuotes'] = $freights;

            if ($zip) {
                $timeFinish = microtime(true) * 1000;
                $this->db->insert('quotes_correios', array(
                    'marketplace'   => $this->mkt['channel'],
                    'zip'           => $zip,
                    'sku'           => json_encode($skusKey),
                    'price'         => 0,
                    'time'          => 0,
                    'service'       => '',
                    'frete_taxa'    => 0,
                    'response'      => json_encode($freights, true),
                    'response_time' => $timeFinish - $timeStart
                ));

                $this->saveLogQuotes($quote, $data_to_product_id, $quote_id, $zip, $timeFinish, $timeStart, $response);
            }
        } catch (Throwable $exception) {
            if ($this->enable_log_slack) {
                $message = "Error={$exception->getMessage()}\nTrace={$exception->getTraceAsString()}\nBody=".file_get_contents('php://input')."\nUrl={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                $this->slack->send($message);
            }

            return $this->returnError($exception->getMessage());
        }

        return $this->response($response, REST_Controller::HTTP_OK);

    }

    private function saveLogQuotes(array $quote, array $data_to_product_id, string $quote_id, string $zip, string $timeFinish, string $timeStart, array $response)
    {
        if ($this->enable_log_quotes && isset($quote['data']['skus'])) {
            $stores = array_column($data_to_product_id, 'store_id');
            $isMultiseller = count(array_unique($stores)) > 1 ? 1 : 0;
            foreach ($data_to_product_id as $product_id => $products) {
                $this->db->insert('log_quotes', array(
                    'quote_id'                  => $quote_id,
                    'marketplace'               => $this->mkt['channel'],
                    'zipcode'                   => $zip,
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

    /**
     * Retorno de erro quando não é possível realizar a cotação
     *
     * @param  string $message  Mensagem retornada da cotação
     * @return null             Retorna o json espero pelo marketplace quando encontra um erro
     */
    private function returnError(string $message)
    {
        $channel = is_array($this->mkt) && array_key_exists('channel', $this->mkt)
            ? $this->mkt['channel']
            : 'unknown';
        $this->log_data('api', "frete{$channel}", $message, 'E');

        $returnError = $this->getMessageError();

        return $this->response($returnError, REST_Controller::HTTP_OK);
    }

    private function getMessageError(): array
    {
        return array(
            'shippingQuotes' => array(
                array(
                    'shippingCost'              => 8888,
                    'deliveryTime'              => 99,
                    'shippingEstimateId'        => '76429',
                    'shippingMethodId'          => '',
                    'shippingMethodName'        => 'Indisponivel',
                    'shippingMethodDisplayName' => ''
                )
            )
        );
    }
}