<?php
/*
 
Verifica os importes antigos procurando produtos cadastrados depois. Pega os últimos 15 dias. 

*/   
 class CarProductsVerifyImport extends BatchBackground_Controller {
	
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
		
		$from_date= date("Y-m-d H:i:s",time() - 60 * 60 * 24*14);
	 	if ($from_date < '2020-09-11 12:00:00') {$from_date= '2020-09-11 12:00:00';}  // remover depois 
		$to_date= date("Y-m-d H:i:s",time() - 60 * 60 * 24*1);
		$sql = "SELECT * FROM carrefour_cargas_import_log WHERE store_id = ? AND date_created > ? AND date_created < ? ORDER BY store_id, date_created";
		$query = $this->db->query($sql,array($store_id,$from_date,$to_date));
		$imports_log = $query->result_array();

		foreach($imports_log as $import_log) {
			$semerro = false;
			echo " Verificando novamente ".$import_log['import_id']." de ".$import_log['date_created']. "\n";
			if ($import_log['has_new_product_report']) {
				$semerro = $this->carrefourApiP45($import_log['import_id']);
				if ($semerro) {
					$sql = 'UPDATE carrefour_cargas_import_log SET processed_new_product_report=NOW() WHERE id = ?';
					$query = $this->db->query($sql,$import_log['id']);
				}
			}
			if ($import_log['has_error_report']) {
				$semerro = $this->carrefourApiP44($import_log['import_id']);
				if ($semerro) {
					$sql = 'UPDATE carrefour_cargas_import_log SET processed_error_report=NOW() WHERE id = '.$import_log['id'];
					$query = $this->db->query($sql);
				}
			}
		}		
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
			
			$sql = "SELECT * FROM bling_ult_envio WHERE skubling = '".$sku."' AND int_to='".$this->getInt_to()."'";
			$query = $this->db->query($sql);
			$bling = $query->result_array();
			if (count($bling) > 0) {
				echo ' Produto '.$sku.' já existe no BLing_ult_envio então foi cadastrado. Pulando erro'."\n";
				continue; 	
			}
			
			$errorExist = $this->model_errors_transformation->getErrorByProdIdCarrefour($prd_to['prd_id'],$import_id );
			if ($errorExist) {
				echo ' Erro do  '.$sku.' import '.$import_id. ' já cadastrado. Pulando erro'."\n";
				continue; 
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
		if ($restorno_get['httpcode'] == 429) {
			sleep(60);
			$restorno_get = $this->getCarrefour($url,$this->getApikey());
		}
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
			echo ' sku ='.$sku.' variant ='.$variant;
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
				}
			}
			echo "Produto ".$prd_to['prd_id']." skubling ".$sku." int_to ".$this->getInt_to()." Cadastrado OK \n"; 

			$sql = "SELECT * FROM products WHERE id = ".$prd_to['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();

			if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
				$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"CAR", $variant);
			}
			else
			{
				$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price'],"CAR");
			}

			//$prd['price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
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
