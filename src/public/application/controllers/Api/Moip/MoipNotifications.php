<?php

require APPPATH . "controllers/Api/V1/API.php";

class MoipNotifications extends API
{
    // public $job;
    // public $unique_id = null;
    // public $apiKey;
    // public $token;
    // public $store;
    // public $company;
    // public $multiStore;
    // public $formatReturn = "json";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_moip');
    }


    //braun -> endpoint para o moip enviar informacoes, dependendo do uso das apis que fazem isso.
    
    public function index_post()
    {
        $api_array = json_decode(file_get_contents('php://input'), true);

        if(isset($api_array['event']))
        {
            $event = explode('.', $api_array['event']);

            $api_data = $api_array['resource']['multiorder'];

            if ($event[0] == 'MULTIORDER')
            {
                if ($event[1] == 'PAID')
                {
                    $payment_data = array
                    (
                        'multiorder_tbl_id'  => $api_data['orders'][0]['ownId'],
                        'total_paid'         => $api_data['amount']['total'],
                        'order_paid'         => $api_data['id'],
                        'created_at'         => $api_data['createdAt'],
                        'funding_instrument' => $api_data['multiPayment'][0]['payments'][0]['fundingInstrument']['method'],
                    );
                }
                else if ($event[1] == 'NOT_PAID')
                {
                    
                }
            }
           
        }





        // $api_array = json_decode($api_json, true);
        // $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        

        //$this->log_data('WebHook', 'WebHookUpdateNfe', 'Novo registro para atualização de estoque', "I");
        // if (!isset($_GET['apiKey'])) {
        //     $this->log_data('WebHook', 'WebHookUpdateNfe - Valid', 'Não foi encontrado a parâmetro apiKey, GETs='.json_encode($_GET), "E");
        //     $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        //     return false;
        // }

    }

}