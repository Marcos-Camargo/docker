<?php

require APPPATH . "/libraries/REST_Controller.php";

/**
 * @property CI_Loader $load
 *
 * @property Model_integrations $model_integrations
 * @property Model_log_integration_product_marketplace $model_log_integration_product_marketplace
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_promotions $model_promotions
 * @property Model_products $model_products
 * @property Model_stores $model_stores
 * @property Model_products_catalog $model_products_catalog
 * @property Model_settings $model_settings
 * @property Model_whitelist $model_whitelist
 * @property Model_blacklist_words $model_blacklist_words
 * @property Model_campaigns_v2 $model_campaigns_v2
 *
 * @property calculoFrete $calculofrete
 * @property BlacklistOfWords $blacklistofwords
 */

abstract class ProductsConectala extends REST_Controller
{
	
	const INATIVO = 90 ;
	const SEMESTOQUE = 10 ;
	const SEMTRANSPORTADORA = 91 ;
	const INICIOCADASTRAMENTO = 20 ;
	const MEIOCADASTRAMENTO = 21 ;
	const EMCADASTRAMENTO = 22 ;
	const EMANALISE = 0;
	const FILAENVIO = 1; 
	
	protected $queue_id; 
	protected $prd; 
	protected $messageHasShipCompany;
	protected $variants; 
	protected $store; 
	protected $pathImage;
	protected $prd_to_integration; 
	protected $dateLastInt;
    protected $only_send_images_from_sku = false;
	
	protected $result;
    protected $responseCode;
    protected $accountName;
    protected $header;
	
	protected $int_to =""; 
	protected $integration_main = array(); 
 	protected $integration_store = array();
	protected $stores_multi_cd = false; 
	protected $from_inactivate = false;

    // inidica se é acontecer a atualização de preço nas tabelas *_ult_envio, *_last_post
    public $update_price_product = true;
    public $remove_product_queue = true;

	abstract protected function insertProduct();

	abstract protected function updateProduct();

	abstract protected function inactivateProduct($status_int, $disable, $variant = null);
	
	protected function hasShipCompany()
    {
		try {
			$this->load->library('calculoFrete');
			$this->calculofrete->productCanBePublished($this->prd['store_id']);
		} catch (Exception $exception) {
			$this->messageHasShipCompany = $exception->getMessage();
			return false;
		}
		return true;
	}
	
	protected $suffixDns;
	

    public function __construct()
    {
        parent::__construct();
		
		$this->load->model('model_integrations');
		$this->load->model('model_log_integration_product_marketplace');
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_promotions');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_products_catalog');
		$this->load->model('model_settings');
		$this->load->model('model_whitelist');
		$this->load->model('model_blacklist_words');
		$this->load->model('model_campaigns_v2');
		$this->load->model('model_stores_multi_channel_fulfillment');
        $this->load->library('BlacklistOfWords');

        $only_send_images_from_sku = $this->model_settings->getSettingDatabyName('only_send_images_from_sku');
        if ($only_send_images_from_sku && $only_send_images_from_sku['status'] == 1) {
            $this->only_send_images_from_sku = true;
        }

		$this->dateLastInt = date('Y-m-d H:i:s');
		$this->suffixDns = '.com.br';
		$this->messageHasShipCompany = false;

		$this->stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;
    }
	
	public function verifyHeader($headers): bool
    {
		foreach ($headers as $header => $value) {
			if ((strtolower($header) == 'x-local-appkey') && ($value == '32322rwerwefwr2343qefasfsfa312e4rfwedsdf')) {
				return true;
			}
		}
		return false;
	}
	
	public function index_get()
	{
		show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,"An Error Was Encountered");
	}
	
	protected function setSuffixDns($setSuffixDns) 
    {
        $this->suffixDns = $setSuffixDns;
	}

	protected function beforeGetProductData($prd_id) {
		return ;
	}

    public function receiveData()
	{

		ignore_user_abort(true);
		set_time_limit(0);

		if (!$this->verifyHeader(getallheaders())) {
		 	$error =  "No authentication key";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
		 	die;
		} 
		
		$data = json_decode(file_get_contents('php://input'), true);
		if (is_null($data)) {
			$error = "Dados fora do formato json!";
			show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die;
		}
		
		$this->queue_id  	= $data['queue_id'];
        $prd_id				= $data['product_id'];

		$this->beforeGetProductData($prd_id);

		// ler o produto e variants; 
		$this->prd=$this->model_products->getProductData(0,$prd_id);
		// leio a loja
		$this->store    = $this->model_stores->getStoresData($this->prd['store_id']);
		// leio as variações
		$this->variants = array();
		if ($this->prd['has_variants'] != '') {
			$this->variants = $this->model_products->getVariants($prd_id);
		}
		// pego os dados do catálogo do produto se houver 
		if (!is_null($this->prd['product_catalog_id'])) {
			$prd_catalog = $this->model_products_catalog->getProductProductData($this->prd['product_catalog_id']); 
			$this->prd['name'] 				= $prd_catalog['name'];
			$this->prd['description'] 		= $prd_catalog['description'];
			$this->prd['EAN'] 				= $prd_catalog['EAN'];
			$this->prd['largura'] 			= $prd_catalog['width'];
			$this->prd['altura'] 			= $prd_catalog['height'];
			$this->prd['profundidade'] 		= $prd_catalog['length'];
			$this->prd['peso_bruto'] 		= $prd_catalog['gross_weight'];
			$this->prd['ref_id'] 			= $prd_catalog['ref_id']; 
			$this->prd['brand_code'] 		= $prd_catalog['brand_code'];
			$this->prd['brand_id'] 			= '["'.$prd_catalog['brand_id'].'"]'; 
			$this->prd['category_id'] 		= '["'.$prd_catalog['category_id'].'"]';
			$this->prd['image'] 			= $prd_catalog['image'];
			$this->pathImage 				= 'catalog_product_image';
			/* campos específicos se baixou o catalago da Vtex */
			$this->prd['mkt_sku_id'] 		= $prd_catalog['mkt_sku_id'];
			$this->prd['mkt_product_id'] 	= $prd_catalog['mkt_product_id'];
			$this->prd['product_name'] 		= $prd_catalog['product_name'];  // nome origial do produto na vtex
			$this->prd['sku_name'] 			= $prd_catalog['sku_name'];  // nome origial do sku na vtex
			$this->prd['is_on_bucket']		= $prd_catalog['is_on_bucket'];
		}
		else {
			$this->prd['mkt_product_id'] 	= null; // importante para a Vtex e inidicar que vai ser match de product e não de sku 
			$this->pathImage 				= 'product_image';
		}

		// Verifico a loja multi-cd para verificar o estoque do produto ou variações se está zerado ou não 
		if ($this->stores_multi_cd) {
			if (($this->store['type_store'] ==1 ) && ($this->store['active'] ==1) && $this->prd['status'] == 1) { // é um produto de loja multi_cd
                $cd_store_only = $this->store['inventory_utilization'] == 'cd_store_only';
				$all_stores = array();
                // Estoque não é somente da loja principal.
                if ($this->store['inventory_utilization'] != 'main_store_only') {
                    $all_store = $this->model_stores_multi_channel_fulfillment->getStoresCD($this->prd['store_id'], $this->prd['company_id']);
                    if ($all_store) {
                        $all_stores = array();
                        foreach ($all_store as $st) {
                            $all_stores[] = $st['store_id_cd'];
                        }
                    }
                    if (!empty($all_stores)) {
                        if ($this->prd['has_variants'] == '') {
                            if ($this->prd['qty'] == 0) {
                                $findSku = $this->model_products->getOtherStoreFullFilmentProduct($this->prd['sku'], $all_stores);
                                if ($findSku) {
                                    $this->prd['qty'] = $findSku['qty'];
                                }
                            }
                        } else {
                            foreach ($this->variants as $key => $variant) {
                                if ($variant['qty'] == 0) {
                                    $findSku = $this->model_products->getOtherStoreFullFilmentVariant($variant['sku'], $all_stores);
                                    if ($findSku) {
                                        $this->variants[$key]['qty'] = $findSku['qty'];
                                        $this->prd['qty'] += $findSku['qty'];
                                    }
                                }
                            }
                        }
                    }
                    // Não tem CD para atender e o estoque será somente do CD.
                    else if ($cd_store_only) {
                        $this->prd['qty'] = 0;
                    }
                }
			}
		}

		return ;
	}
	
	public function removeFromQueue()
	{
		$this->model_queue_products_marketplace->remove($this->queue_id);
	}

    public function getIntegrationSettings()
    {
        return $this->model_integrations->getIntegrationSettings($this->int_to);
    }

	public function checkBlackList()
	{
		$productCheckBlackWhiteList = array_merge($this->prd, ['marketplace' => $this->int_to]);
		// consultar white/black list
        $whiteList = $this->model_whitelist->searchWhitelist($this->blacklistofwords->getProductForCheck($productCheckBlackWhiteList));
        $blackList = $this->model_blacklist_words->getDataBlackListActive($this->blacklistofwords->getProductForCheck($productCheckBlackWhiteList));

        // consultar se produto deve ser bloqueado
        if ($blackList) {
            $hasLockByMarketplace = $this->blacklistofwords->getBlockProduct($this->prd, $this->prd['id'], $whiteList, $blackList, true);
        } else { $hasLockByMarketplace['blocked'] = false; }

        echo 'hasLockByMarketplace = ' . json_encode($hasLockByMarketplace) . "\n";

        $statusBlockPrdInt = $hasLockByMarketplace['blocked'] ? 0 : 1;
        if ($hasLockByMarketplace['blocked']) {
            $ruleBlockPrdInt = array();
            if (!isset($hasLockByMarketplace['data_row'])) {$hasLockByMarketplace['data_row'] = [];}
            if (!is_array($hasLockByMarketplace['data_row'])) {$hasLockByMarketplace['data_row'] = (array)$hasLockByMarketplace['data_row'];}
            foreach ($hasLockByMarketplace['data_row'] as $rulesBlock) {
                array_push($ruleBlockPrdInt, $rulesBlock['blacklist_id']);
			}
            $ruleBlockPrdInt = json_encode($ruleBlockPrdInt);
        } else {$ruleBlockPrdInt = null;}

        echo 'ruleBlockPrdInt = ' . json_encode($ruleBlockPrdInt) . "\n";
		
		$msgvar ='';
		if (!is_null($this->prd_to_integration['variant'])) {
			$msgvar =' variant: '.$this->prd_to_integration['variant'].' ';
		}
		
		// adiciono produto ativo na fila para a integração
        if ($ruleBlockPrdInt) {
        	$statusBlockPrdInt =0 ; 
            echo 'Bloqueando para '. $this->prd_to_integration['int_to']." Regra(s): {$ruleBlockPrdInt}\n";
		}
	    else {
	    	$statusBlockPrdInt =1;
	    	echo "Produto sem bloqueio\n";
	    }
		
		$this->model_integrations->updatePrdToIntegration(Array(
				'rule' => $ruleBlockPrdInt,
			),$this->prd_to_integration['id']);
		
		$this->prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to, $this->prd['id']);
		return $statusBlockPrdInt;
	}

	/**
	 * Seta o produto atual como sendo inativado.
	 * Impede que update insert product seja chamado durante as chamadas.
	 */
	protected function setInactivate(){
		$this->from_inactivate = true;
	}
	
    public function checkAndProcessProduct() 
	{
		$this->prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to, $this->prd['id']);
		if  (!$this->prd_to_integration) {

			if (!$this->checkMultifullfillment($this->prd, $this->store)) { // produto multi-cd de loja secundaria não tem prd_to_integration 
				$this->log("Produto ".$this->prd['id']." não possui integração para ".$this->int_to); 
			}
			return; 
			
		}
		
		//pego a integração da loja e a da principal se precisar.  
		$this->getIntegration(); 

		$price = $this->getPrice(null);
		
		$skumkt = $this->prd_to_integration['skumkt'];
		if (is_null($skumkt)) { // Nunca foi integrado
			if ($this->integration_store['active'] == 0) {
				$this->log("Integração da loja  ".$this->integration_store['store_id']." inativa para ".$this->int_to);
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::EMANALISE, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return;
			}
			if ($this->integration_store['active'] == 0){
				$this->log("Produto ".$this->prd['id']." nunca integrado para ".$this->int_to);
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::INATIVO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return;
			}

			if ($this->prd_to_integration['status'] == 0){
				$this->log("Produto ".$this->prd['id']." nunca integrado para ".$this->int_to);
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::INATIVO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return;
			}
			if ($this->prd_to_integration['approved'] !== '1'){
				$this->log("Produto ".$this->prd['id']." ainda não foi aprovado para ".$this->int_to);
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::EMANALISE, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return;
			}
			if ($this->prd['status'] != 1) { // produto está inativo.
				$this->log("Produto ".$this->prd['id']." está inativo "); 
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::INATIVO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return; 
			} 
			if ($this->prd['situacao'] != 2) { // produto está incompleto 
				$this->log("Produto ".$this->prd['id']." está incompleto "); 
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::INATIVO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return; 
			} 
			if ($this->prd['qty'] == 0) {  // sem estoque
				$this->log("Produto ".$this->prd['id']." sem estoque ");
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::SEMESTOQUE, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return;
			}
			if ($this->checkPriceMin($price)) { //preço menor que o minimo
				$minimo = $this->model_settings->getValueIfAtiveByName('preco_minimo');
				$this->log("Produto ".$this->prd['id']." com preço menor que o minimo");
				$this->errorTransformation($this->prd['id'], "", "Produto com preço abaixo do valor minimo: ". $minimo, "Preparação para o envio");
				return;
			}
			if (!$this->hasShipCompany()){ // sem transportadora 
				$this->log("Produto ".$this->prd['id']." sem transportadora ");
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::SEMTRANSPORTADORA, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				if ($this->messageHasShipCompany !== false) {
					$this->errorTransformation($this->prd['id'], '', $this->messageHasShipCompany, "Preparação para o envio");
				}
				return;
			}
			if ($this->store['active'] !== '1') {
				$this->log("A loja do produto ".$this->prd['id']." inativa aqui");
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::INATIVO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return;
			}
			// verifico se precisa bloquear 
			if ($this->checkBlackList() == 0){
				$this->log("Produto ".$this->prd['id']." Produto Bloqueado para ".$this->int_to); 
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::INATIVO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return; 
			}
			if($this->store['is_vacation'] == 1){
				$this->log("A loja do produto ".$this->prd['id']." esta de férias");
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::INATIVO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
				return;
			}
			
			// Tudo bem, então vou cadastrar Aqui eu cadastro então 
			$this->model_integrations->updatePrdToIntegration(array('status_int'=>self::FILAENVIO, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
			$this->remove_product_queue = $this->insertProduct();
			return ;
		}	
	
		// Ja teve algum integração antes vejo se algo mudou
		if ($this->integration_store['active'] == 0) {
			$this->log("Integração da loja  ".$this->integration_store['store_id']." inativa para ".$this->int_to);
			$this->inactivateProduct(self::INATIVO, false);
			return;
		}
		if ($this->prd_to_integration['status'] == 0){
			$this->log("Produto ".$this->prd['id']." integração inativada ".$this->int_to);
			$this->inactivateProduct(self::INATIVO, true);
			return;
		}
		if ($this->prd['status'] != 1) { // produto está inativo.
			$this->log("Produto ".$this->prd['id']." inativo ");
			$this->inactivateProduct(self::INATIVO, true);
			return;
		}
		if ($this->prd['situacao'] != 2) { // produto está incompleto
			$this->log("Produto ".$this->prd['id']." incompleto ");
			$this->inactivateProduct(self::INATIVO, true);
			return; 
		} 
		if ($this->prd['qty'] == 0) {  // sem estoque
			$this->log("Produto ".$this->prd['id']." sem estoque ");
			$this->inactivateProduct(self::SEMESTOQUE, false);
			return;
		}
	
		if ($this->checkPriceMin($price)) { //preço menor que o minimo
			$minimo = $this->model_settings->getValueIfAtiveByName('preco_minimo');
			$this->log("Produto ".$this->prd['id']." com preço menor que o minimo");
			$this->inactivateProduct(self::INATIVO, false);
			$this->errorTransformation($this->prd['id'],$skumkt, "Produto com preço abaixo do valor minimo: ". $minimo, "Preparação para o envio");
			return;
		}

		if ($this->checkMaxPriceLock($price, $this->prd['id'])) { //preço menor que o minimo
			$value_max_price_lock = $this->model_settings->getValueIfAtiveByName('maximum_price_lock');
			$this->log("Produto ".$this->prd['id']." com alteração preço máximo maior que o máximo estipulado pelo seller Center: " . $value_max_price_lock . "%");
			$this->inactivateProduct(self::INATIVO, true);
			$this->errorTransformation($this->prd['id'],$skumkt, "Produto com alteração de preço maior que o percentual máximo estipulado. O limite é de ". $value_max_price_lock . "% para aumento preço.", "Preparação para o envio");
			return;
		}
		if ($this->checkMinPriceLock($price, $this->prd['id'])) { //preço menor que o minimo
			$value_min_price_lock = $this->model_settings->getValueIfAtiveByName('minimum_price_lock');
			$this->log("Produto ".$this->prd['id']." com preço minimo maior que o minimo");
			$this->inactivateProduct(self::INATIVO, true);
			$this->errorTransformation($this->prd['id'],$skumkt, "Produto com alteração de preço menor que o percentual mínimo estipulado. O limite é de ". $value_min_price_lock . "% para redução de preço.", "Preparação para o envio");
			return;
		}

		if (!$this->hasShipCompany()){ // sem transportadora 
			$this->log("Produto ".$this->prd['id']." sem transportadora ");
			$this->inactivateProduct(self::SEMTRANSPORTADORA, false);
			if ($this->messageHasShipCompany !== false) {
				$this->errorTransformation($this->prd['id'], $skumkt, $this->messageHasShipCompany, "Preparação para o envio");
			}
			return;
		}
		if ($this->store['active'] !== '1') {
			$this->log("A loja do produto ".$this->prd['id']." inativada ");
			$this->log(print_r($this->store,true));
			$this->inactivateProduct(self::INATIVO, false);
			return;
		}
		if ($this->prd_to_integration['approved'] !== '1'){
			if($this->prd_to_integration['approved'] == '2'){
			$this->log("Produto ".$this->prd['id']." foi reprovado para ".$this->int_to);
			$this->inactivateProduct(self::INATIVO, true);
			return;
			}
			$this->log("Produto ".$this->prd['id']." foi reprovado para ".$this->int_to);
			$this->inactivateProduct(self::EMANALISE, true);
			return;
		}
		if ($this->checkBlackList() == 0){
			$this->log("Produto ".$this->prd['id']." bloqueado ".$this->int_to);
			$this->inactivateProduct(self::INATIVO, true);
			return; 
		}
		if($this->store['is_vacation'] == 1){
			$this->log("A loja do produto ".$this->prd['id']." esta de férias");
			$this->inactivateProduct(self::INATIVO, true);
			return;
		}
		
		if (($this->prd_to_integration['status_int'] != self::MEIOCADASTRAMENTO) && ($this->prd_to_integration['status_int'] != self::EMCADASTRAMENTO)) { // se não está em cadastramento muda para em envio
			// $this->model_integrations->updatePrdToIntegration(array('status_int'=>self::FILAENVIO),$this->prd_to_integration['id']);
			$this->remove_product_queue = $this->updateProduct();
			return;
		}
		else { // se está em cadastramento então tento enviar novamente
			$this->remove_product_queue = $this->insertProduct();
			return;
		}
		

	}	
	
	protected function vtexHttp($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null, $cnt429 = 0 )
    {
        $this->accountName = $separateIntegrationData->accountName;

        $this->header = [
            'content-type: application/json',
            'accept: application/json',
            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
        ];
        if (isset($separateIntegrationData->suffixDns)) {
            if (!is_null($separateIntegrationData->suffixDns)) {
	            $this->setSuffixDns($separateIntegrationData->suffixDns);
	        }
        } 
		
        $url = 'https://'.$this->accountName.'.'.$separateIntegrationData->environment. $this->suffixDns .'/'.$endPoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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

		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' 	=> $int_to,
				'prd_id' 	=> $prd_id,
				'url' 		=> $url,
				'function' 	=> $function,
				'method' 	=> $method,
				'sent' 		=> $data,
				'response' 	=> $this->result,
				'httpcode' 	=> $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}
		
		if ($this->responseCode == 429) {
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 10 segundos.");
            sleep(10);
			if ($cnt429 > 3) {
				$this->log("3 requisições já enviadas httpcode=429.Desistindo e mantendo na fila.");
				die;
			}
			$cnt429++;
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data, $cnt429);
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
		}
        if ($this->responseCode == 503) {
		    $this->log("Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttp($separateIntegrationData, $endPoint, $method, $data);
		}
        return;
    }

	protected function vtexHttpUrl($separateIntegrationData, $url, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function = null, $cnt429=0)
    {
        $this->accountName = $separateIntegrationData->accountName;

        $this->header = [
            'content-type: application/json',
            'accept: application/json',
            "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
            "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
		    $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 10 segundos.");
            sleep(10);
			if ($cnt429 > 3) {
				$this->log("3 requisições já enviadas httpcode=429.Desistindo e mantendo na fila.");
				die;
			}
			$cnt429++;
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data, $prd_id, $int_to, $function, $cnt429);
		}
		if ($this->responseCode == 504) {
		    $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data, $prd_id, $int_to, $function, 0);
		}
        if ($this->responseCode == 503) {
		    $this->log("Vtex com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
			$this->vtexHttpUrl($separateIntegrationData, $url, $method, $data, $prd_id, $int_to, $function, 0);
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
    
	function getIntegration() 
	{
		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'],$this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto("0",$this->int_to);
			}
			else {
				$this->integration_main = $this->integration_store;
			} 
		}
	}
	
	public function getPrice($variant = null) 
	{
		$this->prd['price'] = round($this->prd['price'],2);
		// pego o preço por Marketplace 
		$old_price = $this->prd['price'];
		
		// pego o preço da variant 
		if (!is_null($variant)) {
			if ((float)trim($variant['price']) > 0) {
				$old_price = round($variant['price'],2);
				if ($old_price !== $this->prd['price']) {
					$this->log(" Produto ".$this->prd['id']." Variaçao ".$variant['variant']. " tem preço ".$old_price." na variação e preço normal ".$this->prd['price']);
				}
			}
		}
		
		// altero o preço para acertar o DE POR do marketplace. 
		$old_price  =  $this->model_products_marketplace->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $this->prd['has_variants']);
		if ($old_price !== $this->prd['price']) {
			$this->log(" Produto ".$this->prd['id']." tem preço ".$old_price." para ".$this->int_to." e preço normal ".$this->prd['price']);
		}

		// Pego o preço a ser praticado se tem promotion
        //braun2
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            $price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to, $variant);
        }
        else
        {
            $price = $this->model_promotions->getPriceProduct($this->prd['id'],$old_price,$this->int_to);
        }

		if ($old_price !== $price) {
			$this->log(' Produto '.$this->prd['id'].' tem preço promoção '.$price.' para '.$this->int_to.' e preço base '.$old_price);
		}
		
		return round($price,2);
	}
	
	public function checkPriceMin($price) {
		$min_price = $this->model_settings->getValueIfAtiveByName('preco_minimo');
		if ($min_price) {
			$min_price = round(floatval($min_price), 2);
			return round(floatval($price), 2) < $min_price;
		}
		return false;
	}

	public function errorTransformation($prd_id, $sku, $msg, $step, $prd_to_integration_id = null, $mkt_code = null, $variant = null)
	{
		$this->model_errors_transformation->removeByProductId($prd_id,$this->int_to, $variant);
		$trans_err = array(
			'prd_id' 		=> $prd_id,
			'skumkt'		=> $sku,
			'int_to' 		=> $this->int_to,
			'step' 			=> $step,
			'message' 		=> $msg,
			'status' 		=> 0,
			'date_create' 	=> date('Y-m-d H:i:s'), 
			'reset_jason' 	=> '', 
			'mkt_code' 		=> $mkt_code,
			'variant'		=> $variant
		);
		$this->log("Produto ".$prd_id." skubling ".$sku." int_to ".$this->int_to." ERRO: ".$msg); 
		$insert = $this->model_errors_transformation->create($trans_err);
		
		if (!is_null($prd_to_integration_id)) {
			$this->model_integrations->updatePrdToIntegration(array('date_last_int' => $this->dateLastInt), $prd_to_integration_id);
		}
	}
	
	public function log($msg)
	{
		/*
		$dir = FCPATH.'application/logs/queue';
        if (!file_exists($dir)) @mkdir($dir);
        $logfile = $dir.'/products_queue_'.$this->int_to.'_'.$this->prd['id'].'_'.date('YmdHis').'.log';
	
		$logfile = $dir.'/products_queue_'.$this->int_to.'_'.$this->prd['id'].'.log';	
		
		$myfile = fopen($logfile, "a") or die("Unable to open file!");
		fwrite($myfile, $msg."\n");
		fclose($myfile);
		*/
		
		echo $msg."\n";
	}
	
	public function getCategoryMarketplace($skumkt, $int_to = '', $mandatory_category = true)
	{
		if 	($int_to == '') {$int_to=$this->int_to; }
			
		$categoryId = json_decode($this->prd['category_id']);
		if (is_array($categoryId)) {
			$categoryId = $categoryId[0];
		}

		if (trim($categoryId) == '') {
			$msg= 'Produto sem categoria.';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
			if ($this->prd['situacao'] != 1) { // volta produto para incompleto
				$this->model_products->update(array('situacao' => 1),$this->prd['id']); 
			}
			return false;
		}
   		$category   = $this->model_category->getCategoryData($categoryId);
		if (!$category) {
			$msg= 'Produto sem categoria.';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
			if ($this->prd['situacao'] != 1) { // volta produto para incompleto
				$this->model_products->update(array('situacao' => 1),$this->prd['id']); 
			}
			return false;
		}
		if (!isset($category['name'])) {
			$msg= 'Não consegui ler a categoria de id '.$categoryId;
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
			return false;
		}
		// pego o tipo volume da categoria 
		$tipo_volume   = $this->model_category->getTiposVolumesByCategoryId($categoryId);
		$this->prd['tipovolumecodigo'] = isset($tipo_volume) ? $tipo_volume['codigo'] : '';
		$this->prd['categoryname'] = $category['name']; 
		
		if ($mandatory_category) { // se é mandatóra a categoria no Marketplace, pego o ID de lá
			// pego a categoria do marketplace
			$result= $this->model_categorias_marketplaces->getCategoryMktplace($int_to,$categoryId);
			if (!$result) {
				$msg= 'Categoria '.$categoryId.' não vinculada ao marketplace '.$int_to;
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$skumkt,$msg,"Preparação para o envio");
				return false;
			}
			return $result['category_marketplace_id'];
		}
   		return  $category['id']; 
	}
	
	public function getBrandMarketplace($skumkt, $brandRequired = false)
	{
		// pego o brand do Marketplace 
		$brandId    = json_decode($this->prd['brand_id']);
		if ($brandId=='') {
			if ($brandRequired) {
				$msg= 'Produto sem Marca e é obrigatório para '.$this->int_to;
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
				$this->prd['brandname'] = "";
				return false;
			}
			else {
				$this->prd['brandname'] = "";
				return true;
			}
			
		}
    	$brand      = $this->model_brands->getBrandData($brandId);
		if (!isset($brand['name'])) {
			$msg= 'Marca não encontrada ou inativa';
			echo 'Produto '.$this->prd['id']." ".$msg."\n";
			$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
			return false;
		}
		$this->prd['brandname'] = $brand['name'];
		
		$brandmkt = $this->model_brands_marketplaces->getBrandMktplaceByName($this->int_to,$brand['name']);
		if (!$brandmkt) {
			if ($brandRequired) {
				$msg= 'Marca '.$brand['name'].' não vinculada ao marketplace '.$this->int_to;
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
				return false;
			}
			else {
				return true;
			}
		}
		if (!$brandmkt['isActive']) {
			if ($brandRequired) {
				$msg= 'Marca '.$brand['name'].' inativada no marketplace '.$this->int_to;
				echo 'Produto '.$this->prd['id']." ".$msg."\n";
				$this->errorTransformation($this->prd['id'],$skumkt,$msg, "Preparação para o envio");
				return false;
			}
			else {
				return true;
			}
		}
		
		return $brandmkt['id_marketplace'];
	} 
	
	function percEstoque() {
		
		$percEstoque = $this->model_settings->getValueIfAtiveByName(strtolower($this->int_to).'_perc_estoque');
		if ($percEstoque === false) {
			return 100;
		} else {			
			return $percEstoque;
		}
	}
	
	public function checkMaxPriceLock($price, $prd_id = null): bool
    {
		$value_price_lock = $this->model_settings->getValueIfAtiveByName('maximum_price_lock');

        if (!$value_price_lock) {
            return false;
        }

        // Não tem variações, faz a validações com base no preço do produto.
        if (!count($this->variants)) {
            $last_price = $this->getLastPost($prd_id, $this->int_to);
            // Não encontrou o registro. Pode ser um novo produto.
            if (empty($last_price)) {
                return false;
            }
            $last_price = (float)$last_price['price'];

            return $price > ($last_price + (($value_price_lock / 100) * $last_price));
        }

        // Tem variações, faz a validações com base no preço de todas as variações.
        foreach ($this->variants as $variant) {
            $price      = (float)$variant['price'];
            $last_price = $this->getLastPost($prd_id, $this->int_to, $variant['variant']);
            // Não encontrou o registro. Pode ser um novo produto.
            if (empty($last_price)) {
                continue;
            }

            $last_price = (float)$last_price['price'];

            if ($price > ($last_price + (($value_price_lock / 100) * $last_price))) {
                return true;
            }
        }

        return false;
	}

	public function checkMinPriceLock($price, $prd_id = null): bool
    {
		$value_price_lock = $this->model_settings->getValueIfAtiveByName('minimum_price_lock');

        if (!$value_price_lock) {
            return false;
        }

        // Não tem variações, faz a validações com base no preço do produto.
        if (!count($this->variants)) {
            $last_price = $this->getLastPost($prd_id, $this->int_to);
            // Não encontrou o registro. Pode ser um novo produto.
            if (empty($last_price)) {
                return false;
            }
            $last_price = (float)$last_price['price'];

            return $price < ($last_price - (($value_price_lock / 100) * $last_price));
        }

        // Tem variações, faz a validações com base no preço de todas as variações.
        foreach ($this->variants as $variant) {
            $price      = (float)$variant['price'];
            $last_price = $this->getLastPost($prd_id, $this->int_to, $variant['variant']);
            // Não encontrou o registro. Pode ser um novo produto.
            if (empty($last_price)) {
                continue;
            }

            $last_price = (float)$last_price['price'];

            if ($price < ($last_price - (($value_price_lock / 100) * $last_price))) {
                return true;
            }
        }

        return false;
	}

	public abstract function getLastPost(int $prd_id, string $int_to, int $variant = null);

    public function formatFieldsUltEnvio(array $data): array
    {
        if (!$this->update_price_product) {
            $price      = null;
            $list_price = null;

            if (array_key_exists('prd_id', $data) && array_key_exists('int_to', $data)) {
                if (!isset($data['variant']) || $data['variant'] == '') {
                    $data_last_post = $this->getLastPost($data['prd_id'], $data['int_to']);
                } else {
                    $data_last_post = $this->getLastPost($data['prd_id'], $data['int_to'], $data['variant']);
                }

                if ($data_last_post) {
                    $price = $data_last_post['price'];
                    $list_price = $data_last_post['list_price'];
                } else {
                    $price = $data['price'];
                    $list_price = $data['list_price'];
                }

                // Produto está no lixo.
                if (likeTextNew('DEL_%', $data['sku'])) {
                    // Sku já existe
                    if (!empty($data_last_post)) {
                        if (likeTextNew("DEL_$data_last_post[sku]%", $data['sku'])) {
                            // alterar o nome do skumkt
                            if (!empty($data_last_post['skumkt']) && !likeTextNew('DEL_%', $data_last_post['skumkt'])) {
                                $data['skumkt'] = "DEL_$data_last_post[skumkt]";
                            }
                            // alterar o nome do sku_local
                            if (isset($data['skulocal']) && !empty($data_last_post['skulocal']) && !likeTextNew('DEL_%', $data_last_post['skulocal'])) {
                                $data['skulocal'] = "DEL_$data_last_post[skulocal]";
                            }
                        } else {
                            $data['skumkt']   = $data_last_post['skumkt'];
                            if (isset($data['skulocal'])) {
                                $data['skulocal'] = $data_last_post['skulocal'];
                            }
                        }
                    } else {
                        // produto ainda não existe e alterar o código do skumkt para não ser cotado.
                        if (!likeTextNew('DEL_%', $data['skumkt'])) {
                            $data['skumkt'] = "DEL_$data[skumkt]";
                        }
                        if (isset($data['skulocal']) && !likeTextNew('DEL_%', $data['skulocal'])) {
                            $data['skulocal'] = "DEL_$data[skulocal]";
                        }
                    }
                }
            }

            if (is_null($price)) {
                unset($data['price']);
            } else {
                $data['price'] = $price;
            }

            if (is_null($list_price)) {
                unset($data['list_price']);
            } else {
                $data['list_price'] = $list_price;
            }
        }

        return $data;
    }


    public function disableProductVariant(?array $prd_to_integration, array $variant = null) {
        // Produto ainda não existe no marketplace
        if (is_null($prd_to_integration) || is_null($prd_to_integration['skumkt'])) {
            if (!is_null($variant)) {
                $this->log("Variação $variant[variant] do produto {$this->prd['id']} nunca integrado para $this->int_to e está inativo.");
                $this->model_integrations->updatePrdToIntegration(array('status_int' => self::INATIVO, 'date_last_int' => $this->dateLastInt), $this->prd_to_integration['id']);
            } else {
                $this->log("Produto {$this->prd['id']} nunca integrado para $this->int_to e está inativo.");
                $this->model_integrations->updatePrdToIntegration(array('status_int' => self::INATIVO, 'date_last_int' => $this->dateLastInt), $this->prd_to_integration['id']);
            }
            return;
        }

        if (!is_null($variant)) {
            $this->log("Variação $variant[variant] do produto {$this->prd['id']} inativo.");
            $this->inactivateProduct(self::INATIVO, true, $variant);
			$this->update_price_product = true; 
            return;
        }

        $this->log("Produto {$this->prd['id']} inativo.");
        $this->inactivateProduct(self::INATIVO, true);
    }

	private function checkMultifullfillment($prd, $store)
	{		
	    if (($store['type_store'] !=2) || ($store['active'] !=1)) {
			return false; 
		}
				
		$stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;
		if (!$stores_multi_cd) {
			return false; 
		}

		echo "Produto de uma loja CD de Multi-CD \n";
		$multi_channel = $this->model_stores_multi_channel_fulfillment->getRangeZipcode($store['id'], $store['company_id'], 1);

		if (!$multi_channel){
			echo "Não consegui encontrar a loja Original para esse produto \n";
			return true; 
		}
		$original_store_id = $multi_channel[0]['store_id_principal']; 
		if ($prd['has_variants'] == '') {
			$prd_original = $this->model_products->getProductComplete($prd['sku'], $store['company_id'], $original_store_id);
			if ($prd_original) {
				echo "Colocando o produto ". $prd_original['id']. " da loja Principal na fila \n";
				$queue = array(
					'status' => 0,
					'prd_id' => $prd_original['id'],
					'int_to' => null
				);
				$this->model_queue_products_marketplace->create($queue);
				return true;
			}
			echo "ATENÇÂO: Não existe produto original com SKU ".$prd['sku']." na loja ".$original_store_id."\n";			
		}
		else{
			$variants  = (isset ($this->variants)) ? $this->variants : $this->model_products->getVariants($prd['id']);
			
			foreach ($variants as $variant) {
				$variant_original = $this->model_products->getVariantsBySkuAndStore($variant['sku'], $original_store_id);
				if ($variant_original) {
					echo "Colocando o produto ". $variant_original['prd_id'] . " da loja Principal na fila \n";
					$queue = array(
						'status' => 0,
						'prd_id' => $variant_original['prd_id'],
						'int_to' => null
					);
					$this->model_queue_products_marketplace->create($queue);
					return false;
				}
			}
			echo "ATENÇÂO: Não existe produto original, nem variações para os SKUs ".$prd['sku']." na loja ".$original_store_id."\n";			
		}
		return true;
	}

}
