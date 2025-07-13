<?php

// require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";

class Product
{
    // Quantidade de produtos por página (máximo de 50);
	// const REGISTERS = 50;
	
	// var $integrationData;
	
	var $ci;
	var $amountVariation = 0;
	
    public function __construct()
    {
        // parent::__construct();

		
		// $this->load->model('model_category');
		// $this->load->model('model_categorias_marketplaces');
		
		// $this->load->model('model_stores');
		$this->ci =& get_instance();
		$this->ci->load->model('model_atributos_categorias_marketplaces');
		$this->ci->load->model('model_brands');
		$this->ci->load->model('model_products');
    }
	
	public function createWithStockKeepingUnit($separateIntegrationData, $int_to, $stockKeepingUnit, $stores) {
		$ean = '';
		if (array_key_exists('AlternateIds', $stockKeepingUnit)) {
			if (!is_null($stockKeepingUnit['AlternateIds'])) {

				if (array_key_exists('Ean', $stockKeepingUnit['AlternateIds'])) {
					if (!is_null($stockKeepingUnit['AlternateIds']['Ean'])) {
						$ean = $stockKeepingUnit['AlternateIds']['Ean'];
					}
				}

			}
		}

		$attr_variation = $this->hasVariation($separateIntegrationData, $int_to, $stockKeepingUnit);
		if (!$attr_variation[0]) {
			$productData = $this->getProductData($int_to, $stores, $stockKeepingUnit, false);
			$productData['EAN'] = $ean;
			$product_folder = $this->makeProductFolder();

			$principal_image = $this->loadImages($product_folder, $stockKeepingUnit, false);
			$productData['principal_image'] = $principal_image;
			$productData['image'] = $product_folder;

			$this->ci->model_products->create($productData);
		}
		else {
			$product = $this->ci->model_products->getProductComplete($stockKeepingUnit['ProductId'], $stores['company_id'], $stores['id']);
			$status = $stockKeepingUnit['IsActive'] ? 1 : 0;

			if (is_null($product)) {
				$productData = $this->getProductData($int_to, $stores, $stockKeepingUnit, true, $attr_variation[1]);

				$product_folder = $this->makeProductFolder();

				$variant = 0;
				$principal_image = $this->loadImages($product_folder, $stockKeepingUnit, false);
				$productData['principal_image'] = $principal_image;
				$productData['image'] = $product_folder;

				$prd_id = $this->ci->model_products->create($productData);
				if ($prd_id !== false) {
					$principal_image = $this->loadImages($product_folder, $stockKeepingUnit, true, $variant);
					$this->ci->model_products->createvar(array(
						"prd_id" => $prd_id,
						"variant" => $variant,
						"name" => $attr_variation[2],
						"sku" => $stockKeepingUnit['Id'],
						"price" => '',
						"qty" => '',
						"image" => $variant,
						"status" => $status,
						"EAN" => $ean,
						"codigo_do_fabricante" => ''
					));
				}
			}
			else {
				$variants = $this->ci->model_products->getVariants($product['id']);
				$variant = 0;
				$variant = $this->getNewVariant($variant, $variants);

				$principal_image = $this->loadImages($product['image'], $stockKeepingUnit, true, $variant);
				$this->ci->model_products->createvar(array(
					"prd_id" => $product['id'],
					"variant" => $variant,
					"name" => $attr_variation[2],
					"sku" => $stockKeepingUnit['Id'],
					"price" => '',
					"qty" => '',
					"image" => $variant,
					"status" => $status,
					"EAN" => $ean,
					"codigo_do_fabricante" => ''
				));
			}			
		}
	}

	private function getNewVariant($variant, $variants) {
		$exist = false;
		foreach ($variants as $var) {
			if ($variant == $var['variant']) {
				$exist = true;
			}
		}

		if ($exist === false) {
			return $variant;
		}
		else {
			$variant = $variant + 1;
			return $this->getNewVariant($variant, $variants);
		}
	}

	public function hasVariation($separateIntegrationData, $int_to, $stockKeepingUnit) {
		$endPoint = 'api/catalog/pvt/stockkeepingunit/'.$stockKeepingUnit['Id'].'/specification';
		$this->ci->processNew($separateIntegrationData, $endPoint);
		if ($this->ci->responseCode == 429) {
			sleep(60);
			$this->ci->processNew($separateIntegrationData, $endPoint);
		}
		if ($this->ci->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->ci->responseCode.' ao chamar '.$endPoint;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return [false];
		}
		if ($this->ci->result == "") return [false];

		$specifications = json_decode($this->ci->result, true);

		$isVariation = false;
		$name = '';
		$value = '';

		foreach ($specifications as $specification) {
			$attribute = $this->ci->model_atributos_categorias_marketplaces->getAtributoByAttrIdMkt($specification['FieldId'], $int_to);
			if ($attribute['variacao']) {
				$isVariation = true;
				$this->amountVariation = $this->amountVariation + 1;

				if ($name != '') $name .= ';';
					
				if (strtoupper($attribute['nome']) == 'TAMANHO') {
					$name .= 'TAMANHO';
				}
				if (strtoupper($attribute['nome']) == 'VOLTAGEM') {
					$name .= 'VOLTAGEM';
				}
				if (strtoupper($attribute['nome']) == 'COR') {
					$name .= 'Cor';
				}
				if ($value != '') $value .= ';';
				$value .= $specification['Text'];
			}
		}

		return [$isVariation, $name, $value];
	}

	private function makeProductFolder() {
		$product_folder = '';
		$keys = array_merge(range('A', 'Z'), range('a', 'z'));
	
		for ($i = 0; $i < 30; $i++) {
			$product_folder .= $keys[array_rand($keys)];
		}
		return $product_folder;
	}

	private function getProductData($int_to, $stores, $stockKeepingUnit, $isVariation, $has_variants = '') {
		$sku = $isVariation === true ? $stockKeepingUnit['ProductId'] : $stockKeepingUnit['Id'];
		$brand_id = $this->findBrandId($stockKeepingUnit['BrandName']);
		//$category_id = $this->findCategoryId($int_to, $product);
		$category_id = '';
		$status = $stockKeepingUnit['IsActive'] ? 1 : 0;
		$ean = ''; 

		$product_data = array(
			'name' => $stockKeepingUnit['ProductName'],
			'sku' => $sku,
			'price' => 0,
			'qty' => 0,
			'image' => 	'',
			'principal_image' => '',
			'description' => $stockKeepingUnit['ProductDescription'],
			'brand_id' => json_encode(array($brand_id)),
			'category_id' => "", //json_encode(array($category_id)),
			'store_id' => $stores['id'],
			'status' => $status,
			'EAN' => $ean,
			'codigo_do_fabricante' => '',
			'peso_liquido' => '',
			'peso_bruto' => intval($stockKeepingUnit['Dimension']['weight']) / 1000,
			'largura' => $stockKeepingUnit['Dimension']['width'],
			'altura' => $stockKeepingUnit['Dimension']['height'],
			'profundidade' => $stockKeepingUnit['Dimension']['length'],
			'garantia' => '',
			'NCM' => '',
			'origin' => $int_to,
			'CEST' => '',
			'FCI' => '',
			'company_id' => $stores['company_id'],
			'has_variants' => $has_variants,
			'category_imported' => '',
			'omnilogic_status' => 'IMPORTED'
		);	
		return $product_data;	
	}

	private function findBrandId($brandName)
	{
		// Crio a marca se não existir 
		$brand_id= $this->ci->model_brands->getBrandbyName($brandName);
		if (!$brand_id) {
			$brand = array('name' => $brandName, 'active' => 1);
			$brand_id = $this->ci->model_brands->create($brand);
		}

		return $brand_id;
	}

	private function findCategoryId($int_to, $product) 
	{
		// explode('/', $stockKeepingUnit['CategoriesFullPath'][0])
		$category = ltrim(rtrim(json_decode(json_encode($product['categories'][0]), true), "/"), "/");

		// echo "\ncategoria: ".$category." id: ".$lastLeaf."\n";
		$category_id = $this->ci->model_category->getcategorybyName($category);
		if (!$category_id) {
			$data_cat = array (
				'name' => $category,
				'active' => 1,
				'tipo_volume_id' => null, 
				'days_cross_docking' => 0,
				'qty_products' => 0, 
			); 
			$this->ci->model_category->create($data_cat); 
			$category_id = $this->ci->db->insert_id();
			// echo "category id ".$category_id."\n";
			$data_cat_todos = array(
				'id_integration' => $this->integrationData['id'], 
				'id' => $product['categoryId'],
				'nome' => $category, 
				'int_to' => $int_to
			); 
			$this->ci->db->replace('categorias_todos_marketplaces', $data_cat_todos);
			$data_cat_mkt = array(
				'int_to' => $int_to,
				'category_id' => $category_id,
				'category_marketplace_id' => $product['categoryId']
			);
			$replace = $this->ci->model_categorias_marketplaces->replace($data_cat_mkt);
		}

		return $category_id;
	}

	private function loadImages($product_folder, $stockKeepingUnit, $isVariation, $variant = null) {
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
		foreach ($stockKeepingUnit['Images'] as $images) {
			$url = $images['ImageUrl'];
			$img = $folder.'/'.$images['FileId'].'.jpg';
			if 	(!file_exists($img)) {
				file_put_contents($img, file_get_contents($url));
			}
			if ($principal_image=='') {
				$principal_image = base_url('assets/images/product_image/'.$image).'/'.$images['FileId'].'.jpg';
			}
		}

		return $principal_image;
	}

    // private function createWithStockKeepingUnit($product, $store) {
	// 	$productData = array();
	// 	$prd_variants = array();
	// 	$variant_num = 0;

	// 	$product_folder = '';
	// 	$keys = array_merge(range('A', 'Z'), range('a', 'z'));
	
	// 	for ($i = 0; $i < 30; $i++) {
	// 		$product_folder .= $keys[array_rand($keys)];
	// 	}

	// 	foreach ($product['items'] as $item) {
	// 		$product_item = $this->getProduct($separateIntegrationData, $item['itemId']);

	// 		if ($product_item['IsActive'] === false) continue;

	// 		if ($variant_num == 0) {
	// 			$productData = $this->getProductData($separateIntegrationData, $int_to, $stores, $product, $product_item, $item);
	// 			$attributes = $this->getAttributes($separateIntegrationData, $int_to, $product_item, $item);
	// 		}

	// 		$name = '';
	// 		if ($this->isVariation($item)) {
	// 			$variation = $this->getVariation($item);
				
	// 			$productData['has_variants'] = $variation[0];

	// 			$variant = array(
	// 				'prd_id' => '',
	// 				'variant' => $variant_num,
	// 				'name' => $variation[1],
	// 				'EAN' => $item['ean'],
	// 				'sku' => $item['itemId'],
	// 				'image' =>	$variant_num
	// 			);

	// 			array_push($prd_variants, $variant);

	// 			$this->loadImages($product_folder, $item, true, $variant_num);
	// 			if ($variant_num == 0) {
	// 				$principal_image = $this->loadImages($product_folder, $item, false);
	// 				$productData['principal_image'] = $principal_image;
	// 				$productData['image'] = $product_folder;
	// 			}

	// 			$variant_num = $variant_num + 1;
	// 		}
	// 		else {
	// 			$principal_image = $this->loadImages($product_folder, $item, false);
	// 			$productData['principal_image'] = $principal_image;
	// 			$productData['image'] = $product_folder;
	// 		}
	// 	}

	// 	$exist_product = $this->model_products->getProductComplete($productData['sku'], $stores['company_id'], $stores['id']);

	// 	if (!is_null($exist_product)) return;

	// 	$product_id = $this->model_products->create($productData);

	// 	foreach ($prd_variants as $prd_variant) {
	// 		$prd_variant['prd_id'] = $product_id;
	// 		$this->model_products->createvar($prd_variant);
	// 	}

	// 	foreach ($attributes as $attribute) {
	// 		$attribute['id_product'] = $product_id;
	// 		$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($attribute);
	// 	}
		
	// }

	// private function isVariation($item) {
	// 	$isVariation = false;
	// 	if (array_key_exists('variations', $item)) {
	// 		$isVariation = count($item['variations']) > 0;
	// 	}
	// 	return $isVariation;
	// }

	// private function getVariation($item) {
	// 	$name = '';
	// 	$value = '';

	// 	foreach ($item['variations'] as $variation) {
	// 		if ($name != '') $name .= ';';
			
	// 		if (strtoupper($variation) == 'TAMANHO') {
	// 			$name .= 'TAMANHO';
	// 		}
	// 		if (strtoupper($variation) == 'VOLTAGEM') {
	// 			$name .= 'VOLTAGEM';
	// 		}
	// 		if (strtoupper($variation) == 'COR') {
	// 			$name .= 'Cor';
	// 		}
	// 		if ($value != '') $value .= ';';
	// 		$value .= $item[$variation][0];
	// 	}

	// 	return [$name, $value];
	// }

	// private function getAttributes($separateIntegrationData, $int_to, $product_item, $item) {
	// 	$attributes = array();
	// 	if (array_key_exists('SkuSpecifications', $product_item)) {
	// 		foreach ($product_item['SkuSpecifications'] as $specification) {
	// 			$attr = array(
	// 				'id_product' => '',
	// 				'id_atributo' => $specification['FieldId'],
	// 				'valor' => $specification['FieldValues'][0],
	// 				'int_to' => $int_to 
	// 			);

	// 			$isVariation = false;
				
	// 			foreach ($item['variations'] as $variation) {
	// 				if (strtoupper($specification['FieldName']) == strtoupper($variation)) {
	// 					$isVariation = true;
	// 				}
	// 			}
				
	// 			if (!$isVariation) {
	// 				array_push($attributes, $attr);
	// 			}
	// 		}
	// 	}
	// 	return $attributes;
	// }

	// private function findBrandId($product)
	// {
	// 	// Crio a marca se não existir 
	// 	$brand_id= $this->model_brands->getBrandbyName($product['brand']);
	// 	if (!$brand_id) {
	// 		$brand = array('name' => $product['brand'], 'active' => 1);
	// 		$brand_id = $this->model_brands->create($brand);
	// 	}

	// 	return $brand_id;
	// }

	// private function findCategoryId($int_to, $product) 
	// {
	// 	$category = ltrim(rtrim(json_decode(json_encode($product['categories'][0]), true), "/"), "/");

	// 	// echo "\ncategoria: ".$category." id: ".$lastLeaf."\n";
	// 	$category_id = $this->model_category->getcategorybyName($category);
	// 	if (!$category_id) {
	// 		$data_cat = array (
	// 			'name' => $category,
	// 			'active' => 1,
	// 			'tipo_volume_id' => null, 
	// 			'days_cross_docking' => 0,
	// 			'qty_products' => 0, 
	// 		); 
	// 		$this->model_category->create($data_cat); 
	// 		$category_id = $this->db->insert_id();
	// 		// echo "category id ".$category_id."\n";
	// 		$data_cat_todos = array(
	// 			'id_integration' => $this->integrationData['id'], 
	// 			'id' => $product['categoryId'],
	// 			'nome' => $category, 
	// 			'int_to' => $int_to
	// 		); 
	// 		$this->db->replace('categorias_todos_marketplaces', $data_cat_todos);
	// 		$data_cat_mkt = array(
	// 			'int_to' => $int_to,
	// 			'category_id' => $category_id,
	// 			'category_marketplace_id' => $product['categoryId']
	// 		);
	// 		$replace = $this->model_categorias_marketplaces->replace($data_cat_mkt);
	// 	}

	// 	return $category_id;
	// }

	// private function loadImages($product_folder, $product, $isVariation, $variant = null) {
	// 	$image = $product_folder;
		
	// 	$folder = FCPATH . 'assets/images/product_image/'.$image;
	// 	if (!is_dir($folder)) {
	// 		mkdir($folder);
	// 	}

	// 	if ($isVariation) {
	// 		$folder .= '/'. $variant;

	// 		if (!is_dir($folder)) {
	// 			mkdir($folder);
	// 		}
	// 	}
		
	// 	$principal_image = '';
	// 	foreach ($product['images'] as $images) {
	// 		$url = $images['imageUrl'];
	// 		$img = $folder.'/'.$images['imageId'].'.jpg';
	// 		if 	(!file_exists($img)) {
	// 			file_put_contents($img, file_get_contents($url));
	// 		}
	// 		if ($principal_image=='') {
	// 			$principal_image = base_url('assets/images/product_image/'.$image).'/'.$images['imageId'].'.jpg';
	// 		}
	// 	}

	// 	return $principal_image;
	// }

	// private function getProduct($separateIntegrationData, $product_id)  
	// {
	// 	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
	// 	try {
	// 		$endPoint = 'api/catalog_system/pvt/sku/stockkeepingunitbyid/'.$product_id;
	// 		$this->processNew($separateIntegrationData, $endPoint);
			
	// 		if ($this->responseCode == 429) {
	// 			sleep(60);
	// 			$this->processNew($separateIntegrationData, $endPoint);
	// 		}
	// 		if ($this->responseCode !== 200) {
	// 			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
	// 			echo $erro."\n";
	// 			$this->log_data('batch',$log_name, $erro ,"E");
	// 			return array (
	// 				"IsActive" => false
	// 			);
	// 		}

	// 		$productData = json_decode($this->result, true);

	// 		return $productData;
	// 	} catch (Exception $e) {
	// 		return array (
	// 			"IsActive" => false
	// 		);
	// 	}
	// }

	// private function getProductData($separateIntegrationData, $int_to, $stores, $product, $product_item, $item) {
	// 	$isVariation = $this->isVariation($item);
	// 	$sku = $isVariation === true ? $product['productId'] : $product_item['Id'];
	// 	$category_id = $this->findCategoryId($int_to, $product);
	// 	$brand_id = $this->findBrandId($product);
	// 	$ean = ''; 
	// 	if ($isVariation !== true) {
	// 		if (array_key_exists('ean', $item)) {
	// 			if (!is_null($item['ean'])) {
	// 				$ean = $item['ean'];
	// 			}
	// 		}
	// 	}

	// 	echo ' SKU: '. $sku;

	// 	$product_data = array(
	// 		'name' => $product['productName'],
	// 		'sku' => $sku,
	// 		'price' => 0,
	// 		'qty' => 0,
	// 		'image' => 	'',
	// 		'principal_image' => '',
	// 		'description' => $product['description'],
	// 		'brand_id' => json_encode(array($brand_id)),
	// 		'category_id' => json_encode(array($category_id)),
	// 		'store_id' => $stores['id'],
	// 		'status' => 0,
	// 		'EAN' => $ean,
	// 		'codigo_do_fabricante' => '',
	// 		'peso_liquido' => '',
	// 		'peso_bruto' => $product_item['Dimension']['weight'],
	// 		'largura' => $product_item['Dimension']['width'],
	// 		'altura' => $product_item['Dimension']['height'],
	// 		'profundidade' => $product_item['Dimension']['length'],
	// 		'garantia' => '',
	// 		'NCM' => '',
	// 		'origin' => $int_to,
	// 		'CEST' => '',
	// 		'FCI' => '',
	// 		'company_id' => $stores['company_id'],
	// 		'has_variants' => '',
	// 		'category_imported' => '',
	// 		'omnilogic_status' => 'IMPORTED'
	// 	);	
	// 	return $product_data;	
	// }

	protected function processNew($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
        $this->accountName = $separateIntegrationData->accountName;
        if (property_exists($separateIntegrationData,'suffixDns')) {
	        if (!is_null($separateIntegrationData->suffixDns)) {
	            $this->setSuffixDns($separateIntegrationData->suffixDns);
	        }
		}

        $this->header = [
            'content-type: application/json',
            'accept: application/json',
            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
        ];

        $url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($separateIntegrationData, $endPoint, $method, $data);
		}
		if ($this->responseCode == 504) {
		    echo "Deu Timeout httpcode=504. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($separateIntegrationData, $endPoint, $method, $data);
		}
        if ($this->responseCode == 503) {
		    echo "Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($separateIntegrationData, $endPoint, $method, $data);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}

        return;
    }
}
