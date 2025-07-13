<?php
/* Método de chamada redefinido no config/routes.php
 *
 * url_site/Apivia/freight
 *
 */
 
require APPPATH . "controllers/Api/FreteConectala.php";

class LeilaoVIA extends FreteConectala {
    
	private $mkt;

    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'VIA', 'channel' => 'VIA');
    }

    public function auction_get()
    {
        $data = $this->input->get();
        $timeStart = microtime(true);
		
		if (!array_key_exists ('zipCode', $data))
            return $this->returnError('Sem o parametro zipCode. Recebido: '.print_r($data,true));

		if (!array_key_exists ('skuId', $data))
            return $this->returnError('Sem o parametro skuId. Recebido: '.print_r($data,true));

        $zip = filter_var(preg_replace('/\D/', '', $data['zipCode']), FILTER_SANITIZE_NUMBER_INT);

		$tmpArray = explode('|',$data['skuId']);

        $items = array();
        $skusKey  = array();
		foreach ($tmpArray as $skuqtd) {
			$temp =explode(',',$skuqtd);
            array_push($items, array(
                'sku' => $temp[0],
                'qty' => (int)$temp[1]
            ));
            array_push($skusKey, $temp[0]);
		}

        $quotesCalc = $this->calculofrete->leilao($this->mkt, $items, $zip, $timeStart);

        if (!$quotesCalc['success'])
            return $this->returnError($quotesCalc['data']['message']);

        $result_grouped = array();
        foreach ($quotesCalc['data'] as $element) {
            $result_grouped[$element->delivery_method_name][] = $element;
        }

        $quotes = array();
        foreach($result_grouped as $quote) {
            array_push($quotes, (object) [
                'freights' => iterator_to_array(call_user_func(function() use ($quote) {
                    foreach($quote as $item_quote) {
                        yield array(
                            'skuIdOrigin'   => $item_quote->sku,
                            'quantity'      => $item_quote->quantity,
                            'freightAmount' => (float)$item_quote->shipping_price,
                            'deliveryTime'  => (int)$item_quote->qtd_days + (int)$item_quote->cross_docking,
                            'freightType'   => "NORMAL"
                        );
                    }
                })),
                'freightAdditionalInfo' => $quote[0] -> name_provider,
                'sellerMpToken'         => '8hxJlXvVp3QO'
            ]);
        }

        if(count($quotes) > 0) {
            $response = $quotes[0];
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