<?php

require_once APPPATH . "libraries/Rest_request.php";

class RestRequestLI extends Rest_request
{
    const REQUEST_WAITING_INTERVAL = 30;
    const RETRY_REQUEST_LIMIT = 15;
    private $retryRequest = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function send()
    {
        parent::send();
        $decodedResponse = json_decode($this->response, true);
        if (isset($decodedResponse['msg'])
            && ($decodedResponse['httpcode'] == 429 ||
                (strpos(strtolower($decodedResponse['msg']), 'throttling') !== false)
                || (strpos(strtolower($decodedResponse['msg']), 'elevada') !== false))
            && ($this->retryRequest <= self::RETRY_REQUEST_LIMIT)
        ) {
            sleep(self::REQUEST_WAITING_INTERVAL);
            $this->retryRequest++;
            return $this->send();
        }
        $this->retryRequest = 0;
        return !!$this->response;
    }
}