<?php

class Main extends BatchBackground_Controller
{
    public $site;
    public $token;
	public $id_seller;
	public $integration_store;
	public $integration_main;
	public $int_to;
	public $store_id;
	public $responseCode; 
	public $result;
	
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_integrations');
		$this->load->model('model_log_integration_product_marketplace');
    }

	function getIntegration() 
	{
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store_id,$this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto("0",$this->int_to);
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
		$auth = json_decode($this->integration_main['auth_data']);
		$this->site = $auth->site;
		$this->token = $auth->token;
		$this->id_seller = $auth->id_seller;
	}

	protected function processURL($url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null )
    {
    	$url = $this->site . $url; 
        $this->header = [
            'content-type: application/json',
            'accept: application/json',
            "TOKENMM: $this->token",
        ];
		if (is_array($data)) {
			$data = json_encode($data, JSON_UNESCAPED_UNICODE);
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

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.\n";
            sleep(60);
			return $this->processURL($url, $method, $data, $prd_id, $int_to, $function);
		}
		
		if ($this->responseCode == 504) {
		    echo "Deu Timeout httpcode=504. Nova tentativa em 60 segundos.\n";
            sleep(60);
			return $this->processURL($url, $method, $data, $prd_id, $int_to, $function);
		}
         if ($this->responseCode == 503) {
		    echo "Site com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
            sleep(60);
			return $this->processURL($url, $method, $data, $prd_id, $int_to, $function);
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
