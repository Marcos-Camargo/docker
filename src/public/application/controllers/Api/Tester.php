<?php
/*
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Apiitem extends Admin_Controller  
{
*/   

require APPPATH . '/libraries/REST_Controller.php';
     
class Tester extends REST_Controller {
    
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
	public function index_get($id_loja = NULL)
	{
        if(!empty($id_loja)){
			$more = 'categoriasLoja/' . $id_loja . '/';
        }else{
	        return $this->response("MISSING OPTIONS", REST_Controller::HTTP_OK);
        }
		$apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$outputType = "json";
		$url = 'https://bling.com.br/Api/v2/'. $more . $outputType;
		$retorno = $this->executeGetCategories($url, $apikey);
        return $this->response(print_r(json_decode($retorno,true)), REST_Controller::HTTP_OK);
	}



    public function index_post($param1 = NULL, $param2 = NULL) {
		var_dump($param1);
		var_dump($param2);

    }

    public function index_options() {
        return $this->response("OPTIONS", REST_Controller::HTTP_OK);
    }

	function executeGetCategories($url, $apikey){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}



}