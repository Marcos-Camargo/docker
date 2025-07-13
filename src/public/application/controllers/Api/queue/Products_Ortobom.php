<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa no NovoMundo
 */
require APPPATH . "controllers/Api/queue/ProductsVtexV2.php";
     
class Products_Ortobom extends ProductsVtexV2 {
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Ortobom';
		$this->tradesPolicies = array('1');
		$this->ref_id = 'ONLYID';
		$this->adlink = 'https://www.ortobomshop.com.br/';
		$this->update_product_specifications = true;  		// altera os atributos do produto 
		$this->update_sku_specifications = true;  			// altera os atributos do SKU ( variações ) 
        $this->auto_approve = true;
		$this->update_sku_vtex = true;
		$this->update_product_vtex = true;

    }
    
    protected function getCrossDocking($prd) {
        $categoryId = json_decode($prd['category_id']);
        $category   = $this->model_category->getCategoryData($categoryId);
        if (!is_null($category['days_cross_docking'])) {
            return $category['days_cross_docking'];
        }
        else {
            return parent::getCrossDocking($prd);
        }
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