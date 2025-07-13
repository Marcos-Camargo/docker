<?php 

require APPPATH . "controllers/Api/queue/ProductsOcc.php";

class Products_Zema extends ProductsOcc {
	
     public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Zema';
    $this->adlink = 'https://a1594069c1tst-admin.occa.ocs.oraclecloud.com/ccadminui/v1/products';

    }
}