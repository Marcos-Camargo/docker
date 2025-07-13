<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na On Stores
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_OST extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'OST';
		//$this->tradesPolicies = array('1');
		//$this->update_product_specifications = true;
		$this->$adlink = 'https://www.onstores.com.br/'; 
		$this->vtex_conectala = true;
    }
	
	
}