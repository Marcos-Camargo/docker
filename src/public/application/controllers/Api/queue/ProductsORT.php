<?php 
/* 
* recebe a requisição e cadastra / alterara /inativa no SellerCenter
*/


require APPPATH . "controllers/Api/queue/ProductsConectala.php";


class ProductsORT extends ProductsConectala
{
    var $inicio;
	var $auth_data;
    var $int_to_principal;
    var $int_to = 'ORT';
	var $int_to_SC = 'Ortobom';	
	var $integration;
    var $variants;
    protected $api_keys;
    protected $api_url = 'https://ortobomteste.conectala.com.br/app/Api/V1/';


    public function __construct() 
    {
        parent::__construct();
	   
	    $this->load->model('model_blingultenvio');
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		$this->load->model('model_marketplace_prd_variants'); 
		// $this->load->model('model_ml_ult_envio'); 	
		$this->load->model('model_settings'); 	
		$this->load->model('model_integration_last_post'); 
        $this->load->model('model_integrations');

        $this->load->model('model_products');
        $this->load->model('model_products_winners');
        $this->load->model('model_errors_transformation');
    }

	
	public function index_post() 
    {
    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        // $this->getkeys();

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
	

    protected function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";

		$skumkt = $this->prd_to_integration['skumkt'];
		$sku    = $this->prd_to_integration['skubling'];
        $ean    = $this->prd['EAN'];

        if (empty($ean))
        {
            echo $msg = "EAN inexistente, não é possível proceder sem o código EAN \n";
            $this->errorTransformation($this->prd['id'], $this->prd['sku'], $msg, "Inserção de produto MicroLeilão");
            return false;
        }
		
        $auction_status = $this->model_products_winners->getWinner($ean, $this->prd_to_integration['int_to']);

		$integration_last_post = $this->model_integration_last_post->getDataByIntToPrdIdVariant($this->prd_to_integration['int_to'], $this->prd['id']);

        $winner = $this->model_products_winners->getProducts($ean);

        if (is_array($auction_status) && !empty($auction_status) && !empty($winner))
        {            
            if (!empty($winner) && $winner['store_id'] != $auction_status['store_id_1'])
            {
                //conferir se has variations é o mesmo
                $new_product = $this->model_products->getProductData(0, $winner['id']);
                $old_product = $this->model_products->getProductData(0, $auction_status['store_id_1']);

                if($new_product['has_variants'] != $old_product['has_variants'])
                    return false;

                $new_winner = $this->model_products_winners->updateWinner($ean, $winner, $this->int_to);
            }
        }
        else
        {
            //este eh o primeiro cadastro deste ean, procede para a inclusao
            if ((is_null($sku)) || (!$integration_last_post))
            { 
                $sku = $ean.$this->prd_to_integration['int_to'];
                $this->model_integrations->updatePrdToIntegration(array('skumkt' => $sku, 'skubling' => $sku), $this->prd_to_integration['id']);
                $this->prd_to_integration['skubling'] = $sku;
             }

		    // limpa os erros de transformação existentes da fase de preparação para envio
		    $this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'], $this->prd_to_integration['int_to'], "Preparação para envio MicroLeilao");

            // pego informações adicionais como preço, estoque e marca .
            if ($this->prepareProduct($sku) == false)
                return false;
    
            // Monto o Array para enviar para o Seller Center
            $produto = $this->montaArray($sku, true, 0);

            if ($produto == false)
                return false;
            
            echo "Incluindo o produto ".$this->prd['id']." ".$this->prd['name']."\n";

            $url = $this->api_url.'Products';

            $return = $this->SCHttp($url, 'POST', json_encode($produto), $this->prd['id'], $this->prd_to_integration['int_to'], 'Novo produto');

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
                $this->errorTransformation($this->prd['id'], $skumkt, $msg, "Produto de MicroLeilao Não inserido");
                return false;
            }

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

            if(!empty($attributes_object))
            {
                $attributes_object = array('attribute' => $attributes_object);
                $attributes_object = json_encode($attributes_object, JSON_UNESCAPED_UNICODE);

                $url = $this->api_url.'Products/attributes/'.$sku;

                $return = $this->SCHttp($url, 'PUT', $attributes_object);
            }

            if ($this->prd['has_variants'] != "") 
            {
                foreach ($this->variants as $variant)
                {
                    $prd = $this->prd;
                    $prd['sku'] = $variant['sku'];
                    $prd['qty'] = $variant['qty'];
                    $prd['EAN'] = ($variant['EAN']!='') ? $variant['EAN'] : $this->prd['EAN'];		
                    $this->updateSCLastPost($prd, $variant);
                }
            }
            else
            {
                $this->updateSCLastPost($this->prd, null);
            }

            $winner_data = array(
                'int_to'                => $this->int_to,
                'ean'                   => $ean,
                'current_store_id'      => $this->prd['store_id'],
                'current_product_id'    => $this->prd['id'],
                'store_id_1'            => $this->prd['store_id'],
                'store_id_2'            => $this->prd['store_id'],
                'product_id_1'          => $this->prd['id'],
                'product_id_2'          => $this->prd['id']
            );

            $winner = $this->model_products_winners->saveNewWinner($winner_data);

            $this->model_products->updateProductIntegrationStatus($this->prd_to_integration['id'], 2);

            return true;
        }
	}
    
	
	protected function updateProduct()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Update"."\n";
	
        $skumkt = $this->prd_to_integration['skumkt'];
		$sku    = $this->prd_to_integration['skubling'];
        $ean    = $this->prd_to_integration['ean'] = $this->prd['EAN'];

        // verifico se é ganhador do leilão atual 
        $auction_status = $this->model_products_winners->getWinner($ean, $this->prd_to_integration['int_to']);

        $current_store_id   = $auction_status['current_store_id'];
        $current_product_id = $auction_status['current_product_id'];

        if ($this->prd_to_integration['store_id'] == $current_store_id && $this->prd_to_integration['prd_id'] == $current_product_id)
        {
            // limpa os erros de transformação existentes da fase de preparação para envio
            $this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'], $this->int_to, "Preparação para Atualização de Preço/Estoque");
            
            // pego informações adicionais como preço, estoque e marca .
            if ($this->prepareProduct($sku) == false) 
                return false;
            
            // atualiza preço e estoque primeiro antes de alterar o resto do produto.
            if ($this->changeSCPriceQty($skumkt) == false)
            {
                return false;
            }
            else
            {
                $sql = "UPDATE prd_to_integration SET status_int = ? WHERE skumkt = ?";
			    $this->db->query($sql, array(1, $skumkt));
                $this->model_products->updateProductIntegrationStatus($this->prd_to_integration['id'], 2);
                return true; //braun -> remover esse return se houver edição do produto como nas linhas abaixo. discussao pendente
            }


            //deve alterar somente preço e estoque, como foi feito na ultima linha
            //ou deve alterar todo o restante do produto, como nas proximas linhas



            // Monto o Array para enviar para o Seller center
            /* $produto = $this->montaArray($sku, true, 0);

            if ($produto == false)
                return false;
            
            echo "Alterando o produto ".$this->prd['id']." ".$this->prd['name']."\n";

            $url = $this->api_url.$skumkt;

            $return = $this->SCHttp($url, 'PUT', json_encode($produto), $this->prd['id'], $this->int_to, 'Alterando produto');

            if ($this->responseCode != 200)
            { 
                // Deu um erro que não consigo tratar
                echo " Erro URL: ". $url. "\n"; 
                echo " httpcode: ".$this->responseCode."\n"; 
                echo " RESPOSTA: ".print_r($this->result,true)." \n"; 
                echo " ENVIADO : ".print_r($produto,true)." \n"; 
                $this->log_data('batch', $log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA: ".print_r($this->result,true).'  
                                ENVIADO:'.print_r($produto,true), "E");
                return false;
            }

            if ($this->prd['has_variants'] != "")
            {
                foreach($this->variants as $variant)
                {
                    $prd = $this->prd;
                    $prd['sku'] = $variant['sku'];
                    $prd['qty'] = $variant['qty'];
                    $prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;		
                    // $this->updateBlingUltEnvio($prd, $variant);
                    $this->updateNMLastPost($prd, $variant);
                }
            }
            else
            {
                // $this->updateBlingUltEnvio($this->prd, null);
                $this->updateNMLastPost($this->prd, null);
            } */

        }
        else
        {
            //nao é o vencedor de leilao, nao ativa api
            return false;
        }

        return true;
    }


	protected function getkeys()
    {
		$this->getIntegration(); 
		$this->auth_data = $this->api_keys = json_decode($this->integration_main['auth_data']);
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
	

	public function getCategoryMarketplace($skumkt, $int_to = '', $mandatory_category = true) 
	// public function getCategoryMarketplace($skumkt, $int_to = '')
	{
		if 	($int_to == '')
            $int_to=$this->int_to;
			
		$categoryId = json_decode($this->prd['category_id']);

		if (is_array($categoryId))
			$categoryId = $categoryId[0];
		
   		$category   = $this->model_category->getCategoryData($categoryId);

		if (!$category)
        {
			$msg= 'Produto sem categoria.';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'], $skumkt, $msg, "Preparação para o envio");
			return false;
		}

		$this->prd['categoryname'] = $category['name']; 
		
		return $categoryId;
	}

	
	protected function prepareProduct($sku)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Preparando produto\n";
		
		// busco a categoria 
		$this->prd['categoria_id'] = $this->getCategoryMarketplace($sku);

		if ($this->prd['category_id'] == false)
			return false;		
	
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];

		if ((int)$this->prd['qty'] < 0)
			$this->prd['qty']  = 0;
		
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); 
		
		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);

		if ($this->prd['promotional_price'] > $this->prd['price'] )
			$this->prd['price'] = $this->prd['promotional_price']; 
		
		// se tiver Variação,  acerto o estoque de cada variação
    	if ($this->prd['has_variants'] != "")
        {    		
            echo 'visualizando variações';
            if (!empty($this->variants))
                print_r($this->variants);

			foreach ($this->variants as $key => $variant)
            {
				$this->variants[$key]['qty_original'] = $variant['qty'];

				if ((int)$this->variants[$key]['qty'] < 0)
					$this->variants[$key]['qty'] = 0;
				
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100);

				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0))
					$this->variants[$key]['price'] = $this->prd['price'];
				
                $this->variants[$key]['promotional_price'] = $this->getPrice($variant);
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
		
		return true;
	}	


	protected function montaArray($sku, $novo_produto = true, $vendas = 0) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		$description = substr(htmlspecialchars(strip_tags(str_replace("<br>"," \n",$this->prd['description'])), ENT_QUOTES, "utf-8"), 0, 3800);
		$description = str_replace("&amp;amp;", " ", $description);
		$description = str_replace("&amp;", " ", $description);
		$description = str_replace("&nbsp;", " ", $description);

		if (($description == '') || (trim(strip_tags($this->prd['description']), " \t\n\r\0\x0B\xC2\xA0")) == '')
			$description= substr(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8"), 0, 98);

        $brand_id = json_decode($this->prd['brand_id'])[0];
        $category_id = json_decode($this->prd['category_id'])[0];

        $brand_name = $this->model_brands->getBrandData($brand_id)['name'];
        $brand_name = substr($brand_name, 0, 255);
        
        $unity = json_decode($this->prd['attribute_value_id'], true)[0];
        
        switch($unity)
        {
            case 2: $unity = 'Kg'; break;
            default: $unity = 'UN';
        }

        $category_sellercenter = $this->model_categorias_marketplaces->getDataCompleteByCategoryId($category_id);

        $produto = array(			
			"name"			=> substr(strip_tags(htmlspecialchars($this->prd['name'], ENT_QUOTES, "utf-8")," \t\n\r\0\x0B\xC2\xA0"), 0, 255),
            "sku" 			=> $sku,
            "active"        => "enabled",
			"description" 	=> $description,
			"price" 		=> (float)$this->prd['price'], 
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
            "category"  	=> $category_sellercenter,
            "extra_operating_time" => $this->prd['prazo_operacional_extra'],
            // braun -> imagem engessada pois api nao aceita localhost
            "images"        => [$this->prd["principal_image"]]            
            // "images"        => ["https://falcoaria.com.br/app/assets/images/product_image/E6B06B6B-60E8-F3D9-28AD-AA4C93CF2033/16224695012991.jpg"]
        );
		
		// Verifico se é catálogo para pegar a imagem do lugar certo
		if (!is_null($this->prd['product_catalog_id'])) 
			$pathImage = 'catalog_product_image';
		else 
			$pathImage = 'product_image';

        if ($this->prd['has_variants'] != "")
        {
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

			foreach($prd_vars as $k => $value)
            {
                if($k === 'numvars')
                    continue;

                $variation_images = [];

                $image_dir = @scandir(FCPATH . 'assets/images/'.$pathImage.'/'.$this->prd['image'].'/'. $value['image']);	

				foreach($image_dir as $image) 
                {
					if (($image != ".") && ($image != "..")) 
                    {
						if(!is_dir(FCPATH . 'assets/images/'.$pathImage.'/' . $this->prd['image'].'/'.$value['image'].'/'.$image)) 
							$variation_images[] = str_replace('\\','/', base_url('assets/images/'.$pathImage.'/' . $this->prd['image'].'/'. $image));
					} 
				}

                $variation[$k]['sku']       = $value['sku'];
                $variation[$k]['qty']       = $value['qty'];
                $variation[$k]['EAN']       = $value['EAN'];
                $variation[$k]['images']    = $variation_images;

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
				return false;;
			}
		}
		
		echo print_r($resp_json, true)."\n";

		return array("product" => $produto);	
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


	protected function SCHttp($url, $method = 'GET', $data = null, $prd_id = null, $int_to = null, $function = null )
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
			$this->SCHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

		if ($this->responseCode == 504)
        {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->SCHttp($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}

        if ($this->responseCode == 503)
        {
		    $this->log("Site com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->SCHttp($url, $method, $data, $prd_id, $int_to, $function);
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
    

    protected function updateSCLastPost($prd, $variant = null) 
	{
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];

		if ($prd['EAN'] == '')
        {
			if ($prd['is_kit'] == 1)
				$ean ='IS_KIT'.$prd['id'];
			else
                $ean ='EAN'.$prd['id']; 

			if (!is_null($variant_num))
				$ean = $ean."V".$variant_num;
		}

		$skulocal = $this->prd_to_integration['skubling']; 

		if (!is_null($variant_num))
			$skulocal = $skulocal.'-'.$variant_num; 
		
    	$data = array(
    		'int_to' => $this->int_to,
    		'company_id' => intval($prd['company_id']),
            'EAN' => $ean,            
            'prd_id' => intval($prd['id']),
            'price' => $prd['price'],
            'qty' => $prd['qty'],
            'sku' => $prd['sku'],
            'reputacao' => 100,
            'NVL' => 0,
            'data_ult_envio' => $this->dateLastInt,
            'skulocal' => $skulocal,
            'skumkt' => (empty($this->prd_to_integration['skumkt'])) ? $skulocal : $this->prd_to_integration['skumkt'],     
            'tipo_volume_codigo' => $prd['tipovolumecodigo'], 
            'qty_atual' => $prd['qty_original'],
            'largura' => $prd['largura'],
    		'altura' => $prd['altura'],
    		'profundidade' => $prd['profundidade'],
            'peso_bruto' => $prd['peso_bruto'],
            'store_id' => $prd['store_id'], 
            'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'], 
            'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
            'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
            'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
    		'variant' => $variant_num
    	);

        $data = $this->formatFieldsUltEnvio($data);
		
		$savedUltEnvio = $this->model_integration_last_post->createIfNotExist($prd['id'], $this->int_to, $data); 

		if (!$savedUltEnvio)
        {
            $notice = "Falha ao tentar gravar dados na tabela ml_ult_envio.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
			die;
        } 
	}


	protected function changeSCPriceQty($skumkt)
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
					$sku = $skumkt.'-'.$variant['variant'];
					echo "Variação: Estoque id:".$this->prd['id']." ".$sku." estoque:".$variant['qty']."\n";
					
					$product = Array (
						'variation' => array(
						    "qty" => ceil($variant['qty'])
						),
						'specifications' => array(
							array (
								'key' => 'price',
								'value'=> $variant['price']
							), 
							array (
								'key' => 'promotional_price',
								'value'=> $variant['promotional_price']
							),
							
						), 
					);

					$url = $this->api_url.$sku;

					$json_data = json_encode($product);

					$return = $this->SCHttp($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque Variacao '.$variant['variant']);

					if ($this->responseCode !="200") 
                    {  
						echo "Erro url:".$url." httpcode=".$this->responseCode ." RESPOSTA: ".print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data, true)." \n"; 
						$this->log_data('batch',$log_name, "ERRO ao alterar estoque variação ".$sku." url:".$url." - httpcode: ".$this->responseCode." RESPOSTA: ".print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
						return false;
					}
				}
			}	
		}
		else
        {
			echo "Variação: Estoque id:".$this->prd['id']." ".$skumkt." estoque:".$this->prd['qty']. "\n";
			
			$product = Array (
				'product' => array(
    				"price" => $this->prd['price'],
				    "qty" => $this->prd['qty']
				) 
			);

			$url = $this->api_url.'Products/'.$skumkt;
			
			$json_data = json_encode($product);
            
			$return = $this->SCHttp($url, 'PUT', $json_data, $this->prd['id'], $this->int_to, 'Atualização Preço e Estoque');

			if ($this->responseCode != "200")
            {  
				echo "Erro url:".$url.". httpcode=".$this->responseCode ." RESPOSTA: ".print_r( $this->result ,true).' DADOS ENVIADOS:'.print_r($json_data, true)." \n"; 
				$this->log_data('batch',$log_name, "ERRO ao alterar estoque ".$skumkt." url:".$url." - httpcode: ".$this->responseCode." RESPOSTA: ".print_r($this->result ,true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				return false;
			}
            
            $update_last_post = $this->model_products_winners->updateLastPostValues($skumkt, $this->prd['price'], $this->prd['qty']);
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
			$this->disableB2W();
		}
	}

	
	function hasShipCompany()
    {
    	return true;
    }

    public function getLastPost($prd_id, $int_to)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";
		return $this->model_integration_last_post->getData(null, $procura);
	}   
}