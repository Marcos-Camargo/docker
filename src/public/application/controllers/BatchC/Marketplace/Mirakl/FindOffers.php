<?php
/*
 
Busca produtos integrados no marketplace

*/   
abstract class FindOffers extends BatchBackground_Controller {
	
	var $int_to='';
	var $apikey='';
	var $site='';
	var $last_post_table_name; 
	var $model_last_post;

	abstract protected function lastPostModel(); 
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp'  => 1,
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
		$this->load->model('model_queue_products_marketplace');
		
		
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
	
	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setSite($api_keys['site']);
	}
	
	function getProducts()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/mcm/products/sources/status/export';
		echo $url."\n";
		$retorno = $this->getMirakl($url, $this->getApikey());
		if ($retorno['httpcode'] == 429) {
			sleep(60);
			$retorno = $this->getMirakl($url, $this->getApikey());
		} 
		if ($retorno['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
			return false;
		}

		$products = json_decode($retorno['content'],true);
		var_dump($products);

		foreach($products as $product) {

			$sku= $product['provider_unique_identifier'];
			if ($product['status'] == 'LIVE') {
				echo "Verificando ".$sku." que já está ativo\n";
				$row_ult  = $this->model_last_post->getDataBySkuLocalIntto($sku, $this->int_to);
				$variant = '';
				$atualizaLink = false;
				$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($sku, $this->int_to);
					
				if (is_null($row_ult)) { // não achei publicado
					echo "Ainda não está no Last_Post\n";
					if (!$prd_to) { // não achei em para publicar 
						//produto não existe
						$matar[] = $oferta['shop_sku'];					
						continue;	
					}
					echo "Achei no prd_to_integration ".$sku." para o ".$prd_to['prd_id']."\n";
					$this->model_integrations->updatePrdToIntegration(array('status_int' => 2), $prd_to['id']);
				
					$data_queue = array(
						'status' => 0,
						'prd_id' => $prd_to['prd_id'],
						'int_to' => $this->int_to,
					);
					$this->model_queue_products_marketplace->create($data_queue);
				}
				else {
					$atualizaLink = $row_ult['prd_id'];
				}
				if ($atualizaLink) {
					$url_link = "https://www.carrefour.com.br/p/".$oferta['product_sku']; 
					echo 'vou atualizar o '.$sku.' com o link '.$url_link."\n";
					$this->model_integrations->updatePrdToIntegration(array('ad_link' => $url_link), $prd_to['id']);	
				}
			}

		}

	}


	function baixaOfertas()
	{
		
		
		$offset = 0;
		$existe = true;
		$zerar= array();
		$matar = array();
		while($existe){
			echo " vou buscar offertas com offset ".$offset."\n";
			//$url = 'https://'.$this->getSite().'/api/ofers?offset='.$offset.'&max=100';
			$url = 'https://'.$this->getSite().'/api/products?product_references=EAN|7891116075586&offset='.$offset.'&max=100';
			
			$url = 'https://'.$this->getSite().'/api/mcm/products/sources/status/export';
			echo $url."\n";
			$retorno = $this->getMirakl($url, $this->getApikey());
			if ($retorno['httpcode'] == 429) {
				sleep(60);
				$retorno = $this->getMirakl($url, $this->getApikey());
			} 
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
				return false;
			}
			var_dump($retorno);
			die; 
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

				$row_ult  = $this->model_last_post->getDataBySkuLocalIntto($sku, $this->int_to);
				$variant = '';
				$atualizaLink = false;
				$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($sku, $this->int_to);
					
				if (is_null($row_ult)) { // não achei publicado
					if (!$prd_to) { // não achei em para publicar 
						//produto não existe
						$matar[] = $oferta['shop_sku'];					
						continue;	
					}
					echo "Achei no prd_to_integration $sku\n";
					$this->model_integrations->updatePrdToIntegration(array('status_int' => 2), $prd_to['id']);
				
					$data_queue = array(
						'status' => 0,
						'prd_id' => $prd_to['prd_id'],
						'int_to' => $this->int_to,
					);
					$this->model_queue_products_marketplace->create($data_queue);
				}
				else {
					$atualizaLink = $row_ult['prd_id'];
				}
				if ($atualizaLink) {
					$url_link = "https://www.carrefour.com.br/p/".$oferta['product_sku']; 
					echo 'vou atualizar o '.$sku.' com o link '.$url_link."\n";
					$this->model_integrations->updatePrdToIntegration(array('ad_link' => $url_link), $prd_to['id']);	
				}
					
			}
			$offset = $offset + 100; 
			if ($offset > 30000) {
				die;
			}
		}
		die; 

		if ((count($matar)==0) && (count($zerar)==0)) {
			echo "nenhum produto novo\n";
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
			if ($retorno['httpcode'] == 429) {
				sleep(60);
				$retorno = $this->postCarrefourFile($url,$this->getApikey(),$file_prod,"NORMAL");
			}
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
		$retorno = $this->getMirakl($url, $this->getApikey());
		if ($retorno['httpcode'] == 429) {
			sleep(600);
			$retorno = $this->getMirakl($url, $this->getApikey());
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
	
	function getMirakl($url, $api_key){
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
	    return $header;
	}
}
?>
