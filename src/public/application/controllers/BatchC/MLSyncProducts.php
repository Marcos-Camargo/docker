<?php
/*
 
Realiza o Leilão de Produtos e atualiza o ML 

*/   
require APPPATH . "controllers/BatchC/MercadoLivre/Meli.php";

 class MLSyncProducts extends BatchBackground_Controller {
	
	var $int_to_principal='ML'; // esse não varia. Importante para pegar os dados de integração e categoria
	var $int_to='ML';
	var $client_id='';
	var $client_secret='';
	var $refresh_token='';
	var $access_token='';
	var $date_refresh='';
	var $seller='';
	
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
		$this->load->model('model_stores');
		$this->load->model('model_orders');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_products_catalog');
		$this->load->model('model_products_category_mkt');
		
    }

	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setClientId($client_id) {
		$this->client_id = $client_id;
	}
	function getClientId() {
		return $this->client_id;
	}
	function setClientSecret($client_secret) {
		$this->client_secret = $client_secret;
	}
	function getClientSecret() {
		return $this->client_secret;
	}
	function setRefreshToken($refresh_token) {
		$this->refresh_token = $refresh_token;
	}
	function getRefreshToken() {
		return $this->refresh_token;
	}
	function setAccessToken($access_token) {
		$this->access_token = $access_token;
	}
	function getAccessToken() {
		return $this->access_token;
	}
	function setDateRefresh($date_refresh) {
		$this->date_refresh = $date_refresh;
	}
	function getDateRefresh() {
		return $this->date_refresh;
	}
	function setSeller($seller) {
		$this->seller = $seller;
	}
	function getSeller() {
		return $this->seller;
	}
	
	function run($id=null,$params='null')
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}  
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		//$this->apagaTudo();
		//die;
		//$this->baixaVariacao();
		//die;
		//$retorno = $this->limpaBlingUltEnvio();
	    //$this->baixaVariacoes();
		//die;
	    //$this->syncEstoquePreco();
		//$this->gravaFimJob();
	    //die;
		/* faz o que o job precisa fazer */
		
		echo "Rotina desativada\n";
		die; 
		
		if ($params == 'null') {
			$this->setInt_to('ML'); 
		}
		else {
			$this->setInt_to($params); 
		}
		echo $this->getInt_to()."\n";
		$this->getkeys(1,0);
		$retorno = $this->syncProducts();
		$retorno = $this->syncRemoveInativos();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id,$store_id) {
		
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->int_to_principal,"CONECTALA","DIRECT",$store_id);
		
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setClientId($api_keys['client_id']);
		$this->setClientSecret($api_keys['client_secret']);
		//$this->setCode($api_keys['code']);
		$this->setAccessToken($api_keys['access_token']);
		$this->setRefreshToken($api_keys['refresh_token']);
		$this->setDateRefresh($api_keys['date_refresh']);
		$this->setSeller($api_keys['seller']);
		
		/*sql 
		insert into integrations values(0,'Mercado Livre',1,0,1,'{"client_id": "191506436626890", "client_secret":"LrmTu6LdGd5ZbJ6LaGhlfHetjRhmmSvI", "access_token": "APP_USR-191506436626890-080619-8fbb33d69b486596df686d37881cf29c-621913621", "refresh_token": "TG-5f2d64516cc5510006ffad98-621913621", "date_refresh": "0"}','DIRECT','CONECTALA','ML');
		
		 * teste
		UPDATE integrations
	SET auth_data='{"seller":"621913621","client_id": "191506436626890", "client_secret":"LrmTu6LdGd5ZbJ6LaGhlfHetjRhmmSvI", "access_token": "APP_USR-191506436626890-080714-eddfbe20be37f652341d229188a8b919-621913621", "refresh_token": "TG-5f2d70e93476b40007062968-621913621", "date_refresh": "0"}'
	WHERE id=54;
		 * 
		 * producao
		 insert into integrations values(0,'Mercado Livre',1,0,1,'{"client_id": "3148997777473390", "client_secret":"VCpjTA97lhrydu4i9EWBU2Fs4F5HNlKL", "access_token": "APP_USR-191506436626890-080714-eddfbe20be37f652341d229188a8b919-621913621", "refresh_token": "TG-5f2c3ed9e929bc00061b3437-553996630", "date_refresh": "0"}','DIRECT','CONECTALA','ML');
		
		 UPDATE integrations
	SET auth_data='{"seller":"553996630","client_id": "4731355931828241", "client_secret":"zrPa3pp8saTYkAauynbrMYlSCSEd9jFu", "access_token": "APP_USR-4731355931828241-081419-39df5819840113f03f84d0e74f868de4-553996630", "refresh_token": "TG-5f36ea0509b33200065f6d80-553996630", "date_refresh": "0"}'
	WHERE id=736;
		 * 
		 * 
		 * */
		
		$meli = new Meli($this->getClientId(),$this->getClientSecret(),$this->getAccessToken(),$this->getRefreshToken());
		//echo " renovar em ".date('d/m/Y H:i:s',$this->getDateRefresh()).' hora atual = '.date('d/m/Y H:i:s'). "\n"; 
		if ($this->getDateRefresh()+1 < time()) {	
			$user = $meli->refreshAccessToken();
			var_dump($user);
			if ($user["httpCode"] == 400) {
				$user = $meli->authorize($this->getRefreshToken(), 'https://www.mercadolivre.com.br');
				var_dump($user);
				if ($user["httpCode"] == 400) {
					$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
					var_dump($redirectUrl);
					//$retorno = $this->getPage($redirectUrl);
					
					//var_dump($retorno);
					die;
				}
			}
			$this->setAccessToken($user['body']->access_token);
			$this->setDateRefresh($user['body']->expires_in+time());
			$this->setRefreshToken($user['body']->refresh_token);
			$authdata=array(
				'client_id' =>$this->getClientId(),
				'client_secret' =>$this->getClientSecret(),
				'access_token' =>$this->getAccessToken(),
				'refresh_token' =>$this->getRefreshToken(),
				'date_refresh' =>$this->getDateRefresh(),
				'seller' => $this->getSeller(),
			);
			$integration = $this->model_integrations->updateIntegrationsbyCompIntType($company_id,$this->int_to_principal,"CONECTALA","DIRECT",$store_id,json_encode($authdata));	
		}
		// echo 'access token ='.$this->getAccessToken()."\n";
		return $meli; 

		/*
		$user = $meli->authorize($this->getRefreshToken(), 'https://www.mercadolivre.com.br');
		if ($user["httpCode"] == 400) {

			$user = $meli->refreshAccessToken();
			var_dump($user);
					
			$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
			var_dump($redirectUrl);
			//$retorno = $this->getPage($redirectUrl);
			
			//var_dump($retorno);
			die;
		}
		$this->setAccessToken($user['body']->access_token);
		$this->setDateRefresh($user['body']->expires_in+time());
		$this->setRefreshToken($user['body']->refresh_token);
		var_dump($user);
		$authdata=array(
				'client_id' =>$this->getClientId(),
				'client_secret' =>$this->getClientSecret(),
				'access_token' =>$this->getAccessToken(),
				'refresh_token' =>$this->getRefreshToken(),
				'date_refresh' =>$this->getDateRefresh(),
			);
			$integration = $this->model_integrations->updateIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id,json_encode($authdata));
		
		
		die; 
		*/
		$user = $meli->refreshAccessToken();
		var_dump($user);
			
		echo " Authorizando \n";
		$user = $meli->authorize($this->getRefreshToken(), 'https://www.mercadolivre.com.br');
		var_dump($user);
		if ($user["httpCode"] == 400) {
			$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
			var_dump($redirectUrl);
			die;
			
			echo " Não autorizou. Tentando o Refresh \n";
			$user = $meli->refreshAccessToken();
			var_dump($user);
		}
		$this->setAccessToken($user['body']->access_token);
		$this->setDateRefresh($user['body']->expires_in);
		$this->setRefreshToken($user['body']->refresh_token);
		
		if ($this->getDateRefresh()+time()+1 < time()) {	
			$refresh = $meli->refreshAccessToken();
			var_dump($refresh);
			
			$this->setAccessToken($refresh['body']->access_token);
			$this->setDateRefresh($refresh['body']->expires_in);
			$this->setRefreshToken($refresh['body']->refresh_token);
			$authdata=array(
				'client_id' =>$this->getClientId(),
				'client_secret' =>$this->getClientSecret(),
				'access_token' =>$this->getAccessToken(),
				'refresh_token' =>$this->getRefreshToken(),
				'date_refresh' =>$this->getDateRefresh(),
			);
			$integration = $this->model_integrations->updateIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id,json_encode($authdata));
		
		}
	
		die;
		
	}
	
    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo $this->getInt_to()."\n";
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
      	
		$sql = "SELECT pr.* FROM prd_to_integration pr, products p WHERE p.id=pr.prd_id AND pr.approved = 1 AND pr.status=1 
		 AND p.date_update > pr.date_last_int AND p.status=1 AND p.situacao=2
		 AND (pr.status_int =0 OR pr.status_int =1 OR pr.status_int =2) AND pr.int_type=13 AND pr.int_to='".$this->getInt_to()."'";	
       // $sql = "SELECT * FROM prd_to_integration WHERE date_update > date_last_int AND status_int=1 AND status=1 AND int_type=13 AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
			if ($prd['date_update'] < $row['date_last_int']) { // não mudou não mando de novo
				continue;
			}
			if (($prd['status'] == 2) || ($prd['situacao'] == 1)) {
					// está inativo ou incompleto 
				continue;
			}
			
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
			
			echo " enviando produto ".$row['prd_id']."\n";

			$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$bling_ult_envio = $cmd->row_array();
			$skumkt = '';
			if ($bling_ult_envio) {
				$skumkt = $bling_ult_envio['skumkt'];
			}
			echo "SKUMKT =".$skumkt."\n";
			if (substr($skumkt,0,3) != 'MLB') {
				// rick aqui. 
				Echo "Não estamos publicando novos produtos no momento\n";
				continue;	
			} 
			
			
			// pego a categoria do mercado livre 
			$idcat = $category = filter_var($prd['category_id'], FILTER_SANITIZE_NUMBER_INT);
			$result= $this->model_categorias_marketplaces->getCategoryMktplace($this->int_to_principal,$idcat);
			if (!$result) {
				$msg= 'Categoria não vinculada ao Mercado Livre '.$idcat;
				$this->errorTransformation($prd['id'],$skumkt,$msg, $row['id']);
				continue;
			}
			
			$idCatML= $result['category_marketplace_id'];
			
			echo 'categoria do ML '.$idCatML."\n";

			$enrichment = $this->model_products_category_mkt->getCategoryEnriched('ML', $prd['id']);
			if (!is_null($enrichment)) {
				$idCatML = $enrichment['category_mkt_id'];
				echo 'categoria do ML Enriched '.$idCatML."\n";
			}

			$prd['categoria_ML'] = $idCatML;
			
			// se tiver Variação verificar se a categoria aceita a variação 
        	$erro = false;
        	if ($prd['has_variants']!="") {
        		$variações = explode(";",$prd['has_variants']);

				foreach ($variações as $variacao) {
					$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($prd['categoria_ML'],ucfirst(strtolower($variacao)),'ML');
					if (!$atributosCat) {
						$catMl =  $this->model_categorias_marketplaces->getAllCategoriesById($this->getInt_to(),$idCatML);
						$msg= 'Categoria '.$idCatML.'-'.$catMl['nome'].' não aceita variação de '.$variacao;
						//$this->errorTransformation($prd['id'],$skumkt,$msg, $row['id']);
						//$erro = true;
					}elseif (!$atributosCat['variacao']){
						$catMl =  $this->model_categorias_marketplaces->getAllCategoriesById($this->getInt_to(),$idCatML);
						$msg= 'Categoria '.$idCatML.'-'.$catMl['nome'].' não aceita variação de '.$variacao;
						$this->errorTransformation($prd['id'],$skumkt,$msg, $row['id']);
						$erro = true;
					}
				}
			}
        	if ($erro) {
        		continue;
        	}
			
			if ((strpos($prd['description'], 'http://') !== false) || (strpos($prd['description'], 'www.') !== false) || (strpos($prd['description'], 'https://') !== false)) {
				$msg= 'É proibido ter link para páginas web na descrição';
				$this->errorTransformation($prd['id'],$skumkt,$msg, $row['id']);
				continue;
			}
			
			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// Pego o preço a ser praticado 
			$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"ML");
			// e ai vejo se tem campanha 
			// $prd['price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to());
			$prd['price'] = round($prd['price'],2);
			
			if (is_null($row['skubling'])) {
				$row['skubling'] =  "P".$prd['id']."S".$prd['store_id'].$this->getInt_to();
				$sql = "UPDATE prd_to_integration SET skubling = ? WHERE id = ?";
				$query = $this->db->query($sql,array($row['skubling'],$row['id']));
			}
			$sku = $row['skubling'];
			
			$ean = $prd['EAN']; 
			if ($prd['is_kit'] == 1) {
				$ean ='IS_KIT'.$prd['id'];
			}
			else {
				$ean ='ML_EAN'.$prd['id']; // EAN para colocar no Bling_ult_envio. Não é importante ter EAN, então crio um EAN único para cada produto
			}

			echo 'Processando produto prd_to_integration Id= '.$row['id'].' sku '.$row['skubling']." SkuML ".$row['skumkt']."\n";
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$qty_salvo = $prd['qty'];
			$qty_atual = (int) $prd['qty'] * $estoqueIntTo[$row['int_to']] / 100; 
			$qty_atual = ceil($qty_atual); // arrendoda para cima 
			$status_int = 2;
			if  ((int)$prd['qty'] < 5) { // Mando só para a B2W se a quantidade for menor que 5. 
				$qty_atual = 0;
				$status_int = 10;
			}
			$prd['qty'] = $qty_atual;
			
			/*
			if (substr($skumkt,0,3) != 'MLB') {
				echo "Pulando pois não estou cadastrando no momento\n"; // rick remover depois 
				continue; 
			}
			*/
			
			
			
			$resp = $this->inserePrd($prd,$sku,$estoqueIntTo[$this->getInt_to()], $skumkt, $ad_link, $quality);    
			
			if (!$resp) {
				$int_date = date('Y-m-d H:i:s');
				$sql = "UPDATE prd_to_integration SET date_last_int = ? WHERE id = ?";
				$cmd = $this->db->query($sql,array($int_date, $row['id']));
				continue; 
			} else { 
				
				// TROUXE PRA DENTRO DO SUCESSO
				$int_date = date('Y-m-d H:i:s');
				$sql = "UPDATE prd_to_integration SET skumkt = '".$skumkt."' , ad_link = '".$ad_link."' , quality = '".$quality."',status_int=".$status_int." , date_last_int = ? WHERE id = ".$row['id'];
				$cmd = $this->db->query($sql,array($int_date));
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
				// echo 'SQL = '. $sql."\n";
				// echo 'lido ='. print_r($lido,true)."\n";
				
				$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];

				$marca_int_bling= null;
				$categoria_bling = null;
				$mkt_store_id= ''; 
				$reputacao= '100'; 
				$nvl = '100';
				if ($bling_ult_envio) {
					$marca_int_bling = $bling_ult_envio['marca_int_bling'];
					$categoria_bling = $bling_ult_envio['categoria_bling'];
					$mkt_store_id = $bling_ult_envio['mkt_store_id'];
					$reputacao = $bling_ult_envio['reputacao'];
					$nvl =  $bling_ult_envio['NVL'];
				}
				$loja  = $this->model_stores->getStoresData($prd['store_id']);
				
	        	$data = array(
	        		'int_to' => $this->getInt_to(),
	        		'company_id' => $prd['company_id'],
	        		'EAN' => $ean,
	        		'prd_id' => $row['prd_id'],
	        		'price' => $prd['price'],
	        		'qty' => $qty_salvo,
	        		'sku' => $prd['sku'],
	        		'reputacao' => $reputacao,
	        		'NVL' => $nvl,
	        		'mkt_store_id' => $mkt_store_id,         
	        		'data_ult_envio' => $int_date,
	        		'skubling' => $sku,
	        		'skumkt' => $skumkt,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $prd['qty'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id'], 
	        		'marca_int_bling' => $marca_int_bling, 
					'categoria_bling'=> $categoria_bling,
	        		'crossdocking' => $crossdocking, 
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']), 
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type'],
					
	        	);
				if ($bling_ult_envio) {
					$insert = $this->db->replace('bling_ult_envio', $data);
					//$insert = $this->model_blingultenvio->update($data, $bling_ult_envio['id']);
				}else {
					$insert = $this->db->replace('bling_ult_envio', $data);
				}
			}
	    }
        return ;
    } 
 
	function inserePrd($prd,$skubling,$estoqueIntTo, &$skumkt,&$ad_link, &$quality) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		
		$novo_produto = false;
		$vendas = 0;
		if (substr($skumkt,0,3) != 'MLB') {
			echo " Vou incluir ---- SKUMKT =".$skumkt."\n";
			$skumkt = '';
			$novo_produto = true;
		
		} else {
			echo "Vou alterar o preço e estoque do SKUMKT =".$skumkt."\n";
			
			if ($this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas) == false) {
				return false;
			}
			echo "Preço e estoque alterado. Vendas = ".$vendas.". Vou alterar o resto\n";
		}
		
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
		$prazo = $prd['prazo_operacional_extra']; 
		if ($prazo < 1 ) { $prazo = 1; }
		$produto = array(
			
			"currency_id"   => "BRL",
			"sale_terms" => array(
     			array (
        			"id" => "MANUFACTURING_TIME",
        			"value_name" =>  $prazo." dias",
     			)
  			),
			"seller_custom_field" =>$skubling,
  			"attributes" => array()
		);
		
		if ($novo_produto) { // Atributos que só podem ser definidos e não podem ser alterados -  Descricao será alterada com uma chamada específica 
			$produto["site_id"] = "MLB";
			$descricao = substr(htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",str_replace("<br>","\n",$prd['description'])))), ENT_QUOTES, "utf-8"),0,3800);
       		$semacento = strtr(utf8_decode($descricao),
                 utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
			$produto['description'] = array("plain_text" => utf8_encode($semacento));
			if ($this->getInt_to() == 'ML') {
				$produto["listing_type_id"] = "gold_pro";  
			}else {
				$produto["listing_type_id"] = "gold_special";  
			}
			//"listing_type_id" => "gold_pro",   	    // Premium
			//"listing_type_id" => "gold_premium",   	// Diamante
			//"listing_type_id" => "gold_special",    	// Clássico
			//"listing_type_id" => "gold",   			// Ouro
			//"listing_type_id" => "silver",   			// Prata
			//"listing_type_id" => "bronze",   			// Bronze
			//"listing_type_id" => "free",   			// Grátis
		}else { 
			if ((int)$prd['qty'] > 0) {
				$produto["status"] = "active"; // ativa produtos que podem ter sido pausados por algum erro de transformação
			}
		}  
		//$vendas=$this->model_orders->getOrdersByProductItem($prd['id'], $this->getInt_to()); 
		if ($vendas>0) {
			echo ' este produto já teve venda e nem todos os atributos poderão ser alterado '."\n";
		}
		if (($novo_produto) || ($vendas==0) ) { // atributos que só podem ser alterados até a primeira venda 
		// https://developers.mercadolivre.com.br/pt_br/produto-sincronizacao-de-publicacoes
			$produto["title"] = substr(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8"),0,60);
			$produto["buying_mode"] = "buy_it_now"; 
			$produto["condition"] = "new";
			$produto["category_id"] = $prd['categoria_ML'];
			$produto["shipping"]["local_pick_up"] = false;
			$produto["shipping"]["free_shipping"] = false;
			$produto["shipping"]["mode"] = 'me1';
			$larg = ($prd['largura'] < 11) ? 11 : $prd['largura'];
			$prof = ($prd['profundidade'] < 16) ? 16 : $prd['profundidade'];
			$altu = ($prd['altura'] < 2) ? 2 : $prd['altura'];
			$produto["shipping"]["dimensions"] =  ceil($prof).'x'.ceil($larg).'x'.ceil($altu).','.ceil(($prd['peso_bruto']*1000));
				
			$produto["sale_terms"][] = array(
					 "id" => "WARRANTY_TYPE",
       				 "value_name" =>"Garantia de fábrica"
     			);
     		$produto["sale_terms"][] = array (
        			"id" => "WARRANTY_TIME",
        			"value_name" =>  $prd['garantia']." meses",
     			);
		}
		$tipos_variacao = array();
		if ($prd['has_variants']=="") {  // se não tem variação, o preço e quantidade fazem parte do produto, caso contrário, faz parte da variação. 
			$produto["available_quantity"] = ((int)$prd['qty']<0) ? 0 : (int)$prd['qty'];
			$produto["price"] = (float)$prd['price'];
		}
		else {
			$tipos_variacao = explode(";",strtoupper($prd['has_variants'])); 
		}
		
		if (strpos(base_url(),"teste.conectala.com" ) > 0)  {
			$produto["title"] = "Item De Teste - Não Comprar"; 
		}

		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($prd['categoria_ML'],'ML');
		
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
					"value_name" => $brand['name'],
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
					"value_name" => $brand['name'],
				);
				continue;
			}
			$tipos_variacao = array();
			if ($prd['has_variants']!="") {
				 $tipos_variacao = explode(";",strtoupper($prd['has_variants'])); 
				if 	(in_array(strtoupper($atributoCat['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
					continue;
				}
			}
			if ($atributoCat['id_atributo'] == 'SELLER_SKU') {
				if ($prd['has_variants']=="") { // Seller_sku vai para a variação em vez do produto pai. 
					$produto['attributes'][]= array(
						"id" => $atributoCat['id_atributo'],
						"value_name" => $skubling,
					);
				}
				continue;
			}
			
			if 	(($atributoCat['id_atributo'] == 'EAN') || ($atributoCat['id_atributo'] == 'GTIN')) {
				if ($prd['EAN'] != '') {
					$produto['attributes'][]= array(
						"id" => $atributoCat['id_atributo'],
						"value_name" => $prd['EAN'],
					);
				} else {
					if ($novo_produto) {
					 	$produto['attributes'][]= array(
					 		"id" => $atributoCat['id_atributo'],
					 		"value_id" => "-1",
					 		"value_name" => null,
					 	);
					}
				}
				continue;
			}
			if (!is_null($prd['product_catalog_id'])) {
				$atributo_prd = $this->model_products_catalog->getProductAttributeByIdIntto($prd['product_catalog_id'],$atributoCat['id_atributo'],'ML');	
			} else {
				$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($prd['id'],$atributoCat['id_atributo'],'ML');
			}
			if ($atributo_prd) {
				$produto['attributes'][]= array(
					"id" => $atributo_prd['id_atributo'],
					"value_name" => $atributo_prd['valor'],
				);
			}else{
				if ($atributoCat['obrigatorio'] == 1) {
					$msg= 'Falta atributo da categoria obrigatório '.$atributoCat['id_atributo'].'-'.$atributoCat['nome'];
					$this->errorTransformation($prd['id'],$skubling, $msg);
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

		$imagens = array();
		$picture_ids =array(); // usado nas variações  
		if ($prd['image']!="") {
			$numft = 0;
			if (strpos("..".$prd['image'],"http")>0) {
				$fotos = explode(",", $prd['image']);	
				foreach($fotos as $foto) {
					$imagens[] = array("source" =>$foto);
					$picture_ids[] =  $foto;
					$numft++;
					if ($numft==6) { // limite de 6 fotos na skyhub
						break;
					} 
				}
			} else {
				$fotos = scandir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image']);	
				foreach($fotos as $foto) {
					if (($foto!=".") && ($foto!="..")) {
						if(!is_dir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image'].'/'.$foto)) {
							$imagens[] = array("source" => base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $foto));
							$picture_ids[] = base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $foto);
							$numft++;
						}
					}
					if ($numft==6) { // limite de 6 fotos na skyhub
						break;
					} 
				}
			}	
		}
		$produto['pictures'] = $imagens;
		
		if ($prd['has_variants']!="") {
			$prd_variacao = array();
			$variations = array();
            $prd_vars = $this->model_products->getProductVariants($prd['id'],$prd['has_variants']);
            // var_dump($prd_vars);
            $tipos = explode(";",$prd['has_variants']);
            // var_dump($tipos);
			
			foreach($prd_vars as $value) {
				// var_dump($value);
			  	if (isset($value['sku'])) {
			  		$mkt_sku = $this->model_products->getMarketplaceVariantsByFields($this->getInt_to(),0,$prd['id'],$value['variant']);  // vejo se é uma variação nova ou antiga
					
					$attribute_combinations = array();
					$i=0;
					foreach ($tipos as $z => $campo) {
						$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($prd['categoria_ML'],ucfirst(strtolower($campo)),'ML');
						//var_dump($atributoCat);
						
						$valor_id = null;
						if ($atributoCat['valor'] != '') {  // verificar se o valor da variação já está cadastrado 
							$valores = json_decode($atributoCat['valor'],true);
							foreach($valores as $valor) {
								if (strtoupper($valor['name']) ==  strtoupper($value[$campo])) {
									$valor_id = $valor['id'];
									continue;
								}
							}
						}
						if (is_null($valor_id)) {
							$attribute_combinations[] = array(
								"name" => ucfirst(strtolower($campo)),
								"value_name" => $value[$campo], 
								"value_id" => null,
							);
						} else {
							$attribute_combinations[] = array(
								"id" => $atributoCat['id_atributo'],
								"value_id" => $valor_id,
							);
						}
						
					}
					$qty_atual = (int)$value['qty'] * $estoqueIntTo / 100; 
					if ($qty_atual < 0) { $qty_atual=0;}
					$variacao = array(
						"attribute_combinations" =>  $attribute_combinations,
						"price" => (float)$prd['price'], // apesar de parecer mandar preços diferentes, o ML ignora e usa o mais alto 
						"available_quantity" => ceil($qty_atual),
						"picture_ids" => $picture_ids, 
						"seller_custom_field" => $skubling.'-'.$value['variant'],
						"attributes" => array(
							array(
								"id" => "SELLER_SKU",
								"name" => "SKU",
                   				"value_id" => null,
								"value_name" => $skubling.'-'.$value['variant'],
								"value_struct" => null
							)
						), 
						);
					if ($mkt_sku) {
						$variacao['id'] = $mkt_sku['sku'];
					} 
					$variations[] = $variacao;
				}	

			}
			$produto['variations'] = $variations;	
			
		}
		
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($prd['id'],$this->getInt_to(),"Preparação para envio");
		
		//$meli = new Meli('191506436626890','LrmTu6LdGd5ZbJ6LaGhlfHetjRhmmSvI');
	///	$mlcode = 'TG-5f2c57ff8c20a900067e8339-621913621';
		//$redirectUrl = $meli->getAuthUrl("https://www.mercadolivre.com.br",Meli::$AUTH_URL['MLB']); //  Don't forget to change the $AUTH_URL value to match your user's Site Id.
		//var_dump($redirectUrl);
		//die; 
		// $_SESSION['access_token'] = 'APP_USR-191506436626890-080619-8fbb33d69b486596df686d37881cf29c-621913621';
		 

		//
		if (false) {
			$user = $meli->authorize($mlcode, 'https://www.mercadolivre.com.br');
		 	var_dump($user);
			// Now we create the sessions with the authenticated user
			$_SESSION['access_token'] = $user['body']->access_token;
			echo 'access token = '.$user['body']->access_token.' Refresh Token = '.$user['body']->refresh_token."\n";
			$_SESSION['expires_in'] = $user['body']->expires_in;
			$_SESSION['refrsh_token'] = $user['body']->refresh_token;
			// We can check if the access token in invalid checking the time
			if($_SESSION['expires_in'] + time() + 1 < time()) {
				try {
					echo 'fazendo refresh'."\n";
		            print_r($meli->refreshAccessToken());
				} catch (Exception $e) {
				  	echo "Exception: ",  $e->getMessage(), "\n";
				}
			}
		}

		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		//var_dump($prod_data);
		//$response = $meli->post('/items', $prod_data, $params);
		//var_dump($response);
		
		if ($novo_produto) {  // produto novo usa post
			echo "Incluindo o produto ".$prd['id']." ".$prd['name']."\n";
			$resp_json =  json_encode($produto);
			if ($resp_json === false) {
				echo 'Houve um erro ao fazer o json do produto'."\n";
				if ( json_last_error_msg()=="Malformed UTF-8 characters, possibly incorrectly encoded" ) {
					echo "Tentando resolver mudando descrição e título\n";
					$produto['description'] = array("plain_text" => utf8_encode($semacento));
					if (array_key_exists('title',$produto)) {
						$produto['title'] = substr(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8"),0,55);
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
			$url = '/items';
			$retorno = $meli->post($url, $produto, $params);
			// var_dump($retorno);	
		}	
		else {  // produto antigo usa put
			echo "Alterado o produto ".$prd['id']." ".$prd['name']." skumkt = ".$skumkt."\n";
			echo print_r(json_encode($produto),true)."\n";
			$url = '/items/'.$skumkt;
			$retorno = $meli->put($url, $produto, $params);
			// var_dump($retorno);
		}
		if ($retorno['httpCode'] == 400) {
			$respostaML = json_decode(json_encode($retorno['body']),true);
			if ( $respostaML['error'] == 'validation_error')  {
				$errors = $respostaML['cause'];
				$errorTransformation = '';
				foreach ($errors as $erro) {
					if ($erro['type'] == 'error') {
						$errorTransformation =  'ERRO: '.$erro['message'].' ! ';
						$this->errorTransformation($prd['id'],$skubling, $errorTransformation,null, $erro['code'] );
					}
				}
				if ($errorTransformation == '') {
					$errorTransformation= json_encode($retorno['body']); 
					$this->errorTransformation($prd['id'],$skubling, $errorTransformation);
				}
				echo $errorTransformation."\n";
			}
			else {
				$this->errorTransformation($prd['id'],$skubling, json_encode($retorno['body']));
				echo json_encode($retorno['body'])."\n";
			}
			return false;
		}
		if (($retorno['httpCode'] != 201 && $novo_produto) || ($retorno['httpCode'] != 200 && !$novo_produto))  {
				
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($produto,true),"E");
			return false;
		}
		$respostaML = json_decode(json_encode($retorno['body']),true);
		$skumkt =$respostaML['id'];
		echo " Codigo do Mercado Livre - ".$skumkt."\n";
		//var_dump($respostaML);
		$ad_link =$respostaML["permalink"];
		echo " link = ".$ad_link."\n"; 
		$quality = $respostaML["health"];
		echo " quality = ".$quality."\n";
		// TRATAR VARIANTS	- Incluo ou altero um variação. 	
		if ($prd['has_variants']!="") {
			foreach ($respostaML['variations'] as $variacao) {
				$id = $variacao['id'];
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
				$mkt_sku = $this->model_products->getMarketplaceVariantsByFields($this->getInt_to(),0,$prd['id'],$variant);  // vejo se é uma variação nova ou antiga
				if (!$mkt_sku) {  // produto novo cadastro o novo ID. 
					$data = array (
						'prd_id' => $prd['id'],
						'variant' => $variant,
						'store_id' => 0,  // loja Conectala
						'company_id' => 1, // empresa conectala 
						'sku' => $variacao['id'],
						'int_to' => $this->getInt_to(),
					);
					$insert = $this->db->insert('marketplace_prd_variants', $data);
				}	
			}	
		}
		
		// Descrição tem que ser alterada em separado
		// Agora tento colocar a descrição com acentos.
		$description = array("plain_text" => substr(htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",str_replace("<br>","\n",$prd['description'])))), ENT_QUOTES, "utf-8"),0,3800));
		$url = '/items/'.$skumkt.'/description';
		$params = array('access_token' => $this->getAccessToken(),'api_version' => 2);
		$retorno = $meli->put($url, $description, $params);
		if ($retorno['httpCode'] == 400) {
			// não funcionou, removo os acentos
			$tot_desc = 3800;
			While (true) {
				echo "reduzindo a descrição até passar \n";
				$tot_desc--;
				$descricao = substr(htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",str_replace("<br>","\n",$prd['description'])))), ENT_QUOTES, "utf-8"),0,$tot_desc);
       			$semacento = strtr(utf8_decode($descricao),
	                 utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
	                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
				$produto['description'] = array("plain_text" => utf8_encode($semacento));
			
	            $description =  array("plain_text" =>utf8_encode($semacento));
			    $url = '/items/'.$skumkt.'/description';
				$params = array('access_token' => $this->getAccessToken(),'api_version' => 2);
				$retorno = $meli->put($url, $description, $params);                 
				
				if ($retorno['httpCode'] != 400) {
					break;
				}	
				if ($tot_desc< 3780) {
					break;
				}
			}
			
		}
		if ($retorno['httpCode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($produto,true),"E");
			return $skumkt; // apesar de ter dado erro na descrição, já cadastrou o produto
		}	
		
		return $skumkt;
	
	} 

	function syncRemoveInativos()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		// Verifico se o produto ficou inativo ou incompleto e coloco em pause no Mercado Livre 
		$sql = "SELECT DISTINCT b.* FROM bling_ult_envio b ";
		$sql.= " LEFT JOIN products p ON p.id=b.prd_id "; 
		$sql.= " LEFT JOIN prd_to_integration pi ON pi.prd_id=b.prd_id AND b.int_to = pi.int_to";
		//$sql.= " WHERE (p.status!=1 OR p.situacao=1) AND pi.status_int!=90 AND pi.status=0 AND b.int_to='".$this->getInt_to()."'";
      	$sql.= " WHERE (p.status!=1 OR p.situacao=1) AND pi.status_int!=90 AND b.int_to='".$this->getInt_to()."'";
        $query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
	    	if (substr($row['skumkt'],0,3) == 'MLB') {	
				Echo "Colocando em pause produto ".$row['prd_id']." SKU ".$row['skubling']." SKU ML ".$row['skumkt']."\n";
				if ($this->pauseML($row['skumkt'])) {
					$int_date_time = date('Y-m-d H:i:s');
					$sql = "UPDATE bling_ult_envio SET data_ult_envio = ? WHERE id = ?";
					$cmd = $this->db->query($sql,array($int_date_time,$row['id']));
					$sql = "UPDATE prd_to_integration SET status=0, status_int=90, date_last_int = ? WHERE int_to=? AND prd_id = ?";
					$cmd = $this->db->query($sql, array($int_date_time,$this->getInt_to(),$row['prd_id']));
				}
			}
	    }
	    
	    $sql = "SELECT * FROM prd_to_integration WHERE approved!=1 AND status_int=2";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
		{
			if (substr($row['skumkt'],0,3) == 'MLB') {	
				Echo "Colocando em pause produto ".$row['prd_id']." SKU ".$row['skubling']." SKU ML ".$row['skumkt']."\n";
				if ($this->pauseML($row['skumkt'])) {
					$int_date_time = date('Y-m-d H:i:s');
					$sql = "UPDATE bling_ult_envio SET data_ult_envio = ?, qty=0, qty_atual=0 WHERE int_to=? AND prd_id = ? ";
					$cmd = $this->db->query($sql, array($int_date_time,$this->getInt_to(),$row['prd_id']));
					$sql = "UPDATE prd_to_integration SET status=1, status_int=0, date_last_int = ? WHERE id=? ";
					$cmd = $this->db->query($sql, array($int_date_time,$row['id']));
				}
			}
		}
		
		$sql = "SELECT * FROM prd_to_integration WHERE status=0 AND status_int!=90 AND skumkt is not null AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
		{
			if (substr($row['skumkt'],0,3) == 'MLB') {	
				Echo "Colocando em pause produto ".$row['prd_id']." SKU ".$row['skubling']." SKU ML ".$row['skumkt']."\n";
				if ($this->pauseML($row['skumkt'])) {
					$int_date_time = date('Y-m-d H:i:s');
					$sql = "UPDATE bling_ult_envio SET data_ult_envio = ?, qty=0, qty_atual=0 WHERE int_to=? AND prd_id = ? ";
					$cmd = $this->db->query($sql, array($int_date_time,$this->getInt_to(),$row['prd_id']));
					$sql = "UPDATE prd_to_integration SET status=0, status_int=90, date_last_int = ? WHERE id=? ";
					$cmd = $this->db->query($sql, array($int_date_time,$row['id']));
				}
			}
		}
    } 

	function pauseML($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$pause = Array (
						'status' => 'paused'
				);
									
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->put($url, $pause, $params);
		if  ($retorno['httpCode'] == 400 ) { // verifico se já está em closed e ai tem que remover SKUmkt de volta para 00 no Prd_to_integration e Bling_ult_envio
			$body = json_decode(json_encode($retorno['body']),true);
			if ($body['cause'][0]['message'] == 'Item in status closed is not possible to change to status paused. Valid transitions are [closed]') {
				echo 'Colocando 00 para skumkt onde era  '.$skumkt." pois o anúncio ficou Closed no ML \n";
				$sql = 'UPDATE prd_to_integration SET skumkt = "00" WHERE skumkt = ?';
				$cmd = $this->db->query($sql,array($skumkt));
				$sql = 'UPDATE bling_ult_envio SET skumkt = "00" WHERE skumkt = ?';
				$cmd = $this->db->query($sql,array($skumkt));
				return true;
			}
			if ($body['message']== 'Cannot update item '.$skumkt.' [status:under_review, has_bids:false]') {
				echo 'Produdo '.$skumkt.' com status:under_review, has_bids:false - já pausado pelo ML'."\n";
				return true;
			}
			if ($body['message']== 'Cannot update item '.$skumkt.' [status:under_review, has_bids:true]') {
				echo 'Produdo '.$skumkt.' com status:under_review, has_bids:true - já pausado pelo ML'."\n";
				return true;
			}
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		if  ($retorno['httpCode'] != 200 ) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		return true;
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
	
	function baixaVariacoes()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo "Procurando produtos errados \n";
		$offset = 0; 

		$scroll_id = '';
		$cnt =0;
		$cntbling = 0;
		$cntok = 0;
		$ignore = array('EAN','GTIN','SELLER_SKU','EXCLUSIVE_CHANNEL','ITEM_CONDITION'); 
		
		while (true){
			$meli= $this->getkeys(1,0);
			$params = array(
			//	'offset' => $offset,
				'search_type' => 'scan',
				'access_token' => $this->getAccessToken(),
				);
			if ($scroll_id != '') {
				$params['scroll_id'] = $scroll_id;
			}
			$url = '/users/'.$this->getSeller().'/items/search';
			$retorno = $meli->get($url, $params);
			if (!($retorno['httpCode']=="200") )  {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
				die;
				return;
			}
			$body = json_decode(json_encode($retorno['body']),true);
			$scroll_id = $body['scroll_id'];
			$paging = $body['paging'];
			$results = $body['results'];
			
			if (count($results)==0) {
				echo "acabou\n";
				echo ' errados = '.$cnt."\n";
				echo ' bling = '.$cntbling."\n";
				echo ' ok = '.$cntok."\n";
				break;
			}
			
			foreach ($results as $skumkt) {
				// Leio o produto.
				$meli= $this->getkeys(1,0);
				$params = array(
					'access_token' => $this->getAccessToken());
				$url = '/items/'.$skumkt;
				$retorno = $meli->get($url, $params);
				if (!($retorno['httpCode']=="200") )  {  
					echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
					die;
					return;
				}
				$product = json_decode(json_encode($retorno['body']),true);
				
				if (($product['status']=='closed') || ($product['status']=='paused')|| ($product['status']=='under_review')) {
					// pula os encerrados	
					continue;
				}
				
				
				$sql ="SELECT * FROM bling_ult_envio WHERE skumkt = '".$skumkt."'";
				$query = $this->db->query($sql);
				$bling = $query->row_array();
				if (!$bling) {
					echo $skumkt." não existe. Procurando o SKU. Product status = ".$product['status'] ."\n";
					$skubling = '';
					foreach ($product['attributes'] as $attribute){
						if ($attribute['id'] == "SELLER_SKU") {
							$skubling = $attribute['values'][0]['name'];
							break;
						}						
					}
					if ((!is_null($product['seller_custom_field'])) && ($skubling == '')) {
						$skubling = $product['seller_custom_field'];
					}
					if ($skubling == ''){
						$cnt++;
						echo $skumkt.' não achei sku no ML '.$product['status']."\n";
						if ($product['status'] == 'active') {
							//rick $this->pauseML($skumkt);
						}
						
						continue;
					}
					else {
						echo 'achei o '.$skubling."\n";
						die;
						$sql ="SELECT * FROM bling_ult_envio WHERE skubling = '".$skubling."'";
						$query = $this->db->query($sql);
						$bling = $query->row_array();
						if (!$bling) {
							
							echo $skumkt.' não tem no bling_ult_envio '.$product['status']."\n";
							$cnt++;
							if ($product['status'] == 'active') {
								 $this->pauseML($skumkt);
							}
							if (($product['status'] == 'active') || ($product['status'] == 'paused')) {
									$this->closeML($skumkt);
							}
							continue;
						} else {
							if ($bling['skumkt'] != $skumkt) {
								
								echo " estão diferentes ".$skumkt." de ".$bling['skumkt']."\n";
								if ($product['sold_quantity'] != 0) {
									echo "houve vendas\n";
									die;
								}
								if (substr($bling['skumkt'],0,3) == 'MLB') {
									$this->pauseML($skumkt);
									$this->closeML($skumkt);
								}
								
								else {
									echo " o que fazer ?\n";
									if ($bling['int_to'] != $this->getInt_to()) {
										echo $bling['prd_id'].' '.$skumkt.' '.$skubling." marcado para ".$bling['int_to']." erradamente ".$product['status']."\n";
										if ($product['status'] == 'active') {
											 $this->pauseML($skumkt);
										}
										if (($product['status'] == 'active') || ($product['status'] == 'paused')) {
											$this->closeML($skumkt);
										}
										
										$cntbling++;
										continue;
									}
									die;
									$sql = "UPDATE prd_to_integration SET skumkt = '".$skumkt."' where skumkt ='".$bling['skumkt']."' AND int_to = '".$this->getInt_to()."'";
									$cmd = $this->db->query($sql, array($skumkt, $bling['skumkt']));
									$sql = "UPDATE bling_ult_envio SET skumkt = '".$skumkt."' where skumkt ='".$bling['skumkt']."' AND int_to = '".$this->getInt_to()."'";
				     				$cmd = $this->db->query($sql);
									die;
								}
							}
							// Aqui eu acerto o bling_ult_envio e o prd_to_integrations 
							$sql = "UPDATE prd_to_integration SET skumkt = '".$skumkt."' where skubling ='".$skubling."' AND int_to = '".$this->getInt_to()."'";
			//rick				$cmd = $this->db->query($sql);
							$sql = "UPDATE bling_ult_envio SET skumkt = '".$skumkt."' where skubling ='".$skubling."' AND int_to = '".$this->getInt_to()."'";
			//rick				$cmd = $this->db->query($sql);
						}
					}
					
				}
				if ($bling['int_to'] != $this->getInt_to()) {
					echo $bling['prd_id'].' '.$skumkt.' '.$skubling." marcado para ".$bling['int_to']." erradamente ".$product['status']."\n";
					if ($product['status'] == 'active') {
						 $this->pauseML($skumkt);
					}
					if (($product['status'] == 'active') || ($product['status'] == 'paused')) {
						$this->closeML($skumkt);
					}
					
					$cntbling++;
					continue;
				}
				
				// aqui eu insiro os atributos específicos deste produto na nossa tabela produtos_atributos_marketplaces.
				
				foreach ($product['attributes'] as $attribute){
					if (!in_array($attribute['id'], $ignore)) {
						// echo $bling['prd_id'].' '.$attribute['id'].' '.$attribute['values'][0]['name']."\n";
						$prd_att = array(
							'id_product' => $bling['prd_id'],
							'id_atributo' => $attribute['id'], 
							'valor' => $attribute['values'][0]['name']
						); 
						//rick $insert = $this->db->replace('produtos_atributos_marketplaces', $prd_att);  						
					}
				}
				
				
				// echo $skumkt." ok \n";
				$cntok++;
				$prd = $this->model_products->getProductData(0,$bling['prd_id']);
				
				if ($prd['has_variants']!="") {
					$meli= $this->getkeys(1,0);
					$params = array(
						'include_attributes'=>'all',
						'access_token' => $this->getAccessToken());
					
					$url = '/items/'.$skumkt;
					$retorno = $meli->get($url, $params);
					if (!($retorno['httpCode']=="200") )  {  
						echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
						$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
						die;
						return;
					}
					$product = json_decode(json_encode($retorno['body']),true);
					if (array_key_exists('variations', $product)) {
						//var_dump($product['variations']);
						foreach ($product['variations'] as $variacao) {
							$id = $variacao['id'];
							//if (!is_null($variacao['seller_custom_field'])) {
							//	$sku_variant = $variacao['seller_custom_field'];
							//	echo " variação no seller_custom_field\n";
							//}
							//else {
								foreach ($variacao['attributes'] as $attribute){
									if ($attribute['id'] == "SELLER_SKU") {
										$sku_variant = $attribute['values'][0]['name'];
										break;
									}
									$sku_variant = '';
								}
							//}
						//	echo ' SKU da variação = '.$sku_variant."\n";
							$variant = substr($sku_variant,strrpos($sku_variant,'-')+1);
							$mkt_sku = $this->model_products->getMarketplaceVariantsByFields($this->getInt_to(),0,$prd['id'],$variant);  // vejo se é uma variação nova ou antiga
							if (!$mkt_sku) {  // produto novo cadastro o novo ID. 
								$data = array (
									'prd_id' => $prd['id'],
									'variant' => $variant,
									'store_id' => 0,  // loja Conectala
									'company_id' => 1, // empresa conectala 
									'sku' => $variacao['id'],
									'int_to' => $this->getInt_to(),
								);
								$insert = $this->db->insert('marketplace_prd_variants', $data);
								echo "inseri variacao ".$prd['id']." ".$skumkt." ".$variacao['id']."\n";
							}
							else {
								if ($mkt_sku['sku'] != $variacao['id'] ) {
									echo "Erro ".$prd['id']." ".$skumkt." ".$mkt_sku['sku']." ".$variacao['id']."\n";
									$sql ='UPDATE marketplace_prd_variants SET sku = ? WHERE id= ?'; 
									$cmd = $this->db->query($sql,array($variacao['id'],$mkt_sku['id']));
								}
								else {
									// echo "variacaoo ok ".$skumkt." \n";
								} 
							} 
						}
					}
				}
				
			}
			
		}

    }

	function baixaVariacao()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo "Procurando produtos errados \n";
		$offset = 0; 

		$scroll_id = '';
		$cnt =0;
		$cntbling = 0;
		$cntok = 0;
		$ignore = array('EAN','GTIN','SELLER_SKU','EXCLUSIVE_CHANNEL','ITEM_CONDITION'); 
		
		$skumkt = 'MLB1579420767';
		// Leio o produto.
		$meli= $this->getkeys(1,0);
		$params = array(
			'access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->get($url, $params);
		if (!($retorno['httpCode']=="200") )  {  
			echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
			die;
			return;
		}
		$product = json_decode(json_encode($retorno['body']),true);
		
		var_dump($product);
		die;
		$sql ="SELECT * FROM bling_ult_envio WHERE skumkt = '".$skumkt."'";
		$query = $this->db->query($sql);
		$bling = $query->row_array();
		if (!$bling) {
			//echo $skumkt." não existe. Procurando o SKU \n";
		
			$skubling = '';
			foreach ($product['attributes'] as $attribute){
				if ($attribute['id'] == "SELLER_SKU") {
					$skubling = $attribute['values'][0]['name'];
					break;
				}						
			}
			if ((!is_null($product['seller_custom_field'])) && ($skubling == '')) {
				$skubling = $product['seller_custom_field'];
			}
			if ($skubling == ''){
				$cnt++;
				echo $skumkt.' não achei sku no ML '.$product['status']."\n";
				if ($product['status'] == 'active') {
					//rick $this->pauseML($skumkt);
				}
				
				die;
			}
			else {
				//echo 'achei o '.$skubling."\n";
				$sql ="SELECT * FROM bling_ult_envio WHERE skubling = '".$skubling."'";
				$query = $this->db->query($sql);
				$bling = $query->row_array();
				if (!$bling) {
					
					echo $skumkt.' não tem no bling_ult_envio '.$product['status']."\n";
					$cnt++;
					if ($product['status'] == 'active') {
						//rick $this->pauseML($skumkt);
					}
					die;
				} else {
					// Aqui eu acerto o bling_ult_envio e o prd_to_integrations 
					$sql = "UPDATE prd_to_integration SET skumkt = '".$skumkt."' where skubling ='".$skubling."' AND int_to = '".$this->getInt_to()."'";
					$cmd = $this->db->query($sql);
					$sql = "UPDATE bling_ult_envio SET skumkt = '".$skumkt."' where skubling ='".$skubling."' AND int_to = '".$this->getInt_to()."'";
					$cmd = $this->db->query($sql);
				}
			}
			
		}
		if ($bling['int_to'] != $this->getInt_to()) {
			echo $bling['prd_id'].' '.$skumkt.' '.$skubling." marcado para ".$bling['int_to']." erradamente ".$product['status']."\n";
			if ($product['status'] == 'active') {
				//rick $this->pauseML($skumkt);
			}
			$cntbling++;
			die;
		}
		
		// aqui eu insiro os atributos específicos deste produto na nossa tabela produtos_atributos_marketplaces.
		/* rick
		foreach ($product['attributes'] as $attribute){
			if (!in_array($attribute['id'], $ignore)) {
				// echo $bling['prd_id'].' '.$attribute['id'].' '.$attribute['values'][0]['name']."\n";
				$prd_att = array(
					'id_product' => $bling['prd_id'],
					'id_atributo' => $attribute['id'], 
					'valor' => $attribute['values'][0]['name']
				); 
				$insert = $this->db->replace('produtos_atributos_marketplaces', $prd_att);  						
			}
		}
		*/
		
		// echo $skumkt." ok \n";
		$cntok++;
		$prd = $this->model_products->getProductData(0,$bling['prd_id']);
		
		if ($prd['has_variants']!="") {
			$meli= $this->getkeys(1,0);
			$params = array(
				'include_attributes'=>'all',
				'access_token' => $this->getAccessToken());
			
			$url = '/items/'.$skumkt;
			$retorno = $meli->get($url, $params);
			if (!($retorno['httpCode']=="200") )  {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
				die;
				return;
			}
			$product = json_decode(json_encode($retorno['body']),true);
			if (array_key_exists('variations', $product)) {
				//var_dump($product['variations']);
				foreach ($product['variations'] as $variacao) {
					$id = $variacao['id'];
					//if (!is_null($variacao['seller_custom_field'])) {
					//	$sku_variant = $variacao['seller_custom_field'];
					//	echo " variação no seller_custom_field\n";
					//}
					//else {
						foreach ($variacao['attributes'] as $attribute){
							if ($attribute['id'] == "SELLER_SKU") {
								$sku_variant = $attribute['values'][0]['name'];
								break;
							}
							$sku_variant = '';
						}
					//}
				//	echo ' SKU da variação = '.$sku_variant."\n";
					$variant = substr($sku_variant,strrpos($sku_variant,'-')+1);
					$mkt_sku = $this->model_products->getMarketplaceVariantsByFields($this->getInt_to(),0,$prd['id'],$variant);  // vejo se é uma variação nova ou antiga
					if (!$mkt_sku) {  // produto novo cadastro o novo ID. 
						$data = array (
							'prd_id' => $prd['id'],
							'variant' => $variant,
							'store_id' => 0,  // loja Conectala
							'company_id' => 1, // empresa conectala 
							'sku' => $variacao['id'],
							'int_to' => $this->getInt_to(),
						);
						$insert = $this->db->insert('marketplace_prd_variants', $data);
					}
				}
			}
		}
				
	

    }

	function limpaBlingUltEnvio()
	{
		$sql = 'SELECT * FROM bling_ult_envio WHERE int_to="ML" AND skumkt !="00" and EAN like "ML_EAN%"';
		$query = $this->db->query($sql);
		$blings = $query->result_array();
		$cnt = 0;
		foreach($blings as $bling){
			$sql = 'SELECT * FROM bling_ult_envio WHERE int_to="ML" AND prd_id = ? and EAN not like "ML_EAN%"';
			$query = $this->db->query($sql,array($bling['prd_id']));
			$errados = $query->result_array();
			foreach($errados as $errado) {
				if ((($errado['skumkt'] == $bling['skumkt']) || (substr($errado['skumkt'],0,3) != 'MLB')) && ($errado['skubling'] == $bling['skubling'])  && ($errado['data_ult_envio'] < $bling['data_ult_envio'])) {
					echo "remover id = ".$errado['id']."\n";
					$sql = 'DELETE FROM bling_ult_envio WHERE id= ? ';
					$cmd = $this->db->query($sql,array($errado['id']));
				}
				else {
					echo "Novo = ".$bling['skumkt']." velho= ".$errado['skumkt']."\n"; 
					$cnt++;
					
					$meli= $this->getkeys(1,0);
					$params = array(
						'access_token' => $this->getAccessToken());
					$url = '/items/'.$errado['skumkt'];
					$retorno = $meli->get($url, $params);
					$product = json_decode(json_encode($retorno['body']),true);
					if ($product['sold_quantity'] == 0) {
						echo " ainda não teve venda \n";
						if ($this->closeML($errado['skumkt'])) {
							$sql = 'DELETE FROM bling_ult_envio WHERE id= ? ';
							$cmd = $this->db->query($sql,array($errado['id']));
						}
					}
					else {
						echo " vendeu ".$product['sold_quantity']."\n" ;
					}
				}
			}
			
		}
		echo ' todos = '.$cnt."\n";
		
		$sql = 'SELECT * FROM bling_ult_envio WHERE int_to="ML" AND EAN not like "ML_EAN%"';
		$query = $this->db->query($sql);
		$blings = $query->result_array();
		$cnt = 0;
		foreach($blings as $bling){
			if (substr($bling['EAN'],0,6) == "IS_KIT") {
				continue;
			}
			$sql = 'SELECT * FROM bling_ult_envio WHERE int_to="ML" AND prd_id = ? and EAN like "ML_EAN%"';
			$query = $this->db->query($sql,array($bling['prd_id']));
			$errados = $query->result_array();
			if (count($errados) == 0) {
				//echo "nao achei outro ".$bling['id']." sku ".$bling['skumkt']."\n";
				$prd = $this->model_products->getProductData(0,$bling['prd_id']);
				$ean = $prd['EAN']; 
				if ($prd['is_kit'] == 1) {
					$ean ='IS_KIT'.$prd['id'];
				}
				else {
					$ean ='ML_EAN'.$prd['id']; // EAN para colocar no Bling_ult_envio. Não é importante ter EAN, então crio um EAN único para cada produto
				}
				$sql = "UPDATE bling_ult_envio SET EAN = ? where id = ?";
				$cmd = $this->db->query($sql,array($ean,$bling['id']));
							
				if ((substr($bling['skumkt'],0,3) != 'MLB') && ($bling['skumkt'] != '00')) {
					$sku = $bling['skumkt'];
					echo "vou acertar ".$bling['prd_id']." o EAN ".$bling['EAN'] ." e o sku ". $bling['skumkt']. " novo ean ".$ean."\n";
					$sql = "UPDATE bling_ult_envio SET skumkt = ? where id = ?";
					$cmd = $this->db->query($sql,array('00',$bling['id']));
					$sql = "UPDATE prd_to_integration SET skumkt = ? where skumkt = ? AND int_to='ML'";
					$cmd = $this->db->query($sql,array('00',$sku));
				}
				else {
					echo "vou acertar ".$bling['prd_id']." so o EAN ". $bling['EAN']. " novo ean ".$ean."\n";
				}
				
			}
			else {
				foreach ($errados as $errado) {
					echo "Novo = ".$bling['id']." velho= ".$errado['id']."\n"; 
					continue;
				}
				
			}
		}
	}

	
	
	function syncEstoquePreco()
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
    	
        $sql = "SELECT * FROM prd_to_integration WHERE status_int=2 AND status=1 AND int_type=13 AND int_to='".$this->getInt_to()."'";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		foreach ($data as $key => $row) 
	    {
	    	if (substr($row['skumkt'],0,3) != 'MLB') {
	    		continue;
	    	}
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			if ($prd['date_update'] < $row['date_last_int']) { // não precisa atualizar....
				continue;
			}
			// acerto o preço do produto com o preço da promoção se tiver 
			//$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"ML");
			//$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
			// e ai vejo se tem campanha 
			//$prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$this->getInt_to());
			
			$sql = "SELECT * FROM bling_ult_envio WHERE int_to='".$this->getInt_to()."' AND prd_id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$bling_ult_envio = $cmd->row_array();
			if ($bling_ult_envio) {
				$skumkt =$bling_ult_envio['skumkt'];
				echo "SKUMKT =".$skumkt."\n";
				if (substr($skumkt,0,3) != 'MLB') {
	    			continue;
	    		}
				if ($skumkt != $row['skumkt'] ) {
					echo "**************************************************************************\n";
					echo " O SKU da prd_to_integration não bate com o do bLing_ult_envio\n";
					echo " prd = ".$row['skumkt']." bling =".$skumkt."\n";
					continue;
				}
			}
			else {
				continue;
			}
			echo " enviando produto ".$row['prd_id'].'-'.$row['skumkt']."\n";
			
			// Pego o preço a ser praticado 
			$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"ML");
			// $prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
			// e ai vejo se tem campanha 
			// $prd['price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to());
			
    		$sku = $row['skubling'];
			
			$qty_salvo = $prd['qty'];
			$qty_atual = (int) $prd['qty'] * $estoqueIntTo[$row['int_to']] / 100; 
			$qty_atual = ceil($qty_atual); // arrendoda para cima 
			if  ((int)$prd['qty'] < 5) { // Mando só para a B2W se a quantidade for menor que 5. 
				$qty_atual = 0;
			}
			$prd['qty'] = $qty_atual;

			$resp = $this->alteraPrecoEstoque($prd,$sku,$estoqueIntTo[$this->getInt_to()], $skumkt, $ad_link, $quality, $vendas);    
			
			if (!$resp) {
				echo "proximo\n";
				continue; 
			} else { 
				// TROUXE PRA DENTRO DO SUCESSO
				$int_date = date('Y-m-d H:i:s');
				$sql = "UPDATE prd_to_integration SET date_last_int = ?, ad_link =?, quality =? WHERE id = ".$row['id'];
				$cmd = $this->db->query($sql,array($int_date,$ad_link,$quality));
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
				// echo 'SQL = '. $sql."\n";
				// echo 'lido ='. print_r($lido,true)."\n";
				
				$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];

	        	$data = array(
	        		'int_to' => $this->getInt_to(),
	        		'company_id' => $prd['company_id'],
	        		'EAN' => $bling_ult_envio['EAN'],
	        		'prd_id' => $row['prd_id'],
	        		'price' => $prd['price'],
	        		'qty' => $qty_salvo,
	        		'sku' => $prd['sku'],
	        		'reputacao' => $bling_ult_envio['reputacao'],
	        		'NVL' =>  $bling_ult_envio['NVL'],
	        		'mkt_store_id' =>  $bling_ult_envio['mkt_store_id'],         
	        		'data_ult_envio' => $int_date,
	        		'skubling' => $sku,
	        		'skumkt' => $skumkt,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $prd['qty'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id'], 
	        		'marca_int_bling' => $bling_ult_envio['marca_int_bling'], 
					'categoria_bling'=> $bling_ult_envio['categoria_bling'],
	        		'crossdocking' => $crossdocking, 
					
	        	);
				if ($bling_ult_envio) {
					$insert = $this->db->replace('bling_ult_envio', $data);
					//$insert = $this->model_blingultenvio->update($data, $bling_ult_envio['id']);
				}else {
					$insert = $this->db->replace('bling_ult_envio', $data);
				}
					
			}
	    }
        return "PRODUCTS Synced with ML";
    } 

	function alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, &$skumkt,&$ad_link, &$quality, &$vendas) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 

		$novo_produto = false;
		if (substr($skumkt,0,3) != 'MLB') {
			return false;
			
		} else {
			echo "Vou alterar SKUMKT =".$skumkt."\n";
		}	
		$status = $this->verificaStatusProduto($skumkt, $vendas);
		if ($status == false) {
			echo "não foi possível ler o produto no ML ".$skumkt."\n";
			return false;
		}elseif ($status == 'under_review') {
			echo "Oferta com status ".$status." no marketplace para ".$skumkt.". Não vou alterar preço e estoque mas tentar enviar de novo\n";
			return $skumkt;
		} elseif (($status != 'active')  && ($status != 'paused')) {
			echo "Oferta com status ".$status." no marketplace para ".$skumkt."\n";
			$this->errorTransformation($prd['id'],$skubling, "Oferta com status ".$status." no marketplace");
			if ($status == 'closed') { // mercado livre detonou nosso produto.... 
				// rejeitado pelo Mercado Livre 
				$sql = "UPDATE prd_to_integration SET approved = 4  WHERE int_to = ? AND prd_id = ? AND skumkt = ?";
				$cmd = $this->db->query($sql, array($this->getInt_to(),$prd['id'],$skumkt));
			}
		    return false;
		} 	
		
		$produto = array(); 
		$prazo = $prd['prazo_operacional_extra']; 
		if ($prazo < 1 ) { $prazo = 1; }
		$produto = array(
			"sale_terms" => array(
     			array (
        			"id" => "MANUFACTURING_TIME",
        			"value_name" =>  $prazo." dias",
     			)
  			),
		);
		if ($prd['has_variants']=="") {  // se não tem variação, o preço e quantidade fazem parte do produto, caso contrário, faz parte da variação. 
			$produto["available_quantity"] = ((int)$prd['qty']<0) ? 0 : (int)$prd['qty'];
			$produto["price"] = (float)$prd['price'];
		}
		else {
			$prd_variacao = array();
			$variations = array();
            $prd_vars = $this->model_products->getProductVariants($prd['id'],$prd['has_variants']);
            // var_dump($prd_vars);
            $tipos = explode(";",$prd['has_variants']);
            // var_dump($tipos);
			
			foreach($prd_vars as $value) {
				// var_dump($value);
			  	if (isset($value['sku'])) {
			  		$mkt_sku = $this->model_products->getMarketplaceVariantsByFields($this->getInt_to(),0,$prd['id'],$value['variant']);  // vejo se é uma variação nova ou antiga
					if (!$mkt_sku) {
						echo " variação ainda não cadastrada \n";
						return false;
					}
					$qty_atual = (int)$value['qty'] * $estoqueIntTo / 100; 
					if ($qty_atual < 0) { $qty_atual=0;}
					$variacao = array(
						"price" => (float)$prd['price'], // apesar de parecer mandar preços diferentes, o ML ignora e usa o mais alto 
						"available_quantity" => ceil($qty_atual),
					);
					$variacao['id'] = $mkt_sku['sku'];
					$variations[] = $variacao;
				}	

			}
			$produto['variations'] = $variations;	
			
		}
		

		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());

		echo "Alterado o produto ".$prd['id']." ".$prd['name']." skumkt = ".$skumkt."\n";
		echo print_r(json_encode($produto),true)."\n";
		$url = '/items/'.$skumkt;
		$retorno = $meli->put($url, $produto, $params);
		// var_dump($retorno);
		if ($retorno['httpCode'] == 504) { // Gateway Timeout. Vou tentar de novo
			sleep(120);
			$meli= $this->getkeys(1,0);
			$params = array('access_token' => $this->getAccessToken());
			$retorno = $meli->put($url, $produto, $params);
		}
		if ($retorno['httpCode'] == 500) { // Algum erro interno no ML. Vejamos se dá para tratar 
			$respostaML = json_decode(json_encode($retorno['body']),true);
			if (!is_null($respostaML)) { // é um json talvez possa ser tratado.
				if ($respostaML['message'] == 'The thread pool executor cannot run the task. The upper limit of the thread pool size has probably been reached. Current pool size: 1000 Maximum pool size: 1000') { 
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
					return;
				}
				if ($respostaML['message'] == 'Timeout waiting for idle object') { 
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
					return;
				}
				if ($respostaML['message'] == '[http-nio2-8080-exec-97] Timeout: Pool empty. Unable to fetch a connection in 0 seconds, none available[size:30; busy:30; idle:0; lastwait:100].') { 
					echo "Tentarei outra vez pois deu ".$retorno['httpCode']." ".$respostaML['message']."\n";
					sleep(60);
					$this->alteraPrecoEstoque($prd,$skubling,$estoqueIntTo, $skumkt,$ad_link, $quality, $vendas);
					return;
				}
			}
		}
		if ($retorno['httpCode'] == 401) { // expirou o token 
			$respostaML = json_decode(json_encode($retorno['body']),true);
			if ($respostaML['message'] == 'expired_token') {
				echo "Tentarei outra vez pois deu ".$retorno['httpCode']."\n";
				sleep(60); echo "Estourou o Limite\n";
				$meli= $this->getkeys(1,0);
				$params = array('access_token' => $this->getAccessToken());
				$retorno = $meli->put($url, $produto, $params);
			}
		}	
		/*
		if ($retorno['httpCode'] == 400) {
			
			$status = $this->verificaStatusProduto($skumkt);
			if ($status == 'active') {
				$this->errorTransformation($prd['id'],$skubling, json_encode($retorno['body']));
				echo json_encode($retorno['body'])."\n";
				die;
				return false;
			} elseif ($status == false) {
				echo "não foi possível ler o produto no ML ".$skumkt."\n";
				return false;
			}else {
				echo "Oferta com status ".$status." no marketplace para ".$skumkt."\n";
				$this->errorTransformation($prd['id'],$skubling, "Oferta com status ".$status." no marketplace");
				
				$int_date = date('Y-m-d H:i:s');
				$sql = "UPDATE prd_to_integration SET date_last_int = ? WHERE skumkt = ?";
				$cmd = $this->db->query($sql,array($int_date,$skumkt));
				$sql = "UPDATE bling_ult_envio SET data_ult_envio = ? WHERE skumkt = ?";
				$cmd = $this->db->query($sql,array($int_date,$skumkt));
				return false;
			} 
		}
		 */
		if (($retorno['httpCode'] == 400) && ($status != 'paused')){ // è um pause que não deixa acertar o produto
			echo "Oferta com status ".$status." no marketplace para ".$skumkt."\n";
			$this->errorTransformation($prd['id'],$skubling, "Oferta com status ".$status." no marketplace");
			return false;
		}
		if ($retorno['httpCode'] == 400) {
			$respostaML = json_decode(json_encode($retorno['body']),true);
			if ( $respostaML['error'] == 'validation_error')  {
				$errors = $respostaML['cause'];
				$errorTransformation = '';
				foreach ($errors as $erro) {
					if ($erro['type'] == 'error') {
						$errorTransformation .=  'ERRO: '.$erro['message'].' ! ';
					}
				}
				if ($errorTransformation == '') {
					$errorTransformation= json_encode($retorno['body']); 
				}
				$this->errorTransformation($prd['id'],$skubling, $errorTransformation);
				echo $errorTransformation."\n";
			}
			else {
				$this->errorTransformation($prd['id'],$skubling, json_encode($retorno['body']));
				echo json_encode($retorno['body'])."\n";
			}
			return false;
		}
		if  ($retorno['httpCode'] != 200)  {
				
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($produto,true),"E");
			return false;
		}
		$respostaML = json_decode(json_encode($retorno['body']),true);
		$skumkt =$respostaML['id'];
		echo " Codigo do Mercado Livre - ".$skumkt."\n";
		//var_dump($respostaML);
		$ad_link =$respostaML["permalink"];
		echo " link = ".$ad_link."\n"; 
		$quality = $respostaML["health"];
		echo " quality = ".$quality."\n";
		return $skumkt;
	
	} 

	function verificaStatusProduto($skumkt, &$vendas)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$vendas = 0; 
		$meli= $this->getkeys(1,0);
		$params = array(
			'access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->get($url, $params);
		if (!($retorno['httpCode']=="200") )  {  
			echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
			return false;
		}
		$product = json_decode(json_encode($retorno['body']),true);
		//var_dump($product);
		$vendas = $product['sold_quantity'];
		return $product['status'];
	}
	
	
	function apagaTudo()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		
		echo "Apagando os anuncios do mercado livre \n";
		$offset = 0; 
		$scroll_id = '';
		while (true){
			$meli= $this->getkeys(1,0);
			$params = array(
			//	'offset' => $offset,
				'search_type' => 'scan',
				'access_token' => $this->getAccessToken(),
				);
			if ($scroll_id != '') {
				$params['scroll_id'] = $scroll_id;
			}
			$url = '/users/'.$this->getSeller().'/items/search';
			$retorno = $meli->get($url, $params);
			if (!($retorno['httpCode']=="200") )  {  
				echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
				die;
				return;
			}
			$body = json_decode(json_encode($retorno['body']),true);
			$scroll_id = $body['scroll_id'];
			$paging = $body['paging'];
			$results = $body['results'];
			
			if (count($results)==0) {
				echo "acabou\n";
				break;
			}
			
			foreach ($results as $skumkt) {
				// Leio o produto.
				$meli= $this->getkeys(1,0);
				$params = array(
					'access_token' => $this->getAccessToken());
				$url = '/items/'.$skumkt;
				$retorno = $meli->get($url, $params);
				if (!($retorno['httpCode']=="200") )  {  
					echo "Erro na respota do ".$this->getInt_to().". httpcode=".$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno['body'],true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO no disable do produto no '.$this->getInt_to().' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->getInt_to().': '.print_r($retorno['body'],true),"E");
					die;
					return;
				}
				$product = json_decode(json_encode($retorno['body']),true);
				
				if (($product['status']=='closed') || ($product['status']=='under_review')) {
					// pula os encerrados	
					echo 'Deletando '.$skumkt."\n";
					$this->deleteML($skumkt);
				}
				else {
					echo 'Encerrando '.$skumkt."\n";
					$this->closeML($skumkt);
				}	
				
			}
			
		}

    }

	function closeML($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$pause = Array (
						'status' => 'closed'
				);
									
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->put($url, $pause, $params);
		if  ($retorno['httpCode'] != 200 ) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		return true;
	}
	
	function deleteML($skumkt) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$pause = Array (
						'deleted' => 'true'
				);
									
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		$url = '/items/'.$skumkt;
		$retorno = $meli->put($url, $pause, $params);
		if  ($retorno['httpCode'] != 200 ) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpCode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			echo " Dados enviados: ".print_r($pause,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpCode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true).' DADOS ENVIADOS:'.print_r($pause,true),"E");
			return false;
		}
		return true;
	}
	
}
?>
