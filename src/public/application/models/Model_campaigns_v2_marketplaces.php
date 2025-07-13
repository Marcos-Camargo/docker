<?php

class Model_campaigns_v2_marketplaces extends CI_Model
{

    private $tableName = 'campaign_v2_marketplaces';
    private $createLog;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->createLog = new CampaignsV2Logs();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);

            $id = $this->db->insert_id();

            $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

            return ($insert == true) ? $id : false;
        }
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

    public function getByCampaignId(int $campaignId): ?array
    {

        $sql = "SELECT * FROM {$this->tableName} WHERE campaign_v2_id = $campaignId ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

}