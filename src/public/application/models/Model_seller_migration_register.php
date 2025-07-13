<?php 
/*

Model de Acesso ao BD para tabela de fretes de pedidos para produtos cadastrados em Sellercenters do Conecta Lá

*/  

class Model_seller_migration_register extends CI_Model
{
	private $table = 'seller_migration_register';

	public function __construct()
	{
		parent::__construct();
	}
	
	public function getData($id = null, $procura = '')
	{

		if($id) {
			$sql = "SELECT * FROM ".$this->table." WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
		if($procura)
		{
			$sql = "SELECT * FROM ".$this->table;
	        $sql .= $procura;
			$query = $this->db->query($sql);
			return $query->row_array();
		}
		else{
			$sql = "SELECT * FROM ".$this->table;
			$query = $this->db->query($sql);
			return $query->row_array();
		}

	}

	public function getMigrationData($offset = 0, $procura = '', $sOrder = '', $limit = 200)
	{
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND sm.store_id = " . $this->data['userstore']);
		$sql = '';
		if ($offset=='') {$offset=0;}
	 	if ($limit=='') {$limit=200;}
		$sql = "SELECT s.id, s.name as store_name, (SELECT Count(*) FROM products_seller_migration 
                    WHERE store_id = s.id) as total_imported_products, 
                (SELECT Count(*) FROM products_seller_migration 
                     WHERE store_id = s.id AND internal_id IS NULL AND date_disapproved IS NOT NULL) as total_migrated_products, 
                (SELECT Count(*) FROM products_seller_migration 
                     WHERE store_id = s.id AND internal_id IS NOT NULL AND date_approved IS NOT NULL) as total_matchs, sm.status as migration_status, sm.finish_date 
                     FROM stores s 
                     LEFT JOIN seller_migration_register sm ON s.id  = sm.store_id WHERE s.active = 1 AND s.flag_store_migration IS NOT NULL ";
		$sql.= $procura.$more." $sOrder LIMIT $limit  OFFSET $offset";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getMigrationDataCount($procura = null)
	{
		$sql = "SELECT Count(*) as qtd FROM  seller_migration_register";
		$query = $this->db->query($sql);
		return $query->row_array();
	}
		
	public function getCount($procura = '')
	{
		if ($procura == "") {
			$sql = " SELECT count(*) as qtd "; 
			$sql .=	" FROM ".$this->table." ";
		} else {
			$sql = "SELECT count(*) as qtd ";
			$sql .=	" FROM ".$this->table." b";
		}
		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];

	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert($this->table, $data);
            if(!$insert)
                $error = $this->db->error();
			return ($insert) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) 
        {
			$this->db->where('id', $id);
			$update = $this->db->update($this->table, $data);
			return ($update == true) ? $id : false;
		}
	}
	public function updateStatusMigrationByStore($status, $storeId)
    {
        if ($storeId) {
            $data = [
                'status' => $status            ];
            $this->db->where('store_id', $storeId);
            try {
                $update = $this->db->update('seller_migration_register', $data);
                return ($update) ? true : false;
            } catch (Exception $e) {
                return false;
            }
        }
     
    }

    public function getSellerMigrationByStoreId($store_id)
    {
        return $this->db->select('*')->from('seller_migration_register')->where(['store_id' => $store_id])->get()->row_array();
    }

    public function updateSellerMigrationRegisterByStoreId($store_id)
    {
        $this->db->where('store_id', $store_id);
        $this->db->update('seller_migration_register', ['status' => 0, 'end_date' => null, 'finish_date' => null]);
    }

    public function updateProductsInProductsSellerMigration($store_id)
    {
        $this->db->query("
            UPDATE products_seller_migration SET date_disapproved = null WHERE store_id = $store_id AND date_disapproved is not null AND id_sku NOT IN (
                SELECT skumkt FROM prd_to_integration WHERE store_id = $store_id AND skumkt is not null
            );
        ");
    }

    public function getStoresToMigrate()
    {
        $this->db->where('status', 1);
        $this->db->where('finish_date IS NULL');
        return $this->db->get($this->table)->result();
    }

}