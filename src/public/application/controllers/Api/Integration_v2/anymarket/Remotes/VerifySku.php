<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

class VerifySku extends MainController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index_get($idSku)
    {
        $this->response('Recurso não implementado', self::HTTP_NOT_IMPLEMENTED);
    }
}