<?php 
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apib2w/freight
 * 
 */
require APPPATH . "controllers/Api/FreteConectala.php";

class LeilaoB2W extends FreteConectala {

    private $mkt;

    /**
     * FreteB2W constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'B2W', 'channel' => 'B2W');
    }

    public function auction_post()
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

        $items = array();
        $skusKey  = array();
        foreach ($data['volumes'] as $iten) {
            array_push($skusKey, $iten['sku']);
            array_push($items, array(
                'sku' => $iten['sku'],
                'qty' => (int)$iten['quantity']
            ));
        }

        $quotesCalc = $this->calculofrete->leilao($this->mkt, $items, $zip, $timeStart);

        if (!$quotesCalc['success'])
            return $this->returnError($quotesCalc['data']['message']);

        $result_grouped = array();
        foreach ($quotesCalc['data'] as $element) {
            $result_grouped[$element->delivery_method_name][] = $element;
        }

        $quotes = array();

        $total_price = 0.0;
        $delivery_time = 0;

        if($quotesCalc['regra'] === 'todos') {
            foreach($quotesCalc['data'] as $quote) {
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
        } else if($quotesCalc['regra'] === 'menor prazo e menor preço') {
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
            foreach($quotesCalc['data'] as $quote) {
                $total_price += (float)$quote->shipping_price;
    
                if($quote->qtd_days > $delivery_time) {
                    $delivery_time = $quote->qtd_days;
                }
            }
    
            $quote = $quotesCalc['data'][0];
    
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

        $this->db->insert('quotes_marketplace', array(
            'marketplace'               => $this->mkt['channel'],
            'sku'                       => json_encode($skusKey),
            'datahora'                  => date("Y/m/d h:i:sa"),
            'cep_destino'               => $zip,
            'cotacao_externa'           => $quotesCalc['microtime_externo'],
            'cotacao_externa_retornos'  => json_encode($quotesCalc['respostas_externas']),
            'cotacao_interna_retornos'  => json_encode($quotesCalc['respostas_internas']),
            'cotacao_interna'           => $quotesCalc['microtime_interno'],
            'resposta_final'            => json_encode($response),
            'tempo_total_consulta'      => $timeFinish,
            'observacao'                => 'Seller usando regra de retorno ' . $quotesCalc['regra']
        ));

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
        $this->log_data('api', "frete{$this->mkt['channel']}", $message, 'E');

		$json_msg = json_encode([
            'message' => 'Não foi possível realizar a consulta de preço e prazo',
            'error' => $message
            ],JSON_UNESCAPED_UNICODE);
		ob_clean();
		header('Content-type: application/json');
		echo stripslashes($json_msg);
		$this->response(REST_Controller::HTTP_NOT_FOUND);	
		die;
	}

}