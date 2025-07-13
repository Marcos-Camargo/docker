<?php
/*
 * 
Le a tabela carrefour_new_products gerada pela ProductsCar e cria e envia a planilha de novos produtos para o Carrefour
Depois limpa a tabela model_carrefour_new_offers dos produtos enviados 
 * 
*/   
 class CarSendOffers extends BatchBackground_Controller {
	
	var $int_to='CAR';
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
	
		
		$this->load->model('model_carrefour_new_offers'); 
		$this->load->model('model_integrations'); 
		$this->load->model('model_stores');
		$this->load->model('model_log_integration_product_marketplace'); 
		$this->load->model('model_errors_transformation');
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_car_ult_envio');
		$this->load->model('model_blingultenvio');

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
		
		$new_products = $this->model_carrefour_new_offers->getNewProductsByStore($this->store_id);
		  
		if (count($new_products) ==0) {
			echo "Nenhum produto novo\n";
			return;
		}
		
		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}

		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_".$this->store_id."_".date('YmdHi').".csv";
		echo "Arquivo: ".$file_prod."\n";
		
		$myfile = fopen($file_prod, "w") or die("Unable to open file!");
		$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
						'state','update-delete'); 
	
		fputcsv($myfile, $header, ";");
		
		foreach($new_products as $key => $offer) {
			echo $offer['skulocal']."\n";
			$prdcsv = array(
				'sku' => $offer['skulocal'],
				'product_id' => $offer['skulocal'],
				'product_id_type' => "SHOP_SKU",
				'description' => '',
				'internal_description' => '',
				'price' => $offer['price'], 
				'quantity' => ($offer['qty']<0) ? 0 : $offer['qty'],
				'state' => '11',
				'update-delete' => 'update'
			);
			fputcsv($myfile, $prdcsv, ";");
			$new_products[$key]['sent'] = $prdcsv;
			
			$this->model_carrefour_new_offers->update(array('status'=>1),$offer['skulocal']); // marco o produto em processamento. 
		}
		fclose($myfile);

		$url_imp = 'https://'.$this->auth_data->site.'/api/offers/imports';
		echo "chamando ".$url_imp." \n";
		echo "file: ". $file_prod."\n";
		
		$retorno = $this->postCarrefourFile($url_imp,$this->auth_data->apikey,$file_prod,"NORMAL");
		if ($retorno['httpcode'] != 201) {
			echo " Erro URL: ". $url_imp. " httpcode=".$retorno['httpcode']."\n"; 
			echo " RESPOSTA: ".print_r($retorno,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url_imp.' - httpcode: '.$retorno['httpcode']." RESPOSTA: ".print_r($retorno,true),"E");
			
			die; // melhor morrer de deixar processar novamente na fila
			return false;
		}
		//var_dump($retorno['content']);
		$resp = json_decode($retorno['content'],true);
		$import_id= $resp['import_id'];

		While(true) {
			sleep(20);
			$url = 'https://'.$this->auth_data->site.'/api/offers/imports/'.$import_id;
			echo "chamando ".$url." \n";
			$restorno_get = $this->getCarrefour($url,$this->auth_data->apikey);
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
		$insert = $this->db->insert('carrefour_ofertas_import_log', $log_import);
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
			
			/* 
			$this->prd = $this->model_products->getProductData(0,$offer['prd_id']);
			$this->variants = $this->model_products->getVariants($this->prd ['id']);
			$this->prepareProduct();
			
			if (is_null($offer['variant'])) {
				$this->prd['qty']= $offer['qty'];
				$this->updateBlingUltEnvio($this->prd, null,$offer['skumkt'], $offer['skulocal']);
				$this->updateCARUltEnvio($this->prd, null,$offer['skumkt'], $offer['skulocal']);
			}
			else {
				$variant = $this->variants[$offer['variant']];
				$prd= $this->prd;
				$prd['sku'] = $variant['sku'];
				$prd['qty'] = $offer['qty'];
				$prd['price'] = $variant['price'];
				$prd['qty_original'] = $variant['qty_original'];
				$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;		
				$this->updateBlingUltEnvio($prd, $variant, $offer['skumkt'], $offer['skulocal']);
				$this->updateCARUltEnvio($prd, $variant, $offer['skumkt'], $offer['skulocal']);
			}
			
			// limpa os erros de transformação existentes da fase de Oferta Carrefour
			$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Oferta Carrefour');
			*/
			$verstatus = $this->model_carrefour_new_offers->getData($offer['skulocal']);
			if ($verstatus) {
				if ($verstatus['status'] == 1) { // ninguém alterou, posso remover. 
					$this->model_carrefour_new_offers->remove($offer['skulocal']);
				}
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
		if ($httpcode == 429) {
			echo "deu 429\n";
			sleep(60);
			return $this->getCarrefour($url,$api_key);
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
		if ($httpcode == 429) {
			echo "deu 429\n";
			sleep(60);
			return $this->postCarrefourFile($url, $api_key, $file, $import_mode);
		}
		
	    return $header;
	}
	
	function prepareProduct() {  // apagar depois 
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$this->prd['qty_original'] = $this->prd['qty'];
		if ((int)$this->prd['qty'] < 5) { // Mando só para a B2W se a quantidade for menor que 5. 
			$this->prd['qty']  = 0;
		}
		
		// Pego o preço do produto
		$this->prd['price'] = $this->getPrice(null);
		
		// se é a conectaLá não usa EAN para o produto
		if ($this->int_to=='CAR') {
			$this->prd['EAN'] = null;
		}
		// se tiver Variação,  acerto o estoque de cada variação
    	if ($this->prd['has_variants']!='') {
    		$variações = explode(";",$this->prd['has_variants']);
			
			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
				$this->variants[$key]['qty_original'] =$variant['qty'];
				if  ((int)$this->variants[$key]['qty'] < 5) { // Mando só para a B2W se a quantidade for menor que 5. 
					$this->variants[$key]['qty'] = 0;
				}
				$this->variants[$key]['price'] = $this->prd['price'];
				
				// se é a conectaLá não usa EAN para o produto
				if ($this->int_to=='CAR') { 
					$this->variants[$key]['EAN'] = null;
				}
			}
		}

		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1 ) { $this->prd['prazo_operacional_extra'] = 1; }

		$cat_id = json_decode ($this->prd['category_id']);
		$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
				 WHERE id =".intval($cat_id[0]).")";
		$cmd = $this->db->query($sql);
		$tipo_volume_codigo = $cmd->row_array();
		$this->prd['tipovolumecodigo'] = $tipo_volume_codigo['codigo'];
		
		$this->store = $this->model_stores->getStoresData($this->prd['store_id']);

		return true;
	}
	
	public function getPrice($variant = null) 
	{
		$this->prd['price'] = round($this->prd['price'],2);
		// pego o preço por Marketplace 
		$old_price = $this->prd['price'];

		// altero o preço para acertar o DE POR do marketplace. 
		$old_price  =  $this->model_products_marketplace->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $this->prd['has_variants']);

		// Pego o preço a ser praticado se tem promotion
		if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
			$price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $variant);
		}
		else
		{
			$price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to);
		}


		return round($price,2);
	}
	
	function updateBlingUltEnvio($prd, $variant = null, $skumkt, $skubling) 
	{
			
		// EAN para colocar no Bling_ult_envio. Não é importante ter EAN, então crio um EAN único para cada produto
		if ($prd['is_kit'] == 1) {
			$ean ='IS_KIT'.$prd['id'];
		}
		else {
			$ean ='NO_EAN'.$prd['id']; 
		}
		if (!is_null($variant)) {
			$ean = $ean.'V'.$variant['variant'];
		}
    	$data = array(
    		'int_to' => $this->int_to,
    		'company_id' => $prd['company_id'],
    		'EAN' => $ean,
    		'prd_id' => $prd['id'],
    		'price' => $prd['price'],
    		'qty' => $prd['qty_original'],
    		'sku' => $prd['sku'],
    		'reputacao' => 100,
    		'NVL' => 100,
    		'mkt_store_id' => '',         
    		'data_ult_envio' => $this->dateLastInt,
    		'skubling' => $skubling,
    		'skumkt' => $skumkt,
    		'tipo_volume_codigo' => $prd['tipovolumecodigo'], 
    		'qty_atual' => $prd['qty'],
    		'largura' => $prd['largura'],
    		'altura' => $prd['altura'],
    		'profundidade' => $prd['profundidade'],
    		'peso_bruto' => $prd['peso_bruto'],
    		'store_id' => $prd['store_id'], 
    		'marca_int_bling' => null, 
			'categoria_bling'=> null,
    		'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'], 
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
			'variant' => (is_null($variant)) ? $variant : $variant['variant'],
    	);
		
		$savedUltEnvio= $this->model_blingultenvio->createIfNotExist($ean, $this->int_to, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela bling_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 	
	}
	
	function updateCARUltEnvio($prd, $variant = null, $skumkt, $skubling) 
	{
		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];
		if ($prd['EAN'] == '') {
			if ($prd['is_kit'] == 1) {
				$ean ='IS_KIT'.$prd['id'];
			}
			else {
				$ean ='NO_EAN'.$prd['id']; 
			}
			if (!is_null($variant_num)) {
				$ean = $ean.'V'.$variant_num;
			}
		}

    	$data = array(
    		'int_to' => $this->int_to,
    		'prd_id' => $prd['id'],
    		'variant' => $variant_num,
    		'company_id' => $prd['company_id'],
    		'store_id' => $prd['store_id'], 
    		'EAN' => $ean,
    		'price' => $prd['price'],
    		'qty' => $prd['qty'],
    		'qty_total' => $prd['qty_original'],
    		'sku' => $prd['sku'],
    		'skulocal' => $skubling,
    		'skumkt' => $skumkt,     
    		'date_last_sent' => $this->dateLastInt,
    		'tipo_volume_codigo' => $prd['tipovolumecodigo'], 
    		'width' => $prd['largura'],
    		'height' => $prd['altura'],
    		'length' => $prd['profundidade'],
    		'gross_weight' => $prd['peso_bruto'],
    		'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'], 
    		'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']), 
    		'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
    		'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
    	);
		
		$savedUltEnvio =$this->model_car_ult_envio->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela car_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 
	}
}
?>
