<?php 
/* 
* recebe a reuisição e cadastra / alterara /inativa na Casa e Video
 */
require APPPATH . "controllers/Api/queue/ProductsVtex.php";
     
class Products_Decathlon extends ProductsVtex{
	
	
    public function __construct() {
        parent::__construct();
	   
		$this->int_to = 'Decathlon';
		$this->tradesPolicies = array('3');
		$this->update_product_specifications = true;
		// $this->adlink = 'https://www.decathlon.com.br/';
		$this->auto_approve = true;

    }
	
	protected function beforeGetProductData($prd_id) {
		parent::beforeGetProductData($prd_id);

		$permiteVariacaoNosAtributos = $this->model_settings->getValueIfAtiveByName('permite_variacao_nos_atributos');
        if ($permiteVariacaoNosAtributos) {
			$variacaoCorDefault = $this->model_settings->getValueIfAtiveByName('variacao_cor_default');
			$variacaoTamanhoDefault = $this->model_settings->getValueIfAtiveByName('variacao_tamanho_default');
			$prd = $this->model_products->getProductData(0,$prd_id);
			if ($prd['has_variants'] == '') {
				$categoryId = json_decode($prd['category_id']);
				if (is_array($categoryId)) {
					$categoryId = $categoryId[0];
				}
				$category_mkt = $this->model_categorias_marketplaces->getCategoryMktplace($this->int_to,$categoryId);
				if ($variacaoCorDefault) {
					$atributoCat = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($this->int_to, $category_mkt['category_marketplace_id'], 'Cor');
					if (!is_null($atributoCat)) {
						$prodAttrCat = $this->model_atributos_categorias_marketplaces->getProductAttributeById($prd_id, $atributoCat['id_atributo']);
						if (is_null($prodAttrCat)) {
							if ($atributoCat['tipo'] == 'list') {
								$valores = json_decode($atributoCat['valor'], true);
								foreach($valores as $valor) {
									if ($valor['Value'] == $variacaoCorDefault) {
										$variacaoCorDefault = $valor['FieldValueId'];
										break;
									}
								}
							}
							$data_attribute_product = array(
								'id_product' => $prd_id,
								'id_atributo' => $atributoCat['id_atributo'],
								'valor' => $variacaoCorDefault,
								'int_to' => $this->int_to
							);
							$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
						}
					}
				}
				if ($variacaoTamanhoDefault) {
					$atributoCat = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($this->int_to, $category_mkt['category_marketplace_id'], 'Tamanho');
					if (!is_null($atributoCat)) {
						$prodAttrCat = $this->model_atributos_categorias_marketplaces->getProductAttributeById($prd_id, $atributoCat['id_atributo']);
						if (is_null($prodAttrCat)) {
							if ($atributoCat['tipo'] == 'list') {
								$valores = json_decode($atributoCat['valor'], true);
								foreach($valores as $valor) {
									if ($valor['Value'] == $variacaoTamanhoDefault) {
										$variacaoTamanhoDefault = $valor['FieldValueId'];
										break;
									}
								}
							}
							$data_attribute_product = array(
								'id_product' => $prd_id,
								'id_atributo' => $atributoCat['id_atributo'],
								'valor' => $variacaoTamanhoDefault,
								'int_to' => $this->int_to
							);
							$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
						}
					}
				}
			}
		}

		return ;
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