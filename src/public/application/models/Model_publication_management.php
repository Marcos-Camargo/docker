<?php

class Model_publication_management extends CI_Model
{
    private $dbReadOnly;

    public function __construct()
    {
        parent::__construct();
        $this->dbReadOnly = $this->load->database('readonly', TRUE);
    }

    public function getUserConpany():int
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " p.company_id = " . $this->data['usercomp'] : " p.store_id = " . $this->data['userstore']);

        $searchUserCompany = 'SELECT company_id FROM users where id = ' . $this->session->userdata('id');
        $userCompanyQuery = $this->dbReadOnly->query($searchUserCompany);

        if($more){
           return $userCompany = $this->data['usercomp'];
        }

        return $userCompany = $userCompanyQuery->result_array()[0]['company_id'];
    }

    public function getTotalIncompleteProducts():array
    {
        if($this->getUserConpany() == 1){
            $sql  = ' SELECT COUNT(situacao) AS total FROM products WHERE situacao = ?';
            $query = $this->db->query($sql, array(1));
        }else{
            $plus = ' AND company_id = ? ';
            $sql  = ' SELECT COUNT(situacao) AS total FROM products WHERE situacao = ?'. $plus;
            $query = $this->db->query($sql, array(1 ,$this->getUserConpany()) );
        }
        return $query->result_array();
    }

    public function getTotalIncompleteProductsDetalhe()
    {
        $t1 = $this->getProductsWithoutImage();
        $t2 = $this->getProductsWithoutCategory();
        $t3 = $this->getProductsWithoutPrice();
        $t4 = $this->getProductsWithoutDimensions();
        $t5 = $this->getProductsWithoutDescription();

        $total = array($t1[0]['total'],$t2[0]['total'],$t3[0]['total'],$t4[0]['total'],$t5[0]['total']);
        $total = array_sum($total);

        return $total;
    }

    public function getProductsWithoutImage():array
    {
        if($this->getUserConpany() == 1){
            $param = "(principal_image is NULL or principal_image = '')";
            $sql = " SELECT count(company_id) AS total FROM products WHERE situacao = ? AND ". $param;
            $query = $this->db->query($sql, array(1));
        }else{
            $param = "(principal_image is NULL or principal_image = '')";
            $sql = " SELECT count(company_id) AS total FROM products WHERE situacao = ? AND company_id = ? AND ". $param;
            $query = $this->db->query($sql, array(1 ,$this->getUserConpany()) );
        }
        return $query->result_array();
    }

    public function getProductsWithoutCategory():array
    {
        if($this->getUserConpany() == 1){
            $param = '[""]';
            $sql = " SELECT COUNT(company_id) AS total FROM products WHERE situacao = ? AND category_id = '$param' ";
            $query = $this->db->query($sql, array(1));
        }else{
            $param = '[""]';
            $sql = " SELECT COUNT(company_id) AS total FROM products WHERE situacao = ? AND company_id = ? AND category_id = '$param' ";
            $query = $this->db->query($sql, array(1 ,$this->getUserConpany()) );
        }
        return $query->result_array();
    }

    public function getProductsWithoutPrice():array
    {
        if($this->getUserConpany() == 1) {
            $param = '';
            $sql = " SELECT COUNT(price) AS total FROM products WHERE situacao = ? AND price = '$param' ";
            $query = $this->db->query($sql, array(1));
        }else{
            $param = '';
            $sql = " SELECT COUNT(price) AS total FROM products WHERE situacao = ? AND company_id = ? AND price = '$param' ";
            $query = $this->db->query($sql, array(1, $this->getUserConpany()));
        }
        return $query->result_array();
    }

    public function getProductsWithoutDimensions():array
    {
        if($this->getUserConpany() == 1) {
            $sql = 'SELECT COUNT(price) AS total FROM products WHERE situacao = ? AND';
            $sql .= '(peso_bruto = "" OR largura = "" OR altura = "" OR profundidade = "" OR products_package = "" OR peso_liquido = "" )';
            $query = $this->db->query($sql, array(1));
        }else{
            $sql = 'SELECT COUNT(price) AS total FROM products WHERE situacao = ? AND company_id = ? AND';
            $sql .= '(peso_bruto = "" OR largura = "" OR altura = "" OR profundidade = "" OR products_package = "" OR peso_liquido = "" )';
            $query = $this->db->query($sql, array(1, $this->getUserConpany()));
        }
        return $query->result_array();
    }

    public function getProductsWithoutDescription():array
    {
        if($this->getUserConpany() == 1) {
            $param = '';
            $sql = "SELECT COUNT(description) AS total FROM products WHERE situacao = ? AND description = '$param'";
            $query = $this->db->query($sql, array(1));
        }else{
            $param = '';
            $sql = "SELECT COUNT(description) AS total FROM products WHERE situacao = ? AND company_id = ? AND description = '$param'";
            $query = $this->db->query($sql, array(1 ,$this->getUserConpany()));
        }
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
        if($this->getUserConpany() == 1) {
            $sql = "SELECT DISTINCT s.id,s.name AS loja FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE active = ? ORDER BY loja";
            $query = $this->db->query($sql, array(1));
        }else{
            $sql = "SELECT DISTINCT s.id,s.name AS loja FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE active = ? AND p.company_id = ? ORDER BY loja";
            $query = $this->db->query($sql, array(1, $this->getUserConpany()));
        }
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

    public function getProductData($offset = 0, $id = null, $limit = 200, $paramIndicator = '' , $mkt = '') : array
    {

        if ($offset == '') {
            $offset = 0;
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

        if ($limit == false) {
            $limit = "";
        } else {
            $limit = "LIMIT " . (int)$limit . "  OFFSET {$offset}";
        }

        if($this->getUserConpany() == 1) {
            $sql = 'SELECT p.*, s.name AS loja FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE p.status = 1 AND p.company_id != 0 ' . $paramIndicator;
        }else{
            $sql = 'SELECT p.*, s.name AS loja FROM products p LEFT JOIN stores s ON s.id = p.store_id WHERE p.status = 1 AND p.company_id = ? ' . $paramIndicator;
        }
        if ($order_by != "") {
            $order_by = 'GROUP BY p.id ' . $order_by;
        } else {
            $order_by = 'GROUP BY p.id ORDER BY p.id desc ';
        }

        $sql .= $userfilter . $filter . $order_by . " {$limit}";

        $query = $this->db->query($sql, $this->getUserConpany());
        return $query->result_array();
    }

    public function queryExport($filterSave)
    {
        $paramIndicator = $filterSave;

        if($this->getUserConpany() == 1) {
            $companySelect = 'WHERE p.company_id != 0 ';
        }else{
            $companySelect = 'WHERE p.company_id = ? ';
        }

        $caracterOne = '["';
        $caracterTwo = '"]';

        $sql ="SELECT p.id, p.name, p.sku, p.price, p.qty, p.principal_image, COALESCE(NULL,b.name,'') AS Fabricante, p.description, COALESCE(NULL,c.name,'') AS Categoria, p.status,";
        $sql.="p.EAN, p.codigo_do_fabricante, p.peso_liquido, p.peso_bruto, p.largura, p.altura, p.profundidade,";
        $sql.="p.NCM, s.name AS loja FROM products p LEFT JOIN stores s ON s.id = p.store_id";
        $sql.=" LEFT JOIN categories c ON c.id = REPLACE(REPLACE(p.category_id, '$caracterOne',''),'$caracterTwo','') ";
        $sql.=" LEFT JOIN brands b ON b.id = REPLACE(REPLACE(p.brand_id, '$caracterOne',''),'$caracterTwo','') ". $companySelect . $paramIndicator.'LIMIT 20000';

        $query = $this->dbReadOnly->query($sql, $this->getUserConpany());

        return $query->result_array();

    }

    public function TotalErrosTransform():array
    {
        $sql = "SELECT COUNT(e.prd_id) as total FROM errors_transformation as e JOIN products as p ON p.id = e.prd_id JOIN stores as s ON s.id = p.store_id WHERE e.status = 0 AND p.status=1 LIMIT 200 OFFSET 0";
        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function getProductsErrorTransform():array
    {

        $sql = "SELECT DISTINCT i.name AS marketplace, COUNT(e.prd_id) as total, e.message ";
        $sql .= "FROM errors_transformation e INNER JOIN integrations i ON i.int_to = e.int_to ";
        $sql .= "WHERE e.status = 0 AND i.int_type = 'DIRECT' GROUP BY e.int_to ORDER BY total DESC";
        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function getProductsErrorTransformForMkt(string $postdata = ''):array
    {
        $sql = "SELECT DISTINCT i.name AS marketplace, e.message, COUNT(message) AS total ";
        $sql .= "FROM errors_transformation e INNER JOIN integrations i ON i.int_to = e.int_to ";
        $sql .= "WHERE e.status = 0 AND i.int_type = 'DIRECT' AND i.name = ? GROUP BY message ORDER BY total DESC";
        $query = $this->dbReadOnly->query($sql, array($postdata));
        return $query->result_array();
    }

}