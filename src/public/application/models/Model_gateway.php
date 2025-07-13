<?php

class Model_gateway extends CI_Model
{
    public const GETNET = "getnet";
    public const PAGARME = "pagarme";
    public const PAGSEGURO = "pagseguro";
    public const MOIP = "moip";
    public const IUGU = "iugu";
    public const MAGALUPAY = "magalupay";
    public const EXTERNO = "externo";
    public const TUNA = "tuna";
    public const FASTSHOP = "fastshop";

    public function __construct()
    {
        parent::__construct();
    }


    public function getSubAccounts($gatewayId = null)
    {
        $where = '';
        if (!is_null($gatewayId)) {
            $where = 'where gateway_id = ' . $gatewayId;
        }

        $sql = "SELECT * FROM gateway_subaccounts " . $where;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function countSubAccounts()
    {
        $sql = "SELECT COUNT(*) total FROM gateway_subaccounts ";
        $query = $this->db->query($sql);
        $result = $query->row_array();
        return $result['total'];
    }

    public function getSubAccountByStoreId(int $store_id, $gatewayId = null): ?array
    {

        $filter = '';
        $join = '';
        if (is_null($gatewayId)) {
            $join = " JOIN payment_gateways ON payment_gateways.id = gateway_subaccounts.gateway_id ";
        } else {
            $filter = " and gateway_id = '$gatewayId'";
        }

        $sql = "SELECT * FROM gateway_subaccounts " . $join . " where store_id = ? " . $filter;

        $query = $this->db->query($sql, array($store_id));

        return $query->row_array();
    }


    public function getStoresWithoutGatewaySubAccounts()
    {
        $sql = "select s.* from stores s 
            left join gateway_subaccounts gs on gs.store_id = s.id
        where s.id != 1 and gs.id is null ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getStoresWithPendencies($gatewayId = null)
	{

        $this->db->join('stores', 'stores.id = gateway_subaccounts.store_id');
        
		if($gatewayId){
			$this->db->where('gateway_id', $gatewayId);
		}

		$this->db->where('with_pendencies', 1);
		$this->db->where('gateway_account_id IS NOT NULL');

        $this->db->select('gateway_subaccounts.id, stores.name, store_id, secondary_gateway_account_id, gateway_account_id, with_pendencies');

		$query = $this->db->get('gateway_subaccounts');
		return $query->result();

	}

    public function getStoreByGatewayId($id)
	{

        $this->db->where('gateway_account_id', $id);
        $this->db->or_where('secondary_gateway_account_id', $id);

        $this->db->select('gateway_subaccounts.store_id');

		$query = $this->db->get('gateway_subaccounts');
		return $query->result();

	}

    public function createSubAccounts($data)
    {
        $this->db->insert('gateway_subaccounts', $data);
        return $this->db->insert_id();
    }


    public function updateSubAccounts(int $id, array $data)
    {
        return $this->db->update('gateway_subaccounts', $data, ['id' => $id]);
    }

    public function countStoresWithGatewayIdDifferentFromOne($actualStoreId, $gatewayId, $subaccountId)
    {
        $sql = "SELECT * 
                FROM gateway_subaccounts 
                WHERE gateway_id = $gatewayId 
                  AND store_id <> $actualStoreId 
                  AND gateway_account_id = '$subaccountId'";

        $query = $this->db->query($sql);
        return $query->num_rows();
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

    public function getGatewayCodeById(int $id): ?string
    {

        $row = $this->getGatewayById($id);

        return $row['code'] ?? '';
    }

    public function getGatewayNameById(int $id): ?string
    {

        $row = $this->getGatewayById($id);

        return $row['name'] ?? '';
    }

    public function getGatewayById(int $id): array
    {

        $sql = "SELECT name,code FROM payment_gateways WHERE id = ?";

        $query = $this->db->query($sql, array($id));

        return $query ? $query->row_array() : [];
    }


    public function getVtexSellerIdIntegration($store_id = false)
    {
        if (!$store_id)
            return false;

        $sql        = "select auth_data from integrations where store_id = ? and auth_data is not null";
        $query      = $this->db->query($sql, array($store_id));
        $auth_json  = ($query && $query->num_rows() > 0) ? $query->row_array()['auth_data'] : false;

        return $auth_json;
    }

    public function getStoresForUpdatesSubAccounts()
    {
        $sql = "SELECT S.* FROM stores S
        INNER JOIN `getnet_subaccount` GS ON GS.store_id = S.id
        INNER JOIN `gateway_subaccounts` GSA ON GSA.store_id = S.id
        WHERE S.date_update >= DATE_ADD(NOW(), INTERVAL -60 MINUTE) ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getSubAccountsInformationsGetnet($store_id)
    {

        $sql = "SELECT * FROM getnet_subaccount WHERE store_id = ?";

        $query = $this->db->query($sql, array($store_id));

        return $query ? $query->row_array() : [];
    }

    public function createSubAccountsGetnet($data)
    {
        return $this->db->insert('getnet_subaccount', $data);
    }

    public function saveextractgetnet($data)
    {

        //$sql = "select id, count(*) as qtd from getnet_extrato where order_id_json = '".$data['order_id_json']."' and item_id = '".$data['item_id']."' and subseller_rate_amount = '".$data['subseller_rate_amount']."'";
        $sql = "select id, count(*) as qtd from getnet_extrato where chave_md5 = '" . $data['chave_md5'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();

        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_extrato', $data);
        } else {
            return $this->db->update('getnet_extrato', $data, ['id' => $contagem[0]['id']]);
        }
    }

    public function saveextractgetnet2($data)
    {

        $sql = "select id, count(*) as qtd from getnet_extrato_2 where chave_md5 = '" . $data['chave_md5'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();
        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_extrato_2', $data);
        } else {
            return $this->db->update('getnet_extrato_2', $data, ['id' => $contagem[0]['id']]);
        }
    }

    public function saveextractgetnet3($data)
    {

        $sql = "select id, count(*) as qtd from getnet_extrato_2 where json_retorno = '" . $data['json_retorno'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();
        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_extrato_2', $data);
        }/*else{
            return $this->db->update('getnet_extrato_2', $data, ['id' => $contagem[0]['id']]);
        }
        return $this->db->insert('getnet_extrato_2', $data);*/
    }

    public function saveextractgetnet4($data)
    {

        $sql = "select id, count(*) as qtd from getnet_extrato_2 where json_retorno = '" . $data['json_retorno'] . "' and item_id = '" . $data['item_id'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();
        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_extrato_2', $data);
        } else {
            return $this->db->update('getnet_extrato_2', $data, ['id' => $contagem[0]['id']]);
        }
    }

    public function saveextractgetnetajuste($data)
    {

        //$sql = "select id, count(*) as qtd from getnet_extrato_ajustes where cpfcnpj_subseller = '".$data['cpfcnpj_subseller']."' and adjustment_date = '".$data['adjustment_date']."' and adjustment_reason = '".$data['adjustment_reason']."' and adjustment_amount = '".$data['adjustment_amount']."'";
        $sql = "select id, count(*) as qtd from getnet_extrato_ajustes where chave_md5 = '" . $data['chave_md5'] . "'";
        $query = $this->db->query($sql);
        $contagem = $query->result_array();
        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_extrato_ajustes', $data);
        } else {
            return $this->db->update('getnet_extrato_ajustes', $data, ['id' => $contagem[0]['id']]);
        }
    }

    public function saveextractgetnetajuste2($data)
    {

        /*$sql = "select id, count(*) as qtd from getnet_extrato_ajustes_2 where cpfcnpj_subseller = '".$data['cpfcnpj_subseller']."' and adjustment_date = '".$data['adjustment_date']."' and adjustment_reason = '".$data['adjustment_reason']."' and adjustment_amount = '".$data['adjustment_amount']."' and transaction_sign = '".$data['transaction_sign']."'";
        
        $query = $this->db->query($sql);
        $contagem = $query->result_array();
        if($contagem[0]['qtd'] == "0"){
            return $this->db->insert('getnet_extrato_ajustes_2', $data);
        }else{
            return $this->db->update('getnet_extrato_ajustes_2', $data, ['id' => $contagem[0]['id']]);
        }*/
        return $this->db->insert('getnet_extrato_ajustes_2', $data);
    }

    public function saveextractgetnetajuste3($data)
    {

        //$sql = "select id, count(*) as qtd from getnet_extrato_ajustes_2 where json_retorno = '".$data['json_retorno']."'";
        $sql = "select id, count(*) as qtd from getnet_extrato_ajustes_2 where chave_md5 = '" . $data['chave_md5'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();
        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_extrato_ajustes_2', $data);
        } else {
            return $this->db->update('getnet_extrato_ajustes_2', $data, ['id' => $contagem[0]['id']]);
        }
    }

    public function getExtratoGetnetORders($idOrder)
    {
        $sql = "SELECT distinct order_id_json ,seller_id_json ,marketplace_subsellerid ,item_id ,installment_amount ,payment_date ,release_status ,subseller_rate_confirm_date ,transaction_sign ,subseller_rate_amount ,json_retorno  FROM getnet_extrato WHERE order_id_json = ?";

        $query = $this->db->query($sql, array($idOrder));
        return $query->result_array();
    }

    public function limpaextratoanomes($tabela = "getnet_extrato_2", $ano_mes = null)
    {
        $sql = "DELETE FROM $tabela WHERE ano_mes = ?";
        $query = $this->db->query($sql, array($ano_mes));
        return $query;
    }

    public function atualizamdrpayment($mdr, $paymentID){

        /*$sql = "update orders_payment set taxa_cartao_credito = '$mdr' where payment_id = UPPER(REPLACE('$paymentID','-','')) or transaction_id = UPPER(REPLACE('$paymentID','-',''))";

        return $this->db->query($sql);*/

        $data['taxa_cartao_credito'] = $mdr;

        return $this->db->update('orders_payment', $data, ['payment_id' => $paymentID]);

  }

  public function insertmdrpayment($mdr, $paymentID){

    $data['mdr'] = $mdr;
    $data['payment_id'] = $paymentID;

    //$sql = "select id, count(*) as qtd from getnet_extrato_ajustes_2 where json_retorno = '".$data['json_retorno']."'";
    $sql = "select id, count(*) as qtd from getnet_mdr where payment_id = '".$paymentID."'";
        
    $query = $this->db->query($sql);
    $contagem = $query->result_array();
    if($contagem[0]['qtd'] == "0"){
        return $this->db->insert('getnet_mdr', $data);
    }else{
        return $this->db->update('getnet_mdr', $data, ['id' => $contagem[0]['id']]);
    }

  }

  public function insertmdrpedidopayment($orderId, $paymentID){

    $data['payment_id'] = md5($paymentID);
    $data['order_id'] = $orderId;

    $sql = "select id, count(*) as qtd from getnet_mdr_pedido where payment_id = '".$paymentID."' and order_id = '".$orderId."'";
        
    $query = $this->db->query($sql);
    $contagem = $query->result_array();
    if($contagem[0]['qtd'] == "0"){
        return $this->db->insert('getnet_mdr_pedido', $data);
    }
  }

  public function getGatewaySettingByNameAndGatewayCode(string $name = null, int $code = null)
  {
        if(is_null($name)){
            return null;
        }

        $this->db->where('name', $name);
        if(!is_null($code)){
            $this->db->where('gateway_id', $code);
        }
        $this->db->from('payment_gateway_settings');
        $gateway_setting = $this->db->get();

        if($gateway_setting->num_rows() > 0){
            return $gateway_setting->row();
        }

        return null;
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

  public function updateSellerBalance($balance = null)
	{
		if (empty($balance))
			return false;

		$this->db->where(array('gateway_id' => $balance['gateway_id'], 'store_id' => $balance['store_id']));
		$exists = $this->db->get('gateway_balance');
		$this->db->reset_query();

		if ($exists->num_rows() > 0)
		{
			$sql = "update gateway_balance set available = ".$balance['available'].", future =".$balance['future'].", unavailable = ".$balance['unavailable'].", date_edit = NOW()
			where
			gateway_id = ".$balance['gateway_id']." 
			and
			store_id = ".$balance['store_id'];
		}
		else
		{
			$sql = "INSERT INTO gateway_balance (gateway_id, store_id, available, future, unavailable, date_edit) VALUES (
							 ".$balance['gateway_id'].",
							 ".$balance['store_id'].",
							 ".$balance['available'].",
							 ".$balance['future'].",
							 ".$balance['unavailable'].",
							 NOW()
							 )";
		}

		return $this->db->query($sql);
	}

  public function getSellerBalance($balance = [])
	{
		$this->db->where(array('gateway_id' => $balance['gateway_id'], 'store_id' => $balance['store_id']));
		$exists = $this->db->get('gateway_balance');
		$this->db->reset_query();

        return $exists->row_array();

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

    public function saveextractgetnetpayment($data)
    {

        $sql = "select id, count(*) as qtd from getnet_payment where reference_number = '" . $data['reference_number'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();

        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_payment', $data);
        } else {
            return $this->db->update('getnet_payment', $data, ['id' => $contagem[0]['id']]);
        }
    }

    public function getExtratoGetnetORdersAndStoreId($idOrder, $transactionId, $storeId)
    {
        $sql = "SELECT distinct order_id_json ,seller_id_json ,marketplace_subsellerid ,item_id ,installment_amount ,payment_date ,release_status ,subseller_rate_confirm_date ,transaction_sign ,subseller_rate_amount ,json_retorno  FROM getnet_extrato WHERE (order_id_json = ? or order_id_json = ?) and item_id = ?";

        // $query = $this->db->query($sql, array($idOrder));
        $query = $this->db->query($sql, [$idOrder, $transactionId, $storeId]);
        return $query->result_array();
    }

    public function saveextractgetnetv2($data)
    {
        $sql = "select id, count(*) as qtd from getnet_extrato_v2 where chave_md5 = '" . $data['chave_md5'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();

        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('getnet_extrato_v2', $data);
        } else {
            return $this->db->update('getnet_extrato_v2', $data, ['id' => $contagem[0]['id']]);
        }
    }

    public function createSubAccountsMagaluPay($data)
    {
        return $this->db->insert('magalupay_subaccount', $data);
    }

    public function updateSubAccountsMagaluPay(int $storeId, array $data)
    {
        return $this->db->update('magalupay_subaccount', $data, ['store_id' => $storeId]);
    }

    public function getStoresForUpdatesSubAccountsMagaluPay()
    {
        $sql = "SELECT S.* FROM stores S
        INNER JOIN `magalupay_subaccount` MP ON MP.store_id = S.id
        INNER JOIN `gateway_subaccounts` GSA ON GSA.store_id = S.id
        WHERE S.date_update >= DATE_ADD(NOW(), INTERVAL -60 MINUTE) ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function savaextratomagalupay($data)
    {
        $sql = "select id, count(*) as qtd from magalupay_extrato where chave_md5 = '" . $data['chave_md5'] . "'";

        $query = $this->db->query($sql);
        $contagem = $query->result_array();

        if ($contagem[0]['qtd'] == "0") {
            return $this->db->insert('magalupay_extrato', $data);
        } else {
            return $this->db->update('magalupay_extrato', $data, ['id' => $contagem[0]['id']]);
        }
    }


}