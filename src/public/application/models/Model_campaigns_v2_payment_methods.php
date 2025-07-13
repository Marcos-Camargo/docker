<?php

class Model_campaigns_v2_payment_methods extends CI_Model
{
    private $tableName = 'campaign_v2_payment_methods';
    private $createLog;

    public static $paymentMethodsByCampaignId = array();

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

    public function relatePaymenMethodToCampaignV2(array $payment_method_array)
    {
        $sql = "
            insert into campaign_v2_payment_methods (campaign_v2_id, method_id) values (
                ".$payment_method_array['campaign_v2_id'].",
                (select id from vtex_payment_methods where int_to = '".$payment_method_array['int_to']."' and method_id = ".$payment_method_array['method_id'].")
            )
            ";

        return $this->db->query($sql);
    }

    public function getCampaignV2PaymentMethods($campaign_v2_id)
    {

        if (isset(self::$paymentMethodsByCampaignId[$campaign_v2_id])) {
            return self::$paymentMethodsByCampaignId[$campaign_v2_id];
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $sql = "select v.method_id, v.method_name 
                    from campaign_v2_payment_methods c 
                        left join vtex_payment_methods v on c.method_id=v.id 
                    where c.campaign_v2_id = ".$campaign_v2_id;
        }else{
            //@todo pode remover
            $sql = "select v.method_id, v.method_name from campaign_v2_payment_methods c left join vtex_payment_methods v on c.method_id=v.id where c.campaign_v2_id = ".$campaign_v2_id;
        }
        $query = $this->db->query($sql);
        $this->db->query($sql);

        return self::$paymentMethodsByCampaignId[$campaign_v2_id] = $query->result_array();

    }

    public function getIntToFromCampaign($campaign_v2_id)
    {
        $sql = "select distinct v.int_to 
                FROM vtex_payment_methods v 
                INNER JOIN campaign_v2_payment_methods p ON v.id=p.method_id AND p.campaign_v2_id = ".$campaign_v2_id;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

}
