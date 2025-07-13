<?php
/*
 
Baixa os pedidos que chegaram do Madeira Madeira 

*/   
require APPPATH . "controllers/BatchC/MadeiraMadeira/Main.php";

class MadGetOrders extends Main {
	
	
	
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
		$this->load->model('model_orders');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_mad_last_post');
		$this->load->model('model_promotions');
		$this->load->model('model_integrations');
		$this->load->model('model_products');
		$this->load->model('model_freights');
		$this->load->model('model_settings');
		$this->load->model('model_log_integration_order_marketplace');
		$this->load->library('ordersMarketplace');
    }

	// php index.php BatchC/MadeiraMadeira/MadGetOrders run 
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
		If (is_null($params) || ($params=='null')) {  // se não passou parâmetro, é o conecta Lá 
			$this->store_id=0;
			$this->int_to = 'MAD';
		}
		else {
			$this->store_id= $params;
			$this->int_to = 'H_MAD';
		}
		
		$this->getIntegration();
		$this->getOrders();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

    function getOrders()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->load->library('calculoFrete');
		
		$start_update_date= date("Y-m-d",time() - 60 * 60 * 24*30);
		$end_update_date= date("Y-m-d",time());

		$mad_statuses = array('new','approved','cancelled');
		$status_mad = array('','NOVO','RECEBIDO','APROVADO','CANCELADO', 'CANCELADO','NF EMITIDA','ENVIADO', 'ENTREGUE');
		
		foreach($mad_statuses as $mad_status) {
			$exist_orders = true; 
			$offset = 0;
			$limit = 20;
			echo "Procurando pedidos com status {$mad_status}\n";
			while ($exist_orders) {
	
				//	$url = "/v1/pedido/from={$start_update_date}&to={$end_update_date}&limit={$limit}&offset={$offset}";
				//$url = "/v1/pedido/limit={$limit}&offset={$offset}";
				$url = "/v1/pedido/{$mad_status}/limit={$limit}&offset={$offset}";
				echo $url."\n";
				$this->processURL($url,'GET', null); 
				
				if ($this->responseCode == 404) {
					echo "Acabou pedidos com status {$mad_status}\n";
					$exist_orders = false;
					continue;
				}
				if ($this->responseCode != 200) {
					$error = "Erro {$this->responseCode} ao acessar {$this->site}{$url} na função GET";
					echo $error."\n";
					$this->log_data('batch',$log_name,$error,"E");
					die;
				}
				$body = json_decode($this->result,true);
				if (empty($body)) {
					echo "Acabou empty\n";
					$exist_orders = false;
					continue;
				}
				$offset += $limit; 
				
				echo "Total de pedidos = ".$body['meta']['count']."\n"; 
	
				//$pedidos = json_decode($retorno['body'],true);
				foreach ($body['data'] as $pedido) {
					
					echo "------------------------------------------------------------------------\n";	
					echo "Pedido = ".$pedido['id_pedido']."\n";
					
					if ((int)$this->id_seller != (int)$pedido['id_seller']) { // o id_seller do pedido é o mesmo id do conectalá ?
						$error = 'O pedido '.$pedido['id_pedido'].' com id_seller ('.$pedido['id_seller'].') diferente do cadastrado na integração '.$this->int_to.' store_id '.$this->store_id."\n";
						echo $error."\n";
						$this->log_data('batch',$log_name,$error,"E");
					 	die; 
					}
					//var_dump($pedido);
					// Verifico se todos os skus estão certos e são das mesmas empresas 
					$cpy ='';
					$store_id = '';
					$erro = false;
					$cross_docking_default = 0;
					$cross_docking = $cross_docking_default; 
					$cancelar_pedido = false; 
					
					foreach($pedido['skus'] as $item) {
						$sku_item = $item['skuseller']; 
						$prf = $this->model_mad_last_post->getBySku($sku_item);
						if (empty($prf)) {
							var_dump($pedido);
							$error = 'O pedido '.$pedido['id_pedido'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->int_to.". Ordem não importada"."\n";
							echo $error."\n";
							$this->log_data('batch',$log_name,$error,"E");
							$erro = true; 
							break;
						}
						if($cpy == '') { // primeir item 
							$cpy = $prf['company_id']; 
							$store_id = $prf['store_id'];
							echo "Peguei Empresa:".$cpy." e loja:".$store_id."\n";
				    	} 
				    	else 
				    	{ // proximos itens
							if (($cpy != $prf['company_id']) || ($store_id != $prf['store_id'] )) { //empresas diferentes ou lojas diferentes 
							    $msg_cancela = 'O pedido '.$pedido['id_pedido'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')!';
								$error = 'O pedido '.$pedido['id_pedido'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada';
								echo $error."\n";
								$this->log_data('batch',$log_name,$error,"E");
								//$erro = true; 
								$cancelar_pedido = true;
							}
						}
						
						// Tempo de crossdocking
						if (isset($prf['crossdocking'])) {  // pega o pior tempo de crossdocking dos produtos
							if (((int) $prf['crossdocking'] + $cross_docking_default) > $cross_docking) {
								$cross_docking = $cross_docking_default + (int) $prf['crossdocking']; 
							};
						}
						
		
					}
					if ($erro) {
						continue; // teve erro, encerro esta ordem 
					}
					echo 'cross_docking='.$cross_docking."\n";
					
					// Leio a Loja para pegar o service_charge_value
					$store = $this->model_stores->getStoresData($store_id);
					
					// Vejo se já existe para atualizar 
					if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['id_pedido'])) {
					    
						$status = $pedido['status'];	
						echo "Ordem Já existe :".$order_exist['id']." status marketplace= ".$status." paid_status=".$order_exist['paid_status']."\n";
						
						// gravo o status do pedido no marketplace
						$this->model_orders->updateByOrigin($order_exist['id'],array('status_mkt'=> $status_mad[$status]));
						
						if ($status==1) {  // NOVO = 1 no madeira Madeira 
							if ($order_exist['paid_status'] == '1') {
								echo "Já está recebido, ignorando\n";
								continue;
							}
							else {
								if (!in_array($order_exist['paid_status'], [95,97])) {
									// Não deveria acontecer. Mensagem de erro.
									$erro ='Pedido '.$pedido['id_pedido'].' com status '.$status.' em '.$this->int_to.' já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
									echo $erro."\n"; 
									$this->log_data('batch',$log_name, $erro,"E");
									return;
								}
								else {
									echo "Já está recebido E PEDI PARA CANCELAR, ignorando\n";
									continue;
								}
							}
						}elseif (($status==4) || ($status==5)) { // CANCELADO = 4 ou 5 no madeira Madeira  
							echo 'status='.$status."\n";
							$this->ordersmarketplace->cancelOrder($order_exist['id'], false);
							if (!is_null( $pedido['datahora_cancelamento'])) {
								// acerto a data e hora do cancelamento
								$this->model_orders->updateByOrigin($order_exist['id'],array('date_cancel'=> $pedido['datahora_cancelamento']));
							}
							
							echo "Marcado para cancelamento no Frete rápido\n";
							continue; 
						}elseif (($status==3) || ($status==2)) { // APROVADO = 3 e PROCESSADO = 2
							if ($order_exist['paid_status'] == '1') {
								// Pedido foi aprovado, mudo o status para faturar . Não precisa alterar o estoque
								$this->model_orders->updatePaidStatus($order_exist['id'],3);
								// Se for pago, definir o cross docking a partir da data de pagamento
								$data_pago = date("Y-m-d H:i:s");
								if (!is_null($pedido['datahora_aprovacao'])){
									$data_pago = $pedido['datahora_aprovacao'];
								}
								$this->model_orders->updateByOrigin($order_exist['id'], 
									array(
										'data_pago' => $data_pago,
										'data_limite_cross_docking' => $this->somar_dias_uteis(date("Y-m-d",strtotime($data_pago)),$cross_docking,'')));
										
								if ($status == 3) { // Marco o pedido como recebido no Madeira Madeira 
									$this->setAsReceived($pedido['id_pedido']);
								}
								echo 'Pedido '.$order_exist['id']." marcado para faturamento\n";
								continue ;
							}
							elseif (in_array($order_exist['paid_status'], [95,97])) {
								echo "Já está recebido E PEDI PARA CANCELAR em uma rodada anterior, ignorando\n";
								continue;
							}
							elseif ($order_exist['paid_status'] == 3) {
								if ($status == 3) { // Marco o pedido como recebido no Madeira Madeira 
									$this->setAsReceived($pedido['id_pedido']);
								}
								echo "Já está recebido,ignorando\n";
								continue; 
								//pedido já recebido. 
							} 
							else {
								//pode acontecer se demorar a rodar o processo que atualiza os status dos pedidos. 
								$erro ='Pedido '.$pedido['id_pedido'].' com status '.$status.' e já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
								echo $erro."\n"; 
								//$this->log_data('batch',$log_name, $erro,"W");
								continue;
							}	
						}else {
							// Vou registrar os demais status somente na tela 
							$erro ='Pedido '.$pedido['id_pedido'].' com status '.$status.' e já existe na base no pedido '.$order_exist['id'].' e paid_status='.$order_exist['paid_status']; 
							echo $erro."\n"; 
							//$this->log_data('batch',$log_name, $erro,"W");
							continue;	
						}
					}
					// agora a ordem 
					$orders = Array();
					
					//$orders['freight_seller'] = $store['freight_seller'];
					
					$statusNovo ='';
					$status = $pedido['status'];
					if ($status==1) {  // NOVO = 1 no madeira Madeira 
						$statusNovo = 1;
					}elseif ($status== 3) {
					    $statusNovo = 3;
						// $orders['data_pago'] = date("Y-m-d H:i:s");
						$orders['data_pago'] =	$pedido['datahora_aprovacao'];
	
					}elseif (($status== 4) || ($status== 5)) { 
				        // Foi cancelado antes mesmo da gente pegar o pedido.
						$erro ='Pedido '.$pedido['id_pedido'].' com status '.$status.' mas não existe na nossa base'; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"W");
						continue;
					}
					else {
						// Não deveria cair aqui.
						$erro ='Pedido '.$pedido['id_pedido'].' com status '.$status.' mas não existe na nossa base'; 
						echo $erro."\n"; 
						$this->log_data('batch',$log_name, $erro,"W");
						die;
					}
					if ($cancelar_pedido) {
						$statusNovo = 97; // já chega cancelado
					}
					
					// gravo o novo pedido
					// PRIMEIRO INSERE O CLIENTE
					$clients = array();
					$clients['phone_1'] = '';
					$clients['customer_name'] 		= $pedido['comprador']['nome'];
					$clients['phone_1'] 			= $pedido['comprador']['telefone'];
					$clients['origin_id'] 			= $pedido['comprador']['documento'];
					$clients['email'] 				= $pedido['comprador']['email'];
					
					
					$clients['cpf_cnpj'] 			= $pedido['comprador']['documento'];
				
					$clients['customer_address'] 	= $pedido['comprador']['logradouro'];
					$clients['addr_num'] 			= $pedido['comprador']['numero'];
					$clients['addr_compl'] 			= '';
					if (!is_null($pedido['comprador']['complemento'])) {
						$clients['addr_compl'] 			= $pedido['comprador']['complemento'];
					}		
					$clients['addr_neigh'] 			= $pedido['comprador']['bairro'];
					$clients['addr_city'] 			= $pedido['comprador']['cidade'];
					$clients['addr_uf'] 			= $pedido['comprador']['uf'];
					$clients['country'] 			= 'BR';
					$clients['zipcode'] 			= preg_replace("/[^0-9]/", "", $pedido['comprador']['cep']);
					
					$orders['customer_name'] 			= $pedido['dados_entrega']['nome'];
					$orders['customer_address'] 		= $pedido['dados_entrega']['logradouro'];
					$orders['customer_address_num'] 	= $pedido['dados_entrega']['numero'];
					$orders['customer_address_compl'] 	= $pedido['dados_entrega']['complemento'];
					$orders['customer_address_neigh'] 	= $pedido['dados_entrega']['bairro'];
					$orders['customer_address_city'] 	= $pedido['dados_entrega']['cidade'];
					$orders['customer_address_uf'] 		= $pedido['dados_entrega']['uf'];
					$orders['customer_address_zip'] 	= preg_replace("/[^0-9]/", "", $pedido['dados_entrega']['cep']);
					$orders['customer_reference'] 		= '';
	
					$clients['origin'] = $this->int_to;
					// campos que não tem no Madeira Madeira
					$clients['phone_2'] = '';
					$clients['ie'] = '';
					$clients['rg'] = '';
					
					echo "----------------------------------------".$pedido['id_pedido']."\n";
	
					$client_id = $this->model_clients->insert($clients);
					if ($client_id==false) {
						echo 'Não consegui incluir o cliente'."\n";
						$this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
						return;
					}	
					
					$orders['bill_no'] 						= $pedido['id_pedido'];
					$bill_no 								= $pedido['id_pedido'];
					$orders['numero_marketplace'] 			= $pedido['id_pedido']; // numero do pedido no marketplace 
					$orders['date_time']					= $pedido['data_criacao'];
					$orders['customer_id'] 					= $client_id;
					$orders['customer_phone'] 				= $clients['phone_1'];
					
					$orders['total_order'] 					= $pedido['subtotal'];   
					$orders['total_ship'] 					= $pedido['frete'];
					$orders['gross_amount'] 				= $pedido['total'];
					$orders['service_charge_rate'] 			= $store['service_charge_value'];  
					$orders['service_charge_freight_value'] = $store['service_charge_freight_value'];  
					$orders['service_charge'] 				= $orders['gross_amount'] * $store['service_charge_value'] / 100;  
					$orders['vat_charge_rate'] 				= 0; //pegar na tabela de empresa - Não está sendo usado.....
					$orders['vat_charge'] 					= $orders['gross_amount'] * $orders['vat_charge_rate'] / 100; //pegar na tabela de empresa - Não está sendo usado.....
	
					$orders['discount']						= 0; // não achei no pedido da Madeira Madeira  
					$orders['net_amount'] 					= $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'];
			
					$orders['paid_status'] 					= $statusNovo; 
					$orders['company_id'] 					= $cpy;   
					$orders['store_id'] 					= $store_id;
					$orders['origin'] 						= $this->int_to;
					$orders['user_id'] 						= 1;   
					$orders['data_limite_cross_docking'] = $statusNovo != 3 ? null : $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,''); // define cross_docking apenas se for pago
	
					$order_id = $this->model_orders->insertOrder($orders);
					echo "Inserido:".$order_id."\n";
					if (!$order_id) {
						$this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
						return ;
					}
					
					if ($cancelar_pedido) {
						if (!$this->cancelaPedido($pedido['id'])) {
							die; 
						}
						$data = array(
				            'order_id' => $order_id,
				            'motivo_cancelamento' => $msg_cancela,
				            'data' => date("Y-m-d H:i:s"),
				            'status' => '1',
				            'user_id' => '1'
				        );
				        $this->model_orders->insertPedidosCancelados($data);
						
					}	
					// Itens 
					$quoteid = "";
					$this->model_orders->deleteItem($order_id);  // Nao deve deletar nada pois só pego ordem nova
					$itensIds = array();
					
					// para o verificação do frete
					$todos_correios = true; 
					$todos_tipo_volume= true;
					$todos_por_peso = true;
					$fr = array();
					$fr['destinatario']['endereco']['cep'] = $orders['customer_address_zip'];
			        $fr['expedidor']['endereco']['cep'] = $store['zipcode'];
					$origem=$this->calculofrete->lerCep($store['zipcode']);
					$destino=$this->calculofrete->lerCep($orders['customer_address_zip']);
					
					foreach($pedido['skus'] as $item) {
	
						$skumkt = $item['skuseller']; 
						$prf = $this->model_mad_last_post->getBySku($skumkt);
						
						$sku 		= $prf['sku'];
						$skuseller 	= $prf['sku'];
						echo  $skumkt."=".$sku."\n";
					    $prd = $this->model_products->getProductData(0,$prf['prd_id']);
						
						if ($prd['is_kit'] == 0) {
							$variant = (is_null($prf['variant'])) ? '' : $prf['variant'];
							$items = array(
								'skumkt' 			=> $skumkt,
								'order_id'			=> $order_id, // ID da order incluida
								'product_id'		=> $prd['id'],
								'sku'				=> $sku,
								'variant' 			=> $variant,
								'name'				=> $prd['name'],
								'qty' 				=> $item['quantidade'],
								'rate' 				=> $item['valor_unitario'],
								'amount'			=> $item['total'],
								'discount'			=> (float)$item['total'] - (float)$item['valor_unitario']*(int)$item['quantidade'],
								'company_id' 		=> $prd['company_id'],
								'store_id' 			=> $prd['store_id'], 
								'un' 				=> 'Un', // Não tem na Madeira Madeira
								'pesobruto' 		=> $prd['peso_bruto'],
								'largura' 			=> $prd['largura'],
								'altura' 			=> $prd['altura'],
								'profundidade' 		=> $prd['profundidade'],
								'unmedida' 			=> 'cm',	// não tem na Madeira Madeir
								'kit_id' 			=> null,
								'original_qty'  	=> $item['quantidade'],  // não é usado se não for kit mas podemos guardar de qualquer maneira 
								'original_amount' 	=> $item['total'],
							);
							$item_id = $this->model_orders->insertItem($items);
							if (!$item_id) {
								echo 'Erro ao incluir item. removendo pedido '.$order_id."\n";
								$this->model_orders->remove($order_id);
								$this->model_clients->remove($client_id);
								$this->log_data('batch',$log_name,'Erro ao incluir item. pedido mkt = '.$pedido['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
								return; 
							}
							$itensIds[]= $item_id; 
							if (!$cancelar_pedido) {
								$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant, $order_id);
	
								$this->model_mad_last_post->reduzEstoque($prf['id'],$item['quantidade']);
								
								// vejo se o produto estava com promoção de estoque e vejo se devo terminar 
								$this->model_promotions->updatePromotionByStock($prd['id'],$item['quantidade'],$item['valor_unitario']); 
							}
						}
						else { // é um kit,  
							echo "O item é um KIT id=". $prd['id']."\n";
							$productsKit = $this->model_products->getProductsKit($prd['id']);
							$original_qty 	 = $item['quantidade'] ;
							$original_amount = $item['total'] ;
							
							foreach ($productsKit as $productKit){
								$prd = $this->model_products->getProductData(0,$productKit['product_id_item']);
								echo "Produto item =".$prd['id']."\n";
								$variant = '';
								$items = array(
									'skumkt' 			=> $skumkt,
									'order_id'			=> $order_id, // ID da order incluida
									'product_id'		=> $prd['id'],
									'sku'				=> $prd['sku'],
									'variant' 			=> $variant, // Kit não pega produtos com variantes
									'name'				=> $prd['name'],
									'qty' 				=> $item['quantidade'] * $productKit['qty'],
									'rate' 				=> $productKit['price'], 
									'amount'			=> $productKit['price'] * $item['quantidade'] * $productKit['qty'], 
									'discount'			=> (float)$item['total'] - (float)$item['valor_unitario']*(int)$item['quantidade'],
									'company_id' 		=> $prd['company_id'],
									'store_id' 			=> $prd['store_id'], 
									'un' 				=> 'Un', // Não tem na Madeira Madeira
									'pesobruto' 		=> $prd['peso_bruto'],
									'largura' 			=> $prd['largura'],
									'altura' 			=> $prd['altura'],
									'profundidade' 		=> $prd['profundidade'],
									'unmedida' 			=> 'cm',	// não tem na Madeira Madeir
									'kit_id' 			=> $productKit['product_id'],
									'original_qty'  	=> $original_qty,
									'original_amount' 	=> $original_amount,
								);
								
								$original_qty 	 = null; // somente o primeiro item do kit recebe estes valores para poder enviar na nota fiscal. 
								$original_amount = null;
							
								//var_dump($items);
								$item_id = $this->model_orders->insertItem($items);
								if (!$item_id) {
									echo 'Erro ao incluir item. removendo pedido '.$order_id."\n";
									$this->model_orders->remove($order_id);
									$this->model_clients->remove($client_id);
									$this->log_data('batch',$log_name,'Erro ao incluir item. pedido mkt = '.$pedido['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
									return; 
								}
								$itensIds[]= $item_id; 
								// Acerto o estoque do produto filho
								if (!$cancelar_pedido) {
									$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant, $order_id);
								}
							}
							if (!$cancelar_pedido) {
								$this->model_mad_last_post->reduzEstoque($prf['id'],$item['quantidade']);  // reduzo o estoque do produto KIT no MAD_last_post
							}
						}
						//verificacao do frete
						if ($store['freight_seller'] == 1 && in_array($store['freight_seller_type'], [3, 4])) { // intelipost
							$todos_tipo_volume 	= false;
							$todos_correios 	= false;
							$todos_por_peso 	= false;
						}
						
						// acerto o registo para o calculo Frete
						$prf['altura'] = $prf['height'];
						$prf['largura'] = $prf['width'];
						$prf['profundidade'] = $prf['length'];
						$prf['peso_bruto'] = $prf['gross_weight'];
						$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 
						if ($todos_tipo_volume) { // se é tipo_volume não pode ser correios e não procisa consultar os correios	
							$todos_correios = false; 
						}
						else { // se não é tipo volumes, não precisa consultar o tipo_volumes pois já não achou antes 
							$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
						}
						$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
						$vl = Array ( 
							'tipo' 				=> $prf['tipo_volume_codigo'],     
				            'sku' 				=> $skumkt,
				            'quantidade' 		=> $item['quantidade'],	           
				            'altura' 			=> (float) $prf['altura'] / 100,
						    'largura' 			=> (float) $prf['largura'] /100,
						    'comprimento' 		=> (float) $prf['profundidade'] /100,
						    'peso' 				=> (float) $prf['peso_bruto'],  
				            'valor' 			=> (float) $item['valor_unitario']* $item['quantidade'],
				            'volumes_produto' 	=> 1,
				            'consolidar' 		=> false,
				            'sobreposto' 		=> false,
				            'tombar' 			=> false,
							'skuseller' 		=> $skuseller
						);
			            $fr['volumes'][] = $vl;
					}
	
					$this->calculofrete->updateShipCompanyPreview($order_id);
					$tipos_pga = '';
					foreach ($pedido['pagamento'] as $tipo_pga) {
						var_dump($tipo_pga);
						if (is_array($tipo_pga)) {
							$tipos_pga.= $tipo_pga['tipo']; 
						}
						else {
							$tipos_pga.= $tipo_pga; 
						}
					}
					$parcs = array (
						'parcela' 		=> 1,
						'order_id' 		=> $order_id,
						'bill_no' 		=> $bill_no,
						'data_vencto'	=> '',
						'valor'			=> $pedido['total'],
						'forma_id'      => '',
						'forma_desc'    => trim($tipos_pga),
						'forma_cf'		=> ''
					);
						
					$parcs_id = $this->model_orders->insertParcels($parcs);
					if (!$parcs_id) {
						$this->log_data('batch',$log_name,'Erro ao incluir pagamento ',"E");
						return; 
					}

					// Gravando o log do pedido
					$data_log = array(
						'int_to' 	=> $this->int_to,
						'order_id'	=> $order_id,
						'received'	=> json_encode($pedido)
					);
					$this->model_log_integration_order_marketplace->create($data_log);
	
				}	
			}
		}
	}
	
	function cancelaPedido($pedido)   // mandar email para alguém cancelar o pedido
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		/*
		echo 'Cancelando pedido ='.$pedido."\n"; 
		
		$motivo = 'Falta de produto';

		$cancel = Array (
					  "fulfilled" => false,
					  "rating" => "neutral",
					  "message" => "Não consigo atender no momento",
					  "reason" => "SELLER_REGRETS",
					  "restock_item" => true,
				);
		$meli= $this->getkeys(1,0);
		$params = array('access_token' => $this->getAccessToken());
		
		$url = 'orders/'.$pedido.'/feedback';
		
		$retorno = $meli->post($url, $cancel, $params);
		
	    //var_dump($resp); 

		if (!($retorno['httpCode']=="201") )  {  // created
			echo 'Erro na respota do '.$this->int_to.' httpcode='.$retorno['httpCode'].' RESPOSTA '.$this->int_to.': '.print_r($retorno['body'],true)."\n"; 
			$this->log_data('batch',$log_name, 'ERRO ao cancelar no '.$this->int_to.' - httpcode: '.$retorno['httpCode'].' RESPOSTA '.$this->int_to.': '.print_r($retorno['body'],true).' DADOS ENVIADOS:'.print_r($cancel,true),"E");
			return false;
		}
		 * 
		 */
		return true;
	}
	
	function setAsReceived($id) {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$url = "/v1/pedido/received";
		$data = array (
			array (
				"id_pedido" => (int)$id
			)
		);
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		$this->processURL($url,'PUT', $data);
		if (($this->responseCode != 200) && ($this->responseCode != 204))  {
			$error = 'Erro na resposta ao informar como recebido o pedido de id '.$id.' site: '.$this->site.$url.' httpcode:'.$this->responseCode.' RESPOSTA: '.print_r($this->result,true). ' DADOS ENVIADOS: '.print_r($data,true); 
			echo $error."\n";
			$this->log_data('batch',$log_name, $error,"E");
			return false;
		}
		return true;
	}
}

?>
