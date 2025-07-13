<?php

/**
 * ------------------------------------------------------
 * Criado por:  Augusto Braun - ConectaLá
 * ------------------------------------------------------
 * Entregue em: 2021-07-20
 * ------------------------------------------------------
 * Descrição:   Controlador responsável pelas integrações
 *              avulsas do Moip
 * ------------------------------------------------------
*/


defined('BASEPATH') OR exit('No direct script access allowed');

class MoipIntegrations extends Admin_Controller 
{
    private $log_name;
    private $api_url     = 'https://sandbox.moip.com.br/v2/';
    private $app_id      = 'APP-7RCDLDQLDPHY';
    private $app_account = 'MPA-8BHBKILIMTFB';
    private $app_token   = '0fb130a565684b399eb57f1ce84a976c_v2';
    private $result;
    private $responseCode;


	public function __construct()
	{
		parent::__construct();

        $this->load->model('model_moip');
        $this->load->model('model_orders');
	}


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que lidam com a conciliação
     * ------------------------------------------------------
     */

    

    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam os Saldos e Extratos
     * ------------------------------------------------------
     */

    public function checkBalance($subaccount = false)
    {
        $header = array('Accept: application/json;version=2.1');

        $url = $this->api_url.'balances';

        if (false !== $subaccount)
            $this->app_token = $this->model_moip->getSubaccountToken($subaccount);

        $balance = $this->moipTransactions($url, 'GET', null, __FUNCTION__, $header);

        $balance = json_decode($balance, true);

        //braun -> conferir se havera utilidade
        if (false !== $balance)
            return $balance;
           
        return false;
    }



    public function checkStatement($begin = false, $end = false, $subaccount = false)
    {
        if (empty($begin))
            $begin = date("Y-m-d", strtotime("-180 days"));
        
        if (empty($end))
            $end = date("Y-m-d");

        $difference = abs(strtotime($end) - strtotime($begin));
        $difference_days = ceil($difference / 60 / 60 / 24);

        if ($difference_days > 180)
            $begin = date("Y-m-d", strtotime("-180 days"));

        $url = $this->api_url.'statements?begin='.$begin.'&end='.$end;

        //braun -> em geral a subaccount eh necessaria pra um seller especifico
        if (false !== $subaccount)
            $this->app_token = $this->model_moip->getSubaccountToken($subaccount);

        $statement_json = $this->moipTransactions($url, 'GET', null, __FUNCTION__);

        $statement = json_decode($statement_json, true);

        //braun -> conferir se havera utilidade
        if (false !== $statement)
            return $statement;
           
        return false;
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam os Clientes no Moip e no sistema
     * ------------------------------------------------------
     */

    public function createMoipClient($client_id = null, $credit_card = null)
    {      
        if(!$client_data)
            return false;

        echo "=============================== \r\n";
        echo "Iniciando Criação de Cliente MOIP \r\n";        

        //braun -> remover esta linha, ela pega dados do cliente, vou pegar do banco para simular situacao real
        //primeiro confere se usuario ja existe com conta moip e retorna que eh o que o metodo faria
        // $client_data = $this->model_moip->getClientMoipIdbyId($client_id);

        if (!empty($client_data) && false !== strpos($client_data, 'US-'))
            return $client_data;

        $client_data = $this->model_moip->getClientData($client_id);
        
        $phone = $client_data['phone_1'];

        if (false !== strpos($client_data['phone_1'], '+')) 
            $phone = substr($client_data['phone_1'], 5);  //braun
        
        $client_array = array
        (
            'ownId'             => $client_data['id'],
            'fullname'          => $client_data['customer_name'],
            'email'             => $client_data['email'],
            'birthDate'         => '1990-01-01',    //braun
            'taxDocument'       => array(
                'type'          => (strlen(preg_replace('/\D/', '', $client_data['cpf_cnpj'])) > 11) ? 'CNPJ' : 'CPF',
                'number'        => preg_replace('/\D/', '', $client_data['cpf_cnpj'])
            ),
            'phone' => array(
                'countryCode'   => '55',
                'areaCode'      => substr($client_data['phone_1'], 3, 2), //braun
                'number'        => $phone
            ),
            'shippingAddress'   => array(
                'city'          => $client_data['addr_city'],
                'district'      => $client_data['addr_neigh'],
                'street'        => $client_data['customer_address'],
                'streetNumber'  => $client_data['addr_num'],
                'zipCode'       => preg_replace('/\D/', '', $client_data['zipcode']),
                'state'         => $client_data['addr_uf'],
                'country'       => 'BRA'
            )            
        );

        $url = $this->api_url.'customers';

        $client_json = json_encode($client_array, JSON_UNESCAPED_UNICODE);

        $client_result = $this->moipTransactions($url, 'POST', $client_json, __FUNCTION__);

        if (substr($this->responseCode, 0, 1) == 2)
        {
            $new_moip_id = $this->saveCreatedClient();

            echo " Novo Cliente ID = ".$new_moip_id." criado com sucesso \r\n ";

            //braun -> caso positivo e caso possua dados de cartao, grava tb o cartao com os dados que chegaram do moip
            // $credit_card = array(
            //         'expiration_month' => 5,
            //         'expiration_year' => 22,
            //         'number' => '4012001037141112',
            //         'fullname' => 'João Silva',
            //         'birthdate' => '1990-10-22',
            //         'cpf_cnpj' => '22288866644',
            //         'phone_countrycode' => '55',
            //         'phone_areacode' => '11',
            //         'phone_number' => '55552266'
            // );

            if ($new_moip_id && is_array($credit_card) && !empty($credit_card))
                $this->saveCreditCard($credit_card, $new_moip_id);

            if ($new_moip_id)
                return $new_moip_id;
        }

        return false;
    }


    public function saveCreatedClient($client_data = null)
    {
        if (empty($this->result))
            return false;

        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $client_array = json_decode($this->result, true);

        $client = array
        (
            'client_id'             => $client_array['ownId'],
            'moip_id'               => $client_array['id'],
            'fullname'              => $client_array['fullname'],
            'email'                 => $client_array['email'],
            'birthdate'             => $client_array['birthDate'],
            'cpf_cnpj'              => $client_array['taxDocument']['number'],
            'phone_countrycode'     => $client_array['phone']['countryCode'],
            'phone_areacode'        => $client_array['phone']['areaCode'],
            'phone_number'          => $client_array['phone']['number'],
            'phone_number'          => $client_array['phone']['number'],
            'street'                => $client_array['shippingAddress']['street'],
            'street_number'         => $client_array['shippingAddress']['streetNumber'],
            'district'              => $client_array['shippingAddress']['district'],
            'city'                  => $client_array['shippingAddress']['city'],
            'zip_code'              => $client_array['shippingAddress']['zipCode'],
            'country'               => $client_array['shippingAddress']['country'],
            'created'               => $client_array['createdAt'],
            'link_self'             => $client_array['_links']['self']['href'],
            'link_self'             => $client_array['_links']['hostedAccount']['redirectHref']
        );

        $client_id                  = $this->model_moip->saveClient($client);

        if ($client_id > 0)
        {
            echo "### Conta de Cliente Moip gerada com sucesso na tabela moip_clients com o ID = ".$client_id." ### \r\n";
            return $client_array['id'];
        }
        else
        {
            $msg = "!!! O cadastro Moip da Cliente ".$client_array['fullname'] ." (".$client_array['taxDocument']['number'].") apresentou erro!!!"."\n";
            $this->log_data('batch', $log_name, $msg, "E");
            return false;
        }
        
    }


    public function getClientMoipData($client_moip_id = false)
    {
        if (!$client_moip_id)
            return false;

        $url = $this->api_url.'customers/'.trim($client_moip_id);

        $client_return = $this->moipTransactions($url, 'GET', null, __FUNCTION__);

        return json_decode($client_return, true);
    }


    public function getClientsLocal()
    {        
        $clients = $this->model_moip->getClientsLocal();
        
        return $clients;
    }


    public function getClientsMoip()
    {
        $url = $this->api_url.'customers';

        $clients_moip = $this->moipTransactions($url, 'GET', null, __FUNCTION__);

        $clients_moip_array = json_decode($clients_moip, true);

        return (is_array($clients_moip_array)) ? $clients_moip_array['customers'] : false;
    }


    
    
    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam PAGAMENTOS
     * ------------------------------------------------------
     */

    public function createPayment($order_moip_id = false, $payment_data = null)
    {
        if(!$order_moip_id || empty($payment_data))
            return false;

        $order_id        = $this->model_moip->getOrderIdbyMoipId($order_moip_id);
        $client_moip_id  = $this->model_moip->getClientMoipIdByOrderId($order_id);
        $card_moip_id    = $boleto_expiration = $boleto_line = $boleto_link = $boleto_screen = '';

        //revisar este metodo com base nos dados de input que definem a forma de pagto
        if ($payment_data['payment_method'] == 'CREDIT_CARD')
        {            
            $credit_card_moip = $this->saveCreditCard($payment_data['credit_card'], $client_moip_id);

            $credit_card_moip_array = json_decode($credit_card_moip, true);

            if(empty($credit_card_moip_array) || !isset($credit_card_moip_array['creditCard']['id']))
            {
                echo $msg = "Não foi possível registrar o o cartao como pagamento no PEDIDO ".$order_id." com cartao de credito com os seguintes dados ".serialize($payment_data['credit_card']);
                echo "\r\n";
                return false;
            }            

            $funding_instrument = array
            (
                'method' => 'CREDIT_CARD',
                'creditCard' => array(
                    'id' => $credit_card_moip_array['creditCard']['id']
                )
            );    
            
            $card_moip_id = $credit_card_moip_array['creditCard']['id'];
        }
        else
        {
            // $boleto_expiration = '2021-07-20';

            // $funding_instrument = array
            // (
            //     'method' => 'BOLETO',
            //     'boleto' => array(
            //         'expirationDate'    => $boleto_expiration,
            //         'instructionLines'  => array(
            //             'first'         => '',
            //             'second'        => '',
            //             'third'         => ''
            //         )
            //     )
            // );

        }

        $payment_array = array
        (
            'installmentCount'      => $payment_data['parcels'],    //braun <- conferir se receberemos parcelado
            'statementDescriptor'   => $payment_data['store_name'],
            'fundingInstrument'     => $funding_instrument
        );

        $payment_json = json_encode($payment_array, JSON_UNESCAPED_UNICODE);

        $url = $this->api_url.'orders/'.$order_moip_id.'/payments';

        $payment_created_json = $this->moipTransactions($url, 'POST', $payment_json, __FUNCTION__);

        $payment_created = json_decode($payment_created_json, true);
       
        if (substr($this->responseCode, 0, 1) == 2)
        {
           //grava os dados do pagamento
           $payment_created_array = array
           (
                'order_id'              => $order_id,
                'order_moip_id'         => $order_moip_id,
                'payment_moip_id'       => $payment_created['id'],
                'status'                => $payment_created['status'],
                'total'                 => $payment_created['amount']['total'],
                'gross'                 => $payment_created['amount']['gross'],
                'fees'                  => $payment_created['amount']['fees'],
                'refunds'               => $payment_created['amount']['refunds'],
                'liquid'                => $payment_created['amount']['liquid'],
                'currency'              => $payment_created['amount']['currency'],
                'installment_count'     => $payment_created['installmentCount'],
                'statement_descriptor'  => $payment_created['statementDescriptor'],
                'card_moip_id'          => $card_moip_id,
                'boleto_expiration'     => $payment_created['fundingInstrument']['boleto']['expirationDate'],
                'boleto_line'           => $payment_created['fundingInstrument']['boleto']['lineCode'],
                'boleto_link'           => $payment_created['_links']['payBoleto']['printHref'],
                'boleto_screen'         => $payment_created['_links']['payBoleto']['redirectHref'],
                'authorization_number'  => $payment_created['acquirerDetails']['authorizationNumber'],
                'link_self'             => $payment_created['_links']['self']['href'],
                'link_order'            => $payment_created['_links']['order']['href'],
                'created_at'            => date('Y-m-d H:i:s', strtotime($payment_created['createdAt'])),
                'updated_at'            => date('Y-m-d H:i:s', strtotime($payment_created['updatedAt']))
           );

           $payment_moip_tbl_id = $this->model_moip->saveMoipPayment($payment_created_array);

           if ($payment_moip_tbl_id > 0 && is_array($payment_created['receivers']) && !empty($payment_created['receivers']))
           {                
                echo "Dados do PAGAMENTO ".$payment_moip_tbl_id." gravados no banco de dados \r\n";

                $receiver_data = $event_data = [];

                $function_id = $this->model_moip->getFunctionId('payments');

                foreach ($payment_created['receivers'] as $receiver)
                {
                    if($receiver['type'] != 'PRIMARY')
                    {
                        $store_id = $this->model_moip->getSellerByMoipId($receiver['moipAccount']['id']);
                        
                        if (empty($store_id))
                            continue;

                        $receiver_data = array
                        (
                            'tbl_function'          => $function_id,
                            'function_moip_tbl_id'  => $payment_moip_tbl_id,
                            'store_id'              => $store_id,
                            'store_moip_id'         => $receiver['moipAccount']['id'],
                            'type'                  => $receiver['type'],
                            'total'                 => $receiver['amount']['total'],
                            'currency'              => $receiver['amount']['currency'],
                            'fees'                  => $receiver['amount']['fees'],
                            'refunds'               => $receiver['amount']['refunds'],
                            'feePayor'              => $receiver['feePayor'],
                        );

                        if ($this->model_moip->saveFunctionReceiver($receiver_data))
                            echo "Receiver store_id = ".$store_id." gravados registrado no banco de Receivers \r\n";
                    }
                }

                if (!empty($payment_created['events']))
                {
                    foreach ($payment_created['events'] as $event)
                    {
                        $event_data = array
                        (
                            'tbl_function'          => $function_id, 
                            'function_moip_tbl_id'  => $payment_moip_tbl_id,
                            'type'                  => $event['type'],
                            'created_at'            => date('Y-m-d H:i:s', strtotime($event['createdAt'])),
                            'description'           => $event['description']
                        );
    
                        if ($this->model_moip->saveFunctionEvent($event_data))
                            echo "Pagamento ".$event['type']." payment_moip_tbl_id = ".$payment_moip_tbl_id." gravados registrado no banco de Events \r\n";
                    }
                }

                if (!empty($payment_created['fees']))
                {
                    foreach ($payment_created['fees'] as $fee)
                    {
                        $fee_data = array
                        (
                            'tbl_function'          => $function_id, 
                            'function_moip_tbl_id'  => $payment_moip_tbl_id,
                            'type'                  => $fee['type'],
                            'amount'                => $fee['amount']
                        );
    
                        if ($this->model_moip->saveFunctionFee($fee_data))
                            echo "Taxa ".$fee['type']." payment_moip_tbl_id = ".$payment_moip_tbl_id." gravados registrado no banco de Fees \r\n";
                    }
                } 

                return true;              
            }
        }
        else
        {
            echo $msg = "Erro ao criar Pagamento MOIP, retornou codigo ".$this->responseCode." com a mensagem ".$this->result." \r\n";
            return false;
        }
    }


    public function paymentStatus($payment_moip_id = false, $payment_id = false)
    {
        if (!$payment_id && !$payment_moip_id)
            return false;

        if (!empty($payment_id))
            $payment_moip_id = $this->model_moip->getPaymentMoipIdById($payment_id);

        if(empty($payment_moip_id))
            return false;

        $url = $this->api_url.'payments/'.$payment_moip_id;

        $payment_return = $this->moipTransactions($url, 'GET', null, __FUNCTION__);

        return json_decode($payment_return, true);
    }


    public function updatePaymentStatus($payment_moip_id = false, $payment_id = false)
    {
        if (!$payment_id && !$payment_moip_id)
            return false;

        if (!empty($payment_id))
            $payment_moip_id = $this->model_moip->getPaymentMoipIdById($payment_id);

        if(empty($payment_moip_id))
            return false;

        $url = $this->api_url.'payments/'.$payment_moip_id;

        $payment_return = $this->moipTransactions($url, 'GET', null, __FUNCTION__);

        if (substr($this->responseCode, 0, 1) == 2)
        {
            $payment_return_array = json_decode($payment_return, true);

            $update_array = array
            (
                'status' => $payment_return_array['status'],
                'fees' => $payment_return_array['amount']['fees'],
                'refunds' => $payment_return_array['amount']['refunds'],
                'liquid' => $payment_return_array['amount']['liquid']
            );

            $payment_updated = $this->moip_model->updateMoipPayment($update_array, $payment_moip_id);

            $function_id = $this->model_moip->getFunctionId('payments');
            $function_moip_tbl_id = $this->moip_model->getPaymentIdByMoipId($payment_moip_id);

            if (!empty($payment_return_array['events']))
            {
                foreach ($payment_return_array['events'] as $event)
                {
                    $event_data = array
                    (
                        'tbl_function'          => $function_id, 
                        'function_moip_tbl_id'  => $function_moip_tbl_id,
                        'type'                  => $event['type'],
                        'created_at'            => date('Y-m-d H:i:s', strtotime($event['createdAt'])),
                        'description'           => $event['description']
                    );

                    if ($this->model_moip->saveFunctionEvent($event_data))
                        echo "Pagamento ".$event['type']." payment_moip_tbl_id = ".$payment_moip_id." gravados registrado no banco de Events \r\n";
                }
            }


            if (!empty($payment_return_array['fees']))
            {
                foreach ($payment_return_array['fees'] as $fee)
                {
                    $fee_data = array
                    (
                        'tbl_function'          => $function_id, 
                        'function_moip_tbl_id'  => $function_moip_tbl_id, 
                        'payment_moip_id'       => $payment_moip_id,
                        'type'                  => $fee['type'],
                        'amount'                => $fee['amount']
                    );

                    if ($this->model_moip->saveFunctionFee($fee_data))
                        echo "Taxa ".$fee['type']." payment_moip_tbl_id = ".$payment_moip_id." gravados registrado no banco de Fees \r\n";
                }
            } 

            $payment_updated;

        }
        
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam os cartões de crédito
     * ------------------------------------------------------
     */

    public function saveCreditCard($credit_card_data = null, $client_moip_id = null)
    {
        if (empty($credit_card_data) || empty($client_moip_id))
            return false;
        
            // $credit_card_data = array(
            //         'expiration_month' => 5,
            //         'expiration_year' => 22,
            //         'number' => '4012001037141112',
            //         'fullname' => 'João Silva',
            //         'birthdate' => '1990-10-22',
            //         'cpf_cnpj' => '18206885005',
            //         'phone_countrycode' => '55',
            //         'phone_areacode' => '11',
            //         'phone_number' => '34343434'
            // );
            // $client_moip_id = 'CUS-14BLY8TUQE1L';

        if (is_array($credit_card_data))
            $credit_card = $credit_card_data;
        else
            $credit_card = json_decode($credit_card_data, true);

        $card = array
        (
            'method'                    => 'CREDIT_CARD',
            'creditCard'            => array(
                'expirationMonth'       => str_pad($credit_card['expiration_month'], 2, 0, STR_PAD_LEFT),
                'expirationYear'        => str_pad($credit_card['expiration_year'], 2, 0, STR_PAD_LEFT),
                'number'                => preg_replace('/\D/', '', $credit_card['number']),
                'holder'            => array(
                    'fullname'          => $credit_card['fullname'],
                    'birthdate'         => $credit_card['birthdate'],
                    'taxDocument'   => array(
                        'type'          => (strlen(preg_replace('/\D/', '', $credit_card['cpf_cnpj'])) > 11) ? 'CNPJ' : 'CPF',
                        'number'        => preg_replace('/\D/', '', $credit_card['cpf_cnpj'])
                    ),
                    'phone'         => array(
                        'countryCode'   => $credit_card['phone_countrycode'],
                        'areaCode'      => $credit_card['phone_areacode'],
                        'number'        => $credit_card['phone_number']
                    )
                )
            )
        );

        $card_json = json_encode($card, JSON_UNESCAPED_UNICODE);

        $url = $this->api_url.'customers/'.$client_moip_id.'/fundinginstruments';

        $card_created = $this->moipTransactions($url, 'POST', $card_json, __FUNCTION__);

        if (substr($this->responseCode, 0, 1) == 2)
        {
            $new_card = $this->saveCreatedCard($card_created, $client_moip_id);
            
            if ($new_card)
            {
                echo "Cartão de crédito registrado com sucesso para o cliente Moip ID ".$client_moip_id." \r\n";
                return $card_created;
            }
                
            return false;
        }
        
    }


    public function saveCreatedCard($card_json = null, $client_moip_id = false)
    {
        if (empty($card_json) || !$client_moip_id)
            return false;
        
        $card_array = json_decode($card_json, true);

        $client_id = $this->model_moip->getClientIdbyMoipId($client_moip_id);

        if (!$client_id)
            $client_id = null;

        $card_data = array
        (
            'client_id'         => $client_id,
            'client_moip_id'    => $client_moip_id,
            'card_moip_id'      => $card_array['creditCard']['id'],
            'brand'             => $card_array['creditCard']['brand'],
            'first6'            => $card_array['creditCard']['first6'],
            'last4'             => $card_array['creditCard']['last4'],
            'store'             => ($card_array['creditCard']['store']) ? 1 : 0
        );

        if (is_array($card_data) && !empty($card_data))
            return $this->model_moip->saveCreditCard($card_data);
    }


    public function deleteMoipCreditCard($credit_card_id = false)
    {
        if (!$credit_card_id)
            return false;

        $url = $this->api_url.'fundinginstruments/'.trim($credit_card_id);

        $this->moipTransactions($url, 'DELETE', null, __FUNCTION__);

        if (substr($this->responseCode, 0, 1) == 2)
        {
            echo "Cartao de Credito ID ".$credit_card_id." removido da conta do usuario \r\n";
            return $this->model_moip->removeCreditCard($credit_card_id);            
        }
        
        return false;
    }



    /**
     * ------------------------------------------------------
     * Conjunto de métodos para MULTI PEDIDOS E PAGAMENTOS  
     * ------------------------------------------------------
     */

    public function createMultiOrder($multi_order_data = null)
    {
        if(empty($multi_order_data))
            return false;      

        if (is_array($multi_order_data) && !empty($multi_order_data))
        {
            echo "=============================== \r\n";
            echo "Iniciando Criação de MultiPedido MOIP \r\n";

            $multi_orders = [];

            foreach ($multi_order_data as $created)
            {
                $multi_orders[] = array
                (
                    'order' => $created['order'], 
                    'items' => $created['items']
                );
            }           

            foreach ($multi_orders as $key => $order)
            {
                //corrigindo os valores da tabela que sao varchar ao inves de int
                $order['order']['total_ship'] = str_replace(',', '.', $order['order']['total_ship']);
                $order['order']['discount'] = str_replace(',', '.', $order['order']['discount']);
                $order['order']['gross_amount'] = str_replace(',', '.', $order['order']['gross_amount']);

                if (false === strpos($order['order']['total_ship'], '.'))
                    $order['order']['total_ship'] .= '00';
                else
                    $order['order']['total_ship'] = str_replace('.', '', $order['order']['total_ship']);

                if (false === strpos($order['order']['discount'], '.'))
                    $order['order']['discount'] .= "00";
                else
                    $order['order']['discount'] = str_replace('.', '', $order['order']['discount']);

                if (false === strpos($order['order']['gross_amount'], '.'))
                    $order['order']['gross_amount'] .= "00";
                else
                    $order['order']['gross_amount'] = str_replace('.', '', $order['order']['gross_amount']);

                $multi_order_data['orders'][$key] = array
                (
                    'ownId' => $order['order']['order_id'],
                    'amount' => array(
                            'currency' => 'BRL',
                            'subtotals' => array(
                                'shipping' => intVal($this->onlyNumbers($order['order']['total_ship'])),
                                'discount' => intVal($this->onlyNumbers($order['order']['discount']))
                            )
                        )
                );

                if (!empty($order['items']) && is_array($order['items']))
                {
                    foreach ($order['items'] as $item)
                    {
                        $price = str_replace(',', '.', $item['price']);

                        if (false === strpos($price, '.'))
                            $price .= "00";
        
                        $multi_order_data['orders'][$key]['items'][] = array
                        (
                            'product' => $item['name'],
                            'quantity' => intVal($this->onlyNumbers($item['qty'])),
                            'detail' => $item['description'],
                            'price' => intVal($this->onlyNumbers($price))
                        );
                    }
                }

                $client_moip_id = $this->model_moip->getClientMoipIdbyId($order['order']['client_id']);

                if (false === strpos($client_moip_id,'US-'))
                    $client_moip_id = $this->createMoipClient($order['order']['client_id']);
                
                if (empty($client_moip_id))
                    return false;

                $multi_order_data['orders'][$key]['customer'] = array
                (
                    'id' => $client_moip_id
                );

                $store_data = $this->model_moip->getStoreMoipData($order['order']['store_id']);

                $receivers = array(
                    array
                    (
                        'moipAccount' => array(
                            'id' => 'MPA-8BHBKILIMTFB'
                        ),
                        'type' => 'PRIMARY',
                        ),
                    array
                    (
                        'moipAccount' => array(
                            'id' => $store_data['moip_id']
                        ),
                        'type' => 'SECONDARY',
                        'feePayor' => false,
                        'amount' =>array(
                            'fixed' => $order['order']['gross_amount']
                        )
                    )
                );

                $multi_order_data['orders'][$key]['receivers'] = $receivers;
            }

            $multi_order_id = $multi_order_data['ownId'] = $this->model_moip->createPreMultiOrder();
        }
        else
        {
            echo "\r\n !!! Erro !!! Solicitado cadastro de Pedido mas não foi enviado Array de dados \r\n";
            return false;
        }

        $multi_order_json = json_encode($multi_order_data, JSON_UNESCAPED_UNICODE);

        $url = $this->api_url.'multiorders';

        $multi_order_created = $this->moipTransactions($url, 'POST', $multi_order_json, __FUNCTION__);

        $multi_order_return = json_decode($multi_order_created, true);

        if ($multi_order_return['status'] == 'CREATED')
        {
            echo "Multi Pedido Criado \r\n";

            $multi_order_data = array
            (
                'multi_order_moip_id'   => $multi_order_return['id'],
                'status'                => $multi_order_return['status'],
                'created_at'            => date('Y-m-d H:i:s', strtotime($multi_order_return['createdAt'])),
                'updated_at'            => ($multi_order_return['updatedAt'] == "") ? date('Y-m-d H:i:s', strtotime($multi_order_return['createdAt'])) : date('Y-m-d H:i:s', strtotime($multi_order_return['updatedAt'])),
                'amount'                => $multi_order_return['amount']['total'],
                'link_self'             => $multi_order_return['_links']['self']['href'],
                'link_creditcard'       => $multi_order_return['_links']['checkout']['payCreditCard']['redirectHref'],
                'link_boleto'           => $multi_order_return['_links']['checkout']['payBoleto']['redirectHref']
            );

            $multi_order_save = $this->model_moip->saveMoipMultiOrder($multi_order_id, $multi_order_data);

            if ($multi_order_save)
            {                
                echo "Dados do multi pedido tbl_id = ".$multi_order_id." gravados no banco de dados \r\n";

                $processed_orders = $this->createMoipOrders($multi_order_id, $multi_order_return['orders']);              
            }
        }
        else
        {
            echo "\r\n !!! Erro !!! O pedido nao foi cadastrado com sucesso na integração \r\n";
        }

        return false;
    }


    public function createMultiPayment($multi_order_id = false, $multipayment_array = null)
    {
        // if(!$multi_order_id)
            // return false;


        
        
        //braun -> parece que precisa ser criado um cartao na hora pra andar
        $payment_data['credit_card'] = array(
            'expiration_month' => 6,
            'expiration_year' => 22,
            'number' => '6370950000000005',
            'fullname' => 'Joao Teste Segunda',
            'birthdate' => '1976-10-22',
            'cpf_cnpj' => '56921780015',
            'phone_countrycode' => '55',
            'phone_areacode' => '48',
            'phone_number' => '991667882'
        );
        
        //braun -> cancelei a criaçao do cartao por enquanto, pq o moip nao aceita o ID
        $credit_card_moip = $this->saveCreditCard($payment_data['credit_card'], $multipayment_array['client_moip_id']);
        $credit_card_array = json_decode($credit_card_moip, true);
        $credit_card_id = $credit_card_array['creditCard']['id'];

        //braun -> revisar este metodo com base nos dados de input que definem a forma de pagto
        if ($multipayment_array['payment_method'] == 'CREDIT_CARD')
        {          
            $multipayment_data = array
            (
                'installmentCount' => $multipayment_array['installmentCount'],
                'statementDescriptor' => trim(substr($multipayment_array['statementDescriptor'], 0, 13)),
                'fundingInstrument' => array(
                    'method' => 'CREDIT_CARD',
                    'creditCard' => array(
                        'id' => $credit_card_id
                    )
                )
            );
        }


        $multipayment_json = json_encode($multipayment_data, JSON_UNESCAPED_UNICODE);

        $url = $this->api_url.'multiorders/'.$multi_order_id.'/multipayments';

        $multipayment_created_json = $this->moipTransactions($url, 'POST', $multipayment_json, __FUNCTION__);

        $multipayment_created = json_decode($multipayment_created_json, true);
       
        if (substr($this->responseCode, 0, 1) == 2)
        {
            $multipayment_return = array
            (
                'multi_order_id'        => $this->model_moip->getMultiOrderIdByMoipId($multi_order_id),
                'multi_order_moip_id'   => $multi_order_id,
                'multi_payment_moip_id' => $multipayment_created['id'],
                'status'                => $multipayment_created['status'],
                'total'                 => $multipayment_created['amount']['total'],
                'currency'              => $multipayment_created['amount']['currency'],
                'installment_count'     => $multipayment_created['installmentCount'],
                'link_self'             => $multipayment_created['_links']['self']['href'],
                'link_multiorder'       => $multipayment_created['_links']['multiorder']['href']
            );

            $multipayment_return_id = $this->model_moip->saveMoipMultiPayment($multipayment_return);

            if ($multipayment_return_id > 0)
            {
                $multipayment_status = $multipayment_created['status'];
            }
        }
        else
        {
            echo $msg = "Erro ao criar MultiPagamento MOIP, retornou codigo ".$this->responseCode." com a mensagem ".$this->result." \r\n";
            return false;
        }
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam os Pedidos
     * ------------------------------------------------------
     */
    public function createMoipOrders($multi_order_id = false, $orders_data = null)
    {
        if (!$multi_order_id || !is_array($orders_data) || empty($orders_data))
            return false;        

        if (is_array($orders_data) && !empty($orders_data))
        {
            $client_id      = $orders_data[count($orders_data)-1]['customer']['ownId'];
            $client_moip_id = $orders_data[count($orders_data)-1]['customer']['id'];

            foreach ($orders_data as $key => $order)
            {
                echo "=============================== \r\n";
                echo "Iniciando Criação de Pedido Individual MOIP order_id = ".$order['ownId']." \r\n";
    
                $order_data = array
                (
                    'multiorder_id'         => $multi_order_id,
                    'order_id'              => $order['ownId'],                    
                    'order_moip_id'         => $order['id'],
                    'status'                => $order['status'],
                    'created_at'            => date('Y-m-d H:i:s', strtotime($order['createdAt'])),
                    'updated_at'            => ($order['updatedAt'] == "") ? date('Y-m-d H:i:s', strtotime($order['createdAt'])) : date('Y-m-d H:i:s', strtotime($order['updatedAt'])),
                    // 'client_id'             => $order['customer']['ownId'],
                    'client_id'             => $client_id,
                    // 'client_moip_id'        => $order['customer']['id'],
                    'client_moip_id'        => $client_moip_id,
                    'paid'                  => $order['amount']['paid'],
                    'total'                 => $order['amount']['total'],
                    'fees'                  => $order['amount']['fees'],
                    'refunds'               => $order['amount']['refunds'],
                    'liquid'                => $order['amount']['liquid'],
                    'other_receivers'       => $order['amount']['otherReceivers'],
                    'currency'              => $order['amount']['currency'],
                    'shipping'              => $order['amount']['subtotals']['shipping'],
                    'addition'              => $order['amount']['subtotals']['addition'],
                    'discount'              => $order['amount']['subtotals']['discount'],
                    'items'                 => $order['amount']['subtotals']['items'],
                    'channel'               => $order['channel']['externalId'],
                    'link_self'             => $order['_links']['self']['href']
                );

                $create_order_id = $this->model_moip->saveMoipOrder($order_data);

                if ($create_order_id > 0 && !empty($order['events']))
                {
                    foreach ($order['events'] as $event)
                    {
                        $event_data = array
                        (
                            'tbl_function'          => $this->model_moip->getFunctionId('orders'),
                            'function_moip_tbl_id'  => $create_order_id, //id autoincrement do banco de orders
                            'type'                  => $event['type'],
                            'created_at'            => date('Y-m-d H:i:s', strtotime($event['createdAt'])),
                            'description'           => $event['description']
                        );

                        $this->model_moip->saveFunctionEvent($event_data);
                    }
                }

                if ($create_order_id > 0 && !empty($order['receivers']))
                {
                    foreach ($order['receivers'] as $receiver)
                    {
                        $receiver_data = array
                        (
                            'tbl_function'          => $this->model_moip->getFunctionId('orders'),
                            'function_moip_tbl_id'  => $create_order_id, //id autoincrement do banco de orders
                            'subaccount_moip_id'    => $receiver['moipAccount']['id'],
                            'type'                  => $receiver['type'],
                            'total'                 => $receiver['amount']['total'],
                            'currency'              => $receiver['amount']['currency'],
                            'fees'                  => $receiver['amount']['fees'],
                            'refunds'               => $receiver['amount']['refunds'],
                            'feePayor'              => $receiver['feePayor']
                        );

                        $this->model_moip->saveFunctionReceiver($receiver_data);
                    }
                }
            }
        }
        else
        {
            echo "\r\n !!! Erro !!! Solicitado cadastro de Pedido mas não foi enviado Array de dados \r\n";
            return false;
        }

        return true;
    }


    public function getOrderData($order_id = false)
    {
        if(!$order_id)
            return false;

        $url = $this->api_url.'orders/'.trim($order_id);

        $order_return = $this->moipTransactions($url, 'GET', null, __FUNCTION__);

        return json_decode($order_return, true);
    }


    public function listOrdersMoip()
    {
        $url = $this->api_url.'orders';

        $orders_return = $this->moipTransactions($url, 'GET', null, __FUNCTION__);

        return json_decode($orders_return, true);
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos genéricos
     * ------------------------------------------------------
     */

    protected function moipTransactions($url, $method = 'GET', $data = null, $function = null, $optional_headers = null)
    {
        $headers = array
        (
	        'Content-Type: application/json',
	        'Authorization: Bearer '.$this->app_token
	    );

        if(!empty($optional_headers) && is_array($optional_headers))
        {
            foreach($optional_headers as $header)
            {
                $headers[] = $header;
            }
        }

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


}