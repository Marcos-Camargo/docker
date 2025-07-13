<?php
/*
Realiza a atualização dos pedidos
*/

require 'NovoMundo/NMIntegration.php';


class NMOrders extends BatchBackground_Controller 
{
        
	private $integration = null;
	private $integration_data = null;
	private $int_to = 'NM';
	private $order_current_erro = null;


	public function __construct()
	{
        parent::__construct();

        echo '[NOVO MUNDO SelCen]['. strtoupper(__CLASS__) .'] '. strtoupper(__FUNCTION__)  .' ENVIRONMENT: ' . ENVIRONMENT . PHP_EOL;

        $this->integration = new NMIntegration();

		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_integrations');
		$this->load->model('model_category');
		$this->load->model('model_stores');
		$this->load->model('model_orders');
		$this->load->model('model_clients');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_freights');
        $this->load->model('model_settings');
		$this->load->library('calculoFrete');
        $this->load->library('ordersMarketplace');
    }



	function run($id = null, $params = null)
	{
		echo '[NOVO MUNDO]['. strtoupper(__CLASS__) .'] '. strtoupper(__FUNCTION__) . PHP_EOL;

		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");
		
		/* faz o que o job precisa fazer */
		$this->syncOrders();
        
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
    }


	function cancelar($id) 
	{
		$int_to = $this->int_to;

		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType(1, $int_to, "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($this->integration_data['auth_data'], true);
		
		$response = $this->integration->getOrder($api_keys, $id);

		if ($response['httpcode'] < 300) 
		{
			$order = json_decode($response['content'], true);
			$response_cancel = $this->cancelOrder($api_keys, $order);
		}
	}


	function approved($id = null)
	{
		$int_to = $this->int_to;

		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType(1, $int_to, "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($this->integration_data['auth_data'], true);
		
		$this->syncApproveds($api_keys);
	}


	function updateFrete($order_id) 
	{
		$int_to = $this->int_to;

		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType(1, $int_to, "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($this->integration_data['auth_data'], true);

		$order = $this->integration->getOrder($api_keys, $order_id);
		$this->syncFrete(json_decode($order['content'], true));
	}


	function syncFrete($order)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		// Verifico se todos os skus estão certos e são das mesmas empresas 
		$cpy ='';
		$store_id = '';
		$erro = false;
		$cross_docking_default = 5;  // <- hein?
		$cross_docking = $cross_docking_default; 
		$cancelar_pedido = false; 
		$total_order = 0;

		foreach ($order['items'] as $item) 
		{
			if (strrpos($item['skuSellerId'], "-") !== false) 
				$sku_item = substr($item['skuSellerId'], 0, strrpos($item['skuSellerId'], "-"));
			else
				$sku_item = $item['skuSellerId'];

			$total_order += $item['salePrice'];
			$sql = "SELECT * FROM integration_last_post WHERE skulocal = ? AND int_to = ?";
			echo 'Query sql';
			echo $sql;
			$query = $this->db->query($sql, array($sku_item, $this->int_to));
			$prf = $query->row_array();

			if (empty($prf))
			{
				if (strrpos($sku_item, "-") != 0)
				{
					if (strrpos($item['skuSellerId'], "-") !== false)
						$sku_item = substr($item['skuSellerId'], 0, strrpos($item['skuSellerId'], "-"));
					else
						$sku_item = $item['skuSellerId'];

					$sql = "SELECT * FROM integration_last_post WHERE skulocal = ? AND int_to = ?";
					$query = $this->db->query($sql, array($sku_item,$this->int_to));
					$prf = $query->row_array();
				}

				if (empty($prf)) 
				{
					echo 'O pedido '. $order['id'] .' possui produto '.$sku_item.' que não é do Marketplace '.$this->int_to."! Ordem não importada"."\n";
					$this->log_data('batch',$log_name,'O pedido '.$order['id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->int_to."! Ordem não importada","E");
					$erro = true; 
					$this->order_current_erro = $order;
					break;
				}
			}

			if ($cpy == '') // primeir item 
			{
				$cpy = $prf['company_id']; 
				$store_id = $prf['store_id'];
				echo "Peguei Empresa:".$cpy." e loja:".$store_id."\n";
			} 
			else 
			{ // proximos itens
				if (($cpy != $prf['company_id']) || ($store_id != $prf['store_id'] )) //empresas diferentes ou lojas diferentes 
				{
					$msg_cancela = 'O pedido '.$order['orderSiteId'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')!';
					echo 'O pedido '.$order['orderSiteId'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada'."\n";
					$this->log_data('batch',$log_name,'O pedido '.$order['orderSiteId'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada',"E");
					$cancelar_pedido = true;
				}
			}
		}

		// Leio a Loja para pegar o service_charge_value
		$store = $this->model_stores->getStoresData($store_id);
		
		//$orders['freight_seller'] = $store['freight_seller'];
		// agora a ordem 
		// $order = Array();

		$orders['customer_phone'] = $phone_1;
		$orders['customer_address']  = $order['billing']['address'];
		$orders['customer_address_compl'] = $order['billing']['complement'];
		$orders['customer_address_neigh'] = $order['billing']['quarter'];
		$orders['customer_address_num'] = $order['billing']['number'];
		$orders['customer_address_zip'] = preg_replace("/[^0-9]/", "", $order['billing']['zipCode']);
		$orders['customer_address_uf'] = $order['billing']['state'];
		$orders['customer_address_city']  = $order['billing']['city'];

		$frete = $order['freight']['actualAmount'];
		
		// para o verificação do frete
		$todos_correios = true; 
		$todos_tipo_volume= true;
		$todos_por_peso = true;
		$fr = array();
		$fr['destinatario']['endereco']['cep'] = $orders['customer_address_zip'];
		$fr['expedidor']['endereco']['cep'] = $store['zipcode'];
		$origem=$this->calculofrete->lerCep($store['zipcode']);
		$destino=$this->calculofrete->lerCep($orders['customer_address_zip']);

		foreach($order['items'] as $item) 
		{
			if (strrpos($item['skuSellerId'], "-") !== false)
				$sku_item = substr($item['skuSellerId'], 0, strrpos($item['skuSellerId'], "-"));
			else
				$sku_item = $item['skuSellerId'];

			$sql = "SELECT * FROM integration_last_post WHERE skulocal = ? AND int_to = ?";
			$query = $this->db->query($sql, array($sku_item, $this->int_to));
			$prf = $query->row_array();
			$cpy = $prf['company_id']; 
			$sku = 	$prf['sku'];			
			// $prd = $this->model_products->getProductBySku($sku,$cpy);
			$prd = $this->model_products->getProductData(0,$prf['prd_id']);
				
			//verificacao do frete
            if ($store['freight_seller'] == 1 && ($store['freight_seller_type'] == 3 || $store['freight_seller_type'] == 4))  // intelipost
			{
                $todos_tipo_volume 	= false;
                $todos_correios 	= false;
                $todos_por_peso 	= false;
            }

			$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 

			if ($todos_tipo_volume) // se é tipo_volume não pode ser correios e não procisa consultar os correios
				$todos_correios = false; 
			else // se não é tipo volumes, não precisa consultar o tipo_volumes pois já não achou antes 
				$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
			
			$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);

			$vl = Array ( 
				'tipo' => $prf['tipo_volume_codigo'],     
				'sku' => $sku_item,
				'quantidade' => 1,	           
				'altura' => (float) $prf['altura'] / 100,
				'largura' => (float) $prf['largura'] /100,
				'comprimento' => (float) $prf['profundidade'] /100,
				'peso' => (float) $prf['peso_bruto'],  
				'valor' => (float) $item['salePrice'],
				'volumes_produto' => 1,
				'consolidar' => false,
				'sobreposto' => false,
				'tombar' => false);

			$fr['volumes'][] = $vl;
			
			if ($prd['is_kit'] == 0) 
			{
				// pro frete não faz diferença se é kit ou não 
			}
			else  // é um kit,  
			{
				// pro frete não faz diferença se é kit ou não 
			}
			 
		}

		// verificação do frete
        if ($store['freight_seller'] == 1 && ($store['freight_seller_type'] == 3 || $store['freight_seller_type'] == 4)) // intelipost
		{
            if ($store['freight_seller_type'] == 3) {
				$tokenIntelipost = $store['freight_seller_end_point'];
			}
            else 
			{
                $querySettings   = $this->db->query('SELECT * FROM settings WHERE name = ?', array('token_intelipost_sellercenter'));
                $rowSettings     = $querySettings->row_array();
                $tokenIntelipost = $rowSettings['value'];
            }

            $responseIntelipost = $this->calculofrete->getPriceAndDeadline($fr, $cross_docking, $orders['customer_address_zip'], $tokenIntelipost);

            if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['id']) && $responseIntelipost !== false)
                $this->model_orders->setShipCompanyPreview($order_exist['id'], 'Intelipost', 'Intelipost', $responseIntelipost['deadline']);

        }
		elseif ($store['freight_seller'] == 1 && $store['freight_seller_type'] == 1)  // Precode
		{
			if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['id']))
				$this->model_orders->setShipCompanyPreview($order_exist["id"],'Logística Própria','Logística Própria',7);
		}
		else 
		{
			if ($todos_correios) 
			{
				$resposta = $this->calculofrete->calculaCorreiosNovo($fr,$origem,$destino);
			}
			elseif ($todos_tipo_volume) 
			{
				$resposta = $this->calculofrete->calculaTipoVolume($fr,$origem,$destino);
			}
			elseif ($todos_por_peso) 
			{
				$resposta = $this->calculofrete->calculaPorPeso($fr,$origem,$destino);
			}	
			else
			{
				$resposta = array(
					'servicos' => array(
						'FR' => array ('empresa'=>'FreteRápido','servico'=>'A contratar', 'preco'=>0,'prazo'=>0,),
					),
				);
			}

			if (array_key_exists('erro',$resposta ))
			{
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				return;	
			}

			if (!array_key_exists('servicos',$resposta )) 
			{
				$erro = $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				return;	
			}

			if (empty($resposta['servicos'] ))
			{
				$erro = $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				return;	
			}	

			$key = key($resposta['servicos']); 
			$transportadora = $resposta['servicos'][$key]['empresa']; 
			$servico =  $resposta['servicos'][$key]['servico'];
			$prazo = $resposta['servicos'][$key]['prazo']; 
		
			if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['id']))
				$this->model_orders->setShipCompanyPreview($order_exist["id"],$transportadora,$servico,$prazo);
			
		}
	}


    function syncOrders()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch',$log_name,"start","I");	
        
        $int_to = $this->int_to;

		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType(1, $int_to, "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($this->integration_data['auth_data'], true);
		$this->integration->setUrlApi($api_keys['api_url']);
		        
		$this->syncNews($api_keys);
		$this->syncApproveds($api_keys);
		$this->syncCancelled($api_keys);
    }


    function syncNews($api_keys) 
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $initialDate = date("Y-m-d\TH:i:s",time() - 60 * 60 * 24* 15);
		
		$response = $this->integration->getOrdersNew($api_keys, $initialDate, 0, 1);

		if ($response['httpcode'] != 200) 
		{
			echo " Erro URL: ". $this->api_url. " httpcode=".$response['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($response,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$response['httpcode'].' RESPOSTA: '.print_r($response,true),"E");
			return;
		}
		
		$response = json_decode($response['content'], true);
        
        foreach ($response["result"] as $orderMkt) 
		{
			$this->newOrder($orderMkt);
		}	
	}
	

	private function newOrder($orderMkt)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$this->order_current_erro = null;

		// Verifico se já existe, se não existir cadastro
		if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($orderMkt['id'])) 
		{
			echo "Ordem Já existe :".$order_exist['id']." status marketplace= ".$orderMkt['status']." paid_status=".$order_exist['paid_status']."\n";		
			return ;
		} 

		// Verifico se todos os skus estão certos e são das mesmas empresas 
		$cpy ='';
		$store_id = '';
		$erro = false;
		$cross_docking_default = 0;
		$cross_docking = $cross_docking_default; 
		$cancelar_pedido = false; 
		$total_order = 0;

		foreach($orderMkt['items'] as $item) 
		{
			if (strrpos($item['skuSellerId'], "-") !== false)
			{
				$sku_item = substr($item['skuSellerId'], 0, strrpos($item['skuSellerId'], "-"));
			}
			else 
			{
				$sku_item = $item['skuSellerId'];
			}

			$total_order += $item['salePrice'];
			$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
			echo 'Query sql';
			echo $sql;
			$query = $this->db->query($sql, array($sku_item, $this->int_to));
			$prf = $query->row_array();

			if (empty($prf))
			{
				if (strrpos($sku_item, "-") !=0)
				{
					if (strrpos($item['skuSellerId'], "-") !== false)
						$sku_item = substr($item['skuSellerId'], 0, strrpos($item['skuSellerId'], "-"));
					else
						$sku_item = $item['skuSellerId'];

					$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
					$query = $this->db->query($sql, array($sku_item,$this->int_to));
					$prf = $query->row_array();
				}

				if (empty($prf))  
				{
					echo 'O pedido '. $orderMkt['id'] .' possui produto '.$sku_item.' que não é do Marketplace '.$this->int_to."! Ordem não importada"."\n";
					$this->log_data('batch',$log_name,'O pedido '.$orderMkt['id'].' possui produto '.$sku_item.' que não é do Marketplace '.$this->int_to."! Ordem não importada","E");
					$erro = true; 
					$this->order_current_erro = $orderMkt;
					break;
				}
			}
			
			if($cpy == '')  // primeir item 
			{
				$cpy = $prf['company_id']; 
				$store_id = $prf['store_id'];
				echo "Peguei Empresa:".$cpy." e loja:".$store_id."\n";
			} 
			else 
			{ // proximos itens
				if (($cpy != $prf['company_id']) || ($store_id != $prf['store_id'] ))  //empresas diferentes ou lojas diferentes 
				{
					$msg_cancela = 'O pedido '.$orderMkt['orderSiteId'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')!';
					echo 'O pedido '.$orderMkt['orderSiteId'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada'."\n";
					$this->log_data('batch',$log_name,'O pedido '.$orderMkt['orderSiteId'].' possui produtos de mais de uma loja ('.$store_id.' e '. $prf['store_id'].')! Ordem precisa ser cancelada',"E");
					//$erro = true; 
					$cancelar_pedido = true;
				}
			}
			
			// Tempo de crossdocking 
			if (isset($prf['crossdocking']))   // pega o pior tempo de crossdocking dos produtos
			{
				if (((int) $prf['crossdocking'] + $cross_docking_default) > $cross_docking)
					$cross_docking = $cross_docking_default + (int) $prf['crossdocking']; 
			}

		}

		if ($erro)
			return ; // teve erro, encerro esta ordem 
		
		echo 'cross_docking='.$cross_docking."\n";
		
		// Leio a Loja para pegar o service_charge_value
		$store = $this->model_stores->getStoresData($store_id);

		// agora a ordem 
		$order = Array();
		//$orders['freight_seller'] = $store['freight_seller'];
		$statusNovo ='1';
		
		if ($cancelar_pedido)
			$statusNovo = 97; // já chega cancelado
		
		// gravo o novo pedido
		// PRIMEIRO INSERE O CLIENTE
		$clients['customer_name'] = $orderMkt['customer']['name'];
		$orders['customer_name'] = $clients['customer_name'];	
		$clients['phone_1'] = '';
		$clients['phone_2'] = '';

		if (count($orderMkt['customer']['phones']) > 0) 
		{
			$phone_1 = $orderMkt['customer']['phones'][0]['number'];
			$clients['phone_1'] = $phone_1;
			if (count($orderMkt['customer']['phones']) > 1)
				$clients['phone_2'] = $orderMkt['customer']['phones'][1]['number'];
		}

		$clients['cpf_cnpj'] = $orderMkt['customer']['documentNumber'];
		$clients['customer_address'] = $orderMkt['billing']['address'];
		$clients['addr_num'] = $orderMkt['billing']['number'];
		$complement = $orderMkt['billing']['complement'];
		$clients['addr_compl'] = (!is_null($complement) ? $complement : 'Sem Complemento');
		$clients['addr_neigh'] = $orderMkt['billing']['quarter'];
		$clients['addr_city'] = $orderMkt['billing']['city'];
		$clients['addr_uf'] = $orderMkt['billing']['state'];
		$clients['country'] = 'BR';
		$clients['zipcode'] = preg_replace("/[^0-9]/", "", $orderMkt['billing']['zipCode']);
		$clients['origin'] = $this->int_to;
		$clients['origin_id'] = $orderMkt['customer']['documentNumber'];
		$clients['email'] =  $orderMkt['customer']['email'];;

		$clients['ie'] = '';
		$clients['rg'] = '';

		$orders['customer_phone'] = $phone_1;
		$orders['customer_address']  = $orderMkt['billing']['address'];
		$orders['customer_address_compl'] = $orderMkt['billing']['complement'];
		$orders['customer_address_neigh'] = $orderMkt['billing']['quarter'];
		$orders['customer_address_num'] = $orderMkt['billing']['number'];
		$orders['customer_address_zip'] = preg_replace("/[^0-9]/", "", $orderMkt['billing']['zipCode']);
		$orders['customer_address_uf'] = $orderMkt['billing']['state'];
		$orders['customer_address_city']  = $orderMkt['billing']['city'];

		$orders['bill_no'] = $orderMkt['id'];
		$bill_no = $orderMkt['id'];
		$orders['numero_marketplace'] = $orderMkt['id']; // numero do pedido no marketplace 
		$orders['date_time'] = $orderMkt['purchasedAt'];
		
		$orders['total_order'] = $total_order;
		
		$orders['service_charge_rate'] = $store['service_charge_value'];  
		$orders['service_charge_freight_value'] = $store['service_charge_freight_value'];  
		$orders['service_charge'] = $orderMkt['totalAmount']  * $store['service_charge_value'] / 100;  
		$orders['vat_charge_rate'] = 0; //pegar na tabela de empresa - Não está sendo usado.....
		$orders['vat_charge'] =$orderMkt['totalAmount']  * $orders['vat_charge_rate'] / 100; //pegar na tabela de empresa - Não está sendo usado.....
		$orders['gross_amount'] = $orderMkt['totalAmount'] ;
		$orders['total_ship'] = $orderMkt['freight']['actualAmount'];
		$frete = $orderMkt['freight']['actualAmount'];
		
		$orders['discount'] = $orderMkt['totalDiscountAmount']; 
		$orders['net_amount'] = $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'];

		if ($client_id = $this->model_clients->getByOrigin($this->int_to,$orderMkt['customer']['documentNumber']))
		{
			$clients['id'] = $client_id['id'];
			$client_id = $this->model_clients->replace($clients);
			echo "Cliente Atualizado:".$client_id."\n";
		} 
		else 
		{
			$client_id = $this->model_clients->replace($clients);
			echo "Cliente Inserido:".$client_id."\n";
		}	

		if (!$client_id) 
		{
			$this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
			return;
		}

		$orders['paid_status'] = $statusNovo; 
		$orders['company_id'] = $cpy;   
		$orders['store_id'] = $store_id;
		$orders['origin'] = $this->int_to;
		$orders['user_id'] = 1;   
		$orders['customer_id'] = $client_id;
		
		$order_id = $this->model_orders->insertOrder($orders);
		echo "Inserido:".$order_id."\n";

		if (!$order_id) 
		{
			$this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
			return ;
		}
		
		if ($cancelar_pedido)
		{
			$this->cancelOrder($authorization, $orderMkt);
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

		foreach($orderMkt['items'] as $item)
		{
			if (strrpos($item['skuSellerId'], "-") !== false) 
				$sku_item = substr($item['skuSellerId'], 0, strrpos($item['skuSellerId'], "-"));
			else
				$sku_item = $item['skuSellerId'];

			$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
			$query = $this->db->query($sql, array($sku_item, $this->int_to));
			$prf = $query->row_array();
			$cpy = $prf['company_id']; 
			$sku = 	$prf['sku'];			
			//$prd = $this->model_products->getProductBySku($sku,$cpy);
			$prd = $this->model_products->getProductData(0,$prf['prd_id']);
			
			if ($prd['is_kit'] ==0) 
			{
				$items = array();
				$items['order_id'] = $order_id; // ID da order incluida
				$items['product_id'] = $prd['id'];
				$items['sku'] = $sku;
				$variant='';

				if ($prd['has_variants'] != '')
				{
					$variant = substr($item['skuSellerId'],strrpos($item['skuSellerId'], "-")+1);	
					$items['sku'] = $sku.'-'.$variant;
					$items['variant'] = $variant;
				}
				
				$items['name'] = $prd['name'];
				$items['qty'] = 1; //substr($item['id'],strrpos($item['id'], "-")+1);
				$items['rate'] = $item['salePrice'];
				$items['amount'] = (float)$items['qty'] * (float)$items['rate'];
				
				$items['discount'] = (float)$item['salePrice'] - $items['amount']; 
				$items['company_id'] = $prd['company_id']; 
				$items['store_id'] = $prd['store_id']; 
				$items['un'] = 'Un' ; // Não tem na Via Varejo
				$items['pesobruto'] = $prd['peso_bruto'];  // Não tem na Via Varejo
				$items['largura'] = $prd['largura']; // Não tem na Via Varejo
				$items['altura'] = $prd['altura']; // Não tem na Via Varejo
				$items['profundidade'] = $prd['profundidade']; // Não tem na Via Varejo
				$items['unmedida'] = 'cm'; // não tem na Via Varejo
				$items['kit_id'] = null;
				$items['skumkt'] = $sku_item;
				//var_dump($items);
				$item_id = $this->model_orders->insertItem($items);

				if (!$item_id) 
				{
					$this->log_data('batch',$log_name,'Erro ao incluir item',"E");
					return; 
				}

				$itensIds[]= $item_id; 

				if (!$cancelar_pedido) 
				{
					$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant, $order_id);
					$this->model_blingultenvio->reduzEstoque($this->int_to,$prd['id'],$items['qty']);
					
					// vejo se o produto estava com promoção de estoque e vejo se devo terminar 
					$this->model_promotions->updatePromotionByStock($prd['id'],$items['qty'],$item['salePrice']); 
				}

				//verificacao do frete 
				$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 

				if ($todos_tipo_volume)
				{ // se é tipo_volume não pode ser correios e não procisa consultar os correios
					$todos_correios = false; 
				}
				else 
				{ // se não é tipo volumes, não precisa consultar o tipo_volumes pois já não achou antes 
					$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
				}	

				$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
				$vl = Array ( 
					'tipo' => $prf['tipo_volume_codigo'],     
		            'sku' => $sku_item,
		            'quantidade' => 1,	           
		            'altura' => (float) $prf['altura'] / 100,
				    'largura' => (float) $prf['largura'] /100,
				    'comprimento' => (float) $prf['profundidade'] /100,
				    'peso' => (float) $prf['peso_bruto'],  
		            'valor' => (float) $item['salePrice'],
		            'volumes_produto' => 1,
		            'consolidar' => false,
		            'sobreposto' => false,
		            'tombar' => false);
	            $fr['volumes'][] = $vl;
			}
			else 
			{ // é um kit,  
				echo "O item é um KIT id=". $prd['id']."\n";
				$productsKit = $this->model_products->getProductsKit($prd['id']);
				foreach ($productsKit as $productKit)
				{
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
					$items['qty'] = $productKit['qty'];
					$items['rate'] = $productKit['price'] ;  // pego o preço do KIT em vez do item
					$items['amount'] = (float)$items['rate'] * (float)$items['qty'];
					$items['discount'] = 0; // Não sei de quem tirar se houver desconto. 
					$items['company_id'] = $prd['company_id']; 
					$items['store_id'] = $prd['store_id']; 
					$items['un'] = 'Un' ; // Não tem na Via Varejo
					$items['pesobruto'] = $prd['peso_bruto'];  // Não tem na Via Varejo
					$items['largura'] = $prd['largura']; // Não tem na Via Varejo
					$items['altura'] = $prd['altura']; // Não tem na Via Varejo
					$items['profundidade'] = $prd['profundidade']; // Não tem na Via Varejo
					$items['unmedida'] = 'cm'; // não tem na Via Varejo
					$items['skumkt'] = $sku_item;
					//var_dump($items);
					$item_id = $this->model_orders->insertItem($items);

					if (!$item_id)
					{
						$this->log_data('batch',$log_name,'Erro ao incluir item',"E");
						return; 
					}

					$itensIds[]= $item_id; 

					// Acerto o estoque do produto filho
					if (!$cancelar_pedido) 
						$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant, $order_id);				
				}

				if (!$cancelar_pedido) 
					$this->model_blingultenvio->reduzEstoque($this->int_to,$prd['id'], $items['qty']);  // reduzo o estoque do produto KIT no Bling_utl_envio

				//verificacao do frete 
				$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 

				if ($todos_tipo_volume)  // se é tipo_volume não pode ser correios e não procisa consultar os correios
					$todos_correios = false; 				
				else // se não é tipo volumes, não precisa consultar o tipo_volumes pois já não achou antes 
					$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
				
				$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
				$vl = Array ( 
					'tipo' => $prf['tipo_volume_codigo'],     
		            'sku' => $sku_item,
		            'quantidade' => 1,	           
		            'altura' => (float) $prf['altura'] / 100,
				    'largura' => (float) $prf['largura'] /100,
				    'comprimento' => (float) $prf['profundidade'] /100,
				    'peso' => (float) $prf['peso_bruto'],  
		            'valor' => (float) $item['salePrice'],
		            'volumes_produto' => 1,
		            'consolidar' => false,
		            'sobreposto' => false,
		            'tombar' => false);
	            $fr['volumes'][] = $vl;
			}			
		}
		
		// verificação do frete
		if ($store['freight_seller'] == 1 && $store['freight_seller_type'] == 1) 
		{
			$this->model_orders->setShipCompanyPreview($order_id,'Logística Própria','Logística Própria',7);
		}
		else 
		{
			if ($todos_correios) 
			{
				$resposta = $this->calculofrete->calculaCorreiosNovo($fr,$origem,$destino);
			}
			elseif ($todos_tipo_volume) 
			{
				$resposta = $this->calculofrete->calculaTipoVolume($fr,$origem,$destino);
			}
			elseif ($todos_por_peso) 
			{
				$resposta = $this->calculofrete->calculaPorPeso($fr,$origem,$destino);
			}	
			else 
			{
				$resposta = array(
					'servicos' => array(
						'FR' => array ('empresa'=>'FreteRápido','servico'=>'A contratar', 'preco'=>0,'prazo'=>0,),
					),
				);
			}

			if (array_key_exists('erro',$resposta )) 
			{
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				return;	
			}

			if (!array_key_exists('servicos',$resposta ))
			 {
				$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				return;	
			}

			if (empty($resposta['servicos'] )) 
			{
				$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
				echo $resposta['erro']."\n"; 
				$this->log_data('batch',$log_name, $resposta['erro'],"W");
				return;	
			}	

			$key = key($resposta['servicos']); 
			$transportadora = $resposta['servicos'][$key]['empresa']; 
			$servico =  $resposta['servicos'][$key]['servico'];
			$prazo = $resposta['servicos'][$key]['prazo']; 
			$this->model_orders->setShipCompanyPreview($order_id,$transportadora,$servico,$prazo);
		}
		
	}


	function syncApproveds($authorization, $offset = 0, $limit = 50) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$_offset = $offset;

        $initialDate = date("Y-m-d\TH:i:s",time() - 60 * 60 * 24* 15);
		
		$response = $this->integration->getOrdersApproved($authorization, $initialDate, $offset);

		if ($response['httpcode'] != 200) 
		{
			echo " Erro URL: ". $url. " httpcode=".$response['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($response,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$response['httpcode'].' RESPOSTA: '.print_r($response,true),"E");
			return;
		}
		
		$response = json_decode($response['content'], true);
		
		echo PHP_EOL . "syncApproveds ". $_offset. "-" .count($response["orders"]) . PHP_EOL;

		foreach ($response["orders"] as $order) 
		{
			$this->markApproved($authorization, $order);
		}

		echo PHP_EOL;

		if (count($response["orders"]) >= $limit) 
		{
			$_offset += $limit;
			$this->syncApproveds($authorization, $_offset);
		}
	}


	private function markApproved($authorization, $order) 
	{
		$cross_docking_default = 0;
		$cross_docking = $cross_docking_default; 

		if (!is_null($this->order_current_erro)) 
		{
			if ($this->order_current_erro['id'] == $order['id']) 
				return ;
		}

		echo PHP_EOL ."Order: ". $order['id'] . "... ";

		if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['id'])) 
		{
			echo "Achou... Paid_Status: ". $order_exist['paid_status'] . "... ";

			if ($order_exist['paid_status'] == 1)
			{
				foreach ($order['items'] as $item) 
				{
					$sku_item =  explode('-', $item['skuSellerId'])[0];

					$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
					$query = $this->db->query($sql, array($sku_item, $this->int_to));
					$prf = $query->row_array();

					// Tempo de crossdocking 
					if (isset($prf['crossdocking']))   // pega o pior tempo de crossdocking dos produtos
					{
						if (((int) $prf['crossdocking'] + $cross_docking_default) > $cross_docking) 						
							$cross_docking = $cross_docking_default + (int) $prf['crossdocking']; 
					}
				}
				
				$date = (new DateTime($order["approvedAt"]))->format("Y-m-d");
				$data_pago = (new DateTime($order["approvedAt"]))->format("Y-m-d H:i:s");
				$data_limite_cross_docking = $this->somar_dias_uteis($date, $cross_docking, ''); 

				$sql = "SELECT * FROM orders WHERE data_pago is null and numero_marketplace = ? and origin = ? ";
				$query = $this->db->query($sql, array($order['id'], $this->int_to));
				$find = $query->row_array();

				echo "Atualizar Paid Status: 3 ... ";
				$this->model_orders->updatePaidStatus($order_exist['id'], 3);

				if (!is_null($find)) 
				{
					echo "Atualizar Data Pago: ". $data_pago ."... ". "Atualizar CrossDocking: ". $data_limite_cross_docking;
					$this->model_orders->updateDataPagoWithCrossDocking($order_exist['id'], $data_pago, $data_limite_cross_docking);
				}
			}
		}
		else 
		{
			echo "Não Achou... ";
			$this->newOrder($order);
			$this->markApproved($authorization, $order);
		}
	}


	function syncCancelled($authorization, $offset = 0, $limit = 50)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$_offset = $offset;

        $initialDate = date("Y-m-d\TH:i:s",time() - 60 * 60 * 24* 15);
		
		$response = $this->integration->getOrdersCanceled($authorization, $initialDate, $offset);

		if ($response['httpcode'] != 200) 
		{
			echo " Erro URL: ". $url. " httpcode=".$response['httpcode']."\n"; 
			echo " RESPOSTA ".$this->int_to.": ".print_r($response,true)." \n"; 
			$this->log_data('batch',$log_name, 'ERRO no get site:'.$url.' - httpcode: '.$response['httpcode'].' RESPOSTA: '.print_r($response,true),"E");
			return;
		}
		
		$response = json_decode($response['content'], true);
		
		echo PHP_EOL . "syncCancelled ". $_offset. "-" .count($response["orders"]) . PHP_EOL;

		foreach ($response["orders"] as $order) 
		{
			$this->markCancelled($authorization, $order);
		}

		echo PHP_EOL;

		if (count($response["orders"]) >= $limit)
		{
			$_offset += $limit;
			$this->syncCancelled($authorization, $_offset);
		}
	}


	private function markCancelled($authorization, $order)
	{
        echo PHP_EOL . '[CANCELLED]['.$this->int_to.'] Order: '. $order['id'] . "... ";

		if ($order_exist = $this->model_orders->getOrdersDatabyBill($this->int_to, $order['id'])) 
		{
			echo "Ordem Já existe :".$order_exist['id']."  paid_status=".$order_exist['paid_status']."... ";
            $this->ordersmarketplace->cancelOrder($order_exist['id'], false);
		}
		else 
		{
			echo 'Order não localizada... ';
		}
	}


	private function cancelOrder($authorization, $order) 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$response = $this->integration->cancelOrder($authorization, $order);

		if ($response['httpcode'] == 400) 
		{
			$errors_response = json_decode($response['content'], true);
			
			foreach ($errors_response['errors'] as $error) 
			{
				$message = 'Http Status: '. $error['message'] . ' - Código: ' . $error['code'] . ' - ' . $error['message'];
				$this->log_data('batch', $log_name, $message, "W");
			}

			if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['id'])) 
			{
				$this->model_orders->updatePaidStatus($order_exist['id'], 97);
			}
		}

		return $response;
	}
}