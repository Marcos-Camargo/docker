<?php
/*
Verifica quais lojas precisam ser criadas no frete rapido como expedidor e as cria 
*/  

 class SikaCriaProdutos extends BatchBackground_Controller {
	
	var $companySika;
	var $catalogSika;
	var $storeSika; 
	
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
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
	    $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_products');
		$this->load->model('model_products_catalog');
		$this->load->model('model_sika_products_creation_control');
		$this->load->model('model_catalogs');
		$this->load->model('model_company');
		
    }
	
	// php index.php BatchC/GSomaCriaProdutos run
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
		
		$this->getDataSika();
	    $retorno = $this->createProducts();
		$retorno = $this->updateStock();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function getDataSika()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// pego a empresa
		$comp = $this->model_company->getCompaniesByName('SIKA');
		if (count($comp) != 1) {
			$erro = 'Não existe nenhuma empresa chamada '.'SIKA'.' ou tem mais de uma. Garanta que só tenha uma empresa chamada SIKA.';
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$this->companySika =  $comp[0];
		
		//pego a loja 
		$stores = $this->model_stores->getMyCompanyStores($this->companySika['id']);
		foreach($stores as $store) {
			if ($store['CNPJ'] == $this->companySika['CNPJ']) {
				$this->storeSika = $store;
				break;
			}
		}
		if (is_null($this->storeSika)) {
			$erro = 'Não encontrei nenhuma loja com o mesmo CNPJ da empresa '.$this->companySika['name'].' garanta que a loja franqueadora master tenha o CNPJ'.$this->companySika['CNPJ']."\n";
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		
		// pego o catálogo
		$catalog = $this->model_catalogs->getCatalogByName('Catálogo Sika');
		if (!$catalog) {
			$erro = 'Não existe nenhum catálogo chamado '.'Catálogo Sika'.'. Crie o catálogo no sistema antes.';
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$this->catalogSika = $catalog; 
		
	}
	
	function createProducts() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$run_date = date("Y-m-d H:i:s");
		
		$find_date = "2020-01-01 00:00:00"; 
		$gscc = $this->model_sika_products_creation_control->getData(1);
		if ($gscc) {
			$find_date = $gscc['last_run'];
		}
		
		// vejo nas lojas se teve produtos de catalogo novos para serem criados
		$catalog_stores= $this->model_catalogs->getActiveCatalogsStoresDataByCatalogId($this->catalogSika['id']); 
		foreach ($catalog_stores as $catalog_store) {
			if ($catalog_store['store_id'] !== $this->storeSika['id']) {
				continue;
			}
			echo 'Buscando produtos para a loja '.$catalog_store['store_id'].' do catálogo '.$this->catalogSika['name'].' desde a última execução em '.$find_date."\n";
			$offset = 0; 
			$limit = 20; 
			echo "Procurando produtos novos e ativos desde a última execução em ".$find_date."\n";
			while (true) {
				$products_catalog = $this->model_products_catalog->getProductsCatalogUpdated($this->catalogSika['id'], $find_date, $offset, $limit);
				if (!$products_catalog) {  // acabou ou deu erro....
					break;
				}
				$offset += $limit;
				foreach($products_catalog as $product_catalog) {
					$this->verifyAndCreateProduct($product_catalog, $catalog_store);				
				}
			}
		}	
		
		$this->model_sika_products_creation_control->replace(array('id' => 1, 'last_run' => $run_date));
		 
	}	
	
	function verifyAndCreateProduct($product_catalog, $catalog_store) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$data_prod = array(
	        'name' => $product_catalog['name'],
	        'price' => $product_catalog['price'],
	        'image' => 'catalog_'.$product_catalog['id'],
	        'principal_image' => $product_catalog['principal_image'],
	        'description' => $product_catalog['description'],
	        'attribute_value_id' => $product_catalog['attribute_value_id'],
	        'brand_id' => json_encode(array($product_catalog['brand_id'])),
	    	'category_id' => json_encode(array($product_catalog['category_id'])),
            'status' =>  $product_catalog['status'],
            'EAN' => $product_catalog['EAN'],
            'codigo_do_fabricante' => (is_null($product_catalog['brand_code'])) ? '' : $product_catalog['brand_code'],
            'peso_liquido' => $product_catalog['net_weight'],
            'peso_bruto' => $product_catalog['gross_weight'],
            'largura' => $product_catalog['width'],
            'altura' => $product_catalog['height'],
            'has_variants' => $product_catalog['has_variants'],   
            'profundidade' => $product_catalog['length'],
            'garantia' => $product_catalog['warranty'],
            'NCM' => $product_catalog['NCM'],
            'origin' => $product_catalog['origin'],
            'has_variants' => $product_catalog['has_variants'],                
            'product_catalog_id' => $product_catalog['id']
        );	
		echo " Procurando ".$product_catalog['id']. " para a loja ". $catalog_store['store_id']. "\n";
		$product = $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($product_catalog['id'],$catalog_store['store_id']);
		if (is_null($product)) {  // essa loja não tem o produto. Vou criar
			echo "Não achei ".$product_catalog['id']." para a loja ".$catalog_store['store_id']."\n"; 
			$data_prod = array_merge($data_prod, 
				array(
					'sku' => str_replace(".","",$product_catalog['EAN']).'_SIKA_'.$product_catalog['id'], 
			        'qty' => 0,	
	                'store_id' => $catalog_store['store_id'],  
	                'company_id' => $catalog_store['company_id'],
	                'situacao' => 2, 
	                'prazo_operacional_extra' => 0,
	            )
			);
			Echo " sku ". str_replace(".","",$product_catalog['EAN']).'_SIKA' . " \n";
			$create = $this->model_products->create($data_prod);
			if($create != false) {
        		$log_var = array('id'=> $create);
				$this->log_data('Products','create',json_encode(array_merge($log_var,$data_prod)),"I");
				echo "Produto criado ".json_encode(array_merge($log_var,$data_prod)). "\n"; 
				$product_variants = array();
				if ($product_catalog['has_variants']!=="") {
					$tipos_variacao = explode(";",strtoupper($product_catalog['has_variants'])); 
		           	$product_variants = $this->model_products_catalog->getProductCatalogVariants($product_catalog['id']);
                    $skuVar = $this->input->post('SKU_V');
                    foreach ($product_variants as $key => $product_variant) {
                    	if (is_null($product_variant['EAN'])) {
                    		$skuvar = $product_catalog['EAN'].'_SIKA_'.$product_variant['variant_id'];
                    	}
						else {
							$skuvar = $product_variant['EAN'].'_SIKA';
						}
							
                    	$data_var = Array (
							'prd_id' => $create,
							'variant' => $product_variant['variant_id'],
							'name' => $product_variant['name'],
							'sku' => $skuvar,
							'price' => $product_catalog['price'],
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
				}
        	}
        	else {
				$erro =  'Erro ao criar produto. Dados:'.json_encode($data_prod); 
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
                die;
        	}
        }
		else {  // alterou algo, então mudo no produto
			$this->log_data('Products','edit_before',json_encode($product),"I");
			$update = $this->model_products->update($data_prod,$product['id']);
			if ($update) {
				$log_var = array('id'=> $product['id']);
				$this->log_data('Products','edit_after',json_encode(array_merge($log_var,$data_prod)),"I");
				echo "Produto Alterado ".json_encode(array_merge($log_var,$data_prod)). "\n"; 
				$product_variants = array();
				if ($product_catalog['has_variants']!=="") {
					$tipos_variacao = explode(";",strtoupper($product_catalog['has_variants'])); 
		           	$product_variants = $this->model_products_catalog->getProductCatalogVariants($product_catalog['id']);
                    $skuVar = $this->input->post('SKU_V');
                    foreach ($product_variants as $key => $product_variant) {
						$variant_prd = $this->model_products->getVariants($product['id'], $product_variant['variant_id']);
						if (!$variant_prd) { // a variant do produto não existe, eu crio
							if (is_null($product_variant['EAN'])) {
	                    		$skuvar = $product_catalog['EAN'].'_SIKA_'.$product_variant['variant_id'];
	                    	}
							else {
								$skuvar = $product_variant['EAN'].'_SIKA';
							}
							$data_var = Array (
								'prd_id' => $product['id'],
								'variant' => $product_variant['variant_id'],
								'name' => $product_variant['name'],
								'sku' => $skuvar,
								'price' => $product_catalog['price'],
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
								'price' => $product_catalog['price'],
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
				$erro =  'Erro no update do produto '.$product['id'].'. Dados:'.json_encode($data_prod); 
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
                die;
			}
		}
	}
	
	function updateStock()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$offset = 0; 
		$limit = 20; 
		echo "Procurando estoques da Sika\n";
		while (true) {
			$products_catalog = $this->model_products_catalog->getProductsCatalogbyCatalogid($this->catalogSika['id'], $offset, $limit);
			if (!$products_catalog) {  // acabou ou deu erro....
				break;
			}
			$offset += $limit;
			foreach($products_catalog as $product_catalog) {
				if (is_null($product_catalog['prd_principal_id'])) {
					$this->checkProductUpdateStock($product_catalog);
				}						
			}
		}

	}
	
	function checkProductUpdateStock($product_catalog)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$offset = 0; 
		$limit = 20; 
		echo "Checando estoque do product_catalog_id ".$product_catalog['id'].' nome '.$product_catalog['name']."\n";
		
		$qty = 0;
		$ganhou = null;
		$price_other =  $product_catalog['price'];
		while (true) {
			$products = $this->model_products_catalog->getProductsByProductCatalogId($product_catalog['id'], $offset, $limit);
			if (!$products) {  // acabou ou deu erro....
				break;
			}
			$offset += $limit;
			foreach($products as $product) {
				if ($product['store_id'] == $this->storeSika['id']) {
					$id_sika = $product['id'];
					$qty_sika= $product['qty'];
					$price_sika = $product['price'];  // esse pode ser alterado
				}
				else {
					$price_other = $product['price']; // esse não pode ser alterado
					if (is_null($ganhou)) {
						$ganhou = $product['id']; // tem pelo menos 1
					}
					if ($product['qty'] > $qty) {
						$qty =  $product['qty'];
						$ganhou = $product['id'];
					}
				}
			}
		}
		if (!is_null($ganhou)) {
			if ($qty != $qty_sika) { // achou alguém e
				$this->model_products->update(array('qty' => $qty), $id_sika);
			} 
		}
		if (($product_catalog['has_variants']!="") && (!is_null($ganhou))) {
			$variants=$this->model_products->getVariants($ganhou); 
			foreach($variants as $variant) {
				$this->model_products->updateVar(array('qty'=>$variant['qty']), $id_sika, $variant['variant'] );
			}
		}
		if ((float)$price_sika != (float)$price_other) { // se o preço está diferente, altera para o preço do produto da loja sika
			$this->model_products_catalog->updateProductsBasedFromProductCatalog(array('price'=>$price_sika), $product_catalog['id']);
			$this->model_products_catalog->updateSimple(array('price'=>$price_sika), $product_catalog['id']);
		}
		
	}
}
?>
