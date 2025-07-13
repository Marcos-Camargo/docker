<?php
class Model_oci_queues extends CI_Model
{

    private $tableName = 'oci_queues';

    public function __construct()
    {
        parent::__construct();
    }

    /*get the active brands information*/
    public function findAll()
    {
        $sql = "SELECT * FROM oci_queues";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function findByName(string $displayName)
    {
        $sql = "SELECT * FROM oci_queues WHERE display_name = '$displayName'";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function create(array $data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        return $insert ? $id : false;
    }

}
