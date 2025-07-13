<?php

class Model_gateway_settings extends CI_Model
{

    public $tableName = 'payment_gateway_settings';

    public function __construct()
    {
        parent::__construct();
    }

    public function getSettings($gateway_id = false)
    {
        $sql = "SELECT * FROM {$this->tableName} where gateway_id = ?";

        if (!$gateway_id)
            $sql = "SELECT * FROM {$this->tableName}";

        $query = $this->db->query($sql, array($gateway_id));

        return $query->result_array();
    }

    public function updateSettings($name, $value)
    {
        $data['value'] = $value;

        $this->db->where('name', $name);
        return $this->db->update($this->tableName, $data);
    }

    /* get the Setting data */
    public function getSettingData($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        $sql = "SELECT * FROM {$this->tableName}";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);
            return ($insert == true) ? true : false;
        }
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->tableName, $data);
            return ($update == true) ? true : false;
        }
    }


	public function getGatewaySettingByName($gateway_id = false, $setting_name = null)
	{
		$sql = "SELECT value FROM {$this->tableName} where gateway_id = ? and name = ?";

		$query = $this->db->query($sql, array($gateway_id, $setting_name));

		$result = $query->row_array();

		if (isset($result['value']))
		{
			return $result['value'];
		}
		else
		{
			return null;
		}
	}

    public function getGateways()
    {
        $this->db->distinct();
        $this->db->group_by('gateway_id');
        $gateways = $this->db->get('payment_gateway_settings');
        return $gateways->result_array();
    }

    public function getGatewayByGatewayId($gateway_id)
    {
        $this->db->where('gateway_id', $gateway_id);
        $gateway = $this->db->get('payment_gateway_settings');
        return $gateway->result_array();
    }

    public function getGatewaySubaccountsData($gateway_id = null)
    {

        if($gateway_id) {
            $this->db->where('gateway_id', $gateway_id);
        }

        $gateway = $this->db->get('gateway_subaccounts');
        return $gateway->num_rows();
    }

    public function existsPrimaryAccount($primary_account_code): bool
    {
        $this->db->where('gateway_account_id', $primary_account_code);
        $this->db->or_where('secondary_gateway_account_id', $primary_account_code);
        $gateway = $this->db->get('gateway_subaccounts');
        return $gateway->num_rows() > 0;
    }

}