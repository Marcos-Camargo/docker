<?php 
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apib2w/freight
 * 
 */
require APPPATH . "controllers/Api/FreteConectala.php";

class FreteB2WV2 extends FreteConectala {

    private $mkt;

    /**
     * FreteB2W constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'B2W', 'channel' => 'B2W');
        $this->calculofrete->setDisableLogistic('0');
    }
        
    /**
     * Post All Data from this method.
     *
     * Example request
     *
     * {
     *  "destinationZip": 88010000,
     *  "volumes": [
     *    {
     *      "sku": "Q_B2W_1",
     *      "quantity": 1,
     *      "price": 265,
     *      "height": 40,
     *      "length": 29,
     *      "width": 27,
     *      "weight": 3.4
     *    }
     *  ]
     * }
     *
     * @return Response
    */	  
    public function index_post() 
    {
        $timeStart = microtime(true);
        $data = json_decode(file_get_contents('php://input'), true);

		if (is_null($data))
            return $this->returnError('Dados com formato errado'.print_r(file_get_contents('php://input'),true));

        if (!isset($data['volumes']) || !is_array($data['volumes']) || !count($data['volumes']))
            return $this->returnError('Sem o parametro shipping_zip_code. Recebido: '.print_r($data,true));

        if (!isset($data['destinationZip']) || empty($data['destinationZip']))
            return $this->returnError('Sem o parametro destinationZip. Recebido: '.print_r($data,true));

        $zip = filter_var(preg_replace('/\D/', '', $data['destinationZip']), FILTER_SANITIZE_NUMBER_INT);
        $zip = str_pad($zip, 8, 0, STR_PAD_LEFT);

        $items = array();
        $skusKey  = array();
        foreach ($data['volumes'] as $iten) {
            array_push($skusKey, $iten['sku']);
            array_push($items, array(
                'sku' => $iten['sku'],
                'qty' => (int)$iten['quantity']
            ));
        }

        $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip);

        // cotação chegou do moduloe frete
        if (isset($quote['regra']) && isset($quote['items_listed']))
            return $this->formatResponseModuloFrete($quote, $timeStart, $skusKey, $zip);

        if (!$quote['success'])
            return $this->returnError($quote['data']['message']);

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

        //versão Skybub ....
        // https://desenvolvedores.skyhub.com.br/homologacao-de-frete/processo-de-homologacao
        $response = array(
            'shippingQuotes' => array(
                array(
                    'shippingMethodId' => $tempProvider,
                    'shippingMethodName' => $tempMethod,
                    'shippingMethodDisplayName' => $tempMethod,
                    'shippingCost' => (float)$tempValue,
                    'deliveryTime' => array(
                        'total'      => $tempDeadLine,
                        'transit'    => $tempDeadLine - $quote['data']['crossDocking'],
                        'expedition' => $quote['data']['crossDocking'],
                    )
                )
            )
        );

//        $json_data = json_encode($response,JSON_UNESCAPED_UNICODE);
//        $json_data = stripslashes($json_data);
//        ob_clean();
//        header('Content-type: application/json');
//        echo stripslashes($json_data);
//        $this->response(NULL,REST_Controller::HTTP_OK);

        $timeFinish = microtime(true);
        // if (ENVIRONMENT !== "development") {
        //     $this->db->insert('quotes_correios', array(
        //         'marketplace'   => $this->mkt['channel'],
        //         'zip'           => $zip,
        //         'sku'           => json_encode($skusKey),
        //         'price'         => $tempValue,
        //         'time'          => $tempDeadLine,
        //         'service'       => $tempProvider,
        //         'frete_taxa'    => $tax,
        //         'response'      => json_encode($response, true),
        //         'response_time' => ($timeFinish-$timeStart)*1000
        //     ));    
        // }

        if (ENVIRONMENT !== "development") {
            $response["quote"] = array();
            array_push($response["quote"], array(
                'marketplace'   => $this->mkt['channel'],
                'zip'           => $zip,
                'sku'           => json_encode($skusKey),
                'price'         => $tempValue,
                'time'          => $tempDeadLine,
                'service'       => $tempProvider,
                'frete_taxa'    => $tax,
                'response'      => json_encode($response, true),
                'response_time' => ($timeFinish-$timeStart)*1000
            ));
        }
        return $this->response($response, REST_Controller::HTTP_OK);
    }

    /**
     * Retorno de erro quando não é possível realizar a cotação
     *
     * @param  string $message  Mensagem retornada da cotação
     * @return null             Retorna o json espero pelo marketplace quando encontra um erro
     */
    private function returnError(string $message)
    {
        // if (ENVIRONMENT !== 'development') {
        //     $this->log_data('api', "frete{$this->mkt['channel']}", $message, 'E');
        // }
        
        return $this->response(
            array(
                    'shippingMethodId' => 'Transportadora',
                    'shippingMethodName' => 'Transportadora',
                    'shippingMethodDisplayName' => 'Transportadora',
                    'shippingCost' => (float) 3000,
                    'deliveryTime' => array(
                        'total'      => (int)60,
                        'transit'    => (int)10,
                        'expedition' => (int)50,
                    )
                ),REST_Controller::HTTP_OK
            );
        //return $this->response(array('message' => 'Não foi possível realizar a consulta de preço e prazo'), REST_Controller::HTTP_OK);

		$json_msg = json_encode([
            'message' => 'Não foi possível realizar a consulta de preço e prazo'
            ],JSON_UNESCAPED_UNICODE);
		ob_clean();
		header('Content-type: application/json');
		echo stripslashes($json_msg);
		$this->response(REST_Controller::HTTP_NOT_FOUND);	
		die;
	}

    private function formatResponseModuloFrete(array $quoteRequest, float $timeStart, array $skusKey, string $zip)
    {
        if (!$quoteRequest['success'] || count($skusKey) !== count($quoteRequest['data']))
            return $this->returnError($quoteRequest['data']['message'] ?? "Nem todos os skus podem ser enviados.\nskus_request=".json_encode($skusKey)."\nskus_response=".json_encode($quoteRequest));

        $result_grouped = array();
        foreach ($quoteRequest['data'] as $element) {
            $result_grouped[$element->delivery_method_name][] = $element;
        }

        $quotes = array();

        $total_price = 0.0;
        $delivery_time = 0;

        if($quoteRequest['regra'] === 'todos') {
            foreach($quoteRequest['data'] as $quote) {
                array_push($quotes, array(
                    'shippingMethodId' => $quote->name_provider,
                    'shippingMethodName' => $quote->delivery_method_name,
                    'shippingMethodDisplayName' => $quote->delivery_method_name,
                    'shippingCost' => (float)$quote->shipping_price,
                    'deliveryTime' => array(
                        'total'      => (int)$quote->qtd_days + (int) $quote->cross_docking,
                        'transit'    => (int)$quote->qtd_days,
                        'expedition' => (int)$quote->cross_docking,
                    )
                ));
            }  
        } else if($quoteRequest['regra'] === 'menor prazo e menor preço') {
            foreach($result_grouped as $quote) {
                $quote_used = NULL;
                $total_price = 0.0;
                $delivery_time = 0;

                foreach($quote as $quote_item) {
                    $total_price += (float)$quote_item->shipping_price;
        
                    if($quote_item->qtd_days > $delivery_time) {
                        $delivery_time = $quote_item->qtd_days;
                        $quote_used = $quote_item;
                    }
                }

                array_push($quotes, array(
                    'shippingMethodId' => $quote_used->name_provider,
                    'shippingMethodName' => $quote_used->delivery_method_name,
                    'shippingMethodDisplayName' => $quote_used->delivery_method_name,
                    'shippingCost' => (float)$total_price,
                    'deliveryTime' => array(
                        'total'      => (int)$delivery_time + $quote_used->cross_docking,
                        'transit'    => (int)$delivery_time,
                        'expedition' => (int)$quote_used->cross_docking,
                    )
                ));
            }
        } else {
            foreach($quoteRequest['data'] as $quote) {
                $total_price += (float)$quote->shipping_price;
    
                if($quote->qtd_days > $delivery_time) {
                    $delivery_time = $quote->qtd_days;
                }
            }
    
            $quote = $quoteRequest['data'][0];
    
            array_push($quotes, array(
                'shippingMethodId' => $quote->name_provider,
                'shippingMethodName' => $quote->delivery_method_name,
                'shippingMethodDisplayName' => $quote->delivery_method_name,
                'shippingCost' => (float)$total_price,
                'deliveryTime' => array(
                    'total'      => (int)$delivery_time + $quote->cross_docking,
                    'transit'    => (int)$delivery_time,
                    'expedition' => (int)$quote->cross_docking,
                )
            ));
        }

        $response = array(
            'shippingQuotes' => $quotes
        );

        $timeFinish = ((microtime(true) - $timeStart) * 1000);

        if (ENVIRONMENT !== 'development') {
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
        }

        return $this->response($response, REST_Controller::HTTP_OK);
    }
}