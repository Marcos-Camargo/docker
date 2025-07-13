<?php

namespace Integration_v2\magalu\Resources;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Integration_v2\magalu\Services\AuthService;

require_once APPPATH . "libraries/Integration_v2/magalu/Resources/Configuration.php";
require_once APPPATH . "libraries/Integration_v2/magalu/Services/AuthService.php";

/**
 * Class Auth
 * @package Integration_v2\magalu\Resources
 * @property \CI_Loader $load
 * @property \CI_Session $session
 * @property \Model_api_integrations $integrations
 * @property \Model_settings $settings
 * @property Client $client
 * @property Configuration $configuration
 * @property AuthService $authService
 * @property array $storesIntegration
 * @property object $integration
 * @property object $credentials
 */
class Auth
{
    protected $storesIntegration;
    protected $integration;
    protected $credentials;

    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance) || self::$instance === null) {
            $instance = new Auth();
            $instance->load->library('session');
            $instance->client = new Client([
                'verify' => false,
                'timeout' => 900,
                'connect_timeout' => 900,
                'allow_redirects' => true
            ]);
            $instance->load->model('model_settings', 'settings');
            $instance->configuration = new Configuration();
            $instance->load->model('model_api_integrations', 'integrations');
            require_once APPPATH . 'libraries/Integration_v2/magalu/Services/AuthService.php';
            $instance->authService = new AuthService($instance->integrations);
            self::$instance = $instance;
        }
        return self::$instance;
    }

    public function __get(string $var)
    {
        return get_instance()->{$var};
    }

    public function fetchApiAddress($storeId = null): string
    {
        return Configuration::API_URL;
    }

    public function fetchAccessToken($storeId = null): string
    {
        $this->setIntegrationByStoreId($storeId);
        try {
            if (strtotime('now') >= strtotime($this->credentials->expirationAccessToken ?? 'now')) {
                return $this->refreshToken();
            }
            $accessToken = $this->credentials->accessToken ?? '';
            if (empty($accessToken)) throw new \Exception('Suas credenciais de acesso estão inválidas, realize o processo de autenticação novamente.');
            return $accessToken;
        } catch (\Throwable $e) {

            //Tentando novamente agora com o refresh
            try {
                echo "Tentando refreshToken".PHP_EOL;
                return $this->refreshToken();
            }catch (\Throwable $exception){
                return $this->reAuthenticateApp();
            }
        }
    }

    protected function setIntegrationByStoreId($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->session->userdata('userstore') ?? null;
            $companyId = $this->session->userdata('usercomp') ?? null;
            if ($storeId == 0) {
                $storeIntegration = $this->integrations->getStoreByCompanyIdAndIntegration($companyId ?? 0, Configuration::INTEGRATION);
                $storeId = $storeIntegration['id'] ?? null;
            }
            if (empty($storeId)) {
                throw new \Exception("Nenhuma integração com Tray localizada para a empresa #{$companyId}");
            }
        }
        $this->integration = $this->integrations->getIntegrationByStoreId($storeId);
        if (empty($this->integration)) throw new \Exception("Integração não localizada para a loja #{$storeId}");
        $this->credentials = $this->integration['credentials'] ?? '{}';
        $this->credentials = json_decode($this->credentials);
        $this->storesIntegration[$storeId] = (object)[
            'integration' => $this->integration,
            'credentials' => $this->credentials
        ];
    }

    protected function refreshToken()
    {
        $apiAddress = $this->fetchApiAddress();
        $response = $this->client->request('POST', "{$apiAddress}/oauth/token", [
            'json' => [
                'username' => $this->credentials->magalu_username,
                'password' => $this->credentials->magalu_password
            ]
        ]);
        return $this->handleAuthTokenFromResponse($response);
    }

    protected function reAuthenticateApp()
    {
        throw new \Exception('Token inválido, entre em contato com a plataforma magalu.');
    }

    protected function handleAuthTokenFromResponse($response)
    {
        $content = json_decode($response->getBody()->getContents(), true);
        $credentials = (object)[
            'accessToken' => $content['access_token'],
            'expirationAccessToken' => date('Y-m-d H:i:s', strtotime("+{$content['expires_in']} minutes"))
        ];
        $this->saveAuthCredentials($credentials);
        return $content['access_token'];
    }

    protected function saveAuthCredentials(object $content)
    {
        $this->authService->saveAuthCredentials(
            $this->integration['id'], (object)array_merge((array)$this->credentials, (array)$content)
        );
    }

    public function hackRefreshToken()
    {
        $credentials = (object)[
            'expirationAccessToken' => date('Y-m-d H:i:s', strtotime('-1440 minutes'))
        ];
        $this->saveAuthCredentials($credentials);
    }

}