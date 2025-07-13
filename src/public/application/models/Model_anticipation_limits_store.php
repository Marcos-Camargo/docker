<?php

class Model_anticipation_limits_store extends CI_Model
{

    private $tableName = 'anticipation_limits_store';

    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);

            $id = $this->db->insert_id();

            return ($insert == true) ? $id : false;
        }
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->tableName, $data);

            return ($update == true) ? $id : false;
        }
    }

    public function findByStoreId(int $storeId): ?array
    {
        return $this->db->select('*')->from($this->tableName)->where(['store_id' => $storeId])->limit('1')->get()->row_array();
    }

}