<?php 
/* 
* recebe a reuisição e cadastra, alterara e inativa no Madeira Madeira
 */
require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

class ProductsZOOM extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal ;
	var $integration;
	var $reserve_to_b2W = 5;
	var $statusMAD = 1;
	
	var $minimal_price = 30;
	var $maximum_name_length = 100;
	var $tipo_entrega_correios = 'Conectala';
	var $tipo_entrega_transportadora = 'Transportadora';
	var $maximum_qty = 500;
	var $minimum_weight = 0.01;
	
	var $prd_mm;


    public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_blingultenvio');
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		$this->load->model('model_marketplace_prd_variants'); 			
		$this->load->model('model_settings'); 	
		$this->load->model('model_products_category_mkt'); 
		$this->load->model('model_zoom_last_post'); 
		$this->load->model('model_products_catalog');
		
		$this->minimal_price				= $this->getSetting('mm_minimal_price',$this->minimal_price);
		$this->maximum_name_length 			= $this->getSetting('mm_maximum_name_length', $this->maximum_name_length);
		$this->tipo_entrega_correios 		= $this->getSetting('mm_tipo_entrega_correios', $this->tipo_entrega_correios);
		$this->tipo_entrega_transportadora 	= $this->getSetting('mm_tipo_entrega_transportadora', $this->tipo_entrega_transportadora);
		$this->maximum_qty 					= $this->getSetting('mm_maximum_qty', $this->maximum_qty);
		$this->minimum_weight			 	= $this->getSetting('mm_minimum_weight', $this->minimum_weight);
    }

	public function index_post() 
    {
    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();
	
		// verifico se é cadastrar, inativar ou alterar o produto
	    $this->checkAndProcessProduct();
			
		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		$fim= microtime(true);
		echo "\nExecutou em: ". ($fim-$this->inicio)*1000 ." ms\n";
		return;
    } 
	
	public function checkAndProcessProduct()
	{
		
		$this->getkeys();
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
 	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
		
	    // pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku)==false) { return false;};
		
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}
			foreach($this->variants as $variant) {
				echo "inserindo a varação ".$variant['variant']."\n";
				$this->insertProductVariant($sku,$variant);
			}
		}
		else {
			$this->insertProductVariant($sku);
		}
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if ($this->prd_to_integration['status_int'] ==21) {// Ainda não enviou para o Carrefour. 
			return $this->insertProduct(); 
		}
		echo "Update"."\n";
		
		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($this->prd_to_integration['skumkt']) == false) {
			 $this->inactiveMKT();
			 return false;
		};
		
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}
			foreach($this->variants as $variant) {
				$this->updateProductVariant($variant);
			}
		}
		else {
			$this->updateProductVariant();
		}
		//$this->syncOffers();
	}
	
	function insertProductVariant($sku, $variant = null)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$skupai = $sku; 

		$variant_num = null;
		if (!is_null($variant)) {
			$variant_num = $variant['variant'];
			$sku = $sku.'_'.$variant_num; // Madeira madeira não permite - só underline  
		}
		echo "Insert ".$sku."\n";
		
		// Busco o produto já publicado 
		$this->prd_mm = $this->getProductPublished($sku); 
		if ($this->prd_mm ) { // produto já está cadastrado no madeira madeira, melhor fazer update 
			return $this->updateProductVariant($variant);
		}
		
		if (!is_null($variant)) {
			$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			if (!$prd_int) {// jeito velho ou recem criado pelo MarcaTodos, então acerto a prd_to_integration 
				$prd_upd = array (
					'int_id'		=> $this->prd_to_integration['int_id'],
					'prd_id'		=> $this->prd['id'],
					'company_id'	=> $this->prd['company_id'],
					'date_last_int' => $this->dateLastInt,
					'status'	 	=> $this->prd_to_integration['status'],
					'status_int' 	=> 22,
					'user_id' 		=> $this->prd_to_integration['user_id'],
					'int_type' 		=> $this->prd_to_integration['int_type'],
					'int_to' 		=> $this->int_to,
					'skumkt' 		=> $skupai,
					'skubling' 		=> $sku,
					'store_id'		=> $this->prd['store_id'],
					'quality' 		=> $this->prd_to_integration['quality'],
					'ad_link' 		=> $this->prd_to_integration['ad_link'],
					'approved' 		=> $this->prd_to_integration['approved'],
					'variant' 		=> $variant_num,
					'rule' 			=> $this->prd_to_integration['rule'],
				);
				$this->model_integrations->createPrdToIntegration($prd_upd);
				$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			}else {
				If ($prd_int['status_int'] != 22) {  // Pode já ter feito a integração deste item, então tem que fazer update 
					$this->prd_to_integration['status_int'] =22;
				}
			}
			// apaga o registro inicial criado na BlingMarcaTodosEnvio sem variação
			$todelete =  $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to,null);
			if ($todelete) {
				$this->model_integrations->removePrdToIntegration($todelete['id']); 
				if ($this->prd_to_integration['id'] == $todelete['id']) {
					$this->prd_to_integration = $prd_int;
				}
			}
		}
		else {
			$prd_int = $this->prd_to_integration; 
		}
		
		// limpa os erros de transformação existentes da fase de Preparação para o envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para o envio');
		
		// Monto o Array para cadastrar o produto  
		$produto = $this->montaArray($sku, $skupai, $variant);
		if ($produto==false) { return false; };

		echo "Incluindo o produto ".$this->prd['id']." ".$this->prd['name']."\n";
		$url = '/v1/produto';
		$retorno= $this->processURL($url, 'POST',  $produto, $this->prd['id'], $this->int_to, 'Novo produto');
		if ($this->responseCode == 422) { // Deu um erro 
			echo " RESPOSTA ".$this->int_to.": ".print_r($this->result,true)." \n"; 
			$resp = json_decode($this->result,true);
			// var_dump($resp);
			if ($resp['errors'][0]['detail'] !== 'Produto com este EAN ou SKU já cadastrado para ser processado.') {
				$msg= $resp['errors'][0]['detail'];
				echo 'ERRO: Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku,$msg, "Resposta Madeira Madeira");
				return false;
			}
			echo "Está em cadastramento, então vamos alterar o produto\n";
			$url = '/v1/produto/';
			$retorno= $this->processURL($url, 'PUT',  $produto, $this->prd['id'], $this->int_to, 'Novo produto');
		}
		
		if ($this->responseCode == 422) { // Deu um erro mas é tratável. 
			$resp = json_decode($this->result,true);
			if ($resp['errors'][0]['detail'] !== 'Produto não sofreu alteração.') {
				// deu algum erro 
				$msg= $resp['errors'][0]['detail'];
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku,$msg, "Resposta Madeira Madeira");
				return false;
			}
		} elseif (($this->responseCode != 201) && ($this->responseCode != 204)  && ($this->responseCode != 200)) { // Deu um erro 
			echo " Erro URL: ". $url. " httpcode=".$this->responseCode."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($this->result,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA ".$this->int_to.": ".print_r($this->result,true).' DADOS ENVIADOS:'.print_r($produto,true),"E");
			die;
		}

		if ($this->responseCode !== 422) {
			// limpa os erros de transformação existentes da fase de Preparação para o envio
			$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
		}
		$prd_upd = array (
			'skubling' 		=> $sku,
			'skumkt' 		=> $skupai,
			'status_int' 	=> 22,
			'date_last_int' => $this->dateLastInt,
			'variant' 		=> $variant_num,
		);
		$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_int['id']);
	
		echo "Produto ".$sku." enviado\n";
		return true;	
	}
	
	function updateProductVariant($variant = null,$status_int =2)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Update'."\n";
		
		$price = $this->prd['price'];
		$promotional_price = $this->prd['promotional_price'];
		$qty = $this->prd['qty'];
		$sku_prd = $this->prd['sku'];
		$ean = $this->prd['EAN'];
		
		$variant_num = null;
		if (is_null($variant)) {  // não tem variação. 
			$sku = $this->prd_to_integration['skubling']; 
			$skupai = $sku;
		}
		else { // sou uma variação. 
			$variant_num = $variant['variant'];
			// verifico se já tenho prd_to_integration na minha variação. 
			$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			if (!$prd_int) { // Esta variação não está nda prd_to_integration, então cadastra 
				$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
				return $this->insertProductVariant($sku,$variant);
			} else { 
				if (($prd_int['status_int'] == 22) && (!$this->prd_mm)) {
					echo " Variação ainda em cadastramento\n";
					return $this->insertProductVariant($prd_int['skumkt'],$variant);
				}
			}
			$sku = $prd_int['skubling'];
			$skupai = $prd_int['skumkt']; 
			$price = $variant['price'];
			$promotional_price = $variant['promotional_price'];
			$qty = $variant['qty'];
		} 
		
		echo " sku = ".	$sku."\n";

		// Busco o produto já publicado 
		if (!$this->prd_mm) { // pode já ter vindo do insert e já leu o prd_mm
			$this->prd_mm  = $this->getProductPublished($sku); 
			if (!$this->prd_mm ) { // Apesar de tudo, ainda não está aprovado no Madeira madeira, então pode fazer Insert
				return $this->insertProductVariant($skupai,$variant);
			}
		}

		// limpa os erros de transformação existentes da fase de Preparação para o envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para o envio');
			
		$produto = array(
			array (
				'sku' 			=> $sku,
		        'preco_de' 		=> $price,
		        'preco_por' 	=> $promotional_price,
		        'estoque' 		=> ($qty > 500) ? 500 : ($qty < 0) ? 0 : (int)$qty,
		        'status'		=> $this->statusMAD,
		        'tipo_entrega' 	=> $this->tipo_entrega_correios,
		        'altura'		=> ($this->prd['altura'] < 2) ? 2 : (float)$this->prd['altura'],
		        'largura' 		=> ($this->prd['largura'] < 11) ? 11 : (float)$this->prd['largura'],
		        'profundidade' 	=> ($this->prd['profundidade'] < 16) ? 16 : (float)$this->prd['profundidade'],
		        'peso'			=> (float)$this->prd['peso_bruto'],
			)
		);

		$prd_json = json_encode($produto, JSON_UNESCAPED_UNICODE);
		if ($prd_json === false) {
			$msg = 'Erro ao fazer o json do update do produto '.$this->prd['id'].' '.print_r($prd_json,true).' json error = '.json_last_error_msg();
			var_dump($prd_json);
			echo $msg."\n";
			$this->log_data('batch',$log_name, $msg,'E');
			return false;;
		}
		$prd_json = stripcslashes($prd_json);
		echo print_r($prd_json,true)."\n";
		
		echo "Alterando o produto ".$this->prd['id']." ".$this->prd['name']."\n";
		$url = '/v1/produto/bulk';
		$retorno= $this->processURL($url, 'PUT', $prd_json, $this->prd['id'], $this->int_to, 'Alterando produto');
		if ($this->responseCode == 400 ) { // Deu um erro 
			$resp_mm = json_decode($this->result,true);
			var_dump($resp_mm);
			if (array_key_exists('errors', $resp_mm)) {
				$msg = '';
				foreach($resp_mm['errors']['detail'][0] as $error_detail) {
					$msg .= $error_detail['data_path'].' '.$error_detail['message']." ";
				}
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku,$msg, "Resposta Madeira Madeira");
				return false;
			}
			
		}
		if ($this->responseCode == 422) { // Deu um erro. 
			$resp = json_decode($this->result,true);
			$msg = '';
			foreach($resp['errors'] as $error) {
				$msg.= $error['type'].": ".$error['detail'];
			}
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg, "Resposta Madeira Madeira");
			
			$this->inactiveMKT();
			return false;
		}
		if (($this->responseCode != 200) && ($this->responseCode != 201) && ($this->responseCode != 204)) { // Deu um erro 
			echo " ---------------------------------------------------- \n"; 
			echo " Erro URL: ". $url. " httpcode=".$this->responseCode."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($this->result,true)." \n"; 
			echo " Dados enviados: ".print_r($prd_json,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA ".$this->int_to.": ".print_r($this->result,true).' DADOS ENVIADOS:'.print_r($prd_json,true),"E");
			
			// $this->inactiveMKT();
			die;
		}

		if ($status_int==2) {
			$status_int = ($this->prd['qty'] == 0) ? 10 : 2;
		}
		 
		$prd_upd = array (
			'skubling' 		=> $sku,
			'skumkt' 		=> $skupai,
			'status_int' 	=> $status_int,
			'date_last_int' => $this->dateLastInt,
			'variant' 		=> is_null($variant) ? null : $variant['variant'],
			'ad_link'		=> 'https://www.madeiramadeira.com.br'.$this->prd_mm['data']['url'],
			'mkt_sku_id'    => $this->prd_mm['data']['id_produto'],   // não serve bem para nada mas estou guardando
		);
		
		if (!is_null($variant)) {
			$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			if ($prd_int) {
				$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_int['id']);
			}
			else {  // jeito velho, então acerto a prd_to_integration -- Não deveria acontecer pois estou mandando inserir novamente. 
				$prd_upd = array (
					'int_id'		=> $this->prd_to_integration['int_id'],
					'prd_id'		=> $this->prd['id'],
					'company_id'	=> $this->prd['company_id'],
					'date_last_int' => $this->dateLastInt,
					'status'	 	=> $this->prd_to_integration['status'],
					'status_int' 	=> $status_int,
					'user_id' 		=> $this->prd_to_integration['user_id'],
					'int_type' 		=> $this->prd_to_integration['int_type'],
					'int_to' 		=> $this->int_to,
					'skumkt' 		=> $skupai,
					'skubling' 		=> $sku,
					'store_id'		=> $this->prd['store_id'],
					'quality' 		=> $this->prd_to_integration['quality'],
					'ad_link' 		=> $this->prd_to_integration['ad_link'],
					'approved' 		=> $this->prd_to_integration['approved'],
					'variant' 		=> $variant_num,
					'rule' 			=> $this->prd_to_integration['rule'],
				); 
				$this->model_integrations->createPrdToIntegration($prd_upd);
				$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			}
		}
		else {
			$this->model_integrations->updatePrdToIntegration($prd_upd, $this->prd_to_integration['id']);
			$prd_int = $this->prd_to_integration; 
		}

		$this->prd_to_integration['skubling'] = $sku;
    	$this->prd_to_integration['skumkt'] = $skupai;
		
		if (is_null($variant)) {
			$this->updateZOOMLastPost($this->prd, null);
		}
		else {
			$prd = $this->prd;
			$prd['sku'] = $variant['sku'];
			$prd['qty'] = $variant['qty'];
			$prd['price'] = $variant['price'];
			$prd['promotional_price'] = $variant['promotional_price'];
			$prd['qty_original'] = $variant['qty_original'];
			$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;		
			$this->updateZOOMLastPost($prd, $variant);
		}
		
		// limpa os erros de transformação deste produto....
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
		
		return true;
	}
	
	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		$this->prd['qty'] = 0; // zero a quantidade do produto

		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($this->prd_to_integration['skumkt'])  == false) {
			$this->inactiveMKT();
			return false;
		};
		
		if ($disable) {$this->statusMAD = 0;}
		
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = 'As variações deste produto '.$this->prd['id'].' sumiram.';
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,'E');
				die;
			}
			foreach($this->variants as $key => $variant) {
				$this->variants[$key]['qty'] = 0;  // zero a quantidade da variant tb
				$variant['qty'] = 0 ; 
				$this->updateProductVariant($variant, $status_int);
			}
		}
		else {
			$this->updateProductVariant(null, $status_int);
		}
	}
	
	function inactiveMKT() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Zerando o estoque de ".$this->prd['id']." pois deu algum problema para pegar seus dados\n";
		if ($this->prd['has_variants'] !== '') {
			foreach($this->variants as $key => $variant) {
				$int=$this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
				$this->zeroStock($int['skubling'], $int);
			}
		}
		else {
			$int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to);
			$this->zeroStock($int['skubling'], $int);
		}		
	}
	
	function zeroStock($sku, $int) {
		// Busco o produto já publicado 
		
		$url = '/v1/produto/bulk';
		$this->prd_mm  = $this->getProductPublished($sku); 
		if (!$this->prd_mm ) { // Apesar de tudo, ainda não está aprovado no Madeira madeira, então pode fazer Insert
			if ($int['status_int']!=22) {
				return ; // ainda nem tentou cadastrar....
			}
			$url = '/v1/produto/pendente/bulk';
		} 

		$produto = array(
			array (
				'sku' 			=> $sku,
		        'estoque' 		=> 0,
			)
		);

		$prd_json = json_encode($produto, JSON_UNESCAPED_UNICODE);
		$prd_json = stripcslashes($prd_json);
		
		$retorno= $this->processURL($url, 'PUT', $prd_json);
		if ($this->responseCode == 400 ) { // Deu um erro 
			$resp_mm = json_decode($this->result,true);
			if (array_key_exists('errors', $resp_mm)) {
				$msg = '';
				foreach($resp_mm['errors']['detail'][0] as $error_detail) {
					$msg .= $error_detail['data_path'].' '.$error_detail['message']." ";
				}
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku,$msg, "Resposta Madeira Madeira", $variant);
				return false;
			}
			
		}
		if (($this->responseCode != 200) && ($this->responseCode != 201) && ($this->responseCode != 204)) { // Deu um erro 
			echo " Erro URL: ". $url. " httpcode=".$this->responseCode."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($this->result,true)." \n"; 
			echo " Dados enviados: ".print_r($produto,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no post produto site:'.$url.' - httpcode: '.$this->responseCode." RESPOSTA ".$this->int_to.": ".print_r($this->result,true).' DADOS ENVIADOS:'.print_r($prd_json,true),"E");
			die;
		}
	}

	function getkeys() {
		//pega os dados da integração. 
		$this->getIntegration(); 
		$this->auth_data = json_decode($this->integration_main['auth_data']);
		//$this->setApikey($api_keys['apikey']);
	//	$this->setEmail($api_keys['email']);
	}

	function getIntegration() 
	{
		
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				if ($this->integration_store['int_from'] == 'CONECTALA') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto('0',$this->int_to);
				}elseif ($this->integration_store['int_from'] == 'HUB') {
					$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
				} 
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
	}
	
	//public function getCategoryMarketplace($skumkt, $int_to = '') {
	public function getCategoryMarketplace($skumkt, $int_to = '', $mandatory_category = true) {
		$return = parent::getCategoryMarketplace($skumkt, $int_to, $mandatory_category);
		
		$enrichment = $this->model_products_category_mkt->getCategoryEnriched($int_to, $this->prd['id']);
		if (!is_null($enrichment)) {
			echo 'categoria do '.$int_to.' Enriched '.$enrichment['category_mkt_id']."\n";
			$return = $enrichment['category_mkt_id'];
		}
		
		$catmkt = $this->model_categorias_marketplaces->getCategoryByMarketplace($this->int_to, $return);
		$this->prd['category_mkt_name'] = mb_strtoupper($catmkt['nome'],'UTF-8'); // coloca tudo para maiúsculo.
		return $return;
	}
	
	function prepareProduct($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Preparando produto'."\n";
		
		// busco a categoria 
		$this->prd['categoria_MAD'] = $this->getCategoryMarketplace($sku, $this->int_to);		
		if ($this->prd['categoria_MAD']==false) {
			return false;
		}
		
		$this->prd['name'] = substr(strip_tags(htmlspecialchars($this->prd['name'], ENT_QUOTES, 'utf-8')," \t\n\r\0\x0B\xC2\xA0"),0,$this->maximum_name_length);
		if ($this->prd['name'] == '') {
			$msg= 'Sem o nome do produto depois que tirou os tags html';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg, "Preparação para o envio");
			return false;
		}
		//$this->prd['description']= substr(htmlspecialchars(strip_tags($this->prd['description'], '<p><br>'), ENT_QUOTES, "utf-8"),0,10000);
		
		$this->prd['description']= substr(strip_tags($this->prd['description'], '<p><br>'),0,10000);
		if ($this->prd['description'] == '') {
			$msg= 'Sem a descrição do produto depois que tirou os tags html';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg, "Preparação para o envio");
			return false;
		}
	
		if ((float)$this->prd['peso_bruto'] < (float)$this->minimum_weight) {
			$msg= 'Produto com peso bruto menor que o mínimo ('. $this->minimum_weight.')';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg, "Preparação para o envio");
			return false;
		}

		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];
		if ((int)$this->prd['qty'] < $this->reserve_to_b2W) { // Mando só para a B2W se a quantidade for menor que 5. 
			$this->prd['qty']  = 0;
		}
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arredondo para cima 
		
		// Madeira madeira tem um valor máximo para estoque
		if ($this->prd['qty'] > $this->maximum_qty) {
			$this->prd['qty']= $this->maximum_qty;
		}
		
		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);
		if ($this->prd['promotional_price'] > $this->prd['price'] ) {
			$this->prd['price'] = $this->prd['promotional_price']; 
		}
		if ($this->prd['price'] < $this->minimal_price) {
			$msg= 'Produto com preço menor que o mínimo ('. $this->minimal_price.')';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
			return false;
		}
		if ($this->prd['promotional_price'] < $this->minimal_price) {
			$msg= 'Produto com preço promocional menor que o mínimo ('. $this->minimal_price.')';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
			return false;
		}
		 
		// se é a conectaLá não usa EAN para o produto
		if ($this->int_to=='MAD') {
			$this->prd['EAN'] = '';
		}
		// se tiver Variação,  acerto o estoque de cada variação
    	if ($this->prd['has_variants']!='') {
    		$variações = explode(";",$this->prd['has_variants']);
			
			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
				$this->variants[$key]['qty_original'] =$variant['qty'];
				if  ((int)$this->variants[$key]['qty'] < 0) { 
					$this->variants[$key]['qty'] = 0;
				}
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
				// Madeira madeira tem um valor máximo de estoque
				if ($this->variants[$key]['qty'] > $this->maximum_qty) {
					$this->variants[$key]['qty'] = $this->maximum_qty;
				}
				
				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
					$this->variants[$key]['price'] = $this->prd['price'];
				}
				
				$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
				if ($this->variants[$key]['promotional_price'] > $this->variants[$key]['price'] ) {
					$this->variants[$key]['price'] = $this->variants[$key]['promotional_price']; 
				}
				
				//ricardo, por enquanto, o preço da variação é igual ao do produto. REMOVER DEPOIS QUE AS INTEGRAÇÔES ESTIVEREM CONCLUIDAS
				$this->variants[$key]['price'] = $this->prd['price'];
				$this->variants[$key]['promotional_price'] = $this->prd['promotional_price']; 
				
				if ($this->variants[$key]['price'] < $this->minimal_price) {
					$msg= 'Variação '.$key.' com preço menor que o mínimo ('. $this->minimal_price.')';
					echo 'Produto '.$this->prd['id']." ".$msg."\n";
					$this->errorTransformation($this->prd['id'],$sku,$msg, "Preparação para o envio");
					return false;
				}
				if ($this->variants[$key]['promotional_price'] < $this->minimal_price) {
					$msg= 'Variação '.$key.' com preço promocional menor que o mínimo ('. $this->minimal_price.')';
					echo 'Produto '.$this->prd['id']." ".$msg."\n";
					$this->errorTransformation($this->prd['id'],$sku,$msg, "Preparação para o envio");
					return false;
				}
				
				// se é a conectaLá não usa EAN para o produto
				if ($this->int_to=='MAD') { 
					$this->variants[$key]['EAN'] = '';
				}
			}
		}
		
		if ($this->prd['is_kit']) {  // B2W consegue mostrar o preço original dos produtos que o componhe 
			$productsKit = $this->model_products->getProductsKit($this->prd['id']);
			$original_price = 0; 
			foreach($productsKit as $productkit) {
				$original_price += $productkit['qty'] * $productkit['original_price'];
			}
			$this->prd['price'] = $original_price;
			echo ' KIT '.$this->prd['id'].' preço de '.$this->prd['price'].' por '.$this->prd['promotional_price']."\n";  
		}
		
		//leio a brand
		if ($this->getBrandMarketplace($sku,false) == false) return false;
		if ($this->prd['brandname'] == "") {
			$msg= 'Produto sem marca ';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
			return false;		
		}
		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1 ) { $this->prd['prazo_operacional_extra'] = 1; }
		
		if ($this->prd['prazo_operacional_extra'] > 4) {
			$moveis = '1'.mb_strtoupper("móveis ",'UTF-8'); 
			if ((strpos('1'.$this->prd['category_mkt_name'],$moveis) === false) || ($this->prd['prazo_operacional_extra'] > 7)) {
				$msg = 'Prazo operacional acima do máximo de 7 dias para móveis e acima de 4 dias para outros produtos';
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
				return false;	
			}
			
		} 
		
		return true;
	}	
	
	private function getProductImages($folder_ori, $path, $vardir = '', $variacao = false )
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$folder = $folder_ori;
		if ($vardir !== '') {
			$folder .= $vardir;
		}
		elseif ($variacao) {
			return array(); // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}
		echo 'Lendo imagens em assets/images/'.$path.'/'.$folder."\n";
		if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder)) {
			return array();
		}
		if ($folder == '') {
			return array();
		}
        $images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
        
		 echo "Procurando imagens em ".FCPATH.'assets/images/'.$path.'/'.$folder."\n";
        if (!$images) {
            return array();
        }
        if (count($images) <= 2) { // não achei nenhuma imagem
			if ($variacao) { // Mas é uma variação, retorna o array vazio
				return  array();
			}
            return array();
        }
		$numft= 0;
		$imagesData = array();
		foreach($images as $foto) {
			if (($foto!='.') && ($foto!='..') && ($foto!='')) {
				if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder.'/'.$foto)) {
					$imagesData[] = base_url('assets/images/'.$path.'/' . $folder.'/'. $foto);
					$numft++;
				}
			}
		}
        return $imagesData;
    }

	function montaArray($sku, $skupai, $variant = null) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		// sempro pego as imagens do Pai
		$images 			= $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		$attributes = array();
		$nome_ext ='';
		if (is_null($variant)) {  // sem variação
			$price 				= (float)$this->prd['price'];
			$promotional_price 	= (float)$this->prd['promotional_price'];
			$qty 				= (int)$this->prd['qty'];
		}
		else { // com variação 
			$vardir = '';
			if (($this->pathImage == 'product_image')){
				if (!is_null($variant['image']) && trim($variant['image'])!='')	{
					$vardir = '/'.$variant['image'];
				}
				$images_var			= $this->getProductImages($this->prd['image'], $this->pathImage, $vardir, true); 
			}else {
				$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$variant['variant'] ); 
				if ($var_cat) {
					$images_var			= $this->getProductImages($var_cat['image'], $this->pathImage, '', false); 
				}
			}
			$images 			= array_merge($images_var, $images);  // junto as imagens da variação premeiro e depois a do pai 
			$price 				= (float)$variant['price'];
			$promotional_price 	= (float)$variant['promotional_price'];
			$qty 				= (int)$variant['qty'];
			
			// as variações viram atributos. como são os mais importantes, tem que ficar na frente do array. 
			$tipos = explode(';',$this->prd['has_variants']);
			$values = explode(';',$variant['name']);
			foreach ($tipos as $z => $campo) {
				$attributes[] = array('nome' => mb_convert_case($campo, MB_CASE_TITLE, "UTF-8"), 'valor' => mb_convert_case($values[$z], MB_CASE_TITLE, "UTF-8"));
				$nome_ext .= mb_convert_case($values[$z], MB_CASE_TITLE, "UTF-8")." ";
			}
		}
		
		//$images = array('https://i.ibb.co/gJK26hg/16255815460323.jpg','https://i.ibb.co/GsM1hHn/16255814594306.jpg');
		if (empty($images)) {
			$msg= 'Pelo menos uma imagem deve ser enviada.';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$sku,$msg, "Preparação para o envio");
			return false;
		}
		
		if ( $this->prd['garantia'] > 0) {
			$meses = ' meses';
			if ($this->prd['garantia'] == 1) {
				$meses = ' mês';
			}
			$attributes[] = array('nome' => 'Garantia', 'valor' => $this->prd['garantia'].$meses); 
		}
		
		$attributes[] = array('nome' => 'Marca','valor' =>  $this->prd['brandname']); 
		
		if (!is_null($this->prd['actual_width']) && trim($this->prd['actual_width']!=='')) {
			$attributes[] = array('nome' => 'Largura desembalado', 'valor' => $this->prd['actual_width'].' cm'); 
		}
		if (!is_null($this->prd['actual_height']) && trim($this->prd['actual_height']!=='')) {
			$attributes[] = array('nome' => 'Altura desembalado', 'valor' => $this->prd['actual_height'].' cm');
		}
		if (!is_null($this->prd['actual_depth']) && trim($this->prd['actual_depth']!=='')) {
			$attributes[] = array('nome' => 'Profundidade desembalado', 'valor' => $this->prd['actual_depth'].' cm');
		}
		if (!is_null($this->prd['peso_liquido']) && trim($this->prd['peso_liquido']!=='')) {
			$attributes[] = array('nome' => 'Peso líquido', 'valor' => $this->prd['peso_liquido'].' kg');
		}
		$attibutes_custom = $this->model_products->getAttributesCustomProduct($this->prd['id']);
		foreach ($attibutes_custom as $attibute_custom) {
			$attributes[] = array ('nome' => $attibute_custom['name_attr'], 'valor' => $attibute_custom['value_attr']);
		}
		// $this->prd['EAN']= '1234567890128'; 
		
		$title = $this->prd['name'];
		if (strlen(trim($this->prd['name'].' '.$nome_ext)) <= 100) {
			$title = trim($this->prd['name'].' '.$nome_ext);
		}
		$produto = array(
			array(
				'id_categoria'	=> (int)$this->prd['categoria_MAD'],
				'nome'			=> $title,
			    'descricao'     => $this->prd['description'],
				'sku' 			=> $sku,
				'ean'			=> $this->prd['EAN'], 
				'marca'			=> $this->prd['brandname'],
				'preco_de' 		=> $price,
				'preco_por' 	=> $promotional_price,
				'estoque'	    => (int)$qty,
				'altura'		=> ($this->prd['altura'] < 2) ? (float)2 : (float)$this->prd['altura'],
				'largura'		=> ($this->prd['largura'] < 11) ? (float)11 : (float)$this->prd['largura'],
				'profundidade'	=> ($this->prd['profundidade'] < 16) ? (float)16 : (float)$this->prd['profundidade'],
				'peso'  		=> (float)$this->prd['peso_bruto'],
				'imagens'		=> $images,
				// 'video'			=> '',
				'atributos'     => $attributes, 
				'tipo_entrega'	=> $this->tipo_entrega_correios,
			)
		); 

		$resp_json = json_encode($produto, JSON_UNESCAPED_UNICODE);
		if ($resp_json === false) {
			$msg = 'Erro ao fazer o json do produto '.$this->prd['id'].' '.print_r($produto,true).' json error = '.json_last_error_msg();
			var_dump($resp_json);
			echo $msg."\n";
			$this->log_data('batch',$log_name, $msg,'E');
			return false;;
		}
		$resp_json = stripcslashes($resp_json);
		echo print_r($resp_json,true)."\n";

		return $resp_json;	
	}

	protected function processURL($url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null )
    {

		$url = $this->auth_data->site.$url;
		echo " URL : ".$url. "\n"; 
        $this->header = [
            'Content-Type: application/json',
            'TOKENMM: '.$this->auth_data->token
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
		if ($this->responseCode == 429) {
		    $this->log('Muitas requisições já enviadas httpcode=429. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->processURL($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
		if ($this->responseCode == 504) {
		    $this->log('Deu Timeout httpcode=504. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->processURL($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
        if ($this->responseCode == 503) {
		    $this->log('Site com problemas httpcode=503. Nova tentativa em 60 segundos.');
            sleep(60);
			$this->processURL($url, $method, $data, $prd_id, $int_to, $function);
			return;
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'function' => $function,
				'url' => $url,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
		
        return;
    }
    
    function updateZOOMLastPost($prd, $variant = null) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
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
		$skulocal = $this->prd_to_integration['skubling']; 
		//if (!is_null($variant_num)) {
		//	$skulocal = $skulocal.'_'.$variant_num; 
		//}
		
    	$data = array(
    		'int_to' => $this->int_to,
    		'prd_id' => $prd['id'],
    		'variant' => $variant_num,
    		'company_id' => $prd['company_id'],
    		'store_id' => $prd['store_id'], 
    		'EAN' => $ean,
    		'price' => $prd['promotional_price'],
    		'list_price' => $prd['price'],
    		'qty' => $prd['qty'],
    		'qty_total' => $prd['qty_original'],
    		'sku' => $prd['sku'],
    		'skulocal' => $skulocal,
    		'skumkt' => $this->prd_to_integration['skumkt'],     
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

        $data = $this->formatFieldsUltEnvio($data);
		
		$savedUltEnvio =$this->model_zoom_last_post->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela mm_last_post.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 
	}
	
	function hasShipCompany() {
		$this->load->library('calculoFrete'); 
		
		$cat_id = json_decode ( $this->prd['category_id']);
		$sql = 'SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id ='.intval($cat_id[0]).')';
		$cmd = $this->db->query($sql);
		$lido = $cmd->row_array();
		$tipo_volume_codigo= $lido['codigo'];		
					
		$prd_info = array (
			'peso_bruto' =>(float)$this->prd['peso_bruto'],
			'largura' =>(float)$this->prd['largura'],
			'altura' =>(float)$this->prd['altura'],
			'profundidade' =>(float)$this->prd['profundidade'],
			'tipo_volume_codigo' => $tipo_volume_codigo,
		);
		return ($this->calculofrete->verificaCorreios($prd_info) ||
				$this->calculofrete->verificaTipoVolume($prd_info,$this->store['addr_uf'],$this->store['addr_uf']) ||
				$this->calculofrete->verificaPorPeso($prd_info,$this->store['addr_uf'])) ; 
	}
	
	
	public function getSetting($setting, $default = '') {
		$set = $this->model_settings->getValueIfAtiveByName($setting);
		if ($set)
			return $set;
		else
			return $default;
	}
	
	public function getProductPublished($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$url = "/v1/produto/".$sku;
		$this->processURL($url,'GET', null); 
		if ($this->responseCode == 404) {
			echo "Não achei ele publicado\n";
			return false;
		}
		if ($this->responseCode != 200) {
			$error = "Erro {$this->responseCode} ao acessar {$this->site}{$url} na função GET";
			echo $error."\n";
			$this->log_data('batch',$log_name,$error,"E");
			die;
		}
		return json_decode($this->result,true);
	}

	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->model_zoom_last_post->getData(null, $procura);
	}   

}