<?php


require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class NovoMundoMigrationProductsDisable extends Main
{

	var $auth_data;
	var $store_id; 
	var $int_to;
	var $integration_store;
	var $integration_main;
	var $prd;
	var $sellerId; 

	
	public function __construct()
	{
		parent::__construct();
		
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
		$this->load->model('model_products');
	}

	// php index.php BatchC/SellerCenter/NovoMundo/NovoMundoMigrationProductsDisable run null store_id
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

		$this->int_to ='NovoMundo';
		
			
		if (!is_null($params)) {
			$this->store_id = $params;
			$this->disableProducts();
		}
		else {
			echo "Passe como parametro: null id_loja\n";
		}
		

		echo "Fim da rotina\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

	
	function disableProducts() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// pego as integracoes da loja e do marketplace
		$this->getIntegration();
		
		// guardo as chaves da vtex
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		
		// pego o seller_id vtex
		$auth_data = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = $auth_data->seller_id;
		
		$offset=0;
		$limit = 10; 
		while (true)
		{
			$products = $this->model_products->getProductsByStore($this->store_id , $offset,  $limit);
			if (!$products) {
				break;
			}
			$offset+=$limit;
			foreach($products as $this->prd) {
				echo "Executando disable no produto ".$this->prd['id'].' sku id vtex '. $this->prd['sku']." name: ".$this->prd['name']."\n";
				$this->disableSkuVtex($this->prd['sku']);
				$this->model_products->update(array('status'=>2),$this->prd['id']);
			}
		}
		echo "acabou\n";
		
	}
	
	function getIntegration() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store_id,$this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto("0",$this->int_to);
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
	}

	private function disableSkuVtex($vtex_sku_id)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// Vejo o sku existe   api/catalog/pvt/stockkeepinguni
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$vtex_sku_id;
		$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
		if ($this->responseCode != 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$sku_vtex  = json_decode($this->result);

		$status= 'Inativo';
		if ($sku_vtex->IsActive) {
			$status= 'Ativo';
		}
		echo "   Sku ".$sku_vtex->Id." ".$status." Vtex prod id ".$sku_vtex->ProductId." nome: ".$sku_vtex->Name."\n"; 

		if (isset($sku_vtex->SkuSellers)) {
			var_dump($sku_vtex);
			echo " Mais de um seller neste SKU \n";
			echo "Desassociando\n";
			// https://developers.vtex.com/vtex-rest-api/reference/catalog-api-sku-seller#catalog-api-delete-seller-sku
			$endPoint   = '/api/catalog_system/pvt/skuseller/remove/'.$this->sellerId.'/'.$vtex_sku_id;
			$skuAssociate = $this->processNew($this->auth_data, $endPoint, 'POST', json_encode(array()), $this->prd['id'], $this->int_to, 'Delete Sku Association');
			if ($this->responseCode !== 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
			die; 
		}
		
		// alteramos agora o sku para inativo
		$skuvtex= array( 
			'Id' => $vtex_sku_id, 
			'IsActive' => false, 
			'Name' =>  $sku_vtex->Name, 
			'ProductId' => $sku_vtex->ProductId,
		);
		$endPoint   = '/api/catalog/pvt/stockkeepingunit/'.$vtex_sku_id;
		$skuExist = $this->processNew($this->auth_data, $endPoint, 'PUT', json_encode($skuvtex), $this->prd['id'], $this->int_to, 'Disable SKU');
		$sku_vtex  = json_decode($this->result);
		if ($this->responseCode !== 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		sleep(1);
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$vtex_sku_id;
		$skuExist = $this->processNew($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
		$skuExist  = json_decode($this->result);
		//var_dump($skuExist);
		if ($skuExist->IsActive) {
			echo " ***** CONTINUA ATIVO NA VTEX *****\n";
			echo "Desassociando\n";
			// https://developers.vtex.com/vtex-rest-api/reference/catalog-api-sku-seller#catalog-api-delete-seller-sku
			$endPoint   = '/api/catalog_system/pvt/skuseller/remove/'.$this->sellerId.'/'.$vtex_sku_id;
			$skuAssociate = $this->processNew($this->auth_data, $endPoint, 'POST', json_encode(array()), $this->prd['id'], $this->int_to, 'Delete Sku Association');
			if ($this->responseCode !== 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
		}
		
	}
}
