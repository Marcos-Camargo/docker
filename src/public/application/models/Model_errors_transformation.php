<?php
class Model_errors_transformation extends CI_Model
{
    private $dbReadOnly;
    public function __construct()
    {
        parent::__construct();
        $this->dbReadOnly = $this->load->database('readonly', TRUE);
    }

    public function getUserConpany():int
    {
        return $this->data['usercomp'];
    }

    /* get the errors_transformation data */
    public function getData($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM errors_transformation WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        $sql = "SELECT * FROM errors_transformation";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('errors_transformation', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }

    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('errors_transformation', $data);
            return ($update == true) ? true : false;
        }
    }

    public function replace($data)
    {
        if($data) {
            $update = $this->db->replace('errors_transformation', $data);
            return ($update == true) ? true : false;
        }
    }

    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('errors_transformation');
            return ($delete == true) ? true : false;
        }
    }

    public function getDataActiveView($offset=0, $sOrder='', $procura='')
    {
        if ($offset=='') {$offset=0;}

        $sql = "SELECT e.*, s.name as store, p.name as name, p.principal_image FROM errors_transformation as e JOIN products as p ON p.id = e.prd_id JOIN stores as s ON s.id = p.store_id WHERE e.status = 0 AND p.status=1 ".$procura.$sOrder." LIMIT 200 OFFSET ".$offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getDataActiveViewCount($procura = '')
    {

        $sql = "SELECT count(*) as qtd FROM errors_transformation as e JOIN products as p ON p.id = e.prd_id JOIN stores as s ON s.id = p.store_id WHERE e.status = 0  AND p.status=1 ".$procura;
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function setStatus($id,$status)
    {
        $sql = "UPDATE errors_transformation SET status = ?  WHERE id = ? ";
        $update = $this->db->query($sql, array($status,$id));

        $search = $this->db->get_where('errors_transformation', ['id' => $id])->row_array();

        /*
        $this->db->where('prd_id', $search['prd_id']);
        $this->db->update('prd_to_integration', ['approved' => 3]);
        */
        $this->db->where('id', $search['prd_id']);
        $this->db->update('products', ['date_update' => date('Y-m-d H:i:s')]);

        $this->db->delete('products_errors', ['product_id' => $search['prd_id']]);

        return $update;
    }


    public function getErrorByFields($sku,$data,$int_to)
    {
        $sql = "SELECT * FROM errors_transformation WHERE skumkt = ? AND date_create = ? AND int_to = ?";
        $query = $this->db->query($sql,array($sku,$data,$int_to));
        return  $query->row_array();
    }

    public function getErrorsByStatus($status,$int_to)
    {
        $sql = "SELECT * FROM errors_transformation WHERE status = ? AND int_to = ?";
        $query = $this->db->query($sql,array($status,$int_to));
        return  $query->result_array();
    }

    public function getErrorsByProductId($prd_id, $int_to = '', ?int $variant = null): array
    {
        if (!is_null($variant)) {
            $this->db->where('variant', $variant);
        }

        if (!empty($int_to)) {
            $this->db->where('int_to', $int_to);
        }

        return $this->db->where(array(
            'status' => 0,
            'prd_id' => $prd_id
        ))->get('errors_transformation')
        ->result_array();
    }

    public function setStatusResolvedByProductId($prd_id, $int_to, $variant = null)
    {
        if (is_null($variant)) {
            $sql = "UPDATE errors_transformation SET status = 2  WHERE prd_id = ? AND int_to = ? AND status=0";
            $update = $this->db->query($sql, array($prd_id, $int_to));
        }
        else {
            $sql = "UPDATE errors_transformation SET status = 2  WHERE prd_id = ? AND int_to = ? AND status=0 AND variant = ?";
            $update = $this->db->query($sql, array($prd_id, $int_to, $variant ));
        }

        return $update;
    }

    public function setStatusResolvedBySkuMkt($skuMkt, $int_to)
    {
        $sql = "UPDATE errors_transformation SET status = 2  WHERE skumkt = ? AND int_to = ? AND status=0";
        return $this->db->query($sql, [$skuMkt, $int_to]);
    }

    public function removeByProductId($prd_id, $int_to, $variant = null)
    {
        if (is_null($variant)) {
            $sql = "DELETE FROM errors_transformation WHERE prd_id = ? AND int_to = ?";
            $delete = $this->db->query($sql, array($prd_id, $int_to));
        }
        else {
            $sql = "DELETE FROM errors_transformation WHERE prd_id = ? AND int_to = ? AND variant = ?";
            $delete = $this->db->query($sql, array($prd_id, $int_to, $variant ));
        }

        return $delete;
    }

    public function setStatusResolvedByProductIdStep($prd_id, $int_to, $step)
    {
        $sql = "UPDATE errors_transformation SET status = 2 WHERE prd_id = ? AND int_to = ? AND step = ? AND status=0";
        $update = $this->db->query($sql, array($prd_id, $int_to, $step));
        return $update;
    }

    public function countErrorsByProductId($prd_id, $int_to)
    {

        $sql = "SELECT count(*) as qtd FROM errors_transformation WHERE status = 0 AND prd_id= ? AND int_to = ?";
        $query = $this->db->query($sql, array($prd_id, $int_to));
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getErrorByProdIdCarrefour($prd_id,$carrefour_import_id)
    {
        $sql = "SELECT * FROM errors_transformation WHERE prd_id = ? AND carrefour_import_id = ? ";
        $query = $this->db->query($sql,array($prd_id,$carrefour_import_id));
        return  $query->row_array();
    }

    public function getDataExport($mkt = '')
    {
        $where = !empty($mkt) ? " AND e.int_to='{$mkt}'" : '';

        $sql = "SELECT 
            e.prd_id as Produto, 
            e.skumkt as SKU_Erro, 
            p.sku as SKU_Produto, 
            s.name as Loja, 
            e.int_to as MarketPlace, 
            e.step as Passo, 
            e.date_create as Data, 
            e.message as Erro 
        FROM errors_transformation as e JOIN products as p ON p.id = e.prd_id JOIN stores as s ON s.id = p.store_id WHERE e.status = 0 " . $where;
        $query = $this->db->query($sql);
        return $query->result_array();
    }


    public function getErrorsByProductIdVariant($prd_id, $int_to, $variant = null)
    {

        if (is_null($variant) == '') {
            $sql = "SELECT * FROM errors_transformation WHERE status = 0 AND prd_id= ? AND int_to = ? and variant is null";
            $query = $this->db->query($sql,array($prd_id, $int_to));
        } else {
            $sql = "SELECT * FROM errors_transformation WHERE status = 0 AND prd_id= ? AND int_to = ? and variant = ?";
            $query = $this->db->query($sql,array($prd_id, $int_to, $variant));
        }
        return  $query->result_array();
    }

    public function getAllStores():array
    {
        $sql = "SELECT DISTINCT s.id,s.name AS loja FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE active = ? AND p.company_id = ? ORDER BY loja";
        $query = $this->db->query($sql, array(1, $this->getUserConpany()));
        return $query->result_array();
    }

    public function getProductsWithoutImage():array
    {
        $param = "(principal_image is NULL or principal_image = '')";
        $sql = " SELECT count(company_id) AS total FROM products p JOIN errors_transformation e ON p.id = e.prd_id WHERE e.status = 0 AND p.status = 1 AND ". $param;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsWithoutCategory():array
    {
        $param = '[""]';
        $sql = " SELECT COUNT(company_id) AS total FROM products p JOIN errors_transformation e ON p.id = e.prd_id WHERE e.status = 0 AND p.status = 1 AND category_id = '$param' ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsWithoutPrice():array
    {
        $param = '';
        $sql = " SELECT COUNT(price) AS total FROM products p JOIN errors_transformation e ON p.id = e.prd_id WHERE e.status = 0 AND p.status = 1 AND price = '$param' ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsWithoutDimensions():array
    {
        $sql = 'SELECT COUNT(price) AS total FROM products p JOIN errors_transformation e ON p.id = e.prd_id WHERE e.status = 0 AND p.status = 1 AND';
        $sql .= '(peso_bruto = "" OR largura = "" OR altura = "" OR profundidade = "" OR products_package = "" OR peso_liquido = "" )';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsWithoutDescription():array
    {
        $param = '';
        $sql = "SELECT COUNT(description) AS total FROM products p JOIN errors_transformation e ON p.id = e.prd_id WHERE e.status = 0 AND p.status = 1 AND description = '$param'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getErrosActiveWithProductActiveAndComplete(string $date, int $limit = 500, int $last_id = null, int $store_id = null, int $product_id = null): array
    {
        $this->db->select('e.*')
            ->join('products as p', 'p.id = e.prd_id')
            ->where(
                array(
                    'p.status'          => 1,
                    'p.situacao'        => 2,
                    'e.status'          => 0,
                    'e.date_create <='  => $date
                )
            );

        if ($last_id !== null ) {
            $this->db->where('e.id >', $last_id);
        }
        if ($store_id) {
            $this->db->where('p.store_id', $store_id);
        }
        if ($product_id) {
            $this->db->where('e.prd_id', $product_id);
        }

        return $this->db
            ->limit($limit)
            ->order_by('e.id', 'ASC')
            ->group_by('e.prd_id')
            ->get('errors_transformation as e')
            ->result_array();
    }
}