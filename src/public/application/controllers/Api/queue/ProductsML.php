<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsConectala.php";

require APPPATH . "controllers/BatchC/MercadoLivre/Meli.php";

class ProductsML extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal ;
	var $integration;
	var $tipo_anuncio = "gold_pro";
	var $int_to_hub = "H_ML";
	var $official_store_id = null;
    var $reserve_to_b2W = 5;
	var $shipping_modes = array();

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
		$this->load->model('model_products_category_mkt'); 	
		
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
		if (($this->int_to == 'MLC') || ($this->int_to == 'ML')) {
			echo "loja da conectala está desativada\n";
			return;
		}
		parent::checkAndProcessProduct();

	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		$this->getkeys();
		
		$skumkt = $this->prd_to_integration['skumkt'];
		$sku    = $this->prd_to_integration['skubling'];
		
		if (is_null($sku)) {
			$sku = "P".$this->prd['id']."S".$this->prd['store_id'].$this->int_to;
			$this->model_integrations->updatePrdToIntegration(array('skubling'=> $sku), $this->prd_to_integration['id']);
			$this->prd_to_integration['skubling']= $sku;
 		}
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
		
		// pego informações adicionais coom preço, categoria e outras coisas mais.
		if ($this->prepareProduct($sku)==false) { return false;};
		
		// Monto o Array para enviar para o Mercado Livre
		$produto = $this->montaArray($sku, true, 0);
		if ($produto==false) { return false;};

		
		echo "Incluindo o produto ".$this->prd['id']." ".$this->prd['name']."\n";
		$url = '/items';
		$retorno= $this->meliHttp('post', $url, null, $produto,$this->prd['id'], $this->int_to, 'Novo produto');

		if ($this->responseCode == 400) { // Deu um erro que consigo tratar
			$this->writeMeliError($sku);
			return false;
		}
		if ($this->responseCode != 201)  { // Deu um erro que não consigo tratar
			echo " Erro URL: ". $url. " httpcode=".$this->responseCode."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA ".$this->int_to.": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($produto,true),"E");
			return false;
		}
		
		$this->processMLResponse();
		
		// Com a resposta que veio acerto os registros 
		$respostaML = json_decode(json_encode($retorno['body']));
		$skumkt =$respostaML->id;
		echo " Codigo do Mercado Livre - ".$skumkt."\n";
		echo " Link ".$respostaML->permalink."\n";
		echo " Qualidade ".$respostaML->health."\n";
		$this->model_integrations->updatePrdToIntegration(
			array (
				'skumkt' 		=> $respostaML->id,
				'ad_link' 		=> $respostaML->permalink,
				'quality' 		=> $respostaML->health,
				'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
				'date_last_int' => $this->dateLastInt
			), $this->prd_to_integration['id']);
		$this->prd_to_integration['skumkt'] = $respostaML->id; 
		if ($this->prd['has_variants']!="") {
			$this->getMeliVariations($respostaML);
			foreach($this->variants as $variant) {
				$prd = $this->prd;
				$prd['sku'] = $variant['sku'];
				$prd['qty'] = $variant['qty'];
				$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'];

                if ($variant['status'] != 1) {
                    $prd['qty'] = 0;
                }

				$this->updateBlingUltEnvio($prd, $variant);
				$this->updateMLUltEnvio($prd, $variant);
			}
		}else{
			$this->updateBlingUltEnvio($this->prd, null);
			$this->updateMLUltEnvio($this->prd, null);
		}
		return true;		

	}

	function prepareProduct($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// altero o Int_to para enganar o getCategoryMarketplace 
		echo "Preparando produto\n";
		$int_to_sav=$this->int_to;
		$this->int_to = $this->int_to_principal;
		$this->prd['categoria_ML'] =  $this->getCategoryMarketplace($sku, $this->int_to_principal);
		$this->int_to = $int_to_sav;
    	
		if ($this->prd['categoria_ML']==false) {
			return false;
		}
		
		// leio o estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];
		if ((int)$this->prd['qty'] < $this->reserve_to_b2W) { // Mando só para a B2W se a quantidade for menor que 5. 
			$this->prd['qty']  = 0;
		}
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arrendoda para cima 
		
		// se tiver Variação verificar se a categoria aceita a variação e acerto o estoque de cada variação
    	if ($this->prd['has_variants']!="") {
    		$variações = explode(";",$this->prd['has_variants']);

			// vejo se tudo bem com as variações
			foreach ($variações as $variacao) {
				$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoria_ML'],ucfirst(strtolower($variacao)),'ML');
				if (!$atributosCat) {
					$catMl =  $this->model_categorias_marketplaces->getAllCategoriesById($this->int_to_principal,$this->prd['categoria_ML']);
					$msg= 'Categoria '.$this->prd['categoria_ML'].'-'.$catMl['nome'].' não tem variação de '.$variacao;
					//$this->errorTransformation($this->prd['id'],$skumkt,$msg,"Preparação para Envio", $row['id']);
					//return false;
				}elseif (!$atributosCat['variacao']){
					$catMl =  $this->model_categorias_marketplaces->getAllCategoriesById($this->int_to_principal,$this->prd['categoria_ML']);
					$msg= 'Categoria '.$this->prd['categoria_ML'].'-'.$catMl['nome'].' não aceita variação de '.$variacao;
					echo $msg."\n";
					$this->errorTransformation($this->prd['id'],$sku ,$msg, "Preparação para Envio", $this->prd_to_integration['id']);
					return false;
				}
			}
			
			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
				$this->variants[$key]['qty_original'] =$variant['qty'];
				if  ((int)$this->variants[$key]['qty'] < $this->reserve_to_b2W) { // Mando só para a B2W se a quantidade for menor que 5. 
					$this->variants[$key]['qty'] = 0;
				}
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arrendoda para cima

                if ($variant['status'] != 1) {
                    $this->variants[$key]['qty'] = 0;
                }
			}
		}
		
		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);

		//leio a brand
		if ($this->getBrandMarketplace($sku,false) == false) return false;
		
		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1 ) { $this->prd['prazo_operacional_extra'] = 1; }
		
		return true;
	}	
	
	function checkMLProductStatus($skumkt)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$url = '/items/'.$skumkt;
		$retorno = $this->meliHttp('get', $url);

		if (!($this->responseCode=="200") )  {  
			$erro = "Erro no get url: ".$url." httpcode=".$this->responseCode." RESPOSTA: ".print_r($this->result,true)." \n"; 
			$this->log_data('batch',$log_name, $erro,"E");
			return false;
		}
		$product = json_decode(json_encode($this->result));
		echo "Oferta com status ".$product->status." no marketplace para ".$skumkt."\n";

		if ($product->status == 'under_review') {
			echo "Não vou alterar preço e estoque mas tentar enviar o produto novamente \n";
			return $product;
		} elseif (($product->status != 'active')  && ($product->status != 'paused')) {
			$this->errorTransformation($this->prd['id'],$skubling, "Oferta com status ".$product->status." no marketplace", "Preparação para Envio");
			if ($status == 'closed') { // mercado livre detonou nosso produto.... 
				// rejeitado pelo Mercado Livre 
				$this->model_integrations->updatePrdToIntegration(array('approved'=> 4), $this->prd_to_integration['id']);
			}
		    return false;
		} 
		return $product; 
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Update"."\n";
		$this->getkeys();
		
		$skumkt = $this->prd_to_integration['skumkt'];
		$sku    = $this->prd_to_integration['skubling'];
		
		$vendas=0;
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
		
		// verifico como o produto está no ML e aproveito e pego as vendas do mesmo
		$productML = $this->checkMLProductStatus($skumkt);
		if ($productML===false) { return false;}
		
		// pego informações adicionais como preço, categoria e outras coisas mais.
		if ($this->prepareProduct($sku)==false) { return false;};
		
		// atualiza preço e estoque primeiro antes de alterar o resto do produto.
		if ($this->changeMLPriceQty($productML->status)==false) {return false;}
		
		// Monto o Array para enviar para o Mercado Livre
		$produto = $this->montaArray($sku, false, $productML->sold_quantity);
		if ($produto==false) {return false;}
		
		echo "Alterando o produto ".$this->prd['id']." ".$this->prd['name']."\n";
		$url = '/items/'.$this->prd_to_integration['skumkt'];
		$retorno= $this->meliHttp('put', $url, null, $produto,$this->prd['id'], $this->int_to, 'Alterando produto');

		if ($this->responseCode == 400) { // Deu um erro que consigo tratar
			$this->writeMeliError($sku);
			return false;
		}
		if ($this->responseCode != 200)  { // Deu um erro que não consigo tratar
			echo " Erro URL: ". $url. " httpcode=".$this->responseCode."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA ".$this->int_to.": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($produto,true),"E");
			return false;
		}
		
		// processo a resposta do ML
		$this->processMLResponse();
		
		// Descrição tem que ser alterada em separado
		// Agora tento colocar a descrição com acentos.
		$description = array("plain_text" => substr(htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",str_replace("<br>","\n",$this->prd['description'])))), ENT_QUOTES, "utf-8"),0,3800));
		$url = '/items/'.$this->prd_to_integration['skumkt'].'/description';
		$params = array('api_version' => 2);
		$retorno= $this->meliHttp('put', $url, $params, $description, $this->prd['id'], $this->int_to, 'Alterando descrição');
		if ($this->responseCode == 400) {
			// não funcionou, removo os acentos
			$tot_desc = 3800;
			While (true) {
				echo "reduzindo a descrição até passar \n";
				$tot_desc--;
				$semacento = strtr(utf8_decode($this->prd['description']),
                 	utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
				$descricao = preg_replace("/[^a-zA-Z0-9] /", " ", $semacento);
				$descricao = substr(htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",str_replace("<br>","\n",$descricao)))), ENT_QUOTES, "utf-8"),0,$tot_desc);
       			$semacento = strtr(utf8_decode($descricao),
	                 utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
	                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
			
	            $description =  array("plain_text" =>utf8_encode($semacento));
			    $url = '/items/'.$skumkt.'/description';
				$params = array('api_version' => 2);
				$retorno= $this->meliHttp('put', $url, $params, $description, $this->prd['id'], $this->int_to, 'Alterando descrição');               
				
				if ($this->responseCode != 400) {
					break;
				}	
				if ($tot_desc< 3780) {
					break;
				}
			}
			
		}
		if ($this->responseCode != 200) {
			echo " Erro URL: ". $url. " httpcode=".$this->responseCode."\n"; 
			echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
			echo " Dados enviados: ".print_r($description,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true).' DADOS ENVIADOS:'.print_r($description,true),"E");
			return false;
		}	

		return true;

	}

	function processMLResponse()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// Com a resposta que veio acerto os registros 
		$respostaML = json_decode(json_encode($this->result));
		$skumkt =$respostaML->id;
		echo " Codigo do Mercado Livre - ".$skumkt."\n";
		echo " Link ".$respostaML->permalink."\n";
		echo " Qualidade ".$respostaML->health."\n";
		$this->model_integrations->updatePrdToIntegration(
			array (
				'skumkt' 		=> $respostaML->id,
				'ad_link' 		=> $respostaML->permalink,
				'quality' 		=> $respostaML->health,
				'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
				'date_last_int' => $this->dateLastInt
			), $this->prd_to_integration['id']);
		$this->prd_to_integration['skumkt'] = $respostaML->id; 
		if ($this->prd['has_variants']!="") {
			$this->getMeliVariations($respostaML);
			foreach($this->variants as $variant) {
				$prd = $this->prd;
				$prd['sku'] = $variant['sku'];
				$prd['qty'] = $variant['qty'];
				$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;		
				$this->updateBlingUltEnvio($prd, $variant);
				$this->updateMLUltEnvio($prd, $variant);
			}
		}else{
			$this->updateBlingUltEnvio($this->prd, null);
			$this->updateMLUltEnvio($this->prd, null);
		}
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";

		$this->prd['qty'] = 0; // zero a quantidade do produto
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}
			foreach($this->variants as $key => $variant) {
				$this->variants[$key]['qty'] = 0;  // zero a quantidade da variant tb
			}
		}
		$this->updateProduct();
		$this->model_integrations->updatePrdToIntegration(
			array(
				'status_int' 	=> $status_int, 
				'date_last_int' => $this->dateLastInt
			),$this->prd_to_integration['id']);
			
		if ($disable) {
			$this->pauseML();
		}
		
	}
	
	public function getCategoryMarketplace($skumkt, $int_to = '', $mandatory_category = true) {
	//public function getCategoryMarketplace($skumkt, $int_to = '') {
		$return = parent::getCategoryMarketplace($skumkt, $int_to, $mandatory_category);
		
		$enrichment = $this->model_products_category_mkt->getCategoryEnriched($int_to, $this->prd['id']);
		if (!is_null($enrichment)) {
			echo 'categoria do '.$int_to.' Enriched '.$enrichment['category_mkt_id']."\n";
			return $enrichment['category_mkt_id'];
		}
		
		return $return;
	}

	// Mercado Livre eu tenho q mandar todas as imagens no produto principal E de todas as variações no produto principal
	private function getProductImages($folder_ori, $path, $vardir = '', $variacao = false, $variant = null )
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// primeiro pego as imagens do Pai 
		$folder = $folder_ori;
        
        echo 'Lendo imagens em assets/images/'.$path.'/'.$folder."\n";
		if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder)) {
			return false;
		}
		if ($folder == '') {
			return false;
		}
		$images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
        if (!$images) {
            return null;
        }
        if (count($images) <= 2) { // não achei nenhuma imagem
            return false;
        }
		$numft= 0;
		$imagesData = array();
		$imagesVar = array();
		$imagesPai = array();
		foreach($images as $foto) {
			if (($foto!=".") && ($foto!="..") && ($foto!="")) {
				if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder.'/'.$foto)) {
					$imagesData[] = array(
					 	'source'  => base_url('assets/images/'.$path.'/' . $folder.'/'. $foto),
					);
					if ($variacao){
						$imagesPai[]= base_url('assets/images/'.$path.'/' . $folder.'/'. $foto);
					}
					$numft++;
				}
			}
		}
		if ($this->prd['has_variants']!="") { // tem que pegar as imagens das variações tb se houve 
			if ($path == 'catalog_product_image') {  // se é produto de catálogo a rotina é um pouco diferente 
				$varcats = $this->model_products_catalog->getProductCatalogVariants($this->prd['product_catalog_id']); 
			}
			else {
				$varcats = $this->variants;
			}
			foreach($varcats as $varcat) {
				if ($path == 'catalog_product_image') {
					$folder = $varcat['image'];
				} 
				else {
					$folder = $folder_ori."/".$varcat['image'];
					$varcat['variant_id'] = $varcat['variant'];
				}
				
        		$images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
				foreach($images as $foto) {
					if (($foto!=".") && ($foto!="..") && ($foto!="")) {
						if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder.'/'.$foto)) {
							
							$numft++;
							if (($variacao) && ($varcat['variant_id'] == $variant)) {
								$imagesVar[] =  base_url('assets/images/'.$path.'/' . $folder.'/'. $foto);
							}
							else {
								$imagesData[] = array(  
							 		'source'  => base_url('assets/images/'.$path.'/' . $folder.'/'. $foto),
								);
							}
						}
					}
				}
			}
			if ($variacao) { 
				return array_merge($imagesVar,$imagesPai);  // se for variação, retorna só o array daquela variação com as imagens do Pai. 
			}
		}
		
        return $imagesData;
    }

	function getkeys() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		$this->getIntegration();
		
		$this->integration = $this->integration_store;
		if ($this->integration_store['int_type'] == 'BLING') {
			$this->integration = $this->integration_main;
		}
		$this->auth_data = json_decode($this->integration['auth_data']);
		//echo "client_id = ".$this->auth_data->client_id."\n";
		//echo "client_secret = ".$this->auth_data->client_secret."\n";
		//echo "access_token = ".$this->auth_data->access_token."\n";
		//echo "refresh_token = ".$this->auth_data->refresh_token."\n";

		$meli = new Meli($this->auth_data->client_id,$this->auth_data->client_secret,$this->auth_data->access_token,$this->auth_data->refresh_token);
		//echo " renovar em ".date('d/m/Y H:i:s',$this->getDateRefresh()).' hora atual = '.date('d/m/Y H:i:s'). "\n"; 
		if ($this->auth_data->date_refresh+1 < time()) {	
			$user = $meli->refreshAccessToken();
			var_dump($user);
			if ($user["httpCode"] == 400) {
				$redirectUrl = base_url('LoginML');
				if (strpos($redirectUrl,"teste.conectala.com" ) > 0)  {
					$redirectUrl = "https://www.mercadolivre.com.br";
				}
				
				$user = $meli->authorize($this->auth_data->refresh_token, $redirectUrl);
				var_dump($user);
				if ($user["httpCode"] == 400) {
					$redirectUrl = $meli->getAuthUrl($redirectUrl, Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
					var_dump($redirectUrl);
					//$retorno = $this->getPage($redirectUrl);
					
					//var_dump($retorno);
					die;
				}
			}
			$this->auth_data->access_token = $user['body']->access_token;
			$this->auth_data->date_refresh = $user['body']->expires_in+time();
			$this->auth_data->refresh_token = $user['body']->refresh_token;
			$api_keys = json_decode($this->integration_main['auth_data'],true);
			$api_keys['client_id'] = $this->auth_data->client_id;
			$api_keys['client_secret'] = $this->auth_data->client_secret;
			$api_keys['access_token'] = $this->auth_data->access_token;
			$api_keys['refresh_token'] = $this->auth_data->refresh_token;
			$api_keys['date_refresh'] =$this->auth_data->date_refresh;
			$api_keys['seller'] = $this->auth_data->seller;
			$integration = $this->model_integrations->update(array('auth_data'=>json_encode($api_keys)),$this->integration['id']);	
		
		}
		// echo 'access token ='.$this->getAccessToken()."\n";
		return $meli; 
		
	}
	
	function meliHttp($method, $url, $params = null, $data=null, $prd_id = null, $int_to=null, $function=null)
	{
		$meli= $this->getkeys();
		//$param = array('access_token' => $this->auth_data->access_token);  - agora isso vai no header 
		$param = array();
		if (!is_null($params)) {
			//$param[] = $params; 
			$param = $params;
		}
		
		if ($method == 'post') {
			$return = $meli->post($url, $data, $param);
		}elseif ($method == 'put') {
			$return = $meli->put($url, $data, $param);
		}else {
			$return = $meli->get($url, $param);
		}
		$this->responseCode = $return['httpCode']; 
		$this->result 		= $return["body"];
		
		if ($this->responseCode == 429) {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.");
            sleep(60);
			return $this->meliHttp($method, $url, $params, $data, $prd_id , $int_to, $function);
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			return $this->meliHttp($method, $url, $params, $data, $prd_id , $int_to, $function);
		}
        if ($this->responseCode == 503) {
		    $this->log("Meli com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			return $this->meliHttp($method, $url, $params, $data, $prd_id , $int_to, $function);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'function' => $function,
				'url' => $url,
				'method' => $method,
				'sent' => json_encode($data),
				'response' => json_encode($this->result),
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
        return $return;
	}
	
	function writeMeliError($sku) 
	{		
		$respostaML = json_decode(json_encode($this->result),true);
		if ( $respostaML['error'] == 'validation_error')  {
			$errors = $respostaML['cause'];
			$errorTransformation = '';
			foreach ($errors as $erro) {
				if ($erro['type'] == 'error') {
					$errorTransformation =  'ERRO: '.$erro['message'].' ! ';
					$this->errorTransformation($this->prd['id'],$sku, $errorTransformation, "Envio Marketplace",$this->prd_to_integration['id'] , $erro['code'] );
				}
			}
			if ($errorTransformation == '') {
				$errorTransformation= json_encode($this->result); 
				$this->errorTransformation($this->prd['id'],$sku, $errorTransformation, "Envio Marketplace",$this->prd_to_integration['id']);
				echo json_encode($this->result)."\n";	
			}
			echo $errorTransformation."\n";
		}
		else {
			$this->errorTransformation($this->prd['id'],$sku, json_encode($this->result), "Envio Marketplace",$this->prd_to_integration['id']);
			echo json_encode($this->result)."\n";
		}
	}

	function getMeliVariations($returnML)
	{
		$respostaML =  json_decode(json_encode($returnML),true);
		foreach ($respostaML['variations'] as $variacao) {
			if (!is_null($variacao['seller_custom_field'])) {
				$sku_variant = $variacao['seller_custom_field'];
			}
			else {
				foreach ($variacao['attributes'] as $attribute){
					if ($attribute['id'] == "SELLER_SKU") {
						$sku_variant = $attribute['values'][0]['name'];
						break;
					}
					$sku_variant = '';
				}
			}
			echo ' SKU da variação = '.$sku_variant."\n";
			$variant = substr($sku_variant,strrpos($sku_variant,'-')+1);
			$mkt_sku = $this->model_marketplace_prd_variants->getMarketplaceVariantsByFields($this->int_to,$this->integration['store_id'],$this->prd['id'],$variant);  // vejo se é uma variação nova ou antiga
			if (!$mkt_sku) {  // produto novo cadastro o novo ID. 
				$data = array (
					'prd_id' => $this->prd['id'],
					'variant' => $variant,
					'store_id' => $this->integration['store_id'],  		// loja Conectala
					'company_id' => $this->integration['company_id'],   // empresa conectala 
					'sku' => $variacao['id'],
					'int_to' => $this->int_to,
				);
				$mkt_sku = $this->model_marketplace_prd_variants->create($data);
			}	
		}	
	}

	function updateMLUltEnvio($prd, $variant = null) 
	{
			
		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];
		if ($prd['EAN'] == '') {
			if ($prd['is_kit'] == 1) {
				$ean ='IS_KIT'.$prd['id'];
			}
			else {
				$ean ='ML_EAN'.$prd['id']; 
			}
			if (!is_null($variant_num)) {
				$ean = $ean."V".$variant_num;
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
    		'list_price' => $prd['price'],
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
    		'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'], 
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
    	);

        $data = $this->formatFieldsUltEnvio($data);
		
		$savedUltEnvio =$this->model_ml_ult_envio->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = "Falha ao tentar gravar dados na tabela ml_ult_envio.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        } 
	}

	function updateBlingUltEnvio($prd, $variant = null) 
	{
			
		// EAN para colocar no Bling_ult_envio. Não é importante ter EAN, então crio um EAN único para cada produto
		if ($prd['is_kit'] == 1) {
			$ean ='IS_KIT'.$prd['id'];
		}
		else {
			$ean ='ML_EAN'.$prd['id']; 
		}
		$skubling = $this->prd_to_integration['skubling']; 
		if (!is_null($variant)) {
			$ean = $ean."V".$variant['variant'];
			$skubling = $skubling.'-'.$variant['variant']; 
		}
    	$data = array(
    		'int_to' => $this->int_to,
    		'company_id' => $prd['company_id'],
    		'EAN' => $ean,
    		'prd_id' => $prd['id'],
    		'price' => $prd['promotional_price'],
    		'list_price' => $prd['price'],
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
    		'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'], 
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
			'variant' => (is_null($variant)) ? $variant : $variant['variant'],
    	);

        $data = $this->formatFieldsUltEnvio($data);

		$savedUltEnvio= $this->model_blingultenvio->createIfNotExist($ean, $this->int_to, $data); 
		if (!$savedUltEnvio) {
            $notice = "Falha ao tentar gravar dados na tabela bling_ult_envio.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        } 	
	}
	
	function montaArray($sku, $novo_produto = true, $vendas = 0) 
	{
		//echo "Produto Lido \n";
		//var_dump($this->prd);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		if ($this->checkOfficialStore()==false) {return false;};
		
		//Array Minimo 
		if (($this->int_to == 'ML') || ($this->int_to == 'MLC')) {
			$produto = array(
				
				"currency_id"   => "BRL",
				"sale_terms" => array(
	     			array (
	        			"id" => "MANUFACTURING_TIME",
	        			"value_name" => $this->prd['prazo_operacional_extra']." dias",
	     			)
	  			),
				"seller_custom_field" =>$sku ,
	  			"attributes" => array()
			); 
		} else {
			$produto = array(
				"currency_id"   => "BRL",
				"sale_terms" => array(),
				"seller_custom_field" =>$sku ,
	  			"attributes" => array()
			);
		}
		
		if (!is_null($this->official_store_id)) {
			$produto['official_store_id'] = $this->official_store_id; 
		}

		if ($novo_produto) { // Atributos que só podem ser definidos e não podem ser alterados -  Descricao será alterada com uma chamada específica 
			$produto["site_id"] = "MLB";
			$semacento = strtr(utf8_decode($this->prd['description']),
                 utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
			$descricao = preg_replace("/[^a-zA-Z0-9] /", " ", $semacento);
			$descricao = substr(htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",str_replace("<br>","\n",$descricao)))), ENT_QUOTES, "utf-8"),0,3800);
			$semacento = strtr(utf8_decode($descricao),
	                 utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
	                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');

			$produto['description'] = array("plain_text" => utf8_encode($semacento));
			$produto["listing_type_id"] = $this->tipo_anuncio ;  
			/*
			if ($this->int_to == 'ML') {
				$produto["listing_type_id"] = "gold_pro";  
			}else {
				$produto["listing_type_id"] = "gold_special";  
			}*/
			//"listing_type_id" => "gold_pro",   	    // Premium
			//"listing_type_id" => "gold_premium",   	// Diamante
			//"listing_type_id" => "gold_special",    	// Clássico
			//"listing_type_id" => "gold",   			// Ouro
			//"listing_type_id" => "silver",   			// Prata
			//"listing_type_id" => "bronze",   			// Bronze
			//"listing_type_id" => "free",   			// Grátis
		}else { 
			if ((int)$this->prd['qty'] > 0) {
				$produto["status"] = "active"; // ativa produtos que podem ter sido pausados por algum erro de transformação
			}
		}
		if ($vendas>0) {
			echo ' este produto já teve venda ('.$vendas.') e nem todos os atributos poderão ser alterado '."\n";
		}
		if (($novo_produto) || ($vendas==0) ) { // atributos que só podem ser alterados até a primeira venda 
		// https://developers.mercadolivre.com.br/pt_br/produto-sincronizacao-de-publicacoes
			$produto["title"] = substr(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8"),0,60);
			$produto["buying_mode"] = "buy_it_now"; 
			$produto["condition"] = "new";
			
			$produto["category_id"] = $this->prd['categoria_ML'];
			
			$produto["shipping"]["local_pick_up"] = false;
			$produto["shipping"]["free_shipping"] = false;
			$this->getMe();  // vejo a configuração do usuário para ver que tipo de envio o ML aceita. 
			if (in_array('me1',$this->shipping_modes)) {
				$produto["shipping"]["mode"] = 'me1';
				$larg = ($this->prd['largura'] < 11) ? 11 : $this->prd['largura'];
				$prof = ($this->prd['profundidade'] < 16) ? 16 : $this->prd['profundidade'];
				$altu = ($this->prd['altura'] < 2) ? 2 : $this->prd['altura'];
				$produto["shipping"]["dimensions"] =  ceil($prof).'x'.ceil($larg).'x'.ceil($altu).','.ceil(($this->prd['peso_bruto']*1000));
			}
			elseif (in_array('me2',$this->shipping_modes)) {
				$produto["shipping"]["mode"] = 'me2';
			} elseif (in_array('custom',$this->shipping_modes)) {
				$produto["shipping"]["mode"] = 'custom';
			}
			
			$produto["sale_terms"][] = array(
					 "id" => "WARRANTY_TYPE",
       				 "value_name" =>"Garantia de fábrica"
     			);
     		$produto["sale_terms"][] = array (
        			"id" => "WARRANTY_TIME",
        			"value_name" =>  $this->prd['garantia']." meses",
     			);
		}
			
			
			
		$tipos_variacao = array();
		if ($this->prd['has_variants']=="") {  // se não tem variação, o preço e quantidade fazem parte do produto, caso contrário, faz parte da variação. 
			$produto["available_quantity"] = ((int)$this->prd['qty']<0) ? 0 : (int)$this->prd['qty'];
			$produto["price"] = $this->prd['promotional_price'];
		}
		else {
			$tipos_variacao = explode(";",strtoupper($this->prd['has_variants'])); 
		}
		if (strpos($redirectUrl,"teste.conectala.com" ) > 0)  {
  			 $produto["title"] = "Item De Teste - Não Comprar"; 
		}  
		
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($this->prd['categoria_ML'],$this->int_to_principal);
		foreach($atributosCat as $atributoCat) {
			if ($atributoCat['variacao']) { 
				if (in_array(strtoupper($atributoCat['nome']), $tipos_variacao)) { // se ja tá na variação tb não pode ser atributo 
					continue;
				}
				if ($atributoCat['obrigatorio'] == 1) {// atributos que podem ser variação não podem ser atributos do produto pai
					// continue;
				}
			}
			
			if 	($atributoCat['id_atributo'] == 'BRAND') {
				$produto['attributes'][]= array(
					"id" => $atributoCat['id_atributo'],
					"value_name" =>$this->prd['brandname'],
				);
				continue;
			}
			if 	($atributoCat['id_atributo'] == 'ITEM_CONDITION') {
				$produto['attributes'][]= array(
					"id" => $atributoCat['id_atributo'],
					"value_name" => 'Novo',
				);
				continue;
			}
			if 	($atributoCat['id_atributo'] == 'EXCLUSIVE_CHANNEL') {
				$produto['attributes'][]= array(
					"id" => $atributoCat['id_atributo'],
					"value_id" => "-1",
					"value_name" => null,
				);
				continue;
			}
			
			if 	($atributoCat['id_atributo'] == 'MANUFACTURER') {
				$produto['attributes'][]= array(
					"id" => $atributoCat['id_atributo'],
					"value_name" => $this->prd['brandname'],
				);
				continue;
			}
			$tipos_variacao = array();
			if ($this->prd['has_variants']!="") {
				 $tipos_variacao = explode(";",strtoupper($this->prd['has_variants'])); 
				if 	(in_array(strtoupper($atributoCat['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
					continue;
				}
			}
			if ($atributoCat['id_atributo'] == 'SELLER_SKU') {
				if ($this->prd['has_variants']=="") { // Seller_sku vai para a variação em vez do produto pai. 
					$produto['attributes'][]= array(
						"id" => $atributoCat['id_atributo'],
						"value_name" => $sku,
					);
				}
				continue;
			}
			
			if 	(($atributoCat['id_atributo'] == 'EAN') || ($atributoCat['id_atributo'] == 'GTIN')) {
				if ($this->prd['EAN'] != '') {
					$produto['attributes'][]= array(
						"id" => $atributoCat['id_atributo'],
						"value_name" => $this->prd['EAN'],
					);
				} else {
					if ($novo_produto) {
					 /*rick EAN 	$produto['attributes'][]= array(
					 		"id" => $atributoCat['id_atributo'],
					 		"value_id" => "-1",
					 		"value_name" => null,
					 	);
					  * */
					}
				}
				continue;
			}

			if (!is_null($this->prd['product_catalog_id'])) {
				$atributo_prd = $this->model_products_catalog->getProductAttributeByIdIntto($this->prd['product_catalog_id'],$atributoCat['id_atributo'],$this->int_to_principal);	
			} else {
				$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to_principal);
			}
			if ($atributo_prd) {
				$produto['attributes'][]= array(
					"id" => $atributo_prd['id_atributo'],
					"value_name" => $atributo_prd['valor'],
				);
			}else{
				if ($atributoCat['obrigatorio'] == 1) {
					$msg= 'Falta atributo da categoria obrigatório '.$atributoCat['id_atributo'].'-'.$atributoCat['nome'];
					$this->errorTransformation($this->prd['id'],$sku, $msg,"Preparação para Envio");
					return false;
				}
				if ($novo_produto) {
					/*$produto['attributes'][]= array(
						"id" => $atributoCat['id_atributo'],
						"value_id" => "-1",
						"value_name" => null,
					);*/
				}
			}
		}

		// Pego as fotos dos produtos 
		$produto['pictures'] = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		
		if ($this->prd['has_variants']!="") {
			$prd_variacao = array();
			$variations = array();
            // var_dump($prd_vars);
            $tipos = explode(";",$this->prd['has_variants']);

			foreach($this->variants as $key => $variant) {
			  	if (isset($variant['sku'])) {
			  		$variants_value  = explode(';', $variant['name']);
			  		$mkt_sku = $this->model_marketplace_prd_variants->getMarketplaceVariantsByFields($this->int_to,$this->integration['store_id'],$this->prd['id'],$variant['variant']);  // vejo se é uma variação nova ou antiga
					if ($mkt_sku) {
						$this->variants[$key]['ml_id'] = $mkt_sku['sku']; 
					} 
					$attribute_combinations = array();
					$i=0;
					foreach ($tipos as $z => $campo) {
						$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoria_ML'],ucfirst(strtolower($campo)),'ML');
						//var_dump($atributoCat);
						
						$valor_id = null;
						if ($atributoCat['valor'] != '') {  // verificar se o valor da variação já está cadastrado 
							$valores = json_decode($atributoCat['valor'],true);
							foreach($valores as $valor) {
								if (strtoupper($valor['name']) ==  strtoupper($variants_value[$z])) {
									$valor_id = $valor['id'];
									continue;
								}
							}
						}
						if (is_null($valor_id)) {
							$attribute_combinations[] = array(
								"name" => ucfirst(strtolower($campo)),
								"value_name" => $variants_value[$z], 
								"value_id" => null,
							);
						} else {
							$attribute_combinations[] = array(
								"id" => $atributoCat['id_atributo'],
								"value_id" => $valor_id,
							);
						}
						
					}
					
					// Monto array com as imagens do produto
					$vardir = '';
					$cnt = 0;
					if (($this->pathImage == 'product_image') && (!is_null($variant))){
						if (!is_null($variant['image']) && trim($variant['image'])!='')	{
							$vardir = '/'.$variant['image'];
							$cnt ++;
							if($cnt == 10) { // ML só aceita até 10 imagens em uma variação. 
								break; 
							}
						}
					} 
					
					$atrib_variants = array();
					$atrib_variants[] = array(
								"id" => "SELLER_SKU",
								"name" => "SKU",
                   				"value_id" => null,
								"value_name" => $sku.'-'.$variant['variant'],
								"value_struct" => null
							);
					if (!is_null($variant['EAN']) && $novo_produto && ($this->prd['EAN'] == '')) {  //Só pode enviar nos filhos se o pai não tem. 
						if ($variant['EAN'] != '') {
							$atrib_variants[] = array(
								"id" => "EAN",
								"name" => "EAN", 
								"value_id" => null,
								"value_name" => $variant['EAN'],
							);
						}
					}
					
					$variacao = array(
						"attribute_combinations" =>  $attribute_combinations,
						"price" => (float)$this->prd['promotional_price'], // apesar de parecer mandar preços diferentes, o ML ignora e usa o mais alto 
						"available_quantity" => ceil(($variant['qty']<0)  ? 0 : $variant['qty']),
						"picture_ids" => $this->getProductImages($this->prd['image'], $this->pathImage, $vardir, true, $variant['variant']), 
						"seller_custom_field" => $sku.'-'.$variant['variant'],
						"attributes" => $atrib_variants, 
                    );

                    if ($variant['status'] != 1) {
                        $variacao['available_quantity'] = 0;
                    }

					if ($mkt_sku) {
						$variacao['id'] = $mkt_sku['sku'];
					} 
					$variations[] = $variacao;
				}	

			}
			$produto['variations'] = $variations;	
			
		}

		$resp_json =  json_encode($produto);
		if ($resp_json === false) {
			echo 'Houve um erro ao fazer o json do produto'."\n";
			if ( json_last_error_msg()=="Malformed UTF-8 characters, possibly incorrectly encoded" ) {
				echo "Tentando resolver mudando descrição e título\n";
				$produto['description'] = array("plain_text" => utf8_encode($semacento));
				if (array_key_exists('title',$produto)) {
					$produto['title'] = substr(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8"),0,55);
				}
				
			}
			$resp_json=  json_encode($produto);
			echo($resp_json);
			if ($resp_json === false) {
				var_dump($produto);
				echo "\n".json_last_error_msg()."\n";
				echo "não deu para acertar. Morri\n";
				die;
			}
		}
		
		echo print_r($resp_json,true)."\n";
		return $produto;
			
	}

	function changeMLPriceQty($status) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$skumkt = $this->prd_to_integration['skumkt'];
		$skubling = $this->prd_to_integration['skubling'];
		
		// crio o array com o necessário para alterar preço e estoque somente
		$produto = array(); 
		if ($this->prd['has_variants']=="") {  // se não tem variação, o preço e quantidade fazem parte do produto, caso contrário, faz parte da variação. 
			$produto["price"] = (float)$this->prd['promotional_price'];
			$produto["available_quantity"] = ((int)$this->prd['qty']<0) ? 0 : (int)$this->prd['qty'];
		}
		else {
			$variations = array();
			foreach($this->variants as $key => $variant) {
				$mkt_sku = $this->model_marketplace_prd_variants->getMarketplaceVariantsByFields($this->int_to,$this->integration['store_id'],$this->prd['id'],$variant['variant']);  // vejo se é uma variação nova ou antiga
				if (!$mkt_sku) {
					echo " Variação ainda não cadastrada !!!!!! \n";
					return false;
				}
				$variations[] = array(
					'id' =>  $mkt_sku['sku'], 
					"price" => (float)$this->prd['promotional_price'], // apesar de parecer mandar preços diferentes, o ML ignora e usa o mais alto 
					"available_quantity" => ((int)$variant['qty']<0) ? 0 : (int)$variant['qty'], 
				);
			}
			$produto['variations'] = $variations;	
		}

		echo "Alterando Preço e Estoque do produto ".$this->prd['id']." ".$this->prd['name']." skumkt = ".$skumkt."\n";
		
		echo print_r(json_encode($produto),true)."\n";
		$url = '/items/'.$skumkt;
		
		$retorno= $this->meliHttp('put', $url, null, $produto, $this->prd['id'], $this->int_to, 'Alteração Preço e Estoque');
		
		$respostaML = json_decode(json_encode($this->result),true);
		// var_dump($retorno);
		if ($this->responseCode == 500) { // Algum erro interno no ML. Vejamos se dá para tratar 
			if (!is_null($respostaML)) { // é um json talvez possa ser tratado.
				if ($respostaML['message'] == 'The thread pool executor cannot run the task. The upper limit of the thread pool size has probably been reached. Current pool size: 1000 Maximum pool size: 1000') { 
					echo "Tentarei outra vez pois deu ".$this->responseCode." ".$respostaML['message']."\n";
					sleep(60);
					return $this->changeMLPriceQty();
				}
				if ($respostaML['message'] == 'Timeout waiting for idle object') { 
					echo "Tentarei outra vez pois deu ".$this->responseCode." ".$respostaML['message']."\n";
					sleep(60);
					return $this->changeMLPriceQty();
				}
				if (strpos($respostaML['message'], 'Timeout: Pool empty. Unable to fetch a connection in 0 seconds, none available')>0) {
				//if ($respostaML['message'] == '[http-nio2-8080-exec-97] Timeout: Pool empty. Unable to fetch a connection in 0 seconds, none available[size:30; busy:30; idle:0; lastwait:100].') { 
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					return $this->changeMLPriceQty();
					return;
				}
			}
		}
		if ($this->responseCode == 401) { // expirou o token 
			if ($respostaML['message'] == 'expired_token') {
				echo "Tentarei outra vez pois deu ".$this->responseCode."\n";
				sleep(60); echo "Estourou o Limite\n";
				return $this->changeMLPriceQty();
			}
		}	
	
		if (($this->responseCode == 400) && ($status != 'paused') && ($status != 'active') ){ // è um pause que não deixa acertar o produto
			$erro =  "Oferta com status ".$status." no marketplace para ".$skumkt."\n";
			$this->errorTransformation($this->prd['id'],$this->prd_to_integration['skubling'], $erro, "Preparação para Envio");
			return false; 
		}
		if ($this->responseCode == 400) {  // deu um erro que dá para tratar
			$this->writeMeliError($this->prd_to_integration['skubling']);
			return false;
		}
		if  ($this->responseCode != 200) {	// deu um erro que não consigo tratar
			echo " Erro URL: ". $url. "\n"; 
			echo " httpcode: ".$this->responseCode."\n"; 
			echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
			echo " ENVIADO : ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true).' ENVIADO:'.print_r($produto,true),"E");
			return false;
		}
		
		// Atualizou ok....
		// aproveito e atualizo o link e o health 
		$this->model_integrations->updatePrdToIntegration(
			array (
				'ad_link' 		=> $respostaML['permalink'],
				'quality' 		=> $respostaML['health'],
				'date_last_int' => $this->dateLastInt
			), $this->prd_to_integration['id']);
		
		// var_dump($respostaML);
		return true;
	
	} 

	function pauseML() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Pausando ". $this->prd_to_integration['skumkt'];"\n";
		$pause = Array (
						'status' => 'paused'
				);
									
		$url = '/items/'.$this->prd_to_integration['skumkt'];
		$retorno= $this->meliHttp('put', $url, null, $pause,$this->prd['id'], $this->int_to, 'Pausando anúncio');
		
		if  ($this->responseCode == 400 ) { // verifico se já está em closed e ai tem que remover SKUmkt de volta para 00 no Prd_to_integration e Bling_ult_envio
			$body = json_decode(json_encode($this->result),true);
			if ($body['cause'][0]['message'] == 'Item in status closed is not possible to change to status paused. Valid transitions are [closed]') {
				echo "O anúncio ficou Closed no ML \n";
				$this->model_integrations->updatePrdToIntegration(array('approved'=> 4), $this->prd_to_integration['id']);
				return true;
			}
			if ($body['message']== 'Cannot update item '.$this->prd_to_integration['skumkt'].' [status:under_review, has_bids:false]') {
				echo 'Produdo '.$this->prd_to_integration['skumkt'].' com status:under_review, has_bids:false - já pausado pelo ML'."\n";
				return true;
			}
			if ($body['message']== 'Cannot update item '.$this->prd_to_integration['skumkt'].' [status:under_review, has_bids:true]') {
				echo 'Produdo '.$this->prd_to_integration['skumkt'].' com status:under_review, has_bids:true - já pausado pelo ML'."\n";
				return true;
			}
		}
		if ($this->responseCode != 200 ) {
			echo " Erro URL: ". $url. "\n";
			echo " httpcode: ".$this->responseCode."\n"; 
			echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
			echo " Enviado : ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no put pause produto site:'.$url.'  httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true).' ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		return true;
	}

	function getIntegration() 
	{
		
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
		$int_to = $this->int_to;
		if ($int_to == 'MLC') {
			$int_to = 'ML';
		}
		if ($int_to == 'H_MLC') {
			$int_to = 'H_ML';
		}
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				if ($this->integration_store['int_from'] == 'CONECTALA') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto("0",$int_to);
				}elseif ($this->integration_store['int_from'] == 'HUB') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$int_to);
				} 
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
		//echo " INTEGRATION STORE \n";
		//var_dump($this->integration_store);
		
		//echo " INTEGRATION main \n";
		//var_dump($this->integration_main);
	}
	
	function checkOfficialStore() {
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$url = '/users/'.$this->auth_data->seller.'/brands';
		$retorno = $this->meliHttp('get', $url);
		
		// var_dump($this->result);

		if ($this->responseCode=="404") { // não tem loja oficial   
			echo "Não é loja oficial \n";
			$this->official_store_id = null;
			return true;
		}

		if (!($this->responseCode=="200") )  {  
			$erro = "Erro no get url: ".$url." httpcode=".$this->responseCode." RESPOSTA: ".print_r($this->result,true)." \n"; 
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro,"E");
			die; // melhor morrer se não pegar a loja oficial.
			return false;
		}
		$brands = json_decode(json_encode($this->result),true);
		$this->official_store_id =  $brands['brands'][0]['official_store_id'];
		echo "É a loja oficial ".$this->official_store_id."\n";
		return true;
	}
	
	function hasShipCompany() {
		$this->load->library('calculoFrete');
		
		$cat_id = json_decode ( $this->prd['category_id']);
		$sql = "SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id =".intval($cat_id[0]).")";
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
	
	
	function getMe() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$meli = $this->getkeys();
		$url = 'users/me';
		$params = array();
		$retorno = $meli->get($url, $params);
		if ($retorno['httpCode']!="200")  {  // deu algum erro lendo as informações, volto para o login 
			$msg = 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpCode'].' RESPOSTA: '.print_r($retorno,true). ' ENVIADO: '.print_r($params,true);
			$this->log_data('batch',$log_name,$msg ,"E");
			die; // melhor morrer do que continuar processando. 
			return false;
		}
		$resp = json_decode(json_encode($retorno['body']),true);
		$this->shipping_modes = $resp['shipping_modes'];
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