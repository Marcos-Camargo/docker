<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Categorias
 
 */

class Model_category extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /* get active brand infromation */
    public function getActiveCategroy()
    {
        $sql = "SELECT * FROM categories WHERE active = ? ORDER BY name";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }
    
    public function getCategoriesByStoreId($store_id)
    {
        if ($this->data['usercomp'] == 1) {
            $more = "";
        }
        elseif ($this->data['userstore'] == 0) {
            $sql = "SELECT id FROM stores WHERE company_id = ? ORDER BY id";
            $query = $this->db->query($sql, array($this->data['usercomp']));

            $arrStores = array_map(function ($store){
                return $store['id'];
            }, $query->result_array());

            if (count($arrStores)) {
                $more = ' AND store_id IN (' . implode(',', $arrStores) . ')';
            }
            else $more = " AND store_id = 0";

        } else {
            $more = " AND store_id = {$this->data['userstore']}";
        }

        $sql = "SELECT * FROM categories where active = 1 and id in (
            select REPLACE(REPLACE(category_id, '[\"', '' ), '\"]', '') from products p where category_id != '[\"\"]' $more)
            order by name ";
        $query = $this->db->query($sql, array($store_id));
        return $query->result_array();
    }

    public function getCategoriesByStoreIdMarketplace($store_id, $int_to)
    {
        $sql = "SELECT * FROM categories c
        JOIN categorias_marketplaces cm ON (cm.category_id = c.id AND cm.int_to = ?)
        WHERE c.active = 1 
        AND c.id IN (
            SELECT REPLACE(REPLACE(p.category_id, '[\"', '' ), '\"]', '') 
            FROM products p 
            WHERE p.category_id != '[\"\"]' 
            AND p.store_id = ?
        )
        ORDER BY c.name";

        $query = $this->db->query($sql, array($int_to, $store_id));
        return $query->result_array();
    }

    /* get the brand data */
    public function getCategoryData($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM categories WHERE id = ?";
            //$sql = "SELECT categories.*, count(products.category_id) as qtd_product FROM categories LEFT JOIN products ON categories.id = left(substr(products.category_id,3),length(products.category_id)-4) WHERE categories.id = ? group by categories.id";
            //$sql = "SELECT categories.*, tipos_volumes.produto FROM categories LEFT JOIN tipos_volumes ON categories.tipo_volume_id = tipos_volumes.id WHERE categories.id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        //$sql = "SELECT * FROM categories";
        $sql = "SELECT categories.*, tipos_volumes.produto FROM categories LEFT JOIN tipos_volumes ON categories.tipo_volume_id = tipos_volumes.id ORDER BY categories.name";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    /*get the active category information*/
    public function getcategorybyName($name)
    {
        $sql = "SELECT * FROM categories WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        $row = $query->row_array();
        if ($row) {
            return $row['id'];
        } else {
            return false;
        }
    }
    

    //(FR)

    public function listCategory(int $offset, int $limit)
    {  
                

        return $this->db->select('days_cross_docking,blocked_cross_docking,id')
        ->group_start()
            ->where([
                'active' =>  '1',
                'blocked_cross_docking' =>  '1',
            ])
            ->where("data_alteracao > ( NOW() - INTERVAL 1 HOUR )")
        ->group_end()
        ->or_group_start()
            ->where([
                'active' =>  '1',
                'blocked_cross_docking' =>  '1',
                'force_update' => 1
            ])
        ->group_end()
        ->offset($offset)
        ->limit($limit)
        ->get('categories')
        ->result_array();
        
    }



    public function getcategoryBlock($id)
    {
        if($id) {

            if (is_numeric($id)) {
            $sql = "SELECT blocked_cross_docking,id FROM categories WHERE id = ?";
            }else{
            $sql = "SELECT blocked_cross_docking,id FROM categories WHERE name = ? ";   
            }
            $query = $this->db->query($sql, array($id));
            $row = $query->row_array();
            return $row['blocked_cross_docking'];   
        }  
    }

    public function getcategoryDays_cross_docking($id)
    {
        if($id) {
            if (is_numeric($id)) {
            $sql = "SELECT blocked_cross_docking,id,days_cross_docking FROM categories WHERE id = $id";
            }else{
            $sql = "SELECT blocked_cross_docking,id,days_cross_docking FROM categories WHERE name = '$id'";     
            }
            $query = $this->db->query($sql, array($id));
            $row = $query->row_array();
            return $row['days_cross_docking'];   
        }  
    }


    public function getcategoryName($id)
    {
        if($id) {
            if (is_numeric($id)) {
            $sql = "SELECT name FROM categories WHERE id = ?";
            }else{
            $sql = "SELECT name FROM categories WHERE name = ?";    
            }
            $query = $this->db->query($sql, array($id));
            $row = $query->row_array();
            return $row['name'] ?? null;
        }  
    }
    //(FR)
    
    
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('categories', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('categories', $data);
            return ($update == true) ? $id : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('categories');
            return ($delete == true) ? true : false;
        }
    }
    
    /* get the brand data */
    public function getTiposVolumes($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM tipos_volumes WHERE id = ?";
            //$sql = "SELECT categories.*, tipos_volumes.produto FROM categories LEFT JOIN tipos_volumes ON categories.tipo_volume_id = tipos_volumes.id WHERE categories.id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        
        $sql = "SELECT * FROM tipos_volumes WHERE ativo = 1";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function getCategoryDataForExportTemp()
    {
        
        $sql = "SELECT c.id AS codigo, c.name AS nome, t.codigo as codigo_frete , t.produto as descricao   FROM categories c, tipos_volumes t  WHERE c.active = 1 AND c.tipo_volume_id=t.id ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
	 public function getCategoryDataForExport()
    {
        
        $sql = "SELECT id AS codigo, name AS nome, active AS status FROM categories WHERE active = 1";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
	
    public function getCategoryLinked($id, $apelido)
    {
        $sql ='SELECT id_mkt FROM stores_mkts_linked WHERE apelido = ?';
        $query = $this->db->query($sql,array($apelido));
        $result = $query->row_array();
        $id_mkt = $result['id_mkt'];
        
        $sql = "SELECT * FROM categories_mkts_linked WHERE id_integration = ? AND id_mkt IN (SELECT id_mkt FROM categories_mkts WHERE id_cat = ?)";
        $query = $this->db->query($sql,array($id_mkt, $id));
        return $query->row_array();
    }
    
    public function getCategoryFatherData()
    {
        
        $sql = 'SELECT DISTINCT REPLACE(SUBSTRING(name, 1, position(" >" in name)-1),"\"","") AS category FROM categories WHERE active=1 ORDER BY category';
        $query = $this->db->query($sql);
        return $query->result_array();
    }
	
	public function getFeatchCategoryData($offset = 0,$orderby = '', $procura = '', $limit =200)
    {
		if ($offset == '') {$offset=0;}
		if ($limit == '') {$limit=200;}
		if ($procura !== '') { $procura = ' WHERE '.$procura;}
        $sql = "SELECT c.*, tv.produto, (SELECT COUNT(*) FROM products WHERE left(substr(category_id,3),length(category_id)-4) = c.id AND status=1 AND situacao = 2) AS usedby 
        	FROM categories c LEFT JOIN tipos_volumes tv ON c.tipo_volume_id = tv.id";
        $sql .= $procura.$orderby.' LIMIT '.$limit.' OFFSET '.$offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }
	
	public function getCountCategoryData($procura = '')
    {
    	if ($procura !== '') { $procura = ' WHERE '.$procura;}
        $sql = "SELECT count(*) as qtd  FROM categories c LEFT JOIN tipos_volumes tv ON c.tipo_volume_id = tv.id";
        $sql .= $procura;
        $query = $this->db->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
    }
	
	public function getTiposVolumesByCategoryId($category_id )
    {
    	
		$sql = "SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id = ?)";
		$cmd = $this->db->query($sql, array($category_id));
		return  $cmd->row_array();
		
    }

    public function getTipoVolumeCategory($cat_id)
    {
        $queryVol 	= $this->db->query('SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id = ?)', array($cat_id));
        $rsVol      = $queryVol->row_array();
        return $rsVol['codigo'] ?? null;
    }

    public function getDataByNameorId($id)
    {
        return $this->db->or_where(array('id' => $id, 'name' => $id))->get('categories')->row_array();
    }

    public function getDataCategoryByName($name)
    {
        $sql = "SELECT * FROM categories WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }
}