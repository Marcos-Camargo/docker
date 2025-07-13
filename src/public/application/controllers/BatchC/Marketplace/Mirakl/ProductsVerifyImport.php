<?php
/*
 
Verifica os importes antigos procurando produtos cadastrados depois. Pega os últimos 15 dias. 

*/   
 abstract class ProductsVerifyImport extends BatchBackground_Controller {
	
	var $int_to;
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
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_campaigns');
		$this->load->model('model_stores');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_log_integration_product_marketplace'); 
		$this->load->model('model_mirakl_products_import_log');
		
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
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->int_to,"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setSite($api_keys['site']);
	}
	
    function checkProductStatus()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$company_id = 1; // somente da conecta-la
		$store_id = 0;
		
		$from_date= date("Y-m-d H:i:s",time() - 60 * 60 * 24*14);
	 	if ($from_date < '2020-09-11 12:00:00') {$from_date= '2020-09-11 12:00:00';}  // remover depois 
		$to_date= date("Y-m-d H:i:s",time() - 60 * 60 * 24*1);
		
		$imports_log = $this->model_mirakl_products_import_log->getOldImports($this->int_to, $store_id,$from_date,$to_date);

		foreach($imports_log as $import_log) {
			$semerro = false;
			echo " Verificando novamente ".$import_log['import_id']." de ".$import_log['date_created']. "\n";
			if ($import_log['has_new_product_report']) {
				$semerro = $this->miraklApiP45($import_log['import_id']);
				if ($semerro) {
					$this->model_mirakl_products_import_log->update(array('processed_new_product_report'=>date('Y-d-m H:i:s')),$import_log['id']);
				}
			}
			if ($import_log['has_error_report']) {
				$semerro = $this->miraklApiP44($import_log['import_id']);
				if ($semerro) {
						
				}
			}
		}		
	}	
	
	function miraklApiP44($import_id) 
	{ //recupera o report de erro de produtos adicionados (erro na hora de enriquecer e categorizar)
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id.'/error_report';
		echo "chamando ".$url." \n";
		$restorno_get = $this->getMirakl($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' P44 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($restorno_get,true),"E");
			return false;
		}
		echo "import id = ".$import_id."\n";
	//	var_dump($restorno_get['content']);

		$lines= $this->breakLines($restorno_get['content']);
		// echo 'linhas = '.var_dump($lines);
		foreach($lines as $line) {
			//var_dump($line);
			$sku = $line['sku'];
			$skuvariant = $line['product-sku'];
			$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($skuvariant, $this->int_to);
			if (empty($prd_to)) {
				echo " ApiP44 Produto com skubling ".$skuvariant." e int_to ".$this->int_to." não encontrado em prd_to_integration \n"; 
				$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$skuvariant." e int_to ".$this->int_to." não encontrado em prd_to_integration","E");
				return false;
			}
			
			$lastpost  = $this->model_last_post->getDataBySkuLocalIntto($skuvariant, $this->int_to);
			if ($lastpost) {
				echo ' Produto '.$skuvariant.' já existe na '.$this->last_post_table_name.' então foi cadastrado. Pulando erro'."\n";
				continue; 	
			}
			
			$errorExist = $this->model_errors_transformation->getErrorByProdIdCarrefour($prd_to['prd_id'],$import_id);
			if ($errorExist) {
				echo ' Erro do  '.$skuvariant.' import '.$import_id. ' já cadastrado. Pulando erro'."\n";
				continue; 
			}

			$trans_err = array(
				'prd_id' 				=> $prd_to['prd_id'],
				'skumkt' 				=> $skuvariant,
				'int_to' 				=> $this->int_to,
				'step' 					=> "Importação ".$this->int_to,
				'message' 				=> $line['errors'],
				'carrefour_import_id' 	=> $import_id,
				'status' 				=> 0,
			);
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
			
			$data_log = array( 
				'int_to'	=> $this->int_to,
				'prd_id' 	=> $prd_to['prd_id'],
				'function' 	=> 'Erro no envio do produto sku '.$skuvariant,
				'url' 		=> $url,
				'method' 	=> 'GET',
				'sent' 		=> 'Import id: '.$import_id,
				'response'	=> $line['errors'],
				'httpcode' 	=> $restorno_get['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
			
			// gravo o novo erro
			echo "Produto ".$prd_to['prd_id']." skubling ".$variant." int_to ".$this->int_to." ERRO: ".$line['errors']."\n"; 
			$this->model_errors_transformation->create($trans_err);
			
			if ($prd_to['status_int'] == 22) {  // se estava em cadastramento, volto para permitir alterar e re-enviar.
				$prd_upd = array (
					'status_int' 	=> 21,
				);
				$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);
			}
			
		}
		return true;
	}
	
	function miraklApiP45($import_id) 
	{	// recupera o report de arquivos adicionados (prontos para ofertas)
	
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id."/new_product_report";
		echo "chamando ".$url." \n";
		$restorno_get = $this->getMirakl($url,$this->getApikey());
		if ($restorno_get['httpcode'] == 429) {
			sleep(60);
			$restorno_get = $this->getMirakl($url,$this->getApikey());
		}
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' P45 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($restorno_get,true),"E");
			return false;
		}
		
		$lines= $this->breakLines($restorno_get['content']);
		foreach($lines as $line) {
			if (($this->int_to == 'CAR') || ($this->int_to == 'H_CAR')) {
				$sku = $line['sku'];
				$skuvariant = $line['product-sku'];
			} elseif (($this->int_to == 'GPA') || ($this->int_to == 'H_GPA')) {
				$sku = $line['sku'];
				$skuvariant = $line['sku'];
			}
			echo ' sku ='.$sku.' variant ='.$skuvariant;  
			$lastpost  = $this->model_last_post->getDataBySkuLocalIntto($skuvariant, $this->int_to);
			if ($lastpost) {
				echo ' Produto '.$skuvariant.' já existe na '.$this->last_post_table_name.' então foi cadastrado. Pulando erro'."\n";
				continue; 	
			}
			//$sql = "SELECT * FROM prd_to_integration WHERE (status_int = 22 OR status_int = 10) AND skubling = '".$sku."' AND int_to='".$this->int_to."'";
			$sql = "SELECT * FROM prd_to_integration WHERE status_int in [1,2,10,21,22,24] AND skubling = ? AND int_to= ?";
			$query = $this->db->query($sql, array($skuvariant, $this->int_to));
			$prd_to = $query->row_array();
			
			if (empty($prd_to)) {  // NAO DEVERIA MAIS ACONTECER
				// vejo se existe com outro status 
				$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($skuvariant, $this->int_to);
				if (empty($prd_to)) {
					echo " ApiP45: Produto com skubling ".$skuvariant." e int_to ".$this->int_to." não encontrado em prd_to_integration. Possivelmente mudou de EAN \n"; 
					$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->int_to." não encontrado em prd_to_integration","E");
					continue;
				}
				else {
					echo " ApiP45: Produto com skubling ".$skuvariant." e int_to ".$this->int_to." recebido antes. Novo status_int = ".$prd_to['status_int']." \n"; 
					continue;
				}	
			}
			echo "Produto ".$prd_to['prd_id']." skubling ".$skuvariant." int_to ".$this->int_to." Cadastrado OK \n"; 
			
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
			
			// Leio o produto e suas variants se tiver 
			$prd = $this->model_products->getProductData(0,$prd_to['prd_id']);
			$variants = $this->model_products->getVariants($prd['id']);
			
			$variant = null;
			$variant_num = null;
			if (strrpos($skuvariant, "-") !=0) { // vejo se é uma variante de produto 
				$variant_num = substr($skuvariant, strrpos($skuvariant, "-")+1);
				foreach ($variants as $varsearch) {
					if ($variant_num == $varsearch['variant']) {
						$variant = $varsearch;
						break;
					}
				}
				
			}
			
			$price  = $this->getPrice($prd, $variant);
			if ($prd['is_kit'] == 1) {
				$ean ='IS_KIT'.$prd['id'];
			}
			else {
				$ean =$prd['EAN']; 
			}
			$sku_prd = $prd['sku'];
			if (!is_null($variant_num)) {
				$ean = $ean.'V'.$variant_num;
				$sku_prd = $variant['sku'];
			}
			
			$cat_id = json_decode($prd['category_id']);
			$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
					 WHERE id =".intval($cat_id[0]).")";
			$cmd = $this->db->query($sql);
			$tipo_volume_codigo = $cmd->row_array();
			$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
			$loja  = $this->model_stores->getStoresData($prd['store_id']);

			$data = array(
	    		'int_to' 					=> $this->int_to,
	    		'prd_id' 					=> $prd['id'],
	    		'variant' 					=> $variant_num,
	    		'company_id' 				=> $prd['company_id'],
	    		'store_id' 					=> $prd['store_id'], 
	    		'EAN' 						=> $ean,
	    		'price' 					=> $price,
	    		'list_price' 				=> $prd['price'],
	    		'qty' 						=> 0,
	    		'qty_total'	 				=> 0,
	    		'sku' 						=> $sku_prd,
	    		'skulocal' 					=> $skuvariant,
	    		'skumkt' 					=> $sku,     
	    		'date_last_sent' 			=> date('Y-m-d H:i:s'),
	    		'tipo_volume_codigo' 		=> $tipo_volume_codigo['codigo'], 
	    		'width' 					=> $prd['largura'],
	    		'height' 					=> $prd['altura'],
	    		'length' 					=> $prd['profundidade'],
	    		'gross_weight' 				=> $prd['peso_bruto'],
	    		'crossdocking' 				=> $crossdocking, 
	    		'zipcode' 					=> preg_replace('/\D/', '', $loja['zipcode']), 
	    		'CNPJ' 						=> preg_replace('/\D/', '', $loja['CNPJ']),
	    		'freight_seller' 			=> $loja['freight_seller'],
				'freight_seller_end_point' 	=> $loja['freight_seller_end_point'],
				'freight_seller_type' 		=> $loja['freight_seller_type'],
	    	);
			
			$savedUltEnvio = $this->model_last_post->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
			if (!$savedUltEnvio) {
	            $notice = 'Falha ao tentar gravar dados na tabela car_ult_envio.';
	            echo $notice."\n";
	            $this->log_data('batch', $log_name, $notice,'E');
				die;
	        } 
					
			$prd_upd = array (
				'skubling' 		=> $skuvariant,
				'skumkt' 		=> $sku,
				'status_int' 	=> 1,
				'variant' 		=> $variant_num,
				);
			$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);
			
			$data_log = array( 
				'int_to' 	=> $this->int_to,
				'prd_id' 	=> $prd['id'],
				'function' 	=> 'Aceito no Marketplace sku '.$skuvariant,
				'url' 		=> $url,
				'method'	=> 'GET',
				'sent' 		=> 'Import Id: '.$import_id,
				'response' 	=> 'OK',
				'httpcode' 	=> $restorno_get['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
			
			// adiciono o produto na fila para mandar preço e estoque para o markeptplace  
			$data = array (
				'id'     => 0, 
				'status' => 0,
				'prd_id' => $prd['id'],
				'int_to' => $this->int_to
			);
			$this->model_queue_products_marketplace->create($data);
		}
		return true;
	}
	
	function breakLines($texto)
	{
		$result = array();
		$linha_header = substr($texto,0,strpos($texto,PHP_EOL));
		//echo "linha hdr  = ", $linha_header."\n";
		$header = str_getcsv($linha_header,";");
		$texto = substr($texto,strpos($texto,PHP_EOL)+1);
		//echo "texto  = ", $texto."\n";
		$i=0;
		while ($texto!="") {
			if (strpos($texto,PHP_EOL) === false) {
				//echo " não tem PHP_EOL\n";
				$line= $texto; 
			} else {
				$line= substr($texto,0,strpos($texto,PHP_EOL)); 
			}
			
			echo "line  = ", $line."\n";
			if (trim($line) != '') {
				if(count($header) == count(str_getcsv($line,";")))
				{
					$result[]= array_combine($header,str_getcsv($line,";"));
					//echo "linha ".$i." = ", $result[$i++]."\n";
				}
				else 
				{
					//echo 'header = '.print_r($header,true)."\n";
				  	echo "linha ".$i." = ".print_r($line,true)."\n";
				}
			}
			if (strpos($texto,PHP_EOL) === false) {
				$texto = '';
			}
			else {
				$texto = substr($texto,strpos($texto,PHP_EOL)+1);
			}
			$i++;
		}
		return ($result);
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
		if ($httpcode == 429) {
			echo "deu 429\n";
			sleep(60);
			return $this->getMirakl($url, $api_key);
		}
	    return $header;
	}
	
	function postMiraklFile($url,$api_key,$file, $import_mode = ''){
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
		if ($httpcode == 429) {
			echo "deu 429\n";
			sleep(60);
			return $this->postMiraklFile($url,$api_key,$file, $import_mode);
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
		$new_price  =  $this->model_products_marketplace->getPriceProduct($prd['id'],$new_price,$this->int_to, $prd['has_variants']);

		// Pego o preço a ser praticado se tem promotion. Tem precedencia em relação ao preço por variação
		if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
			$price = $this->model_promotions->getPriceProduct($prd['id'],$new_price,$this->int_to, $variant);
		}
		else
		{
			$price = $this->model_promotions->getPriceProduct($prd['id'],$new_price,$this->int_to);
		}


		return round($price,2);
	}
	
}
?>
