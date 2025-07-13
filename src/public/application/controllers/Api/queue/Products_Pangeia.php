<?php 
/* 
* recebe a reuisição e cadastra / altera /inativa no Pangeia 
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_Pangeia extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Pangeia';
		$this->tradesPolicies = array('1');
		$this->adlink = 'https://shop.pangeia.eco/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;

    }
	
}