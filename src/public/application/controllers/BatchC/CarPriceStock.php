<?php
/*
 
Envia estoque  e preço para todos os produtos ativos para o Carrefour 

*/   
class CarPriceStock extends BatchBackground_Controller {
	
	var $int_to='CAR';
	var $apikey='';
	var $site='';
	var $prd;
	var $variants;
	var $store = array(); 
	var $auth_data;
	var $integration_store;
	var $integration_main;
	var $reserveB2W = 5;

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
		$this->load->model('model_blingultenvio');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_errors_transformation');
		
		$this->load->model('model_products_catalog');
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
	
	// php index.php BatchC/CarPriceStock run null B2W
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
		if (!is_null($params)) {
			$this->int_to='CAR';
		}
		$this->getkeys(1,0);
		$retorno = $this->syncPriceQty();
		
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

	function syncPriceQty()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		$company_id = 1; // somente da conecta-la
		$store_id = 0;

		$table_carga = "carrefour_carga_ofertas_".$store_id;
		if ($this->db->table_exists($table_carga) ) {
			$this->db->query("TRUNCATE $table_carga");
		} else {
			$model_table = "carrefour_carga_ofertas_model";
			$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
		}
		echo "Tabela ".$table_carga."\n";
		
		$offset = 0;
		$limit = 100;
		$exist = true;
		echo "Lendo produtos integrados com status_int 2 ou 10\n";
		while($exist) {
			$prds_to = $this->model_integrations->getPrdIntegrationByIntToStatus($this->int_to,'1',array('2','10'),$offset,$limit );
			if (!$prds_to) {
				echo "acabou\n";
				$exist = false;
				break;
			}
			$offset += $limit; 
			foreach ($prds_to as $prd_to) {
				$this->prd=$this->model_products->getProductData(0,$prd_to['prd_id']);
				$this->variants = $this->model_products->getVariants($prd_to['prd_id']);
				
				$qty_original = $this->prd['qty'];
				if ((int)$this->prd['qty'] < $this->reserveB2W) { 
					$this->prd['qty']  = 0;
				}
				$qty = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arredondo para cima 
				// Pego o preço do produto
				$price = $this->getPrice(null);
				// se tiver Variação,  uso o estoque de cada variação
		    	if (!is_null($prd_to['variant'])) {
		    		$variant =  $this->variants[$prd_to['variant']]; 
		    		$qty_original = $variant['qty'];
					
					if ((int)$qty_original < $this->reserveB2W) { 
						$qty_original = 0;
					}
					$qty = ceil((int) $qty_original * $percEstoque / 100); // arredondo para cima 
					if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
						$this->variants[$prd_to['variant']]['price'] = $price;
					}
					
					//ricardo, por enquanto, o preço da variação é igual ao do produto. REMOVER DEPOIS QUE AS INTEGRAÇÔES ESTIVEREM CONCLUIDAS
					// $price = $this->getPrice($variant);
				}
				
				$oferta = array(
	    			'sku' =>  $prd_to['skubling'],
	    			'product_id' =>  $prd_to['skubling'],
	    			'product_id_type' => "SHOP_SKU",
	    			'description' => '',
	    			'internal_description' => $qty_original,
	    			'price' => $price, 
	    			'quantity' => $qty,
	    			'state' => '11',
	    			'update-delete' => 'update'
	    		);
				$sql = "SELECT * FROM ".$table_carga." WHERE sku = ?";
				$cmd = $this->db->query($sql,array($prd_to['skubling']));
				$exist = $cmd->row_array();
				if (!$exist) {
					$insert = $this->db->insert($table_carga, $oferta);
				}
			}
		}
		
		$offset = 0;
		$limit = 100;
		$exist = true;
		echo "Lendo produtos inativos ou incompletos que já foram integrados no passado\n";
		while($exist) {
			// Zero os inativos ou incompletos ou que estejam no bling com estoque zerado. 
			$sql = 'SELECT b.*, p.status, p.situacao, p.has_variants FROM bling_ult_envio b, products p WHERE b.prd_id=p.id AND (p.status!=1 OR p.situacao=1) AND b.int_to="'.$this->int_to.'"';
			$sql .= " LIMIT " . $limit . " OFFSET " . $offset;
			$cmd = $this->db->query($sql);
			$prds_to = $cmd->result_array();
			if (!$prds_to) {
				echo "acabou\n";
				$exist = false;
				break;
			}
			$offset += $limit; 
			foreach ($prds_to as $prd_to) {
				$this->prd=$this->model_products->getProductData(0,$prd_to['prd_id']);

				// Pego o preço do produto
				$price = $this->getPrice(null);
				
				// se tiver Variação,  uso o estoque de cada variação
		    	if (!is_null($prd_to['variant'])) {
		    		$variant =  $this->variants[$prd_to['variant']];
					 
					//ricardo, por enquanto, o preço da variação é igual ao do produto. REMOVER DEPOIS QUE AS INTEGRAÇÔES ESTIVEREM CONCLUIDAS
					// $price = $this->getPrice($variant);
				}
			
				$oferta = array(
	    			'sku' =>  $prd_to['skubling'],
	    			'product_id' =>  $prd_to['skubling'],
	    			'product_id_type' => "SHOP_SKU",
	    			'description' => '',
	    			'internal_description' => 0,
	    			'price' => $price, 
	    			'quantity' => 0,
	    			'state' => '11',
	    			'update-delete' => 'update'
	    		);
				$sql = "SELECT * FROM ".$table_carga." WHERE sku = ?";
				$cmd = $this->db->query($sql,array($prd_to['skubling']));
				$exist = $cmd->row_array();
				if (!$exist) {
					$insert = $this->db->insert($table_carga, $oferta);
				}
			}
		}

		
		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_".$store_id."_".date('dm').".csv";
		$sql = "SELECT * FROM ".$table_carga." LIMIT 1";
		$cmd = $this->db->query($sql);
		$products = $cmd->result_array();
		if (count($products)==0) {
			return ;
		}
		echo "Gerando o aquivo: ".$file_prod."\n";
		$myfile = fopen($file_prod, "w") or die("Unable to open file!");
		$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
						'state','update-delete'); 
	
		fputcsv($myfile, $header, ";");
		
		$offset = 0;
		$limit =100;
		$exist = true;
		while($exist) {
			$sql = "SELECT * FROM ".$table_carga;
			$sql .= " LIMIT " . $limit . " OFFSET " . $offset;
			$cmd = $this->db->query($sql);
			$products = $cmd->result_array();
			if (!$products) {
				echo "acabou\n";
				$exist = false;
				break;
			}
			$offset += $limit; 
			foreach($products as $prdcsv) {
				$prdcsv['internal_description'] ='';
				fputcsv($myfile, $prdcsv, ";");
			}
		}
		fclose($myfile);
		
		$url = 'https://'.$this->getSite().'/api/offers/imports';
		echo "Enviando o arquivo: ". $file_prod."\n";
		
		$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
		if ($retorno['httpcode'] != 201) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno,true),"E");
			return false;
		}
		//var_dump($retorno['content']);
		$resp = json_decode($retorno['content'],true);
		$import_id= $resp['import_id'];

		While(true) {
			sleep(10);
			$url = 'https://'.$this->getSite().'/api/offers/imports/'.$import_id;
			echo "chamando ".$url." \n";
			$restorno_get = $this->getCarrefour($url,$this->getApikey());
			if ($restorno_get['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
				echo " RESPOSTA ".$this->int_to.": ".print_r($restorno_get,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($restorno_get,true),"E");
				return false;
			}
			$resp = json_decode($restorno_get['content'],true);
			//var_dump($restorno_get['content']);
			if (($resp['status'] == "SENT") || ($resp['status'] == "COMPLETE") ){
				break;
			}
		}
		$log_import = array(
			'company_id'=> $company_id,
			'store_id' => $store_id,
			'file' => $file_prod,
			'status' => 0,
			'date_created' => $resp['date_created'],				
			'has_error_report' => $resp['has_error_report'],
			'import_id' => $resp['import_id'],
			'lines_in_error' => $resp['lines_in_error'],
			'lines_in_pending' => $resp['lines_in_pending'],
			'lines_in_success' => $resp['lines_in_success'],
			'lines_read' => $resp['lines_read'],
			'mode' => $resp['mode'],
			'offer_deleted' => $resp['offer_deleted'],
			'offer_inserted' => $resp['offer_inserted'],
			'offer_updated' => $resp['offer_updated'],
			'import_status' => $resp['status'],
		);
		$insert = $this->db->insert('carrefour_ofertas_import_log', $log_import);
		
		echo "Acertando a Bling_ult_envio\n";
		$offset = 0;
		$limit =100;
		$exist = true;
		while($exist) {
			$sql = "SELECT * FROM ".$table_carga;
			$sql .= " LIMIT " . $limit . " OFFSET " . $offset;
			$cmd = $this->db->query($sql);
			$products = $cmd->result_array();
			if (!$products) {
				echo "acabou\n";
				$exist = false;
				break;
			}
			$offset += $limit; 
			foreach($products as $prdcsv) {
				$bdata = array(
					'price' => $prdcsv['price'], 
					'qty' => $prdcsv['internal_description'], 
					'qty_atual' => $prdcsv['quantity'], 
				);
				$bling = $this->model_blingultenvio->getDataBySkyblingAndIntto($prdcsv['sku'], $this->int_to);
				if ($bling) {
					$this->model_blingultenvio->update($bdata,$bling['id']);
				}
				$cardata = array(
					'price' => $prdcsv['price'], 
					'qty_total' => $prdcsv['internal_description'], 
					'qty' => $prdcsv['quantity'], 
				);
				$car_ult = $this->model_car_ult_envio->getBySku($prdcsv['sku']);
				if ($car_ult) {
					$this->model_car_ult_envio->update($cardata,$car_ult['id']);
				}
			}
		}
			
	}

	function percEstoque() {
		
		$percEstoque = $this->model_settings->getValueIfAtiveByName(strtolower($this->int_to).'_perc_estoque');
		if ($percEstoque)
		   	return $percEstoque;
		else 
			return 100;
	} 

	public function getPrice($variant = null) 
	{
		$this->prd['price'] = round($this->prd['price'],2);
		// pego o preço por Marketplace 
		$old_price = $this->prd['price'];
		
		// pego o preço da variant 
		if (!is_null($variant)) {
			if ((float)trim($variant['price']) > 0) {
				$old_price = round($variant['price'],2);
				if ($old_price !== $this->prd['price']) {
					$this->log(" Produto ".$this->prd['id']." Variaçao ".$variant['variant']. " tem preço ".$old_price." na variação e preço normal ".$this->prd['price']);
				}
			}
		}
		
		// altero o preço para acertar o DE POR do marketplace. 
		$old_price  =  $this->model_products_marketplace->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $this->prd['has_variants']);
		if ($old_price !== $this->prd['price']) {
			$this->log(" Produto ".$this->prd['id']." tem preço ".$old_price." para ".$this->int_to." e preço normal ".$this->prd['price']);
		}

		// Pego o preço a ser praticado se tem promotion
		if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
			$price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $variant);
		}
		else
		{
			$price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to);
		}

		if ($old_price !== $price) {
			$this->log(' Produto '.$this->prd['id'].' tem preço promoção '.$price.' para '.$this->int_to.' e preço base '.$old_price);
		}
		return round($price,2);
	}

	public function log($msg)
	{
		echo $msg."\n";
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
		$header['httpcode'] = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
		
		if ($httpcode == 429) {
			sleep(60);
			return $this->postCarrefourFile($url,$api_key,$file, $import_mode);
		}
		
	    return $header;
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
			sleep(60);
			return $this->getCarrefour($url, $api_key);
		}
	    return $header;
	}
}
?>
