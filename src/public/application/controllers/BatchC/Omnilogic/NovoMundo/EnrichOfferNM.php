<?php
/*
 * 
*/  

require APPPATH . "controllers/BatchC/Omnilogic/EnrichOffer.php";
require APPPATH . "libraries/Omnilogic.php";

class EnrichOfferNM extends EnrichOffer {
		
	const INT_TO = 'NovoMundo';

	public function __construct()
	{
		parent::__construct();
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
	}
	
	protected function enrichCategory($offer, $enrichment) {
		$category_mkt_id = $this->getCategoryEnrich($enrichment);

		$category = $this->model_categorias_marketplaces->getDataByCategoryMktId(self::INT_TO, $category_mkt_id);
		
		if (is_null($category['category_id'])) {
			return false;
		}
		$category_id_json = json_encode([$category['category_id']]);
		$data = array('category_id' => $category_id_json);
		
        $this->db->where('id', $offer['seller_offer_id']);
		
		return $this->db->update('products', $data);
	}

    protected function enrichAttributes($offer, $enrichment) {
		$category_mkt_id = $this->getCategoryEnrich($enrichment);
		$attributes_mkt = $this->model_atributos_categorias_marketplaces->getAttributesByCategoryMkt(self::INT_TO, $category_mkt_id);

		if (array_key_exists('metadata', $enrichment)) {
			foreach ($enrichment['metadata'] as $key => $metadata) {
				$attribute = $this->existAttribute($key, $attributes_mkt);
				if ($attribute !== false) {
					if ($attribute['variacao'] == false) {
						$attribute_mkt = $this->makeAttribute($offer['seller_offer_id'], $attribute, $metadata);
						if ($attribute_mkt !== false) {
							$this->saveAttribute($attribute_mkt);
						}
					}
				}
			}
		}
	}

	private function existAttribute($attribute_omnilogic, $attributes_mkt) {
		$attribute = false;

		foreach ($attributes_mkt as $attribute_mkt) {
			if (strtoupper($attribute_omnilogic) == strtoupper($attribute_mkt['nome'])) {
				$attribute = $attribute_mkt;
			}
		}

		return $attribute;
	}

	private function makeAttribute($product_id, $attribute_mkt, $value) {
		$attribute = array(
			"int_to" => self::INT_TO,
			"id_product" => $product_id,
			"id_atributo" => $attribute_mkt['id_atributo'],
			"valor" => $value
		);

		$values = json_decode($attribute_mkt['valor']);
		if (count($values) > 0) {
			$found_value = false;
			foreach ($values as $attribute_value) {
				$attr_value = $this->formatAttribute($attribute_value->Value);
				if (strtoupper($attr_value) == strtoupper(trim($value))) {
					$found_value = $attr_value;
				}
			}

			if  ($found_value === false) {
				return false;
			}
			else {
				$attribute['valor'] = $found_value;
			}
		}

		return $attribute;
	}

	private function formatAttribute($attr) {
		$result = trim($attr);
		return preg_replace('/^- /', '', $result);
	}

	private function saveAttribute($attribute) {
		if ($this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($attribute['id_product'], $attribute['id_atributo'], $attribute['int_to'])) {
			$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($attribute);
		}
	}
}
?>
