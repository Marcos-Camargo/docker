<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RESTfulAPI {

	public function __construct() {}

    /**
     * Fazer requisiçõa externa por CUrl
     *
     * @param   string   $url        URL da requisição
     * @param   array    $header_opt Headers, geralmente usado para informar o apikey
     * @param   string   $data       Dados em JSON para o body da requisição
     * @param   string   $method     Metódo de envio GET|PUT|POST|PATH
     * @param   int|null $timeOut_ms Tempo em timeout para realizar a cotação em ms
     * @return  array                Retorno um array com httpcode e content
     */
    public function sendRest(string $url, array $header_opt = array(), string $data = '', string $method = 'GET', int $timeOut_ms = null): array
    {
        $curl_handle = curl_init();

        curl_setopt($curl_handle, CURLOPT_URL, $url);

        if ($method == "POST" || $method == "PUT" || $method == "PATCH") {
            //post não precisa passar, é o padrão
            if ($method == "PUT")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            elseif ($method == "PATCH")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PATCH');

            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        }

        $header_opt = array_merge($header_opt, array(
            "Content-Type: application/json"
        ));

        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header_opt);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);

        if ($timeOut_ms)
            curl_setopt($curl_handle, CURLOPT_TIMEOUT_MS, $timeOut_ms);

        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        $err      = curl_errno( $curl_handle );
        $errmsg   = curl_error( $curl_handle );
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content']  = $response;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;

        return $header;
    }
}