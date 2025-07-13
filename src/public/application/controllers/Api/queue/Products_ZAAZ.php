<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na lojasZaas
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_ZAAZ extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'ZAAZ';
		$this->$adlink = 'https://www.lojazaaz.com.br/'; 
		$this->vtex_conectala = true;
    }
	
	
}