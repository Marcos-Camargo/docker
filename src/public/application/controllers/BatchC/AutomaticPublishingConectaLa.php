<?php
/*
Marca todos os produtos de todos os lojista para participarem do Leilão
 
Executa uma vez por dia
*/   
 class AutomaticPublishingConectaLa extends BatchBackground_Controller {
	
	var $companySika;
	var $catalogSika;
	var $storeSika; 
	
	public function __construct()
	{
		parent::__construct();

		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
		
		// carrega os modulos necessários para o Job
		$this->load->library('BlacklistOfWords');
		$this->load->model('model_integrations');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_catalogs');
        $this->load->model('model_whitelist');
		$this->load->model('model_blacklist_words');
		$this->load->model('model_errors_transformation');
    }
	// php index.php BatchC/AutomaticPublishingConectaLa run null B2W
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

	 	$this->getDataSika(); // SIKA é especial e so os produtos da loja principal são envidaos
		$this->sendMarketplace($params);
		$this->criaProductsMarketplace($params);
		$this->acertaTipoVolumeBling($params); // verifica se mudaram as categorias dos produtos e acerta no bling_ult_envio
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function criaProductsMarketplace($mkt = null) 
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Criando products marketplace\n";
		
		$integrations = $this->model_integrations->getIntegrationsbyType('BLING');
		foreach ($integrations as $integration) {
			
			if (!is_null($mkt)) {
				if ($integration['int_to'] != $mkt) {
				 	continue;  // pulo marketplaces que não me interessam
				}
			}
			$products = $this->model_products->getProductsByStore($integration['store_id']);
			echo 'Produtos da Loja '.$integration['store_id'].' para '.$integration['name']."\n";
			foreach ($products as $product) {
				
				// adiciono no products_marketplace se não existir 
				$hub = ($integration['int_type'] == 'DIRECT');
			    $this->model_products_marketplace->createIfNotExist($integration['int_to'],$product['id'],$hub);
			}
		}
		
	}
	
	function sendMarketplace($mkt = null)
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Marcando todos os produtos para envio para todos os marketplaces da Conectala\n";
		$this->load->library('calculoFrete');
		
		$integrations = $this->model_integrations->getIntegrationsbyType('BLING');
		foreach ($integrations as $integration) {

			if (!is_null($mkt)) {
				if ($integration['int_to'] != $mkt) {
				 	continue;  // pulo marketplaces que não me interessam
				}
			}
			$products = $this->model_products->getProductsByStore($integration['store_id']);
			echo 'Produtos da Loja '.$integration['store_id'].' para '.$integration['name']."\n";
			$store = $this->model_stores->getStoresData($integration['store_id']);
			
			if ($store['company_id'] == $this->companySika['id']) { // se for empresa sika
				if ($store['id'] !== $this->storeSika['id']) { // mas não é a loja principal, pula 
					continue;
				} 
			}
			
			foreach ($products as $product) {
				$prd_to = $this->model_integrations->getPrdIntegrationByFields($integration['id'],$product['id'],$product['store_id']);

				if ($store['active'] != 1) {
					if ($prd_to['status'] == 0) {
						echo 'Já removido para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois a loja não está ativa\n"; 
					}
					else {
						echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois a loja não está ativa\n"; 
						$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
					}
		
					continue;
				}
				if (($product['status'] != 1) || ($product['situacao'] != '2')) {
					if ($prd_to['status'] == 0) {
						echo 'Já removido para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " produto inativo ou incompleto\n"; 
					}
					else {
						echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " produto inativo ou incompleto\n"; 
						$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
					}
					continue;
				}
				
				$cat_id = json_decode ( $product['category_id']);
				$sql = "SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
						 WHERE id =".intval($cat_id[0]).")";
				$cmd = $this->db->query($sql);
				$lido = $cmd->row_array();
				$tipo_volume_codigo= $lido['codigo'];		
					
				$prd_info = array (
					'peso_bruto' 			=> (float)$product['peso_bruto'],
					'largura' 				=> (float)$product['largura'],
					'altura' 				=> (float)$product['altura'],
					'profundidade'			=> (float)$product['profundidade'],
					'tipo_volume_codigo' 	=> $tipo_volume_codigo,
					'store_id' 				=> $store['id'],
				);
				
				$has_ship_company = true;
				try {
					$this->calculofrete->productCanBePublished($product['store_id']);
				} catch (Exception $exception) {
					$has_ship_company = $exception->getMessage();
				}
				
				$integras = $this->model_integrations->getPrdIntegrationByFieldsMulti($integration['id'], $product['id'],$product['store_id']);
				// inclui se tem freight_seller; 
				if ($has_ship_company === true) {
					// produto ok, posso publicar
					if (count($integras) == 0) { // nunca integrou então crio a integração 
						$this->markPrdToIntegration($mkt, $product, $integration, null );
					}
					else {
						foreach ($integras as $integra) {
							// verifico se está com o status_int=20 que significa que ainda está em cadastramento pelo Marketplace
							if (($integra['status_int'] == 20) || ($integra['status_int'] == 21) || ($integra['status_int'] == 22) || ($integra['status_int'] == 23) ){
								echo "Produto ".$product['id']." ainda em cadastramento no marketplace".$integra['int_to'].". Não será marcado para envio\n";
								if ($integra['status'] == 0) {
									$integra['status'] = 1; 
									$this->model_integrations->updatePrdToIntegration($integra,$integra['id']);
								}
								continue ;
							}
							// rick 060122 $this->markPrdToIntegration($mkt, $product, $integration, $integra);
							$this->markPrdToIntegration($mkt, $product, $integration, $integra);
						}
					}
				}
				else  {
					if ($prd_to['status'] == 0) {
						echo 'Já removido para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois não tem nem correios nem transportadora\n"; 
					}
					else {
						// não posso publicar
						echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois não tem nem correios nem transportadora\n"; 
					}
					if (count($integras) > 0) { // nunca integrou então crio a integração 
						$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1, 91); // sem transportadora
					}

					$this->errorTransformation($product['id'], $integration['int_to'], $has_ship_company);
				}
 			}
		}
	}

	public function errorTransformation($prd_id, $int_to, $msg)
	{
		$this->model_errors_transformation->setStatusResolvedByProductId($prd_id, $int_to);
		$trans_err = array(
			'prd_id' 		=> $prd_id,
			'skumkt'		=> 'NO_SKUMKT',
			'int_to' 		=> $int_to,
			'step' 			=> 'Preparação para o envio',
			'message' 		=> $msg,
			'status' 		=> 0,
			'date_create' 	=> date('Y-m-d H:i:s'), 
			'reset_jason' 	=> ''
		);
		
		$this->model_errors_transformation->create($trans_err);
	}

	function markPrdToIntegration($mkt, $product, $integration, $integra) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		/* retirado o check de blacklist / whitelist daqui e passado para o ProductsConectala */ 
		$prd = Array(
			'int_id' 		=> $integration['id'],
			'prd_id' 		=> $product['id'],
			'company_id' 	=> $product['company_id'],
			'store_id'		=> $product['store_id'],
			'date_last_int' => '',
			'status' 		=> 1,
			'status_int' 	=> 0,
			'rule' 			=> null,
			'int_type' 		=> 13,        // Loja Conecta Lá
			'int_to' 		=> $integration['int_to'] , 
			'skubling' 		=> null,
			'skumkt' 		=> null,
			'approved' 		=> ($integration['auto_approve']) ? 1 : 3,
		);

		$msgvar ='';
		if (!is_null($integra['variant'])) {
			$msgvar =' variant: '.$integra['variant'].' ';
		}
		
		if (!is_null($integra)) {
			$prd['date_last_int'] 	= $integra['date_last_int'];
			$prd['status_int'] 		= $integra['status_int'];
			$prd['skubling'] 		= $integra['skubling'];
			$prd['skumkt'] 			= $integra['skumkt'];
			$prd['approved'] 		= $integra['approved'];	
			$prd['status'] 			= (is_null($integra['rule']) || ($integra['rule']==0) ) ? 1 :	$integra['status'];
			$prd['rule'] 			= $integra['rule'];	
			$this->model_integrations->updatePrdToIntegration($prd,$integra['id']);
			$alterado = array_diff($prd, $integra);
			
			if (empty($alterado)) {
				echo 'Sem alteração para '. $integration['name'].' produto '.$product['id'].$msgvar.' loja '.$product['store_id']. "\n";
			}
			else {
				echo 'Alterado para '. $integration['name'].' produto '.$product['id'].$msgvar.' loja '.$product['store_id']. "\n";
			}
		}
		else {
			$this->model_integrations->setProductToMkt($prd);
			echo 'Adicionando para '. $integration['name'].' produto '.$product['id'].$msgvar.' loja '.$product['store_id']. "\n";
		}
	}
	
	function acertaTipoVolumeBling($int_to) {
		
		$sql = 'SELECT * FROM bling_ult_envio WHERE int_to = ?';
		$query = $this->db->query($sql,array($int_to));
		$blings = $query->result_array();
		echo "Procurando alterações no Tipo_volume\n";
		foreach($blings as $bling) {
			$sql = "SELECT * FROM products WHERE id = ".$bling['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			
			$cat_id = json_decode ( $prd['category_id']);
			$sql = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id =".intval($cat_id[0]).")";
			$cmd = $this->db->query($sql);
			$tipo_volume_codigo = $cmd->row_array();
			
			if ($tipo_volume_codigo['codigo'] !=$bling['tipo_volume_codigo']) {
				echo "Fazendo o update $bling[id] de $bling[tipo_volume_codigo] para $tipo_volume_codigo[codigo] \n";
				$sql = "UPDATE bling_ult_envio SET tipo_volume_codigo = ? WHERE id = ? ";		
				$this->db->query($sql,array($tipo_volume_codigo['codigo'],$bling['id']));
			}
		}
		echo "Encerrado\n";
	}
	
	function getDataSika()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		// pego a empresa
		$comp = $this->model_company->getCompaniesByName('SIKA');
		if (count($comp) != 1) {
			$erro = 'Não existe nenhuma empresa chamada '.'SIKA'.' ou tem mais de uma. Garanta que só tenha uma empresa chamada SIKA.';
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			$this->companySika = array('id'=>'-100'); // retorna um id inválido para que o processo continue. 
			return;
		}
		$this->companySika =  $comp[0];
		
		//pego a loja 
		$stores = $this->model_stores->getMyCompanyStores($this->companySika['id']);
		foreach($stores as $store) {
			if ($store['CNPJ'] == $this->companySika['CNPJ']) {
				$this->storeSika = $store;
				break;
			}
		}
		if (is_null($this->storeSika)) {
			$this->storeSika =array('id'=>'-100');
			$erro = 'Não encontrei nenhuma loja com o mesmo CNPJ da empresa '.$this->companySika['name'].' garanta que a loja franqueadora master tenha o CNPJ'.$this->companySika['CNPJ']."\n";
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
		
		// pego o catálogo
		$catalog = $this->model_catalogs->getCatalogByName('Catálogo Sika');
		if (!$catalog) {
			$erro = 'Não existe nenhum catálogo chamado '.'Catálogo Sika'.'. Crie o catálogo no sistema antes.';
			echo $erro."\n";
			$this->log_data('batch',$log_name, $erro ,"E");
			return;
		}
		$this->catalogSika = $catalog; 
		
	}

}
?>