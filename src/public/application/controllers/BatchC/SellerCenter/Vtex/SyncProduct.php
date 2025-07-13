<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Utils/Product.php";

class SyncProduct extends Main
{
    // Quantidade de produtos por página (máximo de 50);
    const REGISTERS = 50;
	
	var $catalog;

	var $product_util;
	var $amount = 0;
	
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
		$this->load->model('model_stores');

		$this->product_util = new Product();
    }

    // php index.php BatchC/SellerCenter/Vtex/Catalog run null Farm
	function run($id=null,$params=null, $start = 1, $limit = -1)
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
		if (!is_null($params)) {
			$retorno = $this->getCatalog($params, $start, $limit);
		}
		else {
			echo "Informe o int_to do marketplace para puxar o catálogo e seus produtos\n";
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

    // php index.php BatchC/SellerCenter/Vtex/Catalog getCatalog
    public function getCatalog($int_to, $start, $limit)   // leio todos os ids dos produtos atrelados a este catálogo
    {
 		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        $from = $start;
        $to   = $start + self::REGISTERS;
		$maisItens = true;

		$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);

		$amount = 0;

		While($maisItens) {			
		    $endPoint = 'api/catalog_system/pvt/products/GetProductAndSkuIds?_from='.$from.'&_to='.$to;
		    $this->processNew($separateIntegrationData, $endPoint);
			if ($this->responseCode == 429) {
				sleep(60);
				$this->processNew($separateIntegrationData, $endPoint);
			}
			if ($this->responseCode !== 200) {
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				return;
			}
			$response = json_decode($this->result);
			$products = $response->data;
			$itens = array(); 

			foreach ($products as $product) {
				$amount++;
				array_push($itens, $product);

				// if ($amount >= 1000) {
				// 	$maisItens = false;
				// 	break;
				// }
			}

		    foreach ($itens as $id => $skus) {
		        foreach ($skus as $sku) {
					$stockKeepingUnit = $this->getProductVtex($separateIntegrationData, $sku);

					if ($stockKeepingUnit !== false) {
						foreach($stockKeepingUnit['SkuSellers'] as $seller) {
							$stores = $this->model_stores->getStoreBySellerId($seller["SellerId"]);
							if ($stores['integrate_status'] == 2) {
								if (!$this->existProduct($sku, $seller["SellerId"])) {
									$this->product_util->createWithStockKeepingUnit($separateIntegrationData, $int_to, $stockKeepingUnit, $stores);
								}
							}
						}
					}
		        }
		    }
		
		    if ($to < $response->range->total) {
		        $from += self::REGISTERS;
		        $to   += self::REGISTERS;
			}
			else {
				$maisItens = false;
			}

			if ($limit != -1) {
				if ($from > $limit) {
					$maisItens = false;
				}
			}
		}
    }

	private function existProduct($sku, $seller_id) {
		$sql = 
			"select p.* from company c ".
			"    join stores s on s.company_id = c.id ".
			"    join products p on p.store_id = s.id and p.company_id = c.id ".
			"    left join prd_variants pv on pv.prd_id = p.id ".
			"where  ".
				"(p.sku = ? or pv.sku = ?) and    ".
				"c.import_seller_id  = ? ";
		$query = $this->db->query($sql, array($sku, $sku, $seller_id));
		$prf = $query->row_array();
		if (empty($prf)) {
			echo ++$this->amount . " - Produto ( {$sku} ) não encontrado!" . PHP_EOL;
			return false;
		}
		else {
			echo ++$this->amount . " - Produto ( {$sku} ) encontrado!" . PHP_EOL;
			return true;
		}
	}

	private function getProductVtex($separateIntegrationData, $sku)  
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		try {
			$endPoint = 'api/catalog_system/pvt/sku/stockkeepingunitbyid/'.$sku;
			$this->processNew($separateIntegrationData, $endPoint);
			
			if ($this->responseCode == 429) {
				sleep(60);
				$this->processNew($separateIntegrationData, $endPoint);
			}
			if ($this->responseCode !== 200) {
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				return false;
			}

			$productData = json_decode($this->result, true);

			return $productData;
		} catch (Exception $e) {
			return false;
		}
	}


	public function getProduct($int_to, $id, $product_id)  
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
    	$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);

		$endPoint = 'api/catalog_system/pvt/sku/stockkeepingunitbyid/'.$product_id;
		$this->processNew($separateIntegrationData, $endPoint);
		
		if ($this->responseCode == 429) {
			sleep(60);
			$this->processNew($separateIntegrationData, $endPoint);
		}
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}

		$productData = json_decode($this->result, true);

		if ($productData['IsActive'] === false) return ;

		return ;

		// Crio a categoria se não existir 
		$category = "";
		$lastLeaf = "";
		foreach (json_decode(json_encode($productData['ProductCategories']), true) as $key => $value) {
			$category = $value."/".$category;
			if ( $lastLeaf == '') {
				$lastLeaf = $key;
			}
		}
		$category = rtrim($category,"/");

		// echo "\ncategoria: ".$category." id: ".$lastLeaf."\n";
		$category_id = $this->model_category->getcategorybyName($category);
		if (!$category_id) {
			$data_cat = array (
				'name' => $category,
				'active' => 1,
				'tipo_volume_id' => null, 
				'days_cross_docking' => 0,
				'qty_products' => 0, 
			); 
			$this->model_category->create($data_cat); 
			$category_id = $this->db->insert_id();
			// echo "category id ".$category_id."\n";
			$data_cat_todos = array(
				'id_integration' => $integrationData['id'], 
				'id' => $lastLeaf,
				'nome' => $category, 
				'int_to' => $int_to
			); 
			$this->db->replace('categorias_todos_marketplaces', $data_cat_todos);
			$data_cat_mkt = array(
				'int_to' => $int_to,
				'category_id' => $category_id,
				'category_marketplace_id' => $lastLeaf		
			);
			$replace = $this->model_categorias_marketplaces->replace($data_cat_mkt);
		}

		// Crio a marca se não existir 
		$brand_id= $this->model_brands->getBrandbyName($productData['BrandName']);
		if (!$brand_id) {
			$brand = array('name' => $productData['BrandName'], 'active' => $productData['IsBrandActive']);
			$brand_id = $this->model_brands->create($brand);
		}

		$codigo_do_fabricante = '';
		if  (!is_null($productData['ManufacturerCode'])) {
			if ($productData['ManufacturerCode'] != '') {
				$codigo_do_fabricante = $productData['ManufacturerCode'];
				echo '[FABRICANTE] ' . $productData['ManufacturerCode'];
			}
		}

		$image = '';
		$keys = array_merge(range('A', 'Z'), range('a', 'z'));
	
		for ($i = 0; $i < 30; $i++) {
			$image .= $keys[array_rand($keys)];
		}
		
		$folder = FCPATH . 'assets/images/product_image/'.$image;
		if (!is_dir($folder)) {
			mkdir($folder);
		}
		$principal_image = '';
		foreach ($productData['Images'] as $images) {
			$url = $images['ImageUrl'];
			$img = $folder.'/'.$images['FileId'].'.jpg';
			if 	(!file_exists($img)) {
				file_put_contents($img, file_get_contents($url));
			}
			if ($principal_image=='') {
				$principal_image = base_url('assets/images/product_image/'.$image).'/'.$images['FileId'].'.jpg';
			}
		}

		$price = 0; //$this->getPrice($separateIntegrationData, $id);

		foreach ($productData['SkuSellers'] as $skuSeller) {
			if ($skuSeller['SellerId'] == 1) continue ;

			$ean = '';
			if (!is_null($productData['AlternateIds'])) {
				if (!is_null($productData['AlternateIds']['Ean'])) {
					$ean = $productData['AlternateIds']['Ean'];
				}
			} 
			$stores = $this->model_stores->getStoreBySellerId($skuSeller['SellerId']);

			if (!is_null($stores)) {
				$product = array(
					'name' => $productData['ProductName'],
					'sku' => $productData['SkuSellers'][count($productData['SkuSellers']) - 1]['StockKeepingUnitId'],
					'price' => $price,
					'qty' => 0,
					'image' => 	$image,
					'principal_image' => $principal_image,
					'description' => $productData['ProductDescription'],
					'brand_id' => json_encode(array($brand_id)),
					'category_id' => json_encode(array($category_id)),
					'store_id' => $stores['id'],
					'status' => 0,
					'EAN' => $ean,
					'codigo_do_fabricante' => $codigo_do_fabricante,
					'peso_liquido' => '',
					'peso_bruto' => $productData['Dimension']['weight'],
					'largura' => $productData['Dimension']['width'],
					'altura' => $productData['Dimension']['height'],
					'profundidade' => $productData['Dimension']['length'],
					'garantia' => '',
					'NCM' => '',
					'origin' => $int_to,
					'CEST' => '',
					'FCI' => '',
					'profundidade' => $productData['Dimension']['length'],
					'company_id' => $stores['company_id'],
					'has_variants' => '',
					'category_imported' => ''
				);		
		
				$this->model_products->create($product);
			}
		}


	}

	function getPrice($separateIntegrationData, $product_id) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		try {
			
			// pegamos o preço 
			$url = 'https://api.vtex.com/'.$separateIntegrationData->accountName.'/pricing/prices/'.$product_id; 
			$this->processURL($separateIntegrationData, $url);
			if ($this->responseCode == 429) {
				sleep(60);
				$this->processURL($separateIntegrationData, $url);
			}
			if ($this->responseCode == 404) { // ainda não tem preço cadastrado, então não trago este produto
				$erro =  "  Produto VTEX ". $product_id ." sem preço. Não importado"; 
				// echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
			}
			if ($this->responseCode !== 200) {
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$url;
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
			}
			$value = json_decode($this->result);
			if (is_object($value)) {
				$price = $value->basePrice ?? 0;
			} else {
				$price = 0;
			}

			return $price;

		} catch (Exception $e) {
			return 0;
		}
	}

    // php index.php BatchC/SellerCenter/Vtex/Catalog getProducts
    public function getProducts($int_to)  
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
    	$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);
		
        
        foreach ($getCategories as $category) {
			// retu

            $endPoint = 'api/catalog_system/pvt/sku/stockkeepingunitbyid/'.$product['product_id'];
            $this->processNew($separateIntegrationData, $endPoint);
            
			if ($this->responseCode == 429) {
				sleep(60);
			    $this->processNew($separateIntegrationData, $endPoint);
			}
			if ($this->responseCode !== 200) {
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
			if (!$this->result) {
            	$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex,  $product['product_id'], $int_to);
                continue;
            }
			
			$productData = json_decode($this->result);
			
			if (!isset($productData->AlternateIds->RefId)) {
				$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex,  $product['product_id'], $int_to);
				// var_dump($productData);
				$erro = 'Produto VTEX '.$product['product_id'].' sem RefId - ignorando';
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
				continue;
			}
			if (!isset($productData->AlternateIds->Ean)) {
				$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex,  $product['product_id'], $int_to);
				// var_dump($productData);
				$erro = 'Produto VTEX '.$product['product_id'].' sem EAN - ignorando';
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
				continue;
			}
			
			echo "Processando produto VTEX ". $product['product_id']." - ".$productData->AlternateIds->RefId." ".$productData->NameComplete."\n"; 
		 
			// pegamos o preço 
			$url = 'https://api.vtex.com/'.$separateIntegrationData->accountName.'/pricing/prices/'.$product['product_id'] ; 
            $this->processURL($separateIntegrationData, $url);
			if ($this->responseCode == 429) {
				sleep(60);
			    $this->processURL($separateIntegrationData, $url);
			}
			if ($this->responseCode == 404) { // ainda não tem preço cadastrado, então não trago este produto
				$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex,  $product['product_id'], $int_to);
			    $erro =  "  Produto VTEX ". $product['product_id']." - ".$productData->AlternateIds->RefId." ".$productData->NameComplete." sem preço. Não importado"; 
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
				continue;
			}
			if ($this->responseCode !== 200) {
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$url;
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
			if ($product['product_id'] ==18) {
				var_dump($this->result);
			}
            $value = json_decode($this->result);
            if (is_object($value)) {
                $price = $value->basePrice ?? 0;
            } else {
                $price = 0;
            }

			if ($price == 0) { // ainda não tem preço cadastrado, então não trago este produto
				$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex,  $product['product_id'], $int_to);
			    $erro =  "  Produto VTEX ". $product['product_id']." - ".$productData->AlternateIds->RefId." ".$productData->NameComplete." sem preço. Não importado"; 
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
				continue;
			}
			// echo "preco = ".$price."\n";
			// var_dump($value);
			
			// var_dump($productData->ProductCategories);
			
			// Crio a categoria se não existir 
			$category = "";
			$lastLeaf = "";
			foreach (json_decode(json_encode($productData->ProductCategories), true) as $key => $value) {
				$category = $value."/".$category;
				if ( $lastLeaf == '') {
					$lastLeaf = $key;
				}
			}
			$category = rtrim($category,"/");

			// echo "\ncategoria: ".$category." id: ".$lastLeaf."\n";
			$category_id = $this->model_category->getcategorybyName($category);
			if (!$category_id) {
				$data_cat = array (
					'name' => $category,
					'active' => 1,
					'tipo_volume_id' => null, 
					'days_cross_docking' => 0,
					'qty_products' => 0, 
				); 
				$this->model_category->create($data_cat); 
				$category_id = $this->db->insert_id();
				// echo "category id ".$category_id."\n";
				$data_cat_todos = array(
					'id_integration' => $integrationData['id'], 
					'id' => $lastLeaf,
					'nome' => $category, 
					'int_to' => $int_to
				); 
				$this->db->replace('categorias_todos_marketplaces', $data_cat_todos);
				$data_cat_mkt = array(
					'int_to' => $int_to,
					'category_id' => $category_id,
					'category_marketplace_id' => $lastLeaf		
				);
				$replace = $this->model_categorias_marketplaces->replace($data_cat_mkt);
			}
			
			// Crio a marca se não existir 
			$brand_id= $this->model_brands->getBrandbyName($productData->BrandName);
			if (!$brand_id) {
				$brand = array('name' => $productData->BrandName, 'active' => $productData->IsBrandActive);
				$brand_id= $this->model_brands->create($brand);
			}
			
			$productExists = $this->model_products_catalog->getProductsByEAN($productData->AlternateIds->Ean);
			if (count($productExists) > 0) {
				$image =  $productExists[0]['image'];
			}
			else {
				$image = '';
			    $keys = array_merge(range('A', 'Z'), range('a', 'z'));
			
			    for ($i = 0; $i < 15; $i++) {
			        $image .= $keys[array_rand($keys)];
			    }
			}
			$folder = FCPATH . 'assets/images/catalog_product_image/'.$image;
            if (!is_dir($folder)) {
                mkdir($folder);
            }
			$principal_image = '';
            foreach ($productData->Images as $images) {
                $url = $images->ImageUrl;
                $img = $folder.'/'.$images->FileId.'.jpg';
				if 	(!file_exists($img)) {
					file_put_contents($img, file_get_contents($url));
				}
				if ($principal_image=='') {
					$principal_image = base_url('assets/images/catalog_product_image/'.$image).'/'.$images->FileId.'.jpg';
				}
			}
			// finalmente crio o produto de catálogo 

            $data = [
                'EAN'              		=> $productData->AlternateIds->Ean,
                'name'             		=> $productData->NameComplete,
                'status'           		=> ($productData->IsActive) ? 1 : 2,
                'has_variants'    		=> '', 
                'price'            		=> $price,
                'brand_code'       		=> $productData->ManufacturerCode, 
                'net_weight'       		=> $productData->RealDimension->realWeight,
                'gross_weight'     		=> $productData->Dimension->weight,
                'width'            		=> $productData->Dimension->width,
                'height'           		=> $productData->Dimension->height,
                'length'           		=> $productData->Dimension->length,
                'brand_id'         		=> $brand_id,
                'category_id'     		=> $category_id,
                'warranty' 		   		=> 0, 
                'NCM'			   		=> '',
                'origin'           		=> 0,
                'principal_image'  		=> $principal_image, 
                'image'			   		=> $image,
                'prd_principal_id' 		=> null,
                'variant_id'	   		=> null,
                'description'      		=> $productData->ProductDescription,
                'attribute_value_id'	=> '',
				'ref_id'              	=> $productData->AlternateIds->RefId,
				'sku_id' 				=> $product['product_id']
            ];
			//colocor o  refid no products_catlogo
			//quando inativar o product_catalog, inativar todos
			//se sumir, inativar os que sumram
			//colocar o refid no vtex_catalogo para facilitar a função acima. 
			 
            if (count($productExists) > 0) {
				$lido = $productExists[0];
				unset($lido['id']);
				unset($lido['date_create']);
				unset($lido['date_update']);
				if ($lido == $data) {
					echo " Igual, pulando \n";
					$success= true; 
				}
				else {
					echo " Diferente, alterando id = ". $product['product_id']. "\n";
					$success = $this->model_products_catalog->update($data, $productExists[0]['id'],  array($this->catalog['id']));
				} 
            } else {
            	echo " Novo, criando \n";
                $success = $this->model_products_catalog->create($data, array($this->catalog['id']));
            }

            if (!$success) {
            	$erro =  "  Produto ". $product['product_id']." - ".$productData->AlternateIds->RefId." ".$productData->NameComplete." apresentou erro no banco de dados ".print_r($this->db->error(),true); 
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
            	$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $product['product_id'], $int_to);
				die;
            }
			else {
				$data_vtex = array(
            		'status_int' => self::INTEGRATED, 
            		'ref_id'=> $productData->AlternateIds->RefId
				); 
				$this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $product['product_id'], $int_to);
				//$this->model_catalogs->removeProductFromVtexCatalog($product['cat_id']);
			}
        }
	
    }

	public function inactiveProducts($int_to)  
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$offset =0; 
		$limit = 50; 
		echo "Procurando produtos que sumiram do catálogo Vtex ".$int_to." ou que não conseguiram mais ser integrados\n";
		while (true) {
			$products_catalog = $this->model_products_catalog->getProductsCatalogbyCatalogid($this->catalog['id'], $offset, $limit );
			if (!$products_catalog) {  // acabou ou deu erro....
				break;
			}
			$offset += $limit;
			foreach($products_catalog as $product_catalog) {
				$productvtex = $this->model_catalogs->getProductFromVtexCatalogByRefid($product_catalog['ref_id'], $int_to);
				if (!$productvtex) { // Não achei mais no catálogo então inativo
					if ($product_catalog['status'] == 1) { // se estava ativo inativa
						echo "Inativando ".$product_catalog['id']." ".$product_catalog['name']." pois sumiu do catálog VTEX\n";
						$this->disableProducts($product_catalog, $this->catalog['id']); 
					}
				}elseif ($productvtex['status_int'] != self::INTEGRATED) {  // Achei mas não conseguiu ser integrado
					if ($product_catalog['status'] == 1) { // se estava ativo inativa
					    echo "Inativando ".$product_catalog['id']." ".$product_catalog['name']." pois não conseguiu ser integrado corretamente\n";
						$this->disableProducts($product_catalog, $this->catalog['id'], null); 
					}
				}else {
					//achei e foi integrado, não preciso fazer nada neste produto de catálogo 
				}
				
			}
		}
	}

	function disableProducts($product_catalog, $catalog_id = null ) {
		// inativo o product_catalog
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		get_instance()->log_data('products_catalog','edit_before',json_encode($product_catalog),"I");
		$this->model_products_catalog->updateSimple(array('status'=>2), $product_catalog['id']);
		if (!is_null($catalog_id)) { // removo o product_catalog do catalogo
			$this->model_products_catalog->removeProductCatalogFromCatalog($product_catalog['id'],$catalog_id);
		}
		// disable todos os produtos que usam este product_catalag 
		$offset =0; 
		$limit = 50; 
		while (true) {
			$products = $this->model_products_catalog->getProductsByProductCatalogId($product_catalog['id'],$offset,$limit );
			if (!$products) {  // acabou ou deu erro....
				break;
			}
			$offset += $limit;
			foreach($products as $product) {
				if ($product['status'] == 1) {
					get_instance()->log_data('products','edit_before',json_encode($product),"I");
					$data= array('status'=>2);
					$this->model_products->update($data,$product['id']);
					$data['id'] = $product['id'];
					get_instance()->log_data('products','edit_after',json_encode($data),"I");
				}
			}
		}
	}
	
}
