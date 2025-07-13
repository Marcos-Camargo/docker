<?php
/*
 
Verifica como foi a importação dos produtos e recupera possíveis erros 

*/   
 abstract class ProductsStatus extends BatchBackground_Controller {
	
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
		$this->load->model('model_products_marketplace');
		$this->load->model('model_stores');
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_log_integration_product_marketplace'); 
		$this->load->model('model_mirakl_products_import_log');
		$this->load->model('model_mirakl_offers_import_log');
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
		
		// pego os relatórios antigos 
		$imports_log = $this->model_mirakl_products_import_log->getOpenImportsByStore($this->int_to, $store_id); 
		
		// primeiro faço um update de todos os logs pendentes 
		foreach($imports_log as $import_log) {
			 $this->miraklApiP42($import_log);
		}
		
		// Leio outravez para pegar os erros novos 
		$imports_log = $this->model_mirakl_products_import_log->getOpenImportsByStore($this->int_to, $store_id); 
		foreach($imports_log as $import_log) {
			$semerro = true; 
			if (($semerro) && ($import_log['has_transformation_error_report']) && (is_null($import_log['processed_transformation_error']))) {
				$semerro = $this->carrefourApiP47($import_log['import_id']);
				if ($semerro) {
					$this->model_mirakl_products_import_log->update(array('processed_transformation_error' => date('Y-m-d H:i:s'))  ,$import_log['id']);
				}
			}
			if (($semerro) && ($import_log['has_transformed_file']) && (is_null($import_log['processed_transformed_file']))) {
				$semerro = $this->carrefourApiP46($import_log['import_id']);
				if ($semerro) {
					$this->model_mirakl_products_import_log->update(array('processed_transformed_file' => date('Y-m-d H:i:s'))  ,$import_log['id']);
				}
			}
			if (($semerro) && ($import_log['has_error_report']) && (is_null($import_log['processed_error_report']))) {
				$semerro = $this->carrefourApiP44($import_log['import_id']);
				if ($semerro) {
					$this->model_mirakl_products_import_log->update(array('processed_error_report' => date('Y-m-d H:i:s'))  ,$import_log['id']);
				}
			}
			if (($semerro) && ($import_log['has_new_product_report']) && (is_null($import_log['processed_new_product_report']))) {
				$semerro = $this->carrefourApiP45($import_log['import_id']);
				if ($semerro) {
					$this->model_mirakl_products_import_log->update(array('processed_new_product_report' => date('Y-m-d H:i:s'))  ,$import_log['id']);
				}
			}
			if (($semerro) && ($import_log['has_error_report']) && (is_null($import_log['processed_error_report']))) {
				$semerro = $this->carrefourApiP44($import_log['import_id']);
				if ($semerro) {
					$this->model_mirakl_products_import_log->update(array('processed_error_report' => date('Y-m-d H:i:s'))  ,$import_log['id']);
				}
			}
			if ($semerro) {
				if ($import_log['import_status'] == 'COMPLETE') {  // A transformação e importação acabou para este arquivo. Não preciso checar mais.
					$this->model_mirakl_products_import_log->update(array('status' => 1)  ,$import_log['id']);
				}
			}
		}		
	}	
	
	function miraklApiP42($import_log) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$import_id = $import_log['import_id'];
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id;
		echo "chamando ".$url." \n";
		$retorno_get = $this->getMirakl($url,$this->getApikey());
		if ($retorno_get['httpcode'] == 404) {
			$resp = json_decode($retorno_get['content'],true);
			if (array_key_exists('message', $resp)) {
				if 	($resp['message'] == "Import with identifier [".$import_id."] not found" ) {
					echo "Já removeram o import ".$import_id." do sistema do ".$this->int_to."\n";
					$this->model_mirakl_products_import_log->update(array('status' => 1,'import_status' => 'DELETED'),$import_id);
					return;
				}
			}
			echo "Deu ruim e não sei o que fazer aqui \n";
			var_dump($resp);
			die;
		}
		if ($retorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' P42 site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true),"E");
			return false;
		}
		$resp = json_decode($retorno_get['content'],true);

		$log_import = array(
				'date_created' 						=> $resp['date_created'],
				'has_error_report' 					=> $resp['has_error_report'],
				'has_new_product_report' 			=> $resp['has_new_product_report'],
				'has_transformation_error_report' 	=> $resp['has_transformation_error_report'],
				'has_transformed_file' 				=> $resp['has_transformed_file'],
				'import_id' 						=> $resp['import_id'],
				'import_status' 					=> $resp['import_status'],
				'transform_lines_in_error' 			=> $resp['transform_lines_in_error'],
				'transform_lines_in_success' 		=> $resp['transform_lines_in_success'],
				'transform_lines_read' 				=> $resp['transform_lines_read'],
				'transform_lines_with_warning' 		=> $resp['transform_lines_with_warning'],

			);
		$this->model_mirakl_products_import_log->update($log_import,$import_log['id']);
		return true;
	}
	
	function carrefourApiP44($import_id) 
	{ //recupera o report de erro de produtos adicionados (erro na hora de enriquecer e categorizar)
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id.'/error_report';
		echo "chamando ".$url." \n";
		$retorno_get = $this->getMirakl($url,$this->getApikey());
		if ($retorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' P44 site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true),"E");
			return false;
		}
		echo "import id = ".$import_id."\n";
	//	var_dump($retorno_get['content']);

		$lines= $this->breakLines($retorno_get['content']);
		// echo 'linhas = '.var_dump($lines);
		foreach($lines as $line) {
			//var_dump($line);
			if (($this->int_to == 'CAR') || ($this->int_to == 'H_CAR')) {
				$sku = $line['sku'];
				$skuvariant = $line['product-sku'];
			} elseif (($this->int_to == 'GPA') || ($this->int_to == 'H_GPA')) {
				$sku = $line['sku'];
				$skuvariant = $line['sku'];
			}
			
			$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($skuvariant, $this->int_to);
			if (empty($prd_to)) {
				echo " ApiP44 Produto com skubling ".$skuvariant." e int_to ".$this->int_to." não encontrado em prd_to_integration \n"; 
				$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$skuvariant." e int_to ".$this->int_to." não encontrado em prd_to_integration","E");
				return false;
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
				'int_to' 	=> $this->int_to,
				'prd_id' 	=> $prd_to['prd_id'],
				'function' 	=> 'Erro no envio do produto sku '.$skuvariant,
				'url' 		=> $url,
				'method' 	=> 'GET',
				'sent' 		=> 'Import id: '.$import_id,
				'response' 	=> $line['errors'],
				'httpcode' 	=> $retorno_get['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
			
			// gravo o novo erro
			echo "Produto ".$prd_to['prd_id']." skubling ".$skuvariant." int_to ".$this->int_to." ERRO: ".$line['errors']."\n"; 
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
	
	function carrefourApiP45($import_id) 
	{	// recupera o report de arquivos adicionados (prontos para ofertas)
	
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id."/new_product_report";
		echo "chamando ".$url." \n";
		$retorno_get = $this->getMirakl($url,$this->getApikey());
		if ($retorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' P45 site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true),"E");
			return false;
		}
		
		$lines= $this->breakLines($retorno_get['content']);
		foreach($lines as $line) {
			if (($this->int_to == 'CAR') || ($this->int_to == 'H_CAR')) {
				$sku = $line['sku'];
				if (strrpos($sku, "-") !=0) { // vejo se é uma variante de produto 
					$sku = substr($line['sku'], 0, strrpos($line['sku'], "-"));
					$skuvariant = $line['product-sku'];
				}
				else{
					$skuvariant=$sku;
				}
			} elseif (($this->int_to == 'GPA') || ($this->int_to == 'H_GPA')) {
				$sku = $line['sku'];
				$skuvariant = $line['sku'];
			}
			
			echo ' sku='.$sku.' variant='.$skuvariant.' ';

			$lastpost = $this->model_last_post->getDataBySkuLocalIntto($skuvariant,$this->int_to);
			if ($lastpost) {
				echo ' Produto '.$skuvariant.' já existe na '.$this->last_post_table_name.' Pulando'."\n";
				continue; 	
			}
			
			$sql = "SELECT * FROM prd_to_integration WHERE status_int IN (1,2,10,21,22,24) AND skubling = '".$skuvariant."' AND int_to='".$this->int_to."'";
			$query = $this->db->query($sql);
			$prd_to = $query->row_array();
			
			if (empty($prd_to)) {
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
			//	$variant = $variants[$variant_num];
				foreach ($variants as $varsearch) {
					if ($variant_num == $varsearch['variant']) {
						$variant = $varsearch;
						break;
					}
				}
			}
			
			$price  = $this->getPrice($prd, $variant);
			
			if ($prd['is_kit'] == 0) {
				$ean = $prd['ean'];
			}else {
				$ean = 'IS_KIT'.$prd['id'];
			}
			$sku_prd = $prd['sku'];
			if (!is_null($variant_num)) {
				$ean = $ean.'V'.$variant_num;
				$sku_prd = $variant['sku'];
				echo "sku da variaçao: ".$sku_prd."\n";
			}
			
			$cat_id = json_decode ( $prd['category_id']);
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
	    		'qty_total' 				=> 0,
	    		'sku' 						=> $sku_prd,
	    		'skulocal'					=> $skuvariant,
	    		'skumkt' 					=> $sku,     
	    		'date_last_sent' 			=> date('Y-m-d H:i:s'),
	    		'tipo_volume_codigo' 		=> $tipo_volume_codigo['codigo'], 
	    		'width' 					=> $prd['largura'],
	    		'height' 					=> $prd['altura'],
	    		'length'					=> $prd['profundidade'],
	    		'gross_weight' 				=> $prd['peso_bruto'],
	    		'crossdocking' 				=> $crossdocking, 
	    		'zipcode' 					=> preg_replace('/\D/', '', $loja['zipcode']), 
	    		'CNPJ' 						=> preg_replace('/\D/', '', $loja['CNPJ']),
	    		'freight_seller' 			=> $loja['freight_seller'],
				'freight_seller_end_point' 	=> $loja['freight_seller_end_point'],
				'freight_seller_type' 		=> $loja['freight_seller_type'],
	    	);
			
			$savedUltEnvio =$this->model_last_post->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
			if (!$savedUltEnvio) {
	            $notice = 'Falha ao tentar gravar dados na tabela '.$this->last_post_table_name;
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
				'method' 	=> 'GET',
				'sent' 		=> 'Import id: '.$import_id,
				'response' 	=> 'OK',
				'httpcode' 	=> $retorno_get['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
			
			// adiciono o produto na fila para mandar preço e estoque para a Mirakl 
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
	
	function carrefourApiP46($import_id) 
	{   // recupera o report de produtos que foram transformados (prontos para enriquecimento e categorização)
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id."/transformed_file";
		echo "chamando ".$url." \n";
		$retorno_get = $this->getMirakl($url,$this->getApikey());
		if ($retorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' P46 site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true),"E");
			return false;
		}
		$lines= $this->breakLines($retorno_get['content']);
		foreach($lines as $line) {
			if (($this->int_to == 'CAR') || ($this->int_to == 'H_CAR')) {
				$sku = $line['sku'];
				$skuvariant = $line['product-sku'];
			} elseif (($this->int_to == 'GPA') || ($this->int_to == 'H_GPA')) {
				$sku = $line['sku'];
				$skuvariant = $line['sku'];
			}
			$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($skuvariant,$this->int_to);
			if (empty($prd_to)) {
				$prd_to = $this->model_last_post->getDataBySkuLocalIntto($skuvariant, $this->int_to);
				if (empty($prd_to)) {
					echo " ApiP46 Produto com skubling ".$skuvariant." e int_to ".$this->int_to." não encontrado em prd_to_integration \n"; 
					$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$skuvariant." e int_to ".$this->int_to." não encontrado em prd_to_integration","E");
					return false;
				} else {
					echo "Produto ".$prd_to['prd_id']." skubling ".$skuvariant." int_to ".$this->int_to." mudou de EAN \n"; 
					continue; 
				}
			}
			echo "Produto ".$prd_to['prd_id']." skubling ".$skuvariant." int_to ".$this->int_to." Transformado OK \n"; 

			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
			
			$data_log = array( 
				'int_to' 	=> $this->int_to,
				'prd_id' 	=> $prd_to['prd_id'],
				'function' 	=> 'Produto pronto para enriquecimento / aprovação. sku '.$skuvariant,
				'url' 		=> $url,
				'method' 	=> 'GET',
				'sent' 		=> 'Import id: '.$import_id,
				'response' 	=> 'OK',
				'httpcode' 	=> $retorno_get['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
		return true;
	}
	
	function carrefourApiP47($import_id) 
	{   // recupera o report de erros de produtos transformados
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id."/transformation_error_report";
		echo "chamando ".$url." \n";
		$retorno_get = $this->getMirakl($url,$this->getApikey());
		if ($retorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true),"E");
			return false;
		}
		if ($retorno_get['content']=='') {
			return false;
		}
		$lines= $this->breakLines($retorno_get['content']);
		//var_dump($lines);
		foreach($lines as $line) {
			//var_dump($line);
			if (($this->int_to == 'CAR') || ($this->int_to == 'H_CAR')) {
				$sku = $line['sku'];
				$skuvariant = $line['product-sku'];
			} elseif (($this->int_to == 'GPA') || ($this->int_to == 'H_GPA')) {
				$sku = $line['sku'];
				$skuvariant = $line['sku'];
			}
			$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($skuvariant,$this->int_to);
			if (empty($prd_to)) {
				echo " ApiP47 Produto com skubling ".$sku." e int_to ".$this->int_to." não encontrado em prd_to_integration \n"; 
				$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->int_to." não encontrado em prd_to_integration","E");
				return false;
			}
			
			$error_msg= '';
			if ($line['errors'] != '') {
				$error_msg= 'Erro: '.$line['errors'];
			}
			if ($line['warnings'] != '') {
				$error_msg .= ' Alerta: '.$line['warnings'];
			}
			$error_msg = trim($error_msg);

			$trans_err = array(
				'prd_id' 				=> $prd_to['prd_id'],
				'skumkt' 				=> $skuvariant,
				'int_to' 				=> $this->int_to,
				'step' 					=> "Transformação ".$this->int_to,
				'carrefour_import_id' 	=> $import_id,
				'message' 				=> $error_msg,
				'status' 				=> 0,
			);
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
			
			echo "Produto: ".$prd_to['prd_id']." skulocal: ".$skuvariant." int_to: ".$this->int_to." ERRO: ".$error_msg."\n"; 
			$this->model_errors_transformation->create($trans_err);
			
			$data_log = array( 
				'int_to' 	=> $this->int_to,
				'prd_id' 	=> $prd_to['prd_id'],
				'function' 	=> 'Erro no envio do produto sku '.$skuvariant,
				'url' 		=> $url,
				'method' 	=> 'GET',
				'sent' 		=> 'Import id: '.$import_id,
				'response' 	=> $error_msg,
				'httpcode' 	=> $retorno_get['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
			
			if ($prd_to['status_int'] == 22) {  // se estava em cadastramento, volto para permitir alterar e re-enviar.
				$prd_upd = array (
					'status_int' 	=> 21,
				);
				$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);
			}
			
		}
		return true;
	}
	
	function checkOfertasStatus()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$store_id = 0; 
		
		$imports_log = $this->model_mirakl_offers_import_log->getOpenImportsByStore($this->int_to, $store_id);
		
		// Primeiro faço um update dos logs de ofertas  
		foreach($imports_log as $import_log) {
			$this->carrefourApiOF02($import_log);
		}
		foreach($imports_log as $import_log) {
			$semerro = true; 	
			echo "Import Log=".$import_log['import_id']."\n"; 	
			if (($import_log['has_error_report']) && (is_null($import_log['processed_error_report']))) {
				$semerro = $this->carrefourApiOF03($import_log['import_id']);
				if ($semerro) {
					$this->model_mirakl_offers_import_log->update(array('processed_error_report' => date('Y-m-d H:i:s')), $import_log['id']);
				}
			}
			if ($semerro) {
				if ($import_log['import_status'] == 'COMPLETE') {
					$this->model_mirakl_offers_import_log->update(array('status' => 1), $import_log['id']);
				}
			}
		}		
	}	

	function carrefourApiOF02($import_log) 
	{  //  verificar o andamento da importação de ofertas
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$import_id = $import_log['import_id'];
		$url = 'https://'.$this->getSite().'/api/offers/imports/'.$import_id;
		echo "chamando ".$url." \n";
		$retorno_get = $this->getMirakl($url,$this->getApikey());
		if ($retorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' OF02 site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true),"E");
			return false;
		}
		$resp = json_decode($retorno_get['content'],true);
		$log_import = array(
				'date_created' 		=> $resp['date_created'],				
				'has_error_report' 	=> $resp['has_error_report'],
				'import_id' 		=> $resp['import_id'],
				'lines_in_error' 	=> $resp['lines_in_error'],
				'lines_in_pending' 	=> $resp['lines_in_pending'],
				'lines_in_success' 	=> $resp['lines_in_success'],
				'lines_read' 		=> $resp['lines_read'],
				'mode' 				=> $resp['mode'],
				'offer_deleted' 	=> $resp['offer_deleted'],
				'offer_inserted' 	=> $resp['offer_inserted'],
				'offer_updated' 	=> $resp['offer_updated'],
				'import_status' 	=> $resp['status'],
			);
		$this->model_mirakl_offers_import_log->update($log_import, $import_log['id']);
		return true;
	}

	function carrefourApiOF03($import_id) 
	{    // Resgatar report de erro de ofertas:
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$url = 'https://'.$this->getSite().'/api/offers/imports/'.$import_id."/error_report";
		echo "chamando ".$url." \n";
		$retorno_get = $this->getMirakl($url,$this->getApikey());
		if ($retorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Mirakl '.$this->int_to.' OF03 site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($retorno_get,true),"E");
			return false;
		}
		if ($retorno_get['content']=='') {
			return false;
		}
		$lines= $this->breakLines($retorno_get['content']);
		//var_dump($lines);
		foreach($lines as $line) {
			$sku = $line['sku'];
			if ($sku == '') {
				continue;
			}
			$prd_to = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($sku, $this->int_to);
			
			if ($prd_to) {
				$data_log = array( 
					'int_to' 	=> $this->int_to,
					'prd_id' 	=> $prd_to['prd_id'],
					'function' 	=> 'Erro no envio da oferta estoque e preço '.$sku,
					'url' 		=> $url,
					'method' 	=> 'GET',
					'sent' 		=> 'Import id: '.$import_id,
					'response' 	=> $line['error-message'],
					'httpcode' 	=> $retorno_get['httpcode'],
				);
				$this->model_log_integration_product_marketplace->create($data_log);
			}
			
			if ($line['error-message'] =='The product does not exist') {
				echo ' APAGAR ---------------- '.$sku."\n";

				$lastpost  = $this->model_last_post->getDataBySkuLocalIntto($sku, $this->int_to);
				if ($lastpost) {
					echo "Removendo do '.$this->last_post_table_name.' --> ".$lastpost['id']."\n";
					$this->model_last_post->remove($lastpost['id']); 
				}
				
				if ($prd_to){
					// reseto o sku que sumiu 
					echo "Removendo skus do prd_to_integration  --> ".$prd_to['id']."\n";
					$prd_upd = array (
						'status_int' 	=> 21,
						);
					$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_to['id']);
					
					// adiciono o produto na fila para mandar preço e estoque para o carrefour 
					$data = array (
						'id'     => 0, 
						'status' => 0,
						'prd_id' => $prd_to['prd_id'],
						'int_to' => $this->int_to
					);
					$this->model_queue_products_marketplace->create($data);
				}
				
				continue;
			}
			if (empty($prd_to)){
				// Produto pode ter mudado EAN, mas como está com erro. continuo para o próximo
				echo " ApiOF03 Produto com skulocal ".$line['sku']." e int_to ".$this->int_to." não encontrado em prd_to_integration \n"; 
				$this->log_data('batch',$log_name, "ERRO: Produto com skulocal ".$line['sku']." e int_to ".$this->int_to." não encontrado em prd_to_integration","E");
				var_dump($line);
				continue;
			}
			$trans_err = array(
				'prd_id' 				=> $prd_to['prd_id'],
				'skumkt'				=> $line['sku'],
				'int_to' 				=> $this->int_to,
				'step' 					=> "Oferta ".$this->int_to,
				'carrefour_import_id' 	=> $import_id,
				'message' 				=> $line['error-message'],
				'status' 				=> 0,
			);
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->int_to);
			
			echo "Produto ".$prd_to['prd_id']." skulocal ".$sku." int_to ".$this->int_to." ERRO: ".$line['error-message']."\n"; 
			$this->model_errors_transformation->create($trans_err);
			
			$lastpost  = $this->model_last_post->getDataBySkuLocalIntto($sku, $this->int_to);
			if ($lastpost) {
				$this->model_last_post->update(array('qty'=>0,'qty_atual'=>0),$lastpost['id']);
			}

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
			
			//echo "line  = ", $line."\n";
			if (trim($line) != '') {
				if(count($header) == count(str_getcsv($line,";")))
				{
					$result[]= array_combine($header,str_getcsv($line,";"));
					//echo "linha ".$i." = ", $result[$i++]."\n";
				}
				else 
				{
					//echo 'header = '.print_r($header,true)."\n";
				  //	echo "linha ".$i." = ".print_r($line,true)."\n";
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
			echo "Deu 429\n";
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
			echo "Deu 429\n";
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
