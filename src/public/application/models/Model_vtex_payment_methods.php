<?php

class Model_vtex_payment_methods extends CI_Model
{
    private $tableName = 'vtex_payment_methods';
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

            return ($update == true) ? $id : false;
        }
    }

    public function getPaymentMethod($payment_method = array()): ?array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('int_to', $payment_method['int_to']);
        $this->db->where('method_id', $payment_method['method_id']);

        return $this->db->get()->row_array();

    }

    public function getPaymentMethodById($id): ?array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('id', $id);

        return $this->db->get()->row_array();

    }

    public function vtexInsertUpdatePaymentMethods($payment_methods = null)
    {
        if (empty($payment_methods)) {
            return false;
        }

        $sql = "INSERT INTO 
                {$this->tableName} (int_to, method_id, method_name, method_description, active) 
                VALUES (
                    '" . $payment_methods['int_to'] . "', 
                        " . $payment_methods['method_id'] . ", 
                    '" . $payment_methods['method_name'] . "', 
                    '" . $payment_methods['method_description'] . "', 
                    1
                )
                ON DUPLICATE KEY UPDATE active = 1";

        $insert = $this->db->query($sql);
        return ($insert === true) ? true : false;
    }

    public function vtexPaymentMethodsInactive($int_to, $ids_imported)
    {

        $this->db->where_not_in('method_id', $ids_imported)
        ->where('int_to', $int_to);
        $this->db->where('int_to', $int_to);
        $update = $this->db->update($this->tableName, array('active' => 2));
        return ($update === true) ? true : false;
    }

    public function vtexGetPaymentMethods(string $int_to = null)
    {
        $and = ($int_to) ? " and int_to = '" . $int_to . "' " : "";

        $sql = "select * 
                FROM {$this->tableName} 
                where active = 1 " . $and . " order by method_name asc";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function countActiveByMarketplace(string $int_to = null)
    {
        $and = ($int_to) ? " and int_to = '" . $int_to . "' " : "";

        $sql = "select count(*) as total 
                FROM {$this->tableName} 
                where active = 1 " . $and . " order by method_name asc";
        $query = $this->db->query($sql);
        $return = $query->row_array();
        return $return['total'];
    }

}
