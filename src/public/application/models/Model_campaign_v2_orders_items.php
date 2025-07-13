<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Model_campaign_v2_orders_items extends CI_Model
{

    private $tableName = 'campaign_v2_orders_items';
    private $createLog;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_campaign_v2_orders_items');
        $this->load->model('model_settings');
        $this->load->library('CampaignsV2Logs');
        $this->createLog = new CampaignsV2Logs();
    }

    public function save($data)
    {
        $this->db->insert($this->tableName, $data);
        $this->createLog->log($data, $this->db->insert_id(), $this->tableName, __FUNCTION__);
        if ($this->db->affected_rows() == '1') {
            return true;
        }
        return false;
    }

    public function getAllItemCampaignsByOrderItemId($itemID = null)
    {
        if (empty($itemID)) {
            return false;
        }

        $sql = "select * from campaign_v2_orders_items cvoi  where item_id = ? order by id desc";

        $query = $this->db->query($sql, array($itemID));
        $result = $query->row_array();

        return ($result) ? $result : false;
    }
}
