<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class SyncProductPriceAndStock extends Main
{
    // Quantidade de produtos por página (máximo de 50);
    const REGISTERS = 50;
	
	var $catalog;
	
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
 
 		$this->load->model('model_products');
   }

    // php index.php BatchC/SellerCenter/Vtex/Catalog run null Farm
	function run($id=null,$int_to=null,$seller=null)
	{
		/* inicia o job */
		$this->setIdJob($id); 

		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$int_to),"I");
		
		/* faz o que o job precisa fazer */
		if (!is_null($int_to)) {
			if (!is_null($seller)) {
				$separateIntegrationData = $this->getSeparateIntegrationData($int_to);
				if (!is_null($separateIntegrationData)) {
					$retorno = $this->syncPriceAndStock($separateIntegrationData, $int_to , $seller, 0, 50);
				}
			}
			else {
				echo "Informe o seller do marketplace para sincronizar o preço e estoque dos seus produtos\n";
			}
		}
		else {
			echo "Informe o int_to do marketplace para sincronizar o preço e estoque dos seus produtos\n";
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	private function getSeparateIntegrationData($int_to) {
		$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);

		return $separateIntegrationData;
	}

	private function syncPriceAndStock($separateIntegrationData, $int_to, $sellerId, $limit, $offset) {
		$products = $this->model_products->getProductsVtexPagination($sellerId, $limit, $offset);

		foreach ($products as $product) {
			echo $limit . '/' . $offset . ' ' . $product['import_seller_id'] . ' - ' . $product['import_seller_id'] . ' ' .$product['name'] . PHP_EOL;
			$simulation = $this->simulationProduct($separateIntegrationData, $product);
			if (!is_null($simulation[0]))
				$this->updatePriceAndStock($product, $simulation);
		}

		if (count($products) >= $offset) {
			$limit = $limit + $offset;
			$this->syncPriceAndStock($separateIntegrationData, $int_to, $limit, $offset);
		}

		echo PHP_EOL . PHP_EOL . 'FIM SYNC' . PHP_EOL . PHP_EOL;
	}

	private function simulationProduct($separateIntegrationData, $product) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$endPoint = 'api/checkout/pvt/orderforms/simulation';

		$sku = $product['sku'];
		if (!is_null($product['sku_variant'])) {
			$sku = $product['sku_variant'];
		}

		$item = array(
			'seller' => $product['import_seller_id'],
			'id' => $sku,
			'quantity' => 1
		);

		$payload = array(
			'postalCode' => null,
			'items' => array($item),
			'country' => 'BRA'
		);

		$this->processNew($separateIntegrationData, $endPoint, 'POST', json_encode($payload));
		if ($this->responseCode == 429) {
			sleep(60);
			$this->processNew($separateIntegrationData, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint . ' result: '. $this->result;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return [null, null];
		}
		$response = json_decode($this->result);
		
		$simulation_item = null;

		if (!is_null($response->items)) {
			if (count($response->items) > 0) {
				foreach ($response->items as $i) {
					if (($i->id == $product['sku']) && ($i->seller == $product['import_seller_id'])) {
						$simulation_item = $i;
					}
				}
			}
		}

		$stockBalance = 0;
		if (!is_null($simulation_item)) {
			foreach ($response->logisticsInfo as $l) {
				if ($l->itemIndex == $simulation_item->requestIndex) {
					$stockBalance = $l->stockBalance;
				}
			}
		}

		return [$simulation_item, $stockBalance];
	}

	private function updatePriceAndStock($product, $simulation) {
		$price = !is_null($simulation[0]->price) ? ($simulation[0]->price / 100) : 0;

		$qty = $simulation[1];
		if (is_null($product['variant_id'])) {
			$this->model_products->updatePriceAndStock($product['id'], $price, $qty);
		}
		else {
			$this->model_products->updatePriceAndStockVariant($product['variant_id'], $price, $qty);
		}
	}
	

}
