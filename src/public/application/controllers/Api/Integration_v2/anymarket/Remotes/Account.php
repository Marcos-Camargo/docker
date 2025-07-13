<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

class Account extends MainController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index_get()
    {
        $data = [
            'id' => $this->accountIntegration['user_id'],
            'name' => strtoupper($this->accountIntegration['store_name'])
        ];
        $this->response($data, REST_Controller::HTTP_OK);
    }

    public function active_get()
    {
        $response = [];
        foreach ([$this->accountIntegration] as $integration) {
            array_push($response, [
                'id' => $integration['user_id'],
                'name' => strtoupper($integration['store_name'])
            ]);
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function default_get()
    {
        $data = [
            'id' => $this->accountIntegration['user_id'],
            'name' => strtoupper($this->accountIntegration['store_name'])
        ];
        $this->response($data, REST_Controller::HTTP_OK);
    }

    public function isActiveFor_get()
    {
        if (empty($this->accountIntegration)) {
            echo 'false';
        }
        echo 'true';
    }
}
