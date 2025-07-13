<?php 
/* 
* 
 */
require APPPATH . "controllers/Api/queue/ProductsMAD.php";
     
class Products_MAD extends ProductsMAD{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'MAD';
    }
	
	
}