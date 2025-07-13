<?php

namespace Integration_v2\hub2b\Resources;

use GuzzleHttp\Client;
use Integration_v2\hub2b\Services\AuthService;

require_once APPPATH . "libraries/Integration_v2/hub2b/Resources/Configuration.php";
require_once APPPATH . "libraries/Integration_v2/hub2b/Services/AuthService.php";

/**
 * Class Auth
 * @package Integration_v2\hub2b\Resources
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
            $instance->configuration = new Configuration($instance->settings);
            $instance->load->model('model_api_integrations', 'integrations');
            require_once APPPATH . 'libraries/Integration_v2/hub2b/Services/AuthService.php';
            $instance->authService = new AuthService($instance->integrations);
            self::$instance = $instance;
        }
        return self::$instance;
    }

    public function __get(string $var)
    {
        return get_instance()->{$var};
    }

    public function fetchAccessToken($storeId = null): string
    {
        $this->setIntegrationByStoreId($storeId);
        if (!isset($this->credentials->username) || empty($this->credentials->username)) {
            throw new \Exception('Suas credenciais de acesso estão inválidas, realize o processo de autenticação novamente.');
        }
        try {
            if (strtotime('now') >= strtotime($this->credentials->expirationAccessToken ?? 'now')) {
                return $this->refreshToken();
            }
            $accessToken = $this->credentials->accessToken ?? '';
            if (empty($accessToken)) throw new \Exception('Suas credenciais de acesso estão inválidas, realize o processo de autenticação novamente.');
            return $accessToken;
        } catch (\Throwable $e) {
            return $this->reAuthenticateApp();
        }
    }

    protected function setIntegrationByStoreId($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->session->userdata('userstore') ?? null;
            $companyId = $this->session->userdata('usercomp') ?? null;
            if ($storeId == 0) {
                $storeIntegration = $this->integrations->getStoreByCompanyIdAndIntegration($companyId ?? 0, 'hub2b');
                $storeId = $storeIntegration['id'] ?? null;
            }
            if (empty($storeId)) {
                throw new \Exception("Nenhuma integração com hub2b localizada para a empresa #{$companyId}");
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
        if (strtotime('now') >= strtotime($this->credentials->expirationRefreshToken ?? 'now')) {
            throw new \Exception('Auth by app code');
        }
        $url = Configuration::getApiV2URL();
        $response = $this->client->request('POST', "{$url}/oauth2/token", [
            'json' => [
                'client_id' => $this->configuration->getClientId(),
                'client_secret' => $this->configuration->getClientSecret(),
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->credentials->refreshToken ?? ''
            ]
        ]);
        return $this->handleAuthTokenFromResponse($response);
    }

    public function authenticateApp(int $storeId = null, array $credentials = [])
    {
        $this->setIntegrationByStoreId($storeId);
        return $this->requestAccessCredentials($credentials);
    }

    protected function reAuthenticateApp()
    {
        return $this->requestAccessCredentials((array)$this->credentials);
    }

    protected function requestAccessCredentials(array $credentials = [])
    {
        $url = Configuration::getApiV2URL();
        $response = $this->client->request('POST', "{$url}/oauth2/login", [
            'json' => [
                'client_id' => $this->configuration->getClientId(),
                'client_secret' => $this->configuration->getClientSecret(),
                'grant_type' => 'password',
                'scope' => $this->configuration->getAuthScope(),
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? ''
            ]
        ]);
        return $this->handleAuthTokenFromResponse($response);
    }

    protected function handleAuthTokenFromResponse($response)
    {
        $content = json_decode($response->getBody()->getContents());
        if (!isset($content->access_token)) throw new Exception($response->getBody()->getContents(), $response->getStatusCode());
        $this->saveAuthCredentials($content);
        return $content->access_token;
    }

    protected function saveAuthCredentials(object $content)
    {
        $this->authService->saveAuthCredentials(
            $this->integration['id'], (object)array_merge((array)$this->credentials, (array)$content)
        );
    }

}