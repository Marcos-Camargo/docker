<?php

class Model_campaign_v2_vtex_campaigns_logs extends CI_Model
{

    private $tableName = 'campaign_v2_vtex_campaigns_logs';

    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {

        $this->db->insert($this->tableName, $data);

        return $this->db->insert_id();

    }

    public function update($data, $id)
    {

        $this->db->where('id', $id);

        return $this->db->update($this->tableName, $data);

    }

}