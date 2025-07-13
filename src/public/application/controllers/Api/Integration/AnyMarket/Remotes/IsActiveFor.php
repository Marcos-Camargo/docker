<?php

use Firebase\JWT\JWT;
use Lcobucci\JWT\Signer\Rsa;

// require_once APPPATH . "libraries/REST_Controller.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";
class IsActiveFor  extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
    }
    public function index_get(){
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $anymarket_token=$_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload=explode('.',$anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration=$this->model_api_integrations->getUserByOI($payload->oi);
        if($integration){
            echo 'true';
        }else{
            echo 'false';
        }
        // dd($integration);
    }
}
