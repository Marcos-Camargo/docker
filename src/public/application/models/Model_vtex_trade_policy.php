<?php

class Model_vtex_trade_policy extends CI_Model
{
    private $tableName = 'vtex_trade_policies';
    private $createLog;

    public function __construct()
    {

        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->createLog = new CampaignsV2Logs();

    }

    public function create($data)
    {

        $insert = $this->db->insert($this->tableName, $data);

        $idCampanha = $this->db->insert_id();

        $this->createLog->log($data, $idCampanha, get_class($this), __FUNCTION__);

        return $insert ? $idCampanha : false;

    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->tableName, $data);

            $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

            return $update ? $id : false;
        }
    }

    public function getTradePolicy($trade_policy = array()): ?array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('int_to', $trade_policy['int_to']);
        $this->db->where('trade_policy_id', $trade_policy['trade_policy_id']);

        return $this->db->get()->row_array();

    }

    public function findAll(): ?array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        return $this->db->get()->result_array();

    }

    public function getTradePolicyById($id): ?array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('id', $id);

        return $this->db->get()->row_array();

    }

    public function vtexInsertUpdateTradePolicies($trade_policy = null)
    {
        if (empty($trade_policy)) {
            return false;
        }

        $sql = "INSERT INTO 
                {$this->tableName} (int_to, trade_policy_id, trade_policy_name, active) 
                VALUES (
                    '".$trade_policy['int_to']."', 
                        ".$trade_policy['trade_policy_id'].", 
                    '".$trade_policy['trade_policy_name']."',
                    '".$trade_policy['active']."'
                )";

        $insert = $this->db->query($sql);
        return $insert === true;
    }


    public function vtexGetTradePolicies(string $int_to = null)
    {
        $and = ($int_to) ? " and int_to = '".$int_to."' " : "";

        $sql = "select * 
                FROM {$this->tableName} 
                where active = 1 ".$and." order by trade_policy_name asc";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function countActiveByMarketplace(string $int_to = null)
    {
        $sql = "SELECT
                    count(*) as total
                FROM
                    vtex_trade_policies vtp
                    JOIN integrations_settings ist ON JSON_CONTAINS(ist.tradesPolicies, CONCAT('\"', vtp.trade_policy_id, '\"'), '$')
                    JOIN integrations i ON ist.integration_id = i.id 
                WHERE
                    vtp.active = 1 
                    AND vtp.int_to = '$int_to' 
                    AND i.int_to = '$int_to' ";

        $query = $this->db->query($sql);
        $result = $query->row_array();
        return $result['total'];
    }

    public function vtexGetTradePoliciesBeingUsed(string $int_to = null)
    {

        $sql = "SELECT
                    vtp.*,
                    ist.integration_id 
                FROM
                    vtex_trade_policies vtp
                    JOIN integrations_settings ist ON JSON_CONTAINS(ist.tradesPolicies, CONCAT('\"', vtp.trade_policy_id, '\"'), '$')
                    JOIN integrations i ON ist.integration_id = i.id 
                WHERE
                    vtp.active = 1 
                    AND vtp.int_to = '$int_to' 
                    AND i.int_to = '$int_to' 
                ORDER BY
                    vtp.trade_policy_name ASC";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function vtexGetInactiveTradePolicies()
    {
        $sql = "select * 
                FROM {$this->tableName} 
                where active = 2 order by trade_policy_name asc";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function vtexTradePoliciesInactive($ids_imported)
    {

        $this->db->where_not_in('trade_policy_id', $ids_imported);
        $update = $this->db->update($this->tableName, array('active' => 2));
        return ($update === true) ? true : false;
    }

}
