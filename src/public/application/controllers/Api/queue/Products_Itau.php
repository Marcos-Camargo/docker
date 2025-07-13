<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsConectala.php";
     
class Products_Itau extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $score_min = 100;  // score da Vtex 
	var $auth_data;
	var $fotos = array();
	var $sellerId; 
	var $tradesPolicies = array();
	var $adlink = null;
	var $prd_vtex = null; 
	var $ref_id = 'SKUMKT';
	var $update_sku_specifications = false;
	var $update_product_specifications = false;
	var $update_images_specifications = true;
	
    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_vtex_ult_envio');
	    
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		
		$this->int_to = 'Itau';
		$this->tradesPolicies = array('1','2','3');
		$this->update_product_specifications = false;
		
    }
	
	public function index_post() 
    {
    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();
	
		// verifico se é cadastrar, inativar ou alterar o produto
		$this->checkAndProcessProduct();
			
		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		$fim= microtime(true);
		echo "\nExecutou em: ". ($fim-$this->inicio)*1000 ." ms\n";
		return;
    } 
	
	public function checkAndProcessProduct()
	{
		
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
	public function getScore() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->score_min = 80;
	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		// Pego o seller Id da Vtex
		$auth_data = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = $auth_data->seller_id;
		
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		// pego o preço do produto deste marqketplace ou da promotion se houver 
		
		echo "aqui \n";
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}
			foreach($this->variants as $variant) {
                if ($variant['status'] != 1) {
                    $this->disableProductVariant(null, $variant);
                } else {
                    $this->insertProductVariant($variant);
                }
			}
		}
		else {
			$this->insertProductVariant();
		}

	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Update"."\n";
		// Pego o seller Id da Vtex
		$auth_data = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = $auth_data->seller_id;
		
		$this->auth_data = json_decode($this->integration_main['auth_data']);
        // pego o preço do produto deste marqketplace ou da promotion se houver 

		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}
			foreach($this->variants as $variant) {
                $prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
                if ($variant['status'] != 1) {
                    $this->disableProductVariant($prd_to_integration, $variant);
                } else {
                    $this->updateProductVariant($prd_to_integration, $variant);
                }
			}
		}
		else {
			$this->updateProductVariant($this->prd_to_integration);
		}
		return true; 
	}

	function updateProductVariant($prd_to_integration, $variant=null, $disable = false ) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if (is_null($prd_to_integration['skumkt'])) {
			// houve algum problema com alguma variação e a mesma não foi enviada, então insiro. 
			$this->insertProductVariant($variant);
			return;
		}
		$this->prd['promotional_price'] = $this->getPrice($variant);
		
		$variant_num = null;
		
		if (!is_null($variant)) {
			$variant_num = $variant['variant'];
			$this->prd['sku'] = $variant['sku'];
			$this->prd['qty'] = $variant['qty'];
			$this->prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;	
		}
				
		$this->saveVtexUltEnvio($prd_to_integration['skumkt'],$variant_num );

		
        $this->model_integrations->updatePrdToIntegration(array(
            	'int_id'=> $this->integration_store['id'], 
            	'seller_id'=> $this->sellerId , 
            	'status_int' => 2, 
            	'date_last_int' => $this->dateLastInt, 
            	'skumkt' => $prd_to_integration['skumkt'], 
            	'skubling' =>$prd_to_integration['skumkt'],
            	'mkt_product_id' => $this->prd['id'],
            	'mkt_sku_id' => $this->prd['id'],   
            	'ad_link' =>  ''
			), $prd_to_integration['id']);
			
		
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando"."\n";
		// Pego o seller Id da Vtex
		$auth_data = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = $auth_data->seller_id;
		
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		  // pego o preço do produto deste marqketplace ou da promotion se houver 
		$this->prd['qty'] = 0; // zero a quantidade do produto
		$this->update_images_specifications = false;
		$this->update_sku_specifications = false;
		$this->update_product_specifications = false;
		
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}

            $variants = $this->variants;
            if (!is_null($variant)) {
                $variants = array($variant);
            }

			foreach($variants as $variant) {
				$variant['qty'] = 0;  // zero a quantidade da variant tb
				$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
				$this->updateProductVariant($prd_to_integration, $variant, $disable);
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$prd_to_integration['id']);
			}
		}
		else {
			$this->updateProductVariant($this->prd_to_integration, null, $disable);
			$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
		}
		return true; 
	}
	

	function insertProductVariant($variant=null)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->prd['promotional_price'] = $this->getPrice($variant);
		
		$variant_num = null;
		$prd_to_integration = $this->prd_to_integration;
		if (!is_null($variant)) {
			$variant_num = $variant['variant'];
			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant_num);		
			$this->prd['sku'] = $variant['sku'];
			$this->prd['qty'] = $variant['qty'];
			$this->prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;
			
		}
		
		$this->prd['sku']= str_replace('.','',$this->prd['sku']);
		$skumkt = $prd_to_integration['skumkt'];
		if (is_null($skumkt)) {
			$skumkt = $this->prd['sku'];
			if (!is_null($variant_num)) {
				$skumkt = $variant['sku'];
			}
			
		}
	
		$images = array(
			array('ImageUrl' => 'http://nada.nada')		
		);
		
		// pego a categoria 
		$this->prd['categoryvtex'] = 'Categoria';
	
		// pego o brand do Marketplace 
		$this->prd['brandvtex'] = 'MarcaTeste';

		
		if (!(array_key_exists('ref_id', $this->prd))) {
			$this->prd['ref_id'] = null;
		}
		if (!(array_key_exists('sku_id', $this->prd))) {
			$this->prd['sku_id'] = null;
		}

		// Procuro as variações 
		$skuspec_match = array();
		$spec = array();
		$spec_old = array();
		
		$this->prd['skuname']= $this->prd['name'];
		
		$this->prd['vteximages'] = array();
		foreach ($images as $image) {
			// alterar para colocar as imagens da variacao 
			$this->prd['vteximages'][$image['ImageName']] = $image['ImageUrl'];
		}
		
		$prodspec = null;
	
		$spec = null;

		// mando a suggestion	
		// Suggestion velho
		$images_old = array();
		foreach ($images as $image) {
			$images_old[] = array(
				'imageName' => '1', 
				'imageUrl' => $image['ImageUrl'],
			);
		}
		$vtex_prod_id= ((trim($this->prd['ref_id']) ? $this->prd['ref_id'] : null));
		$vtex_prod_id= 	$this->prd['id'];

		$refid= $skumkt; 
		if ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto - solicitação Ortobom
			$this->prd['EAN'] = '';
			$refid = $this->prd['id'];
		}
		
        $data = [
            'ProductName'                => $this->prd['name'],
			'ProductId'                  => $vtex_prod_id,
			'ProductDescription'         => $this->prd['description'],
			'BrandName'                  => $this->prd['brandname'],
			'SkuName'                    => $this->prd['skuname'],
			'SellerId'                   => $this->sellerId ,
			'Height'                     => (float)$this->prd['altura'],
			'Width'                      => (float)$this->prd['largura'],
			'Length'                     => (float)$this->prd['profundidade'],
			'WeightKg'                   => (float)$this->prd['peso_bruto'],
			'RefId'                      => ((trim($this->prd['ref_id']) ? $this->prd['ref_id'] : $refid)), // obrigatório quando o EAN não for enviado
            'EAN'                        => ((trim($this->prd['EAN']) == '') ? null : $this->prd['EAN']),
			//'SellerStockKeepingUnitId'   => (int)$this->prd['id'],
			'SellerStockKeepingUnitId'   => $skumkt,
			'CategoryFullPath'           => str_replace(' > ','/',$this->prd['categoryname']),
			'SkuSpecifications'          => $spec_old,
			'ProductSpecifications'      => $prodspec_old,
			'Images'                     => $images_old,
			'MeasurementUnit' 			 => 'un',
			'UnitMultiplier'             => 1, 
			'AvailableQuantity'          => (int)$this->prd['qty'],
			'Pricing'					 => array(
				'Currency'				 => 'BRL',
				'SalePrice'   			 => (float)$this->prd['promotional_price'], 
				'CurrencySymbol' 	     => 'R$'
			), 
        ];
		
        $bodyParams  = json_encode($data);
		


		"Iniciando match do produto."."\n";

        //$matchOk = $this->skuToMatch($skumkt, $prdspec_match, $skuspec_match, $variant_num);
 
		// aprovou...
		$vtexprodid = $this->prd['id']; 
				
		$prd_to_integration['mkt_sku_id'] = $this->prd['id']; 
		$prd_to_integration['mkt_product_id'] = $vtexprodid;
		
		$this->saveVtexUltEnvio($skumkt,$variant_num );
       
        $toSavePrd = [
            'prd_id' => $this->prd['id'],
            'company_id' => $this->prd['company_id'],
            'status' => 1,
            'status_int' => 2,
            'date_last_int' => $this->dateLastInt, 
            'skumkt' => $skumkt,
            'skubling' => $skumkt,
            'int_to' => $this->int_to,
            'store_id' => $this->prd['store_id'],
            'seller_id' => $this->sellerId ,
            'approved' => 1,
            'int_id' => $this->integration_store['id'],
            'user_id' => 0,
            'int_type' => 0, 
            'variant' => $variant_num,
            'mkt_product_id' => $vtexprodid, 
            'mkt_sku_id' => $this->prd['id']
        ];

        if(isset($this->prd_to_integration['approved_curatorship_at']) && !empty($this->prd_to_integration['approved_curatorship_at'])){
            $toSavePrd['approved_curatorship_at'] = $this->prd_to_integration['approved_curatorship_at'];
        }

		$PrdIntId = $this->model_integrations->createIfNotExist($this->prd['id'], $this->int_to, $variant_num, $toSavePrd);
        if (!$PrdIntId) {
            $notice = "Falha ao tentar gravar produto na tabela prd_to_integration.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        }
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
	}
	
	protected function getCrossDocking($prd) {
		return $prd['prazo_operacional_extra'];
	}

 	function saveVtexUltEnvio($skumkt,$variant_num )
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$crossdocking = $this->getCrossDocking($this->prd);

        $toSaveUltEnvio = [
            'int_to' => $this->int_to,
            'company_id' => $this->prd['company_id'],
            'EAN' => $this->prd['EAN'],
            'prd_id' => $this->prd['id'],
            'price' => $this->prd['promotional_price'],
            'list_price' => $this->prd['price'],
            'sku' => $this->prd['sku'],
            'data_ult_envio' => $this->dateLastInt, 
            'qty_atual' => (int)$this->prd['qty'],
            'largura' => $this->prd['largura'],
            'skumkt' => $skumkt,
            'altura' => $this->prd['altura'],
            'profundidade' => $this->prd['profundidade'],
            'peso_bruto' => $this->prd['peso_bruto'],
            'store_id' => $this->prd['store_id'],
            'seller_id' => $this->sellerId ,
            'crossdocking' => $crossdocking,
        	'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
			'variant' => $variant_num,
            'tipo_volume_codigo' => 19
        ];

        $toSaveUltEnvio = $this->formatFieldsUltEnvio($toSaveUltEnvio);

    	$savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($this->prd['id'], $this->int_to, $variant_num, $toSaveUltEnvio);
        if (!$savedUltEnvio) {
            $notice = "Falha ao tentar gravar dados na tabela vtex_ult_envio.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        } 
	
	}

	function hasShipCompany()
    {
    	return true;
    }
	
	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->model_vtex_ult_envio->getData(null, $procura);
	}

}