<?php

namespace Integration_v2\linx_microvix\Controllers;

require_once 'system/libraries/Vendor/autoload.php';
//require_once APPPATH . 'libraries/Integration_v2/linx_microvix/Resources/Configuration.php';
//require_once APPPATH . 'libraries/Integration_v2/linx_microvix/Services/AuthService.php';
require_once APPPATH . 'libraries/Integration_v2/linx_microvix/Resources/Auth.php';

use Integration_v2\linx_microvix\Resources\Auth;
use Integration_v2\linx_microvix\Resources\Configuration;
use Integration_v2\linx_microvix\Services\AuthService;

/**
 * Classe para realizar a autenticação do app.
 * @package Integration_v2\linx_microvix\Controllers
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
    ) {
        $this->integration = $integration;
        $this->credentials = $integration['credentials'];
        // $this->configuration = new Configuration();
        //  $this->authService = new AuthService($integrationModel);

    }


    /**
     * Testa a integração com a Microvix.
     * @param mixed $storeId Id da loja.
     * @return bool Verdadeiro caso sucesso, se não, falso.
     */
    public function testIntegrationConnection(int $storeId = null): bool
    {
        try {
            return Auth::getInstance()->testCredentials($storeId);
        } catch (\Throwable $e) {
        }
        return false;
    }
}
