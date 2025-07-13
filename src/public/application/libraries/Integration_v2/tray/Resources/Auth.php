<?php

namespace Integration_v2\tray\Resources;

use GuzzleHttp\Client;
use Integration_v2\tray\Services\AuthService;

require_once APPPATH . "libraries/Integration_v2/tray/Resources/Configuration.php";
require_once APPPATH . "libraries/Integration_v2/tray/Services/AuthService.php";

/**
 * Class Auth
 * @package Integration_v2\tray\Resources
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
            require_once APPPATH . 'libraries/Integration_v2/tray/Services/AuthService.php';
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
        if ($storeId !== null && isset($this->storesIntegration[$storeId])) {
            return $this->storesIntegration[$storeId]->credentials->apiAddress ?? Configuration::API_URL;
        }
        $this->setIntegrationByStoreId($storeId);
        return $this->credentials->apiAddress ?? Configuration::API_URL;
    }

    public function fetchAccessToken($storeId = null): string
    {
        $this->setIntegrationByStoreId($storeId);
        if (!isset($this->credentials->apiAddress) || empty($this->credentials->apiAddress)) {
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
                $storeIntegration = $this->integrations->getStoreByCompanyIdAndIntegration($companyId ?? 0, 'tray');
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
        if (strtotime('now') >= strtotime($this->credentials->expirationRefreshToken ?? 'now')) {
            throw new \Exception('Auth by app code');
        }
        $response = $this->client->request('GET', "{$this->credentials->apiAddress}/auth", [
            'query' => [
                'refresh_token' => $this->credentials->refreshToken ?? ''
            ]
        ]);
        return $this->handleAuthTokenFromResponse($response);
    }

    protected function reAuthenticateApp()
    {
        $response = $this->client->request('POST', "{$this->credentials->apiAddress}/auth", [
            'form_params' => [
                'code' => $this->credentials->code ?? '',
                'consumer_key' => $this->configuration->getConsumerKey() ?? '',
                'consumer_secret' => $this->configuration->getConsumerSecret() ?? ''
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
            $this->integration['id'], (object)array_merge((array)$this->credentials, (array)$content, ['code' => $this->credentials->code])
        );
    }

}