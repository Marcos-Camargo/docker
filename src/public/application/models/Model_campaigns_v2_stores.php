<?php

class Model_campaigns_v2_stores extends CI_Model
{

    private $tableName = 'campaign_v2_stores';
    private $createLog;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->createLog = new CampaignsV2Logs();
    }

    public function getByCampaignId(int $campaignId): ?array
    {

        $this->db->select("{$this->tableName}.*, stores.name");
        $this->db->from($this->tableName);
        $this->db->join('stores', 'stores.id = '.$this->tableName.'.store_id');
        $this->db->where('campaign_v2_id', $campaignId);

        $q = $this->db->get();

        return $q->result_array();

    }

    public function joinCampaign(int $campaignId, int $storeId): void
    {

        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->from($this->tableName);
        $this->db->where('store_id', $storeId);
        $this->db->where('joined', 0);
        $q = $this->db->get();

        $row = $q->row_array();

        if ($row) {
            $row['joined'] = 1;
            $this->update($row, $row['id']);

            $this->createLog->log($row, $row['id'], $this->tableName, __FUNCTION__);

            return;
        }

        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->from($this->tableName);
        $this->db->where('store_id', $storeId);
        $this->db->where('joined', 1);
        $q = $this->db->get();

        $row = $q->row_array();

        //Already joined
        if ($row) {
            return;
        }

        //Se ainda não está cadastrado, agora vai ser cadastrado (Quando segmento != stores)
        $row = [];
        $row['campaign_v2_id'] = $campaignId;
        $row['store_id'] = $storeId;
        $row['joined'] = 0;

        $this->create($row);

        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->from($this->tableName);
        $this->db->where('store_id', $storeId);
        $this->db->where('joined', 0);
        $q = $this->db->get();

        $row = $q->row_array();
        if ($row) {

            $row['joined'] = 1;
            $this->update($row, $row['id'], false);

            $this->createLog->log($row, $row['id'], get_class($this), __FUNCTION__);

        }

    }

    public function update($data, $id, $saveLog = true)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->tableName, $data);

            if ($saveLog) {
                $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);
            }

            return ($update == true) ? $id : false;
        }
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);

            $this->createLog->log($data, $this->db->insert_id(), $this->tableName, __FUNCTION__);

            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }

    public function exists($campaignId, $storeId): bool
    {
        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->from($this->tableName);
        $this->db->where('store_id', $storeId);
        $q = $this->db->get();
        $row = $q->row_array();
        return (bool) $row;
    }

}