<?php 
/* 
* 
 */
require APPPATH . "controllers/Api/queue/ProductsML.php";
     
class Products_MLC extends ProductsML{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'MLC';
		$this->int_to_principal = 'ML';
		$this->tipo_anuncio = "gold_special";
    }
	
	
}