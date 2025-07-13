<?php
/*
 * 
*/  

require APPPATH . "controllers/Queues/Omnilogic/ProcessProductsNotifyOmnilogicQueue.php";

class ProcessProductsNotifyOmnilogicConectala extends ProcessProductsNotifyOmnilogicQueue {
	public function __construct()
	{
		parent::__construct();

		$this->load->model('model_products');
		$this->load->model('model_categorias_marketplaces');
	}

	protected function getFetchClass(){ return $this->router->fetch_class(); }
	protected function getPath() { return $this->router->directory; }
	
	protected function afterProcessQueue($queue)
	{
		$productCategoryMkt = null;
		
		$prdToIntegration = $this->model_products->getPrdToIntegrationMeli($queue['prd_id']);
		
		if (empty($prdToIntegration)) {
			$product = $this->model_products->getProductData(0, $queue['prd_id']);

			$productCategoryMkt =  $this->model_categorias_marketplaces->getProductCategoryMktIntegration($queue['prd_id'], 'ML');

			if (empty($productCategoryMkt)) {
				$headers = array(
					"Content-Type: application/json"
				);
				$url = "https://api.mercadolibre.com/sites/MLB/domain_discovery/search?q=" . urlencode($product['name']) . "&limit=1";
				$res = $this->rest_request->sendREST($url, null, 'GET', $headers);
				$res = json_decode($res['content']);
				$this->model_categorias_marketplaces->ProductCategoryMktIntegration($queue['prd_id'], 'ML', $res['0']->category_id);
			}
		}
	}
}
?>
