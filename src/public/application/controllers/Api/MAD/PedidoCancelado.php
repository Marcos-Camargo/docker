<?php

require APPPATH . "/libraries/REST_Controller.php";

class PedidoCancelado extends REST_Controller {
    
	var $int_to; 

    public function __construct() {
       parent::__construct();
	   $this->load->model('model_orders');
	   $this->load->model('model_integrations');
	   $this->load->library('ordersMarketplace');
	   
    }

	public function index_get() 
	{
	 	echo "Não implementado\n";
	 	die;
	}
    
    public function index_post()
    {
		$this->int_to = 'MAD'; 
		
		$dataPost = file_get_contents('php://input');
		$data = json_decode($dataPost,true);
		if (is_null($data)) {
			$this->log_data('api', 'MAD_PedidoCancelado', 'ERRO - Dados com formato errado recebidos do Madeira Madeira='.$dataPost,'E');
			$this->response([
	            'message' => 'Dados fora do formato Json.'
	            ], REST_Controller::HTTP_BAD_REQUEST);
			die; 
		} 

		/*
		 * 
		{
			"id_seller": 225,
			"order": "2371",
			"status": 1,
			"time": 1532567027
		}
		 */
		 
		$this->log_data('api', 'MAD_PedidoCancelado', 'Dados Recebidos:'.print_r($dataPost,true),'I');
		
		 if (!isset($data['id_seller']) || empty($data['id_seller']))
            return $this->returnError('Sem o parametro id_seller. Recebido: '.print_r($dataPost,true));
		 
		  if (!isset($data['order']) || empty($data['order']))
            return $this->returnError('Sem o parametro order. Recebido: '.print_r($dataPost,true));
		
		// Vejo se Hub do madeira madeira pelo id_seller
		$integration = $this->model_integrations->getIntegrationByIntTo($this->int_to, 0);
		if (!$integration) {
			return $this->returnError('Sistema ainda não integrado ao Madeira Madeira');
		}
		$auth_data = json_decode($integration['auth_data']); 
		if ((int)$auth_data->id_seller != (int)$data['id_seller']) {
			$this->int_to='H_MAD'; // é hub e não a integração do Conectala
		}
		
		// procuro o pedido 
		$order = $this->model_orders->getOrdersDatabyBill($this->int_to,$data['order']);
		 
		if ($order) {
			$this->ordersmarketplace->cancelOrder($order['id'], false);
			$this->log_data('api', 'MAD_PedidoCancelado', 'Pedido '.$order['id'].' Numero Marketplace '.$data['order'].' cancelado pelo Madeira Madeira','I');
		}
		else {
			// Possivelmente o pedido já nasceu cancelado....
			$message = 'Pedido Numero Marketplace '.$data['order'].' não encontrado vindo do Madeira Madeira';
			$this->log_data('api', 'MAD_PedidoCancelado',$message ,'E');
		}
		$this->response(null, REST_Controller::HTTP_OK);	
	
    } 

	private function returnError(string $message)
    {
        $this->log_data('api', 'MAD_PedidoCancelado',$message ,'E');
		// ob_clean();
		echo $message;
        return $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST);
	}
}