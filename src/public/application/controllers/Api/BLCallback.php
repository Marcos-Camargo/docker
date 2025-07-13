<?php
/*
SW Serviços de Informática 2019

Recebe atualizações vindas do BLING

*/   

require APPPATH . '/libraries/REST_Controller.php';
     
class BLCallback extends REST_Controller {
    
	  /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function __construct() {
       parent::__construct();
    }

    /**
     * Get All Data from this method.
     *
     * @return Response
    */
	public function index_get($id = NULL)
	{
       	$error = "Wrong Key";
		return $this->response(array('success' => false, 'message' => $error), REST_Controller::HTTP_UNAUTHORIZED);
	}

    public function index_post() {
		// $data = json_decode(file_get_contents("php://input"));
		
		//var_dump($_GET);
		$params = $_GET;
		if (!array_key_exists('apikey', $params)) {
			$error =  "No parameters";
			return $this->response(array('success' => false, 'message' => $error), REST_Controller::HTTP_UNAUTHORIZED);
		} 
		if ($params['apikey']!='jfkjvhkfkfhvjkfshvk') {
			$error = "Wrong Key";
			return $this->response(array('success' => false, 'message' => $error), REST_Controller::HTTP_UNAUTHORIZED);
		}
		
		$data = file_get_contents("php://input");

		$this->log_data('api','BLCallback',"Dados Recebidos=".$data,"I");
				
		$data = substr($data,5);
		$pedidos = json_decode($data, true);
		$pedidos = $pedidos['retorno']['pedidos'];
		
		$this->load->model('model_orders','myorders');
		$this->load->model('model_products','myproducts');
		$this->load->model('model_company','mycompany');
		$this->load->model('model_clients','myclients');
		$this->load->model('model_integrations','myintegrations');

		foreach($pedidos as $pedido) {
			$pedido = $pedido['pedido'];
			// var_dump($pedido);

			$order = $this->myorders->getOrdersDatabyNumeroMarketplace($pedido['numeroPedidoLoja']);
			if (!$order) {
				$this->log_data('api','BLCallback','Pedido Numero_MarketPlace '.$pedido['numeroPedidoLoja'].' não encontrado',"E");
				continue;
			}

			$bst = $pedido['situacao'];
			switch ($bst) {
			    case "Em aberto":
			        $this->log_data('api','BLCallback','Pedido '.$order['id'].' alteração de situação no Bling para '.$pedido['situacao'].' ignorado!',"W");
			        break;
			    case "Atendido":
					$this->log_data('api','BLCallback','Pedido '.$order['id'].' alteração de situação no Bling para '.$pedido['situacao'].' ignorado!',"W");
			        break;
			    case "Cancelado":
			    	if (($order['paid_status'] != '99')  && ($order['paid_status'] != '98') && ($order['paid_status'] != '97')) {
			    		$itens = $this->myorders->getOrdersItemData($order['id']);
						foreach ($itens as $item) {
							$this->myproducts->adicionaEstoque($item['product_id'],$item['qty'],$item['variant']);
						}
			        	$data = array(
			        		'order_id' => $order['id'],
			        		'motivo_cancelamento' => 'Cancelado no Bling/Marketplace',
			        		'data' => date("Y-m-d H:i:s"),	
			                'status' => '1',
			        		'user_id' => '1'	
			        	);
						$this->myorders->insertPedidosCancelados($data);
						// agora tem que cancelar no Frete Rápido.
				        $resp = $this->myorders->updatePaidStatus($order['id'],'98');
				        $this->log_data('api','BLCallback','Pedido '.$order['id'].' CANCELADO ',"W");
			    	} else {
			    		$this->log_data('api','BLCallback','Pedido '.$order['id'].' já cancelado anteriormente',"I");
			    	}
			    	 break;
			    case "Em andamento":
			        $this->log_data('api','BLCallback','Pedido '.$order['id'].' alteração de situação no Bling para '.$pedido['situacao'].' ignorado!',"W");
			        break;
			    default:
			        $this->log_data('api','BLCallback','Pedido '.$order['id'].' alteração de situação no Bling para '.$pedido['situacao'].' ignorado!',"W");
			        break;
			}

		}
        return $this->response(true, REST_Controller::HTTP_OK);

    }

    public function index_options() {
        return $this->response("OPTIONS", REST_Controller::HTTP_OK);
    }

}