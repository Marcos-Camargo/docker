<?php 
/* 
* recebe a requisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_Reserva extends ProductsVtexV2{
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Reserva';
		$this->tradesPolicies = array('1');
		$this->adlink = 'https://usereserva.com/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;

    }
	
}