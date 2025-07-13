<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_integrations $model_integrations
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_providers $model_providers
 * @property Model_stores $model_stores
 * @property Model_payment $model_payment
 * @property Model_settings $model_settings
 * @property Model_clients $model_clients
 * @property Model_products $model_products
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 * @property Model_promotions $model_promotions
 * @property Model_order_payment_transactions $model_order_payment_transactions
 * @property Model_orders_payment $model_orders_payment
 * @property Model_fields_orders_mandatory $model_fields_orders_mandatory
 * @property Model_fields_orders_add $model_fields_orders_add
 *
 * @property OrdersMarketplace $ordersmarketplace
 * @property \Marketplaces\Utilities\Order $marketplace_order
 *
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_Router $router
 */

class VtexMandatoryFields extends Main {
    var $int_to='';
    var $apikey='';
    var $site='';
    var $appToken='';
    var $accountName='';
    var $environment='';

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setSuffixDns('.com');

        // carrega os modulos necessários para o Job
        $this->load->model('model_company');
        $this->load->model('model_stores');
        $this->load->model('model_integrations');
        $this->load->model('model_orders');
        $this->load->model('model_fields_orders_mandatory');
        $this->load->model('model_fields_orders_add');
        $this->load->model('model_orders_payment');
        $this->load->model('model_settings');

    }

    function setInt_to($int_to) {
        $this->int_to = $int_to;
    }
    function getInt_to() {
        return $this->int_to;
    }
    function setApikey($apikey) {
        $this->apikey = $apikey;
    }
    function getApikey() {
        return $this->apikey;
    }
    function setAppToken($appToken) {
        $this->appToken = $appToken;
    }
    function getAppToken() {
        return $this->appToken;
    }
    function setAccoutName($accountName) {
        $this->accountName = $accountName;
    }
    function getAccoutName() {
        return $this->accountName;
    }
    function setEnvironment($environment) {
        $this->environment = $environment;
    }
    function getEnvironment() {
        return $this->environment;
    }

    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        $this->customPaymentsFields($this->int_to);

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }


    // função principal que processa pedidos e preenche campos de pagamento obrigatórios via API
    function customPaymentsFields($int_to) {
        // pega nome da função atual para fins de log
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        // verifica se a opção de envio de novos campos está ativada nas configurações do seller center
        $send_new_fields_erp = $this->model_settings->getValueIfAtiveByName('send_new_fields_erp');
        if (!$send_new_fields_erp) {
            echo "A configuração está desativada no seller center. Nenhuma ação será executada\n";
            return;
        }

        // passo 1: busca todas as lojas que possuem algum campo configurado (obrigatório ou adicional)
        $store_ids = $this->model_stores->getStoresWithFieldsConfigured();

        if (empty($store_ids)) {
            echo "Nenhuma loja com campos configurados. Encerrando job.\n";
            return;
        }

        foreach ($store_ids as $store_id) {
            // passo 2: busca os pedidos das lojas
            $orders = $this->model_orders->getAllOrdersMissingFieldsByOrder($store_ids);

            // percorre cada pedido
            foreach ($orders as $order) {
                $store_id = $order['store_id'];
                $order_id = $order['order_id'];

                // busca os campos obrigatórios configurados para a loja
                $mandatoryFields = $this->model_fields_orders_mandatory->getFieldsOrdersMandatory($store_id);
                $activesMandatory = [];
                foreach ($mandatoryFields as $campo => $valor) {
                    if ((int)$valor === 1) {
                        $activesMandatory[] = $campo;
                    }
                }

                // busca os campos adicionais configurados para a loja
                $additionalFields = $this->model_fields_orders_add->getFieldsOrdersAdd($store_id);
                $activesAdd = [];
                foreach ($additionalFields as $campo => $valor) {
                    if ((int)$valor === 1) {
                        $activesAdd[] = $campo;
                    }
                }

                // se o pagamento for via cartão (crédito ou débito), torna obrigatórios os campos first_digits e last_digits
                $forma_pagamento = strtolower($this->model_orders->getPaymentMethodByOrderId($order_id));
                $isCartao = in_array($forma_pagamento, ['creditCard', 'debitCard', 'creditcard', 'debitcard']); 

                if ($isCartao) {
                    foreach (['first_digits', 'last_digits', 'authorization_id'] as $campo) {
                        if (!in_array($campo, $activesMandatory)) {
                            $activesMandatory[] = $campo;
                        }
                    }
                }
                
                // junta os campos ativos
                $campos_ativos = array_unique(array_merge($activesMandatory, $activesAdd));

                if (empty($campos_ativos) && !$isCartao) {
                    // se a loja não tem campos configurados e o pedido não é de cartão, ignora
                    continue;
                }

                // busca os valores desses campos ativos na tabela orders_payment
                $pagamento = $this->model_orders->getFieldsFromOrdersPayment($order_id, $campos_ativos);

                // identifica quais campos obrigatórios ainda estão faltando
                $faltando = [];
                foreach ($activesMandatory as $campo) {
                    // [Melhoria aplicada] Ignora campos de cartão se não for pagamento com cartão
                    if (in_array($campo, ['first_digits', 'last_digits', 'authorization_id']) && !$isCartao) {
                        continue;
                    }
        
                    if (!isset($pagamento[$campo]) || $pagamento[$campo] === '' || $pagamento[$campo] === null) {
                        $faltando[] = $campo;
                    }
                }

                // se houver campos obrigatórios faltando, tenta buscar da API
                if (!empty($faltando)) {
                    $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'];
                    $this->process($order['origin'], $endPoint);
                    
                    if($this->responseCode != 200){
                        echo "Não foi possível validar campos na API do pedido $order_id\n";
                        continue;
                    }

                    $apiResponse = json_decode($this->result, TRUE);
                    
                    $data_to_update = [];

                    // se a resposta da API tiver a estrutura esperada
                    if (isset($apiResponse['paymentData']['transactions'][0]['payments'][0])) {
                        $payment = $apiResponse['paymentData']['transactions'][0]['payments'][0];
                        $connector = $payment['connectorResponses'] ?? [];

                        // mapeia os campos faltando com os dados da API
                        foreach ($faltando as $campo) {
                            switch ($campo) {
                                case 'first_digits':
                                    if (!empty($payment['firstDigits'])) {
                                        $data_to_update['first_digits'] = $payment['firstDigits'];
                                    }
                                    break;
                                case 'last_digits':
                                    if (!empty($payment['lastDigits'])) {
                                        $data_to_update['last_digits'] = $payment['lastDigits'];
                                    }
                                    break;
                                case 'tid':
                                    if (!empty($connector['tid'])) {
                                        $data_to_update['gateway_tid'] = $connector['tid'];
                                    }
                                    break;
                                case 'nsu':
                                    if (!empty($connector['nsu'])) {
                                        $data_to_update['nsu'] = $connector['nsu'];
                                    }
                                    break;
                                    case 'authorization_id': // [Melhoria aplicada] Verifica o campo com nome correto no banco
                                    case 'autorization_id':
                                        if (!empty($connector['authId'])) {
                                            $data_to_update['autorization_id'] = $connector['authId'];
                                        }
                                        break;
                            }
                        }
                    }

                    // se conseguiu dados relevantes, atualiza a tabela orders_payment
                    if (!empty($data_to_update)) {
                        $this->model_orders_payment->updateFieldsOrdersPayment($order_id, $data_to_update);
                        echo "Valores dos campos [" . implode(', ', array_keys($data_to_update)) . "] foram encontrados na API e o Pedido {$order_id} (Loja {$store_id}) foi atualizado com sucesso.\n";
                    } else {
                        echo "A API não retornou os campos necessários (" . implode(', ', $faltando) . ") para o Pedido {$order_id} (Loja {$store_id}).\n";
                    }
                }
            }
        }
    }
}