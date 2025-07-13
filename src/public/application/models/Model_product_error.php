<?php

class Model_product_error extends CI_Model
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

    public function getProductsWithoutImage():array
    {
        $param = "(principal_image is NULL or principal_image = '')";
        $sql = " SELECT count(company_id) AS total FROM products WHERE situacao = ? AND company_id = ? AND ". $param;
        $query = $this->db->query($sql, array(1 ,$this->getUserConpany()) );
        return $query->result_array();
    }

    public function getProductsWithoutCategory():array
    {
        $param = '[""]';
        $sql = " SELECT COUNT(company_id) AS total FROM products WHERE situacao = ? AND company_id = ? AND category_id = '$param' ";
        $query = $this->db->query($sql, array(1 ,$this->getUserConpany()) );
        //$query->result_array();
        //dd($query);
        return $query->result_array();
    }

    public function getProductsWithoutPrice():array
    {
        $param = '';
        $sql = " SELECT COUNT(price) AS total FROM products WHERE situacao = ? AND company_id = ? AND price = '$param' ";
        $query = $this->db->query($sql, array(1, $this->getUserConpany()));
        return $query->result_array();
    }

    public function getProductsWithoutDimensions():array
    {
        $sql = 'SELECT COUNT(price) AS total FROM products WHERE situacao = ? AND company_id = ? AND';
        $sql .= '(peso_bruto = "" OR largura = "" OR altura = "" OR profundidade = "" OR products_package = "" OR peso_liquido = "" )';
        $query = $this->db->query($sql, array(1, $this->getUserConpany()));
        return $query->result_array();
    }

    public function getProductsWithoutDescription():array
    {
        $param = '';
        $sql = "SELECT COUNT(description) AS total FROM products WHERE situacao = ? AND company_id = ? AND description = '$param'";
        $query = $this->db->query($sql, array(1 ,$this->getUserConpany()));
        return $query->result_array();
    }

    public function getAllCategories():array
    {
        $sql = "SELECT id,name FROM categories WHERE active = ?";
        $query = $this->db->query($sql, array(2));
        return $query->result_array();
    }

    public function getAllStores():array
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT DISTINCT s.id,s.name AS loja FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE active = ? " .$more. " ORDER BY loja";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }

    public function getAllIntegrations():array
    {
        $sql = "SELECT id,name FROM integrations WHERE int_type = 'DIRECT' and active = ? ORDER BY name ";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }

    public function getAllBrands():array
    {
        $sql = "SELECT id,name FROM brands WHERE active = ? ORDER BY name ASC";
        $query= $this->db->query($sql, array(1));
        return $query->result_array();
    }

    public function getProductsCount($situacao, $procura = '')
    {

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(distinct(p.id)) as qtd FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= " WHERE p.dont_publish != true AND p.situacao = {$situacao} AND p.status != 3";
        $sql .= $procura . $more;

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getProducts($start = 0, $length = 200, $order_by = '', $procura = '', $situacao)
    {

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        if ($order_by == "") {
            $order_by = 'GROUP BY p.id ORDER BY p.date_create desc ';
        }

        $sql = "SELECT p.*, s.name AS store FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
        $sql .= " WHERE p.dont_publish != true AND situacao = {$situacao} AND p.status != 3";
        $sql .= $procura . $more . " " . $order_by . " LIMIT " . (int) $start . "," . (int) $length;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductDataCount($situacao, $procura = '')
    {

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(distinct(p.id)) as qtd FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id";
        $sql .= " WHERE p.dont_publish != true AND situacao = {$situacao} AND p.status != 3 ";
        $sql .= $procura . $more;

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }
    public function getProductData($start = 0, $id = null, $length = 200, $procura = '', $situacao) : array
    {

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        if($situacao == ''){
            $situacao = 1;
        }

        $filter = null;

        if ($start == '') {
            $start = 0;
        }

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        }

        $userfilter = "";
        if ($filter != "") {
            $filter = substr($filter, 4);
            $userfilter = " AND ";
        }

        $order_by = "";

        if (isset($this->data['orderby'])) {
            $order_by = $this->data['orderby'];
        }

        if ($length == false) {
            $limit = "";
        } else {
            $limit = "LIMIT " . (int) $start . "," . (int) $length;
        }

        $sql = "SELECT p.*, s.name AS store FROM products p 
                LEFT JOIN stores s ON s.id = p.store_id 
                LEFT JOIN prd_to_integration i ON i.prd_id = p.id
                LEFT JOIN errors_transformation et ON et.prd_id = p.id
                WHERE p.dont_publish != true and situacao = {$situacao} AND p.status != 3 {$procura} {$more}";

        if ($order_by != "") {
            $order_by = 'GROUP BY p.id ' . $order_by;
        } else {
            $order_by = 'GROUP BY p.id ORDER BY p.date_create desc ';
        }

        $sql .= $userfilter . $filter . $order_by . " {$limit}";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

}