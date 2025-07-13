<?php
/*
 * Esta classe é abstrata e cada Marketplace Vtex deve redefinir run e o int_to
 * Verifica os produtos que estão com status_int 22 para ver se já foram aceitos na VTEX
 * 
 * */

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class ProductsGetURL extends Main
{

	var $int_to 			= '';
	var $auth_data			= null;
    var $accountName 		= null;
	var $integration_main 	= null;
	var $integration_store	= null;
    var $url_marketplace    = null;
	
    public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

		$logged_in_sess = array(
			'id'        => 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp'  => 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
        $this->load->model('model_integrations');
		$this->load->model('model_log_integration_product_marketplace');        
		$this->load->model('model_integrations_settings');        
    }

    // php index.php BatchC/SellerCenter/Vtex/ProductsGetURL run null Mesbla
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
			$this->auth_data = json_decode($this->integration_main['auth_data']);
			$urlMarketplace = $this->model_integrations_settings->getIntegrationSettingsbyId($this->integration_main["id"]);
			if (isset($urlMarketplace)) {
				$this->url_marketplace = $urlMarketplace['adlink'];
			}
			if (is_null($this->url_marketplace)) {
				echo "Sem o parametro url_marketplace no integrations neste marketplace \n";
			    return false;
			}
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

		$sql = "SELECT * FROM prd_to_integration WHERE status=1 AND status_int = 2 AND mkt_product_id is not null AND skumkt IS NOT NULL AND ad_link IS NULL AND int_to= ? ";
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

	    		echo "Product Id ".$pi['prd_id']." Sku ".$pi['skumkt']."  mkt_product_id ".$pi['mkt_product_id']."\n";
                
                $endPoint   = 'api/catalog/pvt/product/'.$pi['mkt_product_id'];
                $skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET');
                if ($this->responseCode != 200) { 
                    continue;
                }
                $prod_marketplace = json_decode($this->result,true);

                $ad_link = (is_null($this->url_marketplace)) ? null : $this->url_marketplace . $prod_marketplace["LinkId"]."/p";
       
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

    protected function vtexHttp($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
        $this->accountName = $separateIntegrationData->accountName;

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

        curl_close($ch);
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
		}
		if ($this->responseCode == 504) {
		    echo "Deu Timeout httpcode=504. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
		}
        if ($this->responseCode == 503) {
		    echo "Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to'    => $int_to,
				'prd_id'    => $prd_id,
				'url'       => $url,
				'function'  => $function,
				'method'    => $method,
				'sent'      => $data,
				'response'  => $this->result,
				'httpcode'  => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
        return is_null($this->result) ? null : $this->result;
    }
	
}
