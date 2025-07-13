<?php
/*
 * 
Le a tabela mirakl_new_products gerada pela Productsmirakl e cria e envia a planilha de novos produtos para o Marketplace
Depois limpa a tabela model_mirakl_new_products dos produtos enviados 
 * 
*/   
 abstract class SendProducts extends BatchBackground_Controller {
	
	var $int_to;
	var $auth_data;
	var $integration_main;
	var $integration_store;
	var $int_from = 'CONECTALA';
	var $store_id = 0;
	var $company_id = 1;
	var $dateLastInt;
	
	abstract function getAttributes($new_products);
	abstract function getCSVHeader();
	abstract function getCSVProduct($new_product) ;
	
	public $zera_estoque = array();
	
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

		$this->load->model('model_mirakl_new_products'); 
		$this->load->model('model_integrations'); 
		$this->load->model('model_stores');
		$this->load->model('model_log_integration_product_marketplace'); 
		$this->load->model('model_mirakl_products_import_log');

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
		
		$this->dateLastInt = date('Y-m-d H:i:s');
		
		if (!is_null($params)) {// se passou parametros, é do HUB e não da ConectaLa
			if ($params != 'null') {
				$store = $this->model_stores->getStoresData($params);
				if (!$store) {
					$msg = 'Loja '.$params.' passada como parametro não encontrada!'; 
					echo $msg."\n";
					$this->log_data('batch',$log_name,$msg,"E");
					return ;
				}
				$this->int_from = 'HUB';
				$this->int_to='H_CAR';
				$this->store_id = $store['id'];
				$this->company_id = $store['company_id'];
			}
		}
		$this->getkeys($this->company_id,$this->store_id);
		$retorno = $this->syncProducts();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys() {
		//pega os dados da integração. 
		$this->getIntegration(); 
		$this->auth_data = json_decode($this->integration_main['auth_data']);
	}

	function getIntegration() 
	{
		
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store_id,$this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				if ($this->integration_store['int_from'] == 'CONECTALA') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto('0',$this->int_to);
				}elseif ($this->integration_store['int_from'] == 'HUB') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store_id,$this->int_to);
				} 
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
	}
	
    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	

		echo "Processando produtos que precisam de cadastro no ".$this->int_to." \n";
		
		$new_products = $this->model_mirakl_new_products->getNewProductsByStore($this->int_to, $this->store_id);
		  
		if (count($new_products) ==0) {
			echo "Nenhum produto novo\n";
			return;
		}

		$this->getAttributes($new_products); 
		
		if ( !is_dir( FCPATH."assets/files/mirakl" ) ) {
		    mkdir( FCPATH."assets/files/mirakl" );       
		}

		$file_prod = FCPATH."assets/files/mirakl/".$this->int_to."_PRODUTOS_".$this->store_id.'-'.date('dmHi').".csv";
		$myfile = fopen($file_prod, "w") or die("Unable to open file!");
		
		fputcsv($myfile, $this->getCSVHeader(), ";");
		foreach($new_products as $key => $new_product) {
			echo $new_product['product_sku']."\n";
			$prdcsv = $this->getCSVProduct($new_product);
			
			fputcsv($myfile, $prdcsv, ";");
			$new_products[$key]['sent'] = $prdcsv;
			$this->model_mirakl_new_products->update(array('status'=>1),$new_product['product_sku']); // marco o produto em processamento. 
		}
		fclose($myfile);

		$url = 'https://'.$this->auth_data->site.'/api/products/imports';
		$url_log = $url;
		echo "Enviando arquivo: ".$file_prod."\n";
		$retorno = $this->postMiraklFile($url,$this->auth_data->apikey,$file_prod);
		if ($retorno['httpcode'] != 201) {
			echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA: ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno['httpcode']." RESPOSTA: ".print_r($retorno,true),"E");
			$this->model_mirakl_new_products->updateByStatusAndStoreid(array('status'=>0),'1',$this->store_id, $this->int_to);
			return false;
		}
		//var_dump($retorno['content']);
		$resp = json_decode($retorno['content'],true);
		$import_id= $resp['import_id'];
		
		While(true) {
			sleep(20);
			$url = 'https://'.$this->auth_data->site.'/api/products/imports/'.$import_id;
			echo "chamando ".$url." \n";
			
			$retorno_get = $this->getMirakl($url,$this->auth_data->apikey);
			if ($retorno_get['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno_get['httpcode']."\n"; 
				echo " RESPOSTA : ".print_r($retorno_get,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$retorno_get['httpcode']." RESPOSTA: ".print_r($retorno_get,true),"E");
				$this->model_mirakl_new_products->updateByStatusAndStoreid(array('status'=>0),'1',$this->store_id, $this->int_to);
				return false;
			}
			$resp = json_decode($retorno_get['content'],true);
			//var_dump($retorno_get['content']);
			var_dump($resp);
			if (($resp['import_status'] == "SENT") || ($resp['import_status'] == "COMPLETE") || ($resp['import_status'] == "TRANSFORMATION_WAITING")){
				break;
			}
			if ($resp['import_status'] == "FAILED") {
				$msg = "Erro ao enviar o arquivo no Mirakl {$this->int_to}: ".$resp['reason_status'] ; 
				echo $msg." \n"; 
				$this->log_data('batch',$log_name, $msg,"E");
				return false;
			}
		}
		$log_import = array(
			'int_to' 							=> $this->int_to,
			'company_id'						=> $this->company_id,
			'store_id' 							=> $this->store_id,
			'file' 								=> $file_prod,
			'status' 							=> 0,
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
		$insert = $this->model_mirakl_products_import_log->create($log_import);
		
		echo "Atualizando tabelas internas\n";
		foreach($new_products as $new_product) {  // gravo o log de envio do produto, acerto prd_to_integration e removo da tabela
			$data_log = array( 
				'int_to' => $this->int_to,
				'prd_id' => $new_product['prd_id'],
				'function' => 'Envio do produto '.$new_product['product_sku'].' Import id: '.$import_id,
				'url' => $url_log,
				'method' => 'POST',
				'sent' => json_encode($new_product['sent']),
				'response' => json_encode(json_decode($retorno['content'],true)),
				'httpcode' => $retorno['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
			
			$prd_to = $this->model_integrations->getPrdToIntegrationById($new_product['prd_to_integration_id']);
			if ($prd_to['status_int'] == 21) {
				$prd_upd = array (
					'status_int' 	=> 22,
					'date_last_int' => $this->dateLastInt,
				);
				$this->model_integrations->updatePrdToIntegration($prd_upd, $new_product['prd_to_integration_id']);
			}
			$this->model_mirakl_new_products->remove($new_product['product_sku']);
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
		if ($httpcode == 429) {
			echo "deu 429\n";
			sleep(60);
			return $this->getMirakl($url,$api_key);
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
			return $this->postMiraklFile($url, $api_key, $file, $import_mode);
		}
		
	    return $header;
	}

}
?>
