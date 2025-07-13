<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no Shop Coopera 
 */
require APPPATH . "controllers/Api/queue/ProductsConectalaSellerCenter.php";


class Products_Default_Conectala extends ProductsConectalaSellerCenter {
	public function __construct() {
        
       parent::__construct();
       $data = json_decode($this->input->raw_input_stream, true);
       $this->int_to = $data['int_to'];
       $this->int_to_SC = $data['int_to'];
       $this->hasAuction = FALSE;
       $this->reserve_to_b2W = 0;
        
    }
}