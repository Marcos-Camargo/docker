<?php

/**
 * Criado por:  Augusto Braun - ConectaLá
 * ------------------------------------------------------
 * Entregue em: 2021-07-20
 * ------------------------------------------------------
 * Descrição:   Batch para ser executada periodicamente
 *              para cadastrar lojas na tabela stores no 
 *              ambiente MOIP conta transparente. 
 * ------------------------------------------------------
*/

require APPPATH . "controllers/BatchC/GenericBatch.php";

class MoipBatch extends GenericBatch
{
    private $log_name;
    private $api_url;
    private $app_id;
    private $app_account;
    private $app_bank_id;
    private $app_token;
    private $ymi_token;
    private $ymi_url;

    private $seller_id_min_chars = 3; //minimun chars on vtex seller_id
    private $minutesToCheck;
    private $gateway_name;
    private $gateway_id;
    private $moip_return_advanced_payments;
    
    private $screen_message_header = "\r\n \r\n =========================================================== \r\n";
    private $screen_message_footer = "\r\n =========================================================== \r\n \r\n";

	public function __construct()
	{
		parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        );
        
		$this->session->set_userdata($logged_in_sess);
		
		$this->load->model('model_gateway');
		$this->load->model('model_banks');
		$this->load->model('model_conciliation');
        $this->load->model('model_stores');
        $this->load->model('model_payment_gateway_store_logs');
        $this->load->model('model_settings');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_payment');

		$this->load->library('MoipLibrary');

        $this->minutesToCheck = $this->model_settings->getValueIfAtiveByName('minutes_sincronize_stores_with_gateway_account');
        $this->moip_return_advanced_payments = ($this->model_settings->getStatusbyName('moip_return_advanced_payments') == 1) ? true : false;

        //@todo código duplicado com da library
        $this->gateway_name = Model_gateway::MOIP;
        $this->gateway_id   = $this->model_gateway->getGatewayId($this->gateway_name);

        $api_settings = $this->model_gateway_settings->getSettings($this->gateway_id);

        if (!empty($api_settings) && is_array($api_settings))
        {
            foreach ($api_settings as $key => $setting)
            {
                $this->{$setting['name']} = $setting['value'];
            }
        }
    }


	public function run($id = null, $params = null)
	{               
        $this->startJob(__FUNCTION__ , $id, $params);

		$this->generateMoipSubaccounts();
		
        $this->endJob();
	}
	

    public function generateMoipSubaccounts($onlyNotCreatedAccount = true, $id = null, $params = null)
    {

        if (!is_null($id)) {
            $this->startJob(__FUNCTION__, $id, $params);
        }
       
        $log_name = $this->logName;

        $stores = $this->model_moip->getStores();

        if (is_array($stores) && !empty($stores))
        {
            foreach ($stores as $key => $store)
            {
                $store_data = $this->model_moip->getStoreData($store['id']);

                echo "\r\n" . $store_data['id'] . ' - ' . $store_data['name'];

                $errors = [];

                if (strlen($this->onlyNumbers($store_data["phone_1"])) < 10) 
                    $errors[] = 'Telefone falta o DDD';

                if ($store_data["bank"] == "") 
                    $errors[] = 'Banco não encontrado';

                if ($store_data["agency"] == "")
                    $errors[] = 'Agência Bancária não encontrada';

                if ($store_data["account"] == "")
                    $errors[] = 'Conta Bancária não encontrada';

                if (empty($store_data["responsible_name"]))
                    $errors[] = 'Nome do Responsável não encontrado';

                if (empty($store_data["responsible_email"]))
                    $errors[] = 'E-mail do Responsável não encontrado';

                if (empty($store_data["responsible_cpf"]))
                    $errors[] = 'CPF do Responsável não encontrado';

                if ($errors)
                {
                    $error = implode(', ', $errors);

                    $this->log_data(
                        'batch',
                        $log_name,
                        "Não foi possível integrar a loja: {$store_data['id']} a $this->gateway_name. $error",
                        "E"
                    );

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store_data['id'],
                        $this->gateway_id ,
                        $error
                    );

                    echo " - ERRO por dados incompletos \r\n";

                    continue;
                }

                echo " - iniciando o cadastro no gateway de pagamento \r\n";

                $this->moipCreateSubAccount($store_data);
            }

            return true;
        }
        

        return false;
    }


    public function moipCreateSubAccount($store_data = null)
    {
        $moip_subaccount_created = false;
        $seller_id = trim($store_data['seller_id']);
        $seller_id_json = $this->model_gateway->getVtexSellerIdIntegration($store_data['id']);
        $seller_id_integration = '';
        $success = [];

        if (false !== $seller_id_array = json_decode($seller_id_json, true))
            $seller_id_integration = $seller_id_array['seller_id'];

        if (strlen($seller_id_integration) < $this->seller_id_min_chars)
        {
            echo $error = "ERRO: Seller ID da vtex nao foi capturado ou está com erro. \r\n";
            $this->log_data(
                'batch',
                $this->logName,
                "Não foi possível integrar a loja: {$store_data['id']} a $this->gateway_name. $error",
                "E"
            );
            $this->model_payment_gateway_store_logs->insertLog(
                $store_data['id'],
                $this->gateway_id ,
                $error
            );
            return false;
        }

        if ( (!empty($seller_id) && $seller_id != $seller_id_integration) || empty($seller_id_integration) )
        {
            echo $error = "ERRO: Seller ID da vtex Cadastrado nao Loja não confere com o Retorn Vtex, ou está. \r\n";
            $this->log_data(
                'batch',
                __FUNCTION__,
                "Não foi possível integrar a loja: {$store_data['id']} a $this->gateway_name. $error",
                "E"
            );
            $this->model_payment_gateway_store_logs->insertLog(
                $store_data['id'],
                $this->gateway_id ,
                $error
            );
            return false;
        }

        $names      = explode(' ', $store_data['responsible_name']);
        $name       = $names[0];
        $surname    = $names[count($names)-1];
        $birth_date = (isset($store_data['responsible_birth_date']) && $store_data['responsible_birth_date'] != '0000-00-00') ? $store_data['responsible_birth_date'] : '1990-01-01';

        //checa se a loja ja existe, pois senao apenas atualiza e tenta registrar yami
        $store_exists = $this->model_moip->getStoreMoipData($store_data['id']);

        if (empty($store_exists))
        {
            $url = $this->api_url.'/accounts';
            $url = str_replace(':/', '://',str_replace('//', '/', $url));

            $data = array(
                'email' => array(
                    'address' => $store_data['responsible_email']
                ),
                'person' => array(
                    'name'              => $name,
                    'lastName'          => $surname,
                    'birthDate'         => $birth_date,
                    'taxDocument'       => array(
                        'type'          => 'CPF',
                        // 'number'        => $this->formatDoc($store_data['responsible_cpf'])
                        'number'        => $this->onlyNumbers($store_data['responsible_cpf'])
                    ),
                    'phone' => array(
                        'countryCode'   => '55',
                        'areaCode'      => substr($store_data['phone_1'], 0, 2),
                        'number'        => str_pad(substr($store_data['phone_1'], 2), 9, 0, STR_PAD_LEFT)
                    ),
                    'address' => array(
                        'street'        => $store_data['address'],
                        'streetNumber'  => intVal(filter_var($store_data['addr_num'], FILTER_SANITIZE_NUMBER_INT)),
                        'complement'    => ($store_data['addr_compl']) ? $store_data['addr_compl'] : '',
                        'district'      => $store_data['addr_neigh'],
                        'zipCode'       => $this->formatCep($store_data['zipcode']),
                        'city'          => $store_data['addr_city'],
                        'state'         => $store_data['addr_uf'],
                        'country'       => 'BRA'
                    )
                ),
                'company'               => array(
                    'name'              => $store_data['name'],
                    'businessName'      => $store_data['raz_social'],
                    'taxDocument'       => array(
                        'type'          => 'CNPJ',
                        // 'number'        => stripslashes($this->formatDoc($store_data['CNPJ']))
                        'number'        => stripslashes($this->onlyNumbers($store_data['CNPJ']))
                    ),
                    'phone'             => array(
                        'countryCode'   => '55',
                        'areaCode'      => substr($store_data['phone_1'], 0, 2),
                        'number'        => str_pad(substr($store_data['phone_1'], 2), 9, 0, STR_PAD_LEFT)
                    ),
                    'address' => array(
                        'street'        => $store_data['business_street'],
                        'streetNumber'  => intVal(filter_var($store_data['business_addr_num'], FILTER_SANITIZE_NUMBER_INT)),
                        'complement'    => ($store_data['business_addr_compl']) ? $store_data['business_addr_compl'] : '',
                        'district'      => $store_data['business_neighborhood'],
                        'zipCode'       => $this->formatCep($store_data['business_code']),
                        'city'          => $store_data['business_town'],
                        'state'         => $store_data['business_uf'],
                        'country'       => 'BRA'
                    )
                ),
                'type'                  => 'MERCHANT',
                'transparentAccount'    => true
            );

            $data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $data_json = stripslashes($data_json);

            $new_subaccount = $this->moipTransactions($url, 'POST', $data_json);

            if (substr($this->responseCode, 0, 1) == 2)
                $moip_subaccount_created = 'new';

        }
        else
        {
            $moip_subaccount_created = 'existent';

            $local_subaccount = $store_data['id'];

            $new_bank_account = $this->model_moip->getBankId($store_exists['moip_id']);

            if (empty($new_bank_account))
            {
                $new_bank_account = $this->createUpdateBankAccount($store_exists['tbl_id'], $store_data);

                if (false !== $new_bank_account)
                    $success[] = "Conta Bancária MoIP cadastrada com sucesso";
            }

            $subaccount_data = array
            (
                'id' => $store_exists['moip_id']
            );
        }

        if ($moip_subaccount_created)
        {
            if ($moip_subaccount_created == 'new')
            {
                $success[] = "Subconta MoIP Criada no Gateway com Sucesso";

                $subaccount_data = json_decode($new_subaccount, true);

                $local_subaccount = $this->saveCreatedSubaccount($store_data, $subaccount_data);

                if ($local_subaccount > 0)
                {
                    $success[] = "Conta MoIP registrada no sistema";

                    $new_bank_account = $this->createUpdateBankAccount($local_subaccount, $store_data);

                    if (false !== $new_bank_account)
                        $success[] = "Conta Bancária MoIP cadastrada com sucesso";
                }
                else
                {
                    echo $error = "Não foi possível gravar a Subconta no Banco interno: ".$data_json." retornou ".$local_subaccount;
                    $this->log_data(
                        'batch',
                        __FUNCTION__,
                        $error,
                        "E"
                    );
                    $this->model_payment_gateway_store_logs->insertLog(
                        $store_data['id'],
                        $this->gateway_id ,
                        $error
                    );
                    return false;
                }
            }

            //gravando a conta na yami
            $service_charge = str_replace(',', '.', $store_data['service_charge_value']);

            if (false === strpos('.', $service_charge))
                $service_charge .= '.00';

            $service_charge_freight = $store_data['service_charge_freight_value'];

            $include_freight = ($service_charge_freight > 0) ? 1 : 0;

            $ymi_array = array
            (
                "active" => 1,
                "include_freight" => $include_freight,
                "name" => $store_data['name'],
                "email" => $store_data['responsible_email'],
                "dockId" => array ($seller_id_integration),
                "commission" => array
                    (array(
                        "type" => "percent",
                        "value" => $service_charge
                        )
                    ),
                "gatewayAccount" => $subaccount_data['id'],
                "street" => $store_data['business_street'],
                "number" => (empty($store_data['business_addr_num'])) ? '0' : $store_data['business_addr_num'],
                "complement" => $store_data['business_addr_compl'],
                "neighborhood" => $store_data['business_neighborhood'],
                "city" => $store_data['business_town'],
                "uf" => $store_data['business_uf'],
                "document" => array (
                    "type" => "CNPJ",
                    "value" => stripslashes($this->onlyNumbers($store_data['CNPJ']))
                ),
                "birth_date" => $birth_date,
                "phone" => "(".substr($store_data['phone_1'], 0, 2).") ".str_pad(substr($store_data['phone_1'], 2), 9, 0, STR_PAD_LEFT),
                "postal_code" => $this->formatCep($store_data['business_code']),
                "country" => "BRA",
                "company_data" => array
                (
                    "name" => $store_data['name'],
                    "businessName"  => $store_data['name'],
                    "taxDocument"=> stripslashes($this->onlyNumbers($store_data['CNPJ'])),
                    "phone"=> "(".substr($store_data['phone_1'], 0, 2).") ".str_pad(substr($store_data['phone_1'], 2), 9, 0, STR_PAD_LEFT),
                    "street"=> $store_data['business_street'],
                    "number"=> (empty($store_data['business_addr_num'])) ? 'SN' : $store_data['business_addr_num'],
                    "complement"=> $store_data['business_addr_compl'],
                    "neighborhood"  => $store_data['business_neighborhood'],
                    "city"          => $store_data['business_town'],
                    "zipCode"       => $this->formatCep($store_data['business_code']),
                    "state"=> $store_data['business_uf'],
                    "country"=> "BRA"
                )
            );

            $ymi_json = json_encode($ymi_array, JSON_UNESCAPED_UNICODE);
            $ymi_json = stripslashes($ymi_json);

            //gravando o json de envio da yami para fins de rastreio
            $this->model_payment_gateway_store_logs->insertLog(
                $store_data['id'],
                $this->gateway_id ,
                $ymi_json,
                'json_post'                
            );

            $url = $this->ymi_url.'sellers/';

            $headers = array
            (
                'Content-Type: application/json',
                'x-ymi-token: '.$this->ymi_token
            );

            $new_ymi_account = $this->moipTransactions($url, 'POST', $ymi_json, $headers);

            if (substr($this->responseCode, 0, 1) == 2)
            {
                //grava uma copia simplificada na gateway_subaccounts
                $gateway_subaccounts_array = array
                (
                    'store_id' => $store_data['id'],
                    'gateway_account_id' => $subaccount_data['id'],
                    'gateway_id' => $this->gateway_id,
                    'bank_account_id' => $new_bank_account
                );

                $gateway_subaccount_exists = $this->model_gateway->getSubAccountByStoreId($store_data['id'], $this->gateway_id);

                if ($gateway_subaccount_exists)
                {
                    if ($gateway_subaccount_exists['bank_account_id'] != $new_bank_account)
                        $updated_gateway_subaccount = $this->model_gateway->updateSubAccounts($gateway_subaccount_exists['id'], $gateway_subaccounts_array);
                }
                else
                {
                    $new_gateway_subaccount = $this->model_gateway->createSubAccounts($gateway_subaccounts_array);
                }

                $success[] = "Conta Yami cadastrada com sucesso";

                $success = implode("\r\n", $success);

                $this->model_payment_gateway_store_logs->insertLog(
                    $store_data['id'],
                    $this->gateway_id ,
                    $success,
                    Model_payment_gateway_store_logs::STATUS_SUCCESS
                );

                $ymi_return = json_decode($new_ymi_account, true);

                $ymi_id = $ymi_return['response']['id'];

                return $this->model_moip->updateYmiAcc($ymi_id, $subaccount_data['id']);
            }
            else
            {
                echo $error = "Não foi possível criar a conta Yami retornou ".$new_ymi_account;

                $this->log_data(
                    'batch',
                    __FUNCTION__,
                    $error,
                    "E"
                );

                $this->model_payment_gateway_store_logs->insertLog(
                    $store_data['id'],
                    $this->gateway_id ,
                    $error
                );
                
                return false;
            }
        }
        else
        {
            echo $error = "Não foi possível criar a Subconta ".$data_json." retornou ".$new_subaccount;
            $this->log_data(
                'batch',
                __FUNCTION__,
                $error,
                "E"
            );
            $this->model_payment_gateway_store_logs->insertLog(
                $store_data['id'],
                $this->gateway_id ,
                $error
            );
            return false;
        }
    }


    //braun -> inserir nos jobs este script
    public function gatewayUpdateBalance($id = null, $params = null)
    {
        // $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        // $this->gravaInicioJob($modulePath, __FUNCTION__, $params);
        
        //braun hack
        if (ENVIRONMENT === 'development') 
        {
            $this->model_moip->mockMethod(ENVIRONMENT);
            return;
        }        

        $this->startJob(__FUNCTION__, $id, $params);
        $log_name = $this->logName;

        $url_balance = $this->api_url.'/balances';
        $url_balance = str_replace(':/', '://', str_replace('//', '/', $url_balance));        

        $mktplace_balance_result = $this->moipTransactions($url_balance, 'GET');
        $mktplace_balance = json_decode($mktplace_balance_result, true);

        if (isset($mktplace_balance[0]['current']))
        {
            echo "realizando atualizaçao de saldo do marketplace \n";

            $mktplace_balance_data = array
            (
                'gateway_id' => $this->gateway_id,
                'available' => $mktplace_balance[0]['current'],
                'future' => $mktplace_balance[0]['future'],
                'unavailable' => $mktplace_balance[0]['unavailable']
            );

            $this->model_moip->updateMktBalance($mktplace_balance_data);
        }

        $moip_subaccounts = $this->model_moip->getMoipSubaccounts();

        if (!empty($moip_subaccounts))
        {
            foreach ($moip_subaccounts as $k => $acc)
            {
                $headers = array(
                    'Content-Type: application/json;version=2.1',
                    'Authorization: Bearer '.$acc['access_token']
                );

                $balance_result = $this->moipTransactions($url_balance, 'GET', null, $headers);

                $balance = json_decode($balance_result, true);

                if (isset($balance[0]['current']))
                {
                    echo "realizando atualizaçao de saldo ".$acc['store_id']." \n";

                    $balance_data = array
                    (
                        'gateway_id' => $this->gateway_id,
                        'store_id' => $acc['store_id'],
                        'available' => $balance[0]['current'],
                        'future' => $balance[0]['future'],
                        'unavailable' => $balance[0]['unavailable']
                    );

                    $this->model_moip->updateBalance($balance_data);
                }
                else
                {
                    $errors = 'Saldo não consultado: ('. $balance_result. ') - dados enviados: '.serialize($headers);

                    echo $errors." \n";

                    $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

                    $this->createLogs($errors, $log_name);
                }
            }
        }
        else
        {
            echo 'Não foram encontradas contas MOIP para realizar Consulta de Saldo';
        }

        if (!empty($errors))
            echo " - ERRO na Consulta de Saldo \r\n";            
        else
            echo 'Consulta de Saldo executada sem erros.';

        $this->endJob();
    }

    //braun
    public function gatewayUpdateStatements($id = null, $params = null)
    {
        $this->startJob(__FUNCTION__, $id, $params);
        $log_name = $this->logName;

        $errors = [];

        $moip_subaccounts = $this->model_moip->getMoipSubaccounts();

        if (!empty($moip_subaccounts))
        {
            foreach ($moip_subaccounts as $acc)
            {
                //braun hack
                // $this->api_url = 'https://api.moip.com.br/v2/';

                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$acc['access_token']
                );

                //extrato passado
                $url_past = $this->api_url.'/statements?begin='.date('Y-m-d', strtotime('-3 days')).'&end='.date("Y-m-d");
                $url_past = str_replace(':/', '://', str_replace('//', '/', $url_past));

                $past_balance_result = $this->moipTransactions($url_past, 'GET', null, $headers);
                // $past_balance_result = '{"summary":{"creditSum":221529,"debitSum":0},"lines":[{"date":"2022-08-18","amount":94731,"_links":{"self":{"href":"/statements/details?type=8&date=2022-08-18"}},"description":"Recebíveis como recebedor secundário","type":8},{"date":"2022-08-19","amount":126798,"_links":{"self":{"href":"/statements/details?type=8&date=2022-08-19"}},"description":"Recebíveis como recebedor secundário","type":8}]}';
                
                $past_balances = json_decode($past_balance_result, true);

                if (!empty($past_balances) && is_array($past_balances))
                {
                    $this->iterateBalances($past_balances, $acc['store_id'], $headers);
                }
                else
                {
                    $errors[] = 'past_balances com problema ';
                    echo '###### PAST';
                    var_dump($past_balances);
                }

                //extrato futuro
                $url_future = $this->api_url.'/futurestatements?begin='.date('Y-m-d', strtotime('+1 day')).'&end='.date("Y-m-d", strtotime('+1 month'));
                $url_future = str_replace(':/', '://', str_replace('//', '/', $url_future));

                $future_balance_result = $this->moipTransactions($url_future, 'GET', null, $headers);

                $future_balances = json_decode($future_balance_result, true);

                if (!empty($future_balances) && is_array($future_balances))
                {
                    $this->iterateBalances($future_balances, $acc['store_id'], $headers, 1);
                }
                else
                {
                    $errors[] = 'future_balances com problema ';
                    echo '###### FUTURE';
                    var_dump($future_balances);
                }
            }
        }
        else
        {
            echo "Erro ao atualizar os recebimentos em cartão  \r\n";
            print_r($errors);
            $this->endJob();
            return false;
        }

        echo 'Atualização de recebimentos em cartão concluída.';

        $this->endJob();
    }
    

    //braun
    public function iterateBalances($balances, $store_id, $headers, $future = null)
    {
        if (!is_array($balances) || !isset($balances['lines']))
            return false;

        foreach ($balances['lines'] as $balance)
        {
            $url_statement =  $this->api_url.$balance['_links']['self']['href'];
            $url_statement = str_replace(':/', '://', str_replace('//', '/', $url_statement));

            $statement_result = $this->moipTransactions($url_statement, 'GET', null, $headers, $store_id);
            // $statement_result = '{"summary":{"date":"2022-08-18","entryCount":1,"description":"Recebíveis como recebedor secundário","entrySum":94731},"entries":[{"eventId":"PAY-VNCGJI0TJ64Z","fees":[{"amount":0,"type":"TRANSACTION"}],"references":[{"type":"PAYMENT","value":"PAY-VNCGJI0TJ64Z"},{"type":"ORDER","value":"ORD-1RI3ZEJRUAXM"}],"description":"Recebedor secundario - Pedido PAY-VNCGJI0TJ64Z","external_id":"ENT-IX6P1T9Q2EC2","grossAmount":94731,"type":"COMMISSION","liquidAmount":94731,"reschedule":[],"createdAt":"2022-07-19T12:08:53.000-03","moipAccountId":11113259,"blocked":false,"settledAt":"2022-08-18T04:12:38.000-03","installment":{"number":1,"amount":1},"scheduledFor":"2022-08-18T12:08:52.000-03","id":539416098,"moipAccount":{"account":"MPA-0D8330E9F890"},"status":"SETTLED","updatedAt":"2022-08-18T04:12:38.000-03"}],"_links":{"self":{"href":"/statements/details?type=8&date=2022-08-18"}}}';

            $statements = json_decode($statement_result, true);

            if (!empty($statements['entries']))
            {
                foreach ($statements['entries'] as $entry)
                {
                    $orders = [];

                    $payment_id = $order_id = null;

                    if (!empty($entry['references']))
                    {
                        foreach ($entry['references'] as $reference)
                        {
                            if ($reference['type'] == 'PAYMENT')
                                $payment_id =  $reference['value'];

                            if ($reference['type'] == 'ORDER')
                                $order_id =  $reference['value'];
                        }
                    }

                    if (empty($payment_id))
                    {
                        $payment_id = $entry['eventId'];
                    }

                    $url_payment = $this->api_url.'/payments/'.$payment_id;
                    $url_payment = str_replace(':/', '://', str_replace('//', '/', $url_payment));
    
                    //alteração nao politica moip?
                    //só era possivel puxar detalhes de pagamento utilizando o token do marketplace
                    //agora este token retorna erro e só é possivel  puxar detalhes com o token do seller
                    //houveram alterações
                    // $payment_order_headers = array(
                    //     'Content-Type: application/json',
                    //     'Authorization: Bearer '.$this->app_token
                    // );

                    // $payment_result = $this->moipTransactions($url_payment, 'GET', null, $payment_order_headers, $store_id);

                    $payment_result = $this->moipTransactions($url_payment, 'GET', null, $headers, $store_id);
                    // $payment_result = '{"id":"PAY-VNCGJI0TJ64Z","status":"SETTLED","delayCapture":true,"amount":{"total":115525,"gross":115525,"fees":8668,"refunds":0,"liquid":106857,"currency":"BRL"},"installmentCount":10,"fundingInstrument":{"creditCard":{"id":"CRC-8S3RHJ271JC5","brand":"MASTERCARD","first6":"520048","last4":"5336","store":true,"holder":{"birthdate":"1988-12-30","birthDate":"1988-12-30","taxDocument":{"type":"CPF","number":"38409248808"},"billingAddress":{"street":"Rua Ilansa","streetNumber":"175","complement":"Casa","district":"Vila Prudente","city":"São Paulo","state":"SP","country":"BRA","zipCode":"03127070"},"fullname":"Allan H R Macedo"}},"method":"CREDIT_CARD"},"acquirerDetails":{"authorizationNumber":"071974580137","taxDocument":{"type":"CNPJ","number":"08561701000101"}},"fees":[{"type":"TRANSACTION","amount":2299},{"type":"PRE_PAYMENT","amount":6369}],"events":[{"type":"PAYMENT.SETTLED","createdAt":"2022-08-18T01:55:16.000-03"},{"type":"PAYMENT.AUTHORIZED","createdAt":"2022-07-19T12:08:52.000-03"},{"type":"PAYMENT.PRE_AUTHORIZED","createdAt":"2022-07-19T12:06:32.000-03"},{"type":"PAYMENT.IN_ANALYSIS","createdAt":"2022-07-19T12:06:25.000-03"},{"type":"PAYMENT.WAITING","createdAt":"2022-07-19T12:06:21.000-03"},{"type":"PAYMENT.CREATED","createdAt":"2022-07-19T12:06:20.000-03"}],"receivers":[{"moipAccount":{"id":"MPA-H7F4XQVF6DMN","login":"POLISHOP01","fullname":"POLIMPORT COM EXP LTDA "},"type":"PRIMARY","amount":{"total":20794,"currency":"BRL","fees":0,"refunds":0},"feePayor":false},{"moipAccount":{"id":"MPA-0D8330E9F890","login":"MPA-0D8330E9F890","fullname":"P.C CHACUR INTERMEDIAÇÃO E AGENCIAMENTO DE SERVIÇOS E NEGOCIOS EM GERAL"},"type":"SECONDARY","amount":{"total":94731,"currency":"BRL","fees":0,"refunds":0},"feePayor":false}],"_links":{"self":{"href":"https://api.moip.com.br/v2/payments/PAY-VNCGJI0TJ64Z"},"order":{"href":"https://api.moip.com.br/v2/orders/ORD-1RI3ZEJRUAXM","title":"ORD-1RI3ZEJRUAXM"}},"createdAt":"2022-07-19T12:06:20.000-03","updatedAt":"2022-08-18T01:55:16.000-03"}';

                    $payment = json_decode($payment_result, true);

                    if (@$payment['fundingInstrument']['method'] != 'CREDIT_CARD')
                        continue;

                    if (empty($order_id))
                        $order_id = $payment['_links']['order']['title'];

                    $url_order = $this->api_url.'/orders/'.$order_id;
                    $url_order = str_replace(':/', '://', str_replace('//', '/', $url_order));

                    // $order_result = $this->moipTransactions($url_order, 'GET', null, $payment_order_headers, $store_id);

                    $order_result = $this->moipTransactions($url_order, 'GET', null, $headers, $store_id);
                    // $order_result = '{"id":"ORD-1RI3ZEJRUAXM","ownId":"v46173439plsh-PS003","status":"PAID","platform":"V2","createdAt":"2022-07-19T12:06:17.000-03","updatedAt":"2022-08-18T01:55:16.000-03","amount":{"paid":115525,"total":115525,"fees":0,"refunds":0,"liquid":94731,"otherReceivers":20794,"currency":"BRL","subtotals":{"shipping":0,"addition":0,"discount":0,"items":115525}},"items":[{"price":115525,"detail":"2008240","quantity":1,"product":"KOIOS Mixer 4 em 1 Multifuncional com Acessorios e 12 Velocidades 110V800W Preto"}],"customer":{"id":"CUS-ZYLPYSM96RMZ","ownId":"b7ea432d-70fb-46ef-922d-8b779e48d256","fullname":"Allan Romero","createdAt":"2022-07-19T12:06:17.000-03","updatedAt":"2022-07-19T12:06:17.000-03","birthDate":"1988-12-30","shippingAddress":{"zipCode":"03127070","street":"Rua Ilansa","streetNumber":"175","complement":"Casa","city":"São Paulo","district":"Vila Prudente","state":"SP","country":"BRA"},"moipAccount":{"id":"MPA-23P72DLCECGD"},"_links":{"self":{"href":"https://api.moip.com.br/v2/customers/CUS-ZYLPYSM96RMZ"},"hostedAccount":{"redirectHref":"https://hostedaccount.moip.com.br?token=cd5029ed-0d91-4e32-a86b-0c8af5ebe4fa&id=CUS-ZYLPYSM96RMZ&mpa=MPA-H7F4XQVF6DMN"}}},"payments":[{"id":"PAY-VNCGJI0TJ64Z","status":"SETTLED","delayCapture":true,"amount":{"total":115525,"gross":115525,"fees":8668,"refunds":0,"liquid":106857,"currency":"BRL"},"installmentCount":10,"fundingInstrument":{"creditCard":{"id":"CRC-8S3RHJ271JC5","brand":"MASTERCARD","first6":"520048","last4":"5336","store":true,"holder":{"birthdate":"1988-12-30","birthDate":"1988-12-30","taxDocument":{"type":"CPF","number":"38409248808"},"billingAddress":{"street":"Rua Ilansa","streetNumber":"175","complement":"Casa","district":"Vila Prudente","city":"São Paulo","state":"SP","country":"BRA","zipCode":"03127070"},"fullname":"Allan H R Macedo"}},"method":"CREDIT_CARD"},"acquirerDetails":{"authorizationNumber":"071974580137","taxDocument":{"type":"CNPJ","number":"08561701000101"}},"fees":[{"type":"TRANSACTION","amount":2299},{"type":"PRE_PAYMENT","amount":6369}],"events":[{"type":"PAYMENT.SETTLED","createdAt":"2022-08-18T01:55:16.000-03"},{"type":"PAYMENT.AUTHORIZED","createdAt":"2022-07-19T12:08:52.000-03"},{"type":"PAYMENT.PRE_AUTHORIZED","createdAt":"2022-07-19T12:06:32.000-03"},{"type":"PAYMENT.IN_ANALYSIS","createdAt":"2022-07-19T12:06:25.000-03"},{"type":"PAYMENT.WAITING","createdAt":"2022-07-19T12:06:21.000-03"},{"type":"PAYMENT.CREATED","createdAt":"2022-07-19T12:06:20.000-03"}],"receivers":[{"moipAccount":{"id":"MPA-H7F4XQVF6DMN","login":"POLISHOP01","fullname":"POLIMPORT COM EXP LTDA "},"type":"PRIMARY","amount":{"total":20794,"currency":"BRL","fees":0,"refunds":0},"feePayor":false},{"moipAccount":{"id":"MPA-0D8330E9F890","login":"MPA-0D8330E9F890","fullname":"P.C CHACUR INTERMEDIAÇÃO E AGENCIAMENTO DE SERVIÇOS E NEGOCIOS EM GERAL"},"type":"SECONDARY","amount":{"total":94731,"currency":"BRL","fees":0,"refunds":0},"feePayor":false}],"_links":{"self":{"href":"https://api.moip.com.br/v2/payments/PAY-VNCGJI0TJ64Z"},"order":{"href":"https://api.moip.com.br/v2/orders/ORD-1RI3ZEJRUAXM","title":"ORD-1RI3ZEJRUAXM"}},"createdAt":"2022-07-19T12:06:20.000-03","updatedAt":"2022-08-18T01:55:16.000-03"}],"escrows":[],"refunds":[],"entries":[{"id":"ENT-IX6P1T9Q2EC2","event":"PAY-VNCGJI0TJ64Z","status":"SETTLED","operation":"CREDIT","amount":{"total":94731,"fee":0,"liquid":94731,"currency":"BRL"},"description":"Recebedor secundario - Pedido PAY-VNCGJI0TJ64Z","occurrence":{"in":"1","to":"1"},"scheduledFor":"2022-08-18T12:08:52.000-03","settledAt":"2022-08-18T04:12:38.000-03","updatedAt":"2022-08-18T04:12:38.000-03","createdAt":"2022-07-19T12:08:53.000-03","_links":{"payment":{"href":"https://api.moip.com.br/v2/payments/PAY-VNCGJI0TJ64Z","title":"PAY-VNCGJI0TJ64Z"},"order":{"href":"https://api.moip.com.br/v2/orders/ORD-1RI3ZEJRUAXM","title":"ORD-1RI3ZEJRUAXM"},"self":{"href":"https://api.moip.com.br/v2/entries/ENT-IX6P1T9Q2EC2"}}}],"events":[{"type":"ORDER.PAID","createdAt":"2022-07-19T12:08:52.000-03","description":""},{"type":"ORDER.WAITING","createdAt":"2022-07-19T12:06:32.000-03","description":""},{"type":"ORDER.WAITING","createdAt":"2022-07-19T12:06:21.000-03","description":""},{"type":"ORDER.CREATED","createdAt":"2022-07-19T12:06:17.000-03","description":""}],"shippingAddress":{"zipCode":"03127070","street":"Rua Ilansa","streetNumber":"175","complement":"Casa","city":"São Paulo","district":"Vila Prudente","state":"SP","country":"BRA"},"channel":{"externalId":"APP-8FF39MVEIVRD"},"_links":{"self":{"href":"https://api.moip.com.br/v2/orders/ORD-1RI3ZEJRUAXM"},"multiOrder":{"href":"https://api.moip.com.br/v2/multiorders/MOR-AZZ2998V2PNG"},"checkout":{"payCheckout":{"redirectHref":"https://checkout-new.moip.com.br?token=a90622de-d88d-426e-815a-bb00859f6461&id=ORD-1RI3ZEJRUAXM"},"payCreditCard":{"redirectHref":"https://checkout-new.moip.com.br?token=a90622de-d88d-426e-815a-bb00859f6461&id=ORD-1RI3ZEJRUAXM&payment-method=credit-card"},"payBoleto":{"redirectHref":"https://checkout-new.moip.com.br?token=a90622de-d88d-426e-815a-bb00859f6461&id=ORD-1RI3ZEJRUAXM&payment-method=boleto"},"payOnlineBankDebitItau":{"redirectHref":"https://checkout.moip.com.br/debit/itau/ORD-1RI3ZEJRUAXM"}}}}';

                    $order = json_decode($order_result, true);

                    $date_payment = @$entry['settledAt'];

                    $own_id = explode('-', $order['ownId'])[0];
                    $numero_marketplace = $this->model_moip->getCorrectOrderMktplace($own_id, $store_id);

                    if (!$numero_marketplace)
                    {
                        //pelo rastreio indica que este numero_marketplace esta perdido na moip mas pertence tambem a outra store, pq foi um pedido com mais de 1 seller
                        //o fluxo vai registrar ele corretamente com outro store_id 
                        continue;
                        // $store_moip_id = $payment['receiver'][1]['moipAccount']['id'];
                        // $numero_marketplace = $this->model_moip->getCorrectOrderMktplace($own_id, null, $store_moip_id);
                    }
                    
                    //monto dados do pedido
                    $orders['store_id'] = $store_id;
                    $orders['order_id'] = $numero_marketplace;
                    $orders['order_ownid'] = $order['ownId'];
                    $orders['payment_gateway_id'] = $payment_id;
                    $orders['order_gateway_id'] = $order_id;
                    $orders['type'] = $balance['type'];
                    $orders['date_order'] = explode('T', $entry['createdAt'])[0];
                    $orders['date_payment'] = explode('T', $date_payment)[0];                                
                    $orders['date_scheduled'] = explode('T', $entry['scheduledFor'])[0];                                
                    $orders['payment_status'] = $payment['status'];                                
                    $orders['amount_total'] = $payment['amount']['total'];
                    $orders['amount_mkt'] = $payment['receivers'][0]['amount']['total'];
                    $orders['amount_mkt_fees'] = $payment['receivers'][0]['amount']['fees'];
                    $orders['amount_seller'] = $payment['receivers'][1]['amount']['total'];
                    $orders['amount_seller_fees'] = $payment['receivers'][1]['amount']['fees'];

                    $this->model_payment->saveStatement($orders, $url_order);
                }
            }
        }
    }

    //braun
    public function createLogs($errors, $log_name)
    {
        $this->log_data(
            'batch',
            $log_name,
            "Consulta de Saldo em ". $this->gateway_name.": ".$errors,
            "E"
        );

        $this->model_payment_gateway_store_logs->insertLog(
            '0',
            $this->gateway_id,
            $errors
        );
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que cuidam dos repasses
     * ------------------------------------------------------
     */

    public function runPayments($gateway = false)
    {
        $this->startJob(__FUNCTION__, $gateway);
        echo $this->screen_message_header;  

        //braun hack
        if (ENVIRONMENT === 'development') 
        {
            $this->model_moip->mockMethod();
            return;
        }

        $conciliations = $this->model_conciliation->getOpenConciliations($gateway);

        if (!$conciliations)
        {
            echo "Nenhuma conciliação para executar o Repasse";
            echo $this->screen_message_footer;

            return true;
        }

        $current_day = date("j");

        foreach ($conciliations as $conciliation)
        {
            // if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25))
                // continue;            

            if (!in_array($conciliation['status_repasse'], [21,25]))
                continue;

            echo "Processando conciliação: {$conciliation['conciliacao_id']}".PHP_EOL;

            $this->moiplibrary->processTransfers($conciliation['conciliacao_id']);
        }

        echo $this->screen_message_footer;

        return true;
    }


    //braun
    public function returnAdvancePayments($id = null, $params = null)
    {
        $this->startJob(__FUNCTION__, $id, $params);
        echo $this->screen_message_header;     
        
        if (!$this->moip_return_advanced_payments)
        {
            echo "Configurado para não retornar pendencias";
            echo $this->screen_message_footer;

            $this->endJob();
            return true;
        }

        $pendencies = $this->model_moip->getPendencies();

        if (!$pendencies)
        {
            echo "Não há pendências a transferir";
            echo $this->screen_message_footer;

            $this->endJob();
            return true;
        }

        //braun hack
        // $this->api_url = 'https://api.moip.com.br/v2/';        

        $url_balance = str_replace(':/', '://', str_replace('//', '/', $this->api_url.'/balances'));        

        foreach ($pendencies as $pendency)
        {
            //braun hack
            if (ENVIRONMENT === 'development') 
            {
                $pendency['amount'] = 1;
            }

            $pendency_amount    = abs(intVal($pendency['amount']));
            $pendency_token     = $pendency['access_token'];
            $pendency_id        = $pendency['pendency_id'];
            $pendency_moip_id   = $pendency['moip_id'];
            $pendency_store_id  = $pendency['store_id'];

            $headers = array(
                'Content-Type: application/json;version=2.1',
                'Authorization: Bearer '.$pendency_token
            );

            $balance_result = $this->moipTransactions($url_balance, 'GET', null, $headers);

            if ($this->responseCode != '200')
            {
                echo $msg = "Erro ao realizar transaction ".serialize([$url_balance, 'GET', null, $headers]);
                echo $this->screen_message_footer;

                $this->log_data('batch', __FUNCTION__, $msg, "E");
                $this->model_payment_gateway_store_logs->insertLog($pendency_store_id, $this->gateway_id, $msg);

                continue;
            }

            $balance_current = json_decode($balance_result, true)[0]['current'];

            if ($pendency_amount > $balance_current)
            {
                echo $msg = "IMpossível transferir ".$pendency_amount." enquanto o saldo disponível é ".$balance_current;
                echo $this->screen_message_footer;

                $this->log_data('batch', __FUNCTION__, $msg, "E");
                $this->model_payment_gateway_store_logs->insertLog($pendency_store_id, $this->gateway_id, $msg);

                continue;
            }

            $create_transfer = $this->moiplibrary->createTransfer(array('id' => $pendency_id, 'pendency' => 1), $pendency_moip_id, $this->app_account, $pendency_amount, 'MOIP');

            if (substr($create_transfer, 0, 3) !== 'TRA')
            {
                echo $msg = "Erro ao transferir via createTransfer ".serialize([array('id' => $pendency_id), $pendency_moip_id, $this->app_account, $pendency_amount, 'MOIP']);
                echo $this->screen_message_footer;

                $this->log_data('batch', __FUNCTION__, $msg, "E");
                $this->model_payment_gateway_store_logs->insertLog($pendency_store_id, $this->gateway_id, $msg);

                continue;
            }

            $settle_pendency = $this->model_moip->settlePendency($pendency_id);

            if (!$settle_pendency)
            {
                $error = "Fluxo Moip - Devolução de valores (gateway_pendencies id = ".$pendency_id.") executou com sucesso, porém não foi possível atualizar o status para realizado";

                $this->log_data(
                    'batch',
                    __FUNCTION__,
                    $error,
                    "E"
                );

                $this->model_payment_gateway_store_logs->insertLog(
                    $pendency_store_id,
                    $this->gateway_id ,
                    $error
                );

                continue;
            }

            $success = "Realizada Devolução de Adiantamento de Cartão de Crédito ".serialize($pendency);

            $this->model_payment_gateway_store_logs->insertLog(
                $pendency_store_id,
                $this->gateway_id ,
                $success
            );
        }

        echo "JOB FINALIZADO";
        echo $this->screen_message_footer;

        $this->endJob();
        return true;
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam os Dados Bancários
     * ------------------------------------------------------
     */

    public function createUpdateBankAccount($subaccount_tbl_id = false, $store_data = null, $is_update = false)
    {
        if(empty($store_data) || !is_array($store_data))
            return false;
        
        // $moip_store_data = $this->model_moip->getMoipStoreData(array("tbl_id" => $subaccount_tbl_id));
        $moip_store_data = $this->model_moip->getMoipStoreData(array("store_id" => $store_data['id']));

        $this->app_token    = $moip_store_data['access_token'];
        $subaccount_tbl_id  = $moip_store_data['tbl_id'];
        $subaccount_moip_id = $moip_store_data['moip_id'];

        $agency         = $store_data['agency'];
        $agency_check   = '';

        if (false !== strpos($store_data['agency'], '-'))
        {
            $agency = explode('-', $store_data['agency'])[0];
            $agency_check = explode('-', $store_data['agency'])[1];
        }

        if (false !== strpos($store_data['account'], '-'))
        {
            $account = explode('-', $store_data['account'])[0];
            $account_check = explode('-', $store_data['account'])[1];
        }
        else
        {
            $account = substr($store_data['account'], 0, -1);
            $account_check = substr($store_data['account'], -1);
        }

        $bank_account_data = array
        (
            "bankNumber" => $this->model_moip->getBankCode($store_data['bank']),
            "agencyNumber" => $this->onlyNumbers($agency),
            "agencyCheckNumber" => $agency_check,
            "accountNumber" => $this->onlyNumbers($account),
            "accountCheckNumber" => $this->onlyNumbers($account_check),
            "type" => "CHECKING",
            "holder" => array(
                "taxDocument" => array(
                    "type" => "CNPJ",
                    "number" => preg_replace('/\D/', '', $store_data['CNPJ'])
                ),
                "fullname" => substr($store_data['name'], 0, 90)
            )
        );

        $url = $this->api_url.'/accounts/'.$subaccount_moip_id.'/bankaccounts';
        $url = str_replace(':/', '://',str_replace('//', '/', $url));

        $bank_account_json = json_encode($bank_account_data, JSON_UNESCAPED_UNICODE);

        $bank_account_result = $this->moipTransactions($url, 'POST', $bank_account_json, __FUNCTION__);

        if (substr($this->responseCode, 0, 1) == 2)
        {
            $bank_account_data = json_decode($bank_account_result, true);

            $bank_account_array = array
            (                
                'bank_moip_id'          => $bank_account_data['id'],
                'bank_number'           => $bank_account_data['bankNumber'],
                'bank_agency'           => $bank_account_data['agencyNumber'],
                'bank_agency_check'     => ($bank_account_data['agencyCheckNumber'] == "") ? NULL : $bank_account_data['agencyCheckNumber'],
                'bank_account'          => $bank_account_data['accountNumber'],
                'bank_account_check'    => $bank_account_data['accountCheckNumber'],
            );

            if (!$is_update)
            {
                $bank_account_array = $bank_account_array + array
                (
                'verified'              => 'N',
                'subaccount_tbl_id'     => $subaccount_tbl_id,
                'subaccount_moip_id'    => $subaccount_moip_id,
                'document'              => preg_replace('/\D/', '', $bank_account_data['holder']['taxDocument']['number']),
                'full_name'             => $bank_account_data['holder']['fullname']
                );
            }

            if ($is_update)
            {
                $bank_tbl_id = $this->model_moip->getBankDataByStoreId($store_data['id']);  
                $bank_account_array = $bank_account_array + array
                (
                    'tbl_id'            => $bank_tbl_id['bank_tbl_id']
                );
            }

            $new_bank_account = $this->model_moip->saveBankAccount($subaccount_tbl_id, $bank_account_array, $is_update);

            if ($new_bank_account > 0)
            {
                $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($store_data['id'], $this->gateway_id);

                if($gatewaySubaccount['id'])
                {
                    $gateway_subaccounts_array = array
                    (
                        'bank_account_id' => $bank_account_data['id']
                    );

                    $updated_gateway_subaccount = $this->model_gateway->updateSubAccounts($gatewaySubaccount['id'], $gateway_subaccounts_array);
                }
                
                echo "Conta Bancária da subconta ".$subaccount_moip_id." foi associada ao item ".$new_bank_account." - ".$bank_account_array['bank_moip_id']." da tabela de contas bancarias \r\n ";

                if ($is_update)
                {
                    $success = "Conta bancária Editada com sucesso";

                    $this->model_payment_gateway_store_logs->insertLog(
                        $store_data['id'],
                        $this->gateway_id ,
                        $success,
                        Model_payment_gateway_store_logs::STATUS_SUCCESS
                    );
                }

                return $bank_account_data['id'];
            }
        }

        echo $error = "ERRO: Não foi possivel criar ou atualizar a conta bancária. Json enviado: ".$bank_account_json." -- json recebido: ".$bank_account_result." \r\n";
        $this->log_data(
            'batch',
            __FUNCTION__,
            $error,
            "E"
        );
        $this->model_payment_gateway_store_logs->insertLog(
            $store_data['id'],
            $this->gateway_id ,
            $error
        );
        return false;
    }


    protected function moipTransactions($url, $method = 'GET', $data = null, $headers = false, $store_id = null)
    {
        if (!$headers || !is_array($headers))
        {
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->app_token
            );
        }
        
        print_r($store_id);
        echo "\r\n";
        print_r($url);
        echo "\r\n";
        print_r($headers);
        echo "\r\n ============================================================= \r\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE')
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');        

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
        return $this->result;
    }


    function saveCreatedSubaccount($store_data, $subaccount_data)
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $moip_subaccount = array(
            'store_id'                      => $store_data['id'],
            'moip_id'                       => $subaccount_data['id'],
            'login'                         => $subaccount_data['login'],
            'access_token'                  => $subaccount_data['accessToken'],
            'service_charge_value'          => $store_data['service_charge_value'],
            'service_charge_freight_value'  => $store_data['service_charge_freight_value'],
            'channel_id'                    => $subaccount_data['channelId'],
            'type'                          => $subaccount_data['type'],
            'transparent'                   => ($subaccount_data['type']) ? 1 : 0,
            'person_name'                   => $subaccount_data['person']['name'],
            'person_last_name'              => $subaccount_data['person']['lastName'],
            'person_email'                  => $subaccount_data['email']['address'],
            'person_birthdate'              => $subaccount_data['person']['birthDate'],
            'person_cpf'                    => preg_replace('/\D/', '', $subaccount_data['person']['taxDocument']['number']),
            'person_street'                 => $subaccount_data['person']['address']['street'],
            'person_streetnumber'           => $subaccount_data['person']['address']['streetNumber'],
            'person_complement'             => @$subaccount_data['person']['address']['complement'],
            'person_district'               => $subaccount_data['person']['address']['district'],
            'person_zipcode'                => preg_replace('/\D/', '', $subaccount_data['person']['address']['zipcode']),
            'person_city'                   => $subaccount_data['person']['address']['city'],
            'person_state'                  => $subaccount_data['person']['address']['state'],
            'person_country'                => $subaccount_data['person']['address']['country'],
            'person_phone_code'             => $subaccount_data['person']['phone']['countryCode'],
            'person_phone_area'             => $subaccount_data['person']['phone']['areaCode'],
            'person_phone_number'           => $subaccount_data['person']['phone']['number'],
            'person_phone_type'             => $subaccount_data['person']['phone']['phoneType'],
            'company_name'                  => $subaccount_data['company']['name'],
            'company_businessName'          => $subaccount_data['company']['businessName'],
            'company_cnpj'                  => preg_replace('/\D/', '', $subaccount_data['company']['taxDocument']['number']),
            'company_street'                => $subaccount_data['company']['address']['street'],
            'company_streetnumber'          => $subaccount_data['company']['address']['streetNumber'],
            'company_complement'            => (isset($subaccount_data['company']['address']['complement'])) ? $subaccount_data['company']['address']['complement'] : null,
            'company_district'              => $subaccount_data['company']['address']['district'],
            'company_zipcode'               => $subaccount_data['company']['address']['zipcode'],
            'company_city'                  => $subaccount_data['company']['address']['city'],
            'company_state'                 => $subaccount_data['company']['address']['state'],
            'company_country'               => $subaccount_data['company']['address']['country'],
            'company_phone_code'            => $subaccount_data['company']['phone']['countryCode'],
            'company_phone_area'            => $subaccount_data['company']['phone']['areaCode'],
            'company_phone_number'          => $subaccount_data['company']['phone']['number'],
            'company_phone_type'            => $subaccount_data['company']['phone']['phoneType'],
            'date_created'                  => date('Y-m-d H:i:s', strtotime($subaccount_data['createdAt'])),
            'permission'                    => $subaccount_data['permission']['profile'],
            'link_self'                     => $subaccount_data['_links']['self']['href']
        );

        $moip_subaccount['active']      = ($subaccount_data['ccsStatus'] == 'CREATED') ? 1 : 0;

        $moip_subaccount_id             = $this->model_moip->saveSubaccount($moip_subaccount);

        if ($moip_subaccount_id > 0)
        {
            echo "### Conta Moip gerada com sucesso na tabela moip_subaccounts com o ID = ".$moip_subaccount_id." ### \r\n";
            return $moip_subaccount_id;
        }
        else
        {
            echo $error = "O cadastro Moip da Loja ".$store_data['id'] ." (".$subaccount_data['company']['businessName'].") apresentou erro!!!"."\n";
            $this->log_data(
                'batch',
                __FUNCTION__,
                $error,
                "E"
            );
            $this->model_payment_gateway_store_logs->insertLog(
                $store_data['id'],
                $this->gateway_id ,
                $error
            );
            return false;
        }
            
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que atualizam os sellers
     * ------------------------------------------------------
     */

    /**
     * @param null $id
     * @param null $params
     */
    public function runSyncStoresWithSubaccounts($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id, $params);
        $log_name = $this->logName;
        $is_update = true;

        $updated_stores = $this->model_moip->checkUpdatedStores($this->minutesToCheck);

        if ($updated_stores)
        {
            foreach ($updated_stores as $store)
            {
                $bank_data                  = $this->model_moip->getBankDataByStoreId($store['id']);

                if (empty($bank_data))
                    $is_update = false;

                $store_bank_number          = $this->model_moip->getBankCode($store['bank']);
                $subaccount_bank_number     = $bank_data['bank_number'];

                $store_agency               = $store['agency'];
                $subaccount_agency          = $bank_data['bank_agency'];

                $store_account              = $store['account'];
                $subaccount_account         = $bank_data['bank_account'];

                $min_account_length         = min(strlen($store_account), strlen($subaccount_account));

                $store_account              = substr($store_account, 0, $min_account_length);
                $subaccount_account         = substr($subaccount_account, 0, $min_account_length);

                if (
                    ($store_bank_number != $subaccount_bank_number) 
                    || 
                    ($store_agency != $subaccount_agency) 
                    || 
                    ($store_account != $subaccount_account) 
                )
                    $this->createUpdateBankAccount($bank_data['tbl_id'], $store, $is_update);
                
                //2 - check comissions and update yami

                $subaccount = $this->model_moip->getMoipStoreData(array('store_id' => $store['id']));
                
                $store_service_charge_value = $store['service_charge_value'];
                $store_service_charge_freight_value = $store['service_charge_freight_value'];

                $subaccount_service_charge_value = $subaccount['service_charge_value'];
                $subaccount_service_charge_freight_value = $subaccount['service_charge_freight_value'];

                if (
                    ($store_service_charge_value != $subaccount_service_charge_value)
                    ||
                    ($store_service_charge_freight_value != $subaccount_service_charge_freight_value)
                )
                {
                    $ymi_update_data = array
                    (
                        'ymi_acc'                       => $subaccount['ymi_acc'],
                        'service_charge_value'          => $store_service_charge_value,
                        'service_charge_freight_value'  => $store_service_charge_freight_value,
                    );

                    $new_comissions = $this->updateYamiAccount($ymi_update_data, $store['id']);
                }

            }
        }

        $this->endJob();

    }


    public function updateYamiAccount($ymi_update_data, $store_id)
    {
        $include_freight = ($ymi_update_data['service_charge_freight_value'] > 0) ? 1 : 0;

        $service_charge = str_replace(',', '.', $ymi_update_data['service_charge_value']);

        if (false === strpos('.', $service_charge))
            $service_charge .= '.00';

        $ymi_array = array
        (                        
            "include_freight" => $include_freight,
            "commission" => array
                (array(
                    "type" => "percent",
                    "value" => $service_charge
                    )
                ),                
        );

        $ymi_json = json_encode($ymi_array, JSON_UNESCAPED_UNICODE);

        $url = $this->ymi_url.'sellers/'.$ymi_update_data['ymi_acc'];

        $headers = array
        (
            'Content-Type: application/json',
            'x-ymi-token: '.$this->ymi_token
        );

        $update_ymi_account = $this->moipTransactions($url, 'PUT', $ymi_json, $headers);

        if (substr($this->responseCode, 0, 1) == 2)
        {
            $update_ymi_account_result = json_decode($update_ymi_account, true)['response'];

            $updated_include_freight        = $update_ymi_account_result['include_freight'];
            $updated_service_charge_value   = $update_ymi_account_result['commission'][0]['value'];

            if (
                ($updated_include_freight == $include_freight)
                &&
                ($updated_service_charge_value == $service_charge)
            )
            {
                $update_subaccount_array = array
                (
                    'service_charge_value'          => $ymi_update_data['service_charge_value'],
                    'service_charge_freight_value'  => $ymi_update_data['service_charge_freight_value']
                );
                 
                $this->model_moip->updateSubaccount(array('ymi_acc' => $ymi_update_data['ymi_acc']), $update_subaccount_array);
            }

             $success = "Comissões Editadas na Yami com sucesso";

            $this->model_payment_gateway_store_logs->insertLog(
                $store_id,
                $this->gateway_id ,
                $success,
                Model_payment_gateway_store_logs::STATUS_SUCCESS
            );
        }
        else
        {
            echo $error = "Não foi possivel atualizar conta YAMI. Json enviado: ".$ymi_json." -- json recebido: ".$update_ymi_account;
            $this->log_data(
                'batch',
                __FUNCTION__,
                $error,
                "E"
            );
            $this->model_payment_gateway_store_logs->insertLog(
                $store_id,
                $this->gateway_id ,
                $error
            );
            return false;
        }
    }


    //job para corrigir todos os numero_marketplace deturpados pela Yami/Moip
    public function fixGatewayPendenciesScheduleOrderNumbers()
    {
        $rows = $this->model_payment->listStatements();

        if (is_array($rows) && !empty($rows))
        {
            foreach ($rows as $row)
            {
                $statement_id =  $row['id'];
                $store_id = $row['store_id'];
                $key = explode('-', $row['order_ownid'])[0];
                $numero_marketplace = $this->model_moip->getCorrectOrderMktplace($key, $store_id);
                $this->model_payment->fixStatement($statement_id, $numero_marketplace);
            }
        }

        echo '================================================================
              numero_marketplace fixed on table gateway_pendencies_statements
              ================================================================';
    }



}