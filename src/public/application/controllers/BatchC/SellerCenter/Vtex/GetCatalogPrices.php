<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class GetCatalogPrices extends Main
{
    // Quantidade de produtos por página (máximo de 50);
    const REGISTERS = 50;
	const MAX_IMAGES = 1;
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
 
        $this->load->model('model_catalogs');
        $this->load->model('model_products_catalog');
		$this->load->model('model_brands');
		$this->load->model('model_category');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_products');
    }

    // php index.php BatchC/SellerCenter/Vtex/GetCatalogPrices run
	function run($id=null,$params=null)
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
		$retorno = $this->getPrices();
	
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}


	public function getPrices() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo "Buscando produtos que usam catálogos de produtos e vendo se mudou o preço na VTEX \n";

		$catalogsall = $this->model_catalogs->getCatalogsDataView();
		$catalogs = array();
		foreach($catalogsall as $catalog) {
			$catalogs[$catalog['id']] = $catalog['name'];
		}
		$offset = 0;
		$limit = 1; 
		$exists = true; 
		while ($exists) {
			$dateLastInt = date('Y-m-d H:i:s');
			$products = $this->model_products_catalog->getProductCatalogIdFromProducts($offset, $limit);
			if (count($products)==0) {
				echo "Encerrou \n";
				$exists = false;
				break;
			}
			
			foreach ($products as $key => $product) {
				$product_catalog_id = $product['product_catalog_id'];
				$catprod =  $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id);
				$integrationData = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$catalogs[$catprod[0]['catalog_id']]);
				$auth_data = json_decode($integrationData['auth_data']);
				// pegamos o preço 
				$int_to = $catalogs[$catprod[0]['catalog_id']];
				
				$product_catalog = $this->model_products_catalog->getProductProductData($product_catalog_id);
				
				if (is_null($product_catalog['mkt_sku_id'])) {
					continue;
				}
				
				echo $int_to.": Sku vtex ".$product_catalog['mkt_sku_id']; 
				//var_dump($product_catalog);
				//$url = 'https://api.vtex.com/'.$auth_data->accountName.'/pricing/prices/'.$product_catalog['mkt_sku_id'] ; 
	            
	            $tradepolicy=1;
				$url = 'https://api.vtex.com/'.$auth_data->accountName.'/pricing/prices/'.$product_catalog['mkt_sku_id'].'/computed/'.$tradepolicy; 
           
	            $this->processURL($auth_data, $url);
				if ($this->responseCode == 404) { // ainda não tem preço cadastrado, então não trago este produto
				    $erro =  "  Produto VTEX ". $product_catalog['mkt_sku_id']."  do produto de catálog - ".$product_catalog['id']." sem preço. Disable"; 
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"W");
					$this->model_products_catalog->updateProductCatalog(array('status' => 2),$product_catalog['id']); 
					$this->model_products_catalog->updateProductsBasedFromProductCatalog(array('status' => 2),$product_catalog['id']);
					continue;
				}
				if ($this->responseCode !== 200) {
					$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$url;
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					continue;
				}
				$originalprice = null;
	            $value = json_decode($this->result);
	           
			   
			   /* if (is_object($value)) {
	                //$price = $value->basePrice ?? 0;
					$price = is_null($value->listPrice) ? $value->basePrice: $value->listPrice;
					$originalprice = $price;
					
					// vejo se tem alguma promoção em andamento
					$fixedPrices = $value->fixedPrices;
					//var_dump($fixedPrices);
					foreach($fixedPrices as $fixedPrice) {
						//var_dump($fixedPrice);
						if ($fixedPrice->tradePolicyId == 1) {
							echo " tem promoção" ;
							// var_dump($fixedPrice);
							if (property_exists($fixedPrice, 'dateRange')) {
								if ((strtotime(date('Y-m-d H:i:s')) >= strtotime($fixedPrice->dateRange->from)) &&  
									(strtotime(date('Y-m-d H:i:s')) <= strtotime($fixedPrice->dateRange->to))) {
									$price = $fixedPrice->value;
									break;
								}
							}
							else {
								$price = $fixedPrice->value;
							}
							
						}
					}
					Echo " peguei o preco ".$price." preço original ".$originalprice." preço base ".$value->basePrice."\n";

	            } else {
	                $price = 0;
	            }
			    *
			    */
			    $value = json_decode($this->result);
	            if (is_object($value)) {
	        	   // echo "Preço produto VTEX ". $product['product_id'];
					
					$price = $value->sellingPrice;
					
					$originalprice = $value->listPrice;
					
					Echo " Peguei o preco ".$price." preço lista ".$originalprice." preço de venda ".$price."\n";
	            } else {
	                $price = 0;
	            }
				
				if (is_null($originalprice)) {$originalprice = $price; }
				if ((float)$price != (float)$product_catalog['price']) {
					echo  "  Produto VTEX ". $product_catalog['mkt_sku_id']." catalogo ".$product_catalog['id']." mudou de preço de  ".$product_catalog['price']." para ". $price."\n"; 
					$this->model_products_catalog->updateSimple(array('price' => $price, 'original_price' => $originalprice),$product_catalog['id']); 
					$this->model_products_catalog->updateProductsBasedFromProductCatalog(array('price' => $price),$product_catalog['id']);
				}
				else {
					// echo  "  Produto VTEX ". $product_catalog['mkt_sku_id']." com mesmo preço ".$product_catalog['price']." = ". $price."\n"; 
				}
				if (((float)$originalprice !== (float)$price) && ($originalprice>0)) {
					$desconto = round((1-$product_catalog['price'] / $originalprice)*100,2);
					echo " Produto de catálogo ".$product_catalog['id']." com desconto de ".$desconto." %\n"; 
					$this->model_products_catalog->disableProductsMaximumDiscount($product_catalog['id'], $desconto);
				} 
				
			}
			$offset += $limit;
		} 
		
	}
	
}