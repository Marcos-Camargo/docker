<?php

require APPPATH . "controllers/Api/FreteConectala.php";

class OrderStatus extends FreteConectala {

    private $int_to;
	
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_stores');
        $this->load->model('model_orders');
        $this->load->model('model_clients');
        $this->load->model('model_products');
        $this->load->model('model_promotions');
        $this->load->model('model_vs_last_post');
        $this->load->model('model_freights');
        $this->load->model('model_integrations');
		$this->load->model('model_orders_item');
        $this->load->library('CalculoFrete');
        $this->load->library('ordersMarketplace'); 
		$this->load->model('model_providers');   
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
		
		$supplier_id = xssClean($supplier_id);
		if (is_null($supplier_id)) {
			$error =  "No supplier id";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die; 
		} 

		// $dataget = $this->input->get();

        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));
       // $this->log_data('api', 'Orders - POST', json_encode($data));

       if (is_null($data)) {
			return $this->returnError(600, 'Dados com formato errado '.print_r(file_get_contents('php://input'),true));
		}

		/*
		if (!isset($data['dynamicData']) || !is_array($data['dynamicData']) || !count($data['dynamicData'])) {
			return $this->returnError(601, 'Sem o parametro dynamicData. Recebido: '.print_r($data,true));
		}

		if (!array_key_exists ('supplierId', $data['dynamicData'])) {
			return $this->returnError(602, 'Sem o parametro supplierId ou inválido. Recebido: '.print_r($data,true));
		}
       	$supplier_id = $data['dynamicData']['supplierId'] ; 
		*/
		
		$store = $this->model_integrations->getStoreByMKTSeller($this->int_to, $supplier_id);
		
		if (!$store) {
			return $this->returnError(603,'suplier id inexistente');
		} 
		
		if (!$this->verifyHeaders(getallheaders(), $store)) {
			$error =  "No authentication key or invalid";
		 	show_error( 'Unauthorized', REST_Controller::HTTP_UNAUTHORIZED,$error);
			die; 
		}
		
     	if (!array_key_exists ('vendorOrderId', $data)) {
			return $this->returnError(604,'Sem o parametro vendorOrderId');
		}
           
        // get Order _id
        $order_id = filter_var($data['vendorOrderId'], FILTER_VALIDATE_INT);
        // $this->log_data('api', 'CheckoutVS - Return', 'received='.json_encode($data).' return='.json_encode($return));
        
        /* 
		 * https://partnerhubapimqa.developer.azure-api.net/tracking
		 */
		
		// get order
		$order = $this->model_orders->getOrdersData(0, $order_id);
		if (!$order) {
			return $this->returnError(605,"Pedido {$order_id} não encontrado");
		}
		if ($order['store_id'] != $store['id']) {
			return $this->returnError(606,"Pedido {$order_id} não encontrado para estes suplier");
		}
		$nfe = null;
		$nfes = $this->model_orders->getOrdersNfes($order['id']);
		if (count($nfes) != 0) {
			$nfe = $nfes[0]; 
		}
		//pego o frete se existir 
		$carrier_url ='';
		$estimateDate = '';
		$frete = null;
		$fretes=$this->model_freights->getFreightsDataByOrderId($order['id']);
		if (count($fretes)!=0) {
			$frete = $fretes[0];
			if (!is_null($frete['url_tracking'])) { // Precode manda
				$carrier_url= $frete['url_tracking'];
			}
			else {
				$carrier_url = 'https://www2.correios.com.br/sistemas/rastreamento/';				
				$transportadora = $this->model_providers->getProviderDataForCnpj($frete['CNPJ']);
				if ($transportadora) {
					if (!is_null($transportadora['tracking_web_site'])) {
						$carrier_url = $transportadora['tracking_web_site'];
					}
				}
				$carrier_url .= $carrier_url.'?rastreio='.$frete['codigo_rastreio'];

				$send_track_code = $this->model_settings->getValueIfAtiveByName('send_tracking_code_to_mkt');
				if ($send_track_code === false) {
					$send_track_code = 0;
				}

				$sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
				if (
					(!empty($sellercenter) && ($sellercenter == 'conectala')) ||
					(!empty($sellercenter) && ($sellercenter != 'conectala') && ($send_track_code == 1))
				) {
					$carrier_url = base_url('rastreio');
				}
			}
			if (!empty($frete['prazoprevisto'])) {
				$estimateDate = $this->formatDate($frete['prazoprevisto'].' 18:00:00');
			}
		}
		
		if ($estimateDate=='') {
			$estimateDate = $this->formatDate($this->somar_dias_uteis($order['data_limite_cross_docking'], 5).' 18:00:00');
		}
		
		$trackingHistory = array();
		
/*  
§          "Produto Solicitado" = 1,
§          "Produto confirmado no parceiro" = 2,
§          "Produto enviado para a transportadora" = 3,
§          "Produto em rota de entrega" = 4,
§          "Produto entregue" = 5,
§          "Produto cancelado" = 6,
§          "Produto com dificuldade de entrega" = 7,
§          "Produto em coleta" = 8,
§          "Produto coletado" = 9,
§          "Produto não pode ser coletado" = 10
*/
		
		$trackingHistory[] = Array(
				'statusCode' => '1',
				'statusName' => 'Produto Solicitado',
				'processDate' => $this->formatDate($order['date_time'])
			);
		//var_dump($trackingHistory);
		
		if (!is_null($nfe)) {
			if (!is_null($nfe['date_emission'])) {
				$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission']);
				if ($issue_date_o == false) {
					$issue_date_o = DateTime::createFromFormat('d/m/Y H:i:s',$nfe['date_emission'].' 23:00:00');
					if ($issue_date_o == false) {
						$issue_date_o = DateTime::createFromFormat('Y-m-d H:i:s',$nfe['date_emission']);
					}
				}
				if ($issue_date_o > (new DateTime)) { // ve se a data passou da hora de agora.
					$issue_date_o = new DateTime;
				} 
				$trackingHistory[] = Array(
					'statusCode' => '2',
					'statusName' => 'Produto confirmado no parceiro',
					'processDate' => $this->formatDate($issue_date_o->format('Y-m-d H:i:s'))
				);
			}
		}
		//esse status é usado pela vertem para devolução
		// if (!is_null($frete)) {
		// 	$trackingHistory[] = Array(
		// 		'statusCode' => '8',
		// 		'statusName' => 'Produto em coleta',
		// 		'processDate' => $this->formatDate($frete['data_etiqueta'])
		// 	);
		// }
		if (!is_null($order['data_envio'])) {
			$trackingHistory[] = Array(
				'statusCode' => '3',
				'statusName' => 'Produto enviado para a transportadora',
				'processDate' => $this->formatDate($order['data_envio'])
			);
		}
		if (!is_null($order['data_entrega'])) {
			$trackingHistory[] = Array(
				'statusCode' => '5',
				'statusName' => 'Produto entregue',
				'processDate' => $this->formatDate($order['data_entrega'])
			);
		}
		
		if (($order['paid_status'] >= 95) && ($order['paid_status'] <= 99)) { // pedido cancelado
			$trackingHistory[] = Array(
				'statusCode' => '6',
				'statusName' => 'Produto cancelado',
				'processDate' => $this->formatDate($order['date_cancel'])
			);
		}
		
    	$items = $this->model_orders_item->getItensByOrderId($order['id']);
		$trackingProducts = array();
		
		foreach ($items as $item) {
			if (!is_null($item['kit_id'])) {
				$trackingProducts[] = array (
					'sku'					=> $item['skumkt'],
			      	'estimatedDeliveryDate'	=> $estimateDate,
			      	'urlTracking'			=> $carrier_url,
			      	'trackingHistory' 		=> $trackingHistory
				);
			} 
			else {
				if (!is_null($item['original_qty'])) {
					$trackingProducts[] = array (
						'sku'					=> $item['skumkt'],
			      		'estimatedDeliveryDate'	=> $estimateDate,
			      		'urlTracking'			=> $carrier_url,
			      		'trackingHistory' 		=> $trackingHistory
					);
				}
			}
		}
		$return = array ( 'trackingProducts' => $trackingProducts ); 
		 
        $this->response($return, REST_Controller::HTTP_OK);
        
        return; 
    }

	private function returnError($code, $message) 
	{
		$response = array(
            'error'             =>  array (
				'code'			=> $code,
				'message'		=> $message
			),
        );
		$this->response($response, REST_Controller::HTTP_OK);	
		return; 
	}

    private function formatResponseOrder($order_id)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

    }

    private function formatDate($data) {
		return  DateTime::createFromFormat("Y-m-d H:i:s", $data)->format("Y-m-d\TH:i:s.000")."Z";
    }

}
