<?php

require APPPATH . "/libraries/REST_Controller.php";

class ProdutoAprovado extends REST_Controller {
    
 	var $int_to; 
	
    public function __construct() {
       parent::__construct();
	   $this->load->model('model_integrations');
	   $this->load->model('model_queue_products_marketplace');
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
			$this->log_data('api', 'MAD_ProdutoAprovado', 'ERRO - Dados com formato errado recebidos do Madeira Madeira='.$dataPost,'E');
			$this->response([
	            'message' => 'Dados fora do formato Json.'
	            ], REST_Controller::HTTP_BAD_REQUEST);
			die; 
		} 

		/*
		 * 
		 {
		 	"id_seller" : nnn,
		 	"sku" : "skuenviado",
		    "aprovado" : 1 
		 }
		 */
		
		$this->log_data('api', 'MAD_ProdutoAprovado', 'Dados Recebidos:'.print_r($dataPost,true),'I');
		
		if (!isset($data['id_seller']) || empty($data['id_seller']))
            return $this->returnError('Sem o parametro id_seller. Recebido: '.print_r($dataPost,true));
		 
		  if (!isset($data['sku']) || empty($data['sku']))
            return $this->returnError('Sem o parametro sku. Recebido: '.print_r($dataPost,true));
            
		// Vejo se Hub do madeira madeira pelo id_seller
		$integration = $this->model_integrations->getIntegrationByIntTo($this->int_to, 0);
		if (!$integration) {
			return $this->returnError('Sistema ainda não integrado ao Madeira Madeira');
		}
		$auth_data = json_decode($integration['auth_data']); 
		if ((int)$auth_data->id_seller != (int)$data['id_seller']) {
			$this->int_to='H_MAD'; // é hub e não a integração do Conectala
		}
		
		// Procuro o produto
		$prd_int = $this->model_integrations->getPrdToIntegrationBySkyblingAndIntto($data['sku'],$this->int_to); 
		if ($prd_int) {
			// coloco na fila para ser processado 
			$queue = array (
				'id' 		=> 0, 
				'status' 	=> 0,
				'prd_id' 	=> $prd_int['prd_id'],
				'int_to' 	=> $this->int_to
			);
			$this->model_queue_products_marketplace->create($queue);
		}
		else {
			return $this->returnError('SKU '.$data['sku'].' não encontrado no sistema vindo do Madeira Madeira');
		}
		$this->response(null, REST_Controller::HTTP_OK);	
	
    } 

	private function returnError(string $message)
    {
        $this->log_data('api', 'MAD_ProdutoAprovado',$message ,'E');
		//ob_clean();
		echo $message;
        return $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST);
	}

}