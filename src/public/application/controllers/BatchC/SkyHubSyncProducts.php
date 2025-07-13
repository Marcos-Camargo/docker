<?php
/*
 
Sincroniza os produtos que foram alterados e que são ganhadores de leilão

*/   
 class SkyHubSyncProducts extends BatchBackground_Controller {
	
	var $int_to='B2W';
	var $apikey='';
	var $email='';
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_stores');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_products_catalog');
		
    }
	
	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setApikey($apikey) {
		$this->apikey = $apikey;
	}
	function getApikey() {
		return $this->apikey;
	}
	function setEmail($email) {
		$this->email = $email;
	}
	function getEmail() {
		return $this->email;
	}
	
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		echo " ESSA ROTINA ESTÁ DESATIVADA \n";
		die;
		
		/* faz o que o job precisa fazer */
		//$retorno = $this->limpaErrados();
		$this->getkeys(1,0);
		$retorno = $this->syncProducts();
		$retorno = $this->syncRemoveInativos();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setEmail($api_keys['email']);
	}
 	
    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		$estoqueIntTo=array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value']; 
			$estoqueIntTo[$key_param] = $parm['value'];
		}	
		
		//$categoria = $this->model_category->getCategoryData();
		$int_date_time = date('Y-m-d H:i:s');
		
		$sql = "SELECT b.* FROM bling_ult_envio b INNER JOIN products p ON p.id= b.prd_id WHERE data_ult_envio < p.date_update AND int_to='". $this->getInt_to()."' ORDER BY int_to";
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
			// pego os dados do catálogo do produto se houver 
			if (!is_null($prd['product_catalog_id'])) {
				$prd_catalog = $this->model_products_catalog->getProductProductData($prd['product_catalog_id']); 
				$prd['name'] = $prd_catalog['name'];
				$prd['description'] = $prd_catalog['description'];
				$prd['EAN'] = $prd_catalog['EAN'];
				$prd['largura'] = $prd_catalog['width'];
				$prd['altura'] = $prd_catalog['height'];
				$prd['profundidade'] = $prd_catalog['length'];
				$prd['peso_bruto'] = $prd_catalog['gross_weight'];
				$prd['ref_id'] = $prd_catalog['ref_id']; 
				$prd['brand_code'] = $prd_catalog['brand_code'];
				$prd['brand_id'] = '["'.$prd_catalog['brand_id'].'"]'; 
				$prd['category_id'] = '["'.$prd_catalog['category_id'].'"]';
				$prd['image'] = $prd_catalog['image'];
			}
			
			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// acerto o preço do produto com o preço da promoção se tiver
			if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($prd['variant'])) {
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"B2W", $prd['variant']);
			}
			else
			{
				$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"B2W");
			}

			//$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
			// e ai vejo se tem campanha 
			// $prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'], $this->getInt_to());
			if ($prd['promotional_price'] > $prd['price'] ) {
				$prd['price'] = $prd['promotional_price']; 
			}
			$prd['price'] = round($prd['price'],2);
			$prd['promotional_price'] = round($prd['promotional_price'],2);
			
			if ($prd['is_kit']) {
				$prd['promotional_price'] = $prd['price']; 
				$productsKit = $this->model_products->getProductsKit($prd['id']);
				$original_price = 0; 
				foreach($productsKit as $productkit) {
					$original_price += $productkit['qty'] * $productkit['original_price'];
				}
				$prd['price'] = $original_price;
				echo " KIT ".$prd['id'].' preço de '.$prd['price'].' por '.$prd['promotional_price']."\n";  
			}
			
    		$sku = $row['skubling'];
			if (($prd['status']==2)  || ($prd['situacao'] == '1')) {
				echo 'Produto Inativo ou incompleto '.$prd['id'].' sku '.$row['skubling']." - pulando \n";
				$sql = "UPDATE prd_to_integration SET date_last_int = ? WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$prd['id'];
				$cmd = $this->db->query($sql, array($int_date_time));
				continue;
			}
			if ($prd['category_id'] == '[""]') {	
				$msg= 'Categoria não vinculada';
				$this->errorTransformation($prd['id'],$row['skubling'],$msg);
				$sql = "UPDATE prd_to_integration SET date_last_int = ? WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$prd['id'];
				$cmd = $this->db->query($sql, array($int_date_time));
				continue;
			}
			$ean = $prd['EAN']; 
			if ($ean == '') {
				if ($prd['is_kit'] == 0) {
					$ean = 'NO_EAN'.$prd['id'];
				}else {
					$ean = 'IS_KIT'.$prd['id'];
				}
			}
			else {
				$ean = substr('0000000000'.$prd['EAN'],-13);
			}

			if ($ean != $row['EAN']) {
				echo "Produto mudou de EAN ".$prd['id']." sku ".$row['skubling']." EAN prod=".$ean." EAN Bling=".$row['EAN']." - pulando \n";
				echo "Problema para o Leilao resolver\n";
				continue;
			}
			echo 'Processando produto Bling_ult_envio Id= '.$row['id'].' sku '.$row['skubling']."\n";
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$qty_salvo = $prd['qty'];
			$qty_atual = (int) $prd['qty'] * $estoqueIntTo[$row['int_to']] / 100; 
			$qty_atual = ceil($qty_atual); // arrendoda para cima 
			if  ((int)$prd['qty'] < 5) { // Mando só para a B2W se a quantidade for menor que 5. 
				$qty_atual = (int)$prd['qty'];
			}
			$prd['qty'] = $qty_atual;
			
			$retorno = $this->inserePrd($prd,$sku,$estoqueIntTo[$this->getInt_to()]);    
			
			if (!$retorno) {
				continue; 
			} else { 
				//$nprds = count($retorno['produtos']);
				
				// TROUXE PRA DENTRO DO SUCESSO
				$int_date_time = date('Y-m-d H:i:s');
				$sql = "UPDATE prd_to_integration SET status_int=2 , date_last_int = ? WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$row['prd_id'];
				$cmd = $this->db->query($sql, array($int_date_time));
				$xsku = $sku;
				
				// Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
				$sql = "SELECT category_id FROM products WHERE id = ".$row['prd_id'];
				$cmd = $this->db->query($sql);
				$category_id_array = $cmd->row_array();  //Category_id esta como caracter no products
				$cat_id = json_decode ( $category_id_array['category_id']);
				
				
				$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
						 WHERE id =".intval($cat_id[0]).")";
				$cmd = $this->db->query($sql);
				$lido = $cmd->row_array();
				$tipo_volume_codigo= $lido['codigo'];
				
				$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
				$loja  = $this->model_stores->getStoresData($prd['store_id']);
				
	        	$data = array(
	 				'id'=> $row['id'],       	
	        		'int_to' => $row['int_to'],
	        		'company_id' => $prd['company_id'], 
	        		'EAN' => $ean,
	        		'prd_id' => $row['prd_id'],
	        		'price' => $prd['promotional_price'],
	        		'qty' => $qty_salvo,
	        		'sku' => $prd['sku'],
	        		'reputacao' => $row['reputacao'],
	        		'NVL' => $row['NVL'],
	        		'mkt_store_id' => $row['mkt_store_id'],
	        		'data_ult_envio' => $int_date_time,
	        		'skubling' => $sku,
	        		'skumkt' => $sku,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $prd['qty'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id'], 
	        		'marca_int_bling' => $row['marca_int_bling'],
	        		'categoria_bling' => $row['categoria_bling'], 
	        		'crossdocking' => $crossdocking, 
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
					
	        	);
				//$insert = $this->db->replace('bling_ult_envio', $data);
				$insert = $this->model_blingultenvio->update($data, $row['id']);
			}
			
	    }
		echo " ------- Processo de envio de produtos terminou\n";
        return "PRODUCTS Synced with B2W";
    } 

	function inserePrd($prd,$skumkt,$estoqueIntTo) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$catP = json_decode($prd['category_id']);
		// pego a categoria 
		$categoria = $this->model_category->getCategoryData($catP);
		
		// Verifico se é catálogo para pegar a imagem do lugar certo
		if (!is_null($prd['product_catalog_id'])) {
			$pathImage = 'catalog_product_image';
		}
		else {
			$pathImage = 'product_image';
		}
		
		$brand_id = json_decode($prd['brand_id']);
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $this->db->query($sql, $brand_id);
		$brand = $query->row_array();

		$description = substr(htmlspecialchars(strip_tags(str_replace("<br>"," \n",$prd['description'])), ENT_QUOTES, "utf-8"),0,3800);
		$description = str_replace("&amp;amp;"," ",$description);
		$description = str_replace("&amp;"," ",$description);
		$description = str_replace("&nbsp;"," ",$description);
		if (($description=='') || (trim(strip_tags($prd['description'])," \t\n\r\0\x0B\xC2\xA0")) == ''){
			$description= substr(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8"),0,98);
		}
		$produto = array(
			"sku" 			=> $skumkt,
			"name"			=> substr(strip_tags(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8")," \t\n\r\0\x0B\xC2\xA0"),0,98),
			"description" 	=> $description,
			"status" 		=> "enabled",
			"price" 		=> (float)$prd['price'], 
			"promotional_price" => (float)$prd['promotional_price'],
			"weight"  		=> (float)$prd['peso_bruto'],
			"height"		=> (float)($prd['altura'] < 2) ? 2 : $prd['altura'],
			"width"			=> (float)($prd['largura'] < 11) ? 11 : $prd['largura'],
			"length"		=> (float)($prd['profundidade'] < 16) ? 16 : $prd['profundidade'],
			"qty"			=> (int)$prd['qty'],
			"brand"			=> substr($brand['name'],0,29), // limite da B2w
			"ean"			=> $prd['EAN'],
			"nbm"			=> $prd['NCM'],
			"categories"	=> Array(array(
				"code"			=> $categoria['id'],
				"name"			=> $categoria['name'],
			)),
			"specifications" => array(
				array(
					"key" => "CrossDocking",
					"value" => $prd['prazo_operacional_extra'],
				),
				array(
					"key" => "store_stock_cross_docking",
					"value" => $prd['prazo_operacional_extra'],
				),
				array(
					"key" => "Garantia",
					"value" => $prd['garantia'],
				),
			)
		);
		//echo "IMAGENS:".$prd['image']."\n";
		$imagens = array();
		if ($prd['image']!="") {
			$numft = 0;
			if (strpos("..".$prd['image'],"http")>0) {
				$fotos = explode(",", $prd['image']);	
				foreach($fotos as $foto) {
					$imagens[$numft++] = $foto;
					if ($numft==5) { // limite de 5 fotos na skyhub
						break;
					} 
				}
			} else {
				$fotos = scandir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image']);	
				foreach($fotos as $foto) {
					if (($foto!=".") && ($foto!="..")) {
						if(!is_dir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image'].'/'.$foto)) {
							$imagens[$numft++] = base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $foto);
						}
					}
					if ($numft==5) { // limite de 5 fotos na skyhub
						break;
					} 
				}
			}	
		}
		$produto['images'] = $imagens;
		
		// TRATAR VARIANTS		
		if ($prd['has_variants']!="") {
			$variations = array();
            $prd_vars = $this->model_products->getProductVariants($prd['id'],$prd['has_variants']);
            // var_dump($prd_vars);
            $tipos = explode(";",$prd['has_variants']);
            // var_dump($tipos);
            $variation_attributes = array();
			foreach($prd_vars as $value) {
				// var_dump($value);
			  	if (isset($value['sku'])) {
					$apelido = "";
					$specficiation = array();
					$i=0;
					foreach ($tipos as $z => $campo) {
						if ($apelido!="") {
							$apelido .= ";";
							//$SKU .= "-";
						}
						//$SKU .= $value[$campo];
						$specficiation[$i++] = array(
							"key" => $campo,
							"value" => $value[$campo]
						);
						if (!in_array($campo, $variation_attributes)) {
							$variation_attributes[] = $campo;
						}
					}
					$specficiation[$i++] = 
						array(
							"key" => "store_stock_cross_docking",
							"value" => $prd['prazo_operacional_extra'],
						);
					if ($prd['qty'] < 5) {
						$qty_atual = (int)$value['qty'];  // para B2W manda todos se for menor q 5 
					}
					else {
						$qty_atual = (int)$value['qty'] * $estoqueIntTo / 100; 
					}
					$variacao = array(
						"sku" => $skumkt.'-'.$value['variant'],
						"qty" => ceil($qty_atual),
						"ean" => $prd['EAN'],
						"specifications" => $specficiation, 
						"images" => array(),
						);
					$variations[] = $variacao;
				 }	
			}
			$produto['variation_attributes'] =$variation_attributes;
			$produto['variations'] = $variations;
		}
		
		$prod_data = array("product" => $produto);
		$json_data = json_encode($prod_data);
			
		echo "Alterando o produto ".$prd['id']." ".$prd['name']."\n";
		var_dump($json_data);
		if ($json_data === false) {
			// a descrição está com algum problema . tento reduzir... 
			$produto['name'] = substr(strip_tags(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8")," \t\n\r\0\x0B\xC2\xA0"),0,96);
			$produto['description'] = substr($description,0,3000);
			$prod_data = array("product" => $produto);
			$json_data = json_encode($prod_data);
			var_dump($json_data);
			if ($json_data === false) {
				$msg = "Erro ao fazer o json do produto ".$prd['id']." ".print_r($produto,true);
				echo $msg."\n";
				$this->log_data('batch',$log_name, $msg,"E");
				return false;;
			}
		}
		echo "\n";

		$url = 'https://api.skyhub.com.br/products/'.$skumkt;
		$retorno = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
		// var_dump($retorno);
		if ($retorno['httpcode'] == 429) { // estourou o limite
			sleep(60);
			$retorno = $this->putSkyHub($url, $json_data, $this->getApikey(), $this->getEmail());
		}	
		
		if ($retorno['httpcode'] != 204) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($prod_data,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no alterando produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($prod_data,true),"E");
			
			return false;
		}
		echo "produto ".$prd['name']." alterado\n";
		return true;
	
	} 

 	function syncRemoveInativos()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// Verifico se o produto ficou inativo ou incompleto e coloco disabled na skyhub e removo do envio 
		$sql = "SELECT DISTINCT b.* FROM bling_ult_envio b ";
		$sql.= " LEFT JOIN products p ON p.id=b.prd_id "; 
		$sql.= " LEFT JOIN prd_to_integration pi ON pi.prd_id=b.prd_id AND b.int_to = pi.int_to";
		$sql.= " WHERE (p.status!=1 OR p.situacao=1) AND pi.status_int!=90 AND b.int_to='".$this->getInt_to()."'";
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			Echo "Colocando em Disable produto ".$row['prd_id']." SKU ".$row['skubling']."\n";
			$disable = Array (
				'product' => array(
						'status' => 'disabled'
					)
				);
									
			$json_data = json_encode($disable);
			
			$url = 'https://api.skyhub.com.br/products/'.$row['skubling'];
	
			$resp = $this->putSkyHub($url, $json_data,$this->getApikey(),  $this->getEmail());
			if (($resp['httpcode']=="429") )  {  // created
				sleep(60);
				$resp = $this->putSkyHub($url, $json_data,$this->getApikey(),  $this->getEmail());
			}
			if (!($resp['httpcode']=="204") )  {  // created
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
				echo "Dados enviados=".print_r($json_data,true)."\n";
				$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
				continue;
			}
			$int_date_time = date('Y-m-d H:i:s');
			$sql = "UPDATE bling_ult_envio SET data_ult_envio = ? WHERE id = ?";
			$cmd = $this->db->query($sql,array($int_date_time,$row['id']));
			$sql = "UPDATE prd_to_integration SET status=0, status_int=90, date_last_int = ? WHERE int_to=? AND prd_id = ?";
			$cmd = $this->db->query($sql,array($int_date_time,$this->getInt_to(),$row['prd_id']));
	    }
    } 
	
	function limpaErrados()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo "Procurando produtos errados \n";
		$page =1;
	
		while (true){
			echo "pagina ".$page."\n";
			sleep(1);
			$url = 'https://api.skyhub.com.br/products?page='.$page.'&per_page=100';
			$retorno = $this->getSkyHub($url,$this->getApikey(),  $this->getEmail());
			if (!($retorno['httpcode']=="200") )  {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['content'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['content'],true),"E");
				die;
				return;
			}
			//var_dump($retorno);
			$products = json_decode($retorno['content'],true);
			
			if (count($products['products'])==0) {
				echo "acabou\n";
				break;
			}
			$page++;
			
			foreach ($products['products'] as $product) {
				if ($product['status'] == "enabled") {
					$sku = $product['sku'];
					// echo "Verificado ".$sku;
					$sql ="SELECT * FROM bling_ult_envio WHERE skubling = '".$sku."'";
					$query = $this->db->query($sql);
					$bling = $query->row_array();
					if (!$bling) {
						$this->disableSkyhub($sku);
						echo $sku." não existe \n";
						continue;
					}
					if ($bling['int_to'] != $this->getInt_to()) {
						echo $sku." marcado para ".$bling['int_to']." erradamente \n";
						$this->disableSkyhub($sku);
					}
					else {
						// echo " ok \n";
					}
				}
			}
		}

    } 

	

	function disableSkyhub($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$disable = Array (
						'product' => array(
						'status' => 'disabled'
					)
				);
									
		$json_data = json_encode($disable);
		
		$url = 'https://api.skyhub.com.br/products/'.$sku;

		$resp = $this->putSkyHub($url, $json_data,$this->getApikey(),  $this->getEmail());
		
		if ($resp['httpcode']=="429")  {  // created
			sleep(60);
			$resp = $this->putSkyHub($url, $json_data,$this->getApikey(),  $this->getEmail());
		}
		if (!($resp['httpcode']=="204") )  {  // created
			echo "Erro na respota do ".$this->getInt_to().". httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
			echo "Dados enviados=".print_r($json_data,true)."\n";
			$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return false;
		}
		return true;
	}
	
	function limpaEANErrados()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		$this->getkeys(1,0);
		echo "Procurando produtos errados \n";
		
		$sql = "select * from bling_ult_envio where length(skubling) = 15 and int_to='B2W' and left(skubling,1) !='P' and right(skubling,3)='B2W' order by id";
		$query = $this->db->query($sql);
		$blings = $query->result_array();
		foreach ($blings as $bling) {
			$sql = "select * from bling_ult_envio where EAN = ".$bling['EAN']." AND int_to='B2W'";
			$query = $this->db->query($sql);
			$eans = $query->result_array();
			foreach($eans as $ean) {
				if (strlen($ean['EAN']) == 12) {
					echo 'Apaguei '.$ean['id'].' ean:'.$ean['EAN'].' prd:'.$ean['prd_id'].' skubling:'.$ean['skubling']."\n"; 
					$this->disableSkyhub($ean['skubling']);
					$sql = "DELETE from bling_ult_envio where id = ?";
					$query = $this->db->query($sql,array($ean['id']));
				}
				else {
					// echo 'Manteria '.$ean['id'].' ean:'.$ean['EAN'].' prd:'.$ean['prd_id'].' skubling:'.$ean['skubling']."\n";
				}
			}
		}
		
    } 
	
	function getSkyHub($url, $api_key, $login){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}

	function postSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function putSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "PUT",
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function deleteSkyHub($url, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "DELETE",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

	function errorTransformation($prd_id, $sku, $msg, $prd_to_integration_id = null, $mkt_code = null)
	{
		$this->model_errors_transformation->setStatusResolvedByProductId($prd_id,$this->getInt_to());
		$trans_err = array(
			'prd_id' => $prd_id,
			'skumkt' => $sku,
			'int_to' => $this->getInt_to(),
			'step' => "Preparação para envio",
			'message' => $msg,
			'status' => 0,
			'date_create' => date('Y-m-d H:i:s'), 
			'reset_jason' => '', 
			'mkt_code' => $mkt_code,
		);
		echo "Produto ".$prd_id." skubling ".$sku." int_to ".$this->getInt_to()." ERRO: ".$msg."\n"; 
		$insert = $this->model_errors_transformation->create($trans_err);
		
		if (!is_null($prd_to_integration_id)) {
			$sql = "UPDATE prd_to_integration SET date_last_int = now() WHERE id = ?";
			$cmd = $this->db->query($sql,array($prd_to_integration_id));
		}
	}

}
?>
