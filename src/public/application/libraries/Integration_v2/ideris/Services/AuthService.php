<?php

namespace Integration_v2\ideris\Services;

/**
 * Class AuthService
 * @package Integration_v2\ideris\Services
 * @property \Model_api_integrations $integration
 */
class AuthService
{

    public function __construct(\Model_api_integrations $integrationModel)
    {
        $this->integration = $integrationModel;
    }

    public function saveAuthCredentials($id, $content)
    {
        $integration = $this->integration->getIntegrationById($id);
        if (empty($integration)) throw new \Exception('Store integration not found.');
        $credentials = json_decode($integration['credentials'] ?? '{}', true);
        $credentials = array_merge($credentials, [
            'accessToken' => $content->accessToken,
            'expirationAccessToken' => $content->expirationAccessToken
        ]);
        $credentials = json_encode($credentials);
        $this->integration->update($id, [
            'id' => $id,
            'credentials' => $credentials
        ]);
    }

}