<?php

require_once APPPATH . "libraries/Integration_v2/Integration_v2.php";
require_once APPPATH . "controllers/Api/Integration_v2/viavarejo_b2b/ViaHttpController.php";

class Category extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index_post()
    {
        $this->response(
            [
                'IsValid' => true,
                'StatusCode' => REST_Controller::HTTP_OK,
                'Messages' => []
            ], REST_Controller::HTTP_OK);
    }

    protected function buildToolsClass(): \Integration\Integration_v2
    {
        return new \Integration\Integration_v2();
    }

    protected function handlePostRequest()
    {
    }
}