<?php


namespace Integration\Integration_v2\anymarket;


use Psr\Http\Message\ResponseInterface;

/**
 * Trait RequestTools
 * @package Integration\Integration_v2\AnyMarket
 * @property AnyMarketConfiguration $config
 */
trait RequestTools
{
    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws AnyMarketApiException
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        try {
            return parent::request($method, $uri, $options);
        } catch (\Throwable $e) {
            throw new AnyMarketApiException($e->getMessage(), $e->getCode());
        }
    }

}