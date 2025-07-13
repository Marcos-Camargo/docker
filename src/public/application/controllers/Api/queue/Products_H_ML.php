<?php 
/* 
* 
 */
require APPPATH . "controllers/Api/queue/ProductsML.php";
     
class Products_H_ML extends ProductsML{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'H_ML';
		$this->int_to_principal = 'ML';
		$this->tipo_anuncio = "gold_pro";
		$this->reserve_to_b2W = 0;
    }
	
	
}