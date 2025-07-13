<?php
/*
Verifica se algum produto de catalogo foi alterado e altera todos os produtos que o utilizam 
 * 
*/   
 class ProductsCatalogVerifyChanges extends BatchBackground_Controller {
	
	var $priceRO = false;
	
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
		$this->load->model('model_products');
		$this->load->model('model_products_catalog_change_control');
		$this->load->model('model_products_catalog');
		$this->load->model('model_settings');
		
    }
	
	// php index.php BatchC/GSomaVerifyCatalogChanges run
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
		$this->priceRO = $this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');
		$retorno = $this->verifyChanges();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function verifyChanges() 
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$run_date = date("Y-m-d H:i:s");
		
		$offset =0; 
		$limit = 20; 
		
		$find_date = "2019-01-01 00:00:00"; 
		$lido = $this->model_products_catalog_change_control->getData(1);
		if ($lido) {
			$find_date = $lido['last_run'];
		}

		echo "Lendo todos os produtos de catálogo desde ".$find_date."\n";
		while (true) {
			$products_catalog  = $this->model_products_catalog->getAllProductsCatalogUpdatedThatHaveProducts($find_date, $offset, $limit);
			if (!$products_catalog) {  // acabou ou deu erro....
				break;
			}
			$offset += $limit;
			foreach($products_catalog as $product_catalog) {
				$this->processProductCatalog($product_catalog);	
			}
		}
		
		$this->model_products_catalog_change_control->replace(array('id' => 1, 'last_run' => $run_date));		
	}

	function processProductCatalog($product_catalog) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Procurando produtos do products catalog Id = ". $product_catalog['id']."\n";
		$data_cata = array(
	        'name' => $product_catalog['name'],
	        'principal_image' => $product_catalog['principal_image'],
	        'description' => $product_catalog['description'],
	        'attribute_value_id' => $product_catalog['attribute_value_id'],
	        'brand_id' => json_encode(array($product_catalog['brand_id'])),
	    	'category_id' => json_encode(array($product_catalog['category_id'])),
            'EAN' => $product_catalog['EAN'],
            'codigo_do_fabricante' => (is_null($product_catalog['brand_code'])) ? '' : $product_catalog['brand_code'],
            'peso_liquido' => $product_catalog['net_weight'],
            'peso_bruto' => $product_catalog['gross_weight'],
            'largura' => $product_catalog['width'],
            'altura' => $product_catalog['height'],
            'products_package' => $product_catalog['products_package'],
            'has_variants' => $product_catalog['has_variants'],   
            'profundidade' => $product_catalog['length'],
            'garantia' => $product_catalog['warranty'],
            'NCM' => $product_catalog['NCM'],
            'origin' => $product_catalog['origin'],
            'has_variants' => $product_catalog['has_variants'],
        );
		
		$offset =0; 
		$limit = 20; 
		while (true) {
			$products = $this->model_products_catalog->getProductsByProductCatalogId($product_catalog['id'],$offset,$limit);
			if (!$products) {  // acabou ou deu erro....
				break;
			}
			$offset += $limit;
			
			foreach($products as $product) {
				$data_prod = array(
			        'name' => $product['name'],
			        'principal_image' => $product['principal_image'],
			        'description' => $product['description'],
			        'attribute_value_id' => $product['attribute_value_id'],
			        'brand_id' => $product['brand_id'],
			    	'category_id' => $product['category_id'],
		            'EAN' => $product['EAN'],
		            'codigo_do_fabricante' => $product['codigo_do_fabricante'],
		            'peso_liquido' => $product['peso_liquido'],
		            'peso_bruto' => $product['peso_bruto'],
		            'largura' => $product['largura'],
		            'altura' => $product['altura'],
                    'products_package' => $product['products_package'],
		            'has_variants' => $product['has_variants'],   
		            'profundidade' => $product['profundidade'],
		            'garantia' => $product['garantia'],
		            'NCM' => $product['NCM'],
		            'origin' => $product['origin'],
		            'has_variants' => $product['has_variants'],
		        );
				if  (($product_catalog['status'] != 1) &&  ($product['status'] ==1)) {
					// se inativou io produto de catálogo, troca o status do produto também. Nos demais casos, não faz nada
					$data_cata['status'] = 2;
					$data_prod['status'] = 1; 
				}
				$price_variant = $product['price'];
				if (($this->priceRO) && (($product_catalog['price']*1000) != ($product['price']*1000))) {
					$data_cata['price'] = $product_catalog['price'];
					$data_prod['price'] = $product['price'];
					$price_variant = $product_catalog['price'];
				}
				if ($data_cata == $data_prod) {
					// nada mudou então pula
					continue;
				}
				echo " Diferente, alterando id = ". $product['id']. "\n";
				echo "   Antigo: ".print_r($data_prod,true)."\n";
				echo "   Novo: ".print_r($data_cata,true)."\n";
				$this->log_data('Products','edit_before',json_encode($product),"I");
				$update = $this->model_products->update($data_cata,$product['id']);
				if ($update) {
					$log_var = array('id'=> $product['id']);
					$this->log_data('Products','edit_after',json_encode(array_merge($log_var,$data_cata)),"I");
					echo "Produto Alterado ".json_encode(array_merge($log_var,$data_cata)). "\n"; 
					$product_variants = array();
					if ($product_catalog['has_variants']!=="") {
						$tipos_variacao = explode(";",strtoupper($product_catalog['has_variants'])); 
			           	$product_variants = $this->model_products_catalog->getProductCatalogVariants($product_catalog['id']);
	                    $skuVar = $this->input->post('SKU_V');
	                    foreach ($product_variants as $key => $product_variant) {
							$variant_prd = $this->model_products->getVariants($product['id'], $product_variant['variant_id']);
							if (!$variant_prd) { // a variant do produto não existe, eu crio
								$data_var = Array (
									'prd_id' => $product['id'],
									'variant' => $product_variant['variant_id'],
									'name' => $product_variant['name'],
									'sku' => $product_variant['EAN'],
									'price' => $price_variant,
									'qty' => 0,
									'image' => $product_variant['principal_image'],
									'status' => 1,
									'EAN' => $product_variant['EAN'],
									'codigo_do_fabricante' => '',	
								);
		
								$createvar = $this->model_products->createvar($data_var);
								$this->log_data('Products','create_variation',json_encode($data_var),"I");
								echo "Variação de Produto criado ".json_encode($data_var). "\n"; 
							}
							else  {	// a variante existe, altero 
								$data_var = Array (
									'prd_id' => $product['id'],
									'name' => $product_variant['name'],
									'price' => $price_variant,
									'image' => $product_variant['principal_image'],
									'EAN' => $product_variant['EAN'],
								);
								$this->log_data('Products','update_variation_before',json_encode($variant_prd),"I");
								$updatevar = $this->model_products->updateVar($data_var, $product['id'], $product_variant['variant_id'] );
								$this->log_data('Products','update_variation_after',json_encode($data_var),"I");
								echo "Variação de Produto alterado ".json_encode($data_var). "\n"; 
							}
						}
					}
				}
				else {
					$erro =  'Erro no update do produto '.$product['id'].'. Dados:'.json_encode($data_cata); 
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"W");
	                die;
				}	
			}
		}
	}
}
?>