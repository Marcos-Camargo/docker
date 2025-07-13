<?php
/*
 
Atualiza pedidos que chegaram na SkyHub

*/   
class SkyHubAcertaOrders extends BatchBackground_Controller {
	
	var $int_to='B2W';
	var $apikey='';
	var $email='';
	
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_promotions');
		$this->load->model('model_category');

	}
    
	function setInt_to($int_to) {
		$this->int_to = $int_to;
	}
	function getInt_to() {
		return $this->int_to;
	}
	function setApikey($apikey) {
		$this->apikey = $apikey;
	}
	function getApikey() {
		return $this->apikey;
	}
	function setEmail($email) {
		$this->email = $email;
	}
	function getEmail() {
		return $this->email;
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
		$this->getkeys(1,0);
		//$this->getordersSemItem();
		$this->recalculaExpedicao();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getkeys($company_id,$store_id) {
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		$integration = $this->model_integrations->getIntegrationsbyCompIntType($company_id,$this->getInt_to(),"CONECTALA","DIRECT",$store_id);
		$api_keys = json_decode($integration['auth_data'],true);
		$this->setApikey($api_keys['apikey']);
		$this->setEmail($api_keys['email']);
	}

	function recalculaExpedicao() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo " comecei\n";
		$offset= 0;
		$limit =1; 
		while(true) {
			$sql = 'SELECT * FROM orders WHERE origin = "B2W" and paid_status=3 LIMIT '.$limit.' OFFSET '.$offset;
			$query = $this->db->query($sql);
			$orders = $query->result_array();
			if (!$orders) {
				break; 
			}
			$offset += $limit;
			foreach($orders as $order) {
				$sql = 'SELECT * FROM orders_item  WHERE order_id =?';
				$query = $this->db->query($sql, array($order['id']));
				
				$new_cross_docking = null;
				$cross_docking = 1;
				$orders_item = $query->result_array();
				foreach($orders_item as $order_item) {
					$prd = $this->model_products->getProductData(0, $order_item['product_id']);
					// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
					$new_cross_docking = $this->getCrossDocking($prd['category_id'], $new_cross_docking);
					if (!$new_cross_docking) {
						if ($prd['prazo_operacional_extra'] == '0' ) {
							$prd['prazo_operacional_extra'] = 1;
						}
						if (((int)$prd['prazo_operacional_extra'] ) > $cross_docking) {
							$cross_docking = (int)$prd['prazo_operacional_extra'];
						};
					}
				}
				if ($new_cross_docking) {
					$data_exp = $this->somar_dias_uteis($order['data_pago'],$new_cross_docking,'').' 00:00:00';
					if ($data_exp != $order['data_limite_cross_docking']) {
						echo "pedido ".$order['id'].' '.$order['numero_marketplace'].' '.$order['data_pago'].' '.$order['data_limite_cross_docking'].' '.$data_exp.' cate ='.$new_cross_docking."\n"; 
						$data = array ('data_limite_cross_docking' => $data_exp );
						$this->model_orders->updateByOrigin($order['id'],$data); 
						//die;
					}
					
				}
				else {
					$data_exp = $this->somar_dias_uteis($order['data_pago'],$cross_docking,'').' 00:00:00';
					if ($data_exp != $order['data_limite_cross_docking']) {
					    echo "pedido ".$order['id'].' '.$order['numero_marketplace'].' '.$order['data_pago'].' '.$order['data_limite_cross_docking'].' '.$data_exp.' prod ='.$cross_docking."\n"; 
						$data = array ('data_limite_cross_docking' => $data_exp );
						$this->model_orders->updateByOrigin($order['id'],$data); 
						//die;
						
					}
				}
			}
		}
		
		
		
	}

	function getordersSemItem()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		// filtro por data https://api.skyhub.com.br/orders?filters[start_date]=15/08/2019&filters[end_date]=15/08/2019
		
		// filtro pedidos novos https://api.skyhub.com.br/orders?filters[statuses][]=book_product   
		// filtro pedidos cancelados https://api.skyhub.com.br/orders?filters[statuses][]=order_canceled  
		// filtro pedidos aprovados https://api.skyhub.com.br/orders?filters[statuses][]=payment_received  
		// ver os demais status https://api.skyhub.com.br/statuses
		// se eu quiser ver todos os produtos
		// neste momento usaremos as filas 
		
		$this->load->library('calculoFrete');
		
		$temOrdem = true;
		die; 
		
		$sql = 'SELECT * FROM orders WHERE numero_marketplace = "Shoptime-108248006201"';
		$query = $this->db->query($sql);
		$errados = $query->result_array();
		foreach ($errados as $errado) {
			
			echo ' processando id '.$errado['id'].' - '. $errado['numero_marketplace']." ";
			$url = 'https://api.skyhub.com.br/orders/'.$errado['numero_marketplace'];
			$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
			if ($retorno['httpcode'] == 429) {
				echo 'dormindo ..';
				sleep(60);
				$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
			}
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA : '.print_r($retorno,true),"E");
				return;
			}	

			$pedido = json_decode($retorno['content'],true);
			//var_dump($pedido);

			// Verifico se todos os skus estão certos e são das mesmas empresas 
			$cpy ='';
			$store_id = '';
			$erro = false;
			$cross_docking_default = 2;// tempo default de cross_docking  - depois colocar no prefixes
			$cross_docking = $cross_docking_default;
			$new_cross_docking = null;
			foreach($pedido['items'] as $item) {
				$sku_item = $item['product_id'];
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
				$query = $this->db->query($sql, array($sku_item,$this->getInt_to()));
				$prf = $query->row_array();
				if (empty($prf)) {
					if (strrpos($sku_item, "-") !=0) {
						$sku_item = substr($item['codigo'], 0, strrpos($item['codigo'], "-"));
						$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
						$query = $this->db->query($sql, array($sku_item,$this->getInt_to()));
						$prf = $query->row_array();
					}
					if (empty($prf))  {
						echo 'O pedido '.$pedido['numero'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()."! Ordem não importada"."\n";
						$this->log_data('batch',$log_name,'O pedido '.$pedido['code'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->getInt_to()."! Ordem não importada","E");
						$erro = true; 
						break;
					}
				}
				echo ' produto id ='.$prf['prd_id'].' skubling ='.$prf['skubling']."\n";
				 
				if($cpy == '') { // primeir item 
					$cpy = $prf['company_id']; 
					$store_id = $prf['store_id'];
					echo "Peguei Empresa:".$cpy." e loja:".$store_id."\n";
		    	} 
		    	else 
		    	{ // proximos itens
					if (($cpy != $prf['company_id']) || ($store_id != $prf['store_id'] )) { //empresas diferentes ou lojas diferentes 
						echo 'O pedido '.$pedido['code'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada'."\n";
						$this->log_data('batch',$log_name,'O pedido '.$pedido['code'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada',"E");
						$erro = true; 
						if ($this->cancelaPedido($pedido['code'])) { // cancelo o pedido
						   // removo da fila 
							$url = 'https://api.skyhub.com.br/queues/orders/'.$pedido['code'];
							$resp = $this->deleteSkyHub($url, $this->getApikey(), $this->getEmail());
							if ($retorno['httpcode'] != 200) {
								echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
								echo " RESPOSTA: ".print_r($retorno,true)." \n"; 
								$this->log_data('batch',$log_name, 'ERRO ao remover da fila de pedidos site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
							}
						}
						break; // abandono os itens. 	
					}
				}

				$sku = $prf['sku'];
				$prd = $this->model_products->getProductBySku($sku,$cpy);
				// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
				$new_cross_docking = $this->getCrossDocking($prd['category_id'], $new_cross_docking);

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
			echo 'cross_docking='.$cross_docking.' - Exceção='.json_encode($new_cross_docking)."\n";
			
			// Leio a Loja para pegar o service_charge_value
			$store = $this->model_stores->getStoresData($store_id);

			// agora a ordem 
			$order_id= $errado['id'];
			echo "order id =".$order_id."\n";
			// Itens 
			$quoteid = "";
			$this->model_orders->deleteItem($order_id);  // Nao deve deletar nada pois só pego ordem nova
			$itensIds = array();
			
			// para o verificação do frete
			
			foreach($pedido['items'] as $item) {
				$skubling = $item['product_id'];
				$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
				$query = $this->db->query($sql, array($skubling, $this->getInt_to()));
				$prf = $query->row_array();
			    $cpy = $prf['company_id']; 
				$sku = 	$prf['sku'];			
				echo  $item['product_id']."=".$cpy."=".$sku."\n";
				$prd = $this->model_products->getProductBySku($sku,$cpy);
				var_dump($prd);
			
				if ($prd['is_kit'] ==0) {
					$items = array();
					$items['order_id'] = $order_id; // ID da order incluida
					$items['product_id'] = $prd['id'];
					$items['sku'] = $sku;
					$variant='';
					if ($prd['has_variants'] != '') {
						$variant = substr($item['id'],strrpos($item['id'], "-")+1);	
						$items['sku'] = $sku.'-'.$variant;
					}
					$items['variant'] = $variant;
					$items['name'] = $prd['name'];
					$items['qty'] = $item['qty'];
					$items['rate'] = $item['special_price'];
					$items['amount'] = (float)$item['special_price'] * (float)$item['qty'];
					$items['discount'] = (float)$item['original_price'] - (float)$item['special_price']; 
					$items['company_id'] = $prd['company_id']; 
					$items['store_id'] = $prd['store_id']; 
					$items['un'] = 'Un' ; // Não tem na SkyHub
					$items['pesobruto'] = $prd['peso_bruto'];  // Não tem na SkyHub
					$items['largura'] = $prd['largura']; // Não tem na SkyHub
					$items['altura'] = $prd['altura']; // Não tem na SkyHub
					$items['profundidade'] = $prd['profundidade']; // Não tem na SkyHub
					$items['unmedida'] = 'cm'; // não tem na skyhub
					$items['kit_id'] = null;
					//var_dump($items);
					$item_id = $this->model_orders->insertItem($items);
					if (!$item_id) {
						echo 'Erro ao incluir item'."\n";
						$this->log_data('batch',$log_name,'Erro ao incluir item',"E");
						return; 
					}
					$itensIds[]= $item_id; 
					// Acerto o estoque do produto 
					if ($skystatus='NEW') {
						$this->model_products->reduzEstoque($prd['id'],$item['qty'],$variant);
						$this->model_blingultenvio->reduzEstoque($this->getInt_to(),$prd['id'],$item['qty']);
						
						// vejo se o produto estava com promoção de estoque e vejo se devo terminar 
						$this->model_promotions->updatePromotionByStock($prd['id'],$item['qty'],$item['special_price']); 
					}
				}
				else { // é um kit,  
					echo "O item é um KIT id=". $prd['id']."\n";
					$productsKit = $this->model_products->getProductsKit($prd['id']);
					foreach ($productsKit as $productKit){
						$prd = $this->model_products->getProductData(0,$productKit['product_id_item']);
						echo "Produto item =".$prd['id']."\n";
						$items = array();
						$items['order_id'] = $order_id; // ID da order incluida
						$items['kit_id'] = $productKit['product_id'];
						$items['product_id'] = $prd['id'];
						$items['sku'] = $prd['sku'];
						$variant = '';
						$items['variant'] = $variant;  // Kit não pega produtos com variantes
						$items['name'] = $prd['name'];
						$items['qty'] = $item['qty'] * $productKit['qty'];
						$items['rate'] = $productKit['price'] ;  // pego o preço do KIT em vez do item
						$items['amount'] = (float)$items['rate'] * (float)$items['qty'];
						$items['discount'] = 0; // Não sei de quem tirar se houver desconto. 
						$items['company_id'] = $prd['company_id']; 
						$items['store_id'] = $prd['store_id']; 
						$items['un'] = 'Un' ; // Não tem na SkyHub
						$items['pesobruto'] = $prd['peso_bruto'];  // Não tem na SkyHub
						$items['largura'] = $prd['largura']; // Não tem na SkyHub
						$items['altura'] = $prd['altura']; // Não tem na SkyHub
						$items['profundidade'] = $prd['profundidade']; // Não tem na SkyHub
						$items['unmedida'] = 'cm'; // não tem na skyhub
						//var_dump($items);
						$item_id = $this->model_orders->insertItem($items);
						if (!$item_id) {
							$this->log_data('batch',$log_name,'Erro ao incluir item',"E");
							return; 
						}
						$itensIds[]= $item_id; 
						// Acerto o estoque do produto filho
						if ($skystatus='NEW') {
							$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant);
						}
					}
					if (($skystatus='NEW') || ($skystatus='APPROVED')){
						$this->model_blingultenvio->reduzEstoque($this->getInt_to(),$prd['id'],$item['qty']);  // reduzo o estoque do produto KIT no Bling_utl_envio
					}
					
				}
				die;
				//verificacao do frete 
				$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
				$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 
				$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
				$vl = Array ( 
					'tipo' => $prf['tipo_volume_codigo'],     
		            'sku' => $skubling,
		            'quantidade' => $item['qty'],	           
		            'altura' => (float) $prf['altura'] / 100,
				    'largura' => (float) $prf['largura'] /100,
				    'comprimento' => (float) $prf['profundidade'] /100,
				    'peso' => (float) $prf['peso_bruto'],  
		            'valor' => (float) $item['special_price'],
		            'volumes_produto' => 1,
		            'consolidar' => false,
		            'sobreposto' => false,
		            'tombar' => false);
	            $fr['volumes'][] = $vl;
			}

			$this->calculofrete->updateShipCompanyPreview($order_id);

			/*
			 * [PEDRO HENRIQUE - 18/06/2021] Lógica para previsão da transportadora,
			 * método e prazo de envio, foi migrada para o método updateShipCompanyPreview,
			 * dentro da biblioteca CalculoFrete
			 *
			if ($todos_correios) {
				$resposta = $this->calculofrete->calculaCorreiosNovo($fr,$origem,$destino);
			}elseif ($todos_tipo_volume) {
				$resposta = $this->calculofrete->calculaTipoVolume($fr,$origem['state'],$destino['state']);
			}elseif ($todos_por_peso) {
				$resposta = $this->calculofrete->calculaPorPeso($fr,$origem['state'],$destino['state']);
			}	
			else {
				$resposta = array(
					'servicos' => array(
						'FR' => array ('empresa'=>'FreteRápido','servico'=>'A contratar', 'preco'=>0,'prazo'=>0,),
					),
				);
			}
			if (array_key_exists('erro',$resposta )) {
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				continue;	
			}
			if (!array_key_exists('servicos',$resposta )) {
				$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				continue;	
			}
			if (empty($resposta['servicos'] )) {
				$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				continue;	
			}	
			$key = key($resposta['servicos']); 
			$transportadora = $resposta['servicos'][$key]['empresa']; 
			$servico =  $resposta['servicos'][$key]['servico'];
			$this->model_orders->setShipCompanyPreview($order_id,$transportadora,$servico);
			*/
			
			// PARCELAS
			$i = 0;
			if (isset($pedido['parcelas'])) {
				$parcelas = $pedido['payments'];
				foreach($parcelas as $parc) {
					$i++;
					$parcs['parcela'] 			= $i;
					$parcs['order_id'] 			= $order_id;
					$parcs['bill_no'] 			= $bill_no;
					$parcs['data_vencto'] 		= $parc['transaction_date'];
					$parcs['valor'] 			= $parc['value'];
					$parcs['forma_id']	 		= $parc['sefaz']['id_payment'];
					$parcs['forma_desc'] 		= $parc['method'];
					$parcs['forma_cf'] 			= ''; // nao tem na skyhub 
					//campos novoas abaixo
					$parcs['method'] 			= $parc['method'];
					$parcs['autorization_id'] 	= $parc['autorization_id'];
					$parcs['card_issuer'] 		= $parc['card_issuer'];
					$parcs['description'] 		= $parc['description'];
					$parcs['parcels'] 			= $parc['parcels'];
					$parcs['name_card_issuer'] 	= $parc['sefaz']['name_card_issuer'];
					$parcs['name_payment'] 		= $parc['sefaz']['name_payment'];
					//var_dump($parcs);
					$parcs_id = $this->model_orders->insertParcels($parcs);
					if (!$parcs_id) {
						$this->log_data('batch',$log_name,'Erro ao incluir pagamento ',"E");
						return; 
					}
				}
			}
			// removo da fila 
			$url = 'https://api.skyhub.com.br/queues/orders/'.$pedido['code'];
			$resp = $this->deleteSkyHub($url, $this->getApikey(), $this->getEmail());
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO ao remover da fila de pedidos site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA: '.print_r($retorno,true),"E");
				return;
			}
		}	
	}

    function getordersClienteFaltando()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		// filtro por data https://api.skyhub.com.br/orders?filters[start_date]=15/08/2019&filters[end_date]=15/08/2019
		
		// filtro pedidos novos https://api.skyhub.com.br/orders?filters[statuses][]=book_product   
		// filtro pedidos cancelados https://api.skyhub.com.br/orders?filters[statuses][]=order_canceled  
		// filtro pedidos aprovados https://api.skyhub.com.br/orders?filters[statuses][]=payment_received  
		// ver os demais status https://api.skyhub.com.br/statuses
		// se eu quiser ver todos os produtos
		// neste momento usaremos as filas 
		
		$this->load->library('calculoFrete');
		
		$sql = 'SELECT * FROM orders where origin = "B2W" AND id>1000';
		$query = $this->db->query($sql);
		$errados = $query->result_array();
		foreach ($errados as $errado) {
			echo ' processando id '.$errado['id'].' - '. $errado['numero_marketplace']." ";
			$url = 'https://api.skyhub.com.br/orders/'.$errado['numero_marketplace'];
			$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
			if ($retorno['httpcode'] == 429) {
				echo 'dormindo ..';
				sleep(60);
				$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
			}
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA : '.print_r($retorno,true),"E");
				return;
			}	
			
			
			
			$pedido = json_decode($retorno['content'],true);
			$clients = array();
			// gravo o novo pedido
			// PRIMEIRO INSERE O CLIENTE
			$clients['customer_name'] = $pedido['customer']['name'];	
			$clients['customer_address'] = $pedido['billing_address']['street'];
			$clients['addr_num'] = $pedido['billing_address']['number'];
			$clients['addr_compl'] = $pedido['billing_address']['complement'];
			if ((is_null($clients['addr_compl'])) || $clients['addr_compl'] ='NULL'){
				$clients['addr_compl'] ='';
			}
			$clients['addr_neigh'] = $pedido['billing_address']['neighborhood'];
			$clients['addr_city'] = $pedido['billing_address']['city'];
			$clients['addr_uf'] = $pedido['billing_address']['region'];
			$clients['country'] = $pedido['billing_address']['country'];
			$clients['zipcode'] = $pedido['billing_address']['postcode'];
			$clients['phone_1'] = $pedido['billing_address']['phone'];
			$clients['origin'] = $this->getInt_to();
			$clients['origin_id'] = $pedido['customer']['vat_number'];
			if (!isset($pedido['customer']['phones'][1])) {
				$pedido['customer']['phones'][1]= '';
			}
			$clients['phone_2'] = $pedido['customer']['phones'][1];
			
			if (is_null($pedido['customer']['email'])) {
				$pedido['customer']['email'] = '';
			}
			$clients['email'] =  $pedido['customer']['email'];
			
			$clients['cpf_cnpj'] = $pedido['customer']['vat_number'];  
			$clients['ie'] = '';
			$clients['rg'] = '';
			
			// var_dump($clients);
			$cliente_atual = $this->model_clients->getClientsData($errado['customer_id']);
			if ($client_id = $this->model_clients->getByOrigin($this->getInt_to(),$pedido['customer']['vat_number'])) {
				if  ($client_id['id'] == $errado['customer_id']) {
					echo "OK\n";
				}else {
					if ( preg_replace("/[^0-9]/", "",$cliente_atual['cpf_cnpj']) == $clients['cpf_cnpj']) {
						echo "OK ".$cliente_atual['cpf_cnpj']." 2\n";
					}
					else {
						echo " --------------------  ANALIZAR \n";
						$client_id = $this->model_clients->replace($clients);
						echo '*************   Cliente Alterado: CPF '.$clients['cpf_cnpj']."\n";
						$errado['customer_id']= $client_id; 
						$this->model_orders->updateByOrigin($errado['id'],$errado);
					}
				}
			} else {
				$client_id = $this->model_clients->replace($clients);
				echo '*************   Cliente Inserido: CPF '.$clients['cpf_cnpj']."\n";
				$errado['customer_id']= $client_id; 
				$this->model_orders->updateByOrigin($errado['id'],$errado);
			}	
			
		 	
			
			//die; 
			
		}	
	}

	 function getorders2()
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		// filtro por data https://api.skyhub.com.br/orders?filters[start_date]=15/08/2019&filters[end_date]=15/08/2019
		
		// filtro pedidos novos https://api.skyhub.com.br/orders?filters[statuses][]=book_product   
		// filtro pedidos cancelados https://api.skyhub.com.br/orders?filters[statuses][]=order_canceled  
		// filtro pedidos aprovados https://api.skyhub.com.br/orders?filters[statuses][]=payment_received  
		// ver os demais status https://api.skyhub.com.br/statuses
		// se eu quiser ver todos os produtos
		// neste momento usaremos as filas 
		
		$this->load->library('calculoFrete');
		
		$sql = 'SELECT * FROM orders where customer_id = 480';
		$query = $this->db->query($sql);
		$errados = $query->result_array();
		foreach ($errados as $errado) {
			echo ' processando id '.$errado['id'].' - '. $errado['numero_marketplace']."\n";
			$url = 'https://api.skyhub.com.br/orders/'.$errado['numero_marketplace'];
			$retorno = $this->getSkyHub($url, $this->getApikey(), $this->getEmail());
			if ($retorno['httpcode'] != 200) {
				echo " Erro URL: ". $url. " httpcode=".$retorno['httpcode']."\n"; 
				echo " RESPOSTA : ".print_r($retorno,true)." \n"; 
				$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$retorno['httpcode'].' RESPOSTA : '.print_r($retorno,true),"E");
				return;
			}	
			$pedido = json_decode($retorno['content'],true);
			$clients = array();
			// gravo o novo pedido
			// PRIMEIRO INSERE O CLIENTE
			$clients['customer_name'] = $pedido['customer']['name'];	
			$clients['customer_address'] = $pedido['billing_address']['street'];
			$clients['addr_num'] = $pedido['billing_address']['number'];
			$clients['addr_compl'] = $pedido['billing_address']['complement'];
			if ((is_null($clients['addr_compl'])) || $clients['addr_compl'] ='NULL'){
				$clients['addr_compl'] ='';
			}
			$clients['addr_neigh'] = $pedido['billing_address']['neighborhood'];
			$clients['addr_city'] = $pedido['billing_address']['city'];
			$clients['addr_uf'] = $pedido['billing_address']['region'];
			$clients['country'] = $pedido['billing_address']['country'];
			$clients['zipcode'] = $pedido['billing_address']['postcode'];
			$clients['phone_1'] = $pedido['billing_address']['phone'];
			$clients['origin'] = $this->getInt_to();
			$clients['origin_id'] = $pedido['customer']['vat_number'];
			if (!isset($pedido['customer']['phones'][1])) {
				$pedido['customer']['phones'][1]= '';
			}
			$clients['phone_2'] = $pedido['customer']['phones'][1];
			
			if (is_null($pedido['customer']['email'])) {
				$pedido['customer']['email'] = '';
			}
			$clients['email'] =  $pedido['customer']['email'];
			
			$clients['cpf_cnpj'] = $pedido['customer']['vat_number'];  
			$clients['ie'] = '';
			$clients['rg'] = '';
			
			// var_dump($clients);
			if ($client_id = $this->model_clients->getByOrigin($this->getInt_to(),$pedido['customer']['vat_number'])) {
				$clients['id'] = $client_id['id'];
				$client_id = $this->model_clients->replace($clients);
				echo "Cliente Atualizado:".$client_id.' CPF '.$clients['cpf_cnpj']."\n";
			} else {
				$client_id = $this->model_clients->replace($clients);
				echo "Cliente Inserido:".$client_id.' CPF '.$clients['cpf_cnpj']."\n";
			}	
			if (!$client_id) {
				$this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
				return;
			}
			$errado['customer_id']= $client_id; 
			$this->model_orders->updateByOrigin($errado['id'],$errado);
			
			//die; 
			
		}	
	}

	function cancelaPedido($pedido) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$cancel = Array (
					'status' => 'order_canceled'
				);

		$json_data = json_encode($cancel);
		
		$url = 'https://api.skyhub.com.br/orders/'.$pedido.'/cancel';

		$resp = $this->postSkyHub($url.'', $json_data, $this->getApikey(), $this->getEmail());
		if (!($resp['httpcode']=="201") )  {  // created
			echo "Erro na respota do '.$this->getInt_to().' url:'.$url.' httpcode=".$resp['httpcode']." RESPOSTA ".$this->getInt_to().": ".print_r($resp['content'],true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO ao cancelar no '.$this->getInt_to().' url:'.$url.' - httpcode: '.$resp['httpcode'].' RESPOSTA '.$this->getInt_to().': '.print_r($resp['content'],true).' DADOS ENVIADOS:'.print_r($json_data,true),"E");
			return false;
		}
		return true;
		
	}


	function getSkyHub($url, $api_key, $login){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
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
	    return $header;
	}

	function postSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi', 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
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
	    return $header;
	}

	function putSkyHub($url, $post_data, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "PUT",
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
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
	    return $header;
	}

	function deleteSkyHub($url, $api_key, $login){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "DELETE",
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
				'content-type: application/json', 
				'x-accountmanager-key: YdluFpAdGi',  //fixo no teste 
				'x-api-key: '.$api_key,
				'x-user-email: '.$login
				)
	    );
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
	    return $header;
	}

	function getCrossDocking($category_id, $new_cross_docking)
	{
		// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
		$category = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);
		$dataCategory = $this->model_category->getCategoryData($category);
		if ($dataCategory && $dataCategory['days_cross_docking']) {

			$limit_cross_docking_category = (int)$dataCategory['days_cross_docking'];

			if ($new_cross_docking && $limit_cross_docking_category < $new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;

			if (!$new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;
		}
		return $new_cross_docking;
	}
}

?>
