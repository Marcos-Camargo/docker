<?php
/*
 * Verifica os produtos que estão com status_int 22 para ver se já foram aceitos na VTEX
 * 
 * */

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class ProductsStatusV2 extends Main
{

	var $auth_data;
	var $int_to;
	var $sellerId;
	
    public function __construct()
	{
		parent::__construct();
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
		$this->load->model('model_queue_products_marketplace');

    }

	// php index.php BatchC/SellerCenter/Vtex/ProductsStatusV2 run null CasaeVideo
	function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return;
		}
		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
		
		 /* faz o que o job precisa fazer */
		 if(is_null($params)  || ($params == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
			if($integration){
				$this->int_to = $integration['int_to'];
				echo 'Verificando produtos com status_int 22 de '. $integration['int_to']."\n";
				$this->productStatus($integration);
            }
			else {
				echo PHP_EOL .$params." não tem integração definida". PHP_EOL;
			}
		}
		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}
    
    public function productStatus($main_integrations) {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->auth_data = json_decode($main_integrations['auth_data']);

		$offset = 0;
		$limit = 10000; 
		$exists = true; 
		$store_id = 0; 
		while ($exists) {
			$prd_to_integrations = $this->model_integrations->getProductsToCheckMarketplace($this->int_to, $offset, $limit);
			if (count($prd_to_integrations)==0) {
				echo "Encerrou \n";
				$exists = false;
				break;
			}
			$offset += $limit; 
			foreach($prd_to_integrations as $prd_to_integration) {
				if($prd_to_integration['store_id'] != $store_id) { // verifico se mudei de loja 
					$store_id = $prd_to_integration['store_id']; 
					$integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($store_id,$this->int_to);
					$auth_data_store = json_decode($integration_store['auth_data']);
	    			$this->sellerId = $auth_data_store->seller_id;
				}
				
				$skumkt = $prd_to_integration['skumkt'];
				// Verifico de o produto existe na Vtext
				// https://help.vtex.com/en/tutorial/integration-guide-for-marketplaces-seller-non-vtex-with-payment--bNY99qbQ7mKsSMMuq2m4g
				$bodyParams = json_encode(array());
		        $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$this->sellerId .'/'.$skumkt;
		        
		        echo "Verificando se o produto ".$prd_to_integration['prd_id']." sku ".$skumkt." existe no marketplace ".$this->int_to." para o seller ".$this->sellerId ."\n";
		        $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $prd_to_integration['prd_id'], $this->int_to, 'Notificação de Mudança');
				
				if ($this->responseCode == 500){
					echo " httpcode 500. Possivelmente Timeout, esperando 1 minuto e tenta de novo \n";
					echo  'Erro '.print_r($this->result,true)."\n";
					sleep(60);
					echo "Verificando se o produto ".$prd_to_integration['prd_id']." sku ".$skumkt." existe no marketplace ".$this->int_to." para o seller ".$this->sellerId ."\n";
					$this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $prd_to_integration['prd_id'], $this->int_to, 'Notificação de Mudança');					
				}

				if (($this->responseCode == 200) || ($this->responseCode == 204)) {
					echo " Produto ".$prd_to_integration['prd_id']." aprovado.\n";
					// existe ! Então coloco na fila para o update do produto marketplace 
					$data = array(
						'id' 		=> 0,
						'status' 	=> 0,
						'prd_id' 	=> $prd_to_integration['prd_id'],
						'int_to' 	=> $this->int_to, 
					);
					$this->model_queue_products_marketplace->create($data);
					continue; 
				}
				if ($this->responseCode !== 404) { // O normal é dar 404, então podemos cadastrar o produto
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					return;
				}
				// ainda não criou 
				echo " Produto ".$prd_to_integration['prd_id']." ainda não aprovado.\n";
				echo " ".$this->dateDifference($prd_to_integration['date_last_int'],date('Y-m-d H:i:s'))."\n";
				if ($this->dateDifference($prd_to_integration['date_last_int'],date('Y-m-d H:i:s'))>=2) {
					// Já está a muito tempo para ser aprovado, então faço uma nova sugestão
					$data = array(
						'id' 		=> 0,
						'status' 	=> 0,
						'prd_id' 	=> $prd_to_integration['prd_id'],
						'int_to' 	=> $this->int_to, 
					);
					$this->model_queue_products_marketplace->create($data);
				}			
			}
		}
    }

    function dateDifference($date_1 , $date_2 , $differenceFormat = '%d' )
	{
	    $datetime1 = date_create($date_1);
	    $datetime2 = date_create($date_2);
	   
	    $interval = date_diff($datetime1, $datetime2);
	   
	    return $interval->format($differenceFormat);
	}
	
}
