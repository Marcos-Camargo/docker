<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsConectala.php";
     
class ProductsVtex extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $score_min = 100;  // score da Vtex 
	var $auto_approve = true;
	var $auth_data;
	var $fotos = array();
	var $sellerId; 
	var $tradesPolicies = array();  // array com as trade policies do cadastradas no produto
	var $adlink = null;   // http da marketplace 
	var $prd_vtex = null; 
	var $ref_id = 'SKUMKT';
	var $update_sku_specifications = false; // inidica se é para forçar a alteração das especificações de SKU
	var $update_product_specifications = false; // inidica se é para forçar a alteração das especificações de Products
	var $update_images_specifications = true; // inidica se é para forçar a alteração das imagens 
	var $skumkt_seller = false;  // indica se é para usar o sku do seller como skumkt na vtex.
	var $accountName ='';
	var $informacoesTecnicas = false;
	var $vtex_ref_id = null;

    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_vtex_ult_envio');
	    
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		
		$this->informacoesTecnicas = $this->model_settings->getValueIfAtiveByName('informacoes_tecnicas_vtex');

		echo "INATIVADO\n";
		die;
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
	
	protected function beforeGetProductData($prd_id) {
		parent::beforeGetProductData($prd_id);
	}

	public function checkAndProcessProduct()
	{
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
	public function getScore() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
 		$url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/configuration';
        $this->vtexHttpUrl($this->auth_data, $url);
        $result = json_decode($this->result);
		//echo print_r($result,true));
		if ($this->responseCode !== 200) {
			$erro = "Não foi possivel pegar o score mínimo para match de ".$this->int_to." http: ".$url." httpcode:".$this->responseCode." resposta: ".print_r($this->result,true);
            echo $erro."\n";
            $this->log_data('batch', $log_name, $erro,"E");
			die;
		}
		$this->score_min = $result->Score->Approve;
	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		// Pego o seller Id da Vtex
		$auth_data = json_decode($this->integration_store['auth_data']);
    	$this->sellerId = $auth_data->seller_id;
		
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		$this->accountName = $this->auth_data->accountName;
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
		$this->accountName = $this->auth_data->accountName;
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
	}
	
	function prepareProduct($sku, $variant) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// Pego o preço 
		$this->prd['promotional_price'] = $this->getPrice($variant);
		
		//verifico se o nome está ok
		if (strlen($this->prd['name']) > 150) {
			$notice = "Nome do produto acima de 150 caracteres. Limite máximo do Marketplace é de 150. Favor, acertar o nome do produto";
            echo $notice."\n";
			$this->errorTransformation($this->prd['id'],$sku, $notice, 'Preparação para o envio');
			return false; 
		}
		
		// vejo se devo remover os tags HTML
		$description_without_html = $this->model_settings->getValueIfAtiveByName('description_without_html');
		if ($description_without_html) {
			$this->prd['description'] = str_replace("<br>",$description_without_html,$this->prd['description']);
			$this->prd['description'] = str_replace("</p>",$description_without_html,$this->prd['description']);
			$this->prd['description'] = str_replace("&nbsp;",' ',$this->prd['description']);
			$this->prd['description'] = strip_tags($this->prd['description'],"<br>");  // se o $description_without_html for <br> eu tenho que permiti aqui
		}

		// Tudo OK
		return true; 
		
	}
	
	function updateProductVariant($prd_to_integration, $variant=null, $disable = false ) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if (is_null($prd_to_integration['skumkt'])) {
			// houve algum problema com alguma variação e a mesma não foi enviada, então insiro. 
			$this->insertProductVariant($variant);
			return;
		}
		
		// preparo o produto para ser feito update
		if ($this->prepareProduct($prd_to_integration['skumkt'], $variant)==false) { return false;};
		
		$variant_num = null;
		
		var_dump($variant);
		if (!is_null($variant)) {
			$variant_num = $variant['variant'];
			$this->prd['sku'] = $variant['sku'];
			$this->prd['qty'] = $variant['qty'];
			$this->prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;	
		}
				
		$this->saveVtexUltEnvio($prd_to_integration['skumkt'],$variant_num );
			
		$data = [];
		$bodyParams = json_encode($data);
        $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$this->sellerId .'/'.$prd_to_integration['skumkt'];
	            
        echo "Verificando se o produto ".$this->prd['id']." sku ".$prd_to_integration['skumkt']." existe no marketplace ".$this->int_to." para o seller ".$this->sellerId ."\n";
        $skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Notificação de Mudança');
        
        if ($this->responseCode == 404) {
			sleep(30); // pode não ter dado tempo para a Vtex atualizar as multiplas replicas do banco de dados
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Notificação de Mudança');
        }
		
		if ($this->responseCode == 404) {
			// var_dump($prd_to_integration);
			if ($prd_to_integration['status_int'] != 22) { //está em cadastramento
				$erro = "O produto ".$this->prd['id']." não está cadastrado no marketplace ".$this->int_to." para o seller ".$this->sellerId .".";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				$this->model_integrations->updatePrdToIntegration(array('status_int' => 90, 'date_last_int' => $this->dateLastInt), $prd_to_integration['id']);
				
				echo " **** deveria retirar o registro do prd_to_integration e do vtex_ult_envio se isso acontecer"."\n";
			}
			else {
				echo "Ainda em cadastramento! Mando a suggestion de novo\n";	
			}
			$this->insertProductVariant($variant);;
			return;
		}
		if ($this->responseCode !== 204) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		
		// pego os ids da Vtex pois podem ter sido re-associados a outro produto
		$ids =$this->getVtexSkuProductId($variant_num, $prd_to_integration['skumkt']);
		
        $notice = "Notificação de alteração concluída para o produto ".$this->prd['id']." sku: ".$prd_to_integration['skumkt'];
        echo $notice."\n";
        $this->model_integrations->updatePrdToIntegration(array(
            	'int_id'=> $this->integration_store['id'], 
            	'seller_id'=> $this->sellerId , 
            	'status_int' => 2, 
            	'date_last_int' => $this->dateLastInt, 
            	'skumkt' => $prd_to_integration['skumkt'], 
            	'skubling' =>$prd_to_integration['skumkt'],
            	'mkt_product_id' => $ids['mkt_product_id'],
            	'mkt_sku_id' => $ids['mkt_sku_id'],   
            	'ad_link' =>  (is_null($this->adlink)) ? '' : $this->adlink . $this->prd_vtex->LinkId."/p"
			), $prd_to_integration['id']);
			
		$prd_to_integration['mkt_product_id'] = $ids['mkt_product_id'];
		$prd_to_integration['mkt_sku_id'] = $ids['mkt_sku_id'];

		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
		
		// Pego a Categoria
		$this->prd['categoryvtex'] = $this->getCategoryMarketplace($prd_to_integration['skumkt']);
		if ($this->prd['categoryvtex']===false) {
			return false; 
		}
		if (is_null($variant_num) || ($variant_num ==0)) {
			// update o product  
			$this->updateProductVtex($prd_to_integration);
		}
		
		// update das imagens
		if ($this->update_images_specifications) {
			$this->changeSkuImage($prd_to_integration, $variant);
		}
		
		// update do sku - varição
		$this->updateSkuVtex($prd_to_integration, $variant, $disable);
		
	}

	function insertProductVariant($variant=null)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$variant_num = null;
		$prd_to_integration = $this->prd_to_integration;
		if (!is_null($variant)) {
			$variant_num = $variant['variant'];
			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant_num);		
			$this->prd['sku'] = $variant['sku'];
			$this->prd['qty'] = $variant['qty'];
			$this->prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'];
		}
		
		$this->prd['sku']= str_replace('.','',$this->prd['sku']);
		$skumkt = $prd_to_integration['skumkt'];
		if (is_null($skumkt)) {
			if  ($this->skumkt_seller) {  // o SKU na Vtex será igual ao SKU do lojista, inclusive nas variações
				$skumkt = $this->prd['sku'];
			}
			else {
				$skumkt = $this->prd['id'].'_'.$this->int_to;
				if (!is_null($variant_num)) {
					$skumkt = $this->prd['id'].'_'.$variant_num.'_'.$this->int_to;
				}
			}
		}
		
		// preparo o produto para ser feito insert
		if ($this->prepareProduct($skumkt, $variant)==false) { return false;};
		
		// Verifico de o produto existe na Vtext
		// https://help.vtex.com/en/tutorial/integration-guide-for-marketplaces-seller-non-vtex-with-payment--bNY99qbQ7mKsSMMuq2m4g
		$bodyParams = json_encode(array());
        $endPoint   = 'api/catalog_system/pvt/skuSeller/changenotification/'.$this->sellerId .'/'.$skumkt;
        
        echo "Verificando se o produto ".$this->prd['id']." sku ".$skumkt." existe no marketplace ".$this->int_to." para o seller ".$this->sellerId ."\n";
        $skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Notificação de Mudança');
		
		if (($this->responseCode == 200) || ($this->responseCode == 204)) {
			// O Produto já está na VTEX então insert na prd-to_integration e faço update dele.  
			$prd_to = $this->productAtVtex($variant_num,$skumkt);
			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant_num);		
			$this->updateProductVariant($prd_to_integration, $variant); 
			return; 
		}
		if ($this->responseCode !== 404) { // O normal é dar 404, então podemos cadastrar o produto
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		
		// Monto array com as imagens do produto
		$vardir = '';
		/*if (($this->pathImage == 'product_image') && (!is_null($variant))){
			if (!is_null($variant['image']) && trim($variant['image'])!='')	{
				$vardir = '/'.$variant['image'];
			}
		} */
		$images = $this->getProductImages($this->prd['image'], $this->pathImage, $vardir);
		if ($images === null) {
            $notice = "Pasta ".$this->pathImage .'/'.$this->prd['image']." não encontrada!";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio');
            return;
        } elseif ($images === false) {
            $notice = "Não foram encontradas imagens na pasta ".$this->$pathImage.'/'.$this->prd['image'].".";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio');
            return;
        }
		if  (!is_null($variant)){
			$imagesVar = array();
			if ($this->pathImage == 'product_image') {
				if (!is_null($variant['image']) && trim($variant['image'])!='')	{
					$vardir = '/'.$variant['image'];
					$images_var = $this->getProductImages($this->prd['image'], $this->pathImage,  $vardir);
				}
			}
			else { // produto de catálogo
				$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$variant['variant'] ); 
				if ($var_cat) {
					$images_var = $this->getProductImages($var_cat['image'], $this->pathImage,  '');
				}
			}
			if (is_array($images_var)) {
				$images = array_merge($images_var, $images);  // junto as imagens da variação premeiro e depois a do pai
			}
		} 
		
		// pego a categoria 
		$this->prd['categoryvtex'] = $this->getCategoryMarketplace($skumkt);
		if ($this->prd['categoryvtex']===false) {	
			return false; 
		}
	
		// pego o brand do Marketplace 
		$this->prd['brandvtex'] = $this->getBrandMarketplace($skumkt, true);
		if ($this->prd['brandvtex']===false) {
			return false; 
		}
		
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
		if ($this->prd['has_variants']!= '') {
			$this->prd['skuname'] = $this->prd['name'].' '.str_replace(";", " / ", $variant['name']);
			$this->prd['variant_value'] = $variant['name'];
			$variants = explode(';', $this->prd['has_variants']);
			$variants_value  = explode(';', $variant['name']);
			foreach( $variants as $key => $value) {
				//echo "key = ".$key." value =".$value."\n";
				$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryvtex'],ucfirst(strtolower($value)),$this->int_to, !empty($this->prd['has_variants']));
				$valor_id = null;
				$field_id=0;
				
				if ($atributoCat) {
					if ($atributoCat['variacao'] == 0) {
						$notice = "Esta categoria não aceita variação por ".$value;
						$this->errorTransformation($this->prd['id'],$skumkt, $notice, 'Preparação para o envio');
			            return;
					} 
					$field_id = $atributoCat['id_atributo'];
					$valores = json_decode($atributoCat['valor'],true);
					// var_dump($valores);
					foreach($valores as $valor) {
						if ($valor['IsActive']) {
							// echo trim(strtoupper($valor['Value'])).'  '.trim(strtoupper($variants_value[$key]))."\n";
							if (trim(strtoupper($valor['Value'])) ==  trim(strtoupper($variants_value[$key]))) {
								//echo " ACHEI \n";
								$valor_id = array((int)$valor['FieldValueId']);
								//var_dump($valor_id);
								break ;
							}
						}
					}
					// echo " valor = ". print_r($valor_id, true) . "\n";
					//echo "Field ".$field_id."\n";
					if (($field_id!=0) && (!is_null($valor_id))) {
						$spec[] = array(
							'FieldId' => (int)$field_id,
							'FieldName' => ucfirst(strtolower($value)), 
							'FieldValueIds' => $valor_id,
							'FieldValues' => array(trim($variants_value[$key])),
						);	
						$spec_old[] = array(
							'FieldName' => ucfirst(strtolower($value)), 
							'FieldValues' => array(trim($variants_value[$key])),
						);
						$skuspec_match[ucfirst(strtolower($value))] = trim($variants_value[$key]);
					}
					else {
                        if (((int)$atributoCat['obrigatorio']) == 1
                            && ((int)$atributoCat['variacao']) == 1) { // mas é obrigatório e de variação
							$msg= 'Atributo obrigatório não preenchido ou com valor incorreto: '.$atributoCat['nome'].' : '.$variants_value[$key];
							echo 'Produto '.$this->prd['id']." ".$msg."\n";
							$this->errorTransformation($this->prd['id'],$skumkt, $msg, 'Preparação para o envio');
							return false;
						}
					}
					
				}
				
				
			}
			$data['SkuSpecifications'] = $spec; 
			
			// verifica se já cadastrou alguma variant antes deste produto. 
			$existVtex = $this->model_integrations->getDifferentVariant($this->int_to,$this->prd['id'],$variant_num);
			if ($existVtex) {
				$this->prd['ref_id'] = $existVtex['mkt_product_id'];
				//var_dump($existVtex);
			}
			
		}

		$this->prd['vteximages'] = array();
		foreach ($images as $image) {
			// alterar para colocar as imagens da variacao 
			$this->prd['vteximages'][$image['ImageName']] = $image['ImageUrl'];
		}

		// Busco os atributos específicos do produto para este marketplace 
		$prdspec_match = array();
		$prodspec = array();
		$prodspec_old= array();
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoryvtex'],$this->int_to);
		foreach($atributosCat as $atributoCat) {

			if ($this->informacoesTecnicas) {
				if ($this->informacoesTecnicas == $atributoCat['nome']) {
					$attributesCustomProduct = $this->model_products->getAttributesCustomProduct($this->prd['id']);
					$valueInformacoesTecnicas = $this->getInformacoesTecnicas($attributesCustomProduct);
					
					if ($valueInformacoesTecnicas !== false) {
						$dataProdutosAtributos = array(
							'id_product' => $this->prd['id'],
							'id_atributo' => $atributoCat['id_atributo'],
							'valor' => $valueInformacoesTecnicas,
							'int_to' => $this->int_to
						);

						$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($dataProdutosAtributos);
					}
				}
			}

			$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to);
			//var_dump($atributoCat);
			if (!$atributo_prd) {  // o atributo não está preenchido 
                if (((int)$atributoCat['obrigatorio']) == 1
                    && ((int)$atributoCat['variacao']) == 0
                ) { // mas é obrigatório e não é de variação
                	$found = false;  // se já está na variação um semelhante, puxa tb pro produto - BUGS-691
                	foreach ($spec_old as $spec_var) {
                		if ($spec_var['FieldName'] == $atributoCat['nome']) {
                			$found = true; 
							// $prodspec[] = $spec_var;
							$prodspec_old[] = $spec_var;  // - BUGS-691
							$prdspec_match[$atributoCat['nome']] = $spec_var['FieldValues'][0];
							continue;
                		}
                	}
                	if (!$found) {
                		$msg= 'Atributo obrigatório não preenchido : '.$atributoCat['nome'];
						echo 'Produto '.$this->prd['id']." ".$msg."\n";
						$this->errorTransformation($this->prd['id'],$skumkt, $msg, 'Preparação para o envio');
						return false;
                	}
					
				} 
			} else {  // o atributo está preenchido, vou pegar seus valores
				$valor_id = null;
				$valores = json_decode($atributoCat['valor'],true);
				foreach($valores as $valor) {
					if ($valor['IsActive']) { 
						if (trim(strtoupper($valor['FieldValueId'])) ==  trim(strtoupper($atributo_prd['valor']))) {
							$valor_id = array((int)$atributo_prd['valor']);
							$atributo_prd['valor'] = $valor['Value'];
							break ;
						}
					}
				}
				$spec_novo = array(
						'FieldId' => (int)$atributo_prd['id_atributo'], 
						'FieldName' => $atributoCat['nome'], 
						'FieldValueIds' => $valor_id, 
						'FieldValues' => array(trim($atributo_prd['valor'])), 
					);
				$spec_velho = array(
						'FieldName' => $atributoCat['nome'], 
						'FieldValues' => array(trim($atributo_prd['valor'])),
					);
				if ($atributoCat['variacao'] == 0) {  // não é uma variação, fica no produto 
					$prodspec[] = $spec_novo;
					$prodspec_old[] = $spec_velho;
					$prdspec_match[$atributoCat['nome']] = trim($atributo_prd['valor']);
				}
				else { // é uma variação, fica no sku
					$spec[] = $spec_novo;	
					$spec_old[] = $spec_velho;
					$skuspec_match[$atributoCat['nome']] = trim($atributo_prd['valor']);
				}
			}
		}
		if (count($prodspec) == 0) {
			$prodspec = null;
		}
		if (count($spec) == 0) {
			$spec = null;
		}

		/* 
        $data = [
            'BrandId'                    => (int)$this->prd['brandvtex'],
            'BrandName'                  => $this->prd['brandname'],
            'CategoryFullPath'           => str_replace(' > ','/',$this->prd['categoryname']),
            'CategoryId'                 => (int)$this->prd['categoryvtex'],
            'EAN'                        => array(
                ((trim($this->prd['EAN']) == '') ? null : $this->prd['EAN'])
            ),
            'Height'                     => (float)$this->prd['altura'],
            'Id'                         => null,
            'Images'                     => $images,
            'IsAssociation'              => false,
            'IsKit'                      => false,
            'IsProductSuggestion'        => false,
            'Length'                     => (float)$this->prd['profundidade'],
            'ListPrice'                  => (float)ceil($this->prd['price']),
            'ModalId'                    => null,
            'Price'                      => (int)ceil($this->prd['promotional_price']*100),   
            'ProductDescription'         => $this->prd['description'],
            'ProductId'                  => $this->prd['id'], // ((trim($this->prd['ref_id']) ? $this->prd['ref_id'] : null)),
            'ProductName'                => $this->prd['name'],
            'ProductSpecifications'      => $prodspec,
            'ProductSupplementaryFields' => null,
            'RefId'                      => ((trim($this->prd['ref_id']) ? $this->prd['ref_id'] : $skumkt)), // obrigatório quando o EAN não for enviado
            'SellerId'                   => $this->sellerId ,
            'SellerModifiedDate'         => null,
            'SellerStockKeepingUnitId'   => $skumkt,
            'SkuId'                      => null,
            'SkuName'                    => $this->prd['skuname'],
            'SkuSpecifications'          => $spec,
            'SkuSupplementaryFields'     => null,
            'SynonymousPropertyNames'    => null,
            'WeightKg'                   => (float)$this->prd['peso_bruto'],
            'Width'                      => (float)$this->prd['largura'],
        ];
		
        $bodyParams  = json_encode($data);
        $endPoint    = "api/catalog_system/pvt/sku/SuggestionInsertUpdatev2";
    	
		echo "Enviando sugestão de SKU."."\n";
	    $skuInserted = $this->vtexHttp($this->auth_data, $endPoint, 'POST', $bodyParams, $this->prd['id'], $this->int_to, 'Suggestion');
		if ($this->responseCode != 200) {
            $notice = "Falha no envio de sugestão de SKU do produto ".$this->prd['id']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true).' enviado: '.print_r($bodyParams,true);
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            die;
        }
		//echo print_r($bodyParams,true)."\n";
		var_dump($data);
		*/
	
		// mando a suggestion	
		// Suggestion velho
		$images_old = array();
		foreach ($images as $image) {
			$images_old[] = array(
				'imageName' => $image['ImageName'], 
				'imageUrl' => $image['ImageUrl'],
			);
		}
		
		if (empty($images_old)) {
			$this->errorTransformation($this->prd['id'],$skumkt, 'Produto sem Imagem', 'Preparação para o envio');
            return false;
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
		//var_dump($data);
		
		echo "Enviando sugestão de SKU."."\n";
		$url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$this->sellerId .'/'.$skumkt;
        $this->vtexHttpUrl($this->auth_data, $url,'PUT', $bodyParams, $this->prd['id'], $this->int_to, 'Suggestion');
	
        if ($this->responseCode != 200) {
            $notice = "Falha no envio de sugestão de SKU do produto ".$this->prd['id']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true).' enviado: '.print_r($bodyParams,true);
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            die;
        }

		"Iniciando match do produto."."\n";

        $matchOk = $this->skuToMatch($skumkt, $prdspec_match, $skuspec_match, $variant_num);

        if ($matchOk['success'] === false) {
			$errors_map = array (
				'Catalog response: {"Errors":[{"Message":"Violation of UNIQUE KEY constraint \'IX_Produto_TextoLink\''
			);
			$maps_message = array (
				'O título do produto não foi aceito na VTEX por já existir um outro produto com mesmo nome ou muito semelhante. Por favor, altere alguma informação do nome do produto e integre novamente!'
			);
			foreach ($errors_map as $key => $error_map) {
				if (substr($matchOk['error'],0,strlen($error_map)) ==  $error_map) {
					$matchOk['error'] = $maps_message[$key];
					break;
				}
			}
            $this->errorTransformation($this->prd['id'],$skumkt, $matchOk['error'], 'Match de produto');
            return;
        }
		if ($matchOk['success'] == 'Pending') {
			$this->saveVtexUltEnvio($skumkt,$variant_num );
       
	        $toSavePrd = [
	            'prd_id' => $this->prd['id'],
	            'company_id' => $this->prd['company_id'],
	            'status' => 1,
	            'status_int' => 22, // está aguardando aprovação na Vtex
	            'date_last_int' => $this->dateLastInt, 
	            'skumkt' => $skumkt,
	            'skubling' => $skumkt,
	            'int_to' => $this->int_to,
	            'store_id' => $this->prd['store_id'],
	            'seller_id' => $this->sellerId,
	            'approved' => 1,
	            'int_id' => $this->integration_store['id'],
	            'user_id' => 0,
	            'int_type' => 0, 
	            'variant' => $variant_num,
	            'mkt_product_id' => null, 
	            'mkt_sku_id' => null
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
			return;
		}
		if ($matchOk['success'] == 'Denied') {
			$this->errorTransformation($this->prd['id'],$skumkt, 'Match da VTEX reprovou o produto', 'Match de produto');
            return;
		}
		
		// aprovou...
		$vtexprodid = $matchOk['ProductId']; 
		if (is_null($vtexprodid)) {
			// Agora posso pegar o SKU de verdade  
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$matchOk['SkuId'];
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
			if ($this->responseCode != 200) {
				$notice = "Falha ao ler SKU do produto ".$this->prd['id']." id vtex:".$matchOk['SkuId']." httpcode :".$this->responseCode. " resposta: ".print_r($this->result, true);
                echo $notice."\n";
                $this->log_data('batch', $log_name, $notice,"E");
                die;
			}
			$sku_vtex  = json_decode($this->result);
			$vtexprodid = $sku_vtex->ProductId;
			$this->vtex_ref_id = $sku_vtex->RefId;				
		}
				
		// fez match então ficou ok e pode colocar o status_int = 2
        echo "Match realizado com sucesso! Id do produto local: ".$this->prd['id']."; Id do produto na Vtex: ".$vtexprodid." sku na vtex ".$matchOk['SkuId']."\n";

		$prd_to_integration['mkt_sku_id'] = $matchOk['SkuId']; 
		$prd_to_integration['mkt_product_id'] = $vtexprodid;
		$this->changeSkuSpecifications($prd_to_integration, $variant);
		$this->changeProductSpecifications($prd_to_integration);
		
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
            'mkt_sku_id' => $matchOk['SkuId']
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

	protected function getInformacoesTecnicas($attributesCustomProduct) {
		return false;
	}

	private function getProductImages($folder_ori, $path, $vardir = '')
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$folder = $folder_ori;
		if ($vardir != '') {
			$folder .= $vardir;
		}
		if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder)) {
			return null;
		}
		
        $images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
        
        if (!$images) {
            return null;
        }
        if (count($images) <= 2) {
			/*if ($vardir != '' ) { 
				return $this->getProductImages($folder_ori, $path, '');
			}*/
            return false;
        }
		$numft= 0;
		$imagesData = array();
		
		foreach($images as $foto) {
			if (($foto!=".") && ($foto!="..") && ($foto!="")) {
				if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder.'/'.$foto)) {
					$path_parts = pathinfo($foto);
					$this->fotos[] = $path_parts['filename'];	
					$data = [
		                'ImageUrl'  => base_url('assets/images/'.$path.'/' . $folder.'/'. $foto),
		                //'ImageName' => 'Imagem'.$numft,
		                'ImageName' => $path_parts['filename'],
		                'FileId'    => null
		            ];
					
		            array_push($imagesData, $data);
					$numft++;
				}
				
			}
		}

        return $imagesData;
    }
	
	 private function skuToMatch($skumkt, $prdSpec, $skuSpec, $variant_num =null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "pegando o score do marketplace"."\n";
		$this->getScore();
		
        echo "Pegando version do SKU."."\n";
        $version = $this->getVersion($skumkt);
		echo "version ". $version."\n";
        if ($version == false) {
            return array('success' => false, 'error' => 'Não achou uma versão da suggestion');
        }
		
		$macthType = 'newproduct';
		$ref_id = $this->prd['ref_id'];
		$ean = $this->prd['EAN'];
		
		if ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto
			if ((is_null($this->prd['ref_id'])) || (trim($this->prd['ref_id']) == '')) {
				$ref_id = $this->prd['id'];
				if (!is_null($variant_num)) {
					$ref_id .= '-'.$variant_num;
				}
				$macthType = 'productMatch';
				$ean = '';
			}
		}
		elseif (trim($this->prd['EAN']) == '') {
			if ((is_null($this->prd['ref_id'])) || (trim($this->prd['ref_id']) == '')) {
				$ref_id = $skumkt;
				$macthType = 'productMatch';
			}
		}
		
		if(!$this->auto_approve){
			$this->score_min=70;
		}
		// $this->score_min=80; //ricardo
		$data = [
            'matcherId' => 'vtex-matcher',
            'matchType' => 'productMatch',
            'score' => $this->score_min,
            'productRef' => $this->prd['ref_id'],
            'product' => [
            	'name' => $this->prd['name'],
            	'description' => $this->prd['description'],
            	'categoryId'  => (int)$this->prd['categoryvtex'], 
            	'brandId'     => $this->prd['brandvtex'], 
            	'specifications' => $prdSpec
            ],
            'SKU' => [
                'name' => $this->prd['skuname'],
                'eans' => [
                    $this->prd['EAN']
                ],
                'refId'  => $ref_id,
                'height' => $this->prd['altura'],
                'width'  => $this->prd['largura'],
                'length' => $this->prd['profundidade'],
                'weight' => $this->prd['peso_bruto'],
                'Images' => $this->prd['vteximages'], 
                'unitMultiplier'  => '1.0000',   
                'measurementUnit' => 'un', 
                'specifications' => $skuSpec
            ]
        ];

		var_dump($data);
        $bodyParams = json_encode($data);

        echo "Concluindo match."."\n";
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$this->sellerId .'/'.$skumkt.'/versions/'.$version.'/matches/1';
		
        $this->vtexHttpUrl($this->auth_data, $url,'PUT', $bodyParams, $this->prd['id'], $this->int_to, 'Match');

		$result = json_decode($this->result);
		var_dump($result);
		var_dump($this->responseCode);
		if ($this->responseCode !== 200) {
			return (array('success' => false, 'error' => $result->Error->Message)) ;
		}
		/* Resposta com Erro 
				object(stdClass)#40 (1) {
				  ["Error"]=>
				  object(stdClass)#42 (2) {
				    ["Code"]=>
				    int(2)
				    ["Message"]=>
				    string(32) "The property BrandId is required"
				  }
				} */
		/* Resposta com produto e SKU Novo : 
			object(stdClass)#42 (7) {
			  ["Operation"]=>
			  string(10) "NewProduct"
			  ["Message"]=>
			  string(85) "Product insertion successfully executed. Seller: MKTP462;\n Seller Item id: VarPAzul;"
			  ["Details"]=>
			  string(26) "Product id: 27; Sku id: 30"
			  ["ProductId"]=>
			  string(2) "27"
			  ["SkuId"]=>
			  string(2) "30"
			  ["SuggestionStatus"]=>
			  string(8) "Accepted"
			  ["Suggestion"]=>
			  NULL
			}
		 * 
		 * Resposta com match de produto
		 * "
			  ["Details"]=>
			  string(0) ""
			  ["ProductId"]=>
			  NULL
			  ["SkuId"]=>
			  string(2) "30"
			  ["SuggestionStatus"]=>
			  string(8) "Accepted"
			  ["Suggestion"]=>
			  NULL
			}
		 * 
		 * 
		 *Se não dá match.... 
		 * 
		 object(stdClass)#46 (7) {
			  ["Operation"]=>
			  string(17) "InsufficientScore"
			  ["Message"]=>
			  string(62) "Insufficient score for approval. Suggestion status: 'Pending'."
			  ["Details"]=>
			  string(46) "Suggestion: 2690/1756_cama01. Matches Updated."
			  ["ProductId"]=>
			  NULL
			  ["SkuId"]=>
			  NULL
			  ["SuggestionStatus"]=>
			  string(7) "Pending"
			  ["Suggestion"]=>
			  NULL
			}
		 * 
		
		** se foi rejeitado 
		object(stdClass)#100 (7) {
		  ["Operation"]=>
		  string(17) "InsufficientScore"
		  ["Message"]=>
		  string(62) "Insufficient score for approval. Suggestion status: 'Pending'."
		  ["Details"]=>
		  string(51) "Suggestion: 2690/3066_0_NovoMundo. Matches Updated."
		  ["ProductId"]=>
		  NULL
		  ["SkuId"]=>
		  NULL
		  ["SuggestionStatus"]=>
		  string(6) "Denied"
		  ["Suggestion"]=>
		  NULL
		}
		 * */

        return (array('success' => $result->SuggestionStatus, 'ProductId' => $result->ProductId, 'SkuId' =>$result->SkuId)) ;
    }

    private function getVersion($sku)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/'.$this->sellerId .'/'.$sku.'/versions';

        $this->vtexHttpUrl($this->auth_data, $url, 'GET',null, $this->prd['id'], $this->int_to, 'Última versão de sugestão');

		if ($this->responseCode !== 200) {
			$notice = "Falha ao tentar obter version do SKU. httpcode = ".$this->responseCode;
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			return false;
		}

        $result = json_decode($this->result);
		
		foreach($result as $version) {
			if ($version->IsLatest) {
				return $version->VersionId;
			}
		}
        return $result[0]->VersionId;
    }
	
	private function changeProductSpecifications($prd_to_integration)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if (!$this->update_product_specifications) {
			echo "Alterações de atributos de produtos desligada \n";
			return true; 
		}

		// Pego as specificações do produto
		$endPoint   = 'api/catalog_system/pvt/products/'.$prd_to_integration['mkt_product_id'].'/specification';
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Specification');
		$specifications  = json_decode($this->result, true);
		
		echo "Especificações atuais\n";
		var_dump($specifications);
		if (is_null($specifications)) {
			$specifications = array();
		}
		// Busco os atributos específicos do produto para este marketplace 
		$prodspecs = array();
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoryvtex'],$this->int_to);
		//var_dump($atributosCat);
		foreach($atributosCat as $atributoCat) {
			if ($atributoCat['variacao'] == 1 ) {
				continue;
			}
			$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to);
			if ($atributo_prd) {
				//var_dump($atributoCat);

				//var_dump($atributo_prd);
				$valor_id = null;
				$valores = json_decode($atributoCat['valor'],true);
				foreach($valores as $valor) {
					if ($valor['IsActive']) { 
						if (trim(strtoupper($valor['FieldValueId'])) ==  trim(strtoupper($atributo_prd['valor']))) {
							$valor_id = $atributo_prd['valor'];
							$atributo_prd['valor'] = $valor['Value'];
							break;
						}
					}
				}
				
				$prodspecs[] = array(
					'Id' =>  0, 
					'Name' => $atributoCat['nome'], 
					'Value' => array(trim($atributo_prd['valor'])), 
					
					'FieldId' => $atributo_prd['id_atributo'], 
					'FieldValueId' => $valor_id,
					'Text' => trim($atributo_prd['valor']),
					'Novo' => true, // Flag para verificar se todos já estão criados na vtex
				);
			}
		}
		var_dump($prodspecs);
		$spec_changed = false; 
		foreach ($specifications as $skey => $specification) { // verifica se alterou alguma especificação
			echo "ESPECIFICACAO ". $specification['Id']. "\n";
			$exist = false;
			foreach($prodspecs as $key => $prodspec) {
				if ($specification['Name'] == $prodspec['Name']) {
					$prodspecs[$key]['Novo'] = false; // Já está criado na vtex
					$exist = true;
					if ($specification['Value'][0] !== $prodspec['Value'][0]) { // mudou o valor do atributo
						$spec_changed = true;
						break 2;
					}
				}
			}
			// * incluir aqui os atributos que não são de categoria e verificar se esta especificação está lá 
			if (!$exist) {  // Não achou esta categoria, então removeram do sistema
				$spec_changed = true; 
				break;
			}
		}
		foreach($prodspecs as $prodspec) { //verifico se todos os atributos foram encontrados na Vtex
			if ($prodspec['Novo']) {
				$spec_changed = true; 
				break;
			}
		}
		echo "Analise das especificações\n";
		var_dump($prodspecs);
		if ($spec_changed) { // mudou algum atributo do produto na vtex 
		    if (count($specifications) > 0) { // apago tudo antigo
				$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'].'/specification';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'DELETE', null, $this->prd['id'], $this->int_to, 'Deletar Specification');
				if ($this->responseCode !== 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no DELETE '.$endPoint.' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
			}
			sleep(10);
			echo "Criando Novas Especificações\n";
			foreach($prodspecs as $key => $prodspec) { // cria novas especificações 
				$data=array(
					'FieldId' => $prodspec['FieldId'],
					'FieldValueId' =>  $prodspec['FieldValueId'],
					'Text' => $prodspec['Text']
				);
				var_dump($data);
				$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'].'/specification';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($data), $this->prd['id'], $this->int_to, 'Alterar Specification');
				if ($this->responseCode == 400) { 
					echo "A especificação ".$prodspec['Text']." não existe mais neste tipo de produto\n";
					continue;
				}
				if ($this->responseCode == 409) { 
					echo "A especificação ".$prodspec['Text']." já existe no produto\n";
					continue;
				}
				if ($this->responseCode !== 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no POST '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
			}
			
		}
	}

	private function updateProductVtex($prd_to_integration) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// pego a Marca
		$this->prd['brandvtex'] = $this->getBrandMarketplace($prd_to_integration['skumkt'], true);
		if ($this->prd['brandvtex']===false) {
			return false; 
		}
		
		if (!(array_key_exists('ref_id', $this->prd))) {
			$this->prd['ref_id'] = null;
		}
		if (!(array_key_exists('sku_id', $this->prd))) {
			$this->prd['sku_id'] = null;
		}
		
		$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'];
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Produto');
		//echo $this->responseCode; 
		$this->prd_vtex  = json_decode($this->result);
		
		$ref_id = $this->prd_vtex->RefId; 
		if (is_null($ref_id)) {
			if ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto - solicitação Ortobom
				$ref_id = $this->prd['id'];	
			}  
			else {
				$ref_id  = $this->prd['id'].'_'.$this->int_to;
			}
		}
		$prodvtex = array(
			'name' 			=> $this->prd['name'], 
			'CategoryId' 	=> (int)$this->prd['categoryvtex'], 
			'BrandId' 		=> $this->prd['brandvtex'], 
			'LinkId' 		=> $this->prd_vtex->LinkId,  
			//'RefId' 		=>	$ref_id,
			'IsVisible' 	=> true,
			'Description' 	=> $this->prd['description'],
			//'DescriptionShort' => $this->prd['description'],
			//'ReleaseDate' => $this->prd_vtex->ReleaseDate,
			//'KeyWords' 	=> $this->prd_vtex->IsVisible,
			'Title' 		=> $this->prd['name'],
			//'IsActive'	=> !$disable,
			//'TaxCode' 	=> $this->prd_vtex->TaxCode,
			//'MetaTagDescription' =>  $this->prd_vtex->MetaTagDescription,
			//'SupplierId'	=> $this->prd_vtex->SupplierId,
			//'ShowWithoutStock' => false,
			//'AdWordsRemarketingCode' => $this->prd_vtex->AdWordsRemarketingCode,
			//'LomadeeCampaignCode' => $this->prd_vtex->LomadeeCampaignCode,
			//'Score' 		=> $this->prd_vtex->Score,
		);
		
		// AQUI ALTERA O PRODUTO e NÂO O SKU 
		$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'];
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($prodvtex), $this->prd['id'], $this->int_to, 'Alterar Produto');
		$this->prd_vtex  = json_decode($this->result);
		var_dump($this->prd_vtex);
		if ($this->responseCode !== 200) {
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		
		// Acerta as políticas de preço / site 
		foreach($this->tradesPolicies as $tradePolicy) {
			$endPoint   = 'api/catalog/pvt/product/'.$prd_to_integration['mkt_product_id'].'/salespolicy/'.$tradePolicy;
			$tradepol = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($prodvtex), $this->prd['id'], $this->int_to, 'Alocando na Trade Policy '.$tradePolicy);
			//$this->prd_vtex  = json_decode($this->result);
			//var_dump($this->prd_vtex);
			if ($this->responseCode !== 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
		}
		$this->changeProductSpecifications($prd_to_integration);
	}

	private function updateSkuVtex($prd_to_integration, $variant, $disable)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// alteramos agora o sku 
		$skuvtex= array( 
			'Id' 					=> $prd_to_integration['mkt_sku_id'], 
			'ProductId' 			=> $prd_to_integration['mkt_product_id'],
			'IsActive' 				=> !$disable, 
			'ActivateIfPossible' 	=> !$disable,
			'Name' 					=> ($this->prd['has_variants']!= '') ? $this->prd['name']." ".str_replace(";", " / ", $variant['name']) : $this->prd['name'],
			'PackagedHeight' 		=> (float)$this->prd['altura'],
			'PackagedLength' 		=> (float)$this->prd['profundidade'],
			'PackagedWidth' 		=> (float)$this->prd['largura'],
			'PackagedWeightKg' 		=> (float)$this->prd['peso_bruto'],
			'Height' 				=> (float)$this->prd['actual_height'],
			'Length' 				=> (float)$this->prd['actual_depth'],
			'Width' 				=> (float)$this->prd['actual_width'],
			'WeightKg' 				=> (float)$this->prd['peso_liquido'],
			'IsKit'					=> false,
			'UnitMultiplier' 		=> 1, 
		);
		
		$commercialConditionId = $this->model_settings->getValueIfAtiveByName('vtex_commercial_condition_id');
		if ($commercialConditionId) {
			$skuvtex['CommercialConditionId'] = (int)$commercialConditionId;
		}
		
		if (is_null($this->vtex_ref_id)){
			if ($this->ref_id == 'ONLYID') {  // Se escolher RefId não é nem para definir EAN no produto - solicitação Ortobom
				$ref_id = $this->prd['id'];
				if (!is_null($variant)) {
					$ref_id .= '-'.$variant['variant'];
				}
				$skuvtex['RefId'] = $ref_id; // Força o Id do produto para a Ortobom....
			}
			elseif (trim($this->prd['EAN']) == '') {
				if ((is_null($this->prd['ref_id'])) || (trim($this->prd['ref_id']) == '')) {
					//$ref_id =  $prd_to_integration['store_id'].'_'.$prd_to_integration['mkt_sku_id'];  // acerto para mandar o refid quando não tem EAN; 
					///$skuvtex['RefId'] = $ref_id; // Força o Id do produto....
					$skuvtex['RefId'] = $prd_to_integration['skumkt'];  // acerto para mandar o refid quando não tem EAN; 
				}
			}
		}
		else {
			$skuvtex['RefId'] = $this->vtex_ref_id; 
		}
		
		// AQUI ALTERA O SKU e NÂO O PRODUTO 
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'];
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($skuvtex), $this->prd['id'], $this->int_to, 'Alterar SKU');
		$sku_vtex  = json_decode($this->result);
		var_dump($sku_vtex);
		if ($this->responseCode !== 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}

		$this->changeSkuSpecifications($prd_to_integration, $variant);
		/*
		if ($this->update_images_specifications) {
			$this->changeSkuImage($prd_to_integration, $variant);
		}
		*/
		
	}
	
	private function changeSkuSpecifications($prd_to_integration, $variant)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if (!$this->update_sku_specifications) {
			echo "Alterações de atributos de variações (SKU) desligada \n";
			return true; 
		}
		
		// Pego as specificações do produto
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler SKU Specification');
		$specifications  = json_decode($this->result, true);
		
		echo "Especificações atuais do SKU\n";
		if (is_null($specifications)) {
			$specifications = array();
		}
		foreach($specifications as $key => $value) {
			$specifications[$key]['deleteme'] = true; 
		}
		var_dump($specifications);
		
		// Busco os atributos específicos do produto para este marketplace 
		
		$prodspecs = array();
		$variants = explode(';', $this->prd['has_variants']);
		$variants_value  = explode(';', $variant['name']);
		foreach( $variants as $key => $value) {
			$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryvtex'],ucfirst(strtolower($value)),$this->int_to, !empty($this->prd['has_variants']));
			$valor_id = null;
			$field_id=0;
			if ($atributoCat) {
				if ($atributoCat['variacao'] == 0) {
					$notice = "Esta categoria não aceita variação por ".$value;
					$this->errorTransformation($this->prd['id'],$prd_to_integration['skumkt'], $notice, 'Preparação para o envio');
		            return;
				}
				$field_id = $atributoCat['id_atributo'];
				$valores = json_decode($atributoCat['valor'],true);
				foreach($valores as $valor) {
					if ($valor['IsActive']) { 
						if (trim(strtoupper($valor['Value'])) ==  trim(strtoupper($variants_value[$key]))) {
							$valor_id = (int)$valor['FieldValueId'];
							break;
						}
					}
				}
				if ($field_id!=0) {
					$prodspecs[] = array(
						'FieldId' 		=> (int)$atributoCat['id_atributo'],
						'FieldValueId' 	=> $valor_id,
					);
				}
			}
		}
		
		// Agora vejo se algum atributo deveria ser variação mas não foi. 
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoryvtex'],$this->int_to);
		//var_dump($atributosCat);
		foreach($atributosCat as $atributoCat) {
			if ($atributoCat['variacao'] == 1) {
				$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to);
			    //var_dump($atributo_prd);
				if ($atributo_prd) {   // o atributo está preenchido, vou pegar seus valores
					$valor_id = null;
					$valores = json_decode($atributoCat['valor'],true);
					foreach($valores as $valor) {
						if ($valor['IsActive']) { 
							if (trim(strtoupper($valor['FieldValueId'])) ==  trim(strtoupper($atributo_prd['valor']))) {
								$valor_id = array((int)$atributo_prd['valor']);
								$atributo_prd['valor'] = $valor['Value'];
								$prodspecs[] = array(
									'FieldId' 		=> (int)$atributoCat['id_atributo'],
									'FieldValueId'  => (int)$valor['FieldValueId'],
								);
								break ;
							}
						}
					}
				}
			}
		}


		echo "Especificações que deveria ter\n";
		var_dump($prodspecs);
		
		
		foreach($prodspecs as $prodspec) {
			$achou = false;
			foreach($specifications as $specification) {
				$specifications[$key]['deleteme'] = true; 
				if ($prodspec['FieldId'] == $specification['FieldId']) {  // já existe na Vtex 
					$specifications[$key]['deleteme'] = false; 
					$achou = true; 
					if ($prodspec['FieldValueId'] != $specification['FieldValueId']) { // valor está diferente, então tem que trocar
						$data=array(
							'Id'			=> $specification['Id'],
							'SkuId'			=> $specification['SkuId'],
							'FieldId' 		=> $prodspec['FieldId'],
							'FieldValueId' 	=> $prodspec['FieldValueId']
						);
						$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
						$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'PUT', json_encode($data), $this->prd['id'], $this->int_to, 'Alterar SKU Specification');
						if ($this->responseCode !== 200) { 
							$erro = 'Erro httpcode: '.$this->responseCode.' no POST '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
							echo $erro."\n";
							$this->log_data('batch',$log_name, $erro ,"E");
							die;
						}
					}
				}
			}
			if (!$achou) { // Não achou, tem que criar
				$data=array(
					'FieldId' 		=> $prodspec['FieldId'],
					'FieldValueId' 	=> $prodspec['FieldValueId'],
				);
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($data), $this->prd['id'], $this->int_to, 'Criar SKU Specification');
				if ($this->responseCode !== 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no POST '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
			}
		}
		foreach($specifications as $specification) {
			continue ; // rick - Não consegui testar abaixo e não sei se deve ocorrer... 
			if ($specification['deleteme']) { // sumiu aqui, então remove da Vtex
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification/'.$specification['Id'];
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'DELETE', null, $this->prd['id'], $this->int_to, 'Removendo SKU Specification');
				if ($this->responseCode !== 200) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no DELETE '.$endPoint.' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				
			}
		}
		return;
		
		// Método Antigo que removia tudo....
	    if (!is_null($specifications)) { // apago tudo antigo
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'DELETE', null, $this->prd['id'], $this->int_to, 'Deletar SKU Specification');
			if ($this->responseCode !== 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' no DELETE '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
		}
		echo "Criando Novas Especificações\n";
		foreach($prodspecs as $key => $prodspec) { // cria novas especificações 
			$data=array(
				'FieldId' 		=> $prodspec['FieldId'],
				'FieldValueId' 	=> $prodspec['FieldValueId'],
			);
			var_dump($data);
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/specification';
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($data), $this->prd['id'], $this->int_to, 'Alterar Specification');
			if ($this->responseCode !== 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' no POST '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
		}

	}
	
	private function changeSkuImage($prd_to_integration,$variant)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// pego as fotos atuais
		// Monto array com as imagens do produto
		$vardir = '';
		// pego as fotos do pai 
		$this->fotos = array(); 
		
		$images = $this->getProductImages($this->prd['image'], $this->pathImage,  $vardir);
		if  (!is_null($variant)){
			$imagesVar = array();
			if ($this->pathImage == 'product_image') {
				if (!is_null($variant['image']) && trim($variant['image'])!='')	{
					$vardir = '/'.$variant['image'];
					$images_var = $this->getProductImages($this->prd['image'], $this->pathImage,  $vardir);
				}
			}
			else { // produto de catálogo
				$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$variant['variant'] ); 
				if ($var_cat) {
					$images_var = $this->getProductImages($var_cat['image'], $this->pathImage,  '');
				}
			}
			if (is_array($images_var)) {
				$images = array_merge($images_var, $images);  // junto as imagens da variação premeiro e depois a do pai
			}
		} 
		

		echo "Verificando imagens da Vtex\n";
		// vou pegar as imagens
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/file';
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Listar Imagens');
		$sku_files  = json_decode($this->result);
		//echo " Na vtex \n";
		//var_dump($sku_files);
		if ($this->responseCode == 404) { 
			$erro = 'Local de imagens não encontrado na Vtex httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			//return false;
			$sku_files= array();
		} 
		elseif ($this->responseCode !== 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		//echo " Aqui \n";
		//var_dump($this->fotos);
		$alterou_fotos = (count($sku_files) !== count($this->fotos));
		if (!$alterou_fotos) {
			foreach($sku_files as $file) {
				//var_dump($file);
				if (!in_array($file->Name, $this->fotos)) {
					$alterou_fotos = true;
					echo "Não achei a  foto ".$file->Name." em ".print_r($this->fotos,true)."\n"; 
					break;
				}
			}
		}
		echo "Fotos na vtex=".count($sku_files)." Fotos aqui=".count($this->fotos)."\n";
		if ($alterou_fotos) {
			echo "Removendo fotos\n";
			$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/file';
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'DELETE', null, $this->prd['id'], $this->int_to, 'Deletar Imagens');
			if (($this->responseCode !== 200) && ($this->responseCode !== 202) && ($this->responseCode !== 204)) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' no DELETE chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}

			$principal = true;
			$cnt = 0;
			foreach($images as $image){
				echo "Enviando imagem ".$image['ImageName']."\n";
				$path_parts = pathinfo($image['ImageName']);
				$data = array(
					'IsMain' => $principal,
					"Label" => "",
					'Name' => $path_parts['filename'],
					"Text" => null,
					'Url' => $image['ImageUrl'],
				);
				$principal = false;
				$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$prd_to_integration['mkt_sku_id'].'/file';
				$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'POST', json_encode($data), $this->prd['id'], $this->int_to, 'Criar Imagem');
				if (($this->responseCode !== 200) && ($this->responseCode !== 202) && ($this->responseCode !== 204)) { 
					$erro = 'Erro httpcode: '.$this->responseCode.' no POST chamar '.$endPoint.' enviado '.print_r(json_encode($data),true).' result '.print_r($this->result,true);
					echo $erro."\n";
					$this->log_data('batch',$log_name, $erro ,"E");
					die;
				}
				$cnt++;
				if ($cnt >= 50) {  // Vtex só aceita 50 imagens
					break;
				}
				//var_dump(json_decode($this->result));
			}
		}
	}

	function getVtexSkuProductId($variant_num, $skumkt)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// tenho q fazer get do sku do seller primeiro 
		$endPoint   = 'api/catalog_system/pvt/skuseller/'.$this->sellerId .'/'.$skumkt;
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku do Seller');
		if ($this->responseCode != 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$sku_vtex  = json_decode($this->result);
		echo 'Refid = '.$sku_vtex->StockKeepingUnitId."\n";
		
		// Agora posso pegar o SKU de verdade  
		$endPoint   = 'api/catalog/pvt/stockkeepingunit/'.$sku_vtex->StockKeepingUnitId;
		$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Sku Completo');
		if ($this->responseCode != 200) { 
			$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			die;
		}
		$sku_vtex  = json_decode($this->result);
		$this->vtex_ref_id = $sku_vtex->RefId;
		
		if (!is_null($this->adlink)) {
			// Agora posso pegar o products  de verdade  
			$endPoint   = 'api/catalog/pvt/product/'.$sku_vtex->ProductId;
			$skuExist = $this->vtexHttp($this->auth_data, $endPoint, 'GET', null, $this->prd['id'], $this->int_to, 'Ler Produto');
			if ($this->responseCode != 200) { 
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint.' result '.print_r($this->result,true);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				die;
			}
			$this->prd_vtex  = json_decode($this->result);
		}
		
		return array (
			'mkt_product_id' => $sku_vtex->ProductId,
        	'mkt_sku_id' => $sku_vtex->Id 
		);
			
	}
	

	function productAtVtex($variant_num, $skumkt) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$erro = 'Produto '.$this->prd['id'].' já cadastrado no marketplace '.$this->int_to.' com sku '.$skumkt. ' Criando tabelas prd_to_integration e vtex_ult_envio';
		echo $erro."\n";
		
		$ids= $this->getVtexSkuProductId($variant_num, $skumkt);
		
		$toSavePrd = [
            'prd_id' => $this->prd['id'],
            'company_id' => $this->prd['company_id'],
            'status' => 1,
            'status_int' => 2,
            'date_last_int' =>$this->dateLastInt, 
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
            'mkt_product_id' => $ids['mkt_product_id'],
            'mkt_sku_id' => $ids['mkt_sku_id'],  
            'ad_link' => (is_null($this->adlink)) ? '' : $this->adlink . $this->prd_vtex->LinkId."/p"
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

/*
		$toSaveUltEnvio = [
            'int_to' => $this->int_to,
            'company_id' => $this->prd['company_id'],
            'EAN' => $this->prd['EAN'],
            'prd_id' => $this->prd['id'],
            'price' => $this->prd['promotional_price'],
            'sku' => $this->prd['sku'],
            'data_ult_envio' => $this->dateLastInt, 
            'qty_atual' => $this->prd['qty'],
            'largura' => $this->prd['largura'],
            'skumkt' => $skumkt,
            'altura' => $this->prd['altura'],
            'profundidade' => $this->prd['profundidade'],
            'peso_bruto' => $this->prd['peso_bruto'],
            'store_id' => $this->prd['store_id'],
            'seller_id' => $this->sellerId ,
            'crossdocking' => $this->prd['prazo_operacional_extra'],
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
			'variant' => $variant_num,
        ];
        $savedUltEnvio = $this->model_vtex_ult_envio->createIfNotExist($this->prd['id'], $this->int_to, $variant_num, $toSaveUltEnvio);
		*/
			
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
            'tipo_volume_codigo' => $this->model_category->getTipoVolumeCategory(json_decode($this->prd['category_id'])[0] ?? 0)
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
	
	function checkAutoApproveSeller() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        $url = 'https://api.vtex.com/'.$this->accountName.'/suggestions/configuration/autoapproval/toggle?sellerid='.$this->sellerId ;

        $this->vtexHttpUrl($this->auth_data, $url, 'GET',null);

		if ($this->responseCode !== 200) {
			$notice = "Falha ao tentar obter configuração de autoa aapproval. Ull:".$url."httpcode = ".$this->responseCode;
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
		}

        $result = json_decode($this->result);
        return $result->Enabled;
	}
	
	protected function vtexHttp($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
        $this->accountName = $separateIntegrationData->accountName;
		if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
	        $this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
	            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
	        ];
	        if (isset($separateIntegrationData->suffixDns)) {
	            if (!is_null($separateIntegrationData->suffixDns)) {
		            $this->setSuffixDns($separateIntegrationData->suffixDns);
		        }
	        } 
			
	        $url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;
		} else {   // LinkApi
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	           // "apiKey: $separateIntegrationData->apiKey",
	        ];
	        $url = $separateIntegrationData->site .'/'.$endPoint;
			$url .= "?apiKey=".$separateIntegrationData->apiKey;
		}
		echo "------------------- Chamando: ".$url."\n";
		
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
		
		if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		var_dump( $this->result );
		
		if ($this->responseCode == 429) {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
		}
        if ($this->responseCode == 503) {
		    $this->log("Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
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

	protected function vtexHttpUrl($separateIntegrationData, $url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null )
    {
        $this->accountName = $separateIntegrationData->accountName;
		if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
	        $this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
	            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
	        ];
		} else {   // LinkApi
			$this->header = [
	            'content-type: application/json',
	            'accept: application/json',
	            // "apiKey: $separateIntegrationData->apiKey",
	        ];
			// troca o site da Vtex pelo site da LinkApi 
			$url = str_replace('https://api.vtex.com/'.$this->accountName,$separateIntegrationData->site.'/api/'.$this->accountName, $url);
			$url .= "?apiKey=".$separateIntegrationData->apiKey;
		}
		echo "****************    Chamando: ".$url."\n";
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
		
		if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		var_dump( $this->result );
		if ($this->responseCode == 429) {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data);
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data);
		}
        if ($this->responseCode == 503) {
		    $this->log("Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'function' => $function,
				'url' => $url,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
		
        return;
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