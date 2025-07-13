<?php


namespace Integration\Integration_v2\anymarket;

/**
 * Trait AuthTools
 * @package Integration\Integration_v2\AnyMarket
 * @property AnyMarketConfiguration $config
 * @property \Model_settings $model_settings
 * @property \stdClass $credentials
 */
trait AuthTools
{

    public function setAuth(int $store)
    {
        parent::setAuth($store);

        $accessToken = $this->credentials->token_anymarket;
        $parameters = (array)$this->credentials;

        $this->config = new AnyMarketConfiguration(
            $accessToken,
            $parameters
        );
    }
}