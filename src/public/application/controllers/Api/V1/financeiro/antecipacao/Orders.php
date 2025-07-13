<?php 

require APPPATH . "controllers/Api/V1/API.php";

class Orders extends API
{

  public function __construct()
	{
		parent::__construct();
    
    $this->load->model('model_orders');
    $this->max_per_page = 10;						

	}

  /**
   * Return an object of Store
   *
   * @return mixed
   */
  public function index_get()
  {
       
    $this->header = array_change_key_case(getallheaders());
    $check_auth   = $this->checkAuth($this->header);

    if(!$check_auth[0]) {
      return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
    }
             
    $search = $this->cleanGet($this->input->get());

    $store_id = null;

    if(!empty($search['store_id'])){
        $store_id = (int) $search['store_id'];
    }

    $page       = $search['page'] ?? 1;
    $per_page   = $search['per_page'] ?? $this->max_per_page;
    $page       = filter_var($page, FILTER_VALIDATE_INT);
    $per_page   = filter_var($per_page, FILTER_VALIDATE_INT);
    
    if ($page <= 0){
      $page = 1;
    }
    if ($per_page <= 0){
      $per_page = 1;
    }
    if ($per_page > $this->max_per_page) {
      $per_page = $this->max_per_page;
    }

    $page--;
    $page_per_page = $page * $per_page;
    $limit = "LIMIT {$per_page} OFFSET {$page_per_page}";

    $start = $search['start'] ?? null;
    $end   = $search['end'] ?? null;

    $date_query = "";

    if($start && $end){
      $date_query = " AND date_time BETWEEN '$start' AND '$end' ";
    }    
    if(!$start && $end){
      $date_query = " AND date_time < '$end' ";
    }
    if($start && !$end){
      $date_query = " AND date_time > '$start' ";
    }

    $orders = $this->model_orders->getAnticipationTransferOrders($store_id, $limit, $date_query);
    if($orders)
    {      
      return $this->response(array('success' => true, 'result' => $orders), REST_Controller::HTTP_OK);
    }

    return $this->response(array('success' => false, 'result' => []), REST_Controller::HTTP_OK);

  }

  /**
     * Post All Orders to Anticipate.
     *
     * @return void
     */
    public function index_post()
    {
       
        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);
    
        if(!$check_auth[0]) {
          return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));
               
        if(!isset($data['pedidos']) || !is_array($data['pedidos'])){
          return $this->response($this->returnError('Erro ao identificar os pedidos.'), REST_Controller::HTTP_NOT_FOUND);
        }
        
        if(count($data['pedidos']) == 0){
          return $this->response($this->returnError('Não encontramos pedidos para antecipação.'), REST_Controller::HTTP_NOT_FOUND);
        }

        $pedidos = $data['pedidos'];

        $errors = [];
        foreach($pedidos as $pedido){
          $check_order = $this->model_orders->checkOrderToAnticipation($pedido);          
          if(!$check_order['status']){                          
            array_push($errors, $check_order['errors']);                      
          }
        }
        if(count($errors) > 0){ 
          $message = [
            "success" => false,
            "message" => "Não foi possível antecipar alguns pedidos enviados. Corrija os erros abaixo e tente novamente.",
            "errors" => $errors
          ];                  
          $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, json_encode($message), "E");
          return $this->response(json_decode(json_encode($message)), REST_Controller::HTTP_OK);
        }else{
          // $this->log_data('api',  __CLASS__ . "/" . __FUNCTION__, "Pedidos [".implode(',', array_map('intval', $pedidos))."] antecipados com sucesso.", "I");  
          $this->log_data('api',__CLASS__ . "/" . __FUNCTION__," Host: ".$this->header["host"]." E-mail:".$this->header["x-email"]."- Pedidos antecipados: " . json_encode($data),"I");   
          $this->model_orders->saveOrderAnticipated($pedidos);                        
          return $this->response(array('success' => true, 'result' => 'Pedidos antecipados com sucesso!'), REST_Controller::HTTP_OK);
        }

       
    }


}