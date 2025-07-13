<?php

require APPPATH."controllers/BatchC/GenericBatch.php";

class CampaignsV2 extends GenericBatch
{
    private $gateway_name;
    private $gateway_id;


    public function __construct()
    {
        parent::__construct();

        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            exit('Feature disabled: oep-1443-campanhas-occ');
        }

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true
        );

        $this->session->set_userdata($logged_in_sess);

        $this->load->library('OccCampaigns');

    }

    public function updatePaymentMethods($marketplace = null)
    {
        $this->startJob(__FUNCTION__, null, $marketplace);

        $this->occcampaigns->updatePaymentMethods();

        $this->endJob();
    }

    public function updateCampaigns()
    {
        $this->startJob(__FUNCTION__, null, null);

        $this->occcampaigns->sincronizeCampaigns();

        $this->endJob();

    }

}