<?php

namespace Integration\Integration_v2\microvix\Services;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Integration_v2\Applications\Resources\IntegrationConfiguration;
use InvalidArgumentException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class LinxB2OutputService extends IntegrationConfiguration
{
    protected $endpoint = 'https://webapi-aceitacao.microvix.com.br/api/integracao';
    protected $username;
    protected $password;
    protected $key;
    protected $cnpj;
    protected $storeId;
    protected $portalId;
    protected $CI;

    public function __construct(int $storeId)
    {
        $this->CI =& get_instance();
        $this->storeId = $storeId;
        $this->CI->load->model('model_api_integrations');
        $this->setCredentials();
    }

    protected function setCredentials(): void
    {
        $integrationApi = $this->CI->model_api_integrations->getIntegrationByStoreId($this->storeId);

        if (empty($integrationApi) === true) {
            throw new InvalidArgumentException('Loja não contém nenhuma integração ativa, é necessário ativar e configurar alguma integração.');
        }

        $credentials = json_decode($integrationApi['credentials']);
        $this->username = $credentials->microvix_usuario;
        $this->password = $credentials->microvix_senha;
        $this->key = $this->getKeyMicrovix();
        $this->portalId = $credentials->microvix_id_portal;
        $this->cnpj = $credentials->microvix_cnpj;
    }

    private function getKeyMicrovix(): string
    {
        $this->integration = 'microvix';
        return $this->getIntegrationConfig('token_microvix');
    }

    /**
     * Requisição generia para a API de Output da Microvix.
     *
     * @param string $commandName Para qual command do WebServiceB2C vai ser enviado o request
     * @param array $parameters Parametros que vão ser montados no body (varia de command para command)
     * @return array ['status' => int, 'body' => array|string]
     */
    public function request(string $commandName, array $parameters): array
    {
        $parameterXml = '';
        foreach ($parameters as $id => $value) {
            $parameterXml .= "<Parameter id=\"{$id}\">{$value}</Parameter>\n";
        }

        $xmlPayload = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n" .
            "<LinxMicrovix>\n" .
            "    <Authentication user=\"{$this->username}\" password=\"{$this->password}\"/>\n" .
            "    <ResponseFormat>xml</ResponseFormat>\n" .
            "    <IdPortal>{$this->portalId}</IdPortal>\n" .
            "    <Command>\n" .
            "        <Name>{$commandName}</Name>\n" .
            "        <Parameters>\n" .
            "            <Parameter id=\"chave\">{$this->key}</Parameter>\n" .
            "            <Parameter id=\"cnpjEmp\">{$this->cnpj}</Parameter>\n" .
            "{$parameterXml}" .
            "        </Parameters>\n" .
            "    </Command>\n" .
            "</LinxMicrovix>";

        $client = new Client();

        try {
            $response = $client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/xml',
                ],
                'body' => $xmlPayload,
            ]);

            return $this->microvixResponse($response);
        } catch (RequestException $e) {

            return [
                'status' => $e->getCode(),
                'body' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage(),
            ];
        }
    }

    public function microvixResponse($response): array
    {
        $xmlContent = (string) $response->getBody();
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false || !isset($xml->ResponseData)) {
            throw new \Exception('Erro ao processar o XML retornado.');
        }

        if (!$xml->ResponseResult->ResponseSuccess) {
            return [
                'status' => 400,
                'body' => 'Erro no ResponseSuccess igual a false no XML retornado.',
            ];
        }

        $columns = [];
        $data = [];

        // Pega os nomes das colunas
        if (isset($xml->ResponseData->C->D)) {

            foreach ($xml->ResponseData->C->D as $column) {
                $columns[] = (string) $column;
            }
        }

        // Pega os registros
        if (isset($xml->ResponseData->R)) {
            foreach ($xml->ResponseData->R as $record) {
                $row = [];
                foreach ($record->D as $index => $value) {

                    foreach ((array) $record->D as $index => $value) {
                        $columnName = $columns[$index] ?? "column_{$index}";
                        $row[$columnName] = (string) $value;
                    }
                }
                $data[] = $row;
            }
        }

        return [
            'status' => $response->getStatusCode(),
            'body' => $data,
        ];
    }
}