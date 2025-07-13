<?php

/**
 * @property Model_brands $model_brands
 * @property Model_products $model_products
 * @property Model_products_catalog $model_products_catalog
 */
class Model_brands_marketplaces extends CI_Model
{
	const TABLE = 'brands_marketplaces';
	public function __construct()
	{
		parent::__construct();
		$this->load->model('model_brands');
        $this->load->model('model_products');
        $this->load->model('model_products_catalog');
		
	}

	/* get active brand infromation */
	public function getDataByBrandId($brand_id)
	{
		$sql = "SELECT * FROM brands_marketplaces WHERE brand_id = ?";
		$query = $this->db->query($sql, array($brand_id));
		return $query->result_array();
	}
	public function findByBrandIdAndIntTo($brandId, $intTo)
	{
		return $this->db->select()->from(self::TABLE)->where(['int_to' => $intTo, 'brand_id' => $brandId])->get()->row_array();
	}

	public function getAllBransByMarketplace($int_to)
	{
		$sql = "SELECT * FROM brands_marketplaces WHERE int_to = ? ORDER BY name";
		$query = $this->db->query($sql, array($int_to));
		return $query->result_array();
	}

	public function getBrandMktplace($int_to, $brand_id)
	{
		$sql = "SELECT * FROM brands_marketplaces WHERE int_to = ? AND brand_id = ?";
		$query = $this->db->query($sql, array($int_to, $brand_id));
		return $query->row_array();
	}

	public function getBrandMktplaceByName($int_to, $name)
	{
		$sql = "SELECT * FROM brands_marketplaces WHERE int_to = ? AND name = ?";
		$query = $this->db->query($sql, array($int_to, $name));
		return $query->row_array();
	}

	public function replace($data)
	{
		if ($data) {
			$replace = $this->db->replace('brands_marketplaces', $data);
			return ($replace == true) ? true : false;
		}
		return false;
	}

	public function create($data)
	{
		if ($data) {
			$insert = $this->db->insert('brands_marketplaces', $data);
			return ($insert == true) ? true : false;
		}
		return false;
	}

	public function update($data, $int_to, $brand_id)
	{
		if ($data && $int_to && $brand_id) {
			$this->db->where('int_to', $int_to);
			$this->db->where('brand_id', $brand_id);
			$update = $this->db->update('brands_marketplaces', $data);
			return ($update == true) ? true : false;
		}
		return false;
	}

	public function remove($int_to, $brand_id)
	{
		if ($int_to && $brand_id) {
			$this->db->where('int_to', $int_to);
			$this->db->where('brand_id', $brand_id);
			$delete = $this->db->delete('brands_marketplaces');
			return ($delete == true) ? true : false;
		}
		return false;
	}

	public function createOrUpdateIfChanged($data)
	{
		$int_to = $data['int_to'];
		$brand_id = $data['brand_id'];
		$brand = $this->getBrandMktplace($data['int_to'], $data['brand_id']);
		if (!$brand) { // não existe, crio
			$data['integrated'] = true;
			return $this->create($data);
		}
		unset($brand['date_create']);
		unset($brand['date_update']);
		unset($brand['integrated']);
		if ($brand == $data) {
			return $this->update(array('integrated' => true), $data['int_to'], $data['brand_id']);
			return true;
		} else {
			$data['integrated'] = true;
			return $this->update($data, $data['int_to'], $data['brand_id']);
		}
	}

	public function setAllNotIntegrated($int_to)
	{
		$this->db->where('int_to', $int_to);
		$update = $this->db->update('brands_marketplaces', array('integrated' => false));
		return ($update == true) ? true : false;
	}

	public function removeAllNotIntegrated($int_to)
	{
		$this->db->where('integrated', false);
		$delete = $this->db->delete('brands_marketplaces');
		return ($delete == true) ? true : false;
	}

	public function setBrandsActiveOrInactive(bool $inactive_products = false, int $catalog_id = null)
	{
		/*$sql = 'UPDATE brands SET active = 2 WHERE id NOT IN (SELECT brand_id FROM brands_marketplaces WHERE isActive is true)';
		$this->db->query($sql);

		$sql = 'UPDATE brands SET active = 1 WHERE id IN (SELECT brand_id FROM brands_marketplaces WHERE isActive is true)';
		$this->db->query($sql);
		*/

		$offset = 0 ;
		$limit = 50000; 
		while(true) {
			$sql = 'SELECT * FROM brands LIMIT '.$limit.' OFFSET '.$offset; 
			$query = $this->db->query($sql);
			$brands = $query->result_array();
			if (!$brands) {
				break;
			}
			foreach($brands as $brand) {
				// echo $brand['id']." - ".$brand['name']." ".$brand['active']."\n";
				$brand_marketplace = $this->getDataByBrandId($brand['id']); 
				if (count($brand_marketplace) == 0) {  // não achou 
					if ($brand['active'] == 1) { // e está ativo
						echo "Inativando ".$brand['name']." - ".$brand['name']."\n"; 						
						$this->model_brands->update(array('active' => 2), $brand['id']); // inativa;
					}
				} else { 
					$brand_marketplace = $brand_marketplace[0];					
					$bm_active = $brand_marketplace ['isActive'] ? 1 : 2;  
					if ($brand['active'] != $bm_active) { // está difente 
						echo "Trocando ".$brand['name']." - ".$brand['name']." de ".$brand['active']." para ".$bm_active."\n";
						$this->model_brands->update(array('active' => $bm_active), $brand['id']); // atualiza

                        // Marca inativa
                        if ($inactive_products && $catalog_id && $bm_active == 2) {
                            $last_id = 0;
                            while (true) {
                                $products = $this->model_products_catalog->getListByBrandAndCatalog($brand['id'], $catalog_id, $last_id);

                                if (empty($products)) {
                                    break;
                                }

                                foreach ($products as $product) {
                                    $last_id = $product['id'];

                                    $this->model_products->updateActiveByProductCatalogId($product['id'], array('status' => Model_products::INACTIVE_PRODUCT));
                                }
                            }
                        }
					}
				}
			}
			$offset += $limit; 
		}
	}

	public function getDontSend($int_to)
	{
		$sql = 'SELECT * FROM brands_marketplaces WHERE integrated = 0 AND int_to=?';
		$query = $this->db->query($sql, array($int_to));
		return $query->result_array();
	}

	public function getSent()
	{
		$sql = 'SELECT * FROM brands_marketplaces WHERE integrated = 1';
		$query = $this->db->query($sql);
		return $query->result_array();
	}

    public function getBrandsDontSent($int_to)
    {
        $sql = 'SELECT * FROM brands WHERE id NOT IN (SELECT brand_id FROM brands_marketplaces where int_to = ?)';
        $query = $this->db->query($sql, array($int_to));
        return $query->result_array();
    }

	public function getDataByBrandIdMarketplace($brand_id, $int_to)
	{
		$sql = "SELECT * FROM brands_marketplaces WHERE id_marketplace = ? AND int_to = ?";
		$query = $this->db->query($sql, array($brand_id, $int_to));
		return $query->result_array();
	}
}
