<?php

require APPPATH . "/libraries/REST_Controller.php";

class NotificationsML extends REST_Controller {
    
	  /**
     * Get All Data from this method.
     *
     * @return Response
    */   
    public function __construct() {
       parent::__construct();
    }

    /**
     * Post All Data from this method.
     *
     * @return Response
    */
   public function index_get() 
	 {
	 	echo "morri\n";
	 	die;
	 }
    
    public function index_post()
    {
    	$inicio = microtime(true);
		
       // $data = json_decode(file_get_contents('php://input'), true);
		$dataPost = file_get_contents('php://input');
		$data = json_decode($dataPost,true);
		if (is_null($data)) {
			$this->log_data('api', 'NotificationsML', 'ERRO - Dados com formato errado recebidos do Mercado livre='.$dataPost,'E');
			$this->response([
	            'message' => 'Dados fora do formato Json.'
	            ], REST_Controller::HTTP_BAD_REQUEST);
			die; 
		} 
		
		$data_notification = array (
			'notification' => json_encode(json_decode($dataPost,true)), 
			'company_id' => 1,
			'store_id' =>0,	
		);
		$create = $this->db->insert('mercado_livre_notifications', $data_notification);
		
		$this->response(null, REST_Controller::HTTP_OK);
		
	
    } 

}