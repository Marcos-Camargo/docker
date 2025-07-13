<?php
/*
 
Baixa o link de ofertas dos produtos 

*/   
 class ProductsGetURL extends BatchBackground_Controller {
	
	var $int_to 			= '';
	var $integration_main 	= null;
	var $integration_store	= null;
	var $store				= null;
	var $auth_data			= null;
	var $api_url			= null;
	var $result  			= null;
	var $responseCode		= null;
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' 		=> 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' 	=> 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_integrations');
		$this->load->model('model_log_integration_product_marketplace');
		
	}

	
	// php index.php BatchC/Marketplace/Conectala/ProductsGetURL run null MES
	public function run($id=null,$params=null)
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
		$this->int_to = $params;

		if ($this->getKeys()) {
			$this->getUrl();
		}
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	protected function getKeys()
    {
		if ($this->getIntegration()) {
			$this->auth_data = $this->api_keys = json_decode($this->integration_main['auth_data']);
			$this->api_url = $this->api_keys->api_url;
			return true;
		} 
        return false; 
	}

	protected function getIntegration() 
	{	
		$log_name = __CLASS__.'/'.__FUNCTION__;
		
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $this->int_to);

		if ($this->integration_store)
        {
			if ($this->integration_store['int_type'] == 'DIRECT')
            {
				if ($this->integration_store['int_from'] == 'CONECTALA')
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto("0", $this->int_to);
				elseif ($this->integration_store['int_from'] == 'HUB')
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'], $this->int_to);				 
			}
			else 
            {
				$this->integration_main = $this->integration_store;
			} 
			return true;
		}

		$message = "Não foi possível recuperar os dados de integração";
		echo "$message\n";
		$this->log_data('batch', $log_name, $message, "E");
		return false;
	}

    protected function getUrl()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	

		$sql = "SELECT * FROM prd_to_integration WHERE status=1 AND status_int = 2 AND skumkt IS NOT NULL AND ad_link IS NULL AND int_to= ? ";
      	$query = $this->db->query($sql, array($this->int_to));
		$pis = $query->result_array();
		foreach ($pis as $pi) 
	    {
	    	$sql = "SELECT * FROM products WHERE id = ? AND status=1 AND situacao=2";
      		$query = $this->db->query($sql, array($pi['prd_id']));
			$prd = $pis = $query->row_array();
			if (!$prd) {
				continue;
			}
			
	    	if ($pi['skumkt'] != '') {
				$ad_link  = null; 
	    		echo "Product Id ".$pi['prd_id']." Sku ".$pi['skumkt']."\n";
				$url = $this->api_url.'Products/'.$pi['skumkt'];
      			$return = $this->Http($url, 'GET', NULL);
				if ($this->responseCode === 200) {
					$prod_marketplace = json_decode($this->result,true);
					if (array_key_exists('result', $prod_marketplace)) {
						if (array_key_exists('product', $prod_marketplace['result'])) {
							if (array_key_exists('marketplace_offer_links', $prod_marketplace['result']['product'])) {
								//var_dump($prod_marketplace['result']['product']['marketplace_offer_links']);
								$ad_link = json_encode($prod_marketplace['result']['product']['marketplace_offer_links']);
								
							}
						}
					}
				}
				if (is_null($ad_link)) {
					echo "Ainda sem link de oferta\n";
				}
				else {
					echo $ad_link."\n";
					if ($ad_link != $pi['ad_link']) {
						$prd_upd = array (
							'ad_link'		=> $ad_link
						);
						$this->model_integrations->updatePrdToIntegration($prd_upd,$pi['id']);
					}
				}   		
	    	}
		}
    } 

	private function getHttpHeader($api_keys) 
    {
        if (empty($api_keys))
            return false;
            
        $keys = array();

        foreach ($api_keys as $k => $v)
        {
            if ($k != 'api_url' && $k != 'int_to')
                $keys[] = $k.':'.$v;
        }

        return $keys;        
    }

	protected function Http($url, $method = 'GET', $data = null, $prd_id = null, $int_to = null, $function = null )
    {
        $this->getkeys();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeader($this->api_keys));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE')
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');        

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		if ($this->responseCode == 429) 
        {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->Http($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

		if ($this->responseCode == 504)
        {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->Http($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

        if ($this->responseCode == 503)
        {
		    $this->log("Site com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->Http($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

		if (!is_null($prd_id)) 
        {
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

	protected function log($msg) 
	{
		echo $msg."\n";
	} 

}
?>
