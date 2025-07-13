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
        $this->load->model('model_stores');
		$this->load->model('model_log_integration_product_marketplace');
        $this->load->model('model_settings');
		
    }

    protected function setSuffixDns($setSuffixDns) 
    {
        $this->suffixDns = $setSuffixDns;
    }

    protected function auth( $endPoint, $authToken )
    {
  	
	    $this->header = [
	        'content-type: application/x-www-form-urlencoded',
	        'Authorization: Bearer '.$authToken,
	    ];

        $url = 'https://'.$endPoint.'/ccadmin/v1/login?grant_type=client_credentials';
		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);

		//curl_setopt($ch, CURLOPT_VERBOSE, true);
        $result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		
        curl_close($ch);
        $result = json_decode($result);
        return $result->access_token;
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
       
	   	
        //$this->accountName = $separateIntegrationData->accountName;
        $credentials = $this->auth( $separateIntegrationData->site, $separateIntegrationData->apikey );

        $this->header = [
            'content-type: application/json; charset=UTF-8',
            'Authorization: Bearer '.$credentials,
            'X-CCAsset-Language: pt-BR'
        ];

        $url = 'https://'.$separateIntegrationData->site.$endPoint;

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
		
        curl_close($ch);
        if (($this->responseCode == 429) || ($this->responseCode == 504)) {
            echo "site ".$url." deu ".$this->responseCode." dormindo 50 segundos\n";
            sleep(60);
            return $this->process($integrationThe, $endPoint, $method , $data , $integration_id);
        }
        return;
    }

    public function getOldAndNewStores($int_to) {
        $all_stores = array();
        $integrations = $this->model_integrations->getIntegrationsByIntTo($int_to);

        // pego todas as lojas ativas e inativas que já tiveram integração com o OCC
        foreach($integrations as $integration) {
            if ($integration['store_id'] != 0) {
                $all_stores[] = $integration['store_id'];
            }
        }

        // Junto com as lojas novas
        $stores = $this->model_stores->getAllActiveStore(); 
        foreach ($stores as $store) {
            if ((!in_array($store['id'], $all_stores))  && ($store['type_store'] != 2)) {
                $all_stores[] = $store['id'];
            }
        }
        return $all_stores; 
    }

    public function checkAttributeValues($arr_stores, $att_values) {
        $values = json_decode($att_values, true);    
        foreach($arr_stores as $store) {
            $found = false;
            foreach ($values as $value) {
                if ( (string)$store === (string)$value['Value']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo " Não achei todas as lojas \n";
                return false; 
            }
        }
        echo " Achei todas lojas \n";
        return true;
    }

    public function updateSellerIdOCC($int_to, $verify_values_attribute = false)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        echo "verificando os sellersId nas categorias\n";
		$id_atributo = $this->model_settings->getValueIfAtiveByName('occ_seller_field_'.strtolower($int_to));
		if(!$id_atributo){
			echo "Parametro occ_seller_field_".strtolower($int_to)." não criado ou inativo\n"; 
			return false;
		}

        $arr_stores = $this->getOldAndNewStores($int_to);
		$all_categories =  $this->model_atributos_categorias_marketplaces->getAttributesByAttrIdMkt($id_atributo, $int_to); 
		foreach ($all_categories as $category) {
            $update = true; 
            if ($verify_values_attribute) {
                echo "Verificando em ".$category['id_categoria']."\n";
                $update = !$this->checkAttributeValues( $arr_stores, $category['valor']);
            }
            if ($update) {
                echo "Alterando em ".$category['id_categoria']."\n";
                $data = array (
                    'id'			=> $id_atributo,
                    'productTypeId' => $category['id_categoria'],
                    'values'		=> $arr_stores,
                );
            
                $endPoint = '/ccadminui/v1/productVariants/'.$id_atributo;
                $this->process($int_to, $endPoint, 'PUT', json_encode($data));

                if ($this->responseCode !== 200) {
                    $erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' ENVIADO: '.print_r(json_encode($data),true).' RESPOSTA '.print_r($this->result,true); 
                    echo $erro."\n";
                    $this->log_data('batch',$log_name, $erro ,"E");
                    return false;
                }
            }
		}

		return true;
	}

}
