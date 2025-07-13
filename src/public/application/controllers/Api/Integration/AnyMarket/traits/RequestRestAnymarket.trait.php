<?php

if (!defined('RequestRestAnymarket')) {
    define('RequestRestAnymarket', '');
    trait RequestRestAnymarket
    {
        public function sendREST($url, $data = '', $method = 'GET', $newRequest = true, $header_opt = array())
        {
            $curl_handle = curl_init();

            if ($method == "GET") {
                curl_setopt($curl_handle, CURLOPT_URL, $url);
            } elseif ($method == "POST" || $method == "PUT") {

                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "appId: {$this->appId}",
                "token: {$this->token}",
            ));

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        }
    }
}
