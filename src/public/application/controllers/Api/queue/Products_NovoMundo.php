<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_NovoMundo extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'NovoMundo';
		$this->tradesPolicies = array('1','2','3');
		$this->adlink = 'https://www.novomundo.com.br/';
		$this->auto_approve = true;
		$this->update_product_specifications = false;
		$this->update_sku_specifications = false;
		$this->update_sku_vtex = false; 
		$this->update_product_vtex = false;

    }
	
	
}