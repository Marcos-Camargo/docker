<?php

/**
 * Class Model_conciliation_transfers_log
 */
class Model_conciliation_transfers_log extends CI_Model
{

    public $tableName = 'conciliation_transfer_logs';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {

        $sql = "SELECT * FROM {$this->tableName}";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function create(array $data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        return $insert ? $id: false;
    }

    public function update($id = false, $data = false)
    {
        return $this->db->update($this->tableName, $data, array('id' => $id));
    }

}