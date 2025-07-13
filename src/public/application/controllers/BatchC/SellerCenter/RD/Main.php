<?php

class Main extends BatchBackground_Controller
{
    public $result;
    public $responseCode;
    public $errorMensagem;
    protected $accountName;
    protected $header;
    protected $suffixDns;

    public function __construct()
    {
        parent::__construct();
        $this->suffixDns = '.com.br';
        $this->load->model('model_integrations');
        $this->load->model('model_stores');
        $this->load->model('model_log_integration_product_marketplace');
        $this->load->model('model_settings');
    }

    protected function auth($api_url, $grant_type, $client_id, $client_secret, $scope = 'read')
    {
        $this->header = [
            'content-type: application/json'
        ];

        $data = [
            'grant_type' => $grant_type,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => $scope
        ];

        $url = $api_url . '/oauth2/token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $this->result = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
        $this->result = json_decode($this->result);
        return $this->result;
    }

    protected function process($integration_auth_data, $auth_data_token, $endPoint, $method = 'GET', $data = null, $integration_id = null, $scope = 'read')
    {
        $this->header = [
            'content-type: application/json',
            'Authorization: '. $auth_data_token->token_type .' '. $auth_data_token->access_token,
        ];
        
        echo "Token: ". $auth_data_token->token_type .' '. $auth_data_token->access_token . PHP_EOL;

        $url = $integration_auth_data->api_url . $endPoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if (($method == 'PUT') || ($method == 'PATCH')) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if (($method == 'DELETE')) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $this->result           = curl_exec($ch);
        $this->responseCode     = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->errorMensagem    = curl_error( $ch );

        curl_close($ch);
        if (($this->responseCode == 429) || ($this->responseCode == 504)) {
            echo "site " . $url . " deu " . $this->responseCode . " dormindo 50 segundos\n";
            sleep(60);
            return $this->process($this->result, $endPoint, $method, $data, $integration_id);
        }
        return true;
    }
}
