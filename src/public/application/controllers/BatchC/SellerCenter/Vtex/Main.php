<?php

/**
 * @property Model_integrations $model_integrations
 * @property Model_log_integration_product_marketplace $model_log_integration_product_marketplace
 * @property Model_whitelist $model_whitelist
 * @property Model_blacklist_words $model_blacklist_words
 * @property BlacklistOfWords $blacklistofwords
 */
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
		$this->load->model('model_whitelist');
		$this->load->model('model_blacklist_words');
        $this->load->library('BlacklistOfWords');

    }

    protected function setSuffixDns($setSuffixDns) 
    {
        $this->suffixDns = $setSuffixDns;
    }

    protected function process($integrationThe, $endPoint, $method = 'GET', $data = null, $integration_id = null )
    {
    	if (is_null($integration_id))  {
    		$integrationData         = $this->model_integrations->getIntegrationsbyName($integrationThe);

			if(!empty($integrationData) && isset($integrationData[0]['auth_data'])) {
				$separateIntegrationData = json_decode($integrationData[0]['auth_data']);
			} else {
				throw new InvalidArgumentException('Nenhuma integração encontrada com o nome: ' . $integrationThe);
			}
    	}
		else {
			$integrationData         = $this->model_integrations->getIntegrationsData($integration_id);

			if(!empty($integrationData) && isset($integrationData['auth_data'])) {
				$separateIntegrationData = json_decode($integrationData['auth_data']);
			} else {
				throw new InvalidArgumentException('Nenhuma integração encontrada com o ID: ' . $integration_id);
			}

			
		}

		if (!is_object($separateIntegrationData)) {
			throw new InvalidArgumentException('Nenhum objeto encontrado.');
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

			 // Se não chegar o endpoint completo, monto aqui.
            if (!preg_match('/https:/', $endPoint)) {

				// Remover a barra inicial, se existir
				$endPoint = ltrim($endPoint, '/');

                $url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;
            } else {
                $url = $endPoint;
            }
		}
		else {   // Vertem com linkApi
			
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json'
	        ];
			if (substr($endPoint,0,1) == '/') {
				$endPoint = substr($endPoint,1);
			}
			if ((strpos($endPoint, "?")) === false) {
				$url = $separateIntegrationData->site .'/'.$endPoint.'?apiKey='.$separateIntegrationData->apiKey;
			}
			else {
				if (!is_object($separateIntegrationData) || !isset($separateIntegrationData->site) || !isset($separateIntegrationData->apiKey)) {
					throw new InvalidArgumentException('Dados de integração inválidos: site ou apiKey ausente.');
				}
				else {
					$url = $separateIntegrationData->site . '/' . $endPoint . '&apiKey=' . $separateIntegrationData->apiKey;
				}
			}
	        
			
			// var_dump($url);
			// var_dump($this->header);			
		}
		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

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
		
        $err        = curl_errno($ch);
	    $errmsg     = curl_error($ch);
        
        curl_close($ch);

		if ($err) {
			echo "Houve Erro no curl: ". $errmsg."\n";
		}
        
        return;
    }

	protected function processNew($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
		if (!is_object($separateIntegrationData)) {
			throw new InvalidArgumentException('Nenhum objeto encontrado.');
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
		else {  // Vertem com linkApi
			
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	          
	        ];
			$url = $separateIntegrationData->site .'/'.$endPoint.'?apiKey='.$separateIntegrationData->apiKey;
	        //$url = $separateIntegrationData->site .'/'.$endPoint;
			var_dump($url);
			var_dump($this->header);
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
        
		curl_close($ch);

		if ($err) {
			echo "Houve Erro no curl: ". $errmsg."\n";
		}
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições já enviadas httpcode=429. Nova tentativa em 5 segundos.\n";
            sleep(5);
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

        $err        = curl_errno($ch);
	    $errmsg     = curl_error($ch);
        
        curl_close($ch);

		if ($err) {
			echo "Houve Erro no curl: ". $errmsg."\n";
		}
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições já enviadas httpcode=429. Nova tentativa em 5 segundos.\n";
            sleep(5);
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


    public function checkBlackList(array $prd, string $int_to)
    {
        $productCheckBlackWhiteList = array_merge($prd, ['marketplace' => $int_to]);
        // consultar white/black list
        $whiteList = $this->model_whitelist->searchWhitelist($this->blacklistofwords->getProductForCheck($productCheckBlackWhiteList));
        $blackList = $this->model_blacklist_words->getDataBlackListActive($this->blacklistofwords->getProductForCheck($productCheckBlackWhiteList));

        // consultar se produto deve ser bloqueado
        if ($blackList) {
            $hasLockByMarketplace = $this->blacklistofwords->getBlockProduct($prd, $prd['id'], $whiteList, $blackList, true);
        } else {
            $hasLockByMarketplace['blocked'] = false;
        }

        if ($hasLockByMarketplace['blocked']) {
            $ruleBlockPrdInt = array();
            if (!isset($hasLockByMarketplace['data_row'])) {
                $hasLockByMarketplace['data_row'] = [];
            }
            if (!is_array($hasLockByMarketplace['data_row'])) {
                $hasLockByMarketplace['data_row'] = (array)$hasLockByMarketplace['data_row'];
            }
            foreach ($hasLockByMarketplace['data_row'] as $rulesBlock) {
                $ruleBlockPrdInt[] = $rulesBlock['blacklist_id'];
            }
            $ruleBlockPrdInt = json_encode($ruleBlockPrdInt);
        } else {
            $ruleBlockPrdInt = null;
        }

        $this->model_integrations->updatePrdToIntegrationByPrdId(array('rule' => $ruleBlockPrdInt), $prd['id'], $int_to);

        return $ruleBlockPrdInt ? 0 : 1;
    }
}
