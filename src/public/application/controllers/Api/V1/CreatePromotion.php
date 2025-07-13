<?php
/*
defined('BASEPATH') OR exit('No direct script access allowed');
 */
 
require APPPATH . '/libraries/REST_Controller.php'; 
     
class CreatePromotion extends REST_Controller {
    
	  /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function __construct() {
       parent::__construct();
	   $this->load->model('model_promotions');
    }
	
	private function convertDate($orgDate,$time) {
		$date =  str_replace('/','-', $orgDate);
		$newDate = date("Y-m-d", strtotime($date)); 
		if ($time == '') {
			return $newDate.' 00:00:00';
		}
		else {
			return $newDate.' '.$time.':00';
		}
	}
    /**
     * Post All Data from this method.
     *
     * @return Response
    */
    public function index_post()
    {
        
        $dataPost = file_get_contents("php://input");
		$this->log_data("api", "CreatePromotion", 'dados recebidos='.print_r(($dataPost),true),"I");
		$data = json_decode($dataPost,true);
		$this->log_data("api", "CreatePromotion", 'dados recebidos 2='.print_r($data,true),"I");
		$data = $this->cleanGet($data);

		header('Content-type: application/json'); 
		
		if (is_null($data)) {
			$this->log_data("api", "CreatePromotion", 'ERRO - Dados com formato errado recebidos='.$dataPost,"E");
			$this->response([
	            'message' => 'Dados fora do formato Json.'
	            ], REST_Controller::HTTP_BAD_REQUEST);
			die; 
		} 
		
		if ($data == '') {
			$ret =array(
			'sucess' => true, 
			'message' => 'Problema'
			);
        	$this->response($ret, REST_Controller::HTTP_OK);
		}
		$start_date = $this->convertDate($data['start_date'],$data['start_time']);  
		$end_date = $this->convertDate($data['end_date'],$data['end_time']);  
		
		if ($data['type']) {
			$qty = null;
			$qty_used = null;
		}
		else {
			$qty = $data['qty'];
			$qty_used = 0;
		}
		$rec = array (
			'product_id' => $data['product_id'],
			'active' => 3,
			'type' => $data['type'] + 1 ,
			'qty' => $qty,
			'qty_used' => $qty_used,
			'price' => $data['price'],
			'start_date' => $start_date,
			'end_date' => $end_date,
		    'store_id' =>  $data['store_id'],
			'company_id' =>  $data['company_id'],
		);
		$id = $this->model_promotions->create($rec);
		$ret =array(
		'sucess' => true, 
		'message' => 'Promotion Cretaed',
		'id' => $id );
        $this->response($ret, REST_Controller::HTTP_OK);
    } 
	
}