
<?php

class Model_omnilogic_channel_mkt extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getList() {
        $sql = "select * from omnilogic_channel_mkt";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
}