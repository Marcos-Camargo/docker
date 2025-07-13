<?php 
/* 
* 
 */
require APPPATH . "controllers/Api/queue/ProductsZOOM.php";
     
class Products_ZOOM extends ProductsZOOM{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'ZOOM';
    }
	
}