<?php
/*
Marca todos os produtos de todos os lojista 
 
Executa uma vez por dia
*/   
 class AutomaticPublishing extends BatchBackground_Controller {
	
	var $companySika;
	var $catalogSika; 
	var $storeSika; 
	
	public function __construct()
	{
		parent::__construct();

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
		$this->load->model('model_queue_products_marketplace');
		$this->load->model('model_settings');
		
    }
	//php index.php BatchC/AutomaticPublishing/run/null/CasaeVideo
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
		
		if ($params == 'null') {
			$this->log_data('batch',$log_name,'start int_to não informado',"I");
			echo 'int_to não informado operação cancelada';
			return ;
		}

		$this->sendMarketplace($params);
		$this->criaProductsMarketplace($params);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function criaProductsMarketplace($mkt = null) 
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Criando products marketplace\n";
		if (is_null($mkt)) {
			echo "Nenhum marketplace\n";
			return ; 
		}
		
		$integrations = $this->model_integrations->getIntegrationsbyTypeAndFromIntTo('BLING', 'HUB', $mkt);
		foreach ($integrations as $integration) {  
			$offset = 0;
			$limit = 1000;
			while (true) {
				echo "Lendo produtos da loja ".$integration['store_id']." no offset ".$offset."\n";
				$products =  $this->getProducts($integration['store_id'], $limit, $offset);
				if (!$products) {
					echo "Acabou \n";
					break;
				}
				$offset += $limit; 
				echo 'Verificando Produtos da Loja '.$integration['store_id'].' para '.$integration['name']."\n";
				foreach ($products as $product) {		 		
					// adiciono no products_marketplace se não existir 
					$hub = ($integration['int_type'] == 'DIRECT');
					$this->model_products_marketplace->createIfNotExist($integration['int_to'],$product['id'],$hub);
				}
			}
		}
		
	}
	
	function sendMarketplace($mkt = null)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Marcando todos os produtos para envio para todos os marketplaces do Seller Center\n";
		
		if (is_null($mkt)) {
			echo "Nenhum marketplace\n";
			return ; 
		}

		$integrations = $this->model_integrations->getIntegrationsbyTypeAndFromIntTo('BLING', 'HUB', $mkt);
		foreach ($integrations as $integration) {
			$offset = 0;
			$limit = 1000;

			$store = $this->model_stores->getStoresData($integration['store_id']);
			while (true) {
				echo "Lendo produtos da loja ".$integration['store_id']." no offset ".$offset."\n";
				$products =  $this->getProducts($integration['store_id'], $limit, $offset);
				/* $products = $this->model_products->getProductsByStore($integration['store_id'], $offset, $limit);*/
				if (!$products) {
					break;
				}
				$offset += $limit; 
				echo 'Produtos da Loja '.$integration['store_id'].' para '.$integration['name']."\n";
											
				foreach ($products as $product) {
					$prd_to = $this->model_integrations->getPrdIntegrationByFields($integration['id'],$product['id'],$product['store_id']);

					if ($store['active'] != 1) {
						if (!$prd_to) { 
							echo "Já não existe integracao ". $integration['name'].' do produto '.$product['id']."\n";
						}
						elseif ($prd_to['status'] == 0) {
							echo 'Já removido para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois a loja não está ativa\n"; 
						}
						else {
							echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois a loja não está ativa\n"; 
							$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
						}
			
						continue;
					}
					if (($product['status'] != 1) || ($product['situacao'] != '2')) {
						if (!$prd_to) { 
							echo "Já não existe integracao ". $integration['name'].' do produto '.$product['id']."\n";
						}
						elseif ($prd_to['status'] == 0) {
							echo 'Já removido para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " produto inativo ou incompleto\n"; 
						}
						else {
							echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " produto inativo ou incompleto\n"; 
							$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
						}
						continue;
					}

					$integras = $this->model_integrations->getPrdIntegrationByFieldsMulti($integration['id'], $product['id'],$product['store_id']);
					if (count($integras) == 0) { // nunca integrou então crio a integração 
						$this->markPrdToIntegration($mkt, $product, $integration, null);
					}
					else {
						foreach($integras as $int_prd) {
							if ($int_prd['status'] == 0) {
								echo "Ativando ".$int_prd['id'].' produto '.$int_prd['prd_id']."\n";
								$this->model_integrations->updatePrdToIntegration(array('status'=>1), $int_prd['id']);
							}
						}					
					}
				}
				$cnt = $this->model_queue_products_marketplace->countQueue();		
				
				while($cnt['qtd'] > 400) {
					echo "Dormindo pois tem ".$cnt['qtd']." na fila \n";
					sleep(60);					
					$cnt = $this->model_queue_products_marketplace->countQueue();
				}	
			}

		}
	}


	function markPrdToIntegration($mkt, $product, $integration, $integra) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$disable_message = $this->model_settings->getValueIfAtiveByName('disable_publication_of_new_products');
        if ($disable_message) {
			echo $disable_message . "\n";
            return ;
        }
		
		/* retirado o check de blacklist / whitelist daqui e passado para o ProductsConectala */ 
		$prd = Array(
			'int_id' 		=> $integration['id'],
			'prd_id' 		=> $product['id'],
			'company_id' 	=> $product['company_id'],
			'store_id' 		=> $product['store_id'],
			'date_last_int' => '',
			'status' 		=> 1,
			'status_int' 	=> 0,
			'rule' 			=> null,
			'int_type' 		=> 13,        // Loja Conecta Lá
			'int_to' 		=> $integration['int_to'] , 
			'skubling'		=> null,
			'skumkt' 		=> null,
			'approved' 		=> ($integration['auto_approve']) ? 1 : 3,
		);

		$msgvar ='';
		
		if (!is_null($integra)) {
			if (!is_null($integra['variant'])) {
				$msgvar =' variant: '.$integra['variant'].' ';
			}
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
	}

	private function getProducts($store_id, $limit, $offset) {
		$sql = 'SELECT * FROM products USE INDEX (index_store_id) WHERE store_id = ? ORDER BY id DESC LIMIT ? OFFSET ?';
		$query = $this->db->query($sql, array($store_id, $limit, $offset));
		return $query->result_array();
	}

}
?>
