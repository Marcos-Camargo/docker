<?php

namespace Integration_v2\tray\Services;

/**
 * Class AuthService
 * @package Integration_v2\tray\Services
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
        if(empty($integration)) throw new \Exception('Store integration not found.');
        $credentials = json_decode($integration['credentials'] ?? '{}', true);
        $credentials = array_merge($credentials, [
            'storeId' => $content->store_id,
            'sellerId' => $content->store_id,
            'accessToken' => $content->access_token,
            'refreshToken' => $content->refresh_token,
            'expirationAccessToken' => $content->date_expiration_access_token,
            'expirationRefreshToken' => $content->date_expiration_refresh_token,
            'dateActivated' => $content->date_activated,
            'apiAddress' => $content->api_host
        ],
            (isset($content->code) ? ['code' => $content->code] : []),
            (isset($content->store_host) ? ['storeUrl' => $content->store_host] : [])
        );
        $credentials = json_encode($credentials);
        $this->integration->update($id, [
            'id' => $id,
            'credentials' => $credentials
        ]);
    }

}