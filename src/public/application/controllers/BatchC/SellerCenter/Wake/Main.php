<?php

class Main extends BatchBackground_Controller
{

    public $result;
    public $responseCode;
    protected $api_url;
    protected $header;
   

    public function __construct()
    {
        parent::__construct();
        $this->suffixDns = '.com.br';
        $this->load->model('model_integrations');
        $this->load->model('model_stores');
		$this->load->model('model_log_integration_product_marketplace');
        $this->load->model('model_settings');
		
    }

    protected function processNew($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
        	
		if (property_exists($separateIntegrationData, 'token')) {
	        $this->api_url = $separateIntegrationData->api_url;

	
            $this->header = [
                'content-type: application/json',
                'accept:application/json',
                'Authorization:Basic '.$separateIntegrationData->token,
            ];
	
	        $url = 'https://'.$this->api_url.'/'.$endPoint;
		}
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		// curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $err        = curl_errno($ch);
	    $errmsg     = curl_error($ch);
       
		curl_close($ch);
    
		if ($err) {
			echo "Houve Erro no curl: ". $errmsg."\n";
		}
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($separateIntegrationData, $endPoint, $method, $data);
		}
		if ($this->responseCode == 504) {
		    echo "Deu Timeout httpcode=504. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($separateIntegrationData, $endPoint, $method, $data);
		}
        if ($this->responseCode == 503) {
		    echo "Wake com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($separateIntegrationData, $endPoint, $method, $data);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}

        return;
    }

}
