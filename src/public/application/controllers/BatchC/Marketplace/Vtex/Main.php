<?php

class Main extends BatchBackground_Controller
{
    const TO_INTEGRATE   = 1;
    const INTEGRATED     = 2;
    const NOT_INTEGRATED = 3;
    const TO_NOTIFY      = 4;
    const NOTIFIED       = 5;
    const TO_UPDATE      = 6;
    const UPDATED        = 7;
    const NOT_UPDATED    = 8;
	const INACTIVE       = 9;

    public $result;
    public $responseCode;
    protected $accountName;
    protected $header;
    protected $suffixDns;

    public function __construct()
    {
        parent::__construct();
        $this->suffixDns = '.com.br';
        $this->load->model('model_integrations');
		$this->load->model('model_log_integration_product_marketplace');
		
    }

    protected function setSuffixDns($setSuffixDns) 
    {
        $this->suffixDns = $setSuffixDns;
    }

    protected function process($integrationThe, $endPoint, $method = 'GET', $data = null, $integration_id = null )
    {
    	if (is_null($integration_id))  {
    		$integrationData         = $this->model_integrations->getIntegrationsbyName($integrationThe);
			$separateIntegrationData = json_decode($integrationData[0]['auth_data']);
    	}
		else {
			$integrationData         = $this->model_integrations->getIntegrationsData($integration_id);
			$separateIntegrationData = json_decode($integrationData['auth_data']);
		}
       
	   	if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
	        $this->accountName = $separateIntegrationData->accountName;
	
			if (property_exists($separateIntegrationData,'suffixDns')) {
		        if (!is_null($separateIntegrationData->suffixDns)) {
		            $this->setSuffixDns($separateIntegrationData->suffixDns);
		        }
			}
	
	        $this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
	            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
	        ];

        	$url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;
		}
		else {   // Vertem com linkApi
			
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "Authorization: Basic $separateIntegrationData->apiKey",
	        ];
	        $url = $separateIntegrationData->site .'/'.$endPoint;
			var_dump($url);
			var_dump($this->header);
			
		}

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

		//curl_setopt($ch, CURLOPT_VERBOSE, true);
        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		
        curl_close($ch);
        
        return;
    }

	protected function processNew($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
    	
		if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
	        $this->accountName = $separateIntegrationData->accountName;
	        if (property_exists($separateIntegrationData,'suffixDns')) {
		        if (!is_null($separateIntegrationData->suffixDns)) {
		            $this->setSuffixDns($separateIntegrationData->suffixDns);
		        }
			}
	
	        $this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
	            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
	        ];
	
	        $url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;
		}
		else {  // Vertem com linkApi
			
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "Authorization: Basic $separateIntegrationData->apiKey",
	        ];
	        $url = $separateIntegrationData->site .'/'.$endPoint;
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
		
        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $err        = curl_errno($ch);
	    $errmsg     = curl_error($ch);
	    $header     = curl_getinfo($ch);
        
        curl_close($ch);
		
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
		    echo "Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
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

	protected function processURL($separateIntegrationData, $url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null )
    {
    	
        $this->accountName = $separateIntegrationData->accountName;

        $this->header = [
            'content-type: application/json',
            'accept: application/json',
            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
        ];

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

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processURL($separateIntegrationData, $url, $method, $data);
		}
		
		if ($this->responseCode == 504) {
		    echo "Deu Timeout httpcode=504. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processURL($separateIntegrationData, $url, $method, $data);
		}
         if ($this->responseCode == 503) {
		    echo "Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processURL($separateIntegrationData, $url, $method, $data);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'function' => $function,
				'url' => $url,
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
