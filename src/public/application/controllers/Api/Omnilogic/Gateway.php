<?php

require APPPATH . "controllers/Api/V1/API.php";
require APPPATH . "libraries/Omnilogic.php";

class Gateway extends API
{
    private $id;
    private $routes;

    public function __construct() {
        parent::__construct();
        
        $this->load->model('model_omnilogic');
        $this->load->model('model_omnilogic_gateway');

        $this->routes = $this->model_omnilogic_gateway->getList();
    }

    private function explodeId($id)
    {
        return explode('_', $id);
    }

    public function verifyInit($validateStoreProvider = true)
    {
        // Verifica se foram enviados todos os headers
        $headers = $this->verifyHeader(getallheaders());

        // Não foram enviado todos os headers
        if(!$headers[0]){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,"Not all headers were sent. Missing header: {$headers[1]}","W");
            return array(false, array('success' => false, 'message' => "Not all headers were sent. Missing header: {$headers[1]}"));
        }

        $headers = $headers[1];

        // Decodifica token
        $decodeKeyAPI = $this->decodeKeyAPI($headers['x-api-key']);

        // Não possível decodificar a key_api
        if(is_array($decodeKeyAPI)) return array(false, $decodeKeyAPI);

        return array(true, $headers);
    }

    public function verifyHeader($header_request, $validateStoreProvider = true)
    {
        $headers = array();

        // Headers obrigatórios para requisição
        $headers_valid = array(
            "x-api-key",
            "accept",
            "content-type",
        );

        // headers recuperado na solicitação
        foreach ($header_request as $header => $value)
            $headers[strtolower($header)] = $value;

        // Verifica se não foram enviados todos os headers
        foreach ($headers_valid as $header_valid)
            if(!array_key_exists($header_valid , $headers)) return array(false, $header_valid);

        return array(true, $headers);
    }

    private function getUrlRedirect($prefix) 
    {
        $result = null;

        $records = $this->routes;
        foreach ($records as $record) {
            if ($prefix == $record['prefix']) {
                $result = array($record['host'], $record['api_key'], $record['prefix']);
            }
        }

        return $result;
    }

    private function canSendChannel($prefix, $channel) {
        $canSend = false;
        foreach ($this->routes as $route) {
            if ($prefix == $route['prefix']) {
                foreach (json_decode($route['channels']) as $c) {
                    if (strtoupper($channel) == strtoupper($c)) {
                        $canSend = true;
                    }
                }
            }
        }
        return $canSend;
    }

    public function index_get($id = null)
    {
        // if (!$this->app_authorized)
        //     return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);
        $log = array(
            'origin_api' => 'gateway',
            'method' => 'GET',
            'offer_id' => $id
        );
        $this->model_omnilogic->save_log($log);

        if(!$id){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, 'Id code not informed',"W");
            return $this->response($this->returnError(array('error' => true, 'data' => 'Id code not informed')), REST_Controller::HTTP_NOT_FOUND);
        }
        
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $headers = $verifyInit[1];

        $arr = $this->explodeId($id);
        
        // Verifica se o id informado está em branco
        $result = !(empty($arr[1]));

        if ($result !== false) 
        {
            $redirect = $this->getUrlRedirect($arr[0]);

            $url = $redirect[0] . '/app/Api/V1/Omnilogic/'. $id;
            $token = $redirect[1];
            $header = [
                'x-api-key: ' .$token,
                'accept: application/json', 
                'content-type: application/json'
            ];

            $response = $this->getRequest($url, $header);
        }

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->returnError()['message'] . " - sku: {$this->id}","W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $json_data = json_encode($response['content'],JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);
		ob_clean();
		header('Content-type: application/json');
		echo $response['content'];
        // $this->response($json_data, REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
        $body = file_get_contents('php://input');

        $log = array(
            'origin_api' => 'gateway',
            'method' => 'POST',
            'json' => $body
        );
        $this->model_omnilogic->save_log($log);

//        if (!$this->app_authorized)
//            return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        // Recupera dados enviado pelo body
        $offer = json_decode($body, true);

        $arr = $this->explodeId($offer['seller_offer_id']);

        if ($this->canSendChannel($arr[0], $offer['channel']) === false) {
            return $this->response(array('success' => true, "message" => "Offer successfully inserted"), REST_Controller::HTTP_CREATED);
        }

        $redirect = $this->getUrlRedirect($arr[0]);

        $url = $redirect[0] . '/app/Api/V1/Omnilogic';
        $token = $redirect[1];
        $header = [
            'x-api-key: ' .$token,
            'accept: application/json', 
            'content-type: application/json'
        ];

        $response = $this->postRequest($url, $header, $offer);

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($data),"W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, "message" => "Offer successfully inserted"), REST_Controller::HTTP_CREATED);
    }

    private function getRequest($url, $headers) {
        $options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_HTTPHEADER  => (array)$headers
	    );
        
        $ch         = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );
        
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
        
        return $header;
    }

    private function postRequest($url, $headers, $payload){
        $post_data = json_encode($payload);

		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER  => $headers
	    );
        
        $ch         = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );
        
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
        
        return $header;
	}
}