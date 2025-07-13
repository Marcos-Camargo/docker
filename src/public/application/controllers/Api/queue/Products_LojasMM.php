<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na Lojas MM
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_LojasMM extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'LojasMM';
		$this->tradesPolicies = array('1');
		// $this->adlink = 'https://www.lojasmm.com.br/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;

    }
	
}