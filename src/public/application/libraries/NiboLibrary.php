<?php /** @noinspection DuplicatedCode */

use App\Libraries\Enum\StatusFinancialManagementSystemEnum;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_gateway $model_gateway
 */
class NiboLibrary
{

    protected $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();

        $this->_CI->load->model('model_settings');
        $this->_CI->load->model('model_financial_management_systems');
        $this->_CI->load->model('model_financial_management_system_stores');
        $this->_CI->load->model('model_financial_management_system_store_histories');

    }

    /**
     * @param int $systemId
     * @param array $store
     * @return void
     */
    public function createUpdateCustomer(int $systemId, array $store): void
    {

        $payload = $this->factoryCustomerData($store);

        //Verificando se a loja já tem cadastro na Nibo
        $financialManagementSystemStoreAccount = $this->_CI->model_financial_management_system_stores->getByStoreIdFinancialManagementSystemId(
            $store['id'],
            $systemId
        );

        //Was already created, so we are going to update this store in Nibo
        if (!$financialManagementSystemStoreAccount) {

            echo "Ainda não foi Cadastrado, gerando objeto para cadastro".PHP_EOL;

            $this->_CI->model_financial_management_system_stores->createGeneric($systemId, $store['id'], StatusFinancialManagementSystemEnum::CREATING);

            echo "Gerado objeto para cadastro".PHP_EOL;

            //Getting the updated object
            $financialManagementSystemStoreAccount = $this->_CI->model_financial_management_system_stores->getByStoreIdFinancialManagementSystemId(
                $store['id'],
                $systemId
            );

            echo "Salvo Histórico de cadastrando".PHP_EOL;

        }

        //Updating at Nibo
        if ($financialManagementSystemStoreAccount['financial_management_system_code']) {

            echo "Já foi Cadastrado, gerando objeto para cadastro pelo código: {$financialManagementSystemStoreAccount['financial_management_system_code']}".PHP_EOL;

            $result = $this->putRequest(
                $this->getUrlAPI() . 'customers/' . $financialManagementSystemStoreAccount['financial_management_system_code'],
                $payload
            );

            echo "Enviado alteração, código de resposta da NIBO: {$result['http_code']}".PHP_EOL;

            $this->saveHistory(
                $financialManagementSystemStoreAccount,
                StatusFinancialManagementSystemEnum::UPDATING,
                $payload,
                $result['http_code'],
                $result['content']
            );

            return;

        }

        //Creating at Nibo
        $result = $this->postRequest(
            $this->getUrlAPI() . 'customers',
            $payload
        );

        echo "Realizando envio de cadastro, código de resposta da NIBO: {$result['http_code']}".PHP_EOL;

        $this->saveHistory(
            $financialManagementSystemStoreAccount,
            StatusFinancialManagementSystemEnum::CREATING,
            $payload,
            $result['http_code'],
            $result['content']
        );

    }

    private function factoryCustomerData(array $store): array
    {

        $data = [];
        $data['name'] = substr(trim($store['raz_social']), 0, 50);
        $data['email'] = $store['responsible_email'];
        $data['phone'] = onlyNumbers($store['phone_1']);
        $data['document'] = [];
        $data['document']['number'] = onlyNumbers($store['CNPJ']);
        $data['document']['type'] = 'cnpj';
        $data['communication'] = [];
        $data['communication']['contactName'] = $store['responsible_name'];
        $data['communication']['email'] = $store['responsible_email'];
        $data['communication']['phone'] = onlyNumbers($store['phone_1']);
        $data['communication']['cellPhone'] = onlyNumbers($store['phone_2']);
        $data['address'] = [];
        $data['address']['line1'] = $store['business_street'];
        $data['address']['number'] = $store['business_addr_num'];
        $data['address']['district'] = $store['business_neighborhood'];
        $data['address']['city'] = $store['business_town'];
        $data['address']['state'] = $store['business_uf'];
        $data['address']['zipCode'] = $store['business_code'];
        $data['address']['country'] = $store['business_nation'];
        $data['bankAccountInformation'] = [];
        $data['bankAccountInformation']['bank'] = $store['bank'];
        $data['bankAccountInformation']['agency'] = $store['agency'];
        $data['bankAccountInformation']['accountNumber'] = $store['account'];
        $data['companyInformation'] = [];
        $data['companyInformation']['companyName'] = substr(trim($store['name']), 0, 50);

        return $data;

    }

    /**
     * @param $url
     * @param $putData
     * @return array
     */
    private function putRequest($url, $putData): array
    {

        return curlPut($url, $putData, $this->factoryRequestHeaders());

    }

    private function factoryRequestHeaders(): array
    {

        $headers = [];
        $headers[] = "apitoken: " . $this->_CI->model_settings->getValueIfAtiveByName('nibo_api_key');

        return $headers;

    }

    /**
     * @return string
     */
    private function getUrlAPI(): string
    {
        return 'https://api.nibo.com.br/empresas/v1/';
    }

    private function saveHistory(array $financialManagementSystemStoreAccount, string $jobName, array $payload, int $responseCode, $responseBody): void
    {

        $responseContent = json_decode($responseBody, true);
        $responseContentJson = json_encode($responseContent, JSON_PRETTY_PRINT);

        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT);

        if ($responseCode == 200) {

            $financialManagementSystemStoreAccount['status'] = StatusFinancialManagementSystemEnum::CREATED;
            $financialManagementSystemStoreAccount['financial_management_system_code'] = $responseContent;

            //If !- no content, error ocurred.
        } elseif ($responseCode != 204) {

            //Setting error ocurred
            $financialManagementSystemStoreAccount['status'] = StatusFinancialManagementSystemEnum::ERROR;

        }

        //Always updating data
        $this->_CI->model_financial_management_system_stores->update(
            $financialManagementSystemStoreAccount,
            $financialManagementSystemStoreAccount['id']
        );

        echo "Salvo log de envio de cadastro/alteração para a Nibo".PHP_EOL;

        $this->_CI->model_financial_management_system_store_histories->createGeneric(
            $financialManagementSystemStoreAccount['id'],
            $jobName,
            $payloadJson,
            $responseContentJson,
            $responseCode
        );

        echo "Dados do log salvo: ID: {$financialManagementSystemStoreAccount['id']}, Job Name: $jobName, Payload: $payloadJson, Response: $responseContentJson, Response code: $responseCode".PHP_EOL;

    }

    /**
     * @param $url
     * @param $postData
     * @return array
     */
    private function postRequest($url, $postData): array
    {

        return curlPost($url, $postData, true, 2, $this->factoryRequestHeaders());

    }

}