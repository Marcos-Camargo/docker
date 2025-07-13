<?php

require APPPATH . "libraries/REST_Controller.php";

class ServicesOrder extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Endpoint destinado apenas para responder a VTEX quando realizamos a chama de cancelamento e recebimento de NFe.
     *
     * O processo é feito por uma batch, então só precisamos responder que recebemos a informação e processar ela em um segundo momento.
     */
    public function index_post($pub=null, $orders=null, $orderId=null, $type=null)
    {
        return $this->response(NULL, REST_Controller::HTTP_OK);
    }
}