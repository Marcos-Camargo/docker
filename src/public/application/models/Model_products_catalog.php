<?php
/*
 
 Model de Acesso ao BD para Catalogos de Produtos
 
 */

class Model_products_catalog extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
		
		$this->load->model('model_products');
    }
	
	public function renameAndGetPrincipalImage($oldFolder, $newfolder){
		$baseAsset = 'assets/images/product_image/';
		$newAsset = 'assets/images/catalog_product_image/';
		if (isset(get_instance()->bucket)) {
			/**
			 * @var Bucket $bucket
			 */
			$bucket = get_instance()->bucket;
			$bucket->renameDirectory($baseAsset . $oldFolder, $newAsset . $newfolder);
			$fotos = $bucket->getFinalObject($newAsset . $newfolder);
			foreach ($fotos['contents'] as $foto) {
				if ($foto['url'] != "") {
					return $foto['url'];
				}
			}
			return '';
		}
		$basedir = FCPATH . 'assets/images/product_image/';
		$newdir = FCPATH . 'assets/images/catalog_product_image/';
		rename($basedir.$oldFolder, $newdir.$newfolder);

		$fotos = scandir($newdir.$newfolder);
        foreach($fotos as $foto) {
            if (($foto!=".") && ($foto!="..") && ($foto!="")) {
  				return base_url('assets/images/catalog_product_image/'.$newfolder).'/'.$foto;
            }
        }
		return '';
	}
	
	public function create($data = '', $catalogs = null)
    {
        if($data) {
            $create = $this->db->insert('products_catalog', $data);
            $product_catalog_id = $this->db->insert_id();

            //gravo os relacionamentos com os catálogs
            if (is_array($catalogs)) {
                foreach ($catalogs as $catalog_id) {
                    $catalog_stores_data = array(
                        'catalog_id' => $catalog_id,
                        'product_catalog_id' => $product_catalog_id
                    );
                    $this->db->insert('catalogs_products_catalog', $catalog_stores_data);
                }
            }
			
			//acerto o diretório de imagens e mudo o banco de dados 
			$data['principal_image'] = $this->renameAndGetPrincipalImage($data['image'],$product_catalog_id);
			$data['image'] = $product_catalog_id;
			$this->db->where('id', $product_catalog_id);
            $update = $this->db->update('products_catalog', $data);
			
			// agora gravo o log
			$data['id'] = $product_catalog_id;
	        $data['catalogs'] = $catalogs;    
            get_instance()->log_data('products_catalog','create',json_encode($data),"I");
            return ($create) ? $product_catalog_id : false;
        }
		return false;
    }
	
	public function update($data, $product_catalog_id, $catalogs = null)
    {
        if($data && $product_catalog_id) {
            $this->db->where('id', $product_catalog_id);
            $update = $this->db->update('products_catalog', $data);
			if (is_array($catalogs)) {
				if(!empty($catalogs)) { // limpam os que não existem mais pode ter desmarcado na tela
					$str = implode(",", $catalogs);
					$sql = 'DELETE FROM catalogs_products_catalog WHERE product_catalog_id=? AND catalog_id NOT IN ('.$str.')';
					$query = $this->db->query($sql, array($product_catalog_id));
				}
				else {
					$sql = 'DELETE FROM catalogs_products_catalog WHERE product_catalog_id=?';
					$query = $this->db->query($sql, array($product_catalog_id));
				}
				foreach ($catalogs as $catalog_id) {
					$sql = 'SELECT * FROM catalogs_products_catalog WHERE product_catalog_id=? AND catalog_id=?';
					$query = $this->db->query($sql,array($product_catalog_id,$catalog_id));
					$exist = $query->row_array();
					if (!$exist) { // adiciona os faltosos
						$catalogs_products_data = array(
		                	'catalog_id' => $catalog_id,
		                	'product_catalog_id' => $product_catalog_id
		            	);
		           	 	$group_data = $this->db->insert('catalogs_products_catalog', $catalogs_products_data);
					}		            
				}
			}
			else {
				$sql = 'DELETE FROM catalogs_products_catalog WHERE product_catalog_id=?';
				$query = $this->db->query($sql, array($product_catalog_id));
			}
			$data['id'] = $product_catalog_id;  
			$data['catalogs'] = $catalogs;    
            get_instance()->log_data('products_catalog','edit_after',json_encode($data),"I");
            return ($update == true) ? $product_catalog_id : false;
        }
		return false;
    }

    public function getProductProductData($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM products_catalog WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
		return false ;
    }

	public function deleteAttributes($product_catalog_id)
	{
		$sql = "DELETE FROM products_catalog_attributes_marketplaces WHERE product_catalog_id = ? AND id_atributo <> 'GTIN' AND id_atributo <> 'EAN' AND id_atributo <> 'BRAND' AND id_atributo <> 'SELLER_SKU'";
		$query = $this->db->query($sql, array($product_catalog_id));
		return;
	}
	
	public function saveProductsCatalogAttributes($data)
	{
		if($data) {
			$insert = $this->db->replace('products_catalog_attributes_marketplaces', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function getAllProductsCatalogAttributes($product_catalog_id)
	{

		$sql = "SELECT *  FROM products_catalog_attributes_marketplaces WHERE product_catalog_id = ? ";
		$query = $this->db->query($sql,array($product_catalog_id));
		return $query->result_array();
	}
	
 	public function getCatalogsStoresDataByProductCatalogId($product_catalog_id = null)
    {
        if($product_catalog_id) {
            $sql = "SELECT * FROM catalogs_products_catalog WHERE product_catalog_id = ?";
            $query = $this->db->query($sql, array($product_catalog_id));
            return $query->result_array();
        }
		return false ;
    }

	public function getProductCatalogVariants($product_catalog_id = null)
	{
		if($product_catalog_id) {
            $sql = "SELECT * FROM products_catalog WHERE prd_principal_id = ?";
            $query = $this->db->query($sql, array($product_catalog_id));
            return $query->result_array();
        }
		return false ;
	}

	public function VerifyEanUnique($ean, $id = null)
	{
		if($id) {
            $sql = "SELECT count(*) as qtd FROM products_catalog WHERE id != ? AND EAN = ? AND status in ?";
            $query = $this->db->query($sql, array($id, $ean, array(1,4)));
		}else {
			$sql = "SELECT count(*) as qtd FROM products_catalog WHERE EAN = ?";	
			$query = $this->db->query($sql, array($ean));
		}
        
        $row = $query->row_array(); 
		return ($row['qtd']==0);
	}

    public function getProductsCatalogDataView( $offset = 0, $procura = '',$orderby = '', $limit =200)
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
		
		$sql = "SELECT p.*, b.name as brandname FROM products_catalog p, brands b WHERE p.prd_principal_id is null AND p.brand_id=b.id ".$procura.$orderby." LIMIT ".$limit." OFFSET ".$offset; ;
		//$this->session->set_flashdata('error', $sql);
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getProductsCatalogDataCount($procura = '')
	{

		$sql = "SELECT count(*) as qtd FROM products_catalog p, brands b WHERE prd_principal_id is null AND p.brand_id=b.id ".$procura;
	//	$this->session->set_flashdata('error', $sql);
		$query = $this->db->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function getProductsCatalogDataShowCaseView( $offset = 0, $procura = '',$orderby = '', $limit =200, $attribute = null)
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}

        $select = ' c.attribute_value, ';
        $where = '';
        $join = ' JOIN catalogs_products_catalog cpc ON cpc.product_catalog_id = p.id JOIN catalogs c ON c.id = cpc.catalog_id ';
        if($attribute){
            $attributes = implode('","', $attribute);
            $where = ' AND c.attribute_value IN ("' . $attributes . '")';
        }

		if ($this->data['usercomp'] == 1) { // administrador le todos os catalogos ativos
			$catalogs = "SELECT id FROM catalogs WHERE status=1 ";
		} elseif (($this->data['userstore'] == 0)) { // pego todos os catálogos das minhas lojas 
			$catalogs = "SELECT id FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id IN (SELECT id FROM stores WHERE company_id = ".$this->data['usercomp'].")) ";
		} else {
			$catalogs = "SELECT id FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id = ".$this->data['userstore'].") ";
		}
		
		$more = ' AND p.id IN (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND catalog_id in ('.$catalogs.'))';

		$sql = "SELECT $select p.*, b.name as brand, c.name as catalog_name FROM products_catalog p
        JOIN brands b ON b.id = p.brand_id
        $join    
        WHERE p.status=1 AND b.id = p.brand_id AND prd_principal_id is null ".$where.$more.$procura.$orderby." LIMIT ".$limit." OFFSET ".$offset;
		//$this->session->set_flashdata('error', $sql);
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getProductsCatalogDataShowCaseCount($procura = '')
	{
		if ($this->data['usercomp'] == 1) { // administrador le todos os catalogos ativos
			$catalogs = "SELECT id FROM catalogs WHERE status=1 " ;
		} elseif (($this->data['userstore'] == 0)) { // pego todos os catálogos das minhas lojas 
			$catalogs = "SELECT id FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id IN (SELECT id FROM stores WHERE company_id = ".$this->data['usercomp'].")) " ;
		} else {
			$catalogs = "SELECT id FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id = ".$this->data['userstore'].") " ;		
		}
		
		$more = ' AND p.id IN (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND catalog_id in ('.$catalogs.'))';
		
		$sql = "SELECT count(*) as qtd FROM products_catalog p, brands b, catalogs_products_catalog cpc WHERE p.status=1 AND b.id = p.brand_id AND cpc.product_catalog_id = p.id AND prd_principal_id is null ".$more.$procura;
		//$this->session->set_flashdata('error', $sql);
		$query = $this->db->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function getProductByProductCatalogStoreId($product_catalog_id, $store_id, $ignore_deleted_product = false) {
        $this->db->where(array(
            'store_id' => $store_id,
            'product_catalog_id' => $product_catalog_id
        ));

        if ($ignore_deleted_product) {
            $this->db->where('status !=', Model_products::DELETED_PRODUCT);
        }
        return $this->db->get('products')->row_array();
	}
	
	public function getProductsByProductCatalogId($product_catalog_id, $offset = 0, $limit = null) {
		$sql = "SELECT p.*, s.name as store, s.responsible_email FROM products p, stores s WHERE product_catalog_id = ? AND p.store_id=s.id and s.active =1";
		if (!is_null($limit)) {
			$sql.= ' LIMIT '.$limit. ' OFFSET '.$offset;
		}
		
		$query = $this->db->query($sql,array($product_catalog_id));
		return $query->result_array();
	}

	public function getProductsFetchProductCatalogId($product_catalog_id, $offset = 0, $procura = '',$orderby = '', $limit =200) 
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
		
		$sql = "SELECT p.*, s.name as store FROM products p, stores s WHERE p.store_id=s.id AND product_catalog_id = ".$product_catalog_id." ".$procura.$orderby." LIMIT ".$limit." OFFSET ".$offset; 
		//$this->session->set_flashdata('error', $sql);
		//get_instance()->log_data('products_catalog','SQL',$sql,"I");
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getProductsFetchProductCatalogIdCount($product_catalog_id,$procura = '') {
		$sql = "SELECT count(*) as qtd FROM products p, stores s WHERE p.store_id=s.id AND product_catalog_id = ".$product_catalog_id." ".$procura;
		//get_instance()->log_data('products_catalog','SQL',$sql,"I");
		$query = $this->db->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
	}
	
	public function getProductsCatalogbyStoreId($store_id, $offset = 0, $limit = null){
		
		$sql = 'SELECT * FROM products_catalog p WHERE status=1 AND p.id IN 
			    (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND catalog_id in 
			    	(SELECT id FROM catalogs WHERE status=1 AND id in 
			    		(SELECT catalog_id FROM catalogs_stores WHERE store_id = '.$store_id.') 
			 		)
				) ORDER BY id ';
		if (!is_null($limit)) {
			$sql.= ' LIMIT '.$limit. ' OFFSET '.$offset;
		}
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getProductByProductCatalogIdAndStoreId($product_catalog_id,$store_id){
		$sql = "SELECT * FROM products WHERE store_id=? AND status != ? AND product_catalog_id = ?";
		$query = $this->db->query($sql, array($store_id, Model_products::DELETED_PRODUCT, $product_catalog_id));
		return $query->row_array();
	}
	
	public function getProductsByEAN($EAN)
	{
		$sql = "SELECT * FROM products_catalog WHERE EAN = ?";
		$query = $this->db->query($sql, array($EAN));

        return $query->result_array();
	}
	
	public function getProductsCatalogbyCatalogid($catalog_id, $offset = 0, $limit = null){
		
		$sql = 'SELECT * FROM products_catalog p WHERE p.status=1 AND p.id IN 
			    (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND catalog_id =?
				) ORDER BY id ';
		if (!is_null($limit)) {
			$sql.= ' LIMIT '.$limit. ' OFFSET '.$offset;
		}
		$query = $this->db->query($sql, array($catalog_id));
		return $query->result_array();
	}
	
	public function updateSimple($data, $product_catalog_id)
    {
        if($data && $product_catalog_id) {
            $this->db->where('id', $product_catalog_id);
            $update = $this->db->update('products_catalog', $data);
            get_instance()->log_data('products_catalog','edit_after',json_encode($data),"I");
            return ($update == true) ? $product_catalog_id : false;
        }
		return false;
    }
	
	public function removeProductCatalogFromCatalog($product_catalog_id, $catalog_id)
    {

		$this->db->where('catalog_id', $catalog_id)->where('product_catalog_id', $product_catalog_id);
        $delete = $this->db->delete('catalogs_products_catalog');
        return ($delete == true) ? true : false;		        
    }
    
	public function getProductsCatalogUpdated($catalog_id, $date_update, $offset = 0, $limit = null){
		
		$sql = 'SELECT * FROM products_catalog p WHERE prd_principal_id IS NULL AND status=1 AND date_update > ? AND p.id IN 
			    (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND catalog_id =?
				) ORDER BY id ';
		if (!is_null($limit)) {
			$sql.= ' LIMIT '.$limit. ' OFFSET '.$offset;
		}
		$query = $this->db->query($sql, array($date_update,$catalog_id));
		return $query->result_array();
	}

	public function getProductsByEANandSkuId(string $EAN, int $mkt_sku_id, int $catalog_id): array
    {
        return $this->db->select('pc.*')
            ->join('catalogs_products_catalog cpc', 'cpc.product_catalog_id = pc.id')
            ->where(array(
                'pc.EAN'         => $EAN,
                'pc.mkt_sku_id'  => $mkt_sku_id,
                'cpc.catalog_id' => $catalog_id
            ))->get('products_catalog pc')
            ->result_array();
	}

    public function getProductsWithChangedPrice($getCount = false, $isAdmin = false, $getAlertZero = false)
    {
        // pegar os parametros
        $percSetting = $this->db->query("SELECT * FROM settings WHERE name = 'alert_percentage_update_price_catalog'")->row_array();
        $daysSetting = $this->db->query("SELECT * FROM settings WHERE name = 'alert_days_update_price_catalog'")->row_array();

        $more = $isAdmin || $this->data['usercomp'] == 1 ? "": ($this->data['userstore'] == 0 ? " AND p.company_id = ".$this->data['usercomp'] : " AND p.store_id = ".$this->data['userstore']);
        $cols = $getCount ? 'count(*) as qty' : 'l1.*';
        $percP = (100 - $percSetting['value'])/100;
        $percM = (100 + $percSetting['value'])/100;
        $daysAlert = $daysSetting['value'];

        $where = "AND l1.date_create >= '".date('Y-m-d', strtotime("-{$daysAlert} days", time()))." 00:00:00'";
        $where .= $getAlertZero ? 'AND l1.alert = 0' : '';

        $sql = "SELECT {$cols} FROM log_products_catalog_price l1 
                JOIN products p ON l1.product_catalog_id = p.product_catalog_id 
                LEFT JOIN log_products_catalog_price l2 ON (l1.product_catalog_id = l2.product_catalog_id AND l1.id < l2.id) 
                WHERE l2.id IS NULL AND p.qty > 0 
                AND (
                        (CAST(l1.old_price AS DECIMAL(12,2)) > CAST(l1.new_price AS DECIMAL(12,2)) AND CAST(l1.old_price AS DECIMAL(12,2)) * {$percP} > CAST(l1.new_price AS DECIMAL(12,2))) OR 
                        (CAST(l1.new_price AS DECIMAL(12,2)) > CAST(l1.old_price AS DECIMAL(12,2)) AND CAST(l1.old_price AS DECIMAL(12,2)) * {$percM} < CAST(l1.new_price AS DECIMAL(12,2)))
                    ) {$where} {$more} group by p.product_catalog_id";

        $query = $this->db->query($sql);
        return $getCount ? $query->num_rows() : $query->result_array();
    }

    public function getProductWithChangedPrice($catalog_id = null)
    {
        if (empty($catalog_id)) return false;

        // pegar os parametros
        $percSetting = $this->db->query("SELECT * FROM settings WHERE name = 'alert_percentage_update_price_catalog'")->row_array();
        $daysSetting = $this->db->query("SELECT * FROM settings WHERE name = 'alert_days_update_price_catalog'")->row_array();

        $percP = (100 - $percSetting['value'])/100;
        $percM = (100 + $percSetting['value'])/100;
        $daysAlert = $daysSetting['value'];

        $betweenDate = "AND l1.date_create >= '".date('Y-m-d', strtotime("-{$daysAlert} days", time()))." 00:00:00'";

        $sql = "SELECT l1.* FROM log_products_catalog_price l1
                LEFT JOIN log_products_catalog_price l2 ON (l1.product_catalog_id = l2.product_catalog_id AND l1.id < l2.id) 
                WHERE l2.id IS NULL AND (
                        (CAST(l1.old_price AS DECIMAL(12,2)) > CAST(l1.new_price AS DECIMAL(12,2)) AND CAST(l1.old_price AS DECIMAL(12,2)) * {$percP} > CAST(l1.new_price AS DECIMAL(12,2))) OR 
                        (CAST(l1.new_price AS DECIMAL(12,2)) > CAST(l1.old_price AS DECIMAL(12,2)) AND CAST(l1.old_price AS DECIMAL(12,2)) * {$percM} < CAST(l1.new_price AS DECIMAL(12,2)))
                    ) {$betweenDate} AND l1.product_catalog_id = {$catalog_id} LIMIT 1";

        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function updateAlertLogProductsCatalog($id, $alert)
    {
        if (!$id || !$alert) return false;

        $sql = "UPDATE log_products_catalog_price SET alert = ? WHERE id = ?";
        return $this->db->query($sql, array($alert, $id));
    }

    public function getCatalogByName($name)
    {
        $sql = "SELECT * FROM catalogs WHERE name = ?";
        $query = $this->db->query($sql, array($name));

        return $query->row_array();
    }

    public function verifyStoreCatalog($catalog, $store_id)
    {
        $sql = "SELECT * FROM catalogs_stores WHERE catalog_id = ? AND store_id = ?";
        $query = $this->db->query($sql, array($catalog, $store_id));

        return $query->row_array() ? true : false;

    }

    public function getCatalogsProductsByProductCatalogIdAndCatalogId($catalog_id, $product_catalog_id)
    {
        if($product_catalog_id) {
            $sql = "SELECT * FROM catalogs_products_catalog WHERE catalog_id = ? AND product_catalog_id = ?";
            $query = $this->db->query($sql, array($catalog_id, $product_catalog_id));
            return $query->row_array();
        }
        return false ;
    }
	
	public function setProductCatalogAsDuplicate($product_catalog_id)
	{
        return $this->updateSimple(array('status'=>4),$product_catalog_id);
	}
	
	public function getProductCatalogIdFromProducts($offset=0, $limit=200)
	{
		$sql = "SELECT DISTINCT product_catalog_id FROM products WHERE product_catalog_id IS NOT null ";
		$sql.= ' LIMIT '.$limit. ' OFFSET '.$offset;
		$query = $this->db->query($sql);

        return $query->result_array();
	}
	
	public function updateProductsBasedFromProductCatalog($data, $product_catalog_id)
    {
        if($data && $product_catalog_id) {
        	$sql = "SELECT * FROM products WHERE product_catalog_id = ?";
			$query = $this->db->query($sql, array($product_catalog_id));	
			$products = $query->result_array();
			foreach($products as $product) {
				$this->model_products->update($data,$product['id']);
			}

        }
		return false;
    }

	public function getAllProductsCatalogUpdatedThatHaveProducts( $date_update, $offset = 0, $limit = null){
		
		$sql = 'SELECT * FROM products_catalog p WHERE p.prd_principal_id IS NULL AND p.date_update > ? AND EXISTS ';
		$sql .= '(SELECT * FROM products WHERE product_catalog_id=p.id)';
		if (!is_null($limit)) {
			$sql.= ' LIMIT '.$limit. ' OFFSET '.$offset;
		}
		$query = $this->db->query($sql, array($date_update));
		return $query->result_array();
	}
	
	public function getAllProdutosAtributos($id_product)
	{

		$sql = "SELECT *  FROM products_catalog_attributes_marketplaces WHERE product_catalog_id = ? ";
		$query = $this->db->query($sql,array($id_product));
		return $query->result_array();
	}
	
	public function getProductAttributeByIdIntto($id_product, $id_atributo, $int_to)
	{

		$sql = "SELECT *  FROM products_catalog_attributes_marketplaces WHERE product_catalog_id = ? AND id_atributo = ? AND int_to=?";
		$query = $this->db->query($sql,array($id_product,$id_atributo,$int_to));
		return $query->row_array();
	}
	
	public function associateIfNotExist($product_catalog_id, $catalog_id )
	{
		$sql = 'SELECT * FROM catalogs_products_catalog WHERE product_catalog_id=? AND catalog_id=?';
		$query = $this->db->query($sql,array($product_catalog_id,$catalog_id));
		$exist = $query->row_array();
		if (!$exist) { // adiciona os faltosos
			$catalogs_products_data = array(
            	'catalog_id' => $catalog_id,
            	'product_catalog_id' => $product_catalog_id
        	);
       	 	$group_data = $this->db->insert('catalogs_products_catalog', $catalogs_products_data);
		}	
		
	}

    public function getCatalogByProduct($product_id)
    {
        $sql = "SELECT c.name, attribute_value FROM products p 
                JOIN catalogs_products_catalog cpc ON p.product_catalog_id = cpc.product_catalog_id 
                JOIN catalogs c ON cpc.catalog_id = c.id
                WHERE p.id = ?";
        $query = $this->db->query($sql, array($product_id));

        return $query->row_array();
    }
	
	public function disableProductsMaximumDiscount($product_catalog_id, $discount)
	{
		$sql = "UPDATE products SET status=2 WHERE status=1 AND situacao =2 AND product_catalog_id = ? AND maximum_discount_catalog < ?";
        return $this->db->query($sql, array($product_catalog_id, $discount));
		
	}
	
	public function getProductsCatalogByRefId($refId)
    {
        $sql = "SELECT *  FROM products_catalog WHERE ref_id = ? ";
		$query = $this->db->query($sql,array($refId));
		return $query->result_array();
    }
	
	public function getProductsCatalogByRefIdAndCatalogId($refId, $catalog_id)
    {
        $sql = "SELECT p.* FROM products_catalog p, catalogs_products_catalog c WHERE p.id = c.product_catalog_id AND ref_id = ? AND c.catalog_id =?";
		$query = $this->db->query($sql,array($refId, $catalog_id));
		return $query->result_array();
    }
	
	public function getProductCatalogByVariant($product_catalog_id, $variant)
	{
		if($product_catalog_id) {
            $sql = "SELECT * FROM products_catalog WHERE prd_principal_id = ? and variant_id = ? ";
            $query = $this->db->query($sql, array($product_catalog_id, $variant));
            return $query->row_array();
        }
		return false ;
	}

    public function getAllDataBySkuManufacturer(string $brand_code = null)
    {
        if($brand_code) {
            $sql = "SELECT * FROM products_catalog WHERE brand_code = ?";
            $query = $this->db->query($sql, array($brand_code));
            return $query->result_array(); // usado result_array, pois pode retornar mais que um
        }
        return false;
    }

    public function getProductProductDataByBrandAndEan(int $catalog_id, int $brand_id, string $ean): array
    {
        return $this->db->select('pc.*, cpc.catalog_id')
            ->where(
            array (
                    'pc.EAN' => $ean,
                    'pc.brand_id' => $brand_id,
                    'cpc.catalog_id' => $catalog_id
                )
            )
            ->join('catalogs_products_catalog cpc', 'cpc.product_catalog_id = pc.id')
            ->get('products_catalog pc')
            ->result_array();
    }

    public function getProductProductDataByBrand(int $catalog_id, int $brand_id): array
    {
        return $this->db->where(
            array (
                    'pc.brand_id' => $brand_id,
                    'cpc.catalog_id' => $catalog_id
                )
            )
            ->join('catalogs_products_catalog cpc', 'cpc.product_catalog_id = pc.id')
            ->get('products_catalog pc')
            ->result_array();
    }

    public function getProductProductDataByEan(int $catalog_id, string $ean): array
    {
        return $this->db->where(
            array (
                    'pc.EAN' => $ean,
                    'cpc.catalog_id' => $catalog_id
                )
            )
            ->join('catalogs_products_catalog cpc', 'cpc.product_catalog_id = pc.id')
            ->get('products_catalog pc')
            ->result_array();
    }

	public function getSelectQueryToExportProductsByCatalogId($catalog_id, $offset = 0, $limit = null) {
	
		//$sql = "SELECT p.*, {$catalog_id} AS catalog_id  FROM products_catalog p WHERE p.status=1 AND p.id IN (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND catalog_id = $catalog_id) ORDER BY p.id";
			
		$sql = "SELECT p.*, {$catalog_id} AS catalog_id  
        FROM products_catalog p 
        WHERE p.status = 1 
          AND p.id IN (
              SELECT product_catalog_id 
              FROM catalogs_products_catalog 
			  JOIN catalogs ON catalogs.id = catalogs_products_catalog.catalog_id
			  JOIN brands ON brands.id = p.brand_id 
              WHERE product_catalog_id = p.id 
                AND catalog_id = $catalog_id
				AND (catalogs.inactive_products_with_inactive_brands = 0 
					OR (catalogs.inactive_products_with_inactive_brands = 1 AND brands.active = 1)
				)
          ) 
        ORDER BY p.id";


		if (!is_null($limit)) {
			$sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
		}
	
		return $sql;
	}

    public function getListByBrandAndCatalog(int $brand_id, int $catalog_id, int $last_id = 0, int $limit = 500): array
    {
        return $this->db->select('pc.id, cpc.catalog_id')
            ->join('catalogs_products_catalog cpc', 'cpc.product_catalog_id = pc.id')
            ->where(array(
                'cpc.catalog_id' => $catalog_id,
                'pc.brand_id' => $brand_id,
                'pc.id >' => $last_id
            ))
            ->order_by('pc.id', 'ASC')
            ->limit($limit)
            ->get('products_catalog pc')
            ->result_array();
    }

	/**
	 * Gera o SQL para exportar produtos de determinada loja conforme o modelo de exemplo.
	 */
	public function getSelectQueryToExportProductsByStoreId($store_id, $offset = 0, $limit = null)
	{
		// Monta a query.
		$query = $this->db->select('p.id, p.qty, p.store_id, p.maximum_discount_catalog, p.sku, pc.EAN, pc.brand_id, (
			SELECT
				cpc.catalog_id
			FROM
				catalogs_products_catalog cpc
			JOIN catalogs_stores cs ON
				cs.catalog_id = cpc.catalog_id
				AND cs.store_id = p.store_id
			WHERE
				cpc.product_catalog_id = pc.id
				LIMIT 1
			) as catalog_id, p.status')
			->join('products_catalog pc', 'p.product_catalog_id = pc.id')
			->where(['p.store_id' => $store_id])
			->order_by('p.id');

		// Adiciona o limite e offset.
		if (!is_null($limit)) {
			$query = $query->limit($limit, $offset);
		}

		// Busca o select compilado.
		return $query->get_compiled_select("products p");
	}
	
    public function getProductsCatalogByRefIdAndDifferentCatalogId(string $ref_id, int $catalog_id): array
    {
        return $this->db
            ->select('pc.*, cpc.catalog_id')
            ->join('catalogs_products_catalog cpc', 'pc.id = cpc.product_catalog_id')
            ->where(
                array(
                    'pc.ref_id' => $ref_id,
                    'cpc.catalog_id !=' => $catalog_id
                )
            )->get('products_catalog pc')
            ->result_array();
    }
}