<?php

/** @noinspection DuplicatedCode */

use App\Libraries\Enum\AntecipationTypeEnum;
use App\Libraries\Enum\AnticipationStatusEnum;
use App\Libraries\Enum\PaymentGatewayEnum;

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . "libraries/GatewayPaymentLibrary.php";

/**
 * @property Model_gateway $model_gateway
 * @property Model_settings $model_settings
 * @property Model_gateway_settings $model_gateway_settings
 * @property Model_anticipation_limits_store $model_anticipation_limits_store
 * @property Model_orders_conciliation_installments $model_orders_conciliation_installments
 */
class PagarmeLibrary extends GatewayPaymentLibrary
{

    public $_CI;
    public $primary_account = '';
    public $primary_account_v5 = '';
    public $allow_transfer_between_accounts = '';
    public $url_api_v1 = '';
    public $app_key = '';

    public $url_api_v5 = '';
    public $app_key_v5 = '';
    public $pagarme_subaccounts_api_version = '';
    public $external_id_prefix_v5 = '';

    public $external_id_prefix = '';

    public $banks_with_zero_fee = '';
    public $balance_transfers_valid_updated_minutes;
    public $transferCost;
    public $minimunTransferValue = 100;
    private $subaccountDetails = [];

    public function __construct()
    {
        $this->_CI = &get_instance();

        $this->_CI->load->model('model_gateway_settings');
        $this->_CI->load->model('model_transfer');
        $this->_CI->load->model('model_stores');
        $this->_CI->load->model('model_gateway');
        $this->_CI->load->model('model_gateway_transfers');
        $this->_CI->load->model('model_repasse');
        $this->_CI->load->model('model_iugu_repasse');
        $this->_CI->load->model('model_conciliation');
        $this->_CI->load->model('model_settings');
        $this->_CI->load->model('model_anticipation_limits_store');
        $this->_CI->load->model('model_payment_gateway_store_logs');
        $this->_CI->load->model('model_simulations_anticipations_store');
        $this->_CI->load->model('model_orders_conciliation_installments');
        $this->_CI->load->model('model_orders_simulations_anticipations_store');

        $this->balance_transfers_valid_updated_minutes = intVal($this->_CI->model_settings->getValueIfAtiveByName('balance_transfers_valid_updated_minutes'));

        $this->gateway_name = Model_gateway::PAGARME;
        $this->loadSettings();
    }

    private function regexOldId(string $id) : string
    {
        $position = strpos($id, "_old");
        if (!$position) {
            return $id;
        }
        return substr($id, 0, $position);
    }

    /**
     * @param array $store
     * @param array|null $gatewaySubaccount
     * @return array
     */
    public function createUpdateRecipient_v5(array $store, array $gatewaySubaccount = null): array
    {

        $arr_agency = explode('-', $store['agency']);
        $arr_account = explode('-', $store['account']);

        $bankAccount = array(
            'holder_name' => mb_substr($store['raz_social'], 0, 30),
            'holder_type' => "individual",
            'holder_document' => onlyNumbers($store['CNPJ']),
            'bank' => trim($store['bank_number']),
            'branch_number' => trim($arr_agency[0]),
            'account_number' => trim($arr_account[0]),
            'type' => $this->getAccountTypeByStore_v5($store),
        );

        if (count($arr_agency) > 1) {
            $bankAccount['branch_check_digit'] = $arr_agency[1];
        }

        if (count($arr_account) > 1) {
            $bankAccount['account_check_digit'] = $arr_account[1];
        }

        $externalId = $this->getExternalIdVtex($store);

        //Por algum motivo ainda não tem id vtex, então não permitiremos continuar
        if (!$externalId) {
            get_instance()->model_stores->setDateUpdateNow($store['id']);
            get_instance()->log_data('batch', get_instance()->logName, "Loja {$store['id']} ainda não tem id vtex", "E");
            return [];
        }

        $payload = [
            "name" => mb_substr($store['raz_social'], 0, 30),
            "email" => $store['responsible_email'],
            "description" => "",
            "type" => "individual",
        ];
        if (!$gatewaySubaccount) {
            $payload["code"] = $externalId;
            $payload["default_bank_account"] = $bankAccount;
            $payload["document"] = onlyNumbers($store['CNPJ']);
        }


        $payload['automatic_anticipation_settings']['enabled'] = (bool)$this->_CI->model_settings->getValueIfAtiveByName('allow_automatic_antecipation') && $store['use_automatic_antecipation'];
        if ($payload['automatic_anticipation_settings']['enabled']) {
            $payload['automatic_anticipation_settings']['type'] = $store['antecipation_type'];
            $payload['automatic_anticipation_settings']['volume_percentage'] = intval($store['percentage_amount_to_be_antecipated']);
            if ($payload['automatic_anticipation_settings']['type'] == AntecipationTypeEnum::DX) {
                $payload['automatic_anticipation_settings']['delay'] = (string)$store['number_days_advance'];
            }
        }

        if ($gatewaySubaccount) {

            // Atualiza dados da conta bancária da conta
            $this->updateBankAccountRecipient_v5($store, $gatewaySubaccount);

            // Atualiza dados de antecipação automática da conta
            if ($store['use_automatic_antecipation']) {
                $response = $this->updateAutomaticAnticipationRecipient_v5($store, $gatewaySubaccount);
                if ($response['httpcode'] < 200 || $response['httpcode'] > 299) {
                    get_instance()->model_stores->setDateUpdateNow($store['id']);
                    $this->store_log_pagarme_v5($response, $gatewaySubaccount, $store);
                }
            }

            return $this->putRequest_v5($this->getUrlAPI_V5() . 'recipients' . '/' . $gatewaySubaccount['secondary_gateway_account_id'], $payload);

        }

        return $this->postRequest_v5($this->getUrlAPI_V5() . 'recipients', $payload);

    }

    //Função nova de loja BACEN - OEP-1598
    public function createUpdateRecipientBacen_v5(array $store, array $gatewaySubaccount = null): array
    {
        
        $arr_agency = explode('-', $store['agency']);
        $arr_account = explode('-', $store['account']);

        $bankAccount = array(
            'holder_name' => mb_substr($store['raz_social'], 0, 30),
            'holder_type' => "individual",
            'holder_document' => onlyNumbers($store['CNPJ']),
            'bank' => trim($store['bank_number']),
            'branch_number' => trim($arr_agency[0]),
            'account_number' => trim($arr_account[0]),
            'type' => $this->getAccountTypeByStore_v5($store),
        );

        if (count($arr_agency) > 1) {
            $bankAccount['branch_check_digit'] = $arr_agency[1];
        }

        if (count($arr_account) > 1) {
            $bankAccount['account_check_digit'] = $arr_account[1];
        }
 
        $externalId = $this->getExternalIdVtex($store);

        //Por algum motivo ainda não tem id vtex, então não permitiremos continuar
        if (!$externalId) {
            get_instance()->model_stores->setDateUpdateNow($store['id']);
            get_instance()->log_data('batch', get_instance()->logName, "Loja {$store['id']} ainda não tem id vtex", "E");
            return [];
        }
        

        $payload = array();

        // Informações principais da loja
        $payload['register_information']['company_name'] = $store['name'];
        $payload['register_information']['trading_name'] = mb_substr($store['raz_social'], 0, 30);
        $payload['register_information']['email'] = $store['responsible_email'];
        $payload['register_information']['document'] = onlyNumbers($store['CNPJ']);
        $payload['register_information']['type'] = "corporation";
        $payload['register_information']['annual_revenue'] = $store['company_annual_revenue']/100; // verificar o campo para colocar

        // Informações da endereço
        $payload['register_information']['main_address']['street'] = $store['address'];
        $payload['register_information']['main_address']['complementary'] = $store['addr_compl'];
        $payload['register_information']['main_address']['street_number'] = $store['addr_num'];
        $payload['register_information']['main_address']['neighborhood'] = $store['addr_neigh'];
        $payload['register_information']['main_address']['city'] = $store['addr_city'];
        $payload['register_information']['main_address']['state'] = $store['addr_uf'];
        $payload['register_information']['main_address']['zip_code'] = $store['zipcode'];
        $payload['register_information']['main_address']['reference_point'] = "N/A";

        // Informações de telefone
        $payload['register_information']['phone_numbers'][0]['ddd'] = substr(str_replace("\\","",str_replace("-","",str_replace(")","",str_replace("(","",$store['phone_1'])))),0,2);
        $payload['register_information']['phone_numbers'][0]['number'] = substr(str_replace("\\","",str_replace("-","",str_replace(")","",str_replace("(","",$store['phone_1'])))),2,30);
        $payload['register_information']['phone_numbers'][0]['type'] = "contato";

        
        //Informações responsável

        if($store['responsable_birth_date'] == "0000-00-00"){
            $responsable_birth_date = "1990-01-01";
        }else{
            $responsable_birth_date = $store['responsable_birth_date'];
        }

        $payload['register_information']['managing_partners'][0]['name'] = $store['responsible_name'];
        $payload['register_information']['managing_partners'][0]['email'] = $store['responsible_email'];
        $payload['register_information']['managing_partners'][0]['document'] = str_replace("\\","",str_replace("/","",str_replace("-","",str_replace(".","",$store['responsible_cpf']))));
        $payload['register_information']['managing_partners'][0]['type'] = "individual";
        $payload['register_information']['managing_partners'][0]['mother_name'] = $store['responsible_mother_name'];
        $payload['register_information']['managing_partners'][0]['birthdate'] = $responsable_birth_date;
        $payload['register_information']['managing_partners'][0]['monthly_income'] = $store['responsible_monthly_income']/100;
        $payload['register_information']['managing_partners'][0]['professional_occupation'] = $store['responsible_position'];
        $payload['register_information']['managing_partners'][0]['self_declared_legal_representative'] = true;

        $payload['register_information']['managing_partners'][0]['address']['street'] = $store['address'];
        $payload['register_information']['managing_partners'][0]['address']['complementary'] = $store['addr_compl'];
        $payload['register_information']['managing_partners'][0]['address']['street_number'] = $store['addr_num'];
        $payload['register_information']['managing_partners'][0]['address']['neighborhood'] = $store['addr_neigh'];
        $payload['register_information']['managing_partners'][0]['address']['city'] = $store['addr_city'];
        $payload['register_information']['managing_partners'][0]['address']['state'] = $store['addr_uf'];
        $payload['register_information']['managing_partners'][0]['address']['zip_code'] = $store['zipcode'];
        $payload['register_information']['managing_partners'][0]['address']['reference_point'] = "N/A";

        $payload['register_information']['managing_partners'][0]['phone_numbers'][0]['ddd'] = substr(str_replace("\\","",str_replace("-","",str_replace(")","",str_replace("(","",$store['phone_1'])))),0,2);
        $payload['register_information']['managing_partners'][0]['phone_numbers'][0]['number'] = substr(str_replace("\\","",str_replace("-","",str_replace(")","",str_replace("(","",$store['phone_1'])))),2,30);
        $payload['register_information']['managing_partners'][0]['phone_numbers'][0]['type'] = "contato";

        if (!$gatewaySubaccount) {
            $payload["code"] = $externalId;
            $payload["default_bank_account"] = $bankAccount;
            // $payload["document"] = onlyNumbers($store['CNPJ']);
        }


        $payload['automatic_anticipation_settings']['enables'] = (bool)$this->_CI->model_settings->getValueIfAtiveByName('allow_automatic_antecipation') && $store['use_automatic_antecipation'];
        if ($payload['automatic_anticipation_settings']['enables']) {
            $payload['automatic_anticipation_settings']['type'] = $store['antecipation_type'];
            $payload['automatic_anticipation_settings']['volume_percentage'] = intval($store['percentage_amount_to_be_antecipated']);
            if ($payload['automatic_anticipation_settings']['type'] == AntecipationTypeEnum::DX) {
                $payload['automatic_anticipation_settings']['delay'] = (string)$store['number_days_advance'];
            }
        }

        if ($gatewaySubaccount) {

            // Atualiza dados da conta bancária da conta
            $this->updateBankAccountRecipient_v5($store, $gatewaySubaccount);

            // Atualiza dados de antecipação automática da conta
            if ($store['use_automatic_antecipation']) {
                $response = $this->updateAutomaticAnticipationRecipient_v5($store, $gatewaySubaccount);
                if ($response['httpcode'] < 200 || $response['httpcode'] > 299) {
                    get_instance()->model_stores->setDateUpdateNow($store['id']);
                    $this->store_log_pagarme_v5($response, $gatewaySubaccount, $store);
                }
            }

            return $this->putRequest_v5($this->getUrlAPI_V5() . 'recipients' . '/' . $gatewaySubaccount['secondary_gateway_account_id'], $payload);

        }

        return $this->postRequest_v5($this->getUrlAPI_V5() . 'recipients', $payload);

    }

    private function getAccountTypeByStore_v5(array $store): string
    {
        if ($store['account_type'] == 'Conta Corrente') {
            return 'checking';
        }
        return 'saving';
    }

    public function getExternalIdVtex(array $store): string
    {

        $seller_id_json = $this->_CI->model_gateway->getVtexSellerIdIntegration($store['id']);
        $seller_id_array = json_decode($seller_id_json, true);

        if (!isset($seller_id_array['seller_id'])) {
            return "";
        }

        return $seller_id_array['seller_id'];
    }

    /**
     * @param array $store
     * @param array|null $gatewaySubaccount
     * @return array
     */
    public function updateBankAccountRecipient_v5(array $store, array $gatewaySubaccount = null): void
    {

        $current_recipient = $this->getSubaccountDetails($this->pagarme_subaccounts_api_version, $gatewaySubaccount);
        $current_bank_account = $current_recipient['default_bank_account'];

        $arr_agency = explode('-', $store['agency']);
        $arr_account = explode('-', $store['account']);

        $bankAccount = array(
            'holder_name' => mb_substr($store['raz_social'], 0, 30),
            'holder_type' => "individual",
            'holder_document' => onlyNumbers($store['CNPJ']),
            'bank' => trim($store['bank_number']),
            'branch_number' => trim($arr_agency[0]),
            'account_number' => trim($arr_account[0]),
            'type' => $this->getAccountTypeByStore_v5($store),
        );

        if (count($arr_agency) > 1) {
            $bankAccount['branch_check_digit'] = $arr_agency[1];
        }

        if (count($arr_account) > 1) {
            $bankAccount['account_check_digit'] = $arr_account[1];
        }

        $payload['bank_account'] = $bankAccount;

        $shouldPatch = false;
        foreach ($bankAccount as $key => $value) {
            if ($current_bank_account[$key] != $value) {
                $shouldPatch = true;
            }
        }

        if ($shouldPatch) {

            $response = $this->patchRequest_v5($this->getUrlAPI_V5() . 'recipients/' . $gatewaySubaccount['secondary_gateway_account_id'] . '/default-bank-account', $payload);

            if (($response['httpcode'] < 200 || $response['httpcode'] > 299)) {
                get_instance()->model_stores->setDateUpdateNow($store['id']);
                $this->store_log_pagarme_v5($response, $gatewaySubaccount, $store);
            }

        }

    }

    /**
     * @param int $version
     * @param array $gatewaySubaccount
     * @return array
     */
    public function getSubaccountDetails(int $version, array $gatewaySubaccount): array
    {
        if ($version == 5) {
            if (isset($this->subaccountDetails[$gatewaySubaccount['secondary_gateway_account_id']])) {
                return $this->subaccountDetails[$gatewaySubaccount['secondary_gateway_account_id']];
            }
            $response = $this->getRequest_v5($this->getUrlAPI_V5() . 'recipients/' . $gatewaySubaccount['secondary_gateway_account_id']);
            $response_json = json_decode($response['content'], true);
            return $this->subaccountDetails[$gatewaySubaccount['secondary_gateway_account_id']] = $response_json;
        }
        if (isset($this->subaccountDetails[$gatewaySubaccount['gateway_account_id']])) {
            return $this->subaccountDetails[$gatewaySubaccount['gateway_account_id']];
        }
        $response = $this->getRequest($this->getUrlAPI_V1() . '/recipients/' . $gatewaySubaccount['gateway_account_id']);
        $response_json = json_decode($response['content'], true);
        return $this->subaccountDetails[$gatewaySubaccount['gateway_account_id']] = $response_json;

    }

    /**
     * @param $url
     * @return array
     */
    public function getRequest_v5($url): array
    {

        $payload = [];
//        $payload['api_key'] = $this->app_key;

        $headers = [
            'Authorization: Basic ' . base64_encode($this->app_key_v5 . ':')
        ];

        return curlGet($url, $payload, true, 10, $headers, 60);
    }

    /**
     * @return string
     */
    public function getUrlAPI_V5(): string
    {
        // return (ENVIRONMENT === 'production') ? $this->url_api_v1 : $this->url_api_v1;
        return $this->url_api_v5;
    }

    /**
     * @param $url
     * @return array
     */
    public function getRequest($url): array
    {

        $payload = [];
        $payload['api_key'] = $this->app_key;

        return curlGet($url, $payload, true, 10, [], 60);
    }

    /**
     * @return string
     */
    public function getUrlAPI_V1(): string
    {
        // return (ENVIRONMENT === 'production') ? $this->url_api_v1 : $this->url_api_v1;
        return $this->url_api_v1;
    }

    /**
     * @param $url
     * @param array $post_data
     * @return array
     */
    private function patchRequest_v5($url, array $post_data = []): array
    {

        $payload = $post_data;
        $headers = [
            'Authorization: Basic ' . base64_encode($this->app_key_v5 . ':')
        ];

        return curlPatch($url, $payload, $headers);
    }

    public function store_log_pagarme_v5($response, $gatewaySubaccount, $store)
    {
        $log_name = get_instance()->logName;
        $requestContent = json_decode($response['reqbody']);
        $requestContent = json_encode($requestContent, JSON_PRETTY_PRINT);
        $responseContent = json_decode($response['content']);
        $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT);
        if ($responseContent == 'null') {
            $responseContent = $response['content'];
        }
        $msg = "Erro ao atualizar dados na Pagar.me, Loja: " . $store['id']
            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
            "Resposta da Pagar.me: " . PHP_EOL
            . $responseContent . ' ' . PHP_EOL .
            'Dados Fornecidos: ' . PHP_EOL
            . $requestContent . PHP_EOL;

        get_instance()->log_data('batch', $log_name, $msg, "E");

        get_instance()->model_payment_gateway_store_logs->insertLog(
            $store['id'],
            PaymentGatewayEnum::PAGARME,
            $msg
        );
    }

    /**
     * @param array $store
     * @param array|null $gatewaySubaccount
     * @return array
     */
    public function updateAutomaticAnticipationRecipient_v5(array $store, array $gatewaySubaccount = null): array
    {
        $enabled = (bool)$this->_CI->model_settings->getValueIfAtiveByName('allow_automatic_antecipation') && $store['use_automatic_antecipation'];
        $payload = [
            'enabled' => $enabled,
            'type' => $store['antecipation_type'],
            'volume_percentage' => intval($store['percentage_amount_to_be_antecipated']),
        ];

        if ($enabled) {
            // $payload['days'] = [$store['automatic_anticipation_days']];
            // $payload['days'] = [str_replace(',', '","', $store['automatic_anticipation_days'])];
            $payload['days'] = explode(",", $store['automatic_anticipation_days']);
        }

        if ($store['antecipation_type'] == AntecipationTypeEnum::DX) {
            $payload['delay'] = (string)$store['number_days_advance'];
        }
        return $this->patchRequest_v5($this->getUrlAPI_V5() . 'recipients' . '/' . $gatewaySubaccount['secondary_gateway_account_id'] . '/automatic-anticipation-settings', $payload);
    }

    /**
     * @param $url
     * @param $post_data
     * @return array
     */
    private function putRequest_v5($url, $post_data): array
    {

        $payload = $post_data;
//        $payload['api_key'] = $this->app_key_v5;
        $headers = [
            'Authorization: Basic ' . base64_encode($this->app_key_v5 . ':')
        ];

        return curlPut($url, $payload, $headers);
    }

    /**
     * @param $url
     * @param array $post_data
     * @return array
     */
    public function postRequest_v5($url, array $post_data = []): array
    {

        $payload = $post_data;
//        $payload['api_key'] = $this->app_key_v5;
        $headers = [
            'Authorization: Basic ' . base64_encode($this->app_key_v5 . ':')
        ];

        return curlPost($url, $payload, true, 10, $headers, 60);
    }

    public function validateSubaccountStatus(array $gatewaySubaccount, int $version = 5): void
    {

        $subaccount_details_content = $this->getSubaccountDetails($version, $gatewaySubaccount);

        $validations_check = [];
        if ($version == 5) {

            $validations_check['Status da conta Não está ativo'] = !in_array($subaccount_details_content['status'], ['active', 'registration']) || !in_array($subaccount_details_content['gateway_recipients'][0]['status'], ['active', 'registration']);
            $validations_check['Não Possuí dados bancários'] = !$subaccount_details_content['default_bank_account'];
            $validations_check['Conta Bancária Não cadastrada'] = isset($subaccount_details_content['default_bank_account']['id']) && empty($subaccount_details_content['default_bank_account']['id']);
            $validations_check['Nome da conta bancária não cadastrada'] = isset($subaccount_details_content['default_bank_account']['holder_name']) && empty($subaccount_details_content['default_bank_account']['holder_name']);
            $validations_check['Número do Documento da conta bancária não cadastrada'] = isset($subaccount_details_content['default_bank_account']['holder_document']) && empty($subaccount_details_content['default_bank_account']['holder_document']);
            $validations_check['Banco da conta bancária não cadastrada'] = isset($subaccount_details_content['default_bank_account']['bank']) && empty($subaccount_details_content['default_bank_account']['bank']);
            $validations_check['Agência da conta bancária não cadastrada'] = isset($subaccount_details_content['default_bank_account']['branch_number']) && empty($subaccount_details_content['default_bank_account']['branch_number']);
            $validations_check['Número da conta bancária não cadastrada'] = isset($subaccount_details_content['default_bank_account']['account_number']) && empty($subaccount_details_content['default_bank_account']['account_number']);
            $validations_check['Dígito verificador da conta bancária não cadastrada'] = isset($subaccount_details_content['default_bank_account']['account_check_digit']) && $subaccount_details_content['default_bank_account']['account_check_digit'] === '';
            $validations_check['Tipo da conta bancária não cadastrada'] = isset($subaccount_details_content['default_bank_account']['type']) && empty($subaccount_details_content['default_bank_account']['type']);
            $validations_check['Status da conta bancária não está ativo'] = isset($subaccount_details_content['default_bank_account']['status']) && empty($subaccount_details_content['default_bank_account']['status']);

        } else {

            $validations_check['Status da conta Não está ativo'] = !in_array($subaccount_details_content['status'], ['active', 'registration']);
            $validations_check['Não Possuí dados bancários'] = !isset($subaccount_details_content['bank_account']) || !$subaccount_details_content['bank_account'];
            $validations_check['Conta Bancária Não cadastrada'] = isset($subaccount_details_content['bank_account']['id']) && empty($subaccount_details_content['bank_account']['id']);
            $validations_check['Nome da conta bancária não cadastrada'] = isset($subaccount_details_content['bank_account']['legal_name']) && empty($subaccount_details_content['bank_account']['legal_name']);
            $validations_check['Número do Documento da conta bancária não cadastrada'] = isset($subaccount_details_content['bank_account']['document_number']) && empty($subaccount_details_content['bank_account']['document_number']);
            $validations_check['Banco da conta bancária não cadastrada'] = isset($subaccount_details_content['bank_account']['bank_code']) && empty($subaccount_details_content['bank_account']['bank_code']);
            $validations_check['Agência da conta bancária não cadastrada'] = isset($subaccount_details_content['bank_account']['agencia']) && empty($subaccount_details_content['bank_account']['agencia']);
            $validations_check['Número da conta bancária não cadastrada'] = isset($subaccount_details_content['bank_account']['conta']) && empty($subaccount_details_content['bank_account']['conta']);
            $validations_check['Dígito verificador da conta bancária não cadastrada'] = isset($subaccount_details_content['bank_account']['conta_dv']) && $subaccount_details_content['bank_account']['conta_dv'] === '';
            $validations_check['Tipo da conta bancária não cadastrada'] = isset($subaccount_details_content['bank_account']['type']) && empty($subaccount_details_content['bank_account']['type']);

        }

        $errors = [];
        foreach ($validations_check as $name => $validation) {
            if ($validation) {
                $errors[] = $name;
            }
        }

        if ($gatewaySubaccount['with_pendencies'] == 0 && ($errors)) {
            get_instance()->model_gateway->updateSubAccounts($gatewaySubaccount['id'], ['with_pendencies' => 1]);
            get_instance()->model_payment_gateway_store_logs->insertLog(
                $gatewaySubaccount['store_id'],
                PaymentGatewayEnum::PAGARME,
                "Cadastro incompleto na pagarme
                \nErros encontrados: " . implode(', ', $errors) . " 
                \nDados Atuais na Pagarme: " . json_encode($subaccount_details_content),
                Model_payment_gateway_store_logs::STATUS_PENDENCIES
            );
            get_instance()->model_stores->setDateUpdateNow($gatewaySubaccount['store_id']);
            return;
        }

        //Pendency resolved
        if ($gatewaySubaccount['with_pendencies'] == 1) {
            get_instance()->model_gateway->updateSubAccounts($gatewaySubaccount['id'], ['with_pendencies' => 0]);
        }

    }

    /**
     * @param array $store
     * @param array|null $gatewaySubaccount
     * @return array
     */
    public function createUpdateRecipient_v4(array $store, array $gatewaySubaccount = null): array
    {
        $arr_agency = explode('-', $store['agency']);
        $arr_account = explode('-', $store['account']);

        $document_number = onlyNumbers($store['CNPJ']);
        $name = mb_substr($store['raz_social'], 0, 30);

        $bankAccount = array(
            'type' => $this->getAccountTypeByStore($store),
            'bank_code' => trim($store['bank_number']),
            'agencia' => trim($arr_agency[0]),
            'conta' => trim($arr_account[0]),
            'document_number' => $document_number,
            'legal_name' => $name,
        );

        if (count($arr_agency) > 1) {
            $bankAccount['agencia_dv'] = $arr_agency[1];
        }

        if (count($arr_account) > 1) {
            $bankAccount['conta_dv'] = $arr_account[1];
        }

        $externalId = $this->getExternalIdVtex($store);

        if (!$externalId) {
            get_instance()->model_stores->setDateUpdateNow($store['id']);
            get_instance()->log_data('batch', get_instance()->logName, "Loja {$store['id']} ainda não tem id vtex", "E");
            return [];
        }

        $phone_ddd = mb_substr($store['phone_1'], 0, 2);
        $phone_number = mb_substr($store['phone_1'], 2);

        $payload = [
            'bank_account' => $bankAccount,
            'transfer_enabled' => 'false',
            'external_id' => $externalId,
            'register_information' => [
                'type' => 'individual',
                'document_number' => $document_number,
                'name' => $name,
                'email' => $store['responsible_email'],
                'phone_numbers' => [
                    [
                        'ddd' => $phone_ddd,
                        'number' => $phone_number,
                        'type' => 'phone',
                    ]
                ],
            ]
        ];

        $automatic_anticipation_enabled = (bool)$this->_CI->model_settings->getValueIfAtiveByName('allow_automatic_antecipation') && $store['use_automatic_antecipation'];
        $payload['automatic_anticipation_enabled'] = $automatic_anticipation_enabled;

        if ($automatic_anticipation_enabled) {
            $automatic_anticipation_type = $store['antecipation_type'];
            $anticipatable_volume_percentage = (string)$store['percentage_amount_to_be_antecipated'];
            $automatic_anticipation_days = "[{$store['automatic_anticipation_days']}]";

            $payload['automatic_anticipation_type'] = $automatic_anticipation_type;
            $payload['anticipatable_volume_percentage'] = $anticipatable_volume_percentage;
            $payload['automatic_anticipation_days'] = $automatic_anticipation_days;

            if ($automatic_anticipation_type == AntecipationTypeEnum::DX) {
                $payload['automatic_anticipation_1025_delay'] = (string)$store['number_days_advance'];
            }
        }

        if ($gatewaySubaccount) {
            $newPayload = [
                'bank_account' => array_merge($bankAccount, array("document_type" => strlen($document_number) === 14 ? "cnpj" : "cpf")),
                'automatic_anticipation_enabled' => $automatic_anticipation_enabled
            ];

            if ($automatic_anticipation_enabled) {
                $newPayload['automatic_anticipation_type'] = $automatic_anticipation_type;
                $newPayload['anticipatable_volume_percentage'] = $anticipatable_volume_percentage;
                $newPayload['automatic_anticipation_days'] = $automatic_anticipation_days;

                if ($automatic_anticipation_type == AntecipationTypeEnum::DX) {
                    $newPayload['automatic_anticipation_1025_delay'] = $payload['automatic_anticipation_1025_delay'];
                }
            }

            $payload = $newPayload;

            return $this->putRequest($this->getUrlAPI_V1() . '/recipients/' . $gatewaySubaccount['gateway_account_id'], $payload);
        }

        return $this->postRequest($this->getUrlAPI_V1() . '/recipients', $payload);
    }

    /**
     * @param array $store
     * @return string
     */
    private function getAccountTypeByStore(array $store): string
    {
        if ($store['account_type'] == 'Conta Corrente') {
            return 'conta_corrente';
        }
        return 'conta_poupanca';
    }

    /**
     * @param $url
     * @param $post_data
     * @return array
     */
    private function putRequest($url, $post_data): array
    {

        $payload = $post_data;
        $payload['api_key'] = $this->app_key;

        return curlPut($url, $payload);
    }

    /**
     * @param $url
     * @param array $post_data
     * @return array
     */
    private function postRequest($url, array $post_data = []): array
    {

        $payload = $post_data;
        $payload['api_key'] = $this->app_key;

        return curlPost($url, $payload, true, 5, [], 60);
    }

    public function isRegisteredIntoVtex(array $store): bool
    {

        $seller_id_json = $this->_CI->model_gateway->getVtexSellerIdIntegration($store['id']);

        if (!$seller_id_json) {
            return false;
        }

        $seller_id_array = json_decode($seller_id_json, true);

        if (!$seller_id_array) {
            return false;
        }

        return true;
    }

    /**
     * @param array $store
     * @return array|null
     */
    public function findRecipientByStore(array $store): ?array
    {
        $externalId = $this->external_id_prefix . $store['id'];
        $seller_id_integration_vtex = $this->getExternalIdVtex($store);

        // Se o id externo que criamos manualmente for diferente do que já está cadastrado na vtex, vamos buscar pelo novo
        $id = ($externalId != $seller_id_integration_vtex) ? $seller_id_integration_vtex : $externalId;

        if ($this->pagarme_subaccounts_api_version == "5") {
            $resposta = $this->getRequest_v5($this->getUrlAPI_V5() . 'recipients/code/' . $id);
        } else {
            $resposta = $this->getRequest($this->getUrlAPI_V1() . '/recipients?external_id=' . $id);
        }

        if ($resposta) {
            $content = json_decode($resposta['content'], true);
        }

        if ($resposta && $resposta['httpcode'] == 200 && $content) {
            if ($this->pagarme_subaccounts_api_version == "5") {
                $resposta['content'] = json_encode($content);
            } else {
                $resposta['content'] = json_encode($content[0]);
            }
            return $resposta;
        }

        return null;
    }

    /**
     * @param string $transfer_type
     */
    public function processTransfers($transfer_type = null, string $chargeback_object = null, $user_command = false): void
    {
        echo date('Y-m-d H:i:S').' - Início do processamento processTransfers'.PHP_EOL;

        //Sempre mostrar todos os erros, estamos dentro de um ob_start, o log será salvo no banco de dados para consulta posterior
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $primary_account = $this->primary_account;

        if ($this->pagarme_subaccounts_api_version == "5") {
            $primary_account = $this->primary_account_v5;
        }

        if (!$transfer_type) {
            $transfer_type = 'BANK';
        }

        $chargeback_array = [];
        $array_valid_status = [];
        $reprocess_transfer = false;

        if ($chargeback_object) {
            $chargeback_array = json_decode($chargeback_object, true);
            $array_valid_status = [21, 25, 26];
            if (!is_array($chargeback_array)){
                echo "chargeback_array não é array, veio".PHP_EOL;
                var_dump($chargeback_array);
                echo "chargeback_object veio: ".PHP_EOL;
                var_dump($chargeback_object);
            }
            $reprocess_transfer = $chargeback_array['rep_row'];

            $conciliations = $this->_CI->model_conciliation->getOpenConciliations($this->gateway_id, $chargeback_array['conciliation_id'], $user_command);
        } else {
            $conciliations = $this->_CI->model_conciliation->getOpenConciliations($this->gateway_id, null, $user_command);
        }


        //Se não tem nenhum registro, não tem mais nada a fazer
        if (!$conciliations) {
			echo "Nenhuma conciliação para efetuar".PHP_EOL;
            return;
        }

        $current_day = date("j");

        foreach ($conciliations as $conciliation) {

            echo "INICIO do loop de conciliations".PHP_EOL;

            $transfer_error = 0;
            $conciliation_status = 23; // code for 100% success

            //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
            if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25) && count($chargeback_array) == 0 && !$user_command) {
                continue;
            }

			echo "\n\r \n\r Processando conciliação: {$conciliation['conciliacao_id']}" . PHP_EOL;

//			if (count($chargeback_array) == 0)
            if (!$chargeback_array['rep_row']) {
                $chargeback_array['stores'] = null;
            }

            $transfers_array = $this->_CI->model_transfer->getTransfers($conciliation['conciliacao_id'], $chargeback_array['stores']);

            if (!$transfers_array) {
                $this->_CI->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'], $conciliation_status);
                continue;
            }

            $transfers_sum = $this->generateArraySumByTransfers($transfers_array, $array_valid_status);

            foreach ($transfers_sum as $transfer)
			{
                $store = $this->_CI->model_stores->getStoreWithGatewaySubAccount(PaymentGatewayEnum::PAGARME, $transfer['store_id']);

                echo "INICIO do loop de transfers_sum".PHP_EOL;

                $payment_result = [];

                $subaccount = $this->_CI->model_gateway->getSubAccountByStoreId($transfer['store_id'], PaymentGatewayEnum::PAGARME);

                echo "Processando store_id={$transfer['store_id']}".PHP_EOL;

                $receiver = $this->regexOldId($subaccount['gateway_account_id']);

                if ($this->pagarme_subaccounts_api_version == "5" && !empty($subaccount['secondary_gateway_account_id'])) {
                    $receiver = $subaccount['secondary_gateway_account_id'];
                }

                if (!$receiver) {
					echo "Subconta não encontrada para store_id: {$transfer['store_id']}" . PHP_EOL;
                    $transfer_error++;

                    if ($reprocess_transfer) {
                        $payment_result['message'] = 'ERRO. Seller não possui cadastro de recebedor';

                        echo json_encode($payment_result, JSON_FORCE_OBJECT);
                    }

                    $this->_CI->model_repasse->setStoreTransferFail($transfer);

                    continue;
                }

                //cancellations
                if (isset($transfer['cancel_value']) && $transfer['cancel_value'] < 0 && !in_array($transfer['cancel_status'], [23, 33])) {

                    echo "Processando cancelamentos".PHP_EOL;

                    $amount = moneyToInt($transfer['cancel_value']);

                    $substatus_cancel = true; //status padrao para quando nao tem transferencias, que é o 33

                    if ($this->allow_transfer_between_accounts == '1' && $primary_account && $transfer['cancel_status'] == '43') {
                        $substatus_cancel = false; // existe transferencia entao um status 43 se converte em 23
                        echo "substatus_cancel = $substatus_cancel".PHP_EOL;
                    }

                    if ($this->allow_transfer_between_accounts == '1' && $primary_account && $transfer['cancel_status'] != '43') {
                        echo "Permite transferir entre carteiras e cancel_status != 43".PHP_EOL;
                        if ($this->transferFundsCancellation(['id' => $transfer['store_id'], 'conciliacao_id' => $transfer['conciliacao_id']], $receiver, $primary_account, $amount)) {
                            echo "Realizou transferFundsCancellation, cancel_id={$transfer['cancel_id']} id={$transfer['store_id']}, conciliacao_id={$transfer['conciliacao_id']}, amount=$amount".PHP_EOL;
                            //Atualizando na tabela repasse com status 23
                            $this->_CI->model_repasse->updateTransferStatus(true, $transfer['cancel_id']);
                            $payment_result['negative'] = 23;
                        } else {
                            echo "Não conseguiu realizar transferFundsCancellation, setando transfer_status false em cancel_id={$transfer['cancel_id']}".PHP_EOL;
                            $this->_CI->model_repasse->updateTransferStatus(false, $transfer['cancel_id']);
                            echo "Criando legal item: {$transfer['store_id']}, amount=$amount".PHP_EOL;
                            $this->createLegalItem($transfer['store_id'], abs($amount), $transfer['conciliacao_id']);
                            $payment_result['negative'] = 26;
                            $transfer_error++;
                        }
                    } else {
                        echo "Não tem transferência entre carteiras".PHP_EOL;
                        $this->_CI->model_repasse->updateTransferStatus(true, $transfer['cancel_id'], $substatus_cancel);
                        $payment_result['negative'] = 33;
                    }
                }

                //legal panel
                if (!empty($transfer['legal_id']) && !empty($transfer['legal_value'])) {

                    echo "Tem legal_id".PHP_EOL;

                    foreach ($transfer['legal_id'] as $key => $val) {

                        echo "Possui painel jurídico, ID: {$transfer['legal_id'][$key]}, value: {$transfer['legal_value'][$key]}".PHP_EOL;

                        if (in_array($transfer['legal_status'][$key], [23, 33])) {
                            echo "Status {$transfer['legal_status'][$key]} é 23 ou 33".PHP_EOL;
                            continue;
                        }

                        $amount = moneyToInt($transfer['legal_value'][$key]);

                        $substatus_legal = true; //status padrao para quando nao tem transferencias, que é o 33

                        if ($this->allow_transfer_between_accounts == '1' && $primary_account && $transfer['legal_status'][$key] == '43') {
                            $substatus_legal = false; // existe transferencia entao umstatus 43 se converte em 23
                        }

                        if ($this->allow_transfer_between_accounts == '1' && $primary_account && $transfer['legal_status'][$key] != '43') {
                            echo "legal_status != 43".PHP_EOL;
                            if ($this->transferFundsLegal(['id' => $transfer['store_id'], 'conciliacao_id' => $transfer['conciliacao_id']], $receiver, $primary_account, $amount)) {
                                echo "Fez Transferência de fundos de {$receiver} para conta primaria {$primary_account}, val = $val".PHP_EOL;
                                $this->_CI->model_repasse->updateTransferStatus(true, $val);
                                echo "Atualizou o status do repasse".PHP_EOL;
                                $this->_CI->model_repasse->updateTransferLegal($transfer['store_id'], round((abs($amount) / 100), 2), $transfer['legal_panel_id'][$key]);
                                echo "Atualizou o status do painel jurídico, setando legal_negative = 23".PHP_EOL;
                                $payment_result['legal_negative'] = 23;
                            } else {
                                echo "Não conseguiu realizar a transferência, setando status 26, val = $val".PHP_EOL;
                                $this->_CI->model_repasse->updateTransferStatus(false, $val);
                                $payment_result['legal_negative'] = 26;
                                $transfer_error++;
                            }
                        } else {
                            echo "Não permite transferir entre contas, setando legal_negative = 33".PHP_EOL;
                            $this->_CI->model_repasse->updateTransferStatus(true, $val, $substatus_legal);
                            $payment_result['legal_negative'][] = 33;
                        }
                    }
                }

                //actual transfer with updated math
                if ($transfer['orders_value'] <= 0) {
                    echo "Valores nos pedidos negativos".PHP_EOL;

                    if ($this->allow_transfer_between_accounts != "1") {
                        echo "Transferencia entre contas != 1".PHP_EOL;
                        $this->updateAllTransferStatus($transfer);

                        //cria o jurudico com o valor negativo, não havendo necessidade de gravar se for zero
                        if (moneyToInt($transfer['orders_value']) != 0) {
                            echo "Valor dos pedidos diferente de 0, vamos criar um painel jurídico".PHP_EOL;
                            $this->createLegalItem(
								$transfer['store_id'],
								(abs($transfer['orders_value']) * 100),
								$transfer['conciliacao_id'],
								'Repasse com total negativo',
								'Repasse com total negativo',
								'Rotina de Repasse',
								'Rotina de Repasse',
								$transfer['lote'],
								'Repasse com total negativo'
							);
                        }

						echo "\r\n \r\n Valor de Cancelamento R$ " . number_format(round(($amount / 100), 2), 2, ",", ".") . " e todos os itens do Painel Jurídico foram descontado do repasse e fechados. Este Repasse foi zerado e possivelmente foi gerado um novo item do Painel Jurídico para o próximo ciclo de pagamento." . PHP_EOL;

                        $payment_result['positive'][] = 33;

                    } else {
                        echo "Marcando como transferência não executada".PHP_EOL;
                        $this->updateAllTransferStatus($transfer, false, true);
                    }
                } else {

                    echo "Valores nos pedidos são positivos".PHP_EOL;

                    $amount = moneyToInt($transfer['orders_value']);

                    //caso rode para cancelamentos e juridicos enquanto os positivos ja foram pagos
                    if (isset($transfer['positive_status_repasse'])
                        && $transfer['positive_status_repasse'] == 23){
                        echo "caso rode para cancelamentos e juridicos enquanto os positivos ja foram pagos".PHP_EOL;
                        continue;
                    }

                    $balance = $this->_CI->model_gateway->getSellerBalance(['gateway_id' => PaymentGatewayEnum::PAGARME, 'store_id' => $transfer['store_id']]);

                    /**
                     * Se o valor líquido da conta do seller for o total a ser sacado ou tiver faltando a taxa de saque
                     * E tiver transferência entre carteiras ativado e a conta não é uma das contas com taxa 0,
                     * vamos injetar o valor faltante na conta para possibilitar efetuar o saque
                     */
                    if ($this->allow_transfer_between_accounts == '1' && $primary_account && $this->getTransferCost($receiver) && !$user_command){

                        echo "Verificando se a loja possui saldo suficiente para realizar o saque com taxa de saque.".PHP_EOL;

                        $totalFaltanteSacar = $amount + $this->getTransferCost($receiver) - $balance['available'];
                        if ($totalFaltanteSacar > 0){

                            echo "Falta {$totalFaltanteSacar} para realizar o saque de {$amount}, de um total disponível de {$balance['available']}".PHP_EOL;

                            if ($this->transferFunds($transfer, $primary_account, $receiver, $totalFaltanteSacar)) {

                                echo "Conseguimos efetuar a transferência do total faltante para a conta da loja com sucesso.".PHP_EOL;

                                $balance['available'] += $totalFaltanteSacar;

                                //Atualizando no banco de dados o balanço atual para prevenir que seja efetuado a transferência da diferença 2x
                                $this->_CI->model_gateway->updateSellerBalance($balance);

                            }else{
                                echo "Não conseguimos efetuar a transferência do total faltante para a conta da loja".PHP_EOL;
                            }

                        }


                    }

                    if ($this->createTransfer($transfer, $receiver, $amount, $transfer_type)) {
                        echo "Criado repasse com sucesso, valor de $amount para $receiver no tipo $transfer_type".PHP_EOL;
                        if ($this->allow_transfer_between_accounts == "1") {
                            echo "Permite transferir entre contas".PHP_EOL;
                            $this->updateAllTransferStatus($transfer, true, true);
                        } else {
                            echo "Não Permite transferir entre contas".PHP_EOL;
                            $this->updateAllTransferStatus($transfer);
                        }

                        $payment_result['legal_positive'] = 23;
                        $payment_result['positive'] = 23;

						echo "\r\n \r\n Sucesso no Repasse: Valor Líquido R$ " . number_format(round(($amount / 100), 2), 2, ",", ".") . " descontados possíveis cancelamentos e Painel Jurídico." . PHP_EOL;

                    } else {
                        echo "Não criou repasse".PHP_EOL;
                        if ($this->allow_transfer_between_accounts == "1") {
                            echo "Permite transferir entre contas".PHP_EOL;
                            $this->updateAllTransferStatus($transfer, false, true);
                        } else {
                            echo "Não Permite transferir entre contas".PHP_EOL;
                            $this->updateAllTransferStatus($transfer, false);
                        }

                        $payment_result['positive'] = 26;

						echo "\r\n \r\n Não foi possível realizar o Repasse de Valor Líquido R$ " . number_format(round(($amount / 100), 2), 2, ",", ".") . " O Gateway de pagamentos não retornou sucesso." . PHP_EOL;

                        $transfer_error++;
                    }

                    echo "Chamando função para atualizar o saldo da subconta".PHP_EOL;
                    $this->syncSingleSellerBalance($store);
                }

                echo "Chamando saveOrdersStatements".PHP_EOL;
                print_r($transfer);
                echo "---------------".PHP_EOL;
                $this->saveOrdersStatements($transfer, $transfer['orders_value']);

                echo "FIM do loop de transfers_sum".PHP_EOL;
            } //foreach


            $payment_result['liquid'] = round($transfer['orders_value'], 2);

            if ($reprocess_transfer) {

                echo "Deve reprocessar transferência".PHP_EOL;

                echo json_encode($payment_result, JSON_FORCE_OBJECT).PHP_EOL;

            } else if (is_array($chargeback_array)) {
                echo '$chargeback_array é um array'.PHP_EOL;
            }

            if ($transfer_error > 0) {
                $conciliation_status = 27; //code for processed with some errors
            }

            $this->_CI->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'], $conciliation_status);

            echo "Marcando conciliação id {$conciliation['conciliacao_id']} com o status $conciliation_status".PHP_EOL;

            echo "FIM do loop de conciliations".PHP_EOL;

        }//foreach inicial

        echo date('Y-m-d H:i:S')." - FIM DO PROCESSAMENTO de processTransfers".PHP_EOL;

    }

    public function transferFundsCancellation(array $transfer, string $from_recipient_id, string $to_recipient_id, int $amount): ?int
    {
        // gatilho para viabilizar testes
        if (ENVIRONMENT == 'development') {
            return 1;
        }

        return $this->transferFunds($transfer, $from_recipient_id, $to_recipient_id, $amount);
    }

    public function transferFunds(array  $transfer,
                                  string $from_recipient_id,
                                  string $to_recipient_id,
                                  int    $amount): ?int
    {

        echo "Chamando transferFunds".PHP_EOL;
        echo "Dump transfer: ".PHP_EOL;
        print_r($transfer);
        echo "----------------".PHP_EOL;
        echo "FROM: $from_recipient_id to: $to_recipient_id, Amount: $amount".PHP_EOL;

        $amount = '' . abs('' . $amount);

        $payload = [
            'amount' => $amount,
            'source_id' => $from_recipient_id,
            'target_id' => $to_recipient_id,
            'metadata' => $transfer,
        ];

        /**
         * @var Model_gateway_transfers $modelGatewayTransfers
         */
        $modelGatewayTransfers = $this->_CI->model_gateway_transfers;
        $gatewayId = $this->_CI->model_gateway->getGatewayId('pagarme');
        $transferId = $modelGatewayTransfers->createPreTransfer(
            $transfer['id'],
            $gatewayId,
            $to_recipient_id,
            $amount,
            'WALLET',
            $from_recipient_id
        );

        if ($this->pagarme_subaccounts_api_version == "5") {
            $url = $this->getUrlAPI_V5() . "/transfers/recipients";
            $transfer_response_json = $this->postRequest_v5($url, $payload);
        } else {
            $url = $this->getUrlAPI_V1() . "/transfers";
            $transfer_response_json = $this->postRequest($url, $payload);
        }

        echo "Dump da resposta da pagarme: ".PHP_EOL;
        print_r($transfer_response_json);
        echo "----------------".PHP_EOL;

        $responseCode = $transfer_response_json["httpcode"];
        $transfer_response_array = json_decode($transfer_response_json['content'], true);

        //Se tivemos resposta na faixa do 200 a transação ocorreu com sucesso
        if ($responseCode >= 200 && $responseCode < 300) {

            echo "Transferencia " . $transferId . ", de $from_recipient_id para $to_recipient_id " .
                "valor: $amount efetuada com sucesso." . PHP_EOL;

            if ($this->pagarme_subaccounts_api_version == "5") {
                $transfer_data = [
                    'result_status' => 1,
                    'sender_id' => $transfer_response_array['source']['source_id'],
                    'fee' => $transfer_response_array['fee'] ?? 0,
                    'amount' => $transfer_response_array['amount'],
                    'transfer_gateway_id' => $transfer_response_array['id'],
                    'status' => $transfer_response_array['status'],
                    'funding_estimated_date' => $transfer_response_array['funding_estimated_date'] ?? null,
                    'result_number' => $responseCode,
                    'request_data' => json_encode($payload),
                    'response_data' => $transfer_response_json['content'],
                ];

                //Salvando os dados da transferência na base
                $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);

                return 1;
            } else {
                $transfer_data = [
                    'result_status' => 1,
                    'sender_id' => $transfer_response_array['source_id'],
                    'fee' => $transfer_response_array['fee'] ?? 0,
                    'amount' => $transfer_response_array['amount'],
                    'transfer_gateway_id' => $transfer_response_array['id'],
                    'status' => $transfer_response_array['status'],
                    'funding_estimated_date' => $transfer_response_array['funding_estimated_date'] ?? null,
                    'result_number' => $responseCode,
                    'request_data' => json_encode($payload),
                    'response_data' => $transfer_response_json['content'],
                ];

                //Salvando os dados da transferência na base
                $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);

                return $transfer_response_array['id'];
            }


            //Ocorreu erro na transação
        } else {

            if ($this->pagarme_subaccounts_api_version == "5") {
                $error = $transfer_response_array['message'];
            } else {
                $error = json_encode($transfer_response_array['errors']);
            }

            $transfer_data = [
                'result_status' => 0,
                'status' => 'ERROR',
                'result_message' => $error,
                'result_number' => $responseCode,
                'request_data' => json_encode($payload),
                'response_data' => $transfer_response_json['content'],
            ];

            echo "Transferencia " . $transferId . ", conciliação id: {$transfer['conciliacao_id']}, " .
                "valor $amount, retornou erro: " . json_encode($transfer_response_array) . PHP_EOL;

            $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);
        }

        return null;

    }

    public function transferFundsLegal(array $transfer, string $from_recipient_id, string $to_recipient_id, int $amount): ?int
    {
        // gatilho para viabilizar testes
        if (ENVIRONMENT == 'development') {
            return 1;
        }

        return $this->transferFunds($transfer, $from_recipient_id, $to_recipient_id, $amount);
    }

    public function updateAllTransferStatus(array $transfer = null, $status = true, $positive = false)
    {

        echo "Entrou em updateAllTransferStatus".PHP_EOL;

        //positivos
        if (isset($transfer['multiple_positives'])) {
            echo "Multiplos positivos".PHP_EOL;
            echo "Dump transfer: ".PHP_EOL;
            print_r($transfer);
            echo "----------------".PHP_EOL;
            echo "Dump status: ".PHP_EOL;
            print_r($status);
            echo "----------------".PHP_EOL;
            foreach ($transfer['multiple_positives'] as $multiple_positive) {
                if ($multiple_positive['status_repasse'] != 23) {
                    echo "Status de repasse != 23".PHP_EOL;
                    $this->_CI->model_repasse->updateTransferStatus($status, $multiple_positive['id']);
                }
            }
        }

        //cancelamento
        if (isset($transfer['cancel_id']) && !$positive) {
            echo "Cancelamentos...".PHP_EOL;
            echo "Dump status: ".PHP_EOL;
            print_r($status);
            echo "----------------".PHP_EOL;
            echo "Dump transfer: ".PHP_EOL;
            print_r($transfer);
            echo "----------------".PHP_EOL;
            echo "Atualizando status da transferência".PHP_EOL;
            if ($status) {
                $this->_CI->model_repasse->updateTransferStatus($status, $transfer['cancel_id'], $status);
            } else {
                $this->_CI->model_repasse->updateTransferStatus($status, $transfer['cancel_id']);
            }
        }

        //juridicos negativos
        if (isset($transfer['legal_id']) && !$positive) {
            echo "Jurídicos negativos".PHP_EOL;
            echo "Dump transfer".PHP_EOL;
            print_r($transfer);
            echo "-----------------".PHP_EOL;
            foreach ($transfer['legal_id'] as $key => $legal_item) {
                echo "Dump Legal item: ".PHP_EOL;
                print_r($legal_item);
                echo "----------------".PHP_EOL;
                echo "Dump status: ".PHP_EOL;
                print_r($status);
                echo "----------------".PHP_EOL;
                echo "Dump legal item: ".PHP_EOL;
                print_r($legal_item);
                echo "----------------".PHP_EOL;
                if ($status) {
                    echo "Status positivo".PHP_EOL;
                    $this->_CI->model_repasse->updateTransferStatus($status, $legal_item, $status);
                    $this->_CI->model_repasse->updateTransferLegal(null, null, $transfer['legal_panel_id'][$key]);
                } else {
                    echo "Status negativo".PHP_EOL;
                    $this->_CI->model_repasse->updateTransferStatus($status, $legal_item);
                }
            }
        }

        //juridicos positivos
        if (isset($transfer['legal_panel_positives'])) {
            echo "Jurídicos positivos".PHP_EOL;
            echo "Dump transfer".PHP_EOL;
            print_r($transfer);
            echo "-----------------".PHP_EOL;

            foreach ($transfer['legal_panel_positives'] as $legal_positive) {
                echo "Dump legal_positive".PHP_EOL;
                print_r($legal_positive);
                echo "------------".PHP_EOL;
                if ($legal_positive['status_repasse'] != 23) {
                    echo "Status != 23".PHP_EOL;
                    $this->_CI->model_repasse->updateTransferStatus($status, $legal_positive['id']);
                }

                if ($status) {
                    echo "Status positivo..".PHP_EOL;
                    $this->_CI->model_repasse->updateTransferLegal($transfer['store_id'], null, $legal_positive['legal_panel_id']);
                }
            }
        }

        return true;
    }

    /**
     * @param array $transfer
     * @param string $receiver
     * @param int $amount
     * @param string $transfer_type
     * @return int|null
     */
    public function createTransfer(array $transfer, string $receiver, int $amount, string $transfer_type = 'BANK'): ?string
    {
        // gatilho para viabilizar testes
        if (ENVIRONMENT == 'development') {
            return 1;
        }

        echo "Chamando createTransfer para receiver $receiver, amount $amount".PHP_EOL;

        $transferIds = [];
//        $transferAmounts = [];
        $valid_status = [21, 25, 26];

        //Subtraindo o custo da transferência da conta no valor a ser transferido
        $amount = abs($amount - $this->getTransferCost($receiver));

        /**
         * @var Model_gateway_transfers $modelGatewayTransfers
         */
        $modelGatewayTransfers = $this->_CI->model_gateway_transfers;

        $gatewayId = $this->_CI->model_gateway->getGatewayId('pagarme');

        $gatewayTransferId = $modelGatewayTransfers->createPreTransfer($transfer['orders_id'], $gatewayId, $receiver, $amount, $transfer_type, $receiver);


        /**
         * Validando se o valor a ser transferido é >= que o minimo permitido pelo gateway
         */
        if ($amount < $this->minimunTransferValue && $transfer_type == 'BANK') {

            echo "Amount inferior ao minimo e é banco".PHP_EOL;

//            echo $error = "Transferência até " . intToMoney($this->getTransferCost(), 'R$ ') . " não permitida, " .
//                "Valor a transferir: " . intToMoney($amount, 'R$ ') . " " .
//                "(Valor a sacar: " . intToMoney($amount, 'R$ ') . " - taxa transferência: " . intToMoney($this->getTransferCost(), 'R$ ') . ") " .
//                "- recipient_id: $receiver" . PHP_EOL;

            $transfer_data = [
                'result_status' => 0,
                'status' => 'ERROR',
                'result_message' => $error,
                'result_number' => 0,
            ];

//			foreach ($transferIds as $transferId)
//			{
//				$this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);
            $this->_CI->model_gateway_transfers->saveTransfer($gatewayTransferId, $transfer_data);
//			}

            return null;
        }

//		$transferIds_str = implode('-', $transferIds);

        $transferDepositArray = [
            "amount" => $amount,
            "recipient_id" => $receiver,
            "metadata" => [
                'description' => 'Transfer Payment',
                "contiliation_id" => $transfer['conciliacao_id'],
//                "transfer_id" => $transferIds_str,
                "transfer_id" => $gatewayTransferId,
            ]
        ];

        $transferDepositArray_V5 = [
            "amount" => $amount,
            "metadata" => [
                'description' => 'Transfer Payment',
                "contiliation_id" => $transfer['conciliacao_id'],
//				"transfer_id" => $transferIds_str,
                "transfer_id" => $gatewayTransferId,
            ]
        ];

        if ($this->pagarme_subaccounts_api_version == "5") {
            $url = $this->getUrlAPI_V5() . 'recipients/' . $receiver . '/withdrawals';
        } else {
            $url = $this->getUrlAPI_V1() . '/transfers';
        }

        if ($this->pagarme_subaccounts_api_version == "5") {
            $transfer_response_json = $this->postRequest_v5($url, $transferDepositArray_V5);
        } else {
            $transfer_response_json = $this->postRequest($url, $transferDepositArray);
        }

        $responseCode = $transfer_response_json["httpcode"];
        $transfer_response_array = json_decode($transfer_response_json['content'], true);

        //bugs-3058
        //estou colocando um OK 200 no pagamento,para ver a validacao
//		$responseCode = 201;

        echo "Código do status do retorno: {$responseCode} |Corpo: {$transfer_response_json['content']}".PHP_EOL;

        //Se tivemos resposta na faixa do 200 a transação ocorreu com sucesso
        if ($responseCode >= 200 && $responseCode < 300) {

            echo "Retornou status 200, vamos salvar os dados da transferência.".PHP_EOL;

            //foreach ($transferIds as $key => $transferId)
            //{
//				echo "Transferencia " . $transferId . ", conciliação id: {$transfer['conciliacao_id']}, recipient_id: $receiver, " .
//				echo "Transferencia " . $gatewayTransferId . ", conciliação id: {$transfer['conciliacao_id']}, recipient_id: $receiver, " .
//					"valor: $amount efetuada com sucesso. Data estimada do pagamento: {$transfer_response_array['funding_estimated_date']}" . PHP_EOL;

            if ($this->pagarme_subaccounts_api_version == "5") {
                echo "Usa pagarme 5".PHP_EOL;
                $transfer_data = [
                    'result_status' => 1,
                    'sender_id' => $transfer_response_array['source']['source_id'],
                    'fee' => $transfer_response_array['fee'],
//						'amount' => $transferAmounts[$key],
//						'amount' => $transfer_response_array['amount'], //
                    'transfer_gateway_id' => $transfer_response_array['id'],
                    'status' => (isset($transfer_response_array['status'])) ? $transfer_response_array['status'] : 'ERROR',
                    'funding_estimated_date' => $transfer_response_array['funding_estimated_date'],
                    'result_number' => $responseCode,
                    'request_data' => json_encode($transferDepositArray_V5),
                    'response_data' => $transfer_response_json['content'],
                ];

            } else {

                echo "Usa pagarme 4".PHP_EOL;

                $transfer_data = [
                    'result_status' => 1,
                    'sender_id' => $transfer_response_array['source_id'],
                    'fee' => $transfer_response_array['fee'],
//						'amount' => $transferAmounts[$key],
//						'amount' => $transfer_response_array['amount'],
                    'transfer_gateway_id' => $transfer_response_array['id'],
                    //'status' => $transfer_response_array['status'],
                    'status' => (isset($transfer_response_array['status'])) ? $transfer_response_array['status'] : 'ERROR',
                    'funding_estimated_date' => $transfer_response_array['funding_estimated_date'],
                    'result_number' => $responseCode,
                    'request_data' => json_encode($transferDepositArray),
                    'response_data' => $transfer_response_json['content'],
                ];
            }

            //Salvando os dados da transferência na base
            $this->_CI->model_gateway_transfers->saveTransfer($gatewayTransferId, $transfer_data);
            echo "Salvou dados da transferência na base".PHP_EOL;
            //}

            //bugs-3058
            //retorna qq coisa para dar true

            echo "Vamos retornar o código: {$transfer_response_array['id']}".PHP_EOL;

            return $transfer_response_array['id'];
//            return 'teste_'.$receiver;

            //Ocorreu erro na transação
        } else {

            $error = json_encode($transfer_response_array);

            echo "Ocorreu um erro na transação: {$error}".PHP_EOL;

            $transfer_data = [
                'result_status' => 0,
                'status' => 'ERROR',
                'result_message' => $error,
                'result_number' => $responseCode,
                'request_data' => json_encode($transferDepositArray),
                'response_data' => $transfer_response_json['content'],
            ];

//			$balance = $this->getBalance($receiver);
//
//			if ($balance && $balance->available_amount)
//			{
//				if ($this->pagarme_subaccounts_api_version == "5") {
//					$available_amount = $balance->available_amount;
//				} else {
//					$available_amount = $balance->available->amount;
//				}
//			}
//			else
//			{
//				$available_amount = ' -- erro na consulta de saldo --';
//			}

//            echo "Transferencia(s) " . $transferIds_str . ", conciliação id: {$transfer['conciliacao_id']}, " .
//            echo "Transferencia(s) " . $gatewayTransferId . ", conciliação id: {$transfer['conciliacao_id']}, " .
//                "valor $amount, Saldo restante: $available_amount - retornou erro: " . json_encode($transfer_response_array) . PHP_EOL;

//			foreach ($transferIds as $transferId)
//			{
//				$this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);
            $this->_CI->model_gateway_transfers->saveTransfer($gatewayTransferId, $transfer_data);
//			}

//            echo $error . PHP_EOL;
        }

        echo "Retornando null por ter dado erro.".PHP_EOL;
        return null;
    }

    /**
     * @return int
     */
    public function getTransferCost($receiver = null): int
    {

        $this->transferCost = 0;

        if ($receiver){
            $storeId = $this->_CI->model_gateway->getStoreByGatewayId($receiver);
            $store = $this->_CI->model_stores->getStoresData($storeId[0]->store_id);
        }

        //Pode ser marketplace
        if (!$store){
            return $this->transferCost;
        }

        $pagarme_transfer_tax_active = $this->_CI->model_gateway->getGatewaySettingByNameAndGatewayCode('charge_seller_tax_pagarme', 2);

        if ($receiver){
            $banks_free = $this->_CI->model_gateway->getGatewaySettingByNameAndGatewayCode('banks_with_zero_fee', 2);

            //Se o banco não tem taxa, vamos retornar 0
            if ($banks_free && $banks_free->value){

                $banks_free_array = explode(';', $banks_free->value);
                $banks_free_array = array_filter($banks_free_array);
                if (in_array($store['bank'], $banks_free_array)){
                    return $this->transferCost;
                }
            }
        }

        //Se é para cobrar taxa do lojista
        if ($pagarme_transfer_tax_active->value == '1'){
            $pagarme_transfer_tax = $this->_CI->model_gateway->getGatewaySettingByNameAndGatewayCode('cost_transfer_tax_pagarme', 2);
            $this->transferCost = (int)onlyNumbers($pagarme_transfer_tax->value);
        }

        return $this->transferCost;

    }

    /**
     * @return array
     */
    public function getConfigurations(): ?array
    {
        $configurations = $this->getRequest($this->getUrlAPI_V1() . '/company')['content'];
        return ($configurations) ? json_decode($configurations, true) : null;
    }


//	public function getSellerChargeback($seller_id)
//	{
//		$url = $this->getUrlAPI_V1() . '/chargebacks';
//		$result = $this->getRequest($url);
//
//
//		if ($result['httpcode'] != 200 || !$result['content']) {
//			exit("Resposta código {$result["httpcode"]}, body: {$result['content']} na consulta do balanço do recipient_id: $recipientId");
//		}
//
//		return json_decode($result['content']);
//	}

    public function getBalanceUpdatedMinutes($gateway_id = null): int
    {
        $this->_CI->load->model('model_payment');

        if (!$gateway_id) {
            $gateway_id = $this->gateway_id;
        }

        $mktplace_data = $this->_CI->model_payment->getMktPlaceBalance($gateway_id);

        if (!$mktplace_data) {
            return false;
        }

        $diff = abs(strtotime(date('Y-m-d H:i:s')) - strtotime($mktplace_data['date_edit']));
        $total_minutes = floor($diff / 60);

        return ($total_minutes > 0) ? $total_minutes : 0;
    }

    public function gatewayUpdateBalance()
    {
        //braun hack -> fin-901
        //evitando inserir no job pois nao fica estavel.
        //if (ENVIRONMENT == 'development')
        //{
        return $this->syncSellersBalance();
        //}

        //return $this->runBatch('PagarMe/PagarmeBatch', 'gatewayUpdateBalance');
    }

    public function syncSellersBalance()
    {
        $mktplace_balance_data = [
            'gateway_id' => PaymentGatewayEnum::PAGARME,
            'unavailable' => 0,
            'available' => 0,
            'future' => 0
        ];

        if ($this->pagarme_subaccounts_api_version == "5") {
            $mkt_balance = $this->getBalance($this->primary_account_v5);
            
            if ($mkt_balance){
                $mktplace_balance_data['available'] = $mkt_balance->available_amount;
                $mktplace_balance_data['future'] = $mkt_balance->waiting_funds_amount;
            }

        } else {
            $mkt_balance = $this->getBalance($this->primary_account);
            
            if ($mkt_balance){
                $mktplace_balance_data['available'] = $mkt_balance->available->amount;
                $mktplace_balance_data['future'] = $mkt_balance->waiting_funds->amount;
            }

        }

        $this->_CI->model_gateway->updateMktBalance($mktplace_balance_data);

        //loop de atualização de saldos dos sellers
        $sellers = $this->_CI->model_stores->getStoresWithGatewaySubAccounts(PaymentGatewayEnum::PAGARME);

        foreach ($sellers as $seller) {
            $this->syncSingleSellerBalance($seller);
            usleep(250000);
        }

        return true;
    }

    public function syncSingleSellerBalance(array $seller)
    {

        //Viabilizar testes local
        if (ENVIRONMENT == 'development') {
            return 1;
        }

        if (!$seller){
            return;
        }

        echo "Inicializando a sincronização do balanço atual da conta {$seller['id']}".PHP_EOL;

        $receiver = $seller['gateway_account_id'];

        if ($this->pagarme_subaccounts_api_version == "5" && !empty($seller['secondary_gateway_account_id'])) {
            $receiver = $seller['secondary_gateway_account_id'];
        }

        if (!$receiver) {
            echo "Não tem conta pagarme".PHP_EOL;
            return;
        }

        $balance = $this->getBalance($receiver);

        if (!$balance) {
            echo "Não foi possível retornar o balanço da conta $receiver".PHP_EOL;
            return;
        }

        $available = $balance->available->amount ?? 0;
        $available = $balance->available_amount ?? $available;

        $waitingFunds = $balance->waiting_funds->amount ?? 0;
        $waitingFunds = $balance->waiting_funds_amount ?? $waitingFunds;

        if (!isset($balance->available) && !isset($balance->available_amount)){
            echo "Não retornou available na consulta".PHP_EOL;
            var_dump($balance);
            return;
        }

        $balance_data = array(
            'gateway_id' => PaymentGatewayEnum::PAGARME,
            'store_id' => $seller['id'],
            'available' => $available,
            'future' => $waitingFunds,
            'unavailable' => 0 //considerar usar a API de chargeback
        );

        echo "Atualizando saldo da conta para: ".json_encode($balance_data).PHP_EOL;

        //defesa para quando for mull, ou nao teve sucesso ao puxar o saldo, ele nao acabar por registrar zero, apenas continua no loop e deixa o valor antigo
        if (!is_null($balance_data['available']) && '' . $balance_data['available'] >= 0) {
            $this->_CI->model_gateway->updateSellerBalance($balance_data);
            echo "Saldo da conta atualizado no banco de dados.".PHP_EOL;
        }

    }

    /**
     * @param string $recipientId
     * @return stdClass
     */
    public function getBalance(string $recipientId): ?stdClass
    {

        if ($this->pagarme_subaccounts_api_version == "5") {
            $url = $this->getUrlAPI_V5() . '/recipients/' . $recipientId . '/balance';
            $result = $this->getRequest_v5($url);
        } else {
            $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . '/balance';
            $result = $this->getRequest($url);
        }

        if (isset($result['httpcode']) && ($result['httpcode'] != 200 || !$result['content'])) {
//			echo "Resposta código {$result["httpcode"]}, body: {$result['content']} na consulta do balanço do recipient_id: $recipientId";
            return null;
        }

        return json_decode($result['content']);
    }

    /**
     * @param string $recipientId
     * @return array
     */
    public function getTransferHistory(string $recipientId): array
    {

        if ($this->pagarme_subaccounts_api_version == "5") {

            $url = $this->getUrlAPI_V5() . '/recipients/' . $recipientId . '/withdrawals';
            $response = $this->getRequest_v5($url);
        } else {
            $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . '/balance/operations';
            $response = $this->getRequest($url);
        }

        return json_decode($response['content']);
    }

    public function getAntecipationsHistoryLast15Days(string $recipientId): array
    {

        echo "\n";
        $dateStart = subtractDateFromDays(15);
        $dateNow = dateNow();

        $page = 1;
        $count = 100;
        $loop = true;
        $return = [];

        while($loop){
            
            echo "[".date("Y-m-d H:i:s")."] - Executando a rotina para a loja ".$recipientId." na página ".$page."\n";

            $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . "/settlements?count=".$count."&page=".$page."&payment_date_start=>={$dateStart}&payment_date_end=<={$dateNow->format(DATE_INTERNATIONAL)}";
    
            $response = $this->getRequest($url);
    
            $transactions = json_decode($response['content'], true);
    
            if (isset($transactions['settlements']) && $transactions['settlements']) {
    
                foreach ($transactions['settlements'] as $transaction) {
    
                    if ($transaction['status'] == 'success' && $transaction['liquidation_type'] == 'external') {
                        $return[] = $transaction;
                    }
                }
            }else{
                $loop = false; 
            }
            $page++;

        }

        return $return;
    }

    public function confirmAnticipation(string $recipientId, string $anticipationSimulationId): array
    {

        $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . "/bulk_anticipations/$anticipationSimulationId/confirm";

        return $this->postRequest($url);
    }

    public function cancelAnticipation(string $recipientId, string $anticipationSimulationId): array
    {

        $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . "/bulk_anticipations/$anticipationSimulationId/cancel";

        return $this->postRequest($url);
    }

    public function generateTestCreditToRecipientId(float $amount, string $recipiendId): bool
    {

        $payload = '{
            "amount": ' . moneyToInt($amount) . ',
            "payment_method": "boleto",
            "customer": {
                "type": "individual",
                "country": "br",
                "name": "Daenerys Targaryen",
                "documents": [
                    {
                        "type": "cpf",
                        "number": "00000000000"
                    }
                ]
            },
            "split_rules": [
                {
                    "percentage": "100",
                    "recipient_id": "' . $recipiendId . '"
                }
            ]
        }';

        $payload = json_decode($payload, true);

        $result = $this->postRequest($this->getUrlAPI_V1() . '/transactions', $payload);

        //Se retornou qualquer coisa diferente de 200 é por que teve algum problema e não foi possível processar
        if ($result["httpcode"] != 200) {
            echo "Resposta código {$result["httpcode"]} na geração do boleto no valor de $amount. Erro: " . json_encode($result['content']) . PHP_EOL;
            return false;
        }

        $response_array = json_decode($result['content'], true);

        //Confirmando o pagamento do boleto para que entre o crédito
        $payloadPut = [];
        $payloadPut['status'] = 'paid';

        $url_put = $this->getUrlAPI_V1() . '/transactions/' . $response_array['id'];

        $response_put = $this->putRequest($url_put, $payloadPut);

        //Se retornou qualquer coisa diferente de 200 é por que teve algum problema e não foi possível processar
        if ($response_put["httpcode"] != 200) {
            echo "Resposta código {$response_put["httpcode"]} na confirmação do pagamento do boleto id {$response_array['id']}" . PHP_EOL;
            return false;
        }

        return true;
    }

    public function simulateAnticipation(string $recipientId, int $requestAmount, $timeframe = 'end', $build = false, $automaticTransfer = true): array
    {

        $paymentDate = $this->getNextWorkingDay();

        $paymentDateTimestamp = $paymentDate->getTimestamp() . '000';

        $url = $this->getUrlAPI_V1() . "/recipients/$recipientId/bulk_anticipations";

        $requestData = [
            'payment_date' => $paymentDateTimestamp,
            'timeframe' => $timeframe,
            'requested_amount' => $requestAmount,
            'build' => $build,
            'automatic_transfer' => $automaticTransfer,
        ];

        return $this->postRequest($url, $requestData);
    }

    public function getNextWorkingDay(): DateTime
    {

        $foundDate = false;

        $startingDate = dateNow();

        do {

            //If is before 11am and not saturday or sunday
            if ($startingDate->format('H') < 11 && !in_array($startingDate->format('w'), [0, 6])) {
                $foundDate = true;
            } else {
                $startingDate->add(new DateInterval("P1D"));
                $startingDate->setTime(8, 0, 0, 0);
            }
        } while ($foundDate == false);

        return $startingDate;
    }

    public function loadAnticipationsLimitsByRecipientId(string $recipientId, int $storeId): void
    {

        $paymentDate = new DateTime();
        $paymentDate->add(new DateInterval("P1D"));
        $paymentDate->setTime(8, 1, 0, 0);

        $antecipationLimits = $this->getAnticipationsLimits($recipientId, $paymentDate);

        if ($antecipationLimits) {

            $limits = [];
            $limits['store_id'] = $storeId;
            $limits['payment_date'] = $paymentDate->format(DATE_INTERNATIONAL);
            $limits['maximum_amount'] = intToDecimalDatabase($antecipationLimits['maximum']['amount']);
            $limits['maximum_anticipation_fee'] = intToDecimalDatabase($antecipationLimits['maximum']['anticipation_fee']);
            $limits['maximum_fee'] = intToDecimalDatabase($antecipationLimits['maximum']['fee']);
            $limits['minimum_amount'] = intToDecimalDatabase($antecipationLimits['minimum']['amount']);
            $limits['minimum_anticipation_fee'] = intToDecimalDatabase($antecipationLimits['minimum']['anticipation_fee']);
            $limits['minimum_fee'] = intToDecimalDatabase($antecipationLimits['minimum']['fee']);

            $this->_CI->model_anticipation_limits_store->create($limits);
        }
    }

    public function getAnticipationsLimits(string $recipientId, DateTime $paymentDate): array
    {

        $nextDayTimestamp = $paymentDate->getTimestamp();

        $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . "/bulk_anticipations/limits?timeframe=end&payment_date={$nextDayTimestamp}000";

        $response = $this->getRequest($url);

        $content = json_decode($response['content'], true);

        if (isset($content['maximum']) && isset($content['minimum'])) {
            return $content;
        }

        return [];
    }

    public function fetchAndUpdateAnticipationStatus(array $pending_anticipation, array $gatewaySubaccount): void
    {

        $anticipation = $this->getAnticipation($gatewaySubaccount['gateway_account_id'], $pending_anticipation['anticipation_id']);

        $content = json_decode($anticipation['content'], true);

        if ($pending_anticipation['anticipation_status'] != $content['0']['status']) {

            get_instance()->model_simulations_anticipations_store->update(
                ['anticipation_status' => $content['0']['status']],
                $pending_anticipation['id']
            );

            $ordersId = [];
            $orders = get_instance()->model_orders_simulations_anticipations_store->getAllBySimulationId($pending_anticipation['id']);
            foreach ($orders as $order) {
                $ordersId[] = $order['order_id'];
            }

            //Approved we must update to anticipated = 1
            if (AnticipationStatusEnum::APPROVED == $content['0']['status']) {
                get_instance()->model_orders_conciliation_installments->markOrdersAsAnticipated($ordersId, $pending_anticipation['store_id']);
            }

            //Repproved we must update to anticipated = 0 on
            if (AnticipationStatusEnum::REFUSED == $content['0']['status']) {
                get_instance()->model_orders_conciliation_installments->markOrdersAsAnticipated($ordersId, $pending_anticipation['store_id'], 0);
            }

        }

    }

    public function getAnticipation(string $recipientId, string $anticipation_id): array
    {

        $url = $this->getUrlAPI_V1() . '/recipients/' . $recipientId . "/bulk_anticipations?id=$anticipation_id";

        return $this->getRequest($url);
    }

    public function validateAuthData(): array
    {

        if ($this->pagarme_subaccounts_api_version == 5) {
            $response = $this->getRequest_v5($this->getUrlAPI_V5() . 'recipients/' . $this->primary_account_v5);
        }else{
            $response = $this->getRequest($this->getUrlAPI_V1() . '/recipients/' . $this->primary_account);
        }

        /*if ($response['httpcode'] === 401){
            return [
                'result' => 'error',
                'message' => 'Dados de autenticação inválidos'
            ];
        }
        if ($response['httpcode'] === 404){
            return [
                'result' => 'error',
                'message' => 'Dados de autenticação válidados, mas o código da conta primária não foi encontrado'
            ];
        }*/

        if ($response['httpcode'] === 200){
            return [
                'result' => 'success',
            ];
        }

        return [
            'result' => 'error',
            'message' => 'Resposta da Pagarme inesperada: ' . $response['httpcode'] . ' erro: ' . $response['content'],
        ];

        /*return [
            'result' => 'error',
            'message' => 'Resposta da Pagarme inesperada: '.$response['httpcode'],
        ];*/

    }

}
