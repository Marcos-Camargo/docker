<?php
require APPPATH . "controllers/Api/V1/API.php";

class PartialShipping extends API
{
    public function index_post()
    {
        return [
            'success' => false,
            'message' => 'Partial shipping update not implemented'
        ];
    }
}
