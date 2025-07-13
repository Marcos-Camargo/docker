<?php 

require APPPATH . "controllers/Api/queue/ProductsB2W.php";

class Products_B2W extends ProductsB2W {
	
     public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'B2W';

    }
}