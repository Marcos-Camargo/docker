<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na Decathlon
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_Decathlon extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Decathlon';
		$this->tradesPolicies = array('3','6', '8');
		$this->adlink = 'https://www.decathlon.com.br/';
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
			$html .= '<div class="technical-item">';
			$html .= '<h3 class="technical-title">'.$attributeCustomProduct['name_attr'].'</h3>';
			$html .= '<p class="technical-description">'.$attributeCustomProduct['value_attr'].'</p>';
			$html .= '</div>';
			$html .= '<hr>';
		}
		return $html;
	}
}