<?php

namespace Integration_v2\hub2b\Controllers;

require_once 'system/libraries/Vendor/autoload.php';
require_once APPPATH . 'libraries/Integration_v2/hub2b/Resources/Configuration.php';
require_once APPPATH . 'libraries/Integration_v2/hub2b/Services/AuthService.php';
require_once APPPATH . 'libraries/Integration_v2/hub2b/Resources/Auth.php';

use GuzzleHttp\Client;
use Integration_v2\hub2b\Resources\Auth;
use Integration_v2\hub2b\Resources\Configuration;
use Integration_v2\hub2b\Services\AuthService;

/**
 * Class AuthApp
 * @package Integration_v2\hub2b\Controllers
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

    public function generateAccessToken($store)
    {
        return Auth::getInstance()->authenticateApp($store, (array)$this->credentials);
    }

    public function testIntegrationConnection(int $storeId = null): bool
    {
        try {
            if (empty($this->credentials->accessToken ?? '')) {
                $this->generateAccessToken($storeId);
            }
            return !empty(Auth::getInstance()->fetchAccessToken($storeId));
        } catch (\Throwable $e) {

        }
        return false;
    }

    public function fetchAccessToken($storeId = null): string
    {
        return Auth::getInstance()->fetchAccessToken($storeId);
    }
}