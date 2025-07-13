<?php
/*
Verifica quais lojas precisam ser criadas no frete rapido como expedidor e as cria 
*/

/**
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Loader $load
 *
 * @property Model_products $model_products
 */

class GSomaCriaProdutos extends BatchBackground_Controller {
		
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
	    $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_products');
		$this->load->model('model_products_catalog');
		$this->load->model('model_gsoma_products_creation_control');
		$this->load->model('model_catalogs');
		
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
		die;
	    $retorno = $this->createProducts($params);

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function createProducts($params = NULL) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$main_integrations = $this->model_integrations->getIntegrationsbyStoreId(0);
		foreach ($main_integrations as $integrationName) {
            echo 'Procurando lojas e produtos novos para : '. $integrationName['int_to']."\n";
			$catalog = $this->model_catalogs->getCatalogByName($integrationName['int_to']);
			if (!$catalog) {
				$erro = 'Não existe nenhum catálogo chamado '.$integrationName['int_to'].'. Crie o catálogo no sistema antes e na tabela integrations';
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
            $this->findStores($integrationName['int_to'],$catalog);
            echo PHP_EOL;
        }
	}
	
	function findStores($int_to, $catalog) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$run_date = date("Y-m-d H:i:s");
		
		$find_date = "2020-01-01 00:00:00"; 
		$gscc = $this->model_gsoma_products_creation_control->getData($int_to);
		if ($gscc) {
			$find_date = $gscc['last_run'];
		}
		
		// vejo nas lojas se teve produtos de catalogo novos para serem criados
		$catalog_stores= $this->model_catalogs->getActiveCatalogsStoresDataByCatalogId($catalog['id']); 
		foreach ($catalog_stores as $catalog_store) {
			echo 'Buscando produtos para a loja '.$catalog_store['store_id'].' do catálogo '.$int_to." desde a última execução em ".$find_date."\n";
			$offset =0; 
			$limit = 20; 
			echo "Procurando produtos novos e ativos desde a última execução em ".$find_date."\n";
			while (true) {
				$products_catalog = $this->model_products_catalog->getProductsCatalogUpdated($catalog['id'], $find_date, $offset, $limit);
				if (!$products_catalog) {  // acabou ou deu erro....
					break;
				}
				$offset += $limit;
				foreach($products_catalog as $product_catalog) {
					$this->verifyAndCreateProduct($product_catalog, $catalog_store, $int_to);				
				}
			}
		}	
		
		// vejo agora as lojas que foram recem associados a catálogos
		$catalog_stores= $this->model_catalogs->getActiveCatalogsStoresDataByCatalogIdUpdate($catalog['id'], $find_date); 
		foreach ($catalog_stores as $catalog_store) {
			echo 'Buscando produtos para a loja '.$catalog_store['store_id'].' do catálogo '.$int_to." desde a última execução em ".$find_date."\n";
			$offset =0; 
			$limit = 20; 
			echo "Procurando todos os produtos ativos do ".$catalog['id']."\n";
			while (true) {
				$products_catalog = $this->model_products_catalog->getProductsCatalogbyCatalogid($catalog['id'], $offset, $limit);
				if (!$products_catalog) {  // acabou ou deu erro....
					break;
				}
				$offset += $limit;
				foreach($products_catalog as $product_catalog) {
					$this->verifyAndCreateProduct($product_catalog, $catalog_store, $int_to);			
				}
			}
		}
		
		$this->model_gsoma_products_creation_control->replace(array('int_to' => $int_to, 'last_run' => $run_date));
		 
	}	
	
	function verifyAndCreateProduct($product_catalog, $catalog_store, $int_to) {
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
		
		$product = $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($product_catalog['id'],$catalog_store['store_id']);
		if (is_null($product)) {  // essa loja não tem o produto. Vou criar
			$data_prod = array_merge($data_prod, 
				array(
					'sku' => str_replace(".","",$product_catalog['EAN']).'_'.$int_to, 
			        'qty' => 0,	
	                'store_id' => $catalog_store['store_id'],  
	                'company_id' => $catalog_store['company_id'],
	                'situacao' => 2, 
	                'prazo_operacional_extra' => 0,
	            )
			);
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
                    	$data_var = Array (
							'prd_id' => $create,
							'variant' => $product_variant['variant_id'],
							'name' => $product_variant['name'],
							'sku' => $product_catalog['EAN'].'_'.$int_to.'_'.$product_variant['variant_id'],
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
							$data_var = Array (
								'prd_id' => $product['id'],
								'variant' => $product_variant['variant_id'],
								'name' => $product_variant['name'],
								'sku' => $product_variant['EAN'].'_'.$int_to,
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
	
}
?>
