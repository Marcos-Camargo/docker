<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no Shop Coopera 
 */
require APPPATH . "controllers/Api/queue/ProductsConectalaSellerCenter.php";


class Products_CO extends ProductsConectalaSellerCenter {
	public function __construct() {
		parent::__construct();
		$this->int_to = 'CO';
		$this->int_to_SC = 'Coopera';
		$this->hasAuction = FALSE;
		$this->reserve_to_b2W = 0;  
    }
}