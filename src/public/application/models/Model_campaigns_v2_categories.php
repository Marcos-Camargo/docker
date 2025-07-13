<?php

class Model_campaigns_v2_categories extends CI_Model
{

    private $tableName = 'campaign_v2_categories';
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

        $this->db->select("{$this->tableName}.*, categories.name category_name");
        $this->db->from($this->tableName)
            ->join('categories', 'categories.id = '.$this->tableName.'.category_id')
            ->where("{$this->tableName}.campaign_v2_id", $campaignId);

        $q = $this->db->get();

        return $q->result_array();

    }

    public function getCategoryByCampaignIdProductId(int $campaignId, int $categoryId): ?array
    {

        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->where('category_id', $categoryId);
        $this->db->limit(1, 0);

        return $this->db->get($this->tableName)->row_array();

    }

}