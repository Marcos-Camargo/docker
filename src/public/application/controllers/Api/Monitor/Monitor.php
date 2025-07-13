<?php

/*
defined('BASEPATH') OR exit('No direct script access allowed');
*/   

/*
*  Classe para execução de testes de monitoração do Ambiente
*/	
require APPPATH . '/libraries/REST_Controller.php';
     
class Monitor extends REST_Controller {
    
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
     * Entrada das Chamadas
     *
     * @return Response
    */
	public function index_get($function = NULL, $param = NULL, $param2 = NULL, $param3 = NULL)
	{
		// Adicionar Aqui todos os testes 
		require_once dirname(__FILE__)."/Monitor_tests.php";
		
		//var_dump($function);
		//var_dump($param);
		if (is_callable($function)){
			$result = $function($this,$param,$param2, $param3);
		} else {
			$result = "MONIT_ERROR";
		}	
		echo $result;
	}


    public function index_post() {

	}	
	
}