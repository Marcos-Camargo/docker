<?php

class Model_omnilogic_gateway extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getList() {
        $sql = "select * from omnilogic_gateway where status = 1 ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
}