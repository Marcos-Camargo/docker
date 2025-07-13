<?php

class Model_campaigns_v2_logs extends CI_Model
{

    private $tableName = 'campaign_v2_logs';

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->createLog = new CampaignsV2Logs();
    }

    public function saveLog($data)
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);
            return ($insert == true) ? true : false;
        }
    }

}