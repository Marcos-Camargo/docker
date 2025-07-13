<?php
/*
Baixa as categorias do Madeira Madeira 
*/  
require APPPATH . "controllers/BatchC/MadeiraMadeira/Main.php";

 class MadProductsStatus extends Main {
	
	var $days_to_send_again = 7;  // se estiver em aguarde depois destes dias, coloca na fila novamente 
	
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
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_integrations');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_log_integration_product_marketplace');
		$this->load->model('model_queue_products_marketplace');
		
    }

	// php index.php BatchC/MadeiraMadeira/MadProductsStatus run 
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		
		// seta os valores iniciais e pega os dados da integração. 
		If (is_null($params) || ($params=='null')) {  // se não passou parâmetro, é o conecta Lá 
			$this->store_id=0;
			$this->int_to = 'MAD';
		}
		else {
			$this->store_id= $params;
			$this->int_to = 'H_MAD';
		}
		$this->getIntegration();
		
		// Verifica a situação dos produtos no marketplace
		$this->checkProducts();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function checkProducts()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$offset = 0;
		$limit = 100; 
		$exist_products = true;
		
		$types = array (
			'aprocessar', 
			'acategorizar',
			'reprovado',
			'divergente',
			'aguardando',
			'aprovado', 
		);
		while ($exist_products) {
			
			// busca os produtos aguardando serem cadastrados no Madeira Madeira
			$prds_int = $this->model_integrations->getPrdIntegrationByIntToStatus($this->int_to, 1, 22, $offset, $limit);
			 
			if (count($prds_int) == 0) {
				$exist_products = false;
				continue;
			}
			$offset += $limit; 
			
			foreach($prds_int as $prd_int) {
				$in_mad = false;
				foreach ($types as $type) {
					$product_mad = $this->getProduct($type, $prd_int['skubling']);
					if ($product_mad) {
						$in_mad = true; 
						echo $prd_int['skubling'].' '.$type."\n";
						if ($type == 'aprovado') {
							// gravo o log que foi aprovado
							$data_log = array( 
								'int_to' =>  $this->int_to,
								'prd_id' => $prd_int['prd_id'],
								'function' => 'Aceito no Marketplace sku '.$prd_int['skubling'],
								'url' => "/v1/produto/".$prd_int['skubling'],
								'method' => 'GET',
								'sent' => null,
								'response' => json_encode($product_mad),
								'httpcode' => 200,
							);
							$this->model_log_integration_product_marketplace->create($data_log);
							
							// adiciono o produto na fila para mandar preço e estoque 
							$data = array (
								'id'     => 0, 
								'status' => 0,
								'prd_id' => $prd_int['prd_id'],
								'int_to' => $this->int_to
							);
							$this->model_queue_products_marketplace->create($data);
							break;
						} elseif ($type == 'reprovado') {
							if (array_key_exists('historico_validacao', $product_mad['data'][0])) {
								// verifico se já gravei o erro. 
								// var_dump($product_mad['data'][0]);
								$historicos = $product_mad['data'][0]['historico_validacao']; 
								$error_mad = '';
								foreach ($historicos as $historico) {
									if (array_key_exists('historico',$historico)) {
										$error_mad .=  $historico['historico'].' ';
									}
									else {
										$error_mad .=  $historico[0]['historico'];
									}
								}
								$error_mad = trim($error_mad);
								$errors = $this->model_errors_transformation->getErrorsByProductIdVariant($prd_int['prd_id'], $this->int_to, $prd_int['variant']);
								$find = false;
								foreach($errors as $error) {
									if ($errod['message'] == $error_mad) {
										$find = true; 
										continue;
									}
								}
								if (!$find) {
									$trans_err = array(
										'prd_id' => $prd_int['prd_id'],
										'skumkt' => $prd_int['skubling'],
										'int_to' => $this->int_to,
										'step' => "Configuração",
										'message' => $error_mad,
										'carrefour_import_id' => null,
										'status' => 0,
										'variant' => $prd_int['variant']
									);
									// marco os erros antigos deste produto como resolvido
									$this->model_errors_transformation->setStatusResolvedByProductId($prd_int['prd_id'],$this->int_to);
									$this->model_errors_transformation->create($trans_err);
								}
								break;
							}
							else {
								echo "Ocorreu um erro e não sei tratar\n";
								echo "resposta: ".print_r($product_mad,true)."\n";
								die;   
							}
						} elseif ($type == 'divergente') {
							$error_mad = "Não foi possível fazer match. Abra chamado para interveção manual no portal do marketplace";
							$errors = $this->model_errors_transformation->getErrorsByProductIdVariant($prd_int['prd_id'], $this->int_to, $prd_int['variant']);
							$find = false;
							foreach($errors as $error) {
								if ($errod['message'] == $error_mad) {
									$find = true; 
									continue;
								}
							}
							if (!$find) {
								$trans_err = array(
									'prd_id' => $prd_int['prd_id'],
									'skumkt' => $prd_int['skubling'],
									'int_to' => $this->int_to,
									'step' => "Aprovação Marketplace",
									'message' => $error_mad,
									'carrefour_import_id' => null,
									'status' => 0,
									'variant' => $prd_int['variant']
								);
								// marco os erros antigos deste produto como resolvido
								$this->model_errors_transformation->setStatusResolvedByProductId($prd_int['prd_id'],$this->int_to);
								$this->model_errors_transformation->create($trans_err);
							}
							break; 
						} else {

							if ($prd_int['date_last_int'] != '') {
								if(strtotime($prd_int['date_last_int']) <= strtotime('-'.$this->days_to_send_again.' days')) {
									echo "Já se passaram ".$this->days_to_send_again." dias para cadastrar. colocando fila novamente\n";					
									// adiciono o produto na fila para mandar preço e estoque 
									$data = array (
										'id'     => 0, 
										'status' => 0,
										'prd_id' => $prd_int['prd_id'],
										'int_to' => $this->int_to
									);
									$this->model_queue_products_marketplace->create($data);
								}
							}
							break;
						}
					}
				}
			    if (!$in_mad) {  // Sumiu do madeira Madeira....
					$error = "Produto {$prd_int['prd_id']} e sku {$prd_int['skubling']} não encontrado no marketplace";
					echo $error."\n";
					$this->log_data('batch',$log_name,$error,"E");
					
					// reset do prd_to_integration
					$this->model_integrations->updatePrdToIntegration(
						array(
							'skubling'  	=> null, 
							'skumkt' 	 	=> null,
							'status_int' 	=> 1,  
						),$prd_int['id']);
					
					// coloca na fila novamente
					$data = array (
						'id'     => 0, 
						'status' => 0,
						'prd_id' => $prd_int['prd_id'],
						'int_to' => $this->int_to
					);
					$this->model_queue_products_marketplace->create($data);
			    }
			}
		}
	}
	
	
	function getProduct($type, $sku) {
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;	
		if ($type == 'aprovado') { $type = '';}
		else { $type = $type.'/';}
		$url = "/v1/produto/".$type.$sku;
		$this->processURL($url,'GET', null);
		
		if ($this->responseCode == 404) {
			return false;
		}
		if ($this->responseCode == 502) { // bad gateway
		    sleep(60);
			return $this->getProduct($type, $sku); 
		}
		if ($this->responseCode != 200) {			
			$error = "Erro {$this->responseCode} ao acessar {$this->site}{$url} na função GET. Resposta = ".print_r($this->result,true);			
			echo $error."\n";
			$this->log_data('batch',$log_name,$error,"E");
			die;
		}
		return json_decode($this->result,true);
		
	}
}
