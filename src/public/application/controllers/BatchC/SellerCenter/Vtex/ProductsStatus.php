<?php
/*
 * Esta classe é abstrata e cada Marketplace Vtex deve redefinir run e o int_to
 * Verifica os produtos que estão com status_int 22 para ver se já foram aceitos na VTEX
 * 
 * */

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

abstract class ProductsStatus extends Main
{

	var $auth_data;
	var $int_to;
	var $sellerId;
	
	abstract protected function run();
	
    public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
        $this->load->model('model_integrations');
		$this->load->model('model_queue_products_marketplace');

    }
    
    public function productStatus() {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$main_integrations = $this->model_integrations->getIntegrationByIntTo($this->int_to, '0');
		
		$this->auth_data = json_decode($main_integrations['auth_data']);

		$offset = 0;
		$limit = 1; 
		$exists = true; 
		$store_id = 0; 
		while ($exists) {
			$dateLastInt = date('Y-m-d H:i:s');
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
		        $skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams, $prd_to_integration['prd_id'], $this->int_to, 'Notificação de Mudança');
				
				if (($this->responseCode == 200) || ($this->responseCode == 204)) {
					echo " Produto ".$prd_to_integration['prd_id']." aprovado.\n";
					// existe ! Então coloco na fila para o update do produto marketplace 
					$data = array(
						'id' => 0,
						'status' => 0,
						'prd_id' => $prd_to_integration['prd_id'],
						'int_to' => $this->int_to, 
					);
					$this->model_queue_products_marketplace->create($data);
					continue; 
				}
				if ($this->responseCode !== 404) { // O normal é dar 404, então podemos cadastrar o produto
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				// ainda não criou 
				echo " produto ".$prd_to_integration['prd_id']." ainda não aprovado.\n";
				echo " ".$this->dateDifference($prd_to_integration['date_last_int'],date('Y-m-d H:i:s'))."\n";
				if ($this->dateDifference($prd_to_integration['date_last_int'],date('Y-m-d H:i:s'))>=2) {
					// Já está a muito tempo para ser aprovado, então faço uma nova sugestão
					$data = array(
						'id' => 0,
						'status' => 0,
						'prd_id' => $prd_to_integration['prd_id'],
						'int_to' => $this->int_to, 
					);
					$this->model_queue_products_marketplace->create($data);
					continue; 
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
