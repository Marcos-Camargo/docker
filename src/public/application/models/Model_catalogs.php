<?php
/*
 
 Model de Acesso ao BD para Catalogos de Produtos
 
 */

class Model_catalogs extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($data = '', $stores = null)
    {
        if ($data) {
            $create = $this->db->insert('catalogs', $data);
            $catalog_id = $this->db->insert_id();
            if (is_array($stores)) {
                foreach ($stores as $store_id) {
                    $catalog_stores_data = array(
                        'catalog_id' => $catalog_id,
                        'store_id' => $store_id
                    );
                    $group_data = $this->db->insert('catalogs_stores', $catalog_stores_data);
                }
            }
            $data['id'] = $catalog_id;
            $data['stores'] = $stores;
            get_instance()->log_data('Catalogs', 'create', json_encode($data), "I'");
            return ($create) ? $catalog_id : false;
        }
        return false;
    }

    public function update($data, $catalog_id, $stores)
    {
        if ($data && $catalog_id) {
            $this->db->where('id', $catalog_id);
            $update = $this->db->update('catalogs', $data);
            if (is_array($stores)) {
                if (!empty($stores)) { // limpam os que não existem
                    $str = implode(",", $stores);
                    $sql = 'DELETE FROM catalogs_stores WHERE catalog_id=? AND store_id NOT IN (' . $str . ')';
                    $query = $this->db->query($sql, array($catalog_id));
                } else {
                    $sql = 'DELETE FROM catalogs_stores WHERE catalog_id=?';
                    $query = $this->db->query($sql, array($catalog_id));
                }
                foreach ($stores as $store_id) {
                    $sql = 'SELECT * FROM catalogs_stores WHERE catalog_id=? AND store_id=?';
                    $query = $this->db->query($sql, array($catalog_id, $store_id));
                    $exist = $query->row_array();
                    if (!$exist) { // adiciona os faltosos
                        $catalog_stores_data = array(
                            'catalog_id' => $catalog_id,
                            'store_id' => $store_id
                        );
                        $group_data = $this->db->insert('catalogs_stores', $catalog_stores_data);
                    }
                }
            } else {
                $sql = 'DELETE FROM catalogs_stores WHERE catalog_id=?';
                $query = $this->db->query($sql, array($catalog_id));
            }
            $data['id'] = $catalog_id;
            $data['stores'] = $stores;
            get_instance()->log_data('Catalogs', 'edit_after', json_encode($data), "I'");
            return ($update == true) ? $catalog_id : false;
        }
        return false;
    }

    public function updateById($data, $catalog_id)
    {
        if ($data && $catalog_id) {
            $this->db->where('id', $catalog_id);
            $update = $this->db->update('catalogs', $data);
            get_instance()->log_data('Catalogs', 'edit_after', json_encode($data), "I'");
            return $update ? $catalog_id : false;
        }
        return false;
    }

    public function getCatalogData($id = null)
    {
        if ($id) {
            if (is_array($id)) {
                return $this->db->where_in('id', $id)->get('catalogs')->result_array();
            }

            return $this->db->where('id', $id)->get('catalogs')->row_array();
        }
        return false;
    }

    public function getCatalogsStoresDataByCatalogId($catalog_id = null)
    {
        if ($catalog_id) {
            $sql = "SELECT * FROM catalogs_stores WHERE catalog_id = ?";
            $query = $this->db->query($sql, array($catalog_id));
            return $query->result_array();
        }
        return false;
    }

    public function getStoresInCatalogs() {
        $sql = 'select 
                c.id as catalog_id, 
                c.name as catalog_name, 
                c.int_to as catalog_code, 
                s.id as store_id, 
                s.name as store_name 
            from catalogs c
                join catalogs_stores cs on cs.catalog_id = c.id 
                join stores s on s.id = cs.store_id 
            where c.status = 1';
            
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCatalogsDataView($offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if ($procura != '') {
            $procura = 'WHERE ' . substr($procura, 4);
        }

        $sql = "SELECT * FROM catalogs " . $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;;
        //$this->session->set_flashdata('error', $sql);
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCatalogsDataCount($procura = '')
    {

        if ($procura != '') {
            $procura = 'WHERE ' . substr($procura, 4);
        }
        $sql = "SELECT count(*) as qtd FROM catalogs " . $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getStoresOnCatalogs()
    {
        $sql = "SELECT DISTINCT store_id as id, s.name  FROM catalogs_stores c, stores s WHERE c.store_id=s.id ORDER by s.name ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getActiveCatalogs()
    {
        $sql = "SELECT * FROM catalogs WHERE status=1 ORDER by name ";
        $query = $this->db->query($sql);
        return $query->result_array();

    }

    public function getCatalogsStoresDataByStoreId($store_id = null)
    {
        if ($store_id) {
            $sql = "SELECT * FROM catalogs_stores WHERE store_id = ?";
            $query = $this->db->query($sql, array($store_id));
            return $query->result_array();
        }
        return false;
    }

    public function setCatalogsStoresDataByStoreId($store_id, $catalogs)
    {
        if (is_array($catalogs)) {
            if (!empty($catalogs)) { // limpam os que não existem
                $str = implode(",", $catalogs);
                $sql = 'DELETE FROM catalogs_stores WHERE store_id=? AND catalog_id NOT IN (' . $str . ')';
                $query = $this->db->query($sql, array($store_id));
            } else {
                $sql = 'DELETE FROM catalogs_stores WHERE store_id=?';
                $query = $this->db->query($sql, array($store_id));
            }
            foreach ($catalogs as $catalog_id) {
                $sql = 'SELECT * FROM catalogs_stores WHERE catalog_id=? AND store_id=?';
                $query = $this->db->query($sql, array($catalog_id, $store_id));
                $exist = $query->row_array();
                if (!$exist) { // adiciona os faltosos
                    $catalog_stores_data = array(
                        'catalog_id' => $catalog_id,
                        'store_id' => $store_id
                    );
                    $group_data = $this->db->insert('catalogs_stores', $catalog_stores_data);
                }
            }
        } else {
            $sql = 'DELETE FROM catalogs_stores WHERE store_id=?';
            $query = $this->db->query($sql, array($store_id));
        }

    }

    public function getMyCatalogs()
    {
        if ($this->data['usercomp'] == 1) { // administrador le todos os catalogos ativos
            $sql = "SELECT * FROM catalogs WHERE status=1 ORDER by name ";
            $query = $this->db->query($sql);
            return $query->result_array();
        }
        if (($this->data['userstore'] == 0)) { // pego todos os catálogos das minhas lojas
            $sql = "SELECT * FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id IN (SELECT id FROM stores WHERE company_id = ?)) ORDER by name ";
            $query = $this->db->query($sql, $this->data['usercomp']);
            return $query->result_array();
        }
        // Pego o catálogo da minha loja apenas
        $sql = "SELECT * FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id = ?) ORDER by name ";
        $query = $this->db->query($sql, $this->data['userstore']);
        return $query->result_array();
    }

    public function clearVtexCatalogs($int_to)
    {
        $this->db->where('int_to', $int_to);
        $delete = $this->db->delete('vtex_get_catalog');
        return ($delete == true) ? true : false;
    }

    public function createVtexCatalogs($data)
    {
        $create = $this->db->replace('vtex_get_catalog', $data);
        return ($create) ? true : false;
    }

    public function getProductsFromVtexCatalog(int $status, string $int_to, int $first_product_id = null, int $last_product_id = null): array
    {
        $this->db->where(
            array(
                'status_int' => $status,
                'int_to' => $int_to
            )
        );

        if (!is_null($first_product_id) && !is_null($last_product_id)) {
            $this->db->where('product_id >', $first_product_id)
                ->where('product_id <', $last_product_id);
        }

        return $this->db
            ->order_by('product_id', 'ASC')
            ->get('vtex_get_catalog')
            ->result_array();
    }

    public function getProductFromVtexCatalogByRefid($ref_id, $int_to)
    {
        $sql = "SELECT * FROM vtex_get_catalog WHERE ref_id = ? AND int_to = ?";
        $query = $this->db->query($sql, array($ref_id, $int_to));
        return $query->row_array();
    }

    public function updateProductFromVtexCatalog($data, $product_id, $int_to)
    {
        $this->db->where('int_to', $int_to)->where('product_id', $product_id);
        $update = $this->db->update('vtex_get_catalog', $data);

        return ($update == true) ? true : false;

        //$sql   = "UPDATE vtex_get_catalog SET status_int = ? WHERE int_to = ? AND product_id = ?";
        //	$query = $this->db->query($sql, array($status, $int_to, $product_id));
        //  return $query;

    }

    public function removeProductFromVtexCatalog($product_id, $int_to)
    {
        $sql = "DELETE FROM vtex_get_catalog WHERE int_to = ? AND product_id = ?";
        $query = $this->db->query($sql, array($product_id, $int_to));
    }

    public function getCatalogByName($name)
    {
        $sql = "SELECT * FROM catalogs WHERE name = ?";
        $query = $this->db->query($sql, array($name));

        return $query->row_array();
    }

    public function getActiveCatalogsStoresDataByCatalogId($catalog_id)
    {
        if ($catalog_id) {
            $sql = "SELECT c.*, s.company_id as company_id FROM catalogs_stores c, stores s WHERE catalog_id = ? AND s.id=c.store_id AND s.active = 1";
            $query = $this->db->query($sql, array($catalog_id));
            return $query->result_array();
        }
        return false;
    }

    public function getActiveCatalogsStoresDataByCatalogIdUpdate($catalog_id, $find_date)
    {
        if ($catalog_id) {
            $sql = "SELECT c.*, s.company_id as company_id FROM catalogs_stores c, stores s WHERE catalog_id = ? AND s.id=c.store_id AND s.active = 1 AND c.date_update > ?";
            $query = $this->db->query($sql, array($catalog_id, $find_date));
            return $query->result_array();
        }
        return false;
    }

    public function verifyStoreOnCatalog($catalog_id, $store_id)
    {
        $sql = "SELECT * FROM catalogs_stores WHERE catalog_id= ? AND store_id = ? ";
        $query = $this->db->query($sql, array($catalog_id, $store_id));
        return $query->row_array();
    }

    public function getExportCatalogsByStore()
    {
        if ($this->data['usercomp'] == 1) $more = "";
        elseif ($this->data['userstore'] == 0) {

            $arrStores = array();

            $sql = "SELECT * FROM stores WHERE company_id = ? ORDER BY id";
            $query = $this->db->query($sql, array($this->data['usercomp']));
            foreach ($query->result_array() as $store) array_push($arrStores, $store['id']);

            if (count($arrStores)) $more = ' WHERE cs.store_id in (' . implode(',', $arrStores) . ')';
            else $more = " WHERE cs.store_id = 0";

        } else $more = " AND store_id = " . $this->data['userstore'];

        $sql = "SELECT c.*, cs.store_id FROM catalogs_stores as cs JOIN catalogs as c ON c.id = cs.catalog_id {$more} GROUP BY cs.catalog_id ORDER BY c.name";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getAllCatalogs()
    {
        if (!$this->db->table_exists('catalogs')) {
            return false;
        }

        $catalogs = $this->db->get('catalogs')->result_array();

        return $catalogs;
    }
    public function getStoresOnCatalogsByCatalogID($catalog_id){
        $catalogs=$this->db->select('*')->from('catalogs_stores')->where('catalog_id=',$catalog_id)->get()->result_array();
        return $catalogs;
    }
    public function getCaralogsProductsCatalogsByCatalogID($catalog_id){
        $caralogsProductsCatalogs=$this->db->select('*')->from('catalogs_products_catalog')->where('catalog_id=',$catalog_id)->get()->result_array();
        return $caralogsProductsCatalogs;
    }

    public function getCollections(): array
    {
        return $this->db->distinct('attribute_value')
            ->select('attribute_id, attribute_value')
            ->where('attribute_id IS NOT NULL')
            ->where('attribute_value IS NOT NULL')
            ->get('catalogs')
            ->result_array();
    }

    public function checkIfStoreInCatalog(int $store_id, int $catalog_id): bool
    {
        return $this->db->get_where('catalogs_stores', array(
            'catalog_id' => $catalog_id,
            'store_id' => $store_id
        ))->num_rows() > 0;
    }

    public function getByIntTo(string $int_to): ?array
    {
        return $this->db->get_where('catalogs', array('int_to' => $int_to))->row_array();
    }

    public function getProductIdVtexGetCatalogPerLimit(string $int_to, int $limit, int $offset): ?array
    {
        $subQuery = $this->db
            ->select('vgc.product_id')
            ->where('vgc.int_to', $int_to)
            ->limit($limit, $offset)
            ->order_by('vgc.product_id', 'ASC')
            ->get_compiled_select('vtex_get_catalog AS vgc');

        return $this->db
            ->order_by('query_table.product_id', 'DESC')
            ->limit(1)
            ->get("($subQuery) as query_table")
            ->row_array();
    }

    public function getCountVtexGetCatalog(string $int_to): int
    {
        return $this->db->where('int_to', $int_to)->get('vtex_get_catalog')->num_rows();
    }

    public function getCatalogByIntTo(string $int_to): ?array
    {
        return $this->db
            ->where('int_to', $int_to)
            ->get('catalogs')
            ->row_array();
    }
}