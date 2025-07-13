<?php

namespace Integration\Integration_v2\anymarket;

class AnyMarketApiException extends ApiException
{
    public function __construct($message = '', $code = 0, $responseHeaders = [], $responseBody = null)
    {
        parent::__construct("[AnyMarket API]: {$message}", $code, $responseHeaders, $responseBody);
    }
}