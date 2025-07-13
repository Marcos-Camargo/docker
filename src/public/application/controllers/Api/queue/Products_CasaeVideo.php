<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na Casa e Video
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_CasaeVideo extends ProductsVtexV2{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'CasaeVideo';
		$this->tradesPolicies = ENVIRONMENT === 'development' ? array('1') : array('1','2','11');
		$this->adlink = 'https://www.casaevideo.com.br/';
		$this->auto_approve = true;
		$this->update_product_specifications = true;
		$this->update_sku_specifications = true;
		$this->update_sku_vtex = true; 
		$this->update_product_vtex = true;
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