<?php

namespace Integration_v2\tray\Controllers;

require_once 'system/libraries/Vendor/autoload.php';
require_once APPPATH . 'libraries/Integration_v2/tray/Resources/Configuration.php';
require_once APPPATH . 'libraries/Integration_v2/tray/Services/AuthService.php';
require_once APPPATH . 'libraries/Integration_v2/tray/Resources/Auth.php';

use GuzzleHttp\Client;
use Integration_v2\tray\Resources\Auth;
use Integration_v2\tray\Resources\Configuration;
use Integration_v2\tray\Services\AuthService;

/**
 * Class AuthApp
 * @package Integration_v2\tray\Controllers
 * @property Client $client
 * @property Configuration $configuration
 * @property AuthService $authService
 * @property object $credentials
 */
class AuthApp
{
    protected $integration;

    protected $credentials;
    protected $configuration;

    public function __construct(
        $integration,
        \Model_api_integrations $integrationModel
    )
    {
        $this->integration = $integration;
        $this->credentials = $integration['credentials'];
        $this->configuration = new Configuration();
        $this->authService = new AuthService($integrationModel);

        $this->client = new Client([
            'verify' => false,
            'timeout' => 900,
            'connect_timeout' => 900
        ]);
    }

    public function getAuthAppURL($params = []): string
    {
        $callbackUrl = Configuration::getOAuthCallbackURL();
        $queryParams = [
            'response_type' => 'code',
            'consumer_key' => $this->configuration->getConsumerKey(),
            'callback' => $callbackUrl,
        ];

        $redirectUrl = rtrim($this->credentials->storeUrl ?? Configuration::API_URL, '/');
        $qryStr = http_build_query($queryParams);
        return "{$redirectUrl}/auth.php?{$qryStr}";
    }

    public function testIntegrationConnection(int $store = null): bool
    {
        try {
            return !empty(Auth::getInstance()->fetchAccessToken($store));
        } catch (\Throwable $e) {

        }
        return false;
    }

    public function fetchAccessToken($storeId = null): string
    {
        return Auth::getInstance()->fetchAccessToken($storeId);
    }
}