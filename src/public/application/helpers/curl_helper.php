<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('curlGet')) {
    function curlGet(string $url, array $payload = [], bool $retryRateLimit = false, int $rateLimitAttempts = 0, array $headers = [], $timeout = 10): array
    {

        $httpHeader = array_merge(array(
            'Accept: application/json',
            'Content-Type: application/json'
        ), $headers);

        $options = array(
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => "conectala", // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
            CURLOPT_CONNECTTIMEOUT => $timeout,       // stop after 10 seconds
            CURLOPT_TIMEOUT => $timeout,       // stop after 10 seconds
            CURLOPT_POST => false,
            CURLOPT_HTTPHEADER => $httpHeader,
            CURLOPT_SSL_VERIFYPEER => false // Disabled SSL Cert checks
        );
        if ($payload) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $response = curl_getinfo($ch);

        curl_close($ch);

        /**
         * Tratativa para se a requisição der rate limit, aguardar 1 segundo e tentar novamente, por no máximo 5 vezes
         */
        if ($retryRateLimit && $httpcode == 429 && $rateLimitAttempts < 5) {
            $rateLimitAttempts++;
            sleep(1);
            return curlGet($url, $payload, $retryRateLimit, $rateLimitAttempts);
        }

        $response['httpcode'] = $httpcode;
        $response['errno'] = $err;
        $response['errmsg'] = $errmsg;
        $response['content'] = $content;

        return $response;
    }
}

if (!function_exists('curlPost')) {
    function curlPost(string $url, array $payload = [], bool $retryRateLimit = false, int $rateLimitAttempts = 0, array $headers = [], $timeout = 10): array
    {

        $httpHeader = array_merge(array(
            'Accept: application/json',
            'Content-Type: application/json'
        ), $headers);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => "conectala", // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
            CURLOPT_CONNECTTIMEOUT => $timeout,       // stop after 10 seconds
            CURLOPT_TIMEOUT => $timeout,       // stop after 10 seconds
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $httpHeader,
            CURLOPT_SSL_VERIFYPEER => false // Disabled SSL Cert checks
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $response = curl_getinfo($ch);

        curl_close($ch);

        /**
         * Tratativa para se a requisição der rate limit, aguardar 1 segundo e tentar novamente, por no máximo 5 vezes
         */
        if ($retryRateLimit && $httpcode == 429 && $rateLimitAttempts < 5) {
            $rateLimitAttempts++;
            sleep(1);
            return curlPost($url, $payload, $retryRateLimit, $rateLimitAttempts);
        }

        $response['httpcode'] = $httpcode;
        $response['errno'] = $err;
        $response['errmsg'] = $errmsg;
        $response['content'] = $content;
        $response['reqbody'] = json_encode($payload);

        return $response;
    }
}

if (!function_exists('curlPut')) {

    function curlPut(string $url, array $payload = [], array $headers = [], bool $retryRateLimit = false, int $rateLimitAttempts = 0): array
    {

        $httpHeader = array_merge(array(
            'Accept: application/json',
            'Content-Type: application/json'
        ), $headers);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => "conectala", // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
            CURLOPT_CONNECTTIMEOUT => 10,       // stop after 10 seconds
            CURLOPT_TIMEOUT => 10,       // stop after 10 seconds
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $httpHeader,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $response = curl_getinfo($ch);

        curl_close($ch);

        /**
         * Tratativa para se a requisição der rate limit, aguardar 1 segundo e tentar novamente, por no máximo 5 vezes
         */
        if ($retryRateLimit && $httpcode == 429 && $rateLimitAttempts < 5) {
            $rateLimitAttempts++;
            sleep(1);
            return curlPut($url, $payload, $headers, $retryRateLimit, $rateLimitAttempts);
        }

        $response['httpcode'] = $httpcode;
        $response['errno'] = $err;
        $response['errmsg'] = $errmsg;
        $response['content'] = $content;
        $response['reqbody'] = json_encode($payload);

        return $response;
    }
}

if (!function_exists('curlPatch')) {

    function curlPatch(string $url, array $payload = [], array $headers = [], bool $retryRateLimit = false, int $rateLimitAttempts = 0): array
    {

        $httpHeader = array_merge(array(
            'Accept: application/json',
            'Content-Type: application/json'
        ), $headers);


        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => "conectala", // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
            CURLOPT_CONNECTTIMEOUT => 10,       // stop after 10 seconds
            CURLOPT_TIMEOUT => 10,       // stop after 10 seconds            
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $httpHeader,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $response = curl_getinfo($ch);

        curl_close($ch);

        /**
         * Tratativa para se a requisição der rate limit, aguardar 1 segundo e tentar novamente, por no máximo 5 vezes
         */
        if ($retryRateLimit && $httpcode == 429 && $rateLimitAttempts < 5) {
            $rateLimitAttempts++;
            sleep(1);
            return curlPatch($url, $payload, $headers, $retryRateLimit, $rateLimitAttempts);
        }

        $response['httpcode'] = $httpcode;
        $response['errno'] = $err;
        $response['errmsg'] = $errmsg;
        $response['content'] = $content;
        $response['reqbody'] = json_encode($payload);

        return $response;
    }
}
