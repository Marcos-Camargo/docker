<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

class DeletePublication extends MainController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index_delete()
    {
        $this->response('Recurso não implementado', self::HTTP_NOT_IMPLEMENTED);
    }
}