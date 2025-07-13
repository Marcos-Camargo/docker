<?php 
/* 
Envia produtos carrefour  
 */
require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

class ProductsCAR extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal ;
	var $integration;
    var $reserve_to_b2W = 0;  // removido em 13/09/2022
	var $ofertas = array();
	var $skumkt;
	var $bling_ult_envio;
  	var $days_to_send_again = 14;
  	public function __construct() {
        parent::__construct();
	   
	    $this->load->model('model_blingultenvio');
	    $this->load->model('model_brands');
	    $this->load->model('model_category');
	    $this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_brands_marketplaces');
	  	$this->load->model('model_atributos_categorias_marketplaces'); 	   
		$this->load->model('model_marketplace_prd_variants'); 
		$this->load->model('model_ml_ult_envio'); 	
		$this->load->model('model_settings'); 	
		$this->load->model('model_carrefour_new_products'); 
		$this->load->model('model_log_integration_product_marketplace'); 
		$this->load->model('model_car_ult_envio'); 
		$this->load->model('model_carrefour_new_offers');
		
		 
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
		
		$this->getKeys();
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}
	
	function insertProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Insert"."\n";
		
		if ($this->prd_to_integration['status_int'] == 22)  {
			if ($this->prd_to_integration['date_last_int'] != '') {
				if(strtotime($this->prd_to_integration['date_last_int']) > strtotime('-'.$this->days_to_send_again.' days')) {
					echo "Ainda não se passaram ".$this->days_to_send_again." dias para recadastrar\n";					
					return true;
				}
			}
			
		}
		
		// verifico se existia como ganhador do leilão 
		$this->bling_ult_envio = $this->model_blingultenvio->getDataByIntToPrdIdVariant($this->int_to,$this->prd['id']);
		if (!$this->bling_ult_envio) { // se não existia ou nunca foi ganhador do leilão
			$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
 		} else {
			$sku = $this->bling_ult_envio['skubling'];
 		}
		
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
                if ($variant['status'] != 1) {
                    $this->disableProductVariant(null, $variant);
                } else {
                    echo "inserindo a variação ".$variant['variant']."\n";
                    $this->insertProductVariant($sku,$variant);
                }
			}
		}
		else {
			$this->insertProductVariant($sku);
		}
		//$this->syncOffers();
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if ($this->prd_to_integration['status_int'] ==21) {// Ainda não enviou para o Carrefour. 
			return $this->insertProduct(); 
		}
		echo "Update"."\n";
		
		// verifico se existia como ganhador do leilão 
		$this->bling_ult_envio = $this->model_blingultenvio->getDataByIntToPrdIdVariant($this->int_to,$this->prd['id']);
		
		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($this->prd_to_integration['skumkt']) == false) { return false;};
		
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) ==0) {
				$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
	            echo $erro."\n";
	            $this->log_data('batch', $log_name, $erro,"E");
				die;
			}
			foreach($this->variants as $variant) {
                if ($variant['status'] != 1) {
                    $prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
                    $this->disableProductVariant($prd_to_integration, $variant);
                } else {
                    $this->updateProductVariant($variant);
                }
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
			$sku = $sku.'-'.$variant_num; 
			
		}
		echo "Insert ".$sku."\n";
		
		if (!is_null($variant)) {
			$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			if (!$prd_int) {// jeito velho, então acerto a prd_to_integration 
				$prd_upd = array (
					'int_id'		=> $this->prd_to_integration['int_id'],
					'prd_id'		=> $this->prd['id'],
					'company_id'	=> $this->prd['company_id'],
					'date_last_int' => $this->dateLastInt,
					'status'	 	=> $this->prd_to_integration['status'],
					'status_int' 	=> 21,
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
				echo " Criando no insert \n";
				$this->model_integrations->createPrdToIntegration($prd_upd);
				$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			}else {
				// var_dump($prd_int['status_int']);
				If (($prd_int['status_int'] != 21) && ($prd_int['status_int'] != 22)) {  // Pode já ter feito a integração deste item, então tem que fazer update 
					echo "Essa variação já foi cadastrada, update então.\n";
					return $this->updateProductVariant($variant);
				}
			}
			// apaga o registro inicial criado na BlingMarcaTodosEnvio sem variação
			$todelete =  $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to,null);
			if ($todelete) {
				echo "Removendo registro inicial\n";
				$this->model_integrations->removePrdToIntegration($todelete['id']); 
				if ($this->prd_to_integration['id'] == $todelete['id']) {
					$this->prd_to_integration = $prd_int;
				}
			}
		}
		else {
			$prd_int = $this->prd_to_integration; 
		}
		
		// Monto o Array para enviar para gravar a tabela de carga 
		$produto = $this->montaArray($sku, $skupai, $variant);
		if ($produto==false) { return false;};
		$produto['prd_to_integration_id'] = $prd_int['id'];
		$produto['prd_id'] = $this->prd['id'];

		echo 'Incluindo o produto '.$this->prd['id'].' '.$this->prd['name'].' SKU:'.$sku,"\n";
		
		$this->model_carrefour_new_products->createIfNotExist($sku,$produto); // outro programa processa e envia para o Carrefour o CSV 
		
		$prd_upd = array (
			'skubling' 		=> $sku,
			'skumkt' 		=> $skupai,
			'status_int' 	=> 21,
			'date_last_int' => $this->dateLastInt,
			'variant' 		=> $variant_num,
		);
		$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_int['id']);
		
		$data_log = array( 
				'int_to' => $this->int_to,
				'prd_id' => $this->prd['id'],
				'function' => 'Registrado para envio',
				'url' => 'Tabela carrefour_new_products',
				'method' => 'Create/Update',
				'sent' => json_encode($produto),
				'response' => 'Gravado com sucesso',
				'httpcode' => true,
			);
		$this->model_log_integration_product_marketplace->create($data_log);
		return true;	
		
	}
	
	function updateProductVariant($variant = null,$status_int =2)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Update '.$variant['variant']."\n";
		
		$price = $this->prd['promotional_price'];
		$qty = $this->prd['qty'];
		$sku_prd = $this->prd['sku'];
		$ean = $this->prd['EAN'];
		if (is_null($variant)) {  // não tem variação. 
			if ((!$this->bling_ult_envio)  && (!$this->bling_ult_envio)) { // se nunca foi ganhador do leilão  - insiro um novo produto 
			 	$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
				return $this->insertProductVariant($sku);
			}
			// era ganhador do leilão, então altero 
			$sku = $this->prd_to_integration['skubling']; 
			$skupai = $sku;
		}
		else { // sou uma variação. 
			// verifico se já tenho prd_to_integration na minha variação. 
			$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			if ((!$prd_int) && ($status_int != self::INATIVO)) { // Jeito velho sem prd_to_integration com variação
				if (!$this->bling_ult_envio) { // se nunca foi ganhador do leilão  - insiro um novo produto 
				 	$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
					return $this->insertProductVariant($sku,$variant);
				}
				else { // já teve um ganhador de leilão não tenho certeza se esta variação já foi cadastrada, mando inserir tb por enquanto.  
					$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
					return $this->insertProductVariant($sku,$variant);
				}
			} else { 
				if (($prd_int['status_int'] == 21) || ($prd_int['status_int'] == 22)){
					echo " Variação ainda em cadastramento\n";
					if ($status_int != self::INATIVO) {
						return $this->insertProductVariant($prd_int['skumkt'],$variant);
					}
					echo " Produto está inativo\n";				
				}
			}
			$sku = $prd_int['skubling'];
			$skupai = $prd_int['skumkt']; 
			$price = $variant['promotional_price'];
			$qty = $variant['qty'];
			if ($sku == '') {
				$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
			}
		} 
		echo " sku = ".	$sku."\n";
		
		// Monto o Array para enviar para o Carrefour
		echo " OFERTA ".$this->prd['id']." sku ".$sku." price ".$price." qty ".$qty."\n"; 
		
		if ($status_int==2) {
			$status_int = ($this->prd['qty'] == 0) ? 10 : 2;
		}
		
		$prd_upd = array (
			'skubling' 		=> $sku,
			'skumkt' 		=> $skupai,
			'status_int' 	=> $status_int,
			'date_last_int' => $this->dateLastInt,
			'variant' 		=> is_null($variant) ? null : $variant['variant'],
		);
		if ($status_int == self::INATIVO) {
			// $prd_upd['status'] = '0';
		}
		
		if (!is_null($variant)) {
			$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			if ($prd_int) {
				$this->model_integrations->updatePrdToIntegration($prd_upd, $prd_int['id']);
			}
			else {  // jeito velho, então acerto a prd_to_integration -- Não deveria acontecer pois estou mandando inserir novamente. 
				echo "Entrei no lugar errado pois inativou antes de cadastrar completamente\n";
				$variant_num = $variant['variant'];
				$skupai = $sku;
				$sku = $sku.'-'.$variant_num;  
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
			echo "Sem variação\n";
			$this->model_integrations->updatePrdToIntegration($prd_upd, $this->prd_to_integration['id']);
			$prd_int = $this->prd_to_integration; 
		}
	
		$offer_data = array(
			'skulocal' => $sku,
			'skumkt' => $skupai, 
			'variant' => is_null($variant) ? null : $variant['variant'], 
			'price'=>$price, 
			'qty' => $qty,
			'status' => 0,
			'store_id' => 0, 
			'prd_to_integration_id' => $prd_int['id'],
			'prd_id' =>$this->prd['id'], 
		);
		$this->model_carrefour_new_offers->createIfNotExist($sku, $offer_data);
			
		$data_log = array( 
			'int_to' => $this->int_to,
			'prd_id' => $this->prd['id'],
			'function' => 'Atualizando sku '.$sku,
			'url' => 'Tabela carrefour_new_offers',
			'method' => 'Create/Update',
			'sent' => json_encode($offer_data),
			'response' => 'Gravado com sucesso',
			'httpcode' => true,
		);
		$this->model_log_integration_product_marketplace->create($data_log);
		
		$this->prd_to_integration['skubling'] = $sku;
    	$this->prd_to_integration['skumkt'] = $skupai;
		
		if (is_null($variant)) {
			$this->updateBlingUltEnvio($this->prd, null);
			$this->updateCARUltEnvio($this->prd, null);
		}
		else {
			$prd = $this->prd;
			$prd['sku'] = $variant['sku'];
			$prd['qty'] = $variant['qty'];
			$prd['price'] = $variant['price'];
			$prd['qty_original'] = $variant['qty_original'];
			$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;		
			$this->updateBlingUltEnvio($prd, $variant);
			$this->updateCARUltEnvio($prd, $variant);
		}
			
		// limpa os erros de transformação existentes da fase de Oferta Carrefour
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Oferta Carrefour');
		
		return true;
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		$this->prd['qty'] = 0; // zero a quantidade do produto
		
		// verifico se existia como ganhador do leilão 
		$this->bling_ult_envio = $this->model_blingultenvio->getDataByIntToPrdIdVariant($this->int_to,$this->prd['id']);
		
		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($this->prd_to_integration['skumkt'])  == false) { return false;};
		
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
		// var_dump($this->db->queries);
		//$this->syncOffers();
	}

	function getKeys() 
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
		$this->auth_data = json_decode($this->integration_main['auth_data']);
	}
	
	//public function getCategoryMarketplace($skumkt,$int_to = '')
	public function getCategoryMarketplace($skumkt, $int_to = '', $mandatory_category = true) {
		if 	($int_to == '') {$int_to=$this->int_to; }
			
		$categoryId = json_decode($this->prd['category_id']);
		if (is_array($categoryId)) {
			$categoryId = $categoryId[0];
		}
   		$category   = $this->model_category->getCategoryData($categoryId);
		if (!$category) {
			$msg= 'Produto sem categoria.';
			echo 'Produto '.$this->prd['id'].' '.$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, 'Preparação para o envio');
			return false;
		}

		$this->prd['categoryname'] = $category['name']; 
		
		// pego o tipo volume da categoria 
		$tipo_volume   = $this->model_category->getTiposVolumesByCategoryId($categoryId);
		$this->prd['tipovolumecodigo'] = $tipo_volume['codigo']; 	
		
		return $categoryId;
	}
	
	function prepareProduct($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Preparando produto'."\n";

		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para envio');
		
		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();
		
		$this->prd['qty_original'] = $this->prd['qty'];
		if ((int)$this->prd['qty'] < $this->reserve_to_b2W) { // Mando só para a B2W se a quantidade for menor que 5. 
			$this->prd['qty']  = 0;
		}
		$this->prd['qty'] = ceil((int)$this->prd['qty'] * $percEstoque / 100); // arredondo para cima 
		
		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);
		if ($this->prd['promotional_price'] > $this->prd['price'] ) {
			$this->prd['price'] = $this->prd['promotional_price']; 
		}
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
				if  ((int)$this->variants[$key]['qty'] < $this->reserve_to_b2W) { // Mando só para a B2W se a quantidade for menor que 5. 
					$this->variants[$key]['qty'] = 0;
				}
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
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
				
				// se é a conectaLá não usa EAN para o produto
				if ($this->int_to=='CAR') { 
					$this->variants[$key]['EAN'] = null;
				}
			}
		}
		
		if ($this->prd['is_kit']) {  
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
		
		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1 ) { $this->prd['prazo_operacional_extra'] = 1; }
		
		//leio a categoria 
		if ($this->getCategoryMarketplace($sku) == false) return false;
		
		$this->prd['description'] = trim(preg_replace('/\s+/', ' ', $this->prd['description']));
		$this->prd['description'] = str_replace("</p>", '<br>', $this->prd['description']);
		$this->prd['description'] = strip_tags($this->prd['description'],"<b><br><i>");
		$this->prd['description'] = str_replace('"', ' ', $this->prd['description']);
		$this->prd['description'] = str_replace('\'', ' ', $this->prd['description']);
		$this->prd['description'] = str_replace("&nbsp;", ' ', $this->prd['description']);
		
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
			return $this->getProductImages($folder_ori, $path, '', false); // se é uma variação mas não passou o diretório da variação, retorna as imagens do pai
		}
		echo 'Lendo imagens em assets/images/'.$path.'/'.$folder."\n";
		if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder)) {
			return array();
		}
		if ($folder == '') {
			return array();
		}
        $images = scandir(FCPATH.'assets/images/'.$path.'/'.$folder);
        
        if (!$images) {
            return array();
        }
        if (count($images) <= 2) { // não achei nenhuma imagem
			//if ($variacao) { // Mas é uma variação, retorna o array do pai
			//		return $this->getProductImages($folder_ori, $path, '', false); 
			//}
            return array();
        }
		$imagesData = array();
		foreach($images as $foto) {
			if (($foto!='.') && ($foto!='..') && ($foto!='')) {
				if (!is_dir(FCPATH.'assets/images/'.$path.'/'.$folder.'/'.$foto)) {
					$imagesData[] = base_url('assets/images/'.$path.'/' . $folder.'/'. $foto);
				}
			}
		}
        return $imagesData;
    }

	function montaArray($sku, $skumkt, $variant = null) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		$seller_atributte =  "Fabricante:".$this->prd['brandname'];
		if (!is_null($this->prd['actual_width']) && trim($this->prd['actual_width']!=='')) {
			$seller_atributte .= "|".'Largura desembalado'.":".$this->prd['actual_width'].' cm';
		}
		if (!is_null($this->prd['actual_height']) && trim($this->prd['actual_height']!=='')) {
			$seller_atributte .= "|".'Altura desembalado'.":".$this->prd['actual_height'].' cm';
		}
		if (!is_null($this->prd['actual_depth']) && trim($this->prd['actual_depth']!=='')) {
			$seller_atributte .= "|".'Profundidade desembalado'.":".$this->prd['actual_depth'].' cm';
		}
		if (!is_null($this->prd['peso_liquido']) && trim($this->prd['peso_liquido']!=='')) {
			$seller_atributte .= "|".'Peso líquido'.":".$this->prd['peso_liquido'].' kg';
		}
		$attibutes_custom = $this->model_products->getAttributesCustomProduct($this->prd['id']);
		foreach ($attibutes_custom as $attibute_custom) {
			$seller_atributte .= "|".$attibute_custom['name_attr'].":".$attibute_custom['value_attr'];
		}
		
		// vejo se tem campos customizados				
		$produtos_atributos = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($this->prd['id']);
		foreach ($produtos_atributos as $produto_atributo) {
			$id_atributo =  $produto_atributo['id_atributo']; 
			$valor = $produto_atributo['valor'];
			$atributo = $this->model_atributos_categorias_marketplaces->getAtributo($id_atributo);
			if ($atributo['tipo']=='list') {
				$valores = json_decode($atributo['valor'],true );
				foreach ($valores as $valId) {					
					if ($valId['id'] == $produto_atributo['valor']) {
						$valor = $valId['name'];
					}
				}
			}
			$seller_atributte .= "|".$atributo['nome'].":".$valor;
		}
		// sempro pego as imagens do Pai
		$images = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		
		$vardir = '';
		if (!is_null($variant)) {
			$images_var = array();
			if ($this->pathImage == 'product_image'){
				if (!is_null($variant['image']) && trim($variant['image'])!='')	{
					$vardir = '/'.$variant['image'];
					$images_var	= $this->getProductImages($this->prd['image'], $this->pathImage, $vardir, true);
				} 
			}else {
				$var_cat = $this->model_products_catalog->getProductCatalogByVariant($this->prd['product_catalog_id'],$variant['variant'] ); 
				if ($var_cat) {
					$images_var	= $this->getProductImages($var_cat['image'], $this->pathImage, '', false); 
				}
			} 
			$images = array_merge($images_var, $images);  // junto as imagens da variação premeiro e depois a do pai
		}
		
		// $images = $this->getProductImages($this->prd['image'], $this->pathImage, $vardir, !is_null($variant));
		
		if (empty($images)) {
			$msg= 'Produto sem imagem.';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, 'Preparação para o envio');
		}
		
		$produto = array(
			'category_code' => "seller-category",
			'product_sku' => $sku,
			'sku' => $skumkt, 
			'product_title' => $this->prd['name'],
			'weight' => number_format($this->prd['peso_bruto'] * 1000,2,".",""),
			'height' => ($this->prd['altura'] < 2) ? 2 : (int)$this->prd['altura'],
			'width' => ($this->prd['largura'] < 11) ? 11 : (int)$this->prd['largura'],
			'depth' => ($this->prd['profundidade'] < 16) ? 16 : (int)$this->prd['profundidade'],
			'variantImage1' => '',
			'variantImage2' => '',
			'variantImage3' => '',
			'variantImage4' => '',
			'variantImage5' => '',
			'variant_key' => '',
			'variant_code' => '',
			'variant_color' => '',
			'variant_second_color' => '',
			'variant_size' => '',
			'variant_voltage' => '',				
			'ean' => is_null($this->prd['EAN']) ? '' : $this->prd['EAN'],
			'description' => $this->prd['description'],
			'seller_atributte' => $seller_atributte,
			'store_id' => ($this->int_to=='CAR') ? 0 : $this->store['id'], 
			'status' => 0,
		);
		$numft= 1;
		foreach($images as $image) {
			$produto['variantImage'.$numft++] = $image;
			if ($numft==5) { // limite de 5 fotos no carrefour 
				break;
			} 
		}
		// TRATAR VARIANTS		
		if (!is_null($variant)) {
			$tipos = explode(";",$this->prd['has_variants']);
			$valores = explode(";",$variant['name']);
			$ind = array_search('Cor',$tipos);
			if ($ind !== false) {
				if (!array_key_exists($ind,$valores)) {
					$msg= 'Faltando atributo de COR para a variação '. $variant['variant'];
					echo 'Produto '.$this->prd['id']." ".$msg."\n";
					$this->errorTransformation($this->prd['id'],$skumkt,$msg, 'Preparação para o envio');
					return false ;
				}
				$produto['variant_color']= $valores[$ind];   		// variant-color
			}
			$ind = array_search('TAMANHO',$tipos);
			if ($ind !== false) {
				if (!array_key_exists($ind,$valores)) {
					$msg= 'Faltando atributo de TAMANHO para a variação '. $variant['variant'];
					echo 'Produto '.$this->prd['id']." ".$msg."\n";
					$this->errorTransformation($this->prd['id'],$skumkt,$msg, 'Preparação para o envio');
					return false ;
				}
				$produto['variant_size']= $valores[$ind];   		// variant-size
			} 
			$ind = array_search('VOLTAGEM',$tipos);
			if ($ind !== false) {
				if (!array_key_exists($ind,$valores)) {
					$msg= 'Faltando atributo de VOLTAGEM para a variação '. $variant['variant'];
					echo 'Produto '.$this->prd['id']." ".$msg."\n";
					$this->errorTransformation($this->prd['id'],$skumkt,$msg, 'Preparação para o envio');
					return false ;
				}
				$produto['variant_voltage']= $valores[$ind];   		// variant-voltage
			} 
		}
		return $produto;	
	}
    
 
	function syncOffers() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		return; 	
		
		if (count($this->ofertas) == 0) {
			return;
		}
		if ( !is_dir( FCPATH."assets/files/carrefour" ) ) {
		    mkdir( FCPATH."assets/files/carrefour" );       
		}
		$file_prod = FCPATH."assets/files/carrefour/CARREFOUR_OFERTAS_".$this->prd['id']."_".date('dmHi').".csv";
		echo "Arquivo: ".$file_prod."\n";
		
		$myfile = fopen($file_prod, "w") or die("Unable to open file!");
		$header = array('sku','product-id','product-id-type','description','internal-description','price','quantity',
						'state','update-delete'); 
	
		fputcsv($myfile, $header, ";");
		foreach($this->ofertas as $key => $offer) {
			$prdcsv = array(
				'sku' => $offer['sku'],
				'product_id' => $offer['sku'],
				'product_id_type' => "SHOP_SKU",
				'description' => '',
				'internal_description' => '',
				'price' => $offer['price'], 
				'quantity' => $offer['qty'],
				'state' => '11',
				'update-delete' => 'update'
			);
			$this->ofertas[$key]['array_csv'] =  $prdcsv; 
			fputcsv($myfile, $prdcsv, ";");
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
			'company_id'=> $this->prd['company_id'],
			'store_id' => $this->prd['store_id'],
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

		foreach($this->ofertas as $offer) {
			$variant = $offer['variant'];
			
			$this->prd_to_integration['skubling'] =  $offer['sku'];
			$this->prd_to_integration['skumkt'] =  $offer['skumkt'];
			
			$data_log = array( 
				'int_to' => $this->int_to,
				'prd_id' => $this->prd['id'],
				'function' => 'Atualização sku '.$offer['sku'],
				'url' => $url_imp,
				'method' => 'upload '.$file_prod,
				'sent' => 'Import Id: '.$resp['import_id'],
				'response' => json_encode($offer['array_csv']),
				'httpcode' => true,
			);
			
			$data_log = array( 
				'int_to' => $this->int_to,
				'prd_id' => $this->prd['id'],
				'function' => 'Atualizando sku '.$offer['sku'],
				'url' => 'Tabela carrefour_new_offers',
				'method' => 'Create/Update',
				'sent' => json_encode($produto),
				'response' => 'Gravado com sucesso',
				'httpcode' => true,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
				
			if (is_null($variant)) {
				$this->updateBlingUltEnvio($this->prd, null);
				$this->updateCARUltEnvio($this->prd, null);
				
			}
			else {
				$prd = $this->prd;
				$prd['sku'] = $variant['sku'];
				$prd['qty'] = $variant['qty'];
				$prd['price'] = $variant['price'];
				$prd['qty_original'] = $variant['qty_original'];
				$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;		
				$this->updateBlingUltEnvio($prd, $variant);
				$this->updateCARUltEnvio($prd, $variant);
			}
			
			// limpa os erros de transformação existentes da fase de Oferta Carrefour
			$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Oferta Carrefour');
		}
		
	}

	function updateBlingUltEnvio($prd, $variant = null) 
	{
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
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
    		'price' => $prd['promotional_price'],
    		'list_price' => $prd['price'],
    		'qty' => $prd['qty_original'],
    		'sku' => $prd['sku'],
    		'reputacao' => 100,
    		'NVL' => 100,
    		'mkt_store_id' => '',         
    		'data_ult_envio' => $this->dateLastInt,
    		'skubling' => $this->prd_to_integration['skubling'],
    		'skumkt' => $this->prd_to_integration['skumkt'],
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

        $data = $this->formatFieldsUltEnvio($data);
		
		if ($this->bling_ult_envio) {
			if (!is_null($variant)) { // apago o registro antigo do Leilão sem variant
				$this->model_blingultenvio->remove($this->bling_ult_envio['id']);
			}
			else{
				if ($this->bling_ult_envio['EAN'] != $ean) {  // EAN antigo está diferente do novo EAN, então é registro antigo de Leilão. devo remover
					$this->model_blingultenvio->remove($this->bling_ult_envio['id']);
				}
			} 
			
		}
		
		$savedUltEnvio= $this->model_blingultenvio->createIfNotExist($ean, $this->int_to, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela bling_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 	
	}
	
	function updateCARUltEnvio($prd, $variant = null) 
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

    	$data = array(
    		'int_to' => $this->int_to,
    		'prd_id' => $prd['id'],
    		'variant' => $variant_num,
    		'company_id' => $prd['company_id'],
    		'store_id' => $prd['store_id'], 
    		'EAN' => $ean,
    		'price' => $prd['promotional_price'],
    		'list_price' => $this->prd['price'],
    		'qty' => $prd['qty'],
    		'qty_total' => $prd['qty_original'],
    		'sku' => $prd['sku'],
    		'skulocal' => $this->prd_to_integration['skubling'],
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
		
		$savedUltEnvio =$this->model_car_ult_envio->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela car_ult_envio.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 
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
			return $this->getCarrefour($url,$api_key);
		}
	    return $header;
	}

	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		$procura = " WHERE prd_id  = $prd_id AND int_to = '$this->int_to'";

        if (!is_null($variant)) {
            $procura .= " AND variant = $variant";
        }
		return $this->model_blingultenvio->getData(null, $procura);
	}
}