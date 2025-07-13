<?php

require APPPATH . "libraries/REST_Controller.php";

class ControlProduct extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        header('Integration: v2');
    }

    /**
     * Atualização de estoque, receber via POST
     */
    public function index_post()
    {
        return $this->response(null, REST_Controller::HTTP_NO_CONTENT);
    }
}