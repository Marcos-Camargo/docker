<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

class IsActiveFor extends MainController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index_get()
    {
        if (empty($this->accountIntegration)) {
            echo 'false';
            return;
        }
        echo 'true';
    }
}
