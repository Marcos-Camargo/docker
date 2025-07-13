<?php

use App\Libraries\Enum\DiscountTypeEnum;
use App\Libraries\FeatureFlag\FeatureManager;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_integrations $model_integrations
 * @property Model_vtex_payment_methods $model_vtex_payment_methods
 * @property Model_vtex_trade_policy $model_vtex_trade_policy
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_campaigns_v2_occ_campaigns $model_campaigns_v2_occ_campaigns
 * @property Model_campaigns_v2_payment_methods $model_campaigns_v2_payment_methods
 * @property Model_campaigns_v2_trade_policies $model_campaigns_v2_trade_policies
 * @property Model_campaigns_v2_products $model_campaigns_v2_products
 * @property Model_campaign_v2_occ_campaigns_logs $model_campaign_v2_occ_campaigns_logs
 * @property Model_campaigns_v2_marketplaces $model_campaigns_v2_marketplaces
 */
class OccCampaigns extends MY_Controller
{
    protected $occ_integrations;
    protected $result;
    protected $response_code;


    public function __construct($params = array())
    {

        if (!FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            exit('Feature disabled');
        }

        $this->load->model('model_integrations');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_campaigns_v2_occ_campaigns');
        $this->load->model('model_campaigns_v2_payment_methods');
        $this->load->model('model_campaigns_v2_products');
        $this->load->model('model_vtex_payment_methods');
        $this->load->model('model_products');
        $this->load->model('model_campaign_v2_occ_campaigns_logs');
        $this->load->model('model_campaigns_v2_marketplaces');
        $this->load->model('model_settings');

        $intTo = count($params) > 0 && isset($params['intTo']) ? $params['intTo'] : null;

        $market_places = $this->model_integrations->getIntegrationsbyStoreIdActive(0, $intTo);

        if (is_array($market_places) && !empty($market_places)) {
            foreach ($market_places as $key => $market_place) {
                if (is_array($market_place)) {
                    $auth_data = json_decode($market_place['auth_data'], true);
                    if (isset($auth_data['site']) && strstr($auth_data['site'], 'oraclecloud.com')) {
                        $market_places[$key]['auth_data'] = $auth_data;
                    }
                }
            }

            foreach ($market_places as $key => $market_place) {
                $this->occ_integrations[$market_place['int_to']] = $market_place;
            }
        }

    }

    /**
     * Método mágico para utilização do CI_Controller.
     *
     * @param string $var Propriedade para consulta.
     * @return  mixed           Objeto da propriedade.
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }

    /**
     * @param $mktplace_data
     * @param  array  $error
     * @return void
     */
    private function importPaymentMethodsByPaymentSystems($mktplace_data, array &$error): void
    {

        $payment_methods_json = $this->occTransactions('ccstorex/custom/api/v1/payment/methods', $mktplace_data);
        $payment_methods_array = json_decode($payment_methods_json, true);

        if ($payment_methods_array) {

            $payment_methods = []; //reseto o array para cada nova iteração

            foreach ($payment_methods_array as $method) {

                echo "Validando Método: {$method['id']}, Is available: ".$method['isAvailable'].PHP_EOL;

                if ($method['isAvailable'] != 1) {
                    continue;
                }

                $payment_methods[] = [
                    'int_to' => $mktplace_data['int_to'],
                    'method_id' => $method['id'],
                    'method_name' => $method['name'],
                    'method_description' => $method['gateway'],
                ];

            }

            if ($payment_methods) {

                $ids_imported = [];

                foreach ($payment_methods as $occ_data) {
                    $ids_imported[] = $occ_data['method_id'];

                    if ($item = $this->model_vtex_payment_methods->getPaymentMethod($occ_data)){
                        echo "Ativando método: {$item['method_id']}".PHP_EOL;
                                    $occ_data['active'] = 1;
                                    $this->model_vtex_payment_methods->update($occ_data, $item['id']);
                    }else{
                        echo "Cadastrando o método method_id: {$occ_data['method_id']}".PHP_EOL;
                        $this->model_vtex_payment_methods->vtexInsertUpdatePaymentMethods($occ_data);
                    }

                }

                $this->model_vtex_payment_methods->vtexPaymentMethodsInactive($mktplace_data['int_to'], $ids_imported);

            } else {
                $error[] = "Métodos de Pagamento Occ Vazios (" . $this->response_code . " || " . $this->result . ") \r\n";
            }
        }

    }

    /**
     * @author Dilnei @todo revisado
     * @return false|void
     */
    public function updatePaymentMethods()
    {
        $error = [];

        if (is_array($this->occ_integrations) && !empty($this->occ_integrations)) {
            foreach ($this->occ_integrations as $mktplace_data) {
                if (isset($mktplace_data['auth_data']['apikey'])) {
                    $this->importPaymentMethodsByPaymentSystems($mktplace_data, $error);
                }
            }

        } else {
            echo $error[] = "Configurações Occ com erro ou inexistentes (" . $this->occ_integrations . ") \r\n";
        }

        if (!empty($error)) {
            echo implode("\r\n", $error);
            $this->log_data('batch', __FUNCTION__, serialize($error));
            return false;
        } else {
            echo "Atualização de Métodos de Pagamento Occ concluída com sucesso. \r\n";
        }

    }

    private function inactivateCampaign(int $campaignId)
    {
        $this->db->trans_begin();
        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);

        //If campaign type is vtex, first we need to inactivate in vtex
        if ($campaign['occ_campaign_update'] > 0){
            $this->archiveUnarchiveCampaign($campaign['id'], false);
        }

        $this->model_campaigns_v2->update(['active' => 0], $campaignId);

        if ($campaign['b2w_type'] == 0){
            $this->setDateUpdateAllProductsInCampaign($campaignId);
        }

        $this->db->trans_commit();

    }

    public function setDateUpdateAllProductsInCampaign(int $campaignId): void
    {
        $products = $this->model_campaigns_v2_products->getProductsByCampaign($campaignId);
        if ($products){
            foreach ($products as $product){
                $this->model_products->setDateUpdatedProduct($product['product_id'], null, __METHOD__);
            }
        }
    }

    protected function auth($endPoint, $authToken)
    {

        $this->header = [
            'content-type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $authToken,
        ];

        $url = 'https://' . $endPoint . '/ccadmin/v1/login?grant_type=client_credentials';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);

        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        $result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
        $result = json_decode($result);
        return $result->access_token;
    }

    protected function occTransactions($endpoint = null, $mktplace_data = null, $method = 'GET', $data = null, $campaign_v2_id = null, $echo = true)
    {
        if (empty($endpoint) || empty($mktplace_data)) {
            if ($echo){
                echo "Endpoint ou dados do marketplace estão vazios." . PHP_EOL;
            }
            return false;
        }

        $attempts = 0;
        $maxAttempts = 10;
        $retryInterval = 10; // 10 segundos

        if ($echo){
            echo "Iniciando a execução da função occTransactions." . PHP_EOL;
        }

        do {
            $attempts++;
            if ($echo){
                echo "Tentativa: $attempts de $maxAttempts." . PHP_EOL;
            }

            if (is_array($mktplace_data)){
                $credentials = $this->auth($mktplace_data['auth_data']['site'], $mktplace_data['auth_data']['apikey']);
            }else{
                $credentials = $mktplace_data;
            }

            if ($echo){
                echo "Autenticação concluída. Credenciais obtidas." . PHP_EOL;
            }

            $this->header = [
                'content-type: application/json; charset=UTF-8',
                'Authorization: Bearer ' . $credentials,
                'X-CCAsset-Language: pt-BR'
            ];

            if ($echo){
                echo "Cabeçalhos definidos." . PHP_EOL;
            }

            $url = $endpoint;
            if (!strstr($endpoint, 'http')){
                $url = 'https://' . $mktplace_data['auth_data']['site'] . '/' . $endpoint;
            }

            if ($echo){
                echo "URL definida: $url" . PHP_EOL;
            }

            if ($campaign_v2_id){
                $log_id = $this->model_campaign_v2_occ_campaigns_logs->create(
                    [
                        'campaign_v2_id' => $campaign_v2_id,
                        'conectala_request' => json_encode([
                            'endpoint' => $endpoint,
                            'header' => $this->header,
                            'body' => $data,
                        ])
                    ]
                );
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
                if ($echo){
                    echo "Método POST configurado." . PHP_EOL;
                }
            }

            if ($method == 'PUT') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($echo){
                    echo "Método PUT configurado." . PHP_EOL;
                }
            }

            if ($data){
                if ($echo){
                    echo "Corpo sendo enviado: ".json_encode($data).PHP_EOL;
                }
            }
            if ($echo){
                echo "Executando cURL..." . PHP_EOL;
            }
            $this->result = curl_exec($ch);
            $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($echo){
                echo "Resposta do cURL: " . ($this->result ? "Sucesso" : "Erro") . PHP_EOL;
                echo "Código de resposta HTTP: $this->responseCode" . PHP_EOL;
                echo "Corpo da resposta: " . $this->result . PHP_EOL;
            }

            if ($campaign_v2_id){
                $this->model_campaign_v2_occ_campaigns_logs->update(
                    [
                        'occ_response_code' => $this->responseCode,
                        'occ_response_body' => $this->result
                    ],
                    $log_id
                );
            }

            if ($this->responseCode >= 200 && $this->responseCode < 300) {
                if ($echo){
                    echo "Requisição bem-sucedida na tentativa $attempts." . PHP_EOL;
                }
                return $this->result; // Sucesso
            }

            $logMessage = '';
            if ($this->responseCode == 429) {
                $logMessage = "Muitas requisições já enviadas (HTTP 429). Tentativa $attempts de $maxAttempts";
            } elseif ($this->responseCode == 504) {
                $logMessage = "Deu Timeout (HTTP 504). Tentativa $attempts de $maxAttempts";
            } elseif ($curlError) {
                $logMessage = "Erro no cURL: $curlError. Tentativa $attempts de $maxAttempts";
            }

            if (!empty($logMessage)) {
                $this->log($logMessage);
                if ($echo){
                    echo $logMessage . PHP_EOL; // Imprime o log na tela
                }
            }

            if ($attempts < $maxAttempts) {
                if ($echo){
                    echo "Esperando $retryInterval segundos antes de tentar novamente..." . PHP_EOL;
                }
                sleep($retryInterval); // Espera 10 segundos antes de tentar novamente
            }

        } while ($attempts < $maxAttempts);

        $finalLogMessage = "Falha após $maxAttempts tentativas.";
        $this->log($finalLogMessage);

        if ($echo){
            echo $finalLogMessage . PHP_EOL; // Imprime a mensagem final na tela
        }

        return false; // Falha após atingir o máximo de tentativas
    }

    protected function authSessions($endpoint, string $username, string $password)
    {

        $this->header = [
            'content-type: application/json; charset=UTF-8',
            'X-CCAsset-Language: pt-BR'
        ];

        $data = [
            'username' => $username,
            'password' => $password
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $this->result = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($this->responseCode >= 200 && $this->responseCode < 300) {
            return $this->result; // Sucesso
        }

        $logMessage = '';
        if ($this->responseCode == 429) {
            $logMessage = "Muitas requisições já enviadas (HTTP 429).";
        } elseif ($this->responseCode == 504) {
            $logMessage = "Deu Timeout (HTTP 504).";
        } elseif ($curlError) {
            $logMessage = "Erro no cURL: $curlError.";
        }

        if (!empty($logMessage)) {
            $this->log($logMessage);
        }

        return false; // Falha após atingir o máximo de tentativas

    }

    public function log_data($action, $value, $tipo = 'E')
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (!empty($_SERVER['REMOTE_ADDR']))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "NONE";

        $datalog = array(
            'user_id' => 1,
            'company_id' => 1,
            'store_id' => 1,
            'module' => 'batch',
            'action' => $action,
            'ip' => $ip,
            'value' => $value,
            'tipo' => $tipo
        );

        $insert = $this->db->insert('log_history_batch', $datalog);

        return $insert == true;

    }

    public function sincronizeCampaigns(): void
    {
        $pending_campaigns = $this->model_campaigns_v2->getPendingOccCampaignsToSincronizeWithOcc();

        if (!$pending_campaigns) {
            //Early return
            echo "No pending campaign found" . PHP_EOL;
            return;
        }

        foreach ($pending_campaigns as $pending_campaign) {

            echo "Updating campaign: {$pending_campaign['id']}" . PHP_EOL;

            $campaigns_v2_payment_methods = $this->model_campaigns_v2_payment_methods->getIntToFromCampaign($pending_campaign['id']);

            if (!$campaigns_v2_payment_methods) {
                echo "No payment options found for campaign: {$pending_campaign['id']}" . PHP_EOL;
                continue;
            }


            if (isset($campaigns_v2_payment_methods[0]['int_to'])){
                $occ_campaign_int_to = $campaigns_v2_payment_methods[0]['int_to'];
            }

            if (!is_array($this->occ_integrations[$occ_campaign_int_to])) {
                echo "No integrations found." . PHP_EOL;
                //Early return
                continue;
            }


            try {

                $occ_campaigns = $this->factoryOccCampaign($pending_campaign);

                $mktplace_data = $this->occ_integrations[$occ_campaign_int_to];
                $authToken =  $this->authSessions(
                    $mktplace_data['auth_data']['campaigns_api_url'].'sessions',
                    $mktplace_data['auth_data']['campaigns_api_username'],
                    $mktplace_data['auth_data']['campaigns_api_password']
                );

                if (!is_string($authToken)) {
                    echo "Falha ao gerar token de acesso." . PHP_EOL;
                    exit();
                }

                $authToken = json_decode($authToken, true);
                $authToken = $authToken['token'];

                foreach ($occ_campaigns as $occ_campaign){

                    $endpoint = $mktplace_data['auth_data']['campaigns_api_url'].'campaigns';
                    $method = 'POST';
                    if (isset($occ_campaign['id'])){
                        $endpoint .= '/'.$occ_campaign['id'];
                        $method = 'PUT';
                    }

                    $occ_result = $this->occTransactions(
                        $endpoint,
                        $authToken,
                        $method,
                        $occ_campaign,
                        $pending_campaign['id']
                    );

                    //Early return
                    if (!$occ_result) {
                        continue;
                    }

                    $occ_result = json_decode($occ_result, true);

                    if (!isset($occ_campaign['id'])) {

                        //Updating occ campaign code in database
                        $this->model_campaigns_v2_occ_campaigns->create(
                            [
                                'campaign_v2_id' => $pending_campaign['id'],
                                'occ_campaign_id' => $occ_result['campaignId'],
                                'discount_type' => $pending_campaign['discount_type'],
                                'discount_value' => DiscountTypeEnum::PERCENTUAL == $pending_campaign['discount_type'] ? $pending_campaign['discount_percentage'] : $pending_campaign['fixed_discount'],
                            ]
                        );

                    }

                    //Updating campaign to not sincronize with vtex again
                    $this->model_campaigns_v2->update(
                        [
                            'occ_campaign_update' => 2, //Marca como foi integrado
                            'ds_vtex_campaign_creation' => '', //Marca que não teve nenhum erro
                        ],
                        $pending_campaign['id']
                    );

                }

            }catch (Exception $exception){
                echo $exception->getMessage().PHP_EOL;
                //Se tiver campanha criada, vamos inativar
                $this->archiveUnarchiveCampaign($pending_campaign['id'], false);
                //Vamos salvar o motivo do erro para poder apresentar em tela
                $this->model_campaigns_v2->update(
                    [
                        'ds_vtex_campaign_creation' => $exception->getMessage()
                    ],
                    $pending_campaign['id']);
            }

        }

    }

    /**
     * Validação se todos os dados do payload estão na campanha criada
     * @param array $payload
     * @param array $result
     * @throws Exception
     */
    private function validateOccCampaignCreated(array $payload, array $result): void
    {

        /**
         * Validações a seguir só podem acontecer caso o isActive estiver true
         * Pois pode estar apenas inativando outra campanha por deixar de precisar por remover produtos ou o desconto
         */
        if (!$payload['isActive']){
            return;
        }

        //Se não teve nenhum sku, não pode ter sido criada
        if (!isset($payload['skus']) || !$payload['skus']){
            throw new Exception("Não foi fornecido uma lista de SKUS para enviar para a vtex");
        }

        //Todas as skus do payload atual precisam estar no retorno
        foreach ($payload['skus'] as $sku){

            //Não podemos enviar id vazia nunca
            if (!$sku['id']){
                throw new Exception("Id de sku do marketplace não fornecida");
            }

            //A id do produto enviado precisa estar na lista dos skus cadastrados na occ
            if (!$this->isIdInArray($sku['id'], $result['skus'])){
                ddd($sku['id'], $result['skus']);
                throw new Exception("Id SKU {$sku['id']} não foi encontrado no retorno da occ");
            }

        }

        $hasPaymentMethods = false;

        //Se teve formas de pagamento, validar as formas de pagamento
        if (isset($payload['paymentMethods']) && $payload['paymentMethods']){
            $hasPaymentMethods = true;
            foreach ($payload['paymentMethods'] as $paymentsMethod){
                //Não podemos enviar vazio nunca
                if (!$paymentsMethod['id']){
                    throw new Exception("ID do método de pagamento não foi fornecido");
                }

                //A id da forma de pagamento precisa estar na lista das formas de pagamento cadastradas na vtex
                if (!$this->isIdInArray($paymentsMethod['id'], $result['paymentMethods'])){
                    throw new Exception(
                        "ID do método de pagamento {$paymentsMethod['id']} não foi encontrado no retorno da occ"
                    );
                }

            }
        }

        //Se não teve forma de pagamento nem política comercial, também não pode ter sido criada
        if (!$hasPaymentMethods){
            throw new Exception("Não foi gerado nenhuma lista de métodos de pagamentos");
        }

    }

    private function isIdInArray($id, array $array): bool
    {
        foreach ($array as $item){
            //Se em algum momento tem uma id vazia, não permitiremos essa campanha
            if (!$item['id']){
                return false;
            }
            if ($item['id'] == $id){
                return true;
            }
        }
        return false;
    }

    /**
     * @param $campaign
     * @return array
     * @throws Exception
     */
    private function factoryOccCampaign($campaign, $echo = true): array
    {

        if ($echo){
            echo "Generating data to create/update in Occ for campaign: {$campaign['id']} " . PHP_EOL;
        }

        //adiciona os valores de definicao da campanha, para criar, caso contrario adiciona somente o id para editar
        $start_date = new DateTime($campaign['start_date']);
        $start_date->setTimezone(new DateTimeZone("UTC"));

        $end_date = new DateTime($campaign['end_date']);
        $end_date->setTimezone(new DateTimeZone("UTC"));

        $updated_at = new DateTime($campaign['updated_at']);
        $updated_at->setTimezone(new DateTimeZone("UTC"));

        $campaign_v2_payment_methods = $this->model_campaigns_v2_payment_methods->getCampaignV2PaymentMethods($campaign['id']);
        $marketplaces = $this->model_campaigns_v2_marketplaces->getByCampaignId($campaign['id']);

        if (count($marketplaces) != 1){
            throw new Exception("A campanha {$campaign['id']} só pode ter um marketplace selecionado");
        }

        $marketplace = reset($marketplaces);

        $campaign_payment_methods = [];
        if ($campaign_v2_payment_methods){
            foreach ($campaign_v2_payment_methods as $pmmethod) {
                $campaign_payment_methods[] = ['id' => $pmmethod['method_id'], 'name' => $pmmethod['method_name']];
            }
        }

        $occCampaigns = [];

        $occ_campaign = [
            'name' => $campaign['name'],
            'description' => $campaign['description'],
            'beginDateUtc' => $start_date->format("Y-m-d\TH:i:s.v\Z"),
            'endDateUtc' => $end_date->format("Y-m-d\TH:i:s.v\Z"),
            'lastModified' => $updated_at->format("Y-m-d\TH:i:s.v\Z"),
            'isActive' => true,
            'cumulative' => false,
        ];
        if ($campaign_payment_methods){
            $occ_campaign['paymentMethods'] = $campaign_payment_methods;
        }

        if ($campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
            $occ_campaign['discountType'] = 'percentual';
            $occ_campaign['percentualDiscountValue'] = $campaign['discount_percentage'];
        } else {
            $occ_campaign['discountType'] = 'nominal';
            $occ_campaign['nominalDiscountValue'] = $campaign['fixed_discount'];
        }

        $campaign_products = $this->model_campaigns_v2_products->getProductsByCampaign(
            $campaign['id'],
            null,
            true,
            false,
            $campaign['discount_type'],
            $campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL ? $campaign['discount_percentage'] : '',
            $campaign['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT ? $campaign['fixed_discount'] : ''
        );

        $occ_campaign['skus'] = [];

        if ($campaign_products) {

            foreach ($campaign_products as $campaign_product) {

                $prds_to_integration = $this->model_integrations->getIntegrationsProductByIntTo($campaign_product['product_id'], $marketplace['int_to']);

                if (!$prds_to_integration){
                    throw new Exception("Produto {$campaign_product['product_id']} não possui nenhum cadastro no Marketplace");
                }

                foreach ($prds_to_integration as $prd_to_integration){

                    if(!$prd_to_integration['mkt_sku_id']){
                        throw new Exception("Produto {$campaign_product['product_id']} não possui cadastro no Marketplace {$prd_to_integration['int_to']}: {$prd_to_integration['mkt_sku_id']}");
                    }

                    //Só pode usar os produtos que devem ser enviados do mesmo Marketplace
                    if ($prd_to_integration['int_to'] != $marketplace['int_to']){
                        continue;
                    }

                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                        if ($prd_to_integration['variant'] != $campaign_product['variant']){
                            continue;
                        }
                    }

                    $occ_campaign['skus'][] = [
                        'id' => $prd_to_integration['mkt_sku_id'],
                    ];

                }

            }

        }

        $campaign_v2_occ_campaigns = $this->model_campaigns_v2_occ_campaigns->getCampaignByCampaignIdDiscount(
            $campaign['id'],
            $campaign['discount_type'],
            DiscountTypeEnum::PERCENTUAL == $campaign['discount_type'] ? $campaign['discount_percentage'] : $campaign['fixed_discount']
        );

        $campaign = $occ_campaign;

        //Setting campaign_id again to update
        if ($campaign_v2_occ_campaigns){
            $campaign['id'] = $campaign_v2_occ_campaigns['occ_campaign_id'];
            if ($echo){
                echo "Already have campaign, we are going to update occ campaign: {$campaign['id']} " . PHP_EOL;
            }
        }

        $campaign['skus'] = $occ_campaign['skus'];
        $campaign['isActive'] = count($campaign['skus']) > 0;
        $occCampaigns[] = $campaign;

        return $occCampaigns;

    }

    public function archiveUnarchiveCampaign(int $campaignId): bool
    {

        $campaigns_v2_payment_methods = $this->model_campaigns_v2_payment_methods->getIntToFromCampaign($campaignId);

        if (!$campaigns_v2_payment_methods) {
            echo "No payment options found for campaign: {$campaignId}" . PHP_EOL;
            return false;
        }


        if (isset($campaigns_v2_payment_methods[0]['int_to'])){
            $occ_campaign_int_to = $campaigns_v2_payment_methods[0]['int_to'];
        }

        if (!is_array($this->occ_integrations[$occ_campaign_int_to])) {
            echo "No integrations found." . PHP_EOL;
            //Early return
            return false;
        }

        $pending_campaign = $this->model_campaigns_v2->getCampaignById($campaignId);

        $occ_campaigns = $this->factoryOccCampaign($pending_campaign, false);

        $mktplace_data = $this->occ_integrations[$occ_campaign_int_to];
        $authToken =  $this->authSessions(
            $mktplace_data['auth_data']['campaigns_api_url'].'sessions',
            $mktplace_data['auth_data']['campaigns_api_username'],
            $mktplace_data['auth_data']['campaigns_api_password']
        );

        if (!is_string($authToken)) {
            echo "Falha ao gerar token de acesso." . PHP_EOL;
            exit();
        }

        $authToken = json_decode($authToken, true);
        $authToken = $authToken['token'];

        foreach ($occ_campaigns as $occ_campaign) {

            $endpoint = $mktplace_data['auth_data']['campaigns_api_url'].'campaigns';
            $endpoint .= '/'.$occ_campaign['id'];
            $method = 'PUT';

            $this->occTransactions(
                $endpoint,
                $authToken,
                $method,
                $occ_campaign,
                $campaignId,
                false
            );

        }

        return true;

    }

}