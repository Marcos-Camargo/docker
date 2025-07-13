<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsCAR.php";

class Products_CAR extends ProductsCAR {
	
   public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'CAR';

    }

}