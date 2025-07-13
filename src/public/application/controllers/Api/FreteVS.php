<?php
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apivs/freight
 * 
 * Frete do Hub Vertem. 
 */
 
require APPPATH . "controllers/Api/FreteConectala.php";

/**
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property Model_settings $model_settings
 * @property Model_vs_last_post $model_vs_last_post
 * @property Model_integrations $model_integrations
 * @property CalculoFrete $calculofrete
 * @property Slack $slack
 */
class FreteVS extends FreteConectala 	
{
	private $mkt;
    private $enable_log_slack = false;
    private $enable_log_quotes = false;
	
    public function __construct() {
        parent::__construct();
        $this->load->model('model_settings');
		$this->load->model('model_vs_last_post');
        $this->load->model('model_integrations');
        $this->load->library('calculoFrete');

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

        $this->mkt = array('platform' => 'VS', 'channel' => 'VS');

    }
	
	private function verifyHeaders($headers, $store) {
		foreach ($headers as $header => $value) {
			if (($header == 'Authorization') && (preg_match('/^basic/i', $value ))) {
				$user ="loja".$store['id'];
				$pass = substr($store['token_api'],0,12);
				list( $username, $password ) = explode( ':', base64_decode( substr( $value, 6 ) ) );
				
				if (($username == $user) && ($pass == $password)) {
					return true;
				}
				return false;
			}
		}
		return false;
	}

    public function index_get($id = NULL)
    {
    	// check availability - https://partnerhubapimqa.developer.azure-api.net/availability
    	$timeStart = microtime(true);
		$data = $this->input->get();
		
		if (!array_key_exists ('supplierId', $data)) {
			return $this->returnErrorGet('Sem o parametro supplierId ou inválido. Recebido: '.print_r($data,true), "402");
		}
		$supplier_id = $data['supplierId'] ; 
		
		$store = $this->model_integrations->getStoreByMKTSeller($this->mkt['channel'], $supplier_id);
		if (!$store) {
			return $this->returnErrorGet('Sem o parametro supplierId ou inválido. Recebido: '.print_r($data,true), "402");
		} 
		
		if (!$this->verifyHeaders(getallheaders(), $store)) {
			$error =  "No authentication key";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die; 
		}
		
		if (!array_key_exists ('Productskuid', $data)) {
			return $this->returnErrorGet('Sem o parametro Productskuid. Recebido: '.print_r($data,true),  "401");
		}

		$sku = $data['Productskuid'] ; 
		
		if (!$this->model_vs_last_post->verifyProductOnSeller($supplier_id, $sku)) {
			return $this->returnErrorGet('Produto não encontrado neste suplier id', "403");
		} 
		
		$zip = null; 
		
		$items = array();
		$items[] = array(
            'sku' => $sku,
            'qty' => 1
        );

        $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip);
		
		
        // cotação chegou do módulo de frete
        if (isset($quote['regra']) && isset($quote['items_listed'])) {
        	$products = $this->db->query($this->calculofrete->getQueryTableProvidersUltEnvio($this->mkt),
                    array($sku, $this->mkt['channel']))->result_array();

            foreach ($products as $product) {
				$price = (int)($product['list_price'] * 100);
				$promotional_price = (int)($product['price'] * 100);
				$qty = $product['qty_atual'];
            }
        }
		else {
	        if (!isset($quote['data']['skus']) || !array_key_exists($sku, $quote['data']['skus'])) {
	        	return $this->returnErrorGet('Productskuid Não encontado: '.$sku, "404");
	        };
			$price = $quote['data']['skus'][$sku]['list_price'] * 100;
			$promotional_price = $quote['data']['skus'][$sku]['sale_price'] * 100;
			$qty = $quote['data']['skus'][$sku]['current_qty'];
			
			if (!$quote['success']) {
				// rick  - ver o que dá. 
                // echo $quote['data']['message'] . "\n" . json_encode($data); 
                // $this->saveLogError($quote['data']['message'] . "\n" . json_encode($data));
                // continue;  
                // $qty = 0;
	        }
		} 
		
		$response = array( 
			'error' => null, 
			'isAvailable' => $qty > 0 ? TRUE : FALSE,
			'priceFrom' => (int)$price,
			'priceFor' => (int)$promotional_price
		
		);

        return $this->response($response, REST_Controller::HTTP_OK);
    }

    /**
     * Retorno de erro quando não é possível realizar a cotação
     *
     * @param  string $message  Mensagem retornada da cotação
     * @return null             Retorna o json espero pelo marketplace quando encontra um erro
     */
    private function returnErrorGet(string $message, $code)
    {
		$this->log_data('api', "frete{$this->mkt['channel']} Availability", $message, 'E');
		
		$response = array( 
				'error' => array (
					'code' => (int)$code,
					'message' => $message
				), 
				'isAvailable' => false,
				'priceFrom' => 0,
				'priceFor' => 0
			);
		
        return $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
	}
	
	private function formatResponseModuloFreteGet(array $quoteRequest, float $timeStart, array $skusKey, string $zip)
    {
        if (!$quoteRequest['success'])
            return $this->returnError($quoteRequest['data']['message'], 105);

        $result_grouped = array();
        foreach ($quoteRequest['data'] as $element) {
            $result_grouped[$element->delivery_method_name][] = $element;
        }

        $quotes = array();
        foreach($result_grouped as $quote) {
            array_push($quotes, iterator_to_array(call_user_func(function() use ($quote) {
                foreach($quote as $item_quote) {
                    yield array(
                        'skuIdOrigin'   => $item_quote->sku,
                        'quantity'      => $item_quote->quantity,
                        'freightAmount' => (float)$item_quote->shipping_price,
                        'deliveryTime'  => (int)$item_quote->qtd_days + (int)$item_quote->cross_docking,
                        'freightType'   => "NORMAL"
                    );
                }
            })));
        }

        if (count($quotes) > 0) {
            $response = (object) [
                'freights' => array(),
                'freightAdditionalInfo' => "",
                'sellerMpToken'         => '8hxJlXvVp3QO'
            ];
            foreach ($quotes as $quote)
                foreach ($quote as $_quote)
                    array_push($response->freights, $_quote);
        } else {
            $response = (object) [
                'freights' => array(),
                'freightAdditionalInfo' => "",
                'sellerMpToken'         => '8hxJlXvVp3QO'
            ];
        }

        $timeFinish = (microtime(true) - $timeStart) * 1000;
        
        $this->db->insert('quotes_marketplace', array(
            'marketplace'               => $this->mkt['channel'],
            'sku'                       => json_encode($skusKey),
            'datahora'                  => date("Y/m/d h:i:sa"),
            'cep_destino'               => $zip,
            'cotacao_externa'           => $quoteRequest['microtime_externo'],
            'cotacao_externa_retornos'  => json_encode($quoteRequest['respostas_externas']),
            'cotacao_interna_retornos'  => json_encode($quoteRequest['respostas_internas']),
            'cotacao_interna'           => $quoteRequest['microtime_interno'],
            'resposta_final'            => json_encode($response),
            'tempo_total_consulta'      => $timeFinish,
            'observacao'                => 'Seller usando regra de retorno ' . $quoteRequest['regra']
        ));

        return $this->response($response, REST_Controller::HTTP_OK);
    }

	public function index_post() 
    {
        // https://partnerhubapimqa.developer.azure-api.net/freight
        try {
            set_error_handler('customErrorHandler', E_ALL ^ E_NOTICE);
            $timeStart = microtime(true) * 1000;
            // hash para identificar um grupo de cotação.
            $quote_id = Admin_Controller::getGUID(false);

            $dataget = $this->input->get();

            if (!array_key_exists ('supplierId', $dataget)) {
                return $this->returnError('Sem o parametro supplierId ou inválido. Recebido: '.print_r($dataget,true), "101");
            }

            $supplier_id = $dataget['supplierId'] ;

            $store = $this->model_integrations->getStoreByMKTSeller($this->mkt['channel'], $supplier_id);
            if (!$store) {
                return $this->returnErrorGet('Sem o parametro supplierId ou inválido. Recebido: '.print_r($dataget,true), "102");
            }

            if (!$this->verifyHeaders(getallheaders(), $store)) {
                $error =  "No authentication key";
                show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
                die;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (is_null($data)) {
                return $this->returnError('Dados com formato errado '.print_r(file_get_contents('php://input'),true),100);
            }

            if (!isset($data['products']) || !is_array($data['products']) || !count($data['products'])) {
                return $this->returnError('Sem o parametro products. Recebido: '.print_r($data,true), 103);
            }

            if (!isset($data['zipCode']) || empty($data['zipCode'])) {
                return $this->returnError('Sem o parametro zipCode. Recebido: '.print_r($data,true),104);
            }

            $zip = filter_var(preg_replace('/\D/', '', $data['zipCode']), FILTER_SANITIZE_NUMBER_INT);
            $zip = str_pad($zip, 8, 0, STR_PAD_LEFT);

            $items = array();
            $skusKey  = array();
            foreach ($data['products'] as $iten) {
                $skusKey[] = $iten['sku'];
                $items[] = array(
                    'sku' => $iten['sku'],
                    'qty' => (int)$iten['quantity']
                );
                if (!$this->model_vs_last_post->verifyProductOnSeller($supplier_id, $iten['sku'])) {
                    return $this->returnError('Produto não encontrado '.$iten['sku'].' neste suplier id', "105");
                }
            }

            $data_to_product_id = array();
            $this->calculofrete->_time_start = microtime(true) * 1000;
            $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip);
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

            if (
                !$quote['success'] ||
                !isset($quote['data']['services']) ||
                !count($quote['data']['services'])
            ) {
                $message_error = $quote['data']['message'] ?? 'Sem transportadora disponível!';

                if ($zip) {
                    $timeFinish = microtime(true) * 1000;
                    $this->saveLogQuotes($quote, $data_to_product_id, $quote_id, $zip, $timeFinish, $timeStart, $this->getMessageError($message_error, 106));
                }

                return $this->returnError($message_error, 106);
            }

            $tempDeadLine   = null;
            $tempValue      = null;
            $tempProvider   = null;
            $tempMethod     = null;
            // por default iremos retornar o melhor preço até o módulo frete ficar pronto.
            foreach ($quote['data']['services'] as $service) {
                $price      = $service['value'];
                $deadline   = $service['deadline'];
                $changeData = false;

                if (
                    $tempValue === null ||
                    $price < $tempValue ||
                    (
                        $deadline < $tempDeadLine &&
                        $price == $tempValue
                    )
                ) $changeData = true;

                if ($changeData) {
                    $tempValue      = $price;
                    $tempDeadLine   = $deadline;
                    $tempProvider   = $service['provider'];
                    $tempMethod     = $service['method'];
                }
            }

            $tax = 0;
            /*
            if ($tempValue != 0 && $quote['data']['logistic']['sellercenter']) { // Para Frete gratis não adicionamos taxa
                $tax = $this->calculofrete->calculaTaxa($quote['data']['totalPrice']);
                $tempValue += $tax;
            }
            */

            $tempValue = $tempValue * 100;
            $data_dias_uteis = $this->somar_dias_uteis(dateNow()->format("Y-m-d"), $tempDeadLine);
            $data_dias_uteis = DateTime::createFromFormat("Y-m-d", $data_dias_uteis);

            $datedeliver = $data_dias_uteis->format("Y-m-d\TH:i:s.00000-03:00");

            $itemsret = array();
            foreach($skusKey as $sku) {
                $itemsret[] = array (
                    'sku' => $sku,
                    'scheduledDeliveryDate' => $datedeliver
                );
            }

            $response = array(
                'error' 	=> null,
                'costPrice' => (int)$tempValue,
                'items'		=> $itemsret
            );

            if ($zip) {
                $timeFinish = microtime(true) * 1000;
                $this->db->insert('quotes_correios', array(
                    'marketplace'   => $this->mkt['channel'],
                    'zip'           => $zip,
                    'sku'           => json_encode($skusKey),
                    'price'         => $tempValue,
                    'time'          => $tempDeadLine === null ? 999 : $tempDeadLine,
                    'service'       => $tempProvider === null ? 999 : $tempProvider,
                    'frete_taxa'    => $tax,
                    'response'      => json_encode($response, true),
                    'response_time' => $timeFinish - $timeStart
                ));

                $this->saveLogQuotes($quote, $data_to_product_id, $quote_id, $zip, $timeFinish, $timeStart, $response);
            }
        } catch (Throwable $exception) {
            if ($this->enable_log_slack) {
                $message = "Error={$exception->getMessage()}\nTrace={$exception->getTraceAsString()}\nBody=".file_get_contents('php://input')."\nUrl={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                $this->slack->send($message);
            }

            return $this->returnError($exception->getMessage(), 106);
        }

        return $this->response($response, REST_Controller::HTTP_OK);
    }

    private function saveLogQuotes(array $quote, array $data_to_product_id, string $quote_id, string $zip, string $timeFinish, string $timeStart, array $response)
    {
        if ($this->enable_log_quotes && isset($quote['data']['skus'])) {
            foreach ($data_to_product_id as $product_id => $products) {
                $this->db->insert('log_quotes', array(
                    'quote_id'                  => $quote_id,
                    'marketplace'               => $this->mkt['channel'],
                    'zipcode'                   => $zip,
                    'product_id'                => $product_id,
                    'skumkt'                    => $products['skumkt'],
                    'store_id'                  => $products['store_id'],
                    'seller_id'                 => $products['seller_id'] ?? '',
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

    private function returnError(string $message, $code)
    {
        $this->log_data('api', "frete{$this->mkt['channel']}", $message, 'E');

        return $this->response($this->getMessageError($message, $code), REST_Controller::HTTP_BAD_REQUEST);
    }

    private function getMessageError(string $message, $code): array
    {
        return array(
            'error' => array (
                'code' => (int)$code,
                'message' => $message
            ),
            'costPrice' => null,
            'items' => null,
        );
    }
	
	private function formatResponseModuloFrete(array $quoteRequest, float $timeStart, array $skusKey, string $zip)
    {
        if (!$quoteRequest['success'] || count($skusKey) !== count($quoteRequest['data'])) {
        	return $this->returnError($quoteRequest['data']['message'] ?? "Nem todos os skus podem ser enviados.\nskus_request=".json_encode($skusKey)."\nskus_response=".json_encode($quoteRequest), 107);
			
        }
            
        $result_grouped = array();
        foreach ($quoteRequest['data'] as $element) {
            $result_grouped[$element->delivery_method_name][] = $element;
        }

        $quotes = array();

        $total_price = 0.0;
        $delivery_time = 0;

	    $quote = $quoteRequest['data'][0];
	   
        $tempValue = (float)$quote->shipping_price * 100;       
		$deadLine =   (int)$quote->qtd_days + (int) $quote->cross_docking;
        $data_dias_uteis = $this->somar_dias_uteis(date("Y-m-d\TH:i:s.00000-03:00"), $deadLine); 
        $data_dias_uteis = DateTime::createFromFormat("Y-m-d", $data_dias_uteis);
        
          
        $datedeliver = $data_dias_uteis->format("Y-m-d\TH:i:s.00000-03:00");
		
        $itemsret = array();
		foreach($skusKey as $sku) {
			$itemsret[] = array (
				'sku' => $sku,
				'scheduledDeliveryDate' => $datedeliver
			);
		}
        
        $response = array(
        	'error' 	=> null, 
        	'costPrice' => (int)$tempValue,
        	'items'		=> $itemsret
        );
		
        $timeFinish = ((microtime(true) - $timeStart) * 1000);

        $this->db->insert('quotes_marketplace', array(
            'marketplace'               => $this->mkt['channel'],
            'sku'                       => json_encode($skusKey),
            'datahora'                  => date("Y/m/d h:i:sa"),
            'cep_destino'               => $zip,
            'cotacao_externa'           => $quoteRequest['microtime_externo'],
            'cotacao_externa_retornos'  => json_encode($quoteRequest['respostas_externas']),
            'cotacao_interna_retornos'  => json_encode($quoteRequest['respostas_internas']),
            'cotacao_interna'           => $quoteRequest['microtime_interno'],
            'resposta_final'            => json_encode($response),
            'tempo_total_consulta'      => $timeFinish,
            'observacao'                => 'Seller usando regra de retorno ' . $quoteRequest['regra']
        ));

        return $this->response($response, REST_Controller::HTTP_OK);
    }
}