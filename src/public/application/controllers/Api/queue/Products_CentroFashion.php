<?php 
/* 
* recebe a reuisição e cadastra / altera /inativa no Polishop / Sicoob
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_CentroFashion extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'CentroFashion';
		$this->tradesPolicies = array('1');
		$this->adlink = 'https://www.centrofashion.com/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;
		$this->ref_id = 'FORCEREFID';  // vai sempre forçar o ref_id se não estiver preenchido
		$this->minimum_stock = 2; 
		

    }

}
