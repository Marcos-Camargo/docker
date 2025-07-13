<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";

class configpricerefresh extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_settings');
        $this->load->library('rest_request');
        $this->app_id_anymarket = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        if (!$this->app_id_anymarket) {
            die("appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.");
        }
        $this->load->model('model_settings');
        $this->url_anymarket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymarket) {
            throw new Exception("\'url_anymarket\' não está definido no sistema");
        }
    }
	
	public function __destruct() {
		parent::__destruct();
        // deleta o cookie
		unlink($this->rest_request->cookiejar);
    }
    
    public function index_post()
    {
        $dados = $this->input->post();
        $dados['token'] = $dados['token2'];
        $this->input = $dados;
        if (!$this->checkToken()) {
            $this->load->view('any_market/unauthorized');
            return;
        }
        $anymarket_token = $dados['token'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $log_data = [
            'endpoint' => 'CanSave',
            'body_received' => json_encode($dados),
            'store_id' => $integration['store_id'],
        ];
        $this->model_anymarket_log->create($log_data);
        $credentials = json_decode($integration['credentials'], true);
        $headers = array(
            "Content-Type: application/json",
            "appId: {$this->app_id_anymarket}",
            "token: {$credentials['token_anymarket']}",
        );
        $url_confirm = $this->url_anymarket . "delegates/forceSyncMarkup";
        $res = $this->rest_request->sendREST($url_confirm, [], 'PUT', $headers);
        if ($res['httpcode'] != 200 && (strpos($res['content'], '422') !== false)) {
            $lastTimeMarkup = new DateTime($credentials['lastTimeMarkup'] ?? date('Y-m-d H:i:s'));
            $currentTime = new DateTime('now');
            $interval = $lastTimeMarkup->diff($currentTime);
            $details = $interval->format("É necessário aguardar %H hora(s) e %i minuto(s) para iniciar uma nova execução.");
            $content = json_decode($res['content'], true);
            $content['details'] = $details;
            $res['content'] = json_encode($content);
        } else if ($res['httpcode'] == 200) {
            $credentials['lastTimeMarkup'] = date('Y-m-d H:i:s');
            $integration = $this->model_api_integrations->update($integration['id'], [
                'credentials' => json_encode($credentials)
            ]);
        }
        echo json_encode($res);
    }
    public function checkToken()
    {
        $anymarket_token = $this->input['token'];
        if (empty($anymarket_token)) {
            return false;
        }
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        if (!isset($payload->oi)) {
            return false;
        }
        return true;
    }
}
