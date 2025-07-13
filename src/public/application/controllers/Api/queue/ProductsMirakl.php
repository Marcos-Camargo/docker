<?php 
/* 
Envia produtos para marketplaces que usam mirakl  
 */
require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

abstract class ProductsMirakl extends ProductsConectala {
	
    var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal ;
	var $integration;
    var $reserve_to_b2W = 5;
	var $ofertas = array();
	var $skumkt;
	var $bling_ult_envio;
  	var $days_to_send_again = 1; // 14
	
	var $mandatory_category = false;    // indica se é obrigatório ter categoria 
	var $mandatory_attributes = false;  // indica se é obrigatório ter os atributos. 
	var $mandatory_ean = false;  // indica se é obrigatório ter ean. 
	var $no_variations = false; // A principio, o Marketplace aceita variações. 
	
	abstract protected function lastPostModel(); 
	
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
		$this->load->model('model_mirakl_new_products');  
		$this->load->model('model_log_integration_product_marketplace'); 
		$this->load->model('model_mirakl_new_offers');
		$this->load->model('model_products_winners');

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
					//return true;
				}
			}
		}
		
		$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
		
		if (($this->mandatory_ean) && (!$this->prd['is_kit'])) { // EAN é mandatório e não é um KIT 
			if ((!$this->prd['EAN']) && (!$this->prd['is_kit'])) {  // não tem variação, não é kit e não tem EAN
				$msg= 'Código de barras (EAN) é obrigatório';
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
				return false;
			}
			// forço o EAN ter 13 digitos
			$this->prd['EANAUCTION'] = substr('00000000000000'. $this->prd['EAN'],-13);
			$sku = $this->prd['EANAUCTION'].$this->int_to;
			
			if ($this->prd['has_variants'] !== '') {  // verifico se as variações tb estão oK
				if (count($this->variants) ==0) {
					$erro = "As variações deste produto ".$this->prd['id']." sumiram.";
		            echo $erro."\n";
		            $this->log_data('batch', $log_name, $erro,"E");
					die;
				}
				foreach($this->variants as $variant) { // verifico se todas as variações tem EAN	
					if (!$variant['EAN']) {
						$msg= 'Código de barras (EAN) é obrigatório e está faltando em uma variação ';
						echo 'Produto '.$this->prd['id']." ".$msg."\n";
						$this->errorTransformation($this->prd['id'],$sku,$msg,"Preparação para o envio");
						return false;
					}
				}
			}	
			
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
				echo "inserindo a variação ".$variant['variant']."\n";
				$this->insertProductVariant($sku,$variant);
			}
		}
		else {
			$this->insertProductVariant($sku);
		}

	}

	function auctionVerify($new_product) {
		

		if ($new_product['status'] != 1) { // produto está inativo. 
			echo "Produto ".$new_product['id']." está inativo \n"; 
			return false; 
		} 
		if ($new_product['situacao'] != 2) { // produto está incompleto 
			echo "Produto ".$new_product['id']." está incompleto \n"; 
			return false; 
		} 
		if ($new_product['qty'] <= $this->reserve_to_b2W ) {  // sem estoque
			echo "Produto ".$new_product['id']." sem estoque \n"; 
			return false; 
		}
		$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to, $new_product['id']);
		if (!$prd_to_integration) {
			echo "Produto ".$new_product['id']." não possui integração para ".$this->int_to."\n"; 
			return false; 
		}
		if ($prd_to_integration['status'] == 0){
			echo "Produto ".$new_product['id']." com integração desligadapara ".$this->int_to."\n"; 
			return false; 
		}
		if ($prd_to_integration['approved'] !== '1'){
			echo "Produto ".$new_product['id']." ainda não foi aprovado para ".$this->int_to."\n"; 
			return false; 
		}
		$store = $this->model_stores->getStoresData($new_product['store_id']);
		if ($store['active'] !== '1') {
			echo  "A loja do produto ".$new_product['id']." inativa\n"; 
			return false; 
		}
		
		if ($this->mandatory_category) { // verifico se tem a categoria e se está linkado ao marketplace 
			$categoryId = json_decode($new_product['category_id']);
			if (is_array($categoryId)) {
				$categoryId = $categoryId[0];
			}
   			$category   = $this->model_category->getCategoryData($categoryId);
			if (!$category) {
				echo " novo ganhador ainda não tem categoria \n";
				return false;
			}	
			// pego a categoria do marketplace
			$result= $this->model_categorias_marketplaces->getCategoryMktplace($this->int_to,$categoryId);
			if (!$result) {
				echo " novo ganhador ainda não tem categoria configurada no marketplace \n";
				return false;
			}
			$new_product['categoria_mkt_id'] = $result['category_marketplace_id'];
		}
		if ($this->mandatory_attributes) { // Verifico se tem os atributos obrigatórios
			$seller_atributte = $this->getSellerAtributesNew('', $new_product, null,  false); 
			if ($seller_atributte == false) {
				echo " novo ganhador tem atributos obrigatórios faltando \n";
				return false; 
			}
		}
		
		
		// Tudo Ok
		return true; 
	}

	function runAuction($auction_status, $sku) 
	{
		echo "Rodando o Leilão!\n";

		if (!$auction_status) {
			echo "Nunca houve um campeão. Então ganhei!";
			// Me gravo como Ganhador do Leilão.... 
			$winner_data = array(
	            'int_to'                => $this->int_to,
	            'ean'                   => $this->prd['EANAUCTION'],
	            'current_store_id'      => $this->prd['store_id'],
	            'current_product_id'    => $this->prd['id'],
	            'store_id_1'            => $this->prd['store_id'],
	            'store_id_2'            => $this->prd['store_id'],
	            'product_id_1'          => $this->prd['id'],
	            'product_id_2'          => $this->prd['id'], 
	            'first_winner'          => $this->prd['id']
	        );
	        $winner = $this->model_products_winners->saveNewWinner($winner_data);
			return true;   // continuo o processamento 
		}

        // Pego a lista de produtos já ordenado pelo ganhador deste leilão  
        echo " EAN ==". $this->prd['EANAUCTION']."\n";
		$winner = $this->model_products_winners->getProducts($this->prd['EANAUCTION']);
		if (!$winner) {
			echo " não achei um novo ganhador, mantem o antigo\n";
			return true; 
		}
		
		// verifico se sou o atual ganhador 
		if ($auction_status['current_product_id'] == $this->prd['id']) {
			if ($winner['id'] == $this->prd['id']) { //Ganhei novamente ?
				echo "Continuo como ganhador do leilão.\n";
				return true; // continuo o processamento 
			}
			else {
				echo "Sou o atual mas perdi\n";
				$retorno = true;
			} 
		}
		else {
			// verifico se ganhei já que não sou o atual. 
			if ($winner['id'] != $this->prd['id']) {
			    echo "Não ganhei\n";
				$prd_upd = array (
					'skubling' 		=> $sku,
					'skumkt' 		=> $sku,
					'status_int' 	=> 11,
					'date_last_int' => $this->dateLastInt,
				);
				$this->model_integrations->updatePrdToIntegration($prd_upd, $this->prd_to_integration['id']);
				
				return false; // Não continua o processamento pois não ganhei. 
			}
			else {
				echo "Não sou o atual mas ganhei\n";
				$retorno = false;
			}	
		}
		var_dump ($winner);
		echo " Verificando se o ganhador ".$winner['id']." realmente pode ganhar\n";
		
        $new_product = $this->model_products->getProductData(0, $winner['id']);
        $old_product = $this->model_products->getProductData(0, $auction_status['current_product_id']);
		
		
		$status_int = 1;
        //se o que ta no ar tem variacao, nao aceitar outros.
        //se o que ta no ar nao tem, mas o novo tem, nao aceitar
        if( ($old_product['has_variants'] != '') || ($old_product['has_variants'] == '' && $new_product['has_variants'] != '') ) {
        	echo " ter ou não ter variação não bate\n";
        	$status_int = 14;
        }
                           
        if($new_product['has_variants'] != $old_product['has_variants']) { // as variantes não batem, então mantenho o atual vencedor
        	echo " as variações não bate\n ";
        	$status_int = 14;
        }
        
		if($new_product['brand_id'] != $old_product['brand_id']) { // as marcas não batem, então mantenho o atual vencedor 
        	echo " a marca não bate\n ";
        	$status_int = 14;
        }
		
		/* tenho q verificar se o novo ganhador está completo para este marketplace */
		
		if (!$this->auctionVerify($new_product)) { // O novo produto tem algum problema para este marketplace?
        	$status_int = 14;
        }
		
		if ($status_int == 1) {
			echo "Novo ganhador {$winner['id']} \n";
			$new_winner = $this->model_products_winners->updateWinner($this->prd['EAN'], $winner, $this->int_to);
		}
		else {
			echo "Ganhador incompatível\n";
		}
		
		if ($winner['id'] != $this->prd['id']) {
			$status_int = 14;
		}
		// atualizo o prd_to_integration 
		$prd_upd = array (
				'skubling' 		=> $sku,
				'skumkt' 		=> $sku,
				'status_int' 	=> $status_int,
				'date_last_int' => $this->dateLastInt,
		);
		$this->model_integrations->updatePrdToIntegration($prd_upd, $this->prd_to_integration['id']);
		return $retorno;
		
	}
	
	function updateProduct()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if ($this->prd_to_integration['status_int'] ==21) {// Ainda não enviou para a Mirakl. 
			return $this->insertProduct(); 
		}
		echo "Update"."\n";
		 // verifico se é ganhador do leilão atual 
		
		if (($this->mandatory_ean) && (!$this->prd['is_kit'])) { // EAN é mandatório e não é um KIT 
			
			$this->prd['EANAUCTION'] = substr('00000000000000'. $this->prd['EAN'],-13);
			// Pego a linha atual do ganhador do leilão deste EAN
	        $auction_status = $this->model_products_winners->getWinner($this->prd['EANAUCTION'], $this->int_to);
	        if ($this->prd_to_integration['prd_id'] != $auction_status['current_product_id']) {
	        	echo "Não é o atual ganhador do leilão\n";
	     		if (!$this->runAuction($auction_status, $this->prd_to_integration['skumkt'])) {
					echo "Saindo...\n";
					return; 
				}
	        }
		}
		
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
				$this->updateProductVariant($variant);
			}
		}
		else {
			$this->updateProductVariant();
		}
		
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
		$produto['int_to'] = $this->int_to;
		
		///********************* LEILAO ************/
		if (($this->mandatory_ean) && (!$this->prd['is_kit'])) { // EAN é mandatório e não é um KIT 
		// Pego a linha atual do ganhador do leilão deste EAN
			$auction_status = $this->model_products_winners->getWinner($this->prd['EANAUCTION'], $this->int_to);
			// rodo o leilão 
			if (!$this->runAuction($auction_status, $sku)) {
				return false; // não ganhou ou já tinha um ganhador no dia de hoje.
			}
		}
		
		echo 'Incluindo o produto '.$this->prd['id'].' '.$this->prd['name'].' SKU:'.$sku,"\n";
		
		$this->model_mirakl_new_products->createIfNotExist($sku,$produto); // outro programa processa e envia para o Mirakl o CSV 
		
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
				'url' => 'Tabela mirakl_new_products',
				'method' => 'Create/Update',
				'sent' => json_encode($produto),
				'response' => 'Gravado com sucesso',
				'httpcode' => true,
			);
		$this->model_log_integration_product_marketplace->create($data_log);

		// limpa os erros de transformação existentes da fase Preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para o envio');
		
		return true;	
		
	}
	
	function updateProductVariant($variant = null,$status_int =2)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Update'."\n";
		
		$price = $this->prd['promotional_price'];
		$original_price = $this->prd['price'];
		$qty = $this->prd['qty'];
		$sku_prd = $this->prd['sku'];
		$ean = $this->prd['EAN'];
		if (is_null($variant)) {  // não tem variação. 
			$sku = $this->prd_to_integration['skubling']; 
			$skupai = $sku;
		}
		else { // sou uma variação. 
			// verifico se já tenho prd_to_integration na minha variação. 
			$prd_int = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'],$this->int_to, $variant['variant']);
			if (!$prd_int) { // Jeito velho sem prd_to_integration com variação
				$sku = 'P'.$this->prd['id'].'S'.$this->prd['store_id'].$this->int_to;
				return $this->insertProductVariant($sku,$variant);
			} else if (($prd_int['status_int'] == 21) || ($prd_int['status_int'] == 22)){
				echo " Variação ainda em cadastramento\n";
				return $this->insertProductVariant($prd_int['skumkt'],$variant);	
			}
			$sku = $prd_int['skubling'];
			$skupai = $prd_int['skumkt']; 
			$price = $variant['promotional_price'];
			$original_price = $variant['price'];
			$qty = $variant['qty'];
		} 
		echo " sku = ".	$sku."\n";
		
		// Monto o Array para enviar para o Mirakl
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

		$offer_data = array(
			'int_to' 				=> $this->int_to,
			'skulocal' 				=> $sku,
			'skumkt' 				=> $skupai, 
			'variant' 				=> is_null($variant) ? null : $variant['variant'], 
			'price'					=> $price, 
			'qty' 					=> $qty,
			'status' 				=> 0,
			'store_id' 				=> 0, 
			'prd_to_integration_id' => $prd_int['id'],
			'prd_id' 				=> $this->prd['id'], 
			'original_price'		=> $original_price, // usado no GPA 
			'leadtime_to_ship' 		=> (is_null($this->prd['prazo_operacional_extra'])) ? 1 : $this->prd['prazo_operacional_extra'], // usado no GPA 
		);
		$this->model_mirakl_new_offers->createIfNotExist($sku, $offer_data);
			
		$data_log = array( 
			'int_to' => $this->int_to,
			'prd_id' => $this->prd['id'],
			'function' => 'Atualizando sku '.$sku,
			'url' => 'Tabela mirakl_new_offers',
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
			$this->updateMiraklUltEnvio($this->prd, null);
		}
		else {
			$prd = $this->prd;
			$prd['sku'] = $variant['sku'];
			$prd['qty'] = $variant['qty'];
			$prd['price'] = $variant['price'];
			$prd['qty_original'] = $variant['qty_original'];
			$prd['EAN'] = ($variant['EAN']!='')? $variant['EAN']:$this->prd['EAN'] ;		
			$this->updateBlingUltEnvio($prd, $variant);
			$this->updateMiraklUltEnvio($prd, $variant);
		}
			
		// limpa os erros de transformação existentes da fase de Oferta No marketplace
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para o envio');
		// limpa os erros de transformação existentes da fase de preparação para envio
		// $this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'],$this->int_to);
		return true;
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$this->update_price_product = false;
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Inativando\n";
		$this->prd['qty'] = 0; // zero a quantidade do produto
		
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
	
	function prepareProduct($sku) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo 'Preparando produto'."\n";

		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductIdStep($this->prd['id'],$this->int_to,'Preparação para envio');
				
		$this->prd['categoria_mkt_id'] = $this->getCategoryMarketplace($sku, $this->int_to, $this->mandatory_category);
		if ($this->prd['categoria_mkt_id'] == false) return false;
		if (!$this->mandatory_category) {
			$this->prd['categoria_mkt_id'] = 'seller-category';
		}
		
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
    		$variacoes = explode(";",$this->prd['has_variants']);
			
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
		
		// verifico e tem o atributo marca com lista no marketplace e substituo pelo valor de lá - rick 
		if ($this->int_to == 'GPA') {
			$achou = true;
			$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoria_mkt_id'],'Marca', $this->int_to);
			$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($this->prd['id'],$atributoCat['id_atributo'],$this->int_to);
		
			if ($atributo_prd) { // se definiu nos atributos, pega de lá, se não usa do cadastro normal
				$this->prd['brandname'] = $atributo_prd['valor'];
			}
			else{
				if ($atributoCat['tipo']=='list') {
					$achou = false;
					$valores = json_decode($atributoCat['valor'],true );
					foreach ($valores as $valId) {			//	Vejo se acho no cadastro normal
						if (strtolower($valId['Value']) == strtolower($this->prd['brandname'])) {
							$this->prd['brandname'] = $valId['FieldValueId'];
							$achou = true;
							break; 
						}
					}
				}
				if (!$achou) {
					$msg= 'Produto com Marca '.$this->prd['brandname'].' não cadastrado no marketplace  '.$this->int_to;
					echo 'Produto '.$this->prd['id']." ".$msg."\n";
					$this->errorTransformation($this->prd['id'],$sku,$msg, "Preparação para o envio");
					return false;
				}
			}
		}

		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1 ) { $this->prd['prazo_operacional_extra'] = 1; }
			
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

	function getSellerAtributes($skumkt) {  // modelo Carrefour 
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
		
		return $seller_atributte;
	}

	function getSellerAtributesNew($skumkt, $prd, $variant = null, $write_error = true) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// echo "Entrei no novo de atributos\n";
		
		$seller_atributte = ''; 	
		if (!is_null($variant))  {
			$variacoes = explode(";",$prd['has_variants']);
			$valores_var = explode(";",$variant['name']);
			
			// vejo se tudo bem com as variações
			foreach ($variacoes as $key => $variacao) {
				if ($this->int_to == 'GPA') {
					if ($variacao == 'TAMANHO') {
						 $variacao = 'Dimensão do produto';  
					}
				}
				// echo $variacao."\n";
				$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($prd['categoria_mkt_id'],ucfirst(strtolower($variacao)), $this->int_to);
				//var_dump($atributoCat);
				if (!$atributoCat) {
					$catMl =  $this->model_categorias_marketplaces->getAllCategoriesById($this->int_to,$prd['categoria_mkt_id']);
					$msg= 'Categoria '.$prd['categoria_mkt_id'].'-'.$catMl['nome'].' não tem o atributo '.$variacao;
					if ($write_error) {
						$this->errorTransformation($prd['id'],$skumkt,$msg,"Preparação para Envio");
					}
					return false;
				}
				else {
					$valor = $valores_var[$key]; 
					if ($atributoCat['tipo']=='list') {
						$valores = json_decode($atributoCat['valor'],true );
						//var_dump($valores);
						foreach ($valores as $valId) {					
							if ($valId['Value'] ==$valores_var[$key]) {
								$valor = $valId['FieldValueId'];
							}
						}
					}
					
					$seller_atributte .= $atributoCat['id_atributo'].":".$valor.'|';
				}
			}
		}
	//echo $seller_atributte."\n";

		// Busco os atributos específicos do produto para este marketplace 
		$atributos = array();
		$atributosCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($prd['categoria_mkt_id'],$this->int_to);
		foreach($atributosCat as $atributoCat) {
			$atributo_prd = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($prd['id'],$atributoCat['id_atributo'],$this->int_to);
			if (strpos($seller_atributte, $atributoCat['id_atributo']) === false) {  // pode ter sido uma variação.
				if ($atributo_prd) {  // tem um valor para o atributo. 
					$valor = $atributo_prd['valor']; 
					if ($atributoCat['tipo']=='list') {
						$valores = json_decode($atributoCat['valor'],true );
						//var_dump($valores);
						foreach ($valores as $valId) {					
							if ($valId['Value'] == $atributo_prd['valor']) {
								$valor = $valId['FieldValueId'];
							}
						}
					}
					$seller_atributte .= $atributo_prd['id_atributo'].":".$valor.'|';
					
				} else { // não tem o valor 
					if ($atributoCat['obrigatorio'] == 1) { // mas é obrigatório
						$msg= 'Atributo obrigatório não preenchido: '.$atributoCat['nome'];
						echo 'Produto '.$prd['id']." ".$msg."\n";
						if ($write_error) {
							$this->errorTransformation($prd['id'],$skumkt,$msg, 'Preparação para o envio');
						}
						return false;
					}
				}
			}
		}
		
		if  ( $seller_atributte != '') {
			$seller_atributte = substr($seller_atributte,0, -1);
		}
		return $seller_atributte;
	}	
	
	function montaArray($sku, $skumkt, $variant = null) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		
		if ($this->mandatory_attributes) { // jeito do GPA
			$seller_atributte = $this->getSellerAtributesNew($skumkt,  $this->prd, $variant, true); 
		} else {  // jeito do carrefour 
			$seller_atributte = $this->getSellerAtributes($skumkt); 
		}
		if ($seller_atributte == false) return false; 
		
		// sempro pego as imagens do Pai
		$images = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
		
		$nome_ext ='';
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
			$images = array_merge($images_var, $images);  // junto as imagens da variação primeiro e depois a do pai
			
			//
			if ($this->no_variations) {  // se o marketplace não tem variações, as variações vão para o título
				$tipos = explode(';',$this->prd['has_variants']);
				$values = explode(';',$variant['name']);
				foreach ($tipos as $z => $campo) {
					if (strpos(strtoupper($this->prd['name']), strtoupper($values[$z])) ==- false) {  // se a variação não está no nome
						$nome_ext .= mb_convert_case($values[$z], MB_CASE_TITLE, "UTF-8")." ";;
					}
				}
			}
			
		
		}
		
		// $images = $this->getProductImages($this->prd['image'], $this->pathImage, $vardir, !is_null($variant));
		
		if (empty($images)) {
			$msg= 'Produto sem imagem.';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, 'Preparação para o envio');
			return false;
		}
		
		$title = $this->prd['name'];
		if (strlen(trim($this->prd['name'].' '.$nome_ext)) <= 150) {
			$title = trim($this->prd['name'].' '.$nome_ext);
		}
		$produto = array(
			
			'int_to' 				=> $this->int_to,
			'product_sku' 			=> $sku,
			'category_code' 		=> $this->prd['categoria_mkt_id'],
			'sku' 					=> $skumkt, 
			'product_title' 		=> $title,
			'weight' 				=> number_format($this->prd['peso_bruto'] * 1000,2,".",""),
			'height' 				=> ($this->prd['altura'] < 2) ? 2 : (int)$this->prd['altura'],
			'width' 				=> ($this->prd['largura'] < 11) ? 11 : (int)$this->prd['largura'],
			'depth' 				=> ($this->prd['profundidade'] < 16) ? 16 : (int)$this->prd['profundidade'],
			'variantImage1' 		=> '',
			'variantImage2' 		=> '',
			'variantImage3' 		=> '',
			'variantImage4' 		=> '',
			'variantImage5' 		=> '',
			'variant_key' 			=> '',
			'variant_code' 			=> '',
			'variant_color' 		=> '',
			'variant_second_color' 	=> '',
			'variant_size' 			=> '',
			'variant_voltage' 		=> '',				
			'ean' 					=> is_null($this->prd['EAN']) ? '' : $this->prd['EAN'],
			'description' 			=> $this->prd['description'],
			'seller_atributte' 		=> $seller_atributte,
			'store_id' 				=> ($this->integration_store['int_from'] == 'CONECTALA') ? 0 : $this->store['id'], 
			'status'				=> 0,
			'brand' 				=> $this->prd['brandname'],  // utilizado no GPA mas não no Carrefour
			'is_kit'				=> $this->prd['is_kit'],
		);
		
		$numft= 1;
		foreach($images as $image) {
			$produto['variantImage'.$numft++] = $image;
			if ($numft==5) { // limite de 5 fotos no mirakl 
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

	function updateBlingUltEnvio($prd, $variant = null) 
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
	
	function updateMiraklUltEnvio($prd, $variant = null) 
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
		
		$modelo = $this->lastPostModel();
		
		$savedUltEnvio = $modelo->createIfNotExist($this->int_to,$prd['id'], $variant_num, $data); 
		if (!$savedUltEnvio) {
            $notice = 'Falha ao tentar gravar dados na tabela gpa_last_post.';
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,'E');
			die;
        } 
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