<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na Decathlon
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_Mesbla extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Mesbla';
		$this->tradesPolicies = array('1');
		$this->adlink = 'https://www.mesbla.com/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;
		$this->minimum_stock = 2;

    }

}