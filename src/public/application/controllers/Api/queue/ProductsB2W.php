<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

class ProductsB2W extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal ;
	var $integration;
	var $statusB2W = 'enabled';	
	var $bling_ult_envio = null;

    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_blingultenvio');
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		$this->load->model('model_marketplace_prd_variants'); 
		$this->load->model('model_ml_ult_envio'); 	
		$this->load->model('model_settings'); 	
		$this->load->model('model_b2w_ult_envio'); 
		
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
    } 
	
	public function checkAndProcessProduct()
	{
		
		$this->getkeys();
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		// verifico se existia como ganhador do leilão 
		$this->bling_ult_envio = $this->model_blingultenvio->getDataByIntToPrdIdVariant($this->int_to,$this->prd['id']);
		if (!$this->bling_ult_envio) { // se não existia ou nunca foi ganhador do leilão
			$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
			$this->prd_to_integration['skubling']= $sku;
 		} else {
			$sku = $this->bling_ult_envio['skubling'];
 		}
		$skumkt = $sku; 
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para envio');
		
		// pego informações adicionais como preço, estoque e marca .
		if (!$this->prepareProduct($sku)) { return false;}
		
		// Monto o Array para enviar para o Mercado Livre
		$produto = $this->montaArray($sku, true, 0);
		if (!$produto) { return false;}
		
		echo 'Incluindo o produto '.$this->prd['id'].' '.$this->prd['name']."\n";

		$url = 'https://api.skyhub.com.br/products';
		$retorno = $this->skyHubHttp($url, 'POST', json_encode($produto), $this->prd['id'], $this->int_to, 'Novo produto');

		if ($this->responseCode != 201)  { // Deu um erro que não consigo tratar
			echo ' Erro URL: '. $url. "\n"; 
			echo ' httpcode: '.$this->responseCode."\n"; 
			echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
			echo ' ENVIADO : '.print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r($this->result,true).' ENVIADO:'.print_r($produto,true),'E');
			die;
			return false;
		}
		
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
				$prd['sku'] = $variant['sku'];
				$prd['qty'] = $variant['qty'];
				$prd['price'] = $variant['price'];
				$prd['qty_original'] = $variant['qty_original'];
				$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'];

                if ($variant['status'] != 1) {
                    $prd['qty'] = 0;
                    $prd['qty_original'] = 0;
                }

				$this->updateBlingUltEnvio($prd, $variant);
				$this->updateB2WUltEnvio($prd, $variant);
			}
		}else{
			$this->updateBlingUltEnvio($this->prd, null);
			$this->updateB2WUltEnvio($this->prd, null);
		}
		return true;	
		
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Update'."\n";
		
		// verifico se existia como ganhador do leilão 
		$this->bling_ult_envio = $this->model_blingultenvio->getDataByIntToPrdIdVariant($this->int_to,$this->prd['id']);

		if (!$this->bling_ult_envio) { // se nunca foi ganhador do leilão (não tinha variação)
			if (!$this->variants) {
				echo " Nunca foi ganhador do Leilão, então pulo para inserir\n";
				return $this->insertProduct(); 
			}
			$this->bling_ult_envio = $this->model_blingultenvio->getDataByIntToPrdIdVariant($this->int_to,$this->prd['id'],0);
			if (!$this->bling_ult_envio) {
				echo " Nunca foi ganhador do Leilão, então pulo para inserir\n";
				return $this->insertProduct(); 
			}
			$skumkt = $this->bling_ult_envio['skumkt'];
			$sku = $this->bling_ult_envio['skumkt'];
 		}
		else {
			$skumkt = $this->bling_ult_envio['skumkt'];
			$sku = $this->bling_ult_envio['skubling']; 
		}
		
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,"Preparação para envio");
		
		// pego informações adicionais como preço, estoque e marca .
		if (!$this->prepareProduct($sku)) { return false;}
		
		// atualiza preço e estoque primeiro antes de alterar o resto do produto.
		if (!$this->changeB2WPriceQty($skumkt)) {die; return false;}
		
		// Monto o Array para enviar para o Mercado Livre
		$produto = $this->montaArray($sku, true, 0);
		if (!$produto) { return false;}
		
		echo 'Alterando o produto '.$this->prd['id'].' '.$this->prd['name']."\n";
		$url = 'https://api.skyhub.com.br/products/'.$skumkt;

		$retorno = $this->skyHubHttp($url, 'PUT', json_encode($produto), $this->prd['id'], $this->int_to, 'Alterando produto');

		if ($this->responseCode != 204)  { // Deu um erro que não consigo tratar
			echo ' Erro URL: '. $url. "\n"; 
			echo ' httpcode: '.$this->responseCode."\n"; 
			echo ' RESPOSTA: '.print_r($this->result,true)." \n"; 
			echo ' ENVIADO : '.print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r($this->result,true).' ENVIADO:'.print_r($produto,true),'E');
			return false;
		}
		
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
				$prd['sku'] = $variant['sku'];
				$prd['qty'] = $variant['qty'];
				$prd['price'] = $variant['price'];
				$prd['qty_original'] = $variant['qty_original'];
				$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'];

                if ($variant['status'] != 1) {
                    $prd['qty'] = 0;
                    $prd['qty_original'] = 0;
                }

				$this->updateBlingUltEnvio($prd, $variant);
				$this->updateB2WUltEnvio($prd, $variant);
			}
		}else{
			$this->updateBlingUltEnvio($this->prd, null);
			$this->updateB2WUltEnvio($this->prd, null);
		}
		return true;
		
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		$disable = false; // Não dá mais o disable na skyhub 
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
		if ($disable) {
			$this->statusB2W = 'disabled';	
		}
		$this->updateProduct();
		$this->model_integrations->updatePrdToIntegration(
			array(
				'status_int' 	=> $status_int, 
				'date_last_int' => $this->dateLastInt
			),$this->prd_to_integration['id']);
			
		if ($disable) {
			$this->disableB2W();
		}
	}

	function getkeys() {
		//pega os dados da integração. 
		$this->getIntegration(); 
		$this->auth_data = json_decode($this->integration_main['auth_data']);
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
	
	//public function getCategoryMarketplace($skumkt,$int_to = '')
	public function getCategoryMarketplace($skumkt, $int_to = '', $mandatory_category = true) 
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
		$this->prd['tipovolumecodigo'] = $tipo_volume['codigo']; 	
		
		return $categoryId;
	}
	
	function prepareProduct($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Preparando produto'."\n";
		
		// busco a categoria 
		$this->prd['categoria_b2w'] = $this->getCategoryMarketplace($sku);
		if (!$this->prd['categoria_b2w']) {
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
		// se é a conectaLá não usa EAN para o produto
		if ($this->int_to=='B2W') {
			$this->prd['EAN'] = null;
		}
		// se tiver Variação,  acerto o estoque de cada variação
    	if ($this->prd['has_variants']!='') {
			
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
							
				// se é a conectaLá não usa EAN para o produto
				if ($this->int_to=='B2W') { 
					$this->variants[$key]['EAN'] = null;
				}
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
		if (!$this->getBrandMarketplace($sku,false)) {return false;}
		
		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 2 ) { $this->prd['prazo_operacional_extra'] = 2; }
		
		return true;
	}	
	
	private function getProductImages($folder_ori, $path, $vardir = '', $variacao = false )
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$folder = $folder_ori;
		if ($vardir !== '') {
			$folder .= $vardir;
		}
		elseif ($variacao) {
			return array(); // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}
		echo 'Lendo imagens em assets/images/'.$path.'/'.$folder."\n";
		if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder)) {
			return array();
		}
		if ($folder == '') {
			return array();
		}
        $images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
        
        if (!$images) {
            return array();
        }
        if (count($images) <= 2) { // não achei nenhuma imagem
			if ($variacao) { // Mas é uma variação, retorna o array vazio
				return  array();
			}
            return array();
        }
		$numft= 0;
		$imagesData = array();
		foreach($images as $foto) {
			if (($foto!='.') && ($foto!='..') && ($foto!='')) {
				if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder.'/'.$foto)) {
					$imagesData[] = base_url('assets/images/'.$path.'/' . $folder.'/'. $foto);
					$numft++;
				}
			}
		}
        return $imagesData;
    }

	function montaArray($sku, $novo_produto = true, $vendas = 0) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$description = substr(htmlspecialchars(strip_tags(str_replace('<br>'," \n",$this->prd['description'])), ENT_QUOTES, "utf-8"),0,3800);
		$description = str_replace("&amp;amp;"," ",$description);
		$description = str_replace("&amp;"," ",$description);
		$description = str_replace("&nbsp;"," ",$description);
		if (($description=='') || (trim(strip_tags($this->prd['description'])," \t\n\r\0\x0B\xC2\xA0")) == ''){
			$description= substr(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8"),0,98);
		}
		$imagem_pai = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		$produto = array(
			'sku' 			=> $sku,
			'name'			=> substr(strip_tags(htmlspecialchars($this->prd['name'], ENT_QUOTES, 'utf-8')," \t\n\r\0\x0B\xC2\xA0"),0,100),
			'description' 	=> $description,
			'status' 		=> $this->statusB2W,
			'price' 		=> (float)$this->prd['price'], 
			'promotional_price' => (float)$this->prd['promotional_price'],
			'weight'  		=> (float)$this->prd['peso_bruto'],
			'height'		=> (float)($this->prd['altura'] < 2) ? 2 : $this->prd['altura'],
			'width'			=> (float)($this->prd['largura'] < 11) ? 11 : $this->prd['largura'],
			'length'		=> (float)($this->prd['profundidade'] < 16) ? 16 : $this->prd['profundidade'],
			'qty'	    	=> (int)$this->prd['qty'],
			'brand'			=> substr($this->prd['brandname'],0,29), // limite da B2w
			'ean'			=> $this->prd['EAN'],
			'nbm'			=> $this->prd['NCM'],
			'images'		=> $imagem_pai,
			'categories' 	=> Array(array(
				'code'			=> $this->prd['categoria_b2w'],
				'name'			=> $this->prd['categoryname'],
			)),
			'specifications' => array(
				array(
					'key' => 'CrossDocking',
					'value' => $this->prd['prazo_operacional_extra'],
				),
				array(
					'key' => 'store_stock_cross_docking',
					'value' => $this->prd['prazo_operacional_extra'],
				),
				array(
					'key' => 'Garantia',
					'value' => $this->prd['garantia'],
				),
			)
		);
		if (!is_null($this->prd['actual_width']) && trim($this->prd['actual_width']!=='')) {
			$produto['specifications'][] = array (
				'key' => 'Largura desembalado', 'value' => $this->prd['actual_width'],
			);
		}
		if (!is_null($this->prd['actual_height']) && trim($this->prd['actual_height']!=='')) {
			$produto['specifications'][] = array (
				'key' => 'Altura desembalado', 'value' => $this->prd['actual_height'],
			);
		}
		if (!is_null($this->prd['actual_depth']) && trim($this->prd['actual_depth']!=='')) {
			$produto['specifications'][] = array (
				'key' => 'Profundidade desembalado', 'value' => $this->prd['actual_depth'],
			);
		}
		if (!is_null($this->prd['peso_liquido']) && trim($this->prd['peso_liquido']!=='')) {
			$produto['specifications'][] = array (
				'key' => 'Peso líquido', 'value' => $this->prd['peso_liquido'],
			);
		}
		$attibutes_custom = $this->model_products->getAttributesCustomProduct($this->prd['id']);
		foreach ($attibutes_custom as $attibute_custom) {
			$produto['specifications'][] = array (
				'key' => $attibute_custom['name_attr'], 'value' => $attibute_custom['value_attr'],
			);
		}
		// TRATAR VARIANTS		
		if ($this->prd['has_variants']!='') {
            $tipos = explode(';',$this->prd['has_variants']);
			$variation_attributes = array();
			$variations = array();
			foreach($this->variants as $key => $variant) {
			  	if (isset($variant['sku'])) { 
					
					$values = explode(';',$variant['name']);
					$specficiation = array();
					foreach ($tipos as $z => $campo) {
						$specficiation[] = array(
							'key' => $campo, 'value' => $values[$z]
						);
						if (!in_array($campo, $variation_attributes)) {
							$variation_attributes[] = $campo;
						}
					}
					$specficiation[] = array(
						'key' => 'store_stock_cross_docking', 'value' => $this->prd['prazo_operacional_extra'],
					);
					$specficiation[] = array(
						'key' => 'price', 'value' => (float)$variant['price'],
					);
					$specficiation[] = array(
						'key' => 'promotional_price', 'value' => (float)$variant['promotional_price'],
					);
					$specficiation[] = array(
						'key' => 'Garantia','value' => $this->prd['garantia'],
					);
					$vardir = '';
					$images_var = array();
					if ($this->pathImage == 'product_image') {
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
					$variacao = array(
						'sku' => $sku.'-'.$variant['variant'],
						'qty' => ceil($variant['qty']),
						'ean' => $variant['EAN'],
						'specifications' => $specficiation, 
						'images' => $images,
                    );

                    if ($variant['status'] != 1) {
                        $variacao['qty'] = 0;
                    }

					$variations[] = $variacao;
				 }	
			}
			$produto['variation_attributes'] =$variation_attributes;
			$produto['variations'] = $variations;
		}

		$resp_json = json_encode($produto);

		if (($resp_json === false)  || (json_last_error() == JSON_ERROR_UTF8)){
			// a descrição está com algum problema . tento reduzir... 
			$semacento = strtr(utf8_decode($this->prd['name']),
                 	utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
			$produto['name'] = substr(strip_tags($semacento," \t\n\r\0\x0B\xC2\xA0"),0,100);
			$produto['description'] = substr($description,0,3000);
			$resp_json = json_encode($produto);
			if (($resp_json === false)  || (json_last_error() == JSON_ERROR_UTF8)) {
				$msg = 'Erro ao fazer o json do produto '.$this->prd['id'].' '.print_r($produto,true).' json error = '.json_last_error_msg();
				var_dump($resp_json); echo $msg."\n";
				$this->log_data('batch',$log_name, $msg,'E');
				return false;
			}
		}
		echo print_r($resp_json,true)."\n";
		return array('product' => $produto);	
	}

	protected function skyHubHttp($url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null )
    {

        $this->header = [
            'content-type: application/json',
            'accept: application/json',
            'x-accountmanager-key: YdluFpAdGi', 
			'x-api-key: '.$this->auth_data->apikey,
			'x-user-email: '.$this->auth_data->email
        ];

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
		
		if ($this->responseCode == 429) {
		    $this->log('Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->skyHubHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
		if ($this->responseCode == 504) {
		    $this->log('Deu Timeout httpcode=504. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->skyHubHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
        if ($this->responseCode == 503) {
		    $this->log('Site com problemas httpcode=503. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->skyHubHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
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
		
    }
    
    function updateB2WUltEnvio($prd, $variant = null) 
	{
			
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
    		'int_to' => $this->int_to,
    		'prd_id' => $prd['id'],
    		'variant' => $variant_num,
    		'company_id' => $prd['company_id'],
    		'store_id' => $prd['store_id'], 
    		'EAN' => $ean,
    		'price' => $prd['promotional_price'],
    		'list_price' => $this->prd['price'],
    		'qty' => $prd['qty'],
    		'qty_total' => $prd['qty_original'],
    		'sku' => $prd['sku'],
    		'skulocal' => $skulocal,
    		'skumkt' => $this->prd_to_integration['skumkt'],     
    		'date_last_sent' => $this->dateLastInt,
    		'tipo_volume_codigo' => $prd['tipovolumecodigo'], 
    		'width' => $prd['largura'],
    		'height' => $prd['altura'],
    		'length' => $prd['profundidade'],
    		'gross_weight' => $prd['peso_bruto'],
    		'crossdocking' => (is_null($prd['prazo_operacional_extra']) || ($prd['prazo_operacional_extra']<2)) ? 2 : $prd['prazo_operacional_extra'], 
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
    	);

        $data = $this->formatFieldsUltEnvio($data);
		
		$savedUltEnvio =$this->model_b2w_ult_envio->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela ml_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 
	}

	function updateBlingUltEnvio($prd, $variant = null) 
	{
			
		// EAN para colocar no Bling_ult_envio. Não é importante ter EAN, então crio um EAN único para cada produto
		echo "Update bling_ult_envio ".date("H:i:s")."\n"; 
		if ($prd['is_kit'] == 1) {
			$ean ='IS_KIT'.$prd['id'];
		}
		else {
			$ean ='NO_EAN'.$prd['id']; 
		}
		$skubling = $this->prd_to_integration['skubling']; 
		if (!is_null($variant)) {
			$ean = $ean.'V'.$variant['variant'];
			$skubling = $skubling.'-'.$variant['variant']; 
		}
    	$data = array(
    		'int_to' => $this->int_to,
    		'company_id' => $prd['company_id'],
    		'EAN' => $ean,
    		'prd_id' => $prd['id'],
    		'price' => $prd['promotional_price'],
    		'list_price' => $this->prd['price'],
    		'qty' => $prd['qty_original'],
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
    		'crossdocking' => (is_null($prd['prazo_operacional_extra']) || ($prd['prazo_operacional_extra']<2)) ? 2 : $prd['prazo_operacional_extra'], 
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
			'variant' => (is_null($variant)) ? $variant : $variant['variant'],
    	);
		
		if ($this->bling_ult_envio) {
			if (!is_null($variant)) { // apago o registro antigo do Leilão sem variant
				$this->model_blingultenvio->remove($this->bling_ult_envio['id']);
			}
			else{
				if ($this->bling_ult_envio['EAN'] != $ean) {  // EAN antigo está diferente do novo EAN, então é registro antigo de Leilão. devo remover
					$this->model_blingultenvio->remove($this->bling_ult_envio['id']);
				}
			} 
			
		}

        $data = $this->formatFieldsUltEnvio($data);
		
		$savedUltEnvio= $this->model_blingultenvio->createIfNotExist($ean, $this->int_to, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela bling_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			echo "Update terminado bling_ult_envio ".date("H:i:s")."\n";
			die;
        } 	
		echo "Update terminado bling_ult_envio ".date("H:i:s")."\n";
	}

	function changeB2WPriceQty($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if ($this->prd['has_variants']!='') {
            $tipos = explode(';',$this->prd['has_variants']);
            $variation_attributes = array();
			$variations = array();
			foreach($this->variants as $variant) {
				if (isset($variant['sku'])) {
					$sku = $skumkt.'-'.$variant['variant'];
					echo 'Produto:'.$this->prd['id'].' Variação: '.$variant['variant'].' Sku: '.$sku.' estoque:'.$variant['qty'].' De: '.(float)$variant['price'].' Por: '.(float)$variant['promotional_price']."\n";
					
					$product = Array (
						'variation' => array(
						    'qty' => (int)$variant['qty']
						),
						'specifications' => array(
							array (
								'key' => 'price',
								'value'=> (float)$variant['price']
							), 
							array (
								'key' => 'promotional_price',
								'value'=> (float)$variant['promotional_price']
							),
							
						), 
					);
					$url = 'https://api.skyhub.com.br/variations/'.$sku;

					$json_data = json_encode($product);
					$retorno = $this->skyHubHttp($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque Variacao '.$variant['variant']);
					if ($this->responseCode == 404)  {  // created
						echo "Sumiu uma variação da B2W. Removendo a prd_to_integration deste produto\n";
						$this->model_integrations->updatePrdToIntegration(
							array (
								'skubling' 		=> null,
								'skumkt' 		=> null,
								'date_last_int' => $this->dateLastInt
							), $this->prd_to_integration['id']); 
						die; // morre e deixa na fila, da proxima vez irá cadastrar. 
					}
					if ($this->responseCode != 204)  {  // created
						echo 'Erro url:'.$url.' httpcode='.$this->responseCode .' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO ao alterar estoque variação '.$sku.' url:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),'E');
						return false;
					}
				}
			}	
		}
		else {

			echo 'Produto:'.$this->prd['id'].' Sku:'.$skumkt.' estoque:'.$this->prd['qty'].' De: '.(float)$this->prd['price'].' Por: '.(float)$this->prd['promotional_price']."\n";
					
			$product = Array (
				'product' => array(
    				'price' => (float)$this->prd['price'],
   					'promotional_price' => (float)$this->prd['promotional_price'], 
				    'qty' => (int)$this->prd['qty']
				) 
			);
			$url = 'https://api.skyhub.com.br/products/'.$skumkt;
			
			$json_data = json_encode($product);
			$retorno = $this->skyHubHttp($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque');
			if ($this->responseCode !='204')  {  // created
				echo 'Erro url:'.$url.'. httpcode='.$this->responseCode .' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO ao alterar estoque '.$skumkt.' url:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r($this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),'E');
				return false;
			}
		}	
		return true;
	} 

	function disableB2W() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$skumkt = $this->prd_to_integration['skumkt'];
		
		Echo 'Colocando em Disable produto '.$this->prd['id'].' SKU '.$skumkt."\n";
		$disable = Array (
			'product' => array(
					'status' => 'disabled'
				)
			);
								
		$json_data = json_encode($disable);
		
		$url = 'https://api.skyhub.com.br/products/'.$skumkt;
		$retorno = $this->skyHubHttp($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Disable');
		if ($this->responseCode !='204')  {  // created
			echo 'Erro url:'.$url.'. httpcode='.$this->responseCode .' RESPOSTA: '.print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true)."\n"; 
			$this->log_data('batch',$log_name, 'ERRO ao alterar estoque '.$skumkt.' url:'.$url.' - httpcode: '.$this->responseCode.' RESPOSTA: '.print_r($this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),'E');
			return false;
		}
	}
	
	function hasShipCompany_old() {
		// remover depois - 15/06/2022
		$this->load->library('calculoFrete'); 

		// Se for logística propria, não precisa validar transportadora
        if ($this->store['freight_seller']) {
            return true;
        }
		
		$cat_id = json_decode ( $this->prd['category_id']);
		$sql = 'SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id ='.intval($cat_id[0]).')';
		$cmd = $this->db->query($sql);
		$lido = $cmd->row_array();
		$tipo_volume_codigo= $lido['codigo'];		
					
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

	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->model_blingultenvio->getData(null, $procura);
	}
}