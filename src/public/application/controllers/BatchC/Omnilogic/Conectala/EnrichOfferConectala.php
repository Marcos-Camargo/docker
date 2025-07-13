<?php
/*
 * 
*/  

require APPPATH . "controllers/BatchC/Omnilogic/EnrichOffer.php";
require APPPATH . "libraries/Omnilogic.php";

class EnrichOfferConectala extends EnrichOffer {
		
	public function __construct()
	{
		parent::__construct();
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_products_category_mkt');
		
	}
	
	protected function getChannels() { return Omnilogic::channel_conectala(); }

	protected function enrichCategory($offer, $enrichment) {
		$category_mkt_id = $this->getCategoryEnrich($enrichment);

		$int_to = false;
		foreach (Omnilogic::channel_int_to() as $channel_int_to) {
			if ($offer['channel'] == $channel_int_to['channel']) {
				$int_to = $channel_int_to['int_to'];
			}
		} 

		if ($int_to !== false) {
			$category = $this->model_products_category_mkt->getCategoryEnriched($int_to, $enrichment['seller_offer_id']);
			if (is_null($category)) {
				$data = array(
					'prd_id' => $enrichment['seller_offer_id'],
					'int_to' => $int_to,
					'category_mkt_id' => $category_mkt_id
				);
				return $this->model_products_category_mkt->replace($data);
			} 
			else {
				if ($category['category_mkt_id'] != $category_mkt_id) {
					$category['category_mkt_id'] = $category_mkt_id;
					return $this->model_products_category_mkt->replace($category);
				}
				else 
					return true;
			}
		}
		return null;
	}

    protected function enrichAttributes($offer, $enrichment) {
		$int_to = false;
		foreach (Omnilogic::channel_int_to() as $channel_int_to) {
			if ($offer['channel'] == $channel_int_to['channel']) {
				$int_to = $channel_int_to['int_to'];
			}
		} 

		$category_mkt_id = $this->getCategoryEnrich($enrichment);
		$attributes_mkt = $this->model_atributos_categorias_marketplaces->getAttributesByCategoryMkt($int_to, $category_mkt_id);

		if (!is_null($category_mkt_id)) {
			if (array_key_exists('metadata', $enrichment)) {
				foreach ($enrichment['metadata'] as $key => $metadata) {
					$attribute = false;

					if ($offer['channel'] == Omnilogic::channel_mercado_livre()) {
						$attribute = $this->existAttribute($key, $attributes_mkt);
					}

					if ($attribute !== false) {
						if ($attribute['variacao'] == false) {
							$attribute_mkt = $this->makeAttribute($int_to, $offer['seller_offer_id'], $attribute, $metadata);
							if ($attribute_mkt !== false) {
								$this->saveAttribute($attribute_mkt);
							}
						}
						else {
							// $this->model_products->
						}
					}
					else {
						$data = array(
							'prd_id' => $offer['seller_offer_id'],
							'int_to' => $int_to,
							'attribute' => $key,
							'value' => $metadata
						);
						$this->model_omnilogic->insertAtributeNotFound($data);
					}
				}
			}
		}
	}
	
	private function existAttributeML($attribute_omnilogic, $attributes_mkt) {
		$attribute = false;

		foreach ($attributes_mkt as $attribute_mkt) {
			if (strtoupper($attribute_omnilogic) == strtoupper($attribute_mkt['nome'])) {
				$attribute = $attribute_mkt;
			}
			else if (strtoupper($attribute_omnilogic) == strtoupper('Marca')) {
				if (in_array(strtoupper($attribute_mkt['id_atributo']), ['BRAND'])) {
					$attribute = $attribute_mkt;
				}
			}
			else if (strtoupper($attribute_omnilogic) == strtoupper('Quantidade')) {
				if (in_array(strtoupper($attribute_mkt['id_atributo']), ['PIECES_NUMBER'])) {
					$attribute = $attribute_mkt;
				} else {
					echo '';
				}
			}
			else if (strtoupper($attribute_omnilogic) == strtoupper($attribute_mkt['nome'])) {
				$attribute = $attribute_mkt;
			}
		}
		return $attribute;
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

	private function makeAttribute($int_to, $product_id, $attribute_mkt, $value) {
		$attribute = array(
			"int_to" => $int_to,
			"id_product" => $product_id,
			"id_atributo" => $attribute_mkt['id_atributo'],
			"valor" => $value
		);

		if (is_array($attribute_mkt['valor'])) {
			if (count($attribute_mkt['valor']) > 0) {
				$found_value = false;
				foreach ($attribute_mkt['valor'] as $attribute_value) {
					if (strtoupper(trim($attribute_value)) == strtoupper(trim($value))) {
						$found_value = $attribute_value;
					}
				}
				

				if  ($found_value === false) {
					return false;
				}
				else {
					$attribute['valor'] = $found_value;
				}
			}
		}

		return $attribute;
	}

	private function saveAttribute($attribute) {
		$exists = $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($attribute['id_product'], $attribute['id_atributo'], $attribute['int_to']);
		if (is_null($exists)) {
			$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($attribute);
		}
	}
}
?>
