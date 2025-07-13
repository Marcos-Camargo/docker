<?php

/** @noinspection PhpUndefinedFieldInspection */

use App\Libraries\Enum\PaymentGatewayEnum;

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Class PagarmeBatch
 */
class PagarmeBatch extends GenericBatch
{

    /**
     * @var PagarmeLibrary $integration
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
        $this->load->model('model_settings');
        $this->load->model('model_legal_panel');
        $this->load->model('model_anticipation_limits_store');
        $this->load->model('model_simulations_anticipations_store');

        //Libraries
        $this->load->library('PagarmeLibrary');

        //Starting Pagar.me integration library
        $this->integration = new PagarmeLibrary();
    }

    /**
     * @param null $id
     * @param null $params
     */
    public function runSyncStoresWithoutSubaccount($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id, $params);
        $this->syncSubAccounts(true, $id, $params);
        $this->endJob();

    }

    private function syncSubAccounts(bool $onlyNotCreatedAccount = true, $id = null, $params = null): void
    {

        //$this->startJob(__FUNCTION__, $id, $params);

        $gateway_name = Model_gateway::PAGARME;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $log_name = $this->logName;

        if ($onlyNotCreatedAccount) {
            if ($this->integration->pagarme_subaccounts_api_version == "5") {
                $stores = $this->model_stores->getStoresWithoutGatewaySubAccountsV5($gatewayId);
            } else {
                $stores = $this->model_stores->getStoresWithoutGatewaySubAccounts($gatewayId);
            }
        } else {
            $minutesToCheck = $this->model_settings->getValueIfAtiveByName('minutes_sincronize_stores_with_gateway_account');
            $stores = $this->model_stores->getStoresWithGatewaySubAccountsChangedLastXMinutes($minutesToCheck, $gatewayId);
        }

        foreach ($stores as $key => $store) {

            echo $key . ' - ' . $store['name'] . ' (' . $store['id'] . ')' . PHP_EOL;

            $errors = [];

            if ($store["bank"] == "") {
                $errors[] = 'Banco não encontrado';
            }

            if ($store["agency"] == "") {
                $errors[] = 'Agência não encontrada';
            }

            if ($store["account"] == "") {
                $errors[] = 'Conta Bancária não encontrada';
            }

            if (!$this->integration->isRegisteredIntoVtex($store)) {
                $errors[] = 'Loja ainda não cadastrada no Marketplace';
            }

            if ($errors) {

                $error = implode(', ', $errors);

                $this->log_data(
                    'batch',
                    $log_name,
                    "Não foi possível integrar a loja: {$store['id']} a pagarme. $error",
                    "E"
                );

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $error
                );

                continue;
            }

            $store['bank_number'] = $this->model_banks->getBankNumber($store['bank']);

            //Verificando se a loja já tem cadastro na pagar.me
            $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($store['id'], $gatewayId);
            //Antes de tentar cadastrar ou atualizar, vamos verificar se já foi cadastrado com o mesmo external_id na pagarme
            $responsePagarme = $this->integration->findRecipientByStore($store);
            if (isset($responsePagarme['content'])) {
                $responseContent = json_decode($responsePagarme['content'], true);
            }

            $contaAutoCadastradaPagarme = false;
            $contaAtualizada = false;
            //Se encontrou o registro na pagarme, e o id do recebedor é o mesmo, vamos atualizar ele com os novos dados

            if ($gatewaySubaccount && isset($responseContent)) {

                //Já está cadastrado, vamos atualizar
                if ($this->integration->pagarme_subaccounts_api_version == "5") {

                    $gatewaySubaccount['secondary_gateway_account_id'] = $responseContent['id'] ?? null;

                    $dadosMinimos = $this->model_settings->getStatusbyName('pagarme_v5_dados_minimos');

                    if($dadosMinimos){
                        if($store['rule_pagarme_bacen'] == 0){
                            $response = $this->integration->createUpdateRecipient_v5($store, $gatewaySubaccount);
                        }else{
                            $response = $this->integration->createUpdateRecipientBacen_v5($store, $gatewaySubaccount);
                        }
                    }else{
                        $response = $this->integration->createUpdateRecipient_v5($store, $gatewaySubaccount);
                    }

                    print_r($store);
                    print_r($response);




                    if ($response && $response['httpcode'] == "200") {
                        $contaAtualizada = true;
                    }

                } else {

                    $response = $this->integration->createUpdateRecipient_v4($store, $gatewaySubaccount);
                    if ($response && $response['httpcode'] == "200") {
                        $contaAtualizada = true;
                    }
                }

                $this->integration->validateSubaccountStatus($gatewaySubaccount, (int)$this->integration->pagarme_subaccounts_api_version);
            } elseif ($responsePagarme) {

                /**
                 * Não está cadastrado no banco ainda, mas já está na pagarme cadastrado pela vtex,
                 * vamos apenas atribuir a variável para vincular no banco
                 */

                $response = $responsePagarme;

                $contaAutoCadastradaPagarme = true;
            } else {

                //Não está cadastrado ainda, vamos cadastrar
                if ($this->integration->pagarme_subaccounts_api_version == "5") {

                    $dadosMinimos = $this->model_settings->getStatusbyName('pagarme_v5_dados_minimos');

                    if($dadosMinimos){
                        if($store['rule_pagarme_bacen'] == 0){
                            $response = $this->integration->createUpdateRecipient_v5($store);
                        }else{
                            $response = $this->integration->createUpdateRecipientBacen_v5($store);
                        }
                    }else{
                        $response = $this->integration->createUpdateRecipient_v5($store);
                    }

                } else {
                    $response = $this->integration->createUpdateRecipient_v4($store);
                }

                 print_r($store);
                 print_r($response);
            }

            if (!$response || !($response['httpcode'] == "200")) {  // created

                if ($response['httpcode'] == "502") {
                    $responseContent = "A atualização do modelo de antecipação não foi aprovado. Contate o suporte da pagarme.";
                } else {
                    $responseContent = json_decode($response['content']);
                    $responseContent = json_encode($responseContent, JSON_PRETTY_PRINT);
                }

                $requestContent = json_decode($response['reqbody']);
                $requestContent = json_encode($requestContent, JSON_PRETTY_PRINT);

                $msg = "Erro ao cadastrar recebedor na Pagar.me, Loja: " . $store['id']
                    . " httpcode: " . $response['httpcode'] . " " . PHP_EOL .
                    "Resposta da Pagar.me: " . PHP_EOL
                    . $responseContent . ' ' . PHP_EOL .
                    'Dados Fornecidos: ' . PHP_EOL
                    . $requestContent . PHP_EOL;

                $this->log_data('batch', $log_name, $msg, "E");

                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $msg
                );
            } elseif ($response) {

                $responseContent = json_decode($response['content'], true);

                $mensagem = $contaAutoCadastradaPagarme
                    ? 'Loja já cadastrada anteriormente, vinculado com id da subconta: ' . $responseContent['id']
                    : 'Loja cadastrada com sucesso, id da subconta: ' . $responseContent['id'];
                if ($contaAtualizada) {
                    $mensagem = "Loja atualizada com sucesso, id da subconta: " . $responseContent['id'];
                }
                $this->model_payment_gateway_store_logs->insertLog(
                    $store['id'],
                    $gatewayId,
                    $mensagem,
                    Model_payment_gateway_store_logs::STATUS_SUCCESS
                );

                $data = array(
                    "store_id" => $store['id'],
                    "gateway_id" => $gatewayId,
                    "with_pendencies" => $gatewaySubaccount['with_pendencies'] ?? 0
                );

                if ($this->integration->pagarme_subaccounts_api_version == "5") {
                    $data['secondary_gateway_account_id'] = $responseContent['id'];
                    $data['gateway_account_id'] = $responseContent['gateway_recipients'][0]['pgid'];
                    $data['bank_account_id'] = $responseContent['default_bank_account']['id'];
                } else {
                    $data['bank_account_id'] = $responseContent['bank_account']['id'];
                    $data['gateway_account_id'] = $responseContent['id'];
                }

                //Só vamos salvar uma nova conta caso ainda não o tenha
                if ($gatewaySubaccount) {

                    //Garantindo que nunca vai usar uma mesma id da subconta em 2 sellers, pois aqui não deve sofrer uma alteração de id
                    if (isset($data['gateway_account_id'])){
                        unset($data['gateway_account_id']);
                    }
                    if (isset($data['secondary_gateway_account_id'])){
                        unset($data['secondary_gateway_account_id']);
                    }

                    $this->model_gateway->updateSubAccounts($gatewaySubaccount['id'], $data);

                } else {

                    //Antes de cadastrar uma nova subconta, vamos validar se ela já foi usada anteriormente em outro seller diferente do seller atual
                    if ($this->model_gateway->countStoresWithGatewayIdDifferentFromOne($store['id'], PaymentGatewayEnum::PAGARME, $data['gateway_account_id']) > 0){

                        $mensagem = "Não foi possível cadastrar a loja na pagarme, pois retornou 
                        a id da subconta {$data['gateway_account_id']} e a mesma já está sendo utilizada por outra loja";
                        $this->model_payment_gateway_store_logs->insertLog(
                            $store['id'],
                            $gatewayId,
                            $mensagem,
                            Model_payment_gateway_store_logs::STATUS_ERROR
                        );

                    }else{

                        $id = $this->model_gateway->createSubAccounts($data);

                        $data['id'] = $id;
                        $this->integration->validateSubaccountStatus($data, (int)$this->integration->pagarme_subaccounts_api_version);

                    }

                }
            }
        }

        //$this->endJob();
    }

    /**
     * @param null $id
     * @param null $params
     */
    public function runSyncStoresWithSubaccounts($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id, $params);
        $this->syncSubAccounts(false, $id, $params);
        $this->endJob();
    }

    /**
     * @param bool $gateway
     * @param null $id
     * @param null $params
     */
    public function runPayments($id=null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id, $params);

        ob_start();

		$this->integration->processTransfers(null, $params);

        $resultado = ob_get_contents();
        ob_end_clean();

        $this->load->model('model_conciliation_transfers_log');
        $this->model_conciliation_transfers_log->create(['data' => $resultado]);

        $this->endJob();
    }

    public function runAntecipations($id = null, $params = null): void
    {

        $this->startJob(__FUNCTION__, $id, $params);

        $gateway_name = Model_gateway::PAGARME;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $stores = $this->model_stores->getStoresWithGatewaySubAccounts($gatewayId);

        if (!$stores) {
            $this->endJob();
            return;
        }

        foreach ($stores as $key => $store) {

            echo $key . ' - ' . $store['name'] . ' (' . $store['id'] . ')' . PHP_EOL;

            //Verificando se a loja já tem cadastro na pagar.me
            $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($store['id'], $gatewayId);

            $antecipationsHistory = $this->integration->getAntecipationsHistoryLast15Days($gatewaySubaccount['gateway_account_id']);

            if ($antecipationsHistory) {

                foreach ($antecipationsHistory as $antecipation) {

                    //Validando se a antecipação já foi salva no banco anteriormente
                    if (!$this->model_legal_panel->isNotificationStoreAlreadyRegistered($antecipation['id'], $store['id'])) {
                        $this->model_legal_panel->createDebitToStore(
                            $store['id'],
                            'Antecipação automática externa',
                            $antecipation['id'],
                            'Chamado Aberto',
                            json_encode($antecipation, JSON_PRETTY_PRINT),
                            $antecipation['amount'] / 100,
                            $antecipation['payment_date'],
                            'Rotina API'
                        );
                    }
                }
            }
        }

        $this->endJob();
    }

    public function fetchAnticipationsLimits($id = null, $params = null): void
    {

        $this->startJob(__FUNCTION__, $id, $params);

        $gateway_name = Model_gateway::PAGARME;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $stores = $this->model_stores->getStoresWithGatewaySubAccounts();

        if (!$stores) {
            return;
        }

        foreach ($stores as $key => $store) {

            //Verificando se a loja já tem cadastro na pagar.me
            $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($store['id'], $gatewayId);

            echo $key . ' - ' . $store['name'] . ' (' . $store['id'] . ') Recipient: ' . $gatewaySubaccount['gateway_account_id'] . PHP_EOL;

            if (!$gatewaySubaccount['gateway_account_id']) {
                echo "No gateway account id found" . PHP_EOL;
                continue;
            }

            $this->integration->loadAnticipationsLimitsByRecipientId($gatewaySubaccount['gateway_account_id'], $store['id']);
        }

        $this->endJob();
    }

    public function fetchAnticipationsStatus($id = null, $params = null): void
    {

        $this->startJob(__FUNCTION__, $id, $params);

        $gateway_name = Model_gateway::PAGARME;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $pending_anticipations = $this->model_simulations_anticipations_store->findPending();

        //Early return
        if (!$pending_anticipations) {
            return;
        }

        foreach ($pending_anticipations as $key => $pending_anticipation) {

            echo $key . ' - Store ID: ' . $pending_anticipation['store_id'] . ', Anticipation ID: ' . $pending_anticipation['anticipation_id'] . ', Amount: ' . $pending_anticipation['amount'] . ', Created At: ' . $pending_anticipation['created_at'] . PHP_EOL;

            $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($pending_anticipation['store_id'], $gatewayId);

            $this->integration->fetchAndUpdateAnticipationStatus($pending_anticipation, $gatewaySubaccount);
        }

        $this->endJob();
    }

    /**
     * @param null $id
     * @param null $params
     */
    public function runSyncStoresWithSubaccountsPendencies($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id);
        $this->syncSubAccountsWithPendencies(false, $id, $params);
        $this->endJob();
    }

    // Parametros mantidos no método para uma possível utilização futura
    private function syncSubAccountsWithPendencies(bool $onlyNotCreatedAccount = true, $id = null, $params = null): void
    {
        $gateway_name = Model_gateway::PAGARME;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $gateway_subaccounts = $this->model_gateway->getStoresWithPendencies($gatewayId);

        echo "\n\n[".date("Y-m-d H:i:s")."] - Inicio do Job Sincroniza Subcontas com Pendencias\n\n";

        foreach ($gateway_subaccounts as $key => $subaccount) {

            echo "Sincronizando loja: $subaccount->name - ID $subaccount->store_id\n";

            $data = [
                'id' => $subaccount->id,
                'store_id' => $subaccount->store_id,
                'secondary_gateway_account_id' => $subaccount->secondary_gateway_account_id,
                'gateway_account_id' => $subaccount->gateway_account_id,
                'with_pendencies' => $subaccount->with_pendencies,
            ];

            $this->integration->validateSubaccountStatus($data, (int)$this->integration->pagarme_subaccounts_api_version);

        }

        echo "\n[".date("Y-m-d H:i:s")."] - Fim do Job Sincroniza Subcontas com Pendencias\n\n";

    }


	public function gatewayUpdateBalance($id=null)
	{
		$this->startJob(__FUNCTION__, $id);
		$log_name = $this->logName;

		$this->integration->syncSellersBalance();

		echo 'Consulta de Saldo executada';

		$this->endJob();
	}
}
