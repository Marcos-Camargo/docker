<?php 
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apib2w/freight
 * 
 */
require APPPATH . "controllers/Api/FreteConectala.php";

/**
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 *
 * @property CalculoFrete $calculofrete
 */

class FreteB2W extends FreteConectala {

    private $mkt;

    /**
     * FreteB2W constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'B2W', 'channel' => 'B2W');
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
    public function index_post(): ?Response
    {
        $timeStart = microtime(true);
        $data = json_decode(file_get_contents('php://input'), true);

		if (is_null($data)) {
            return $this->returnError('Dados com formato errado' . print_r(file_get_contents('php://input'), true));
        }

        if (!isset($data['volumes']) || !is_array($data['volumes']) || !count($data['volumes'])) {
            return $this->returnError('Sem o parametro shipping_zip_code. Recebido: ' . print_r($data, true));
        }

        if (!isset($data['destinationZip']) || empty($data['destinationZip'])) {
            return $this->returnError('Sem o parametro destinationZip. Recebido: ' . print_r($data, true));
        }

        $zip = filter_var(preg_replace('/\D/', '', $data['destinationZip']), FILTER_SANITIZE_NUMBER_INT);
        $zip = str_pad($zip, 8, 0, STR_PAD_LEFT);

        $items = array();
        $skusKey  = array();
        foreach ($data['volumes'] as $iten) {
            $skusKey[] = $iten['sku'];
            $items[] = array(
                'sku' => $iten['sku'],
                'qty' => (int)$iten['quantity']
            );
        }

        $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip);

        if (
            !$quote['success'] ||
            !isset($quote['data']['services']) ||
            !count($quote['data']['services'])
        ) {
            return $this->returnError($quote['data']['message'] ?? 'Sem serviço disponível');
        }

        $tempDeadLine   = null;
        $tempValue      = null;
        $tempProvider   = null;
        $tempMethod     = null;
        // Por padrão retornaremos o melhor preço até o módulo frete ficar pronto.
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

        $integrations = ['erp','intelipost','freterapido','sequoia','precode','sgp','sgpweb','vtex','pluggto','anymarket','Anymarket','dressAndGo'];
        if ($quote["success"] == true){
            if (in_array($quote["data"]["logistic"]["type"], $integrations)){
                if ($quote["data"]["logistic"]["sellercenter"] == true){
                    $response_status = 'Sucesso Integração Sellercenter';
                }
                else {
                    $response_status = 'Sucesso Integração Seller';
                }
            }
            else {
                $response_status = 'Sucesso Tabela';
                $quote["data"]["logistic"]["type"] = 'Tabela';
            }
        } else{
            if (in_array($quote["data"]["logistic"]["type"],$integrations)){
                if ($quote["data"]["logistic"]["sellercenter"] == true){
                    $response_status = 'Erro Integração Sellercenter';
                }
                else {
                    $response_status = 'Erro Integração Seller';
                }
            }
            else {
                $response_status = 'Erro Tabela';
                $quote["data"]["logistic"]["type"] = 'Tabela';
            }
        }

        $timeFinish = microtime(true);
        if (ENVIRONMENT !== "development") {
            $this->db->insert('quotes_correios', array(
                'marketplace'   => $this->mkt['channel'],
                'zip'           => $zip,
                'sku'           => json_encode($skusKey),
                'price'         => $tempValue,
                'time'          => $tempDeadLine,
                'service'       => $tempProvider,
                'frete_taxa'    => $tax,
                'response'      => json_encode($response, true),
                'response_time' => ($timeFinish-$timeStart)*1000,
                'error'         => 1 ? $quote["success"] == false : 0, 
                'integration_name' => $quote["data"]["logistic"]["type"],
                'status'        => $response_status,
                'store_id'      => $quote["store_id"] ?? ''
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
        $this->log_data('api', "frete{$this->mkt['channel']}", $message, 'E');

        return $this->response(array('message' => 'Não foi possível realizar a consulta de preço e prazo'), REST_Controller::HTTP_OK);
	}
}