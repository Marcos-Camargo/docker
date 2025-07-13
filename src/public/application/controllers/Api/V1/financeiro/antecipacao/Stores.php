<?php 

require APPPATH . "controllers/Api/V1/API.php";

class Stores extends API
{

  public function __construct()
	{
		parent::__construct();
    
    $this->load->model('model_stores');						

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
             
    $search = $this->cleanGet();

    $store = $this->model_stores->getStoresAntecipacao($search);
    if($store)
    {      
      return $this->response(array('success' => true, 'result' => $store), REST_Controller::HTTP_OK);
    }

    return $this->response(array('success' => false, 'result' => []), REST_Controller::HTTP_OK);

  }


}