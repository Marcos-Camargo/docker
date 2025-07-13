<?php
/*
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Apiitem extends Admin_Controller  
{
*/   
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apiml/freight
 * 
 */
 
require APPPATH . "controllers/Api/FreteConectala.php";

class FreteML extends FreteConectala {

    private $mkt;
    private $itemsResponse = array();

    /**
     * FreteML constructor.
     */
    public function __construct() {
       parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'ML', 'channel' => 'ML');
    }

    public function index_get()
	{
	 	echo "morri\n";
	 	die;
	}

    /**
     * Post All Data from this method.
     *
     * Example request
     *
     * https://developers.mercadolivre.com.br/pt_br/frete-dinamico
     *
     * {
     *   "destination": "88010000",
     *   "items": [
     *     {
     *       "seller_id": "555",
     *       "sku": "Q_ML_2",
     *       "quantity": 1,
     *       "origin": "88010000",
     *       "price": 316,
     *       "dimensions": {
     *         "length": 1,
     *         "height": 1,
     *         "width": 1,
     *         "weight": 1
     *       }
     *     }
     *   ]
     * }
     *
     * @return Response
     */
    public function index_post()
    {
        $timeStart = microtime(true);
		$dataPost = file_get_contents('php://input');
		$data = json_decode($dataPost,true);

		if (is_null($data))
            return $this->returnError('Dados com formato errado recebidos do Mercado livre='.print_r($dataPost,true));

        if (!isset($data['items']) || !is_array($data['items']) || !count($data['items']))
            return $this->returnError('Sem o parametro shipping_zip_code. Recebido: '.print_r($data,true));

        if (!isset($data['destination']) || empty($data['destination']))
            return $this->returnError('Sem o parametro destinationZip. Recebido: '.print_r($data,true));

        $zip = filter_var(preg_replace('/\D/', '', $data['destination']), FILTER_SANITIZE_NUMBER_INT);
        $zip = str_pad($zip, 8, 0, STR_PAD_LEFT);

        $items   = array();
        $skusKey = array();
        foreach ($data['items'] as $iten) {
            array_push($skusKey, $iten['sku']);
            array_push($items, array(
                'sku' => $iten['sku'],
                'qty' => (int)$iten['quantity']
            ));

            array_push($this->itemsResponse, array(
                'sku'           => $iten['sku'],
                'seller_id'     => $iten['seller_id'],
                'quantity'      => $iten['quantity'],
                'stock'         => 0,
                'error_code'    => 0,
            ));
        }

        $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip);

        if (!$quote['success'])
            return $this->returnError($quote['data']['message']);

        $tempDeadLine   = null;
        $tempValue      = null;
        $tempProvider   = null;
        // por default iremos retornar o melhor preço até o módulo frete ficar pronto.
        foreach ($quote['data']['services'] as $service) {
            $price      = $service['value'];
            $deadline   = $service['deadline'];
            $changeData = false;

            if ($tempValue === null ||
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

        foreach ($data['items'] as $key => $iten)
            $this->itemsResponse[$key]['stock'] = (int)$quote['data']['skus'][$iten['sku']]['current_qty'];

        $quotations = array();
        if ($tempValue !== null && $tempDeadLine !== null)
            $quotations = array(
                array(
                    'cost'          => $tempValue,
                    'price'         => $tempValue,
                    'handling_time' => 0,
                    'shipping_time' => $quote['data']['crossDocking'],
                    'promise'       => $tempDeadLine,
                    'caption'       => 'Normal',
                    'service_id'    => 1, // É o código que identifica um serviço/transportadora dentro do contexto do seller. Valores válidos vão de 0 à 99. Esse código é única e exclusivamente de responsabilidade do seller/integrador, sendo responsabilidade do Mercado Livre apenas repassar este valor.
                )
            );

        $response = array(
            'packages' => array(
                'items'      => $this->itemsResponse,
                'quotations' => $quotations
            )
        );

//        $json_data = json_encode($response,JSON_UNESCAPED_UNICODE);
//        $json_data = stripslashes($json_data);
//        ob_clean();
//        header('Content-type: application/json');
//        echo $json_data;
//        $this->response(REST_Controller::HTTP_OK);

        $timeFinish = microtime(true);
        $this->db->insert('quotes_correios', array(
            'marketplace'   => $this->mkt['channel'],
            'zip'           => $zip,
            'sku'           => json_encode($skusKey),
            'price'         => $tempValue ?? 0,
            'time'          => $tempDeadLine ?? 0,
            'service'       => $tempProvider ?? '',
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
		foreach($this->itemsResponse as $key => $itemRet ) {
			foreach ($itemRet as $keyItem => $value ) {				
				if (($keyItem == 'error_code') && ($value == 0)) {
					$this->itemsResponse[$key]['error_code'] = 3;
					$this->itemsResponse[$key]['stock'] = -1;
				}
			}
		}
		$response = array(
            'packages' => array (
                'items' => $this->itemsResponse,
                'quotations' => array()
            )
        );

        $this->log_data('api', "frete{$this->mkt['channel']}", $message, 'E');

        return $this->response($response, REST_Controller::HTTP_OK);
		
		$json_data = json_encode($response,JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);
		//ob_clean();
		header('Content-type: application/json');
		echo $json_data;
        $this->response(REST_Controller::HTTP_OK);
		$this->log_data('api', 'FreteML Consulta Frete', $msg, $type);
		die;
	}

}