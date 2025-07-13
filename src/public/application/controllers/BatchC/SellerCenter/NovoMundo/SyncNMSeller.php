<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/SyncSeller.php";

class SyncNMSeller extends SyncSeller {

    public function __construct()
    {
        parent::__construct();

        $this->setSuffixDns('.com');
    }

    protected function canInsertSeller($seller) {
        return (strpos($seller['Name'], 'novomundo') === false) && parent::canInsertSeller($seller);
    }

}