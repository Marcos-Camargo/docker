<?php

use GuzzleHttp\Client;

require 'system/libraries/Vendor/autoload.php';

require_once APPPATH . "libraries/REST_Controller.php";

/**
 * Class DownloadRedirect
 * Esse endpoint serve para ser chamado em um servidor de IP fixo
 * (por um outro servidor com IP dinâmico),
 * com a finalidade de ter o acesso autorizado em plataformas (VIA)
 * que possuem restrição de acesso por IP
 * @package Api\Integration_v2\viavarejo_b2b
 * @property Client $client
 */
class DownloadRedirect extends \REST_Controller
{
    private $queryParams = [];


    protected $client;

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        $this->queryParams = $this->_query_args;

        $this->client = new Client([
            'verify' => false,
            'timeout' => 900,
            'connect_timeout' => 900
        ]);
    }

    public function index_get()
    {
        $url = urldecode($this->queryParams['url']);
        if ((
            (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) &&
            filter_var($url, FILTER_VALIDATE_URL) !== false
        )) {
            try {
                header_remove();
                $response = $this->client->request('GET', $url, [
                    'stream' => true
                ]);
                foreach ($response->getHeaders() as $name => $values) {
                    foreach ($values as $value) {
                        header(sprintf('%s: %s', $name, $value), true, 200);
                    }
                }
                $body = $response->getBody();
                while (!$body->eof()) {
                    echo $body->read(1024 * 1000);
                }
                return ;
            } catch (Throwable $e) {
                header('Content-Type: text/html; charset=utf-8', true, 400);
                die('O arquivo não está disponível no momento: ' . $e->getMessage());
            }
        } else {
            header('Content-Type: text/html; charset=utf-8', true, 400);
            die('O arquivo não está disponível no momento');
        }
    }
}