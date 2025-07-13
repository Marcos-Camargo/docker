<?php
/** @noinspection PhpUndefinedFieldInspection */

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Class GetnetBatch
 */
class MagalupayBatch extends GenericBatch
{

    /**
     * @var GetnetLibrary $integration
     */
    private $integration;

    public function __construct()
    {

        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($logged_in_sess);

        //Models
        $this->load->model('model_gateway');
        $this->load->model('model_banks');
        $this->load->model('model_conciliation');
        $this->load->model('model_stores');
        $this->load->model('model_payment_gateway_store_logs');
        $this->load->model('model_payment');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_settings');
        $this->load->model('model_transfer');
        $this->load->model('model_orders');
        $this->load->model('model_company');
        $this->load->model('model_orders_payment');

        //Libraries
        $this->load->library('Magalupaylibrary');

        //Starting Pagar.me integration library
        $this->integration = new Magalupaylibrary();

    }

    /**
     * @param null $id
     * @param null $params
     */
    public function getaccesstokens(): void
    {
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job getaccesstokens\n";
        $gateway_name = Model_gateway::MAGALUPAY;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = "getaccesstokens";

        $response = $this->integration->getaccesstokengestaocarteira();
        
        if (!($response['httpcode'] == "200")) {  // created
            $responseContent = $response['content'];

            $msg = "Erro ao gerar token na Getnet, Loja: " . 0
                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                "Resposta da Getnet: " . PHP_EOL
                . $responseContent . ' ' . PHP_EOL .
                'Dados Fornecidos: ' . PHP_EOL
                . '' . PHP_EOL;

            // $this->log_data('batch', $log_name, $msg, "E");

            $this->model_payment_gateway_store_logs->insertLog(
                0,
                $gatewayId,
                $msg
            );

            echo "[".date("Y-m-d H:i:s")."] - Erro ao gerar credencial oob\n";

        } else {
            
            $responseContent = $response['content'];

            $this->model_payment_gateway_store_logs->insertLog(
                0,
                $gatewayId,
                'Token Gestão de Carteiras gerado com sucesso: ' . $responseContent->access_token,
                "W"
            );
            
            $this->model_gateway_settings->updateSettings('access_token_gc',$responseContent->access_token);
            $this->model_gateway_settings->updateSettings('id_token_gc',$responseContent->id_token);

            echo "[".date("Y-m-d H:i:s")."] - Credencial oob atualizada\n";

        }


        // Atualiza as variáveis de acesso na memória
        $this->integration->recarregacredenciais();

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job getaccesstokens\n";

    }

    public function runSyncStoresWithoutSubaccount($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id);
        $this->syncSubAccounts(true, $id, $params);
        $this->endJob();
    }

    private function syncSubAccounts(bool $onlyNotCreatedAccount = true, $id = null, $params = null): void
    {

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";
        // descomentar em PRD
         $this->getaccesstokens();

        //$this->startJob(__FUNCTION__, $id, $params);

        $gateway_name = Model_gateway::MAGALUPAY;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'syncSubAccounts';

        $stores = $this->model_stores->getStoresWithoutGatewaySubAccountsMagalupay();
        
        foreach ($stores as $key => $store) {
            
            echo "[".date("Y-m-d H:i:s")."] - Cadastrando Loja ".$store['id']." - ".$store['name']."\n";

            // Buscando as informações da empresa
            $company = $this->model_company->getCompanyData($store['company_id']);
            $store['bank_number'] = $this->model_banks->getBankNumber($store['bank']);

            $arrayInformacoes = array();
            $arrayInformacoes['store'] = $store;
            $arrayInformacoes['company'] = $company;
          

            $response = $this->integration->createRecipient($arrayInformacoes);
            $retornoAPI = json_decode(json_encode($response['content']), true);
            
            if (!($response['httpcode'] == "202")) {  // created

                echo "[".date("Y-m-d H:i:s")."] - Erro ao cadastrar Loja ".$store['id']." - ".$store['name']."\n";
                
                $msgErro = array();
                $i = 0;
                foreach($retornoAPI['detail'] as $retorno){
                    $msgErro[$i] = "Campo: ".implode("->",$retorno['loc']);
                    $msgErro[$i] .= " Tipo: ".$retorno['type'];
                    $msgErro[$i] .= " Mensagem: ".$retorno['msg'];
                    $i++;
                }

                $msg = "Erro ao cadastrar recebedor na MagaluPay, Loja: " . $store['id']
                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                    "Resposta da MagaluPay: " . PHP_EOL
                    . implode(PHP_EOL,$msgErro) . ' ' . PHP_EOL . PHP_EOL .
                    "Payload enviado: " . PHP_EOL
                    . $response['payload_request'];

                // $this->log_data('batch', $log_name, $msg, "E");

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $msg
                );

            } 
            elseif($this->model_gateway->countStoresWithGatewayIdDifferentFromOne($store['id'], PaymentGatewayEnum::MAGALUPAY, $retornoAPI['public_id']) > 0) 
            {
                echo "[" . date("Y-m-d H:i:s") . "] - Erro ao cadastrar Loja " . $store['id'] . " - " . $store['name'] . "\n";

                $msg = "Erro ao cadastrar recebedor na MagaluPay, Loja: " . $store['id'] .
                    "ID de subconta já registrado: " . $retornoAPI['public_id'] . PHP_EOL .
                    "Payload enviado: " . PHP_EOL
                    . $response['payload_request'];

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $msg
                );
            }
            else 
            {

                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao cadastrar Loja ".$store['id']." - ".$store['name']."\n";

                $retornoAPI = json_decode(json_encode($response['content']), true);

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    'Loja cadastrada com sucesso, aguardando a validação na MagaluPay '. PHP_EOL . PHP_EOL. "Payload enviado: " . PHP_EOL . $response['payload_request'],
                    "W"
                );

                $data = array(
                    "store_id" => $store['id'],
                    "public_id" => $retornoAPI['public_id'],
                    "reference_key" => $retornoAPI['recipient_config']['reference_key'],
                    "bank_account_document_number" => $retornoAPI['bank_account']['document_number'],
                    "bank_account_bank_code" => $retornoAPI['bank_account']['bank_code'],
                    "bank_account_bank_agency" => $retornoAPI['bank_account']['bank_agency'],
                    "bank_account_bank_agency_digit" => $retornoAPI['bank_account']['bank_agency_digit'],
                    "bank_account_account" => $retornoAPI['bank_account']['account'],
                    "bank_account_account_digit" => $retornoAPI['bank_account']['account_digit'],
                    "bank_account_bank_account_type" => $retornoAPI['bank_account']['bank_account_type'],
                    "recipient_config_auto_anticipate" => $retornoAPI['recipient_config']['auto_anticipate'],
                    "recipient_config_auto_transfer" => $retornoAPI['recipient_config']['auto_transfer'],
                    "recipient_config_transfer_periodicity" => $retornoAPI['recipient_config']['transfer_periodicity'],
                    "recipient_config_transfer_days" => implode(",",$retornoAPI['recipient_config']['transfer_days']),
                    "recipient_config_transfer_weekday" => $retornoAPI['recipient_config']['transfer_weekday'],
                    "terms_conditions_accept" => $retornoAPI['terms_conditions']['accept'],
                    "terms_conditions_fatca" => $retornoAPI['terms_conditions']['fatca'],
                    "webhook_url" => $retornoAPI['webhook_url'],
                    "create_bank_account" => $retornoAPI['create_bank_account'],
                    "create_seller_access" => $retornoAPI['create_seller_access'],
                    "reprove_source" => $retornoAPI['reprove_source'],
                    "reprove_reason" => $retornoAPI['reprove_reason'],
                    "auth_id" => $retornoAPI['auth_id'],
                    "product_key" => $retornoAPI['product_key'],
                    "document_number" => $retornoAPI['document_number'],
                    "status" => $retornoAPI['status'],
                    "person_type" => $retornoAPI['person_type'],
                    "created_at" => $retornoAPI['created_at'],
                    "updated_at" => $retornoAPI['updated_at'],
                    "analysis_id" => implode(",",$retornoAPI['analysis_id']),
                    "recipient_id" => $retornoAPI['recipient_id'],
                    "additional_step" => $retornoAPI['additional_step'],
                    "analysis" => implode(",",$retornoAPI['analysis']),
                    "info_pj_name" => $retornoAPI['info_pj']['name'],
                    "info_pj_company_name" => $retornoAPI['info_pj']['company_name'],
                    "info_pj_trading_name" => $retornoAPI['info_pj']['trading_name'],
                    "info_pj_phone_number" => $retornoAPI['info_pj']['phone_number'],
                    "info_pj_site" => $retornoAPI['info_pj']['site'],
                    "info_pj_email" => $retornoAPI['info_pj']['email'],
                    "info_pj_register_number" => $retornoAPI['info_pj']['register_number'],
                    "info_pj_address_zipcode" => $retornoAPI['info_pj']['address']['zipcode'],
                    "info_pj_address_street" => $retornoAPI['info_pj']['address']['street'],
                    "info_pj_address_complement" => $retornoAPI['info_pj']['address']['complement'],
                    "info_pj_address_number" => $retornoAPI['info_pj']['address']['number'],
                    "info_pj_address_city" => $retornoAPI['info_pj']['address']['city'],
                    "info_pj_address_district" => $retornoAPI['info_pj']['address']['district'],
                    "info_pj_address_state" => $retornoAPI['info_pj']['address']['state'],
                    "info_pj_legal_person_full_name" => $retornoAPI['info_pj']['legal_person']['full_name'],
                    "info_pj_legal_person_document_number" => $retornoAPI['info_pj']['legal_person']['document_number'],
                    "info_pj_legal_person_birth_date" => $retornoAPI['info_pj']['legal_person']['birth_date'],
                    "info_pj_legal_person_mother_full_name" => $retornoAPI['info_pj']['legal_person']['mother_full_name'],
                    "info_pj_legal_person_phone_number" => $retornoAPI['info_pj']['legal_person']['phone_number'],
                    "info_pj_legal_person_email" => $retornoAPI['info_pj']['legal_person']['email'],
                    "info_pj_legal_person_position" => $retornoAPI['info_pj']['legal_person']['position'],
                    "info_pj_business_category" => $retornoAPI['info_pj']['business_category']
                );

                $this->model_gateway->createSubAccountsMagaluPay($data);

            }

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim Job do Job\n";

        //$this->endJob();

    }


    public function checkstatussubaccount($id = null){
        $this->startJob(__FUNCTION__, $id);
        // descomentar em PRD
        $this->getaccesstokens();

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job\n";
        $gateway_name = Model_gateway::MAGALUPAY;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'callbacksubaccount';

        $stores = $this->model_stores->getStoresCheckStatusSubAccountsMagalupay();

        if($stores){
            foreach ($stores as $key => $store) {
                echo "[".date("Y-m-d H:i:s")."] - Gerando Check Status Subaccount na Loja ".$store['store_id']." - ".$store['info_pj_name']."\n";

                
                $response = $this->integration->checkstatussubaccount($store['public_id']);
                
                if (!($response['httpcode'] == "200")) {  // created

                    $retornoAPI = json_decode(json_encode($response['content']), true);
                    $msgErro = array();
                    $i = 0;
                    foreach($retornoAPI['detail'] as $retorno){
                        $msgErro[$i] = "Campo: ".implode("->",$retorno['loc']);
                        $msgErro[$i] .= " Tipo: ".$retorno['type'];
                        $msgErro[$i] .= " Mensagem: ".$retorno['msg'];
                        $i++;
                    }

                    $msg = "Erro ao buscar loja na MagaluPay, Loja: " . $store['store_id']." - ".$store['info_pj_name']
                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                        "Resposta da MagaluPay: " . PHP_EOL
                        . implode(PHP_EOL,$msgErro) . ' ' . PHP_EOL . PHP_EOL .
                        "Payload enviado: " . PHP_EOL
                        . $response['payload_request'];

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store['store_id'],
                        $gatewayId,
                        $msg
                    );

                    echo "[".date("Y-m-d H:i:s")."] - Erro ao atualizar loja ".$store['store_id']." - ".$store['info_pj_name']."\n";

                } else {

                    $retornoAPI = json_decode(json_encode($response['content']), true);
                    
                    if($retornoAPI['status'] == "APPROVED" ){

                        $msg = "Sucesso ao aprovar loja na MagaluPay, Loja: " . $store['store_id']." - ".$store['info_pj_name']
                            . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                            "Resposta da MagaluPay: " . PHP_EOL
                            . $retornoAPI['status'] . ' ' . PHP_EOL ;

                        $this->model_payment_gateway_store_logs->insertLog(
                            $store['store_id'],
                            $gatewayId,
                            $msg,
                            Model_payment_gateway_store_logs::STATUS_SUCCESS
                        );

                        $data = array(
                            "store_id" => $store['store_id'],
                            "gateway_account_id" => $retornoAPI['public_id'],
                            "gateway_id" => $gatewayId,
                            "bank_account_id" => 0
                        );

                        $this->model_gateway->createSubAccounts($data);

                        $data = array(
                            "store_id" => $store['store_id'],
                            "public_id" => $retornoAPI['public_id'],
                            "reference_key" => $retornoAPI['recipient_config']['reference_key'],
                            "bank_account_document_number" => $retornoAPI['bank_account']['document_number'],
                            "bank_account_bank_code" => $retornoAPI['bank_account']['bank_code'],
                            "bank_account_bank_agency" => $retornoAPI['bank_account']['bank_agency'],
                            "bank_account_bank_agency_digit" => $retornoAPI['bank_account']['bank_agency_digit'],
                            "bank_account_account" => $retornoAPI['bank_account']['account'],
                            "bank_account_account_digit" => $retornoAPI['bank_account']['account_digit'],
                            "bank_account_bank_account_type" => $retornoAPI['bank_account']['bank_account_type'],
                            "recipient_config_transfer_days" => implode(",",$retornoAPI['recipient_config']['transfer_days']),
                            "recipient_config_transfer_weekday" => $retornoAPI['recipient_config']['transfer_weekday'],
                            "terms_conditions_accept" => $retornoAPI['terms_conditions']['accept'],
                            "terms_conditions_fatca" => $retornoAPI['terms_conditions']['fatca'],
                            "webhook_url" => $retornoAPI['webhook_url'],
                            "create_bank_account" => $retornoAPI['create_bank_account'],
                            "create_seller_access" => $retornoAPI['create_seller_access'],
                            "reprove_source" => $retornoAPI['reprove_source'],
                            "reprove_reason" => $retornoAPI['reprove_reason'],
                            "auth_id" => $retornoAPI['auth_id'],
                            "product_key" => $retornoAPI['product_key'],
                            "document_number" => $retornoAPI['document_number'],
                            "status" => $retornoAPI['status'],
                            "person_type" => $retornoAPI['person_type'],
                            "created_at" => $retornoAPI['created_at'],
                            "updated_at" => $retornoAPI['updated_at'],
                            "analysis_id" => implode(",",$retornoAPI['analysis_id']),
                            "recipient_id" => $retornoAPI['recipient_id'],
                            "additional_step" => $retornoAPI['additional_step'],
                            "analysis" => implode(",",$retornoAPI['analysis'][0]),
                            "info_pj_name" => $retornoAPI['info_pj']['name'],
                            "info_pj_company_name" => $retornoAPI['info_pj']['company_name'],
                            "info_pj_trading_name" => $retornoAPI['info_pj']['trading_name'],
                            "info_pj_phone_number" => $retornoAPI['info_pj']['phone_number'],
                            "info_pj_site" => $retornoAPI['info_pj']['site'],
                            "info_pj_email" => $retornoAPI['info_pj']['email'],
                            "info_pj_register_number" => $retornoAPI['info_pj']['register_number'],
                            "info_pj_address_zipcode" => $retornoAPI['info_pj']['address']['zipcode'],
                            "info_pj_address_street" => $retornoAPI['info_pj']['address']['street'],
                            "info_pj_address_complement" => $retornoAPI['info_pj']['address']['complement'],
                            "info_pj_address_number" => $retornoAPI['info_pj']['address']['number'],
                            "info_pj_address_city" => $retornoAPI['info_pj']['address']['city'],
                            "info_pj_address_district" => $retornoAPI['info_pj']['address']['district'],
                            "info_pj_address_state" => $retornoAPI['info_pj']['address']['state'],
                            "info_pj_legal_person_full_name" => $retornoAPI['info_pj']['legal_person']['full_name'],
                            "info_pj_legal_person_document_number" => $retornoAPI['info_pj']['legal_person']['document_number'],
                            "info_pj_legal_person_birth_date" => $retornoAPI['info_pj']['legal_person']['birth_date'],
                            "info_pj_legal_person_mother_full_name" => $retornoAPI['info_pj']['legal_person']['mother_full_name'],
                            "info_pj_legal_person_phone_number" => $retornoAPI['info_pj']['legal_person']['phone_number'],
                            "info_pj_legal_person_email" => $retornoAPI['info_pj']['legal_person']['email'],
                            "info_pj_legal_person_position" => $retornoAPI['info_pj']['legal_person']['position'],
                            "info_pj_business_category" => $retornoAPI['info_pj']['business_category']
                        );

                        $this->model_gateway->updateSubAccountsMagaluPay($store['store_id'], $data);

                        echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['store_id']." - ".$store['info_pj_name']." atualizada com sucesso\n";

                        echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['store_id']." - ".$store['info_pj_name']." atualizada com sucesso\n";

                    }else{

                        if($retornoAPI['status'] == "REPROVED"){

                            $msg = "Erro de aprovação loja na MagaluPay, Loja: " . $store['store_id']." - ".$store['info_pj_name']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da MagaluPay: " . PHP_EOL
                                . $retornoAPI['status'] . ' - ' . $retornoAPI['reprove_reason'] . ' ' . PHP_EOL ;

                            $this->model_payment_gateway_store_logs->insertLog(
                                $store['store_id'],
                                $gatewayId,
                                $msg
                            );

                            $data = array(
                                "store_id" => $store['store_id'],
                                "public_id" => $retornoAPI['public_id'],
                                "reference_key" => $retornoAPI['recipient_config']['reference_key'],
                                "bank_account_document_number" => $retornoAPI['bank_account']['document_number'],
                                "bank_account_bank_code" => $retornoAPI['bank_account']['bank_code'],
                                "bank_account_bank_agency" => $retornoAPI['bank_account']['bank_agency'],
                                "bank_account_bank_agency_digit" => $retornoAPI['bank_account']['bank_agency_digit'],
                                "bank_account_account" => $retornoAPI['bank_account']['account'],
                                "bank_account_account_digit" => $retornoAPI['bank_account']['account_digit'],
                                "bank_account_bank_account_type" => $retornoAPI['bank_account']['bank_account_type'],
                                "recipient_config_transfer_days" => implode(",",$retornoAPI['recipient_config']['transfer_days']),
                                "recipient_config_transfer_weekday" => $retornoAPI['recipient_config']['transfer_weekday'],
                                "terms_conditions_accept" => $retornoAPI['terms_conditions']['accept'],
                                "terms_conditions_fatca" => $retornoAPI['terms_conditions']['fatca'],
                                "webhook_url" => $retornoAPI['webhook_url'],
                                "create_bank_account" => $retornoAPI['create_bank_account'],
                                "create_seller_access" => $retornoAPI['create_seller_access'],
                                "reprove_source" => $retornoAPI['reprove_source'],
                                "reprove_reason" => $retornoAPI['reprove_reason'],
                                "auth_id" => $retornoAPI['auth_id'],
                                "product_key" => $retornoAPI['product_key'],
                                "document_number" => $retornoAPI['document_number'],
                                "status" => $retornoAPI['status'],
                                "person_type" => $retornoAPI['person_type'],
                                "created_at" => $retornoAPI['created_at'],
                                "updated_at" => $retornoAPI['updated_at'],
                                "analysis_id" => implode(",",$retornoAPI['analysis_id']),
                                "recipient_id" => $retornoAPI['recipient_id'],
                                "additional_step" => $retornoAPI['additional_step'],
                                "analysis" => implode(",",$retornoAPI['analysis'][0]),
                                "info_pj_name" => $retornoAPI['info_pj']['name'],
                                "info_pj_company_name" => $retornoAPI['info_pj']['company_name'],
                                "info_pj_trading_name" => $retornoAPI['info_pj']['trading_name'],
                                "info_pj_phone_number" => $retornoAPI['info_pj']['phone_number'],
                                "info_pj_site" => $retornoAPI['info_pj']['site'],
                                "info_pj_email" => $retornoAPI['info_pj']['email'],
                                "info_pj_register_number" => $retornoAPI['info_pj']['register_number'],
                                "info_pj_address_zipcode" => $retornoAPI['info_pj']['address']['zipcode'],
                                "info_pj_address_street" => $retornoAPI['info_pj']['address']['street'],
                                "info_pj_address_complement" => $retornoAPI['info_pj']['address']['complement'],
                                "info_pj_address_number" => $retornoAPI['info_pj']['address']['number'],
                                "info_pj_address_city" => $retornoAPI['info_pj']['address']['city'],
                                "info_pj_address_district" => $retornoAPI['info_pj']['address']['district'],
                                "info_pj_address_state" => $retornoAPI['info_pj']['address']['state'],
                                "info_pj_legal_person_full_name" => $retornoAPI['info_pj']['legal_person']['full_name'],
                                "info_pj_legal_person_document_number" => $retornoAPI['info_pj']['legal_person']['document_number'],
                                "info_pj_legal_person_birth_date" => $retornoAPI['info_pj']['legal_person']['birth_date'],
                                "info_pj_legal_person_mother_full_name" => $retornoAPI['info_pj']['legal_person']['mother_full_name'],
                                "info_pj_legal_person_phone_number" => $retornoAPI['info_pj']['legal_person']['phone_number'],
                                "info_pj_legal_person_email" => $retornoAPI['info_pj']['legal_person']['email'],
                                "info_pj_legal_person_position" => $retornoAPI['info_pj']['legal_person']['position'],
                                "info_pj_business_category" => $retornoAPI['info_pj']['business_category']
                            );

                            $this->model_gateway->updateSubAccountsMagaluPay($store['store_id'], $data);

                            echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['store_id']." - ".$store['info_pj_name']." atualizada com sucesso\n";

                        }else{

                            $msg = "Aguardando aprovação loja na MagaluPay, Loja: " . $store['store_id']." - ".$store['info_pj_name']
                                . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                                "Resposta da MagaluPay: " . PHP_EOL
                                . $retornoAPI['status'] . ' - ' . $retornoAPI['reprove_reason'] . ' ' . PHP_EOL ;

                            //  $this->log_data('batch', $log_name, $msg, "W");

                            $this->model_payment_gateway_store_logs->insertLog(
                                $store['store_id'],
                                $gatewayId,
                                $msg,
                                "W"
                            );

                            echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['store_id']." - ".$store['info_pj_name']." atualizada com sucesso\n";

                        }

                    }

                }

            }
        }else{

            echo "[".date("Y-m-d H:i:s")."] - Nenhuma Loja a rodar o callback\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";
        $this->endJob();
    }

    public function runSyncStoresUpdated($id = null):void{
        $this->startJob(__FUNCTION__, $id);
        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job de atualização de lojas\n";

        // descomentar em PRD
         $this->getaccesstokens();

        $gateway_name = Model_gateway::MAGALUPAY;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'runSyncStoresUpdated';

        $stores = $this->model_gateway->getStoresForUpdatesSubAccountsMagaluPay($gateway_name);

        if($stores){
            foreach ($stores as $key => $store) {
                echo "[".date("Y-m-d H:i:s")."] - Atualizando Loja ".$store['id'].' - '.$store['name']."\n";

                $store['bank_number'] = $this->model_banks->getBankNumber($store['bank']);

                // Buscando as informações da empresa
                $company = $this->model_company->getCompanyData($store['company_id']);

                $arrayInformacoes = array();
                $arrayInformacoes['store'] = $store;
                $arrayInformacoes['company'] = $company;
            
                 $response = $this->integration->updateRecipient($arrayInformacoes);

                if (!($response['httpcode'] == "200")) {  // created

                    $retornoAPI = json_decode(json_encode($response['content']), true);
                    $msgErro = array();
                    $i = 0;
                    foreach($retornoAPI['detail'] as $retorno){
                        $msgErro[$i] = "Campo: ".implode("->",$retorno['loc']);
                        $msgErro[$i] .= " Tipo: ".$retorno['type'];
                        $msgErro[$i] .= " Mensagem: ".$retorno['msg'];
                        $i++;
                    }

                    $msg = "Erro ao atualizar a loja no MagaluPay, Loja: " . $store['store_id']." - ".$store['info_pj_name']
                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                        "Resposta da MagaluPay: " . PHP_EOL
                        . implode(PHP_EOL,$msgErro) . ' ' . PHP_EOL . PHP_EOL .
                        "Payload enviado: " . PHP_EOL
                        . $response['payload_request'];

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store['store_id'],
                        $gatewayId,
                        $msg
                    );

                    echo "[".date("Y-m-d H:i:s")."] - Erro ao atualizar loja ".$store['store_id']." - ".$store['info_pj_name']."\n";

                } else {

                    $retornoAPI = json_decode(json_encode($response['content']), true);

                    $msg = "Loja atualizada com sucesso, Loja: " . $store['store_id']
                        . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                        "Resposta da MagaluPay: " . PHP_EOL
                        . $responseContent . ' ' . PHP_EOL . PHP_EOL .
                        "Payload enviado: " . PHP_EOL
                        . $response['payload_request'];

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store['id'],
                        $gatewayId,
                        $msg,
                        Model_payment_gateway_store_logs::STATUS_SUCCESS
                    );

                    $data = array(
                            "store_id" => $store['store_id'],
                            "public_id" => $retornoAPI['public_id'],
                            "reference_key" => $retornoAPI['recipient_config']['reference_key'],
                            "bank_account_document_number" => $retornoAPI['bank_account']['document_number'],
                            "bank_account_bank_code" => $retornoAPI['bank_account']['bank_code'],
                            "bank_account_bank_agency" => $retornoAPI['bank_account']['bank_agency'],
                            "bank_account_bank_agency_digit" => $retornoAPI['bank_account']['bank_agency_digit'],
                            "bank_account_account" => $retornoAPI['bank_account']['account'],
                            "bank_account_account_digit" => $retornoAPI['bank_account']['account_digit'],
                            "bank_account_bank_account_type" => $retornoAPI['bank_account']['bank_account_type'],
                            "recipient_config_transfer_days" => implode(",",$retornoAPI['recipient_config']['transfer_days']),
                            "recipient_config_transfer_weekday" => $retornoAPI['recipient_config']['transfer_weekday'],
                            "terms_conditions_accept" => $retornoAPI['terms_conditions']['accept'],
                            "terms_conditions_fatca" => $retornoAPI['terms_conditions']['fatca'],
                            "webhook_url" => $retornoAPI['webhook_url'],
                            "create_bank_account" => $retornoAPI['create_bank_account'],
                            "create_seller_access" => $retornoAPI['create_seller_access'],
                            "reprove_source" => $retornoAPI['reprove_source'],
                            "reprove_reason" => $retornoAPI['reprove_reason'],
                            "auth_id" => $retornoAPI['auth_id'],
                            "product_key" => $retornoAPI['product_key'],
                            "document_number" => $retornoAPI['document_number'],
                            "status" => $retornoAPI['status'],
                            "person_type" => $retornoAPI['person_type'],
                            "created_at" => $retornoAPI['created_at'],
                            "updated_at" => $retornoAPI['updated_at'],
                            "analysis_id" => implode(",",$retornoAPI['analysis_id']),
                            "recipient_id" => $retornoAPI['recipient_id'],
                            "additional_step" => $retornoAPI['additional_step'],
                            "analysis" => implode(",",$retornoAPI['analysis'][0]),
                            "info_pj_name" => $retornoAPI['info_pj']['name'],
                            "info_pj_company_name" => $retornoAPI['info_pj']['company_name'],
                            "info_pj_trading_name" => $retornoAPI['info_pj']['trading_name'],
                            "info_pj_phone_number" => $retornoAPI['info_pj']['phone_number'],
                            "info_pj_site" => $retornoAPI['info_pj']['site'],
                            "info_pj_email" => $retornoAPI['info_pj']['email'],
                            "info_pj_register_number" => $retornoAPI['info_pj']['register_number'],
                            "info_pj_address_zipcode" => $retornoAPI['info_pj']['address']['zipcode'],
                            "info_pj_address_street" => $retornoAPI['info_pj']['address']['street'],
                            "info_pj_address_complement" => $retornoAPI['info_pj']['address']['complement'],
                            "info_pj_address_number" => $retornoAPI['info_pj']['address']['number'],
                            "info_pj_address_city" => $retornoAPI['info_pj']['address']['city'],
                            "info_pj_address_district" => $retornoAPI['info_pj']['address']['district'],
                            "info_pj_address_state" => $retornoAPI['info_pj']['address']['state'],
                            "info_pj_legal_person_full_name" => $retornoAPI['info_pj']['legal_person']['full_name'],
                            "info_pj_legal_person_document_number" => $retornoAPI['info_pj']['legal_person']['document_number'],
                            "info_pj_legal_person_birth_date" => $retornoAPI['info_pj']['legal_person']['birth_date'],
                            "info_pj_legal_person_mother_full_name" => $retornoAPI['info_pj']['legal_person']['mother_full_name'],
                            "info_pj_legal_person_phone_number" => $retornoAPI['info_pj']['legal_person']['phone_number'],
                            "info_pj_legal_person_email" => $retornoAPI['info_pj']['legal_person']['email'],
                            "info_pj_legal_person_position" => $retornoAPI['info_pj']['legal_person']['position'],
                            "info_pj_business_category" => $retornoAPI['info_pj']['business_category']
                        );

                        $this->model_gateway->updateSubAccountsMagaluPay($store['store_id'], $data);

                    echo "[".date("Y-m-d H:i:s")."] - Loja ".$store['id']." atualizada com sucesso\n";

                }


            }
        }else{

            echo "[".date("Y-m-d H:i:s")."] - Nenhuma Loja a atualizar\n";

        }

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";
        $this->endJob();
    }


    public function gerapagamento($idJob = null, $idConciliacao = null){

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Job gerapagamento\n";

        // Comentar em PRD
        $this->getaccesstokens();
        $contador = 0;

        $gateway_name = Model_gateway::MAGALUPAY;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = 'gerapagamento';

        $conciliations = $this->model_conciliation->getOpenConciliations(null,$idConciliacao);

        if($conciliations){

            foreach ($conciliations as $key => $conciliation) {

                $current_day = date("j");

                //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
                if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25 || $idConciliacao)) {
                    continue;
                }

                echo "[".date("Y-m-d H:i:s")."] - Processando conciliação: {$conciliation['conciliacao_id']}" . PHP_EOL;

                //Busca as linhas que serão pagas ou ajustadas
                $transferencias = $this->model_transfer->getTransfersConciliacao($conciliation['lote']);

                foreach($transferencias as $transferencia){

                    //Busca dados da conta
                    $subaccount = $this->model_stores->getallinformationsmagalupay($transferencia['store_id']);
                    $ordersPayment = $this->model_orders_payment->getByOrderId($transferencia['order_id']);

                    if(!$subaccount || !$ordersPayment){
                        echo "[".date("Y-m-d H:i:s")."] - O Pedido ". $transferencia['numero_marketplace'] ." não possui informçaões da subconta MagaluPay ou do Payment ID". PHP_EOL;
                        continue;
                    }

                    if($transferencia['repasse_tratado'] < 0){

                        //Chama API de Ajuste para descontar do seller
                        echo "[".date("Y-m-d H:i:s")."] - Processando o Pedido ". $transferencia['numero_marketplace'] ." por ajuste negativo de ". $transferencia['repasse_tratado']. PHP_EOL;
                        
                        $response = array();
                        $response['httpcode'] = 200;
                        //CHAMAR A API DE AJUSTES
                       
                        if (!($response['httpcode'] == "200")) {  // created

                            echo "[".date("Y-m-d H:i:s")."] - Erro ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." - ". $transferencia['repasse_tratado']. PHP_EOL;
                            print_r($response);
                        } else {

                            echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." - ". $transferencia['repasse_tratado']. PHP_EOL;

                        }

                    }else{
                        
                        //Chama API de transferencias para repassar do seller
                        $valorDiferencaRepasse = 0;
                        //$valorDiferencaRepasse = round( $transferencia['repasse_tratado'] - ($transferencia['repasse_tratado']/100) ,2);

                        echo "[".date("Y-m-d H:i:s")."] - Processando o debloqueio do Pedido ". $transferencia['numero_marketplace'] ." - ". $transferencia['repasse_tratado']. PHP_EOL;

                        $dadosTransferencia = array();
                        $dadosTransferencia['order_reference_key'] = $ordersPayment[0]['payment_id'];
                        $dadosTransferencia['recipient_reference_key'] = $subaccount['reference_key'];


                        $response = $this->integration->desbloqueiapedidomagalupay($dadosTransferencia);

                        dd($response);

                        if (!($response['httpcode'] == "200")) {  // created

                            $retornoAPI = json_decode(json_encode($response['content']), true);
                            $msgErro = array();
                            $i = 0;
                            foreach($retornoAPI['detail'] as $retorno){
                                $msgErro[$i] = "Campo: ".implode("->",$retorno['loc']);
                                $msgErro[$i] .= " Tipo: ".$retorno['type'];
                                $msgErro[$i] .= " Mensagem: ".$retorno['msg'];
                                $i++;
                            }
            
                            echo "[".date("Y-m-d H:i:s")."] - Erro ao desbloquear o Pedido ". $transferencia['numero_marketplace'] ." - ". $transferencia['repasse_tratado']. PHP_EOL.implode(PHP_EOL,$msgErro) ;

                            
                        } else {

                            $retornoAPI = json_decode(json_encode($response['content']), true);

                            $dadosIuguRepasse['order_id'] = $transferencia['order_id'];
                            $dadosIuguRepasse['numero_marketplace'] = $transferencia['numero_marketplace'];
                            $dadosIuguRepasse['data_split'] = $transferencia['data_pedido'];
                            $dadosIuguRepasse['valor_parceiro'] = $transferencia['repasse_tratado'];
                            $dadosIuguRepasse['conciliacao_id'] = $transferencia['conciliacao_id'];

                            $this->model_repasse->saveStatement($dadosIuguRepasse);

                            echo "[".date("Y-m-d H:i:s")."] - Sucesso ao desbloquear o Pedido ". $transferencia['numero_marketplace'] ." - ". $transferencia['repasse_tratado']. PHP_EOL;

                            if($valorDiferencaRepasse > 1){

                                $motivo = "Ajuste de saldo pós liberação do pedido".$transferencia['numero_marketplace'];

                                echo "[".date("Y-m-d H:i:s")."] - Sucesso ao ajustar o Pedido ". $transferencia['numero_marketplace'] ." - ". $transferencia['repasse_tratado']." em ".$valorDiferencaRepasse. PHP_EOL;
                                print_r($response);

                            } 

                        }

                    }
                   
                    $contador++;
                    if($contador == "200"){
                        // descomentar quanto for para PRD
                         $this->getaccesstokens();
                        $contador = 0;
                    }

                }

                //Muda para paga a conciliação
                $this->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'],'23');

            }

        }else{
            echo "[".date("Y-m-d H:i:s")."] - Nenhuma conciliação para efetuar\n";
        }
        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }

    public function geraextratopedidomagalupay(){

        ini_set('memory_limit', '3048M');

        // Comentar em PRD
        $this->getaccesstokens();

        echo "[".date("Y-m-d H:i:s")."] - Inicio do Job do Gera Extrato\n";

        $response = $this->integration->extratopedidomagalupay();
        
        if($response['httpcode']  == 200){

            $contentAPI = json_decode(json_encode($response['content']), true);

            foreach($contentAPI['items'] as $pedido){

                $data = array();
                $dataPagamento = "";

                $md5Chave = md5($pedido['reference_key'].$pedido['public_id'].$pedido['amount']);

                $metadata = "";
                if(is_array($pedido['metadata'])){
                    $metadata = implode("",$pedido['metadata']);
                }else{
                    $metadata = $pedido['metadata'];
                }

                $data['status'] = $pedido['status'];
                $data['amount'] = $pedido['amount'];
                $data['captured_at'] = $pedido['captured_at'];
                $data['created_at'] = $pedido['created_at'];
                $data['public_id'] = $pedido['public_id'];
                $data['reference_key'] = $pedido['reference_key'];
                $data['metadata'] = $metadata;
                $data['chave_md5'] = $md5Chave;

                $i = 1;
                foreach($pedido['payment_methods'] as $pagamentos){
                    $dataPagamento .= "Método de pagamento $i"." - ".$pagamentos['payment_method_name']." / ".$pagamentos['payment_method_reference_key']." / ".$pagamentos['card_brand_name']." ";
                    $i++;
                }

                $data['payment_methods'] = $dataPagamento;


                $insert = $this->model_gateway->savaextratomagalupay($data);

                if($insert){
                    echo "[".date("Y-m-d H:i:s")."] - Pedido".$pedido['reference_key']." inserido/atualizado com sucesso\n";
                }else{
                    echo "[".date("Y-m-d H:i:s")."] - Erro ao inserido/atualizado o Pedido".$pedido['reference_key']."\n";
                }

            }

        }

        //Busca as informaçõe do split depois de atualizada a base
        $this->geraextratosplitmagalupay();

       

        echo "[".date("Y-m-d H:i:s")."] - Fim do Job\n";

    }


    public function geraextratosplitmagalupay(){

        ini_set('memory_limit', '3048M');

        // Comentar em PRD
        $this->getaccesstokens();


    }


}
