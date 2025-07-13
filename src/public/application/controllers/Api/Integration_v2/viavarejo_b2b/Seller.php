<?php

require_once APPPATH . "libraries/Integration_v2/Integration_v2.php";
require_once APPPATH . "controllers/Api/Integration_v2/viavarejo_b2b/ViaHttpController.php";

class Seller extends ViaHttpController
{

    protected function handlePostRequest()
    {
    }

    protected function buildToolsClass():\Integration\Integration_v2
    {
        return new \Integration\Integration_v2();
    }
}