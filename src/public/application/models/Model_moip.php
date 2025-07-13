<?php 

/**
 * ------------------------------------------------------
 * Criado por:  Augusto Braun - ConectaLá
 * ------------------------------------------------------
 * Entregue em: 2021-06-25
 * ------------------------------------------------------
 * Descrição:   Model responsável pela criação de contas
 *              Moip e gerenciamento das informações no 
 *              sistema
 * ------------------------------------------------------
*/


class Model_moip extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	

     /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam da conciliação
     * ------------------------------------------------------
     */

    public function getTransactions($gateway = false)
    {
        $sql = "select 
                c.id as conciliacao_id,
                c.status_repasse,
                p.data_pagamento
                from conciliacao c inner join param_mkt_ciclo p 
                on c.param_mkt_ciclo_id = p.id
                where 
                c.ativo = 1
                and
                p.ativo = 1
                and
                c.status_repasse in (21, 25)
                ";

        $query          = $this->db->query($sql);
        $transactions   = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return $transactions;
    }


    //@todo coloquei em model_transfer
    public function getTransfers($conciliacao_id = false)
    {
        if (!$conciliacao_id)
            return false;

        $sql = "select * from repasse where conciliacao_id = ? and status_repasse in (21, 26)";

        $query          = $this->db->query($sql, array($conciliacao_id));
        $transfers      = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return $transfers;
        
    }


    //@todo utilizar a model repasse
    public function updateTransferStatus($status = false, $id = false)
    {
        $status_number = 26;

        if ($status === true)
            $status_number = 23;

        return $this->db->update('repasse', array('status_repasse' => $status_number), array('id' => $id));
    }


    public function getGatewayId($code = false)
    {
        if (!$code)
            return false;

        $sql = "select id from payment_gateways where code = ?";

        $query          = $this->db->query($sql, array($code));
        $gateway_id     = ($query && $query->num_rows() > 0) ? $query->row_array()['id'] : false;

        return $gateway_id;
    }


    public function updateYmiAcc($ymi_id = false, $moip_id = false)
    {
        if (!$ymi_id || !$moip_id)
            return false;

        $ymi_update = $this->db->update('moip_subaccounts', array('ymi_acc' => $ymi_id), array('moip_id' => $moip_id));
        
        return $ymi_update;
    }


    public function updateSubaccount($key_valule = null, $data_array = null)
    {
        if (empty($key_valule) || !is_array($key_valule) || empty($data_array) || !is_array($data_array))
            return false;
        
        $array_keys = array_keys($key_valule);
        
        $sql          = "select * from moip_subaccounts where ".$array_keys[0]." = '".$key_valule[$array_keys[0]]."'";

        $ymi_update = $this->db->update('moip_subaccounts', $data_array, array($array_keys[0] => $key_valule[$array_keys[0]]));
        
        return $ymi_update;
    }


     /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam de contas bancarias
     * ------------------------------------------------------
     */

    public function saveBankAccount($subaccount_tbl_id, $bank_account_data, $is_update = false)
    {
        if (!$subaccount_tbl_id || !is_array($bank_account_data) || empty($bank_account_data))
            return false;

        if ($is_update)
        {
            $bank_account_id = $this->db->update('moip_bank_accounts', $bank_account_data, array('tbl_id' => $bank_account_data['tbl_id']));
            return $bank_account_data['tbl_id'];
        }           
        else
        {
            $bank_account_id = $this->db->replace('moip_bank_accounts', $bank_account_data, array('subaccount_tbl_id' => $subaccount_tbl_id));
            return ($bank_account_id == true) ? $this->db->insert_id() : false;
        }
    }
        

    public function getBankId($subaccount_moip_id = null)
    {
        if (empty($subaccount_moip_id))
            return false;

        $sql = "select bank_moip_id from moip_bank_accounts where subaccount_moip_id = ?";
        $query          = $this->db->query($sql, array($subaccount_moip_id));
        $bank_moip_id   = ($query) ? $query->row_array()['bank_moip_id'] : false;

        return $bank_moip_id;
    }


    public function getBankCode($bank = false)
    {
        if (!$bank)
            return false;

        $sql = "select number from banks where name = ?";
        $query          = $this->db->query($sql, array(trim($bank)));
        $bank_code      = ($query) ? $query->row_array()['number'] : false;

        return $bank_code;
    }


    public function getBankDataByStoreId($store_id = null)
    {
        if (!$store_id)
            return false;

        $sql = "select *, b.tbl_id as bank_tbl_id from moip_bank_accounts b left join moip_subaccounts s on b.subaccount_moip_id = s.moip_id 
                where s.store_id = ? order by b.tbl_id desc";        

        $query          = $this->db->query($sql, array($store_id));
        $bank_data      = ($query) ? $query->row_array() : false;

        return $bank_data;
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam das TRANSFERENCIAS
     * ------------------------------------------------------
     */

    public function createPreTransfer($sender = null, $receiver = null, $bank_moip_id = null, $amount = null, $transfer_type = null)
    {
        if (empty($sender) || empty($receiver) || empty($amount) || empty($transfer_type))
            return false;

        $transfer_array = array
        (
            'sender_moip_id' => $sender,
            'receiver_moip_id' => $receiver,
            'bank_moip_id' => $bank_moip_id,
            'amount' => $amount,
            'transfer_type' => $transfer_type,
            'status' => 'CREATING'
        );

        $insert = $this->db->insert('moip_transfers', $transfer_array);

        return ($insert == true) ? $this->db->insert_id() : false;
    }


    public function saveTransfer($transfer_data = null)
    {
        if (empty($transfer_data) || !is_array($transfer_data))
            return false;

        $transfer_saved = $this->db->replace('moip_transfers', $transfer_data);
    
        return ($transfer_saved == true) ? true : false;
    }


    public function updateTransfer($tbl_id = false, $transfer_data = null)
    {
        if (!$tbl_id || !is_array($transfer_data) || empty($transfer_data))
            return false;

        $transfer_update = $this->db->update('moip_transfers', $transfer_data, array('tbl_id' => $tbl_id));
        
        return $transfer_update;
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam dos sellers
     * ------------------------------------------------------
     */

    public function getStores()
    {        
            
        $sql = "Select id  
                from stores s 
                where not EXISTS(
                    select store_id from moip_subaccounts m where s.id = m.store_id AND ymi_acc IS NOT null
                )";   

        //versao para debug, evitando lojas com poucos dados
        // $sql = "Select id  
        //         from stores s 
        //         where not EXISTS(
        //             select store_id from moip_subaccounts m where s.id = m.store_id AND ymi_acc IS NOT null
        //         )
        //         and s.responsible_name != ''
        //         and s.responsible_email != ''
        //         and s.bank != ''";   

        $query  = $this->db->query($sql);
        $stores = ($query) ? $query->result_array() : false;

        return $stores;
    }


    public function getMoipSubaccounts()
    {
        $sql = "select store_id, moip_id, access_token from moip_subaccounts where active = 1 and ymi_acc is not null";

        $query  = $this->db->query($sql);
        $stores = ($query) ? $query->result_array() : false;

        return $stores;
    }


    public function updateBalance($balance = null)
    {
        if (empty($balance))
            return false;
 
        $this->db->where(array('gateway_id' => $balance['gateway_id'], 'store_id' => $balance['store_id']));
        $exists = $this->db->get('gateway_balance');
        $this->db->reset_query();

        if ($exists->num_rows() > 0) 
        {
            $sql = "update gateway_balance set available = ".$balance['available'].", future =".$balance['future'].", unavailable = ".$balance['unavailable']."
            where
            gateway_id = ".$balance['gateway_id']." 
            and
            store_id = ".$balance['store_id'];
        }
        else
        {
            $sql = "INSERT INTO gateway_balance (gateway_id, store_id, available, future, unavailable) VALUES (
                             ".$balance['gateway_id'].",
                             ".$balance['store_id'].",
                             ".$balance['available'].",
                             ".$balance['future'].",
                             ".$balance['unavailable']."
                             )";
        }

        return $this->db->query($sql);
    }


    public function updateMktBalance($balance = null)
    {
        if (empty($balance))
            return false;
 
        $this->db->where(array('gateway_id' => $balance['gateway_id']));
        $exists = $this->db->get('gateway_balance_mktplace');
        $this->db->reset_query();

        if ($exists->num_rows() > 0) 
        {
            $sql = "
                    update 
                        gateway_balance_mktplace 
                    set 
                        available = ".$balance['available'].", 
                        future =".$balance['future'].", 
                        unavailable = ".$balance['unavailable'].",
                        date_edit = NOW()
                    where
                        gateway_id = ".$balance['gateway_id'];
        }
        else
        {
            $sql = "INSERT INTO gateway_balance_mktplace (gateway_id, available, future, unavailable) VALUES (
                             ".$balance['gateway_id'].",
                             ".$balance['available'].",
                             ".$balance['future'].",
                             ".$balance['unavailable']."
                             )";
        }

        return $this->db->query($sql);
    }


    public function getStoreData($store_id = null)
    {
        if (!$store_id)
            return false;

        $sql            = "select * from stores where id = ?";
        $query          = $this->db->query($sql, array($store_id));
        $store_data     = ($query) ? $query->row_array() : false;

        return $store_data;
    }


    public function getMoipStoreData($subaccount_id = null)
    {
        if (empty($subaccount_id) || !is_array($subaccount_id))
            return false;
            
        $array_keys = array_keys($subaccount_id);
        
        $sql          = "select * from moip_subaccounts where ".$array_keys[0]." = '".$subaccount_id[$array_keys[0]]."'";
        $query        = $this->db->query($sql);
        $store_data   = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return $store_data;
    }


    public function getStoreMoipData($store_id = null)
    {
        if (!$store_id)
            return false;

        $sql            = "select * from moip_subaccounts where store_id = ?";
        $query          = $this->db->query($sql, array($store_id));
        $store_data     = ($query) ? $query->row_array() : false;

        return $store_data;
    }


    public function getSubaccountToken($subaccount_id = null)
    {
        if (empty($subaccount_id) || !is_array($subaccount_id))
            return false;
            
        $array_keys = array_keys($subaccount_id);            

        $sql          = "select access_token from moip_subaccounts where ".$array_keys[0]." = '".$subaccount_id[$array_keys[0]]."'";
        $query        = $this->db->query($sql);
        $access_token = ($query && $query->num_rows() > 0) ? $query->row_array()['access_token'] : false;

        return $access_token;
    }


    public function saveSubaccount($moip_subaccount = null)
    {
        if (!$moip_subaccount)
            return false;

        $insert = $this->db->insert('moip_subaccounts', $moip_subaccount);
        
        return ($insert == true) ? $this->db->insert_id() : false;
    }


    public function getSellerMoipId($store_id = false)
    {
        if (!$store_id)
            return false;

        $sql            = "select moip_id from moip_subaccounts where store_id = ?";
        $query          = $this->db->query($sql, array($store_id));
        $store_moip_id  = ($query && $query->num_rows() > 0) ? $query->row_array()['moip_id'] : false;

        return $store_moip_id;
    }
    

    public function getSellerByMoipId($store_moip_id = false)
    {
        if (!$store_moip_id)
            return false;

        $sql            = "select store_id from moip_subaccounts where moip_id = ?";
        $query          = $this->db->query($sql, array($store_moip_id));
        $store_id       = ($query && $query->num_rows() > 0) ? $query->row_array()['store_id'] : false;

        return $store_id;
    }


    public function getSubaccountId($moip_id = false)
    {
        if (!$moip_id)
            return false;

        $sql            = "select tbl_id from moip_subaccounts where moip_id = ?";
        $query          = $this->db->query($sql, array($moip_id));
        $subaccount_id  = ($query && $query->num_rows() > 0) ? $query->row_array()['tbl_id'] : false;

        return $subaccount_id;
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam os clientes
     * ------------------------------------------------------
     */

    public function getClientData($client_id = null)
    {
        if (empty($client_id))
            $sql = "select * from clients ORDER BY RAND() LIMIT 1";
        else
            $sql = "select * from clients where id = '".$client_id."'";

        $query          = $this->db->query($sql);
        $client_data     = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return $client_data;
    }


    public function saveClient($client = null)
    {
        if (empty($client))
            return false;

        $insert = $this->db->insert('moip_clients', $client);
        
        return ($insert == true) ? $this->db->insert_id() : false;
    }


    public function getClientIdbyMoipId($client_moip_id = false)
    {
        if (!$client_moip_id)
            return false;

        $sql            = "select id from moip_clients where moip_id = ?";
        $query          = $this->db->query($sql, array($client_moip_id));
        $client_id      = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($client_id['id']) ? $client_id['id'] : false;
    }


    public function getClientMoipIdbyId($client_id = false)
    {
        if (!$client_id)
            return false;

        $sql            = "select moip_id from moip_clients where client_id = ?";
        $query          = $this->db->query($sql, array($client_id));
        $client_moip_id = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($client_moip_id['moip_id']) ? $client_moip_id['moip_id'] : false;
    }


    public function getClientsLocal()
    {
        $sql    = "select * from moip_clients order by fullname asc";       
        $query  = $this->db->query($sql);
        $clients = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return $clients;
    }


    public function checkClient($cpf_cnpj = null)
    {
        if (!is_array($cpf_cnpj) || empty($cpf_cnpj))
            return false;

        $sql                = "select moip_id from moip_clients where cpf_cnpj = ?";
        $query              = $this->db->query($sql, array(preg_replace('/\D/', '', $cpf_cnpj)));
        $client_moip_id     = ($query && $query->num_rows() > 0) ? $query->row_array()['moip_id'] : false;

        return $client_moip_id;
    }
    

    /**
     * ------------------------------------------------------
     * Conjunto de métodos que manipula os pedidos
     * ------------------------------------------------------
     */

    public function saveMoipOrder($order_data = null)
    {
        if (!is_array($order_data) || empty($order_data))
            return false;

        $order_moip_tbl_id = $this->db->insert('moip_orders', $order_data);
        
        return ($order_moip_tbl_id == true) ? $this->db->insert_id() : false;
    }


    public function createPreMultiOrder()
    {
        $data_array = array('status' => 'CREATING');

        $multi_order_tbl_id = $this->db->insert('moip_multiorders', $data_array);
        
        return ($multi_order_tbl_id == true) ? $this->db->insert_id() : false;
    }


    public function saveMoipMultiOrder($multi_order_id = false, $multi_order_data = null)
    {
        if (!$multi_order_id || !is_array($multi_order_data) || empty($multi_order_data))
            return false;

        // $multi_order_update = $this->db->replace('moip_multiorders', $multi_order_data);
        $multi_order_update = $this->db->update('moip_multiorders', $multi_order_data, array('tbl_id' => $multi_order_id));
        
        return $multi_order_update;
    }


    public function getMultiOrderIdByMoipId($multiorder_moip_id = false)
    {
        if (!$multiorder_moip_id)
            return false;

        $sql            = "select tbl_id from moip_multiorders where multi_order_moip_id = ?";
        $query          = $this->db->query($sql, array($multiorder_moip_id));
        $order_id      = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($order_id['tbl_id']) ? $order_id['tbl_id'] : false;
    }


    public function getOrderIdbyMoipId($order_moip_id = false)
    {
        if (!$order_moip_id)
            return false;

        $sql            = "select order_id from moip_orders where order_moip_id = ?";
        $query          = $this->db->query($sql, array($order_moip_id));
        $order_id      = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($order_id['order_id']) ? $order_id['order_id'] : false;
    }


    public function getClientMoipIdByOrderId($order_id = false)
    {
        if (!$order_id)
            return false;

        $sql            = "SELECT moip_id FROM moip_clients WHERE client_id = (SELECT customer_id FROM orders WHERE id = ?)";
        $query          = $this->db->query($sql, array($order_id));
        $client_id      = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($client_id['moip_id']) ? $client_id['moip_id'] : false;
        
    }




    /**
     * ------------------------------------------------------
     * Conjunto de métodos que manipula os PAGAMENTOS
     * ------------------------------------------------------
     */

    public function saveMoipPayment($payment_created_array = null)
    {
        if (!is_array($payment_created_array) || empty($payment_created_array))
            return false;

        $payment_moip_tbl_id = $this->db->insert('moip_payments', $payment_created_array);
        
        return ($payment_moip_tbl_id == true) ? $this->db->insert_id() : false;
    }


    public function getPaymentMoipIdById($payment_id = false)
    {
        if (!$payment_id)
            return false;

        $sql                = "SELECT payment_moip_id FROM moip_payments WHERE tbl_id = ?";
        $query              = $this->db->query($sql, array($payment_id));
        $payment_moip_id    = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($payment_moip_id['payment_moip_id']) ? $payment_moip_id['payment_moip_id'] : false;
        
    }


    public function getPaymentIdByMoipId($payment_moip_id = false)
    {
        if (!$payment_moip_id)
            return false;

        $sql                = "SELECT tbl_id FROM moip_payments WHERE payment_moip_id = ?";
        $query              = $this->db->query($sql, array($payment_moip_id));
        $payment_tbl_id     = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($payment_tbl_id['tbl_id']) ? $payment_tbl_id['tbl_id'] : false;
        
    }


    public function updateMoipPayment($payment_created_array = null, $payment_moip_id = false)
    {
        if (!is_array($payment_created_array) || empty($payment_created_array) || !$payment_moip_id)
            return false;

        $payment_update = $this->db->update('moip_payments', $payment_created_array, array('payment_moip_id' => $payment_moip_id));
        
        return $payment_update;
    }

    
    public function saveMoipMultiPayment($multipayment_return = null)
    {
        if (!is_array($multipayment_return) || empty($multipayment_return))
            return false;

        $insert = $this->db->insert('moip_multipayments', $multipayment_return);
        
        return ($insert == true) ? $this->db->insert_id() : false;
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que trata Cartao de Credito
     * ------------------------------------------------------
     */
    public function saveCreditCard($card_data = null)
    {
        if (!is_array($card_data) || empty($card_data))
            return false;

        $insert = $this->db->insert('moip_creditcards', $card_data);
        
        return ($insert == true) ? $this->db->insert_id() : false;
    }


    public function removeCreditCard($credit_card_id = false)
    {
        if (!$credit_card_id)
            return false;

	    $data['removed'] = 1;	    
	    $this->db->where('card_moip_id', $credit_card_id);
	    return $update = $this->db->update('moip_creditcards', $data);
    }



    
    /**
     * ------------------------------------------------------
     * Conjunto de métodos Gerais
     * ------------------------------------------------------
     */

    public function getFunctionId($function_name = false)
    {
        if (!($function_name))
            return false;

        $sql            = "select tbl_id from moip_functions where function_name = ?";
        $query          = $this->db->query($sql, array($function_name));
        $function_id    = ($query && $query->num_rows() > 0) ? $query->row_array() : false;

        return ($function_id['tbl_id']) ? $function_id['tbl_id'] : false;
    }
	

    public function saveFunctionReceiver($receiver_data = null)
    {
        if (!is_array($receiver_data) || empty($receiver_data))
            return false;

        $order_receiver = $this->db->replace('moip_receivers', $receiver_data);
        
        return ($order_receiver == true) ? true : false;
    }


    public function saveFunctionEvent($event_data = null)
    {
        if (!is_array($event_data) || empty($event_data))
            return false;

        $order_event = $this->db->replace('moip_events', $event_data);

        return ($order_event == true) ? true : false;
    }


    public function saveFunctionEntry($entry_data = null)
    {
        if (!is_array($entry_data) || empty($entry_data))
            return false;

        $order_entry = $this->db->replace('moip_entries', $entry_data);

        return ($order_entry == true) ? true : false;
    }


    public function saveFunctionFee($fee_data = null)
    {
        if (!is_array($fee_data) || empty($fee_data))
            return false;

        $fee_result = $this->db->replace('moip_fees', $fee_data);

        return $fee_result;
    }


    public function checkUpdatedStores($minutes = null)
    {
        if (!$minutes)
            return false;

        $sql = "select id, CNPJ, name, bank, agency, account, service_charge_value, service_charge_freight_value from stores 
                where 
                date_update > (now() - interval ? minute)
                and
                id in (select store_id from moip_subaccounts)";

        $query              = $this->db->query($sql, array($minutes));
        $updated_stores     = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return ($updated_stores) ? $updated_stores : false;
    }


    //braun
    public function getCorrectOrderMktplace($own_id, $store_id = null, $store_moip_id = null)
    {
        $sql = "select numero_marketplace from orders where numero_marketplace like '".$own_id."%' and store_id = ".$store_id;

        if (!$store_id || $store_moip_id)
        {
            $sql = "select numero_marketplace from orders where numero_marketplace like '".$own_id."%' and store_id = 
                    (select store_id from moip_subaccounts where moip_id='".$store_moip_id."')";
        }

        $query = $this->db->query($sql);
        return ($query && $query->num_rows() > 0) ? $query->row_array()['numero_marketplace'] : false;
    }


    public function mockMethod($string = 'confirm') //método utilizado para informar algum processamento por atualizar ocorrencia no banco
    {
        $mock_array = array
        (
            'store_id' => 0,
            'payment_gateway_id' => 0,
            'status' => 'mock_flag',
            'description' => $string
        );

        $this->db->insert('payment_gateway_store_logs', $mock_array);
    }


	public function getCalendarEvent($module_path, $module_method)
	{
		$sql    = "select * from calendar_events where module_path = '".$module_path."' and module_method = '".$module_method."'";
	    $query = $this->db->query($sql);
	    return $query->row_array();
	}


    public function saveNewJob($save_job_array): bool
    {
        $this->db->insert_string('job_schedule', $save_job_array);

        $new_job = $this->db->insert('job_schedule', $save_job_array);
        
        // dd($this->db->last_query());

        return ($new_job) ? true : false;
    }


    //braun
    public function getPendencies(): ?array
    {
        $sql = "
                SELECT
                     gp.id AS pendency_id
                    ,gp.store_id
                    ,gp.amount
                    ,gp.`status`
                    ,msub.moip_id
                    ,msub.access_token
                FROM
                    gateway_pendencies gp
                    INNER JOIN moip_subaccounts msub ON msub.store_id = gp.store_id
                WHERE
                    gp.`status` <> 'r'
                ORDER BY
                    gp.store_id ASC, gp.amount asc 
                ";

        $query = $this->db->query($sql);

        return ($query && $query->num_rows() > 0) ? $query->result_array() : null;
    }


    //braun
    public function settlePendency($pendency_id = null): ?bool
    {
        // $sql = "update gateway_pendencies set status = 'r' where id = ".$pendency_id;
        // $query = $this->db->query($sql);
        $this->db->where('id', $pendency_id);
        $update = $this->db->update('gateway_pendencies', ['status' => 'r']);
        // $this->db->affected_rows();

        return ($this->db->affected_rows() > 0) ? true : false;
        // $this->db->where('id', $pendency_id);
        // return $this->db->update('gateway_pendencies', ['status' => 'r']);
    }
    
}
