<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
defined('BASEPATH') OR exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

class SyncProductNew extends Main
{
    // Quantidade de produtos por página (máximo de 50);
	const REGISTERS = 49;
	
	var $integrationData;
	
	var $catalog;
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
 
        // $this->load->model('model_catalogs');
        // $this->load->model('model_products_catalog');
		$this->load->model('model_brands');
		$this->load->model('model_category');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_atributos_categorias_marketplaces');
    }

    function run($id=null,$int_to=null,$seller=null)
	{
		/* inicia o job */
		$this->setIdJob($id); 

		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__, $int_to . " " . $seller)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$int_to." ".$seller),"I");
		
		/* faz o que o job precisa fazer */
		if (!is_null($int_to)) {
			if (!is_null($seller)) {
				$retorno = $this->initIntegration($int_to, $seller);
			}
			else {
				echo "Informe o seller do marketplace para puxar o catálogo e seus produtos\n";
			}
		}
		else {
			echo "Informe o int_to do marketplace para puxar o catálogo e seus produtos\n";
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	private function initIntegration($int_to, $sellerId) {
		$this->integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($this->integrationData['auth_data']);
		$categories_root = $this->model_categorias_marketplaces->getCategoriesRootVtex(4);
		$sellers = $this->listSellerToIntegrate();
		$to = self::REGISTERS;
		foreach ($sellers as $seller) {
			if (($sellerId == $seller['import_seller_id']) || ($sellerId == "ALL")) {
				echo "Inicio integração: ". $seller['import_seller_id'] . PHP_EOL;
				foreach($categories_root as $category) {
					$array = explode('/', $category["nome"]);
					$tree_category = array();       
							
					$root = '';
					for ($j = 0; $j < count($array) - 1; $j++) {
						if ($root != '') $root .= "/";
						$root .= $array[$j];
						if ($root != '') {
							array_push($tree_category, $root);
						}
					}

					$categories_parents = '';
					foreach($tree_category as $node) {
						$category_node = $this->model_categorias_marketplaces->getCategoryVtexByName($node);
						$categories_parents .= $category_node['id'];
						$categories_parents .= ($categories_parents != '' ? '/' : '') ;
					}

					$this->syncSellerProducts($separateIntegrationData, $int_to, $seller['import_seller_id'], $categories_parents . $category['id'], 0, $to, $category);
				}
				$this->makeStoreIntegrated($seller);
				echo "Fim integração: ". $seller['import_seller_id'] . PHP_EOL;
			}
		}
	}

	private function listSellerToIntegrate() {
		// return $this->model_stores->getStoreToIntegrateVtex();
		return $this->model_stores->getStoreToIntegrateVtex(2);
	}

	private function makeStoreIntegrated($seller) {
		$this->model_stores->updateIntegrateStatus($seller['store_id'], 3);
	}

    public function syncSellerProducts($separateIntegrationData, $int_to, $sellerId, $category, $from, $to, $category_row)   // leio todos os ids dos produtos atrelados a este catálogo
    {
 		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$endPoint = 'api/catalog_system/pub/products/search?fq=sellerId:'.$sellerId.'&fq=C:/'. $category .'/&_from='.$from.'&_to='.$to;
		$this->processNew($separateIntegrationData, $endPoint);
		if ($this->responseCode == 429) {
			sleep(60);
			$this->processNew($separateIntegrationData, $endPoint);
		}
		if ($this->responseCode >= 300) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
		$products = json_decode($this->result, true);

		$this->amount = $this->amount + count($products);

		echo strval($this->amount) . " " . $sellerId . " - Category: " . $category_row['nome'] . " - Amount:  " . strval(count($products)) . " - " . strval($to) . " - " . strval($from) . PHP_EOL;

		$stores = $this->model_stores->getStoreBySellerId($sellerId);

		foreach ($products as $product) {
			echo 'Product id: ' . $product['productId'] . ' - ';
			$this->syncProduct($separateIntegrationData, $int_to, $stores, $sellerId, $product);
			echo PHP_EOL;
		}
		if (count($products) >= $to - $from) {
			$count = $to - $from + 1;
			$from = $to + 1;
			$to = $from + $count - 1; 
			$this->syncSellerProducts($separateIntegrationData, $int_to, $sellerId, $category, $from, $to, $category_row);
		}
	}
	
	private function syncProduct($separateIntegrationData, $int_to, $stores, $sellerId, $product) {
		$amout_items = count($product['items']);
		$productData = array();
		$prd_variants = array();
		$variant_num = 0;

		$product_folder = '';
		$keys = array_merge(range('A', 'Z'), range('a', 'z'));
	
		for ($i = 0; $i < 30; $i++) {
			$product_folder .= $keys[array_rand($keys)];
		}

		foreach ($product['items'] as $item) {
			$product_item = $this->getProduct($separateIntegrationData, $item['itemId']);

			// if ($product_item['IsActive'] === false) continue;

			if ($variant_num == 0) {
				$productData = $this->getProductData($separateIntegrationData, $int_to, $stores, $product, $product_item, $item);
				$attributes = $this->getAttributes($separateIntegrationData, $int_to, $product_item, $item);
			}

			$name = '';
			if ($this->isVariation($item)) {
				$variation = $this->getVariation($item);
				
				$productData['has_variants'] = $variation[0];

				$variant = array(
					'prd_id' => '',
					'variant' => $variant_num,
					'name' => $variation[1],
					'EAN' => $item['ean'],
					'sku' => $item['itemId'],
					'image' =>	$variant_num
				);

				array_push($prd_variants, $variant);

				$this->loadImages($product_folder, $item, true, $variant_num);
				if ($variant_num == 0) {
					$principal_image = $this->loadImages($product_folder, $item, false);
					$productData['principal_image'] = $principal_image;
					$productData['image'] = $product_folder;
				}

				$variant_num = $variant_num + 1;
			}
			else {
				$principal_image = $this->loadImages($product_folder, $item, false);
				$productData['principal_image'] = $principal_image;
				$productData['image'] = $product_folder;
			}
		}

		$exist_product = $this->model_products->getProductComplete($productData['sku'], $stores['company_id'], $stores['id']);

		if (!is_null($exist_product)) {
			echo "Já importado.";
			return; 
		}

		$product_id = $this->model_products->create($productData);
		echo "Importado.";

		foreach ($prd_variants as $prd_variant) {
			$prd_variant['prd_id'] = $product_id;
			$this->model_products->createvar($prd_variant);
		}

		foreach ($attributes as $attribute) {
			$attribute['id_product'] = $product_id;
			$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($attribute);
		}
		
	}

	private function isVariation($item) {
		$isVariation = false;
		if (array_key_exists('variations', $item)) {
			$isVariation = count($item['variations']) > 0;
		}
		return $isVariation;
	}

	private function getVariation($item) {
		$name = '';
		$value = '';

		foreach ($item['variations'] as $variation) {
			if ($name != '') $name .= ';';
			
			if (strtoupper($variation) == 'TAMANHO') {
				$name .= 'TAMANHO';
			}
			if (strtoupper($variation) == 'VOLTAGEM') {
				$name .= 'VOLTAGEM';
			}
			if (strtoupper($variation) == 'COR') {
				$name .= 'Cor';
			}
			if ($value != '') $value .= ';';
			$value .= $item[$variation][0];
		}

		return [$name, $value];
	}

	private function getAttributes($separateIntegrationData, $int_to, $product_item, $item) {
		$attributes = array();
		if (array_key_exists('SkuSpecifications', $product_item)) {
			foreach ($product_item['SkuSpecifications'] as $specification) {
				$attr = array(
					'id_product' => '',
					'id_atributo' => $specification['FieldId'],
					'valor' => $specification['FieldValues'][0],
					'int_to' => $int_to 
				);

				$isVariation = false;
				
				foreach ($item['variations'] as $variation) {
					if (strtoupper($specification['FieldName']) == strtoupper($variation)) {
						$isVariation = true;
					}
				}
				
				if (!$isVariation) {
					array_push($attributes, $attr);
				}
			}
		}
		return $attributes;
	}

	private function findBrandId($product)
	{
		// Crio a marca se não existir 
		$brand_id= $this->model_brands->getBrandbyName($product['brand']);
		if (!$brand_id) {
			$brand = array('name' => $product['brand'], 'active' => 1);
			$brand_id = $this->model_brands->create($brand);
		}

		return $brand_id;
	}

	private function findCategoryId($int_to, $product) 
	{
		$category = ltrim(rtrim(json_decode(json_encode($product['categories'][0]), true), "/"), "/");

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
				'id_integration' => $this->integrationData['id'], 
				'id' => $product['categoryId'],
				'nome' => $category, 
				'int_to' => $int_to
			); 
			$this->db->replace('categorias_todos_marketplaces', $data_cat_todos);
			$data_cat_mkt = array(
				'int_to' => $int_to,
				'category_id' => $category_id,
				'category_marketplace_id' => $product['categoryId']
			);
			$replace = $this->model_categorias_marketplaces->replace($data_cat_mkt);
		}

		return $category_id;
	}

	private function loadImages($product_folder, $product, $isVariation, $variant = null) {
		$image = $product_folder;
		
		$folder = FCPATH . 'assets/images/product_image/'.$image;
		if (!is_dir($folder)) {
			mkdir($folder);
		}

		if ($isVariation) {
			$folder .= '/'. $variant;

			if (!is_dir($folder)) {
				mkdir($folder);
			}
		}
		
		$principal_image = '';
		foreach ($product['images'] as $images) {
			$url = $images['imageUrl'];
			$img = $folder.'/'.$images['imageId'].'.jpg';
			if 	(!file_exists($img)) {
				file_put_contents($img, file_get_contents($url));
			}
			if ($principal_image=='') {
				$principal_image = base_url('assets/images/product_image/'.$image).'/'.$images['imageId'].'.jpg';
			}
		}

		return $principal_image;
	}

	private function getProduct($separateIntegrationData, $product_id)  
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		try {
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
				return array (
					"IsActive" => false
				);
			}

			$productData = json_decode($this->result, true);

			return $productData;
		} catch (Exception $e) {
			return array (
				"IsActive" => false
			);
		}
	}

	private function getProductData($separateIntegrationData, $int_to, $stores, $product, $product_item, $item) {
		$isVariation = $this->isVariation($item);
		$sku = $isVariation === true ? $product['productId'] : $product_item['Id'];
		$category_id = $this->findCategoryId($int_to, $product);
		$brand_id = $this->findBrandId($product);
		$ean = ''; 
		if ($isVariation !== true) {
			if (array_key_exists('ean', $item)) {
				if (!is_null($item['ean'])) {
					$ean = $item['ean'];
				}
			}
		}

		echo ' SKU: '. $sku;

		$product_data = array(
			'name' => $product['productName'],
			'sku' => $sku,
			'price' => 0,
			'qty' => 0,
			'image' => 	'',
			'principal_image' => '',
			'description' => $product['description'],
			'brand_id' => json_encode(array($brand_id)),
			'category_id' => json_encode(array($category_id)),
			'store_id' => $stores['id'],
			'status' => $product_item['IsActive'] === false ? 0 : 1,
			'EAN' => $ean,
			'codigo_do_fabricante' => '',
			'peso_liquido' => '',
			'peso_bruto' => intval($product_item['Dimension']['weight']) / 1000,
			'largura' => $product_item['Dimension']['width'],
			'altura' => $product_item['Dimension']['height'],
			'profundidade' => $product_item['Dimension']['length'],
			'garantia' => '',
			'NCM' => '',
			'origin' => $int_to,
			'CEST' => '',
			'FCI' => '',
			'company_id' => $stores['company_id'],
			'has_variants' => '',
			'category_imported' => '',
			'omnilogic_status' => 'IMPORTED'
		);	
		return $product_data;	
	}
}
