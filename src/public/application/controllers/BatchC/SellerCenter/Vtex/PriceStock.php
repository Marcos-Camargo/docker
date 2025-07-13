<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class PriceStock extends Main
{
	var $int_to = null;
	var $auth_data;
	
    public function __construct()
    {
        parent::__construct();
		$this->load->model('model_integrations');
		$this->load->model('model_queue_products_marketplace');
			
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
    }

    // php index.php BatchC/SellerCenter/Vtex/PriceStock run null
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id); 
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		if (is_null($this->int_to)) {
			echo "Esta rotina não deve ser chamada diretamente.\n";
		}
		else {
			$integrationData = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$this->int_to);
			$this->auth_data = json_decode($integrationData['auth_data']);
			
		    $retorno = $this->notifyPriceAndStockChange();
		}
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}


    // php index.php BatchC/SellerCenter/Vtex/Product notifyPriceAndStockChange
    public function notifyPriceAndStockChange()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Buscando produtos com estoque alterado para notificar ".$this->int_to."\n";
		
		$offset = 0;
		$limit = 50; 
		$exists = true; 
		while ($exists) {
			$dateLastInt = date('Y-m-d H:i:s');
			$products = $this->model_integrations->getProductsRefresh($this->int_to, $offset, $limit);
			if (count($products)==0) {
				echo "Encerrou \n";
				$exists = false;
				break;
			}
			
			foreach ($products as $key => $product) {
				echo $offset + $key + 1 . " - ";
				
				// Leio os dados da integração desta loja deste produto
				$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto($product['store_id'],$this->int_to);
				$auth_data = json_decode($integration['auth_data']);
            	$sellerId = $auth_data->seller_id;
				
				$data = [];
				$bodyParams = json_encode($data);
	            $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$product['pi_skumkt'];
	            
	            echo "Verificando se o produto ".$product['id']." sku ".$product['pi_skumkt']." existe no marketplace ".$this->int_to." para o seller ".$sellerId.".\n";

				/*
	            $skuExist = $this->processNew($this->auth_data, $endPoint, 'POST', $bodyParams);
		            
				if ($this->responseCode == 404) {
					$erro = "O produto ".$product['id']." não está cadastrado no marketplace ".$this->int_to." para o seller ".$sellerId.".";
		            echo $erro."\n";
		            $this->log_data('batch', $log_name, $erro,"E");
					$this->model_integrations->updatePrdToIntegration(array('status_int' => 90, 'date_last_int' => $dateLastInt), $product['pi_id']);
					
					// deveria retirar o registro do prd_to_integration e do vtex_ult_envio se isso acontecer 
					continue;
				}
				if ($this->responseCode !== 204) {
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				 * 
				 */
				$data = array ( 
					'status' => 0,
					'prd_id' => $product['id'],
					'int_to' => $this->int_to,
				);
				
				$this->model_queue_products_marketplace->create($data);
	            $notice = "Notificação de alteração concluída para o produto ".$product['id']." sku: ".$product['pi_skumkt'];
                echo $notice."\n";

			}	
			$offset += $limit;
			$sleep = rand(10,60);
			echo ' ------ Dormindo '.$sleep." segundos ----------- \n";
			sleep($sleep);
		} 

    }

}
