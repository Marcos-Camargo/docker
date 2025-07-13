<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class CheckCommercialCondition extends Main
{
	var $auth_data = null; 
	var $accountName = null;
	var $sent = array();

	public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' 		=> 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
 
		$this->load->model('model_products');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_queue_products_marketplace');
		
    }

    // php index.php BatchC/SellerCenter/Vtex/CheckCommercialCondition/run/null/Decathlon
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id); 
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$retorno = $this->getCondition($params);
	
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	public function getCondition($int_to) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$vtex_commercial_condition_id = $this->model_settings->getValueIfAtiveByName('vtex_commercial_condition_id');

		if (!$vtex_commercial_condition_id) {
			echo "parametro ".$vtex_commercial_condition_id." não definido ou inativo\n" ;
			return;
		}

		$this->getAuth($int_to);

		// echo "Buscando produtos com mkt_sku_id no ".$int_to." \n";
		$offset = 0; 
		$limit = 5000; 
		while (true) {
			$prods = $this->getPublishProductsVtex($int_to, $offset, $limit);
			if (!$prods) {
				echo "Acabou \n";
				break;
			}
			foreach($prods as $prod) {				
				$sku = $this->getVtexSku($prod['mkt_sku_id']);
				if ($sku['CommercialConditionId'] != $vtex_commercial_condition_id) {
					if (!in_array($prod['prd_id'],$this->sent)) {
						echo "Poduto precisando alterar ".$prod['prd_id']." ".$prod['mkt_sku_id']." Cond=".$sku['CommercialConditionId']."\n";
						$data = array ( 
							'status' => 0,
							'prd_id' => $prod['prd_id'],
							'int_to' => $int_to,
						);
						$this->model_queue_products_marketplace->create($data);
						$notice = "Produto colocado na fila ".$prod['prd_id']." sku: ".$prod['mkt_sku_id'];
						echo $notice."\n";
						$this->sent[] = $prod['prd_id'];

						$sku['CommercialConditionId'] = $vtex_commercial_condition_id;
						$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prod['mkt_sku_id'];
						$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($sku));
						$sku_vtex  = json_decode($this->result);
						var_dump($sku_vtex);
					}
					
				}
			}
			$offset += $limit; 
		}

	}

	private function getVtexSku($mkt_sku_id) {
		// Agora posso pegar o SKU de verdade  
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$mkt_sku_id;
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET');
		if ($this->responseCode == 429) {
			echo "Deu 429 \n";
			sleep(10);
			return $this->getVtexSku($mkt_sku_id);
		}
		if ($this->responseCode != 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		return json_decode($this->result, true);		
	}

	private function getPublishProductsVtex($int_to, $offset = 0, $limit = 20)
    {

        //$sql = "SELECT * FROM prd_to_integration WHERE status=1 AND status_int = 2 AND skumkt IS NOT null AND mkt_sku_id IS NOT NULL AND int_to=? ORDER BY prd_id DESC";
		$sql = "SELECT * FROM prd_to_integration WHERE status=1 AND skumkt IS NOT null AND mkt_sku_id IS NOT NULL AND int_to=? ORDER BY prd_id DESC";
		$sql = "SELECT * FROM prd_to_integration WHERE mkt_sku_id IS NOT NULL AND int_to=? ORDER BY prd_id DESC";
        $sql .= " LIMIT " . $limit . "  OFFSET " . $offset;
        $query = $this->db->query($sql, array($int_to));
        return $query->result_array();
    }

	private function getAuth($int_to) {

		$integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto("0",$int_to);
		$this->auth_data = json_decode($integration_main['auth_data']);
		$this->accountName = $this->auth_data->accountName;

	}
	
	protected function vtexHttp($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null, $cnt429=0 )
    {
        $this->accountName = $separateIntegrationData->accountName;
		if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
	        $this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
	            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
	        ];
	        if (isset($separateIntegrationData->suffixDns)) {
	            if (!is_null($separateIntegrationData->suffixDns)) {
		            $this->setSuffixDns($separateIntegrationData->suffixDns);
		        }
	        } 
			
	        $url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;
		} else {   // LinkApi
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	           // "apiKey: $separateIntegrationData->apiKey",
	        ];
	        $url = $separateIntegrationData->site .'/'.$endPoint;
			$url .= "?apiKey=".$separateIntegrationData->apiKey;
		}
		// echo "------------------- Chamando: ".$url."\n";
		
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
		
		if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if($errno = curl_errno($ch)) {
			$error_message = curl_strerror($errno);
			echo "cURL error ({$errno}):\n {$error_message}\n";
		}
        curl_close($ch);
		
		if ($this->responseCode == 429) {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 10 segundos.");
            sleep(10);
			if ($cnt429 >= 2) {
				$this->log("3 requisições já enviadas httpcode=429.Desistindo e mantendo na fila.");
				die;
			}
			$cnt429++;
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $prd_id, $int_to, $function, $cnt429 );
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $prd_id, $int_to, $function, 0);
		}
		if ($this->responseCode == 500) {
		    $this->log("Deu httpcode=500. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $prd_id, $int_to, $function, 0);
		}
        if ($this->responseCode == 503) {
		    $this->log("Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $prd_id, $int_to, $function, 0);
		}

        return;
    }
	
	private function log($log) {
		echo $log."\n";
	}
}