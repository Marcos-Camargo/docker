<?php

use Firebase\JWT\JWT;

require_once APPPATH . "libraries/REST_Controller.php";

/**
 * Class MainController
 * @package controllers\Api\Integration_v2\anymarket
 * @property \Model_api_integrations $model_api_integrations
 * @property \Model_settings $model_settings
 */
class MainController  extends REST_Controller
{
    public $data;

    protected $sellercenterName;

    protected $accountIntegration = [];

    public function __construct()
    {
        parent::__construct();
        ini_set('display_errors', 0);
        $this->load->model('model_api_integrations');
        header('Content-Type: application/json');
        header('Integration: v2');


        $this->load->model('model_settings');
        $this->sellercenterName = $this->model_settings->getValueIfAtiveByName('sellercenter_name') ?? "Conecta Lá";
        try {
            $this->authRequest();
        } catch (\Throwable $e) {
            $this->response($e->getMessage(), $e->getCode());
            die(json_encode([
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]));
        }
    }

    protected function authRequest()
    {
        if (!$this->checkToken()) {
            throw new Exception('unauthorized', self::HTTP_UNAUTHORIZED);
        }
        $jwtToken = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $tokenPayload = explode('.', $jwtToken)[1];
        $tokenPayload = JWT::jsonDecode(JWT::urlsafeB64Decode($tokenPayload));
        $integration = $this->model_api_integrations->getUserByOI($tokenPayload->oi);
        $integration = !empty($integration) ? current($integration) : null;
        if (empty($integration)) {
            throw new Exception('forbidden', self::HTTP_FORBIDDEN);
        }
        $this->accountIntegration = $integration;
    }

    public function checkToken()
    {
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        if (empty($anymarket_token)) {
            return false;
        }
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        if (!isset($payload->oi)) {
            return false;
        }
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        if (empty($integration)) {
            return false;
        }
        $integration = current($integration);
        $this->setAuthData($integration);
        return true;
    }

    protected function setAuthData($data)
    {
        $this->data['usercomp'] = $data['company_id'] ?? '0';
        $this->data['userstore'] = $data['store_id'] ?? '0';

        foreach (get_object_vars($this) as $name => $value) {
            if ($value instanceof CI_Model) {
                $name = strtolower($name);
                $this->{$name}->data = array_merge($this->{$name}->data ?? [], $this->data);
            }
        }
    }

}
