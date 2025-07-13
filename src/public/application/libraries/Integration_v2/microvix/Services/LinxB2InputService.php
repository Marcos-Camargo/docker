<?php

namespace Integration\Integration_v2\microvix\Services;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Integration_v2\Applications\Resources\IntegrationConfiguration;
use InvalidArgumentException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class LinxB2InputService extends IntegrationConfiguration
{
    protected $endpoint = 'http://webapi.microvix.com.br/1.0/importador.svc';
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

    protected function generateSoapPayload(string $command, array $columns): string
    {
        $paramsSeletorDestino = [
            ['Name' => 'chave', 'Value' => $this->key],
            ['Name' => 'cnpjEmp', 'Value' => $this->cnpj],
            ['Name' => 'IdPortal', 'Value' => $this->portalId],
        ];

        $paramsXml = '';
        foreach ($paramsSeletorDestino as $param) {
            $paramsXml .= "
        <linx1:CommandParameter>
            <linx1:Name>{$param['Name']}</linx1:Name>
            <linx1:Value>{$param['Value']}</linx1:Value>
        </linx1:CommandParameter>";
        }


        $colunasXml = '';
        foreach ($columns as $name => $value) {
            $colunasXml .= "
        <linx1:CommandParameter>
            <linx1:Name>{$name}</linx1:Name>
            <linx1:Value>{$value}</linx1:Value>
        </linx1:CommandParameter>";
        }

        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
                  xmlns:tem=\"http://tempuri.org/\"
                  xmlns:linx=\"http://schemas.datacontract.org/2004/07/Linx.Microvix.WebApi.Importacao.Requests\"
                  xmlns:linx1=\"http://schemas.datacontract.org/2004/07/Linx.Microvix.WebApi.Business.Api\"
                  xmlns:linx2=\"http://schemas.datacontract.org/2004/07/Linx.Microvix.WebApi.Importacao\">
    <soapenv:Header/>
    <soapenv:Body>
        <tem:Importar>
            <tem:request>
                <linx:ParamsSeletorDestino>
                    {$paramsXml}
                </linx:ParamsSeletorDestino>
                <linx:Tabela>
                    <linx2:Comando>{$command}</linx2:Comando>
                    <linx2:Registros>
                        <linx:Registros>
                            <linx:Colunas>
                                {$colunasXml}
                            </linx:Colunas>
                        </linx:Registros>
                    </linx2:Registros>
                </linx:Tabela>
                <linx:UserAuth>
                    <linx2:Pass>{$this->password}</linx2:Pass>
                    <linx2:User>{$this->username}</linx2:User>
                </linx:UserAuth>
            </tem:request>
        </tem:Importar>
    </soapenv:Body>
</soapenv:Envelope>";
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    protected function sendSoapRequest(string $xml): array
    {
        $client = new Client([
            'timeout' => 30.0,
            'http_errors' => false,
        ]);

        try {
            $response = $client->post($this->endpoint, [
                'headers' => [
                    'SOAPAction' => 'http://tempuri.org/IImportador/Importar',
                    'Content-Type' => 'text/xml;charset=UTF-8',
                    'Accept' => 'text/xml',
                ],
                'body' => $xml,
            ]);

            return [
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
            ];
        } catch (RequestException $e) {
            return [
                'status' => $e->getCode() ?: 500,
                'body' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage(),
            ];
        }
    }

    public function registerCustomer(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CCadastraClientes', $columns);
        return $this->sendSoapRequest($xml);
    }

    public function registerDeliveryAddress(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CCadastraEnderecosEntrega', $columns);
        return $this->sendSoapRequest($xml);
    }

    public function registerOrder(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CCadastraPedido', $columns);
        return $this->sendSoapRequest($xml);
    }

    public function registerOrderPlans(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CCadastraPedidoPlanos', $columns);
        return $this->sendSoapRequest($xml);
    }

    public function registerOrderItems(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CCadastraPedidoItens', $columns);
        return $this->sendSoapRequest($xml);
    }

    public function cancelOrder(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CCancelaPedido', $columns);
        return $this->sendSoapRequest($xml);
    }

    public function updateTrackingCode(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CAtualizaCodigoRastreio', $columns);
        return $this->sendSoapRequest($xml);
    }

    public function updateStatusOrder(array $columns): array
    {
        $xml = $this->generateSoapPayload('B2CAtualizaPedidoStatus', $columns);
        return $this->sendSoapRequest($xml);
    }
}