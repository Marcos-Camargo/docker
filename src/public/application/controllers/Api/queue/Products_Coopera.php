<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no Coopera / Sicoob
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_Coopera extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Coopera';
		$this->tradesPolicies = array('1');
		$this->ref_id = 'ONLYID';
		$this->adlink = 'https://www.shopcoopera.com.br/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;

    }
	
}