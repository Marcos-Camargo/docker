<?php

class Model_campaigns_v2_trade_policies extends CI_Model
{
    private $tableName = 'campaign_v2_trade_policies';
    private $createLog;

    public static $tradePoliciesByCampaign = array();

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->createLog = new CampaignsV2Logs();
    }

    public function create($data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $idCampanha = $this->db->insert_id();

        $this->createLog->log($data, $idCampanha, get_class($this), __FUNCTION__);

        return $insert ? $idCampanha : false;
    }

    public function relateTradePolicyToCampaignV2(array $trade_policy)
    {
        $sql = "
            INSERT INTO {$this->tableName} (campaign_v2_id, trade_policy_id) VALUES (
                ".$trade_policy['campaign_v2_id'].",
                (SELECT id FROM vtex_trade_policies where int_to = '".$trade_policy['int_to']."' and trade_policy_id = ".$trade_policy['trade_policy_id'].")
            )
            ";

        return $this->db->query($sql);
    }

    public function getCampaignV2TradePoliciesIds($campaign_v2_id)
    {

        if (isset(self::$tradePoliciesByCampaign[$campaign_v2_id])) {
            return self::$tradePoliciesByCampaign[$campaign_v2_id];
        }

        $trade_policies = $this->getCampaignV2TradePolicies($campaign_v2_id);
        $ids = [];
        if ($trade_policies) {
            foreach ($trade_policies as $trade_policy) {
                $ids[] = $trade_policy['trade_policy_id'];
            }
        }
        return self::$tradePoliciesByCampaign[$campaign_v2_id] = $ids;
    }

    public function getCampaignV2TradePolicies($campaign_v2_id)
    {
        $sql = "SELECT v.trade_policy_id, v.trade_policy_name FROM {$this->tableName} c LEFT JOIN vtex_trade_policies v on c.trade_policy_id=v.id where c.campaign_v2_id = ".$campaign_v2_id;
        $query = $this->db->query($sql);
        $this->db->query($sql);
        return $query->result_array();
    }

    public function getIntToFromCampaign($campaign_v2_id)
    {
        $sql = "SELECT DISTINCT v.int_to 
                FROM vtex_trade_policies v 
                INNER JOIN campaign_v2_trade_policies p ON v.id=p.trade_policy_id AND p.campaign_v2_id = ".$campaign_v2_id;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getByTradePolicyId(int $tradePolicyId)
    {
        $sql = "SELECT DISTINCT campaign_v2_id 
                FROM {$this->tableName} 
                WHERE trade_policy_id = $tradePolicyId";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

}
