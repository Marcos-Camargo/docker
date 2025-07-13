<?php 

class Model_products_modified extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('products_modified', $data);
          
            return ($insert == true) ? true : false;
        }
        return false;
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);

            $update = $this->db->update('products_modified', $data);

            return ($update == true) ? true : false;
        }
    }

    public function deleteProductsModified($id, $store_id)
    {
        if ($id && $store_id) {
            $this->db->where('sku', $id);
            $this->db->where('store_id', $store_id);
            $query = $this->db->get('products_modified');

            // Se o registro existir, procede com a exclusão
            if ($query->num_rows() > 0) {
                return $this->db->delete('products_modified', array('sku' => $id));
            }
            return false;
        }
        return false;
    }

    public function getByProductId($prd_id)
    {
        $this->db->select('*');
        $this->db->from('products_modified');
        $this->db->where('prd_id', $prd_id);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row_array(); 
        } else {
            return false; 
        }
    }

    public function getProductsModifiedByStore($store_id, $offset = 0, $limit = null, $sku = null, $filters = array(), $return_count = false, $order_by = array('id', 'DESC'))
    {
        // Seleciona os campos desejados da tabela products e products_modified
        $this->db->select($return_count ? 'p.id' : 'p.*, c.name as category_name, b.name as brand_name', );
    
        // Filtro por store_id
        if (!empty($store_id)) {
            $this->db->where('p.store_id', $store_id);
        }
    
        // Filtro por SKU
        if (!is_null($sku)) {
            $this->db->where('p.sku', $sku);
        }
    
        // Filtros adicionais
        foreach ($filters as $filter_key => $filter_value) {
            if (is_null($filter_value)) {
                continue;
            }
            $this->db->where($filter_key, $filter_value);
        }
    
        // Faz o JOIN com a tabela products_modified
        $this->db->join('products_modified pm', 'pm.prd_id = p.id AND pm.store_id = p.store_id', 'inner');

        $this->db->join('categories as c', 'c.id = left(substr(p.category_id,3),length(p.category_id)-4)', 'left');
        $this->db->join('brands as b', 'b.id = left(substr(p.brand_id,3),length(p.brand_id)-4)', 'left');
    
        // Limita os resultados
        if (!is_null($limit)) {
            $this->db->limit($limit, $offset);
        }
    
        // Ordena os resultados
        $this->db->order_by($order_by[0], $order_by[1]);
        
        if ($return_count) {
            return $this->db->get('products p')->num_rows();
        }
    
        if (!is_null($sku)) {
            return $this->db->get('products p')->row_array();
        }
    
        return $this->db->get('products p')->result_array();
    }
    
}