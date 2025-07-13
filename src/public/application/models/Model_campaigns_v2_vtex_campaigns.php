<?php

class Model_campaigns_v2_vtex_campaigns extends CI_Model
{
    private $tableName = 'campaign_v2_vtex_campaigns';
    private $createLog;

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

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return $update ? $id : false;
    }

    public function getCampaignV2PaymentMethods($campaign_v2_id)
    {
        $sql = "select v.method_id, v.method_name from campaign_v2_payment_methods c left join vtex_payment_methods v on c.method_id=v.id where c.campaign_v2_id = ".$campaign_v2_id;
        $query = $this->db->query($sql);
        $this->db->query($sql);
        return $query->result_array();
    }

    public function getCampaignV2TradePolicies($campaign_v2_id)
    {
        $sql = "select v.trade_policy_id, v.trade_policy_name from campaign_v2_trade_policies c left join vtex_trade_policies v on c.trade_policy_id=v.id where c.campaign_v2_id = ".$campaign_v2_id;
        $query = $this->db->query($sql);
        $this->db->query($sql);
        return $query->result_array();
    }

    public function getCampaignsByCampaignId(int $campaign_v2_id): ?array
    {

        return $this->db->select('*')
            ->from($this->tableName)
            ->where('campaign_v2_id', $campaign_v2_id)
            ->get()
            ->result_array();

    }

    public function getLastCampaignByCampaignId(int $campaign_v2_id): ?array
    {

        return $this->db->select('*')
            ->from($this->tableName)
            ->where('campaign_v2_id', $campaign_v2_id)
            ->order_by('id', 'DESC')
            ->get()
            ->first_row('array');

    }

    public function getCampaignByCampaignIdDiscount(
        int $campaign_v2_id,
        string $discount_type,
        string $discount_value
    ): ?array {

        return $this->db->select('*')
            ->from($this->tableName)
            ->where('campaign_v2_id', $campaign_v2_id)
            ->where('discount_type', $discount_type)
            ->where('discount_value', $discount_value)
            ->get()
            ->result_array();

    }

    public function vtexCampaignIdExists(string $vtex_campaign_id): bool
    {

        $this->db->select("*");
        $this->db->from($this->tableName);
        $this->db->where('vtex_campaign_id', $vtex_campaign_id);

        $result = $this->db->get()->row_array();
        if (!$result) {
            return false;
        }

        return true;

    }

    public function vtexProductCampaignExists(string $vtex_campaign_id, string $skuMkt): bool
    {

        $this->db->select('count(prd_to_integration.skumkt) as total');
        $this->db->from('campaign_v2_vtex_campaigns');
        $this->db->join('campaign_v2_products',
            'campaign_v2_products.campaign_v2_id = campaign_v2_vtex_campaigns.campaign_v2_id');
        $this->db->join('prd_to_integration', 'campaign_v2_products.product_id = prd_to_integration.prd_id');
        $this->db->where('vtex_campaign_id', $vtex_campaign_id);
        $this->db->where('prd_to_integration.skumkt', $skuMkt);

        $result = $this->db->get()->row_array();

        return $result['total'] > 0;

    }

}
