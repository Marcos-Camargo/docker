<?php 
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apib2w/freight
 * 
 */
require APPPATH . "controllers/Api/FreteConectala.php";

class LeilaoVtex extends FreteConectala {

    private $mkt;

    /**
     * FreteB2W constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'VTEX', 'channel' => NULL);
    }

    public function auction_post($mkt)
    {
        $this->mkt['channel'] = $this->tiraAcentos($mkt);

        $timeStart = microtime(true);
        $data = json_decode(file_get_contents('php://input'), true);

		if (is_null($data))
            return $this->returnError('Dados com formato errado'.print_r(file_get_contents('php://input'),true));

        if (!isset($data['items']) || !is_array($data['items']) || !count($data['items']))
            return $this->returnError('Sem o parametro items. Recebido: '.print_r($data,true));

        $items = array();
        $skusKey  = array();
        foreach ($data['items'] as $iten) {
            array_push($skusKey, $iten['id']);
            array_push($items, array(
                'sku' => $iten['id'],
                'quantity' => (int)$iten['quantity'],
                'seller_id' => $iten['seller']
            ));
        }

        $products = $this->db->query($this->calculofrete->getQueryTableProvidersUltEnvio($this->mkt), 
        array($skusKey, $items[0]['seller_id'], $this->mkt['channel']))->result_array();

        $returnItens = array();
        $emptyQuotes = array();

        foreach($products as $product) {
            array_push($returnItens, array(
                'id'                => $product['skumkt'],
                'requestIndex'      => count($emptyQuotes),
                'price'             => (int) ($product['price'] * 100),
                'listPrice'         => (int) ($product['list_price'] * 100),
                'quantity'          => $items[count($emptyQuotes)]['quantity'],
                'seller'            => $items[count($emptyQuotes)]['seller_id'],
                'merchantName'      => "",
                "priceValidUntil"   => NULL
            ));

            array_push($emptyQuotes, array(
                'itemIndex'         => count($emptyQuotes),
                'stockBalance'      => (int) $product['qty_atual'],
                'quantity'          => $items[count($emptyQuotes)]['quantity'],
                'shipsTo'           => array('BRA'),
                'slas'              => array(),
            ));
        }

        if (!isset($data['postalCode']) || empty($data['postalCode'])) {
            $response = array(
                'items'             => $returnItens,
                'logisticsInfo'     => $emptyQuotes
            );
            return $this->response($response, REST_Controller::HTTP_OK);
        }

        $data['postalCode'] = str_replace("-", "", $data['postalCode']);
        $zip = filter_var(preg_replace('/\D/', '', $data['postalCode']), FILTER_SANITIZE_NUMBER_INT);

        $quotesCalc = $this->calculofrete->leilao($this->mkt, $items, $zip, $timeStart);
        $quotes = array(); 
        
        if (!$quotesCalc['success'])
            return $this->returnError($quotesCalc['data']['message']);

        foreach($quotesCalc['items_listed'] as $item) {
            $slas = array();

            foreach($quotesCalc['data'] as $quote) {
                if($quote->sku == $item['id']) {
                    array_push($slas, array(
                        'id'                        => $quote->delivery_method_name,
                        'name'                      => $quote->name_provider,
                        'shippingEstimate'          => ((int)$quote->qtd_days + (int) $quote->cross_docking) . "bd",
                        'price'                     => (int) ((float)$quote->shipping_price * 100),
                        'availableDeliveryWindows'  => array()
                    ));
                }
            }

            array_push($quotes, array(
                'itemIndex'         => $item['requestIndex'],
                'stockBalance'      => (int) $item['qty_atual'],
                'quantity'          => $items[(int) $item['requestIndex']]['quantity'],
                'shipsTo'           => array('BRA'),
                'slas'              => $slas,
            ));
        }

        $response = array(
            'items'             => $returnItens,
            'logisticsInfo'     => $quotes,
            'postalCode'        => $data['postalCode'],
            'country'           => $data['country']
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