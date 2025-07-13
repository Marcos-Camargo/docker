<?php
/*
 
Baixa as Ofertas e acerta a bling_ult_envio e prd_to_integration 
 
*/   
class CarFindOffersNew extends BatchBackground_Controller {
	
	var $int_to='CAR';
	var $apikey='';
	var $site='';
	
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
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_freights');
		$this->load->model('model_integrations');
		$this->load->model('model_frete_ocorrencias');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_stores');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_products');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_log_integration_product_marketplace');
		$this->load->model('model_car_ult_envio');
		
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
	function setSite($site) {
		$this->site = $site;
	}
	function getSite() {
		return $this->site;
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
		
		/* faz o que o job precisa fazer */
		$this->getkeys(1,0);

		$this->baixaOfertas();
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	
	
	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setSite($api_keys['site']);
	}
	
	function baixaOfertas()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$offset = 0;
		//$offset = 4100;
		$existe = true;
		$zerar= array();
		$matar = array();
		while($existe){
			echo " vou buscar offertas com offset ".$offset."\n";
			$url = 'https://'.$this->getSite().'/api/offers?offset='.$offset.'&max=100';
			$retorno = $this->getCarrefour($url, $this->getApikey());
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
				return false;
			}
			$product = json_decode($retorno['content'],true);
			//var_dump($product);
			if (count($product['offers']) == 0) {
				echo "acabou\n";
				break;
			}
			foreach ($product['offers'] as $oferta) {
				$status ='inativo';
				if ($oferta['active']) {
					$status ='ativo';
				}
				echo " oferta ".$oferta['shop_sku']."-".$oferta['product_title']." com status ".$status."\n";
				
				$sku= $oferta['shop_sku']; 
				
				$row_ult = $this->model_blingultenvio->getDataBySkyblingAndIntto($sku,$this->getInt_to());

				$variant = '';
				$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($sku, $this->getInt_to());
				if (is_null($row_ult)) {
					if (is_null($prd_to)) {
						//produto não existe nem na bling nem na prd
						echo "Não achei nem na Bling nem na Prdto ".$oferta['shop_sku']." \n";
						$matar[] = $oferta['shop_sku'];					
						continue;	
					}
					echo "Achei no prd_to_integration $sku\n";

					$prd = $this->model_products->getProductData(0,$prd_to['prd_id']);
					$variants = $this->model_products->getVariants($prd['id']);
			
					$variant = null;
					$variant_num = null;
					$skumkt = $sku;
					if (strrpos($oferta['shop_sku'], "-") !=0) { // vejo se é uma variante de produto 
						$variant_num = substr($oferta['shop_sku'], strrpos($oferta['shop_sku'], "-")+1);
						foreach ($variants as $varsearch) {
							if ($variant_num == $varsearch['variant']) {
								$variant = $varsearch;
								break;
							}
						}
						$skumkt = substr($oferta['shop_sku'],0,strrpos($oferta['shop_sku'], "-")); 
					}
					
					echo " SKU: ".$sku." SKUMKT: ".$skumkt."\n";
					
					$price  = $this->getPrice($prd, $variant);
					if ($prd['is_kit'] == 1) {
						$ean ='IS_KIT'.$prd['id'];
					}
					else {
						$ean ='NO_EAN'.$prd['id']; 
					}
					$sku_prd = $prd['sku'];
					if (!is_null($variant)) {
						$ean = $ean.'V'.$variant['variant'];
						$sku_prd = $variant['sku'];
					}
					$zerar[] = array (
						'sku' => $oferta['shop_sku'],
						'price' =>$price,
					);	

					$cat_id = json_decode ( $prd['category_id']);
					$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id =".intval($cat_id[0]).")";
					$cmd = $this->db->query($sql);
					$tipo_volume_codigo = $cmd->row_array();
					$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
					$loja  = $this->model_stores->getStoresData($prd['store_id']);
					
					$bling = array(
						'int_to' => $this->getInt_to(),
						'company_id' => $prd['company_id'],
						'EAN'=> $ean,
						'prd_id'=> $prd['id'], 
						'price'=> $price,
						'qty'=> 0,
						'sku'=> $sku_prd,
						'reputacao'=> 100,
						'NVL'=> 0,
						'mkt_store_id'=> 0,
						'data_ult_envio'=> '',
						'skubling'=> $sku,
						'skumkt'=> $skumkt,
						'tipo_volume_codigo'=> $tipo_volume_codigo['codigo'],
						'qty_atual'=> 0,
						'largura'=> $prd['largura'],
						'altura'=> $prd['altura'],
						'profundidade'=>$prd['profundidade'],
						'peso_bruto'=>$prd['peso_bruto'],
						'store_id'=> $prd['store_id'],
						'marca_int_bling'=> null,
						'categoria_bling'=> "CarFindOffersNew",
						'crossdocking' => $crossdocking,
						'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
        				'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
        				'freight_seller' =>  $loja['freight_seller'],
						'freight_seller_end_point' => $loja['freight_seller_end_point'],
						'freight_seller_type' => $loja['freight_seller_type'],
						'variant' => $variant_num
					);
					if ($prd_to['status'] == 0) {
						$status_int=90;
					}else {
						$status_int=1;
					}  

					// insiro no bling_ult_envio para que o produto deixe de ser novo começar a receber a carga de ofertas. 
					$savedUltEnvio= $this->model_blingultenvio->createIfNotExist($ean, $this->getInt_to(), $bling); 
					
					$datacar = array(
			    		'int_to' => $this->getInt_to(),
			    		'prd_id' => $prd['id'],
			    		'variant' => $variant_num,
			    		'company_id' => $prd['company_id'],
			    		'store_id' => $prd['store_id'], 
			    		'EAN' => $ean,
			    		'price' => $price,
			    		'qty' => 0,
			    		'qty_total' => 0,
			    		'sku' => $sku_prd,
			    		'skulocal' => $sku,
			    		'skumkt' =>$skumkt,     
			    		'date_last_sent' => date('Y-m-d H:i:s'),
			    		'tipo_volume_codigo' => $tipo_volume_codigo['codigo'], 
			    		'width' => $prd['largura'],
			    		'height' => $prd['altura'],
			    		'length' => $prd['profundidade'],
			    		'gross_weight' => $prd['peso_bruto'],
			    		'crossdocking' => $crossdocking, 
			    		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']), 
			    		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
			    		'freight_seller' => $loja['freight_seller'],
						'freight_seller_end_point' => $loja['freight_seller_end_point'],
						'freight_seller_type' => $loja['freight_seller_type'],
			    	);
					
					$savedUltEnvio =$this->model_car_ult_envio->createIfNotExist($this->int_to,$prd['id'], $variant_num, $datacar); 
					if (!$savedUltEnvio) {
			            $notice = 'Falha ao tentar gravar dados na tabela car_ult_envio.';
			            echo $notice."\n";
			            $this->log_data('batch', $log_name, $notice,'E');
						die;
			        } 
					
					// adiciono o produto na fila para mandar preço e estoque para o carrefour 
					$data = array (
						'id'     => 0, 
						'status' => 0,
						'prd_id' => $prd['id'],
						'int_to' => $this->getInt_to()
					);
					$this->model_queue_products_marketplace->create($data);	
					$data_log = array( 
						'int_to' => $this->getInt_to(),
						'prd_id' => $prd['id'],
						'function' => 'Aceito no Marketplace sku '.$sku,
						'url' => $url,
						'method' => 'GET',
						'sent' => '',
						'response' => json_encode($oferta),
						'httpcode' => $retorno['httpcode'],
					);
					$this->model_log_integration_product_marketplace->create($data_log);
				}
				else {
					$status_int =  $prd_to['status_int']; 
				}
				$prd_upd = array (
					'status_int' => $status_int,
					 'ad_link' => "https://www.carrefour.com.br/p/".$oferta['product_sku']
					);
				$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);	
					
			}
			$offset = $offset + 100; 
			if ($offset > 30000) {
			//	die;
			}
		}
		
		echo "IRIA MATAR\n";
		var_dump($matar);
		die;
		
		if ((count($matar)==0) && (count($zerar)==0)) {
			echo "nenhum produto novo\n";
			return ;
		}
		if ((count($matar)==0)) {
			echo "nenhum produto para matar\n";
			return ;
		}
		$store_id = 0; 
		$table_carga = "carrefour_carga_ofertas_".$store_id;
		if ($this->db->table_exists($table_carga) ) {
			$this->db->query("TRUNCATE $table_carga");
		} else {
			$model_table = "carrefour_carga_ofertas_model";
			$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
		}
	/* não faço mais isso pois coloquei o produto na fila novamente 
		foreach($zerar as $sku) {
			$oferta = array(
    			'sku' => $sku['sku'],
    			'product_id' => $sku['sku'],
    			'product_id_type' => "SHOP_SKU",
    			'description' => '',
    			'internal_description' => '',
    			'price' => $sku['price'], 
    			'quantity' => 0,
    			'state' => '11',
    			'update-delete' => 'update'
    		);
			$insert = $this->db->insert($table_carga, $oferta);
		}
		 
		 */
		 
		foreach($matar as $sku) {
			$oferta = array(
    			'sku' => $sku,
    			'product_id' => $sku,
    			'product_id_type' => "SHOP_SKU",
    			'description' => '',
    			'internal_description' => '',
    			'price' => 0, 
    			'quantity' => 0,
    			'state' => '11',
    			'update-delete' => 'delete'
    		);
			$insert = $this->db->insert($table_carga, $oferta);
		}
		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_ENCONTRADOS_".$store_id.".csv";
		
		$sql = "SELECT * FROM ".$table_carga;
		$query = $this->db->query($sql);
		$products = $query->result_array();
		if (count($products)) {
			$myfile = fopen($file_prod, "w") or die("Unable to open file!");
			$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
							'state','update-delete'); 
		
			fputcsv($myfile, $header, ";");
			foreach($products as $prdcsv) {
				fputcsv($myfile, $prdcsv, ";");
			}
			fclose($myfile);
			$url = 'https://'.$this->getSite().'/api/offers/imports';
			echo "chamando ".$url." \n";
			echo "file: ". $file_prod."\n";
			
			$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
			if ($retorno['httpcode'] != 201) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true),"E");
				return false;
			}
			//var_dump($retorno['content']);
			$resp = json_decode($retorno['content'],true);
			$import_id= $resp['import_id'];

		}
		
	}
	function baixaProdutos()
	{
		// esse não funciona que estoura o limite de conexões....
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$sql = 'SELECT * FROM prd_to_integration WHERE status_int=22 and int_to =?';
		$query = $this->db->query($sql, array($this->getInt_to()));
		$prds_to = $query->result_array();
		foreach ($prds_to as $prd_to) {
			
			$sql = "SELECT * FROM products WHERE id = ?";
			$cmd = $this->db->query($sql, array($prd_to['prd_id']));
			$prd = $cmd->row_array();
			if ($prd['has_variants'] != '') {
				$sql = "SELECT * FROM prd_variants WHERE prd_id = ?";
				$cmd = $this->db->query($sql, array($prd_to['prd_id']));
				$variants = $cmd->result_array();
				foreach ($variants as $variant) {
					$sku = $prd_to['skumkt'].'-'.$variant['variant'];
					if ($this->productExist($sku)) {
						echo ' ACHEI UM AQUI '."\n";
						die;
					}
					
				}
			}
			else {
				continue;
				$sku = $prd_to['skumkt'];
				if ($this->productExist($sku)) {
					echo ' ACHEI UM AQUI '."\n";
				}
				else {
					
				}
			}
			
		}
		
	}

	function productExist($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		sleep(5);
		echo 'Buscando '.$sku.' - ';
		$url = 'https://'.$this->getSite().'/api/offers?sku='.$sku;
		$retorno = $this->getCarrefour($url, $this->getApikey());
		if ($retorno['httpcode'] == 429) {
			sleep(600);
			$retorno = $this->getCarrefour($url, $this->getApikey());
		} 
		if ($retorno['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
			return false;
		}
		$product = json_decode($retorno['content'],true);
		var_dump($product);
		if ($product['total_count'] == 0) {
			echo "Não existe\n";
			return false;
		}
		else {
			echo "Existe\n";
			return true;
		}
		
	}
	
	function getCarrefour($url, $api_key){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_ENCODING 	   => "",
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_TIMEOUT        => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST  => "GET",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json',
				'Authorization: '.$api_key,
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
		if ($httpcode == 429) {
			echo "Deu 429\n";
			sleep(60);
			return $this->getCarrefour($url, $api_key);
		}
	    return $header;
	}
	
	function postCarrefourFile($url,$api_key,$file, $import_mode = ''){
		$options = array(
		  	CURLOPT_RETURNTRANSFER => true,
		  	CURLOPT_ENCODING => "",
		  	CURLOPT_MAXREDIRS => 10,
		  	CURLOPT_TIMEOUT => 0,
		  	CURLOPT_FOLLOWLOCATION => true,
		  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  	CURLOPT_CUSTOMREQUEST => "POST",
		  	CURLOPT_POSTFIELDS => array('file'=> new CURLFILE($file)),
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json',
				'content-type: multipart/form-data', 
				'Authorization: '.$api_key,
				)
	    );
		if ($import_mode != '') {
			$options[CURLOPT_POSTFIELDS] = array('file'=> new CURLFILE($file),'import_mode' => $import_mode );
		}
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
		if ($httpcode = 429) {
			sleep(60);
			return $this->postCarrefourFile($url,$api_key,$file, $import_mode);
		}
	    return $header;
	}

	public function getPrice($prd, $variant = null) 
	{
		$new_price = round($prd['price'],2);
		// pego o preço da variant 
		if (!is_null($variant)) {
			if ((float)trim($variant['price']) > 0) {
				$new_price = round($variant['price'],2);
			}
		}
		// altero o preço para acertar o DE POR do marketplace. Tem precedencia em relação ao preço por variação 
		$new_price  =  $this->model_products_marketplace->getPriceProduct($prd['id'],$new_price,$this->getInt_to(), $prd['has_variants']);

		// Pego o preço a ser praticado se tem promotion. Tem precedencia em relação ao preço por variação
		if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
			$price = $this->model_promotions->getPriceProduct($prd['id'],$new_price,$this->getInt_to(), $variant);
		}
		else
		{
			$price = $this->model_promotions->getPriceProduct($prd['id'],$new_price,$this->getInt_to());
		}


		return round($price,2);
	}
}
?>
