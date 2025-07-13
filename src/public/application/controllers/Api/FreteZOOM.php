<?php 
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apizoom/freight
 * 
 * Frete do Madeira Madeira. 
 */
require APPPATH . "controllers/Api/FreteConectala.php";

class FreteZOOM extends FreteConectala {

    private $mkt;

    /**
     * FreteB2W constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'ZOOM', 'channel' => 'ZOOM');
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

        $response = array(
            'shippingQuotes' => array(
                array(
                	'shippingCost' => (float)$tempValue,
                	'deliveryTime' => array(
                		'expedition' => $quote['data']['crossDocking'],
                        'transit'    => $tempDeadLine - $quote['data']['crossDocking'],
                        'total'      => $tempDeadLine,
                    ),
                    'shippingEstimatedId' => (string)time(),
                    'shippingMethodId' => $tempProvider,
                    'shippingMethodName' => $tempMethod,
                    'shippingMethodDisplayName' => $tempProvider.'-'.$tempMethod,
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
        $this->db->insert('quotes_correios', array(
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
		ob_clean();
		echo $message;
        return $this->response(NULL, REST_Controller::HTTP_NOT_FOUND);
	}

}