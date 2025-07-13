<?php

use Firebase\JWT\JWT;

require_once APPPATH . "libraries/REST_Controller.php";

/**
 * Class Toggle
 * @property CI_Loader $load
 * @property Model_api_integrations $model_api_integrations
 */
class MainToggle extends REST_Controller
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        ini_set('display_errors', 0);
        header('Integration: v1');
        $this->load->model('model_api_integrations');
    }

    public function index_post()
    {
        $this->index_get();
    }

    public function index_put()
    {
        $this->index_get();
    }

    public function index_get()
    {
        try {
            $this->process();
        } catch (Throwable $e) {
            $this->response($e->getMessage(), $e->getCode());
            die(json_encode([
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]));
        }
    }

    public function process()
    {
        $anyMarketToken = $_SERVER['HTTP_X_ANYMARKET_TOKEN'] ?? '';
        if (empty($anyMarketToken)) {
            $this->response(null, self::HTTP_OK);
            die('true');
        }

        try {
            $payload = explode('.', $anyMarketToken);
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload[1] ?? current($payload)));
            if (!isset($payload->oi) || empty($payload->oi)) {
                throw new Exception('Without OI ou OI empty');
            }
        } catch (Throwable $e) {
            $this->response(null, self::HTTP_OK);
            die('true');
        }

        if (isset($this->_query_args['action']) && strcasecmp($this->_query_args['action'], 'activating') === 0) {
            $this->response(null, self::HTTP_OK);
            die('true');
        }
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = !empty($integration) ? current($integration) : null;
        if (empty($integration)) {
            $this->response(null, self::HTTP_OK);
            die('true');
        }
        throw new Exception('unauthorized', self::HTTP_UNAUTHORIZED);
    }
}