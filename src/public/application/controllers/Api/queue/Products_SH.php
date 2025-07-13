<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no ShopHub - SH Vtex 
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";

class Products_SH extends ProductsVtexV2{
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'SH';
		$this->tradesPolicies = array('1');
		$this->adlink = 'https://www.shophub.com.br/';
		$this->update_product_specifications = true;
		$this->skumkt_seller = true; 
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;

    }

	public function index_post() 
    {
    	$this->inicio = microtime(true);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();
			
		// Acabou a importação, retiro da fila 
		$this->RemoveFromQueue();

		$fim= microtime(true);
		echo "\nExecutou em: ". ($fim-$this->inicio)*1000 ." ms\n";
		return;
    } 
	
}