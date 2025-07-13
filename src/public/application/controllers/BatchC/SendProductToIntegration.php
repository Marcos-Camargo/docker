<?php
/*

*/   
 class SendProductToIntegration extends BatchBackground_Controller {
		
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
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_integrations');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_products_marketplace');
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
		
		if (!is_null($params)) {
			$retorno = $this->sendMarketplace($params);
			$this->criaProductsMarketplace($params);
		}
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function criaProductsMarketplace($prd_id) 
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Criando products marketplace\n";
		echo " produto ".$prd_id."\n";
		$product = $this->model_products->getProductData(0,$prd_id); 
		
		if (!$product) {
			echo "produto inexistente\n";  
			return;
		}

		$sql = "SELECT * FROM integrations WHERE company_id = ? AND store_id = ? ORDER BY int_to";
		$query = $this->db->query($sql, array($product['company_id'],$product['store_id']));
        $integrations = $query->result_array();
		foreach ($integrations as $integration) {

			echo 'Produto  '.$product['id'].' para '.$integration['name']."\n";
			// adiciono no products_marketplace se não existir 
			$hub = ($integration['int_type'] == 'DIRECT');
		    $this->model_products_marketplace->createIfNotExist($integration['int_to'],$product['id'],$hub);
		}
		
	}
	
	function sendMarketplace($prd_id)
	{
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Marcando todos os produtos para envio para todos os marketplaces da Conectala\n";
		$this->load->library('calculoFrete');
		
		echo " produto ".$prd_id."\n";
		$product = $this->model_products->getProductData(0,$prd_id); 
		if (!$product) {
			echo "produto inexistente\n";  
			return;
		}
		$sql = "SELECT * FROM integrations WHERE company_id = ? AND store_id = ? ORDER BY int_to";
		$query = $this->db->query($sql, array($product['company_id'],$product['store_id']));
        $integrations = $query->result_array();

		foreach ($integrations as $integration) {
			if (($integration['int_to'] == 'ML')) {
				//continue;  // pulo ML pois estamos bloqueados
			}
			echo 'Produto da Loja '.$integration['store_id'].' para '.$integration['name']."\n";
			$store = $this->model_stores->getStoresData($integration['store_id']);
			
			if ($store['active'] != 1) {
				echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois a loja não está ativa\n"; 
				$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
				continue;
			}
			if (($product['status'] != 1) || ($product['situacao'] != '2')) {
				echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " produto inativo ou incompleto\n"; 
				$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
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
			);

			if ($this->calculofrete->verificaCorreios($prd_info) ||
				$this->calculofrete->verificaTipoVolume($prd_info,$store['addr_uf'],$store['addr_uf']) ||
				$this->calculofrete->verificaPorPeso($prd_info,$store['addr_uf'])) {
					// produto ok, posso publicar
				$skubling = null;
				$skumkt = null;
				$date_last_int = '';
				
				// Verifico se devo auto-aprovar ou não de acordo com a integração.  
				if ($integration['auto_approve']) {
					$approved = 1;
				}
				else {
					$approved = 3;
				}
				
				// verifico se está com o status_int=20 que significa que ainda está em cadastramento pelo Marketplace
				$integra = $this->model_integrations->getPrdIntegrationByFields($integration['id'], $product['id'],$product['store_id']);
				if ($integra) {
					$skubling = $integra['skubling'];
					$skumkt = $integra['skumkt'];
					$date_last_int = $integra['date_last_int'];
					if (($integra['status_int'] == 20) || ($integra['status_int'] == 22) || ($integra['status_int'] == 23) ){
						echo "Produto ".$product['id']." ainda em cadastramento no marketplace".$integra['int_to'].". Não será marcado para envio\n";
						if ($integra['status'] == 0) {
							$integra['status'] = 1; 
							$this->model_integrations->updatePrdToIntegration($integra,$integra['id']);
						}
						continue;
					}
					$approved = $integra['approved'];  // Mantenho a aprovação do produto anterior 
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
					'int_type' => 13,        // Loja Conecta Lá
					'int_to' => $integration['int_to'] , 
					'skubling' =>$skubling,
					'skumkt' => $skumkt,
					'approved' => $approved,
				);
				// var_dump($prd);
				$this->model_integrations->setProductToMkt($prd);
				}
			else  {
				// não posso publicar
				echo 'Removendo para '. $integration['name'].' produto '.$product['id'].' loja '.$product['store_id']. " pois não tem nem correios nem transportadora\n"; 
				$this->model_integrations->changeStatus( $integration['id'],$product['id'],$product['store_id'], 0, 1);
			} 
		
		}
	}

}
?>