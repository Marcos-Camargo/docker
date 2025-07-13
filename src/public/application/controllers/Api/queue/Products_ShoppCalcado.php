<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na Shopping do Calcado
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_ShoppCalcado extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'ShoppCalcado';
		$this->tradesPolicies = array('1');
		// $this->adlink = 'https://www.shoppingdocalcado.com.br/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;
		$this->ref_id = 'FORCEREFID';  // vai sempre forçar o ref_id se não estiver preenchido
		
    }
	
	protected function getInformacoesTecnicas($attributesCustomProduct) {
		$html = '';
		foreach($attributesCustomProduct as $attributeCustomProduct) {
			$html .= '<div class="technical-item">
				<h3 class="technical-title">'.$attributeCustomProduct['name_attr'].'</h3>
				<p class="technical-description">'.$attributeCustomProduct['value_attr'].'</p>
			</div>
			<hr>';
		}
		return $html;
	}
}