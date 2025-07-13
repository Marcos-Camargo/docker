<?php
/*
 
Realiza o Leilão de Produtos e atualiza o CAR 

*/   
 class CarProductsStatus extends BatchBackground_Controller {
	
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
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_campaigns');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_stores');
		
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
		
		echo "Rotina desativada\n";
		die;
		
		/* faz o que o job precisa fazer */
		$this->getkeys(1,0);
		$retorno = $this->checkProductStatus();
		$retorno = $this->productChangesOfertasStatus();
		$retorno = $this->checkOfertasStatus();

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
	
    function checkProductStatus()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$company_id = 1; // somente da conecta-la
		$store_id = 0;
		
		$sql = "SELECT * FROM carrefour_cargas_import_log WHERE store_id = ".$store_id." AND status=0 ORDER BY store_id, date_created";
		$query = $this->db->query($sql);
		$imports_log = $query->result_array();
		
		// primeiro faço um update de todos os logs pendentes 
		foreach($imports_log as $import_log) {
			 $this->carrefourApiP42($import_log);
		}
		
		// agora pego erros novos 
		$sql = "SELECT * FROM carrefour_cargas_import_log WHERE store_id = ".$store_id." AND status=0 ORDER BY store_id, date_created";
		$query = $this->db->query($sql);
		$imports_log = $query->result_array();
		foreach($imports_log as $import_log) {
			$semerro = true; 
			
			if (($semerro) && ($import_log['has_transformation_error_report']) && (is_null($import_log['processed_transformation_error']))) {
				$semerro = $this->carrefourApiP47($import_log['import_id']);
				if ($semerro) {
					$sql = 'UPDATE carrefour_cargas_import_log SET processed_transformation_error=NOW() WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
				}
			}
			if (($semerro) && ($import_log['has_transformed_file']) && (is_null($import_log['processed_transformed_file']))) {
				$semerro = $this->carrefourApiP46($import_log['import_id']);
				if ($semerro) {
					$sql = 'UPDATE carrefour_cargas_import_log SET processed_transformed_file=NOW() WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
				}
			}
			if (($semerro) && ($import_log['has_error_report']) && (is_null($import_log['processed_error_report']))) {
				$semerro = $this->carrefourApiP44($import_log['import_id']);
				if ($semerro) {
					$sql = 'UPDATE carrefour_cargas_import_log SET processed_error_report=NOW() WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
			
				}
			}
			if (($semerro) && ($import_log['has_new_product_report']) && (is_null($import_log['processed_new_product_report']))) {
				$semerro = $this->carrefourApiP45($import_log['import_id']);
				if ($semerro) {
					$sql = 'UPDATE carrefour_cargas_import_log SET processed_new_product_report=NOW() WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
				}
			}
			if ($semerro) {
				if ($import_log['import_status'] == 'COMPLETE') {  // A transformação e importação acabou para este arquivo. Não preciso checar mais.
					$sql = 'UPDATE carrefour_cargas_import_log SET status=1 WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
				}
			}
		}		
	}	
	
	function carrefourApiP42($import_log) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$import_id = $import_log['import_id'];
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id;
		echo "chamando ".$url." \n";
		$restorno_get = $this->getCarrefour($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Carrefour P42 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
			return false;
		}
		$resp = json_decode($restorno_get['content'],true);
		$log_import = array(
				'id' => $import_log['id'],
				'company_id'=> $import_log['company_id'],
				'store_id' => $import_log['store_id'],
				'file' => $import_log['file'],
				'status' => $import_log['file'],
				'date_created' => $resp['date_created'],
				'has_error_report' => $resp['has_error_report'],
				'has_new_product_report' => $resp['has_new_product_report'],
				'has_transformation_error_report' => $resp['has_transformation_error_report'],
				'has_transformed_file' => $resp['has_transformed_file'],
				'import_id' => $resp['import_id'],
				'import_status' => $resp['import_status'],
				'transform_lines_in_error' => $resp['transform_lines_in_error'],
				'transform_lines_in_success' => $resp['transform_lines_in_success'],
				'transform_lines_read' => $resp['transform_lines_read'],
				'transform_lines_with_warning' => $resp['transform_lines_with_warning'],
				'processed_transformed_file'  => $import_log['processed_transformed_file'],
				'processed_transformation_error' => $import_log['processed_transformation_error'],
				'processed_new_product_report' => $import_log['processed_new_product_report'],
				'processed_error_report' => $import_log['processed_error_report'],
			);
		// var_dump($log_import);
		$insert = $this->db->replace('carrefour_cargas_import_log', $log_import);
		return true;
	}
	
	function carrefourApiP44($import_id) 
	{ //recupera o report de erro de produtos adicionados (erro na hora de enriquecer e categorizar)
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id.'/error_report';
		echo "chamando ".$url." \n";
		$restorno_get = $this->getCarrefour($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Carrefour P44 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
			return false;
		}
		echo "import id = ".$import_id."\n";
	//	var_dump($restorno_get['content']);

		$lines= $this->breakLines($restorno_get['content']);
		// echo 'linhas = '.var_dump($lines);
		foreach($lines as $line) {
			//var_dump($line);
			$sku = $line['sku'];
			$variant = $line['product-sku'];
			$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$query = $this->db->query($sql);
			$prd_to = $query->row_array();
			if (empty($prd_to)) {
				echo " ApiP44 Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration \n"; 
				$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration","E");
				return false;
			}
			//var_dump($prd_to);
			$trans_err = array(
				'prd_id' => $prd_to['prd_id'],
				'skumkt' => $sku,
				'int_to' => $this->getInt_to(),
				'step' => "Importação Carrefour",
				'message' => $line['errors'],
				'carrefour_import_id' => $import_id,
				'status' => 0,
			);
			//var_dump($trans_err);
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->getInt_to());
			// gravo o novo erro
			echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." ERRO: ".$line['errors']."\n"; 
			$this->model_errors_transformation->create($trans_err);
			
			// volto novamente para 1 para enviar novamente amanhã e, se tudo correu direito, acertaram o erro. 
			$sql = "UPDATE prd_to_integration SET status_int=1 WHERE id=".$prd_to['id'];
			$cmd = $this->db->query($sql);
		}
		return true;
	}
	
	function carrefourApiP45($import_id) 
	{	// recupera o report de arquivos adicionados (prontos para ofertas)
	
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id."/new_product_report";
		echo "chamando ".$url." \n";
		$restorno_get = $this->getCarrefour($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Carrefour P45 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
			return false;
		}
		
		$lines= $this->breakLines($restorno_get['content']);
		foreach($lines as $line) {
			$sku = $line['sku'];
			$variant = $line['product-sku'];
			echo ' sku ='.$sku.' variant ='.$variant.' ';
			$sql = "SELECT * FROM bling_ult_envio WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$query = $this->db->query($sql);
			$bling = $query->result_array();
			if (count($bling) > 0) {
				echo ' Produto '.$sku.' já existe no BLing_ult_envio. Pulando'."\n";
				continue; 	
			}
			//$sql = "SELECT * FROM prd_to_integration WHERE (status_int = 22 OR status_int = 10) AND skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$sql = "SELECT * FROM prd_to_integration WHERE (status_int = 22 OR status_int = 10 OR status_int = 24 OR status_int = 1 OR status_int = 2) AND skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			
			$query = $this->db->query($sql);
			$prd_to = $query->row_array();
			if (empty($prd_to)) {
				if (strrpos($sku, "-") !=0) { // vejo se é uma variante de produto 
					$sku = substr($line['sku'], 0, strrpos($line['sku'], "-"));
					$sql = "SELECT * FROM prd_to_integration WHERE (status_int = 22 OR status_int = 10 OR status_int = 24 OR status_int = 1 OR status_int = 2) AND skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
					$query = $this->db->query($sql);
					$prd_to = $query->row_array();
				}
				if (empty($prd_to)) {
					
					// vejo se existe com outro status 
					$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
					$query = $this->db->query($sql);
					$prd_to = $query->row_array();
					if (empty($prd_to)) {
						echo " ApiP45: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration. Possivelmente mudou de EAN \n"; 
						$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration","E");
						continue;
					}
					else {
						echo " ApiP45: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." recebido antes. Novo status_int = ".$prd_to['status_int']." \n"; 
						continue;
					}
					//echo " ApiP45: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration \n"; 
					//$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration","E");
					//return false;
				}
			}
			echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." Cadastrado OK \n"; 

			$sql = "SELECT * FROM products WHERE id = ".$prd_to['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
			$old_price = $prd['price'];
			$prd['price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['price']) {
				echo " Produto ".$prd['id']." tem preço ".$prd['price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// acerto o preço do produto com o preço da promoção se tiver 
			$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"CAR");
			// $prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
			// e ai vejo se tem campanha 
			//$prd['price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to());
			
			$ean = $prd['EAN']; 
			if ($ean == '') {
				if ($prd['is_kit'] == 0) {
					$ean = 'NO_EAN'.$prd['id'];
				}else {
					$ean = 'IS_KIT'.$prd['id'];
				}
			}else {
				$ean = substr('0000000000'.$prd['EAN'],-13);
			}
			
			$cat_id = json_decode ( $prd['category_id']);
			$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
					 WHERE id =".intval($cat_id[0]).")";
			$cmd = $this->db->query($sql);
			$tipo_volume_codigo = $cmd->row_array();
			$crossdocking = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
			$loja  = $this->model_stores->getStoresData($prd['store_id']);
			
			$bling = array(
				'int_to' => $this->getInt_to(),
				'company_id' => $prd['company_id'],
				'EAN'=> $ean,
				'prd_id'=> $prd_to['prd_id'], 
				'price'=> $prd['price'],
				'qty'=> 0,
				'sku'=> $prd['sku'],
				'reputacao'=> 100,
				'NVL'=> 0,
				'mkt_store_id'=> 0,
				'data_ult_envio'=> '',
				'skubling'=> $sku,
				'skumkt'=> $sku,
				'tipo_volume_codigo'=> $tipo_volume_codigo['codigo'],
				'qty_atual'=> 0,
				'largura'=> $prd['largura'],
				'altura'=> $prd['altura'],
				'profundidade'=>$prd['profundidade'],
				'peso_bruto'=>$prd['peso_bruto'],
				'store_id'=> $prd['store_id'],
				'marca_int_bling'=> null,
				'categoria_bling'=> null,
				'crossdocking' => $crossdocking,
				'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        	'zipcode' => preg_replace('/\D/', '', $loja['zipcode']),
	        	'freight_seller' =>  $loja['freight_seller'],
				'freight_seller_end_point' => $loja['freight_seller_end_point'],
				'freight_seller_type' => $loja['freight_seller_type'],
			);
			$sql = "UPDATE prd_to_integration SET status_int=24, skumkt = '".$sku."'  WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$cmd = $this->db->query($sql);
			// insiro no bling_ult_envio para que o produto deixe de ser novo começar a receber a carga de ofertas. 
			$insert = $this->db->replace('bling_ult_envio', $bling);
			
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->getInt_to());
		}
		$sql = "UPDATE prd_to_integration SET status_int=1 WHERE status_int=24 AND int_to='".$this->getInt_to()."'";
		$cmd = $this->db->query($sql);
		return true;
	}
	
	function carrefourApiP46($import_id) 
	{   // recupera o report de produtos que foram transformados (prontos para enriquecimento e categorização)
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id."/transformed_file";
		echo "chamando ".$url." \n";
		$restorno_get = $this->getCarrefour($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Carrefour P46 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
			return false;
		}
		$lines= $this->breakLines($restorno_get['content']);
		foreach($lines as $line) {
			$sku = $line['sku'];
			$variant = $line['product-sku'];
			$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$query = $this->db->query($sql);
			$prd_to = $query->row_array();
			if (empty($prd_to)) {
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
				$query = $this->db->query($sql);
				$prd_to = $query->row_array();
				if (empty($prd_to)) {
					echo " ApiP46 Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration \n"; 
					$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration","E");
					return false;
				} else {
					echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." mudou de EAN \n"; 
					continue; 
				}
			}
			echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." Transformado OK \n"; 
			// $sql = "UPDATE prd_to_integration SET status_int=23 WHERE id=".$prd_to['id'];
			// $cmd = $this->db->query($sql);
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->getInt_to());
		}
		return true;
	}
	
	function carrefourApiP47($import_id) 
	{   // recupera o report de erros de produtos transformados
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$url = 'https://'.$this->getSite().'/api/products/imports/'.$import_id."/transformation_error_report";
		echo "chamando ".$url." \n";
		$restorno_get = $this->getCarrefour($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Carrefour P47 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
			return false;
		}
		if ($restorno_get['content']=='') {
			return false;
		}
		$lines= $this->breakLines($restorno_get['content']);
		//var_dump($lines);
		foreach($lines as $line) {
			$sku = $line['sku'];
			$variant = $line['product-sku'];
			$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$query = $this->db->query($sql);
			$prd_to = $query->row_array();
			if (empty($prd_to)) {
				echo " ApiP47 Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration \n"; 
				$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$sku." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration","E");
				return false;
			}
			$trans_err = array(
				'prd_id' => $prd_to['prd_id'],
				'skumkt' => $sku,
				'int_to' => $this->getInt_to(),
				'step' => "Transformação Carrefour",
				'carrefour_import_id' => $import_id,
				'message' => $line['errors'],
				'status' => 0,
			);
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->getInt_to());
			
			echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." ERRO: ".$line['errors']."\n"; 
			$this->model_errors_transformation->create($trans_err);
			
			// volto novamente para 1 para enviar novamente amanhã e, se tudo correu direito, acertaram o erro. 
			$sql = "UPDATE prd_to_integration SET status_int=1 WHERE id=".$prd_to['id'];
			$cmd = $this->db->query($sql);
		}
		return true;
	}
	
	function checkOfertasStatus()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
		$store_id = 0; 
		
		$sql = "SELECT * FROM carrefour_ofertas_import_log WHERE store_id = ".$store_id." AND status=0 ORDER BY store_id, date_created";
		$query = $this->db->query($sql);
		$imports_log = $query->result_array();
		
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
					$sql = 'UPDATE carrefour_ofertas_import_log SET processed_error_report=NOW() WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
				}
			}
			if ($semerro) {
				if ($import_log['import_status'] == 'COMPLETE') {
					$sql = 'UPDATE carrefour_ofertas_import_log SET status=1 WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
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
		$restorno_get = $this->getCarrefour($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Carrefour OF02 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
			return false;
		}
		$resp = json_decode($restorno_get['content'],true);
		$log_import = array(
				'id' => $import_log['id'],
				'company_id'=> $import_log['company_id'],
				'store_id' => $import_log['store_id'],
				'file' => $import_log['file'],
				'status' => $import_log['file'],
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
				'processed_error_report' => $import_log['processed_error_report'],
			);
		$insert = $this->db->replace('carrefour_ofertas_import_log', $log_import);
		return true;
	}

	function carrefourApiOF03($import_id) 
	{    // Resgatar report de erro de ofertas:
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$url = 'https://'.$this->getSite().'/api/offers/imports/'.$import_id."/error_report";
		echo "chamando ".$url." \n";
		$restorno_get = $this->getCarrefour($url,$this->getApikey());
		if ($restorno_get['httpcode'] != 200) {
			echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
			echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get Carrefour OF03 site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
			return false;
		}
		if ($restorno_get['content']=='') {
			return false;
		}
		$lines= $this->breakLines($restorno_get['content']);
		//var_dump($lines);
		foreach($lines as $line) {
			$sku = $line['sku'];
			if ($sku == '') {
				continue;
			}
			$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$query = $this->db->query($sql);
			$prd_to = $query->row_array();
			if (empty($prd_to)) {
				if (strrpos($sku, "-") !=0) { // vejo se é uma variante de produto 
					$sku = substr($line['sku'], 0, strrpos($line['sku'], "-"));
					$sql = "SELECT * FROM prd_to_integration WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
					$query = $this->db->query($sql);
					$prd_to = $query->row_array();
				}
				if (empty($prd_to)){
					// Produto pode ter mudado EAN, mas como está com erro. continuo para o próximo
					echo " ApiOF03 Produto com skubling ".$line['sku']." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration \n"; 
					$this->log_data('batch',$log_name, "ERRO: Produto com skubling ".$line['sku']." e int_to ".$this->getInt_to()." não encontrado em prd_to_integration","E");
					var_dump($line);
					continue;
				}
			}
			if ($line['error-message'] =='The product does not exist') {
				echo ' APAGAR ---------------- '.$sku."\n";
				//if ($this->removerProduto($prd_to)) { // remove as variações que podem ter sido cadastradas 
					// O produto sumiu do Carrefour. Removo no bling_ult_envio para que seja cadastrado novamente
					// Muito comum não cadastrarem todas as variações de um produto.
				//	echo ' APAGAR ---------------- '.$sku."\n";
				//	$sql = "DELETE FROM bling_ult_envio  WHERE int_to = '".$this->getInt_to()."' AND skubling = '".$sku."'";
				//	$cmd = $this->db->query($sql);
				//};
				
				continue;
			}
			$trans_err = array(
				'prd_id' => $prd_to['prd_id'],
				'skumkt' => $line['sku'],
				'int_to' => $this->getInt_to(),
				'step' => "Oferta Carrefour",
				'carrefour_import_id' => $import_id,
				'message' => $line['error-message'],
				'status' => 0,
			);
			// marco os erros antigos deste produto como resolvido
			$this->model_errors_transformation->setStatusResolvedByProductId($prd_to['prd_id'],$this->getInt_to());
			
			echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." ERRO: ".$line['error-message']."\n"; 
			$this->model_errors_transformation->create($trans_err);
			
			$sql = "UPDATE bling_ult_envio SET qty = '0',  qty_atual = '0', data_ult_envio = NOW() WHERE int_to = '".$this->getInt_to()."' AND skubling = '".$sku."'";
			$cmd = $this->db->query($sql);
			
			//$sql = "UPDATE prd_to_integration SET status_int=28 WHERE id=".$prd_to['id'];
			//$cmd = $this->db->query($sql);
		}
		return true;
	}

	function removerProduto($prd_to) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		$sql = 'SELECT * FROM products WHERE id=?';
		$query = $this->db->query($sql, array($prd_to['prd_id']));
		$prd = $query->row_array();
		if ($prd['has_variants'] |= '') {
			$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_APAGA_".$prd_to['prd_id'].".csv";
			$myfile = fopen($file_prod, "w") or die("Unable to open file!");
			$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
							'state','update-delete'); 
		
			fputcsv($myfile, $header, ";");
			
			$prd_vars = $this->model_products->getVariants($prd_to['prd_id']);
			$tipos = explode(";",$prd['has_variants']);
			//var_dump($tipos);
			foreach($prd_vars as $prd_var) {
				$oferta = array (
					 $prd_to['skubling']."-".$prd_var['variant'] , 
					 $prd_to['skubling']."-".$prd_var['variant'] ,
					 'SHOP_SKU','','',0,0,11,'delete'
					);
				fputcsv($myfile, $oferta, ";");		
				echo " VAriant ".$prd['id']." sku ".$prd_to['skubling']."-".$prd_var['variant']." marcado para delete \n";    		
			}

			fclose($myfile);
			$url = 'https://'.$this->getSite().'/api/offers/imports';
			echo "chamando ".$url." \n";
			echo "file: ". $file_prod."\n";
			sleep(60);
			
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
	
	function productChangesOfertasStatus() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$company_id = 1; // somente da conecta-la
		$store_id = 0;
		
		//guardo a hora para atualizar o bling uando acabar 
		$now = date('Y-m-d H:i:s');
		
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

		$sql = "SELECT b.* FROM bling_ult_envio b, products p WHERE p.id= b.prd_id AND data_ult_envio < p.date_update AND int_to='".$this->getInt_to()."'";
      	$query = $this->db->query($sql);
		$data = $query->result_array();
		if (count($data) > 0) {
			$table_carga = "carrefour_carga_ofertas_".$store_id;
			if ($this->db->table_exists($table_carga) ) {
				$this->db->query("TRUNCATE $table_carga");
			} else {
				$model_table = "carrefour_carga_ofertas_model";
				$this->db->query("CREATE TABLE $table_carga LIKE $model_table");
			}
		} 
		else {
			echo "Nenhum produto novo \n";
			return true;
		}
		
		foreach ($data as $key => $row) 
	    {
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
			// pego o preço por Marketplace 
			$old_price = $prd['price'];
			$prd['promotional_price'] =  $this->model_products_marketplace->getPriceProduct($prd['id'],$prd['price'],$this->getInt_to(), $prd['has_variants']);
			if ($old_price !== $prd['promotional_price']) {
				echo " Produto ".$prd['id']." tem preço preço ".$prd['promotional_price']." para ".$this->getInt_to()." e preço base ".$old_price."\n";
			}
			// acerto o preço da promoção do  produto com o preço da promoção se tiver 
			$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['promotional_price'],$this->getInt_to());
			//$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['promotional_price']);
			
			// e ai vejo se tem campanha 
			//$prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$this->getInt_to());
			
    		$sku = $row['skubling'];

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
			echo 'Processando produto Bling_ult_envio Id= '.$row['id'].' sku '.$row['skubling']."\n";
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$qty_salvo = $prd['qty'];
			$qty_atual = (int) $prd['qty'] * $estoqueIntTo[$row['int_to']] / 100; 
			$qty_atual = ceil($qty_atual); // arrendoda para cima 
			if  ((int)$prd['qty'] < 5) { // Mando só para a B2W se a quantidade for menor que 5. 
				$qty_atual = 0;
			}
			if (($prd['status']!=1)  || ($prd['situacao'] == '1')) {
				echo 'Produto Inativo ou incompleto '.$prd['id'].' sku '.$sku." - zerando \n";
				$qty_atual = 0;
			}
			if ($ean != $row['EAN']) {
				echo "Produto mudou de EAN ".$prd['id']." sku ".$sku." EAN prod=".$ean." EAN Bling=".$row['EAN']." - zerando \n";
				$qty_atual = 0;
			}
			
			echo " OFERTA ".$prd['id']." sku ".$sku." price ".$prd['promotional_price']." qty ".$qty_atual."\n"; 
    		$oferta = array(
    			'sku' => $sku,
    			'product_id' => $sku,
    			'product_id_type' => "SHOP_SKU",
    			'description' => '',
    			'internal_description' => '',
    			'price' => $prd['promotional_price'], 
    			'quantity' => $qty_atual,
    			'state' => '11',
    			'update-delete' => 'update'
    		);
			$ofertas_bling[] = array(
				'skubling' => $sku, 
				'price' => $prd['promotional_price'],
				'qty' => $prd['qty'], 
				'qty_atual' => $qty_atual,
			);
			if ($prd['has_variants']=="") {
				$exist = $this->db->get_where($table_carga, array('sku'=>$oferta['sku']))->result();  // vejo se já inseri para evitar duplicados
				if (!$exist) {
					$insert = $this->db->insert($table_carga, $oferta);
				}
			}
			else {
				echo "TEM variant \n";
				$prd_vars = $this->model_products->getVariants($row['prd_id']);
				$tipos = explode(";",$prd['has_variants']);
				//var_dump($tipos);
				foreach($prd_vars as $prd_var) {
					$oferta['sku'] = $sku."-".$prd_var['variant'];				//sku
					$oferta['product_id'] = $sku."-".$prd_var['variant'];			//product-id
					if ($qty_atual!=0) {
						$qty_atual = (int) $prd_var['qty'] * $estoqueIntTo[$this->getInt_to()] / 100; 
						if ($qty_atual<5) {
							$qty_atual = 0; 
						}
					}
					$oferta['quantity']= ceil($qty_atual);		//quantity	
					$exist = $this->db->get_where($table_carga, array('sku'=>$oferta['sku']))->result();  // vejo se já inseri para evitar duplicados
					if (!$exist) {
						$insert = $this->db->insert($table_carga, $oferta);
					}					
					echo " VAriant ".$prd['id']." sku ".$sku."-".$prd_var['variant']." price ".$prd['promotional_price']." qty ".$qty_atual."\n";    		
				}
			}
		}	

		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH.'assets/files/carrefour/CARREFOUR_OFERTAS_'.$store_id.'_'.date('YmdHi').'.csv';
		
		$sql = 'SELECT * FROM '.$table_carga;
		$query = $this->db->query($sql);
		$products = $query->result_array();
		if (count($products)) {
			$myfile = fopen($file_prod, 'w') or die("Unable to open file!");
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
				$this->log_data('batch',$log_name, 'ERRO no post no arquivo de ofertas site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($retorno,true),"E");
				return false;
			}
			// var_dump($retorno['content']);
			$resp = json_decode($retorno['content'],true);
			$import_id= $resp['import_id'];

			While(true) {
				sleep(10);
				$url = 'https://'.$this->getSite().'/api/offers/imports/'.$import_id;
				echo "chamando ".$url." \n";
				$restorno_get = $this->getCarrefour($url,$this->getApikey());
				if ($restorno_get['httpcode'] != 200) {
					echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
					echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO na verificação de ofertas site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
					return false;
				}
				$resp = json_decode($restorno_get['content'],true);
				// var_dump($restorno_get['content']);
				if (($resp['status'] == "SENT") || ($resp['status'] == "COMPLETE") ){
					break;
				}
				if ($resp['status'] == "FAILED" ){
					echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
					echo " RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true)." \n"; 
					$this->log_data('batch',$log_name, 'ERRO na verificação de ofertas site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($restorno_get,true),"E");
					return false;
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
			
			// atualizo o Bling_ult_envio considerando que deve ter passado tudo ok. Se der erro, zero no CarProductsStatus
			foreach($ofertas_bling as $oferta_bling) {
				
				$sql = "UPDATE bling_ult_envio SET price = ?, qty = ?, qty_atual = ?, data_ult_envio = ? WHERE int_to = ? AND skubling = ?";
				$cmd = $this->db->query($sql,array($oferta_bling['price'],$oferta_bling['qty'],$oferta_bling['qty_atual'],$now,$this->getInt_to(),$oferta_bling['skubling']));
				$status_int = 10;
				if ($oferta_bling['qty_atual'] > 0) { $status_int = 2 ; }
				$sql = "UPDATE prd_to_integration SET status_int = ?, date_last_int = ? WHERE int_to = ? AND skubling = ?";
				$cmd = $this->db->query($sql,array($status_int,$now,$this->getInt_to(),$oferta_bling['skubling']));
			}
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
