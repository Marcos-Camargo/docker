<?php

require_once APPPATH . "libraries/Microservices/v1/Integration/Price.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Stock.php";

use Microservices\v1\Integration\Stock;
use Microservices\v1\Integration\Price;

/**
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 *
 * @property Stock $ms_stock
 * @property Price $ms_price
 */
class Model_products extends CI_Model
{
    public const COMPLETE_SITUATION = 2;
    public const INCOMPLETE_SITUATION = 1;

    public const ACTIVE_PRODUCT = 1;
    public const INACTIVE_PRODUCT = 2;
    public const DELETED_PRODUCT = 3;
    public const BLOCKED_PRODUCT = 4;

    public const ALL_PRODUCT_STATUS = [
        self::ACTIVE_PRODUCT,
        self::INACTIVE_PRODUCT,
        self::DELETED_PRODUCT,
        self::BLOCKED_PRODUCT,
    ];

    public const CHARACTER_LIMIT_IN_FIELD_SKU = 60;
    public const CHARACTER_LIMIT_IN_FIELD_NAME = 60;
    public const CHARACTER_LIMIT_IN_FIELD_DESCRIPTION = 3000;
    public $CHARACTER_LIMIT_IN_FIELD_NAME = Model_products::CHARACTER_LIMIT_IN_FIELD_NAME;
    public $CHARACTER_LIMIT_IN_FIELD_DESCRIPTION = Model_products::CHARACTER_LIMIT_IN_FIELD_DESCRIPTION;

    public const SYNCED_MKTPLACE = 1;
    public const NOT_SYNCED_MKTPLACE = 0;

    public $allowable_tags = null;

    private $createLog;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_products_marketplace');
        $this->load->model('model_log_products');
        $this->load->model('model_settings');
        $this->load->library('CampaignsV2Logs');
        $this->load->library("Microservices\\v1\\Integration\\Stock", array(), 'ms_stock');
        $this->load->library("Microservices\\v1\\Integration\\Price", array(), 'ms_price');
        $this->createLog = new CampaignsV2Logs();

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }
    }

    /**
     * Atualizar o preço do produto em camapnha no microsserviço.
     *
     * @param   string          $marketplace    Apelico do marketplace.
     * @param   int             $product_id     Código ID do produto.
     * @param   int|string|null $variant        Ordem da variação.
     * @param   float           $price          Preço do produto em campanha.
     * @param   float|null      $list_price     "Preço de" do produto em campanha.
     * @return  void
     */
    protected function updateCampaignPriceMicroservice(string $marketplace, int $product_id, $variant, float $price, float $list_price = null)
    {
        $variant = $variant === '' ? null : $variant;

        try {
            if ($this->ms_price->use_ms_price) {
                $this->ms_price->updateCampaignPrice($product_id, $variant, $marketplace, $price, $list_price);
            }
        } catch (Exception $exception) {
            // Se der erro, por enquanto, não faz nada.
        }
    }

    /**
     * Excluir o preço do produto de campanha no microsserviço.
     *
     * @param   string          $marketplace    Apelico do marketplace.
     * @param   int             $product_id     Código do produto.
     * @param   int|string|null $variant        Ordem da variação.
     * @return  void
     */
    protected function deleteCampaignPriceMicroservice(string $marketplace, int $product_id, $variant = null)
    {
        $variant = $variant === '' ? null : $variant;

        try {
            if ($this->ms_price->use_ms_price) {
                $this->ms_price->deleteCampaignPrice($marketplace, $product_id, $variant);
            }
        } catch (Exception $exception) {
            // Se der erro, por enquanto, não faz nada.
        }
    }

    /**
     * Atualizar o preço e/ou estoque do produto no microsserviço.
     *
     * @param   int|null        $product_id Código ID do produto.
     * @param   int|string|null $variant    Ordem da variação.
     * @param   array           $data       Dados de atualização.
     * @param   int|null        $var_id     Código ID da variação.
     * @param   string|null     $var_sku    Código SKU da variação.
     * @return  void
     */
    protected function updatePriceAndStockMicroservice(int $product_id, $variant, array $data, int $var_id = null, string $var_sku = null)
    {
        if (empty($data['price']) && empty($data['list_price']) && empty($data['qty'])) {
            return;
        }

        $variant = $variant === '' ? null : $variant;

        if (
            ($this->ms_price->use_ms_price || $this->ms_stock->use_ms_stock) &&
            ($variant === null && ($var_id !== null || $var_sku !== null))
        ) {
            $whereSearchVar = array();
            if ($var_id !== null) {
                $whereSearchVar = array('id' => $var_id);
            }
            else if ($var_sku !== null) {
                $whereSearchVar = array('prd_id' => $product_id, 'sku' => $var_sku);
            }

            if (empty($whereSearchVar)) {
                return;
            }

            $variant = $this->db->where($whereSearchVar)->get('prd_variants')->row_array();

            if (empty($variant)) {
                return;
            }

            $variant = $variant['variant'];
        }

        try {
            if ($this->ms_price->use_ms_price && (!empty($data['price']) || !empty($data['list_price']))) {
                $this->ms_price->updateProductPrice($product_id, $variant, $data['price'] ?? null, $data['list_price'] ?? null);
            }

            if ($this->ms_stock->use_ms_stock && !empty($data['qty'])) {
                $this->ms_stock->updateProductStock($product_id, $variant, $data['qty']);
            }
        } catch (Exception $exception) {
            // Se der erro, por enquanto, não faz nada.
        }
    }

    /* get the Product data */
    public function getProductComplete(string $sku, $comp, $store)
    {
        if ($sku) {
            $sql = "SELECT * FROM products where sku = ? AND company_id = ? and store_id = ?";
            $query = $this->db->query($sql, array($sku, $comp, $store));
            return $query->row_array();
        }
    }

    public function getProductCompleteBySkyAndStore(string $sku, $store)
    {
        if ($sku) {
            $sql = "SELECT * FROM products where sku = ? and store_id = ?";
            $query = $this->db->query($sql, array($sku, $store));
            return $query->row_array();
        }
        return null;
    }

    public function getByProductIdErp(string $product_id_erp)
    {
        if ($product_id_erp) {
            $sql = "SELECT * FROM products where product_id_erp = ?";
            $query = $this->db->query($sql, array($product_id_erp));
            return $query->row_array();
        }
    }

    /* get the Product data */

    public function getProductBySku(string $sku, $cpy)
    {
        if ($sku) {
            $sql = "SELECT * FROM products where sku = ? AND company_id = ?";
            $query = $this->db->query($sql, array($sku, $cpy));
            return $query->row_array();
        }
    }

    public function getProductBySkuAndStore(string $sku, $store_id)
    {
        if ($sku) {
            $sql = "SELECT * FROM products where sku = ? AND store_id = ?";
            $query = $this->db->query($sql, array($sku, $store_id));
            return $query->row_array();
        }
    }

    public function listProduct(int $offset, int $limit): array
    {
        return $this->db->select('p.prazo_fixo,p.prazo_operacional_extra,p.category_id,p.id')
            ->join('stores s', 's.id = p.store_id')
            ->where([
                'p.status !=' => self::DELETED_PRODUCT,
                'p.category_id !=' => '[""]',
                's.active' => true
            ])
            ->order_by('id', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get('products p')
            ->result_array();
    }


    public function listProductJob($id,$days_cross_docking)
    {  
        $cat_id = '["' . $id . '"]';
        return $this->db->select('prazo_fixo,prazo_operacional_extra,category_id,id')
        ->where([ 
            'status !=' => self::DELETED_PRODUCT,
            'category_id' => $cat_id,
            'prazo_operacional_extra !=' => $days_cross_docking
        ])
        ->get('products')
        ->result_array(); 

    }



    public function updatePrazoOperacionalExtra($days_cross_docking,$id)
    {
        if($days_cross_docking) {
            $data = array(
                'prazo_operacional_extra' => $days_cross_docking,
            );
            $this->db->where('id', $id);
            $update = $this->db->update('products', $data);
            return 'editado';
        }
    }


    public function formatMore(&$more)
    {
        if (isset($this->data)) {
            if ((isset($this->data['usercomp']) && $this->data['usercomp'] != 1)) {
                $more['company_id'] = $this->data['usercomp'];
            }
            if ((isset($this->data['userstore']) && $this->data['userstore'] != 0)) {
                $more['store_id'] = $this->data['userstore'];
            }
        }
    }

    public function getAllProducts($more = [])
    {
        $this->formatMore($more);
        $query = $this->db->from('products')->where($more)->get();
        return $query->result_array();
    }

    public function getProductData($offset = 0, $id = null, $limit = 200)
    {

        if ($id) {
            $sql = "SELECT * FROM products where id = ?";
            $query = $this->db->query($sql, array($id));

            /*            echo '<br>sql_L_44';
            print_r($sql);
            die; */

            return $query->row_array();
        }

        if ($offset == '') {
            $offset = 0;
        }

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        } else {
            if ($this->session->has_userdata('productExportFilters')) {
                $filter = $this->session->productExportFilters;
            } else {
                $filter = "";
            }
        }
        if ($filter != "") {
            $filter = substr($filter, 4);
        }
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " p.company_id = " . $this->data['usercomp'] : " p.store_id = " . $this->data['userstore']);

        if (($filter != "") && ($more != "")) {
            $more = " AND " . $more;
        }

        if (($filter != "") or ($more != "")) {
            $filter = "WHERE " . $filter;
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

        $select = "";
        if (isset($this->data['joinCollection'])) {
            $select = ", c.attribute_value";
        }

        //    $sql = "SELECT * FROM products ".$filter.$more." ORDER BY id DESC LIMIT 20 OFFSET ".$offset;
        $sql = "SELECT p.*, s.name AS loja, i.int_to as mkt, i.skumkt, i.approved_curatorship_at, i.published_at $select FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";

        if (isset($this->data['joinCollection'])) {
            $sql .= $this->data['joinCollection'];
        }

        #$sql.= 'WHERE    p.store_id = 10
        if ($order_by != "") {
            // $order_by = 'GROUP BY p.id ORDER BY p.date_create desc ';
            $order_by = 'GROUP BY p.id ' . $order_by;
        } else {
            $order_by = 'GROUP BY p.id ORDER BY p.id desc ';
        }

        $sql .= $filter . $more . " " . $order_by . " {$limit}";
        //get_instance()->MyLog('info', 'model_products sql:'.$sql);

        /*        echo '<br>sql_L_81';
        print_r($sql);
        die; */

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductCount($filter = "")
    {
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        // $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "WHERE 1=1" : (($this->data['userstore'] == 0) ? " WHERE p.company_id = " . $this->data['usercomp'] : " WHERE p.store_id = " . $this->data['userstore']);

        $deletedStatus = self::DELETED_PRODUCT;
        $more .= " AND p.status NOT IN ({$deletedStatus}) ";
        if (($more == "") && ($filter != "")) {
            $filter = "WHERE " . substr($filter, 4);
        }

        if ($filter == '') {
            $sql = "SELECT count(*) as qtd FROM products p ";
            $sql .= $more;
        } else {
            $sql = "SELECT count(distinct(p.id)) as qtd FROM products p ";
            $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
            //  $sql.= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
            //  $sql.= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
            if (isset($this->data['join'])) {
                $sql .= $this->data['join'];
            }
            if (isset($this->data['joinCollection'])) {
                $sql .= $this->data['joinCollection'];
            }
            $sql .= $more . $filter;
        }

        // get_instance()->log_data('Products','count',print_r($sql,true),"I");

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function filter_product($filter = '')
    {
        $more = ($this->data['usercomp'] == 1) ? "WHERE 1=1" : (($this->data['userstore'] == 0) ? " WHERE p.company_id = " . $this->data['usercomp'] : " WHERE p.store_id = " . $this->data['userstore']);
        $sql = "SELECT p.*, s.name AS loja FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
        $sql .= "LEFT JOIN categories c ON p.category_id like CONCAT('[\"', c.id,'\"]') ";
        $sql .= $more . $filter;
        $query = $this->db->query($sql, array());
        return $query->result_array();
    }

    public function get_product_for_omnilogic($hours, $more = [], $order_by = 'p.name', $asc = 'asc')
    {
        $date = new DateTime();
        $newDate = new DateTime();
        $newDate->sub(new DateInterval('P0Y0DT' . $hours . 'H0M'));
        $whereCause = ['omnilogic_date_sent <' => $newDate->format('Y-m-d H:i:s')];
        $result = $this->db
            ->select('p.id, p.sku, p.name, p.omnilogic_date_sent ,c.name as c_name ,s.name as s_name')
            ->from('products p')
            ->where($whereCause)
            ->join('stores s', 'p.store_id = s.id', 'left')
            ->join('categories c', "p.category_id like CONCAT('[\"', c.id,'\"]')", 'left')
            ->like($more)
            ->order_by($order_by, $asc)
            ->get()
            ->result_array();
        return $result;
    }

    public function get_count_filter_product($filter = '')
    {
        $more = ($this->data['usercomp'] == 1) ? "WHERE 1=1" : (($this->data['userstore'] == 0) ? " WHERE p.company_id = " . $this->data['usercomp'] : " WHERE p.store_id = " . $this->data['userstore']);
        $sql = "SELECT count(distinct(p.id)) as qtd, p.*, s.name AS loja FROM products p ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "LEFT JOIN prd_to_integration i ON i.prd_id = p.id ";
        $sql .= "LEFT JOIN errors_transformation et ON et.prd_id = p.id ";
        $sql .= "LEFT JOIN categories c ON p.category_id like CONCAT('[\"', c.id,'\"]') ";
        $sql .= $more . $filter;
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getProductsByCategory(int $category_id): array
    {
        $where = array('category_id' => '["'.$category_id.'"]');
        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $where['company_id'] = $this->data['usercomp'];
            } else {
                $where['store_id'] = $this->data['userstore'];
            }
        }

        return $this->db
            ->from('products')
            ->where($where)
            ->get()
            ->result_array();
    }

    public function getAttributesProductsByCategory($category_id)
    {
        $sql = "select acm.id_atributo, cm.category_id, acm.obrigatorio, acm.nome, 
            acm.int_to, acm.tipo, acm.valor
            from categorias_marketplaces cm
            join atributos_categorias_marketplaces acm on acm.id_categoria = cm.category_marketplace_id 
            where cm.category_id = ". $category_id . " order by acm.int_to, acm.obrigatorio, acm.nome ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProdutosAtributosMarketplaces($prd_id) {
        $sql = "select * from produtos_atributos_marketplaces where id_product = ? ";
        $query = $this->db->query($sql, array($prd_id));
        return $query->result_array();
    }

    public function getProductVariants($id = null, $has_variants='')
    {
        if ($id) {
            $sql = "SELECT * FROM prd_variants where prd_id = ?";
            $query = $this->db->query($sql, array($id));
            $variants = explode(';', $has_variants);
            $result = $query->result_array();
            $j = -1;
            foreach ($result as $row) {
                $j++;
                $fields = explode(';', $row['name']);
                $var_line[$j] = array(
                    'id' => $row['id'],
                    'variant' => $row['variant'],
                    'sku' => $row['sku'],
                    'price' => $row['price'],
                    'list_price' => $row['list_price'] ?? $row['price'],
                    'qty' => $row['qty'],
                    'image' => $row['image'],
                    'status' => $row['status'],
                    'EAN' => $row['EAN'],
                    'codigo_do_fabricante' => $row['codigo_do_fabricante'],
                    'name' => $row['name'],
                    'variant_id_erp' => $row['variant_id_erp']
                );
                $i = 0;
                foreach ($variants as $var) {
                    $var_line[$j][$var] = $fields[$i];
                    $i++;
                }
            }
            $var_line['numvars'] = $j;
            return $var_line;
        }
        return false;
    }

    public function getCount($filter = "")
    {
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        // $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE company_id = " . $this->data['usercomp'] : " WHERE store_id = " . $this->data['userstore']);

        if (($more == "") && ($filter != "")) {
            $filter = "WHERE " . substr($filter, 4);
        }
        $sql = "SELECT count(*) as qtd FROM products " . $more . $filter;
        // get_instance()->log_data('Products','count',print_r($sql,true),"I");

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getActiveShortStock($more = "")
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM products WHERE status = ? " . $more . " ORDER BY qty DESC LIMIT 10";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }

    public function getActiveProductData()
    {
        $sql = "SELECT * FROM products WHERE status = ? ORDER BY id DESC";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }

    public function getProductsVtexPagination($sellerId, $limit = 0, $offset = 10)
    {
        $sql =
            "select p.*, c.import_seller_id, pv.variant, pv.sku as sku_variant, pv.id as variant_id from products p " .
            "join company c on c.id = p.company_id " .
            "join stores s on s.id = p.store_id " .
            "left join prd_variants pv on pv.prd_id = p.id " .
            "where integrate_status = 2 and c.import_seller_id = ? " .
            "LIMIT ?, ?;";
        $query = $this->db->query($sql, array($sellerId, $limit, $offset));
        return $query->result_array();
    }

    public function create($data, $change = 'Criado')
    {
        if ($data) {
            $insert = $this->db->insert('products', $data);
            if ($insert) {
                $prd_id = $this->db->insert_id();

                $this->updatePriceAndStockMicroservice($prd_id, null, $data);

                $this->model_log_products->create_log_products($data, $prd_id, $change);
                $this->load->model('model_products_marketplace'); // cria os preços e estoque por marketplace se não existir
                $prd_mkt = $this->model_products_marketplace->newProduct($prd_id);
                return ($insert == true) ? $prd_id : false;
            }
        }
        return false;
    }

    public function createImage($data)
    {
        if ($data) {
            $insert = $this->db->replace('prd_image', $data);
            return ($insert == true) ? true : false;
        }
        return false;
    }

    public function deleteImage($pathprd)
    {
        if ($pathprd) {
            $this->db->where(array('pathProd' => $pathprd, 'pathVariant' => null));
            $delete = $this->db->delete('prd_image');
            return ($delete == true) ? true : false;
        }
    }

    public function deleteImageVariation($pathProd,$pathVar)
    {
        if ($pathProd) {
            if($pathVar){
                $this->db->where(array('pathProd' => $pathProd, 'pathVariant' => $pathVar));
            } else{
                $this->db->where(array('pathProd' => $pathProd, 'pathVariant !=' => null));
            }
            $delete = $this->db->delete('prd_image');
            return ($delete == true) ? true : false;
        }
    }

    public function createvar($data)
    {
        if ($data) {
            $insert = $this->db->insert('prd_variants', $data);

            $this->updatePriceAndStockMicroservice($data['prd_id'], $data['variant'], $data);

            return ($insert == true) ? true : false;
        }
    }

    public function enrichCategory($data)
    {
        if ($data) {
            $insert = $this->db->replace('products_category_mkt', $data);
            return ($insert == true) ? true : false;
        }
        return false;
    }

    public function insertQueue($prd_id, $int_to = null)
    {

        $data = array(
            'prd_id' => $prd_id,
            'int_to' => $int_to,
        );
        $insert = $this->db->insert('queue_products_marketplace', $data);
        return ($insert == true) ? true : false;
    }

    public function deletevar($id)
    {
        if ($id) {
            $this->db->where('prd_id', $id);
            $delete = $this->db->delete('prd_variants');
            return ($delete == true) ? true : false;
        }
    }

    public function replace($data, $change = 'Trocado')
    {
        if ($data) {
            $insert = $this->db->replace('products', $data);
            $prd_id = $this->db->insert_id();

            $this->updatePriceAndStockMicroservice($prd_id, null, $data);

            $this->model_log_products->create_log_products($data, $prd_id, 'Trocado');
            if (isset($data['id'])) {
                $this->checkKitMinimumStock($data['id']);
            }
            // Acerto o estoque dos kits que este produto faz parte
            $this->load->model('model_products_marketplace'); // cria os preços e estoque por marketplace se não existir
            $prd_mkt = $this->model_products_marketplace->newProduct($prd_id);
            return ($insert == true) ? $prd_id : false;
        }
    }

    public function update($data, $id, $change = 'Alterado')
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('products', $data);

            $this->updatePriceAndStockMicroservice($id, null, $data);

            $prd_mkt = $this->model_products_marketplace->newProduct($id); // cria os preços e estoque por marketplace se não existir

            $this->model_log_products->create_log_products($data, $id, $change . ": new data\n" . json_encode($data, JSON_UNESCAPED_UNICODE));

            $this->checkKitMinimumStock($id); // Acerto o estoque dos kits que esrteproduto faz parte
            return ($update == true) ? true : false;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('products');
            $this->deletevar($id);
            $this->deletekit($id);
            return ($delete == true) ? true : false;
        }
    }

    public function countTotalProducts($more = "")
    {

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE company_id = " . $this->data['usercomp'] : " WHERE store_id = " . $this->data['userstore']);

        $ignoreStatus = implode(',', [Model_products::DELETED_PRODUCT]);
        $more = !empty($more) ? "{$more} AND " : 'WHERE';
        $more = "{$more} status NOT IN({$ignoreStatus})";

        $sql = "SELECT count(*) as qtd FROM products {$more}";
        //$this->session->set_flashdata('success', $sql);
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function countTotalProductsActive($more = "")
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE p.company_id = " . $this->data['usercomp'] : " WHERE p.store_id = " . $this->data['userstore']);
        if ($more == "") {
            $more .= " where s.active=1 and p.situacao=2 and status=1";
        } else {
            $more .= " AND s.active=1 and p.situacao=2 and status=1";
        }
        $sql = "select count(*) as qtd from products p left join stores s on s.id=p.store_id" . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function ean_check1($ean)
    {
        $ean = strrev($ean);
        // Split number into checksum and number
        $checksum = substr($ean, 0, 1);
        $number = substr($ean, 1);
        $total = 0;
        for ($i = 0, $max = strlen($number); $i < $max; $i++) {
            if (($i % 2) == 0) {
                $total += ($number[$i] * 3);
            } else {
                $total += $number[$i];
            }
        }
        $mod = ($total % 10);
        $calculated_checksum = (10 - $mod);
        if ($calculated_checksum == $checksum) {
            return true;
        } else {
            return false;
        }
    }

    public function ean_check($barcode)
    {
        if ($barcode == '') {
            //verifica se é obrigatório neste sellecenter. Se é não pode ser em branco !
            $require_ean = ($this->model_settings->getStatusbyName('products_require_ean') == 1);
            return !$require_ean;
        }

        return $this->isValidBarcode($barcode);
        // check to see if barcode is 13 digits long
        if (!preg_match("/^[0-9]{13}$/", $barcode)) {
            return false;
        }

        $digits = $barcode;

        // 1. Add the values of the digits in the
        // even-numbered positions: 2, 4, 6, etc.
        $even_sum = $digits[1] + $digits[3] + $digits[5] +
            $digits[7] + $digits[9] + $digits[11];

        // 2. Multiply this result by 3.
        $even_sum_three = $even_sum * 3;

        // 3. Add the values of the digits in the
        // odd-numbered positions: 1, 3, 5, etc.
        $odd_sum = $digits[0] + $digits[2] + $digits[4] +
            $digits[6] + $digits[8] + $digits[10];

        // 4. Sum the results of steps 2 and 3.
        $total_sum = $even_sum_three + $odd_sum;

        // 5. The check character is the smallest number which,
        // when added to the result in step 4, produces a multiple of 10.
        $next_ten = (ceil($total_sum / 10)) * 10;
        $check_digit = $next_ten - $total_sum;

        // if the check digit and the last digit of the
        // barcode are OK return true;
        if ($check_digit == $digits[12]) {
            return true;
        }

        return false;
    }

    public function updatePriceAndStock($id, $price, $qty)
    {
        $data = array(
            'price' => $price,
            'qty' => $qty,
        );

        $this->updatePriceAndStockMicroservice($id, null, $data);

        return $this->update($data, $id);
    }

    public function updatePriceAndStockVariant($id, $price, $qty)
    {
        $data = array(
            'price' => $price,
            'qty' => $qty,
        );
        /*
        $this->db->where('id', $id);
        return $this->db->update('prd_variants', $data);
         */
        return $this->updateProductVar($data, $id, $change = 'Alterado Variação');
    }

    public function reduzEstoque($id, $qty, $variant = null, $order_id = null)
    {
        $prod = $this->getProductData(0, $id);
        $qty_update = (int)$prod['qty'] - (int)$qty;
        $sql = "UPDATE products SET qty = " . $qty_update . ", stock_updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $id;
        $update = $this->db->query($sql);
        $log_array = array('id' => $id, 'old qty' => (int)$prod['qty'], 'new qty' => $qty_update, 'order_id' => $order_id, 'itens order' => $qty);
        get_instance()->log_data('Products', 'Stock New Order', json_encode($log_array), "I");

        $log_products_array = array(
            'prd_id' => $id,
            'qty' => $qty_update,
            'price' => $prod['price'],
            'username' => 'SYSTEM',
            'change' => 'Reduziu estoque de ' . $prod['qty'] . ' para ' . $qty_update . ' devido ao pedido ' . $order_id . ' que comprou ' . $qty . ' itens.',
        );
        $this->model_log_products->create($log_products_array);

        $this->updatePriceAndStockMicroservice($id, $variant, array('qty' => $qty_update));

        if ($prod['has_variants'] != '') {
            $sql = "SELECT * FROM prd_variants where prd_id = ? AND variant = ?";
            $query = $this->db->query($sql, array($id, $variant));
            $variant = $query->row_array();
            $qty_update = (int)$variant['qty'] - (int)$qty;
            $sql = "UPDATE prd_variants SET qty = " . $qty_update . " WHERE id = " . $variant['id'];
            $update = $this->db->query($sql);

            $log_array = array('id' => $id, 'variant_id' => $variant['id'], 'variant' => $variant['variant'], 'old qty' => (int)$variant['qty'], 'new qty' => $qty_update, 'order_id' => $order_id, 'itens order' => $qty);
            get_instance()->log_data('Products Variant', 'Stock New Order', json_encode($log_array), "I");

            $log_products_array = array(
                'prd_id' => $id,
                'qty' => $qty_update,
                'price' => $prod['price'],
                'username' => 'SYSTEM',
                'change' => 'Reduziu estoque da variante ' . $variant['id'] . ' de ' . $variant['qty'] . ' para ' . $qty_update . ' devido ao pedido ' . $order_id . ' que comprou ' . $qty . ' itens.',
            );
            $this->model_log_products->create($log_products_array);
        }

        //  acerta o estoque dos kits que este produt faz parte
        $this->checkKitMinimumStock($id, $order_id);

        return ($update == true) ? true : false;
    }

    public function adicionaEstoque($id, $qty, $variant = null, $order_id = null)
    {
        $prod = $this->getProductData(0, $id);
        $qty_update = (int)$prod['qty'] + (int)$qty;
        $sql = "UPDATE products SET qty = " . $qty_update . ", stock_updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $id;
        $update = $this->db->query($sql);
        $log_array = array('id' => $id, 'old qty' => (int)$prod['qty'], 'new qty' => $qty_update, 'order_id' => $order_id, 'itens order' => $qty);
        get_instance()->log_data('Products', 'Stock Cancel Order', json_encode($log_array), "I");

        $log_products_array = array(
            'prd_id' => $id,
            'qty' => $qty_update,
            'price' => $prod['price'],
            'username' => 'SYSTEM',
            'change' => 'Devolveu o estoque de ' . $prod['qty'] . ' para ' . $qty_update . ' devido ao pedido cancelado ' . $order_id . ' que comprou ' . $qty . ' itens.',
        );
        $this->model_log_products->create($log_products_array);

        $this->updatePriceAndStockMicroservice($id, $variant, array('qty' => $qty_update));

        if ($prod['has_variants'] != '') {
            $sql = "SELECT * FROM prd_variants where prd_id = ? AND variant = ?";
            $query = $this->db->query($sql, array($id, $variant));
            $variant = $query->row_array();
            $qty_update = (int)$variant['qty'] + (int)$qty;
            $sql = "UPDATE prd_variants SET qty = " . $qty_update . " WHERE id = " . $variant['id'];
            $update = $this->db->query($sql);
            $log_array = array('id' => $id, 'variant_id' => $variant['id'], 'variant' => $variant['variant'], 'old qty' => (int)$variant['qty'], 'new qty' => $qty_update, 'order_id' => $order_id, 'itens order' => $qty);
            get_instance()->log_data('Products Variant', 'Stock Cancel Order', json_encode($log_array), "I");

            $log_products_array = array(
                'prd_id' => $id,
                'qty' => $qty_update,
                'price' => $prod['price'],
                'username' => 'SYSTEM',
                'change' => 'Devolveu o estoque da variante ' . $variant['id'] . ' de ' . $variant['qty'] . ' para ' . $qty_update . ' devido ao pedido cancelado ' . $order_id . ' que comprou ' . $qty . ' itens.',
            );
            $this->model_log_products->create($log_products_array);
        }
        //  acerta o estoque dos kits que este produt faz parte
        $this->checkKitMinimumStock($id, $order_id);

        return ($update == true) ? true : false;
    }

    public function getProductsByCompany($company_id)
    {
        $sql = "SELECT * FROM products WHERE company_id =?";
        $query = $this->db->query($sql, $company_id);
        return $query->result_array();
    }

    public function checkIfSkuExists($company_id, $store_id, $sku)
    {

        $sql = "SELECT * FROM products WHERE company_id =? and store_id =? and sku = ? ";
        $query = $this->db->query($sql, array($company_id, $store_id, $sku));
        return ($query->num_rows() > 0);
    }

    public function getIfSkuExists($company_id, $store_id, $sku)
    {

        $sql = "SELECT * FROM products WHERE company_id =? and store_id =? and sku = ? ";
        $query = $this->db->query($sql, array($company_id, $store_id, $sku));
        return $query->row();
    }

    public function count_prd_variant_by_prod_id($prod_id)
    {

        $sql = "SELECT * FROM prd_variants WHERE prd_id =? ";
        $query = $this->db->query($sql, array($prod_id));
        return $query->num_rows();
    }

    public function getProdutosIntegracao($offset = 0, $orderby = '', $procura = '')
    {
        $sql = "SELECT pi.*,p.sku as sku, p.name AS produto, p.category_id AS categoria FROM prd_to_integration pi ";
        $sql .= " LEFT JOIN products p ON p.id =pi.prd_id ";

        $sql = "SELECT pi.*,p.sku as sku, p.name as produto, p.category_id as category_id, s.name as loja, c.name AS categoria FROM products p ";
        $sql .= ' RIGHT JOIN prd_to_integration pi on p.id =pi.prd_id ';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id';
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' WHERE pi.status_int = 2 ';

        /*
        SELECT pi.*,p.name, p.category_id, s.name as loja, c.name AS categoria FROM products p
        RIGHT JOIN prd_to_integration pi on p.id =pi.prd_id
        LEFT JOIN stores s on s.id = p.store_id
        LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)

         *
         */

        $sql .= $procura . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getPrdToIntegrationMeli($prd_id)
    {
        $sql = "SELECT * FROM prd_to_integration where status_int = 2 and prd_id = ? AND int_to in('ML', 'MLC')";
        $query = $this->db->query($sql, array($prd_id));
        return $query->row_array();
    }

    public function getCountProdutosIntegracao($procura = '')
    {
        $sql = "SELECT count(*) as qtd FROM prd_to_integration WHERE status_int = 2 ";
        if ($procura != '') {
            $sql = "SELECT count(*) as qtd FROM products p ";
            $sql .= ' RIGHT JOIN prd_to_integration pi on p.id =pi.prd_id ';
            $sql .= ' LEFT JOIN stores s on s.id = p.store_id';
            $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
            $sql .= ' WHERE pi.status_int = 2 ' . $procura;
        }
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getListIncomplet()
    {
        $sql = "select id, p.category_id, p.image from products p where situacao = 1 and status = 1";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function markComplete($prd_id)
    {
        $sql = 'UPDATE products SET situacao=2 WHERE id= ?';
        $update = $this->db->query($sql, array($prd_id));
    }

    public function countTotalProductsIncomplet()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM products WHERE situacao = 1 and status=1 " . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function countTotalProductsWithoutStock()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $ignoreStatus = implode(',', [Model_products::DELETED_PRODUCT]);
        $more = "{$more} AND status NOT IN({$ignoreStatus})";

        $sql = "SELECT count(*) as qtd FROM products WHERE qty = 0 {$more}";
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    /**
     * Total de produtos com alto estoque
     *
     * @param int|null $more
     * @return int
     */
    public function getProductHighStock($usercomp = null, $return_count = true)
    {
        $dateStart = date('Y-m-d', strtotime('-30 days'));
        $dateEnd = date('Y-m-d');

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND products.company_id = " . $this->data['usercomp'] : " AND products.store_id = " . $this->data['userstore']);

        // $whereCompProd = $usercomp !== null && $usercomp <> 1 ? "AND c.company_id = {$usercomp}" : "";
        $selector = "orders_item.product_id,";
        $selector .= "(SELECT products.qty FROM products WHERE products.id = orders_item.product_id {$more}) as qty_product,";
        $selector .= "(SUM(orders_item.qty) * 2) as dois_meses_estoque";

        $join = "JOIN orders_item ON orders.id = orders_item.order_id";
        $tables = "orders {$join}";

        $where = "orders.date_time BETWEEN '{$dateStart}' AND '{$dateEnd}'";
        $where .= ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND orders.company_id = " . $this->data['usercomp'] : " AND orders.store_id = " . $this->data['userstore']);

        $groupBy = "orders_item.product_id";
        $having = "qty_product > dois_meses_estoque";

        $sql = "SELECT {$selector} FROM {$tables} WHERE {$where} GROUP BY {$groupBy} HAVING {$having}";
        $query = $this->db->query($sql);

        if ($return_count) {
            return $query->num_rows();
        }

        return $query;
    }

    /**
     * Total de produtos fora de preço
     *
     * @param string $more
     * @return int
     */
    public function getProductsOutOfPrice($more = "", $return_count = true)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $ignoreStatus = implode(',', [Model_products::DELETED_PRODUCT]);
        $more = "{$more} AND status NOT IN({$ignoreStatus})";
        $sql = "SELECT * FROM prd_to_integration WHERE status_int = 11 {$more} GROUP BY prd_id";
        $query = $this->db->query($sql);
        if ($return_count) {
            return $query->num_rows();
        }

        return $query;
    }

    /**
     * Total de produtos com baixo estoque
     *
     * @param string $more
     * @return int
     */
    public function getProductsLowStock($more = "", $return_count = true)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM prd_to_integration WHERE status_int = 12 " . $more . " GROUP BY prd_id";
        $query = $this->db->query($sql);
        if ($return_count) {
            return $query->num_rows();
        }

        return $query;
    }

    /**
     * Total de produtos publicados
     *
     * @param string $more
     * @return int
     */
    public function getProductsPublished($more = "", $return_count = true)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM prd_to_integration WHERE status_int = 2 AND status=0" . $more . " GROUP BY prd_id";
        $query = $this->db->query($sql);
        if ($return_count) {
            return $query->num_rows();
        }

        return $query;
    }

    public function getProductsCategoriesData()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : " AND p.company_id = " . $this->data['usercomp'];
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT DISTINCT c.id as id, c.name AS name FROM products p ";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= " WHERE p.category_id != '' AND p.category_id != '[\"\"]' " . $more;
        $sql .= " ORDER BY c.name ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsBrandsData()
    {
        //$more = ($this->data['usercomp'] == 1) ? "": " AND p.company_id = ".$this->data['usercomp'];
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT DISTINCT b.id as id, b.name AS name FROM products p ";
        $sql .= ' LEFT JOIN brands b on b.id = left(substr(p.brand_id,3),length(p.brand_id)-4)';
        $sql .= " WHERE p.brand_id != ''  AND p.brand_id != '[\"\"]' " . $more;
        $sql .= " ORDER BY b.name ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getMyProductsPromotionsData()
    {
        //$more = ($this->data['usercomp'] == 1) ? "": " AND p.company_id = ".$this->data['usercomp'];
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $productsfilter = $this->session->userdata('productsfilter');

        $sql = "SELECT p.*, c.name AS category, s.name AS store FROM products p";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
        $sql .= " WHERE p.status = 1 AND p.situacao = 2 AND p.is_kit = 0  "; //só os produtos que estão ativos. E que não estejam já em uma promoção e não sejam kit
        $sql .= " AND p.id NOT IN (SELECT pr.product_id FROM promotions pr WHERE p.id = pr.product_id AND (pr.active != 2))";
        $sql .= $more . $productsfilter;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductWithCategoryStoreData($id)
    {

        $sql = "SELECT p.*, c.name AS category, s.name AS store FROM products p";
        $sql .= ' LEFT JOIN categories c on c.id = left(substr(p.category_id,3),length(p.category_id)-4)';
        $sql .= ' LEFT JOIN stores s on s.id = p.store_id ';
        $sql .= " WHERE p.id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function getProductsCompleteNoVarsData($offset = 0, $procura = '', $orderby = '')
    {
        if ($offset == '') {
            $offset = 0;
        }
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT p.*, s.name AS loja FROM products p ";
        $sql .= " LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= " WHERE p.has_variants = '' AND p.status=1 AND p.situacao = 2 AND p.is_kit = 0 " . $more . $procura . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsCompleteNoVarsCount($procura = '')
    {
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM products p WHERE p.has_variants = '' AND p.status=1 AND p.situacao = 2 AND p.is_kit = 0 " . $more;
        } else {
            $sql = "SELECT count(*) as qtd FROM products p ";
            $sql .= " LEFT JOIN stores s ON s.id=p.store_id ";
            $sql .= " WHERE p.has_variants = '' AND p.status=1 AND p.situacao = 2 AND p.is_kit = 0 " . $more . $procura;
        }
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function createKit($data)
    {
        if ($data) {
            $insert = $this->db->insert('products_kit', $data);
            $prd_id = $this->db->insert_id();

            //$this->updatePriceAndStockMicroservice($prd_id, null, $data);

            $this->model_log_products->create_log_products($data, $prd_id, 'Criado');
            return ($insert == true) ? $prd_id : false;
        }
    }

    public function getProductsKit($product_id)
    {
        $sql = "SELECT pk.*, p.sku AS sku, p.name AS name, p.qty AS qty_item, p.status as status, p.price AS original_price FROM products_kit pk ";
        $sql .= " LEFT JOIN products p ON p.id = pk.product_id_item ";
        $sql .= " WHERE pk.product_id=? ORDER BY CAST(pk.price AS DECIMAL(12,2)) DESC ";
        $query = $this->db->query($sql, array($product_id));
        return $query->result_array();
    }

    public function getAllProductsKitByItem($product_id_item)
    {
        $sql = "SELECT pk.*, p.sku AS sku, p.name AS name, p.qty AS qty_item, p.status as status FROM products_kit pk ";
        $sql .= " LEFT JOIN products p ON p.id = pk.product_id ";
        $sql .= " WHERE pk.product_id_item=?";
        $query = $this->db->query($sql, array($product_id_item));
        return $query->result_array();
    }

    public function checkKitMinimumStock($product_id, $order_id = null)
    {
        $productsKit = $this->getAllProductsKitByItem($product_id);

        // pegos todos os kits que este produto faz parte e acerto o estoque dele se precisar
        foreach ($productsKit as $product_kit) {
            // acerto a quantidade máxima de kits no produto
            $qty = -10000000;
            $products_items = $this->getProductsKit($product_kit['product_id']);
            foreach ($products_items as $product_item) {
                if (($qty == -10000000) || ($qty > intdiv($product_item['qty_item'], $product_item['qty']))) {
                    $qty = intdiv($product_item['qty_item'], $product_item['qty']);
                }
                if ($product_item['status'] != 1) {
                    // se tem um produto inativo, inativo o kit
                    $sql = 'UPDATE products SET status=2 WHERE id= ?';
                    $update = $this->db->query($sql, array($product_kit['product_id']));
                }
            }
            $sql = 'UPDATE products SET qty=? WHERE id= ?';
            $update = $this->db->query($sql, array($qty, $product_kit['product_id']));

            $this->updatePriceAndStockMicroservice($product_kit['product_id'], null, array('qty' => $qty));

            if (is_null($order_id)) {
                $log_products_array = array(
                    'prd_id' => $product_kit['product_id'],
                    'qty' => $qty,
                    'price' => "-",
                    'username' => 'SYSTEM',
                    'change' => 'Estoque alterado para ' . $qty . ' pois o estoque do produto pai ' . $product_id . ' foi reduzido ',
                );
            } else {
                $log_products_array = array(
                    'prd_id' => $product_kit['product_id'],
                    'qty' => $qty,
                    'price' => "-",
                    'username' => 'SYSTEM',
                    'change' => 'Estoque alterado para ' . $qty . ' pois o estoque do produto pai ' . $product_id . ' foi reduzido no pedido ' . $order_id,
                );
            }
            $this->model_log_products->create($log_products_array);
        }
    }

    public function deletekit($id)
    {
        if ($id) {
            $this->db->where('product_id', $id);
            $delete = $this->db->delete('products_kit');
            $this->db->where('product_id_item', $id);
            $delete = $this->db->delete('products_kit');
            return ($delete == true) ? true : false;
        }
    }

    public function updatePriceProductKitItem($product_id, $product_id_item, $price)
    {
        $sql = 'UPDATE products_kit SET price=? WHERE product_id=? AND product_id_item=?';
        $update = $this->db->query($sql, array($price, $product_id, $product_id_item));
        $this->model_log_products->create_log_products(array('price' => $price), $product_id, 'Alterado preço do produto ' . $product_id_item);
        return ($update == true) ? true : false;
    }

    public function update_price($id, $price)
    {
        $sql = 'UPDATE products SET price=? WHERE id=?';
        $update = $this->db->query($sql, array($price, $id));

        $this->updatePriceAndStockMicroservice($id, null, array('price' => $price));

        $this->model_log_products->create_log_products(array('price' => $price), $id, 'Alterado');
        return ($update == true) ? true : false;
    }

    public function getVariants($id = null, $variant = null)
    {
        if ($id) {
            if (!is_null($variant)) {
                $sql = "SELECT * FROM prd_variants where prd_id = ? AND variant = ?";
                $query = $this->db->query($sql, array($id, $variant));
                $result = $query->row_array();
                return $result;
            } else {
                $sql = "SELECT * FROM prd_variants where prd_id = ?";
                $query = $this->db->query($sql, array($id));
                $result = $query->result_array();
                return $result;
            }
        }
        return false;
    }

    public function getVariantSku($id = null, $sku = null)
    {
        if ($id) {
            $sql = "SELECT * FROM prd_variants where prd_id = ? AND sku = ?";
            $query = $this->db->query($sql, array($id, $sku));
            $result = $query->row_array();
            return $result;
        }
        return false;
    }

    public function getVariantSkuOrder($id = null, $variant = null)
    {
        if ($id) {
            $sql = "SELECT * FROM prd_variants where prd_id = ? AND variant = ?";
            $query = $this->db->query($sql, array($id, $variant));
            $result = $query->row_array();
            return $result;
        }
        return false;
    }

    public function getVariantByPrdIdAndIDErp($prd_id, $id_erp)
    {
        return $this->db->select(['id', 'qty', 'price'])->from('prd_variants')->where(array('prd_id' => $prd_id, 'variant_id_erp' => $id_erp))->get()->row_array();
    }

    public function getVariationIdErpForSkuAndSkuVarAndStore($sku, $skuVar, $store)
    {
        return $this->db
            ->select('products.product_id_erp ,prd_variants.variant_id_erp')
            ->from('products')
            ->join('prd_variants', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'products.sku' => $sku,
                    'products.store_id' => $store,
                    'prd_variants.sku' => $skuVar
                )
            )
            ->get()
            ->row_array();
    }

    public function getVariantByPrdIdAndSku($prd_id, $sku)
    {
        return $this->db->select(['id', 'qty', 'price'])->from('prd_variants')->where(array('prd_id' => $prd_id, 'sku' => $sku))->get()->row_array();
    }

    public function updateProductVar($data, $var_id, $change = 'Alterado Variação')
    {
        $this->db->where('id', $var_id);
        $update = $this->db->update('prd_variants', $data);
        // leio a variant para gravar o log da variant
        $variant = $this->db->select('*')->from('prd_variants')->where(array('id' => $var_id))->get()->row_array();

        $this->updatePriceAndStockMicroservice($variant['prd_id'], $variant['variant'], $data);

        $change .= ' variação: ' . $variant['variant'];
        $this->model_log_products->create_log_products($data, $variant['prd_id'], $change);
        $this->update(array('date_update' => date('Y-m-d H:i:s')), $variant['prd_id'], $change);
        return ($update == true) ? true : false;
    }

    public function getVariantsByProd_id($prod_id)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('prd_id' => $prod_id))->order_by('variant', 'ASC')->get()->result_array();
    }

    public function getVariantsByProd_idAndName($prod_id, $name)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('prd_id' => $prod_id, 'name' => $name))->get()->result_array();
    }

    public function getVariantsByProd_idAndVariant($prod_id, $variant)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('prd_id' => $prod_id, 'variant' => $variant))->get()->row_array();
    }

    public function getVariantionById($variantionId)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('id' => $variantionId))->get()->row_array();
    }

    public function getVariantsByProd_idAndVariant_id_erp($prod_id, $variant_id_erp)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('prd_id' => $prod_id, 'variant_id_erp' => $variant_id_erp))->get()->row_array();
    }

    public function getVariantsByVariant_id_erp($variant_id_erp)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('variant_id_erp' => $variant_id_erp))->get()->row_array();
    }

    public function getVariantsByProd_idAndSku($id = null, $sku = null)
    {
        if ($id && $sku) {
            $sql = "SELECT * FROM prd_variants where prd_id = ? AND sku = ?";
            $query = $this->db->query($sql, array($id, $sku));
            $result = $query->row_array();
            return $result;
        }
        return false;
    }

    public function getProductByVarSkuAndStore($varSku, $storeId)
    {
        return $this->db->select('p.*')
            ->from('products p')
            ->join('prd_variants pv', 'p.id = pv.prd_id')
            ->where([
                'pv.sku' => $varSku,
                'p.store_id' => $storeId
            ])->get()->row_array();
    }

    public function get_variant_by_prod_id_and_name($prod_id = null, $name = null)
    {
        $sql = "SELECT * FROM prd_variants where prd_id = ? AND name = ?";
        $query = $this->db->query($sql, array($prod_id, $name));
        $result = $query->row_array();
        return $result;
    }

    public function get_variant_by_prod_id_and_sku($prod_id = null, $sku = null)
    {
        $sql = "SELECT * FROM prd_variants where prd_id = ? AND sku = ?";
        $query = $this->db->query($sql, array($prod_id, $sku));
        $result = $query->row_array();
        return $result;
    }

    public function isValidBarcode($barcode)
    {
        // se não precisar verificar o EAN, sempre retornará como um EAN válido
        $require_verify = $this->model_settings->getValueIfAtiveByName('products_verify_ean');
        if ($require_verify === false) {
            return true;
        }

        //checks validity of: GTIN-8, GTIN-12, GTIN-13, GTIN-14, GSIN, SSCC
        //see: http://www.gs1.org/how-calculate-check-digit-manually
        $barcode = (string)$barcode;
        //we accept only digits
        if (!preg_match("/^[0-9]+$/", $barcode)) {
            return false;
        }
        //check valid lengths:
        $l = strlen($barcode);
        if (!in_array($l, [8, 12, 13, 14, 17, 18])) {
            return false;
        }

        //get check digit
        $check = substr($barcode, -1);
        $barcode = substr($barcode, 0, -1);
        $sum_even = $sum_odd = 0;
        $even = true;
        while (strlen($barcode) > 0) {
            $digit = substr($barcode, -1);
            if ($even) {
                $sum_even += 3 * $digit;
            } else {
                $sum_odd += $digit;
            }

            $even = !$even;
            $barcode = substr($barcode, 0, -1);
        }
        $sum = $sum_even + $sum_odd;
        $sum_rounded_up = ceil($sum / 10) * 10;
        return ($check == ($sum_rounded_up - $sum));
    }

    public function getProductsByStore($store_id, $offset = 0, $limit = null, $sku = null, $filters = array(), $return_count = false, $order_by = array('id', 'DESC'))
    {
        $make_join_stores = false;

        $this->db->select($return_count ? 'p.id' : 'p.*, c.name as category_name, b.name as brand_name');

        if (!empty($store_id)) {
            $this->db->where('p.store_id', $store_id);
        }
        if (!is_null($sku)) {
            $this->db->where('p.sku', $sku);
        }

        foreach ($filters as $filter_key => $filter_value) {
            // O valor é nulo, não deve aplicar o filtro.
            if (is_null($filter_value)) {
                continue;
            }

            // A consulta será fila na tabela stores.
            if (likeText("%s.%", $filter_key)) {
                $make_join_stores = true;
            }

            $this->db->where($filter_key, $filter_value);
        }

        // Faz join com a tabela stores.
        if ($make_join_stores) {
            $this->db->join('stores s', 's.id = p.store_id');
        }

        $this->db->join('categories as c', 'c.id = left(substr(p.category_id,3),length(p.category_id)-4)', 'left');
        $this->db->join('brands as b', 'b.id = left(substr(p.brand_id,3),length(p.brand_id)-4)', 'left');

        if (!is_null($limit)) {
            $this->db->limit($limit, $offset);
        }

        $this->db->order_by($order_by[0], $order_by[1]);

        if ($return_count) {
            return $this->db->get('products p')->num_rows();
        }

        if (!is_null($sku)) {
            return $this->db->get('products p')->row_array();
        }

        return $this->db->get('products p')->result_array();
    }

    public function getProductsNotCorreios($offset = 0, $orderby='', $procura='', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT p.*, s.name as store, largura+altura+profundidade as soma, (largura*altura*profundidade/6000) as peso_cubico FROM products p, stores s ";
        $sql .= " WHERE s.id=p.store_id AND (largura >105 OR altura > 105 OR profundidade > 105 or largura+altura+profundidade > 200 or peso_bruto > 30 or (largura*altura*profundidade/6000) > 30) ";
        $sql .= " AND p.id NOT IN (SELECT product_id FROM products_not_correios) ";
        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCountProductsNotCorreios($procura = '')
    {
        $sql = "SELECT count(*) as qtd FROM products p, stores s ";
        $sql .= " WHERE s.id=p.store_id AND (largura >105 OR altura > 105 OR profundidade > 105 or largura+altura+profundidade > 200 or peso_bruto > 30 or (largura*altura*profundidade/6000) > 30)";
        $sql .= " AND p.id NOT IN (SELECT product_id FROM products_not_correios) ";
        $sql .= $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function markAsNotCorreios($product_id, $user_id)
    {
        $data = array('product_id' => $product_id, 'user_id' => $user_id);
        $insert = $this->db->insert('products_not_correios', $data);
    }

    public function getProductsNotCorreiosWithStoresNotAtFreteRapido()
    {
        $sql = "SELECT p.*, s.name AS loja FROM products p, stores s WHERE p.store_id=s.id AND s.fr_cadastro=6 AND p.id in (SELECT product_id FROM products_not_correios) ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getBetterPriceByEan($ean = null, $original_price=0)
    {
        if ($ean) {
            $price = (float)$original_price;
            $sql = "SELECT price FROM products WHERE EAN = ?";
            $query = $this->db->query($sql, array($ean));
            $rows = $query->result_array();
            if (isset($rows)) {
                foreach ($rows as $row) {
                    if ((float)$row['price'] < $price) {
                        $price = (float)$row['price'];
                    }
                }
            }
            return $price;
        }
        return null;
    }

    public function getVariantsForSkuAndStore($sku, $store)
    {
        $sql = "SELECT pv.* FROM products as p JOIN prd_variants as pv ON p.id = pv.prd_id where p.sku = ? AND p.store_id = ?";
        $query = $this->db->query($sql, array($sku, $store));
        return $query->result_array();
    }

    public function getVariantsForSku($product_id, $sku_var)
    {
        if ($product_id && $sku_var) {
            $sql = "SELECT * FROM prd_variants where prd_id = ? AND sku = ?";
            $query = $this->db->query($sql, array($product_id, $sku_var));
            $result = $query->result_array();
            return $result;
        }
        return false;
    }

    public function getProductsKitFromProductItem($product_id)
    {
        $sql = "SELECT * FROM products WHERE id IN (SELECT product_id FROM products_kit WHERE product_id_item = ?) ";
        $query = $this->db->query($sql, array($product_id));
        return $query->result_array();
    }

    public function verifyProductsOfStore($product_id)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);
        $sql = "SELECT * FROM products WHERE id =  ? " . $more;
        $query = $this->db->query($sql, array($product_id));
        return $query->row_array();
    }

    public function getMarketplaceVariantsByFields($int_to, $store_id, $prd_id, $variant)
    {
        $sql = 'SELECT * FROM marketplace_prd_variants WHERE int_to = ?AND store_id = ? AND prd_id = ? AND variant = ?';
        $query = $this->db->query($sql, array($int_to, $store_id, $prd_id, $variant));
        return $query->row_array();
    }

    public function getCountProductsCategory($cat_id)
    {
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        // $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];

        $cat_id = '["' . $cat_id . '"]';

        $sql = "SELECT count(*) AS qtd FROM products WHERE category_id = ? AND status=1 AND situacao = 2";
        // get_instance()->log_data('Products','count',print_r($sql,true),"I");

        $query = $this->db->query($sql, array($cat_id));
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function next_result()
    {
        if (is_object($this->db->conn_id)) {
            return mysqli_next_result($this->db->conn_id);
        }
    }

    public function call_procedure_update_qtd_prod($prd_id)
    {
        $sql = 'call update_price_product_with_variation(?);';
        $query = $this->db->query($sql, array($prd_id));
        $this->next_result();
        return $query->result();
    }

    public function updateVar($data, $prd_id, $variant, $change = "Alterando Variação")
    {
        if ($data && $prd_id && $variant !== null) {
            $this->db->where(array('prd_id' => $prd_id, 'variant' => $variant));
            $update = $this->db->update('prd_variants', $data);
            $this->update(array('date_update' => date('Y-m-d H:i:s')), $prd_id, $change);

            $this->updatePriceAndStockMicroservice($prd_id, $variant, $data);

            $this->model_log_products->create_log_products($data, $prd_id, $change);
            return ($update == true) ? true : false;
        }
    }

    public function updateVarBySku($data, $prd_id, $sku, $change = "Alterando Variação")
    {
        if ($data && $prd_id && $sku !== null) {
            $this->db->where(array('prd_id' => $prd_id, 'sku' => $sku));
            $update = $this->db->update('prd_variants', $data);

            $this->updatePriceAndStockMicroservice($prd_id, null, $data, null, $sku);

            $this->update(array('date_update' => date('Y-m-d H:i:s')), $prd_id, $change);
            $this->model_log_products->create_log_products($data, $prd_id, $change);
            return ($update == true) ? true : false;
        }
    }

    public function getProductsToIntegrateIntoTheVtexIn()
    {
        $sql = "SELECT
                    stores.zipcode,
                    stores.CNPJ,
                    stores.freight_seller,
                    stores.freight_seller_end_point,
                    stores.freight_seller_type,
                    catalogs.name AS catalog,
                    catalogs.name AS int_to,
                    integrations.auth_data,
                    products_catalog.*,
                    products.store_id,
                    products.sku,
                    products.company_id,
                    products.qty,
                    products.largura,
                    products.altura,
                    products.profundidade,
                    products.peso_bruto,
                    products.prazo_operacional_extra,
                    products.id AS prd_id
                FROM products
                LEFT JOIN prd_to_integration ON prd_to_integration.prd_id = products.id
                JOIN products_catalog ON products_catalog.id = products.product_catalog_id
                JOIN catalogs_products_catalog ON catalogs_products_catalog.product_catalog_id = products.product_catalog_id
                JOIN catalogs ON catalogs.id = catalogs_products_catalog.catalog_id
                JOIN stores ON stores.id = products.store_id
                JOIN integrations ON integrations.store_id = stores.id AND integrations.int_to = catalogs.name
                WHERE prd_to_integration.prd_id IS NULL AND qty <> 0";

        // $sql = "SELECT
        //             stores.zipcode,
        //             stores.CNPJ,
        //             stores.freight_seller,
        //             stores.freight_seller_end_point,
        //             stores.freight_seller_type,
        //             catalogs.name AS catalog,
        //             catalogs.name AS int_to,
        //             integrations.auth_data,
        //             products_catalog.*,
        //             products.store_id,
        //             products.sku,
        //             products.company_id,
        //             products.qty,
        //             products.largura,
        //             products.altura,
        //             products.profundidade,
        //             products.peso_bruto,
        //             products.prazo_operacional_extra,
        //             products.id AS prd_id
        //         FROM products
        //         LEFT JOIN prd_to_integration ON prd_to_integration.prd_id = products.id
        //         JOIN products_catalog ON products_catalog.id = products.product_catalog_id
        //         JOIN catalogs_products_catalog ON catalogs_products_catalog.product_catalog_id = products.product_catalog_id
        //         JOIN catalogs ON catalogs.id = catalogs_products_catalog.catalog_id
        //         JOIN stores ON stores.id = products.store_id
        //         JOIN integrations ON integrations.store_id = stores.id AND integrations.int_to = prd_to_integration.int_to
        //         WHERE prd_to_integration.prd_id IS NULL AND qty <> 0";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function updateProductIntegrationStatus($id, $status)
    {
        $dateLastInt = date('Y-m-d H:i:s');
        $sql = "UPDATE prd_to_integration SET status_int = ?, date_last_int = ? WHERE id = ?";
        $query = $this->db->query($sql, array($status, $dateLastInt, $id));
        return $query;
    }

    public function updateProductIntegrationSku($id, $skumkt, $skubling)
    {
        $dateLastInt = date('Y-m-d H:i:s');
        $sql = "UPDATE prd_to_integration SET skumkt = ?, skubling = ?, date_last_int = ? WHERE id = ?";
        $query = $this->db->query($sql, array($skumkt, $skubling, $dateLastInt, $id));
        return $query;
    }

    public function getProductIntegrationSkumkt($skumkt)
    {
        $sql = "SELECT * FROM prd_to_integration WHERE skumkt = ?";
        $query = $this->db->query($sql, array($skumkt));
        return $query->result_array();
    }

    public function getIntegrationId($int_to)
    {
        $sql = "SELECT id FROM integrations WHERE store_id = ? AND int_to = ?";
        $query = $this->db->query($sql, array(0, $int_to));
        return $query->result_array();
    }

    public function insertProductIntegration($data)
    {
        $insert = $this->db->insert('prd_to_integration', $data);
        $prd_id = $this->db->insert_id();
        return ($insert == true) ? $prd_id : false;
    }

    public function insertUltEnvio($data)
    {
        $insert = $this->db->insert('vtex_ult_envio', $data);
        $prd_id = $this->db->insert_id();
        return ($insert == true) ? $prd_id : false;
    }

    public function getProductsByCategoryData($cat_id, $offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }

        $cat_id = '["' . $cat_id . '"]';

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT p.*, s.name AS loja FROM products p, stores s ";
        $sql .= " WHERE s.id=p.store_id AND category_id = ? AND p.status=1 " . $more . $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;
        //$this->session->set_flashdata('error', $sql);
        $query = $this->db->query($sql, array($cat_id));
        return $query->result_array();
    }

    public function getProductsByCategoryCount($cat_id, $procura = '')
    {
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $cat_id = '["' . $cat_id . '"]';

        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM products p WHERE category_id = ? AND  p.status=1 " . $more;
        } else {
            $sql = "SELECT count(*) as qtd FROM products p, stores s ";
            $sql .= " WHERE s.id=p.store_id AND category_id = ? AND p.status=1 " . $more . $procura;
        }
        $query = $this->db->query($sql, array($cat_id));
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getProductErrorsCount($filter = "")
    {
        $more = "WHERE p.status=1 AND e.status=0";
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        // $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more .= ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT e.* FROM errors_transformation e JOIN products p ON p.id = e.prd_id ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= "{$more} {$filter} GROUP BY e.prd_id";
        get_instance()->log_data('Products', 'count', print_r($sql, true), "I");

        $query = $this->db->query($sql, array());
        return $query->num_rows();
        //        return $row['qtd'];
    }

    /* get the Product data */

    public function getProductErrorData($offset = 0, $limit = 200)
    {
        $filter = "";

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        }

        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = "WHERE p.status=1 AND e.status=0";
        $more .= ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $order_by = "";
        if (isset($this->data['orderby'])) {
            $order_by = $this->data['orderby'];
        }

        $limit = "LIMIT " . (int)$limit . " OFFSET {$offset}";

        $sql = "SELECT p.*, s.name AS loja FROM errors_transformation e JOIN products p ON p.id = e.prd_id ";
        $sql .= "LEFT JOIN stores s ON s.id=p.store_id ";
        $sql .= $more . $filter . " GROUP BY e.prd_id " . $order_by . " {$limit}";
        get_instance()->log_data('Products', 'count', print_r($sql, true), "I");
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function viewErrosTransformation($prd_id)
    {
        $more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);

        $sql = "SELECT e.* FROM errors_transformation e JOIN products p ON p.id = e.prd_id";
        $sql .= " WHERE e.status=0 AND e.prd_id = {$prd_id} {$more} ORDER BY id DESC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsToIntegration()
    {
        $sql = "SELECT products_catalog.ref_id, stores.id AS seller_id, stores.zipcode, stores.CNPJ, stores.freight_seller, stores.freight_seller_end_point, stores.freight_seller_type, prd_to_integration.int_to, prd_to_integration.id AS prdIntegration_id, integrations.auth_data, products.* FROM products
                JOIN prd_to_integration ON prd_to_integration.prd_id = products.id
                JOIN stores ON stores.id = products.store_id
                JOIN integrations ON integrations.store_id = stores.id AND integrations.int_to = prd_to_integration.int_to
                JOIN products_catalog ON products_catalog.id = products.product_catalog_id
                WHERE products.date_update > prd_to_integration.date_update";
        // $sql = "SELECT products_catalog.ref_id, stores.id AS seller_id, prd_to_integration.int_to, prd_to_integration.id AS prdIntegration_id, products.* FROM products
        //         JOIN prd_to_integration ON prd_to_integration.prd_id = products.id
        //         JOIN stores ON stores.id = products.store_id
        //         JOIN products_catalog ON products_catalog.id = products.product_catalog_id
        //         WHERE products.date_update > prd_to_integration.date_update";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProductsToIntegrationById($id, $int_to)
    {
        $sql = "SELECT products_catalog.ref_id, stores.id AS seller_id, prd_to_integration.int_to, prd_to_integration.id AS prdIntegration_id, products.*
                FROM products
                JOIN prd_to_integration ON prd_to_integration.prd_id = products.id
                JOIN stores ON stores.id = products.store_id
                JOIN products_catalog ON products_catalog.id = products.product_catalog_id
                WHERE products.id = ? and  prd_to_integration.int_to = ? ";
        $query = $this->db->query($sql, array($id, $int_to));
        return $query->result_array();
    }

    public function getProductsToIntegrationByIdIntTo($id, $int_to)
    {
        $sql = "SELECT prd_to_integration.* FROM prd_to_integration WHERE prd_id = ? and  prd_to_integration.int_to = ? ";
        $query = $this->db->query($sql, array($id, $int_to));
        return $query->row_array();
    }

    public function updateStockInThePrdToIntegration($prd_id, $int_to, $qty)
    {
        $exists = $this->db->get_where('vtex_ult_envio', ['prd_id' => $prd_id, 'int_to' => $int_to]);
        if (!$exists->result_array()) {
            return false;
        }

        $this->db->set('qty_atual', $qty)->where('int_to', $int_to)->where('prd_id', $prd_id)->update('vtex_ult_envio');
        return true;
    }

    public function getDataPrdVariant($id, $variant)
    {
        $sql = "SELECT * FROM prd_variants where prd_id = ? AND variant = ?";
        $query = $this->db->query($sql, array($id, $variant));
        return $query->row_array();
    }

    public function getDataProductsAndVariants($offset, $limit)
    {
        $sql = "SELECT p.*, v.variant, v.qty AS varqty FROM products p LEFT JOIN prd_variants v ON p.id=v.prd_id ORDER BY p.id";
        $sql .= " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function checkSkuAvailable($sku, $store_id, int $prd_id = null)
    {
        $where = "p.store_id = {$store_id}";
        if ($prd_id) {
            $where .= " AND p.id <> {$prd_id}";
        }

        $sql = "SELECT p.id,v.id FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE {$where} AND (p.sku = ? OR v.sku = ?) limit 1";
        $query = $this->db->query($sql, array($sku, $sku));
        return $query->row_array() ? false : true;
    }

    public function getVariantsBySkuAndStore($sku_var, $store_id)
    {
        if ($sku_var && $store_id) {
            $sql = "SELECT v.*, p.name as product_name, p.sku as sku_product FROM prd_variants as v JOIN products as p ON v.prd_id = p.id WHERE v.sku = ? AND p.store_id = ?";
            $query = $this->db->query($sql, array($sku_var, $store_id));
            $result = $query->row_array();
            return $result;
        }
        return false;
    }

    public function VerifyEanUnique($ean, $store_id, $id = null)
    {
        if ($id) {
            $sql = "SELECT p.id AS id FROM products p WHERE p.id != ? AND p.EAN = ? AND p.store_id = ? and p.status in ? LIMIT 1";
            $query = $this->db->query($sql, array($id, (string)$ean, $store_id, [self::ACTIVE_PRODUCT, self::BLOCKED_PRODUCT]));
            $row = $query->row_array();
            if ($row) {
                return ($row['id']);
            }
            $sql = "SELECT v.prd_id AS id FROM prd_variants v WHERE v.EAN =? AND v.prd_id != ? AND v.prd_id in (SELECT id FROM products WHERE store_id = ? AND status IN ?) LIMIT 1" ;
            $query = $this->db->query($sql, array( (string)$ean, $id, $store_id, [self::ACTIVE_PRODUCT, self::BLOCKED_PRODUCT]));
            //$sql = "SELECT p.id AS id FROM products p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.id != ? AND (p.EAN = ? OR v.EAN =?) AND p.store_id = ? and p.status in ?";
            //$query = $this->db->query($sql, array($id, $ean, $ean, $store_id, [self::ACTIVE_PRODUCT, self::BLOCKED_PRODUCT]));
        } else {
            //$sql = "SELECT p.id AS id FROM products p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE (p.EAN = ? OR v.EAN =?) AND p.store_id = ? and p.status in ?";
            //$query = $this->db->query($sql, array($ean, $ean, $store_id, [self::ACTIVE_PRODUCT, self::BLOCKED_PRODUCT]));
            $sql = "SELECT p.id AS id FROM products p WHERE p.EAN = ? AND p.store_id = ? and p.status in ? LIMIT 1";
            $query = $this->db->query($sql, array((string)$ean, $store_id, [self::ACTIVE_PRODUCT, self::BLOCKED_PRODUCT]));
            $row = $query->row_array();
            if ($row) {
                return ($row['id']);
            }
            $sql = "SELECT v.prd_id AS id FROM prd_variants v WHERE v.EAN =? AND v.prd_id in (SELECT id FROM products WHERE store_id = ? AND status IN ?) LIMIT 1" ;
            $query = $this->db->query($sql, array((string)$ean, $store_id, [self::ACTIVE_PRODUCT, self::BLOCKED_PRODUCT]));
        }

        $row = $query->row_array();
        if ($row) {
            return ($row['id']);
        } else {
            return false;
        }

    }

    public function verifyUniqueEanProductVariation($ean, $storeId, $id = 0): array
    {
        if (!empty($id)) {
            $result = $this->verifyUniqueEanProduct($ean, $storeId, $id);
            if (!empty($result))
                return array_merge($result, ['type' => 'product']);
            $result = $this->verifyUniqueEanVariation($ean, $storeId, $id);
            if (!empty($result))
                return array_merge($result, ['type' => 'variation']);
            return [];
        }
        $result = $this->verifyUniqueEanNewProduct($ean, $storeId);
        if (!empty($result))
            return array_merge($result, ['type' => 'product']);
        $result = $this->verifyUniqueEanNewVariation($ean, $storeId);
        if (!empty($result))
            return array_merge($result, ['type' => 'variation']);
        return [];
    }

    public function verifyUniqueEanProduct($ean, $storeId, $id): array
    {
        $sql = "SELECT p.id FROM products p WHERE p.id != ? AND p.EAN = ? AND p.store_id = ? and p.status not in ? LIMIT 1";
        $query = $this->db->query($sql, [
            $id,
            (string)$ean,
            $storeId, [
                self::DELETED_PRODUCT
            ]
        ]);
        return $query->row_array() ?? [];
    }

    public function verifyUniqueEanNewProduct($ean, $storeId): array
    {
        $sql = "SELECT p.id FROM (products p use index (ix_products_01)) WHERE p.EAN = ? AND p.store_id = ? and p.status not in ? LIMIT 1";
        $query = $this->db->query($sql, [
            (string)$ean,
            $storeId, [
                self::DELETED_PRODUCT
            ]
        ]);
        return $query->row_array() ?? [];
    }

    public function verifyUniqueEanVariation($ean, $storeId, $id): array
    {
        // $sql = "SELECT v.id, v.prd_id FROM prd_variants v WHERE v.EAN =? AND v.prd_id != ? AND v.prd_id in (SELECT id FROM products WHERE store_id = ? AND status NOT IN ?) LIMIT 1";

        $sql = "SELECT v.id, v.prd_id 
                FROM prd_variants v
                INNER JOIN  products p on p.id = v.prd_id 
                WHERE v.EAN =? and v.prd_id != ? and p.store_id = ? AND p.status NOT IN ?  
                limit 1";
        $query = $this->db->query($sql, [
            (string)$ean,
            $id,
            $storeId, [
                self::DELETED_PRODUCT
            ]
        ]);
        return $query->row_array() ?? [];
    }

    public function verifyUniqueEanNewVariation($ean, $storeId): array
    {
        //$sql = "SELECT v.id, v.prd_id FROM prd_variants v WHERE v.EAN =? AND v.prd_id in (SELECT id FROM products WHERE store_id = ? AND status NOT IN ?) LIMIT 1";

        $sql = "SELECT v.id, v.prd_id 
                FROM prd_variants v
                INNER JOIN  products p on p.id = v.prd_id 
                WHERE v.EAN =? and p.store_id = ? AND p.status NOT IN ?  
                limit 1";
        $query = $this->db->query($sql, [
            (string)$ean,
            $storeId, [
                self::DELETED_PRODUCT
            ]
        ]);
        return $query->row_array() ?? [];
    }

    public function getProductOrVariation($id, $type): array
    {
        if (strcasecmp($type, 'product') === 0) {
            return $this->db->select(['p.id, p.EAN as ean, p.name, p.sku, \'product\' as type'])
                ->from('products p')
                ->where([
                    'p.id' => (int)$id
                ])->where_not_in('p.status', [self::DELETED_PRODUCT])
                ->get()->row_array() ?? [];
        }
        if (strcasecmp($type, 'variation') === 0) {
            return $this->db->select(['v.id, v.EAN as ean, CONCAT(p.name, CONCAT(" ", p.has_variants, " "), v.name) as name, v.sku, \'variation\' as type'])
                ->from('prd_variants v')
                ->join('products p', 'v.prd_id = p.id')
                ->where([
                    'v.id' => (int)$id
                ])->where_not_in('v.status', [self::DELETED_PRODUCT])
                ->where_not_in('p.status', [self::DELETED_PRODUCT])
                ->get()->row_array() ?? [];
        }
        return [];
    }

    public function updateEstoqueProdutoPai($idProdutoPai)
    {
        $sql = "SELECT sum(qty) as qtyPai from prd_variants pv where pv.prd_id = ?";
        $query = $this->db->query($sql, array($idProdutoPai));
        $row = $query->row_array();
        if (isset($row['qtyPai']) && $row['qtyPai'] > 0) {
            $prod = $this->getProductData(0, $idProdutoPai);
            $qty_update = (int)$row['qtyPai'];
            $sql2 = "UPDATE products SET qty = " . $qty_update . ", stock_updated_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . (int)$idProdutoPai;
            $this->db->query($sql2);
            $log_array = array('id' => $idProdutoPai, 'old qty' => (int)$prod['qty'], 'new qty' => $qty_update);
            get_instance()->log_data('Products', 'New Stock product', json_encode($log_array), "I");

            $log_products_array = array(
                'prd_id' => $idProdutoPai,
                'qty' => $qty_update,
                'price' => $prod['price'],
                'username' => 'SYSTEM',
                'change' => 'Novo estoque de ' . $prod['qty'] . ' para ' . $qty_update . ' devido ao cadastro de uma nova variação ',
            );
            $this->model_log_products->create($log_products_array);

            $this->updatePriceAndStockMicroservice($idProdutoPai, null, array('qty' => $qty_update));
        }
    }

    public function getAttributesCustomProduct($product)
    {
        if ($product) {
            $sql = "SELECT ap.name as name_attr,
                    atv.value as value_attr,
                    ap.id as id_attr
                    FROM attributes_products_value as atv
                    JOIN attributes_products as ap ON atv.id_attr_prd = ap.id
                    WHERE atv.prd_id = ? ORDER BY ap.name";
            $query = $this->db->query($sql, array($product));
            return $query->result_array();
        }
        return [];
    }

    public function getAttributesCustom()
    {
        $sql = "SELECT name as name_attr FROM attributes_products ORDER BY name";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function removeAttributesCustomProduct($product)
    {
        if ($product) {
            return $this->db->query('DELETE FROM attributes_products_value WHERE prd_id = ?', array($product));
        }

        return false;
    }

    public function insertAttributesCustomProduct($product, $name, $value, $user = null): bool
    {
        if ($product && $name && $value) {

            // Ver se o atributo existe
            $sql = "SELECT ap.id FROM attributes_products as ap WHERE ap.name = ?";
            $query = $this->db->query($sql, array($name));
            $user = $user ?? $this->session->userdata();

            if ($query->num_rows() === 0) { // Não existe vou criar e pegar o ID
                $insertAttr = $this->db->insert('attributes_products', array(
                    'name' => $name,
                    'user_created' => $user['id'],
                ));
                if (!$insertAttr) {
                    return false;
                }

                $idAttr = $this->db->insert_id();
            } else // Existe apenas pego o ID
            {
                $idAttr = $query->row_array()['id'];
            }

			$sql = "SELECT * FROM attributes_products_value WHERE id_attr_prd = ? and prd_id = ?";
            $query = $this->db->query($sql, array($idAttr, $product));
			if ($query->num_rows() === 0) { // Não existe vou criar
	            $resp = $this->db->insert('attributes_products_value', array(
	                'id_attr_prd' => $idAttr,
	                'prd_id' => $product,
	                'value' => $value,
	                'user_created' => $user['id'],
	            ));
			}
			else {
				$resp = true;
				$line = $query->row_array();
				if ($line['value'] != $value) {//update rick
					$this->db->where('id_attr_prd', $idAttr);
					$this->db->where('prd_id', $product);
            		$resp = $this->db->update('attributes_products_value', array('value' => $value, 'user_created' => $user['id']));
				}
			}
            return $resp == true;
        }

        return false;
    }

    public function getTotalAttributesByCategory($category_id) {

        $category_id = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);

        $query = $this->db->select('COUNT(*) AS total')
            ->from('atributos_categorias_marketplaces')
            ->where('id_categoria', $category_id)
            ->get();

        return $query->row()->total;
    }

    public function getAttributesFilledByProduct($product_id) {

        $product_id = filter_var($product_id, FILTER_SANITIZE_NUMBER_INT);

        $query = $this->db->select('COUNT(*) AS total')
            ->from('produtos_atributos_marketplaces')
            ->where('id_product', $product_id)
            ->where('valor IS NOT NULL AND valor != ""', NULL, FALSE)
            ->get();

        return $query->row()->total;
    }

    public function updateAttributesCount($product_id) {

        $product = $this->db->select('id, name, sku, category_id')
            ->from('products')
            ->where('id', $product_id)
            ->get()
            ->row();

        if ($product) {

            $totalAttributes = $this->getTotalAttributesByCategory($product->category_id);
            $filledAttributes = $this->getAttributesFilledByProduct($product_id);

            $unfilledAttributes = $totalAttributes - $filledAttributes;

            $data = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category_id,
                'total_attributes_not_filled' => $unfilledAttributes
            ];

            $existing = $this->db->select('1')
                ->from('products_counting_attributes')
                ->where('product_id', $product->id)
                ->get()
                ->row();

            if ($existing) {
                $this->db->where('product_id', $product->id)->update('products_counting_attributes', $data);
            } else {
                $this->db->insert('products_counting_attributes', $data);
            }
        }
    }

    public function getAttributesCustomByProductAndAttrId($product, $attrId)
    {
        if ($product) {
            $sql = "SELECT ap.name as name_attr,
                    atv.value as value_attr,
                    ap.id as id_attr
                    FROM attributes_products_value as atv
                    JOIN attributes_products as ap ON atv.id_attr_prd = ap.id
                    WHERE atv.prd_id = ? AND atv.id_attr_prd = ?";
            $query = $this->db->query($sql, array($product, $attrId));
            return $query->row_array();
        }
        return false;
    }

    public function updateAttributesCustomProduct($product, $attrId, $value)
    {

        if ($product && $value && $attrId) {

            $sql = "UPDATE attributes_products_value SET value = ? WHERE id_attr_prd = ? AND prd_id = ?";
            return $this->db->query($sql, array($value, $attrId, $product));
        }

        return false;
    }

    public function getAttributesCustomByName($name)
    {
        $sql = "SELECT * FROM attributes_products WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }

    public function updateProduct($productId, $data)
    {
        return $this->update($data, $productId);
    }

    public function getProductsForRoutineBatch($offset, $limit, $idDebug = false)
    {
        $lastDateOnTheSellerIndexTable = $this->db->select('date')->order_by('date', 'DESC')->limit(1)->get('seller_index_history')->row();

        $query = $this->db->select(
            [
                'products.id',
                'products.name',
                'products.description',
                'products.status',
                'products.image',
                'sku',
                'products.store_id',
                'category_id',
                'brand_id',
                'stores.service_charge_value',
                'seller_index_history.seller_index'
            ]
        )
            ->join('stores', 'products.store_id = stores.id')
            ->join('seller_index_history', "products.store_id = seller_index_history.store_id AND seller_index_history.date = \"$lastDateOnTheSellerIndexTable->date\"");

        if ($idDebug) {
            $query->where('products.id', $idDebug);
        }

        return $query->where('stores.active', 1)
            ->where_in('products.status', [1, 4])
            ->limit($limit)
            ->offset($offset)
            ->get('products')
            ->result_array();
    }

    public function getProductsExportData($filter, $join, $offset = 0, $limit = null)
    {
        // admin ve tudo. Se tem store 0, ve tudo da empresa, se não, ve tudo só da loja
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " p.company_id = " . $this->data['usercomp'] : " p.store_id = " . $this->data['userstore']);

        if ($more != "") {
            $more = "WHERE {$more} {$filter}";
        } else if ($filter != '') {
            $more = 'WHERE ' . substr($filter, 4);
        }

        $sqllimit = "";
        if (!is_null($limit)) {
            $sqllimit = " LIMIT " . (int)$limit . "  OFFSET " . $offset;
        }

        $sql = "SELECT
                    p.store_id,
                    p.sku,
                    p.EAN,
                    p.name,
                    p.price,
                    p.qty,
                    p.brand_id,
                    p.category_id,
                    p.product_catalog_id,
                    p.image,
                    p.id,
                    p.codigo_do_fabricante,
                    p.peso_liquido,
                    p.peso_bruto,
                    p.largura,
                    p.altura,
                    p.profundidade,
                    p.NCM,
                    p.origin,
                    p.garantia,
                    p.prazo_operacional_extra,
                    p.description,
                    p.status,
                    p.has_variants,
                    s.name AS loja,
                    i.int_to as mkt,
                    i.skumkt
                FROM products p
                LEFT JOIN stores s ON s.id=p.store_id
                LEFT JOIN prd_to_integration i ON i.prd_id = p.id
                {$join}
                {$more}
                {$sqllimit}";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function disableVariationForIdErpOutProduct($id_erp, $prod_id)
    {
        $this->db->update('prd_variants', array('status' => 2), array('prd_id <>' => $prod_id, 'variant_id_erp' => $id_erp));
    }

    public function disableProductByIdErp($id_erp)
    {
        $this->db->update('products', array('status' => 2), array('product_id_erp' => $id_erp));
    }

    public function productHasIntegration($product_id)
    {
        $prod_int = $this->db->select('*')->from('prd_to_integration')
            ->where(array('prd_id' => $product_id))->get()->result_array();
        if (count($prod_int) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getProductByProductCatalogIdAndStore($productCatalog, $store_id)
    {
        $whereData = array('product_catalog_id' => $productCatalog, 'store_id' => $store_id);
        return $this->db->select('*')->from('products')->where($whereData)->get()->row_array();
    }

    public function getPrdVariant($id)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('id' => $id))->get()->row_array();
    }

    public function getAllVariantByPrdIdAndIDErp($prd_id, $id_erp)
    {
        return $this->db->select('*')->from('prd_variants')->where(array('prd_id' => $prd_id, 'variant_id_erp' => $id_erp))->get()->row_array();
    }

    public function getDataProductIntegrationMkt($product_id, $int_to)
    {
        return $this->db->from('prd_to_integration')->where(array('prd_id' => $product_id, 'int_to' => $int_to))->get()->row_array();
    }

    public function updateStockProduct($id, $qty)
    {
        $this->updatePriceAndStockMicroservice($id, null, array('qty' => $qty));

        $data = array('qty' => $qty);
        return $this->update($data, $id);
    }

    public function getByProductIdErpAndStore(string $product_id_erp, $store_id)
    {
        if ($product_id_erp && $store_id) {
            $sql = "SELECT * FROM products where product_id_erp = ? and store_id = ?";
            $query = $this->db->query($sql, array((string)$product_id_erp, $store_id));
            $result = $query->row_array();
            if (empty($result)) {
                return $this->db->select('p.*')
                    ->join('prd_variants pv', 'pv.prd_id = p.id')
                    ->where(array(
                        'pv.variant_id_erp' => $product_id_erp,
                        'p.store_id' => $store_id,
                        'p.is_variation_grouped' => true
                    ))
                    ->get('products p')
                    ->row_array();
            }
            return $result;
        }

        return false;
    }

    public function getByVariantIdErpAndStore(string $variant_id_erp, $store_id)
    {
        if ($variant_id_erp && $store_id) {
            $sql = "SELECT var.*, p.sku AS prdSku FROM products p
                    JOIN prd_variants var ON p.id = var.prd_id
                    WHERE var.variant_id_erp = ? AND p.store_id = ? AND p.has_variants !=''";
            $query = $this->db->query($sql, [$variant_id_erp, $store_id]);
            return $query->row_array();
        }

        return false;
    }

    public function setDateUpdatedProduct($product, $date = null, $method = null, $data_update_ms = array())
    {
        if ($date === null) $date = date('Y-m-d H:i:s');

        if ($method === null) $method = __METHOD__;

        if (!strtotime($date)) return false;

        if (!empty($data_update_ms) && !empty($data_update_ms['price'])) {
            if ($data_update_ms['active'] == 1) {
                $this->updateCampaignPriceMicroservice($data_update_ms['int_to'], $product, null, $data_update_ms['price'], $data_update_ms['list_price']);
            } else if ($data_update_ms['active'] == 0) {
                $this->deleteCampaignPriceMicroservice($data_update_ms['int_to'], $product);
            }
        }

        $this->createLog->log(array('date_update' => $date,'product_id' => $product), $product, 'products', $method);

        return $this->update(array('date_update' => $date), $product);

    }

    public function setDateUpdatedProducts(array $products, $date = null, $method = null)
    {
        if ($date === null) $date = date('Y-m-d H:i:s');

        if ($method === null) $method = __METHOD__;

        if (!strtotime($date)) return false;

        $ids = implode(',', $products);

        $this->createLog->log(array('date_update' => $date,'products_id' => $ids), '', 'products', $method);

        return $this->db->query("UPDATE products SET date_update = '$date' WHERE id IN($ids)");

    }

    public function setDateUpdatedProductsVacation($id)
    { 
        $date = date('Y-m-d H:i:s');
        $this->createLog->log(array('date_update' => $date,'store_id' => $id), '', 'products', __FUNCTION__);

        $query = "UPDATE products SET date_update = ? WHERE store_id = ?";
        return $this->db->query($query, array($date, $id));

    }

    public function searchProductsToCampaign(      $searchString, $participatingComissionFrom, $participatingComissionTo,
                                                   $productMinValue, $productMinQuantity, $minSellerIndex, array $stores = null,
                                             array $categories = null, array $productsIds = null, $limit = 100, $campaignId=null,
                                                    $productVariantIds = null): array
    {

        $participatingComissionFrom = $participatingComissionFrom ?: 0;

        //Se não está fornecendo comissão participante até, vamos entender que qualquer um se enquadra
        $participatingComissionTo = $participatingComissionTo > 0 ? $participatingComissionTo : 100;

        //Comissão participante até não pode ser menor que comissão participante de
        $participatingComissionTo = $participatingComissionTo < $participatingComissionFrom ? $participatingComissionFrom : $participatingComissionTo;

        $productMinValue = $productMinValue ?: 0;
        $productMinQuantity = $productMinQuantity ?: 1;

        $lastDateOnTheSellerIndexTable = $this->db->select('date')->order_by('date', 'DESC')->limit(1)->get('seller_index_history')->row();
        $minSellerIndex = $minSellerIndex ?: 1;

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
            $sql = "SELECT p.id as id, s.name AS store, p.store_id, 
                    CASE 
                        WHEN p.has_variants != '' AND pv.sku IS NOT NULL THEN pv.sku 
                        ELSE p.sku 
                    END as sku, 
                    p.name as name,
                    CASE 
                        WHEN p.has_variants != '' AND pv.name IS NOT NULL THEN pv.name 
                        ELSE NULL 
                    END as variant_name,  
                    CASE 
                        WHEN p.has_variants != '' AND pv.price IS NOT NULL THEN CAST(pv.price AS DECIMAL(12,2)) 
                        ELSE CAST(p.price AS DECIMAL(12,2)) 
                    END as price, 
                    CASE 
                        WHEN p.has_variants != '' AND pv.qty IS NOT NULL THEN pv.qty 
                        ELSE p.qty 
                    END as qty, 
                    REPLACE(REPLACE(REPLACE(p.category_id,'\"',''),']',''),'[','') as category_id,
                    p.has_variants,
                    CASE 
                        WHEN p.has_variants != '' THEN pv.id 
                        ELSE NULL 
                    END as prd_variant_id,
                    CASE 
                        WHEN p.has_variants != '' THEN pv.variant 
                        ELSE NULL 
                    END as variant
                    FROM products AS p 
                    LEFT JOIN prd_variants pv ON (p.id = pv.prd_id AND p.has_variants != '')";
        }else{
            //@todo pode excluir
            $sql = "SELECT DISTINCT p.id as id, s.name AS store, p.store_id, p.sku as sku, p.name as name,  
           CAST(p.price AS DECIMAL(12,2)) as price, p.qty , REPLACE(REPLACE(REPLACE(p.category_id,'\"',''),']',''),'[','') as category_id
                    FROM products AS p ";
        }

        //Loja precisa estar ativa
        $sql.= " JOIN stores AS s ON (s.id = p.store_id AND s.active = '1') ";

        //Seller index precisa estar dentro da regra
        if ($lastDateOnTheSellerIndexTable){
            $sql.= " JOIN seller_index_history sih ON (sih.store_id = p.store_id AND sih.date = '{$lastDateOnTheSellerIndexTable->date}' ) ";
        }

        //Produto precisa estar ativo
        $sql.= " WHERE p.status = 1 ";

        //Must be in prd_to_integration
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
            $sql.= " AND EXISTS (
                        SELECT 1 FROM prd_to_integration
                        WHERE prd_to_integration.prd_id = p.id
                        AND (
                            (p.has_variants != '' AND prd_to_integration.variant = pv.variant)
                            OR (p.has_variants = '' AND prd_to_integration.variant IS NULL)
                        )
                        AND prd_to_integration.store_id = p.store_id
                        AND prd_to_integration.skumkt IS NOT NULL
                        AND prd_to_integration.skumkt <> ''
                        AND prd_to_integration.mkt_sku_id IS NOT NULL
                        AND prd_to_integration.mkt_sku_id <> ''
                    ) ";
        }else{
            //@todo excluir
            $sql.= " AND EXISTS (
                        SELECT 1 FROM prd_to_integration
                        WHERE prd_to_integration.prd_id = p.id
                        AND prd_to_integration.store_id = p.store_id
                        AND prd_to_integration.skumkt IS NOT NULL
                        AND prd_to_integration.skumkt <> ''
                        AND prd_to_integration.mkt_sku_id IS NOT NULL
                        AND prd_to_integration.mkt_sku_id <> ''
                    ) ";
        }

        //Filtrando por comissão participante se usar o filtro
        if ($participatingComissionFrom && $participatingComissionTo){
            $sql.= " AND s.service_charge_value >= $participatingComissionFrom AND s.service_charge_value <= $participatingComissionTo ";
        }

        //Se estiver usando valor min do produto, vamos filtrar
        if ($productMinValue){
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                $sql.= " AND (CASE WHEN p.has_variants != '' AND pv.price IS NOT NULL THEN pv.price ELSE p.price END) >= $productMinValue ";
            } else {
                $sql.= " AND p.price >= $productMinValue ";
            }
        }

        //Se estiver filtrando por quantidade em estoque minima, vamos filtrar
        if ($productMinQuantity){
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                $sql.= " AND (CASE WHEN p.has_variants != '' AND pv.qty IS NOT NULL THEN pv.qty ELSE p.qty END) >= $productMinQuantity ";
            } else {
                $sql.= " AND p.qty >= $productMinQuantity ";
            }
        }

        //Se estiver filtrando por loja, vamos filtrar
        if ($stores){
            $stores = implode(',', $stores);
            $sql.= " AND p.store_id IN ($stores) ";
        }

        //Sempre terá filtro pelo minimo do seller index
        if ($lastDateOnTheSellerIndexTable){
            $sql.= " AND sih.seller_index >= '$minSellerIndex' ";
        }

        //Se estiver buscando uma string, vamos buscar em vários lugares
        if ($searchString){
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                $sql.= " AND (p.id = '$searchString' OR p.name LIKE '%$searchString%' OR p.sku LIKE '%$searchString%' OR p.description LIKE '%$searchString%' OR (p.has_variants != '' AND pv.sku LIKE '%$searchString%')) ";
            } else {
                $sql.= " AND (p.id = '$searchString' OR p.name LIKE '%$searchString%' OR p.sku LIKE '%$searchString%' OR p.description LIKE '%$searchString%') ";
            }
        }

        //Se estiver filtrando por categoria específica
        if ($categories){

            $i=0;
            foreach ($categories as $category){

                $i++;

                if ($i==1){

                    $sql.= " AND (p.category_id = '[\"$category\"]' ";

                }else{
                    $sql.= " OR p.category_id = '[\"$category\"]' ";
                }

            }

            $sql.= " ) ";

        }

        if ($productsIds){

            $productsIds = implode(',', $productsIds);
            $sql.= " AND (p.id IN($productsIds) ";

        }
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $productVariantIds){
            $ids = implode(',', $productVariantIds);
            // Close the product ID condition
            $sql.= " ) ";

            // Add a new condition to filter by specific variants
            $sql.= " AND (p.has_variants = '' OR pv.id IN ($ids)) ";

        }
        // Only add the closing parenthesis if we didn't already close it in the variant condition
        // and if we have product IDs (to avoid unbalanced parentheses)
        if ($productsIds && (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') || !$productVariantIds)) {
            $sql.= " ) "; //Fechamendo do AND (p.id IN
        }

//        if ($campaignId){
//            $sql.= " AND p.id NOT IN (SELECT product_id FROM campaign_v2_products
//            WHERE campaign_v2_products.campaign_v2_id = $campaignId
//            AND (campaign_v2_products.removed = 1 AND campaign_v2_products.auto_removed = 0)) ";
//        }

        $sql.= " ORDER BY p.id ";

        //Sempre limitando em 100 temporariamente até melhor essa busca
        if ($limit){
            $sql.= " LIMIT $limit";
        }

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function searchProductsToMassiveImportCampaign(array $ids, bool $groupById = false, $productVariantIds = null): array
    {
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
            $sql = "SELECT p.id as id, s.name AS store, p.store_id, 
                    CASE 
                        WHEN p.has_variants != '' AND pv.sku IS NOT NULL THEN pv.sku 
                        ELSE p.sku 
                    END as sku, 
                    p.name as name,
                    CASE 
                        WHEN p.has_variants != '' AND pv.name IS NOT NULL THEN pv.name 
                        ELSE NULL 
                    END as variant_name,  
                    CASE 
                        WHEN p.has_variants != '' AND pv.price IS NOT NULL THEN CAST(pv.price AS DECIMAL(12,2)) 
                        ELSE CAST(p.price AS DECIMAL(12,2)) 
                    END as price, 
                    CASE 
                        WHEN p.has_variants != '' AND pv.qty IS NOT NULL THEN pv.qty 
                        ELSE p.qty 
                    END as qty,
                    p.has_variants,
                    CASE 
                        WHEN p.has_variants != '' THEN pv.id 
                        ELSE NULL 
                    END as prd_variant_id,
                    CASE 
                        WHEN p.has_variants != '' THEN pv.variant 
                        ELSE NULL 
                    END as variant
                    FROM products AS p 
                    LEFT JOIN prd_variants pv ON (p.id = pv.prd_id AND p.has_variants != '')";
        } else {
            $sql = "SELECT p.id as id, s.name AS store, p.store_id, p.sku as sku, p.name as name, CAST(p.price AS DECIMAL(12,2)) as price, p.qty 
                    FROM products AS p ";
        }

        $sql.= " JOIN stores AS s ON (s.id = p.store_id AND s.active = '1') ";
        $sql.= " JOIN prd_to_integration ON (prd_to_integration.prd_id = p.id) ";
        $sql.= " WHERE p.status = 1 ";
        $sql.= " AND prd_to_integration.skumkt IS NOT NULL
                AND prd_to_integration.skumkt <> ''
                AND prd_to_integration.mkt_sku_id IS NOT NULL
                AND prd_to_integration.mkt_sku_id <> '' ";
        if ($ids){
            $ids = implode(',', $ids);
            $sql.= " AND ( p.id IN ($ids) ";
        }
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $productVariantIds){
            $ids = implode(',', $productVariantIds);
            $sql.= " OR pv.id IN ($ids) ) "; //Aqui fecha o and p.id pra ter o AND a seguir
            $sql.= " AND (
                            (p.has_variants != '' AND prd_to_integration.variant = pv.variant)
                            OR (p.has_variants = '' AND prd_to_integration.variant IS NULL)
                        ) ";
        } else if ($ids) {
            // Only add the closing parenthesis if we didn't already close it in the variant condition
            // and if we have product IDs (to avoid unbalanced parentheses)
            $sql.= " ) "; //Fechamendo do AND (p.id IN
        }

        if ($groupById){
            $sql.= " GROUP by p.id ";
        }

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function getProductToMassiveImportCampaign(int $id = null, string $sku = null, int $storeId = null): array
    {
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            $sql = "SELECT p.id as id, s.name AS store, p.store_id, 
                    CASE 
                        WHEN p.has_variants != '' AND pv.sku IS NOT NULL THEN pv.sku 
                        ELSE p.sku 
                    END as sku, 
                    p.name as name,
                    CASE 
                        WHEN p.has_variants != '' AND pv.name IS NOT NULL THEN pv.name 
                        ELSE NULL 
                    END as variant_name,  
                    CASE 
                        WHEN p.has_variants != '' AND pv.price IS NOT NULL THEN CAST(pv.price AS DECIMAL(12,2)) 
                        ELSE CAST(p.price AS DECIMAL(12,2)) 
                    END as price, 
                    CASE 
                        WHEN p.has_variants != '' AND pv.qty IS NOT NULL THEN pv.qty 
                        ELSE p.qty 
                    END as qty,
                    p.has_variants,
                    CASE 
                        WHEN p.has_variants != '' THEN pv.id 
                        ELSE NULL 
                    END as prd_variant_id,
                    CASE 
                        WHEN p.has_variants != '' THEN pv.variant 
                        ELSE NULL 
                    END as variant
                    FROM products AS p 
                    LEFT JOIN prd_variants pv ON (p.id = pv.prd_id AND p.has_variants != '')";
        } else {
            $sql = "SELECT p.id as id, s.name AS store, p.store_id, p.sku as sku, p.name as name, CAST(p.price AS DECIMAL(12,2)) as price, p.qty 
                    FROM products AS p ";
        }

        $sql.= " JOIN stores AS s ON (s.id = p.store_id AND s.active = '1') ";

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            $sql.= " JOIN prd_to_integration
                    ON (
                        prd_to_integration.prd_id = p.id
                        AND (
                            (p.has_variants != '' AND prd_to_integration.variant = pv.variant)
                            OR (p.has_variants = '' AND prd_to_integration.variant IS NULL)
                        )
                        AND prd_to_integration.skumkt IS NOT NULL
                        AND prd_to_integration.skumkt <> ''
                        AND prd_to_integration.mkt_sku_id IS NOT NULL
                        AND prd_to_integration.mkt_sku_id <> ''
                    ) ";
        } else {
            $sql.= " JOIN prd_to_integration ON (prd_to_integration.prd_id = p.id) ";
            $sql.= " AND prd_to_integration.skumkt IS NOT NULL
                    AND prd_to_integration.skumkt <> ''
                    AND prd_to_integration.mkt_sku_id IS NOT NULL
                    AND prd_to_integration.mkt_sku_id <> '' ";
        }

        $sql.= " WHERE p.status = 1 ";

        if ($id){
            $sql.= " AND p.id IN ($id) ";
        }

        if ($sku && $storeId){
            $sku = addslashes($sku);
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                $sql.= " AND (p.sku = '{$sku}' OR (p.has_variants != '' AND pv.sku = '{$sku}')) AND p.store_id = $storeId ";
            } else {
                $sql.= " AND p.sku = '{$sku}' AND p.store_id = $storeId ";
            }
        }

        $query = $this->db->query($sql);

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $query->num_rows() > 0) {
            $results = $query->result_array();
            // If we have variants, return all of them
            if (count($results) > 0 && !empty($results[0]['has_variants'])) {
                return $results;
            }
            // Otherwise return the first result
            return $results[0];
        } else {
            $result = $query->row_array();
            return $result ?: [];
        }

    }

    public function updateStatus($productsId, $status)
    {
        if (!in_array($status, [
            self::INACTIVE_PRODUCT,
            self::ACTIVE_PRODUCT,
            self::DELETED_PRODUCT,
            self::BLOCKED_PRODUCT
        ])) {
            return 0;
        }
        get_instance()->log_data('Products', __FUNCTION__, "Products updated status. New status: $status\n".json_encode($productsId));
        $this->db->where_in('id', $productsId);
        $this->db->update('products', [
            'status' => $status
        ]);
        return $this->db->affected_rows();
    }

    public function getProductsByOrderStatus($productIds, $orderStatus = [])
    {
        $this->db->select(
            "products.id, products.sku, products.name, products.status, GROUP_CONCAT(IFNULL(fromkit.id, '') SEPARATOR ',') as prod_ids"
        )
            ->from('products')
            ->join('orders_item', 'products.id = orders_item.product_id')
            ->join('orders', 'orders_item.order_id = orders.id')
            ->join('stores', 'orders.store_id = stores.id')
            ->join('company', 'stores.company_id = company.id')
            ->join('products_kit', 'products.id = products_kit.product_id', 'left')
            ->join('products as fromkit', 'products_kit.product_id_item = fromkit.id', 'left')
            ->where_in('products.id', $productIds)
            ->where_in('orders.paid_status', $orderStatus);

        if ((isset($this->data['usercomp']) && $this->data['usercomp'] != 1)) {
            $this->db->where('company.id', $this->data['usercomp']);
        }
        if ((isset($this->data['userstore']) && $this->data['userstore'] != 0)) {
            $this->db->where('stores.id', $this->data['userstore']);
        }

        $q = $this->db->group_by('products.id')->get();
        return $q->result_array();
    }

    public function updateProductData($id, $data)
    {
        $data['date_update'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);

        $this->updatePriceAndStockMicroservice($id, null, $data);

        return $this->db
            ->update('products', $data);
    }

    public function updateVariationData($varId, $prodId, $data)
    {
        $this->updatePriceAndStockMicroservice($prodId, null, $data, $varId);

        $this->db->where(['id' => $varId, 'prd_id' => $prodId]);
        return $this->db
            ->update('prd_variants', $data);
    }

    public function log($id, $data, $tag)
    {
        $this->model_log_products->create_log_products($data, $id, $tag);
    }

    public function getSelectQueryToExportProductsByCriteria(array $criteria = [], array $orderBy = [], int $offset = 0, int $limit = 100): string
    {
        $selectFields = [
            "prod.*", "store.name as store_name"
        ];
        $groupBy = ['prod.id'];
        if (($criteria['variation'] ?? false)) {
            $selectFields = array_merge($selectFields, [
                'prod_vars.id as variant_id',
                'prod_vars.sku as variant_sku',
                'prod_vars.name as variant_name',
                'prod_vars.qty as variant_qty',
                'prod_vars.price as variant_price',
                'prod_vars.image as variant_image',
                'prod_vars.status as variant_status'
            ]);
            $groupBy = ['prod_vars.id', 'prod.id'];
        }
        return $this->buildSelectQueryToSearchProductsByCriteria($selectFields, $criteria, $orderBy, $groupBy);
    }

    public function getProductsToDisplayByCriteria(array $criteria = [], int $offset = 0, int $limit = 100, array $orderBy = []): array
    {
        $selectFields = [
            "prod.id", "prod.company_id", "prod.store_id", "prod.name", "prod.sku", "prod.price", "prod.qty", "prod.is_kit",
            "prod.product_catalog_id", "prod.situacao", "prod.status", "prod.principal_image", /*"prod.collections",*/
            "store.name as store_name", "GROUP_CONCAT(IF(mktp.int_to=NULL, NULL, mktp.int_to) SEPARATOR ',') AS marketplaces",
            "GROUP_CONCAT(IFNULL(prod_vars.id, NULL) SEPARATOR ',') AS var_ids", "IF(prod_vars.id IS NULL, FALSE, TRUE) AS has_variations",
            "prod.list_price", "prod.id as productId", "prod.has_variants"
        ];
        if (!empty($filters['collections'] ?? []) && !empty(current(array_values($filters['collections'])))) {
            //array_push($selectFields, 'cc.id AS ccId', "cc.name as ccName");
        }
        $query = $this->buildSelectQueryToSearchProductsByCriteria($selectFields, $criteria, $orderBy, ['prod.id']);
        $query = $this->db->query("{$query} LIMIT {$limit} OFFSET {$offset}");
        return $query->result_array() ?? [];
    }

    public function countGetProductsByCriteria(array $criteria = []): int
    {
        $selectFields = [
            "count(DISTINCT prod.id) as count"
        ];
        $query = $this->buildSelectQueryToSearchProductsByCriteria($selectFields, $criteria);
        $query = $this->db->query("{$query}");
        return $query->row_array()['count'] ?? 0;
    }

    protected function buildSelectQueryToSearchProductsByCriteria(array $selectFields = ['prod.*'], array $criteria = [], array $orderBy = [], array $groupBy = []): string
    {
        $filters = $criteria;
        $onJoinStore = 'prod.store_id = store.id';
        $onJoinCompany = 'store.company_id = comp.id';
        if (isset($filters['store_id'])) {
            $onJoinStore = "(prod.store_id = store.id AND store.id = {$filters['store_id']})";
        }
        if (isset($filters['company_id'])) {
            $onJoinCompany = "(store.company_id = comp.id AND comp.id = {$filters['company_id']})";
        }

        $joinMktplace = 'LEFT JOIN prd_to_integration mktp ON mktp.prd_id = prod.id';
        $whereMktPlace = '';
        if (isset($filters['synchronized']) && strlen($filters['synchronized']) > 0) {
            if (((int)$filters['synchronized']) === self::SYNCED_MKTPLACE) {
                $joinMktplace = 'JOIN prd_to_integration mktp ON mktp.prd_id = prod.id';
            } else if (((int)$filters['synchronized']) === self::NOT_SYNCED_MKTPLACE) {
                $whereMktPlace = ' AND mktp.id IS NULL';
            }
        }
        $mktps = implode(',', array_map(function ($mktp) {
            $mktp = htmlspecialchars_decode($mktp);
            return "'{$mktp}'";
        }, $filters['marketplaces'] ?? []));

        if (strlen($filters['status_integration'] ?? '') > 0) {
            if ($filters['status_integration'] == 'not_published') {
                $whereMktPlace = ' AND mktp.id IS NULL';
                if (!empty($mktps)) {
                    $whereMktPlace = " AND IF(mktp.int_to IS NULL, '', mktp.int_to) NOT IN({$mktps})";
                }
            } else if (((int)$filters['status_integration']) >= 0) {
                $joinMktplace = 'JOIN prd_to_integration mktp ON mktp.prd_id = prod.id';
                $joinMktplace .= ' LEFT JOIN errors_transformation err ON err.prd_id = prod.id';
                switch ((int)$filters['status_integration']) {
                    case 30:
                        $whereMktPlace = "err.status = 0";
                        break;
                    case 40:
                        $whereMktPlace = "mktp.ad_link IS NOT NULL";
                        break;
                    case 999:
                        break;
                    default:
                        $whereMktPlace = "mktp.status_int = {$filters['status_integration']}";
                        break;
                }
                if (!empty($mktps)) {
                    $whereMktPlace = !empty($whereMktPlace) ? " AND ({$whereMktPlace} AND mktp.int_to IN({$mktps}))" : " AND mktp._int_to IN({$mktps})";
                }
            }
        } else {
            if (!empty($mktps)) {
                $whereMktPlace = " AND mktp.int_to IN({$mktps})";
            }
        }

        $joinCollection = '';
        $whereCollection = '';
        if (!empty($filters['collections'] ?? []) && !empty(current(array_values($filters['collections'])))) {
            //$idProducColection = $this->getVtexColections(0, implode(',', $filters['collections']))['id'] ?? 0;
            //$whereCollection = " AND replace(replace(replace(replace(prod.collections, '[', ''),']','' ),'\"',''), ' ', '') REGEXP '[[:<:]]{$idProducColection}[[:>:]]'";
            //$joinCollection = "LEFT JOIN catalog_collections cc ON prod.id = cc.id";
        }

        $selectFields = implode(', ', $selectFields);
        $leftJoinVars = 'LEFT JOIN prd_variants AS prod_vars ON prod.id = prod_vars.prd_id';
        $qry = "SELECT {$selectFields}
                FROM products prod 
                JOIN stores store ON {$onJoinStore}
                JOIN company comp ON {$onJoinCompany}
                {$joinMktplace}
                {$leftJoinVars}
                {$joinCollection}
                WHERE prod.id > 0 {$whereMktPlace} {$whereCollection}";

        if (!empty($filters['product_id'] ?? 0)) {
            $qry .= " AND prod.id = '{$filters['product_id']}'";
        }

        if (!empty($filters['products_ids'] ?? 0)) {
            $qry .= sprintf(" AND prod.id IN (%s)", implode(',', $filters['products_ids']));
        }

        if (!empty($filters['sku'] ?? '')) {
            $remove = ['%20','%'];
            $sku = str_replace($remove,' ',$filters['sku']);
            $sku = str_replace('  ',' ',$sku);
            $sku = trim($sku);
            $qry .= " AND prod.sku LIKE '%{$sku}%'";
        }

        if (!empty($filters['product'] ?? '')) {
            $remove = ['%20','%'];
            $product = str_replace($remove,' ',$filters['product']);
            $product = str_replace('  ',' ',$product);
            $product = trim($product);
            $qry .= " AND prod.name LIKE '%{$product}%'";
        }

        if (!empty($filters['search'] ?? '')) {
            $qry .= " AND (prod.name LIKE '%{$filters['search']}%' OR prod.sku LIKE '%{$filters['search']}%'";
            if (is_numeric($filters['search'])) {
                $id = (int)$filters['search'];
                $qry .= " OR prod.id = {$id}";
            }
            $qry .= ")";
        }
        if (!empty($filters['stores'] ?? [])) {
            $stores = implode(',', array_map(function ($store) {
                    return (int)$store;
                }, $filters['stores'])
            );
            $qry .= " AND store.id IN ({$stores})";
        }

        if (!empty($filters['cnpj'] ?? '')) {
            $qry .= " AND replace(replace(replace(store.CNPJ, '/', ''),'.','' ),'-','' )";
            $qry .= " = replace(replace(replace({$filters['cnpj']}, '/', ''),'.','' ),'-','' )";
        }

        if (isset($filters['with_stock'])) {
            $stockCondition = $filters['with_stock'] ? '>' : '<=';
            $qry .= " AND CAST(prod.qty AS DECIMAL) {$stockCondition} 0";
        }

        if (isset($filters['is_kit'])) {
            $qry .= " AND IF(prod.is_kit IS NULL, 0, prod.is_kit) = {$filters['is_kit']} ";
        }

        if (in_array($filters['situation'] ?? 0, [
            self::COMPLETE_SITUATION, self::INCOMPLETE_SITUATION
        ])) {
            $qry .= " AND IF(prod.situacao IS NULL, 0, prod.situacao) = {$filters['situation']} ";
        }

        $filteredStatus = is_array($filters['status'] ?? null) ? $filters['status'] : [$filters['status'] ?? -1];
        $filteredStatus = array_filter($filteredStatus, function ($status) {
            return in_array($status, self::ALL_PRODUCT_STATUS);
        });
        if (!empty($filteredStatus)) {
            $filteredStatus = implode(',', $filteredStatus);
            $qry .= " AND prod.status IN ({$filteredStatus})";
        } else {
            $ignoredStatus = implode(',', [self::DELETED_PRODUCT]);
            $qry .= " AND prod.status NOT IN ({$ignoredStatus})";
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                if (strpos($qry, "prod.{$field}") !== false) {
                    if (strpos($field, "qty") !== false) {
                        $order[] = "CAST(prod.{$field} AS DECIMAL) {$direction}";
                        continue;
                    }
                    $order[] = "prod.{$field} {$direction}";
                }
            }
        }
        $order = !empty($order) ? $order : ['prod.id DESC'];
        $order = implode(', ', $order);

        $groupBy = !empty($groupBy) ? implode(', ', $groupBy) : '';
        $groupBy = !empty($groupBy) ? "GROUP BY {$groupBy}" : '';
        return "{$qry} {$groupBy} ORDER BY {$order}";
    }

    public function getKitsByProdsIds($productIds = [])
    {
        $this->db->select('kit.id')
            ->from('products as kit')
            ->join('products_kit', 'kit.id = products_kit.product_id')
            ->join('products', 'products_kit.product_id_item = products.id')
            ->join('stores', 'products.store_id = stores.id')
            ->join('company', 'stores.company_id = company.id');
            if (!empty($productIds)) {
                $this->db->where('products.id', $productIds);
            }


        if ((isset($this->data['usercomp']) && $this->data['usercomp'] != 1)) {
            $this->db->where('company.id', $this->data['usercomp']);
        }
        if ((isset($this->data['userstore']) && $this->data['userstore'] != 0)) {
            $this->db->where('stores.id', $this->data['userstore']);
        }

        $q = $this->db->group_by('kit.id')->get();
        return $q->result_array();
    }

    public function getPrice(int $productId): ?string
    {
        $this->db->select('p.price')
            ->from('products as p')
            ->where('p.id', $productId);

        $q = $this->db->get();

        $row = $q->row_array();

        if ($row){
            return $row['price'];
        }

        return null;

    }

    public function getVariant(int $productId, int $prdVariantId): ?array
    {
        $this->db->select('pv.price, pv.qty, pv.variant')
            ->from('prd_variants as pv')
            ->where('pv.prd_id', $productId)
            ->where('pv.id', $prdVariantId);

        $q = $this->db->get();

        $row = $q->row_array();

        if ($row){
            return $row;
        }

        return null;

    }

    public function getStore(int $productId): ?string
    {
        $this->db->select('p.store_id')
            ->from('products as p')
            ->where('p.id', $productId);

        $q = $this->db->get();

        $row = $q->row_array();

        if ($row){
            return $row['store_id'];
        }

        return null;

    }

    public function getCategoryId(int $productId): ?int
    {
        $this->db->select('p.category_id')
            ->from('products as p')
            ->where('p.id', $productId);

        $q = $this->db->get();

        $row = $q->row_array();


        if ($row){

            $categoryId = json_decode($row['category_id']);

            if ($categoryId){
                return (int)$categoryId[0];
            }

        }

        return null;

    }

    public function getGmvLast30Days($productId)
    {
        $dateStart = date('Y-m-d', strtotime('-30 days'));
        $dateEnd = date('Y-m-d');

        $sql = "SELECT SUM(orders_item.amount) as gmv";
        $sql.= " FROM orders_item ";
        $sql.= " JOIN orders ON (orders.id = orders_item.order_id AND orders.date_time BETWEEN '{$dateStart}' AND '{$dateEnd}') ";
        $sql.= " WHERE product_id = $productId ";

        $query = $this->db->query($sql);

        $row = $query->row_array();

        if (!$row){
            return 0;
        }

        return number_format($row['gmv'], '2', '.', '');

    }

    /**
     * Recupera dados da variação do produto pelo SKU do produto e SKU da variação
     *
     * @param   string      $sku    SKU do produto (products.sku)
     * @param   int         $store  Código da loja (stores.id)
     * @param   string      $skuVar SKU da variação (prd_variants.sku)
     * @return  null|array          Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForSkuAndSkuVar(string $sku, int $store, string $skuVar): ?array
    {
        return $this->db
            ->select('prd_variants.*, products.has_variants, products.image as image_product')
            ->from('products')
            ->join('prd_variants', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'products.sku' => $sku,
                    'products.store_id' => $store,
                    'prd_variants.sku' => $skuVar
                )
            )
            ->get()
            ->row_array();
    }

    /**
     * @param   array   $data   Dados do produto para atualizar
     * @param   string  $sku    Código SKU do produto (products.sku)
     * @param   int     $store  Código da loja (stores.id)
     * @param   string  $change Mensagem para gravar log
     * @return  bool            Status da atualização
     */
    public function updateProductBySkuAndStore(array $data, string $sku, int $store, string $change = 'Alterado'): bool
    {
        if ($data && $sku && $store) {
            $dataPrd = $this->getProductCompleteBySkyAndStore($sku, $store);

            if (!$dataPrd) {
                return false;
            }

            //$this->updatePriceAndStockMicroservice($dataPrd['id'], null, $data);

            $update = $this->update($data, $dataPrd['id'], $change);

            //$update = $this->db->where('id', $dataPrd['id'])->update('products', $data);

            // cria os preços e estoque por marketplace se não existir
            //$this->model_products_marketplace->newProduct($dataPrd['id']);
            //$this->model_log_products->create_log_products($data, $dataPrd['id'], 'Alterado: new data\n' . json_encode($data));

            // Acerto o estoque dos kits que esrteproduto faz parte
            //$this->checkKitMinimumStock($dataPrd['id']);

            return $update == true;
        }
        return false;
    }

    /**
     * @param   string      $sku            Código SKU do produto (products.sku)
     * @param   string      $idIntegration  Código da integradora
     * @param   int         $store          Código da loja (stores.id)
     * @param   string|null $var            Código SKU da variação (prd_variants.sku)
     * @return  bool                        Estado da atualização
     */
    public function updateIdIntegrationBySkuAndStore(string $sku, string $idIntegration, int $store, string $var = null): bool
    {
        if ($var) {
            $data = array('prd_variants.variant_id_erp' => $idIntegration);
            $update = (bool)$this->db
                ->where('prd_variants.sku', $var)
                ->where("prd_variants.prd_id = (SELECT products.id FROM products WHERE products.sku = '$sku' AND products.store_id = $store)")
                ->update('prd_variants', $data);
        } else {
            $data = array('product_id_erp' => $idIntegration);
            $update = (bool)$this->db
                ->where(array('sku' => $sku, 'store_id' => $store))
                ->update('products', $data);
        }

        return $update;
    }

    /**
     * Recupera dados do produto pelo código SKU da variação
     *
     * @param string $skuVar
     * @param int $store
     * @return mixed
     */
    public function getDataProductBySkuVarAndStore(string $skuVar, int $store)
    {
        return $this->db
            ->select('products.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(['prd_variants.sku' => $skuVar, 'products.store_id' => $store])
            ->get()->row_array();
    }

    public static function isActive($product): bool
    {
        return ((int)$product['status']) === self::ACTIVE_PRODUCT;
    }

    /**
     * @param   int         $store_id   Código da loja (stores.id).
     * @param   int         $offset     Deslocamento da consulta.
     * @param   int         $limit      Limite de resultados na consulta.
     * @param   string|null $skuProd    SKU, caso queira pesquisar apenas um único SKU.
     * @return  array                   Dados de produtos da consulta.
     */
    public function getProductsActiveByStore(int $store_id, int $offset = 0, int $limit = 200, string $skuProd = null): array
    {
        $sql = "SELECT * FROM products WHERE store_id = ? AND status <> ?";

        if ($skuProd !== null) {
            $sql .= " AND sku = '$skuProd'";
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";

        $query = $this->db->query($sql, [$store_id, self::DELETED_PRODUCT, $limit, $offset]);
        return $query->result_array();
    }

    /**
     * Verifica se o produto pertence a loja.
     *
     * @param   int  $store     Código da loja (stores.id).
     * @param   int  $product   Código do produto (stores.id).
     * @return  bool
     */
    public function checkProductStore(int $store, int $product): bool
    {
        return $this->db->where(array('id' => $product, 'store_id' => $store))->count_all_results('products') > 0;
    }

    /**
     * Consulta produtos por um vetor de códigos.
     *
     * @param   array    $products  Códigos dos produtos (stores.id).
     * @param   int|null $store     Código da loja (stores.id).
     * @return  array
     */
    public function getProductsByIds(array $products, int $store = null): array
    {
        $this->db->select('id, sku, name, store_id, company_id, price, qty')->where_in('id', $products);

        if ($store) {
            $this->db->where('store_id', $store);
        }

        return $this->db->get('products')->result_array();
    }

    public function setStockStore(int $store_id): bool
    {
        if($store_id){
            return (bool)$this->db->where('store_id', $store_id)->update('products',array('qty'=>0));
        }

        return false;
    }

    public function setStatusStore(int $store_id, int $status_store): bool
    {
        if($store_id){
            return (bool)$this->db->where('store_id', $store_id)->update('products',array('status' => $status_store));
        }

        return false;
    }

    public function getSellerCenterActive(): array
    {
        return $this->db->select()
        ->from('settings')
        ->where(['name' => 'sellercenter'])
        ->get()
        ->row_array();
    }

    public function getProductsWithoutCategory(): array
    {
        return $this->db->select()
        ->from('products')
        ->where(['category_id' => '[""]'])
        ->where(['omnilogic_status' => 'NEW'])
        ->limit(200)
        ->get()
        ->result_array();
    }

    public function updateOmnilogicStatus(int $product_id)
    {
        $this->db->set('omnilogic_status','SENT')
        ->where(['id' => $product_id])
        ->update('products');
    }

    public function updateProdutoSkutMktWithVariations($skumkt, $id)
    {
        $data = [
            'skumkt' => $skumkt,
            'skubling' => $skumkt
        ];

        $this->db->where('id', $id);
        $this->db->update('prd_to_integration', $data);
    }

    public function updateProdutoSkutMkt($skumkt, $id)
    {
        $data = [
            'skumkt' => $skumkt,
            'skubling' => $skumkt
        ];

        $this->db->where('id', $id);
        $this->db->update('prd_to_integration', $data);
    }


    public function getProductsBySkuVariantAndStore(string $sku_var, int $store_id): ?array
    {
        return $this->db->select('p.*, v.sku as sku_var, v.variant')
            ->join('products as p', 'v.prd_id = p.id')
            ->where(['v.sku' => $sku_var, 'p.store_id' => $store_id])
            ->get('prd_variants as v')
            ->row_array();
    }

    public function getByPrincipalImageNull(int $offset = 0, int $limit = 200): array
    {
        return $this->db
            ->where('status !=', self::DELETED_PRODUCT)
            ->where('has_variants !=', '')
            ->where('principal_image IS NULL', null, false)
            ->offset($offset)
            ->limit($limit)
            ->get('products')
            ->result_array();
    }

    public function getByPrdIdIntToVariant(int $prd_id, string $int_to, $variant = null): ?array
    {
        if ($variant === '') {
            $variant = null;
        }

        return $this->db
            ->where(
                array(
                    'prd_id'    => $prd_id,
                    'int_to'    => $int_to,
                    'variant'   => $variant
                )
            )->get('prd_to_integration USE INDEX (ix_prd_id_int_to_variant_prd_to_integration)')
            ->row_array();
    }

    public function getVetxColectionsForSelect()
    {
        return [];
        /*return $this->db->select('id, name')
        ->from('catalog_collections')
        ->get()
        ->result_array();*/
    }

    public function getVtexColections($filterCount, $colection)
    {
        return [];
        /*$remove = ['[', ']', '"'];
        $colection = explode(",", $colection);
        $colection = str_replace($remove, '', $colection);

        if ($filterCount && $filterCount > 5) {
            return $this->db->where_in('id', $colection)
                ->select('id, name')
                ->from('catalog_collections')
                ->get()
                ->result_array();

        }
        return $this->db->where_in('id', $colection)
            ->select('id, name')
            ->from('catalog_collections')
            ->get()
            ->row_array();*/
    }

    public function getCatalogColections(array $colections)
    {
        return [];
        /*return $this->db->where_in('id', $colections)
            ->select(["*"])
            ->from('catalog_collections')
            ->get()
            ->result_array() ?? [];*/
    }

    public function getByCategoryWithLimit(int $category_id, int $limit = 200, $select = '*'): array
    {
        return $this->db
            ->select($select)
            ->where(
                array(
                    'category_id' => json_encode(array((string)$category_id)),
                    'status !=' => self::DELETED_PRODUCT,
                )
            )
            ->limit($limit)
            ->get('products')
            ->result_array();
    }

    public function updateByProductIds(array $data, array $products): bool
    {
        return $this->db->where_in('id', $products)->update('products', $data);
    }

    public function movedToTrash(array $product = [])
    {
        if (empty($product['productId'] ?? null)) return;
        foreach ($product['variations'] ?? [] as $variation) {
            $this->handleTrashEventInMS(__FUNCTION__, $variation);
        }
        $this->handleTrashEventInMS(__FUNCTION__, $product);
    }

    public function deletedFromTrash(array $product = [])
    {
        if (empty($product['productId'] ?? null)) return;
        foreach ($product['variations'] ?? [] as $variation) {
            $this->handleTrashEventInMS(__FUNCTION__, $variation);
        }
        $this->handleTrashEventInMS(__FUNCTION__, $product);
    }

    protected function handleTrashEventInMS(string $event, array $data)
    {
        $marketplaces = explode(',', $data['marketplaces'] ?? '');
        foreach ($marketplaces ?? [] as $marketplace) {
            try {
                if ($this->ms_price->use_ms_price) {
                    $this->ms_price->deleteCampaignPrice($marketplace, $data['productId'], $data['variant'] ?? null);
                    $this->ms_price->deletePromotionPrice($marketplace, $data['productId'], $data['variant'] ?? null);
                    $this->ms_price->deleteMarketplacePrice($marketplace, $data['productId'], $data['variant'] ?? null);
                    $this->ms_price->deleteCatalogsPrice($marketplace, $data['productId'], $data['variant'] ?? null);
                }
                if ($this->ms_stock->use_ms_stock) {
                    $this->ms_stock->deleteMarketplaceStock($marketplaces, $data['productId'], $data['variant'] ?? null);
                }
            } catch (Throwable $e) {
            }
        }
        if ($this->ms_price->use_ms_price) {
            if (strcasecmp($event, 'deletedFromTrash') === 0) {
                $this->ms_price->deleteProductPrice($data['productId'], $data['variant'] ?? null);
            }
        }
        if ($this->ms_stock->use_ms_stock) {
            if (strcasecmp($event, 'movedToTrash') === 0) {
                try {
                    $this->ms_stock->updateProductStock($data['productId'], $data['variant'] ?? null, 0);
                } catch (Throwable $e) {
                }
            } else if (strcasecmp($event, 'deletedFromTrash') === 0) {
                $this->ms_stock->deleteProductStock($data['productId'], $data['variant'] ?? null);
            }
        }
    }

    public function getCategoriesByStoreProduct(array $store_id): array
    {
        $where = array();
        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $where['company_id'] = $this->data['usercomp'];
            } else {
                $where['store_id'] = $this->data['userstore'];
            }
        }

        return $this->db
            ->distinct('c.id')->select('c.id, c.name')
            ->join('categories as c', 'c.id = left(substr(p.category_id,3),length(p.category_id)-4)')
            ->where($where)
            ->where_in('p.store_id', $store_id)
            ->get('products p')
            ->result_array();
    }


    public function getProductById($product_id = null){
        if(is_null($product_id)){
            return null;
        }

        return $this->db->where('id', $product_id)->get('products')->row();
    }

    public function getOtherStoreFullFilmentProduct(string $sku, array $stores_cd) {
        $sql = "SELECT MAX(CAST(qty AS UNSIGNED)) AS `qty`
             FROM `products`
             WHERE `sku` = ?
             AND `store_id` IN ?
             AND `status` = 1
             AND `qty` > 0";
         $query = $this->db->query($sql, array($sku, $stores_cd));
         return $query->row_array();
     }

     public function getOtherStoreFullFilmentVariant(string $sku, array $stores_cd) {

         $sql = "SELECT MAX(CAST(prd_variants.qty AS UNSIGNED)) AS `qty`
                 FROM `prd_variants`
                 JOIN `products` ON `products`.`id` = `prd_variants`.`prd_id`
                 WHERE `prd_variants`.`sku` = ?
                 AND `products`.`store_id` IN ?
                 AND `products`.`status` = 1
                 AND `prd_variants`.`qty` >0";
         $query = $this->db->query($sql, array($sku, $stores_cd));
         return $query->row_array();
     }

    public function getProductGroupedVariantsBySkuAndStore($sku_var, $store_id)
    {
        if ($sku_var && $store_id) {
            return $this->db->select('p.*')
                ->join('products as p', 'v.prd_id = p.id')
                ->where(array(
                    'v.sku' => $sku_var,
                    'p.store_id' => $store_id,
                    'is_variation_grouped' => 1
                ))
                ->get('prd_variants as v')
                ->row_array();
        }
        return false;
    }

    public function listCompleteVariationProduct(int $offset, int $limit): array
    {
        return $this->db->select('p.id')
            ->where([
                'p.status !=' => self::DELETED_PRODUCT,
                'p.has_variants !=' => ''
            ])
            ->order_by('id', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get('products p')
            ->result_array();
    }

    public function getProductsModified($offset, $limit, $idDebug = false)
    {
        $date_start = date('Y-m-d 00:00:00');
        $date_end = date('Y-m-d 23:59:59');

        // Seleciona apenas os campos necessários
        $query = $this->db->select(
            [
                'products.id',
                'products.sku',
                'products.store_id',
                'products.date_update'
            ]
        );

        // Filtro de depuração (caso seja fornecido um id para debug)
        if ($idDebug) {
            $query->where('products.id', $idDebug);
        }

        // Filtro de produtos modificados no dia atual
        return $query->where_in('products.status', [1,2,3,4])
            ->where('products.date_update >=', $date_start)
            ->where('products.date_update <=', $date_end)
            ->limit($limit)
            ->offset($offset)
            ->order_by('products.id', 'desc')
            ->get('products')
            ->result_array();
    }

    /**
     * @param   string      $idIntegration  Código da integradora
     * @param   bool        $var            Existe variação
     * @return  null|array                  Dados do produto ou variação
     */
    public function getIdIntegrationByIdIntegration(string $idIntegration, int $store_id, bool $var = false): ?array
    {
        if ($var) {
            return $this->db
                ->join('products p', 'p.id = pv.prd_id')
                ->where(array('pv.variant_id_erp' => $idIntegration, 'p.store_id' => $store_id))
                ->get('prd_variants pv')
                ->row_array();
        }

        return $this->db
            ->where(array('product_id_erp' => $idIntegration, 'store_id' => $store_id))
            ->get('products')
            ->row_array();
    }

    public function updateActiveByProductCatalogId(int $product_catalog_id, array $data): bool
    {
        return $this->db->update('products',
            $data,
            array(
                'product_catalog_id' => $product_catalog_id,
                'status' => Model_products::ACTIVE_PRODUCT
            )
        );
    }

    public function getOriginPrice($ean){
        $row = $this->db->select('original_price')
                                 ->from('products_catalog')
                                 ->where('EAN', $ean)
                                 ->get()
                                 ->row();

        return $row ? (float) $row->original_price : 0.0;

    }
    public function getSuggestedPrice($ean){
        $row = $this->db->select('price')
                                 ->from('products_catalog')
                                 ->where('EAN', $ean)
                                 ->get()
                                 ->row();

        return $row ? (float) $row->price : 0.0;

    }
    public function getRefIdVtex($ean){
        return $this->db->select('ref_id')
                                 ->from('products_catalog')
                                 ->where('EAN', $ean)
                                 ->get()
                                 ->result_array();


    }
    public function getSkuIdVtex($ean){
        return $this->db->select('mkt_sku_id')
                                 ->from('products_catalog')
                                 ->where('EAN', $ean)
                                 ->get()
                                 ->result_array();


    }

    /**
     * @param   int         $store_id   Código da loja (stores.id).
     * @param   int         $last_id    ùltimo registro encontrado
     * @param   int         $limit      Limite de resultados na consulta.
     * @param   string|null $skuProd    SKU, caso queira pesquisar apenas um único SKU.
     * @return  array                   Dados de produtos da consulta.
     */
    public function getProductsActiveByStoreAndLastId(int $store_id, int $last_id = 0, int $limit = 200, string $skuProd = null): array
    {
        $sql = "SELECT * FROM products WHERE store_id = ? AND status <> ? and id > ?";

        if ($skuProd !== null) {
            $sql .= " AND sku = '$skuProd'";
        }

        $sql .= " ORDER BY id DESC LIMIT ?";

        $query = $this->db->query($sql, [$store_id, self::DELETED_PRODUCT, $last_id, $limit]);
        return $query->result_array();
    }

public function getTrashedProductByStoreId(int $store_id, int $last_id, int $limit = 5000): array
    {
        return $this->db->select('alfi.*')
            ->join('prd_to_integration pti', 'pti.prd_id = alfi.prd_id')
            ->where('existing IS NULL', NULL, FALSE)
            ->where(array(
                'alfi.copied' => false,
                'alfi.id >' => $last_id,
                'alfi.created_at >' => '2025-06-24 00:00:00',
                'alfi.store_id' => $store_id,
                'alfi.error !=' => 'Imagem com erro do nome da pasta'
            ))
            ->not_like('alfi.error', 'Não foi possível enviar o produto pra lixeira')
            ->order_by('alfi.id', 'ASC')
            ->group_by('alfi.prd_id')
            ->limit($limit)
            ->get('anymarket_log_fix_id AS alfi')
            ->result_array();
    }



}
