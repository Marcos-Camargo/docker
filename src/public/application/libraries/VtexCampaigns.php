<?php

use App\Libraries\Enum\DiscountTypeEnum;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_integrations $model_integrations
 * @property Model_vtex_payment_methods $model_vtex_payment_methods
 * @property Model_vtex_trade_policy $model_vtex_trade_policy
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_campaigns_v2_vtex_campaigns $model_campaigns_v2_vtex_campaigns
 * @property Model_campaigns_v2_payment_methods $model_campaigns_v2_payment_methods
 * @property Model_campaigns_v2_trade_policies $model_campaigns_v2_trade_policies
 * @property Model_campaigns_v2_products $model_campaigns_v2_products
 * @property Model_campaign_v2_vtex_campaigns_logs $model_campaign_v2_vtex_campaigns_logs
 * @property Model_campaigns_v2_marketplaces $model_campaigns_v2_marketplaces
 */
class VtexCampaigns extends MY_Controller
{
    protected $vtex_integrations;
    protected $result;
    protected $response_code;


    public function __construct($params = array())
    {

        $this->load->model('model_integrations');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_campaigns_v2_vtex_campaigns');
        $this->load->model('model_campaigns_v2_payment_methods');
        $this->load->model('model_campaigns_v2_trade_policies');
        $this->load->model('model_campaigns_v2_products');
        $this->load->model('model_vtex_payment_methods');
        $this->load->model('model_vtex_trade_policy');
        $this->load->model('model_products');
        $this->load->model('model_campaign_v2_vtex_campaigns_logs');
        $this->load->model('model_campaigns_v2_marketplaces');
        $this->load->model('model_settings');

        $intTo = count($params) > 0 && isset($params['intTo']) ? $params['intTo'] : null;

        $market_places = $this->model_integrations->getIntegrationsbyStoreIdActive(0, $intTo);

        if (is_array($market_places) && !empty($market_places)) {
            foreach ($market_places as $key => $market_place) {
                if (is_array($market_place)) {
                    $auth_data = json_decode($market_place['auth_data'], true);
                    if (isset($auth_data['X_VTEX_API_AppKey'])){
                        $market_places[$key]['auth_data'] = $auth_data;
                    }
                }
            }

            foreach ($market_places as $key => $market_place) {
                $this->vtex_integrations[$market_place['int_to']] = $market_place;
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
     * ------------------------------------------------------
     * Conjunto de métodos genéricos
     * ------------------------------------------------------
     */

    private function importPaymentMethodsByPaymentSystems($mktplace_data, array &$error): void
    {

                    $endpoint = 'https://' . $mktplace_data['auth_data']['accountName'] . '.vtexpayments.com.br/api/pvt/merchants/payment-systems';
                    echo "INT_TO: {$mktplace_data['int_to']}".PHP_EOL;
                    echo "Endpoint: ".$endpoint.PHP_EOL;

                    $payment_methods_json = $this->vtexTransactions($endpoint, $mktplace_data);
                    $payment_methods_array = json_decode($payment_methods_json, true);

        if (substr($this->response_code, 0, 1) == 2) {
            if (!empty($payment_methods_array)) {
                $payment_methods = []; //reseto o array para cada nova iteração

                foreach ($payment_methods_array as $method) {

                    echo "Validando Método: {$method['id']}, Is available: ".$method['isAvailable'].PHP_EOL;

                                if ($method['isAvailable'] != true) {
                                    continue;
                                }

                    $payment_methods[] = [
                        'int_to' => $mktplace_data['int_to'],
                        'method_id' => $method['id'],
                        'method_name' => $method['name'],
                        'method_description' => $method['description'],
                    ];

                }

                if ($payment_methods) {

                    $ids_imported = [];

                    foreach ($payment_methods as $vtex_data) {
                        $ids_imported[] = $vtex_data['method_id'];

                        if ($item = $this->model_vtex_payment_methods->getPaymentMethod($vtex_data)){
                            echo "Ativando método: {$item['method_id']}".PHP_EOL;
                                        $vtex_data['active'] = 1;
                                        $this->model_vtex_payment_methods->update($vtex_data, $item['id']);
                                    }else{
                                        echo "Desativando método method_id: {$vtex_data['method_id']}".PHP_EOL;
                            $this->model_vtex_payment_methods->vtexInsertUpdatePaymentMethods($vtex_data);
                        }

                    }

                    $this->model_vtex_payment_methods->vtexPaymentMethodsInactive($mktplace_data['int_to'], $ids_imported);

                } else {
                    $error[] = "Métodos de Pagamento Vtex Vazios (" . $this->response_code . " || " . $this->result . ") \r\n";
                }
            }
        } else {
            $error[] = "Falha recuperando Métodos de Pagamento Vtex (" . $this->response_code . " || " . $this->result . ") \r\n";
        }

    }

    private function importPaymentMethodsByPaymentRules($mktplace_data, array &$error): void
    {

        $accountName = $mktplace_data['auth_data']['accountName'];
        $customAccountName = $this->model_settings->getValueIfAtiveByName('custom_seller_id_for_import_all_vtex_payment_methods');
        if ($customAccountName){
            $customAccountNameJson = json_decode($customAccountName, true);
            if ($customAccountNameJson){
                if (isset($customAccountNameJson[$accountName]) && $customAccountNameJson[$accountName]){
                    $accountName = $customAccountNameJson[$accountName];
                }
            }else{
                $accountName = $customAccountName;
            }
        }

        $endpoint = 'https://' . $accountName . '.vtexpayments.com.br/api/pvt/rules';
        $payment_methods_json = $this->vtexTransactions($endpoint, $mktplace_data);
        $payment_methods_array = json_decode($payment_methods_json, true);

        if (substr($this->response_code, 0, 1) == 2) {
            if (!empty($payment_methods_array)) {
                $payment_methods = []; //reseto o array para cada nova iteração

                foreach ($payment_methods_array as $method) {

                    if (!$method['enabled'] || isset($payment_methods[$method['paymentSystem']['id']])) {
                        if (isset($payment_methods[$method['paymentSystem']['id']])
                            && !strstr($method['name'], $method['paymentSystem']['name'])){
                            $payment_methods[$method['paymentSystem']['id']]['method_description'] .= ', '.$method['name'];
                        }
                        continue;
                    }

                    $payment_methods[$method['paymentSystem']['id']] = [
                        'int_to' => $mktplace_data['int_to'],
                        'method_id' => $method['paymentSystem']['id'],
                        'method_name' => $method['paymentSystem']['name'],
                        'method_description' => '',
                    ];

                    if (!strstr($method['name'], $method['paymentSystem']['name'])){
                        $payment_methods[$method['paymentSystem']['id']]['method_description'] = $method['name'];
                    }

                }

                if ($payment_methods) {

                    $ids_imported = [];

                    foreach ($payment_methods as $vtex_data) {
                        $ids_imported[] = $vtex_data['method_id'];

                        if ($item = $this->model_vtex_payment_methods->getPaymentMethod($vtex_data)){
                            $vtex_data['active'] = 1;
                            $this->model_vtex_payment_methods->update($vtex_data, $item['id']);
                        }else{
                            $this->model_vtex_payment_methods->vtexInsertUpdatePaymentMethods($vtex_data);
                        }

                    }

                    $this->model_vtex_payment_methods->vtexPaymentMethodsInactive($mktplace_data['int_to'], $ids_imported);

                } else {
                    $error[] = "Métodos de Pagamento Vtex Vazios (" . $this->response_code . " || " . $this->result . ") \r\n";
                }
            }
        } else {
            $error[] = "Falha recuperando Métodos de Pagamento Vtex (" . $this->response_code . " || " . $this->result . ") \r\n";
        }

    }

    public function updatePaymentMethods()
    {
        $error = [];

        if (is_array($this->vtex_integrations) && !empty($this->vtex_integrations)) {
            foreach ($this->vtex_integrations as $mktplace_data) {
                if (isset($mktplace_data['auth_data']['X_VTEX_API_AppKey'])) {

                    if ($this->model_settings->getValueIfAtiveByName('import_all_vtex_payment_methods')) {
                        $this->importPaymentMethodsByPaymentRules($mktplace_data, $error);
                    } else {
                        $this->importPaymentMethodsByPaymentSystems($mktplace_data, $error);
                    }

                }

                //ignora os mktplaces que nao sao vtex
            }

        } else {
            echo $error[] = "Configurações Vtex com erro ou inexistentes (" . $this->vtex_integrations . ") \r\n";
        }

        if (!empty($error)) {
            echo implode("\r\n", $error);
            $this->log_data('batch', __FUNCTION__, serialize($error));
            return false;
        } else {
            echo "Atualização de Métodos de Pagamento Vtex concluída com sucesso. \r\n";
        }

    }

    public function updateTradePolicies($showMessages = true)
    {
        $error = [];

        if (is_array($this->vtex_integrations) && !empty($this->vtex_integrations)) {
            foreach ($this->vtex_integrations as $mktplace_data) {
                if (isset($mktplace_data['auth_data']['X_VTEX_API_AppKey'])) {

                    $suffixDns = $mktplace_data['auth_data']['suffixDns'] ?? '.com';
                    $environment = $mktplace_data['auth_data']['environment'];
                    $endpoint = 'https://' . $mktplace_data['auth_data']['accountName'] . '.' . $environment . $suffixDns .'/api/catalog_system/pvt/saleschannel/list';

                    $trade_policies_json = $this->vtexTransactions($endpoint, $mktplace_data);
                    $trade_policies_array = json_decode($trade_policies_json, true);

                    if (substr($this->response_code, 0, 1) == 2) {
                        if (!empty($trade_policies_array)) {
                            $trade_policies = []; //reseto o array para cada nova iteração

                            foreach ($trade_policies_array as $trade_policy) {

                                $trade_policies[] = [
                                    'int_to' => $mktplace_data['int_to'],
                                    'trade_policy_id' => $trade_policy['Id'],
                                    'trade_policy_name' => $trade_policy['Name'],
                                    'active' => $trade_policy['IsActive'] ? 1 : 2
                                ];

                            }

                            if (!empty($trade_policies)) {

                                $ids_imported = [];

                                foreach ($trade_policies as $vtex_data) {

                                    $ids_imported[] = $vtex_data['trade_policy_id'];

                                    if ($item = $this->model_vtex_trade_policy->getTradePolicy($vtex_data)){
                                        $this->model_vtex_trade_policy->update($vtex_data, $item['id']);
                                    }else{
                                        $this->model_vtex_trade_policy->vtexInsertUpdateTradePolicies($vtex_data);
                                    }

                                }

                                $this->model_vtex_trade_policy->vtexTradePoliciesInactive($ids_imported);

                                //Inactive campaigns with inactive trade policies
                                $inactiveTradePolicies = $this->model_vtex_trade_policy->vtexGetInactiveTradePolicies();
                                if ($inactiveTradePolicies){
                                    foreach ($inactiveTradePolicies as $inactiveTradePolicy){

                                        $campaigns = $this->model_campaigns_v2_trade_policies->getByTradePolicyId($inactiveTradePolicy['id']);
                                        if ($campaigns){
                                            foreach ($campaigns as $campaign){
                                                $this->inactivateCampaign($campaign['campaign_v2_id']);
                                            }
                                        }

                                    }
                                }

                            } else {
                                if($showMessages) {
                                    echo $error[] = "Política comercial Vazios (" . $this->response_code . " || " . $this->result . ") \r\n";
                                }
                            }
                        }
                    } else {
                        if($showMessages) {
                            echo $error[] = "Falha recuperando Políticas comerciais na Vtex (" . $this->response_code . " || " . $this->result . ") \r\n";
                        }
                    }
                }

            }

        } else {
            if($showMessages) {
                echo $error[] = "Configurações Vtex com erro ou inexistentes (" . $this->vtex_integrations . ") \r\n";
            }
        }

        if (!empty($error)) {
            $this->log_data('batch', __FUNCTION__, serialize($error));
            return false;
        } else {
            if($showMessages) {
                echo "Atualização de Políticas Comerciais da Vtex concluída com sucesso. \r\n";
            }
        }

    }

    private function inactivateCampaign(int $campaignId)
    {
        $this->db->trans_begin();
        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);

        //If campaign type is vtex, first we need to inactivate in vtex
        if ($campaign['vtex_campaign_update'] > 0){
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

    protected function vtexTransactions($endpoint = null, $mktplace_data = null, $method = 'GET', $data = null, $headers = null)
    {
        if (empty($endpoint) || empty($mktplace_data)) {
            return false;
        }

        if (!$headers) {
            $headers = [
                'Content-Type: application/json',
                'Accept: application/vnd.vtex.ds.v10+json',
                'X-VTEX-API-AppKey: ' . $mktplace_data['auth_data']['X_VTEX_API_AppKey'],
                'X-VTEX-API-AppToken: ' . $mktplace_data['auth_data']['X_VTEX_API_AppToken']
            ];
        }

        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if (strtoupper($method) == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        if (strtoupper($method) == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $this->result = curl_exec($ch);
        $this->response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        return $this->result;

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

        $pending_campaigns = $this->model_campaigns_v2->getPendingVtexCampaignsToSincronizeWithVtex();

        if (!$pending_campaigns) {
            //Early return
            echo "No pending campaign found" . PHP_EOL;
            return;
        }

        foreach ($pending_campaigns as $pending_campaign) {

            echo "Updating campaign: {$pending_campaign['id']}" . PHP_EOL;

            $campaigns_v2_payment_methods = $this->model_campaigns_v2_payment_methods->getIntToFromCampaign($pending_campaign['id']);
            $campaigns_v2_trade_policies = $this->model_campaigns_v2_trade_policies->getIntToFromCampaign($pending_campaign['id']);

            if (!$campaigns_v2_payment_methods && !$campaigns_v2_trade_policies) {
                echo "No payment options & trade policies found for campaign: {$pending_campaign['id']}" . PHP_EOL;
                continue;
            }

            if (isset($campaigns_v2_payment_methods[0]['int_to'])){
                $vtex_campaign_int_to = $campaigns_v2_payment_methods[0]['int_to'];
            }else{
                $vtex_campaign_int_to = $campaigns_v2_trade_policies[0]['int_to'];
            }

            if (!is_array($this->vtex_integrations[$vtex_campaign_int_to])) {
                echo "No integrations found." . PHP_EOL;
                //Early return
                continue;
            }


            try {

                $vtex_campaigns = $this->factoryVtexCampaign($pending_campaign);

                foreach ($vtex_campaigns as $vtex_campaign){

                    echo "Cadastrando/atualizando campanha: ".json_encode($vtex_campaign).PHP_EOL;

                    $vtex_result = $this->postVtexData($vtex_campaign_int_to, $vtex_campaign, $pending_campaign['id']);

                    //Early return
                    if (!$vtex_result) {
                        echo "Não cadastrou/atualizou na vtex: {$this->result}";
                        continue;
                    }

                    $this->validateVtexCampaignCreated($vtex_campaign, $vtex_result);

                    if (!isset($vtex_campaign['idCalculatorConfiguration'])) {

                        //Updating vtex campaign code in database
                        $this->model_campaigns_v2_vtex_campaigns->create(
                            [
                                'campaign_v2_id' => $pending_campaign['id'],
                                'vtex_campaign_id' => $vtex_result['idCalculatorConfiguration'],
                                'discount_type' => $pending_campaign['discount_type'],
                                'discount_value' => DiscountTypeEnum::PERCENTUAL == $pending_campaign['discount_type'] ? $pending_campaign['discount_percentage'] : $pending_campaign['fixed_discount'],
                            ]
                        );

                    }

                    //Updating campaign to not sincronize with vtex again
                    $this->model_campaigns_v2->update(
                        [
                            'vtex_campaign_update' => 2, //Marca como foi integrado
                            'ds_vtex_campaign_creation' => '', //Marca que não teve nenhum erro
                        ],
                        $pending_campaign['id']
                    );

                    echo "Fim cadastro do chunk".PHP_EOL;

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
    private function validateVtexCampaignCreated(array $payload, array $result): void
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

            //A id do produto enviado precisa estar na lista dos skus cadastrados na vtex
            if (!$this->isIdInArray($sku['id'], $result['skus'])){
                throw new Exception("Id SKU {$sku['id']} não foi encontrado no retorno da vtex");
            }

        }

        $hasPaymentMethods = false;
        $hasTradePolicies = false;

        //Se teve formas de pagamento, validar as formas de pagamento
        if (isset($payload['paymentsMethods']) && $payload['paymentsMethods']){
            $hasPaymentMethods = true;
            foreach ($payload['paymentsMethods'] as $paymentsMethod){
                //Não podemos enviar vazio nunca
                if (!$paymentsMethod['id']){
                    throw new Exception("ID do método de pagamento não foi fornecido");
                }

                //A id da forma de pagamento precisa estar na lista das formas de pagamento cadastradas na vtex
                if (!$this->isIdInArray($paymentsMethod['id'], $result['paymentsMethods'])){
                    throw new Exception(
                        "ID do método de pagamento {$paymentsMethod['id']} não foi encontrado no retorno da vtex"
                    );
                }

            }
        }

        //Se teve política comercial, validar as políticas comerciais
        if (isset($payload['idsSalesChannel']) && $payload['idsSalesChannel']){
            $hasTradePolicies = true;
            foreach ($payload['idsSalesChannel'] as $saleChannel){
                //Não podemos enviar vazio nunca
                if (!$saleChannel){
                    throw new Exception("ID do canal de venda inválido: $saleChannel");
                }

                if (!in_array($saleChannel, $result['idsSalesChannel'])){
                    throw new Exception("ID do canal de venda $saleChannel não foi encontrado no retorno da vtex");
                }

            }
        }

        //Se não teve forma de pagamento nem política comercial, também não pode ter sido criada
        if (!$hasPaymentMethods && !$hasTradePolicies){
            throw new Exception("Não foi gerado nenhuma lista de métodos de pagamentos e políticas comerciais");
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
    private function factoryVtexCampaign($campaign): array
    {

        echo "Generating data to create/update in vtex for campaign: {$campaign['id']} " . PHP_EOL;

        //adiciona os valores de definicao da campanha, para criar, caso contrario adiciona somente o id para editar
        $start_date = new DateTime($campaign['start_date']);
        $start_date->setTimezone(new DateTimeZone("UTC"));

        $end_date = new DateTime($campaign['end_date']);
        $end_date->setTimezone(new DateTimeZone("UTC"));

        $updated_at = new DateTime($campaign['updated_at']);
        $updated_at->setTimezone(new DateTimeZone("UTC"));

        $campaign_v2_payment_methods = $this->model_campaigns_v2_payment_methods->getCampaignV2PaymentMethods($campaign['id']);
        $campaign_v2_trade_policies = $this->model_campaigns_v2_trade_policies->getCampaignV2TradePolicies($campaign['id']);
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

        $campaign_trade_policies = [];
        if ($campaign_v2_trade_policies){
            foreach ($campaign_v2_trade_policies as $trade_policy) {
                $campaign_trade_policies[] = $trade_policy['trade_policy_id'];
            }
        }

        $vtexCampaigns = [];

        $vtex_campaign = [
            'name' => $campaign['name'],
            'description' => $campaign['description'],
            'beginDateUtc' => $start_date->format("Y-m-d\TH:i:s.v\Z"),
            'endDateUtc' => $end_date->format("Y-m-d\TH:i:s.v\Z"),
            'lastModified' => $updated_at->format("Y-m-d\TH:i:s.v\Z"),
            'daysAgoOfPurchases' => 0,
            'isArchived' => false,
            'isFeatured' => true,
            'offset' => -3,
            'activateGiftsMultiplier' => false,
            'newOffset' => -3,
            'cumulative' => false,
            'effectType' => 'price',
            'skusAreInclusive' => true,
            'type' => 'regular',
            'origin' => 'marketplace',
        ];
        if ($campaign_payment_methods){
            $vtex_campaign['paymentsMethods'] = $campaign_payment_methods;
        }
        if ($campaign_trade_policies){
            $vtex_campaign['idsSalesChannel'] = $campaign_trade_policies;
        }

        if ($campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
            $vtex_campaign['discountType'] = 'percentual';
            $vtex_campaign['percentualDiscountValue'] = $campaign['discount_percentage'];
        } else {
            $vtex_campaign['discountType'] = 'nominal';
            $vtex_campaign['nominalDiscountValue'] = $campaign['fixed_discount'];
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

        $vtex_campaign['skus'] = [];

        if ($campaign_products) {

            foreach ($campaign_products as $campaign_product) {

                echo "Buscando prds_to_integration do product_id: {$campaign_product['product_id']} no int_to: {$marketplace['int_to']}".PHP_EOL;

                $prds_to_integration = $this->model_integrations->getIntegrationsProductByIntTo($campaign_product['product_id'], $marketplace['int_to']);

                if (!$prds_to_integration){
                    echo "Não tem prds_to_integration, vamos pular essa campanha".PHP_EOL;
                    continue;
                }

                foreach ($prds_to_integration as $prd_to_integration){

                    if(!$prd_to_integration['mkt_sku_id']){
                        echo "Não tem mkt_sku_id".PHP_EOL;
                        continue;
                    }

                    //Só pode usar os produtos que devem ser enviados do mesmo Marketplace
                    if ($prd_to_integration['int_to'] != $marketplace['int_to']){
                        echo "int_to diferente do marketplace".PHP_EOL;
                        continue;
                    }

                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                        if ($prd_to_integration['variant'] != $campaign_product['variant']){
                            continue;
                        }
                    }

                    $vtex_campaign['skus'][] = [
                        'id' => $prd_to_integration['mkt_sku_id'],
                        'name' => $campaign_product['product_name'],
                    ];

                }

            }

        }

        $campaign_v2_vtex_campaigns = $this->model_campaigns_v2_vtex_campaigns->getCampaignByCampaignIdDiscount(
            $campaign['id'],
            $campaign['discount_type'],
            DiscountTypeEnum::PERCENTUAL == $campaign['discount_type'] ? $campaign['discount_percentage'] : $campaign['fixed_discount']
        );

        $chunks = array_chunk($vtex_campaign['skus'], 200);

        //Adding max 200 skus each vtex promotion
        foreach ($chunks as $key => $chunk){

            $campaign = $vtex_campaign;

            //Setting campaign_id again to update
            if ($campaign_v2_vtex_campaigns && isset($campaign_v2_vtex_campaigns[$key])){
                $campaign['idCalculatorConfiguration'] = $campaign_v2_vtex_campaigns[$key]['vtex_campaign_id'];
                echo "Already have campaign, we are going to update vtex campaign: {$campaign['idCalculatorConfiguration']} " . PHP_EOL;
            }

            $campaign['skus'] = $chunk;
            $campaign['isActive'] = count($campaign['skus']) > 0;
            $vtexCampaigns[] = $campaign;

        }

        //Checking if we have saved more vtex promotions than we should have right now to disable them
        if ($campaign_v2_vtex_campaigns){
            foreach ($campaign_v2_vtex_campaigns as $key => $campaign_v2_vtex_campaign){
                if (!isset($vtexCampaigns[$key])){
                    //disabling old promotions
                    $campaign = $vtex_campaign;
                    $campaign['idCalculatorConfiguration'] = $campaign_v2_vtex_campaign['vtex_campaign_id'];
                    $campaign['skus'] = [];
                    $campaign['isActive'] = 0;
                    $vtexCampaigns[] = $campaign;
                    echo "Have more campaigns saved than generated, we are going to disable promotion: {$campaign['idCalculatorConfiguration']} " . PHP_EOL;
                }
            }
        }

        echo "Criou a seguinte lista de campanhas: " . PHP_EOL;
        echo json_encode($vtexCampaigns).PHP_EOL;

        return $vtexCampaigns;

    }

    private function postVtexData(string $vtex_campaign_int_to, array $vtex_campaign = null, int $campaign_v2_id, string $endpoint_suffix = 'rnb/pvt/calculatorconfiguration'): ?array
    {

        $mktplace_data = $this->vtex_integrations[$vtex_campaign_int_to];
        $suffixDns = $mktplace_data['auth_data']['suffixDns'] ?? '.com';
        $endpoint = 'https://' . $mktplace_data['auth_data']['accountName'] . '.' . $mktplace_data['auth_data']['environment'] . $suffixDns .'/api/' . $endpoint_suffix;

        $vtex_campaign_json = '';
        if ($vtex_campaign) {
            $vtex_campaign_json = json_encode($vtex_campaign, JSON_UNESCAPED_SLASHES);
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-VTEX-API-AppKey: ' . $mktplace_data['auth_data']['X_VTEX_API_AppKey'],
            'X-VTEX-API-AppToken: ' . $mktplace_data['auth_data']['X_VTEX_API_AppToken']
        ];

        $log_id = $this->model_campaign_v2_vtex_campaigns_logs->create(
            [
                'campaign_v2_id' => $campaign_v2_id,
                'conectala_request' => json_encode([
                    'endpoint' => $endpoint,
                    'header' => $headers,
                    'body' => $vtex_campaign,
                ])
            ]
        );

        $vetx_campaign_creation_json = $this->vtexTransactions($endpoint, $mktplace_data, 'POST', $vtex_campaign_json, $headers);

        $this->model_campaign_v2_vtex_campaigns_logs->update(
            [
                'vtex_response_code' => $this->response_code,
                'vtex_response_body' => $vetx_campaign_creation_json
            ],
            $log_id
        );

        if (!in_array($this->response_code, [200, 201, 204])) {

            echo "Erro ao cadastrar/atualizar a campanha $campaign_v2_id na vtex. Retorno da vtex: $vetx_campaign_creation_json" . PHP_EOL;

            return null;

        }

        return json_decode($vetx_campaign_creation_json, true);

    }

    public function archiveUnarchiveCampaign(int $campaignId, $isActive = true, $approved = true): bool
    {

        $vtex_campaigns = $this->model_campaigns_v2_vtex_campaigns->getCampaignsByCampaignId($campaignId);

        if (!$vtex_campaigns) {
            //Early return
            return true;
        }

        foreach ($vtex_campaigns as $vtex_campaign) {

            $campaigns_v2_payment_methods = $this->model_campaigns_v2_payment_methods->getIntToFromCampaign($campaignId);
            $campaigns_v2_trade_policies = $this->model_campaigns_v2_trade_policies->getIntToFromCampaign($campaignId);

            if (!$campaigns_v2_payment_methods && !$campaigns_v2_trade_policies) {
                continue;
            }

            $vtex_campaign_int_to = '';
            if (isset($campaigns_v2_payment_methods[0]['int_to'])){
                $vtex_campaign_int_to = $campaigns_v2_payment_methods[0]['int_to'];
            }
            if (!$vtex_campaign_int_to && isset($campaigns_v2_trade_policies[0]['int_to'])){
                $vtex_campaign_int_to = $campaigns_v2_trade_policies[0]['int_to'];
            }

            if (!$vtex_campaign_int_to || !is_array($this->vtex_integrations[$vtex_campaign_int_to])) {
                //Early return
                continue;
            }

            $endpoint_suffix = 'rnb/pvt/';
            $endpoint_suffix .= $isActive && $approved ? 'unarchive' : 'archive';
            $endpoint_suffix .= "/calculatorConfiguration/{$vtex_campaign['vtex_campaign_id']}";

            $this->postVtexData(
                $vtex_campaign_int_to,
                null,
                $campaignId,
                $endpoint_suffix
            );

            if ($this->response_code != 204) {
                return false;
            }

        }

        return true;

    }

}