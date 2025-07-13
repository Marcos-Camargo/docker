<?php
/* Método de chamada redefinido no config/routes.php
 *
 * url_site/Apivia/freight
 *
 */
 
require APPPATH . "controllers/Api/FreteConectala.php";

class FreteVIA extends FreteConectala {
    
	private $mkt;

    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'VIA', 'channel' => 'VIA');
    }
    
    /**
     * Get All Data from this method.
     *
     * Example request
     *
     * /Apivia/freight?skuId=Q_VIA_1,1|Q_VIA_2,2&zipCode=88010000
     *
     * sku = { [SKU_1] , [QTY_1] | [SKU_2] , [QTY_2] }
     *
     * @return Response
    */
	public function index_get($id = NULL)
	{
        $timeStart = microtime(true);
        $data = $this->input->get();
		
		if (!array_key_exists ('zipCode', $data))
            return $this->returnError('Sem o parametro zipCode. Recebido: '.print_r($data,true));

		if (!array_key_exists ('skuId', $data))
            return $this->returnError('Sem o parametro skuId. Recebido: '.print_r($data,true));

        $zip = filter_var(preg_replace('/\D/', '', $data['zipCode']), FILTER_SANITIZE_NUMBER_INT);
        $zip = str_pad($zip, 8, 0, STR_PAD_LEFT);

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

        $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip);

        if (
            !$quote['success'] ||
            !isset($quote['data']['services']) ||
            !count($quote['data']['services'])
        ) {
            return $this->returnError($quote['data']['message']);
        }

        $tempDeadLine   = null;
        $tempValue      = null;
        $tempProvider   = null;
        // por default iremos retornar o melhor preço até o módulo frete ficar pronto.
        foreach ($quote['data']['services'] as $service) {
            $price      = $service['value'];
            $deadline   = $service['deadline'];
            $changeData = false;

            if ($deadline === null || $price === null) {
                continue;
            }

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
            }
        }

        $tax = 0;
        /*
        if ($tempValue != 0 && $quote['data']['logistic']['sellercenter']) { // Para Frete gratis não adicionamos taxa
            $tax = $this->calculofrete->calculaTaxa($quote['data']['totalPrice']);
            $tempValue += $tax;
        }
        */

        // format response
        $freights = array();
        if ($tempValue !== null && $tempDeadLine !== null) {
            foreach ($items as $iten) {
                array_push($freights, array(
                    'skuIdOrigin' => $iten['sku'],
                    'quantity' => $iten['qty'],
                    'freightAmount' => $tempValue,
                    'deliveryTime' => $tempDeadLine,
                    'freightType' => 'NORMAL'
                ));
            }
        }

        $response = array(
            'freights'              => $freights,
            'freightAdditionalInfo' => $tempProvider,
            'sellerMpToken'         => '8hxJlXvVp3QO'
        );

//        $json_data = json_encode($response,JSON_UNESCAPED_UNICODE);
//        $json_data = stripslashes($json_data);
//        ob_clean();
//        header('Content-type: application/json');
//        echo $json_data;
//        $this->response(NULL, REST_Controller::HTTP_OK);

        $timeFinish = microtime(true);
        $this->db->insert('quotes_correios', array(
            'marketplace'   => $this->mkt['channel'],
            'zip'           => $zip,
            'sku'           => json_encode($skusKey),
            'price'         => $tempValue ?? 0,
            'time'          => $tempDeadLine ?? 0,
            'service'       => $tempProvider ?? '',
            'frete_taxa'    => $tax,
            'response'      => json_encode($freights, true),
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

        return $this->response(array('freights' => Array()), REST_Controller::HTTP_OK);
    }

}