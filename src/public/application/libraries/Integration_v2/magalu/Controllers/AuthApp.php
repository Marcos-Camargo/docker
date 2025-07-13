<?php

namespace Integration_v2\magalu\Controllers;

require_once APPPATH . 'libraries/Integration_v2/magalu/Services/AuthService.php';
require_once APPPATH . 'libraries/Integration_v2/magalu/Resources/Auth.php';

use Integration_v2\magalu\Resources\Auth;
use Integration_v2\magalu\Services\AuthService;

/**
 * Class AuthApp
 * @package Integration_v2\magalu\Controllers
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
        $this->authService = new AuthService($integrationModel);
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