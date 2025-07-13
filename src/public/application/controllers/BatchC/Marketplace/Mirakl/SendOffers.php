<?php
/*
 * 
Le a tabela mirakl_new_offers gerada pela ProductsMirakl e cria e envia a planilha de novos produtos para o marketplace
Depois limpa a tabela model_mirakl_new_offers dos produtos enviados 
 * 
*/   
 abstract class SendOffers extends BatchBackground_Controller {
	
	var $int_to;
	var $auth_data;
	var $integration_main;
	var $integration_store;
	var $int_from = 'CONECTALA';
	var $store_id = 0;
	var $company_id = 1;
	var $dateLastInt;
	var $prd;
	var $variants;
	var $store;

	public $zera_estoque = array();

	abstract function getCSVHeader();
	abstract function getCSVProduct($offer) ;
	
	public function __construct()
	{
		parent::__construct();
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_mirakl_new_offers'); 
		$this->load->model('model_mirakl_offers_import_log');
		$this->load->model('model_integrations'); 
		$this->load->model('model_stores');
		$this->load->model('model_log_integration_product_marketplace'); 
		$this->load->model('model_errors_transformation');
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_products_marketplace');


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

		echo "Processando produtos que precisam de cadastro de Ofertas no ".$this->int_to." \n";
		
		$new_products = $this->model_mirakl_new_offers->getNewProductsByStore($this->store_id);
		  
		if (count($new_products) ==0) {
			echo "Nenhum produto novo\n";
			return;
		}
		
		if ( !is_dir( FCPATH."assets/files/mirakl" ) ) {
		    mkdir( FCPATH."assets/files/mirakl" );       
		}

		$file_prod = FCPATH."assets/files/mirakl/".$this->int_to."_OFERTAS_".$this->store_id."_".date('YmdHi').".csv";
		echo "Arquivo: ".$file_prod."\n";
		
		$myfile = fopen($file_prod, "w") or die("Unable to open file!");
	
		fputcsv($myfile, $this->getCSVHeader(), ";");
		
		foreach($new_products as $key => $offer) {
			echo $offer['skulocal']."\n";
			
			$prdcsv = $this->getCSVProduct($offer);
			fputcsv($myfile, $prdcsv, ";");
			$new_products[$key]['sent'] = $prdcsv;
			
			$this->model_mirakl_new_offers->update(array('status'=>1),$offer['skulocal']); // marco o produto em processamento. 
		}
		fclose($myfile);

		$url_imp = 'https://'.$this->auth_data->site.'/api/offers/imports';
		echo "chamando ".$url_imp." \n";
		echo "file: ". $file_prod."\n";
		
		$retorno = $this->postMiraklFile($url_imp,$this->auth_data->apikey,$file_prod,"NORMAL");
		if ($retorno['httpcode'] != 201) {
			echo " Erro URL: ". $url_imp. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA: ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url_imp.' - httpcode: '.$retorno['httpcode']." RESPOSTA: ".print_r($retorno,true),"E");
			
			die; // melhor morrer e deixar processar novamente na fila
			return false;
		}
		//var_dump($retorno['content']);
		$resp = json_decode($retorno['content'],true);
		$import_id= $resp['import_id'];

		While(true) {
			sleep(20);
			$url = 'https://'.$this->auth_data->site.'/api/offers/imports/'.$import_id;
			echo "chamando ".$url." \n";
			$restorno_get = $this->getMirakl($url,$this->auth_data->apikey);
			if ($restorno_get['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$restorno_get['httpcode']."\n"; 
				echo " RESPOSTA: ".print_r($restorno_get,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$restorno_get['httpcode']." RESPOSTA: ".print_r($restorno_get,true),"E");
				die; // melhor morrer de deixar processar novamente na fila
				return false;
			}
			$resp = json_decode($restorno_get['content'],true);
			//var_dump($restorno_get['content']);
			if (($resp['status'] == "SENT") || ($resp['status'] == "COMPLETE") ){
				break;
			}
		}
		
		$log_import = array(
			'int_to'=> $this->int_to,
			'company_id'=> $this->company_id,
			'store_id' => $this->store_id,
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
		$insert = $this->model_mirakl_offers_import_log->create($log_import);
		//var_dump($log_import); 
		
		foreach($new_products as $offer) {  // gravo o log de envio do produto, acerto prd_to_integration e removo da tabela
			$data_log = array( 
				'int_to' => $this->int_to,
				'prd_id' => $offer['prd_id'],
				'function' => 'Atualização Estoque/Preço '.$offer['skulocal'].' Import id: '.$import_id,
				'url' => $url_imp,
				'method' => 'POST',
				'sent' => json_encode($offer['sent']),
				'response' => json_encode(json_decode($retorno['content'],true)),
				'httpcode' => $retorno['httpcode'],
			);
			$this->model_log_integration_product_marketplace->create($data_log);
			
			$prd_to = $this->model_integrations->getPrdToIntegrationById($offer['prd_to_integration_id']);
			$prd_upd = array (
				'date_last_int' => $this->dateLastInt,
			);
			$this->model_integrations->updatePrdToIntegration($prd_upd, $offer['prd_to_integration_id']);
			
			$verstatus = $this->model_mirakl_new_offers->getData($offer['skulocal']);
			if ($verstatus) {
				if ($verstatus['status'] == 1) { // ninguém alterou, posso remover. 
					$this->model_mirakl_new_offers->remove($offer['skulocal']);
				}
			}	
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
