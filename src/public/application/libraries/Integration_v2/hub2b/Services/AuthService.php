<?php

namespace Integration_v2\hub2b\Services;

/**
 * Class AuthService
 * @package Integration_v2\hub2b\Services
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
        $expiration = strtotime('now') + ($content->expires_in ?? 0);
        $credentials = array_merge($credentials, [
            'accessToken' => $content->access_token,
            'refreshToken' => $content->refresh_token,
            'expirationAccessToken' => date('Y-m-d H:i:s', $expiration),
            'expirationRefreshToken' => date('Y-m-d H:i:s', $expiration)
        ]);
        $credentials = json_encode($credentials);
        $this->integration->update($id, [
            'id' => $id,
            'credentials' => $credentials
        ]);
    }

}