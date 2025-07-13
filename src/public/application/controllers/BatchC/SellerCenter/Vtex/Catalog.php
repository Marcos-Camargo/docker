<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

/**
 * @property Model_catalogs $model_catalogs
 * @property Model_products_catalog $model_products_catalog
 * @property Model_brands $model_brands
 * @property Model_category $model_category
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_products $model_products
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Model_job_schedule $model_job_schedule
 * 
 * @property Bucket	$bucket
 */
class Catalog extends Main
{
    // Quantidade de produtos por página (máximo de 50);
    const REGISTERS = 250;
	const MAX_IMAGES = 1;
	var $catalog;
	var $ean_duplicate;
	
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
		$this->load->model('model_settings');
		$this->load->model('model_integrations');
		$this->load->model('model_job_schedule');
		$this->load->library('bucket');

    }

    // php index.php BatchC/SellerCenter/Vtex/Catalog run null Farm 
	function run($id = null, $params = null, $first_product_id = null, $last_product_id = null)
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
		
		$this->ean_duplicate = $this->model_settings->getStatusbyName('vtex_catalog_import_access_ean_duplicate') == 1;

		/* faz o que o job precisa fazer */
		if (!is_null($params)) {
            $read_catalog = $this->readCatalog($params);
            if ($read_catalog) {
                if (is_null($first_product_id)) {
                    $this->getCatalog($params);
                    $this->setCatalogJob($params);
                }
                if (!is_null($first_product_id)) {
                    $this->getProducts($params, $first_product_id, $last_product_id);
                    $this->inactiveProducts($params, $first_product_id, $last_product_id);
                }
            }
		}
		else {
			echo "Informe o int_to do marketplace para puxar o catálogo e seus produtos\n";
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	public function readCatalog(string $int_to): bool
    {
		$this->catalog = $this->model_catalogs->getCatalogByName($int_to);
		if (!$this->catalog) {
            $this->catalog = $this->model_catalogs->getCatalogByIntTo($int_to);
            if (!$this->catalog) {
                echo "[ ERROR ] Não existe nenhum catálogo chamado $int_to. Crie o catálogo no sistema antes e na tabela integrations\n";
                return false;
            }
		}

        return true;
	}

    public function getCatalog($int_to): bool
    {
		$this->model_catalogs->clearVtexCatalogs($int_to);
		
        $from = 1;
        $to   = self::REGISTERS;
		$maisItens = true;

		$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);

		while ($maisItens) {
		    $endPoint = 'api/catalog_system/pvt/products/GetProductAndSkuIds?_from='.$from.'&_to='.$to;
		    $this->processNew($separateIntegrationData, $endPoint);
			if ($this->responseCode != 200) {
				echo "[ ERROR ] Erro httpcode: $this->responseCode ao chamar $endPoint\n";
				return false;
			}
		    $products = json_decode($this->result);
		    foreach ($products->data as $skus) {
		        foreach ($skus as $sku) {
		            $data = [
		                'product_id' => $sku,
		                'status_int' => self::TO_INTEGRATE,
		                'int_to'     => $int_to
		            ];
		
		            $this->model_catalogs->createVtexCatalogs($data);
		        }
		    }
		
		    if ($to < $products->range->total) {
		        $from += self::REGISTERS;
		        $to   += self::REGISTERS;
			}
			else {
				$maisItens = false;
			}
		}

        return true;
    }

    public function getProducts($int_to, $first_product_id, $last_product_id)
    {
		$vtex_weight_in_grams    = $this->model_settings->getStatusbyName('vtex_weight_in_grams');
    	$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);
        $getProducts             = $this->model_catalogs->getProductsFromVtexCatalog(self::TO_INTEGRATE, $int_to, $first_product_id, $last_product_id);
        $integrate_products_that_exist_in_other_catalogs = $this->catalog['integrate_products_that_exist_in_other_catalogs'];

        if (!$getProducts) {
        	echo "Nenhum produto de ".$int_to." para fazer a integração\n";
            return true;
        }

        foreach ($getProducts as $product) {
            $sku_vtex = $product['product_id'];
            echo "[  INFO ] product_id=$sku_vtex\n";

            $endPoint = 'api/catalog_system/pvt/sku/stockkeepingunitbyid/'.$sku_vtex;
            $this->processNew($separateIntegrationData, $endPoint);
            
			if ($this->responseCode !== 200) {
				echo '[ ERROR ] Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint."\n";
				continue;
			}
			if (!$this->result) {
            	$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
                continue;
            }
			
			$productData = json_decode($this->result);
			
			// Criar a categoria se não existir 
			$category = "";
			$lastLeaf = "";
			foreach (json_decode(json_encode($productData->ProductCategories), true) as $key => $value) {
				$category = $value."/".$category;
				if ( $lastLeaf == '') {
					$lastLeaf = $key;
				}
			}
			$category = rtrim($category,"/");

			$cat_temp = explode("/", rtrim($productData->ProductCategoryIds,"/"));
			$category_marketplace = end($cat_temp);
			
			if (!isset($productData->AlternateIds->RefId)) {
				$data_vtex = array(
            		'status_int' => self::INACTIVE,
            		'reason' => 'Sem RefId',
            		'category' => $category   
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
				echo '[ ERROR ] SKU VTEX '.$sku_vtex.' sem RefId - ignorando'."\n";
				continue;
			}

            // O SKU deve conter em outro produto de catálogo
            if ($integrate_products_that_exist_in_other_catalogs) {
                // Encontrar o mesmo produto em outro catálogo pro RefId
                $different_catalogs = $this->model_products_catalog->getProductsCatalogByRefIdAndDifferentCatalogId($productData->AlternateIds->RefId, $this->catalog['id']);

                // SKU ainda não existe em algum produto de catálogo
                if (empty($different_catalogs)) {
                    $data_vtex = array(
                        'ref_id' => $productData->AlternateIds->RefId,
                        'status_int' => self::INACTIVE,
                        'reason' => 'SKU não contém em outros catálogos',
                        'category' => $category
                    );
                    $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
                    echo '[ ERROR ] SKU VTEX '.$sku_vtex.' não contém em outros catálogos - ignorando'."\n";
                    continue;
                }
            }
			
			if (!isset($productData->AlternateIds->Ean)) {
				$data_vtex = array(
					'ref_id' => $productData->AlternateIds->RefId,
            		'status_int' => self::INACTIVE,
            		'reason' => 'Sem Código de Barra',
            		'category' => $category
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
				echo '[ ERROR ] SKU VTEX '.$sku_vtex.' sem EAN - ignorando'."\n";
				continue;
			}
			
			if ((!$productData->IsActive) || (!$productData->IsProductActive)) { // se o produto não está ativo....
				$data_vtex = array(
				    'ref_id' => $productData->AlternateIds->RefId,
            		'status_int' => self::INACTIVE,
            		'reason' => (!$productData->IsActive) ? "Sku Inativo" : "Produto Inativo",
            		'category' => $category
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
			    echo "[ ERROR ] SKU VTEX ". $sku_vtex." - inativo. Não importado\n";
				continue;
			}
			
			// buscando o produto. 
			if (isset($productData->ProductIsVisible)) {
				if (!$productData->ProductIsVisible) {
					$data_vtex = array(
						'ref_id' => $productData->AlternateIds->RefId,
	            		'status_int' => self::INACTIVE, 
	            		'reason' => 'Nao Visivel - Sku',
	            		'category' => $category
					); 
	                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
					echo '[ ERROR ] SKU VTEX '.$productData->ProductId.' SKU '.$sku_vtex.' Invisivel - ignorando'."\n";
					continue;
				}
			} else {
				$endPoint = 'api/catalog/pvt/product/'.$productData->ProductId;
	            $this->processNew($separateIntegrationData, $endPoint);
				if ($this->responseCode !== 200) {
					echo '[ ERROR ] Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint."\n";
					continue;
				}
				if (!$this->result) {
	            	$data_vtex = array(
	            		'status_int' => self::NOT_INTEGRATED, 
	            		'ref_id' => $productData->AlternateIds->RefId,
	            		'reason' => 'erro ao ler o produto '.$productData->ProductId,
					); 
	                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
	                continue;
	            }
				$productVtexData = json_decode($this->result);
				if (!$productVtexData->IsVisible) {
					$data_vtex = array(
						'ref_id' => $productData->AlternateIds->RefId,
	            		'status_int' => self::INACTIVE, 
	            		'reason' => 'Nao Visivel - Product',
	            		'category' => $category
					); 
	                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
					echo '[ ERROR ] SKU VTEX '.$productData->ProductId.' SKU '.$sku_vtex.' Invisivel - ignorando'."\n";
					continue;
				}
			}

			// pegamos o preço
			$tradepolicy = 1;
			$url = 'https://api.vtex.com/'.$separateIntegrationData->accountName.'/pricing/prices/'.$sku_vtex.'/computed/'.$tradepolicy;

            $this->processURL($separateIntegrationData, $url);
			if ($this->responseCode == 404) { // ainda não tem preço cadastrado, então não trago este produto
				$data_vtex = array(
					'ref_id' => $productData->AlternateIds->RefId,
            		'status_int' => self::INACTIVE, 
            		'reason' => 'Sem preco computado na politica '.$tradepolicy,
            		'category' => $category
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
			    echo "[ ERROR ] SKU VTEX ". $sku_vtex." - sem preço. Não importado\n";
				continue;
			}
			if ($this->responseCode !== 200) {
				echo '[ ERROR ] Erro httpcode: '.$this->responseCode.' ao chamar '.$url."\n";
				continue;
			}

            $originalprice = null;
			$value = json_decode($this->result);
            if (is_object($value)) {
				$price = $value->sellingPrice;
				$originalprice = $value->listPrice;
            } else {
                $price = 0;
            }
			
			if ($price == 0) { // ainda não tem preço cadastrado, então não trago este produto
				$data_vtex = array(
					'ref_id' => $productData->AlternateIds->RefId,
            		'status_int' => self::INACTIVE,
            		'reason' => 'Preco zerado',
            		'category' => $category
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
			    echo "[ ERROR ] SKU VTEX ". $sku_vtex." - sem preço. Não importado\n";
				continue;
			}

			$price_min = $this->catalog['price_min'] ?? null;
			$price_max = $this->catalog['price_max'] ?? null;

			if(!is_null($price_min) && $price <= $price_min){
				$data_vtex = array(
					'ref_id' => $productData->AlternateIds->RefId,
            		'status_int' => self::INACTIVE,
            		'reason' => 'Preço menor que o valor estipulado na trava de preço',
            		'category' => $category
				);
				$this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
				echo "[ ERROR ] SKU VTEX ". $sku_vtex." - tem o valor de $price e o mínimo permitido é $price_min. Não importado\n";
				continue;
			}

			if(!is_null($price_max) && $price >= $price_max){
				$data_vtex = array(
					'ref_id' => $productData->AlternateIds->RefId,
            		'status_int' => self::INACTIVE,
            		'reason' => 'Preço maior que o valor estipulado na trava de preço',
            		'category' => $category
				);
				$this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
				echo "[ ERROR ] SKU VTEX ". $sku_vtex." - tem o valor de $price e o máximo permitido é $price_max. Não importado\n";
				continue;
			}

			$productExists = $this->model_products_catalog->getProductsByEANandSkuId($productData->AlternateIds->Ean, $sku_vtex, $this->catalog['id']);
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

			// Caso o produto já exista, então verifica direto na catalog_product_image.
			// Se não, irá criar na product_image e posteriormente renomear para a catalog.
			$asset_dir = (count($productExists) > 0) ? 'catalog_product_image' : 'product_image';
			$folder = 'assets/images/' . $asset_dir . '/' . $image;

			$principal_image = '';
			$cnt = 0;
			$imagesPaths = [];
			$anyImgToBucket = false;
			foreach ($productData->Images as $images) {
				// Buusca a URL da imagem.
				$url = $images->ImageUrl;
				
				$img = $folder . '/' . $images->FileId . '.jpg';
				$imagesPaths[] = $img;

				// Acerta a url tirando os caracteres especiais
				$fileUrl = mb_convert_encoding($url, 'HTML-ENTITIES', "UTF-8");
				if ($fileUrl != $url) {
					$url = $fileUrl;
				}

				// Verifica se a imagem baixada já existe e está ok.
				$is_image = false;
				if($this->bucket->objectExists($img)['success']){
					$is_image = true;
				}

				$addedToBucket = false;
				// Não existe ou não está ok, então baixa novamente.
				if (!$is_image) {
					$file = file_get_contents($url);
					if($file){
						$this->bucket->sendFileToObjectStorage($file, $img);
						$addedToBucket = true;
						$anyImgToBucket = true;
					}
				}

				// Adiciona ao count e seta o principal image.
				$cnt++;
				if ($principal_image == '' || $addedToBucket) {
					$principal_image = $this->bucket->getAssetUrl('assets/images/' . $asset_dir . '/' . $image) . '/' . $images->FileId . '.jpg';
				}

				if ($cnt >= self::MAX_IMAGES) {
					break;
				}
			}

			$bucketImages = $this->bucket->getFinalObject($folder);
			foreach ($bucketImages['contents'] as $bucketImage) {
				$bucketImage = $folder . $bucketImage['key'];
				if (in_array($bucketImage, $imagesPaths)) {
					continue;
				}
				$this->bucket->deleteObject($bucketImage);
				echo "Deletando imagem: $bucketImage. Imagem não presente no catalogo\n";
			}

			if ((count($productExists) == 0) && ($principal_image == '')) {
				$data_vtex = array(
					'ref_id' => $productData->AlternateIds->RefId,
            		'status_int' => self::INACTIVE, 
            		'reason' => 'Sem Imagem',
            		'category' => $category
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
			    echo "[ ERROR ] Produto sem imagem nunca importado EAN: ".$productData->AlternateIds->Ean." REF.ID ".$productData->AlternateIds->RefId." não importado\n";
				continue;
			}

			if ($category_marketplace != '') {  // jeito novo 
				$category_mkt = $this->model_categorias_marketplaces->getDataByCategoryMktId($int_to, $category_marketplace);
				if ($category_mkt) {
					$category_id = $category_mkt['category_id'];
				}
				else {				
					$data_vtex = array(
						'ref_id' => $productData->AlternateIds->RefId,
						'status_int' => self::INACTIVE,
						'reason' => 'Categoria id '.$category_marketplace.' ainda não baixada ou inativa na Vtex',
						'category' => $category
					); 
					$this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
					echo "[ ERROR ] SKU VTEX ". $sku_vtex." - Categoria id $category_marketplace ainda não baixada ou inativa na Vtex\n";
					continue;
				}
			} else { // modelo antigo 
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
					$this->model_categorias_marketplaces->replace($data_cat_mkt);
				}
			} 

			// Crio a marca se não existir 
			$brand_id= $this->model_brands->getBrandbyName($productData->BrandName);
			if (!$brand_id) {
				$brand = array('name' => $productData->BrandName, 'active' => $productData->IsBrandActive);
				$brand_id= $this->model_brands->create($brand);
			}		

			// finalmente crio o produto de catálogo 
			$divideby=1;
			if( $vtex_weight_in_grams) {
				$divideby=1000;
			}
			
			if (is_null($originalprice)) {
                $originalprice = $price;
            }
			$isBucket = 0;
			if(count($productExists)==0 || $productExists[0]['is_on_bucket'] || $anyImgToBucket){
				$isBucket = 1;
			} else {
				// Não conseguiu migrar as imagens.
				$isBucket = 0;
			}
            $data = [
                'EAN'              		=> $productData->AlternateIds->Ean,
                'name'             		=> $productData->NameComplete,
                'status'           		=> empty($principal_image) ? 2 : 1,
                'has_variants'    		=> '', 
                'price'            		=> number_format($price,2,'.',''),
                'brand_code'       		=> $productData->ManufacturerCode, 
                'net_weight'       		=> number_format((is_null($productData->RealDimension->realWeight)) ? (float)$productData->Dimension->weight / $divideby : (float)$productData->RealDimension->realWeight / $divideby,3,'.',''),
                'gross_weight'     		=> number_format((float)$productData->Dimension->weight / $divideby,3,'.',''),
                'width'            		=> number_format((float)$productData->Dimension->width,3,'.',''),
                'height'           		=> number_format((float)$productData->Dimension->height,3,'.',''),
                'length'           		=> number_format((float)$productData->Dimension->length,3,'.',''),
                'products_package' 		=> number_format(1,3,'.',''),
                'brand_id'         		=> $brand_id,
                'category_id'     		=> $category_id,
                'warranty' 		   		=> 0, 
                'NCM'			   		=> is_null($productData->TaxCode)? '': $productData->TaxCode,
                'origin'           		=> 0,
                'principal_image'  		=> $principal_image, 
                'image'			   		=> $image,
                'prd_principal_id' 		=> null,
                'variant_id'	   		=> null,
                'description'      		=> $productData->ProductDescription,
                'attribute_value_id'	=> '',
				'ref_id'              	=> $productData->AlternateIds->RefId,
				'mkt_sku_id' 	    	=> $sku_vtex,
				'actual_width' 			=> number_format((float)$productData->RealDimension->realWidth,3,'.',''),
    			'actual_height' 		=> number_format((float)$productData->RealDimension->realHeight,3,'.',''),
    			'actual_depth' 			=> number_format((float)$productData->RealDimension->realLength,3,'.',''),
				'original_price'		=> number_format($originalprice,2,'.',''),
				'product_name'          => $productData->ProductName,
				'sku_name'             	=> $productData->SkuName,
				'mkt_product_id' 	    => $productData->ProductId,
				'is_on_bucket'			=> $isBucket
            ];
			//colocar o  refid no products_catlogo
			//quando inativar o product_catalog, inativar todos
			//se sumir, inativar os que sumram
			//colocar o refid no vtex_catalogo para facilitar a função acima. 
			 
            if (count($productExists) > 0) {
				$lido = $productExists[0];
				unset($lido['id']);
				unset($lido['date_create']);
				unset($lido['date_update']);
				unset($lido['reason']);
				unset($lido['last_inactive_date']);
				if ($lido == $data) {
					echo "[  INFO ] skipped \n";
					$this->model_products_catalog->associateIfNotExist( $productExists[0]['id'],$this->catalog['id']);
					$success= true; 
				}
				elseif (empty(array_diff($data,$lido)) && empty(array_diff($lido,$data))) {
                    echo "[  INFO ] The same. Skipped \n";
					$this->model_products_catalog->associateIfNotExist( $productExists[0]['id'],$this->catalog['id']);
					$success= true; 
				}
				else {
                    echo "[SUCCESS] updated \n";
					$success = $this->model_products_catalog->update($data, $productExists[0]['id'],  array($this->catalog['id']));
				} 
            } else {
                echo "[SUCCESS] created \n";
                $success = $this->model_products_catalog->create($data, array($this->catalog['id']));
            }
			
            if (!$success) {
            	echo "[ ERROR ] Produto ". $sku_vtex." - apresentou erro no banco de dados ".print_r($this->db->error(),true)."\n";
            	$data_vtex = array(
            		'status_int' => self::NOT_INTEGRATED
				); 
                $this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
				die;
            }
			else {	
				$data_vtex = array(
            		'status_int' => self::INTEGRATED, 
            		'ref_id'=> $productData->AlternateIds->RefId
				); 
				$this->model_catalogs->updateProductFromVtexCatalog($data_vtex, $sku_vtex, $int_to);
			}
			
			// checa se o mesmo EAN está em outro Catálogo. 
			if (!$this->ean_duplicate) { // somente se estiver não estiver ativo verifico se está duplicado
				$myeans = $this->model_products_catalog->getProductsByEAN($data['EAN']);
				if (count($myeans) > 1) {
                    echo "[SUCCESS] duplicated EAN \n";
					foreach($myeans as $myean) {
						$this->model_products_catalog->updateSimple(array('status'=>4),$myean['id']);
						echo "[ ERROR ] EAN: ".$myean['EAN']." VTEX SKU ".$myean['mkt_sku_id']." DUPLICADO entre catálogos. Colocando em DISABLE ".$myean['id']."\n";
					}
				}
			}
        }

        return true;
    }

	public function inactiveProducts($int_to, $first_product_id, $last_product_id): bool
    {
		echo "[  INFO ] Inativando produtos de catálogo que não estão mais ativos na Vtex\n";
		$products_vtex = $this->model_catalogs->getProductsFromVtexCatalog(self::INACTIVE, $int_to, $first_product_id, $last_product_id);
		
        if (!$products_vtex) {
        	echo "[  INFO ] Nenhum produto de ".$int_to." para inativar\n";
            return true;
        }
		foreach ($products_vtex as $product_vtex) {
            echo "[  INFO ] product_id=$product_vtex[product_id]\n";
			if (is_null($product_vtex['ref_id'])) {
				continue;
			}
			$products_catalog = $this->model_products_catalog->getProductsCatalogByRefIdAndCatalogId($product_vtex['ref_id'], $this->catalog['id']);
			if (count($products_catalog)==0) {
				continue; 
			}
			foreach($products_catalog as $product_catalog ) {
				if ($product_catalog['status'] == 1) {
                    echo "[  INFO ] $product_vtex[reason] [$product_catalog[id]]\n";
						
					$offset = 0;
					$limit  = 50;
					while (true) {
						$products = $this->model_products_catalog->getProductsByProductCatalogId($product_catalog['id'], $offset, $limit);
						if (!$products) {  // acabou ou deu erro....
							break;
						}
						$offset += $limit;
						foreach($products as $product) {
							if ($product['status'] == 1) {
								// Inativo o produto
								get_instance()->log_data('products','edit_before',json_encode($product),"I");
								$data = array('status' => 2);
								$this->model_products->update($data,$product['id']);
								$data['id'] = $product['id'];
								$data['reason'] = $product_vtex['reason'];
								get_instance()->log_data('products','edit_after',json_encode($data),"I");
							}
						}
					}
					// Agora inativo o catálogo
					$this->model_products_catalog->updateSimple(
						array(
							'status' => 2,
							'reason' => $product_vtex['reason'],
							'last_inactive_date' => date('Y-m-d H:i:s'),
						), $product_catalog['id']
					);
				}
				else {  // ja estava inativo antes. 
					if (is_null($product_catalog['reason'])) {  //ainda não gravava a reason
						$this->model_products_catalog->updateSimple(
							array(
								'reason' => $product_vtex['reason'],
								'last_inactive_date' =>  $product_catalog['date_update'],  // a ultima vez que alterou provavelmente foi quando inativou
							), $product_catalog['id']
						);
					}
				}
				
			}
		}

        return true;
	}

    private function setCatalogJob(string $int_to)
    {
        $limit = 100000;
        $count = $this->model_catalogs->getCountVtexGetCatalog($int_to);
        $columns = ceil($count / $limit);
        $first_product_id = 0;

        for ($x = 0; $x < $columns; $x++) {
            $offset = $x * $limit;
            $last_product_id = $this->model_catalogs->getProductIdVtexGetCatalogPerLimit($int_to, $limit, $offset);
            $last_product_id = $last_product_id['product_id'];

            $more_minute = ($x + 1) * 10;
            $date_start = date('Y-m-d H:i:s', strtotime("+$more_minute minutes"));
            $this->model_job_schedule->create(array(
                'module_path'   => "SellerCenter/Vtex/Catalog",
                'module_method' => 'run',
                'params'        => "$int_to $first_product_id $last_product_id",
                'status'        => 0,
                'finished'      => 0,
                'date_start'    => $date_start,
                'date_end'      => null,
                'server_id'     => 0
            ));

            echo "[CREATED] SellerCenter/Vtex/Catalog ([$date_start] - $int_to $first_product_id $last_product_id)\n";
            $first_product_id = $last_product_id;
        }
    }
}
