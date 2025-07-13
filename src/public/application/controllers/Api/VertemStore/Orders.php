<?php

require APPPATH . "controllers/Api/FreteConectala.php";

class Orders extends FreteConectala {

    private $int_to;
	
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_stores');
        $this->load->model('model_orders');
        $this->load->model('model_clients');
        $this->load->model('model_products');
        $this->load->model('model_promotions');
        $this->load->model('model_vs_last_post');
        $this->load->model('model_freights');
        $this->load->model('model_integrations');
        $this->load->model('model_log_integration_order_marketplace');
        $this->load->library('CalculoFrete');
        $this->load->library('ordersMarketplace');    
		
    }

    /**
     * Get All Data from this method.
     *
     * @return void
     */
    public function index_get($orderId = null)
    {
  
         $this->response([
            "error" => [
                "code"      => "1",
                "message"   => "O verbo 'GET' não é compatível com essa rota",
                "exception" => null
            ]
        ], REST_Controller::HTTP_OK);
    }
	private function verifyHeaders($headers, $store) {
		foreach ($headers as $header => $value) {
			if (($header == 'Authorization') && (preg_match('/^basic/i', $value ))) {
				$user ="loja".$store['id'];
				$pass = substr($store['token_api'],0,12);
				list( $username, $password ) = explode( ':', base64_decode( substr( $value, 6 ) ) );
				
				if (($username == $user) && ($pass == $password)) {
					return true;
				}
				return false;
			}
		}
		return false;
	}
    
    /**
     * Post All Data from this method.
     *
     * @return void
     */
    public function index_post($supplier_id = null)
    {
        $this->int_to = 'VS';

		if (is_null($supplier_id)) {
			$error =  "No supplier id";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die; 
		} 
		
		$store = $this->model_integrations->getStoreByMKTSeller($this->int_to, $supplier_id);
		
		if (!$store) {
			$error =  "Invalid supplier id";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die; 
		} 
		
		if (!$this->verifyHeaders(getallheaders(), $store)) {
			$error =  "No authentication key or invalid";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die; 
		}
		
        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));
       // $this->log_data('api', 'Orders - POST', json_encode($data));

        // new order
        $return = $this->formatResponseCheckout($data, $supplier_id);
		
        // $this->log_data('api', 'CheckoutVS - Return', 'received='.json_encode($data).' return='.json_encode($return));
        $this->response($return, REST_Controller::HTTP_OK);
    }

    private function formatResponseCheckout($datas, $supplier_id)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        // Inicia transação
        $this->db->trans_begin();

        $create = $this->createOrder($datas['order'], $supplier_id);

        if (!$create[0]) {
            $this->db->trans_rollback();
            $this->log_data('api',$log_name,$create[2] . ' - Pedido = ' . json_encode($datas),"E");
            return [
            	'orderId' 	=> null, 
            	'orderVendorId' => null,
                "error" => [
                    "code"      => $create[1],
                    "message"   => $create[2]
                ]
            ];
        }

        $order_hv_id = $create[2];
		$order_id = $create[3];
			
		$response = array (
			'orderId' 		=> $order_hv_id, 
			'orderVendorId' => $order_id, 
			'error' 		=> null
		);
          
        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $this->log_data('api',$log_name,'Não foi possível se comunicar com a base de dados - Pedido = ' . json_encode($datas),"E");
			return [
            	'orderId' 	=> null, 
            	'orderVendorId' => null,
                "error" => [
                    "code"      => "510",
                    "message"   => 'Não foi possível se comunicar com a base de dados'
                ]
            ];
        }

		$data_log = array(
			'int_to' 	=> $this->int_to,
			'order_id'	=> $order_id,
			'received'	=> json_encode($datas)
		);
		$this->model_log_integration_order_marketplace->create($data_log);

        $this->db->trans_commit();
		
        return $response;
    }


    private function createOrder($pedido, $supplier_id)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['id']))
            return [false, 502, "Pedido ( {$pedido['id']} ) já existe!"];

        // Verifico se todos os skus estão certos e são das mesmas empresas
        $company_id ='';
        $store_id = '';
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default;
        $totalOrder = 0;
        $totalDiscount = 0;

        foreach($pedido['items'] as $item) {

            $sku        = $item['sku'];
            $prf = $this->model_vs_last_post->getBySku($sku);
            if (empty($prf)) {
            	return [false, 503, "Produto ( {$sku} ) não encontrado!"];
            }
                
			if ($supplier_id != $prf['seller_id']) {
				return [false, 504, "Produto ( {$sku} ) não pertence a esse supplier!"];
			}


            $prd = $this->model_products->getProductData(0,$prf['prd_id']);

            if ($item['quantity'] > $prd['qty'])
                return [false, 505, "Produto ( {$sku} ) sem estoque!"];

            $company_id = $prf['company_id'];
			$store_id = $prf['store_id'];
			
            // Tempo de crossdocking
            if ($prf['crossdocking'])  // pega o pior tempo de crossdocking dos produtos
                if ((int)$prf['crossdocking'] + $cross_docking_default > $cross_docking)
                    $cross_docking = $cross_docking_default + (int)$prf['crossdocking'];

            $totalOrder += (float)$item['costPrice'] / 100 * $item['quantity'];
			
        }

        // Leio a Loja para pegar o service_charge_value
        $store = $this->model_stores->getStoresData($store_id);

        // pedido
        $orders = Array();

        //$orders['freight_seller'] = $store['freight_seller'];

        $paid_status = 3; // sempre chegará como pago rick confirmar 

        // gravo o novo pedido
        $participant  = $pedido['participant'];
		$receiver = $pedido['shipping']['receiver'];
		$shippingAddress = $pedido['shipping']['shippingAddress'];
		
        $totalShip = $pedido['shipping']['costPrice'] / 100;
		
        // PRIMEIRO INSERE O CLIENTE
        $clients = array();
        $clients['customer_name']       = $participant['name'];
		$clients['email']               = $participant['email'] ?? '';
		$clients['cpf_cnpj']            = preg_replace("/[^0-9]/", "", $participant['cpfCnpj']);
		
        $clients['phone_1']             = isset($participant['phones'][0]) ? $participant['phones'][0]['DDD'].$participant['phones'][0]['number'] : '';
        $clients['phone_2']             = isset($participant['phones'][1]) ? $participant['phones'][1]['DDD'].$participant['phones'][1]['number'] : '';
    
        $clients['customer_address']    = $shippingAddress['address'] ?? '';
        $clients['addr_num']            = $shippingAddress['number'] ?? '';
        $clients['addr_compl']          = $shippingAddress['complement'] ?? '';
        $clients['addr_neigh']          = $shippingAddress['district'] ?? '';
        $clients['addr_city']           = $shippingAddress['city'] ?? '';
        $clients['addr_uf']             = $shippingAddress['state'] ?? '';
        $clients['country']             = 'BR';
        $clients['zipcode']             = preg_replace("/[^0-9]/", "", $shippingAddress['zipCode']);
        $clients['origin']              = $this->int_to; // Entender melhor como encontrar esse info
        $clients['origin_id']           = 1; // Entender melhor como encontrar esse info

        // campos que não tem na VTEX
        $clients['ie'] = '';
        $clients['rg'] = '';

        $client_id = $this->model_clients->insert($clients);
        if (!$client_id)
            return [false, "505", "Ocorreu um problema para gravar o cliente!"];

        $orders['data_pago'] 					= $this->formatDate($pedido['createDate']);
		$orders['data_limite_cross_docking'] 	= $this->somar_dias_uteis(date("Y-m-d"),$cross_docking);

        $orders['customer_name']            	= $receiver['name'];
        $orders['customer_address']         	= $clients['customer_address'];
        $orders['customer_address_num']     	= $clients['addr_num'];
        $orders['customer_address_compl']   	= $clients['addr_compl'];
        $orders['customer_address_neigh']   	= $clients['addr_neigh'];
        $orders['customer_address_city']    	= $clients['addr_city'];
        $orders['customer_address_uf']      	= $clients['addr_uf'];
        $orders['customer_address_zip']     	= $clients['zipcode'];
        $orders['customer_reference']       	= $shippingAddress['reference'] ?? '';

        $order_mkt                      		= $pedido['id'];
        $orders['bill_no']              		= $order_mkt;
        $orders['numero_marketplace']   		= $order_mkt;
		$orders['date_time']            		= $this->formatDate($pedido['createDate']);
        $orders['customer_id']          		= $client_id;
        $orders['customer_phone']       		= isset($receiver['phones'][0]) ? $receiver['phones'][0]['DDD'].$receiver['phones'][0]['number'] : $clients['phone_1'];
       
        $orders['total_order']          		= ($pedido['amount'] - $pedido['shipping']['costPrice']) / 100;
        $orders['total_ship']           		= $pedido['shipping']['costPrice'] /100 ;
        if (isset( $pedido['shipping']['shippingName'])){
            $orders['ship_company_preview'] = $pedido['shipping']['shippingName'];
        }
        if (isset( $pedido['shipping']['shippingMethod'])){
            $orders['ship_service_preview'] = $pedido['shipping']['shippingMethod'];
        }
        $orders['gross_amount']         		= $pedido['amount'] / 100;
        $orders['service_charge_rate']  		= $store['service_charge_value'];
		$orders['service_charge_freight_value'] = $store['service_charge_freight_value'];
        $orders['service_charge']       		= number_format(($orders['total_order'] * $store['service_charge_value'] / 100) + ($orders['total_ship'] * $store['service_charge_freight_value'] / 100), 2, '.', '');
        $orders['vat_charge_rate']      		= 0; //pegar na tabela de empresa - Não está sendo usado.....
        $orders['vat_charge']           		= number_format($orders['gross_amount'] * $orders['vat_charge_rate'] / 100, 2, '.', ''); //pegar na tabela de empresa - Não está sendo usado.....
        $orders['discount']             		= $totalDiscount;
        $orders['net_amount']           		= number_format($orders['gross_amount'] - $orders['discount'], 2, '.', '');

        $orders['paid_status']  				= $paid_status;
        $orders['company_id']   				= $company_id;
        $orders['store_id']     				= $store_id;
        $orders['origin']       				= $this->int_to;
        $orders['user_id']      				= 1;

        $order_id = $this->model_orders->insertOrder($orders);
        if (!$order_id)
            return [false, "506", "Não foi possível gravar o pedido ( {$order_mkt} )!"];

        // Itens
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

        foreach($pedido['items'] as $item) {

			$sku        = $item['sku'];
            $prf = $this->model_vs_last_post->getBySku($sku);
			
			$discount = 0;
			
            $prd = $this->model_products->getProductData(0,$prf['prd_id']);

            if (!$prd)
                return [false, 503, "Produto ( {$sku} ) não encontrado!"];

			if ($prd['is_kit'] ==0) {
	            $items = array();
	            $items['skumkt']        = $sku;
	            $items['order_id']      = $order_id; // ID da order incluida
	            $items['product_id']    = $prd['id'];
	            $items['sku']           = $prd['sku'];
	
	            $variant='';
				if (!is_null($prf['variant'])) {
					$variant=$prf['variant'];
				}
	            $items['variant']       = $variant;
	            $items['name']          = $prd['name'];
	            $items['qty']           = (int)$item['quantity'];
	            $items['rate']          = $item['costPrice'] / 100 - $discount;
	            $items['amount']        = (float) $items['rate'] * $item['quantity'];
	            $items['discount']      = $discount > 0 ? (float)number_format($discount, 2, '.', '') : $discount;
	            $items['company_id']    = $prd['company_id'];
	            $items['store_id']      = $prd['store_id'];
	            $items['un']            = 'un'; // Não tem
	            $items['pesobruto']     = $prd['peso_bruto'];  // Não tem 
	            $items['largura']       = $prd['largura']; // Não tem 
	            $items['altura']        = $prd['altura']; // Não tem 
	            $items['profundidade']  = $prd['profundidade']; // Não tem 
	            $items['unmedida']      = 'cm'; // não tem 
	            $items['kit_id']        = null;
	
	            $item_id = $this->model_orders->insertItem($items);
	            if (!$item_id)
	                return [false, "507", "Ocorreu um problema para gravar o item ( {$sku} )!"];
	
	            $this->model_products->reduzEstoque($prd['id'], $items['qty'], $variant, $order_id);
	            $this->model_vs_last_post->reduzEstoque($prf['int_to'], $prd['id'], $items['qty']);
	            $this->model_promotions->updatePromotionByStock($prd['id'], $items['qty'], $item['costPrice'] / 100);
            }
			else {
				$productsKit = $this->model_products->getProductsKit($prd['id']);
				foreach ($productsKit as $productKit){
					$prd_kit = $this->model_products->getProductData(0,$productKit['product_id_item']);
					$items = array();
					$items['order_id'] 		= $order_id; // ID da order incluida
					$items['skumkt'] 		= $sku;
					$items['kit_id'] 		= $productKit['product_id'];
					$items['product_id'] 	= $prd_kit['id'];
					$items['sku'] 			= $prd_kit['sku'];
					$variant 				= '';
					$items['variant'] 		= $variant;  // Kit não pega produtos com variantes
					$items['name'] 			= $prd_kit['name'];
					$items['qty'] 			= (int)$item['quantity'] * $productKit['qty'];
					$items['rate'] 			= $productKit['price'] ;  // pego o preço do KIT em vez do item
					$items['amount'] 		= ((float)$items['rate'] * $items['qty']) - $discount;
					$items['discount'] 		= $discount > 0 ? (float)number_format($discount, 2, '.', '') : $discount;// Tiro o desconto do primeiro item . 
					$discount 				= 0;
					$items['company_id'] 	= $prd_kit['company_id']; 
					$items['store_id'] 		= $prd_kit['store_id']; 
					$items['un'] 			= 'un';
					$items['pesobruto'] 	= $prd_kit['peso_bruto'];  // Não tem na SkyHub
					$items['largura'] 		= $prd_kit['largura']; // Não tem na SkyHub
					$items['altura'] 		= $prd_kit['altura']; // Não tem na SkyHub
					$items['profundidade'] 	= $prd_kit['profundidade']; // Não tem na SkyHub
					$items['unmedida'] 		= 'cm'; // não tem na skyhub
					//var_dump($items);
					$item_id = $this->model_orders->insertItem($items);
					if (!$item_id) {
						$this->log_data('api',$log_name,'Erro ao incluir item. pedido mkt = '.$pedido['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
						return [false, "508","Ocorreu um problema para gravar o item ( {$sku} )!"];
					}
					$itensIds[]= $item_id; 
					// Acerto o estoque do produto filho
					
					$this->model_products->reduzEstoque($prd_kit['id'],$items['qty'],$variant,$order_id);
					
				}
				 $this->model_vs_last_post->reduzEstoque($prf['int_to'], $prd['id'], $items['qty']);
					
			}

			//verificacao do frete
			if ($store['freight_seller'] == 1 && in_array($store['freight_seller_type'], [3, 4])) { // intelipost
				$todos_tipo_volume 	= false;
				$todos_correios 	= false;
				$todos_por_peso 	= false;
			}
			// acerto o registo para o calculo Frete
			$prf['altura'] 			= $prf['height'];
			$prf['largura'] 		= $prf['width'];
			$prf['profundidade'] 	= $prf['length'];
			$prf['peso_bruto'] 		= $prf['gross_weight'];
			$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 
			if ($todos_tipo_volume) { // se é tipo_volume não pode ser correios e não procisa consultar os correios	
				$todos_correios = false; 
			}
			else { // se não é tipo volumes, não precisa consultar o tipo_volumes pois já não achou antes 
				$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
			}
			$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
			$vl = Array ( 
				'tipo'				=> $prf['tipo_volume_codigo'],     
	            'sku' 				=> $sku,
	            'quantidade' 		=> $item['quantity'],	           
	            'altura' 			=> (float) $prf['height'] / 100,
			    'largura' 			=> (float) $prf['width'] /100,
			    'comprimento' 		=> (float) $prf['length'] /100,
			    'peso' 				=> (float) $prf['gross_weight'],
	            'valor' 			=> (float) ($item['costPrice'] / 100 * $item['quantity']),
	            'volumes_produto' 	=> 1,
	            'consolidar' 		=> false,
	            'sobreposto' 		=> false,
	            'tombar' 			=> false,
				'skuseller' 		=> $prf['sku']
			);
            $fr['volumes'][] = $vl;
		}

		$this->calculofrete->updateShipCompanyPreview($order_id);
		
        return [true, null, $order_mkt, $order_id];
    }

    private function formatDate($data) {
		$data = str_replace("T",' ',substr($data,0,19));
		return  DateTime::createFromFormat("Y-m-d H:i:s", $data)->format("Y-m-d H:i:s");
    }

}