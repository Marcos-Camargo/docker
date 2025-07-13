<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class PriceStockV2 extends Main
{
	
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

    // php index.php BatchC/SellerCenter/Vtex/PriceStockV2 run null CasaeVideo
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
		 if(is_null($params)  || ($params == 'null')){
            echo PHP_EOL ."É OBRIGATÓRIO PASSAR O int_to NO PARAMS". PHP_EOL;
        }
		else {
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
			if($integration){
				$this->includeProductsOnQueue($integration['int_to']);
            }
			else {
				echo PHP_EOL .$params." não tem integração definida". PHP_EOL;
			}
		}
		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

    public function includeProductsOnQueue($int_to)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Buscando produtos com estoque alterado para notificar ".$int_to."\n";
		
		$offset = 0;
		$limit = 50; 
		$exists = true; 
		while ($exists) {
			$products = $this->model_integrations->getProductsRefresh($int_to, $offset, $limit);
			if (count($products)==0) {
				echo "Encerrou \n";
				$exists = false;
				break;
			}
			
			foreach ($products as $key => $product) {
				echo $offset + $key + 1 . " - ";
				
				$data = array ( 
					'status' => 0,
					'prd_id' => $product['id'],
					'int_to' => $int_to,
				);
				
				$this->model_queue_products_marketplace->create($data);
	            $notice = "Produto colocado na fila ".$product['id']." sku: ".$product['pi_skumkt'];
                echo $notice."\n";

			}	
			$offset += $limit;
			$sleep = rand(10,60);
			echo ' ------ Dormindo '.$sleep." segundos ----------- \n";
			sleep($sleep);
		} 

    }

}
