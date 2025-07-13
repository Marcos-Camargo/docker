<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsConectala.php";
require APPPATH . "controllers/BatchC/ViaVarejo/ViaOAuth2.php";
require APPPATH . "controllers/BatchC/ViaVarejo/ViaIntegration.php";
require APPPATH . "controllers/BatchC/ViaVarejo/ViaUtils.php";

class Products_VIA extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal;
	var $integration;
	var $isMock = false;

	var $via_oAuth2 = null;
	var $via_integration = null;
	var $via_authorization = null;

    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_blingultenvio');
	    
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces'); 	 
		$this->load->model('model_log_integration_product_marketplace');   
		$this->load->model('model_marketplace_prd_variants'); 
		$this->load->model('model_settings'); 	
		$this->int_to = 'VIA';

		$this->via_oAuth2 = new ViaOAuth2();
		$this->via_integration = new ViaIntegration();
		
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$this->via_authorization = $this->via_oAuth2->authorize($client_id, $client_secret, $grant_code);
	}
	
	public function run($id) {
		$this->id = $id;
	}

	var $id = 0;
	var $product_id = 0;

	protected function getDataMock() {
		echo $this->product_id . PHP_EOL;
		return array(
			'queue_id' => 1,
			'product_id' => $this->product_id
		);
	}
	
	public function receiveData()
	{
		if ($this->isMock === false)
			return parent::receiveData();

		ignore_user_abort(true);
		set_time_limit(0);
		
		$data = json_decode(file_get_contents('php://input'), true);
		$this->queue_id  	= $data['queue_id'];
		echo 'removendo da fila '.$this->queue_id."\n";
		$this->removeFromQueue();
		
		$data = $this->getDataMock();
		if (is_null($data)) {
			$error = "Dados fora do formato json!";
			show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die;
		}
		
		$this->queue_id  	= $data['queue_id'];
        $prd_id				= $data['product_id'];

		// ler o produto e variants; 
		$this->prd=$this->model_products->getProductData(0,$prd_id);
		// $this->prd['qty'] = 1;
		// leio a loja
		$this->store    = $this->model_stores->getStoresData($this->prd['store_id']);
		// leio as variações
		$this->variants = $this->model_products->getVariants($prd_id);
		// pego os dados do catálogo do produto se houver 
		if (!is_null($this->prd['product_catalog_id'])) {
			$prd_catalog = $this->model_products_catalog->getProductProductData($this->prd['product_catalog_id']); 
			$this->prd['name'] = $prd_catalog['name'];
			$this->prd['description'] = $prd_catalog['description'];
			$this->prd['EAN'] = $prd_catalog['EAN'];
			$this->prd['largura'] = $prd_catalog['width'];
			$this->prd['altura'] = $prd_catalog['height'];
			$this->prd['profundidade'] = $prd_catalog['length'];
			$this->prd['peso_bruto'] = $prd_catalog['gross_weight'];
			$this->prd['ref_id'] = $prd_catalog['ref_id']; 
			$this->prd['brand_code'] = $prd_catalog['brand_code'];
			$this->prd['brand_id'] = '["'.$prd_catalog['brand_id'].'"]'; 
			$this->prd['category_id'] = '["'.$prd_catalog['category_id'].'"]';
			$this->prd['image'] = $prd_catalog['image'];
			$this->pathImage = 'catalog_product_image';
		}
		else {
			$this->pathImage = 'product_image';
		}

		return ;
	}
	

	public function index_get() {
		$this->isMock = true;
		$products = array(215339);
		
		foreach ($products as $id) {
			$this->product_id = $id;
			$this->receiveData();
			$this->checkAndProcessProduct();
		}
		
	}
	
	public function index_post() 
    {
    	// $this->inicio = microhmtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();
	
		// verifico se é cadastrar, inativar ou alterar o produto
		$this->checkAndProcessProduct();
			
		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		// $fim= microtime(true);
		// echo "\nExecutou em: ". ($fim-$this->inicio)*1000 ." ms\n";
		return;
    } 
	
	public function checkAndProcessProduct()
	{
		
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
	
		$prd = $this->model_products->getProductData(0, $this->prd_to_integration['prd_id']);
		$prd['price'] = $this->getPrice(null);
		$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'], $this->int_to);

		$skumkt = $this->prd_to_integration['skumkt'];
		
		if (is_null($skumkt)) {
			$skumkt = "P".$this->prd_to_integration['prd_id']."S".$this->prd_to_integration['store_id'].$this->int_to;
			$this->model_products->updateProductIntegrationSku($this->prd_to_integration['id'], $skumkt, $skumkt); 
			$this->prd_to_integration['skumkt'] = $skumkt;
			$this->prd_to_integration['skubling'] = $skumkt;
		}

		$prd_variants = array();
		if ($prd['has_variants'] != '')
		{
			$sql = "SELECT * FROM prd_variants WHERE prd_id = ".$this->prd_to_integration['prd_id'];
			$cmd = $this->db->query($sql);
			$variants = $cmd->result_array();
			$hasSkuInMkt = false;
			foreach ($variants as $variant) {
				$item_variant = array();
				$item_variant['variant'] = $variant['variant'];
				$item_variant['qty'] = $variant['qty'];
				$item_variant['image'] = $variant['image'];
				foreach (explode(';', $prd['has_variants']) as $key_has_variant => $has_variant) {
					if (strtoupper($has_variant) == 'COR')
					{
						$item_variant['color'] = explode(';', $variant['name'])[$key_has_variant];
					} else if (strtoupper($has_variant) == 'VOLTAGEM')
					{
						$item_variant['voltage'] = explode(';', $variant['name'])[$key_has_variant];
					} else if (strtoupper($has_variant) == 'TAMANHO')
					{
						$item_variant['size'] = explode(';', $variant['name'])[$key_has_variant];
					}
				}

				$skuInMkt = $this->prd_to_integration['skumkt']. '-' .$variant['variant'];
				
				$result = $this->via_integration->hasSkuInMkt($this->via_authorization, $skuInMkt);
				if (($result[0]) && (($result[1])))
				{
					$hasSkuInMkt = true;
				}
				
				if (!$hasSkuInMkt) 
				{
					array_push($prd_variants, $item_variant);
				}
			}
			if (count($prd_variants) == 0) return ;
		}

		$brand_id = json_decode($prd['brand_id']);
		$brand = $this->getBrand($brand_id);

		$category_id = $this->getCategoryMarketplace($skumkt, $this->int_to);

		if ($category_id === false) 
		{
			return ;
		}

		$atributos = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($prd['id']);
		$atributos_via = [];
		$atributos_variants = [];
		foreach($atributos as $key => $atributo) {
			if ($atributo["int_to"] == 'VIA') {
				$attr_via = $this->model_atributos_categorias_marketplaces->getAtributoCategoria_MKT($category_id, $atributo['id_atributo'], $atributo['int_to']);
				if ($attr_via['variacao'] === "1") {
					array_push($atributos_variants, $atributo);
				}
				else {
					array_push($atributos_via, $atributo);
				}
			}
		}

		$prd['tipovolumecodigo'] = $this->prd['tipovolumecodigo'];
		$this->updateBlingUltEnvio($prd);

		$retorno = $this->via_integration->register($this->via_authorization, $skumkt, $prd, $prd_variants, $brand['name'], $category_id, $atributos_via, $atributos_variants);

		if ($retorno['httpcode'] >= 300) {
			$this->model_products->updateProductIntegrationStatus($this->prd_to_integration['id'], 0);
			if ($retorno['httpcode'] == 429) {
				$this->model_products->insertQueue($this->prd_to_integration['prd_id'], $this->int_to);
			}

			$prod_data = $retorno['reqbody'];
            echo " Erro URL: /import/itens httpcode=".$retorno['httpcode']."\n"; 
            echo " RESPOSTA VIA ".print_r($retorno,true)." \n"; 
            echo " Dados enviados: ".print_r($prod_data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto: '.$prd['id'].' site: Via Varejo - httpcode: '.$retorno['httpcode']." RESPOSTA VIA: ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($prod_data,true),"E");
			
			$data_log = array( 
				'int_to' => $this->int_to,
				'prd_id' => $this->prd['id'],
				'function' => 'Em cadastramento',
				'url' => $retorno['url'],
				'method' => 'POST',
				'sent' => $retorno['reqbody'],
				'response' => '',
				'httpcode' => $retorno['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);

			return false;
		} else {
			$this->model_products->updateProductIntegrationStatus($this->prd_to_integration['id'], 22);
			$this->model_errors_transformation->setStatusResolvedByProductId($prd['id'], $this->int_to);
			echo $skumkt. ' - Retorno: '. $retorno['httpcode'] . PHP_EOL;

			$data_log = array( 
				'int_to' => $this->int_to,
				'prd_id' => $this->prd['id'],
				'function' => 'Em cadastramento',
				'url' => $retorno['url'],
				'method' => 'POST',
				'sent' => $retorno['reqbody'],
				'response' => 'Gravado com sucesso',
				'httpcode' => $retorno['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}		
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Update"."\n";
		$skumkt = $this->prd_to_integration['skumkt'];
		$prd = $this->model_products->getProductData(0, $this->prd_to_integration['prd_id']);
		$prd['price'] = $this->getPrice(null);
		$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'], $this->int_to);

		$search_skumkt = $skumkt;
		if ($prd['has_variants'] != '') {
			$search_skumkt = $skumkt . '-0';
		}

		$hasSkuInMkt = $this->via_integration->hasSkuInMkt($this->via_authorization, $search_skumkt, false);
		if (!$hasSkuInMkt[0]) {
			$this->insertProduct();
		}
		else {
			$bling_ult_envio = $this->model_blingultenvio->getDataBySkumkt($skumkt);
			if (is_null($bling_ult_envio)) {
				$skumkt = "P".$this->prd_to_integration['prd_id']."S".$this->prd_to_integration['store_id'].$this->int_to;
				$this->model_products->updateProductIntegrationSku($this->prd_to_integration['id'], null, $skumkt); 
				$this->model_products->insertQueue($this->prd_to_integration['prd_id'], $this->int_to);
				return true;
			}
			else {
				if ($bling_ult_envio['prd_id'] != $this->prd_to_integration['prd_id']) {
					$skumkt = "P".$this->prd_to_integration['prd_id']."S".$this->prd_to_integration['store_id'].$this->int_to;
					$this->model_products->updateProductIntegrationSku($this->prd_to_integration['id'], null, $skumkt); 
					$this->model_products->insertQueue($this->prd_to_integration['prd_id'], $this->int_to);
					return true;
				}
			}

			$this->getCategoryMarketplace($skumkt, $this->int_to);
			$prd = $this->model_products->getProductData(0, $this->prd_to_integration['prd_id']);
			$prd['price'] = $this->getPrice(null);
			$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'], $this->int_to);
			$prd['tipovolumecodigo'] = $this->prd['tipovolumecodigo'];
			$this->updateBlingUltEnvio($prd);

			if (!$hasSkuInMkt[1]) {
				$this->model_products->updateProductIntegrationStatus($this->prd_to_integration['id'], 22);	
				return true;
			}

			$this->model_products->updateProductIntegrationStatus($this->prd_to_integration['id'], 2);
			$this->model_errors_transformation->setStatusResolvedByProductId($this->prd_to_integration['prd_id'], $this->int_to);

			if ($prd['has_variants'] != '') {
				$variants = $this->model_products->getVariants($prd['id']);
				foreach ($variants as $variant) {
					$skumkt_variant = $skumkt . '-' . $variant['variant'];
					$prd['qty'] = $variant['qty'];
					$response = $this->via_integration->update($this->via_authorization, $skumkt_variant, $prd);
					if ($response['httpcode'] == 422) {
						$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
							"VALUES(". $prd["id"] .", '". $skumkt_variant . "', '".$this->int_to."', 'Atualização estoque', 'Erro ao atualizar o estoque da variação ".$variant['variant'].".', 0);";
			
						$this->db->query($sql);
					}

					$response['httpcode'] = $this->via_integration->updatePricesV2($this->via_authorization, $skumkt_variant, $prd);
					if ($response['httpcode'] == 422) {
						$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
							"VALUES(". $prd["id"] .", '". $skumkt_variant . "', '".$this->int_to."', 'Atualização de Preço', 'Erro ao atualizar o preço da variação ".$variant['variant'].".  Variação do preço superior a 50%.', 0);";
			
						$this->db->query($sql);
					}
				}
			}
			else {
				$response = $this->via_integration->update($this->via_authorization, $skumkt, $prd);
				if ($response['httpcode'] == 422) {
					$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
						"VALUES(". $prd["id"] .", '". $skumkt . "', '".$this->int_to."', 'Atualização estoque', 'Erro ao atualizar o estoque do produto.', 0);";
		
					$this->db->query($sql);
				}

				$response = $this->via_integration->updatePricesV2($this->via_authorization, $skumkt, $prd);
				if ($response['httpcode'] == 422) {
					$sql = "INSERT INTO errors_transformation (prd_id, skumkt, int_to, step, message, status) ".
						"VALUES(". $prd["id"] .", '". $skumkt . "', '".$this->int_to."', 'Atualização de Preço', 'Erro ao atualizar o preço do produto. Variação do preço superior a 50%.', 0);";
		
					$this->db->query($sql);
				}
			}
		}
		return true;
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		$skumkt = $this->prd_to_integration['skumkt'];
		$prd = $this->model_products->getProductData(0, $this->prd_to_integration['prd_id']);
		$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'],"VIA");

		$search_skumkt = $skumkt;
		if ($prd['has_variants'] != '') {
			$search_skumkt = $skumkt . '-0';
		}

		$hasSkuInMkt = $this->via_integration->hasSkuInMkt($this->via_authorization, $search_skumkt, false);
		if (!$hasSkuInMkt[0]) {
			return ;
		}

		$prd_to_integration = $this->model_products->getProductIntegrationSkumkt($skumkt);
		if (count($prd_to_integration) == 1) {
			if ($prd['has_variants'] != '') {
				$variants = $this->model_products->getVariants($prd['id']);
				foreach ($variants as $variant) {
					$skumkt_variant = $skumkt . '-' . $variant['variant'];
					$this->via_integration->disableAll($this->via_authorization, $skumkt_variant);
				}
			}
			else {
				$this->via_integration->disableAll($this->via_authorization, $skumkt);
			}
		}
		else {
			$bling_ult_envio = $this->model_blingultenvio->getDataBySkumkt($skumkt);
			if (is_null($bling_ult_envio)) {
				$skumkt = "P".$this->prd_to_integration['prd_id']."S".$this->prd_to_integration['store_id'].$this->int_to;
				$this->model_products->updateProductIntegrationSku($this->prd_to_integration['id'], $skumkt, $skumkt); 
			}
			else {
				if ($bling_ult_envio['prd_id'] != $this->prd_to_integration['prd_id']) {
					$skumkt = "P".$this->prd_to_integration['prd_id']."S".$this->prd_to_integration['store_id'].$this->int_to;
					$this->model_products->updateProductIntegrationSku($this->prd_to_integration['id'], $skumkt, $skumkt); 
				}
				else {
					$this->via_integration->disableAll($this->via_authorization, $skumkt);
				}
			}
		}
	}
	
	function getBrand($brand_id) {
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $this->db->query($sql, $brand_id);
		return $query->row_array();	
	}

	function updateBlingUltEnvio($prd) 
	{
		// EAN para colocar no Bling_ult_envio. Não é importante ter EAN, então crio um EAN único para cada produto
		if ($prd['is_kit'] == 1) {
			$ean ='IS_KIT'.$prd['id'];
		}
		else {
			$ean ='VIA_EAN'.$prd['id']; 
		}
		$skubling = $this->prd_to_integration['skubling']; 
    	$data = array(
    		'int_to' => $this->int_to,
    		'company_id' => $prd['company_id'],
    		'EAN' => $ean,
    		'prd_id' => $prd['id'],
    		'price' => $prd['price'],
    		'qty' => $prd['qty'],
    		'sku' => $prd['sku'],
    		'reputacao' => 100,
    		'NVL' => 100,
    		'mkt_store_id' => '',         
    		'data_ult_envio' => $this->dateLastInt,
    		'skubling' => $skubling,
    		'skumkt' => $this->prd_to_integration['skumkt'],
    		'tipo_volume_codigo' => $prd['tipovolumecodigo'], 
    		'qty_atual' => $prd['qty'],
    		'largura' => $prd['largura'],
    		'altura' => $prd['altura'],
    		'profundidade' => $prd['profundidade'],
    		'peso_bruto' => $prd['peso_bruto'],
    		'store_id' => $prd['store_id'], 
    		'marca_int_bling' => null, 
			'categoria_bling'=> null,
    		'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'], 
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type']
    	);

        $data = $this->formatFieldsUltEnvio($data);

		$savedUltEnvio= $this->model_blingultenvio->createIfNotExist($ean, $this->int_to, $data); 
		if (!$savedUltEnvio) {
            $notice = "Falha ao tentar gravar dados na tabela bling_ult_envio.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
		}
		else {
			$records = $this->model_blingultenvio->getDataByPrdIdAndIntTo($prd['id'], $this->int_to);
			if (count($records) > 1) {
				$this->model_blingultenvio->deleteByIntToPrdIdAndDiffEan($prd['id'], $this->int_to, $ean);
			}			
		}
	}

	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->model_blingultenvio->getData(null, $procura);
	}
}