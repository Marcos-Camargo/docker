<?php 
/* 
* 
 */
require APPPATH . "controllers/Api/queue/ProductsML.php";
     
class Products_H_MLC extends ProductsML{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'H_MLC';
		$this->int_to_principal = 'ML';
		$this->tipo_anuncio = "gold_special";
		$this->reserve_to_b2W = 0;
    }
	
	
}