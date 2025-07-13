<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na Vertem Hub - Vrtem Store
 */

use GuzzleHttp\Client;

require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

/**
 * @property Model_settings $model_settings
 * @property Bucket			$bucket
 */
class Products_VS extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal ;
	var $integration;
	var $vs_last_post = null;

    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		$this->load->model('model_marketplace_prd_variants'); 
		$this->load->model('model_settings'); 	
		$this->load->model('model_vs_last_post');
		$this->load->model('model_vs_products');
		$this->load->library('bucket');
        $this->load->helper('validation');
		
    }

    private function sendProductsCallback($produto)
    {
        $produto = json_decode($produto, true);

        $vertemProductWebhook = $this->model_settings->getSettingDatabyName("vertem_product_webhook");
        if ($vertemProductWebhook && $vertemProductWebhook["status"] == 1) {

            $url = $vertemProductWebhook["value"];
            if (empty($url)) {
                return;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $data_log = array(
                    'int_to'    => $this->int_to,
                    'prd_id'    => $this->prd['id'],
                    'function'  => 'Envio de Callback',
                    'url'      => $url,
                    'method'    => 'Update',
                    'sent'     => json_encode($produto, JSON_UNESCAPED_UNICODE),
                    'response'  => 'Url inválida. Forneça uma url válida no parâmetro "vertem_product_webhook", exemplo: https://www....',
                    'httpcode'  => false,
                );
                $this->model_log_integration_product_marketplace->create($data_log);
                return;
            }

            $data_log = array(
                'int_to'    => $this->int_to,
                'prd_id'    => $this->prd['id'],
                'function'  => 'Envio de Callback',
                'url'      => $url,
                'method'    => 'POST',
                'sent'     => json_encode($produto, JSON_UNESCAPED_UNICODE),
                'response'  => 'Enviado com sucesso',
                'httpcode'  => true,
            );

			try {
                $client = new Client([
                    'verify' => true, // no verify ssl
                    'timeout' => 20000,
                ]);
                $response = $client->request('POST', $url, [
                    'json' => $produto
                ]);
				$data_log['httpcode'] = $response->getStatusCode();
				$data_log['response'] = $response->getBody()->getContents();
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
				// log the error here
				$data_log['response'] = $e->getMessage();
				$data_log['httpcode'] = false;echo " Erro no callback. URL->".$url.' resposta '.$data_log['response']."\n";
			}catch (GuzzleHttp\Exception\ClientException $e) {				
				$data_log['response'] = $e->getResponse()->getBody()->getContents();
				$data_log['httpcode'] =  $e->getResponse()->getStatusCode();
				
				echo " Erro no callback. URL->".$url.' httpcode: '.$data_log['httpcode'].' resposta '.$data_log['response']."\n";
            } catch (Exception $e) {
				$data_log['response'] = $e->getMessage(); 
				$data_log['httpcode'] = false;
				echo " Erro no callback. URL->".$url.' resposta '.$data_log['response']."\n";
			}

            $this->model_log_integration_product_marketplace->create($data_log);
        }
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
		$this->int_to='VS';
		$this->getkeys();
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
		$this->prd_to_integration['skubling']= $sku;

		$this->removeVariantsPrdIntegration();
		
		$skumkt = $sku; 
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para o envio');
		
		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku)==false) { return false;};
		
		// Monto o Array 
		$status = ($this->prd['qty'] == 0) ? 2 : 1;
		$produto = $this->montaArray($sku, true, 1);
		if ($produto==false) { return false;};
		
		echo 'Incluindo o produto '.$this->prd['id'].' '.$this->prd['name']."\n";
		
		$data = array(
			'prd_id' 		=> $this->prd['id'],
			'store_id' 		=> $this->prd['store_id'],
			'company_id'	=> $this->prd['company_id'],
			'seller_id' 	=> $this->seller_id,
			'json'   		=> $produto,
		);
		
		$this->model_vs_products->createIfNotExist($this->prd['id'], $data);
		
		$data_log = array( 
				'int_to' 	=> $this->int_to,
				'prd_id' 	=> $this->prd['id'],
				'function' 	=> 'Registrado para envio',
				'url' 		=> 'Tabela vs_products',
				'method' 	=> 'Create',
				'sent' 		=> $produto,
				'response' 	=> 'Gravado com sucesso',
				'httpcode' 	=> true,
			);
		$this->model_log_integration_product_marketplace->create($data_log);
		
		$this->model_integrations->updatePrdToIntegration(
			array (
				'skubling' 		=> $sku,
				'skumkt' 		=> $skumkt,
				'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
				'date_last_int' => $this->dateLastInt
			), $this->prd_to_integration['id']);

		$this->prd_to_integration['skubling'] = $sku;
		$this->prd_to_integration['skumkt'] = $skumkt;
		if ($this->prd['has_variants']!='') {
			foreach($this->variants as $variant) {
                $prd = $this->prd;
                $prd['qty']                 = $variant['qty'];
                $prd['qty_original']        = $variant['qty_original'];
                $prd['sku'] 			    = $variant['sku'];
				$prd['price'] 			    = $variant['price'];
				$prd['promotional_price']   = $variant['promotional_price'];
				$prd['EAN'] 			    = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'];

                if ($variant['status'] != 1) {
                    $prd['qty'] = 0;
                    $prd['qty_original'] = 0;
                }

				$this->updateVSLastPost($prd, $variant);
                $this->sendProductsCallback($produto);
			}
		}else{
			$this->updateVSLastPost($this->prd, null);
            $this->sendProductsCallback($produto);
		}

		return true;	
	}
	
	function updateProduct($disable = false)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Update'."\n";
		
		$this->removeVariantsPrdIntegration();
		
		$sku = $this->prd_to_integration['skubling']; 
		$skumkt = $this->prd_to_integration['skumkt']; 
		$skupai = $sku;
		
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,"Preparação para o envio");
		
		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku)==false) { return false;};
		
		// Monto o Array para enviar para o Mercado Livre
		$status = ($disable) ? 2 : 1; 
		$status = ($this->prd['qty'] == 0) ? 2 : $status;
		$produto = $this->montaArray($sku, false, $status);
		if ($produto==false) { return false;};
		
		echo 'Alterando o produto '.$this->prd['id'].' '.$this->prd['name']."\n";
		$data = array(
			'prd_id' 		=> $this->prd['id'],
			'store_id' 		=> $this->prd['store_id'],
			'company_id' 	=> $this->prd['company_id'],
			'seller_id' 	=> $this->seller_id,
			'json'   		=> $produto,
		);
		
		$this->model_vs_products->createIfNotExist($this->prd['id'], $data);
		
		$data_log = array( 
				'int_to' 	=> $this->int_to,
				'prd_id' 	=> $this->prd['id'],
				'function' 	=> 'Registrado para envio',
				'url' 		=> 'Tabela vs_products',
				'method' 	=> 'Update',
				'sent' 		=> $produto,
				'response' 	=> 'Gravado com sucesso',
				'httpcode' 	=> true,
			);
		$this->model_log_integration_product_marketplace->create($data_log);
		
		$this->model_integrations->updatePrdToIntegration(
			array (
				'skumkt' 		=> $skumkt,
				'skubling' 		=> $sku,
				'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
				'date_last_int' => $this->dateLastInt
			), $this->prd_to_integration['id']);
		$this->prd_to_integration['skumkt'] = $skumkt;
		$this->prd_to_integration['skubling'] = $sku;
		
		if ($this->prd['has_variants']!='') {
			foreach($this->variants as $variant) {
				$prd = $this->prd;
				$prd['qty']             = $variant['qty'];
                $prd['qty_original']    = $variant['qty_original'];
                $prd['sku'] 			= $variant['sku'];
				$prd['price'] 			= $variant['price'];
				$prd['EAN'] 			= ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'];

                if ($variant['status'] != 1) {
                    $prd['qty'] = 0;
                    $prd['qty_original'] = 0;
                }

				$this->updateVSLastPost($prd, $variant);
                $this->sendProductsCallback($produto);
			}
		}else{
			$this->updateVSLastPost($this->prd, null);
            $this->sendProductsCallback($produto);
		}
		return true;
		
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		
		$this->removeVariantsPrdIntegration();
		
		$this->prd['qty'] = 0; // zero a quantidade do produto
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = 'As variações deste produto '.$this->prd['id'].' sumiram.';
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,'E');
				$disable = true; // melhor dar disable na skyhub 
			}
			foreach($this->variants as $key => $variant) {
				$this->variants[$key]['qty'] = 0;  // zero a quantidade da variant tb
			}
		}
		$this->updateProduct($disable);
		$this->model_integrations->updatePrdToIntegration(
			array(
				'status_int' 	=> $status_int, 
				'date_last_int' => $this->dateLastInt
			),$this->prd_to_integration['id']);
	}

	function getkeys() {
		//pega os dados da integração. 
		$this->getIntegration(); 
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		
		$auth_data = json_decode($this->integration_store['auth_data']);
		
		$this->seller_id = $auth_data->seller_id;
		//$this->setApikey($api_keys['apikey']);
	//	$this->setEmail($api_keys['email']);
	}

	function getIntegration() 
	{
		
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				if ($this->integration_store['int_from'] == 'CONECTALA') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto('0',$this->int_to);
				}elseif ($this->integration_store['int_from'] == 'HUB') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
				} 
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
	}
	
	public function getCategoryMarketplace($skumkt,$int_to = '', $mandatory_category = true)
	{
		if 	($int_to == '') {$int_to=$this->int_to; }
			
		$categoryId = json_decode($this->prd['category_id']);
		if (is_array($categoryId)) {
			$categoryId = $categoryId[0];
		}
   		$category   = $this->model_category->getCategoryData($categoryId);
		if (!$category) {
			$msg= 'Produto sem categoria.';
			echo 'Produto '.$this->prd['id'].' '.$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, 'Preparação para o envio');
			return false;
		}

		$this->prd['categoryname'] = $category['name']; 
		
		// pego o tipo volume da categoria 
		$tipo_volume   = $this->model_category->getTiposVolumesByCategoryId($categoryId);
		$this->prd['tipovolumecodigo'] = is_null($tipo_volume) ? null : $tipo_volume['codigo']; 	
		
		return $categoryId;
	}
	
	function prepareProduct($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Preparando produto'."\n";
		
		// busco a categoria 
		$this->prd['categoria_mktp'] = $this->getCategoryMarketplace($sku);
		if ($this->prd['categoria_mktp']==false) {
			return false;
		}
	
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];
		if ((int)$this->prd['qty'] < 0) { 
			$this->prd['qty']  = 0;
		}
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arredondo para cima 
		
		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);
		if ($this->prd['promotional_price'] > $this->prd['price'] ) {
			$this->prd['price'] = $this->prd['promotional_price']; 
		}

		// se tiver Variação,  acerto o estoque de cada variação
    	if ($this->prd['has_variants']!='') {
    		$variações = explode(";",$this->prd['has_variants']);
			
			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
				$this->variants[$key]['qty_original'] =$variant['qty'];
				if  ((int)$this->variants[$key]['qty'] < 0) { 
					$this->variants[$key]['qty'] = 0;
				}
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
					$this->variants[$key]['price'] = $this->prd['price'];
				}
				
				$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
				if ($this->variants[$key]['promotional_price'] > $this->variants[$key]['price'] ) {
					$this->variants[$key]['price'] = $this->variants[$key]['promotional_price']; 
				}
				
				//ricardo, por enquanto, o preço da variação é igual ao do produto. REMOVER DEPOIS QUE AS INTEGRAÇÔES ESTIVEREM CONCLUIDAS
				//$this->variants[$key]['price'] = $this->prd['price'];
				//$this->variants[$key]['promotional_price'] = $this->prd['promotional_price']; 
				
			}
		}
		
		if ($this->prd['is_kit']) {  // B2W consegue mostrar o preço original dos produtos que o componhe 
			$productsKit = $this->model_products->getProductsKit($this->prd['id']);
			$original_price = 0; 
			foreach($productsKit as $productkit) {
				$original_price += $productkit['qty'] * $productkit['original_price'];
			}
			$this->prd['price'] = $original_price;
			echo ' KIT '.$this->prd['id'].' preço de '.$this->prd['price'].' por '.$this->prd['promotional_price']."\n";  
		}
		
		//leio a brand
		if ($this->getBrandMarketplace($sku,true) == false) return false;
		
		// marco o prazo_operacional para pelo menos 0 dia
		if ($this->prd['prazo_operacional_extra'] < 0 ) { $this->prd['prazo_operacional_extra'] = 0; }
		
		return true;
	}

	private function getProductImages($folder_ori, $path, $vardir = '', $variacao = false)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$folder = $folder_ori;
		if ($vardir !== '') {
			$folder .= $vardir;
		} elseif ($variacao) {
			return []; // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}
		$imagesData = [];

		if ($this->prd["is_on_bucket"]) {
			$images = $this->bucket->getFinalObject('assets/images/' . $path . '/' . $folder);
			if (empty($images['contents'])) {
				return [];
			}
			$images = $images['contents'];
			foreach ($images as $foto) {
				$imagesData[] = $foto['url'];
			}
		} else {
			$images = scandir(FCPATH . 'assets/images/' . $path . '/' . $folder);

			if (!$images) {
				return [];
			}
			if (count($images) <= 2) { // não achei nenhuma imagem
				if ($variacao) { // Mas é uma variação, retorna o array vazio
					return  [];
				}
				return [];
			}
			foreach ($images as $foto) {
				if (($foto != '.') && ($foto != '..') && ($foto != '')) {
					if (!is_dir(FCPATH . 'assets/images/' . $path . '/' . $folder . '/' . $foto)) {
						$image_url = base_url('assets/images/' . $path . '/' . $folder . '/' . $foto);
						$image_url = str_replace('http://', 'https://', $image_url);
						$imagesData[] = str_replace('conectala.tec.br', 'conectala.com.br', $image_url);
						// $imagesData[] = base_url('assets/images/'.$path.'/' . $folder.'/'. $foto);
					}
				}
			}
		}
		return $imagesData;
	}

	function montaArray($sku, $novo_produto = true, $status = 1) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		$description = substr(htmlspecialchars(strip_tags(str_replace('<br>'," \n",$this->prd['description'])), ENT_QUOTES, "utf-8"),0,3800);
		$description = str_replace("&amp;amp;"," ",$description);
		$description = str_replace("&amp;"," ",$description);
		$description = str_replace("&nbsp;"," ",$description);
		if (($description=='') || (trim(strip_tags($this->prd['description'])," \t\n\r\0\x0B\xC2\xA0")) == ''){
			$description= substr(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8"),0,98);
		}
		
		$carGer = '';
		
		// rick : onde manda a marca do produto
		$carGer .= 'Marca: '.$this->prd['brandname'].'. ';
		$carGer .= 'Altura: '.(int)$this->prd['altura'].' cm. ';
		$carGer .= 'Largura: '.(int)$this->prd['largura'].' cm. ';
		$carGer .= 'Profundidade: '.(int)$this->prd['profundidade'].' cm. ';
		$carGer .= 'Peso bruto: '.number_format($this->prd['peso_bruto'], 2, ",", ".").' kg. ';	
		
		
		if (!is_null($this->prd['actual_width']) && trim($this->prd['actual_width']!=='')) {
			$carGer .= 'Largura desembalado: '.(int)$this->prd['actual_width'].' cm. ';
		}
		if (!is_null($this->prd['actual_height']) && trim($this->prd['actual_height']!=='')) {
			$carGer .= 'Altura desembalado: '.(int)$this->prd['actual_height'].' cm. ';
		}
		if (!is_null($this->prd['actual_depth']) && trim($this->prd['actual_depth']!=='')) {
			$carGer .= 'Profundidade desembalado: '.(int)$this->prd['actual_depth'].' cm. ';
		}
		if (!is_null($this->prd['peso_liquido']) && trim($this->prd['peso_liquido']!=='')) {
			$carGer .= 'Peso líquido: '.number_format($this->prd['peso_liquido'], 2, ",", ".").' kg. ';
		}
		if (!is_null($this->prd['garantia']) && ($this->prd['garantia']>0)) {
			$carGer .= 'Garantia: '.(int)$this->prd['garantia'].' meses. ';
		}
		
		$espTec = '';
		$attibutes_custom = $this->model_products->getAttributesCustomProduct($this->prd['id']);
		foreach ($attibutes_custom as $attibute_custom) {
			$espTec .= $attibute_custom['name_attr'].': '.$attibute_custom['value_attr'].'. ';
		}
		
		$imagem_pai = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		
		$productSkus = Array();
		if ($this->prd['has_variants']=='') {
			$price = $this->prd['price'] * 100;
			$promotional_price = $this->prd['promotional_price'] * 100;
			
			if (empty($imagem_pai)) {
				$msg= 'Produto sem imagem.';
				echo 'Produto '.$this->prd['id'].' '.$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku ,$msg, 'Preparação para o envio');
				return false;	
			}
			
			$productSkus[] = Array(
                'originalSku'   	=> $this->prd['sku'],
				'productSkuId' 		=> $sku, 
				'skuStatusId' 		=> $status,  // status 1 para ativo e 2 para inativo ou indisponível.
				'ean' 				=> $this->prd['EAN'], 
				'priceFrom' 		=> (int)$price, 
				'priceFor' 			=> (int)$promotional_price, 
				'skuFeatures'		=> array(),
				'skuImages' 		=> $this->formatImages($imagem_pai),
				'amount'   			=> (int)$this->prd['qty'], 
				'additionalTime' 	=> (is_null($this->prd['prazo_operacional_extra'])) ? 0 : (int)$this->prd['prazo_operacional_extra'], 
			);
		}
		else { // tem variaçãoes.
            $tipos = explode(';',$this->prd['has_variants']);
		    
			foreach($this->variants as $key => $variant) {
			  	if (isset($variant['sku'])) { 
					$values = explode(';',$variant['name']);
					$skuFeatures = Array();
					foreach ($tipos as $z => $campo) {
						$campo = ucfirst(strtolower($campo));
						switch ($campo) {
						    case "Cor":
						        $featureType =1;
						        break;
						    case "Tamanho":
						        $featureType =2;
						        break;
						    case "Voltagem":
						      	$featureType =3;
						        break;
							default: 
							    $featureType= 0;
							    $campo = 'Desconhecido';
						};
						$skuFeatures[] = array(
						    'featureType' => $featureType,
							'name' => $campo,
							'value' => $values[$z]
						);
					}
					$vardir = '';
					$images_var = array();
					if (($this->pathImage == 'product_image')) {
						if (!is_null($variant['image']) && trim($variant['image'])!='')	{
							$vardir = '/'.$variant['image'];
						}
						$images_var	= $this->getProductImages($this->prd['image'], $this->pathImage, $vardir, true); 
					} else {
						$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$variant['variant'] ); 
						if ($var_cat) {
							$images_var	= $this->getProductImages($var_cat['image'], $this->pathImage, '', false); 
						}
					}
					$images = array_merge($images_var, $imagem_pai);  // junto as imagens da variação premeiro e depois a do pai 
					
					if (empty($images)) {
						$msg= 'Produto sem imagem.';
						echo 'Produto '.$this->prd['id'].' '.$msg."\n";
						$this->errorTransformation($this->prd['id'], $sku ,$msg , 'Preparação para o envio');
						return false;	
					}
					
					$price = $variant['price'] * 100;
					$promotional_price = $variant['promotional_price'] * 100;
					
					$statusvar = ($status != 1) ? 2 : (($variant['qty'] <= 0) ? 2 : 1);
					
					$productSkus[] = Array(
                        'originalSku'   	=> $variant['sku'],
						'productSkuId' 		=> $sku.'-'.$variant['variant'],
						'skuStatusId' 		=> $statusvar,  
						'ean' 				=> $variant['EAN'], 
						'priceFrom' 		=> (int)$price, 
						'priceFor' 			=> (int)$promotional_price, 
						'skuFeatures'		=> $skuFeatures, 
						'skuImages' 		=> $this->formatImages($images),
						'amount'   			=> ($variant['qty'] <= 0) ? 0 : (int)$variant['qty'], 
						'additionalTime' 	=> (is_null($this->prd['prazo_operacional_extra'])) ? 0 : (int)$this->prd['prazo_operacional_extra'], 
					);
				}	
			}
		}
		$productFeatures[] = array(
				'name' 			=>  'Características Gerais',
				'value' 		=> $carGer,
				'featureType' 	=> 5,
			);
			
		if ($espTec != '') {
			$productFeatures[] = array(
				'name' 			=> 'Especificações Técnicas',
				'value' 		=> $espTec,
				'featureType' 	=> 6,
			);
		}
		
	
		$produto = array(
			'productId'     	=> $this->prd['id'],
			'name'				=> $this->prd['name'],
			'description'   	=> $this->prd['description'], 
			'productFeatures' 	=> $productFeatures,
			'productSkus'   	=> $productSkus,
			'sections'			=> $this->generateSections(), 
		);
		
		$resp_json = json_encode($produto, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
		if ($resp_json === false) {
			// a descrição está com algum problema . tento reduzir... 
			$produto['name'] = substr(strip_tags(htmlspecialchars($this->prd['name'], ENT_QUOTES, 'utf-8')," \t\n\r\0\x0B\xC2\xA0"),0,96);
			$produto['description'] = substr($description,0,3000);
			$resp_json = json_encode($produto, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
			if ($resp_json === false) {
				$msg = 'Erro ao fazer o json do produto '.$this->prd['id'].' '.print_r($produto,true).' json error = '.json_last_error_msg();
				var_dump($resp_json);
				echo $msg."\n";
				$this->log_data('batch',$log_name, $msg,'E');
				return false;;
			}
		}
		
		echo print_r($resp_json,true)."\n";

		return $resp_json;	
	}

	function generateSections() {
	
		$cat_Names = explode('/',$this->prd['categoryname']);
		
		$categoryId = json_decode($this->prd['category_id']);
		if (is_array($categoryId)) {
			$categoryId = $categoryId[0];
		}
		$brandId = json_decode($this->prd['brand_id']);
		if (is_array($brandId)) {
			$brand_id = $brandId[0];
		}

		$sections = array();
		$sections[] = array( 
				'sectionTypeId' 	=> (int)1,
				'sectionId'			=> (int)$categoryId,
				'sectionParentId' 	=> null,
				'value'				=> $cat_Names[0],
			);
		if (key_exists(1,$cat_Names)) {
			$sections[] = array( 
					'sectionTypeId' 	=> (int)2,
					'sectionId'			=> (int)$categoryId.'00001',
					'sectionParentId' 	=> (int)$categoryId,
					'value'				=> $cat_Names[1],
				);
		}
		$sections[] = array( 
				'sectionTypeId' 	=> (int)3,
				'sectionId'			=> (int)$brand_id,
				'sectionParentId' 	=> null,
				'value'				=> $this->prd['brandname'],
			);
		
		return $sections; 
	}
	
	
	function formatImages($images){
		
		$images_ret = array();
		$cnt = 1;
		foreach($images as $image) {
			$images_ret[] = array(  // rick perguntas: quantas imagens podemos mandar e se pode mandar a mesma imagem no small,medium, large image
              	'smallImage' 	=> $image,
              	'mediumImage' 	=> $image,
              	'largeImage' 	=> $image,
              	'order'			=> $cnt++,
			);
			if ($cnt > 6 ) { break;}
		}
		return $images_ret;
	}
    
    function updateVSLastPost($prd, $variant = null) 
	{
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;
			
		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];
		if ($prd['EAN'] == '') {
			if ($prd['is_kit'] == 1) {
				$ean ='IS_KIT'.$prd['id'];
			}
			else {
				$ean ='NO_EAN'.$prd['id']; 
			}
			if (!is_null($variant_num)) {
				$ean = $ean.'V'.$variant_num;
			}
		}
		$skulocal = $this->prd_to_integration['skubling']; 
		if (!is_null($variant_num)) {
			$skulocal = $skulocal.'-'.$variant_num; 
		}
		
    	$data = array(
    		'int_to' 					=> $this->int_to,
    		'seller_id' 				=> $this->seller_id,
    		'prd_id' 					=> $prd['id'],
    		'variant' 					=> $variant_num,
    		'company_id' 				=> $prd['company_id'],
    		'store_id' 					=> $prd['store_id'], 
    		'EAN' 						=> $ean,
    		'price' 					=> $prd['promotional_price'],
    		'list_price' 				=> $prd['price'], 
    		'qty' 						=> $prd['qty'],
    		'qty_total' 				=> $prd['qty_original'],
    		'sku' 						=> $prd['sku'],
    		'skulocal' 					=> $skulocal,
    		'skumkt' 					=> $this->prd_to_integration['skumkt'],     
    		'date_last_sent'			=> $this->dateLastInt,
    		'tipo_volume_codigo' 		=> $prd['tipovolumecodigo'], 
    		'width' 					=> $prd['largura'],
    		'height' 					=> $prd['altura'],
    		'length' 					=> $prd['profundidade'],
    		'gross_weight' 				=> $prd['peso_bruto'],
    		'crossdocking' 				=> (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'], 
    		'zipcode' 					=> preg_replace('/\D/', '', $this->store['zipcode']), 
    		'CNPJ' 						=> preg_replace('/\D/', '', $this->store['CNPJ']),
    		'freight_seller' 			=> $this->store['freight_seller'],
			'freight_seller_end_point' 	=> $this->store['freight_seller_end_point'],
			'freight_seller_type' 		=> $this->store['freight_seller_type'],
    	);

        $data = $this->formatFieldsUltEnvio($data);
		
		$savedUltEnvio =$this->model_vs_last_post->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela ml_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 
	}
	
	function hasShipCompany() {
		return true;

		$this->load->library('calculoFrete'); 
		
		// Se for logística propria, não precisa validar transportadora
        if ($this->store['freight_seller']) {
            return true;
        }
		
		$cat_id = json_decode ( $this->prd['category_id']);
		$sql = 'SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id ='.intval($cat_id[0]).')';
		$cmd = $this->db->query($sql);
		$lido = $cmd->row_array();
		$tipo_volume_codigo= is_null($lido) ? null : $lido['codigo'];		
					
		$prd_info = array (
			'peso_bruto' =>(float)$this->prd['peso_bruto'],
			'largura' =>(float)$this->prd['largura'],
			'altura' =>(float)$this->prd['altura'],
			'profundidade' =>(float)$this->prd['profundidade'],
			'tipo_volume_codigo' => $tipo_volume_codigo,
		);
		return ($this->calculofrete->verificaCorreios($prd_info) ||
				$this->calculofrete->verificaTipoVolume($prd_info,$this->store['addr_uf'],$this->store['addr_uf']) ||
				$this->calculofrete->verificaPorPeso($prd_info,$this->store['addr_uf'])) ; 
	}

	function removeVariantsPrdIntegration() {  // limpa prd_to_integrations das variants já que não usamos isso no hubvertem 
			
		if ($this->prd['has_variants']=='') {
			return;
		}
		$prds_to = $this->model_integrations->getPrdIntegrationByFieldsMulti($this->prd_to_integration['int_id'], $this->prd['id'], $this->store['id']);
		
		foreach($prds_to as $prd_to) {
			if (($prd_to['variant'] != 0) && (!is_null($prd_to['variant']))) {
				$this->model_integrations->removePrdToIntegration($prd_to['id']);
			}
		}
		
	}
	
	public function getBrandMarketplace($skumkt, $brandRequired = false)
	{
		// pego o brand do Marketplace 
		$this->prd['brandname'] = "";
		$brandId    = json_decode($this->prd['brand_id']);
		if ($brandId=='') {
			if ($brandRequired) {
				$msg= 'Produto sem Marca e é obrigatório para '.$this->int_to;
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
				$this->prd['brandname'] = "";
				return false;
			}
			else {
				return true;;
			}
			
		}
    	$brand      = $this->model_brands->getBrandData($brandId);
		if ($brand) {
			$this->prd['brandname'] = $brand['name'];
			return true;
		}
		else {
			if ($brandRequired) {
				$msg= 'Produto com Marca inexistente '.$this->int_to;
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
				return false;
			}
			else {
				return true;;
			}
		}	
	
	}

    public function getLastPost($prd_id, $int_to, int $variant = null)
    {
        $procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }

        return $this->model_vs_last_post->getData(null, $procura);
    }

}