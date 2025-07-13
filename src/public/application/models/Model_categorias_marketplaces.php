<?php

/**
 * Class Model_categorias_marketplaces
 * @property CI_DB_query_builder $db
 */
class Model_categorias_marketplaces extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
		
    }
    
    /* get active brand infromation */
    public function getDataByCategoryId($category_id)
    {
        $sql = "SELECT * FROM categorias_marketplaces WHERE category_id = ?";
        $query = $this->db->query($sql, array($category_id));
        return $query->result_array();
    }

    /* get active brand infromation */
    public function getDataByCategoryMktId($int_to, $category_marketplace_id)
    {
        $sql = "SELECT * FROM categorias_marketplaces WHERE category_marketplace_id = ? and int_to = ?";
        $query = $this->db->query($sql, array($category_marketplace_id, $int_to));
        return $query->row_array();
    }
    
    public function getListIntTo() {
        $sql = "select distinct int_to from categorias_todos_marketplaces";
        $query = $this->db->query($sql);
        return $query->result_array(); 
    }

	public function getAllCategoriesByMarketplace($int_to) 
	{
		$sql = "SELECT * FROM categorias_todos_marketplaces WHERE int_to = ? ORDER BY nome";
        $query = $this->db->query($sql, array($int_to));
        return $query->result_array();
		
	}
    
    public function getCategoryByMarketplace($int_to, $category_marketplace_id) 
	{
		$sql = "SELECT * FROM categorias_todos_marketplaces WHERE int_to = ? and id = ?";
        $query = $this->db->query($sql, array($int_to, $category_marketplace_id));
        return $query->row_array();
	}

	public function getCategoryMktplace($int_to, $category_id)
	{
		$sql = "SELECT * FROM categorias_marketplaces WHERE int_to = ? AND category_id = ?";
        $query = $this->db->query($sql, array($int_to, $category_id));
        return $query->row_array();
    }

    public function getCategoriesMktplace($int_to, array $categoriesIds = []): array
    {
        return $this->db->select(['cm.*', 'c.active'])
                ->from('categorias_marketplaces cm')
                ->join('categories c', 'cm.category_id = c.id')
                ->where('cm.int_to', $int_to)
                ->where_in('cm.category_id', $categoriesIds)
                ->get()->result_array() ?? [];
    }
    
	public function getLimitedCategoriesByMarketplace($int_to,$nome) 
	{
		$nome = str_replace(">", '', $nome);
		$nome = str_replace(", ", ' ', $nome);
		$nome = str_replace(",", '', $nome);
		$nome = str_replace("/", ' ', $nome);
		$nomes = explode(' ',$nome);
		$like = '';
		$remover = array('PARA','DE','DA','DO');
		foreach($nomes as $nome) {
			if ((strlen($nome)>1) && (!in_array($nome,$remover))){
				$like .= " nome like '%".$nome."%' OR";
			}
		}
		$like = substr($like,0,-2);
		
		$sql = "SELECT * FROM categorias_todos_marketplaces WHERE int_to = ? AND (".$like.") ORDER BY nome";
        $query = $this->db->query($sql, array($int_to));
		// $this->session->set_flashdata('success',$sql);
        return $query->result_array();
		
	}
	
	public function replace($data)
    {
        if($data) {
            $replace = $this->db->replace('categorias_marketplaces', $data);
            return ($replace == true) ? true : false;
        }
    }
	
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('categorias_marketplaces', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('category_id', $id);
            $update = $this->db->update('categorias_marketplaces', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('category_id', $id);
            $delete = $this->db->delete('categorias_marketplaces');
            return ($delete == true) ? true : false;
        }
    }
    
	public function getCategoryDataForExport()
    {
        
        $sql = "SELECT id AS codigo, nome FROM categorias_todos_marketplaces ORDER BY nome";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
	
	public function getAllCategoriesById($int_to,$id) 
	{
		$sql = "SELECT * FROM categorias_todos_marketplaces WHERE int_to = ? AND id = ?";
        $query = $this->db->query($sql, array($int_to, $id));
        return $query->row_array();
		
	}
	
	public function getDataCompleteByCategoryId($category_id)
    {
        $sql = "SELECT c.*, cm.nome FROM categorias_marketplaces c, categorias_todos_marketplaces cm WHERE c.category_id = ? AND c.int_to=cm.int_to AND cm.id = c.category_marketplace_id";
        $query = $this->db->query($sql, array($category_id));
        return $query->result_array();
    }

    public function getCategoriesRootVtex($level = 0) {
        $sql = "select LENGTH(nome) - LENGTH(REPLACE(nome, '/', '')), ctm.* from categorias_todos_marketplaces ctm where LENGTH(nome) - LENGTH(REPLACE(nome, '/', '')) <= ? order by 1 desc";
        $query = $this->db->query($sql, array($level));
        return $query->result_array();
    }

    public function getCategoryVtexByName($name) {
        $sql = "select * from categorias_todos_marketplaces ctm where nome = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }
	
	public function createCatTodosMkt($data)
    {
    	$insert = $this->db->insert('categorias_todos_marketplaces', $data);
        return ($insert == true) ? true : false;
    }
    
    public function updateCatTodosMkt($data,$id,$int_to)
    {
    	$this->db->where('id', $id);
    	$this->db->where('int_to', $int_to);
        $update = $this->db->update('categorias_todos_marketplaces', $data);
        return ($update == true) ? true : false;
    }

    public function getProductCategoryMktIntegration($prd_id, $int_to)
    {
        $sql = "SELECT * FROM products_category_mkt where prd_id = ? AND int_to = ?";
        $query = $this->db->query($sql, array($prd_id, $int_to));
        return $query->row_array();
    }

    public function ProductCategoryMktIntegration($prd_id, $int_to, $category_mkt_id)
    {
        $data = array('prd_id' => $prd_id, 'int_to' => $int_to, 'category_mkt_id' => $category_mkt_id);
        $insert = $this->db->insert('products_category_mkt', $data);
    }

    public function removeAtttributesByCategoryIdIntTo($int_to, $id)
    {
        if($id) {
            $this->db->where('id_categoria', $id);
            $this->db->where('int_to', $int_to);
            $delete = $this->db->delete('atributos_categorias_marketplaces');
            return ($delete == true) ? true : false;
        }
    }

    public function removeMarketplaceByCategoryIdIntTo($int_to, $id)
    {
        if($id) {
            $this->db->where('id', $id);
            $this->db->where('int_to', $int_to);
            $delete = $this->db->delete('categorias_todos_marketplaces');
            return ($delete == true) ? true : false;
        }
    }

    public function removeCategoryByCategoryIdIntTo($int_to, $id)
    {
        if($id) {
            $this->db->where('category_marketplace_id', $id);
            $this->db->where('int_to', $int_to);
            $delete = $this->db->delete('categorias_marketplaces');
            return ($delete == true) ? true : false;
        }
    }

    public function getAllCategoriesByMarketplaceAndCategoryId($int_to, $category_marketplace_id) 
	{
		$sql = "SELECT * FROM categorias_marketplaces WHERE int_to = ? and category_marketplace_id = ?";
        $query = $this->db->query($sql, array($int_to, $category_marketplace_id));
        return $query->result_array();
	}

    public function removeByAllFields($int_to, $category_id, $category_marketplace_id)
    {
        $this->db->where('int_to', $int_to);
        $this->db->where('category_id', $category_id);
        $this->db->where('category_marketplace_id', $category_marketplace_id);
        $delete = $this->db->delete('categorias_marketplaces');
        return ($delete == true) ? true : false;
    }

    public function verifyExistCategoryAssociateDiferent($category_id, $category_marketplace_id) 
	{	
        $sql = "SELECT * FROM categorias_marketplaces WHERE category_id = ? AND category_marketplace_id != ?";
        $query = $this->db->query($sql, array($category_id, $category_marketplace_id));
        return $query->result_array();
	}

    public function removeByIntToCategoryId($int_to, $category_id)
    {
        $this->db->where('int_to', $int_to);
        $this->db->where('category_id', $category_id);
        $delete = $this->db->delete('categorias_marketplaces');
    }

    public function getAllCategoryByMarketplace($int_to, $category_marketplace_id) 
	{
		$sql = "SELECT * FROM categorias_todos_marketplaces WHERE int_to = ? and id = ?";
        $query = $this->db->query($sql, array($int_to, $category_marketplace_id));
        return $query->result_array();
	}

    public function createTodosMarketplace($data)
    {
        $insert = $this->db->insert('categorias_todos_marketplaces', $data);
        return ($insert == true) ? true : false;
    }
    
    public function replaceTodosMarketplace($data)
    {
        $update =  $this->db->replace('categorias_todos_marketplaces', $data);
        return ($update == true) ? true : false;
    }

    public function verifyExistCategoryAssociateDiferentByIntTo($category_id, $int_to)
    {
        $sql = "SELECT * FROM categorias_marketplaces WHERE category_id = ? AND int_to != ?";
        $query = $this->db->query($sql, array($category_id, $int_to));
        return $query->result_array();
    }

    public function getDataByCategoryIdAndIntTo($category_id, $int_to)
    {
        $sql = "SELECT * FROM categorias_marketplaces WHERE category_id = ? AND int_to = ?";
        $query = $this->db->query($sql, array($category_id, $int_to));
        return $query->result_array();
    }
}
