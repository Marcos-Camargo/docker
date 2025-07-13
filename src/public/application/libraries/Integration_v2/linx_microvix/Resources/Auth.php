<?php

namespace Integration_v2\linx_microvix\Resources;

use GuzzleHttp\Client;
use MicrovixXMLUtil;
use SimpleXMLElement;
use Throwable;

require_once APPPATH . "libraries/Integration_v2/linx_microvix/XMLUtil.php";
require_once APPPATH . "libraries/Integration_v2/linx_microvix/Resources/Configuration.php";

/**
 * Classe para realizar a autenticação para a Microvix.
 * @package Integration_v2\linx_microvix\Resources
 * @property \CI_Loader $load
 * @property \CI_Session $session
 * @property \Model_api_integrations $integrations
 * @property \Model_settings $settings
 * @property \Model_stores $model_stores
 * @property Client $client
 * @property Configuration $configuration
 * @property array $storesIntegration
 * @property object $integration
 * @property object $credentials
 */
class Auth
{
    protected $storesIntegration;
    protected $integration;
    protected $credentials;

    // Instância da classe.
    private static $instance;

    /**
     * Retorna uma instância da classe.
     */
    public static function getInstance()
    {
        // Caso a instância não esteja definida, então cria.
        if (!isset(self::$instance) || self::$instance === null) {
            // Cria a instância da classe e da configuração, carrega os modelos e libraries.
            $instance = new Auth();
            $instance->configuration = new Configuration();
            $instance->load->library('session');
            $instance->load->model('model_settings', 'settings');
            $instance->load->model('model_api_integrations', 'integrations');
            $instance->load->model('model_stores');
            $instance->client = new Client([
                'verify' => false,
                'timeout' => 900,
                'connect_timeout' => 900,
                'allow_redirects' => true
            ]);

            self::$instance = $instance;
        }
        return self::$instance;
    }

    public function __get(string $var)
    {
        return get_instance()->{$var};
    }

    /**
     * Recupera as credenciais baseado no id da loja.
     * @param mixed $storeId Id da loja.
     */
    protected function setIntegrationByStoreId($storeId = null)
    {
        // Busca a loja.
        if ($storeId === null) {
            $storeId = $this->session->userdata('userstore') ?? null;
            $companyId = $this->session->userdata('usercomp') ?? null;
            if ($storeId == 0) {
                $storeIntegration = $this->integrations->getStoreByCompanyIdAndIntegration($companyId ?? 0, Configuration::INTEGRATION);
                $storeId = $storeIntegration['id'] ?? null;
            }
            if (empty($storeId)) {
                throw new \Exception("Nenhuma integração com Microvix localizada para a empresa #{$companyId}");
            }
        }

        // Cria a instância da integração.
        $this->integration = $this->integrations->getIntegrationByStoreId($storeId);
        if (empty($this->integration)) {
            throw new \Exception("Integração não localizada para a loja #{$storeId}");
        }

        // Recupera as credenciais de acesso.
        $this->credentials = $this->integration['credentials'] ?? '{}';
        $this->credentials = json_decode((string)$this->credentials);

        // Busca a chave de acesso compartilhada, vinda das configurações.
        $this->credentials->microvix_chave_acesso = $this->configuration->getChaveAcesso();
        $this->storesIntegration[$storeId] = (object)[
            'integration' => $this->integration,
            'credentials' => $this->credentials
        ];
    }

    /**
     * Testa a conexão com a API da Microvix.
     * @param mixed $storeId Id da Loja.
     * @return bool Verdadeiro caso as credenciais estejam validas, falso caso não consiga acessar.
     */
    public function testCredentials($storeId)
    {
        $this->setIntegrationByStoreId($storeId);
        try {
            // Gera o XML para testar as credenciais.
            // Testando apenas a chave, necessário implementar com CNPJ também.
            $xml_util = new MicrovixXMLUtil($this->credentials);
            $generatedBody = $xml_util->generateBody("B2CConsultaProdutos",null, ['cnpjEmp' => $this->credentials->microvix_cnpj,'timestamp' => 999999999]);

            // Executa a request pro endpoint de saida.
            $response = $this->client->request(
                'POST',
                $this->configuration->getUrlSaida(),
                [
                    'headers' => ['Content-Type' => 'application/xml'],
                    'body' => $generatedBody
                ]
            );

            // Verifica se apresenta status code válido.
            // Retornam 200 até para erros de autenticação, sendo necessário verificar no body da response.
            if ($response->getStatusCode() != 200) {
                return false;
            }

            // Busca o conteudo da request e acessa o resultado.
            $result = $response->getBody()->getContents();
            $xml_result = new SimpleXMLElement($result);
            return filter_var($xml_result->ResponseResult->ResponseSuccess, FILTER_VALIDATE_BOOL);
        } catch (Throwable $e) {
            // Se qualquer erro ocorrer, retorna falso.
            return false;
        }
        return false;
    }
}
