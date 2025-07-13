<?php 
/* 
* recebe a requisição e cadastra / alterara /inativa no SellerCenter
*/


require APPPATH . "controllers/Api/queue/ProductsConectala.php";

/**
 * @property	Bucket									$bucket
 * @property	Model_blingultenvio						$model_blingultenvio
 * @property	Model_brands							$model_brands
 * @property	Model_category							$model_category
 * @property	Model_categorias_marketplaces			$model_categorias_marketplaces
 * @property	Model_brands_marketplaces				$model_brands_marketplaces
 * @property	Model_atributos_categorias_marketplaces	$model_atributos_categorias_marketplaces
 * @property	Model_marketplace_prd_variants			$model_marketplace_prd_variants
 * @property	Model_settings							$model_settings
 * @property	Model_integration_last_post				$model_integration_last_post
 * @property	Model_sellercenter_last_post			$model_sellercenter_last_post
 * @property	Model_integrations						$model_integrations
 * @property	Model_products							$model_products
 * @property	Model_products_winners					$model_products_winners
 * @property	Model_errors_transformation				$model_errors_transformation
 * @property	CI_Router								$router
 */
class ProductsConectalaSellerCenter extends ProductsConectala
{
    var $inicio;
	var $auth_data;
	var $int_to_principal;
	var $int_to = '';
	var $int_to_SC = '';
	var $integration;
    var $variants;
    var $isMock = false;
	var $product_id;
	var $reserve_to_b2W = 0; // removido em 13/09/2022
	var $mandatory_category = true;
	var $mandatory_attributes = true;
	var $skuformat = 'default';
	var $ad_link = null;
	var $first_variant = null;
	
    protected $api_keys;
    protected $api_url = '';
    protected $hasAuction = true;

    public function __construct() 
    {
        parent::__construct();
       
        //$this->int_to = $int_to;
        //$this->int_to_SC = $int_to_SC;
		$this->load->library('Bucket');

	    $this->load->model('model_blingultenvio');
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		$this->load->model('model_marketplace_prd_variants'); 
		$this->load->model('model_settings'); 	
		$this->load->model('model_integration_last_post'); 
		$this->load->model('model_sellercenter_last_post'); 
        $this->load->model('model_integrations');

        $this->load->model('model_products');
        $this->load->model('model_products_winners');
        $this->load->model('model_errors_transformation');		
    }
	
	public function index_get() {
		$this->isMock = true;
		$products = array(9036);
		$this->getkeys();
		foreach ($products as $id) {
			$this->product_id = $id;
			$this->receiveData();
			$this->checkAndProcessProduct();
		}
		
	}
	
	public function index_post() 
    {
    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        $this->getkeys();

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
		parent::checkAndProcessProduct();
	}
	
	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		// Impede que crie um produto que foi inativado.
		if($this->from_inactivate){
			$prd_id = $this->prd['id'];
			echo "Produto $prd_id inativo para este Seller Center.\n";
			return;
		}

		switch ($this->skuformat) {
			case 'store_skuoriginal' :  // necessário para shophub
				$sku = $this->prd['store_id'].'_'.$this->prd['sku'];
				break;
			case 'default' : 
				$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
				break;
			default:
				$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
				break;
		} 

		if ($this->hasAuction) { // EAN é mandatório
			if (!$this->prd['EAN']) {  // não tem variação
				$msg= 'Código de barras (EAN) é obrigatório';
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
				return false;
			}
			// forço o EAN ter 13 digitos
			$this->prd['EANAUCTION'] = substr('00000000000000'. $this->prd['EAN'],-13);
			$sku = $this->prd['EANAUCTION'].$this->int_to;
			
			if ($this->prd['has_variants'] != '') {  // verifico se as variações tb estão oK
				if (count($this->variants) ==0) {
					$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
		            echo $erro."\n";
		            $this->log_data('batch', $log_name, $erro,"E");
					die;
				}
				foreach($this->variants as $variant) { // verifico se todas as variações tem EAN	
					if (!$variant['EAN']) {
						$msg= 'Código de barras (EAN) é obrigatório e está faltando em uma variação ';
						echo 'Produto '.$this->prd['id']." ".$msg."\n";
						$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
						return false;
					}
					if (is_null($this->first_variant)) {
						$this->first_variant = (int)$variant['variant'];
					}elseif ((int)$variant['variant'] < (int)$this->first_variant) {
						$this->first_variant = (int)$variant['variant'];
					}
				}
			}	
	    }
		
	    // pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku)==false) { return false;};
		
		if ($this->prd['has_variants'] != '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}
		}
		
		$skumkt = $this->prd_to_integration['skumkt'];
        $ean    = $this->prd['EAN'];
		
		if ($this->hasAuction) { // EAN é mandatório e precisa de leilão
		// Pego a linha atual do ganhador do leilão deste EAN
			$auction_status = $this->model_products_winners->getWinner($this->prd['EANAUCTION'], $this->int_to);
			// rodo o leilão 
			if (!$this->runAuction($auction_status, $sku)) {
				return false; // não ganhou ou já tinha um ganhador no dia de hoje.
			}
		}
       
		// $integration_last_post = $this->model_integration_last_post->getDataByIntToPrdIdVariant($this->prd_to_integration['int_to'], $this->prd['id']);

	    // limpa os erros de transformação existentes da fase de preparação para envio
	    // $this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'], $this->prd_to_integration['int_to'], "Preparação para o envio");
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);

        // pego informações adicionais como preço, estoque e marca .
        if ($this->prepareProduct($sku) == false) {
        	return $this->undoAuction(); // desfaz o ganhador de leilão
        }

        // Monto o Array para enviar para o Seller Center
        $produto = $this->montaArray($sku, true, 0);

        if ($produto == false)
            return $this->undoAuction(); // desfaz o ganhador de leilão

		
		echo "Verificando se o produto já existe\n";
		$url = $this->api_url.'Products/'.$sku;
        $return = $this->Http($url, 'GET', NULL, $this->prd['id'], $this->prd_to_integration['int_to'], 'Verificando se Já existe');
		if ($this->responseCode == 200) {
			echo "Produto já existe no Sellercenter. Chamando Update\n";
			$this->prd_to_integration['skumkt'] = $sku;
			$this->prd_to_integration['skubling'] = $sku;
			return $this->updateProduct();
		}

        echo "Incluindo o produto ".$this->prd['id']." ".$this->prd['name']."\n";

        $url = $this->api_url.'Products';

        $return = $this->Http($url, 'POST', json_encode($produto), $this->prd['id'], $this->prd_to_integration['int_to'], 'Novo produto');
		
		$msg  = " CHAMANDO  URL: ". $url. "\n"; 
        $msg .= " httpcode: ".$this->responseCode."\n"; 
        $msg .= " RESPOSTA: ".print_r($this->result, true)." \n"; 
        $msg .= " ENVIADO : ".print_r($produto, true)." \n"; 

		//var_dump($return);
		
        if ($this->responseCode != 201)
        { 
            if(!$skumkt)
                $skumkt = '';

            $msg  = " Erro URL: ". $url. "\n"; 
            $msg .= " httpcode: ".$this->responseCode."\n"; 
            $msg .= " RESPOSTA: ".print_r($this->result, true)." \n"; 
            $msg .= " ENVIADO : ".print_r($produto, true)." \n"; 
            $this->log_data('batch', $log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result, true).'
            ENVIADO:'.print_r($produto, true),"E");
			
			$resp = json_decode($this->result, true);

			// Extrai a mensagem da response.
			$newMsg = $this->getMessage($resp);
			if ($newMsg != null) {
				$msg = $newMsg;
			}

			// Caso nenhuma das validações acima tenha pego a mensagem, então irá inserir a mensagem de erro anterior.
            $this->errorTransformation($this->prd['id'], $sku, $msg, "Preparação para o envio");
           	$this->undoAuction(); // desfaz o ganhador de leilão
			if ($this->responseCode  >= 500) { //deu erro lá do outro lado é melhor morrer para q tente de novo mais tarde
				die; 
			}
			return false; 
        }
		
		//este eh o primeiro cadastro deste ean, procede para a inclusao
 
        $this->model_integrations->updatePrdToIntegration(array(
            'skumkt'         => $sku,
            'skubling'       => $sku,
            'mkt_sku_id'     => $sku,
            'mkt_product_id' => $sku,
            'variant' => null
        ), $this->prd_to_integration['id']);
        $this->prd_to_integration['skubling'] = $this->prd_to_integration['skulocal'] = $sku;
		$this->prd_to_integration['skumkt'] = $sku; 

		$this->updatePrdToIntegration($sku,2); 

        if ($this->prd['has_variants'] != "") 
        {
            foreach ($this->variants as $variant)
            {
				if (is_null($this->first_variant)) {
					$this->first_variant = (int)$variant['variant'];
				}elseif ((int)$variant['variant'] < (int)$this->first_variant) {
					$this->first_variant = (int)$variant['variant'];
				}

                $prd = $this->prd;
                $prd['sku'] 				= $variant['sku'];
                $prd['qty'] 				= $variant['qty'];
                $prd['EAN'] 				= $variant['EAN'];	
				$prd['qty_original'] 		= $variant['qty_original'];
				$prd['price'] 				= $variant['price'];
				$prd['list_price'] 			= $variant['list_price'];
				$prd['promotional_price'] 	= $variant['promotional_price'];

                if ($variant['status'] != 1) {
                    $prd['qty'] = 0;
                    $prd['qty_original'] = 0;
                }

                $this->updateLastPost($prd, $variant);

				$this->cleanPrdToIntegration($variant['variant']);
            }
        }
        else
        {
            $this->updateLastPost($this->prd, null);
        }
        
		$this->updateAttributes($sku);

        return true;
   
	}

	protected function updatePrdToIntegration($sku, $status_int = 2, $setnull = false )
	{
		
		$prd_upd = array (
			'skubling' 		=> $sku,
			'skumkt' 		=> $sku,
            'mkt_sku_id'    => $sku,
            'mkt_product_id'=> $sku,
			'status_int' 	=> $status_int,
			'date_last_int' => $this->dateLastInt,
			'ad_link'		=> $this->ad_link
		);
		if ($setnull!==false) {
			$prd_upd['variant'] = ($this->prd['has_variants'] != "") ? $this->first_variant : null ;
		}
		$this->model_integrations->updatePrdToIntegration($prd_upd,$this->prd_to_integration['id']);
	}
	
	protected function updateProduct()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Update"."\n";
	
		if ($this->hasAuction) { // EAN é mandatório
			$this->prd['EANAUCTION'] = substr('00000000000000'. $this->prd['EAN'],-13);
			// Pego a linha atual do ganhador do leilão deste EAN
	        $auction_status = $this->model_products_winners->getWinner($this->prd['EANAUCTION'], $this->int_to);
	        if ($this->prd_to_integration['prd_id'] != $auction_status['current_product_id']) {
	        	echo "Não é o atual ganhador do leilão\n";
	     		if (!$this->runAuction($auction_status, $this->prd_to_integration['skumkt'])) {
	     			echo "Saindo...\n";
					return ;
	     		}
	        }
		}

		$skumkt = $this->prd_to_integration['skumkt'];
		$sku    = $this->prd_to_integration['skubling'];
        $ean    = $this->prd_to_integration['ean'] = $this->prd['EAN'];

		echo "Verificando se o produto já existe\n";
		$url = $this->api_url.'Products/'.$sku;
        $return = $this->Http($url, 'GET', NULL, $this->prd['id'], $this->prd_to_integration['int_to'], 'Verificando se Já existe');
		if ($this->responseCode == 200) {
			$prod_marketplace = json_decode($this->result,true);
			if (array_key_exists('result', $prod_marketplace)) {
				if (array_key_exists('product', $prod_marketplace['result'])) {
					if (array_key_exists('marketplace_offer_links', $prod_marketplace['result']['product'])) {
						//var_dump($prod_marketplace['result']['product']['marketplace_offer_links']);
						$ad_link = json_encode($prod_marketplace['result']['product']['marketplace_offer_links']);
						if (!is_null($ad_link)) {
							$this->ad_link = $ad_link; 
						}
					}
				}
			}
		}
		elseif ($this->responseCode == 404) {
			echo "Produto não existe no Sellercenter. Chamando Insert\n";
			return $this->insertProduct();
		}
		else {
            $response_decode = json_decode($this->result);
            if (
                is_object($response_decode) &&
                property_exists($response_decode, 'message') &&
                $response_decode->message == 'Produto não encontrado.'
            ) {
                echo "Produto não existe no Sellercenter. Chamando Insert\n";
                return $this->insertProduct();
            }
            echo " Erro URL: ". $url. "\n";
            echo " httpcode: ".$this->responseCode."\n"; 
            echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
            $this->log_data('batch', $log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true), "E");
			
			$msg = 'Erro desconhecido';		
			$resp = json_decode($this->result, true);

			// Extrai a mensagem da response.
			$newMsg = $this->getMessage($resp);
			if ($newMsg != null) {
				$msg = $newMsg;
			}
			
            $this->errorTransformation($this->prd['id'], $sku, $msg, "Preparação para o envio");
			if ($this->responseCode == 401) {
				echo "Loja inativa no sellercenter\n";
				return; 
			}
            if ($this->responseCode >= 500) {
                die;
            }
            return;
        }

		
        // limpa os erros de transformação existentes da fase de preparação para envio
        // $this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'], $this->int_to, "Preparação para o envio");
        $this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
        // pego informações adicionais como preço, estoque e marca .
        if ($this->prepareProduct($sku) == false) {
			$this->changePriceQty($sku, $skumkt);
            return false;
        }
        
        // atualiza preço e estoque primeiro antes de alterar o resto do produto.
        if ($this->changePriceQty($sku, $skumkt) == false) {
            return false;
        }

		$this->updatePrdToIntegration($sku,1,null); 

/*      $sql = "UPDATE prd_to_integration SET status_int = ? WHERE skumkt = ?";
	    $this->db->query($sql, array(1, $skumkt));
        $this->model_products->updateProductIntegrationStatus($this->prd_to_integration['id'], 2);
*/

		if ($this->prd['has_variants'] != "") 
        {
            foreach ($this->variants as $variant)
            {
                $prd = $this->prd;
                $prd['qty']                 = $variant['qty'];
                $prd['qty_original']        = $variant['qty_original'];
                $prd['sku'] 				= $variant['sku'];
                $prd['EAN'] 				= $variant['EAN'];
				$prd['price'] 				= $variant['price'];
				$prd['list_price'] 			= $variant['list_price'];
				$prd['promotional_price'] 	= $variant['promotional_price'];

                if ($variant['status'] != 1) {
                    $prd['qty'] = 0;
                    $prd['qty_original'] = 0;
                }

                $this->updateLastPost($prd, $variant);
				if (is_null($this->first_variant)) {
					$this->first_variant = (int)$variant['variant'];
				}elseif ((int)$variant['variant'] < (int)$this->first_variant) {
					$this->first_variant = (int)$variant['variant'];
				}
            }
        }
        else
        {
            $this->updateLastPost($this->prd, null);
        }

        // Monto o Array para enviar para o Seller center
        $produto = $this->montaArray($sku, false, 0);

        if ($produto == false)
            return false;
        
        echo "Alterando o produto ".$this->prd['id']." ".$this->prd['name']."\n";

        $url = $this->api_url.'Products/'.$skumkt;

        // $return = $this->NMHttp($url, 'PUT', json_encode($produto), $this->prd['id'], $this->int_to, 'Alterando produto');

        // A atualização de preço e estoque são efetuados noutro método. Aqui só é feito atualização dos outros dados.
        unset($produto['product']['price']);
        unset($produto['product']['list_price']);
        unset($produto['product']['qty']);
		$return = $this->Http($url, 'PUT', json_encode($produto), $this->prd['id'], $this->prd_to_integration['int_to'], 'Alterando produto');
		
		if ($this->responseCode == 404)  {
			$return = json_decode($this->result,true);
			
			if (($return['message'] == "Product already integrated with marketplace, cannot receive updates. Only stock and price allowed.") ||
				($return['message'] == "Produto já integrado ao marketplace, não pode receber atualizações. Somente de estoque e preço é permitido.")) {
				echo "Produto já publicado e não pode mais alterar. Alterado somente estoque e preço\n";
				$this->updatePrdToIntegration($sku,2); 

				if ($this->prd['has_variants'] != "") {
					foreach ($this->variants as $variant)
					{
						if (is_null($this->first_variant)) {
							$this->first_variant = (int)$variant['variant'];
						}elseif ((int)$variant['variant'] < (int)$this->first_variant) {
							$this->first_variant = (int)$variant['variant'];
						}
						$this->cleanPrdToIntegration($variant['variant']);
					}
				}
			
				return true;
			}
			
		}

        if ($this->responseCode != 200)
        { 
            // Deu um erro que não consigo tratar
            echo " Erro URL: ". $url. "\n"; 
            echo " httpcode: ".$this->responseCode."\n"; 
            echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
            echo " ENVIADO : ".print_r($produto,true)." \n"; 
            $this->log_data('batch', $log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true).'  
                            ENVIADO:'.print_r($produto,true), "E");

			$msg = 'Erro desconhecido';
			$resp = json_decode($this->result, true);
			if (is_array($resp)) {
				// Extrai a mensagem da response.
				$newMsg = $this->getMessage($resp);
				if ($newMsg != null) {
					$msg = $newMsg;
				}
			}
            $this->errorTransformation($this->prd['id'], $skumkt, $msg, "Preparação para o envio");
			
            return false;
        }
        
        if ($this->prd['has_variants'] != "") {
        	// Verifico se é catálogo para pegar a imagem do lugar certo
			if (!is_null($this->prd['product_catalog_id'])) 
				$pathImage = 'catalog_product_image';
			else 
				$pathImage = 'product_image';
				
			foreach($this->variants as $variant) {
	
				switch ($this->skuformat) {
					case 'store_skuoriginal' :  // necessário para shophub
						$skuvar = $this->prd['store_id'].'_'.$variant['sku'];
						break;
					case 'default' : 
						$skuvar = $skumkt.'-'.$variant['variant'];
						break;
					default:
						$skuvar = $skumkt.'-'.$variant['variant'];
						break;
				}

                if ($variant['status'] != 1) {
                    $variant['qty'] = 0;
                }
				
				$var_json = array (
					'variation' => array(
						'EAN' 	=> $variant['EAN'], 
						'price' => $variant['promotional_price'],
						'qty'	=> $variant['qty'],
					)
				);
				
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
				$var_json['variation']['images'] = $images_var;
				
				Echo "Atualizando a variação: ".$variant['variant']."\n";
				// var_dump(json_encode($var_json));
				
				$url = $this->api_url.'Variations/sku/'.$skumkt.'/'.$skuvar;
				$return = $this->Http($url, 'PUT', json_encode($var_json), $this->prd['id'], $this->prd_to_integration['int_to'], 'Alterando Variação '.$variant['variant']);
				if ($this->responseCode != 200)
	            { 
	                // Deu um erro que não consigo tratar
	                echo " Erro URL: ". $url. "\n"; 
	                echo " httpcode: ".$this->responseCode."\n"; 
	                echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
	                echo " ENVIADO : ".print_r($var_json,true)." \n"; 
	                $this->log_data('batch', $log_name, 'ERRO no put variacao site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true).'  
	                                ENVIADO:'.print_r($produto,true), "E");

					$msg = 'Erro desconhecido';
					$resp = json_decode($this->result,true);
					// Extrai a mensagem da response.
					$newMsg = $this->getMessage($resp);
					if ($newMsg != null) {
						$msg = $newMsg;
					}
	                $this->errorTransformation($this->prd['id'], $skumkt, $msg, "Preparação para o envio");
					
	                return false;
	            }
			
			}
			
        }

		$this->updateAttributes($sku);

		$this->updatePrdToIntegration($sku,2); 
		return true; 
    }

	protected function cleanPrdToIntegration($variant = null) 
	{
		if (!is_null($variant)) {
			if ($variant != '0') {
				// apaga o registros das variants diferentes de 0 pois só precisa de 1 registro na prd_to_integations
				$todelete =  $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to,$variant);				

				if ($todelete){
					if (is_null($todelete['skumkt'])) {
						$this->model_integrations->removePrdToIntegration($todelete['id']); 
					}
				}
		  	}
		}
	}

	protected function updateAttributes($sku) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Verificando Atributos\n";

		/* verifico se o produto já tem atributos no sellercenter */
		$url = $this->api_url.'Products/attributes/'.$sku;
		$return = $this->Http($url, 'GET', null , $this->prd['id'], $this->prd_to_integration['int_to'], 'Listando atrributos ');
		if (($this->responseCode  != 404)  && ($this->responseCode  != 200)) {
			echo " Erro URL: ". $url. "\n"; 
			echo " httpcode: ".$this->responseCode."\n"; 
			echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
//			echo " ENVIADO : ".print_r($attributes_object,true)." \n"; 
			$this->log_data('batch', $log_name, 'ERRO no get de atrubutos site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true), "E");
			//die;
            return false;
		}
		$attributes_mkt = array(); 
		if ($this->responseCode  == 200) {
			$resp_array = json_decode($this->result, true);
			$attributes_mkt = $resp_array['result'];
		}

		// pego os attributos de categoria  que por enqunto não estamos enviando
		$category_attributes = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($this->prd['id']);
        $attributes_object = [];
        if (is_array($category_attributes) && !empty($category_attributes))
        {
            foreach ($category_attributes as $attribute)
            {
                if ($attribute['id_atributo'] != 'BRAND' && !empty($attribute['int_to']))
                {
                    $attributes_object[] = array(
                        'code' => $this->int_to_SC.'-'.$attribute['id_atributo'],
                        'value' => $attribute['valor']
                    );
                }
            }
        }
		// Mando os da cetgoria. Na teoria, não deveria ter nenhum....
        if(!empty($attributes_object))
        {
            $attributes_object = array('attribute' => $attributes_object);
            $attributes_object = json_encode($attributes_object, JSON_UNESCAPED_UNICODE);
            $url = $this->api_url.'Products/attributes/'.$sku;
            $return = $this->Http($url, 'PUT', $attributes_object);
        }
        
		// agora vejo os attributos customizados
		$attributes_custom = $this->model_products->getAttributesCustomProduct($this->prd['id']);
		$attributes_put = array();
		$attributes_post = array();
		foreach ($attributes_custom as $attribute)
		{	
			$found = false; 
			foreach ($attributes_mkt as $attribute_mkt) {
				if (mb_strtoupper($attribute['name_attr']) == mb_strtoupper($attribute_mkt['attribute'])) { // achei
					$found = true;
					if ($attribute['value_attr'] != $attribute_mkt['original_value']) { // mas é diferente então altero
						$attributes_put[] = array(
							'code' 	=> $attribute_mkt['code'],
							'name' 	=> $attribute['name_attr'],
							'value' => $attribute['value_attr']
						);
					}
				}
			}
			if (!$found) { // não achou então incluo 
				$attributes_post[] = array(
					'sku'  	=> $sku,
					'name' 	=> $attribute['name_attr'],
					'value' => $attribute['value_attr']
				);
			}
		}	

		if(!empty($attributes_post))  // tem algo para incluir  
		{
			if (!$this->sendAttributes($sku,'POST',$attributes_post, 'Incluindo atributos')) {
				return false; 
			}
		}

		if(!empty($attributes_put)) // tem algo para alterar 
		{
			if (!$this->sendAttributes($sku,'PUT',$attributes_put, 'Alterando atributos')) {
				return false; 
			}
		}
		return true; 
	}

	protected function sendAttributes($sku,$function,$attributes_array, $messageupdate) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo $messageupdate."\n";
		$url = $this->api_url.'Products/attributes/'.$sku;
		$attributes_object = array('attribute' => $attributes_array);
		$attributes_object = json_encode($attributes_object, JSON_UNESCAPED_UNICODE);
		echo print_r($attributes_object,true)."\n";
		$return = $this->Http($url, $function, $attributes_object, $this->prd['id'], $this->prd_to_integration['int_to'], $messageupdate);
		if (($this->responseCode != 201) && ($this->responseCode != 204) && ($this->responseCode != 200))
		{ 

			echo " Erro URL: ". $url. "\n"; 
			echo " httpcode: ".$this->responseCode."\n"; 
			echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
			echo " ENVIADO : ".print_r($attributes_object,true)." \n"; 
			$this->log_data('batch', $log_name, 'ERRO no '.$function.' de atrubutos site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true).'  
							ENVIADO:'.print_r($attributes_object,true), "E");
			
			$msg = 'Erro desconhecido';		
			$resp = json_decode($this->result, true); 

			// Extrai a mensagem da response.
			$newMsg = $this->getMessage($resp);
			if($newMsg !=null){
				$msg = $newMsg;
			}

			$this->errorTransformation($this->prd['id'], $sku, $msg, "Preparação para o envio");
			return false;
		}
		return true; 

	}

	protected function getkeys()
    {
		$this->getIntegration(); 
        $this->auth_data = $this->api_keys = json_decode($this->integration_main['auth_data']);
        $this->api_url = $this->api_keys->api_url;
	}


	public function getIntegration() 
	{
		
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $this->int_to);

		if ($this->integration_store)
        {
			if ($this->integration_store['int_type'] == 'DIRECT')
            {
				if ($this->integration_store['int_from'] == 'CONECTALA')
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto("0", $this->int_to);
				elseif ($this->integration_store['int_from'] == 'HUB')
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'], $this->int_to);				 
			}
			else 
            {
				$this->integration_main = $this->integration_store;
			} 
		}
	}
    
	
   // public function getCategoryMarketplace($skumkt, $int_to = '')
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
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
			return false;
		}
		// pego o tipo volume da categoria 
		$tipo_volume   = $this->model_category->getTiposVolumesByCategoryId($categoryId);		
		$this->prd['tipovolumecodigo'] = '';
		if (is_array($tipo_volume)){
			$this->prd['tipovolumecodigo'] = array_key_exists('codigo',$tipo_volume) ? $tipo_volume['codigo'] : '';	
		} 

		// não estamos mapeando as categorias dos sellercenters então mandamos as nossas categorias 
		$this->prd['localcategoryname'] = $category['name']; 
		
   		// pego a categoria do marketplace
		// $result= $this->model_categorias_marketplaces->getCategoryMktplace($int_to,$categoryId);
		// if (!$result) {
		// 	$msg= 'Categoria '.$categoryId.' não vinculada ao marketplace '.$int_to;
		// 	echo 'Produto '.$this->prd['id']." ".$msg."\n";
		// 	$this->errorTransformation($this->prd['id'],$skumkt,$msg,"Preparação para o envio");
		// 	return false;
		// }
		// $this->prd['categoryname'] = $category['name']; 
		
        // return $result['category_marketplace_id'];
        return ;
	}

	protected function prepareProduct($sku)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Preparando produto\n";			
	
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];

		if ((int)$this->prd['qty'] < $this->reserve_to_b2W) { // Mando só para a B2W se a quantidade for menor que 5. 
			$this->prd['qty']  = 0;
		}
		
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); 
		
		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);

		if ($this->prd['promotional_price'] > $this->prd['price'] )
			$this->prd['price'] = $this->prd['promotional_price']; 
		
		
		// Não manda EAN se não tem leilão 
		if ($this->hasAuction) {
			$this->prd['EAN'] = '';
		}
		// se tiver Variação,  acerto o estoque de cada variação
    	if ($this->prd['has_variants'] != "")
        {
            /*echo 'visualizando variações';
            if (!empty($this->variants))
                print_r($this->variants);
			*/
			foreach ($this->variants as $key => $variant)
            {
				$this->variants[$key]['qty_original'] = $variant['qty'];

				if ((int)$this->variants[$key]['qty'] < 0)
					$this->variants[$key]['qty'] = 0;
				
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100);

				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0))
					$this->variants[$key]['price'] = $this->prd['price'];
				
				$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
				// Não manda EAN se não tem leilão 
				if ($this->hasAuction) {
					$this->variants[$key]['EAN'] = '';
				}

				if (is_null($this->first_variant)) {
					$this->first_variant = (int)$variant['variant'];
				}elseif ((int)$variant['variant'] < (int)$this->first_variant) {
					$this->first_variant = (int)$variant['variant'];
				}
			}
		}
		
		if ($this->prd['is_kit'])
        {  
            // Talvez nao utilize pois kit nao tem ean
			$productsKit = $this->model_products->getProductsKit($this->prd['id']);
			$original_price = 0; 

			foreach($productsKit as $productkit) 
            {
				$original_price += $productkit['qty'] * $productkit['original_price'];
			}

			$this->prd['price'] = $original_price;
			echo " KIT ".$this->prd['id'].' preço de '.$this->prd['price'].' por '.$this->prd['promotional_price']."\n";  
		}
		
		//leio a brand
		if ($this->getBrandMarketplace($sku, false) == false) 
            return false;
		
		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1 )
            $this->prd['prazo_operacional_extra'] = 1;

		// busco a categoria 
        // $this->prd['categoria_nm'] = $this->getCategoryMarketplace($sku);
        if ($this->getCategoryMarketplace($sku) === false) {
			return false;
		}

		if ($this->prd['category_id'] == false){
			return false;	
		}
			
		
		return true;
	}	


	protected function montaArray($sku, $novo_produto = true, $vendas = 0) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		/*
		$description = substr(htmlspecialchars(strip_tags(str_replace("<br>"," \n",$this->prd['description'])), ENT_QUOTES, "utf-8"), 0, 3800);
		$description = str_replace("&amp;amp;", " ", $description);
		$description = str_replace("&amp;", " ", $description);
		$description = str_replace("&nbsp;", " ", $description);

		if (($description == '') || (trim(strip_tags($this->prd['description']), " \t\n\r\0\x0B\xC2\xA0")) == '')
			$description= substr(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8"), 0, 98);
		*/
		
        $brand_id = json_decode($this->prd['brand_id'])[0];
        $category_id = json_decode($this->prd['category_id'])[0];

        $brand_name = $this->model_brands->getBrandData($brand_id)['name'];
        $brand_name = substr($brand_name, 0, 255);
		
		$unity = 1; 
        $unity_arr = json_decode($this->prd['attribute_value_id'], true);
        if (is_array($unity_arr)) {
			$unity = $unity_arr[0];
		} 
        switch($unity)
        {
            case 2: $unity = 'Kg'; break;
            default: $unity = 'UN';
        }

        // $category_sellercenter = $this->model_categorias_marketplaces->getDataCompleteByCategoryId($category_id);

        // if(!empty($category_sellercenter[0]['nome']))
        //     $category_sellercenter = $category_sellercenter[0]['nome'];
        // else
        //     $category_sellercenter = $this->prd['categoryname'];

        $description = $this->prd['description'];
     	// $description = htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",$description)),"<br>"), ENT_QUOTES, "utf-8");
		
		$produto = array(			
			"name"			=> substr(strip_tags(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8")," \t\n\r\0\x0B\xC2\xA0"), 0, 255),
            "sku" 			=> $sku,
            "active"        => "enabled",
			"description" 	=> $description,
			"price" 		=> (float)$this->prd['price'], 
			"list_price"    => (float)$this->prd['list_price'],
            "qty"			=> (int)$this->prd['qty'],
            "ean"			=> $this->prd['EAN'],
            "sku_manufacturer" => "",
            "net_weight"  	=> (float)$this->prd['peso_liquido'],
            "gross_weight" 	=> (float)$this->prd['peso_bruto'],
            "width"			=> ($this->prd['largura'] < 11) ? 11 : (float)$this->prd['largura'],
            "height"		=> ($this->prd['altura'] < 2) ? 2 : (float)$this->prd['altura'],
            "depth"	    	=> ($this->prd['profundidade'] < 16) ? 16 : (float)$this->prd['profundidade'],
            "guarantee"	   	=> (int)$this->prd['garantia'],
            "ncm"   	   	=> $this->prd['NCM'],
            "origin"   	   	=> $this->prd['origin'],
            "unity"   	   	=> $unity,
            "manufacturer" 	=> $brand_name,
            "category"  	=> $this->prd['localcategoryname'],
            "extra_operating_time" => $this->prd['prazo_operacional_extra'],
            "images"        => $this->getProductImages($this->prd['image'], $this->pathImage, '', false), 
        );
		
		// Verifico se é catálogo para pegar a imagem do lugar certo
		if (!is_null($this->prd['product_catalog_id'])) 
			$pathImage = 'catalog_product_image';
		else 
			$pathImage = 'product_image';

        if (($this->prd['has_variants'] != "") && $novo_produto)
        {
			$used_skus = array();
			$types_variations = $types_variations_translated = array();

            $prd_vars = $this->model_products->getProductVariants($this->prd['id'], $this->prd['has_variants']);
            $types_variations = @explode(";", $this->prd['has_variants']);

            if(is_array($types_variations) && !empty($types_variations))
            {
                foreach($types_variations as $k => $v)
                {   
                    $types_variations_translated[$k] = $this->translateTypeVariation($v);
                }
            }

            $variation = array();
			foreach($prd_vars as $k => $value)
            {
                if($k === 'numvars')
                    continue;
				
				$vardir = '';
				$images_var = array();
				if (($this->pathImage == 'product_image')) {
					if (!is_null($value['image']) && trim($value['image'])!='')	{
						$vardir = '/'.$value['image'];
					}
					$images_var	= $this->getProductImages($this->prd['image'], $this->pathImage, $vardir, true); 
				} else {
					$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$value['variant'] );
					if ($var_cat) {
						$images_var	= $this->getProductImages($var_cat['image'], $this->pathImage, '', false); 
					}
				}
				$variation[$k]['images'] 	= $images_var;
				
				switch ($this->skuformat) {
					case 'store_skuoriginal' : // necessário para shophub
						$variation[$k]['sku']  = $this->prd['store_id'].'_'.$value['sku'];
						break;
					case 'default' : 
						$variation[$k]['sku']  = $sku.'-'.$value['variant'];
						break;
					default:
						$variation[$k]['sku']  = $sku.'-'.$value['variant'];
						break;
				} 
				if (in_array($value['sku'], $used_skus)) {
					$msg = "SKU da variação repetido ".$value['sku'];
					$this->log_data('batch',$log_name,$msg ,"E");
					$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
					return false;
				}
				$used_skus[] = $value['sku'];

                if ($value['status'] != 1) {
                    $value['qty'] = 0;
                }
                
                $variation[$k]['qty']   = $value['qty'];
                $variation[$k]['EAN']   = $value['EAN'];
                
                if(is_array($types_variations) && !empty($types_variations))
                {
                    foreach($types_variations as $type)
                    {                        
                        $variation[$k][$this->translateTypeVariation($type)] = $value[$type];
                    }
                }
			}

			$produto['types_variations'] = $types_variations_translated;
			$produto['product_variations'] = $variation;
		}

		$resp_json = json_encode($produto);


		if (!$resp_json)
        {
			// a descrição está com algum problema . tento reduzir... 
			$produto['name'] = substr(strip_tags(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8")," \t\n\r\0\x0B\xC2\xA0"),0,96);
			$produto['description'] = substr($description,0,3000);
			$resp_json = json_encode($produto);

			if (!$resp_json)
            {
				$msg = "Erro ao fazer o json do produto ".$this->prd['id']." ".print_r($produto,true).' json error = '.json_last_error_msg();
				var_dump($resp_json);
				echo $msg."\n";
				$this->log_data('batch', $log_name, $msg, "E");
				return false;
			}
		}
		
		echo print_r($resp_json, true)."\n";

		return array("product" => $produto);	
	}

	private function getProductImages($folder_ori, $path, $vardir = '', $variacao = false)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$folder = $folder_ori;
		if ($vardir != '') {
			$folder .= $vardir;
		} elseif ($variacao) {
			return array(); // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}

		$numft = 0;
		$imagesData = array();
		$onBucket = $this->prd["is_on_bucket"];
		$asset_image = 'assets/images/' . $path . '/' . $folder;

		// Caso não esteja no bucket, realiza o processo antigo de buscar no disco.
		if (!$onBucket) {
			$images = scandir(FCPATH . $asset_image);

			if (!$images) {
				return array();
			}
			if (count($images) <= 2) { // não achei nenhuma imagem
				if ($variacao) { // Mas é uma variação, retorna o array vazio
					return  array();
				}
				return array();
			}


			foreach ($images as $foto) {
				if (($foto != '.') && ($foto != '..') && ($foto != '')) {
					if (!is_dir(FCPATH . $asset_image . '/' . $foto)) {

						$image_url = base_url($asset_image . '/' . $foto);
						$image_url = str_replace('http://', 'https://', $image_url); // vtex só aceita https
						$imagesData[] = str_replace('conectala.tec.br', 'conectala.com.br', $image_url);
						$numft++;
					}
					if ($numft == 6) {
						break;
					}
				}
			}
		} else {
			// Busca as imagens diretamente atreladas a essa pasta (Sem incluir sub diretórios)
			$images = $this->bucket->getFinalObject('assets/images/' . $path . '/' . $folder);
			if (!$images['success']) {
				return array();
			}

			$images = $images['contents'];
			foreach ($images as $foto) {
				// Adiciona o URL da imagem.
				$imagesData[] = $foto['url'];
				$numft++;
				if ($numft == 6) {
					break;
				}
			}
		}
		return $imagesData;
	}
	
    
    function pegaCamposMKTdaMinhaCategoria($idcat, $int_to, $idprd = null)
    {
        $result = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $idcat);
        $idCatML = ($result) ? $result['category_marketplace_id'] : null;
        $enriched = false;
        if ($idprd) {
            $productCategoryMkt = $this->model_categorias_marketplaces->getProductCategoryMkt($idprd, $int_to);
            if ($productCategoryMkt) {
                $idCatML = $productCategoryMkt['category_mkt_id'];
                $enriched = true;
            }
        }
        $category_mkt = $this->model_categorias_marketplaces->getCategoryByMarketplace($int_to, $idCatML);
        $result = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCatML, $int_to);

        return [$result, $category_mkt, $enriched];
    }


    private function translateTypeVariation($type = false)
    {
        if(!$type)
            return false;

        $type_english = strtolower($type);

        switch($type_english)
        {
            case 'tamanho':     $type_english = 'size';     break;
            case 'cor':         $type_english = 'color';    break;
            default:            $type_english = 'voltage';
        }

        return $type_english;
    }


	protected function Http($url, $method = 'GET', $data = null, $prd_id = null, $int_to = null, $function = null )
    {
        $this->getkeys();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeader($this->api_keys));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE')
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');        

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		if ($this->responseCode == 429) 
        {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->Http($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

		if ($this->responseCode == 504)
        {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->Http($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

        if ($this->responseCode == 503)
        {
		    $this->log("Site com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->Http($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

		if (!is_null($prd_id)) 
        {
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
    

    protected function updateLastPost($prd, $variant = null) 
	{
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];

		if ($prd['EAN'] == '')
        {
			if ($prd['is_kit'] == 1)
				$ean ='IS_KIT'.$prd['id'];
			else
                $ean = $this->int_to.'_EAN'.$prd['id']; 

			if (!is_null($variant_num))
				$ean = $ean."V".$variant_num;
		}

		$skulocal = $this->prd_to_integration['skubling']; 
		if (!is_null($variant_num)) {
			switch ($this->skuformat) {
				case 'store_skuoriginal' :  // necessário para shophub
					$skulocal = $this->prd['store_id'].'_'.$variant['sku'];
					break;
				case 'default' : 
					$skulocal = $skulocal.'-'.$variant_num; 
					break;
				default:
					$skulocal = $skulocal.'-'.$variant_num; 
					break;
			} 
		}

        $skumkt = (empty($this->prd_to_integration['skumkt'])) ? $skulocal : $this->prd_to_integration['skumkt'];

		$data = array(
    		'int_to' 					=> $this->int_to,
    		'prd_id' 					=> $prd['id'],
    		'variant' 					=> $variant_num,
    		'company_id' 				=> $prd['company_id'],
    		'store_id' 					=> $prd['store_id'], 
    		'EAN' 						=> $ean,
    		'price' 					=> $prd['promotional_price'],
    		'list_price' 				=> $prd['list_price'],
    		'qty' 						=> $prd['qty'],
    		'qty_total' 				=> $prd['qty_original'],
    		'sku' 						=> $prd['sku'],
    		'skulocal' 					=> $skulocal,
    		'skumkt' 					=> $skumkt,     
    		'date_last_sent'			=> $this->dateLastInt,
    		'tipo_volume_codigo' 		=> $prd['tipovolumecodigo'], 
    		'width' 					=> $prd['largura'],
    		'height' 					=> $prd['altura'],
    		'length' 					=> $prd['profundidade'],
    		'gross_weight' 				=> $prd['peso_bruto'],
    		'crossdocking' 				=> (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'], 
    		'zipcode' 					=> preg_replace('/\D/', '', $this->store['zipcode']), 
    		'CNPJ' 						=> preg_replace('/\D/', '', $this->store['CNPJ']),
    		'freight_seller' 			=> $this->store['freight_seller'],
			'freight_seller_end_point' 	=> $this->store['freight_seller_end_point'],
			'freight_seller_type' 		=> $this->store['freight_seller_type'],
    	);

        $data = $this->formatFieldsUltEnvio($data);
	
		//var_dump($data);
		$savedUltEnvio = $this->model_sellercenter_last_post->createIfNotExist($prd['id'], $this->int_to, $data, $variant_num); 
	
		if (!$savedUltEnvio)
        {
            $notice = "Falha ao tentar gravar dados na tabela sellercenter_last_post.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        }
		
	}

	protected function changePriceQty($sku, $skumkt)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		if ($this->prd['has_variants'] != "") 
        {
            $tipos = explode(";",$this->prd['has_variants']);
            $variation_attributes = array();
			$variations = array();

			foreach($this->variants as $variant) 
            {
				if (isset($variant['sku'])) 
                {
					switch ($this->skuformat) {
						case 'store_skuoriginal' :  // necessário para shophub
							$sku = $this->prd['store_id'].'_'.$variant['sku'];
							break;
						case 'default' : 
							$sku = $skumkt.'-'.$variant['variant']; 
							break;
						default:
						$sku = $skumkt.'-'.$variant['variant'];
							break;
					}

                    if ($variant['status'] != 1) {
                        echo "Variação $variant[variant] do produto {$this->prd['id']} inativo.\n";
                        $variant['qty'] = 0;
                    }

					echo "Alterando Variação: Estoque id:".$this->prd['id']." ".$sku." estoque:".$variant['qty']." Preço: ".$variant['promotional_price']."\n";
					
					$product = Array (
						'variation' => array(
						    "qty" 	=> ceil($variant['qty']), 
						    'price' => (float)$variant['promotional_price'], 
						    'list_price' => (float)$variant['list_price'],
						),
					);

					$url = $this->api_url.'Variations/sku/'.$skumkt.'/'.$sku;
					echo "url =".$url."\n"; 
					$json_data = json_encode($product);
 
					$return = $this->Http($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque Variacao '.$variant['variant']);

					if ($this->responseCode == 404) {
						echo "Hoje algum erro no cadastro das variações no sellercenter. Movendo para a Lixeira\n";
						$url = $this->api_url.'Products/trash/'.$skumkt;
						echo "url =".$url."\n"; 
						$json_data = null;
	
						$return = $this->Http($url, 'DELETE', $json_data, $this->prd['id'], $this->int_to, 'Removendo o produto do marketplace');
						$this->RemoveFromQueue();

						die;

					}

					if ($this->responseCode !="200") 
                    {  
						echo "Erro url:".$url." httpcode=".$this->responseCode ." RESPOSTA: ".print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data, true)." \n"; 
						$msg =  "ERRO ao alterar estoque variação ".$sku." url:".$url." - httpcode: ".$this->responseCode." RESPOSTA: ".print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true);
						$this->log_data('batch',$log_name,$msg ,"E");
						$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");

                        if ($this->responseCode >= 500) {
                            die;
                        }
						return false;
					}
					// $this->cleanPrdToIntegration($variant['variant']);
				}
			}	
		}
		else
        {
			echo "Alterando Produto Simples: Estoque id:".$this->prd['id']." ".$skumkt." estoque:".$this->prd['qty']." preço: ".$this->prd['promotional_price']."\n";
			
			$product = Array (
				'product' => array(
    				"price" => $this->prd['promotional_price'],
    				"list_price" => $this->prd['list_price'],
				    "qty" => $this->prd['qty']
				) 
			);

			// Caso não apresente preço de, seta como o preço por.
			if ($product["product"]["list_price"] == null) {
				$product["product"]["list_price"] = $this->prd['promotional_price'];
			}

			$url = $this->api_url.'Products/'.$skumkt;
			$json_data = json_encode($product);
            
			$return = $this->Http($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque');

			if ($this->responseCode != "200") {
				$msg = 'Erro ao alterar preço ou estoque.';
				$resp = json_decode($this->result, true);

				// Extrai a mensagem da response.
				$newMsg = $this->getMessage($resp);
				if ($newMsg != null) {
					$msg = $newMsg;
				}

				echo "Erro url:" . $url . ". httpcode=" . $this->responseCode . " RESPOSTA: " . print_r($this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true) . " \n";
				$this->errorTransformation($this->prd['id'], $sku, $msg, "Preparação para o envio");
				$this->log_data('batch', $log_name, "ERRO ao alterar preço e estoque " . $skumkt . " url:" . $url . " - httpcode: " . $this->responseCode . " RESPOSTA: " . print_r($this->result, true) . ' DADOS ENVIADOS:' . print_r($json_data, true), "E");
				return false;
			}
            
			if ($this->hasAuction) {
				$update_last_post = $this->model_products_winners->updateLastPostValues($skumkt, $this->prd['price'], $this->prd['qty']);
			}
            
		}	

        return true;
	} 


    private function getHttpHeader($api_keys) 
    {
        if (empty($api_keys))
            return false;
            
        $keys = array();

        foreach ($api_keys as $k => $v)
        {
            if ($k != 'api_url' && $k != 'int_to')
                $keys[] = $k.':'.$v;
        }

        return $keys;        
    }


    function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->setInactivate();
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		$this->prd['qty'] = 0; // zero a quantidade do produto
		if ($this->prd['has_variants'] != '') {
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
			// $this->disableB2W();
		}
	}

	
	function hasShipCompany()
    {
    	return true;
    }

	function auctionVerify($new_product) {
		

		if ($new_product['status'] != 1) { // produto está inativo. 
			echo "Produto ".$new_product['id']." está inativo \n"; 
			return false; 
		} 
		if ($new_product['situacao'] != 2) { // produto está incompleto 
			echo "Produto ".$new_product['id']." está incompleto \n"; 
			return false; 
		} 
		if ($new_product['qty'] <= $this->reserve_to_b2W ) {  // sem estoque
			echo "Produto ".$new_product['id']." sem estoque \n"; 
			return false; 
		}
		$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to, $new_product['id']);
		if (!$prd_to_integration) {
			echo "Produto ".$new_product['id']." não possui integração para ".$this->int_to."\n"; 
			return false; 
		}
		if ($prd_to_integration['status'] == 0){
			echo "Produto ".$new_product['id']." com integração desligada para ".$this->int_to."\n"; 
			return false; 
		}
		if ($prd_to_integration['approved'] != '1'){
			echo "Produto ".$new_product['id']." ainda não foi aprovado para ".$this->int_to."\n"; 
			return false; 
		}
		$store = $this->model_stores->getStoresData($new_product['store_id']);
		if ($store['active'] != '1') {
			echo  "A loja do produto ".$new_product['id']." inativa\n"; 
			return false; 
		}
		
		if ($this->mandatory_category) { // verifico se tem a categoria e se está linkado ao marketplace 
			$categoryId = json_decode($new_product['category_id']);
			if (is_array($categoryId)) {
				$categoryId = $categoryId[0];
			}
   			$category   = $this->model_category->getCategoryData($categoryId);
			if (!$category) {
				echo " novo ganhador ainda não tem categoria \n";
				return false;
			}	 
			// pego a categoria do marketplace - copiado do Mirakl
			//$result= $this->model_categorias_marketplaces->getCategoryMktplace($this->int_to,$categoryId);
			//if (!$result) {
		    //		echo " novo ganhador ainda não tem categoria configurada no marketplace \n";
			//	return false;
			//}
			//$new_product['categoria_mkt_id'] = $result['category_marketplace_id'];
		}
		if ($this->mandatory_attributes) { // Verifico se tem os atributos obrigatórios - copiado do Mirakl
			//$seller_atributte = $this->getSellerAtributesNew('', $new_product, null,  false); 
			//if ($seller_atributte == false) {
			//	echo " novo ganhador tem atributos obrigatórios faltando \n";
			//	return false; 
			//}
		}
		
		
		// Tudo Ok
		return true; 
	}

	function runAuction($auction_status, $sku) 
	{
		echo "Rodando o Leilão!\n";

		if (!$auction_status) {
			echo "Nunca houve um campeão. Então ganhei!";
			// Me gravo como Ganhador do Leilão.... 
			$winner_data = array(
	            'int_to'                => $this->int_to,
	            'ean'                   => $this->prd['EANAUCTION'],
	            'current_store_id'      => $this->prd['store_id'],
	            'current_product_id'    => $this->prd['id'],
	            'store_id_1'            => $this->prd['store_id'],
	            'store_id_2'            => $this->prd['store_id'],
	            'product_id_1'          => $this->prd['id'],
	            'product_id_2'          => $this->prd['id'], 
	            'first_winner'          => $this->prd['id']
	        );
	        $winner = $this->model_products_winners->saveNewWinner($winner_data);
			return true;   // continuo o processamento 
		}

        // Pego a lista de produtos já ordenado pelo ganhador deste leilão  
        echo " EAN ==". $this->prd['EANAUCTION']."\n";
		$winner = $this->model_products_winners->getProducts($this->prd['EANAUCTION']);
		if (!$winner) {
			echo " não achei um novo ganhador, mantem o antigo\n";
			return true; 
		}
		
		// verifico se sou o atual ganhador 
		if ($auction_status['current_product_id'] == $this->prd['id']) {
			if ($winner['id'] == $this->prd['id']) { //Ganhei novamente ?
				echo "Continuo como ganhador do leilão.\n";
				return true; // continuo o processamento 
			}
			else {
				echo "Sou o atual mas perdi\n";
				$retorno = true;  // como sou o atual, continuo o processo, amanhã outro me substituirá. 
			} 
		}
		else {
			// verifico se ganhei já que não sou o atual. 
			if ($winner['id'] != $this->prd['id']) {
			    echo "Não ganhei\n";

				$this->updatePrdToIntegration($sku,11); 
				
				return false; // Não continua o processamento pois não ganhei. 
			}
			else {
				echo "Não sou o atual mas ganhei\n";
				$retorno = false; // Não continua o processamento
			}	
		}
		var_dump ($winner);
		echo " Verificando se o ganhador ".$winner['id']." realmente pode ganhar\n";
		
        $new_product = $this->model_products->getProductData(0, $winner['id']);
        $old_product = $this->model_products->getProductData(0, $auction_status['current_product_id']);
		
		$status_int = 1;
        //se o que ta no ar tem variacao, nao aceitar outros.
        //se o que ta no ar nao tem, mas o novo tem, nao aceitar
        if( ($old_product['has_variants'] != '') || ($old_product['has_variants'] == '' && $new_product['has_variants'] != '') ) {
        	echo " ter ou não ter variação não bate\n";
        	$status_int = 14;
        }
                           
        if($new_product['has_variants'] != $old_product['has_variants']) { // as variantes não batem, então mantenho o atual vencedor
        	echo " as variações não bate\n ";
        	$status_int = 14;
        }
        
		if($new_product['brand_id'] != $old_product['brand_id']) { // as marcas não batem, então mantenho o atual vencedor 
        	echo " a marca não bate\n ";
        	$status_int = 14;
        }
		
		/* tenho q verificar se o novo ganhador está completo para este marketplace */
		
		if (!$this->auctionVerify($new_product)) { // O novo produto tem algum problema para este marketplace?
        	$status_int = 14;
        }
		
		if ($status_int == 1) {
			echo "Novo ganhador {$winner['id']} \n";
			$new_winner = $this->model_products_winners->updateWinner($this->prd['EANAUCTION'], $winner, $this->int_to);
		}
		else {
			echo "Ganhador incompatível\n";
		}
		
		if ($winner['id'] != $this->prd['id']) {
			$status_int = 14;
		}
		// atualizo o prd_to_integration 

		$this->updatePrdToIntegration($sku,$status_int); 
		return $retorno;
		
	}

	function undoAuction()
	{
		if ($this->hasAuction) {
			$this->model_products_winners->remove($this->prd['id'], $this->int_to, $this->prd['EANAUCTION']);
		}
		return false;  // sempre retorna falso
	}
    
	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->model_sellercenter_last_post->getData(null, $procura);
	}

	/**
	 * Recupera a mensagem correta da response.
	 * Trata casos em que a mensagem é enviada como um array.
	 * @param mixed $resp Valor contendo ou uma string ou um array associativo recebido via API.
	 * @return string Retorna a mensagem de erro ou o array como uma string.
	 */
	private function getMessage($resp)
	{
		$msg = null;

		// Verifica se possui a mensagem de erro.
		if (array_key_exists('message', $resp) && is_string($resp['message']) && strlen($resp['message']) > 0) {
			// Caso seja uma string, apenas salva como a mensagem.
			$msg = $resp['message'];
		} else if (array_key_exists('message', $resp) && is_array($resp['message'])) {
			// Em alguns casos a mensagem é retornada como um array, realizo o tratamento destes casos. 
			// Normalmente a mensagem vem dentro do campo data, extrai e seta como a mensagem.
			if (array_key_exists('data', $resp['message'])) {
				$msg = $resp['message']['data'];
			} else {
				// É um array sem campo ['data'], converte para json e salva como a mensagem.
				$as_json = json_encode($resp['message']);

				// Verifica se conseguiu converter para json e seta a mensagem.
				if ($as_json) {
					$msg = $as_json;
				}
			}
		}
		return $msg;
	}
}