<?php
/*
Marca todos os produtos de todos os lojista para participarem do Leilão
 
Executa uma vez por dia
*/   
 class BlingMarcaTodosEnvio extends BatchBackground_Controller {
	
	var $companySika;
	var $catalogSika;
	var $storeSika; 
	
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
		$this->load->library('BlacklistOfWords');
		$this->load->model('model_integrations');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_catalogs');
        $this->load->model('model_whitelist');
        $this->load->model('model_blacklist_words');
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
		// $retorno = $this->apagaErro();
		// $retorno = $this->acertaPrdtoIntegration();
		//$retorno = $this->acertaProdutosMudaramLoja();
		
		$retorno = $this->getDataSika(); // SIKA é especial e so os produtos da loja principal são envidaos
		$retorno = $this->sendMarketplace($params);
		$retorno = $this->criaProductsMarketplace($params);
		$retorno = $this->acertaTipoVolumeBling($params); // verifica se mudaram as categorias dos produtos e acerta no bling_ult_envio
		
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
				if (($integration['int_to'] != $mkt)) {
					//echo "pulando\n";
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
				if (($integration['int_to'] != $mkt)) {
					//echo "pulando\n";
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
					'peso_bruto' =>(float)$product['peso_bruto'],
					'largura' =>(float)$product['largura'],
					'altura' =>(float)$product['altura'],
					'profundidade' =>(float)$product['profundidade'],
					'tipo_volume_codigo' => $tipo_volume_codigo,
					'store_id' => $store['id'],
				);
				
				// inclui se tem freight_seller; 
				if (($store['freight_seller']) || $this->calculofrete->verificaCorreios($prd_info) ||
					$this->calculofrete->verificaTipoVolume($prd_info,$store['addr_uf'],$store['addr_uf']) ||
					$this->calculofrete->verificaPorPeso($prd_info,$store['addr_uf'])) {
					// produto ok, posso publicar
					
					
					$integras = $this->model_integrations->getPrdIntegrationByFieldsMulti($integration['id'], $product['id'],$product['store_id']);
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
						$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1, 91); // sem transportadora
					}
				}
 			}
		}
	}


	function markPrdToIntegration($mkt, $product, $integration, $integra) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		/* retirado o check de blacklist / whitelist daqui e passado para o ProductsConectala */ 
		$prd = Array(
			'int_id' => $integration['id'],
			'prd_id' => $product['id'],
			'company_id' => $product['company_id'],
			'store_id' => $product['store_id'],
			'date_last_int' => '',
			'status' => 1,
			'status_int' => 0,
			'rule' => null,
			'int_type' => 13,        // Loja Conecta Lá
			'int_to' => $integration['int_to'] , 
			'skubling' => null,
			'skumkt' => null,
			'approved' => ($integration['auto_approve']) ? 1 : 3,
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
			$prd['status'] 			= $integra['status'];	
			$prd['rule'] 			= $integra['rule'];	
			$this->model_integrations->updatePrdToIntegration($prd,$integra['id']);
			echo 'Alterado para '. $integration['name'].' produto '.$product['id'].$msgvar.' loja '.$product['store_id']. "\n";
		}
		else {
			$this->model_integrations->setProductToMkt($prd);
			echo 'Adicionando para '. $integration['name'].' produto '.$product['id'].$msgvar.' loja '.$product['store_id']. "\n";
		}
		return 
		// daqui para baixo foi removido......
		
		// novo array de produto com chave marketplace nos produtos para consultar black/white list
		$productCheckBlackWhiteList = array_merge($product, ['marketplace' => $mkt]);
		// consultar white/black list
        $whiteList = $this->model_whitelist->searchWhitelist($this->blacklistofwords->getProductForCheck($productCheckBlackWhiteList));
        $blackList = $this->model_blacklist_words->getDataBlackListActive($this->blacklistofwords->getProductForCheck($productCheckBlackWhiteList));

        // consultar se produto deve ser bloqueado
        if ($blackList) {
            $hasLockByMarketplace = $this->blacklistofwords->getBlockProduct($product, $product['id'], $whiteList, $blackList, true);
        } else $hasLockByMarketplace['blocked'] = false;

        echo 'hasLockByMarketplace = ' . json_encode($hasLockByMarketplace) . "\n";

        $statusBlockPrdInt = $hasLockByMarketplace['blocked'] ? 0 : 1;
        if ($hasLockByMarketplace['blocked']) {
            $ruleBlockPrdInt = array();
            if (!isset($hasLockByMarketplace['data_row'])) $hasLockByMarketplace['data_row'] = [];
            if (!is_array($hasLockByMarketplace['data_row'])) $hasLockByMarketplace['data_row'] = (array)$hasLockByMarketplace['data_row'];
            foreach ($hasLockByMarketplace['data_row'] as $rulesBlock)
                array_push($ruleBlockPrdInt, $rulesBlock['blacklist_id']);

            $ruleBlockPrdInt = json_encode($ruleBlockPrdInt);
        } else $ruleBlockPrdInt = null;

        echo 'ruleBlockPrdInt = ' . json_encode($ruleBlockPrdInt) . "\n";
		
		$prd = Array(
			'int_id' => $integration['id'],
			'prd_id' => $product['id'],
			'company_id' => $product['company_id'],
			'store_id' => $product['store_id'],
			'date_last_int' => '',
			'status' => $statusBlockPrdInt,
			'status_int' => 0,
			'rule' => $ruleBlockPrdInt,
			'int_type' => 13,        // Loja Conecta Lá
			'int_to' => $integration['int_to'] , 
			'skubling' => null,
			'skumkt' => null,
			'approved' => ($integration['auto_approve']) ? 1 : 3,
		);
		
		if (!is_null($integra)) {
			$prd['date_last_int'] 	= $integra['date_last_int'];
			$prd['status_int'] 		= $integra['status_int'];
			$prd['skubling'] 		= $integra['skubling'];
			$prd['skumkt'] 			= $integra['skumkt'];
			$prd['approved'] 		= $integra['approved'];	
		}
		

		// adiciono produto ativo na fila para a integração
        if ($ruleBlockPrdInt)
            echo 'Bloqueando para '. $integration['name'].' produto '.$product['id'].$msgvar.' loja '.$product['store_id']. " Regra(s): {$ruleBlockPrdInt}\n";
        else
		    echo 'Adicionando para '. $integration['name'].' produto '.$product['id'].$msgvar.' loja '.$product['store_id']. "\n";

		// var_dump($prd);
		if (!is_null($integra)) { // se já tem integração só faço update 
			$this->model_integrations->updatePrdToIntegration($prd,$integra['id']);
		} else{
			$this->model_integrations->setProductToMkt($prd);
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
				//echo "Fazendo o update $bling[id] de $bling[tipo_volume_codigo] para $tipo_volume_codigo[codigo] \n";
				$query = $this->db->query($sql,array($tipo_volume_codigo['codigo'],$bling['id']));
			}
		}
		echo "Encerrado\n";
	}
	
	function sendMarketplaceold()
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Marcando todos os produtos para envio para todos os marketplaces da Conectala\n";

		
		$integrations = $this->model_integrations->getIntegrationsbyType('BLING');
		foreach ($integrations as $integration) {
			$products = $this->model_products->getProductsByStore($integration['store_id']);
			foreach ($products as $product) {
				if (($product['status'] == 1) && ($product['situacao'] != '1')) {
					$peso_cubico = ceil($product['altura'] * $product['largura'] *  $product['profundidade'] /6000);	
					// Verifico se é um produtos dos correios. Se não for,  vejo como está o cadastro no frete rápido
					if (((float)$product['peso_bruto'] > 30) || ((float)$peso_cubico > 30) || 
							((int)$product['largura'] < 1 ) || ((int)$product['largura'] > 105 ) ||
							((int)$product['altura'] < 1 ) || ((int)$product['altura'] > 105 ) ||
							((int)$product['profundidade'] < 1 ) || ((int)$product['profundidade'] > 105 ) ||
							((int)$product['largura']+(int)$product['altura']+(int)$product['profundidade'] < 3 ) ||
							((int)$product['largura']+(int)$product['altura']+(int)$product['profundidade'] > 200 )) {
					
						// Verifico se o FreteRápido já cadastrou a categoria do produto
						$cat_id = json_decode ( $product['category_id']);
						$sql = "SELECT id FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
								 WHERE id =".intval($cat_id[0]).")";
						$cmd = $this->db->query($sql);
						$lido = $cmd->row_array();
						$tipo_volume_id= $lido['id'];
						
						if (is_null($tipo_volume_id)) {
							echo 'Sem tipo volume Produto='.$product['id'].' Categoria='.$cat_id[0].' loja='.$product['store_id']." nao encontrado em stores_tiposvolumes\n";
							$this->log_data('batch',$log_name,'Sem tipo voluume Produto='.$product['id'].' Categoria='.$cat_id[0].' loja='.$product['store_id']." nao encontrado em stores_tiposvolumes","E");
							// removo produto sem Tipo Volume
							$prdData = Array(
								'status' => 0,
								'user_id' => 1  // Administrador que retirou 
							);
							//$this->model_integrations->unsetProductToMkt( $integration['id'],$product['id'],$product['company_id'],$prdData);
							$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
							continue;	
						}
						$sql = "SELECT status FROM stores_tiposvolumes WHERE store_id=".$product['store_id']." AND tipos_volumes_id = ".$tipo_volume_id;
						$cmd = $this->db->query($sql);
						$lido = $cmd->row_array();
						if (!$lido) {
							// Não deveria acontecer 
							echo 'Produto='.$product['id'].' Categoria='.$cat_id[0].' tipo_volue='.$tipo_volume_id." loja=".$product['store_id']." nao encontrado em stores_tiposvolumes\n";
							$this->log_data('batch',$log_name,'Produto='.$product['id'].' Categoria='.$cat_id[0].' tipo_volue='.$tipo_volume_id." loja=".$product['store_id']." nao encontrado em stores_tiposvolumes","E");
							// removo produto que não acho tipo_voluem
							$prdData = Array(
								'status' => 0,
								'user_id' => 1  // Administrador que retirou 
							);
							//$this->model_integrations->unsetProductToMkt( $integration['id'],$product['id'],$product['company_id'],$prdData);
							$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
							continue;
						}		 
						if (($lido['status'] != 2) && ($lido['status'] != 4)) { // 2 significa que foi cadastrado no frete rápido o correios, 4 outra transportadora
							echo "Ignorando o produto ".$product['id']." pois seu tipo_volume da loja ".$product['store_id']." não foi cadastrado no Frete Rápido\n";
							// removo produto não cadastrado da fila para a integração
							$prdData = Array(
								'status' => 0,
								'user_id' => 1  // Administrador que retirou 
							);
							//$this->model_integrations->unsetProductToMkt( $integration['id'],$product['id'],$product['company_id'],$prdData);
							$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
							continue;
						}
						/* Correios não vao mais pela Frete Rápido
						if ($lido['status'] == 2) { // se é correio, vejo se está dentro dos limite
							$peso_cubico = ceil($product['altura'] * $product['largura'] *  $product['profundidade'] /6000);	
							if (((float)$product['peso_bruto'] > 30) || ((float)$peso_cubico > 30) || 
								((int)$product['largura'] < 1 ) || ((int)$product['largura'] > 105 ) ||
								((int)$product['altura'] < 1 ) || ((int)$product['altura'] > 105 ) ||
								((int)$product['profundidade'] < 1 ) || ((int)$product['profundidade'] > 105 ) ||
								((int)$product['largura']+(int)$product['altura']+(int)$product['profundidade'] < 3 ) ||
								((int)$product['largura']+(int)$product['altura']+(int)$product['profundidade'] > 200 )) {
								echo "Ignorando o produto ".$product['id']." pois suas dimensões não passam no correio e ainda não tem transportadora que faça\n";
								// removo produto não cadastrado da fila para a integração
								echo 'peso ='.$product['peso_bruto']."\n";
								echo 'largura ='.$product['largura']."\n";
								echo 'altura ='.$product['altura']."\n";
								echo 'profundidade ='.$product['profundidade']."\n";
								$prdData = Array(
									'status' => 0,
									'user_id' => 1  // Administrador que retirou 
								);
								//$this->model_integrations->unsetProductToMkt( $integration['id'],$product['id'],$product['company_id'],$prdData);
								$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
								continue;	
							}
						}
						 *
						 */
					}
					$skubling = null;
					$skumkt = null;
					$date_last_int = '';
					// verifico se está com o status_int=20 que significa que ainda está em cadastramento pelo Marketplace
					$integra = $this->model_integrations->getPrdIntegrationByFields($integration['id'], $product['id'],$product['store_id']);
					if ($integra) {
						$skubling = $integra['skubling'];
						$skumkt = $integra['skumkt'];
						$date_last_int = $integra['date_last_int'];
						if (($integra['status_int'] == 20) || ($integra['status_int'] == 22) || ($integra['status_int'] == 23) ){
							echo "Produto ".$product['id']." ainda em cadastramento no marketplace".$integra['int_to'].". Não será marcado para envio\n";
							continue;
						}
					}
					// adiciono produto ativo na fila para a integração
					echo 'Adicionando para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. "\n"; 
					$prd = Array(
						'int_id' => $integration['id'],
						'prd_id' => $product['id'],
						'company_id' => $product['company_id'],
						'store_id' => $product['store_id'],
						'date_last_int' => $date_last_int,
						'status' => 1,
						'status_int' => 0,
						'int_type' => 13,        // BLING FORÇADO
						'int_to' => $integration['int_to'] , 
						'skubling' =>$skubling,
						'skumkt' => $skumkt
					);
					// var_dump($prd);
					$this->model_integrations->setProductToMkt($prd);
				} 
				else {
					echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. "\n"; 
					// removo produto inativo da fila para a integração
					$prdData = Array(
						'status' => 0,
						'user_id' => 1  // Administrador que retirou 
					);
					//$this->model_integrations->unsetProductToMkt( $integration['id'],$product['id'],$product['company_id'],$prdData);
					$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
				}
			}
		}
	}

	function acertaProdutosMudaramLoja() {
		$sql = "SELECT pr.*,p.store_id as loja,p.company_id as empresa FROM prd_to_integration pr, products p WHERE pr.prd_id=p.id AND p.store_id != pr.store_id";
		$query = $this->db->query($sql);
		$prds_to = $query->result_array();
		foreach($prds_to as $prd_to){
			$sql = "UPDATE bling_ult_envio SET store_id=? WHERE prd_id = ?";
			$query = $this->db->query($sql,array($prd_to['loja'],$prd_to['prd_id']));
			
			$sql = "SELECT * FROM prd_to_integration WHERE prd_id = ? AND store_id = ? AND int_to =?";
			$query = $this->db->query($sql,array($prd_to['prd_id'],$prd_to['loja'],$prd_to['int_to']));
			$prd_certo = $query->row_array();
			echo "---------------------------\n";
			if ($prd_certo) {
				if ($prd_certo['skubling']== $prd_to['skubling']) {
					if ($prd_certo['status_int'] == 2) {
						echo "Remove do ".$prd_to['id']. " ". $prd_to['loja']. " ".$prd_to['empresa']."\n";
						$sql = "DELETE FROM prd_to_integration WHERE id=?";
						$query = $this->db->query($sql,array($prd_to['id']));				
					}
					else {	
						echo "Remove do ".$prd_certo['id']. " ". $prd_to['loja']. " ".$prd_to['empresa']."\n";
						$sql = "DELETE FROM prd_to_integration WHERE id=?";
						$query = $this->db->query($sql,array($prd_certo['id']));
						echo "faz update do ".$prd_to['id']. " ". $prd_to['loja']. " ".$prd_to['empresa']."\n";
						$sql = "UPDATE prd_to_integration SET store_id = ? WHERE id=?";
						$query = $this->db->query($sql,array($prd_to['loja'],$prd_to['id']));
					}
					
				}
				else {
					echo "não sei o que fazer ".$prd_to['id'].' e '.$prd_certo['id']."\n";
				}
			}
			else {

				echo "faz update do ".$prd_to['id']. " ". $prd_to['loja']. " ".$prd_to['empresa']."\n";
				$sql = "UPDATE prd_to_integration SET store_id = ? WHERE id=?";
				$query = $this->db->query($sql,array($prd_to['loja'],$prd_to['id']));
			}		
		}	
	}

	function apagaErro() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$sql = "SELECT * FROM prd_to_integration WHERE company_id = 31";
		$query = $this->db->query($sql);
		$prds_to = $query->result_array();
		foreach($prds_to as $prd_to){
			$sql = "SELECT * FROM integrations WHERE id = ".$prd_to['int_id'];
			$query = $this->db->query($sql);
			$int = $query->row_array();
			
			$sql = "SELECT * FROM products WHERE id = ".$prd_to['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			if ($prd['store_id'] != $int['store_id']) {
				echo "Apagando ". $prd_to['id']." prd_id= ".$prd_to['prd_id']." storeid= ".$prd['store_id']." storeidINt= ".$int['store_id']."\n";
				$sql = "DELETE FROM prd_to_integration WHERE id = ".$prd_to['id'];
				$query = $this->db->query($sql);
			}
		}
	
	}
	
	function acertaPrdtoIntegration() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$sql = "SELECT * FROM bling_ult_envio";
		$query = $this->db->query($sql);
		$prds_to = $query->result_array();
		foreach($prds_to as $bling){
						
			$sql = "SELECT * FROM prd_to_integration WHERE int_to = '".$bling['int_to']."' AND prd_id = ".$bling['prd_id'] ;
			$query = $this->db->query($sql);
			$int = $query->row_array();
			
			$sql = "SELECT * FROM products WHERE id = ".$bling['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			if (($int['skubling'] != $bling['skubling'])) {
			//if (($int['skubling'] != $bling['skubling']) || ($int['skumkt'] != $bling['skumkt'])) {
				echo "intto=".$bling['int_to']." prd=".$bling['prd_id']." qty=".$prd['qty']." skuB=".$bling['skubling']." skum=".$bling['skumkt']." skuB=".$int['skubling']." skum=".$int['skumkt']."\n";
				//$sql = "update prd_to_integration set skubling = '".$bling['skubling']."', skumkt ='".$bling['skumkt']."' WHERE id=".$int['id'];
				$sql = "update prd_to_integration set skubling = '".$bling['skubling']."' WHERE id=".$int['id'];
				$query = $this->db->query($sql);
			}
			
		}
	
	}

	function cargaCepBling()
	{
		// Foi feita a carga uma única vez. Mas s e precisar carregar de novo, está pronto
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$sql = "SELECT * FROM bling_ult_envio";
		$query = $this->db->query($sql);
		$prds_to = $query->result_array();
		foreach($prds_to as $bling){
						
			$sql = "SELECT * FROM products WHERE id = ".$bling['prd_id'] ;
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			
			$sql = "SELECT * FROM stores WHERE id = ".$prd['store_id'];
			$query = $this->db->query($sql);
			$loja = $query->row_array();
			
			
		    $sql = "UPDATE bling_ult_envio set CNPJ = ?, zipcode = ? WHERE id=?";
			$query = $this->db->query($sql, array( preg_replace('/\D/', '', $loja['CNPJ']),  preg_replace('/\D/', '', $loja['zipcode']),$bling['id'] ));
		}
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