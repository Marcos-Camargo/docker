<?php
/*

Model de Acesso ao BD para Promoções Logistica

*/

/**
 * @property CI_DB_driver $db
 * @property CI_Session $session
 */

class Model_promotionslogistic extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getRegions()
    {
        $sql = "SELECT 
                    re.Nome as regiao
                    ,re.idRegiao as id_regiao
                FROM regions as re
                ORDER BY regiao";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function getStateRegions($idRegion)
    {
        $sql = "SELECT                     
                     es.CodigoUF as cod_uf
                    ,es.Nome as estado
                    ,es.uf as uf
                FROM states as es
                WHERE es.Regiao = '$idRegion'
                ORDER BY estado";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function getList()
    {
        $sql = "SELECT * FROM logistic_promotion WHERE deleted = 0;";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function getListActive()
    {
        $sql = "SELECT * FROM logistic_promotion WHERE status = 1 AND deleted = 0;";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * Lista as promoções da loja (Minhas Promoções)
     *
     * @return  mixed
     */
    public function getListPromoSeller()
    {
        $storeId = (int)$this->session->userdata('userstore');

        return $this->db->select('lp.*, lps.dt_inactive , lps.active_status')
            ->join('logistic_promotion_stores as lps', 'lps.logistic_promotion_id = lp.id')
            ->where(array(
                'lps.id_stores'     => $storeId,
                'lp.deleted'        => false,
                'seller_accepted'   => true
            ))
            ->get('logistic_promotion as lp')
            ->result_array();
    }
    
    public function getPromoId($id)
    {
        /**
         * id
        , name
        , DATE_FORMAT(dt_start, '%d/%m/%Y') as dt_start
        , DATE_FORMAT(dt_start, '%H:%i:%s') as start_hour
        , DATE_FORMAT(dt_end, '%d/%m/%Y') as dt_end
        , DATE_FORMAT(dt_end, '%H:%i:%s') as end_hour
        , dt_update
        , user
        , status
        , rule
        , criterion_type
        , price_type_value
        , product_value_mim
        , produtct_amonut
        , region
         */
        return $this->db
            ->select("
                *, 
                DATE_FORMAT(dt_start, '%d/%m/%Y') as dt_start, 
                DATE_FORMAT(dt_start, '%H:%i:%s') as start_hour, 
                DATE_FORMAT(dt_end, '%d/%m/%Y') as dt_end, 
                DATE_FORMAT(dt_end, '%H:%i:%s') as end_hour
            ")
            ->where('id', $id)
            ->get('logistic_promotion')
            ->row_array();
    }
    
    public function getRegionPromoId($id)
    {
        $sql = "SELECT logistic_promotion_idregion as region FROM logistic_promotion_region WHERE logistic_promotion_id = '$id';";
        $query = $this->db->query($sql);        
        return $query->result_array();
    }
    
    public function getCategoriesPromoId(int $promotionId): array
    {
        $categories = $this->db->select('id_categorie as id')
            ->where('logistic_promotion_id', $promotionId)
            ->get('logistic_promotion_categories')
            ->result_array();

        return array_map(function ($item){
            return $item['id'];
        }, $categories);
    }

    public function getStoresPromoId(int $promotionId): array
    {
        $stores = $this->db->select('id_stores as id')
            ->where('logistic_promotion_id', $promotionId)
            ->get('logistic_promotion_stores')
            ->result_array();

        return array_map(function ($item){
            return $item['id'];
        }, $stores);
    }

    /**
     * Recupera os produtos aptos para a promoção, conforme a regra de preço mínimo, estoque mínimo e categoria.
     *
     * @param   float    $priceMin   Preço mínimo para participar da promoção.
     * @param   int      $stockMin   Preço mínimo para participar da promoção.
     * @param   int|null $store      Código da loja participante da promoção.
     * @param   string   $categories Categorias que poderão participar da promoção.
     * @param   int|null $product    Código do produto.
     * @return  mixed
     */
    public function getProducts(float $priceMin, int $stockMin, int $store = null, string $categories = '', int $product = null, $filters = array())
    {
        $whereProduct = array(
            'p.price >=' => $priceMin,
            'p.qty >='   => $stockMin,
            'p.status'   => 1, // produto ativo.
            'p.situacao' => 2  // produto completo.
        );

        // Não tem loja, deve pegar da sessão.
        if (empty($store)) {
            if ($this->data['usercomp'] != 1) {
                if ($this->data['userstore'] == 0) {
                    $whereProduct['p.company_id'] = $this->data['usercomp'];
                } else {
                    $whereProduct['p.store_id'] = $this->data['userstore'];
                }
            }
        } else {
            $whereProduct['p.store_id'] = $store;
        }

        $this->db->select('p.*, s.name as store_name')
            ->join('stores as s', 's.id = p.store_id')
            ->where($whereProduct);

        // filtro opcional.
        foreach ($filters as $typeFilter => $filter) {
            $this->db->group_start();
                if ($typeFilter === 'where_not_in') {
                    foreach ($filter as $field => $value) {
                        $this->db->$typeFilter($field, $value);
                    }
                } else {
                    $this->db->$typeFilter($filter);
                }
            $this->db->group_end();
        }

        if(!empty($categories)) {
            $this->db->where_in('p.category_id', explode(',', $categories));
        }

        // Se existe o produto, devo retornar apenas ele.
        if ($product) {
            return $this->db->where('p.id', $product)->get('products as p')->row_array();
        }
        
        return $this->db->get('products as p')->result_array();
    }

    /**
     * Verifica se o produto está apto a participar da promoção.
     *
     * @param   int     $product    Código do produto.
     * @param   float   $priceMin   Preço mínimo para participar da promoção.
     * @param   int     $stockMin   Preço mínimo para participar da promoção.
     * @param   int     $store      Código da loja participante da promoção.
     * @param   string  $categories Categorias que poderão participar da promoção.
     * @return  mixed
     */
    public function checkProductPromotion(int $product, float $priceMin, int $stockMin, int $store, string $categories = '')
    {
        $this->db->select('p.*')->where(
            array(
                'p.id'       => $product,
                'p.store_id' => $store,
                'p.price >=' => $priceMin,
                'p.qty >='   => $stockMin,
                'p.status'   => 1, // produto ativo.
                'p.situacao' => 2  // produto completo.
            )
        );

        if(empty($categories)) {
            $this->db->where_in('p.category_id', explode(',', $categories));
        }

        return $this->db->get('products as p')->row_array();
    }
    
    public function insertInfo($data)
    {
        $insert = $this->db->insert('logistic_promotion', $data);
        $promotionId = $this->db->insert_id();
        return $promotionId;
    }
    
    public function updateInfo($data, $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('logistic_promotion', $data);
        return $update;
    }

    public function insertRegion($data)
    {
        foreach($data as $k => $value){
            $insert = $this->db->insert('logistic_promotion_region', $value);
        }       
        return $insert;
    }
    
    public function updateRegion($data,$id)
    {   
        $sql = "DELETE FROM logistic_promotion_region WHERE logistic_promotion_id = $id;";
        $query = $this->db->query($sql);
        foreach($data as $k => $value){
            $insert = $this->db->insert('logistic_promotion_region', $value);
        }       
        return $insert;
    }
    
    public function insertCategories($data)
    {
        foreach($data as $k => $value){
            $insert = $this->db->insert('logistic_promotion_categories', $value);
        }       
        return $insert;
    }

    public function insertStores(array $data): bool
    {
        return (bool)$this->db->insert_batch('logistic_promotion_stores', $data);
    }

    public function updatePromotionByStore(int $promotion, int $store, array $data): bool
    {
        return (bool)$this->db->where(array('id_stores' => $store, 'logistic_promotion_id' => $promotion))->update('logistic_promotion_stores', $data);
    }
    
    public function updateCategories($data, $id): bool
    {
        $this->db->delete('logistic_promotion_categories', array('logistic_promotion_id' => $id), null, true);

        $insert = false;
        foreach($data as $value){
            $insert = (bool)$this->db->insert('logistic_promotion_categories', $value);
        }       
        return $insert;
    }

    public function updateStoresPromotion(array $data, int $id): bool
    {
        $this->db->delete('logistic_promotion_stores', array('logistic_promotion_id' => $id), null, true);

        $insert = false;
        foreach($data as $value){
            $insert = (bool)$this->db->insert('logistic_promotion_stores', $value);
        }
        return $insert;
    }
    
    public function insertProduct($data)
    {
        foreach($data as $k => $value){
            $insert = $this->db->insert('logistic_promotion_product', $value);
        }       
        return $insert;       
    }

    public function getPromoByStore(int $promotion, int $store): ?array
    {
        return $this->db->where(array('id_stores' => $store, 'logistic_promotion_id' => $promotion))->get('logistic_promotion_stores')->row_array();
    }
    
    public function insertPromoStore($data)
    {
        foreach($data as $k => $value){
            $insert = $this->db->insert('logistic_promotion_stores', $value);
        }       
        return $insert;       
    }
    
    public function removePromoStore($data)
    {
        $sql = "DELETE FROM logistic_promotion_stores WHERE logistic_promotion_id = ? AND id_stores = ?;";        
		$query = $this->db->query($sql, array($data['promotion_id'], $data['store_id']));
        return $query;
    }
   
    public function removeAllProductStore($data)
    {
        $sql = "DELETE FROM logistic_promotion_product WHERE promotion_id = ? AND store_id = ?;";        
		$query = $this->db->query($sql, array($data['promotion_id'], $data['store_id']));        
        return $query;             
    }
    
    public function removeProduct($data)
    {
        $sql = "DELETE FROM logistic_promotion_product WHERE promotion_id = ? AND store_id = ? AND product_id = ?;";        
		$query = $this->db->query($sql, array($data['promotion_id'], $data['store_id'], $data['product_id']));        
        return $query;             
    }

    /**
     * Lista os produtos da promoção da loja.
     *
     * @param   int         $promotion  Código da promoção.
     * @param   int|null    $store      Código da loja.
     * @return  array
     */
    public function getProductPromoList(int $promotion, int $store = null): array
    {
        $where = array('lpp.promotion_id' => $promotion);

        if ($store) {
            $where['lpp.store_id'] = $store;
        }

        return $this->db->select('p.id, p.name, p.sku, p.price, p.qty, lpp.dt_inactive, lpp.active_status, s.name as store_name')
            ->join('logistic_promotion_product as lpp', 'lpp.product_id = p.id')
            ->join('stores as s', 'lpp.store_id = s.id')
            ->where($where)
            ->get('products as p')
            ->result_array();
    }

    public function getPromoStores($storeId)
    {
        $sql = "SELECT logistic_promotion_id as promotion_id FROM logistic_promotion_stores WHERE id_stores = '$storeId';";
        $query = $this->db->query($sql);        
        return $query->result_array();
    }

    /**
     * Lista as promoções disponíveis para as lojas (Promoções)
     *
     * @return mixed
     */
    public function getListPromo()
    {
        $store = $this->session->userdata('userstore');

        return $this->db->select('lp.*')
            ->join('logistic_promotion_stores as lps', 'lps.logistic_promotion_id = lp.id', 'left')
            ->where('lp.deleted', false)
            ->group_start()
                ->or_group_start()
                    ->where('lps.seller_accepted', false)
                    ->where('lps.id_stores', $store)
                ->group_end()
                ->or_where('lps.seller_accepted IS NULL', NULL, FALSE)
            ->group_end()
            ->get('logistic_promotion as lp')
            ->result_array();
          
    }

    public function updateStatus($id,$data)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('logistic_promotion', $data);
        get_instance()->log_data('PromotionLogistic','edit after',json_encode($data),"I");
        return $update == true;
    }

    public function deleted($id,$data)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('logistic_promotion', $data);
        get_instance()->log_data('PromotionLogistic','edit after',json_encode($data),"I");
        return $update == true;
    }

    public function getDataByPromotionAndProduct($promotion, $prd_id)
    {
        return $this->db
            ->get_where('logistic_promotion_product',
                array(
                    'promotion_id'  => $promotion,
                    'product_id'    => $prd_id
                )
            )->row_array();
    }

    /**
     * Inativar produto da promoção.
     *
     * @param   int     $promotion  Código da promoção.
     * @param   int     $product    Código do produto.
     * @param   int     $store      Código da loja.
     * @return  bool
     */
    public function inactivateProduct(int $promotion, int $product, int $store): bool
    {
        $this->db->set('active_status', 0);
        $this->db->set('dt_inactive', 'CURRENT_TIMESTAMP', FALSE);

        $this->db->where(array(
            'promotion_id'  => $promotion,
            'store_id'      => $store,
            'product_id'    => $product
        ));

        return (bool)$this->db->update('logistic_promotion_product');
    }

    /**
     * Inativar loja da promoção.
     *
     * @param   int     $promotion  Código da promoção.
     * @param   int     $store      Código da loja.
     * @return  bool
     */
    public function inactivateStorePromo(int $promotion, int $store): bool
    {
        $this->db->set('active_status', 0);
        $this->db->set('dt_inactive', 'CURRENT_TIMESTAMP', FALSE);

        $this->db->where(array(
            'logistic_promotion_id' => $promotion,
            'id_stores'             => $store
        ));

        return (bool)$this->db->update('logistic_promotion_stores');
    }

    /**
     * Inativar todos os produtos da promoção da loja.
     *
     * @param   int     $promotion  Código da promoção.
     * @param   int     $store      Código da loja.
     * @return  bool
     */
    public function inactivateAllProductsStore(int $promotion, int $store): bool
    {
        $this->db->set('active_status', 0);
        $this->db->set('dt_inactive', 'CURRENT_TIMESTAMP', FALSE);

        $this->db->where(array(
            'promotion_id'  => $promotion,
            'store_id'      => $store
        ));

        return (bool)$this->db->update('logistic_promotion_product');
    }

    public function batchActivate()
    {   
        $sql = "SELECT id FROM logistic_promotion WHERE status = 0 AND dt_inactive IS NULL AND NOW() > dt_start";
        $query1 = $this->db->query($sql); 
        $return = implode(",",(array_column($query1->result_array(), 'id')));

        $sql = "UPDATE logistic_promotion SET status = ?, active_status = ?, dt_active = CURRENT_TIMESTAMP  WHERE status = ? AND dt_inactive IS NULL AND NOW() >= dt_start";
        $query2 = $this->db->query($sql,array(1,1,0));
        return $return;
    }

    public function batchInactivate()
    {   
        $sql = "SELECT id FROM logistic_promotion WHERE status = ? AND active_status = ? AND NOW() > dt_end ";
        $query1 = $this->db->query($sql, array(1,1)); 
        $return = implode(",",(array_column($query1->result_array(), 'id')));

        $sql = "UPDATE logistic_promotion SET status = ?, active_status = ?, dt_inactive = NOW() WHERE status = ? AND active_status = ? AND NOW() > dt_end ";
        $query = $this->db->query($sql, array(0,0,1,1));

        return $return;
    }

    public function batchInactivateStoresbyID($promoId)
    {   
        $sql = "SELECT id_stores FROM logistic_promotion_stores WHERE logistic_promotion_id = ? AND active_status = ?;";
        $query1 = $this->db->query($sql, array($promoId,1)); 
        $return = implode(",",(array_column($query1->result_array(), 'id_stores')));

        //Desativando a promoção de todas as lojas participantes 
        $sql = "UPDATE logistic_promotion_stores SET active_status = ?, dt_inactive = CURRENT_TIMESTAMP WHERE logistic_promotion_id = ? AND active_status = ?;";        
		$query = $this->db->query($sql, array(0, $promoId,1));

        return $return;
    }

    public function batchInactivateItensStoresByPromoId($promoId)
    {   
        //Desativando todos os itens participantes 
        $sql = "UPDATE logistic_promotion_product SET dt_inactive = CURRENT_TIMESTAMP, active_status = ? WHERE promotion_id = ? AND active_status = ?";        
		$query = $this->db->query($sql, array(0, $promoId, 1));
    }

    /**
     * Remove todas as lojas da promoção.
     *
     * @param   int     $promotion  Código da promoção.
     * @return  bool
     */
    public function removeAllStoreByPromotion(int $promotion): bool
    {
        $this->db->where('logistic_promotion_id', $promotion);
        return (bool)$this->db->delete('logistic_promotion_stores');
    }

    /**
     * Remove todas as categorias da promoção.
     *
     * @param   int     $promotion  Código da promoção.
     * @return  bool
     */
    public function removeAllCategoryByPromotion(int $promotion): bool
    {
        $this->db->where('logistic_promotion_id', $promotion);
        return (bool)$this->db->delete('logistic_promotion_categories');
    }

    /**
     * Recupera os produtos da promoção.
     *
     * @param   int     $promotion  Código da promoção.
     * @return  array
     */
    public function getProductsByPromotion(int $promotion): array
    {
        return $this->db->where('promotion_id', $promotion)
            ->get('logistic_promotion_product')
            ->result_array();
    }
}