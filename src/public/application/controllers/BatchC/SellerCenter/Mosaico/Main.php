<?php

use GuzzleHttp\Client;

/**
 * Classe de utils para integração com a Mosaico.
 * @property Model_settings $model_settings
 */
class Main extends BatchBackground_Controller
{
    public $result;
    public $responseCode;
    protected $api_url;
    protected $header;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_integrations');
        $this->load->model('model_stores');
        $this->load->model('model_log_integration_product_marketplace');
        $this->load->model('model_settings');
    }

    /**
     * Realiza uma request para a API da Mosaico.
     */
    protected function processNew($separateIntegrationData, $endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to = null, $function = null)
    {
        // Dados de autenticação.
        // Durante desenvolvimento seguirá apenas este fluxo.
        $username = $separateIntegrationData->user_name;
        $password = $separateIntegrationData->password;

        // Monta a URL para esta request.
        $this->api_url = $separateIntegrationData->api_url;

        // Cria o cliente do guzzle.
        $client = new Client([
            'base_uri' => 'https://' . $this->api_url,
            'auth' => [$username, $password],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => true,
        ]);

        try {
            // Adiciona o body se necessário.
            $options = [];
            if ($data !== null) {
                $options['body'] = is_string($data) ? $data : json_encode($data);
            }

            // Realiza a request, seta o status code e o body.
            $response = $client->request($method, $endPoint, $options);
            $this->responseCode = $response->getStatusCode();
            $this->result = $response->getBody()->getContents();
        } catch (Exception $e) {
            // Em caso de erro, seta o status code, a mensagem de erro.
            $this->responseCode = 0;
            if (method_exists($e, 'hasResponse')) {
                $this->responseCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            }
            $this->result = $e->getMessage();

            echo "Erro: " . $this->result . "\n";

            // Tentativa de retry.
            if (in_array($this->responseCode, [429, 503, 504])) {
                echo "HTTP {$this->responseCode}. Nova tentativa em 60 segundos.\n";
                sleep(60);
                $this->processNew($separateIntegrationData, $endPoint, $method, $data);
                return;
            }
        }

        // Caso seja envio de produto, insere o log.
        if (!is_null($prd_id)) {
            $data_log = array(
                'int_to' => $int_to,
                'prd_id' => $prd_id,
                'url' => "https://{$this->api_url}/$endPoint",
                'function' => $function,
                'method' => $method,
                'sent' => $data,
                'response' => $this->result,
                'httpcode' => $this->responseCode,
            );
            $this->model_log_integration_product_marketplace->create($data_log);
        }

        return;
    }
}
